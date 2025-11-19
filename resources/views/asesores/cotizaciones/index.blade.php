@extends('asesores.layout')

@section('title', 'Cotizaciones')
@section('page-title', 'Cotizaciones y Borradores')

@section('content')
<div class="container-fluid">
    <!-- HEADER -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <h1 style="margin: 0; font-size: 2rem; color: #333;">📋 Cotizaciones</h1>
        <a href="{{ route('asesores.pedidos.create') }}" class="btn btn-primary" style="background: #3498db; color: white; padding: 10px 20px; border-radius: 4px; text-decoration: none;">
            <i class="fas fa-plus"></i> Nueva Cotización
        </a>
    </div>

    <!-- TABS -->
    <div style="display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 2px solid #ecf0f1;">
        <button class="tab-btn active" onclick="mostrarTab('cotizaciones')" style="padding: 10px 20px; background: none; border: none; border-bottom: 3px solid #3498db; cursor: pointer; font-weight: bold; color: #333;">
            📤 Cotizaciones Enviadas
        </button>
        <button class="tab-btn" onclick="mostrarTab('borradores')" style="padding: 10px 20px; background: none; border: none; border-bottom: 3px solid transparent; cursor: pointer; font-weight: bold; color: #999;">
            📝 Borradores
        </button>
    </div>

    <!-- COTIZACIONES ENVIADAS -->
    <div id="tab-cotizaciones" class="tab-content">
        @if($cotizaciones->count() > 0)
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">
                @foreach($cotizaciones as $cot)
                    <div style="background: white; border: 1px solid #ecf0f1; border-radius: 8px; padding: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px;">
                            <div>
                                <h3 style="margin: 0 0 5px 0; color: #333;">{{ $cot->cliente ?? 'Sin cliente' }}</h3>
                                <p style="margin: 0; color: #999; font-size: 0.9rem;">ID: #{{ $cot->id }}</p>
                            </div>
                            <span style="background: #d4edda; color: #155724; padding: 5px 10px; border-radius: 20px; font-size: 0.8rem; font-weight: bold;">
                                {{ ucfirst($cot->estado) }}
                            </span>
                        </div>

                        <div style="background: #f8f9fa; padding: 10px; border-radius: 4px; margin-bottom: 15px; font-size: 0.9rem;">
                            <p style="margin: 5px 0;"><strong>Fecha:</strong> {{ $cot->created_at->format('d/m/Y H:i') }}</p>
                            <p style="margin: 5px 0;"><strong>Asesora:</strong> {{ $cot->usuario->name ?? 'N/A' }}</p>
                        </div>

                        <div style="display: flex; gap: 10px;">
                            <a href="{{ route('asesores.cotizaciones.show', $cot->id) }}" class="btn" style="flex: 1; background: #3498db; color: white; padding: 8px; border-radius: 4px; text-align: center; text-decoration: none; font-size: 0.9rem;">
                                👁️ Ver
                            </a>
                            <button onclick="eliminarCotizacion({{ $cot->id }})" class="btn" style="flex: 1; background: #e74c3c; color: white; padding: 8px; border-radius: 4px; border: none; cursor: pointer; font-size: 0.9rem;">
                                🗑️ Eliminar
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- PAGINACIÓN -->
            <div style="margin-top: 30px; display: flex; justify-content: center; gap: 10px;">
                {{ $cotizaciones->links() }}
            </div>
        @else
            <div style="background: #f0f7ff; border: 2px dashed #3498db; border-radius: 8px; padding: 40px; text-align: center;">
                <p style="margin: 0; color: #666; font-size: 1.1rem;">
                    📭 No hay cotizaciones enviadas aún
                </p>
                <a href="{{ route('asesores.pedidos.create') }}" style="display: inline-block; margin-top: 15px; background: #3498db; color: white; padding: 10px 20px; border-radius: 4px; text-decoration: none;">
                    Crear Primera Cotización
                </a>
            </div>
        @endif
    </div>

    <!-- BORRADORES -->
    <div id="tab-borradores" class="tab-content" style="display: none;">
        @if($borradores->count() > 0)
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">
                @foreach($borradores as $borrador)
                    <div style="background: white; border: 1px solid #ecf0f1; border-radius: 8px; padding: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px;">
                            <div>
                                <h3 style="margin: 0 0 5px 0; color: #333;">{{ $borrador->cliente ?? 'Sin cliente' }}</h3>
                                <p style="margin: 0; color: #999; font-size: 0.9rem;">ID: #{{ $borrador->id }}</p>
                            </div>
                            <span style="background: #fff3cd; color: #856404; padding: 5px 10px; border-radius: 20px; font-size: 0.8rem; font-weight: bold;">
                                BORRADOR
                            </span>
                        </div>

                        <div style="background: #f8f9fa; padding: 10px; border-radius: 4px; margin-bottom: 15px; font-size: 0.9rem;">
                            <p style="margin: 5px 0;"><strong>Fecha:</strong> {{ $borrador->created_at->format('d/m/Y H:i') }}</p>
                            <p style="margin: 5px 0;"><strong>Asesora:</strong> {{ $borrador->usuario->name ?? 'N/A' }}</p>
                        </div>

                        <div style="display: flex; gap: 10px;">
                            <a href="{{ route('asesores.cotizaciones.edit-borrador', $borrador->id) }}" class="btn" style="flex: 1; background: #f39c12; color: white; padding: 8px; border-radius: 4px; text-align: center; text-decoration: none; font-size: 0.9rem;">
                                ✏️ Editar
                            </a>
                            <button onclick="eliminarBorrador({{ $borrador->id }})" class="btn" style="flex: 1; background: #e74c3c; color: white; padding: 8px; border-radius: 4px; border: none; cursor: pointer; font-size: 0.9rem;">
                                🗑️ Eliminar
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- PAGINACIÓN -->
            <div style="margin-top: 30px; display: flex; justify-content: center; gap: 10px;">
                {{ $borradores->links() }}
            </div>
        @else
            <div style="background: #f0f7ff; border: 2px dashed #3498db; border-radius: 8px; padding: 40px; text-align: center;">
                <p style="margin: 0; color: #666; font-size: 1.1rem;">
                    📭 No hay borradores guardados
                </p>
                <a href="{{ route('asesores.pedidos.create') }}" style="display: inline-block; margin-top: 15px; background: #3498db; color: white; padding: 10px 20px; border-radius: 4px; text-decoration: none;">
                    Crear Nuevo Borrador
                </a>
            </div>
        @endif
    </div>
</div>

<script>
function mostrarTab(tab) {
    // Ocultar todos los tabs
    document.querySelectorAll('.tab-content').forEach(el => {
        el.style.display = 'none';
    });
    
    // Desactivar todos los botones
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.style.borderBottomColor = 'transparent';
        btn.style.color = '#999';
    });
    
    // Mostrar tab seleccionado
    document.getElementById('tab-' + tab).style.display = 'block';
    
    // Activar botón seleccionado
    event.target.style.borderBottomColor = '#3498db';
    event.target.style.color = '#333';
}

function eliminarCotizacion(id) {
    if (confirm('¿Estás seguro de que deseas eliminar esta cotización?')) {
        fetch(`/asesores/cotizaciones/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('✓ Cotización eliminada');
                location.reload();
            } else {
                alert('✗ Error al eliminar');
            }
        });
    }
}

function eliminarBorrador(id) {
    if (confirm('¿Estás seguro de que deseas eliminar este borrador?')) {
        fetch(`/asesores/cotizaciones/${id}/borrador`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('✓ Borrador eliminado');
                location.reload();
            } else {
                alert('✗ Error al eliminar');
            }
        });
    }
}
</script>
@endsection
