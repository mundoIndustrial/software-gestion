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
                            onkeyup="buscarPrendas(this); mostrarSelectorVariantes(this); limpiarError(this);"
                            onchange="if(typeof actualizarResumenFriendly === 'function') { actualizarResumenFriendly(); } mostrarSelectorVariantes(this);"
                            onfocus="limpiarError(this);"
                        >
                        <div class="prenda-suggestions mt-2 space-y-1">
                            <div class="prenda-suggestion-item p-2 hover:bg-blue-50 dark:hover:bg-blue-900/30 cursor-pointer rounded" onclick="seleccionarPrenda('👔 CAMISA', this)">👔 CAMISA</div>
                            <div class="prenda-suggestion-item p-2 hover:bg-blue-50 dark:hover:bg-blue-900/30 cursor-pointer rounded" onclick="seleccionarPrenda(' CAMISETA', this)"> CAMISETA</div>
                            <div class="prenda-suggestion-item p-2 hover:bg-blue-50 dark:hover:bg-blue-900/30 cursor-pointer rounded" onclick="seleccionarPrenda('🎽 POLO', this)">🎽 POLO</div>
                            <div class="prenda-suggestion-item p-2 hover:bg-blue-50 dark:hover:bg-blue-900/30 cursor-pointer rounded" onclick="seleccionarPrenda('👖 PANTALÓN', this)">👖 PANTALÓN</div>
                        </div>
                    </div>
                </div>

                <!-- PRENDA DE BODEGA -->
                <div>
                    <label class="block text-sm font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                        <i class="fas fa-warehouse"></i> PRENDA DE BODEGA
                    </label>
                    <div class="flex items-center gap-3">
                        <input 
                            type="checkbox" 
                            id="prenda-bodega"
                            class="w-5 h-5 border-2 border-blue-500 rounded cursor-pointer accent-blue-500"
                        >
                        <label for="prenda-bodega" class="text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                            Marcar si esta prenda viene de bodega
                        </label>
                    </div>
                </div>

                <!-- DESCRIPCIÓN -->
                <div>
                    <label class="block text-sm font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                        <i class="fas fa-sticky-note"></i> DESCRIPCIÓN
                    </label>
                    <textarea 
                        id="descripcion"
                        placeholder="DESCRIPCIÓN DE LA PRENDA, DETALLES ESPECIALES, ETC."
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
                                        <input type="text" id="color" placeholder="Buscar o crear color..." style="width: 100%; padding: 8px; border: 1px solid #0066cc; border-radius: 4px; font-size: 0.9rem; dark:bg-gray-700 dark:text-white" onkeyup="buscarColor(this)" onfocus="limpiarError(this);">
                                    </td>
                                    <td style="padding: 12px; border-right: 1px solid #ddd;">
                                        <input type="text" id="tela" placeholder="Buscar o crear tela..." style="width: 100%; padding: 8px; border: 1px solid #0066cc; border-radius: 4px; font-size: 0.9rem; dark:bg-gray-700 dark:text-white" onkeyup="buscarTela(this)" onfocus="limpiarError(this);">
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
                    
                    <!-- Vista Desktop (tabla) -->
                    <div class="variaciones-tabla-desktop" style="display: none; overflow-x: auto; border-radius: 6px; border: 1px solid #ddd;">
                        <table style="width: 100%; border-collapse: collapse; background: white; min-width: 600px;">
                            <thead>
                                <tr style="background: linear-gradient(135deg, #0066cc, #0052a3);">
                                    <th style="padding: 14px 12px; text-align: center; font-weight: 600; color: white; border-right: 1px solid #0052a3; width: 8%; min-width: 50px;">
                                        <i class="fas fa-check-circle"></i>
                                    </th>
                                    <th style="padding: 14px 12px; text-align: left; font-weight: 600; color: white; border-right: 1px solid #0052a3; width: 20%; min-width: 120px;">
                                        <i class="fas fa-list"></i> Variación
                                    </th>
                                    <th style="padding: 14px 12px; text-align: left; font-weight: 600; color: white; width: 72%; min-width: 300px;">
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
                                    <td style="padding: 14px 12px; word-break: break-word;">
                                        <input type="text" id="manga-input" class="manga-input" placeholder="Ej: manga larga, corta..." list="manga-options" style="width: 100%; max-width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem; opacity: 0.5; pointer-events: none; box-sizing: border-box; dark:bg-gray-700 dark:text-white" disabled>
                                        <datalist id="manga-options">
                                            <!-- Las opciones se cargarán dinámicamente desde el API -->
                                        </datalist>
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
                                    <td style="padding: 14px 12px; word-break: break-word;">
                                        <input type="text" class="bolsillos-input" placeholder="Ej: 4 bolsillos, con cierre..." style="width: 100%; max-width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem; box-sizing: border-box; dark:bg-gray-700 dark:text-white">
                                    </td>
                                </tr>
                                
                                <!-- BROCHE/BOTÓN -->
                                <tr style="border-bottom: 1px solid #eee; background-color: #fafafa;">
                                    <td style="padding: 14px 12px; text-align: center; border-right: 1px solid #eee;">
                                        <input type="checkbox" class="aplica-broche" style="width: 18px; height: 18px; cursor: pointer; accent-color: #0066cc;" onchange="toggleBrocheInputs(this)">
                                    </td>
                                    <td style="padding: 14px 12px; border-right: 1px solid #eee; font-weight: 600; color: #0066cc; white-space: nowrap;">
                                        <i class="fas fa-link"></i> Broche/Botón
                                    </td>
                                    <td style="padding: 14px 12px; word-break: break-word;">
                                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; width: 100%;">
                                            <select id="broche-tipo" class="broche-tipo-select" style="width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem; opacity: 0.5; pointer-events: none; box-sizing: border-box; dark:bg-gray-700 dark:text-white" disabled>
                                                <option value="">-- Selecciona --</option>
                                                <option value="1">Broche</option>
                                                <option value="2">Botón</option>
                                            </select>
                                            <input type="text" class="broche-obs-input" placeholder="Ej: metálicos, 5mm..." style="width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem; opacity: 0.5; pointer-events: none; box-sizing: border-box; dark:bg-gray-700 dark:text-white" disabled>
                                        </div>
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
                                    <td style="padding: 14px 12px; word-break: break-word;">
                                        <input type="text" class="puno-input" placeholder="Ej: puño elástico..." style="width: 100%; max-width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem; box-sizing: border-box; dark:bg-gray-700 dark:text-white">
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
                                    <td style="padding: 14px 12px; word-break: break-word;">
                                        <input type="text" class="proceso-input" placeholder="Ej: lavado especial..." style="width: 100%; max-width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem; box-sizing: border-box; dark:bg-gray-700 dark:text-white">
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Vista Mobile (cards) -->
                    <div class="variaciones-cards-mobile" style="display: none;">
                        <!-- MANGA -->
                        <div style="background: white; border: 1px solid #ddd; border-radius: 6px; padding: 12px; margin-bottom: 12px; overflow: hidden; word-wrap: break-word; dark:bg-gray-700;">
                            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px;">
                                <input type="checkbox" class="aplica-manga" style="width: 18px; height: 18px; flex-shrink: 0; cursor: pointer; accent-color: #0066cc;" onchange="toggleMangaInputMobile(this)">
                                <label style="font-weight: 600; color: #0066cc; cursor: pointer; flex: 1; overflow: hidden; word-break: break-word;"><i class="fas fa-shirt"></i> Manga</label>
                            </div>
                            <input type="text" id="manga-input-mobile" class="manga-input" placeholder="Ej: manga larga, corta..." list="manga-options-mobile" style="width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem; opacity: 0.5; pointer-events: none; box-sizing: border-box; word-break: break-word; dark:bg-gray-600 dark:text-white" disabled>
                            <datalist id="manga-options-mobile">
                                <!-- Las opciones se cargarán dinámicamente desde el API -->
                            </datalist>
                        </div>

                        <!-- BOLSILLOS -->
                        <div style="background: white; border: 1px solid #ddd; border-radius: 6px; padding: 12px; margin-bottom: 12px; overflow: hidden; word-wrap: break-word; dark:bg-gray-700;">
                            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px;">
                                <input type="checkbox" class="aplica-bolsillos" style="width: 18px; height: 18px; flex-shrink: 0; cursor: pointer; accent-color: #0066cc;">
                                <label style="font-weight: 600; color: #0066cc; cursor: pointer; flex: 1; overflow: hidden; word-break: break-word;"><i class="fas fa-square"></i> Bolsillos</label>
                            </div>
                            <input type="text" class="bolsillos-input" placeholder="Ej: 4 bolsillos, con cierre..." style="width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem; box-sizing: border-box; word-break: break-word; dark:bg-gray-600 dark:text-white">
                        </div>

                        <!-- BROCHE/BOTÓN -->
                        <div style="background: white; border: 1px solid #ddd; border-radius: 6px; padding: 12px; margin-bottom: 12px; overflow: hidden; word-wrap: break-word; dark:bg-gray-700;">
                            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px;">
                                <input type="checkbox" class="aplica-broche" style="width: 18px; height: 18px; flex-shrink: 0; cursor: pointer; accent-color: #0066cc;" onchange="toggleBrocheInputsMobile(this)">
                                <label style="font-weight: 600; color: #0066cc; cursor: pointer; flex: 1; overflow: hidden; word-break: break-word;"><i class="fas fa-link"></i> Broche/Botón</label>
                            </div>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; width: 100%; box-sizing: border-box;">
                                <select id="broche-tipo-mobile" class="broche-tipo-select" style="width: 100%; padding: 8px 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.85rem; opacity: 0.5; pointer-events: none; box-sizing: border-box; word-break: break-word; dark:bg-gray-600 dark:text-white" disabled>
                                    <option value="">-- Selecciona --</option>
                                    <option value="1">Broche</option>
                                    <option value="2">Botón</option>
                                </select>
                                <input type="text" class="broche-obs-input" placeholder="Ej: metálicos..." style="width: 100%; padding: 8px 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.85rem; opacity: 0.5; pointer-events: none; box-sizing: border-box; word-break: break-word; dark:bg-gray-600 dark:text-white" disabled>
                            </div>
                        </div>

                        <!-- PUÑO -->
                        <div style="background: white; border: 1px solid #ddd; border-radius: 6px; padding: 12px; margin-bottom: 12px; overflow: hidden; word-wrap: break-word; dark:bg-gray-700;">
                            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px;">
                                <input type="checkbox" class="aplica-puno" style="width: 18px; height: 18px; flex-shrink: 0; cursor: pointer; accent-color: #0066cc;">
                                <label style="font-weight: 600; color: #0066cc; cursor: pointer; flex: 1; overflow: hidden; word-break: break-word;"><i class="fas fa-ring"></i> Puño</label>
                            </div>
                            <input type="text" class="puno-input" placeholder="Ej: puño elástico..." style="width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem; box-sizing: border-box; word-break: break-word; dark:bg-gray-600 dark:text-white">
                        </div>

                        <!-- PROCESO -->
                        <div style="background: white; border: 1px solid #ddd; border-radius: 6px; padding: 12px; margin-bottom: 12px; overflow: hidden; word-wrap: break-word; dark:bg-gray-700;">
                            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px;">
                                <input type="checkbox" class="aplica-proceso" style="width: 18px; height: 18px; flex-shrink: 0; cursor: pointer; accent-color: #0066cc;">
                                <label style="font-weight: 600; color: #0066cc; cursor: pointer; flex: 1; overflow: hidden; word-break: break-word;"><i class="fas fa-cogs"></i> Proceso</label>
                            </div>
                            <input type="text" class="proceso-input" placeholder="Ej: lavado especial..." style="width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem; box-sizing: border-box; word-break: break-word; dark:bg-gray-600 dark:text-white">
                        </div>
                    </div>

                    <style>
                        * {
                            box-sizing: border-box;
                        }

                        .variaciones-tabla-desktop {
                            overflow-x: auto;
                            -webkit-overflow-scrolling: touch;
                            border-radius: 6px;
                            border: 1px solid #ddd;
                        }

                        .variaciones-tabla-desktop table {
                            width: 100%;
                            min-width: 600px;
                            border-collapse: collapse;
                        }

                        .variaciones-tabla-desktop table th,
                        .variaciones-tabla-desktop table td {
                            overflow-wrap: break-word;
                            word-wrap: break-word;
                            word-break: break-word;
                            hyphens: auto;
                        }

                        .variaciones-tabla-desktop input,
                        .variaciones-tabla-desktop select {
                            max-width: 100%;
                            box-sizing: border-box;
                        }

                        .variaciones-cards-mobile {
                            display: none;
                            width: 100%;
                        }

                        .variaciones-cards-mobile > div {
                            width: 100%;
                            box-sizing: border-box;
                            overflow-wrap: break-word;
                            word-wrap: break-word;
                            word-break: break-word;
                        }

                        .variaciones-cards-mobile input,
                        .variaciones-cards-mobile select {
                            max-width: 100%;
                            box-sizing: border-box;
                            overflow-wrap: break-word;
                            word-wrap: break-word;
                        }

                        /* Dispositivos pequeños: hasta 768px */
                        @media (max-width: 640px) {
                            .variaciones-tabla-desktop {
                                display: none !important;
                            }
                            .variaciones-cards-mobile {
                                display: block !important;
                            }

                            .variaciones-cards-mobile > div {
                                padding: 10px;
                                margin-bottom: 10px;
                            }

                            .variaciones-cards-mobile input,
                            .variaciones-cards-mobile select {
                                font-size: 16px;
                                padding: 10px 12px;
                            }
                        }

                        /* Tablets: 641px a 1024px */
                        @media (min-width: 641px) and (max-width: 1024px) {
                            .variaciones-tabla-desktop {
                                display: block !important;
                            }
                            .variaciones-cards-mobile {
                                display: none !important;
                            }

                            .variaciones-tabla-desktop table {
                                min-width: 100%;
                            }
                        }

                        /* Escritorio: 1025px en adelante */
                        @media (min-width: 1025px) {
                            .variaciones-tabla-desktop {
                                display: block !important;
                            }
                            .variaciones-cards-mobile {
                                display: none !important;
                            }
                        }
                    </style>
                </div>

                <!-- TALLAS Y CANTIDADES -->
                <div>
                    <label class="block text-sm font-bold text-gray-900 dark:text-white mb-3">
                        Tallas y Cantidades
                    </label>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                        <div>
                            <label class="text-xs text-gray-600 dark:text-gray-400">S</label>
                            <input type="number" id="talla-s" min="0" value="0" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white" onchange="limpiarErrorTallas()" oninput="limpiarErrorTallas()">
                        </div>
                        <div>
                            <label class="text-xs text-gray-600 dark:text-gray-400">M</label>
                            <input type="number" id="talla-m" min="0" value="0" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white" onchange="limpiarErrorTallas()" oninput="limpiarErrorTallas()">
                        </div>
                        <div>
                            <label class="text-xs text-gray-600 dark:text-gray-400">L</label>
                            <input type="number" id="talla-l" min="0" value="0" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white" onchange="limpiarErrorTallas()" oninput="limpiarErrorTallas()">
                        </div>
                        <div>
                            <label class="text-xs text-gray-600 dark:text-gray-400">XL</label>
                            <input type="number" id="talla-xl" min="0" value="0" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white" onchange="limpiarErrorTallas()" oninput="limpiarErrorTallas()">
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
                 Prendas Agregadas (<span id="contador-prendas">0</span>)
            </h2>

            <div id="prendas-tabla" class="space-y-3">
                <p class="text-gray-500 dark:text-gray-400 text-center py-8">
                    No hay prendas agregadas aún. Completa los datos y haz clic en "Agregar Prenda".
                </p>
            </div>

            <!-- Botones de Acción -->
            <div id="botones-accion" class="hidden mt-6 flex gap-3">
                <button class="flex-1 bg-green-600 hover:bg-green-700 text-white font-semibold py-2 rounded-lg transition-colors">
                     Guardar Cotización
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

