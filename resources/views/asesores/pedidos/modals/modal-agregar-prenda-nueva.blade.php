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
                        <div id="nueva-prenda-foto-preview" class="foto-preview" onclick="document.getElementById('nueva-prenda-foto-input').click()">
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
                        <button type="button" id="nueva-prenda-foto-btn" onclick="document.getElementById('nueva-prenda-foto-input').click()" class="btn btn-sm btn-primary" style="display: none;">
                            + Agregar
                        </button>
                    </div>
                </div>

                <!-- Color, Tela, Referencia e Imágenes de Tela -->
                <div class="form-section">
                    <label class="form-label-primary">
                        <span class="material-symbols-rounded">palette</span>TELA, COLOR Y REFERENCIA
                    </label>
                    <div class="form-row-4col">
                        <!-- Tela -->
                        <div class="form-group">
                            <label class="form-label-sm">Tela</label>
                            <input type="text" id="nueva-prenda-tela" placeholder="Ej: DRILL" class="form-input">
                        </div>
                        <!-- Color -->
                        <div class="form-group">
                            <label class="form-label-sm">Color</label>
                            <input type="text" id="nueva-prenda-color" placeholder="Ej: AZUL MARINO" class="form-input">
                        </div>
                        <!-- Referencia -->
                        <div class="form-group">
                            <label class="form-label-sm">Referencia</label>
                            <input type="text" id="nueva-prenda-referencia" placeholder="Ej: REF-001" class="form-input">
                        </div>
                        <!-- Imágenes de Tela -->
                        <div class="form-group">
                            <div style="position: relative;">
                                <input type="file" id="nueva-prenda-tela-img-input" accept="image/*" style="display: none;" onchange="manejarImagenesTela(this)">
                                <button type="button" onclick="document.getElementById('nueva-prenda-tela-img-input').click()" class="btn btn-primary btn-flex">
                                    <span class="material-symbols-rounded">image</span>Fotos
                                </button>
                            </div>
                            <!-- Preview de imágenes de tela -->
                            <div id="nueva-prenda-tela-preview" class="tela-preview-container"></div>
                        </div>
                    </div>

                    <!-- Tallas y Cantidades -->
                    <label class="form-label-primary">
                        <span class="material-symbols-rounded">straighten</span>TALLAS Y CANTIDADES *
                    </label>
                    
                    <!-- Seleccionar Género(s) -->
                    <div class="genero-buttons">
                        <!-- Botón DAMA -->
                        <button type="button" id="btn-genero-dama" class="btn-genero" onclick="abrirModalSeleccionarTallas('dama')" data-selected="false">
                            <div class="btn-genero-content">
                                <span class="material-symbols-rounded">woman</span>
                                <span>DAMA</span>
                            </div>
                            <span id="check-dama" class="btn-genero-check">✓</span>
                        </button>
                        
                        <!-- Botón CABALLERO -->
                        <button type="button" id="btn-genero-caballero" class="btn-genero" onclick="abrirModalSeleccionarTallas('caballero')" data-selected="false">
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
                    <div class="variaciones-grid">
                        <div class="variacion-item">
                            <label class="checkbox-label">
                                <input type="checkbox" id="aplica-manga" class="form-checkbox">
                                <span><span class="material-symbols-rounded">checkroom</span>Manga</span>
                            </label>
                            <input type="text" id="manga-input" placeholder="Ej: manga larga..." disabled class="form-input form-input-disabled">
                        </div>
                        <div class="variacion-item">
                            <label class="checkbox-label">
                                <input type="checkbox" id="aplica-bolsillos" class="form-checkbox">
                                <span><span class="material-symbols-rounded">backpack</span>Bolsillos</span>
                            </label>
                            <input type="text" id="bolsillos-input" placeholder="Ej: 2 bolsillos..." disabled class="form-input form-input-disabled">
                        </div>
                        <div class="variacion-item">
                            <label class="checkbox-label">
                                <input type="checkbox" id="aplica-broche" class="form-checkbox">
                                <span><span class="material-symbols-rounded">circle</span>Broche/Botón</span>
                            </label>
                            <input type="text" id="broche-input" placeholder="Ej: botones metálicos..." disabled class="form-input form-input-disabled">
                        </div>
                        <div class="variacion-item">
                            <label class="checkbox-label">
                                <input type="checkbox" id="aplica-puno" class="form-checkbox">
                                <span><span class="material-symbols-rounded">pan_tool</span>Puño</span>
                            </label>
                            <input type="text" id="puno-input" placeholder="Ej: puño elástico..." disabled class="form-input form-input-disabled">
                        </div>
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
                            <input type="checkbox" name="nueva-prenda-procesos" value="Reflectivo" class="form-checkbox">
                            <span><span class="material-symbols-rounded">light_mode</span>Reflectivo</span>
                        </label>
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
