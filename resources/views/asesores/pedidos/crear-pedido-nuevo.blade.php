@extends('layouts.asesores')

@section('body-class', 'crear-pedido-view')

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
            Editando pedido de: {{ $pedido->cliente_nombre_display ?? 'Pedido #'.$pedidoEditarId }}
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
        window.pedidoEditarData = {{ Js::from([
            'pedido' => [
                'id' => $pedido->id ?? null,
                'numero_pedido' => $pedido->numero_pedido ?? null,
                'orden_compra' => $pedido->orden_compra ?? '',
                'cliente' => $pedido->cliente_nombre_display ?? '',
                'forma_de_pago' => $pedido->forma_de_pago ?? '',
                'dia_de_entrega' => $pedido->dia_de_entrega ?? null,
                'fecha_estimada_de_entrega' => $pedido->fecha_estimada_de_entrega
                    ?? $pedido->fecha_estimada_entrega
                    ?? $pedido->fecha_estimada
                    ?? null,
                'observaciones' => $pedido->observaciones ?? '',
                'estado' => $pedido->estado ?? '',
                // Contrato explícito: payload ya mapeado por MapearPedidoEdicionService.
                // No remapear aquí para evitar pérdidas de campos del flujo talla_color.
                'prendas' => ($pedido->prendas ?? collect())->values()->toArray(),
            ],
            'epps' => $epps ?? [],
            'estados' => $estados ?? [],
            'areas' => $areas ?? []
        ]) }};

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
                    <input type="text" id="cliente_editable" name="cliente" list="clientes-editable-list" value="{{ ($modoEdicion ?? false) ? ($pedido->cliente_nombre_display ?? '') : '' }}">
                    <datalist id="clientes-editable-list"></datalist>
                </div>

                <div class="form-group">
                    <label for="orden_compra_editable">Orden de Compra</label>
                    <input type="text" id="orden_compra_editable" name="orden_compra" value="{{ ($modoEdicion ?? false) ? ($pedido->orden_compra ?? '') : '' }}" placeholder="Opcional: Ingrese el número de orden de compra">
                </div>

                <div class="form-group">
                    <label for="asesora_editable">Asesora</label>
                    <input type="text" id="asesora_editable" name="asesora" readonly value="{{ auth()->user()->name }}">
                </div>

                <div class="form-group">
                    <label for="forma_de_pago_editable">Forma de Pago</label>
                    <input type="text" id="forma_de_pago_editable" name="forma_de_pago" value="{{ ($modoEdicion ?? false) ? ($pedido->forma_de_pago ?? '') : '' }}">
                </div>

                <div class="form-group">
                    <label for="dia_de_entrega_editable">Dias de Entrega</label>
                    <select id="dia_de_entrega_editable" name="dia_de_entrega">
                        <option value="">Selecciona dias</option>
                        <option value="15" {{ (($modoEdicion ?? false) && (int)($pedido->dia_de_entrega ?? 0) === 15) ? 'selected' : '' }}>15 dias</option>
                        <option value="20" {{ (($modoEdicion ?? false) && (int)($pedido->dia_de_entrega ?? 0) === 20) ? 'selected' : '' }}>20 dias</option>
                        <option value="25" {{ (($modoEdicion ?? false) && (int)($pedido->dia_de_entrega ?? 0) === 25) ? 'selected' : '' }}>25 dias</option>
                        <option value="30" {{ (($modoEdicion ?? false) && (int)($pedido->dia_de_entrega ?? 0) === 30) ? 'selected' : '' }}>30 dias</option>
                    </select>
                </div>
            </div>

            <div style="width: 100%; margin-top: 1rem;">
                <div class="form-group">
                    <label for="observaciones_editable">Observaciones</label>
                    <textarea id="observaciones_editable" name="observaciones" rows="3" placeholder="Agrega cualquier observación adicional sobre el pedido...">{{ ($modoEdicion ?? false) ? ($pedido->observaciones ?? '') : '' }}</textarea>
                </div>
            </div>
            <div style="width: 100%; margin-top: 0.5rem;">
                <small id="fecha_estimada_preview" style="color:#2563eb;font-weight:600;"></small>
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
            <button type="button" id="btn-guardar-borrador" class="btn btn-warning" style="background: linear-gradient(135deg, #fb923c 0%, #ea580c 100%); color: white; border: none; padding: 10px 16px; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 0.9rem; transition: all 0.3s; box-shadow: 0 2px 4px rgba(251, 146, 60, 0.2); display: flex; align-items: center; justify-content: center; gap: 0.5rem;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 8px rgba(251, 146, 60, 0.3)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 4px rgba(251, 146, 60, 0.2)'">
                <span class="material-symbols-rounded" style="font-size: 1.1rem;">save</span>
                Guardar Borrador
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
@include('asesores.pedidos.modals.modal-selector-modo-proceso')
@include('shared.pedidos.modals.modal-proceso-por-tallas')
@include('asesores.pedidos.modals.modal-proceso-generico')
@include('asesores.pedidos.modals.modal-confirmar-eliminar-imagen-proceso')
@include('shared.pedidos.modals.modal-agregar-editar-epp')
@include('asesores.pedidos.modals.modal-editar-epp')

