{{-- Modal para ver insumos --}}
<div id="insumosModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center" style="display: none; z-index: 10001; top: 0; left: 0; right: 0; bottom: 0;">
    <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto" style="z-index: 10002;">
        <div class="sticky top-0 bg-gradient-to-r from-blue-600 to-blue-700 text-white p-6 flex justify-between items-center" style="z-index: 10003;">
            <div>
                <h2 class="text-2xl font-bold flex items-center gap-2">
                    <i class="fas fa-box"></i>
                    Insumos de la Orden
                </h2>
                <p class="text-blue-100 text-sm">Pedido: <span id="modalPedido" class="font-bold"></span></p>
                <p class="text-blue-100 text-sm">Prenda: <span id="modalPrendaNombre" class="font-bold"></span></p>
                <input type="hidden" id="modalPrendaId" value="">
            </div>
            <button type="button" data-insumos-action="modal-insumos-close" class="text-white hover:bg-blue-600 rounded-full p-2 transition">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>

        <div class="p-6">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-100 border-b-2 border-gray-300">
                            <th class="text-left py-3 px-4 font-bold text-gray-800 min-w-max">Insumo</th>
                            <th class="text-center py-3 px-3 font-bold text-gray-800">Estado</th>
                            <th class="text-center py-3 px-3 font-bold text-gray-800">Fecha Orden</th>
                            <th class="text-center py-3 px-3 font-bold text-gray-800">Fecha Pedido</th>
                            <th class="text-center py-3 px-3 font-bold text-gray-800">Fecha Pago</th>
                            <th class="text-center py-3 px-3 font-bold text-gray-800">Fecha Despacho</th>
                            <th class="text-center py-3 px-3 font-bold text-gray-800">Fecha Llegada</th>
                            <th class="text-center py-3 px-3 font-bold text-gray-800">Días Demora</th>
                            <th class="text-center py-3 px-3 font-bold text-gray-800">Observaciones</th>
                            <th class="text-center py-3 px-3 font-bold text-gray-800">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="insumosTableBody">
                        <!-- Se llena dinámicamente -->
                    </tbody>
                </table>
            </div>

            <div class="mt-6 flex gap-3 justify-between">
                <div class="flex gap-3">
                    <button 
                        data-insumos-action="material-add-row"
                        class="px-6 py-2 bg-green-600 text-white font-semibold rounded-lg hover:bg-green-700 transition flex items-center gap-2"
                    >
                        <i class="fas fa-plus"></i> Agregar Insumo
                    </button>
                </div>
                <div class="flex gap-3">
                    <button 
                        type="button"
                        data-insumos-action="material-save-changes"
                        class="px-6 py-2 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition flex items-center gap-2"
                    >
                        <i class="fas fa-save"></i> Guardar Cambios
                    </button>
                    <button 
                        type="button"
                        data-insumos-action="modal-insumos-close"
                        class="px-6 py-2 bg-gray-400 text-white font-semibold rounded-lg hover:bg-gray-500 transition flex items-center gap-2"
                    >
                        <i class="fas fa-times"></i> Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal para ver/editar observaciones --}}
<div id="observacionesModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center" style="display: none; z-index: 10001;">
    <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4" style="z-index: 10002;">
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white p-6 flex justify-between items-center">
            <div>
                <h2 class="text-2xl font-bold flex items-center gap-2">
                    <i class="fas fa-sticky-note"></i>
                    Observaciones del Insumo
                </h2>
                <p class="text-blue-100 text-sm">Material: <span id="observacionesMaterial" class="font-bold"></span></p>
            </div>
            <button type="button" data-insumos-action="observaciones-close" class="text-white hover:bg-blue-600 rounded-full p-2 transition">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div class="p-6">
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Observaciones:</label>
                <textarea 
                    id="observacionesTexto" 
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none" 
                    rows="6"
                    placeholder="Escribe las observaciones del insumo aquí..."
                    data-insumos-action="observaciones-textarea"
                ></textarea>
                <p class="text-gray-500 text-xs mt-2">💡 Presiona <strong>Ctrl + Enter</strong> para guardar rápidamente</p>
            </div>
            <div class="flex gap-3 justify-end">
                <button 
                    type="button"
                    data-insumos-action="observaciones-save"
                    class="px-6 py-2 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition flex items-center gap-2"
                >
                    <i class="fas fa-save"></i> Guardar
                </button>
                <button 
                    type="button"
                    data-insumos-action="observaciones-close"
                    class="px-6 py-2 bg-gray-400 text-white font-semibold rounded-lg hover:bg-gray-500 transition flex items-center gap-2"
                >
                    <i class="fas fa-times"></i> Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Modal de Ancho y Metraje --}}
