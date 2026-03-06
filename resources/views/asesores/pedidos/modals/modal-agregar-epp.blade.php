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
                        <div class="text-xs text-gray-500">Equipo de protección personal</div>
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
            
            <!-- Descripción -->
            <div>
                <label for="descripcionPrenda" class="text-sm font-medium text-gray-700 block mb-2">Descripción</label>
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

            <!-- Sección de Fotos (opcional) -->
            <div>
                <div class="flex items-center justify-between mb-3">
                    <label class="text-sm font-medium text-gray-700">Fotos de la Prenda (Opcional)</label>
                    <button type="button" onclick="agregarFotoPrenda()" 
                        class="px-3 py-1 bg-blue-600 text-white text-sm rounded-lg font-medium flex items-center gap-1 hover:bg-blue-700 transition">
                        <i class="material-symbols-rounded" style="font-size: 16px;">add_photo_alternate</i>
                        Agregar Foto
                    </button>
                </div>
                
                <!-- Contenedor de imágenes -->
                <div id="contenedorFotosPrenda" class="grid grid-cols-3 gap-3 border-2 border-dashed border-gray-300 rounded-lg p-4 min-h-[120px] transition-all" 
                    tabindex="0" style="outline: none;" data-zona="prenda" onmouseover="this.focus()" onmouseleave="this.blur()" 
                    ondrop="handleDropPrenda(event)" ondragover="handleDragOverPrenda(event)" ondragleave="handleDragLeavePrenda(event)">
                    
                    <!-- Mensaje inicial -->
                    <div id="mensajeDragDropPrenda" class="col-span-3 flex flex-col items-center justify-center text-gray-400">
                        <i class="material-symbols-rounded text-4xl mb-2">cloud_upload</i>
                        <p class="text-sm">Arrastra imágenes aquí o haz clic en "Agregar Foto"</p>
                        <p class="text-xs">También puedes pegar con Ctrl+V</p>
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

<!-- Modal Agregar EPP al Pedido - REDISEÑO CON TABLA MÚLTIPLE -->
<div id="modalAgregarEPP" class="fixed inset-0 bg-black/50 flex items-center justify-center" style="display: none; z-index: 9999999;">
    <div id="modalAgregarEPPContent" class="bg-white rounded-lg w-full max-w-3xl shadow-2xl overflow-hidden" style="z-index: 10000000; max-height: 95vh; height: 95vh; display: flex; flex-direction: column; margin: 0 20px;">
        
        <!-- Header Azul -->
        <div class="bg-blue-600 px-6 py-4 flex justify-between items-center flex-shrink-0">
            <h2 class="text-white text-lg font-bold">Agregar EPP al Pedido - Selección Múltiple</h2>
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
                        Selecciona EPP (búsqueda y multi-select)
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
                            <!-- Se llena dinámicamente -->
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
                                <th class="px-4 py-2 text-center text-gray-700 font-semibold w-16">Acción</th>
                            </tr>
                        </thead>
                        <tbody id="cuerpoTablaEPP">
                            <!-- Se llena dinámicamente -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tarjeta Producto (inicialmente oculta) - PARA MODO EDICIÓN -->
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

            <!-- Cantidad y Talla - PARA MODO EDICIÓN -->
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

            <!-- Valor Unitario (opcional) y Total - PARA MODO EDICIÓN -->
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

            <!-- Observaciones - PARA MODO EDICIÓN -->
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


            <!-- Sección de Fotos - PARA MODO EDICIÓN -->
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
                            <p class="text-sm mt-1">Arrastra imágenes aquí</p>
                            <p class="text-xs">También puedes usar Ctrl+V o hacer clic en "Agregar Foto"</p>
                        </div>
                    </div>

                    <!-- Botón para agregar fotos -->
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
                <!-- Botón Finalizar (visible en modo normal) -->
                <button 
                    id="btnFinalizarAgregarEPP"
                    onclick="finalizarAgregarEPP()"
                    disabled
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg font-medium flex items-center gap-2 hover:bg-blue-700 disabled:bg-gray-300 disabled:text-gray-500 disabled:cursor-not-allowed transition text-sm"
                >
                    <i class="material-symbols-rounded" style="font-size: 20px;">check_circle</i>
                    Finalizar
                </button>
                <!-- Botón Guardar Cambios (visible en modo edición) -->
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


<script>
// Variables globales
let productoSeleccionadoEPP = null;
let eppAgregadosList = []; // Lista de EPP agregados en el modal
window.eppAgregadosList = eppAgregadosList; // Exponer para DragDropManager
let eppDisponiblesList = []; // Lista de EPP disponibles para mostrar en tabla
let eppYaAgregadosEnFormulario = []; // IDs de EPPs ya en el formulario

/**
 * Obtener los EPP IDs ya agregados en el formulario
 */
function obtenerEPPsYaAgregadosEnFormulario() {
    eppYaAgregadosEnFormulario = [];
    
    // 1) Buscar la tabla en el formulario de cotizacion/pedido (modo creación)
    const tablaItems = document.getElementById('tabla-items-pedido');
    console.log('[obtenerEPPsYaAgregadosEnFormulario] Buscando tabla:', tablaItems);
    
    if (tablaItems) {
        // Buscar todas las filas con clase item-epp (estas tienen data-item-id)
        const filas = tablaItems.querySelectorAll('tr.item-epp');
        console.log('[obtenerEPPsYaAgregadosEnFormulario] Filas encontradas:', filas.length);
        
        filas.forEach((fila, idx) => {
            // Intentar primero con data-item-id (el ID del EPP)
            let eppId = fila.getAttribute('data-item-id');
            console.log(`[obtenerEPPsYaAgregadosEnFormulario] Fila ${idx}: data-item-id=${eppId}`);
            
            if (eppId) {
                eppYaAgregadosEnFormulario.push(parseInt(eppId));
            }
        });
    } else {
        console.warn('[obtenerEPPsYaAgregadosEnFormulario] tabla-items-pedido NO ENCONTRADA');
    }
    
    // 2) MODO EDICIÓN: También incluir EPPs del pedido existente (window.datosEdicionPedido)
    if (window.__EPP_AGREGAR_PEDIDO_EXISTENTE__ && window.datosEdicionPedido && window.datosEdicionPedido.epps) {
        const eppsExistentes = window.datosEdicionPedido.epps;
        console.log('[obtenerEPPsYaAgregadosEnFormulario] MODO EDICIÓN - EPPs existentes en pedido:', eppsExistentes.length);
        
        eppsExistentes.forEach(epp => {
            const eppId = parseInt(epp.epp_id || epp.id);
            if (eppId && !eppYaAgregadosEnFormulario.includes(eppId)) {
                eppYaAgregadosEnFormulario.push(eppId);
                console.log(`[obtenerEPPsYaAgregadosEnFormulario] EPP existente en pedido: ${eppId} (${epp.nombre_completo || epp.nombre || ''})`);
            }
        });
    }
    
    console.log('[obtenerEPPsYaAgregadosEnFormulario] EPPs totales encontrados:', eppYaAgregadosEnFormulario);
    return eppYaAgregadosEnFormulario;
}

/**
 * Cargar EPP disponibles en la tabla de selección
 */
async function cargarEPPDisponibles() {
    try {
        console.log('[cargarEPPDisponibles] Iniciando carga de EPP...');
        
        // Llamar al endpoint API para obtener los primeros EPPs CON paginación
        const response = await fetch('/api/epp/gestion');
        const result = await response.json();
        
        if (result.success && result.data) {
            eppDisponiblesList = result.data || [];
            console.log('[cargarEPPDisponibles] EPPs cargados:', eppDisponiblesList.length, 'Total en BD:', result.total);
        } else {
            console.warn('[cargarEPPDisponibles] Error en la respuesta del API');
            eppDisponiblesList = [];
        }
        
        // Renderizar dropdown inicial
        renderizarDropdownEPP(eppDisponiblesList);
        
    } catch (error) {
        console.error('[cargarEPPDisponibles] Error:', error);
        eppDisponiblesList = [];
    }
}

/**
 * Mostrar dropdown de EPP disponibles
 */
function mostrarDropdownEPP() {
    const dropdown = document.getElementById('dropdownEPP');
    dropdown.classList.remove('hidden');
    console.log('[mostrarDropdownEPP] Dropdown mostrado');
}

/**
 * Renderizar opciones en el dropdown
 */
function renderizarDropdownEPP(epps) {
    const container = document.getElementById('opcionesDropdownEPP');
    const mensajeSinResultados = document.getElementById('mensajeSinResultados');
    
    if (!container) return;
    
    console.log('[renderizarDropdownEPP] Renderizando con:', {
        eppsCounts: epps ? epps.length : 0,
        eppAgregadosModal: eppAgregadosList.length,
        eppEnFormulario: eppYaAgregadosEnFormulario.length
    });
    
    container.innerHTML = '';
    
    // Filtrar EPPs que ya están agregados (EN EL MODAL O EN EL FORMULARIO)
    const eppsFiltrados = (epps || []).filter(epp => {
        const enModal = eppAgregadosList.find(item => item.id == epp.id);
        const enFormulario = eppYaAgregadosEnFormulario.includes(epp.id);
        
        if (enModal) {
            console.log(`  - EPP ${epp.id} (${epp.nombre_completo}) EXCLUIDO: en modal`);
        }
        if (enFormulario) {
            console.log(`  - EPP ${epp.id} (${epp.nombre_completo}) EXCLUIDO: en formulario`);
        }
        
        return !enModal && !enFormulario;
    });
    
    console.log('[renderizarDropdownEPP] EPPs después de filtrado:', eppsFiltrados.length);
    
    if (!eppsFiltrados || eppsFiltrados.length === 0) {
        mensajeSinResultados.classList.remove('hidden');
        return;
    }
    
    mensajeSinResultados.classList.add('hidden');
    
    eppsFiltrados.forEach(epp => {
        const label = document.createElement('label');
        label.className = 'flex items-center p-2 hover:bg-blue-50 rounded cursor-pointer gap-2';
        
        label.innerHTML = `
            <input 
                type="checkbox" 
                class="checkbox-epp-dropdown w-4 h-4" 
                data-epp-id="${epp.id}" 
                data-epp-nombre="${epp.nombre_completo || epp.nombre}"
                onchange="agregarEPPDesdeDropdown(this)"
            >
            <div class="flex-1">
                <p class="font-medium text-gray-900 text-sm">${epp.nombre_completo || epp.nombre}</p>
                ${epp.marca ? `<p class="text-xs text-gray-500">${epp.marca}</p>` : ''}
            </div>
        `;
        
        container.appendChild(label);
    });
    
    console.log('[renderizarDropdownEPP] Dropdown renderizado con', epps.length, 'opciones');
}

/**
 * Filtrar dropdown según búsqueda (consulta al servidor sin límite)
 */
async function filtrarDropdownEPP(valor) {
    const dropdown = document.getElementById('dropdownEPP');
    const busqueda = valor.toLowerCase().trim();
    
    // Si no hay búsqueda, ocultar dropdown
    if (!busqueda) {
        dropdown.classList.add('hidden');
        console.log('[filtrarDropdownEPP] Dropdown ocultado (sin búsqueda)');
        return;
    }
    
    try {
        console.log('[filtrarDropdownEPP] Buscando:', busqueda, 'con EPPs en formulario:', eppYaAgregadosEnFormulario);
        
        // Buscar en el servidor SIN límite de paginación
        const response = await fetch(`/api/epp/gestion?q=${encodeURIComponent(busqueda)}&per_page=10000`);
        const result = await response.json();
        
        let filtrados = [];
        if (result.success && result.data) {
            console.log('[filtrarDropdownEPP] Resultados del servidor:', result.data.length);
            
            // Filtrar EPPs que ya están agregados (EN EL MODAL O EN EL FORMULARIO)
            filtrados = (result.data || []).filter(epp => {
                const enModal = eppAgregadosList.find(item => item.id == epp.id);
                const enFormulario = eppYaAgregadosEnFormulario.includes(epp.id);
                
                if (enModal) {
                    console.log(`  - EPP ${epp.id} EXCLUIDO (búsqueda): en modal`);
                }
                if (enFormulario) {
                    console.log(`  - EPP ${epp.id} EXCLUIDO (búsqueda): en formulario (${epp.nombre_completo})`);
                }
                
                return !enModal && !enFormulario;
            });
        }
        
        console.log('[filtrarDropdownEPP] Búsqueda:', busqueda, '- Resultados finales:', filtrados.length, 'de', (result.data || []).length);
        
        // Actualizar la lista local con los resultados de búsqueda
        // Renderizar y mostrar
        renderizarDropdownEPP(filtrados);
        mostrarDropdownEPP();
        
    } catch (error) {
        console.error('[filtrarDropdownEPP] Error en búsqueda:', error);
    }
}

/**
 * Agregar EPP desde el dropdown
 */
function agregarEPPDesdeDropdown(checkbox) {
    const eppId = parseInt(checkbox.getAttribute('data-epp-id'));
    const nombre = checkbox.getAttribute('data-epp-nombre');
    
    if (checkbox.checked) {
        // Agregar a la lista si no existe
        if (!eppAgregadosList.find(e => e.id == eppId)) {
            const epp = eppDisponiblesList.find(e => e.id == eppId);
            eppAgregadosList.push({
                id: eppId,
                epp_id: eppId,
                nombre: nombre,
                nombre_epp: nombre,
                nombre_completo: nombre,
                cantidad: 1,
                observaciones: '-',
                valor_unitario: 0,
                total: 0,
                imagenes: [],
                imagen: epp?.imagen || ''
            });
            console.log('[agregarEPPDesdeDropdown] EPP agregado:', eppId);
        }
    } else {
        // Quitar de la lista
        eppAgregadosList = eppAgregadosList.filter(e => e.id != eppId);
        console.log('[agregarEPPDesdeDropdown] EPP removido:', eppId);
    }
    
    actualizarSeleccionEPP();
    renderizarTablaEPPAgregados();
}



/**
 * Actualizar contador y estado de selección
 */
function actualizarSeleccionEPP() {
    const total = eppAgregadosList.length;
    document.getElementById('totalSeleccionados').textContent = total;
    document.getElementById('btnFinalizarAgregarEPP').disabled = total === 0;
    
    // Mostrar/ocultar tabla de agregados
    const tabla = document.getElementById('listaEPPAgregados');
    if (total > 0) {
        tabla.style.display = 'block';
        document.getElementById('contadorEPP').textContent = total;
    } else {
        tabla.style.display = 'none';
    }
    
    console.log('[actualizarSeleccionEPP] Total EPPs:', total);
}

/**
 * Cerrar dropdown cuando se hace clic fuera
 */
document.addEventListener('click', function(e) {
    const dropdown = document.getElementById('dropdownEPP');
    const input = document.getElementById('inputBuscadorEPPTabla');
    
    if (!dropdown || !input) return;
    
    if (e.target !== input && !dropdown.contains(e.target)) {
        dropdown.classList.add('hidden');
    }
});

/**
 * Manejo de Ctrl+V (paste) para agregar fotos en la tabla de EPP agregados
 * y en el modo edición (contenedorFotosEPP)
 */
