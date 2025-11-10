@extends('asesores.layout')

@section('title', 'Crear Pedido')
@section('page-title', 'Crear Nuevo Pedido')

@section('content')
<div class="erp-form-container">
    
    <!-- Header Profesional -->
    <div class="erp-form-header">
        <h1 class="erp-form-title">Nuevo Pedido</h1>
        <p class="erp-form-subtitle">Complete la información detallada del pedido</p>
        <div class="erp-form-meta">
            <div class="erp-meta-item">
                <span class="material-symbols-rounded">calendar_today</span>
                <span>{{ date('d/m/Y') }}</span>
            </div>
            <div class="erp-meta-item">
                <span class="material-symbols-rounded">person</span>
                <span>{{ Auth::user()->name }}</span>
            </div>
            <div class="erp-meta-item">
                <span class="material-symbols-rounded">tag</span>
                <span>Pedido #{{ $siguientePedido }}</span>
            </div>
        </div>
    </div>

    <!-- Pestañas de Navegación -->
    <div class="erp-tabs">
        <button type="button" class="erp-tab active" data-tab="general">
            <span class="material-symbols-rounded">info</span>
            Información General
        </button>
        <button type="button" class="erp-tab" data-tab="productos">
            <span class="material-symbols-rounded">inventory_2</span>
            Productos
        </button>
        <button type="button" class="erp-tab" data-tab="resumen">
            <span class="material-symbols-rounded">summarize</span>
            Resumen
        </button>
    </div>

    <form id="formCrearPedido" method="POST" enctype="multipart/form-data">
        @csrf
        
        <!-- PESTAÑA: INFORMACIÓN GENERAL -->
        <div class="erp-tab-content" data-content="general">
            
            <!-- Sección: Información del Pedido -->
            <div class="erp-section">
                <div class="erp-section-header">
                    <div class="erp-section-title">
                        <span class="material-symbols-rounded">description</span>
                        Información del Pedido
                        <span class="erp-section-badge">Requerido</span>
                    </div>
                    <div class="erp-section-toggle">
                        <span class="material-symbols-rounded">expand_more</span>
                    </div>
                </div>
                <div class="erp-section-body">
            
                    <div class="erp-form-grid cols-3">
                        <div class="erp-form-group">
                            <label class="erp-label required">
                                <span class="material-symbols-rounded">tag</span>
                                Número de Pedido
                            </label>
                            <input type="number" id="pedido" name="pedido" value="{{ $siguientePedido }}" class="erp-input" readonly required>
                        </div>

                        <div class="erp-form-group">
                            <label class="erp-label required">
                                <span class="material-symbols-rounded">business</span>
                                Cliente
                            </label>
                            <input type="text" id="cliente" name="cliente" class="erp-input" placeholder="Ej: INVERSIONES EVAN" required>
                        </div>

                        <div class="erp-form-group">
                            <label class="erp-label">
                                <span class="material-symbols-rounded">payments</span>
                                Forma de Pago
                            </label>
                            <select id="forma_de_pago" name="forma_de_pago" class="erp-select">
                                <option value="">Seleccionar...</option>
                                <option value="Crédito">Crédito</option>
                                <option value="Contado">Contado</option>
                                <option value="50/50">50/50</option>
                                <option value="Anticipo">Anticipo</option>
                            </select>
                        </div>

                        <div class="erp-form-group">
                            <label class="erp-label">
                                <span class="material-symbols-rounded">flag</span>
                                Estado Inicial
                            </label>
                            <select id="estado" name="estado" class="erp-select">
                                <option value="No iniciado" selected>No iniciado</option>
                                <option value="En Ejecución">En Ejecución</option>
                            </select>
                        </div>

                        <div class="erp-form-group full-width">
                            <label class="erp-label">
                                <span class="material-symbols-rounded">description</span>
                                Descripción General
                            </label>
                            <textarea id="descripcion" name="descripcion" rows="3" class="erp-textarea" placeholder="Descripción general del pedido..."></textarea>
                        </div>

                        <div class="erp-form-group full-width">
                            <label class="erp-label">
                                <span class="material-symbols-rounded">notes</span>
                                Novedades
                            </label>
                            <textarea id="novedades" name="novedades" rows="2" class="erp-textarea" placeholder="Novedades o instrucciones especiales..."></textarea>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- PESTAÑA: PRODUCTOS -->
        <div class="erp-tab-content" data-content="productos" style="display:none;">
            
            <div id="productosContainer">
                <!-- Los productos se agregarán aquí dinámicamente -->
            </div>
            
            <button type="button" id="btnAgregarProducto" class="erp-btn erp-btn-primary erp-btn-lg" style="width: 100%; margin-top: 1rem;">
                <span class="material-symbols-rounded">add_circle</span>
                Agregar Producto
            </button>

        </div>

        <!-- PESTAÑA: RESUMEN -->
        <div class="erp-tab-content" data-content="resumen" style="display:none;">
            <div class="erp-summary">
                <div class="erp-summary-grid">
                    <div class="erp-summary-item">
                        <div class="erp-summary-label">Total Productos</div>
                        <div class="erp-summary-value" id="totalProductos">0</div>
                    </div>
                    <div class="erp-summary-item">
                        <div class="erp-summary-label">Total Unidades</div>
                        <div class="erp-summary-value" id="cantidadTotal">0</div>
                    </div>
                    <div class="erp-summary-item">
                        <div class="erp-summary-label">Valor Total</div>
                        <div class="erp-summary-value" id="valorTotal">$0</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Acciones del Formulario -->
        <div class="erp-form-actions">
            <div class="erp-actions-left">
                <a href="{{ route('asesores.pedidos.index') }}" class="erp-btn erp-btn-secondary">
                    <span class="material-symbols-rounded">arrow_back</span>
                    Cancelar
                </a>
            </div>
            <div class="erp-actions-right">
                <button type="submit" class="erp-btn erp-btn-success erp-btn-lg">
                    <span class="material-symbols-rounded">check_circle</span>
                    Crear Pedido
                </button>
            </div>
        </div>

    </form>
