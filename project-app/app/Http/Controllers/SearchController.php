<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

use App\Http\Controllers\MaintenanceSchedController;

use App\Models\Predictive;
use App\Models\Preventive;
use App\Models\User;
use App\Models\assetModel;
use App\Models\Maintenance;
use App\Models\ActivityLog;

class SearchController extends Controller
{
    public function globalSearch(Request $request)
    {
        $query = $request->input('query');
        $userType = Auth::user()->usertype;
        $deptId = Auth::user()->dept_id; // Get the user's department ID

        // Initialize result collections
        $users = collect();
        $assets = collect();
        $request = collect();

        // Admin Search: Search all tables and departments
        if ($userType === 'admin') {
            // Admin logic: Search all assets and maintenance records
            $users = User::where('firstname', 'LIKE', "%$query%")
                ->orWhere('lastname', 'LIKE', "%$query%")
                ->orWhere('email', 'LIKE', "%$query%")
                ->orWhere('employee_id', 'LIKE', "%$query%")
                ->get();

            $assets = assetModel::where('name', 'LIKE', "%$query%")
                ->orWhere('code', 'LIKE', "%$query%")
                ->get();

            $assetIds = $assets->pluck('id'); // Get IDs of matching assets

            $request = Maintenance::whereIn('asset_key', $assetIds)
                ->orWhere('description', 'LIKE', "%$query%")
                ->orWhere('type', 'LIKE', "%$query%")
                ->get();
        } elseif ($userType === 'dept_head') {
            // Dept Head logic: Filter assets by department and search within those
            $assets = assetModel::where('dept_ID', $deptId)
                ->where(function ($q) use ($query) {
                    $q->where('name', 'LIKE', "%$query%")
                        ->orWhere('code', 'LIKE', "%$query%");
                })
                ->get();

            $assetIds = $assets->pluck('id'); // Get IDs of matching assets

            $request = Maintenance::whereIn('asset_key', $assetIds)
                ->orWhere(function ($q) use ($query) {
                    $q->where('description', 'LIKE', "%$query%")
                        ->orWhere('type', 'LIKE', "%$query%");
                })
                ->get();
        }

        // Return the results to a view
        return view('search.results', compact('users', 'assets', 'request', 'query'));
    }

    public function searchMaintenance(Request $request)
    {
        $user = Auth::user();
        $userType = $user->usertype;
        $deptId = $user->dept_id;

        $query = $request->input('query', '');
        $tab = $request->input('tab', 'requests');
        $perPage = $request->input('rows_per_page', 10);
        $sortBy = $request->input('sort_by', 'maintenance.id');
        $sortOrder = $request->input('sort_order', 'asc');

        // Initialize the maintenance query with joins
        $maintenanceQuery = Maintenance::query()
            ->join('asset', 'maintenance.asset_key', '=', 'asset.id')
            ->leftJoin('users', 'maintenance.requestor', '=', 'users.id')
            ->leftJoin('category', 'asset.ctg_ID', '=', 'category.id')
            ->leftJoin('location', 'asset.loc_key', '=', 'location.id')
            ->select(
                'maintenance.*',
                DB::raw("CONCAT(users.firstname, ' ', IFNULL(users.middlename, ''), ' ', users.lastname) AS requestor_name"),
                'category.name AS category_name',
                'location.name AS location_name',
                'asset.code AS asset_code'
            );

        // Apply department filter if the user is a department head
        if ($userType === 'dept_head') {
            $maintenanceQuery->where('asset.dept_ID', $deptId);
        }

        // Apply search filters
        if ($query) {
            $maintenanceQuery->where(function ($q) use ($query) {
                $q->where('maintenance.id', 'LIKE', "%{$query}%")
                    ->orWhere('users.firstname', 'LIKE', "%{$query}%")
                    ->orWhere('users.middlename', 'LIKE', "%{$query}%")
                    ->orWhere('users.lastname', 'LIKE', "%{$query}%")
                    ->orWhere('maintenance.description', 'LIKE', "%{$query}%")
                    ->orWhere('maintenance.type', 'LIKE', "%{$query}%")
                    ->orWhere('maintenance.cost', 'LIKE', "%{$query}%")
                    ->orWhere('maintenance.reason', 'LIKE', "%{$query}%")
                    ->orWhere('maintenance.status', 'LIKE', "%{$query}%")
                    ->orWhere(DB::raw("DATE_FORMAT(maintenance.requested_at, '%Y-%m-%d')"), 'LIKE', "%{$query}%")
                    ->orWhere(DB::raw("DATE_FORMAT(maintenance.authorized_at, '%Y-%m-%d')"), 'LIKE', "%{$query}%")
                    ->orWhere(DB::raw("DATE_FORMAT(maintenance.completion_date, '%Y-%m-%d')"), 'LIKE', "%{$query}%")
                    ->orWhere('asset.code', 'LIKE', "%{$query}%");
            });
        }

        // Apply sorting
        $maintenanceQuery->orderBy($sortBy, $sortOrder);

        // Paginate the results
        $requests = $maintenanceQuery->paginate($perPage);
        // Determine which view to return based on user type
        $view = $userType === 'admin' ? 'admin.maintenance' : 'dept_head.maintenance';

        // Return the search results to a view
        return view($view, [
            'requests' => $requests,
            'query' => $query,
            'tab' => $tab,
            'perPage' => $perPage, // Fix: Pass the perPage variable correctly
            'sortBy' => $sortBy,
            'sortOrder' => $sortOrder,
        ]);
    }

