<!-- Modal Selección de Tipo (Prenda o EPP) -->
<div id="modalSeleccionTipo" class="fixed inset-0 bg-black/50 flex items-center justify-center" style="display: none; z-index: 999999;">
    <div class="bg-white rounded-lg w-full max-w-md shadow-2xl">
        <!-- Header -->
        <div class="bg-blue-600 px-6 py-4 flex justify-between items-center">
            <h2 class="text-white text-lg font-bold">Seleccionar Tipo de Producto</h2>
            <button onclick="cerrarModalSeleccion()" class="text-white hover:bg-blue-700 p-1 rounded transition">
                <i class="material-symbols-rounded">close</i>
            </button>
        </div>

        <!-- Body -->
        <div class="p-6 space-y-4">
            <p class="text-gray-700 text-center mb-6">¿Qué tipo de producto deseas agregar?</p>
            
            <button type="button" onclick="seleccionarTipoProducto('prenda')"
                class="w-full px-6 py-4 border-2 border-blue-400 rounded-lg hover:bg-blue-50 transition flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <i class="material-symbols-rounded text-2xl text-blue-600">checkroom</i>
                    <div class="text-left">
                        <div class="font-semibold text-gray-900">Prenda</div>
                        <div class="text-xs text-gray-500">Ropa y accesorios personalizados</div>
                    </div>
                </div>
                <i class="material-symbols-rounded text-blue-600">arrow_forward</i>
            </button>

            <button type="button" onclick="seleccionarTipoProducto('epp')"
                class="w-full px-6 py-4 border-2 border-green-400 rounded-lg hover:bg-green-50 transition flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <i class="material-symbols-rounded text-2xl text-green-600">engineering</i>
                    <div class="text-left">
                        <div class="font-semibold text-gray-900">EPP</div>
                        <div class="text-xs text-gray-500">Equipo de proteccion personal</div>
                    </div>
                </div>
                <i class="material-symbols-rounded text-green-600">arrow_forward</i>
            </button>
        </div>
    </div>
</div>

