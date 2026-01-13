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

        <!-- PASO 1: Información del Pedido -->
        <div class="form-section" id="seccion-info-prenda">
            <h2>
                <span>1</span> Información del Pedido
            </h2>

            <div class="form-row">
                <!-- Campo Número de Cotización (solo se muestra si viene de cotización) -->
                <div id="campo-numero-cotizacion" class="form-group">
                    <label for="numero_cotizacion_editable">Número de Cotización</label>
                    <input type="text" id="numero_cotizacion_editable" name="numero_cotizacion" readonly>
                </div>

                <div class="form-group">
                    <label for="cliente_editable">
                        Cliente
                        <span id="cliente-requerido" style="color: #ef4444;">*</span>
                    </label>
                    <input type="text" id="cliente_editable" name="cliente">
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

        <!-- PASO 2: Seleccionar Cotización -->
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
                <span>2</span> {{ $tituloTipo }}
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
            </div>
        </div>

        <!-- PASO 3: Ítems del Pedido -->
        <div class="form-section" id="seccion-items-pedido" style="margin-top: 2rem;">
            <h2>
                <span>3</span> Ítems del Pedido
            </h2>
            <div style="display: flex; gap: 0.75rem; margin-bottom: 1rem;">
                <button type="button" id="btn-agregar-item-cotizacion" style="display: none; padding: 0.5rem 1rem; background: #0066cc; color: white; border: none; border-radius: 6px; font-size: 0.875rem; font-weight: 500; cursor: pointer; align-items: center; gap: 0.5rem; transition: background 0.2s;" onmouseover="this.style.background='#0052a3'" onmouseout="this.style.background='#0066cc'">
                    <span style="font-size: 1.25rem;">+</span>
                    Agregar Prendas
                </button>
                <button type="button" id="btn-agregar-item-tipo" style="display: none; padding: 0.5rem 1rem; background: #059669; color: white; border: none; border-radius: 6px; font-size: 0.875rem; font-weight: 500; cursor: pointer; align-items: center; gap: 0.5rem; transition: background 0.2s;" onmouseover="this.style.background='#047857'" onmouseout="this.style.background='#059669'">
                    <span style="font-size: 1.25rem;">+</span>
                    Agregar Prenda
                </button>
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

        <!-- PASO 4: Prendas Editables -->
        <div class="form-section" id="seccion-prendas" style="display: none;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h2 style="margin: 0;">
                    <span>4</span> <span id="titulo-prendas-dinamico">Prendas Técnicas del Logo</span>
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
            <button type="button" id="btn-vista-previa" class="btn btn-secondary" style="display: none; background: #f59e0b; color: white;" title="Ver factura en tamaño grande">
                👁️ Vista Previa
            </button>
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
            // Inicializar storages de imágenes
            window.imagenesPrendaStorage = new ImageStorageService(3);
            window.imagenesTelaStorage = new ImageStorageService(3);
            window.imagenesReflectivoStorage = new ImageStorageService(3);
            
            const seccionItems = document.getElementById('seccion-items-pedido');
            if (seccionItems) {
                seccionItems.style.display = 'block';
            }
            console.log('✅ Sección de ítems mostrada');
            console.log('📋 Cotizaciones disponibles:', window.cotizacionesData.length);
            console.log('✅ Storages de imágenes inicializados');
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
            if (!input.files || input.files.length === 0) {
                return;
            }
            
            window.imagenesTelaStorage.agregarImagen(input.files[0])
                .then(() => {
                    actualizarPreviewTela();
                })
                .catch(err => {
                    alert(err.message);
                });
            input.value = '';
        }
        
        function actualizarPreviewTela() {
            const imagenes = window.imagenesTelaStorage.obtenerImagenes();
            
            // Encontrar la celda de imagen en la fila de inputs
            const tbody = document.getElementById('tbody-telas');
            const primeraFila = tbody.querySelector('tr');
            if (!primeraFila) return;
            
            const celdaImagen = primeraFila.querySelector('td:nth-child(4)');
            if (!celdaImagen) return;
            
            // Limpiar previews anteriores
            const previousPreview = celdaImagen.querySelector('.imagen-preview-tela-temp');
            if (previousPreview) {
                previousPreview.remove();
            }
            
            if (imagenes.length === 0) return;
            
            // Crear contenedor para las imágenes
            const previewDiv = document.createElement('div');
            previewDiv.className = 'imagen-preview-tela-temp';
            previewDiv.style.cssText = 'display: flex; gap: 0.5rem; align-items: center; margin-top: 0.5rem; flex-wrap: wrap;';
            
            // Mostrar todas las imágenes agregadas
            imagenes.forEach((img, index) => {
                const imgElement = document.createElement('img');
                imgElement.src = img.data;
                imgElement.style.cssText = 'width: 50px; height: 50px; border-radius: 4px; object-fit: cover; border: 2px solid #0066cc; cursor: pointer;';
                imgElement.title = `Imagen ${index + 1}`;
                imgElement.onclick = () => mostrarGaleriaImagenes(imagenes, index);
                previewDiv.appendChild(imgElement);
            });
            
            celdaImagen.appendChild(previewDiv);
        }
        
        function manejarImagenesPrenda(input) {
            if (!input.files || input.files.length === 0) {
                return;
            }
            
            window.imagenesPrendaStorage.agregarImagen(input.files[0])
                .then(() => {
                    actualizarPreviewPrenda();
                })
                .catch(err => {
                    alert(err.message);
                });
            input.value = '';
        }
        
        function actualizarPreviewPrenda() {
            const preview = document.getElementById('nueva-prenda-foto-preview');
            const contador = document.getElementById('nueva-prenda-foto-contador');
            const btn = document.getElementById('nueva-prenda-foto-btn');
            const imagenes = window.imagenesPrendaStorage.obtenerImagenes();
            
            if (imagenes.length === 0) {
                preview.innerHTML = '<div style="text-align: center;"><div class="material-symbols-rounded" style="font-size: 2rem; color: #9ca3af; margin-bottom: 0.25rem;">add_photo_alternate</div><div style="font-size: 0.7rem; color: #9ca3af;">Click para agregar</div></div>';
                preview.style.cursor = 'pointer';
                contador.textContent = '';
                btn.style.display = 'block';
                return;
            }
            
            preview.innerHTML = '';
            preview.style.cursor = 'pointer';
            const img = document.createElement('img');
            img.src = imagenes[0].data;
            img.style.cssText = 'width: 100%; height: 100%; object-fit: cover; cursor: pointer;';
            
            preview.appendChild(img);
            
            contador.textContent = imagenes.length === 1 ? '1 foto' : imagenes.length + ' fotos';
            btn.style.display = imagenes.length < 3 ? 'block' : 'none';
        }
        
        // Función para abrir galería si hay imágenes
        function abrirGaleriaOSelectorPrenda() {
            const imagenes = window.imagenesPrendaStorage.obtenerImagenes();
            
            if (imagenes.length > 0) {
                // Si hay imágenes, abre la galería
                mostrarGaleriaPrenda(imagenes, 0);
            }
            // Si no hay imágenes, no hace nada (el usuario debe usar el botón "Agregar")
        }
        
        function mostrarGaleriaPrenda(imagenes, indiceInicial = 0) {
            let indiceActual = indiceInicial;
            let modalClosed = false;
            
            const modal = document.createElement('div');
            modal.style.cssText = 'position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.95); display: flex; flex-direction: column; align-items: center; justify-content: center; z-index: 10000; padding: 0;';
            modal.onclick = function(e) {
                if (e.target === modal && !modalClosed) {
                    modalClosed = true;
                    modal.remove();
                }
            };
            
            const container = document.createElement('div');
            container.style.cssText = 'position: relative; display: flex; flex-direction: column; align-items: center; width: 100%; height: 100%; max-width: 100%; max-height: 100%;';
            
            // Contenedor de imagen - Ocupa casi toda la pantalla
            const imgContainer = document.createElement('div');
            imgContainer.style.cssText = 'flex: 1; display: flex; align-items: center; justify-content: center; position: relative; width: 100%; height: calc(100% - 120px); padding: 2rem;';
            
            const img = document.createElement('img');
            img.style.cssText = 'width: 90%; height: 90%; border-radius: 8px; object-fit: contain; box-shadow: 0 20px 50px rgba(0,0,0,0.7);';
            
            // Función para actualizar la imagen mostrada
            const actualizarImagen = () => {
                const imagenesActuales = window.imagenesPrendaStorage.obtenerImagenes();
                if (imagenesActuales.length > 0 && indiceActual < imagenesActuales.length) {
                    img.src = imagenesActuales[indiceActual].data;
                }
            };
            
            actualizarImagen();
            imgContainer.appendChild(img);
            
            container.appendChild(imgContainer);
            
            // Barra de herramientas con controles
            const toolbar = document.createElement('div');
            toolbar.style.cssText = 'display: flex; justify-content: center; align-items: center; width: 100%; gap: 1rem; padding: 1.5rem; background: rgba(0,0,0,0.5);';
            
            // Botón anterior
            const btnAnterior = document.createElement('button');
            btnAnterior.innerHTML = '<span class="material-symbols-rounded" style="font-size: 1.5rem;">arrow_back</span>';
            btnAnterior.style.cssText = 'background: #0066cc; color: white; border: none; border-radius: 6px; cursor: pointer; padding: 0.75rem; display: flex; align-items: center; justify-content: center; transition: background 0.2s; width: 50px; height: 50px;';
            btnAnterior.onmouseover = () => btnAnterior.style.background = '#0052a3';
            btnAnterior.onmouseout = () => btnAnterior.style.background = '#0066cc';
            btnAnterior.onclick = () => {
                const imagenesActuales = window.imagenesPrendaStorage.obtenerImagenes();
                if (imagenesActuales.length > 0) {
                    indiceActual = (indiceActual - 1 + imagenesActuales.length) % imagenesActuales.length;
                    actualizarImagen();
                    contador.textContent = (indiceActual + 1) + ' de ' + imagenesActuales.length;
                }
            };
            toolbar.appendChild(btnAnterior);
            
            // Botón eliminar
            const btnEliminar = document.createElement('button');
            btnEliminar.innerHTML = '<span class="material-symbols-rounded" style="font-size: 1.5rem;">delete</span>';
            btnEliminar.style.cssText = 'background: #ef4444; color: white; border: none; border-radius: 6px; cursor: pointer; padding: 0.75rem; display: flex; align-items: center; justify-content: center; transition: background 0.2s; width: 50px; height: 50px;';
            btnEliminar.onmouseover = () => btnEliminar.style.background = '#dc2626';
            btnEliminar.onmouseout = () => btnEliminar.style.background = '#ef4444';
            btnEliminar.onclick = () => {
                console.log('🗑️ [ELIMINAR] Click en botón eliminar');
                console.log('🗑️ [ELIMINAR] Índice actual:', indiceActual);
                console.log('🗑️ [ELIMINAR] Total imágenes:', window.imagenesPrendaStorage.obtenerImagenes().length);
                
                // Crear modal de confirmación
                const confirmModal = document.createElement('div');
                confirmModal.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 10002;';
                
                const confirmBox = document.createElement('div');
                confirmBox.style.cssText = 'background: white; border-radius: 12px; padding: 2rem; max-width: 400px; box-shadow: 0 10px 40px rgba(0,0,0,0.3);';
                
                const titulo = document.createElement('h3');
                titulo.textContent = '¿Eliminar esta imagen?';
                titulo.style.cssText = 'margin: 0 0 1rem 0; color: #1f2937; font-size: 1.25rem;';
                confirmBox.appendChild(titulo);
                
                const mensaje = document.createElement('p');
                mensaje.textContent = 'Esta acción no se puede deshacer.';
                mensaje.style.cssText = 'margin: 0 0 1.5rem 0; color: #6b7280; font-size: 0.95rem;';
                confirmBox.appendChild(mensaje);
                
                const botones = document.createElement('div');
                botones.style.cssText = 'display: flex; gap: 1rem; justify-content: flex-end;';
                
                const btnCancelar = document.createElement('button');
                btnCancelar.textContent = 'Cancelar';
                btnCancelar.style.cssText = 'background: #e5e7eb; color: #1f2937; border: none; border-radius: 6px; padding: 0.75rem 1.5rem; cursor: pointer; font-weight: 500; transition: background 0.2s;';
                btnCancelar.onmouseover = () => btnCancelar.style.background = '#d1d5db';
                btnCancelar.onmouseout = () => btnCancelar.style.background = '#e5e7eb';
                btnCancelar.onclick = () => {
                    console.log('❌ [ELIMINAR] Cancelado');
                    confirmModal.remove();
                };
                botones.appendChild(btnCancelar);
                
                const btnConfirmar = document.createElement('button');
                btnConfirmar.textContent = 'Eliminar';
                btnConfirmar.style.cssText = 'background: #ef4444; color: white; border: none; border-radius: 6px; padding: 0.75rem 1.5rem; cursor: pointer; font-weight: 500; transition: background 0.2s;';
                btnConfirmar.onmouseover = () => btnConfirmar.style.background = '#dc2626';
                btnConfirmar.onmouseout = () => btnConfirmar.style.background = '#ef4444';
                btnConfirmar.onclick = () => {
                    console.log('✅ [ELIMINAR] Confirmado - Eliminando imagen en índice:', indiceActual);
                    confirmModal.remove();
                    
                    window.imagenesPrendaStorage.eliminarImagen(indiceActual);
                    const imagenesRestantes = window.imagenesPrendaStorage.obtenerImagenes();
                    console.log('✅ [ELIMINAR] Imágenes restantes:', imagenesRestantes.length);
                    
                    if (imagenesRestantes.length === 0) {
                        console.log('✅ [ELIMINAR] Sin imágenes, cerrando galería');
                        modal.remove();
                        actualizarPreviewPrenda();
                        return;
                    }
                    
                    if (indiceActual >= imagenesRestantes.length) {
                        indiceActual = imagenesRestantes.length - 1;
                        console.log('✅ [ELIMINAR] Ajustando índice a:', indiceActual);
                    }
                    
                    actualizarImagen();
                    contador.textContent = (indiceActual + 1) + ' de ' + imagenesRestantes.length;
                    console.log('✅ [ELIMINAR] Galería actualizada');
                    
                    actualizarPreviewPrenda();
                };
                botones.appendChild(btnConfirmar);
                
                confirmBox.appendChild(botones);
                confirmModal.appendChild(confirmBox);
                document.body.appendChild(confirmModal);
            };
            toolbar.appendChild(btnEliminar);
            
            // Contador
            const contador = document.createElement('div');
            contador.style.cssText = 'color: white; font-size: 0.95rem; font-weight: 500; min-width: 80px; text-align: center;';
            contador.textContent = (indiceActual + 1) + ' de ' + window.imagenesPrendaStorage.obtenerImagenes().length;
            toolbar.appendChild(contador);
            
            // Botón siguiente
            const btnSiguiente = document.createElement('button');
            btnSiguiente.innerHTML = '<span class="material-symbols-rounded" style="font-size: 1.5rem;">arrow_forward</span>';
            btnSiguiente.style.cssText = 'background: #0066cc; color: white; border: none; border-radius: 6px; cursor: pointer; padding: 0.75rem; display: flex; align-items: center; justify-content: center; transition: background 0.2s; width: 50px; height: 50px;';
            btnSiguiente.onmouseover = () => btnSiguiente.style.background = '#0052a3';
            btnSiguiente.onmouseout = () => btnSiguiente.style.background = '#0066cc';
            btnSiguiente.onclick = () => {
                const imagenesActuales = window.imagenesPrendaStorage.obtenerImagenes();
                if (imagenesActuales.length > 0) {
                    indiceActual = (indiceActual + 1) % imagenesActuales.length;
                    actualizarImagen();
                    contador.textContent = (indiceActual + 1) + ' de ' + imagenesActuales.length;
                }
            };
            toolbar.appendChild(btnSiguiente);
            
            // Botón cerrar
            const btnCerrar = document.createElement('button');
            btnCerrar.innerHTML = '<span class="material-symbols-rounded" style="font-size: 1.5rem;">close</span>';
            btnCerrar.style.cssText = 'background: rgba(255,255,255,0.2); color: white; border: none; border-radius: 6px; cursor: pointer; padding: 0.75rem; display: flex; align-items: center; justify-content: center; transition: background 0.2s; width: 50px; height: 50px;';
            btnCerrar.onmouseover = () => btnCerrar.style.background = 'rgba(255,255,255,0.3)';
            btnCerrar.onmouseout = () => btnCerrar.style.background = 'rgba(255,255,255,0.2)';
            btnCerrar.onclick = () => {
                if (!modalClosed) {
                    modalClosed = true;
                    modal.remove();
                }
            };
            toolbar.appendChild(btnCerrar);
            
            container.appendChild(toolbar);
            modal.appendChild(container);
            document.body.appendChild(modal);
            
            // Soporte para navegación con teclas
            const manejarTeclas = function(e) {
                if (e.key === 'ArrowLeft') {
                    btnAnterior.click();
                } else if (e.key === 'ArrowRight') {
                    btnSiguiente.click();
                } else if (e.key === 'Escape') {
                    if (!modalClosed) {
                        modalClosed = true;
                        modal.remove();
                    }
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
            let modalClosed = false;
            
            const modal = document.createElement('div');
            modal.style.cssText = 'position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.95); display: flex; flex-direction: column; align-items: center; justify-content: center; z-index: 10000; padding: 0;';
            modal.onclick = function(e) {
                if (e.target === modal && !modalClosed) {
                    modalClosed = true;
                    modal.remove();
                }
            };
            
            const container = document.createElement('div');
            container.style.cssText = 'position: relative; display: flex; flex-direction: column; align-items: center; width: 100%; height: 100%; max-width: 100%; max-height: 100%;';
            
            // Contenedor de imagen - Ocupa casi toda la pantalla
            const imgContainer = document.createElement('div');
            imgContainer.style.cssText = 'flex: 1; display: flex; align-items: center; justify-content: center; position: relative; width: 100%; height: calc(100% - 120px); padding: 2rem;';
            
            const img = document.createElement('img');
            img.src = imagenes[indiceActual].data;
            img.style.cssText = 'width: 90%; height: 90%; border-radius: 8px; object-fit: contain; box-shadow: 0 20px 50px rgba(0,0,0,0.7);';
            imgContainer.appendChild(img);
            
            container.appendChild(imgContainer);
            
            // Barra de herramientas con controles
            const toolbar = document.createElement('div');
            toolbar.style.cssText = 'display: flex; justify-content: center; align-items: center; width: 100%; gap: 1rem; padding: 1.5rem; background: rgba(0,0,0,0.5);';
            
            // Botón anterior
            const btnAnterior = document.createElement('button');
            btnAnterior.innerHTML = '<span class="material-symbols-rounded" style="font-size: 1.5rem;">arrow_back</span>';
            btnAnterior.style.cssText = 'background: #0066cc; color: white; border: none; border-radius: 6px; cursor: pointer; padding: 0.75rem; display: flex; align-items: center; justify-content: center; transition: background 0.2s; width: 50px; height: 50px;';
            btnAnterior.onmouseover = () => btnAnterior.style.background = '#0052a3';
            btnAnterior.onmouseout = () => btnAnterior.style.background = '#0066cc';
            btnAnterior.onclick = () => {
                indiceActual = (indiceActual - 1 + imagenes.length) % imagenes.length;
                img.src = imagenes[indiceActual].data;
                contador.textContent = (indiceActual + 1) + ' de ' + imagenes.length;
            };
            toolbar.appendChild(btnAnterior);
            
            // Botón eliminar
            const btnEliminar = document.createElement('button');
            btnEliminar.innerHTML = '<span class="material-symbols-rounded" style="font-size: 1.5rem;">delete</span>';
            btnEliminar.style.cssText = 'background: #ef4444; color: white; border: none; border-radius: 6px; cursor: pointer; padding: 0.75rem; display: flex; align-items: center; justify-content: center; transition: background 0.2s; width: 50px; height: 50px;';
            btnEliminar.onmouseover = () => btnEliminar.style.background = '#dc2626';
            btnEliminar.onmouseout = () => btnEliminar.style.background = '#ef4444';
            btnEliminar.onclick = () => {
                // Crear modal de confirmación
                const confirmModal = document.createElement('div');
                confirmModal.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 10002;';
                
                const confirmBox = document.createElement('div');
                confirmBox.style.cssText = 'background: white; border-radius: 12px; padding: 2rem; max-width: 400px; box-shadow: 0 10px 40px rgba(0,0,0,0.3);';
                
                const titulo = document.createElement('h3');
                titulo.textContent = '¿Eliminar esta imagen?';
                titulo.style.cssText = 'margin: 0 0 1rem 0; color: #1f2937; font-size: 1.25rem;';
                confirmBox.appendChild(titulo);
                
                const mensaje = document.createElement('p');
                mensaje.textContent = 'Esta acción no se puede deshacer.';
                mensaje.style.cssText = 'margin: 0 0 1.5rem 0; color: #6b7280; font-size: 0.95rem;';
                confirmBox.appendChild(mensaje);
                
                const botones = document.createElement('div');
                botones.style.cssText = 'display: flex; gap: 1rem; justify-content: flex-end;';
                
                const btnCancelar = document.createElement('button');
                btnCancelar.textContent = 'Cancelar';
                btnCancelar.style.cssText = 'background: #e5e7eb; color: #1f2937; border: none; border-radius: 6px; padding: 0.75rem 1.5rem; cursor: pointer; font-weight: 500; transition: background 0.2s;';
                btnCancelar.onmouseover = () => btnCancelar.style.background = '#d1d5db';
                btnCancelar.onmouseout = () => btnCancelar.style.background = '#e5e7eb';
                btnCancelar.onclick = () => confirmModal.remove();
                botones.appendChild(btnCancelar);
                
                const btnConfirmar = document.createElement('button');
                btnConfirmar.textContent = 'Eliminar';
                btnConfirmar.style.cssText = 'background: #ef4444; color: white; border: none; border-radius: 6px; padding: 0.75rem 1.5rem; cursor: pointer; font-weight: 500; transition: background 0.2s;';
                btnConfirmar.onmouseover = () => btnConfirmar.style.background = '#dc2626';
                btnConfirmar.onmouseout = () => btnConfirmar.style.background = '#ef4444';
                btnConfirmar.onclick = () => {
                    confirmModal.remove();
                    
                    // Encontrar y eliminar la imagen del array
                    const imagenAEliminar = imagenes[indiceActual];
                    const indexEnArray = imagenes.indexOf(imagenAEliminar);
                    if (indexEnArray > -1) {
                        imagenes.splice(indexEnArray, 1);
                    }
                    
                    if (imagenes.length === 0) {
                        modal.remove();
                        actualizarPreviewTela();
                        return;
                    }
                    
                    if (indiceActual >= imagenes.length) {
                        indiceActual = imagenes.length - 1;
                    }
                    
                    img.src = imagenes[indiceActual].data;
                    contador.textContent = (indiceActual + 1) + ' de ' + imagenes.length;
                    
                    actualizarPreviewTela();
                };
                botones.appendChild(btnConfirmar);
                
                confirmBox.appendChild(botones);
                confirmModal.appendChild(confirmBox);
                document.body.appendChild(confirmModal);
            };
            toolbar.appendChild(btnEliminar);
            
            // Contador
            const contador = document.createElement('div');
            contador.style.cssText = 'color: white; font-size: 0.95rem; font-weight: 500; min-width: 80px; text-align: center;';
            contador.textContent = (indiceActual + 1) + ' de ' + imagenes.length;
            toolbar.appendChild(contador);
            
            // Botón siguiente
            const btnSiguiente = document.createElement('button');
            btnSiguiente.innerHTML = '<span class="material-symbols-rounded" style="font-size: 1.5rem;">arrow_forward</span>';
            btnSiguiente.style.cssText = 'background: #0066cc; color: white; border: none; border-radius: 6px; cursor: pointer; padding: 0.75rem; display: flex; align-items: center; justify-content: center; transition: background 0.2s; width: 50px; height: 50px;';
            btnSiguiente.onmouseover = () => btnSiguiente.style.background = '#0052a3';
            btnSiguiente.onmouseout = () => btnSiguiente.style.background = '#0066cc';
            btnSiguiente.onclick = () => {
                indiceActual = (indiceActual + 1) % imagenes.length;
                img.src = imagenes[indiceActual].data;
                contador.textContent = (indiceActual + 1) + ' de ' + imagenes.length;
            };
            toolbar.appendChild(btnSiguiente);
            
            // Botón cerrar
            const btnCerrar = document.createElement('button');
            btnCerrar.innerHTML = '<span class="material-symbols-rounded" style="font-size: 1.5rem;">close</span>';
            btnCerrar.style.cssText = 'background: rgba(255,255,255,0.2); color: white; border: none; border-radius: 6px; cursor: pointer; padding: 0.75rem; display: flex; align-items: center; justify-content: center; transition: background 0.2s; width: 50px; height: 50px;';
            btnCerrar.onmouseover = () => btnCerrar.style.background = 'rgba(255,255,255,0.3)';
            btnCerrar.onmouseout = () => btnCerrar.style.background = 'rgba(255,255,255,0.2)';
            btnCerrar.onclick = () => {
                if (!modalClosed) {
                    modalClosed = true;
                    modal.remove();
                }
            };
            toolbar.appendChild(btnCerrar);
            
            container.appendChild(toolbar);
            modal.appendChild(container);
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
            
            // Mostrar botones
            const btnSubmit = document.getElementById('btn-submit');
            btnSubmit.textContent = '✓ Crear Pedido';
            btnSubmit.style.display = 'block';
            
            const btnVistaPrevio = document.getElementById('btn-vista-previa');
            btnVistaPrevio.style.display = 'block';

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
            
            // Función para abrir selector de archivos
            window.abrirSelectorPrendas = function() {
                const inputFotos = document.getElementById('nueva-prenda-foto-input');
                if (inputFotos) {
                    inputFotos.click();
                }
            };
            
            // ========== MODAL DE PRENDA NUEVA ==========
            window.abrirModalPrendaNueva = function() {
                const modal = document.getElementById('modal-agregar-prenda-nueva');
                if (modal) {
                    modal.style.display = 'flex';
                    limpiarFormularioPrendaNueva();
                    configurarEventosFormulario();
                    
                    // Registrar listener para el preview
                    const preview = document.getElementById('nueva-prenda-foto-preview');
                    if (preview) {
                        // Remover listener anterior si existe
                        preview.removeEventListener('click', abrirGaleriaOSelectorPrenda);
                        // Registrar nuevo listener
                        preview.addEventListener('click', abrirGaleriaOSelectorPrenda);
                    }
                    
                    // Registrar listener para el botón agregar
                    const btnAgregar = document.getElementById('nueva-prenda-foto-btn');
                    if (btnAgregar) {
                        // Remover listener anterior si existe
                        btnAgregar.removeEventListener('click', abrirSelectorPrendas);
                        // Registrar nuevo listener
                        btnAgregar.addEventListener('click', abrirSelectorPrendas);
                    }
                }
            };
            
            // ========== GESTIÓN DE MÚLTIPLES TELAS ==========
            window.telasAgregadas = [];
            window.imagenesTelaModalNueva = [];
            
            window.agregarTelaNueva = function() {
                const color = document.getElementById('nueva-prenda-color').value.trim().toUpperCase();
                const tela = document.getElementById('nueva-prenda-tela').value.trim().toUpperCase();
                const referencia = document.getElementById('nueva-prenda-referencia').value.trim().toUpperCase();
                
                // Validación más específica
                if (!color) {
                    alert('Por favor completa el campo Color');
                    document.getElementById('nueva-prenda-color').focus();
                    return;
                }
                if (!tela) {
                    alert('Por favor completa el campo Tela');
                    document.getElementById('nueva-prenda-tela').focus();
                    return;
                }
                if (!referencia) {
                    alert('Por favor completa el campo Referencia');
                    document.getElementById('nueva-prenda-referencia').focus();
                    return;
                }
                
                // Obtener imágenes del storage temporal
                const imagenesTemporales = window.imagenesTelaStorage.obtenerImagenes();
                
                // Agregar a la lista con las imágenes del storage temporal
                window.telasAgregadas.push({ 
                    color, 
                    tela, 
                    referencia,
                    imagenes: [...imagenesTemporales]  // Copiar imágenes del storage temporal
                });
                
                // Limpiar inputs
                document.getElementById('nueva-prenda-color').value = '';
                document.getElementById('nueva-prenda-tela').value = '';
                document.getElementById('nueva-prenda-referencia').value = '';
                
                // Limpiar storage de imágenes de tela
                window.imagenesTelaStorage.limpiar();
                
                // Limpiar preview de imágenes en la celda
                const tbody = document.getElementById('tbody-telas');
                const primeraFila = tbody.querySelector('tr');
                if (primeraFila) {
                    const celdaImagen = primeraFila.querySelector('td:nth-child(4)');
                    if (celdaImagen) {
                        const previousPreview = celdaImagen.querySelector('.imagen-preview-tela-temp');
                        if (previousPreview) {
                            previousPreview.remove();
                        }
                    }
                }
                
                const previewTela = document.getElementById('nueva-prenda-tela-preview');
                if (previewTela) {
                    previewTela.innerHTML = '';
                }
                
                // Actualizar tabla
                actualizarTablaTelas();
            };
            
            window.actualizarTablaTelas = function() {
                const tbody = document.getElementById('tbody-telas');
                
                // Limpiar tbody excepto la fila de inputs
                const filas = Array.from(tbody.querySelectorAll('tr'));
                filas.forEach((fila, index) => {
                    if (index > 0) {  // Mantener la primera fila (inputs)
                        fila.remove();
                    }
                });
                
                // Agregar filas con los datos
                window.telasAgregadas.forEach((telaData, index) => {
                    const tr = document.createElement('tr');
                    tr.style.cssText = 'border-bottom: 1px solid #e5e7eb;';
                    
                    // Crear celda de imágenes
                    let imagenHTML = '';
                    if (telaData.imagenes && telaData.imagenes.length > 0) {
                        // Guardar las imágenes en un objeto global temporal
                        window[`telaImagenes_${index}`] = telaData.imagenes;
                        imagenHTML = `
                            <div style="display: flex; gap: 0.5rem; align-items: center;">
                                <img src="${telaData.imagenes[0].data}" style="width: 40px; height: 40px; border-radius: 4px; object-fit: cover; cursor: pointer;" onclick="mostrarGaleriaImagenes(window['telaImagenes_${index}'], 0)">
                                ${telaData.imagenes.length > 1 ? `<span style="background: #0066cc; color: white; border-radius: 50%; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: bold;">+${telaData.imagenes.length - 1}</span>` : ''}
                            </div>
                        `;
                    }
                    
                    tr.innerHTML = `
                        <td style="padding: 0.5rem;">${telaData.color}</td>
                        <td style="padding: 0.5rem;">${telaData.tela}</td>
                        <td style="padding: 0.5rem;">${telaData.referencia}</td>
                        <td style="padding: 0.5rem; text-align: center;">
                            ${imagenHTML}
                        </td>
                        <td style="padding: 0.5rem; text-align: center;">
                            <button type="button" onclick="eliminarTela(${index})" class="btn btn-sm" style="background: #ef4444; color: white; padding: 0.25rem 0.5rem; font-size: 0.75rem; border: none; cursor: pointer;">
                                <span class="material-symbols-rounded" style="font-size: 1rem;">delete</span>
                            </button>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });
            };
            
            window.eliminarTela = function(index) {
                // Crear modal de confirmación
                const confirmModal = document.createElement('div');
                confirmModal.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 10002;';
                
                const confirmBox = document.createElement('div');
                confirmBox.style.cssText = 'background: white; border-radius: 12px; padding: 2rem; max-width: 400px; box-shadow: 0 10px 40px rgba(0,0,0,0.3);';
                
                const titulo = document.createElement('h3');
                titulo.textContent = '¿Eliminar esta tela?';
                titulo.style.cssText = 'margin: 0 0 1rem 0; color: #1f2937; font-size: 1.25rem;';
                confirmBox.appendChild(titulo);
                
                const mensaje = document.createElement('p');
                mensaje.textContent = 'Esta acción no se puede deshacer.';
                mensaje.style.cssText = 'margin: 0 0 1.5rem 0; color: #6b7280; font-size: 0.95rem;';
                confirmBox.appendChild(mensaje);
                
                const botones = document.createElement('div');
                botones.style.cssText = 'display: flex; gap: 1rem; justify-content: flex-end;';
                
                const btnCancelar = document.createElement('button');
                btnCancelar.textContent = 'Cancelar';
                btnCancelar.style.cssText = 'background: #e5e7eb; color: #1f2937; border: none; border-radius: 6px; padding: 0.75rem 1.5rem; cursor: pointer; font-weight: 500; transition: background 0.2s;';
                btnCancelar.onmouseover = () => btnCancelar.style.background = '#d1d5db';
                btnCancelar.onmouseout = () => btnCancelar.style.background = '#e5e7eb';
                btnCancelar.onclick = () => confirmModal.remove();
                botones.appendChild(btnCancelar);
                
                const btnConfirmar = document.createElement('button');
                btnConfirmar.textContent = 'Eliminar';
                btnConfirmar.style.cssText = 'background: #ef4444; color: white; border: none; border-radius: 6px; padding: 0.75rem 1.5rem; cursor: pointer; font-weight: 500; transition: background 0.2s;';
                btnConfirmar.onmouseover = () => btnConfirmar.style.background = '#dc2626';
                btnConfirmar.onmouseout = () => btnConfirmar.style.background = '#ef4444';
                btnConfirmar.onclick = () => {
                    confirmModal.remove();
                    window.telasAgregadas.splice(index, 1);
                    actualizarTablaTelas();
                };
                botones.appendChild(btnConfirmar);
                
                confirmBox.appendChild(botones);
                confirmModal.appendChild(confirmBox);
                document.body.appendChild(confirmModal);
            };
            
            window.manejarImagenTela = function(input) {
                if (!input.files || input.files.length === 0) {
                    return;
                }
                
                const file = input.files[0];
                
                // Validar que sea imagen
                if (!file.type.startsWith('image/')) {
                    alert('Por favor selecciona una imagen válida');
                    return;
                }
                
                // Verificar límite de 3 imágenes en el storage temporal
                if (window.imagenesTelaStorage.obtenerImagenes().length >= 3) {
                    alert('Máximo 3 imágenes por tela');
                    return;
                }
                
                // Crear reader para la imagen
                const reader = new FileReader();
                reader.onload = function(e) {
                    // Guardar en el storage temporal
                    window.imagenesTelaStorage.agregarImagen(file)
                        .then(() => {
                            // Actualizar preview
                            actualizarPreviewTela();
                            
                            // Limpiar input
                            input.value = '';
                        })
                        .catch(err => {
                            alert(err.message);
                        });
                };
                reader.readAsDataURL(file);
            };
            
            window.mostrarPreviewImagenTela = function(dataUrl) {
                // Encontrar la celda de imagen en la fila de inputs
                const tbody = document.getElementById('tbody-telas');
                const primeraFila = tbody.querySelector('tr');
                if (!primeraFila) return;
                
                const celdaImagen = primeraFila.querySelector('td:nth-child(4)');
                if (!celdaImagen) return;
                
                // Limpiar previews anteriores
                const previousPreview = celdaImagen.querySelector('.imagen-preview-tela');
                if (previousPreview) {
                    previousPreview.remove();
                }
                
                // Crear contenedor para la galería
                const previewDiv = document.createElement('div');
                previewDiv.className = 'imagen-preview-tela';
                previewDiv.style.cssText = 'display: flex; gap: 0.5rem; align-items: center; margin-top: 0.5rem; position: relative;';
                
                // Contenedor de imagen
                const imgContainer = document.createElement('div');
                imgContainer.style.cssText = 'position: relative; display: inline-block;';
                
                // Imagen principal
                const img = document.createElement('img');
                img.src = dataUrl;
                img.style.cssText = 'width: 50px; height: 50px; border-radius: 4px; object-fit: cover; border: 2px solid #0066cc; cursor: pointer;';
                img.title = 'Click para ver completa';
                img.onclick = function() {
                    // Encontrar índice de la imagen actual
                    let indiceActual = window.imagenesTelaModalNueva.length - 1;
                    
                    // Abrir imagen en modal grande
                    const modal = document.createElement('div');
                    modal.style.cssText = 'position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.95); display: flex; flex-direction: column; align-items: center; justify-content: center; z-index: 9999; padding: 0;';
                    
                    // Contenedor principal
                    const container = document.createElement('div');
                    container.style.cssText = 'position: relative; display: flex; flex-direction: column; align-items: center; width: 100%; height: 100%; max-width: 100%; max-height: 100%;';
                    
                    // Contenedor de imagen - Ocupa casi toda la pantalla
                    const imgContainer = document.createElement('div');
                    imgContainer.style.cssText = 'flex: 1; display: flex; align-items: center; justify-content: center; position: relative; width: 100%; height: calc(100% - 120px); padding: 2rem;';
                    
                    // Imagen modal
                    const imgModal = document.createElement('img');
                    imgModal.src = dataUrl;
                    imgModal.style.cssText = 'max-width: 95vw; max-height: 80vh; border-radius: 8px; object-fit: contain; box-shadow: 0 20px 50px rgba(0,0,0,0.7);';
                    
                    imgContainer.appendChild(imgModal);
                    
                    // Barra de herramientas con controles
                    const toolbar = document.createElement('div');
                    toolbar.style.cssText = 'display: flex; justify-content: center; align-items: center; width: 100%; gap: 1rem; padding: 1.5rem; background: rgba(0,0,0,0.5);';
                    
                    // Botón anterior
                    const btnAnterior = document.createElement('button');
                    btnAnterior.innerHTML = '<span class="material-symbols-rounded" style="font-size: 1.5rem;">arrow_back</span>';
                    btnAnterior.style.cssText = 'background: #0066cc; color: white; border: none; border-radius: 6px; cursor: pointer; padding: 0.75rem; display: flex; align-items: center; justify-content: center; transition: background 0.2s; width: 50px; height: 50px;';
                    btnAnterior.onmouseover = () => btnAnterior.style.background = '#0052a3';
                    btnAnterior.onmouseout = () => btnAnterior.style.background = '#0066cc';
                    btnAnterior.onclick = () => {
                        indiceActual = (indiceActual - 1 + window.imagenesTelaModalNueva.length) % window.imagenesTelaModalNueva.length;
                        imgModal.src = window.imagenesTelaModalNueva[indiceActual].data;
                        contador.textContent = (indiceActual + 1) + ' de ' + window.imagenesTelaModalNueva.length;
                    };
                    toolbar.appendChild(btnAnterior);
                    
                    // Botón eliminar
                    const btnEliminar = document.createElement('button');
                    btnEliminar.innerHTML = '<span class="material-symbols-rounded" style="font-size: 1.5rem;">delete</span>';
                    btnEliminar.style.cssText = 'background: #ef4444; color: white; border: none; border-radius: 6px; cursor: pointer; padding: 0.75rem; display: flex; align-items: center; justify-content: center; transition: background 0.2s; width: 50px; height: 50px;';
                    btnEliminar.onmouseover = () => btnEliminar.style.background = '#dc2626';
                    btnEliminar.onmouseout = () => btnEliminar.style.background = '#ef4444';
                    btnEliminar.onclick = () => {
                        if (confirm('¿Eliminar esta imagen?')) {
                            window.imagenesTelaModalNueva.splice(indiceActual, 1);
                            console.log('🗑️ Imagen eliminada. Quedan:', window.imagenesTelaModalNueva.length);
                            
                            if (window.imagenesTelaModalNueva.length === 0) {
                                modal.remove();
                                mostrarPreviewImagenTela(undefined);
                                return;
                            }
                            
                            if (indiceActual >= window.imagenesTelaModalNueva.length) {
                                indiceActual = window.imagenesTelaModalNueva.length - 1;
                            }
                            
                            imgModal.src = window.imagenesTelaModalNueva[indiceActual].data;
                            contador.textContent = (indiceActual + 1) + ' de ' + window.imagenesTelaModalNueva.length;
                            btnEliminar.style.display = window.imagenesTelaModalNueva.length > 1 ? 'flex' : 'none';
                        }
                    };
                    toolbar.appendChild(btnEliminar);
                    
                    // Contador
                    const contador = document.createElement('div');
                    contador.style.cssText = 'color: white; font-size: 0.95rem; font-weight: 500; min-width: 80px; text-align: center;';
                    contador.textContent = (indiceActual + 1) + ' de ' + window.imagenesTelaModalNueva.length;
                    toolbar.appendChild(contador);
                    
                    // Botón siguiente
                    const btnSiguiente = document.createElement('button');
                    btnSiguiente.innerHTML = '<span class="material-symbols-rounded" style="font-size: 1.5rem;">arrow_forward</span>';
                    btnSiguiente.style.cssText = 'background: #0066cc; color: white; border: none; border-radius: 6px; cursor: pointer; padding: 0.75rem; display: flex; align-items: center; justify-content: center; transition: background 0.2s; width: 50px; height: 50px;';
                    btnSiguiente.onmouseover = () => btnSiguiente.style.background = '#0052a3';
                    btnSiguiente.onmouseout = () => btnSiguiente.style.background = '#0066cc';
                    btnSiguiente.onclick = () => {
                        indiceActual = (indiceActual + 1) % window.imagenesTelaModalNueva.length;
                        imgModal.src = window.imagenesTelaModalNueva[indiceActual].data;
                        contador.textContent = (indiceActual + 1) + ' de ' + window.imagenesTelaModalNueva.length;
                    };
                    toolbar.appendChild(btnSiguiente);
                    
                    // Botón cerrar
                    const btnCerrar = document.createElement('button');
                    btnCerrar.innerHTML = '<span class="material-symbols-rounded" style="font-size: 1.5rem;">close</span>';
                    btnCerrar.style.cssText = 'background: rgba(255,255,255,0.2); color: white; border: none; border-radius: 6px; cursor: pointer; padding: 0.75rem; display: flex; align-items: center; justify-content: center; transition: background 0.2s; width: 50px; height: 50px;';
                    btnCerrar.onmouseover = () => btnCerrar.style.background = 'rgba(255,255,255,0.3)';
                    btnCerrar.onmouseout = () => btnCerrar.style.background = 'rgba(255,255,255,0.2)';
                    btnCerrar.onclick = () => modal.remove();
                    toolbar.appendChild(btnCerrar);
                    
                    // Ocultar botón eliminar si solo hay 1 imagen
                    btnEliminar.style.display = window.imagenesTelaModalNueva.length > 1 ? 'flex' : 'none';
                    
                    container.appendChild(imgContainer);
                    container.appendChild(toolbar);
                    modal.appendChild(container);
                    
                    // Cerrar al hacer clic en el fondo
                    modal.onclick = (e) => {
                        if (e.target === modal) {
                            modal.remove();
                        }
                    };
                    
                    document.body.appendChild(modal);
                };
                
                imgContainer.appendChild(img);
                
                // Mostrar contador de imágenes si hay más de una
                if (window.imagenesTelaModalNueva.length > 1) {
                    const contador = document.createElement('div');
                    const restantes = window.imagenesTelaModalNueva.length - 1;
                    contador.style.cssText = 'position: absolute; bottom: 0; right: 0; background: #0066cc; color: white; border-radius: 4px; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 600; border: 2px solid white;';
                    contador.textContent = restantes <= 2 ? `+${restantes}` : '+2';
                    contador.title = `${restantes} imagen${restantes > 1 ? 'es' : ''} más`;
                    imgContainer.appendChild(contador);
                }
                
                previewDiv.appendChild(imgContainer);
                
                // Insertar después del botón
                const btn = celdaImagen.querySelector('button');
                if (btn && btn.parentNode) {
                    btn.parentNode.insertBefore(previewDiv, btn.nextSibling);
                } else {
                    celdaImagen.appendChild(previewDiv);
                }
            };
            
            function limpiarFormularioPrendaNueva() {
                document.getElementById('nueva-prenda-nombre').value = '';
                document.getElementById('nueva-prenda-descripcion').value = '';
                document.getElementById('nueva-prenda-color').value = '';
                document.getElementById('nueva-prenda-tela').value = '';
                document.getElementById('nueva-prenda-referencia').value = '';
                
                // Limpiar telas agregadas
                window.telasAgregadas = [];
                actualizarTablaTelas();
                
                // Limpiar storage de imágenes
                if (window.imagenesPrendaStorage) {
                    window.imagenesPrendaStorage.limpiar();
                }
                if (window.imagenesTelaStorage) {
                    window.imagenesTelaStorage.limpiar();
                }
                
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
                document.querySelectorAll('#manga-input, #manga-obs, #bolsillos-input, #broche-input, #broche-obs').forEach(input => {
                    if (input) {
                        input.value = '';
                        input.disabled = true;
                        input.style.opacity = '0.5';
                    }
                });
                
                // Reset origen
                document.getElementById('nueva-prenda-origen-select').value = 'bodega';
            }
            
            // Variables globales para reflectivo
            window.datosReflectivo = {
                imagenes: [],
                ubicaciones: [],
                aplicarATodas: true,
                tallasPorGenero: {
                    dama: [],
                    caballero: []
                }
            };
            
            window.abrirModalReflectivo = function() {
                const modal = document.createElement('div');
                modal.id = 'modal-reflectivo';
                modal.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 10001;';
                
                const container = document.createElement('div');
                container.style.cssText = 'background: white; border-radius: 12px; max-width: 500px; width: 90%; max-height: 80vh; overflow-y: auto; box-shadow: 0 20px 50px rgba(0,0,0,0.3);';
                
                // Header
                const header = document.createElement('div');
                header.style.cssText = 'background: linear-gradient(135deg, #0066cc 0%, #0052a3 100%); color: white; padding: 1.5rem; border-radius: 12px 12px 0 0; display: flex; align-items: center; justify-content: space-between; gap: 0.75rem;';
                
                const headerContent = document.createElement('div');
                headerContent.style.cssText = 'display: flex; align-items: center; gap: 0.75rem;';
                headerContent.innerHTML = '<span class="material-symbols-rounded" style="font-size: 1.5rem;">light_mode</span><h2 style="margin: 0; font-size: 1.25rem;">Configurar Reflectivo</h2>';
                header.appendChild(headerContent);
                
                const btnCerrarHeader = document.createElement('button');
                btnCerrarHeader.innerHTML = '<span class="material-symbols-rounded" style="font-size: 1.5rem;">close</span>';
                btnCerrarHeader.style.cssText = 'background: transparent; color: white; border: none; cursor: pointer; padding: 0; display: flex; align-items: center; justify-content: center; width: 40px; height: 40px;';
                btnCerrarHeader.onclick = () => cerrarModalReflectivo();
                header.appendChild(btnCerrarHeader);
                
                container.appendChild(header);
                
                // Content
                const content = document.createElement('div');
                content.style.cssText = 'padding: 1.5rem; display: flex; flex-direction: column; gap: 1.5rem;';
                
                // Sección 1: Imágenes
                const seccionImagenes = document.createElement('div');
                seccionImagenes.innerHTML = `
                    <div>
                        <h3 style="margin: 0 0 0.75rem 0; color: #1f2937; font-size: 1rem;">Imágenes (Máximo 3)</h3>
                        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; margin-bottom: 0.75rem;" id="reflectivo-imagenes-preview"></div>
                        <button type="button" onclick="document.getElementById('reflectivo-img-input').click()" class="btn btn-primary" style="padding: 0.75rem 1rem; font-size: 0.875rem;">
                            <span class="material-symbols-rounded" style="margin-right: 0.5rem; font-size: 1rem;">image</span>Agregar Imagen
                        </button>
                        <input type="file" id="reflectivo-img-input" accept="image/*" style="display: none;" onchange="manejarImagenReflectivo(this)">
                    </div>
                `;
                content.appendChild(seccionImagenes);
                
                // Sección 2: Ubicaciones
                const seccionUbicaciones = document.createElement('div');
                seccionUbicaciones.innerHTML = `
                    <div>
                        <h3 style="margin: 0 0 0.75rem 0; color: #1f2937; font-size: 1rem;">Ubicaciones</h3>
                        <div style="display: flex; gap: 0.5rem; margin-bottom: 0.75rem;">
                            <input type="text" id="reflectivo-ubicacion-input" placeholder="Ej: Pecho, Espalda..." style="flex: 1; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.875rem;">
                            <button type="button" onclick="agregarUbicacionReflectivo()" class="btn btn-primary" style="padding: 0.75rem 1rem; font-size: 0.875rem; white-space: nowrap;">
                                <span class="material-symbols-rounded" style="font-size: 1rem;">add</span>
                            </button>
                        </div>
                        <div id="reflectivo-ubicaciones-lista" style="display: flex; flex-direction: column; gap: 0.5rem;"></div>
                    </div>
                `;
                content.appendChild(seccionUbicaciones);
                
                // Sección 3: Tallas
                const seccionTallas = document.createElement('div');
                seccionTallas.innerHTML = `
                    <div>
                        <h3 style="margin: 0 0 0.75rem 0; color: #1f2937; font-size: 1rem;">Aplicar a Tallas</h3>
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; margin-bottom: 1rem;">
                            <input type="checkbox" id="reflectivo-aplicar-todas" checked style="width: 18px; height: 18px; cursor: pointer;">
                            <span style="font-size: 0.875rem; color: #1f2937;">Aplicar a todas las tallas</span>
                        </label>
                        <button type="button" id="reflectivo-btn-editar-tallas" onclick="abrirEditorTallasReflectivo()" class="btn btn-primary" style="padding: 0.75rem 1rem; font-size: 0.875rem; display: none;">
                            <span class="material-symbols-rounded" style="margin-right: 0.5rem;">edit</span>Editar Tallas
                        </button>
                        <div id="reflectivo-tallas-seleccionadas" style="display: none; margin-top: 1rem;"></div>
                    </div>
                `;
                content.appendChild(seccionTallas);
                
                // Agregar event listener al checkbox
                setTimeout(() => {
                    const checkbox = document.getElementById('reflectivo-aplicar-todas');
                    const btnEditar = document.getElementById('reflectivo-btn-editar-tallas');
                    if (checkbox && btnEditar) {
                        checkbox.addEventListener('change', function() {
                            btnEditar.style.display = this.checked ? 'none' : 'block';
                            const tallasCont = document.getElementById('reflectivo-tallas-seleccionadas');
                            if (tallasCont) {
                                tallasCont.style.display = this.checked ? 'none' : 'block';
                            }
                        });
                    }
                }, 100);
                
                // Sección 4: Observaciones
                const seccionObservaciones = document.createElement('div');
                seccionObservaciones.innerHTML = `
                    <div>
                        <h3 style="margin: 0 0 0.75rem 0; color: #1f2937; font-size: 1rem;">Observaciones</h3>
                        <textarea id="reflectivo-observaciones" placeholder="Agregar observaciones..." style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.875rem; font-family: inherit; resize: vertical; min-height: 80px;"></textarea>
                    </div>
                `;
                content.appendChild(seccionObservaciones);
                
                container.appendChild(content);
                
                // Footer
                const footer = document.createElement('div');
                footer.style.cssText = 'display: flex; gap: 1rem; justify-content: flex-end; padding: 1.5rem; border-top: 1px solid #e5e7eb;';
                
                const btnCancelar = document.createElement('button');
                btnCancelar.textContent = 'Cancelar';
                btnCancelar.style.cssText = 'background: #e5e7eb; color: #1f2937; border: none; border-radius: 6px; padding: 0.75rem 1.5rem; cursor: pointer; font-weight: 500; font-size: 0.875rem;';
                btnCancelar.onclick = () => cerrarModalReflectivo();
                footer.appendChild(btnCancelar);
                
                const btnGuardar = document.createElement('button');
                btnGuardar.textContent = 'Guardar';
                btnGuardar.style.cssText = 'background: #0066cc; color: white; border: none; border-radius: 6px; padding: 0.75rem 1.5rem; cursor: pointer; font-weight: 500; font-size: 0.875rem;';
                btnGuardar.onclick = () => guardarConfiguracionReflectivo();
                footer.appendChild(btnGuardar);
                
                container.appendChild(footer);
                modal.appendChild(container);
                document.body.appendChild(modal);
            };
            
            window.cerrarModalReflectivo = function() {
                const modal = document.getElementById('modal-reflectivo');
                if (modal) {
                    modal.remove();
                }
                // Desmarcar checkbox si se cancela
                document.getElementById('checkbox-reflectivo').checked = false;
            };
            
            window.manejarImagenReflectivo = function(input) {
                if (!input.files || input.files.length === 0) return;
                
                if (window.datosReflectivo.imagenes.length >= 3) {
                    alert('Máximo 3 imágenes');
                    return;
                }
                
                const file = input.files[0];
                if (!file.type.startsWith('image/')) {
                    alert('Por favor selecciona una imagen válida');
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    window.datosReflectivo.imagenes.push({
                        nombre: file.name,
                        data: e.target.result
                    });
                    actualizarPreviewImagenesReflectivo();
                    input.value = '';
                };
                reader.readAsDataURL(file);
            };
            
            window.actualizarPreviewImagenesReflectivo = function() {
                const preview = document.getElementById('reflectivo-imagenes-preview');
                if (!preview) {
                    console.log('❌ Preview element not found');
                    return;
                }
                
                console.log('📸 Actualizando preview con', window.datosReflectivo.imagenes.length, 'imágenes');
                
                preview.innerHTML = '';
                window.datosReflectivo.imagenes.forEach((img, index) => {
                    const imgElement = document.createElement('img');
                    imgElement.src = img.data;
                    imgElement.style.cssText = 'width: 60px; height: 60px; border-radius: 6px; object-fit: cover; border: 2px solid #0066cc; cursor: pointer;';
                    imgElement.title = `Imagen ${index + 1}`;
                    imgElement.onclick = () => {
                        if (confirm('¿Eliminar esta imagen?')) {
                            window.datosReflectivo.imagenes.splice(index, 1);
                            actualizarPreviewImagenesReflectivo();
                        }
                    };
                    preview.appendChild(imgElement);
                });
            };
            
            // Variables para reflectivo - tallas
            window.reflectivoTallasSeleccionadas = {
                dama: { tallas: [], tipo: null },
                caballero: { tallas: [], tipo: null }
            };
            
            window.seleccionarGeneroReflectivo = function(genero) {
                const btn = document.getElementById(`reflectivo-btn-genero-${genero}`);
                if (!btn) return;
                
                const isSelected = btn.dataset.selected === 'true';
                
                if (isSelected) {
                    btn.dataset.selected = 'false';
                    btn.style.borderColor = '#d1d5db';
                    btn.style.background = 'white';
                    btn.style.color = '#1f2937';
                    window.reflectivoTallasSeleccionadas[genero].tallas = [];
                    window.reflectivoTallasSeleccionadas[genero].tipo = null;
                } else {
                    btn.dataset.selected = 'true';
                    btn.style.borderColor = '#0066cc';
                    btn.style.background = '#0066cc';
                    btn.style.color = 'white';
                }
                
                // Mostrar/ocultar selector de tipo de talla
                const container = document.getElementById('reflectivo-tipo-talla-container');
                const btnDama = document.getElementById('reflectivo-btn-genero-dama');
                const btnCaballero = document.getElementById('reflectivo-btn-genero-caballero');
                
                if (!container || !btnDama || !btnCaballero) return;
                
                const dama = btnDama.dataset.selected === 'true';
                const caballero = btnCaballero.dataset.selected === 'true';
                
                if (dama || caballero) {
                    container.style.display = 'block';
                    actualizarTallasReflectivo();
                } else {
                    container.style.display = 'none';
                    const grid = document.getElementById('reflectivo-tallas-grid');
                    const tabla = document.getElementById('reflectivo-tallas-tabla-container');
                    if (grid) grid.innerHTML = '';
                    if (tabla) tabla.style.display = 'none';
                }
            };
            
            window.actualizarTallasReflectivo = function() {
                const grid = document.getElementById('reflectivo-tallas-grid');
                if (!grid) return;
                
                grid.innerHTML = '';
                
                const tipoSelect = document.getElementById('reflectivo-tipo-talla');
                if (!tipoSelect) return;
                
                const tipo = tipoSelect.value;
                const btnDama = document.getElementById('reflectivo-btn-genero-dama');
                const btnCaballero = document.getElementById('reflectivo-btn-genero-caballero');
                
                if (!btnDama || !btnCaballero) return;
                
                const dama = btnDama.dataset.selected === 'true';
                const caballero = btnCaballero.dataset.selected === 'true';
                
                const tallas = tipo === 'letra' 
                    ? ['XS', 'S', 'M', 'L', 'XL', 'XXL']
                    : ['28', '30', '32', '34', '36', '38', '40', '42', '44', '46', '48', '50'];
                
                tallas.forEach(talla => {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.textContent = talla;
                    btn.style.cssText = 'padding: 0.75rem; border: 2px solid #d1d5db; background: white; border-radius: 6px; cursor: pointer; font-weight: 500; color: #1f2937; transition: all 0.2s;';
                    btn.onclick = () => agregarTallaReflectivo(talla, tipo, btn);
                    grid.appendChild(btn);
                });
            };
            
            window.agregarTallaReflectivo = function(talla, tipo, btn) {
                const dama = document.getElementById('reflectivo-btn-genero-dama').dataset.selected === 'true';
                const caballero = document.getElementById('reflectivo-btn-genero-caballero').dataset.selected === 'true';
                
                if (dama) {
                    if (!window.reflectivoTallasSeleccionadas.dama.tallas.includes(talla)) {
                        window.reflectivoTallasSeleccionadas.dama.tallas.push(talla);
                        window.reflectivoTallasSeleccionadas.dama.tipo = tipo;
                    }
                }
                
                if (caballero) {
                    if (!window.reflectivoTallasSeleccionadas.caballero.tallas.includes(talla)) {
                        window.reflectivoTallasSeleccionadas.caballero.tallas.push(talla);
                        window.reflectivoTallasSeleccionadas.caballero.tipo = tipo;
                    }
                }
                
                btn.style.borderColor = '#0066cc';
                btn.style.background = '#0066cc';
                btn.style.color = 'white';
                
                actualizarTablaTallasReflectivo();
            };
            
            window.actualizarTablaTallasReflectivo = function() {
                const tbody = document.getElementById('reflectivo-tallas-tbody');
                const container = document.getElementById('reflectivo-tallas-tabla-container');
                
                tbody.innerHTML = '';
                
                const todasLasTallas = [];
                
                if (window.reflectivoTallasSeleccionadas.dama.tallas.length > 0) {
                    window.reflectivoTallasSeleccionadas.dama.tallas.forEach(talla => {
                        todasLasTallas.push({ talla, genero: 'dama' });
                    });
                }
                
                if (window.reflectivoTallasSeleccionadas.caballero.tallas.length > 0) {
                    window.reflectivoTallasSeleccionadas.caballero.tallas.forEach(talla => {
                        todasLasTallas.push({ talla, genero: 'caballero' });
                    });
                }
                
                if (todasLasTallas.length === 0) {
                    container.style.display = 'none';
                    return;
                }
                
                container.style.display = 'block';
                
                todasLasTallas.forEach((item, index) => {
                    const tr = document.createElement('tr');
                    tr.style.cssText = 'border-bottom: 1px solid #d1d5db;';
                    tr.innerHTML = `
                        <td style="padding: 0.75rem; font-size: 0.875rem;">${item.talla} (${item.genero})</td>
                        <td style="padding: 0.75rem; text-align: center;">
                            <input type="number" value="1" min="1" style="width: 60px; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 4px; text-align: center; font-size: 0.875rem;">
                        </td>
                        <td style="padding: 0.75rem; text-align: center;">
                            <button type="button" onclick="eliminarTallaReflectivo('${item.talla}', '${item.genero}')" class="btn btn-sm" style="background: #ef4444; color: white; padding: 0.5rem 0.75rem; font-size: 0.75rem; border: none; cursor: pointer; border-radius: 4px;">
                                <span class="material-symbols-rounded" style="font-size: 0.9rem;">delete</span>
                            </button>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });
            };
            
            window.eliminarTallaReflectivo = function(talla, genero) {
                const index = window.reflectivoTallasSeleccionadas[genero].tallas.indexOf(talla);
                if (index > -1) {
                    window.reflectivoTallasSeleccionadas[genero].tallas.splice(index, 1);
                }
                actualizarTablaTallasReflectivo();
            };
            
            window.generarSelectoresTallasReflectivo = function() {
                // Esta función ya no se usa, se reemplazó con seleccionarGeneroReflectivo
            };
            
            window.generarSelectoresTallas = function() {
                const container = document.getElementById('reflectivo-tallas-generos');
                if (!container) return;
                
                container.innerHTML = '';
                
                // Obtener tallas seleccionadas del modal de tallas
                const tallasSeleccionadas = window.tallasSeleccionadas || { dama: { tallas: [] }, caballero: { tallas: [] } };
                
                ['dama', 'caballero'].forEach(genero => {
                    const tallas = tallasSeleccionadas[genero]?.tallas || [];
                    if (tallas.length === 0) return;
                    
                    const div = document.createElement('div');
                    div.innerHTML = `<h4 style="margin: 0 0 0.5rem 0; color: #1f2937; text-transform: capitalize;">${genero}</h4>`;
                    
                    const tallasList = document.createElement('div');
                    tallasList.style.cssText = 'display: flex; flex-wrap: wrap; gap: 0.5rem;';
                    
                    tallas.forEach(talla => {
                        const label = document.createElement('label');
                        label.style.cssText = 'display: flex; align-items: center; gap: 0.5rem; cursor: pointer;';
                        label.innerHTML = `
                            <input type="checkbox" class="reflectivo-talla-${genero}" value="${talla}" style="width: 16px; height: 16px; cursor: pointer;">
                            <span style="font-size: 0.875rem;">${talla}</span>
                        `;
                        tallasList.appendChild(label);
                    });
                    
                    div.appendChild(tallasList);
                    container.appendChild(div);
                });
            };
            
            window.agregarUbicacionReflectivo = function() {
                const input = document.getElementById('reflectivo-ubicacion-input');
                const ubicacion = input.value.trim();
                
                if (!ubicacion) {
                    alert('Por favor escribe una ubicación');
                    return;
                }
                
                // Agregar a la lista
                window.datosReflectivo.ubicaciones.push(ubicacion);
                
                // Limpiar input
                input.value = '';
                input.focus();
                
                // Actualizar lista visual
                actualizarListaUbicacionesReflectivo();
            };
            
            window.actualizarListaUbicacionesReflectivo = function() {
                const lista = document.getElementById('reflectivo-ubicaciones-lista');
                if (!lista) return;
                
                lista.innerHTML = '';
                window.datosReflectivo.ubicaciones.forEach((ubicacion, index) => {
                    const item = document.createElement('div');
                    item.style.cssText = 'display: flex; align-items: center; justify-content: space-between; background: #f3f4f6; padding: 0.75rem; border-radius: 6px; border-left: 4px solid #0066cc;';
                    
                    const texto = document.createElement('span');
                    texto.textContent = ubicacion;
                    texto.style.cssText = 'font-size: 0.875rem; color: #1f2937;';
                    item.appendChild(texto);
                    
                    const btnEliminar = document.createElement('button');
                    btnEliminar.innerHTML = '<span class="material-symbols-rounded" style="font-size: 1rem;">close</span>';
                    btnEliminar.style.cssText = 'background: #ef4444; color: white; border: none; border-radius: 4px; padding: 0.5rem; cursor: pointer; display: flex; align-items: center; justify-content: center; width: 32px; height: 32px;';
                    btnEliminar.onclick = () => {
                        window.datosReflectivo.ubicaciones.splice(index, 1);
                        actualizarListaUbicacionesReflectivo();
                    };
                    item.appendChild(btnEliminar);
                    
                    lista.appendChild(item);
                });
            };
            
            window.abrirEditorTallasReflectivo = function() {
                const modal = document.createElement('div');
                modal.id = 'modal-editor-tallas-reflectivo';
                modal.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 10002;';
                
                const container = document.createElement('div');
                container.style.cssText = 'background: white; border-radius: 12px; max-width: 400px; width: 90%; max-height: 80vh; overflow-y: auto; box-shadow: 0 20px 50px rgba(0,0,0,0.3);';
                
                // Header
                const header = document.createElement('div');
                header.style.cssText = 'background: linear-gradient(135deg, #0066cc 0%, #0052a3 100%); color: white; padding: 1.5rem; border-radius: 12px 12px 0 0; display: flex; align-items: center; justify-content: space-between;';
                header.innerHTML = '<h2 style="margin: 0; font-size: 1.25rem;">Seleccionar Tallas</h2>';
                
                const btnCerrar = document.createElement('button');
                btnCerrar.innerHTML = '<span class="material-symbols-rounded">close</span>';
                btnCerrar.style.cssText = 'background: transparent; color: white; border: none; cursor: pointer; padding: 0; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;';
                btnCerrar.onclick = () => modal.remove();
                header.appendChild(btnCerrar);
                container.appendChild(header);
                
                // Content
                const content = document.createElement('div');
                content.style.cssText = 'padding: 1.5rem; display: flex; flex-direction: column; gap: 1rem;';
                
                // Obtener tallas seleccionadas del modal principal
                const tallasSeleccionadas = window.tallasSeleccionadas || { dama: { tallas: [] }, caballero: { tallas: [] } };
                
                ['dama', 'caballero'].forEach(genero => {
                    const tallas = tallasSeleccionadas[genero]?.tallas || [];
                    if (tallas.length === 0) return;
                    
                    const div = document.createElement('div');
                    div.innerHTML = `<h4 style="margin: 0 0 0.5rem 0; color: #1f2937; text-transform: capitalize; font-size: 0.95rem;">${genero.toUpperCase()}</h4>`;
                    
                    const tallasList = document.createElement('div');
                    tallasList.style.cssText = 'display: flex; flex-wrap: wrap; gap: 0.5rem;';
                    
                    tallas.forEach(talla => {
                        const label = document.createElement('label');
                        label.style.cssText = 'display: flex; align-items: center; gap: 0.5rem; cursor: pointer;';
                        label.innerHTML = `
                            <input type="checkbox" class="reflectivo-talla-editor-${genero}" value="${talla}" style="width: 16px; height: 16px; cursor: pointer;">
                            <span style="font-size: 0.875rem;">${talla}</span>
                        `;
                        tallasList.appendChild(label);
                    });
                    
                    div.appendChild(tallasList);
                    content.appendChild(div);
                });
                
                container.appendChild(content);
                
                // Footer
                const footer = document.createElement('div');
                footer.style.cssText = 'display: flex; gap: 1rem; justify-content: flex-end; padding: 1.5rem; border-top: 1px solid #e5e7eb;';
                
                const btnCancelar = document.createElement('button');
                btnCancelar.textContent = 'Cancelar';
                btnCancelar.style.cssText = 'background: #e5e7eb; color: #1f2937; border: none; border-radius: 6px; padding: 0.75rem 1.5rem; cursor: pointer; font-weight: 500; font-size: 0.875rem;';
                btnCancelar.onclick = () => modal.remove();
                footer.appendChild(btnCancelar);
                
                const btnGuardar = document.createElement('button');
                btnGuardar.textContent = 'Guardar';
                btnGuardar.style.cssText = 'background: #0066cc; color: white; border: none; border-radius: 6px; padding: 0.75rem 1.5rem; cursor: pointer; font-weight: 500; font-size: 0.875rem;';
                btnGuardar.onclick = () => {
                    window.datosReflectivo.tallasPorGenero.dama = Array.from(document.querySelectorAll('.reflectivo-talla-editor-dama:checked')).map(cb => cb.value);
                    window.datosReflectivo.tallasPorGenero.caballero = Array.from(document.querySelectorAll('.reflectivo-talla-editor-caballero:checked')).map(cb => cb.value);
                    
                    // Actualizar tarjeta de tallas en el modal principal
                    actualizarTarjetaTallasReflectivo();
                    
                    modal.remove();
                };
                footer.appendChild(btnGuardar);
                
                container.appendChild(footer);
                modal.appendChild(container);
                document.body.appendChild(modal);
            };
            
            window.actualizarTarjetaTallasReflectivo = function() {
                const container = document.getElementById('reflectivo-tallas-seleccionadas');
                if (!container) return;
                
                container.innerHTML = '';
                
                const todasLasTallas = [];
                
                if (window.datosReflectivo.tallasPorGenero.dama.length > 0) {
                    window.datosReflectivo.tallasPorGenero.dama.forEach(talla => {
                        todasLasTallas.push({ talla, genero: 'dama' });
                    });
                }
                
                if (window.datosReflectivo.tallasPorGenero.caballero.length > 0) {
                    window.datosReflectivo.tallasPorGenero.caballero.forEach(talla => {
                        todasLasTallas.push({ talla, genero: 'caballero' });
                    });
                }
                
                if (todasLasTallas.length === 0) {
                    container.innerHTML = '<p style="color: #9ca3af; font-size: 0.875rem;">No hay tallas seleccionadas</p>';
                    return;
                }
                
                const tabla = document.createElement('table');
                tabla.style.cssText = 'width: 100%; border-collapse: collapse; border: 1px solid #d1d5db; border-radius: 6px; overflow: hidden;';
                
                const thead = document.createElement('thead');
                thead.innerHTML = `
                    <tr style="background: #f3f4f6;">
                        <th style="padding: 0.75rem; text-align: left; font-weight: 600; font-size: 0.875rem; border-bottom: 1px solid #d1d5db;">Talla</th>
                        <th style="padding: 0.75rem; text-align: center; font-weight: 600; font-size: 0.875rem; border-bottom: 1px solid #d1d5db;">Género</th>
                        <th style="padding: 0.75rem; text-align: center; font-weight: 600; font-size: 0.875rem; border-bottom: 1px solid #d1d5db;">Cantidad</th>
                        <th style="padding: 0.75rem; text-align: center; font-weight: 600; font-size: 0.875rem; border-bottom: 1px solid #d1d5db;">Acción</th>
                    </tr>
                `;
                tabla.appendChild(thead);
                
                const tbody = document.createElement('tbody');
                todasLasTallas.forEach((item, index) => {
                    const tr = document.createElement('tr');
                    tr.style.cssText = 'border-bottom: 1px solid #d1d5db;';
                    
                    const cantidadKey = `reflectivo-cantidad-${item.genero}-${item.talla}`;
                    const cantidadGuardada = sessionStorage.getItem(cantidadKey) || '1';
                    
                    tr.innerHTML = `
                        <td style="padding: 0.75rem; font-size: 0.875rem;">${item.talla}</td>
                        <td style="padding: 0.75rem; text-align: center; font-size: 0.875rem; text-transform: capitalize;">${item.genero}</td>
                        <td style="padding: 0.75rem; text-align: center;">
                            <input type="number" id="${cantidadKey}" value="${cantidadGuardada}" min="1" style="width: 60px; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 4px; text-align: center; font-size: 0.875rem;" onchange="guardarCantidadReflectivo('${cantidadKey}')">
                        </td>
                        <td style="padding: 0.75rem; text-align: center;">
                            <button type="button" onclick="eliminarTallaDelReflectivo('${item.talla}', '${item.genero}')" class="btn btn-sm" style="background: #ef4444; color: white; padding: 0.5rem 0.75rem; font-size: 0.75rem; border: none; cursor: pointer; border-radius: 4px;">
                                <span class="material-symbols-rounded" style="font-size: 0.9rem;">delete</span>
                            </button>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });
                tabla.appendChild(tbody);
                container.appendChild(tabla);
            };
            
            window.capturarCantidadesPrenda = function() {
                if (!window.cantidadesPrenda) {
                    window.cantidadesPrenda = {};
                }
                
                // Buscar en la tabla de tallas del modal principal
                const tbody = document.querySelector('#tbody-tallas-principal tbody') || document.querySelector('table tbody');
                if (!tbody) return;
                
                const filas = tbody.querySelectorAll('tr');
                filas.forEach(fila => {
                    const celdas = fila.querySelectorAll('td');
                    if (celdas.length >= 3) {
                        const talla = celdas[0].textContent.trim();
                        const genero = celdas[1].textContent.trim().toLowerCase();
                        const inputCantidad = celdas[2].querySelector('input[type="number"]');
                        
                        if (inputCantidad && talla && genero) {
                            const cantidad = parseInt(inputCantidad.value) || 0;
                            window.cantidadesPrenda[`${genero}-${talla}`] = cantidad;
                        }
                    }
                });
            };
            
            window.guardarCantidadReflectivo = function(cantidadKey) {
                const input = document.getElementById(cantidadKey);
                if (!input) return;
                
                const cantidadReflectivo = parseInt(input.value) || 0;
                
                // Extraer género y talla del key
                const partes = cantidadKey.split('-');
                const genero = partes[2];
                const talla = partes.slice(3).join('-');
                
                // Capturar cantidades de prenda si no están disponibles
                if (!window.cantidadesPrenda || Object.keys(window.cantidadesPrenda).length === 0) {
                    capturarCantidadesPrenda();
                }
                
                // Obtener cantidad de prenda para esta talla
                const cantidadPrenda = window.cantidadesPrenda ? window.cantidadesPrenda[`${genero}-${talla}`] : null;
                
                if (cantidadPrenda && cantidadReflectivo > cantidadPrenda) {
                    // Mostrar modal de error
                    const modal = document.createElement('div');
                    modal.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 10003;';
                    
                    const box = document.createElement('div');
                    box.style.cssText = 'background: white; border-radius: 12px; padding: 2rem; max-width: 400px; box-shadow: 0 10px 40px rgba(0,0,0,0.3);';
                    
                    const titulo = document.createElement('h3');
                    titulo.textContent = '⚠️ Cantidad Excedida';
                    titulo.style.cssText = 'margin: 0 0 1rem 0; color: #ef4444; font-size: 1.1rem;';
                    box.appendChild(titulo);
                    
                    const mensaje = document.createElement('p');
                    mensaje.textContent = `La cantidad de reflectivo (${cantidadReflectivo}) no puede ser mayor que la cantidad de prendas (${cantidadPrenda}) para la talla ${talla} ${genero}.`;
                    mensaje.style.cssText = 'margin: 0 0 1.5rem 0; color: #6b7280; font-size: 0.95rem;';
                    box.appendChild(mensaje);
                    
                    const btn = document.createElement('button');
                    btn.textContent = 'Entendido';
                    btn.style.cssText = 'background: #0066cc; color: white; border: none; border-radius: 6px; padding: 0.75rem 1.5rem; cursor: pointer; font-weight: 500; width: 100%;';
                    btn.onclick = () => {
                        modal.remove();
                        input.value = cantidadPrenda;
                        sessionStorage.setItem(cantidadKey, cantidadPrenda);
                    };
                    box.appendChild(btn);
                    
                    modal.appendChild(box);
                    document.body.appendChild(modal);
                    
                    input.value = cantidadPrenda;
                    return;
                }
                
                sessionStorage.setItem(cantidadKey, input.value);
            };
            
            window.eliminarTallaDelReflectivo = function(talla, genero) {
                const index = window.datosReflectivo.tallasPorGenero[genero].indexOf(talla);
                if (index > -1) {
                    window.datosReflectivo.tallasPorGenero[genero].splice(index, 1);
                }
                actualizarTarjetaTallasReflectivo();
            };
            
            window.guardarConfiguracionReflectivo = function() {
                // Guardar observaciones
                window.datosReflectivo.observaciones = document.getElementById('reflectivo-observaciones').value;
                
                // Guardar si aplica a todas las tallas
                const aplicarTodas = document.getElementById('reflectivo-aplicar-todas');
                if (aplicarTodas) {
                    window.datosReflectivo.aplicarATodas = aplicarTodas.checked;
                }
                
                console.log('📋 Datos antes de mostrar resumen:', window.datosReflectivo);
                
                // Mostrar sección de reflectivo en el modal principal
                mostrarResumenReflectivo();
                
                cerrarModalReflectivo();
                console.log('✅ Configuración de reflectivo guardada:', window.datosReflectivo);
            };
            
            window.mostrarResumenReflectivo = function() {
                const seccion = document.getElementById('seccion-reflectivo-resumen');
                const contenido = document.getElementById('reflectivo-resumen-contenido');
                
                if (!seccion || !contenido) return;
                
                // Construir resumen
                let html = '';
                
                // Imágenes con preview
                if (window.datosReflectivo.imagenes.length > 0) {
                    html += `<div style="display: flex; gap: 0.5rem; align-items: center; margin-bottom: 1rem;">`;
                    html += `<img src="${window.datosReflectivo.imagenes[0].data}" style="width: 50px; height: 50px; border-radius: 6px; object-fit: cover; border: 2px solid #0066cc;">`;
                    if (window.datosReflectivo.imagenes.length > 1) {
                        html += `<span style="background: #0066cc; color: white; border-radius: 50%; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: bold;">+${window.datosReflectivo.imagenes.length - 1}</span>`;
                    }
                    html += `</div>`;
                }
                
                // Ubicaciones
                if (window.datosReflectivo.ubicaciones.length > 0) {
                    html += `<p style="margin: 0.5rem 0;"><strong>📍 Ubicaciones:</strong> ${window.datosReflectivo.ubicaciones.join(', ')}</p>`;
                }
                
                // Tallas
                if (!window.datosReflectivo.aplicarATodas) {
                    const todasLasTallas = [];
                    if (window.datosReflectivo.tallasPorGenero.dama.length > 0) {
                        todasLasTallas.push(...window.datosReflectivo.tallasPorGenero.dama.map(t => `${t} (D)`));
                    }
                    if (window.datosReflectivo.tallasPorGenero.caballero.length > 0) {
                        todasLasTallas.push(...window.datosReflectivo.tallasPorGenero.caballero.map(t => `${t} (C)`));
                    }
                    if (todasLasTallas.length > 0) {
                        html += `<p style="margin: 0.5rem 0;"><strong>📏 Tallas:</strong> ${todasLasTallas.join(', ')}</p>`;
                    }
                } else {
                    html += `<p style="margin: 0.5rem 0;"><strong>📏 Tallas:</strong> Todas las tallas</p>`;
                }
                
                // Observaciones
                if (window.datosReflectivo.observaciones) {
                    html += `<p style="margin: 0.5rem 0;"><strong>📝 Observaciones:</strong> ${window.datosReflectivo.observaciones}</p>`;
                }
                
                if (html === '') {
                    html = '<p style="color: #9ca3af;">Sin configuración</p>';
                }
                
                contenido.innerHTML = html;
                seccion.style.display = 'block';
            };
            
            function configurarEventosFormulario() {
                // Habilitar/deshabilitar inputs de variaciones
                const mangaCb = document.getElementById('aplica-manga');
                const bolsillosCb = document.getElementById('aplica-bolsillos');
                const brocheCb = document.getElementById('aplica-broche');
                
                // Si no existen los elementos, no hacer nada
                if (!mangaCb || !bolsillosCb || !brocheCb) {
                    return;
                }
                
                // Remover listeners anteriores si existen
                if (mangaCb._configured) return;
                
                mangaCb.addEventListener('change', function() {
                    const input = document.getElementById('manga-input');
                    const obs = document.getElementById('manga-obs');
                    if (input) {
                        input.disabled = !this.checked;
                        input.style.opacity = this.checked ? '1' : '0.5';
                    }
                    if (obs) {
                        obs.disabled = !this.checked;
                        obs.style.opacity = this.checked ? '1' : '0.5';
                    }
                });
                
                bolsillosCb.addEventListener('change', function() {
                    const input = document.getElementById('bolsillos-input');
                    if (input) {
                        input.disabled = !this.checked;
                        input.style.opacity = this.checked ? '1' : '0.5';
                    }
                });
                
                brocheCb.addEventListener('change', function() {
                    const input = document.getElementById('broche-input');
                    const obs = document.getElementById('broche-obs');
                    if (input) {
                        input.disabled = !this.checked;
                        input.style.opacity = this.checked ? '1' : '0.5';
                    }
                    if (obs) {
                        obs.disabled = !this.checked;
                        obs.style.opacity = this.checked ? '1' : '0.5';
                    }
                });
                
                // Marcar como configurado
                mangaCb._configured = true;
            }
            
            // Función global para manejar cambio de variaciones (para inline onchange)
            window.manejarCheckVariacion = function(checkbox) {
                const idCheckbox = checkbox.id;
                let inputIds = [];
                
                if (idCheckbox === 'aplica-manga') inputIds = ['manga-input', 'manga-obs'];
                else if (idCheckbox === 'aplica-bolsillos') inputIds = ['bolsillos-input'];
                else if (idCheckbox === 'aplica-broche') inputIds = ['broche-input', 'broche-obs'];
                
                inputIds.forEach(inputId => {
                    const input = document.getElementById(inputId);
                    if (input) {
                        input.disabled = !checkbox.checked;
                        input.style.opacity = checkbox.checked ? '1' : '0.5';
                    }
                });
            };
            
            window.cerrarModalPrendaNueva = function() {
                const modal = document.getElementById('modal-agregar-prenda-nueva');
                if (modal) {
                    modal.style.display = 'none';
                }
                
                // Limpiar botones de género
                const btnDama = document.getElementById('btn-genero-dama');
                const btnCaballero = document.getElementById('btn-genero-caballero');
                if (btnDama) btnDama.setAttribute('data-selected', 'false');
                if (btnCaballero) btnCaballero.setAttribute('data-selected', 'false');
                
                // Limpiar contenedor de géneros
                const generosContainer = document.getElementById('tarjetas-generos-container');
                if (generosContainer) generosContainer.innerHTML = '';
                
                // Limpiar imágenes de tela almacenadas
                window.imagenesTelaModalNueva = [];
                // Limpiar preview de tela
                const previewTela = document.getElementById('nueva-prenda-tela-preview');
                if (previewTela) {
                    previewTela.innerHTML = '';
                }
                // Limpiar input file
                const imgInput = document.getElementById('nueva-prenda-tela-img-input');
                if (imgInput) {
                    imgInput.value = '';
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
                
                // Limpiar telas agregadas
                window.telasAgregadas = [];
                actualizarTablaTelas();
            };
            
            window.agregarPrendaNueva = function() {
                const nombre = document.getElementById('nueva-prenda-nombre').value.trim().toUpperCase();
                const descripcion = document.getElementById('nueva-prenda-descripcion').value.trim();
                const origen = document.getElementById('nueva-prenda-origen-select').value;
                
                if (!nombre) {
                    alert('Por favor ingresa el nombre de la prenda');
                    return;
                }
                
                // Verificar que hay telas agregadas
                if (window.telasAgregadas.length === 0) {
                    alert('Por favor agrega al menos una tela');
                    return;
                }
                
                // Obtener tallas y cantidades del nuevo sistema - Formato: { genero: { talla: cantidad } }
                const tallasObj = {};
                let cantidadTotal = 0;
                
                document.querySelectorAll('#tarjetas-generos-container input[type="number"]').forEach(input => {
                    const cantidad = parseInt(input.value) || 0;
                    if (cantidad > 0) {
                        const genero = input.dataset.genero;
                        const talla = input.dataset.talla;
                        
                        // Inicializar género si no existe
                        if (!tallasObj[genero]) {
                            tallasObj[genero] = {};
                        }
                        
                        tallasObj[genero][talla] = cantidad;
                        cantidadTotal += cantidad;
                    }
                });
                
                if (cantidadTotal === 0) {
                    alert('Por favor ingresa al menos una cantidad en las tallas');
                    return;
                }
                
                // Convertir a array para compatibilidad con renderizado
                const tallas = [];
                Object.keys(tallasObj).forEach(genero => {
                    Object.keys(tallasObj[genero]).forEach(talla => {
                        tallas.push({
                            genero: genero,
                            talla: talla,
                            cantidad: tallasObj[genero][talla]
                        });
                    });
                });
                
                // Obtener variaciones
                const variaciones = {};
                if (document.getElementById('aplica-manga').checked) {
                    variaciones.manga = {
                        tipo: document.getElementById('manga-input').value.trim(),
                        observacion: document.getElementById('manga-obs')?.value.trim() || ''
                    };
                }
                if (document.getElementById('aplica-bolsillos').checked) {
                    variaciones.bolsillos = {
                        tipo: document.getElementById('bolsillos-input').value.trim(),
                        observacion: document.getElementById('bolsillos-obs')?.value.trim() || ''
                    };
                }
                if (document.getElementById('aplica-broche').checked) {
                    variaciones.broche = {
                        tipo: document.getElementById('broche-input').value.trim(),
                        observacion: document.getElementById('broche-obs')?.value.trim() || ''
                    };
                }
                
                // Obtener procesos seleccionados
                const procesos = [];
                document.querySelectorAll('input[name="nueva-prenda-procesos"]:checked').forEach(cb => {
                    procesos.push(cb.value);
                });
                
                console.log('➕ Agregando prenda nueva:', { nombre, cantidadTotal, origen, procesos, tallas, variaciones });
                
                // Estructura completa de la prenda - con MÚLTIPLES TELAS
                const prendaData = {
                    nombre: nombre,
                    descripcion: descripcion,
                    telas: window.telasAgregadas,  // Array de {tela, color, referencia}
                    cantidad: cantidadTotal,
                    tallas: tallas,
                    variaciones: variaciones,
                    imagenes: window.imagenesPrendaStorage.obtenerImagenes()  // Guardar imágenes
                };
                
                // REGLA DE SPLIT: Si tiene procesos, crear 2 ítems
                if (procesos.length > 0) {
                    // ÍTEM 1: Prenda BASE (sin procesos)
                    window.itemsPedido.push({
                        tipo: 'nuevo',
                        prenda: prendaData,
                        origen: origen,
                        procesos: [],
                        es_proceso: false,
                        tallas: tallas,  // Pasar tallas al nivel del ítem
                        variaciones: variaciones,  // Pasar variaciones al nivel del ítem
                        imagenes: window.imagenesPrendaStorage.obtenerImagenes()  // Pasar imágenes al nivel del ítem
                    });
                    
                    // ÍTEM 2: Prenda PROCESO (con procesos)
                    window.itemsPedido.push({
                        tipo: 'nuevo',
                        prenda: prendaData,
                        origen: origen,
                        procesos: procesos,
                        es_proceso: true,
                        tallas: tallas,  // Pasar tallas al nivel del ítem
                        variaciones: variaciones,  // Pasar variaciones al nivel del ítem
                        imagenes: window.imagenesPrendaStorage.obtenerImagenes()  // Pasar imágenes al nivel del ítem
                    });
                    
                    console.log(`✅ Prenda "${nombre}" agregada como 2 ítems (BASE + PROCESO)`);
                } else {
                    // Sin procesos: 1 solo ítem
                    window.itemsPedido.push({
                        tipo: 'nuevo',
                        prenda: prendaData,
                        origen: origen,
                        procesos: [],
                        es_proceso: false,
                        tallas: tallas,  // Pasar tallas al nivel del ítem
                        variaciones: variaciones,  // Pasar variaciones al nivel del ítem
                        imagenes: window.imagenesPrendaStorage.obtenerImagenes()  // Pasar imágenes al nivel del ítem
                    });
                    
                    console.log(`✅ Prenda "${nombre}" agregada como 1 ítem (sin procesos)`);
                }
                
                // Actualizar vista
                window.actualizarVistaItems();
                
                // Cerrar modal
                window.cerrarModalPrendaNueva();
            };

            // ========== MODAL DE REFLECTIVO (ANTIGUO - ELIMINADO) ==========
            // El nuevo modal de reflectivo está definido arriba en abrirModalReflectivo()
            
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
            
            // cerrarModalReflectivo ya está definida arriba - no duplicar
            
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
                    observaciones: observaciones,
                    imagenes: window.imagenesReflectivoStorage.obtenerImagenes()  // Guardar imágenes
                };
                
                // REFLECTIVO SIEMPRE TIENE PROCESO
                // ÍTEM 1: Prenda BASE (sin procesos)
                window.itemsPedido.push({
                    tipo: 'nuevo',
                    prenda: reflectivoData,
                    origen: origen,
                    procesos: [],
                    es_proceso: false,
                    tallas: tallas,
                    imagenes: window.imagenesReflectivoStorage.obtenerImagenes()  // Pasar imágenes al nivel del ítem
                });
                
                // ÍTEM 2: REFLECTIVO (con proceso reflectivo)
                window.itemsPedido.push({
                    tipo: 'nuevo',
                    prenda: reflectivoData,
                    origen: origen,
                    procesos: ['Reflectivo'],
                    es_proceso: true,
                    tallas: tallas,
                    imagenes: window.imagenesReflectivoStorage.obtenerImagenes()  // Pasar imágenes al nivel del ítem
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
                
                // Obtener el otro género
                const otroGenero = genero === 'dama' ? 'caballero' : 'dama';
                const tipoDelOtroGenero = window.tallasSeleccionadas[otroGenero].tipo;
                
                const btnLetra = document.getElementById('btn-tipo-letra');
                const btnNumero = document.getElementById('btn-tipo-numero');
                
                // Reset botones de tipo
                btnLetra.dataset.selected = 'false';
                btnNumero.dataset.selected = 'false';
                btnLetra.style.background = 'white';
                btnNumero.style.background = 'white';
                btnLetra.style.borderColor = '#d1d5db';
                btnNumero.style.borderColor = '#d1d5db';
                btnLetra.style.opacity = '1';
                btnNumero.style.opacity = '1';
                btnLetra.style.cursor = 'pointer';
                btnNumero.style.cursor = 'pointer';
                btnLetra.disabled = false;
                btnNumero.disabled = false;
                document.getElementById('container-tallas-seleccion').innerHTML = '';
                
                // Limpiar mensajes anteriores
                const msgAnterior = document.getElementById('mensaje-tipo-talla-sincronizado');
                if (msgAnterior) msgAnterior.remove();
                
                // ========== SI EXISTE UN TIPO EN EL OTRO GÉNERO, USAR ESE Y DESHABILITAR CAMBIOS ==========
                if (tipoDelOtroGenero) {
                    // Mostrar tipo del otro género automáticamente
                    seleccionarTipoTalla(tipoDelOtroGenero);
                    
                    // Deshabilitar botones de tipo para mantener consistencia
                    btnLetra.disabled = true;
                    btnNumero.disabled = true;
                    btnLetra.style.opacity = '0.5';
                    btnNumero.style.opacity = '0.5';
                    btnLetra.style.cursor = 'not-allowed';
                    btnNumero.style.cursor = 'not-allowed';
                    
                    // Agregar leyenda explicativa (solo una vez)
                    const msgDiv = document.createElement('div');
                    msgDiv.id = 'mensaje-tipo-talla-sincronizado';
                    msgDiv.style.cssText = 'background: #dbeafe; border: 1px solid #93c5fd; border-radius: 6px; padding: 0.75rem; margin-bottom: 1rem; font-size: 0.85rem; color: #1e40af; text-align: center;';
                    msgDiv.innerHTML = `<strong>ℹ️ Tipo de talla sincronizado:</strong> Se mantiene el tipo ${tipoDelOtroGenero.toUpperCase()} según tu selección anterior`;
                    document.querySelector('.modal-section:first-of-type').insertBefore(msgDiv, document.querySelector('.button-group'));
                } else if (window.tallasSeleccionadas[genero].tipo) {
                    // Si el género actual ya tiene tipo asignado, mostrarlo
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
                
                // Cerrar modal sin abrir automáticamente el otro género
                cerrarModalSeleccionarTallas();
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