@extends('layouts.visualizador-logo')

@section('title', 'Pedidos Logo - Bordado/Estampado')

@section('page-title', 'Pedidos Logo')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/visualizador-logo/pedidos-logo.css') }}">
@endpush

@section('content')
@php($isDisenadorLogos = Auth::user() && Auth::user()->hasRole('diseñador-logos'))
@php($isBordador = Auth::user() && Auth::user()->hasRole('bordador'))
@php($isVisualizadorLogo = Auth::user() && Auth::user()->hasRole('visualizador_cotizaciones_logo'))
@php($isMinimalLogoRole = $isDisenadorLogos || $isBordador)
<div style="padding: 1rem 1.25rem 2rem 0.25rem; background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); min-height: calc(100vh - 60px); font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;">
    <!-- Tabla de Pedidos Logo -->
    <div style="display: flex; justify-content: flex-start;">
        <div style="width: 100%; max-width: 1500px;">
            @if(!$isMinimalLogoRole)
                <div style="display: flex; gap: 12px; align-items: center; justify-content: flex-start; margin-bottom: 14px; flex-wrap: wrap;">
                    <button id="btn-filter-bordado" type="button" onclick="setFiltroRecibosLogo('bordado')" style="position: relative; padding: 10px 14px; border-radius: 10px; border: 2px solid #0ea5e9; background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%); color: white; font-weight: 700; cursor: pointer; transition: all 0.2s; box-shadow: 0 2px 8px rgba(14, 165, 233, 0.18);">
                        PEDIDOS BORDADO
                        <span id="badge-bordado" class="conteo-pendiente-badge" style="position: absolute; top: -8px; right: -8px; background: #ef4444; color: white; border-radius: 50%; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 700; border: 2px solid white; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2); display: none;">0</span>
                    </button>
                    <button id="btn-filter-estampado" type="button" onclick="setFiltroRecibosLogo('estampado')" style="position: relative; padding: 10px 14px; border-radius: 10px; border: 2px solid #e2e8f0; background: white; color: #334155; font-weight: 700; cursor: pointer; transition: all 0.2s; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);">
                        PEDIDOS ESTAMPADO
                        <span id="badge-estampado" class="conteo-pendiente-badge" style="position: absolute; top: -8px; right: -8px; background: #ef4444; color: white; border-radius: 50%; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 700; border: 2px solid white; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2); display: none;">0</span>
                    </button>
                    <button id="btn-filter-dtf" type="button" onclick="setFiltroRecibosLogo('dtf')" style="position: relative; padding: 10px 14px; border-radius: 10px; border: 2px solid #e2e8f0; background: white; color: #334155; font-weight: 700; cursor: pointer; transition: all 0.2s; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);">
                        PEDIDOS DTF
                        <span id="badge-dtf" class="conteo-pendiente-badge" style="position: absolute; top: -8px; right: -8px; background: #ef4444; color: white; border-radius: 50%; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 700; border: 2px solid white; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2); display: none;">0</span>
                    </button>
                    <button id="btn-filter-sublimado" type="button" onclick="setFiltroRecibosLogo('sublimado')" style="position: relative; padding: 10px 14px; border-radius: 10px; border: 2px solid #e2e8f0; background: white; color: #334155; font-weight: 700; cursor: pointer; transition: all 0.2s; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);">
                        PEDIDOS SUBLIMADO
                        <span id="badge-sublimado" class="conteo-pendiente-badge" style="position: absolute; top: -8px; right: -8px; background: #ef4444; color: white; border-radius: 50%; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 700; border: 2px solid white; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2); display: none;">0</span>
                    </button>
                </div>
            @endif
            <!-- Container -->
            <div style="background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07), 0 1px 3px rgba(0, 0, 0, 0.06); border: 1px solid #e2e8f0;">
                <div style="overflow-x: auto; -webkit-overflow-scrolling: touch;">
                    <div style="{{ $isMinimalLogoRole ? 'zoom: 0.75;' : 'zoom: 0.75;' }}">
                    <!-- Header -->
                    <div style="
                        min-width: {{ $isMinimalLogoRole ? '1080px' : '2150px' }};
                        background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
                        color: white;
                        padding: 1rem 1.5rem;
                        display: grid;
                        grid-template-columns: {{ $isBordador ? '100px 200px 120px 340px 180px 140px' : ($isMinimalLogoRole ? '100px 200px 340px 120px 180px 140px' : '100px 120px 200px 300px 120px 170px 230px 190px 260px 160px') }};
                        gap: 1rem;
                        font-weight: 700;
                        font-size: 0.9rem;
                        text-transform: uppercase;
                        letter-spacing: 0.05em;
                    ">
                        <div style="color: #cbd5e1;">Acciones</div>
                        @if($isBordador)
                            <div style="color: #cbd5e1;">
                                Número Recibo
                            </div>
                            <div style="color: #cbd5e1;">Cantidad</div>
                        @endif
                        @if(!$isBordador)
                            <div style="color: #cbd5e1;">
                                @if($isMinimalLogoRole)
                                    Número Recibo
                                @else
                                    <div class="th-wrapper">
                                        <span>Número Recibo</span>
                                        <button class="btn-filter-column" type="button" data-column="numero_recibo" onclick="openLogoColumnFilter('numero_recibo', 'Número Recibo')"><i class="fas fa-filter"></i><span class="filter-badge" data-badge="numero_recibo">0</span></button>
                                    </div>
                                @endif
                            </div>
                        @endif
                        @if(!$isMinimalLogoRole)
                            <div style="color: #cbd5e1;">
                                <div class="th-wrapper">
                                    <span>Total días</span>
                                    <button class="btn-filter-column" type="button" data-column="total_dias" onclick="openLogoColumnFilter('total_dias', 'Total días')"><i class="fas fa-filter"></i><span class="filter-badge" data-badge="total_dias">0</span></button>
                                </div>
                            </div>
                        @endif
                        <div style="color: #cbd5e1;">
                            @if($isMinimalLogoRole)
                                Cliente
                            @else
                                <div class="th-wrapper">
                                    <span>Cliente</span>
                                    <button class="btn-filter-column" type="button" data-column="cliente" onclick="openLogoColumnFilter('cliente', 'Cliente')"><i class="fas fa-filter"></i><span class="filter-badge" data-badge="cliente">0</span></button>
                                </div>
                            @endif
                        </div>
                        @if(!$isBordador)
                            <div style="color: #cbd5e1;">Cantidad</div>
                        @endif
                        <div style="color: #cbd5e1;">Fecha Creación</div>
                        @if($isBordador)
                            <div style="color: #cbd5e1;">Completado</div>
                        @elseif($isMinimalLogoRole)
                            <div style="color: #cbd5e1;">Estado</div>
                        @endif
                        @if(!$isMinimalLogoRole)
                            <div style="color: #cbd5e1;">
                                <div class="th-wrapper">
                                    <span>Área</span>
                                    <button class="btn-filter-column" type="button" data-column="area" onclick="openLogoColumnFilter('area', 'Área')"><i class="fas fa-filter"></i><span class="filter-badge" data-badge="area">0</span></button>
                                </div>
                            </div>
                            <div style="color: #cbd5e1;">
                                <div class="th-wrapper">
                                    <span>Asesora</span>
                                    <button class="btn-filter-column" type="button" data-column="asesora" onclick="openLogoColumnFilter('asesora', 'Asesora')"><i class="fas fa-filter"></i><span class="filter-badge" data-badge="asesora">0</span></button>
                                </div>
                            </div>
                            <div style="color: #cbd5e1;">
                                <div class="th-wrapper">
                                    <span>Novedad</span>
                                    <button class="btn-filter-column" type="button" data-column="novedades" onclick="openLogoColumnFilter('novedades', 'Novedad')"><i class="fas fa-filter"></i><span class="filter-badge" data-badge="novedades">0</span></button>
                                </div>
                            </div>
                            <div style="color: #cbd5e1; white-space: nowrap;">Fecha Entrega</div>
                        @endif
                    </div>

                    <!-- Filas -->
                    <div id="pedidos-body">
                        <div style="padding: 3rem 2rem; text-align: center; color: #64748b; background: #f8fafc;">
                            <div style="font-size: 2.5rem; color: #cbd5e1; margin-bottom: 1rem;">
                                <i class="fas fa-spinner fa-spin"></i>
                            </div>
                            <p style="margin: 0; font-size: 1rem; font-weight: 500;">Cargando pedidos...</p>
                        </div>
                    </div>
                    </div>
                </div>
            </div>
            
            <!-- Paginación -->
            <div id="paginacion-container" style="margin-top: 1.5rem; text-align: center;"></div>
        </div>
    </div>
