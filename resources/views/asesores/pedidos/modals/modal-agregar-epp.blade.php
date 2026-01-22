<!-- Modal Agregar EPP al Pedido -->
<div id="modalAgregarEPP" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" style="display: none;">
    <div class="bg-white rounded-lg w-full max-w-md shadow-2xl overflow-hidden">
        
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
            </div>

            <!-- Tarjeta Producto (inicialmente oculta) -->
            <div id="productoCardEPP" class="bg-blue-50 border border-blue-200 rounded-lg p-4 flex gap-4 animate-in fade-in" style="display: none;">
                <img id="imagenProductoEPP" src="" alt="EPP" class="w-20 h-20 rounded bg-white border border-blue-200 object-cover flex-shrink-0">
                <div class="flex-1">
                    <span id="categoriaProductoEPP" class="text-xs font-bold text-blue-600 uppercase block mb-1"></span>
                    <h3 id="nombreProductoEPP" class="font-semibold text-gray-900 text-sm mb-1 leading-snug"></h3>
                    <code id="codigoProductoEPP" class="text-xs text-gray-600"></code>
                </div>
            </div>

            <!-- Cantidad -->
            <div>
                <label class="text-sm font-medium text-gray-700 block mb-2">Cantidad</label>
                <input 
                    type="number"
                    id="cantidadEPP"
                    value="1"
                    placeholder="1"
                    disabled
                    class="w-full px-3 py-2 border-2 border-gray-300 rounded-lg bg-gray-100 text-gray-500 text-sm disabled:bg-gray-100 disabled:text-gray-400 focus:outline-none"
                >
            </div>

            <!-- Observaciones -->
            <div>
                <label class="text-sm font-medium text-gray-700 block mb-2">Observaciones</label>
                <textarea 
                    id="observacionesEPP"
                    placeholder="Detalles adicionales..."
                    disabled
                    rows="3"
                    class="w-full px-3 py-2 border-2 border-gray-300 rounded-lg bg-gray-100 text-gray-500 text-sm disabled:bg-gray-100 disabled:text-gray-400 focus:outline-none resize-none"
                ></textarea>
            </div>
        </div>

        <!-- Footer -->
        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end gap-3">
            <button onclick="cerrarModalAgregarEPP()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 font-medium hover:bg-gray-100 transition text-sm">
                Cancelar
            </button>
            <button 
                id="btnAgregarEPP"
                onclick="agregarEPPAlPedido()"
                disabled
                class="px-4 py-2 bg-blue-600 text-white rounded-lg font-medium flex items-center gap-2 hover:bg-blue-700 disabled:bg-gray-300 disabled:text-gray-500 disabled:cursor-not-allowed transition text-sm"
            >
                <i class="material-symbols-rounded" style="font-size: 20px;">add_circle</i>
                Agregar al Pedido
            </button>
        </div>
    </div>
</div>


<script>
// Variables globales
let productoSeleccionadoEPP = null;
const eppDatos = [
    {
        id: 1,
        nombre: 'Casco de Seguridad ABS con Suspensión',
        categoria: 'PROTECCIÓN CABEZA',
        codigo: 'EPP-CAB-001',
        imagen: 'https://via.placeholder.com/80?text=Casco',
        referencia: 'Casco'
    },
    {
        id: 2,
        nombre: 'Guantes Nitrilo Anti Resbalón',
        categoria: 'PROTECCIÓN MANOS',
        codigo: 'EPP-MAO-002',
        imagen: 'https://via.placeholder.com/80?text=Guantes',
        referencia: 'Nitrilo'
    },
    {
        id: 3,
        nombre: 'Botas de Seguridad Punta de Acero',
        categoria: 'PROTECCIÓN PIES',
        codigo: 'EPP-PIE-003',
        imagen: 'https://via.placeholder.com/80?text=Botas',
        referencia: 'Botas'
    },
    {
        id: 4,
        nombre: 'Chalecos Reflectivos Alta Visibilidad',
        categoria: 'VISIBILIDAD',
        codigo: 'EPP-VIS-004',
        imagen: 'https://via.placeholder.com/80?text=Chaleco',
        referencia: 'Chaleco'
    },
    {
        id: 5,
        nombre: 'Mascarillas FFP2 Protección Respiratoria',
        categoria: 'PROTECCIÓN RESPIRATORIA',
        codigo: 'EPP-RES-005',
        imagen: 'https://via.placeholder.com/80?text=Mascarilla',
        referencia: 'Mascarilla'
    }
];

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
}

