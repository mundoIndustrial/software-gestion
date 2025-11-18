@extends('asesores.layout')

@section('title', 'Crear Nuevo Pedido')
@section('page-title', 'Crear Nuevo Pedido')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/asesores/create-friendly.css') }}">
@endpush

@section('content')
<div class="friendly-form-fullscreen">
    <!-- STEPPER VISUAL - CLICKEABLE -->
    <div class="stepper-container">
        <div class="stepper">
            <div class="step active" data-step="1" onclick="irAlPaso(1)" onkeypress="if(event.key==='Enter') irAlPaso(1)" tabindex="0" role="tab" aria-selected="true" style="cursor: pointer;">
                <div class="step-number">1</div>
                <div class="step-label">CLIENTE</div>
            </div>
            <div class="step-line"></div>
            <div class="step" data-step="2" onclick="irAlPaso(2)" onkeypress="if(event.key==='Enter') irAlPaso(2)" tabindex="0" role="tab" aria-selected="false" style="cursor: pointer;">
                <div class="step-number">2</div>
                <div class="step-label">PRODUCTOS</div>
            </div>
            <div class="step-line"></div>
            <div class="step" data-step="3" onclick="irAlPaso(3)" onkeypress="if(event.key==='Enter') irAlPaso(3)" tabindex="0" role="tab" aria-selected="false" style="cursor: pointer;">
                <div class="step-number">3</div>
                <div class="step-label">REVISAR</div>
            </div>
        </div>
    </div>

    <!-- FORMULARIO PASO A PASO -->
    <form id="formCrearPedidoFriendly" class="friendly-form">
        @csrf

        <!-- PASO 1: INFORMACIÓN DEL CLIENTE -->
        <div class="form-step active" data-step="1">
            <div class="step-header">
                <h2>PASO 1: INFORMACIÓN DEL CLIENTE</h2>
                <p>CUÉNTANOS QUIÉN ES TU CLIENTE</p>
            </div>

            <div class="form-section">
                <div class="form-group-large">
                    <label for="cliente">
                        <i class="fas fa-user"></i>
                        NOMBRE DEL CLIENTE *
                    </label>
                    <input type="text" id="cliente" name="cliente" class="input-large" placeholder="EJ: JUAN GARCÍA, EMPRESA ABC..." required>
                    <small class="help-text">EL NOMBRE DE TU CLIENTE O EMPRESA</small>
                </div>

                <div class="form-group-large">
                    <label for="forma_de_pago">
                        <i class="fas fa-credit-card"></i>
                        FORMA DE PAGO
                    </label>
                    <select id="forma_de_pago" name="forma_de_pago" class="input-large">
                        <option value="">-- SELECCIONA UNA OPCIÓN --</option>
                        <option value="CONTADO">💵 CONTADO (PAGO INMEDIATO)</option>
                        <option value="CRÉDITO">📋 CRÉDITO (PAGO DESPUÉS)</option>
                        <option value="50/50">⚖️ 50/50 (MITAD AHORA, MITAD DESPUÉS)</option>
                        <option value="ANTICIPO">🎯 ANTICIPO (PAGO ANTES)</option>
                    </select>
                    <small class="help-text">CÓMO PAGARÁ TU CLIENTE</small>
                </div>
            </div>

            <div class="form-actions">
                <button type="button" class="btn-next" onclick="irAlPaso(2)">
                    SIGUIENTE <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </div>

        <!-- PASO 2: AGREGAR PRODUCTOS -->
        <div class="form-step" data-step="2">
            <div class="step-header">
                <h2>PASO 2: PRODUCTOS DEL PEDIDO</h2>
                <p>AGREGA LAS PRENDAS QUE TU CLIENTE QUIERE</p>
            </div>

            <div class="form-section">
                <div class="productos-container" id="productosContainer"></div>
                <button type="button" class="btn-add-product-friendly" onclick="agregarProductoFriendly()">
                    <i class="fas fa-plus-circle"></i> AGREGAR PRENDA
                </button>
            </div>

            <!-- SECCIÓN: Archivos y Documentos de la Orden -->
            <div style="margin-top: 2.5rem; padding: 1.5rem; background: linear-gradient(135deg, #fff9e6 0%, #fffbf0 100%); border: 3px solid #ffc107; border-radius: 12px; box-shadow: 0 4px 12px rgba(255, 193, 7, 0.15);">
                <button type="button" onclick="toggleArchivosOrden(this)" style="width: 100%; display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; font-size: 1.1rem; font-weight: 700; color: #ff9800; background: none; border: none; cursor: pointer; padding: 0; margin-bottom: 0;">
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <i class="fas fa-paperclip"></i> ARCHIVOS ADICIONALES DE LA ORDEN
                    </div>
                    <i class="fas fa-chevron-down" style="transition: transform 0.3s ease;"></i>
                </button>
                
                <!-- Contenedor expandible -->
                <div class="archivos-orden-content" style="display: none; margin-top: 1rem;">
                    <p style="color: #666; font-size: 0.9rem; margin-bottom: 1rem;">💡 AGREGA DISEÑOS, ESPECIFICACIONES O CUALQUIER DOCUMENTO IMPORTANTE PARA ESTA ORDEN</p>
                    
                    <!-- Imágenes de la Orden -->
                    <div style="margin-bottom: 1rem; padding: 1.5rem; background: white; border-radius: 8px; border-left: 4px solid #ff9800;">
                        <button type="button" onclick="toggleSubseccion(this)" style="width: 100%; display: flex; align-items: center; justify-content: space-between; gap: 0.5rem; font-weight: 600; color: #ff9800; font-size: 0.95rem; background: none; border: none; cursor: pointer; padding: 0; margin-bottom: 0;">
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <i class="fas fa-image"></i> 📸 IMÁGENES DE REFERENCIA
                            </div>
                            <i class="fas fa-chevron-down" style="transition: transform 0.3s ease;"></i>
                        </button>
                        <p style="color: #999; font-size: 0.85rem; margin-bottom: 0.75rem; margin-top: 0.5rem;">DISEÑOS, MOCKUPS, REFERENCIAS VISUALES, ETC.</p>
                        <div class="subseccion-content" style="display: none;">
                            <div class="fotos-drop-zone fotos-drop-zone-compact" ondrop="manejarDrop(event)" ondragover="event.preventDefault()" ondragleave="this.classList.remove('drag-over')" style="background: #fff9e6; border-color: #ffc107;">
                                <input type="file" name="archivos_orden[imagenes][]" class="input-file-single" accept="image/*" multiple onchange="agregarFotosOrden(this.files, this)">
                                <div class="drop-zone-content">
                                    <i class="fas fa-cloud-upload-alt" style="color: #ff9800;"></i>
                                    <p style="color: #ff9800;">ARRASTRA IMÁGENES AQUÍ</p>
                                </div>
                            </div>
                            <div class="fotos-preview-orden" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 0.75rem; margin-top: 1rem;"></div>
                        </div>
                    </div>

                    <!-- Documentos de la Orden -->
                    <div style="padding: 1.5rem; background: white; border-radius: 8px; border-left: 4px solid #ff9800;">
                        <button type="button" onclick="toggleSubseccion(this)" style="width: 100%; display: flex; align-items: center; justify-content: space-between; gap: 0.5rem; font-weight: 600; color: #ff9800; font-size: 0.95rem; background: none; border: none; cursor: pointer; padding: 0; margin-bottom: 0;">
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <i class="fas fa-file-pdf"></i> 📄 DOCUMENTOS
                            </div>
                            <i class="fas fa-chevron-down" style="transition: transform 0.3s ease;"></i>
                        </button>
                        <p style="color: #999; font-size: 0.85rem; margin-bottom: 0.75rem; margin-top: 0.5rem;">PDF, WORD, EXCEL, ESPECIFICACIONES TÉCNICAS, ETC.</p>
                        <div class="subseccion-content" style="display: none;">
                            <div class="fotos-drop-zone fotos-drop-zone-compact" ondrop="manejarDrop(event)" ondragover="event.preventDefault()" ondragleave="this.classList.remove('drag-over')" style="background: #fff9e6; border-color: #ffc107;">
                                <input type="file" name="archivos_orden[documentos][]" class="input-file-single" accept=".pdf,.doc,.docx,.xls,.xlsx,.txt" multiple onchange="agregarDocumentosOrden(this.files, this)">
                                <div class="drop-zone-content">
                                    <i class="fas fa-cloud-upload-alt" style="color: #ff9800;"></i>
                                    <p style="color: #ff9800;">ARRASTRA DOCUMENTOS AQUÍ</p>
                                </div>
                            </div>
                            <div class="documentos-preview-orden" style="display: flex; flex-direction: column; gap: 0.5rem; margin-top: 1rem;"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="button" class="btn-prev" onclick="irAlPaso(1)">
                    <i class="fas fa-arrow-left"></i> ANTERIOR
                </button>
                <button type="button" class="btn-next" onclick="irAlPaso(3)">
                    REVISAR <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </div>

        <!-- PASO 3: REVISAR Y CONFIRMAR -->
        <div class="form-step" data-step="3">
            <div class="step-header">
                <h2>PASO 3: REVISAR TU PEDIDO</h2>
                <p>VERIFICA QUE TODO ESTÉ CORRECTO ANTES DE CREAR</p>
            </div>

            <div class="form-section">
                <div class="review-card">
                    <div class="review-title"><i class="fas fa-user"></i> INFORMACIÓN DEL CLIENTE</div>
                    <div class="review-content">
                        <div class="review-item">
                            <span class="review-label">CLIENTE:</span>
                            <span class="review-value" id="reviewCliente">-</span>
                        </div>
                        <div class="review-item">
                            <span class="review-label">FORMA DE PAGO:</span>
                            <span class="review-value" id="reviewFormaPago">-</span>
                        </div>
                    </div>
                </div>

                <div class="review-card">
                    <div class="review-title"><i class="fas fa-box"></i> PRODUCTOS (<span id="reviewTotalProductos">0</span>)</div>
                    <div class="review-content" id="reviewProductos"></div>
                </div>

                <div class="review-card highlight">
                    <div class="review-title"><i class="fas fa-calculator"></i> TOTALES</div>
                    <div class="review-content">
                        <div class="review-item large">
                            <span class="review-label">TOTAL DE PRENDAS:</span>
                            <span class="review-value" id="reviewCantidadTotal">0</span>
                        </div>
                    </div>
                </div>

                <div class="info-box success">
                    <i class="fas fa-check-circle"></i>
                    <div>
                        <strong>¡TODO LISTO!</strong>
                        <p>HAZ CLIC EN "CREAR PEDIDO" PARA GUARDAR TU PEDIDO EN EL SISTEMA.</p>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="button" class="btn-prev" onclick="irAlPaso(2)">
                    <i class="fas fa-arrow-left"></i> ANTERIOR
                </button>
                <button type="submit" class="btn-submit">
                    <i class="fas fa-check"></i> CREAR PEDIDO
                </button>
            </div>
        </div>
    </form>
