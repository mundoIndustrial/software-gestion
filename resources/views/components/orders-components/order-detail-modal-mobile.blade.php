<link rel="stylesheet" href="{{ asset('css/order-detail-modal-mobile.css') }}">

<style>
    #mobile-numero-pedido {
        top: 120px !important;
        right: 12px !important;
    }

    @media (max-width: 768px) {
        .order-detail-modal-container--mobile-full {
            padding: 2px !important;
            margin: 0 !important;
            width: 100vw !important;
            max-width: 100% !important;
            justify-content: stretch !important;
            box-sizing: border-box !important;
        }

        .order-detail-card--mobile-full {
            width: 100% !important;
            max-width: 100% !important;
            margin: 0 auto !important;
        }
    }
</style>

<div class="order-detail-modal-container order-detail-modal-container--mobile-full" style="
    max-width: 100%;
    padding: 0.5rem;
    display: flex;
    justify-content: center;
    align-items: flex-start;
    min-height: 100vh;
    background: transparent;
">
    <div class="order-detail-card order-detail-card--mobile-full" style="
        position: relative;
        width: 100%;
        max-width: 600px;
        margin: 20px auto;
        background: white;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    ">
        <!-- Logo -->
        <img src="{{ asset('images/logo2.png') }}" alt="Mundo Industrial Logo" class="order-logo" width="150" height="80">
        
        <!-- Boton de navegacion de procesos (esquina superior derecha) -->
        <div id="process-navigation-mobile" style="position: absolute; top: 15px; right: 15px; display: none; z-index: 100;"></div>
        
        <!-- Fecha -->
        <div id="order-date" class="order-date">
            <div class="fec-label">FECHA</div>
            <div class="date-boxes">
                <div class="date-box day-box" id="fecha-dia"></div>
                <div class="date-box month-box" id="fecha-mes"></div>
                <div class="date-box year-box" id="fecha-year"></div>
            </div>
        </div>
        
        <!-- Información Básica -->
        <div id="order-asesora" class="order-asesora">ASESORA: <span id="mobile-asesora"></span></div>
        <div id="order-forma-pago" class="order-forma-pago">FORMA DE PAGO: <span id="mobile-forma-pago"></span></div>
        <div id="order-cliente" class="order-cliente">CLIENTE: <span id="mobile-cliente"></span></div>
        
        <!-- Descripcion -->
        <div id="order-descripcion" class="order-descripcion" style="margin-bottom: 50px;">
            <div id="mobile-descripcion"></div>
        </div>
        
        <!-- Titulo Recibo -->
        <h2 class="receipt-title" id="receipt-title-mobile">RECIBO DE COSTURA</h2>
        
        <!-- Numero Pedido -->
        <div class="pedido-number" id="mobile-numero-pedido"></div>

        <!-- Ancho y Metraje (ANTES del separador) - VISTA NORMAL -->
        <div id="order-ancho-metraje" class="order-ancho-metraje" style="display: none; padding: 15px; border-bottom: 1px solid #e5e7eb; background: #f9fafb;">
            <div class="ancho-metraje-container" style="display: flex; gap: 30px;">
                <div class="ancho-column" style="flex: 1;">
                    <span style="display: block; font-weight: 600; color: #333; font-size: 0.9rem;">Ancho: <span id="ancho-valor-mobile" class="ancho-valor" style="color: #d32f2f; font-weight: bold;">-</span></span>
                </div>
                <div class="metraje-column" style="flex: 1;">
                    <span class="metraje-label" style="display: block; font-weight: 600; color: #333; font-size: 0.9rem;">Metraje: <span id="metraje-valor-mobile" class="metraje-valor" style="color: #d32f2f; font-weight: bold;">-</span></span>
                    <div id="metrajes-por-color-container-mobile" style="margin-top: 5px; font-size: 0.8rem; color: #666;"></div>
                </div>
            </div>
        </div>

        <!-- Contenido a Mano (VISTA MANUAL) -->
        <div id="order-ancho-metraje-mano" class="order-ancho-metraje-mano" style="display: none; padding: 12px; background: rgb(243, 244, 246); border-radius: 6px; border-left: 4px solid rgb(209, 213, 219); margin-top: 15px; margin-bottom: 15px;">
            <div id="contenido-mano-mobile" class="text-sm whitespace-pre-wrap text-gray-800" style="font-size: 0.875rem; white-space: pre-wrap; color: #374151; line-height: 1.5;"></div>
            <div id="observaciones-mano-mobile" class="text-xs text-gray-600" style="display: none; font-size: 0.75rem; color: #6b7280; margin-top: 8px; border-top: 1px solid rgb(209, 213, 219); padding-top: 8px;">
                <strong>Observaciones:</strong>
                <div id="contenido-observaciones-mobile" class="whitespace-pre-wrap" style="white-space: pre-wrap; margin-top: 4px;"></div>
            </div>


        </div>

        <!-- Separador removido -->
        <!-- Footer removido -->
    </div>
</div>

@vite('resources/js/orders/mobile/order-detail-mobile-modal.js')






