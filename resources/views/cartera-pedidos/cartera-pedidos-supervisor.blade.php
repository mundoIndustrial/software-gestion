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
            padding: 0 !important;
            transition: all 0.2s !important;
            user-select: none !important;
        }

        .table-header-cell-cartera:hover {
            opacity: 0.8;
        }

        .table-header-cell-cartera.sortable::after {
            content: '⇅';
            font-size: 0.8em;
            opacity: 0.6;
            margin-left: 6px;
        }

        .table-header-cell-cartera.sort-asc::after {
            content: '↑';
            opacity: 1;
        }

        .table-header-cell-cartera.sort-desc::after {
            content: '↓';
            opacity: 1;
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
            padding: 16px;
            border-top: 1px solid #f3f4f6;
            flex-wrap: wrap;
        }

        .pagination-btn {
            padding: 8px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            background: white;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 0.9rem;
        }

        .pagination-btn:hover:not(:disabled) {
            border-color: #3b82f6;
            background: #f0f9ff;
            color: #3b82f6;
        }

        .pagination-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .pagination-btn.active {
            background: #3b82f6;
            color: white;
            border-color: #3b82f6;
        }

        .pagination-info {
            font-size: 0.9rem;
            color: #6b7280;
            margin: 0 8px;
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

    <!-- TOOLBAR DE FILTROS -->
    <div class="cartera-toolbar">
        <input 
            type="text" 
            id="searchInput" 
            placeholder="🔍 Buscar por cliente, número de pedido..." 
            style="flex: 1; min-width: 250px;"
        />
        <select id="perPageSelect" style="width: 150px;">
            <option value="10">10 por página</option>
            <option value="15" selected>15 por página</option>
            <option value="25">25 por página</option>
            <option value="50">50 por página</option>
        </select>
        <button 
            id="btnRefreshPedidos" 
            style="padding: 10px 20px; background: #3b82f6; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 500; transition: background 0.2s;"
            onmouseover="this.style.background='#2563eb'"
            onmouseout="this.style.background='#3b82f6'"
        >
            🔄 Actualizar
        </button>
    </div>

    <!-- TABLA DE PEDIDOS -->
    <div class="table-container-cartera">
        <div class="table-scroll-container-cartera">
            <!-- ENCABEZADOS CON FILTROS -->
            <div class="table-head">
                <div class="table-header-row">
                    <div class="table-header-cell-cartera" style="flex: 0 0 140px; justify-content: center;">
                        <span>Acciones</span>
                    </div>
                    <div class="table-header-cell-cartera sortable" style="flex: 0 0 280px;" data-sort="cliente">
                        <span>Cliente</span>
                        <span class="filter-icon" title="Filtrar cliente" onclick="abrirModalFiltro('cliente', event)">
                            <span class="material-symbols-rounded" style="font-size: 1.1rem;">filter_alt</span>
                        </span>
                    </div>
                    <div class="table-header-cell-cartera sortable" style="flex: 0 0 150px;" data-sort="fecha">
                        <span>Fecha</span>
                        <span class="filter-icon" title="Filtrar fecha" onclick="abrirModalFiltro('fecha', event)">
                            <span class="material-symbols-rounded" style="font-size: 1.1rem;">filter_alt</span>
                        </span>
                    </div>
                </div>
            </div>

            <!-- CUERPO DE LA TABLA -->
            <div class="modern-table-cartera">
                <div class="table-body-cartera" id="tablaPedidosBody">
                    <!-- Pedidos aquí -->
                </div>

                <!-- ESTADO VACÍO -->
                <div id="emptyState" class="empty-state-cartera" style="display: none;">
                    <span class="material-symbols-rounded">shopping_cart</span>
                    <p>No hay pedidos pendientes de cartera</p>
                </div>

                <!-- ESTADO DE CARGA -->
                <div id="loadingState" class="loading-state-cartera" style="display: none;">
                    <div class="spinner"></div>
                    <span>Cargando pedidos...</span>
                </div>
            </div>
        </div>

        <!-- PAGINACIÓN -->
        <div class="pagination-container" id="paginationContainer" style="display: none;">
            <button class="pagination-btn" id="btnFirstPage">Primera</button>
            <button class="pagination-btn" id="btnPrevPage">← Anterior</button>
            <span class="pagination-info">
                Página <span id="currentPage">1</span> de <span id="totalPages">1</span> 
                (Mostrando <span id="showingFrom">0</span>-<span id="showingTo">0</span> de <span id="totalRecords">0</span>)
            </span>
            <button class="pagination-btn" id="btnNextPage">Siguiente →</button>
            <button class="pagination-btn" id="btnLastPage">Última</button>
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

<!-- MODAL FILTRO CLIENTE -->
<div id="modalFiltroCliente" class="modal-filter">
    <div class="modal-filter-content">
        <div class="modal-filter-header">
            <h3>Filtrar por Cliente</h3>
            <button type="button" class="modal-filter-close" onclick="cerrarModalFiltro('cliente')">&times;</button>
        </div>
        <div class="modal-filter-body">
            <div class="form-group">
                <label>Nombre del cliente:</label>
                <input 
                    type="text" 
                    id="filtroClienteInput" 
                    placeholder="Escriba el nombre del cliente..." 
                />
            </div>
        </div>
        <div class="modal-filter-footer">
            <button type="button" class="modal-filter-footer button btn-filter-cancel" onclick="cerrarModalFiltro('cliente')">Cancelar</button>
            <button type="button" class="modal-filter-footer button btn-filter-apply" onclick="aplicarFiltroCliente()">Aplicar Filtro</button>
        </div>
    </div>
</div>

<!-- MODAL FILTRO FECHA -->
<div id="modalFiltroFecha" class="modal-filter">
    <div class="modal-filter-content">
        <div class="modal-filter-header">
            <h3>Filtrar por Fecha</h3>
            <button type="button" class="modal-filter-close" onclick="cerrarModalFiltro('fecha')">&times;</button>
        </div>
        <div class="modal-filter-body">
            <div class="form-group">
                <label>Desde:</label>
                <input 
                    type="date" 
                    id="filtroFechaDesde"
                />
            </div>
            <div class="form-group">
                <label>Hasta:</label>
                <input 
                    type="date" 
                    id="filtroFechaHasta"
                />
            </div>
        </div>
        <div class="modal-filter-footer">
            <button type="button" class="modal-filter-footer button btn-filter-cancel" onclick="cerrarModalFiltro('fecha')">Cancelar</button>
            <button type="button" class="modal-filter-footer button btn-filter-apply" onclick="aplicarFiltroFecha()">Aplicar Filtro</button>
        </div>
    </div>
</div>
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
        console.log('ℹ️ Notificaciones deshabilitadas en cartera');
    }
    
    function cargarContadorOrdenesPendientes() {
        // Deshabilitado para cartera
        console.log('ℹ️ Contador de órdenes deshabilitado en cartera');
    }
</script>
<!-- Scripts para ver facturas (desde asesores) -->
<script src="{{ asset('js/invoice-preview-live.js') }}"></script>
<script src="{{ asset('js/cartera-pedidos/app.js') }}"></script>
@endpush