// Mapeo de IDs a nombres de broche/botón
const TIPOS_BROCHE_BOTON = {
    '1': 'Broche',
    '2': 'Botón'
};

function obtenerNombreBrocheBoton(id) {
    return TIPOS_BROCHE_BOTON[String(id)] || 'Desconocido';
}

// Cargar tipos de broche/botón desde el API cuando se carga la página
document.addEventListener('DOMContentLoaded', function() {
    cargarTiposBrocheBoton();
    cargarTiposManga();
    configurarManejadorManga();
});

async function cargarTiposBrocheBoton() {
    try {
        const response = await fetch('{{ route("asesores.api.tipos-broche-boton") }}');
        if (!response.ok) throw new Error('Error fetching tipos broche/botón');
        
        const result = await response.json();
        if (result.success && result.data) {
            // Actualizar el mapeo local con los datos de la BDD
            result.data.forEach(tipo => {
                TIPOS_BROCHE_BOTON[String(tipo.id)] = tipo.nombre;
            });
            
            // Actualizar los selectores con las opciones dinámicas
            const selects = document.querySelectorAll('.broche-tipo-select');
            selects.forEach(select => {
                // Limpiar opciones excepto la primera
                while (select.options.length > 1) {
                    select.remove(1);
                }
                
                // Agregar opciones dinámicas desde la BDD
                result.data.forEach(tipo => {
                    const option = document.createElement('option');
                    option.value = tipo.id;
                    option.textContent = tipo.nombre;
                    select.appendChild(option);
                });
            });
        }
    } catch (error) {
        console.warn('Error cargando tipos de broche/botón:', error);
        // Fallback a valores por defecto si hay error
    }
}

