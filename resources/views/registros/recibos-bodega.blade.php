@extends('layouts.app')

@section('title', 'Recibos Bodega')

@section('content')
@php
    $tallasSugeridas = ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL', '4', '6', '8', '10', '12', '14', '16', '18', '20', '22', '24', '26', '28', '30', '32', '34', '36', '38', '40', '42'];
    $tallasSugeridasLetra = ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL', 'XXXXL'];
    $tallasSugeridasNumero = ['4', '6', '8', '10', '12', '14', '16', '18', '20', '22', '24', '26', '28', '30', '32', '34', '36', '38', '40', '42', '44', '46', '48', '50'];
    $generosPrenda = [
        ['key' => 'dama', 'label' => 'Dama', 'tallaPlaceholder' => 'XS', 'colorPlaceholder' => 'ROJO'],
        ['key' => 'caballero', 'label' => 'Caballero', 'tallaPlaceholder' => 'M', 'colorPlaceholder' => 'NEGRO'],
        ['key' => 'unisex', 'label' => 'Unisex', 'tallaPlaceholder' => 'M', 'colorPlaceholder' => 'AZUL'],
    ];
@endphp
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
                                <div class="prenda-imagenes-section">
                                    <label class="form-label-small" for="prendaImagenes_0">Imágenes de referencia</label>
                                    <input
                                        id="prendaImagenes_0"
                                        type="file"
                                        class="prenda-imagenes-input"
                                        name="prenda_imagenes[0][]"
                                        accept="image/*"
                                        multiple
                                        hidden
                                    >
                                    <div class="prenda-imagenes-dropzone" data-prenda-dropzone="0" tabindex="0" role="button" aria-label="Subir imágenes">
                                        <strong>Arrastra imágenes aquí</strong>
                                        <span>o haz clic para seleccionar</span>
                                    </div>
                                    <small class="prenda-imagenes-help">Puedes seleccionar una o varias imágenes.</small>
                                    <div class="prenda-imagenes-preview" data-prenda-imagenes-preview="0"></div>
                                </div>

                                <div class="tallas-section">
                                    <label class="form-label-small">Tallas, color y cantidad</label>
                                    <div class="genero-selector" role="group" aria-label="Seleccionar género">
                                        @foreach ($generosPrenda as $genero)
                                            <label class="genero-check">
                                                <input type="checkbox" class="genero-check-input" data-genero-toggle="{{ $genero['key'] }}">
                                                <span>{{ $genero['label'] }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                    <datalist id="tallas-sugeridas-list">
                                        @foreach ($tallasSugeridas as $talla)
                                            <option value="{{ $talla }}"></option>
                                        @endforeach
                                    </datalist>
                                    <datalist id="tallas-sugeridas-letra-list">
                                        @foreach ($tallasSugeridasLetra as $talla)
                                            <option value="{{ $talla }}"></option>
                                        @endforeach
                                    </datalist>
                                    <datalist id="tallas-sugeridas-numero-list">
                                        @foreach ($tallasSugeridasNumero as $talla)
                                            <option value="{{ $talla }}"></option>
                                        @endforeach
                                    </datalist>
                                    @foreach ($generosPrenda as $index => $genero)
                                        <div class="tallas-subsection {{ $index > 0 ? 'mt-2' : '' }} is-hidden" data-genero-section="{{ $genero['key'] }}">
                                            <label class="form-label-small mb-1">{{ $genero['label'] }}</label>
                                            <div class="tallas-head"><span>Talla</span><span>Color</span><span>Cantidad</span><span></span></div>
                                            <div class="tallas-list tallas-list-{{ $genero['key'] }}">
                                                <div>
                                                    <input type="text" name="talla_{{ $genero['key'] }}[0][]" class="talla-input-uppercase" list="tallas-sugeridas-list" placeholder="{{ $genero['tallaPlaceholder'] }}">
                                                    <input type="text" name="color_{{ $genero['key'] }}[0][]" class="color-input-uppercase" placeholder="{{ $genero['colorPlaceholder'] }}">
                                                    <input type="number" name="cantidad_{{ $genero['key'] }}[0][]" placeholder="0" min="1">
                                                    <button type="button" class="eliminar-talla-btn">x</button>
                                                </div>
                                            </div>
                                            <button type="button" class="btn-add-talla anadir-talla-{{ $genero['key'] }}-btn" data-prenda-index="0">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                                    <path d="M12 4v16m8-8H4" stroke-width="2" stroke-linecap="round"></path>
                                                </svg>
                                                Agregar talla {{ strtolower($genero['label']) }}
                                            </button>
                                        </div>
                                    @endforeach
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
<link rel="stylesheet" href="{{ asset('css/recibos-bodega.css') }}?v={{ time() }}">
@endpush

@push('scripts')
<script>
window.RECIBOS_BODEGA_CONFIG = {
    isAdminBodega: @json((bool) (auth()->user()?->hasRole('admin'))),
    festivosReciboBodega: @json(\App\Models\Festivo::pluck('fecha')->toArray()),
};
</script>
<script src="{{ asset('js/recibos-bodega.js') }}?v={{ time() }}"></script>
@endpush
