@extends('asesores.layout')

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
                <div class="step-label">BORDADO/ESTAMPADO</div>
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
            <div class="step-header">
                <h2>PASO 2: PRENDAS DEL PEDIDO</h2>
                <p>AGREGA LAS PRENDAS QUE TU CLIENTE QUIERE (OPCIONAL)</p>
            </div>
            
            <div style="background: #fff3cd; border: 2px solid #ffc107; border-radius: 8px; padding: 12px 15px; margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center;">
                <span style="color: #856404; font-size: 0.9rem;"><i class="fas fa-info-circle"></i> Esta sección es opcional</span>
                <button type="button" id="btnAplicaPaso2" onclick="toggleAplicaPaso(2, this)" style="background: #10b981; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 0.85rem; transition: all 0.3s;">APLICA</button>
            </div>

            <div style="background: linear-gradient(135deg, #0066cc, #0052a3); border: 2px solid #0052a3; border-radius: 8px; padding: 1rem 1.5rem; margin-bottom: 2rem; display: flex; align-items: center; justify-content: space-between; gap: 1rem; box-shadow: 0 4px 12px rgba(0, 102, 204, 0.3);">
                <label for="tipo_cotizacion" style="font-weight: 700; font-size: 0.9rem; color: white; white-space: nowrap; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-tag"></i> Elija el tipo de cotización
                </label>
                <select id="tipo_cotizacion" name="tipo_cotizacion" style="padding: 0.6rem 0.8rem; border: 2px solid white; border-radius: 6px; font-size: 0.85rem; cursor: pointer; background-color: white; text-align: center; color: #0066cc; font-weight: 600; min-width: 100px;">
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

            <!-- Botón flotante tipo WhatsApp - Solo en PASO 2 -->
            <div style="position: fixed; bottom: 30px; right: 30px; z-index: 1000;">
                <!-- Menú flotante -->
                <div id="menuFlotante" style="display: none; position: absolute; bottom: 70px; right: 0; background: white; border-radius: 12px; box-shadow: 0 5px 40px rgba(0,0,0,0.16); overflow: hidden; min-width: 200px;">
                    <button type="button" onclick="agregarProductoFriendly(); document.getElementById('menuFlotante').style.display='none'; document.getElementById('btnFlotante').style.transform='scale(1) rotate(0deg)'" style="width: 100%; padding: 12px 16px; border: none; background: white; cursor: pointer; text-align: left; font-size: 0.9rem; color: #333; display: flex; align-items: center; gap: 12px; transition: all 0.2s; border-bottom: 1px solid #f0f0f0;" onmouseover="this.style.background='#f5f5f5'" onmouseout="this.style.background='white'">
                        <i class="fas fa-plus" style="color: #10b981; font-size: 1.1rem;"></i>
                        <span>Agregar Prenda</span>
                    </button>
                    <button type="button" onclick="abrirModalEspecificaciones(); document.getElementById('menuFlotante').style.display='none'; document.getElementById('btnFlotante').style.transform='scale(1) rotate(0deg)'" style="width: 100%; padding: 12px 16px; border: none; background: white; cursor: pointer; text-align: left; font-size: 0.9rem; color: #333; display: flex; align-items: center; gap: 12px; transition: all 0.2s;" onmouseover="this.style.background='#f5f5f5'" onmouseout="this.style.background='white'">
                        <i class="fas fa-clipboard-check" style="color: #f59e0b; font-size: 1.1rem;"></i>
                        <span>Especificaciones</span>
                    </button>
                </div>
                
                <!-- Botón principal flotante -->
                <button type="button" id="btnFlotante" onclick="const menu = document.getElementById('menuFlotante'); menu.style.display = menu.style.display === 'none' ? 'block' : 'none'; this.style.transform = menu.style.display === 'block' ? 'scale(1) rotate(45deg)' : 'scale(1) rotate(0deg)'" style="width: 56px; height: 56px; border-radius: 50%; background: linear-gradient(135deg, #0066cc, #0052a3); color: white; border: none; cursor: pointer; font-size: 1.8rem; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(0, 102, 204, 0.4); transition: all 0.3s ease; position: relative;" onmouseover="this.style.boxShadow='0 6px 20px rgba(0, 102, 204, 0.5)'; this.style.transform='scale(1.1) ' + (document.getElementById('menuFlotante').style.display === 'block' ? 'rotate(45deg)' : 'rotate(0deg)')" onmouseout="this.style.boxShadow='0 4px 12px rgba(0, 102, 204, 0.4)'; this.style.transform='scale(1) ' + (document.getElementById('menuFlotante').style.display === 'block' ? 'rotate(45deg)' : 'rotate(0deg)')">
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

        <!-- PASO 3: BORDADO/ESTAMPADO -->
        <div class="form-step" data-step="3">
            <div class="step-header">
                <h2>PASO 3: BORDADO/ESTAMPADO</h2>
                <p>ESPECIFICA LOS DETALLES DE BORDADO Y ESTAMPADO (OPCIONAL)</p>
            </div>
            
            <div style="background: #fff3cd; border: 2px solid #ffc107; border-radius: 8px; padding: 12px 15px; margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center;">
                <span style="color: #856404; font-size: 0.9rem;"><i class="fas fa-info-circle"></i> Esta sección es opcional</span>
                <button type="button" id="btnAplicaPaso3" onclick="toggleAplicaPaso(3, this)" style="background: #10b981; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 0.85rem; transition: all 0.3s;">APLICA</button>
            </div>

            <div class="form-section">
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
                    <button type="button" class="btn-submit" onclick="guardarCotizacion()" style="background: #95a5a6;">
                        <i class="fas fa-save"></i> GUARDAR (BORRADOR)
                    </button>
                    <button type="button" class="btn-submit" onclick="enviarCotizacion()">
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
                <div class="form-row">
                    <div class="form-col full">
                        <label><i class="fas fa-list"></i> SELECCIONA O ESCRIBE EL TIPO *</label>
                        <div class="prenda-search-container">
                            <input type="text" name="productos_friendly[][nombre_producto]" class="prenda-search-input input-large" placeholder="BUSCA O ESCRIBE (CAMISA, CAMISETA, POLO...)" required onkeyup="buscarPrendas(this)" onchange="actualizarResumenFriendly()">
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
                </div>
            </div>

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
                            
                            <select class="talla-modo-select" onchange="actualizarModoTallas(this)" style="padding: 0.6rem 0.8rem; border: 2px solid #0066cc; border-radius: 6px; font-size: 0.85rem; cursor: pointer; background-color: white; color: #0066cc; font-weight: 600; max-width: 200px; display: none;">
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

            <div class="producto-section">
                <button type="button" class="section-title-btn" onclick="toggleSeccion(this)">
                    <div class="section-title">
                        <i class="fas fa-images"></i> FOTOS DE LA PRENDA (MÁX. 3)
                        <i class="fas fa-chevron-down" style="margin-left: auto; transition: transform 0.3s ease;"></i>
                    </div>
                </button>
                <div class="section-content">
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; margin-bottom: 1rem;">
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

                        <div>
                            <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: 600; margin-bottom: 0.5rem; color: #0066cc; font-size: 0.85rem;">
                                <i class="fas fa-fiber-manual-record"></i> TELA
                            </label>
                            <label style="display: block; min-height: 80px; padding: 0.75rem; border: 2px dashed #0066cc; border-radius: 6px; cursor: pointer; text-align: center; background: #f0f7ff;" ondrop="manejarDrop(event)" ondragover="event.preventDefault()" ondragleave="this.classList.remove('drag-over')">
                                <input type="file" name="productos_friendly[][imagen_tela]" class="input-file-single" accept="image/*" onchange="agregarFotoTela(this)" style="display: none;">
                                <div class="drop-zone-content" style="font-size: 0.75rem;">
                                    <i class="fas fa-cloud-upload-alt" style="font-size: 1rem; color: #0066cc;"></i>
                                    <p style="margin: 0.25rem 0; color: #0066cc; font-weight: 500;">ARRASTRA O CLIC</p>
                                </div>
                            </label>
                            <div class="foto-tela-preview" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.4rem; margin-top: 0.5rem;"></div>
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
<!-- Función para agregar tallas dinámicamente -->
<script>
const tallasLetras = ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL', 'XXXXL'];
const tallasDama = ['6', '8', '10', '12', '14', '16', '18', '20', '22', '24', '26'];
const tallasCaballero = ['28', '30', '32', '34', '36', '38', '40', '42', '44', '46', '48', '50'];

