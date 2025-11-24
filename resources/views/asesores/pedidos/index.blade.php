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

    <script>
        console.log('📋 ASESORES PEDIDOS - Cargando CSS');
        console.log('CSS modern-table.css:', document.querySelector('link[href*="modern-table.css"]') ? 'CARGADO' : 'NO CARGADO');
        console.log('CSS dropdown-styles.css:', document.querySelector('link[href*="dropdown-styles.css"]') ? 'CARGADO' : 'NO CARGADO');
        console.log('CSS viewButtonDropdown.css:', document.querySelector('link[href*="viewButtonDropdown.css"]') ? 'CARGADO' : 'NO CARGADO');
    </script>

    <style>
        /* Override AGRESIVO de estilos de asesores.layout para que use modern-table */
        
        /* TABLA */
        .modern-table {
            width: 100% !important;
            border-collapse: collapse !important;
            background: white !important;
            border: 1px solid #ddd !important;
            font-size: 13px !important;
        }
        
        .modern-table thead {
            background: #2a2a2a !important;
            color: white !important;
        }
        
        .modern-table thead th {
            padding: 10px 8px !important;
            text-align: left !important;
            font-weight: 600 !important;
            border: 1px solid #ddd !important;
            background: #2a2a2a !important;
            color: white !important;
            white-space: nowrap !important;
            font-size: 12px !important;
        }
        
        .modern-table tbody td {
            padding: 8px 6px !important;
            border: 1px solid #ddd !important;
            background: white !important;
            font-size: 12px !important;
        }
        
        .modern-table tbody tr:hover {
            background: #f9f9f9 !important;
        }
        
        /* ANCHO DE COLUMNAS */
        .acciones-column {
            width: 120px !important;
            min-width: 120px !important;
        }
        
        .modern-table th:nth-child(2),
        .modern-table td:nth-child(2) {
            width: 80px !important;
            min-width: 80px !important;
        }
        
        .modern-table th:nth-child(3),
        .modern-table td:nth-child(3) {
            width: 90px !important;
            min-width: 90px !important;
        }
        
        .modern-table th:nth-child(4),
        .modern-table td:nth-child(4) {
            width: 100px !important;
            min-width: 100px !important;
        }
        
        .modern-table th:nth-child(5),
        .modern-table td:nth-child(5) {
            width: 70px !important;
            min-width: 70px !important;
        }
        
        /* CONTAINER */
        .table-container {
            background: #f5f5f5 !important;
            padding: 20px !important;
            border-radius: 8px !important;
        }
        
        /* HEADER */
        .table-header {
            display: flex !important;
            justify-content: space-between !important;
            align-items: center !important;
            margin-bottom: 20px !important;
            background: white !important;
            padding: 15px !important;
            border-radius: 6px !important;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1) !important;
        }
        
        .table-title {
            margin: 0 !important;
            font-size: 1.5rem !important;
            color: #333 !important;
            display: flex !important;
            align-items: center !important;
            gap: 10px !important;
            font-weight: 700 !important;
        }
        
        /* SEARCH */
        .search-container {
            flex: 1 !important;
            margin: 0 20px !important;
        }
        
        .search-input-wrapper {
            display: flex !important;
            align-items: center !important;
            background: #f9f9f9 !important;
            border: 1px solid #ddd !important;
            border-radius: 6px !important;
            padding: 8px 12px !important;
        }
        
        .search-icon {
            color: #999 !important;
            margin-right: 8px !important;
        }
        
        .search-input {
            border: none !important;
            background: transparent !important;
            flex: 1 !important;
            outline: none !important;
            font-size: 14px !important;
            width: 100% !important;
        }
        
        /* ACTIONS */
        .table-actions {
            display: flex !important;
            gap: 10px !important;
        }
        
        .btn {
            padding: 10px 20px !important;
            border-radius: 6px !important;
            text-decoration: none !important;
            font-weight: 600 !important;
            cursor: pointer !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 8px !important;
        }
        
        .btn-primary {
            background: #0066cc !important;
            color: white !important;
            border: none !important;
        }
        
        .btn-primary:hover {
            background: #0052a3 !important;
        }
        
        /* WRAPPER */
        .modern-table-wrapper {
            background: white !important;
            border-radius: 6px !important;
            overflow: hidden !important;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1) !important;
        }
        
        .table-scroll-container {
            overflow-x: auto !important;
        }
        
        /* PAGINATION */
        .table-pagination {
            background: #f5f5f5 !important;
            padding: 15px !important;
            display: flex !important;
            justify-content: space-between !important;
            align-items: center !important;
            border-top: 1px solid #ddd !important;
        }
        
        .pagination-info {
            color: #666 !important;
            font-size: 14px !important;
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
                                        <button class="action-btn detail-btn" onclick="verFactura({{ $pedido->id }})"
                                            title="Ver Factura"
                                            style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border: none; padding: 8px 12px; border-radius: 4px; cursor: pointer; font-size: 10px; font-weight: 600; flex: 1; min-width: 45px; height: 36px; text-align: center; display: flex; align-items: center; justify-content: center; white-space: nowrap;">
                                            <i class="fas fa-eye"></i> Ver
                                        </button>
                                        <button class="action-btn detail-btn" onclick="verSeguimiento({{ $pedido->id }})"
                                            title="Ver Seguimiento"
                                            style="background: linear-gradient(135deg, #3b82f6 0%, #6366f1 100%); color: white; border: none; padding: 8px 12px; border-radius: 4px; cursor: pointer; font-size: 10px; font-weight: 600; flex: 1; min-width: 45px; height: 36px; text-align: center; display: flex; align-items: center; justify-content: center; white-space: nowrap;">
                                            <i class="fas fa-tasks"></i> Seguimiento
                                        </button>
                                    </div>
                                </td>
                                <td class="table-cell">
                                    <div class="cell-content">
                                        <span style="background: #ff8c00; color: white; padding: 4px 12px; border-radius: 4px; font-size: 12px; font-weight: 600;">
                                            {{ $pedido->estado ?? 'Sin estado' }}
                                        </span>
                                    </div>
                                </td>
                                <td class="table-cell">
                                    <div class="cell-content">
                                        <span style="color: #fff;">{{ $pedido->getAreaActual() }}</span>
                                    </div>
                                </td>
                                <td class="table-cell">
                                    <div class="cell-content">
                                        <span style="color: #fff;">{{ $pedido->dia_de_entrega ?? '-' }}</span>
                                    </div>
                                </td>
                                <td class="table-cell">
                                    <div class="cell-content">
                                        <span style="color: #fff; font-weight: 600;">#{{ $pedido->numero_pedido }}</span>
                                    </div>
                                </td>
                                <td class="table-cell">
                                    <div class="cell-content">
                                        <span style="color: #fff;">{{ $pedido->cliente }}</span>
                                    </div>
                                </td>
                                <td class="table-cell">
                                    <div class="cell-content">
                                        <span style="color: #999;">
                                            @if($pedido->prendas->first())
                                                {{ $pedido->prendas->first()->nombre_prenda }}
                                            @else
                                                -
                                            @endif
                                        </span>
                                    </div>
                                </td>
                                <td class="table-cell">
                                    <div class="cell-content">
                                        <span style="color: #fff;">
                                            @if($pedido->prendas->first())
                                                {{ $pedido->prendas->first()->cantidad }}
                                            @else
                                                -
                                            @endif
                                        </span>
                                    </div>
                                </td>
                                <td class="table-cell">
                                    <div class="cell-content">
                                        <span style="color: #fff;">{{ $pedido->forma_de_pago ?? '-' }}</span>
                                    </div>
                                </td>
                                <td class="table-cell">
                                    <div class="cell-content">
                                        <span style="color: #fff;">{{ $pedido->fecha_de_creacion_de_orden ? $pedido->fecha_de_creacion_de_orden->format('d/m/Y') : '-' }}</span>
                                    </div>
                                </td>
                                <td class="table-cell">
                                    <div class="cell-content">
                                        <span style="color: #fff;">{{ $pedido->fecha_estimada_de_entrega ? $pedido->fecha_estimada_de_entrega->format('d/m/Y') : '-' }}</span>
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
function verFactura(pedidoId) {
    alert('Ver Factura - Pedido ID: ' + pedidoId);
    // Aquí irá el código para abrir el modal de factura
}

function verSeguimiento(pedidoId) {
    alert('Ver Seguimiento - Pedido ID: ' + pedidoId);
    // Aquí irá el código para abrir el modal de seguimiento
}
</script>

<!-- MODAL CREAR PEDIDO - SISTEMA DE TABS/PESTAÑAS -->
<div id="modalCrearPedido" class="modal-overlay" style="display: none;">
    <!-- Header del Modal -->
    <div class="modal-header">
        <h2>Crear Nuevo Pedido</h2>
        <button onclick="cerrarModalCrearPedido()" class="btn-close" title="Cerrar">
            ✕
        </button>
    </div>

    <!-- TABS NAVIGATION -->
    <div class="tabs-navigation">
        <button type="button" class="tab-button active" onclick="mostrarTabModal('info-general')">
            <i class="fas fa-info-circle"></i>
            <span>Información</span>
        </button>
        <button type="button" class="tab-button" onclick="mostrarTabModal('productos')">
            <i class="fas fa-box"></i>
            <span>Productos</span>
        </button>
        <button type="button" class="tab-button" onclick="mostrarTabModal('resumen')">
            <i class="fas fa-clipboard-list"></i>
            <span>Resumen</span>
        </button>
    </div>

    <form id="formCrearPedidoModal" class="form-modal-tabs">
        @csrf

        <!-- TAB 1: INFORMACIÓN GENERAL -->
        <div id="tab-info-general" class="tab-content active">
            <div class="tab-body">
                <div class="form-group">
                    <label>Cliente *</label>
                    <input type="text" id="nuevoCliente" name="cliente" class="form-control" placeholder="Nombre cliente" required>
                </div>

                <div class="form-group">
                    <label>Forma de Pago</label>
                    <input type="text" id="nuevoFormaPago" name="forma_de_pago" class="form-control" placeholder="Escribir o seleccionar..." list="formasPagoList" autocomplete="off">
                    <datalist id="formasPagoList">
                        <option value="CRÉDITO"></option>
                        <option value="CONTADO"></option>
                        <option value="50/50"></option>
                        <option value="ANTICIPO"></option>
                    </datalist>
                </div>
            </div>

            <div class="tab-actions">
                <button type="button" onclick="mostrarTabModal('productos')" class="btn btn-primary">
                    <i class="fas fa-arrow-right"></i>
                    Siguiente
                </button>
            </div>
        </div>

        <!-- TAB 2: PRODUCTOS -->
        <div id="tab-productos" class="tab-content">
            <div class="tab-header">
                <button type="button" onclick="agregarProductoModal()" class="btn-add-product">
                    <i class="fas fa-plus"></i>
                    Agregar Producto
                </button>
            </div>
            <div class="tab-body">
                <div id="productosModalContainer" class="productos-modal-list">
                    <!-- Productos se agregan aquí -->
                </div>
            </div>

            <div class="tab-actions">
                <button type="button" onclick="mostrarTabModal('info-general')" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i>
                    Anterior
                </button>
                <button type="button" onclick="mostrarTabModal('resumen')" class="btn btn-primary">
                    <i class="fas fa-arrow-right"></i>
                    Siguiente
                </button>
            </div>
        </div>

        <!-- TAB 3: RESUMEN -->
        <div id="tab-resumen" class="tab-content">
            <div class="tab-body">
                <div class="resumen-card">
                    <h4>Resumen del Pedido</h4>
                    <div class="resumen-item">
                        <span>Total de Productos:</span>
                        <strong id="resumenTotalProductos">0</strong>
                    </div>
                    <div class="resumen-item">
                        <span>Cantidad Total:</span>
                        <strong id="resumenCantidadTotal">0</strong>
                    </div>
                </div>

                <div class="resumen-info">
                    <i class="fas fa-check-circle"></i>
                    <p>Revisa que toda la información esté correcta antes de crear el pedido.</p>
                </div>

                <div class="resumen-detalles">
                    <div class="detalle-item">
                        <span>Cliente:</span>
                        <strong id="resumenCliente">-</strong>
                    </div>
                    <div class="detalle-item">
                        <span>Forma de Pago:</span>
                        <strong id="resumenFormaPago">-</strong>
                    </div>
                    <div class="detalle-item">
                        <span>Estado Inicial:</span>
                        <strong id="resumenEstado">No iniciado</strong>
                        <span style="font-size: 0.85rem; color: #666;">(asignado automáticamente)</span>
                    </div>
                </div>
            </div>

            <div class="tab-actions">
                <button type="button" onclick="mostrarTabModal('productos')" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i>
                    Anterior
                </button>
                <button type="button" onclick="guardarPedidoModal()" class="btn btn-primary">
                    <i class="fas fa-check"></i>
                    Crear Pedido
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Template para producto en modal -->
<template id="productoModalTemplate">
    <div class="producto-modal-item">
        <div class="producto-modal-header">
            <h4 class="prenda-numero">Prenda <span class="numero-prenda">1</span></h4>
            <button type="button" onclick="eliminarProductoModal(this)" class="btn-remove">
                <i class="fas fa-trash"></i>
            </button>
        </div>
        <div class="producto-modal-body">
            <div class="form-row">
                <div class="form-col">
                    <label>Nombre de Prenda *</label>
                    <input type="text" name="productos_modal[][nombre_producto]" class="form-control" placeholder="Ej: Polo, Camiseta..." required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-col">
                    <label>Descripción</label>
                    <input type="text" name="productos_modal[][descripcion]" class="form-control" placeholder="Detalles adicionales">
                </div>
            </div>

            <div class="form-row">
                <div class="form-col">
                    <label>Talla *</label>
                    <input type="text" name="productos_modal[][talla]" class="form-control" placeholder="Ej: S, M, L, XL" required>
                </div>
                <div class="form-col">
                    <label>Cantidad *</label>
                    <input type="number" name="productos_modal[][cantidad]" class="form-control producto-modal-cantidad" placeholder="1" min="1" value="1" onchange="actualizarResumenModal()" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-col">
                    <label>Color</label>
                    <input type="text" name="productos_modal[][color]" class="form-control" placeholder="Ej: Blanco, Negro">
                </div>
                <div class="form-col">
                    <label>Tela</label>
                    <input type="text" name="productos_modal[][tella]" class="form-control" placeholder="Ej: Algodón 100%">
                </div>
            </div>

            <div class="form-row">
                <div class="form-col">
                    <label>Tipo de Manga</label>
                    <select name="productos_modal[][tipo_manga]" class="form-control">
                        <option value="">Seleccionar...</option>
                        <option value="Manga Corta">Manga Corta</option>
                        <option value="Manga Larga">Manga Larga</option>
                        <option value="Sin Manga">Sin Manga</option>
                    </select>
                </div>
                <div class="form-col">
                    <label>Género</label>
                    <select name="productos_modal[][genero]" class="form-control">
                        <option value="">Seleccionar...</option>
                        <option value="Hombre">Hombre</option>
                        <option value="Mujer">Mujer</option>
                        <option value="Niño">Niño</option>
                        <option value="Unisex">Unisex</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-col">
                    <label>Referencia de Hilo</label>
                    <input type="text" name="productos_modal[][ref_hilo]" class="form-control" placeholder="Código de hilo">
                </div>
                <div class="form-col">
                    <label>Precio Unitario</label>
                    <input type="number" name="productos_modal[][precio_unitario]" class="form-control" placeholder="0.00" step="0.01" min="0">
                </div>
            </div>

            <!-- TAB 2: PRODUCTOS -->
            <div id="tab-productos" class="tab-content">
                <div class="tab-header">
                    <button type="button" onclick="agregarProductoModal()" class="btn-add-product">
                        <i class="fas fa-plus"></i>
                        Agregar Producto
                    </button>
                </div>
                <div class="tab-body">
                    <div id="productosModalContainer" class="productos-modal-list">
                        <!-- Productos se agregan aquí -->
                    </div>
                </div>

                <div class="tab-actions">
                    <button type="button" onclick="mostrarTabModal('info-general')" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i>
                        Anterior
                    </button>
                    <button type="button" onclick="mostrarTabModal('resumen')" class="btn btn-primary">
                        <i class="fas fa-arrow-right"></i>
                        Siguiente
                    </button>
                </div>
            </div>

            <!-- TAB 3: RESUMEN -->
            <div id="tab-resumen" class="tab-content">
                <div class="tab-body">
                    <div class="resumen-card">
                        <h4>Resumen del Pedido</h4>
                        <div class="resumen-item">
                            <span>Total de Productos:</span>
                            <strong id="resumenTotalProductos">0</strong>
                        </div>
                        <div class="resumen-item">
                            <span>Cantidad Total:</span>
                            <strong id="resumenCantidadTotal">0</strong>
                        </div>
                    </div>

                    <div class="resumen-info">
                        <i class="fas fa-check-circle"></i>
                        <p>Revisa que toda la información esté correcta antes de crear el pedido.</p>
                    </div>

                    <div class="resumen-detalles">
                        <div class="detalle-item">
                            <span>Cliente:</span>
                            <strong id="resumenCliente">-</strong>
                        </div>
                        <div class="detalle-item">
                            <span>Forma de Pago:</span>
                            <strong id="resumenFormaPago">-</strong>
                        </div>
                        <div class="detalle-item">
                            <span>Estado Inicial:</span>
                            <strong id="resumenEstado">No iniciado</strong>
                            <span style="font-size: 0.85rem; color: #666;">(asignado automáticamente)</span>
                        </div>
                    </div>
                </div>

                <div class="tab-actions">
                    <button type="button" onclick="mostrarTabModal('productos')" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i>
                        Anterior
                    </button>
                    <button type="button" onclick="guardarPedidoModal()" class="btn btn-primary">
                        <i class="fas fa-check"></i>
                        Crear Pedido
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Template para producto en modal -->
    <template id="productoModalTemplate">
        <div class="producto-modal-item">
            <div class="producto-modal-header">
                <h4 class="prenda-numero">Prenda <span class="numero-prenda">1</span></h4>
                <button type="button" onclick="eliminarProductoModal(this)" class="btn-remove">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
            <div class="producto-modal-body">
                <div class="form-row">
                    <div class="form-col">
                        <label>Nombre de Prenda *</label>
                        <input type="text" name="productos_modal[][nombre_producto]" class="form-control" placeholder="Ej: Polo, Camiseta..." required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-col">
                        <label>Descripción</label>
                        <input type="text" name="productos_modal[][descripcion]" class="form-control" placeholder="Detalles adicionales">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-col">
                        <label>Talla *</label>
                        <input type="text" name="productos_modal[][talla]" class="form-control" placeholder="Ej: S, M, L, XL" required>
                    </div>
                    <div class="form-col">
                        <label>Cantidad *</label>
                        <input type="number" name="productos_modal[][cantidad]" class="form-control producto-modal-cantidad" placeholder="1" min="1" value="1" onchange="actualizarResumenModal()" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-col">
                        <label>Color</label>
                        <input type="text" name="productos_modal[][color]" class="form-control" placeholder="Ej: Blanco, Negro">
                    </div>
                    <div class="form-col">
                        <label>Tela</label>
                        <input type="text" name="productos_modal[][tella]" class="form-control" placeholder="Ej: Algodón 100%">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-col">
                        <label>Tipo de Manga</label>
                        <select name="productos_modal[][tipo_manga]" class="form-control">
                            <option value="">Seleccionar...</option>
                            <option value="Manga Corta">Manga Corta</option>
                            <option value="Manga Larga">Manga Larga</option>
                            <option value="Sin Manga">Sin Manga</option>
                        </select>
                    </div>
                    <div class="form-col">
                        <label>Género</label>
                        <select name="productos_modal[][genero]" class="form-control">
                            <option value="">Seleccionar...</option>
                            <option value="Hombre">Hombre</option>
                            <option value="Mujer">Mujer</option>
                            <option value="Niño">Niño</option>
                            <option value="Unisex">Unisex</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-col">
                        <label>Referencia de Hilo</label>
                        <input type="text" name="productos_modal[][ref_hilo]" class="form-control" placeholder="Código de hilo">
                    </div>
                    <div class="form-col">
                        <label>Precio Unitario</label>
                        <input type="number" name="productos_modal[][precio_unitario]" class="form-control" placeholder="0.00" step="0.01" min="0">
                    </div>
                </div>
            </div>
        </div>
    </template>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('TABLA CARGADA');
            const tabla = document.querySelector('.modern-table');
            const container = document.querySelector('.table-container');
            const header = document.querySelector('.table-header');
            
            console.log('Tabla .modern-table existe:', tabla ? 'SÍ' : 'NO');
            console.log('Container .table-container existe:', container ? 'SÍ' : 'NO');
            console.log('Header .table-header existe:', header ? 'SÍ' : 'NO');
            
            if (tabla) {
                console.log('Tabla clases:', tabla.className);
                console.log('Tabla estilos computados:', window.getComputedStyle(tabla).display);
            }
            
            if (container) {
                console.log('Container estilos:', window.getComputedStyle(container).background);
            }
            
            if (header) {
                console.log('Header estilos:', window.getComputedStyle(header).display);
            }
        });
    </script>

