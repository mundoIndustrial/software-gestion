@extends('asesores.layout')

@section('title', 'Cotizaciones')
@section('page-title', 'Cotizaciones y Borradores')

@section('content')
<div class="container-fluid">
    <!-- HEADER MEJORADO -->
    <div style="background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%); border-radius: 12px; padding: 25px 30px; margin-bottom: 30px; box-shadow: 0 4px 15px rgba(44, 62, 80, 0.2);">
        <div style="display: flex; justify-content: space-between; align-items: center; gap: 20px;">
            <!-- TÍTULO Y DESCRIPCIÓN -->
            <div>
                <h1 style="margin: 0 0 5px 0; font-size: 2rem; color: white; font-weight: 700;">📋 Cotizaciones</h1>
                <p style="margin: 0; color: rgba(255,255,255,0.8); font-size: 0.9rem;">Gestiona tus cotizaciones y borradores</p>
            </div>
            
            <!-- BUSCADOR MEJORADO -->
            <div style="flex: 1; max-width: 350px; position: relative;">
                <i class="fas fa-search" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #999; font-size: 0.9rem;"></i>
                <input type="text" id="buscador" placeholder="Buscar por cliente..." style="width: 100%; padding: 12px 15px 12px 40px; border: none; border-radius: 8px; font-size: 0.9rem; background: white; box-shadow: 0 2px 8px rgba(0,0,0,0.1); transition: all 0.3s;" onkeyup="filtrarCotizaciones()" onfocus="this.style.boxShadow='0 4px 12px rgba(0,0,0,0.15)'" onblur="this.style.boxShadow='0 2px 8px rgba(0,0,0,0.1)'">
            </div>
            
            <!-- BOTÓN NUEVA -->
            <a href="{{ route('asesores.pedidos.create') }}" style="background: white; color: #2c3e50; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 0.9rem; display: inline-flex; align-items: center; gap: 8px; transition: all 0.3s; box-shadow: 0 2px 8px rgba(0,0,0,0.1);" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.15)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.1)'">
                <i class="fas fa-plus"></i> Nueva
            </a>
        </div>
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
            <!-- VISTA TARJETAS -->
            <div id="vista-tarjetas-cot" style="display: none; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 15px;">
                @foreach($cotizaciones as $cot)
                    <div style="background: white; border: 1px solid #ecf0f1; border-radius: 6px; padding: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); transition: all 0.3s;">
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 8px;">
                            <div style="flex: 1; min-width: 0;">
                                <h4 style="margin: 0; color: #333; font-size: 0.95rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $cot->cliente ?? 'Sin cliente' }}</h4>
                                <p style="margin: 2px 0 0 0; color: #999; font-size: 0.8rem;">ID: #{{ $cot->id }}</p>
                            </div>
                            <span style="background: #d4edda; color: #155724; padding: 3px 8px; border-radius: 12px; font-size: 0.7rem; font-weight: bold; white-space: nowrap; margin-left: 5px;">
                                {{ ucfirst($cot->estado) }}
                            </span>
                        </div>
                        <div style="background: #f8f9fa; padding: 8px; border-radius: 4px; margin-bottom: 10px; font-size: 0.8rem;">
                            <p style="margin: 2px 0;"><strong>Fecha:</strong> {{ $cot->created_at->format('d/m/Y') }}</p>
                            <p style="margin: 2px 0;"><strong>Asesora:</strong> {{ $cot->usuario->name ?? 'N/A' }}</p>
                        </div>
                        <a href="{{ route('asesores.cotizaciones.show', $cot->id) }}" style="display: block; background: #3498db; color: white; padding: 6px; border-radius: 4px; text-align: center; text-decoration: none; font-size: 0.85rem; font-weight: bold;">
                            👁️ Ver
                        </a>
                    </div>
                @endforeach
            </div>

            <!-- VISTA TABLA -->
            <div id="vista-tabla-cot" style="display: block; overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; background: white; border-radius: 6px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.08);">
                    <thead style="background: #f8f9fa; border-bottom: 2px solid #ecf0f1;">
                        <tr>
                            <th style="padding: 12px; text-align: left; font-weight: bold; color: #333; font-size: 0.9rem;">ID</th>
                            <th style="padding: 12px; text-align: left; font-weight: bold; color: #333; font-size: 0.9rem;">Cliente</th>
                            <th style="padding: 12px; text-align: left; font-weight: bold; color: #333; font-size: 0.9rem;">Fecha</th>
                            <th style="padding: 12px; text-align: left; font-weight: bold; color: #333; font-size: 0.9rem;">Estado</th>
                            <th style="padding: 12px; text-align: center; font-weight: bold; color: #333; font-size: 0.9rem;">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($cotizaciones as $cot)
                            <tr style="border-bottom: 1px solid #ecf0f1; transition: background 0.2s;">
                                <td style="padding: 12px; color: #666; font-size: 0.9rem;">#{{ $cot->id }}</td>
                                <td style="padding: 12px; color: #333; font-size: 0.9rem; font-weight: 500;">{{ $cot->cliente ?? 'Sin cliente' }}</td>
                                <td style="padding: 12px; color: #666; font-size: 0.9rem;">{{ $cot->created_at->format('d/m/Y H:i') }}</td>
                                <td style="padding: 12px;">
                                    <span style="background: #d4edda; color: #155724; padding: 4px 10px; border-radius: 12px; font-size: 0.8rem; font-weight: bold;">
                                        {{ ucfirst($cot->estado) }}
                                    </span>
                                </td>
                                <td style="padding: 12px; text-align: center;">
                                    <a href="{{ route('asesores.cotizaciones.show', $cot->id) }}" style="background: #3498db; color: white; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 0.85rem; font-weight: bold; display: inline-block;">
                                        Ver
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- PAGINACIÓN -->
            <div style="margin-top: 30px;">
                <div class="pagination-info" style="text-align: center; margin-bottom: 15px; color: #666; font-size: 0.9rem;">
                    Mostrando {{ $cotizaciones->firstItem() ?? 0 }}-{{ $cotizaciones->lastItem() ?? 0 }} de {{ $cotizaciones->total() }} registros
                </div>
                @if($cotizaciones->hasPages())
                    <div style="display: flex; justify-content: center; gap: 5px; flex-wrap: wrap;">
                        {{-- Botón primera página --}}
                        <a href="{{ $cotizaciones->url(1) }}" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; text-decoration: none; color: #333; background: white; font-size: 0.9rem; transition: all 0.3s; {{ $cotizaciones->currentPage() == 1 ? 'opacity: 0.5; cursor: not-allowed;' : 'cursor: pointer;' }}" {{ $cotizaciones->currentPage() == 1 ? 'onclick="return false;"' : '' }}>
                            ⟨⟨
                        </a>

                        {{-- Botón página anterior --}}
                        <a href="{{ $cotizaciones->previousPageUrl() }}" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; text-decoration: none; color: #333; background: white; font-size: 0.9rem; transition: all 0.3s; {{ $cotizaciones->currentPage() == 1 ? 'opacity: 0.5; cursor: not-allowed;' : 'cursor: pointer;' }}" {{ $cotizaciones->currentPage() == 1 ? 'onclick="return false;"' : '' }}>
                            ⟨
                        </a>

                        {{-- Números de página --}}
                        @php
                            $start = max(1, $cotizaciones->currentPage() - 2);
                            $end = min($cotizaciones->lastPage(), $cotizaciones->currentPage() + 2);
                        @endphp

                        @for($i = $start; $i <= $end; $i++)
                            <a href="{{ $cotizaciones->url($i) }}" style="padding: 8px 12px; border: 1px solid {{ $i == $cotizaciones->currentPage() ? '#3498db' : '#ddd' }}; border-radius: 4px; text-decoration: none; color: {{ $i == $cotizaciones->currentPage() ? 'white' : '#333' }}; background: {{ $i == $cotizaciones->currentPage() ? '#3498db' : 'white' }}; font-size: 0.9rem; font-weight: {{ $i == $cotizaciones->currentPage() ? 'bold' : 'normal' }}; transition: all 0.3s; cursor: pointer;">
                                {{ $i }}
                            </a>
                        @endfor

                        {{-- Botón página siguiente --}}
                        <a href="{{ $cotizaciones->nextPageUrl() }}" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; text-decoration: none; color: #333; background: white; font-size: 0.9rem; transition: all 0.3s; {{ $cotizaciones->currentPage() == $cotizaciones->lastPage() ? 'opacity: 0.5; cursor: not-allowed;' : 'cursor: pointer;' }}" {{ $cotizaciones->currentPage() == $cotizaciones->lastPage() ? 'onclick="return false;"' : '' }}>
                            ⟩
                        </a>

                        {{-- Botón última página --}}
                        <a href="{{ $cotizaciones->url($cotizaciones->lastPage()) }}" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; text-decoration: none; color: #333; background: white; font-size: 0.9rem; transition: all 0.3s; {{ $cotizaciones->currentPage() == $cotizaciones->lastPage() ? 'opacity: 0.5; cursor: not-allowed;' : 'cursor: pointer;' }}" {{ $cotizaciones->currentPage() == $cotizaciones->lastPage() ? 'onclick="return false;"' : '' }}>
                            ⟩⟩
                        </a>
                    </div>
                @endif
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
            <!-- VISTA TARJETAS -->
            <div id="vista-tarjetas-bor" style="display: none; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 15px;">
                @foreach($borradores as $borrador)
                    <div style="background: white; border: 1px solid #ecf0f1; border-radius: 6px; padding: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); transition: all 0.3s;">
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 8px;">
                            <div style="flex: 1; min-width: 0;">
                                <h4 style="margin: 0; color: #333; font-size: 0.95rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $borrador->cliente ?? 'Sin cliente' }}</h4>
                                <p style="margin: 2px 0 0 0; color: #999; font-size: 0.8rem;">ID: #{{ $borrador->id }}</p>
                            </div>
                            <span style="background: #fff3cd; color: #856404; padding: 3px 8px; border-radius: 12px; font-size: 0.7rem; font-weight: bold; white-space: nowrap; margin-left: 5px;">
                                BORRADOR
                            </span>
                        </div>
                        <div style="background: #f8f9fa; padding: 8px; border-radius: 4px; margin-bottom: 10px; font-size: 0.8rem;">
                            <p style="margin: 2px 0;"><strong>Fecha:</strong> {{ $borrador->created_at->format('d/m/Y') }}</p>
                            <p style="margin: 2px 0;"><strong>Asesora:</strong> {{ $borrador->usuario->name ?? 'N/A' }}</p>
                        </div>
                        <div style="display: flex; gap: 6px;">
                            <a href="{{ route('asesores.cotizaciones.edit-borrador', $borrador->id) }}" style="flex: 1; background: #f39c12; color: white; padding: 6px; border-radius: 4px; text-align: center; text-decoration: none; font-size: 0.8rem; font-weight: bold;">
                                ✏️ Editar
                            </a>
                            <button onclick="eliminarBorrador({{ $borrador->id }})" style="flex: 1; background: #e74c3c; color: white; padding: 6px; border-radius: 4px; border: none; cursor: pointer; font-size: 0.8rem; font-weight: bold;">
                                🗑️
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- VISTA TABLA -->
            <div id="vista-tabla-bor" style="display: block; overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; background: white; border-radius: 6px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.08);">
                    <thead style="background: #f8f9fa; border-bottom: 2px solid #ecf0f1;">
                        <tr>
                            <th style="padding: 12px; text-align: left; font-weight: bold; color: #333; font-size: 0.9rem;">ID</th>
                            <th style="padding: 12px; text-align: left; font-weight: bold; color: #333; font-size: 0.9rem;">Cliente</th>
                            <th style="padding: 12px; text-align: left; font-weight: bold; color: #333; font-size: 0.9rem;">Fecha</th>
                            <th style="padding: 12px; text-align: center; font-weight: bold; color: #333; font-size: 0.9rem;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($borradores as $borrador)
                            <tr style="border-bottom: 1px solid #ecf0f1; transition: background 0.2s;">
                                <td style="padding: 12px; color: #666; font-size: 0.9rem;">#{{ $borrador->id }}</td>
                                <td style="padding: 12px; color: #333; font-size: 0.9rem; font-weight: 500;">{{ $borrador->cliente ?? 'Sin cliente' }}</td>
                                <td style="padding: 12px; color: #666; font-size: 0.9rem;">{{ $borrador->created_at->format('d/m/Y H:i') }}</td>
                                <td style="padding: 12px; text-align: center;">
                                    <a href="{{ route('asesores.cotizaciones.edit-borrador', $borrador->id) }}" style="background: #f39c12; color: white; padding: 6px 10px; border-radius: 4px; text-decoration: none; font-size: 0.85rem; font-weight: bold; display: inline-block; margin-right: 5px;">
                                        Editar
                                    </a>
                                    <button onclick="eliminarBorrador({{ $borrador->id }})" style="background: #e74c3c; color: white; padding: 6px 10px; border-radius: 4px; border: none; cursor: pointer; font-size: 0.85rem; font-weight: bold; display: inline-block;">
                                        Eliminar
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- PAGINACIÓN -->
            <div style="margin-top: 30px;">
                <div class="pagination-info" style="text-align: center; margin-bottom: 15px; color: #666; font-size: 0.9rem;">
                    Mostrando {{ $borradores->firstItem() ?? 0 }}-{{ $borradores->lastItem() ?? 0 }} de {{ $borradores->total() }} registros
                </div>
                @if($borradores->hasPages())
                    <div style="display: flex; justify-content: center; gap: 5px; flex-wrap: wrap;">
                        {{-- Botón primera página --}}
                        <a href="{{ $borradores->url(1) }}" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; text-decoration: none; color: #333; background: white; font-size: 0.9rem; transition: all 0.3s; {{ $borradores->currentPage() == 1 ? 'opacity: 0.5; cursor: not-allowed;' : 'cursor: pointer;' }}" {{ $borradores->currentPage() == 1 ? 'onclick="return false;"' : '' }}>
                            ⟨⟨
                        </a>

                        {{-- Botón página anterior --}}
                        <a href="{{ $borradores->previousPageUrl() }}" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; text-decoration: none; color: #333; background: white; font-size: 0.9rem; transition: all 0.3s; {{ $borradores->currentPage() == 1 ? 'opacity: 0.5; cursor: not-allowed;' : 'cursor: pointer;' }}" {{ $borradores->currentPage() == 1 ? 'onclick="return false;"' : '' }}>
                            ⟨
                        </a>

                        {{-- Números de página --}}
                        @php
                            $start = max(1, $borradores->currentPage() - 2);
                            $end = min($borradores->lastPage(), $borradores->currentPage() + 2);
                        @endphp

                        @for($i = $start; $i <= $end; $i++)
                            <a href="{{ $borradores->url($i) }}" style="padding: 8px 12px; border: 1px solid {{ $i == $borradores->currentPage() ? '#3498db' : '#ddd' }}; border-radius: 4px; text-decoration: none; color: {{ $i == $borradores->currentPage() ? 'white' : '#333' }}; background: {{ $i == $borradores->currentPage() ? '#3498db' : 'white' }}; font-size: 0.9rem; font-weight: {{ $i == $borradores->currentPage() ? 'bold' : 'normal' }}; transition: all 0.3s; cursor: pointer;">
                                {{ $i }}
                            </a>
                        @endfor

                        {{-- Botón página siguiente --}}
                        <a href="{{ $borradores->nextPageUrl() }}" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; text-decoration: none; color: #333; background: white; font-size: 0.9rem; transition: all 0.3s; {{ $borradores->currentPage() == $borradores->lastPage() ? 'opacity: 0.5; cursor: not-allowed;' : 'cursor: pointer;' }}" {{ $borradores->currentPage() == $borradores->lastPage() ? 'onclick="return false;"' : '' }}>
                            ⟩
                        </a>

                        {{-- Botón última página --}}
                        <a href="{{ $borradores->url($borradores->lastPage()) }}" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; text-decoration: none; color: #333; background: white; font-size: 0.9rem; transition: all 0.3s; {{ $borradores->currentPage() == $borradores->lastPage() ? 'opacity: 0.5; cursor: not-allowed;' : 'cursor: pointer;' }}" {{ $borradores->currentPage() == $borradores->lastPage() ? 'onclick="return false;"' : '' }}>
                            ⟩⟩
                        </a>
                    </div>
                @endif
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
let vistaActual = 'tarjetas';

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

