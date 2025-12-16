@extends('layouts.app')

@section('title', 'Cotizaciones')
@section('page-title', 'Cotizaciones Pendientes de Aprobación')

@section('content')
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/contador/tabla-cotizaciones.css') }}?v={{ time() }}">
@endpush

<script>
/**
 * Búsqueda en tabla de cotizaciones pendientes
 */
function aplicarBusquedaCotizaciones() {
    const searchInput = document.getElementById('navSearchInput');
    if (!searchInput) return;
    
    const query = searchInput.value.toLowerCase().trim();
    const tableRows = document.querySelectorAll('.table-row');
    let visibleCount = 0;
    
    tableRows.forEach(row => {
        const numero = row.getAttribute('data-numero') || '';
        const cliente = row.getAttribute('data-cliente') || '';
        const asesora = row.getAttribute('data-asesora') || '';
        const fecha = row.getAttribute('data-fecha') || '';
        
        const matches = numero.toLowerCase().includes(query) ||
                       cliente.toLowerCase().includes(query) ||
                       asesora.toLowerCase().includes(query) ||
                       fecha.toLowerCase().includes(query);
        
        if (query === '' || matches) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });
    
    // Actualizar contador
    const paginationInfo = document.getElementById('paginationInfo');
    if (paginationInfo) {
        if (query === '') {
            paginationInfo.textContent = `Total: {{ count($cotizaciones) }} cotizaciones`;
        } else {
            paginationInfo.textContent = `Resultados: ${visibleCount} cotizaciones`;
        }
    }
}

// Inicializar búsqueda
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('navSearchInput');
    if (searchInput) {
        searchInput.addEventListener('input', aplicarBusquedaCotizaciones);
        searchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                aplicarBusquedaCotizaciones();
            }
        });
    }
});
</script>

