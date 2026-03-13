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
            <button onclick="cerrarModalInsumos()" class="text-white hover:bg-blue-600 rounded-full p-2 transition">
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
                        onclick="agregarMaterialModal()"
                        class="px-6 py-2 bg-green-600 text-white font-semibold rounded-lg hover:bg-green-700 transition flex items-center gap-2"
                    >
                        <i class="fas fa-plus"></i> Agregar Insumo
                    </button>
                </div>
                <div class="flex gap-3">
                    <button 
                        onclick="guardarInsumosModal()"
                        class="px-6 py-2 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition flex items-center gap-2"
                    >
                        <i class="fas fa-save"></i> Guardar Cambios
                    </button>
                    <button 
                        onclick="cerrarModalInsumos()"
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
            <button onclick="cerrarModalObservaciones()" class="text-white hover:bg-blue-600 rounded-full p-2 transition">
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
                    onkeydown="if(event.ctrlKey && event.key === 'Enter') guardarObservaciones()"
                ></textarea>
                <p class="text-gray-500 text-xs mt-2">💡 Presiona <strong>Ctrl + Enter</strong> para guardar rápidamente</p>
            </div>
            <div class="flex gap-3 justify-end">
                <button 
                    onclick="guardarObservaciones()" 
                    class="px-6 py-2 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition flex items-center gap-2"
                >
                    <i class="fas fa-save"></i> Guardar
                </button>
                <button 
                    onclick="cerrarModalObservaciones()" 
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
    <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] flex flex-col" style="z-index: 10002;">
        <div class="bg-gradient-to-r from-blue-900 to-blue-800 text-white p-3 flex justify-between items-center shadow-lg flex-shrink-0" style="background: linear-gradient(to right, #111827, #1e3a8a) !important;">
            <div>
                <h2 class="text-lg font-bold flex items-center gap-2 drop-shadow text-white">
                    <i class="fas fa-ruler"></i>
                    Ancho y Metraje - Recibo: <span id="anchoMetrajeRecibo" class="font-bold text-white">-</span>
                </h2>
            </div>
            <button onclick="cerrarModalAnchoMetraje()" class="text-white bg-blue-700 rounded-full p-2 transition hover:bg-blue-600 flex-shrink-0">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>
        <div class="overflow-y-auto flex-1 p-6 space-y-6">
            <!-- Indicador de carga mientras se obtienen los datos -->
            <div id="anchoMetrajeLoading" class="text-center py-6">
                <i class="fas fa-spinner fa-spin text-2xl text-blue-600"></i>
                <p class="text-sm text-gray-500 mt-2">Cargando datos...</p>
            </div>

            <!-- SELECTOR DE MODO: Normal, Por Color, Por Pieza o A Mano -->
            <div id="modoSelector" class="bg-gray-100 p-4 rounded-lg border border-gray-300 hidden">
                <p class="text-sm font-semibold text-gray-700 mb-3">¿Cómo deseas ingresar el ancho y metraje?</p>
                <div class="flex gap-4 flex-wrap">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="modoAnchoMetraje" value="normal" class="modoRadio" checked>
                        <span class="text-gray-800 font-medium">Normal</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="modoAnchoMetraje" value="color" class="modoRadio">
                        <span class="text-gray-800 font-medium">Por Color</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="modoAnchoMetraje" value="pieza" class="modoRadio">
                        <span class="text-gray-800 font-medium">Por Pieza</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="modoAnchoMetraje" value="mano" class="modoRadio">
                        <span class="text-gray-800 font-medium">A Mano</span>
                    </label>
                </div>
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
                    <label class="block text-base font-bold text-gray-800 mb-2">Ancho (m):</label>
                    <input 
                        type="number" 
                        id="anchoInput" 
                        class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-transparent"
                        placeholder="Ingresa el ancho en metros..."
                        step="0.01"
                        min="0"
                    >
                </div>
                <div>
                    <label class="block text-base font-bold text-gray-800 mb-2">Metraje (m):</label>
                    <input 
                        type="number" 
                        id="metrajeInput" 
                        class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-transparent"
                        placeholder="Ingresa el metraje en metros..."
                        step="0.01"
                        min="0"
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

            <div class="flex gap-3 justify-end border-t border-gray-200 p-6 flex-shrink-0">
                <button 
                    id="btnEliminarAnchoMetraje"
                    onclick="abrirModalConfirmacionEliminar()" 
                    class="px-6 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg flex items-center gap-2 hidden"
                >
                    <i class="fas fa-trash-alt"></i> Eliminar
                </button>
                <button 
                    onclick="guardarAnchoMetraje()" 
                    class="px-6 py-2 text-white font-semibold rounded-lg flex items-center gap-2"
                    style="background: linear-gradient(to right, #111827, #1e3a8a) !important; color: white !important;"
                >
                    <i class="fas fa-save"></i> Guardar
                </button>
                <button 
                    onclick="cerrarModalAnchoMetraje()" 
                    class="px-6 py-2 bg-gray-400 text-white font-semibold rounded-lg flex items-center gap-2"
                >
                    <i class="fas fa-times"></i> Cancelar
                </button>
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
                    onclick="cerrarModalConfirmacionEliminar()" 
                    class="px-4 py-2 bg-gray-400 text-white font-semibold rounded-lg hover:bg-gray-500 flex items-center gap-2"
                >
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button 
                    onclick="confirmarEliminarAnchoMetraje()" 
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
                    onclick="cerrarModalConfirmarProduccion()"
                    class="flex-1 px-4 py-2 bg-gray-200 text-gray-700 font-semibold rounded hover:bg-gray-300 transition text-sm"
                >
                    Cancelar
                </button>
                <button 
                    id="btnAprobarProduccion"
                    onclick="confirmarEnvioProduccion()"
                    class="flex-1 px-4 py-2 bg-blue-600 text-white font-semibold rounded hover:bg-blue-700 transition text-sm"
                >
                    Aprobar
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Modal: Pasar a Revisar --}}
<div id="modalPasarRevisar" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.5); z-index: 1000000; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); max-width: 500px; width: 90%; padding: 0; overflow: hidden;">
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

            <form id="formPasarRevisar" onsubmit="confirmarPasarRevisar(event)">
                <input type="hidden" id="reciboIdPasarRevisar" value="">
                <input type="hidden" id="pedidoIdPasarRevisar" value="">
                <div style="margin-bottom: 1rem;">
                    <label for="motivoPasarRevisar" style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: #374151;">Motivo de la revisión *</label>
                    <textarea id="motivoPasarRevisar" name="motivo_pasar_revisar" rows="4" style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 6px; font-family: inherit; font-size: 0.95rem; resize: vertical;" placeholder="Ej: Revisar especificaciones, cambios en cantidad..." required minlength="10" maxlength="500"></textarea>
                    <small style="display: block; margin-top: 0.5rem; color: #6b7280; text-align: right;"><span id="contadorPasarRevisar">0</span>/500 caracteres</small>
                </div>
                <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                    <button type="button" onclick="cerrarModalPasarRevisar()" style="padding: 0.75rem 1.5rem; border: 1px solid #d1d5db; background: white; color: #374151; border-radius: 6px; cursor: pointer; font-weight: 500; transition: all 0.2s;">Cancelar</button>
                    <button type="submit" id="btnConfirmarPasarRevisar" style="padding: 0.75rem 1.5rem; background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%); color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 500; transition: all 0.2s;"><i class="fas fa-arrow-rotate-left"></i> Pasar a Revisión</button>
                </div>
            </form>
        </div>
    </div>
</div>
