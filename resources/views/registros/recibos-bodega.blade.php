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

            <div id="recibo-corte-bodega-container">
                <div class="table-scroll-container">
                    <table id="recibo-corte-bodega-table" class="table table-striped table-hover modern-table">
                        <thead class="table-header">
                            <tr>
                                <th style="width: 60px; text-align: center;">Acciones</th>
                                <th style="width: 120px; text-align: center;">N&deg; Recibo</th>
                                <th>Prenda</th>
                                <th>Descripción</th>
                                <th style="width: 120px;">Tallas</th>
                                <th style="width: 120px;">Cantidad Total</th>
                                <th style="width: 150px;">Fecha de creación</th>
                            </tr>
                        </thead>
                        <tbody id="recibo-corte-bodega-tbody">
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">Cargando recibos...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="reciboBodegaCreateModal" class="custom-recibo-modal">
    <div class="custom-recibo-modal__backdrop" data-close-recibo-modal="true"></div>
    <div class="custom-recibo-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="reciboBodegaCreateModalTitle">
        <div class="custom-recibo-modal__header">
            <div class="custom-recibo-modal__title-wrap">
                <p class="custom-recibo-modal__eyebrow">Módulo bodega</p>
                <h2 id="reciboBodegaCreateModalTitle" class="mb-0">Nuevo recibo de bodega</h2>
                <p class="custom-recibo-modal__subtitle">Registra prendas, color por talla y cantidades para generar el recibo.</p>
            </div>
            <button type="button" class="custom-recibo-modal__close-btn" data-close-recibo-modal="true" aria-label="Cerrar">×</button>
        </div>

        <div class="custom-recibo-modal__body">
            <form id="reciboBodegaCreateForm">
                <div class="section-card">
                    <div class="section-header">
                        <h3 class="section-title">Detalle de prendas</h3>
                    </div>

                    <div id="prendasContainer" class="prendas-container">
                        <div class="prenda-card" data-prenda-index="0">
                            <div class="prenda-header">
                                <span class="prenda-number">01</span>
                                <button type="button" class="btn-delete eliminar-prenda-btn" style="display: none;">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path d="M6 18L18 6M6 6l12 12" stroke-width="2" stroke-linecap="round"></path>
                                    </svg>
                                </button>
                            </div>

                            <div class="prenda-content">
                                <div class="prenda-main-grid">
                                    <div class="form-group">
                                        <label class="form-label-small">Descripción</label>
                                        <textarea name="prenda[0]" rows="3" class="form-textarea form-textarea-no-resize" placeholder="Ej: Polo roja con bolsillo frontal" required></textarea>
                                    </div>
                                </div>

                                <div class="tallas-section">
                                    <label class="form-label-small">Tallas, color y cantidad</label>
                                    <div class="genero-selector" role="group" aria-label="Seleccionar género">
                                        <label class="genero-check">
                                            <input type="checkbox" class="genero-check-input" data-genero-toggle="dama">
                                            <span>Dama</span>
                                        </label>
                                        <label class="genero-check">
                                            <input type="checkbox" class="genero-check-input" data-genero-toggle="caballero">
                                            <span>Caballero</span>
                                        </label>
                                        <label class="genero-check">
                                            <input type="checkbox" class="genero-check-input" data-genero-toggle="unisex">
                                            <span>Unisex</span>
                                        </label>
                                    </div>
                                    <datalist id="tallas-sugeridas-list">
                                        <option value="XS"></option><option value="S"></option><option value="M"></option><option value="L"></option><option value="XL"></option>
                                        <option value="XXL"></option><option value="XXXL"></option><option value="4"></option><option value="6"></option><option value="8"></option>
                                        <option value="10"></option><option value="12"></option><option value="14"></option><option value="16"></option><option value="18"></option>
                                        <option value="20"></option><option value="22"></option><option value="24"></option><option value="26"></option><option value="28"></option>
                                        <option value="30"></option><option value="32"></option><option value="34"></option><option value="36"></option><option value="38"></option>
                                        <option value="40"></option><option value="42"></option>
                                    </datalist>
                                    <datalist id="tallas-sugeridas-letra-list">
                                        <option value="XS"></option><option value="S"></option><option value="M"></option><option value="L"></option><option value="XL"></option>
                                        <option value="XXL"></option><option value="XXXL"></option><option value="XXXXL"></option>
                                    </datalist>
                                    <datalist id="tallas-sugeridas-numero-list">
                                        <option value="4"></option><option value="6"></option><option value="8"></option><option value="10"></option><option value="12"></option>
                                        <option value="14"></option><option value="16"></option><option value="18"></option><option value="20"></option><option value="22"></option>
                                        <option value="24"></option><option value="26"></option><option value="28"></option><option value="30"></option><option value="32"></option>
                                        <option value="34"></option><option value="36"></option><option value="38"></option><option value="40"></option><option value="42"></option>
                                        <option value="44"></option><option value="46"></option><option value="48"></option><option value="50"></option>
                                    </datalist>
                                    <div class="tallas-subsection is-hidden" data-genero-section="dama">
                                        <label class="form-label-small mb-1">Dama</label>
                                        <div class="tallas-head"><span>Talla</span><span>Color</span><span>Cantidad</span><span></span></div>
                                        <div class="tallas-list tallas-list-dama">
                                            <div>
                                                <input type="text" name="talla_dama[0][]" class="talla-input-uppercase" list="tallas-sugeridas-list" placeholder="XS">
                                                <input type="text" name="color_dama[0][]" class="color-input-uppercase" placeholder="ROJO">
                                                <input type="number" name="cantidad_dama[0][]" placeholder="0" min="1">
                                                <button type="button" class="eliminar-talla-btn">x</button>
                                            </div>
                                        </div>
                                        <button type="button" class="btn-add-talla anadir-talla-dama-btn" data-prenda-index="0">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                                <path d="M12 4v16m8-8H4" stroke-width="2" stroke-linecap="round"></path>
                                            </svg>
                                            Agregar talla dama
                                        </button>
                                    </div>
                                    <div class="tallas-subsection mt-2 is-hidden" data-genero-section="caballero">
                                        <label class="form-label-small mb-1">Caballero</label>
                                        <div class="tallas-head"><span>Talla</span><span>Color</span><span>Cantidad</span><span></span></div>
                                        <div class="tallas-list tallas-list-caballero">
                                            <div>
                                                <input type="text" name="talla_caballero[0][]" class="talla-input-uppercase" list="tallas-sugeridas-list" placeholder="M">
                                                <input type="text" name="color_caballero[0][]" class="color-input-uppercase" placeholder="NEGRO">
                                                <input type="number" name="cantidad_caballero[0][]" placeholder="0" min="1">
                                                <button type="button" class="eliminar-talla-btn">x</button>
                                            </div>
                                        </div>
                                        <button type="button" class="btn-add-talla anadir-talla-caballero-btn" data-prenda-index="0">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                                <path d="M12 4v16m8-8H4" stroke-width="2" stroke-linecap="round"></path>
                                            </svg>
                                            Agregar talla caballero
                                        </button>
                                    </div>
                                    <div class="tallas-subsection mt-2 is-hidden" data-genero-section="unisex">
                                        <label class="form-label-small mb-1">Unisex</label>
                                        <div class="tallas-head"><span>Talla</span><span>Color</span><span>Cantidad</span><span></span></div>
                                        <div class="tallas-list tallas-list-unisex">
                                            <div>
                                                <input type="text" name="talla_unisex[0][]" class="talla-input-uppercase" list="tallas-sugeridas-list" placeholder="M">
                                                <input type="text" name="color_unisex[0][]" class="color-input-uppercase" placeholder="AZUL">
                                                <input type="number" name="cantidad_unisex[0][]" placeholder="0" min="1">
                                                <button type="button" class="eliminar-talla-btn">x</button>
                                            </div>
                                        </div>
                                        <button type="button" class="btn-add-talla anadir-talla-unisex-btn" data-prenda-index="0">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                                <path d="M12 4v16m8-8H4" stroke-width="2" stroke-linecap="round"></path>
                                            </svg>
                                            Agregar talla unisex
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="custom-recibo-modal__footer">
                    <button type="button" class="custom-recibo-btn custom-recibo-btn--secondary" data-close-recibo-modal="true">Cancelar</button>
                    <button type="submit" class="custom-recibo-btn custom-recibo-btn--primary">Generar recibo</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="tipoTallaGeneroModal" class="tipo-talla-modal is-hidden" aria-hidden="true">
    <div class="tipo-talla-modal__backdrop"></div>
    <div class="tipo-talla-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="tipoTallaGeneroModalTitle">
        <h3 id="tipoTallaGeneroModalTitle" class="mb-2">Tipo de talla</h3>
        <p id="tipoTallaGeneroModalText" class="mb-3 text-sm text-muted">Selecciona si deseas manejar tallas por letra o por número.</p>
        <div class="tipo-talla-modal__actions">
            <button type="button" class="btn btn-outline-primary" data-tipo-talla-select="letra">Por letra</button>
            <button type="button" class="btn btn-primary" data-tipo-talla-select="numero">Por número</button>
        </div>
        <div id="tipoTallaGeneroGrid" class="tipo-talla-grid"></div>
        <div class="tipo-talla-modal__footer">
            <button type="button" id="cancelarTipoTallaGeneroBtn" class="btn btn-light">Cancelar</button>
            <button type="button" id="confirmarTipoTallaGeneroBtn" class="btn btn-primary" disabled>Confirmar</button>
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

