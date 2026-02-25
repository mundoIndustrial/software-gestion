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

<!-- Modal Agregar EPP al Pedido -->
<div id="modalAgregarEPP" class="fixed inset-0 bg-black/50 flex items-center justify-center" style="display: none; z-index: 999999;">
    <div class="bg-white rounded-lg w-full max-w-2xl shadow-2xl overflow-hidden" style="z-index: 1000000; max-height: 90vh; display: flex; flex-direction: column;">
        
        <!-- Header Azul -->
        <div class="bg-blue-600 px-6 py-4 flex justify-between items-center flex-shrink-0">
            <h2 class="text-white text-lg font-bold">Agregar EPP al Pedido</h2>
            <button onclick="cerrarModalAgregarEPP()" class="text-white hover:bg-blue-700 p-1 rounded transition">
                <i class="material-symbols-rounded">close</i>
            </button>
        </div>

        <!-- Body con scroll -->
        <div class="p-6 space-y-4 overflow-y-auto flex-1" style="max-height: calc(90vh - 140px);">
            
            <!-- Buscador -->
            <div>
                <label for="inputBuscadorEPP" class="text-sm font-medium text-gray-700 block mb-2">Buscar por Referencia o Nombre</label>
                <div class="relative">
                    <i class="material-symbols-rounded absolute left-3 top-2.5 text-gray-400 text-xl">search</i>
                    <input 
                        type="text" 
                        id="inputBuscadorEPP"
                        onkeyup="filtrarEPPBuscador(this.value)"
                        placeholder="Ej. Casco, Nitrilo, Botas..." 
                        class="w-full pl-10 pr-4 py-2 border-2 border-gray-300 rounded-lg focus:outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-200 text-sm"
                    >
                </div>
                <!-- Contenedor de resultados de búsqueda - DENTRO DEL FORMULARIO -->
                <div id="resultadosBuscadorEPP" class="bg-white border border-gray-300 border-t-0 rounded-b-lg shadow max-h-64 overflow-y-auto mt-0" style="display: none;"></div>
            </div>

            <!-- Botón Crear Nuevo EPP -->
            <div class="flex gap-2">
                <button 
                    type="button"
                    onclick="abrirFormularioCrearEPP()"
                    class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg font-medium flex items-center justify-center gap-2 hover:bg-blue-700 transition text-sm"
                >
                    <i class="material-symbols-rounded" style="font-size: 20px;">add_circle</i>
                    Crear Nuevo EPP
                </button>
            </div>

            <!-- Formulario para Crear Nuevo EPP (inicialmente oculto) -->
            <div id="formularioCrearEPP" class="bg-blue-50 border-2 border-blue-200 rounded-lg p-4" style="display: none;">
                <h3 class="font-semibold text-gray-900 mb-4 flex items-center gap-2">
                    <i class="material-symbols-rounded">add</i>
                    Crear Nuevo EPP
                </h3>
                
                <div class="space-y-3">
                    <div>
                        <label for="nombreCompletNuevoEPP" class="text-sm font-medium text-gray-700 block mb-1">Nombre Completo</label>
                        <input 
                            type="text"
                            id="nombreCompletNuevoEPP"
                            placeholder="Ej. CASCO DE SEGURIDAD ROJO"
                            class="w-full px-3 py-2 border-2 border-blue-300 rounded-lg focus:outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-200 text-sm"
                        >
                    </div>
                    
                    <div class="flex gap-2">
                        <button 
                            type="button"
                            onclick="guardarNuevoEPP()"
                            class="flex-1 px-3 py-2 bg-green-600 text-white rounded-lg font-medium flex items-center justify-center gap-2 hover:bg-green-700 transition text-sm"
                        >
                            <i class="material-symbols-rounded" style="font-size: 18px;">check_circle</i>
                            Guardar
                        </button>
                        <button 
                            type="button"
                            onclick="cerrarFormularioCrearEPP()"
                            class="flex-1 px-3 py-2 bg-gray-400 text-white rounded-lg font-medium flex items-center justify-center gap-2 hover:bg-gray-500 transition text-sm"
                        >
                            <i class="material-symbols-rounded" style="font-size: 18px;">close</i>
                            Cancelar
                        </button>
                    </div>
                </div>
            </div>


            <!-- Tarjeta Producto (inicialmente oculta) -->
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

            <!-- Cantidad y Talla -->
            <!-- Solo Cantidad -->
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

            <!-- Valor Unitario (opcional) y Total -->
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

            <!-- Observaciones -->
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

            <!-- Sección de Fotos (opcional) -->
            <div id="seccionFotosEPP" style="display: none;">
                <div class="flex items-center justify-between mb-3">
                    <label class="text-sm font-medium text-gray-700">Fotos del EPP (Opcional)</label>
                    <button type="button" onclick="agregarFotoEPP()" class="px-3 py-1 bg-blue-600 text-white text-sm rounded-lg font-medium flex items-center gap-1 hover:bg-blue-700 transition">
                        <i class="material-symbols-rounded" style="font-size: 16px;">add_photo_alternate</i>
                        Agregar Foto
                    </button>
                </div>
                
                <!-- Contenedor de imágenes -->
                <div id="contenedorFotosEPP" class="grid grid-cols-3 gap-3 mb-4 border-2 border-dashed border-gray-300 rounded-lg p-4 min-h-[120px] transition-all"
                     tabindex="0" 
                     style="outline: none;"
                     data-zona="epp"
                     onmouseover="this.focus()"
                     onmouseleave="this.blur()"
                     ondrop="handleDropEPP(event)"
                     ondragover="handleDragOverEPP(event)"
                     ondragleave="handleDragLeaveEPP(event)">
                    
                    <!-- Mensaje inicial -->
                    <div id="mensajeDragDrop" class="col-span-3 flex flex-col items-center justify-center text-gray-400">
                        <i class="material-symbols-rounded text-4xl mb-2">cloud_upload</i>
                        <p class="text-sm">Arrastra imágenes aquí o haz clic en "Agregar Foto"</p>
                        <p class="text-xs">También puedes pegar con Ctrl+V</p>
                        <p class="text-xs">Formatos: JPG, PNG, GIF, WebP, JFIF</p>
                    </div>
                    
                    <!-- Las imágenes se agregarán aquí dinámicamente -->
                </div>
                
                <!-- Input oculto para subir archivos -->
                <input 
                    type="file" 
                    id="inputFotosEPP" 
                    multiple 
                    accept="image/*" 
                    style="display: none;"
                    onchange="manejarSubidaFotosEPP(this)"
                >
            </div>

            <!-- Botón Agregar a Lista -->
            <button 
                id="btnAgregarALista"
                onclick="agregarEPPALista()"
                disabled
                class="w-full px-4 py-2 bg-green-600 text-white rounded-lg font-medium flex items-center justify-center gap-2 hover:bg-green-700 disabled:bg-gray-300 disabled:text-gray-500 disabled:cursor-not-allowed transition text-sm"
                style="display: none;"
            >
                <i class="material-symbols-rounded" style="font-size: 20px;">add</i>
                Agregar a la Lista
            </button>

            <!-- Lista de EPP agregados -->
            <div id="listaEPPAgregados" style="display: none;">
                <h3 class="text-sm font-semibold text-gray-900 mb-2 flex items-center gap-2">
                    <i class="material-symbols-rounded">list</i>
                    EPP Agregados
                </h3>
                <div class="border border-gray-200 rounded-lg overflow-hidden">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-2 text-left text-gray-700 font-medium">Foto</th>
                                <th class="px-4 py-2 text-left text-gray-700 font-medium">EPP</th>
                                <th class="px-4 py-2 text-left text-gray-700 font-medium">Cantidad</th>
                                <th class="px-4 py-2 text-left text-gray-700 font-medium">Observaciones</th>
                                <th class="px-4 py-2 text-center text-gray-700 font-medium">Acción</th>
                            </tr>
                        </thead>
                        <tbody id="cuerpoTablaEPP">
                            <!-- Se llena dinámicamente -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Footer fijo -->
        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end gap-3 flex-shrink-0">
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


