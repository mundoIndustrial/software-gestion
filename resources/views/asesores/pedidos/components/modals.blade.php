<!-- Contenedor para Dropdowns (Fuera de la tabla) -->
<div id="dropdowns-container" style="position: fixed; top: 0; left: 0; z-index: 999999; pointer-events: none;"></div>

<!-- Modal de Filtros (necesario para pedidos-table-filters.js) -->
<div id="filterModal" class="filter-modal-overlay" onclick="closeFilterModal(event)">
    <div class="filter-modal" onclick="event.stopPropagation()" style="width: 420px; max-width: 90%;">
        <div class="filter-modal-header" style="display:flex; justify-content:space-between; align-items:center; padding: 1.5rem; border-bottom: 1px solid #e5e7eb;">
            <h3 id="filterModalTitle" style="margin: 0; font-size: 1.125rem; color: #1e40af; font-weight: 700;">Filtrar</h3>
            <button class="filter-modal-close" onclick="closeFilterModal()" style="background:none; border:none; font-size:24px; cursor:pointer; color: #6b7280;">&times;</button>
        </div>
        <div class="filter-modal-body" style="padding: 1.5rem;">
            <input type="text" class="filter-search" id="filterSearch" placeholder="Buscar..." style="width:100%; padding:0.75rem; margin-bottom:1rem; border:2px solid #e5e7eb; border-radius:8px; font-size:0.95rem;">
            <div class="filter-options" id="filterOptions" style="display:flex; flex-direction:column; gap:0.5rem; max-height:300px; overflow-y:auto;"></div>
        </div>
        <div class="filter-modal-footer" style="display:flex; gap:8px; justify-content:flex-end; padding: 1rem 1.5rem; border-top: 1px solid #e5e7eb;">
            <button class="btn-filter-reset" onclick="resetFilters()" style="background:white; border:2px solid #e5e7eb; padding:8px 16px; border-radius:6px; cursor:pointer; font-weight:600; color:#374151;">Limpiar</button>
            <button class="btn-filter-apply" onclick="applyFilters()" style="background:linear-gradient(135deg,#1e40af,#0ea5e9); color:white; border:none; padding:8px 16px; border-radius:6px; cursor:pointer; font-weight:600;">Aplicar</button>
        </div>
    </div>
</div>

<!-- Modal de Descripción de Prendas (reutilizado de órdenes) -->
<x-orders-components.order-description-modal />

<!-- Modal de Imagen -->
@include('components.modal-imagen')

<!-- Modal de Detalle de Orden -->
<div id="modal-overlay" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.5); backdrop-filter: blur(4px); z-index: 9997; display: none; pointer-events: auto;" onclick="closeModalOverlay()"></div>

<div id="order-detail-modal-wrapper" style="width: 90%; max-width: 672px; position: fixed; top: 60%; left: 50%; transform: translate(-50%, -50%); z-index: 9998; pointer-events: auto; display: none;">
    <x-orders-components.order-detail-modal />
</div>

<!-- Modal de Detalle de Orden - LOGO -->
<div id="order-detail-modal-wrapper-logo" style="width: 90%; max-width: 672px; height: auto; position: fixed; top: 60%; left: 50%; transform: translate(-50%, -50%); z-index: 99999; pointer-events: auto; display: none;">
    <x-orders-components.order-detail-modal-logo />
</div>

<!-- Modal de Seguimiento del Pedido (Tracking Simplificado para Asesoras) -->
<x-orders-components.asesoras-tracking-modal />

<!-- Selector de Prendas y Procesos (abre primero) -->
@include('components.modals.recibos-process-selector')

<!-- Modal Intermedio de Recibos (lista de prendas y procesos) -->
@include('components.modals.recibos-intermediate-modal')

<!-- Modal Dinámico de Recibo (detalle de proceso específico) -->
@include('components.modals.recibo-dinamico-modal')

<!-- Modal para Agregar/Editar Prendas (necesario para edición desde listado) -->
@include('asesores.pedidos.modals.modal-agregar-prenda-nueva')
@include('asesores.pedidos.modals.modal-seleccionar-tallas')
@include('asesores.pedidos.modals.modal-proceso-generico')

<!-- Modal de Confirmación de Eliminación de Imagen (FUERA del modal-proceso-generico para evitar aria-hidden) -->
@include('asesores.pedidos.modals.modal-confirmar-eliminar-imagen-proceso')
