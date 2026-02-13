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
    <!-- CSS para tarjetas de prenda (debe cargarse antes que los scripts) -->
    <link rel="stylesheet" href="{{ asset('css/componentes/prenda-card-readonly.css') }}">
    
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
    
    <!--  SERVICIOS EDICIÓN DINÁMICA DE PROCESOS - Deben cargarse PRIMERO -->
    <script src="{{ asset('js/modulos/crear-pedido/prendas/proceso-editor.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/prendas/gestor-edicion-procesos.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/prendas/servicio-procesos.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/prendas/middleware-guardado-prenda.js') }}?v={{ time() }}"></script>
    
    <!--  SERVICIOS SOLID - Deben cargarse ANTES de GestionItemsUI -->
    <script src="{{ asset('js/modulos/crear-pedido/prendas/notification-service.js') }}?v={{ time() }}"></script>
    
    <!-- PAYLOAD NORMALIZER v3 - VERSIÓN DEFINITIVA Y SEGURA -->
    <script src="{{ asset('js/modulos/crear-pedido/prendas/payload-normalizer.js') }}?v={{ time() }}"></script>
    
    <script src="{{ asset('js/modulos/crear-pedido/prendas/item-api-service.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/prendas/item-validator.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/prendas/item-form-collector.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/prendas/item-renderer.js') }}?v={{ time() }}"></script>
    <!-- 🆕 PrendaEditor sin legacy -->
    <script src="{{ asset('js/modulos/crear-pedido/prendas/prenda-editor.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/prendas/prenda-editor-init.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/prendas/item-orchestrator.js') }}?v={{ time() }}"></script>
    
    <!-- IMPORTANTE: Cargar módulos DESPUÉS de las constantes y servicios -->
    <!-- Módulos para COTIZACIONES (crear pedido desde cotización) -->
    <script src="{{ asset('js/modulos/crear-pedido/modales/modales-dinamicos.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/tallas/gestion-tallas.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/telas/gestion-telas.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/procesos/gestion-items-pedido.js') }}"></script>
    <script src="{{ asset('js/componentes/prenda-card-readonly.js') }}"></script>
    <script src="{{ asset('js/componentes/services/prenda-card-handlers.js') }}"></script>
    <script src="{{ asset('js/componentes/prenda-card-editar-simple.js') }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/modales/modales-pedido.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/modales/modal-seleccion-prendas.js') }}?v={{ time() }}"></script>
    
    <!-- Módulos para NUEVO PEDIDO (sin cotización) -->
    <script src="{{ asset('js/modulos/crear-pedido/gestores/gestor-tallas-sin-cotizacion.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/prendas/funciones-prenda-sin-cotizacion.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/prendas/integracion-prenda-sin-cotizacion.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/reflectivo/funciones-reflectivo-sin-cotizacion.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/gestores/gestor-prenda-sin-cotizacion.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/reflectivo/gestor-reflectivo-sin-cotizacion.js') }}?v={{ time() }}"></script>
    
    <!-- Componentes compartidos -->
    
    <!-- API y Servicios -->
    <script src="{{ asset('js/modulos/crear-pedido/configuracion/api-pedidos-editable.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/fotos/image-storage-service.js') }}?v={{ time() }}"></script>

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

    <!-- 🔬 TEST SUITE: Puedes ejecutar testPrendaEditor() en la consola -->
    <script src="{{ asset('js/tests/prenda-editor-test.js') }}?v={{ time() }}"></script>
@endpush
