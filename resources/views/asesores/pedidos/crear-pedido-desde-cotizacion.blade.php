@extends('layouts.asesores')

@section('extra_styles')
    <link rel="stylesheet" href="{{ asset('css/crear-pedido.css') }}">
    <link rel="stylesheet" href="{{ asset('css/crear-pedido-editable.css') }}">
    <link rel="stylesheet" href="{{ asset('css/form-modal-consistency.css') }}">
    <link rel="stylesheet" href="{{ asset('css/swal-z-index-fix.css') }}">
    <link rel="stylesheet" href="{{ asset('css/componentes/prendas.css') }}">
    <link rel="stylesheet" href="{{ asset('css/componentes/reflectivo.css') }}">
    <link rel="stylesheet" href="{{ asset('css/modales/modal-exito-pedido.css') }}">
    <link rel="stylesheet" href="{{ asset('css/modulos/epp-modal.css') }}">
@endsection

@section('content')

<!-- Loading Spinner -->
<div id="loading-overlay" style="
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255, 255, 255, 0.95);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    backdrop-filter: blur(2px);
">
    <div style="text-align: center;">
        <div style="
            width: 60px;
            height: 60px;
            margin: 0 auto 20px;
            border: 4px solid #e5e7eb;
            border-top: 4px solid #3b82f6;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        "></div>
        <h2 style="color: #374151; font-size: 1.125rem; margin: 10px 0; font-weight: 600;">Cargando...</h2>
        <p style="color: #6b7280; font-size: 0.875rem; margin: 0;">Preparando el formulario de pedido</p>
    </div>
</div>

<style>
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
</style>

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
                        <div style="display: flex; gap: 1rem; align-items: flex-end;">
                            <div style="position: relative; flex: 1;">
                                <input type="text" id="cotizacion_search_editable" placeholder="Buscar por número de cotización o cliente..." class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" autocomplete="off">
                                <input type="hidden" id="cotizacion_id_editable" name="cotizacion_id">
                                <input type="hidden" id="logoCotizacionId" name="logoCotizacionId">
                                <div id="cotizacion_dropdown_editable" style="position: absolute; top: 100%; left: 0; right: 0; background: white; border: 1px solid #d1d5db; border-top: none; border-radius: 0 0 8px 8px; max-height: 300px; overflow-y: auto; display: none; z-index: 1000; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
                                </div>
                            </div>
                            <!-- Botón para agregar prenda -->
                            <button type="button" id="btn-agregar-prenda" class="btn btn-primary" style="background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); color: white; border: none; padding: 10px 16px; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 0.9rem; transition: all 0.3s; box-shadow: 0 2px 4px rgba(59, 130, 246, 0.2); white-space: nowrap;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 8px rgba(59, 130, 246, 0.3)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 4px rgba(59, 130, 246, 0.2)'">
                                <span class="material-symbols-rounded" style="font-size: 1.1rem; margin-right: 0.5rem; vertical-align: middle;">add_circle</span>
                                Agregar Prenda
                            </button>
                            <!-- Botón para agregar EPP -->
                            <button type="button" id="btn-agregar-epp" class="btn btn-primary" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border: none; padding: 10px 16px; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 0.9rem; transition: all 0.3s; box-shadow: 0 2px 4px rgba(16, 185, 129, 0.2); white-space: nowrap;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 8px rgba(16, 185, 129, 0.3)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 4px rgba(16, 185, 129, 0.2)'">
                                <span class="material-symbols-rounded" style="font-size: 1.1rem; margin-right: 0.5rem; vertical-align: middle;">health_and_safety</span>
                                Agregar EPP
                            </button>
                        </div>
                        <div id="cotizacion_selected_editable" style="margin-top: 0.75rem; padding: 1rem; background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); border-left: 4px solid #0066cc; border-radius: 4px; display: none; box-shadow: 0 2px 4px rgba(0, 102, 204, 0.1);">
                            <div style="font-size: 0.875rem; color: #1e40af;">
                                <strong>✓ Cotización Seleccionada:</strong>
                            </div>
                            <div style="font-size: 0.9rem; color: #1e40af; margin-top: 0.25rem;" id="cotizacion_selected_text_editable"></div>
                            <div style="font-size: 0.875rem; color: #0d47a1; margin-top: 0.5rem;">
                                <strong>Tipo:</strong> <span id="cotizacion_tipo_text_editable" style="background: #dbeafe; padding: 2px 6px; border-radius: 3px; font-weight: 600;"></span>
                            </div>
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
                <p style="margin: 0; font-size: 0.875rem;">No hay ítems agregados.</p>
            </div>
        </div>

        <!-- PASO 4: Botones de Acción -->
        <div class="btn-actions">
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
@include('asesores.pedidos.modals.modal-agregar-epp')

