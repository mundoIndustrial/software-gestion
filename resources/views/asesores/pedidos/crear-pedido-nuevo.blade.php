@extends('layouts.asesores')

@section('extra_styles')
    <link rel="stylesheet" href="{{ asset('css/crear-pedido.css') }}">
    <link rel="stylesheet" href="{{ asset('css/crear-pedido-editable.css') }}">
    <link rel="stylesheet" href="{{ asset('css/form-modal-consistency.css') }}">
    <link rel="stylesheet" href="{{ asset('css/swal-z-index-fix.css') }}">
    <link rel="stylesheet" href="{{ asset('css/componentes/prendas.css') }}">
    <link rel="stylesheet" href="{{ asset('css/modales/modal-exito-pedido.css') }}">
    <link rel="stylesheet" href="{{ asset('css/modulos/epp-modal.css') }}">
@endsection

@section('content')

<!-- Header Full Width -->
<div class="page-header">
    <h1>
        <span class="material-symbols-rounded" style="vertical-align: middle; margin-right: 8px;">description</span>
        @if($modoEdicion ?? false)
            Editar Pedido #{{ $pedidoEditarId }}
        @else
            Crear Nuevo Pedido de Producción
        @endif
    </h1>
    <p>@if($modoEdicion ?? false)
        Edita los detalles del pedido
    @else
        Crea un pedido completamente nuevo sin una cotización previa
    @endif</p>
</div>

@if($modoEdicion ?? false)
    <script>
        window.modoEdicion = true;
        window.pedidoEditarId = {{ $pedidoEditarId }};
        // Pasar datos completos con pedido, prendas, EPPs, etc.
        window.pedidoEditarData = {!! json_encode([
            'pedido' => $pedido ?? [],
            'epps' => $epps ?? [],
            'estados' => $estados ?? [],
            'areas' => $areas ?? []
        ]) !!};

    </script>
@endif

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
                    <input type="text" id="cliente_editable" name="cliente" value="{{ $pedido->cliente ?? '' }}">
                </div>

                <div class="form-group">
                    <label for="asesora_editable">Asesora</label>
                    <input type="text" id="asesora_editable" name="asesora" readonly value="{{ auth()->user()->name }}">
                </div>

                <div class="form-group">
                    <label for="forma_de_pago_editable">Forma de Pago</label>
                    <input type="text" id="forma_de_pago_editable" name="forma_de_pago" value="{{ $pedido->forma_de_pago ?? '' }}">
                </div>

                <div class="form-group">
                    <label for="numero_pedido_editable">Número de Pedido</label>
                    <input type="text" id="numero_pedido_editable" name="numero_pedido" readonly placeholder="Se asignará automáticamente" style="background-color: #f3f4f6; cursor: not-allowed;">
                </div>
            </div>
        </div>

        <!-- PASO 2: Tipo de Ítem -->
        <div class="form-section">
            <h2>
                <span>2</span> Selecciona el Tipo de Ítem
            </h2>

            <div class="form-group" style="margin-bottom: 2rem;">
                <div style="display: flex; gap: 1rem; align-items: stretch;">
                    <div class="form-group" style="flex: 1; display: flex; flex-direction: column;">
                        <label for="tipo_pedido_nuevo" class="block text-sm font-medium text-gray-700 mb-2">
                            Tipo de Ítem
                        </label>
                        <!-- Loading State -->
                        <div id="tipo-pedido-loading" style="display: flex; align-items: center; gap: 0.75rem; padding: 1rem; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px;">
                            <div style="width: 20px; height: 20px; border: 3px solid #e5e7eb; border-top-color: #0066cc; border-radius: 50%; animation: spin 0.8s linear infinite;"></div>
                            <span style="color: #6b7280; font-size: 0.875rem;">Cargando opciones...</span>
                        </div>
                        <!-- Select (oculto inicialmente) -->
                        <select id="tipo_pedido_nuevo" name="tipo_pedido_nuevo" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" onchange="manejarCambiaTipoPedido()" style="display: none;" disabled>
                            <option value="">-- Selecciona un ítem --</option>
                            <option value="P">PRENDA</option>
                            <option value="EPP">EPP</option>
                        </select>
                    </div>
                    <button type="button" id="btn-agregar-item-tipo-inline" style="display: none; padding: 0.75rem 1.25rem; background: linear-gradient(135deg, #0066cc 0%, #0052a3 100%); color: white; border: none; border-radius: 6px; font-size: 0.9rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 0.5rem; transition: all 0.3s; white-space: nowrap; box-shadow: 0 2px 4px rgba(0, 102, 204, 0.2); height: 42px; margin-top: 26px;" onclick="abrirModalSegunTipo()" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 8px rgba(0, 102, 204, 0.3)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 4px rgba(0, 102, 204, 0.2)'">
                        <span class="material-symbols-rounded" style="font-size: 1.25rem;">add_circle</span>
                        Agregar
                    </button>
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

            <!-- Lista de ítems genéricos -->
            <div id="lista-items-pedido" style="display: flex; flex-direction: column; gap: 0.75rem;">
                <!-- Los ítems se agregarán aquí dinámicamente -->
            </div>

            <!-- Prendas del Pedido (dentro de Ítems del Pedido) -->
            <div id="prendas-container-editable" style="margin-top: 1.5rem;">
                <div class="empty-state">
                    <p>Agrega ítems al pedido</p>
                </div>
            </div>

        </div>

        <!-- PASO 5: Botones de Acción -->
        <div class="btn-actions">
            <button type="submit" id="btn-submit" class="btn btn-primary" style="display: none; background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); color: white; border: none; padding: 10px 16px; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 0.9rem; transition: all 0.3s; box-shadow: 0 2px 4px rgba(59, 130, 246, 0.2); display: flex; align-items: center; justify-content: center; gap: 0.5rem;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 8px rgba(59, 130, 246, 0.3)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 4px rgba(59, 130, 246, 0.2)'">
                <span class="material-symbols-rounded" style="font-size: 1.1rem;">check_circle</span>
                Crear Pedido
            </button>
            <a href="{{ route('asesores.pedidos.index') }}" class="btn btn-secondary" style="display: flex; align-items: center; gap: 0.5rem;">
                <span class="material-symbols-rounded" style="font-size: 1.1rem;">close</span>
                Cancelar
            </a>
        </div>
    </form>