async function cargarTiposManga() {
    try {
        const response = await fetch('{{ route("asesores.api.tipos-manga") }}');
        if (!response.ok) throw new Error('Error fetching tipos manga');
        
        const result = await response.json();
        if (result.success && result.data) {
            // Llenar los datalists con las opciones disponibles
            const datalistDesktop = document.getElementById('manga-options');
            const datalistMobile = document.getElementById('manga-options-mobile');
            
            datalistDesktop.innerHTML = '';
            datalistMobile.innerHTML = '';
            
            result.data.forEach(tipo => {
                const optionDesktop = document.createElement('option');
                optionDesktop.value = tipo.nombre;
                optionDesktop.dataset.id = tipo.id;
                datalistDesktop.appendChild(optionDesktop);
                
                const optionMobile = document.createElement('option');
                optionMobile.value = tipo.nombre;
                optionMobile.dataset.id = tipo.id;
                datalistMobile.appendChild(optionMobile);
            });
        }
    } catch (error) {
        console.warn('Error cargando tipos de manga:', error);
    }
}

function configurarManejadorManga() {
    // Manejador para crear tipo de manga si no existe
    const mangaInputDesktop = document.getElementById('manga-input');
    const mangaInputMobile = document.getElementById('manga-input-mobile');
    
    if (mangaInputDesktop) {
        mangaInputDesktop.addEventListener('blur', function() {
            procesarMangaInput(this);
        });
    }
    
    if (mangaInputMobile) {
        mangaInputMobile.addEventListener('blur', function() {
            procesarMangaInput(this);
        });
    }
}