<!-- Modal para ver recibo de corte para bodega -->
<x-orders-components.recibo-corte-bodega-detail-modal />

@endsection

<!-- Contenedor para Toast Notifications -->
<div class="toast-container" id="toastContainer"></div>

@push('styles')
<link rel="stylesheet" href="{{ asset('css/recibos-costura.css') }}?v={{ time() }}">
<link rel="stylesheet" href="{{ asset('css/tracking-modal.css') }}?v={{ time() }}">
<link rel="stylesheet" href="{{ asset('css/dropdowns-recibos.css') }}?v={{ time() }}">

<style>
/* Ajustes exclusivos para tabla Recibo Corte Bodega */
#recibo-corte-bodega-table {
    width: 100%;
    min-width: 860px;
    table-layout: fixed;
    border-collapse: separate;
    border-spacing: 0;
}

#recibo-corte-bodega-table th,
#recibo-corte-bodega-table td {
    vertical-align: middle;
    white-space: normal;
    word-break: break-word;
    overflow-wrap: anywhere;
    line-height: 1.35;
}

#recibo-corte-bodega-table th:nth-child(1),
#recibo-corte-bodega-table td:nth-child(1) { width: 60px; text-align: center; }
#recibo-corte-bodega-table th:nth-child(2),
#recibo-corte-bodega-table td:nth-child(2) { width: 120px; text-align: center; white-space: nowrap; }
#recibo-corte-bodega-table th:nth-child(3),
#recibo-corte-bodega-table td:nth-child(3) { width: 170px; }
#recibo-corte-bodega-table th:nth-child(4),
#recibo-corte-bodega-table td:nth-child(4) { width: 320px; }
#recibo-corte-bodega-table th:nth-child(5),
#recibo-corte-bodega-table td:nth-child(5) { width: 120px; text-align: center; }
#recibo-corte-bodega-table th:nth-child(6),
#recibo-corte-bodega-table td:nth-child(6) { width: 140px; text-align: center; }
#recibo-corte-bodega-table th:nth-child(7),
#recibo-corte-bodega-table td:nth-child(7) { width: 150px; text-align: center; white-space: nowrap; }
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
    pointer-events: none;
}