</div>

@include('asesores.pedidos.modals.modal-seleccionar-prendas')
@include('asesores.pedidos.modals.modal-seleccionar-tallas')
@include('asesores.pedidos.modals.modal-agregar-prenda-nueva')
@include('asesores.pedidos.modals.modal-proceso-generico')
@include('asesores.pedidos.modals.modal-agregar-epp')

@endsection

@push('scripts')
    <!-- 🆕 SERVICIOS COMPARTIDOS DE EDICIÓN DE PRENDAS (cargar PRIMERO) -->
    <script src="{{ asset('js/servicios/shared/event-bus.js') }}?v=1"></script>
    <script src="{{ asset('js/servicios/shared/format-detector.js') }}?v=1"></script>
    <script src="{{ asset('js/servicios/shared/shared-prenda-validation-service.js') }}?v=1"></script>
    <script src="{{ asset('js/servicios/shared/shared-prenda-data-service.js') }}?v=1"></script>
    <script src="{{ asset('js/servicios/shared/shared-prenda-storage-service.js') }}?v=1"></script>
    <script src="{{ asset('js/servicios/shared/shared-prenda-editor-service.js') }}?v=1"></script>
    <script src="{{ asset('js/servicios/shared/prenda-service-container.js') }}?v=1"></script>
    <script src="{{ asset('js/servicios/shared/initialization-helper.js') }}?v=1"></script>
    <script src="{{ asset('js/servicios/shared/system-validation-test.js') }}?v=1"></script>
    
    <!-- Inicializar contenedor de servicios -->
    <script>
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                PrendasEditorHelper.inicializar().catch(err => {
                    console.error('[crear-nuevo] Error:', err);
                });
            });
        } else {
            PrendasEditorHelper.inicializar().catch(err => {
                console.error('[crear-nuevo] Error:', err);
            });
        }
    </script>
    
    <!-- IMPORTANTE: Cargar PRIMERO el protector de datos principales -->
    <script src="{{ asset('js/modulos/crear-pedido/seguridad/protector-datos-principales.js') }}?v={{ time() }}"></script>
    
    <!-- IMPORTANTE: Cargar constantes PRIMERO -->
    <script src="{{ asset('js/configuraciones/constantes-tallas.js') }}?v={{ time() }}"></script>
    
    <!-- IMPORTANTE: Cargar módulos DESPUÉS de las constantes -->
    <script src="{{ asset('js/modulos/crear-pedido/modales/modales-dinamicos.js') }}?v={{ time() }}"></script>
    
    <!-- SERVICIO HTTP para EPP (DEBE cargarse ANTES del modal) -->
    <script src="{{ asset('js/services/epp/EppHttpService.js') }}?v={{ time() }}"></script>

    <!--  CRÍTICO: Cargar image-storage-service ANTES de gestion-telas.js -->
    <script src="{{ asset('js/modulos/crear-pedido/fotos/image-storage-service.js') }}?v={{ time() }}"></script>
    
    <!--  Inicializar storages INMEDIATAMENTE (ANTES de que se cargue gestion-telas.js) -->
    <script>
        if (!window.imagenesPrendaStorage) {
            window.imagenesPrendaStorage = new ImageStorageService(3);
        }
        if (!window.imagenesTelaStorage) {
            window.imagenesTelaStorage = new ImageStorageService(3);
        }
        if (!window.imagenesReflectivoStorage) {
            window.imagenesReflectivoStorage = new ImageStorageService(3);
        }
    </script>
    
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
    
    <!-- Modal EPP se incluye como Blade template (línea 166) -->
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
    
    <!-- Manejadores de procesos - DEBEN cargarse ANTES de prenda-editor.js -->
    <script src="{{ asset('js/modulos/crear-pedido/procesos/manejadores-procesos-prenda.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/procesos/gestor-modal-proceso-generico.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/procesos/renderizador-tarjetas-procesos.js') }}?v={{ time() }}"></script>
    
    <!-- STORAGE Y MANEJO DE IMÁGENES DE PROCESOS -->
    <script src="{{ asset('js/componentes/procesos-imagenes-storage.js') }}"></script>
    <script src="{{ asset('js/componentes/manejo-imagenes-proceso.js') }}"></script>
    
    <!--  SERVICIOS EDICIÓN DINÁMICA DE PROCESOS - Deben cargarse PRIMERO -->
    <script src="{{ asset('js/modulos/crear-pedido/prendas/proceso-editor.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/prendas/gestor-edicion-procesos.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/prendas/servicio-procesos.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/prendas/middleware-guardado-prenda.js') }}?v={{ time() }}"></script>
    
    <!--  SERVICIOS SOLID - Deben cargarse ANTES de GestionItemsUI -->
    <script src="{{ asset('js/modulos/crear-pedido/prendas/notification-service.js') }}?v={{ time() }}"></script>
    
    <!-- PAYLOAD NORMALIZER v3 - VERSIÓN DEFINITIVA Y SEGURA -->
    <script src="{{ asset('js/modulos/crear-pedido/prendas/payload-normalizer.js') }}?v={{ time() }}"></script>
    
    <script src="{{ asset('js/modulos/crear-pedido/prendas/item-api-service.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/prendas/item-validator.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/prendas/item-form-collector.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/prendas/item-renderer.js') }}?v={{ time() }}"></script>
    <!-- 🆕 PrendaEditor sin legacy -->
    <script src="{{ asset('js/modulos/crear-pedido/prendas/prenda-editor.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/prendas/prenda-editor-init.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/prendas/item-orchestrator.js') }}?v={{ time() }}"></script>
    
    <!-- Componentes de Modales -->
    <script src="{{ asset('js/componentes/prenda-form-collector.js') }}?v={{ time() }}"></script>
    
    <script src="{{ asset('js/modulos/crear-pedido/procesos/gestion-items-pedido.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/modales/modal-seleccion-prendas.js') }}?v={{ time() }}"></script>
    
    <!-- Wrappers delegadores para prendas -->
    <script src="{{ asset('js/componentes/prendas-wrappers.js') }}?v={{ time() }}"></script>
    
    <!-- Cargar módulos de gestión de pedidos -->
    <script src="{{ asset('js/modulos/crear-pedido/configuracion/api-pedidos-editable.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/fotos/manejador-fotos-prenda-edicion.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/fotos/galeria-imagenes-prenda.js') }}?v={{ time() }}"></script>
    
    <!-- Gestor base (necesario para la clase GestorPrendaSinCotizacion) -->
    <script src="{{ asset('js/modulos/crear-pedido/gestores/gestor-prenda-sin-cotizacion.js') }}"></script>
    
    <!-- Inicializador del gestor -->
    <script src="{{ asset('js/modulos/crear-pedido/prendas/inicializar-gestor.js') }}"></script>
    
    <!-- Manejadores de variaciones -->
    <script src="{{ asset('js/modulos/crear-pedido/prendas/manejadores-variaciones.js') }}"></script>
    
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
    
    <!-- Modal de prendas - Constantes HTML (DEBE cargarse ANTES del modal principal) -->
    <script src="{{ asset('js/componentes/modal-prenda-dinamico-constantes.js') }}"></script>
    
    <!-- Modal de prendas - Clase principal (usa constantes) -->
    <script src="{{ asset('js/componentes/modal-prenda-dinamico.js') }}"></script>
    
    <!-- Edición simple de prendas (reutiliza factura) -->
    <script src="{{ asset('js/componentes/prenda-card-editar-simple.js') }}"></script>

