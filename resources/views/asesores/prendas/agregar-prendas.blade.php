@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                ➕ Agregar Prendas a Cotización
            </h1>
            <p class="text-gray-600 dark:text-gray-400">
                Escribe el nombre de la prenda y el sistema reconocerá automáticamente el tipo
            </p>
        </div>

        <!-- Formulario de Entrada -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 mb-8">
            <div class="space-y-4">
                <!-- Input de Nombre -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Nombre de Prenda
                    </label>
                    <input 
                        type="text" 
                        id="nombre-prenda"
                        placeholder="Ej: JEAN NAPOLES AZUL, CAMISA DRILL BORNEO NARANJA"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                        @keyup="reconocerPrenda"
                    >
                </div>

                <!-- Mensaje de Reconocimiento -->
                <div id="reconocimiento-container" class="hidden">
                    <div class="bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-700 rounded-lg p-4">
                        <p class="text-sm text-blue-800 dark:text-blue-300">
                            ✅ Sistema reconoce: <strong id="tipo-reconocido"></strong>
                        </p>
                    </div>
                </div>

                <!-- Selector Dinámico -->
                <div id="selector-container" class="hidden">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Manga -->
                        <div id="campo-manga" class="hidden">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Manga
                            </label>
                            <select id="manga" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                                <option value="">Seleccionar...</option>
                                <option value="Larga">Larga</option>
                                <option value="Corta">Corta</option>
                                <option value="3/4">3/4</option>
                            </select>
                        </div>

                        <!-- Bolsillos -->
                        <div id="campo-bolsillos" class="hidden">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Bolsillos
                            </label>
                            <select id="bolsillos" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                                <option value="">Seleccionar...</option>
                                <option value="Sí">Sí</option>
                                <option value="No">No</option>
                            </select>
                        </div>

                        <!-- Broche -->
                        <div id="campo-broche" class="hidden">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Broche
                            </label>
                            <select id="broche" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                                <option value="">Seleccionar...</option>
                                <option value="Metálico">Metálico</option>
                                <option value="Plástico">Plástico</option>
                            </select>
                        </div>

                        <!-- Género -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Género
                            </label>
                            <select id="genero" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                                <option value="">Seleccionar...</option>
                                <option value="Dama">Dama</option>
                                <option value="Caballero">Caballero</option>
                                <option value="Unisex">Unisex</option>
                            </select>
                        </div>

                        <!-- Color -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Color
                            </label>
                            <select id="color" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                                <option value="">Seleccionar...</option>
                                <option value="Azul">Azul</option>
                                <option value="Negro">Negro</option>
                                <option value="Gris">Gris</option>
                                <option value="Blanco">Blanco</option>
                                <option value="Naranja">Naranja</option>
                                <option value="Rojo">Rojo</option>
                            </select>
                        </div>

                        <!-- Tela -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Tela
                            </label>
                            <select id="tela" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                                <option value="">Seleccionar...</option>
                                <option value="NAPOLES">NAPOLES (REF-NAP-001)</option>
                                <option value="DRILL BORNEO">DRILL BORNEO (REF-DB-001)</option>
                                <option value="OXFORD">OXFORD (REF-OX-001)</option>
                                <option value="JERSEY">JERSEY (REF-JER-001)</option>
                                <option value="LINO">LINO (REF-LIN-001)</option>
                            </select>
                        </div>

                        <!-- Reflectivo -->
                        <div id="campo-reflectivo" class="hidden">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Reflectivo
                            </label>
                            <select id="reflectivo" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                                <option value="">Seleccionar...</option>
                                <option value="Sí">Sí</option>
                                <option value="No">No</option>
                            </select>
                        </div>
                    </div>

                    <!-- Tallas y Cantidades -->
                    <div class="mt-6">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                            Tallas y Cantidades
                        </label>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                            <div>
                                <label class="text-xs text-gray-600 dark:text-gray-400">S</label>
                                <input type="number" id="talla-s" min="0" value="0" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                            </div>
                            <div>
                                <label class="text-xs text-gray-600 dark:text-gray-400">M</label>
                                <input type="number" id="talla-m" min="0" value="0" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                            </div>
                            <div>
                                <label class="text-xs text-gray-600 dark:text-gray-400">L</label>
                                <input type="number" id="talla-l" min="0" value="0" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                            </div>
                            <div>
                                <label class="text-xs text-gray-600 dark:text-gray-400">XL</label>
                                <input type="number" id="talla-xl" min="0" value="0" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                            </div>
                        </div>
                    </div>

                    <!-- Botón Agregar -->
                    <div class="mt-6">
                        <button 
                            onclick="agregarPrenda()"
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 rounded-lg transition-colors"
                        >
                            ➕ Agregar Prenda
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla de Prendas Agregadas -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
                📋 Prendas Agregadas (<span id="contador-prendas">0</span>)
            </h2>

            <div id="prendas-tabla" class="space-y-3">
                <p class="text-gray-500 dark:text-gray-400 text-center py-8">
                    No hay prendas agregadas aún. Comienza escribiendo el nombre de una prenda.
                </p>
            </div>

            <!-- Botones de Acción -->
            <div id="botones-accion" class="hidden mt-6 flex gap-3">
                <button class="flex-1 bg-green-600 hover:bg-green-700 text-white font-semibold py-2 rounded-lg transition-colors">
                    💾 Guardar Cotización
                </button>
                <button class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 rounded-lg transition-colors">
                    📤 Enviar Cotización
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let prendas = [];

