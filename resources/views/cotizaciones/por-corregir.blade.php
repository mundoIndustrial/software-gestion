@extends('layouts.app')

@section('title', 'Cotizaciones por Corregir')
@section('page-title', 'Cotizaciones por Corregir')

@section('content')
<div class="p-8">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Cotizaciones por Corregir</h1>
                <p class="text-gray-600 mt-2">Total: <span class="font-semibold text-amber-600">{{ count($cotizaciones) }}</span> cotizaciones en corrección</p>
            </div>
        </div>
    </div>

    <!-- Tabla -->
    @if(count($cotizaciones) > 0)
    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-gradient-to-r from-amber-500 to-amber-600 text-white">
                        <th class="px-6 py-4 text-left text-sm font-semibold">Cotización</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold">Fecha</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold">Cliente</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold">Asesora</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold">Estado</th>
                        <th class="px-6 py-4 text-center text-sm font-semibold">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($cotizaciones as $cotizacion)
                    <tr class="hover:bg-amber-50 transition-colors duration-200">
                        <!-- Cotización -->
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-amber-100 rounded-lg flex items-center justify-center mr-3">
                                    <span class="material-symbols-rounded text-amber-600 text-lg">receipt</span>
                                </div>
                                <span class="font-semibold text-gray-900">#{{ $cotizacion->id }}</span>
                            </div>
                        </td>

                        <!-- Fecha -->
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-600">
                                {{ $cotizacion->created_at->format('d/m/Y') }}
                            </div>
                            <div class="text-xs text-gray-400">
                                {{ $cotizacion->created_at->format('h:i A') }}
                            </div>
                        </td>

                        <!-- Cliente -->
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm font-medium text-gray-900">{{ $cotizacion->cliente ?? 'N/A' }}</span>
                        </td>

                        <!-- Asesora -->
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm text-gray-600">{{ $cotizacion->asesora ?? ($cotizacion->usuario->name ?? 'N/A') }}</span>
                        </td>

                        <!-- Estado -->
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-800">
                                <span class="w-2 h-2 bg-amber-600 rounded-full mr-2"></span>
                                En Corrección
                            </span>
                        </td>

                        <!-- Acciones -->
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <div class="flex items-center justify-center space-x-2">
                                <div class="ver-menu-container" style="position: relative;">
                                    <button class="inline-flex items-center px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 transition-colors duration-200 text-sm font-medium"
                                            title="Ver detalles"
                                            onclick="verDetallesCorreccion({{ $cotizacion->id }})">
                                        <span class="material-symbols-rounded text-base mr-1">visibility</span>
                                        Ver
                                    </button>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @else
    <!-- Empty State -->
    <div class="bg-white rounded-lg shadow-lg p-12 text-center">
        <div class="mb-6">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-amber-100 rounded-full mb-4">
                <span class="material-symbols-rounded text-4xl text-amber-600">check_circle</span>
            </div>
        </div>
        <h3 class="text-xl font-semibold text-gray-900 mb-2">No hay cotizaciones por corregir</h3>
        <p class="text-gray-600">Todas las cotizaciones han sido corregidas</p>
    </div>
    @endif
</div>

<!-- Modal para ver detalles con observaciones -->
<div id="modal-detalles-correccion" class="modal-overlay" onclick="if(event.target === this) cerrarModalDetallesCorreccion();" style="z-index: 9999; background: rgba(0, 0, 0, 0.7);">
    <div class="modal-content" style="max-width: 800px;">
        <!-- Header del Modal -->
        <div style="display: flex; justify-content: space-between; align-items: center; padding: 24px; border-bottom: 1px solid #e5e7eb; background: linear-gradient(to right, #f59e0b, #fbbf24);">
            <h2 style="margin: 0; color: white; font-size: 1.5rem; font-weight: bold;">Detalles de Corrección</h2>
            <button onclick="cerrarModalDetallesCorreccion()" style="background: none; border: none; color: white; cursor: pointer; font-size: 1.5rem;">
                <span class="material-symbols-rounded">close</span>
            </button>
        </div>

        <!-- Contenido del Modal -->
        <div style="padding: 24px;">
            <!-- Observaciones -->
            <div style="margin-bottom: 24px; background: #fef3c7; border-left: 4px solid #f59e0b; padding: 16px; border-radius: 6px;">
                <h3 style="margin: 0 0 12px 0; color: #92400e; font-weight: bold;">Observaciones del Aprobador:</h3>
                <p id="observaciones-text" style="margin: 0; color: #78350f; line-height: 1.6;"></p>
            </div>

            <!-- Detalles de cotización -->
            <div id="modal-contenido-correccion" style="padding: 0;">
                <!-- Se llenará dinámicamente con JavaScript -->
            </div>

            <!-- Botón para volver -->
            <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 24px; padding-top: 24px; border-top: 1px solid #e5e7eb;">
                <button onclick="cerrarModalDetallesCorreccion()" style="background: #e5e7eb; color: #374151; padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold;">
                    Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    .material-symbols-rounded {
        font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
    }
    
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 1000;
        display: none;
        align-items: center;
        justify-content: center;
        overflow-y: auto;
    }
    
    .modal-content {
        background: white;
        border-radius: 12px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        width: 95%;
        margin: 20px auto;
    }
</style>

<script>
function verDetallesCorreccion(cotizacionId) {
    fetch(`/cotizaciones/${cotizacionId}/datos`)
        .then(response => {
            if (!response.ok) throw new Error('Error al cargar los datos');
            return response.json();
        })
        .then(data => {
            mostrarDetallesCorreccion(data);
            const modal = document.getElementById('modal-detalles-correccion');
            modal.style.setProperty('display', 'flex', 'important');
            modal.style.setProperty('visibility', 'visible', 'important');
            modal.style.setProperty('opacity', '1', 'important');
        })
        .catch(error => {
            console.error('Error:', error);
            alert('No se pudo cargar la cotización');
        });
}

