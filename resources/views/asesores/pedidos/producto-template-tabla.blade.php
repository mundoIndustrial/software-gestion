<!-- TEMPLATE PARA PRODUCTO - ESTRUCTURA TABLA -->
<template id="productoTemplate">
    <div class="producto-card" data-producto-id="">
        <div class="producto-header">
            <h4 class="producto-titulo">PRENDA <span class="numero-producto">1</span></h4>
            <button type="button" class="btn-remove-product" onclick="eliminarProductoFriendly(this)" title="Eliminar prenda">
                &times;
            </button>
        </div>
        <div class="producto-body">
            <!-- TABLA ESTRUCTURA ESTILO FACTURA -->
            <table class="tabla-producto-control">
                <tbody>
                    <!-- TIPO DE PRENDA -->
                    <tr class="fila-categoria">
                        <td colspan="2" style="background: #0066cc; font-weight: 700; color: white;">👕 TIPO DE PRENDA</td>
                    </tr>
                    <tr>
                        <td class="item-label">SELECCIONA O ESCRIBE *</td>
                        <td class="item-input">
                            <div class="prenda-search-container">
                                <input type="text" name="productos_friendly[][nombre_producto]" class="prenda-search-input input-large" placeholder="CAMISA, CAMISETA, POLO..." required onkeyup="buscarPrendas(this)" onchange="actualizarResumenFriendly()">
                                <div class="prenda-suggestions">
                                    <div class="prenda-suggestion-item" onclick="seleccionarPrenda('👔 CAMISA', this)">👔 CAMISA</div>
                                    <div class="prenda-suggestion-item" onclick="seleccionarPrenda('👕 CAMISETA', this)">👕 CAMISETA</div>
                                    <div class="prenda-suggestion-item" onclick="seleccionarPrenda('🎽 POLO', this)">🎽 POLO</div>
                                    <div class="prenda-suggestion-item" onclick="seleccionarPrenda('👖 PANTALÓN', this)">👖 PANTALÓN</div>
                                    <div class="prenda-suggestion-item" onclick="seleccionarPrenda('👗 FALDA', this)">👗 FALDA</div>
                                    <div class="prenda-suggestion-item" onclick="seleccionarPrenda('🧥 CHAQUETA', this)">🧥 CHAQUETA</div>
                                    <div class="prenda-suggestion-item" onclick="seleccionarPrenda('🧢 SUDADERA', this)">🧢 SUDADERA</div>
                                    <div class="prenda-suggestion-item" onclick="seleccionarPrenda('❓ OTRO', this)">❓ OTRO</div>
                                </div>
                            </div>
                        </td>
                    </tr>

                    <!-- DESCRIPCIÓN -->
                    <tr class="fila-categoria">
                        <td colspan="2" style="background: #0066cc; font-weight: 700; color: white;">📝 DESCRIPCIÓN</td>
                    </tr>
                    <tr>
                        <td class="item-label">DETALLES</td>
                        <td class="item-input">
                            <textarea name="productos_friendly[][descripcion]" class="input-medium" placeholder="DETALLES, LOGO, BORDADO, ESTAMPADO..." rows="2"></textarea>
                        </td>
                    </tr>

                    <!-- TALLAS Y CANTIDADES -->
                    <tr class="fila-categoria">
                        <td colspan="2" style="background: #0066cc; font-weight: 700; color: white;">📏 TALLAS Y CANTIDADES</td>
                    </tr>
                    <tr>
                        <td colspan="2" class="item-input" style="padding: 0;">
                            <div class="tallas-container">
                                <table class="tallas-table">
                                    <thead>
                                        <tr>
                                            <th>TALLA</th>
                                            <th>CANT.</th>
                                            <th>GÉNERO</th>
                                            <th>COLOR</th>
                                            <th>TELA</th>
                                            <th>REF. HILO</th>
                                            <th>ACCIONES</th>
                                        </tr>
                                    </thead>
                                    <tbody class="tallas-tbody">
                                        <tr class="talla-row">
                                            <td>
                                                <select name="productos_friendly[][talla]" class="input-small" required onchange="actualizarResumenFriendly()">
                                                    <option value="">SEL</option>
                                                    <option value="XS">XS</option>
                                                    <option value="S">S</option>
                                                    <option value="M">M</option>
                                                    <option value="L">L</option>
                                                    <option value="XL">XL</option>
                                                    <option value="XXL">XXL</option>
                                                </select>
                                            </td>
                                            <td><input type="number" name="productos_friendly[][cantidad]" class="input-small" placeholder="1" min="1" value="1" onchange="actualizarResumenFriendly()" required></td>
                                            <td>
                                                <select name="productos_friendly[][genero]" class="input-small" onchange="actualizarResumenFriendly()">
                                                    <option value="">SEL</option>
                                                    <option value="Dama">DAMA</option>
                                                    <option value="Caballero">CABALLERO</option>
                                                    <option value="Unisex">UNISEX</option>
                                                </select>
                                            </td>
                                            <td><input type="text" name="productos_friendly[][color]" class="input-small" placeholder="BLANCO" onchange="actualizarResumenFriendly()"></td>
                                            <td><input type="text" name="productos_friendly[][tella]" class="input-small" placeholder="ALGODÓN" onchange="actualizarResumenFriendly()"></td>
                                            <td><input type="text" name="productos_friendly[][ref_hilo]" class="input-small" placeholder="REF-001" onchange="actualizarResumenFriendly()"></td>
                                            <td>
                                                <button type="button" class="btn-remove-talla" onclick="eliminarFilaTalla(this)" title="Eliminar talla">
                                                    &times;
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <button type="button" class="btn-add-talla" onclick="agregarFilaTalla(this)">
                                <i class="fas fa-plus"></i> AGREGAR OTRA TALLA
                            </button>
                        </td>
                    </tr>

                    <!-- FOTOS -->
                    <tr class="fila-categoria">
                        <td colspan="2" style="background: #0066cc; font-weight: 700; color: white;">🖼️ FOTOS DE LA PRENDA</td>
                    </tr>
                    <tr>
                        <td colspan="2" class="item-input">
                            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem;">
                                <!-- Fotos de la Prenda -->
                                <div>
                                    <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: 600; margin-bottom: 0.5rem; color: #0066cc; font-size: 0.85rem;">
                                        <i class="fas fa-image"></i> PRENDA
                                    </label>
                                    <label style="display: block; min-height: 80px; padding: 0.75rem; border: 2px dashed #0066cc; border-radius: 6px; cursor: pointer; text-align: center; background: #f0f7ff;" ondrop="manejarDrop(event)" ondragover="event.preventDefault()" ondragleave="this.classList.remove('drag-over')">
                                        <input type="file" name="productos_friendly[][fotos][]" class="input-file-single" accept="image/*" multiple onchange="agregarFotos(this.files, this.closest('label').nextElementSibling)" style="display: none;">
                                        <div class="drop-zone-content" style="font-size: 0.75rem;">
                                            <i class="fas fa-cloud-upload-alt" style="font-size: 1rem; color: #0066cc;"></i>
                                            <p style="margin: 0.25rem 0; color: #0066cc; font-weight: 500;">ARRASTRA</p>
                                        </div>
                                    </label>
                                    <div class="fotos-preview" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.4rem; margin-top: 0.5rem;"></div>
                                </div>

                                <!-- Imagen de Tela -->
                                <div>
                                    <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: 600; margin-bottom: 0.5rem; color: #0066cc; font-size: 0.85rem;">
                                        <i class="fas fa-fiber-manual-record"></i> TELA
                                    </label>
                                    <div style="display: flex; gap: 0.5rem;">
                                        <label style="flex: 1; min-height: 80px; padding: 0.75rem; border: 2px dashed #0066cc; border-radius: 6px; cursor: pointer; text-align: center; background: #f0f7ff; display: flex; flex-direction: column; align-items: center; justify-content: center;" ondrop="manejarDrop(event)" ondragover="event.preventDefault()" ondragleave="this.classList.remove('drag-over')">
                                            <input type="file" name="productos_friendly[][imagen_tela]" class="input-file-single" accept="image/*" onchange="agregarFotoTela(this)" style="display: none;">
                                            <div class="drop-zone-content" style="font-size: 0.75rem;">
                                                <i class="fas fa-cloud-upload-alt" style="font-size: 1rem; color: #0066cc;"></i>
                                                <p style="margin: 0.25rem 0; color: #0066cc; font-weight: 500;">FOTO</p>
                                            </div>
                                        </label>
                                        <label style="display: flex; align-items: center; gap: 0.25rem; cursor: pointer; font-size: 0.75rem; color: #666; white-space: nowrap;">
                                            <input type="checkbox" name="productos_friendly[][no_aplica_tela]" class="checkbox-no-aplica" onchange="toggleImagenTela(this)">
                                            N/A
                                        </label>
                                    </div>
                                    <div class="foto-tela-preview" style="display: grid; grid-template-columns: repeat(3, 60px); gap: 0.4rem; margin-top: 0.5rem;"></div>
                                </div>

                                <!-- Imagen de Bordado -->
                                <div>
                                    <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: 600; margin-bottom: 0.5rem; color: #0066cc; font-size: 0.85rem;">
                                        <i class="fas fa-palette"></i> BORDADO
                                    </label>
                                    <div style="display: flex; gap: 0.5rem;">
                                        <label style="flex: 1; min-height: 80px; padding: 0.75rem; border: 2px dashed #0066cc; border-radius: 6px; cursor: pointer; text-align: center; background: #f0f7ff; display: flex; flex-direction: column; align-items: center; justify-content: center;" ondrop="manejarDrop(event)" ondragover="event.preventDefault()" ondragleave="this.classList.remove('drag-over')">
                                            <input type="file" name="productos_friendly[][imagen_bordado]" class="input-file-single" accept="image/*" onchange="agregarFotoBordado(this)" style="display: none;">
                                            <div class="drop-zone-content" style="font-size: 0.75rem;">
                                                <i class="fas fa-cloud-upload-alt" style="font-size: 1rem; color: #0066cc;"></i>
                                                <p style="margin: 0.25rem 0; color: #0066cc; font-weight: 500;">FOTO</p>
                                            </div>
                                        </label>
                                        <label style="display: flex; align-items: center; gap: 0.25rem; cursor: pointer; font-size: 0.75rem; color: #666; white-space: nowrap;">
                                            <input type="checkbox" name="productos_friendly[][no_aplica_bordado]" class="checkbox-no-aplica" onchange="toggleImagenBordado(this)">
                                            N/A
                                        </label>
                                    </div>
                                    <div class="foto-bordado-preview" style="display: grid; grid-template-columns: repeat(3, 60px); gap: 0.4rem; margin-top: 0.5rem;"></div>
                                </div>

                                <!-- Imagen de Estampado -->
                                <div>
                                    <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: 600; margin-bottom: 0.5rem; color: #0066cc; font-size: 0.85rem;">
                                        <i class="fas fa-print"></i> ESTAMPADO
                                    </label>
                                    <div style="display: flex; gap: 0.5rem;">
                                        <label style="flex: 1; min-height: 80px; padding: 0.75rem; border: 2px dashed #0066cc; border-radius: 6px; cursor: pointer; text-align: center; background: #f0f7ff; display: flex; flex-direction: column; align-items: center; justify-content: center;" ondrop="manejarDrop(event)" ondragover="event.preventDefault()" ondragleave="this.classList.remove('drag-over')">
                                            <input type="file" name="productos_friendly[][imagen_estampado]" class="input-file-single" accept="image/*" onchange="agregarFotoEstampado(this)" style="display: none;">
                                            <div class="drop-zone-content" style="font-size: 0.75rem;">
                                                <i class="fas fa-cloud-upload-alt" style="font-size: 1rem; color: #0066cc;"></i>
                                                <p style="margin: 0.25rem 0; color: #0066cc; font-weight: 500;">FOTO</p>
                                            </div>
                                        </label>
                                        <label style="display: flex; align-items: center; gap: 0.25rem; cursor: pointer; font-size: 0.75rem; color: #666; white-space: nowrap;">
                                            <input type="checkbox" name="productos_friendly[][no_aplica_estampado]" class="checkbox-no-aplica" onchange="toggleImagenEstampado(this)">
                                            N/A
                                        </label>
                                    </div>
                                    <div class="foto-estampado-preview" style="display: grid; grid-template-columns: repeat(3, 60px); gap: 0.4rem; margin-top: 0.5rem;"></div>
                                </div>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