document.addEventListener('paste', function(e) {
    const modal = document.getElementById('modalAgregarEPP');
    if (!modal || modal.style.display === 'none') return;
    
    const items = e.clipboardData.items;
    if (!items) return;
    
    // ========== PRIORIDAD 1: Si clickeó una zona de fotos en la tabla, usar esa ==========
    if (window.zonaFotosActivaId && eppAgregadosList.length > 0) {
        const eppId = parseInt(window.zonaFotosActivaId.replace('fotoZona_', ''));
        console.log('[paste] TABLA PRIORITARIA - Zona activa:', window.zonaFotosActivaId, '-> eppId:', eppId);
        
        const epp = eppAgregadosList.find(e => e.id == eppId);
        if (epp) {
            if (!epp.imagenes) epp.imagenes = [];
            
            let pegadasOk = 0;
            for (let item of items) {
                if (item.type.startsWith('image/')) {
                    const file = item.getAsFile();
                    const blobUrl = URL.createObjectURL(file);
                    epp.imagenes.push({
                        file: file,
                        previewUrl: blobUrl,
                        nombre: file.name
                    });
                    pegadasOk++;
                }
            }
            
            if (pegadasOk > 0) {
                renderizarTablaEPPAgregados();
                console.log('[paste] ✅ Imágenes pegadas en tabla PRIORITARIO - EPP:', eppId, '- Total:', pegadasOk);
                return; // Se procesó en tabla, no continuar
            }
        }
    }
    
    // ========== PRIORIDAD 2: pegar en contenedorFotosEPP (formulario de creación) ==========
    const seccionFotos = document.getElementById('seccionFotosEPP');
    if (seccionFotos && seccionFotos.style.display !== 'none') {
        let tieneImagen = false;
        
        for (let item of items) {
            if (item.type.startsWith('image/')) {
                tieneImagen = true;
                const file = item.getAsFile();
                if (!file) continue;
                
                const previewUrl = URL.createObjectURL(file);
                const nombreArchivo = file.name || 'pegado_' + Date.now() + '.png';
                const extension = nombreArchivo.split('.').pop().toLowerCase();
                
                const imagen = {
                    id: Date.now() + '_paste_' + Math.random().toString(36).substr(2, 5),
                    file: file,
                    previewUrl: previewUrl,
                    nombre: nombreArchivo,
                    extension: extension,
                    tamaño: file.size,
                    pedido_epp_id: null,
                    ruta_original: null,
                    ruta_webp: null,
                    principal: 0,
                    orden: 0
                };
                
                if (!window.fotosEPP) window.fotosEPP = [];
                window.fotosEPP.push(imagen);
                
                if (typeof mostrarVistaPreviaFoto === 'function') {
                    mostrarVistaPreviaFoto(imagen);
                }
                
                console.log('[paste] Imagen pegada en modo edición:', nombreArchivo);
            }
        }
        
        if (tieneImagen) {
            // Actualizar contador
            const contadorFotos = document.getElementById('contadorFotosEPP');
            if (contadorFotos) contadorFotos.textContent = window.fotosEPP ? window.fotosEPP.length : 0;
            
            // Ocultar mensaje drag-drop
            const mensajeDragDrop = document.getElementById('mensajeDragDrop');
            if (mensajeDragDrop && window.fotosEPP && window.fotosEPP.length > 0) {
                mensajeDragDrop.style.display = 'none';
            }
            return; // Ya procesamos, no continuar con lógica de tabla
        }
    }
    
    // ========== PRIORIDAD 3: MODO TABLA - pegar en eppAgregadosList ==========
    if (eppAgregadosList.length === 0) return;
    
    // PRIMERA OPCIÓN: Usar la zona de fotos que fue clickeada (más confiable)
    let eppId = null;
    
    if (window.zonaFotosActivaId) {
        // Extraer el ID del EPP desde la zona activa
        eppId = parseInt(window.zonaFotosActivaId.replace('fotoZona_', ''));
        console.log('[paste] Usando zona de fotos activa:', window.zonaFotosActivaId, '-> eppId:', eppId);
    }
    
    // SEGUNDA OPCIÓN: Detectar cuál zona de fotos tiene foco actualmente
    if (!eppId) {
        const focusedElement = document.activeElement;
        
        if (focusedElement && focusedElement.id && focusedElement.id.startsWith('fotoZona_')) {
            // Si el elemento enfocado es una zona de fotos, extraer el EPP ID
            eppId = parseInt(focusedElement.id.replace('fotoZona_', ''));
            console.log('[paste] Zona de fotos tiene focus:', focusedElement.id);
        } else {
            // Si no hay zona enfocada, buscar en el elemento más cercano con clase fotoZona
            let parent = focusedElement;
            while (parent && !parent.id?.startsWith('fotoZona_')) {
                parent = parent.parentElement;
            }
            if (parent && parent.id?.startsWith('fotoZona_')) {
                eppId = parseInt(parent.id.replace('fotoZona_', ''));
                console.log('[paste] Zona de fotos detectada como padre:', parent.id);
            }
        }
    }
    
    // TERCERA OPCIÓN: Si no logramos detectar, usar el último EPP
    if (!eppId) {
        eppId = eppAgregadosList[eppAgregadosList.length - 1].id;
        console.log('[paste] Ninguna zona detectada, usando último EPP:', eppId);
    }
    
    // Obtener el EPP con el ID detectado
    const epp = eppAgregadosList.find(e => e.id == eppId);
    if (!epp) {
        console.warn('[paste] EPP no encontrado con ID:', eppId);
        return;
    }
    
    if (!epp.imagenes) epp.imagenes = [];
    
    let pegadasOk = 0;
    for (let item of items) {
        if (item.type.startsWith('image/')) {
            const file = item.getAsFile();
            // Usar blob URL en lugar de base64
            const blobUrl = URL.createObjectURL(file);
            epp.imagenes.push({
                file: file,
                previewUrl: blobUrl,
                nombre: file.name
            });
            pegadasOk++;
        }
    }
    
    if (pegadasOk > 0) {
        renderizarTablaEPPAgregados();
        console.log('[paste] ✅ Imágenes pegadas en EPP:', eppId, '- Total:', pegadasOk);
    }
});

/**
 * Soporte para click derecho en miniaturas de fotos para eliminar
 */
document.addEventListener('contextmenu', function(e) {
    const miniatura = e.target.closest('.foto-miniatura');
    if (miniatura) {
        e.preventDefault();
        const eppId = parseInt(miniatura.dataset.eppId);
        const fotoIndex = parseInt(miniatura.dataset.fotoIndex);
        
        if (confirm('¿Eliminar esta imagen?')) {
            eliminarFotoEPP(eppId, fotoIndex);
        }
    }
});

/**
 * Soporte para tecla Delete cuando está enfocada una zona de fotos
 */
document.addEventListener('keydown', function(e) {
    // Si se presiona Delete y hay una zona de fotos enfocada
    if (e.key === 'Delete') {
        const fotoZona = document.activeElement?.closest('[id^="fotoZona_"]');
        if (fotoZona) {
            e.preventDefault();
            const eppId = parseInt(fotoZona.id.replace('fotoZona_', ''));
            const epp = eppAgregadosList.find(e => e.id == eppId);
            
            // Eliminar la última foto agregada
            if (epp && epp.imagenes && epp.imagenes.length > 0) {
                const ultimaIndex = epp.imagenes.length - 1;
                if (confirm('¿Eliminar la última imagen agregada?')) {
                    eliminarFotoEPP(eppId, ultimaIndex);
                }
            }
        }
    }
});

/**
 * Renderizar tabla de EPP agregados
 */
function renderizarTablaEPPAgregados() {
    const tbody = document.getElementById('cuerpoTablaEPP');
    if (!tbody) return;
    
    const esCotizacion = !!window.__EPP_COTIZACION_MODE__;
    
    // Mostrar/ocultar columnas de cotización en el header
    document.querySelectorAll('.columna-cotizacion').forEach(col => {
        col.style.display = esCotizacion ? '' : 'none';
    });
    
    // Ajustar ancho del modal según modo
    const modalContent = document.getElementById('modalAgregarEPPContent');
    if (modalContent) {
        if (esCotizacion) {
            modalContent.classList.remove('max-w-3xl');
            modalContent.classList.add('max-w-5xl');
        } else {
            modalContent.classList.remove('max-w-5xl');
            modalContent.classList.add('max-w-3xl');
        }
    }
    
    tbody.innerHTML = '';
    
    eppAgregadosList.forEach((epp, idx) => {
        const row = document.createElement('tr');
        row.className = idx % 2 === 0 ? 'bg-white' : 'bg-gray-50';
        
        // Inicializar valores si no existen
        if (epp.valor_unitario === undefined) epp.valor_unitario = 0;
        if (epp.total === undefined) epp.total = 0;
        
        // Miniaturas de fotos
        let fotosHtml = '';
        if (epp.imagenes && epp.imagenes.length > 0) {
            const fotosMostrar = epp.imagenes.slice(0, 3);
            fotosHtml = fotosMostrar.map((foto, i) => 
                `<div class="relative group foto-miniatura cursor-pointer" 
                    data-epp-id="${epp.id}" 
                    data-foto-index="${i}"
                    title="Click para eliminar, Ctrl+Click para expandir">
                    <img src="${foto.previewUrl || foto.base64}" alt="Foto ${i+1}" class="w-8 h-8 object-cover rounded border border-gray-200">
                    <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-40 rounded transition flex items-center justify-center">
                        <button type="button" 
                            onclick="event.stopPropagation(); eliminarFotoEPP(${epp.id}, ${i})" 
                            class="opacity-0 group-hover:opacity-100 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center transition transform hover:scale-110 hover:bg-red-600"
                            title="Eliminar imagen">
                            <i class="material-symbols-rounded" style="font-size: 14px;">delete</i>
                        </button>
                    </div>
                </div>`
            ).join('');
            if (epp.imagenes.length > 3) {
                fotosHtml += `<span class="text-xs text-gray-500 font-medium bg-gray-200 rounded px-2 py-1 cursor-pointer hover:bg-gray-300 transition" title="Haz clic para ver el resto">+${epp.imagenes.length - 3}</span>`;
            }
        }
        
        // Columnas de cotización (valor unitario y total)
        const columnasCotizacion = esCotizacion ? `
            <td class="px-4 py-2 text-center columna-cotizacion">
                <input 
                    type="number" 
                    value="${epp.valor_unitario || ''}" 
                    min="0" 
                    step="0.01"
                    onchange="actualizarValorUnitarioEPP(${epp.id}, this.value)"
                    class="w-24 px-2 py-1 border border-gray-300 rounded text-center text-sm"
                    placeholder="0"
                >
            </td>
            <td class="px-4 py-2 text-center columna-cotizacion">
                <span id="totalEPPItem_${epp.id}" class="font-semibold text-gray-900 text-sm">
                    ${((epp.cantidad || 1) * (epp.valor_unitario || 0)).toLocaleString('es-CO', {minimumFractionDigits: 0, maximumFractionDigits: 2})}
                </span>
            </td>
        ` : '';
        
        row.innerHTML = `
            <td class="px-4 py-2 text-gray-900 font-medium">${epp.nombre_completo}</td>
            <td class="px-4 py-2 text-center">
                <input 
                    type="number" 
                    value="${epp.cantidad}" 
                    min="1" 
                    onchange="actualizarCantidadEPP(${epp.id}, this.value)"
                    class="w-14 px-2 py-1 border border-gray-300 rounded text-center text-sm"
                >
            </td>
            <td class="px-4 py-2 text-gray-700 text-xs">
                <input 
                    type="text" 
                    value="${epp.observaciones}" 
                    onchange="actualizarObservacionesEPP(${epp.id}, this.value)"
                    class="w-full px-2 py-1 border border-gray-300 rounded text-xs"
                    placeholder="Observaciones..."
                >
            </td>
            ${columnasCotizacion}
            <td class="px-4 py-2 text-center">
                <div class="flex gap-1 items-center justify-center flex-wrap border-2 border-dashed border-gray-300 rounded p-2 min-h-10 cursor-pointer bg-gray-50 hover:bg-gray-100 transition" 
                    id="fotoZona_${epp.id}"
                    tabindex="0"
                    ondrop="manejarDropFotosEPP(event, ${epp.id})" 
                    ondragover="manejarDragOverFotosEPP(event)" 
                    ondragleave="manejarDragLeaveFotosEPP(event)">
                    ${fotosHtml ? `<div class="flex gap-1 items-center">${fotosHtml}</div>` : '<span class="text-gray-400 text-xs">Arrastra imágenes</span>'}
                </div>
                <input type="file" id="inputFotos_${epp.id}" multiple accept="image/*" style="display: none;" onchange="manejarSeleccionFotosEPP(event, ${epp.id})">
            </td>
            <td class="px-4 py-2 text-center">
                <button 
                    type="button"
                    onclick="event.stopPropagation(); eliminarEPPDeLista(${epp.id})"
                    class="text-red-600 hover:text-red-800 font-medium transition"
                >
                    <i class="material-symbols-rounded" style="font-size: 18px;">delete</i>
                </button>
            </td>
        `;
        
        tbody.appendChild(row);
    });
    
    console.log('[renderizarTablaEPPAgregados] Tabla actualizada con', eppAgregadosList.length, 'EPPs', esCotizacion ? '(modo cotización)' : '');
}

/**
 * Agregar fotos a un EPP desde selección de archivos
 */
function manejarSeleccionFotosEPP(event, eppId) {
    const files = event.target.files;
    const epp = eppAgregadosList.find(e => e.id == eppId);
    if (!epp) return;
    
    if (!epp.imagenes) epp.imagenes = [];
    
    Array.from(files).forEach(file => {
        // Usar blob URL en lugar de base64
        const blobUrl = URL.createObjectURL(file);
        epp.imagenes.push({
            file: file,
            previewUrl: blobUrl,
            nombre: file.name
        });
        renderizarTablaEPPAgregados();
    });
    
    console.log('[manejarSeleccionFotosEPP] Fotos agregadas para EPP', eppId);
}

/**
 * Manejo de drag & drop para fotos
 */
function manejarDragOverFotosEPP(event) {
    event.preventDefault();
    event.stopPropagation();
    event.currentTarget.classList.add('bg-blue-100', 'border-blue-400');
}

function manejarDragLeaveFotosEPP(event) {
    event.preventDefault();
    event.stopPropagation();
    event.currentTarget.classList.remove('bg-blue-100', 'border-blue-400');
}

function manejarDropFotosEPP(event, eppId) {
    event.preventDefault();
    event.stopPropagation();
    event.currentTarget.classList.remove('bg-blue-100', 'border-blue-400');
    
    const files = event.dataTransfer.files;
    const epp = eppAgregadosList.find(e => e.id == eppId);
    if (!epp) return;
    
    if (!epp.imagenes) epp.imagenes = [];
    
    Array.from(files).forEach(file => {
        if (file.type.startsWith('image/')) {
            // Usar blob URL en lugar de base64
            const blobUrl = URL.createObjectURL(file);
            epp.imagenes.push({
                file: file,
                previewUrl: blobUrl,
                nombre: file.name
            });
            renderizarTablaEPPAgregados();
        }
    });
    
    console.log('[manejarDropFotosEPP] Fotos agregadas por drag & drop para EPP', eppId);
}

/**
 * Eliminar una foto de un EPP
 * Opciones de eliminación:
 * 1. Click en el botón X
 * 2. Click derecho en la miniatura
 * 3. Presionar Delete cuando está seleccionada
 */
function eliminarFotoEPP(eppId, indexFoto, event) {
    // Prevenir propagación si viene de un evento
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    
    const epp = eppAgregadosList.find(e => e.id == eppId);
    if (epp && epp.imagenes) {
        const fotoEliminada = epp.imagenes[indexFoto];
        
        // Liberar la blob URL para ahorrar memoria
        if (fotoEliminada && fotoEliminada.previewUrl) {
            URL.revokeObjectURL(fotoEliminada.previewUrl);
        }
        
        epp.imagenes.splice(indexFoto, 1);
        renderizarTablaEPPAgregados();
        console.log('[eliminarFotoEPP] Foto eliminada de EPP', eppId, '- Nombre:', fotoEliminada?.nombre);
    }
}

/**
 * Manejar eventos de las miniaturas (click derecho, etc.)
 */
function manejarEventoMiniatura(event) {
    if (event.button === 2) { // Click derecho
        event.preventDefault();
        const miniatura = event.target.closest('.foto-miniatura');
        if (miniatura) {
            const eppId = parseInt(miniatura.dataset.eppId);
            const fotoIndex = parseInt(miniatura.dataset.fotoIndex);
            
            // Mostrar confirmación simple
            if (confirm('¿Eliminar esta imagen?')) {
                eliminarFotoEPP(eppId, fotoIndex);
            }
        }
    }
}

/**
 * Actualizar cantidad de EPP agregado
 */
function actualizarCantidadEPP(eppId, cantidad) {
    const index = eppAgregadosList.findIndex(e => e.id == eppId);
    if (index !== -1) {
        eppAgregadosList[index].cantidad = parseInt(cantidad) || 1;
        // Recalcular total si estamos en modo cotización
        if (window.__EPP_COTIZACION_MODE__) {
            const vu = parseFloat(eppAgregadosList[index].valor_unitario) || 0;
            eppAgregadosList[index].total = eppAgregadosList[index].cantidad * vu;
            const spanTotal = document.getElementById(`totalEPPItem_${eppId}`);
            if (spanTotal) {
                spanTotal.textContent = eppAgregadosList[index].total.toLocaleString('es-CO', {minimumFractionDigits: 0, maximumFractionDigits: 2});
            }
        }
        console.log('[actualizarCantidadEPP] Cantidad actualizada para EPP', eppId);
    }
}

/**
 * Actualizar valor unitario de EPP agregado (solo en modo cotización)
 */
function actualizarValorUnitarioEPP(eppId, valor) {
    const index = eppAgregadosList.findIndex(e => e.id == eppId);
    if (index !== -1) {
        eppAgregadosList[index].valor_unitario = parseFloat(valor) || 0;
        const cant = parseInt(eppAgregadosList[index].cantidad) || 1;
        eppAgregadosList[index].total = cant * eppAgregadosList[index].valor_unitario;
        
        const spanTotal = document.getElementById(`totalEPPItem_${eppId}`);
        if (spanTotal) {
            spanTotal.textContent = eppAgregadosList[index].total.toLocaleString('es-CO', {minimumFractionDigits: 0, maximumFractionDigits: 2});
        }
        console.log('[actualizarValorUnitarioEPP] V.Unitario actualizado para EPP', eppId, '=> Total:', eppAgregadosList[index].total);
    }
}

/**
 * Actualizar observaciones de EPP agregado
 */
function actualizarObservacionesEPP(eppId, observaciones) {
    const index = eppAgregadosList.findIndex(e => e.id == eppId);
    if (index !== -1) {
        eppAgregadosList[index].observaciones = observaciones || '-';
        console.log('[actualizarObservacionesEPP] Observaciones actualizadas para EPP', eppId);
    }
}

/**
 * Eliminar EPP de la lista agregada
 */
function eliminarEPPDeLista(eppId) {
    const index = eppAgregadosList.findIndex(e => e.id == eppId);
    if (index !== -1) {
        eppAgregadosList.splice(index, 1);
        
        // Desmarcar checkbox
        const checkbox = document.querySelector(`input[data-epp-id="${eppId}"]`);
        if (checkbox) checkbox.checked = false;
        
        // Actualizar UI
        actualizarSeleccionEPP();
        renderizarTablaEPPAgregados();
        
        console.log('[eliminarEPPDeLista] EPP eliminado:', eppId);
    }
}

/**
 * Abrir modal y cargar EPP disponibles
 */