function resetearModalAgregarEPP() {
    productoSeleccionadoEPP = null;
    document.getElementById('inputBuscadorEPP').value = '';
    document.getElementById('productoCardEPP').style.display = 'none';
    document.getElementById('cantidadEPP').disabled = true;
    document.getElementById('cantidadEPP').value = '1';
    document.getElementById('observacionesEPP').disabled = true;
    document.getElementById('observacionesEPP').value = '';
    document.getElementById('btnAgregarEPP').disabled = true;
    document.getElementById('btnAgregarEPP').classList.add('disabled');
    
    actualizarEstilosCampos();
}

function filtrarEPPBuscador(valor) {
    const busqueda = valor.toLowerCase();
    
    if (!busqueda) {
        document.getElementById('productoCardEPP').style.display = 'none';
        resetearFormularioEPP();
        return;
    }

    const producto = eppDatos.find(epp => 
        epp.nombre.toLowerCase().includes(busqueda) ||
        epp.referencia.toLowerCase().includes(busqueda) ||
        epp.codigo.toLowerCase().includes(busqueda)
    );

    if (producto) {
        mostrarProductoEPP(producto);
    } else {
        document.getElementById('productoCardEPP').style.display = 'none';
        resetearFormularioEPP();
    }
}

function mostrarProductoEPP(producto) {
    productoSeleccionadoEPP = producto;
    
    // Mostrar tarjeta
    document.getElementById('productoCardEPP').style.display = 'flex';
    document.getElementById('imagenProductoEPP').src = producto.imagen;
    document.getElementById('categoriaProductoEPP').textContent = producto.categoria;
    document.getElementById('nombreProductoEPP').textContent = producto.nombre;
    document.getElementById('codigoProductoEPP').textContent = producto.codigo;

    // Habilitar campos
    document.getElementById('cantidadEPP').disabled = false;
    document.getElementById('observacionesEPP').disabled = false;

    // Habilitar botón
    document.getElementById('btnAgregarEPP').disabled = false;
    document.getElementById('btnAgregarEPP').classList.remove('disabled');

    actualizarEstilosCampos();
}

function resetearFormularioEPP() {
    document.getElementById('cantidadEPP').disabled = true;
    document.getElementById('cantidadEPP').value = '1';
    document.getElementById('observacionesEPP').disabled = true;
    document.getElementById('observacionesEPP').value = '';
    document.getElementById('btnAgregarEPP').disabled = true;
    document.getElementById('btnAgregarEPP').classList.add('disabled');

    actualizarEstilosCampos();
}

function actualizarEstilosCampos() {
    const cantidadInput = document.getElementById('cantidadEPP');
    const observacionesInput = document.getElementById('observacionesEPP');

    if (cantidadInput.disabled) {
        cantidadInput.classList.add('disabled');
    } else {
        cantidadInput.classList.remove('disabled');
    }

    if (observacionesInput.disabled) {
        observacionesInput.classList.add('disabled');
    } else {
        observacionesInput.classList.remove('disabled');
    }
}

function agregarEPPAlPedido() {
    if (!productoSeleccionadoEPP) {
        alert('Por favor selecciona un producto');
        return;
    }

    const cantidad = document.getElementById('cantidadEPP').value;
    const observaciones = document.getElementById('observacionesEPP').value;

    // Simulación de agregar al pedido
    console.log('EPP Agregado:', {
        producto: productoSeleccionadoEPP,
        cantidad: cantidad || 1,
        observaciones: observaciones || 'Sin observaciones'
    });

    alert(`EPP "${productoSeleccionadoEPP.nombre}" agregado al pedido exitosamente.`);
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
