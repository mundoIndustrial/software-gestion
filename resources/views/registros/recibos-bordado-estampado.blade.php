@extends('layouts.app')

@section('title', 'Recibos de Bordado/Estampado')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Table Component -->
            <x-recibos.recibos-bordado-estampado-table
                :recibos="$recibos"
                :totalCantidadGlobal="$totalCantidadGlobal ?? 0"
                :tipoFiltro="$tipoFiltro ?? 'BORDADO'"
                :conteoBordado="$conteoBordado ?? 0"
                :conteoEstampado="$conteoEstampado ?? 0"
                :conteoDtf="$conteoDtf ?? 0"
                :conteoSublimado="$conteoSublimado ?? 0" />
        </div>
    </div>
</div>



<!-- Contenedor para dropdowns (requerido por DropdownService.js) -->
<div id="dropdowns-container" style="position: fixed; top: 0; left: 0; z-index: 999999; pointer-events: none; width: 0; height: 0; overflow: visible;"></div>

<!-- Modal para ver detalles del recibo -->
<div id="modal-overlay" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.5); backdrop-filter: blur(4px); z-index: 9997; display: none; pointer-events: auto;" onclick="closeModalOverlay()"></div>

<div id="order-detail-modal-wrapper" style="width: 90%; max-width: 672px; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 9998; pointer-events: auto; display: none;">
    <x-orders-components.order-detail-modal />
</div>

<div id="recibo-distribution-modal" class="distribution-modal" aria-hidden="true">
    <div class="distribution-modal__backdrop" data-distribution-close="true"></div>
    <div class="distribution-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="distributionModalTitle">
        <div class="distribution-modal__header">
            <div class="distribution-modal__header-icon">
                <i class="fas fa-share-alt"></i>
            </div>
            <div class="distribution-modal__header-copy">
                <p class="distribution-modal__eyebrow">Distribución activa</p>
                <h2 id="distributionModalTitle">Distribución del recibo</h2>
            </div>
            <button type="button" class="distribution-modal__close" data-distribution-close="true" aria-label="Cerrar">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div id="distributionModalBody" class="distribution-modal__body"></div>
    </div>
</div>

<div id="partial-tracking-modal" class="partial-tracking-modal" aria-hidden="true">
    <div class="partial-tracking-modal__backdrop" data-partial-tracking-close="true"></div>
    <div class="partial-tracking-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="partialTrackingModalTitle">
        <div class="partial-tracking-modal__header">
            <div class="partial-tracking-modal__header-icon">
                <i class="fas fa-route"></i>
            </div>
            <div class="partial-tracking-modal__header-copy">
                <p class="partial-tracking-modal__eyebrow">Seguimiento del parcial</p>
                <h2 id="partialTrackingModalTitle">Recorrido del parcial</h2>
            </div>
            <button type="button" class="partial-tracking-modal__close" data-partial-tracking-close="true" aria-label="Cerrar">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div id="partialTrackingModalBody" class="partial-tracking-modal__body"></div>
    </div>
</div>

<!-- Modal de Seguimiento del Pedido -->
<x-orders-components.order-tracking-modal />

<!-- Modal de Novedades -->
<x-modals.novedades-edit-modal />

@endsection

<!-- Contenedor para Toast Notifications -->
<div class="toast-container" id="toastContainer"></div>

@push('styles')
<link rel="stylesheet" href="{{ asset('css/recibos-costura.css') }}?v={{ time() }}">
<link rel="stylesheet" href="{{ asset('css/tracking-modal.css') }}?v={{ time() }}">
<link rel="stylesheet" href="{{ asset('css/dropdowns-recibos.css') }}?v={{ time() }}">

<style>
</style>

<!-- Estilos adicionales para el modal de agregar proceso -->
<style>
.recibos-costura-scale-90 {
    zoom: 0.9;
}

@supports not (zoom: 1) {
    .recibos-costura-scale-90 {
        transform: scale(0.9);
        transform-origin: top left;
        width: 111.1111%;
    }
}

.add-proceso-modal.show {
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    opacity: 1 !important;
    visibility: visible !important;
    z-index: 10000000 !important;
}