.custom-recibo-modal.is-open {
    display: flex;
    align-items: center;
    justify-content: center;
    pointer-events: auto;
}

.custom-recibo-modal__backdrop {
    position: fixed;
    inset: 0;
    background: radial-gradient(circle at 20% 10%, rgba(14, 116, 144, 0.25), rgba(15, 23, 42, 0.65));
    cursor: pointer;
}

.custom-recibo-modal__dialog {
    position: fixed;
    inset: 0;
    width: 100vw;
    height: 100vh;
    max-height: 100vh;
    min-height: 100vh;
    overflow: hidden;
    margin: 0;
    background: #f8fafc;
    border-radius: 0;
    padding: 0;
    border: 0;
    box-shadow: none;
    transform: translateY(10px) scale(0.98);
    opacity: 0;
    transition: opacity .2s ease, transform .2s ease;
    pointer-events: none;
}

.custom-recibo-modal.is-open .custom-recibo-modal__dialog {
    transform: translateY(0) scale(1);
    opacity: 1;
    pointer-events: auto;
}

.custom-recibo-modal__header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 12px 16px 10px;
    border-bottom: 1px solid #dbe5f1;
    background: linear-gradient(135deg, #1d4ed8 0%, #2563eb 55%, #3b82f6 100%);
}

.custom-recibo-modal__title-wrap h2 {
    color: #f8fafc;
    font-size: 20px;
    font-weight: 700;
    margin: 0;
}

.custom-recibo-modal__eyebrow {
    margin: 0 0 4px 0;
    color: #cbd5e1;
    font-size: 10px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .8px;
}

.custom-recibo-modal__subtitle {
    margin: 4px 0 0 0;
    color: #cbd5e1;
    font-size: 11px;
}

.custom-recibo-modal__close-btn {
    width: 30px;
    height: 30px;
    border-radius: 999px;
    border: 1px solid rgba(191, 219, 254, 0.7);
    background: rgba(30, 64, 175, 0.35);
    color: #f8fafc;
    font-size: 18px;
    line-height: 1;
    cursor: pointer;
}

.custom-recibo-modal__body {
    padding: 16px 20px 12px;
    overflow-y: auto;
    max-height: calc(100vh - 124px);
}

.custom-recibo-modal__footer {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    border-top: 1px solid #dbe5f1;
    padding-top: 14px;
    margin-top: 12px;
}

.custom-recibo-btn {
    border-radius: 10px;
    padding: 10px 14px;
    font-weight: 600;
    border: 1px solid transparent;
    cursor: pointer;
}

.custom-recibo-btn--secondary {
    border-color: #cbd5e1;
    background: #fff;
    color: #0f172a;
}

.custom-recibo-btn--primary {
    background: linear-gradient(135deg, #0284c7 0%, #2563eb 100%);
    color: #fff;
}

#reciboBodegaCreateModal .section-card {
    background: #ffffff;
    border-radius: 16px;
    padding: 18px;
    margin-bottom: 12px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 12px 28px rgba(15, 23, 42, 0.06);
}

#reciboBodegaCreateModal .section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
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
    margin-bottom: 10px;
}

#reciboBodegaCreateModal .form-label-small {
    font-size: 13px;
    font-weight: 600;
    color: #0f172a;
    margin-bottom: 4px;
}

