<!-- PASO 1 -->
<div class="form-step active" data-step="1">
    <div class="step-header">
        <h2 style="font-size: 1rem !important; margin: 0 0 0.2rem 0 !important;">PASO 1: INFORMACIÓN DEL CLIENTE</h2>
        <p style="font-size: 0.8rem !important; margin: 0 !important; color: #666 !important;">CUÉNTANOS QUIÉN ES TU CLIENTE</p>
    </div>

    <div style="background: #f0f7ff; border-left: 4px solid #3498db; padding: 15px; margin-bottom: 20px; border-radius: 4px;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
            <div>
                <p style="margin: 0; font-size: 0.9rem; color: #666;">
                    <strong>{{ Auth::user()->genero === 'F' ? 'ASESORA COMERCIAL' : 'ASESOR COMERCIAL' }}:</strong>
                    {{ Auth::user()->name }}
                </p>
            </div>
            <div>
                <p style="margin: 0; font-size: 0.9rem; color: #374151;">
                    <strong>FECHA:</strong>
                    <label for="fechaActual" style="display: inline-block; font-weight: 600; margin: 0 4px;"></label>
                    <input type="date" id="fechaActual" name="fecha_cotizacion" style="border: 1px solid #ccc; padding: 4px 8px; border-radius: 4px; font-size: 0.9rem; cursor: pointer;" aria-label="Fecha de cotización">
                </p>
            </div>
        </div>
    </div>

    <div class="form-section">
        <div class="form-group-large">
            <label for="cliente"><i class="fas fa-user"></i> NOMBRE DEL CLIENTE *</label>
            <input type="text" id="cliente" name="cliente" class="input-large" placeholder="EJ: JUAN GARCÍA, EMPRESA ABC..." value="{{ isset($esEdicion) && $esEdicion && isset($cotizacion) ? $cotizacion->cliente : '' }}" required aria-label="Nombre del cliente o empresa">
            <small class="help-text">EL NOMBRE DE TU CLIENTE O EMPRESA</small>
        </div>
    </div>

    <div class="form-actions">
        <button type="button" class="btn-next" onclick="irAlPaso(2)">
            SIGUIENTE <i class="fas fa-arrow-right"></i>
        </button>
    </div>
</div>
