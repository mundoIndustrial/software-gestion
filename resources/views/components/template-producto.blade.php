<template id="productoTemplate">
    <div class="producto-card" data-producto-id="">
        <div class="producto-header">
            <h4 class="producto-titulo">PRENDA <span class="numero-producto">1</span></h4>
            <div style="display: flex; gap: 0.5rem;">
                <button type="button" class="btn-toggle-product" onclick="toggleProductoBody(this)" title="Expandir/Contraer" style="font-size: 1.5rem; line-height: 1; font-weight: bold;">▼</button>
                <button type="button" class="btn-remove-product" onclick="eliminarProductoFriendly(this)" title="Eliminar prenda">&times;</button>
            </div>
        </div>
        <div class="producto-body" style="display: block;">
            <div class="producto-section">
                <div class="section-title"><i class="fas fa-shirt"></i> TIPO DE PRENDA</div>
                <div class="form-row tipo-prenda-row" style="display: flex; gap: 12px; align-items: flex-start;">
                    <div class="form-col full" style="flex: 1;">
                        <label style="font-size: 0.65rem; font-weight: 600; display: flex; align-items: center; gap: 0.3rem; margin-bottom: 0.3rem;"><i class="fas fa-list"></i> SELECCIONA O ESCRIBE EL TIPO *</label>
                        <div class="prenda-search-container">
                            <input type="text" name="productos_friendly[][nombre_producto]" class="prenda-search-input input-large" placeholder="BUSCA O ESCRIBE (CAMISA, CAMISETA, POLO...)" required onkeyup="buscarPrendas(this); mostrarSelectorVariantes(this);" onchange="if(typeof actualizarResumenFriendly === 'function') { actualizarResumenFriendly(); } mostrarSelectorVariantes(this);">
                        </div>
                        <small class="help-text">PUEDES BUSCAR, SELECCIONAR O ESCRIBIR UNA PRENDA PERSONALIZADA</small>
                    </div>
                    <!-- Selector de Tipo de JEAN/PANTALÓN - Oculto por defecto -->
                    <div class="tipo-jean-pantalon-inline" style="display: none; width: 280px; padding: 0; background: transparent; border: none; border-radius: 0; margin-left: 12px; flex-shrink: 0;">
                        <div class="tipo-jean-pantalon-inline-container" style="display: flex; flex-direction: column; gap: 4px;">
                            <!-- El selector se inserta aquí dinámicamente -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECCIÓN DE DESCRIPCIÓN -->
            <div class="producto-section">
                <div class="section-title"><i class="fas fa-sticky-note"></i> DESCRIPCIÓN</div>
                <div class="form-row">
                    <div class="form-col full">
                        <label><i class="fas fa-pen"></i> DESCRIPCIÓN</label>
                        <textarea name="productos_friendly[][descripcion]" class="input-medium" placeholder="DESCRIPCIÓN DE LA PRENDA..." rows="2"></textarea>
                        <small class="help-text">DESCRIBE LA PRENDA, DETALLES ESPECIALES, ETC.</small>
                    </div>
                </div>
            </div>

            <!-- SECCIÓN DE FOTOS DE LA PRENDA -->
            <div class="producto-section">
                <button type="button" class="section-title-btn" onclick="toggleSeccion(this)">
                    <div class="section-title">
                        <i class="fas fa-images"></i> FOTOS DE LA PRENDA (MÁX. 3)
                        <i class="fas fa-chevron-down" style="margin-left: auto; transition: transform 0.3s ease;"></i>
                    </div>
                </button>
                <div class="section-content">
                    <div style="margin-bottom: 1rem;">
                        <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: 600; margin-bottom: 0.5rem; color: #0066cc; font-size: 0.85rem;">
                            <i class="fas fa-image"></i> FOTOS PRENDA
                        </label>
                        <label style="display: block; min-height: 80px; padding: 0.75rem; border: 2px dashed #0066cc; border-radius: 6px; cursor: pointer; text-align: center; background: #f0f7ff;" ondrop="manejarDrop(event, this)" ondragover="event.preventDefault()" ondragleave="this.classList.remove('drag-over')">
                            <input type="file" name="productos_friendly[][fotos][]" class="input-file-single" accept="image/*" multiple onchange="agregarFotos(this.files, this.closest('label'))" style="display: none;">
                            <div class="drop-zone-content" style="font-size: 0.75rem;">
                                <i class="fas fa-cloud-upload-alt" style="font-size: 1rem; color: #0066cc;"></i>
                                <p style="margin: 0.25rem 0; color: #0066cc; font-weight: 500;">ARRASTRA O CLIC</p>
                            </div>
                        </label>
                        <div class="fotos-preview" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.4rem; margin-top: 0.5rem;"></div>
                    </div>
                </div>
            </div>

            <!-- SECCIÓN DE COLOR, TELA Y REFERENCIA (Tabla con imagen) -->
            <div class="producto-section">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <div class="section-title"><i class="fas fa-palette"></i> COLOR, TELA Y REFERENCIA</div>
                    <button type="button" class="btn-agregar-tela" onclick="agregarFilaTela(this)" style="padding: 0.5rem 1rem; background: linear-gradient(135deg, #0066cc, #0052a3); color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 0.9rem; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-plus"></i> Agregar Tela
                    </button>
                </div>
                <div class="form-row" style="overflow-x: auto;">
                    <div class="form-col full" style="min-width: 0;">
                        <table style="width: 100%; border-collapse: collapse; background: white; min-width: 800px;">
                            <thead>
                                <tr style="background-color: #f0f0f0; border-bottom: 2px solid #0066cc;">
                                    <th style="padding: 12px; text-align: left; font-weight: 600; color: #0066cc; border-right: 1px solid #ddd; width: 150px; white-space: nowrap;">
                                        <i class="fas fa-palette"></i> Color
                                    </th>
                                    <th style="padding: 12px; text-align: left; font-weight: 600; color: #0066cc; border-right: 1px solid #ddd; width: 150px; white-space: nowrap;">
                                        <i class="fas fa-cloth"></i> Tela
                                    </th>
                                    <th style="padding: 12px; text-align: left; font-weight: 600; color: #0066cc; border-right: 1px solid #ddd; width: 120px; white-space: nowrap;">
                                        <i class="fas fa-barcode"></i> Referencia
                                    </th>
                                    <th style="padding: 12px; text-align: center; font-weight: 600; color: #0066cc; border-right: 1px solid #ddd; width: 200px; white-space: nowrap;">
                                        <i class="fas fa-image"></i> Imagen Tela
                                    </th>
                                    <th style="padding: 12px; text-align: center; font-weight: 600; color: #0066cc; width: 50px; white-space: nowrap;">
                                        <i class="fas fa-trash"></i>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="telas-tbody">
                                <tr style="border-bottom: 1px solid #ddd;" class="fila-tela" data-tela-index="0">
                                    <td style="padding: 14px; border-right: 1px solid #ddd;">
                                        <div style="position: relative;">
                                            <label for="color-input" class="sr-only">Color</label>
                                            <input type="text" id="color-input" class="color-input" placeholder="Color..." style="width: 100%; padding: 12px; border: 2px solid #0066cc; border-radius: 4px; font-size: 0.95rem; box-sizing: border-box; min-height: 44px;" onkeyup="buscarColor(this)" onkeypress="if(event.key==='Enter') crearColorDesdeInput(this)" aria-label="Selecciona o escribe un color">
                                            <input type="hidden" name="productos_friendly[][telas][0][color_id]" class="color-id-input" value="">
                                            <div class="color-suggestions" style="position: absolute; background: white; border: 1px solid #ddd; border-radius: 4px; max-height: 150px; overflow-y: auto; z-index: 1000; min-width: 100%; display: none; margin-top: 2px; top: 100%;"></div>
                                        </div>
                                    </td>
                                    <td style="padding: 14px; border-right: 1px solid #ddd;">
                                        <div style="position: relative;">
                                            <label for="tela-input" class="sr-only">Tela</label>
                                            <input type="text" id="tela-input" class="tela-input" placeholder="Tela..." style="width: 100%; padding: 12px; border: 2px solid #0066cc; border-radius: 4px; font-size: 0.95rem; box-sizing: border-box; min-height: 44px;" onkeyup="buscarTela(this)" onkeypress="if(event.key==='Enter') crearTelaDesdeInput(this)" aria-label="Selecciona o escribe el tipo de tela">
                                            <input type="hidden" name="productos_friendly[][telas][0][tela_id]" class="tela-id-input" value="">
                                            <div class="tela-suggestions" style="position: absolute; background: white; border: 1px solid #ddd; border-radius: 4px; max-height: 150px; overflow-y: auto; z-index: 1000; min-width: 100%; display: none; margin-top: 2px; top: 100%;"></div>
                                        </div>
                                    </td>
                                    <td style="padding: 14px; border-right: 1px solid #ddd;">
                                        <label for="referencia-input" class="sr-only">Referencia</label>
                                        <input type="text" id="referencia-input" name="productos_friendly[][telas][0][referencia]" class="referencia-input" placeholder="Ref..." style="width: 100%; padding: 12px; border: 2px solid #0066cc; border-radius: 4px; font-size: 0.95rem; box-sizing: border-box; min-height: 44px;" aria-label="Referencia del producto">
                                    </td>
                                    <td style="padding: 14px; text-align: center; border-right: 1px solid #ddd;">
                                        <label style="display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 80px; padding: 8px; border: 2px dashed #0066cc; border-radius: 6px; cursor: pointer; text-align: center; background: #f0f7ff;" ondrop="manejarDrop(event, this)" ondragover="event.preventDefault(); this.style.background='#e8f4f8';" ondragleave="this.style.background='#f0f7ff'">
                                            <input type="file" name="productos_friendly[][telas][0][fotos][]" class="input-file-tela" accept="image/*" multiple onchange="agregarFotoTela(this)" style="display: none;">
                                            <div class="drop-zone-content" style="font-size: 0.8rem;">
                                                <i class="fas fa-cloud-upload-alt" style="font-size: 1.2rem; color: #0066cc; margin-bottom: 4px;"></i>
                                                <p style="margin: 4px 0; color: #0066cc; font-weight: 600; font-size: 0.8rem;">CLIC</p>
                                                <small style="color: #666; font-size: 0.75rem;">(Máx. 3)</small>
                                            </div>
                                        </label>
                                        <div class="foto-tela-preview" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 6px; margin-top: 6px;"></div>
                                    </td>
                                    <td style="padding: 14px; text-align: center;">
                                        <button type="button" class="btn-eliminar-tela" onclick="eliminarFilaTela(this)" style="padding: 10px 12px; background: #dc3545; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; display: none; min-width: 44px; min-height: 44px;">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- SECCIÓN DE VARIACIONES ESPECÍFICAS -->
            <div class="producto-section">
                <div class="section-title"><i class="fas fa-sliders-h"></i> VARIACIONES ESPECÍFICAS</div>
                <div class="form-row">
                    <div class="form-col full">
                        <!-- Vista Desktop (tabla) -->
                        <table style="width: 100%; border-collapse: collapse; background: white; margin: 0; border: 1px solid #ddd; border-radius: 4px; overflow: hidden; display: none;" class="variaciones-tabla-desktop">
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
                                        <input type="checkbox" name="productos_friendly[][variantes][aplica_manga]" value="1" style="width: 18px; height: 18px; cursor: pointer; accent-color: #0066cc;" onchange="toggleMangaInput(this)">
                                    </td>
                                    <td style="padding: 14px 12px; border-right: 1px solid #eee; font-weight: 600; color: #0066cc; white-space: nowrap;">
                                        <i class="fas fa-shirt"></i> Manga
                                    </td>
                                    <td style="padding: 14px 12px;">
                                        <div style="display: flex; gap: 8px; align-items: flex-start;">
                                            <div style="position: relative; flex: 1;">
                                                <input type="text" class="manga-input" placeholder="Buscar tipo..." style="width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem; box-sizing: border-box; transition: border-color 0.2s; opacity: 0.5; pointer-events: none;" onkeyup="buscarManga(this)" onkeypress="if(event.key==='Enter') crearMangaDesdeInput(this)" onblur="if(this.value.trim() && !this.closest('td').querySelector('.manga-id-input').value) crearMangaDesdeInput(this)" disabled>
                                                <input type="hidden" name="productos_friendly[][variantes][tipo_manga_id]" class="manga-id-input" value="">
                                                <div class="manga-suggestions" style="position: absolute; background: white; border: 1px solid #ddd; border-radius: 4px; max-height: 150px; overflow-y: auto; z-index: 1000; width: 100%; display: none; margin-top: 2px; top: 100%; left: 0; box-shadow: 0 2px 8px rgba(0,0,0,0.1);"></div>
                                            </div>
                                            <input type="text" name="productos_friendly[][variantes][obs_manga]" placeholder="Ej: manga larga..." style="flex: 1; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem; box-sizing: border-box; transition: border-color 0.2s;">
                                        </div>
                                    </td>
                                </tr>
                                
                                <!-- BOLSILLOS -->
                                <tr style="border-bottom: 1px solid #eee; background-color: white;">
                                    <td style="padding: 14px 12px; text-align: center; border-right: 1px solid #eee;">
                                        <input type="checkbox" name="productos_friendly[][variantes][aplica_bolsillos]" value="1" style="width: 18px; height: 18px; cursor: pointer; accent-color: #0066cc;">
                                    </td>
                                    <td style="padding: 14px 12px; border-right: 1px solid #eee; font-weight: 600; color: #0066cc; white-space: nowrap;">
                                        <i class="fas fa-square"></i> Bolsillos
                                    </td>
                                    <td style="padding: 14px 12px;">
                                        <input type="text" name="productos_friendly[][variantes][obs_bolsillos]" placeholder="Ej: 4 bolsillos, con cierre..." style="width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem; box-sizing: border-box; transition: border-color 0.2s;">
                                    </td>
                                </tr>
                                
                                <!-- BROCHE/BOTÓN -->
                                <tr style="border-bottom: 1px solid #eee; background-color: #fafafa;">
                                    <td style="padding: 14px 12px; text-align: center; border-right: 1px solid #eee;">
                                        <input type="checkbox" name="productos_friendly[][variantes][aplica_broche]" value="1" style="width: 18px; height: 18px; cursor: pointer; accent-color: #0066cc;">
                                    </td>
                                    <td style="padding: 14px 12px; border-right: 1px solid #eee; font-weight: 600; color: #0066cc; white-space: nowrap;">
                                        <i class="fas fa-link"></i> Broche/Botón
                                    </td>
                                    <td style="padding: 14px 12px;">
                                        <div style="display: flex; gap: 8px; align-items: center;">
                                            <select name="productos_friendly[][variantes][tipo_broche_id]" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.65rem; background-color: white; cursor: pointer; transition: border-color 0.2s;">
                                                <option value="">Seleccionar...</option>
                                                <option value="1">Broche</option>
                                                <option value="2">Botón</option>
                                            </select>
                                            <input type="text" name="productos_friendly[][variantes][obs_broche]" placeholder="Ej: Botones de madera..." style="flex: 1; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.65rem; box-sizing: border-box; transition: border-color 0.2s;">
                                        </div>
                                    </td>
                                </tr>
                                
                                <!-- REFLECTIVO -->
                                <tr style="border-bottom: 1px solid #eee; background-color: white;">
                                    <td style="padding: 14px 12px; text-align: center; border-right: 1px solid #eee;">
                                        <input type="checkbox" name="productos_friendly[][variantes][aplica_reflectivo]" value="1" style="width: 18px; height: 18px; cursor: pointer; accent-color: #0066cc;">
                                    </td>
                                    <td style="padding: 14px 12px; border-right: 1px solid #eee; font-weight: 600; color: #0066cc; white-space: nowrap;">
                                        <i class="fas fa-star"></i> Reflectivo
                                    </td>
                                    <td style="padding: 14px 12px;">
                                        <input type="text" name="productos_friendly[][variantes][obs_reflectivo]" placeholder="Ej: En brazos y espalda..." style="width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem; box-sizing: border-box; transition: border-color 0.2s;">
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <!-- Vista Mobile (cards) -->
                        <div style="display: none;" class="variaciones-cards-mobile">
                            <!-- MANGA -->
                            <div style="background: white; border: 1px solid #ddd; border-radius: 6px; padding: 12px; margin-bottom: 12px;">
                                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px;">
                                    <input type="checkbox" name="productos_friendly[][variantes][aplica_manga]" value="1" style="width: 18px; height: 18px; cursor: pointer; accent-color: #0066cc;" onchange="toggleMangaInput(this)">
                                    <label style="font-weight: 600; color: #0066cc; cursor: pointer; flex: 1;"><i class="fas fa-shirt"></i> Manga</label>
                                </div>
                                <div style="display: flex; flex-direction: column; gap: 8px;">
                                    <div style="position: relative;">
                                        <input type="text" class="manga-input" placeholder="Buscar tipo..." style="width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem; box-sizing: border-box; transition: border-color 0.2s; opacity: 0.5; pointer-events: none;" onkeyup="buscarManga(this)" onkeypress="if(event.key==='Enter') crearMangaDesdeInput(this)" disabled>
                                        <input type="hidden" name="productos_friendly[][variantes][tipo_manga_id]" class="manga-id-input" value="">
                                        <div class="manga-suggestions" style="position: absolute; background: white; border: 1px solid #ddd; border-radius: 4px; max-height: 150px; overflow-y: auto; z-index: 1000; width: 100%; display: none; margin-top: 2px; top: 100%; left: 0; box-shadow: 0 2px 8px rgba(0,0,0,0.1);"></div>
                                    </div>
                                    <input type="text" name="productos_friendly[][variantes][obs_manga]" placeholder="Ej: manga larga..." style="width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem; box-sizing: border-box; transition: border-color 0.2s;">
                                </div>
                            </div>

                            <!-- BOLSILLOS -->
                            <div style="background: white; border: 1px solid #ddd; border-radius: 6px; padding: 12px; margin-bottom: 12px;">
                                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px;">
                                    <input type="checkbox" name="productos_friendly[][variantes][aplica_bolsillos]" value="1" style="width: 18px; height: 18px; cursor: pointer; accent-color: #0066cc;">
                                    <label style="font-weight: 600; color: #0066cc; cursor: pointer; flex: 1;"><i class="fas fa-square"></i> Bolsillos</label>
                                </div>
                                <input type="text" name="productos_friendly[][variantes][obs_bolsillos]" placeholder="Ej: 4 bolsillos, con cierre..." style="width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem; box-sizing: border-box; transition: border-color 0.2s;">
                            </div>

                            <!-- BROCHE/BOTÓN -->
                            <div style="background: white; border: 1px solid #ddd; border-radius: 6px; padding: 12px; margin-bottom: 12px;">
                                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px;">
                                    <input type="checkbox" name="productos_friendly[][variantes][aplica_broche]" value="1" style="width: 18px; height: 18px; cursor: pointer; accent-color: #0066cc;">
                                    <label style="font-weight: 600; color: #0066cc; cursor: pointer; flex: 1;"><i class="fas fa-link"></i> Broche/Botón</label>
                                </div>
                                <div style="display: flex; flex-direction: column; gap: 8px;">
                                    <select name="productos_friendly[][variantes][tipo_broche_id]" style="width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem; background-color: white; cursor: pointer; transition: border-color 0.2s; box-sizing: border-box;">
                                        <option value="">Seleccionar...</option>
                                        <option value="1">Broche</option>
                                        <option value="2">Botón</option>
                                    </select>
                                    <input type="text" name="productos_friendly[][variantes][obs_broche]" placeholder="Ej: Botones de madera..." style="width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem; box-sizing: border-box; transition: border-color 0.2s;">
                                </div>
                            </div>

                            <!-- REFLECTIVO -->
                            <div style="background: white; border: 1px solid #ddd; border-radius: 6px; padding: 12px; margin-bottom: 12px;">
                                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px;">
                                    <input type="checkbox" name="productos_friendly[][variantes][aplica_reflectivo]" value="1" style="width: 18px; height: 18px; cursor: pointer; accent-color: #0066cc;">
                                    <label style="font-weight: 600; color: #0066cc; cursor: pointer; flex: 1;"><i class="fas fa-star"></i> Reflectivo</label>
                                </div>
                                <input type="text" name="productos_friendly[][variantes][obs_reflectivo]" placeholder="Ej: En brazos y espalda..." style="width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem; box-sizing: border-box; transition: border-color 0.2s;">
                            </div>
                        </div>

                        <style>
                            @media (max-width: 768px) {
                                .variaciones-tabla-desktop {
                                    display: none !important;
                                }
                                .variaciones-cards-mobile {
                                    display: block !important;
                                }
                            }
                            @media (min-width: 769px) {
                                .variaciones-tabla-desktop {
                                    display: table !important;
                                }
                                .variaciones-cards-mobile {
                                    display: none !important;
                                }
                            }
                        </style>
                    </div>
                </div>
            </div>

            <div class="producto-section">
                <div class="section-title"><i class="fas fa-ruler"></i> TALLAS A COTIZAR</div>
                <div class="form-row">
                    <div class="form-col full">
                        <!-- Input oculto para guardar el género seleccionado -->
                        <input type="hidden" name="productos_friendly[][variantes][genero_id]" class="genero-id-hidden" value="">
                        
                        <!-- Fila 1: Selectores de tipo, género y modo -->
                        <div style="display: flex; gap: 0.75rem; align-items: center; margin-bottom: 1rem; flex-wrap: wrap;">
                            <select class="talla-tipo-select" onchange="actualizarSelectTallas(this)" style="padding: 0.6rem 0.8rem; border: 2px solid #0066cc; border-radius: 6px; font-size: 0.85rem; cursor: pointer; background-color: white; color: #0066cc; font-weight: 600; max-width: 300px;">
                                <option value="">Selecciona tipo de talla</option>
                                <option value="letra">LETRAS (XS, S, M, L, XL...)</option>
                                <option value="numero">NÚMEROS (DAMA/CABALLERO)</option>
                            </select>
                            
                            <select class="talla-genero-select" onchange="actualizarGeneroSeleccionado(this)" style="padding: 0.6rem 0.8rem; border: 2px solid #0066cc; border-radius: 6px; font-size: 0.85rem; cursor: pointer; background-color: white; color: #0066cc; font-weight: 600; max-width: 200px; display: none;">
                                <option value="">Selecciona género</option>
                                <option value="dama">Dama</option>
                                <option value="caballero">Caballero</option>
                            </select>
                            
                            <select class="talla-modo-select" style="padding: 0.6rem 0.8rem; border: 2px solid #0066cc; border-radius: 6px; font-size: 0.85rem; cursor: pointer; background-color: white; color: #0066cc; font-weight: 600; max-width: 200px; display: none;">
                                <option value="">Selecciona modo</option>
                                <option value="manual">Manual</option>
                                <option value="rango">Rango (Desde-Hasta)</option>
                            </select>
                            
                            <!-- Selectores de rango (aparecen cuando se selecciona Rango) -->
                            <div class="talla-rango-selectors" style="display: none; flex-wrap: wrap; gap: 0.75rem; align-items: center;">
                                <select class="talla-desde" style="padding: 0.6rem 0.8rem; border: 2px solid #0066cc; border-radius: 6px; font-size: 0.85rem; cursor: pointer; background-color: white; color: #0066cc; font-weight: 600; max-width: 150px;">
                                    <option value="">Desde</option>
                                </select>
                                <span style="color: #0066cc; font-weight: 600;">hasta</span>
                                <select class="talla-hasta" style="padding: 0.6rem 0.8rem; border: 2px solid #0066cc; border-radius: 6px; font-size: 0.85rem; cursor: pointer; background-color: white; color: #0066cc; font-weight: 600; max-width: 150px;">
                                    <option value="">Hasta</option>
                                </select>
                                <button type="button" class="btn-agregar-rango" onclick="agregarTallasRango(this)" style="padding: 0.6rem 1rem; background: linear-gradient(135deg, #0066cc, #0052a3); color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 1rem; white-space: nowrap;">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Fila 2: Botones de tallas (Modo Manual) -->
                        <div class="talla-botones" style="display: none; margin-bottom: 1.5rem;">
                            <p style="margin: 0 0 0.75rem 0; font-size: 0.85rem; font-weight: 600; color: #0066cc;">Selecciona tallas:</p>
                            <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center;">
                                <div class="talla-botones-container" style="display: flex; flex-wrap: wrap; gap: 0.5rem; flex: 1;">
                                </div>
                                <button type="button" class="btn-agregar-tallas-seleccionadas" onclick="agregarTallasSeleccionadas(this)" style="padding: 0.6rem 1rem; background: linear-gradient(135deg, #0066cc, #0052a3); color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 1rem; white-space: nowrap; flex-shrink: 0;">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                        
                        
                        <!-- Fila 3: Tallas agregadas -->
                        <div class="tallas-section" style="display: none; padding-top: 1rem; border-top: 1px solid #e0e0e0;">
                            <p style="margin: 0 0 0.75rem 0; font-size: 0.85rem; font-weight: 600; color: #0066cc;">Tallas seleccionadas:</p>
                            <div class="tallas-agregadas" style="display: flex; flex-wrap: wrap; gap: 0.5rem; min-height: 35px;">
                                <input type="hidden" name="productos_friendly[][tallas]" class="tallas-hidden" value="">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</template>