async function procesarMangaInput(input) {
    const valor = input.value.trim();
    if (!valor) return;
    
    try {
        // Verificar si ya existe en el datalist
        const datalist = document.getElementById(input.getAttribute('list'));
        let existe = false;
        
        for (let option of datalist.options) {
            if (option.value.toLowerCase() === valor.toLowerCase()) {
                existe = true;
                break;
            }
        }
        
        if (!existe) {
            // Crear el nuevo tipo de manga
            const response = await fetch('{{ route("asesores.api.tipos-manga.create") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: JSON.stringify({ nombre: valor })
            });
            
            const result = await response.json();
            if (result.success) {
                // Agregar a los datalists
                const newOption = document.createElement('option');
                newOption.value = result.data.nombre;
                newOption.dataset.id = result.data.id;
                
                document.getElementById('manga-options').appendChild(newOption);
                document.getElementById('manga-options-mobile').appendChild(newOption.cloneNode(true));
                
                console.log(' Tipo de manga creado:', result.data);
            }
        }
    } catch (error) {
        console.error('Error procesando manga:', error);
    }
}

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

function toggleMangaInputMobile(checkbox) {
    const card = checkbox.closest('div');
    const input = card.querySelector('.manga-input');
    input.disabled = !checkbox.checked;
    input.style.opacity = checkbox.checked ? '1' : '0.5';
    input.style.pointerEvents = checkbox.checked ? 'auto' : 'none';
}