@push('scripts')
    <!-- IMPORTANTE: Cargar constantes PRIMERO -->
    <script src="{{ asset('js/configuraciones/constantes-tallas.js') }}"></script>
    
    <!-- API SERVICE - Manejo centralizado de API -->
    <script type="module" src="{{ asset('js/services/api-service.js') }}"></script>
    
    <!-- SERVICIO HTTP para EPP (DEBE cargarse ANTES del modal) -->
    <script src="{{ asset('js/services/epp/EppHttpService.js') }}?v={{ time() }}"></script>

    <!-- EPP Services - Deben cargarse ANTES del modal -->
    <script src="{{ asset('js/modulos/crear-pedido/epp/services/epp-api-service.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/epp/services/epp-state-manager.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/epp/services/epp-modal-manager.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/epp/services/epp-item-manager.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/epp/services/epp-imagen-manager.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/epp/services/epp-service.js') }}?v={{ time() }}"></script>
    
    <!-- EPP Services SOLID - Mejoras de refactorización -->
    <script src="{{ asset('js/modulos/crear-pedido/epp/services/epp-notification-service.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/epp/services/epp-creation-service.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/epp/services/epp-form-manager.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/epp/services/epp-menu-handlers.js') }}?v={{ time() }}"></script>
    
    <!-- EPP Templates e Interfaces -->
    <script src="{{ asset('js/modulos/crear-pedido/epp/templates/epp-modal-template.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/epp/interfaces/epp-modal-interface.js') }}?v={{ time() }}"></script>
    
    <!-- EPP Initialization -->
    <script src="{{ asset('js/modulos/crear-pedido/epp/epp-init.js') }}?v={{ time() }}"></script>
    
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
    <script src="{{ asset('js/modulos/crear-pedido/tallas/gestion-tallas.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/telas/gestion-telas.js') }}?v={{ time() }}"></script>
    
    <!-- ESTILOS del componente tarjeta readonly (ANTES de scripts) -->
    <link rel="stylesheet" href="{{ asset('css/componentes/prenda-card-readonly.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('css/componentes/epp-card.css') }}">
    
    <!-- Constantes y helpers -->
    <script src="{{ asset('js/modulos/crear-pedido/procesos/gestion-items-pedido-constantes.js') }}?v={{ time() }}"></script>
    
    <!-- UTILIDADES (Helpers de DOM y Limpieza) -->
    <script src="{{ asset('js/utilidades/dom-utils.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/utilidades/modal-cleanup.js') }}?v={{ time() }}"></script>
    
    <!-- UTILIDADES (Procesamiento de datos de prenda) -->
    <script src="{{ asset('js/utilidades/tela-processor.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/utilidades/prenda-data-builder.js') }}?v={{ time() }}"></script>
    
    <!-- UTILIDADES (Validación y Logging - Phase 3) -->
    <script src="{{ asset('js/utilidades/logger-app.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/utilidades/validador-prenda.js') }}?v={{ time() }}"></script>
    
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
    
    <!-- Componente: Logo -->
    <script src="{{ asset('js/modulos/crear-pedido/logo/logo-pedido.js') }}?v={{ time() }}"></script>
    
    <!-- Cargar módulos de gestión de pedidos -->
    <script src="{{ asset('js/modulos/crear-pedido/configuracion/config-pedido-editable.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/configuracion/api-pedidos-editable.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/fotos/image-storage-service.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/fotos/manejador-fotos-prenda-edicion.js') }}?v={{ time() }}"></script>
    
    <!-- Componente: Wrappers para preview y funciones de prenda (carga PRIMERO para el placeholder) -->
    <script src="{{ asset('js/componentes/prendas-wrappers.js') }}?v={{ time() }}"></script>
    
    <!-- Componente: Form Collector (necesario para gestión de prendas) -->
    <script src="{{ asset('js/componentes/prenda-form-collector.js') }}?v={{ time() }}"></script>
    
    <!-- Galería de imágenes (carga DESPUÉS para sobrescribir la función real) -->
    <script src="{{ asset('js/modulos/crear-pedido/fotos/galeria-imagenes-prenda.js') }}?v={{ time() }}"></script>
    
    <!-- NUEVO: Cargador de prendas desde cotización (COMPLETO) -->
    <script src="{{ asset('js/modulos/crear-pedido/integracion/cargar-prendas-cotizacion.js') }}?v={{ time() }}"></script>
    
    <!-- NUEVO: Renderizador específico para cotizaciones -->
    <script src="{{ asset('js/modulos/crear-pedido/integracion/renderizador-cotizaciones.js') }}?v={{ time() }}"></script>
    
    <!-- NUEVO: Agregador independiente para cotizaciones -->
    <script src="{{ asset('js/modulos/crear-pedido/integracion/agregador-prendas-cotizacion.js') }}?v={{ time() }}"></script>
    
    <!-- Gestor base (necesario para la clase GestorPrendaSinCotizacion) -->
    <script src="{{ asset('js/modulos/crear-pedido/gestores/gestor-prenda-sin-cotizacion.js') }}?v={{ time() }}"></script>
    
    <!-- Inicializador del gestor sin cotización (necesario para renderizado) -->
    <script src="{{ asset('js/modulos/crear-pedido/inicializadores/init-gestor-sin-cotizacion.js') }}?v={{ time() }}"></script>
    
    <!-- UTILIDADES para transformación de datos -->
    <script src="{{ asset('js/prendas/utils/prenda-data-transformer.js') }}?v={{ time() }}"></script>
    
    <!-- BUILDERS para construcción de secciones HTML -->
    <script src="{{ asset('js/prendas/builders/variaciones-builder.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/prendas/builders/tallas-builder.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/prendas/builders/procesos-builder.js') }}?v={{ time() }}"></script>
    
    <!-- SERVICIOS MODULARES para tarjeta readonly (DEBEN cargarse ANTES) -->
    <script src="{{ asset('js/componentes/services/image-converter-service.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/componentes/services/prenda-card-service.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/componentes/services/prenda-card-handlers.js') }}?v={{ time() }}"></script>
    
    <!-- Componente tarjeta readonly (completo - funcional) -->
    <script src="{{ asset('js/componentes/prenda-card-readonly.js') }}?v={{ time() }}"></script>
    
    <!-- Inicializador del gestor -->
    <script src="{{ asset('js/modulos/crear-pedido/prendas/inicializar-gestor.js') }}?v={{ time() }}"></script>
    
    <!-- Manejadores de variaciones -->
    <script src="{{ asset('js/modulos/crear-pedido/prendas/manejadores-variaciones.js') }}?v={{ time() }}"></script>
    
    <!-- Componente: Editor de Prendas (para editar desde listado de pedidos) -->
    <script src="{{ asset('js/componentes/prenda-editor-modal.js') }}?v={{ time() }}"></script>
    
    <!-- Componente: Tarjetas de prendas (necesario para renderizado) -->
    <script src="{{ asset('js/componentes/prenda-card-readonly.js') }}?v={{ time() }}"></script>
    
    <!-- Componente para editar prendas con procesos desde API -->
    <script src="{{ asset('js/componentes/prenda-card-editar-simple.js') }}"></script>

    <!-- Modal de prendas - Constantes HTML (DEBE cargarse ANTES del modal principal) -->
    <script src="{{ asset('js/componentes/modal-prenda-dinamico-constantes.js') }}?v={{ time() }}"></script>
    
    <!-- Modal de prendas - Clase principal (usa constantes) -->
    <script src="{{ asset('js/componentes/modal-prenda-dinamico.js') }}?v={{ time() }}"></script>

    <!-- Funciones principales para crear pedido desde cotización -->
    <script src="{{ asset('js/crear-pedido-editable.js') }}?v={{ time() }}"></script>

    <!-- Gestor de cotizaciones -->
    <script src="{{ asset('js/modulos/crear-pedido/utilidades/helpers-pedido-editable.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/gestores/gestor-cotizacion.js') }}?v={{ time() }}"></script>

    <!-- Datos globales del servidor -->
    <script>
        window.cotizacionesData = @json($cotizacionesData ?? []);
        window.asesorActualNombre = '{{ Auth::user()->name ?? '' }}';
    </script>

    <!-- Inicializador del buscador de cotizaciones -->
    <script src="{{ asset('js/modulos/crear-pedido/inicializadores/init-buscador-cotizacion.js') }}"></script>
    
    <!-- Script para manejar los botones de agregar prenda y EPP -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Función para verificar si hay cotización seleccionada
        function verificarCotizacionSeleccionada() {
            const cotizacion = window.cotizacionSeleccionadaActual || window.cotizacionSeleccionada;
            console.log('🔍 [verificarCotizacionSeleccionada] Verificando cotización:', cotizacion);
            return cotizacion && cotizacion.id;
        }
        
        // Función para mostrar modal de cotización requerida
        function mostrarModalCotizacionRequerida() {
            Swal.fire({
                icon: 'warning',
                title: ' Cotización Requerida',
                text: 'Por favor selecciona una cotización antes de agregar ítems al pedido',
                confirmButtonText: 'Entendido',
                confirmButtonColor: '#3b82f6',
                backdrop: 'rgba(0, 0, 0, 0.1)',
                showClass: {
                    popup: 'animate__animated animate__fadeInDown'
                },
                hideClass: {
                    popup: 'animate__animated animate__fadeOutUp'
                }
            });
        }
        
        // Manejar clic en el botón de agregar prenda
        const btnAgregarPrenda = document.getElementById('btn-agregar-prenda');
        if (btnAgregarPrenda) {
            btnAgregarPrenda.addEventListener('click', function() {
                console.log('🟢 [btn-agregar-prenda] Clic detectado');
                
                if (!verificarCotizacionSeleccionada()) {
                    console.log(' [btn-agregar-prenda] No hay cotización seleccionada');
                    mostrarModalCotizacionRequerida();
                    return;
                }
                
                console.log(' [btn-agregar-prenda] Cotización seleccionada, abriendo selector de prendas');
                if (typeof window.abrirSelectorPrendasCotizacion === 'function') {
                    // Obtener la cotización seleccionada (usar la variable correcta)
                    const cotizacionSeleccionada = window.cotizacionSeleccionadaActual || window.cotizacionSeleccionada;
                    console.log('🔍 [btn-agregar-prenda] Variable cotizacionSeleccionada:', cotizacionSeleccionada);
                    
                    if (cotizacionSeleccionada && cotizacionSeleccionada.id) {
                        console.log(' [btn-agregar-prenda] ID de cotización encontrado:', cotizacionSeleccionada.id);
                        window.abrirSelectorPrendasCotizacion(cotizacionSeleccionada);
                    } else {
                        console.error('❌ [btn-agregar-prenda] No hay cotización seleccionada o no tiene ID');
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'No se ha seleccionado ninguna cotización válida.',
                            confirmButtonText: 'Aceptar',
                            confirmButtonColor: '#ef4444'
                        });
                    }
                } else {
                    console.error('❌ [btn-agregar-prenda] La función abrirSelectorPrendasCotizacion no está disponible');
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'No se pudo abrir el selector de prendas. Intenta recargar la página.',
                        confirmButtonText: 'Aceptar',
                        confirmButtonColor: '#ef4444'
                    });
                }
            });
            console.log(' [btn-agregar-prenda] Event listener agregado correctamente');
        } else {
            console.warn(' [btn-agregar-prenda] Botón no encontrado');
        }
        
        // Manejar clic en el botón de agregar EPP
        const btnAgregarEPP = document.getElementById('btn-agregar-epp');
        if (btnAgregarEPP) {
            btnAgregarEPP.addEventListener('click', function() {
                console.log('🟢 [btn-agregar-epp] Clic detectado');
                
                console.log(' [btn-agregar-epp] Abriendo modal EPP (no requiere cotización)');
                if (typeof window.abrirModalAgregarEPP === 'function') {
                    window.abrirModalAgregarEPP();
                } else {
                    console.error('❌ [btn-agregar-epp] La función abrirModalAgregarEPP no está disponible');
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'No se pudo abrir el modal de EPP. Intenta recargar la página.',
                        confirmButtonText: 'Aceptar',
                        confirmButtonColor: '#ef4444'
                    });
                }
            });
            console.log(' [btn-agregar-epp] Event listener agregado correctamente');
        } else {
            console.warn(' [btn-agregar-epp] Botón no encontrado');
        }
    });
