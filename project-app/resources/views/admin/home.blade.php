<!-- resources/views/admin/home.blade.php -->

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
           {{ "Dashboard" }}
        </h2>
    </x-slot>

    <div class="contents relative flex ">
        {{-- Cards --}}
        <div class="text-center max-w-100 flex justify-center sm:flex-col md:flex-row ">
            <h1>Welcome, Admin!</h1>
            <p>This is the admin landing page.</p>
        </div>
    </div>
</x-app-layout>