function reconocerPrenda() {
    const nombre = document.getElementById('nombre-prenda').value.trim();
    
    if (!nombre) {
        document.getElementById('reconocimiento-container').classList.add('hidden');
        document.getElementById('selector-container').classList.add('hidden');
        return;
    }

    // Fetch a API para reconocer
    fetch('/api/prenda/reconocer', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ nombre: nombre })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            document.getElementById('tipo-reconocido').textContent = data.tipo.nombre;
            document.getElementById('reconocimiento-container').classList.remove('hidden');
            
            // Mostrar selector dinámico
            mostrarSelector(data.variaciones);
        } else {
            document.getElementById('reconocimiento-container').classList.add('hidden');
            document.getElementById('selector-container').classList.add('hidden');
        }
    })
    .catch(err => {
        console.error('Error:', err);
        document.getElementById('reconocimiento-container').classList.add('hidden');
        document.getElementById('selector-container').classList.add('hidden');
    });
}

function mostrarSelector(variaciones) {
    // Mostrar/ocultar campos según variaciones
    document.getElementById('campo-manga').classList.toggle('hidden', !variaciones.tiene_manga);
    document.getElementById('campo-bolsillos').classList.toggle('hidden', !variaciones.tiene_bolsillos);
    document.getElementById('campo-broche').classList.toggle('hidden', !variaciones.tiene_broche);
    document.getElementById('campo-reflectivo').classList.toggle('hidden', !variaciones.tiene_reflectivo);
    
    document.getElementById('selector-container').classList.remove('hidden');
}

function agregarPrenda() {
    const nombre = document.getElementById('nombre-prenda').value.trim();
    const manga = document.getElementById('manga').value;
    const bolsillos = document.getElementById('bolsillos').value;
    const broche = document.getElementById('broche').value;
    const genero = document.getElementById('genero').value;
    const color = document.getElementById('color').value;
    const tela = document.getElementById('tela').value;
    const reflectivo = document.getElementById('reflectivo').value;
    
    const tallas = {
        S: parseInt(document.getElementById('talla-s').value) || 0,
        M: parseInt(document.getElementById('talla-m').value) || 0,
        L: parseInt(document.getElementById('talla-l').value) || 0,
        XL: parseInt(document.getElementById('talla-xl').value) || 0
    };

    if (!nombre || !genero || !color || !tela) {
        alert('Por favor completa los campos obligatorios');
        return;
    }

    const prenda = {
        id: Date.now(),
        nombre,
        manga: manga || '-',
        bolsillos: bolsillos || '-',
        broche: broche || '-',
        genero,
        color,
        tela,
        reflectivo: reflectivo || '-',
        tallas
    };

    prendas.push(prenda);
    actualizarTabla();
    limpiarFormulario();
}

