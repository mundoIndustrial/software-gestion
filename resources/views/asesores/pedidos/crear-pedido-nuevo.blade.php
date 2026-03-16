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
            'pedido' => [
                'id' => $pedido->id ?? null,
                'numero_pedido' => $pedido->numero_pedido ?? null,
                'orden_compra' => $pedido->orden_compra ?? '',
                'cliente' => $pedido->cliente_nombre_display ?? '',
                'forma_de_pago' => $pedido->forma_de_pago ?? '',
                'observaciones' => $pedido->observaciones ?? '',
                'estado' => $pedido->estado ?? '',
                'prendas' => ($pedido->prendas ?? collect())->map(function($prenda) {
                    return [
                        'id' => $prenda['id'] ?? null,
                        'nombre_prenda' => $prenda['nombre'] ?? $prenda['nombre_prenda'] ?? '',
                        'descripcion' => $prenda['descripcion'] ?? '',
                        'de_bodega' => $prenda['de_bodega'] ?? 1,
                        'genero' => $prenda['genero'] ?? '',
                        'generosConTallas' => $prenda['generosConTallas'] ?? [],
                        'cantidadesPorTalla' => $prenda['cantidadesPorTalla'] ?? [],
                        'telasAgregadas' => $prenda['telasAgregadas'] ?? [],
                        'fotos' => collect($prenda['fotos'] ?? [])->map(fn($f) => [
                            'id' => $f['id'] ?? null,
                            'ruta_webp' => $f['url'] ?? $f['ruta_webp'] ?? $f['ruta'] ?? '',
                        ])->toArray(),
                        'procesos' => $prenda['procesos'] ?? [],
                        'variaciones' => $prenda['variaciones'] ?? [],
                    ];
                })->toArray(),
            ],
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
                    <input type="text" id="cliente_editable" name="cliente" value="{{ ($modoEdicion ?? false) ? ($pedido->cliente_nombre_display ?? '') : '' }}">
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
            </div>

            <div style="width: 100%; margin-top: 1rem;">
                <div class="form-group">
                    <label for="observaciones_editable">Observaciones</label>
                    <textarea id="observaciones_editable" name="observaciones" rows="3" placeholder="Agrega cualquier observación adicional sobre el pedido...">{{ ($modoEdicion ?? false) ? ($pedido->observaciones ?? '') : '' }}</textarea>
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
@include('asesores.pedidos.modals.modal-proceso-por-tallas')
@include('asesores.pedidos.modals.modal-proceso-generico')
@include('asesores.pedidos.modals.modal-confirmar-eliminar-imagen-proceso')
@include('asesores.pedidos.modals.modal-agregar-editar-epp')
@include('asesores.pedidos.modals.modal-editar-epp')

@endsection

