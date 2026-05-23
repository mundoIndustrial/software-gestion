@extends('layouts.app')

@section('title', 'Recibos Bodega')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            @unless(auth()->user()?->hasRole('admin'))
                <div class="mb-3 d-flex justify-content-end">
                    <button type="button" id="openReciboBodegaModalBtn" class="btn btn-primary">
                        Registrar recibo
                    </button>
                </div>
            @endunless

            <div id="recibo-corte-bodega-container">
                <div class="table-scroll-container">
                    <table id="recibo-corte-bodega-table" class="table table-striped table-hover modern-table">
                        <thead class="table-header">
                            <tr>
                                <th style="width: 60px; text-align: center;">Acciones</th>
                                <th style="width: 140px; text-align: center;">Área</th>
                                <th style="width: 120px; text-align: center;">N&deg; Recibo</th>
                                <th style="width: 300px;">Descripción</th>
                                <th style="width: 120px; text-align: center;">Tallas</th>
                                <th style="width: 120px; text-align: center;">Cantidad Total</th>
                                <th style="width: 150px; text-align: center;">Fecha de creación</th>
                                <th style="width: 180px; text-align: center;">Encargado</th>
                            </tr>
                        </thead>
                        <tbody id="recibo-corte-bodega-tbody">
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">Cargando recibos...</td>
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
        <div class="tipo-talla-modal__header">
            <div>
                <h3 id="tipoTallaGeneroModalTitle">Configurar tallas</h3>
                <p id="tipoTallaGeneroModalText">Selecciona el tipo de talla y registra talla, color y cantidad.</p>
            </div>
            <div class="tipo-talla-modal__header-actions">
                <span id="tipoTallaGeneroBadge" class="tipo-talla-genero-badge">GÉNERO</span>
                <button type="button" id="tipoTallaCloseBtn" class="tipo-talla-close-btn" aria-label="Cerrar">×</button>
            </div>
        </div>

        <div id="tipoTallaWizardBar" class="tipo-talla-wizard-bar">
            <div class="tipo-talla-stepper" aria-hidden="true">
                <div id="tipoTallaStepModo" class="tipo-talla-step">
                    <span class="tipo-talla-step__dot">1</span>
                    <span class="tipo-talla-step__label">Modo</span>
                </div>
                <div id="tipoTallaStepTipo" class="tipo-talla-step">
                    <span class="tipo-talla-step__dot">2</span>
                    <span class="tipo-talla-step__label">Tipo</span>
                </div>
                <div id="tipoTallaStepCaptura" class="tipo-talla-step">
                    <span class="tipo-talla-step__dot">3</span>
                    <span class="tipo-talla-step__label">Tallas</span>
                </div>
            </div>
            <div class="tipo-talla-wizard-bar__tools">
                <span id="tipoTallaWizardSummary" class="tipo-talla-summary-pill"></span>
                <button type="button" id="tipoTallaBackBtn" class="btn btn-light btn-sm" style="display:none;">Volver</button>
            </div>
        </div>

        <div id="tipoTallaModoActions" class="tipo-talla-modal__actions tipo-talla-modal__actions--wizard">
            <button type="button" class="btn btn-primary tipo-talla-select-card" data-modo-carga-select="normal">
                <span class="tipo-talla-select-card__title">Normal</span>
            </button>
            <button type="button" class="btn btn-outline-primary tipo-talla-select-card" data-modo-carga-select="color">
                <span class="tipo-talla-select-card__title">Por color</span>
            </button>
            <button type="button" class="btn btn-outline-primary tipo-talla-select-card" data-modo-carga-select="cantidad">
                <span class="tipo-talla-select-card__title">Cantidad nada más</span>
            </button>
        </div>
        <div id="tipoTallaTipoActions" class="tipo-talla-modal__actions">
            <button type="button" class="btn btn-primary tipo-talla-select-card" data-tipo-talla-select="letra">
                <span class="tipo-talla-select-card__title">Por letra</span>
            </button>
            <button type="button" class="btn btn-outline-primary tipo-talla-select-card" data-tipo-talla-select="numero">
                <span class="tipo-talla-select-card__title">Por número</span>
            </button>
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

<div id="rcbSuccessModal" style="display:none; position: fixed; inset: 0; background: rgba(15,23,42,.45); z-index: 10000300; align-items: center; justify-content: center;">
    <div style="background:#fff; width:min(92vw,420px); border-radius:14px; padding:18px; box-shadow:0 18px 40px rgba(15,23,42,.25);">
        <h3 id="rcbSuccessModalTitle" style="margin:0 0 8px 0; font-size:18px; font-weight:700; color:#0f172a;">Recibo registrado</h3>
        <p id="rcbSuccessModalMessage" style="margin:0 0 14px 0; color:#334155; font-size:14px;">El recibo se guardó correctamente.</p>
        <div style="display:flex; justify-content:flex-end;">
            <button type="button" id="rcbSuccessModalOkBtn" class="btn btn-primary">Aceptar</button>
        </div>
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
#recibo-corte-bodega-table td:nth-child(3) { width: 440px; }
#recibo-corte-bodega-table th:nth-child(4),
#recibo-corte-bodega-table td:nth-child(4) { width: 120px; text-align: center; }
#recibo-corte-bodega-table th:nth-child(5),
#recibo-corte-bodega-table td:nth-child(5) { width: 140px; text-align: center; }
#recibo-corte-bodega-table th:nth-child(6),
#recibo-corte-bodega-table td:nth-child(6) { width: 150px; text-align: center; white-space: nowrap; }
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

tr.recibo-atrasado-row > td {
    background-color: #fde8e8 !important;
    color: #7f1d1d;
    font-weight: 600;
}

tr.recibo-atrasado-row:hover > td {
    background-color: #fecaca !important;
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
    font-family: "Inter", "Segoe UI", "Helvetica Neue", Arial, sans-serif;
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
    width: 100vw;
    height: 100vh;
    margin: 0;
    max-height: 100vh;
    overflow-y: auto;
    background: #fff;
    border-radius: 0;
    padding: 22px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 22px 60px rgba(15, 23, 42, 0.22);
    display: grid;
    grid-template-rows: auto auto auto auto 1fr auto;
    gap: 14px;
}

.tipo-talla-modal__header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 14px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eef2f7;
}

.tipo-talla-modal__header-actions {
    display: flex;
    align-items: center;
    gap: 10px;
}

#tipoTallaGeneroModalTitle {
    margin: 0 0 6px 0;
    font-size: 1.35rem;
    font-weight: 800;
    letter-spacing: -.02em;
    color: #0f172a;
}

#tipoTallaGeneroModalText {
    margin: 0;
    color: #64748b;
    font-size: .92rem;
}

.tipo-talla-genero-badge {
    background: #eff6ff;
    color: #1d4ed8;
    border: 1px solid #bfdbfe;
    border-radius: 999px;
    font-size: .72rem;
    font-weight: 800;
    letter-spacing: .08em;
    text-transform: uppercase;
    padding: 7px 11px;
    white-space: nowrap;
}

.tipo-talla-close-btn {
    width: 34px;
    height: 34px;
    border-radius: 10px;
    border: 1px solid #d1d5db;
    background: #fff;
    color: #475569;
    font-size: 20px;
    line-height: 1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0;
    transition: all .18s ease;
}

.tipo-talla-close-btn:hover {
    border-color: #94a3b8;
    color: #0f172a;
    background: #f8fafc;
}

.tipo-talla-modal__actions {
    display: grid !important;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10px;
    margin-bottom: 0;
}

.tipo-talla-modal__actions--wizard {
    grid-template-columns: repeat(3, minmax(0, 1fr));
}

.tipo-talla-wizard-bar {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 14px;
}

.tipo-talla-wizard-bar__tools {
    display: flex;
    align-items: center;
    gap: 8px;
}

.tipo-talla-summary-pill {
    display: inline-flex;
    align-items: center;
    border-radius: 999px;
    border: 1px solid #cbd5e1;
    color: #475569;
    font-size: .73rem;
    font-weight: 700;
    padding: 6px 10px;
    background: #fff;
    min-height: 30px;
}

.tipo-talla-stepper {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 8px;
    flex: 1;
}

.tipo-talla-step {
    border: 1px solid #e2e8f0;
    background: #f8fafc;
    border-radius: 12px;
    padding: 8px 10px;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all .22s ease;
}

.tipo-talla-step__dot {
    width: 22px;
    height: 22px;
    border-radius: 999px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: .72rem;
    font-weight: 700;
    border: 1px solid #cbd5e1;
    color: #475569;
    background: #fff;
}

.tipo-talla-step__label {
    font-size: .76rem;
    font-weight: 700;
    color: #64748b;
    letter-spacing: .01em;
}

.tipo-talla-step.is-active {
    border-color: #93c5fd;
    background: #eff6ff;
}

.tipo-talla-step.is-active .tipo-talla-step__dot {
    border-color: #3b82f6;
    color: #fff;
    background: #3b82f6;
}

.tipo-talla-step.is-active .tipo-talla-step__label {
    color: #1e3a8a;
}

.tipo-talla-step.is-completed {
    border-color: #bbf7d0;
    background: #f0fdf4;
}

.tipo-talla-step.is-completed .tipo-talla-step__dot {
    border-color: #10b981;
    color: #fff;
    background: #10b981;
}

.tipo-talla-step.is-completed .tipo-talla-step__label {
    color: #065f46;
}

#tipoTallaBackBtn {
    border: 1px solid #d1d5db;
    background: #fff;
    color: #334155;
    font-weight: 700;
    border-radius: 10px;
    padding: 6px 12px;
    transition: all .2s ease;
}

#tipoTallaBackBtn:hover {
    border-color: #94a3b8;
    color: #0f172a;
}

#tipoTallaBackBtn:focus {
    outline: none;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
}

.tipo-talla-grid {
    display: grid;
    gap: 12px;
    margin-bottom: 0;
    background: #fcfdff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 14px;
    overflow: visible;
    max-height: none;
}

.tipo-talla-color-groups {
    display: grid;
    gap: 12px;
}

.tipo-talla-color-block {
    border: 1px solid #dbeafe;
    border-radius: 12px;
    background: #f8fbff;
    padding: 12px;
    display: grid;
    gap: 10px;
    transition: all .2s ease;
}

.tipo-talla-color-block.is-active {
    border-color: #60a5fa;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, .14);
    transform: translateY(-1px);
}

.tipo-talla-color-head {
    display: grid;
    grid-template-columns: 1fr 34px;
    gap: 8px;
}

.tipo-talla-color-head input {
    border: 1px solid #cbd5e1;
    border-radius: 10px;
    padding: 10px 11px;
    font-size: 13px;
    text-transform: uppercase;
    background: #fff;
    color: #0f172a;
    transition: all .18s ease;
}

.tipo-talla-color-head input:focus {
    outline: none;
    border-color: #60a5fa;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, .18);
}

.tipo-talla-remove-color-block {
    border: 1px solid #fecaca;
    border-radius: 10px;
    background: #fff5f5;
    color: #ef4444;
    font-weight: 700;
    transition: all .18s ease;
}

.tipo-talla-remove-color-block:hover {
    background: #fee2e2;
    color: #b91c1c;
}

