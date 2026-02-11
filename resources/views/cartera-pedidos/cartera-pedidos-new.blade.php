@extends('cartera-pedidos.layout-new')

@section('title', 'Cartera de Pedidos')
@section('page-title', 'Cartera de Pedidos')

@section('content')
<style>
    /* CSS específico para cartera pedidos */
    .cartera-section {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }

    .cartera-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
    }

    .cartera-title {
        font-size: 1.25rem;
        font-weight: 600;
        color: #1f2937;
    }

    .cartera-actions {
        display: flex;
        gap: 0.75rem;
    }

    .btn {
        padding: 0.75rem 1.5rem;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 500;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.95rem;
    }

    .btn-primary {
        background: #3b82f6;
        color: white;
    }

    .btn-primary:hover {
        background: #2563eb;
    }

    .btn-primary:disabled {
        background: #9ca3af;
        cursor: not-allowed;
    }

    .btn-primary.loading {
        opacity: 0.8;
    }

    /* TABLE */
    .table-container {
        background: white;
        border-radius: 8px;
        border: 1px solid #e5e7eb;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .table {
        width: 100%;
        border-collapse: collapse;
    }

    .table thead {
        background: #f9fafb;
        border-bottom: 2px solid #e5e7eb;
    }

    .table th {
        padding: 1rem;
        text-align: left;
        font-weight: 600;
        color: #374151;
        font-size: 0.9rem;
        text-transform: uppercase;
    }

    .table td {
        padding: 1rem;
        border-bottom: 1px solid #e5e7eb;
        color: #6b7280;
    }

    .table tbody tr:hover {
        background: #fafbfc;
    }

    /* EMPTY STATE */
    .empty-state {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 1rem;
        padding: 3rem 2rem;
        text-align: center;
        background: white;
        border-radius: 8px;
        border: 1px solid #e5e7eb;
    }

    .empty-icon {
        font-size: 3rem;
        opacity: 0.5;
    }

    .empty-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: #1f2937;
    }

    .empty-description {
        color: #9ca3af;
        font-size: 0.95rem;
    }

    /* MODALS */
    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 3000;
        align-items: center;
        justify-content: center;
    }

    .modal.open {
        display: flex;
    }

    .modal-content {
        background: white;
        border-radius: 8px;
        padding: 2rem;
        max-width: 500px;
        width: 90%;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 20px 25px rgba(0, 0, 0, 0.15);
    }

    .modal-header {
        margin-bottom: 1.5rem;
    }

    .modal-title {
        font-size: 1.25rem;
        font-weight: 600;
        color: #1f2937;
    }

    .modal-body {
        margin-bottom: 1.5rem;
    }

    .form-group {
        margin-bottom: 1rem;
    }

    .form-label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 500;
        color: #374151;
        font-size: 0.95rem;
    }

    .form-input,
    .form-textarea {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-family: inherit;
        font-size: 0.95rem;
        transition: all 0.3s ease;
    }

    .form-input:focus,
    .form-textarea:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    .form-textarea {
        resize: vertical;
        min-height: 100px;
    }

    .char-counter {
        font-size: 0.85rem;
        color: #9ca3af;
        margin-top: 0.25rem;
    }

    .modal-footer {
        display: flex;
        gap: 0.75rem;
        justify-content: flex-end;
    }

    .btn-secondary {
        background: #e5e7eb;
        color: #374151;
    }

    .btn-secondary:hover {
        background: #d1d5db;
    }

    .btn-danger {
        background: #ef4444;
        color: white;
    }

    .btn-danger:hover {
        background: #dc2626;
    }

    .btn-success {
        background: #10b981;
        color: white;
    }

    .btn-success:hover {
        background: #059669;
    }

    /* ALERTS */
    .alert {
        padding: 1rem;
        border-radius: 6px;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .alert-success {
        background: #ecfdf5;
        color: #065f46;
        border: 1px solid #a7f3d0;
    }

    .alert-danger {
        background: #fef2f2;
        color: #7f1d1d;
        border: 1px solid #fecaca;
    }

    .alert-warning {
        background: #fffbeb;
        color: #78350f;
        border: 1px solid #fcd34d;
    }

    .alert-info {
        background: #eff6ff;
        color: #0c2d6b;
        border: 1px solid #bfdbfe;
    }
</style>

<div class="cartera-section">
    <!-- HEADER CON BOTONES -->
    <div class="cartera-header">
        <h2 class="cartera-title">Gestión de Pedidos</h2>
        <div class="cartera-actions">
            <button id="btnRefreshPedidos" class="btn btn-primary">
                <span class="material-symbols-rounded">refresh</span>
                Actualizar
            </button>
        </div>
    </div>

    <!-- CONTENEDOR DE NOTIFICACIONES -->
    <div id="notificacionesContainer"></div>

    <!-- TABLA O EMPTY STATE -->
    <div id="tablaPedidosWrapper" class="table-container" style="display: none;">
        <table class="table">
            <thead>
                <tr>
                    <th>Número Pedido</th>
                    <th>Cliente</th>
                    <th>Monto Total</th>
                    <th>Fecha</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="tablaPedidosBody">
                <!-- Pedidos aquí -->
            </tbody>
        </table>
    </div>

    <!-- ESTADO VACÍO -->
    <div id="emptyState" class="empty-state" style="display: none;">
        <div class="empty-icon"></div>
        <h3 class="empty-title">No hay pedidos pendientes</h3>
        <p class="empty-description">No hay pedidos en estado "Pendiente Cartera" para revisar</p>
    </div>

    <!-- ESTADO DE CARGA -->
    <div id="loadingState" style="display: none;">
        <div class="empty-state">
            <div class="empty-icon">⏳</div>
            <h3 class="empty-title">Cargando pedidos...</h3>
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
            <p>¿Está seguro de que desea <strong>aprobar</strong> el pedido <span id="pedidoNumeroAprobacion"></span>?</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="cerrarModalAprobacion()">Cancelar</button>
            <button type="button" id="btnConfirmarAprobacion" class="btn btn-success">Aprobar</button>
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
                <p>Pedido: <strong><span id="pedidoNumeroRechazo"></span></strong></p>
                <div class="form-group">
                    <label class="form-label">Motivo del rechazo *</label>
                    <textarea id="motivoRechazo" class="form-textarea" placeholder="Explique el motivo..." required></textarea>
                    <div class="char-counter">
                        <span id="charCount">0</span>/500
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="cerrarModalRechazo()">Cancelar</button>
                <button type="submit" id="btnConfirmarRechazo" class="btn btn-danger">Rechazar</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script src="{{ asset('js/cartera-pedidos/app.js') }}"></script>
@endpush
