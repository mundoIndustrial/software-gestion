<!-- MODAL: Agregar Proceso Genérico (Reflectivo, Estampado, Bordado, DTF, Sublimado) -->
<div id="modal-proceso-generico" class="modal-overlay" style="z-index: 9999999999 !important; display: none;">
    <div class="modal-container modal-xl">
        <!-- Header -->
        <div class="modal-header modal-header-primary">
            <h3 class="modal-title">
                <span class="material-symbols-rounded" id="modal-proceso-icon">light_mode</span>
                <span id="modal-proceso-titulo">Agregar Proceso</span>
            </h3>
            <button class="modal-close-btn" onclick="cerrarModalProcesoGenerico()">
                <span class="material-symbols-rounded">close</span>
            </button>
        </div>
        
        <!-- Body -->
        <div class="modal-body">
            <form id="form-proceso-generico">
                <!-- Ubicación del Proceso (Agregar lista) -->
                <div class="form-section">
                    <label for="input-ubicacion-nueva" class="form-label-primary">
                        <span class="material-symbols-rounded">location_on</span>UBICACIONES
                    </label>
                    <div style="display: flex; flex-direction: column; gap: 0.5rem; margin-bottom: 1rem;">
                        <textarea id="input-ubicacion-nueva" placeholder="Ej: Frente, Espalda, Manga derecha, Bolsillo..." class="form-textarea" style="min-height: 120px; resize: vertical;"></textarea>
                        <button type="button" class="btn btn-primary" onclick="agregarUbicacionProceso()" style="width: auto; align-self: flex-end; padding: 0.75rem 1rem;">
                            <span class="material-symbols-rounded">add</span>
                        </button>
                    </div>
                    
                    <!-- Lista de ubicaciones agregadas -->
                    <div id="lista-ubicaciones-proceso" style="display: flex; flex-wrap: wrap; gap: 0.5rem;"></div>
                </div>

                <!-- Observaciones -->
                <div class="form-section">
                    <label for="proceso-observaciones" class="form-label-primary">
                        <span class="material-symbols-rounded">description</span>OBSERVACIONES (Opcional)
                    </label>
                    <textarea id="proceso-observaciones" placeholder="Ej: Colores específicos, tamaño del diseño, instrucciones especiales..." class="form-textarea" style="min-height: 80px;"></textarea>
                </div>

                <!-- Tallas -->
                <div class="form-section">
                    <label class="form-label-primary">
                        <span class="material-symbols-rounded">straighten</span>APLICAR PROCESO A TALLAS
                    </label>
                    
                    <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                        <!-- Botón: Aplicar para todas -->
                        <button type="button" id="btn-aplicar-todas-tallas" class="btn btn-outline-primary" onclick="aplicarProcesoParaTodasTallas()" style="flex: 1; min-width: 200px; padding: 0.75rem;">
                            <span class="material-symbols-rounded" style="font-size: 1.2rem;">done_all</span>
                            Aplicar para todas (de la prenda)
                        </button>
                        
                        <!-- Botón: Editar tallas específicas -->
                        <button type="button" id="btn-editar-tallas-especificas" class="btn btn-outline-secondary" onclick="abrirEditorTallasEspecificas()" style="flex: 1; min-width: 200px; padding: 0.75rem;">
                            <span class="material-symbols-rounded" style="font-size: 1.2rem;">edit</span>
                            Editar tallas específicas
                        </button>
                    </div>
                    
                    <!-- Resumen de tallas seleccionadas -->
                    <div id="proceso-tallas-resumen" style="background: #f3f4f6; border-radius: 8px; padding: 1rem; font-size: 0.875rem; color: #6b7280; margin-top: 1rem;"></div>
                </div>

                <!-- Imágenes del Proceso -->
                <div class="form-section">
                    <label class="form-label-primary">
                        <span class="material-symbols-rounded">photo_camera</span>IMÁGENES (Máximo 3)
                    </label>
                    
                    <div class="foto-panel" style="display: flex; gap: 0.75rem; flex-direction: row; align-items: flex-start;" tabindex="0">
                        <!-- Preview 1 -->
                        <div id="proceso-foto-preview-1" class="foto-preview-proceso" style="width: 120px; height: 120px; flex-shrink: 0; border: 2px dashed #0066cc; border-radius: 8px; display: flex; align-items: center; justify-content: center; cursor: pointer; background: #f9fafb; position: relative;" tabindex="0">
                            <div class="placeholder-content" style="text-align: center;">
                                <div class="material-symbols-rounded" style="font-size: 1.5rem; color: #6b7280;">add_photo_alternate</div>
                                <div style="font-size: 0.7rem; color: #6b7280; margin-top: 0.25rem;">Imagen 1</div>
                            </div>
                        </div>
                        <input type="file" id="proceso-foto-input-1" accept="image/*" style="display: none;" aria-label="Imagen 1 del Proceso" onchange="manejarImagenProceso(this, 1)">
                        
                        <!-- Preview 2 -->
                        <div id="proceso-foto-preview-2" class="foto-preview-proceso" style="width: 120px; height: 120px; flex-shrink: 0; border: 2px dashed #0066cc; border-radius: 8px; display: flex; align-items: center; justify-content: center; cursor: pointer; background: #f9fafb; position: relative;" tabindex="0">
                            <div class="placeholder-content" style="text-align: center;">
                                <div class="material-symbols-rounded" style="font-size: 1.5rem; color: #6b7280;">add_photo_alternate</div>
                                <div style="font-size: 0.7rem; color: #6b7280; margin-top: 0.25rem;">Imagen 2</div>
                            </div>
                        </div>
                        <input type="file" id="proceso-foto-input-2" accept="image/*" style="display: none;" aria-label="Imagen 2 del Proceso" onchange="manejarImagenProceso(this, 2)">
                        
                        <!-- Preview 3 -->
                        <div id="proceso-foto-preview-3" class="foto-preview-proceso" style="width: 120px; height: 120px; flex-shrink: 0; border: 2px dashed #0066cc; border-radius: 8px; display: flex; align-items: center; justify-content: center; cursor: pointer; background: #f9fafb; position: relative;" tabindex="0">
                            <div class="placeholder-content" style="text-align: center;">
                                <div class="material-symbols-rounded" style="font-size: 1.5rem; color: #6b7280;">add_photo_alternate</div>
                                <div style="font-size: 0.7rem; color: #6b7280; margin-top: 0.25rem;">Imagen 3</div>
                            </div>
                        </div>
                        <input type="file" id="proceso-foto-input-3" accept="image/*" style="display: none;" aria-label="Imagen 3 del Proceso" onchange="manejarImagenProceso(this, 3)">
                    </div>
                    <p style="margin-top: 0.5rem; font-size: 0.75rem; color: #6b7280;">
                        <i class="fas fa-info-circle"></i> Puedes agregar hasta 3 imágenes para este proceso
                    </p>
                </div>
            </form>
        </div>
        
        <!-- Footer -->
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="cerrarModalProcesoGenerico()">Cancelar</button>
            <button class="btn btn-primary" onclick="agregarProcesoAlPedido()">
                <span class="material-symbols-rounded">check</span><span id="modal-btn-texto">Agregar</span>
            </button>
        </div>
    </div>
