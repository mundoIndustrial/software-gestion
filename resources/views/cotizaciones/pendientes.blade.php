@extends('layouts.app')

@section('title', 'Cotizaciones')
@section('page-title', 'Cotizaciones Pendientes de Aprobación')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/contador/tabla-index.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('css/contador/cotizacion-modal.css') }}?v={{ time() }}">
@endpush

@section('content')
<div class="p-8">
    <!-- Tabla -->
    @if(count($cotizaciones) > 0)
    <div class="table-container">
        <div class="modern-table-wrapper">
            <div class="table-head" id="tableHead">
                <div style="display: flex; align-items: center; width: 100%; gap: 12px; padding: 14px 12px;">
                    @php
                        $columns = [
                            ['key' => 'acciones', 'label' => 'Acciones', 'flex' => '0 0 120px', 'justify' => 'flex-start'],
                            ['key' => 'estado', 'label' => 'Estado', 'flex' => '0 0 150px', 'justify' => 'center'],
                            ['key' => 'aprobaciones', 'label' => 'Aprobaciones', 'flex' => '0 0 140px', 'justify' => 'center'],
                            ['key' => 'numero', 'label' => 'Número', 'flex' => '0 0 140px', 'justify' => 'center'],
                            ['key' => 'fecha', 'label' => 'Fecha', 'flex' => '0 0 180px', 'justify' => 'center'],
                            ['key' => 'cliente', 'label' => 'Cliente', 'flex' => '0 0 200px', 'justify' => 'center'],
                            ['key' => 'asesora', 'label' => 'Asesora', 'flex' => '0 0 150px', 'justify' => 'center'],
                            ['key' => 'novedades', 'label' => 'Novedades', 'flex' => '0 0 180px', 'justify' => 'center'],
                        ];
                    @endphp
                    @foreach($columns as $column)
                        <div class="table-header-cell{{ $column['key'] === 'acciones' ? ' acciones-column' : '' }}" style="flex: {{ $column['flex'] }}; justify-content: {{ $column['justify'] }};">
                            <div class="th-wrapper" style="display: flex; align-items: center; justify-content: space-between; gap: 0.5rem;">
                                <span class="header-text">{{ $column['label'] }}</span>
                                @if($column['key'] !== 'acciones')
                                    <button type="button" class="btn-filter-column" data-filter-column="{{ $column['key'] }}" onclick="abrirFiltroColumna('{{ $column['key'] }}', obtenerValoresColumna('{{ $column['key'] }}'))" title="Filtrar {{ $column['label'] }}">
                                        <span class="material-symbols-rounded">filter_alt</span>
                                        <div class="filter-badge"></div>
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="table-scroll-container">
                <div class="modern-table">
                    <div id="tablaCotizacionesBody" class="table-body">
                        @foreach($cotizaciones as $cotizacion)
                            <div class="table-row" 
                                data-cotizacion-id="{{ $cotizacion->id }}" 
                                data-numero="{{ $cotizacion->numero_cotizacion ?? 'Por asignar' }}" 
                                data-fecha="{{ $cotizacion->created_at ? $cotizacion->created_at->format('d/m/Y') : '' }}" 
                                data-cliente="{{ $cotizacion->cliente?->nombre ?? 'N/A' }}" 
                                data-asesora="{{ $cotizacion->asesora ?? ($cotizacion->usuario->name ?? 'N/A') }}"
                                data-estado="{{ $cotizacion->estado }}"
                                data-novedades="{{ $cotizacion->novedades ?? '-' }}"
                            >
                                <!-- Acciones -->
                                <div class="table-cell acciones-column" style="flex: 0 0 120px; justify-content: center; position: relative;">
                                    <div class="actions-group">
                                        <button class="action-view-btn" title="Ver opciones" data-cotizacion-id="{{ $cotizacion->id }}">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <div class="action-menu" data-cotizacion-id="{{ $cotizacion->id }}">
                                            <a href="#" class="action-menu-item" data-action="cotizacion" onclick="openCotizacionModal({{ $cotizacion->id }}); return false;">
                                                <i class="fas fa-file-alt"></i>
                                                <span>Ver Cotización</span>
                                            </a>
                                            <a href="#" class="action-menu-item" data-action="costos" onclick="abrirModalVisorCostos({{ $cotizacion->id }}, '{{ $cotizacion->cliente?->nombre ?? 'N/A' }}'); return false;">
                                                <i class="fas fa-chart-bar"></i>
                                                <span>Ver Costos</span>
                                            </a>
                                        </div>
                                        <button class="btn-action btn-success" onclick="aprobarCotizacionAprobador({{ $cotizacion->id }})" title="Aprobar Cotización">
                                            <i class="fas fa-check-circle"></i>
                                        </button>
                                        <button class="btn-action btn-edit" onclick="abrirFormularioCorregir({{ $cotizacion->id }})" title="Enviar a Corrección">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Estado -->
                                <div class="table-cell" style="flex: 0 0 150px;">
                                    <div class="cell-content" style="justify-content: center;">
                                        @php
                                            $estadoLabel = match($cotizacion->estado) {
                                                'BORRADOR' => ['text' => 'Borrador', 'bg' => '#e5e7eb', 'color' => '#374151'],
                                                'ENVIADA_CONTADOR' => ['text' => 'Enviada a Contador', 'bg' => '#dbeafe', 'color' => '#1e40af'],
                                                'APROBADA_CONTADOR' => ['text' => 'Aprobada por Contador', 'bg' => '#d1fae5', 'color' => '#065f46'],
                                                'APROBADA_COTIZACIONES' => ['text' => 'Aprobada por Aprobador', 'bg' => '#d1fae5', 'color' => '#065f46'],
                                                'APROBADO_PARA_PEDIDO' => ['text' => 'Aprobada para Pedido', 'bg' => '#d1fae5', 'color' => '#065f46'],
                                                'EN_CORRECCION' => ['text' => 'En Corrección', 'bg' => '#fef3c7', 'color' => '#92400e'],
                                                'CONVERTIDA_PEDIDO' => ['text' => 'Convertida a Pedido', 'bg' => '#e0e7ff', 'color' => '#3730a3'],
                                                'FINALIZADA' => ['text' => 'Finalizada', 'bg' => '#f3f4f6', 'color' => '#1f2937'],
                                                default => ['text' => $cotizacion->estado, 'bg' => '#fff3cd', 'color' => '#856404']
                                            };
                                        @endphp
                                        <span style="background: {{ $estadoLabel['bg'] }}; color: {{ $estadoLabel['color'] }}; padding: 4px 10px; border-radius: 12px; font-size: 0.8rem; font-weight: bold;">
                                            {{ $estadoLabel['text'] }}
                                        </span>
                                    </div>
                                </div>
                                
                                <!-- Aprobaciones -->
                                <div class="table-cell" style="flex: 0 0 140px;">
                                    <div class="cell-content" style="justify-content: center; flex-direction: column; gap: 4px;">
                                        @php
                                            $aprobacionesCount = $cotizacion->aprobaciones->count();
                                            $yaAprobo = $cotizacion->aprobaciones->where('usuario_id', auth()->id())->count() > 0;
                                            $porcentaje = $totalAprobadores > 0 ? ($aprobacionesCount / $totalAprobadores) * 100 : 0;
                                        @endphp
                                        <div style="display: flex; align-items: center; gap: 6px;">
                                            <span style="font-weight: 600; font-size: 0.9rem;">{{ $aprobacionesCount }}/{{ $totalAprobadores }}</span>
                                            @if($yaAprobo)
                                                <span style="color: #10b981; font-size: 0.75rem;" title="Ya aprobaste esta cotización">✓</span>
                                            @endif
                                        </div>
                                        <div style="width: 100%; background: #e5e7eb; height: 4px; border-radius: 2px; overflow: hidden;">
                                            <div style="width: {{ $porcentaje }}%; background: {{ $porcentaje >= 100 ? '#10b981' : '#3b82f6' }}; height: 100%;"></div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Número -->
                                <div class="table-cell" style="flex: 0 0 140px;">
                                    <div class="cell-content" style="justify-content: center;">
                                        <span style="font-weight: 600;">{{ $cotizacion->numero_cotizacion ?? 'Por asignar' }}</span>
                                    </div>
                                </div>
                                
                                <!-- Fecha -->
                                <div class="table-cell" style="flex: 0 0 180px;">
                                    <div class="cell-content" style="justify-content: center;">
                                        <span>{{ $cotizacion->created_at ? $cotizacion->created_at->format('d/m/Y H:i') : '-' }}</span>
                                    </div>
                                </div>
                                
                                <!-- Cliente -->
                                <div class="table-cell" style="flex: 0 0 200px;">
                                    <div class="cell-content" style="justify-content: center;">
                                        <span>{{ $cotizacion->cliente?->nombre ?? 'N/A' }}</span>
                                    </div>
                                </div>
                                
                                <!-- Asesora -->
                                <div class="table-cell" style="flex: 0 0 150px;">
                                    <div class="cell-content" style="justify-content: center;">
                                        <span>{{ $cotizacion->asesora ?? ($cotizacion->usuario->name ?? 'N/A') }}</span>
                                    </div>
                                </div>
                                
                                <!-- Novedades -->
                                <div class="table-cell" style="flex: 0 0 180px;">
                                    <div class="cell-content" style="justify-content: center;">
                                        <span style="font-size: 0.85rem;">{{ $cotizacion->novedades ?? '-' }}</span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
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



