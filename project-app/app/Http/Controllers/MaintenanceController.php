<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Maintenance;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\assetModel;
use App\Models\category;
use App\Models\locationModel;
use App\Models\ModelAsset;
use App\Models\Manufacturer;
use App\Models\Preventive;



class MaintenanceController extends Controller
{
    // Show the list of maintenance requests based on user type and tab
    public function index(Request $request)
    {
        $user = Auth::user();
        $tab = $request->query('tab', 'requests'); // Default tab is 'requests'
        $searchQuery = $request->input('query', '');

        $query = Maintenance::leftjoin('asset', 'maintenance.asset_key', '=', 'asset.id');
        // $query = Maintenance::join('asset', 'maintenance.asset_key', '=', 'asset.id')
        // ->join('users', 'maintenance.requestor', '=', 'users.id') // Join with users table
        // ->select('maintenance.*', 'users.firstname as requestor_name'); // Select requestor name

        // Apply status filter based on the selected tab
        if ($tab === 'requests') {
            $query->where('maintenance.status', 'request');
        } elseif ($tab === 'approved') {
            $query->where('maintenance.status', 'approved');
        } elseif ($tab === 'denied') {
            $query->where('maintenance.status', 'denied');
        }

        // Apply department filter for department heads
        if ($user->usertype === 'dept_head') {
            $deptId = $user->dept_id;
            $query->where('asset.dept_ID', $deptId);
        } elseif ($user->usertype === 'user') {
            $query->where('maintenance.requestor', $user->id);
        } else {
            return redirect()->route('user.home');
        }

        // Apply search filter
        if ($searchQuery) {
            $query->where(function ($q) use ($searchQuery) {
                $q->where('maintenance.id', 'LIKE', "%{$searchQuery}%")
                ->orWhere('users.firstname', 'LIKE', "%{$searchQuery}%")
                ->orWhere('users.middlename', 'LIKE', "%{$searchQuery}%")
                ->orWhere('users.lastname', 'LIKE', "%{$searchQuery}%")
                ->orWhere('maintenance.description', 'LIKE', "%{$searchQuery}%")
                ->orWhere('asset.code', 'LIKE', "%{$searchQuery}%")
                ->orWhere('category.name', 'LIKE', "%{$searchQuery}%")
                ->orWhere('location.name', 'LIKE', "%{$searchQuery}%")
                ->orWhere('maintenance.type', 'LIKE', "%{$searchQuery}%")
                ->orWhere('maintenance.reason', 'LIKE', "%{$searchQuery}%")
                ->orWhere(DB::raw("DATE_FORMAT(maintenance.requested_at, '%Y-%m-%d')"), 'LIKE', "%{$searchQuery}%")
                ->orWhere(DB::raw("DATE_FORMAT(maintenance.authorized_at, '%Y-%m-%d')"), 'LIKE', "%{$searchQuery}%");
            });
        }

        // Fetch the filtered and paginated results
        // $requests = $query->paginate(7);
        // $requests = $query->select('maintenance.*')->paginate(7);
        $requests = $query->leftjoin('users', 'maintenance.requestor', '=', 'users.id')
        ->leftjoin('category', 'asset.ctg_ID', '=', 'category.id')
        ->leftjoin('location', 'asset.loc_key', '=', 'location.id')
        ->select('maintenance.*', DB::raw("CONCAT(users.firstname, ' ', IFNULL(users.middlename, ''), ' ', users.lastname) AS requestor_name"), 'category.name AS category_name', 'location.name AS location_name', 'asset.code as asset_code')
        ->paginate(7);

        // Return the view with the filtered requests and selected tab
        if ($user->usertype === 'dept_head') {
            return view('dept_head.maintenance', [
                'requests' => $requests,
                'tab' => $tab,
                'searchQuery' => $searchQuery, // Passing the search query
            ]);
        } else {
            return view('user.requestList', [
                'requests' => $requests,
                'searchQuery' => $searchQuery, // Passing the search query
            ]);
        }
    }

    // Search functionality
    public function search(Request $request)
    {
        return $this->index($request);
    }

    // Show the list of maintenance requests for the department head
    // public function requests()
    // {
    //     $user = Auth::user();

    //     if ($user->usertype === 'dept_head') {
    //         $deptId = $user->dept_id;

    //         $requests = Maintenance::join('asset', 'maintenance.asset_key', '=', 'asset.id')
    //             ->where('asset.dept_ID', $deptId)
    //             ->where('maintenance.status', 'request')
    //             ->select('maintenance.*')
    //             ->get();

