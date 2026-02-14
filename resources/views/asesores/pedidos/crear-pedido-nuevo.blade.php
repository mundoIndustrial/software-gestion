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
                    <button type="button" id="btn-agregar-item-tipo-inline" style="display: none; padding: 0.75rem 1.25rem; background: linear-gradient(135deg, #0066cc 0%, #0052a3 100%); color: white; border: none; border-radius: 6px; font-size: 0.9rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 0.5rem; transition: all 0.3s; white-space: nowrap; box-shadow: 0 2px 4px rgba(0, 102, 204, 0.2); height: 42px; margin-top: 26px;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 8px rgba(0, 102, 204, 0.3)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 4px rgba(0, 102, 204, 0.2)'">
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

            <!-- Lista de ítems genéricos (readonly, después de agregar) -->
            <div id="lista-items-pedido" style="display: flex; flex-direction: column; gap: 0.75rem;">
                <!-- Los ítems se agregarán aquí dinámicamente por ItemRenderer -->
            </div>

            <!-- Formulario de entrada para prendas sin cotización (durante creación) -->
            <div id="prendas-container-editable" style="margin-top: 1.5rem; display: none;">
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
    {{-- Scripts individuales con defer — Nginx + HTTP/2 sirve en paralelo real --}}
    {{-- En producción, js_asset() carga .min.js automáticamente si existe --}}

    @php $v = config('app.asset_version'); @endphp

    <!-- ─── Shared Services ─── -->
    <script defer src="{{ js_asset('js/servicios/shared/event-bus.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/servicios/shared/format-detector.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/servicios/shared/shared-prenda-validation-service.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/servicios/shared/shared-prenda-data-service.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/servicios/shared/shared-prenda-storage-service.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/servicios/shared/shared-prenda-editor-service.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/servicios/shared/prenda-service-container.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/servicios/shared/initialization-helper.js') }}?v={{ $v }}"></script>

    @if(config('app.debug'))
    <script defer src="{{ js_asset('js/servicios/shared/system-validation-test.js') }}?v=1"></script>
    @endif

    <!-- Inicializar contenedor de servicios (defer-compatible) -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            PrendasEditorHelper.inicializar().catch(err => {
                console.error('[crear-nuevo] Error:', err);
            });
        });
    </script>

    <!-- ─── Config, Security, Constants, Image Storage ─── -->
    <script defer src="{{ js_asset('js/modulos/crear-pedido/seguridad/protector-datos-principales.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/configuraciones/constantes-tallas.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/modales/modales-dinamicos.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/services/epp/EppHttpService.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/fotos/image-storage-service.js') }}?v={{ $v }}"></script>

    <!-- Inicializar storages cuando scripts defer hayan cargado -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (!window.imagenesPrendaStorage) {
                window.imagenesPrendaStorage = new ImageStorageService(3);
            }
            if (!window.imagenesTelaStorage) {
                window.imagenesTelaStorage = new ImageStorageService(3);
            }
            if (!window.imagenesReflectivoStorage) {
                window.imagenesReflectivoStorage = new ImageStorageService(3);
            }
        });
    </script>

    <!-- ─── EPP Services ─── -->
    <script defer src="{{ js_asset('js/modulos/crear-pedido/epp/services/epp-api-service.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/epp/services/epp-state-manager.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/epp/services/epp-modal-manager.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/epp/services/epp-item-manager.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/epp/services/epp-imagen-manager.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/epp/services/epp-service.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/epp/services/epp-notification-service.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/epp/services/epp-creation-service.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/epp/services/epp-form-manager.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/epp/services/epp-menu-handlers.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/epp/templates/epp-modal-template.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/epp/interfaces/epp-modal-interface.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/epp/epp-init.js') }}?v={{ $v }}"></script>

    <!-- ─── Core: tallas, telas, utilidades, procesos ─── -->
    <script defer src="{{ js_asset('js/modulos/crear-pedido/tallas/gestion-tallas.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/telas/gestion-telas.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/procesos/gestion-items-pedido-constantes.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/utilidades/dom-utils.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/utilidades/modal-cleanup.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/utilidades/tela-processor.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/utilidades/prenda-data-builder.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/utilidades/logger-app.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/utilidades/validador-prenda.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/procesos/manejadores-procesos-prenda.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/procesos/gestor-modal-proceso-generico.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/procesos/renderizador-tarjetas-procesos.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/componentes/procesos-imagenes-storage.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/componentes/manejo-imagenes-proceso.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/componentes/FileDialogStateManager.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/componentes/manejador-imagen-proceso-con-indice-v2.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/telas/telas-module/manejo-imagenes.js') }}?v={{ $v }}"></script>

    <!-- ESTILOS del componente tarjeta readonly -->
    <link rel="stylesheet" href="{{ asset('css/componentes/prenda-card-readonly.css') }}?v={{ $v }}">
    <link rel="stylesheet" href="{{ asset('css/componentes/epp-card.css') }}">

    <!-- ─── Prendas Services ─── -->
    <script defer src="{{ js_asset('js/modulos/crear-pedido/prendas/proceso-editor.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/prendas/gestor-edicion-procesos.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/prendas/servicio-procesos.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/prendas/middleware-guardado-prenda.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/prendas/notification-service.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/prendas/payload-normalizer.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/prendas/item-api-service.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/prendas/item-validator.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/prendas/item-form-collector.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/prendas/item-renderer.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/prendas/prenda-editor.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/prendas/prenda-editor-init.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/prendas/item-orchestrator.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/componentes/prenda-form-collector.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/procesos/gestion-items-pedido.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/modales/modal-seleccion-prendas.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/componentes/prendas-wrappers.js') }}?v={{ $v }}"></script>

    <!-- ─── Gestores, Builders, Card Services ─── -->
    <script defer src="{{ js_asset('js/modulos/crear-pedido/configuracion/api-pedidos-editable.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/fotos/manejador-fotos-prenda-edicion.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/fotos/galeria-imagenes-prenda.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/gestores/gestor-prenda-sin-cotizacion.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/prendas/inicializar-gestor.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/prendas/manejadores-variaciones.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/prendas/utils/prenda-data-transformer.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/prendas/builders/variaciones-builder.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/prendas/builders/tallas-builder.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/prendas/utils/image-processor.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/prendas/builders/procesos-builder.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/componentes/services/image-converter-service.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/componentes/services/prenda-card-service.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/componentes/services/prenda-card-handlers.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/componentes/prenda-card-readonly.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/componentes/modal-prenda-dinamico-constantes.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/componentes/modal-prenda-dinamico.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/componentes/prenda-card-editar-simple.js') }}?v={{ $v }}"></script>

