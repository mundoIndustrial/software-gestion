<!-- MODAL: Confirmar eliminación de imagen de proceso -->
<!-- IMPORTANTE: Este modal está FUERA del modal-proceso-generico para que no sea ocultado por aria-hidden -->
<div id="modal-confirmar-eliminar-imagen-proceso" class="modal-overlay" style="z-index: 2147483648 !important; display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.7); backdrop-filter: blur(4px); align-items: center; justify-content: center;">
    <div class="modal-container" style="max-width: 400px; background: white; border-radius: 8px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);">
        <div class="modal-header modal-header-danger" style="padding: 1.5rem; border-bottom: 1px solid #e5e7eb; display: flex; align-items: center; gap: 0.75rem;">
            <span class="material-symbols-rounded" style="color: #dc2626; font-size: 24px;">warning</span>
            <h3 class="modal-title" style="margin: 0; color: #dc2626; font-weight: 600;">Confirmar eliminación</h3>
        </div>
        
        <div class="modal-body" style="padding: 1.5rem;">
            <p style="color: #374151; margin: 0; font-size: 0.95rem;">
                ¿Estás seguro de que deseas eliminar esta imagen?
            </p>
        </div>
        
        <div class="modal-footer" style="padding: 1rem 1.5rem; border-top: 1px solid #e5e7eb; display: flex; gap: 0.75rem; justify-content: flex-end;">
            <button type="button" class="btn btn-secondary" onclick="cerrarModalConfirmarEliminarImagen()" style="background: #f3f4f6; color: #374151; border: 1px solid #d1d5db; padding: 0.5rem 1rem; border-radius: 6px; cursor: pointer; font-weight: 500;">
                Cancelar
            </button>
            <button type="button" class="btn btn-danger" onclick="confirmarEliminarImagenProceso()" style="background: #dc2626; color: white; border: none; padding: 0.5rem 1rem; border-radius: 6px; cursor: pointer; font-weight: 500; display: flex; align-items: center; gap: 0.5rem;">
                <span class="material-symbols-rounded" style="font-size: 18px;">delete</span>Eliminar
            </button>
        </div>
    </div>
</div>