    //             return view('dept_head.maintenance', [
    //                 'requests' => $requests,
    //                 'tab' => 'requests' // Default tab
    //             ]);
    //     }

    //     return redirect()->route('user.home');
    // }


    public function requests()
    {
        $user = Auth::user();
        $query = Maintenance::leftjoin('asset', 'maintenance.asset_key', '=', 'asset.id')
            ->where('maintenance.status', 'request')
            ->select('maintenance.*');

        if ($user->usertype === 'dept_head') {
            $deptId = $user->dept_id;
            $query->where('asset.dept_ID', $deptId);
        } else {
            return redirect()->route('user.home');
        }

        // $requests = $query->get();
        $requests = $query->join('users', 'maintenance.requestor', '=', 'users.id')
        ->leftjoin('category', 'asset.ctg_ID', '=', 'category.id')
        ->leftjoin('location', 'asset.loc_key', '=', 'location.id')
        ->select('maintenance.*', DB::raw("CONCAT(users.firstname, ' ', IFNULL(users.middlename, ''), ' ', users.lastname) AS requestor_name"), 'category.name AS category_name', 'location.name AS location_name', 'asset.code as asset_code')
        ->paginate(7);

        return view('dept_head.maintenance', [
            'requests' => $requests,
            'tab' => 'requests',
        ]);
    }

    // Show the list of approved maintenance requests
    public function approved()
    {
        $user = Auth::user();
        $searchQuery = ''; // Initialize to empty string

        $query = Maintenance::join('asset', 'maintenance.asset_key', '=', 'asset.id')
            ->where('maintenance.status', 'approved')
            ->select('maintenance.*');

        if ($user->usertype === 'dept_head') {
            $deptId = $user->dept_id;
            $query->where('asset.dept_ID', $deptId);
        } else {
            return redirect()->route('user.home');
        }

        // $requests = $query->get();
        $requests = $query->join('users as requestor_user', 'maintenance.requestor', '=', 'requestor_user.id')
        ->join('users as authorized_user', 'maintenance.authorized_by', '=', 'authorized_user.id')
        ->join('category', 'asset.ctg_ID', '=', 'category.id')
        ->select('maintenance.*',
                DB::raw("CONCAT(requestor_user.firstname, ' ', IFNULL(requestor_user.middlename, ''), ' ', requestor_user.lastname) AS requestor_name"),
                DB::raw("CONCAT(authorized_user.firstname, ' ', IFNULL(authorized_user.middlename, ''), ' ', authorized_user.lastname) AS authorized_by_name"),
                'category.name AS category_name', 'asset.code as asset_code')
        ->paginate(7);

        return view('dept_head.maintenance', [
            'requests' => $requests,
            'tab' => 'approved',
            'searchQuery' => $searchQuery, // Passing an empty search query
        ]);
    }

    // Show the list of denied maintenance requests
    public function denied()
    {
        $user = Auth::user();
        $searchQuery = ''; // Initialize to empty string

        $query = Maintenance::join('asset', 'maintenance.asset_key', '=', 'asset.id')
            ->where('maintenance.status', 'denied')
            ->select('maintenance.*');

        if ($user->usertype === 'dept_head') {
            $deptId = $user->dept_id;
            $query->where('asset.dept_ID', $deptId);
        } else {
            return redirect()->route('user.home');
        }

        // $requests = $query->get();
        $requests = $query->join('users as requestor_user', 'maintenance.requestor', '=', 'requestor_user.id')
        ->join('users as authorized_user', 'maintenance.authorized_by', '=', 'authorized_user.id')
        ->join('category', 'asset.ctg_ID', '=', 'category.id')
        ->select('maintenance.*',
                DB::raw("CONCAT(requestor_user.firstname, ' ', IFNULL(requestor_user.middlename, ''), ' ', requestor_user.lastname) AS requestor_name"),
                DB::raw("CONCAT(authorized_user.firstname, ' ', IFNULL(authorized_user.middlename, ''), ' ', authorized_user.lastname) AS denied_by_name"),
                'category.name AS category_name', 'asset.code as asset_code')
        ->paginate(7);

        return view('dept_head.maintenance', [
            'requests' => $requests,
            'tab' => 'denied',
            'searchQuery' => $searchQuery, // Passing an empty search query
        ]);
    }

