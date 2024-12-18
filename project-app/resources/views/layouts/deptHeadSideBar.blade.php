<aside id="sidebar" class="h-screen transition-all duration-300 ease-in-out max-md:w-[50px] md:w-[205px] overflow-hidden flex flex-col items-center p-2 fixed bg-blue-950 font-semibold text-white">
    <!-- Hamburger Button -->
    {{-- <button id="hamburgerToggle" class="h-[30px] mb-4 max-md:block lg:hidden">
        <x-icons.hamburger />
    </button> --}}

    <!-- Profile Section -->
    <x-nav-link :href="route('profile')" class="mt-3 items-center justify-center">
        <div class="profileAccount flex items-center p-2 rounded-lg transition-all">
            <div class="imagepart overflow-hidden rounded-full w-[30px] h-[30px] md:w-[60px] md:h-[60px] border-2 border-slate-500">
                <img src="{{ Auth::user()->userPicture ? asset('storage/' . Auth::user()->userPicture) : asset('images/default_profile.jpg') }}"
                    class="w-full h-full object-cover rounded-full" alt="User Profile Photo">
            </div>
            <div class="profileUser flex-col ml-2 text-[12px] hidden lg:flex">
                <span class="font-normal">{{ Auth::user()->lastname }}, {{ Auth::user()->firstname }}</span>
                <span>
                    @switch(Auth::user()->usertype)
                    @case('dept_head') Department Head @break
                    @case(2) Admin @break
                    @endswitch
                </span>
            </div>
        </div>
    </x-nav-link>

    <div class="divider w-[80%] h-[1px] bg-white mt-2 mb-2"></div>

    <!-- Navigation Menu -->
    <nav class="w-full">
        <ul class="flex flex-col w-full space-y-1">
            <li>
                <x-nav-link :href="route('dept_head.home')" :active="request()->routeIs('dept_head.home')"
                    class="flex items-center p-2 space-x-2 sidebar-icon rounded-md transition-all">
                    <x-icons.dash-icon class="w-8 h-8 md:w-6 md:h-6" />
                    <span class="hidden md:inline">Dashboard</span>
                </x-nav-link>
            </li>

            <li>
                <x-nav-link :href="route('asset')" :active="request()->routeIs('asset')"
                    class="flex items-center p-2 space-x-2 sidebar-icon rounded-md transition-all">
                    <x-icons.receipticon class="w-8 h-8 md:w-6 md:h-6" />
                    <span class="hidden md:inline">Asset</span>
                </x-nav-link>
            </li>

            <li class="relative">
                <button id="maintenanceDropdownToggle"
                    class="flex items-center w-full text-left p-2 hover:bg-slate-400/15 rounded-md transition-all"
                    aria-expanded="false">
                    <x-icons.wrench-icon class="w-8 h-8 md:w-6 md:h-6" />
                    <span class="hidden md:inline">&nbsp;&nbsp;Maintenance</span>
                    {{-- <i class="fas fa-chevron-down ml-auto"></i> --}}
                    <!-- SVG Icon on the Right Side -->
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                        stroke-width="1.5" stroke="currentColor"
                        class="ml-auto w-5 h-5 transition-transform duration-200 toggle-icon"
                        id="maintenanceIcon">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                    </svg>
                </button>

                <ul id="maintenanceDropdownMenu"> <!-- Added padding-left -->
                    <x-nav-link :href="route('maintenance', ['dropdown' => 'open'])"
                        :active="request()->routeIs('maintenance')"
                        class="flex items-center p-2 space-x-2 sidebar-icon rounded-md transition-all">
                        <x-icons.envelope-icon class="w-8 h-8 md:w-6 md:h-6" />
                        <span class="hidden sm:inline">Request</span>
                    </x-nav-link>

                    <x-nav-link :href="route('maintenance_sched', ['dropdown' => 'open'])"
                        :active="request()->routeIs('maintenance_sched')"
                        class="flex items-center p-2 space-x-2 sidebar-icon rounded-md transition-all">
                        <x-icons.calendar-icon class="w-8 h-8 md:w-6 md:h-6" />
                        <span class="hidden sm:inline">Scheduling</span>
                    </x-nav-link>

                    <x-nav-link :href="route('maintenance.records', ['status' => 'completed', 'dropdown' => 'open'])"
                        :active="request()->routeIs('maintenance.records')"
                        class="flex items-center p-2 space-x-2 sidebar-icon rounded-md transition-all">
                        <x-icons.records-icon class="w-8 h-8 md:w-6 md:h-6" />
                        <span class="hidden sm:inline">Records</span>
                    </x-nav-link>
                </ul>
            </li>

            <li class="relative">
                <button id="reportsDropdownToggle"
                    class="flex items-center w-full text-left p-2 hover:bg-slate-400/15 rounded-md transition-all"
                    aria-expanded="false">
                    <x-icons.chart-icon class="w-8 h-8 md:w-6 md:h-6" />
                    <span class="hidden md:inline">&nbsp;&nbsp;Reports</span>
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                        stroke-width="1.5" stroke="currentColor"
                        class="ml-auto w-5 h-5 transition-transform duration-200 toggle-icon"
                        id="maintenanceIcon">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                    </svg>
                </button>

                <ul id="reportsDropdownMenu"> <!-- Added padding-left -->
                    <x-nav-link :href="route('asset.report', ['dropdown' => 'open'])"
                        :active="request()->routeIs('asset.report')"
                        class="flex items-center p-2 space-x-2 sidebar-icon rounded-md transition-all">
                        <x-icons.envelope-icon class="w-8 h-8 md:w-6 md:h-6" />
                        <span class="hidden sm:inline">Assets</span>
                    </x-nav-link>

                    <x-nav-link :href="route('maintenance.report')"
                        :active="request()->routeIs('maintenance.report')"
                        class="flex items-center p-2 space-x-2 sidebar-icon rounded-md transition-all">
                        <x-icons.calendar-icon class="w-8 h-8 md:w-6 md:h-6" />
                        <span class="hidden sm:inline">Maintenance</span>
                    </x-nav-link>
                </ul>
            </li>


            <li>
                <x-nav-link :href="route('setting')" :active="request()->routeIs('setting')"
                    class="flex items-center p-2 space-x-2 sidebar-icon rounded-md transition-all">
                    <x-icons.gear-icon class="w-8 h-8 md:w-6 md:h-6" />
                    <span class="hidden md:inline">Settings</span>
                </x-nav-link>
            </li>

            <li>
                <x-nav-link :href="route('notifications.index')" :active="request()->routeIs('notifications.index')"
                    class="flex items-center p-2 space-x-2 sidebar-icon rounded-md transition-all">
                    <x-icons.bell-icon class="w-8 h-8 md:w-6 md:h-6" />
                    <span class="hidden md:inline">Notifications</span>
                </x-nav-link>
            </li>

            <li>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="flex items-center w-full p-2 hover:bg-slate-400/15 rounded-md space-x-2 transition-all">
                        <x-icons.logout-icon class="w-8 h-8 md:w-6 md:h-6" />
                        <span class="hidden md:inline">Log out</span>
                    </button>
                </form>
            </li>
        </ul>
    </nav>