<!-- Modal para corrección de cotización -->
<div id="modal-corregir-cotizacion" class="modal-overlay" onclick="if(event.target === this) cerrarModalCorregir();" style="z-index: 9999; background: rgba(0, 0, 0, 0.7);">
    <div class="modal-content" style="max-width: 600px;">
        <!-- Header del Modal -->
        <div style="display: flex; justify-content: space-between; align-items: center; padding: 24px; border-bottom: 1px solid #e5e7eb; background: linear-gradient(to right, #ef4444, #dc2626);">
            <h2 style="margin: 0; color: white; font-size: 1.5rem; font-weight: bold;">Enviar a Corrección</h2>
            <button onclick="cerrarModalCorregir()" style="background: none; border: none; color: white; cursor: pointer; font-size: 1.5rem;">
                <span class="material-symbols-rounded">close</span>
            </button>
        </div>

        <!-- Contenido del Modal -->
        <div style="padding: 24px;">
            <div style="background: #fef2f2; border-left: 4px solid #ef4444; padding: 12px; margin-bottom: 20px; border-radius: 4px;">
                <p style="margin: 0; color: #991b1b; font-size: 0.875rem;">
                    <strong> Atención:</strong> Al enviar a corrección, se eliminarán todas las aprobaciones registradas y la cotización volverá al contador para revisión.
                </p>
            </div>
            
            <form id="form-corregir-cotizacion">
                <div style="margin-bottom: 20px;">
                    <label style="display: block; color: #374151; font-weight: bold; margin-bottom: 8px;">Observaciones para el Contador</label>
                    <textarea id="observaciones-correccion" name="observaciones" rows="6" style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; font-family: Arial, sans-serif; resize: none;" placeholder="Describe los ajustes o correcciones que el contador debe realizar..." required></textarea>
                </div>

                <div style="display: flex; gap: 12px; justify-content: flex-end;">
                    <button type="button" onclick="cerrarModalCorregir()" style="background: #e5e7eb; color: #374151; padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold;">
                        Cancelar
                    </button>
                    <button type="submit" style="background: #ef4444; color: white; padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold;">
                        Enviar a Corrección
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
function openCotizacionModal(cotizacionId) {
    const modal = document.getElementById('cotizacionModal');
    const content = document.getElementById('modalBody');
    
    fetch(`/contador/cotizacion/${cotizacionId}`)
        .then(response => response.text())
        .then(html => {
            content.innerHTML = html;
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        })
        .catch(error => {
            alert('Error al cargar la cotización');
        });
}

