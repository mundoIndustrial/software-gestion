@extends('layouts.base')

@section('module', 'asesores')

@section('body')
<div class="asesores-wrapper">
    <!-- Sidebar Asesores (Moderno) -->
    @include('components.sidebars.sidebar-asesores')

    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <!-- Header Asesores (Con notificaciones y perfil) -->
        @include('components.headers.header-asesores')

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
    @yield('extra_styles')
@endpush

@push('scripts')
    <script defer src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script defer src="{{ asset('js/asesores/layout.js') }}"></script>
@endpush