</div>

<!-- TEMPLATE PARA PRODUCTO -->
<template id="productoTemplate">
    <div class="producto-card" data-producto-id="">
        <div class="producto-header">
            <h4 class="producto-titulo">PRENDA <span class="numero-producto">1</span></h4>
            <div style="display: flex; gap: 0.5rem;">
                <button type="button" class="btn-toggle-product" onclick="toggleProductoBody(this)" title="Expandir/Contraer" style="font-size: 1.5rem; line-height: 1; font-weight: bold;">
                    ▼
                </button>
                <button type="button" class="btn-remove-product" onclick="eliminarProductoFriendly(this)" title="Eliminar prenda">
                    &times;
                </button>
            </div>
        </div>
        <div class="producto-body" style="display: block;">
            <!-- SECCIÓN 1: Tipo de Prenda -->
            <div class="producto-section">
                <div class="section-title">
                    <i class="fas fa-shirt"></i> PASO 1: TIPO DE PRENDA
                </div>
                <div class="form-row">
                    <div class="form-col full">
                        <label title="Tipo de prenda"><i class="fas fa-list"></i> SELECCIONA O ESCRIBE EL TIPO *</label>
                        <div class="prenda-search-container">
                            <input type="text" name="productos_friendly[][nombre_producto]" class="prenda-search-input input-large" placeholder="BUSCA O ESCRIBE (CAMISA, CAMISETA, POLO...)" title="Tipo de prenda" required onkeyup="buscarPrendas(this)" onchange="actualizarResumenFriendly()">
                            <div class="prenda-suggestions">
                                <div class="prenda-suggestion-item" onclick="seleccionarPrenda('👔 CAMISA', this)">👔 CAMISA</div>
                                <div class="prenda-suggestion-item" onclick="seleccionarPrenda('👕 CAMISETA', this)">👕 CAMISETA</div>
                                <div class="prenda-suggestion-item" onclick="seleccionarPrenda('🎽 POLO', this)">🎽 POLO</div>
                                <div class="prenda-suggestion-item" onclick="seleccionarPrenda('👖 PANTALÓN', this)">👖 PANTALÓN</div>
                                <div class="prenda-suggestion-item" onclick="seleccionarPrenda('👗 FALDA', this)">👗 FALDA</div>
                                <div class="prenda-suggestion-item" onclick="seleccionarPrenda('🧥 CHAQUETA', this)">🧥 CHAQUETA</div>
                                <div class="prenda-suggestion-item" onclick="seleccionarPrenda('🧢 SUDADERA', this)">🧢 SUDADERA</div>
                                <div class="prenda-suggestion-item" onclick="seleccionarPrenda('� OTRO', this)">� OTRO</div>
                            </div>
                        </div>
                        <small class="help-text">PUEDES BUSCAR, SELECCIONAR O ESCRIBIR UNA PRENDA PERSONALIZADA</small>
                    </div>
                </div>
            </div>

            <!-- SECCIÓN 2: DESCRIPCIÓN -->
            <div class="producto-section">
                <div class="section-title">
                    <i class="fas fa-sticky-note"></i> PASO 2: DESCRIPCIÓN
                </div>
                <div class="form-row">
                    <div class="form-col full">
                        <label><i class="fas fa-pen"></i> DESCRIPCIÓN</label>
                        <textarea name="productos_friendly[][descripcion]" class="input-medium" placeholder="DESCRIPCIÓN DE LA PRENDA..." rows="2"></textarea>
                        <small class="help-text">DESCRIBE LA PRENDA, DETALLES ESPECIALES, LOGO, BORDADO, ESTAMPADO, ETC.</small>
                    </div>
                </div>
            </div>

            <!-- SECCIÓN 3: TALLAS Y VARIANTES -->
            <div class="producto-section">
                <button type="button" class="section-title-btn" onclick="toggleSeccion(this)">
                    <div class="section-title">
                        <i class="fas fa-ruler"></i> PASO 3: TALLAS Y VARIANTES
                        <i class="fas fa-chevron-down" style="margin-left: auto; transition: transform 0.3s ease;"></i>
                    </div>
                </button>
                <div class="section-content">
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
                                        <option value="">SELECCIONAR</option>
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
                </div>
            </div>

            <!-- SECCIÓN 4: Fotos de la Prenda -->
            <div class="producto-section">
                <button type="button" class="section-title-btn" onclick="toggleSeccion(this)">
                    <div class="section-title">
                        <i class="fas fa-images"></i> PASO 4: FOTOS DE LA PRENDA (MÁX. 3)
                        <i class="fas fa-chevron-down" style="margin-left: auto; transition: transform 0.3s ease;"></i>
                    </div>
                </button>
                <div class="section-content">
                <!-- Grid de 4 columnas para las fotos -->
                <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 1rem;">
                    <!-- Fotos de la Prenda -->
                    <div>
                        <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: 600; margin-bottom: 0.5rem; color: #0066cc; font-size: 0.85rem;">
                            <i class="fas fa-image"></i> FOTOS PRENDA
                        </label>
                        <label style="display: block; min-height: 80px; padding: 0.75rem; border: 2px dashed #0066cc; border-radius: 6px; cursor: pointer; text-align: center; background: #f0f7ff;" ondrop="manejarDrop(event)" ondragover="event.preventDefault()" ondragleave="this.classList.remove('drag-over')">
                            <input type="file" name="productos_friendly[][fotos][]" class="input-file-single" accept="image/*" multiple onchange="agregarFotos(this.files, this.closest('label').nextElementSibling)" style="display: none;">
                            <div class="drop-zone-content" style="font-size: 0.75rem;">
                                <i class="fas fa-cloud-upload-alt" style="font-size: 1rem; color: #0066cc;"></i>
                                <p style="margin: 0.25rem 0; color: #0066cc; font-weight: 500;">ARRASTRA O CLIC</p>
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
                                NO APLICA
                            </label>
                        </div>
                        <div class="foto-tela-preview" style="display: grid; grid-template-columns: repeat(3, 60px); gap: 0.4rem; margin-top: 0.5rem;"></div>
                    </div>

                    <!-- Imagen de Bordado -->
                    <div>
                        <div style="display: flex; align-items: center; gap: 0.5rem; font-weight: 600; margin-bottom: 0.5rem; color: #0066cc; font-size: 0.85rem;">
                            <i class="fas fa-palette"></i> BORDADO
                        </div>
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
                                NO APLICA
                            </label>
                        </div>
                        <div class="foto-bordado-preview" style="display: grid; grid-template-columns: repeat(3, 60px); gap: 0.4rem; margin-top: 0.5rem;"></div>
                    </div>

                    <!-- Imagen de Estampado -->
                    <div>
                        <div style="display: flex; align-items: center; gap: 0.5rem; font-weight: 600; margin-bottom: 0.5rem; color: #0066cc; font-size: 0.85rem;">
                            <i class="fas fa-print"></i> ESTAMPADO
                        </div>
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
                                NO APLICA
                            </label>
                        </div>
                        <div class="foto-estampado-preview" style="display: grid; grid-template-columns: repeat(3, 60px); gap: 0.4rem; margin-top: 0.5rem;"></div>
                    </div>
                </div>
                </div>
            </div>

        </div>
    </div>