.add-proceso-modal.show .add-proceso-content {
    display: flex !important;
    flex-direction: column !important;
    overflow: hidden !important;
    animation: slideUp 0.3s ease-out !important;
    margin: auto !important;
    position: relative !important;
    background: white !important;
    border-radius: 16px !important;
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3) !important;
    max-width: 500px !important;
    width: 90% !important;
    max-height: 90vh !important;
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.area-badge-clickable {
    position: relative;
    overflow: hidden;
}

.area-badge-clickable::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
    transition: left 0.5s ease;
}

.area-badge-clickable:hover::before {
    left: 100%;
}

/* Colores personalizados para badges de área */
.badge.bg-purple {
    background-color: #8b5cf6 !important;
    color: white !important;
}

.badge.bg-teal {
    background-color: #14b8a6 !important;
    color: white !important;
}

.badge.bg-orange {
    background-color: #f97316 !important;
    color: white !important;
}

.badge.bg-pink {
    background-color: #ec4899 !important;
    color: white !important;
}

/* Mejorar contraste para badges existentes */
.badge.bg-success {
    background-color: #22c55e !important;
    color: white !important;
}

.badge.bg-info {
    background-color: #06b6d4 !important;
    color: white !important;
}

.badge.bg-primary {
    background-color: #3b82f6 !important;
    color: white !important;
}

.badge.bg-warning {
    background-color: #f59e0b !important;
    color: white !important;
}

.badge.bg-secondary {
    background-color: #6b7280 !important;
    color: white !important;
}

/* Toast Notifications */
.toast-container {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 999999;
    pointer-events: none;
}

.toast {
    background: white;
    border-radius: 12px;
    padding: 16px 20px;
    margin-bottom: 12px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    border-left: 4px solid;
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 300px;
    max-width: 400px;
    pointer-events: auto;
    animation: slideInRight 0.3s ease-out;
    position: relative;
    overflow: hidden;
}

.toast::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    animation: shimmer 2s infinite;
}