function cambiarVista(vista) {
    vistaActual = vista;
    
    // Actualizar botones
    document.getElementById('btn-tarjetas').style.background = vista === 'tarjetas' ? '#3498db' : 'transparent';
    document.getElementById('btn-tarjetas').style.color = vista === 'tarjetas' ? 'white' : '#666';
    document.getElementById('btn-tabla').style.background = vista === 'tabla' ? '#3498db' : 'transparent';
    document.getElementById('btn-tabla').style.color = vista === 'tabla' ? 'white' : '#666';
    
    // Cambiar vista en cotizaciones
    document.getElementById('vista-tarjetas-cot').style.display = vista === 'tarjetas' ? 'grid' : 'none';
    document.getElementById('vista-tabla-cot').style.display = vista === 'tabla' ? 'block' : 'none';
    
    // Cambiar vista en borradores
    document.getElementById('vista-tarjetas-bor').style.display = vista === 'tarjetas' ? 'grid' : 'none';
    document.getElementById('vista-tabla-bor').style.display = vista === 'tabla' ? 'block' : 'none';
}

function filtrarCotizaciones() {
    const busqueda = document.getElementById('buscador').value.toLowerCase();
    
    // Filtrar tarjetas de cotizaciones
    const tarjetasCot = document.querySelectorAll('#vista-tarjetas-cot > div');
    tarjetasCot.forEach(tarjeta => {
        const cliente = tarjeta.querySelector('h4').textContent.toLowerCase();
        tarjeta.style.display = cliente.includes(busqueda) ? 'block' : 'none';
    });
    
    // Filtrar tabla de cotizaciones
    const filasCot = document.querySelectorAll('#vista-tabla-cot tbody tr');
    filasCot.forEach(fila => {
        const cliente = fila.querySelector('td:nth-child(2)').textContent.toLowerCase();
        fila.style.display = cliente.includes(busqueda) ? 'table-row' : 'none';
    });
    
    // Filtrar tarjetas de borradores
    const tarjetasBor = document.querySelectorAll('#vista-tarjetas-bor > div');
    tarjetasBor.forEach(tarjeta => {
        const cliente = tarjeta.querySelector('h4').textContent.toLowerCase();
        tarjeta.style.display = cliente.includes(busqueda) ? 'block' : 'none';
    });
    
    // Filtrar tabla de borradores
    const filasBor = document.querySelectorAll('#vista-tabla-bor tbody tr');
    filasBor.forEach(fila => {
        const cliente = fila.querySelector('td:nth-child(2)').textContent.toLowerCase();
        fila.style.display = cliente.includes(busqueda) ? 'table-row' : 'none';
    });
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