function abrirModalAgregarEPP() {
    console.log('[abrirModalAgregarEPP] Abriendo modal con tabla de selección múltiple');
    const modal = document.getElementById('modalAgregarEPP');
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    
    // IMPORTANTE: Obtener los EPPs ya agregados en el formulario
    console.log('[abrirModalAgregarEPP] Llamando a obtenerEPPsYaAgregadosEnFormulario...');
    const eppEnFormulario = obtenerEPPsYaAgregadosEnFormulario();
    console.log('[abrirModalAgregarEPP] Variable global actualizada:', eppYaAgregadosEnFormulario);
    
    // IMPORTANTE: Solo limpiar si la lista está vacía (primera vez)
    // Si ya hay EPPs en la lista (regresos al modal), preservarlos
    if (eppAgregadosList.length === 0) {
        console.log('[abrirModalAgregarEPP] PRIMERA VEZ - limpiando estado previo');
        eppDisponiblesList = []; // Vaciar lista de disponibles solo si es la primera vez
        document.getElementById('inputBuscadorEPPTabla').value = '';
    } else {
        console.log('[abrirModalAgregarEPP] REGRESO AL MODAL - preservando', eppAgregadosList.length, 'EPPs agregados');
    }
    
    document.getElementById('contadorEPP').textContent = eppAgregadosList.length;
    document.getElementById('listaEPPAgregados').style.display = eppAgregadosList.length > 0 ? 'block' : 'none';
    document.getElementById('btnFinalizarAgregarEPP').disabled = eppAgregadosList.length === 0;
    
    // Limpiar dropdown - no cargar EPPs disponibles inicialmente
    const opcionesContainer = document.getElementById('opcionesDropdownEPP');
    if (opcionesContainer) {
        opcionesContainer.innerHTML = '';
    }
    
    // Ocultar el dropdown
    const dropdown = document.getElementById('dropdownEPP');
    if (dropdown) {
        dropdown.classList.add('hidden');
    }
    
    // Mostrar mensaje "sin resultados"
    const mensajeSinResultados = document.getElementById('mensajeSinResultados');
    if (mensajeSinResultados) {
        mensajeSinResultados.classList.remove('hidden');
    }
    
    // Re-renderizar tabla si hay EPPs
    if (eppAgregadosList.length > 0) {
        renderizarTablaEPPAgregados();
        console.log('[abrirModalAgregarEPP] Tabla Re-renderizada con', eppAgregadosList.length, 'EPPs');
    }
    
    // IMPORTANTÍSIMO: Ocultar sección de edición para evitar que Ctrl+V vaya allá
    const seccionFotos = document.getElementById('seccionFotosEPP');
    if (seccionFotos) {
        seccionFotos.style.display = 'none';
        console.log('[abrirModalAgregarEPP] Sección de edición ocultada');
    }
    
    // Limpiar zona de fotos activa desde cierre anterior
    window.zonaFotosActivaId = null;
    
    // ===== REGISTRAR LISTENER DE PASTE PARA CTRL+V =====
    document.addEventListener('paste', window.handlePasteEPP);
    console.log('[abrirModalAgregarEPP] Paste listener registrado para Ctrl+V');
    
    console.log('[abrirModalAgregarEPP] Modal abierto - EPPs agregados:', eppAgregadosList.length);
    console.log('[abrirModalAgregarEPP] EPPs a EXCLUIR:', eppYaAgregadosEnFormulario);
}

function actualizarTotalEPP() {
    const cantidadInput = document.getElementById('cantidadEPP');
    const valorUnitarioInput = document.getElementById('valorUnitarioEPP');
    const totalInput = document.getElementById('totalEPP');
    if (!cantidadInput || !valorUnitarioInput || !totalInput) return;

    const formatearNumero = (num) => {
        if (!Number.isFinite(num)) return '0';
        if (Number.isInteger(num)) return String(num);
        const s = num.toFixed(2);
        return s.replace(/\.00$/, '').replace(/(\.[0-9])0$/, '$1');
    };

    const cantidad = parseInt(cantidadInput.value) || 0;
    const vuRaw = String(valorUnitarioInput.value || '').trim();
    const vu = vuRaw !== '' && !isNaN(Number(vuRaw)) ? Number(vuRaw) : null;

    const total = (vu !== null && cantidad > 0) ? (vu * cantidad) : 0;
    totalInput.value = formatearNumero(total);
}

/**
 * Valida si hay datos sin guardar en el modal
 */
function hayDatosNoGuardados() {
    // Si hay EPPs en la lista, hay datos
    if (eppAgregadosList.length > 0) {
        return true;
    }
    
    // Validar si hay producto seleccionado
    if (productoSeleccionadoEPP) {
        return true;
    }
    
    // Validar si hay cantidad diferente de 1 (valor inicial)
    const cantidadInput = document.getElementById('cantidadEPP');
    if (cantidadInput && cantidadInput.value && parseInt(cantidadInput.value) !== 1) {
        return true;
    }
    
    // Validar si hay observaciones
    const obsInput = document.getElementById('observacionesEPP');
    if (obsInput && obsInput.value && obsInput.value.trim() !== '') {
        return true;
    }
    
    // Validar si hay fotos
    if (window.fotosEPP && window.fotosEPP.length > 0) {
        return true;
    }
    
    // Validar si hay valor unitario (modo cotización)
    const vuInput = document.getElementById('valorUnitarioEPP');
    if (vuInput && vuInput.value && vuInput.value.trim() !== '' && vuInput.value !== '0') {
        return true;
    }
    
    return false;
}

function cerrarModalAgregarEPP() {
    console.log('🔒 [cerrarModalAgregarEPP] Cerrando modal');
    
    // Validar si hay datos sin guardar (excepto en modo edición)
    const enModoEdicion = !!window.eppEnEdicion;
    if (!enModoEdicion && hayDatosNoGuardados()) {
        console.log('🔒 [cerrarModalAgregarEPP] Hay datos sin guardar - pidiendo confirmación');
        Swal.fire({
            icon: 'warning',
            title: '¿Descartar cambios?',
            text: 'Tienes datos sin guardar que se perderán. ¿Estás seguro de que deseas cerrar?',
            showCancelButton: true,
            confirmButtonText: 'Sí, descartar',
            cancelButtonText: 'No, continuar',
            confirmButtonColor: '#dd3333',
            cancelButtonColor: '#3085d6'
        }).then((result) => {
            if (result.isConfirmed) {
                console.log('🔒 [cerrarModalAgregarEPP] Usuario confirmó descartar cambios');
                cerrarModalAgregarEPPConfirmado();
            }
        });
        return; // No continuar si no está confirmado
    }
    
    // Si no hay datos o está en modo edición, cerrar directo
    cerrarModalAgregarEPPConfirmado();
}

/**
 * Función auxiliar que realmente cierra el modal
 */
function cerrarModalAgregarEPPConfirmado() {
    console.log('🔒 [cerrarModalAgregarEPPConfirmado] Cerrando modal confirmado');
    const modal = document.getElementById('modalAgregarEPP');
    
    // Resetear título del modal a estado por defecto
    const tituloModal = modal.querySelector('h2.text-white');
    if (tituloModal) {
        tituloModal.textContent = 'Agregar EPP al Pedido';
        console.log('🔒 [cerrarModalAgregarEPPConfirmado] Título reseteado a: Agregar EPP al Pedido');
    }
    
    modal.style.display = 'none';
    
    // Remover listener de paste
    document.removeEventListener('paste', window.handlePasteEPP);
    console.log('[cerrarModalAgregarEPP] Paste listener removido');
    document.body.style.overflow = 'auto';
    eppAgregadosList = []; // Limpiar lista al cerrar
    window.eppAgregadosList = eppAgregadosList;
    
    // Limpiar zona de fotos activa
    window.zonaFotosActivaId = null;
    console.log('[cerrarModalAgregarEPP] Zona de fotos activa reseteada');

    // Limpiar flag de agregar EPP a pedido existente
    window.__EPP_AGREGAR_PEDIDO_EXISTENTE__ = null;

    // Siempre limpiar estado de edición y formulario al cerrar/cancelar
    eppEnEdicion = null;
    window.eppEnEdicion = null;
    console.log('🔒 [cerrarModalAgregarEPPConfirmado] window.eppEnEdicion limpiado');
    
    // Limpiar imágenes temporales al cerrar
    limpiarImagenesTemporales();
    
    resetearModalAgregarEPP();
}

/**
 * Función para limpiar imágenes temporales del storage
 */
function limpiarImagenesTemporales() {
    // NO revocar URLs blob - se mantienen en cache global window._eppFilesCache
    // Las URLs se revocarán cuando se elimine el item de la tabla
    // Simplemente limpiar el array temporal de fotosEPP
    window.fotosEPP = [];
    
    console.log('[limpiarImagenesTemporales] Array de fotos temporales limpiado (URLs en cache global)');
}

function resetearModalAgregarEPP() {
    console.log('📖 [resetearModalAgregarEPP] INICIANDO reset COMPLETO del modal');
    
    // Limpiar imágenes temporales
    limpiarImagenesTemporales();
    
    // Limpiar producto seleccionado
    productoSeleccionadoEPP = null;
    console.log('📖 [resetearModalAgregarEPP] Producto seleccionado limpiado');
    
    // ELEMENTOS PRINCIPALES - Ocultarlos todos
    const elementosOcultar = [
        'productoCardEPP',
        'formularioAgregarEPP',
        'observacionesContainer',

        'resultadosBuscadorEPP',
        'valorUnitarioTotalContainer'
    ];
    
    elementosOcultar.forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            el.style.setProperty('display', 'none', 'important');
            console.log(`📖 [resetearModalAgregarEPP] ${id} ocultado`);
        }
    });
    
    // CAMPOS DE FORMULARIO - Resetear valores y deshabilitar
    const cantidadEPP = document.getElementById('cantidadEPP');
    const observacionesEPP = document.getElementById('observacionesEPP');
    const nombreProductoEPP = document.getElementById('nombreProductoEPP');
    const inputBuscadorEPP = document.getElementById('inputBuscadorEPP');
    
    if (cantidadEPP) {
        cantidadEPP.value = '1';
        cantidadEPP.disabled = true;
    }
    if (observacionesEPP) {
        observacionesEPP.value = '';
        observacionesEPP.disabled = true;
    }
    if (nombreProductoEPP) {
        nombreProductoEPP.value = '';
    }
    if (inputBuscadorEPP) {
        inputBuscadorEPP.value = '';
    }
    
    // VALOR UNITARIO Y TOTAL
    const valorUnitarioEPP = document.getElementById('valorUnitarioEPP');
    const totalEPP = document.getElementById('totalEPP');
    
    if (valorUnitarioEPP) {
        valorUnitarioEPP.value = '';
        valorUnitarioEPP.disabled = true;
    }
    if (totalEPP) {
        totalEPP.value = '0';
    }
    
    // LIMPIAR CONTAINER DE FOTOS
    const contenedorFotosEPP = document.getElementById('contenedorFotosEPP');
    const mensajeDragDrop = document.getElementById('mensajeDragDrop');
    
    if (contenedorFotosEPP) {
        // Eliminar elementos de fotos pero mantener mensaje
        const fotosItems = contenedorFotosEPP.querySelectorAll('.foto-epp-item');
        fotosItems.forEach(item => item.remove());
        console.log('📖 [resetearModalAgregarEPP] Fotos del contenedor limpiadas');
    }
    
    if (mensajeDragDrop) {
        mensajeDragDrop.style.display = 'flex';
        console.log('📖 [resetearModalAgregarEPP] Mensaje inicial de drag-drop restaurado');
    }
    
    // BOTONES DEL FOOTER (con null checks para evitar crash en contexto de edición)
    const btnAgregar = document.getElementById('btnAgregarALista');
    const btnFinalizar = document.getElementById('btnFinalizarAgregarEPP');
    const btnGuardar = document.getElementById('btnGuardarCambiosEPP');
    if (btnAgregar) btnAgregar.disabled = true;
    if (btnFinalizar) { btnFinalizar.disabled = true; btnFinalizar.style.setProperty('display', 'flex', 'important'); }
    if (btnGuardar) { btnGuardar.disabled = true; btnGuardar.style.setProperty('display', 'none', 'important'); }
    
    console.log('📖 [resetearModalAgregarEPP] Botones reseteados');
    
    // Restaurar sección de "EPP Agregados" al resetear (SOLO si no estamos editando)
    const enEdicion = !!eppEnEdicion || !!window.eppEnEdicion;
    const listaEPPAgregados = document.getElementById('listaEPPAgregados');
    
    if (listaEPPAgregados) {
        if (enEdicion) {
            // Si estamos editando, mantener la tabla oculta
            listaEPPAgregados.removeAttribute('style');
            listaEPPAgregados.style.setProperty('display', 'none', 'important');
            listaEPPAgregados.style.setProperty('visibility', 'hidden', 'important');
            console.log('📖 [resetearModalAgregarEPP] Tabla ocultada (modo edición)');
        } else {
            // Si estamos en modo agregar, mostrar la tabla
            listaEPPAgregados.removeAttribute('style');
            listaEPPAgregados.style.setProperty('display', 'block', 'important');
            listaEPPAgregados.style.setProperty('visibility', 'visible', 'important');
            console.log('📖 [resetearModalAgregarEPP] Tabla restaurada (modo agregar)');
        }
    }
    
    actualizarEstilosCampos();
    
    // Restaurar visibilidad del buscador (oculto en modo edición)
    const buscadorSection = document.getElementById('buscadorEPPSection');
    if (buscadorSection) {
        buscadorSection.style.display = '';
    }
    
    // Ocultar sección de fotos (solo para modo edición)
    const seccionFotosResetEl = document.getElementById('seccionFotosEPP');
    if (seccionFotosResetEl) {
        seccionFotosResetEl.style.display = 'none';
    }
    
    // Resetear contador de fotos
    const contadorFotosReset = document.getElementById('contadorFotosEPP');
    if (contadorFotosReset) contadorFotosReset.textContent = '0';
    
    console.log('📖 [resetearModalAgregarEPP] COMPLETADO - Modal reseteado completamente');
}

function filtrarEPPBuscador(valor) {
    const busqueda = valor.toLowerCase().trim();
    
    if (!busqueda) {
        document.getElementById('resultadosBuscadorEPP').style.display = 'none';
        document.getElementById('productoCardEPP').style.display = 'none';
        document.getElementById('formularioAgregarEPP').style.display = 'none';
        document.getElementById('observacionesContainer').style.display = 'none';
        resetearFormularioEPP();
        return;
    }

    // Usar el filtrarEPP del servicio que llena el contenedor resultadosBuscadorEPP
    if (window.eppService && typeof window.eppService.filtrarEPP === 'function') {
        window.eppService.filtrarEPP(busqueda);
        // El servicio llenará automáticamente resultadosBuscadorEPP
    } else {
        console.warn('eppService.filtrarEPP no disponible');
    }
}

function mostrarProductoEPP(producto) {
    console.log(' [mostrarProductoEPP] Llamado con producto:', producto);
    productoSeleccionadoEPP = producto;
    
    // Detectar si estamos en modo edición
    const enModoEdicion = window.eppEnEdicion && typeof window.eppEnEdicion === 'object' && Object.keys(window.eppEnEdicion).length > 0;
    console.log(' [mostrarProductoEPP] En modo edición:', enModoEdicion);
    
    // LIMPIAR FOTOS DEL PRODUCTO ANTERIOR - pero solo en modo NORMAL
    // En modo edición, mantenemos las fotos que el usuario haya cargado
    if (!enModoEdicion) {
        window.fotosEPP = [];
        const contenedorFotosEPP = document.getElementById('contenedorFotosEPP');
        const mensajeDragDrop = document.getElementById('mensajeDragDrop');
        
        if (contenedorFotosEPP) {
            // Eliminar elementos de fotos pero mantener mensaje
            const fotosItems = contenedorFotosEPP.querySelectorAll('.foto-epp-item');
            fotosItems.forEach(item => item.remove());
            console.log(' [mostrarProductoEPP] Fotos del producto anterior limpiadas (modo normal)');
        }
        
        if (mensajeDragDrop) {
            mensajeDragDrop.style.display = 'flex';
            console.log(' [mostrarProductoEPP] Mensaje inicial de drag-drop mostrado');
        }
    } else {
        console.log(' [mostrarProductoEPP] Modo edición - mantienen las fotos actuales');
    }
    
    // Mostrar tarjeta
    const tarjeta = document.getElementById('productoCardEPP');
    console.log(' [mostrarProductoEPP] Elemento tarjeta encontrado:', !!tarjeta);
    if (tarjeta) {
        tarjeta.style.display = 'block';
        console.log(' [mostrarProductoEPP] Tarjeta visible - display:', tarjeta.style.display);
    }
    
    const nombreElement = document.getElementById('nombreProductoEPP');
    console.log(' [mostrarProductoEPP] Elemento nombre encontrado:', !!nombreElement);
    if (nombreElement) {
        nombreElement.value = producto.nombre_completo || producto.nombre;
        console.log(' [mostrarProductoEPP] Nombre actualizado:', nombreElement.value);
    }

    // Mostrar formulario
    const formulario = document.getElementById('formularioAgregarEPP');
    console.log(' [mostrarProductoEPP] Elemento formulario encontrado:', !!formulario);
    if (formulario) {
        formulario.style.display = 'grid';
        console.log(' [mostrarProductoEPP] Formulario visible - display:', formulario.style.display);
    }
    
    const obsContainer = document.getElementById('observacionesContainer');
    console.log(' [mostrarProductoEPP] Elemento observaciones container encontrado:', !!obsContainer);
    if (obsContainer) {
        obsContainer.style.display = 'block';
        console.log(' [mostrarProductoEPP] Observaciones container visible - display:', obsContainer.style.display);
    }
    
    const btnAgregar = document.getElementById('btnAgregarALista');
    console.log(' [mostrarProductoEPP] Botón agregar encontrado:', !!btnAgregar);
    if (btnAgregar) {
        btnAgregar.style.display = 'flex';
        console.log(' [mostrarProductoEPP] Botón agregar visible - display:', btnAgregar.style.display);
    }

    // Habilitar campos
    const cantidadInput = document.getElementById('cantidadEPP');
    const obsInput = document.getElementById('observacionesEPP');
    console.log(' [mostrarProductoEPP] Cantidad input encontrado:', !!cantidadInput, 'Obs input encontrado:', !!obsInput);
    
    if (cantidadInput) {
        cantidadInput.disabled = false;
        console.log(' [mostrarProductoEPP] Campo cantidad habilitado');
    }

    const valorUnitarioInput = document.getElementById('valorUnitarioEPP');
    const vuCont = document.getElementById('valorUnitarioTotalContainer');
    const modoCotizacion = !!window.__EPP_COTIZACION_MODE__;
    if (vuCont) vuCont.style.display = modoCotizacion ? 'block' : 'none';
    if (valorUnitarioInput) {
        valorUnitarioInput.disabled = !modoCotizacion;
    }

    // Recalcular total al habilitar
    actualizarTotalEPP();
    if (obsInput) {
        obsInput.disabled = false;
        console.log(' [mostrarProductoEPP] Campo observaciones habilitado');
    }
    if (btnAgregar) {
        btnAgregar.disabled = false;
        console.log(' [mostrarProductoEPP] Botón habilitado');
    }

    actualizarEstilosCampos();
    console.log(' [mostrarProductoEPP] Completado - todos los elementos configurados');
}


