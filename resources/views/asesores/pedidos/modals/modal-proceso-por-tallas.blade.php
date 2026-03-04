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
            <button class="modal-close-btn" onclick="cerrarModalProcesoPorTallas()">
                <span class="material-symbols-rounded">close</span>
            </button>
        </div>

        <!-- Body -->
        <div class="modal-body" style="overflow-y: auto; flex: 1; padding: 1.25rem;">
            
            <!-- Sección DAMA -->
            <div id="seccion-dama-por-tallas" style="display: none; margin-bottom: 1.5rem;">
                <h4 style="margin: 0 0 0.75rem; padding: 0.5rem 1rem; background: linear-gradient(135deg, #fce7f3, #fbcfe8); border-radius: 8px; font-size: 0.95rem; font-weight: 800; color: #be185d; display: flex; align-items: center; gap: 0.5rem;">
                    <span class="material-symbols-rounded" style="font-size: 1.2rem;">woman</span>DAMA
                </h4>
                <div id="tallas-dama-por-tallas" style="display: grid; grid-template-columns: 1fr; gap: 1rem;"></div>
            </div>

            <!-- Sección CABALLERO -->
            <div id="seccion-caballero-por-tallas" style="display: none; margin-bottom: 1.5rem;">
                <h4 style="margin: 0 0 0.75rem; padding: 0.5rem 1rem; background: linear-gradient(135deg, #dbeafe, #bfdbfe); border-radius: 8px; font-size: 0.95rem; font-weight: 800; color: #1d4ed8; display: flex; align-items: center; gap: 0.5rem;">
                    <span class="material-symbols-rounded" style="font-size: 1.2rem;">man</span>CABALLERO
                </h4>
                <div id="tallas-caballero-por-tallas" style="display: grid; grid-template-columns: 1fr; gap: 1rem;"></div>
            </div>

            <!-- Mensaje cuando no hay tallas -->
            <div id="sin-tallas-por-tallas" style="display: none; text-align: center; padding: 3rem 1rem;">
                <span class="material-symbols-rounded" style="font-size: 3rem; color: #f59e0b;">warning</span>
                <h4 style="color: #92400e; margin: 0.75rem 0 0.5rem;">Sin Tallas</h4>
                <p style="color: #6b7280; font-size: 0.9rem;">Primero debes agregar tallas y cantidades a la prenda.</p>
            </div>
        </div>

        <!-- Footer -->
        <div class="modal-footer" style="flex-shrink: 0;">
            <button class="btn btn-secondary" onclick="cerrarModalProcesoPorTallas()">Cancelar</button>
            <button class="btn btn-primary" onclick="guardarProcesoPorTallas()">
                <span class="material-symbols-rounded">check</span>Guardar Proceso
            </button>
        </div>
    </div>
</div>
