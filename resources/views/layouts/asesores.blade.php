@extends('layouts.base')

@section('module', 'asesores')
@section('notifications-ui', 'asesores')

@section('body')
<!-- Loading Overlay - Mostrar mientras carga la página -->
<div id="loading-overlay">
    <div style="text-align: center;">
        <div style="
            width: 60px;
            height: 60px;
            margin: 0 auto 20px;
            border: 4px solid #e5e7eb;
            border-top: 4px solid #3b82f6;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        "></div>
        <h2 style="color: #374151; font-size: 1.125rem; margin: 10px 0; font-weight: 600;">Cargando...</h2>
        <p style="color: #6b7280; font-size: 0.875rem; margin: 0;">Preparando la página</p>
    </div>
</div>

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
    <script defer src="{{ asset('js/asesores/notifications.js') }}"></script>
    
    <!-- Ocultar loading overlay cuando todos los recursos estén cargados -->
    <script>
        function hideLoadingOverlay() {
            const overlay = document.getElementById('loading-overlay');
            if (overlay) {
                overlay.classList.add('hidden');
            }
        }
        
        // Esperar a que todos los recursos estén cargados
        if (document.readyState === 'complete') {
            hideLoadingOverlay();
        } else {
            window.addEventListener('load', hideLoadingOverlay);
        }
    </script>
@endpush
