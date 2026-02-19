@extends('cartera-pedidos.layout')

@section('title', 'Cartera - Pedidos Aprobados')
@section('page-title', 'Pedidos Aprobados por Cartera')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/supervisor-pedidos/index.css') }}">
    <link rel="stylesheet" href="{{ asset('css/cartera-pedidos/cartera.css') }}">
    <style>
        .cartera-container {
            display: flex;
            flex-direction: column;
            flex: 1;
            gap: 16px;
            padding: 20px;
            align-items: center;
            max-width: 1400px;
            margin: 0 auto;
            width: 100%;
            min-height: 0;
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
            background: linear-gradient(135deg, var(--primary) 0%, #2b7ec9 100%) !important;
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
            background: transparent !important;
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

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        /* Estado badges */
        .estado-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.025em;
        }

        .estado-aprobado {
            background-color: #d1fae5;
            color: #065f46;
            border: 1px solid #34d399;
        }
    </style>
@endpush

@section('content')
<!-- TABLA DE PEDIDOS - Integrada con el layout existente -->
<div class="table-container-cartera">
    <div class="table-scroll-container-cartera">
        <!-- ENCABEZADOS -->
        <div class="table-head">
            <div class="table-header-row">
                <div class="table-header-cell-cartera" style="flex: 0 0 180px; justify-content: center; border-right: 6px solid rgba(255,255,255,0.4); box-sizing: border-box; padding: 0 12px;">
                    <span>Acciones</span>
                </div>
                <div class="table-header-cell-cartera" style="flex: 0 0 250px; padding: 0 14px 0 32px; box-sizing: border-box; display: flex; align-items: center; justify-content: space-between;">
                    <span>Cliente</span>
                    <button type="button" class="btn-filter-column" title="Filtrar Cliente" onclick="abrirModalFiltro('cliente', event)">
                        <span class="material-symbols-rounded" style="font-size: 1.1rem; cursor: pointer; opacity: 0.7; transition: opacity 0.2s;">filter_alt</span>
                    </button>
                </div>
                <div class="table-header-cell-cartera" style="flex: 0 0 120px; padding: 0 10px; box-sizing: border-box; display: flex; align-items: center; justify-content: space-between;">
                    <span>N° Pedido</span>
                    <button type="button" class="btn-filter-column" title="Filtrar N° Pedido" onclick="abrirModalFiltro('numero', event)">
                        <span class="material-symbols-rounded" style="font-size: 1.1rem; cursor: pointer; opacity: 0.7; transition: opacity 0.2s;">filter_alt</span>
                    </button>
                </div>
                <div class="table-header-cell-cartera" style="flex: 0 0 150px; padding: 0 10px; box-sizing: border-box; display: flex; align-items: center; justify-content: space-between;">
                    <span>Fecha Aprobación</span>
                    <button type="button" class="btn-filter-column" title="Filtrar Fecha" onclick="abrirModalFiltro('fecha', event)">
                        <span class="material-symbols-rounded" style="font-size: 1.1rem; cursor: pointer; opacity: 0.7; transition: opacity 0.2s;">filter_alt</span>
                    </button>
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
                <span class="material-symbols-rounded">check_circle</span>
                <p>No hay pedidos aprobados por cartera</p>
            </div>

            <!-- ESTADO DE CARGA -->
            <div id="loadingState" class="loading-state-cartera" style="display: none;">
                <div class="loading-spinner"></div>
                <p>Cargando pedidos...</p>
            </div>
        </div>
    </div>
    
    <!-- PAGINACIÓN -->
    <div class="pagination-container">
        <button class="pagination-btn" id="btnPrevPage" onclick="goToPage(currentPage - 1)">
            <span class="material-symbols-rounded">chevron_left</span>
        </button>
        
        <span class="pagination-info" id="paginationInfo">Mostrando 0 pedidos</span>
        
        <button class="pagination-btn" id="btnNextPage" onclick="goToPage(currentPage + 1)">
            <span class="material-symbols-rounded">chevron_right</span>
        </button>
    </div>
</div>

<!-- Incluir modales compartidos -->
@include('cartera-pedidos.partials.modales-filtro')

<!-- Modal Ver Pedido -->
<div id="modalVerPedido" class="modal-overlay" style="display: none;">
    <div class="modal-content modal-ver-pedido">
        <div class="modal-header">
            <div class="header-icon info">
                <span class="material-symbols-rounded">receipt</span>
            </div>
            <div class="header-content">
                <h2 class="modal-title">Detalles del Pedido</h2>
                <p class="modal-subtitle">Información completa del pedido seleccionado</p>
            </div>
            <button type="button" class="modal-close" onclick="cerrarModalVerPedido()">&times;</button>
        </div>
        <div class="modal-body">
            <div id="pedidoDetalles">
                <!-- Los detalles del pedido se cargarán aquí -->
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="cerrarModalVerPedido()">Cerrar</button>
        </div>
    </div>
</div>
@endsection

<!-- Toast Notifications -->
<div id="toastContainer" class="toast-container"></div>

@push('scripts')
    <script>
        console.log('%c CARRERA APROBADOS - SCRIPTS SECTION', 'color: #10b981; font-size: 14px; font-weight: bold;');
    </script>
@endpush
