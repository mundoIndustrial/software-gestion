@extends('layouts.app')

@section('title', 'Cotizaciones')
@section('page-title', 'Cotizaciones Pendientes de Aprobación')

@section('content')
<div class="p-8">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Cotizaciones Pendientes</h1>
                <p class="text-gray-600 mt-2">Total: <span class="font-semibold text-orange-600">{{ count($cotizaciones) }}</span> cotizaciones</p>
            </div>
        </div>
    </div>

    <!-- Tabla -->
    @if(count($cotizaciones) > 0)
    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-gradient-to-r from-orange-500 to-orange-600 text-white">
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
                    <tr class="hover:bg-orange-50 transition-colors duration-200">
                        <!-- Cotización -->
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center mr-3">
                                    <span class="material-symbols-rounded text-orange-600 text-lg">receipt</span>
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
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                <span class="w-2 h-2 bg-orange-600 rounded-full mr-2"></span>
                                Pendiente
                            </span>
                        </td>

                        <!-- Acciones -->
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <div class="flex items-center justify-center space-x-2">
                                <div class="ver-menu-container" style="position: relative;">
                                    <button class="inline-flex items-center px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors duration-200 text-sm font-medium"
                                            title="Ver opciones"
                                            onclick="toggleVerMenu(event, {{ $cotizacion->id }})">
                                        <span class="material-symbols-rounded text-base mr-1">visibility</span>
                                        Ver
                                    </button>
                                    <div class="ver-submenu" id="ver-menu-{{ $cotizacion->id }}" style="position: absolute; top: 100%; right: 0; background: white; border: 1px solid #e5e7eb; border-radius: 6px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); z-index: 10; min-width: 180px; margin-top: 4px; display: none;">
                                        <button class="submenu-item" onclick="verComparacion({{ $cotizacion->id }})" style="display: flex; align-items: center; gap: 8px; width: 100%; padding: 12px 16px; text-align: left; border: none; background: none; cursor: pointer; color: #374151; font-size: 0.875rem; transition: all 0.2s; border-bottom: 1px solid #e5e7eb;">
                                            <span class="material-symbols-rounded" style="font-size: 1.2rem;">compare_arrows</span>
                                            Comparar
                                        </button>
                                        <button class="submenu-item" onclick="verDetalles({{ $cotizacion->id }})" style="display: flex; align-items: center; gap: 8px; width: 100%; padding: 12px 16px; text-align: left; border: none; background: none; cursor: pointer; color: #374151; font-size: 0.875rem; transition: all 0.2s;">
                                            <span class="material-symbols-rounded" style="font-size: 1.2rem;">description</span>
                                            Detalles
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Botón Aprobar -->
                                <button onclick="aprobarCotizacionAprobador({{ $cotizacion->id }})" 
                                        class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors duration-200 text-sm font-medium"
                                        title="Aprobar cotización">
                                    <span class="material-symbols-rounded text-base mr-1">check_circle</span>
                                    Aprobar
                                </button>
                                
                                <!-- Botón Corregir -->
                                <button onclick="abrirFormularioCorregir({{ $cotizacion->id }})" 
                                        class="inline-flex items-center px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 transition-colors duration-200 text-sm font-medium"
                                        title="Enviar a corrección">
                                    <span class="material-symbols-rounded text-base mr-1">edit</span>
                                    Corregir
                                </button>
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
            <div class="inline-flex items-center justify-center w-20 h-20 bg-orange-100 rounded-full mb-4">
                <span class="material-symbols-rounded text-4xl text-orange-600">inbox</span>
            </div>
        </div>
        <h3 class="text-xl font-semibold text-gray-900 mb-2">No hay cotizaciones pendientes</h3>
        <p class="text-gray-600">Todas las cotizaciones han sido aprobadas o rechazadas</p>
    </div>
    @endif
</div>