        // Approve a maintenance request
        public function approve($id)
        {
            $user = Auth::user();

            // Ensure the user is a department head
            if ($user->usertype !== 'dept_head') {
                return redirect()->route('user.home');
            }

            // Find the maintenance request
            $maintenance = Maintenance::findOrFail($id);

            // Update the status to 'approved'
            $maintenance->status = 'approved';
            $maintenance->authorized_by = $user->id;
            $maintenance->authorized_at = now();
            $maintenance->save();

            return redirect()->route('maintenance')->with('status', 'Request approved successfully.');
        }

        // Deny a maintenance request
        public function deny(Request $request, $id)
        {
            $user = Auth::user();

            // Ensure the user is a department head
            if ($user->usertype !== 'dept_head') {
                return redirect()->route('user.home');
            }

            // Find the maintenance request
            $maintenance = Maintenance::findOrFail($id);

            // Validate the reason
            $request->validate([
                'reason' => 'required|string|max:255',
            ]);

            // Update the status to 'denied'
            $maintenance->status = 'denied';
            $maintenance->authorized_by = $user->id;
            $maintenance->authorized_at = now();
            $maintenance->reason = $request->input('reason');
            $maintenance->save();

            return redirect()->route('maintenance')->with('status', 'Request denied.');
        }

        //download button
        public function download(Request $request)
        {
            $user = Auth::user();
            $tab = $request->query('tab', 'requests'); // Default tab is 'requests'
            $searchQuery = $request->input('query', ''); // Default to empty string if no search query

            $query = Maintenance::join('asset', 'maintenance.asset_key', '=', 'asset.id');

            // Apply status filter based on the selected tab
            if ($tab === 'requests') {
                $query->where('maintenance.status', 'request');
            } elseif ($tab === 'approved') {
                $query->where('maintenance.status', 'approved');
            } elseif ($tab === 'denied') {
                $query->where('maintenance.status', 'denied');
            }

            // Apply department filter for department heads
            if ($user->usertype === 'dept_head') {
                $deptId = $user->dept_id;
                $query->where('asset.dept_ID', $deptId);
            } elseif ($user->usertype === 'user') {
                $query->where('maintenance.requestor', $user->id);
            } else {
                return redirect()->route('user.home');
            }

            // Apply search filter
            if ($searchQuery) {
                $query->where(function ($q) use ($searchQuery) {
                    $q->where('maintenance.id', 'LIKE', "%{$searchQuery}%")
                      ->orWhere('users.firstname', 'LIKE', "%{$searchQuery}%")
                      ->orWhere('users.middlename', 'LIKE', "%{$searchQuery}%")
                      ->orWhere('users.lastname', 'LIKE', "%{$searchQuery}%")
                      ->orWhere('maintenance.description', 'LIKE', "%{$searchQuery}%")
                      ->orWhere('maintenance.repair', 'LIKE', "%{$searchQuery}%")
                      ->orWhere('maintenance.reason', 'LIKE', "%{$searchQuery}%")
                      ->orWhere('asset.code', 'LIKE', "%{$searchQuery}%")
                      ->orWhere('category.name', 'LIKE', "%{$searchQuery}%")
                      ->orWhere('location.name', 'LIKE', "%{$searchQuery}%")
                      ->orWhere(DB::raw("DATE_FORMAT(maintenance.requested_at, '%Y-%m-%d')"), 'LIKE', "%{$searchQuery}%")
                      ->orWhere(DB::raw("DATE_FORMAT(maintenance.authorized_at, '%Y-%m-%d')"), 'LIKE', "%{$searchQuery}%")
                      ->orWhere(DB::raw("DATE_FORMAT(maintenance.created_at, '%Y-%m-%d')"), 'LIKE', "%{$searchQuery}%")
                      ->orWhere(DB::raw("DATE_FORMAT(maintenance.updated_at, '%Y-%m-%d')"), 'LIKE', "%{$searchQuery}%");
                });
            }

            // Fetch the data
            $maintenances = $query->join('users', 'maintenance.requestor', '=', 'users.id')
                ->leftJoin('users as authorized_user', 'maintenance.authorized_by', '=', 'authorized_user.id')
                ->join('category', 'asset.ctg_ID', '=', 'category.id')
                ->join('location', 'asset.loc_key', '=', 'location.id')
                ->select('maintenance.id', DB::raw("CONCAT(users.firstname, ' ', IFNULL(users.middlename, ''), ' ', users.lastname) AS requestor_name"),
                         'maintenance.asset_key', 'maintenance.description', 'category.name AS category_name',
                         'location.name AS location_name', 'maintenance.status', 'maintenance.type', 'asset.code as asset_code',
                         'maintenance.reason', DB::raw("DATE_FORMAT(maintenance.requested_at, '%Y-%m-%d') as requested_at"),
                         DB::raw("DATE_FORMAT(maintenance.authorized_at, '%Y-%m-%d') as authorized_at"),
                         DB::raw("DATE_FORMAT(maintenance.created_at, '%Y-%m-%d %H:%i:%s') as created_at"),
                         DB::raw("DATE_FORMAT(maintenance.updated_at, '%Y-%m-%d %H:%i:%s') as updated_at"),
                         DB::raw("CONCAT(authorized_user.firstname, ' ', IFNULL(authorized_user.middlename, ''), ' ', authorized_user.lastname) AS authorized_by_name"))
                ->get();

            // Generate CSV content
            $csvContent = "Request ID,Requestor,Asset Code,Description,Category,Location,Status,Type,Reason,Requested At,Authorized At,Created At,Updated At,Authorized By\n";
            foreach ($maintenances as $maintenance) {
                $csvContent .= "\"{$maintenance->id}\",\"{$maintenance->requestor_name}\",\"{$maintenance->asset_code}\",\"{$maintenance->description}\",\"{$maintenance->category_name}\",\"{$maintenance->location_name}\",\"{$maintenance->status}\",\"{$maintenance->type}\",\"{$maintenance->reason}\",\"{$maintenance->requested_at}\",\"{$maintenance->authorized_at}\",\"{$maintenance->created_at}\",\"{$maintenance->updated_at}\",\"{$maintenance->authorized_by_name}\"\n";
            }

            // Return response as a CSV file download
            return response($csvContent)
                ->header('Content-Type', 'text/csv')
                ->header('Content-Disposition', 'attachment; filename="maintenance_' . $tab . '_' . now()->format('Y-m-d_H:i:s') . '.csv"');
        }

