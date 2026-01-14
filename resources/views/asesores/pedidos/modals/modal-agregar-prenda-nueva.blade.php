<!-- MODAL: Agregar Prenda Nueva (Sin Cotización) -->
<div id="modal-agregar-prenda-nueva" class="modal-overlay">
    <div class="modal-container modal-xl">
        <!-- Header -->
        <div class="modal-header modal-header-primary">
            <h3 class="modal-title">
                <span class="material-symbols-rounded">add_box</span>Agregar Prenda Nueva
            </h3>
            <button class="modal-close-btn" onclick="cerrarModalPrendaNueva()">
                <span class="material-symbols-rounded">close</span>
            </button>
        </div>
        
        <!-- Body -->
        <div class="modal-body">
            <form id="form-prenda-nueva">
                <!-- Layout en 2 columnas: Datos a la izquierda, Fotos a la derecha -->
                <div class="form-prenda-grid">
                    <!-- COLUMNA IZQUIERDA: Datos principales -->
                    <div>
                        <!-- Primera fila: Nombre y Origen -->
                        <div class="form-row-2col">
                            <!-- Nombre de la prenda -->
                            <div class="form-group">
                                <label class="form-label-primary">
                                    <span class="material-symbols-rounded">checkroom</span>NOMBRE DE LA PRENDA *
                                </label>
                                <input type="text" id="nueva-prenda-nombre" required placeholder="Ej: CAMISA DRILL, POLO, PANTALÓN..." class="form-input">
                            </div>
                            
                            <!-- Origen -->
                            <div class="form-group">
                                <label class="form-label-primary">
                                    <span class="material-symbols-rounded">location_on</span>ORIGEN *
                                </label>
                                <select id="nueva-prenda-origen-select" class="form-input">
                                    <option value="bodega">Bodega</option>
                                    <option value="confeccion">Confección</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Descripción -->
                        <div class="form-group">
                            <label class="form-label-primary">
                                <span class="material-symbols-rounded">description</span>DESCRIPCIÓN
                            </label>
                            <textarea id="nueva-prenda-descripcion" placeholder="Descripción de la prenda, detalles especiales..." class="form-textarea"></textarea>
                        </div>
                    </div>

                    <!-- COLUMNA DERECHA: Fotos de la Prenda -->
                    <div class="foto-panel">
                        <label class="foto-panel-label">
                            <span class="material-symbols-rounded">photo_camera</span>FOTOS
                        </label>
                        
                        <!-- Imagen principal preview -->
                        <div id="nueva-prenda-foto-preview" class="foto-preview">
                            <div class="foto-preview-content">
                                <div class="material-symbols-rounded">add_photo_alternate</div>
                                <div class="foto-preview-text">Agregar</div>
                            </div>
                        </div>
                        
                        <!-- Contador de fotos -->
                        <div id="nueva-prenda-foto-contador" class="foto-counter"></div>
                        
                        <!-- Input de archivos -->
                        <input type="file" id="nueva-prenda-foto-input" accept="image/*" style="display: none;" onchange="if(this.files.length > 0) { manejarImagenesPrenda(this); }">
                        
                        <!-- Botón agregar más fotos -->
                        <button type="button" id="nueva-prenda-foto-btn" class="btn btn-sm btn-primary">
                            + Agregar
                        </button>
                    </div>
                </div>

                <!-- Color, Tela, Referencia e Imágenes de Tela -->
                <div class="form-section">
                    <label class="form-label-primary">
                        <span class="material-symbols-rounded">palette</span>COLOR, TELA Y REFERENCIA
                    </label>
                    
                    <!-- Tabla de telas -->
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse; margin-bottom: 1rem;">
                            <thead>
                                <tr style="background: #0066cc; border-bottom: 2px solid #0066cc;">
                                    <th style="padding: 0.75rem; text-align: left; font-weight: 600; font-size: 0.875rem; color: white;">Tela</th>
                                    <th style="padding: 0.75rem; text-align: left; font-weight: 600; font-size: 0.875rem; color: white;">Color</th>
                                    <th style="padding: 0.75rem; text-align: left; font-weight: 600; font-size: 0.875rem; color: white;">Referencia</th>
                                    <th style="padding: 0.75rem; text-align: center; font-weight: 600; font-size: 0.875rem; color: white;">Imagen Tela</th>
                                    <th style="padding: 0.75rem; text-align: center; font-weight: 600; font-size: 0.875rem; color: white; width: 30px;"></th>
                                </tr>
                            </thead>
                            <tbody id="tbody-telas">
                                <!-- Fila para agregar nueva tela -->
                                <tr style="border-bottom: 1px solid #e5e7eb;">
                                    <td style="padding: 0.5rem;">
                                        <input type="text" id="nueva-prenda-tela" placeholder="TELA..." class="form-input" style="width: 100%; padding: 0.5rem;">
                                    </td>
                                    <td style="padding: 0.5rem;">
                                        <input type="text" id="nueva-prenda-color" placeholder="COLOR..." class="form-input" style="width: 100%; padding: 0.5rem;">
                                    </td>
                                    <td style="padding: 0.5rem;">
                                        <input type="text" id="nueva-prenda-referencia" placeholder="REF..." class="form-input" style="width: 100%; padding: 0.5rem;">
                                    </td>
                                    <td style="padding: 0.5rem; text-align: center;">
                                        <button type="button" onclick="document.getElementById('nueva-prenda-tela-img-input').click()" class="btn btn-primary btn-flex" style="font-size: 0.75rem; padding: 0.25rem 0.75rem;" title="Agregar imagen (opcional)">
                                            <span class="material-symbols-rounded" style="font-size: 1.2rem;">image</span>
                                        </button>
                                        <input type="file" id="nueva-prenda-tela-img-input" accept="image/*" style="display: none;" onchange="manejarImagenTela(this)">
                                    </td>
                                    <td style="padding: 0.5rem; text-align: center;">
                                        <button type="button" onclick="agregarTelaNueva()" class="btn btn-success btn-flex" style="font-size: 0.75rem; padding: 0.25rem 0.75rem;" title="Agregar esta tela">
                                            <span class="material-symbols-rounded" style="font-size: 1.2rem;">add</span>Agregar
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Preview de imágenes de tela -->
                    <div id="nueva-prenda-tela-preview" style="margin-top: 0.5rem; display: flex; gap: 0.5rem; flex-wrap: wrap;"></div>

                    <!-- Tallas y Cantidades -->
                    <label class="form-label-primary" style="margin-top: 1.5rem;">
                        <span class="material-symbols-rounded">straighten</span>TALLAS Y CANTIDADES *
                    </label>
                    
                    <!-- Seleccionar Género(s) -->
                    <div class="genero-buttons">
                        <!-- Botón DAMA -->
                        <button type="button" id="btn-genero-dama" class="btn-genero" data-selected="false">
                            <div class="btn-genero-content">
                                <span class="material-symbols-rounded">woman</span>
                                <span>DAMA</span>
                            </div>
                            <span id="check-dama" class="btn-genero-check">✓</span>
                        </button>
                        
                        <!-- Botón CABALLERO -->
                        <button type="button" id="btn-genero-caballero" class="btn-genero" data-selected="false">
                            <div class="btn-genero-content">
                                <span class="material-symbols-rounded">man</span>
                                <span>CABALLERO</span>
                            </div>
                            <span id="check-caballero" class="btn-genero-check">✓</span>
                        </button>
                    </div>
                    
                    <!-- Tarjetas de Géneros Seleccionados -->
                    <div id="tarjetas-generos-container" class="generos-container"></div>
                    
                    <!-- Total general -->
                    <div class="total-box">
                        <span class="material-symbols-rounded">shopping_cart</span>
                        Total: <span id="total-prendas">0</span> unidades
                    </div>
                </div>

                <!-- Variaciones -->
                <div class="form-section">
                    <label class="form-label-primary">
                        <span class="material-symbols-rounded">tune</span>VARIACIONES ESPECÍFICAS
                    </label>
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background: #0066cc;">
                                    <th style="padding: 0.75rem; text-align: center; font-weight: 600; font-size: 0.875rem; width: 50px; color: white;">
                                        APLICA
                                    </th>
                                    <th style="padding: 0.75rem; text-align: left; font-weight: 600; font-size: 0.875rem; color: white;">
                                        VARIACIÓN
                                    </th>
                                    <th style="padding: 0.75rem; text-align: left; font-weight: 600; font-size: 0.875rem; color: white;">
                                        ESPECIFICACIÓN
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Manga -->
                                <tr style="border-bottom: 1px solid #e5e7eb;">
                                    <td style="padding: 0.75rem; text-align: center;">
                                        <input type="checkbox" id="aplica-manga" class="form-checkbox" onchange="manejarCheckVariacion(this)" style="width: 18px; height: 18px; cursor: pointer;">
                                    </td>
                                    <td style="padding: 0.75rem;">
                                        <span style="font-weight: 600; color: #0066cc;">Manga</span>
                                    </td>
                                    <td style="padding: 0.75rem;">
                                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem;">
                                            <input type="text" id="manga-input" placeholder="Ej: manga larga..." disabled list="opciones-manga" style="padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 4px; opacity: 0.5; font-size: 0.875rem; width: 100%;">
                                            <datalist id="opciones-manga">
                                                <option value="Manga Larga">
                                                <option value="Manga Corta">
                                                <option value="Manga Media">
                                                <option value="Sin Manga">
                                            </datalist>
                                            <input type="text" id="manga-obs" placeholder="Observaciones..." disabled style="padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 4px; opacity: 0.5; font-size: 0.875rem; width: 100%;">
                                        </div>
                                    </td>
                                </tr>
                                
                                <!-- Bolsillos -->
                                <tr style="border-bottom: 1px solid #e5e7eb;">
                                    <td style="padding: 0.75rem; text-align: center;">
                                        <input type="checkbox" id="aplica-bolsillos" class="form-checkbox" onchange="manejarCheckVariacion(this)" style="width: 18px; height: 18px; cursor: pointer;">
                                    </td>
                                    <td style="padding: 0.75rem;">
                                        <span style="font-weight: 600; color: #0066cc;">Bolsillos</span>
                                    </td>
                                    <td style="padding: 0.75rem;">
                                        <input type="text" id="bolsillos-input" placeholder="Ej: 4 bolsillos, con cierre..." disabled class="form-input" style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 4px; opacity: 0.5; font-size: 0.875rem;">
                                    </td>
                                </tr>
                                
                                <!-- Broche/Botón -->
                                <tr style="border-bottom: 1px solid #e5e7eb;">
                                    <td style="padding: 0.75rem; text-align: center;">
                                        <input type="checkbox" id="aplica-broche" class="form-checkbox" onchange="manejarCheckVariacion(this)" style="width: 18px; height: 18px; cursor: pointer;">
                                    </td>
                                    <td style="padding: 0.75rem;">
                                        <span style="font-weight: 600; color: #0066cc;">Broche/Botón</span>
                                    </td>
                                    <td style="padding: 0.75rem;">
                                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem;">
                                            <select id="broche-input" disabled style="padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 4px; opacity: 0.5; font-size: 0.875rem; width: 100%;">
                                                <option value="">Seleccionar tipo...</option>
                                                <option value="boton">Botón</option>
                                                <option value="broche">Broche</option>
                                            </select>
                                            <input type="text" id="broche-obs" placeholder="Observaciones..." disabled style="padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 4px; opacity: 0.5; font-size: 0.875rem; width: 100%;">
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Procesos -->
                <div class="form-section">
                    <label class="form-label-primary">
                        <span class="material-symbols-rounded">settings</span>PROCESOS (Opcional)
                    </label>
                    <div class="procesos-container">
                        <label class="proceso-checkbox">
                            <input type="checkbox" name="nueva-prenda-procesos" value="Bordado" class="form-checkbox">
                            <span><span class="material-symbols-rounded">auto_awesome</span>Bordado</span>
                        </label>
                        <label class="proceso-checkbox">
                            <input type="checkbox" name="nueva-prenda-procesos" value="Estampado" class="form-checkbox">
                            <span><span class="material-symbols-rounded">format_paint</span>Estampado</span>
                        </label>
                        <label class="proceso-checkbox">
                            <input type="checkbox" id="checkbox-reflectivo" name="nueva-prenda-procesos" value="Reflectivo" class="form-checkbox" onchange="if(this.checked) { abrirModalReflectivo(); } else { cerrarModalReflectivo(); }">
                            <span><span class="material-symbols-rounded">light_mode</span>Reflectivo</span>
                        </label>
                    </div>
                </div>

                <!-- Reflectivo (Resumen) -->
                <div class="form-section" id="seccion-reflectivo-resumen" style="display: none;">
                    <label class="form-label-primary">
                        <span class="material-symbols-rounded">light_mode</span>REFLECTIVO
                    </label>
                    <div style="background: #f3f4f6; border-radius: 8px; padding: 1rem; border-left: 4px solid #0066cc;">
                        <div id="reflectivo-resumen-contenido" style="font-size: 0.875rem; color: #6b7280;"></div>
                        <button type="button" onclick="abrirModalReflectivo()" class="btn btn-primary" style="margin-top: 1rem; padding: 0.75rem 1rem; font-size: 0.875rem;">
                            <span class="material-symbols-rounded" style="margin-right: 0.5rem;">edit</span>Editar
                        </button>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Footer -->
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="cerrarModalPrendaNueva()">Cancelar</button>
            <button class="btn btn-primary" onclick="agregarPrendaNueva()">
                <span class="material-symbols-rounded">check</span>Agregar Prenda
            </button>
        </div>
    </div>
</div>
