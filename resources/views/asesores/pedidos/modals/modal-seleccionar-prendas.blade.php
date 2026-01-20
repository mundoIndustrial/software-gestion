<!-- MODAL: Seleccionar Prendas de Cotización -->
<div id="modal-seleccion-prendas" class="modal-overlay">
    <div class="modal-container modal-lg">
        <!-- Header -->
        <div class="modal-header modal-header-gradient">
            <h3 class="modal-title"> Seleccionar Prendas de la Cotización</h3>
            <button class="modal-close-btn" onclick="cerrarModalPrendas()">✕</button>
        </div>
        
        <!-- Body -->
        <div class="modal-body">
            <div id="modal-cotizacion-info" class="info-box">
                <div class="info-box-title">Cotización: <span id="modal-cot-numero"></span></div>
                <div class="info-box-subtitle">Cliente: <span id="modal-cot-cliente"></span></div>
            </div>
            
            <div id="lista-prendas-modal" class="prendas-list">
                <!-- Las prendas se cargarán aquí dinámicamente -->
            </div>
        </div>
        
        <!-- Footer -->
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="cerrarModalPrendas()">Cancelar</button>
            <button class="btn btn-primary" onclick="agregarPrendasSeleccionadas()">✓ Agregar Prendas Seleccionadas</button>
        </div>
    </div>
</div>
