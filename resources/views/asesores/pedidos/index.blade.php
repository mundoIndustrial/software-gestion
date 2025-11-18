@extends('asesores.layout')

@section('title', 'Mis Pedidos')
@section('page-title', 'Mis Pedidos')

@section('content')
<div class="pedidos-list-container">
    <!-- Barra de Acciones -->
    <div class="list-header">
        <div class="header-left">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Buscar por número o cliente..." value="{{ request('search') }}">
            </div>
            
            <div class="filter-group">
                <select id="filterEstado" class="filter-select">
                    <option value="">Todos los Estados</option>
                    @foreach($estados as $estado)
                        <option value="{{ $estado }}" {{ request('estado') == $estado ? 'selected' : '' }}>
                            {{ $estado }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="header-actions">
            <a href="{{ route('asesores.borradores.index') }}" class="btn btn-info" title="Ver mis borradores de órdenes">
                <i class="fas fa-file-alt"></i>
                Mis Borradores
            </a>

            <button onclick="abrirModalCrearPedido()" class="btn btn-primary">
                <i class="fas fa-plus"></i>
                Nuevo Pedido
            </button>
        </div>
    </div>

    <!-- Tabla de Pedidos -->
    <div class="table-container">
        @if($pedidos->count() > 0)
            <table class="pedidos-table">
                <thead>
                    <tr>
                        <th>Pedido</th>
                        <th>Cliente</th>
                        <th>Productos</th>
                        <th>Cantidad</th>
                        <th>Estado</th>
                        <th>Fecha</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pedidos as $pedido)
                        <tr>
                            <td>
                                <strong>#{{ $pedido->pedido }}</strong>
                            </td>
                            <td>{{ $pedido->cliente }}</td>
                            <td>
                                <span class="badge badge-info">
                                    {{ $pedido->productos->count() }} productos
                                </span>
                            </td>
                            <td>{{ $pedido->cantidad ?? 0 }}</td>
                            <td>
                                <span class="badge badge-{{ 
                                    $pedido->estado == 'Entregado' ? 'success' : 
                                    ($pedido->estado == 'En Ejecución' ? 'warning' : 
                                    ($pedido->estado == 'Anulada' ? 'danger' : 'secondary'))
                                }}">
                                    {{ $pedido->estado ?? 'Sin estado' }}
                                </span>
                            </td>
                            <td>
                                {{ $pedido->fecha_de_creacion_de_orden ? \Carbon\Carbon::parse($pedido->fecha_de_creacion_de_orden)->format('d/m/Y') : '-' }}
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="{{ route('asesores.pedidos.show', $pedido->pedido) }}" 
                                       class="btn-action btn-view" 
                                       title="Ver detalles">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('asesores.pedidos.edit', $pedido->pedido) }}" 
                                       class="btn-action btn-edit" 
                                       title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button" 
                                            class="btn-action btn-delete" 
                                            data-pedido="{{ $pedido->pedido }}"
                                            data-cliente="{{ $pedido->cliente }}"
                                            title="Eliminar">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <!-- Paginación -->
            <div class="pagination-container">
                {{ $pedidos->links() }}
            </div>
        @else
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <h3>No hay pedidos</h3>
                <p>Aún no has creado ningún pedido. ¡Crea tu primer pedido ahora!</p>
                <button onclick="abrirModalCrearPedido()" class="btn btn-primary">
                    <i class="fas fa-plus"></i>
                    Crear Primer Pedido
                </button>
            </div>
        @endif
    </div>
</div>

