@extends('layouts.asesores')

@include('components.modal-imagen')

@section('extra_styles')
    <link rel="stylesheet" href="{{ asset('css/crear-pedido.css') }}">
    <link rel="stylesheet" href="{{ asset('css/crear-pedido-editable.css') }}">
@endsection

@section('content')
<!-- Loading Overlay de Página Completa -->
<div id="page-loading-overlay">
    <div class="loading-spinner"></div>
    <div class="loading-text">Cargando sistema de pedidos...</div>
    <div class="loading-subtext">Por favor espera mientras preparamos todo</div>
</div>

<!-- Header Full Width -->
<div class="page-header">
    <h1>📋 Crear Pedido de Producción (Editable)</h1>
    <p>Selecciona una cotización y personaliza tu pedido</p>
</div>

<div style="width: 100%; padding: 1.5rem;">
    <form id="formCrearPedidoEditable" class="space-y-6">
        @csrf

        <!-- PASO 1: Seleccionar Cotización -->
        <div class="form-section">
            @php
                $tipoInicial = $tipoInicial ?? 'cotizacion';
                $tituloTipo = match($tipoInicial) {
                    'cotizacion' => 'Pedido desde Cotización',
                    'nuevo' => 'Nuevo Pedido',
                    default => 'Tipo de Pedido'
                };
            @endphp
            
            <h2>
                <span>1</span> {{ $tituloTipo }}
            </h2>

            <!-- Radio Buttons para elegir tipo de pedido (OCULTOS si viene de ruta específica) -->
            @if(!isset($tipoInicial) || $tipoInicial === null)
            <div class="form-group" style="margin-bottom: 2rem;">
                <div style="display: flex; gap: 2rem; align-items: center;">
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-weight: 500;">
                        <input type="radio" name="tipo_pedido_editable" id="tipo_desde_cotizacion" value="cotizacion" checked style="width: 18px; height: 18px; cursor: pointer;">
                        <span>Desde Cotización</span>
                    </label>
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-weight: 500;">
                        <input type="radio" name="tipo_pedido_editable" id="tipo_nuevo_pedido" value="nuevo" style="width: 18px; height: 18px; cursor: pointer;">
                        <span>Nuevo Pedido</span>
                    </label>
                </div>
            </div>
            @else
            <!-- Input hidden para mantener el tipo seleccionado -->
            <input type="hidden" name="tipo_pedido_editable" id="tipo_pedido_hidden" value="{{ $tipoInicial }}">
            <div style="margin-bottom: 1rem; padding: 0.75rem; background: #f0f9ff; border-left: 3px solid #0066cc; border-radius: 4px;">
                <p style="margin: 0; color: #1e40af; font-size: 0.875rem;">
                    <strong>Tipo:</strong> {{ $tituloTipo }}
                </p>
            </div>
            @endif

            <!-- Contenedor para opciones dinámicas -->
            <div id="contenedor-opciones-pedido" style="margin-top: 1.5rem;">
                <!-- Buscador de Cotización (se muestra solo si está seleccionado "Desde Cotización") -->
                <div id="seccion-buscar-cotizacion" style="display: block;">
                    <div class="form-group">
                        <label for="cotizacion_search_editable" class="block text-sm font-medium text-gray-700 mb-2">
                            Cotización
                        </label>
                        <div style="position: relative;">
                            <input type="text" id="cotizacion_search_editable" placeholder="🔍 Buscar por número, cliente o asesora..." class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" autocomplete="off">
                            <input type="hidden" id="cotizacion_id_editable" name="cotizacion_id">
                            <input type="hidden" id="logoCotizacionId" name="logoCotizacionId">
                            <div id="cotizacion_dropdown_editable" style="position: absolute; top: 100%; left: 0; right: 0; background: white; border: 1px solid #d1d5db; border-top: none; border-radius: 0 0 8px 8px; max-height: 300px; overflow-y: auto; display: none; z-index: 1000; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
                            </div>
                        </div>
                        <div id="cotizacion_selected_editable" style="margin-top: 0.75rem; padding: 0.75rem; background: #f0f9ff; border-left: 3px solid #0066cc; border-radius: 4px; display: none;">
                            <div style="font-size: 0.875rem; color: #1e40af;"><strong>Seleccionada:</strong> <span id="cotizacion_selected_text_editable"></span></div>
                        </div>
                    </div>
                </div>

                <!-- Selector de Tipo de Pedido (se muestra solo si está seleccionado "Nuevo Pedido") -->
                <div id="seccion-tipo-pedido-nuevo" style="display: none;">
                    <div class="form-group">
                        <label for="tipo_pedido_nuevo" class="block text-sm font-medium text-gray-700 mb-2">
                            Tipo de Pedido
                        </label>
                        <!-- Loading State -->
                        <div id="tipo-pedido-loading" style="display: flex; align-items: center; gap: 0.75rem; padding: 1rem; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px;">
                            <div style="width: 20px; height: 20px; border: 3px solid #e5e7eb; border-top-color: #0066cc; border-radius: 50%; animation: spin 0.8s linear infinite;"></div>
                            <span style="color: #6b7280; font-size: 0.875rem;">Cargando opciones...</span>
                        </div>
                        <!-- Select (oculto inicialmente) -->
                        <select id="tipo_pedido_nuevo" name="tipo_pedido_nuevo" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" onchange="manejarCambiaTipoPedido()" style="display: none;" disabled>
                            <option value="">-- Selecciona un tipo de pedido --</option>
                            <option value="P">PRENDA</option>
                            <option value="R">REFLECTIVO</option>
                            <option value="B">BORDADO</option>
                            <option value="E">ESTAMPADO</option>
                            <option value="EPP">EPP</option>
                        </select>
                    </div>
                </div>
                
                <!-- CSS para la animación del spinner -->
                <style>
                    @keyframes spin {
                        0% { transform: rotate(0deg); }
                        100% { transform: rotate(360deg); }
                    }
                </style>

                <!-- SECCIÓN DE ÍTEMS DEL PEDIDO -->
                <div id="seccion-items-pedido" style="margin-top: 2rem; display: none;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                        <h3 style="font-size: 1.125rem; font-weight: 600; color: #1e40af;">Ítems del Pedido</h3>
                        <div style="display: flex; gap: 0.75rem;">
                            <button type="button" id="btn-agregar-item-cotizacion" style="display: none; padding: 0.5rem 1rem; background: #0066cc; color: white; border: none; border-radius: 6px; font-size: 0.875rem; font-weight: 500; cursor: pointer; align-items: center; gap: 0.5rem; transition: background 0.2s;" onmouseover="this.style.background='#0052a3'" onmouseout="this.style.background='#0066cc'">
                                <span style="font-size: 1.25rem;">+</span>
                                Agregar Prendas
                            </button>
                            <button type="button" id="btn-agregar-item-tipo" style="display: none; padding: 0.5rem 1rem; background: #059669; color: white; border: none; border-radius: 6px; font-size: 0.875rem; font-weight: 500; cursor: pointer; align-items: center; gap: 0.5rem; transition: background 0.2s;" onmouseover="this.style.background='#047857'" onmouseout="this.style.background='#059669'">
                                <span style="font-size: 1.25rem;">+</span>
                                Agregar Prenda
                            </button>
                        </div>
                    </div>

                    <!-- Lista de ítems -->
                    <div id="lista-items-pedido" style="display: flex; flex-direction: column; gap: 0.75rem;">
                        <!-- Los ítems se agregarán aquí dinámicamente -->
                    </div>

                    <!-- Mensaje cuando no hay ítems -->
                    <div id="mensaje-sin-items" style="padding: 2rem; text-align: center; background: #f9fafb; border: 2px dashed #d1d5db; border-radius: 8px; color: #6b7280;">
                        <p style="margin: 0; font-size: 0.875rem;">No hay ítems agregados. Usa los botones de arriba para agregar cotizaciones o tipos nuevos.</p>
                    </div>
                </div>
            </div>

        <!-- PASO 2: Información del Pedido -->
        <div class="form-section" id="seccion-info-prenda" style="display: none;">
            <h2>
                <span>2</span> Información del Pedido
            </h2>

            <div class="form-row">
                <!-- Campo Número de Cotización (solo se muestra si viene de cotización) -->
                <div id="campo-numero-cotizacion" class="form-group">
                    <label for="numero_cotizacion_editable">Número de Cotización</label>
                    <input type="text" id="numero_cotizacion_editable" name="numero_cotizacion" readonly>
                </div>

                <div class="form-group">
                    <label for="cliente_editable">Cliente</label>
                    <input type="text" id="cliente_editable" name="cliente" required>
                </div>

                <div class="form-group">
                    <label for="asesora_editable">Asesora</label>
                    <input type="text" id="asesora_editable" name="asesora" readonly>
                </div>

                <div class="form-group">
                    <label for="forma_de_pago_editable">Forma de Pago</label>
                    <input type="text" id="forma_de_pago_editable" name="forma_de_pago">
                </div>

                <div class="form-group">
                    <label for="numero_pedido_editable">Número de Pedido</label>
                    <input type="text" id="numero_pedido_editable" name="numero_pedido" readonly placeholder="Se asignará automáticamente" style="background-color: #f3f4f6; cursor: not-allowed;">
                </div>
            </div>
        </div>

        <!-- PASO 3: Prendas Editables -->
        <div class="form-section" id="seccion-prendas" style="display: none;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h2 style="margin: 0;">
                    <span>3</span> <span id="titulo-prendas-dinamico">Prendas Técnicas del Logo</span>
                </h2>
                <button type="button" 
                    id="btn-agregar-prenda-tecnica-logo"
                    onclick="abrirModalAgregarPrendaTecnicaLogo()"
                    style="display: none; background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%); color: white; border: none; padding: 10px 16px; border-radius: 6px; cursor: pointer; font-weight: 700; font-size: 0.95rem; transition: all 0.3s; box-shadow: 0 2px 4px rgba(30, 64, 175, 0.2);" 
                    onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 8px rgba(30, 64, 175, 0.3)'"
                    onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 4px rgba(30, 64, 175, 0.2)'">
                    ➕ Agregar Prenda Técnica
                </button>
            </div>

            <div id="prendas-container-editable">
                <div class="empty-state">
                    <p>Selecciona una cotización para ver las prendas</p>
                </div>
            </div>
        </div>

        <!-- PASO 6: Botones de Acción -->
        <div class="btn-actions">
            <button type="submit" id="btn-submit" class="btn btn-primary" style="display: none;">
                ✓ Crear Pedido
            </button>
            <a href="{{ route('asesores.pedidos-produccion.index') }}" class="btn btn-secondary">
                ✕ Cancelar
            </a>
        </div>
    </form>
