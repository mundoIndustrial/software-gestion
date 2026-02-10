@extends('layouts.visualizador-logo')

@section('title', 'Pedidos Logo - Bordado/Estampado')

@section('page-title', 'Pedidos Logo')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/visualizador-logo/pedidos-logo.css') }}">
@endpush

@section('content')
@php($isDisenadorLogos = Auth::user() && Auth::user()->hasRole('diseñador-logos'))
@php($isBordador = Auth::user() && Auth::user()->hasRole('bordador'))
@php($isMinimalLogoRole = $isDisenadorLogos || $isBordador)
<div style="padding: 1rem 1.25rem 2rem 0.25rem; background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); min-height: calc(100vh - 60px); font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;">
    <!-- Tabla de Pedidos Logo -->
    <div style="display: flex; justify-content: flex-start;">
        <div style="width: 100%; max-width: 1500px;">
            @if(!$isMinimalLogoRole)
                <div style="display: flex; gap: 12px; align-items: center; justify-content: flex-start; margin-bottom: 14px;">
                    <button id="btn-filter-bordado" type="button" onclick="setFiltroRecibosLogo('bordado')" style="padding: 10px 14px; border-radius: 10px; border: 2px solid #0ea5e9; background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%); color: white; font-weight: 700; cursor: pointer; transition: all 0.2s; box-shadow: 0 2px 8px rgba(14, 165, 233, 0.18);">
                        PEDIDOS BORDADO
                    </button>
                    <button id="btn-filter-estampado" type="button" onclick="setFiltroRecibosLogo('estampado')" style="padding: 10px 14px; border-radius: 10px; border: 2px solid #e2e8f0; background: white; color: #334155; font-weight: 700; cursor: pointer; transition: all 0.2s; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);">
                        PEDIDOS ESTAMPADO
                    </button>
                </div>
            @endif
            <!-- Container -->
            <div style="background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07), 0 1px 3px rgba(0, 0, 0, 0.06); border: 1px solid #e2e8f0;">
                <div style="overflow-x: auto; -webkit-overflow-scrolling: touch;">
                    <div style="{{ $isMinimalLogoRole ? '' : 'zoom: 0.75;' }}">
                    <!-- Header -->
                    <div style="
                        min-width: {{ $isMinimalLogoRole ? '900px' : '1900px' }};
                        background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
                        color: white;
                        padding: 1rem 1.5rem;
                        display: grid;
                        grid-template-columns: {{ $isMinimalLogoRole ? '100px 200px 340px 180px 140px' : '100px 120px 200px 300px 170px 230px 190px 260px 160px' }};
                        gap: 1rem;
                        font-weight: 700;
                        font-size: 0.9rem;
                        text-transform: uppercase;
                        letter-spacing: 0.05em;
                    ">
                        <div style="color: #cbd5e1;">Acciones</div>
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
                                Número Recibo
                            @else
                                <div class="th-wrapper">
                                    <span>Número Recibo</span>
                                    <button class="btn-filter-column" type="button" data-column="numero_recibo" onclick="openLogoColumnFilter('numero_recibo', 'Número Recibo')"><i class="fas fa-filter"></i><span class="filter-badge" data-badge="numero_recibo">0</span></button>
                                </div>
                            @endif
                        </div>
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
                        <div style="color: #cbd5e1;">Fecha Creación</div>
                        @if($isMinimalLogoRole)
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
// Función global para ver recibo directamente (sin selector)
window.verRecibo = function(pedidoId, prendaId, tipoProceso) {
    if (typeof window.openOrderDetailModalWithProcess === 'function') {
        window.openOrderDetailModalWithProcess(pedidoId, prendaId, String(tipoProceso));
    } else {
        console.error('La función openOrderDetailModalWithProcess no está disponible');
        alert('Error: El módulo de recibos no está disponible. Por favor recargue la página.');
    }
};

window.__areasPermitidasLogo = [
    'CREACION_DE_ORDEN',
    'PENDIENTE_DISENO',
    'DISENO',
    'PENDIENTE_CONFIRMAR',
    'CORTE_Y_APLIQUE',
    'HACIENDO_MUESTRA',
    'BORDANDO',
    'ENTREGADO',
    'ANULADO',
    'PENDIENTE',
];

window.__isDisenadorLogos = {{ $isMinimalLogoRole ? 'true' : 'false' }};

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

