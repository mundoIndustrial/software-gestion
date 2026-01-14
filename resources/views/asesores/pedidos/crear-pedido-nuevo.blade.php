{{-- Flujo específico para NUEVO PEDIDO --}}
<link rel="stylesheet" href="{{ asset('css/crear-pedido-editable.css') }}">
<link rel="stylesheet" href="{{ asset('css/componentes/prendas.css') }}">
<link rel="stylesheet" href="{{ asset('css/componentes/reflectivo.css') }}">

<!-- Header Full Width -->
<div class="page-header">
    <h1>📋 Crear Nuevo Pedido de Producción</h1>
    <p>Crea un pedido completamente nuevo sin una cotización previa</p>
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
                            <option value="R">REFLECTIVO</option>
                            <option value="B">BORDADO</option>
                            <option value="E">ESTAMPADO</option>
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

            <!-- Lista de ítems -->
            <div id="lista-items-pedido" style="display: flex; flex-direction: column; gap: 0.75rem;">
                <!-- Los ítems se agregarán aquí dinámicamente -->
            </div>

            <!-- Mensaje cuando no hay ítems -->
            <div id="mensaje-sin-items" style="padding: 2rem; text-align: center; background: #f9fafb; border: 2px dashed #d1d5db; border-radius: 8px; color: #6b7280;">
                <p style="margin: 0; font-size: 0.875rem;">No hay ítems agregados. Selecciona un tipo de pedido para agregar nuevos ítems.</p>
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
                    window.abrirModalPrendaNueva();
                } else if (tipoPedido === 'R') {
                    window.abrirModalReflectivo();
                } else {
                    alert('Tipo de pedido "' + tipoPedido + '" en desarrollo');
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
        
        console.log('✅ Vista de nuevo pedido inicializada');
    });
</script>
