@extends('supervisor-pedidos.layout')

@section('title', 'Supervisión de Pedidos')
@section('page-title', 'Supervisión de Pedidos')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/supervisor-pedidos/index.css') }}?v={{ filemtime(public_path('css/supervisor-pedidos/index.css')) }}">
    <!-- CSS para modal-agregar-prenda-nueva y formularios de edición -->
    <link rel="stylesheet" href="{{ asset('css/crear-pedido.css') }}?v={{ filemtime(public_path('css/crear-pedido.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/crear-pedido-editable.css') }}?v={{ filemtime(public_path('css/crear-pedido-editable.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/form-modal-consistency.css') }}?v={{ filemtime(public_path('css/form-modal-consistency.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/swal-z-index-fix.css') }}?v={{ filemtime(public_path('css/swal-z-index-fix.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/componentes/prendas.css') }}?v={{ filemtime(public_path('css/componentes/prendas.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/modales/modal-exito-pedido.css') }}?v={{ filemtime(public_path('css/modales/modal-exito-pedido.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/modulos/epp-modal.css') }}?v={{ filemtime(public_path('css/modulos/epp-modal.css')) }}">
 
    <link rel="stylesheet" href="{{ asset('css/tracking-modal.css') }}?v={{ filemtime(public_path('css/tracking-modal.css')) }}">

    <!-- 🚨 FIX: Asegurar z-index correcto para modal de prendas en supervisor-pedidos -->
    <style>
        /* Modal específico para prendas debe estar por encima de todo */
        #modal-agregar-prenda-nueva.modal-overlay {
            z-index: 1050001 !important;
        }
        
        /* Contenedor del modal también necesita z-index alto */
        #modal-agregar-prenda-nueva .modal-container {
            z-index: 1050002 !important;
            position: relative;
        }
        
        /* SweetAlert2 debe estar por debajo del modal de prendas */
        .swal2-container {
            z-index: 1050000 !important;
        }
        
        /* 🚨 FIX: Manejar modal-backdrop de Bootstrap que está tapando el modal */
        .modal-backdrop {
            z-index: 999999 !important; /* Poner backdrop por debajo del modal */
        }
        
        /* O mejor aún, ocultar backdrop cuando nuestro modal está activo */
        #modal-agregar-prenda-nueva:not([style*="display: none"]) ~ .modal-backdrop,
        #modal-agregar-prenda-nueva:not([style*="display: none"]) + .modal-backdrop {
            display: none !important;
        }
        
        /* Asegurar que el modal no sea afectado por aria-hidden */
        #modal-agregar-prenda-nueva:not([style*="display: none"]) {
            visibility: visible !important;
            opacity: 1 !important;
        }
        
        /* 🚨 Modal de confirmación de limpiar asignaciones */
        #modal-confirmar-limpiar {
            z-index: 1060000 !important;
        }
        
        #modal-confirmar-limpiar .modal-dialog {
            z-index: 1060001 !important;
            position: relative;
        }
        
        /* Asegurar que el modal de confirmación no sea tapado */
        #modal-confirmar-limpiar:not([style*="display: none"]) {
            visibility: visible !important;
            opacity: 1 !important;
        }

        #sp-loading-overlay {
            position: fixed;
            inset: 0;
            background: rgba(255, 255, 255, 0.96);
            z-index: 200000;
            display: flex;
            justify-content: center;
            align-items: center;
            transition: opacity 0.3s ease, visibility 0.3s ease;
            opacity: 1;
            visibility: visible;
            pointer-events: all;
        }

        #sp-loading-overlay.is-hidden {
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
        }
    </style>
@endpush

@section('content')
<div class="supervisor-pedidos-container" style="position: relative;">

    <!-- Overlay de carga -->
    <div id="sp-loading-overlay" role="status" aria-live="polite" aria-label="Cargando pedidos">
        <div style="text-align: center;">
            <div class="spinner-border text-primary" role="status" style="width: 2.5rem; height: 2.5rem;">
                <span class="sr-only">Cargando...</span>
            </div>
            <p style="margin-top: 0.75rem; color: #555; font-size: 0.95rem; font-weight: 500;">Cargando pedidos...</p>
        </div>
    </div>

    <div id="supervisorPedidosIndexContent">

    @include('supervisor-pedidos.partials.tabla-ordenes')
    </div>

</div>

@include('supervisor-pedidos.partials.modales')



<!-- Modal Overlay y Wrapper para Detalles de Orden -->
<div id="dropdowns-container" style="position: fixed; top: 0; left: 0; z-index: 999999; pointer-events: none;"></div>

<button type="button" id="modal-overlay" aria-label="Cerrar modal" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.5); backdrop-filter: blur(4px); z-index: 9997; display: none; border: 0; padding: 0;" onclick="closeModalOverlay()"></button>

<div id="order-detail-modal-wrapper" style="width: 90%; max-width: 90vw; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 9998; pointer-events: auto; display: none; border-radius: 8px;">
    <x-orders-components.order-detail-modal />
</div>

<!-- Modal Wrapper para Detalles de Orden - LOGO -->
<div id="order-detail-modal-wrapper-logo" style="width: 90%; max-width: 90vw; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 9998; pointer-events: auto; display: none; border-radius: 8px;">
    <x-orders-components.order-detail-modal-logo />
</div>

<!-- Modal Comparar Pedido y Cotización -->
<x-supervisor-pedidos.modal-comparar-pedido />

<!-- Modal Seguimiento del Pedido -->
<x-orders-components.order-tracking-modal />

<!-- Modal para Selector de Recibos (desde asesores) -->
@include('components.modals.recibos-process-selector')

