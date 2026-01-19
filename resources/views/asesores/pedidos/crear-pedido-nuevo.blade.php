@extends('layouts.asesores')

@section('extra_styles')
    <link rel="stylesheet" href="{{ asset('css/crear-pedido.css') }}">
    <link rel="stylesheet" href="{{ asset('css/crear-pedido-editable.css') }}">
    <link rel="stylesheet" href="{{ asset('css/form-modal-consistency.css') }}">
    <link rel="stylesheet" href="{{ asset('css/componentes/prendas.css') }}">
    <link rel="stylesheet" href="{{ asset('css/componentes/reflectivo.css') }}">
    <link rel="stylesheet" href="{{ asset('css/modales/modal-exito-pedido.css') }}">
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
        // ✅ Pasar datos completos con pedido, prendas, EPPs, etc.
        window.pedidoEditarData = {!! json_encode([
            'pedido' => $pedido ?? [],
            'epps' => $epps ?? [],
            'estados' => $estados ?? [],
            'areas' => $areas ?? []
        ]) !!};
        console.log('📝 Modo edición: Editando pedido #{{ $pedidoEditarId }}');
        console.log('📦 Datos cargados:', window.pedidoEditarData);
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
                            <option value="">-- Selecciona un tipo de pedido --</option>
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

        <!-- COMPONENTE: Reflectivo Editable -->
        @include('asesores.pedidos.components.reflectivo-editable')

        <!-- PASO 5: Botones de Acción -->
        <div class="btn-actions">
            <button type="submit" id="btn-submit" class="btn btn-primary" style="display: none; background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); color: white; border: none; padding: 10px 16px; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 0.9rem; transition: all 0.3s; box-shadow: 0 2px 4px rgba(59, 130, 246, 0.2); display: flex; align-items: center; justify-content: center; gap: 0.5rem;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 8px rgba(59, 130, 246, 0.3)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 4px rgba(59, 130, 246, 0.2)'">
                <span class="material-symbols-rounded" style="font-size: 1.1rem;">check_circle</span>
                Crear Pedido
            </button>
            <a href="{{ route('asesores.pedidos-produccion.index') }}" class="btn btn-secondary" style="display: flex; align-items: center; gap: 0.5rem;">
                <span class="material-symbols-rounded" style="font-size: 1.1rem;">close</span>
                Cancelar
            </a>
        </div>
    </form>
</div>

@include('asesores.pedidos.modals.modal-seleccionar-prendas')
@include('asesores.pedidos.modals.modal-seleccionar-tallas')
@include('asesores.pedidos.modals.modal-agregar-prenda-nueva')
@include('asesores.pedidos.modals.modal-agregar-reflectivo')
@include('asesores.pedidos.modals.modal-proceso-generico')

@endsection

@push('scripts')
    <!-- IMPORTANTE: Cargar PRIMERO el protector de datos principales -->
    <script src="{{ asset('js/modulos/crear-pedido/seguridad/protector-datos-principales.js') }}"></script>
    
    <!-- IMPORTANTE: Cargar constantes PRIMERO -->
    <script src="{{ asset('js/configuraciones/constantes-tallas.js') }}"></script>
    
    <!-- IMPORTANTE: Cargar módulos DESPUÉS de las constantes -->
    <script src="{{ asset('js/modulos/crear-pedido/modales/modales-dinamicos.js') }}"></script>
    
    <!-- ✅ SERVICIO HTTP para EPP (DEBE cargarse ANTES del modal) -->
    <script src="{{ asset('js/services/epp/EppHttpService.js') }}"></script>
    
    <script src="{{ asset('js/modulos/crear-pedido/modales/modal-agregar-epp.js') }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/tallas/gestion-tallas.js') }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/telas/gestion-telas.js') }}"></script>
    
    <!-- ✅ ESTILOS del componente tarjeta readonly (ANTES de scripts) -->
    <link rel="stylesheet" href="{{ asset('css/componentes/prenda-card-readonly.css') }}">
    
    <script src="{{ asset('js/modulos/crear-pedido/procesos/gestion-items-pedido.js') }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/modales/modal-seleccion-prendas.js') }}"></script>
    
    <!-- Wrappers delegadores para prendas -->
    <script src="{{ asset('js/componentes/prendas-wrappers.js') }}"></script>
    
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
    
    <!-- ✅ Componente tarjeta readonly (completo - funcional) -->
    <script src="{{ asset('js/componentes/prenda-card-readonly.js') }}"></script>
    
    <!-- ✅ Edición simple de prendas (reutiliza factura) -->
    <script src="{{ asset('js/componentes/prenda-card-editar-simple.js') }}"></script>
    
    <script src="{{ asset('js/modulos/crear-pedido/procesos/manejadores-procesos-prenda.js') }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/procesos/gestor-modal-proceso-generico.js') }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/procesos/renderizador-tarjetas-procesos.js') }}"></script>

