@extends('layouts.asesores')

@section('title', 'Reportes')
@section('page-title', 'Mis Reportes')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/asesores/create-friendly.css') }}">
<style>
    .reportes-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .reporte-card {
        background: white;
        border: 1px solid #ecf0f1;
        border-radius: 8px;
        padding: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
    }
    
    .reporte-card:hover {
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        border-color: #3498db;
    }
    
    .reporte-tipo {
        display: inline-block;
        background: #e3f2fd;
        color: #1976d2;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
        margin-bottom: 10px;
    }
    
    .reporte-titulo {
        font-size: 1.1rem;
        font-weight: 600;
        color: #333;
        margin: 10px 0;
    }
    
    .reporte-fecha {
        font-size: 0.9rem;
        color: #999;
        margin: 8px 0;
    }
    
    .reporte-acciones {
        display: flex;
        gap: 10px;
        margin-top: 15px;
        padding-top: 15px;
        border-top: 1px solid #ecf0f1;
    }
    
    .reporte-acciones button {
        flex: 1;
        padding: 8px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 0.9rem;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    
    .btn-editar {
        background: #f39c12;
        color: white;
    }
    
    .btn-editar:hover {
        background: #e67e22;
    }
    
    .btn-eliminar {
        background: #e74c3c;
        color: white;
    }
    
    .btn-eliminar:hover {
        background: #c0392b;
    }
</style>
@endpush

@section('content')
<div class="friendly-form-fullscreen">
    <!-- TÍTULO PRINCIPAL -->
    <div style="text-align: center; margin-bottom: 15px; padding: 10px 0; border-bottom: 2px solid #3498db;">
        <h1 style="margin: 0; font-size: 1.5rem; color: #333; font-weight: bold;">REPORTES</h1>
        <p style="margin: 5px 0 0 0; color: #666; font-size: 0.9rem;">Gestiona tus reportes de ventas, producción y más</p>
    </div>

    <!-- INFORMACIÓN DE LA ASESORA Y FECHA -->
    <div style="background: #f0f7ff; border-left: 4px solid #3498db; padding: 15px; margin-bottom: 20px; border-radius: 4px;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
            <div>
                <p style="margin: 0; font-size: 0.9rem; color: #666;">
                    <strong>{{ Auth::user()->genero === 'F' ? 'ASESORA COMERCIAL' : 'ASESOR COMERCIAL' }}:</strong>
                    {{ Auth::user()->name }}
                </p>
            </div>
            <div>
                <p style="margin: 0; font-size: 0.9rem; color: #666;">
                    <strong>FECHA:</strong>
                    <span id="fechaActual"></span>
                </p>
            </div>
        </div>
    </div>

    <!-- BOTÓN CREAR REPORTE -->
    <div style="margin-bottom: 30px;">
        <button type="button" class="btn-add-product-friendly" onclick="abrirModalReporte()" style="width: auto; display: inline-flex; align-items: center; gap: 10px;">
            <i class="fas fa-plus-circle"></i> CREAR NUEVO REPORTE
        </button>
    </div>

    <!-- GRID DE REPORTES -->
    @if($reportes->count() > 0)
        <div class="reportes-grid">
            @foreach($reportes as $reporte)
                <div class="reporte-card">
                    <div class="reporte-tipo">
                        @switch($reporte->tipo)
                            @case('ventas')
                                📈 Ventas
                                @break
                            @case('produccion')
                                🏭 Producción
                                @break
                            @case('clientes')
                                👥 Clientes
                                @break
                            @default
                                 General
                        @endswitch
                    </div>
                    
                    <div class="reporte-titulo">{{ $reporte->titulo }}</div>
                    
                    @if($reporte->descripcion)
                        <p style="font-size: 0.9rem; color: #666; margin: 10px 0;">{{ Str::limit($reporte->descripcion, 100) }}</p>
                    @endif
                    
                    <div class="reporte-fecha">
                        <strong>Período:</strong>
                        {{ $reporte->fecha_inicio ? $reporte->fecha_inicio->format('d/m/Y') : 'Sin fecha' }} 
                        a 
                        {{ $reporte->fecha_fin ? $reporte->fecha_fin->format('d/m/Y') : 'Sin fecha' }}
                    </div>
                    
                    <div class="reporte-fecha">
                        <strong>Creado:</strong> {{ $reporte->created_at->format('d/m/Y h:i A') }}
                    </div>
                    
                    <div class="reporte-acciones">
                        <button type="button" class="btn-editar" onclick="editarReporte({{ $reporte->id }}, '{{ $reporte->titulo }}', '{{ $reporte->descripcion }}', '{{ $reporte->tipo }}', '{{ $reporte->fecha_inicio }}', '{{ $reporte->fecha_fin }}')">
                             Editar
                        </button>
                        <button type="button" class="btn-eliminar" onclick="eliminarReporte({{ $reporte->id }})">
                            🗑️ Eliminar
                        </button>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- PAGINACIÓN -->
        @if($reportes->hasPages())
            <div style="display: flex; justify-content: center; margin-top: 30px;">
                {{ $reportes->links() }}
            </div>
        @endif
    @else
        <div style="background: #f0f7ff; border: 2px dashed #3498db; border-radius: 8px; padding: 60px 20px; text-align: center;">
            <p style="margin: 0; color: #666; font-size: 1.1rem; margin-bottom: 20px;">
                📭 No hay reportes creados aún
            </p>
            <button type="button" class="btn-add-product-friendly" onclick="abrirModalReporte()">
                <i class="fas fa-plus-circle"></i> CREAR PRIMER REPORTE
            </button>
        </div>
    @endif
</div>

<!-- MODAL REPORTE -->
<div id="modalReporte" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; overflow-y: auto;">
    <div style="background: white; border-radius: 8px; padding: 30px; width: 90%; max-width: 600px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); margin: 20px auto;">
        <h2 style="margin: 0 0 20px 0; color: #333; font-size: 1.3rem;">Nuevo Reporte</h2>
        
        <form id="formReporte">
            @csrf
            <input type="hidden" id="reporteId" value="">
            
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #333;">
                    <i class="fas fa-heading"></i> Título *
                </label>
                <input type="text" id="titulo" name="titulo" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem;" placeholder="Ej: Reporte de Ventas Octubre">
            </div>

            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #333;">
                    <i class="fas fa-tag"></i> Tipo *
                </label>
                <select id="tipo" name="tipo" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem;">
                    <option value="">Seleccionar tipo</option>
                    <option value="ventas">📈 Ventas</option>
                    <option value="produccion">🏭 Producción</option>
                    <option value="clientes">👥 Clientes</option>
                    <option value="general"> General</option>
                </select>
            </div>

            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #333;">
                    <i class="fas fa-file-alt"></i> Descripción
                </label>
                <textarea id="descripcion" name="descripcion" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem; resize: vertical; min-height: 80px;" placeholder="Describe el contenido del reporte..."></textarea>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                <div>
                    <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #333;">
                        <i class="fas fa-calendar"></i> Fecha Inicio
                    </label>
                    <input type="date" id="fecha_inicio" name="fecha_inicio" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem;">
                </div>
                <div>
                    <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #333;">
                        <i class="fas fa-calendar"></i> Fecha Fin
                    </label>
                    <input type="date" id="fecha_fin" name="fecha_fin" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem;">
                </div>
            </div>

            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn-submit" style="flex: 1;">
                    <i class="fas fa-save"></i> GUARDAR REPORTE
                </button>
                <button type="button" onclick="cerrarModalReporte()" class="btn-prev" style="flex: 1;">
                    <i class="fas fa-times"></i> CANCELAR
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Mostrar fecha actual
document.addEventListener('DOMContentLoaded', function() {
    const fechaActualElement = document.getElementById('fechaActual');
    if (fechaActualElement) {
        const hoy = new Date();
        const dia = String(hoy.getDate()).padStart(2, '0');
        const mes = String(hoy.getMonth() + 1).padStart(2, '0');
        const año = hoy.getFullYear();
        fechaActualElement.textContent = `${dia}/${mes}/${año}`;
    }
});

function abrirModalReporte() {
    document.getElementById('modalReporte').style.display = 'flex';
    document.getElementById('formReporte').reset();
    document.getElementById('reporteId').value = '';
    document.querySelector('#modalReporte h2').textContent = 'Nuevo Reporte';
}

function cerrarModalReporte() {
    document.getElementById('modalReporte').style.display = 'none';
}

function editarReporte(id, titulo, descripcion, tipo, fecha_inicio, fecha_fin) {
    document.getElementById('reporteId').value = id;
    document.getElementById('titulo').value = titulo;
    document.getElementById('descripcion').value = descripcion;
    document.getElementById('tipo').value = tipo;
    document.getElementById('fecha_inicio').value = fecha_inicio;
    document.getElementById('fecha_fin').value = fecha_fin;
    document.querySelector('#modalReporte h2').textContent = 'Editar Reporte';
    document.getElementById('modalReporte').style.display = 'flex';
}

function eliminarReporte(id) {
    if (confirm('¿Estás seguro de que deseas eliminar este reporte?')) {
        fetch(`/asesores/reportes/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('✓ Reporte eliminado');
                location.reload();
            }
        });
    }
}

document.getElementById('formReporte').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const reporteId = document.getElementById('reporteId').value;
    const url = reporteId ? `/asesores/reportes/${reporteId}` : '/asesores/reportes';
    const method = reporteId ? 'PATCH' : 'POST';
    
    const formData = new FormData(this);
    
    fetch(url, {
        method: method,
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✓ ' + data.message);
            location.reload();
        }
    });
});

// Cerrar modal al hacer clic fuera
document.getElementById('modalReporte').addEventListener('click', function(e) {
    if (e.target === this) {
        cerrarModalReporte();
    }
});
</script>
@endsection
