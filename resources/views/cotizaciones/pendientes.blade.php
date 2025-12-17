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
                <p class="text-gray-600 mt-2">Total: <span class="font-semibold text-blue-600">{{ count($cotizaciones) }}</span> cotizaciones</p>
            </div>
        </div>
    </div>

    <!-- Tabla -->
    @if(count($cotizaciones) > 0)
    <div class="bg-white rounded-lg shadow-lg" style="overflow: visible;">
        <div class="overflow-x-auto" style="overflow-y: visible;">
            <table class="w-full">
                <thead>
                    <tr class="bg-gradient-to-r from-blue-500 to-blue-600 text-white">
                        <th class="px-6 py-4 text-left text-sm font-semibold">Cotización</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold">Fecha Creación</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold">Fecha Envío a Aprobador</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold">Cliente</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold">Asesora</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold">Estado</th>
                        <th class="px-6 py-4 text-center text-sm font-semibold">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($cotizaciones as $cotizacion)
                    <tr class="hover:bg-blue-50 transition-colors duration-200">
                        <!-- Cotización -->
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
                                    <span class="material-symbols-rounded text-blue-600 text-lg">receipt</span>
                                </div>
                                <span class="font-semibold text-gray-900">#{{ $cotizacion->id }}</span>
                            </div>
                        </td>

                        <!-- Fecha Creación -->
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-600">
                                {{ $cotizacion->created_at->format('d/m/Y') }}
                            </div>
                            <div class="text-xs text-gray-400">
                                {{ $cotizacion->created_at->format('h:i A') }}
                            </div>
                        </td>

                        <!-- Fecha Envío a Aprobador -->
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($cotizacion->fecha_enviado_a_aprobador)
                                <div class="text-sm text-gray-600">
                                    {{ $cotizacion->fecha_enviado_a_aprobador->format('d/m/Y') }}
                                </div>
                                <div class="text-xs text-gray-400">
                                    {{ $cotizacion->fecha_enviado_a_aprobador->format('h:i A') }}
                                </div>
                            @else
                                <span class="text-xs text-gray-400">Pendiente</span>
                            @endif
                        </td>

                        <!-- Cliente -->
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm font-medium text-gray-900">{{ $cotizacion->cliente?->nombre ?? 'N/A' }}</span>
                        </td>

                        <!-- Asesora -->
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm text-gray-600">{{ $cotizacion->asesora ?? ($cotizacion->usuario->name ?? 'N/A') }}</span>
                        </td>

                        <!-- Estado -->
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                <span class="w-2 h-2 bg-blue-600 rounded-full mr-2"></span>
                                Pendiente
                            </span>
                        </td>

                        <!-- Acciones -->
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <div class="flex items-center justify-center space-x-2">
                                <!-- Botón Ver (sin submenu) -->
                                <button onclick="verComparacion({{ $cotizacion->id }})" 
                                        class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors duration-200 text-sm font-medium"
                                        title="Ver cotización">
                                    <span class="material-symbols-rounded text-base mr-1">visibility</span>
                                    Ver
                                </button>
                                
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
            <div class="inline-flex items-center justify-center w-20 h-20 bg-blue-100 rounded-full mb-4">
                <span class="material-symbols-rounded text-4xl text-blue-600">inbox</span>
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
        <div style="display: flex; justify-content: space-between; align-items: center; padding: 24px; border-bottom: 1px solid #e5e7eb; background: linear-gradient(to right, #3b82f6, #1e40af);">
            <h2 style="margin: 0; color: white; font-size: 1.5rem; font-weight: bold;">Cotización</h2>
            <button onclick="cerrarModalComparar()" style="background: none; border: none; color: white; cursor: pointer; font-size: 1.5rem;">
                <span class="material-symbols-rounded">close</span>
            </button>
        </div>

        <!-- Contenido del Modal - Con scroll vertical -->
        <div id="modal-contenido-comparar" style="padding: 24px; display: flex; flex-direction: column; max-height: 70vh; overflow-y: auto;">
            <!-- Se llenará dinámicamente con JavaScript -->
        </div>
    </div>
</div>

