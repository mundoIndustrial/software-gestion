@extends('layouts.asesores')

@include('components.modal-imagen')

@section('extra_styles')
    <link rel="stylesheet" href="{{ asset('css/crear-pedido.css') }}">
@endsection

@section('content')
    @php
        $tipo = $tipoInicial ?? 'cotizacion';
    @endphp

    <!-- Loading Overlay de Página Completa -->
    <div id="page-loading-overlay">
        <div class="loading-spinner"></div>
        <div class="loading-text">Cargando sistema de pedidos...</div>
        <div class="loading-subtext">Por favor espera mientras preparamos todo</div>
    </div>

    @if($tipo === 'cotizacion')
        {{-- Flujo para pedidos desde cotización --}}
        @include('asesores.pedidos.crear-pedido-desde-cotizacion')
    @elseif($tipo === 'nuevo')
        {{-- Flujo para pedidos nuevos --}}
        @include('asesores.pedidos.crear-pedido-nuevo')
    @endif

@endsection

@push('scripts')
    <!-- IMPORTANTE: Cargar constantes PRIMERO -->
    <script src="{{ asset('js/constantes-tallas.js') }}"></script>
    
    <!-- IMPORTANTE: Cargar helpers ANTES de los módulos que los usan -->
    <script src="{{ asset('js/modulos/crear-pedido/helpers-pedido-editable.js') }}"></script>
    
    <!-- IMPORTANTE: Cargar módulos DESPUÉS de las constantes -->
    <!-- Módulos para COTIZACIONES (crear pedido desde cotización) -->
    <script src="{{ asset('js/modulos/crear-pedido/modales-dinamicos.js') }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/gestion-tallas.js') }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/gestion-telas.js') }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/gestion-items-pedido.js') }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/modal-seleccion-prendas.js') }}"></script>
    
    <!-- Módulos para NUEVO PEDIDO (sin cotización) -->
    <script src="{{ asset('js/modulos/crear-pedido/gestor-tallas-sin-cotizacion.js') }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/funciones-prenda-sin-cotizacion.js') }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/funciones-reflectivo-sin-cotizacion.js') }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/gestor-prenda-sin-cotizacion.js') }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/gestor-reflectivo-sin-cotizacion.js') }}"></script>
    
    <!-- Componentes compartidos -->
    <script src="{{ asset('js/componentes/prendas.js') }}"></script>
    <script src="{{ asset('js/componentes/reflectivo.js') }}"></script>
    
    <!-- API y Servicios -->
    <script src="{{ asset('js/modulos/crear-pedido/api-pedidos-editable.js') }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/image-storage-service.js') }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/gestion-items-pedido-refactorizado.js') }}"></script>

    <script>
        // Ocultar loading screen al terminar
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(() => {
                const loadingOverlay = document.getElementById('page-loading-overlay');
                if (loadingOverlay) {
                    loadingOverlay.classList.add('fade-out');
                    setTimeout(() => {
                        loadingOverlay.style.display = 'none';
                    }, 300);
                }
            }, 500);
        });
    </script>
@endpush