</div>

@if(!$isMinimalLogoRole)
    <div id="logo-filter-modal-overlay" class="logo-filter-modal-overlay" onclick="closeLogoFilterModal(event)">
        <div class="logo-filter-modal" onclick="event.stopPropagation()">
            <div class="logo-filter-modal-header">
                <h3 id="logo-filter-title">Filtrar</h3>
                <button type="button" class="logo-filter-modal-close" onclick="closeLogoFilterModal()">&times;</button>
            </div>
            <div class="logo-filter-modal-body">
                <input id="logo-filter-search" class="logo-filter-search" type="text" placeholder="Buscar..." />
                <div id="logo-filter-options" class="logo-filter-options"></div>
            </div>
            <div class="logo-filter-modal-footer">
                <button type="button" class="logo-filter-btn reset" onclick="resetLogoColumnFilter()">Reset</button>
                <button type="button" class="logo-filter-btn apply" onclick="applyLogoColumnFilter()">Aplicar</button>
            </div>
        </div>
    </div>

    <button id="floating-clear-filters-logo" class="floating-clear-filters-logo" type="button" onclick="clearAllLogoFilters()">
        <i class="fas fa-broom"></i>
        <div class="floating-clear-filters-logo-tooltip">Limpiar filtros</div>
    </button>
@endif

<script>
window.__pedidosRecibosLoaderUrl = @json(asset('js/modulos/pedidos-recibos/loader.js') . '?v=' . time());
window.__pedidosRecibosLoaderPromise = null;

window.__ensurePedidosRecibosModule = async function() {
    const tieneApiGlobal = typeof window.openOrderDetailModalWithProcess === 'function';
    const tieneInstancia = !!(window.pedidosRecibosModule && typeof window.pedidosRecibosModule.abrirRecibo === 'function');
    if (tieneApiGlobal || tieneInstancia) return true;

    const loaderUrl = String(window.__pedidosRecibosLoaderUrl || '').trim();
    if (!loaderUrl) return false;

    try {
        if (!window.__pedidosRecibosLoaderPromise) {
            window.__pedidosRecibosLoaderPromise = import(loaderUrl);
        }
        await window.__pedidosRecibosLoaderPromise;
    } catch (error) {
        console.error('[pedidos-logo] Error cargando loader de recibos:', error);
        return false;
    }

    return (
        typeof window.openOrderDetailModalWithProcess === 'function' ||
        !!(window.pedidosRecibosModule && typeof window.pedidosRecibosModule.abrirRecibo === 'function')
    );
};

// Función global para ver recibo directamente (sin selector)
window.verRecibo = async function(pedidoId, prendaId, tipoProceso, esParcial = false, pedidoParcialId = null, nombreProceso = null, numeroRecibo = null) {
    const moduloListo = await window.__ensurePedidosRecibosModule();
    if (!moduloListo) {
        console.error('El módulo de recibos no está disponible');
        alert('Error: El módulo de recibos no está disponible. Por favor recargue la página.');
        return;
    }

    const tipo = String(tipoProceso);

    // Si es anexo (recibo parcial), abrir usando el flujo de parcial para que cargue sus tallas/consecutivo.
    if (esParcial && pedidoParcialId) {
        if (window.pedidosRecibosModule && typeof window.pedidosRecibosModule.abrirReciboParcial === 'function') {
            const nombre = nombreProceso ? String(nombreProceso) : tipo;
            return window.pedidosRecibosModule.abrirReciboParcial(pedidoId, prendaId, tipo, Number(pedidoParcialId), nombre);
        }

        console.error('PedidosRecibosModule no está disponible para abrir anexos');
        alert('Error: No se pudo abrir el anexo. Por favor recargue la página.');
        return;
    }

    // Caso normal: abrir recibo base
    if (typeof window.openOrderDetailModalWithProcess === 'function') {
        return window.openOrderDetailModalWithProcess(pedidoId, prendaId, tipo, null, numeroRecibo);
    }

    if (window.pedidosRecibosModule && typeof window.pedidosRecibosModule.abrirRecibo === 'function') {
        return window.pedidosRecibosModule.abrirRecibo(pedidoId, prendaId, tipo, null, {
            targetConsecutivo: numeroRecibo || null
        });
    }

    console.error('La función openOrderDetailModalWithProcess no está disponible');
    alert('Error: El módulo de recibos no está disponible. Por favor recargue la página.');
};

window.__areasPermitidasLogo = [
    'CREACION_DE_ORDEN',
    'PENDIENTE_DISENO',
    'DISENO',
    'PENDIENTE_CONFIRMAR',
    'CORTE_Y_APLIQUE',
    'BORD_POR_FUERA',
    'HACIENDO_MUESTRA',
    'ESTAMPANDO',
    'BORDANDO',
    'BORDADO',
    'ENTREGADO',
    'ANULADO',
    'PENDIENTE',
];

window.__isDisenadorLogos = {{ $isMinimalLogoRole ? 'true' : 'false' }};
window.__isDisenadorLogosRole = {{ $isDisenadorLogos ? 'true' : 'false' }};
window.__isBordadorRole = {{ $isBordador ? 'true' : 'false' }};
window.__isVisualizadorCotizacionesLogoRole = {{ $isVisualizadorLogo ? 'true' : 'false' }};
window.__incluirEntregadosLogo = {{ request()->query('vista') === 'todos' ? 'true' : 'false' }};

window.__festivosLogo = @json(\App\Models\Festivo::pluck('fecha')->toArray());

window.__calcularDiasHabilesLogo = function(fechaInicio, fechaFin, festivos) {
    if (!fechaInicio || !fechaFin) return 0;

    const start = new Date(fechaInicio);
    const end = new Date(fechaFin);

    if (isNaN(start.getTime()) || isNaN(end.getTime())) return 0;

    const startYmd = start.toISOString().slice(0, 10);
    const endYmd = end.toISOString().slice(0, 10);
    if (startYmd === endYmd) return 0;

    if (end < start) return 0;

    const festivosSet = new Set((festivos || []).map(f => {
        if (typeof f === 'string') return f.slice(0, 10);
        try {
            const d = new Date(f);
            return isNaN(d.getTime()) ? null : d.toISOString().slice(0, 10);
        } catch (e) {
            return null;
        }
    }).filter(Boolean));

    let diasHabiles = 0;
    const current = new Date(start);
    current.setHours(0, 0, 0, 0);
    const endDay = new Date(end);
    endDay.setHours(0, 0, 0, 0);

    while (current <= endDay) {
        const day = current.getDay();
        const ymd = current.toISOString().slice(0, 10);
        if (day !== 0 && day !== 6 && !festivosSet.has(ymd)) {
            diasHabiles++;
        }
        current.setDate(current.getDate() + 1);
    }

    return Math.max(0, diasHabiles - 1);
};

window.__actualizarDiasYColoresLogo = function() {
    const rows = document.querySelectorAll('[data-recibo-row="1"][data-proceso-id]');
    rows.forEach((row) => {
        const procesoId = row.dataset.procesoId;
        const fechaCreacionRecibo = row.dataset.fechaCreacionRecibo;
        const fechaEntrega = row.dataset.fechaEntrega;
        if (!fechaCreacionRecibo) {
            window.__aplicarColoresFilaReciboLogo(procesoId);
            return;
        }

        const fechaFin = fechaEntrega ? new Date(fechaEntrega) : new Date();
        const totalDias = window.__calcularDiasHabilesLogo(fechaCreacionRecibo, fechaFin, window.__festivosLogo);
        row.dataset.totalDias = String(totalDias);

        const totalDiasCell = row.querySelector('[data-field="total_dias"]');
        if (totalDiasCell) {
            totalDiasCell.textContent = String(totalDias);
        }

        window.__aplicarColoresFilaReciboLogo(procesoId);
    });
};

