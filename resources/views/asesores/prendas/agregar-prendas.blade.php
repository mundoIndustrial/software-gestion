@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-6xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                ➕ Agregar Prendas a Cotización
            </h1>
            <p class="text-gray-600 dark:text-gray-400">
                Completa los datos de la prenda con especificaciones, color, tela y variaciones
            </p>
        </div>

        <!-- Formulario de Entrada -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 mb-8">
            <div class="space-y-6">
                <!-- TIPO DE PRENDA -->
                <div>
                    <label class="block text-sm font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                        <i class="fas fa-shirt"></i> TIPO DE PRENDA *
                    </label>
                    <div class="prenda-search-container">
                        <input 
                            type="text" 
                            id="nombre-prenda"
                            placeholder="BUSCA O ESCRIBE (CAMISA, CAMISETA, POLO...)"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                            onkeyup="buscarPrendas(this); mostrarSelectorVariantes(this);"
                            onchange="actualizarResumenFriendly(); mostrarSelectorVariantes(this);"
                        >
                        <div class="prenda-suggestions mt-2 space-y-1">
                            <div class="prenda-suggestion-item p-2 hover:bg-blue-50 dark:hover:bg-blue-900/30 cursor-pointer rounded" onclick="seleccionarPrenda('👔 CAMISA', this)">👔 CAMISA</div>
                            <div class="prenda-suggestion-item p-2 hover:bg-blue-50 dark:hover:bg-blue-900/30 cursor-pointer rounded" onclick="seleccionarPrenda('👕 CAMISETA', this)">👕 CAMISETA</div>
                            <div class="prenda-suggestion-item p-2 hover:bg-blue-50 dark:hover:bg-blue-900/30 cursor-pointer rounded" onclick="seleccionarPrenda('🎽 POLO', this)">🎽 POLO</div>
                            <div class="prenda-suggestion-item p-2 hover:bg-blue-50 dark:hover:bg-blue-900/30 cursor-pointer rounded" onclick="seleccionarPrenda('👖 PANTALÓN', this)">👖 PANTALÓN</div>
                        </div>
                    </div>
                </div>

                <!-- DESCRIPCIÓN -->
                <div>
                    <label class="block text-sm font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                        <i class="fas fa-sticky-note"></i> DESCRIPCIÓN
                    </label>
                    <textarea 
                        id="descripcion"
                        placeholder="DESCRIPCIÓN DE LA PRENDA, DETALLES ESPECIALES, LOGO, BORDADO, ESTAMPADO, ETC."
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                        rows="3"
                    ></textarea>
                </div>

                <!-- FOTOS DE LA PRENDA -->
                <div>
                    <label class="block text-sm font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                        <i class="fas fa-images"></i> FOTOS DE LA PRENDA (MÁX. 3)
                    </label>
                    <label class="block min-height-80 p-3 border-2 border-dashed border-blue-500 rounded-lg cursor-pointer text-center bg-blue-50 dark:bg-blue-900/20 hover:bg-blue-100 dark:hover:bg-blue-900/30 transition" ondrop="manejarDrop(event)" ondragover="event.preventDefault()" ondragleave="this.classList.remove('drag-over')">
                        <input type="file" id="fotos-prenda" class="input-file-single" accept="image/*" multiple onchange="agregarFotos(this.files, this.closest('label').nextElementSibling)" style="display: none;">
                        <div class="drop-zone-content">
                            <i class="fas fa-cloud-upload-alt text-2xl text-blue-500 mb-2"></i>
                            <p class="text-blue-600 dark:text-blue-400 font-semibold">ARRASTRA O CLIC</p>
                        </div>
                    </label>
                    <div class="fotos-preview mt-3 grid grid-cols-3 gap-2"></div>
                </div>

                <!-- COLOR, TELA Y REFERENCIA -->
                <div>
                    <label class="block text-sm font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                        <i class="fas fa-palette"></i> COLOR, TELA Y REFERENCIA
                    </label>
                    <div class="overflow-x-auto">
                        <table style="width: 100%; border-collapse: collapse; background: white; dark:bg-gray-700;">
                            <thead>
                                <tr style="background-color: #f0f0f0; dark:bg-gray-600 border-bottom: 2px solid #0066cc;">
                                    <th style="padding: 12px; text-align: left; font-weight: 600; color: #0066cc; border-right: 1px solid #ddd;">
                                        <i class="fas fa-palette"></i> Color
                                    </th>
                                    <th style="padding: 12px; text-align: left; font-weight: 600; color: #0066cc; border-right: 1px solid #ddd;">
                                        <i class="fas fa-cloth"></i> Tela
                                    </th>
                                    <th style="padding: 12px; text-align: left; font-weight: 600; color: #0066cc; border-right: 1px solid #ddd;">
                                        <i class="fas fa-barcode"></i> Referencia
                                    </th>
                                    <th style="padding: 12px; text-align: center; font-weight: 600; color: #0066cc;">
                                        <i class="fas fa-image"></i> Imagen Tela
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr style="border-bottom: 1px solid #ddd;">
                                    <td style="padding: 12px; border-right: 1px solid #ddd;">
                                        <input type="text" id="color" placeholder="Buscar o crear color..." style="width: 100%; padding: 8px; border: 1px solid #0066cc; border-radius: 4px; font-size: 0.9rem; dark:bg-gray-700 dark:text-white" onkeyup="buscarColor(this)">
                                    </td>
                                    <td style="padding: 12px; border-right: 1px solid #ddd;">
                                        <input type="text" id="tela" placeholder="Buscar o crear tela..." style="width: 100%; padding: 8px; border: 1px solid #0066cc; border-radius: 4px; font-size: 0.9rem; dark:bg-gray-700 dark:text-white" onkeyup="buscarTela(this)">
                                    </td>
                                    <td style="padding: 12px; border-right: 1px solid #ddd;">
                                        <input type="text" id="referencia" placeholder="Ej: REF-NAP-001" style="width: 100%; padding: 8px; border: 1px solid #0066cc; border-radius: 4px; font-size: 0.9rem; dark:bg-gray-700 dark:text-white">
                                    </td>
                                    <td style="padding: 12px; text-align: center;">
                                        <label style="display: block; min-height: 60px; padding: 0.5rem; border: 2px dashed #0066cc; border-radius: 6px; cursor: pointer; text-align: center; background: #f0f7ff; dark:bg-blue-900/20">
                                            <input type="file" id="foto-tela" class="input-file-tela" accept="image/*" multiple onchange="agregarFotoTela(this)" style="display: none;">
                                            <div class="drop-zone-content" style="font-size: 0.7rem;">
                                                <i class="fas fa-cloud-upload-alt" style="font-size: 0.9rem; color: #0066cc;"></i>
                                                <p style="margin: 0.25rem 0; color: #0066cc; font-weight: 500;">ARRASTRA O CLIC</p>
                                            </div>
                                        </label>
                                        <div class="foto-tela-preview mt-2 grid grid-cols-3 gap-2"></div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- VARIACIONES ESPECÍFICAS -->
                <div>
                    <label class="block text-sm font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                        <i class="fas fa-sliders-h"></i> VARIACIONES ESPECÍFICAS
                    </label>
                    <div class="overflow-x-auto">
                        <table style="width: 100%; border-collapse: collapse; background: white; dark:bg-gray-700; border: 1px solid #ddd; border-radius: 4px; overflow: hidden;">
                            <thead>
                                <tr style="background: linear-gradient(135deg, #0066cc, #0052a3); border-bottom: 2px solid #0066cc;">
                                    <th style="padding: 14px 12px; text-align: center; font-weight: 600; color: white; border-right: 1px solid #0052a3; width: 60px;">
                                        <i class="fas fa-check-circle"></i>
                                    </th>
                                    <th style="padding: 14px 12px; text-align: left; font-weight: 600; color: white; border-right: 1px solid #0052a3; width: 160px;">
                                        <i class="fas fa-list"></i> Variación
                                    </th>
                                    <th style="padding: 14px 12px; text-align: left; font-weight: 600; color: white;">
                                        <i class="fas fa-comment"></i> Observación
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- MANGA -->
                                <tr style="border-bottom: 1px solid #eee; background-color: #fafafa;">
                                    <td style="padding: 14px 12px; text-align: center; border-right: 1px solid #eee;">
                                        <input type="checkbox" class="aplica-manga" style="width: 18px; height: 18px; cursor: pointer; accent-color: #0066cc;" onchange="toggleMangaInput(this)">
                                    </td>
                                    <td style="padding: 14px 12px; border-right: 1px solid #eee; font-weight: 600; color: #0066cc; white-space: nowrap;">
                                        <i class="fas fa-shirt"></i> Manga
                                    </td>
                                    <td style="padding: 14px 12px;">
                                        <input type="text" class="manga-input" placeholder="Ej: manga larga..." style="width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem; opacity: 0.5; pointer-events: none; dark:bg-gray-700 dark:text-white" disabled>
                                    </td>
                                </tr>
                                
                                <!-- BOLSILLOS -->
                                <tr style="border-bottom: 1px solid #eee; background-color: white;">
                                    <td style="padding: 14px 12px; text-align: center; border-right: 1px solid #eee;">
                                        <input type="checkbox" class="aplica-bolsillos" style="width: 18px; height: 18px; cursor: pointer; accent-color: #0066cc;">
                                    </td>
                                    <td style="padding: 14px 12px; border-right: 1px solid #eee; font-weight: 600; color: #0066cc; white-space: nowrap;">
                                        <i class="fas fa-square"></i> Bolsillos
                                    </td>
                                    <td style="padding: 14px 12px;">
                                        <input type="text" class="bolsillos-input" placeholder="Ej: 4 bolsillos, con cierre..." style="width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem; dark:bg-gray-700 dark:text-white">
                                    </td>
                                </tr>
                                
                                <!-- BROCHE/BOTÓN -->
                                <tr style="border-bottom: 1px solid #eee; background-color: #fafafa;">
                                    <td style="padding: 14px 12px; text-align: center; border-right: 1px solid #eee;">
                                        <input type="checkbox" class="aplica-broche" style="width: 18px; height: 18px; cursor: pointer; accent-color: #0066cc;">
                                    </td>
                                    <td style="padding: 14px 12px; border-right: 1px solid #eee; font-weight: 600; color: #0066cc; white-space: nowrap;">
                                        <i class="fas fa-link"></i> Broche/Botón
                                    </td>
                                    <td style="padding: 14px 12px;">
                                        <input type="text" class="broche-input" placeholder="Ej: botones metálicos..." style="width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem; dark:bg-gray-700 dark:text-white">
                                    </td>
                                </tr>

                                <!-- PUÑO -->
                                <tr style="border-bottom: 1px solid #eee; background-color: white;">
                                    <td style="padding: 14px 12px; text-align: center; border-right: 1px solid #eee;">
                                        <input type="checkbox" class="aplica-puno" style="width: 18px; height: 18px; cursor: pointer; accent-color: #0066cc;">
                                    </td>
                                    <td style="padding: 14px 12px; border-right: 1px solid #eee; font-weight: 600; color: #0066cc; white-space: nowrap;">
                                        <i class="fas fa-ring"></i> Puño
                                    </td>
                                    <td style="padding: 14px 12px;">
                                        <input type="text" class="puno-input" placeholder="Ej: puño elástico..." style="width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem; dark:bg-gray-700 dark:text-white">
                                    </td>
                                </tr>

                                <!-- PROCESO -->
                                <tr style="border-bottom: 1px solid #eee; background-color: #fafafa;">
                                    <td style="padding: 14px 12px; text-align: center; border-right: 1px solid #eee;">
                                        <input type="checkbox" class="aplica-proceso" style="width: 18px; height: 18px; cursor: pointer; accent-color: #0066cc;">
                                    </td>
                                    <td style="padding: 14px 12px; border-right: 1px solid #eee; font-weight: 600; color: #0066cc; white-space: nowrap;">
                                        <i class="fas fa-cogs"></i> Proceso
                                    </td>
                                    <td style="padding: 14px 12px;">
                                        <input type="text" class="proceso-input" placeholder="Ej: lavado especial..." style="width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem; dark:bg-gray-700 dark:text-white">
                                    </td>
                                </tr>

                                <!-- REFLECTIVO -->
                                <tr style="border-bottom: 1px solid #eee; background-color: white;">
                                    <td style="padding: 14px 12px; text-align: center; border-right: 1px solid #eee;">
                                        <input type="checkbox" class="aplica-reflectivo" style="width: 18px; height: 18px; cursor: pointer; accent-color: #0066cc;">
                                    </td>
                                    <td style="padding: 14px 12px; border-right: 1px solid #eee; font-weight: 600; color: #0066cc; white-space: nowrap;">
                                        <i class="fas fa-lightbulb"></i> Reflectivo
                                    </td>
                                    <td style="padding: 14px 12px;">
                                        <input type="text" class="reflectivo-input" placeholder="Ej: reflectivo en espalda..." style="width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem; dark:bg-gray-700 dark:text-white">
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- TALLAS Y CANTIDADES -->
                <div>
                    <label class="block text-sm font-bold text-gray-900 dark:text-white mb-3">
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
                <div>
                    <button 
                        onclick="agregarPrenda()"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 rounded-lg transition-colors"
                    >
                        ➕ Agregar Prenda
                    </button>
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
                    No hay prendas agregadas aún. Completa los datos y haz clic en "Agregar Prenda".
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

