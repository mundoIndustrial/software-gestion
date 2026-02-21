<!-- Modal de Novedades (Estilo Despacho) -->
<div id="novedadesEditModal" class="hidden fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-9998 overflow-auto" style="z-index: 100001;">
    <div class="bg-white rounded-lg shadow-2xl max-w-2xl w-full mx-4 my-8">
        <div class="bg-slate-900 px-6 py-4 border-b border-slate-200 flex justify-between items-center">
            <h2 class="text-lg font-semibold text-white">💬 Novedades - Pedido <span id="modalNovedadesNumeroPedido">#</span></h2>
            <button onclick="cerrarModalNovedades()" class="text-white hover:text-slate-200 text-2xl leading-none">✕</button>
        </div>
        <div class="px-6 py-6">
            <div id="novedadesHistorial" class="mb-6" style="max-height: 350px; overflow-y: auto;">
                <div class="flex justify-center items-center py-8">
                    <span class="text-slate-500">⏳ Cargando novedades...</span>
                </div>
            </div>
            
            <div class="border-t border-slate-200 pt-6">
                <label class="block text-sm font-bold text-slate-900 mb-3">Agregar Nueva Novedad:</label>
                <textarea
                    id="novedadesNuevaContent"
                    class="w-full px-4 py-3 border border-slate-300 rounded-lg text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-700 outline-none transition resize-none"
                    placeholder="Escribe tu novedad aquí..."
                    rows="4"
                ></textarea>
                <div class="flex gap-3 mt-4">
                    <button
                        type="button"
                        onclick="guardarNovedad()"
                        class="flex-1 px-4 py-2 bg-green-500 hover:bg-green-600 text-white font-bold rounded-lg transition"
                    >
                        ✓ Guardar Novedad
                    </button>
                    <button
                        type="button"
                        onclick="cerrarModalNovedades()"
                        class="flex-1 px-4 py-2 bg-slate-400 hover:bg-slate-500 text-white font-bold rounded-lg transition"
                    >
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Confirmación (Eliminar Novedad) -->
<div id="modalConfirmarEliminar" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-9999" style="z-index: 100002;">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
        <div class="bg-red-600 px-6 py-4 border-b border-red-200">
            <h3 class="text-lg font-semibold text-white flex items-center gap-2">
                <span class="material-symbols-rounded">warning</span>
                Confirmar Eliminación
            </h3>
        </div>
        <div class="px-6 py-4">
            <p class="text-gray-700">¿Estás seguro de que deseas eliminar esta novedad? Esta acción no se puede deshacer.</p>
        </div>
        <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex gap-3 justify-end">
            <button type="button" onclick="cerrarModalConfirmarEliminar()" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 font-medium rounded-lg transition">
                Cancelar
            </button>
            <button type="button" id="btnConfirmarEliminar" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition">
                Eliminar Novedad
            </button>
        </div>
    </div>
</div>
