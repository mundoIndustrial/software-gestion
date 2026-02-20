@extends('layouts.app')

@section('title', 'Recibos de Costura')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">Recibos de Costura</h1>
                    <p class="text-muted mb-0">Listado de recibos de costura por número de recibo</p>
                </div>
            </div>

            <!-- Tabla tradicional con th y td -->
            <div class="table-scroll-container">
                <table class="table table-striped table-hover modern-table">
                    <thead class="table-header">
                        <tr>
                            <th class="acciones-column" style="width: 100px; text-align: center;">Acciones</th>
                            <th style="width: auto;">Estado</th>
                            <th style="width: auto;">Área</th>
                            <th style="width: auto;">Día de entrega</th>
                            <th style="width: 120px;">Total de días</th>
                            <th style="width: 120px;">N° Recibo</th>
                            <th style="width: 150px;">Cliente</th>
                            <th style="width: auto;">Descripción</th>
                            <th style="width: 100px;">Cantidad</th>
                            <th style="width: 120px;">Novedades</th>
                            <th style="width: 120px;">Asesor</th>
                            <th style="width: 150px;">Forma de pago</th>
                            <th style="width: 150px;">Fecha de creación</th>
                            <th style="width: 180px;">Fecha estimada entrega</th>
                            <th style="width: 150px;">Encargado orden</th>
                        </tr>
                    </thead>
                    <tbody id="tablaRecibosBody">
                        @if($recibos->count() > 0)
                            @foreach($recibos as $recibo)
                                <tr class="@if($recibo['pedido_info']['estado'] == 'PENDIENTE_SUPERVISOR') dias-mayor-15 @else dias-5-9 @endif" data-orden-id="{{ $recibo['id'] }}">
                                    <!-- Acciones -->
                                    <td class="acciones-column" style="text-align: center; position: relative;">
                                        <button class="action-view-btn" title="Ver detalles" data-orden-id="{{ $recibo['id'] }}">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <div class="action-menu" data-orden-id="{{ $recibo['id'] }}">
                                            @if($recibo['pedido_info'])
                                                <a href="{{ route('registros.show', $recibo['pedido_produccion_id']) }}" class="action-menu-item" data-action="detalle">
                                                    <i class="fas fa-eye"></i>
                                                    <span>Ver Pedido</span>
                                                </a>
                                            @endif
                                            <a href="#" class="action-menu-item" data-action="seguimiento" onclick="verDetallesRecibo({{ $recibo['id'] }})">
                                                <i class="fas fa-receipt-long"></i>
                                                <span>Detalles Recibo</span>
                                            </a>
                                        </div>
                                    </td>
                                    
                                    <!-- Estado (Badge) -->
                                    <td>
                                        @if($recibo['pedido_info'])
                                            <span class="badge bg-info">
                                                {{ $recibo['pedido_info']['estado'] }}
                                            </span>
                                        @else
                                            <span class="badge bg-secondary">N/A</span>
                                        @endif
                                    </td>
                                    
                                    <!-- Área -->
                                    <td>
                                        @if($recibo['pedido_info'])
                                            <span class="badge bg-secondary">
                                                {{ $recibo['pedido_info']['area'] ?? 'N/A' }}
                                            </span>
                                        @else
                                            <span class="badge bg-secondary">N/A</span>
                                        @endif
                                    </td>
                                    
                                    <!-- Día de entrega (No aplica para recibos) -->
                                    <td>
                                        <span class="text-muted">-</span>
                                    </td>
                                    
                                    <!-- Total de días (No aplica para recibos) -->
                                    <td style="text-align: center;">
                                        <span class="text-muted">-</span>
                                    </td>
                                    
                                    <!-- N° Recibo -->
                                    <td style="text-align: center;">
                                        <span style="font-weight: 600;">{{ $recibo['consecutivo_actual'] }}</span>
                                    </td>
                                    
                                    <!-- Cliente -->
                                    <td style="text-align: center;">
                                        @if($recibo['pedido_info'])
                                            <span>{{ $recibo['pedido_info']['cliente'] }}</span>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    
                                    <!-- Descripción (Recibo de Costura) -->
                                    <td>
                                        <span style="color: #6b7280; font-size: 0.875rem;">
                                            <strong>Recibo de Costura #{{ $recibo['consecutivo_actual'] }}</strong>
                                            @if($recibo['notas'])
                                                <br><small>{{ Str::limit($recibo['notas'], 100) }}</small>
                                            @endif
                                        </span>
                                    </td>
                                    
                                    <!-- Cantidad -->
                                    <td>
                                        <span class="text-muted">-</span>
                                    </td>
                                    
                                    <!-- Novedades -->
                                    <td>
                                        <button class="btn-edit-novedades" 
                                                data-full-novedades="{{ $recibo['notas'] ?? 'Sin novedades' }}" 
                                                onclick="event.stopPropagation(); openNovedadesModal({{ $recibo['id'] }}, `{{ $recibo['notas'] ?? 'Sin novedades' }}`)" 
                                                title="Editar notas del recibo" 
                                                type="button">
                                            <span class="novedades-text">
                                                @if($recibo['notas'])
                                                    {{ Str::limit($recibo['notas'], 30) }}
                                                @else
                                                    Sin novedades
                                                @endif
                                            </span>
                                            <span class="material-symbols-rounded">edit</span>
                                        </button>
                                    </td>
                                    
                                    <!-- Asesor (No aplica para recibos) -->
                                    <td>
                                        <span class="text-muted">-</span>
                                    </td>
                                    
                                    <!-- Forma de pago (No aplica para recibos) -->
                                    <td>
                                        <span class="text-muted">-</span>
                                    </td>
                                    
                                    <!-- Fecha de creación -->
                                    <td>
                                        <span>{{ \Carbon\Carbon::parse($recibo['created_at'])->format('d/m/Y') }}</span>
                                    </td>
                                    
                                    <!-- Fecha estimada entrega (No aplica para recibos) -->
                                    <td>
                                        <span class="fecha-estimada-span text-muted">-</span>
                                    </td>
                                    
                                    <!-- Encargado orden (No aplica para recibos) -->
                                    <td>
                                        <span class="text-muted">-</span>
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <!-- Mensaje cuando no hay recibos -->
                            <tr>
                                <td colspan="15" class="text-center py-5">
                                    <i class="fas fa-receipt-long fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">No hay recibos de costura activos</h5>
                                    <p class="text-muted">No se encontraron recibos de costura para mostrar.</p>
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal para ver detalles del recibo -->
<div class="modal fade" id="detallesReciboModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalles del Recibo de Costura</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="detallesReciboContent">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
/* Estilos para tabla HTML tradicional */
.table-scroll-container {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    overflow-x: auto;
    overflow-y: hidden;
}