<!-- Modal para corrección de cotización -->
<div id="modal-corregir-cotizacion" class="modal-overlay" onclick="if(event.target === this) cerrarModalCorregir();" style="z-index: 9999; background: rgba(0, 0, 0, 0.7);">
    <div class="modal-content" style="max-width: 600px;">
        <!-- Header del Modal -->
        <div style="display: flex; justify-content: space-between; align-items: center; padding: 24px; border-bottom: 1px solid #e5e7eb; background: linear-gradient(to right, #3b82f6, #1e40af);">
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
                    <button type="submit" style="background: #3b82f6; color: white; padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold;">
                        Enviar a Contador
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para ver imágenes -->
<div id="modal-imagenes" class="modal-overlay" onclick="if(event.target === this) cerrarModalImagenes();" style="z-index: 10000; background: rgba(0, 0, 0, 0.95); display: none; align-items: center; justify-content: center;">
    <div class="modal-content" style="width: 95vw; height: 95vh; max-width: 1400px; max-height: 850px; background: #1f2937; display: flex; flex-direction: column;">
        <!-- Header del Modal -->
        <div style="display: flex; justify-content: space-between; align-items: center; padding: 24px; border-bottom: 1px solid #374151; flex-shrink: 0;">
            <h2 id="modal-imagenes-titulo" style="margin: 0; color: white; font-size: 1.5rem; font-weight: bold;"></h2>
            <button onclick="cerrarModalImagenes()" style="background: none; border: none; color: white; cursor: pointer; font-size: 1.5rem;">
                <span class="material-symbols-rounded">close</span>
            </button>
        </div>

        <!-- Contenido del Modal -->
        <div style="padding: 24px; text-align: center; flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; overflow: auto;">
            <img id="modal-imagenes-img" src="" alt="Imagen" style="width: 100%; height: 100%; object-fit: contain; border-radius: 8px; margin-bottom: 20px;">
            
            <!-- Navegación -->
            <div id="modal-imagenes-nav" style="display: flex; gap: 12px; justify-content: center; align-items: center; flex-wrap: wrap; flex-shrink: 0;">
                <button onclick="imagenAnterior()" style="background: #3b82f6; color: white; padding: 10px 16px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; hover: #2563eb;">
                    <span class="material-symbols-rounded" style="vertical-align: middle;">chevron_left</span>
                </button>
                <span id="modal-imagenes-contador" style="color: white; font-weight: bold; min-width: 100px;"></span>
                <button onclick="imagenSiguiente()" style="background: #3b82f6; color: white; padding: 10px 16px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold;">
                    <span class="material-symbols-rounded" style="vertical-align: middle;">chevron_right</span>
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
    
    #modal-comparar-cotizacion .modal-content {
        max-width: 1200px;
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
    'APROBADO_PARA_PEDIDO': 'Aprobada para Pedido',
    'EN_CORRECCION': 'En Corrección',
    'CONVERTIDA_PEDIDO': 'Convertida a Pedido',
    'FINALIZADA': 'Finalizada',
    'EN_PRODUCCION': 'En Producción'
};

function transformarEstado(estado) {
    return estadosLabel[estado] || estado;
}