#reciboBodegaCreateModal .form-input-compact,
#reciboBodegaCreateModal .form-textarea,
#reciboBodegaCreateModal .tallas-list input {
    width: 100%;
    padding: 9px 11px;
    border: 1px solid #cbd5e1;
    border-radius: 10px;
    background: #f8fafc;
    color: #1f2937;
    font-size: 14px;
    transition: all .2s ease;
}

#reciboBodegaCreateModal .form-input-compact:focus,
#reciboBodegaCreateModal .form-textarea:focus,
#reciboBodegaCreateModal .tallas-list input:focus {
    outline: none;
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.15);
}

#reciboBodegaCreateModal .form-textarea {
    min-height: 64px;
    resize: vertical;
}

#reciboBodegaCreateModal .form-textarea-no-resize {
    resize: vertical !important;
    overflow: auto;
}

#reciboBodegaCreateModal input[type="text"],
#reciboBodegaCreateModal textarea {
    text-transform: uppercase;
}

#reciboBodegaCreateModal .prendas-container {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

#reciboBodegaCreateModal .prenda-card {
    border: 1px solid #dbe5f1;
    border-radius: 14px;
    padding: 14px;
    background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
}

#reciboBodegaCreateModal .prenda-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}

#reciboBodegaCreateModal .prenda-number {
    width: 38px;
    height: 26px;
    border-radius: 999px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #0f172a;
    color: #fff;
    font-weight: 700;
}

#reciboBodegaCreateModal .prenda-content {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

#reciboBodegaCreateModal .prenda-main-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 12px;
}

#reciboBodegaCreateModal .tallas-list {
    display: flex;
    flex-direction: column;
    gap: 6px;
    margin-bottom: 8px;
}

#reciboBodegaCreateModal .tallas-list > div {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr auto;
    gap: 8px;
    align-items: center;
}
#reciboBodegaCreateModal .tallas-subsection {
    border: 1px solid #dbe5f1;
    border-radius: 12px;
    padding: 10px;
    background: #ffffff;
}

#reciboBodegaCreateModal .tallas-subsection.is-hidden {
    display: none;
}

#reciboBodegaCreateModal .genero-selector {
    display: flex;
    gap: 8px;
    margin: 8px 0 10px;
    flex-wrap: wrap;
}

#reciboBodegaCreateModal .genero-check {
    border: 1px solid #93c5fd;
    background: #f8fafc;
    color: #1e3a8a;
    border-radius: 999px;
    padding: 6px 10px;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

#reciboBodegaCreateModal .genero-check-input {
    width: 14px;
    height: 14px;
    accent-color: #1d4ed8;
    cursor: pointer;
}

#reciboBodegaCreateModal .genero-check.is-active {
    background: #1d4ed8;
    color: #fff;
    border-color: #1d4ed8;
}

.tipo-talla-modal {
    position: fixed;
    inset: 0;
    z-index: 10000200;
}

.tipo-talla-modal.is-hidden {
    display: none;
}

.tipo-talla-modal__backdrop {
    position: absolute;
    inset: 0;
    background: rgba(15, 23, 42, 0.45);
}

.tipo-talla-modal__dialog {
    position: relative;
    z-index: 10000201;
    width: min(92vw, 420px);
    margin: 14vh auto 0;
    background: #fff;
    border-radius: 14px;
    padding: 16px;
    box-shadow: 0 24px 44px rgba(15, 23, 42, 0.32);
}

.tipo-talla-modal__actions {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
    margin-bottom: 12px;
}

.tipo-talla-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(84px, 1fr));
    gap: 8px;
    margin-bottom: 12px;
}

.tipo-talla-pill {
    border: 2px solid #d1d5db;
    background: #fff;
    color: #1f2937;
    border-radius: 8px;
    padding: 8px 10px;
    font-weight: 700;
    cursor: pointer;
}

.tipo-talla-pill.is-selected {
    border-color: #1d4ed8;
    background: #eff6ff;
    color: #1d4ed8;
}

.tipo-talla-modal__footer {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
}

#reciboBodegaCreateModal .tallas-head {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr 32px;
    gap: 8px;
    margin-bottom: 6px;
    color: #64748b;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: .6px;
    font-weight: 700;
}

#reciboBodegaCreateModal .talla-input-uppercase {
    text-transform: uppercase;
}

#reciboBodegaCreateModal .color-input-uppercase {
    text-transform: uppercase;
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
    background: #fff5f5;
    color: #dc2626;
    cursor: pointer;
}

#reciboBodegaCreateModal .btn-add-talla {
    border: 1px dashed #60a5fa;
    border-radius: 10px;
    background: #eff6ff;
    color: #1d4ed8;
    font-weight: 600;
    padding: 7px 10px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    cursor: pointer;
}