// Función cargarTallasEPP removida - talla incluida en nombre_completo

// Almacenar referencias globales a archivos para mantener blob URLs válidas
window._eppFilesCache = new Map();

// Función para guardar archivo en caché global
function _guardarArchivoEnCache(blobUrl, file) {
    window._eppFilesCache.set(blobUrl, file);
    console.log(`[_guardarArchivoEnCache] Archivo cacheado para blob URL: ${blobUrl}`);
}

// Función para convertir URLs blob a archivos para envío como FormData (no base64 en JSON)
async function convertirBlobAArchivos(imagenes) {
    const imagenesConvertidas = [];
    
    for (const imagen of imagenes) {
        if (imagen.previewUrl && imagen.file) {
            // Obtener ID del pedido actual (temporal hasta que se cree el pedido)
            const pedidoId = window.pedidoIdActual || 'temp';
            
            // Generar nombre único para evitar conflictos
            const timestamp = Date.now();
            const randomSuffix = Math.random().toString(36).substring(2, 8);
            const nombreLimpio = imagen.nombre.replace(/[^a-zA-Z0-9.-]/g, '_');
            const nombreArchivo = `${timestamp}_${randomSuffix}_${nombreLimpio}`;
            
            // Preparar rutas para guardado en storage/pedidos/[pedido_id]/epp/
            const rutaStorage = `pedidos/${pedidoId}/epp`;
            const rutaCompleta = `${rutaStorage}/${nombreArchivo}`;
            const rutaWeb = `/storage/${rutaCompleta}`;
            
            imagenesConvertidas.push({
                id: imagen.id,
                nombre: imagen.nombre,
                extension: imagen.extension,
                tamaño: imagen.tamaño,
                file: imagen.file, // Para FormData
                previewUrl: imagen.previewUrl, // Para mostrar en UI
                
                // Metadatos para guardado en BD (sin base64)
                ruta_storage: rutaStorage, // pedidos/25/epp
                nombre_archivo: nombreArchivo, // 123456_abc123_images.jfif
                ruta_completa: rutaCompleta, // pedidos/25/epp/123456_abc123_images.jfif
                ruta_web: rutaWeb, // /storage/pedidos/25/epp/123456_abc123_images.jfif
                
                // Para tabla pedido_epp_imagenes
                pedido_epp_id: null, // Se asignará cuando se guarde el EPP
                principal: 0, // 0 = no principal, 1 = principal
                orden: imagenesConvertidas.length + 1 // Orden automático
            });
            
            console.log(`[convertirBlobAArchivos] Imagen preparada: ${imagen.nombre} -> ${rutaWeb}`);
        } else {
            // Si ya es base64 u otro formato, mantenerla (para compatibilidad)
            imagenesConvertidas.push(imagen);
        }
    }
    
    return imagenesConvertidas;
}

// Función para preparar datos para envío: JSON limpio + FormData para imágenes
function prepararDatosParaEnvio(itemsPedido) {
    const datosLimpios = [];
    const formData = new FormData();
    
    itemsPedido.forEach((item, index) => {
        if (item.tipo === 'epp' && item.imagenes && item.imagenes.length > 0) {
            // Procesar EPP con imágenes
            const eppData = {
                uid: item.uid || `uid-${Date.now()}-${Math.random().toString(36).substring(2, 8)}`,
                epp_id: item.epp_id,
                nombre_epp: item.nombre_epp,
                categoria: item.categoria || '',
                cantidad: item.cantidad,
                observaciones: item.observaciones,
                imagenes: [] // Array vacío, las imágenes van en FormData
            };
            
            // Agregar cada imagen al FormData
            item.imagenes.forEach((imagen, imgIndex) => {
                if (imagen.file) {
                    // Agregar archivo al FormData con nombre único
                    const fieldName = `epp_imagen_${index}_${imgIndex}`;
                    formData.append(fieldName, imagen.file);
                    
                    // Agregar metadatos de la imagen al FormData
                    formData.append(`${fieldName}_metadata`, JSON.stringify({
                        id: imagen.id,
                        nombre: imagen.nombre,
                        extension: imagen.extension,
                        tamaño: imagen.tamaño,
                        ruta_storage: imagen.ruta_storage,
                        nombre_archivo: imagen.nombre_archivo,
                        ruta_completa: imagen.ruta_completa,
                        ruta_web: imagen.ruta_web,
                        pedido_epp_id: imagen.pedido_epp_id,
                        principal: imagen.principal,
                        orden: imagen.orden
                    }));
                    
                    // Agregar referencia en el EPP (sin base64)
                    eppData.imagenes.push({
                        id: imagen.id,
                        nombre: imagen.nombre,
                        ruta_web: imagen.ruta_web,
                        principal: imagen.principal,
                        orden: imagen.orden
                    });
                }
            });
            
            datosLimpios.push(eppData);
        } else {
            // Agregar otros items sin modificar
            datosLimpios.push(item);
        }
    });
    
    return {
        jsonData: datosLimpios,
        formData: formData
    };
}

// Hacer la función disponible globalmente
window.prepararDatosParaEnvio = prepararDatosParaEnvio;

function agregarEPPALista() {
    if (!productoSeleccionadoEPP) {
        alert('Por favor selecciona un producto');
        return;
    }

    const cantidad = document.getElementById('cantidadEPP').value;
    const observaciones = document.getElementById('observacionesEPP').value || '-';

    if (!cantidad || cantidad <= 0) {
        alert('Por favor ingresa una cantidad válida');
        return;
    }

    const modoCotizacion = !!window.__EPP_COTIZACION_MODE__;
    const valorUnitarioRaw = modoCotizacion ? document.getElementById('valorUnitarioEPP')?.value : null;
    const valorUnitario = (valorUnitarioRaw !== undefined && valorUnitarioRaw !== null && String(valorUnitarioRaw).trim() !== '')
        ? Number(valorUnitarioRaw)
        : null;
    const totalValue = modoCotizacion ? document.getElementById('totalEPP')?.value : null;
    const total = (totalValue !== undefined && totalValue !== null && String(totalValue).trim() !== '')
        ? Number(totalValue)
        : null;

    // Agregar a la lista (usar las fotos tal como están, solo URLs blob temporales)
    const eppData = {
        id: productoSeleccionadoEPP.id,
        nombre_completo: productoSeleccionadoEPP.nombre_completo || productoSeleccionadoEPP.nombre,
        cantidad: parseInt(cantidad),
        observaciones: observaciones,
        valor_unitario: modoCotizacion ? valorUnitario : null,
        total: modoCotizacion ? total : null,
        imagenes: [...window.fotosEPP], // Mantener fotos con URLs blob (temporal)
        imagen: productoSeleccionadoEPP.imagen
    };

    eppAgregadosList.push(eppData);

    console.log(' EPP agregado a lista:', eppAgregadosList[eppAgregadosList.length - 1]);

    // Actualizar tabla
    renderizarTablaEPP();

    // Mostrar lista si hay items
    if (eppAgregadosList.length > 0) {
        const listaContainer = document.getElementById('listaEPPAgregados');
        listaContainer.style.display = 'block';
        console.log(' [agregarEPPALista] Lista mostrada. Display:', listaContainer.style.display);
        document.getElementById('btnFinalizarAgregarEPP').disabled = false;
    }

    // 🔑 IMPORTANTE: Limpiar completamente el formulario después de agregar a lista
    console.log('[agregarEPPALista] Limpiando modal después de agregar EPP...');
    
    // Resetear completamente el modal PERO manteniendo el estado del botón Finalizar
    const botonFinalizarEstado = document.getElementById('btnFinalizarAgregarEPP').disabled;
    
    resetearModalAgregarEPP();
    
    // Restaurar estado del botón Finalizar si hay EPPs
    if (eppAgregadosList.length > 0) {
        document.getElementById('btnFinalizarAgregarEPP').disabled = botonFinalizarEstado;
    }
    
    // Limpiar elementos específicos que no cubre resetearModalAgregarEPP
    document.getElementById('productoCardEPP').style.display = 'none';
    document.getElementById('formularioAgregarEPP').style.display = 'none';
    document.getElementById('observacionesContainer').style.display = 'none';
    
    // Limpiar buscador y desseleccionar producto
    document.getElementById('inputBuscadorEPP').value = '';
    document.getElementById('resultadosBuscadorEPP').style.display = 'none';
    productoSeleccionadoEPP = null;
    
    // Limpiar fotos del contenedor (ya están asociadas al EPP)
    limpiarFotosEPP();
    
    console.log('[agregarEPPALista] Modal limpiado completamente');
}

function limpiarFotosEPP() {
    console.log('🧹 [limpiarFotosEPP] Limpiando fotos del contenedor EPP');
    
    // NO liberar URLs blob si están asociadas a EPPs agregados
    // Las URLs blob se mantendrán para mostrar en las tarjetas
    // Solo limpiar el array temporal del contenedor
    window.fotosEPP = [];
    
    // Limpiar contenedor HTML (solo eliminar elementos de fotos, no el mensaje)
    const contenedor = document.getElementById('contenedorFotosEPP');
    if (contenedor) {
        // Eliminar solo los elementos de fotos (clase foto-epp-item)
        const elementosFoto = contenedor.querySelectorAll('.foto-epp-item');
        elementosFoto.forEach(elemento => elemento.remove());
        
        // Mostrar mensaje inicial
        const mensajeDragDrop = document.getElementById('mensajeDragDrop');
        if (mensajeDragDrop) {
            mensajeDragDrop.style.display = 'flex';
        }
        
        console.log('[limpiarFotosEPP] Contenedor limpiado y mensaje inicial restaurado');
    }
}

function renderizarTablaEPP() {
    console.log(' [renderizarTablaEPP] Iniciado. Total items:', eppAgregadosList.length);
    const tbody = document.getElementById('cuerpoTablaEPP');
    if (!tbody) {
        console.error(' [renderizarTablaEPP] tbody no encontrado');
        return;
    }
    
    tbody.innerHTML = '';

    eppAgregadosList.forEach((epp, idx) => {
        console.log(`📌 [renderizarTablaEPP] Renderizando EPP ${idx + 1}:`, epp.nombre_completo);
        const row = document.createElement('tr');
        row.className = idx % 2 === 0 ? 'bg-white' : 'bg-gray-50';
        
        // Generar miniaturas de fotos
        let fotosHtml = '';
        if (epp.imagenes && epp.imagenes.length > 0) {
            // Mostrar hasta 3 miniaturas
            const fotosMostrar = epp.imagenes.slice(0, 3);
            fotosHtml = fotosMostrar.map(foto => 
                `<img src="${foto.previewUrl || foto.base64}" alt="Foto EPP" class="w-8 h-8 object-cover rounded border border-gray-200" title="${foto.nombre}">`
            ).join(' ');
            
            // Si hay más de 3 fotos, mostrar indicador
            if (epp.imagenes.length > 3) {
                fotosHtml += `<span class="text-xs text-gray-500 ml-1">+${epp.imagenes.length - 3}</span>`;
            }
        } else {
            fotosHtml = '<span class="text-gray-400 text-xs">Sin fotos</span>';
        }
        
        row.innerHTML = `
            <td class="px-4 py-2">
                <div class="flex gap-1 items-center">
                    ${fotosHtml}
                </div>
            </td>
            <td class="px-4 py-2 text-gray-900 font-medium">${epp.nombre_completo}</td>
            <td class="px-4 py-2 text-gray-700">${epp.cantidad}</td>
            <td class="px-4 py-2 text-gray-700 text-xs">${epp.observaciones}</td>
            <td class="px-4 py-2 text-center">
                <button 
                    type="button"
                    onclick="event.stopPropagation(); eliminarEPPDeLista(${epp.id})"
                    class="text-red-600 hover:text-red-800 font-medium transition"
                >
                    <i class="material-symbols-rounded" style="font-size: 18px;">delete</i>
                </button>
            </td>
        `;
        tbody.appendChild(row);
    });
    
    console.log(' [renderizarTablaEPP] Completado. Filas renderizadas:', eppAgregadosList.length);
}

function resetearFormularioEPP() {
    document.getElementById('nombreProductoEPP').value = '';
    document.getElementById('cantidadEPP').disabled = true;
    document.getElementById('cantidadEPP').value = '1';
    document.getElementById('observacionesEPP').disabled = true;
    document.getElementById('observacionesEPP').value = '';
    document.getElementById('btnAgregarALista').disabled = true;

    // Limpiar fotos del EPP
    limpiarFotosEPP();

    actualizarEstilosCampos();
}

function actualizarEstilosCampos() {
    const cantidadInput = document.getElementById('cantidadEPP');
    const observacionesInput = document.getElementById('observacionesEPP');

    // Actualizar cantidad
    if (cantidadInput) {
        if (cantidadInput.disabled) {
            cantidadInput.classList.add('disabled');
        } else {
            cantidadInput.classList.remove('disabled');
        }
    }

    // Actualizar observaciones
    if (observacionesInput) {
        if (observacionesInput.disabled) {
            observacionesInput.classList.add('disabled');
        } else {
            observacionesInput.classList.remove('disabled');
        }
    }
}
 

// ====================================
// FUNCIONES PARA CREAR NUEVO EPP
// ====================================

function abrirFormularioCrearEPP() {
    document.getElementById('formularioCrearEPP').style.display = 'block';
    document.getElementById('nombreCompletNuevoEPP').focus();
}

function cerrarFormularioCrearEPP() {
    document.getElementById('formularioCrearEPP').style.display = 'none';
    document.getElementById('nombreCompletNuevoEPP').value = '';
}

async function guardarNuevoEPP() {
    const nombreCompleto = document.getElementById('nombreCompletNuevoEPP').value.trim();
    
    if (!nombreCompleto) {
        Swal.fire({
            icon: 'warning',
            title: 'Campo requerido',
            text: 'Por favor ingresa un nombre completo para el EPP',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            customClass: {
                container: 'toast-epp-container'
            }
        });
        return;
    }

    try {
        console.log('📤 [guardarNuevoEPP] Creando EPP con nombre:', nombreCompleto);
        
        // Crear el EPP en la base de datos
        const response = await fetch('/api/epp', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: JSON.stringify({
                nombre_completo: nombreCompleto,
                categoria_id: 19,  // Categoría fija como especificaste
                tipo: 'PRODUCTO',
                activo: true  // Usar boolean true en lugar de 1
            })
        });

        const resultado = await response.json();

        if (!response.ok) {
            console.error('[guardarNuevoEPP] Response error:', {
                status: response.status,
                resultado: resultado
            });
            throw new Error(resultado.message || `Error ${response.status}: ${response.statusText}`);
        }

        if (!resultado.success) {
            throw new Error(resultado.message || 'Error desconocido');
        }

        const nuevoEPP = resultado.data || resultado.epp;
        console.log(' [guardarNuevoEPP] EPP creado exitosamente:', nuevoEPP);

        // Mostrar el producto inmediatamente en el formulario
        mostrarProductoEPP({
            id: nuevoEPP.id,
            nombre_completo: nuevoEPP.nombre_completo,
            nombre: nuevoEPP.nombre_completo,
            imagen: '',
            tallas: []
        });

        // Cerrar el formulario de creación
        cerrarFormularioCrearEPP();
        
        // Mostrar notificación diferente si ya existía
        if (resultado.existia) {
            Swal.fire({
                icon: 'info',
                title: 'EPP existente',
                text: 'Este EPP ya existe. Se está utilizando el existente.',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                customClass: {
                    container: 'toast-epp-container'
                }
            });
        } else {
            Swal.fire({
                icon: 'success',
                title: 'EPP creado',
                text: 'EPP creado exitosamente. Ahora agrega la cantidad y observaciones.',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                customClass: {
                    container: 'toast-epp-container'
                }
            });
        }

    } catch (error) {
        console.error(' [guardarNuevoEPP] Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Error al crear el EPP: ' + error.message,
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 4000,
            customClass: {
                container: 'toast-epp-container'
            }
        });
    }
}