<!-- Modal para comparar cotización -->
<div id="modal-comparar-cotizacion" class="modal-overlay" onclick="if(event.target === this) cerrarModalComparar();">
    <div class="modal-content">
        <!-- Header del Modal -->
        <div style="display: flex; justify-content: space-between; align-items: center; padding: 24px; border-bottom: 1px solid #e5e7eb; background: linear-gradient(to right, #f97316, #fb923c);">
            <h2 style="margin: 0; color: white; font-size: 1.5rem; font-weight: bold;">Comparar Cotización</h2>
            <button onclick="cerrarModalComparar()" style="background: none; border: none; color: white; cursor: pointer; font-size: 1.5rem;">
                <span class="material-symbols-rounded">close</span>
            </button>
        </div>

        <!-- Contenido del Modal -->
        <div id="modal-contenido-comparar" style="padding: 24px; display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
            <!-- Se llenará dinámicamente con JavaScript -->
        </div>
    </div>
</div>

<!-- Modal para detalles de cotización -->
<div id="modal-detalles-cotizacion" class="modal-overlay" onclick="if(event.target === this) cerrarModalCotizacion();">
    <div class="modal-content">
        <!-- Header del Modal -->
        <div style="display: flex; justify-content: space-between; align-items: center; padding: 24px; border-bottom: 1px solid #e5e7eb; background: linear-gradient(to right, #f97316, #fb923c);">
            <h2 style="margin: 0; color: white; font-size: 1.5rem; font-weight: bold;">Detalles de Cotización</h2>
            <button onclick="cerrarModalCotizacion()" style="background: none; border: none; color: white; cursor: pointer; font-size: 1.5rem;">
                <span class="material-symbols-rounded">close</span>
            </button>
        </div>

        <!-- Contenido del Modal -->
        <div id="modal-contenido-cotizacion" style="padding: 24px; overflow-y: auto; max-height: calc(90vh - 100px);">
            <!-- Se llenará dinámicamente con JavaScript -->
        </div>
    </div>
</div>

<!-- Modal para corrección de cotización -->
<div id="modal-corregir-cotizacion" class="modal-overlay" onclick="if(event.target === this) cerrarModalCorregir();" style="z-index: 9999; background: rgba(0, 0, 0, 0.7);">
    <div class="modal-content" style="max-width: 600px;">
        <!-- Header del Modal -->
        <div style="display: flex; justify-content: space-between; align-items: center; padding: 24px; border-bottom: 1px solid #e5e7eb; background: linear-gradient(to right, #f59e0b, #fbbf24);">
            <h2 style="margin: 0; color: white; font-size: 1.5rem; font-weight: bold;">Enviar al Contador</h2>
            <button onclick="cerrarModalCorregir()" style="background: none; border: none; color: white; cursor: pointer; font-size: 1.5rem;">
                <span class="material-symbols-rounded">close</span>
            </button>
        </div>

        <!-- Contenido del Modal -->
        <div style="padding: 24px;">
            <form id="form-corregir-cotizacion">
                <div style="margin-bottom: 20px;">
                    <label style="display: block; color: #374151; font-weight: bold; margin-bottom: 8px;">Observaciones para el Contador</label>
                    <textarea id="observaciones-correccion" name="observaciones" rows="6" style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; font-family: Arial, sans-serif; resize: none;" placeholder="Describe los ajustes o correcciones que el contador debe realizar..." required></textarea>
                </div>

                <div style="display: flex; gap: 12px; justify-content: flex-end;">
                    <button type="button" onclick="cerrarModalCorregir()" style="background: #e5e7eb; color: #374151; padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold;">
                        Cancelar
                    </button>
                    <button type="submit" style="background: #f59e0b; color: white; padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold;">
                        Enviar a Contador
                    </button>
                </div>
            </form>
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
    
    #modal-comparar-cotizacion .modal-content {
        max-width: 1200px;
    }
    
    #modal-detalles-cotizacion .modal-content {
        max-width: 900px;
    }
    
    #modal-corregir-cotizacion .modal-content {
        max-width: 600px;
    }
    
    .ver-submenu {
        display: none !important;
    }
    
    .ver-submenu.visible {
        display: block !important;
    }
    
    .submenu-item:hover {
        background: #f3f4f6;
    }
</style>

<script>
// Mapeo de estados en enums a labels legibles
const estadosLabel = {
    'BORRADOR': 'Borrador',
    'ENVIADA_CONTADOR': 'Enviada a Contador',
    'APROBADA_CONTADOR': 'Aprobada por Contador',
    'APROBADA_COTIZACIONES': 'Aprobada por Aprobador',
    'EN_CORRECCION': 'En Corrección',
    'CONVERTIDA_PEDIDO': 'Convertida a Pedido',
    'FINALIZADA': 'Finalizada',
    'EN_PRODUCCION': 'En Producción'
};

