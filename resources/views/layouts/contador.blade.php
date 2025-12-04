@extends('layouts.base')

@section('module', 'contador')

@section('body')
<div class="contador-wrapper">
    <!-- Sidebar Contador -->
    @include('contador.sidebar')

    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <!-- Header Contador (Con notificaciones y perfil) -->
        @include('components.headers.header-contador')

        <!-- Page Content -->
        <main class="page-content">
            @yield('content')
        </main>
    </div>
</div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/contador/layout.css') }}">
    <link rel="stylesheet" href="{{ asset('css/contador/header.css') }}">
    <link rel="stylesheet" href="{{ asset('css/contador/cotizacion-modal.css') }}">
@endpush

@push('scripts')
    <script src="{{ asset('js/contador/editar-tallas.js') }}"></script>
    <script src="{{ asset('js/contador/cotizacion.js') }}"></script>
    <script src="{{ asset('js/contador/contador.js') }}"></script>
    <script src="{{ asset('js/contador/notifications.js') }}"></script>
    <script>
        /**
         * Cargar contador de cotizaciones pendientes
         */
        function cargarContadorPendientes() {
            fetch('{{ route("contador.cotizaciones-pendientes-count") }}')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.count > 0) {
                        const badge = document.getElementById('cotizacionesPendientesCount');
                        if (badge) {
                            badge.textContent = data.count;
                            badge.style.display = 'inline-flex';
                        }
                    }
                })
                .catch(error => console.error('Error al cargar contador:', error));
        }

        // Cargar contador al cargar la página
        document.addEventListener('DOMContentLoaded', cargarContadorPendientes);

        // Recargar contador cada 30 segundos
        setInterval(cargarContadorPendientes, 30000);
    </script>
@endpush