function actualizarSelectTallas(select) {
    const container = select.closest('.producto-section');
    const tallaBotones = container.querySelector('.talla-botones');
    const botonesDiv = container.querySelector('.talla-botones-container');
    const generoSelect = container.querySelector('.talla-genero-select');
    const modoSelect = container.querySelector('.talla-modo-select');
    const tallaRangoSelectors = container.querySelector('.talla-rango-selectors');
    const tipo = select.value;
    
    botonesDiv.innerHTML = '';
    
    if (tipo === 'letra') {
        // Mostrar selector de modo para letras
        if (generoSelect) generoSelect.style.display = 'none';
        modoSelect.style.display = 'block';
        modoSelect.value = '';
        tallaBotones.style.display = 'none';
        tallaRangoSelectors.style.display = 'none';
        
        // Agregar evento al selector de modo
        modoSelect.onchange = function() {
            actualizarModoLetras(container, this.value);
        };
    } else if (tipo === 'numero') {
        // Mostrar selector de género
        if (generoSelect) {
            generoSelect.style.display = 'block';
            generoSelect.value = '';
        }
        modoSelect.style.display = 'none';
        tallaBotones.style.display = 'none';
        tallaRangoSelectors.style.display = 'none';
        
        // Agregar evento al selector de género
        if (generoSelect) {
            generoSelect.onchange = function() {
                actualizarBotonesPorGenero(container, this.value);
            };
        }
    } else {
        if (generoSelect) generoSelect.style.display = 'none';
        modoSelect.style.display = 'none';
        tallaBotones.style.display = 'none';
        tallaRangoSelectors.style.display = 'none';
    }
}