function seleccionarPrenda(nombre, element) {
    document.getElementById('nombre-prenda').value = nombre;
    document.querySelectorAll('.prenda-suggestion-item').forEach(el => el.style.background = '');
    element.style.background = '#e3f2fd';
}

function buscarPrendas(input) {
    // Implementar búsqueda de prendas
}

function buscarColor(input) {
    // Implementar búsqueda de colores
}

function buscarTela(input) {
    // Implementar búsqueda de telas
}

function mostrarSelectorVariantes(input) {
    // Mostrar selectores dinámicos según tipo de prenda
}

function actualizarResumenFriendly() {
    // Actualizar resumen de prenda
}

function toggleMangaInput(checkbox) {
    const input = checkbox.closest('tr').querySelector('.manga-input');
    input.disabled = !checkbox.checked;
    input.style.opacity = checkbox.checked ? '1' : '0.5';
    input.style.pointerEvents = checkbox.checked ? 'auto' : 'none';
}

function agregarFotos(files, container) {
    const preview = container.querySelector('.fotos-preview') || container.nextElementSibling;
    Array.from(files).forEach(file => {
        const reader = new FileReader();
        reader.onload = (e) => {
            const img = document.createElement('img');
            img.src = e.target.result;
            img.style.width = '80px';
            img.style.height = '80px';
            img.style.objectFit = 'cover';
            img.style.borderRadius = '4px';
            preview.appendChild(img);
        };
        reader.readAsDataURL(file);
    });
}