#reciboBodegaCreateModal .eliminar-talla-btn {
    width: 30px;
    height: 30px;
    border-radius: 6px;
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
        width: 100vw;
        max-height: 100vh;
        min-height: 100vh;
        border-radius: 0;
        margin: 0;
    }

    #reciboBodegaCreateModal .section-card {
        padding: 12px;
    }

    #reciboBodegaCreateModal .tallas-list > div {
        grid-template-columns: 1fr 1fr;
    }

    #reciboBodegaCreateModal .tallas-head {
        display: none;
    }

    #reciboBodegaCreateModal .custom-recibo-modal__title-wrap h2 {
        font-size: 22px;
    }

    #reciboBodegaCreateModal .prenda-main-grid {
        grid-template-columns: 1fr;
        gap: 8px;
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
    const tipoTallaModal = document.getElementById('tipoTallaGeneroModal');
    const tipoTallaModalText = document.getElementById('tipoTallaGeneroModalText');
    const tipoTallaCancelarBtn = document.getElementById('cancelarTipoTallaGeneroBtn');
    const tipoTallaConfirmarBtn = document.getElementById('confirmarTipoTallaGeneroBtn');
    const tipoTallaGrid = document.getElementById('tipoTallaGeneroGrid');

    if (!modal || !openBtn || !prendasContainer || !form || !tipoTallaModal || !tipoTallaModalText) return;

    const tipoTallaState = {
        isOpen: false,
        resolve: null,
        genero: null,
        tipoSeleccionado: null,
        tallasSeleccionadas: new Set(),
    };

    const LISTA_TALLAS_POR_TIPO = {
        letra: 'tallas-sugeridas-letra-list',
        numero: 'tallas-sugeridas-numero-list',
    };
    const TALLAS_POR_TIPO = {
        letra: ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL', 'XXXXL'],
        numero: ['4', '6', '8', '10', '12', '14', '16', '18', '20', '22', '24', '26', '28', '30', '32', '34', '36', '38', '40', '42', '44', '46', '48', '50'],
    };

    function toGeneroLabel(genero) {
        const key = String(genero || '').trim().toLowerCase();
        if (key === 'dama') return 'Dama';
        if (key === 'caballero') return 'Caballero';
        if (key === 'unisex') return 'Unisex';
        return 'Género';
    }

    function renderTallasDisponibles(tipo) {
        if (!tipoTallaGrid) return;
        const tallas = TALLAS_POR_TIPO[tipo] || [];
        tipoTallaGrid.innerHTML = '';
        tallas.forEach((talla) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'tipo-talla-pill';
            btn.dataset.talla = talla;
            btn.textContent = talla;
            btn.addEventListener('click', function () {
                if (tipoTallaState.tallasSeleccionadas.has(talla)) {
                    tipoTallaState.tallasSeleccionadas.delete(talla);
                    btn.classList.remove('is-selected');
                } else {
                    tipoTallaState.tallasSeleccionadas.add(talla);
                    btn.classList.add('is-selected');
                }
                if (tipoTallaConfirmarBtn) {
                    tipoTallaConfirmarBtn.disabled = tipoTallaState.tallasSeleccionadas.size === 0;
                }
            });
            tipoTallaGrid.appendChild(btn);
        });
    }

    function abrirModalTipoTalla(genero) {
        return new Promise((resolve) => {
            tipoTallaState.isOpen = true;
            tipoTallaState.resolve = resolve;
            tipoTallaState.genero = genero;
            tipoTallaState.tipoSeleccionado = null;
            tipoTallaState.tallasSeleccionadas = new Set();
            tipoTallaModalText.textContent = `Selecciona el tipo de talla para ${toGeneroLabel(genero)}.`;
            if (tipoTallaGrid) tipoTallaGrid.innerHTML = '';
            if (tipoTallaConfirmarBtn) tipoTallaConfirmarBtn.disabled = true;
            tipoTallaModal.classList.remove('is-hidden');
            tipoTallaModal.setAttribute('aria-hidden', 'false');
        });
    }

    function cerrarModalTipoTalla(resultado) {
        if (!tipoTallaState.isOpen) return;
        tipoTallaModal.classList.add('is-hidden');
        tipoTallaModal.setAttribute('aria-hidden', 'true');
        tipoTallaState.isOpen = false;
        if (typeof tipoTallaState.resolve === 'function') {
            tipoTallaState.resolve(resultado || null);
        }
        tipoTallaState.resolve = null;
        tipoTallaState.genero = null;
        tipoTallaState.tipoSeleccionado = null;
        tipoTallaState.tallasSeleccionadas = new Set();
    }

    function setTipoTallaEnSeccion(section, tipo) {
        if (!section) return;
        const datalistId = LISTA_TALLAS_POR_TIPO[tipo] || 'tallas-sugeridas-list';
        section.dataset.tipoTalla = tipo || '';
        section.querySelectorAll('input[name^="talla_"]').forEach((input) => {
            input.setAttribute('list', datalistId);
            input.placeholder = tipo === 'numero' ? '34' : 'M';
        });
    }

    function crearFilaTalla(prendaIndex, genero, listId, talla = '') {
        const fila = document.createElement('div');
        fila.innerHTML = `
            <input type="text" name="talla_${genero}[${prendaIndex}][]" class="talla-input-uppercase" list="${listId}" placeholder="Talla" value="${talla}">
            <input type="text" name="color_${genero}[${prendaIndex}][]" class="color-input-uppercase" placeholder="Color (ej: ROJO)">
            <input type="number" name="cantidad_${genero}[${prendaIndex}][]" placeholder="Cantidad" min="1">
            <button type="button" class="eliminar-talla-btn">x</button>
        `;
        return fila;
    }

    function aplicarTallasSeleccionadas(prendaCard, genero, tipo, tallasSeleccionadas) {
        const prendaIndex = parseInt(prendaCard.dataset.prendaIndex || '0', 10);
        const list = prendaCard.querySelector(`.tallas-list-${genero}`);
        if (!list) return;
        const listId = LISTA_TALLAS_POR_TIPO[tipo] || 'tallas-sugeridas-list';
        list.innerHTML = '';
        (tallasSeleccionadas || []).forEach((talla) => {
            list.appendChild(crearFilaTalla(prendaIndex, genero, listId, talla));
        });
        if ((tallasSeleccionadas || []).length === 0) {
            list.appendChild(crearFilaTalla(prendaIndex, genero, listId, ''));
        }
    }

    let previousActiveElement = null;

    const closeModal = () => {
        modal.classList.remove('is-open');
        document.body.style.overflow = '';
        if (previousActiveElement) {
            previousActiveElement.focus();
            previousActiveElement = null;
        }
    };

    const openModal = () => {
        previousActiveElement = document.activeElement;
        modal.classList.add('is-open');
        document.body.style.overflow = 'hidden';
        const firstInput = form.querySelector('input, textarea, button[type="submit"]');
        if (firstInput) setTimeout(() => firstInput.focus(), 100);
    };

    openBtn.addEventListener('click', openModal);

    modal.addEventListener('click', function (event) {
        if (event.target === modal || event.target.closest('[data-close-recibo-modal="true"]')) {
            closeModal();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && modal.classList.contains('is-open')) {
            closeModal();
        }
    });

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        console.log('[FORM] Submit iniciado');

        const formData = new FormData(form);
        const prendas = [];

        const prendaIndices = new Set();
        for (const [key, value] of formData.entries()) {
            const match = key.match(/^prenda\[(\d+)\]/);
            if (match) {
                prendaIndices.add(parseInt(match[1]));
            }
        }

        console.log('[FORM] indices de prendas encontrados:', Array.from(prendaIndices));

        prendaIndices.forEach(index => {
            const nombre = formData.get(`prenda[${index}]`);
            const descripcion = formData.get(`descripcion[${index}]`);
            const tallasDama = formData.getAll(`talla_dama[${index}][]`);
            const coloresDama = formData.getAll(`color_dama[${index}][]`);
            const cantidadesDama = formData.getAll(`cantidad_dama[${index}][]`);
            const tallasCab = formData.getAll(`talla_caballero[${index}][]`);
            const coloresCab = formData.getAll(`color_caballero[${index}][]`);
            const cantidadesCab = formData.getAll(`cantidad_caballero[${index}][]`);
            const tallasUni = formData.getAll(`talla_unisex[${index}][]`);
            const coloresUni = formData.getAll(`color_unisex[${index}][]`);
            const cantidadesUni = formData.getAll(`cantidad_unisex[${index}][]`);

            const tallasList = [];

            tallasDama.forEach((talla, i) => {
                const cantidad = parseInt(cantidadesDama[i]) || 0;
                const color = (coloresDama[i] || '').trim();
                if ((talla || '').trim() !== '' && cantidad > 0) {
                    tallasList.push({ talla, color, cantidad, genero: 'dama' });
                }
            });

            tallasCab.forEach((talla, i) => {
                const cantidad = parseInt(cantidadesCab[i]) || 0;
                const color = (coloresCab[i] || '').trim();
                if ((talla || '').trim() !== '' && cantidad > 0) {
                    tallasList.push({ talla, color, cantidad, genero: 'caballero' });
                }
            });

            tallasUni.forEach((talla, i) => {
                const cantidad = parseInt(cantidadesUni[i]) || 0;
                const color = (coloresUni[i] || '').trim();
                if ((talla || '').trim() !== '' && cantidad > 0) {
                    tallasList.push({ talla, color, cantidad, genero: 'unisex' });
                }
            });

            console.log(`[FORM] Prenda ${index}:`, { nombre, descripcion, tallasList });

            if (nombre && tallasList.length > 0) {
                
                prendas.push({
                    nombre: nombre,
                    descripcion: descripcion || null,
                    tallas: tallasList,
                });
            }
        });

        console.log('[FORM] Prendas procesadas:', prendas);

        if (prendas.length === 0) {
            alert('Por favor completa al menos una prenda con talla y cantidad');
            return;
        }

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        console.log('[FORM] CSRF Token:', csrfToken ? 'Presente' : 'NO ENCONTRADO');

        const payload = { prendas: prendas };
        console.log('[FORM] Enviando payload:', JSON.stringify(payload));

        fetch('/api/recibo-corte-bodega', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify(payload),
        })
        .then(async response => {
            console.log('[FETCH] Response status:', response.status, response.statusText);
            const raw = await response.text();
            let parsed = null;

            try {
                parsed = raw ? JSON.parse(raw) : null;
            } catch (_) {
                parsed = null;
            }

            if (!response.ok) {
                const backendMessage = parsed?.message || raw || `HTTP ${response.status}: ${response.statusText}`;
                throw new Error(backendMessage);
            }

            return parsed || {};
        })
        .then(data => {
            console.log('[FETCH] Response data:', data);
            if (data.success) {
                closeModal();
                form.reset();
                alert('Recibo registrado correctamente');
                loadRecibosCorteForBodega();
                if (data.prendas && data.prendas.length > 0) {
                    setTimeout(() => openReciboCorteBodegaModal(data.prendas[0].id), 500);
                }
            } else {
                alert('Error: ' + (data.message || 'No se pudo guardar el recibo'));
            }
        })
        .catch(error => {
            console.error('[FETCH] Error:', error);
            alert('Error al guardar el recibo: ' + error.message);
        });
    });

    function bindPrendaActions(prendaCard) {
        const prendaIndex = parseInt(prendaCard.dataset.prendaIndex || '0', 10);
        const addDamaBtn = prendaCard.querySelector('.anadir-talla-dama-btn');
        const addCabBtn = prendaCard.querySelector('.anadir-talla-caballero-btn');
        const addUniBtn = prendaCard.querySelector('.anadir-talla-unisex-btn');
        const tallasDamaList = prendaCard.querySelector('.tallas-list-dama');
        const tallasCabList = prendaCard.querySelector('.tallas-list-caballero');
        const tallasUniList = prendaCard.querySelector('.tallas-list-unisex');

        if (addDamaBtn && tallasDamaList) {
            addDamaBtn.addEventListener('click', function () {
                const tallaRow = document.createElement('div');
                const section = prendaCard.querySelector('[data-genero-section="dama"]');
                const listId = LISTA_TALLAS_POR_TIPO[section?.dataset?.tipoTalla] || 'tallas-sugeridas-list';
                tallaRow.innerHTML = `
                    <input type="text" name="talla_dama[${prendaIndex}][]" class="talla-input-uppercase" list="${listId}" placeholder="Talla">
                    <input type="text" name="color_dama[${prendaIndex}][]" class="color-input-uppercase" placeholder="Color (ej: ROJO)">
                    <input type="number" name="cantidad_dama[${prendaIndex}][]" placeholder="Cantidad" min="1">
                    <button type="button" class="eliminar-talla-btn">x</button>
                `;
                tallasDamaList.appendChild(tallaRow);
            });
        }

        if (addCabBtn && tallasCabList) {
            addCabBtn.addEventListener('click', function () {
                const tallaRow = document.createElement('div');
                const section = prendaCard.querySelector('[data-genero-section="caballero"]');
                const listId = LISTA_TALLAS_POR_TIPO[section?.dataset?.tipoTalla] || 'tallas-sugeridas-list';
                tallaRow.innerHTML = `
                    <input type="text" name="talla_caballero[${prendaIndex}][]" class="talla-input-uppercase" list="${listId}" placeholder="Talla">
                    <input type="text" name="color_caballero[${prendaIndex}][]" class="color-input-uppercase" placeholder="Color (ej: NEGRO)">
                    <input type="number" name="cantidad_caballero[${prendaIndex}][]" placeholder="Cantidad" min="1">
                    <button type="button" class="eliminar-talla-btn">x</button>
                `;
                tallasCabList.appendChild(tallaRow);
            });
        }

        if (addUniBtn && tallasUniList) {
            addUniBtn.addEventListener('click', function () {
                const tallaRow = document.createElement('div');
                const section = prendaCard.querySelector('[data-genero-section="unisex"]');
                const listId = LISTA_TALLAS_POR_TIPO[section?.dataset?.tipoTalla] || 'tallas-sugeridas-list';
                tallaRow.innerHTML = `
                    <input type="text" name="talla_unisex[${prendaIndex}][]" class="talla-input-uppercase" list="${listId}" placeholder="Talla">
                    <input type="text" name="color_unisex[${prendaIndex}][]" class="color-input-uppercase" placeholder="Color (ej: AZUL)">
                    <input type="number" name="cantidad_unisex[${prendaIndex}][]" placeholder="Cantidad" min="1">
                    <button type="button" class="eliminar-talla-btn">x</button>
                `;
                tallasUniList.appendChild(tallaRow);
            });
        }
    }

    prendasContainer.addEventListener('input', function (event) {
        const target = event.target;
        const isTextInput = target.matches('input[type="text"]');
        const isTextarea = target.matches('textarea');
        if (!isTextInput && !isTextarea) return;
        target.value = String(target.value || '').toUpperCase();
    });

    prendasContainer.addEventListener('change', async function (event) {
        const generoToggleInput = event.target.closest('.genero-check-input[data-genero-toggle]');
        if (generoToggleInput) {
            const prendaCard = generoToggleInput.closest('.prenda-card');
            if (!prendaCard) return;
            const genero = generoToggleInput.dataset.generoToggle;
            const section = prendaCard.querySelector(`[data-genero-section="${genero}"]`);
            if (!section) return;
            const label = generoToggleInput.closest('.genero-check');

            if (generoToggleInput.checked) {
                const seleccion = await abrirModalTipoTalla(genero);
                if (!seleccion || !seleccion.tipo || !Array.isArray(seleccion.tallas) || seleccion.tallas.length === 0) {
                    generoToggleInput.checked = false;
                    label?.classList.remove('is-active');
                    section.classList.add('is-hidden');
                    return;
                }
                setTipoTallaEnSeccion(section, seleccion.tipo);
                aplicarTallasSeleccionadas(prendaCard, genero, seleccion.tipo, seleccion.tallas);
                label?.classList.add('is-active');
                section.classList.remove('is-hidden');
            } else {
                label?.classList.remove('is-active');
                section.classList.add('is-hidden');
                section.dataset.tipoTalla = '';
            }

            return;
        }
    });

    prendasContainer.addEventListener('click', function (event) {
        const generoToggleInput = event.target.closest('.genero-check-input[data-genero-toggle]');
        if (generoToggleInput) return;

        if (!event.target.classList.contains('eliminar-talla-btn')) return;
        const tallasList = event.target.closest('.tallas-list');
        if (!tallasList) return;
        const rows = tallasList.querySelectorAll(':scope > div');
        if (rows.length > 1) event.target.closest('div')?.remove();
    });

    prendasContainer.querySelectorAll('.prenda-card').forEach(bindPrendaActions);

    tipoTallaModal.querySelectorAll('[data-tipo-talla-select]').forEach((btn) => {
        btn.addEventListener('click', function () {
            tipoTallaState.tipoSeleccionado = btn.dataset.tipoTallaSelect;
            tipoTallaState.tallasSeleccionadas = new Set();
            tipoTallaModal.querySelectorAll('[data-tipo-talla-select]').forEach((b) => {
                b.classList.toggle('btn-primary', b === btn);
                b.classList.toggle('btn-outline-primary', b !== btn);
            });
            renderTallasDisponibles(tipoTallaState.tipoSeleccionado);
            if (tipoTallaConfirmarBtn) tipoTallaConfirmarBtn.disabled = true;
        });
    });

    tipoTallaConfirmarBtn?.addEventListener('click', function () {
        if (!tipoTallaState.tipoSeleccionado || tipoTallaState.tallasSeleccionadas.size === 0) return;
        cerrarModalTipoTalla({
            tipo: tipoTallaState.tipoSeleccionado,
            tallas: Array.from(tipoTallaState.tallasSeleccionadas),
        });
    });

    tipoTallaCancelarBtn?.addEventListener('click', function () {
        cerrarModalTipoTalla(null);
    });

    tipoTallaModal.querySelector('.tipo-talla-modal__backdrop')?.addEventListener('click', function () {
        cerrarModalTipoTalla(null);
    });
});