function closeCotizacionModal() {
    const modal = document.getElementById('cotizacionModal');
    modal.style.display = 'none';
    document.body.style.overflow = 'auto';
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
        if (data.success) {
            Swal.fire({
                title: 'Enviada a Corrección',
                html: `<p>${data.message}</p><p class="mt-2 text-sm text-gray-600">La cotización ha sido enviada al contador para revisión. Todas las aprobaciones previas han sido eliminadas.</p>`,
                icon: 'success',
                confirmButtonColor: '#10b981'
            }).then(() => {
                location.reload();
            });
        } else {
            Swal.fire('Error', data.message, 'error');
        }
    })
    .catch(error => {
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
                if (data.success) {
                    let mensaje = data.message;
                    
                    if (data.aprobacion_completa) {
                        Swal.fire({
                            title: '¡Aprobación Completa!',
                            html: `<p>${mensaje}</p><p class="mt-2 text-sm text-gray-600">La cotización ha cambiado a estado <strong>APROBADA POR APROBADOR</strong></p>`,
                            icon: 'success',
                            confirmButtonColor: '#10b981'
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            title: 'Aprobación Registrada',
                            html: `<p>${mensaje}</p><p class="mt-2 text-sm text-gray-600">Aprobaciones: ${data.aprobaciones_actuales}/${data.total_aprobadores}</p>`,
                            icon: 'info',
                            confirmButtonColor: '#3b82f6'
                        }).then(() => {
                            location.reload();
                        });
                    }
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            })
            .catch(error => {
                Swal.fire('Error', 'No se pudo aprobar la cotización: ' + error.message, 'error');
            });
        }
    });
}

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



