<!-- PASO 4 -->
<div class="form-step" data-step="4">
    <div class="step-header">
        <h2>PASO 4: REVISAR COTIZACIÓN</h2>
        <p>VERIFICA QUE TODO ESTÉ CORRECTO</p>
    </div>

    <div class="form-section">
        <div style="background: #f0f7ff; border-left: 4px solid #3498db; padding: 15px; border-radius: 4px; margin-bottom: 20px;">
            <p style="margin: 0; color: #333;"><strong>✓ Resumen de tu cotización:</strong></p>
            <ul style="margin: 10px 0 0 0; padding-left: 20px; color: #666;">
                <li>Cliente: <strong id="resumenCliente">-</strong></li>
                <li>Productos: <strong id="resumenProductos">0</strong></li>
                <li>Asesora: <strong>{{ Auth::user()->name }}</strong></li>
                <li>Fecha: <strong id="resumenFecha"></strong></li>
            </ul>
        </div>
    </div>

    <div class="form-actions">
        <button type="button" class="btn-prev" onclick="irAlPaso(3)">
            <i class="fas fa-arrow-left"></i> ANTERIOR
        </button>
        <div style="display: flex; gap: 10px;">
            <button type="button" class="btn-submit" id="btnGuardarBorrador" onclick="guardarCotizacion()" style="background: #95a5a6;">
                <i class="fas fa-save"></i> GUARDAR (BORRADOR)
            </button>
            <button type="button" class="btn-submit" id="btnEnviar" onclick="enviarCotizacion()">
                <i class="fas fa-paper-plane"></i> ENVIAR
            </button>
        </div>
    </div>
</div>