</template>

@push('scripts')
<script>
// Ocultar navbar cuando se carga la página
document.addEventListener('DOMContentLoaded', function() {
    const topNav = document.querySelector('.top-nav');
    if (topNav) {
        topNav.style.display = 'none';
    }
    
    // Ocultar también la barra de navegación secundaria (page-header)
    const pageHeader = document.querySelector('.page-header');
    if (pageHeader) {
        pageHeader.style.display = 'none';
    }
});

// Mostrar navbar cuando se vuelve a la lista
window.addEventListener('beforeunload', function() {
    const topNav = document.querySelector('.top-nav');
    if (topNav) {
        topNav.style.display = '';
    }
    
    const pageHeader = document.querySelector('.page-header');
    if (pageHeader) {
        pageHeader.style.display = '';
    }
});

let productosCount = 0;

// Ir al paso especificado (sin validación - libre navegación)
function irAlPaso(paso) {
    // Ocultar todos los pasos
    document.querySelectorAll('.form-step').forEach(step => {
        step.classList.remove('active');
    });

    // Mostrar paso seleccionado
    const formStep = document.querySelector(`.form-step[data-step="${paso}"]`);
    if (formStep) {
        formStep.classList.add('active');
    }

    // Actualizar stepper
    document.querySelectorAll('.step').forEach(step => {
        step.classList.remove('active');
    });
    const stepElement = document.querySelector(`.step[data-step="${paso}"]`);
    if (stepElement) {
        stepElement.classList.add('active');
    }

    // Si es el paso 3, actualizar resumen
    if (paso === 3) {
        setTimeout(() => actualizarResumenFriendly(), 100);
    }
}