</script>

<!-- Script para Vista Previa en Vivo de Factura -->
<script src="{{ asset('js/invoice-preview-live.js') }}?v={{ time() }}&t={{ uniqid() }}"></script>

<!-- Script para interactividad de item-cards -->
<script src="{{ asset('js/modulos/crear-pedido/components/item-card-interactions.js') }}"></script>

<!-- Script para modal de prendas y autocomplete -->
<script src="{{ asset('js/componentes/prenda-editor-modal.js') }}?v={{ time() }}"></script>

<!-- Script para ocultar loading cuando todo está listo -->
<script>
    // Esperar a que DOM esté completamente listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            ocultarLoadingConDelay();
        });
    } else {
        ocultarLoadingConDelay();
    }

    function ocultarLoadingConDelay() {
        // Dar un pequeño delay para asegurar que todos los scripts se han cargado
        setTimeout(() => {
            const loadingOverlay = document.getElementById('loading-overlay');
            if (loadingOverlay) {
                loadingOverlay.style.transition = 'opacity 0.5s ease-out';
                loadingOverlay.style.opacity = '0';
                
                setTimeout(() => {
                    if (loadingOverlay) {
                        loadingOverlay.style.display = 'none';
                    }
                }, 500);
                
                console.log(' [LOADING] Loading overlay ocultado');
            }
        }, 300);
    }

    // Usar window.addEventListener('load') como fallback
    window.addEventListener('load', function() {
        console.log('📦 [LOADING] Evento load disparado');
        const loadingOverlay = document.getElementById('loading-overlay');
        if (loadingOverlay && loadingOverlay.style.display !== 'none') {
            setTimeout(() => {
                if (loadingOverlay) {
                    loadingOverlay.style.display = 'none';
                }
            }, 100);
        }
    });
</script>
@endpush
@endsection
