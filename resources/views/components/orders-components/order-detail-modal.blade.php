<link rel="stylesheet" href="{{ asset('css/order-detail-modal.css') }}">
<link rel="stylesheet" href="{{ asset('css/print-order-detail-modal.css') }}" media="print">
<style>
/* Estilos específicos para ancho y metraje */
.order-ancho-metraje {
    position: absolute !important;
    top: 460px !important;
    left: 30px !important;
    right: 30px !important;
    padding: 8px !important;
    font-family: 'Courier New', monospace !important;
    font-weight: bold !important;
    font-size: 0.75rem !important;
    letter-spacing: 2px !important;
    color: #333 !important;
    background: transparent !important;
    z-index: 5 !important;
    border-top: 1px solid #e5e5e5 !important;
    padding-top: 15px !important;
    box-sizing: border-box !important;
}

.ancho-metraje-container {
    display: flex;
    justify-content: space-between !important;
    align-items: flex-end !important;
    gap: 60px !important;
    width: 100% !important;
}

.ancho-metraje-container.hidden-view {
    display: none !important;
}

.ancho-column {
    flex: 0 0 auto !important;
    text-align: left !important;
    white-space: nowrap !important;
    display: flex !important;
    align-items: flex-end !important;
    height: 100% !important;
}

.metraje-column {
    flex: 1 !important;
    text-align: right !important;
    display: flex !important;
    flex-direction: column !important;
    align-items: flex-end !important;
}

.metraje-label {
    display: block !important;
    margin-bottom: 4px !important;
}

#metrajes-por-color-container {
    display: flex !important;
    flex-direction: column !important;
    align-items: flex-end !important;
    gap: 2px !important;
}

#metrajes-por-color-container span {
    display: block !important;
    color: red !important;
    font-weight: bold !important;
    white-space: nowrap !important;
}

.order-ancho-metraje .ancho-valor,
.order-ancho-metraje .metraje-valor {
    margin-left: 5px !important;
    font-weight: bold !important;
}

#ancho-valor {
    color: red !important;
}

#ancho-metraje-mano,
#ancho-metraje-mano #contenido-mano,
#ancho-metraje-mano #observaciones-mano {
    color: red !important;
}
</style>

<div class="order-detail-modal-container" style="display: flex; flex-direction: column; width: 100%; height: 100%;">
    <div class="order-detail-card">
        <img src="{{ asset('images/logo.png') }}" alt="Mundo Industrial Logo" class="order-logo" width="150" height="80">
        <div id="order-date" class="order-date">
            <div class="fec-label">FECHA</div>
            <div class="date-boxes">
                <div class="date-box day-box"></div>
                <div class="date-box month-box"></div>
                <div class="date-box year-box"></div>
            </div>
        </div>
        <div id="order-asesora" class="order-asesora">ASESOR: <span id="asesora-value"></span></div>
        <div id="order-forma-pago" class="order-forma-pago">FORMA DE PAGO: <span id="forma-pago-value"></span></div>
        <div id="order-cliente" class="order-cliente">CLIENTE: <span id="cliente-value"></span></div>
        <div id="order-descripcion" class="order-descripcion">
            <div id="descripcion-text"></div>
        </div>
        <h2 id="receipt-title" class="receipt-title">RECIBO DE COSTURA</h2>
        <div class="arrow-container">
            <button id="prev-arrow" class="arrow-btn" style="display: none;">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
            </button>
            <button id="next-arrow" class="arrow-btn" style="display: none;">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
            </button>
        </div>
        <div id="order-pedido" class="pedido-number"></div>
        
        <!-- Elementos para ReceiptManager -->
        <div id="receipt-number" style="display: none;"></div>
        <div id="receipt-total" style="display: none;"></div>
        <div id="receipt-day" style="display: none;"></div>
        <div id="receipt-month" style="display: none;"></div>
        <div id="receipt-year" style="display: none;"></div>

        <div class="separator-line"></div>

        <!-- Línea de ancho y metraje con dos columnas separadas -->
        <div id="order-ancho-metraje" class="order-ancho-metraje" style="display: none;">
            <!-- VISTA NORMAL: Dos columnas -->
            <div id="ancho-metraje-normal" class="ancho-metraje-container">
                <div class="ancho-column">
                    <span>Ancho: <span id="ancho-valor" class="ancho-valor">--</span></span>
                </div>
                <div class="metraje-column">
                    <span class="metraje-label">Metraje: <span id="metraje-valor" class="metraje-valor">--</span></span>
                    <div id="metrajes-por-color-container"></div>
                </div>
            </div>
            
            <!-- VISTA A MANO: Texto libre -->
            <div id="ancho-metraje-mano" class="ancho-metraje-mano" style="display: none; padding: 12px; background: #f3f4f6; border-radius: 6px; border-left: 4px solid #d1d5db;">
                <div id="contenido-mano" class="text-sm whitespace-pre-wrap text-gray-800"></div>
                <div id="observaciones-mano" class="text-xs text-gray-600 mt-2 border-t border-gray-300 pt-2" style="display: none;">
                    <strong>Observaciones:</strong>
                    <div id="contenido-observaciones" class="whitespace-pre-wrap mt-1"></div>
                </div>
            </div>
        </div>

        <div class="signature-section">
            <div class="signature-field">
                <span>ENCARGADO DE ORDEN:</span>
                <span id="encargado-value"></span>
            </div>
            <div class="vertical-separator"></div>
            <div class="signature-field">
                <span>PRENDAS ENTREGADAS:</span>
                <span id="prendas-entregadas-value"></span>
                <a href="#" id="ver-entregas" style="color: red; font-weight: bold;">VER ENTREGAS</a>
            </div>
        </div>
    </div>
</div>

<!-- Botones flotantes para cambiar a galería de fotos -->
<div id="floating-buttons-container" style="position: absolute; left: calc(50% + 334px + 1cm); top: 50%; transform: translateY(-50%); display: flex; flex-direction: column; gap: 12px; z-index: 10000; pointer-events: auto;">
    <button id="btn-factura" type="button" title="Ver factura" onclick="toggleFactura()" style="width: 56px; height: 56px; border-radius: 50%; background: linear-gradient(135deg, #1e40af, #0ea5e9); border: none; color: white; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 24px; transition: all 0.3s ease; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15); pointer-events: auto;">
        <i class="fas fa-receipt"></i>
    </button>
    <button id="btn-galeria" type="button" title="Ver galería" onclick="toggleFactura()" style="width: 56px; height: 56px; border-radius: 50%; background: white; border: 2px solid #ddd; color: #333; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 24px; transition: all 0.3s ease; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); pointer-events: auto;">
        <i class="fas fa-images"></i>
    </button>
    <button id="btn-print-receipt" type="button" title="Imprimir" onclick="window.printReceiptModal && window.printReceiptModal()" style="width: 56px; height: 56px; border-radius: 50%; background: linear-gradient(135deg, #27ae60, #229954); border: none; color: white; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 24px; transition: all 0.3s ease; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15); pointer-events: auto;">
        <i class="fas fa-print"></i>
    </button>
</div>