// Agregar producto
function agregarProductoFriendly() {
    productosCount++;
    const template = document.getElementById('productoTemplate');
    const clone = template.content.cloneNode(true);

    // Actualizar número de prenda
    clone.querySelector('.numero-producto').textContent = productosCount;
    
    // Asignar ID único al producto
    const productoId = 'producto-' + Date.now() + '-' + productosCount;
    clone.querySelector('.producto-card').dataset.productoId = productoId;
    
    // Inicializar array de fotos para este producto
    fotosSeleccionadas[productoId] = [];

    // Agregar al contenedor
    document.getElementById('productosContainer').appendChild(clone);
}

// Eliminar producto
function eliminarProductoFriendly(btn) {
    btn.closest('.producto-card').remove();
    actualizarResumenFriendly();
}

// Almacenar fotos seleccionadas
let fotosSeleccionadas = {};

// Manejar drag & drop
function manejarDrop(event) {
    event.preventDefault();
    event.stopPropagation();
    
    const dropZone = event.currentTarget;
    dropZone.classList.remove('drag-over');
    
    const files = event.dataTransfer.files;
    agregarFotos(files, dropZone);
}

// Agregar fotos
function agregarFotos(files, dropZone) {
    const productoCard = dropZone.closest('.producto-card');
    const productoId = productoCard ? productoCard.dataset.productoId : 'default';
    
    if (!fotosSeleccionadas[productoId]) {
        fotosSeleccionadas[productoId] = [];
    }
    
    // Agregar nuevas fotos (máximo 3 total)
    Array.from(files).forEach(file => {
        if (fotosSeleccionadas[productoId].length < 3) {
            fotosSeleccionadas[productoId].push(file);
        }
    });
    
    if (Array.from(files).length > 3 - fotosSeleccionadas[productoId].length + Array.from(files).length) {
        alert('Máximo 3 fotos permitidas.');
    }
    
    // Actualizar preview
    actualizarPreviewFotos(dropZone);
}

