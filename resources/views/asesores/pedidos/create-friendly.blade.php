@extends('layouts.asesores')

@section('title', 'Cotizaciones')
@section('page-title', 'Cotizaciones')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/asesores/create-friendly-refactored.css') }}">
<link rel="stylesheet" href="{{ asset('css/asesores/create-friendly.css') }}">
@endpush

@section('content')

<div class="friendly-form-fullscreen">
    <!-- TÍTULO PRINCIPAL -->
    <div style="text-align: center; margin-bottom: 10px; padding: 8px 0; border-bottom: 2px solid #3498db;">
        <h1 style="margin: 0; font-size: 1.2rem; color: #333; font-weight: bold;">COTIZACIONES</h1>
        <p style="margin: 4px 0 0 0; color: #666; font-size: 0.8rem;">Crea una nueva cotización para tu cliente</p>
    </div>

    <div class="stepper-container">
        <div class="stepper">
            <div class="step active" data-step="1" onclick="irAlPaso(1)" style="cursor: pointer;">
                <div class="step-number">1</div>
                <div class="step-label">CLIENTE</div>
            </div>
            <div class="step-line"></div>
            <div class="step" data-step="2" onclick="irAlPaso(2)" style="cursor: pointer;">
                <div class="step-number">2</div>
                <div class="step-label">PRENDAS</div>
            </div>
            <div class="step-line"></div>
            <div class="step" data-step="3" onclick="irAlPaso(3)" style="cursor: pointer;">
                <div class="step-number">3</div>
                <div class="step-label">LOGO</div>
            </div>
            <div class="step-line"></div>
            <div class="step" data-step="4" onclick="irAlPaso(4)" style="cursor: pointer;">
                <div class="step-number">4</div>
                <div class="step-label">REVISAR</div>
            </div>
        </div>
    </div>

    <form id="formCrearPedidoFriendly" class="friendly-form">
        @csrf

        <!-- Campo oculto para cotizacion_id (si es actualización) -->
        @if(isset($cotizacion))
            <input type="hidden" name="cotizacion_id" value="{{ $cotizacion->id }}">
        @endif

        <!-- PASO 1 -->
        <div class="form-step active" data-step="1">
            <div class="step-header">
                <h2>PASO 1: INFORMACIÓN DEL CLIENTE</h2>
                <p>CUÉNTANOS QUIÉN ES TU CLIENTE</p>
            </div>

            <div style="background: #f0f7ff; border-left: 4px solid #3498db; padding: 15px; margin-bottom: 20px; border-radius: 4px;">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                    <div>
                        <p style="margin: 0; font-size: 0.9rem; color: #666;">
                            <strong>{{ Auth::user()->genero === 'F' ? 'ASESORA COMERCIAL' : 'ASESOR COMERCIAL' }}:</strong>
                            {{ Auth::user()->name }}
                        </p>
                    </div>
                    <div>
                        <p style="margin: 0; font-size: 0.9rem; color: #666;">
                            <strong>FECHA:</strong>
                            <span id="fechaActual"></span>
                        </p>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <div class="form-group-large">
                    <label for="cliente"><i class="fas fa-user"></i> NOMBRE DEL CLIENTE *</label>
                    <input type="text" id="cliente" name="cliente" class="input-large" placeholder="EJ: JUAN GARCÍA, EMPRESA ABC..." value="{{ isset($esEdicion) && $esEdicion && isset($cotizacion) ? $cotizacion->cliente : '' }}" required>
                    <small class="help-text">EL NOMBRE DE TU CLIENTE O EMPRESA</small>
                </div>
            </div>

            <div class="form-actions">
                <button type="button" class="btn-next" onclick="irAlPaso(2)">
                    SIGUIENTE <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </div>

        <!-- PASO 2 -->
        <div class="form-step" data-step="2">
            <div class="step-header" style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h2>PASO 2: PRENDAS DEL PEDIDO</h2>
                    <p>AGREGA LAS PRENDAS QUE TU CLIENTE QUIERE (OPCIONAL)</p>
                </div>
            </div>

            <div style="background: linear-gradient(135deg, #0066cc, #0052a3); border: 2px solid #0052a3; border-radius: 8px; padding: 1rem 1.5rem; margin-bottom: 2rem; display: flex; align-items: center; justify-content: space-between; gap: 1rem; box-shadow: 0 4px 12px rgba(0, 102, 204, 0.3);">
                <label for="tipo_venta" style="font-weight: 700; font-size: 0.9rem; color: white; white-space: nowrap; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-tag"></i> Elija el tipo de cotización
                </label>
                <select id="tipo_venta" name="tipo_venta" style="padding: 0.6rem 0.8rem; border: 2px solid white; border-radius: 6px; font-size: 0.85rem; cursor: pointer; background-color: white; text-align: center; color: #0066cc; font-weight: 600; min-width: 100px;">
                    <option value="">Selecciona</option>
                    <option value="M">M</option>
                    <option value="D">D</option>
                    <option value="X">X</option>
                </select>
            </div>

            <div class="form-section">
                <div class="productos-container" id="productosContainer">
                    @if(isset($esEdicion) && $esEdicion && isset($cotizacion) && $cotizacion->productos)
                        <!-- Cargar productos guardados -->
                        <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                const productos = {!! json_encode($cotizacion->productos) !!};
                                console.log('📦 Productos a cargar:', productos);
                                
                                productos.forEach((producto, idx) => {
                                    agregarProductoFriendly();
                                    
                                    // Esperar a que se cree el elemento
                                    setTimeout(() => {
                                        const ultimoProducto = document.querySelectorAll('.producto-card')[document.querySelectorAll('.producto-card').length - 1];
                                        
                                        if (ultimoProducto) {
                                            // Nombre
                                            const inputNombre = ultimoProducto.querySelector('input[name*="nombre_producto"]');
                                            if (inputNombre) inputNombre.value = producto.nombre_producto || '';
                                            
                                            // Descripción
                                            const textareaDesc = ultimoProducto.querySelector('textarea[name*="descripcion"]');
                                            if (textareaDesc) textareaDesc.value = producto.descripcion || '';
                                            
                                            // Tallas
                                            if (producto.tallas && Array.isArray(producto.tallas)) {
                                                producto.tallas.forEach(talla => {
                                                    const tallaBtn = ultimoProducto.querySelector(`.talla-btn[data-talla="${talla}"]`);
                                                    if (tallaBtn) tallaBtn.click();
                                                });
                                            }
                                            
                                            console.log('✅ Producto cargado:', producto.nombre_producto);
                                        }
                                    }, 500);
                                });
                            });
                        </script>
                    @endif
                </div>
            </div>

            <!-- Botón flotante para agregar prenda -->
            <div style="position: fixed; bottom: 30px; right: 30px; z-index: 1000;">
                <!-- Menú flotante -->
                <div id="menuFlotante" style="display: none; position: absolute; bottom: 70px; right: 0; background: white; border-radius: 12px; box-shadow: 0 5px 40px rgba(0,0,0,0.16); overflow: hidden; min-width: 200px;">
                    <button type="button" onclick="agregarProductoFriendly(); document.getElementById('menuFlotante').style.display='none'; document.getElementById('btnFlotante').style.transform='scale(1) rotate(0deg)'" style="width: 100%; padding: 12px 16px; border: none; background: white; cursor: pointer; text-align: left; font-size: 0.9rem; color: #333; display: flex; align-items: center; gap: 12px; transition: all 0.2s; border-bottom: 1px solid #f0f0f0;" onmouseover="this.style.background='#f5f5f5'" onmouseout="this.style.background='white'">
                        <i class="fas fa-plus" style="color: #1e40af; font-size: 1.1rem;"></i>
                        <span>Agregar Prenda</span>
                    </button>
                    <button type="button" onclick="abrirModalEspecificaciones(); document.getElementById('menuFlotante').style.display='none'; document.getElementById('btnFlotante').style.transform='scale(1) rotate(0deg)'" style="width: 100%; padding: 12px 16px; border: none; background: white; cursor: pointer; text-align: left; font-size: 0.9rem; color: #333; display: flex; align-items: center; gap: 12px; transition: all 0.2s;" onmouseover="this.style.background='#f5f5f5'" onmouseout="this.style.background='white'">
                        <i class="fas fa-sliders-h" style="color: #ff9800; font-size: 1.1rem;"></i>
                        <span>Especificaciones</span>
                    </button>
                </div>
                
                <!-- Botón principal flotante -->
                <button type="button" id="btnFlotante" onclick="const menu = document.getElementById('menuFlotante'); menu.style.display = menu.style.display === 'none' ? 'block' : 'none'; this.style.transform = menu.style.display === 'block' ? 'scale(1) rotate(45deg)' : 'scale(1) rotate(0deg)'" style="width: 56px; height: 56px; border-radius: 50%; background: linear-gradient(135deg, #1e40af, #0ea5e9); color: white; border: none; cursor: pointer; font-size: 1.8rem; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(30, 64, 175, 0.4); transition: all 0.3s ease; position: relative;" onmouseover="this.style.boxShadow='0 6px 20px rgba(30, 64, 175, 0.5)'; this.style.transform='scale(1.1) ' + (document.getElementById('menuFlotante').style.display === 'block' ? 'rotate(45deg)' : 'rotate(0deg)')" onmouseout="this.style.boxShadow='0 4px 12px rgba(30, 64, 175, 0.4)'; this.style.transform='scale(1) ' + (document.getElementById('menuFlotante').style.display === 'block' ? 'rotate(45deg)' : 'rotate(0deg)')">
                    <i class="fas fa-plus"></i>
                </button>
            </div>

            <div class="form-actions">
                <button type="button" class="btn-prev" onclick="irAlPaso(1)">
                    <i class="fas fa-arrow-left"></i> ANTERIOR
                </button>
                <button type="button" class="btn-next" onclick="irAlPaso(3)">
                    SIGUIENTE <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </div>

        <!-- PASO 3: LOGO -->
        <div class="form-step" data-step="3">
            <div class="step-header">
                <h2>PASO 3: LOGO</h2>
                <p>ESPECIFICA LOS DETALLES DE BORDADO Y ESTAMPADO (OPCIONAL)</p>
            </div>

            <div class="form-section">
                <!-- DESCRIPCIÓN DEL LOGO/BORDADO -->
                <div class="form-group-large">
                    <label for="descripcion_logo"><i class="fas fa-pen"></i> DESCRIPCIÓN DEL LOGO/BORDADO</label>
                    <textarea id="descripcion_logo" name="descripcion_logo" class="input-large" rows="3" placeholder="Describe el logo, bordado o estampado que deseas..." style="width: 100%; padding: 12px; border: 2px solid #3498db; border-radius: 6px; font-size: 0.9rem; font-family: inherit;"></textarea>
                    <small class="help-text">Incluye detalles sobre colores, tamaño, posición, etc.</small>
                </div>

                <!-- IMÁGENES -->
                <div class="form-group-large">
                    <label for="imagenes_bordado"><i class="fas fa-images"></i> IMÁGENES (MÁXIMO 5)</label>
                    <div id="drop_zone_imagenes" style="border: 2px dashed #3498db; border-radius: 8px; padding: 30px; text-align: center; background: #f0f7ff; cursor: pointer; margin-bottom: 10px;">
                        <i class="fas fa-cloud-upload-alt" style="font-size: 2.5rem; color: #3498db; margin-bottom: 10px; display: block;"></i>
                        <p style="margin: 10px 0; color: #3498db; font-weight: 600;">ARRASTRA IMÁGENES AQUÍ O HAZ CLIC</p>
                        <p style="margin: 5px 0; color: #666; font-size: 0.9rem;">Máximo 5 imágenes</p>
                        <input type="file" id="imagenes_bordado" name="imagenes_bordado[]" accept="image/*" multiple style="display: none;">
                    </div>
                    <div id="galeria_imagenes" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 10px; margin-top: 10px;"></div>
                </div>

                <!-- TÉCNICAS -->
                <div style="background: #f9f9f9; border: 2px solid #3498db; border-radius: 8px; padding: 15px; margin-bottom: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <label style="font-weight: bold; font-size: 1.1rem; margin: 0;">Técnicas disponibles</label>
                        <button type="button" onclick="agregarTecnica()" style="background: #3498db; color: white; border: none; border-radius: 50%; width: 36px; height: 36px; cursor: pointer; font-size: 1.5rem; font-weight: bold; display: flex; align-items: center; justify-content: center; line-height: 1;">+</button>
                    </div>
                    
                    <select id="selector_tecnicas" class="input-large" style="width: 100%; margin-bottom: 10px; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                        <option value="">-- SELECCIONA UNA TÉCNICA --</option>
                        <option value="BORDADO">BORDADO</option>
                        <option value="DTF">DTF</option>
                        <option value="ESTAMPADO">ESTAMPADO</option>
                        <option value="SUBLIMADO">SUBLIMADO</option>
                    </select>
                    
                    <div id="tecnicas_seleccionadas" style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 12px; min-height: 30px;"></div>
                    
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; font-size: 0.9rem;">Observaciones</label>
                    <textarea id="observaciones_tecnicas" name="observaciones_tecnicas" class="input-large" rows="2" placeholder="Observaciones..." style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem;"></textarea>
                </div>

                <!-- UBICACIÓN -->
                <div style="background: #f9f9f9; border: 2px solid #3498db; border-radius: 8px; padding: 15px; margin-bottom: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                        <label style="font-weight: bold; font-size: 1.1rem; margin: 0;">Ubicación</label>
                        <button type="button" onclick="agregarSeccion()" style="background: #3498db; color: white; border: none; border-radius: 50%; width: 36px; height: 36px; cursor: pointer; font-size: 1.5rem; font-weight: bold; display: flex; align-items: center; justify-content: center; line-height: 1;">+</button>
                    </div>
                    
                    <label for="seccion_prenda" style="display: block; margin-bottom: 8px; font-weight: 600; font-size: 0.9rem;">Selecciona la sección a agregar:</label>
                    <select id="seccion_prenda" class="input-large" style="width: 100%; margin-bottom: 12px; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                        <option value="">-- SELECCIONA UNA OPCIÓN --</option>
                        <option value="CAMISA">CAMISA</option>
                        <option value="JEAN_SUDADERA">JEAN/SUDADERA</option>
                        <option value="GORRAS">GORRAS</option>
                    </select>
                    
                    <div id="secciones_agregadas" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px;"></div>
                </div>

                <!-- OBSERVACIONES GENERALES -->
                <div style="background: #f9f9f9; border: 2px solid #3498db; border-radius: 8px; padding: 15px; margin-bottom: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                        <label style="font-weight: bold; font-size: 1.1rem; margin: 0;">Observaciones Generales</label>
                        <button type="button" onclick="agregarObservacion()" style="background: #3498db; color: white; border: none; border-radius: 50%; width: 36px; height: 36px; cursor: pointer; font-size: 1.5rem; font-weight: bold; display: flex; align-items: center; justify-content: center; line-height: 1;">+</button>
                    </div>
                    
                    <div id="observaciones_lista" style="display: flex; flex-direction: column; gap: 10px;"></div>
                </div>
            </div>

            <div class="form-actions">
                <button type="button" class="btn-prev" onclick="irAlPaso(2)">
                    <i class="fas fa-arrow-left"></i> ANTERIOR
                </button>
                <button type="button" class="btn-next" onclick="irAlPaso(4)">
                    REVISAR <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </div>

        <!-- PASO 4 -->
        <div class="form-step" data-step="4">
            <div class="step-header">
                <h2>PASO 4: REVISAR COTIZACIÓN</h2>
                <p>VERIFICA QUE TODO ESTÉ CORRECTO</p>
            </div>

            <div class="form-section">
                <div style="background: #f0f7ff; border-left: 4px solid #3498db; padding: 15px; border-radius: 4px; margin-bottom: 20px;">
                    <p style="margin: 0; color: #333;"><strong>✓ Resumen de tu cotización:</strong></p>
                    <ul style="margin: 10px 0 0 0; padding-left: 20px; color: #666;">
                        <li>Cliente: <strong id="resumenCliente">-</strong></li>
                        <li>Productos: <strong id="resumenProductos">0</strong></li>
                        <li>Asesora: <strong>{{ Auth::user()->name }}</strong></li>
                        <li>Fecha: <strong id="resumenFecha"></strong></li>
                    </ul>
                </div>
            </div>

            <div class="form-actions">
                <button type="button" class="btn-prev" onclick="irAlPaso(3)">
                    <i class="fas fa-arrow-left"></i> ANTERIOR
                </button>
                <div style="display: flex; gap: 10px;">
                    <button type="button" class="btn-submit" id="btnGuardarBorrador" onclick="guardarCotizacion()" style="background: #95a5a6;">
                        <i class="fas fa-save"></i> GUARDAR (BORRADOR)
                    </button>
                    <button type="button" class="btn-submit" id="btnEnviar" onclick="enviarCotizacion()">
                        <i class="fas fa-paper-plane"></i> ENVIAR
                    </button>
                </div>
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
                <button type="button" class="btn-toggle-product" onclick="toggleProductoBody(this)" title="Expandir/Contraer" style="font-size: 1.5rem; line-height: 1; font-weight: bold;">▼</button>
                <button type="button" class="btn-remove-product" onclick="eliminarProductoFriendly(this)" title="Eliminar prenda">&times;</button>
            </div>
        </div>
        <div class="producto-body" style="display: block;">
            <div class="producto-section">
                <div class="section-title"><i class="fas fa-shirt"></i> TIPO DE PRENDA</div>
                <div class="form-row tipo-prenda-row" style="display: flex; gap: 12px; align-items: flex-start;">
                    <div class="form-col full" style="flex: 1;">
                        <label><i class="fas fa-list"></i> SELECCIONA O ESCRIBE EL TIPO *</label>
                        <div class="prenda-search-container">
                            <input type="text" name="productos_friendly[][nombre_producto]" class="prenda-search-input input-large" placeholder="BUSCA O ESCRIBE (CAMISA, CAMISETA, POLO...)" required onkeyup="buscarPrendas(this); mostrarSelectorVariantes(this);" onchange="actualizarResumenFriendly(); mostrarSelectorVariantes(this);">
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
                        <label style="display: block; min-height: 80px; padding: 0.75rem; border: 2px dashed #0066cc; border-radius: 6px; cursor: pointer; text-align: center; background: #f0f7ff;" ondrop="manejarDrop(event)" ondragover="event.preventDefault()" ondragleave="this.classList.remove('drag-over')">
                            <input type="file" name="productos_friendly[][fotos][]" class="input-file-single" accept="image/*" multiple onchange="agregarFotos(this.files, this.closest('label').nextElementSibling)" style="display: none;">
                            <div class="drop-zone-content" style="font-size: 0.75rem;">
                                <i class="fas fa-cloud-upload-alt" style="font-size: 1rem; color: #0066cc;"></i>
                                <p style="margin: 0.25rem 0; color: #0066cc; font-weight: 500;">ARRASTRA O CLIC</p>
                            </div>
                        </label>
                        <div class="fotos-preview" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.4rem; margin-top: 0.5rem;"></div>
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
                        <small class="help-text">DESCRIBE LA PRENDA, DETALLES ESPECIALES, LOGO, BORDADO, ESTAMPADO, ETC.</small>
                    </div>
                </div>
            </div>

            <!-- SECCIÓN DE COLOR, TELA Y REFERENCIA (Tabla con imagen) -->
            <div class="producto-section">
                <div class="section-title"><i class="fas fa-palette"></i> COLOR, TELA Y REFERENCIA</div>
                <div class="form-row">
                    <div class="form-col full">
                        <table style="width: 100%; border-collapse: collapse; background: white;">
                            <thead>
                                <tr style="background-color: #f0f0f0; border-bottom: 2px solid #0066cc;">
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
                                        <div style="position: relative;">
                                            <input type="text" class="color-input" placeholder="Buscar o crear color..." style="width: 100%; padding: 8px; border: 1px solid #0066cc; border-radius: 4px; font-size: 0.9rem;" onkeyup="buscarColor(this)" onkeypress="if(event.key==='Enter') crearColorDesdeInput(this)">
                                            <input type="hidden" name="productos_friendly[][variantes][color_id]" class="color-id-input" value="">
                                            <div class="color-suggestions" style="position: absolute; background: white; border: 1px solid #ddd; border-radius: 4px; max-height: 150px; overflow-y: auto; z-index: 1000; min-width: 100%; display: none; margin-top: 2px; top: 100%;"></div>
                                        </div>
                                    </td>
                                    <td style="padding: 12px; border-right: 1px solid #ddd;">
                                        <div style="position: relative;">
                                            <input type="text" class="tela-input" placeholder="Buscar o crear tela..." style="width: 100%; padding: 8px; border: 1px solid #0066cc; border-radius: 4px; font-size: 0.9rem;" onkeyup="buscarTela(this)" onkeypress="if(event.key==='Enter') crearTelaDesdeInput(this)">
                                            <input type="hidden" name="productos_friendly[][variantes][tela_id]" class="tela-id-input" value="">
                                            <div class="tela-suggestions" style="position: absolute; background: white; border: 1px solid #ddd; border-radius: 4px; max-height: 150px; overflow-y: auto; z-index: 1000; min-width: 100%; display: none; margin-top: 2px; top: 100%;"></div>
                                        </div>
                                    </td>
                                    <td style="padding: 12px; border-right: 1px solid #ddd;">
                                        <input type="text" name="productos_friendly[][variantes][referencia]" class="referencia-input" placeholder="Ej: REF-NAP-001" style="width: 100%; padding: 8px; border: 1px solid #0066cc; border-radius: 4px; font-size: 0.9rem;">
                                    </td>
                                    <td style="padding: 12px; text-align: center;">
                                        <label style="display: block; min-height: 60px; padding: 0.5rem; border: 2px dashed #0066cc; border-radius: 6px; cursor: pointer; text-align: center; background: #f0f7ff;" ondrop="manejarDrop(event)" ondragover="event.preventDefault()" ondragleave="this.classList.remove('drag-over')">
                                            <input type="file" name="productos_friendly[][telas][]" class="input-file-tela" accept="image/*" multiple onchange="agregarFotoTela(this)" style="display: none;">
                                            <div class="drop-zone-content" style="font-size: 0.7rem;">
                                                <i class="fas fa-cloud-upload-alt" style="font-size: 0.9rem; color: #0066cc;"></i>
                                                <p style="margin: 0.25rem 0; color: #0066cc; font-weight: 500;">ARRASTRA O CLIC</p>
                                                <small style="color: #666;">(Máx. 3)</small>
                                            </div>
                                        </label>
                                        <div class="foto-tela-preview" style="display: grid; grid-template-columns: repeat(3, 50px); gap: 0.4rem; margin-top: 0.5rem; justify-content: center;"></div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- SECCIÓN DE VARIACIONES ESPECÍFICAS (Tabla HTML) -->
            <div class="producto-section">
                <div class="section-title"><i class="fas fa-sliders-h"></i> VARIACIONES ESPECÍFICAS</div>
                <div class="form-row">
                    <div class="form-col full">
                        <table style="width: 100%; border-collapse: collapse; background: white; margin: 0; border: 1px solid #ddd; border-radius: 4px; overflow: hidden;">
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
                                                <input type="text" class="manga-input" placeholder="Buscar tipo..." style="width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem; box-sizing: border-box; transition: border-color 0.2s; opacity: 0.5; pointer-events: none;" onkeyup="buscarManga(this)" onkeypress="if(event.key==='Enter') crearMangaDesdeInput(this)" disabled>
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
                                            <select name="productos_friendly[][variantes][tipo_broche_id]" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem; background-color: white; cursor: pointer; transition: border-color 0.2s;">
                                                <option value="">Seleccionar...</option>
                                                <option value="1">Broche</option>
                                                <option value="2">Botón</option>
                                            </select>
                                            <input type="text" name="productos_friendly[][variantes][obs_broche]" placeholder="Ej: Botones de madera..." style="flex: 1; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem; box-sizing: border-box; transition: border-color 0.2s;">
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
                    </div>
                </div>
            </div>

            <div class="producto-section">
                <div class="section-title"><i class="fas fa-ruler"></i> TALLAS A COTIZAR</div>
                <div class="form-row">
                    <div class="form-col full">
                        <!-- Fila 1: Selectores de tipo, género y modo -->
                        <div style="display: flex; gap: 0.75rem; align-items: center; margin-bottom: 1rem; flex-wrap: wrap;">
                            <select class="talla-tipo-select" onchange="actualizarSelectTallas(this)" style="padding: 0.6rem 0.8rem; border: 2px solid #0066cc; border-radius: 6px; font-size: 0.85rem; cursor: pointer; background-color: white; color: #0066cc; font-weight: 600; max-width: 300px;">
                                <option value="">Selecciona tipo de talla</option>
                                <option value="letra">LETRAS (XS, S, M, L, XL...)</option>
                                <option value="numero">NÚMEROS (DAMA/CABALLERO)</option>
                            </select>
                            
                            <select class="talla-genero-select" style="padding: 0.6rem 0.8rem; border: 2px solid #0066cc; border-radius: 6px; font-size: 0.85rem; cursor: pointer; background-color: white; color: #0066cc; font-weight: 600; max-width: 200px; display: none;">
                                <option value="">Selecciona género</option>
                                <option value="dama">DAMA</option>
                                <option value="caballero">CABALLERO</option>
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