        //user side
        public function createRequest(Request $request)
        {
            // Validate the input from the form
            $request->validate([
                'asset_id' => 'required|exists:asset,id', // Ensure the asset exists
                'issue_description' => 'required|string|max:1000',
                'type' => 'required|in:repair,maintenance,upgrade,inspection', // Validate the request type
            ]);

            // Create a new maintenance request with 'pending' status
            Maintenance::create([
                'description' => $request->input('issue_description'), // Issue description
                'status' => 'pending', // Set status to 'pending'
                'asset_key' => $request->input('asset_id'), // Asset reference
                'requestor' => Auth::id(), // Logged-in user as requestor
                'type' => $request->input('type'), // Request type
            ]);

            // Redirect back with a success message
            return redirect()->back()->with('status', 'Maintenance request submitted successfully.');
        }



        // public function create() {
        //     $assets = assetModel::all(['id', 'code', 'name']); // Retrieve asset id, code, and name
        //     return view('dept_head.createmaintenance', compact('assets'));
        // }

        // public function create() {
        //     $assets = assetModel::all(['id', 'code', 'name']); // Retrieve asset id, code, and name
        //     $categories = category::all(['id', 'name']);       // Retrieve category id and name
        //     $locations = locationModel::all(['id', 'name']);        // Retrieve location id and name
        //     $models = ModelAsset::all(['id', 'name']);              // Retrieve model id and name
        //     $manufacturers = Manufacturer::all(['id', 'name']); // Retrieve manufacturer id and name

        //     return view('dept_head.createmaintenance', compact('assets', 'categories', 'locations', 'models', 'manufacturers'));
        // }

        public function create() {
            // Get the currently authenticated user
            $user = Auth::user();

            // Only retrieve assets that belong to the same department as the user
            $assets = assetModel::where('dept_ID', $user->dept_id)->get(['id', 'code', 'name']);

            // Retrieve categories, locations, models, manufacturers related to the user's department if applicable
            $categories = category::where('dept_ID', $user->dept_id)->get(['id', 'name']);
            $locations = locationModel::all(['id', 'name']); // No department link, fetching all
            $models = ModelAsset::all(['id', 'name']); // No department link, fetching all
            $manufacturers = Manufacturer::all(['id', 'name']); // No department link, fetching all

            return view('dept_head.createmaintenance', compact('assets', 'categories', 'locations', 'models', 'manufacturers'));
        }

        // public function getAssetDetails($id) {
        //     // Retrieve the asset details based on its id
        //     $asset = assetModel::where('id', $id)->with(['category', 'manufacturer', 'model', 'location'])->first();