</div>

<!-- Template para Producto -->
<template id="productoTemplate">
    <div class="erp-product-card producto-item">
        <div class="erp-product-header">
            <div class="erp-product-number producto-numero">1</div>
            <div class="erp-product-actions">
                <button type="button" class="erp-btn erp-btn-sm erp-btn-danger btn-remove-product">
                    <span class="material-symbols-rounded">delete</span>
                    Eliminar
                </button>
            </div>
        </div>
        
        <!-- Información Básica -->
        <div class="erp-section">
            <div class="erp-section-header">
                <div class="erp-section-title">
                    <span class="material-symbols-rounded">checkroom</span>
                    Información Básica
                </div>
                <div class="erp-section-toggle">
                    <span class="material-symbols-rounded">expand_more</span>
                </div>
            </div>
            <div class="erp-section-body">
                <div class="erp-form-grid cols-2">
                    <div class="erp-form-group full-width">
                        <label class="erp-label required">
                            <span class="material-symbols-rounded">label</span>
                            Tipo de Prenda
                        </label>
                        <input type="text" name="productos[][nombre_producto]" class="erp-input" placeholder="Ej: CAMISA TIPO POLO" required>
                    </div>

                <!-- Tallas y Cantidades -->
                <div class="form-group full-width">
                    <label class="section-label">
                        <span class="material-symbols-rounded">straighten</span>
                        Tallas y Cantidades
                    </label>
                    <div class="tallas-configuracion">
                        <!-- Container para tallas -->
                        <div class="tallas-list">
                            <div class="talla-item">
                                <span class="talla-label">Talla</span>
                                <select class="talla-select">
                                    <option value="">Seleccionar...</option>
                                    <option value="XXS">XXS</option>
                                    <option value="XS">XS</option>
                                    <option value="S">S</option>
                                    <option value="M">M</option>
                                    <option value="L">L</option>
                                    <option value="XL">XL</option>
                                    <option value="XXL">XXL</option>
                                    <option value="XXXL">XXXL</option>
                                    <option value="4">4</option>
                                    <option value="6">6</option>
                                    <option value="8">8</option>
                                    <option value="10">10</option>
                                    <option value="12">12</option>
                                    <option value="14">14</option>
                                    <option value="16">16</option>
                                    <option value="Única">Única</option>
                                    <option value="Personalizada">Otra</option>
                                </select>
                                <div class="cantidad-wrapper">
                                    <span class="cantidad-label">Cant:</span>
                                    <input type="number" 
                                           class="cantidad-input" 
                                           min="1" 
                                           value="1">
                                </div>
                                <button type="button" class="btn-remove-talla" style="display:none;">
                                    <span class="material-symbols-rounded">close</span>
                                </button>
                            </div>
                        </div>

                        <!-- Botón para agregar más tallas -->
                        <button type="button" class="btn-add-talla" title="Agregar Talla">
                            <span class="material-symbols-rounded">add</span>
                        </button>

                        <!-- Resumen de cantidades -->
                        <div class="tallas-resumen">
                            <span class="resumen-label">Total de Unidades:</span>
                            <span class="resumen-total">0</span>
                        </div>
                    </div>
                    <input type="hidden" name="productos[][tallas_cantidades_json]" class="tallas-cantidades-hidden">
                    <input type="hidden" name="productos[][cantidad]" class="producto-cantidad" value="0">
                </div>

                <!-- Tela -->
                <div class="form-group">
                    <label>Tela</label>
                    <input type="text" name="productos[][tela]" placeholder="Ej: TELA LAFAYETTE">
                </div>

                <!-- Manga -->
                <div class="form-group">
                    <label>Tipo de Manga</label>
                    <select name="productos[][tipo_manga]">
                        <option value="">Seleccionar...</option>
                        <option value="Manga Corta">Manga Corta</option>
                        <option value="Manga Larga">Manga Larga</option>
                        <option value="Sin Manga">Sin Manga</option>
                        <option value="Manga 3/4">Manga 3/4</option>
                    </select>
                </div>

                <!-- Configuración de Telas por Sección -->
                <div class="form-group full-width">
                    <label class="section-label">
                        <span class="material-symbols-rounded">palette</span>
                        Configuración de Telas y Colores
                    </label>
                    <div class="telas-configuracion">
                        <!-- Tela Principal -->
                        <div class="tela-section principal">
                            <div class="tela-section-header">
                                <span class="material-symbols-rounded">checkroom</span>
                                <span>Tela Principal (Cuerpo)</span>
                            </div>
                            <div class="tela-fields">
                                <input type="text" 
                                       name="productos[][tela]" 
                                       placeholder="Ej: TELA LAFAYETTE, DRIL LIVIANO"
                                       class="tela-input">
                                <input type="text" 
                                       name="productos[][color]" 
                                       placeholder="Color: Ej: AZUL REY"
                                       class="color-input">
                            </div>
                        </div>

                        <!-- Botón para agregar secciones adicionales -->
                        <button type="button" class="btn-add-tela-section">
                            <span class="material-symbols-rounded">add_circle</span>
                            Agregar Sección con Tela Diferente
                        </button>

                        <!-- Container para secciones adicionales -->
                        <div class="telas-adicionales-container"></div>
                    </div>
                    <input type="hidden" name="productos[][configuracion_telas]" class="configuracion-telas-hidden">
                </div>

                <!-- Género -->
                <div class="form-group">
                    <label>Género</label>
                    <select name="productos[][genero]">
                        <option value="">Seleccionar...</option>
                        <option value="Dama">Dama</option>
                        <option value="Caballero">Caballero</option>
                        <option value="Unisex">Unisex</option>
                    </select>
                </div>

                <!-- Cantidad -->
                <div class="form-group">
                    <label>Cantidad *</label>
                    <input type="number" name="productos[][cantidad]" min="1" value="1" class="producto-cantidad" required>
                </div>

                <!-- Referencia de Hilo -->
                <div class="form-group">
                    <label>Ref. Hilo</label>
                    <input type="text" name="productos[][ref_hilo]" placeholder="Ej: REF HILO 293">
                </div>

                <!-- Descripción Completa -->
                <div class="form-group full-width">
                    <label>Descripción Completa</label>
                    <textarea name="productos[][descripcion]" rows="4" placeholder="Ej: CON CUELLOS Y PUÑOS EN HILO, CON BROCHES, SIN BOLSILLO, MODELO DE BODEGA..."></textarea>
                </div>

                <!-- Tipo de Personalización -->
                <div class="form-group full-width">
                    <label>Tipo de Personalización</label>
                    <div class="personalizacion-selector">
                        <div class="personalizacion-option">
                            <input type="checkbox" id="bordado_PRODUCTINDEX" class="personalizacion-checkbox" data-target="bordado">
                            <label for="bordado_PRODUCTINDEX" class="personalizacion-label">
                                <span class="material-symbols-rounded">draw</span>
                                <span class="option-title">Bordado</span>
                                <span class="option-description">Logos y textos bordados</span>
                            </label>
                        </div>
                        <div class="personalizacion-option">
                            <input type="checkbox" id="estampado_PRODUCTINDEX" class="personalizacion-checkbox" data-target="estampado">
                            <label for="estampado_PRODUCTINDEX" class="personalizacion-label">
                                <span class="material-symbols-rounded">palette</span>
                                <span class="option-title">Estampado</span>
                                <span class="option-description">Serigrafía y estampados</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Detalles de Bordado (oculto por defecto) -->
                <div class="form-group full-width personalizacion-details bordado-only" id="bordado-details_PRODUCTINDEX" style="display:none;">
                    <div class="personalizacion-content">
                        <label class="personalizacion-content-label">
                            <span class="material-symbols-rounded">draw</span>
                            Detalles de Bordado
                        </label>
                        <textarea name="productos[][bordados]" rows="3" placeholder="Ej: LOGO BORDADO EN LADO DERECHO DEL PECHO 'INVERSIONES EVAN', LOGO BORDADO EN LADO IZQUIERDO DEL PECHO 'DIFFER'"></textarea>
                        
                        <!-- Imágenes de Bordado -->
                        <div class="personalizacion-images-section">
                            <label class="images-section-label">
                                <span class="material-symbols-rounded">image</span>
                                Imágenes de Bordados
                            </label>
                            <div class="upload-area">
                                <input type="file" 
                                       name="productos[][imagenes_personalizacion][]" 
                                       accept="image/*" 
                                       multiple 
                                       class="personalizacion-images-input"
                                       id="bordado-images-PRODUCTINDEX">
                                <label for="bordado-images-PRODUCTINDEX" class="upload-label">
                                    <span class="material-symbols-rounded">cloud_upload</span>
                                    <span class="upload-text">Click para seleccionar múltiples imágenes</span>
                                    <span class="upload-hint">o arrastra archivos aquí</span>
                                </label>
                            </div>
                            <div class="personalizacion-images-preview"></div>
                            <small class="form-help">💡 Puedes seleccionar varias imágenes a la vez (Ctrl+Click o Shift+Click)</small>
                        </div>
                    </div>
                </div>

                <!-- Detalles de Estampado (oculto por defecto) -->
                <div class="form-group full-width personalizacion-details estampado-only" id="estampado-details_PRODUCTINDEX" style="display:none;">
                    <div class="personalizacion-content">
                        <label class="personalizacion-content-label">
                            <span class="material-symbols-rounded">palette</span>
                            Detalles de Estampado
                        </label>
                        <textarea name="productos[][estampados]" rows="3" placeholder="Ej: ESTAMPADO EN ESPALDA 'NOMBRE EMPRESA', SERIGRAFÍA EN PECHO"></textarea>
                        
                        <!-- Imágenes de Estampado -->
                        <div class="personalizacion-images-section">
                            <label class="images-section-label">
                                <span class="material-symbols-rounded">image</span>
                                Imágenes de Estampados
                            </label>
                            <div class="upload-area">
                                <input type="file" 
                                       name="productos[][imagenes_personalizacion][]" 
                                       accept="image/*" 
                                       multiple 
                                       class="personalizacion-images-input"
                                       id="estampado-images-PRODUCTINDEX">
                                <label for="estampado-images-PRODUCTINDEX" class="upload-label">
                                    <span class="material-symbols-rounded">cloud_upload</span>
                                    <span class="upload-text">Click para seleccionar múltiples imágenes</span>
                                    <span class="upload-hint">o arrastra archivos aquí</span>
                                </label>
                            </div>
                            <div class="personalizacion-images-preview"></div>
                            <small class="form-help">💡 Puedes seleccionar varias imágenes a la vez (Ctrl+Click o Shift+Click)</small>
                        </div>
                    </div>
                </div>

                <!-- Detalles Combinados (cuando ambos están seleccionados) -->
                <div class="form-group full-width personalizacion-details combinado-details" id="combinado-details_PRODUCTINDEX" style="display:none;">
                    <div class="personalizacion-content">
                        <label class="personalizacion-content-label">
                            <span class="material-symbols-rounded">draw</span>
                            <span class="material-symbols-rounded">palette</span>
                            Detalles de Bordado y Estampado
                        </label>
                        <textarea name="productos[][personalizacion_combinada]" rows="5" placeholder="Describe todos los detalles de bordados y estampados aquí...&#10;&#10;Ejemplo:&#10;BORDADOS:&#10;- Logo bordado en pecho derecho 'EMPRESA'&#10;- Texto bordado en espalda 'CONTRATISTA'&#10;&#10;ESTAMPADOS:&#10;- Serigrafía en manga izquierda"></textarea>
                        
                        <!-- Imágenes de Referencia -->
                        <div class="personalizacion-images-section">
                            <label class="images-section-label">
                                <span class="material-symbols-rounded">image</span>
                                Imágenes de Referencia
                            </label>
                            <div class="upload-area">
                                <input type="file" 
                                       name="productos[][imagenes_personalizacion][]" 
                                       accept="image/*" 
                                       multiple 
                                       class="personalizacion-images-input"
                                       id="combinado-images-PRODUCTINDEX">
                                <label for="combinado-images-PRODUCTINDEX" class="upload-label">
                                    <span class="material-symbols-rounded">cloud_upload</span>
                                    <span class="upload-text">Click para seleccionar múltiples imágenes</span>
                                    <span class="upload-hint">o arrastra archivos aquí</span>
                                </label>
                            </div>
                            <div class="personalizacion-images-preview"></div>
                            <small class="form-help">💡 Puedes seleccionar varias imágenes a la vez (Ctrl+Click o Shift+Click)</small>
                        </div>
                    </div>
                </div>

                <!-- Modelo/Referencia Foto -->
                <div class="form-group full-width">
                    <label>Modelo / Referencia Foto</label>
                    <input type="text" name="productos[][modelo_foto]" placeholder="URL de la foto o descripción del modelo">
                </div>

                <!-- Precio y Subtotal -->
                <div class="form-group">
                    <label>Precio Unitario</label>
                    <input type="number" name="productos[][precio_unitario]" min="0" step="0.01" class="producto-precio" placeholder="0.00">
                </div>

                <div class="form-group">
                    <label>Subtotal</label>
                    <input type="text" class="producto-subtotal" readonly placeholder="$0.00">
                </div>

                <!-- Notas Adicionales -->
                <div class="form-group full-width">
                    <label>Notas Adicionales</label>
                    <textarea name="productos[][notas]" rows="2" placeholder="Cualquier observación adicional sobre este producto..."></textarea>
                </div>

                <!-- Imagen Principal del Producto -->
                <div class="form-group full-width">
                    <label>Imagen Principal del Producto</label>
                    <input type="file" name="productos[][imagen]" accept="image/*" class="producto-imagen-input">
                    <div class="image-preview-container" style="display:none;">
                        <img class="image-preview" src="" alt="Preview">
                        <button type="button" class="btn-remove-preview">×</button>
                    </div>
                    <small class="form-help">Formatos: JPG, PNG, GIF. Máximo 5MB</small>
                </div>

                <!-- Imágenes Adicionales (Bordados, Referencias, etc.) -->
                <div class="form-group full-width">
                    <label>Imágenes Adicionales (Bordados, Logos, Referencias)</label>
                    <input type="file" name="productos[][imagenes_adicionales][]" accept="image/*" multiple class="producto-imagenes-adicionales">
                    <div class="images-preview-grid"></div>
                    <small class="form-help">Puedes seleccionar múltiples imágenes. Máximo 5MB cada una.</small>
                </div>
            </div>
        </div>
    </div>