function actualizarModoLetras(container, modo) {
    const tallaBotones = container.querySelector('.talla-botones');
    const tallaRangoSelectors = container.querySelector('.talla-rango-selectors');
    const botonesDiv = container.querySelector('.talla-botones-container');
    
    botonesDiv.innerHTML = '';
    
    if (modo === 'manual') {
        tallaBotones.style.display = 'block';
        tallaRangoSelectors.style.display = 'none';
        
        tallasLetras.forEach(talla => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.textContent = talla;
            btn.className = 'talla-btn';
            btn.dataset.talla = talla;
            btn.style.cssText = 'padding: 0.5rem 1rem; background: white; color: #0066cc; border: 2px solid #0066cc; border-radius: 20px; cursor: pointer; font-weight: 600; font-size: 0.85rem; transition: all 0.2s ease;';
            btn.onmouseover = function() { if (!this.classList.contains('activo')) this.style.background = '#e6f0ff'; };
            btn.onmouseout = function() { if (!this.classList.contains('activo')) this.style.background = 'white'; };
            btn.onclick = function(e) {
                e.preventDefault();
                this.classList.toggle('activo');
                if (this.classList.contains('activo')) {
                    this.style.background = '#0066cc';
                    this.style.color = 'white';
                } else {
                    this.style.background = 'white';
                    this.style.color = '#0066cc';
                }
            };
            botonesDiv.appendChild(btn);
        });
    } else if (modo === 'rango') {
        tallaBotones.style.display = 'none';
        tallaRangoSelectors.style.display = 'flex';
        actualizarSelectoresRangoLetras(container);
    } else {
        tallaBotones.style.display = 'none';
        tallaRangoSelectors.style.display = 'none';
    }
}

