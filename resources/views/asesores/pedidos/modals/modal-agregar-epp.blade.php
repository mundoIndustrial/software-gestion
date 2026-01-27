<!-- Modal Agregar EPP al Pedido -->
<div id="modalAgregarEPP" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" style="display: none;">
    <div class="bg-white rounded-lg w-full max-w-2xl shadow-2xl overflow-hidden max-h-screen overflow-y-auto">
        
        <!-- Header Azul -->
        <div class="bg-blue-600 px-6 py-4 flex justify-between items-center">
            <h2 class="text-white text-lg font-bold">Agregar EPP al Pedido</h2>
            <button onclick="cerrarModalAgregarEPP()" class="text-white hover:bg-blue-700 p-1 rounded transition">
                <i class="material-symbols-rounded">close</i>
            </button>
        </div>

        <!-- Body -->
        <div class="p-6 space-y-4">
            
            <!-- Buscador -->
            <div>
                <label class="text-sm font-medium text-gray-700 block mb-2">Buscar por Referencia o Nombre</label>
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

            <!-- Tarjeta Producto (inicialmente oculta) -->
            <div id="productoCardEPP" class="bg-blue-50 border border-blue-200 rounded-lg p-4 animate-in fade-in" style="display: none;">
                <h3 id="nombreProductoEPP" class="font-semibold text-gray-900 text-sm leading-snug"></h3>
            </div>

            <!-- Cantidad y Talla -->
            <!-- Solo Cantidad -->
            <div id="formularioAgregarEPP" style="display: none;">
                <div>
                    <label class="text-sm font-medium text-gray-700 block mb-2">Cantidad</label>
                    <input 
                        type="number"
                        id="cantidadEPP"
                        value="1"
                        placeholder="1"
                        min="1"
                        disabled
                        class="w-full px-3 py-2 border-2 border-gray-300 rounded-lg bg-gray-100 text-gray-500 text-sm disabled:bg-gray-100 disabled:text-gray-400 focus:outline-none"
                    >
                </div>
            </div>

            <!-- Observaciones -->
            <div id="observacionesContainer" style="display: none;">
                <label class="text-sm font-medium text-gray-700 block mb-2">Observaciones (Opcional)</label>
                <textarea 
                    id="observacionesEPP"
                    placeholder="Detalles adicionales..."
                    disabled
                    rows="2"
                    class="w-full px-3 py-2 border-2 border-gray-300 rounded-lg bg-gray-100 text-gray-500 text-sm disabled:bg-gray-100 disabled:text-gray-400 focus:outline-none resize-none"
                ></textarea>
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

        <!-- Footer -->
        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end gap-3">
            <button onclick="cerrarModalAgregarEPP()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 font-medium hover:bg-gray-100 transition text-sm">
                Cancelar
            </button>
            <button 
                id="btnFinalizarAgregarEPP"
                onclick="finalizarAgregarEPP()"
                disabled
                class="px-4 py-2 bg-blue-600 text-white rounded-lg font-medium flex items-center gap-2 hover:bg-blue-700 disabled:bg-gray-300 disabled:text-gray-500 disabled:cursor-not-allowed transition text-sm"
            >
                <i class="material-symbols-rounded" style="font-size: 20px;">check_circle</i>
                Finalizar
            </button>
        </div>
    </div>
</div>


<script>
// Variables globales
let productoSeleccionadoEPP = null;
let eppAgregadosList = []; // Lista de EPP agregados

function abrirModalAgregarEPP() {
    const modal = document.getElementById('modalAgregarEPP');
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    resetearModalAgregarEPP();
}

function cerrarModalAgregarEPP() {
    const modal = document.getElementById('modalAgregarEPP');
    modal.style.display = 'none';
    document.body.style.overflow = 'auto';
    eppAgregadosList = []; // Limpiar lista al cerrar
}

function resetearModalAgregarEPP() {
    productoSeleccionadoEPP = null;
    eppAgregadosList = [];
    document.getElementById('inputBuscadorEPP').value = '';
    document.getElementById('productoCardEPP').style.display = 'none';
    document.getElementById('formularioAgregarEPP').style.display = 'none';
    document.getElementById('observacionesContainer').style.display = 'none';
    document.getElementById('listaEPPAgregados').style.display = 'none';
    document.getElementById('cuerpoTablaEPP').innerHTML = '';
    document.getElementById('cantidadEPP').disabled = true;
    document.getElementById('cantidadEPP').value = '1';
    document.getElementById('observacionesEPP').disabled = true;
    document.getElementById('observacionesEPP').value = '';
    document.getElementById('btnAgregarALista').disabled = true;
    document.getElementById('btnFinalizarAgregarEPP').disabled = true;
    
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
    console.log('✅ [mostrarProductoEPP] Llamado con producto:', producto);
    productoSeleccionadoEPP = producto;
    
    // Mostrar tarjeta
    document.getElementById('productoCardEPP').style.display = 'block';
    document.getElementById('nombreProductoEPP').textContent = producto.nombre_completo || producto.nombre;

    // Mostrar formulario
    document.getElementById('formularioAgregarEPP').style.display = 'grid';
    document.getElementById('observacionesContainer').style.display = 'block';
    document.getElementById('btnAgregarALista').style.display = 'flex';

    // Información completa incluida en nombre_completo (no necesita cargar tallas)

    // Habilitar campos
    document.getElementById('cantidadEPP').disabled = false;
    document.getElementById('observacionesEPP').disabled = false;
    document.getElementById('btnAgregarALista').disabled = false;

    actualizarEstilosCampos();
    console.log('✅ [mostrarProductoEPP] Completado');
}