function mostrarDetallesCorreccion(data) {
    const cotizacion = data.cotizacion;
    const prendas = data.prendas_cotizaciones || [];
    
    // Mostrar observaciones (desde historial o notificaciones)
    const observacionesText = document.getElementById('observaciones-text');
    observacionesText.textContent = cotizacion.observaciones || 'Sin observaciones específicas. Revisa los detalles de la cotización.';
    
    let html = `
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px;">
            <!-- Información General -->
            <div>
                <h3 style="color: #374151; font-weight: bold; margin-bottom: 16px; border-bottom: 2px solid #f59e0b; padding-bottom: 8px;">Información General</h3>
                <div style="space-y: 12px;">
                    <div style="margin-bottom: 12px;">
                        <p style="color: #6b7280; font-size: 0.875rem; margin: 0;">NÚMERO DE COTIZACIÓN</p>
                        <p style="color: #1f2937; font-weight: bold; margin: 4px 0;">#${cotizacion.numero_cotizacion}</p>
                    </div>
                    <div style="margin-bottom: 12px;">
                        <p style="color: #6b7280; font-size: 0.875rem; margin: 0;">ASESORA</p>
                        <p style="color: #1f2937; font-weight: bold; margin: 4px 0;">${cotizacion.asesora_nombre || 'N/A'}</p>
                    </div>
                    <div style="margin-bottom: 12px;">
                        <p style="color: #6b7280; font-size: 0.875rem; margin: 0;">CLIENTE</p>
                        <p style="color: #1f2937; font-weight: bold; margin: 4px 0;">${cotizacion.nombre_cliente || 'N/A'}</p>
                    </div>
                    <div style="margin-bottom: 12px;">
                        <p style="color: #6b7280; font-size: 0.875rem; margin: 0;">FECHA</p>
                        <p style="color: #1f2937; font-weight: bold; margin: 4px 0;">${new Date(cotizacion.created_at).toLocaleDateString()}</p>
                    </div>
                </div>
            </div>

            <!-- Información del Cliente -->
            <div>
                <h3 style="color: #374151; font-weight: bold; margin-bottom: 16px; border-bottom: 2px solid #f59e0b; padding-bottom: 8px;">Información del Cliente</h3>
                <div style="space-y: 12px;">
                    <div style="margin-bottom: 12px;">
                        <p style="color: #6b7280; font-size: 0.875rem; margin: 0;">EMAIL</p>
                        <p style="color: #1f2937; font-weight: bold; margin: 4px 0;">${cotizacion.email_cliente || 'N/A'}</p>
                    </div>
                    <div style="margin-bottom: 12px;">
                        <p style="color: #6b7280; font-size: 0.875rem; margin: 0;">TELÉFONO</p>
                        <p style="color: #1f2937; font-weight: bold; margin: 4px 0;">${cotizacion.telefono_cliente || 'N/A'}</p>
                    </div>
                    <div style="margin-bottom: 12px;">
                        <p style="color: #6b7280; font-size: 0.875rem; margin: 0;">CIUDAD</p>
                        <p style="color: #1f2937; font-weight: bold; margin: 4px 0;">${cotizacion.ciudad_cliente || 'N/A'}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Prendas -->
        <div style="border-top: 2px solid #e5e7eb; padding-top: 24px;">
            <h3 style="color: #374151; font-weight: bold; margin-bottom: 16px; border-bottom: 2px solid #f59e0b; padding-bottom: 8px;">Prendas Cotizadas</h3>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f3f4f6;">
                            <th style="padding: 12px; text-align: left; border-bottom: 2px solid #e5e7eb; color: #374151; font-weight: bold; font-size: 0.875rem;">PRENDA</th>
                            <th style="padding: 12px; text-align: center; border-bottom: 2px solid #e5e7eb; color: #374151; font-weight: bold; font-size: 0.875rem;">CANTIDAD</th>
                            <th style="padding: 12px; text-align: left; border-bottom: 2px solid #e5e7eb; color: #374151; font-weight: bold; font-size: 0.875rem;">PROCESOS</th>
                        </tr>
                    </thead>
                    <tbody>
    `;
    
    if (prendas.length === 0) {
        html += `
                        <tr>
                            <td colspan="3" style="padding: 20px; text-align: center; color: #6b7280;">No hay prendas en esta cotización</td>
                        </tr>
        `;
    } else {
        prendas.forEach((prenda, index) => {
            html += `
                        <tr style="${index % 2 === 0 ? 'background: #ffffff;' : 'background: #f9fafb;'}">
                            <td style="padding: 12px; border-bottom: 1px solid #e5e7eb; color: #1f2937; font-weight: 500;">${prenda.nombre_prenda}</td>
                            <td style="padding: 12px; border-bottom: 1px solid #e5e7eb; color: #1f2937; text-align: center; font-weight: 500;">${prenda.cantidad}</td>
                            <td style="padding: 12px; border-bottom: 1px solid #e5e7eb; color: #6b7280; font-size: 0.875rem;">${prenda.detalles_proceso || 'Sin procesos'}</td>
                        </tr>
            `;
        });
    }
    
    html += `
                    </tbody>
                </table>
            </div>
        </div>
    `;
    
    document.getElementById('modal-contenido-correccion').innerHTML = html;
}

function cerrarModalDetallesCorreccion() {
    const modal = document.getElementById('modal-detalles-correccion');
    modal.style.setProperty('display', 'none', 'important');
    modal.style.setProperty('visibility', 'hidden', 'important');
    modal.style.setProperty('opacity', '0', 'important');
}
</script>
@endsection