<!-- MODAL CREAR PEDIDO - SISTEMA DE TABS/PESTAÑAS -->
<div id="modalCrearPedido" class="modal-overlay" style="display: none;">
    <div class="modal-container-tabs">
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
                        <label>Número de Pedido</label>
                        <input type="number" id="nuevoPedido" class="form-control" readonly>
                    </div>

                    <div class="form-group">
                        <label>Cliente *</label>
                        <input type="text" id="nuevoCliente" name="cliente" class="form-control" placeholder="Nombre cliente" required>
                    </div>

                    <div class="form-group">
                        <label>Forma de Pago</label>
                        <select id="nuevoFormaPago" name="forma_de_pago" class="form-control">
                            <option value="">Seleccionar...</option>
                            <option value="Crédito">Crédito</option>
                            <option value="Contado">Contado</option>
                            <option value="50/50">50/50</option>
                            <option value="Anticipo">Anticipo</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Estado Inicial</label>
                        <select id="nuevoEstado" name="estado" class="form-control">
                            <option value="No iniciado">No iniciado</option>
                            <option value="En Ejecución">En Ejecución</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Descripción</label>
                        <textarea id="nuevoDescripcion" name="descripcion" class="form-control" rows="3" placeholder="Descripción del pedido..."></textarea>
                    </div>

                    <div class="form-group">
                        <label>Novedades</label>
                        <textarea id="nuevoNovedades" name="novedades" class="form-control" rows="2" placeholder="Instrucciones especiales..."></textarea>
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
                            <span>Estado:</span>
                            <strong id="resumenEstado">-</strong>
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
</div>

<!-- Template para producto en modal -->
<template id="productoModalTemplate">
    <div class="producto-modal-item">
        <div class="producto-modal-header">
            <input type="text" name="productos_modal[][nombre_producto]" class="input-sm" placeholder="Prenda" required>
            <button type="button" onclick="eliminarProductoModal(this)" class="btn-remove">
                <i class="fas fa-trash"></i>
            </button>
        </div>
        <div class="producto-modal-body">
            <input type="text" name="productos_modal[][color]" class="input-sm" placeholder="Color" required>
            <input type="text" name="productos_modal[][talla]" class="input-sm" placeholder="Talla" required>
            <input type="number" name="productos_modal[][cantidad]" class="input-sm producto-modal-cantidad" placeholder="Cant." min="1" value="1" onchange="actualizarResumenModal()" required>
            <input type="text" name="productos_modal[][tela]" class="input-sm" placeholder="Tela">
            <select name="productos_modal[][tipo_manga]" class="input-sm">
                <option value="">Manga</option>
                <option value="Manga Corta">Manga Corta</option>
                <option value="Manga Larga">Manga Larga</option>
                <option value="Sin Manga">Sin Manga</option>
            </select>
        </div>
    </div>
</template>

