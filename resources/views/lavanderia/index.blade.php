@extends('layouts.lavanderia')

@section('content')
<!-- LOADING SCREEN -->
<div id="loadingScreen" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: white; display: flex; align-items: center; justify-content: center; z-index: 9999;">
    <div style="text-align: center;">
        <div style="width: 50px; height: 50px; border: 4px solid #f0f0f0; border-top: 4px solid #2450ef; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 20px;"></div>
        <p style="color: #64748b; font-size: 14px; font-weight: 500; margin: 0;">Cargando...</p>
    </div>
</div>

<style>
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
</style>

<div class="lavanderia-container">
    <div class="lavanderia-content">
        
        <!-- SECCIÓN: BUSCADOR DE MOVIMIENTOS -->
        <div class="movimiento-section">
            <div style="display: flex; flex-direction: column; gap: 12px;">
                <div class="search-wrapper" style="width: 100%;">
                    <input 
                        type="text" 
                        id="searchMovimientosInput" 
                        class="form-input search-input-large" 
                        placeholder="Busca por número de recibo..."
                        autocomplete="off"
                    >
                </div>
                <button type="button" class="btn btn-primary" id="btnAbrirModalSalida" style="width: 100%;">
                    <span class="material-symbols-rounded">add_circle</span>
                    Nuevo Movimiento
                </button>
            </div>
        </div>

        <!-- SECCIÓN: TABLA DE CONTROL -->
        <div class="control-section">
            <div class="section-header">
                <div class="section-icon">
                    <span class="material-symbols-rounded">list_alt</span>
                </div>
                <div>
                    <h2 class="section-title">Control de Movimientos</h2>
                    <p class="section-subtitle">Historial de salidas y llegadas</p>
                </div>
            </div>

            <!-- TABS -->
            <div class="tabs-container">
                <button class="tab-button active" data-tab="salidas">
                    <span class="material-symbols-rounded">arrow_upward</span>
                    Salidas
                </button>
                <button class="tab-button" data-tab="entradas">
                    <span class="material-symbols-rounded">arrow_downward</span>
                    Entradas
                </button>
            </div>

            <div id="movementsContainer">
                <!-- Los cards se renderizarán aquí dinámicamente -->
            </div>

            <!-- PAGINACIÓN -->
            <div id="paginationContainer" style="display: none; margin-top: 24px; display: flex; justify-content: center; align-items: center; gap: 8px;">
                <button id="btnPrevPage" class="btn-pagination" style="padding: 8px 12px; background: #e2e8f0; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; color: #1e293b;">
                    <span class="material-symbols-rounded" style="font-size: 18px;">chevron_left</span>
                </button>
                <div id="pageNumbers" style="display: flex; gap: 4px; align-items: center;">
                    <!-- Los números de página se renderizarán aquí -->
                </div>
                <button id="btnNextPage" class="btn-pagination" style="padding: 8px 12px; background: #e2e8f0; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; color: #1e293b;">
                    <span class="material-symbols-rounded" style="font-size: 18px;">chevron_right</span>
                </button>
            </div>
        </div>

    </div>
</div>