<!-- Sección de Cotizaciones Pendientes -->
<section id="pendientes-section" class="section-content active" style="display: block;">
    <div class="table-container">
        <div class="modern-table-wrapper">
            <div class="table-head" id="tableHead">
                <div style="display: flex; align-items: center; width: 100%; gap: 12px; padding: 14px 12px;">
                    @php
                        $columns = [
                            ['key' => 'acciones', 'label' => 'Acciones', 'flex' => '0 0 280px', 'justify' => 'center'],
                            ['key' => 'numero', 'label' => 'Número', 'flex' => '0 0 140px', 'justify' => 'center'],
                            ['key' => 'fecha_creacion', 'label' => 'Fecha Creación', 'flex' => '0 0 180px', 'justify' => 'center'],
                            ['key' => 'fecha_envio', 'label' => 'Fecha Envío a Aprobador', 'flex' => '0 0 200px', 'justify' => 'center'],
                            ['key' => 'cliente', 'label' => 'Cliente', 'flex' => '1', 'justify' => 'center'],
                            ['key' => 'asesora', 'label' => 'Asesora', 'flex' => '0 0 150px', 'justify' => 'center'],
                            ['key' => 'estado', 'label' => 'Estado', 'flex' => '0 0 150px', 'justify' => 'center'],
                        ];
                    @endphp
                    
                    @foreach($columns as $column)
                        <div class="table-header-cell{{ $column['key'] === 'acciones' ? ' acciones-column' : '' }}" style="flex: {{ $column['flex'] }}; justify-content: {{ $column['justify'] }};">
                            <div class="th-wrapper" style="display: flex; align-items: center; justify-content: space-between; gap: 0.5rem;">
                                <span class="header-text">{{ $column['label'] }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="table-scroll-container">
                <div class="modern-table">
                    <div id="tablaCotizacionesBody" class="table-body">
                        @forelse($cotizaciones as $cotizacion)
                            <div class="table-row" data-cotizacion-id="{{ $cotizacion->id }}" data-numero="COT-{{ str_pad($cotizacion->id, 5, '0', STR_PAD_LEFT) }}" data-cliente="{{ is_object($cotizacion->cliente) ? $cotizacion->cliente->nombre : ($cotizacion->cliente ?? '') }}" data-asesora="{{ $cotizacion->asesora ?? ($cotizacion->usuario->name ?? '') }}" data-fecha="{{ $cotizacion->created_at ? $cotizacion->created_at->format('d/m/Y') : '' }}">
                                <!-- Acciones -->
                                <div class="table-cell acciones-column" style="flex: 0 0 280px; justify-content: center; position: relative;">
                                    <div class="actions-group" style="display: flex; gap: 8px; align-items: center; justify-content: center;">
                                        <button class="btn-action" onclick="verComparacion({{ $cotizacion->id }})" title="Ver cotización" style="background: #3b82f6; color: white; padding: 6px 12px; border: none; border-radius: 6px; cursor: pointer; font-size: 0.85rem; font-weight: 500; transition: all 0.2s;" onmouseover="this.style.background='#2563eb'" onmouseout="this.style.background='#3b82f6'">
                                            <i class="fas fa-eye" style="margin-right: 4px;"></i>Ver
                                        </button>
                                        <button class="btn-action" onclick="aprobarCotizacionAprobador({{ $cotizacion->id }})" title="Aprobar cotización" style="background: #10b981; color: white; padding: 6px 12px; border: none; border-radius: 6px; cursor: pointer; font-size: 0.85rem; font-weight: 500; transition: all 0.2s;" onmouseover="this.style.background='#059669'" onmouseout="this.style.background='#10b981'">
                                            <i class="fas fa-check-circle" style="margin-right: 4px;"></i>Aprobar
                                        </button>
                                        <button class="btn-action" onclick="abrirFormularioCorregir({{ $cotizacion->id }})" title="Enviar a corrección" style="background: #f59e0b; color: white; padding: 6px 12px; border: none; border-radius: 6px; cursor: pointer; font-size: 0.85rem; font-weight: 500; transition: all 0.2s;" onmouseover="this.style.background='#d97706'" onmouseout="this.style.background='#f59e0b'">
                                            <i class="fas fa-edit" style="margin-right: 4px;"></i>Corregir
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Número -->
                                <div class="table-cell" style="flex: 0 0 140px;" data-numero="COT-{{ str_pad($cotizacion->id, 5, '0', STR_PAD_LEFT) }}">
                                    <div class="cell-content" style="justify-content: center;">
                                        <span style="font-weight: 600;">COT-{{ str_pad($cotizacion->id, 5, '0', STR_PAD_LEFT) }}</span>
                                    </div>
                                </div>
                                
                                <!-- Fecha Creación -->
                                <div class="table-cell" style="flex: 0 0 180px;" data-fecha="{{ $cotizacion->created_at ? $cotizacion->created_at->format('d/m/Y') : '' }}">
                                    <div class="cell-content" style="justify-content: center;">
                                        <div style="text-align: center;">
                                            <div style="font-size: 0.85rem; font-weight: 500;">{{ $cotizacion->created_at ? $cotizacion->created_at->format('d/m/Y') : '-' }}</div>
                                            <div style="font-size: 0.75rem; color: #6b7280;">{{ $cotizacion->created_at ? $cotizacion->created_at->format('H:i') : '' }}</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Fecha Envío a Aprobador -->
                                <div class="table-cell" style="flex: 0 0 200px;">
                                    <div class="cell-content" style="justify-content: center;">
                                        <div style="text-align: center;">
                                            @if($cotizacion->fecha_enviado_a_aprobador)
                                                <div style="font-size: 0.85rem; font-weight: 500;">{{ $cotizacion->fecha_enviado_a_aprobador->format('d/m/Y') }}</div>
                                                <div style="font-size: 0.75rem; color: #6b7280;">{{ $cotizacion->fecha_enviado_a_aprobador->format('H:i') }}</div>
                                            @else
                                                <span style="font-size: 0.85rem; color: #9ca3af;">Pendiente</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Cliente -->
                                <div class="table-cell" style="flex: 1;" data-cliente="{{ is_object($cotizacion->cliente) ? $cotizacion->cliente->nombre : ($cotizacion->cliente ?? '') }}">
                                    <div class="cell-content" style="justify-content: center;">
                                        <span>{{ is_object($cotizacion->cliente) ? $cotizacion->cliente->nombre : ($cotizacion->cliente ?? '-') }}</span>
                                    </div>
                                </div>
                                
                                <!-- Asesora -->
                                <div class="table-cell" style="flex: 0 0 150px;" data-asesora="{{ $cotizacion->asesora ?? ($cotizacion->usuario->name ?? '') }}">
                                    <div class="cell-content" style="justify-content: center;">
                                        <span>{{ $cotizacion->asesora ?? ($cotizacion->usuario->name ?? '-') }}</span>
                                    </div>
                                </div>
                                
                                <!-- Estado -->
                                <div class="table-cell" style="flex: 0 0 150px;">
                                    <div class="cell-content" style="justify-content: center;">
                                        <span style="display: inline-flex; align-items: center; padding: 4px 12px; background: #dbeafe; color: #1e40af; border-radius: 20px; font-size: 0.85rem; font-weight: 600; white-space: nowrap;">
                                            <span style="width: 6px; height: 6px; background: #1e40af; border-radius: 50%; margin-right: 6px;"></span>
                                            Pendiente
                                        </span>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div style="padding: 40px; text-align: center; color: #9ca3af;">
                                <p>No hay cotizaciones pendientes</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="table-pagination" id="tablePagination">
                <div class="pagination-info">
                    <span id="paginationInfo">Total: {{ count($cotizaciones) }} cotizaciones</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Modal de Cotización (Mismo del Contador) -->
<div id="cotizacionModal" class="modal fullscreen" style="display: none;">
    <div class="modal-content" style="background: white;">
        <div class="modal-header">
            <img src="{{ asset('images/logo2.png') }}" alt="Logo Mundo Industrial" class="modal-header-logo" width="150" height="60">
            <div style="display: flex; gap: 3rem; align-items: center; flex: 1; margin-left: 2rem; color: white; font-size: 0.85rem;">
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
        <div id="modalBody" style="padding: 2rem; overflow-y: auto; background: white;"></div>
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
    <div class="modal-content" style="max-width: 90vw; max-height: 90vh; background: #1f2937; display: flex; flex-direction: column;">
        <!-- Header del Modal -->
        <div style="display: flex; justify-content: space-between; align-items: center; padding: 24px; border-bottom: 1px solid #374151; flex-shrink: 0;">
            <h2 id="modal-imagenes-titulo" style="margin: 0; color: white; font-size: 1.5rem; font-weight: bold;"></h2>
            <button onclick="cerrarModalImagenes()" style="background: none; border: none; color: white; cursor: pointer; font-size: 1.5rem;">
                <span class="material-symbols-rounded">close</span>
            </button>
        </div>

        <!-- Contenido del Modal -->
        <div style="padding: 24px; text-align: center; flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; overflow: auto;">
            <img id="modal-imagenes-img" src="" alt="Imagen" style="max-width: 100%; max-height: 70vh; object-fit: contain; border-radius: 8px; margin-bottom: 20px;">
            
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

    /* Modal Fullscreen Styles */
    .modal.fullscreen {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.7);
        z-index: 9999;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 0;
        margin: 0;
    }

    .modal.fullscreen .modal-content {
        width: 95%;
        max-width: 1400px;
        height: 90vh;
        max-height: 90vh;
        border-radius: 12px;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
    }

    .modal.fullscreen .modal-header {
        background: linear-gradient(135deg, #1e5ba8 0%, #1e3a8a 100%);
        color: white;
        padding: 1.5rem;
        display: flex;
        align-items: center;
        gap: 2rem;
        flex-shrink: 0;
        border-bottom: 2px solid #1e40af;
    }

    .modal.fullscreen .modal-header-logo {
        height: 60px;
        width: auto;
    }

    .modal.fullscreen #modalBody {
        flex: 1;
        overflow-y: auto;
        padding: 2rem;
        background: white;
    }

    .prenda-card {
        background: #f5f5f5;
        border-left: 5px solid #1e5ba8;
        padding: 1rem 1.5rem;
        border-radius: 4px;
        margin-bottom: 1.5rem;
    }

    .prenda-card:last-child {
        margin-bottom: 0;
    }

    .prendas-container {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
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
    console.log('🔄 Cargando cotización:', cotizacionId);

    fetch(`/cotizaciones/${cotizacionId}/datos`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Datos recibidos:', data);

            // Construir HTML del modal con encabezado y contenido
            let html = '';

            // Encabezado con información de la cotización
            if (data.cotizacion) {
                const cot = data.cotizacion;
                
                // Llenar información del header
                document.getElementById('modalHeaderNumber').textContent = cot.numero_cotizacion || 'N/A';
                document.getElementById('modalHeaderDate').textContent = cot.created_at ? new Date(cot.created_at).toLocaleDateString('es-ES') : 'N/A';
                document.getElementById('modalHeaderClient').textContent = cot.nombre_cliente || 'N/A';
                document.getElementById('modalHeaderAdvisor').textContent = cot.asesora_nombre || 'N/A';
                
                html += `
                    <div style="background: linear-gradient(135deg, #1e5ba8 0%, #1e3a8a 100%); color: white; padding: 1.5rem; border-radius: 8px 8px 0 0; margin: -2rem -2rem 1.5rem -2rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                            <div>
                                <h2 style="margin: 0 0 0.5rem 0; font-size: 1.3rem; font-weight: 700;">MUNDO INDUSTRIAL</h2>
                                <p style="margin: 0; font-size: 0.9rem; opacity: 0.9;">Cotización de Prendas</p>
                            </div>
                        </div>
                        <div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 1rem; font-size: 0.9rem;">
                            <div>
                                <p style="margin: 0 0 0.25rem 0; opacity: 0.8; font-size: 0.8rem;">COTIZACIÓN #</p>
                                <p style="margin: 0; font-weight: 700; font-size: 1rem;">${cot.numero_cotizacion || 'N/A'}</p>
                            </div>
                            <div>
                                <p style="margin: 0 0 0.25rem 0; opacity: 0.8; font-size: 0.8rem;">TIPO:</p>
                                <p style="margin: 0; font-weight: 700;">${cot.tipo_venta || 'N/A'}</p>
                            </div>
                            <div>
                                <p style="margin: 0 0 0.25rem 0; opacity: 0.8; font-size: 0.8rem;">FECHA:</p>
                                <p style="margin: 0; font-weight: 700;">${cot.created_at ? new Date(cot.created_at).toLocaleDateString('es-ES') : 'N/A'}</p>
                            </div>
                            <div>
                                <p style="margin: 0 0 0.25rem 0; opacity: 0.8; font-size: 0.8rem;">CLIENTE:</p>
                                <p style="margin: 0; font-weight: 700;">${cot.nombre_cliente || 'N/A'}</p>
                            </div>
                            <div>
                                <p style="margin: 0 0 0.25rem 0; opacity: 0.8; font-size: 0.8rem;">ASESORA:</p>
                                <p style="margin: 0; font-weight: 700;">${cot.asesora_nombre || 'N/A'}</p>
                            </div>
                        </div>
                    </div>
                `;
            }

            // Contenedor de prendas
            html += '<div class="prendas-container" style="display: flex; flex-direction: column; gap: 1.5rem;">';

            if (data.prendas_cotizaciones && data.prendas_cotizaciones.length > 0) {
                data.prendas_cotizaciones.forEach((prenda, index) => {
                    console.log('Renderizando prenda:', prenda);

                    // Construir atributos principales
                    let atributosLinea = [];

                    // Obtener color de variantes o telas
                    let color = '';
                    if (prenda.variantes && prenda.variantes.length > 0 && prenda.variantes[0].color) {
                        color = prenda.variantes[0].color;
                    }

                    // Obtener tela de telas
                    let telaInfo = '';
                    if (prenda.telas && prenda.telas.length > 0) {
                        const tela = prenda.telas[0];
                        telaInfo = tela.nombre_tela || '';
                        if (tela.referencia) {
                            telaInfo += ` REF:${tela.referencia}`;
                        }
                    }

                    // Obtener manga de variantes
                    let manga = '';
                    if (prenda.variantes && prenda.variantes.length > 0 && prenda.variantes[0].tipo_manga) {
                        manga = prenda.variantes[0].tipo_manga;
                    }

                    // Obtener manga de variantes
                    let manguaInfo = '';
                    if (prenda.variantes && prenda.variantes.length > 0) {
                        const variante = prenda.variantes[0];
                        if (variante.manga && variante.manga.nombre) {
                            manguaInfo = variante.manga.nombre;
                        }
                    }

                    if (color) atributosLinea.push(`Color: ${color}`);
                    if (telaInfo) atributosLinea.push(`Tela: ${telaInfo}`);
                    if (manguaInfo) atributosLinea.push(`Manga: ${manguaInfo}`);

                    // Construir HTML de la prenda
                    html += `
                        <div class="prenda-card" style="background: #f5f5f5; border-left: 5px solid #1e5ba8; padding: 1rem 1.5rem; border-radius: 4px;">
                            <h3 style="margin: 0 0 0.5rem 0; color: #1e5ba8; font-size: 1.1rem; font-weight: 700; text-transform: uppercase;">
                                ${prenda.nombre_prenda || 'Sin nombre'}
                            </h3>
                            <p style="margin: 0 0 0.75rem 0; color: #666; font-size: 0.9rem; font-weight: 500;">
                                ${atributosLinea.join(' | ') || ''}
                            </p>
                            <div style="margin: 0 0 1rem 0; color: #333; font-size: 0.85rem; line-height: 1.6;">
                                <span style="color: #1e5ba8; font-weight: 700;">DESCRIPCION:</span> ${(prenda.descripcion_formateada || prenda.descripcion || '-').replace(/\n/g, '<br>')}
                            </div>
                    `;

                    // Mostrar tallas si existen
                    if (prenda.tallas && prenda.tallas.length > 0) {
                        html += `
                            <p style="margin: 0 0 0.5rem 0; color: #1e5ba8; font-size: 0.9rem; font-weight: 700;">
                                Tallas: <span style="color: #ef4444; font-weight: 700;">${prenda.tallas.map(t => t.talla).join(', ')}</span>
                            </p>
                        `;
                    }

                    // Mostrar fotos de la prenda si existen
                    if (prenda.fotos && prenda.fotos.length > 0) {
                        html += `
                            <p style="margin: 0 0 0.5rem 0; color: #1e5ba8; font-size: 0.9rem; font-weight: 700;">
                                IMAGENES:
                            </p>
                            <div style="display: flex; gap: 0.75rem; flex-wrap: wrap; margin-bottom: 1rem;">
                        `;
                        prenda.fotos.forEach(foto => {
                            html += `
                                <img src="${foto}" alt="Foto prenda" style="width: 80px; height: 80px; object-fit: cover; border-radius: 4px; border: 1px solid #ddd; cursor: pointer;" onclick="abrirImagenGrande('${foto}')">
                            `;
                        });
                        html += `</div>`;
                    }

                    // Mostrar fotos de telas si existen
                    if (prenda.tela_fotos && prenda.tela_fotos.length > 0) {
                        html += `
                            <p style="margin: 0 0 0.5rem 0; color: #1e5ba8; font-size: 0.9rem; font-weight: 700;">
                                TELAS:
                            </p>
                            <div style="display: flex; gap: 0.75rem; flex-wrap: wrap; margin-bottom: 1rem;">
                        `;
                        prenda.tela_fotos.forEach(foto => {
                            if (foto) {
                                html += `
                                    <img src="${foto}" alt="Foto tela" style="width: 80px; height: 80px; object-fit: cover; border-radius: 4px; border: 1px solid #ddd; cursor: pointer;" onclick="abrirImagenGrande('${foto}')">
                                `;
                            }
                        });
                        html += `</div>`;
                    }

                    html += `</div>`;
                });
            } else {
                html += '<p style="color: #999; text-align: center; padding: 2rem;">No hay prendas para mostrar</p>';
            }

            html += '</div>';

            // Agregar tabla de Especificaciones Generales
            if (data.cotizacion && data.cotizacion.especificaciones && Object.keys(data.cotizacion.especificaciones).length > 0) {
                const especificacionesMap = {
                    'disponibilidad': 'DISPONIBILIDAD',
                    'forma_pago': 'FORMA DE PAGO',
                    'regimen': 'RÉGIMEN',
                    'se_ha_vendido': 'SE HA VENDIDO',
                    'ultima_venta': 'ÚLTIMA VENTA',
                    'flete': 'FLETE DE ENVÍO'
                };

                html += `
                    <div style="margin-top: 2rem;">
                        <h3 style="margin: 0 0 1rem 0; color: #1e5ba8; font-size: 1.1rem; font-weight: 700; text-transform: uppercase;">Especificaciones Generales</h3>
                        <table style="width: 100%; border-collapse: collapse; background: white; border: 1px solid #ddd; border-radius: 4px; overflow: hidden;">
                            <thead>
                                <tr style="background: #f5f5f5; border-bottom: 2px solid #1e5ba8;">
                                    <th style="padding: 0.75rem 1rem; text-align: left; color: #1e5ba8; font-weight: 700; font-size: 0.9rem;">Especificación</th>
                                    <th style="padding: 0.75rem 1rem; text-align: left; color: #1e5ba8; font-weight: 700; font-size: 0.9rem;">Opciones Seleccionadas</th>
                                </tr>
                            </thead>
                            <tbody>
                `;

                for (const [clave, nombreCategoria] of Object.entries(especificacionesMap)) {
                    const valores = data.cotizacion.especificaciones[clave] || [];
                    let valoresText = '-';

                    if (Array.isArray(valores) && valores.length > 0) {
                        valoresText = valores.map(v => {
                            if (typeof v === 'object') {
                                return Object.values(v).join(', ');
                            }
                            return String(v);
                        }).join(', ');
                    } else if (typeof valores === 'string' && valores.trim() !== '') {
                        valoresText = valores;
                    }

                    html += `
                                <tr style="border-bottom: 1px solid #eee;">
                                    <td style="padding: 0.75rem 1rem; color: #333; font-weight: 600; font-size: 0.85rem;">${nombreCategoria}</td>
                                    <td style="padding: 0.75rem 1rem; color: #666; font-size: 0.85rem;">${valoresText}</td>
                                </tr>
                    `;
                }

                html += `
                            </tbody>
                        </table>
                    </div>
                `;
            }

            // Insertar contenido en el modal
            document.getElementById('modalBody').innerHTML = html;
            document.getElementById('cotizacionModal').style.display = 'flex';

            console.log('✅ Modal abierto correctamente con', data.prendas_cotizaciones ? data.prendas_cotizaciones.length : 0, 'prendas');
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire('Error', 'No se pudo cargar la cotización: ' + error.message, 'error');
        });
}