window.__guardarAreaNovedadLogo = async function(procesoPrendaDetalleId, area, novedades) {
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
        'HACIENDO_MUESTRA': { bg: '#e9d5ff', color: '#6b21a8', border: '#a855f7' },
        'BORDANDO': { bg: '#f97316', color: '#ffffff', border: '#ea580c' },
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

window.__aplicarColoresFilaReciboLogo = function(procesoId) {
    const row = document.querySelector(`[data-recibo-row="1"][data-proceso-id="${procesoId}"]`);
    if (!row) return;

    const areaSelect = row.querySelector(`select[data-proceso-id="${procesoId}"][data-field="area"]`);
    const area = areaSelect ? areaSelect.value : (row.dataset.area || 'PENDIENTE');
    const totalDias = row.dataset.totalDias || 0;

    const { rowBg, rowHoverBg, rowBorderLeft } = window.__getColoresFilaReciboLogo(area, totalDias);

    row.style.background = rowBg;
    row.style.borderLeft = `4px solid ${rowBorderLeft}`;
    row.setAttribute('onmouseover', `this.style.background='${rowHoverBg}'; this.style.boxShadow='inset 0 0 0 1px #e2e8f0'`);
    row.setAttribute('onmouseout', `this.style.background='${rowBg}'; this.style.boxShadow='none'`);
};

// Estado global del filtro (por defecto: bordado)
window.__filtroRecibosLogo = 'bordado';

window.setFiltroRecibosLogo = function(nuevoFiltro) {
    window.__filtroRecibosLogo = nuevoFiltro === 'estampado' ? 'estampado' : 'bordado';
    
    const btnBordado = document.getElementById('btn-filter-bordado');
    const btnEstampado = document.getElementById('btn-filter-estampado');

    if (btnBordado && btnEstampado) {
        if (window.__filtroRecibosLogo === 'bordado') {
            btnBordado.style.background = 'linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%)';
            btnBordado.style.borderColor = '#0ea5e9';
            btnBordado.style.color = 'white';

            btnEstampado.style.background = 'white';
            btnEstampado.style.borderColor = '#e2e8f0';
            btnEstampado.style.color = '#334155';
        } else {
            btnEstampado.style.background = 'linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%)';
            btnEstampado.style.borderColor = '#0ea5e9';
            btnEstampado.style.color = 'white';

            btnBordado.style.background = 'white';
            btnBordado.style.borderColor = '#e2e8f0';
            btnBordado.style.color = '#334155';
        }
    }

    // Resetear paginación y búsqueda al cambiar filtro
    const searchInput = document.getElementById('search-input');
    const clearSearchBtn = document.getElementById('clear-search');
    if (searchInput) searchInput.value = '';
    if (clearSearchBtn) clearSearchBtn.style.display = 'none';

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
        renderizarRecibosFiltrados(recibosUltimaCarga);
    };

    window.openLogoColumnFilter = function(columnKey, title) {
        window.__logoFilterModalState.columnKey = columnKey;
        window.__logoFilterModalState.title = title;

        const activeValues = (window.__logoColumnFilters && window.__logoColumnFilters[columnKey]) ? window.__logoColumnFilters[columnKey] : [];
        window.__logoFilterModalState.selected = new Set(activeValues);

        const options = getUniqueOptionsForColumn(columnKey, recibosUltimaCarga);
        window.__logoFilterModalState.options = options;

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
        renderizarRecibosFiltrados(recibosUltimaCarga);
        window.closeLogoFilterModal();
    };

    window.applyLogoColumnFilter = function() {
        const key = window.__logoFilterModalState.columnKey;
        if (!key) return;
        const values = Array.from(window.__logoFilterModalState.selected);
        window.__logoColumnFilters[key] = values;
        persistFilters();
        updateFilterBadges();
        renderizarRecibosFiltrados(recibosUltimaCarga);
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

    function renderFilterOptions(search) {
        const container = document.getElementById('logo-filter-options');
        const s = (search || '').toLowerCase();
        const opts = (window.__logoFilterModalState.options || []).filter(o => String(o).toLowerCase().includes(s));

        container.innerHTML = opts.map((opt) => {
            const checked = window.__logoFilterModalState.selected.has(opt) ? 'checked' : '';
            return `
                <label class="logo-filter-option">
                    <input type="checkbox" data-filter-option="1" value="${String(opt).replace(/"/g, '&quot;')}" ${checked} />
                    <span style="font-weight: 700; color: #0f172a;">${opt}</span>
                </label>
            `;
        }).join('');

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
    }
    
    // Hook global para recargar desde botones
    window.__reloadRecibosLogo = function() {
        paginaActual = 1;
        pedidosOriginales = [];
        cargarRecibos('');
    };

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
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.trim();
            
            // Mostrar/ocultar botón de limpiar
            if (searchTerm) {
                clearSearchBtn.style.display = 'block';
            } else {
                clearSearchBtn.style.display = 'none';
            }
            
            // Búsqueda en tiempo real con debounce
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                paginaActual = 1;
                cargarRecibos(searchTerm);
            }, 300);
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
        
        fetch(`{{ route("visualizador-logo.pedidos-logo.data") }}?${params}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Guardar datos originales para filtrado local si es necesario
                    if (pedidosOriginales.length === 0 && !searchTerm) {
                        pedidosOriginales = data.recibos.data;
                    }
                    recibosUltimaCarga = data.recibos.data || [];
                    updateFilterBadges();
                    renderizarRecibosFiltrados(recibosUltimaCarga, data.recibos, searchTerm);
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
        const filtrados = applyActiveColumnFilters(recibosPagina);
        const paginados = recibosPaginados ? { ...recibosPaginados, data: filtrados } : { data: filtrados, last_page: 1, current_page: 1 };
        renderizarRecibos(paginados, searchTerm);
    }
    
    function renderizarRecibos(recibos, searchTerm = '') {
        const tbody = document.getElementById('pedidos-body');
        
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
            let numeroRecibo = recibo.numero_recibo || '-';
            let fechaCreacion = recibo.created_at || null;
            let fechaEntrega = recibo.fecha_entrega || null;
            const area = recibo.area || 'PENDIENTE';
            const asesora = recibo.asesora || '';
            const novedades = recibo.novedades || '';
            const totalDias = (recibo.total_dias ?? 0);
            const fechaCreacionRecibo = recibo.created_at || null;

            const { rowBg, rowHoverBg, rowBorderLeft } = window.__getColoresFilaReciboLogo(area, totalDias);

            if (searchTerm) {
                const regex = new RegExp(`(${searchTerm})`, 'gi');
                numeroRecibo = String(numeroRecibo).replace(regex, '<mark style="background: #fef3c7; color: #92400e; padding: 2px 4px; border-radius: 3px;">$1</mark>');
                nombreCliente = String(nombreCliente).replace(regex, '<mark style="background: #fef3c7; color: #92400e; padding: 2px 4px; border-radius: 3px;">$1</mark>');
            }

            const pedidoId = recibo.pedido_id;
            const prendaId = recibo.prenda_id;
            const tipoProceso = recibo.tipo_proceso;
            const procesoPrendaDetalleId = recibo.id;

            if (window.__isDisenadorLogos) {
                return `
                    <div data-recibo-row="1" data-proceso-id="${procesoPrendaDetalleId}" style="
                        min-width: 900px;
                        display: grid;
                        grid-template-columns: 100px 200px 340px 180px 140px;
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
                               onclick="verRecibo(${pedidoId}, ${prendaId}, '${String(tipoProceso || '').replace(/'/g, "\\'")}')"
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
                        <div style="color: #64748b; font-size: 0.95rem;">${formatearFecha(fechaCreacion)}</div>
                        <div style="color: #0f172a; font-size: 0.95rem; font-weight: 700;">${area}</div>
                    </div>
                `;
            }

            const optionsArea = (window.__areasPermitidasLogo || []).map((a) => {
                const selected = a === area ? 'selected' : '';
                const label = a.replace(/_/g, ' ');
                return `<option value="${a}" ${selected}>${label}</option>`;
            }).join('');

            return `
                <div data-recibo-row="1" data-proceso-id="${procesoPrendaDetalleId}" data-area="${area}" data-total-dias="${totalDias}" data-fecha-creacion-recibo="${fechaCreacionRecibo || ''}" data-fecha-entrega="${fechaEntrega || ''}" style="
                    min-width: 1860px;
                    display: grid;
                    grid-template-columns: 100px 120px 200px 300px 170px 230px 190px 260px 160px;
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
                           onclick="verRecibo(${pedidoId}, ${prendaId}, '${String(tipoProceso || '').replace(/'/g, "\\'")}')"
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
                    <div data-field="total_dias" style="color: #0f172a; font-weight: 800; font-size: 0.95rem; text-align: center;">${totalDias}</div>
                    <div style="font-weight: 700; color: #0ea5e9; font-size: 0.95rem;">${numeroRecibo}</div>
                    <div style="color: #334155; font-size: 0.95rem; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${nombreCliente}</div>
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
                const novedadesInput = tbody.querySelector(`input[data-proceso-id="${procesoId}"][data-field="novedades"]`);
                const novedadesValue = novedadesInput ? novedadesInput.value : '';

                // Aplicar color inmediatamente (sin esperar recarga)
                window.__aplicarEstiloAreaDropdownLogo(this);
                window.__aplicarColoresFilaReciboLogo(procesoId);

                this.disabled = true;
                try {
                    const resp = await window.__guardarAreaNovedadLogo(procesoId, areaValue, novedadesValue);

                    const row = document.querySelector(`[data-recibo-row="1"][data-proceso-id="${procesoId}"]`);
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
                const areaSelect = tbody.querySelector(`select[data-proceso-id="${procesoId}"][data-field="area"]`);
                const areaValue = areaSelect ? areaSelect.value : 'PENDIENTE';

                this.disabled = true;
                try {
                    await window.__guardarAreaNovedadLogo(procesoId, areaValue, novedadesValue);
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
    
    function formatearFecha(fecha) {
        if (!fecha) return '-';
        const date = new Date(fecha);
        return date.toLocaleDateString('es-ES', { 
            year: 'numeric', 
            month: 'short', 
            day: 'numeric' 
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
