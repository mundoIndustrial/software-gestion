@extends('supervisor-pedidos.layout')

@section('title', 'Supervisión de Pedidos')
@section('page-title', 'Supervisión de Pedidos')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/supervisor-pedidos/index.css') }}">
    <!-- CSS para modal-agregar-prenda-nueva y formularios de edición -->
    <link rel="stylesheet" href="{{ asset('css/crear-pedido.css') }}">
    <link rel="stylesheet" href="{{ asset('css/crear-pedido-editable.css') }}">
    <link rel="stylesheet" href="{{ asset('css/form-modal-consistency.css') }}">
    <link rel="stylesheet" href="{{ asset('css/swal-z-index-fix.css') }}">
    <link rel="stylesheet" href="{{ asset('css/componentes/prendas.css') }}">
    <link rel="stylesheet" href="{{ asset('css/modales/modal-exito-pedido.css') }}">
    <link rel="stylesheet" href="{{ asset('css/modulos/epp-modal.css') }}">

@endpush

@section('content')
<div class="supervisor-pedidos-container">

    <div id="supervisorPedidosIndexContent">

    @include('supervisor-pedidos.partials.tabla-ordenes')
    </div>

</div>

@include('supervisor-pedidos.partials.modales')



<!-- Modal Overlay y Wrapper para Detalles de Orden -->
<div id="dropdowns-container" style="position: fixed; top: 0; left: 0; z-index: 999999; pointer-events: none;"></div>

<div id="modal-overlay" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.5); backdrop-filter: blur(4px); z-index: 9997; display: none;" onclick="closeModalOverlay()"></div>

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

<!-- Modal Editar Pedido (desde asesores) - Componente completo para edición de pedidos -->
@include('asesores.pedidos.components.modal-editar-pedido')

<!-- Componentes de módulos de edición (desde asesores) -->
@include('asesores.pedidos.components.modal-prendas-lista')
@include('asesores.pedidos.components.modal-agregar-prenda')
@include('asesores.pedidos.modals.modal-agregar-prenda-nueva')
@include('asesores.pedidos.components.modal-editar-prenda')
<!-- Modal Agregar EPP (mismo modal que en creación) -->
@include('asesores.pedidos.modals.modal-agregar-editar-epp')

@include('asesores.pedidos.components.modal-editar-epp')

<!-- Modal para Seleccionar Tallas -->
@include('asesores.pedidos.modals.modal-seleccionar-tallas')

<!-- Modal Selector de Modo de Proceso -->
@include('asesores.pedidos.modals.modal-selector-modo-proceso')
@include('asesores.pedidos.modals.modal-proceso-por-tallas')

<!-- Modal para Editar Procesos Genéricos -->
@include('asesores.pedidos.modals.modal-proceso-generico')

<!-- Modal para Confirmar Eliminación de Imagen de Proceso -->
@include('asesores.pedidos.modals.modal-confirmar-eliminar-imagen-proceso')

