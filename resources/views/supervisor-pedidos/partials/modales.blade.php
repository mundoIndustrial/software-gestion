<!-- Modal Filtro Dinámico -->
<div id="modalFiltro" class="modal-overlay" style="display: none;">
    <div class="modal-content" style="width: 90%; max-width: 400px;">
        <div class="modal-header">
            <h2 id="modalFiltroTitulo">Filtrar</h2>
            <button class="btn-close" onclick="cerrarModalFiltro()">
                <span class="material-symbols-rounded">close</span>
            </button>
        </div>
        <div class="modal-body">
            <form id="formFiltroColumna" onsubmit="aplicarFiltroColumna(event)">
                <div class="form-group" id="filtroContenido">
                    <!-- Contenido dinámico según la columna -->
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="cerrarModalFiltro()">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary" style="background: var(--primary-color); color: white;">
                        Aplicar Filtro
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Ver Orden -->
<div id="modalVerOrden" class="modal-overlay" style="display: none;">
    <div class="modal-content modal-lg">
        <div class="modal-header">
            <h2>Detalle de Orden</h2>
            <button class="btn-close" onclick="cerrarModalVerOrden()">
                <span class="material-symbols-rounded">close</span>
            </button>
        </div>
        <div class="modal-body" id="modalVerOrdenContent">
            <!-- Contenido cargado dinámicamente -->
        </div>
    </div>
</div>

<!-- Modal Anulación -->
<div id="modalAnulacion" class="modal-overlay" style="display: none;">
    <div class="modal-content modal-anulacion">
        <div class="modal-header">
            <div class="header-icon">
                <span class="material-symbols-rounded">warning</span>
            </div>
            <h2>¿Pasar a Revisión Orden <span id="ordenNumero"></span>?</h2>
        </div>

        <div class="modal-body">
            <p class="advertencia-texto">
                Esta acción enviará la orden de vuelta a la asesora para revisión. Por favor ingresa el motivo de la revisión.
            </p>

            <form id="formAnulacion" onsubmit="confirmarAnulacion(event)">
                @csrf
                <div class="form-group">
                    <label for="motivoAnulacion">Motivo de la revisión *</label>
                    <textarea
                        id="motivoAnulacion"
                        name="motivo_anulacion"
                        class="form-control"
                        rows="4"
                        placeholder="Ej: Revisar precios, errores en especificaciones..."
                        required
                        minlength="10"
                        maxlength="500">
                    </textarea>
                    <small class="contador-caracteres">
                        <span id="contadorActual">0</span>/500 caracteres
                    </small>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="cerrarModalAnulacion()">
                        Cancelar
                    </button>
                    <button type="submit" id="btnConfirmarAnulacion" class="btn btn-danger">
                        <span class="material-symbols-rounded">delete</span>
                        Pasar a Revisión
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de Éxito para Pasar a Revisión -->
<div id="modalExitoRevision" class="modal-overlay" style="display: none;">
    <div class="modal-content modal-anulacion" style="text-align: center; max-width: 400px;">
        <div class="modal-header">
            <div class="header-icon" style="background: #d4edda; color: #28a745;">
                <span class="material-symbols-rounded">check_circle</span>
            </div>
            <h2>¡Éxito!</h2>
        </div>

        <div class="modal-body">
            <p style="color: #28a745; font-weight: 500; margin-bottom: 1.5rem;">
                La orden ha sido enviada a revisión correctamente
            </p>
            <p style="color: #7f8c8d; font-size: 0.9rem; margin-bottom: 1.5rem;">
                La asesora recibirá la notificación del cambio.
            </p>

            <div class="form-actions" style="justify-content: center;">
                <button type="button" class="btn btn-success" onclick="cerrarModalExitoRevision()">
                    Aceptar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Novedades -->
<div id="modalNovedades" class="modal-overlay" style="display: none;">
    <div class="modal-content" style="width: 90%; max-width: 700px; max-height: 75vh; display: flex; flex-direction: column;">
        <div class="modal-header" style="border-bottom: 2px solid #1e40af; background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%); color: white; padding: 1.5rem;">
            <h2 style="margin: 0; font-size: 1.2rem; color: white;"> Historial de Novedades</h2>
            <button class="btn-close" onclick="cerrarModalNovedades()" style="background: none; border: none; cursor: pointer; font-size: 1.5rem; color: white; position: absolute; right: 1rem; top: 1rem;">
                <span class="material-symbols-rounded">close</span>
            </button>
        </div>
        <div id="modalNovedadesContent" style="overflow-y: auto; flex: 1; padding: 2rem; background: #f9fafb; margin: 0; border: none; color: #1f2937;">
        <!-- Contenido de novedades formateado -->
        </div>

    </div>
</div>

<!-- Modal Ocultar Pedido -->
<div id="modalOcultar" class="modal-overlay" style="display: none;">
    <div class="modal-content modal-anulacion" style="text-align: center; max-width: 400px;">
        <div class="modal-header">
            <div class="header-icon" style="background: #fef3c7; color: #d97706;">
                <span class="material-symbols-rounded">visibility_off</span>
            </div>
            <h2>Ocultar Pedido <span id="ordenOcultarNumero"></span>?</h2>
        </div>

        <div class="modal-body">
            <p style="color: #7f8c8d; font-size: 0.95rem; margin-bottom: 1.5rem;">
                Al ocultar este pedido, dejará de aparecer en tu vista de supervisor-pedidos. Podrás mostrarlo nuevamente desde la vista de pedidos no filtrados si es necesario.
            </p>

            <div class="form-actions" style="justify-content: center; gap: 1rem;">
                <button type="button" class="btn btn-secondary" onclick="cerrarModalOcultar()">
                    Cancelar
                </button>
                <button type="button" class="btn btn-warning" id="btnConfirmarOcultar" onclick="confirmarOcultar()">
                    <span class="material-symbols-rounded">visibility_off</span>
                    Ocultar Pedido
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Éxito para Ocultar -->
<div id="modalExitoOcultar" class="modal-overlay" style="display: none;">
    <div class="modal-content modal-anulacion" style="text-align: center; max-width: 400px;">
        <div class="modal-header">
            <div class="header-icon" style="background: #d4edda; color: #28a745;">
                <span class="material-symbols-rounded">check_circle</span>
            </div>
            <h2>¡Pedido Oculto!</h2>
        </div>

        <div class="modal-body">
            <p style="color: #28a745; font-weight: 500; margin-bottom: 1.5rem;">
                El pedido ha sido ocultado correctamente
            </p>
            <p style="color: #7f8c8d; font-size: 0.9rem; margin-bottom: 1.5rem;">
                El pedido será removido de tu vista de supervisor-pedidos.
            </p>

            <div class="form-actions" style="justify-content: center;">
                <button type="button" class="btn btn-success" onclick="cerrarModalExitoOcultar()">
                    Aceptar
                </button>
            </div>
        </div>
    </div>
</div>
