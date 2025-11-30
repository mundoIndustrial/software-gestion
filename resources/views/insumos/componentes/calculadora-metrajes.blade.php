<link rel="stylesheet" href="{{ asset('css/inventario-telas/inventario.css') }}">

<div class="min-h-screen bg-gray-50">
    {{-- Header Principal Blanco --}}
    <div class="bg-white border-b border-gray-200 shadow-sm w-full m-0">
        <div class="px-6 py-6">
            {{-- Título y Descripción --}}
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-gray-900 flex items-center gap-3">
                    <span class="material-symbols-rounded text-4xl text-blue-600">straighten</span>
                    Cálculo de Metrajes
                </h1>
                <p class="text-gray-600 text-sm mt-2">Calcula el metraje requerido para tus proyectos</p>
            </div>
        </div>
    </div>

    <div class="px-6 py-8 w-full">
        {{-- Tarjetas de calculadoras --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            {{-- Calculadora 1: Largo x Ancho --}}
            <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition">
                <div class="flex items-center mb-4">
                    <div class="bg-blue-100 rounded-full p-3 mr-4">
                        <i class="fas fa-ruler text-blue-600 text-xl"></i>
                    </div>
                    <h2 class="text-xl font-bold text-gray-900">Largo x Ancho</h2>
                </div>
                <p class="text-gray-600 text-sm mb-4">Calcula metraje por dimensiones</p>
                <button onclick="abrirCalculadora('largoAncho')" class="w-full bg-blue-600 text-white font-semibold py-2 rounded-lg hover:bg-blue-700 transition">
                    Abrir Calculadora
                </button>
            </div>

            {{-- Calculadora 2: Cantidad x Metraje --}}
            <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition">
                <div class="flex items-center mb-4">
                    <div class="bg-green-100 rounded-full p-3 mr-4">
                        <i class="fas fa-boxes text-green-600 text-xl"></i>
                    </div>
                    <h2 class="text-xl font-bold text-gray-900">Cantidad x Metraje</h2>
                </div>
                <p class="text-gray-600 text-sm mb-4">Calcula metraje por cantidad de prendas</p>
                <button onclick="abrirCalculadora('cantidadMetraje')" class="w-full bg-green-600 text-white font-semibold py-2 rounded-lg hover:bg-green-700 transition">
                    Abrir Calculadora
                </button>
            </div>

            {{-- Calculadora 3: Personalizada --}}
            <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition">
                <div class="flex items-center mb-4">
                    <div class="bg-purple-100 rounded-full p-3 mr-4">
                        <i class="fas fa-calculator text-purple-600 text-xl"></i>
                    </div>
                    <h2 class="text-xl font-bold text-gray-900">Personalizada</h2>
                </div>
                <p class="text-gray-600 text-sm mb-4">Crea tu propia fórmula de cálculo</p>
                <button onclick="abrirCalculadora('personalizada')" class="w-full bg-purple-600 text-white font-semibold py-2 rounded-lg hover:bg-purple-700 transition">
                    Abrir Calculadora
                </button>
            </div>
        </div>

        {{-- Historial de cálculos --}}
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Historial de Cálculos</h2>
            <div id="historialContainer" class="space-y-3">
                <p class="text-gray-500 text-center py-8">No hay cálculos aún. Realiza tu primer cálculo.</p>
            </div>
        </div>
    </div>
</div>

{{-- Modal de Calculadora --}}
<div id="calculadoraModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        {{-- Header --}}
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white px-6 py-4 flex justify-between items-center">
            <h3 class="text-xl font-bold" id="modalTitle">Calculadora</h3>
            <button onclick="cerrarCalculadora()" class="text-white hover:bg-blue-800 p-2 rounded">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>

        {{-- Contenido --}}
        <div class="p-6">
            <div id="modalContent"></div>
        </div>

        {{-- Footer --}}
        <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3 border-t">
            <button onclick="cerrarCalculadora()" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400 transition">
                Cerrar
            </button>
            <button onclick="guardarCalculo()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                Guardar Cálculo
            </button>
        </div>
    </div>
</div>

<script>
    let calculoActual = null;

    function abrirCalculadora(tipo) {
        const modal = document.getElementById('calculadoraModal');
        const modalTitle = document.getElementById('modalTitle');
        const modalContent = document.getElementById('modalContent');

        if (tipo === 'largoAncho') {
            modalTitle.textContent = 'Largo x Ancho';
            modalContent.innerHTML = `
                <div class="space-y-4">
                    <div>
                        <label for="largo" class="block text-sm font-medium text-gray-700 mb-2">Largo (m)</label>
                        <input type="number" id="largo" placeholder="Ej: 2.5" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" step="0.01">
                    </div>
                    <div>
                        <label for="ancho" class="block text-sm font-medium text-gray-700 mb-2">Ancho (m)</label>
                        <input type="number" id="ancho" placeholder="Ej: 1.5" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" step="0.01">
                    </div>
                    <div class="bg-blue-50 p-4 rounded-lg">
                        <p class="text-sm text-gray-600 mb-2">Resultado:</p>
                        <p class="text-2xl font-bold text-blue-600" id="resultado">0 m²</p>
                    </div>
                </div>
            `;
            document.getElementById('largo').addEventListener('input', () => calcularLargoAncho());
            document.getElementById('ancho').addEventListener('input', () => calcularLargoAncho());
        } else if (tipo === 'cantidadMetraje') {
            modalTitle.textContent = 'Cantidad x Metraje';
            modalContent.innerHTML = `
                <div class="space-y-4">
                    <div>
                        <label for="cantidad" class="block text-sm font-medium text-gray-700 mb-2">Cantidad de Prendas</label>
                        <input type="number" id="cantidad" placeholder="Ej: 50" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500" step="1">
                    </div>
                    <div>
                        <label for="metraje" class="block text-sm font-medium text-gray-700 mb-2">Metraje por Prenda (m)</label>
                        <input type="number" id="metraje" placeholder="Ej: 1.2" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500" step="0.01">
                    </div>
                    <div class="bg-green-50 p-4 rounded-lg">
                        <p class="text-sm text-gray-600 mb-2">Resultado:</p>
                        <p class="text-2xl font-bold text-green-600" id="resultado">0 m</p>
                    </div>
                </div>
            `;
            document.getElementById('cantidad').addEventListener('input', () => calcularCantidadMetraje());
            document.getElementById('metraje').addEventListener('input', () => calcularCantidadMetraje());
        } else if (tipo === 'personalizada') {
            modalTitle.textContent = 'Calculadora Personalizada';
            modalContent.innerHTML = `
                <div class="space-y-4">
                    <div>
                        <label for="formula" class="block text-sm font-medium text-gray-700 mb-2">Fórmula (ej: 2 * 3 + 1.5)</label>
                        <input type="text" id="formula" placeholder="Ingresa tu fórmula" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                    </div>
                    <div class="bg-purple-50 p-4 rounded-lg">
                        <p class="text-sm text-gray-600 mb-2">Resultado:</p>
                        <p class="text-2xl font-bold text-purple-600" id="resultado">0</p>
                    </div>
                </div>
            `;
            document.getElementById('formula').addEventListener('input', () => calcularPersonalizada());
        }

        modal.classList.remove('hidden');
    }

    function cerrarCalculadora() {
        document.getElementById('calculadoraModal').classList.add('hidden');
    }

    function calcularLargoAncho() {
        const largo = parseFloat(document.getElementById('largo').value) || 0;
        const ancho = parseFloat(document.getElementById('ancho').value) || 0;
        const resultado = largo * ancho;
        document.getElementById('resultado').textContent = resultado.toFixed(2) + ' m²';
        calculoActual = { tipo: 'Largo x Ancho', valores: { largo, ancho }, resultado };
    }

    function calcularCantidadMetraje() {
        const cantidad = parseFloat(document.getElementById('cantidad').value) || 0;
        const metraje = parseFloat(document.getElementById('metraje').value) || 0;
        const resultado = cantidad * metraje;
        document.getElementById('resultado').textContent = resultado.toFixed(2) + ' m';
        calculoActual = { tipo: 'Cantidad x Metraje', valores: { cantidad, metraje }, resultado };
    }

    function calcularPersonalizada() {
        const formula = document.getElementById('formula').value;
        try {
            const resultado = Function('"use strict"; return (' + formula + ')')();
            document.getElementById('resultado').textContent = resultado;
            calculoActual = { tipo: 'Personalizada', formula, resultado };
        } catch (e) {
            document.getElementById('resultado').textContent = 'Error en fórmula';
        }
    }

    function guardarCalculo() {
        if (!calculoActual) return;

        const historial = JSON.parse(localStorage.getItem('historialCalculos') || '[]');
        historial.unshift({
            ...calculoActual,
            fecha: new Date().toLocaleString('es-ES')
        });
        localStorage.setItem('historialCalculos', JSON.stringify(historial.slice(0, 50)));

        cargarHistorial();
        cerrarCalculadora();
    }

    function cargarHistorial() {
        const historial = JSON.parse(localStorage.getItem('historialCalculos') || '[]');
        const container = document.getElementById('historialContainer');

        if (historial.length === 0) {
            container.innerHTML = '<p class="text-gray-500 text-center py-8">No hay cálculos aún. Realiza tu primer cálculo.</p>';
            return;
        }

        container.innerHTML = historial.map((calc, idx) => `
            <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="font-semibold text-gray-900">${calc.tipo}</p>
                        <p class="text-sm text-gray-500">${calc.fecha}</p>
                        <p class="text-lg font-bold text-blue-600 mt-2">${calc.resultado}</p>
                    </div>
                    <button onclick="eliminarCalculo(${idx})" class="text-red-600 hover:text-red-800">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `).join('');
    }

    function eliminarCalculo(idx) {
        const historial = JSON.parse(localStorage.getItem('historialCalculos') || '[]');
        historial.splice(idx, 1);
        localStorage.setItem('historialCalculos', JSON.stringify(historial));
        cargarHistorial();
    }

    document.addEventListener('DOMContentLoaded', cargarHistorial);
</script>