<!-- Modal Agregar Prenda al Pedido -->
<div id="modalAgregarPrenda" class="fixed inset-0 bg-black/50 flex items-center justify-center" style="display: none; z-index: 999999;">
    <div class="bg-white rounded-lg w-full max-w-2xl shadow-2xl overflow-hidden" style="z-index: 1000000; max-height: 90vh; display: flex; flex-direction: column;">
        
        <!-- Header Azul -->
        <div class="bg-blue-600 px-6 py-4 flex justify-between items-center flex-shrink-0">
            <h2 class="text-white text-lg font-bold">Agregar Prenda al Pedido</h2>
            <button onclick="cerrarModalAgregarPrenda()" class="text-white hover:bg-blue-700 p-1 rounded transition">
                <i class="material-symbols-rounded">close</i>
            </button>
        </div>

        <!-- Body con scroll -->
        <div class="p-6 space-y-4 overflow-y-auto flex-1" style="max-height: calc(90vh - 140px);">
            
            <!-- Descripcion -->
            <div>
                <label for="descripcionPrenda" class="text-sm font-medium text-gray-700 block mb-2">Descripcion</label>
                <textarea id="descripcionPrenda" placeholder="Detalles de la prenda..." rows="3"
                    class="w-full px-3 py-2 border-2 border-gray-300 rounded-lg focus:outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-200 text-sm resize-none"></textarea>
            </div>

            <!-- Cantidad -->
            <div>
                <label for="cantidadPrenda" class="text-sm font-medium text-gray-700 block mb-2">Cantidad</label>
                <input type="number" id="cantidadPrenda" value="1" min="1" oninput="actualizarTotalPrenda()"
                    class="w-full px-3 py-2 border-2 border-gray-300 rounded-lg focus:outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-200 text-sm">
            </div>

            <!-- Valor Unitario y Total -->
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="valorUnitarioPrenda" class="text-sm font-medium text-gray-700 block mb-2">Valor Unitario (Opcional)</label>
                    <input type="number" id="valorUnitarioPrenda" min="0" step="0.01" placeholder="0" oninput="actualizarTotalPrenda()"
                        class="w-full px-3 py-2 border-2 border-gray-300 rounded-lg focus:outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-200 text-sm">
                </div>
                <div>
                    <label for="totalPrenda" class="text-sm font-medium text-gray-700 block mb-2">Total</label>
                    <input type="text" id="totalPrenda" value="0" readonly
                        class="w-full px-3 py-2 border-2 border-gray-300 rounded-lg bg-gray-100 text-gray-700 text-sm focus:outline-none cursor-default">
                </div>
            </div>

            <!-- Observaciones -->
            <div>
                <label for="observacionesPrenda" class="text-sm font-medium text-gray-700 block mb-2">Observaciones (Opcional)</label>
                <textarea id="observacionesPrenda" placeholder="Detalles adicionales..." rows="2"
                    class="w-full px-3 py-2 border-2 border-gray-300 rounded-lg focus:outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-200 text-sm resize-none"></textarea>
            </div>

            <!-- Seccion de Fotos (opcional) -->
            <div>
                <div class="flex items-center justify-between mb-3">
                    <label class="text-sm font-medium text-gray-700">Fotos de la Prenda (Opcional)</label>
                    <button type="button" onclick="agregarFotoPrenda()" 
                        class="px-3 py-1 bg-blue-600 text-white text-sm rounded-lg font-medium flex items-center gap-1 hover:bg-blue-700 transition">
                        <i class="material-symbols-rounded" style="font-size: 16px;">add_photo_alternate</i>
                        Agregar Foto
                    </button>
                </div>
                
                <!-- Contenedor de imagenes -->
                <div id="contenedorFotosPrenda" class="grid grid-cols-3 gap-3 border-2 border-dashed border-gray-300 rounded-lg p-4 min-h-[120px] transition-all"
                    tabindex="0" style="outline: none;" data-zona="prenda" onmouseover="this.focus()" onmouseleave="this.blur()"
                    ondrop="handleDropPrenda(event)" ondragover="handleDragOverPrenda(event)" ondragleave="handleDragLeavePrenda(event)">
                    
                    <!-- Mensaje inicial -->
                    <div id="mensajeDragDropPrenda" class="col-span-3 flex flex-col items-center justify-center text-gray-400">
                        <i class="material-symbols-rounded text-4xl mb-2">cloud_upload</i>
                        <p class="text-sm">Arrastra imagenes aqui o haz clic en "Agregar Foto"</p>
                        <p class="text-xs">Tambien puedes pegar con Ctrl+V</p>
                        <p class="text-xs">Formatos: JPG, PNG, GIF, WebP, JFIF</p>
                    </div>
                </div>
                
                <!-- Input oculto para subir archivos -->
                <input type="file" id="inputFotosPrenda" multiple accept="image/*" style="display: none;" onchange="manejarSubidaFotosPrenda(this)">
            </div>
        </div>

        <!-- Footer fijo -->
        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end gap-3 flex-shrink-0">
            <button onclick="cerrarModalAgregarPrenda()"
                class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 font-medium hover:bg-gray-100 transition text-sm">
                Cancelar
            </button>
            <button id="btnFinalizarAgregarPrenda" onclick="finalizarAgregarPrenda()"
                class="px-4 py-2 bg-blue-600 text-white rounded-lg font-medium flex items-center gap-2 hover:bg-blue-700 transition text-sm">
                <i class="material-symbols-rounded" style="font-size: 20px;">check_circle</i>
                Finalizar
            </button>
        </div>
    </div>
</div>