.modern-table {
    margin-bottom: 0;
    min-width: 1400px; /* Ancho mínimo para forzar scroll horizontal */
}

.table-header {
    background: linear-gradient(135deg, #1e293b 0%, #334155 100%) !important;
}

.table-header th {
    color: white !important;
    font-weight: 600 !important;
    font-size: 0.875rem !important;
    padding: 12px 8px !important;
    text-align: center !important;
    border-right: 1px solid rgba(255, 255, 255, 0.1) !important;
    vertical-align: middle !important;
}

.table-header th:last-child {
    border-right: none !important;
}

.table tbody tr {
    border-bottom: 1px solid #e2e8f0;
    transition: all 0.2s ease;
}

.table tbody tr:hover {
    background: #f8fafc !important;
}

.table tbody tr.dias-mayor-15 {
    background: #fef3c7 !important;
    border-left: 4px solid #f59e0b;
}

.table tbody tr.dias-5-9 {
    background: #f0f9ff !important;
    border-left: 4px solid #3b82f6;
}

.table tbody tr.dias-10-15 {
    background: #ecfdf5 !important;
    border-left: 4px solid #10b981;
}

.table td {
    padding: 8px !important;
    vertical-align: middle !important;
    border-right: 1px solid #e2e8f0;
    font-size: 0.875rem;
}

.table td:last-child {
    border-right: none !important;
}

/* Acciones */
.acciones-column {
    position: relative;
}

.action-view-btn {
    background: #3b82f6;
    color: white;
    border: none;
    border-radius: 6px;
    padding: 6px 10px;
    cursor: pointer;
    font-size: 0.875rem;
    transition: all 0.2s ease;
}

.action-view-btn:hover {
    background: #2563eb;
    transform: translateY(-1px);
}

.action-menu {
    position: absolute;
    top: 100%;
    right: 0;
    background: white;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    z-index: 1000;
    display: none;
    min-width: 150px;
    overflow: hidden;
}

.action-menu.show {
    display: block;
}

.action-menu-item {
    display: flex;
    align-items: center;
    padding: 8px 12px;
    color: #374151;
    text-decoration: none;
    font-size: 0.875rem;
    transition: background 0.2s ease;
}

.action-menu-item:hover {
    background: #f3f4f6;
}

.action-menu-item i {
    margin-right: 8px;
    font-size: 0.875rem;
}

/* Novedades */
.btn-edit-novedades {
    background: transparent;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    padding: 4px 8px;
    cursor: pointer;
    font-size: 0.75rem;
    display: flex;
    align-items: center;
    gap: 4px;
    transition: all 0.2s ease;
    max-width: 100%;
}

.btn-edit-novedades:hover {
    background: #f9fafb;
    border-color: #9ca3af;
}

.novedades-text {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    flex: 1;
}

.novedades-text.empty {
    color: #9ca3af;
}

.material-symbols-rounded {
    font-size: 1rem;
    color: #6b7280;
}

/* Badges */
.badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
}

