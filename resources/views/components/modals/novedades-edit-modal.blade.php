<!-- Modal para Editar Novedades -->
<div id="novedadesEditModal" class="novedades-modal-overlay" style="display: none;">
    <div class="novedades-modal-content">
        <div class="novedades-modal-header">
            <h2 class="novedades-modal-title">Novedades del Pedido</h2>
            <p class="novedades-order-number" id="novedadesOrderNumber"></p>
            <button class="novedades-modal-close" onclick="closeNovedadesModal()" type="button">
                <span class="material-symbols-rounded">close</span>
            </button>
        </div>

        <div class="novedades-modal-body">
            <!-- Textarea para ver/editar historial de novedades (readonly por defecto) -->
            <textarea 
                id="novedadesTextarea" 
                class="novedades-textarea"
                placeholder="Sin novedades registradas"
                readonly
                rows="8"
            ></textarea>

            <!-- Input para agregar nueva novedad (oculto hasta hacer clic en +) -->
            <div id="nuevaNovedadContainer" style="display: none; margin-top: 15px; padding-top: 15px; border-top: 1px solid #e5e7eb;">
                <label style="display: block; font-size: 12px; color: #666; margin-bottom: 8px;">Agregar Nueva Novedad:</label>
                <textarea 
                    id="nuevaNovedadTextarea" 
                    class="novedades-textarea"
                    placeholder="Escribe la novedad aquí..."
                    maxlength="500"
                    rows="4"
                    style="background: #f9fafb; border: 2px solid #dbeafe;"
                ></textarea>
                <div class="textarea-counter" style="font-size: 12px; color: #999; margin-top: 5px;">
                    <span id="newCharCount">0</span>/500
                </div>
                <div style="display: flex; gap: 8px; margin-top: 10px;">
                    <button class="btn-cancel-new" onclick="cancelNewNovedad()" type="button" style="flex: 1;">
                        <span class="material-symbols-rounded">cancel</span>
                        Cancelar
                    </button>
                    <button class="btn-save-new" onclick="saveNewNovedad()" type="button" style="flex: 1;">
                        <span class="material-symbols-rounded">save</span>
                        Agregar
                    </button>
                </div>
            </div>
        </div>

        <div class="novedades-modal-footer">
            <button class="btn-cancel" onclick="closeNovedadesModal()" type="button">
                <span class="material-symbols-rounded">cancel</span>
                Cerrar
            </button>
            <button class="btn-edit-toggle" onclick="toggleEditMode()" type="button" id="btnEditToggle">
                <span class="material-symbols-rounded">edit</span>
                Editar
            </button>
            <button class="btn-add-new" onclick="showNewNovedadInput()" type="button" id="btnAddNew">
                <span class="material-symbols-rounded">add</span>
                Agregar Novedad
            </button>
            <button class="btn-save" onclick="saveEditedNovedades()" type="button" id="btnSaveEdit" style="display: none;">
                <span class="material-symbols-rounded">save</span>
                Guardar Cambios
            </button>
        </div>
    </div>
</div>
