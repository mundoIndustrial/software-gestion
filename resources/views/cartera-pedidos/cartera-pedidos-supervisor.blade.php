@extends('cartera-pedidos.layout')

@section('title', 'Cartera de Pedidos')
@section('page-title', 'Cartera de Pedidos')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/supervisor-pedidos/index.css') }}">
    <link rel="stylesheet" href="{{ asset('css/cartera-pedidos/cartera.css') }}">
@endpush

@section('content')
<div class="supervisor-pedidos-container">
    <!-- CONTENEDOR DE NOTIFICACIONES -->
    <div id="notificacionesContainer"></div>

    <!-- Tabla de Pedidos - Mismo Diseño que Supervisores -->
    <div class="table-container">
        <div class="modern-table-wrapper">
            <div class="table-scroll-container">
                <!-- TABLA CON ESTRUCTURA DE SUPERVISORES -->
                <div class="table-head">
                    <div style="display: flex; align-items: center; width: 100%; gap: 12px; padding: 14px 12px;">
                        @php
                            $columns = [
                                ['key' => 'acciones', 'label' => 'Acciones', 'flex' => '0 0 200px', 'justify' => 'flex-start'],
                                ['key' => 'numero', 'label' => 'Número', 'flex' => '0 0 140px', 'justify' => 'center'],
                                ['key' => 'cliente', 'label' => 'Cliente', 'flex' => '0 0 200px', 'justify' => 'center'],
                                ['key' => 'fecha', 'label' => 'Fecha', 'flex' => '0 0 160px', 'justify' => 'center'],
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
                    <div class="table-body" id="tablaPedidosBody" style="min-height: 200px;">
                        <!-- Pedidos aquí -->
                    </div>
                </div>

                <!-- ESTADO VACÍO -->
                <div id="emptyState" class="empty-state" style="display: none; flex-direction: column; align-items: center; justify-content: center; padding: 40px; text-align: center; color: #9ca3af; width: 100%; min-height: 300px;">
                    <span class="material-symbols-rounded" style="font-size: 3rem; opacity: 0.5;">shopping_cart</span>
                    <p style="margin-top: 1rem; font-size: 1.1rem;">No hay pedidos pendientes de cartera</p>
                </div>

                <!-- ESTADO DE CARGA -->
                <div id="loadingState" style="display: none; padding: 40px; text-align: center; color: #9ca3af; width: 100%;">
                    <span class="material-symbols-rounded" style="font-size: 2rem; opacity: 0.5; animation: spin 1s linear infinite;">hourglass_empty</span>
                    <p>Cargando pedidos...</p>
                </div>
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
            <p>¿Está seguro de que desea <strong>aprobar</strong> el pedido <span id="pedidoNumeroAprobacion" style="color: #1e5ba8; font-weight: 700;"></span>?</p>
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
                <p>Pedido: <strong><span id="pedidoNumeroRechazo" style="color: #1e5ba8; font-weight: 700;"></span></strong></p>
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
