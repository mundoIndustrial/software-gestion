<!-- MODAL: Agregar Reflectivo -->
<div id="modal-agregar-reflectivo" class="modal-overlay">
    <div class="modal-container modal-xl">
        <!-- Header -->
        <div class="modal-header modal-header-primary">
            <h3 class="modal-title">
                <span class="material-symbols-rounded">light_mode</span>Agregar Reflectivo
            </h3>
            <button class="modal-close-btn" onclick="cerrarModalReflectivo()">
                <span class="material-symbols-rounded">close</span>
            </button>
        </div>
        
        <!-- Body -->
        <div class="modal-body">
            <form id="form-reflectivo-nuevo">
                <!-- Layout en 2 columnas: Datos a la izquierda, Fotos a la derecha -->
                <div class="form-prenda-grid form-prenda-grid-lg">
                    <!-- COLUMNA IZQUIERDA: Datos principales -->
                    <div>
                        <!-- Nombre y Origen -->
                        <div class="form-row-2col">
                            <!-- Nombre de la prenda -->
                            <div class="form-group">
                                <label class="form-label-primary">
                                    <span class="material-symbols-rounded">checkroom</span>NOMBRE DE LA PRENDA *
                                </label>
                                <input type="text" id="reflectivo-prenda-nombre" required placeholder="Ej: CAMISA, CHALECO, PANTALÓN..." class="form-input">
                            </div>

                            <!-- Origen -->
                            <div class="form-group">
                                <label class="form-label-primary">
                                    <span class="material-symbols-rounded">location_on</span>ORIGEN *
                                </label>
                                <select id="reflectivo-origen-select" class="form-input">
                                    <option value="bodega">Bodega</option>
                                    <option value="confeccion">Confección</option>
                                </select>
                            </div>
                        </div>

                        <!-- Descripción (si aplica) -->
                        <div class="form-group">
                            <label class="form-label-primary">
                                <span class="material-symbols-rounded">description</span>DESCRIPCIÓN (Opcional)
                            </label>
                            <textarea id="reflectivo-prenda-descripcion" placeholder="Detalles especiales del reflectivo..." class="form-textarea"></textarea>
                        </div>
                    </div>

                    <!-- COLUMNA DERECHA: Fotos del Reflectivo -->
                    <div class="foto-panel foto-panel-lg">
                        <label class="foto-panel-label">
                            <span class="material-symbols-rounded">photo_camera</span>FOTOS
                        </label>
                        
                        <!-- Imagen principal preview -->
                        <div id="reflectivo-foto-preview" class="foto-preview-lg" onclick="document.getElementById('reflectivo-foto-input').click()">
                            <div class="foto-preview-content">
                                <div class="material-symbols-rounded">add_photo_alternate</div>
                                <div class="foto-preview-text">Click para agregar</div>
                            </div>
                        </div>
                        
                        <!-- Contador de fotos -->
                        <div id="reflectivo-foto-contador" class="foto-counter"></div>
                        
                        <!-- Input de archivos -->
                        <input type="file" id="reflectivo-foto-input" accept="image/*" style="display: none;" onchange="manejarImagenesReflectivo(this)">
                        
                        <!-- Botón agregar más fotos -->
                        <button type="button" id="reflectivo-foto-btn" onclick="document.getElementById('reflectivo-foto-input').click()" class="btn btn-sm btn-primary" style="display: none;">
                            + Agregar
                        </button>
                    </div>
                </div>

                <!-- Tallas y Cantidades -->
                <div class="form-section">
                    <label class="form-label-primary">
                        <span class="material-symbols-rounded">straighten</span>TALLAS Y CANTIDADES *
                    </label>
                    
                    <!-- Seleccionar Género(s) -->
                    <div class="genero-buttons">
                        <!-- Botón DAMA -->
                        <button type="button" id="btn-genero-reflectivo-dama" class="btn-genero" onclick="abrirModalSeleccionarTallasReflectivo('dama')" data-selected="false">
                            <div class="btn-genero-content">
                                <span class="material-symbols-rounded">woman</span>
                                <span>DAMA</span>
                            </div>
                            <span id="check-reflectivo-dama" class="btn-genero-check">✓</span>
                        </button>
                        
                        <!-- Botón CABALLERO -->
                        <button type="button" id="btn-genero-reflectivo-caballero" class="btn-genero" onclick="abrirModalSeleccionarTallasReflectivo('caballero')" data-selected="false">
                            <div class="btn-genero-content">
                                <span class="material-symbols-rounded">man</span>
                                <span>CABALLERO</span>
                            </div>
                            <span id="check-reflectivo-caballero" class="btn-genero-check">✓</span>
                        </button>
                    </div>
                    
                    <!-- Tarjetas de Géneros Seleccionados -->
                    <div id="tarjetas-generos-reflectivo-container" class="generos-container"></div>
                    
                    <!-- Total general -->
                    <div class="total-box">
                        <span class="material-symbols-rounded">shopping_cart</span>
                        Total: <span id="total-reflectivo">0</span> unidades
                    </div>
                </div>

                <!-- Procesos -->
                <div class="form-section">
                    <label class="form-label-primary">
                        <span class="material-symbols-rounded">settings</span>PROCESOS (Opcional)
                    </label>
                    <div class="procesos-container">
                        <label class="proceso-checkbox">
                            <input type="checkbox" name="reflectivo-procesos" value="Bordado" class="form-checkbox">
                            <span><span class="material-symbols-rounded">auto_awesome</span>Bordado</span>
                        </label>
                        <label class="proceso-checkbox">
                            <input type="checkbox" name="reflectivo-procesos" value="Estampado" class="form-checkbox">
                            <span><span class="material-symbols-rounded">format_paint</span>Estampado</span>
                        </label>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Footer -->
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="cerrarModalReflectivo()">Cancelar</button>
            <button class="btn btn-primary" onclick="agregarReflectivo()">
                <span class="material-symbols-rounded">check</span>Agregar Reflectivo
            </button>
        </div>
    </div>
</div>
