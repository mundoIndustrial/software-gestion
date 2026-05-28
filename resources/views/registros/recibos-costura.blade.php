@extends('layouts.app')

@section('title', 'Recibos de Costura')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="recibos-area-tabs" role="tablist" aria-label="Filtrar por área">
                <button type="button" class="recibos-area-tab is-active" data-area-tab="all" aria-pressed="true">Todos</button>
                <button type="button" class="recibos-area-tab" data-area-tab="corte" aria-pressed="false">Corte</button>
                <button type="button" class="recibos-area-tab" data-area-tab="costura" aria-pressed="false">Costura</button>
                <button type="button" class="recibos-area-tab" data-area-tab="control-calidad" aria-pressed="false">Control Calidad</button>
                <button type="button" class="recibos-area-tab" data-area-tab="entrega" aria-pressed="false">Entrega</button>
                <button type="button" class="recibos-area-tab" data-area-tab="despacho" aria-pressed="false">Despacho</button>
            </div>
            <!-- Table Component -->
            <x-recibos.recibos-costura-table :recibos="$recibos" :totalCantidadGlobal="$totalCantidadGlobal ?? 0" />
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

/* Colores personalizados para badges de Area */
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

.recibos-area-tabs {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 14px;
}

.recibos-area-tab {
    border: 1px solid #d1d5db;
    background: #f9fafb;
    color: #374151;
    border-radius: 999px;
    padding: 6px 14px;
    font-size: 13px;
    font-weight: 600;
    line-height: 1;
    cursor: pointer;
    transition: all 0.2s ease;
}

.recibos-area-tab:hover {
    background: #eef2ff;
    border-color: #c7d2fe;
}

.recibos-area-tab.is-active {
    background: #1d4ed8;
    border-color: #1d4ed8;
    color: #ffffff;
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
<!-- 
    ============================================
    PHASE 2: Modulo Modular DDD Recibos Costura
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

<!-- Filter Module - Sistema de filtrado de tabla -->
<script src="{{ asset('js/recibos-costura/modules/FilterModule.js') }}"></script>

<!-- Dropdown Service - Sistema de dropdowns -->
<script src="{{ asset('js/recibos-costura/services/DropdownService.js') }}"></script>

<!-- Costura Notification Bell Service - Sistema de notificaciones de campana -->
<script src="{{ asset('js/recibos-costura/services/CosturaNotificationBellService.js') }}"></script>

<!-- Realtime Recibo Listener - Sistema de escucha en tiempo real de eventos -->
<script src="{{ asset('js/recibos-costura/services/RealtimeReciboListener.js') }}"></script>

<!-- Tracking Modal Controller - Controlador de modal de seguimiento -->
<script src="{{ asset('js/recibos-costura/controllers/TrackingModalController.js') }}"></script>

<!-- Add Process Modal Controller - Controlador de modal para agregar procesos -->
<script src="{{ asset('js/recibos-costura/controllers/AddProcessModalController.js') }}"></script>

<!-- Legacy Handlers - Funciones heredadas que delegan a modulos -->
<script src="{{ asset('js/recibos-costura/legacy-handlers.js') }}"></script>

<!-- Search Module - Sistema de busqueda AJAX -->
<script src="{{ asset('js/recibos-costura/search.js') }}?v={{ time() }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const tabs = Array.from(document.querySelectorAll('.recibos-area-tab'));
    if (!tabs.length) return;
    const storageKey = 'recibos_costura_filters';
    const url = new URL(window.location.href);
    const areaParam = (url.searchParams.get('area') || '').trim();

    const getTabFromAreaParam = (value) => {
        const lower = value.toLowerCase();
        if (!lower) return 'all';
        if (lower.includes('control calidad') || lower.includes('control de calidad')) return 'control-calidad';
        if (lower.includes('corte')) return 'corte';
        if (lower.includes('costura')) return 'costura';
        if (lower.includes('entrega')) return 'entrega';
        if (lower.includes('despacho')) return 'despacho';
        return 'all';
    };

    const tabToAreaValue = {
        'all': '',
        'corte': 'Corte',
        'costura': 'Costura',
        'control-calidad': 'Control Calidad,Control de Calidad',
        'entrega': 'Entrega',
        'despacho': 'Despacho'
    };


    const setActiveTabUI = (targetTab) => {
        tabs.forEach((tab) => {
            const isActive = tab.getAttribute('data-area-tab') === targetTab;
            tab.classList.toggle('is-active', isActive);
            tab.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });
    };

    const syncAreaFilterStorage = (areaValue) => {
        try {
            const raw = localStorage.getItem(storageKey);
            const filters = raw ? JSON.parse(raw) : {};

            if (!areaValue) {
                delete filters.area;
            } else {
                filters.area = areaValue.split(',').map(v => v.trim()).filter(Boolean);
            }

            localStorage.setItem(storageKey, JSON.stringify(filters));
            window.__columnFilters = filters;
        } catch (e) {
            // no-op
        }
    };

    tabs.forEach((tab) => {
        tab.addEventListener('click', function () {
            const targetTab = this.getAttribute('data-area-tab') || 'all';
            const nextArea = tabToAreaValue[targetTab] || '';
            const nextUrl = new URL(window.location.href);

            if (nextArea) nextUrl.searchParams.set('area', nextArea);
            else nextUrl.searchParams.delete('area');

            nextUrl.searchParams.delete('page');
            syncAreaFilterStorage(nextArea);
            window.location.href = nextUrl.toString();
        });
    });

    const activeTab = getTabFromAreaParam(areaParam);
    setActiveTabUI(activeTab);
});
</script>
@endpush