function toggleBrocheInputs(checkbox) {
    const row = checkbox.closest('tr');
    const select = row.querySelector('.broche-tipo-select');
    const input = row.querySelector('.broche-obs-input');
    
    select.disabled = !checkbox.checked;
    input.disabled = !checkbox.checked;
    select.style.opacity = checkbox.checked ? '1' : '0.5';
    input.style.opacity = checkbox.checked ? '1' : '0.5';
    select.style.pointerEvents = checkbox.checked ? 'auto' : 'none';
    input.style.pointerEvents = checkbox.checked ? 'auto' : 'none';
}

function toggleBrocheInputsMobile(checkbox) {
    const card = checkbox.closest('div');
    const select = card.querySelector('.broche-tipo-select');
    const input = card.querySelector('.broche-obs-input');
    
    select.disabled = !checkbox.checked;
    input.disabled = !checkbox.checked;
    select.style.opacity = checkbox.checked ? '1' : '0.5';
    input.style.opacity = checkbox.checked ? '1' : '0.5';
    select.style.pointerEvents = checkbox.checked ? 'auto' : 'none';
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

function limpiarErrorTallas() {
    const campo = document.getElementById('talla-s');
    if (campo && campo.classList.contains('campo-error')) {
        campo.classList.remove('campo-error');
        campo.style.borderColor = '';
        campo.style.backgroundColor = '';
        const mensajeError = campo.parentNode.nextElementSibling;
        if (mensajeError && mensajeError.style.color === 'rgb(220, 38, 38)') {
            mensajeError.remove();
        }
    }
}

function limpiarError(campo) {
    if (campo.classList.contains('campo-error')) {
        campo.classList.remove('campo-error');
        campo.style.borderColor = '';
        campo.style.backgroundColor = '';
        const mensajeError = campo.nextElementSibling;
        if (mensajeError && mensajeError.style.color === 'rgb(220, 38, 38)') {
            mensajeError.remove();
        }
    }
}

function agregarPrenda() {
    // Limpiar errores anteriores
    document.querySelectorAll('.campo-error').forEach(el => {
        el.classList.remove('campo-error');
        el.nextElementSibling?.remove();
    });

    const nombre = document.getElementById('nombre-prenda').value.trim();
    const descripcion = document.getElementById('descripcion').value.trim();
    const color = document.getElementById('color').value.trim();
    const tela = document.getElementById('tela').value.trim();
    const referencia = document.getElementById('referencia').value.trim();
    const prendaBodega = document.getElementById('prenda-bodega').checked;
    
    const tallas = {
        S: parseInt(document.getElementById('talla-s').value) || 0,
        M: parseInt(document.getElementById('talla-m').value) || 0,
        L: parseInt(document.getElementById('talla-l').value) || 0,
        XL: parseInt(document.getElementById('talla-xl').value) || 0
    };

    let errores = [];
    
    // Validar campos OBLIGATORIOS
    if (!nombre) {
        errores.push({ campo: 'nombre-prenda', mensaje: '⚠️ Tipo de prenda es requerido' });
    }
    if (!color) {
        errores.push({ campo: 'color', mensaje: '⚠️ Color es requerido' });
    }
    if (!tela) {
        errores.push({ campo: 'tela', mensaje: '⚠️ Tela es requerida' });
    }
    
    // Validar que al menos una talla tenga cantidad
    const totalTallas = Object.values(tallas).reduce((a, b) => a + b, 0);
    if (totalTallas === 0) {
        errores.push({ campo: 'talla-s', mensaje: '⚠️ Debe agregar al menos una talla con cantidad' });
    }
    
    // Si hay errores, mostrarlos en rojo
    if (errores.length > 0) {
        errores.forEach(error => {
            const campo = document.getElementById(error.campo);
            if (campo) {
                campo.classList.add('campo-error');
                campo.style.borderColor = '#ef4444';
                campo.style.backgroundColor = '#fee2e2';
                const mensajeDiv = document.createElement('div');
                mensajeDiv.style.color = '#dc2626';
                mensajeDiv.style.fontSize = '0.85rem';
                mensajeDiv.style.marginTop = '4px';
                mensajeDiv.style.fontWeight = '500';
                mensajeDiv.textContent = error.mensaje;
                campo.parentNode.insertBefore(mensajeDiv, campo.nextSibling);
            }
        });
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
        prendaBodega,
        variaciones: {
            manga_nombre: document.querySelector('.aplica-manga').checked ? document.querySelector('.manga-input').value : null,
            bolsillos: document.querySelector('.aplica-bolsillos').checked ? document.querySelector('.bolsillos-input').value : null,
            tipo_broche_boton_id: document.querySelector('.aplica-broche').checked ? (document.getElementById('broche-tipo')?.value || null) : null,
            broche_obs: document.querySelector('.aplica-broche').checked ? document.querySelector('.broche-obs-input').value : null,
            puno: document.querySelector('.aplica-puno').checked ? document.querySelector('.puno-input').value : null,
            proceso: document.querySelector('.aplica-proceso').checked ? document.querySelector('.proceso-input').value : null,
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
                <p class="col-span-full"><strong>Prenda de Bodega:</strong> <span style="color: ${prenda.prendaBodega ? '#10b981' : '#ef4444'}; font-weight: bold;">${prenda.prendaBodega ? ' Sí' : ' No'}</span></p>
            </div>

            ${prenda.variaciones.manga_nombre || prenda.variaciones.tipo_broche_boton_id || prenda.variaciones.bolsillos || prenda.variaciones.puno || prenda.variaciones.proceso ? `
            <div class="bg-blue-50 dark:bg-blue-900/20 rounded p-3 mb-3">
                <p class="text-xs font-semibold text-blue-700 dark:text-blue-300 mb-2">Variaciones:</p>
                <div class="space-y-1 text-xs">
                    ${prenda.variaciones.manga_nombre ? `<p>• <strong>Manga:</strong> ${prenda.variaciones.manga_nombre}</p>` : ''}
                    ${prenda.variaciones.tipo_broche_boton_id ? `<p>• <strong>Broche/Botón:</strong> ${obtenerNombreBrocheBoton(prenda.variaciones.tipo_broche_boton_id)}${prenda.variaciones.broche_obs ? ' (' + prenda.variaciones.broche_obs + ')' : ''}</p>` : ''}
                    ${prenda.variaciones.bolsillos ? `<p>• <strong>Bolsillos:</strong> ${prenda.variaciones.bolsillos}</p>` : ''}
                    ${prenda.variaciones.puno ? `<p>• <strong>Puño:</strong> ${prenda.variaciones.puno}</p>` : ''}
                    ${prenda.variaciones.proceso ? `<p>• <strong>Proceso:</strong> ${prenda.variaciones.proceso}</p>` : ''}
                </div>
            </div>
            ` : ''}

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
    document.querySelectorAll('.manga-input, .bolsillos-input, .broche-obs-input, .puno-input, .proceso-input').forEach(input => input.value = '');
    document.querySelectorAll('.broche-tipo-select').forEach(select => select.value = '');
    document.getElementById('nombre-prenda').focus();
}
</script>

<style>
    .campo-error {
        transition: all 0.3s ease !important;
        box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.2) !important;
    }
    
    .campo-error:focus {
        outline: none !important;
        box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.3) !important;
    }
</style>
@endsection