// ====================================
// FUNCIÓN PARA EDITAR EPP AGREGADO
// ====================================
// Inicializar en window para que sea accesible globalmente
if (!window.eppEnEdicion) {
    window.eppEnEdicion = null;  // Para guardar el índice del EPP que se está editando
}
let eppEnEdicion = null;  // Variable local para compatibilidad

function editarEPPAgregado(eppData) {
    console.log('✏️ [editarEPPAgregado] INICIANDO - Abriendo modal de edición para EPP:', eppData);
    
    // Usar el modal de edición individual que ya existe
    if (typeof abrirModalEditarEPP === 'function') {
        // Preparar datos en formato compatible
        const eppParaEditar = {
            id: eppData.epp_id || eppData.id,
            nombre: eppData.nombre_epp || eppData.nombre,
            nombre_completo: eppData.nombre_epp || eppData.nombre,
            cantidad: eppData.cantidad || 1,
            observaciones: eppData.observaciones || '-',
            imagenes: eppData.imagenes || [],
            tarjetaId: eppData.tarjetaId  // Pasar ID de la tarjeta visual
        };
        
        console.log('✏️ [editarEPPAgregado] Abriendo modal de edición con datos:', eppParaEditar);
        abrirModalEditarEPP(eppParaEditar);
    } else {
        console.error('✏️ [editarEPPAgregado] Función abrirModalEditarEPP no disponible');
    }
}

function guardarEdicionEPP() {
    console.log('💾 [guardarEdicionEPP] INICIANDO - window.eppEnEdicion:', window.eppEnEdicion);
    console.log('💾 [guardarEdicionEPP] Es objeto válido:', window.eppEnEdicion && typeof window.eppEnEdicion === 'object');
    
    if (!window.eppEnEdicion || typeof window.eppEnEdicion !== 'object' || Object.keys(window.eppEnEdicion).length === 0) {
        console.error('💾 [guardarEdicionEPP] ❌ No hay EPP válido en edición:', window.eppEnEdicion);
        return;
    }
    console.log('💾 [guardarEdicionEPP] ✓ EPP en edición válido');

    const nombre = document.getElementById('nombreProductoEPP').value;
    const cantidad = document.getElementById('cantidadEPP').value;
    const observaciones = document.getElementById('observacionesEPP').value;

    const modoCotizacion = !!window.__EPP_COTIZACION_MODE__;
    const vuRaw = modoCotizacion ? document.getElementById('valorUnitarioEPP')?.value : null;
    const vu = (vuRaw !== undefined && vuRaw !== null && String(vuRaw).trim() !== '' && !isNaN(Number(vuRaw)))
        ? Number(vuRaw)
        : null;
    const totalRaw = modoCotizacion ? document.getElementById('totalEPP')?.value : null;
    const total = (totalRaw !== undefined && totalRaw !== null && String(totalRaw).trim() !== '' && !isNaN(Number(totalRaw)))
        ? Number(totalRaw)
        : null;

    const imagenes = Array.isArray(window.fotosEPP) ? window.fotosEPP : [];

    if (!cantidad || cantidad <= 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Cantidad inválida',
            text: 'La cantidad debe ser mayor a 0',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            customClass: {
                container: 'toast-epp-container'
            }
        });
        return;
    }

    console.log('💾 [guardarEdicionEPP] Guardando cambios para EPP:', {
        epp_id: window.eppEnEdicion.epp_id,
        nombre: nombre,
        cantidad: cantidad,
        observaciones: observaciones
    });

    // Actualizar en store o window.itemsPedido
    const targetId = window.eppEnEdicion.epp_id || window.eppEnEdicion.id;
    const datosActualizados = {
        nombre_epp: nombre,
        nombre: nombre,
        cantidad: parseInt(cantidad),
        observaciones: observaciones || '-',
        imagenes: imagenes
    };
    if (modoCotizacion) {
        datosActualizados.valor_unitario = vu;
        datosActualizados.total = total;
    }

    if (window.eppStore) {
        const ok = window.eppStore.actualizarItem(targetId, datosActualizados);
        if (!ok) {
            console.warn('💾 [guardarEdicionEPP] No se encontró EPP en eppStore para actualizar');
        }
    } else {
        // Fallback sin store
        const index = window.itemsPedido.findIndex(item =>
            String(item.epp_id || item.id) === String(targetId)
        );
        if (index !== -1) {
            Object.assign(window.itemsPedido[index], datosActualizados);
            console.log(' [guardarEdicionEPP] EPP actualizado en window.itemsPedido:', window.itemsPedido[index]);
        } else {
            console.warn(' [guardarEdicionEPP] No se encontró EPP en window.itemsPedido para actualizar');
        }
    }

    // Actualizar visualmente en la tarjeta (usar el item manager correspondiente)
    const esVistaNuevo = window.location.pathname.includes('/crear-nuevo');
    
    if (esVistaNuevo) {
        // Vista de nuevo pedido - usar clases -nuevo
        if (window.eppItemManagerNuevo && typeof window.eppItemManagerNuevo.actualizarItem === 'function') {
            // Buscar la tarjeta usando el ID original para obtener el ID único
            const tarjeta = document.querySelector(`.item-epp-card-nuevo[data-epp-original-id="${targetId}"]`);
            const tarjetaId = tarjeta ? tarjeta.getAttribute('data-epp-id') : targetId;
            
            console.log('[guardarEdicionEPP] Actualizando tarjeta:', {
                targetId: targetId,
                tarjetaId: tarjetaId,
                tarjetaEncontrada: !!tarjeta
            });
            
            window.eppItemManagerNuevo.actualizarItem(tarjetaId, {
                nombre,
                cantidad: parseInt(cantidad),
                observaciones: observaciones || '-',
                imagenes,
                valor_unitario: undefined, // No hay valor unitario en nuevo pedido
                total: undefined, // No hay total en nuevo pedido
            });
        }
    } else {
        // Vista de cotización - usar clases genéricas
        if (window.eppItemManager && typeof window.eppItemManager.actualizarItem === 'function') {
            window.eppItemManager.actualizarItem(targetId, {
                nombre,
                cantidad: parseInt(cantidad),
                observaciones: observaciones || '-',
                imagenes,
                valor_unitario: modoCotizacion ? vu : undefined,
                total: modoCotizacion ? total : undefined,
            });
        }
    }

    // Limpiar referencia
    console.log('📖 [cerrarModalAgregarEPP] Limpiando window.eppEnEdicion');
    window.eppEnEdicion = null;
    
    // Restaurar botones a estado original
    const btnAgregar = document.getElementById('btnAgregarALista');
    const btnGuardarCambios = document.getElementById('btnGuardarCambiosEPP');
    const btnFinalizar = document.getElementById('btnFinalizarAgregarEPP');
    
    if (btnAgregar) {
        btnAgregar.style.display = 'none';
        console.log(' [guardarEdicionEPP] Botón agregar ocultado');
    }
    if (btnGuardarCambios) {
        btnGuardarCambios.style.display = 'none';
        btnGuardarCambios.disabled = true;
        console.log(' [guardarEdicionEPP] Botón guardar cambios ocultado');
    }
    if (btnFinalizar) {
        btnFinalizar.style.display = 'flex';
        btnFinalizar.disabled = true;
        console.log(' [guardarEdicionEPP] Botón finalizar restaurado');
    }
    
    // ⭐ Cerrar modal directamente sin confirmación (ya se guardó)
    cerrarModalAgregarEPPConfirmado();

    // Mostrar toast de éxito pequeño
    Swal.fire({
        icon: 'success',
        title: 'Guardado',
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 2500,
        timerProgressBar: false,
        customClass: {
            popup: 'swal2-toast-compact'
        },
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer)
            toast.addEventListener('mouseleave', Swal.resumeTimer)
        }
    });
    
    // Agregar estilos CSS para toast compacto si no existen
    if (!document.getElementById('swal2-toast-compact-style')) {
        const style = document.createElement('style');
        style.id = 'swal2-toast-compact-style';
        style.textContent = `
            .swal2-toast-compact {
                min-width: auto !important;
                width: auto !important;
                padding: 8px 12px !important;
                border-radius: 6px !important;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15) !important;
            }
            .swal2-toast-compact .swal2-title {
                font-size: 14px !important;
                margin: 0 !important;
            }
            .swal2-toast-compact .swal2-icon {
                width: 24px !important;
                height: 24px !important;
                margin-right: 8px !important;
            }
        `;
        document.head.appendChild(style);
    }
    
    console.log(' [guardarEdicionEPP] Edición completada');
}

// ====================================

async function finalizarAgregarEPP() {
    if (eppAgregadosList.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Sin EPPs',
            text: 'Por favor agrega al menos un EPP',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            customClass: {
                container: 'toast-epp-container'
            }
        });
        return;
    }

    // ==========================================
    // MODO EDICIÓN: Guardar directamente vía API
    // ==========================================
    const pedidoIdExistente = window.__EPP_AGREGAR_PEDIDO_EXISTENTE__;
    if (pedidoIdExistente) {
        console.log('[finalizarAgregarEPP] MODO EDICIÓN - Pidiendo novedad antes de guardar');
        
        // Pedir novedad/justificación del cambio
        // Inyectar CSS para z-index encima del modal EPP (z-index 9999999)
        let novedadStyle = document.getElementById('swal-epp-novedad-zindex');
        if (!novedadStyle) {
            novedadStyle = document.createElement('style');
            novedadStyle.id = 'swal-epp-novedad-zindex';
            novedadStyle.textContent = `
                .swal-epp-novedad-container {
                    z-index: 20000000 !important;
                    display: flex !important;
                    align-items: center !important;
                    justify-content: center !important;
                    position: fixed !important;
                }
                .swal-epp-novedad-container .swal2-popup {
                    margin: auto !important;
                }
            `;
            document.head.appendChild(novedadStyle);
        }

        const novedadResult = await Swal.fire({
            title: 'Novedad del cambio',
            input: 'textarea',
            inputLabel: '¿Por qué agregaste estos EPP?',
            inputPlaceholder: 'Describe brevemente el motivo...',
            inputAttributes: { 'aria-label': 'Novedad del cambio' },
            showCancelButton: true,
            confirmButtonText: '💾 Guardar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#10b981',
            customClass: { container: 'swal-epp-novedad-container' },
            inputValidator: (value) => {
                if (!value || !value.trim()) {
                    return 'Debes ingresar una novedad del cambio';
                }
            }
        });

        if (!novedadResult.isConfirmed) {
            console.log('[finalizarAgregarEPP] Usuario canceló la novedad');
            return;
        }

        const motivoUsuario = novedadResult.value.trim();
        // Construir novedad con nombres de todos los EPPs agregados
        const nombresEpp = eppAgregadosList.map(e => `"${e.nombre_completo || 'Sin nombre'}"`).join(', ');
        const novedad = `Agregó EPP: ${nombresEpp} - ${motivoUsuario}`;
        console.log('[finalizarAgregarEPP] MODO EDICIÓN - Guardando vía API para pedido:', pedidoIdExistente, 'novedad:', novedad);
        await _guardarEPPsViaAPI(pedidoIdExistente, novedad);
        return;
    }

    console.log(' [finalizarAgregarEPP] Finalizando con EPP:', eppAgregadosList);
    
    // Inicializar window.itemsPedido si no existe
    if (!window.itemsPedido) {
        window.itemsPedido = [];
    }
    
    // Convertir imágenes de todos los EPPs de forma asíncrona
    const promesasEPP = eppAgregadosList.map(async (epp) => {
        console.log(`📌 [finalizarAgregarEPP] Procesando EPP: ${epp.nombre_completo}`);
        
        // En modo edición O modo cotización, mantener blob URLs (NO convertir a base64)
        // Las referencias a los archivos se guardan globalmente para mantener las blob URLs válidas
        let imagenesParaGuardar;
        const esModoCotizacion = !!window.__EPP_COTIZACION_MODE__;
        const esModoEdicion = !!window.__EPP_MODO_EDICION__;
        
        if (esModoEdicion || esModoCotizacion) {
            console.log(`[finalizarAgregarEPP] Modo ${esModoCotizacion ? 'cotización' : 'edición'} - manteniendo blob URLs`);
            // Mantener las imágenes con blob URLs (los archivos están referenciados globalmente)
            imagenesParaGuardar = epp.imagenes || [];
        } else {
            // En modo creación de pedido, convertir URLs blob a archivos para guardado
            imagenesParaGuardar = await convertirBlobAArchivos(epp.imagenes);
        }
        
        // Usar el item manager correspondiente según la vista
        const esVistaNuevo = window.location.pathname.includes('/crear-nuevo');
        const modoCotizacion = !!window.__EPP_COTIZACION_MODE__;
        
        if (esVistaNuevo) {
            // Vista de nuevo pedido - usar clases -nuevo
            if (window.eppItemManagerNuevo && typeof window.eppItemManagerNuevo.crearItem === 'function') {
                window.eppItemManagerNuevo.crearItem(
                    epp.id,                    // id
                    epp.nombre_completo,        // nombre
                    'EPP',                     // categoria
                    epp.cantidad,              // cantidad
                    epp.observaciones,         // observaciones
                    imagenesParaGuardar,      // imagenes convertidas a archivos
                    null,
                    null, // No hay valor unitario en nuevo pedido
                    null  // No hay total en nuevo pedido
                );
                
                // Guardar referencias en cache global también para mantener blob URLs vivas
                if (imagenesParaGuardar && imagenesParaGuardar.length > 0) {
                    imagenesParaGuardar.forEach((imagen, idx) => {
                        if (imagen.previewUrl && imagen.previewUrl.startsWith('blob:')) {
                            _guardarArchivoEnCache(imagen.previewUrl, imagen.file || imagen);
                        }
                    });
                }
                
                console.log(` [finalizarAgregarEPP] EPP agregado a tarjeta (nuevo): ${epp.nombre_completo}`);
            } else {
                console.warn(' [finalizarAgregarEPP] eppItemManagerNuevo no disponible');
            }
        } else {
            // Vista de cotización - usar clases genéricas
            if (window.eppItemManager && typeof window.eppItemManager.crearItem === 'function') {
                window.eppItemManager.crearItem(
                    epp.id,                    // id
                    epp.nombre_completo,        // nombre
                    'EPP',                     // categoria
                    epp.cantidad,              // cantidad
                    epp.observaciones,         // observaciones
                    imagenesParaGuardar,      // imagenes convertidas a archivos
                    null,
                    modoCotizacion ? epp.valor_unitario : null,
                    modoCotizacion ? epp.total : null,
                    'epp'                      // tipo
                );
                
                // Guardar referencias en cache global también para mantener blob URLs vivas
                if (imagenesParaGuardar && imagenesParaGuardar.length > 0) {
                    imagenesParaGuardar.forEach((imagen, idx) => {
                        if (imagen.previewUrl && imagen.previewUrl.startsWith('blob:')) {
                            _guardarArchivoEnCache(imagen.previewUrl, imagen.file || imagen);
                        }
                    });
                }
                
                console.log(` [finalizarAgregarEPP] EPP agregado a tarjeta (cotización): ${epp.nombre_completo}`);
            } else {
                console.warn(' [finalizarAgregarEPP] eppItemManager no disponible');
            }
        }
        
        // También guardar en window.itemsPedido para que se envíe al servidor
        const eppData = {
            tipo: 'epp',
            epp_id: epp.id,
            nombre_epp: epp.nombre_completo,
            cantidad: epp.cantidad,
            observaciones: epp.observaciones,
            valor_unitario: modoCotizacion ? epp.valor_unitario : null,
            total: modoCotizacion ? epp.total : null,
            imagenes: imagenesParaGuardar // Usar siempre las imágenes procesadas (base64 en cotización/edición, rutas en creación)
        };
        
        return eppData;
    });
    
    // Esperar a que todas las conversiones terminen
    try {
        const eppsProcesados = await Promise.all(promesasEPP);
        
        // Agregar todos los EPPs procesados via eppStore o window.itemsPedido
        eppsProcesados.forEach((eppData) => {
            if (window.eppStore) {
                window.eppStore.agregarItem(eppData);
            } else {
                window.itemsPedido.push(eppData);
            }
            console.log(` [finalizarAgregarEPP] EPP guardado:`, eppData);
            
            //  CRÍTICO: También registrar en gestionItemsUI para mantener sincronizado
            if (window.gestionItemsUI && typeof window.gestionItemsUI.agregarEPPAlOrden === 'function') {
                window.gestionItemsUI.agregarEPPAlOrden(eppData);
                console.log(` [finalizarAgregarEPP] EPP registrado en gestionItemsUI:`, eppData.nombre_epp);
            } else {
                console.warn(' [finalizarAgregarEPP] gestionItemsUI no disponible');
            }
        });
        
        console.log(' [finalizarAgregarEPP] Todos los EPP han sido procesados y agregados');
        console.log(' [finalizarAgregarEPP] window.itemsPedido actual:', window.itemsPedido);
        
        // Limpiar lista de EPPs agregados ya que fueron guardados
        eppAgregadosList = [];
        window.eppAgregadosList = eppAgregadosList;
        console.log(' [finalizarAgregarEPP] Lista de EPPs agregados limpiada');
        
        // Recalcular totales (eppStore.onChange ya lo hace, esto es para vistas sin store)
        if (!window.eppStore && typeof window.syncTotales === 'function') {
            window.syncTotales();
            console.log('[finalizarAgregarEPP] Totales recalculados (fallback)');
        }
        
        // Cerrar modal
        cerrarModalAgregarEPP();
        
    } catch (error) {
        console.error('[finalizarAgregarEPP] Error procesando EPPs:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Ocurrió un error al procesar las imágenes. Por favor intenta nuevamente.',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 4000,
            customClass: {
                container: 'toast-epp-container'
            }
        });
    }
}

