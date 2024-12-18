@extends('layouts.app')

@section('header')
<div class="header flex w-full justify-between pr-3 pl-3 items-center">
    <div class="title">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Activity Logs</h2>
    </div>
    <div class="header-R flex items-center space-x-4">
        <div class="relative">
            <!-- Export Button with Dropdown -->
            <button
                onclick="toggleExportDropdown()">
                <x-icons.exportIcon />
            </button>

            <!-- Dropdown Options -->
            <div id="exportDropdown" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-10">
                <a href="{{ route('activityLogs.export', ['format' => 'csv']) }}"
                    class="block px-4 py-2 text-sm text-gray-800 hover:bg-gray-100">
                    Export as CSV
                </a>
                <a href="{{ route('activityLogs.export', ['format' => 'pdf']) }}"
                    class="block px-4 py-2 text-sm text-gray-800 hover:bg-gray-100">
                    Export as PDF
                </a>
            </div>
            <!-- Settings Button -->
            <button onclick="toggleSettingsModal()">
                <x-icons.gear-icon />
            </button>
        </div>
    </div>
</div>
@endsection

@section('content')
{{-- <div class="w-full px-8 mt-4"> --}}
<div class="w-full mt-4">
    <div>
        <form method="GET" action="{{ route('searchActivity') }}" class="flex flex-col space-y-4">
            <!-- Search Input and Button -->
            <div class="relative search-container">
                <x-search-input
                    placeholder="Search by activity or description"
                    class="w-72" />
            </div>

            <div class="flex justify-between items-center mb-4">
                <!-- Rows per page dropdown (Left) -->
                <div class="flex items-center space-x-2">
                    <label for="perPage">Rows per page: </label>
                    <select name="perPage" id="perPage" class="border border-gray-300 rounded px-2 py-1 w-16" onchange="this.form.submit()">
                        <option value="10" {{ request('perPage') == 10 ? 'selected' : '' }}>10</option>
                        <option value="25" {{ request('perPage') == 25 ? 'selected' : '' }}>25</option>
                        <option value="50" {{ request('perPage') == 50 ? 'selected' : '' }}>50</option>
                        <option value="100" {{ request('perPage') == 100 ? 'selected' : '' }}>100</option>
                    </select>
                </div>
                <!-- Pagination Links and Showing Results (Right) -->
                @if($logs->hasPages())
                <div class="flex items-center space-x-4">
                    <span class="text-gray-600 hidden md:block">Showing {{ $logs->firstItem() }} to {{ $logs->lastItem() }} of {{ $logs->total() }} items</span>
                    <div class="hidden md:block">
                        {{ $logs->links('vendor.pagination.tailwind') }}
                    </div>
                    <div class="md:hidden md:hidden text-xs flex justify-center space-x-1 mt-2">
                        {{ $logs->links() }}
                    </div>
                </div>
                @endif
            </div>
        </form>
    </div>

    <div class="mb-4 text-blue-700">
        <strong>Next log deletion in:</strong>
        <span id="countdownTimer">Calculating...</span>
    </div>

    <div class="overflow-x-auto">
        <div class="hidden md:block">
            <table class="table-auto w-full border-collapse border border-gray-300 rounded-lg shadow-md">
                <thead class="bg-blue-500 text-white">
                    <tr>
                        <th class="p-2 border">Activity</th>
                        <th class="p-2 border">Description</th>
                        <th class="p-2 border">User Role</th>
                        <th class="p-2 border">User Name</th>
                        <th class="p-2 border">Asset Name</th>
                        <th class="p-2 border">Request ID</th>
                        <th class="p-2 border">Date & Time</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                    <tr class="hover:bg-gray-100 transition">
                        <td class="p-2 border">{{ $log->activity }}</td>
                        <td class="p-2 border">{{ $log->description }}</td>
                        <td class="p-2 border">
                            @switch($log->userType)
                            @case('admin')
                            Admin
                            @break
                            @case('dept_head')
                            Department Head
                            @break
                            @default
                            System
                            @endswitch
                        </td>
                        <!-- <td class="p-2 border">{{ $log->user_id ?? 'System' }}</td>
                        <td class="p-2 border">{{ $log->asset_id ?? 'N/A' }}</td> -->
                        <td class="p-2 border">
                            {{ $log->user ? $log->user->firstname . ' ' . $log->user->lastname : 'System' }}
                        </td>
                        <td class="p-2 border">
                            {{ $log->asset ? $log->asset->name : 'N/A' }}
                        </td>
                        <td class="p-2 border">{{ $log->request_id ?? 'N/A' }}</td>
                        <td class="p-2 border">{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center p-4">No activity logs found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Card layout for small screens -->
        <div class="block md:hidden space-y-2">
            {{-- Changed: Added 'block md:hidden' to show cards only on small screens --}}
            @forelse ($logs as $log)
            <div class="bg-white shadow-md rounded-lg p-4">
                <p class="text-xs"><strong>Activity:</strong> {{ $log->activity }}</p>
                <p class="text-xs"><strong>Description:</strong> {{ $log->description }}</p>
                <p class="text-xs">
                    <strong>User Role:</strong>
                    @switch($log->userType)
                    @case('admin') Admin @break
                    @case('dept_head') Department Head @break
                    @default System
                    @endswitch
                </p>
                <td class="text-xs">
                    {{ $log->user ? $log->user->firstname . ' ' . $log->user->lastname : 'System' }}
                </td>
                <td class="text-xs">
                    {{ $log->asset ? $log->asset->name : 'N/A' }}
                </td>
                <!-- <p class="text-xs"><strong>User ID:</strong> {{ $log->user_id ?? 'System' }}</p> -->
                <!-- <p class="text-xs"><strong>Asset ID:</strong> {{ $log->asset_id ?? 'N/A' }}</p> -->
                <p class="text-xs"><strong>Request ID:</strong> {{ $log->request_id ?? 'N/A' }}</p>
                <p class="text-xs"><strong>Date & Time:</strong> {{ $log->created_at->format('Y-m-d H:i:s') }}</p>
            </div>
            @empty
            <div class="bg-gray-100 p-4 rounded-lg text-center text-gray-500">
                No activity logs found.
            </div>
            @endforelse
        </div>
    </div>

    <!-- Settings Modal -->
    {{-- <div id="settingsModal" class="hidden fixed inset-0 bg-gray-800 bg-opacity-50 flex items-center justify-center"> --}}
    <div id="settingsModal" class="hidden fixed inset-0 bg-gray-800 z-10 bg-opacity-50 flex items-center justify-center p-4">
        {{-- <div class="bg-white rounded-lg p-6 shadow-lg w-96"> --}}
        <div class="bg-white rounded-lg p-6 shadow-lg w-full max-w-md">
            {{-- <h2 class="text-xl font-bold mb-4">Log Deletion Settings</h2> --}}
            <h2 class="text-xl font-bold mb-4 text-center">Log Deletion Settings</h2>

            <form action="{{ route('activityLogs.updateSettings') }}" method="POST">
                @csrf
                <label for="deletion_interval" class="block mb-2">Select Deletion Interval:</label>
                <select name="deletion_interval" id="deletion_interval"
                    class="w-full border-gray-300 rounded px-3 py-2 mb-4">
                    {{-- onchange="updateWarningAndTimer()"> --}}
                    <option value="1_week" {{ $interval === '1_week' ? 'selected' : '' }}>Every 1 Week</option>
                    <option value="1_month" {{ $interval === '1_month' ? 'selected' : '' }}>Every 1 Month</option>
                    <option value="1_year" {{ $interval === '1_year' ? 'selected' : '' }}>Every 1 Year</option>
                    <option value="never" {{ $interval === 'never' ? 'selected' : '' }}>Never</option>
                </select>

                <!-- Dynamic Warning Message -->
                <div id="warningMessage" class="text-sm text-yellow-600 mb-4"></div>

                <div class="flex flex-col sm:flex-row justify-center sm:justify-end space-y-2 sm:space-y-0 sm:space-x-2">
                    <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600">
                        Save Settings
                    </button>
                    <button type="button" onclick="toggleSettingsModal()"
                        class="bg-red-500 text-white px-4 py-2 rounded-md hover:bg-red-600">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    const nextDeletionTime = @json(Cache::get('next_deletion_timestamp', null));
    let timer = null;
    let tempInterval = null;

    function toggleExportDropdown() {
        const dropdown = document.getElementById('exportDropdown');
        dropdown.classList.toggle('hidden');
    }

    // Close the dropdown if clicked outside
    window.addEventListener('click', function(e) {
        const menu = document.getElementById('exportDropdown');
        if (!menu.contains(e.target) && !e.target.closest('button')) {
            menu.classList.add('hidden');
        }
    });

    function toggleSettingsModal() {
        const modal = document.getElementById('settingsModal');
        modal.classList.toggle('hidden');
        // Reset temporary interval to the current interval in the cache
        tempInterval = document.getElementById('deletion_interval').value;
    }

    document.getElementById('deletion_interval').addEventListener('change', function() {
        tempInterval = this.value;
        updateWarningMessage(tempInterval);
    });

    function updateWarningMessage(interval) {
        const warningMessage = document.getElementById('warningMessage');

        if (interval === 'never') {
            warningMessage.textContent = 'Logs will be stored indefinitely.';
        } else {
            warningMessage.textContent = 'Consider exporting logs to prevent data loss.';
        }
    }

    function applySettings() {
        const interval = tempInterval;

        if (interval === 'never') {
            localStorage.removeItem('deletionEndTime');
            document.getElementById('countdownTimer').textContent = 'No deletion scheduled.';
        } else {
            const countdownDuration = getCountdownDuration(interval);
            const now = Date.now();
            const endTime = now + countdownDuration;

            localStorage.setItem('deletionEndTime', endTime);
            localStorage.setItem('originalDuration', countdownDuration);
            startCountdown(endTime);
        }
    }

    document.querySelector("form").addEventListener("submit", function(event) {
        event.preventDefault();
        applySettings();
        this.submit(); // Submit the form to save settings to the server
    });

    function updateWarningAndTimer() {
        const interval = document.getElementById('deletion_interval').value;
        const warningMessage = document.getElementById('warningMessage');
        const countdownElement = document.getElementById('countdownTimer');
        let countdownDuration;

        if (timer) clearInterval(timer);

        if (interval === 'never') {
            warningMessage.textContent = 'Logs will be stored indefinitely.';
            localStorage.removeItem('deletionEndTime');
            document.getElementById('countdownTimer').textContent = 'No deletion scheduled.';
        } else {
            warningMessage.textContent = 'Consider exporting logs to prevent data loss.';
            const now = Date.now();
            const countdownDuration = getCountdownDuration(interval);
            const endTime = now + countdownDuration;

            localStorage.setItem('deletionEndTime', endTime);
            localStorage.setItem('originalDuration', countdownDuration);

            // initializeCountdown();
            startCountdown(endTime);
        }
    }

    // FOR TESTING
    // function getCountdownDuration(interval) {
    //     switch (interval) {
    //         case '1_week':
    //             return 1 * 60 * 1000; // 1 minute for testing
    //         case '1_month':
    //             return 2 * 60 * 1000; // 2 minutes for testing
    //         case '1_year':
    //             return 3 * 60 * 1000; // 3 minutes for testing
    //         default:
    //             return 0;
    //     }
    // }

    function getCountdownDuration(interval) {
        switch (interval) {
            case '1_week':
                return 7 * 24 * 60 * 60 * 1000; // 1 week
            case '1_month':
                return 30 * 24 * 60 * 60 * 1000; // 1 month (approximate)
            case '1_year':
                return 365 * 24 * 60 * 60 * 1000; // 1 year
            default:
                return 0;
        }
    }


    // function initializeCountdown() {
    //     // const endTime = nextDeletionTime;
    //     const endTime = parseInt(localStorage.getItem('deletionEndTime'), 10);
    //     if (endTime) {
    //         startCountdown(endTime);
    //     } else {
    //         document.getElementById('countdownTimer').textContent = 'No deletion scheduled.';
    //     }
    // }

    function fetchAndStartTimer() {
        fetch("{{ route('activityLogs.getNextDeletionTime') }}")
            .then(response => {
                if (!response.ok) {
                    throw new Error(`Network response was not ok: ${response.statusText}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Fetched deletion time:', data); // Debugging log
                const countdownElement = document.getElementById('countdownTimer');
                if (data.nextDeletionTime) {
                    startCountdown(data.nextDeletionTime * 1000); // Convert to milliseconds
                } else {
                    countdownElement.textContent = 'No deletion scheduled.';
                    countdownElement.style.color = 'black';
                }
            })
            .catch(error => {
                console.error('Error fetching deletion time:', error);
                document.getElementById('countdownTimer').textContent = 'Error fetching deletion time.';
            });
    }


    function startCountdown(endTime) {
        const countdownElement = document.getElementById('countdownTimer');

        function updateTimer() {
            const now = Date.now();
            const timeLeft = endTime - now;

            if (timeLeft <= 0) {
                countdownElement.textContent = 'Logs will be deleted soon!';
                countdownElement.style.color = 'red';

                // Schedule next deletion and reset timer
                const originalDuration = parseInt(localStorage.getItem('originalDuration'), 10);
                const newEndTime = now + originalDuration;
                localStorage.setItem('deletionEndTime', newEndTime);

                clearInterval(timer);
                startCountdown(newEndTime);
                return;
            }

            // FOR TESTING
            // const minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
            // const seconds = Math.floor((timeLeft % (1000 * 60)) / 1000);

            const days = Math.floor(timeLeft / (1000 * 60 * 60 * 24));
            const hours = Math.floor((timeLeft % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((timeLeft % (1000 * 60)) / 1000);

            countdownElement.style.color = timeLeft <= 10 * 1000 ? 'red' : 'black';

            // FOR TESTING
            // countdownElement.textContent = `${minutes}m ${seconds}s`;

            countdownElement.textContent = `${days}d ${hours}h ${minutes}m ${seconds}s`;

        }

        const timer = setInterval(updateTimer, 1000);
        updateTimer();
    }

    document.addEventListener('DOMContentLoaded', fetchAndStartTimer);
</script>
@endsection
