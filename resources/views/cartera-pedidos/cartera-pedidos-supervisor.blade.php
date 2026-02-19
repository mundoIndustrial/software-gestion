@extends('cartera-pedidos.layout')

@section('title', 'Cartera de Pedidos')
@section('page-title', 'Cartera de Pedidos')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/supervisor-pedidos/index.css') }}">
    <link rel="stylesheet" href="{{ asset('css/cartera-pedidos/cartera.css') }}">
    <style>
        .cartera-container {
            display: flex;
            flex-direction: column;
            height: 100%;
            gap: 16px;
            padding: 0;
            align-items: center;
            max-width: 1400px;
            margin: 0 auto;
            width: 100%;
        }

        .cartera-toolbar {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            flex-wrap: wrap;
            justify-content: center;
            width: 100%;
            max-width: 800px;
        }

        .cartera-toolbar input,
        .cartera-toolbar select {
            padding: 10px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 0.95rem;
            font-family: inherit;
        }

        .cartera-toolbar input:focus,
        .cartera-toolbar select:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .table-container-cartera {
            display: flex;
            flex-direction: column;
            flex: 1;
            overflow: hidden;
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 1000px;
        }

        .table-scroll-container-cartera {
            flex: 1;
            overflow-y: auto;
            overflow-x: auto;
            display: flex;
            flex-direction: column;
            width: 100%;
            min-height: 0;
        }

        .modern-table-cartera {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .table-body-cartera {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .table-row-cartera {
            display: flex;
            align-items: center;
            gap: 0;
            border-bottom: 1px solid #f3f4f6;
            font-size: 0.95rem;
            min-height: 60px;
            padding: 8px 4px;
        }
            padding: 14px 8px;
            border-bottom: 1px solid #f3f4f6;
            transition: background-color 0.2s;
        }

        .table-row-cartera:hover {
            background-color: #f9fafb;
        }

        .table-cell-cartera {
            display: flex;
            align-items: center;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            padding: 0;
        }

        .table-head {
            background: #3b82f6 !important;
            border-bottom: 2px solid #1e40af !important;
            padding: 0 !important;
            position: sticky !important;
            top: 0 !important;
            z-index: 10 !important;
            display: flex !important;
            width: 100% !important;
        }

        .table-header-row {
            display: flex !important;
            align-items: center !important;
            width: 100% !important;
            gap: 0 !important;
            padding: 14px 8px !important;
            background: #3b82f6 !important;
        }

        .table-header-cell-cartera {
            display: flex !important;
            align-items: center !important;
            gap: 8px !important;
            font-weight: 600 !important;
            color: white !important;
            cursor: pointer !important;
            transition: all 0.2s !important;
            user-select: none !important;
            box-sizing: border-box !important;
        }

        .table-header-cell-cartera:hover {
            opacity: 0.8;
        }

        .filter-icon {
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
            opacity: 0.7;
            transition: opacity 0.2s;
        }

        .filter-icon:hover {
            opacity: 1;
        }

        .pagination-container {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 20px 24px;
            border-top: 2px solid #e5e7eb;
            flex-wrap: wrap;
            background: linear-gradient(to right, #f9fafb, #ffffff);
            width: 100%;
            max-width: 1000px;
            border-radius: 0 0 8px 8px;
        }

        .pagination-btn {
            padding: 10px 12px;
            border: 1.5px solid #d1d5db;
            border-radius: 6px;
            background: white;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            min-width: 44px;
            height: 44px;
            color: #374151;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }

        .pagination-btn:hover:not(:disabled) {
            border-color: #3b82f6;
            background: #eff6ff;
            color: #3b82f6;
            box-shadow: 0 4px 6px rgba(59, 130, 246, 0.1);
            transform: translateY(-1px);
        }

        .pagination-btn:active:not(:disabled) {
            transform: translateY(0);
            box-shadow: 0 1px 2px rgba(59, 130, 246, 0.1);
        }

        .pagination-btn:disabled {
            opacity: 0.4;
            cursor: not-allowed;
            background: #f3f4f6;
            border-color: #e5e7eb;
        }

        .pagination-btn.active {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
            border-color: #2563eb;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }

        .pagination-info {
            font-size: 0.9rem;
            color: #374151;
            margin: 0 12px;
            white-space: nowrap;
            font-weight: 500;
            padding: 8px 12px;
            background: rgba(59, 130, 246, 0.05);
            border-radius: 6px;
            border-left: 3px solid #3b82f6;
        }

        .empty-state-cartera {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 60px 20px;
            text-align: center;
            color: #9ca3af;
            flex: 1;
        }

        .empty-state-cartera span {
            font-size: 3rem;
            opacity: 0.5;
            margin-bottom: 16px;
        }

        .empty-state-cartera p {
            margin: 0;
            font-size: 1.1rem;
        }

        .btn-action-cartera {
            padding: 6px 10px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 4px;
            font-size: 0.8rem;
            font-weight: 500;
            white-space: nowrap;
        }

        .btn-success-cartera {
            background: #d1fae5;
            color: #065f46;
        }

        .btn-success-cartera:hover {
            background: #a7f3d0;
        }

        .btn-danger-cartera {
            background: #fee2e2;
            color: #991b1b;
        }

        .btn-danger-cartera:hover {
            background: #fca5a5;
        }

        .btn-info-cartera {
            background: #dbeafe;
            color: #1e40af;
        }

        .btn-info-cartera:hover {
            background: #bfdbfe;
        }

        .loading-state-cartera {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            padding: 40px;
            color: #9ca3af;
            flex: 1;
        }

        .spinner {
            width: 24px;
            height: 24px;
            border: 2px solid #f3f4f6;
            border-top-color: #3b82f6;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        /* Modal Filtro */
        .modal-filter {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal-filter.open {
            display: flex;
        }

        .modal-filter-content {
            background: white;
            border-radius: 8px;
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);
            max-width: 500px;
            width: 90%;
            animation: slideUp 0.3s ease;
        }

        .modal-filter-header {
            padding: 16px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-filter-header h3 {
            margin: 0;
            color: #1f2937;
            font-size: 1.1rem;
        }

        .modal-filter-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #6b7280;
            transition: color 0.2s;
        }

        .modal-filter-close:hover {
            color: #1f2937;
        }

        .modal-filter-body {
            padding: 20px;
        }

        .modal-filter-body .form-group {
            margin-bottom: 16px;
        }

        .modal-filter-body label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #374151;
            font-size: 0.95rem;
        }

        .modal-filter-body input,
        .modal-filter-body select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 0.95rem;
            font-family: inherit;
            box-sizing: border-box;
        }

        .modal-filter-body input:focus,
        .modal-filter-body select:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .modal-filter-footer {
            padding: 16px;
            border-top: 1px solid #e5e7eb;
            display: flex;
            gap: 12px;
            justify-content: flex-end;
        }

        .modal-filter-footer button {
            padding: 10px 24px;
            border: none;
            border-radius: 6px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 0.95rem;
        }

        .btn-filter-cancel {
            background: #e5e7eb;
            color: #1f2937;
        }

        .btn-filter-cancel:hover {
            background: #d1d5db;
        }

        .btn-filter-apply {
            background: #3b82f6;
            color: white;
        }

        .btn-filter-apply:hover {
            background: #2563eb;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        @keyframes slideUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .cartera-toolbar {
                flex-direction: column;
                align-items: stretch;
            }

            .cartera-toolbar input,
            .cartera-toolbar select {
                width: 100%;
            }

            .table-row-cartera {
                font-size: 0.9rem;
            }
        }
    </style>
@endpush

@section('content')
<div class="cartera-container">
    <!-- CONTENEDOR DE NOTIFICACIONES -->
    <div id="notificacionesContainer"></div>

    <!-- TABLA DE PEDIDOS -->
    <div class="table-container">
        <div class="modern-table-wrapper">
            <div class="table-head" id="tableHead">
                <div style="display: flex; align-items: center; width: 100%; gap: 12px; padding: 14px 12px;">
                    @php
                        $columns = [
                            ['key' => 'acciones', 'label' => 'Acciones', 'flex' => '0 0 180px', 'justify' => 'flex-start'],
                            ['key' => 'cliente', 'label' => 'Cliente', 'flex' => '0 0 310px', 'justify' => 'center'],
                            ['key' => 'fecha', 'label' => 'Fecha', 'flex' => '0 0 150px', 'justify' => 'center'],
                        ];
                    @endphp

                    @foreach($columns as $column)
                        <div class="table-header-cell{{ $column['key'] === 'acciones' ? ' acciones-column' : '' }}{{ $column['key'] !== 'acciones' ? ' sortable' : '' }}" style="flex: {{ $column['flex'] }}; justify-content: {{ $column['justify'] }};" @if($column['key'] !== 'acciones') data-sort="{{ $column['key'] }}" @endif>
                            <div class="th-wrapper" style="display: flex; align-items: center; justify-content: space-between; gap: 0.5rem; width: 100%;">
                                <span class="header-text">{{ $column['label'] }}</span>
                                @if($column['key'] === 'cliente')
                                    <button type="button" class="btn-filter-column" title="Filtrar Cliente" onclick="abrirModalFiltro('cliente', event)">
                                        <span class="material-symbols-rounded">filter_alt</span>
                                        <div class="filter-badge"></div>
                                    </button>
                                @endif
                                @if($column['key'] === 'fecha')
                                    <button type="button" class="btn-filter-column" title="Filtrar Fecha" onclick="abrirModalFiltro('fecha', event)">
                                        <span class="material-symbols-rounded">filter_alt</span>
                                        <div class="filter-badge"></div>
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="table-scroll-container">
                <div class="modern-table">
                    <div class="table-body" id="tablaPedidosBody">
                        <!-- Pedidos aquí -->
                    </div>

                    <div id="emptyState" class="empty-state-cartera" style="display: none;">
                        <span class="material-symbols-rounded">shopping_cart</span>
                        <p>No hay pedidos pendientes de cartera</p>
                    </div>

                    <div id="loadingState" class="loading-state-cartera" style="display: none;">
                        <div class="spinner"></div>
                        <span>Cargando pedidos...</span>
                    </div>
                </div>
            </div>

            <div class="table-pagination" id="paginationContainer" style="display: none;">
                <button class="pagination-btn" id="btnFirstPage" title="Primera página">
                    <span class="material-symbols-rounded" style="font-size: 1.2rem;">first_page</span>
                </button>
                <button class="pagination-btn" id="btnPrevPage" title="Página anterior">
                    <span class="material-symbols-rounded" style="font-size: 1.2rem;">chevron_left</span>
                </button>
                <span class="pagination-info">
                    <span class="material-symbols-rounded" style="font-size: 1rem; vertical-align: middle; margin-right: 4px;">article</span>
                    Pág. <span id="currentPage">1</span> de <span id="totalPages">1</span>
                    (<span id="showingFrom">0</span>-<span id="showingTo">0</span> de <span id="totalRecords">0</span>)
                </span>
                <button class="pagination-btn" id="btnNextPage" title="Próxima página">
                    <span class="material-symbols-rounded" style="font-size: 1.2rem;">chevron_right</span>
                </button>
                <button class="pagination-btn" id="btnLastPage" title="Última página">
                    <span class="material-symbols-rounded" style="font-size: 1.2rem;">last_page</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL APROBACIÓN -->
<div id="modalAprobacion" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Aprobar Pedido</h3>
        </div>
        <div class="modal-body">
            <p>¿Está seguro de que desea <strong>aprobar</strong> este pedido?</p>
        </div>
        <div class="modal-footer" style="display: flex; gap: 12px; justify-content: flex-end;">
            <button type="button" class="btn" onclick="cerrarModalAprobacion()" style="padding: 10px 24px; border: none; border-radius: 6px; font-weight: 500; cursor: pointer; background-color: #e5e7eb; color: #1f2937; transition: background-color 0.2s;">Cancelar</button>
            <button type="button" id="btnConfirmarAprobacion" class="btn" style="padding: 10px 24px; border: none; border-radius: 6px; font-weight: 500; cursor: pointer; background-color: #10b981; color: white; transition: background-color 0.2s;">Aprobar</button>
        </div>
    </div>
</div>

<!-- MODAL RECHAZO -->
<div id="modalRechazo" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Rechazar Pedido</h3>
        </div>
        <form id="formRechazo" onsubmit="confirmarRechazo(event)">
            <div class="modal-body">
                <div class="form-group" style="margin: 16px 0;">
                    <label class="form-label" style="display: block; margin-bottom: 8px; font-weight: 500; color: #374151;">Motivo del rechazo *</label>
                    <textarea id="motivoRechazo" class="form-textarea" placeholder="Explique el motivo..." required style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; font-family: inherit; font-size: 0.95rem; resize: vertical; min-height: 120px; box-sizing: border-box;"></textarea>
                </div>
            </div>
            <div class="modal-footer" style="display: flex; gap: 12px; justify-content: flex-end; padding-top: 16px; border-top: 1px solid #e5e7eb;">
                <button type="button" class="btn" onclick="cerrarModalRechazo()" style="padding: 10px 24px; border: none; border-radius: 6px; font-weight: 500; cursor: pointer; background-color: #e5e7eb; color: #1f2937; transition: background-color 0.2s;">Cancelar</button>
                <button type="submit" id="btnConfirmarRechazo" class="btn" style="padding: 10px 24px; border: none; border-radius: 6px; font-weight: 500; cursor: pointer; background-color: #ef4444; color: white; transition: background-color 0.2s;">Rechazar</button>
            </div>
        </form>
    </div>
</div>

<!-- Incluir modales compartidos -->
@include('cartera-pedidos.partials.modales-filtro')

@endsection

@push('scripts')
<script>
    // Flag para indicar que estamos en cartera
    const isCartera = true;
    
    // Ocultar elemento "Pendientes Logo" del sidebar (no se usa en cartera)
    document.addEventListener('DOMContentLoaded', function() {
        const logoMenuItem = document.querySelector('a[href*="tipo=logo"]')?.closest('.menu-item');
        if (logoMenuItem) {
            logoMenuItem.style.display = 'none';
        }
        
        // Cerrar modales de filtro al hacer click fuera
        const modalFiltroCliente = document.getElementById('modalFiltroCliente');
        const modalFiltroFecha = document.getElementById('modalFiltroFecha');
        
        if (modalFiltroCliente) {
            modalFiltroCliente.addEventListener('click', function(e) {
                if (e.target === this) {
                    cerrarModalFiltro('cliente');
                }
            });
        }
        
        if (modalFiltroFecha) {
            modalFiltroFecha.addEventListener('click', function(e) {
                if (e.target === this) {
                    cerrarModalFiltro('fecha');
                }
            });
        }
    });
    
    // Deshabilitar funciones de supervisores que no aplican a cartera
    function cargarNotificacionesPendientes() {
        // Deshabilitado para cartera
        console.log(' Notificaciones deshabilitadas en cartera');
    }
    
    function cargarContadorOrdenesPendientes() {
        // Deshabilitado para cartera
        console.log(' Contador de órdenes deshabilitado en cartera');
    }
</script>
<!-- Scripts para ver facturas (desde asesores) - Módulos Desacoplados -->
<script src="{{ asset('js/modulos/invoice/ImageGalleryManager.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/modulos/invoice/FormDataCaptureService.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/modulos/invoice/InvoiceRenderer.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/modulos/invoice/ModalManager.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/modulos/invoice/InvoiceExportService.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/invoice-preview-live.js') }}"></script>
<!-- Scripts de Filtros Compartidos -->
<script src="{{ asset('js/cartera-pedidos/cartera-filtros-compartidos.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/cartera-pedidos/app.js') }}"></script>
@endpush