<!-- MODAL: REGISTRAR MOVIMIENTO -->
<div class="modal" id="modalSalida">
    <div class="modal-content modal-salida-content">
        <div class="modal-header">
            <div>
                <h3 class="modal-title">Registrar Movimiento de Lavandería</h3>
            </div>
            <button type="button" class="modal-close">
                <span class="material-symbols-rounded">close</span>
            </button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label">Tipo de Movimiento</label>
                <select id="selectTipoMovimiento" class="form-select">
                    <option value="SALIDA">Salida</option>
                    <option value="ENTRADA">Entrada</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Número de Recibo</label>
                <div class="search-wrapper">
                    <input 
                        type="text" 
                        id="searchRecibo" 
                        class="form-input search-input-large" 
                        placeholder="Ejemplo: C-101, B-201..."
                        autocomplete="off"
                    >
                    <div class="autocomplete-results"></div>
                </div>
            </div>

            <!-- Información del Recibo (se muestra después de seleccionar) -->
            <div id="reciboInfo" style="display: none; margin-top: 20px;">
                <div class="recibo-info-card">
                    <div class="info-row">
                        <span class="info-label">Cliente</span>
                        <span class="info-value" id="infoCliente">-</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Prenda a Despachar</span>
                        <span class="info-value" id="infoPrenda">-</span>
                    </div>
                </div>

                <!-- Selección de Tallas -->
                <div style="margin-top: 24px;">
                    <h3 class="form-label" style="margin-bottom: 16px;">Prendas por Talla para la Salida</h3>
                    <div id="tallasContainer" class="tallas-container">
                        <!-- Se llena dinámicamente -->
                    </div>
                </div>

                <!-- Campo de Novedad -->
                <div style="margin-top: 24px;">
                    <label class="form-label">Novedad (Opcional)</label>
                    <textarea 
                        id="inputNovedad" 
                        class="form-input" 
                        placeholder="Describe cualquier novedad o incidencia..."
                        style="resize: vertical; min-height: 80px; font-family: inherit;"
                    ></textarea>
                </div>

                <!-- Botón Registrar -->
                <button type="button" class="btn btn-primary" id="btnRegistrarSalida" style="margin-top: 24px; width: 100%;">
                    <span class="material-symbols-rounded">check_circle</span>
                    Registrar Movimiento
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

<!-- MODAL: FIRMAR MOVIMIENTO -->
<div class="modal" id="modalFirmaSalida">
    <div class="modal-content modal-firma-content">
        <div class="modal-header">
            <div>
                <h3 class="modal-title">Firmar Movimiento de Lavandería</h3>
                <p style="margin: 4px 0 0 0; font-size: 12px; color: #94a3b8; font-weight: 500;">Dibuja tu firma en el área de abajo</p>
            </div>
            <button type="button" class="modal-close">
                <span class="material-symbols-rounded">close</span>
            </button>
        </div>
        <div class="modal-body">
            <div class="signature-section">
                <div class="signature-canvas-wrapper">
                    <canvas id="signatureCanvas" width="500" height="200"></canvas>
                </div>
                
                <div class="signature-actions">
                    <button type="button" class="btn-clear-signature" id="btnLimpiarFirma">
                        <span class="material-symbols-rounded">refresh</span>
                        Limpiar
                    </button>
                </div>

                <div style="margin-top: 20px; display: flex; gap: 12px;">
                    <button type="button" class="btn btn-secondary" id="btnCancelarFirma" style="flex: 1;">
                        Cancelar
                    </button>
                    <button type="button" class="btn btn-primary" id="btnGuardarFirma" style="flex: 1;">
                        <span class="material-symbols-rounded">check_circle</span>
                        Guardar Firma
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: VER FIRMA -->
<div class="modal" id="modalVerFirma">
    <div class="modal-content modal-ver-firma-content">
        <div class="modal-header">
            <div>
                <h3 class="modal-title">Firma Registrada</h3>
            </div>
            <button type="button" class="modal-close">
                <span class="material-symbols-rounded">close</span>
            </button>
        </div>
        <div class="modal-body">
            <div class="firma-controls">
                <button type="button" class="btn-rotar-firma" id="btnRotarIzquierda" onclick="lavanderiaManager.rotarFirmaIzquierda()">
                    <span class="material-symbols-rounded">rotate_left</span>
                </button>
                <button type="button" class="btn-rotar-firma" id="btnRotarDerecha" onclick="lavanderiaManager.rotarFirmaDerecha()">
                    <span class="material-symbols-rounded">rotate_right</span>
                </button>
            </div>
            <div class="firma-preview-container-large">
                <img id="firmaImagenPreview" src="" alt="Firma" class="firma-preview-image-large">
            </div>
        </div>
    </div>
</div>

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/lavanderia.css') }}">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
@endpush

@push('scripts')
    <script>
        const apiSearchUrl = "{{ route('gestion-lavanderia.api.search-recibos') }}";
    </script>
    <script src="{{ asset('js/lavanderia/lavanderia.js') }}"></script>
@endpush