// Actualizar preview de fotos
function actualizarPreviewFotos(input) {
    const productoCard = input.closest('.producto-card');
    if (!productoCard) return;
    
    const productoId = productoCard.dataset.productoId || 'default';
    
    // Buscar el contenedor de preview
    let container = null;
    
    // Intentar encontrar el preview más cercano
    const label = input.closest('label');
    if (label && label.parentElement) {
        container = label.parentElement.querySelector('.fotos-preview');
    }
    
    // Si no lo encuentra, buscar en toda la tarjeta
    if (!container) {
        container = productoCard.querySelector('.fotos-preview');
    }
    
    if (!container) return;
    
    container.innerHTML = '';
    
    const fotos = fotosSeleccionadas[productoId] || [];
    
    fotos.forEach((file, index) => {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            const preview = document.createElement('div');
            preview.setAttribute('data-foto', 'true');
            preview.style.cssText = 'position: relative; width: 60px; height: 60px; border-radius: 4px; overflow: hidden; background: #f0f0f0;';
            const numeroFoto = index + 1;
            preview.innerHTML = `
                <img src="${e.target.result}" alt="Foto ${numeroFoto}" style="width: 100%; height: 100%; object-fit: cover;">
                <span class="foto-numero" style="position: absolute; top: 1px; left: 1px; background: #0066cc; color: white; border-radius: 50%; width: 16px; height: 16px; display: flex; align-items: center; justify-content: center; font-size: 9px; font-weight: bold; transition: opacity 0.2s;">${numeroFoto}</span>
                <button type="button" class="btn-eliminar-foto" onclick="eliminarFoto('${productoId}', ${index})" style="position: absolute; top: 1px; left: 1px; background: #f44336; color: white; border: none; border-radius: 50%; width: 16px; height: 16px; cursor: pointer; font-size: 11px; display: flex; align-items: center; justify-content: center; padding: 0; line-height: 1; opacity: 0; transition: opacity 0.2s;">✕</button>
            `;
            
            // Agregar evento hover
            preview.addEventListener('mouseenter', function() {
                this.querySelector('.foto-numero').style.opacity = '0';
                this.querySelector('.btn-eliminar-foto').style.opacity = '1';
            });
            
            preview.addEventListener('mouseleave', function() {
                this.querySelector('.foto-numero').style.opacity = '1';
                this.querySelector('.btn-eliminar-foto').style.opacity = '0';
            });
            
            container.appendChild(preview);
        };
        
        reader.readAsDataURL(file);
    });
}

// Eliminar foto individual
function eliminarFoto(productoId, index) {
    if (fotosSeleccionadas[productoId]) {
        fotosSeleccionadas[productoId].splice(index, 1);
        
        // Actualizar el input file
        const productoCard = document.querySelector(`[data-producto-id="${productoId}"]`);
        if (productoCard) {
            const input = productoCard.querySelector('input[type="file"]');
            if (input) {
                actualizarPreviewFotos(input);
            }
        }
    }
}