function actualizarSelectoresRangoLetras(container) {
    const desdeSelect = container.querySelector('.talla-desde');
    const hastaSelect = container.querySelector('.talla-hasta');
    
    desdeSelect.innerHTML = '<option value="">Desde</option>';
    hastaSelect.innerHTML = '<option value="">Hasta</option>';
    
    tallasLetras.forEach(talla => {
        const optDesde = document.createElement('option');
        optDesde.value = talla;
        optDesde.textContent = talla;
        desdeSelect.appendChild(optDesde);
        
        const optHasta = document.createElement('option');
        optHasta.value = talla;
        optHasta.textContent = talla;
        hastaSelect.appendChild(optHasta);
    });
}

function actualizarModoTallas(select) {
    const container = select.closest('.producto-section');
    const tallaBotones = container.querySelector('.talla-botones');
    const tallaRangoSelectors = container.querySelector('.talla-rango-selectors');
    const modo = select.value;
    
    if (modo === 'manual') {
        tallaBotones.style.display = 'block';
        tallaRangoSelectors.style.display = 'none';
    } else if (modo === 'rango') {
        tallaBotones.style.display = 'none';
        tallaRangoSelectors.style.display = 'flex';
        actualizarSelectoresRango(container);
    } else {
        tallaBotones.style.display = 'none';
        tallaRangoSelectors.style.display = 'none';
    }
}

function actualizarSelectoresRango(container) {
    const generoSelect = container.querySelector('.talla-genero-select');
    const desdeSelect = container.querySelector('.talla-desde');
    const hastaSelect = container.querySelector('.talla-hasta');
    const genero = generoSelect.value;
    
    let tallas = [];
    if (genero === 'dama') {
        tallas = tallasDama;
    } else if (genero === 'caballero') {
        tallas = tallasCaballero;
    }
    
    desdeSelect.innerHTML = '<option value="">Desde</option>';
    hastaSelect.innerHTML = '<option value="">Hasta</option>';
    
    tallas.forEach(talla => {
        const optDesde = document.createElement('option');
        optDesde.value = talla;
        optDesde.textContent = talla;
        desdeSelect.appendChild(optDesde);
        
        const optHasta = document.createElement('option');
        optHasta.value = talla;
        optHasta.textContent = talla;
        hastaSelect.appendChild(optHasta);
    });
}

function agregarTallasRango(btn) {
    const container = btn.closest('.producto-section');
    const desdeSelect = container.querySelector('.talla-desde');
    const hastaSelect = container.querySelector('.talla-hasta');
    const generoSelect = container.querySelector('.talla-genero-select');
    const tallasAgregadas = container.querySelector('.tallas-agregadas');
    const tallasSection = container.querySelector('.tallas-section');
    
    const desde = desdeSelect.value;
    const hasta = hastaSelect.value;
    
    if (!desde || !hasta) {
        alert('Por favor selecciona rango desde y hasta');
        return;
    }
    
    let tallas = [];
    let esLetra = false;
    
    // Determinar si es letra o número
    if (tallasLetras.includes(desde)) {
        tallas = tallasLetras;
        esLetra = true;
    } else {
        const genero = generoSelect.value;
        if (genero === 'dama') {
            tallas = tallasDama;
        } else if (genero === 'caballero') {
            tallas = tallasCaballero;
        }
    }
    
    const desdeIdx = tallas.indexOf(desde);
    const hastaIdx = tallas.indexOf(hasta);
    
    if (desdeIdx === -1 || hastaIdx === -1 || desdeIdx > hastaIdx) {
        alert('Rango inválido');
        return;
    }
    
    const tallasRango = tallas.slice(desdeIdx, hastaIdx + 1);
    
    tallasRango.forEach(talla => {
        // Solo guardar el número, sin el género
        const valor = talla;
        
        const existe = Array.from(tallasAgregadas.querySelectorAll('div')).some(tag =>
            tag.querySelector('span').textContent === valor
        );
        
        if (!existe) {
            const tag = document.createElement('div');
            tag.style.cssText = 'background: #0066cc; color: white; padding: 6px 12px; border-radius: 20px; display: inline-flex; align-items: center; gap: 8px; font-size: 0.85rem; font-weight: 600;';
            tag.innerHTML = `
                <span>${valor}</span>
                <button type="button" onclick="this.closest('div').remove(); actualizarTallasHidden(this.closest('.producto-section'))" style="background: none; border: none; color: white; cursor: pointer; font-size: 1rem; padding: 0; line-height: 1;">✕</button>
            `;
            tallasAgregadas.appendChild(tag);
        }
    });
    
    tallasSection.style.display = 'block';
    actualizarTallasHidden(container);
}