<script>
    window.asesorActualNombre = '{{ Auth::user()->name ?? '' }}';

    document.addEventListener('DOMContentLoaded', function() {
        //  Storages ya inicializados en el script anterior (antes de cargar gestion-telas.js)
        
        // Configurar asesora
        document.getElementById('asesora_editable').value = '{{ Auth::user()->name ?? '' }}';
        
        // Mostrar botones
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
            }, 500);
        }

        // ========== GESTIÓN DE ÍTEMS ==========
        const selectTipoPedidoNuevo = document.getElementById('tipo_pedido_nuevo');
        const seccionItems = document.getElementById('seccion-items-pedido');
        
        if (seccionItems) {
            seccionItems.style.display = 'block';
        }

        // Agregar ítem de tipo nuevo desde el botón inline
        const btnAgregarItemTipoInline = document.getElementById('btn-agregar-item-tipo-inline');
        if (btnAgregarItemTipoInline) {
            btnAgregarItemTipoInline.addEventListener('click', function() {
                const tipoPedido = selectTipoPedidoNuevo.value;
                
                if (!tipoPedido) {
                    Swal.fire({
                        icon: 'warning',
                        title: ' Tipo de Ítem Requerido',
                        text: 'Por favor selecciona un ítem primero',
                        confirmButtonText: 'Entendido',
                        confirmButtonColor: '#0066cc'
                    });
                    return;
                }
                // Manejar diferentes tipos de pedido
                if (tipoPedido === 'P') {
                    // Prenda - incluye prendas, reflectivo, bordado, estampado, DTF, sublimado
                    window.abrirModalPrendaNueva();
                } else if (tipoPedido === 'EPP') {
                    // EPP - Equipo de Protección Personal
                    window.abrirModalAgregarEPP();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: ' Tipo Desconocido',
                        text: 'Tipo de pedido "' + tipoPedido + '" desconocido',
                        confirmButtonText: 'Aceptar',
                        confirmButtonColor: '#ef4444'
                    });
                }
            });
        }

        // Manejar cambio de tipo de pedido nuevo
        window.manejarCambiaTipoPedido = function() {
            const tipoPedido = selectTipoPedidoNuevo.value;
            
            if (!tipoPedido) return;
            // Mostrar botón inline
            const btnAgregarTipoInline = document.getElementById('btn-agregar-item-tipo-inline');
            if (btnAgregarTipoInline) {
                btnAgregarTipoInline.style.display = 'flex';
            }
        };

        // Abrir modal según tipo de pedido seleccionado
        window.abrirModalSegunTipo = function() {
            const tipoPedido = selectTipoPedidoNuevo.value;
            if (tipoPedido === 'EPP') {
                window.abrirModalAgregarEPP();
            } else if (tipoPedido === 'P') {
                // Prenda - abre el modal existente
                window.abrirModalPrendaNueva();
            }
        };
    });