<!-- Modales de edición compartidos (encapsulan dependencias heredadas de asesores) -->
@include('supervisor-pedidos.partials.modales-edicion-compartidos')

@push('scripts')
    <script defer src="{{ asset('js/supervisor-pedidos/modales-acciones.js') }}?v={{ filemtime(public_path('js/supervisor-pedidos/modales-acciones.js')) }}"></script>
    <script defer src="{{ asset('js/supervisor-pedidos/lazy-editor-loader.js') }}?v={{ filemtime(public_path('js/supervisor-pedidos/lazy-editor-loader.js')) }}"></script>
    <script defer src="{{ asset('js/supervisor-pedidos/index.js') }}?v={{ filemtime(public_path('js/supervisor-pedidos/index.js')) }}"></script>
    

    <!--  SERVICIO DE ALMACENAMIENTO DE IMÁGENES (Requerido para agregar/eliminar imágenes) -->

    <!--  LAZY LOADERS: Cargan módulos bajo demanda (Requeridos para modal-editar-pedido) -->
    <script defer src="{{ asset('js/lazy-loaders/prenda-editor-preloader.js') }}"></script>
    <script defer src="{{ asset('js/lazy-loaders/prenda-editor-loader-modular.js') }}"></script>
    <script defer src="{{ asset('js/lazy-loaders/epp-manager-loader.js') }}"></script>

    <!-- Inicializador de servicios de imágenes -->

    <!-- Manejadores de procesos - Para edición de procesos desde supervisor -->

    <!-- Scripts para funcionalidad de asesores - Módulos Desacoplados -->
    <script defer src="/js/ordersjs/order-detail-modal-manager.js"></script>
    <!-- Scripts para Vista Previa en Vivo de Factura - Módulos Desacoplados -->
    <script defer src="{{ asset('js/modulos/invoice/ImageGalleryManager.js') }}?v={{ filemtime(public_path('js/modulos/invoice/ImageGalleryManager.js')) }}"></script>
    <script defer src="{{ asset('js/modulos/invoice/FormDataCaptureService.js') }}?v={{ filemtime(public_path('js/modulos/invoice/FormDataCaptureService.js')) }}"></script>
    <script defer src="{{ asset('js/modulos/invoice/InvoiceRenderer.js') }}?v={{ filemtime(public_path('js/modulos/invoice/InvoiceRenderer.js')) }}"></script>
    <script defer src="{{ asset('js/modulos/invoice/ModalManager.js') }}?v={{ filemtime(public_path('js/modulos/invoice/ModalManager.js')) }}"></script>
    <script defer src="{{ asset('js/modulos/invoice/InvoiceExportService.js') }}?v={{ filemtime(public_path('js/modulos/invoice/InvoiceExportService.js')) }}"></script>
    <script defer src="{{ asset('js/invoice-preview-live.js') }}"></script>
    <!-- Scripts para Vista de Factura desde Lista - Lazy Loading -->
    <script>
        window.__disableInvoicePreload = true;
    </script>
    <script defer src="{{ asset('js/modulos/invoice/InvoiceLazyLoader.js') }}?v={{ filemtime(public_path('js/modulos/invoice/InvoiceLazyLoader.js')) }}"></script>

    <!-- Scripts específicos de supervisor -->

    <!-- Scripts para Recibos/Procesos -->
    <script type="module" src="{{ asset('js/modulos/pedidos-recibos/loader.js') }}?v={{ filemtime(public_path('js/modulos/pedidos-recibos/loader.js')) }}"></script>

    <!-- Scripts para Modal de Seguimiento de Pedidos -->
    <script defer src="{{ asset('js/ordersjs/tracking-modal-utils.js') }}"></script>
    <script defer src="{{ asset('js/ordersjs/tracking/days-selector-handler.js') }}"></script>
    <script defer src="{{ asset('js/ordersjs/tracking/date-utils.js') }}"></script>
    <script defer src="{{ asset('js/ordersjs/tracking/modal-manager.js') }}"></script>
    <script defer src="{{ asset('js/ordersjs/tracking/days-selector.js') }}"></script>
    <script defer src="{{ asset('js/ordersjs/tracking/data-loader.js') }}"></script>
    <script defer type="module" src="{{ asset('js/ordersjs/tracking-modal-handler.js') }}"></script>
    <script defer src="{{ asset('js/ordersjs/tracking/ui-components.js') }}"></script>
    <script defer src="{{ asset('js/ordersjs/tracking/process-manager.js') }}"></script>
    <script defer src="{{ asset('js/ordersjs/tracking/area-cards.js') }}"></script>
    <script defer src="{{ asset('js/ordersjs/tracking/prendas-renderer.js') }}"></script>
    <script defer src="{{ asset('js/ordersjs/tracking/tracking-main.js') }}"></script>
    <script defer src="{{ asset('js/supervisor-pedidos/tracking-modal-init.js') }}?v={{ filemtime(public_path('js/supervisor-pedidos/tracking-modal-init.js')) }}"></script>

    <!-- Novedades, Galería y Toggle Factura -->
    <!-- Limpiar asignaciones y selección de pedidos -->
    <script defer src="{{ asset('js/supervisor-pedidos/limpiar-asignaciones.js') }}"></script>
    <script defer src="{{ asset('js/supervisor-pedidos/seleccion-pedidos.js') }}"></script>

    <!-- Realtime: suscripción WebSocket para actualizaciones en vivo -->
    <script defer src="{{ asset('js/supervisor-pedidos/realtime-supervisor.js') }}?v={{ filemtime(public_path('js/supervisor-pedidos/realtime-supervisor.js')) }}"></script>

@endpush

@endsection
