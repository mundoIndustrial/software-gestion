@extends('layouts.asesores')

@section('extra_styles')
    <link rel="stylesheet" href="{{ asset('css/crear-pedido.css') }}">
    <link rel="stylesheet" href="{{ asset('css/crear-pedido-editable.css') }}">
    <link rel="stylesheet" href="{{ asset('css/form-modal-consistency.css') }}">
    <link rel="stylesheet" href="{{ asset('css/componentes/prendas.css') }}">
    <link rel="stylesheet" href="{{ asset('css/componentes/reflectivo.css') }}">
@endsection

@section('content')

<!-- Header Full Width -->
<div class="page-header">
    <h1> Crear Pedido de Producción desde Cotización</h1>
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
            <h2>
                <span>2</span> Selecciona una Cotización
            </h2>

            <!-- Contenedor para opciones dinámicas -->
            <div id="contenedor-opciones-pedido" style="margin-top: 1.5rem;">
                <!-- Buscador de Cotización -->
                <div id="seccion-buscar-cotizacion" style="display: block;">
                    <div class="form-group">
                        <label for="cotizacion_search_editable" class="block text-sm font-medium text-gray-700 mb-2">
                            Cotización
                        </label>
                        <div style="position: relative;">
                            <input type="text" id="cotizacion_search_editable" placeholder=" Buscar por número, cliente o asesora..." class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" autocomplete="off">
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
            </div>
        </div>

        <!-- PASO 3: Ítems del Pedido -->
        <div class="form-section" id="seccion-items-pedido" style="margin-top: 2rem;">
            <h2>
                <span>3</span> Ítems del Pedido
            </h2>

            <!-- Lista de ítems -->
            <div id="lista-items-pedido" style="display: flex; flex-direction: column; gap: 0.75rem;">
                <!-- Los ítems se agregarán aquí dinámicamente -->
            </div>

            <!-- Mensaje cuando no hay ítems -->
            <div id="mensaje-sin-items" style="padding: 2rem; text-align: center; background: #f9fafb; border: 2px dashed #d1d5db; border-radius: 8px; color: #6b7280;">
                <p style="margin: 0; font-size: 0.875rem;">No hay ítems agregados. Selecciona una cotización para agregar prendas.</p>
            </div>
        </div>

        <!-- COMPONENTE: Prendas Editables -->
        @include('asesores.pedidos.components.prendas-editable')

        <!-- COMPONENTE: Reflectivo Editable -->
        @include('asesores.pedidos.components.reflectivo-editable')

        <!-- PASO 4: Botones de Acción -->
        <div class="btn-actions">
            <button type="button" id="btn-vista-previa" class="btn btn-secondary" style="display: none; background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%); color: white; border: none; padding: 10px 16px; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 0.9rem; transition: all 0.3s; box-shadow: 0 2px 4px rgba(107, 114, 128, 0.2); display: flex; align-items: center; gap: 0.5rem;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 8px rgba(107, 114, 128, 0.3)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 4px rgba(107, 114, 128, 0.2)'" title="Ver factura en tamaño grande">
                <span class="material-symbols-rounded" style="font-size: 1.1rem;">visibility</span>
                Vista Previa
            </button>
            <button type="submit" id="btn-submit" class="btn btn-primary" style="display: none; background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); color: white; border: none; padding: 10px 16px; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 0.9rem; transition: all 0.3s; box-shadow: 0 2px 4px rgba(59, 130, 246, 0.2); display: flex; align-items: center; justify-content: center; gap: 0.5rem;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 8px rgba(59, 130, 246, 0.3)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 4px rgba(59, 130, 246, 0.2)'">
                <span class="material-symbols-rounded" style="font-size: 1.1rem;">check_circle</span>
                Crear Pedido
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
@include('asesores.pedidos.modals.modal-proceso-generico')