.tipo-talla-empty {
    border: 1px dashed #cbd5e1;
    background: #fff;
    color: #475569;
    border-radius: 12px;
    padding: 10px 12px;
    font-size: 12px;
}

.tipo-talla-pills {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.tipo-talla-pill {
    border: 1px solid #d1d5db;
    background: #fff;
    color: #1f2937;
    border-radius: 10px;
    padding: 10px 16px;
    font-weight: 700;
    font-size: .9rem;
    min-height: 40px;
    min-width: 68px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    line-height: 1;
    cursor: pointer;
    transition: all .2s ease;
}

.tipo-talla-pill:hover {
    border-color: #93c5fd;
    background: #eff6ff;
    color: #1d4ed8;
}

.tipo-talla-pill.is-picked {
    border-color: #60a5fa;
    background: #dbeafe;
    color: #1e3a8a;
}

.tipo-talla-pill.is-selected {
    border-color: #3b82f6;
    background: #dbeafe;
    color: #1e3a8a;
    box-shadow: inset 0 0 0 1px rgba(59, 130, 246, .25);
}

.tipo-talla-row {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 120px 34px;
    gap: 8px;
    align-items: center;
}

.tipo-talla-row.is-normal {
    grid-template-columns: minmax(0, 1fr) 120px 34px;
}

.tipo-talla-row.is-color {
    grid-template-columns: minmax(0, 1fr) 120px 34px;
}

.tipo-talla-row input {
    border: 1px solid #cbd5e1;
    border-radius: 10px;
    padding: 10px 11px;
    font-size: 13px;
    color: #0f172a;
    background: #fff;
    transition: all .18s ease;
}

.tipo-talla-row input:focus {
    outline: none;
    border-color: #60a5fa;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, .18);
}

.tipo-talla-remove {
    width: 34px;
    height: 34px;
    border: 1px solid #fecaca;
    border-radius: 10px;
    background: #fff5f5;
    color: #ef4444;
    font-weight: 700;
    line-height: 1;
    transition: all .18s ease;
}

.tipo-talla-remove:hover {
    background: #fee2e2;
    color: #b91c1c;
}

.tipo-talla-add-btn {
    width: fit-content;
    border: 1px solid #bfdbfe;
    color: #1d4ed8;
    background: #eff6ff;
    border-radius: 10px;
    font-weight: 700;
    padding: 9px 13px;
    transition: all .2s ease;
}

.tipo-talla-add-btn:hover {
    background: #dbeafe;
    border-color: #93c5fd;
}

.tipo-talla-modal__footer {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
    border-top: 1px solid #eef2f7;
    padding-top: 14px;
    flex-wrap: nowrap;
    width: 100%;
    max-width: 100%;
    align-items: center;
}

.tipo-talla-modal__footer .btn {
    width: auto !important;
    max-width: none !important;
    flex: 0 0 auto !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    white-space: nowrap;
}

#cancelarTipoTallaGeneroBtn,
#confirmarTipoTallaGeneroBtn,
.tipo-talla-add-talla-color-btn {
    border-radius: 10px;
    padding: 8px 16px;
    font-weight: 600;
    font-size: .90rem;
    line-height: 1.2;
    transition: all .2s ease;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
}

#cancelarTipoTallaGeneroBtn {
    border: 1px solid #d1d5db;
    color: #334155;
    background: #fff;
    width: auto !important;
    min-width: 100px !important;
    max-width: 140px !important;
    flex: 0 0 auto !important;
    padding: 8px 16px !important;
}

#confirmarTipoTallaGeneroBtn {
    border: 1px solid #2563eb;
    background: #2563eb;
    color: #fff;
    box-shadow: 0 8px 20px rgba(37, 99, 235, .22);
    width: auto !important;
    min-width: 100px !important;
    max-width: 140px !important;
    flex: 0 0 auto !important;
    padding: 8px 16px !important;
}

#confirmarTipoTallaGeneroBtn:disabled {
    opacity: .55;
    box-shadow: none;
    cursor: not-allowed;
}

.tipo-talla-add-talla-color-btn {
    border: 1px solid #cbd5e1;
    color: #334155;
    background: #fff;
    width: fit-content;
    padding: 8px 11px;
    font-size: .78rem;
}

.tipo-talla-select-card {
    text-align: left;
    border-radius: 12px;
    border: 1px solid #dbe2ea;
    background: #fff;
    color: #0f172a;
    padding: 12px;
    transition: all .2s ease;
    display: grid;
    gap: 4px;
    min-height: 74px;
}

.tipo-talla-select-card:hover {
    border-color: #93c5fd;
    background: #f8fbff;
    transform: translateY(-1px);
}

.tipo-talla-select-card__title {
    font-weight: 800;
    font-size: .88rem;
}

.tipo-talla-select-card__desc {
    font-size: .77rem;
    color: #64748b;
    font-weight: 500;
}

.tipo-talla-select-card.btn-primary {
    border-color: #60a5fa;
    background: linear-gradient(180deg, #eff6ff 0%, #e0ecff 100%);
    color: #1e3a8a;
    box-shadow: 0 8px 20px rgba(59, 130, 246, .15);
}

.tipo-talla-modal .tipo-talla-select-card.btn-primary:hover,
.tipo-talla-modal .tipo-talla-select-card.btn-primary:focus,
.tipo-talla-modal .tipo-talla-select-card.btn-primary:active {
    border-color: #3b82f6 !important;
    background: linear-gradient(180deg, #dbeafe 0%, #bfdbfe 100%) !important;
    color: #1e3a8a !important;
}

.tipo-talla-modal .tipo-talla-select-card.btn-outline-primary:hover,
.tipo-talla-modal .tipo-talla-select-card.btn-outline-primary:focus {
    border-color: #93c5fd !important;
    background: #eff6ff !important;
    color: #1d4ed8 !important;
}

.tipo-talla-modal__dialog::-webkit-scrollbar,
.tipo-talla-grid::-webkit-scrollbar {
    width: 10px;
}

.tipo-talla-modal__dialog::-webkit-scrollbar-thumb,
.tipo-talla-grid::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 999px;
}

@media (max-width: 768px) {
    .tipo-talla-modal__dialog {
        max-height: 100vh;
        border-radius: 0;
    }

    .tipo-talla-modal__header {
        flex-direction: column;
        align-items: flex-start;
    }

    .tipo-talla-wizard-bar {
        flex-direction: column;
    }

    .tipo-talla-wizard-bar__tools {
        width: 100%;
        justify-content: space-between;
    }

    .tipo-talla-modal__actions--wizard,
    .tipo-talla-modal__actions {
        grid-template-columns: 1fr !important;
    }

    .tipo-talla-row,
    .tipo-talla-row.is-normal,
    .tipo-talla-row.is-color {
        grid-template-columns: 1fr 96px 32px;
    }
}

/* UX simple para usuarios operativos */
.tipo-talla-modal__dialog {
    border-radius: 16px;
    box-shadow: 0 12px 28px rgba(15, 23, 42, 0.14);
    gap: 12px;
}

.tipo-talla-stepper {
    display: none;
}

.tipo-talla-wizard-bar {
    justify-content: flex-end;
}

.tipo-talla-summary-pill {
    border: 0;
    background: #f1f5f9;
    color: #334155;
}

.tipo-talla-modal__actions--wizard,
.tipo-talla-modal__actions {
    gap: 12px;
}

.tipo-talla-select-card {
    min-height: 58px;
    border-radius: 12px;
    padding: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.tipo-talla-select-card__title {
    font-size: .98rem;
}

.tipo-talla-select-card__desc {
    display: none;
}

.tipo-talla-grid {
    border-radius: 12px;
    background: #f8fafc;
    border: 1px solid #e5e7eb;
    padding: 12px;
}

.tipo-talla-color-block {
    background: #fff;
    border: 1px solid #dbe2ea;
    border-radius: 12px;
    box-shadow: none;
}

.tipo-talla-color-block.is-active {
    box-shadow: none;
    transform: none;
    border-color: #93c5fd;
}

.tipo-talla-block-pills {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.tipo-talla-pill--in-block {
    background: #f8fafc;
}

.tipo-talla-pill--in-block:hover {
    background: #e2e8f0;
    border-color: #94a3b8;
    color: #0f172a;
}

.tipo-talla-row {
    grid-template-columns: minmax(0, 1fr) 120px 34px;
}

.tipo-talla-row .modal-talla-input.is-readonly {
    background: #f1f5f9;
    color: #334155;
    font-weight: 700;
    border-color: #dbe2ea;
}

.tipo-talla-row input,
.tipo-talla-color-head input {
    background: #f8fafc;
    border: 1px solid #dbe2ea;
    box-shadow: none;
}

.tipo-talla-row input:focus,
.tipo-talla-color-head input:focus {
    background: #fff;
    border-color: #93c5fd;
    box-shadow: 0 0 0 2px rgba(147, 197, 253, .35);
}

.tipo-talla-add-btn {
    background: #fff;
    color: #1e40af;
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

#reciboBodegaCreateModal .tallas-subsection.is-sin-color .tallas-head {
    grid-template-columns: 1fr 1fr 32px;
}

#reciboBodegaCreateModal .tallas-subsection.is-sin-color .tallas-head span:nth-child(2) {
    display: none;
}

#reciboBodegaCreateModal .tallas-subsection.is-sin-color .tallas-list > div {
    grid-template-columns: 1fr 1fr 32px;
}

#reciboBodegaCreateModal .tallas-subsection.is-cantidad-solo .tallas-head {
    grid-template-columns: 1fr 32px;
}

#reciboBodegaCreateModal .tallas-subsection.is-cantidad-solo .tallas-head span:nth-child(1),
#reciboBodegaCreateModal .tallas-subsection.is-cantidad-solo .tallas-head span:nth-child(2) {
    display: none;
}

#reciboBodegaCreateModal .tallas-subsection.is-cantidad-solo .tallas-list > div {
    grid-template-columns: 1fr 32px;
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

/* Override específico para botones del modal de tallas */
.tipo-talla-modal__footer #cancelarTipoTallaGeneroBtn,
.tipo-talla-modal__footer #confirmarTipoTallaGeneroBtn {
    width: auto !important;
    min-width: 100px !important;
    max-width: 140px !important;
    flex: 0 0 auto !important;
    padding: 8px 16px !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    font-size: 0.9rem !important;
    line-height: 1.2 !important;
}

.tipo-talla-modal__footer #cancelarTipoTallaGeneroBtn {
    background: #fff !important;
    color: #334155 !important;
    border: 1px solid #d1d5db !important;
}

.tipo-talla-modal__footer #confirmarTipoTallaGeneroBtn {
    background: #2563eb !important;
    color: #fff !important;
    border: 1px solid #2563eb !important;
}

.tipo-talla-modal__footer #confirmarTipoTallaGeneroBtn:disabled {
    opacity: 0.55 !important;
}
</style>
@endpush

@push('scripts')
<script>
const isAdminBodega = @json((bool) (auth()->user()?->hasRole('admin')));