function verComparacion(cotizacionId) {
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

function mostrarComparacionCotizacion(data) {
    const contenido = document.getElementById('modal-contenido-comparar');
    
    // Función para convertir markdown bold
    const convertMarkdownBold = (texto) => {
        return texto.replace(/\*\*\*(.*?)\*\*\*/g, '<strong>$1</strong>')
                    .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
    };
    
    // Función para procesar la descripción con formato
    const procesarDescripcion = (descripcion) => {
        if (!descripcion || descripcion === 'N/A') {
            return '<em style="color: #999; font-size: 0.75rem;">Sin descripción</em>';
        }
        
        const lineas = descripcion.split('\n');
        let htmlResultado = '';
        
        lineas.forEach((linea) => {
            const lineaTrimmed = linea.trim();
            
            if (lineaTrimmed === '') {
                htmlResultado += '<br>';
            } else if (lineaTrimmed.startsWith('PRENDA')) {
                htmlResultado += '<strong style="font-size: 11px; display: block; margin-top: 8px;">' + convertMarkdownBold(lineaTrimmed) + '</strong>';
            } else if (lineaTrimmed.includes(':') && (lineaTrimmed.includes('DESCRIPCION') || lineaTrimmed.includes('Tallas') || lineaTrimmed.includes('Reflectivo') || lineaTrimmed.includes('Bolsillos') || lineaTrimmed.includes('Botón') || lineaTrimmed.includes('Broche') || lineaTrimmed.includes('Manga'))) {
                htmlResultado += '<strong style="font-size: 10px; display: block; margin-top: 6px;">' + convertMarkdownBold(lineaTrimmed) + '</strong>';
            } else if (lineaTrimmed.startsWith('•') || lineaTrimmed.startsWith('.')) {
                htmlResultado += '<div style="margin-left: 12px; font-size: 10px;">' + convertMarkdownBold(lineaTrimmed) + '</div>';
            } else if (lineaTrimmed.startsWith('-') && lineaTrimmed.length === 1) {
                htmlResultado += '<br>';
            } else if (lineaTrimmed.includes(':') && lineaTrimmed.includes('|')) {
                htmlResultado += '<div style="font-size: 10px; margin: 2px 0;">' + convertMarkdownBold(lineaTrimmed) + '</div>';
            } else {
                htmlResultado += '<div style="font-size: 10px; margin: 2px 0;">' + convertMarkdownBold(lineaTrimmed) + '</div>';
            }
        });
        
        return htmlResultado;
    };
    
    const cotizacion = data.cotizacion;
    const prendas = data.prendas_cotizaciones || [];
    
    let html = `
        <!-- Información de la Cotización -->
        <div style="margin-bottom: 24px; padding: 16px; background: #f9fafb; border-radius: 8px; border-left: 4px solid #3b82f6;">
            <h3 style="color: #3b82f6; font-weight: bold; margin: 0 0 16px 0;">Cotización #${cotizacion.numero_cotizacion}</h3>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <div>
                    <p style="color: #6b7280; font-size: 0.875rem; margin: 0;">ASESORA</p>
                    <p style="color: #1f2937; font-weight: bold; margin: 4px 0 0 0;">${cotizacion.asesora_nombre || 'N/A'}</p>
                </div>
                <div>
                    <p style="color: #6b7280; font-size: 0.875rem; margin: 0;">CLIENTE</p>
                    <p style="color: #1f2937; font-weight: bold; margin: 4px 0 0 0;">${cotizacion.nombre_cliente || 'N/A'}</p>
                </div>
                <div>
                    <p style="color: #6b7280; font-size: 0.875rem; margin: 0;">FECHA</p>
                    <p style="color: #1f2937; font-weight: bold; margin: 4px 0 0 0;">${new Date(cotizacion.created_at).toLocaleDateString()}</p>
                </div>
                <div>
                    <p style="color: #6b7280; font-size: 0.875rem; margin: 0;">ESTADO</p>
                    <p style="margin: 4px 0 0 0;"><span style="background: #dbeafe; color: #1e40af; padding: 4px 8px; border-radius: 4px; font-size: 0.875rem; font-weight: bold;">${transformarEstado(cotizacion.estado)}</span></p>
                </div>
            </div>
        </div>

        <!-- Prendas de la Cotización -->
        <div style="margin-top: 24px;">
            <h4 style="font-weight: bold; margin: 0 0 12px 0; color: #374151;">Prendas Cotizadas</h4>
            <div style="background: #ffffff; border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden;">
    `;
    
    if (prendas.length === 0) {
        html += '<div style="padding: 16px; color: #6b7280; text-align: center;">No hay prendas en esta cotización</div>';
    } else {
        html += '<table style="width: 100%; border-collapse: collapse;">';
        html += `
            <thead>
                <tr style="background: #f3f4f6; border-bottom: 1px solid #e5e7eb;">
                    <th style="padding: 12px; text-align: left; color: #374151; font-weight: bold; font-size: 0.875rem;">PRENDA</th>
                    <th style="padding: 12px; text-align: left; color: #374151; font-weight: bold; font-size: 0.875rem;">TELA</th>
                    <th style="padding: 12px; text-align: center; color: #374151; font-weight: bold; font-size: 0.875rem;">CANTIDAD</th>
                    <th style="padding: 12px; text-align: left; color: #374151; font-weight: bold; font-size: 0.875rem;">DESCRIPCIÓN</th>
                </tr>
            </thead>
            <tbody>
        `;
        
        prendas.forEach((prenda, indiceFor) => {
            const fotosArray = Array.isArray(prenda.fotos) ? prenda.fotos : [];
            const telasArray = Array.isArray(prenda.tela_fotos) ? prenda.tela_fotos : [];
            const fotosCount = fotosArray.length;
            const telasCount = telasArray.length;
            
            // Guardar los arrays en variables globales para acceso desde event listeners
            window[`fotos_${indiceFor}`] = fotosArray;
            window[`telas_${indiceFor}`] = telasArray;
            
            html += `
                <tr style="border-bottom: 1px solid #e5e7eb; ${indiceFor % 2 === 0 ? 'background: #ffffff;' : 'background: #f9fafb;'}">
                    <td style="padding: 12px; color: #1f2937; font-weight: 500;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            ${fotosCount > 0 ? `
                                <div class="foto-prenda-container" data-fotos-key="fotos_${indiceFor}" data-title="${(prenda.nombre_prenda || 'Prenda').replace(/"/g, '&quot;')}" style="position: relative; cursor: pointer;">
                                    <img src="${fotosArray[0]}" alt="${prenda.nombre_prenda || 'Prenda'}" 
                                         width="60" height="60"
                                         style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px; border: 2px solid #3b82f6;">
                                    ${fotosCount > 1 ? `<div style="position: absolute; top: -8px; right: -8px; background: #3b82f6; color: white; border-radius: 50%; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: bold;">+${fotosCount - 1}</div>` : ''}
                                </div>
                            ` : '<div style="width: 60px; height: 60px; background: #e5e7eb; border-radius: 4px; display: flex; align-items: center; justify-content: center; color: #9ca3af; font-size: 0.75rem;">Sin foto</div>'}
                            <span>${prenda.nombre_prenda || 'Prenda'}</span>
                        </div>
                    </td>
                    <td style="padding: 12px; color: #1f2937; font-weight: 500;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            ${telasCount > 0 ? `
                                <div class="foto-tela-container" data-fotos-key="telas_${indiceFor}" data-title="${(prenda.nombre_prenda || 'Prenda').replace(/"/g, '&quot;')} - Tela" style="position: relative; cursor: pointer;">
                                    <img src="${telasArray[0]}" alt="Tela" 
                                         width="60" height="60"
                                         style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px; border: 2px solid #8B4513;">
                                    ${telasCount > 1 ? `<div style="position: absolute; top: -8px; right: -8px; background: #3b82f6; color: white; border-radius: 50%; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: bold;">+${telasCount - 1}</div>` : ''}
                                </div>
                            ` : '<div style="width: 60px; height: 60px; background: #e5e7eb; border-radius: 4px; display: flex; align-items: center; justify-content: center; color: #9ca3af; font-size: 0.75rem;">Sin tela</div>'}
                        </div>
                    </td>
                    <td style="padding: 12px; text-align: center; color: #1f2937; font-weight: 500;">${prenda.cantidad}</td>
                    <td style="padding: 12px; color: #6b7280; font-size: 0.875rem;">
                        <div style="max-height: 200px; overflow-y: auto;">
                            ${procesarDescripcion(prenda.descripcion_formateada || prenda.descripcion || prenda.detalles_proceso)}
                            ${prenda.tallas && prenda.tallas.length > 0 ? `
                                <div style="margin-top: 12px; padding-top: 12px; border-top: 1px solid #e5e7eb;">
                                    <strong style="font-size: 10px; display: block; margin-bottom: 4px;">TALLAS:</strong>
                                    <div style="font-size: 9px; color: #4b5563;">${prenda.tallas.map(t => t.talla).join(', ')}</div>
                                </div>
                            ` : ''}
                        </div>
                    </td>
                </tr>
            `;
        });
        
        html += `
            </tbody>
        </table>
        `;
    }
    
    html += `
            </div>
        </div>
    `;
    
    contenido.innerHTML = html;
    
    // Agregar event listeners para fotos de prendas
    document.querySelectorAll('.foto-prenda-container').forEach(el => {
        el.addEventListener('click', function() {
            const fotosKey = this.getAttribute('data-fotos-key');
            const title = this.getAttribute('data-title');
            const fotos = window[fotosKey] || [];
            abrirModalImagenesArray(fotos, title, 0);
        });
    });
    
    // Agregar event listeners para fotos de telas
    document.querySelectorAll('.foto-tela-container').forEach(el => {
        el.addEventListener('click', function() {
            const fotosKey = this.getAttribute('data-fotos-key');
            const title = this.getAttribute('data-title');
            const fotos = window[fotosKey] || [];
            abrirModalImagenesArray(fotos, title, 0);
        });
    });
}