<!-- MODAL: ESPECIFICACIONES DE LA ORDEN -->
<div id="modalEspecificaciones" class="modal-especificaciones" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 12px; padding: 2rem; max-width: 900px; width: 90%; max-height: 90vh; overflow-y: auto; box-shadow: 0 10px 40px rgba(0,0,0,0.3);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; border-bottom: 2px solid #ffc107; padding-bottom: 1rem;">
            <h3 style="margin: 0; color: #333; font-size: 1.3rem;"><i class="fas fa-clipboard-check"></i> ESPECIFICACIONES DE LA ORDEN</h3>
            <button type="button" onclick="cerrarModalEspecificaciones()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #999;">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <table class="tabla-control-compacta">
            <thead>
                <tr>
                    <th style="width: 30%; text-align: left;"></th>
                    <th style="width: 15%; text-align: center;">SELECCIONAR</th>
                    <th style="width: 55%; text-align: left;">OBSERVACIONES</th>
                </tr>
            </thead>
            <tbody>
                <!-- DISPONIBILIDAD -->
                <tr class="fila-grupo">
                    <td colspan="3" style="font-weight: 600; background: #ffc107; padding: 10px;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span>📦 DISPONIBILIDAD</span>
                            <button type="button" onclick="agregarFilaEspecificacion('disponibilidad')" style="background: #3498db; color: white; border: none; padding: 4px 8px; border-radius: 4px; cursor: pointer; font-size: 0.8rem;">+</button>
                        </div>
                    </td>
                </tr>
                <tbody id="tbody_disponibilidad">
                    <tr>
                        <td><label style="margin: 0; font-size: 0.8rem;">Bodega</label></td>
                        <td style="text-align: center;">
                            <input type="checkbox" class="checkbox-guardar" style="width: 20px; height: 20px; cursor: pointer; accent-color: #10b981;">
                        </td>
                        <td style="display: flex; gap: 5px;">
                            <input type="text" name="tabla_orden[bodega_obs]" class="input-compact" placeholder="Observaciones" style="flex: 1;">
                        </td>
                    </tr>
                    <tr>
                        <td><label style="margin: 0; font-size: 0.8rem;">Cúcuta</label></td>
                        <td style="text-align: center;">
                            <input type="checkbox" class="checkbox-guardar" style="width: 20px; height: 20px; cursor: pointer; accent-color: #10b981;">
                        </td>
                        <td style="display: flex; gap: 5px;">
                            <input type="text" name="tabla_orden[cucuta_obs]" class="input-compact" placeholder="Observaciones" style="flex: 1;">
                        </td>
                    </tr>
                    <tr>
                        <td><label style="margin: 0; font-size: 0.8rem;">Lafayette</label></td>
                        <td style="text-align: center;">
                            <input type="checkbox" class="checkbox-guardar" style="width: 20px; height: 20px; cursor: pointer; accent-color: #10b981;">
                        </td>
                        <td style="display: flex; gap: 5px;">
                            <input type="text" name="tabla_orden[lafayette_obs]" class="input-compact" placeholder="Observaciones" style="flex: 1;">
                        </td>
                    </tr>
                    <tr>
                        <td><label style="margin: 0; font-size: 0.8rem;">Fábrica</label></td>
                        <td style="text-align: center;">
                            <input type="checkbox" class="checkbox-guardar" style="width: 20px; height: 20px; cursor: pointer; accent-color: #10b981;">
                        </td>
                        <td style="display: flex; gap: 5px;">
                            <input type="text" name="tabla_orden[fabrica_obs]" class="input-compact" placeholder="Observaciones" style="flex: 1;">
                        </td>
                    </tr>
                </tbody>

                <!-- PAGO -->
                <tr class="fila-grupo">
                    <td colspan="3" style="font-weight: 600; background: #ffc107; padding: 10px;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span>💳 FORMA DE PAGO</span>
                            <button type="button" onclick="agregarFilaEspecificacion('pago')" style="background: #3498db; color: white; border: none; padding: 4px 8px; border-radius: 4px; cursor: pointer; font-size: 0.8rem;">+</button>
                        </div>
                    </td>
                </tr>
                <tbody id="tbody_pago">
                    <tr>
                        <td><label style="margin: 0; font-size: 0.8rem;">Contado</label></td>
                        <td style="text-align: center;">
                            <input type="checkbox" class="checkbox-guardar" style="width: 20px; height: 20px; cursor: pointer; accent-color: #10b981;">
                        </td>
                        <td style="display: flex; gap: 5px;">
                            <input type="text" name="tabla_orden[pago_contado_obs]" class="input-compact" placeholder="Observaciones" style="flex: 1;">
                        </td>
                    </tr>
                    <tr>
                        <td><label style="margin: 0; font-size: 0.8rem;">Crédito</label></td>
                        <td style="text-align: center;">
                            <input type="checkbox" class="checkbox-guardar" style="width: 20px; height: 20px; cursor: pointer; accent-color: #10b981;">
                        </td>
                        <td style="display: flex; gap: 5px;">
                            <input type="text" name="tabla_orden[pago_credito_obs]" class="input-compact" placeholder="Observaciones" style="flex: 1;">
                        </td>
                    </tr>
                </tbody>

                <!-- RÉGIMEN -->
                <tr class="fila-grupo">
                    <td colspan="3" style="font-weight: 600; background: #ffc107; padding: 10px;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span>🏛️ RÉGIMEN</span>
                            <button type="button" onclick="agregarFilaEspecificacion('regimen')" style="background: #3498db; color: white; border: none; padding: 4px 8px; border-radius: 4px; cursor: pointer; font-size: 0.8rem;">+</button>
                        </div>
                    </td>
                </tr>
                <tbody id="tbody_regimen">
                    <tr>
                        <td><label style="margin: 0; font-size: 0.8rem;">Común</label></td>
                        <td style="text-align: center;">
                            <input type="checkbox" class="checkbox-guardar" style="width: 20px; height: 20px; cursor: pointer; accent-color: #10b981;">
                        </td>
                        <td style="display: flex; gap: 5px;">
                            <input type="text" name="tabla_orden[regimen_comun_obs]" class="input-compact" placeholder="Observaciones" style="flex: 1;">
                        </td>
                    </tr>
                    <tr>
                        <td><label style="margin: 0; font-size: 0.8rem;">Simplificado</label></td>
                        <td style="text-align: center;">
                            <input type="checkbox" class="checkbox-guardar" style="width: 20px; height: 20px; cursor: pointer; accent-color: #10b981;">
                        </td>
                        <td style="display: flex; gap: 5px;">
                            <input type="text" name="tabla_orden[regimen_simp_obs]" class="input-compact" placeholder="Observaciones" style="flex: 1;">
                        </td>
                    </tr>
                </tbody>

                <!-- SE HA VENDIDO -->
                <tr class="fila-grupo">
                    <td colspan="3" style="font-weight: 600; background: #ffc107; padding: 10px;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span>📊 SE HA VENDIDO</span>
                            <button type="button" onclick="agregarFilaEspecificacion('vendido')" style="background: #3498db; color: white; border: none; padding: 4px 8px; border-radius: 4px; cursor: pointer; font-size: 0.8rem;">+</button>
                        </div>
                    </td>
                </tr>
                <tbody id="tbody_vendido">
                    <tr>
                        <td><input type="text" name="tabla_orden[vendido_item]" class="input-compact" placeholder="Escribe aquí" style="width: 100%;"></td>
                        <td style="text-align: center;">
                            <input type="checkbox" class="checkbox-guardar" style="width: 20px; height: 20px; cursor: pointer; accent-color: #10b981;">
                        </td>
                        <td style="display: flex; gap: 5px;">
                            <input type="text" name="tabla_orden[vendido_obs]" class="input-compact" placeholder="Observaciones" style="flex: 1;">
                        </td>
                    </tr>
                </tbody>

                <!-- ÚLTIMA VENTA -->
                <tr class="fila-grupo">
                    <td colspan="3" style="font-weight: 600; background: #ffc107; padding: 10px;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span>💰 ÚLTIMA VENTA</span>
                            <button type="button" onclick="agregarFilaEspecificacion('ultima_venta')" style="background: #3498db; color: white; border: none; padding: 4px 8px; border-radius: 4px; cursor: pointer; font-size: 0.8rem;">+</button>
                        </div>
                    </td>
                </tr>
                <tbody id="tbody_ultima_venta">
                    <tr>
                        <td><input type="text" name="tabla_orden[ultima_venta_item]" class="input-compact" placeholder="Escribe aquí" style="width: 100%;"></td>
                        <td style="text-align: center;">
                            <input type="checkbox" class="checkbox-guardar" style="width: 20px; height: 20px; cursor: pointer; accent-color: #10b981;">
                        </td>
                        <td style="display: flex; gap: 5px;">
                            <input type="text" name="tabla_orden[ultima_venta_obs]" class="input-compact" placeholder="Observaciones" style="flex: 1;">
                        </td>
                    </tr>
                </tbody>

                <!-- FLETE DE ENVÍO -->
                <tr class="fila-grupo">
                    <td colspan="3" style="font-weight: 600; background: #ffc107; padding: 10px;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span>🚚 FLETE DE ENVÍO</span>
                            <button type="button" onclick="agregarFilaEspecificacion('flete')" style="background: #3498db; color: white; border: none; padding: 4px 8px; border-radius: 4px; cursor: pointer; font-size: 0.8rem;">+</button>
                        </div>
                    </td>
                </tr>
                <tbody id="tbody_flete">
                    <tr>
                        <td><input type="text" name="tabla_orden[flete_item]" class="input-compact" placeholder="Escribe aquí" style="width: 100%;"></td>
                        <td style="text-align: center;">
                            <input type="checkbox" class="checkbox-guardar" style="width: 20px; height: 20px; cursor: pointer; accent-color: #10b981;">
                        </td>
                        <td style="display: flex; gap: 5px;">
                            <input type="text" name="tabla_orden[flete_obs]" class="input-compact" placeholder="Observaciones" style="flex: 1;">
                        </td>
                    </tr>
                </tbody>
            </tbody>
        </table>

        <!-- Footer -->
        <div style="margin-top: 1.5rem; padding-top: 1rem; border-top: 2px solid #ffc107; display: flex; gap: 1rem; justify-content: flex-end;">
            <button type="button" onclick="cerrarModalEspecificaciones()" style="padding: 0.6rem 1.5rem; background: #f0f0f0; border: 1px solid #ddd; border-radius: 6px; cursor: pointer; font-weight: 600; color: #333;">
                CANCELAR
            </button>
            <button type="button" onclick="guardarEspecificaciones()" style="padding: 0.6rem 1.5rem; background: #0066cc; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; color: white;">
                GUARDAR
            </button>
        </div>
    </div>