document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('reciboBodegaCreateModal');
    const openBtn = document.getElementById('openReciboBodegaModalBtn');
    const prendasContainer = document.getElementById('prendasContainer');
    const form = document.getElementById('reciboBodegaCreateForm');
    const tipoTallaModal = document.getElementById('tipoTallaGeneroModal');
    const tipoTallaModalText = document.getElementById('tipoTallaGeneroModalText');
    const tipoTallaCloseBtn = document.getElementById('tipoTallaCloseBtn');
    const tipoTallaGeneroBadge = document.getElementById('tipoTallaGeneroBadge');
    const tipoTallaWizardSummary = document.getElementById('tipoTallaWizardSummary');
    const tipoTallaBackBtn = document.getElementById('tipoTallaBackBtn');
    const tipoTallaStepModo = document.getElementById('tipoTallaStepModo');
    const tipoTallaStepTipo = document.getElementById('tipoTallaStepTipo');
    const tipoTallaStepCaptura = document.getElementById('tipoTallaStepCaptura');
    const tipoTallaModoActions = document.getElementById('tipoTallaModoActions');
    const tipoTallaTipoActions = document.getElementById('tipoTallaTipoActions');
    const tipoTallaCancelarBtn = document.getElementById('cancelarTipoTallaGeneroBtn');
    const tipoTallaConfirmarBtn = document.getElementById('confirmarTipoTallaGeneroBtn');
    const tipoTallaGrid = document.getElementById('tipoTallaGeneroGrid');
    const rcbSuccessModal = document.getElementById('rcbSuccessModal');
    const rcbSuccessModalTitle = document.getElementById('rcbSuccessModalTitle');
    const rcbSuccessModalMessage = document.getElementById('rcbSuccessModalMessage');
    const rcbSuccessModalOkBtn = document.getElementById('rcbSuccessModalOkBtn');
    const addProcesoModal = document.getElementById('addProcesoModal');
    const addProcesoOverlay = document.getElementById('addProcesoOverlay');
    const closeAddProcesoBtn = document.getElementById('closeAddProcesoModal');
    const cancelAddProcesoBtn = document.getElementById('btnCancelAddProceso');
    const confirmAddProcesoBtn = document.getElementById('btnConfirmAddProceso');
    const procesoAreaSelect = document.getElementById('procesoArea');
    const procesoEncargadoInput = document.getElementById('procesoEncargado');
    const procesoEncargadoGroup = procesoEncargadoInput?.closest('.add-proceso-form-group') || null;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    let adminBadgeContext = null;

    if (!modal || !prendasContainer || !form || !tipoTallaModal || !tipoTallaModalText) return;

    function showReciboSuccessModal() {
        if (!rcbSuccessModal) return;
        if (rcbSuccessModalTitle) rcbSuccessModalTitle.textContent = 'Recibo registrado';
        if (rcbSuccessModalMessage) rcbSuccessModalMessage.textContent = 'El recibo se guardó correctamente.';
        rcbSuccessModal.style.display = 'flex';
    }

    function showFeedbackModal(title, message) {
        if (!rcbSuccessModal) return;
        if (rcbSuccessModalTitle) rcbSuccessModalTitle.textContent = String(title || 'Mensaje');
        if (rcbSuccessModalMessage) rcbSuccessModalMessage.textContent = String(message || '');
        rcbSuccessModal.style.display = 'flex';
    }

    function hideReciboSuccessModal() {
        if (!rcbSuccessModal) return;
        rcbSuccessModal.style.display = 'none';
    }

    rcbSuccessModalOkBtn?.addEventListener('click', hideReciboSuccessModal);
    rcbSuccessModal?.addEventListener('click', function (event) {
        if (event.target === rcbSuccessModal) {
            hideReciboSuccessModal();
        }
    });

    function closeAdminAddProcesoModal() {
        if (!addProcesoModal) return;
        addProcesoModal.classList.remove('show');
        addProcesoModal.style.display = 'none';
        if (procesoAreaSelect) procesoAreaSelect.value = '';
        if (procesoEncargadoInput) procesoEncargadoInput.value = '';
        adminBadgeContext = null;
    }

    function openAdminAddProcesoModal(context) {
        if (!addProcesoModal) return;
        adminBadgeContext = context;
        if (procesoAreaSelect) {
            procesoAreaSelect.value = 'Corte';
            procesoAreaSelect.setAttribute('disabled', 'disabled');
        }
        ensureEncargadoSelectForCorte().then(() => preselectEncargadoFromProceso(context));
        if (procesoEncargadoInput) procesoEncargadoInput.value = '';
        addProcesoModal.style.display = 'flex';
        addProcesoModal.classList.add('show');
    }

    async function ensureEncargadoSelectForCorte() {
        if (!procesoEncargadoGroup) return;
        procesoEncargadoGroup.style.display = 'block';

        let select = document.getElementById('procesoEncargadoSelect');
        if (!select) {
            select = document.createElement('select');
            select.id = 'procesoEncargadoSelect';
            select.className = 'add-proceso-select';
            procesoEncargadoGroup.appendChild(select);
        }

        if (procesoEncargadoInput) {
            procesoEncargadoInput.style.display = 'none';
        }

        select.innerHTML = '<option value="">Cargando encargados...</option>';
        try {
            const resp = await fetch('/api/areas/corte/encargados', {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
            const data = await resp.json().catch(() => ({}));
            const usuarios = Array.isArray(data?.encargados) ? data.encargados : [];

            select.innerHTML = '<option value="">Seleccionar encargado...</option>';
            usuarios.forEach((u) => {
                const opt = document.createElement('option');
                opt.value = String(u?.id ?? '');
                opt.textContent = String(u?.nombre || u?.name || '').toUpperCase();
                select.appendChild(opt);
            });
        } catch (e) {
            select.innerHTML = '<option value="">No se pudieron cargar encargados</option>';
        }
    }

    async function preselectEncargadoFromProceso(context) {
        const select = document.getElementById('procesoEncargadoSelect');
        if (!select) return;

        const numeroRecibo = Number(context?.numero_recibo || 0);
        const prendaBodegaId = Number(context?.prenda_bodega_id || 0);
        if (!numeroRecibo) return;

        try {
            const qs = prendaBodegaId > 0 ? `?prenda_bodega_id=${encodeURIComponent(String(prendaBodegaId))}` : '';
            const resp = await fetch(`/api/recibos-bodega/${numeroRecibo}/procesos${qs}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
            const procesos = await resp.json().catch(() => []);
            if (!resp.ok || !Array.isArray(procesos) || procesos.length === 0) return;

            const procesoCorte = procesos.find((p) => String(p?.proceso || '').toLowerCase().includes('corte')) || procesos[0];
            const encargadoActual = String(procesoCorte?.encargado || '').trim().toUpperCase();
            if (!encargadoActual) return;

            let matched = false;
            for (const option of select.options) {
                if (String(option.textContent || '').trim().toUpperCase() === encargadoActual) {
                    select.value = option.value;
                    matched = true;
                    break;
                }
            }

            if (!matched && /^\d+$/.test(encargadoActual)) {
                const byId = Array.from(select.options).find((o) => String(o.value) === encargadoActual);
                if (byId) {
                    select.value = byId.value;
                }
            }
        } catch (e) {
            // No bloquear apertura del modal.
        }
    }

    async function saveAdminAddProceso() {
        if (!isAdminBodega || !adminBadgeContext) return;

        const area = 'Corte';
        const selectEncargado = document.getElementById('procesoEncargadoSelect');
        const encargado = selectEncargado
            ? String(selectEncargado.options[selectEncargado.selectedIndex]?.text || '').trim().toUpperCase()
            : (procesoEncargadoInput?.value || '').trim().toUpperCase();

        if (!area) {
            showFeedbackModal('Validación', 'Selecciona un área para continuar.');
            return;
        }

        if (!encargado) {
            showFeedbackModal('Validación', 'Ingresa el encargado para continuar.');
            return;
        }

        if (confirmAddProcesoBtn) confirmAddProcesoBtn.disabled = true;
        try {
            const numeroRecibo = Number(adminBadgeContext.numero_recibo || 0);
            const prendaBodegaId = Number(adminBadgeContext.prenda_bodega_id || 0);
            if (!numeroRecibo) {
                throw new Error('No se encontró número de recibo para actualizar el proceso.');
            }

            const qs = prendaBodegaId > 0 ? `?prenda_bodega_id=${encodeURIComponent(String(prendaBodegaId))}` : '';
            const procesosResp = await fetch(`/api/recibos-bodega/${numeroRecibo}/procesos${qs}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            const procesos = await procesosResp.json().catch(() => []);
            if (!procesosResp.ok || !Array.isArray(procesos)) {
                throw new Error('No se pudieron consultar procesos del recibo en bodega.');
            }

            const procesoCorte = procesos.find((p) => String(p?.proceso || '').toLowerCase().includes('corte'))
                || procesos[0];
            if (!procesoCorte?.id) {
                throw new Error('No existe un proceso para este recibo en bodega.');
            }

            const editarResp = await fetch(`/api/recibos-bodega/procesos/${procesoCorte.id}/encargado`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    encargado,
                }),
            });

            const editarJson = await editarResp.json().catch(() => ({}));
            if (!editarResp.ok || editarJson?.success === false) {
                throw new Error(editarJson?.message || 'No se pudo actualizar el proceso de bodega.');
            }

            closeAdminAddProcesoModal();
            loadRecibosCorteForBodega();
            showFeedbackModal('Proceso actualizado', `Encargado asignado correctamente: ${encargado}`);
        } catch (error) {
            showFeedbackModal('Error', error.message || 'Error guardando proceso');
        } finally {
            if (confirmAddProcesoBtn) confirmAddProcesoBtn.disabled = false;
        }
    }

    if (addProcesoOverlay) addProcesoOverlay.addEventListener('click', closeAdminAddProcesoModal);
    if (closeAddProcesoBtn) closeAddProcesoBtn.addEventListener('click', closeAdminAddProcesoModal);
    if (cancelAddProcesoBtn) cancelAddProcesoBtn.addEventListener('click', closeAdminAddProcesoModal);
    if (confirmAddProcesoBtn) confirmAddProcesoBtn.addEventListener('click', saveAdminAddProceso);

    window.openAddProcesoFromBodegaBadge = function (payload) {
        if (!isAdminBodega) return;
        const data = payload || {};
        openAdminAddProcesoModal(data);
    };

    // Compatibilidad: mismo entrypoint usado en recibos-costura
    window.abrirModalAgregarProcesoDesdeArea = function (areaSeleccionada, pedidoId, prendaId, numeroRecibo) {
        window.openAddProcesoFromBodegaBadge({
            area: areaSeleccionada || 'Corte',
            pedido_produccion_id: pedidoId || null,
            prenda_id: prendaId || null,
            numero_recibo: numeroRecibo || null,
        });
    };

    const tipoTallaState = {
        isOpen: false,
        resolve: null,
        genero: null,
        tipoSeleccionado: null,
        modoCarga: 'normal',
        etapa: 'modo',
        detallesSeleccionados: [],
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

    function crearFilaDetalleModal(tipo, modo = 'normal', detalle = {}) {
        const row = document.createElement('div');
        row.className = `tipo-talla-row ${modo === 'normal' ? 'is-normal' : ''}`;
        const datalistId = LISTA_TALLAS_POR_TIPO[tipo] || 'tallas-sugeridas-list';
        if (modo === 'cantidad') {
            row.classList.add('is-normal');
            row.innerHTML = `
                <input type="number" class="modal-cantidad-input" min="1" placeholder="Cantidad" value="${detalle.cantidad || ''}">
                <button type="button" class="tipo-talla-remove">x</button>
            `;
        } else if (modo === 'normal') {
            row.innerHTML = `
                <input type="text" class="modal-talla-input" list="${datalistId}" placeholder="Talla" value="${detalle.talla || ''}">
                <input type="number" class="modal-cantidad-input" min="1" placeholder="Cantidad" value="${detalle.cantidad || ''}">
                <button type="button" class="tipo-talla-remove">x</button>
            `;
        } else {
            row.classList.add('is-color');
            row.innerHTML = `
                <input type="text" class="modal-talla-input" list="${datalistId}" placeholder="Talla" value="${detalle.talla || ''}">
                <input type="number" class="modal-cantidad-input" min="1" placeholder="Cantidad" value="${detalle.cantidad || ''}">
                <button type="button" class="tipo-talla-remove">x</button>
            `;
        }
        return row;
    }

    function textoModoSeleccionado() {
        if (tipoTallaState.modoCarga === 'cantidad') return 'Cantidad nada más';
        if (tipoTallaState.modoCarga === 'color') return 'Talla por color';
        return 'Normal';
    }

    function textoTipoSeleccionado() {
        return tipoTallaState.tipoSeleccionado === 'numero' ? 'Por número' : 'Por letra';
    }

    function setStepVisual(stepEl, isActive, isCompleted) {
        if (!stepEl) return;
        stepEl.classList.toggle('is-active', !!isActive);
        stepEl.classList.toggle('is-completed', !!isCompleted);
    }

    function actualizarUIWizard() {
        const etapa = tipoTallaState.etapa;
        if (tipoTallaModoActions) tipoTallaModoActions.style.display = etapa === 'modo' ? '' : 'none';
        if (tipoTallaTipoActions) tipoTallaTipoActions.style.display = etapa === 'tipo' ? '' : 'none';
        if (tipoTallaBackBtn) tipoTallaBackBtn.style.display = etapa === 'modo' ? 'none' : '';
        if (tipoTallaGrid) tipoTallaGrid.style.display = etapa === 'captura' ? '' : 'none';
        setStepVisual(tipoTallaStepModo, etapa === 'modo', etapa === 'tipo' || etapa === 'captura');
        setStepVisual(tipoTallaStepTipo, etapa === 'tipo', etapa === 'captura');
        setStepVisual(tipoTallaStepCaptura, etapa === 'captura', false);
        if (tipoTallaWizardSummary) {
            tipoTallaWizardSummary.textContent = etapa === 'captura'
                ? `${textoModoSeleccionado()} · ${textoTipoSeleccionado()}`
                : etapa === 'tipo'
                    ? `${textoModoSeleccionado()}`
                    : 'Selecciona un modo';
        }
        if (tipoTallaConfirmarBtn) {
            tipoTallaConfirmarBtn.disabled = etapa !== 'captura';
        }
    }

    function actualizarConfirmarModal() {
        if (!tipoTallaConfirmarBtn) return;
        if (!tipoTallaState.tipoSeleccionado) {
            tipoTallaConfirmarBtn.disabled = true;
            return;
        }
        const rows = tipoTallaGrid?.querySelectorAll('.tipo-talla-row') || [];
        let tieneAlMenosUnaValida = false;
        rows.forEach((row) => {
            const talla = (row.querySelector('.modal-talla-input')?.value || '').trim();
            const cantidad = parseInt(row.querySelector('.modal-cantidad-input')?.value || '0', 10);
            let esValida = tipoTallaState.modoCarga === 'normal'
                ? (talla !== '' && cantidad > 0)
                : tipoTallaState.modoCarga === 'cantidad'
                    ? (cantidad > 0)
                : false;
            if (tipoTallaState.modoCarga === 'color') {
                esValida = false;
            }
            if (esValida) {
                tieneAlMenosUnaValida = true;
            }
        });
        if (!tieneAlMenosUnaValida && tipoTallaState.modoCarga === 'color') {
            const blocks = tipoTallaGrid?.querySelectorAll('.tipo-talla-color-block') || [];
            blocks.forEach((block) => {
                const color = (block.querySelector('.modal-color-grupo-input')?.value || '').trim();
                const colorRows = block.querySelectorAll('.tipo-talla-row');
                colorRows.forEach((row) => {
                    const talla = (row.querySelector('.modal-talla-input')?.value || '').trim();
                    const cantidad = parseInt(row.querySelector('.modal-cantidad-input')?.value || '0', 10);
                    if (color !== '' && talla !== '' && cantidad > 0) {
                        tieneAlMenosUnaValida = true;
                    }
                });
            });
        }
        tipoTallaConfirmarBtn.disabled = !tieneAlMenosUnaValida;
    }

    function crearBloqueColorModal(tipo, color = '', detalles = []) {
        const block = document.createElement('div');
        block.className = 'tipo-talla-color-block';
        const tallasPillsHtml = (TALLAS_POR_TIPO[tipo] || []).map((talla) => {
            return `<button type="button" class="tipo-talla-pill tipo-talla-pill--in-block" data-color-block-pill="${talla}">${talla}</button>`;
        }).join('');
        block.innerHTML = `
            <div class="tipo-talla-color-head">
                <input type="text" class="modal-color-grupo-input" placeholder="Color (ej: AZUL REY)" value="${color}">
                <button type="button" class="tipo-talla-remove-color-block">x</button>
            </div>
            <div class="tipo-talla-block-pills">${tallasPillsHtml}</div>
            <div class="tipo-talla-color-rows"></div>
        `;
        const rowsWrap = block.querySelector('.tipo-talla-color-rows');
        const items = Array.isArray(detalles) && detalles.length > 0 ? detalles : [{}];
        items.forEach((detalle) => {
            if ((detalle?.talla || '').trim() !== '') {
                const row = crearFilaDetalleModal(tipo, 'color', detalle);
                const tallaInput = row.querySelector('.modal-talla-input');
                if (tallaInput) {
                    tallaInput.readOnly = true;
                    tallaInput.classList.add('is-readonly');
                }
                rowsWrap?.appendChild(row);
            }
        });
        const selected = new Set(
            Array.from(rowsWrap?.querySelectorAll('.modal-talla-input') || [])
                .map((input) => String(input.value || '').trim().toUpperCase())
                .filter(Boolean)
        );
        block.querySelectorAll('.tipo-talla-pill--in-block[data-color-block-pill]').forEach((pill) => {
            const talla = String(pill.dataset.colorBlockPill || '').trim().toUpperCase();
            pill.classList.toggle('is-selected', selected.has(talla));
        });
        return block;
    }

    function obtenerBloqueColorActivo() {
        const active = tipoTallaGrid?.querySelector('.tipo-talla-color-block.is-active');
        if (active) return active;
        return tipoTallaGrid?.querySelector('.tipo-talla-color-block') || null;
    }

    function activarBloqueColor(block) {
        tipoTallaGrid?.querySelectorAll('.tipo-talla-color-block').forEach((item) => {
            item.classList.toggle('is-active', item === block);
        });
        sincronizarEstadoPills();
    }

    function sincronizarEstadoPills() {
        const pills = tipoTallaGrid?.querySelectorAll('.tipo-talla-pill') || [];
        pills.forEach((pill) => pill.classList.remove('is-selected'));
        if (tipoTallaState.modoCarga === 'cantidad') return;

        if (tipoTallaState.modoCarga === 'color') {
            const activeBlock = obtenerBloqueColorActivo();
            const selected = new Set(
                Array.from(activeBlock?.querySelectorAll('.modal-talla-input') || [])
                    .map((input) => String(input.value || '').trim().toUpperCase())
                    .filter(Boolean)
            );
            pills.forEach((pill) => {
                const talla = String(pill.dataset.colorBlockPill || pill.textContent || '').trim().toUpperCase();
                if (selected.has(talla)) pill.classList.add('is-selected');
            });
            activeBlock?.querySelectorAll('.tipo-talla-pill--in-block[data-color-block-pill]').forEach((pill) => {
                const talla = String(pill.dataset.colorBlockPill || '').trim().toUpperCase();
                pill.classList.toggle('is-selected', selected.has(talla));
            });
            return;
        }

        const selected = new Set(
            Array.from(tipoTallaGrid?.querySelectorAll('.tipo-talla-row .modal-talla-input') || [])
                .map((input) => String(input.value || '').trim().toUpperCase())
                .filter(Boolean)
        );
        pills.forEach((pill) => {
            const talla = String(pill.textContent || '').trim().toUpperCase();
            if (selected.has(talla)) pill.classList.add('is-selected');
        });
    }

    function renderTallasDisponibles(tipo) {
        if (!tipoTallaGrid) return;
        tipoTallaGrid.innerHTML = '';

        if (tipoTallaState.modoCarga === 'color') {
            const groupsWrap = document.createElement('div');
            groupsWrap.className = 'tipo-talla-color-groups';
            const block = crearBloqueColorModal(tipo);
            groupsWrap.appendChild(block);
            tipoTallaGrid.appendChild(groupsWrap);
            activarBloqueColor(block);
        }

        if (tipoTallaState.modoCarga !== 'cantidad' && tipoTallaState.modoCarga !== 'color') {
            const pillsWrap = document.createElement('div');
            pillsWrap.className = 'tipo-talla-pills';
            (TALLAS_POR_TIPO[tipo] || []).forEach((talla) => {
                const pill = document.createElement('button');
                pill.type = 'button';
                pill.className = 'tipo-talla-pill';
                pill.textContent = talla;
                pill.addEventListener('click', function () {
                    pill.classList.add('is-picked');
                    setTimeout(() => pill.classList.remove('is-picked'), 220);
                    if (tipoTallaState.modoCarga === 'normal') {
                        const existe = Array.from(tipoTallaGrid.querySelectorAll('.tipo-talla-row .modal-talla-input'))
                            .some((input) => String(input.value || '').trim().toUpperCase() === talla.toUpperCase());
                        if (existe) {
                            return;
                        }
                    }
                    const addBtnRef = tipoTallaGrid.querySelector('.tipo-talla-add-btn');
                    const emptyRef = tipoTallaGrid.querySelector('.tipo-talla-empty');
                    if (emptyRef) emptyRef.remove();
                    const row = crearFilaDetalleModal(tipo, tipoTallaState.modoCarga, { talla });
                    if (addBtnRef) {
                        tipoTallaGrid.insertBefore(row, addBtnRef);
                    } else {
                        tipoTallaGrid.appendChild(row);
                    }
                    actualizarConfirmarModal();
                    sincronizarEstadoPills();
                });
                pillsWrap.appendChild(pill);
            });
            tipoTallaGrid.appendChild(pillsWrap);
            const emptyState = document.createElement('div');
            emptyState.className = 'tipo-talla-empty';
            emptyState.textContent = 'No hay tallas agregadas. Usa el botón para crear una fila.';
            tipoTallaGrid.appendChild(emptyState);
        } else {
            const emptyState = document.createElement('div');
            emptyState.className = 'tipo-talla-empty';
            emptyState.textContent = 'Agrega una cantidad para continuar.';
            tipoTallaGrid.appendChild(emptyState);
        }

        const addBtn = document.createElement('button');
        addBtn.type = 'button';
        addBtn.className = 'btn btn-outline-primary tipo-talla-add-btn';
        addBtn.textContent = tipoTallaState.modoCarga === 'cantidad'
            ? '+ Agregar cantidad'
            : tipoTallaState.modoCarga === 'color'
                ? '+ Agregar color'
                : '+ Agregar talla';
        addBtn.addEventListener('click', function () {
            const emptyRef = tipoTallaGrid.querySelector('.tipo-talla-empty');
            if (emptyRef) emptyRef.remove();
            if (tipoTallaState.modoCarga === 'color') {
                const groupsWrap = tipoTallaGrid.querySelector('.tipo-talla-color-groups');
                const block = crearBloqueColorModal(tipo);
                groupsWrap?.appendChild(block);
                activarBloqueColor(block);
                const colorInput = block.querySelector('.modal-color-grupo-input');
                colorInput?.focus();
            } else {
                tipoTallaGrid.insertBefore(crearFilaDetalleModal(tipo, tipoTallaState.modoCarga), addBtn);
            }
            actualizarConfirmarModal();
        });
        tipoTallaGrid.appendChild(addBtn);
        actualizarConfirmarModal();
        sincronizarEstadoPills();
    }

    function abrirModalTipoTalla(genero) {
        return new Promise((resolve) => {
            tipoTallaState.isOpen = true;
            tipoTallaState.resolve = resolve;
            tipoTallaState.genero = genero;
            tipoTallaState.tipoSeleccionado = null;
            tipoTallaState.modoCarga = 'normal';
            tipoTallaState.etapa = 'modo';
            tipoTallaState.detallesSeleccionados = [];
            tipoTallaModalText.textContent = `Configura tallas para ${toGeneroLabel(genero)}.`;
            if (tipoTallaGeneroBadge) {
                tipoTallaGeneroBadge.textContent = toGeneroLabel(genero);
            }

            tipoTallaModal.querySelectorAll('[data-modo-carga-select]').forEach((btn) => {
                const isActive = btn.dataset.modoCargaSelect === 'normal';
                btn.classList.toggle('btn-primary', isActive);
                btn.classList.toggle('btn-outline-primary', !isActive);
                if (btn.dataset.modoCargaSelect === 'cantidad') {
                    btn.style.display = genero === 'unisex' ? '' : 'none';
                }
            });

            tipoTallaModal.querySelectorAll('[data-tipo-talla-select]').forEach((btn) => {
                const isActive = btn.dataset.tipoTallaSelect === 'letra';
                btn.classList.toggle('btn-primary', isActive);
                btn.classList.toggle('btn-outline-primary', !isActive);
            });

            tipoTallaModal.querySelectorAll('[data-tipo-talla-select]').forEach((btn) => {
                btn.classList.toggle('btn-primary', false);
                btn.classList.toggle('btn-outline-primary', true);
            });

            if (tipoTallaGrid) {
                tipoTallaGrid.innerHTML = '';
            }
            actualizarUIWizard();
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
        tipoTallaState.modoCarga = 'normal';
        tipoTallaState.etapa = 'modo';
        tipoTallaState.detallesSeleccionados = [];
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

    function setModoCargaEnSeccion(section, modo) {
        if (!section) return;
        const modoNormalizado = modo === 'color' ? 'color' : (modo === 'cantidad' ? 'cantidad' : 'normal');
        section.dataset.modoCarga = modoNormalizado;
        section.classList.toggle('is-sin-color', modoNormalizado === 'normal');
        section.classList.toggle('is-cantidad-solo', modoNormalizado === 'cantidad');
    }

    function crearFilaTalla(prendaIndex, genero, listId, talla = '', incluirColor = true, soloCantidad = false) {
        const fila = document.createElement('div');
        if (soloCantidad) {
            fila.innerHTML = `
                <input type="hidden" name="talla_${genero}[${prendaIndex}][]" value="UNICA">
                <input type="number" name="cantidad_${genero}[${prendaIndex}][]" placeholder="Cantidad" min="1">
                <button type="button" class="eliminar-talla-btn">x</button>
            `;
        } else if (incluirColor) {
            fila.innerHTML = `
                <input type="text" name="talla_${genero}[${prendaIndex}][]" class="talla-input-uppercase" list="${listId}" placeholder="Talla" value="${talla}">
                <input type="text" name="color_${genero}[${prendaIndex}][]" class="color-input-uppercase" placeholder="Color (ej: ROJO)">
                <input type="number" name="cantidad_${genero}[${prendaIndex}][]" placeholder="Cantidad" min="1">
                <button type="button" class="eliminar-talla-btn">x</button>
            `;
        } else {
            fila.innerHTML = `
                <input type="text" name="talla_${genero}[${prendaIndex}][]" class="talla-input-uppercase" list="${listId}" placeholder="Talla" value="${talla}">
                <input type="number" name="cantidad_${genero}[${prendaIndex}][]" placeholder="Cantidad" min="1">
                <button type="button" class="eliminar-talla-btn">x</button>
            `;
        }
        return fila;
    }

    function aplicarTallasSeleccionadas(prendaCard, genero, tipo, detallesSeleccionados, modo = 'normal') {
        const prendaIndex = parseInt(prendaCard.dataset.prendaIndex || '0', 10);
        const list = prendaCard.querySelector(`.tallas-list-${genero}`);
        const section = prendaCard.querySelector(`[data-genero-section="${genero}"]`);
        if (!list) return;
        const listId = LISTA_TALLAS_POR_TIPO[tipo] || 'tallas-sugeridas-list';
        const incluirColor = modo === 'color';
        const soloCantidad = modo === 'cantidad';
        setModoCargaEnSeccion(section, modo);
        list.innerHTML = '';
        (detallesSeleccionados || []).forEach((detalle) => {
            const fila = crearFilaTalla(prendaIndex, genero, listId, detalle.talla || '', incluirColor, soloCantidad);
            const colorInput = fila.querySelector(`input[name="color_${genero}[${prendaIndex}][]"]`);
            const cantidadInput = fila.querySelector(`input[name="cantidad_${genero}[${prendaIndex}][]"]`);
            if (colorInput) colorInput.value = (detalle.color || '').toUpperCase();
            if (cantidadInput) cantidadInput.value = detalle.cantidad || '';
            list.appendChild(fila);
        });
        if ((detallesSeleccionados || []).length === 0) {
            list.appendChild(crearFilaTalla(prendaIndex, genero, listId, '', incluirColor, soloCantidad));
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

    openBtn?.addEventListener('click', openModal);

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
        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn?.disabled) {
            return;
        }

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
            const descripcion = formData.get(`prenda[${index}]`);
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

            console.log(`[FORM] Prenda ${index}:`, { descripcion, tallasList });

            if (descripcion && tallasList.length > 0) {
                
                prendas.push({
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

        const originalSubmitText = submitBtn ? submitBtn.innerHTML : '';
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = 'Guardando...';
        }

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
                if (!data.duplicate) {
                    showReciboSuccessModal();
                    loadRecibosCorteForBodega();
                    if (data.prendas && data.prendas.length > 0) {
                        setTimeout(() => openReciboCorteBodegaModal(data.prendas[0].id), 500);
                    }
                }
            } else {
                alert('Error: ' + (data.message || 'No se pudo guardar el recibo'));
            }
        })
        .catch(error => {
            console.error('[FETCH] Error:', error);
            alert('Error al guardar el recibo: ' + error.message);
        })
        .finally(() => {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalSubmitText;
            }
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
                const section = prendaCard.querySelector('[data-genero-section="dama"]');
                const listId = LISTA_TALLAS_POR_TIPO[section?.dataset?.tipoTalla] || 'tallas-sugeridas-list';
                const incluirColor = (section?.dataset?.modoCarga || 'normal') === 'color';
                tallasDamaList.appendChild(crearFilaTalla(prendaIndex, 'dama', listId, '', incluirColor));
            });
        }

        if (addCabBtn && tallasCabList) {
            addCabBtn.addEventListener('click', function () {
                const section = prendaCard.querySelector('[data-genero-section="caballero"]');
                const listId = LISTA_TALLAS_POR_TIPO[section?.dataset?.tipoTalla] || 'tallas-sugeridas-list';
                const incluirColor = (section?.dataset?.modoCarga || 'normal') === 'color';
                tallasCabList.appendChild(crearFilaTalla(prendaIndex, 'caballero', listId, '', incluirColor));
            });
        }

        if (addUniBtn && tallasUniList) {
            addUniBtn.addEventListener('click', function () {
                const section = prendaCard.querySelector('[data-genero-section="unisex"]');
                const listId = LISTA_TALLAS_POR_TIPO[section?.dataset?.tipoTalla] || 'tallas-sugeridas-list';
                const incluirColor = (section?.dataset?.modoCarga || 'normal') === 'color';
                tallasUniList.appendChild(crearFilaTalla(prendaIndex, 'unisex', listId, '', incluirColor));
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
                if (!seleccion || !seleccion.tipo || !Array.isArray(seleccion.detalles) || seleccion.detalles.length === 0) {
                    generoToggleInput.checked = false;
                    label?.classList.remove('is-active');
                    section.classList.add('is-hidden');
                    return;
                }
                setTipoTallaEnSeccion(section, seleccion.tipo);
                aplicarTallasSeleccionadas(prendaCard, genero, seleccion.tipo, seleccion.detalles, seleccion.modo);
                label?.classList.add('is-active');
                section.classList.remove('is-hidden');
            } else {
                label?.classList.remove('is-active');
                section.classList.add('is-hidden');
                section.dataset.tipoTalla = '';
                section.dataset.modoCarga = '';
                section.classList.remove('is-sin-color');
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
        event.target.closest('div')?.remove();

        const section = tallasList.closest('.tallas-subsection');
        if (!section) return;
        const rowsRestantes = tallasList.querySelectorAll(':scope > div');
        if (rowsRestantes.length > 0) return;

        section.classList.add('is-hidden');
        section.dataset.tipoTalla = '';
        section.dataset.modoCarga = '';
        section.classList.remove('is-sin-color');

        const prendaCard = section.closest('.prenda-card');
        const genero = section.dataset.generoSection;
        if (!prendaCard || !genero) return;

        const checkbox = prendaCard.querySelector(`.genero-check-input[data-genero-toggle="${genero}"]`);
        const label = checkbox?.closest('.genero-check');
        if (checkbox) checkbox.checked = false;
        label?.classList.remove('is-active');
    });

    prendasContainer.querySelectorAll('.prenda-card').forEach(bindPrendaActions);

    tipoTallaModal.querySelectorAll('[data-tipo-talla-select]').forEach((btn) => {
        btn.addEventListener('click', function () {
            tipoTallaState.tipoSeleccionado = btn.dataset.tipoTallaSelect;
            tipoTallaState.detallesSeleccionados = [];
            tipoTallaModal.querySelectorAll('[data-tipo-talla-select]').forEach((b) => {
                b.classList.toggle('btn-primary', b === btn);
                b.classList.toggle('btn-outline-primary', b !== btn);
            });
            renderTallasDisponibles(tipoTallaState.tipoSeleccionado);
            tipoTallaState.etapa = 'captura';
            actualizarUIWizard();
            if (tipoTallaConfirmarBtn) tipoTallaConfirmarBtn.disabled = true;
        });
    });

    tipoTallaModal.querySelectorAll('[data-modo-carga-select]').forEach((btn) => {
        btn.addEventListener('click', function () {
            tipoTallaState.modoCarga = btn.dataset.modoCargaSelect || 'normal';
            if (tipoTallaState.genero !== 'unisex' && tipoTallaState.modoCarga === 'cantidad') {
                tipoTallaState.modoCarga = 'normal';
            }
            tipoTallaModal.querySelectorAll('[data-modo-carga-select]').forEach((b) => {
                b.classList.toggle('btn-primary', b === btn);
                b.classList.toggle('btn-outline-primary', b !== btn);
            });
            if (tipoTallaState.modoCarga === 'cantidad') {
                tipoTallaState.tipoSeleccionado = 'letra';
                tipoTallaState.etapa = 'captura';
                renderTallasDisponibles(tipoTallaState.tipoSeleccionado);
                actualizarUIWizard();
                if (tipoTallaConfirmarBtn) tipoTallaConfirmarBtn.disabled = true;
                return;
            }

            tipoTallaState.tipoSeleccionado = null;
            tipoTallaState.etapa = 'tipo';
            if (tipoTallaGrid) tipoTallaGrid.innerHTML = '';
            actualizarUIWizard();
        });
    });

    tipoTallaBackBtn?.addEventListener('click', function () {
        if (tipoTallaState.etapa === 'captura') {
            tipoTallaState.tipoSeleccionado = null;
            tipoTallaState.etapa = 'tipo';
            if (tipoTallaGrid) tipoTallaGrid.innerHTML = '';
        } else if (tipoTallaState.etapa === 'tipo') {
            tipoTallaState.etapa = 'modo';
        }
        actualizarUIWizard();
    });

    tipoTallaGrid?.addEventListener('input', function () {
        const active = document.activeElement;
        if (active?.classList?.contains('modal-color-grupo-input')) {
            active.value = String(active.value || '').toUpperCase();
        }
        actualizarConfirmarModal();
        sincronizarEstadoPills();
    });

    tipoTallaGrid?.addEventListener('click', function (event) {
        const colorBlock = event.target.closest('.tipo-talla-color-block');
        if (colorBlock) {
            activarBloqueColor(colorBlock);
        }
        const colorPill = event.target.closest('.tipo-talla-pill--in-block[data-color-block-pill]');
        if (colorPill) {
            const block = colorPill.closest('.tipo-talla-color-block');
            const talla = (colorPill.dataset.colorBlockPill || '').trim().toUpperCase();
            const rowsWrap = block?.querySelector('.tipo-talla-color-rows');
            if (!block || !rowsWrap || !talla) return;
            activarBloqueColor(block);
            const existe = Array.from(rowsWrap.querySelectorAll('.modal-talla-input'))
                .some((input) => String(input.value || '').trim().toUpperCase() === talla);
            if (existe) return;
            const row = crearFilaDetalleModal(tipoTallaState.tipoSeleccionado, 'color', { talla, cantidad: 1 });
            const tallaInput = row.querySelector('.modal-talla-input');
            const cantidadInput = row.querySelector('.modal-cantidad-input');
            if (tallaInput) {
                tallaInput.readOnly = true;
                tallaInput.classList.add('is-readonly');
            }
            rowsWrap.appendChild(row);
            colorPill.classList.add('is-selected');
            if (cantidadInput) cantidadInput.focus();
            actualizarConfirmarModal();
            sincronizarEstadoPills();
            return;
        }
        const removeColorBlockBtn = event.target.closest('.tipo-talla-remove-color-block');
        if (removeColorBlockBtn) {
            const block = removeColorBlockBtn.closest('.tipo-talla-color-block');
            block?.remove();
            const firstBlock = tipoTallaGrid.querySelector('.tipo-talla-color-block');
            if (firstBlock) activarBloqueColor(firstBlock);
            actualizarConfirmarModal();
            sincronizarEstadoPills();
            return;
        }
        const removeBtn = event.target.closest('.tipo-talla-remove');
        if (!removeBtn) return;
        removeBtn.closest('.tipo-talla-row')?.remove();
        actualizarConfirmarModal();
        sincronizarEstadoPills();
    });

    tipoTallaConfirmarBtn?.addEventListener('click', function () {
        if (!tipoTallaState.tipoSeleccionado) return;
        const detalles = [];
        const tallasNormal = new Set();
        const rows = tipoTallaGrid?.querySelectorAll('.tipo-talla-row') || [];
        rows.forEach((row) => {
            const talla = (row.querySelector('.modal-talla-input')?.value || '').trim().toUpperCase();
            const cantidad = parseInt(row.querySelector('.modal-cantidad-input')?.value || '0', 10);
            if (tipoTallaState.modoCarga === 'normal') {
                if (talla !== '' && cantidad > 0) {
                    if (tallasNormal.has(talla)) {
                        return;
                    }
                    tallasNormal.add(talla);
                    detalles.push({ talla, color: '', cantidad });
                }
            } else if (tipoTallaState.modoCarga === 'cantidad') {
                if (cantidad > 0) {
                    detalles.push({ talla: 'UNICA', color: '', cantidad });
                }
            }
        });
        if (tipoTallaState.modoCarga === 'color') {
            const blocks = tipoTallaGrid?.querySelectorAll('.tipo-talla-color-block') || [];
            blocks.forEach((block) => {
                const color = (block.querySelector('.modal-color-grupo-input')?.value || '').trim().toUpperCase();
                if (!color) return;
                const tallasColor = new Set();
                block.querySelectorAll('.tipo-talla-row').forEach((row) => {
                    const talla = (row.querySelector('.modal-talla-input')?.value || '').trim().toUpperCase();
                    const cantidad = parseInt(row.querySelector('.modal-cantidad-input')?.value || '0', 10);
                    if (talla !== '' && cantidad > 0) {
                        const key = `${color}::${talla}`;
                        if (tallasColor.has(key)) return;
                        tallasColor.add(key);
                        detalles.push({ talla, color, cantidad });
                    }
                });
            });
        }
        if (tipoTallaState.modoCarga === 'normal') {
            const tallasCapturadas = rows
                ? Array.from(rows).map((row) => (row.querySelector('.modal-talla-input')?.value || '').trim().toUpperCase()).filter(Boolean)
                : [];
            const unicas = new Set(tallasCapturadas);
            if (tallasCapturadas.length !== unicas.size) {
                alert('En modo Normal no se permiten tallas repetidas.');
                return;
            }
        }
        if (detalles.length === 0) return;
        cerrarModalTipoTalla({
            tipo: tipoTallaState.tipoSeleccionado,
            modo: tipoTallaState.modoCarga,
            detalles,
        });
    });

    tipoTallaCancelarBtn?.addEventListener('click', function () {
        cerrarModalTipoTalla(null);
    });

    tipoTallaCloseBtn?.addEventListener('click', function () {
        cerrarModalTipoTalla(null);
    });

    tipoTallaModal.querySelector('.tipo-talla-modal__backdrop')?.addEventListener('click', function () {
        cerrarModalTipoTalla(null);
    });
});

document.addEventListener('DOMContentLoaded', function() {
    loadRecibosCorteForBodega();
});

const festivosReciboBodega = @json(\App\Models\Festivo::pluck('fecha')->toArray());
const festivosReciboBodegaSet = new Set(
    (festivosReciboBodega || [])
        .map((f) => String(f || '').slice(0, 10))
        .filter(Boolean)
);

function formatLocalYmd(date) {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
}

function parseFechaFlexible(value) {
    if (!value) return null;
    if (value instanceof Date && !Number.isNaN(value.getTime())) return value;

    const raw = String(value).trim();
    if (!raw) return null;

    const ddmmyyyy = raw.match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})/);
    if (ddmmyyyy) {
        const day = Number(ddmmyyyy[1]);
        const month = Number(ddmmyyyy[2]);
        const year = Number(ddmmyyyy[3]);
        const parsed = new Date(year, month - 1, day);
        return Number.isNaN(parsed.getTime()) ? null : parsed;
    }

    const parsed = new Date(raw);
    return Number.isNaN(parsed.getTime()) ? null : parsed;
}

