<!-- Modal para Editar Novedades -->
<div id="novedadesEditModal" class="novedades-modal-overlay" style="display: none;">
    <div class="novedades-modal-content">
        <div class="novedades-modal-header">
            <h2 class="novedades-modal-title">Editar Novedades</h2>
            <p class="novedades-order-number" id="novedadesOrderNumber"></p>
            <button class="novedades-modal-close" onclick="closeNovedadesModal()" type="button">
                <span class="material-symbols-rounded">close</span>
            </button>
        </div>

        <div class="novedades-modal-body">
            <textarea 
                id="novedadesTextarea" 
                class="novedades-textarea"
                placeholder="Ingresa las novedades del pedido..."
                maxlength="1000"
                rows="8"
            ></textarea>
            <div class="textarea-counter">
                <span id="charCount">0</span>/1000
            </div>
        </div>

        <div class="novedades-modal-footer">
            <button class="btn-cancel" onclick="closeNovedadesModal()" type="button">
                <span class="material-symbols-rounded">cancel</span>
                Cancelar
            </button>
            <button class="btn-save" onclick="saveNovedades()" type="button">
                <span class="material-symbols-rounded">save</span>
                Guardar
            </button>
        </div>
    </div>
</div>
