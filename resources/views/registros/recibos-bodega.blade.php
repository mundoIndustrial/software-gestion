@extends('layouts.app')

@section('title', 'Recibos Bodega')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="mb-3 d-flex justify-content-end">
                <button type="button" id="openReciboBodegaModalBtn" class="btn btn-primary">
                    Registrar recibo
                </button>
            </div>

            <!-- Table Component -->
            <x-recibos.recibos-costura-table :recibos="$recibos" :totalCantidadGlobal="$totalCantidadGlobal ?? 0" />
        </div>
    </div>
</div>

<div id="reciboBodegaCreateModal" class="custom-recibo-modal" aria-hidden="true">
    <div class="custom-recibo-modal__backdrop" data-close-recibo-modal="true"></div>
    <div class="custom-recibo-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="reciboBodegaCreateModalTitle">
        <div class="custom-recibo-modal__header">
            <div>
                <p class="custom-recibo-modal__eyebrow">Nuevo registro</p>
                <h2 id="reciboBodegaCreateModalTitle" class="mb-0">Registrar recibo de bodega</h2>
            </div>
            <button type="button" class="custom-recibo-modal__close-btn" data-close-recibo-modal="true" aria-label="Cerrar">X</button>
        </div>

        <div class="custom-recibo-modal__body">
            <form id="reciboBodegaCreateForm">
                <div class="section-card">
                    <div class="section-header">
                        <h3 class="section-title">Prendas</h3>
                    </div>

                    <div id="prendasContainer" class="prendas-container">
                        <div class="prenda-card" data-prenda-index="0">
                            <div class="prenda-header">
                                <span class="prenda-number">1</span>
                                <button type="button" class="btn-delete eliminar-prenda-btn" style="display: none;">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path d="M6 18L18 6M6 6l12 12" stroke-width="2" stroke-linecap="round"></path>
                                    </svg>
                                </button>
                            </div>

                            <div class="prenda-content">
                                <div class="form-group">
                                    <label class="form-label-small">Descripcion de la prenda</label>
                                    <input type="text" name="prenda[0]" class="form-input-compact" placeholder="Ej: Polo roja" required>
                                </div>

                                <div class="form-group">
                                    <label class="form-label-small">Detalles adicionales</label>
                                    <textarea name="descripcion[0]" rows="2" class="form-textarea" placeholder="Ej: Pegar bolsillo en la parte frontal"></textarea>
                                </div>

                                <div class="tallas-section">
                                    <label class="form-label-small">Tallas y Cantidades</label>
                                    <div class="tallas-list">
                                        <div>
                                            <input type="text" name="talla[0][]" placeholder="Talla (ej: XS)" required>
                                            <input type="number" name="cantidad[0][]" placeholder="Cantidad" min="1" required>
                                            <button type="button" class="eliminar-talla-btn">x</button>
                                        </div>
                                    </div>
                                    <button type="button" class="btn-add-talla anadir-talla-btn" data-prenda-index="0">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path d="M12 4v16m8-8H4" stroke-width="2" stroke-linecap="round"></path>
                                        </svg>
                                        Anadir talla
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="custom-recibo-modal__footer">
                    <button type="button" class="custom-recibo-btn custom-recibo-btn--secondary" data-close-recibo-modal="true">Cancelar</button>
                    <button type="submit" class="custom-recibo-btn custom-recibo-btn--primary">Guardar recibo</button>
                </div>
            </form>
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
/* Recibos Bodega: ocultar columna Cliente */
#tablaRecibosBody tr td:nth-child(6),
.modern-table thead tr th:nth-child(6) {
    display: none !important;
}
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

.custom-recibo-modal {
    position: fixed;
    inset: 0;
    display: none;
    z-index: 10000100;
}

.custom-recibo-modal.is-open {
    display: block;
}

.custom-recibo-modal__backdrop {
    position: absolute;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
}

.custom-recibo-modal__dialog {
    position: relative;
    width: min(920px, 95vw);
    max-height: 90vh;
    overflow: auto;
    margin: 4vh auto;
    background: #fff;
    border-radius: 16px;
    padding: 0;
    border: 1px solid #dbeafe;
    box-shadow: 0 30px 70px rgba(2, 6, 23, 0.35);
    transform: translateY(10px) scale(0.98);
    opacity: 0;
    transition: opacity .2s ease, transform .2s ease;
}

.custom-recibo-modal.is-open .custom-recibo-modal__dialog {
    transform: translateY(0) scale(1);
    opacity: 1;
}

.custom-recibo-modal__header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 18px 22px;
    border-bottom: 1px solid #e5e7eb;
    background: linear-gradient(180deg, #eff6ff 0%, #ffffff 100%);
}

.custom-recibo-modal__eyebrow {
    margin: 0 0 4px 0;
    color: #2563eb;
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .4px;
}

.custom-recibo-modal__close-btn {
    width: 34px;
    height: 34px;
    border-radius: 999px;
    border: 1px solid #d1d5db;
    background: #fff;
    color: #1f2937;
    font-size: 22px;
    line-height: 1;
    cursor: pointer;
}

.custom-recibo-modal__body {
    padding: 18px 22px 14px;
}

.custom-recibo-modal__footer {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    border-top: 1px solid #e5e7eb;
    padding-top: 14px;
}

.custom-recibo-btn {
    border-radius: 10px;
    padding: 9px 14px;
    font-weight: 600;
    border: 1px solid transparent;
    cursor: pointer;
}

.custom-recibo-btn--secondary {
    border-color: #d1d5db;
    background: #fff;
    color: #374151;
}

