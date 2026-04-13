@extends('supervisor-pedidos.layout')

@section('title', 'Pendiente Control Calidad')
@section('page-title', 'Pendiente Control Calidad')

@push('styles')
<style>
    .hidden { display: none !important; }

    /* Asegurar ocultamiento por defecto aunque existan clases display conflictivas */
    #novedadesEditModal.hidden,
    #modalConfirmarEliminar.hidden {
        display: none !important;
    }

    .modal-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.45);
        z-index: 9999;
        align-items: center;
        justify-content: center;
        padding: 1rem;
    }

    .modal-content {
        background: white;
        border-radius: 12px;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        padding: 2rem;
        max-width: 500px;
        width: 100%;
        max-height: 80vh;
        display: flex;
        flex-direction: column;
    }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
    }

    .btn-close {
        background: none;
        border: none;
        font-size: 1.5rem;
        cursor: pointer;
        color: #6b7280;
    }

    .modal-body {
        flex: 1;
        overflow-y: auto;
    }

    .form-group {
        margin-bottom: 1rem;
    }

    .form-control {
        width: 100%;
        padding: 0.5rem;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 0.9rem;
    }

    .modal-footer {
        display: flex;
        gap: 0.5rem;
        margin-top: 1.5rem;
    }

    .btn {
        padding: 0.5rem 1rem;
        border-radius: 6px;
        border: 1px solid transparent;
        cursor: pointer;
        font-size: 0.9rem;
        font-weight: 500;
    }

    .btn-primary {
        background: #3b82f6;
        color: white;
    }

    .btn-secondary {
        background: #e5e7eb;
        color: #1f2937;
    }

    .btn-filter-column {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        position: relative;
    }

    .btn-filter-column.has-filter {
        opacity: 1;
        background: rgba(255, 255, 255, 0.18);
        border-radius: 8px;
        box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.18);
    }

    .filter-badge {
        position: absolute;
        top: -8px;
        right: -8px;
        background: #ef4444;
        color: white;
        border-radius: 50%;
        width: 20px;
        height: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.7rem;
        font-weight: 700;
        border: 2px solid white;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        opacity: 0;
        transform: scale(0);
        transition: all 0.3s ease;
    }

    .btn-filter-column.has-filter .filter-badge {
        opacity: 1;
        transform: scale(1);
    }

    [data-row="processo"] {
        background: var(--row-bg-color, #ffffff) !important;
        opacity: 1;
        transition: background 0.2s ease, opacity 0.2s ease;
    }

    [data-row="processo"]:hover,
    [data-row="processo"]:focus-within {
        background: #f9fafb !important;
    }

    [data-row="processo"][data-color-stored]:not([data-color-stored=""]):hover,
    [data-row="processo"][data-color-stored]:not([data-color-stored=""]):focus-within {
        background: var(--row-bg-color, #ffffff) !important;
        opacity: 0.9;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="supervisor-pedidos-container">
                <div id="supervisorPendientesControlCalidadContent">
                <!-- Tabla de Ordenes -->
                <div style="background: #e5e7eb; border-radius: 8px; overflow: visible; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08); padding: 0.75rem; width: 100%; max-width: 100%;">
                    <!-- Contenedor con Scroll -->
                    <div class="table-scroll-container" style="overflow-x: auto; overflow-y: auto; width: 100%; max-width: 100%; max-height: 800px; border-radius: 6px; scrollbar-width: thin; scrollbar-color: #cbd5e1 #f1f5f9;">
                        <!-- Header Azul -->
                        <div style="
                            background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%);
                            color: white;
                            padding: 0.75rem 1rem;
                            display: grid;
                            grid-template-columns: 170px 110px 200px 120px 200px 160px 130px 100px;
                            gap: 0.15rem;
                            font-weight: 600;
                            font-size: 0.8rem;
                            text-transform: uppercase;
                            letter-spacing: 0.5px;
                            min-width: min-content;
                            border-radius: 6px;
                        ">
                            <div class="th-wrapper" style="display: flex; align-items: center; gap: 0.5rem;">
                                <span>Fecha de Creacion</span>
                                <button type="button" class="btn-filter-column" data-col="fecha_creacion" title="Filtrar Fecha de Creacion" style="display: flex; align-items: center; background: none; border: none; color: white; cursor: pointer; padding: 0;">
                                    <i class="fas fa-filter" style="font-size: 1rem;"></i>
                                </button>
                            </div>
                            <div class="th-wrapper" style="display: flex; align-items: center; gap: 0.5rem;">
                                <span>N° Recibo</span>
                                <button type="button" class="btn-filter-column" data-col="numero_recibo" title="Filtrar N° Recibo" style="display: flex; align-items: center; background: none; border: none; color: white; cursor: pointer; padding: 0;">
                                    <i class="fas fa-filter" style="font-size: 1rem;"></i>
                                </button>
                            </div>
                            <div class="th-wrapper" style="display: flex; align-items: center; gap: 0.5rem;">
                                <span>Cliente</span>
                                <button type="button" class="btn-filter-column" data-col="cliente" title="Filtrar Cliente" style="display: flex; align-items: center; background: none; border: none; color: white; cursor: pointer; padding: 0;">
                                    <i class="fas fa-filter" style="font-size: 1rem;"></i>
                                </button>
                            </div>
                            <div class="th-wrapper" style="display: flex; align-items: center; gap: 0.5rem;">
                                <span>Area</span>
                                <button type="button" class="btn-filter-column" data-col="area" title="Filtrar Area" style="display: flex; align-items: center; background: none; border: none; color: white; cursor: pointer; padding: 0;">
                                    <i class="fas fa-filter" style="font-size: 1rem;"></i>
                                </button>
                            </div>
                            <div class="th-wrapper" style="display: flex; align-items: center; gap: 0.5rem;">
                                <span>Prendas</span>
                                <button type="button" class="btn-filter-column" data-col="prendas" title="Filtrar Prendas" style="display: flex; align-items: center; background: none; border: none; color: white; cursor: pointer; padding: 0;">
                                    <i class="fas fa-filter" style="font-size: 1rem;"></i>
                                </button>
                            </div>
                            <div class="th-wrapper" style="display: flex; align-items: center; gap: 0.5rem;">
                                <span>Novedades</span>
                            </div>
                            <div class="th-wrapper" style="display: flex; align-items: center; gap: 0.5rem;">
                                <span>Asesora</span>
                                <button type="button" class="btn-filter-column" data-col="asesor" title="Filtrar Asesora" style="display: flex; align-items: center; background: none; border: none; color: white; cursor: pointer; padding: 0;">
                                    <i class="fas fa-filter" style="font-size: 1rem;"></i>
                                </button>
                            </div>
                            <div class="th-wrapper" style="display: flex; align-items: center; gap: 0.5rem;">
                                <span>Color</span>
                            </div>
                        </div>

                        <div id="controlCalidadRows">
                            <!-- Filas -->
                            @if(empty($procesosConCantidad))
                                <div style="padding: 3rem 2rem; text-align: center; color: #6b7280;">
                                    <i class="fas fa-inbox" style="font-size: 3rem; color: #d1d5db; margin-bottom: 1rem; display: block;"></i>
                                    <p style="font-size: 1rem; margin: 0;">No hay pendientes</p>
                                </div>
                            @else
                                @foreach($procesosConCantidad as $proceso)
                                    <div data-row="processo" data-color-stored="{{ $proceso['color_control_calidad'] ?? '' }}" style="
                                        --row-bg-color: {{ $proceso['color_control_calidad'] ?: '#ffffff' }};
                                        display: grid;
                                        grid-template-columns: 170px 110px 200px 120px 200px 160px 130px 100px;
                                        gap: 0.15rem;
                                        padding: 1rem;
                                        border-bottom: 1px solid #e5e7eb;
                                        align-items: start;
                                        min-width: min-content;
                                        transition: background 0.2s ease;
                                    ">
                                        
                                        <!-- Fecha de Creacion -->
                                        <div style="display: flex; align-items: center; font-size: 0.9rem; color: #374151;">
                                            {{ \Carbon\Carbon::parse($proceso['fecha_creacion'])->format('d/m/Y') }}
                                        </div>

                                        <!-- Numero de Recibo -->
                                        <div style="display: flex; align-items: center; font-size: 0.9rem; color: #374151; font-weight: 500;">
                                            {{ $proceso['numero_recibo'] }}
                                        </div>

                                        <!-- Cliente -->
                                        <div style="display: flex; align-items: center; font-size: 0.9rem; color: #374151;">
                                            {{ $proceso['cliente'] }}
                                        </div>

                                        <!-- Area -->
                                        <div style="display: flex; align-items: center; font-size: 0.9rem; color: #374151;">
                                            @if($proceso['area'])
                                                <span style="background: #e8f3ff; color: #1e40af; padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: bold; white-space: nowrap; border: 1px solid #bfdbfe; display: inline-block;">
                                                    {{ $proceso['area'] }}
                                                </span>
                                            @else
                                                <span style="background: #f3f4f6; color: #6b7280; padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: bold; white-space: nowrap; display: inline-block;">
                                                    Sin Area
                                                </span>
                                            @endif
                                        </div>

                                        <!-- Prendas -->
                                        <div style="display: flex; align-items: start; font-size: 0.9rem; color: #374151;">
                                            <div class="prenda-list">
                                                @php
                                                    $prendasAgrupadas = [];
                                                    foreach($proceso['prendas'] as $prenda) {
                                                        if(!empty($prenda->color_nombre) && !empty($prenda->cantidad_color)) {
                                                            // Con color
                                                            $key = $prenda->nombre_prenda . '|' . $prenda->color_nombre;
                                                            if(!isset($prendasAgrupadas[$key])) {
                                                                $prendasAgrupadas[$key] = $prenda->cantidad_color;
                                                            }
                                                        } elseif(!empty($prenda->cantidad_talla) && empty($prenda->color_nombre)) {
                                                            // Sin color
                                                            $tela = !empty($prenda->tela) ? ' ' . $prenda->tela : '';
                                                            $key = $prenda->nombre_prenda . $tela . '|sin-color';
                                                            if(!isset($prendasAgrupadas[$key])) {
                                                                $prendasAgrupadas[$key] = $prenda->cantidad_talla;
                                                            }
                                                        }
                                                    }
                                                @endphp
                                                @foreach($prendasAgrupadas as $prenda => $cantidad)
                                                    <div style="margin-bottom: 0.25rem;">
                                                        @php
                                                            $partes = explode('|', $prenda);
                                                            $nombrePrenda = $partes[0];
                                                            $tipo = $partes[1] ?? 'sin-color';
                                                            
                                                            if($tipo === 'sin-color') {
                                                                echo $cantidad . ' ' . $nombrePrenda;
                                                            } else {
                                                                echo $cantidad . ' ' . $nombrePrenda . ' color ' . $tipo;
                                                            }
                                                        @endphp
                                                    </div>
                                                @endforeach
                                                @if(count($prendasAgrupadas) === 0)
                                                    <div>-</div>
                                                @endif
                                            </div>
                                        </div>

                                        <!-- Novedades -->
                                        <div style="display: flex; align-items: center; font-size: 0.85rem; color: #374151;">
                                            @php
                                                $novedadesTexto = '';
                                                try {
                                                    $pedido = \App\Models\PedidoProduccion::find($proceso['pedido_id']);
                                                    if ($pedido && $pedido->prendas && $pedido->prendas->count() > 0) {
                                                        $prendaTarget = $pedido->prendas->firstWhere('id', $proceso['prenda_id'] ?? null);
                                                        $prendasIter = $prendaTarget ? collect([$prendaTarget]) : $pedido->prendas;

                                                        $novedadesRecibo = [];
                                                        foreach ($prendasIter as $prenda) {
                                                            $novedadesPrenda = $prenda->novedadesRecibo()
                                                                ->where('numero_recibo', $proceso['numero_recibo'])
                                                                ->orderBy('creado_en', 'desc')
                                                                ->get();

                                                            foreach ($novedadesPrenda as $novedad) {
                                                                $textoLimpio = str_replace(["\r", "\n", "'", '"'], ' ', $novedad->novedad_texto);
                                                                $novedadesRecibo[] = $textoLimpio;
                                                            }
                                                        }

                                                        if (!empty($novedadesRecibo)) {
                                                            $novedadesTexto = implode(' | ', $novedadesRecibo);
                                                        }
                                                    }
                                                } catch (\Exception $e) {
                                                    $novedadesTexto = '';
                                                }
                                            @endphp

                                            <button
                                                type="button"
                                                data-pedido-id="{{ $proceso['pedido_id'] }}"
                                                data-numero-recibo="{{ $proceso['numero_recibo'] }}"
                                                data-novedades="{{ addslashes(str_replace(["\r", "\n"], ' ', $novedadesTexto)) }}"
                                                onclick="event.stopPropagation(); openNovedadesModalRecibo(this)"
                                                title="Ver novedades del recibo"
                                                style="
                                                    width: 100%;
                                                    text-align: left;
                                                    background: #f9fafb;
                                                    border: 1px solid #e5e7eb;
                                                    border-radius: 8px;
                                                    padding: 6px 10px;
                                                    display: flex;
                                                    align-items: center;
                                                    justify-content: space-between;
                                                    gap: 8px;
                                                    cursor: pointer;
                                                    transition: background 0.2s ease;
                                                "
                                                onmouseover="this.style.background='#f3f4f6'"
                                                onmouseout="this.style.background='#f9fafb'"
                                            >
                                                @if($novedadesTexto)
                                                    <span style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 120px;">{{ \Illuminate\Support\Str::limit(str_replace(["\r", "\n"], ' ', $novedadesTexto), 28, '...') }}</span>
                                                @else
                                                    <span style="color:#9ca3af;">Sin novedades</span>
                                                @endif
                                                <i class="fas fa-edit" style="color:#6b7280;"></i>
                                            </button>
                                        </div>

                                        <!-- Asesora -->
                                        <div style="display: flex; align-items: center; font-size: 0.9rem; color: #374151;">
                                            {{ $proceso['asesor'] }}
                                        </div>

                                        <!-- Color Selector -->
                                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                                            <div class="color-selector-wrapper" data-recibo-id="{{ $proceso['numero_recibo'] }}" style="position: relative; display: flex; gap: 0.3rem; align-items: center;">
                                                <button type="button" class="color-btn" data-color="#e0f2fe" title="Azul claro" style="width: 24px; height: 24px; border-radius: 50%; border: 2px solid #cbd5e1; background: #e0f2fe; cursor: pointer; transition: all 0.2s;"></button>
                                                <button type="button" class="color-btn" data-color="#fef08a" title="Amarillo" style="width: 24px; height: 24px; border-radius: 50%; border: 2px solid #cbd5e1; background: #fef08a; cursor: pointer; transition: all 0.2s;"></button>
                                                <button type="button" class="color-btn" data-color="#fecaca" title="Rojo claro" style="width: 24px; height: 24px; border-radius: 50%; border: 2px solid #cbd5e1; background: #fecaca; cursor: pointer; transition: all 0.2s;"></button>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </div>
                </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Novedades (mismo componente de /recibos-costura) -->
<x-modals.novedades-edit-modal />

<!-- Modal Filtro Dinamico -->
<div id="modalFiltro" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalFiltroTitulo">Filtrar</h2>
            <button class="btn-close" type="button" onclick="cerrarModalFiltro()">&times;</button>
        </div>
        <div class="modal-body" id="filtroContenido">
            <!-- Contenido Dinamico -->
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-primary" onclick="aplicarFiltroColumna(event)">Aplicar</button>
            <button type="button" class="btn btn-secondary" onclick="cerrarModalFiltro()">Cancelar</button>
            <button type="button" class="btn btn-secondary" onclick="limpiarFiltroActual()" style="margin-left: auto;">Limpiar Filtro</button>
        </div>
    </div>
</div>

@push('scripts')
<script src="{{ asset('js/supervisor-pedidos/shared/receipts-renderers.js') }}?v={{ filemtime(public_path('js/supervisor-pedidos/shared/receipts-renderers.js')) }}"></script>
<script src="{{ asset('js/supervisor-pedidos/shared/receipts-api-filters.js') }}?v={{ filemtime(public_path('js/supervisor-pedidos/shared/receipts-api-filters.js')) }}"></script>
<script src="{{ asset('js/recibos-novedades.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/supervisor-pedidos/sidebar-badge-manager.js') }}?v={{ time() }}"></script>
<script>
let filtroActual = null;

// Wrapper igual que en /recibos-costura
function openNovedadesModalRecibo(button) {
    const pedidoId = button.getAttribute('data-pedido-id');
    const numeroRecibo = button.getAttribute('data-numero-recibo');
    const novedadesActuales = button.getAttribute('data-novedades') || '';

    if (typeof abrirModalNovedadesRecibo === 'function') {
        abrirModalNovedadesRecibo(pedidoId, numeroRecibo);
        return;
    }

    setTimeout(() => {
        if (typeof abrirModalNovedadesRecibo === 'function') {
            abrirModalNovedadesRecibo(pedidoId, numeroRecibo);
        } else {
            alert(`Novedades del recibo ${numeroRecibo}:\n\n${novedadesActuales || 'Sin novedades'}`);
        }
    }, 100);
}

function escapeHtml(str) {
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

const receiptsFilters = window.SupervisorReceiptsApiFilters.create({
    contentSelector: '#supervisorPendientesControlCalidadContent',
    openButtonSelector: '#supervisorPendientesControlCalidadContent .btn-filter-column',
    filterOptionsEndpoint: (columna) => `/api/supervisor-pedidos/recibos/pendientes-control-calidad/filtro-opciones/${columna}`,
    navigate: (url) => window.navegarPendientesControlCalidad(url),
    titleMap: {
        fecha_creacion: 'Filtrar Fecha de Creación',
        numero_recibo: 'Filtrar N° Recibo',
        cliente: 'Filtrar Cliente',
        area: 'Filtrar Área',
        prendas: 'Filtrar Prendas',
        asesor: 'Filtrar Asesora'
    }
});

function abrirModalFiltro(columna) { receiptsFilters.open(columna); }
function cerrarModalFiltro() { receiptsFilters.close(); }
function aplicarFiltroColumna(event) { receiptsFilters.apply(event); }
function limpiarFiltroActual() { receiptsFilters.clearCurrent(); }
function actualizarIndicadoresFiltros() { receiptsFilters.refreshIndicators(); }

receiptsFilters.bindUi();
actualizarIndicadoresFiltros();

function construirUrlApiPendientesControlCalidad(urlString) {
    const source = new URL(urlString, window.location.origin);
    return `/api/supervisor-pedidos/recibos/pendientes-control-calidad${source.search || ''}`;
}

const receiptsRenderers = window.SupervisorReceiptsRenderers;

window.navegarPendientesControlCalidad = async function navegarPendientesControlCalidad(urlString, options = {}) {
    const { pushState = true } = options;
    const container = document.getElementById('supervisorPendientesControlCalidadContent');
    const rows = document.getElementById('controlCalidadRows');
    if (!container) {
        window.location.href = urlString;
        return;
    }
    if (!rows) return;

    try {
        container.style.opacity = '0.6';
        container.style.pointerEvents = 'none';

        const apiUrl = construirUrlApiPendientesControlCalidad(urlString);
        const res = await fetch(apiUrl, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            cache: 'no-store'
        });

        const payload = await res.json();
        const procesos = payload?.data?.procesosConCantidad;
        if (!res.ok || !Array.isArray(procesos)) {
            window.location.href = urlString;
            return;
        }

        if (procesos.length === 0) {
            rows.innerHTML = receiptsRenderers.emptyStateHtml();
        } else {
            rows.innerHTML = procesos.map((proceso) => receiptsRenderers.renderSewingRow(proceso, escapeHtml)).join('');
        }

        if (pushState) {
            window.history.pushState({ url: urlString }, '', urlString);
        }

        actualizarIndicadoresFiltros();
        inicializarSelectorColores();
        window.dispatchEvent(new Event('supervisorPedidos:filtersUpdated'));
    } catch (e) {
        window.location.href = urlString;
        return;
    } finally {
        container.style.opacity = '';
        container.style.pointerEvents = '';
    }
}

window.addEventListener('popstate', function() {
    navegarPendientesControlCalidad(window.location.href, { pushState: false });
});

document.addEventListener('click', function(e) {
    const a = e.target.closest('#supervisorPendientesControlCalidadContent a');
    if (!a) return;
    const href = a.getAttribute('href');
    if (!href) return;
    if (href.startsWith('#')) return;
    if (a.target && a.target !== '_self') return;
    if (a.hasAttribute('download')) return;
    if (!href.startsWith(window.location.origin) && !href.startsWith('/')) return;

    const urlAbs = href.startsWith('http') ? href : (window.location.origin + href);
    let path = '';
    try {
        path = new URL(urlAbs).pathname || '';
    } catch (e) {
        return;
    }

    if (!path.startsWith('/supervisor-pedidos/pendientes-control-calidad')) return;
    e.preventDefault();
    navegarPendientesControlCalidad(urlAbs);
});

function inicializarSelectorColores() {
    // Aplicar colores guardados al cargar la pagina
    document.querySelectorAll('[data-row="processo"]').forEach((fila) => {
        const color = fila.getAttribute('data-color-stored');
        
        if (color && color.trim()) {
            // Aplicar el color al fondo de la fila
            fila.style.setProperty('--row-bg-color', color);
            
            // Encontrar y marcar el boton correspondiente
            const wrapper = fila.querySelector('.color-selector-wrapper');
            if (wrapper) {
                wrapper.querySelectorAll('.color-btn').forEach(btn => {
                    if (btn.getAttribute('data-color') === color) {
                        btn.style.boxShadow = '0 0 0 2px #1e40af';
                    }
                });
            }
        }
    });

    // Configurar manejadores de clic para los botones de color
    document.querySelectorAll('.color-btn').forEach((btn) => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const wrapper = this.closest('.color-selector-wrapper');
            const reciboId = wrapper.getAttribute('data-recibo-id');
            const color = this.getAttribute('data-color');
            const filaBg = wrapper.closest('[data-row="processo"]');

            // Aplicar color a la fila
            filaBg.style.setProperty('--row-bg-color', color);
            filaBg.setAttribute('data-color-stored', color);
            
            // Retroalimentacion visual
            wrapper.querySelectorAll('.color-btn').forEach(b => b.style.boxShadow = '');
            this.style.boxShadow = '0 0 0 2px #1e40af';
            
            // Guardar en BD
            guardarColorCostura(reciboId, color);
        });
    });
}

// Ejecutar al cargar
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', inicializarSelectorColores);
} else {
    inicializarSelectorColores();
}

// Funcion para guardar el color en la BD
async function guardarColorCostura(reciboId, color) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    try {
        const response = await fetch('/api/supervisor-pedidos/recibos/guardar-color-control-calidad', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({
            numero_recibo: reciboId,
            color: color
        })
        });

        if (!response.ok) {
            const errorText = await response.text();
            console.error('Error al guardar color (HTTP):', response.status, errorText);
            return;
        }

        const data = await response.json();
        if (!data?.success) {
            console.error('Error al guardar color (API):', data);
        }
    } catch (error) {
        console.error('Error al guardar color:', error);
    }
}
</script>
@endpush

@endsection