function actualizarBotonesPorGenero(container, genero) {
    const tallaBotones = container.querySelector('.talla-botones');
    const botonesDiv = container.querySelector('.talla-botones-container');
    const modoSelect = container.querySelector('.talla-modo-select');
    const tallaRangoSelectors = container.querySelector('.talla-rango-selectors');
    
    botonesDiv.innerHTML = '';
    
    // Mostrar selector de modo
    modoSelect.style.display = 'block';
    modoSelect.value = '';
    
    // Ocultar botones y rango hasta que se seleccione modo
    tallaBotones.style.display = 'none';
    tallaRangoSelectors.style.display = 'none';
    
    if (genero === 'dama') {
        tallaBotones.style.display = 'block';
        tallasDama.forEach(talla => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.textContent = talla;
            btn.className = 'talla-btn';
            btn.dataset.talla = talla; // Solo guardar el número, sin "DAMA"
            btn.style.cssText = 'padding: 0.5rem 1rem; background: white; color: #0066cc; border: 2px solid #0066cc; border-radius: 20px; cursor: pointer; font-weight: 600; font-size: 0.85rem; transition: all 0.2s ease;';
            btn.onmouseover = function() { if (!this.classList.contains('activo')) this.style.background = '#e6f0ff'; };
            btn.onmouseout = function() { if (!this.classList.contains('activo')) this.style.background = 'white'; };
            btn.onclick = function(e) {
                e.preventDefault();
                this.classList.toggle('activo');
                if (this.classList.contains('activo')) {
                    this.style.background = '#0066cc';
                    this.style.color = 'white';
                } else {
                    this.style.background = 'white';
                    this.style.color = '#0066cc';
                }
            };
            botonesDiv.appendChild(btn);
        });
    } else if (genero === 'caballero') {
        tallaBotones.style.display = 'block';
        tallasCaballero.forEach(talla => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.textContent = talla;
            btn.className = 'talla-btn';
            btn.dataset.talla = talla; // Solo guardar el número, sin "CABALLERO"
            btn.style.cssText = 'padding: 0.5rem 1rem; background: white; color: #0066cc; border: 2px solid #0066cc; border-radius: 20px; cursor: pointer; font-weight: 600; font-size: 0.85rem; transition: all 0.2s ease;';
            btn.onmouseover = function() { if (!this.classList.contains('activo')) this.style.background = '#e6f0ff'; };
            btn.onmouseout = function() { if (!this.classList.contains('activo')) this.style.background = 'white'; };
            btn.onclick = function(e) {
                e.preventDefault();
                this.classList.toggle('activo');
                if (this.classList.contains('activo')) {
                    this.style.background = '#0066cc';
                    this.style.color = 'white';
                } else {
                    this.style.background = 'white';
                    this.style.color = '#0066cc';
                }
            };
            botonesDiv.appendChild(btn);
        });
    } else {
        tallaBotones.style.display = 'none';
    }
}

