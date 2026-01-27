@extends('layouts.asesores')

@section('extra_styles')
    <link rel="stylesheet" href="{{ asset('css/crear-pedido.css') }}">
    <link rel="stylesheet" href="{{ asset('css/crear-pedido-editable.css') }}">
    <link rel="stylesheet" href="{{ asset('css/form-modal-consistency.css') }}">
    <link rel="stylesheet" href="{{ asset('css/swal-z-index-fix.css') }}">
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
                            <input type="text" id="cotizacion_search_editable" placeholder="Buscar por número de cotización o cliente..." class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" autocomplete="off">
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

            <!-- Botón para agregar prenda -->
            <div style="margin-bottom: 1.5rem;">
                <button type="button" id="btn-agregar-prenda" class="btn btn-primary" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border: none; padding: 10px 16px; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 0.9rem; transition: all 0.3s; box-shadow: 0 2px 4px rgba(16, 185, 129, 0.2);" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 8px rgba(16, 185, 129, 0.3)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 4px rgba(16, 185, 129, 0.2)'">
                    <span class="material-symbols-rounded" style="font-size: 1.1rem; margin-right: 0.5rem; vertical-align: middle;">add_circle</span>
                    Agregar Prenda
                </button>
            </div>

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
            <a href="{{ route('asesores.pedidos.index') }}" class="btn btn-secondary">
                ✕ Cancelar
            </a>
        </div>
    </form>
</div>

@include('asesores.pedidos.modals.modal-seleccionar-prendas')
@include('asesores.pedidos.components.modal-prendas-lista')
@include('asesores.pedidos.modals.modal-seleccionar-tallas')
@include('asesores.pedidos.modals.modal-agregar-prenda-nueva')
@include('asesores.pedidos.modals.modal-agregar-reflectivo')
@include('asesores.pedidos.modals.modal-proceso-generico')

@push('scripts')
    <!-- IMPORTANTE: Cargar constantes PRIMERO -->
    <script src="{{ asset('js/configuraciones/constantes-tallas.js') }}"></script>
    
    <!-- API SERVICE - Manejo centralizado de API -->
    <script type="module" src="{{ asset('js/services/api-service.js') }}"></script>
    
    <!-- BUILDER UNIFICADO - Sanitización y estructura única -->
    <script type="module" src="{{ asset('js/pedidos-produccion/PedidoCompletoUnificado.js') }}"></script>
    
    <!-- INICIALIZADOR - Puente entre módulos ES6 y código global -->
    <script type="module" src="{{ asset('js/pedidos-produccion/inicializador-pedido-completo.js') }}"></script>
    
    <!-- Manejadores de procesos - DEBEN cargarse ANTES de prenda-editor.js -->
    <script src="{{ asset('js/modulos/crear-pedido/procesos/manejadores-procesos-prenda.js') }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/procesos/gestor-modal-proceso-generico.js') }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/procesos/renderizador-tarjetas-procesos.js') }}"></script>
    
    <!--  SERVICIOS EDICIÓN DINÁMICA DE PROCESOS - Deben cargarse PRIMERO -->
    <script src="{{ asset('js/modulos/crear-pedido/procesos/services/proceso-editor.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/procesos/services/gestor-edicion-procesos.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/procesos/services/servicio-procesos.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/procesos/services/middleware-guardado-prenda.js') }}?v={{ time() }}"></script>
    
    <!-- IMPORTANTE: Cargar módulos DESPUÉS de las constantes -->
    <script src="{{ asset('js/modulos/crear-pedido/modales/modales-dinamicos.js') }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/tallas/gestion-tallas.js') }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/telas/gestion-telas.js') }}"></script>
    
    <!--  SERVICIOS SOLID - Deben cargarse ANTES de GestionItemsUI -->
    <script src="{{ asset('js/modulos/crear-pedido/procesos/services/notification-service.js') }}?v={{ time() }}"></script>
    
    <!-- PAYLOAD NORMALIZER v3 - VERSIÓN DEFINITIVA Y SEGURA -->
    <script src="{{ asset('js/modulos/crear-pedido/procesos/services/payload-normalizer-v3-definitiva.js') }}?v={{ time() }}"></script>
    
    <script src="{{ asset('js/modulos/crear-pedido/procesos/services/item-api-service.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/procesos/services/item-validator.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/procesos/services/item-form-collector.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/procesos/services/item-renderer.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/procesos/services/prenda-editor.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/procesos/services/item-orchestrator.js') }}?v={{ time() }}"></script>
    
    <script src="{{ asset('js/modulos/crear-pedido/procesos/gestion-items-pedido.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/modales/modal-seleccion-prendas.js') }}?v={{ time() }}"></script>
    
    <!-- Componente: Reflectivo -->
    <script src="{{ asset('js/componentes/reflectivo.js') }}?v={{ time() }}"></script>
    
    <!-- Cargar módulos de gestión de pedidos -->
    <script src="{{ asset('js/modulos/crear-pedido/configuracion/api-pedidos-editable.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/fotos/image-storage-service.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/fotos/manejador-fotos-prenda-edicion.js') }}?v={{ time() }}"></script>
    
    <!-- Componente: Wrappers para preview y funciones de prenda (carga PRIMERO para el placeholder) -->
    <script src="{{ asset('js/componentes/prendas-wrappers.js') }}?v={{ time() }}"></script>
    
    <!-- Galería de imágenes (carga DESPUÉS para sobrescribir la función real) -->
    <script src="{{ asset('js/modulos/crear-pedido/fotos/galeria-imagenes-prenda.js') }}?v={{ time() }}"></script>
    
    <!-- NUEVO: Cargador de prendas desde cotización (COMPLETO) -->
    <script src="{{ asset('js/modulos/crear-pedido/integracion/cargar-prendas-cotizacion.js') }}?v={{ time() }}"></script>
    
    <!-- Gestor base (necesario para la clase GestorPrendaSinCotizacion) -->
    <script src="{{ asset('js/modulos/crear-pedido/gestores/gestor-prenda-sin-cotizacion.js') }}?v={{ time() }}"></script>
    
    <!-- Inicializador del gestor -->
    <script src="{{ asset('js/modulos/crear-pedido/prendas/inicializar-gestor.js') }}?v={{ time() }}"></script>
    
    <!-- Manejadores de variaciones -->
    <script src="{{ asset('js/modulos/crear-pedido/prendas/manejadores-variaciones.js') }}?v={{ time() }}"></script>
    
    <!-- Componente: Editor de Prendas (para editar desde listado de pedidos) -->
    <script src="{{ asset('js/componentes/prenda-editor-modal.js') }}?v={{ time() }}"></script>
    
    <!-- Componente para editar prendas con procesos desde API -->
    <script src="{{ asset('js/componentes/prenda-card-editar-simple.js') }}"></script>

    <!-- Datos globales del servidor -->
    <script>
        window.cotizacionesData = @json($cotizacionesData ?? []);
        window.asesorActualNombre = '{{ Auth::user()->name ?? '' }}';
    </script>

    <!-- Inicializador del buscador de cotizaciones -->
    <script src="{{ asset('js/modulos/crear-pedido/inicializadores/init-buscador-cotizacion.js') }}"></script>
@endpush

@endsection