// Actualizar resumen
function actualizarResumenFriendly() {
    // Información del cliente
    const cliente = document.getElementById('cliente').value || '-';
    const formaPago = document.getElementById('forma_de_pago').value || '-';

    document.getElementById('reviewCliente').textContent = cliente;
    document.getElementById('reviewFormaPago').textContent = formatearFormaPago(formaPago);

    // Productos
    const productos = document.querySelectorAll('.producto-card');
    let totalProductos = 0;
    let totalCantidad = 0;

    const reviewProductos = document.getElementById('reviewProductos');
    reviewProductos.innerHTML = '';

    productos.forEach((producto, index) => {
        const nombre = producto.querySelector('input[name*="nombre_producto"]').value || 'Sin nombre';
        const cantidad = parseInt(producto.querySelector('input[name*="cantidad"]').value) || 0;
        const talla = producto.querySelector('select[name*="talla"]').value || '-';
        const color = producto.querySelector('input[name*="color"]').value || '-';

        totalProductos++;
        totalCantidad += cantidad;

        // Crear tarjeta del producto
        const card = document.createElement('div');
        card.className = 'review-product-card';
        
        // Información del producto
        let html = `
            <div class="review-product-info">
                <div class="review-item">
                    <span class="review-label">${nombre}</span>
                    <span class="review-value">${cantidad} unidades - Talla ${talla}</span>
                </div>
                <div class="review-item">
                    <span class="review-label">Color:</span>
                    <span class="review-value">${color}</span>
                </div>
            </div>
        `;
        
        // Fotos del producto
        const fotosInputs = producto.querySelectorAll('input[type="file"]');
        let tienesFotos = false;
        
        fotosInputs.forEach((fileInput, fotoIndex) => {
            if (fileInput.files && fileInput.files[0]) {
                tienesFotos = true;
            }
        });
        
        if (tienesFotos) {
            html += '<div class="review-product-fotos">';
            fotosInputs.forEach((fileInput, fotoIndex) => {
                if (fileInput.files && fileInput.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const fotoDiv = document.createElement('div');
                        fotoDiv.className = 'review-foto';
                        fotoDiv.innerHTML = `
                            <img src="${e.target.result}" alt="Foto ${fotoIndex + 1}">
                            <span class="review-foto-numero">${fotoIndex + 1}</span>
                        `;
                        card.querySelector('.review-product-fotos').appendChild(fotoDiv);
                    };
                    reader.readAsDataURL(fileInput.files[0]);
                }
            });
            html += '</div>';
        }
        
        card.innerHTML = html;
        reviewProductos.appendChild(card);
    });

    document.getElementById('reviewTotalProductos').textContent = totalProductos;
    document.getElementById('reviewCantidadTotal').textContent = totalCantidad;
}

// Formatear forma de pago
function formatearFormaPago(valor) {
    const opciones = {
        'CONTADO': '💵 Contado',
        'CRÉDITO': '📋 Crédito',
        '50/50': '⚖️ 50/50',
        'ANTICIPO': '🎯 Anticipo'
    };
    return opciones[valor] || '-';
}

// ============ BÚSQUEDA DE PRENDAS ============
function buscarPrendas(input) {
    const valor = input.value.toLowerCase();
    const suggestions = input.closest('.prenda-search-container').querySelector('.prenda-suggestions');
    const items = suggestions.querySelectorAll('.prenda-suggestion-item');
    
    if (valor.length === 0) {
        suggestions.classList.remove('show');
        return;
    }
    
    let hayCoincidencias = false;
    items.forEach(item => {
        const texto = item.textContent.toLowerCase();
        if (texto.includes(valor)) {
            item.style.display = 'block';
            hayCoincidencias = true;
        } else {
            item.style.display = 'none';
        }
    });
    
    if (hayCoincidencias) {
        suggestions.classList.add('show');
    } else {
        suggestions.classList.remove('show');
    }
}

function seleccionarPrenda(valor, element) {
    const input = element.closest('.prenda-search-container').querySelector('.prenda-search-input');
    input.value = valor;
    input.closest('.prenda-search-container').querySelector('.prenda-suggestions').classList.remove('show');
    actualizarResumenFriendly();
}

// Cerrar sugerencias al hacer clic fuera
document.addEventListener('click', function(e) {
    if (!e.target.closest('.prenda-search-container')) {
        document.querySelectorAll('.prenda-suggestions').forEach(s => s.classList.remove('show'));
    }
});

// ============ IMÁGENES ADICIONALES ============
function toggleAdditionalImages(btn) {
    const section = btn.closest('.producto-section').querySelector('.additional-images-section');
    section.classList.toggle('show');
    
    if (section.classList.contains('show')) {
        btn.innerHTML = '<i class="fas fa-minus"></i> Ocultar Imágenes Adicionales';
    } else {
        btn.innerHTML = '<i class="fas fa-plus"></i> Agregar Imagen de Tela o Bordado';
    }
}

function agregarFotoTela(input) {
    const productoCard = input.closest('.producto-card');
    if (!productoCard) return;
    
    const container = productoCard.querySelector('.foto-tela-preview');
    if (container) {
        mostrarPreviewFoto(input, container);
    }
}

function agregarFotoBordado(input) {
    const productoCard = input.closest('.producto-card');
    if (!productoCard) return;
    
    const container = productoCard.querySelector('.foto-bordado-preview');
    if (container) {
        mostrarPreviewFoto(input, container);
    }
}