/**
 * Guardar EPPs directamente vía API cuando se edita un pedido existente
 * Se llama desde finalizarAgregarEPP() cuando __EPP_AGREGAR_PEDIDO_EXISTENTE__ está activo
 */
async function _guardarEPPsViaAPI(pedidoId, novedad = '') {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    let exitosos = 0;
    let errores = [];

    // Mostrar indicador de carga
    Swal.fire({
        title: 'Guardando EPPs...',
        text: `Guardando ${eppAgregadosList.length} EPP(s) en el pedido`,
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        didOpen: () => Swal.showLoading()
    });

    for (let idx = 0; idx < eppAgregadosList.length; idx++) {
        const epp = eppAgregadosList[idx];
        try {
            const formData = new FormData();
            formData.append('epp_id', epp.id);
            formData.append('cantidad', epp.cantidad || 1);
            formData.append('observaciones', epp.observaciones || '');
            // Solo enviar novedad en el PRIMER EPP para no duplicar
            if (novedad && idx === 0) {
                formData.append('novedad', novedad);
            }

            // Adjuntar imágenes como archivos si existen
            let imagenesAdjuntas = 0;
            if (epp.imagenes && epp.imagenes.length > 0) {
                for (let i = 0; i < epp.imagenes.length; i++) {
                    const img = epp.imagenes[i];
                    if (img.file instanceof File) {
                        formData.append(`imagenes[${i}]`, img.file, img.file.name);
                        imagenesAdjuntas++;
                    } else if (img.previewUrl && img.previewUrl.startsWith('blob:')) {
                        // Intentar obtener el archivo desde el cache global
                        const cachedFile = window._eppFilesCache?.[img.previewUrl];
                        if (cachedFile instanceof File) {
                            formData.append(`imagenes[${i}]`, cachedFile, cachedFile.name);
                            imagenesAdjuntas++;
                        } else {
                            console.warn(`[_guardarEPPsViaAPI] Imagen ${i} no es File válido, omitiendo`);
                        }
                    } else {
                        console.warn(`[_guardarEPPsViaAPI] Imagen ${i} tipo no reconocido:`, typeof img.file, img);
                    }
                }
            }

            // Debug: log de datos que se envían
            console.log(`[_guardarEPPsViaAPI] Enviando EPP "${epp.nombre_completo}" al pedido ${pedidoId}`, {
                epp_id: epp.id,
                cantidad: epp.cantidad || 1,
                observaciones: epp.observaciones || '',
                novedad: (novedad && idx === 0) ? novedad : '(no)',
                imagenes: imagenesAdjuntas,
                csrfToken: csrfToken ? 'presente' : 'AUSENTE'
            });

            const response = await fetch(`/api/pedidos/${pedidoId}/epp/agregar`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: formData
            });

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                console.error(`[_guardarEPPsViaAPI] Respuesta ${response.status}:`, errorData);
                // Mostrar campos con error si existen
                if (errorData.errors) {
                    const camposError = Object.entries(errorData.errors)
                        .map(([campo, msgs]) => `${campo}: ${Array.isArray(msgs) ? msgs.join(', ') : msgs}`)
                        .join('; ');
                    throw new Error(`${errorData.message || 'Error'}: ${camposError}`);
                }
                throw new Error(errorData.message || `Error HTTP ${response.status}`);
            }

            const resultado = await response.json();
            console.log(`[_guardarEPPsViaAPI] ✅ EPP "${epp.nombre_completo}" guardado:`, resultado);
            exitosos++;
        } catch (error) {
            console.error(`[_guardarEPPsViaAPI] ❌ Error guardando EPP "${epp.nombre_completo}":`, error);
            errores.push({ nombre: epp.nombre_completo, error: error.message });
        }
    }

    // Cerrar loading
    Swal.close();

    // Limpiar flag y lista
    window.__EPP_AGREGAR_PEDIDO_EXISTENTE__ = null;
    eppAgregadosList = [];
    window.eppAgregadosList = eppAgregadosList;

    // Cerrar modal
    cerrarModalAgregarEPPConfirmado();

    if (errores.length > 0) {
        // Si hubo errores pero también éxitos parciales, recargar datos
        if (exitosos > 0) {
            await _recargarYMostrarEPPs(pedidoId);
            Swal.fire({
                icon: 'warning',
                title: 'Guardado parcial',
                html: `Se guardaron ${exitosos} EPP(s) correctamente.<br>Errores: ${errores.map(e => e.nombre + ': ' + e.error).join('<br>')}`,
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error al guardar',
                html: `No se pudieron guardar los EPP(s).<br>Errores: ${errores.map(e => e.nombre + ': ' + e.error).join('<br>')}`,
            });
        }
    } else {
        // Todos exitosos - recargar datos y mostrar la lista actualizada en tiempo real
        await _recargarYMostrarEPPs(pedidoId);
        Swal.fire({
            icon: 'success',
            title: 'EPP(s) agregado(s)',
            text: `Se agregaron ${exitosos} EPP(s) al pedido correctamente`,
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000
        });
    }
}

/**
 * Recargar datos del pedido y re-abrir la vista de EPPs (actualización en tiempo real)
 */
async function _recargarYMostrarEPPs(pedidoId) {
    try {
        if (window.datosEdicionPedido && typeof window.abrirEditarEPP === 'function') {
            // Recargar datos del pedido vía fetch
            const pedidoResponse = await fetch(`/api/pedidos/${pedidoId}`, {
                headers: { 'Accept': 'application/json' }
            });
            if (pedidoResponse.ok) {
                const pedidoData = await pedidoResponse.json();
                if (pedidoData.epps || pedidoData.epps_transformados) {
                    window.datosEdicionPedido.epps = pedidoData.epps_transformados || pedidoData.epps || [];
                    console.log('[_recargarYMostrarEPPs] Datos del pedido actualizados con', window.datosEdicionPedido.epps.length, 'EPPs');
                }
            } else {
                console.warn('[_recargarYMostrarEPPs] Error al recargar pedido:', pedidoResponse.status);
            }
            // Re-abrir la vista de EPPs del pedido (actualización en tiempo real)
            abrirEditarEPP();
        }
    } catch (e) {
        console.warn('[_recargarYMostrarEPPs] No se pudieron recargar los datos del pedido:', e);
    }
}

// Cerrar modal al hacer clic fuera
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('modalAgregarEPP');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                cerrarModalAgregarEPP();
            }
        });
    }
});

// Exportar función globalmente para que sea accesible desde otros scripts
window.abrirModalAgregarEPP = abrirModalAgregarEPP;
window.cerrarModalAgregarEPP = cerrarModalAgregarEPP;
window.editarEPPAgregado = editarEPPAgregado;
window.guardarEdicionEPP = guardarEdicionEPP;

// Funciones para manejar fotos de EPP
window.fotosEPP = [];
window.fotosEPPEliminadas = [];

function agregarFotoEPP() {
    document.getElementById('inputFotosEPP').click();
}

function manejarSubidaFotosEPP(input) {
    const archivos = input.files;
    const pedidoId = window.pedidoIdActual || 31; // ID del pedido actual
    
    console.log(`📸 [manejarSubidaFotosEPP] Seleccionados ${archivos.length} archivos para el pedido ${pedidoId}`);
    
    Array.from(archivos).forEach((archivo, index) => {
        const nombreArchivo = archivo.name;
        const extension = nombreArchivo.split('.').pop().toLowerCase();
        
        // Validar que sea una imagen
        if (!['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'jfif'].includes(extension)) {
            console.warn(`[manejarSubidaFotosEPP] Archivo no válido: ${nombreArchivo}`);
            return;
        }
        
        // Crear URL blob para la imagen
        const previewUrl = URL.createObjectURL(archivo);
        
        // Guardar referencia al archivo para mantener la blob URL válida
        _guardarArchivoEnCache(previewUrl, archivo);
        
        // Crear objeto de imagen con URL blob
        const imagen = {
            id: Date.now() + '_' + index,
            file: archivo, // Mantener referencia al archivo original
            previewUrl: previewUrl, // URL blob para mostrar
            nombre: nombreArchivo,
            extension: extension,
            tamaño: archivo.size,
            pedido_epp_id: null, // Se asignará al guardar
            ruta_original: null,
            ruta_webp: null,
            principal: 0,
            orden: 0
        };
        
        // Agregar a la lista temporal
        window.fotosEPP.push(imagen);
        
        // Mostrar vista previa
        mostrarVistaPreviaFoto(imagen);
        
        console.log(`📸 [manejarSubidaFotosEPP] Foto agregada: ${nombreArchivo} (${(archivo.size / 1024).toFixed(2)} KB)`);
    });
    
    // Actualizar contador de fotos
    const contadorFotos = document.getElementById('contadorFotosEPP');
    if (contadorFotos) contadorFotos.textContent = window.fotosEPP.length;
    
    // Limpiar input para permitir seleccionar el mismo archivo nuevamente
    input.value = '';
}

function mostrarVistaPreviaFoto(imagen) {
    console.log('[mostrarVistaPreviaFoto] Iniciando con imagen:', imagen);
    
    const contenedor = document.getElementById('contenedorFotosEPP');
    console.log('[mostrarVistaPreviaFoto] Contenedor encontrado:', !!contenedor);
    
    // Ocultar mensaje inicial si está visible
    const mensajeDragDrop = document.getElementById('mensajeDragDrop');
    console.log('[mostrarVistaPreviaFoto] Mensaje drag-drop encontrado:', !!mensajeDragDrop);
    
    if (mensajeDragDrop) {
        mensajeDragDrop.style.display = 'none';
        console.log('[mostrarVistaPreviaFoto] Mensaje drag-drop ocultado');
    }
    
    // Crear elemento de imagen con atributo data-foto-id
    const divImagen = document.createElement('div');
    divImagen.className = 'relative group foto-epp-item';
    divImagen.setAttribute('data-foto-id', imagen.id);
    console.log('[mostrarVistaPreviaFoto] Div creado con ID:', imagen.id);
    
    divImagen.innerHTML = `
        <div class="relative overflow-hidden rounded-lg border-2 border-gray-200">
            <img src="${imagen.previewUrl}" alt="Foto EPP" class="w-full h-32 object-cover" 
                 onerror="this.onerror=null; this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTI4IiBoZWlnaHQ9IjEyOCIgdmlld0JveD0iMCAwIDEyOCAxMjgiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSIxMjgiIGhlaWdodD0iMTI4IiBmaWxsPSIjRjRGNEY2Ii8+CjxwYXRoIGQ9Ik00OCA0OEg4MFY4MEg0OFY0OFoiIHN0cm9rZT0iIzlDQTNBIiBzdHJva2Utd2lkdGg9IjIiIGZpbGw9Im5vbmUiLz4KPHN2ZyB4PSI0OCIgeT0iNDgiIHdpZHRoPSIzMiIgaGVpZ2h0PSIzMiIgdmlld0JveD0iMCAwIDI0IDI0IiBmaWxsPSJub25lIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciPgo8cGF0aCBkPSJNMTIgMkM2LjQ4IDIgMiA2LjQ4IDIgMTJTNi40OCAyMiAxMiAyMkMxNy41MiAyMiAyMiAxNy41MiAyMiAxMlMyMiA2LjQ4IDEyIDEyUzYuNDggMiAxMiAyWk0xMiA4QzEwLjkgOCA5IDguMSA5IDEwUzguMSAxMCAxMCAxMFMxMy45IDEwIDEzIDEwUzE0LjE4IDEwIDE0LjE4IDhTMTMuMSA2IDEyIDZaIiBmaWxsPSIjOUNDQTNBIi8+Cjwvc3ZnPgo8L3N2Zz4K'; this.style.opacity='0.5';" 
                 title="Imagen no disponible">
            <div class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                <button type="button" onclick="eliminarFotoEPPEdicion('${imagen.id}', event)" class="bg-red-500 text-white p-2 rounded-full hover:bg-red-600 transition" title="Eliminar imagen">
                    <i class="material-symbols-rounded text-base">delete</i>
                </button>
            </div>
            <div class="absolute bottom-0 right-0 bg-blue-600 text-white text-xs px-2 py-1 rounded-tl">
                ${imagen.nombre}
            </div>
        </div>
    `;
    
    console.log('[mostrarVistaPreviaFoto] HTML creado, agregando al contenedor...');
    contenedor.appendChild(divImagen);
    console.log('[mostrarVistaPreviaFoto] Imagen agregada al DOM. Total elementos en contenedor:', contenedor.children.length);
}


/**
 * Eliminar una foto del modo edición (contenedorFotosEPP / window.fotosEPP)
 */
function eliminarFotoEPPEdicion(fotoId, event) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    
    console.log('[eliminarFotoEPPEdicion] Eliminando foto con ID:', fotoId);
    
    // Eliminar del array window.fotosEPP
    if (window.fotosEPP && Array.isArray(window.fotosEPP)) {
        const idx = window.fotosEPP.findIndex(f => String(f.id) === String(fotoId));
        if (idx !== -1) {
            const foto = window.fotosEPP[idx];
            // Liberar blob URL si existe
            if (foto.previewUrl && foto.previewUrl.startsWith('blob:')) {
                URL.revokeObjectURL(foto.previewUrl);
            }
            window.fotosEPP.splice(idx, 1);
            console.log('[eliminarFotoEPPEdicion] Foto eliminada del array. Restantes:', window.fotosEPP.length);
        }
    }
    
    // Eliminar del DOM
    const contenedor = document.getElementById('contenedorFotosEPP');
    if (contenedor) {
        const elemento = contenedor.querySelector(`[data-foto-id="${fotoId}"]`);
        if (elemento) {
            elemento.remove();
            console.log('[eliminarFotoEPPEdicion] Elemento DOM eliminado');
        }
    }
    
    // Mostrar mensaje drag-drop si no quedan fotos
    if (!window.fotosEPP || window.fotosEPP.length === 0) {
        const mensajeDragDrop = document.getElementById('mensajeDragDrop');
        if (mensajeDragDrop) {
            mensajeDragDrop.style.display = 'flex';
        }
    }
    
    // Actualizar contador
    const contadorFotos = document.getElementById('contadorFotosEPP');
    if (contadorFotos) {
        contadorFotos.textContent = window.fotosEPP ? window.fotosEPP.length : 0;
    }
}
window.eliminarFotoEPPEdicion = eliminarFotoEPPEdicion;

// Funciones para Drag and Drop
function handleDragOverEPP(event) {
    event.preventDefault();
    event.stopPropagation();
    
    const contenedor = document.getElementById('contenedorFotosEPP');
    contenedor.classList.add('border-blue-400', 'bg-blue-50');
    contenedor.classList.remove('border-gray-300');
    
    // Ocultar mensaje inicial si hay imágenes
    if (window.fotosEPP.length > 0) {
        const mensajeDragDrop = document.getElementById('mensajeDragDrop');
        if (mensajeDragDrop) {
            mensajeDragDrop.style.display = 'none';
        }
    }
}

function handleDragLeaveEPP(event) {
    event.preventDefault();
    event.stopPropagation();
    
    const contenedor = document.getElementById('contenedorFotosEPP');
    contenedor.classList.remove('border-blue-400', 'bg-blue-50');
    contenedor.classList.add('border-gray-300');
    
    // Mostrar mensaje inicial si no hay imágenes
    if (window.fotosEPP.length === 0) {
        const mensajeDragDrop = document.getElementById('mensajeDragDrop');
        if (mensajeDragDrop) {
            mensajeDragDrop.style.display = 'flex';
        }
    }
}