@endsection

@push('scripts')
    {{-- Scripts individuales con defer — Nginx + HTTP/2 sirve en paralelo real --}}
    {{-- En producción, js_asset() carga .min.js automáticamente si existe --}}

    @php $v = config('app.asset_version'); @endphp

    <!--  Logger centralizado (DEBE cargar ANTES de cualquier servicio) -->
    <script defer src="{{ js_asset('js/utilidades/logger-app.js') }}?v={{ $v }}"></script>

    <!-- ─── Shared Services ─── -->
    <script defer src="{{ js_asset('js/servicios/shared/event-bus.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/servicios/shared/format-detector.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/servicios/shared/shared-prenda-validation-service.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/servicios/shared/shared-prenda-data-service.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/servicios/shared/shared-prenda-storage-service.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/servicios/shared/shared-prenda-editor-service.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/servicios/shared/prenda-service-container.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/servicios/shared/initialization-helper.js') }}?v={{ $v }}"></script>

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
    <script defer src="{{ js_asset('js/modulos/crear-pedido/utils/local-id.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/seguridad/pedido-state-guard.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/configuraciones/constantes-tallas.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/modales/modales-dinamicos.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/services/epp/EppHttpService.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/fotos/image-storage-service.js') }}?v={{ $v }}"></script>
    <!-- ✅ NUEVO: IndexedImageStorageService para prevenir desincronización de imágenes entre prendas -->
    <script defer src="{{ js_asset('js/modulos/crear-pedido/fotos/indexed-image-storage-service.js') }}?v={{ $v }}"></script>

    <!-- ─── Inicialización de Image Storage (Fase 3) ─── -->
    <script defer src="{{ js_asset('js/modulos/crear-pedido/inicializacion/image-storage-init.js') }}?v={{ $v }}"></script>

    <!-- ─── EPP Services ─── -->
    <script defer src="{{ js_asset('js/modulos/crear-pedido/epp/services/epp-api-service.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/epp/services/epp-state-manager.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/epp/services/epp-modal-manager.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/epp/services/epp-item-manager-tabla.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/epp/services/epp-imagen-manager.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/epp/services/epp-service.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/epp/services/epp-notification-service.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/epp/services/epp-creation-service.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/epp/services/epp-form-manager.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/epp/templates/epp-modal-template.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/epp/interfaces/epp-modal-interface.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/epp/epp-init.js') }}?v={{ $v }}"></script>

    <!-- ─── Core: tallas, telas, utilidades, procesos ─── -->
    <script defer src="{{ js_asset('js/modulos/crear-pedido/tallas/gestion-tallas.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/telas/telas-module/telas-module-main.js') }}?v={{ time() }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/procesos/gestion-items-pedido-constantes.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/utilidades/dom-utils.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/utilidades/modal-cleanup.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/utilidades/tela-processor.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/utilidades/prenda-data-builder.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/utilidades/validador-prenda.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/procesos/manejadores-procesos-prenda.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/procesos/proceso-modal-state.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/procesos/gestor-modal-proceso-generico.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/procesos/proceso-modal-imagenes.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/procesos/proceso-modal-tallas.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/procesos/proceso-modal-persistencia.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/procesos/proceso-modal-controller.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/procesos/selector-modo-proceso.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/procesos/proceso-por-tallas-state.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/procesos/proceso-por-tallas-render-events.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/procesos/proceso-por-tallas-persist-controller.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/procesos/extension-editor-tallas-multiproducto.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/procesos/extension-guardar-datos-tallas-extendida.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/procesos/proceso-galeria-service.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/procesos/proceso-delete-service.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/procesos/proceso-modal-loader-service.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/procesos/proceso-card-renderer-service.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/procesos/renderizador-tarjetas-procesos.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/componentes/procesos-imagenes-storage.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/componentes/manejador-imagen-proceso-con-indice.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/telas/telas-module/manejo-imagenes.js') }}?v={{ $v }}"></script>

    <!-- ESTILOS del componente tarjeta readonly -->
    <link rel="stylesheet" href="{{ asset('css/componentes/prenda-card-readonly.css') }}?v={{ $v }}">
    <link rel="stylesheet" href="{{ asset('css/componentes/epp-card.css') }}">

    <!-- EPP Services exclusivos para vista de nuevo pedido (solo los que no están duplicados) -->
    <script src="{{ asset('js/modulos/crear-pedido/epp/services/epp-item-manager-tarjeta.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/epp/services/epp-menu-handler-base.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/epp/services/epp-menu-handlers-tarjeta.js') }}?v={{ time() }}"></script>
    
    <!-- Inicializar EPP Menu Handlers para vista nuevo pedido -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('[crear-pedido-nuevo] Inicializando EPP Menu Handlers...');
            
            // El handler se instancia automáticamente en el archivo JS
            if (typeof window.eppMenuHandlerTarjeta !== 'undefined') {
                console.log('[crear-pedido-nuevo] EPP Menu Handlers inicializado correctamente');
            } else {
                console.error('[crear-pedido-nuevo] eppMenuHandlerTarjeta no está disponible');
            }
        });
    </script>

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
    <script defer src="{{ js_asset('js/componentes/prenda-form-collector.js') }}?v={{ time() }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/procesos/pedido-items-state.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/procesos/gestion-items-pedido-core-services.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/procesos/prenda-modal-service.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/procesos/prenda-flow-service.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/procesos/epp-flow-service.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/procesos/items-sync-service.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/procesos/item-removal-service.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/procesos/gestion-items-pedido.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/procesos/proceso-modal-edicion-adapter.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/modales/modal-seleccion-prendas.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/componentes/prendas-wrappers.js') }}?v={{ $v }}"></script>

    <!-- ─── Validación y envío ─── -->
    <script defer src="{{ js_asset('js/modulos/crear-pedido/validacion/validacion-envio-fase3.js') }}?v={{ $v }}"></script>

    <!-- ─── Gestores, Builders, Card Services ─── -->
    <script defer src="{{ js_asset('js/modulos/crear-pedido/configuracion/api-pedidos.js') }}?v={{ $v }}"></script>
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
    <script defer src="{{ js_asset('js/componentes/services/prenda-card-context.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/componentes/services/prenda-card-normalizers.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/componentes/services/prenda-card-renderers.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/componentes/services/prenda-card-data-utils.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/componentes/services/prenda-card-variations-service.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/componentes/services/prenda-card-sizing-service.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/componentes/services/prenda-card-process-service.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/componentes/services/prenda-card-service.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/componentes/services/prenda-card-handlers.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/componentes/prenda-card-readonly.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/componentes/modal-prenda-dinamico-constantes.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/componentes/modal-prenda-dinamico.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/componentes/prenda-card-editar-simple.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/edicion/draft-pedido-serializer.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/edicion/draft-pedido-serializer-helpers.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/edicion/draft-pedido-builder.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/edicion/draft-pedido-save-service.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/edicion/draft-pedido-orchestrator.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/edicion/draft-pedido-unsaved-changes.js') }}?v={{ $v }}"></script>
    @if($modoEdicion ?? false)
    <script defer src="{{ js_asset('js/modulos/crear-pedido/edicion/cargar-datos-edicion-nuevo.js') }}?v={{ $v }}"></script>
    @endif

    <!-- ─── Inicialización UI: Formatters, Buttons, Dropdowns, Handlers ─── -->
    <script defer src="{{ js_asset('js/modulos/crear-pedido/inicializacion/input-formatter-init.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/inicializacion/cliente-autocomplete-init.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/inicializacion/leave-button-setup.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/inicializacion/items-dropdown-init.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/inicializacion/item-type-handlers.js') }}?v={{ $v }}"></script>