@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/asesores/pedidos.css') }}">
<style>
    /* MODAL OVERLAY */
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        padding: 2rem;
        overflow: auto;
    }

    /* CONTENEDOR MODAL - CON MÁRGENES */
    .modal-container-tabs {
        background: white;
        width: 100%;
        height: auto;
        max-width: 1000px;
        max-height: 85vh;
        display: flex;
        flex-direction: column;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        overflow: hidden;
        border-radius: 8px;
    }

    /* HEADER MODAL */
    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1.5rem 2rem;
        border-bottom: 2px solid #e0e0e0;
        background: linear-gradient(135deg, #f5f5f5, #fafafa);
        flex-shrink: 0;
        min-height: 70px;
    }

    .modal-header h2 {
        margin: 0;
        color: #333;
        font-size: 1.5rem;
        font-weight: 700;
    }

    .btn-close {
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

    .btn-close:hover {
        background: #e63333;
        transform: scale(1.1) rotate(90deg);
        box-shadow: 0 4px 12px rgba(255, 68, 68, 0.5);
    }

    .btn-close:active {
        transform: scale(0.95);
    }

    /* TABS NAVIGATION */
    .tabs-navigation {
        display: flex;
        border-bottom: 2px solid #e0e0e0;
        background: #fafafa;
        padding: 0 2rem;
        gap: 0.5rem;
        overflow-x: auto;
        flex-shrink: 0;
    }

    .tab-button {
        padding: 1rem 1.5rem;
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
        font-size: 0.95rem;
        white-space: nowrap;
        flex-shrink: 0;
    }

    .tab-button i {
        font-size: 1rem;
    }

    .tab-button:hover {
        color: #0066cc;
        background: rgba(0, 102, 204, 0.05);
    }

    .tab-button.active {
        color: #0066cc;
        border-bottom-color: #0066cc;
    }

    /* CONTENIDO TABS */
    .form-modal-tabs {
        flex: 1;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    .tab-content {
        display: none;
        flex-direction: column;
        flex: 1;
        overflow: hidden;
    }

    .tab-content.active {
        display: flex;
    }

    /* TAB BODY */
    .tab-body {
        padding: 2rem;
        flex: 1;
        overflow-y: auto;
        overflow-x: hidden;
    }

    .tab-header {
        padding: 1rem 2rem 0 2rem;
        border-bottom: 1px solid #e0e0e0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-shrink: 0;
    }

    /* FORM GROUPS */
    .form-group {
        margin-bottom: 1.5rem;
    }

    .form-group label {
        display: block;
        font-weight: 600;
        margin-bottom: 0.5rem;
        color: #333;
        font-size: 0.95rem;
    }

    .form-control {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-size: 0.95rem;
        font-family: inherit;
        transition: border-color 0.3s, box-shadow 0.3s;
    }

    .form-control:focus {
        outline: none;
        border-color: #0066cc;
        box-shadow: 0 0 0 3px rgba(0, 102, 204, 0.1);
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
        background: #f9f9f9;
        border: 1px solid #e0e0e0;
        border-left: 3px solid #0066cc;
        border-radius: 6px;
        padding: 1rem;
    }

    .producto-modal-header {
        display: flex;
        gap: 0.75rem;
        margin-bottom: 0.75rem;
    }

    .producto-modal-header input {
        flex: 1;
    }

    .btn-remove {
        width: 36px;
        height: 36px;
        padding: 0;
        background: #ffebee;
        color: #f44336;
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
        background: #ffcdd2;
    }

    .producto-modal-body {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
        gap: 0.75rem;
    }

    .producto-modal-body input,
    .producto-modal-body select {
        padding: 0.6rem;
        font-size: 0.9rem;
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
        padding: 1.5rem 2rem;
        border-top: 1px solid #e0e0e0;
        background: #fafafa;
        flex-shrink: 0;
    }

    .tab-actions .btn {
        flex: 1;
        min-height: 45px;
        padding: 0.75rem 1.5rem;
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
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
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
    productoCountModal = 0;
    obtenerSiguientePedido();
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
}

/**
 * Agregar producto al modal
 */
function agregarProductoModal() {
    const container = document.getElementById('productosModalContainer');
    const template = document.getElementById('productoModalTemplate');
    const clone = template.content.cloneNode(true);

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
    const estado = document.getElementById('nuevoEstado').value || '-';

    document.getElementById('resumenCliente').textContent = cliente;
    document.getElementById('resumenFormaPago').textContent = formaPago;
    document.getElementById('resumenEstado').textContent = estado;
}

/**
 * Guardar pedido modal
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
    
    Swal.fire({
        title: '¿Crear pedido?',
        text: 'Se creará el pedido con la información ingresada',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#0066cc',
        cancelButtonColor: '#f0f0f0',
        confirmButtonText: 'Sí, crear',
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
                    Swal.fire({
                        title: '¡Éxito!',
                        text: 'Pedido creado correctamente',
                        icon: 'success',
                        confirmButtonColor: '#0066cc'
                    }).then(() => {
                        cerrarModalCrearPedido();
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        title: 'Error',
                        text: data.message || 'Ocurrió un error al crear el pedido',
                        icon: 'error',
                        confirmButtonColor: '#0066cc'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    title: 'Error',
                    text: 'Ocurrió un error al crear el pedido',
                    icon: 'error',
                    confirmButtonColor: '#0066cc'
                });
            });
        }
    });
}
</script>
@endpush
