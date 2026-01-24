<!-- MODAL: Agregar Prenda Nueva (Sin Cotización) -->
<div id="modal-agregar-prenda-nueva" class="modal-overlay" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 99999; align-items: center; justify-content: center; overflow-y: auto; padding: 2rem 0;">
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
                                <input type="text" id="nueva-prenda-nombre" required placeholder="Ej: CAMISA DRILL, POLO, PANTALÓN..." class="form-input" onkeyup="this.value = this.value.toUpperCase();" style="text-transform: uppercase;">
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
                            <textarea id="nueva-prenda-descripcion" placeholder="Descripción de la prenda, detalles especiales..." class="form-textarea" onkeyup="this.value = this.value.toUpperCase();" style="text-transform: uppercase;"></textarea>
                        </div>
                    </div>

                    <!-- COLUMNA DERECHA: Fotos de la Prenda -->
                    <div class="foto-panel">
                        <label class="foto-panel-label">
                            <span class="material-symbols-rounded">photo_camera</span>FOTOS
                        </label>
                        
                        <!-- Imagen principal preview -->
                        <div id="nueva-prenda-foto-preview" class="foto-preview foto-preview-lg">
                            <div class="foto-preview-content">
                                <div class="material-symbols-rounded">add_photo_alternate</div>
                                <div class="foto-preview-text">Agregar</div>
                            </div>
                        </div>
                        
                        <!-- Contador de fotos -->
                        <div id="nueva-prenda-foto-contador" class="foto-counter"></div>
                        
                        <!-- Input de archivos -->
                        <input type="file" id="nueva-prenda-foto-input" accept="image/*" style="display: none;" onchange="manejarImagenesPrenda(this)">
                        
                        <!-- Botón agregar más fotos -->
                        <button type="button" id="nueva-prenda-foto-btn" class="btn btn-sm btn-primary" onclick="document.getElementById('nueva-prenda-foto-input').click()">
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
                        <table style="width: 100%; border-collapse: collapse; margin-bottom: 1rem; table-layout: fixed;">
                            <thead>
                                <tr style="background: #0066cc; border-bottom: 2px solid #0066cc;">
                                    <th style="padding: 0.75rem; text-align: left; font-weight: 600; font-size: 0.875rem; color: white; width: 20%;">Tela</th>
                                    <th style="padding: 0.75rem; text-align: left; font-weight: 600; font-size: 0.875rem; color: white; width: 20%;">Color</th>
                                    <th style="padding: 0.75rem; text-align: left; font-weight: 600; font-size: 0.875rem; color: white; width: 20%;">Referencia</th>
                                    <th style="padding: 0.75rem; text-align: center; font-weight: 600; font-size: 0.875rem; color: white; width: 20%;">Imagen Tela</th>
                                    <th style="padding: 0.75rem; text-align: center; font-weight: 600; font-size: 0.875rem; color: white; width: 20%;"></th>
                                </tr>
                            </thead>
                            <tbody id="tbody-telas">
                                <!-- Fila para agregar nueva tela -->
                                <tr style="border-bottom: 1px solid #e5e7eb;">
                                    <td style="padding: 0.5rem; width: 20%;">
                                        <input type="text" id="nueva-prenda-tela" placeholder="TELA..." class="form-input" style="width: 100%; padding: 0.5rem; text-transform: uppercase;" onkeyup="this.value = this.value.toUpperCase();">
                                    </td>
                                    <td style="padding: 0.5rem; width: 20%;">
                                        <input type="text" id="nueva-prenda-color" placeholder="COLOR..." class="form-input" style="width: 100%; padding: 0.5rem; text-transform: uppercase;" onkeyup="this.value = this.value.toUpperCase();">
                                    </td>
                                    <td style="padding: 0.5rem; width: 20%;">
                                        <input type="text" id="nueva-prenda-referencia" placeholder="REF..." class="form-input" style="width: 100%; padding: 0.5rem; text-transform: uppercase;" onkeyup="this.value = this.value.toUpperCase();">
                                    </td>
                                    <td style="padding: 0.5rem; text-align: center; vertical-align: top; width: 20%;">
                                        <button type="button" onclick="document.getElementById('nueva-prenda-tela-img-input').click()" class="btn btn-primary btn-flex" style="font-size: 0.75rem; padding: 0.25rem 0.75rem;" title="Agregar imagen (opcional)">
                                            <span class="material-symbols-rounded" style="font-size: 1.2rem;">image</span>
                                        </button>
                                        <input type="file" id="nueva-prenda-tela-img-input" accept="image/*" style="display: none;" onchange="manejarImagenTela(this)">
                                        <!-- Preview temporal dentro de la celda - EN EL FLUJO VISUAL Y VISIBLE -->
                                        <div id="nueva-prenda-tela-preview" style="display: none; flex-wrap: wrap; gap: 0.5rem; justify-content: center; align-items: flex-start; margin-top: 0.5rem; padding: 0.5rem; background: #f9fafb; border: 1px dashed #d1d5db; border-radius: 4px; width: calc(100% + 1rem); margin-left: -0.5rem; margin-right: -0.5rem;"></div>
                                    </td>
                                    <td style="padding: 0.5rem; text-align: center; width: 20%;">
                                        <button type="button" onclick="agregarTelaNueva()" class="btn btn-success btn-flex" style="font-size: 0.75rem; padding: 0.25rem 0.75rem;" title="Agregar esta tela">
                                            <span class="material-symbols-rounded" style="font-size: 1.2rem;">add</span>Agregar
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Tallas y Cantidades -->
                    <label class="form-label-primary" style="margin-top: 1.5rem;">
                        <span class="material-symbols-rounded">straighten</span>TALLAS Y CANTIDADES *
                    </label>
                    
                    <!-- Seleccionar Género(s) -->
                    <div class="genero-buttons">
                        <!-- Botón DAMA -->
                        <button type="button" id="btn-genero-dama" class="btn-genero" data-selected="false" onclick="abrirModalSeleccionarTallas('dama')">
                            <div class="btn-genero-content">
                                <span class="material-symbols-rounded">woman</span>
                                <span>DAMA</span>
                            </div>
                            <span id="check-dama" class="btn-genero-check">✓</span>
                        </button>
                        
                        <!-- Botón CABALLERO -->
                        <button type="button" id="btn-genero-caballero" class="btn-genero" data-selected="false" onclick="abrirModalSeleccionarTallas('caballero')">
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
                                            <input type="text" id="manga-input" placeholder="Ej: Larga, Corta, 3/4..." disabled list="opciones-manga" style="padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 4px; opacity: 0.5; font-size: 0.875rem; width: 100%;">
                                            <datalist id="opciones-manga">
                                                <!-- Las opciones se cargarán dinámicamente desde /asesores/api/tipos-manga -->
                                            </datalist>
                                            <input type="text" id="manga-obs" placeholder="Observaciones..." disabled style="padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 4px; opacity: 0.5; font-size: 0.875rem; width: 100%; text-transform: uppercase;" onkeyup="this.value = this.value.toUpperCase();">
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
                                        <input type="text" id="bolsillos-obs" placeholder="Observaciones (Ej: 4 bolsillos con cierre, ocultos, etc...)" disabled style="padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 4px; opacity: 0.5; font-size: 0.875rem; width: 100%; text-transform: uppercase;" onkeyup="this.value = this.value.toUpperCase();">
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
                                            <input type="text" id="broche-obs" placeholder="Observaciones..." disabled style="padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 4px; opacity: 0.5; font-size: 0.875rem; width: 100%; text-transform: uppercase;" onkeyup="this.value = this.value.toUpperCase();">
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
                        <!-- Reflectivo -->
                        <label class="proceso-checkbox">
                            <input type="checkbox" id="checkbox-reflectivo" name="nueva-prenda-procesos" value="reflectivo" class="form-checkbox" onclick="if (!this._ignorarOnclick) { if(this.checked) { manejarCheckboxProceso('reflectivo', true); } else { manejarCheckboxProceso('reflectivo', false); } }">
                            <span><i class="fas fa-lightbulb" style="color: #FFD700; margin-right: 6px;"></i>Reflectivo</span>
                        </label>
                        
                        <!-- Bordado -->
                        <label class="proceso-checkbox">
                            <input type="checkbox" id="checkbox-bordado" name="nueva-prenda-procesos" value="bordado" class="form-checkbox" onclick="if (!this._ignorarOnclick) { if(this.checked) { manejarCheckboxProceso('bordado', true); } else { manejarCheckboxProceso('bordado', false); } }">
                            <span><i class="fas fa-gem" style="color: #9333EA; margin-right: 6px;"></i>Bordado</span>
                        </label>
                        
                        <!-- Estampado -->
                        <label class="proceso-checkbox">
                            <input type="checkbox" id="checkbox-estampado" name="nueva-prenda-procesos" value="estampado" class="form-checkbox" onclick="if (!this._ignorarOnclick) { if(this.checked) { manejarCheckboxProceso('estampado', true); } else { manejarCheckboxProceso('estampado', false); } }">
                            <span><i class="fas fa-paint-brush" style="color: #DC2626; margin-right: 6px;"></i>Estampado</span>
                        </label>
                        
                        <!-- DTF -->
                        <label class="proceso-checkbox">
                            <input type="checkbox" id="checkbox-dtf" name="nueva-prenda-procesos" value="dtf" class="form-checkbox" onclick="if (!this._ignorarOnclick) { if(this.checked) { manejarCheckboxProceso('dtf', true); } else { manejarCheckboxProceso('dtf', false); } }">
                            <span><i class="fas fa-print" style="color: #EA580C; margin-right: 6px;"></i>DTF</span>
                        </label>
                        
                        <!-- Sublimado -->
                        <label class="proceso-checkbox">
                            <input type="checkbox" id="checkbox-sublimado" name="nueva-prenda-procesos" value="sublimado" class="form-checkbox" onclick="if (!this._ignorarOnclick) { if(this.checked) { manejarCheckboxProceso('sublimado', true); } else { manejarCheckboxProceso('sublimado', false); } }">
                            <span><i class="fas fa-tint" style="color: #0891B2; margin-right: 6px;"></i>Sublimado</span>
                        </label>
                    </div>
                    
                    <!--  CONTENEDOR PARA TARJETAS DE PROCESOS CONFIGURADOS -->
                    <div id="contenedor-tarjetas-procesos" style="margin-top: 1rem; display: none;"></div>
                </div>
            </form>
        </div>
        
        <!-- Footer -->
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="cerrarModalPrendaNueva()">Cancelar</button>
            <button id="btn-guardar-prenda" class="btn btn-primary" onclick="agregarPrendaNueva()">
                <span class="material-symbols-rounded">check</span>Agregar Prenda
            </button>
        </div>
    </div>
</div>
