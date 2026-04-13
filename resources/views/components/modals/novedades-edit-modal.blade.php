<!-- Modal de Novedades -->
<div
    id="novedadesEditModal"
    class="hidden fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-9998 overflow-auto"
    style="z-index: 100001; display: none;"
>
    <div
        class="bg-white rounded-lg shadow-2xl max-w-2xl w-full mx-4 my-8"
        style="background: #ffffff; border-radius: 12px; box-shadow: 0 24px 50px rgba(0,0,0,.35); width: min(100%, 840px); margin: 1rem;"
    >
        <div
            class="bg-slate-900 px-6 py-4 border-b border-slate-200 flex justify-between items-center"
            style="background: #0f172a; color: #fff; padding: 1rem 1.25rem; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;"
        >
            <h2 class="text-lg font-semibold text-white" style="margin: 0; font-size: 1.05rem; font-weight: 700;">
                Novedades - Pedido <span id="modalNovedadesNumeroPedido">#</span>
            </h2>
            <button
                onclick="cerrarModalNovedades()"
                class="text-white hover:text-slate-200 text-2xl leading-none"
                style="background: transparent; color: #fff; border: 0; font-size: 1.35rem; line-height: 1; cursor: pointer;"
            >X</button>
        </div>
        <div class="px-6 py-6" style="padding: 1.25rem;">
            <div id="novedadesHistorial" class="mb-6" style="max-height: 350px; overflow-y: auto; margin-bottom: 1rem;">
                <div style="text-align: center; color: #64748b; padding: 2rem 0;">Cargando novedades...</div>
            </div>

            <div class="border-t border-slate-200 pt-6" style="border-top: 1px solid #e2e8f0; padding-top: 1rem;">
                <label class="block text-sm font-bold text-slate-900 mb-3" style="display: block; font-size: .875rem; font-weight: 700; color: #0f172a; margin-bottom: .5rem;">
                    Agregar Nueva Novedad:
                </label>
                <textarea
                    id="novedadesNuevaContent"
                    class="w-full px-4 py-3 border border-slate-300 rounded-lg text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-700 outline-none transition resize-none"
                    style="width: 100%; border: 1px solid #cbd5e1; border-radius: 10px; padding: .75rem .9rem; font-size: .9rem; outline: none; resize: none;"
                    placeholder="Escribe tu novedad aqui..."
                    rows="4"
                ></textarea>
                <div class="flex gap-3 mt-4" style="display: flex; gap: .6rem; margin-top: .75rem;">
                    <button
                        type="button"
                        onclick="guardarNovedad()"
                        class="flex-1 px-4 py-2 bg-green-500 hover:bg-green-600 text-white font-bold rounded-lg transition"
                        style="flex: 1; border: 0; border-radius: 10px; background: #22c55e; color: #fff; font-weight: 700; padding: .65rem .85rem; cursor: pointer;"
                    >
                        Guardar Novedad
                    </button>
                    <button
                        type="button"
                        onclick="cerrarModalNovedades()"
                        class="flex-1 px-4 py-2 bg-slate-400 hover:bg-slate-500 text-white font-bold rounded-lg transition"
                        style="flex: 1; border: 0; border-radius: 10px; background: #94a3b8; color: #fff; font-weight: 700; padding: .65rem .85rem; cursor: pointer;"
                    >
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Confirmacion (Eliminar Novedad) -->
<div id="modalConfirmarEliminar" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-9999" style="z-index: 100002; display: none;">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4" style="background: #fff; border-radius: 12px; box-shadow: 0 20px 40px rgba(0,0,0,.25); width: min(100%, 520px); margin: 1rem;">
        <div class="bg-red-600 px-6 py-4 border-b border-red-200" style="background: #dc2626; padding: 1rem 1.25rem; border-bottom: 1px solid #fecaca;">
            <h3 class="text-lg font-semibold text-white flex items-center gap-2" style="margin: 0; color: #fff; font-size: 1rem; font-weight: 700;">
                Confirmar Eliminacion
            </h3>
        </div>
        <div class="px-6 py-4" style="padding: 1rem 1.25rem;">
            <p class="text-gray-700" style="margin: 0; color: #374151;">Estas seguro de que deseas eliminar esta novedad? Esta accion no se puede deshacer.</p>
        </div>
        <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex gap-3 justify-end" style="background: #f9fafb; padding: 1rem 1.25rem; border-top: 1px solid #e5e7eb; display: flex; gap: .6rem; justify-content: flex-end;">
            <button type="button" onclick="cerrarModalConfirmarEliminar()" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 font-medium rounded-lg transition" style="border: 0; border-radius: 10px; background: #d1d5db; color: #1f2937; font-weight: 600; padding: .6rem .9rem; cursor: pointer;">
                Cancelar
            </button>
            <button type="button" id="btnConfirmarEliminar" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition" style="border: 0; border-radius: 10px; background: #dc2626; color: #fff; font-weight: 600; padding: .6rem .9rem; cursor: pointer;">
                Eliminar Novedad
            </button>
        </div>
    </div>
</div>