@push('scripts')
    <!--  MODALES DE ACCIONES (CARGADO TEMPRANO - Aprobación, Anulación, Ocultación) -->
    <script src="{{ asset('js/supervisor-pedidos/modales-acciones.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/supervisor-pedidos/index.js') }}?v={{ time() }}"></script>

    <!--  SERVICIOS CENTRALIZADOS (Requeridos para modal-editar-pedido) -->
    <script src="{{ asset('js/utilidades/validation-service.js') }}"></script>
    <script src="{{ asset('js/utilidades/ui-modal-service.js') }}"></script>
    <script src="{{ asset('js/utilidades/deletion-service.js') }}"></script>
    <script src="{{ asset('js/utilidades/galeria-service.js') }}"></script>

    <!--  SERVICIO DE ALMACENAMIENTO DE IMÁGENES (Requerido para agregar/eliminar imágenes) -->
    <script src="{{ asset('js/modulos/crear-pedido/fotos/image-storage-service.js') }}"></script>

    <!--  LAZY LOADERS: Cargan módulos bajo demanda (Requeridos para modal-editar-pedido) -->
    <script src="{{ asset('js/lazy-loaders/prenda-editor-preloader.js') }}"></script>
    <script src="{{ asset('js/lazy-loaders/prenda-editor-loader-modular.js') }}"></script>
    <script src="{{ asset('js/lazy-loaders/epp-manager-loader.js') }}"></script>
    <script defer src="{{ asset('js/componentes/epp-agregar-pedido.js') }}"></script>

    <!-- Scripts para edición de prendas desde lista de pedidos (requeridos por editarPrendaDePedido) -->
    <script defer src="{{ asset('js/modulos/crear-pedido/prendas/prenda-editor.js') }}"></script>
    <script defer src="{{ asset('js/modulos/crear-pedido/tallas/gestion-tallas.js') }}"></script>
    <script defer src="{{ asset('js/modulos/crear-pedido/telas/telas-module/manejo-imagenes.js') }}"></script>
    <script defer src="{{ asset('js/componentes/prenda-form-collector.js') }}"></script>
    <script defer src="{{ asset('js/componentes/prenda-editor-pedidos-adapter.js') }}"></script>
    <script defer src="{{ asset('js/componentes/prenda-agregar-pedido.js') }}"></script>

    <!-- Inicializador de servicios de imágenes -->
    <script src="{{ asset('js/modulos/crear-pedido/inicializadores/init-storage-servicios.js') }}"></script>

    <!-- Manejadores de procesos - Para edición de procesos desde supervisor -->
    <script src="{{ asset('js/modulos/crear-pedido/procesos/manejadores-procesos-prenda.js') }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/procesos/gestor-modal-proceso-generico.js') }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/procesos/selector-modo-proceso.js') }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/procesos/gestor-modal-proceso-por-tallas.js') }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/procesos/extension-editor-tallas-multiproducto.js') }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/procesos/extension-guardar-datos-tallas-extendida.js') }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/procesos/renderizador-tarjetas-procesos.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/componentes/procesos-imagenes-storage.js') }}"></script>
    <script src="{{ asset('js/componentes/manejo-imagenes-proceso.js') }}"></script>
    <script src="{{ asset('js/componentes/manejador-imagen-proceso-con-indice.js') }}"></script>

    <!-- Scripts para funcionalidad de asesores - Módulos Desacoplados -->
    <script src="{{ asset('js/asesores/pedidos-dropdown-simple.js') }}"></script>
    <script src="{{ asset('js/asesores/observaciones-despacho.js') }}"></script>
    <script src="{{ asset('js/asesores/pedidos-modal-edit.js') }}"></script>
    <!-- Scripts para Vista Previa en Vivo de Factura - Módulos Desacoplados -->
    <script src="{{ asset('js/modulos/invoice/ImageGalleryManager.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/modulos/invoice/FormDataCaptureService.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/modulos/invoice/InvoiceRenderer.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/modulos/invoice/ModalManager.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/modulos/invoice/InvoiceExportService.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/invoice-preview-live.js') }}"></script>
    <!-- Scripts para Vista de Factura desde Lista - Lazy Loading -->
    <script src="{{ asset('js/modulos/invoice/InvoiceLazyLoader.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/asesores/invoice-from-list.js') }}"></script>
    <script src="{{ asset('js/asesores/receipt-manager.js') }}"></script>
    <script src="{{ asset('js/asesores/pedidos-detail-modal.js') }}"></script>
    <script src="{{ asset('js/asesores/pedidos-anular.js') }}"></script>

    <!-- Scripts específicos de supervisor -->
    <script src="{{ asset('js/supervisor-pedidos/supervisor-pedidos-detail-modal.js') }}"></script>
    <script src="{{ asset('js/ordersjs/tracking-modal-utils.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/ordersjs/tracking-modal-handler.js') }}"></script>

    <!-- Script para abrir el modal de seguimiento -->
    <script src="{{ asset('js/supervisor-pedidos/tracking-modal-init.js') }}?v={{ time() }}"></script>


    <!-- Scripts para Recibos/Procesos -->
    <script type="module" src="{{ asset('js/modulos/pedidos-recibos/loader.js') }}"></script>

    <!-- Novedades, Galería y Toggle Factura -->
    <script src="{{ asset('js/supervisor-pedidos/novedades-galeria.js') }}?v={{ time() }}"></script>
    <!-- Limpiar asignaciones y selección de pedidos -->
    <script src="{{ asset('js/supervisor-pedidos/limpiar-asignaciones.js') }}"></script>
    <script src="{{ asset('js/supervisor-pedidos/seleccion-pedidos.js') }}"></script>

    <!-- Realtime: suscripción WebSocket para actualizaciones en vivo -->
    @auth
    <script defer src="{{ asset('js/supervisor-pedidos/realtime-supervisor.js') }}"></script>
    @endauth

@endpush

@endsection