<!-- Modal Agregar EPP al Pedido - REDISEÑO CON TABLA Multiple -->
<div id="modalAgregarEPP" class="fixed inset-0 bg-black/50 flex items-center justify-center" style="display: none; z-index: 9999999;">
    <div id="modalAgregarEPPContent" class="bg-white rounded-lg w-full max-w-3xl shadow-2xl overflow-hidden" style="z-index: 10000000; max-height: 95vh; height: 95vh; display: flex; flex-direction: column; margin: 0 20px;">
        
        <!-- Header Azul -->
        <div class="bg-blue-600 px-6 py-4 flex justify-between items-center flex-shrink-0">
            <h2 class="text-white text-lg font-bold">Agregar EPP al Pedido - Seleccion Multiple</h2>
            <button onclick="cerrarModalAgregarEPP()" class="text-white hover:bg-blue-700 p-1 rounded transition">
                <i class="material-symbols-rounded">close</i>
            </button>
        </div>

        <!-- Body con scroll -->
        <div class="p-6 space-y-4 overflow-y-auto flex-1" style="max-height: calc(90vh - 140px);">
            
            <!-- Buscador con Dropdown Multi-Select -->
            <div id="buscadorEPPSection" class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="relative">
                    <label for="inputBuscadorEPPTabla" class="text-sm font-medium text-gray-700 block mb-2">
                        <i class="material-symbols-rounded inline text-base mr-1">search</i>
                        Selecciona EPP (busqueda y multi-select)
                    </label>
                    <input
                        type="text"
                        id="inputBuscadorEPPTabla"
                        onkeyup="filtrarDropdownEPP(this.value)"
                        placeholder="Escribe para buscar y selecciona..."
                        class="w-full px-3 py-2 border-2 border-gray-300 rounded-lg focus:outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-200 text-sm"
                    >
                    <!-- Dropdown de opciones -->
                    <div id="dropdownEPP" class="absolute top-full left-0 right-0 mt-1 bg-white border-2 border-gray-300 rounded-lg shadow-lg hidden z-50" style="max-height: 400px; overflow-y: auto;">
                        <div class="p-2 space-y-1" id="opcionesDropdownEPP">
                            <!-- Se llena dinamicamente -->
                        </div>
                        <div id="mensajeSinResultados" class="p-4 text-center text-gray-500 hidden">
                            <p>Sin resultados</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TABLA DE EPP SELECCIONADOS -->
            <div id="listaEPPAgregados" class="border border-gray-200 rounded-lg overflow-hidden" style="display: none;">
                <h3 class="text-sm font-semibold text-gray-900 px-4 py-3 bg-gray-50 border-b flex items-center gap-2">
                    <i class="material-symbols-rounded">list_alt</i>
                    EPP Seleccionados (<span id="contadorEPP">0</span>)
                </h3>
                <div class="overflow-x-auto" style="max-height: 300px; overflow-y: auto;">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 border-b border-gray-200 sticky top-0" id="headerTablaEPPAgregados">
                            <tr>
                                <th class="px-4 py-2 text-left text-gray-700 font-semibold">EPP</th>
                                <th class="px-4 py-2 text-center text-gray-700 font-semibold w-20">Cant.</th>
                                <th class="px-4 py-2 text-left text-gray-700 font-semibold">Observaciones</th>
                                <th class="px-4 py-2 text-center text-gray-700 font-semibold w-24 columna-cotizacion" style="display:none;">V. Unitario</th>
                                <th class="px-4 py-2 text-center text-gray-700 font-semibold w-24 columna-cotizacion" style="display:none;">Total</th>
                                <th class="px-4 py-2 text-center text-gray-700 font-semibold w-16">Fotos</th>
                                <th class="px-4 py-2 text-center text-gray-700 font-semibold w-16">Accion</th>
                            </tr>
                        </thead>
                        <tbody id="cuerpoTablaEPP">
                            <!-- Se llena dinamicamente -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tarjeta Producto (inicialmente oculta) - PARA MODO EDICION -->
            <div id="productoCardEPP" class="bg-blue-50 border border-blue-200 rounded-lg p-4 animate-in fade-in" style="display: none;">
                <div>
                    <label for="nombreProductoEPP" class="text-sm font-medium text-gray-700 block mb-2">Nombre del EPP</label>
                    <input
                        type="text"
                        id="nombreProductoEPP"
                        placeholder="Nombre del EPP"
                        readonly
                        class="w-full px-3 py-2 border-2 border-gray-300 rounded-lg bg-gray-100 text-gray-700 text-sm focus:outline-none cursor-default"
                    >
                </div>
            </div>

            <!-- Cantidad y Talla - PARA MODO EDICION -->
            <div id="formularioAgregarEPP" style="display: none;">
                <div>
                    <label for="cantidadEPP" class="text-sm font-medium text-gray-700 block mb-2">Cantidad</label>
                    <input
                        type="number"
                        id="cantidadEPP"
                        value="1"
                        placeholder="1"
                        min="1"
                        oninput="actualizarTotalEPP()"
                        disabled
                        class="w-full px-3 py-2 border-2 border-gray-300 rounded-lg bg-gray-100 text-gray-500 text-sm disabled:bg-gray-100 disabled:text-gray-400 focus:outline-none"
                    >
                </div>
            </div>

            <!-- Valor Unitario (opcional) y Total - PARA MODO EDICION -->
            <div id="valorUnitarioTotalContainer" style="display: none;">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="valorUnitarioEPP" class="text-sm font-medium text-gray-700 block mb-2">Valor Unitario (Opcional)</label>
                        <input
                            type="number"
                            id="valorUnitarioEPP"
                            min="0"
                            step="0.01"
                            placeholder="0"
                            oninput="actualizarTotalEPP()"
                            class="w-full px-3 py-2 border-2 border-gray-300 rounded-lg bg-gray-100 text-gray-500 text-sm disabled:bg-gray-100 disabled:text-gray-400 focus:outline-none"
                        >
                    </div>
                    <div>
                        <label for="totalEPP" class="text-sm font-medium text-gray-700 block mb-2">Total</label>
                        <input
                            type="text"
                            id="totalEPP"
                            value="0"
                            readonly
                            class="w-full px-3 py-2 border-2 border-gray-300 rounded-lg bg-gray-100 text-gray-500 text-sm focus:outline-none"
                        >
                    </div>
                </div>
            </div>

            <!-- Observaciones - PARA MODO EDICION -->
            <div id="observacionesContainer" style="display: none;">
                <label for="observacionesEPP" class="text-sm font-medium text-gray-700 block mb-2">Observaciones (Opcional)</label>
                <textarea
                    id="observacionesEPP"
                    placeholder="Detalles adicionales..."
                    disabled
                    rows="2"
                    class="w-full px-3 py-2 border-2 border-gray-300 rounded-lg bg-gray-100 text-gray-500 text-sm disabled:bg-gray-100 disabled:text-gray-400 focus:outline-none resize-none"
                ></textarea>
            </div>


            <!-- Seccion de Fotos - PARA MODO EDICION -->
            <div id="seccionFotosEPP" style="display: none;">
                <div class="flex items-center justify-between mb-3">
                    <label class="text-sm font-semibold text-gray-900">Fotos del EPP</label>
                    <span class="text-xs text-gray-500">(<span id="contadorFotosEPP">0</span> foto/s)</span>
                </div>

                <!-- Zona de carga -->
                <div class="space-y-3">
                    <!-- Zona Drag & Drop -->
                    <div id="contenedorFotosEPP"
                        class="grid grid-cols-3 gap-3 border-2 border-dashed border-gray-300 rounded-lg p-4 min-h-[120px] transition-all"
                        ondrop="handleDropEPP(event)"
                        ondragover="handleDragOverEPP(event)"
                        ondragleave="handleDragLeaveEPP(event)">
                        <div id="mensajeDragDrop" class="col-span-3 flex flex-col items-center justify-center text-gray-400">
                            <i class="material-symbols-rounded text-3xl">cloud_upload</i>
                            <p class="text-sm mt-1">Arrastra imagenes aqui</p>
                            <p class="text-xs">Tambien puedes usar Ctrl+V o hacer clic en "Agregar Foto"</p>
                        </div>
                    </div>

                    <!-- Boton para agregar fotos -->
                    <button
                        type="button"
                        onclick="document.getElementById('inputFotosEPP').click()"
                        class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition text-sm flex items-center justify-center gap-2">
                        <i class="material-symbols-rounded" style="font-size: 18px;">add_photo_alternate</i>
                        Agregar Fotos
                    </button>
                </div>

                <!-- Input file oculto -->
                <input
                    type="file"
                    id="inputFotosEPP"
                    multiple
                    accept="image/*"
                    style="display: none !important;"
                    onchange="manejarSubidaFotosEPP(this)"
                >
            </div>
        </div>

        <!-- Footer fijo -->
        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-between items-center flex-shrink-0">
            <div class="text-sm text-gray-600">
                <span id="totalSeleccionados">0</span> EPP seleccionados
            </div>
            <div class="flex justify-end gap-3">
                <button onclick="cerrarModalAgregarEPP()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 font-medium hover:bg-gray-100 transition text-sm">
                    Cancelar
                </button>
                <!-- Boton Finalizar (visible en modo normal) -->
                <button
                    id="btnFinalizarAgregarEPP"
                    onclick="finalizarAgregarEPP()"
                    disabled
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg font-medium flex items-center gap-2 hover:bg-blue-700 disabled:bg-gray-300 disabled:text-gray-500 disabled:cursor-not-allowed transition text-sm"
                >
                    <i class="material-symbols-rounded" style="font-size: 20px;">check_circle</i>
                    Finalizar
                </button>
                <!-- Boton Guardar Cambios (visible en modo edicion) -->
                <button
                    id="btnGuardarCambiosEPP"
                    onclick="guardarEdicionEPP()"
                    disabled
                    class="px-4 py-2 bg-green-600 text-white rounded-lg font-medium flex items-center gap-2 hover:bg-green-700 disabled:bg-gray-300 disabled:text-gray-500 disabled:cursor-not-allowed transition text-sm"
                    style="display: none;"
                >
                    <i class="material-symbols-rounded" style="font-size: 20px;">save</i>
                    Guardar Cambios
                </button>
            </div>
        </div>
    </div>
</div>