function closeCotizacionModal() {
    document.getElementById('cotizacionModal').style.display = 'none';
}

function cerrarModalComparar() {
    closeCotizacionModal();
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

// Cerrar modal al hacer clic fuera
document.addEventListener('click', function(event) {
    const modal = document.getElementById('cotizacionModal');
    if (event.target === modal) {
        closeCotizacionModal();
    }
});

// Cerrar modal al presionar ESC
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        const modal = document.getElementById('cotizacionModal');
        if (modal && modal.style.display === 'flex') {
            closeCotizacionModal();
        }
    }
});

/**
 * Abre una imagen en grande en un modal
 * @param {string} imagenUrl - URL de la imagen
 */
function abrirImagenGrande(imagenUrl) {
    // Crear modal dinámicamente si no existe
    let modalImagen = document.getElementById('modalImagenGrande');
    if (!modalImagen) {
        modalImagen = document.createElement('div');
        modalImagen.id = 'modalImagenGrande';
        modalImagen.style.cssText = `
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.8);
            z-index: 10000;
            justify-content: center;
            align-items: center;
            padding: 2rem;
        `;
        modalImagen.innerHTML = `
            <div style="position: relative; max-width: 90vw; max-height: 90vh;">
                <button onclick="cerrarImagenGrande()" style="position: absolute; top: -40px; right: 0; background: white; border: none; font-size: 2rem; cursor: pointer; color: white; z-index: 10001;">
                    ✕
                </button>
                <img id="imagenGrandeContent" src="" alt="Imagen ampliada" style="max-width: 100%; max-height: 100%; border-radius: 8px; box-shadow: 0 10px 40px rgba(0,0,0,0.3);">
            </div>
        `;
        document.body.appendChild(modalImagen);
    }

    document.getElementById('imagenGrandeContent').src = imagenUrl;
    modalImagen.style.display = 'flex';
}

/**
 * Cierra el modal de imagen grande
 */
function cerrarImagenGrande() {
    const modal = document.getElementById('modalImagenGrande');
    if (modal) {
        modal.style.display = 'none';
    }
}

// Cerrar modal de imagen al hacer clic fuera
document.addEventListener('click', function (event) {
    const modal = document.getElementById('modalImagenGrande');
    if (modal && event.target === modal) {
        cerrarImagenGrande();
    }
});

// Cerrar modal de imagen al presionar ESC
document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
        cerrarImagenGrande();
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
