@extends('cartera-pedidos.layout')

@section('title', 'Cartera - Pedidos por Aprobar')
@section('page-title', 'Cartera - Pedidos por Aprobar')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/cartera-pedidos/cartera_pedidos.css') }}">
@endpush

@section('content')
<div class="cartera-pedidos-container">
    <!-- Tabla de Pedidos -->
    <div class="table-container">
        <div class="modern-table-wrapper">
            <div class="table-scroll-container">
                <div class="table-head">
                    <div style="display: flex; align-items: center; width: 100%; gap: 12px; padding: 14px 12px;">
                        @php
                            $columns = [
                                ['key' => 'acciones', 'label' => 'Acciones', 'flex' => '0 0 200px', 'justify' => 'flex-start'],
                                ['key' => 'cliente', 'label' => 'Cliente', 'flex' => '0 0 200px', 'justify' => 'center'],
                                ['key' => 'fecha', 'label' => 'Fecha', 'flex' => '0 0 160px', 'justify' => 'center'],
                                ['key' => 'estado', 'label' => 'Estado', 'flex' => '0 0 150px', 'justify' => 'center'],
                                ['key' => 'monto', 'label' => 'Monto', 'flex' => '0 0 150px', 'justify' => 'center'],
                            ];
                        @endphp
                        @foreach($columns as $column)
                            <div class="table-header-cell{{ $column['key'] === 'acciones' ? ' acciones-column' : '' }}" style="flex: {{ $column['flex'] }}; justify-content: {{ $column['justify'] }};">
                                <span class="header-text">{{ $column['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="modern-table">
                    <div class="table-body" id="tablaPedidosBody">
                        <!-- Los pedidos se cargarán aquí mediante JavaScript -->
                        <div class="loading-state">
                            <div class="spinner"></div>
                            <p>Cargando pedidos...</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Paginación -->
            <div class="table-pagination">
                <div class="pagination-info">
                    <span id="paginationInfo">Cargando información...</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Aprobación -->
<div id="modalAprobacion" class="modal-overlay" style="display: none;">
    <div class="modal-content modal-aprobacion">
        <div class="modal-header">
            <div class="header-icon success">
                <span class="material-symbols-rounded">check_circle</span>
            </div>
            <h2>Aprobar Pedido <span id="aprobacionPedidoNumero"></span></h2>
        </div>
        <div class="modal-body">
            <p class="info-texto">¿Deseas aprobar este pedido? Se marcará como aprobado y procederá al siguiente estado.</p>
            <form id="formAprobacion" onsubmit="confirmarAprobacion(event)">
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="cerrarModalAprobacion()">Cancelar</button>
                    <button type="submit" id="btnConfirmarAprobacion" class="btn btn-success">
                        <span class="material-symbols-rounded">check_circle</span>
                        Aprobar Pedido
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Rechazo -->
<div id="modalRechazo" class="modal-overlay" style="display: none;">
    <div class="modal-content modal-rechazo">
        <div class="modal-header">
            <div class="header-icon warning">
                <span class="material-symbols-rounded">block</span>
            </div>
            <h2>Rechazar Pedido <span id="rechazoPedidoNumero"></span></h2>
        </div>
        <div class="modal-body">
            <p class="advertencia-texto">Ingresa el motivo del rechazo. El cliente será notificado sobre esta decisión.</p>
            <form id="formRechazo" onsubmit="confirmarRechazo(event)">
                <div class="form-group">
                    <label for="motivoRechazo">Motivo del rechazo *</label>
                    <textarea 
                        id="motivoRechazo" 
                        name="motivo" 
                        class="form-control" 
                        rows="5" 
                        placeholder="Ej: Crédito vencido, documentación incompleta, política de cartera..."
                        required
                        minlength="10"
                        maxlength="1000"></textarea>
                    <small class="contador-caracteres">
                        <span id="contadorRechazo">0</span>/1000 caracteres
                    </small>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="cerrarModalRechazo()">Cancelar</button>
                    <button type="submit" id="btnConfirmarRechazo" class="btn btn-danger">
                        <span class="material-symbols-rounded">done</span>
                        Confirmar Rechazo
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Toast Notifications -->
<div id="toastContainer" class="toast-container"></div>

@push('scripts')
    <script>
        console.log('%c SCRIPTS SECTION EJECUTÁNDOSE', 'color: #3b82f6; font-size: 14px; font-weight: bold;');
    </script>
    <script src="{{ asset('js/cartera-pedidos/debug-css.js') }}"></script>
    <script src="{{ asset('js/cartera-pedidos/cartera_pedidos.js') }}"></script>
@endpush