@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/asesores/pedidos.css') }}">
<style>
    /* MODAL OVERLAY - AHORA ES EL CONTENEDOR PRINCIPAL */
    .modal-overlay {
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        right: 0 !important;
        bottom: 0 !important;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: flex-start;
        z-index: 99999 !important;
        padding: 2rem;
        overflow-y: auto;
        overflow-x: hidden;
        width: 100% !important;
        max-width: 100% !important;
        height: 100% !important;
        margin: 0 !important;
        box-sizing: border-box !important;
    }

    /* HEADER MODAL */
    .modal-overlay .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem 1.5rem;
        border-bottom: 2px solid #e0e0e0;
        background: linear-gradient(135deg, #f5f5f5, #fafafa);
        flex-shrink: 0;
        min-height: 60px;
        width: 100%;
        max-width: 800px;
        border-radius: 8px 8px 0 0;
        margin-bottom: -2px;
    }

    /* TABS NAVIGATION */
    .modal-overlay .tabs-navigation {
        display: flex;
        border-bottom: 2px solid #e0e0e0;
        background: #fafafa;
        padding: 0 1.5rem;
        gap: 0.5rem;
        overflow-x: auto;
        flex-shrink: 0;
        width: 100%;
        max-width: 800px;
    }

    /* FORM MODAL TABS */
    .modal-overlay .form-modal-tabs {
        flex: 0 1 auto;
        display: flex;
        flex-direction: column;
        width: 100%;
        max-width: 800px;
        background: white;
        border-radius: 0 0 8px 8px;
        overflow: visible;
        margin: 0;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
    }

    /* HEADER H2 Y BOTÓN CERRAR */
    .modal-overlay .modal-header h2 {
        margin: 0;
        color: #333;
        font-size: 1.3rem;
        font-weight: 700;
    }

    .modal-overlay .btn-close {
        width: 36px;
        height: 36px;
        border: none;
        background: #ff4444;
        border-radius: 50%;
        cursor: pointer;
        font-size: 1.2rem;
        color: white;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        box-shadow: 0 2px 8px rgba(255, 68, 68, 0.3);
        font-weight: bold;
        line-height: 1;
        padding: 0;
    }

    .modal-overlay .btn-close:hover {
        background: #e63333;
        transform: scale(1.1) rotate(90deg);
        box-shadow: 0 4px 12px rgba(255, 68, 68, 0.5);
    }

    .modal-overlay .btn-close:active {
        transform: scale(0.95);
    }

    /* TAB BUTTONS */
    .modal-overlay .tab-button {
        padding: 0.75rem 1.2rem;
        background: transparent;
        border: none;
        color: #666;
        font-weight: 600;
        cursor: pointer;
        border-bottom: 3px solid transparent;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.9rem;
        white-space: nowrap;
        flex-shrink: 0;
    }

    .modal-overlay .tab-button i {
        font-size: 1rem;
    }

    .modal-overlay .tab-button:hover {
        color: #0066cc;
        background: rgba(0, 102, 204, 0.05);
    }

    .modal-overlay .tab-button.active {
        color: #0066cc;
        border-bottom-color: #0066cc;
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
    }

    .modal-overlay .tab-header {
        padding: 0.75rem 1.5rem 0 1.5rem;
        border-bottom: 1px solid #e0e0e0;
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
        font-weight: 600;
        margin-bottom: 0.4rem;
        color: #333;
        font-size: 0.9rem;
    }

    .modal-overlay .form-control {
        width: 100%;
        padding: 0.6rem;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-size: 0.9rem;
        font-family: inherit;
        transition: border-color 0.3s, box-shadow 0.3s;
    }

    .modal-overlay .form-control:focus {
        outline: none;
        border-color: #0066cc;
        box-shadow: 0 0 0 3px rgba(0, 102, 204, 0.1);
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
        background: #0066cc;
        color: white;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.3s;
    }

    .btn-add-product:hover {
        background: #0052a3;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 102, 204, 0.3);
    }

    /* PRODUCTOS MODAL */
    .productos-modal-list {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .producto-modal-item {
        background: white;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        overflow: hidden;
        margin-bottom: 1.5rem;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        transition: all 0.3s;
    }

    .producto-modal-item:hover {
        box-shadow: 0 4px 12px rgba(0, 102, 204, 0.1);
        border-color: #0066cc;
    }

    .producto-modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem 1.5rem;
        background: linear-gradient(135deg, #0066cc, #0052a3);
        color: white;
        border-bottom: 2px solid #0052a3;
    }

    .prenda-numero {
        margin: 0;
        font-size: 1.1rem;
        font-weight: 700;
        color: white;
    }

    .numero-prenda {
        font-weight: 700;
        color: #fff;
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
        transition: all 0.3s;
        flex-shrink: 0;
    }

    .btn-remove:hover {
        background: #f44336;
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
        font-weight: 600;
        margin-bottom: 0.5rem;
        color: #333;
        font-size: 0.9rem;
    }

    .producto-modal-body .form-control {
        padding: 0.75rem;
        font-size: 0.95rem;
        border: 1px solid #ddd;
        border-radius: 4px;
        transition: all 0.3s;
    }

    .producto-modal-body .form-control:focus {
        outline: none;
        border-color: #0066cc;
        box-shadow: 0 0 0 3px rgba(0, 102, 204, 0.1);
    }

    /* RESUMEN */
    .resumen-card {
        background: #e3f2fd;
        border-left: 4px solid #0066cc;
        border-radius: 8px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .resumen-card h4 {
        margin: 0 0 1rem 0;
        color: #0066cc;
        font-size: 1.1rem;
    }

    .resumen-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.75rem 0;
        font-weight: 600;
        border-bottom: 1px solid rgba(0, 102, 204, 0.2);
    }

    .resumen-item:last-child {
        border-bottom: none;
    }

    .resumen-item span {
        color: #666;
    }

    .resumen-item strong {
        color: #0066cc;
        font-size: 1.2rem;
    }

    .resumen-info {
        background: #fff3cd;
        border-left: 4px solid #ffc107;
        border-radius: 6px;
        padding: 1rem;
        margin-bottom: 1.5rem;
        display: flex;
        gap: 1rem;
        align-items: flex-start;
    }

    .resumen-info i {
        color: #856404;
        font-size: 1.2rem;
        margin-top: 0.2rem;
        flex-shrink: 0;
    }

    .resumen-info p {
        margin: 0;
        color: #856404;
        font-size: 0.95rem;
        line-height: 1.5;
    }

    .resumen-detalles {
        background: white;
        border: 1px solid #e0e0e0;
        border-radius: 6px;
        padding: 1.5rem;
    }

    .detalle-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.75rem 0;
        border-bottom: 1px solid #f0f0f0;
    }

    .detalle-item:last-child {
        border-bottom: none;
    }

    .detalle-item span {
        color: #666;
        font-weight: 500;
    }

    .detalle-item strong {
        color: #333;
    }

    /* ACCIONES TAB */
    .tab-actions {
        display: flex;
        gap: 1rem;
        padding: 1rem 1.5rem;
        border-top: 1px solid #e0e0e0;
        background: #fafafa;
        flex-shrink: 0;
    }

    .tab-actions .btn {
        flex: 1;
        min-height: 40px;
        padding: 0.6rem 1.2rem;
        border: none;
        border-radius: 6px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        font-size: 0.95rem;
    }

    .btn-secondary {
        background: #f0f0f0;
        color: #333;
    }

    .btn-secondary:hover {
        background: #e0e0e0;
    }

    .btn-primary {
        background: #0066cc;
        color: white;
    }

    .btn-primary:hover {
        background: #0052a3;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 102, 204, 0.3);
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
</script>