</div>

@include('asesores.pedidos.modals.modal-seleccionar-prendas')

@include('asesores.pedidos.modals.modal-seleccionar-tallas')

@include('asesores.pedidos.modals.modal-agregar-prenda-nueva')

@include('asesores.pedidos.modals.modal-agregar-reflectivo')

@endsection

@push('scripts')
    <!-- IMPORTANTE: Cargar módulos PRIMERO -->
    <script src="{{ asset('js/modulos/crear-pedido/gestion-items-pedido.js') }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/modal-seleccion-prendas.js') }}"></script>
    
    <!-- Datos del servidor (transformados en el Controller) -->
    <script>
        window.cotizacionesData = @json($cotizacionesData ?? []);
        window.asesorActualNombre = '{{ Auth::user()->name ?? '' }}';
        
        // Mostrar la sección de ítems al cargar
        document.addEventListener('DOMContentLoaded', function() {
            const seccionItems = document.getElementById('seccion-items-pedido');
            if (seccionItems) {
                seccionItems.style.display = 'block';
            }
            console.log('✅ Sección de ítems mostrada');
            console.log('📋 Cotizaciones disponibles:', window.cotizacionesData.length);
        });
    </script>
    <!-- Cargar módulos de gestión de pedidos (DDD - Lógica en backend) -->
    <script src="{{ asset('js/modulos/crear-pedido/api-pedidos-editable.js') }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/image-storage-service.js') }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/gestion-items-pedido-refactorizado.js') }}"></script>

    <script>
        // Legacy: Mantener compatibilidad con funciones de galerías de imágenes
        // TODO: Refactorizar estas funciones a módulos separados
        
        // Funciones de manejo de imágenes delegadas a image-storage-service.js
        // Mantener compatibilidad con handlers de input
        function manejarImagenesTela(input) {
            window.imagenesTelaStorage.agregarImagen(input.files[0])
                .then(() => actualizarPreviewTela())
                .catch(err => alert(err.message));
            input.value = '';
        }
        
        function actualizarPreviewTela() {
            const preview = document.getElementById('nueva-prenda-tela-preview');
            const imagenes = window.imagenesTelaStorage.obtenerImagenes();
            preview.innerHTML = '';
            
            if (imagenes.length === 0) return;
            
            const img = document.createElement('img');
            img.src = imagenes[0].data;
            img.style.cssText = 'width: 60px; height: 60px; border-radius: 6px; object-fit: cover; border: 2px solid #0066cc; cursor: pointer;';
            img.onclick = () => mostrarGaleriaImagenes(imagenes, 0);
            preview.appendChild(img);
            
            if (imagenes.length > 1) {
                const badge = document.createElement('div');
                badge.style.cssText = 'padding: 0.25rem 0.5rem; background: #0066cc; color: white; border-radius: 50%; font-size: 0.75rem; font-weight: 700; display: flex; align-items: center; justify-content: center; min-width: 30px; height: 30px; cursor: pointer;';
                badge.textContent = '+' + (imagenes.length - 1);
                badge.onclick = () => mostrarGaleriaImagenes(imagenes, 0);
                preview.appendChild(badge);
            }
        }
        
        function manejarImagenesPrenda(input) {
            window.imagenesPrendaStorage.agregarImagen(input.files[0])
                .then(() => actualizarPreviewPrenda())
                .catch(err => alert(err.message));
            input.value = '';
        }
        
        function actualizarPreviewPrenda() {
            const preview = document.getElementById('nueva-prenda-foto-preview');
            const contador = document.getElementById('nueva-prenda-foto-contador');
            const btn = document.getElementById('nueva-prenda-foto-btn');
            const imagenes = window.imagenesPrendaStorage.obtenerImagenes();
            
            if (imagenes.length === 0) {
                preview.innerHTML = '<div style="text-align: center;"><div class="material-symbols-rounded" style="font-size: 2rem; color: #9ca3af; margin-bottom: 0.25rem;">add_photo_alternate</div><div style="font-size: 0.7rem; color: #9ca3af;">Click para agregar</div></div>';
                contador.textContent = '';
                btn.style.display = 'none';
                return;
            }
            
            preview.innerHTML = '';
            const img = document.createElement('img');
            img.src = imagenes[0].data;
            img.style.cssText = 'width: 100%; height: 100%; object-fit: cover; cursor: pointer;';
            img.onclick = () => mostrarGaleriaPrenda(imagenes, 0);
            preview.appendChild(img);
            
            contador.textContent = imagenes.length === 1 ? '1 foto' : imagenes.length + ' fotos';
            btn.style.display = imagenes.length < 3 ? 'block' : 'none';
        }
        
        function mostrarGaleriaPrenda(imagenes, indiceInicial = 0) {
            let indiceActual = indiceInicial;
            
            const modal = document.createElement('div');
            modal.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); display: flex; align-items: center; justify-content: center; z-index: 10000;';
            modal.onclick = function(e) {
                if (e.target === modal) {
                    modal.remove();
                }
            };
            
            const contenedor = document.createElement('div');
            contenedor.style.cssText = 'position: relative; max-width: 800px; width: 90%; max-height: 90vh;';
            
            // Imagen grande
            const imgContainer = document.createElement('div');
            imgContainer.style.cssText = 'position: relative; width: 100%; background: black; border-radius: 8px; overflow: hidden;';
            
            const img = document.createElement('img');
            img.src = imagenes[indiceActual].data;
            img.style.cssText = 'width: 100%; height: auto; max-height: 80vh; object-fit: contain;';
            imgContainer.appendChild(img);
            
            // Contador
            const contador = document.createElement('div');
            contador.style.cssText = 'position: absolute; top: 1rem; right: 1rem; background: #0066cc; color: white; padding: 0.5rem 1rem; border-radius: 6px; font-weight: 600; font-size: 0.875rem;';
            contador.textContent = (indiceActual + 1) + ' de ' + imagenes.length;
            imgContainer.appendChild(contador);
            
            // Botón cerrar
            const btnCerrar = document.createElement('button');
            btnCerrar.innerHTML = '<span class="material-symbols-rounded" style="font-size: 1.5rem;">close</span>';
            btnCerrar.style.cssText = 'position: absolute; top: 1rem; left: 1rem; background: #0066cc; color: white; border: none; border-radius: 6px; cursor: pointer; padding: 0.5rem; display: flex; align-items: center; justify-content: center; width: 40px; height: 40px; transition: background 0.2s;';
            btnCerrar.onmouseover = function() { btnCerrar.style.background = '#0052a3'; };
            btnCerrar.onmouseout = function() { btnCerrar.style.background = '#0066cc'; };
            btnCerrar.onclick = function() {
                modal.remove();
            };
            imgContainer.appendChild(btnCerrar);
            
            contenedor.appendChild(imgContainer);
            
            // Controles (flechas y eliminar)
            const controles = document.createElement('div');
            controles.style.cssText = 'display: flex; gap: 1rem; justify-content: center; align-items: center; margin-top: 1.5rem;';
            
            // Flecha izquierda
            const btnAnterior = document.createElement('button');
            btnAnterior.innerHTML = '<span class="material-symbols-rounded" style="font-size: 1.5rem;">arrow_back</span>';
            btnAnterior.style.cssText = 'background: #0066cc; color: white; border: none; border-radius: 6px; cursor: pointer; padding: 0.75rem; display: flex; align-items: center; justify-content: center; transition: background 0.2s; width: 50px; height: 50px;';
            btnAnterior.onmouseover = function() { btnAnterior.style.background = '#0052a3'; };
            btnAnterior.onmouseout = function() { btnAnterior.style.background = '#0066cc'; };
            btnAnterior.onclick = function() {
                indiceActual = (indiceActual - 1 + imagenes.length) % imagenes.length;
                img.src = imagenes[indiceActual].data;
                contador.textContent = (indiceActual + 1) + ' de ' + imagenes.length;
            };
            controles.appendChild(btnAnterior);
            
            // Botón eliminar
            const btnEliminar = document.createElement('button');
            btnEliminar.innerHTML = '<span class="material-symbols-rounded" style="font-size: 1.5rem;">delete</span>';
            btnEliminar.style.cssText = 'background: #ef4444; color: white; border: none; border-radius: 6px; cursor: pointer; padding: 0.75rem; display: flex; align-items: center; justify-content: center; transition: background 0.2s; width: 50px; height: 50px;';
            btnEliminar.onmouseover = function() { btnEliminar.style.background = '#dc2626'; };
            btnEliminar.onmouseout = function() { btnEliminar.style.background = '#ef4444'; };
            btnEliminar.onclick = function() {
                if (confirm('¿Eliminar esta imagen?')) {
                    window.imagenesPrendaStorage.splice(indiceActual, 1);
                    
                    if (window.imagenesPrendaStorage.length === 0) {
                        modal.remove();
                        actualizarPreviewPrenda();
                        return;
                    }
                    
                    if (indiceActual >= window.imagenesPrendaStorage.length) {
                        indiceActual = window.imagenesPrendaStorage.length - 1;
                    }
                    
                    img.src = window.imagenesPrendaStorage[indiceActual].data;
                    contador.textContent = (indiceActual + 1) + ' de ' + window.imagenesPrendaStorage.length;
                    
                    actualizarPreviewPrenda();
                }
            };
            controles.appendChild(btnEliminar);
            
            // Flecha derecha
            const btnSiguiente = document.createElement('button');
            btnSiguiente.innerHTML = '<span class="material-symbols-rounded" style="font-size: 1.5rem;">arrow_forward</span>';
            btnSiguiente.style.cssText = 'background: #0066cc; color: white; border: none; border-radius: 6px; cursor: pointer; padding: 0.75rem; display: flex; align-items: center; justify-content: center; transition: background 0.2s; width: 50px; height: 50px;';
            btnSiguiente.onmouseover = function() { btnSiguiente.style.background = '#0052a3'; };
            btnSiguiente.onmouseout = function() { btnSiguiente.style.background = '#0066cc'; };
            btnSiguiente.onclick = function() {
                indiceActual = (indiceActual + 1) % imagenes.length;
                img.src = imagenes[indiceActual].data;
                contador.textContent = (indiceActual + 1) + ' de ' + imagenes.length;
            };
            controles.appendChild(btnSiguiente);
            
            contenedor.appendChild(controles);
            modal.appendChild(contenedor);
            document.body.appendChild(modal);
            
            // Soporte para navegación con teclas
            const manejarTeclas = function(e) {
                if (e.key === 'ArrowLeft') {
                    btnAnterior.click();
                } else if (e.key === 'ArrowRight') {
                    btnSiguiente.click();
                } else if (e.key === 'Escape') {
                    modal.remove();
                    document.removeEventListener('keydown', manejarTeclas);
                }
            };
            document.addEventListener('keydown', manejarTeclas);
            
            modal.addEventListener('remove', function() {
                document.removeEventListener('keydown', manejarTeclas);
            });
        }
        
        function manejarImagenesReflectivo(input) {
            window.imagenesReflectivoStorage.agregarImagen(input.files[0])
                .then(() => actualizarPreviewReflectivo())
                .catch(err => alert(err.message));
            input.value = '';
        }
        
        function actualizarPreviewReflectivo() {
            const preview = document.getElementById('reflectivo-foto-preview');
            const contador = document.getElementById('reflectivo-foto-contador');
            const btn = document.getElementById('reflectivo-foto-btn');
            const imagenes = window.imagenesReflectivoStorage.obtenerImagenes();
            
            if (imagenes.length === 0) {
                preview.innerHTML = '<div style="text-align: center;"><div class="material-symbols-rounded" style="font-size: 2rem; color: #9ca3af; margin-bottom: 0.25rem;">add_photo_alternate</div><div style="font-size: 0.7rem; color: #9ca3af;">Click para agregar</div></div>';
                contador.textContent = '';
                btn.style.display = 'none';
                return;
            }
            
            preview.innerHTML = '';
            const img = document.createElement('img');
            img.src = imagenes[0].data;
            img.style.cssText = 'width: 100%; height: 100%; object-fit: cover; cursor: pointer;';
            img.onclick = () => mostrarGaleriaReflectivo(imagenes, 0);
            preview.appendChild(img);
            
            contador.textContent = imagenes.length === 1 ? '1 foto' : imagenes.length + ' fotos';
            btn.style.display = imagenes.length < 3 ? 'block' : 'none';
        }
        
        function mostrarGaleriaReflectivo(imagenes, indiceInicial = 0) {
            let indiceActual = indiceInicial;
            
            const modal = document.createElement('div');
            modal.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); display: flex; align-items: center; justify-content: center; z-index: 10000;';
            modal.onclick = function(e) {
                if (e.target === modal) {
                    modal.remove();
                }
            };
            
            const contenedor = document.createElement('div');
            contenedor.style.cssText = 'position: relative; max-width: 800px; width: 90%; max-height: 90vh;';
            
            // Imagen grande
            const imgContainer = document.createElement('div');
            imgContainer.style.cssText = 'position: relative; width: 100%; background: black; border-radius: 8px; overflow: hidden;';
            
            const img = document.createElement('img');
            img.src = imagenes[indiceActual].data;
            img.style.cssText = 'width: 100%; height: auto; max-height: 80vh; object-fit: contain;';
            imgContainer.appendChild(img);
            
            // Contador
            const contador = document.createElement('div');
            contador.style.cssText = 'position: absolute; top: 1rem; right: 1rem; background: #0066cc; color: white; padding: 0.5rem 1rem; border-radius: 6px; font-weight: 600; font-size: 0.875rem;';
            contador.textContent = (indiceActual + 1) + ' de ' + imagenes.length;
            imgContainer.appendChild(contador);
            
            // Botón cerrar
            const btnCerrar = document.createElement('button');
            btnCerrar.innerHTML = '<span class="material-symbols-rounded" style="font-size: 1.5rem;">close</span>';
            btnCerrar.style.cssText = 'position: absolute; top: 1rem; left: 1rem; background: #0066cc; color: white; border: none; border-radius: 6px; cursor: pointer; padding: 0.5rem; display: flex; align-items: center; justify-content: center; width: 40px; height: 40px; transition: background 0.2s;';
            btnCerrar.onmouseover = function() { btnCerrar.style.background = '#0052a3'; };
            btnCerrar.onmouseout = function() { btnCerrar.style.background = '#0066cc'; };
            btnCerrar.onclick = function() {
                modal.remove();
            };
            imgContainer.appendChild(btnCerrar);
            
            contenedor.appendChild(imgContainer);
            
            // Controles (flechas y eliminar)
            const controles = document.createElement('div');
            controles.style.cssText = 'display: flex; gap: 1rem; justify-content: center; align-items: center; margin-top: 1.5rem;';
            
            // Flecha izquierda
            const btnAnterior = document.createElement('button');
            btnAnterior.innerHTML = '<span class="material-symbols-rounded" style="font-size: 1.5rem;">arrow_back</span>';
            btnAnterior.style.cssText = 'background: #0066cc; color: white; border: none; border-radius: 6px; cursor: pointer; padding: 0.75rem; display: flex; align-items: center; justify-content: center; transition: background 0.2s; width: 50px; height: 50px;';
            btnAnterior.onmouseover = function() { btnAnterior.style.background = '#0052a3'; };
            btnAnterior.onmouseout = function() { btnAnterior.style.background = '#0066cc'; };
            btnAnterior.onclick = function() {
                indiceActual = (indiceActual - 1 + imagenes.length) % imagenes.length;
                img.src = imagenes[indiceActual].data;
                contador.textContent = (indiceActual + 1) + ' de ' + imagenes.length;
            };
            controles.appendChild(btnAnterior);
            
            // Botón eliminar
            const btnEliminar = document.createElement('button');
            btnEliminar.innerHTML = '<span class="material-symbols-rounded" style="font-size: 1.5rem;">delete</span>';
            btnEliminar.style.cssText = 'background: #ef4444; color: white; border: none; border-radius: 6px; cursor: pointer; padding: 0.75rem; display: flex; align-items: center; justify-content: center; transition: background 0.2s; width: 50px; height: 50px;';
            btnEliminar.onmouseover = function() { btnEliminar.style.background = '#dc2626'; };
            btnEliminar.onmouseout = function() { btnEliminar.style.background = '#ef4444'; };
            btnEliminar.onclick = function() {
                if (confirm('¿Eliminar esta imagen?')) {
                    window.imagenesReflectivoStorage.splice(indiceActual, 1);
                    
                    if (window.imagenesReflectivoStorage.length === 0) {
                        modal.remove();
                        actualizarPreviewReflectivo();
                        return;
                    }
                    
                    if (indiceActual >= window.imagenesReflectivoStorage.length) {
                        indiceActual = window.imagenesReflectivoStorage.length - 1;
                    }
                    
                    img.src = window.imagenesReflectivoStorage[indiceActual].data;
                    contador.textContent = (indiceActual + 1) + ' de ' + window.imagenesReflectivoStorage.length;
                    
                    actualizarPreviewReflectivo();
                }
            };
            controles.appendChild(btnEliminar);
            
            // Flecha derecha
            const btnSiguiente = document.createElement('button');
            btnSiguiente.innerHTML = '<span class="material-symbols-rounded" style="font-size: 1.5rem;">arrow_forward</span>';
            btnSiguiente.style.cssText = 'background: #0066cc; color: white; border: none; border-radius: 6px; cursor: pointer; padding: 0.75rem; display: flex; align-items: center; justify-content: center; transition: background 0.2s; width: 50px; height: 50px;';
            btnSiguiente.onmouseover = function() { btnSiguiente.style.background = '#0052a3'; };
            btnSiguiente.onmouseout = function() { btnSiguiente.style.background = '#0066cc'; };
            btnSiguiente.onclick = function() {
                indiceActual = (indiceActual + 1) % imagenes.length;
                img.src = imagenes[indiceActual].data;
                contador.textContent = (indiceActual + 1) + ' de ' + imagenes.length;
            };
            controles.appendChild(btnSiguiente);
            
            contenedor.appendChild(controles);
            modal.appendChild(contenedor);
            document.body.appendChild(modal);
            
            // Soporte para navegación con teclas
            const manejarTeclas = function(e) {
                if (e.key === 'ArrowLeft') {
                    btnAnterior.click();
                } else if (e.key === 'ArrowRight') {
                    btnSiguiente.click();
                } else if (e.key === 'Escape') {
                    modal.remove();
                    document.removeEventListener('keydown', manejarTeclas);
                }
            };
            document.addEventListener('keydown', manejarTeclas);
            
            modal.addEventListener('remove', function() {
                document.removeEventListener('keydown', manejarTeclas);
            });
        }
        
        function mostrarGaleriaImagenes(imagenes, indiceInicial = 0) {
            let indiceActual = indiceInicial;
            
            const modal = document.createElement('div');
            modal.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); display: flex; align-items: center; justify-content: center; z-index: 10000;';
            modal.onclick = function(e) {
                if (e.target === modal) {
                    modal.remove();
                }
            };
            
            const contenedor = document.createElement('div');
            contenedor.style.cssText = 'position: relative; max-width: 800px; width: 90%; max-height: 90vh;';
            
            // Imagen grande
            const imgContainer = document.createElement('div');
            imgContainer.style.cssText = 'position: relative; width: 100%; background: black; border-radius: 8px; overflow: hidden;';
            
            const img = document.createElement('img');
            img.src = imagenes[indiceActual].data;
            img.style.cssText = 'width: 100%; height: auto; max-height: 80vh; object-fit: contain;';
            imgContainer.appendChild(img);
            
            // Contador
            const contador = document.createElement('div');
            contador.style.cssText = 'position: absolute; top: 1rem; right: 1rem; background: #0066cc; color: white; padding: 0.5rem 1rem; border-radius: 6px; font-weight: 600; font-size: 0.875rem;';
            contador.textContent = (indiceActual + 1) + ' de ' + imagenes.length;
            imgContainer.appendChild(contador);
            
            // Botón cerrar
            const btnCerrar = document.createElement('button');
            btnCerrar.innerHTML = '<span class="material-symbols-rounded" style="font-size: 1.5rem;">close</span>';
            btnCerrar.style.cssText = 'position: absolute; top: 1rem; left: 1rem; background: #0066cc; color: white; border: none; border-radius: 6px; cursor: pointer; padding: 0.5rem; display: flex; align-items: center; justify-content: center; width: 40px; height: 40px; transition: background 0.2s;';
            btnCerrar.onmouseover = function() { btnCerrar.style.background = '#0052a3'; };
            btnCerrar.onmouseout = function() { btnCerrar.style.background = '#0066cc'; };
            btnCerrar.onclick = function() {
                modal.remove();
            };
            imgContainer.appendChild(btnCerrar);
            
            contenedor.appendChild(imgContainer);
            
            // Controles (flechas y eliminar)
            const controles = document.createElement('div');
            controles.style.cssText = 'display: flex; gap: 1rem; justify-content: center; align-items: center; margin-top: 1.5rem;';
            
            // Flecha izquierda
            const btnAnterior = document.createElement('button');
            btnAnterior.innerHTML = '<span class="material-symbols-rounded" style="font-size: 1.5rem;">arrow_back</span>';
            btnAnterior.style.cssText = 'background: #0066cc; color: white; border: none; border-radius: 6px; cursor: pointer; padding: 0.75rem; display: flex; align-items: center; justify-content: center; transition: background 0.2s; width: 50px; height: 50px;';
            btnAnterior.onmouseover = function() { btnAnterior.style.background = '#0052a3'; };
            btnAnterior.onmouseout = function() { btnAnterior.style.background = '#0066cc'; };
            btnAnterior.onclick = function() {
                indiceActual = (indiceActual - 1 + imagenes.length) % imagenes.length;
                img.src = imagenes[indiceActual].data;
                contador.textContent = (indiceActual + 1) + ' de ' + imagenes.length;
                btnEliminar.style.display = imagenes.length > 1 ? 'flex' : 'none';
            };
            controles.appendChild(btnAnterior);
            
            // Botón eliminar
            const btnEliminar = document.createElement('button');
            btnEliminar.innerHTML = '<span class="material-symbols-rounded" style="font-size: 1.5rem;">delete</span>';
            btnEliminar.style.cssText = 'background: #ef4444; color: white; border: none; border-radius: 6px; cursor: pointer; padding: 0.75rem; display: flex; align-items: center; justify-content: center; transition: background 0.2s; width: 50px; height: 50px;';
            btnEliminar.onmouseover = function() { btnEliminar.style.background = '#dc2626'; };
            btnEliminar.onmouseout = function() { btnEliminar.style.background = '#ef4444'; };
            btnEliminar.onclick = function() {
                if (confirm('¿Eliminar esta imagen?')) {
                    window.imagenesTelaStorage.splice(indiceActual, 1);
                    
                    if (window.imagenesTelaStorage.length === 0) {
                        modal.remove();
                        actualizarPreviewTela();
                        return;
                    }
                    
                    if (indiceActual >= window.imagenesTelaStorage.length) {
                        indiceActual = window.imagenesTelaStorage.length - 1;
                    }
                    
                    img.src = window.imagenesTelaStorage[indiceActual].data;
                    contador.textContent = (indiceActual + 1) + ' de ' + window.imagenesTelaStorage.length;
                    
                    // Ocultar botón eliminar si solo queda 1 imagen
                    btnEliminar.style.display = window.imagenesTelaStorage.length > 1 ? 'flex' : 'none';
                    
                    actualizarPreviewTela();
                }
            };
            controles.appendChild(btnEliminar);
            
            // Flecha derecha
            const btnSiguiente = document.createElement('button');
            btnSiguiente.innerHTML = '<span class="material-symbols-rounded" style="font-size: 1.5rem;">arrow_forward</span>';
            btnSiguiente.style.cssText = 'background: #0066cc; color: white; border: none; border-radius: 6px; cursor: pointer; padding: 0.75rem; display: flex; align-items: center; justify-content: center; transition: background 0.2s; width: 50px; height: 50px;';
            btnSiguiente.onmouseover = function() { btnSiguiente.style.background = '#0052a3'; };
            btnSiguiente.onmouseout = function() { btnSiguiente.style.background = '#0066cc'; };
            btnSiguiente.onclick = function() {
                indiceActual = (indiceActual + 1) % window.imagenesTelaStorage.length;
                img.src = window.imagenesTelaStorage[indiceActual].data;
                contador.textContent = (indiceActual + 1) + ' de ' + window.imagenesTelaStorage.length;
                btnEliminar.style.display = window.imagenesTelaStorage.length > 1 ? 'flex' : 'none';
            };
            controles.appendChild(btnSiguiente);
            
            // Ocultar botón eliminar si solo hay 1 imagen
            btnEliminar.style.display = window.imagenesTelaStorage.length > 1 ? 'flex' : 'none';
            
            contenedor.appendChild(controles);
            modal.appendChild(contenedor);
            document.body.appendChild(modal);
            
            // Soporte para navegación con teclas
            const manejarTeclas = function(e) {
                if (e.key === 'ArrowLeft') {
                    btnAnterior.click();
                } else if (e.key === 'ArrowRight') {
                    btnSiguiente.click();
                } else if (e.key === 'Escape') {
                    modal.remove();
                    document.removeEventListener('keydown', manejarTeclas);
                }
            };
            document.addEventListener('keydown', manejarTeclas);
            
            modal.addEventListener('remove', function() {
                document.removeEventListener('keydown', manejarTeclas);
            });
        }
        
        // Configuración inicial
        document.addEventListener('DOMContentLoaded', function() {
            // ========== BUSCADOR DE COTIZACIONES ==========
            const searchInput = document.getElementById('cotizacion_search_editable');
            const dropdown = document.getElementById('cotizacion_dropdown_editable');
            const selectedDiv = document.getElementById('cotizacion_selected_editable');
            const selectedText = document.getElementById('cotizacion_selected_text_editable');
            const hiddenInput = document.getElementById('cotizacion_id_editable');
            
            console.log('🔍 [BUSCADOR] Elementos encontrados:', {
                searchInput: !!searchInput,
                dropdown: !!dropdown,
                selectedDiv: !!selectedDiv,
                selectedText: !!selectedText,
                hiddenInput: !!hiddenInput
            });
            
            if (!searchInput) {
                console.error('❌ [BUSCADOR] No se encontró el input de búsqueda');
                return;
            }
            
            let cotizacionSeleccionada = null;
            
            // Mostrar todas las cotizaciones al hacer focus
            searchInput.addEventListener('focus', function() {
                console.log('🔍 [BUSCADOR] Focus en el campo de búsqueda');
                mostrarCotizaciones('');
            });
            
            // Filtrar cotizaciones al escribir
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase().trim();
                console.log('🔍 [BUSCADOR] Búsqueda:', searchTerm);
                mostrarCotizaciones(searchTerm);
            });
            
            // Función para mostrar cotizaciones filtradas
            function mostrarCotizaciones(searchTerm) {
                if (searchTerm.length === 0) {
                    // Mostrar todas las cotizaciones
                    renderizarDropdown(window.cotizacionesData);
                    return;
                }
                
                const filtered = window.cotizacionesData.filter(cot => {
                    return cot.numero_cotizacion.toLowerCase().includes(searchTerm) ||
                           cot.cliente.toLowerCase().includes(searchTerm) ||
                           cot.asesora.toLowerCase().includes(searchTerm);
                });
                
                renderizarDropdown(filtered);
            }
            
            // Función para renderizar el dropdown
            function renderizarDropdown(cotizaciones) {
                console.log('🔍 [BUSCADOR] Renderizando dropdown con', cotizaciones.length, 'cotizaciones');
                
                if (cotizaciones.length === 0) {
                    dropdown.innerHTML = '<div style="padding: 1rem; text-align: center; color: #6b7280;">No se encontraron cotizaciones</div>';
                    dropdown.style.display = 'block';
                    console.log('🔍 [BUSCADOR] Mostrando mensaje de "no encontrado"');
                    return;
                }
                
                dropdown.innerHTML = cotizaciones.map(cot => `
                    <div class="cotizacion-item" data-id="${cot.id}" style="padding: 0.75rem; cursor: pointer; border-bottom: 1px solid #e5e7eb; transition: background 0.2s;" onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background='white'">
                        <div style="font-weight: 600; color: #1e40af;">${cot.numero_cotizacion}</div>
                        <div style="font-size: 0.875rem; color: #6b7280; margin-top: 0.25rem;">
                            Cliente: ${cot.cliente} | Asesora: ${cot.asesora}
                        </div>
                    </div>
                `).join('');
                
                dropdown.style.display = 'block';
                console.log('✅ [BUSCADOR] Dropdown mostrado con', cotizaciones.length, 'resultados');
                
                // Agregar event listeners a los items
                dropdown.querySelectorAll('.cotizacion-item').forEach(item => {
                    item.addEventListener('click', function() {
                        const cotId = parseInt(this.dataset.id);
                        const cotizacion = window.cotizacionesData.find(c => c.id === cotId);
                        seleccionarCotizacion(cotizacion);
                    });
                });
            }
            
            // Función para seleccionar cotización
            function seleccionarCotizacion(cotizacion) {
                cotizacionSeleccionada = cotizacion;
                hiddenInput.value = cotizacion.id;
                searchInput.value = cotizacion.numero_cotizacion;
                selectedText.textContent = `${cotizacion.numero_cotizacion} - ${cotizacion.cliente}`;
                selectedDiv.style.display = 'block';
                dropdown.style.display = 'none';
                
                console.log('✅ Cotización seleccionada:', cotizacion);
                
                // Abrir modal de selección de prendas
                if (typeof window.abrirModalSeleccionPrendas === 'function') {
                    window.abrirModalSeleccionPrendas(cotizacion);
                } else {
                    console.error('❌ Función abrirModalSeleccionPrendas no está disponible');
                }
            }
            
            // Cerrar dropdown al hacer click fuera
            document.addEventListener('click', function(e) {
                if (!searchInput.contains(e.target) && !dropdown.contains(e.target)) {
                    dropdown.style.display = 'none';
                }
            });
            
            // ========== RESTO DE LA CONFIGURACIÓN ==========
            // Configurar asesora
            document.getElementById('asesora_editable').value = '{{ Auth::user()->name ?? '' }}';
            
            // Mostrar botón submit
            const btnSubmit = document.getElementById('btn-submit');
            btnSubmit.textContent = '✓ Crear Pedido';
            btnSubmit.style.display = 'block';

            // ========== OCULTAR LOADING Y MOSTRAR SELECT DE TIPO DE PEDIDO ==========
            const tipoPedidoLoading = document.getElementById('tipo-pedido-loading');
            const tipoPedidoSelect = document.getElementById('tipo_pedido_nuevo');
            
            if (tipoPedidoLoading && tipoPedidoSelect) {
                setTimeout(() => {
                    tipoPedidoLoading.style.display = 'none';
                    tipoPedidoSelect.style.display = 'block';
                    tipoPedidoSelect.removeAttribute('disabled');
                    console.log('✅ Selector de tipo de pedido listo');
                }, 500);
            }

            // ========== DETECTAR TIPO INICIAL DESDE RUTA ==========
            const tipoInicial = '{{ $tipoInicial ?? "cotizacion" }}';
            
            // ========== MANEJAR CAMBIO DE TIPO DE PEDIDO ==========
            const tipoDesdeRadio = document.getElementById('tipo_desde_cotizacion');
            const tipoNuevoRadio = document.getElementById('tipo_nuevo_pedido');
            const tipoHidden = document.getElementById('tipo_pedido_hidden');
            const seccionBuscarCotizacion = document.getElementById('seccion-buscar-cotizacion');
            const seccionTipoPedidoNuevo = document.getElementById('seccion-tipo-pedido-nuevo');
            const selectTipoPedidoNuevo = document.getElementById('tipo_pedido_nuevo');
            const campNumeroCotizacion = document.getElementById('campo-numero-cotizacion');

            function actualizarVistaPedido() {
                // Si hay tipo inicial forzado, usar ese
                let tipoActual = tipoInicial;
                
                // Si no hay tipo inicial, usar los radio buttons
                if (!tipoHidden && tipoDesdeRadio && tipoNuevoRadio) {
                    tipoActual = tipoDesdeRadio.checked ? 'cotizacion' : 'nuevo';
                }
                
                if (tipoActual === 'cotizacion') {
                    // Mostrar buscador de cotización
                    if (seccionBuscarCotizacion) seccionBuscarCotizacion.style.display = 'block';
                    if (seccionTipoPedidoNuevo) seccionTipoPedidoNuevo.style.display = 'none';
                    if (campNumeroCotizacion) campNumeroCotizacion.style.display = 'block';
                } else if (tipoActual === 'nuevo') {
                    // Mostrar selector de tipo de pedido nuevo
                    if (seccionBuscarCotizacion) seccionBuscarCotizacion.style.display = 'none';
                    if (seccionTipoPedidoNuevo) seccionTipoPedidoNuevo.style.display = 'block';
                    if (campNumeroCotizacion) campNumeroCotizacion.style.display = 'none';
                }
            }

            // Ejecutar al cargar para configurar vista inicial
            actualizarVistaPedido();

            // Listener para cambios en radio buttons (solo si existen)
            if (tipoDesdeRadio) tipoDesdeRadio.addEventListener('change', actualizarVistaPedido);
            if (tipoNuevoRadio) tipoNuevoRadio.addEventListener('change', actualizarVistaPedido);

            // ========== GESTIÓN DE ÍTEMS DINÁMICOS ==========
            // ⚠️ REFACTORIZADO: El código de gestión de ítems ahora está en gestion-items-pedido.js
            // Solo mantenemos las referencias al DOM que se necesitan aquí
            const seccionItems = document.getElementById('seccion-items-pedido');
            const btnAgregarItemCotizacion = document.getElementById('btn-agregar-item-cotizacion');
            const btnAgregarItemTipo = document.getElementById('btn-agregar-item-tipo');
            
            // Guardar cotización seleccionada globalmente
            window.cotizacionSeleccionadaActual = null;
            
            // Actualizar referencia cuando se selecciona una cotización
            const originalSeleccionarCotizacion = seleccionarCotizacion;
            seleccionarCotizacion = function(cotizacion) {
                window.cotizacionSeleccionadaActual = cotizacion;
                originalSeleccionarCotizacion(cotizacion);
            };
            
            // Agregar evento al botón "Agregar Prendas"
            if (btnAgregarItemCotizacion) {
                btnAgregarItemCotizacion.addEventListener('click', function() {
                    if (window.cotizacionSeleccionadaActual) {
                        window.abrirModalSeleccionPrendas(window.cotizacionSeleccionadaActual);
                    } else {
                        alert('Por favor selecciona una cotización primero');
                    }
                });
            }
            
            // ⚠️ itemsPedido ahora es window.itemsPedido (definido en gestion-items-pedido.js)

            // Mostrar sección de ítems según el tipo
            function mostrarSeccionItems() {
                if (seccionItems) {
                    seccionItems.style.display = 'block';
                    
                    // Mostrar botones según el tipo
                    if (tipoInicial === 'cotizacion') {
                        btnAgregarItemCotizacion.style.display = 'flex';
                        btnAgregarItemTipo.style.display = 'none';
                    } else if (tipoInicial === 'nuevo') {
                        btnAgregarItemCotizacion.style.display = 'none';
                        btnAgregarItemTipo.style.display = 'flex';
                    }
                    
                    window.actualizarVistaItems(); // ⚠️ Ahora es función global del módulo
                }
            }

            // ⚠️ REFACTORIZADO: Las siguientes funciones ahora están en gestion-items-pedido.js
            // - window.actualizarVistaItems()
            // - renderizarItems()
            // - determinarCategoria()
            // - obtenerColorCategoria()
            // - window.eliminarItem()
            // - window.obtenerItemsPedido()
            // - window.tieneItems()

            /* ============================================================
               CÓDIGO COMENTADO - AHORA EN gestion-items-pedido.js
               ============================================================
               Las siguientes funciones fueron movidas al módulo:
               - actualizarVistaItems()
               - renderizarItems()
               - determinarCategoria()
               - obtenerColorCategoria()
               - eliminarItem()
               - obtenerItemsPedido()
               - tieneItems()
               ============================================================ */

            // Agregar ítem desde cotización
            btnAgregarItemCotizacion.addEventListener('click', function() {
                // Mostrar el buscador de cotización si está oculto
                if (seccionBuscarCotizacion.style.display === 'none') {
                    seccionBuscarCotizacion.style.display = 'block';
                }
                // Focus en el input de búsqueda
                document.getElementById('cotizacion_search_editable').focus();
            });

            // Agregar ítem de tipo nuevo
            btnAgregarItemTipo.addEventListener('click', function() {
                const tipoPedido = selectTipoPedidoNuevo.value;
                
                if (!tipoPedido) {
                    alert('Por favor selecciona un tipo de pedido primero');
                    return;
                }
                
                console.log('🎯 Abriendo modal para tipo:', tipoPedido);
                
                // Manejar diferentes tipos de pedido
                if (tipoPedido === 'P') {
                    window.abrirModalPrendaNueva();
                } else if (tipoPedido === 'R') {
                    window.abrirModalReflectivo();
                } else {
                    alert('Tipo de pedido "' + tipoPedido + '" en desarrollo');
                }
            });

            // Función para agregar cotización a la lista
            window.agregarCotizacionAItems = function(cotizacion) {
                const item = {
                    tipo: 'cotizacion',
                    id: cotizacion.id,
                    numero: cotizacion.numero_cotizacion,
                    cliente: cotizacion.cliente,
                    data: cotizacion
                };
                itemsPedido.push(item);
                actualizarVistaItems();
            };

            // Función para agregar tipo nuevo a la lista
            window.agregarTipoAItems = function(tipo, nombre) {
                const item = {
                    tipo: 'nuevo',
                    tipoId: tipo,
                    nombre: nombre
                };
                itemsPedido.push(item);
                actualizarVistaItems();
            };

            // Manejar cambio de tipo de pedido nuevo
            window.manejarCambiaTipoPedido = function() {
                const tipoPedido = selectTipoPedidoNuevo.value;
                
                if (!tipoPedido) return;
                
                console.log('🔄 Tipo de pedido seleccionado:', tipoPedido);
                
                // Mostrar botón de agregar tipo
                const btnAgregarTipo = document.getElementById('btn-agregar-item-tipo');
                if (btnAgregarTipo) {
                    btnAgregarTipo.style.display = 'flex';
                }
            };
            
            // ========== MODAL DE PRENDA NUEVA ==========
            window.abrirModalPrendaNueva = function() {
                const modal = document.getElementById('modal-agregar-prenda-nueva');
                if (modal) {
                    modal.style.display = 'flex';
                    limpiarFormularioPrendaNueva();
                    configurarEventosFormulario();
                }
            };
            
            function limpiarFormularioPrendaNueva() {
                document.getElementById('nueva-prenda-nombre').value = '';
                document.getElementById('nueva-prenda-descripcion').value = '';
                document.getElementById('nueva-prenda-color').value = '';
                document.getElementById('nueva-prenda-tela').value = '';
                document.getElementById('nueva-prenda-referencia').value = '';
                
                // Reset tallas seleccionadas
                window.tallasSeleccionadas = {
                    dama: { tallas: [], tipo: null },
                    caballero: { tallas: [], tipo: null }
                };
                
                // Reset botones
                const btnDama = document.getElementById('btn-genero-dama');
                const btnCaballero = document.getElementById('btn-genero-caballero');
                
                btnDama.dataset.selected = 'false';
                btnDama.style.borderColor = '#d1d5db';
                btnDama.style.background = 'white';
                document.getElementById('check-dama').style.display = 'none';
                
                btnCaballero.dataset.selected = 'false';
                btnCaballero.style.borderColor = '#d1d5db';
                btnCaballero.style.background = 'white';
                document.getElementById('check-caballero').style.display = 'none';
                
                // Limpiar tarjetas
                document.getElementById('tarjetas-generos-container').innerHTML = '';
                
                // Reset total
                document.getElementById('total-prendas').textContent = '0';
                
                // Limpiar variaciones
                document.querySelectorAll('#modal-agregar-prenda-nueva input[type="checkbox"]').forEach(cb => cb.checked = false);
                document.querySelectorAll('#manga-input, #bolsillos-input, #broche-input, #puno-input').forEach(input => {
                    input.value = '';
                    input.disabled = true;
                    input.style.opacity = '0.5';
                });
                
                // Reset origen
                document.getElementById('nueva-prenda-origen-select').value = 'bodega';
            }
            
            function configurarEventosFormulario() {
                // Habilitar/deshabilitar inputs de variaciones
                const mangaCb = document.getElementById('aplica-manga');
                const bolsillosCb = document.getElementById('aplica-bolsillos');
                const brocheCb = document.getElementById('aplica-broche');
                const punoCb = document.getElementById('aplica-puno');
                
                // Remover listeners anteriores si existen
                if (mangaCb._configured) return;
                
                mangaCb.addEventListener('change', function() {
                    const input = document.getElementById('manga-input');
                    input.disabled = !this.checked;
                    input.style.opacity = this.checked ? '1' : '0.5';
                });
                
                bolsillosCb.addEventListener('change', function() {
                    const input = document.getElementById('bolsillos-input');
                    input.disabled = !this.checked;
                    input.style.opacity = this.checked ? '1' : '0.5';
                });
                
                brocheCb.addEventListener('change', function() {
                    const input = document.getElementById('broche-input');
                    input.disabled = !this.checked;
                    input.style.opacity = this.checked ? '1' : '0.5';
                });
                
                punoCb.addEventListener('change', function() {
                    const input = document.getElementById('puno-input');
                    input.disabled = !this.checked;
                    input.style.opacity = this.checked ? '1' : '0.5';
                });
                
                // Marcar como configurado
                mangaCb._configured = true;
            }
            
            window.cerrarModalPrendaNueva = function() {
                const modal = document.getElementById('modal-agregar-prenda-nueva');
                if (modal) {
                    modal.style.display = 'none';
                }
                
                // Limpiar checkboxes de género
                document.getElementById('genero-dama').checked = false;
                document.getElementById('genero-caballero').checked = false;
                
                // Limpiar selectores de tipo de talla
                const tipoTallaDama = document.getElementById('tipo-talla-dama');
                const tipoTallaCaballero = document.getElementById('tipo-talla-caballero');
                if (tipoTallaDama) tipoTallaDama.value = '';
                if (tipoTallaCaballero) tipoTallaCaballero.value = '';
                
                // Limpiar contenedor de géneros
                document.getElementById('generos-container').innerHTML = '';
                
                // Limpiar imágenes de tela almacenadas
                window.imagenesTelaStorage = [];
                // Limpiar preview de tela
                const previewTela = document.getElementById('nueva-prenda-tela-preview');
                if (previewTela) {
                    previewTela.innerHTML = '';
                }
                // Limpiar imágenes de prenda almacenadas
                window.imagenesPrendaStorage = [];
                // Limpiar preview de prenda
                const previewPrenda = document.getElementById('nueva-prenda-foto-preview');
                if (previewPrenda) {
                    previewPrenda.innerHTML = '<div style="text-align: center;"><div class="material-symbols-rounded" style="font-size: 2rem; color: #9ca3af; margin-bottom: 0.25rem;">add_photo_alternate</div><div style="font-size: 0.7rem; color: #9ca3af;">Click para agregar</div></div>';
                }
                const contadorPrenda = document.getElementById('nueva-prenda-foto-contador');
                if (contadorPrenda) {
                    contadorPrenda.textContent = '';
                }
                const btnPrenda = document.getElementById('nueva-prenda-foto-btn');
                if (btnPrenda) {
                    btnPrenda.style.display = 'none';
                }
            };
            
            window.agregarPrendaNueva = function() {
                const nombre = document.getElementById('nueva-prenda-nombre').value.trim().toUpperCase();
                const descripcion = document.getElementById('nueva-prenda-descripcion').value.trim();
                const color = document.getElementById('nueva-prenda-color').value.trim().toUpperCase();
                const tela = document.getElementById('nueva-prenda-tela').value.trim().toUpperCase();
                const referencia = document.getElementById('nueva-prenda-referencia').value.trim().toUpperCase();
                const origen = document.getElementById('nueva-prenda-origen-select').value;
                
                if (!nombre) {
                    alert('Por favor ingresa el nombre de la prenda');
                    return;
                }
                
                // Obtener tallas y cantidades del nuevo sistema - Formato: { genero: { talla: cantidad } }
                const tallas = {};
                let cantidadTotal = 0;
                
                document.querySelectorAll('#tarjetas-generos-container input[type="number"]').forEach(input => {
                    const cantidad = parseInt(input.value) || 0;
                    if (cantidad > 0) {
                        const genero = input.dataset.genero;
                        const talla = input.dataset.talla;
                        
                        // Inicializar género si no existe
                        if (!tallas[genero]) {
                            tallas[genero] = {};
                        }
                        
                        tallas[genero][talla] = cantidad;
                        cantidadTotal += cantidad;
                    }
                });
                
                if (cantidadTotal === 0) {
                    alert('Por favor ingresa al menos una cantidad en las tallas');
                    return;
                }
                
                // Obtener variaciones
                const variaciones = {};
                if (document.getElementById('aplica-manga').checked) {
                    variaciones.manga = document.getElementById('manga-input').value.trim();
                }
                if (document.getElementById('aplica-bolsillos').checked) {
                    variaciones.bolsillos = document.getElementById('bolsillos-input').value.trim();
                }
                if (document.getElementById('aplica-broche').checked) {
                    variaciones.broche = document.getElementById('broche-input').value.trim();
                }
                if (document.getElementById('aplica-puno').checked) {
                    variaciones.puno = document.getElementById('puno-input').value.trim();
                }
                
                // Obtener procesos seleccionados
                const procesos = [];
                document.querySelectorAll('input[name="nueva-prenda-procesos"]:checked').forEach(cb => {
                    procesos.push(cb.value);
                });
                
                console.log('➕ Agregando prenda nueva:', { nombre, cantidadTotal, origen, procesos, tallas, variaciones });
                
                // Estructura completa de la prenda
                const prendaData = {
                    nombre: nombre,
                    descripcion: descripcion,
                    color: color,
                    tela: tela,
                    referencia: referencia,
                    cantidad: cantidadTotal,
                    tallas: tallas,
                    variaciones: variaciones
                };
                
                // REGLA DE SPLIT: Si tiene procesos, crear 2 ítems
                if (procesos.length > 0) {
                    // ÍTEM 1: Prenda BASE (sin procesos)
                    window.itemsPedido.push({
                        tipo: 'nuevo',
                        prenda: prendaData,
                        origen: origen,
                        procesos: [],
                        es_proceso: false
                    });
                    
                    // ÍTEM 2: Prenda PROCESO (con procesos)
                    window.itemsPedido.push({
                        tipo: 'nuevo',
                        prenda: prendaData,
                        origen: origen,
                        procesos: procesos,
                        es_proceso: true
                    });
                    
                    console.log(`✅ Prenda "${nombre}" agregada como 2 ítems (BASE + PROCESO)`);
                } else {
                    // Sin procesos: 1 solo ítem
                    window.itemsPedido.push({
                        tipo: 'nuevo',
                        prenda: prendaData,
                        origen: origen,
                        procesos: [],
                        es_proceso: false
                    });
                    
                    console.log(`✅ Prenda "${nombre}" agregada como 1 ítem (sin procesos)`);
                }
                
                // Actualizar vista
                window.actualizarVistaItems();
                
                // Cerrar modal
                window.cerrarModalPrendaNueva();
            };

            // ========== MODAL DE REFLECTIVO ==========
            window.abrirModalReflectivo = function() {
                const modal = document.getElementById('modal-agregar-reflectivo');
                if (modal) {
                    modal.style.display = 'flex';
                    limpiarFormularioReflectivo();
                    configurarEventosReflectivo();
                }
            };
            
            function limpiarFormularioReflectivo() {
                document.getElementById('reflectivo-prenda-nombre').value = '';
                document.getElementById('reflectivo-observaciones').value = '';
                
                // Reset selectores de talla
                document.getElementById('reflectivo-tipo-talla').value = 'letra';
                document.getElementById('reflectivo-genero-talla').value = '';
                document.getElementById('reflectivo-genero-talla').style.display = 'none';
                
                // Inicializar tallas por defecto (letras)
                actualizarTallasReflectivo();
                
                // Limpiar ubicaciones
                document.querySelectorAll('input[name="reflectivo-ubicacion"]').forEach(cb => cb.checked = false);
                
                // Reset origen
                document.getElementById('reflectivo-origen-select').value = 'bodega';
            }
            
            function configurarEventosReflectivo() {
                // No necesita configurar eventos adicionales
                // Los eventos onchange ya están en los inputs dinámicos
            }
            
            function actualizarTotalReflectivo() {
                let total = 0;
                document.querySelectorAll('#reflectivo-tallas-container input[type="number"]').forEach(input => {
                    total += parseInt(input.value) || 0;
                });
                document.getElementById('total-reflectivo').textContent = total;
            }
            
            window.cerrarModalReflectivo = function() {
                const modal = document.getElementById('modal-agregar-reflectivo');
                if (modal) {
                    modal.style.display = 'none';
                }
            };
            
            window.agregarReflectivo = function() {
                const nombre = document.getElementById('reflectivo-prenda-nombre').value.trim().toUpperCase();
                const observaciones = document.getElementById('reflectivo-observaciones').value.trim();
                const origen = document.getElementById('reflectivo-origen-select').value;
                
                if (!nombre) {
                    alert('Por favor ingresa el nombre de la prenda');
                    return;
                }
                
                // Obtener tallas y cantidades
                const tallas = [];
                let cantidadTotal = 0;
                document.querySelectorAll('#reflectivo-tallas-container input[type="number"]').forEach(input => {
                    const cantidad = parseInt(input.value) || 0;
                    if (cantidad > 0) {
                        const talla = input.name.replace('reflectivo-talla-', '').toUpperCase();
                        tallas.push({ talla, cantidad });
                        cantidadTotal += cantidad;
                    }
                });
                
                if (cantidadTotal === 0) {
                    alert('Por favor ingresa al menos una cantidad en las tallas');
                    return;
                }
                
                // Obtener ubicaciones del reflectivo
                const ubicaciones = [];
                document.querySelectorAll('input[name="reflectivo-ubicacion"]:checked').forEach(cb => {
                    ubicaciones.push(cb.value);
                });
                
                console.log('➕ Agregando reflectivo:', { nombre, cantidadTotal, origen, ubicaciones, observaciones });
                
                // Estructura del reflectivo
                const reflectivoData = {
                    nombre: nombre,
                    cantidad: cantidadTotal,
                    tallas: tallas,
                    ubicaciones: ubicaciones,
                    observaciones: observaciones
                };
                
                // REFLECTIVO SIEMPRE TIENE PROCESO
                // ÍTEM 1: Prenda BASE (sin procesos)
                window.itemsPedido.push({
                    tipo: 'nuevo',
                    prenda: reflectivoData,
                    origen: origen,
                    procesos: [],
                    es_proceso: false
                });
                
                // ÍTEM 2: REFLECTIVO (con proceso reflectivo)
                window.itemsPedido.push({
                    tipo: 'nuevo',
                    prenda: reflectivoData,
                    origen: origen,
                    procesos: ['Reflectivo'],
                    es_proceso: true
                });
                
                console.log(`✅ Reflectivo "${nombre}" agregado como 2 ítems (BASE + REFLECTIVO)`);
                
                // Actualizar vista
                window.actualizarVistaItems();
                
                // Cerrar modal
                window.cerrarModalReflectivo();
            };

            // ========== ALMACENAMIENTO DE TALLAS SELECCIONADAS ==========
            window.tallasSeleccionadas = {
                dama: { tallas: [], tipo: null },
                caballero: { tallas: [], tipo: null }
            };
            
            // ========== CONSTANTES DE TALLAS ==========
            const TALLAS_LETRAS = ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL'];
            const TALLAS_NUMEROS_DAMA = ['2', '4', '6', '8', '10', '12', '14', '16', '18', '20', '22', '24', '26', '28'];
            const TALLAS_NUMEROS_CABALLERO = ['30', '32', '34', '36', '38', '40', '42', '44', '46', '48', '50', '52', '54', '56'];
            
            // ========== ABRIR MODAL PARA SELECCIONAR TALLAS ==========
            window.abrirModalSeleccionarTallas = function(genero) {
                window.generoActual = genero;
                document.getElementById('genero-modal-display').textContent = genero.toUpperCase();
                document.getElementById('modal-seleccionar-tallas').style.display = 'flex';
                
                // Reset botones de tipo
                document.getElementById('btn-tipo-letra').dataset.selected = 'false';
                document.getElementById('btn-tipo-numero').dataset.selected = 'false';
                document.getElementById('btn-tipo-letra').style.background = 'white';
                document.getElementById('btn-tipo-numero').style.background = 'white';
                document.getElementById('btn-tipo-letra').style.borderColor = '#d1d5db';
                document.getElementById('btn-tipo-numero').style.borderColor = '#d1d5db';
                document.getElementById('container-tallas-seleccion').innerHTML = '';
                
                // ========== SI YA EXISTE UN TIPO ASIGNADO, MOSTRAR AUTOMÁTICAMENTE ==========
                if (window.tallasSeleccionadas[genero].tipo) {
                    const tipoExistente = window.tallasSeleccionadas[genero].tipo;
                    seleccionarTipoTalla(tipoExistente);
                }
            };
            
            // ========== CERRAR MODAL ==========
            window.cerrarModalSeleccionarTallas = function() {
                document.getElementById('modal-seleccionar-tallas').style.display = 'none';
                window.generoActual = null;
            };
            
            // ========== SELECCIONAR TIPO DE TALLA ==========
            window.seleccionarTipoTalla = function(tipo) {
                const btnLetra = document.getElementById('btn-tipo-letra');
                const btnNumero = document.getElementById('btn-tipo-numero');
                
                // Reset todos
                btnLetra.dataset.selected = 'false';
                btnNumero.dataset.selected = 'false';
                btnLetra.style.background = 'white';
                btnNumero.style.background = 'white';
                btnLetra.style.borderColor = '#d1d5db';
                btnNumero.style.borderColor = '#d1d5db';
                btnLetra.style.color = '#6b7280';
                btnNumero.style.color = '#6b7280';
                
                // Seleccionar el actual
                if (tipo === 'letra') {
                    btnLetra.dataset.selected = 'true';
                    btnLetra.style.background = '#0066cc';
                    btnLetra.style.borderColor = '#0066cc';
                    btnLetra.style.color = 'white';
                } else if (tipo === 'numero') {
                    btnNumero.dataset.selected = 'true';
                    btnNumero.style.background = '#0066cc';
                    btnNumero.style.borderColor = '#0066cc';
                    btnNumero.style.color = 'white';
                }
                
                // Generar tallas disponibles
                mostrarTallasDisponibles(tipo);
                
                // ========== SINCRONIZAR CON EL OTRO GÉNERO ==========
                const otroGenero = window.generoActual === 'dama' ? 'caballero' : 'dama';
                
                // Asignar mismo tipo al otro género
                window.tallasSeleccionadas[otroGenero].tipo = tipo;
                
                // Automáticamente mostrar tallas del otro género (sin abrir modal)
                // Solo si el usuario confirma, el otro género también se activa
            };
            
            // ========== MOSTRAR TALLAS DISPONIBLES ==========
            window.mostrarTallasDisponibles = function(tipo) {
                const container = document.getElementById('container-tallas-seleccion');
                let tallasDisponibles = [];
                
                if (tipo === 'letra') {
                    tallasDisponibles = TALLAS_LETRAS;
                } else if (tipo === 'numero' && window.generoActual === 'dama') {
                    tallasDisponibles = TALLAS_NUMEROS_DAMA;
                } else if (tipo === 'numero' && window.generoActual === 'caballero') {
                    tallasDisponibles = TALLAS_NUMEROS_CABALLERO;
                }
                
                container.innerHTML = '';
                tallasDisponibles.forEach(talla => {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.dataset.talla = talla;
                    btn.dataset.selected = 'false';
                    btn.textContent = talla;
                    btn.onclick = function() {
                        this.dataset.selected = this.dataset.selected === 'true' ? 'false' : 'true';
                        this.style.background = this.dataset.selected === 'true' ? '#0066cc' : 'white';
                        this.style.borderColor = this.dataset.selected === 'true' ? '#0066cc' : '#d1d5db';
                        this.style.color = this.dataset.selected === 'true' ? 'white' : '#6b7280';
                    };
                    btn.style.cssText = 'padding: 0.75rem; border: 2px solid #d1d5db; background: white; border-radius: 6px; cursor: pointer; font-weight: 600; color: #6b7280; transition: all 0.2s;';
                    container.appendChild(btn);
                });
            };
            
            // ========== CONFIRMAR SELECCIÓN DE TALLAS ==========
            window.confirmarSeleccionTallas = function() {
                const tallasSeleccionadas = [];
                document.querySelectorAll('#container-tallas-seleccion button[data-selected="true"]').forEach(btn => {
                    tallasSeleccionadas.push(btn.dataset.talla);
                });
                
                if (tallasSeleccionadas.length === 0) {
                    alert('Selecciona al menos una talla');
                    return;
                }
                
                // Guardar selección
                const tipoSeleccionado = document.getElementById('btn-tipo-letra').dataset.selected === 'true' ? 'letra' : 'numero';
                window.tallasSeleccionadas[window.generoActual] = {
                    tallas: tallasSeleccionadas,
                    tipo: tipoSeleccionado
                };
                
                // Actualizar botones y tarjetas
                actualizarTarjetasGeneros();
                
                // ========== ABRIR AUTOMÁTICAMENTE EL OTRO GÉNERO ==========
                const otroGenero = window.generoActual === 'dama' ? 'caballero' : 'dama';
                
                // Cerrar modal actual
                cerrarModalSeleccionarTallas();
                
                // Abrir automáticamente el otro género después de un pequeño delay
                setTimeout(() => {
                    abrirModalSeleccionarTallas(otroGenero);
                }, 300);
            };
            
            // ========== ACTUALIZAR TARJETAS DE GÉNEROS ==========
            window.actualizarTarjetasGeneros = function() {
                const container = document.getElementById('tarjetas-generos-container');
                container.innerHTML = '';
                
                // Actualizar botones
                const btnDama = document.getElementById('btn-genero-dama');
                const btnCaballero = document.getElementById('btn-genero-caballero');
                
                if (window.tallasSeleccionadas.dama.tallas.length > 0) {
                    btnDama.dataset.selected = 'true';
                    btnDama.style.borderColor = '#0066cc';
                    btnDama.style.background = '#dbeafe';
                    document.getElementById('check-dama').style.display = 'inline';
                } else {
                    btnDama.dataset.selected = 'false';
                    btnDama.style.borderColor = '#d1d5db';
                    btnDama.style.background = 'white';
                    document.getElementById('check-dama').style.display = 'none';
                }
                
                if (window.tallasSeleccionadas.caballero.tallas.length > 0) {
                    btnCaballero.dataset.selected = 'true';
                    btnCaballero.style.borderColor = '#0066cc';
                    btnCaballero.style.background = '#dbeafe';
                    document.getElementById('check-caballero').style.display = 'inline';
                } else {
                    btnCaballero.dataset.selected = 'false';
                    btnCaballero.style.borderColor = '#d1d5db';
                    btnCaballero.style.background = 'white';
                    document.getElementById('check-caballero').style.display = 'none';
                }
                
                // Generar tarjetas
                ['dama', 'caballero'].forEach(genero => {
                    if (window.tallasSeleccionadas[genero].tallas.length > 0) {
                        const tarjeta = crearTarjetaGenero(genero);
                        container.appendChild(tarjeta);
                    }
                });
                
                actualizarTotalPrendas();
            };
            
            // ========== CREAR TARJETA DE GÉNERO ==========
            window.crearTarjetaGenero = function(genero) {
                const data = window.tallasSeleccionadas[genero];
                const div = document.createElement('div');
                div.style.cssText = 'border: 2px solid #bfdbfe; border-radius: 6px; padding: 1rem; background: #f0f9ff;';
                
                let html = `
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <span class="material-symbols-rounded" style="color: #0066cc; font-size: 1.5rem;">${genero === 'dama' ? 'woman' : 'man'}</span>
                            <div>
                                <div style="font-weight: 700; color: #0066cc; font-size: 0.95rem;">${genero.toUpperCase()}</div>
                                <div style="font-size: 0.75rem; color: #6b7280;">${data.tipo === 'letra' ? 'Tallas Letras' : 'Tallas Números'}</div>
                            </div>
                        </div>
                        <button onclick="eliminarGenero('${genero}')" style="background: #ef4444; color: white; border: none; border-radius: 4px; padding: 0.5rem 1rem; cursor: pointer; font-weight: 600; font-size: 0.75rem;">
                            ✕ Eliminar
                        </button>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(90px, 1fr)); gap: 0.75rem;">
                `;
                
                data.tallas.forEach(talla => {
                    html += `
                        <div style="display: flex; flex-direction: column; gap: 0.25rem;">
                            <label style="font-size: 0.75rem; font-weight: 600; color: #6b7280; text-align: center;">${talla}</label>
                            <input type="number" data-genero="${genero}" data-talla="${talla}" min="0" value="0" onchange="actualizarTotalPrendas()" style="padding: 0.5rem; border: 2px solid #d1d5db; border-radius: 4px; text-align: center; font-weight: 600; font-size: 0.8rem;">
                        </div>
                    `;
                });
                
                html += '</div>';
                div.innerHTML = html;
                return div;
            };
            
            // ========== ELIMINAR GÉNERO ==========
            window.eliminarGenero = function(genero) {
                window.tallasSeleccionadas[genero] = { tallas: [], tipo: null };
                actualizarTarjetasGeneros();
            };
            
            // ========== ACTUALIZAR TOTAL PRENDAS ==========
            window.actualizarTotalPrendas = function() {
                let total = 0;
                document.querySelectorAll('#tarjetas-generos-container input[type="number"]').forEach(input => {
                    total += parseInt(input.value) || 0;
                });
                document.getElementById('total-prendas').textContent = total;
            };
            
            // Función para actualizar tallas de REFLECTIVO
            window.actualizarTallasReflectivo = function() {
                const tipoTalla = document.getElementById('reflectivo-tipo-talla').value;
                const generoSelect = document.getElementById('reflectivo-genero-talla');
                const container = document.getElementById('reflectivo-tallas-container');
                
                // Mostrar/ocultar selector de género
                if (tipoTalla === 'numero') {
                    generoSelect.style.display = 'block';
                } else {
                    generoSelect.style.display = 'none';
                    generoSelect.value = '';
                }
                
                // Generar tallas
                let tallas = [];
                if (tipoTalla === 'letra') {
                    tallas = TALLAS_LETRAS;
                } else if (tipoTalla === 'numero') {
                    const genero = generoSelect.value;
                    if (genero === 'dama') {
                        tallas = TALLAS_NUMEROS_DAMA;
                    } else if (genero === 'caballero') {
                        tallas = TALLAS_NUMEROS_CABALLERO;
                    }
                }
                
                // Renderizar inputs de tallas
                container.innerHTML = '';
                tallas.forEach(talla => {
                    const div = document.createElement('div');
                    div.style.cssText = 'display: flex; flex-direction: column; gap: 0.25rem;';
                    div.innerHTML = `
                        <label style="font-size: 0.75rem; font-weight: 600; color: #92400e;">${talla}</label>
                        <input type="number" name="reflectivo-talla-${talla.toLowerCase()}" min="0" value="0" onchange="actualizarTotalReflectivo()" style="padding: 0.5rem; border: 2px solid #d1d5db; border-radius: 4px; text-align: center; font-weight: 600;">
                    `;
                    container.appendChild(div);
                });
                
                actualizarTotalReflectivo();
            };

            // ========== LÓGICA DE ÍTEMS REFACTORIZADA ==========
            // ⚠️ REFACTORIZADO: Toda la gestión de ítems ahora está en:
            // - Backend: CrearPedidoEditableController.php
            // - Frontend: gestion-items-pedido-refactorizado.js
            // - API: api-pedidos-editable.js
            
            // Funciones globales disponibles:
            // - window.pedidosAPI.agregarItem(itemData)
            // - window.pedidosAPI.eliminarItem(index)
            // - window.pedidosAPI.obtenerItems()
            // - window.gestionItemsUI.agregarItem(itemData)
            // - window.gestionItemsUI.eliminarItem(index)

            // ========== MODAL DE SELECCIÓN DE PRENDAS ==========
            // ⚠️ REFACTORIZADO: Todo el código del modal ahora está en modal-seleccion-prendas.js
            // Funciones disponibles:
            // - window.abrirModalSeleccionPrendas(cotizacion)
            // - window.cerrarModalPrendas()
            // - window.agregarPrendasSeleccionadas()
            // - window.togglePrendaSeleccion(index)
            // - window.actualizarOrigenPrenda(index, origen)

            // ========== OCULTAR LOADING SCREEN ==========
            setTimeout(() => {
                const loadingOverlay = document.getElementById('page-loading-overlay');
                if (loadingOverlay) {
                    loadingOverlay.classList.add('fade-out');
                    setTimeout(() => {
                        loadingOverlay.style.display = 'none';
                    }, 300);
                }
            }, 500);
        });
    </script>
@endpush