<div id="modalAnchoMetraje" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center" style="z-index: 10001;">
    <div class="bg-white rounded-2xl shadow-2xl max-w-3xl w-full mx-3 md:mx-4 max-h-[92vh] flex flex-col overflow-hidden" style="z-index: 10002;">
        <div class="bg-gradient-to-r from-blue-900 to-blue-800 text-white px-4 py-3 md:px-5 md:py-4 flex justify-between items-center shadow-lg flex-shrink-0" style="background: linear-gradient(to right, #111827, #1e3a8a) !important;">
            <div>
                <h2 class="text-lg md:text-xl font-bold flex items-center gap-2 drop-shadow text-white">
                    <i class="fas fa-ruler"></i>
                    Ancho y Metraje - Recibo: <span id="anchoMetrajeRecibo" class="font-bold text-white">-</span>
                </h2>
                <p class="text-blue-100 text-xs md:text-sm mt-1">Registra medida de acuerdo al modo elegido.</p>
            </div>
            <button type="button" data-insumos-action="ancho-metraje-close" class="text-white bg-blue-700 rounded-full p-2 transition hover:bg-blue-600 flex-shrink-0 border border-blue-500">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>
        <div class="overflow-y-auto flex-1 p-4 md:p-6 space-y-5 bg-slate-50">
            <!-- Indicador de carga mientras se obtienen los datos -->
            <div id="anchoMetrajeLoading" class="text-center py-6">
                <i class="fas fa-spinner fa-spin text-2xl text-blue-600"></i>
                <p class="text-sm text-gray-500 mt-2">Cargando datos...</p>
            </div>

            <!-- SELECTOR DE MODO: Normal, Por Color, Por Pieza o Manual -->
            <div id="modoSelector" class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm hidden">
                <p class="text-sm font-semibold text-gray-700 mb-3">¿Cómo deseas ingresar el ancho y metraje?</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 md:gap-3">
                    <label data-modo-card class="flex items-start gap-3 cursor-pointer border border-slate-200 rounded-xl px-3 py-2.5 bg-white transition">
                        <input type="radio" name="modoAnchoMetraje" value="normal" class="modoRadio mt-1" checked>
                        <span class="block">
                            <span class="text-gray-900 font-semibold text-sm flex items-center gap-2"><i class="fas fa-list"></i> Normal</span>
                            <span class="text-xs text-gray-500">Un solo ancho y metraje</span>
                        </span>
                    </label>
                    <label data-modo-card class="flex items-start gap-3 cursor-pointer border border-slate-200 rounded-xl px-3 py-2.5 bg-white transition">
                        <input type="radio" name="modoAnchoMetraje" value="color" class="modoRadio mt-1">
                        <span class="block">
                            <span class="text-gray-900 font-semibold text-sm flex items-center gap-2"><i class="fas fa-palette"></i> Por Color</span>
                            <span class="text-xs text-gray-500">Metrado separado por color</span>
                        </span>
                    </label>
                    <label data-modo-card class="flex items-start gap-3 cursor-pointer border border-slate-200 rounded-xl px-3 py-2.5 bg-white transition">
                        <input type="radio" name="modoAnchoMetraje" value="pieza" class="modoRadio mt-1">
                        <span class="block">
                            <span class="text-gray-900 font-semibold text-sm flex items-center gap-2"><i class="fas fa-cubes"></i> Por Pieza</span>
                            <span class="text-xs text-gray-500">Control por pieza/color</span>
                        </span>
                    </label>
                    <label data-modo-card class="flex items-start gap-3 cursor-pointer border border-slate-200 rounded-xl px-3 py-2.5 bg-white transition">
                        <input type="radio" name="modoAnchoMetraje" value="mano" class="modoRadio mt-1">
                        <span class="block">
                            <span class="text-gray-900 font-semibold text-sm flex items-center gap-2"><i class="fas fa-pen"></i> Manual</span>
                            <span class="text-xs text-gray-500">Texto libre</span>
                        </span>
                    </label>
                </div>
            </div>

            <div id="modoActivoInfo" class="bg-indigo-50 border border-indigo-200 rounded-xl px-4 py-3 hidden shadow-sm">
                <p class="text-sm text-indigo-900 flex items-center gap-2">
                    <i class="fas fa-shield-alt"></i><strong>Modo activo:</strong> <span id="modoActivoLabel">Normal</span>
                </p>
                <p id="modoActivoAyuda" class="text-xs text-indigo-700 mt-1">Un ancho y un metraje para toda la prenda.</p>
                <p id="modoGuardadoLabel" class="text-xs text-amber-700 mt-2 hidden"></p>
            </div>

            <!-- VISTA NORMAL: Un ancho/metraje por prenda -->
            <div id="normalView" class="space-y-4 hidden">
                <div class="bg-green-50 border-l-4 border-green-600 p-3 mb-4 hidden" id="normalDataWarning">
                    <p class="text-sm text-green-900">
                        <i class="fas fa-check-circle mr-2"></i>
                        No hay datos guardados. Ingresa los valores a continuación.
                    </p>
                </div>
                <div>
                    <label class="block text-base font-bold text-gray-800 mb-2">Ancho:</label>
                    <input 
                        type="text" 
                        id="anchoInput" 
                        class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-transparent"
                        placeholder="Ingresa el ancho..."
                    >
                </div>
                <div>
                    <label class="block text-base font-bold text-gray-800 mb-2">Metraje:</label>
                    <input 
                        type="text" 
                        id="metrajeInput" 
                        class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-transparent"
                        placeholder="Ingresa el metraje..."
                    >
                </div>
            </div>

            <!-- VISTA POR COLOR: Para prendas con múltiples colores -->
            <div id="colorView" class="space-y-4 hidden">
                <div class="bg-blue-50 border-l-4 border-blue-600 p-3 mb-4 hidden" id="colorDataWarning">
                    <p class="text-sm text-blue-900">
                        <i class="fas fa-info-circle mr-2"></i>
                        No hay datos guardados. Ingresa los valores por color a continuación.
                    </p>
                </div>
                <div id="colorInputsContainer" class="space-y-4">
                    <!-- Los inputs por color se generarán dinámicamente aquí -->
                </div>
            </div>

            <!-- VISTA POR PIEZA: Para prendas combinadas (talla-color) -->
            <div id="piezaView" class="space-y-4 hidden">
                <div class="bg-orange-50 border-l-4 border-orange-600 p-3 mb-4 hidden" id="piezaDataWarning">
                    <p class="text-sm text-orange-900">
                        <i class="fas fa-info-circle mr-2"></i>
                        No hay datos guardados. Ingresa los valores por pieza a continuación.
                    </p>
                </div>
                <div id="piezaInputsContainer" class="space-y-4">
                    <!-- Los inputs por pieza/talla-color se generarán dinámicamente aquí -->
                </div>
            </div>

            <!-- VISTA A MANO: Ingresar texto libre para ancho y metraje -->
            <div id="manoView" class="space-y-4 hidden">
                <div class="bg-purple-50 border-l-4 border-purple-600 p-3 mb-4">
                    <p class="text-sm text-purple-900">
                        <i class="fas fa-pencil mr-2"></i>
                        Ingresa el ancho y metraje en formato libre.
                    </p>
                </div>
                <div>
                    <label class="block text-base font-bold text-gray-800 mb-2">Ancho y Metraje:</label>
                    <textarea 
                        id="manoTexto" 
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                        placeholder="Ej: Ancho: 1.5m, Metraje: 100m o cualquier formato que prefieras..."
                        rows="4"
                    ></textarea>
                </div>
            </div>

            <div class="sticky bottom-0 bg-white/95 backdrop-blur border-t border-gray-200 pt-4 pb-1 flex-shrink-0">
                <div class="flex flex-col-reverse sm:flex-row gap-2 sm:gap-3 justify-end">
                    <button 
                        id="btnEliminarAnchoMetraje"
                        type="button"
                        data-insumos-action="ancho-metraje-open-delete-confirm"
                        class="px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg flex items-center justify-center gap-2 hidden"
                    >
                        <i class="fas fa-trash-alt"></i> Eliminar
                    </button>
                    <button 
                        type="button"
                        data-insumos-action="ancho-metraje-save"
                        class="px-4 py-2.5 text-white font-semibold rounded-lg flex items-center justify-center gap-2 shadow-sm"
                        style="background: linear-gradient(to right, #111827, #1e3a8a) !important; color: white !important;"
                    >
                        <i class="fas fa-save"></i> Guardar
                    </button>
                    <button 
                        type="button"
                        data-insumos-action="ancho-metraje-close"
                        class="px-4 py-2.5 bg-gray-500 text-white font-semibold rounded-lg flex items-center justify-center gap-2 hover:bg-gray-600"
                    >
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal de Historial de Ancho/Metraje y Estados --}}
<div id="modalHistorialAnchoMetraje" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center" style="z-index: 10001;">
    <div class="bg-white rounded-2xl shadow-2xl max-w-4xl w-full mx-3 md:mx-4 max-h-[92vh] flex flex-col overflow-hidden" style="z-index: 10002;">
        <div class="bg-gradient-to-r from-slate-900 to-slate-800 text-white px-4 py-3 md:px-5 md:py-4 flex justify-between items-center shadow-lg flex-shrink-0">
            <div>
                <h2 class="text-lg md:text-xl font-bold flex items-center gap-2 drop-shadow text-white">
                    <i class="fas fa-history"></i>
                    Historial de Ancho, Metraje y Estados
                </h2>
                <p class="text-slate-100 text-xs md:text-sm mt-1">
                    <span>Pedido: <strong id="historialAnchoMetrajePedido">-</strong></span>
                    <span class="mx-2">•</span>
                    <span>Recibo: <strong id="historialAnchoMetrajeRecibo">-</strong></span>
                </p>
            </div>
            <button type="button" data-insumos-action="historial-ancho-metraje-close" class="text-white bg-slate-700 rounded-full p-2 transition hover:bg-slate-600 flex-shrink-0 border border-slate-500">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>
        <div class="overflow-y-auto flex-1 p-4 md:p-6 bg-slate-50">
            <div id="historialAnchoMetrajeLoading" class="text-center py-10">
                <i class="fas fa-spinner fa-spin text-2xl text-slate-600"></i>
                <p class="text-sm text-gray-500 mt-2">Cargando historial...</p>
            </div>
            <div id="historialAnchoMetrajeEmpty" class="hidden text-center py-10 text-gray-500">
                No hay movimientos registrados para este contexto.
            </div>
            <div id="historialAnchoMetrajeList" class="space-y-3 hidden"></div>
            <div id="historialAnchoMetrajePagination" class="hidden mt-4 flex flex-col sm:flex-row items-center justify-between gap-3 border-t border-slate-200 pt-4">
                <div id="historialAnchoMetrajePageInfo" class="text-xs text-slate-500 font-medium"></div>
                <div class="flex items-center gap-2">
                    <button type="button" id="historialAnchoMetrajePrev" class="px-3 py-2 rounded-lg border border-slate-300 bg-white text-slate-700 text-sm font-semibold hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed">
                        Anterior
                    </button>
                    <button type="button" id="historialAnchoMetrajeNext" class="px-3 py-2 rounded-lg border border-slate-300 bg-white text-slate-700 text-sm font-semibold hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed">
                        Siguiente
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL DE CONFIRMACIÓN PARA ELIMINAR ANCHO/METRAJE -->
<div id="modalConfirmacionEliminar" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center" style="z-index: 10002;">
    <div class="bg-white rounded-lg shadow-2xl w-96">
        <div class="bg-gradient-to-r from-red-600 to-red-700 text-white p-4 rounded-t-lg flex items-center gap-3">
            <i class="fas fa-exclamation-triangle text-2xl"></i>
            <h2 class="text-lg font-bold">Eliminar Datos</h2>
        </div>
        <div class="p-6">
            <p class="text-gray-700 text-base font-semibold mb-2">¿Estás seguro?</p>
            <p class="text-gray-600 text-sm mb-6">Se eliminará todo el registro de ancho/metraje para esta prenda. Esta acción no se puede deshacer.</p>
            <div class="flex gap-3 justify-end">
                <button 
                    type="button"
                    data-insumos-action="confirm-eliminar-close"
                    class="px-4 py-2 bg-gray-400 text-white font-semibold rounded-lg hover:bg-gray-500 flex items-center gap-2"
                >
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button 
                    type="button"
                    data-insumos-action="confirm-eliminar-submit"
                    class="px-4 py-2 bg-red-600 text-white font-semibold rounded-lg hover:bg-red-700 flex items-center gap-2"
                >
                    <i class="fas fa-trash-alt"></i> Eliminar
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Modal de Confirmación para Enviar a Producción --}}
<div id="modalConfirmarProduccion" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center" style="display: none; z-index: 10001; top: 0; left: 0; right: 0; bottom: 0;">
    <div class="bg-white rounded-lg shadow-2xl" style="width: 380px; z-index: 10002;">
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white p-4 rounded-t-lg flex items-center gap-3">
            <i class="fas fa-industry text-2xl"></i>
            <h2 class="text-base font-bold">Aprobar Recibo</h2>
        </div>

        <div class="p-5">
            <p class="text-gray-700 mb-2 text-sm font-semibold">Recibo N°:</p>
            <p class="text-2xl font-bold text-blue-600 mb-4" id="numeroPedidoConfirm"></p>
            
            <p class="text-gray-600 text-sm leading-relaxed mb-6">
                ¿Aprobar este recibo para enviar a producción? Solo se aprobará este recibo individual.
            </p>
            
            <div class="flex gap-3 justify-end">
                <button 
                    type="button"
                    data-insumos-action="produccion-confirm-close"
                    class="flex-1 px-4 py-2 bg-gray-200 text-gray-700 font-semibold rounded hover:bg-gray-300 transition text-sm"
                >
                    Cancelar
                </button>
                <button 
                    id="btnAprobarProduccion"
                    type="button"
                    data-insumos-action="produccion-confirm-submit"
                    class="flex-1 px-4 py-2 bg-blue-600 text-white font-semibold rounded hover:bg-blue-700 transition text-sm"
                >
                    Aprobar
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Modal: Pasar a Revisar --}}
<div id="modalPasarRevisar" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.5); z-index: 1000000; align-items: center; justify-content: center; pointer-events: auto;">
    <div style="background: white; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); max-width: 500px; width: 90%; padding: 0; overflow: hidden; pointer-events: auto;">
        <div style="background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%); color: white; padding: 1.5rem; display: flex; align-items: center; gap: 1rem;">
            <i class="fas fa-arrow-rotate-left" style="font-size: 1.5rem;"></i>
            <div>
                <h2 style="margin: 0; font-size: 1.25rem; font-weight: bold;">¿Pasar a Revisión?</h2>
                <p style="margin: 0.25rem 0 0 0; font-size: 0.9rem; opacity: 0.9;">Recibo a revisión por asesor</p>
            </div>
        </div>

        <div style="padding: 1.5rem;">
            <p style="margin: 0 0 1rem 0; color: #6b7280; font-size: 0.95rem;">
                Esta acción devolverá el recibo para que sea corregido.
            </p>

            <form id="formPasarRevisar" data-insumos-action="pasar-revisar-submit" style="pointer-events: auto;">
                <input type="hidden" id="reciboIdPasarRevisar" value="">
                <input type="hidden" id="pedidoIdPasarRevisar" value="">
                <div style="margin-bottom: 1rem;">
                    <label for="motivoPasarRevisar" style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: #374151;">Motivo de la revisión *</label>
                    <textarea id="motivoPasarRevisar" name="motivo_pasar_revisar" rows="4" style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 6px; font-family: inherit; font-size: 0.95rem; resize: vertical;" placeholder="Ej: Revisar especificaciones, cambios en cantidad..."></textarea>
                    <small style="display: block; margin-top: 0.5rem; color: #6b7280; text-align: right;"><span id="contadorPasarRevisar">0</span>/500 caracteres</small>
                </div>
                <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                    <button type="button" data-insumos-action="pasar-revisar-close" style="padding: 0.75rem 1.5rem; border: 1px solid #d1d5db; background: white; color: #374151; border-radius: 6px; cursor: pointer; font-weight: 500; transition: all 0.2s; pointer-events: auto;">Cancelar</button>
                    <button type="button" id="btnConfirmarPasarRevisar" style="padding: 0.75rem 1.5rem; background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%); color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 500; transition: all 0.2s; pointer-events: auto;"><i class="fas fa-arrow-rotate-left"></i> Pasar a Revisión</button>
                </div>
            </form>
        </div>
    </div>
</div>