// ===== HORIZONTAL SCROLL SYNCHRONIZATION =====
document.addEventListener('DOMContentLoaded', function() {
    const scrollContainer = document.querySelector('.table-scroll-container');
    const tableHead = document.querySelector('.table-head');
    
    if (scrollContainer && tableHead) {
        scrollContainer.addEventListener('scroll', function() {
            tableHead.style.transform = 'translateX(' + (-this.scrollLeft) + 'px)';
        });
    }
});

// ===== ACTION MENU FUNCTIONALITY =====
document.addEventListener('DOMContentLoaded', function() {
    // Toggle action menu
    document.addEventListener('click', function(event) {
        const viewBtn = event.target.closest('.action-view-btn');
        
        if (viewBtn) {
            event.stopPropagation();
            const cotizacionId = viewBtn.getAttribute('data-cotizacion-id');
            const menu = document.querySelector(`.action-menu[data-cotizacion-id="${cotizacionId}"]`);
            
            // Close all other menus
            document.querySelectorAll('.action-menu').forEach(m => {
                if (m !== menu) {
                    m.classList.remove('active');
                }
            });
            
            // Toggle current menu
            if (menu) {
                menu.classList.toggle('active');
            }
        } else if (!event.target.closest('.action-menu')) {
            // Close all menus when clicking outside
            document.querySelectorAll('.action-menu').forEach(m => {
                m.classList.remove('active');
            });
        }
    });
    
    // Close menu when clicking on menu item
    document.querySelectorAll('.action-menu-item').forEach(item => {
        item.addEventListener('click', function() {
            document.querySelectorAll('.action-menu').forEach(m => {
                m.classList.remove('active');
            });
        });
    });
});
</script>



<!-- Estilos para botones de filtro -->
<style>
.btn-filter-column {
    background: rgba(255, 255, 255, 0.2);
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 6px;
    padding: 6px;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
}

.btn-filter-column:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: scale(1.1);
}

.btn-filter-column .material-symbols-rounded {
    font-size: 18px;
    color: white;
}
</style>

