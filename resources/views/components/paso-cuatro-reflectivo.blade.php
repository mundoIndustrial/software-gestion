<!-- PASO 3: REFLECTIVO -->
<div class="form-step" data-step="3">
    <div class="step-header">
        <h2 style="font-size: 1rem !important; margin: 0 0 0.2rem 0 !important;">PASO 3: REFLECTIVO</h2>
        <p style="font-size: 0.45rem !important; margin: 0 !important; color: #666 !important;">ESPECIFICA LOS DETALLES DEL REFLECTIVO</p>
    </div>

    <div class="form-section">
        <!-- DESCRIPCIÓN DEL REFLECTIVO -->
        <div class="form-group-large">
            <label for="descripcion_reflectivo"><i class="fas fa-pen"></i> DESCRIPCIÓN DEL REFLECTIVO</label>
            <textarea id="descripcion_reflectivo" name="descripcion_reflectivo" class="input-large" rows="3" placeholder="Describe el reflectivo que deseas (tipo, tamaño, color, etc.)..." style="width: 100%; padding: 12px; border: 2px solid #3498db; border-radius: 6px; font-size: 0.9rem; font-family: inherit;"></textarea>
            <small class="help-text">Incluye detalles sobre tipo de reflectivo, tamaño, color, posición, etc.</small>
        </div>

        <!-- IMÁGENES -->
        <div class="form-group-large">
            <label for="imagenes_reflectivo"><i class="fas fa-images"></i> IMÁGENES (MÁXIMO 5)</label>
            <div id="drop_zone_reflectivo" style="border: 2px dashed #3498db; border-radius: 8px; padding: 30px; text-align: center; background: #f0f7ff; cursor: pointer; margin-bottom: 10px;">
                <i class="fas fa-cloud-upload-alt" style="font-size: 2.5rem; color: #3498db; margin-bottom: 10px; display: block;"></i>
                <p style="margin: 10px 0; color: #3498db; font-weight: 600;">ARRASTRA IMÁGENES AQUÍ O HAZ CLIC</p>
                <p style="margin: 5px 0; color: #666; font-size: 0.9rem;">Máximo 5 imágenes</p>
                <input type="file" id="imagenes_reflectivo" name="imagenes_reflectivo[]" accept="image/*" multiple style="display: none;">
            </div>
            <div id="galeria_reflectivo" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 10px; margin-top: 10px;"></div>
        </div>

        <!-- UBICACIÓN -->
        <div style="background: #f9f9f9; border: 2px solid #3498db; border-radius: 8px; padding: 15px; margin-bottom: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                <label style="font-weight: bold; font-size: 1.1rem; margin: 0;">Ubicación</label>
                <button type="button" onclick="agregarUbicacionReflectivo()" style="background: #3498db; color: white; border: none; border-radius: 50%; width: 36px; height: 36px; cursor: pointer; font-size: 1.5rem; font-weight: bold; display: flex; align-items: center; justify-content: center; line-height: 1;">+</button>
            </div>
            
            <label for="seccion_reflectivo" style="display: block; margin-bottom: 8px; font-weight: 600; font-size: 0.9rem;">Selecciona o escribe la sección:</label>
            <input type="text" id="seccion_reflectivo" list="opciones_seccion_reflectivo" class="input-large" placeholder="Ej: PECHO, ESPALDA, MANGA..." style="width: 100%; margin-bottom: 12px; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
            <datalist id="opciones_seccion_reflectivo">
                <option value="PECHO">
                <option value="ESPALDA">
                <option value="MANGA">
                <option value="CUELLO">
                <option value="COSTADO">
                <option value="MÚLTIPLE">
            </datalist>
            
            <div id="ubicaciones_reflectivo_agregadas" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px;"></div>
        </div>

        <!-- OBSERVACIONES GENERALES -->
        <div style="background: #f9f9f9; border: 2px solid #3498db; border-radius: 8px; padding: 15px; margin-bottom: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <label style="font-weight: bold; font-size: 1.1rem; margin: 0;">Observaciones Generales</label>
                <button type="button" onclick="agregarObservacionReflectivo()" style="background: #3498db; color: white; border: none; border-radius: 50%; width: 36px; height: 36px; cursor: pointer; font-size: 1.5rem; font-weight: bold; display: flex; align-items: center; justify-content: center; line-height: 1;">+</button>
            </div>
            
            <div id="observaciones_reflectivo_lista" style="display: flex; flex-direction: column; gap: 10px;"></div>
        </div>
    </div>

    <div class="form-actions">
        <button type="button" class="btn-prev" onclick="if(typeof irAlPaso === 'function') irAlPaso(2)">
            <i class="fas fa-arrow-left"></i> ANTERIOR
        </button>
        <button type="button" class="btn-next" onclick="if(typeof irAlPaso === 'function') irAlPaso(4)">
            REVISAR <i class="fas fa-arrow-right"></i>
        </button>
    </div>
</div>
