@extends('supervisor-pedidos.layout')

@section('title', 'Pendientes Logo')
@section('page-title', 'Pendientes Logo')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <span class="material-symbols-rounded me-2">brush</span>
                        Pendientes Logo
                    </h5>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="exportarExcel()">
                            <span class="material-symbols-rounded me-1">download</span>
                            Exportar Excel
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="recargarDatos()">
                            <span class="material-symbols-rounded me-1">refresh</span>
                            Recargar
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    @if($procesosConCantidad->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped table-hover" id="tabla-pendientes">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Fecha de Creación</th>
                                        <th>N° Recibo</th>
                                        <th>Cliente</th>
                                        <th>Cantidad de Prendas</th>
                                        <th>Asesor</th>
                                        <th>Logo</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($procesosConCantidad as $proceso)
                                        <tr>
                                            <td>{{ \Carbon\Carbon::parse($proceso->fecha_creacion)->format('d/m/Y H:i') }}</td>
                                            <td>
                                                <span class="badge bg-primary">{{ $proceso->numero_recibo ?? 'Sin asignar' }}</span>
                                            </td>
                                            <td>{{ $proceso->cliente }}</td>
                                            <td>
                                                <span class="badge bg-info">{{ $proceso->cantidad_total_prendas }}</span>
                                            </td>
                                            <td>{{ $proceso->asesor }}</td>
                                            <td>
                                                @switch($proceso->tipo_recibo)
                                                    @case('BORDADO')
                                                        <span class="badge bg-purple text-white">
                                                            <span class="material-symbols-rounded me-1" style="font-size: 14px;">brush</span>
                                                            Bordado
                                                        </span>
                                                        @break
                                                    @case('ESTAMPADO')
                                                        <span class="badge bg-orange text-white">
                                                            <span class="material-symbols-rounded me-1" style="font-size: 14px;">palette</span>
                                                            Estampado
                                                        </span>
                                                        @break
                                                    @case('SUBLIMADO')
                                                        <span class="badge bg-cyan text-white">
                                                            <span class="material-symbols-rounded me-1" style="font-size: 14px;">waves</span>
                                                            Sublimado
                                                        </span>
                                                        @break
                                                    @case('DTF')
                                                        <span class="badge bg-pink text-white">
                                                            <span class="material-symbols-rounded me-1" style="font-size: 14px;">print</span>
                                                            DTF
                                                        </span>
                                                        @break
                                                    @default
                                                        <span class="badge bg-secondary">{{ $proceso->tipo_recibo }}</span>
                                                @endswitch
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                                            onclick="verDetalles({{ $proceso->recibo_id }}, '{{ $proceso->tipo_recibo }}')">
                                                        <span class="material-symbols-rounded" style="font-size: 16px;">visibility</span>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-success" 
                                                            onclick="aprobarProceso({{ $proceso->recibo_id }})">
                                                        <span class="material-symbols-rounded" style="font-size: 16px;">check</span>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-warning" 
                                                            onclick="verPedido({{ $proceso->pedido_id }})">
                                                        <span class="material-symbols-rounded" style="font-size: 16px;">description</span>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <span class="material-symbols-rounded text-muted" style="font-size: 64px;">check_circle</span>
                            <h5 class="text-muted mt-3">No hay pendientes</h5>
                            <p class="text-muted">No hay procesos de bordado, estampado, sublimado o DTF pendientes en este momento.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para ver detalles del proceso -->
<div class="modal fade" id="modalDetalles" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalles del Proceso</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detalles-contenido">
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-success" id="btn-aprobar-modal">Aprobar</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .bg-purple { background-color: #6f42c1 !important; }
    .bg-orange { background-color: #fd7e14 !important; }
    .bg-cyan { background-color: #17a2b8 !important; }
    .bg-pink { background-color: #e83e8c !important; }
    
    .table-responsive {
        max-height: 70vh;
        overflow-y: auto;
    }
    
    .badge {
        font-size: 0.85em;
    }
    
    .material-symbols-rounded {
        vertical-align: middle;
    }
</style>
@endpush

@push('scripts')
<script>
function verDetalles(procesoId, tipoRecibo) {
    // Mostrar modal con loading
    const modal = new bootstrap.Modal(document.getElementById('modalDetalles'));
    document.getElementById('detalles-contenido').innerHTML = `
        <div class="text-center">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
            <p class="mt-2">Cargando detalles...</p>
        </div>
    `;
    
    // Configurar botón aprobar
    document.getElementById('btn-aprobar-modal').onclick = function() {
        aprobarProceso(procesoId);
        modal.hide();
    };
    
    modal.show();
    
    // Cargar detalles del proceso
    fetch(`/supervisor-pedidos/procesos/${procesoId}/detalles`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderizarDetalles(data.data);
            } else {
                document.getElementById('detalles-contenido').innerHTML = `
                    <div class="alert alert-danger">
                        Error: ${data.message}
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('detalles-contenido').innerHTML = `
                <div class="alert alert-danger">
                    Error al cargar los detalles del proceso.
                </div>
            `;
        });
}

function renderizarDetalles(data) {
    let html = `
        <div class="row">
            <div class="col-md-6">
                <h6>Información General</h6>
                <table class="table table-sm">
                    <tr><td><strong>Prenda:</strong></td><td>${data.nombre_prenda}</td></tr>
                    <tr><td><strong>Tipo:</strong></td><td>${data.tipo_recibo}</td></tr>
                    <tr><td><strong>Estado:</strong></td><td><span class="badge bg-warning">${data.estado}</span></td></tr>
                    <tr><td><strong>Observaciones:</strong></td><td>${data.observaciones || 'N/A'}</td></tr>
                </table>
            </div>
            <div class="col-md-6">
                <h6>Tallas y Cantidades</h6>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Género</th>
                                <th>Talla</th>
                                <th>Cantidad</th>
                            </tr>
                        </thead>
                        <tbody>
    `;
    
    // Agregar tallas
    if (data.tallas && data.tallas.length > 0) {
        data.tallas.forEach(talla => {
            html += `
                <tr>
                    <td>${talla.genero}</td>
                    <td>${talla.talla}</td>
                    <td>${talla.cantidad}</td>
                </tr>
            `;
        });
    } else {
        html += `<tr><td colspan="3" class="text-center">No hay tallas registradas</td></tr>`;
    }
    
    html += `
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    `;
    
    // Agregar imágenes si hay
    if (data.imagenes && data.imagenes.length > 0) {
        html += `
            <div class="row mt-3">
                <div class="col-12">
                    <h6>Imágenes del Proceso</h6>
                    <div class="row">
        `;
        
        data.imagenes.forEach(imagen => {
            html += `
                <div class="col-md-3 mb-3">
                    <img src="/storage/${imagen.ruta_webp || imagen.ruta_original}" 
                         class="img-fluid img-thumbnail" 
                         alt="Imagen del proceso"
                         onclick="window.open('/storage/${imagen.ruta_webp || imagen.ruta_original}', '_blank')"
                         style="cursor: pointer;">
                </div>
            `;
        });
        
        html += `
                    </div>
                </div>
            </div>
        `;
    }
    
    document.getElementById('detalles-contenido').innerHTML = html;
}

function aprobarProceso(procesoId) {
    if (!confirm('¿Está seguro de aprobar este proceso?')) {
        return;
    }
    
    fetch(`/supervisor-pedidos/procesos/${procesoId}/aprobar`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Proceso aprobado correctamente');
            recargarDatos();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al aprobar el proceso');
    });
}

function verPedido(pedidoId) {
    window.open(`/supervisor-pedidos/${pedidoId}`, '_blank');
}

function recargarDatos() {
    location.reload();
}

function exportarExcel() {
    // Implementar exportación a Excel si es necesario
    alert('Función de exportación a Excel en desarrollo');
}

// Inicializar DataTable si está disponible
$(document).ready(function() {
    if ($.fn.DataTable) {
        $('#tabla-pendientes').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es.json'
            },
            order: [[0, 'desc']],
            responsive: true,
            pageLength: 25
        });
    }
});
</script>
@endpush
