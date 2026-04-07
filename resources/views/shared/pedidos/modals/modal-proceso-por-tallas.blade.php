<!-- MODAL: Configurar Proceso Por Tallas -->
<!-- Modal dedicado para cuando el usuario elige "Por Tallas" en el selector de modo -->
<div id="modal-proceso-por-tallas" class="modal-overlay" style="z-index: 9999999999 !important; display: none;">
    <div class="modal-container" style="max-width: 900px; width: 95%; max-height: 90vh; display: flex; flex-direction: column;">
        <!-- Header -->
        <div class="modal-header modal-header-primary" style="flex-shrink: 0;">
            <h3 class="modal-title">
                <span class="material-symbols-rounded" id="modal-por-tallas-icon">edit_note</span>
                <span id="modal-por-tallas-titulo">Configurar Proceso por Tallas</span>
            </h3>
            <!-- Selector de Modo -->
            <div style="display: flex; gap: 0.5rem; align-items: center; margin-right: 1rem;">
                <button id="btn-modo-general" class="btn-modo-activo" data-prt-action="cambiar-modo" data-mode="general"
                    style="padding: 0.4rem 1rem; border: 1px solid #e5e7eb; border-radius: 6px; background: white; font-size: 0.85rem; font-weight: 600; cursor: pointer; transition: all 0.2s; color: #6b7280;">
                    General
                </button>
                <button id="btn-modo-especifico" class="btn-modo-inactivo" data-prt-action="cambiar-modo" data-mode="especifico"
                    style="padding: 0.4rem 1rem; border: 1px solid #e5e7eb; border-radius: 6px; background: #f9fafb; font-size: 0.85rem; font-weight: 600; cursor: pointer; transition: all 0.2s; color: #6b7280;">
                    Específico
                </button>
            </div>
            <button class="modal-close-btn" data-prt-action="cerrar-modal">
                <span class="material-symbols-rounded">close</span>
            </button>
        </div>

        <!-- Body -->
        <div class="modal-body" style="overflow-y: auto; flex: 1; padding: 1.25rem;">
            
            <!-- ════════════════════════════════════════════════════════════════ -->
            <!-- MODO GENERAL: Ubicación general + Observaciones por talla + Imágenes generales -->
            <!-- ════════════════════════════════════════════════════════════════ -->
            <div id="modo-general-container" style="display: block;">
                <!-- Ubicación General (compartida) -->
                <div style="background: #f5f5f5; border-radius: 8px; padding: 1.5rem; margin-bottom: 2rem; border: 1px solid #e0e0e0;">
                    <label style="font-size: 0.9rem; font-weight: 600; color: #333; display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.75rem;">
                        <span class="material-symbols-rounded" style="font-size: 1.2rem;">public</span>Ubicación General
                    </label>
                    <textarea id="ubicacion-general-input" placeholder="Ej: Frente, Espalda..." 
                        style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 6px; font-size: 0.85rem; min-height: 70px; resize: vertical; box-sizing: border-box;"></textarea>
                </div>

                <!-- Observaciones por Talla en Modo General -->
                <div style="margin-bottom: 1.5rem;">
                    <h4 style="margin: 0 0 1rem; font-size: 0.95rem; font-weight: 600; color: #333;">Observaciones por Talla</h4>
                    
                    <!-- Sección DAMA -->
                    <div id="seccion-dama-modo-general" style="display: none; margin-bottom: 1.5rem;">
                        <h5 style="margin: 0 0 0.75rem; padding: 0.5rem 1rem; background: #efefef; border-radius: 6px; font-size: 0.95rem; font-weight: 600; color: #333; display: flex; align-items: center; gap: 0.5rem;">
                            <span class="material-symbols-rounded" style="font-size: 1.2rem;">woman</span>DAMA
                        </h5>
                        <div id="tallas-dama-modo-general" style="display: grid; grid-template-columns: 1fr; gap: 1rem;"></div>
                    </div>

                    <!-- Sección CABALLERO -->
                    <div id="seccion-caballero-modo-general" style="display: none; margin-bottom: 1.5rem;">
                        <h5 style="margin: 0 0 0.75rem; padding: 0.5rem 1rem; background: #efefef; border-radius: 6px; font-size: 0.95rem; font-weight: 600; color: #333; display: flex; align-items: center; gap: 0.5rem;">
                            <span class="material-symbols-rounded" style="font-size: 1.2rem;">man</span>CABALLERO
                        </h5>
                        <div id="tallas-caballero-modo-general" style="display: grid; grid-template-columns: 1fr; gap: 1rem;"></div>
                    </div>
                </div>

                <!-- Imágenes Generales (compartidas) -->
                <div style="background: #f5f5f5; border-radius: 8px; padding: 1.5rem; margin-bottom: 2rem; border: 1px solid #e0e0e0;">
                    <div style="display: flex; flex-direction: column; gap: 0.35rem;">
                        <label style="font-size: 0.9rem; font-weight: 600; color: #333; display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.75rem;">
                            <span class="material-symbols-rounded" style="font-size: 1.2rem;">photo_camera</span>Fotos Generales
                        </label>
                        <div id="prt-galeria-general" style="display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: flex-start;">
                            <div data-prt-action="abrir-input" data-target-id="prt-foto-input-general"
                                style="width: 70px; height: 70px; border: 2px dashed #ccc; border-radius: 6px; display: flex; align-items: center; justify-content: center; cursor: pointer; background: white; flex-shrink: 0;">
                                <div style="text-align:center;">
                                    <span class="material-symbols-rounded" style="font-size:1.3rem;color:#999;">add_photo_alternate</span>
                                    <div style="font-size:0.6rem;color:#999;">Agregar</div>
                                </div>
                            </div>
                        </div>
                        <input type="file" id="prt-foto-input-general" data-prt-action="cargar-fotos-generales" accept="image/*" multiple style="display:none;">
                    </div>
                </div>
            </div>

            <!-- ════════════════════════════════════════════════════════════════ -->
            <!-- MODO ESPECÍFICO: Por talla ubicación, observación e imágenes (original) -->
            <!-- ════════════════════════════════════════════════════════════════ -->
            <div id="modo-especifico-container" style="display: none;">
            
                <!-- Sección DAMA -->
                <div id="seccion-dama-por-tallas" style="display: none; margin-bottom: 1.5rem;">
                <h4 style="margin: 0 0 0.75rem; padding: 0.5rem 1rem; background: #efefef; border-radius: 6px; font-size: 0.95rem; font-weight: 600; color: #333; display: flex; align-items: center; gap: 0.5rem;">
                    <span class="material-symbols-rounded" style="font-size: 1.2rem;">woman</span>DAMA
                </h4>
                <div id="tallas-dama-por-tallas" style="display: grid; grid-template-columns: 1fr; gap: 1rem;"></div>
            </div>

            <!-- Sección CABALLERO -->
            <div id="seccion-caballero-por-tallas" style="display: none; margin-bottom: 1.5rem;">
                <h4 style="margin: 0 0 0.75rem; padding: 0.5rem 1rem; background: #efefef; border-radius: 6px; font-size: 0.95rem; font-weight: 600; color: #333; display: flex; align-items: center; gap: 0.5rem;">
                    <span class="material-symbols-rounded" style="font-size: 1.2rem;">man</span>CABALLERO
                </h4>
                <div id="tallas-caballero-por-tallas" style="display: grid; grid-template-columns: 1fr; gap: 1rem;"></div>
            </div>

            <!-- Mensaje cuando no hay tallas -->
            <div id="sin-tallas-por-tallas" style="display: none; text-align: center; padding: 3rem 1rem;">
                <span class="material-symbols-rounded" style="font-size: 3rem; color: #ccc;">warning</span>
                <h4 style="color: #666; margin: 0.75rem 0 0.5rem;">Sin Tallas</h4>
                <p style="color: #999; font-size: 0.9rem;">Primero debes agregar tallas y cantidades a la prenda.</p>
            </div>
            </div>
            <!-- Fin de modo-especifico-container -->
        </div>
        <!-- Fin de modal-body -->

        <!-- Footer -->
        <div class="modal-footer" style="flex-shrink: 0;">
            <button class="btn btn-secondary" data-prt-action="cerrar-modal">Cancelar</button>
            <button class="btn btn-primary" data-prt-action="guardar-modal">
                <span class="material-symbols-rounded">check</span>Guardar Proceso
            </button>
        </div>
    </div>
</div>