function esDiaHabil(date) {
    const day = date.getDay();
    if (day === 0 || day === 6) return false;
    return !festivosReciboBodegaSet.has(formatLocalYmd(date));
}

function calcularDiasHabilesDesdeSiguienteHabil(fechaInicio, fechaFin = new Date()) {
    const inicio = parseFechaFlexible(fechaInicio);
    const fin = parseFechaFlexible(fechaFin);
    if (!inicio || !fin) return 0;

    const start = new Date(inicio.getFullYear(), inicio.getMonth(), inicio.getDate());
    const end = new Date(fin.getFullYear(), fin.getMonth(), fin.getDate());
    if (start > end) return 0;

    const cursor = new Date(start);
    cursor.setDate(cursor.getDate() + 1);

    while (cursor <= end && !esDiaHabil(cursor)) {
        cursor.setDate(cursor.getDate() + 1);
    }

    let diasHabiles = 0;
    while (cursor <= end) {
        if (esDiaHabil(cursor)) diasHabiles++;
        cursor.setDate(cursor.getDate() + 1);
    }

    return diasHabiles;
}

function loadRecibosCorteForBodega() {
    fetch('/api/recibo-corte-bodega')
        .then(response => response.json())
        .then(data => {
            const tbody = document.getElementById('recibo-corte-bodega-tbody');
            tbody.innerHTML = '';

            if (data.success && data.data && data.data.length > 0) {
                data.data.forEach(prenda => {
                    const canAssignByBadge = Boolean(
                        isAdminBodega &&
                        prenda &&
                        String(prenda.area || '').toLowerCase().includes('corte')
                    );
                    const fechaBaseCalculo = prenda.created_at || prenda.fecha_creacion || prenda.fecha_corta;
                    const diasHabilesTranscurridos = calcularDiasHabilesDesdeSiguienteHabil(fechaBaseCalculo, new Date());
                    const estaAtrasado = diasHabilesTranscurridos > 3;
                    const row = document.createElement('tr');
                    if (estaAtrasado) {
                        row.classList.add('recibo-atrasado-row');
                        row.title = `Atrasado: ${diasHabilesTranscurridos} dias habiles transcurridos`;
                    }
                    row.innerHTML = `
                        <td class="acciones-column" style="text-align: center; position: relative;">
                            <button type="button"
                                class="btn-ver-dropdown-bodega"
                                title="Ver Opciones"
                                data-menu-id="menu-recibo-bodega-${prenda.id}"
                                data-pedido-id="${prenda.pedido_produccion_id || ''}"
                                data-prenda-id="${prenda.prenda_id || ''}"
                                data-numero-recibo="${prenda.numero_recibo || ''}"
                                data-tipo-recibo="CORTE-PARA-BODEGA"
                                data-es-parcial="false"
                                data-pedido-parcial-id=""
                                data-recibo-id="${prenda.id}"
                                data-prenda-bodega-id="${prenda.id}"
                                data-tiene-parciales="false"
                                data-total-parciales="0">
                                <i class="fas fa-eye"></i>
                            </button>
                        </td>
                        <td style="text-align: center;">
                            <span class="badge rounded-pill bg-info text-dark"
                                  style="${canAssignByBadge ? 'cursor:pointer;' : ''}"
                                  title="${canAssignByBadge ? 'Click para asignar proceso' : ''}"
                                  ${canAssignByBadge ? `onclick="openAddProcesoFromBodegaBadge({ area: '${String(prenda.area || 'Corte').replace(/'/g, "\\'")}', numero_recibo: ${prenda.numero_recibo || 'null'}, pedido_produccion_id: ${prenda.pedido_produccion_id || 'null'}, prenda_id: ${prenda.prenda_id || 'null'}, prenda_bodega_id: ${prenda.id || 'null'} })"` : ''}>${prenda.area || '-'}</span>
                        </td>
                        <td style="text-align: center;"><strong>${prenda.numero_recibo || '-'}</strong></td>
                        <td>${prenda.descripcion || '-'}</td>
                        <td style="text-align: center;">${prenda.cantidad_tallas}</td>
                        <td style="text-align: center;"><span class="badge bg-success">${prenda.total_cantidad}</span></td>
                        <td style="text-align: center;">${prenda.fecha_corta}</td>
                        <td style="text-align: center;">${prenda.encargado || '-'}</td>
                    `;
                    tbody.appendChild(row);
                });
            } else {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="8" class="text-center py-4">
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
                    <td colspan="8" class="text-center py-4">
                        <div class="alert alert-danger mb-0">
                            Error al cargar los recibos de corte para bodega.
                        </div>
                    </td>
                </tr>
            `;
        });
}

function closeReciboBodegaDropdowns() {
    document.querySelectorAll('.dropdown-menu-recibos').forEach((m) => m.remove());
    document.querySelectorAll('.btn-ver-dropdown-bodega.dropdown-opening').forEach((b) => b.classList.remove('dropdown-opening'));
}

function openReciboBodegaDropdown(button) {
    if (!button) return;

    const existing = document.getElementById(button.getAttribute('data-menu-id'));
    if (existing) {
        closeReciboBodegaDropdowns();
        return;
    }

    closeReciboBodegaDropdowns();
    button.classList.add('dropdown-opening');

    const menuId = button.getAttribute('data-menu-id') || `menu-recibo-bodega-${Date.now()}`;
    const reciboId = Number(button.getAttribute('data-recibo-id') || 0);
    const pedidoId = String(button.getAttribute('data-pedido-id') || '').trim();
    const numeroRecibo = Number(button.getAttribute('data-numero-recibo') || 0);
    const prendaBodegaId = Number(button.getAttribute('data-prenda-bodega-id') || 0);

    const dropdown = document.createElement('div');
    dropdown.id = menuId;
    dropdown.className = 'dropdown-menu-recibos';
    dropdown.style.display = 'block';
    dropdown.style.pointerEvents = 'auto';
    dropdown.innerHTML = `
        <button class="dropdown-item-btn" type="button" data-action="ver-detalles">
            <i class="fas fa-eye"></i> Ver Detalles
        </button>
        <div class="dropdown-divider"></div>
        <button class="dropdown-item-btn" type="button" data-action="ver-distribucion">
            <i class="fas fa-share"></i> Ver Distribución
        </button>
        <div class="dropdown-divider"></div>
        <button class="dropdown-item-btn" type="button" data-action="seguimiento">
            <i class="fas fa-tasks"></i> Seguimiento
        </button>
    `;

    dropdown.addEventListener('click', async function (event) {
        const actionBtn = event.target.closest('.dropdown-item-btn');
        if (!actionBtn) return;
        const action = actionBtn.getAttribute('data-action');

        if (action === 'ver-detalles') {
            if (reciboId > 0) {
                openReciboCorteBodegaModal(reciboId);
            }
            closeReciboBodegaDropdowns();
            return;
        }

        if (action === 'ver-distribucion') {
            if (numeroRecibo > 0) {
                await openReciboBodegaDistribucion(numeroRecibo, prendaBodegaId);
            }
            closeReciboBodegaDropdowns();
            return;
        }

        if (action === 'seguimiento') {
            await openReciboBodegaSeguimientoInterno(numeroRecibo, prendaBodegaId);
            closeReciboBodegaDropdowns();
        }
    });

    document.body.appendChild(dropdown);
    const rect = button.getBoundingClientRect();
    dropdown.style.top = `${window.scrollY + rect.bottom + 8}px`;
    dropdown.style.left = `${window.scrollX + rect.left - 8}px`;
}

async function openReciboBodegaSeguimientoInterno(numeroRecibo, prendaBodegaId) {
    const numero = Number(numeroRecibo || 0);
    const prendaBodega = Number(prendaBodegaId || 0);

    if (numero <= 0) {
        alert('Este recibo no tiene número de recibo válido.');
        return;
    }

    try {
        const qs = prendaBodega > 0 ? `?prenda_bodega_id=${encodeURIComponent(String(prendaBodega))}` : '';
        const resp = await fetch(`/api/recibos-bodega/${numero}/procesos${qs}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });
        const procesos = await resp.json().catch(() => []);

        if (!resp.ok || !Array.isArray(procesos)) {
            throw new Error('No se pudieron cargar los procesos del recibo interno.');
        }

        const modal = document.getElementById('orderTrackingModal');
        const overlay = document.getElementById('trackingModalOverlay');
        const closeBtn = document.getElementById('closeTrackingModal');
        const reciboEl = document.getElementById('trackingOrderRecibo');
        const pedidoEl = document.getElementById('trackingOrderNumber');
        const clienteEl = document.getElementById('trackingOrderClient');
        const estadoEl = document.getElementById('trackingOrderStatus');
        const fechaEstimadaEl = document.getElementById('trackingEstimatedDate');
        const subtitleEl = document.getElementById('trackingPrendaReciboHeader');
        const timelineContainer = document.getElementById('trackingTimelineContainer');
        const timelineSection = document.getElementById('trackingTimelineSection');

        if (!modal || !timelineContainer) {
            throw new Error('Modal de seguimiento unificado no disponible.');
        }

        if (reciboEl) reciboEl.textContent = String(numero);
        if (pedidoEl) pedidoEl.textContent = '-';
        if (clienteEl) clienteEl.textContent = 'RECIBO INTERNO BODEGA';
        if (estadoEl) estadoEl.textContent = 'EN EJECUCIÓN';
        if (fechaEstimadaEl) fechaEstimadaEl.textContent = 'No definida';
        if (subtitleEl) subtitleEl.textContent = `CORTE-PARA-BODEGA #${numero}`;

        const cards = procesos.length
            ? procesos.map((p) => {
                const estado = String(p?.estado_proceso || 'Pendiente');
                const pendingClass = estado.toLowerCase() === 'completado' ? 'completed' : 'pending';
                const inicio = p?.fecha_inicio ? String(p.fecha_inicio).slice(0, 10) : '---';
                const fin = p?.fecha_fin ? String(p.fecha_fin).slice(0, 10) : '---';
                return `
                <div class="tracking-area-card tracking-area-card-v2 ${pendingClass}">
                    <div class="tracking-area-v2-left">
                        <div class="tracking-area-v2-name">${p?.proceso || '-'}</div>
                    </div>
                    <div class="tracking-area-v2-body">
                        <div class="tracking-area-v2-row">
                            <div class="tracking-area-v2-field">
                                <div class="tracking-area-v2-label">Encargado:</div>
                                <div class="tracking-area-v2-pill">${p?.encargado || '-'}</div>
                            </div>
                            <div class="tracking-area-v2-field">
                                <div class="tracking-area-v2-label">Fecha inicio:</div>
                                <div class="tracking-area-v2-pill">${inicio}</div>
                            </div>
                            <div class="tracking-area-v2-field tracking-area-v2-field-right">
                                <div class="tracking-area-v2-label">Fecha fin:</div>
                                <div class="tracking-area-v2-badge">${fin}</div>
                            </div>
                        </div>
                        <div class="tracking-area-v2-footer">
                            <div class="tracking-area-v2-status">
                                <span class="tracking-days-badge">${estado}</span>
                            </div>
                        </div>
                    </div>
                </div>`;
            }).join('')
            : `<div style="padding: 1rem; color:#64748b;">Sin procesos registrados para este recibo.</div>`;

        timelineContainer.innerHTML = `
            <div class="tracking-section tracking-section-areas">
                <div class="tracking-section-header">
                    <div class="tracking-section-title">Seguimiento por áreas:</div>
                </div>
                ${cards}
            </div>
        `;
        if (timelineSection) timelineSection.style.display = 'block';

        const closeUnified = () => {
            modal.classList.remove('show');
            modal.style.display = 'none';
            modal.style.visibility = 'hidden';
            modal.style.opacity = '0';
        };

        if (overlay && overlay.dataset.bodegaCloseBound !== '1') {
            overlay.dataset.bodegaCloseBound = '1';
            overlay.addEventListener('click', closeUnified);
        }
        if (closeBtn && closeBtn.dataset.bodegaCloseBound !== '1') {
            closeBtn.dataset.bodegaCloseBound = '1';
            closeBtn.addEventListener('click', closeUnified);
        }

        modal.classList.add('show');
        modal.style.display = 'flex';
        modal.style.visibility = 'visible';
        modal.style.opacity = '1';
    } catch (error) {
        alert(error.message || 'Error cargando seguimiento interno de bodega.');
    }
}

async function openReciboBodegaDistribucion(numeroRecibo, prendaBodegaId) {
    const numero = Number(numeroRecibo || 0);
    const prendaBodega = Number(prendaBodegaId || 0);

    if (numero <= 0) {
        alert('Este recibo no tiene número de recibo válido.');
        return;
    }

    try {
        const qs = prendaBodega > 0 ? `?prenda_bodega_id=${encodeURIComponent(String(prendaBodega))}` : '';
        const resp = await fetch(`/api/recibos-bodega/${numero}/distribucion${qs}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });
        const data = await resp.json().catch(() => ({}));

        if (!resp.ok) {
            throw new Error(data.message || 'No se pudieron cargar los datos de distribución.');
        }

        const parciales = data.parciales || [];
        const totalParciales = data.total_parciales || 0;
        const areaActual = String(data?.recibo?.area_actual || 'Sin asignar');
        const totalUnidades = Number(data?.recibo?.total_unidades || 0);

        if (totalParciales === 0) {
            alert('No hay parciales creados para este recibo #' + numero);
            return;
        }

        // Usar el modal específico de distribución
        const modal = document.getElementById('recibo-distribution-modal');
        const backdrop = modal?.querySelector('.distribution-modal__backdrop');
        const closeBtn = modal?.querySelector('.distribution-modal__close');
        const titleEl = modal?.querySelector('#distributionModalTitle');
        const bodyEl = modal?.querySelector('#distributionModalBody');

        if (!modal || !bodyEl) {
            throw new Error('Modal de distribución no disponible.');
        }

        // Actualizar título
        if (titleEl) titleEl.textContent = `Distribucion del recibo #${numero}`;

        // Calcular cantidad total
        const totalCantidad = parciales.reduce((sum, p) => {
            // Aquí podrías sumar cantidades si están disponibles en los datos
            return sum;
        }, 0);

        // Construir HTML del modal
        const parcialesHTML = parciales.length
            ? parciales.map((p, idx) => {
                const estado = String(p?.estado_proceso || 'Pendiente');
                const estadoNormalizado = estado.trim().toLowerCase();
                const estadoPillClass = estadoNormalizado === 'anulado'
                    ? 'distribution-pill--red'
                    : 'distribution-pill--slate';
                const numeroReciboParcial = p?.numero_recibo_parcial || (idx + 1);
                const parcialLabel = String(numeroReciboParcial).includes('.')
                    ? String(numeroReciboParcial)
                    : `${numero}.${numeroReciboParcial}`;
                const tallas = Array.isArray(p?.tallas) ? p.tallas : [];
                const tallasHTML = tallas.length
                    ? `<div class="distribution-sizes">${tallas.map((t) => {
                        const tallaNombre = String(t?.talla || '-').toUpperCase();
                        const qty = Number(t?.cantidad || 0);
                        return `<span class="distribution-size-chip">${tallaNombre} <strong>x${qty}</strong></span>`;
                    }).join('')}</div>`
                    : '<span class="distribution-pill">Sin tallas</span>';
                
                return `
                <article class="distribution-card">
                    <div class="distribution-card__inner">
                        <div class="distribution-card__top">
                            <div class="distribution-card__title">
                                <h3>Parcial #${parcialLabel}</h3>
                                <span class="distribution-pill ${estadoPillClass}">${estado}</span>
                            </div>
                            <span class="distribution-pill distribution-pill--green">${p?.proceso || 'Sin asignar'}</span>
                        </div>
                        <div class="distribution-card__meta">
                            <div class="distribution-card__row">
                                <span class="distribution-card__row-label">Encargado</span>
                                <span class="distribution-pill distribution-pill--blue">${p?.encargado || 'SIN ASIGNAR'}</span>
                            </div>
                            <div class="distribution-card__row">
                                <span class="distribution-card__row-label">Fechas</span>
                                <span class="distribution-pill">${p?.fecha_inicio ? String(p.fecha_inicio).slice(0, 10) : '---'} a ${p?.fecha_fin ? String(p.fecha_fin).slice(0, 10) : '---'}</span>
                            </div>
                            <div class="distribution-card__row">
                                <span class="distribution-card__row-label">Tallas</span>
                                ${tallasHTML}
                            </div>
                        </div>
                        <div class="distribution-card__actions">
                            <button type="button" class="distribution-action-btn" onclick="openReciboBodegaParcialSeguimiento(${Number(p?.id || 0)}, ${numero}, ${prendaBodega})">
                                <i class="fas fa-route"></i>
                                Ver seguimiento
                            </button>
                        </div>
                    </div>
                </article>`;
            }).join('')
            : `<div style="padding: 2rem; text-align: center; color: #64748b;">Sin parciales registrados para este recibo.</div>`;

        bodyEl.innerHTML = `
            <div class="distribution-summary">
                <div class="distribution-summary__card">
                    <span class="distribution-summary__label">Recibo</span>
                    <span class="distribution-summary__value">#${numero}</span>
                </div>
                <div class="distribution-summary__card">
                    <span class="distribution-summary__label">Area actual</span>
                    <span class="distribution-summary__value">${areaActual}</span>
                </div>
                <div class="distribution-summary__card">
                    <span class="distribution-summary__label">Resumen</span>
                    <span class="distribution-summary__value">${totalParciales} parciales - ${totalUnidades} und</span>
                </div>
            </div>
            <div class="distribution-list">
                ${parcialesHTML}
            </div>
        `;

        // Configurar eventos de cierre
        const closeModal = () => {
            if (modal?.contains(document.activeElement)) {
                document.activeElement.blur();
                const table = document.getElementById('recibo-corte-bodega-table');
                if (table) table.focus?.();
            }
            modal.setAttribute('aria-hidden', 'true');
            modal.style.display = 'none';
        };

        if (backdrop) {
            backdrop.onclick = closeModal;
        }
        if (closeBtn) {
            closeBtn.onclick = closeModal;
        }

        // Mostrar modal
        modal.setAttribute('aria-hidden', 'false');
        modal.style.display = 'flex';
        if (closeBtn) {
            setTimeout(() => closeBtn.focus?.(), 0);
        }
    } catch (error) {
        alert(error.message || 'Error cargando distribución de bodega.');
    }
}

function openReciboBodegaParcialSeguimiento(parcialId, numeroRecibo, prendaBodegaId) {
    const id = Number(parcialId || 0);
    if (id > 0 && typeof window.openSeguimientoParcialModal === 'function') {
        window.openSeguimientoParcialModal(id);
        return;
    }

    alert('No está disponible el modal de seguimiento por parcial en esta vista.');
}

function escapeDistributionHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function formatParcialConsecutivo(value) {
    if (value === null || value === undefined || value === '') return '-';
    const raw = String(value);
    if (raw.includes('.')) {
        return raw.replace(/\.0+$/, '').replace(/(\.\d*[1-9])0+$/, '$1');
    }
    return raw;
}

function buildPartialTrackingModalContent(parcial, timeline) {
    const tallas = Array.isArray(parcial.tallas) ? parcial.tallas : [];
    const tallasHtml = tallas.length > 0
        ? `
            <div class="partial-tracking-sizes" aria-label="Tallas del parcial">
                ${tallas.map((talla) => {
                    const tallaNombre = escapeDistributionHtml(talla.talla ?? 'N/A');
                    const cantidad = parseInt(talla.cantidad, 10) || 0;
                    const color = talla.color_nombre ? ` <span style="opacity:.75;">${escapeDistributionHtml(talla.color_nombre)}</span>` : '';
                    return `<span class="partial-tracking-size-chip">${tallaNombre} <strong>x${cantidad}</strong>${color}</span>`;
                }).join('')}
            </div>
        `
        : '<span class="partial-tracking-muted">Sin tallas registradas</span>';

    const steps = (Array.isArray(timeline) ? timeline : []).map((step) => {
        const isCompleted = Boolean(step.completado);
        const estadoLabel = isCompleted ? 'Completado' : (step.estado || 'En progreso');
        const estadoIcon = isCompleted ? 'fa-check-circle' : 'fa-signal';
        const fechaInicio = step.fecha_inicio ? `<span><strong>Inicio:</strong> ${escapeDistributionHtml(step.fecha_inicio)}</span>` : '';
        const fechaFin = step.fecha_fin ? `<span><strong>Fin:</strong> ${escapeDistributionHtml(step.fecha_fin)}</span>` : '';

        return `
            <article class="partial-tracking-step">
                <div class="partial-tracking-step__top">
                    <div class="partial-tracking-step__title">
                        <h3>${escapeDistributionHtml(step.area || 'Sin area')}</h3>
                        <div class="partial-tracking-step__meta">
                            <span class="partial-tracking-badge partial-tracking-badge--blue">
                                <i class="fas fa-user"></i>
                                ${escapeDistributionHtml(step.encargado || 'Sin asignar')}
                            </span>
                            <span class="partial-tracking-badge partial-tracking-badge--green">
                                <i class="fas ${estadoIcon}"></i>
                                ${escapeDistributionHtml(estadoLabel)}
                            </span>
                        </div>
                    </div>
                    <span class="partial-tracking-badge partial-tracking-badge--slate">Paso ${escapeDistributionHtml(step.orden || '-')}</span>
                </div>
                <div class="partial-tracking-step__dates">${fechaInicio}${fechaFin}</div>
            </article>
        `;
    }).join('');

    return `
        <div class="partial-tracking-summary">
            <div class="partial-tracking-summary__card">
                <span class="partial-tracking-summary__label">Parcial</span>
                <span class="partial-tracking-summary__value">#${escapeDistributionHtml(formatParcialConsecutivo(parcial.consecutivo_parcial ?? '-'))}</span>
            </div>
            <div class="partial-tracking-summary__card">
                <span class="partial-tracking-summary__label">Area actual</span>
                <span class="partial-tracking-summary__value">${escapeDistributionHtml(parcial.area_actual ?? 'Sin area')}</span>
            </div>
            <div class="partial-tracking-summary__card">
                <span class="partial-tracking-summary__label">Tallas</span>
                <div class="partial-tracking-summary__value">${tallasHtml}</div>
            </div>
        </div>
        <div class="partial-tracking-timeline">
            ${steps || '<div class="partial-tracking-empty"><p style="margin:0;">Este parcial aún no tiene recorrido registrado.</p></div>'}
        </div>
    `;
}

window.openSeguimientoParcialModal = async function (parcialId) {
    const modal = document.getElementById('partial-tracking-modal');
    const body = document.getElementById('partialTrackingModalBody');
    const title = document.getElementById('partialTrackingModalTitle');
    const id = Number(parcialId || 0);

    if (!modal || !body || !title || id <= 0) {
        alert('No se pudo abrir el seguimiento del parcial.');
        return;
    }

    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    title.textContent = 'Recorrido del parcial';
    body.innerHTML = `<div class="distribution-loading"><span class="distribution-spinner"></span><span>Cargando seguimiento del parcial...</span></div>`;

    try {
        const response = await fetch(`/api/recibos-costura/parciales/${encodeURIComponent(String(id))}/seguimiento`, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        });
        const result = await response.json().catch(() => ({}));
        if (!response.ok || !result.success) {
            throw new Error(result.message || 'No se pudo cargar el seguimiento del parcial');
        }

        const parcial = result.parcial || {};
        const timeline = Array.isArray(result.timeline) ? result.timeline : [];
        title.textContent = `Seguimiento del parcial #${escapeDistributionHtml(formatParcialConsecutivo(parcial.consecutivo_parcial ?? id))}`;
        body.innerHTML = buildPartialTrackingModalContent(parcial, timeline);
    } catch (error) {
        body.innerHTML = `<div class="partial-tracking-empty"><p style="margin:0;">${escapeDistributionHtml(error.message || 'Error cargando seguimiento')}</p></div>`;
    }
};

window.closeSeguimientoParcialModal = function () {
    const modal = document.getElementById('partial-tracking-modal');
    if (!modal) return;
    if (modal.contains(document.activeElement)) {
        document.activeElement.blur();
        const table = document.getElementById('recibo-corte-bodega-table');
        if (table) table.focus?.();
    }
    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');
    modal.style.display = 'none';
    document.body.style.overflow = '';
};

document.addEventListener('click', function (event) {
    const modal = document.getElementById('partial-tracking-modal');
    if (!modal) return;
    const shouldClose = event.target.closest('[data-partial-tracking-close="true"]');
    if (shouldClose || event.target === modal) {
        window.closeSeguimientoParcialModal();
    }
});


document.addEventListener('click', function (event) {
    const btnVer = event.target.closest('.btn-ver-dropdown-bodega');
    const inMenu = event.target.closest('.dropdown-menu-recibos');

    if (btnVer) {
        event.preventDefault();
        event.stopPropagation();
        openReciboBodegaDropdown(btnVer);
        return;
    }

    if (!inMenu) {
        closeReciboBodegaDropdowns();
    }
});
</script>
@endpush