function agregarTallasSeleccionadas(btn) {
    const container = btn.closest('.producto-section');
    const botonesActivos = container.querySelectorAll('.talla-btn.activo');
    const tallasAgregadas = container.querySelector('.tallas-agregadas');
    const tallasSection = container.querySelector('.tallas-section');
    
    if (botonesActivos.length === 0) {
        alert('Por favor selecciona al menos una talla');
        return;
    }
    
    botonesActivos.forEach(boton => {
        const talla = boton.dataset.talla;
        
        // Verificar si ya existe
        const existe = Array.from(tallasAgregadas.querySelectorAll('div')).some(tag =>
            tag.querySelector('span').textContent === talla
        );
        
        if (!existe) {
            // Crear etiqueta de talla
            const tag = document.createElement('div');
            tag.style.cssText = 'background: #0066cc; color: white; padding: 6px 12px; border-radius: 20px; display: inline-flex; align-items: center; gap: 8px; font-size: 0.85rem; font-weight: 600;';
            tag.innerHTML = `
                <span>${talla}</span>
                <button type="button" onclick="this.closest('div').remove(); actualizarTallasHidden(this.closest('.producto-section'))" style="background: none; border: none; color: white; cursor: pointer; font-size: 1rem; padding: 0; line-height: 1;">✕</button>
            `;
            
            tallasAgregadas.appendChild(tag);
        }
    });
    
    // Mostrar la sección de tallas seleccionadas
    tallasSection.style.display = 'block';
    
    // Actualizar campo hidden
    actualizarTallasHidden(container);
    
    // Desmarcar todos los botones
    botonesActivos.forEach(boton => {
        boton.classList.remove('activo');
        boton.style.background = 'white';
        boton.style.color = '#0066cc';
    });
}

function actualizarTallasHidden(container) {
    // Validar que container existe
    if (!container) {
        console.warn('⚠️ Container no encontrado en actualizarTallasHidden');
        return;
    }
    
    const tallasAgregadas = container.querySelector('.tallas-agregadas');
    const tallasHidden = container.querySelector('.tallas-hidden');
    
    // Validar que los elementos existen
    if (!tallasAgregadas || !tallasHidden) {
        console.warn('⚠️ Elementos de tallas no encontrados');
        return;
    }
    
    const tallas = [];
    
    tallasAgregadas.querySelectorAll('div').forEach(tag => {
        const span = tag.querySelector('span');
        if (span) {
            tallas.push(span.textContent);
        }
    });
    
    tallasHidden.value = tallas.join(', ');
}
</script>

<!-- Módulos del sistema de cotizaciones -->
<script src="{{ asset('js/asesores/cotizaciones/rutas.js') }}"></script>
<script>
    // Asignar rutas después de cargar rutas.js
    window.routes.guardarCotizacion = '{{ route("asesores.cotizaciones.guardar") }}';
    window.routes.cotizacionesIndex = '{{ route("asesores.cotizaciones.index") }}';