<script>
// Variables globales
let productoSeleccionadoEPP = null;
let eppAgregadosList = []; // Lista de EPP agregados

function abrirModalAgregarEPP() {
    console.log('📖 [abrirModalAgregarEPP] Abriendo modal');
    const modal = document.getElementById('modalAgregarEPP');
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';

    // Verificar si estamos en modo edición
    // Buscar en window.eppEnEdicion que es donde se configura realmente
    const esObjeto = typeof window.eppEnEdicion === 'object';
    const tienePropiedades = window.eppEnEdicion && Object.keys(window.eppEnEdicion).length > 0;
    const enModoEdicion = window.eppEnEdicion && esObjeto && tienePropiedades;
    
    console.log('📖 [abrirModalAgregarEPP] window.eppEnEdicion:', window.eppEnEdicion);
    console.log('📖 [abrirModalAgregarEPP] Es objeto:', esObjeto);
    console.log('📖 [abrirModalAgregarEPP] Tiene propiedades:', tienePropiedades);
    console.log('📖 [abrirModalAgregarEPP] En modo edición (final):', enModoEdicion);
    
    // Verificar estado de la tabla
    const listaControl = document.getElementById('listaEPPAgregados');
    if (listaControl) {
        console.log('📖 [abrirModalAgregarEPP] Estado actual tabla ANTES - display:', window.getComputedStyle(listaControl).display);
    }
    
    // Solo resetear si NO estamos en modo edición
    if (!enModoEdicion) {
        console.log('📖 [abrirModalAgregarEPP] Modo normal - resetear modal');
        resetearModalAgregarEPP();
    } else {
        console.log('📖 [abrirModalAgregarEPP] Modo edición - NO resetear modal, manteniendo estado');
        if (listaControl) {
            console.log('📖 [abrirModalAgregarEPP] Estado tabla DESPUÉS (sin reset) - display:', window.getComputedStyle(listaControl).display);
        }
    }
    
    // Registrar listener de paste después de un pequeño delay
    setTimeout(() => {
        document.addEventListener('paste', window.handlePasteEPP);
        console.log('[abrirModalAgregarEPP] Paste listener registrado');
    }, 100);
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

function cerrarModalAgregarEPP() {
    console.log('🔒 [cerrarModalAgregarEPP] Cerrando modal');
    const modal = document.getElementById('modalAgregarEPP');
    modal.style.display = 'none';
    
    // Remover listener de paste
    document.removeEventListener('paste', window.handlePasteEPP);
    console.log('[cerrarModalAgregarEPP] Paste listener removido');
    document.body.style.overflow = 'auto';
    eppAgregadosList = []; // Limpiar lista al cerrar

    // Siempre limpiar estado de edición y formulario al cerrar/cancelar
    eppEnEdicion = null;
    window.eppEnEdicion = null;
    console.log('🔒 [cerrarModalAgregarEPP] window.eppEnEdicion limpiado');
    
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
    console.log('📖 [resetearModalAgregarEPP] INICIANDO reset del modal');
    // NO limpiar window.eppEnEdicion aquí, solo hacerlo cuando realmente terminamos
    // eppEnEdicion = null;  // Comentado: no limpiar la variable local
    // Limpiar imágenes temporales primero
    limpiarImagenesTemporales();
    
    document.getElementById('cantidadEPP').value = '1';
    const vu = document.getElementById('valorUnitarioEPP');
    const tot = document.getElementById('totalEPP');
    const vuCont = document.getElementById('valorUnitarioTotalContainer');
    if (vu) {
        vu.disabled = true;
        vu.value = '';
    }
    if (tot) {
        tot.value = '0';
    }
    if (vuCont) {
        vuCont.style.display = 'none';
    }
    document.getElementById('observacionesEPP').disabled = true;
    document.getElementById('observacionesEPP').value = '';
    document.getElementById('btnAgregarALista').disabled = true;
    document.getElementById('btnAgregarALista').style.display = 'none';
    document.getElementById('btnFinalizarAgregarEPP').disabled = true;
    document.getElementById('btnFinalizarAgregarEPP').style.display = 'flex';
    document.getElementById('btnGuardarCambiosEPP').disabled = true;
    document.getElementById('btnGuardarCambiosEPP').style.display = 'none';
    
    // Restaurar sección de "EPP Agregados" al resetear (SOLO si no estamos editando)
    const enEdicion = !!eppEnEdicion || !!window.eppEnEdicion;
    const listaEPPAgregados = document.getElementById('listaEPPAgregados');
    if (listaEPPAgregados) {
        console.log('📖 [resetearModalAgregarEPP] Tabla encontrada - display ANTES:', window.getComputedStyle(listaEPPAgregados).display);
        console.log('📖 [resetearModalAgregarEPP] En edición:', enEdicion);
        
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
        console.log('📖 [resetearModalAgregarEPP] Tabla - display DESPUÉS:', window.getComputedStyle(listaEPPAgregados).display);
    } else {
        console.warn('📖 [resetearModalAgregarEPP] Tabla no encontrada');
    }
    
    actualizarEstilosCampos();
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

    // Mostrar sección de fotos
    const seccionFotos = document.getElementById('seccionFotosEPP');
    console.log(' [mostrarProductoEPP] Sección fotos encontrada:', !!seccionFotos);
    if (seccionFotos) {
        seccionFotos.style.display = 'block';
        console.log(' [mostrarProductoEPP] Sección fotos visible - display:', seccionFotos.style.display);
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
    document.getElementById('seccionFotosEPP').style.display = 'none';
    
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
        
        // Asegurar que la sección de fotos esté visible
        document.getElementById('seccionFotosEPP').style.display = 'block';
        
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
                    onclick="eliminarEPPDeLista(${idx})"
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

function eliminarEPPDeLista(idx) {
    eppAgregadosList.splice(idx, 1);
    renderizarTablaEPP();

    if (eppAgregadosList.length === 0) {
        document.getElementById('listaEPPAgregados').style.display = 'none';
        document.getElementById('btnFinalizarAgregarEPP').disabled = true;
    }
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
    console.log('✏️ [editarEPPAgregado] INICIANDO - Editando EPP:', eppData);
    console.log('✏️ [editarEPPAgregado] Propiedades de eppData:', Object.keys(eppData));
    
    // Guardar referencia del EPP en edición TANTO en local como en window
    eppEnEdicion = eppData;
    window.eppEnEdicion = eppData;
    console.log('✏️ [editarEPPAgregado] eppEnEdicion configurado (local):', !!eppEnEdicion);
    console.log('✏️ [editarEPPAgregado] window.eppEnEdicion configurado (global):', !!window.eppEnEdicion);
    console.log('✏️ [editarEPPAgregado] eppEnEdicion es objeto:', typeof eppEnEdicion === 'object');
    console.log('✏️ [editarEPPAgregado] Claves en eppEnEdicion:', Object.keys(eppEnEdicion || {}));
    
    // Limpiar la lista de agregados para modo edición
    eppAgregadosList = [];
    productoSeleccionadoEPP = null;
    console.log('✏️ [editarEPPAgregado] Lista de agregados y producto limpiados');
    
    // Limpiar buscador
    const buscador = document.getElementById('inputBuscadorEPP');
    if (buscador) buscador.value = '';
    const resultados = document.getElementById('resultadosBuscadorEPP');
    if (resultados) resultados.style.display = 'none';
    console.log('✏️ [editarEPPAgregado] Buscador limpiado');
    
    // Limpiar elementos visuales previos
    const tarjetaCard = document.getElementById('productoCardEPP');
    if (tarjetaCard) tarjetaCard.style.display = 'none';
    const lista = document.getElementById('listaEPPAgregados');
    if (lista) {
        console.log('✏️ [editarEPPAgregado] Tabla encontrada ANTES - display:', window.getComputedStyle(lista).display);
        // Remover completamente el atributo style para que se oculte sin conflictos
        lista.removeAttribute('style');
        lista.style.setProperty('display', 'none', 'important');
        lista.style.setProperty('visibility', 'hidden', 'important');
        console.log('✏️ [editarEPPAgregado] Tabla listaEPPAgregados ocultada - estilo removido y reestablecido');
        console.log('✏️ [editarEPPAgregado] Tabla DESPUÉS - display:', window.getComputedStyle(lista).display);
        console.log('✏️ [editarEPPAgregado] Tabla style.display:', lista.style.display);
    } else {
        console.warn('✏️ [editarEPPAgregado] Elemento listaEPPAgregados no encontrado');
    }
    const cuerpo = document.getElementById('cuerpoTablaEPP');
    if (cuerpo) cuerpo.innerHTML = '';
    console.log('✏️ [editarEPPAgregado] Elementos visuales previos limpiados');
    
    // Mostrar el producto seleccionado
    console.log('✏️ [editarEPPAgregado] Llamando a mostrarProductoEPP...');
    mostrarProductoEPP({
        id: eppData.epp_id || eppData.id,
        nombre_completo: eppData.nombre_epp || eppData.nombre,
        nombre: eppData.nombre_epp || eppData.nombre,
        imagen: ''
    });
    console.log('✏️ [editarEPPAgregado] mostrarProductoEPP completado');
    
    // Cargar valores en el formulario
    const cantidadInput = document.getElementById('cantidadEPP');
    const obsInput = document.getElementById('observacionesEPP');
    if (cantidadInput) cantidadInput.value = eppData.cantidad || 1;
    if (obsInput) obsInput.value = eppData.observaciones || '';
    console.log('✏️ [editarEPPAgregado] Valores cargados - cantidad:', eppData.cantidad, 'observaciones:', eppData.observaciones);

    // Precargar valor unitario / total (modo cotización)
    const modoCotizacion = !!window.__EPP_COTIZACION_MODE__;
    const vuInput = document.getElementById('valorUnitarioEPP');
    const totInput = document.getElementById('totalEPP');
    const vuCont = document.getElementById('valorUnitarioTotalContainer');
    if (modoCotizacion && vuCont) {
        vuCont.style.display = 'block';
    }
    if (modoCotizacion && vuInput) {
        vuInput.disabled = false;
        vuInput.value = (eppData.valor_unitario !== undefined && eppData.valor_unitario !== null && String(eppData.valor_unitario).trim() !== '')
            ? String(eppData.valor_unitario)
            : '';
    }
    if (modoCotizacion && totInput) {
        totInput.value = (eppData.total !== undefined && eppData.total !== null && String(eppData.total).trim() !== '')
            ? String(eppData.total)
            : '0';
    }
    if (modoCotizacion) {
        actualizarTotalEPP();
    }

    // Precargar imágenes en el modal
    try {
        const imgs = Array.isArray(eppData.imagenes) ? eppData.imagenes : [];
        console.log('✏️ [editarEPPAgregado] Imágenes recibidas:', imgs.length, imgs);
        
        // IMPORTANTE: Preservar referencias blob válidas
        window.fotosEPP = [];
        
        const contenedor = document.getElementById('contenedorFotosEPP');
        if (contenedor) {
            const elementosFoto = contenedor.querySelectorAll('.foto-epp-item');
            elementosFoto.forEach(el => el.remove());
        }
        const mensajeDragDrop = document.getElementById('mensajeDragDrop');
        if (mensajeDragDrop) {
            mensajeDragDrop.style.display = imgs.length > 0 ? 'none' : 'flex';
        }
        
        imgs.forEach((img, idx) => {
            // Detectar si es un blob/preview URL
            const isBlob = img.previewUrl && (img.previewUrl.includes('blob:') || img.previewUrl.startsWith('blob:'));
            
            // Para URLs blob, mantener la referencia original exacta
            let url = null;
            if (isBlob && img.previewUrl) {
                url = img.previewUrl;
            } else {
                url = (typeof img === 'string')
                    ? img
                    : (img.previewUrl || img.url || img.ruta_web || img.ruta_webp || img.ruta_original || null);
            }
            
            if (!url) {
                console.warn('✏️ [editarEPPAgregado] Imagen sin URL válida:', img);
                return;
            }
            
            const imagen = {
                id: img?.id || `edit-${Date.now()}-${idx}`,
                nombre: img?.nombre || `imagen-${idx + 1}`,
                previewUrl: url,  // Mantener la referencia exacta
                url: url,
            };
            window.fotosEPP.push(imagen);
            console.log('✏️ [editarEPPAgregado] Imagen agregada - nombre:', imagen.nombre, 'URL:', url.substring(0, 50) + '...');
            
            if (typeof mostrarVistaPreviaFoto === 'function') {
                mostrarVistaPreviaFoto(imagen);
            }
        });
        console.log('✏️ [editarEPPAgregado] Total imágenes cargadas en window.fotosEPP:', window.fotosEPP.length);
    } catch (e) {
        console.error('✏️ [editarEPPAgregado] Error cargando imágenes:', e);
    }
    
    // Mostrar los campos del formulario
    const formulario = document.getElementById('formularioAgregarEPP');
    const obsContainer = document.getElementById('observacionesContainer');
    const btnAgregar = document.getElementById('btnAgregarALista');
    const btnGuardarCambios = document.getElementById('btnGuardarCambiosEPP');
    const btnFinalizar = document.getElementById('btnFinalizarAgregarEPP');
    
    if (formulario) {
        formulario.style.display = 'grid';
        console.log('✏️ [editarEPPAgregado] Formulario mostrado');
    }
    if (obsContainer) {
        obsContainer.style.display = 'block';
        console.log('✏️ [editarEPPAgregado] Contenedor observaciones mostrado');
    }
    
    // Ocultar botón de agregar a lista (no se usa en modo edición)
    if (btnAgregar) {
        btnAgregar.style.display = 'none';
        console.log('✏️ [editarEPPAgregado] Botón agregar a lista ocultado');
    }
    
    // Habilitar campos para edición
    if (cantidadInput) cantidadInput.disabled = false;
    if (obsInput) obsInput.disabled = false;
    console.log('✏️ [editarEPPAgregado] Campos habilitados');
    
    // Configurar botones del footer
    if (btnFinalizar) {
        btnFinalizar.style.display = 'none';
        btnFinalizar.disabled = true;
        console.log('✏️ [editarEPPAgregado] Botón finalizar ocultado');
    }
    if (btnGuardarCambios) {
        btnGuardarCambios.style.display = 'flex';
        btnGuardarCambios.disabled = false;
        console.log('✏️ [editarEPPAgregado] Botón guardar cambios mostrado y habilitado');
    }
    
    // Actualizar estilos de campos
    actualizarEstilosCampos();
    
    console.log('✏️ [editarEPPAgregado] Preparado para edición, abriendo modal...');
    
    // Mostrar modal
    abrirModalAgregarEPP();
    
    console.log('✏️ [editarEPPAgregado] FINALIZADO - Modal abierto en modo edición');
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

    // Actualizar en window.itemsPedido
    const targetId = window.eppEnEdicion.epp_id || window.eppEnEdicion.id;
    const index = window.itemsPedido.findIndex(item =>
        (item.id !== undefined && item.id !== null && String(item.id) === String(targetId))
        || (item.epp_id !== undefined && item.epp_id !== null && String(item.epp_id) === String(targetId))
    );
    if (index !== -1) {
        window.itemsPedido[index].nombre_epp = nombre;
        window.itemsPedido[index].nombre = nombre;
        window.itemsPedido[index].cantidad = parseInt(cantidad);
        window.itemsPedido[index].observaciones = observaciones || '-';
        if (modoCotizacion) {
            window.itemsPedido[index].valor_unitario = vu;
            window.itemsPedido[index].total = total;
        }
        window.itemsPedido[index].imagenes = imagenes;
        console.log(' [guardarEdicionEPP] EPP actualizado en window.itemsPedido:', window.itemsPedido[index]);
    } else {
        console.warn(' [guardarEdicionEPP] No se encontró EPP en window.itemsPedido para actualizar');
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
    
    // Cerrar modal
    cerrarModalAgregarEPP();

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
        
        // Agregar todos los EPPs procesados a window.itemsPedido
        eppsProcesados.forEach((eppData) => {
            window.itemsPedido.push(eppData);
            console.log(` [finalizarAgregarEPP] EPP guardado en window.itemsPedido:`, eppData);
            
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
        
        // Recalcular totales después de agregar los EPPs
        if (typeof window.syncTotales === 'function') {
            window.syncTotales();
            console.log('[finalizarAgregarEPP] Totales recalculados');
        }
        
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
                <button type="button" onclick="eliminarFotoEPP('${imagen.id}')" class="bg-red-500 text-white p-1 rounded-full opacity-0 group-hover:opacity-100 transition-opacity">
                    <i class="mostrar-symbols-rounded text-sm">delete</i>
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
    
    // Mostrar sección de fotos si no está visible
    document.getElementById('seccionFotosEPP').style.display = 'block';
}

function eliminarFotoEPP(fotoId) {
    console.log(`[eliminarFotoEPP] Intentando eliminar foto con ID: ${fotoId}`);
    console.log(`[eliminarFotoEPP] Fotos actuales:`, window.fotosEPP.map(f => ({ id: f.id, nombre: f.nombre })));
    
    // Encontrar la foto para liberar la URL blob
    const fotoAEliminar = window.fotosEPP.find(foto => foto.id === fotoId);
    
    // Eliminar de la lista temporal
    window.fotosEPP = window.fotosEPP.filter(foto => foto.id !== fotoId);
    
    // Eliminar del DOM usando el selector más específico
    const elemento = document.querySelector(`#contenedorFotosEPP div.foto-epp-item[data-foto-id="${fotoId}"]`);
    console.log(`[eliminarFotoEPP] Buscando elemento con selector: #contenedorFotosEPP div.foto-epp-item[data-foto-id="${fotoId}"]`);
    console.log(`[eliminarFotoEPP] Elemento encontrado:`, elemento);
    console.log(`[eliminarFotoEPP] Elemento ID:`, elemento?.id);
    console.log(`[eliminarFotoEPP] Elemento classes:`, elemento?.className);
    console.log(`[eliminarFotoEPP] Elemento es contenedor principal:`, elemento?.id === 'contenedorFotosEPP');
    
    if (elemento) {
        // Verificación extra de seguridad
        if (elemento.id === 'contenedorFotosEPP') {
            console.error(`[eliminarFotoEPP] ¡ERROR! Se encontró el contenedor principal en lugar de la imagen. Abortando eliminación.`);
            return;
        }
        elemento.remove();
        console.log(`[eliminarFotoEPP] Elemento DOM eliminado para ID: ${fotoId}`);
    } else {
        console.warn(`[eliminarFotoEPP] No se encontró elemento con ID: ${fotoId}`);
        
        // Intentar buscar por el nombre del archivo como fallback
        const elementos = document.querySelectorAll('#contenedorFotosEPP div.foto-epp-item');
        console.log(`[eliminarFotoEPP] Elementos con clase foto-epp-item encontrados:`, elementos.length);
        elementos.forEach((elemento, index) => {
            console.log(`[eliminarFotoEPP] Elemento ${index}:`, elemento.id, elemento.className);
            const img = elemento.querySelector('img');
            if (img && img.alt && img.alt.includes(fotoId)) {
                console.log(`[eliminarFotoEPP] Eliminando elemento ${index} por nombre: ${img.alt}`);
                elemento.remove();
                return;
            }
        });
    }
    
    // Liberar la URL blob para liberar memoria
    if (fotoAEliminar && fotoAEliminar.previewUrl) {
        URL.revokeObjectURL(fotoAEliminar.previewUrl);
        console.log(`[eliminarFotoEPP] URL blob liberada para: ${fotoAEliminar.nombre}`);
    }
    
    console.log(`[eliminarFotoEPP] Fotos restantes: ${window.fotosEPP.length}`);
    
    // Si no hay más fotos, mostrar mensaje inicial
    if (window.fotosEPP.length === 0) {
        const mensajeDragDrop = document.getElementById('mensajeDragDrop');
        if (mensajeDragDrop) {
            mensajeDragDrop.style.display = 'flex';
        }
        // No ocultar la sección completa, solo mostrar el mensaje inicial
        // document.getElementById('seccionFotosEPP').style.display = 'none'; // Comentar esta línea
    }
}

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
        z-index: 9999999 !important;
    }
</style>

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
window.abrirModalAgregarEPP = abrirModalAgregarEPP;
window.abrirModalEditarEPPNuevo = abrirModalEditarEPPNuevo;
window.resetearModalAgregarEPP = resetearModalAgregarEPP;
window.cerrarModalAgregarEPP = cerrarModalAgregarEPP;
window.mostrarProductoEPP = mostrarProductoEPP;
window.agregarEPPALista = agregarEPPALista;
window.finalizarAgregarEPP = finalizarAgregarEPP;
window.guardarEdicionEPP = guardarEdicionEPP;
window.filtrarEPPBuscador = filtrarEPPBuscador;
window.agregarFotoEPP = agregarFotoEPP;
window.eliminarFotoEPP = eliminarFotoEPP;
window.mostrarVistaPreviaFoto = mostrarVistaPreviaFoto;
window.limpiarImagenesTemporales = limpiarImagenesTemporales;

console.log('[EPP Modal] Funciones exportadas al objeto window');

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

// ========== MANEJADOR DE CTRL+V PARA EPP ==========
function handlePasteEPP(event) {
    console.log('[handlePasteEPP] Paste detectado');
    
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
        event.preventDefault();
        event.stopPropagation();
        
        // Crear un pseudo-evento con los archivos
        const input = document.getElementById('inputFotosEPP');
        const dataTransfer = new DataTransfer();
        
        archivos.forEach(archivo => {
            dataTransfer.items.add(archivo);
        });
        
        input.files = dataTransfer.files;
        manejarSubidaFotosEPP(input);
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
</script>