function cerrarModalComparar() {
    const modal = document.getElementById('modal-comparar-cotizacion');
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
    
    if (comparar && event.target === comparar) {
        cerrarModalComparar();
    }
});

// Variables globales para el modal de imágenes
let imagenesActuales = [];
let imagenActualIndex = 0;

function abrirModalImagenes(imagenes, titulo, indiceInicial = 0) {
    imagenesActuales = imagenes;
    imagenActualIndex = indiceInicial;
    
    const modal = document.getElementById('modal-imagenes');
    document.getElementById('modal-imagenes-titulo').textContent = titulo;
    
    mostrarImagen();
    
    modal.style.setProperty('display', 'flex', 'important');
    modal.style.setProperty('visibility', 'visible', 'important');
    modal.style.setProperty('opacity', '1', 'important');
}

// Función alias para manejar arrays simples de URLs
function abrirModalImagenesArray(imagenes, titulo, indiceInicial = 0) {
    // Si recibe array de strings (URLs), usa directamente
    // Si recibe array de objetos, extrae las URLs
    const urlsArray = Array.isArray(imagenes) ? imagenes.map(img => {
        if (typeof img === 'string') {
            return img;
        } else if (img && typeof img === 'object') {
            return img.url || img.ruta_webp || '';
        }
        return '';
    }).filter(url => url) : [];
    
    abrirModalImagenes(urlsArray, titulo, indiceInicial);
}