</div>

@push('scripts')
<!-- Script de tallas -->
<script src="{{ asset('js/asesores/cotizaciones/tallas.js') }}"></script>

<!-- Módulos del sistema de cotizaciones -->
<script src="{{ asset('js/asesores/cotizaciones/rutas.js') }}"></script>
<script>
    // Asignar rutas después de cargar rutas.js
    window.tipoCotizacionGlobal = 'PB'; // Prenda-Bordado
    window.routes.guardarCotizacion = '{{ route("asesores.cotizaciones.guardar") }}';
    window.routes.cotizacionesIndex = '{{ route("asesores.cotizaciones.index") }}';}
</script>
<script src="{{ asset('js/asesores/cotizaciones/cotizaciones.js') }}"></script>
<script src="{{ asset('js/asesores/cotizaciones/productos.js') }}"></script>
<script src="{{ asset('js/asesores/cotizaciones/imagenes.js') }}"></script>
<script src="{{ asset('js/asesores/cotizaciones/especificaciones.js') }}"></script>
<script src="{{ asset('js/asesores/cotizaciones/guardado.js') }}"></script>
<script src="{{ asset('js/asesores/cotizaciones/cargar-borrador.js') }}"></script>
<!-- Script de carga de borrador inline (para pasar datos de Blade a JS) -->
<script>
    @if(isset($esEdicion) && $esEdicion && isset($cotizacion))
    document.addEventListener('DOMContentLoaded', function() {
        const cotizacion = {!! json_encode($cotizacion) !!};
        
        // Cargar datos desde el archivo externo
        if (window.cargarBorradorCompleto) {
            window.cargarBorradorCompleto(cotizacion);
        }
    });
    @endif
</script>

<!-- Script de Variantes de Prendas -->
<script src="{{ asset('js/asesores/variantes-prendas.js') }}"></script>

<!-- Script de Color, Tela y Referencia (Find/Create) -->
<script src="{{ asset('js/asesores/color-tela-referencia.js') }}"></script>

<!-- Integración de Variantes en Paso 2 -->
<script src="{{ asset('js/asesores/cotizaciones/integracion-variantes-inline.js') }}"></script>
@endpush

@endsection
