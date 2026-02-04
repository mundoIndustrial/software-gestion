<!-- PASO 4: REVISAR COTIZACIÓN COMBINADA COMPLETA -->
<div class="form-step" data-step="4">
    <div class="paso5-header">
        <h2>Revisar Cotización</h2>
        <p>Verifica que toda la información esté correcta antes de enviar</p>
    </div>

    <div class="form-section">
        <!-- INFORMACIÓN DEL CLIENTE (HEADER) -->
        <div class="resumen-seccion">
            <h3>INFORMACIÓN DEL CLIENTE</h3>
            <div class="info-grid">
                <div class="info-item">
                    <label>Cliente</label>
                    <div class="value" id="resumen_cliente">-</div>
                </div>
                <div class="info-item">
                    <label>Asesor/a</label>
                    <div class="value">{{ Auth::user()->name }}</div>
                </div>
                <div class="info-item">
                    <label>Fecha</label>
                    <div class="value" id="resumen_fecha">-</div>
                </div>
                <div class="info-item">
                    <label>Tipo de Cotización</label>
                    <div class="value" id="resumen_tipo">-</div>
                </div>
            </div>
        </div>

        <!-- CARDS DE PRENDAS CON TABLAS DINÁMICAS -->
        <div id="resumen_prendas_cards" style="display: grid; gap: 20px; margin-bottom: 20px;"></div>

        <!-- FALLBACK: LOGO GENERAL SI NO HAY PRENDAS -->
        <div id="resumen_logo_general_container" style="display: none;">
            <div class="resumen-seccion logo-section">
                <h3>LOGO / BORDADO / TÉCNICAS</h3>
                <div id="resumen_logo_general_content"></div>
            </div>
        </div>
    </div>

    <div class="form-actions">
        <button type="button" class="btn-prev" onclick="if(typeof irAlPaso === 'function') irAlPaso(3)">
            <i class="fas fa-arrow-left"></i> ANTERIOR
        </button>
        <div style="display: flex; gap: 10px;">
            <button type="button" class="btn-submit" id="btnGuardarBorrador" onclick="guardarCotizacion()" style="background: #95a5a6;">
                <i class="fas fa-save"></i> GUARDAR COMO BORRADOR
            </button>
            <button type="button" class="btn-submit" id="btnEnviarCotizacion" onclick="enviarCotizacion()" style="background: #27ae60;">
                <i class="fas fa-paper-plane"></i> ENVIAR COTIZACIÓN
            </button>
        </div>
    </div>
</div>
