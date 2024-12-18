{{--
    THIS WILL DISPLAY THE PROFILE OF THE CURRENT USER.
    ALLOWING THE TO EDIT THEIR INFORMATION AND CHANGE PASSWORD.
--}}

@include('components.icons')
@extends('layouts.app')

@php
    use Carbon\Carbon;
@endphp

@section('header')
    {{-- <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        {{ "Profile" }}
    </h2> --}}
<h2 class="font-medium text-lg sm:text-xl text-gray-800 leading-tight text-center sm:text-left">
    {{ "Profile" }}
</h2>
@endsection

@section('content')
    <div class="relative">
        {{-- <div class="absolute top-0 right-0 mt-3 mr-3 flex flex-col"> --}}
        <div class="flex justify-end flex-col sm:flex-row sm:space-x-2">
            <!-- Container for buttons -->
            {{-- <div id="profile-buttons" class="flex flex-col"> --}}
            <div id="profile-buttons" class="flex flex-col sm:flex-row sm:space-x-2">
                <!-- Edit Profile and Change Password buttons -->
                <a href="#" onclick="editProfile(event)" class="mb-1 sm:mb-0 px-2 sm:px-4 py-2 sm:py-2 text-xs sm:text-sm bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded hover:bg-gray-300 dark:hover:bg-gray-600 w-full sm:w-auto text-center">
                    Edit Profile
                </a>
                <a href="{{ route('admin.profile_password') }}" class="mb-1 sm:mb-0 px-2 sm:px-4 py-2 sm:py-2 text-xs sm:text-sm bg-blue-200 dark:bg-blue-700 text-blue-800 dark:text-blue-200 rounded hover:bg-blue-300 dark:hover:bg-blue-600 w-full sm:w-auto text-center">
                    Change Password
                </a>
            </div>
            {{-- <div id="edit-buttons" class="hidden flex flex-col"> --}}
            <div id="edit-buttons" class="hidden flex flex-col sm:flex-row sm:space-x-2">
                <!-- Save and Cancel buttons -->
                <a href="#" onclick="saveProfile(event)" class="mb-1 sm:mb-0 px-2 sm:px-4 py-2 sm:py-2 text-xs sm:text-sm bg-green-200 dark:bg-green-700 text-green-800 dark:text-green-200 rounded hover:bg-green-300 dark:hover:bg-green-600 w-full sm:w-auto text-center">
                    Save
                </a>
                <a href="#" onclick="cancelEdit(event)" class="mb-1 sm:mb-0 px-2 sm:px-4 py-2 sm:py-2 text-xs sm:text-sm bg-red-200 dark:bg-red-700 text-red-800 dark:text-red-200 rounded hover:bg-red-300 dark:hover:bg-red-600 w-full sm:w-auto text-center">
                 Cancel
             </a>
            </div>
        </div>
    </div>

    <div class="">
        {{-- <div class="max-w-4xl mx-auto sm:px-6 lg:px-8"> --}}
        <div class="max-w-4xl mx-auto px-3 sm:px-5 lg:px-6">
            <form id="profile-form" method="POST" enctype="multipart/form-data" action="{{ route(Auth::user()->usertype . '.profile_update') }}">
                @csrf
                @method('PATCH')
                <div class="p-4">
                    <!-- Profile Header -->
                    {{-- <div class="text-center pb-6 mb-6"> --}}
                    <div class="text-center pb-4 mb-4">
                        {{-- <div class="relative flex justify-center mb-4"> --}}
                        <div class="relative flex justify-center mb-3">
                            <!-- Profile Photo with Camera Icon (Initially Hidden) -->
                            <img id="profilePhoto"
                                src="{{ auth()->user()->userPicture ? asset('storage/' . auth()->user()->userPicture) : asset('images/default_profile.jpg') }}"
                                alt="Profile Picture"
                                class="w-20 h-20 sm:w-32 sm:h-32 rounded-full object-cover border-2 border-gray-300">
                            <label id="cameraIcon" for="profile_photo"
                                    class="hidden absolute bottom-0 right-21 bg-gray-200 p-1 sm:p-2 rounded-full cursor-pointer shadow-md">
                                <input type="file" id="profile_photo" name="profile_photo" class="hidden" accept="image/*" onchange="previewImage(event)" />
                                @yield('cameraIcon')
                            </label>
                        </div>
                        <h3 class="text-lg sm:text-xl font-medium">
                            {{ auth()->user()->firstname ?? 'Guest' }}
                            {{ auth()->user()->middlename ? auth()->user()->middlename . ' ' : '' }}
                            {{ auth()->user()->lastname ?? '' }}
                        </h3>
                    </div>

                    <!-- Profile Details -->
                    <div class="border-b-2 border-t-2 border-gray-100 pb-8 pt-8">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm sm:text-base">
                            <div class="flex items-center">
                                @yield('locationIcon')
                                <div class="ml-1 flex-1">
                                    <span id="address-display">{{ Auth::user()->address ?? 'N/A'}}</span>
                                    <input id="address-edit" name="location" type="text" class="hidden w-full border-gray-300 rounded-md" value="{{ Auth::user()->address }}">
                                </div>
                            </div>
                            <div class="flex items-center">
                                @yield('emailIcon')
                                <div class="ml-1 flex-1">
                                    <span>{{ Auth::user()->email ?? 'N/A'}}</span>
                                </div>
                            </div>
                            <div class="flex items-center">
                                @yield('contactIcon')
                                <div class="ml-1 flex-1">
                                <span id="contact-display">{{ Auth::user()->contact ?? 'N/A'}}</span>
                                    <input id="contact-edit" name="contact" type="text" class="hidden w-full border-gray-300 rounded-md" value="{{ Auth::user()->contact }}">
                                </div>
                            </div>
                            <div class="flex items-center">
                                @yield('idNumberIcon')
                                <div class="ml-1 flex-1">
                                    <span>{{ Auth::user()->employee_id ?? 'N/A' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- More Profile Details -->
                    <div class="border-b-2 border-t-2 border-gray-100 pb-8 pt-8">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm sm:text-base">
                            <div class="flex items-center">
                                <div class="ml-1 flex-1">
                                    <span id="status-display">Status: <span class="font-bold uppercase">{{ Auth::user()->status ?? 'N/A' }}</span></span>
                                </div>
                            </div>
                            <div class="flex items-center">
                                <div class="ml-1 flex-1">
                                    <span>Department: <span class="font-bold uppercase">{{ $departmentName }}</span></span>
                                </div>
                            </div>
                            <div class="flex items-center">
                                <div class="ml-1 flex-1">
                                    <!-- Birthdate Field -->
                                    <span id="birthdate-display">Birthdate:
                                        <span class="font-bold uppercase" id="birthdate-view">{{ Auth::user()->birthdate ? \Carbon\Carbon::parse(Auth::user()->birthdate)->format('Y-m-d') : 'N/A' }}</span>
                                    </span>
                                    <input type="date" id="birthdate-edit" name="birthdate" value="{{ Auth::user()->birthdate ? \Carbon\Carbon::parse(Auth::user()->birthdate)->format('Y-m-d') : '' }}" class="hidden ml-2 border-gray-300 rounded-md" />
                                </div>
                            </div>
                            <div class="flex items-center">
                                <div class="ml-1 flex-1">
                                    <span>Account Created: <span class="font-bold uppercase">{{ Auth::user()->created_at ? \Carbon\Carbon::parse(Auth::user()->created_at)->format('Y-m-d h:i A') : 'N/A' }}</span></span>
                                </div>
                            </div>
                            <div class="flex items-center">
                                <div class="ml-1 flex-1">
                                    <span id="age-display">Age: <span class="font-bold uppercase">{{ Auth::user()->birthdate ? \Carbon\Carbon::parse(Auth::user()->birthdate)->age : 'N/A' }}</span></span>
                                </div>
                            </div>
                            <div class="flex items-center">
                                <div class="ml-1 flex-1">
                                    <!-- Gender Field -->
                                    <span>Gender:
                                        <span class="font-bold uppercase" id="gender-view">{{ Auth::user()->gender ?? 'N/A' }}</span>
                                    </span>
                                    <select id="gender-edit" name="gender" class="hidden ml-2 border-gray-300 rounded-md">
                                        <option value="male" {{ Auth::user()->gender === 'male' ? 'selected' : '' }}>Male</option>
                                        <option value="female" {{ Auth::user()->gender === 'female' ? 'selected' : '' }}>Female</option>
                                    </select>
                                </div>
                            </div>
                            <div class="flex items-center">
                                <div class="ml-1 flex-1">
                                    <span id="birhtdate-display">User Type: <span class="font-bold uppercase">{{ Auth::user()->usertype ?? 'N/A' }}</span></span>
                                </div>
                            </div>
                        </div>
                    </div>


                </div>
            </form>
        </div>
    </div>

    <script src="{{ asset('js/profile.js') }}"></script>

    <!-- Toast Notification -->
    @if(session('status'))
        <div id="toast" class="fixed bottom-5 right-5 bg-green-500 text-white px-4 py-2 rounded shadow-lg">
            {{ session('status') }}
        </div>
    @endif

@endsection
