@extends('layouts.asesores')

@section('title', 'Editar Pedido')
@section('page-title', 'Editar Pedido #' . $pedidoData->pedido)

@section('content')
<div class="pedidos-container">
    <form id="formEditarPedido" class="pedido-form" data-pedido="{{ $pedidoData->pedido }}">
        @csrf
        @method('PUT')
        
        <!-- Información General -->
        <div class="form-section">
            <h2 class="section-title">
                <i class="fas fa-info-circle"></i>
                Información General
            </h2>
            
            <div class="form-grid">
                <div class="form-group">
                    <label for="pedido">Número de Pedido</label>
                    <input type="number" id="pedido" value="{{ $pedidoData->pedido }}" readonly>
                </div>

                <div class="form-group">
                    <label for="cliente">Cliente *</label>
                    <input type="text" id="cliente" name="cliente" value="{{ $pedidoData->cliente }}" required>
                </div>

                <div class="form-group">
                    <label for="forma_de_pago">Forma de Pago</label>
                    <select id="forma_de_pago" name="forma_de_pago">
                        <option value="">Seleccionar...</option>
                        <option value="Crédito" {{ $pedidoData->forma_de_pago == 'Crédito' ? 'selected' : '' }}>Crédito</option>
                        <option value="Contado" {{ $pedidoData->forma_de_pago == 'Contado' ? 'selected' : '' }}>Contado</option>
                        <option value="50/50" {{ $pedidoData->forma_de_pago == '50/50' ? 'selected' : '' }}>50/50</option>
                        <option value="Anticipo" {{ $pedidoData->forma_de_pago == 'Anticipo' ? 'selected' : '' }}>Anticipo</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="estado">Estado</label>
                    <select id="estado" name="estado">
                        @foreach($estados as $estado)
                            <option value="{{ $estado }}" {{ $pedidoData->estado == $estado ? 'selected' : '' }}>
                                {{ $estado }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group full-width">
                    <label for="descripcion">Descripción General</label>
                    <textarea id="descripcion" name="descripcion" rows="3">{{ $pedidoData->descripcion }}</textarea>
                </div>

                <div class="form-group full-width">
                    <label for="novedades">Novedades</label>
                    <textarea id="novedades" name="novedades" rows="2">{{ $pedidoData->novedades }}</textarea>
                </div>
            </div>
        </div>

        <!-- Productos del Pedido -->
        <div class="form-section">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="fas fa-box"></i>
                    Productos del Pedido
                </h2>
                <button type="button" class="btn btn-add-product" id="btnAgregarProducto">
                    <i class="fas fa-plus"></i>
                    Agregar Producto
                </button>
            </div>

            <div id="productosContainer" class="productos-container">
                @foreach($pedidoData->productos as $index => $producto)
                    <div class="producto-item">
                        <div class="producto-header">
                            <h3>Producto {{ $index + 1 }}</h3>
                            <button type="button" class="btn-remove-product">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                        <div class="producto-body">
                            <div class="form-grid">
                                <div class="form-group full-width">
                                    <label>Nombre del Producto *</label>
                                    <input type="text" name="productos[{{ $index }}][nombre_producto]" value="{{ $producto->nombre_producto }}" required>
                                </div>

                                <div class="form-group full-width">
                                    <label>Descripción Completa</label>
                                    <textarea name="productos[{{ $index }}][descripcion]" rows="3">{{ $producto->descripcion }}</textarea>
                                </div>

                                <div class="form-group">
                                    <label>Talla</label>
                                    <input type="text" name="productos[{{ $index }}][talla]" value="{{ $producto->talla }}">
                                </div>

                                <div class="form-group">
                                    <label>Cantidad *</label>
                                    <input type="number" name="productos[{{ $index }}][cantidad]" min="1" value="{{ $producto->cantidad }}" class="producto-cantidad" required>
                                </div>

                                <div class="form-group">
                                    <label>Precio Unitario</label>
                                    <input type="number" name="productos[{{ $index }}][precio_unitario]" min="0" step="0.01" value="{{ $producto->precio_unitario }}" class="producto-precio">
                                </div>

                                <div class="form-group">
                                    <label>Subtotal</label>
                                    <input type="text" class="producto-subtotal" readonly value="${{ number_format($producto->subtotal ?? 0, 2) }}">
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="productos-summary">
                <div class="summary-item">
                    <span>Total de Productos:</span>
                    <strong id="totalProductos">{{ $pedidoData->productos->count() }}</strong>
                </div>
                <div class="summary-item">
                    <span>Cantidad Total:</span>
                    <strong id="cantidadTotal">{{ $pedidoData->productos->sum('cantidad') }}</strong>
                </div>
            </div>
        </div>

        <!-- Botones de Acción -->
        <div class="form-actions">
            <a href="{{ route('asesores.pedidos.show', $pedidoData->pedido) }}" class="btn btn-secondary">
                <i class="fas fa-times"></i>
                Cancelar
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i>
                Actualizar Pedido
            </button>
        </div>
    </form>
</div>

<!-- Template para Producto -->
<template id="productoTemplate">
    <div class="producto-item">
        <div class="producto-header">
            <h3>Producto <span class="producto-numero"></span></h3>
            <button type="button" class="btn-remove-product">
                <i class="fas fa-trash"></i>
            </button>
        </div>
        <div class="producto-body">
            <div class="form-grid">
                <div class="form-group full-width">
                    <label>Nombre del Producto *</label>
                    <input type="text" name="productos[][nombre_producto]" required>
                </div>

                <div class="form-group full-width">
                    <label>Descripción Completa</label>
                    <textarea name="productos[][descripcion]" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label>Talla</label>
                    <input type="text" name="productos[][talla]">
                </div>

                <div class="form-group">
                    <label>Cantidad *</label>
                    <input type="number" name="productos[][cantidad]" min="1" value="1" class="producto-cantidad" required>
                </div>

                <div class="form-group">
                    <label>Precio Unitario</label>
                    <input type="number" name="productos[][precio_unitario]" min="0" step="0.01" class="producto-precio">
                </div>

                <div class="form-group">
                    <label>Subtotal</label>
                    <input type="text" class="producto-subtotal" readonly placeholder="$0.00">
                </div>
            </div>
        </div>
    </div>
</template>
@endsection

<!-- Incluir modales necesarios para edición de prendas -->
@include('asesores.pedidos.modals.modal-agregar-prenda-nueva')
@include('asesores.pedidos.modals.modal-seleccionar-tallas')
@include('asesores.pedidos.modals.modal-proceso-generico')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/asesores/pedidos.css') }}">
@endpush

@push('scripts')
<!-- IMPORTANTE: Cargar PRIMERO el protector de datos principales -->
<script src="{{ asset('js/modulos/crear-pedido/seguridad/protector-datos-principales.js') }}"></script>

<!-- Scripts base de pedidos -->
<script src="{{ asset('js/asesores/pedidos.js') }}"></script>

<!-- Scripts para gestión de ítems en modal (necesarios para edición modal de prendas) -->
<script src="{{ asset('js/modulos/crear-pedido/configuracion/api-pedidos-editable.js') }}"></script>
<script src="{{ asset('js/modulos/crear-pedido/fotos/image-storage-service.js') }}"></script>
<script src="{{ asset('js/configuraciones/constantes-tallas.js') }}"></script>
<script src="{{ asset('js/modulos/crear-pedido/modales/modales-dinamicos.js') }}"></script>

<!--  SERVICIO HTTP para EPP (DEBE cargarse ANTES del modal) -->
<script src="{{ asset('js/services/epp/EppHttpService.js') }}"></script>

<script src="{{ asset('js/modulos/crear-pedido/tallas/gestion-tallas.js') }}"></script>

<!-- Inicializar storages INMEDIATAMENTE (ANTES de que se cargue gestion-telas.js) -->
<script>
    //  CRÍTICO: Esto se ejecuta INMEDIATAMENTE, NO en DOMContentLoaded
    // Asegurar que imagenesPrendaStorage existe
    if (!window.imagenesPrendaStorage) {
        window.imagenesPrendaStorage = new ImageStorageService(3);
    }

    // Asegurar que imagenesTelaStorage existe
    if (!window.imagenesTelaStorage) {
        window.imagenesTelaStorage = new ImageStorageService(3);
    }

    // Asegurar que imagenesReflectivoStorage existe
    if (!window.imagenesReflectivoStorage) {
        window.imagenesReflectivoStorage = new ImageStorageService(3);
    }

    // Asegurar que telasAgregadas existe
    if (!window.telasAgregadas) {
        window.telasAgregadas = [];
    }

    // Asegurar que procesosSeleccionados existe
    if (!window.procesosSeleccionados) {
        window.procesosSeleccionados = {};
    }
</script>

<!-- Ahora cargar gestion-telas.js (con imagenesTelaStorage YA disponible) -->
<script src="{{ asset('js/modulos/crear-pedido/telas/gestion-telas.js') }}"></script>

<!--  ESTILOS del componente tarjeta readonly (ANTES de scripts) -->
<link rel="stylesheet" href="{{ asset('css/componentes/prenda-card-readonly.css') }}?v={{ time() }}">
<link rel="stylesheet" href="{{ asset('css/componentes/epp-card.css') }}">

<!-- Manejadores de procesos - DEBEN cargarse ANTES de prenda-editor.js -->
<script src="{{ asset('js/modulos/crear-pedido/procesos/manejadores-procesos-prenda.js') }}"></script>
<script src="{{ asset('js/modulos/crear-pedido/procesos/gestor-modal-proceso-generico.js') }}"></script>
<script src="{{ asset('js/modulos/crear-pedido/procesos/renderizador-tarjetas-procesos.js') }}"></script>

<!--  SERVICIOS EDICIÓN DINÁMICA DE PROCESOS - Deben cargarse PRIMERO -->
<script src="{{ asset('js/modulos/crear-pedido/procesos/services/proceso-editor.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/modulos/crear-pedido/procesos/services/gestor-edicion-procesos.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/modulos/crear-pedido/procesos/services/servicio-procesos.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/modulos/crear-pedido/procesos/services/middleware-guardado-prenda.js') }}?v={{ time() }}"></script>

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

<!-- Manejadores de variaciones -->
<script src="{{ asset('js/modulos/crear-pedido/prendas/manejadores-variaciones.js') }}?v={{ time() }}"></script>

<!-- Componente tarjeta readonly (completo - funcional) -->
<script src="{{ asset('js/componentes/prenda-card-readonly.js') }}?v={{ time() }}"></script>

<!-- Componente para editar prendas con procesos desde API -->
<script src="{{ asset('js/componentes/prenda-card-editar-simple.js') }}?v={{ time() }}"></script>

<!-- Inicializar storages INMEDIATAMENTE (antes de que se cargue gestion-telas.js) -->
<script>
    //  CRÍTICO: Esto se ejecuta INMEDIATAMENTE, NO en DOMContentLoaded
    // Asegurar que imagenesPrendaStorage existe
    if (!window.imagenesPrendaStorage) {
        window.imagenesPrendaStorage = new ImageStorageService(3);
    }

    // Asegurar que imagenesTelaStorage existe
    if (!window.imagenesTelaStorage) {
        window.imagenesTelaStorage = new ImageStorageService(3);
    }

    // Asegurar que imagenesReflectivoStorage existe
    if (!window.imagenesReflectivoStorage) {
        window.imagenesReflectivoStorage = new ImageStorageService(3);
    }

    // Asegurar que telasAgregadas existe
    if (!window.telasAgregadas) {
        window.telasAgregadas = [];
    }

    // Asegurar que procesosSeleccionados existe
    if (!window.procesosSeleccionados) {
        window.procesosSeleccionados = {};
    }
</script>

<!-- Ahora cargar gestion-telas.js (con imagenesTelaStorage YA disponible) -->
<script src="{{ asset('js/modulos/crear-pedido/telas/gestion-telas.js') }}?v={{ time() }}"></script>

<!-- Resto de handlers que necesitan DOMContentLoaded -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Configurar asesora
        const asesoraInput = document.getElementById('asesora_editable');
        if (asesoraInput) {
            asesoraInput.value = '{{ Auth::user()->name ?? '' }}';
        }

        //  HANDLER: Botón agregar producto abre el modal de prenda
        const btnAgregarProducto = document.getElementById('btnAgregarProducto');
        if (btnAgregarProducto && window.gestionItemsUI) {
            btnAgregarProducto.addEventListener('click', function(e) {
                e.preventDefault();
                window.gestionItemsUI.abrirModalAgregarPrendaNueva();
            });
        }
    });