</div>

<!-- Componentes: Galerías Modales Reutilizables para cada Proceso -->
<x-galeria-modal :id="'proceso-1'" :titulo="'Galería - Proceso 1'" />
<x-galeria-modal :id="'proceso-2'" :titulo="'Galería - Proceso 2'" />
<x-galeria-modal :id="'proceso-3'" :titulo="'Galería - Proceso 3'" />

<script>
    // Manejar paste en el panel de fotos
    document.addEventListener('DOMContentLoaded', function() {
        const fotoPanelElement = document.querySelector('.foto-panel');
        
        if (fotoPanelElement) {
            fotoPanelElement.addEventListener('paste', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const items = (e.clipboardData || window.clipboardData).items;
                
                for (let item of items) {
                    if (item.kind === 'file' && item.type.startsWith('image/')) {
                        const file = item.getAsFile();
                        
                        // Encontrar el primer cuadro vacío
                        let cuadroVacio = null;
                        for (let i = 1; i <= 3; i++) {
                            const preview = document.getElementById(`proceso-foto-preview-${i}`);
                            const input = document.getElementById(`proceso-foto-input-${i}`);
                            
                            // Verificar si el cuadro está vacío (no tiene imagen)
                            if (preview && !preview.querySelector('img')) {
                                cuadroVacio = i;
                                break;
                            }
                        }
                        
                        if (cuadroVacio) {
                            // Asignar el archivo al input correcto
                            const input = document.getElementById(`proceso-foto-input-${cuadroVacio}`);
                            const dataTransfer = new DataTransfer();
                            dataTransfer.items.add(file);
                            input.files = dataTransfer.files;
                            
                            // Ejecutar el manejador de imagen
                            if (typeof manejarImagenProceso === 'function') {
                                manejarImagenProceso(input, cuadroVacio);
                            }
                        }
                        break; // Solo procesar la primera imagen
                    }
                }
            }, false);
        }
    });
</script>

<!-- MODAL SECUNDARIO: Editor de Tallas Específicas -->
<div id="modal-editor-tallas" class="modal-overlay" style="z-index: 100002; display: none;">
    <div class="modal-container modal-lg">
        <!-- Header -->
        <div class="modal-header modal-header-primary">
            <h3 class="modal-title">
                <span class="material-symbols-rounded">straighten</span>
                <span>Editar Tallas por Género</span>
            </h3>
            <button class="modal-close-btn" onclick="cerrarEditorTallas()">
                <span class="material-symbols-rounded">close</span>
            </button>
        </div>
        
        <!-- Body -->
        <div class="modal-body">
            <form id="form-editor-tallas">
                <!-- DAMA -->
                <div class="form-section">
                    <label class="form-label-primary">
                        <span class="material-symbols-rounded">woman</span>DAMA
                    </label>
                    <div id="tallas-dama-container" class="tallas-genero-container" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(100px, 1fr)); gap: 0.75rem;"></div>
                </div>
                
                <!-- CABALLERO -->
                <div class="form-section">
                    <label class="form-label-primary">
                        <span class="material-symbols-rounded">man</span>CABALLERO
                    </label>
                    <div id="tallas-caballero-container" class="tallas-genero-container" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(100px, 1fr)); gap: 0.75rem;"></div>
                </div>
            </form>
        </div>
        
        <!-- Footer -->
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="cerrarEditorTallas()">Cancelar</button>
            <button class="btn btn-primary" onclick="guardarTallasSeleccionadas()">
                <span class="material-symbols-rounded">check</span>Guardar Tallas
            </button>
        </div>
    </div>
</div>
