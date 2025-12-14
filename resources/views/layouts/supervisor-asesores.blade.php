@extends('layouts.base')

@section('module', 'supervisor-asesores')

@section('body')
<div class="container">
    @include('components.sidebars.sidebar-supervisor-asesores')

    <div class="main-content" id="mainContent">
        <!-- Header Supervisor Asesores -->
        @include('components.headers.header-supervisor-asesores')

        <!-- Page Content -->
        <main class="page-content">
            @yield('content')
        </main>
    </div>
</div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/asesores/layout.css') }}">
    <link rel="stylesheet" href="{{ asset('css/asesores/module.css') }}">
    <link rel="stylesheet" href="{{ asset('css/asesores/dashboard.css') }}">
    <link rel="stylesheet" href="{{ asset('css/supervisor-asesores/layout.css') }}">
@endpush

@push('scripts')
    <script src="{{ asset('js/asesores/layout.js') }}"></script>
    <script src="{{ asset('js/asesores/notifications.js') }}"></script>
@endpush
