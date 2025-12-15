<!-- PASO 4: REVISAR -->
<div class="form-step" data-step="4">
    <div class="step-header">
        <h2 style="font-size: 1rem !important; margin: 0 0 0.2rem 0 !important;">PASO 4: REVISAR COTIZACIÓN</h2>
        <p style="font-size: 0.8rem !important; margin: 0 !important; color: #666 !important;">VERIFICA QUE TODO ESTÉ CORRECTO</p>
    </div>

    <div class="form-section">
        <!-- RESUMEN CLIENTE -->
        <div style="background: #f0f7ff; border: 2px solid #3498db; border-radius: 8px; padding: 15px; margin-bottom: 20px;">
            <h3 style="margin: 0 0 10px 0; color: #0066cc;">📋 INFORMACIÓN DEL CLIENTE</h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div>
                    <p style="margin: 0; font-size: 0.9rem; color: #666;"><strong>Cliente:</strong></p>
                    <p style="margin: 5px 0 0 0; font-size: 1rem;" id="resumen_cliente">-</p>
                </div>
                <div>
                    <p style="margin: 0; font-size: 0.9rem; color: #666;"><strong>Asesor/a:</strong></p>
                    <p style="margin: 5px 0 0 0; font-size: 1rem;">{{ Auth::user()->name }}</p>
                </div>
                <div>
                    <p style="margin: 0; font-size: 0.9rem; color: #666;"><strong>Fecha:</strong></p>
                    <p style="margin: 5px 0 0 0; font-size: 1rem;" id="resumen_fecha">-</p>
                </div>
                <div>
                    <p style="margin: 0; font-size: 0.9rem; color: #666;"><strong>Tipo:</strong></p>
                    <p style="margin: 5px 0 0 0; font-size: 1rem;" id="resumen_tipo">-</p>
                </div>
            </div>
        </div>

        <!-- RESUMEN PRENDAS -->
        <div style="background: #f0f7ff; border: 2px solid #3498db; border-radius: 8px; padding: 15px; margin-bottom: 20px;">
            <h3 style="margin: 0 0 10px 0; color: #0066cc;">👕 PRENDAS</h3>
            <div id="resumen_prendas" style="display: grid; gap: 10px;"></div>
        </div>

        <!-- RESUMEN LOGO/TÉCNICAS -->
        <div style="background: #f0f7ff; border: 2px solid #3498db; border-radius: 8px; padding: 15px; margin-bottom: 20px;">
            <h3 style="margin: 0 0 10px 0; color: #0066cc;">🎨 LOGO/BORDADO/TÉCNICAS</h3>
            <div>
                <p style="margin: 0 0 10px 0; font-size: 0.9rem;"><strong>Descripción:</strong></p>
                <p style="margin: 0; font-size: 0.9rem; color: #666;" id="resumen_logo_desc">-</p>
            </div>
            <div style="margin-top: 10px;">
                <p style="margin: 0 0 10px 0; font-size: 0.9rem;"><strong>Técnicas:</strong></p>
                <div id="resumen_tecnicas" style="display: flex; flex-wrap: wrap; gap: 8px;"></div>
            </div>
        </div>


    </div>

    <div class="form-actions">
        <button type="button" class="btn-prev" onclick="irAlPaso(4)">
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