@push('scripts')
    {{-- Scripts individuales con defer — Nginx + HTTP/2 sirve en paralelo real --}}
    {{-- En producción, js_asset() carga .min.js automáticamente si existe --}}

    @php $v = config('app.asset_version'); @endphp

    <!-- 🔧 Logger centralizado (DEBE cargar ANTES de cualquier servicio) -->
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
    <script defer src="{{ js_asset('js/modulos/crear-pedido/telas/gestion-telas.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/procesos/gestion-items-pedido-constantes.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/utilidades/dom-utils.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/utilidades/modal-cleanup.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/utilidades/tela-processor.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/utilidades/prenda-data-builder.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/utilidades/validador-prenda.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/procesos/manejadores-procesos-prenda.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/procesos/gestor-modal-proceso-generico.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/procesos/selector-modo-proceso.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/procesos/gestor-modal-proceso-por-tallas.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/procesos/extension-editor-tallas-multiproducto.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/procesos/extension-guardar-datos-tallas-extendida.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/procesos/renderizador-tarjetas-procesos.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/componentes/procesos-imagenes-storage.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/componentes/manejo-imagenes-proceso.js') }}?v={{ $v }}"></script>
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
    <script defer src="{{ js_asset('js/componentes/prenda-form-collector.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/procesos/gestion-items-pedido.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/modulos/crear-pedido/modales/modal-seleccion-prendas.js') }}?v={{ $v }}"></script>
    <script defer src="{{ js_asset('js/componentes/prendas-wrappers.js') }}?v={{ $v }}"></script>

    <!-- ─── Validación y envío ─── -->
    <script defer src="{{ js_asset('js/modulos/crear-pedido/validacion/validacion-envio-fase3.js') }}?v={{ $v }}"></script>

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
        
        // ========== CONFIGURAR INPUTS EN MAYÚSCULAS ==========
        function setupUpperCaseInput(inputId) {
            const input = document.getElementById(inputId);
            if (input) {
                console.log('🔤 Configurando input para mayúsculas:', inputId);
                
                // Función para convertir a mayúsculas preservando posición del cursor
                function forceUpperCase() {
                    const currentValue = input.value;
                    const upperValue = currentValue.toUpperCase();
                    if (currentValue !== upperValue) {
                        // Guardar posición del cursor
                        const start = input.selectionStart;
                        const end = input.selectionEnd;
                        
                        // Actualizar valor
                        input.value = upperValue;
                        
                        // Restaurar posición del cursor
                        input.setSelectionRange(start, end);
                        
                        console.log('🔤 Convertido a mayúsculas:', currentValue, '→', upperValue);
                    }
                }
                
                // Eventos para cubrir todos los casos
                input.addEventListener('input', forceUpperCase);
                input.addEventListener('keyup', forceUpperCase);
                input.addEventListener('change', forceUpperCase);
                input.addEventListener('paste', function(e) {
                    setTimeout(forceUpperCase, 10);
                });
                input.addEventListener('blur', forceUpperCase);
                
                // Convertir valor inicial si existe
                if (input.value) {
                    input.value = input.value.toUpperCase();
                    console.log('🔤 Valor inicial convertido:', input.value);
                }
                
                // Forzar mayúsculas cada segundo por si acaso
                const intervalId = setInterval(forceUpperCase, 1000);
                
                // Limpiar intervalo después de 10 segundos para no consumir recursos
                setTimeout(() => clearInterval(intervalId), 10000);
            } else {
                console.warn('⚠️ Input no encontrado:', inputId);
            }
        }
        
        // Aplicar a los inputs especificados
        setupUpperCaseInput('cliente_editable');
        setupUpperCaseInput('asesora_editable');
        setupUpperCaseInput('forma_de_pago_editable');
        setupUpperCaseInput('observaciones_editable');
        
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

