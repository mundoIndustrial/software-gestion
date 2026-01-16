@extends('layouts.app')

@section('title', 'Crear Pedido de Producción')

@section('content')
<div class="container-fluid py-4">
    <!-- HEADER -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1>📦 Crear Pedido de Producción</h1>
                    <p class="text-muted">Formulario complejo para capturar y organizar prendas, variantes, fotos y procesos</p>
                </div>
                <a href="{{ route('asesores.pedidos-produccion.index') }}" class="btn btn-secondary">
                    ← Volver
                </a>
            </div>
        </div>
    </div>

    <!-- SELECTOR DE PEDIDO -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">🎯 Seleccionar Pedido</h5>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="pedido-selector">Pedido de producción *</label>
                        <select id="pedido-selector" class="form-control form-control-lg" required>
                            <option value="">Seleccionar pedido...</option>
                            @forelse ($pedidos as $pedido)
                                <option value="{{ $pedido->id }}">
                                    {{ $pedido->numero_pedido }} - {{ $pedido->cliente }} 
                                    ({{ $pedido->estado }})
                                </option>
                            @empty
                                <option value="" disabled>No hay pedidos disponibles</option>
                            @endforelse
                        </select>
                        <small class="form-text text-muted">
                            Debe seleccionar un pedido de producción antes de agregar prendas
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">📊 Información del Pedido</h5>
                </div>
                <div class="card-body">
                    <div id="pedido-info">
                        <p class="text-muted">Seleccione un pedido para ver detalles</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- FORMULARIO PRINCIPAL -->
    <div class="row">
        <div class="col-12">
            <form id="pedido-form">
                @csrf
                
                <!-- Contenedor principal para prendas -->
                <div id="prendas-container" style="min-height: 400px;">
                    <div class="alert alert-info text-center py-5">
                        <h5>👕 Agregue prendas al pedido</h5>
                        <p class="text-muted mb-0">
                            Seleccione un pedido de producción y comience a agregar prendas
                        </p>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- SOPORTE Y DOCUMENTACIÓN -->
    <div class="row mt-5">
        <div class="col-12">
            <div class="card border-info">
                <div class="card-header bg-light">
                    <h5 class="mb-0">ℹ️ Ayuda</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>📋 Estructura del formulario</h6>
                            <ul class="small">
                                <li><strong>Prenda:</strong> Nombre, descripción, género, origen</li>
                                <li><strong>Variante:</strong> Talla, cantidad, color, tela, detalles especiales</li>
                                <li><strong>Fotos:</strong> Referencias de prenda y telas</li>
                                <li><strong>Procesos:</strong> Bordado, estampado, DTF, sublimado, etc.</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6>💡 Consejos</h6>
                            <ul class="small">
                                <li>✅ Todo se guarda en el navegador automáticamente</li>
                                <li>✅ Puede cerrar y volver sin perder datos</li>
                                <li>✅ Valide antes de enviar</li>
                                <li>✅ Las imágenes se procesarán en el servidor</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ESTILOS PERSONALIZADOS -->
