@extends('layouts.app')

@section('header')
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        {{ "Dashboard" }}
    </h2>
@endsection
@section('content')
<div class="contents relative flex ">
    {{-- Cards --}}
    <div class="text-center max-w-100 flex justify-center sm:flex-col md:flex-row ">
        <div class="card  w-100">
            <div class="card-header">
                Active
            </div>
            <div class="card-body">
                {{ $asset['active'] }}
            </div>
        </div>
        <div class="card  w-100">
            <div class="card-header">
                Under Maintenance
            </div>
            <div class="card-body">
                {{ $asset['um'] }}
            </div>
        </div>
        <div class="card  w-100">
            <div class="card-header">
                deployed
            </div>
            <div class="card-body">
                {{ $asset['deploy'] }}
            </div>
        </div>
        <div class="card  w-100">
            <div class="card-header">
                disposed
            </div>
            <div class="card-body">
                {{ $asset['dispose'] }}
            </div>

</div>
</div>
{{-- Recent Activity --}}
<h2 class="font-semibold text-xl text-gray-800 leading-tight">
{{ "Recent Activities" }}
</h2>
@endsection