        //     // Prepare the image URL or set to "No Image" placeholder
        //     $asset->image_url = $asset->image ? asset('storage/' . $asset->image) : asset('images/no-image.png');

        //     // Return the asset details as JSON
        //     return response()->json($asset);
        // }
        public function getAssetDetails($id) {
            // Retrieve the asset details based on its id, including relationships
            $asset = assetModel::where('id', $id)->with(['category', 'manufacturer', 'model', 'location'])->first();

            if (!$asset) {
                return response()->json(['error' => 'Asset not found'], 404); // Error handling
            }

            // Prepare the image URL or set to "No Image" placeholder
            $asset->image_url = $asset->image ? asset('storage/' . $asset->image) : asset('images/no-image.png');

            // Return the asset details as a custom JSON response
            return response()->json([
                'id' => $asset->id,
                'code' => $asset->code, // Asset code
                'name' => $asset->name, // Asset name
                'model' => $asset->model ? ['id' => $asset->model->id, 'name' => $asset->model->name] : null,
                'category' => $asset->category ? ['id' => $asset->category->id, 'name' => $asset->category->name] : null,
                'location' => $asset->location ? ['id' => $asset->location->id, 'name' => $asset->location->name] : null,
                'manufacturer' => $asset->manufacturer ? ['id' => $asset->manufacturer->id, 'name' => $asset->manufacturer->name] : null,
                'image_url' => $asset->image_url // Image URL
            ]);
        }

        public function store(Request $request) {
            // Validate the form input
            $validatedData = $request->validate([
                'asset_code' => 'required|exists:asset,id',
                'cost' => 'required|numeric',
                'frequency' => 'required|string',
                'repeat' => 'nullable|integer',
                'interval' => 'nullable|integer',
                'ends' => ['required', 'regex:/^(never|\d+)$/'],  // Allow "never" or a numeric value
                'occurrence' => 'nullable|integer', // For custom occurrences
            ]);

            // Determine the frequency in days
            $frequencyDays = 0;
            switch ($validatedData['frequency']) {
                case 'every_day':
                    $frequencyDays = 1;
                    break;
                case 'every_week':
                    $frequencyDays = 7;
                    break;
                case 'every_month':
                    $frequencyDays = 30;
                    break;
                case 'every_year':
                    $frequencyDays = 365;
                    break;
                case 'custom':
                    if (isset($validatedData['repeat']) && isset($validatedData['interval'])) {
                        $frequencyDays = $validatedData['repeat'] * $validatedData['interval'];
                    } else {
                        $frequencyDays = 1; // Set a default value if repeat or interval is null
                    }
                    break;
            }

            // Handle 'ends' logic correctly
            if ($validatedData['ends'] === 'never') {
                $ends = 0; // Never ends
            } else {
                $ends = (int)$validatedData['ends']; // Convert to integer for occurrences
            }


            // Insert the data into the preventive table
            Preventive::create([
                'asset_key' => $validatedData['asset_code'],  // Assuming asset_key is the asset ID
                'cost' => $validatedData['cost'],
                'frequency' => $frequencyDays,  // Frequency stored in days
                'ends' => $ends,  // 0 for "never", a number for occurrences
            ]);

            // Set session value for success notification
            session()->flash('status', 'Maintenance schedule created successfully!');

            return redirect()->route('maintenance_sched')->with('success', 'Maintenance schedule created successfully!');
        }

        // MaintenanceController.php
        public function editApproved($id)
        {
            // Load related asset, category, location, model, and manufacturer data
            $maintenance = Maintenance::with(['asset', 'category', 'location', 'model', 'manufacturer'])
                ->findOrFail($id);

            return view('dept_head.modal.editApprove', compact('maintenance'));
        }

        // In your MaintenanceController updateApproved function
        public function updateApproved(Request $request, $id)
        {

            $request->validate([
                'cost' => 'required|numeric|min:0',
                'type' => 'required|string',
                'start_date' => 'required|date',
                'completion_date' => 'nullable|date',
            ]);

            // Find the maintenance request by ID
            $maintenance = Maintenance::findOrFail($id);

            // Update the maintenance details
            $maintenance->update([
                'type' => $request->type,
                'start_date' => $request->start_date,
                'cost' => $request->cost,
                'completed' => $request->has('set_as_completed'),
                // 'completion_date' => $request->completion_date,
                'completion_date' => $request->has('set_as_completed') ? now() : null,

            ]);

            // Redirect back with success message
            return redirect()->route('maintenance.approved')
                ->with('status', 'Maintenance request updated successfully.');
        }