function transformarEstado(estado) {
    return estadosLabel[estado] || estado;
}

function toggleVerMenu(event, cotizacionId) {
    event.stopPropagation();
    const menu = document.getElementById(`ver-menu-${cotizacionId}`);
    
    // Cerrar todos los menús abiertos excepto este
    document.querySelectorAll('.ver-submenu.visible').forEach(m => {
        if (m.id !== `ver-menu-${cotizacionId}`) {
            m.classList.remove('visible');
        }
    });
    
    // Alternar menú actual
    menu.classList.toggle('visible');
}

function verComparacion(cotizacionId) {
    const menuIds = document.querySelectorAll('[id^="ver-menu-"]');
    menuIds.forEach(m => m.classList.remove('visible'));
    
    fetch(`/cotizaciones/${cotizacionId}/datos`)
        .then(response => {
            if (!response.ok) throw new Error('Error al cargar los datos');
            return response.json();
        })
        .then(data => {
            mostrarComparacionCotizacion(data);
            const modal = document.getElementById('modal-comparar-cotizacion');
            modal.style.setProperty('display', 'flex', 'important');
            modal.style.setProperty('visibility', 'visible', 'important');
            modal.style.setProperty('opacity', '1', 'important');
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire('Error', 'No se pudo cargar la comparación', 'error');
        });
}

function verDetalles(cotizacionId) {
    const menuIds = document.querySelectorAll('[id^="ver-menu-"]');
    menuIds.forEach(m => m.classList.remove('visible'));
    
    fetch(`/cotizaciones/${cotizacionId}/datos`)
        .then(response => {
            if (!response.ok) throw new Error('Error al cargar los datos');
            return response.json();
        })
        .then(data => {
            mostrarDetallesCotizacion(data);
            const modal = document.getElementById('modal-detalles-cotizacion');
            modal.style.setProperty('display', 'flex', 'important');
            modal.style.setProperty('visibility', 'visible', 'important');
            modal.style.setProperty('opacity', '1', 'important');
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire('Error', 'No se pudo cargar los detalles', 'error');
        });
}

function mostrarComparacionCotizacion(data) {
    const contenido = document.getElementById('modal-contenido-comparar');
    
    const cotizacion = data.cotizacion;
    const prendas = data.prendas_cotizaciones || [];
    const ordenesRelacionadas = data.ordenes_relacionadas || [];
    
    let html = `
        <!-- Columna Izquierda: Cotización -->
        <div style="border-right: 2px solid #e5e7eb; padding-right: 24px;">
            <h3 style="color: #f97316; font-weight: bold; margin-bottom: 16px;">Cotización #${cotizacion.numero_cotizacion}</h3>
            
            <div style="background: #f9fafb; padding: 16px; border-radius: 8px; margin-bottom: 16px;">
                <p><strong>Asesora:</strong> ${cotizacion.asesora_nombre || 'N/A'}</p>
                <p><strong>Empresa:</strong> ${cotizacion.empresa || 'N/A'}</p>
                <p><strong>Cliente:</strong> ${cotizacion.nombre_cliente || 'N/A'}</p>
                <p><strong>Fecha:</strong> ${new Date(cotizacion.created_at).toLocaleDateString()}</p>
                <p><strong>Estado:</strong> <span style="background: #dbeafe; color: #1e40af; padding: 4px 8px; border-radius: 4px; font-size: 0.875rem;">${transformarEstado(cotizacion.estado)}</span></p>
            </div>

            <h4 style="font-weight: bold; margin-bottom: 12px; color: #374151;">Prendas:</h4>
            <div style="background: #f9fafb; padding: 12px; border-radius: 8px; max-height: 400px; overflow-y: auto;">
    `;
    
    if (prendas.length === 0) {
        html += '<p style="color: #6b7280;">No hay prendas en esta cotización</p>';
    } else {
        prendas.forEach((prenda, index) => {
            html += `
                <div style="padding: 8px; border-bottom: 1px solid #e5e7eb; ${index === 0 ? '' : 'margin-top: 8px;'}">
                    <p style="margin: 4px 0; font-size: 0.875rem;"><strong>${prenda.nombre_prenda}</strong></p>
                    <p style="margin: 4px 0; font-size: 0.875rem; color: #6b7280;">Cantidad: ${prenda.cantidad}</p>
                    ${prenda.detalles_proceso ? `<p style="margin: 4px 0; font-size: 0.75rem; color: #6b7280;">${prenda.detalles_proceso}</p>` : ''}
                </div>
            `;
        });
    }
    
    html += `
            </div>
        </div>
        
        <!-- Columna Derecha: Órdenes Relacionadas -->
        <div style="padding-left: 24px;">
            <h3 style="color: #059669; font-weight: bold; margin-bottom: 16px;">Órdenes de Producción</h3>
    `;
    
    if (ordenesRelacionadas.length === 0) {
        html += `<p style="color: #6b7280; background: #f0fdf4; padding: 16px; border-radius: 8px;">No hay órdenes de producción asociadas</p>`;
    } else {
        ordenesRelacionadas.forEach(orden => {
            html += `
                <div style="background: #f0fdf4; padding: 16px; border-radius: 8px; margin-bottom: 12px; border-left: 4px solid #059669;">
                    <p><strong>Orden #${orden.numero_orden}</strong></p>
                    <p style="font-size: 0.875rem; color: #6b7280;">Fecha: ${new Date(orden.created_at).toLocaleDateString()}</p>
                    <p style="font-size: 0.875rem; color: #6b7280;">Estado: ${transformarEstado(orden.estado)}</p>
                </div>
            `;
        });
    }
    
    html += '</div>';
    
    contenido.innerHTML = html;
}

