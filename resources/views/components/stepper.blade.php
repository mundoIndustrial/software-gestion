<div class="stepper-container">
    <div class="stepper">
        <div class="step active" data-step="1" onclick="if(typeof irAlPaso === 'function') irAlPaso(1)" style="cursor: pointer;">
            <div class="step-number">1</div>
            <div class="step-label">CLIENTE</div>
        </div>
        <div class="step-line"></div>
        <div class="step" data-step="2" onclick="if(typeof irAlPaso === 'function') irAlPaso(2)" style="cursor: pointer;">
            <div class="step-number">2</div>
            <div class="step-label">PRENDAS</div>
        </div>
        
        <!-- PASO 3 (LOGO) - Se muestra solo para cotizaciones no-prenda -->
        <div class="step-line" id="step-line-3" style="display: none;"></div>
        <div class="step" id="step-3" data-step="3" onclick="if(typeof irAlPaso === 'function') irAlPaso(3)" style="cursor: pointer; display: none;">
            <div class="step-number">3</div>
            <div class="step-label">LOGO</div>
        </div>
        
        <!-- PASO 4 (REVISAR) - Siempre visible -->
        <div class="step-line" id="step-line-4"></div>
        <div class="step" data-step="4" onclick="if(typeof irAlPaso === 'function') irAlPaso(4)" style="cursor: pointer;">
            <div class="step-number" id="step-4-number">4</div>
            <div class="step-label">REVISAR</div>
        </div>
    </div>
</div>
