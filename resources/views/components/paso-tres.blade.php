<!-- PASO 3: LOGO -->
<div class="form-step" data-step="3">
    <div class="step-header" style="display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h2 style="font-size: 1rem !important; margin: 0 0 0.2rem 0 !important;">PASO 3: LOGO / BORDADO</h2>
            <p style="font-size: 0.8rem !important; margin: 0 !important; color: #666 !important;">ESPECIFICA LOS DETALLES DE BORDADO Y ESTAMPADO</p>
        </div>
        
        <!-- Selector de tipo de venta en la esquina derecha -->
        <div style="display: flex; align-items: center; gap: 8px; background: linear-gradient(135deg, #0066cc, #0052a3); border: 2px solid #0052a3; border-radius: 8px; padding: 0.8rem 1.2rem; box-shadow: 0 4px 12px rgba(0, 102, 204, 0.3);">
            <label for="tipo_venta_paso3" style="font-weight: 700; font-size: 0.85rem; color: white; white-space: nowrap; display: flex; align-items: center; gap: 6px; margin: 0;">
                <i class="fas fa-tag"></i> Tipo
            </label>
            <select id="tipo_venta_paso3" name="tipo_venta_paso3" style="padding: 0.5rem 0.6rem; border: 2px solid white; border-radius: 6px; font-size: 0.85rem; cursor: pointer; background-color: white; text-align: center; color: #0066cc; font-weight: 600; min-width: 80px;">
                <option value="">Selecciona</option>
                <option value="M">M</option>
                <option value="D">D</option>
                <option value="X">X</option>
            </select>
        </div>
    </div>

    <div class="form-section">
        <!-- DESCRIPCIÓN DEL LOGO/BORDADO -->
        <div class="form-group-large">
            <label for="descripcion_logo"><i class="fas fa-pen"></i> DESCRIPCIÓN DEL LOGO/BORDADO</label>
            <textarea id="descripcion_logo" name="descripcion_logo" class="input-large" rows="3" placeholder="Describe el logo, bordado o estampado que deseas..." style="width: 100%; padding: 12px; border: 2px solid #3498db; border-radius: 6px; font-size: 0.9rem; font-family: inherit;"></textarea>
            <small class="help-text">Incluye detalles sobre colores, tamaño, posición, etc.</small>
        </div>

        <!-- IMÁGENES -->
        <div class="form-group-large">
            <label for="imagenes_bordado"><i class="fas fa-images"></i> IMÁGENES (MÁXIMO 5)</label>
            <div id="drop_zone_imagenes" style="border: 2px dashed #3498db; border-radius: 8px; padding: 30px; text-align: center; background: #f0f7ff; cursor: pointer; margin-bottom: 10px;">
                <i class="fas fa-cloud-upload-alt" style="font-size: 2.5rem; color: #3498db; margin-bottom: 10px; display: block;"></i>
                <p style="margin: 10px 0; color: #3498db; font-weight: 600;">ARRASTRA IMÁGENES AQUÍ O HAZ CLIC</p>
                <p style="margin: 5px 0; color: #666; font-size: 0.9rem;">Máximo 5 imágenes</p>
                <input type="file" id="imagenes_bordado" name="imagenes_bordado[]" accept="image/*" multiple style="display: none;">
            </div>
            <div id="galeria_imagenes" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 10px; margin-top: 10px;"></div>
        </div>

        <!-- TÉCNICAS -->
        <div style="background: #f9f9f9; border: 2px solid #3498db; border-radius: 8px; padding: 15px; margin-bottom: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                <label style="font-weight: bold; font-size: 1.1rem; margin: 0;">Técnicas disponibles</label>
                <button type="button" onclick="agregarTecnica()" style="background: #3498db; color: white; border: none; border-radius: 50%; width: 36px; height: 36px; cursor: pointer; font-size: 1.5rem; font-weight: bold; display: flex; align-items: center; justify-content: center; line-height: 1;">+</button>
            </div>
            
            <select id="selector_tecnicas" class="input-large" style="width: 100%; margin-bottom: 10px; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" onchange="if(this.value) { agregarTecnica(); }">
                <option value="">-- SELECCIONA UNA TÉCNICA --</option>
                <option value="BORDADO">BORDADO</option>
                <option value="DTF">DTF</option>
                <option value="ESTAMPADO">ESTAMPADO</option>
                <option value="SUBLIMADO">SUBLIMADO</option>
            </select>
            
            <div id="tecnicas_seleccionadas" style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 12px; min-height: 30px;"></div>
            
            <label style="display: block; margin-bottom: 8px; font-weight: 600; font-size: 0.9rem;">Observaciones</label>
            <textarea id="observaciones_tecnicas" name="observaciones_tecnicas" class="input-large" rows="2" placeholder="Observaciones..." style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem;"></textarea>
        </div>

        <!-- UBICACIÓN -->
        <div style="background: #f9f9f9; border: 2px solid #3498db; border-radius: 8px; padding: 15px; margin-bottom: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                <label style="font-weight: bold; font-size: 1.1rem; margin: 0;">Ubicación</label>
                <button type="button" onclick="agregarSeccion()" style="background: #3498db; color: white; border: none; border-radius: 50%; width: 36px; height: 36px; cursor: pointer; font-size: 1.5rem; font-weight: bold; display: flex; align-items: center; justify-content: center; line-height: 1;">+</button>
            </div>
            
            <label for="seccion_prenda" style="display: block; margin-bottom: 8px; font-weight: 600; font-size: 0.9rem;">Selecciona la sección a agregar:</label>
            <select id="seccion_prenda" class="input-large" style="width: 100%; margin-bottom: 12px; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                <option value="">-- SELECCIONA UNA OPCIÓN --</option>
                <option value="CAMISA">CAMISA</option>
                <option value="JEAN_SUDADERA">JEAN/SUDADERA</option>
                <option value="GORRAS">GORRAS</option>
            </select>
            
            <div id="secciones_agregadas" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px;"></div>
        </div>

        <!-- OBSERVACIONES GENERALES -->
        <div style="background: #f9f9f9; border: 2px solid #3498db; border-radius: 8px; padding: 15px; margin-bottom: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <label style="font-weight: bold; font-size: 1.1rem; margin: 0;">Observaciones Generales</label>
                <button type="button" onclick="agregarObservacion()" style="background: #3498db; color: white; border: none; border-radius: 50%; width: 36px; height: 36px; cursor: pointer; font-size: 1.5rem; font-weight: bold; display: flex; align-items: center; justify-content: center; line-height: 1;">+</button>
            </div>
            
            <div id="observaciones_lista" style="display: flex; flex-direction: column; gap: 10px;"></div>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Los selectores de tipo_venta en PASO 2 y PASO 3 son independientes
    // No se sincronizan automáticamente para permitir valores diferentes
    console.log('✅ Selectores tipo_venta configurados como independientes');
});
</script>