<script>
    window.routeGuardarBorradorUrl = '{{ url("/api/asesores/pedidos/borrador") }}';
    window.routePedidosIndexUrl = '{{ route("asesores.pedidos.index") }}';
    window.asesorActualNombre = '{{ Auth::user()->name ?? '' }}';

    document.addEventListener('DOMContentLoaded', function() {
        console.log('[crear-pedido-nuevo] Inicializando componentes de la página...');
        
        // Inicializar image storage (Fase 3)
        if (typeof InitializeImageStorages === 'function') {
            InitializeImageStorages();
        }
        
        // Inicializar componentes modularizados (Fase 2 - Refactoring)
        if (typeof InitializeInputFormatters === 'function') {
            InitializeInputFormatters();
        }
        
        if (typeof InitializeLeaveButtons === 'function') {
            InitializeLeaveButtons();
        }
        
        if (typeof InitializeItemsDropdown === 'function') {
            InitializeItemsDropdown();
        }
        
        if (typeof InitializeItemTypeHandlers === 'function') {
            InitializeItemTypeHandlers();
        }
        
        console.log('[crear-pedido-nuevo] Componentes inicializados ✓');
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const selectDias = document.getElementById('dia_de_entrega_editable');
        const previewFecha = document.getElementById('fecha_estimada_preview');
        const actualizarFechaEstimada = async () => {
            if (!selectDias || !previewFecha) return;
            const dias = parseInt(selectDias.value || '', 10);
            if (!dias) {
                previewFecha.textContent = '';
                return;
            }
            previewFecha.textContent = 'Calculando fecha estimada...';
            try {
                const res = await fetch('/api/registros/calcular-fecha-estimada-preview', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    },
                    body: JSON.stringify({ dia_de_entrega: dias }),
                });
                const data = await res.json();
                if (res.ok && data?.success && data?.fecha_estimada) {
                    previewFecha.textContent = `Fecha estimada de entrega: ${data.fecha_estimada}`;
                    return;
                }
                previewFecha.textContent = '';
            } catch (e) {
                previewFecha.textContent = '';
            }
        };
        selectDias?.addEventListener('change', actualizarFechaEstimada);
        actualizarFechaEstimada();
    });
</script>

<!-- Script para manejar Guardar Borrador -->
<script>
    // Asignar evento al botón cuando se cargue el DOM
    document.addEventListener('DOMContentLoaded', function() {
        if (window.DraftPedidoOrchestrator && typeof window.DraftPedidoOrchestrator.registrarBotonGuardarBorrador === 'function') {
            window.DraftPedidoOrchestrator.registrarBotonGuardarBorrador();
        }
    });
</script>

<!-- ─── Final UI Scripts ─── -->
<script defer src="{{ js_asset('js/modulos/crear-pedido/components/item-card-interactions.js') }}?v={{ $v }}"></script>
<script defer src="{{ js_asset('js/componentes/prenda-editor-modal.js') }}?v={{ $v }}"></script>

<!--  TEST SUITE: Solo en desarrollo -->
@if(config('app.debug'))
<script defer src="{{ js_asset('js/tests/prenda-editor-test.js') }}?v={{ $v }}"></script>
@endif
@endpush