</script>
<script src="{{ asset('js/asesores/cotizaciones/cotizaciones.js') }}"></script>
<script src="{{ asset('js/asesores/cotizaciones/productos.js') }}"></script>
<script src="{{ asset('js/asesores/cotizaciones/imagenes.js') }}"></script>
<script src="{{ asset('js/asesores/cotizaciones/especificaciones.js') }}"></script>
<script src="{{ asset('js/asesores/cotizaciones/guardado.js') }}"></script>
<script src="{{ asset('js/asesores/cotizaciones/cargar-borrador.js') }}"></script>
<script>
    // Cargar TODO el borrador (técnicas, observaciones, imágenes, etc.)
    @if(isset($esEdicion) && $esEdicion && isset($cotizacion))
    document.addEventListener('DOMContentLoaded', function() {
        const cotizacion = {!! json_encode($cotizacion) !!};
        console.log('📂 Cargando borrador completo:', cotizacion);
        
        // Cargar técnicas
        if (cotizacion.tecnicas && Array.isArray(cotizacion.tecnicas)) {
            console.log('🔧 Cargando técnicas:', cotizacion.tecnicas);
            cotizacion.tecnicas.forEach(tecnica => {
                const selector = document.getElementById('selector_tecnicas');
                if (selector) {
                    selector.value = tecnica;
                    agregarTecnica();
                }
            });
        }
        
        // Cargar observaciones técnicas
        if (cotizacion.observaciones_tecnicas) {
            const textareaObs = document.getElementById('observaciones_tecnicas');
            if (textareaObs) {
                textareaObs.value = cotizacion.observaciones_tecnicas;
                console.log('✅ Observaciones técnicas cargadas');
            }
        }
        
        // Cargar imágenes de bordado/estampado
        if (cotizacion.imagenes && Array.isArray(cotizacion.imagenes)) {
            console.log('📸 Cargando imágenes:', cotizacion.imagenes);
            const galeriaImagenes = document.getElementById('galeria_imagenes');
            if (galeriaImagenes) {
                cotizacion.imagenes.forEach(imagen => {
                    const div = document.createElement('div');
                    div.style.cssText = 'position: relative; width: 100px; height: 100px; border-radius: 6px; overflow: hidden; border: 1px solid #ddd;';
                    div.innerHTML = `
                        <img src="${imagen}" style="width: 100%; height: 100%; object-fit: cover;">
                        <button type="button" onclick="this.parentElement.remove()" style="position: absolute; top: 2px; right: 2px; background: #f44336; color: white; border: none; border-radius: 50%; width: 24px; height: 24px; cursor: pointer; font-size: 16px; padding: 0; line-height: 1;">✕</button>
                    `;
                    galeriaImagenes.appendChild(div);
                });
            }
        }
        
        // Cargar observaciones generales
        if (cotizacion.observaciones_generales && Array.isArray(cotizacion.observaciones_generales)) {
            console.log('📝 Cargando observaciones generales:', cotizacion.observaciones_generales);
            const contenedor = document.getElementById('observaciones_lista');
            if (contenedor) {
                cotizacion.observaciones_generales.forEach(obs => {
                    let texto = '';
                    let tipo = 'texto';
                    let valor = '';
                    
                    if (typeof obs === 'string') {
                        texto = obs;
                    } else if (typeof obs === 'object' && obs.texto) {
                        texto = obs.texto || '';
                        tipo = obs.tipo || 'texto';
                        valor = obs.valor || '';
                    }
                    
                    if (!texto.trim()) return;
                    
                    const fila = document.createElement('div');
                    fila.style.cssText = 'display: flex; gap: 10px; align-items: center; padding: 10px; background: white; border-radius: 6px; border: 1px solid #ddd;';
                    fila.innerHTML = `
                        <input type="text" name="observaciones_generales[]" class="input-large" value="${texto}" style="flex: 1; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem;">
                        <div style="display: flex; gap: 5px; align-items: center; flex-shrink: 0;">
                            <div class="obs-checkbox-mode" style="display: flex; align-items: center; gap: 5px; ${tipo === 'checkbox' ? '' : 'display: none;'}">
                                <input type="checkbox" name="observaciones_check[]" style="width: 20px; height: 20px; cursor: pointer;" ${tipo === 'checkbox' ? 'checked' : ''}>
                            </div>
                            <div class="obs-text-mode" style="display: ${tipo === 'texto' ? 'block' : 'none'}; flex: 1;">
                                <input type="text" name="observaciones_valor[]" placeholder="Valor..." value="${valor}" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem;">
                            </div>
                            <button type="button" class="obs-toggle-btn" style="background: ${tipo === 'checkbox' ? '#3498db' : '#ff9800'}; color: white; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer; font-size: 0.8rem; font-weight: bold; flex-shrink: 0;">✓/✎</button>
                        </div>
                        <button type="button" onclick="this.closest('div').remove()" style="background: #f44336; color: white; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer; font-size: 1rem; flex-shrink: 0;">✕</button>
                    `;
                    contenedor.appendChild(fila);
                    
                    const toggleBtn = fila.querySelector('.obs-toggle-btn');
                    const checkboxMode = fila.querySelector('.obs-checkbox-mode');
                    const textMode = fila.querySelector('.obs-text-mode');
                    toggleBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        if (checkboxMode.style.display === 'none') {
                            checkboxMode.style.display = 'flex';
                            textMode.style.display = 'none';
                            toggleBtn.style.background = '#3498db';
                        } else {
                            checkboxMode.style.display = 'none';
                            textMode.style.display = 'block';
                            toggleBtn.style.background = '#ff9800';
                        }
                    });
                });
            }
        }
        
        console.log('✅ Borrador cargado completamente');
        actualizarResumenFriendly();
    });
    @endif
</script>
@endpush

@endsection