function handleDropEPP(event) {
    event.preventDefault();
    event.stopPropagation();
    
    const contenedor = document.getElementById('contenedorFotosEPP');
    if (contenedor) {
        contenedor.classList.remove('border-blue-400', 'bg-blue-50');
        contenedor.classList.add('border-gray-300');
    }
    
    const archivos = event.dataTransfer.files;
    const pedidoId = window.pedidoIdActual || 31;
    
    console.log(`📸 [handleDropEPP] Se arrastraron ${archivos.length} archivos para el pedido ${pedidoId}`);
    
    // Ocultar mensaje inicial
    const mensajeDragDrop = document.getElementById('mensajeDragDrop');
    if (mensajeDragDrop) {
        mensajeDragDrop.style.display = 'none';
    }
    
    // Procesar cada archivo arrastrado
    Array.from(archivos).forEach((archivo, index) => {
        const nombreArchivo = archivo.name;
        const extension = nombreArchivo.split('.').pop().toLowerCase();
        
        // Validar que sea una imagen
        if (!['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'jfif'].includes(extension)) {
            console.warn(`[handleDropEPP] Archivo no válido: ${nombreArchivo}`);
            return;
        }
        
        // Crear URL blob para la imagen
        const previewUrl = URL.createObjectURL(archivo);
        
        // Crear objeto de imagen con URL blob
        const imagen = {
            id: Date.now() + '_' + index + '_drop',
            file: archivo, // Mantener referencia al archivo original
            previewUrl: previewUrl, // URL blob para mostrar
            nombre: nombreArchivo,
            extension: extension,
            tamaño: archivo.size,
            pedido_epp_id: null,
            ruta_original: null,
            ruta_webp: null,
            principal: 0,
            orden: 0
        };
        
        // Agregar a la lista temporal
        window.fotosEPP.push(imagen);
        
        // Mostrar vista previa
        mostrarVistaPreviaFoto(imagen);
        
        console.log(`[handleDropEPP] Foto arrastrada: ${nombreArchivo} (${(archivo.size / 1024).toFixed(2)} KB)`);
    });
    
    // Actualizar contador de fotos
    const contadorFotosD = document.getElementById('contadorFotosEPP');
    if (contadorFotosD) contadorFotosD.textContent = window.fotosEPP.length;
}
</script>

<script>
/**
 * Función exclusiva para editar EPP en vista de nuevo pedido
 * Evita conflictos con el sistema de cotización
 */
function abrirModalEditarEPPNuevo(epp) {
    console.log('[abrirModalEditarEPPNuevo] Iniciando edición para:', epp);
    console.log('[abrirModalEditarEPPNuevo] EPP recibido:', JSON.stringify(epp, null, 2));
    
    // Abrir modal
    const modal = document.getElementById('modalAgregarEPP');
    console.log('[abrirModalEditarEPPNuevo] Modal encontrado:', !!modal);
    
    if (!modal) {
        console.error('[abrirModalEditarEPPNuevo] Modal no encontrado');
        return;
    }
    
    // Resetear modal primero
    console.log('[abrirModalEditarEPPNuevo] Resetear modal...');
    resetearModalAgregarEPP();
    
    // Modo edición
    window.__EPP_MODO_EDICION__ = true;
    window.__EPP_EDICION_ID__ = epp.epp_id || epp.id;
    console.log('[abrirModalEditarEPPNuevo] Modo edición establecido:', {
        '__EPP_MODO_EDICION__': window.__EPP_MODO_EDICION__,
        '__EPP_EDICION_ID__': window.__EPP_EDICION_ID__
    });
    
    // Cambiar título del modal
    const titulo = modal.querySelector('.modal-header h3');
    console.log('[abrirModalEditarEPPNuevo] Título encontrado:', !!titulo);
    
    if (titulo) {
        titulo.textContent = 'Editar EPP';
        console.log('[abrirModalEditarEPPNuevo] Título cambiado a: Editar EPP');
    }
    
    // Ocultar sección de "EPP Agregados" en modo edición
    const listaEPPAgregados = document.getElementById('listaEPPAgregados');
    console.log('[abrirModalEditarEPPNuevo] Lista EPP Agregados encontrada:', !!listaEPPAgregados);
    
    if (listaEPPAgregados) {
        listaEPPAgregados.style.display = 'none';
        console.log('[abrirModalEditarEPPNuevo] Lista EPP Agregados oculta');
    }
    
    // Ocultar buscador en modo edición
    const buscadorSection = document.getElementById('buscadorEPPSection');
    if (buscadorSection) {
        buscadorSection.style.display = 'none';
        console.log('[abrirModalEditarEPPNuevo] Buscador ocultado en modo edición');
    }
    const formularioCrearEPPEl = document.getElementById('formularioCrearEPP');
    if (formularioCrearEPPEl) {
        formularioCrearEPPEl.style.display = 'none';
    }
    
    // Mostrar sección de fotos
    const seccionFotosEPPEl = document.getElementById('seccionFotosEPP');
    if (seccionFotosEPPEl) {
        seccionFotosEPPEl.style.display = 'block';
    }
    
    // Cargar datos del EPP
    mostrarProductoEPP({
        id: epp.epp_id || epp.id,
        nombre_completo: epp.nombre_epp || epp.nombre,
        nombre: epp.nombre_epp || epp.nombre,
        imagen: epp.imagen || '',
        tallas: epp.tallas || []
    });
    
    // Cargar cantidad y observaciones
    const cantidadInput = document.getElementById('cantidadEPP');
    const observacionesInput = document.getElementById('observacionesEPP');
    
    if (cantidadInput) {
        cantidadInput.value = epp.cantidad || 1;
    }
    if (observacionesInput) {
        observacionesInput.value = epp.observaciones || '-';
    }
    
    // Establecer variable eppEnEdicion para que guardarEdicionEPP funcione
    window.eppEnEdicion = epp;
    console.log('🔄 [editarEPPAgregado - cotización] window.eppEnEdicion asignado:', epp);
    console.log('[abrirModalEditarEPPNuevo] eppEnEdicion establecido:', epp);
    
    // Cargar imágenes si existen
    console.log('[abrirModalEditarEPPNuevo] Verificando imágenes del EPP:', {
        tieneImagenes: !!(epp.imagenes && Array.isArray(epp.imagenes)),
        cantidadImagenes: epp.imagenes?.length || 0,
        imagenes: epp.imagenes
    });
    
    // Limpiar contenedor de imágenes antes de cargar nuevas en modo edición
    const contenedor = document.getElementById('contenedorFotosEPP');
    if (contenedor) {
        // Limpiar todas las imágenes existentes excepto el mensaje inicial
        const imagenesExistentes = contenedor.querySelectorAll('.foto-epp-item');
        imagenesExistentes.forEach(img => img.remove());
        
        // Restaurar mensaje inicial
        const mensajeDragDrop = document.getElementById('mensajeDragDrop');
        if (mensajeDragDrop) {
            mensajeDragDrop.style.display = 'flex';
        }
        
        console.log('[abrirModalEditarEPPNuevo] Contenedor de imágenes limpiado');
    }
    
    if (epp.imagenes && Array.isArray(epp.imagenes)) {
        window.fotosEPP = [];
        console.log('[abrirModalEditarEPPNuevo] Iniciando carga de imágenes...');
        
        epp.imagenes.forEach((img, index) => {
            console.log(`[abrirModalEditarEPPNuevo] Procesando imagen ${index + 1}:`, img);
            
            // Convertir imágenes existentes al formato esperado
            const imagenObj = {
                id: img.id || Date.now(),
                previewUrl: img.previewUrl || img.ruta_web || img.url || img.base64, // Priorizar blob URL
                nombre: img.nombre || 'imagen.jpg',
                file: null, // No hay archivo original en edición
                extension: (img.nombre || '').split('.').pop().toLowerCase() || 'jpg',
                tamaño: img.tamaño || 0,
                pedido_epp_id: epp.pedido_epp_id || null,
                ruta_original: img.ruta_original || null,
                ruta_webp: img.ruta_webp || null,
                principal: img.principal || 0,
                orden: img.orden || 0
            };
            
            console.log(`[abrirModalEditarEPPNuevo] ImagenObj creado:`, imagenObj);
            
            // Verificar si la URL es válida antes de agregar
            if (imagenObj.previewUrl) {
                // Permitir imágenes blob URLs y URLs que no sean temporales
                if (imagenObj.previewUrl.startsWith('blob:') || !imagenObj.previewUrl.includes('temp/epp/')) {
                    window.fotosEPP.push(imagenObj);
                    console.log(`[abrirModalEditarEPPNuevo] Imagen agregada a window.fotosEPP: ${imagenObj.previewUrl}`);
                    console.log(`[abrirModalEditarEPPNuevo] Total imágenes en window.fotosEPP: ${window.fotosEPP.length}`);
                    
                    mostrarVistaPreviaFoto(imagenObj);
                    console.log(`[abrirModalEditarEPPNuevo] mostrarVistaPreviaFoto llamado para imagen ${index + 1}`);
                } else if (imagenObj.previewUrl.includes('temp/epp/')) {
                    // Para imágenes temporales, mostrar warning y skip
                    console.warn('[abrirModalEditarEPPNuevo] Imagen temporal no disponible:', imagenObj.previewUrl);
                }
            } else {
                console.warn(`[abrirModalEditarEPPNuevo] Imagen sin previewUrl:`, img);
            }
        });
        
        console.log(`[abrirModalEditarEPPNuevo] Proceso de imágenes completado. Total en window.fotosEPP: ${window.fotosEPP.length}`);
    } else {
        console.log('[abrirModalEditarEPPNuevo] No hay imágenes para cargar');
    }
    
    // Ocultar botones de agregar/finalizar
    const btnAgregar = document.getElementById('btnAgregarALista');
    const btnFinalizar = document.getElementById('btnFinalizarAgregarEPP');
    const btnGuardarCambios = document.getElementById('btnGuardarCambiosEPP');
    
    if (btnAgregar) btnAgregar.style.display = 'none';
    if (btnFinalizar) btnFinalizar.style.display = 'none';
    if (btnGuardarCambios) {
        btnGuardarCambios.style.display = 'block';
        btnGuardarCambios.disabled = false; // Habilitar botón en modo edición
        console.log('[abrirModalEditarEPPNuevo] Botón Guardar Cambios habilitado');
    }
    
    // Mostrar modal
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    
    console.log('[abrirModalEditarEPPNuevo] Modal abierto para edición');
}
</script>

<script>
// ========== FUNCIONES PARA MODAL DE SELECCIÓN ==========

function abrirModalSeleccion() {
    console.log('[abrirModalSeleccion] Abriendo modal de selección de tipo');
    const modal = document.getElementById('modalSeleccionTipo');
    if (modal) {
        modal.style.display = 'flex';
    }
}

function cerrarModalSeleccion() {
    console.log('[cerrarModalSeleccion] Cerrando modal de selección de tipo');
    const modal = document.getElementById('modalSeleccionTipo');
    if (modal) {
        modal.style.display = 'none';
    }
}

function seleccionarTipoProducto(tipo) {
    console.log('[seleccionarTipoProducto] Tipo seleccionado:', tipo);
    cerrarModalSeleccion();
    
    if (tipo === 'prenda') {
        abrirModalAgregarPrenda();
    } else if (tipo === 'epp') {
        abrirModalAgregarEPP();
    }
}

// ========== FUNCIONES PARA MODAL DE PRENDA ==========

function abrirModalAgregarPrenda() {
    console.log('[abrirModalAgregarPrenda] Abriendo modal de prenda');
    const modal = document.getElementById('modalAgregarPrenda');
    if (modal) {
        // Limpiar formulario
        document.getElementById('descripcionPrenda').value = '';
        document.getElementById('cantidadPrenda').value = '1';
        document.getElementById('valorUnitarioPrenda').value = '';
        document.getElementById('totalPrenda').value = '0';
        document.getElementById('observacionesPrenda').value = '';
        limpiarFotosPrenda();
        
        modal.style.display = 'flex';
        
        // Registrar listener de paste después de un pequeño delay
        setTimeout(() => {
            document.addEventListener('paste', window.handlePastePrenda);
            console.log('[abrirModalAgregarPrenda] Paste listener registrado');
        }, 100);
    }
}

function cerrarModalAgregarPrenda() {
    console.log('[cerrarModalAgregarPrenda] Cerrando modal de prenda');
    const modal = document.getElementById('modalAgregarPrenda');
    if (modal) {
        modal.style.display = 'none';
        
        // Remover listener de paste
        document.removeEventListener('paste', window.handlePastePrenda);
        console.log('[cerrarModalAgregarPrenda] Paste listener removido');
    }
    
    // Limpiar referencias de edición
    window.prendaEnEdicion = null;
    window.eppEnEdicion = null;
    console.log('[cerrarModalAgregarPrenda] Referencias de edición limpiadas');
}

function actualizarTotalPrenda() {
    const cantidad = parseFloat(document.getElementById('cantidadPrenda').value) || 0;
    const valorUnitario = parseFloat(document.getElementById('valorUnitarioPrenda').value) || 0;
    const total = cantidad * valorUnitario;
    document.getElementById('totalPrenda').value = total.toFixed(2);
}

function agregarFotoPrenda() {
    console.log('[agregarFotoPrenda] Abriendo selector de archivos');
    const input = document.getElementById('inputFotosPrenda');
    if (input) {
        input.click();
    }
}

function manejarSubidaFotosPrenda(input) {
    console.log('[manejarSubidaFotosPrenda] Archivos seleccionados:', input.files.length);
    
    const archivos = Array.from(input.files);
    const contenedor = document.getElementById('contenedorFotosPrenda');
    const mensaje = document.getElementById('mensajeDragDropPrenda');
    
    archivos.forEach((archivo, index) => {
        const reader = new FileReader();
        reader.onload = (e) => {
            const div = document.createElement('div');
            div.className = 'relative group rounded-lg overflow-hidden';
            div.innerHTML = `
                <img src="${e.target.result}" class="w-full h-24 object-cover border border-gray-300 rounded-lg cursor-pointer" 
                    onclick="mostrarVistaPreviaFotoPrenda(this.src)">
                <button type="button" class="absolute top-1 right-1 bg-red-600 text-white p-1 rounded opacity-0 group-hover:opacity-100 transition" 
                    onclick="this.parentElement.remove(); validarBotonesPrenda()">
                    <i class="material-symbols-rounded" style="font-size: 16px;">close</i>
                </button>
            `;
            contenedor.appendChild(div);
            if (mensaje) mensaje.style.display = 'none';
            validarBotonesPrenda();
        };
        reader.readAsDataURL(archivo);
    });
    
    input.value = '';
}

function limpiarFotosPrenda() {
    const contenedor = document.getElementById('contenedorFotosPrenda');
    const mensaje = document.getElementById('mensajeDragDropPrenda');
    
    if (contenedor) {
        const fotos = contenedor.querySelectorAll('div.relative');
        fotos.forEach(foto => foto.remove());
    }
    
    if (mensaje) mensaje.style.display = 'flex';
}

function mostrarVistaPreviaFotoPrenda(src) {
    console.log('[mostrarVistaPreviaFotoPrenda] Mostrando vista previa');
    // Implementar modal de vista previa si es necesario
}

function handleDropPrenda(e) {
    e.preventDefault();
    e.stopPropagation();
    
    const contenedor = e.currentTarget;
    contenedor.style.borderColor = '#ccc';
    
    if (e.dataTransfer.files.length > 0) {
        const input = document.getElementById('inputFotosPrenda');
        input.files = e.dataTransfer.files;
        manejarSubidaFotosPrenda(input);
    }
}

function handleDragOverPrenda(e) {
    e.preventDefault();
    e.currentTarget.style.borderColor = '#3b82f6';
    e.currentTarget.style.backgroundColor = '#eff6ff';
}

function handleDragLeavePrenda(e) {
    e.currentTarget.style.borderColor = '#d1d5db';
    e.currentTarget.style.backgroundColor = 'transparent';
}

function validarBotonesPrenda() {
    const descripcion = document.getElementById('descripcionPrenda').value.trim();
    const cantidad = parseInt(document.getElementById('cantidadPrenda').value) || 0;
    const valido = descripcion.length > 0 && cantidad > 0;
    
    const btnFinalizar = document.getElementById('btnFinalizarAgregarPrenda');
    if (btnFinalizar) {
        btnFinalizar.disabled = !valido;
    }
}

function finalizarAgregarPrenda() {
    console.log('[finalizarAgregarPrenda] Finalizando agregación de prenda');
    
    // Verificar si estamos en modo edición
    const enModoEdicion = !!(window.eppEnEdicion && typeof window.eppEnEdicion === 'object' && Object.keys(window.eppEnEdicion).length > 0);
    const prendaEnEdicion = enModoEdicion ? window.eppEnEdicion : null;
    
    if (enModoEdicion) {
        console.log('[finalizarAgregarPrenda] 📝 MODO EDICIÓN detectado:', prendaEnEdicion);
    } else {
        console.log('[finalizarAgregarPrenda] ➕ MODO CREACIÓN');
    }
    
    const descripcion = document.getElementById('descripcionPrenda').value.trim();
    const cantidad = parseInt(document.getElementById('cantidadPrenda').value) || 1;
    const valorUnitario = parseFloat(document.getElementById('valorUnitarioPrenda').value) || 0;
    const total = parseFloat(document.getElementById('totalPrenda').value) || 0;
    const observaciones = document.getElementById('observacionesPrenda').value.trim();
    
    if (!descripcion) {
        alert('Por favor ingresa la descripción de la prenda');
        return;
    }
    
    // Obtener imágenes
    const fotos = [];
    const imagenes = document.querySelectorAll('#contenedorFotosPrenda img');
    imagenes.forEach(img => {
        fotos.push(img.src);
    });
    
    // Crear objeto de prenda
    const prenda = {
        tipo: 'prenda',
        id: Date.now(),
        descripcion: descripcion,
        cantidad: cantidad,
        valorUnitario: valorUnitario,
        total: total,
        observaciones: observaciones,
        imagenes: fotos
    };
    
    console.log('[finalizarAgregarPrenda] Prenda creada:', prenda);
    
    // Agregar a ventana global para uso posterior
    if (!window.prendasAgregadas) {
        window.prendasAgregadas = [];
    }
    
    // Si estamos en modo edición, actualizar la prenda existente en prendasAgregadas
    if (enModoEdicion) {
        const targetId = prendaEnEdicion.id || prendaEnEdicion.prenda_id || prendaEnEdicion.epp_id;
        const indexPrendas = window.prendasAgregadas.findIndex(p => 
            String(p.id) === String(targetId) || String(p.prenda_id) === String(targetId)
        );
        if (indexPrendas !== -1) {
            window.prendasAgregadas[indexPrendas] = prenda;
            console.log('[finalizarAgregarPrenda] Prenda actualizada en prendasAgregadas');
        }
    } else {
        window.prendasAgregadas.push(prenda);
    }
    
    // Agregar a window.itemsPedido para envío
    if (!window.itemsPedido) {
        window.itemsPedido = [];
    }
    
    const prendaData = {
        tipo: 'prenda',
        id: prenda.id,
        nombre_epp: descripcion,  // Usar mismo field que EPP para compatibilidad
        cantidad: cantidad,
        observaciones: observaciones || '-',
        valor_unitario: valorUnitario,
        total: total,
        imagenes: fotos
    };
    
    // Si estamos en modo edición, NO agregar a window.itemsPedido de nuevo
    // Solo actualizar el existente
    if (!enModoEdicion) {
        window.itemsPedido.push(prendaData);
        console.log('[finalizarAgregarPrenda] Prenda agregada a window.itemsPedido');
    } else {
        // Actualizar en window.itemsPedido si existe
        const targetId = prendaEnEdicion.id || prendaEnEdicion.prenda_id || prendaEnEdicion.epp_id;
        const index = window.itemsPedido.findIndex(item => 
            String(item.id) === String(targetId) || String(item.epp_id) === String(targetId)
        );
        if (index !== -1) {
            window.itemsPedido[index].nombre_epp = descripcion;
            window.itemsPedido[index].nombre = descripcion;
            window.itemsPedido[index].cantidad = cantidad;
            window.itemsPedido[index].observaciones = observaciones || '-';
            window.itemsPedido[index].valor_unitario = valorUnitario;
            window.itemsPedido[index].total = total;
            window.itemsPedido[index].imagenes = fotos;
            console.log('[finalizarAgregarPrenda] Prenda actualizada en window.itemsPedido:', window.itemsPedido[index]);
        }
    }
    
    // Renderizar en la tabla principal usando eppItemManager
    if (window.eppItemManager && typeof window.eppItemManager.crearItem === 'function') {
        if (enModoEdicion) {
            // MODO EDICIÓN: Actualizar item existente
            console.log('[finalizarAgregarPrenda] 📝 ACTUALIZANDO prenda en tabla principal');
            const targetId = prendaEnEdicion.id || prendaEnEdicion.prenda_id || prendaEnEdicion.epp_id;
            
            if (typeof window.eppItemManager.actualizarItem === 'function') {
                window.eppItemManager.actualizarItem(targetId, {
                    nombre: descripcion,
                    cantidad: cantidad,
                    observaciones: observaciones || '-',
                    valor_unitario: valorUnitario,
                    total: total,
                    imagenes: fotos.map((src, idx) => ({
                        previewUrl: src,
                        base64: src,
                        nombre: `prenda_${prenda.id}_${idx}`
                    }))
                });
                console.log('[finalizarAgregarPrenda] Prenda actualizada correctamente');
            } else {
                console.warn('[finalizarAgregarPrenda] actualizarItem no disponible');
            }
        } else {
            // MODO CREACIÓN: Crear nuevo item
            console.log('[finalizarAgregarPrenda] ➕ Creando nueva prenda en tabla principal');
            window.eppItemManager.crearItem(
                prenda.id,                // id
                descripcion,               // nombre
                'prenda',                  // categoria
                cantidad,                  // cantidad
                observaciones || '-',      // observaciones
                fotos.map((src, idx) => ({  // imagenes
                    previewUrl: src,
                    base64: src,
                    nombre: `prenda_${prenda.id}_${idx}`
                })),
                prenda.id,                 // pedidoEppId
                valorUnitario,             // valorUnitario
                total,                     // total
                'prenda'                   // tipo
            );
        }
    } else {
        console.warn('[finalizarAgregarPrenda] eppItemManager no disponible');
    }
    
    // Registrar en gestionItemsUI si está disponible
    if (window.gestionItemsUI && typeof window.gestionItemsUI.agregarEPPAlOrden === 'function') {
        window.gestionItemsUI.agregarEPPAlOrden(prendaData);
        console.log('[finalizarAgregarPrenda] Prenda registrada en gestionItemsUI');
    }
    
    // Recalcular totales después de agregar la prenda
    if (typeof window.syncTotales === 'function') {
        window.syncTotales();
        console.log('[finalizarAgregarPrenda] Totales recalculados');
    }
    
    // NOTA: El guardado en BD se realiza cuando se envía la cotización completa (junto con EPPs)
    // No se intenta guardar la prenda individualmente aquí
    
    cerrarModalAgregarPrenda();
}

/**
 * Guardar prenda en la base de datos
 */
function guardarPrendaEnBD(prendaData) {
    const cotizacionId = document.querySelector('[data-cotizacion-id]')?.getAttribute('data-cotizacion-id') 
        || new URLSearchParams(window.location.search).get('id')
        || window.__COTIZACION_ID__;
    
    if (!cotizacionId) {
        console.warn('[guardarPrendaEnBD] No se puede obtener el ID de cotización');
        return;
    }
    
    const datos = {
        cotizacion_id: cotizacionId,
        descripcion: prendaData.nombre_epp,
        cantidad: prendaData.cantidad,
        observaciones: prendaData.observaciones,
        valor_unitario: prendaData.valor_unitario,
        imagenes: prendaData.imagenes
    };
    
    console.log('[guardarPrendaEnBD] Intentando guardar prenda:', datos);
    
    fetch('/api/cotizacion/prendas', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
        },
        body: JSON.stringify(datos)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('[guardarPrendaEnBD] Prenda guardada exitosamente:', data);
            if (window.mostrarNotificacion) {
                window.mostrarNotificacion('Prenda guardada correctamente', 'success');
            }
        } else {
            console.error('[guardarPrendaEnBD] Error:', data.error);
            if (window.mostrarNotificacion) {
                window.mostrarNotificacion('Error al guardar prenda: ' + data.error, 'error');
            }
        }
    })
    .catch(error => {
        console.error('[guardarPrendaEnBD] Error de conexión:', error);
        if (window.mostrarNotificacion) {
            window.mostrarNotificacion('Error de conexión: ' + error.message, 'error');
        }
    });
}

