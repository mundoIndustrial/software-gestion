<!-- MODAL: Seleccionar Tallas por Género -->
<div id="modal-seleccionar-tallas" class="modal-overlay">
    <div class="modal-container modal-md">
        <!-- Header -->
        <div class="modal-header modal-header-primary">
            <h2 id="modal-tallas-titulo" class="modal-title">
                <span class="material-symbols-rounded">straighten</span>
                Seleccionar Tallas - <span id="genero-modal-display">DAMA</span>
            </h2>
            <button class="modal-close-btn" onclick="cerrarModalSeleccionarTallas()">✕</button>
        </div>

        <!-- Body -->
        <div class="modal-body">
            <!-- Paso 1: Seleccionar tipo de talla -->
            <div class="modal-section">
                <label class="modal-label">
                    <span class="material-symbols-rounded">category</span>
                    Tipo de Talla:
                </label>
                <div class="button-group">
                    <button type="button" id="btn-tipo-letra" class="btn-toggle" onclick="seleccionarTipoTalla('letra')" data-selected="false">
                        LETRAS (XS-XXXL)
                    </button>
                    <button type="button" id="btn-tipo-numero" class="btn-toggle" onclick="seleccionarTipoTalla('numero')" data-selected="false">
                        NÚMEROS
                    </button>
                </div>
            </div>

            <!-- Paso 2: Seleccionar tallas específicas -->
            <div class="modal-section">
                <label class="modal-label">
                    <span class="material-symbols-rounded">checklist</span>
                    Selecciona Tallas:
                </label>
                <div id="container-tallas-seleccion" class="tallas-grid">
                    <!-- Se genera dinámicamente -->
                </div>
            </div>

            <!-- Botones de acción -->
            <div class="modal-actions">
                <button class="btn btn-secondary" onclick="cerrarModalSeleccionarTallas()">Cancelar</button>
                <button class="btn btn-primary" onclick="confirmarSeleccionTallas()">Confirmar</button>
            </div>
        </div>
    </div>
</div>