function agregarFotoEstampado(input) {
    const productoCard = input.closest('.producto-card');
    if (!productoCard) return;
    
    const container = productoCard.querySelector('.foto-estampado-preview');
    if (container) {
        mostrarPreviewFoto(input, container);
    }
}

function mostrarPreviewFoto(input, container) {
    // Obtener fotos existentes en el contenedor
    const fotosExistentes = container.querySelectorAll('div[data-foto]').length;
    const fotosNuevas = input.files.length;
    const totalFotos = fotosExistentes + fotosNuevas;
    
    // Limitar a máximo 3 fotos totales
    if (totalFotos > 3) {
        alert('Máximo 3 fotos permitidas por sección. Ya tienes ' + fotosExistentes + ' foto(s).');
        return;
    }
    
    // Configurar el contenedor como grid 3 columnas con tamaño fijo
    if (!container.style.display) {
        container.style.cssText = 'display: grid; grid-template-columns: repeat(3, 60px); gap: 0.4rem; margin-top: 0.5rem;';
    }
    
    Array.from(input.files).forEach((file, index) => {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.createElement('div');
            preview.setAttribute('data-foto', 'true');
            preview.style.cssText = 'position: relative; width: 60px; height: 60px; border-radius: 4px; overflow: hidden; background: #f0f0f0;';
            const numeroFoto = fotosExistentes + index + 1;
            preview.innerHTML = `
                <img src="${e.target.result}" alt="Preview" style="width: 100%; height: 100%; object-fit: cover;">
                <span class="foto-numero" style="position: absolute; top: 1px; left: 1px; background: #0066cc; color: white; border-radius: 50%; width: 16px; height: 16px; display: flex; align-items: center; justify-content: center; font-size: 9px; font-weight: bold; transition: opacity 0.2s;">${numeroFoto}</span>
                <button type="button" class="btn-eliminar-foto" onclick="this.closest('div').remove()" style="position: absolute; top: 1px; left: 1px; background: #f44336; color: white; border: none; border-radius: 50%; width: 16px; height: 16px; cursor: pointer; font-size: 11px; display: flex; align-items: center; justify-content: center; padding: 0; line-height: 1; opacity: 0; transition: opacity 0.2s;">✕</button>
            `;
            
            // Agregar evento hover
            preview.addEventListener('mouseenter', function() {
                this.querySelector('.foto-numero').style.opacity = '0';
                this.querySelector('.btn-eliminar-foto').style.opacity = '1';
            });
            
            preview.addEventListener('mouseleave', function() {
                this.querySelector('.foto-numero').style.opacity = '1';
                this.querySelector('.btn-eliminar-foto').style.opacity = '0';
            });
            container.appendChild(preview);
        };
        reader.readAsDataURL(file);
    });
    
    // Limpiar el input después de procesar
    input.value = '';
}

// ============ TABLA DE TALLAS ============
function agregarFilaTalla(btn) {
    const tbody = btn.closest('.producto-section').querySelector('.tallas-tbody');
    const ultimaFila = tbody.querySelector('.talla-row:last-child');
    const nuevaFila = ultimaFila.cloneNode(true);
    
    // Copiar valores de la última fila (excepto cantidad y talla)
    // Cantidad se mantiene en 1, Talla se deja vacía para que el usuario la seleccione
    const inputs = nuevaFila.querySelectorAll('input, select');
    inputs.forEach((input, index) => {
        if (index === 0) {
            // Cantidad: siempre 1
            input.value = '1';
        } else if (index === 1) {
            // Talla: dejar vacía para que seleccione
            input.value = '';
        }
        // El resto mantiene los valores de la última fila (Color, Manga, Tela, Ref. Hilo)
    });
    
    tbody.appendChild(nuevaFila);
    actualizarResumenFriendly();
}

function eliminarFilaTalla(btn) {
    const fila = btn.closest('.talla-row');
    const tbody = fila.closest('.tallas-tbody');
    
    // No permitir eliminar si es la única fila
    if (tbody.querySelectorAll('.talla-row').length > 1) {
        fila.remove();
        actualizarResumenFriendly();
    } else {
        alert('Debe haber al menos una talla');
    }
}

// ============ TOGGLE IMÁGENES CON CHECKBOX ============
function toggleImagenTela(checkbox) {
    const zone = checkbox.closest('.image-upload-group').querySelector('.imagen-tela-zone');
    const preview = checkbox.closest('.image-upload-group').querySelector('.foto-tela-preview');
    const fileInput = checkbox.closest('.image-upload-group').querySelector('input[type="file"]');
    
    if (checkbox.checked) {
        zone.style.display = 'none';
        preview.style.display = 'none';
        fileInput.value = '';
        preview.innerHTML = '';
    } else {
        zone.style.display = 'block';
        preview.style.display = 'block';
    }
}

function toggleImagenBordado(checkbox) {
    const parentDiv = checkbox.closest('div');
    const zone = parentDiv.querySelector('label');
    const preview = parentDiv.parentElement.querySelector('.foto-bordado-preview');
    const fileInput = zone.querySelector('input[type="file"]');
    
    if (checkbox.checked) {
        zone.style.display = 'none';
        preview.style.display = 'none';
        fileInput.value = '';
        preview.innerHTML = '';
    } else {
        zone.style.display = 'flex';
        preview.style.display = 'block';
    }
}