// Observadores de cambios en el formulario de prenda
document.addEventListener('DOMContentLoaded', function() {
    const descripcionPrenda = document.getElementById('descripcionPrenda');
    const cantidadPrenda = document.getElementById('cantidadPrenda');
    
    if (descripcionPrenda) {
        descripcionPrenda.addEventListener('input', validarBotonesPrenda);
    }
    
    if (cantidadPrenda) {
        cantidadPrenda.addEventListener('input', validarBotonesPrenda);
    }
});
</script>

<style>
    /* Asegurar que los toasts EPP aparezcan encima de todo */
    .toast-epp-container {
        z-index: 10000001 !important;
    }
    
    /* Asegurar que Swal2 dialogs aparezcan encima del modal */
    .swal2-container {
        z-index: 10000001 !important;
    }
    
    .swal2-popup {
        z-index: 10000001 !important;
    }
</style>

<script>
// Agregar listener para detectar clicks en el backdrop (fondo oscuro)
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('modalAgregarEPP');
    if (!modal) {
        console.warn('[Modal EPP] Elemento modal no encontrado');
        return;
    }
    
    // Listener en el backdrop (el div con fixed inset-0)
    modal.addEventListener('click', function(event) {
        // Verificar si el click fue en el backdrop, no en el contenedor blanco
        const contenedorBlanco = modal.querySelector('.bg-white');
        if (contenedorBlanco && !contenedorBlanco.contains(event.target)) {
            console.log('[Modal EPP] Click detectado en backdrop');
            cerrarModalAgregarEPP();
        }
    });
    
    console.log('[Modal EPP] Listener de backdrop agregado');
});
</script>

<script>
// Exportar funciones necesarias al objeto window para que estén disponibles globalmente
window.abrirModalSeleccion = abrirModalSeleccion;
window.cerrarModalSeleccion = cerrarModalSeleccion;
window.seleccionarTipoProducto = seleccionarTipoProducto;
window.abrirModalAgregarPrenda = abrirModalAgregarPrenda;
window.cerrarModalAgregarPrenda = cerrarModalAgregarPrenda;
window.actualizarTotalPrenda = actualizarTotalPrenda;
window.agregarFotoPrenda = agregarFotoPrenda;
window.manejarSubidaFotosPrenda = manejarSubidaFotosPrenda;
window.limpiarFotosPrenda = limpiarFotosPrenda;
window.mostrarVistaPreviaFotoPrenda = mostrarVistaPreviaFotoPrenda;
window.handleDropPrenda = handleDropPrenda;
window.handleDragOverPrenda = handleDragOverPrenda;
window.handleDragLeavePrenda = handleDragLeavePrenda;
window.validarBotonesPrenda = validarBotonesPrenda;
window.finalizarAgregarPrenda = finalizarAgregarPrenda;

// Funciones nuevas de selección múltiple con dropdown
window.cargarEPPDisponibles = cargarEPPDisponibles;
window.mostrarDropdownEPP = mostrarDropdownEPP;
window.renderizarDropdownEPP = renderizarDropdownEPP;
window.filtrarDropdownEPP = filtrarDropdownEPP;
window.agregarEPPDesdeDropdown = agregarEPPDesdeDropdown;
window.actualizarSeleccionEPP = actualizarSeleccionEPP;
window.renderizarTablaEPPAgregados = renderizarTablaEPPAgregados;
window.actualizarCantidadEPP = actualizarCantidadEPP;
window.actualizarValorUnitarioEPP = actualizarValorUnitarioEPP;
window.actualizarObservacionesEPP = actualizarObservacionesEPP;
window.eliminarEPPDeLista = eliminarEPPDeLista;
window.obtenerEPPsYaAgregadosEnFormulario = obtenerEPPsYaAgregadosEnFormulario;

// Funciones de manejo de fotos en tabla EPP
window.manejarSeleccionFotosEPP = manejarSeleccionFotosEPP;
window.manejarDragOverFotosEPP = manejarDragOverFotosEPP;
window.manejarDragLeaveFotosEPP = manejarDragLeaveFotosEPP;
window.manejarDropFotosEPP = manejarDropFotosEPP;
window.eliminarFotoEPP = eliminarFotoEPP;

// Funciones principales del modal
window.abrirModalAgregarEPP = abrirModalAgregarEPP;
window.abrirModalEditarEPPNuevo = abrirModalEditarEPPNuevo;
window.resetearModalAgregarEPP = resetearModalAgregarEPP;
window.cerrarModalAgregarEPP = cerrarModalAgregarEPP;
window.cerrarModalAgregarEPPConfirmado = cerrarModalAgregarEPPConfirmado;
window.hayDatosNoGuardados = hayDatosNoGuardados;
window.mostrarProductoEPP = mostrarProductoEPP;
window.agregarEPPALista = agregarEPPALista;
window.finalizarAgregarEPP = finalizarAgregarEPP;
window.guardarEdicionEPP = guardarEdicionEPP;
window.filtrarEPPBuscador = filtrarEPPBuscador;
window.agregarFotoEPP = agregarFotoEPP;
window.mostrarVistaPreviaFoto = mostrarVistaPreviaFoto;
window.limpiarImagenesTemporales = limpiarImagenesTemporales;

console.log('[EPP Modal] Todas las funciones exportadas al objeto window');

// ========== MANEJADOR DE CTRL+V PARA PRENDAS ==========
function handlePastePrenda(event) {
    console.log('[handlePastePrenda] Paste detectado');
    
    if (!event.clipboardData || !event.clipboardData.items) {
        console.warn('[handlePastePrenda] No hay datos en el clipboard');
        return;
    }
    
    const items = event.clipboardData.items;
    const archivos = [];
    
    for (let i = 0; i < items.length; i++) {
        if (items[i].kind === 'file' && items[i].type.startsWith('image/')) {
            archivos.push(items[i].getAsFile());
            console.log('[handlePastePrenda] Imagen pegada del clipboard:', items[i].type);
        }
    }
    
    if (archivos.length > 0) {
        event.preventDefault();
        event.stopPropagation();
        
        // Crear un pseudo-evento con los archivos
        const input = document.getElementById('inputFotosPrenda');
        const dataTransfer = new DataTransfer();
        
        archivos.forEach(archivo => {
            dataTransfer.items.add(archivo);
        });
        
        input.files = dataTransfer.files;
        manejarSubidaFotosPrenda(input);
        console.log('[handlePastePrenda] Archivos pegados procesados:', archivos.length);
    }
}

// ========== MANEJADOR DE CTRL+V MEJORADO PARA EPP ==========
function handlePasteEPP(event) {
    console.log('[handlePasteEPP] Paste detectado');
    
    // Prevenir que otros handlers lo procesen
    event.preventDefault();
    event.stopPropagation();
    
    if (!event.clipboardData || !event.clipboardData.items) {
        console.warn('[handlePasteEPP] No hay datos en el clipboard');
        return;
    }
    
    const items = event.clipboardData.items;
    const archivos = [];
    
    for (let i = 0; i < items.length; i++) {
        if (items[i].kind === 'file' && items[i].type.startsWith('image/')) {
            archivos.push(items[i].getAsFile());
            console.log('[handlePasteEPP] Imagen pegada del clipboard:', items[i].type);
        }
    }
    
    if (archivos.length > 0) {
        console.log('[handlePasteEPP] Archivos encontrados:', archivos.length);
        console.log('[handlePasteEPP] window.zonaFotosActivaId:', window.zonaFotosActivaId);
        
        // Determinar si estamos en la tabla de EPPs agregados o en sección de edición
        if (window.zonaFotosActivaId && window.zonaFotosActivaId.startsWith('fotoZona_')) {
            // Estamos en la tabla - usar manejarSeleccionFotosEPP
            const eppId = window.zonaFotosActivaId.replace('fotoZona_', '');
            const input = document.getElementById(`inputFotos_${eppId}`);
            
            console.log('[handlePasteEPP] Pegando en tabla para EPP:', eppId);
            console.log('[handlePasteEPP] Input encontrado:', !!input);
            
            if (input) {
                const dataTransfer = new DataTransfer();
                archivos.forEach(archivo => {
                    dataTransfer.items.add(archivo);
                });
                input.files = dataTransfer.files;
                
                console.log('[handlePasteEPP] Archivos asignados al input:', input.files.length);
                console.log('[handlePasteEPP] Llamando a manejarSeleccionFotosEPP');
                
                // Llamar directamente con el objeto evento correcto
                manejarSeleccionFotosEPP({target: input, preventDefault: () => {}, stopPropagation: () => {}}, eppId);
            }
        } else {
            // Estamos en modo edición - usar el input de edición
            const inputEdicion = document.getElementById('inputFotosEPP');
            
            console.log('[handlePasteEPP] Pegando en sección de edición');
            console.log('[handlePasteEPP] Input edición encontrado:', !!inputEdicion);
            
            if (inputEdicion) {
                const dataTransfer = new DataTransfer();
                archivos.forEach(archivo => {
                    dataTransfer.items.add(archivo);
                });
                inputEdicion.files = dataTransfer.files;
                
                console.log('[handlePasteEPP] Archivos asignados al input edición:', inputEdicion.files.length);
                console.log('[handlePasteEPP] Llamando a manejarSubidaFotosEPP');
                
                manejarSubidaFotosEPP(inputEdicion);
            }
        }
        
        console.log('[handlePasteEPP] Archivos pegados procesados:', archivos.length);
    }
}

// Registrar listener de paste cuando se abre el modal de prenda
function abrirModalAgregarPrendaConPasteListener() {
    abrirModalAgregarPrenda();
    setTimeout(() => {
        document.addEventListener('paste', handlePastePrenda);
        console.log('[handlePastePrenda] Listener de paste registrado');
    }, 100);
}

function cerrarModalAgregarPrendaConPasteListener() {
    document.removeEventListener('paste', handlePastePrenda);
    cerrarModalAgregarPrenda();
    console.log('[handlePastePrenda] Listener de paste removido');
}

window.handlePastePrenda = handlePastePrenda;
window.abrirModalAgregarPrendaConPasteListener = abrirModalAgregarPrendaConPasteListener;
window.cerrarModalAgregarPrendaConPasteListener = cerrarModalAgregarPrendaConPasteListener;
window.handlePasteEPP = handlePasteEPP;

// ========== CERRAR MODALES AL HACER CLIC EN EL FONDO ==========
document.addEventListener('click', function(e) {
    // Cerrar modal de selección si se hace clic en el fondo
    const modalSeleccion = document.getElementById('modalSeleccionTipo');
    if (e.target === modalSeleccion) {
        cerrarModalSeleccion();
    }
    
    // Cerrar modal de prenda si se hace clic en el fondo
    const modalPrenda = document.getElementById('modalAgregarPrenda');
    if (e.target === modalPrenda) {
        cerrarModalAgregarPrenda();
    }
    
    // Cerrar modal de EPP si se hace clic en el fondo
    const modalEPP = document.getElementById('modalAgregarEPP');
    if (e.target === modalEPP) {
        cerrarModalAgregarEPP();
    }
});

/**
 * Variable global para rastrear cuál zona de fotos está activa (última clickeada)
 */
window.zonaFotosActivaId = null;

/**
 * Hacer que las zonas de fotos reciban focus al hacer click
 * Esto permite que Ctrl+V detecte correctamente en cuál EPP pegar la imagen
 * También abre el file picker al hacer click
 */
document.addEventListener('click', function(e) {
    const fotoZona = e.target.closest('[id^="fotoZona_"]');
    
    if (fotoZona) {
        // Dar focus a la zona para que Ctrl+V pueda detectarla
        fotoZona.focus();
        // IMPORTANTE: Guardar cuál zona está activa para usar en paste
        window.zonaFotosActivaId = fotoZona.id;
        console.log('[click] Focus en zona de fotos:', window.zonaFotosActivaId);
        
        // Abrir file picker
        const eppId = fotoZona.id.replace('fotoZona_', '');
        const inputFile = document.getElementById(`inputFotos_${eppId}`);
        if (inputFile) {
            console.log('[click] Abriendo file picker para EPP:', eppId);
            inputFile.click();
        }
    }
});
</script>