</script>

<!-- Script para cargar datos en modo edición -->
@if($modoEdicion ?? false)
<script src="{{ asset('js/modulos/crear-pedido/edicion/cargar-datos-edicion-nuevo.js') }}?v={{ time() }}&t={{ uniqid() }}"></script>
@endif

<!-- Script para Vista Previa en Vivo de Factura - Módulos Desacoplados -->
<script src="{{ asset('js/modulos/invoice/ImageGalleryManager.js') }}?v={{ time() }}&t={{ uniqid() }}"></script>
<script src="{{ asset('js/modulos/invoice/FormDataCaptureService.js') }}?v={{ time() }}&t={{ uniqid() }}"></script>
<script src="{{ asset('js/modulos/invoice/InvoiceRenderer.js') }}?v={{ time() }}&t={{ uniqid() }}"></script>
<script src="{{ asset('js/modulos/invoice/ModalManager.js') }}?v={{ time() }}&t={{ uniqid() }}"></script>
<script src="{{ asset('js/modulos/invoice/InvoiceExportService.js') }}?v={{ time() }}&t={{ uniqid() }}"></script>
<script src="{{ asset('js/invoice-preview-live.js') }}?v={{ time() }}&t={{ uniqid() }}"></script>

<!-- Script para interactividad de item-cards -->
<script src="{{ asset('js/modulos/crear-pedido/components/item-card-interactions.js') }}"></script>

<!-- 🧪 TEST SUITE: Puedes ejecutar testPrendaEditor() en la consola -->
<script src="{{ asset('js/tests/prenda-editor-test.js') }}?v={{ time() }}"></script>

<!-- Script para modal de prendas y autocomplete -->
<script src="{{ asset('js/componentes/prenda-editor-modal.js') }}?v={{ time() }}"></script>

<!-- Script para drag & drop de procesos -->
<script src="{{ asset('js/componentes/drag-drop-procesos-estilo-prenda.js') }}?v={{ time() }}"></script>
@endpush