<style>
    /* Tarjetas de prendas */
    .card.border-left-primary {
        border-left: 4px solid #007bff;
    }

    .badge-group {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    /* Botones pequeños */
    .btn-xs {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
    }

    /* Galerías de fotos -->
    .foto-gallery {
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .foto-thumb {
        position: relative;
    }

    .foto-thumb img {
        border: 1px solid #dee2e6;
        border-radius: 4px;
    }

    /* Contenedor principal */
    .pedido-form-container {
        background: white;
        border-radius: 8px;
        padding: 2rem;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    /* Botones de acción -->
    .action-buttons {
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
    }

    .action-buttons .btn {
        min-width: 150px;
    }

    /* Alertas -->
    .alert {
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    /* Tablas -->
    .table-hover tbody tr:hover {
        background-color: rgba(0, 0, 0, 0.05);
    }

    /* Modal -->
    .modal-content {
        border-radius: 8px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
    }

    .modal-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-bottom: none;
    }

    .modal-header .close {
        color: white;
    }

    /* Toast -->
    .toast {
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        animation: slideInRight 0.3s ease-out;
    }

    @keyframes slideInRight {
        from {
            transform: translateX(400px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    /* Formularios -->
    .form-control:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
    }

    .form-control-lg:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
    }

    /* Pestañas -->
    .nav-tabs .nav-link.active {
        border-bottom-color: #667eea;
        color: #667eea;
    }

    /* Loading spinner -->
    .spinner {
        border: 3px solid #f3f3f3;
        border-top: 3px solid #667eea;
        border-radius: 50%;
        width: 40px;
        height: 40px;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    /* Badge personalizadas -->
    .badge {
        padding: 0.5rem 1rem;
        font-size: 0.85rem;
        font-weight: 500;
    }

    /* Información de pedido -->
    #pedido-info {
        line-height: 1.8;
    }

    #pedido-info p {
        margin-bottom: 0.5rem;
    }

    #pedido-info strong {
        color: #333;
    }

    /* Responsive -->
    @media (max-width: 768px) {
        .action-buttons {
            flex-direction: column;
        }

        .action-buttons .btn {
            width: 100%;
        }

        .badge-group {
            gap: 0.25rem;
        }

        .badge-group .badge {
            font-size: 0.7rem;
            padding: 0.3rem 0.6rem;
        }

        .modal-lg {
            max-width: 100%;
            margin: 0.5rem;
        }
    }
</style>

@endsection

@section('scripts')
<!-- jQuery (requerido por Bootstrap 4) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Bootstrap Modal (si no está incluido en layout) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Librerías del formulario -->
<script src="{{ asset('js/pedidos-produccion/PedidoFormManager.js') }}"></script>
<script src="{{ asset('js/pedidos-produccion/PedidoValidator.js') }}"></script>
<script src="{{ asset('js/pedidos-produccion/ui-components.js') }}"></script>
<script src="{{ asset('js/pedidos-produccion/form-handlers.js') }}"></script>

<!-- Inicialización -->
<script>
    // Esperar a que el DOM esté listo
    document.addEventListener('DOMContentLoaded', function() {
        console.log('🚀 Inicializando formulario de pedidos...');

        // ==================== INICIALIZACIÓN ====================

        // 1. Crear instancias
        const formManager = new PedidoFormManager({
            autoSave: true,
            saveInterval: 30000
        });

        const handlers = new PedidoFormHandlers(
            formManager,
            PedidoValidator,
            UIComponents
        );

        // 2. Inicializar handlers
        handlers.init('prendas-container');

        // 3. Selector de pedido
        const pedidoSelector = document.getElementById('pedido-selector');
        pedidoSelector.addEventListener('change', (e) => {
            const pedidoId = parseInt(e.target.value);
            if (pedidoId > 0) {
                formManager.setPedidoId(pedidoId);
                actualizarInfoPedido(pedidoId);
                handlers.render();
                UIComponents.renderToast('success', '✅ Pedido seleccionado');
            }
        });

        // 4. Cargar pedido guardado si existe
        const savedPedidoId = formManager.getPedidoId();
        if (savedPedidoId) {
            pedidoSelector.value = savedPedidoId;
            actualizarInfoPedido(savedPedidoId);
        }

        // ==================== FUNCIONES AUXILIARES ====================

        /**
         * Actualizar información del pedido
         */
        function actualizarInfoPedido(pedidoId) {
            const pedidoInfo = document.getElementById('pedido-info');
            
            // Buscar el pedido en el select
            const option = pedidoSelector.querySelector(`option[value="${pedidoId}"]`);
            if (!option) return;

            const text = option.textContent;
            
            pedidoInfo.innerHTML = `
                <div class="alert alert-info mb-0">
                    <p><strong>Información del pedido:</strong></p>
                    <p>${text}</p>
                    <small class="text-muted">
                        ID: ${pedidoId} | 
                        Guardado automáticamente cada 30 segundos
                    </small>
                </div>
            `;
        }

        // ==================== VALIDACIÓN DE NAVEGACIÓN ====================

        /**
         * Advertir si hay cambios sin guardar
         */
        window.addEventListener('beforeunload', (e) => {
            const summary = formManager.getSummary();
            if (summary.prendas > 0) {
                e.preventDefault();
                e.returnValue = '⚠️ Tiene cambios sin enviar. ¿Está seguro de que desea salir?';
                return e.returnValue;
            }
        });

        // ==================== DEBUGGING ====================

        // Disponibilizar en consola para debugging
        window.formManager = formManager;
        window.handlers = handlers;
        window.UIComponents = UIComponents;
        window.PedidoValidator = PedidoValidator;

        console.log('✅ Formulario inicializado correctamente');
        console.log('💡 Acceso en consola: window.formManager, window.handlers, etc.');
    });
</script>

<!-- Estilos adicionales para Bootstrap si es necesario -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" rel="stylesheet">

@endsection

<!-- Modal de Éxito - Ir a Pedidos -->
<div class="modal fade" id="modalExitoPedido" tabindex="-1" role="dialog" aria-labelledby="modalExitoPedidoLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="modalExitoPedidoLabel">✅ Pedido Creado Exitosamente</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-center">
                <div class="mb-3">
                    <i class="fas fa-check-circle text-success" style="font-size: 3rem;"></i>
                </div>
                <p class="lead">¡El pedido ha sido creado correctamente!</p>
                <p class="text-muted">¿Deseas ir a la sección de pedidos para verlo?</p>
            </div>
            <div class="modal-footer d-flex justify-content-between">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                <a href="{{ route('asesores.pedidos.index') }}" class="btn btn-success" id="btnIrAPedidos">
                    🔗 Ir a Pedidos
                </a>
            </div>
        </div>
    </div>
</div>