</aside>

<style>
    #maintenanceDropdownMenu,
    #reportsDropdownMenu {
        max-height: 0;
        opacity: 0;
        overflow: hidden;
        transition: max-height 0.3s ease-in-out, opacity 0.3s ease-in-out;
    }

    /* #reportsDropdownMenu {
    max-height: 0;
    opacity: 0;
    overflow: hidden;
    transition: max-height 0.3s ease-in-out, opacity 0.3s ease-in-out;
} */

    .rotate-180 {
        transform: rotate(180deg);
    }

    /* Hide SVG arrow when sidebar is collapsed or on small screens */
    @media (max-width: 768px) {
        .toggle-icon {
            display: none;
        }
    }

    /* Hide the arrow if sidebar is collapsed */
    .collapsed .toggle-icon {
        display: none;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const reportsToggle = document.getElementById('reportsDropdownToggle');
        const reportsMenu = document.getElementById('reportsDropdownMenu');
        const maintenanceToggle = document.getElementById('maintenanceDropdownToggle');
        const maintenanceMenu = document.getElementById('maintenanceDropdownMenu');

        // Restore dropdown states on page load
        restoreDropdownState('reportsDropdownOpen', reportsMenu, reportsToggle);
        restoreDropdownState('maintenanceDropdownOpen', maintenanceMenu, maintenanceToggle);

        // Toggle reports dropdown
        reportsToggle.addEventListener('click', (event) => {
            event.preventDefault();
            toggleDropdown(reportsMenu, reportsToggle, 'reportsDropdownOpen');
        });

        // Toggle maintenance dropdown
        maintenanceToggle.addEventListener('click', (event) => {
            event.preventDefault();
            toggleDropdown(maintenanceMenu, maintenanceToggle, 'maintenanceDropdownOpen');
        });

        // Detect clicks inside dropdowns and allow navigation
        document.addEventListener('click', (event) => {
            const target = event.target;

            if (!target.closest('#reportsDropdownMenu') && !target.closest('#reportsDropdownToggle')) {
                closeDropdown(reportsMenu, reportsToggle, 'reportsDropdownOpen');
            }

            if (!target.closest('#maintenanceDropdownMenu') && !target.closest('#maintenanceDropdownToggle')) {
                closeDropdown(maintenanceMenu, maintenanceToggle, 'maintenanceDropdownOpen');
            }
        });

        // Prevent unnecessary propagation on dropdown items to allow them to function
        const dropdownLinks = document.querySelectorAll('#reportsDropdownMenu a, #maintenanceDropdownMenu a');
        dropdownLinks.forEach((link) => {
            link.addEventListener('click', (event) => {
                const targetUrl = link.getAttribute('href');
                if (targetUrl) {
                    window.location.href = targetUrl; // Navigate to the target URL
                }
            });
        });

        // Toggle dropdown logic
        function toggleDropdown(menu, toggle, key) {
            const isOpen = toggle.getAttribute('aria-expanded') === 'true';
            if (isOpen) {
                closeDropdown(menu, toggle, key);
            } else {
                openDropdown(menu, toggle, key);
            }
        }

        // Open dropdown
        function openDropdown(menu, toggle, key) {
            menu.style.maxHeight = `${menu.scrollHeight}px`;
            menu.style.opacity = '1';
            toggle.setAttribute('aria-expanded', 'true');
            localStorage.setItem(key, 'true');
        }

        // Close dropdown
        function closeDropdown(menu, toggle, key) {
            menu.style.maxHeight = '0';
            menu.style.opacity = '0';
            toggle.setAttribute('aria-expanded', 'false');
            localStorage.setItem(key, 'false');
        }

        // Restore dropdown state from localStorage
        function restoreDropdownState(key, menu, toggle) {
            const isOpen = localStorage.getItem(key) === 'true';
            if (isOpen) {
                openDropdown(menu, toggle, key);
            }
        }
    });
</script>