.toast.success {
    border-left-color: #22c55e;
    background: linear-gradient(135deg, #f0fdf4 0%, #ffffff 100%);
}

.toast.error {
    border-left-color: #ef4444;
    background: linear-gradient(135deg, #fef2f2 0%, #ffffff 100%);
}

.toast.info {
    border-left-color: #3b82f6;
    background: linear-gradient(135deg, #eff6ff 0%, #ffffff 100%);
}

.toast-icon {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-weight: bold;
    color: white;
    font-size: 14px;
}

.toast.success .toast-icon {
    background: #22c55e;
}

.toast.error .toast-icon {
    background: #ef4444;
}

.toast.info .toast-icon {
    background: #3b82f6;
}

.toast-content {
    flex: 1;
}

.toast-title {
    font-weight: 600;
    font-size: 14px;
    margin-bottom: 2px;
    color: #1f2937;
}

.toast-message {
    font-size: 13px;
    color: #6b7280;
    line-height: 1.4;
}

.toast-close {
    position: absolute;
    top: 8px;
    right: 8px;
    width: 20px;
    height: 20px;
    border: none;
    background: rgba(0, 0, 0, 0.1);
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    color: #6b7280;
    transition: all 0.2s ease;
}

.toast-close:hover {
    background: rgba(0, 0, 0, 0.2);
    color: #1f2937;
}

.toast.removing {
    animation: slideOutRight 0.3s ease-out forwards;
}

@keyframes slideInRight {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

@keyframes slideOutRight {
    from {
        transform: translateX(0);
        opacity: 1;
    }
    to {
        transform: translateX(100%);
        opacity: 0;
    }
}

@keyframes shimmer {
    0% {
        left: -100%;
    }
    100% {
        left: 100%;
    }
}
</style>
@endpush

@push('scripts')
<!-- Limpiar datos residuales de costura antes de cargar módulos -->
<script>
    window.__VISTA_TIPO__ = 'bordado-estampado';
    window.__SKIP_RECIBOS_TABLE_INIT__ = true;
    if (typeof window.pedidosRecibosModule !== 'undefined') {
        delete window.pedidosRecibosModule;
    }
    localStorage.removeItem('recibos_costura_state');
    sessionStorage.removeItem('recibos_costura_state');
</script>

<!--
    ============================================
    PHASE 2: Módulo Modular DDD Recibos Costura
    ============================================

    Bundle compilado con:
    - Domain Layer: Value Objects (EstadoRecibo, AreaRecibo, etc)
    - Infrastructure: API Client + State Manager
    - Presentation: Table Controller + Dropdown + Modal Handlers
    - Initializer: Auto-bootstrap + listeners
-->
<script src="{{ asset('js/recibos-costura/bundle.js') }}"></script>

<!-- Legacy Scripts Component (MANTENER POR COMPATIBILIDAD) -->
<x-recibos.recibos-costura-scripts />

<!-- Toast Notification Service - Servicio centralizado de notificaciones -->
<script src="{{ asset('js/recibos-costura/services/ToastNotificationService.js') }}"></script>


<!-- Dropdown Service - Sistema de dropdowns -->
<script src="{{ asset('js/recibos-costura/services/DropdownService.js') }}"></script>

<!-- Search Module - Sistema de búsqueda AJAX -->
<script src="{{ asset('js/recibos-costura/search.js') }}?v={{ time() }}"></script>

<!-- Costura Notification Bell Service - Sistema de notificaciones de campana -->
<script src="{{ asset('js/recibos-costura/services/CosturaNotificationBellService.js') }}"></script>

<!-- Realtime Recibo Listener - Sistema de escucha en tiempo real de eventos -->
<script src="{{ asset('js/recibos-costura/services/RealtimeReciboListener.js') }}"></script>

<!-- Tracking Modal Controller - Controlador de modal de seguimiento -->
<script src="{{ asset('js/recibos-costura/controllers/TrackingModalController.js') }}"></script>

<!-- Add Process Modal Controller - Controlador de modal para agregar procesos -->
<script src="{{ asset('js/recibos-costura/controllers/AddProcessModalController.js') }}"></script>

<!-- Legacy Handlers - Funciones heredadas que delegan a módulos -->
<script src="{{ asset('js/recibos-costura/legacy-handlers.js') }}"></script>


<script>
// Script de inicialización para bordado/estampado - Prevenir datos de costura
(() => {
    // Limpiar datos de costura del DOM antes de que se inicialicen los módulos
    const limpiarDatosCostura = () => {
        document.querySelectorAll('[data-vista-tipo="costura"]').forEach(el => {
            el.style.display = 'none';
        });

        // Marcar tabla como bordado/estampado
        const tabla = document.querySelector('.modern-table');
        if (tabla) {
            tabla.setAttribute('data-vista-tipo', 'bordado-estampado');
        }
    };

    limpiarDatosCostura();
    document.addEventListener('DOMContentLoaded', limpiarDatosCostura);
    setTimeout(limpiarDatosCostura, 100);
    setTimeout(limpiarDatosCostura, 500);
})();
</script>

<script>
(() => {
    let pendingOpen = null;
    let modulePollStarted = false;

    const parseBool = (value) => {
        const normalized = String(value ?? '').trim().toLowerCase();
        return normalized === 'true' || normalized === '1';
    };

    const toNumberOrNull = (value) => {
        const trimmed = String(value ?? '').trim();
        if (!trimmed) return null;
        const num = Number(trimmed);
        return Number.isFinite(num) ? num : null;
    };

    const isRecibosModuleReady = () => !!(
        window.pedidosRecibosModule &&
        typeof window.pedidosRecibosModule.abrirRecibo === 'function'
    );

    const tryInstantiateModule = () => {
        if (!window.pedidosRecibosModule && typeof window.PedidosRecibosModule === 'function') {
            window.pedidosRecibosModule = new window.PedidosRecibosModule();
        }
    };

    const openDirect = (payload) => {
        if (!payload || !payload.pedidoId) return;

        if (window.DropdownService && typeof window.DropdownService.getInstance === 'function') {
            window.DropdownService.getInstance().closeAll();
        }

        if (payload.esParcial && payload.parcialId && typeof window.openOrderDetailModalWithParcial === 'function') {
            window.openOrderDetailModalWithParcial(payload.parcialId, payload.prendaId, payload.tipoRecibo, payload.pedidoId);
            return;
        }

        if (typeof window.openOrderDetailModalWithProcess === 'function') {
            window.openOrderDetailModalWithProcess(
                payload.pedidoId,
                payload.prendaId,
                payload.tipoRecibo,
                null,
                payload.numeroRecibo,
                payload.reciboId
            );
        }
    };

    const startModulePoll = () => {
        if (modulePollStarted) return;
        modulePollStarted = true;

        let attempts = 0;
        const maxAttempts = 80;

        const poll = () => {
            attempts += 1;
            tryInstantiateModule();

            if (isRecibosModuleReady()) {
                if (pendingOpen) {
                    const payload = pendingOpen;
                    pendingOpen = null;
                    openDirect(payload);
                }
                modulePollStarted = false;
                return;
            }

            if (attempts < maxAttempts) {
                setTimeout(poll, 50);
            } else {
                console.warn('[recibos-bordado-estampado] Modulo de recibos aun no disponible');
                modulePollStarted = false;
            }
        };

        poll();
    };

    document.addEventListener('click', function (event) {
        const btnVer = event.target.closest('.btn-ver-dropdown');
        if (!btnVer) return;

        event.preventDefault();
        event.stopPropagation();
        event.stopImmediatePropagation();

        const payload = {
            pedidoId: toNumberOrNull(btnVer.getAttribute('data-pedido-id')),
            prendaId: toNumberOrNull(btnVer.getAttribute('data-prenda-id')),
            numeroRecibo: toNumberOrNull(btnVer.getAttribute('data-numero-recibo')),
            reciboId: toNumberOrNull(btnVer.getAttribute('data-recibo-id')),
            tipoRecibo: btnVer.getAttribute('data-tipo-recibo') || 'COSTURA',
            esParcial: parseBool(btnVer.getAttribute('data-es-parcial')),
            parcialId: toNumberOrNull(btnVer.getAttribute('data-pedido-parcial-id'))
        };

        if (!payload.pedidoId) {
            console.error('[recibos-bordado-estampado] pedidoId invalido para abrir recibo');
            return;
        }

        if (isRecibosModuleReady()) {
            openDirect(payload);
            return;
        }

        pendingOpen = payload;
        startModulePoll();
    }, true);
})();
</script>

<script>
(() => {

    const normalizarTipo = (rawTipo) => {
        const tipo = String(rawTipo || '').trim().toUpperCase();
        if (tipo.includes('ESTAMP')) return 'ESTAMPADO';
        if (tipo.includes('BORD')) return 'BORDADO';
        if (tipo.includes('DTF')) return 'DTF';
        if (tipo.includes('SUBLI')) return 'SUBLIMADO';
        return tipo || '-';
    };

    const crearBadgeTipo = (tipo) => {
        const colorByTipo = {
            BORDADO: '#2563eb',
            ESTAMPADO: '#0f766e',
            DTF: '#7c3aed',
            SUBLIMADO: '#ea580c',
        };
        const color = colorByTipo[tipo] || '#475569';
        return `<span style="display:inline-block;padding:3px 8px;border-radius:999px;background:${color};color:#fff;font-size:11px;font-weight:700;letter-spacing:.3px;">${tipo}</span>`;
    };

    const obtenerIndiceColumnaNumeroRecibo = (table) => {
        const headers = Array.from(table?.querySelectorAll('thead th') || []);
        if (!headers.length) return -1;

        const thPorDataColumn = table.querySelector(
            'thead th .btn-filter-column[data-column="numero_recibo"]'
        )?.closest('th');
        if (thPorDataColumn) {
            const idx = headers.indexOf(thPorDataColumn);
            if (idx >= 0) return idx;
        }

        const normalizarTexto = (texto) =>
            String(texto || '')
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .replace(/°/g, 'o')
                .replace(/[^\w\s]/g, ' ')
                .replace(/\s+/g, ' ')
                .trim()
                .toLowerCase();

        return headers.findIndex((th) => {
            const txt = normalizarTexto(th.textContent);
            return (
                txt.includes('numero de recibo') ||
                txt.includes('numero recibo') ||
                txt.includes('no recibo') ||
                txt.includes('nro recibo')
            );
        });
    };

    const asegurarHeaderTipo = (table) => {
        const headerRow = table?.querySelector('thead tr');
        if (!headerRow || headerRow.querySelector(`.${TIPO_HEADER_CLASS}`)) return;

        const th = document.createElement('th');
        th.className = TIPO_HEADER_CLASS;
        th.style.width = '120px';
        th.style.textAlign = 'center';
        th.textContent = 'Tipo';

        const indexNumeroRecibo = obtenerIndiceColumnaNumeroRecibo(table);
        const referencia = indexNumeroRecibo >= 0 ? headerRow.children[indexNumeroRecibo + 1] : null;
        if (referencia) {
            headerRow.insertBefore(th, referencia);
            return;
        }

        headerRow.appendChild(th);
    };

    const asegurarCeldasTipo = (table) => {
        const rows = table?.querySelectorAll('tbody tr[data-orden-id]');
        if (!rows?.length) return;

        rows.forEach((row) => {
            if (row.querySelector(`.${TIPO_CELL_CLASS}`)) return;
            const tipoRaw = row.querySelector('.btn-ver-dropdown')?.getAttribute('data-tipo-recibo');
            const tipo = normalizarTipo(tipoRaw);

            const td = document.createElement('td');
            td.className = TIPO_CELL_CLASS;
            td.style.textAlign = 'center';
            td.innerHTML = crearBadgeTipo(tipo);

            const indexNumeroRecibo = obtenerIndiceColumnaNumeroRecibo(table);
            const referencia = indexNumeroRecibo >= 0 ? row.children[indexNumeroRecibo + 1] : null;
            if (referencia) {
                row.insertBefore(td, referencia);
                return;
            }

            row.appendChild(td);
        });
    };

    const aplicarColumnaTipo = () => {
        const table = document.querySelector(TABLE_SELECTOR);
        if (!table) return;
        asegurarHeaderTipo(table);
        asegurarCeldasTipo(table);
    };

    const ocultarColumnasFijas = () => {
        const table = document.querySelector(TABLE_SELECTOR);
        if (!table) return;

        const headers = Array.from(table.querySelectorAll('thead th'));
        if (!headers.length) return;

        const indexesOcultos = [];

        headers.forEach((th, index) => {
            const columnKey = th.querySelector('.btn-filter-column')?.getAttribute('data-column');
            if (COLUMNAS_OCULTAS.includes(String(columnKey || '').toLowerCase())) {
                th.style.display = 'none';
                indexesOcultos.push(index);
            }
        });

        if (!indexesOcultos.length) return;

        table.querySelectorAll('tbody tr').forEach((row) => {
            indexesOcultos.forEach((idx) => {
                if (row.children[idx]) {
                    row.children[idx].style.display = 'none';
                }
            });
        });
    };

})();
</script>

<script>
(() => {
    const initFloatingClear = () => {
        const searchInput = document.getElementById('navSearchInput');
        if (!searchInput) return;

        let btn = document.getElementById('floating-clear-search-btn');
        if (!btn) {
            btn = document.createElement('button');
            btn.id = 'floating-clear-search-btn';
            btn.type = 'button';
            btn.textContent = 'Limpiar filtro';
            btn.style.cssText = [
                'position:fixed',
                'right:20px',
                'bottom:24px',
                'z-index:100000',
                'display:none',
                'padding:10px 14px',
                'border:none',
                'border-radius:999px',
                'background:#0f172a',
                'color:#fff',
                'font-size:12px',
                'font-weight:700',
                'box-shadow:0 10px 20px rgba(15,23,42,.28)',
                'cursor:pointer'
            ].join(';');
            document.body.appendChild(btn);
        }

        const toggleBtn = () => {
            const hasTerm = String(searchInput.value || '').trim().length > 0;
            btn.style.display = hasTerm ? 'block' : 'none';
        };

        const clearSearch = () => {
            const clearBtn = document.getElementById('navSearchClear');
            if (clearBtn) {
                clearBtn.click();
            } else {
                searchInput.value = '';
                searchInput.dispatchEvent(new Event('input', { bubbles: true }));
            }
            btn.style.display = 'none';
        };

        searchInput.addEventListener('input', toggleBtn);
        btn.addEventListener('click', clearSearch);
        toggleBtn();
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initFloatingClear);
        return;
    }
    initFloatingClear();
})();
</script>


@endpush