.bg-info {
    background-color: #0ea5e9 !important;
    color: white !important;
}

.bg-secondary {
    background-color: #6b7280 !important;
    color: white !important;
}

.text-muted {
    color: #6b7280 !important;
}
</style>
@endpush

@push('scripts')
<script>
function verDetallesRecibo(reciboId) {
    // Mostrar modal
    const modal = new bootstrap.Modal(document.getElementById('detallesReciboModal'));
    modal.show();
    
    // Cargar detalles
    const content = document.getElementById('detallesReciboContent');
    content.innerHTML = `
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i>
            <strong>Detalles del Recibo de Costura ID:</strong> ${reciboId}
        </div>
        <div class="row">
            <div class="col-md-6">
                <h6>Información del Recibo</h6>
                <p><strong>ID:</strong> ${reciboId}</p>
                <p><strong>Tipo:</strong> COSTURA</p>
                <p><strong>Estado:</strong> Activo</p>
            </div>
            <div class="col-md-6">
                <h6>Acciones Disponibles</h6>
                <p>Puedes agregar más detalles aquí según sea necesario.</p>
            </div>
        </div>
    `;
}

// Función para menú de acciones (similar a la de registros)
document.addEventListener('click', function(e) {
    if (e.target.closest('.action-view-btn')) {
        e.preventDefault();
        e.stopPropagation();
        
        const btn = e.target.closest('.action-view-btn');
        const ordenId = btn.dataset.ordenId;
        const menu = btn.nextElementSibling;
        
        // Cerrar otros menús
        document.querySelectorAll('.action-menu').forEach(m => {
            if (m !== menu) m.classList.remove('show');
        });
        
        // Toggle este menú
        menu.classList.toggle('show');
    }
    
    // Cerrar menús al hacer clic fuera
    if (!e.target.closest('.acciones-column')) {
        document.querySelectorAll('.action-menu').forEach(m => {
            m.classList.remove('show');
        });
    }
});

// Función para novedades (similar a la de registros)
function openNovedadesModal(id, novedades) {
    // Aquí podrías implementar un modal para editar novedades si es necesario
    console.log('Editar novedades del recibo:', id, novedades);
    alert('Función de edición de novedades no implementada para recibos de costura');
}
</script>
@endpush