</template>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/asesores/pedidos.css') }}">
@endpush

@push('scripts')
<script src="{{ asset('js/asesores/pedidos.js') }}"></script>
<script>
// Manejo de Pestañas ERP
document.querySelectorAll('.erp-tab').forEach(tab => {
    tab.addEventListener('click', function() {
        document.querySelectorAll('.erp-tab').forEach(t => t.classList.remove('active'));
        this.classList.add('active');
        
        document.querySelectorAll('.erp-tab-content').forEach(content => {
            content.style.display = 'none';
        });
        
        const tabName = this.dataset.tab;
        document.querySelector(`[data-content="${tabName}"]`).style.display = 'block';
        
        if (tabName === 'resumen') {
            actualizarResumenCompleto();
        }
    });
});

// Manejo de Secciones Colapsables
document.addEventListener('click', function(e) {
    const header = e.target.closest('.erp-section-header');
    if (header) {
        const section = header.closest('.erp-section');
        section.classList.toggle('collapsed');
    }
});

// Actualizar resumen completo
function actualizarResumenCompleto() {
    const productos = document.querySelectorAll('.producto-item');
    let totalProductos = productos.length;
    let totalUnidades = 0;
    let valorTotal = 0;
    
    productos.forEach(producto => {
        const cantidad = parseInt(producto.querySelector('.producto-cantidad')?.value) || 0;
        const precio = parseFloat(producto.querySelector('.producto-precio')?.value) || 0;
        totalUnidades += cantidad;
        valorTotal += cantidad * precio;
    });
    
    document.getElementById('totalProductos').textContent = totalProductos;
    document.getElementById('cantidadTotal').textContent = totalUnidades;
    document.getElementById('valorTotal').textContent = '$' + valorTotal.toFixed(2);
}

// Agregar primer producto al cargar
document.addEventListener('DOMContentLoaded', function() {
    if (typeof agregarProducto === 'function') {
        agregarProducto();
    }
});
</script>
@endpush