function toggleImagenEstampado(checkbox) {
    const parentDiv = checkbox.closest('div');
    const zone = parentDiv.querySelector('label');
    const preview = parentDiv.parentElement.querySelector('.foto-estampado-preview');
    const fileInput = zone.querySelector('input[type="file"]');
    
    if (checkbox.checked) {
        zone.style.display = 'none';
        preview.style.display = 'none';
        fileInput.value = '';
        preview.innerHTML = '';
    } else {
        zone.style.display = 'flex';
        preview.style.display = 'block';
    }
}

// ============ ARCHIVOS DE LA ORDEN ============
function agregarFotosOrden(files, input) {
    const container = input.closest('.producto-section').querySelector('.fotos-preview-orden');
    container.innerHTML = '';
    
    Array.from(files).forEach((file, index) => {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.createElement('div');
            preview.style.cssText = 'position: relative; width: 100%; height: 100px; border-radius: 6px; overflow: hidden; background: #f0f0f0;';
            preview.innerHTML = `
                <img src="${e.target.result}" alt="Preview" style="width: 100%; height: 100%; object-fit: cover;">
                <button type="button" style="position: absolute; top: 0.25rem; right: 0.25rem; width: 24px; height: 24px; background: #f44336; color: white; border: none; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; line-height: 1; font-weight: bold;" onclick="this.closest('div').remove();">&times;</button>
            `;
            container.appendChild(preview);
        };
        reader.readAsDataURL(file);
    });
}

function agregarDocumentosOrden(files, input) {
    const container = input.closest('.producto-section') ? input.closest('.producto-section').querySelector('.documentos-preview-orden') : document.querySelector('.documentos-preview-orden');
    container.innerHTML = '';
    
    Array.from(files).forEach((file, index) => {
        const item = document.createElement('div');
        item.style.cssText = 'display: flex; align-items: center; gap: 0.5rem; padding: 0.75rem; background: #f9f9f9; border: 1px solid #e0e0e0; border-radius: 6px;';
        item.innerHTML = `
            <i class="fas fa-file" style="color: #ff9800; font-size: 1.2rem;"></i>
            <span style="flex: 1; font-size: 0.9rem; color: #333;">${file.name}</span>
            <button type="button" style="width: 24px; height: 24px; background: #f44336; color: white; border: none; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 1rem; line-height: 1; font-weight: bold;" onclick="this.closest('div').remove();">&times;</button>
        `;
        container.appendChild(item);
    });
}

// ============ EXPANDIR/CONTRAER ARCHIVOS ============
function toggleArchivosOrden(btn) {
    const content = btn.closest('div').querySelector('.archivos-orden-content');
    const icon = btn.querySelector('i.fa-chevron-down');
    
    if (content.style.display === 'none') {
        content.style.display = 'block';
        icon.style.transform = 'rotate(180deg)';
    } else {
        content.style.display = 'none';
        icon.style.transform = 'rotate(0deg)';
    }
}

function toggleSubseccion(btn) {
    const content = btn.closest('div').querySelector('.subseccion-content');
    const icon = btn.querySelector('i.fa-chevron-down');
    
    if (content.style.display === 'none') {
        content.style.display = 'block';
        icon.style.transform = 'rotate(180deg)';
    } else {
        content.style.display = 'none';
        icon.style.transform = 'rotate(0deg)';
    }
}

// ============ EXPANDIR/CONTRAER SECCIONES DE PRENDA ============
function toggleSeccion(btn) {
    const content = btn.closest('.producto-section').querySelector('.section-content');
    const icon = btn.querySelector('i.fa-chevron-down');
    
    if (content.style.display === 'none') {
        content.style.display = 'block';
        icon.style.transform = 'rotate(180deg)';
    } else {
        content.style.display = 'none';
        icon.style.transform = 'rotate(0deg)';
    }
}

// ============ EXPANDIR/CONTRAER PRENDA COMPLETA ============
function toggleProductoBody(btn) {
    const body = btn.closest('.producto-card').querySelector('.producto-body');
    
    if (body.style.display === 'none') {
        body.style.display = 'block';
        btn.style.transform = 'rotate(180deg)';
    } else {
        body.style.display = 'none';
        btn.style.transform = 'rotate(0deg)';
    }
}

// Manejar envío del formulario
document.getElementById('formCrearPedidoFriendly').addEventListener('submit', function(e) {
    e.preventDefault();

    // Recolectar datos
    const formData = new FormData(this);

    // Enviar
    fetch('{{ route("asesores.pedidos.store") }}', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('¡Pedido creado exitosamente!');
            window.location.href = '{{ route("asesores.pedidos.index") }}';
        } else {
            alert('Error: ' + (data.message || 'No se pudo crear el pedido'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al crear el pedido. Por favor intenta de nuevo.');
    });
});

// Agregar primer producto automáticamente
document.addEventListener('DOMContentLoaded', function() {
    agregarProductoFriendly();
});
</script>
@endpush

@endsection