function actualizarTabla() {
    const tabla = document.getElementById('prendas-tabla');
    const contador = document.getElementById('contador-prendas');
    const botones = document.getElementById('botones-accion');

    contador.textContent = prendas.length;

    if (prendas.length === 0) {
        tabla.innerHTML = '<p class="text-gray-500 dark:text-gray-400 text-center py-8">No hay prendas agregadas aún.</p>';
        botones.classList.add('hidden');
        return;
    }

    botones.classList.remove('hidden');

    tabla.innerHTML = prendas.map((prenda, index) => `
        <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 bg-gray-50 dark:bg-gray-700/50">
            <div class="flex justify-between items-start mb-3">
                <h3 class="font-semibold text-gray-900 dark:text-white">
                    ${index + 1}️⃣ ${prenda.nombre}
                </h3>
                <button onclick="eliminarPrenda(${prenda.id})" class="text-red-600 hover:text-red-700 font-semibold">
                    🗑️ Eliminar
                </button>
            </div>
            
            <div class="grid grid-cols-2 md:grid-cols-4 gap-2 text-sm mb-3">
                ${prenda.manga !== '-' ? `<p><strong>Manga:</strong> ${prenda.manga}</p>` : ''}
                ${prenda.bolsillos !== '-' ? `<p><strong>Bolsillos:</strong> ${prenda.bolsillos}</p>` : ''}
                ${prenda.broche !== '-' ? `<p><strong>Broche:</strong> ${prenda.broche}</p>` : ''}
                <p><strong>Género:</strong> ${prenda.genero}</p>
                <p><strong>Color:</strong> ${prenda.color}</p>
                <p><strong>Tela:</strong> ${prenda.tela}</p>
                ${prenda.reflectivo !== '-' ? `<p><strong>Reflectivo:</strong> ${prenda.reflectivo}</p>` : ''}
            </div>

            <div class="bg-white dark:bg-gray-800 rounded p-3">
                <p class="text-xs font-semibold text-gray-600 dark:text-gray-400 mb-2">Tallas:</p>
                <div class="flex gap-2 text-sm">
                    <span class="px-2 py-1 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded">S: ${prenda.tallas.S}</span>
                    <span class="px-2 py-1 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded">M: ${prenda.tallas.M}</span>
                    <span class="px-2 py-1 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded">L: ${prenda.tallas.L}</span>
                    <span class="px-2 py-1 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded">XL: ${prenda.tallas.XL}</span>
                </div>
            </div>
        </div>
    `).join('');
}

function eliminarPrenda(id) {
    prendas = prendas.filter(p => p.id !== id);
    actualizarTabla();
}

function limpiarFormulario() {
    document.getElementById('nombre-prenda').value = '';
    document.getElementById('manga').value = '';
    document.getElementById('bolsillos').value = '';
    document.getElementById('broche').value = '';
    document.getElementById('genero').value = '';
    document.getElementById('color').value = '';
    document.getElementById('tela').value = '';
    document.getElementById('reflectivo').value = '';
    document.getElementById('talla-s').value = '0';
    document.getElementById('talla-m').value = '0';
    document.getElementById('talla-l').value = '0';
    document.getElementById('talla-xl').value = '0';
    document.getElementById('reconocimiento-container').classList.add('hidden');
    document.getElementById('selector-container').classList.add('hidden');
    document.getElementById('nombre-prenda').focus();
}
</script>
@endsection