function mostrarDetallesCotizacion(data) {
    const contenido = document.getElementById('modal-contenido-cotizacion');
    const cotizacion = data.cotizacion;
    const prendas = data.prendas_cotizaciones || [];
    
    let estadoBadge = '';
    const estadoLabel = transformarEstado(cotizacion.estado);
    estadoBadge = `<span style="background: #dbeafe; color: #1e40af; padding: 6px 12px; border-radius: 6px; font-weight: bold;">${estadoLabel}</span>`;
    
    let html = `
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
            <!-- Información General -->
            <div>
                <h3 style="color: #374151; font-weight: bold; margin-bottom: 16px; border-bottom: 2px solid #f97316; padding-bottom: 8px;">Información General</h3>
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
                        <p style="color: #6b7280; font-size: 0.875rem; margin: 0;">EMPRESA</p>
                        <p style="color: #1f2937; font-weight: bold; margin: 4px 0;">${cotizacion.empresa || 'N/A'}</p>
                    </div>
                    <div style="margin-bottom: 12px;">
                        <p style="color: #6b7280; font-size: 0.875rem; margin: 0;">CLIENTE</p>
                        <p style="color: #1f2937; font-weight: bold; margin: 4px 0;">${cotizacion.nombre_cliente || 'N/A'}</p>
                    </div>
                    <div style="margin-bottom: 12px;">
                        <p style="color: #6b7280; font-size: 0.875rem; margin: 0;">FECHA CREACIÓN</p>
                        <p style="color: #1f2937; font-weight: bold; margin: 4px 0;">${new Date(cotizacion.created_at).toLocaleDateString()}</p>
                    </div>
                    <div style="margin-bottom: 12px;">
                        <p style="color: #6b7280; font-size: 0.875rem; margin: 0;">ESTADO</p>
                        <p style="margin: 4px 0;">${estadoBadge}</p>
                    </div>
                </div>
            </div>

            <!-- Información del Cliente -->
            <div>
                <h3 style="color: #374151; font-weight: bold; margin-bottom: 16px; border-bottom: 2px solid #f97316; padding-bottom: 8px;">Información del Cliente</h3>
                <div style="space-y: 12px;">
                    <div style="margin-bottom: 12px;">
                        <p style="color: #6b7280; font-size: 0.875rem; margin: 0;">NOMBRE COMPLETO</p>
                        <p style="color: #1f2937; font-weight: bold; margin: 4px 0;">${cotizacion.nombre_cliente || 'N/A'}</p>
                    </div>
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
        <div style="margin-top: 24px; border-top: 2px solid #e5e7eb; padding-top: 24px;">
            <h3 style="color: #374151; font-weight: bold; margin-bottom: 16px; border-bottom: 2px solid #f97316; padding-bottom: 8px;">Prendas Cotizadas</h3>
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
    
    contenido.innerHTML = html;
}

function cerrarModalComparar() {
    const modal = document.getElementById('modal-comparar-cotizacion');
    modal.style.setProperty('display', 'none', 'important');
    modal.style.setProperty('visibility', 'hidden', 'important');
    modal.style.setProperty('opacity', '0', 'important');
}

function cerrarModalCotizacion() {
    const modal = document.getElementById('modal-detalles-cotizacion');
    modal.style.setProperty('display', 'none', 'important');
    modal.style.setProperty('visibility', 'hidden', 'important');
    modal.style.setProperty('opacity', '0', 'important');
}

function cerrarModalCorregir() {
    const modal = document.getElementById('modal-corregir-cotizacion');
    modal.style.setProperty('display', 'none', 'important');
    modal.style.setProperty('visibility', 'hidden', 'important');
    modal.style.setProperty('opacity', '0', 'important');
    document.getElementById('form-corregir-cotizacion').reset();
}

function abrirFormularioCorregir(cotizacionId) {
    const modal = document.getElementById('modal-corregir-cotizacion');
    
    if (modal) {
        // Remover aria-hidden antes de mostrar
        modal.removeAttribute('aria-hidden');
        modal.style.setProperty('display', 'flex', 'important');
        modal.style.setProperty('visibility', 'visible', 'important');
        modal.style.setProperty('opacity', '1', 'important');
        
        // Dar foco al textarea
        setTimeout(() => {
            const textarea = document.getElementById('observaciones-correccion');
            if (textarea) {
                textarea.focus();
            }
        }, 100);
    }
    
    document.getElementById('observaciones-correccion').value = '';
    
    // Configurar el form para enviar la corrección
    document.getElementById('form-corregir-cotizacion').onsubmit = function(e) {
        e.preventDefault();
        enviarCorreccion(cotizacionId);
    };
}

function enviarCorreccion(cotizacionId) {
    const observaciones = document.getElementById('observaciones-correccion').value.trim();
    
    if (!observaciones) {
        Swal.fire('Error', 'Por favor ingresa las observaciones', 'error');
        return;
    }
    
    fetch(`/cotizaciones/${cotizacionId}/rechazar`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            observaciones: observaciones,
            motivo: 'Requiere correcciones'
        })
    })
    .then(response => {
        if (!response.ok) throw new Error('Error al enviar corrección');
        return response.json();
    })
    .then(data => {
        cerrarModalCorregir();
        Swal.fire('Éxito', 'Cotización reenviada a la asesora con observaciones', 'success').then(() => {
            location.reload();
        });
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire('Error', 'No se pudo enviar la corrección: ' + error.message, 'error');
    });
}

function aprobarCotizacionAprobador(cotizacionId) {
    Swal.fire({
        title: '¿Aprobar Cotización?',
        text: 'Esta cotización será aprobada y el cliente podrá proceder con el pedido',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Sí, Aprobar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(`/cotizaciones/${cotizacionId}/aprobar-aprobador`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => {
                if (!response.ok) throw new Error('Error al aprobar');
                return response.json();
            })
            .then(data => {
                Swal.fire('Éxito', 'Cotización aprobada correctamente', 'success').then(() => {
                    location.reload();
                });
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('Error', 'No se pudo aprobar la cotización: ' + error.message, 'error');
            });
        }
    });
}

// Cerrar menú al hacer clic en otro lugar
document.addEventListener('click', function(event) {
    const comparar = document.getElementById('modal-comparar-cotizacion');
    const detalles = document.getElementById('modal-detalles-cotizacion');
    
    if ((comparar && event.target === comparar) || (detalles && event.target === detalles)) {
        if (event.target === comparar) cerrarModalComparar();
        if (event.target === detalles) cerrarModalCotizacion();
    } else if (!event.target.closest('.ver-menu-container')) {
        document.querySelectorAll('.ver-submenu.visible').forEach(m => {
            m.classList.remove('visible');
        });
    }
});
</script>
@endsection