// Función cargarTallasEPP removida - talla incluida en nombre_completo

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

    // Agregar a la lista (talla ya viene en nombre_completo)
    eppAgregadosList.push({
        id: productoSeleccionadoEPP.id,
        nombre_completo: productoSeleccionadoEPP.nombre_completo || productoSeleccionadoEPP.nombre,
        cantidad: parseInt(cantidad),
        observaciones: observaciones,
        imagen: productoSeleccionadoEPP.imagen
    });

    console.log('✅ EPP agregado a lista:', eppAgregadosList[eppAgregadosList.length - 1]);

    // Actualizar tabla
    renderizarTablaEPP();

    // Mostrar lista si hay items
    if (eppAgregadosList.length > 0) {
        const listaContainer = document.getElementById('listaEPPAgregados');
        listaContainer.style.display = 'block';
        console.log('✅ [agregarEPPALista] Lista mostrada. Display:', listaContainer.style.display);
        document.getElementById('btnFinalizarAgregarEPP').disabled = false;
    }

    // 🔑 IMPORTANTE: Limpiar completamente el formulario
    // Ocultar tarjeta y formulario
    document.getElementById('productoCardEPP').style.display = 'none';
    document.getElementById('formularioAgregarEPP').style.display = 'none';
    document.getElementById('observacionesContainer').style.display = 'none';
    document.getElementById('btnAgregarALista').style.display = 'none';

    // Resetear valores
    document.getElementById('cantidadEPP').value = '1';
    document.getElementById('observacionesEPP').value = '';
    
    // 🔑 IMPORTANTE: Limpiar buscador y desseleccionar producto
    document.getElementById('inputBuscadorEPP').value = '';
    productoSeleccionadoEPP = null;

    // Deshabilitar campos
    document.getElementById('cantidadEPP').disabled = true;
    document.getElementById('observacionesEPP').disabled = true;
    document.getElementById('btnAgregarALista').disabled = true;

    // Enfocar buscador para agregar otro
    document.getElementById('inputBuscadorEPP').focus();
    
    console.log('✅ Formulario limpiado. Listo para agregar otro EPP');
}

function renderizarTablaEPP() {
    console.log('📋 [renderizarTablaEPP] Iniciado. Total items:', eppAgregadosList.length);
    const tbody = document.getElementById('cuerpoTablaEPP');
    if (!tbody) {
        console.error('❌ [renderizarTablaEPP] tbody no encontrado');
        return;
    }
    
    tbody.innerHTML = '';

    eppAgregadosList.forEach((epp, idx) => {
        console.log(`📌 [renderizarTablaEPP] Renderizando EPP ${idx + 1}:`, epp.nombre_completo);
        const row = document.createElement('tr');
        row.className = idx % 2 === 0 ? 'bg-white' : 'bg-gray-50';
        row.innerHTML = `
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
    
    console.log('✅ [renderizarTablaEPP] Completado. Filas renderizadas:', eppAgregadosList.length);
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
    document.getElementById('cantidadEPP').disabled = true;
    document.getElementById('cantidadEPP').value = '1';
    document.getElementById('observacionesEPP').disabled = true;
    document.getElementById('observacionesEPP').value = '';
    document.getElementById('btnAgregarALista').disabled = true;

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
 

function finalizarAgregarEPP() {
    if (eppAgregadosList.length === 0) {
        alert('Por favor agrega al menos un EPP');
        return;
    }

    console.log('🎯 [finalizarAgregarEPP] Finalizando con EPP:', eppAgregadosList);
    
    // Agregar cada EPP a las tarjetas del pedido
    eppAgregadosList.forEach((epp) => {
        console.log(`📌 [finalizarAgregarEPP] Agregando EPP: ${epp.nombre_completo}`);
        
        // Usar eppItemManager para crear la tarjeta visual
        if (window.eppItemManager && typeof window.eppItemManager.crearItem === 'function') {
            window.eppItemManager.crearItem(
                epp.id,                    // id
                epp.nombre_completo,       // nombre
                '',                         // categoria
                epp.cantidad,              // cantidad
                epp.observaciones,         // observaciones
                []                         // imagenes
            );
            console.log(`✅ [finalizarAgregarEPP] EPP agregado a tarjeta: ${epp.nombre_completo}`);
        } else {
            console.warn('⚠️ [finalizarAgregarEPP] eppItemManager no disponible');
        }
    });
    
    console.log('✅ [finalizarAgregarEPP] Todos los EPP han sido agregados');
    cerrarModalAgregarEPP();
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
</script>

