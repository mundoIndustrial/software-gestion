@extends('asesores.layout')

@section('title', 'Mis Pedidos')
@section('page-title', 'Mis Pedidos')

@section('content')
    <!-- Agregar referencia a FontAwesome para iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css"
        integrity="sha512-1ycn6IcaQQ40/MKBW2W4Rhis/DbILU74C1vSrLJxCq57o941Ym01SwNsOMqvEBFlcgUa6xLiPY/NS5R+E6ztJQ=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="{{ asset('css/orders styles/modern-table.css') }}">
    <link rel="stylesheet" href="{{ asset('css/orders styles/dropdown-styles.css') }}">
    <link rel="stylesheet" href="{{ asset('css/viewButtonDropdown.css') }}">

    <style>
        /* ========================================
           VARIABLES DE COLORES - PALETA ERP
           ======================================== */
        :root {
            /* Colores Primarios */
            --primary-color: #2563eb;
            --primary-dark: #1d4ed8;
            --primary-light: #3b82f6;
            --secondary-color: #0ea5e9;
            
            /* Estados */
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --info-color: #0066cc;
            
            /* Grises profesionales */
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;
            
            /* Bordes */
            --border-light: #e5e7eb;
            --border-dark: #d1d5db;
        }
        
        /* Estilos para modales de orden */
        [x-cloak] { display: none; }
        
        /* Container modales - dejar que Alpine lo controle */
        .order-detail-modal {
            /* Vacío - Alpine controla el display */
        }
        
        /* Asegurar que los modales sean visibles */
        x-modal, [x-data*="modal"] {
            display: block;
        }
        
        /* Overlay modales */
        .modal-overlay {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            bottom: 0 !important;
            background: rgba(0, 0, 0, 0.5) !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            z-index: 9999 !important;
        }
    </style>

    <script>
        console.log('📋 ASESORES PEDIDOS - Cargando CSS');
        console.log('CSS modern-table.css:', document.querySelector('link[href*="modern-table.css"]') ? 'CARGADO' : 'NO CARGADO');
        console.log('CSS dropdown-styles.css:', document.querySelector('link[href*="dropdown-styles.css"]') ? 'CARGADO' : 'NO CARGADO');
        console.log('CSS viewButtonDropdown.css:', document.querySelector('link[href*="viewButtonDropdown.css"]') ? 'CARGADO' : 'NO CARGADO');
    </script>

    <style>
        /* ========================================
           ESTILOS DE TABLA PROFESIONAL ERP
           ======================================== */
        
        /* TABLA PRINCIPAL */
        .modern-table {
            width: 100% !important;
            border-collapse: collapse !important;
            background: white !important;
            border: none !important;
            font-size: 13px !important;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08) !important;
        }
        
        /* ENCABEZADO */
        .modern-table thead {
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary-color) 100%) !important;
            color: white !important;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        
        .modern-table thead th {
            padding: 14px 12px !important;
            text-align: left !important;
            font-weight: 700 !important;
            border: none !important;
            background: transparent !important;
            color: white !important;
            white-space: nowrap !important;
            font-size: 12px !important;
            letter-spacing: 0.5px !important;
            text-transform: uppercase !important;
        }
        
        /* BODY DE TABLA */
        .modern-table tbody td {
            padding: 12px !important;
            border: none !important;
            border-bottom: 1px solid var(--border-light) !important;
            background: white !important;
            font-size: 13px !important;
            color: var(--gray-700) !important;
        }
        
        /* FILAS */
        .modern-table tbody tr {
            transition: all 0.2s ease !important;
        }
        
        .modern-table tbody tr:hover {
            background: var(--gray-50) !important;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05) !important;
        }
        
        /* ALTERNANCIA DE FILAS */
        .modern-table tbody tr:nth-child(even) {
            background: var(--gray-50) !important;
        }
        
        .modern-table tbody tr:nth-child(even):hover {
            background: var(--gray-100) !important;
        }
        
        /* ÚLTIMA FILA */
        .modern-table tbody tr:last-child td {
            border-bottom: 2px solid var(--border-dark) !important;
        }
        
        /* ========================================
           ESTRUCTURA Y LAYOUT
           ======================================== */
        
        /* CONTAINER PRINCIPAL */
        .table-container {
            background: var(--gray-100) !important;
            padding: 24px !important;
            border-radius: 12px !important;
        }
        
        /* HEADER DE TABLA */
        .table-header {
            display: flex !important;
            justify-content: space-between !important;
            align-items: center !important;
            margin-bottom: 24px !important;
            background: white !important;
            padding: 20px 24px !important;
            border-radius: 10px !important;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1) !important;
            gap: 20px !important;
        }
        
        .table-title {
            margin: 0 !important;
            font-size: 1.75rem !important;
            color: var(--gray-900) !important;
            display: flex !important;
            align-items: center !important;
            gap: 12px !important;
            font-weight: 800 !important;
            letter-spacing: -0.5px !important;
            min-width: fit-content !important;
        }
        
        .table-title i {
            color: var(--primary-color) !important;
            font-size: 1.75rem !important;
        }
        
        /* BUSCADOR */
        .search-container {
            flex: 1 !important;
            min-width: 250px !important;
        }
        
        .search-input-wrapper {
            display: flex !important;
            align-items: center !important;
            background: var(--gray-50) !important;
            border: 2px solid var(--border-light) !important;
            border-radius: 8px !important;
            padding: 10px 16px !important;
            transition: all 0.3s ease !important;
        }
        
        .search-input-wrapper:focus-within {
            border-color: var(--primary-color) !important;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1) !important;
        }
        
        .search-icon {
            color: var(--gray-400) !important;
            margin-right: 12px !important;
            font-size: 14px !important;
        }
        
        .search-input {
            border: none !important;
            background: transparent !important;
            flex: 1 !important;
            outline: none !important;
            font-size: 14px !important;
            width: 100% !important;
            color: var(--gray-700) !important;
        }
        
        .search-input::placeholder {
            color: var(--gray-400) !important;
        }
        
        /* ACCIONES */
        .table-actions {
            display: flex !important;
            gap: 12px !important;
            min-width: fit-content !important;
        }
        
        .btn {
            padding: 10px 20px !important;
            border-radius: 8px !important;
            text-decoration: none !important;
            font-weight: 600 !important;
            cursor: pointer !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 8px !important;
            transition: all 0.2s ease !important;
            border: none !important;
            font-size: 13px !important;
        }
        
        .btn-primary {
            background: var(--primary-color) !important;
            color: white !important;
        }
        
        .btn-primary:hover {
            background: var(--primary-dark) !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3) !important;
        }
        
        /* WRAPPER */
        .modern-table-wrapper {
            background: white !important;
            border-radius: 10px !important;
            overflow: hidden !important;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1) !important;
        }
        
        .table-scroll-container {
            overflow-x: auto !important;
        }
        
        /* ANCHO DE COLUMNAS */
        .acciones-column {
            width: 140px !important;
            min-width: 140px !important;
        }
        
        .modern-table th:nth-child(2),
        .modern-table td:nth-child(2) {
            width: 100px !important;
            min-width: 100px !important;
        }
        
        .modern-table th:nth-child(3),
        .modern-table td:nth-child(3) {
            width: 90px !important;
            min-width: 90px !important;
        }
        
        .modern-table th:nth-child(4),
        .modern-table td:nth-child(4) {
            width: 110px !important;
            min-width: 110px !important;
        }
        
        /* PAGINATION */
        .table-pagination {
            background: var(--gray-50) !important;
            padding: 16px 24px !important;
            display: flex !important;
            justify-content: space-between !important;
            align-items: center !important;
            border-top: 1px solid var(--border-light) !important;
        }
        
        .pagination-info {
            color: var(--gray-600) !important;
            font-size: 13px !important;
            font-weight: 500 !important;
        }
        
        /* ========================================
           ESTILOS DE CELDAS Y CONTENIDO
           ======================================== */
        
        .cell-content {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        /* BADGES Y ETIQUETAS */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
            white-space: nowrap;
        }
        
        .badge-success {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
            border: 1px solid rgba(16, 185, 129, 0.3);
        }
        
        .badge-warning {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning-color);
            border: 1px solid rgba(245, 158, 11, 0.3);
        }
        
        .badge-danger {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger-color);
            border: 1px solid rgba(239, 68, 68, 0.3);
        }
        
        .badge-info {
            background: rgba(37, 99, 235, 0.1);
            color: var(--primary-color);
            border: 1px solid rgba(37, 99, 235, 0.3);
        }
        
        /* ESTADO SIN RESULTADOS */
        .no-results {
            text-align: center !important;
            padding: 40px 20px !important;
            color: var(--gray-500) !important;
            font-size: 14px !important;
        }
        
        .no-results i {
            font-size: 2.5rem !important;
            margin-bottom: 12px !important;
            color: var(--gray-300) !important;
            display: block !important;
        }
        
        /* ========================================
           EFECTOS Y TRANSICIONES
           ======================================== */
        
        .action-btn {
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        /* RESPONSIVE */
        @media (max-width: 1200px) {
            .table-header {
                flex-wrap: wrap;
                gap: 12px;
            }
            
            .search-container {
                flex: 1 1 100%;
                min-width: 100%;
            }
            
            .table-title {
                font-size: 1.5rem;
            }
        }
        
        @media (max-width: 768px) {
            .table-container {
                padding: 12px;
            }
            
            .table-header {
                padding: 12px 16px;
                margin-bottom: 16px;
            }
            
            .table-title {
                font-size: 1.25rem;
            }
            
            .search-input-wrapper {
                padding: 8px 12px;
            }
            
            .modern-table {
                font-size: 11px;
            }
            
            .modern-table thead th {
                padding: 10px 8px;
                font-size: 11px;
            }
            
            .modern-table tbody td {
                padding: 10px 8px;
                font-size: 11px;
            }
            
            .acciones-column {
                width: 100px !important;
            }
        }
        
        @media (max-width: 480px) {
            .table-title {
                font-size: 1.1rem;
            }
            
            .table-title i {
                font-size: 1.3rem;
            }
            
            .search-input {
                font-size: 12px;
            }
            
            .action-btn {
                font-size: 9px !important;
                padding: 6px 10px !important;
                height: 32px !important;
            }
        }
    </style>

    <div class="table-container">
        <div class="table-header" id="tableHeader">
            <h1 class="table-title">
                <i class="fas fa-list"></i>
                Mis Pedidos de Producción
            </h1>

            <div class="search-container">
                <div class="search-input-wrapper">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="buscarOrden" placeholder="Buscar por pedido o cliente..." class="search-input">
                </div>
            </div>

            <!-- llamada de botones de la  tabla -->
            <div class="table-actions"></div>
        </div>

    <div class="modern-table-wrapper">
        <div class="table-scroll-container">
            <table id="tablaOrdenes" class="modern-table">
                <thead class="table-head">
                    @if($pedidos->isNotEmpty())
                        <tr>
                            <th class="table-header-cell acciones-column">
                                <div class="header-content">
                                    <span class="header-text">Acciones</span>
                                </div>
                            </th>
                            <th class="table-header-cell">
                                <div class="header-content">
                                    <span class="header-text">Estado</span>
                                </div>
                            </th>
                            <th class="table-header-cell">
                                <div class="header-content">
                                    <span class="header-text">Área</span>
                                </div>
                            </th>
                            <th class="table-header-cell">
                                <div class="header-content">
                                    <span class="header-text">Día De Entrega</span>
                                </div>
                            </th>
                            <th class="table-header-cell">
                                <div class="header-content">
                                    <span class="header-text">Pedido</span>
                                </div>
                            </th>
                            <th class="table-header-cell">
                                <div class="header-content">
                                    <span class="header-text">Cliente</span>
                                </div>
                            </th>
                            <th class="table-header-cell">
                                <div class="header-content">
                                    <span class="header-text">Descripción</span>
                                </div>
                            </th>
                            <th class="table-header-cell">
                                <div class="header-content">
                                    <span class="header-text">Cantidad</span>
                                </div>
                            </th>
                            <th class="table-header-cell">
                                <div class="header-content">
                                    <span class="header-text">Forma De Pago</span>
                                </div>
                            </th>
                            <th class="table-header-cell">
                                <div class="header-content">
                                    <span class="header-text">Fecha Creación</span>
                                </div>
                            </th>
                            <th class="table-header-cell">
                                <div class="header-content">
                                    <span class="header-text">Fecha Estimada</span>
                                </div>
                            </th>
                        </tr>
                    @endif
                </thead>
                <tbody id="tablaOrdenesBody" class="table-body">
                    @if($pedidos->isEmpty())
                        <tr class="table-row">
                            <td colspan="11" class="no-results" style="text-align: center; padding: 20px; color: #6c757d;">
                                No hay resultados que coincidan con los filtros aplicados.
                            </td>
                        </tr>
                    @else
                        @foreach($pedidos as $pedido)
                            <tr class="table-row" data-order-id="{{ $pedido->numero_pedido }}">
                                <td class="table-cell acciones-column" style="min-width: 220px !important;">
                                    <div class="cell-content" style="display: flex; gap: 8px; flex-wrap: nowrap; align-items: center; justify-content: flex-start; padding: 4px 0;">
                                        <button class="action-btn detail-btn" onclick="verFactura({{ $pedido->numero_pedido }})"
                                            title="Ver Factura"
                                            style="
                                                background: linear-gradient(135deg, var(--success-color) 0%, #059669 100%);
                                                color: white;
                                                border: none;
                                                padding: 8px 12px;
                                                border-radius: 6px;
                                                cursor: pointer;
                                                font-size: 11px;
                                                font-weight: 700;
                                                flex: 1;
                                                min-width: 65px;
                                                height: 36px;
                                                text-align: center;
                                                display: flex;
                                                align-items: center;
                                                justify-content: center;
                                                white-space: nowrap;
                                                transition: all 0.2s ease;
                                                box-shadow: 0 2px 4px rgba(16, 185, 129, 0.2);
                                            "
                                            onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 8px rgba(16, 185, 129, 0.3)'"
                                            onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 4px rgba(16, 185, 129, 0.2)'"
                                        >
                                            <i class="fas fa-eye"></i> Ver
                                        </button>
                                        <button class="action-btn detail-btn" onclick="verSeguimiento({{ $pedido->numero_pedido }})"
                                            title="Ver Seguimiento"
                                            style="
                                                background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
                                                color: white;
                                                border: none;
                                                padding: 8px 12px;
                                                border-radius: 6px;
                                                cursor: pointer;
                                                font-size: 11px;
                                                font-weight: 700;
                                                flex: 1;
                                                min-width: 65px;
                                                height: 36px;
                                                text-align: center;
                                                display: flex;
                                                align-items: center;
                                                justify-content: center;
                                                white-space: nowrap;
                                                transition: all 0.2s ease;
                                                box-shadow: 0 2px 4px rgba(37, 99, 235, 0.2);
                                            "
                                            onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 8px rgba(37, 99, 235, 0.3)'"
                                            onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 4px rgba(37, 99, 235, 0.2)'"
                                        >
                                            <i class="fas fa-tasks"></i> Seguimiento
                                        </button>
                                    </div>
                                </td>
                                <td class="table-cell">
                                    <div class="cell-content">
                                        <span style="
                                            background: linear-gradient(135deg, var(--warning-color) 0%, #d97706 100%);
                                            color: white;
                                            padding: 6px 12px;
                                            border-radius: 6px;
                                            font-size: 12px;
                                            font-weight: 700;
                                            display: inline-flex;
                                            align-items: center;
                                            gap: 6px;
                                            white-space: nowrap;
                                        ">
                                            <i class="fas fa-circle-notch" style="font-size: 8px;"></i>
                                            {{ $pedido->estado ?? 'Sin estado' }}
                                        </span>
                                    </div>
                                </td>
                                <td class="table-cell">
                                    <div class="cell-content">
                                        <span style="color: var(--gray-700); font-weight: 500;">{{ $pedido->getAreaActual() }}</span>
                                    </div>
                                </td>
                                <td class="table-cell">
                                    <div class="cell-content">
                                        <span style="color: var(--gray-700); font-weight: 500;">{{ $pedido->dia_de_entrega ?? '-' }}</span>
                                    </div>
                                </td>
                                <td class="table-cell">
                                    <div class="cell-content">
                                        <span style="color: var(--primary-color); font-weight: 700; font-size: 13px;">#{{ $pedido->numero_pedido }}</span>
                                    </div>
                                </td>
                                <td class="table-cell">
                                    <div class="cell-content">
                                        <span style="color: var(--gray-700); font-weight: 600;">{{ $pedido->cliente }}</span>
                                    </div>
                                </td>
                                <td class="table-cell">
                                    <div class="cell-content">
                                        <span style="color: var(--gray-600);">
                                            @if($pedido->prendas->first())
                                                {{ $pedido->prendas->first()->nombre_prenda }}
                                            @else
                                                <span style="color: var(--gray-400);">-</span>
                                            @endif
                                        </span>
                                    </div>
                                </td>
                                <td class="table-cell">
                                    <div class="cell-content">
                                        <span style="color: var(--gray-700); font-weight: 700;">
                                            @if($pedido->prendas->first())
                                                {{ $pedido->prendas->first()->cantidad }} <small style="color: var(--gray-500);">und</small>
                                            @else
                                                <span style="color: var(--gray-400);">-</span>
                                            @endif
                                        </span>
                                    </div>
                                </td>
                                <td class="table-cell">
                                    <div class="cell-content">
                                        <span style="color: var(--gray-700); font-weight: 500;">{{ $pedido->forma_de_pago ?? '-' }}</span>
                                    </div>
                                </td>
                                <td class="table-cell">
                                    <div class="cell-content">
                                        <span style="color: var(--gray-600); font-size: 12px;">{{ $pedido->fecha_de_creacion_de_orden ? $pedido->fecha_de_creacion_de_orden->format('d/m/Y') : '-' }}</span>
                                    </div>
                                </td>
                                <td class="table-cell">
                                    <div class="cell-content">
                                        <span style="color: var(--gray-600); font-size: 12px;">{{ $pedido->fecha_estimada_de_entrega ? $pedido->fecha_estimada_de_entrega->format('d/m/Y') : '-' }}</span>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>

        <div class="table-pagination" id="tablePagination">
            <div class="pagination-info">
                <span id="paginationInfo">Mostrando {{ $pedidos->firstItem() ?? 0 }}-{{ $pedidos->lastItem() ?? 0 }} de {{ $pedidos->total() }} registros</span>
            </div>
            <div class="pagination-controls" id="paginationControls">
                @if($pedidos->hasPages())
                    {{ $pedidos->links() }}
                @endif
            </div>
        </div>
    </div>
</div>

<script>
// Función para obtener datos de la orden y abrir modal de detalle
async function verFactura(numeroPedido) {
    console.log('🔵 verFactura INICIADA - numeroPedido:', numeroPedido);
    
    try {
        console.log('📡 Haciendo fetch a /registros/' + numeroPedido);
        const response = await fetch(`/registros/${numeroPedido}`);
        
        console.log('✅ Response status:', response.status);
        console.log('✅ Response ok:', response.ok);
        
        if (!response.ok) throw new Error('Error fetching order');
        
        const order = await response.json();
        console.log('📦 Orden obtenida:', order);
        
        // Actualizar la orden actual para navegación
        setCurrentOrder(numeroPedido);
        console.log('✅ Orden actual establecida:', numeroPedido);
        
        // Cargar imágenes de la orden
        if (typeof loadOrderImages === 'function') {
            console.log('🖼️ Cargando imágenes...');
            loadOrderImages(numeroPedido);
        } else {
            console.log('⚠️ loadOrderImages no existe');
        }
        
        const fechaCreacion = new Date(order.fecha_de_creacion_de_orden);
        const day = fechaCreacion.getDate().toString().padStart(2, '0');
        const month = fechaCreacion.toLocaleDateString('es-ES', { month: 'short' }).toUpperCase();
        const year = fechaCreacion.getFullYear().toString().slice(-2);
        
        console.log('📅 Fecha:', day + '/' + month + '/' + year);
        
        const orderDate = document.getElementById('order-date');
        console.log('🔍 order-date element:', orderDate ? '✅ ENCONTRADO' : '❌ NO ENCONTRADO');
        
        if (orderDate) {
            const dayBox = orderDate.querySelector('.day-box');
            const monthBox = orderDate.querySelector('.month-box');
            const yearBox = orderDate.querySelector('.year-box');
            console.log('📦 dayBox:', dayBox ? '✅' : '❌', 'monthBox:', monthBox ? '✅' : '❌', 'yearBox:', yearBox ? '✅' : '❌');
            
            if (dayBox) dayBox.textContent = day;
            if (monthBox) monthBox.textContent = month;
            if (yearBox) yearBox.textContent = year;
        }
        
        const pedidoDiv = document.getElementById('order-pedido');
        console.log('🔍 order-pedido element:', pedidoDiv ? '✅ ENCONTRADO' : '❌ NO ENCONTRADO');
        if (pedidoDiv) {
            pedidoDiv.textContent = `N° ${numeroPedido}`;
        }
        
        const asesoraValue = document.getElementById('asesora-value');
        console.log('🔍 asesora-value element:', asesoraValue ? '✅ ENCONTRADO' : '❌ NO ENCONTRADO');
        if (asesoraValue) {
            asesoraValue.textContent = order.asesora || '';
        }
        
        const formaPagoValue = document.getElementById('forma-pago-value');
        console.log('🔍 forma-pago-value element:', formaPagoValue ? '✅ ENCONTRADO' : '❌ NO ENCONTRADO');
        if (formaPagoValue) {
            formaPagoValue.textContent = order.forma_de_pago || '';
        }
        
        const clienteValue = document.getElementById('cliente-value');
        console.log('🔍 cliente-value element:', clienteValue ? '✅ ENCONTRADO' : '❌ NO ENCONTRADO');
        if (clienteValue) {
            clienteValue.textContent = order.cliente || '';
        }

        const encargadoValue = document.getElementById('encargado-value');
        console.log('🔍 encargado-value element:', encargadoValue ? '✅ ENCONTRADO' : '❌ NO ENCONTRADO');
        if (encargadoValue) {
            encargadoValue.textContent = order.encargado_orden || '';
        }

        const prendasEntregadasValue = document.getElementById('prendas-entregadas-value');
        console.log('🔍 prendas-entregadas-value element:', prendasEntregadasValue ? '✅ ENCONTRADO' : '❌ NO ENCONTRADO');
        if (prendasEntregadasValue) {
            const totalEntregado = order.total_entregado || 0;
            const totalCantidad = order.total_cantidad || 0;
            prendasEntregadasValue.textContent = `${totalEntregado} de ${totalCantidad}`;
        }
        
        // Abrir el modal usando evento Alpine
        console.log('🎭 Intentando abrir modal order-detail...');
        console.log('⏱️ Esperando 100ms para que Alpine esté listo...');
        
        setTimeout(() => {
            console.log('🔊 Disparando evento open-modal con detail: order-detail');
            console.log('📍 window.dispatchEvent:', typeof window.dispatchEvent);
            
            const event = new CustomEvent('open-modal', { detail: 'order-detail' });
            console.log('📌 Evento creado:', event);
            
            window.dispatchEvent(event);
            console.log('✅ Evento disparado');
        }, 100);
    } catch (error) {
        console.error('❌ Error en verFactura:', error);
        console.error('📋 Stack:', error.stack);
        alert('Error al cargar los detalles de la orden: ' + error.message);
    }
}

// Función para abrir modal de seguimiento
function verSeguimiento(pedidoId) {
    console.log('🔵 verSeguimiento INICIADA - pedidoId:', pedidoId);
    
    try {
        // Establecer la orden actual
        setCurrentOrder(pedidoId);
        console.log('✅ Orden actual establecida:', pedidoId);
        
        // Cargar datos de seguimiento
        if (typeof loadOrderImages === 'function') {
            console.log('🖼️ Cargando imágenes para seguimiento...');
            loadOrderImages(pedidoId);
        } else {
            console.log('⚠️ loadOrderImages no existe');
        }
        
        // Abrir el modal usando evento Alpine
        console.log('🎭 Intentando abrir modal order-tracking...');
        console.log('⏱️ Esperando 100ms para que Alpine esté listo...');
        
        setTimeout(() => {
            console.log('🔊 Disparando evento open-modal con detail: order-tracking');
            
            const event = new CustomEvent('open-modal', { detail: 'order-tracking' });
            console.log('📌 Evento creado:', event);
            
            window.dispatchEvent(event);
            console.log('✅ Evento disparado');
        }, 100);
    } catch (error) {
        console.error('❌ Error en verSeguimiento:', error);
        console.error('📋 Stack:', error.stack);
        alert('Error al abrir el seguimiento: ' + error.message);
    }
}

// Función auxiliar para establecer la orden actual
function setCurrentOrder(pedido) {
    console.log('💾 setCurrentOrder llamada con:', pedido);
    window.currentOrder = pedido;
    localStorage.setItem('currentOrder', pedido);
    console.log('✅ currentOrder guardada en window y localStorage');
}
</script>

<!-- Modal de Detalle de Orden (igual al de producción) -->
<x-orders-components.order-detail-modal />

<!-- Modal de Seguimiento del Pedido (igual al de producción) -->
<x-orders-components.order-tracking-modal />

<script src="{{ asset('js/orders js/order-navigation.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/orderTracking.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/orders-scripts/image-gallery-zoom.js') }}?v={{ time() }}"></script>


@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/asesores/pedidos.css') }}">
<style>
    /* NO SOBRESCRIBIR ESTILOS DEL MODAL - USAR LOS DEL COMPONENTE */
    /* El modal de asesores usa exactamente el mismo componente y CSS que producción */

    .modal-overlay .tab-button:hover {
        color: var(--primary-color);
        background: rgba(37, 99, 235, 0.05);
    }

    .modal-overlay .tab-button.active {
        color: var(--primary-color);
        border-bottom-color: var(--primary-color);
    }

    /* CONTENIDO TABS */
    .modal-overlay .tab-content {
        display: none;
        flex-direction: column;
        flex: 0 1 auto;
        overflow: visible;
    }

    .modal-overlay .tab-content.active {
        display: flex;
    }

    /* TAB BODY */
    .modal-overlay .tab-body {
        padding: 1.5rem;
        flex: 0 1 auto;
        background: white;
    }

    .modal-overlay .tab-header {
        padding: 0.75rem 1.5rem 0 1.5rem;
        border-bottom: 1px solid var(--border-light);
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-shrink: 0;
    }

    /* FORM GROUPS */
    .modal-overlay .form-group {
        margin-bottom: 1rem;
    }

    .modal-overlay .form-group label {
        display: block;
        font-weight: 700;
        margin-bottom: 0.5rem;
        color: var(--gray-700);
        font-size: 0.95rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .modal-overlay .form-control {
        width: 100%;
        padding: 0.75rem;
        border: 2px solid var(--border-light);
        border-radius: 6px;
        font-size: 0.95rem;
        font-family: inherit;
        transition: all 0.3s ease;
        background: var(--gray-50);
    }

    .modal-overlay .form-control:focus {
        outline: none;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        background: white;
    }

    /* TODO EN MAYÚSCULA EN EL MODAL DE PEDIDOS */
    #modalCrearPedido .form-control,
    #modalCrearPedido input[type="text"],
    #modalCrearPedido input[type="number"],
    #modalCrearPedido textarea,
    #modalCrearPedido select {
        text-transform: uppercase;
    }

    /* TAMBIÉN EL RESUMEN EN MAYÚSCULA */
    #resumenCliente,
    #resumenFormaPago {
        text-transform: uppercase;
    }

    /* BOTÓN AGREGAR PRODUCTO */
    .btn-add-product {
        padding: 0.75rem 1.25rem;
        background: var(--primary-color);
        color: white;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 700;
        font-size: 0.95rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.3s ease;
        box-shadow: 0 2px 4px rgba(37, 99, 235, 0.2);
    }

    .btn-add-product:hover {
        background: var(--primary-dark);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
    }

    /* PRODUCTOS MODAL */
    .productos-modal-list {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .producto-modal-item {
        background: white;
        border: 2px solid var(--border-light);
        border-radius: 8px;
        overflow: hidden;
        margin-bottom: 1.5rem;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        transition: all 0.3s ease;
    }

    .producto-modal-item:hover {
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.1);
        border-color: var(--primary-color);
    }

    .producto-modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem 1.5rem;
        background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
        color: white;
        border-bottom: 2px solid var(--primary-dark);
    }

    .prenda-numero {
        margin: 0;
        font-size: 1.1rem;
        font-weight: 700;
        color: white;
    }

    .numero-prenda {
        font-weight: 700;
        color: white;
    }

    .btn-remove {
        width: 36px;
        height: 36px;
        padding: 0;
        background: rgba(255, 255, 255, 0.2);
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        transition: all 0.3s ease;
        flex-shrink: 0;
    }

    .btn-remove:hover {
        background: var(--danger-color);
    }

    .producto-modal-body {
        padding: 1.5rem;
    }

    .form-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .form-row:last-child {
        margin-bottom: 0;
    }

    .form-col {
        display: flex;
        flex-direction: column;
    }

    .form-col label {
        font-weight: 700;
        margin-bottom: 0.5rem;
        color: var(--gray-700);
        font-size: 0.95rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .producto-modal-body .form-control {
        padding: 0.75rem;
        font-size: 0.95rem;
        border: 2px solid var(--border-light);
        border-radius: 6px;
        transition: all 0.3s ease;
        background: var(--gray-50);
    }

    .producto-modal-body .form-control:focus {
        outline: none;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        background: white;
    }

    /* RESUMEN */
    .resumen-card {
        background: linear-gradient(135deg, rgba(37, 99, 235, 0.05), rgba(14, 165, 233, 0.05));
        border-left: 4px solid var(--primary-color);
        border-radius: 8px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .resumen-card h4 {
        margin: 0 0 1rem 0;
        color: var(--primary-color);
        font-size: 1.1rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .resumen-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.75rem 0;
        font-weight: 600;
        border-bottom: 1px solid rgba(37, 99, 235, 0.2);
    }

    .resumen-item:last-child {
        border-bottom: none;
    }

    .resumen-item span {
        color: var(--gray-600);
    }

    .resumen-item strong {
        color: var(--primary-color);
        font-size: 1.2rem;
    }

    .resumen-info {
        background: linear-gradient(135deg, rgba(245, 158, 11, 0.1), rgba(252, 191, 73, 0.1));
        border-left: 4px solid var(--warning-color);
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 1.5rem;
        display: flex;
        gap: 1rem;
        align-items: flex-start;
    }

    .resumen-info i {
        color: var(--warning-color);
        font-size: 1.2rem;
        margin-top: 0.2rem;
        flex-shrink: 0;
    }

    .resumen-info p {
        margin: 0;
        color: var(--gray-700);
        font-size: 0.95rem;
        line-height: 1.5;
    }

    .resumen-detalles {
        background: white;
        border: 2px solid var(--border-light);
        border-radius: 8px;
        padding: 1.5rem;
    }

    .detalle-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.75rem 0;
        border-bottom: 1px solid var(--gray-100);
    }

    .detalle-item:last-child {
        border-bottom: none;
    }

    .detalle-item span {
        color: var(--gray-600);
        font-weight: 500;
    }

    .detalle-item strong {
        color: var(--gray-800);
        font-weight: 700;
    }

    /* ACCIONES TAB */
    .tab-actions {
        display: flex;
        gap: 1rem;
        padding: 1rem 1.5rem;
        border-top: 1px solid var(--border-light);
        background: var(--gray-50);
        flex-shrink: 0;
    }

    .tab-actions .btn {
        flex: 1;
        min-height: 40px;
        padding: 0.75rem 1.2rem;
        border: none;
        border-radius: 6px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        font-size: 0.95rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .btn-secondary {
        background: var(--gray-200);
        color: var(--gray-700);
    }

    .btn-secondary:hover {
        background: var(--gray-300);
    }

    .btn-primary {
        background: var(--primary-color);
        color: white;
        box-shadow: 0 2px 4px rgba(37, 99, 235, 0.2);
    }

    .btn-primary:hover {
        background: var(--primary-dark);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
    }

    /* RESPONSIVE - TABLETS Y PANTALLAS MEDIANAS */
    @media (max-width: 1024px) {
        .modal-header {
            padding: 1rem 1.5rem;
            min-height: 60px;
        }

        .modal-header h2 {
            font-size: 1.3rem;
        }

        .tabs-navigation {
            padding: 0 1.5rem;
        }

        .tab-button {
            padding: 0.8rem 1rem;
            font-size: 0.9rem;
        }

        .tab-body {
            padding: 1.5rem;
        }

        .tab-header {
            padding: 0.75rem 1.5rem 0 1.5rem;
        }

        .tab-actions {
            padding: 1rem 1.5rem;
            gap: 0.75rem;
        }

        .producto-modal-body {
            grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
        }

        .resumen-card {
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .resumen-detalles {
            padding: 1rem;
        }
    }

    /* RESPONSIVE - MOBILE */
    @media (max-width: 768px) {
        .modal-header {
            padding: 1rem;
            flex-direction: column;
            gap: 0.5rem;
            align-items: flex-start;
            position: relative;
            min-height: auto;
        }

        .modal-header h2 {
            font-size: 1.1rem;
            flex: 1;
        }

        .btn-close {
            position: absolute;
            top: 1rem;
            right: 1rem;
            width: 32px;
            height: 32px;
            font-size: 1rem;
        }

        .tabs-navigation {
            padding: 0 0.5rem;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .tab-button {
            padding: 0.6rem 0.75rem;
            font-size: 0.8rem;
            min-width: 70px;
        }

        .tab-button i {
            font-size: 0.9rem;
        }

        .tab-button span {
            display: none;
        }

        .tab-button.active span,
        .tab-button:first-child span {
            display: inline;
        }

        .tab-body {
            padding: 1rem;
        }

        .tab-header {
            padding: 0.5rem 1rem 0 1rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            font-size: 0.85rem;
            margin-bottom: 0.3rem;
        }

        .form-control {
            padding: 0.6rem;
            font-size: 0.9rem;
        }

        .btn-add-product {
            width: 100%;
            padding: 0.65rem 1rem;
            font-size: 0.85rem;
        }

        .producto-modal-item {
            padding: 0.75rem;
        }

        .producto-modal-header {
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .btn-remove {
            width: 32px;
            height: 32px;
            font-size: 0.9rem;
        }

        .producto-modal-body {
            grid-template-columns: repeat(auto-fit, minmax(90px, 1fr));
            gap: 0.5rem;
        }

        .producto-modal-body input,
        .producto-modal-body select {
            padding: 0.5rem;
            font-size: 0.8rem;
        }

        .resumen-card {
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .resumen-card h4 {
            margin: 0 0 0.75rem 0;
            font-size: 1rem;
        }

        .resumen-item {
            padding: 0.5rem 0;
            font-size: 0.9rem;
        }

        .resumen-item strong {
            font-size: 1rem;
        }

        .resumen-info {
            padding: 0.75rem;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }

        .resumen-info i {
            font-size: 1rem;
        }

        .resumen-info p {
            font-size: 0.85rem;
        }

        .resumen-detalles {
            padding: 1rem;
        }

        .detalle-item {
            padding: 0.5rem 0;
            font-size: 0.9rem;
        }

        .tab-actions {
            padding: 1rem;
            gap: 0.5rem;
            flex-direction: column;
        }

        .tab-actions .btn {
            padding: 0.65rem 1rem;
            font-size: 0.9rem;
            min-height: 40px;
        }
    }

    /* RESPONSIVE - MOBILE PEQUEÑO */
    @media (max-width: 480px) {
        .modal-header h2 {
            font-size: 1rem;
        }

        .tabs-navigation {
            padding: 0;
        }

        .tab-button {
            padding: 0.5rem 0.5rem;
            font-size: 0.7rem;
            min-width: 60px;
        }

        .tab-body {
            padding: 0.75rem;
        }

        .tab-header {
            padding: 0.5rem 0.75rem 0 0.75rem;
        }

        .form-group {
            margin-bottom: 0.75rem;
        }

        .form-group label {
            font-size: 0.8rem;
        }

        .form-control {
            padding: 0.5rem;
            font-size: 0.85rem;
        }

        .producto-modal-body {
            grid-template-columns: 1fr;
        }

        .tab-actions {
            padding: 0.75rem;
        }

        .tab-actions .btn {
            padding: 0.6rem;
            font-size: 0.8rem;
        }
    }
</style>
@endpush

@push('scripts')
<script src="{{ asset('js/asesores/pedidos-list.js') }}"></script>
<script>
let productoCountModal = 0;
let siguientePedido = 1;

/**
 * Mostrar tab específico
 */
function mostrarTabModal(tabName) {
    // Ocultar todos los tabs
    const tabs = document.querySelectorAll('.tab-content');
    tabs.forEach(tab => tab.classList.remove('active'));

    // Mostrar el tab seleccionado
    const tabSeleccionado = document.getElementById(`tab-${tabName}`);
    if (tabSeleccionado) {
        tabSeleccionado.classList.add('active');
    }

    // Actualizar botones activos en navegación
    const buttons = document.querySelectorAll('.tab-button');
    buttons.forEach(btn => btn.classList.remove('active'));

    // Marcar como activo el botón del tab actual
    event?.target?.classList.add('active');
    if (tabName === 'info-general') {
        buttons[0].classList.add('active');
    } else if (tabName === 'productos') {
        buttons[1].classList.add('active');
    } else if (tabName === 'resumen') {
        buttons[2].classList.add('active');
        actualizarResumenModal();
    }
}

/**
 * Obtener siguiente pedido
 */
async function obtenerSiguientePedido() {
    try {
        const response = await fetch("{{ route('asesores.next-pedido') }}");
        const data = await response.json();
        siguientePedido = data.siguiente_pedido;
        document.getElementById('nuevoPedido').value = siguientePedido;
    } catch (error) {
        console.error('Error:', error);
        document.getElementById('nuevoPedido').value = 1;
    }
}

/**
 * Abrir modal crear pedido
 */
function abrirModalCrearPedido() {
    const modal = document.getElementById('modalCrearPedido');
    modal.style.display = 'flex';
    modal.addEventListener('click', cerrarAlClickAfuera);
    
    // Evitar scroll del body cuando el modal está abierto
    document.body.style.overflow = 'hidden';
    document.body.style.overflowX = 'hidden';
    
    productoCountModal = 0;
    
    // NO obtener el siguiente pedido aquí - se asignará al crear
    document.getElementById('nuevoPedido').value = '';
    
    document.getElementById('productosModalContainer').innerHTML = '';
    agregarProductoModal();
    
    // Mostrar primer tab
    mostrarTabModal('info-general');
    
    // Focus al cliente
    setTimeout(() => {
        document.getElementById('nuevoCliente').focus();
    }, 100);
}

/**
 * Configurar autocomplete para forma de pago
 */
document.addEventListener('DOMContentLoaded', function() {
    const inputFormaPago = document.getElementById('nuevoFormaPago');
    const datalist = document.getElementById('formasPagoList');
    const formasPagoStandard = ['CRÉDITO', 'CONTADO', '50/50', 'ANTICIPO'];
    let formasPersonalizadas = [];

    // Cargar formas personalizadas del localStorage
    const formasGuardadas = localStorage.getItem('formasPagoPersonalizadas');
    if (formasGuardadas) {
        formasPersonalizadas = JSON.parse(formasGuardadas);
        actualizarDatalist();
    }

    // Actualizar datalist con todas las opciones
    function actualizarDatalist() {
        datalist.innerHTML = '';
        const todasLasFormas = [...new Set([...formasPagoStandard, ...formasPersonalizadas])];
        todasLasFormas.forEach(forma => {
            const option = document.createElement('option');
            option.value = forma;
            datalist.appendChild(option);
        });
    }

    // Convertir a mayúscula mientras se escribe
    inputFormaPago?.addEventListener('input', function() {
        this.value = this.value.toUpperCase();
    });

    // Al seleccionar del datalist o escribir, solo usar lo que existe
    inputFormaPago?.addEventListener('change', function() {
        const valor = this.value.trim().toUpperCase();
        const todasLasFormas = [...formasPagoStandard, ...formasPersonalizadas];
        
        // Si no existe la forma exacta, preguntar
        const existe = todasLasFormas.some(forma => forma === valor);
        
        if (!existe && valor) {
            // Mostrar sugerencia para crear
            Swal.fire({
                title: '¿Crear nueva forma de pago?',
                text: `"${valor}" no existe. ¿Deseas agregarla?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#0066cc',
                cancelButtonColor: '#f0f0f0',
                confirmButtonText: 'Sí, crear',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    formasPersonalizadas.push(valor);
                    localStorage.setItem('formasPagoPersonalizadas', JSON.stringify(formasPersonalizadas));
                    actualizarDatalist();
                    inputFormaPago.value = valor;
                } else {
                    inputFormaPago.value = '';
                }
            });
        }
    });
});

/**
 * Cerrar al hacer clic fuera del modal
 */
function cerrarAlClickAfuera(event) {
    const modal = document.getElementById('modalCrearPedido');
    const container = document.querySelector('.modal-container-tabs');
    
    if (event.target === modal) {
        cerrarModalCrearPedido();
    }
}

/**
 * Cerrar modal
 */
function cerrarModalCrearPedido() {
    const modal = document.getElementById('modalCrearPedido');
    modal.style.display = 'none';
    modal.removeEventListener('click', cerrarAlClickAfuera);
    document.getElementById('formCrearPedidoModal').reset();
    
    // Restaurar el scroll del body
    document.body.style.overflow = 'auto';
    document.body.style.overflowX = 'auto';
}

/**
 * Agregar producto al modal
 */
function agregarProductoModal() {
    const container = document.getElementById('productosModalContainer');
    const template = document.getElementById('productoModalTemplate');
    const clone = template.content.cloneNode(true);

    // Actualizar número de prenda
    const numeroPrenda = container.querySelectorAll('.producto-modal-item').length + 1;
    clone.querySelector('.numero-prenda').textContent = numeroPrenda;

    // Actualizar índices
    const inputs = clone.querySelectorAll('input, select');
    inputs.forEach(input => {
        const name = input.getAttribute('name');
        if (name) {
            input.setAttribute('name', name.replace('[0]', `[${productoCountModal}]`));
        }
        if (input.classList.contains('producto-modal-cantidad')) {
            input.addEventListener('change', actualizarResumenModal);
        }
    });

    container.appendChild(clone);
    productoCountModal++;
    actualizarResumenModal();
}

/**
 * Eliminar producto modal
 */
function eliminarProductoModal(button) {
    button.closest('.producto-modal-item').remove();
    actualizarResumenModal();
}

/**
 * Actualizar resumen modal
 */
function actualizarResumenModal() {
    const productos = document.querySelectorAll('.producto-modal-item');
    let cantidadTotal = 0;

    productos.forEach(producto => {
        const cantidadInput = producto.querySelector('.producto-modal-cantidad');
        if (cantidadInput && cantidadInput.value) {
            cantidadTotal += parseInt(cantidadInput.value) || 0;
        }
    });

    document.getElementById('resumenTotalProductos').textContent = productos.length;
    document.getElementById('resumenCantidadTotal').textContent = cantidadTotal;

    // Actualizar también los datos del resumen en el tab final
    const cliente = document.getElementById('nuevoCliente').value || '-';
    const formaPago = document.getElementById('nuevoFormaPago').value || '-';

    document.getElementById('resumenCliente').textContent = cliente;
    document.getElementById('resumenFormaPago').textContent = formaPago;
    document.getElementById('resumenEstado').textContent = 'No iniciado'; // El estado siempre es "No iniciado"
}

/**
 * Guardar pedido modal como borrador (SIN ID AÚN)
 */
function guardarPedidoModal() {
    const form = document.getElementById('formCrearPedidoModal');
    
    if (!form.checkValidity()) {
        Swal.fire({
            title: 'Validación',
            text: 'Por favor completa todos los campos requeridos',
            icon: 'warning',
            confirmButtonColor: '#0066cc'
        });
        return;
    }

    const formData = new FormData(form);
    // NO incluir el ID de pedido - se asignará después
    formData.delete('pedido');
    
    Swal.fire({
        title: '¿Guardar pedido?',
        text: 'El pedido se guardará como borrador.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#0066cc',
        cancelButtonColor: '#f0f0f0',
        confirmButtonText: 'Sí, guardar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch("{{ route('asesores.pedidos.store') }}", {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    cerrarModalCrearPedido();
                    
                    // Mostrar Toast con opción de crear
                    mostrarToastCrear(data.borrador_id);
                } else {
                    Swal.fire({
                        title: 'Error',
                        text: data.message || 'Ocurrió un error al guardar el pedido',
                        icon: 'error',
                        confirmButtonColor: '#0066cc'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    title: 'Error',
                    text: 'Ocurrió un error al guardar el pedido',
                    icon: 'error',
                    confirmButtonColor: '#0066cc'
                });
            });
        }
    });
}

/**
 * Mostrar toast con opción de crear pedido
 */
function mostrarToastCrear(borradorId) {
    Swal.fire({
        position: 'top-end',
        icon: 'success',
        title: '¡Pedido guardado!',
        html: 'El pedido se guardó como borrador. <br><strong>¿Deseas crear el pedido ahora?</strong>',
        showConfirmButton: true,
        showCancelButton: true,
        confirmButtonText: 'Crear Pedido',
        cancelButtonText: 'Luego',
        confirmButtonColor: '#0066cc',
        timer: 10000,
        timerProgressBar: true
    }).then((result) => {
        if (result.isConfirmed) {
            crearPedidoFromBorrador(borradorId);
        }
    });
}

/**
 * Crear pedido a partir del borrador
 */
function crearPedidoFromBorrador(borradorId) {
    // Obtener el siguiente número de pedido
    fetch("{{ route('asesores.next-pedido') }}")
        .then(response => response.json())
        .then(data => {
            const siguientePedido = data.siguiente_pedido;
            
            // Mostrar modal de confirmación para crear
            Swal.fire({
                title: 'Crear Pedido',
                html: `<p>Tu pedido recibirá el ID: <strong>${siguientePedido}</strong></p>
                       <p style="color: #666; font-size: 0.9rem;">Esto no se puede cambiar.</p>`,
                icon: 'info',
                showCancelButton: true,
                confirmButtonColor: '#0066cc',
                cancelButtonColor: '#f0f0f0',
                confirmButtonText: 'Confirmar y Crear',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Llamar al método confirm del controlador
                    fetch("{{ route('asesores.pedidos.confirm') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            borrador_id: borradorId,
                            pedido: siguientePedido
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                title: '¡Éxito!',
                                text: `Pedido creado con ID: ${data.pedido}`,
                                icon: 'success',
                                confirmButtonColor: '#0066cc'
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                title: 'Error',
                                text: data.message || 'Error al crear el pedido',
                                icon: 'error',
                                confirmButtonColor: '#0066cc'
                            });
                        }
                    });
                }
            });
        });
}

// Inicializar componentes de modales cuando el DOM está listo
document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ Asesores Pedidos - Modales cargados');
    
    // Establecer contexto modal para asesores
    window.modalContext = 'registros';
    window.fetchUrl = '{{ route("registros.show", ":id") }}'.replace(':id', '');
    
    // Configurar cierres de modales
    const closeDetailBtn = document.getElementById('closeDetailModal');
    if (closeDetailBtn) {
        closeDetailBtn.addEventListener('click', closeOrderDetailModal);
    }
    
    const closeTrackingBtn = document.getElementById('closeTrackingModal');
    if (closeTrackingBtn) {
        closeTrackingBtn.addEventListener('click', closeOrderTrackingModal);
    }
    
    // Cerrar modales al hacer click en overlay
    const detailOverlay = document.getElementById('detailModalOverlay');
    if (detailOverlay) {
        detailOverlay.addEventListener('click', closeOrderDetailModal);
    }
    
    const trackingOverlay = document.getElementById('trackingModalOverlay');
    if (trackingOverlay) {
        trackingOverlay.addEventListener('click', closeOrderTrackingModal);
    }
});

// Función para cerrar modal de detalles
function closeOrderDetailModal() {
    const modal = document.querySelector('.order-detail-modal');
    if (modal) {
        modal.style.display = 'none';
    }
}

// Función para cerrar modal de seguimiento
function closeOrderTrackingModal() {
    const modal = document.getElementById('orderTrackingModal');
    if (modal) {
        modal.style.display = 'none';
    }
}
</script>
