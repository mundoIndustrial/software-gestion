<!-- Botones para mostrar registros y formulario -->
<div class="action-buttons">
    <button class="show-records-btn" @click="showRecords = !showRecords">
        <span x-text="showRecords ? 'Ocultar Tabla' : 'Mostrar Tabla'"></span>
    </button>
    <button class="show-form-btn" @click="openFormModal()">
        Formulario
    </button>
</div>

<!-- Espacio adicional -->
<div class="spacer-section"></div>