    public function searchUser(Request $request)
    {
        $query = $request->input('query');
        $perPage = $request->input('perPage', 10); // Get rows per page from the request

        // Perform search query and paginate the results
        $userList = DB::table('users')
            ->where('firstname', 'like', "%{$query}%")
            ->orWhere('lastname', 'like', "%{$query}%")
            ->orWhere('email', 'like', "%{$query}%")
            ->orWhere('employee_id', 'like', "%{$query}%")
            ->paginate($perPage) // Use the dynamic per page value
            ->appends(['query' => $query, 'perPage' => $perPage]); // Keep the query and perPage in pagination links

        return view('admin.userList', ['userList' => $userList]);
    }

    public function searchAssets(Request $request)
    {
        // Get search query and rows per page
        $query = $request->input('query');
        $perPage = $request->input('perPage', 10); // Default to 10 rows per page
        $deptId = $request->input('dept'); // Get the department ID from the request, if present

        // Build the query to search assets by name or code
        $assets = DB::table('asset')
            ->when($deptId, function ($query, $deptId) {
                // Apply department filter if deptId is provided
                return $query->where('asset.dept_ID', '=', $deptId);
            })
            ->where(function ($subquery) use ($query) {
                // Search by asset name or code
                $subquery->where('asset.name', 'like', '%' . $query . '%')
                    ->orWhere('asset.code', 'like', '%' . $query . '%');
            })
            ->join('department', 'asset.dept_ID', '=', 'department.id')
            ->join('category', 'asset.ctg_ID', '=', 'category.id')
            ->select('asset.*', 'department.name as department', 'category.name as category')
            ->orderByRaw("
            CASE
            WHEN asset.status = 'active' THEN 0
            WHEN asset.status = 'under_maintenance' THEN 1
            WHEN asset.status = 'deployed' THEN 2
            WHEN asset.status = 'disposed' THEN 3
            ELSE 4
            END
            ")
            ->orderBy('department', 'asc')
            ->orderBy(DB::raw("
            IF(asset.name REGEXP '[0-9]+$',
                CAST(REGEXP_SUBSTR(asset.name, '[0-9]+$') AS UNSIGNED),
                asset.id
            )
        "), 'asc') // Order by name or another column if needed
            ->paginate($perPage);

        // Return the view with the filtered assets
        return view('admin.assetList', compact('assets'));
    }

    public function searchActivityLogs(Request $request)
    {
        $query = $request->input('query');
        $perPage = $request->input('perPage', 10);

        // Get the current interval from cache (default to 'never' if not set)
        $interval = Cache::get('activity_log_deletion_interval', 'never');

        // Perform search and paginate results
        $logs = ActivityLog::where('activity', 'like', "%{$query}%")
            ->orWhere('description', 'like', "%{$query}%")
            ->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->appends(['query' => $query, 'perPage' => $perPage]);

        return view('admin.activityLogs', [
            'logs' => $logs,
            'interval' => $interval, // Pass interval to the view
        ]);
    }

    public function searchPreventive(Request $request, MaintenanceSchedController $maintenanceController)
    {
        // Determine user role and department ID
        $userRole = Auth::user()->usertype;
        $userDeptId = Auth::user()->dept_id;

        // Fetch input values
        $searchQuery = $request->input('query', '');
        $perPage = $request->input('rows_per_page', 10);
        $sortBy = $request->input('sort_by', 'preventive.created_at');
        $sortOrder = $request->input('sort_order', 'desc');

        // Build the query
        $preventives = Preventive::whereHas('asset', function ($query) use ($userRole, $userDeptId, $searchQuery) {
            if ($userRole === 'dept_head') {
                $query->where('dept_ID', $userDeptId);
            }
            $query->where(function ($q) use ($searchQuery) {
                $q->where('code', 'like', "%{$searchQuery}%")
                    ->orWhere('name', 'like', "%{$searchQuery}%");
            });
        })
            ->join('asset', 'preventive.asset_key', '=', 'asset.id')
            ->orderBy($sortBy, $sortOrder)
            ->select('preventive.*')
            ->paginate($perPage);

        // Apply department filter if the user is a department head
        if ($userRole === 'dept_head') {
            $preventives->where('asset.dept_ID', $userDeptId);
        }

        // Calculate next maintenance
        $maintenanceController->calculateNextMaintenance($preventives);

        $view = $userRole === 'admin' ? 'admin.maintenanceSched' : 'dept_head.maintenance_sched';

        return view($view, [
            'records' => $preventives,
            'tab' => 'preventive',
            'perPage' => $perPage,
            'query' => $searchQuery,
            'sortBy' => $sortBy,
            'sortOrder' => $sortOrder,
        ]);
    }
}