.custom-recibo-btn--primary {
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    color: #fff;
}

#reciboBodegaCreateModal .section-card {
    background: #ffffff;
    border-radius: 16px;
    padding: 24px;
    margin-bottom: 20px;
    border: 1px solid #e5e7eb;
    box-shadow: 0 10px 25px rgba(15, 23, 42, 0.06);
}

#reciboBodegaCreateModal .section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

#reciboBodegaCreateModal .section-title {
    font-size: 20px;
    font-weight: 700;
    color: #1f2937;
    margin: 0;
}

#reciboBodegaCreateModal .form-group {
    display: flex;
    flex-direction: column;
    margin-bottom: 12px;
}

#reciboBodegaCreateModal .form-label-small {
    font-size: 14px;
    font-weight: 600;
    color: #374151;
    margin-bottom: 6px;
}

#reciboBodegaCreateModal .form-input-compact,
#reciboBodegaCreateModal .form-textarea,
#reciboBodegaCreateModal .tallas-list input {
    width: 100%;
    padding: 10px 12px;
    border: 2px solid #e5e7eb;
    border-radius: 10px;
    background: #f9fafb;
    color: #1f2937;
    font-size: 14px;
    transition: all .2s ease;
}

#reciboBodegaCreateModal .form-input-compact:focus,
#reciboBodegaCreateModal .form-textarea:focus,
#reciboBodegaCreateModal .tallas-list input:focus {
    outline: none;
    border-color: #2563eb;
    background: #ffffff;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
}

#reciboBodegaCreateModal .form-textarea {
    min-height: 72px;
    resize: vertical;
}

#reciboBodegaCreateModal .prendas-container {
    display: flex;
    flex-direction: column;
    gap: 14px;
}

#reciboBodegaCreateModal .prenda-card {
    border: 2px solid #e5e7eb;
    border-radius: 14px;
    padding: 18px;
    background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
}

#reciboBodegaCreateModal .prenda-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 14px;
}

#reciboBodegaCreateModal .prenda-number {
    width: 30px;
    height: 30px;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    color: #fff;
    font-weight: 700;
}

#reciboBodegaCreateModal .prenda-content {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

#reciboBodegaCreateModal .tallas-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-bottom: 10px;
}

#reciboBodegaCreateModal .tallas-list > div {
    display: grid;
    grid-template-columns: 1fr 1fr auto;
    gap: 8px;
    align-items: center;
}

#reciboBodegaCreateModal .btn-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    border: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    color: #fff;
    cursor: pointer;
}

#reciboBodegaCreateModal .btn-icon svg,
#reciboBodegaCreateModal .btn-delete svg,
#reciboBodegaCreateModal .btn-add-talla svg {
    width: 18px;
    height: 18px;
}

#reciboBodegaCreateModal .btn-delete {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    border: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #fee2e2;
    color: #dc2626;
    cursor: pointer;
}

#reciboBodegaCreateModal .btn-add-talla {
    border: 2px dashed #cbd5e1;
    border-radius: 10px;
    background: #fff;
    color: #2563eb;
    font-weight: 600;
    padding: 9px 14px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    cursor: pointer;
}

#reciboBodegaCreateModal .eliminar-talla-btn {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    border: 1px solid #fca5a5;
    background: #fff;
    color: #dc2626;
    font-weight: 700;
    font-size: 18px;
    line-height: 1;
    cursor: pointer;
}

@media (max-width: 768px) {
    #reciboBodegaCreateModal .custom-recibo-modal__dialog {
        width: 96vw;
        margin: 2vh auto;
    }

    #reciboBodegaCreateModal .section-card {
        padding: 14px;
    }
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('reciboBodegaCreateModal');
    const openBtn = document.getElementById('openReciboBodegaModalBtn');
    const prendasContainer = document.getElementById('prendasContainer');
    const form = document.getElementById('reciboBodegaCreateForm');

    if (!modal || !openBtn || !prendasContainer || !form) return;

    const closeModal = () => {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    };

    const openModal = () => {
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    };

    openBtn.addEventListener('click', openModal);

    modal.addEventListener('click', function (event) {
        if (event.target.matches('[data-close-recibo-modal=\"true\"]')) closeModal();
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') closeModal();
    });

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        closeModal();
    });

    function bindPrendaActions(prendaCard) {
        const prendaIndex = parseInt(prendaCard.dataset.prendaIndex || '0', 10);
        const addTallaBtn = prendaCard.querySelector('.anadir-talla-btn');
        const tallasList = prendaCard.querySelector('.tallas-list');

        if (addTallaBtn && tallasList) {
            addTallaBtn.addEventListener('click', function () {
                const tallaRow = document.createElement('div');
                tallaRow.innerHTML = `
                    <input type="text" name="talla[${prendaIndex}][]" placeholder="Talla (ej: XS)" required>
                    <input type="number" name="cantidad[${prendaIndex}][]" placeholder="Cantidad" min="1" required>
                    <button type="button" class="eliminar-talla-btn">x</button>
                `;
                tallasList.appendChild(tallaRow);
            });
        }
    }

    prendasContainer.addEventListener('click', function (event) {
        if (!event.target.classList.contains('eliminar-talla-btn')) return;
        const tallasList = event.target.closest('.tallas-list');
        if (!tallasList) return;
        const rows = tallasList.querySelectorAll(':scope > div');
        if (rows.length > 1) event.target.closest('div')?.remove();
    });

    prendasContainer.querySelectorAll('.prenda-card').forEach(bindPrendaActions);
});
</script>
@endpush
