<!-- PASO 5: REVISAR -->
<div class="form-step" data-step="5">
    <div class="step-header">
        <h2 style="font-size: 1rem !important; margin: 0 0 0.2rem 0 !important;">PASO 5: REVISAR COTIZACIÓN</h2>
        <p style="font-size: 0.8rem !important; margin: 0 !important; color: #666 !important;">VERIFICA QUE TODO ESTÉ CORRECTO</p>
    </div>

    <div class="form-section">
        <!-- RESUMEN CLIENTE -->
        <div style="background: #f0f7ff; border: 2px solid #3498db; border-radius: 8px; padding: 15px; margin-bottom: 20px;">
            <h3 style="margin: 0 0 15px 0; color: #0066cc; font-size: 1.1rem; font-weight: 700; letter-spacing: 0.5px;">INFORMACIÓN DEL CLIENTE</h3>
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
            <h3 style="margin: 0 0 15px 0; color: #0066cc; font-size: 1.1rem; font-weight: 700; letter-spacing: 0.5px;">PRENDAS</h3>
            <div id="resumen_prendas" style="display: grid; gap: 10px;"></div>
        </div>

        <!-- RESUMEN UBICACIONES -->
        <div style="background: #f0f7ff; border: 2px solid #3498db; border-radius: 8px; padding: 15px; margin-bottom: 20px; display: none;" id="resumen_ubicaciones_container">
            <h3 style="margin: 0 0 15px 0; color: #0066cc; font-size: 1.1rem; font-weight: 700; letter-spacing: 0.5px;">UBICACIONES</h3>
            <div id="resumen_ubicaciones" style="display: grid; gap: 10px;"></div>
        </div>

        <!-- RESUMEN ESPECIFICACIONES -->
        <div style="background: #f0f7ff; border: 2px solid #3498db; border-radius: 8px; padding: 15px; margin-bottom: 20px; display: none;" id="resumen_especificaciones_container">
            <h3 style="margin: 0 0 15px 0; color: #0066cc; font-size: 1.1rem; font-weight: 700; letter-spacing: 0.5px;">ESPECIFICACIONES</h3>
            <div id="resumen_especificaciones" style="display: grid; gap: 10px;"></div>
        </div>

        <!-- RESUMEN LOGO/BORDADO/TÉCNICAS -->
        <div style="background: #f0f7ff; border: 2px solid #3498db; border-radius: 8px; padding: 15px; margin-bottom: 20px;">
            <h3 style="margin: 0 0 15px 0; color: #0066cc; font-size: 1.1rem; font-weight: 700; letter-spacing: 0.5px;">LOGO / BORDADO</h3>
            
            <!-- Descripción -->
            <div style="margin-bottom: 15px;">
                <p style="margin: 0 0 5px 0; font-size: 0.9rem;"><strong>Descripción:</strong></p>
                <p style="margin: 0; font-size: 0.95rem; color: #555; padding: 8px 12px; background: #fff; border-left: 3px solid #3498db; border-radius: 4px;" id="resumen_logo_desc">-</p>
            </div>
            
            <!-- Técnicas -->
            <div style="margin-bottom: 15px;">
                <p style="margin: 0 0 8px 0; font-size: 0.9rem;"><strong>Técnicas:</strong></p>
                <div id="resumen_tecnicas" style="display: flex; flex-wrap: wrap; gap: 8px; min-height: 30px; align-items: center;"></div>
            </div>
            
            <!-- Ubicaciones -->
            <div style="display: none;" id="resumen_logo_ubicaciones_container">
                <p style="margin: 0 0 10px 0; font-size: 0.9rem;"><strong>Ubicaciones:</strong></p>
                <div id="resumen_logo_ubicaciones" style="display: grid; gap: 10px;"></div>
            </div>
        </div>

        <!-- RESUMEN REFLECTIVO -->
        <div style="background: #fff3e0; border: 2px solid #ff9800; border-radius: 8px; padding: 15px; margin-bottom: 20px;" id="resumen_reflectivo_container">
            <h3 style="margin: 0 0 15px 0; color: #e65100; font-size: 1.1rem; font-weight: 700; letter-spacing: 0.5px;">🔸 REFLECTIVO</h3>
            
            <!-- Prendas Reflectivo -->
            <div id="resumen_reflectivo_prendas" style="display: grid; gap: 15px;"></div>
        </div>

    </div>

    <div class="form-actions">
        <button type="button" class="btn-prev" onclick="if(typeof irAlPaso === 'function') irAlPaso(4)">
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