function agregarFotoTela(input) {
    const container = input.closest('td');
    const preview = container.querySelector('.foto-tela-preview');
    Array.from(input.files).forEach(file => {
        const reader = new FileReader();
        reader.onload = (e) => {
            const img = document.createElement('img');
            img.src = e.target.result;
            img.style.width = '60px';
            img.style.height = '60px';
            img.style.objectFit = 'cover';
            img.style.borderRadius = '4px';
            preview.appendChild(img);
        };
        reader.readAsDataURL(file);
    });
}

function manejarDrop(event) {
    event.preventDefault();
    event.stopPropagation();
}

function agregarPrenda() {
    const nombre = document.getElementById('nombre-prenda').value.trim();
    const descripcion = document.getElementById('descripcion').value.trim();
    const color = document.getElementById('color').value.trim();
    const tela = document.getElementById('tela').value.trim();
    const referencia = document.getElementById('referencia').value.trim();
    
    const tallas = {
        S: parseInt(document.getElementById('talla-s').value) || 0,
        M: parseInt(document.getElementById('talla-m').value) || 0,
        L: parseInt(document.getElementById('talla-l').value) || 0,
        XL: parseInt(document.getElementById('talla-xl').value) || 0
    };

    if (!nombre || !color || !tela) {
        alert('Por favor completa los campos obligatorios (Prenda, Color, Tela)');
        return;
    }

    const prenda = {
        id: Date.now(),
        nombre,
        descripcion,
        color,
        tela,
        referencia,
        tallas,
        variaciones: {
            manga: document.querySelector('.aplica-manga').checked ? document.querySelector('.manga-input').value : null,
            bolsillos: document.querySelector('.aplica-bolsillos').checked ? document.querySelector('.bolsillos-input').value : null,
            broche: document.querySelector('.aplica-broche').checked ? document.querySelector('.broche-input').value : null,
            puno: document.querySelector('.aplica-puno').checked ? document.querySelector('.puno-input').value : null,
            proceso: document.querySelector('.aplica-proceso').checked ? document.querySelector('.proceso-input').value : null,
            reflectivo: document.querySelector('.aplica-reflectivo').checked ? document.querySelector('.reflectivo-input').value : null,
        }
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
            
            <div class="grid grid-cols-2 md:grid-cols-3 gap-2 text-sm mb-3">
                <p><strong>Color:</strong> ${prenda.color}</p>
                <p><strong>Tela:</strong> ${prenda.tela}</p>
                <p><strong>Referencia:</strong> ${prenda.referencia || 'N/A'}</p>
                ${prenda.descripcion ? `<p class="col-span-full"><strong>Descripción:</strong> ${prenda.descripcion}</p>` : ''}
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
    document.getElementById('descripcion').value = '';
    document.getElementById('color').value = '';
    document.getElementById('tela').value = '';
    document.getElementById('referencia').value = '';
    document.getElementById('talla-s').value = '0';
    document.getElementById('talla-m').value = '0';
    document.getElementById('talla-l').value = '0';
    document.getElementById('talla-xl').value = '0';
    document.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = false);
    document.querySelectorAll('.manga-input, .bolsillos-input, .broche-input, .puno-input, .proceso-input, .reflectivo-input').forEach(input => input.value = '');
    document.getElementById('nombre-prenda').focus();
}
</script>
@endsection