<!-- Modal de Visor de Costos -->
<div id="visorCostosModal" class="modal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); z-index: 9998; justify-content: center; align-items: flex-start; padding: 2rem; overflow: hidden; padding-top: 4rem;">
    <div style="position: relative; width: 90%; max-width: 900px;">
        <!-- Tabs de Prendas que sobresalen del modal -->
        <div id="visorCostosTabsContainer" style="display: flex; gap: 0.75rem; margin-bottom: -1.5rem; position: relative; z-index: 11; flex-wrap: wrap; justify-content: flex-start;">
            <!-- Tabs generados dinámicamente por visor-costos.js -->
        </div>
        
        <!-- Contenedor principal del modal -->
        <div class="modal-content" id="visorCostosModalContent" style="width: 100%; max-height: 85vh; overflow: visible; background: white; border-radius: 12px; box-shadow: 0 20px 60px rgba(0,0,0,0.4); display: flex; flex-direction: column; position: relative;">
            <button onclick="cerrarVisorCostos()" style="position: absolute; top: 1rem; right: 1rem; background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #7f8c8d; z-index: 10;">
                <span class="material-symbols-rounded">close</span>
            </button>
            <div id="visorCostosContenido" style="overflow-y: auto; flex: 1; padding: 1.5rem;">
                <!-- Contenido cargado dinámicamente por visor-costos.js -->
            </div>
        </div>
    </div>
</div>

<!-- Modal de Cotización (Full Screen) -->
<div id="cotizacionModal" class="modal fullscreen" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; width: 100%; height: 100%; z-index: 99999; overflow: hidden; margin: 0; padding: 0;">
    <div class="modal-content" style="background: white; width: 100%; height: 100%; display: flex; flex-direction: column; overflow: hidden; margin: 0; padding: 0;">
        <div class="modal-header" style="background: linear-gradient(to right, #1e5ba8, #1a4d8f); padding: 1.5rem 2rem; display: flex; align-items: center; gap: 2rem; color: white; flex-shrink: 0; border-bottom: 2px solid #0f3a6e;">
            <img src="{{ asset('images/logo2.png') }}" alt="Logo Mundo Industrial" class="modal-header-logo" width="150" height="60" style="object-fit: contain; filter: brightness(0) invert(1);">
            <div style="display: flex; gap: 3rem; align-items: center; flex: 1; font-size: 0.85rem;">
                <div>
                    <p style="margin: 0; opacity: 0.8;">Cotización #</p>
                    <p id="modalHeaderNumber" style="margin: 0; font-size: 1.1rem; font-weight: 600;">-</p>
                </div>
                <div>
                    <p style="margin: 0; opacity: 0.8;">Fecha</p>
                    <p id="modalHeaderDate" style="margin: 0; font-size: 1.1rem; font-weight: 600;">-</p>
                </div>
                <div>
                    <p style="margin: 0; opacity: 0.8;">Cliente</p>
                    <p id="modalHeaderClient" style="margin: 0; font-size: 1.1rem; font-weight: 600;">-</p>
                </div>
                <div>
                    <p style="margin: 0; opacity: 0.8;">Asesora</p>
                    <p id="modalHeaderAdvisor" style="margin: 0; font-size: 1.1rem; font-weight: 600;">-</p>
                </div>
            </div>
            <button onclick="closeCotizacionModal()" style="background: rgba(255,255,255,0.2); border: none; color: white; font-size: 1.5rem; cursor: pointer; padding: 0.5rem 1rem; border-radius: 4px; transition: all 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">
                ✕
            </button>
        </div>
        <div id="modalBody" style="padding: 2rem; overflow-y: auto; background: white; flex: 1;"></div>
    </div>
</div>

<script src="{{ asset('js/contador/cotizacion.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/contador/visor-costos.js') }}?v={{ time() }}"></script>

<script>
    function cerrarVisorCostos() {
        document.getElementById('visorCostosModal').style.display = 'none';
    }
</script>

@endsection

