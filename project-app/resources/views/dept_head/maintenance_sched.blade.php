<!-- resources/views/dept_head/maintenance_sched.blade.php -->
@extends('layouts.app')

@section('header')
    <h2 class="my-3 font-semibold text-2xl text-black-800 leading-tight">Maintenance</h2>
@endsection

@section('content')
    <div class="px-6 py-4">

        <!-- Toast Notification -->
        @if(session('status'))
            <div id="toast" class="fixed bottom-5 right-5 bg-green-500 text-white px-4 py-2 rounded shadow-lg">
                {{ session('status') }}
            </div>
        @endif

        <!-- Top Section -->
        <div class="flex justify-between items-center mb-4">
            <!-- Search Bar -->
            <div class="flex items-center w-1/2">
                <form action="" method="GET" class="w-full">
                    <input type="hidden" name="tab" value="{{ $tab }}"> <!-- Include the current tab -->
                    <input type="text" name="query" placeholder="Search..." value="{{ request('query') }}" class="w-1/2 px-4 py-2 border rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
                </form>
            </div>

            <!-- Right Section: Download Icon and Create Button -->
            <div class="flex items-center space-x-4">
                <a href="" class="p-2 text-black">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-7">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 8.25H7.5a2.25 2.25 0 0 0-2.25 2.25v9a2.25 2.25 0 0 0 2.25 2.25h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25H15M9 12l3 3m0 0 3-3m-3 3V2.25" />
                    </svg>
                </a>
                <a href="{{ route('formMaintenance') }}" class="px-3 py-1 bg-blue-500 text-white rounded-md shadow hover:bg-blue-600 focus:outline-none flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-7 mr-2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                    Create Maintenance
                </a>
            </div>
        </div>

        <div class="flex justify-between items-center mb-4">
            <!-- Rows per page dropdown (on the left) -->
            <div class="flex items-center">
                <label for="rows_per_page" class="mr-2 text-gray-700">Rows per page:</label>
                <form action="" method="GET" id="rowsPerPageForm">
                    <select name="rows_per_page" id="rows_per_page" class="border rounded-md bg-white text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500" onchange="document.getElementById('rowsPerPageForm').submit()">
                        <option value="5" {{ $perPage == 5 ? 'selected' : '' }}>5</option>
                        <option value="10" {{ $perPage == 10 ? 'selected' : '' }}>10</option>
                        <option value="20" {{ $perPage == 20 ? 'selected' : '' }}>20</option>
                        <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50</option>
                    </select>
                </form>
            </div>

            <!-- Pagination (on the right) -->
            <div class="ml-auto">
                {{ $records->appends(['rows_per_page' => $perPage])->links() }} <!-- Pagination Links -->
            </div>
        </div>


        <!-- Tabs Section -->
        <div class="mb-4 flex justify-end">
            <ul class="flex border-b">
                <li class="mr-4">
                    <a href="{{ route('maintenance_sched') }}" class="inline-block px-4 py-2 {{ $tab === 'preventive' ? 'text-blue-600 font-semibold border-b-2 border-blue-600' : 'text-gray-600 hover:text-blue-600' }}">Preventive Maintenance</a>
                </li>
                <li class="mr-4">
                    <a href="{{ route('maintenance_sched.predictive') }}" class="inline-block px-4 py-2 {{ $tab === 'predictive' ? 'text-blue-600 font-semibold border-b-2 border-blue-600' : 'text-gray-600 hover:text-blue-600' }}">Predictive Maintenance</a>
                </li>
            </ul>
        </div>

        <!-- Table Section -->
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white border rounded-md">
                <thead class="bg-gray-100 border-b">
                    @if ($tab === 'preventive')
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <a href="{{ route('maintenance_sched', ['sort_by' => 'asset.code', 'sort_order' => $sortOrder === 'asc' ? 'desc' : 'asc']) }}">
                                Asset Code
                            </a>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <a href="{{ route('maintenance_sched', ['sort_by' => 'asset.name', 'sort_order' => $sortOrder === 'asc' ? 'desc' : 'asc']) }}">
                                Asset Name
                            </a>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <a href="{{ route('maintenance_sched', ['sort_by' => 'cost', 'sort_order' => $sortOrder === 'asc' ? 'desc' : 'asc']) }}">
                                Cost
                            </a>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <a href="{{ route('maintenance_sched', ['sort_by' => 'frequency', 'sort_order' => $sortOrder === 'asc' ? 'desc' : 'asc']) }}">
                                Frequency
                            </a>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <a href="{{ route('maintenance_sched', ['sort_by' => 'ends', 'sort_order' => $sortOrder === 'asc' ? 'desc' : 'asc']) }}">
                                Ends
                            </a>
                        </th>
                    </tr>
                    @elseif ($tab === 'predictive')
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <a href="{{ route('maintenance_sched.predictive', ['sort_by' => 'asset.code', 'sort_order' => $sortOrder === 'asc' ? 'desc' : 'asc']) }}">
                                Asset Code
                            </a>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <a href="{{ route('maintenance_sched.predictive', ['sort_by' => 'asset.name', 'sort_order' => $sortOrder === 'asc' ? 'desc' : 'asc']) }}">
                                Asset Name
                            </a>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <a href="{{ route('maintenance_sched.predictive', ['sort_by' => 'category.name', 'sort_order' => $sortOrder === 'asc' ? 'desc' : 'asc']) }}">
                                Category
                            </a>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <a href="{{ route('maintenance_sched.predictive', ['sort_by' => 'average_cost', 'sort_order' => $sortOrder === 'asc' ? 'desc' : 'asc']) }}">
                                Average Cost
                            </a>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <a href="{{ route('maintenance_sched.predictive', ['sort_by' => 'repair_count', 'sort_order' => $sortOrder === 'asc' ? 'desc' : 'asc']) }}">
                                Repair Count
                            </a>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <a href="{{ route('maintenance_sched.predictive', ['sort_by' => 'recommendation', 'sort_order' => $sortOrder === 'asc' ? 'desc' : 'asc']) }}">
                                Recommendation
                            </a>
                        </th>
                    </tr>
                    @endif
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach ($records as $record)
                        <tr>
                            <td class="px-6 py-4">{{ $record->asset->code }}</td>
                            <td class="px-6 py-4">{{ $record->asset->name }}</td>
                            @if ($tab === 'preventive')
                                <td class="px-6 py-4">₱ {{ $record->cost }}</td>
                                <td class="px-6 py-4">Every {{ $record->frequency }} days</td>
                                <td class="px-6 py-4">After {{ $record->ends }} occurrence(s)</td>
                            @elseif ($tab === 'predictive')
                                <td class="px-6 py-4">{{ $record->asset->category->name }}</td>
                                <td class="px-6 py-4">₱ {{ $record->average_cost }}</td>
                                <td class="px-6 py-4">{{ $record->repair_count }}</td>
                                <td class="px-6 py-4">{{ $record->recommendation }}</td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Toast Notification fade-out
        setTimeout(function() {
            var toast = document.getElementById('toast');
            if (toast) {
                toast.style.transition = 'opacity 1s ease';
                toast.style.opacity = '0';
                setTimeout(function() {
                    toast.remove();
                }, 1000); // Remove it after fading out
            }
        }, 3000); // 3 seconds delay
    </script>
    
@endsection