<script>
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

        // ========== OCULTAR LOADING Y MOSTRAR SELECT DE TIPO DE PEDIDO ==========
        const tipoPedidoLoading = document.getElementById('tipo-pedido-loading');
        const tipoPedidoSelect = document.getElementById('tipo_pedido_nuevo');
        
        if (tipoPedidoLoading && tipoPedidoSelect) {
            setTimeout(() => {
                tipoPedidoLoading.style.display = 'none';
                tipoPedidoSelect.style.display = 'block';
                tipoPedidoSelect.removeAttribute('disabled');
                console.log(' Selector de tipo de pedido listo');
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
                    alert('Por favor selecciona un tipo de pedido primero');
                    return;
                }
                
                console.log('🎯 Abriendo modal para tipo:', tipoPedido);
                
                // Manejar diferentes tipos de pedido
                if (tipoPedido === 'P') {
                    // Prenda - incluye prendas, reflectivo, bordado, estampado, DTF, sublimado
                    window.abrirModalPrendaNueva();
                } else if (tipoPedido === 'EPP') {
                    // EPP - Equipo de Protección Personal
                    window.abrirModalAgregarEPP();
                } else {
                    alert('Tipo de pedido "' + tipoPedido + '" desconocido');
                }
            });
        }

        // Manejar cambio de tipo de pedido nuevo
        window.manejarCambiaTipoPedido = function() {
            const tipoPedido = selectTipoPedidoNuevo.value;
            
            if (!tipoPedido) return;
            
            console.log('🔄 Tipo de pedido seleccionado:', tipoPedido);
            
            // Mostrar botón inline
            const btnAgregarTipoInline = document.getElementById('btn-agregar-item-tipo-inline');
            if (btnAgregarTipoInline) {
                btnAgregarTipoInline.style.display = 'flex';
            }
        };

        // Abrir modal según tipo de pedido seleccionado
        window.abrirModalSegunTipo = function() {
            const tipoPedido = selectTipoPedidoNuevo.value;
            
            console.log('🎯 Abriendo modal para tipo:', tipoPedido);
            
            if (tipoPedido === 'EPP') {
                window.abrirModalAgregarEPP();
            } else if (tipoPedido === 'P') {
                // Prenda - abre el modal existente
                window.abrirModalPrendaNueva();
            }
        };

        console.log('✅ Vista de nuevo pedido inicializada');
    });
</script>

<!-- ✅ Script para cargar datos en modo edición -->
@if($modoEdicion ?? false)
<script src="{{ asset('js/modulos/crear-pedido/edicion/cargar-datos-edicion-nuevo.js') }}?v={{ time() }}&t={{ uniqid() }}"></script>
@endif

<!-- ✅ Script para Vista Previa en Vivo de Factura -->
<script src="{{ asset('js/invoice-preview-live.js') }}?v={{ time() }}&t={{ uniqid() }}"></script>

<!-- Script para interactividad de item-cards -->
<script src="{{ asset('js/modulos/crear-pedido/components/item-card-interactions.js') }}"></script>
@endpush