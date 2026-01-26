@extends('layouts.asesores')

@include('components.modal-imagen')

@section('extra_styles')
    <link rel="stylesheet" href="{{ asset('css/crear-pedido.css') }}">
    <link rel="stylesheet" href="{{ asset('css/crear-pedido-editable.css') }}">
    <link rel="stylesheet" href="{{ asset('css/form-modal-consistency.css') }}">
    <link rel="stylesheet" href="{{ asset('css/swal-z-index-fix.css') }}">
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
    <script src="{{ asset('js/configuraciones/constantes-tallas.js') }}"></script>
    
    <!-- IMPORTANTE: Cargar helpers ANTES de los módulos que los usan -->
    <script src="{{ asset('js/modulos/crear-pedido/utilidades/helpers-pedido-editable.js') }}"></script>
    
    <!--  GESTOR CENTRALIZADO JSON - Debe cargarse PRIMERO -->
    <script src="{{ asset('js/modulos/crear-pedido/gestor-datos-pedido-json.js') }}"></script>
    
    <!-- Manejadores de procesos - DEBEN cargarse ANTES de prenda-editor.js -->
    <script src="{{ asset('js/modulos/crear-pedido/procesos/manejadores-procesos-prenda.js') }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/procesos/gestor-modal-proceso-generico.js') }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/procesos/renderizador-tarjetas-procesos.js') }}"></script>
    
    <!--  SERVICIOS SOLID - Deben cargarse ANTES de GestionItemsUI -->
    <script src="{{ asset('js/modulos/crear-pedido/procesos/services/notification-service.js') }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/procesos/services/item-api-service.js') }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/procesos/services/item-validator.js') }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/procesos/services/item-form-collector.js') }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/procesos/services/item-renderer.js') }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/procesos/services/prenda-editor.js') }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/procesos/services/item-orchestrator.js') }}"></script>
    
    <!-- IMPORTANTE: Cargar módulos DESPUÉS de las constantes y servicios -->
    <!-- Módulos para COTIZACIONES (crear pedido desde cotización) -->
    <script src="{{ asset('js/modulos/crear-pedido/modales/modales-dinamicos.js') }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/tallas/gestion-tallas.js') }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/telas/gestion-telas.js') }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/procesos/gestion-items-pedido.js') }}"></script>
    <script src="{{ asset('js/componentes/prenda-card-readonly.js') }}"></script>
    <script src="{{ asset('js/componentes/prenda-card-editar-simple.js') }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/modales/modales-pedido.js') }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/modales/modal-seleccion-prendas.js') }}"></script>
    
    <!-- Módulos para NUEVO PEDIDO (sin cotización) -->
    <script src="{{ asset('js/modulos/crear-pedido/gestores/gestor-tallas-sin-cotizacion.js') }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/prendas/funciones-prenda-sin-cotizacion.js') }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/prendas/integracion-prenda-sin-cotizacion.js') }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/reflectivo/funciones-reflectivo-sin-cotizacion.js') }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/gestores/gestor-prenda-sin-cotizacion.js') }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/reflectivo/gestor-reflectivo-sin-cotizacion.js') }}"></script>
    
    <!-- Componentes compartidos -->
    <script src="{{ asset('js/componentes/reflectivo.js') }}"></script>
    
    <!-- API y Servicios -->
    <script src="{{ asset('js/modulos/crear-pedido/configuracion/api-pedidos-editable.js') }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/fotos/image-storage-service.js') }}"></script>

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
