<!-- MODAL: Confirmar eliminación de imagen de proceso -->
<!-- IMPORTANTE: Este modal está FUERA del modal-proceso-generico para que no sea ocultado por aria-hidden -->
<div id="modal-confirmar-eliminar-imagen-proceso" class="modal-overlay" style="z-index: 999999999 !important; display: none;">
    <div class="modal-container" style="max-width: 400px;">
        <div class="modal-header modal-header-danger">
            <h3 class="modal-title">
                <span class="material-symbols-rounded">warning</span>
                Confirmar eliminación
            </h3>
        </div>
        
        <div class="modal-body">
            <p style="color: #374151; margin-bottom: 1rem;">
                ¿Estás seguro de que deseas eliminar esta imagen?
            </p>
            <p style="color: #6b7280; font-size: 0.875rem;">
                La imagen se eliminará de la vista inmediatamente. Cuando hagas clic en "Guardar cambios", se borrará definitivamente de la base de datos y del almacenamiento.
            </p>
        </div>
        
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="cerrarModalConfirmarEliminarImagen()">
                Cancelar
            </button>
            <button type="button" class="btn btn-danger" onclick="confirmarEliminarImagenProceso()">
                <span class="material-symbols-rounded">delete</span>Eliminar imagen
            </button>
        </div>
    </div>
</div>
