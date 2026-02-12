{{-- Modales de Filtro Compartidos para Cartera de Pedidos --}}

<!-- MODAL FILTRO CLIENTE -->
<div id="modalFiltroCliente" class="modal-filter">
    <div class="modal-filter-content">
        <div class="modal-filter-header">
            <h3>Filtrar por Cliente</h3>
            <button type="button" class="modal-filter-close" onclick="cerrarModalFiltro('cliente')">&times;</button>
        </div>
        <div class="modal-filter-body">
            <div class="form-group">
                <label for="filtroClienteInput">Buscar cliente:</label>
                <input type="text" id="filtroClienteInput" class="form-control" placeholder="Escriba el nombre del cliente..." autocomplete="off" onkeyup="buscarSugerenciasCliente()">
                <!-- Contenedor de sugerencias -->
                <div id="sugerenciasCliente" class="sugerencias-container" style="display: none;">
                    <!-- Las sugerencias se cargarán dinámicamente -->
                </div>
            </div>
        </div>
        <div class="modal-filter-footer">
            <button type="button" class="modal-filter-footer button btn-filter-cancel" onclick="cerrarModalFiltro('cliente')">Cancelar</button>
            <button type="button" class="modal-filter-footer button btn-filter-apply" onclick="aplicarFiltroCliente()">Aplicar Filtro</button>
        </div>
    </div>
</div>

<!-- MODAL FILTRO NÚMERO -->
<div id="modalFiltroNumero" class="modal-filter">
    <div class="modal-filter-content">
        <div class="modal-filter-header">
            <h3>Filtrar por N° Pedido</h3>
            <button type="button" class="modal-filter-close" onclick="cerrarModalFiltro('numero')">&times;</button>
        </div>
        <div class="modal-filter-body">
            <div class="form-group">
                <label for="filtroNumeroInput">Buscar número:</label>
                <input type="text" id="filtroNumeroInput" class="form-control" placeholder="Escriba el número de pedido..." autocomplete="off" onkeyup="buscarSugerenciasNumero()">
                <!-- Contenedor de sugerencias -->
                <div id="sugerenciasNumero" class="sugerencias-container" style="display: none;">
                    <!-- Las sugerencias se cargarán dinámicamente -->
                </div>
            </div>
        </div>
        <div class="modal-filter-footer">
            <button type="button" class="modal-filter-footer button btn-filter-cancel" onclick="cerrarModalFiltro('numero')">Cancelar</button>
            <button type="button" class="modal-filter-footer button btn-filter-apply" onclick="aplicarFiltroNumero()">Aplicar Filtro</button>
        </div>
    </div>
</div>

<!-- MODAL FILTRO FECHA -->
<div id="modalFiltroFecha" class="modal-filter">
    <div class="modal-filter-content">
        <div class="modal-filter-header">
            <h3>Filtrar por Fecha</h3>
            <button type="button" class="modal-filter-close" onclick="cerrarModalFiltro('fecha')">&times;</button>
        </div>
        <div class="modal-filter-body">
            <div class="form-group">
                <label for="filtroFechaInput">Buscar fecha:</label>
                <input type="text" id="filtroFechaInput" class="form-control" placeholder="Escriba la fecha (dd/mm/yyyy)..." autocomplete="off" onkeyup="buscarSugerenciasFecha()">
                <!-- Contenedor de sugerencias -->
                <div id="sugerenciasFecha" class="sugerencias-container" style="display: none;">
                    <!-- Las sugerencias se cargarán dinámicamente -->
                </div>
            </div>
        </div>
        <div class="modal-filter-footer">
            <button type="button" class="modal-filter-footer button btn-filter-cancel" onclick="cerrarModalFiltro('fecha')">Cancelar</button>
            <button type="button" class="modal-filter-footer button btn-filter-apply" onclick="aplicarFiltroFecha()">Aplicar Filtro</button>
        </div>
    </div>
</div>

<!-- MODAL FILTRO ESTADO (opcional, solo para algunas vistas) -->
<div id="modalFiltroEstado" class="modal-filter">
    <div class="modal-filter-content">
        <div class="modal-filter-header">
            <h3>Filtrar por Estado</h3>
            <button type="button" class="modal-filter-close" onclick="cerrarModalFiltro('estado')">&times;</button>
        </div>
        <div class="modal-filter-body">
            <div class="form-group">
                <label for="filtroEstadoInput">Buscar estado:</label>
                <input type="text" id="filtroEstadoInput" class="form-control" placeholder="Escriba el estado..." autocomplete="off" onkeyup="buscarSugerenciasEstado()">
                <!-- Contenedor de sugerencias -->
                <div id="sugerenciasEstado" class="sugerencias-container" style="display: none;">
                    <!-- Las sugerencias se cargarán dinámicamente -->
                </div>
            </div>
        </div>
        <div class="modal-filter-footer">
            <button type="button" class="modal-filter-footer button btn-filter-cancel" onclick="cerrarModalFiltro('estado')">Cancelar</button>
            <button type="button" class="modal-filter-footer button btn-filter-apply" onclick="aplicarFiltroEstado()">Aplicar Filtro</button>
        </div>
    </div>
</div>

<!-- BOTÓN FLOTANTE PARA LIMPIAR FILTROS -->
<div id="btnLimpiarFiltros" class="btn-limpiar-filtros" style="display: none;" onclick="limpiarTodosLosFiltros()" title="Limpiar todos los filtros">
    <span class="material-symbols-rounded">clear_all</span>
    <span class="btn-limpiar-texto">Limpiar Filtros</span>
</div>

<!-- Toast Notifications -->
<div id="toastContainer" class="toast-container"></div>
<!-- Modal para ver Novedades Completas -->
<div id="modalVerNovedades" class="modal-filter">
    <div class="modal-filter-content" style="max-width: 700px;">
        <div class="modal-filter-header">
            <h3 id="novedadesTitulo" style="margin: 0;">Novedades del Pedido</h3>
            <button type="button" class="modal-filter-close" onclick="cerrarModalNovedades()" title="Cerrar">
                <span class="material-symbols-rounded">close</span>
            </button>
        </div>
        <div class="modal-filter-body">
            <div id="novedadesContenido" style="padding: 16px; background: #f3f4f6; border-radius: 6px; max-height: 400px; overflow-y: auto; border: 1px solid #e5e7eb; line-height: 1.6; color: #374151;">
                <!-- Contenido de novedades se carga aquí -->
            </div>
        </div>
    </div>
</div>