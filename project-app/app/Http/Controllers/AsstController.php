<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\department;
use App\Models\Maintenance;
use App\Models\assetModel;
use App\Models\AssignedToUser;
use App\Models\category;
use App\Models\locationModel;
use App\Models\Manufacturer;
use App\Models\ModelAsset;
use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\SystemNotification;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AsstController extends Controller
{
    public function showAllAssets(Request $request)
    {
        // Fetch department information (if provided)
        $deptId = $request->input('dept', null);
        $departmentName = $deptId ? DB::table('department')->where('id', $deptId)->value('name') : null;

        // Fetch categories based on department ID
        $categoriesList = DB::table('category')
            ->when($deptId, fn($q) => $q->where('dept_ID', $deptId))
            ->get();

        // Sorting parameters with default values
        $sortBy = $request->input('sort_by', 'asset.name');
        $sortOrder = strtolower($request->input('sort_order', 'asc'));

        $validSortFields = [
            'asset.name',
            'asset.code',
            'category.name',
            'department.name',
            'asset.depreciation',
            'asset.status'
        ];
        if (!in_array($sortBy, $validSortFields)) {
            $sortBy = 'asset.name';
        }
        if (!in_array($sortOrder, ['asc', 'desc'])) {
            $sortOrder = 'asc';
        }

        // Pagination, search query, and filters
        $perPage = max((int) $request->input('rows_per_page', 10), 10);
        $query = $request->input('query', '');

        $statuses = $request->input('status', []);
        $categories = $request->input('category', []);
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        // Build the query for assets
        $assetsQuery = DB::table('asset')
            ->join('department', 'asset.dept_ID', '=', 'department.id')
            ->join('category', 'asset.ctg_ID', '=', 'category.id')
            ->select('asset.*', 'department.name as department', 'category.name as category')
            ->where('asset.isDeleted', 0)
            ->when($deptId, fn($q) => $q->where('asset.dept_ID', $deptId))
            ->when($query !== '', fn($q) => $q->where(function ($subquery) use ($query) {
                $subquery->where('asset.name', 'like', '%' . $query . '%')
                    ->orWhere('asset.code', 'like', '%' . $query . '%');
            }))
            ->when(!empty($statuses), fn($q) => $q->whereIn('asset.status', $statuses))
            ->when(!empty($categories), fn($q) => $q->whereIn('category.id', $categories))
            ->when($startDate && $endDate, fn($q) => $q->whereBetween('asset.created_at', [$startDate, $endDate]));

        // Apply sorting and paginate the results
        $assets = $assetsQuery
            ->orderBy($sortBy, $sortOrder)
            ->paginate($perPage)
            ->appends($request->all());

        // Return the view with all parameters
        return view('admin.assetList', compact(
            'assets',
            'sortBy',
            'sortOrder',
            'perPage',
            'deptId',
            'departmentName',
            'categoriesList'
        ));
    }

    public function showDeptAsset(Request $request)
    {
        $userDept = Auth::user()->dept_id;

        // Get query parameters with defaults
        $search = $request->input('search');
        $sortField = $request->input('sort', 'code');
        $sortDirection = $request->input('direction', 'asc');
        $rowsPerPage = $request->input('rows_per_page', 10);

        // Ensure statuses and categories are always arrays
        $statuses = $request->input('status', []);
        $categories = $request->input('category', []);

        if (is_string($statuses)) {
            $statuses = json_decode($statuses, true) ?? [];
        }

        if (is_string($categories)) {
            $categories = json_decode($categories, true) ?? [];
        }

        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        // Validate sorting field
        $validSortFields = ['code', 'name', 'category_name', 'status', 'depreciation', 'purchase_date'];
        if (!in_array($sortField, $validSortFields)) {
            $sortField = 'code';
        }

        // Build the query with filters
        $assets = DB::table('asset')
            ->join('department', 'asset.dept_ID', '=', 'department.id')
            ->join('category', 'asset.ctg_ID', '=', 'category.id')
            ->where('asset.dept_ID', $userDept)
            ->where('asset.isDeleted', 0)
            ->when($search, function ($query, $search) {
                return $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('asset.name', 'like', "%{$search}%")
                        ->orWhere('asset.code', 'like', "%{$search}%");
                });
            })
            ->when(!empty($statuses), function ($query) use ($statuses) {
                return $query->whereIn('asset.status', $statuses);
            })
            ->when(!empty($categories), function ($query) use ($categories) {
                return $query->whereIn('category.id', $categories);
            })
            ->when($startDate && $endDate, function ($query) use ($startDate, $endDate) {
                return $query->whereBetween('asset.purchase_date', [$startDate, $endDate]);
            })
            ->select('asset.*', 'category.name as category_name', 'department.name as department')
            ->orderBy($sortField, $sortDirection)
            ->paginate($rowsPerPage)
            ->appends($request->except('page'));

        // Fetch all categories for the dropdown (filtered by department)
        $categoriesList = DB::table('category')->where('dept_ID', $userDept)->get();

        // Return the view with the necessary data
        return view('dept_head.asset', compact('assets', 'categoriesList'));
    }


    public function showHistory($id)
    {
        //history of a Asset
        $asset = AssetModel::where('asset.id', $id)
            ->select("asset.code as assetCode")->first();

        $assetRet = Maintenance::where("asset_key", $id)
            ->where("is_completed", 1)
            ->join('users', 'users.id', '=', 'maintenance.requestor')
            ->select(
                'maintenance.reason as reason',
                'maintenance.type as type',
                'maintenance.cost as cost',
                'maintenance.description as description',
                DB::raw('DATE(maintenance.completion_date) AS complete',),
                'maintenance.status as status',
                'users.firstname as fname',
                'users.lastname as lname',
            )->groupBy(
                'maintenance.completion_date',
                'maintenance.status',
                'maintenance.type',
                'maintenance.cost',
                'users.firstname',
                'users.lastname',
                'maintenance.description',
                'maintenance.reason',
            )
            ->orderByRaw("FIELD(maintenance.status, 'request', 'pending', 'in_progress','complete','denied','denied')")
            ->orderBy('maintenance.completion_date', 'asc')
            ->get();
        $AssetMaintenance = Maintenance::where("asset_key", $id)->get();

        return view('dept_head.MaintenanceHistory', compact('assetRet', 'asset'));
    }

    public function showForm()
    {
        $userRole = Auth::user()->usertype;
        $departments = department::all();
        $defaultDeptId = $departments->first()->id ?? null;
        $usrDPT = $userRole === 'admin' ? $defaultDeptId : Auth::user()->dept_id;

        // Retrieve department details
        $department = department::find($usrDPT);
        if (!$department) {
            return redirect()->back()->with('noSettings', 'Department not found.');
        }

        // Fetch related data based on department
        $categories = ['ctglist' => DB::table('category')->where('dept_ID', $usrDPT)->get()];
        $location = ['locs' => DB::table('location')->where('dept_ID', $usrDPT)->get()];
        $model = ['mod' => DB::table('model')->where('dept_ID', $usrDPT)->get()];
        $manufacturer = ['mcft' => DB::table('manufacturer')->where('dept_ID', $usrDPT)->get()];
        $addInfos = json_decode($department->custom_fields);

        // Check if any of the settings are empty or the usertype is not Admin
        if (
            Auth::user()->usertype !== 'admin' &&
            ($categories['ctglist']->isEmpty() ||
                $location['locs']->isEmpty() ||
                $model['mod']->isEmpty() ||
                $manufacturer['mcft']->isEmpty())
        ) {
            return redirect()->back()->with('noSettings', 'Some settings are missing. Please set up your settings.');
        }

        if ($userRole === 'admin') {
            $view = 'admin.createAsset';
            $compactContent = compact(
                'addInfos',
                'categories',
                'location',
                'model',
                'manufacturer',
                'departments',
                'usrDPT',
            );
        } else {
            $view = 'dept_head.createAsset';
            $compactContent = compact(
                'addInfos',
                'categories',
                'location',
                'model',
                'manufacturer',
                'usrDPT',
            );
        }

        return view($view, $compactContent);
    }

    public function fetchDepartmentData($id)
    {
        $categories = Category::where('dept_ID', $id)->get();
        $locations = locationModel::where('dept_ID', $id)->get();
        $models = ModelAsset::where('dept_ID', $id)->get();
        $manufacturers = Manufacturer::where('dept_ID', $id)->get();
        $department =  department::findOrFail($id);
        $addInfos = json_decode($department->custom_fields);
        return response()->json([
            'categories' => $categories,
            'locations' => $locations,
            'models' => $models,
            'manufacturers' => $manufacturers,
            'addInfos' => $addInfos
        ]);
    }

    public  function convertJSON($key, $value)
    {
        $additionalInfo = [];
        // Initialize an empty array to hold key-value pairs
        if (isset($key) && isset($value)) {
            foreach ($key as $index => $keys) {
                if (!empty($key) && !empty($value[$index])) {
                    $additionalInfo[$keys] = $value[$index];
                }
            }
        }
        return json_encode($additionalInfo);
    }

    public function create(Request $request)
    {
        $userDept = Auth::user()->dept_id;
        $deptHead = Auth::user();
        $isAdmin = $deptHead->usertype === 'admin';

        // Validate the request
        $request->validate([
            'asst_img' => 'sometimes|image|mimes:jpeg,png,jpg,gif',
            'assetname' => 'required',
            'category' => 'required',
            'department' => $isAdmin ? 'required|exists:department,id' : 'nullable',
            'purchasedDate' => 'nullable|date|before_or_equal:today',
            'pCost' => 'required|numeric|min:0.01',
            'lifespan' => 'required|integer|min:0',
            'salvageValue' => 'required|numeric|min:0|lt:pCost',
            'depreciation' => 'required|numeric|min:0',
            'loc' => 'required|exists:location,id',
            'mod' => 'required|exists:model,id',
            'mcft' => 'required|exists:manufacturer,id',
            'field.key.*' => 'nullable|string|max:255',
            'field.value.*' => 'nullable|string|max:255',
        ], [
            'asst_img.image' => 'The asset image must be a valid image file.',
            'asst_img.mimes' => 'The asset image must be a file of type: jpeg, png, jpg, or gif.',
            'assetname.required' => 'The asset name is required.',
            'category.required' => 'Please select a category for the asset.',
            'department.required' => 'The department field is required.',
            'purchasedDate.date' => 'The purchase date must be a valid date.',
            'purchasedDate.before_or_equal' => 'The purchase date cannot be in the future.',
            'pCost.required' => 'The purchase cost is required.',
            'lifespan.required' => 'The lifespan of the asset is required.',
            'salvageValue.required' => 'The salvage value is required.',
            'salvageValue.lt' => 'The salvage value must be less than the purchase cost.',
            'depreciation.required' => 'The depreciation value is required.',
            'loc.required' => 'The location is required.',
            'mod.required' => 'The model is required.',
            'mcft.required' => 'The manufacturer is required.',
        ]);

        // Determine the department ID
        $departmentId = $isAdmin ? $request->department : $userDept;

        // Additional Fields
        $customFields = $this->convertJSON($request->input('field.key'), $request->input('field.value'));

        // Generate Asset Code
        $department = DB::table('department')->where('id', $departmentId)->first();
        $departmentCode = $department->name;
        $lastID = department::where('name', $departmentCode)->max('assetSequence');
        $seq = $lastID ? $lastID + 1 : 1;
        $code = $departmentCode . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT);

        // Handle image upload
        $pathFile = null;
        if ($request->hasFile('asst_img')) {
            $image = $request->file('asst_img');
            $filename = $code . '-' . time() . '.' . $image->getClientOriginalExtension();
            $path = $image->storeAs('asset_images', $filename, 'public');
            $pathFile = $path;
        }

        // Increment asset sequence in department
        department::where('id', $departmentId)->increment('assetSequence', 1);

        // Generate QR Code
        $qrCodePath = 'qrcodes/' . $code . '.png';
        $qrStoragePath = storage_path('app/public/' . $qrCodePath);

        // Ensure the directory exists
        if (!file_exists(storage_path('app/public/qrcodes'))) {
            mkdir(storage_path('app/public/qrcodes'), 0777, true);
        }

        \SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')
            ->size(250)
            ->generate($code, $qrStoragePath);

        // Save asset details to the database
        DB::table('asset')->insert([
            'asst_img' => $pathFile,
            'name' => $request->assetname,
            'code' => $code,
            'purchase_cost' => $request->pCost,
            'purchase_date' => $request->purchasedDate,
            'depreciation' => $request->depreciation,
            'usage_lifespan' => $request->lifespan,
            'salvage_value' => $request->salvageValue,
            'ctg_ID' => $request->category,
            'custom_fields' => $customFields,
            'dept_ID' => $departmentId,
            'loc_key' => $request->loc,
            'model_key' => $request->mod,
            'manufacturer_key' => $request->mcft,
            'qr_img' => $qrCodePath,
            'created_at' => now(),
        ]);

        // Log the activity
        ActivityLog::create([
            'activity' => 'Create New Asset via System',
            'description' => "User {$deptHead->firstname} {$deptHead->lastname} created a new asset '{$request->assetname}' (Code: {$code}).",
            'userType' => $deptHead->usertype,
            'user_id' => $deptHead->id,
            'asset_id' => DB::getPdo()->lastInsertId(),
        ]);

        // Notify the admin about the new asset creation
        $notificationData = [
            'title' => 'New Asset Created',
            'message' => "A new asset '{$request->assetname}' (Code: {$code}) has been added.",
            'asset_name' => $request->assetname,
            'asset_code' => $code,
            'action_url' => route('asset'),
            'authorized_by' => $deptHead->id,
            'authorized_user_name' => "{$deptHead->firstname} {$deptHead->lastname}",
        ];

        // Send the notification to all admins
        $admins = User::where('usertype', 'admin')->get();
        foreach ($admins as $admin) {
            $admin->notify(new \App\Notifications\SystemNotification($notificationData));
        }

        // Redirect based on user type
        $routePath = $isAdmin ? '/admin/assets' : '/asset';
        return redirect()->to($routePath)->with('success', 'New Asset Created');
    }

    public static function assetCount()
    {
        // Dashboard
        $userDept = Auth::user()->dept_id;
        $usertype = Auth::user()->usertype;

        // Initialize the months array for the last 4 months (including the current month)
        $months = [];
        for ($i = 3; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $monthYear = $date->format('M Y');
            $months[$monthYear] = ['active' => 0, 'under_maintenance' => 0];
        }

        // Query for counts by status (filtered by department if not admin)
        $statuses = ['active', 'deployed', 'under_maintenance', 'disposed'];
        foreach ($statuses as $status) {
            $query = DB::table('asset')->where('status', '=', $status);

            if ($usertype !== 'admin') {
                $query->where('dept_ID', '=', $userDept);
            }

            $asset[$status] = $query->count();
        }

        // Query for recently created assets (last 5) - filtered by department if not admin
        $newAssetCreatedQuery = assetModel::whereMonth('created_at', Carbon::now()->month)
            ->orderBy('created_at', 'desc')
            ->take(5);
        if ($usertype !== 'admin') {
            $newAssetCreatedQuery->where('dept_ID', $userDept);
        }
        $newAssetCreated = $newAssetCreatedQuery->get();

        // Query to fetch and group active assets by month (filtered by dept_ID if not admin)
        $dataActiveQuery = assetModel::where('status', 'active')
            ->select(
                DB::raw('DATE_FORMAT(created_at, "%b %Y") as monthYear'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('monthYear');
        if ($usertype !== 'admin') {
            $dataActiveQuery->where('dept_ID', $userDept);
        }
        $dataActive = $dataActiveQuery->get();

        // Query to fetch and group maintenance records by month for under maintenance assets
        $dataUnderMaintenanceQuery = Maintenance::join('asset', 'asset.id', '=', 'maintenance.asset_key')
            ->where('asset.status', 'under_maintenance')
            ->where('maintenance.status', 'approved')
            ->select(
                DB::raw('DATE_FORMAT(maintenance.created_at, "%b %Y") as monthYear'),
                DB::raw('COUNT(DISTINCT maintenance.id) as count')
            )
            ->groupBy('monthYear');
        if ($usertype !== 'admin') {
            $dataUnderMaintenanceQuery->where('asset.dept_ID', $userDept);
        }
        $dataUnderMaintenance = $dataUnderMaintenanceQuery->get();

        // Map the data into the months array (only for the last 4 months)
        foreach ($dataActive as $record) {
            if (isset($months[$record->monthYear])) {
                $months[$record->monthYear]['active'] = $record->count;
            }
        }

        foreach ($dataUnderMaintenance as $record) {
            if (isset($months[$record->monthYear])) {
                $months[$record->monthYear]['under_maintenance'] = $record->count;
            }
        }

        // Prepare data for the view
        $labels = array_keys($months);
        $activeCounts = array_column($months, 'active');
        $maintenanceCounts = array_column($months, 'under_maintenance');

        // Return the view with the data
        $view = ($usertype === 'admin') ? 'admin.home' : 'dept_head.home';
        return view($view, [
            'asset' => $asset,
            'newAssetCreated' => $newAssetCreated,
            'labels' => $labels,
            'activeCounts' => $activeCounts,
            'maintenanceCounts' => $maintenanceCounts,
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = Auth::user();
        $userType = $user->usertype;
        $userDept = $user->dept_id;

        $validatedData = $request->validate([
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif',
            'name' => 'sometimes|string',
            'category' => 'sometimes|exists:category,id',
            'usrAct' => 'nullable|exists:users,id',
            'mod' => 'sometimes|string',
            'mcft' => 'sometimes|exists:manufacturer,id',
            'loc' => 'sometimes|exists:location,id',
            'purchasedDate' => 'required|date|before_or_equal:today',
            'purchaseCost' => 'required|numeric|min:0.01',
            'lifespan' => 'required|integer|min:0',
            'salvageValue' => 'required|numeric|min:0|lt:purchaseCost',
            'depreciation' => 'required|numeric|min:0.01',
            'status' => 'sometimes|string|max:511',
            'field.key.*' => 'nullable|string|max:255',
            'field.value.*' => 'nullable|string|max:255',
            'current_image' => 'nullable|string',
        ], ['salvageValue.lt' => "Salvage value must be less than the Purchased cost"]);

        // dd($validatedData);

        // Retrieve department info and generate a new asset code if needed
        $department = DB::table('department')->where('id', $userDept)->first();
        $departmentCode = $department->name;
        $lastID = department::where('name', $departmentCode)->max('assetSequence');
        $seq = $lastID ? $lastID + 1 : 1;
        $code = $departmentCode . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT);

        // Convert custom fields into JSON
        $fieldUpdate = $this->convertJSON(
            $request->input('field.key'),
            $request->input('field.value')
        );

        $validatedData['purchasedDate'] = Carbon::parse($validatedData['purchasedDate'])->format('Y-m-d');

        // Update asset data in the database
        $updatedRow = assetModel::findOrFail($id);
        $oldLastUser = $updatedRow->last_used_by;

        // Prepare the data array for updating
        $updateData = [
            'name' => $validatedData["name"],
            'ctg_ID' => $validatedData["category"],
            'manufacturer_key' => $validatedData['mcft'],
            'model_key' => $validatedData["mod"],
            'loc_key' => $validatedData["loc"],
            'purchase_cost' => $validatedData["purchaseCost"],
            'purchase_date' => $validatedData["purchasedDate"],
            'depreciation' => $validatedData["depreciation"],
            'usage_lifespan' => $validatedData["lifespan"],
            'salvage_value' => $validatedData["salvageValue"],
            'last_used_by' => $validatedData["usrAct"],
            'status' => $validatedData["status"],
            'custom_fields' => $fieldUpdate,
            'updated_at' => now(),
        ];

        // Handle image upload or retain the current image
        $pathFile = $request->input('current_image');

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $filename = $code . '-' . time() . '.' . $image->getClientOriginalExtension();
            $path = $image->storeAs('asset_images', $filename, 'public');
            if ($updatedRow->asst_img && Storage::disk('public')->exists($updatedRow->asst_img)) {
                Storage::disk('public')->delete($updatedRow->asst_img);
            }
            $pathFile = $path;
        }

        // Only include 'asst_img' in the update if a new path is set
        if (isset($pathFile)) {
            $updateData['asst_img'] = $pathFile;
        }

        //Updating row
        $updatedRow->update($updateData);

        // Log the asset update activity
        ActivityLog::create([
            'activity' => 'Edit/Update Asset Details',
            'description' => "Department Head {$user->firstname} {$user->lastname} updated asset '{$updatedRow->name}' (Code: {$updatedRow->code}).",
            'userType' => $user->usertype,
            'user_id' => $user->id,
            'asset_id' => $id,
        ]);

        $settingUsageLogs = new AsstController();
        $assetKey = assetModel::findOrFail($id);
        if (isset($validatedData['usrAct'])) {
            $settingUsageLogs->assetAcquiredBy($validatedData["usrAct"], $assetKey->id);
        }
        if ($oldLastUser !== $validatedData["usrAct"]) {
            // dd("you are returning");
            $settingUsageLogs->assetReturnedBy($oldLastUser, $assetKey->id);
        }

        // Retrieve the updated asset to get the code
        $asset = DB::table('asset')->where('id', $id)->first();

        if ($updatedRow) {
            // Redirect based on user type
            $route = $userType === 'admin'
                ? 'adminAssetDetails'
                : 'assetDetails';

            return redirect()->route($route, ['id' => $asset->code])
                ->with('success', 'Asset updated successfully.');
        } else {
            $route = $userType === 'admin'
                ? 'adminAssetDetails'
                : 'assetDetails';

            return redirect()->route($route, ['id' => $asset->code])
                ->with('failed', 'Asset update failed.');
        }
    }

    public function searchFiltering(Request $request)
    {
        // Get search input (default to empty string if not provided)
        $search = $request->input('search', '');

        // Allowed statuses in predefined order
        $allowedStatuses = ['active', 'under_maintenance', 'deployed', 'disposed'];

        // Initialize query with necessary joins and filters
        $assetsQuery = assetModel::leftJoin('category', 'asset.ctg_ID', '=', 'category.id')
            ->where('asset.dept_ID', Auth::user()->dept_id)
            ->whereIn('asset.status', $allowedStatuses)
            ->select('asset.*', 'category.name as category_name');

        // Apply search filter if input is provided
        if (!empty($search)) {
            $assetsQuery->where(function ($query) use ($search) {
                $query->where('asset.name', 'LIKE', "%{$search}%")
                    ->orWhere('asset.code', 'LIKE', "%{$search}%")
                    ->orWhere('category.name', 'LIKE', "%{$search}%")
                    ->orWhere('asset.status', 'LIKE', "%{$search}%");
            });
        }

        // Sort by category name (alphabetically) and then by status in custom order
        $assetsQuery->orderByRaw("
                                CASE
                                    WHEN asset.status = 'active' THEN 0
                                    WHEN asset.status = 'under_maintenance' THEN 1
                                    WHEN asset.status = 'deployed' THEN 2
                                    WHEN asset.status = 'disposed' THEN 3
                                    ELSE 4
                                END
                            ")
            ->orderBy('code', 'asc')
            ->orderBy('asset.created_at', 'desc');

        // Paginate results
        $assets = $assetsQuery->paginate(10)->appends($request->all());
        $categoriesList = DB::table('category')->where('dept_ID', Auth::user()->dept_ID)->get();

        // Return the view with filtered and sorted results
        return view('dept_head.asset', compact('assets', 'categoriesList'));
    }

    public function multiDelete(Request $request)
    {
        // Get the selected asset IDs from the request
        $assetIds = $request->input('asset_ids', []);
        $routepath = Auth::user()->usertype === 'admin' ?  'assetList' : 'asset';

        if (count($assetIds) > 0) {
            try {
                // Loop through each asset ID and call the delete function
                foreach ($assetIds as $id) {
                    $this->delete($id);
                }

                return redirect()->route($routepath)->with('success', 'Selected assets have been deleted.');
            } catch (Exception $e) {
                // Handle the exception and redirect with error message
                return redirect()->route($routepath)->with('error', 'Failed to delete selected assets.');
            }
        }

        return redirect()->route($routepath)->with('error', 'No assets selected for deletion.');
    }

    public function delete($id)
    {
        $assetDel = assetModel::findOrFail($id); // Find asset by ID
        $userLogged = Auth::user();
        $assetDel->updated_at = now(); // Optionally update the timestamp
        $assetDel->delete();

        // Log the activity
        ActivityLog::create([
            'activity' => 'Asset is Deleted via System',
            'description' => "User {$userLogged->firstname} {$userLogged->lastname} Delete a asset '{$userLogged->assetname}' (Code: {$assetDel->code}).",
            'userType' => $userLogged->usertype,
            'user_id' => $userLogged->id,
            'asset_id' => $assetDel->id,
        ]);

        // Notify the admin about the new asset creation
        $notificationData = [
            'title' => 'Asset Deleted',
            'message' => "Asset '{$assetDel->assetname}' (Code: {$assetDel->code}) has been deleted.",
            'asset_name' => $assetDel->name,
            'asset_code' => $assetDel->code,
            'authorized_by' => $userLogged->id,
            'authorized_user_name' => "{$userLogged->firstname} {$userLogged->lastname}",
        ];

        // Send the notification to all admins
        $admins = User::where('usertype', 'admin')->get();
        foreach ($admins as $admin) {
            $admin->notify(new \App\Notifications\SystemNotification($notificationData));
        }

        $routPath = Auth::user()->usertype === 'admin' ? 'assetList' : 'asset';

        return redirect()->route($routPath)->with('success', 'Asset Deleted Successfully');
    }

    public function UsageHistory($id)
    {
        return AssignedToUser::with(['assetUserBy', 'assignedBy'])
            ->where('asset_id', $id)->get();
    }

    public function showDetails($code)
    {
        // Get the logged-in user's department ID and user type
        $userDept = Auth::user()->dept_id;
        $userType = Auth::user()->usertype;
        $department = ['list' => DB::table('department')->get()];
        $status = ['sts' => ['active', 'deployed', 'need repair', 'under_maintenance', 'dispose']];

        $categories = [
            'ctglist' => DB::table('category')->when($userType != 'admin', function ($query) use ($userDept) {
                return $query->where('dept_ID', $userDept);
            })->get()
        ];

        $location = [
            'locs' => DB::table('location')->when($userType != 'admin', function ($query) use ($userDept) {
                return $query->where('dept_ID', $userDept);
            })->get()
        ];

        $model = [
            'mod' => DB::table('model')->when($userType != 'admin', function ($query) use ($userDept) {
                return $query->where('dept_ID', $userDept);
            })->get()
        ];

        $manufacturer = [
            'mcft' => DB::table('manufacturer')->when($userType != 'admin', function ($query) use ($userDept) {
                return $query->where('dept_ID', $userDept);
            })->get()
        ];

        // Build the query to retrieve the asset data based on the asset code
        $retrieveDataQuery = assetModel::where('code', $code)
            ->leftJoin('department', 'dept_ID', '=', 'department.id')
            ->leftJoin('category', 'ctg_ID', '=', 'category.id')
            ->leftJoin('model', 'model_key', '=', 'model.id')
            ->leftJoin('manufacturer', 'manufacturer_key', '=', 'manufacturer.id')
            ->leftJoin('location', 'loc_key', '=', 'location.id')
            ->leftJoin('users', 'users.id', '=', 'asset.last_used_by')
            ->select(
                'asset.id',
                'asset.asst_img',
                'asset.name',
                'asset.code',
                'asset.depreciation',
                'asset.purchase_cost',
                'asset.purchase_date',
                'asset.usage_lifespan',
                'asset.salvage_value',
                'asset.status',
                'asset.last_used_by',
                'asset.custom_fields',
                'asset.qr_img',
                'asset.dept_ID',
                'asset.created_at',
                'asset.updated_at',
                'users.firstname',
                'users.lastname',
                'users.middlename',
                'category.name as category',
                'model.name as model',
                'location.name as location',
                'manufacturer.name as manufacturer'
            );
        // Apply department filter for dept_head and user
        if ($userType != 'admin') {
            $retrieveDataQuery->where('asset.dept_ID', '=', $userDept);
        }

        // Retrieve the asset data
        $retrieveData = $retrieveDataQuery->first();

        // If no asset is found, redirect with an error message
        if (!$retrieveData) {
            return redirect()->route('asset')->with('error', 'Asset not found.');
        }

        // Generate the correct asset image path or fallback to the default
        $imagePath = $retrieveData->asst_img && Storage::exists('public/' . $retrieveData->asst_img)
            ? asset('storage/' . $retrieveData->asst_img)
            : asset('images/no-image.png');

        // Check if the request is an AJAX call, return JSON if true
        if (request()->ajax()) {
            return response()->json([
                'image_url' => $imagePath,
            ]);
        }

        $usersDeptId = ($userType === 'admin') ? $retrieveData->dept_ID : $userDept;
        $allUserInDept = User::select('users.id', 'users.firstname', 'users.lastname')->get();

        // Retrieve asset and department data
        $asset = assetModel::find($retrieveData->id);
        $department = department::find($asset->dept_ID);

        // Decode custom_fields from both asset and department (assuming they are stored as JSON)
        $assetCustomFields = json_decode($asset->custom_fields, true) ?? [];
        $departmentCustomFields = json_decode($department->custom_fields, true) ?? [];

        // Create an empty array to hold the updated custom fields
        $updatedCustomFields = [];
        foreach ($departmentCustomFields as $deptField) {
            $fieldName = $deptField['name'];

            // Check if the asset has a value for this field
            $fieldValue = isset($assetCustomFields[$fieldName]) ? $assetCustomFields[$fieldName] : null;


            // Add the field to the updated custom fields array
            $updatedCustomFields[] = [
                'name' => $fieldName,
                'value' => $fieldValue,
                'type' => $deptField['type'],
                'helper' => $deptField['helptext']
            ];
        }

        // Retrieve related maintenance data for the asset
        $assetRet = Maintenance::where('asset_key', $retrieveData->id) // Use asset ID to match
            ->where('is_completed', 1)
            ->leftjoin('users', 'users.id', '=', 'maintenance.requestor')
            ->leftjoin('users as authorized', 'authorized.id', '=', 'maintenance.authorized_by')
            ->select(
                'users.firstname as fname',
                'users.lastname as lname',
                'maintenance.type',
                'authorized.firstname as authorized_fname',
                'authorized.lastname as authorized_lname',
                'maintenance.created_at',
                'maintenance.cost',
                'maintenance.reason',
                'maintenance.completion_date as complete'
            )
            ->get();

        // fetching
        $usageLogsAsset = $this->UsageHistory($retrieveData->id);

        // Determine the view based on user type
        $view = ($userType == 'admin') ? 'admin.assetDetail' : 'dept_head.assetDetail';
        // Return the appropriate view with the asset data, including the QR code
        return view($view, compact(
            'retrieveData',
            'updatedCustomFields',
            'department',
            'categories',
            'location',
            'model',
            'status',
            'manufacturer',
            'assetRet',
            'allUserInDept',
            'usageLogsAsset'
        ));
    }

    public function downloadCsvTemplate()
    {
        // Define the column names matching the asset table structure
        $columns = [
            'name',
            'purchase_date',
            'purchase_cost',
            'depreciation',
            'salvage_value',
            'usage_lifespan',
            'category',
            'manufacturer',
            'model',
            'location',
            'status'
        ];

        // Add a sample row with data matching the asset schema
        $sampleData = [
            'Sample Asset',
            now()->format('Y-m-d'),
            '10000',
            '500',
            '1000',
            '10',
            'IT Equipment',
            'Sony',
            'Model X',
            'HQ',
            'active'
        ];

        // Convert the column names and sample data into CSV format
        $csvContent = implode(",", $columns) . "\n";
        $csvContent .= implode(",", $sampleData) . "\n";
        // Return the CSV as a download
        return response($csvContent)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="asset_template.csv"');
    }

    public function saveNewManufacturers(Request $request)
    {
        $validated = $request->validate([
            'manufacturers' => 'required|array',
            'manufacturers.*.name' => 'required|string|max:255',
            'manufacturers.*.description' => 'required|string|max:500',
        ]);

        foreach ($validated['manufacturers'] as $manufacturer) {
            Manufacturer::create([
                'name' => ucfirst(strtolower($manufacturer['name'])),
                'description' => $manufacturer['description'],
                'dept_ID' => Auth::user()->dept_id,
            ]);
        }

        return response()->json(['success' => true, 'message' => 'Manufacturers added successfully.']);
    }


    public function uploadCsv(Request $request)
    {
        try {
            Log::info('Starting CSV upload process.');

            // Validate headers and rows
            $validated = $request->validate([
                'headers' => 'required|array',
                'rows' => 'required|array',
            ]);

            $headers = $request->input('headers');
            $rows = $request->input('rows');

            if (!$rows || count($rows) == 0) {
                Log::warning('No rows provided in the CSV.');
                return response()->json(['success' => false, 'message' => 'No rows provided.'], 400);
            }

            $userDept = Auth::user()->dept_id;
            $deptHead = Auth::user();
            $department = DB::table('department')->where('id', $userDept)->first();
            Log::info('Authenticated user department ID: ' . $userDept);

            // Array to store manufacturer names
            $manufacturersFromCsv = [];
            foreach ($rows as $row) {
                $rowData = array_combine($headers, $row);
                $manufacturerName = strtolower(trim($rowData['manufacturer'] ?? ''));
                if ($manufacturerName) {
                    $manufacturersFromCsv[] = $manufacturerName;
                }
            }
            $manufacturersFromCsv = array_unique($manufacturersFromCsv);
            Log::info('Manufacturers from CSV: ' . json_encode($manufacturersFromCsv));

            if (empty($manufacturersFromCsv)) {
                Log::warning('No manufacturers found in CSV.');
                return response()->json(['success' => false, 'message' => 'No manufacturers found in CSV.'], 400);
            }

            try {
                $allManufacturers = Manufacturer::pluck('name')->map(fn($name) => strtolower($name))->toArray();
                $existingManufacturers = array_intersect($manufacturersFromCsv, $allManufacturers);
                $newManufacturers = array_diff($manufacturersFromCsv, $allManufacturers);

                Log::info('Manufacturers from CSV: ' . json_encode($manufacturersFromCsv));
                Log::info('Existing manufacturers: ' . json_encode($existingManufacturers));
                Log::info('New manufacturers found: ' . json_encode($newManufacturers));
            } catch (\Exception $e) {
                Log::error('Error querying manufacturers: ' . $e->getMessage());
                return response()->json(['success' => false, 'message' => 'Error querying manufacturers.'], 500);
            }

            if (!empty($newManufacturers)) {
                return response()->json([
                    'success' => false,
                    'message' => 'New manufacturers found.',
                    'newManufacturers' => array_values($newManufacturers), // Send new manufacturers to the frontend
                ]);
            }

            // Initialize the $assets array to collect asset names and codes
            $assets = [];

            foreach ($rows as $row) {
                if (count($row) < count($headers)) {
                    Log::warning('Skipping a row due to insufficient columns.', ['row' => $row]);
                    return response()->json([
                        'success' => false,
                        'message' => 'Insufficient columns in a row. Check the CSV format.',
                        'row' => $row
                    ], 400);
                }

                $rowData = array_combine($headers, $row);

                // Validate and convert date
                try {
                    $purchaseDate = null;
                    $formats = ['d/m/Y', 'Y-m-d', 'm-d-Y', 'd-M-Y'];

                    foreach ($formats as $format) {
                        try {
                            $purchaseDate = Carbon::createFromFormat($format, $rowData['purchase_date']);
                            break; // Stop if a valid date is found
                        } catch (\Exception $e) {
                            continue; // Try the next format
                        }
                    }

                    if ($purchaseDate) {
                        $purchaseDate = $purchaseDate->format('Y-m-d');
                    } else {
                        throw new \Exception('Invalid date format.');
                    }
                } catch (\Exception $e) {
                    Log::error('Invalid date format in row.', [
                        'row' => $rowData,
                        'error' => $e->getMessage()
                    ]);
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid date format in row.',
                        'row' => $rowData
                    ], 400);
                }

                try {
                    // Create or retrieve related models
                    $category = category::firstOrCreate(
                        ['name' => $rowData['category'], 'dept_ID' => $userDept],
                        ['description' => 'new item description']
                    );

                    $location = locationModel::firstOrCreate(
                        ['name' => $rowData['location'], 'dept_ID' => $userDept],
                        ['description' => 'new item description']
                    );

                    $manufacturer = Manufacturer::firstOrCreate(
                        ['name' => $rowData['manufacturer'], 'dept_ID' => $userDept],
                        ['description' => 'new item description']
                    );

                    $model = ModelAsset::firstOrCreate(
                        ['name' => $rowData['model'], 'dept_ID' => $userDept],
                        ['description' => 'new item description']
                    );

                    // Generate asset code based on department sequence
                    $department = DB::table('department')->where('id', $userDept)->first();
                    $departmentCode = $department->name ?? 'UNKNOWN';
                    $lastID = department::where('name', $departmentCode)->max('assetSequence');
                    $seq = $lastID ? $lastID + 1 : 1;
                    $assetCode = $departmentCode . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT);
                    department::where('id', $userDept)->increment('assetSequence', 1);

                    Log::info('Generated asset code: ' . $assetCode);

                    // Define QR code path and ensure directory exists
                    $qrCodePath = 'qrcodes/' . $assetCode . '.png';
                    $qrStoragePath = storage_path('app/public/' . $qrCodePath);

                    if (!file_exists(storage_path('app/public/qrcodes'))) {
                        mkdir(storage_path('app/public/qrcodes'), 0777, true);
                    }

                    // Generate QR code
                    \SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')
                        ->size(250)
                        ->generate($assetCode, $qrStoragePath);
                    Log::info('QR code generated and saved.', ['path' => $qrCodePath]);

                    // Create the asset
                    assetModel::create([
                        'code' => $assetCode,
                        'name' => $rowData['name'],
                        'salvage_value' => (int) $rowData['salvage_value'],
                        'depreciation' => (int) $rowData['depreciation'],
                        'purchase_date' => $purchaseDate,
                        'purchase_cost' => (int) $rowData['purchase_cost'],
                        'usage_lifespan' => !empty($rowData['usage_lifespan']) ? (int) $rowData['usage_lifespan'] : null,
                        'ctg_ID' => $category->id,
                        'manufacturer_key' => $manufacturer->id,
                        'model_key' => $model->id,
                        'loc_key' => $location->id,
                        'dept_ID' => $userDept,
                        'status' => $rowData['status'] ?? 'active',
                        'qr_img' => $qrCodePath,
                    ]);
                    // Add asset name and code to the $assets array
                    $assets[] = ['name' => $rowData['name'], 'code' => $assetCode];
                    Log::info('Asset created successfully.', ['code' => $assetCode]);
                } catch (\Exception $e) {
                    Log::error('Error inserting asset: ' . $e->getMessage(), ['row' => $rowData]);
                    return response()->json([
                        'success' => false,
                        'message' => 'Error inserting asset.',
                        'row' => $rowData,
                        'error' => $e->getMessage()
                    ], 400);
                }
            }

            // Activity Log: Log the import action
            ActivityLog::create([
                'activity' => 'Add New Asset via Import',
                'description' => "Department Head {$deptHead->firstname} {$deptHead->lastname} imported new assets via CSV into the {$department->name} department.",
                'userType' => $deptHead->usertype,
                'user_id' => $deptHead->id,
            ]);

            $notificationData = [
                'title' => 'New Assets Added via CSV Import',
                'message' => "New assets were added in '{$department->name}' Department.",
                'asset_name' => 'Multiple Assets',
                'asset_code' => 'System Generated Code',
                'authorized_by' => $deptHead->id,
                'authorized_user_name' => "{$deptHead->firstname} {$deptHead->lastname}",
                'action_url' => route('asset'),
            ];

            $admins = User::where('usertype', 'admin')->get();
            foreach ($admins as $admin) {
                $admin->notify(new \App\Notifications\SystemNotification($notificationData));
            }

            Log::info('CSV uploaded successfully.');
            return response()->json([
                'success' => true,
                'message' => 'CSV uploaded successfully'
            ]);
        } catch (\Throwable $th) {
            Log::error('Error during CSV upload: ' . $th->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error uploading CSV. Check logs.',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function dispose($id)
    {
        try {
            $user = Auth::user();
            $asset = assetModel::findOrFail($id);

            $asset->update([
                'status' => 'disposed',
                'updated_at' => now(),
            ]);

            ActivityLog::create([
                'activity' => 'Dispose Asset',
                'description' => "{$user->firstname} {$user->lastname} disposed asset '{$asset->name}' (Code: {$asset->code}).",
                'userType' => $user->usertype,
                'user_id' => $user->id,
                'asset_id' => $id,
            ]);

            session()->flash('success', 'Asset disposed successfully.');

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            session()->flash('failed', 'Failed to dispose asset.');

            return response()->json(['success' => false]);
        }
    }
}