@push('scripts')
    <!-- IMPORTANTE: Cargar constantes PRIMERO -->
    <script src="{{ asset('js/configuraciones/constantes-tallas.js') }}"></script>
    
    <!-- Manejadores de procesos - DEBEN cargarse ANTES de prenda-editor.js -->
    <script src="{{ asset('js/modulos/crear-pedido/procesos/manejadores-procesos-prenda.js') }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/procesos/gestor-modal-proceso-generico.js') }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/procesos/renderizador-tarjetas-procesos.js') }}"></script>
    
    <!-- IMPORTANTE: Cargar módulos DESPUÉS de las constantes -->
    <script src="{{ asset('js/modulos/crear-pedido/modales/modales-dinamicos.js') }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/tallas/gestion-tallas.js') }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/telas/gestion-telas.js') }}"></script>
    
    <!--  SERVICIOS SOLID - Deben cargarse ANTES de GestionItemsUI -->
    <script src="{{ asset('js/modulos/crear-pedido/procesos/services/notification-service.js') }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/procesos/services/item-api-service.js') }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/procesos/services/item-validator.js') }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/procesos/services/item-form-collector.js') }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/procesos/services/item-renderer.js') }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/procesos/services/prenda-editor.js') }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/procesos/services/item-orchestrator.js') }}"></script>
    
    <script src="{{ asset('js/modulos/crear-pedido/procesos/gestion-items-pedido.js') }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/modales/modal-seleccion-prendas.js') }}"></script>
    
    <!-- Componente: Reflectivo -->
    <script src="{{ asset('js/componentes/reflectivo.js') }}"></script>
    
    <!-- Cargar módulos de gestión de pedidos -->
    <script src="{{ asset('js/modulos/crear-pedido/configuracion/api-pedidos-editable.js') }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/fotos/image-storage-service.js') }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/fotos/manejador-fotos-prenda-edicion.js') }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/fotos/galeria-imagenes-prenda.js') }}"></script>
    
    <!-- Gestor base (necesario para la clase GestorPrendaSinCotizacion) -->
    <script src="{{ asset('js/modulos/crear-pedido/gestores/gestor-prenda-sin-cotizacion.js') }}"></script>
    
    <!-- Inicializador del gestor -->
    <script src="{{ asset('js/modulos/crear-pedido/prendas/inicializar-gestor.js') }}"></script>
    
    <!-- Manejadores de variaciones -->
    <script src="{{ asset('js/modulos/crear-pedido/prendas/manejadores-variaciones.js') }}"></script>
    
    <!-- Componente: Editor de Prendas (para editar desde listado de pedidos) -->
    <script src="{{ asset('js/componentes/prenda-editor-modal.js') }}"></script>

    <script>
        // Datos del servidor
        window.cotizacionesData = @json($cotizacionesData ?? []);
        window.asesorActualNombre = '{{ Auth::user()->name ?? '' }}';

        document.addEventListener('DOMContentLoaded', function() {
            // Inicializar storages de imágenes
            window.imagenesPrendaStorage = new ImageStorageService(3);
            window.imagenesTelaStorage = new ImageStorageService(3);
            window.imagenesReflectivoStorage = new ImageStorageService(3);
            
            // Configurar asesora
            document.getElementById('asesora_editable').value = '{{ Auth::user()->name ?? '' }}';
            
            // Mostrar botones
            const btnSubmit = document.getElementById('btn-submit');
            btnSubmit.textContent = '✓ Crear Pedido';
            btnSubmit.style.display = 'block';
            
            const btnVistaPrevio = document.getElementById('btn-vista-previa');
            btnVistaPrevio.style.display = 'block';

            // ========== BUSCADOR DE COTIZACIONES ==========
            const searchInput = document.getElementById('cotizacion_search_editable');
            const dropdown = document.getElementById('cotizacion_dropdown_editable');
            const selectedDiv = document.getElementById('cotizacion_selected_editable');
            const selectedText = document.getElementById('cotizacion_selected_text_editable');
            const hiddenInput = document.getElementById('cotizacion_id_editable');
            
            if (!searchInput) {
                return;
            }
            
            let cotizacionSeleccionada = null;
            
            // Mostrar todas las cotizaciones al hacer focus
            searchInput.addEventListener('focus', function() {
                mostrarCotizaciones('');
            });
            
            // Filtrar cotizaciones al escribir
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase().trim();
                mostrarCotizaciones(searchTerm);
            });
            
            // Función para mostrar cotizaciones filtradas
            function mostrarCotizaciones(searchTerm) {
                if (searchTerm.length === 0) {
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
                if (cotizaciones.length === 0) {
                    dropdown.innerHTML = '<div style="padding: 1rem; text-align: center; color: #6b7280;">No se encontraron cotizaciones</div>';
                    dropdown.style.display = 'block';
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
                
                // Guardar para usar en agregar prendas
                window.cotizacionSeleccionadaActual = cotizacion;
                // Abrir modal de selección de prendas
                if (typeof window.abrirModalSeleccionPrendas === 'function') {
                    window.abrirModalSeleccionPrendas(cotizacion);
                }
            }
            
            // Cerrar dropdown al hacer click fuera
            document.addEventListener('click', function(e) {
                if (!searchInput.contains(e.target) && !dropdown.contains(e.target)) {
                    dropdown.style.display = 'none';
                }
            });

            // ========== GESTIÓN DE ÍTEMS ==========
            const seccionItems = document.getElementById('seccion-items-pedido');
            if (seccionItems) {
                seccionItems.style.display = 'block';
            }

        });
    </script>
@endpush

@endsection