</script>

<!-- Script para renderizar factura editable - Para mostrar prendas con imágenes -->
<script src="{{ asset('js/invoice-preview-live.js') }}?v={{ time() }}"></script>
<script>
    window.addEventListener('load', function() {
        console.log('🔥 [FACTURA-EDITABLE-EDIT] Página cargada, renderizando factura...');
        
        // Esperar un poco para que todo esté listo
        setTimeout(function() {
            console.log('🔍 [FACTURA-EDITABLE-EDIT] Buscando datos del pedido...');
            
            // Los datos vienen en $pedidoData desde el servidor
            // Necesito obtener los datos del formulario
            const formElement = document.getElementById('formEditarPedido');
            if (!formElement) {
                console.log('❌ [FACTURA-EDITABLE-EDIT] Formulario no encontrado');
                return;
            }
            
            // Obtener ID del pedido del atributo data-pedido
            const pedidoId = formElement.getAttribute('data-pedido');
            console.log('📋 [FACTURA-EDITABLE-EDIT] Pedido ID:', pedidoId);
            
            // Hacer fetch para obtener los datos del pedido desde la API
            fetch(`/api/pedidos/${pedidoId}`)
                .then(response => response.json())
                .then(resultado => {
                    console.log('✅ [FACTURA-EDITABLE-EDIT] Datos obtenidos de API:', resultado);
                    
                    if (resultado.success && resultado.data && typeof generarHTMLFactura === 'function') {
                        const datos = {
                            numero_pedido: resultado.data.pedido?.numero_pedido || pedidoId,
                            numero_pedido_temporal: resultado.data.pedido?.numero_pedido_temporal,
                            cliente: resultado.data.pedido?.cliente || '',
                            asesora: resultado.data.pedido?.asesora || '',
                            forma_de_pago: resultado.data.pedido?.forma_de_pago || '',
                            prendas: resultado.data.pedido?.prendas || [],
                            procesos: resultado.data.pedido?.procesos || [],
                            epps: resultado.data['epps_transformados'] || resultado.data.pedido?.epps || []
                        };
                        
                        console.log('📦 [FACTURA-EDITABLE-EDIT] Datos preparados:', {
                            prendas: datos.prendas.length,
                            epps: datos.epps.length
                        });
                        
                        try {
                            const htmlFactura = generarHTMLFactura(datos);
                            
                            // Crear contenedor si no existe
                            let contenedor = document.getElementById('factura-container-editable');
                            if (!contenedor) {
                                contenedor = document.createElement('div');
                                contenedor.id = 'factura-container-editable';
                                contenedor.style.cssText = 'margin: 1rem 0; padding: 1rem; background: #f9fafb; border-radius: 8px; border: 1px solid #e5e7eb; position: relative; z-index: 1;';
                                
                                // Insertar antes del formulario
                                const pedidosContainer = document.querySelector('.pedidos-container');
                                if (pedidosContainer) {
                                    pedidosContainer.insertBefore(contenedor, pedidosContainer.firstChild);
                                }
                            }
                            
                            contenedor.innerHTML = htmlFactura;
                            console.log('✅✅ [FACTURA-EDITABLE-EDIT] FACTURA RENDERIZADA EXITOSAMENTE');
                        } catch (e) {
                            console.error('❌ [FACTURA-EDITABLE-EDIT] Error al renderizar factura:', e);
                        }
                    } else {
                        console.log('⚠️ [FACTURA-EDITABLE-EDIT] No se pudieron obtener datos o generarHTMLFactura no está disponible');
                    }
                })
                .catch(error => {
                    console.error('❌ [FACTURA-EDITABLE-EDIT] Error en fetch:', error);
                });
        }, 500);
    });
</script>
@endpush