document.addEventListener('DOMContentLoaded', function() {
    loadRecibosCorteForBodega();
});

function loadRecibosCorteForBodega() {
    fetch('/api/recibo-corte-bodega')
        .then(response => response.json())
        .then(data => {
            const tbody = document.getElementById('recibo-corte-bodega-tbody');
            tbody.innerHTML = '';

            if (data.success && data.data && data.data.length > 0) {
                data.data.forEach(prenda => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td style="text-align: center;">
                            <button type="button" class="btn btn-sm btn-primary" onclick="openReciboCorteBodegaModal(${prenda.id})" title="Ver recibo">
                                <i class="fas fa-eye"></i>
                            </button>
                        </td>
                        <td style="text-align: center;"><strong>${prenda.numero_recibo || '-'}</strong></td>
                        <td><strong>${prenda.nombre}</strong></td>
                        <td>${prenda.descripcion || '-'}</td>
                        <td style="text-align: center;">${prenda.cantidad_tallas}</td>
                        <td style="text-align: center;"><span class="badge bg-success">${prenda.total_cantidad}</span></td>
                        <td>${prenda.fecha_corta}</td>
                    `;
                    tbody.appendChild(row);
                });
            } else {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="7" class="text-center py-4">
                            <div class="alert alert-info mb-0">
                                <i class="fas fa-info-circle"></i> No hay recibos de corte para bodega registrados aún.
                            </div>
                        </td>
                    </tr>
                `;
            }
        })
        .catch(error => {
            console.error('Error cargando recibos:', error);
            const tbody = document.getElementById('recibo-corte-bodega-tbody');
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center py-4">
                        <div class="alert alert-danger mb-0">
                            Error al cargar los recibos de corte para bodega.
                        </div>
                    </td>
                </tr>
            `;
        });
}
</script>
@endpush