<!-- Script para manejar Guardar Borrador -->
<script>
    /**
     * Función para enviar el formulario como BORRADOR
     * POST /asesores/pedidos-editable/borrador
     * 
     * A diferencia de la creación normal, el borrador:
     * - NO genera numero_pedido
     * - Se guarda con estado 'Borrador'
     * - Puede ser editado después
     */
    window.guardarComoBorrador = async function() {
        try {
            // Validar que hay al menos un ítem (prenda o EPP)
            const listaPrendas = document.getElementById('prendas-container-editable');
            const listaItems = document.getElementById('lista-items-pedido');
            
            const tienePrendas = listaPrendas && listaPrendas.querySelector('.prenda-item-card');
            const tieneItems = listaItems && listaItems.children.length > 0;
            
            if (!tienePrendas && !tieneItems) {
                Swal.fire({
                    icon: 'warning',
                    title: ' Pedido Vacío',
                    text: 'Agrega al menos una prenda o ítem EPP antes de guardar como borrador',
                    confirmButtonText: 'Entendido',
                    confirmButtonColor: '#fb923c'
                });
                return;
            }
            
            // Mostrar confirmación
            const result = await Swal.fire({
                icon: 'question',
                title: ' Guardar Borrador',
                html: `
                    <div style="text-align: left;">
                        <p style="margin-bottom: 10px;">
                            <strong>Este pedido se guardará como borrador</strong>
                        </p>
                        <ul style="margin: 10px 0; text-align: left; display: inline-block;">
                            <li>✓ No se asignará número de pedido</li>
                            <li>✓ Estado: Borrador</li>
                            <li>✓ Podrás editarlo después</li>
                        </ul>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonColor: '#fb923c',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Sí, guardar borrador',
                cancelButtonText: 'Cancelar'
            });
            
            if (!result.isConfirmed) {
                return;
            }
            
            // Mostrar loading
            Swal.fire({
                title: ' Guardando Borrador...',
                html: '<div style="text-align: center;"><div style="width: 50px; height: 50px; border: 4px solid #e5e7eb; border-top-color: #fb923c; border-radius: 50%; margin: 20px auto; animation: spin 0.8s linear infinite;"></div></div>',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: async () => {
                    try {
                        // Reutilizar la misma función que usa "Crear Pedido" para recopilar datos
                        const datos = (typeof window.prepararDatosParaEnvio === 'function')
                            ? window.prepararDatosParaEnvio({ soloConCantidades: false })
                            : null;

                        if (!datos) {
                            throw new Error('No se pudo recopilar los datos del pedido. Recarga la página e intenta de nuevo.');
                        }

                        // Agregar observaciones (prepararDatosParaEnvio no las incluye)
                        datos.observaciones = document.getElementById('observaciones_editable')?.value?.trim() || '';

                        // Enviar al endpoint de borrador usando enviarDatosAlServidor si existe,
                        // o construir manualmente el FormData igual que la creación normal
                        const csrfToken = document.querySelector('input[name="_token"]')?.value ||
                                        document.querySelector('meta[name="csrf-token"]')?.content;

                        const formData = new FormData();

                        // JSON del pedido (misma estructura que creación normal)
                        // IMPORTANTE: Procesar imágenes de EPPs correctamente
                        // Separar imágenes nuevas (File objects) de existentes (URLs)
                        const eppsProcesados = (datos.epps || []).map((e, eppIndex) => {
                            const imagenesExistentes = [];
                            
                            if (Array.isArray(e.imagenes)) {
                                e.imagenes.forEach((img, imgIndex) => {
                                    if (!img) return;
                                    
                                    // Si es un File object (nueva imagen del modal)
                                    if (img instanceof File || (img.file && img.file instanceof File)) {
                                        const file = img instanceof File ? img : img.file;
                                        // Usar notación de punto que Laravel interpreta como array anidado
                                        const fieldName = `epps.${eppIndex}.imagenes.${imgIndex}`;
                                        formData.append(fieldName, file);
                                        console.log(`[guardarComoBorrador] ✅ Archivo agregado a FormData: fieldName="${fieldName}", filename="${file.name}", size=${file.size}, epp_id=${e.epp_id}, eppIndex=${eppIndex}, imgIndex=${imgIndex}`);
                                    }
                                    // Si es una URL o propiedad con URL (imagen existente)
                                    else {
                                        let imageUrl = null;
                                        if (typeof img === 'string') imageUrl = img;
                                        else if (img.url) imageUrl = img.url;
                                        else if (img.preview) imageUrl = img.preview;
                                        else if (img.ruta_webp) imageUrl = img.ruta_webp;
                                        else if (img.ruta) imageUrl = img.ruta;
                                        
                                        if (imageUrl) {
                                            imagenesExistentes.push(imageUrl);
                                            console.log(`[guardarComoBorrador] 🔗 URL existente agregada: ${imageUrl}`);
                                        }
                                    }
                                });
                            }
                            
                            return {
                                epp_id: e.epp_id,
                                cantidad: e.cantidad,
                                observaciones: e.observaciones,
                                imagenes: imagenesExistentes
                            };
                        });
                        
                        // Collect new prendas added via modal (stored in gestionItemsUI.prendas)
                        const nuevasPrendasJson = [];
                        if (window.gestionItemsUI && Array.isArray(window.gestionItemsUI.prendas)) {
                            window.gestionItemsUI.prendas.forEach((p, prendaIdx) => {
                                // Upload prenda images
                                const imagenesArr = Array.isArray(p.imagenes) ? p.imagenes : [];
                                imagenesArr.forEach((img, imgIdx) => {
                                    const file = (img instanceof File) ? img : (img && img.file instanceof File ? img.file : null);
                                    if (file) {
                                        formData.append(`nuevas_prendas.${prendaIdx}.imagenes.${imgIdx}`, file);
                                    }
                                });

                                // Upload tela images
                                const telasArr = Array.isArray(p.telasAgregadas) ? p.telasAgregadas : (Array.isArray(p.telas) ? p.telas : []);
                                telasArr.forEach((tela, telaIdx) => {
                                    const imagenesTelaArr = Array.isArray(tela.imagenes) ? tela.imagenes : [];
                                    imagenesTelaArr.forEach((imgTela, imgIdx) => {
                                        const file = (imgTela instanceof File) ? imgTela : (imgTela && imgTela.file instanceof File ? imgTela.file : null);
                                        if (file) {
                                            formData.append(`nuevas_prendas.${prendaIdx}.telas.${telaIdx}.imagenes.${imgIdx}`, file);
                                        }
                                    });
                                });

                                nuevasPrendasJson.push({
                                    tipo: 'prenda',
                                    nombre_prenda: p.nombre_prenda || p.nombre_producto || '',
                                    nombre_producto: p.nombre_producto || p.nombre_prenda || '',
                                    descripcion: p.descripcion || '',
                                    de_bodega: p.de_bodega !== undefined ? p.de_bodega : 1,
                                    genero: p.genero || '',
                                    cantidad_talla: p.cantidad_talla || p.cantidades || {},
                                    telas: telasArr.map(t => ({
                                        tela: t.nombre_tela || t.tela || '',
                                        color: t.color || t.color_nombre || '',
                                        referencia: t.referencia || ''
                                    })),
                                    procesos: (typeof p.procesos === 'object' && p.procesos) ? p.procesos : {},
                                    asignacionesColoresPorTalla: p.asignacionesColoresPorTalla || {}
                                });
                            });
                        }

                        const pedidoLimpio = {
                            cliente: datos.cliente || '',
                            asesora: datos.asesora || '',
                            forma_de_pago: datos.forma_de_pago || '',
                            observaciones: datos.observaciones || '',
                            orden_compra: datos.orden_compra || document.getElementById('orden_compra_editable')?.value?.trim() || '',
                            numero_cotizacion: datos.numero_cotizacion,
                            es_sin_cotizacion: datos.es_sin_cotizacion,
                            tipo_cotizacion: datos.tipo_cotizacion || null,
                            logo: datos.logo || null,
                            reflectivo: datos.reflectivo || null,
                            prendas: (datos.prendas || []).map(p => ({
                                tipo: p.tipo,
                                nombre_prenda: p.nombre_producto || p.nombre_prenda || '',
                                nombre_producto: p.nombre_producto || p.nombre_prenda || '',
                                descripcion: p.descripcion,
                                de_bodega: p.de_bodega,
                                genero: p.genero,
                                cantidad_talla: p.cantidades || {},
                                cantidades: p.cantidades || {},
                                telas: (p.telas || []).map(t => ({tela: t.nombre_tela || t.tela, color: t.color, referencia: t.referencia}))
                            })),
                            nuevas_prendas: nuevasPrendasJson,
                            epps: eppsProcesados
                        };
                        formData.append('pedido', JSON.stringify(pedidoLimpio));
                        formData.append('_token', csrfToken);

                        console.debug('[guardarComoBorrador] Datos a enviar:', pedidoLimpio);
                        console.debug('[guardarComoBorrador] Prendas:', pedidoLimpio.prendas.length, 'Nuevas prendas:', pedidoLimpio.nuevas_prendas.length, 'EPPs:', pedidoLimpio.epps.length);

                        // Determinar si es modo edición o creación
                        const modoEdicion = window.modoEdicion || false;
                        const pedidoId = window.pedidoEditarId || null;
                        
                        // Seleccionar endpoint según el modo
                        let endpoint = '{{ route("asesores.pedidos-editable.guardarBorrador") }}';
                        if (modoEdicion && pedidoId) {
                            // En modo edición, actualizar el pedido existente
                            endpoint = `/asesores/pedidos-editable/${pedidoId}/actualizar`;
                            formData.append('pedido_id', pedidoId);
                            console.debug('[guardarComoBorrador] MODO EDICIÓN - Actualizando pedido:', pedidoId);
                        } else {
                            console.debug('[guardarComoBorrador] MODO CREACIÓN - Creando nuevo borrador');
                        }

                        // Enviar al servidor
                        const response = await fetch(endpoint, {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            }
                        });
                        
                        const resultado = await response.json();
                        
                        if (resultado.success) {
                            // Éxito: mostrar mensaje según el modo (edición o creación)
                            if (modoEdicion && pedidoId) {
                                // Modo edición: Solo mostrar confirmación, sin redirigir
                                Swal.fire({
                                    icon: 'success',
                                    title: '✓ Cambios Guardados',
                                    html: `
                                        <div style="text-align: left;">
                                            <p>El pedido ha sido actualizado correctamente.</p>
                                            <p style="margin-top: 10px; padding: 10px; background: #f0f7ff; border-left: 4px solid #0066cc; border-radius: 4px;">
                                                <strong>Pedido #${resultado.numero_pedido || pedidoId}</strong>
                                            </p>
                                        </div>
                                    `,
                                    confirmButtonColor: '#0066cc',
                                    confirmButtonText: 'Aceptar'
                                });
                            } else {
                                // Modo creación: Mostrar ID y redirigir
                                Swal.fire({
                                    icon: 'success',
                                    title: ' ¡Borrador Guardado!',
                                    html: `
                                        <div style="text-align: left;">
                                            <p>Tu pedido ha sido guardado como borrador.</p>
                                            <p style="margin-top: 10px; padding: 10px; background: #f0f7ff; border-left: 4px solid #0066cc; border-radius: 4px;">
                                                <strong>ID:</strong> #${resultado.pedido_id}
                                            </p>
                                        </div>
                                    `,
                                    confirmButtonColor: '#0066cc',
                                    confirmButtonText: 'Aceptar'
                                }).then(() => {
                                    // Redirigir a la página de pedidos
                                    if (resultado.redirect_url) {
                                        window.location.href = resultado.redirect_url;
                                    } else {
                                        window.location.href = '{{ route("asesores.pedidos.index") }}';
                                    }
                                });
                            }
                        } else {
                            throw new Error(resultado.message || 'Error desconocido al guardar borrador');
                        }
                    } catch (error) {
                        console.error('[guardarComoBorrador] Error:', error);
                        
                        Swal.fire({
                            icon: 'error',
                            title: ' Error al Guardar Borrador',
                            text: error.message || 'No se pudo guardar el borrador. Intenta nuevamente.',
                            confirmButtonColor: '#ef4444',
                            confirmButtonText: 'Aceptar'
                        });
                    }
                }
            });
            
        } catch (error) {
            console.error('[guardarComoBorrador] Error critical:', error);
            Swal.fire({
                icon: 'error',
                title: ' Error Crítico',
                text: 'Ocurrió un error inesperado. Revisa la consola.',
                confirmButtonColor: '#ef4444'
            });
        }
    };
    
    // Asignar evento al botón cuando se cargue el DOM
    document.addEventListener('DOMContentLoaded', function() {
        const btnGuardarBorrador = document.getElementById('btn-guardar-borrador');
        if (btnGuardarBorrador) {
            btnGuardarBorrador.addEventListener('click', function(e) {
                e.preventDefault();
                window.guardarComoBorrador();
            });
        }
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
<script defer src="{{ js_asset('js/componentes/drag-drop-procesos-estilo-prenda.js') }}?v={{ $v }}"></script>

<!-- 🧪 TEST SUITE: Solo en desarrollo -->
@if(config('app.debug'))
<script defer src="{{ js_asset('js/tests/prenda-editor-test.js') }}?v={{ $v }}"></script>
@endif
@endpush