window.__programarActualizacionDiariaLogo = function() {
    const now = new Date();
    const next = new Date(now);
    next.setDate(now.getDate() + 1);
    next.setHours(0, 1, 0, 0);
    const ms = Math.max(1000, next.getTime() - now.getTime());
    setTimeout(() => {
        window.__actualizarDiasYColoresLogo();
        window.__programarActualizacionDiariaLogo();
    }, ms);
};

window.__guardarAreaNovedadLogo = async function(procesoPrendaDetalleId, area, novedades, pedidoParcialId = null, consecutivoReciboId = null) {
    // If not explicitly provided, fallback to first matching row (legacy behavior).
    const rowResolved = (() => {
        const row = document.querySelector(`[data-recibo-row="1"][data-proceso-id="${procesoPrendaDetalleId}"]`);
        return row || null;
    })();
    const pedidoParcialResolved = pedidoParcialId ?? (rowResolved ? rowResolved.dataset.pedidoParcialId : null);
    const consecutivoReciboResolved = consecutivoReciboId ?? (rowResolved ? rowResolved.dataset.consecutivoReciboId : null);
    const prendaPedidoIdResolved = rowResolved ? rowResolved.dataset.prendaPedidoId : null;

    const response = await fetch(`{{ route('visualizador-logo.pedidos-logo.area-novedad') }}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': `{{ csrf_token() }}`,
            'Accept': 'application/json',
        },
        body: JSON.stringify({
            proceso_prenda_detalle_id: procesoPrendaDetalleId,
            area,
            novedades,
            pedido_parcial_id: pedidoParcialResolved ? parseInt(pedidoParcialResolved) : null,
            consecutivo_recibo_id: consecutivoReciboResolved ? parseInt(consecutivoReciboResolved) : null,
            prenda_pedido_id: prendaPedidoIdResolved ? parseInt(prendaPedidoIdResolved) : null,
        }),
    });

    const data = await response.json().catch(() => null);
    if (!response.ok || !data || data.success !== true) {
        throw new Error((data && data.message) ? data.message : 'No se pudo guardar');
    }

    return data;
};

window.__getColoresFilaReciboLogo = function(area, totalDias) {
    let rowBg = 'white';
    let rowHoverBg = '#f8fafc';
    let rowBorderLeft = 'transparent';

    if (area === 'ENTREGADO') {
        rowBg = '#a7cdff';
        rowHoverBg = '#82baff';
        rowBorderLeft = '#0284c7';
    } else if (area === 'ANULADO') {
        rowBg = '#9ca3af';
        rowHoverBg = '#6b7280';
        rowBorderLeft = '#374151';
    } else if (Number(totalDias) >= 3) {
        rowBg = '#fecaca';
        rowHoverBg = '#fca5a5';
        rowBorderLeft = '#dc2626';
    }

    return { rowBg, rowHoverBg, rowBorderLeft };
};

window.__getEstiloAreaDropdownLogo = function(area) {
    const map = {
        'CREACION_DE_ORDEN': { bg: '#dbeafe', color: '#1e40af', border: '#93c5fd' },
        'PENDIENTE_DISENO': { bg: '#fef3c7', color: '#92400e', border: '#f59e0b' },
        'DISENO': { bg: '#fdba74', color: '#7c2d12', border: '#f97316' },
        'PENDIENTE_CONFIRMAR': { bg: '#fde68a', color: '#92400e', border: '#f59e0b' },
        'CORTE_Y_APLIQUE': { bg: '#d4a574', color: '#78350f', border: '#92400e' },
        'BORD_POR_FUERA': { bg: '#f5d0fe', color: '#701a75', border: '#c026d3' },
        'HACIENDO_MUESTRA': { bg: '#e9d5ff', color: '#6b21a8', border: '#a855f7' },
        'BORDANDO': { bg: '#f97316', color: '#ffffff', border: '#ea580c' },
        'BORDADO': { bg: '#10b981', color: '#ffffff', border: '#059669' },
        'ENTREGADO': { bg: '#a7cdff', color: '#0b3a67', border: '#0284c7' },
        'ANULADO': { bg: '#374151', color: '#ffffff', border: '#111827' },
        'PENDIENTE': { bg: '#e2e8f0', color: '#0f172a', border: '#94a3b8' },
    };

    return map[area] || { bg: '#f8d376ff', color: '#1f2937', border: '#e5e7eb' };
};

window.__aplicarEstiloAreaDropdownLogo = function(selectEl) {
    if (!selectEl) return;
    const area = selectEl.value;
    const { bg, color, border } = window.__getEstiloAreaDropdownLogo(area);
    selectEl.style.background = bg;
    selectEl.style.color = color;
    selectEl.style.border = `1px solid ${border}`;
};

window.__obtenerMapaCompletadosLocalLogo = function() {
    try {
        const raw = localStorage.getItem('visualizador_logo_bordador_completados');
        if (!raw) return {};

        const parsed = JSON.parse(raw);
        if (!parsed || typeof parsed !== 'object') return {};

        return parsed;
    } catch (error) {
        return {};
    }
};

window.__estaCompletadoLocalmenteLogo = function(procesoId) {
    const mapa = window.__obtenerMapaCompletadosLocalLogo();
    return mapa[String(procesoId)] === true;
};

window.__aplicarColoresFilaReciboLogo = function(procesoId) {
    const row = document.querySelector(`[data-recibo-row="1"][data-proceso-id="${procesoId}"]`);
    if (!row) return;

    const areaSelect = row.querySelector(`select[data-proceso-id="${procesoId}"][data-field="area"]`);
    const area = areaSelect ? areaSelect.value : (row.dataset.area || 'PENDIENTE');
    const totalDias = row.dataset.totalDias || 0;
    const completado = row.dataset.completado === '1' || window.__estaCompletadoLocalmenteLogo(procesoId);

    const { rowBg, rowHoverBg, rowBorderLeft } = completado
        ? { rowBg: '#a7cdff', rowHoverBg: '#82baff', rowBorderLeft: '#0284c7' }
        : window.__getColoresFilaReciboLogo(area, totalDias);

    row.classList.toggle('recibo-completado-logo', completado);
    row.style.background = rowBg;
    row.style.borderLeft = `4px solid ${rowBorderLeft}`;
    row.dataset.completado = completado ? '1' : '0';
    row.setAttribute('onmouseover', `this.style.background='${rowHoverBg}'; this.style.boxShadow='inset 0 0 0 1px #e2e8f0'`);
    row.setAttribute('onmouseout', `this.style.background='${rowBg}'; this.style.boxShadow='none'`);
};

// Estado global del filtro (por defecto: bordado)
window.__filtroRecibosLogo = 'bordado';

window.setFiltroRecibosLogo = function(nuevoFiltro) {
    const filtrosValidos = ['bordado', 'estampado', 'dtf', 'sublimado'];
    window.__filtroRecibosLogo = filtrosValidos.includes(nuevoFiltro) ? nuevoFiltro : 'bordado';

    const btnBordado = document.getElementById('btn-filter-bordado');
    const btnEstampado = document.getElementById('btn-filter-estampado');
    const btnDtf = document.getElementById('btn-filter-dtf');
    const btnSublimado = document.getElementById('btn-filter-sublimado');

    const filtroActual = window.__filtroRecibosLogo;

    // Función para activar/desactivar botón
    const updateButtonStyle = (btn, isActive) => {
        if (!btn) return;
        if (isActive) {
            btn.style.background = 'linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%)';
            btn.style.borderColor = '#0ea5e9';
            btn.style.color = 'white';
        } else {
            btn.style.background = 'white';
            btn.style.borderColor = '#e2e8f0';
            btn.style.color = '#334155';
        }
    };

    updateButtonStyle(btnBordado, filtroActual === 'bordado');
    updateButtonStyle(btnEstampado, filtroActual === 'estampado');
    updateButtonStyle(btnDtf, filtroActual === 'dtf');
    updateButtonStyle(btnSublimado, filtroActual === 'sublimado');

    // Resetear paginación y búsqueda al cambiar filtro
    const searchInput = document.getElementById('search-input');
    const clearSearchBtn = document.getElementById('clear-search');
    if (searchInput) searchInput.value = '';
    if (clearSearchBtn) clearSearchBtn.style.display = 'none';

    // Recargar conteos
    if (typeof window.__cargarConteosPendientes === 'function') {
        window.__cargarConteosPendientes();
    }

    // Disparar recarga (hook definido en DOMContentLoaded)
    if (typeof window.__reloadRecibosLogo === 'function') {
        window.__reloadRecibosLogo();
    }
};

document.addEventListener('DOMContentLoaded', function() {
    let paginaActual = 1;
    let searchTimeout;
    let pedidosOriginales = [];
    let recibosUltimaCarga = [];

    // Cargar conteos de pendientes
    window.__cargarConteosPendientes = async function() {
        try {
            const response = await fetch(`{{ route('visualizador-logo.pedidos-logo.conteos-pendientes') }}`);
            const data = await response.json();

            if (data.success && data.conteos) {
                const conteos = data.conteos;
                const tiposFilters = ['bordado', 'estampado', 'dtf', 'sublimado'];

                tiposFilters.forEach(tipo => {
                    const cantidad = conteos[tipo] || 0;
                    const badge = document.getElementById(`badge-${tipo}`);
                    if (badge) {
                        badge.textContent = String(cantidad);
                        if (cantidad > 0) {
                            badge.style.display = 'flex';
                        } else {
                            badge.style.display = 'none';
                        }
                    }
                });
            }
        } catch (error) {
            console.error('Error cargando conteos de pendientes:', error);
        }
    };

    const STORAGE_KEY = 'visualizador_logo_pedidos_logo_filters';
    window.__logoColumnFilters = (() => {
        try {
            const raw = localStorage.getItem(STORAGE_KEY);
            return raw ? JSON.parse(raw) : {};
        } catch (e) {
            return {};
        }
    })();

    window.__logoFilterModalState = {
        columnKey: null,
        title: null,
        options: [],
        selected: new Set(),
    };

    function persistFilters() {
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(window.__logoColumnFilters || {}));
        } catch (e) {}
    }

    function updateFilterBadges() {
        document.querySelectorAll('.btn-filter-column').forEach(btn => {
            const key = btn.dataset.column;
            const values = (window.__logoColumnFilters && window.__logoColumnFilters[key]) ? window.__logoColumnFilters[key] : [];
            const badge = btn.querySelector('.filter-badge');
            if (badge) badge.textContent = String(values.length || 0);
            if (values.length) {
                btn.classList.add('has-filter');
            } else {
                btn.classList.remove('has-filter');
            }
        });

        const floatingBtn = document.getElementById('floating-clear-filters-logo');
        if (floatingBtn) {
            const totalActive = Object.values(window.__logoColumnFilters || {}).reduce((acc, arr) => acc + ((arr || []).length), 0);
            if (totalActive > 0) floatingBtn.classList.add('visible');
            else floatingBtn.classList.remove('visible');
        }
    }

    window.clearAllLogoFilters = function() {
        window.__logoColumnFilters = {};
        persistFilters();
        updateFilterBadges();
        // Recargar datos desde el backend sin filtros
        paginaActual = 1;
        const searchTerm = document.getElementById('search-input').value.trim();
        cargarRecibos(searchTerm);
    };

    window.openLogoColumnFilter = async function(columnKey, title) {
        window.__logoFilterModalState.columnKey = columnKey;
        window.__logoFilterModalState.title = title;

        const activeValues = (window.__logoColumnFilters && window.__logoColumnFilters[columnKey]) ? window.__logoColumnFilters[columnKey] : [];
        window.__logoFilterModalState.selected = new Set(activeValues);

        // Columnas que cargan todos los valores del backend
        const columnasCompletas = ['area', 'asesora'];
        
        let options = [];
        if (columnKey === 'area') {
            try {
                const response = await fetch(`{{ route('visualizador-logo.pedidos-logo.areas-unicas') }}?filtro=${window.__filtroRecibosLogo || 'bordado'}`);
                const data = await response.json();
                if (data.success && data.areas) {
                    options = data.areas.sort((a, b) => a.localeCompare(b, 'es'));
                }
            } catch (error) {
                console.error('Error cargando áreas del backend:', error);
                options = getUniqueOptionsForColumn(columnKey, recibosUltimaCarga);
            }
        } else if (columnKey === 'asesora') {
            try {
                const response = await fetch(`{{ route('visualizador-logo.pedidos-logo.asesoras-unicas') }}?filtro=${window.__filtroRecibosLogo || 'bordado'}`);
                const data = await response.json();
                if (data.success && data.asesoras) {
                    options = data.asesoras.sort((a, b) => a.localeCompare(b, 'es'));
                }
            } catch (error) {
                console.error('Error cargando asesoras del backend:', error);
                options = getUniqueOptionsForColumn(columnKey, recibosUltimaCarga);
            }
        } else {
            // Para otras columnas, mostrar solo los primeros 5 valores de la página actual
            options = getUniqueOptionsForColumn(columnKey, recibosUltimaCarga).slice(0, 5);
        }
        
        window.__logoFilterModalState.options = options;
        window.__logoFilterModalState.allOptionsLoaded = columnasCompletas.includes(columnKey);

        const overlay = document.getElementById('logo-filter-modal-overlay');
        const titleEl = document.getElementById('logo-filter-title');
        const searchEl = document.getElementById('logo-filter-search');

        if (!overlay || !titleEl || !searchEl) {
            console.error('No se encontró el modal de filtros (logo-filter-*) en el DOM');
            return;
        }

        titleEl.textContent = `Filtrar: ${title}`;
        searchEl.value = '';
        renderFilterOptions('');
        overlay.classList.add('active');
        setTimeout(() => searchEl.focus(), 50);
    };

    window.closeLogoFilterModal = function(e) {
        const overlay = document.getElementById('logo-filter-modal-overlay');
        overlay.classList.remove('active');
    };

    window.resetLogoColumnFilter = function() {
        const key = window.__logoFilterModalState.columnKey;
        if (!key) return;
        window.__logoColumnFilters[key] = [];
        persistFilters();
        updateFilterBadges();
        // Recargar datos desde el backend sin el filtro
        paginaActual = 1;
        const searchTerm = document.getElementById('search-input').value.trim();
        cargarRecibos(searchTerm);
        window.closeLogoFilterModal();
    };

    window.applyLogoColumnFilter = function() {
        const key = window.__logoFilterModalState.columnKey;
        if (!key) return;
        const values = Array.from(window.__logoFilterModalState.selected);
        window.__logoColumnFilters[key] = values;
        persistFilters();
        updateFilterBadges();
        // Recargar datos desde el backend con los filtros aplicados
        paginaActual = 1;
        const searchTerm = document.getElementById('search-input').value.trim();
        cargarRecibos(searchTerm);
        window.closeLogoFilterModal();
    };

    function getUniqueOptionsForColumn(columnKey, recibos) {
        const set = new Set();
        (recibos || []).forEach(r => {
            if (columnKey === 'total_dias') {
                set.add(String(r.total_dias ?? 0));
            } else {
                const value = (r && r[columnKey] !== undefined && r[columnKey] !== null) ? String(r[columnKey]).trim() : '';
                if (value) set.add(value);
            }
        });
        return Array.from(set).sort((a, b) => a.localeCompare(b, 'es'));
    }

    async function renderFilterOptions(search) {
        const container = document.getElementById('logo-filter-options');
        const s = (search || '').toLowerCase();
        const columnKey = window.__logoFilterModalState.columnKey;
        
        let opts = window.__logoFilterModalState.options || [];
        
        // Si hay búsqueda y no es una columna completa, buscar en el backend
        if (s && !window.__logoFilterModalState.allOptionsLoaded) {
            try {
                container.innerHTML = '<div style="padding: 1rem; text-align: center; color: #64748b;"><i class="fas fa-spinner fa-spin"></i> Buscando...</div>';
                const response = await fetch(`{{ route('visualizador-logo.pedidos-logo.buscar-valores-columna') }}?columna=${columnKey}&busqueda=${encodeURIComponent(s)}&filtro=${window.__filtroRecibosLogo || 'bordado'}`);
                const data = await response.json();
                if (data.success && data.valores) {
                    opts = data.valores;
                }
            } catch (error) {
                console.error('Error buscando valores:', error);
            }
        } else {
            // Filtrar localmente si es una columna completa o no hay búsqueda
            opts = opts.filter(o => String(o).toLowerCase().includes(s));
        }

        container.innerHTML = opts.map((opt) => {
            const checked = window.__logoFilterModalState.selected.has(opt) ? 'checked' : '';
            return `
                <label class="logo-filter-option">
                    <input type="checkbox" data-filter-option="1" value="${String(opt).replace(/"/g, '&quot;')}" ${checked} />
                    <span style="font-weight: 700; color: #0f172a;">${opt}</span>
                </label>
            `;
        }).join('');

        if (opts.length === 0) {
            container.innerHTML = '<div style="padding: 1rem; text-align: center; color: #64748b;">No se encontraron resultados</div>';
        }

        container.querySelectorAll('input[data-filter-option="1"]').forEach(cb => {
            cb.addEventListener('change', function() {
                const v = this.value;
                if (this.checked) window.__logoFilterModalState.selected.add(v);
                else window.__logoFilterModalState.selected.delete(v);
            });
        });
    }

    const filterSearchEl = document.getElementById('logo-filter-search');
    if (filterSearchEl) {
        filterSearchEl.addEventListener('input', function() {
            renderFilterOptions(this.value);
        });
        filterSearchEl.addEventListener('keyup', function() {
            renderFilterOptions(this.value);
        });
    }
    
    // Hook global para recargar desde botones
    window.__reloadRecibosLogo = function() {
        paginaActual = 1;
        pedidosOriginales = [];
        cargarRecibos('');
    };

    // Cargar conteos de pendientes
    window.__cargarConteosPendientes();

    // Cargar por defecto: bordado (si es diseñador/bordador no mostramos botones)
    if (window.__isDisenadorLogos) {
        window.__filtroRecibosLogo = 'bordado';
        cargarRecibos('');
    } else {
        setFiltroRecibosLogo('bordado');
    }
    
    // Event listeners para la barra de búsqueda
    const searchInput = document.getElementById('search-input');
    const clearSearchBtn = document.getElementById('clear-search');

    if (searchInput) {
        searchInput.placeholder = 'Buscar por cliente, número de recibo o anexo...';
        searchInput.setAttribute('autocomplete', 'off');
        searchInput.setAttribute('inputmode', 'search');
    }

    function ejecutarBusquedaLogo() {
        if (!searchInput) return;

        const searchTerm = searchInput.value.trim();

        if (clearSearchBtn) {
            clearSearchBtn.style.display = searchTerm ? 'block' : 'none';
        }

        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            paginaActual = 1;
            cargarRecibos(searchTerm);
        }, 80);
    }

    if (searchInput) {
        searchInput.addEventListener('input', ejecutarBusquedaLogo);
        searchInput.addEventListener('keyup', ejecutarBusquedaLogo);
        searchInput.addEventListener('search', ejecutarBusquedaLogo);
        searchInput.addEventListener('keydown', function(event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                ejecutarBusquedaLogo();
            }
        });
        searchInput.addEventListener('paste', function() {
            setTimeout(ejecutarBusquedaLogo, 0);
        });
    }
    
    if (clearSearchBtn) {
        clearSearchBtn.addEventListener('click', function() {
            searchInput.value = '';
            this.style.display = 'none';
            paginaActual = 1;
            cargarRecibos('');
            searchInput.focus();
        });
    }
    
    function cargarRecibos(searchTerm = '') {
        const params = new URLSearchParams({
            page: paginaActual
        });
        
        if (searchTerm) {
            params.append('search', searchTerm);
        }

        params.append('filtro', window.__filtroRecibosLogo || 'bordado');
        params.append('incluir_entregados', window.__incluirEntregadosLogo ? '1' : '0');
        
        // Enviar filtros de columnas al backend
        if (window.__logoColumnFilters && Object.keys(window.__logoColumnFilters).length > 0) {
            params.append('filters', JSON.stringify(window.__logoColumnFilters));
        }
        
        fetch(`{{ route("visualizador-logo.pedidos-logo.data") }}?${params}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    recibosUltimaCarga = data.recibos.data || [];
                    updateFilterBadges();
                    // Ya no aplicamos filtros localmente, el backend ya lo hace
                    renderizarRecibos(data.recibos, searchTerm);
                }
            })
            .catch(error => {
                mostrarError();
            });
    }

    function applyActiveColumnFilters(recibos) {
        const filters = window.__logoColumnFilters || {};
        const keys = Object.keys(filters);
        if (!keys.length) return recibos;

        return (recibos || []).filter(r => {
            return keys.every((k) => {
                const selected = filters[k] || [];
                if (!selected.length) return true;

                if (k === 'total_dias') {
                    const v = String(r.total_dias ?? 0);
                    return selected.includes(v);
                }

                const v = (r && r[k] !== undefined && r[k] !== null) ? String(r[k]).trim() : '';
                return selected.includes(v);
            });
        });
    }

    function renderizarRecibosFiltrados(recibosPagina, recibosPaginados = null, searchTerm = '') {
        // Esta función ya no se usa para filtrado local, pero se mantiene por compatibilidad
        // El filtrado ahora se hace en el backend
        renderizarRecibos(recibosPaginados || { data: recibosPagina, last_page: 1, current_page: 1 }, searchTerm);
    }
    
    function renderizarRecibos(recibos, searchTerm = '') {
        const tbody = document.getElementById('pedidos-body');
        const searchRegex = searchTerm ? new RegExp(escapeRegExp(searchTerm), 'gi') : null;
        
        if (recibos.data.length === 0) {
            tbody.innerHTML = `
                <div style="padding: 3rem 2rem; text-align: center; color: #64748b; background: #f8fafc;">
                    <i class="fas fa-inbox" style="font-size: 3rem; color: #cbd5e1; margin-bottom: 1rem; display: block;"></i>
                    <p style="margin: 0; font-size: 1rem; font-weight: 500;">
                        ${searchTerm ? 'No se encontraron recibos para tu búsqueda' : 'No se encontraron recibos'}
                    </p>
                </div>
            `;
            return;
        }
        
        tbody.innerHTML = recibos.data.map((recibo) => {
            let nombreCliente = recibo.cliente || '-';
            const numeroReciboValor = (recibo.numero_recibo !== null && recibo.numero_recibo !== undefined)
                ? String(recibo.numero_recibo)
                : '';
            let numeroRecibo = numeroReciboValor || '-';
            // Para parciales: si existe fecha_activacion, usarla; sino usar created_at
            let fechaCreacion = (recibo.pedido_parcial_id && recibo.fecha_activacion) 
                ? recibo.fecha_activacion 
                : (recibo.created_at || null);
            let fechaEntrega = recibo.fecha_entrega || null;
            const area = recibo.area || 'PENDIENTE';
            const asesora = recibo.asesora || '';
            const novedades = recibo.novedades || '';
            const totalDias = (recibo.total_dias ?? 0);
            const fechaCreacionRecibo = recibo.created_at || null;

            const { rowBg, rowHoverBg, rowBorderLeft } = window.__getColoresFilaReciboLogo(area, totalDias);

            if (searchRegex) {
                numeroRecibo = String(numeroRecibo).replace(searchRegex, '<mark style="background: #fef3c7; color: #92400e; padding: 2px 4px; border-radius: 3px;">$&</mark>');
                nombreCliente = String(nombreCliente).replace(searchRegex, '<mark style="background: #fef3c7; color: #92400e; padding: 2px 4px; border-radius: 3px;">$&</mark>');
            }

            const pedidoId = recibo.pedido_id;
            const prendaId = recibo.prenda_id;
            const tipoProceso = recibo.tipo_proceso;
            const esParcial = !!recibo.es_parcial;
            const pedidoParcialId = recibo.pedido_parcial_id || null;
            const consecutivoReciboId = recibo.consecutivo_recibo_id || null;
            const nombreProceso = recibo.nombre_proceso || recibo.tipo_proceso || '';
            const procesoPrendaDetalleId = recibo.id;
            const completado = Boolean(recibo.completado || window.__estaCompletadoLocalmenteLogo(procesoPrendaDetalleId));

            if (window.__isBordadorRole) {
                const rowBgColor = completado ? '#a7cdff' : 'white';
                const rowBorderColor = completado ? '#0284c7' : 'transparent';
                const btnBg = completado 
                    ? 'linear-gradient(135deg, #22c55e 0%, #16a34a 100%)' 
                    : 'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)';
                const btnShadow = completado 
                    ? '0 2px 8px rgba(34, 197, 94, 0.2)' 
                    : '0 2px 8px rgba(245, 158, 11, 0.2)';
                const btnTitle = completado ? 'Click para deshacer completado' : 'Marcar como completado';
                
                return `
                    <div data-recibo-row="1" data-proceso-id="${procesoPrendaDetalleId}" data-completado="${completado ? '1' : '0'}" data-pedido-parcial-id="${pedidoParcialId || ''}" data-consecutivo-recibo-id="${consecutivoReciboId || ''}" data-prenda-pedido-id="${prendaId || ''}" class="${completado ? 'recibo-completado-logo' : ''}" style="
                        min-width: 900px;
                        display: grid;
                        grid-template-columns: 100px 200px 120px 340px 180px 140px;
                        gap: 1rem;
                        padding: 1rem 1.5rem;
                        align-items: center;
                        transition: all 0.3s ease;
                        background: ${rowBgColor};
                        border-left: 4px solid ${rowBorderColor};
                        border-bottom: 1px solid #e2e8f0;
                    ">
                        <div style="display: flex; justify-content: center; gap: 0.5rem;">
                            <button 
                               onclick="verRecibo(${pedidoId}, ${prendaId}, '${String(tipoProceso || '').replace(/'/g, "\\'")}', ${esParcial ? 'true' : 'false'}, ${pedidoParcialId ? Number(pedidoParcialId) : 'null'}, '${String(nombreProceso || '').replace(/'/g, "\\'")}', '${escapeJsAttr(numeroReciboValor)}')"
                               title="Ver detalles"
                               style="
                                   background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
                                   color: white;
                                   border: none;
                                   padding: 0.6rem;
                                   border-radius: 8px;
                                   cursor: pointer;
                                   font-size: 1rem;
                                   transition: all 0.3s ease;
                                   display: flex;
                                   align-items: center;
                                   justify-content: center;
                                   width: 40px;
                                   height: 40px;
                                   box-shadow: 0 2px 8px rgba(14, 165, 233, 0.2);
                               " onmouseover="this.style.transform='translateY(-3px) scale(1.1)'; this.style.boxShadow='0 6px 16px rgba(14, 165, 233, 0.35)'" onmouseout="this.style.transform='translateY(0) scale(1)'; this.style.boxShadow='0 2px 8px rgba(14, 165, 233, 0.2)'">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div style="font-weight: 700; color: #0ea5e9; font-size: 0.95rem;">${numeroRecibo}</div>
                        <div style="font-weight: 700; color: #0f172a; font-size: 0.95rem; text-align: center;">${recibo.cantidad_total || '-'}</div>
                        <div style="color: #334155; font-size: 0.95rem; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${nombreCliente}</div>
                        <div style="color: #64748b; font-size: 0.95rem;">${formatearFecha(fechaCreacion)}</div>
                        <div style="display: flex; justify-content: center;">
                            <button 
                               onclick="marcarReciboCompletado(${procesoPrendaDetalleId}, '${escapeJsAttr(numeroReciboValor)}', ${consecutivoReciboId ? Number(consecutivoReciboId) : 'null'}, this)"
                               title="${btnTitle}"
                               style="
                                   background: ${btnBg};
                                   color: white;
                                   border: none;
                                   padding: 0.6rem;
                                   border-radius: 8px;
                                   cursor: pointer;
                                   font-size: 1rem;
                                   transition: all 0.3s ease;
                                   display: flex;
                                   align-items: center;
                                   justify-content: center;
                                   width: 40px;
                                   height: 40px;
                                   box-shadow: ${btnShadow};
                               " onmouseover="this.style.transform='translateY(-3px) scale(1.1)'" onmouseout="this.style.transform='translateY(0) scale(1)'">
                                <i class="fas fa-check"></i>
                            </button>
                        </div>
                    </div>
                `;
            }

            if (window.__isDisenadorLogos) {
                return `
                    <div data-recibo-row="1" data-proceso-id="${procesoPrendaDetalleId}" data-pedido-parcial-id="${pedidoParcialId || ''}" data-consecutivo-recibo-id="${consecutivoReciboId || ''}" data-prenda-pedido-id="${prendaId || ''}" class="${completado ? 'recibo-completado-logo' : ''}" style="
                        min-width: 1080px;
                        display: grid;
                        grid-template-columns: 100px 200px 340px 120px 180px 140px;
                        gap: 1rem;
                        padding: 1rem 1.5rem;
                        align-items: center;
                        transition: all 0.3s ease;
                        background: white;
                        border-left: 4px solid transparent;
                        border-bottom: 1px solid #e2e8f0;
                    ">
                        <div style="display: flex; justify-content: center; gap: 0.5rem;">
                            <button 
                               onclick="verRecibo(${pedidoId}, ${prendaId}, '${String(tipoProceso || '').replace(/'/g, "\\'")}', ${esParcial ? 'true' : 'false'}, ${pedidoParcialId ? Number(pedidoParcialId) : 'null'}, '${String(nombreProceso || '').replace(/'/g, "\\'")}', '${escapeJsAttr(numeroReciboValor)}')"
                               title="Ver detalles"
                               style="
                                   background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
                                   color: white;
                                   border: none;
                                   padding: 0.6rem;
                                   border-radius: 8px;
                                   cursor: pointer;
                                   font-size: 1rem;
                                   transition: all 0.3s ease;
                                   display: flex;
                                   align-items: center;
                                   justify-content: center;
                                   width: 40px;
                                   height: 40px;
                                   box-shadow: 0 2px 8px rgba(14, 165, 233, 0.2);
                               " onmouseover="this.style.transform='translateY(-3px) scale(1.1)'; this.style.boxShadow='0 6px 16px rgba(14, 165, 233, 0.35)'" onmouseout="this.style.transform='translateY(0) scale(1)'; this.style.boxShadow='0 2px 8px rgba(14, 165, 233, 0.2)'">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div style="font-weight: 700; color: #0ea5e9; font-size: 0.95rem;">${numeroRecibo}</div>
                        <div style="color: #334155; font-size: 0.95rem; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${nombreCliente}</div>
                        <div style="font-weight: 700; color: #0f172a; font-size: 0.95rem; text-align: center;">${recibo.cantidad_total || '-'}</div>
                        <div style="color: #64748b; font-size: 0.95rem;">${formatearFecha(fechaCreacion)}</div>
                        <div style="font-size: 0.95rem; font-weight: 700;">
                            ${(() => {
                                const estiloArea = window.__getEstiloAreaDropdownLogo(area);
                                return `<span style="background: ${estiloArea.bg}; color: ${estiloArea.color}; border: 1px solid ${estiloArea.border}; padding: 4px 8px; border-radius: 4px; font-size: 0.85rem; font-weight: 600; display: inline-block;">${area}</span>`;
                            })()}
                        </div>
                    </div>
                `;
            }

            const filtroActual = window.__filtroRecibosLogo || 'bordado';
            const areasBase = Array.isArray(window.__areasPermitidasLogo) ? window.__areasPermitidasLogo : [];
            const areasPermitidas = areasBase.filter((a) => {
                if (filtroActual === 'estampado' || filtroActual === 'dtf' || filtroActual === 'sublimado') {
                    // En estampado/dtf/sublimado: permitir ESTAMPANDO
                    // y ocultar áreas específicas de bordado.
                    if (a === 'CORTE_Y_APLIQUE') return false;
                    if (a === 'BORD_POR_FUERA') return false;
                    if (a === 'BORDANDO') return false;
                    return true;
                }
                // En bordado: ocultar ESTAMPANDO
                if (a === 'ESTAMPANDO') return false;
                return true;
            });

            const optionsArea = areasPermitidas.map((a) => {
                const selected = a === area ? 'selected' : '';
                const label = a === 'BORD_POR_FUERA' ? 'Bord. Por Fuera' : a.replace(/_/g, ' ');
                return `<option value="${a}" ${selected}>${label}</option>`;
            }).join('');

            return `
                <div data-recibo-row="1" data-proceso-id="${procesoPrendaDetalleId}" data-area="${area}" data-total-dias="${totalDias}" data-fecha-creacion-recibo="${fechaCreacionRecibo || ''}" data-fecha-entrega="${fechaEntrega || ''}" data-pedido-parcial-id="${pedidoParcialId || ''}" data-consecutivo-recibo-id="${consecutivoReciboId || ''}" data-prenda-pedido-id="${prendaId || ''}" class="${completado ? 'recibo-completado-logo' : ''}" style="
                    min-width: 2150px;
                    display: grid;
                    grid-template-columns: 100px 120px 200px 300px 120px 170px 230px 190px 260px 160px;
                    gap: 1rem;
                    padding: 1rem 1.5rem;
                    align-items: center;
                    transition: all 0.3s ease;
                    background: ${rowBg};
                    border-left: 4px solid ${rowBorderLeft};
                    border-bottom: 1px solid #e2e8f0;
                " onmouseover="this.style.background='${rowHoverBg}'; this.style.boxShadow='inset 0 0 0 1px #e2e8f0'" onmouseout="this.style.background='${rowBg}'; this.style.boxShadow='none'">
                    
                    <div style="display: flex; justify-content: center; gap: 0.5rem;">
                        <button 
                           onclick="verRecibo(${pedidoId}, ${prendaId}, '${String(tipoProceso || '').replace(/'/g, "\\'")}', ${esParcial ? 'true' : 'false'}, ${pedidoParcialId ? Number(pedidoParcialId) : 'null'}, '${String(nombreProceso || '').replace(/'/g, "\\'")}', '${escapeJsAttr(numeroReciboValor)}')"
                           title="Ver detalles"
                           style="
                               background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
                               color: white;
                               border: none;
                               padding: 0.6rem;
                               border-radius: 8px;
                               cursor: pointer;
                               font-size: 1rem;
                               transition: all 0.3s ease;
                               display: flex;
                               align-items: center;
                               justify-content: center;
                               width: 40px;
                               height: 40px;
                               box-shadow: 0 2px 8px rgba(14, 165, 233, 0.2);
                           " onmouseover="this.style.transform='translateY(-3px) scale(1.1)'; this.style.boxShadow='0 6px 16px rgba(14, 165, 233, 0.35)'" onmouseout="this.style.transform='translateY(0) scale(1)'; this.style.boxShadow='0 2px 8px rgba(14, 165, 233, 0.2)'">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div style="font-weight: 700; color: #0ea5e9; font-size: 0.95rem;">${numeroRecibo}</div>
                    <div data-field="total_dias" style="color: #0f172a; font-weight: 800; font-size: 0.95rem; text-align: center;">${totalDias}</div>
                    <div style="color: #334155; font-size: 0.95rem; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${nombreCliente}</div>
                    <div style="font-weight: 700; color: #0f172a; font-size: 0.95rem; text-align: center;">${recibo.cantidad_total || '-'}</div>
                    <div style="color: #64748b; font-size: 0.95rem;">${formatearFecha(fechaCreacion)}</div>
                    <div style="display: flex; align-items: center; justify-content: flex-start;">
                        <select data-proceso-id="${procesoPrendaDetalleId}" data-field="area" style="width: fit-content; max-width: 100%; display: inline-block; padding: 6px 12px; border-radius: 20px; border: 1px solid #e5e7eb; font-weight: 800; font-size: 12px; cursor: pointer; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); transition: all 0.2s ease;">
                            ${optionsArea}
                        </select>
                    </div>
                    <div style="color: #0f172a; font-weight: 700; font-size: 0.9rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${asesora}</div>
                    <div>
                        <input data-proceso-id="${procesoPrendaDetalleId}" data-field="novedades" type="text" value="${String(novedades).replace(/\"/g, '&quot;')}" placeholder="Escribe una novedad" style="width: 100%; padding: 0.5rem 0.6rem; border-radius: 8px; border: 1px solid #e2e8f0; background: white; color: #0f172a; font-weight: 500; font-size: 0.85rem;" />
                    </div>
                    <div data-field="fecha_entrega" style="color: #64748b; font-size: 0.95rem;">${formatearFecha(fechaEntrega)}</div>
                </div>
            `;
        }).join('');

        renderizarPaginacion(recibos);

        // Bind de guardado inline
        tbody.querySelectorAll('select[data-field="area"]').forEach((el) => {
            window.__aplicarEstiloAreaDropdownLogo(el);
            el.addEventListener('change', async function() {
                const procesoId = parseInt(this.dataset.procesoId);
                const areaValue = this.value;
                const row = this.closest('[data-recibo-row="1"]');
                const pedidoParcialId = row ? row.dataset.pedidoParcialId : null;
                const consecutivoReciboId = row ? row.dataset.consecutivoReciboId : null;
                const novedadesInput = row
                    ? row.querySelector(`input[data-proceso-id="${procesoId}"][data-field="novedades"]`)
                    : tbody.querySelector(`input[data-proceso-id="${procesoId}"][data-field="novedades"]`);
                const novedadesValue = novedadesInput ? novedadesInput.value : '';

                // Aplicar color inmediatamente (sin esperar recarga)
                window.__aplicarEstiloAreaDropdownLogo(this);
                window.__aplicarColoresFilaReciboLogo(procesoId);

                this.disabled = true;
                try {
                    const resp = await window.__guardarAreaNovedadLogo(procesoId, areaValue, novedadesValue, pedidoParcialId, consecutivoReciboId);
                    if (row && resp && resp.fecha_entrega) {
                        row.dataset.fechaEntrega = resp.fecha_entrega;
                        const entregaCell = row.querySelector('[data-field="fecha_entrega"]');
                        if (entregaCell) entregaCell.textContent = formatearFecha(resp.fecha_entrega);
                    }

                    window.__actualizarDiasYColoresLogo();
                    window.__aplicarEstiloAreaDropdownLogo(this);
                    window.__aplicarColoresFilaReciboLogo(procesoId);
                } catch (e) {
                    alert('Error guardando Área/Novedad. Intenta de nuevo.');
                } finally {
                    this.disabled = false;
                }
            });
        });

        tbody.querySelectorAll('input[data-field="novedades"]').forEach((el) => {
            el.addEventListener('blur', async function() {
                const procesoId = parseInt(this.dataset.procesoId);
                const novedadesValue = this.value;
                const row = this.closest('[data-recibo-row="1"]');
                const pedidoParcialId = row ? row.dataset.pedidoParcialId : null;
                const consecutivoReciboId = row ? row.dataset.consecutivoReciboId : null;
                const areaSelect = row
                    ? row.querySelector(`select[data-proceso-id="${procesoId}"][data-field="area"]`)
                    : tbody.querySelector(`select[data-proceso-id="${procesoId}"][data-field="area"]`);
                const areaValue = areaSelect ? areaSelect.value : 'PENDIENTE';

                this.disabled = true;
                try {
                    await window.__guardarAreaNovedadLogo(procesoId, areaValue, novedadesValue, pedidoParcialId, consecutivoReciboId);
                    window.__aplicarColoresFilaReciboLogo(procesoId);
                } catch (e) {
                    alert('Error guardando Área/Novedad. Intenta de nuevo.');
                } finally {
                    this.disabled = false;
                }
            });
        });

        window.__actualizarDiasYColoresLogo();
        window.__programarActualizacionDiariaLogo();
    }
    
    // Función para marcar/desmarcar recibo como completado (bordador)
    window.marcarReciboCompletado = async function(idRecibo, numeroRecibo, consecutivoReciboId, buttonElement) {
        console.log('%c[marcarReciboCompletado] ¡INICIANDO!', 'background: #22c55e; color: white; padding: 8px; font-weight: bold; font-size: 14px;', { idRecibo, numeroRecibo, consecutivoReciboId });
        
        const row = buttonElement.closest('[data-recibo-row="1"]');
        const estaCompletado = row && row.dataset.completado === '1';
        
        console.log('[marcarReciboCompletado] Row encontrada:', !!row, 'Está completado:', estaCompletado);
        
        buttonElement.disabled = true;
        buttonElement.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        
        try {
            const urlMarcarCompletado = '{{ route("visualizador-logo.pedidos-logo.marcar-completado") }}';
            console.log('[marcarReciboCompletado] URL del endpoint:', urlMarcarCompletado);
            
            const bodyData = {
                id_recibo: parseInt(idRecibo),
                numero_recibo: parseInt(numeroRecibo),
                consecutivo_recibo_id: consecutivoReciboId ? parseInt(consecutivoReciboId) : null,
                pedido_parcial_id: row && row.dataset.pedidoParcialId ? parseInt(row.dataset.pedidoParcialId) : null,
            };
            
            console.log('[marcarReciboCompletado] Enviando request con body:', bodyData);
            
            const response = await fetch(urlMarcarCompletado, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
                body: JSON.stringify(bodyData),
            });
            
            console.log('[marcarReciboCompletado] Response status:', response.status);
            
            const data = await response.json();
            
            console.log('[marcarReciboCompletado] Response data:', data);
            
            if (data.success) {
                const nuevoCompletado = data.completado === true;
                
                console.log('[marcarReciboCompletado] Éxito. Nuevo completado:', nuevoCompletado);
                
                // Actualizar dataset de la fila
                if (row) {
                    row.dataset.completado = nuevoCompletado ? '1' : '0';
                    
                    // Cambiar color de la fila
                    if (nuevoCompletado) {
                        row.classList.add('recibo-completado-logo');
                        row.style.background = '#a7cdff';
                        row.style.borderLeftColor = '#0284c7';
                    } else {
                        row.classList.remove('recibo-completado-logo');
                        row.style.background = 'white';
                        row.style.borderLeftColor = 'transparent';
                    }
                }

                actualizarCompletadoLocal(idRecibo, nuevoCompletado);
                
                console.log('[marcarReciboCompletado] Recargando datos...');
                
                // Recargar datos para mostrar el área actualizada (BORDADO)
                cargarRecibos('');
                
                buttonElement.innerHTML = '<i class="fas fa-check"></i>';
                buttonElement.disabled = false;
            } else {
                console.error('[marcarReciboCompletado] Error en respuesta:', data.message);
                alert('Error: ' + (data.message || 'No se pudo procesar'));
                buttonElement.disabled = false;
                buttonElement.innerHTML = '<i class="fas fa-check"></i>';
            }
        } catch (error) {
            console.error('[marcarReciboCompletado] Error de excepción:', error);
            alert('Error de conexión. Intenta de nuevo.');
            buttonElement.disabled = false;
            buttonElement.innerHTML = '<i class="fas fa-check"></i>';
        }
    };
    
    function formatearFecha(fecha) {
        if (!fecha) return '-';
        const date = new Date(fecha);
        return date.toLocaleString('es-ES', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    }
    
    function renderizarPaginacion(pedidos) {
        const container = document.getElementById('paginacion-container');
        
        if (pedidos.last_page <= 1) {
            container.innerHTML = '';
            return;
        }
        
        let html = '<nav><ul class="pagination">';
        
        // Anterior
        html += `<li class="page-item ${pedidos.current_page === 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" data-page="${pedidos.current_page - 1}"><i class="fas fa-chevron-left" style="margin-right: 0.3rem;"></i>Anterior</a>
        </li>`;
        
        // Páginas
        for (let i = 1; i <= pedidos.last_page; i++) {
            if (i === 1 || i === pedidos.last_page || (i >= pedidos.current_page - 2 && i <= pedidos.current_page + 2)) {
                html += `<li class="page-item ${i === pedidos.current_page ? 'active' : ''}">
                    <a class="page-link" href="#" data-page="${i}">${i}</a>
                </li>`;
            } else if (i === pedidos.current_page - 3 || i === pedidos.current_page + 3) {
                html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
        }
        
        // Siguiente
        html += `<li class="page-item ${pedidos.current_page === pedidos.last_page ? 'disabled' : ''}">
            <a class="page-link" href="#" data-page="${pedidos.current_page + 1}">Siguiente <i class="fas fa-chevron-right" style="margin-left: 0.3rem;"></i></a>
        </li>`;
        
        html += '</ul></nav>';
        container.innerHTML = html;
        
        // Event listeners para paginación
        container.querySelectorAll('.page-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const page = parseInt(this.dataset.page);
                if (page && page !== paginaActual) {
                    paginaActual = page;
                    const searchTerm = document.getElementById('search-input').value.trim();
                    cargarRecibos(searchTerm);
                }
            });
        });
    }
    
    function mostrarError() {
        const tbody = document.getElementById('pedidos-body');
        tbody.innerHTML = `
            <div style="padding: 3rem 2rem; text-align: center; color: #64748b; background: #f8fafc;">
                <i class="fas fa-exclamation-triangle" style="font-size: 3rem; color: #ef4444; margin-bottom: 1rem; display: block;"></i>
                <p style="margin: 0; font-size: 1rem; font-weight: 500;">Error al cargar los pedidos</p>
            </div>
        `;
    }

    function escapeRegExp(value) {
        return String(value).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    function escapeJsAttr(value) {
        return String(value)
            .replace(/\\/g, '\\\\')
            .replace(/'/g, "\\'")
            .replace(/\r/g, ' ')
            .replace(/\n/g, ' ')
            .replace(/\u2028/g, ' ')
            .replace(/\u2029/g, ' ');
    }

    function actualizarCompletadoLocal(procesoId, completado) {
        try {
            const mapa = window.__obtenerMapaCompletadosLocalLogo();
            const key = String(procesoId);

            if (completado) {
                mapa[key] = true;
            } else {
                delete mapa[key];
            }

            localStorage.setItem('visualizador_logo_bordador_completados', JSON.stringify(mapa));
        } catch (error) {
            // Si localStorage no está disponible, no bloqueamos la vista.
        }
    }
});
</script>

<!-- MODALES DE RECIBOS DE PRODUCCIÓN -->
@include('components.modals.recibos-process-selector')
@include('components.modals.recibos-intermediate-modal')
@include('components.modals.recibo-dinamico-modal')

<!-- MODAL WRAPPER PARA RECIBOS -->
<div id="modal-overlay" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.5); backdrop-filter: blur(4px); z-index: 9997; display: none; pointer-events: auto;" onclick="closeModalOverlay()"></div>

<div id="order-detail-modal-wrapper" style="width: 90%; max-width: 672px; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 9998; pointer-events: auto; display: none;">
    <x-orders-components.order-detail-modal />
</div>

<!-- MÓDULO DE RECIBOS -->
<script type="module" src="{{ asset('js/modulos/pedidos-recibos/loader.js') }}?v={{ time() }}"></script>

@endsection