function cerrarModalImagenes() {
    const modal = document.getElementById('modal-imagenes');
    modal.style.setProperty('display', 'none', 'important');
    modal.style.setProperty('visibility', 'hidden', 'important');
    modal.style.setProperty('opacity', '0', 'important');
    imagenesActuales = [];
    imagenActualIndex = 0;
}

function mostrarImagen() {
    if (imagenesActuales.length === 0) return;
    
    const img = document.getElementById('modal-imagenes-img');
    const contador = document.getElementById('modal-imagenes-contador');
    const nav = document.getElementById('modal-imagenes-nav');
    
    img.src = imagenesActuales[imagenActualIndex];
    contador.textContent = `${imagenActualIndex + 1} / ${imagenesActuales.length}`;
    
    // Mostrar/ocultar botones de navegación
    if (imagenesActuales.length === 1) {
        nav.style.display = 'none';
    } else {
        nav.style.display = 'flex';
    }
}

function imagenAnterior() {
    if (imagenesActuales.length === 0) return;
    imagenActualIndex = (imagenActualIndex - 1 + imagenesActuales.length) % imagenesActuales.length;
    mostrarImagen();
}

function imagenSiguiente() {
    if (imagenesActuales.length === 0) return;
    imagenActualIndex = (imagenActualIndex + 1) % imagenesActuales.length;
    mostrarImagen();
}

// Navegación con teclado
document.addEventListener('keydown', function(event) {
    const modal = document.getElementById('modal-imagenes');
    if (modal.style.display === 'flex' || modal.style.display === 'block') {
        if (event.key === 'ArrowLeft') {
            imagenAnterior();
        } else if (event.key === 'ArrowRight') {
            imagenSiguiente();
        } else if (event.key === 'Escape') {
            cerrarModalImagenes();
        }
    }
});
</script>
@endsection