<script>
    window.asesorActualNombre = '{{ Auth::user()->name ?? '' }}';

    document.addEventListener('DOMContentLoaded', function() {
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
            btnAgregarItemTipoInline.addEventListener('click', function(e) {
                e.preventDefault();
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
                
                // Feedback visual: deshabilitar y mostrar estado cargando
                btnAgregarItemTipoInline.disabled = true;
                btnAgregarItemTipoInline.style.opacity = '0.6';
                const textoOriginal = btnAgregarItemTipoInline.innerHTML;
                btnAgregarItemTipoInline.innerHTML = '<span class="material-symbols-rounded" style="font-size: 1.25rem; animation: spin 0.8s linear infinite;">refresh</span>';
                
                // Auto-habilitar después de 600ms
                setTimeout(() => {
                    btnAgregarItemTipoInline.disabled = false;
                    btnAgregarItemTipoInline.style.opacity = '1';
                    btnAgregarItemTipoInline.innerHTML = textoOriginal;
                }, 600);
                
                // Manejar diferentes tipos de pedido
                if (tipoPedido === 'P') {
                    window.abrirModalPrendaNueva();
                } else if (tipoPedido === 'EPP') {
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
            const btnAgregarTipoInline = document.getElementById('btn-agregar-item-tipo-inline');
            if (btnAgregarTipoInline) {
                btnAgregarTipoInline.style.display = 'flex';
            }
        };
    });
</script>

<!-- Script para cargar datos en modo edición -->
@if($modoEdicion ?? false)
<script defer src="{{ js_asset('js/modulos/crear-pedido/edicion/cargar-datos-edicion-nuevo.js') }}?v={{ $v }}"></script>
@endif

<!-- Invoice Preview: Lazy-loaded cuando se necesite -->
<script>
    window._invoiceScriptsLoaded = false;
    window.cargarModulosInvoice = function() {
        if (window._invoiceScriptsLoaded) return Promise.resolve();
        return new Promise(function(resolve) {
            var scripts = [
                '{{ asset("js/modulos/invoice/ImageGalleryManager.js") }}?v={{ $v }}',
                '{{ asset("js/modulos/invoice/FormDataCaptureService.js") }}?v={{ $v }}',
                '{{ asset("js/modulos/invoice/InvoiceRenderer.js") }}?v={{ $v }}',
                '{{ asset("js/modulos/invoice/ModalManager.js") }}?v={{ $v }}',
                '{{ asset("js/modulos/invoice/InvoiceExportService.js") }}?v={{ $v }}',
                '{{ asset("js/invoice-preview-live.js") }}?v={{ $v }}'
            ];
            var loaded = 0;
            scripts.forEach(function(src) {
                var s = document.createElement('script');
                s.src = src;
                s.onload = function() { if (++loaded === scripts.length) { window._invoiceScriptsLoaded = true; resolve(); } };
                document.head.appendChild(s);
            });
        });
    };
</script>

<!-- ─── Final UI Scripts ─── -->
<script defer src="{{ js_asset('js/modulos/crear-pedido/components/item-card-interactions.js') }}?v={{ $v }}"></script>
<script defer src="{{ js_asset('js/componentes/prenda-editor-modal.js') }}?v={{ $v }}"></script>

<!-- 🧪 TEST SUITE: Solo en desarrollo -->
@if(config('app.debug'))
<script defer src="{{ js_asset('js/tests/prenda-editor-test.js') }}?v={{ $v }}"></script>
@endif
@endpush