        public function editDenied($id)
        {
            // Load related asset, category, location, model, and manufacturer data
            $maintenance = Maintenance::with(['asset', 'category', 'location', 'model', 'manufacturer'])
                ->findOrFail($id);

            return view('dept_head.modal.editDenied', compact('maintenance'));
        }

        public function updateDenied(Request $request, $id)
        {
            // Validate that the status is 'approved' or 'denied' (as per your dropdown in editDenied.blade.php)
            $request->validate([
                'status' => 'required|string|in:approved,denied',
            ]);

            // Find the maintenance request by ID
            $maintenance = Maintenance::findOrFail($id);

            // Update only the status
            $maintenance->update([
                'status' => $request->status,
            ]);

            // Redirect back with success message
            return redirect()->route('maintenance.denied')
                ->with('status', 'Maintenance request status updated successfully.');
        }


        public function showRequestList(Request $request) {
            $userId = Auth::id();
            $search = $request->input('search');
            $sort_by = $request->input('sort_by', 'created_at'); // Default sorting by 'created_at'
            $sort_direction = $request->input('sort_direction', 'desc'); // Default sorting direction is 'desc'
            $status = $request->input('status');  // Filter by status
            $type = $request->input('type');  // Filter by type
            $from_date = $request->input('from_date');  // Date range (start)
            $to_date = $request->input('to_date');  // Date range (end)

            // Fetch requests made by the current user and join with related tables
            $requests = DB::table('maintenance')
                ->join('asset', 'maintenance.asset_key', '=', 'asset.id')
                ->join('category', 'asset.ctg_ID', '=', 'category.id')
                ->join('model', 'asset.model_key', '=', 'model.id')
                ->join('manufacturer', 'asset.manufacturer_key', '=', 'manufacturer.id')
                ->join('location', 'asset.loc_key', '=', 'location.id')
                ->join('department', 'asset.dept_ID', '=', 'department.id')
                ->where('maintenance.requestor', $userId)
                ->when($search, function ($query, $search) {
                    return $query->where(function ($query) use ($search) {
                        $query->where('maintenance.description', 'like', '%' . $search . '%')
                              ->orWhere('maintenance.status', 'like', '%' . $search . '%')
                              ->orWhere('asset.code', 'like', '%' . $search . '%')
                              ->orWhere('maintenance.id', 'like', '%' . $search . '%');
                    });
                })
                // Apply status filter if provided
                ->when($status, function ($query, $status) {
                    return $query->where('maintenance.status', $status);
                })
                // Apply type filter if provided
                ->when($type, function ($query, $type) {
                    return $query->where('maintenance.type', $type);
                })
                // Apply date range filter if both dates are provided
                ->when($from_date && $to_date, function ($query) use ($from_date, $to_date) {
                    return $query->whereBetween('maintenance.created_at', [$from_date, $to_date]);
                })
                ->select(
                    'maintenance.*',
                    'asset.code as asset_code',
                    'asset.status as asset_status',
                    'asset.image as asset_image',
                    'asset.name as asset_name',
                    'asset.depreciation',
                    'asset.salvageVal',
                    'asset.usage_Lifespan',
                    'category.name as category',
                    'model.name as model',
                    'manufacturer.name as manufacturer',
                    'location.name as location',
                    'department.name as department'
                )
                ->orderBy($sort_by, $sort_direction) // Apply sorting
                ->paginate(8);

            return view('user.requestList', [
                'requests' => $requests,
                'search' => $search,
                'status' => $status,
                'type' => $type,
                'from_date' => $from_date,
                'to_date' => $to_date
            ]);
        }




        public function cancelRequest($id)
        {
            // Find the maintenance request by its ID, or fail if not found
            $request = Maintenance::findOrFail($id);

            // Check if the request is pending, only pending requests can be canceled
            if ($request->status !== 'pending') {
                return redirect()->back()->withErrors('Only pending requests can be canceled.');
            }

            // Optional: Check if the authenticated user is allowed to cancel the request
            // This checks if the current user is the one who submitted the request, or if the user has admin privileges
            if (auth()->user()->id !== $request->requestor && !auth()->user()->is_admin) {
                return redirect()->back()->withErrors('You do not have permission to cancel this request.');
            }
            // Update the status to 'cancelled'
            $request->status = 'cancelled';
            $request->save();

            // Redirect back to the request list page or another relevant page with a success message
            return redirect()->route('requests.list')->with('status', 'Request canceled successfully.');
        }

}





