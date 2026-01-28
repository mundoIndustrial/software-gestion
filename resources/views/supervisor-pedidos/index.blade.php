@extends('supervisor-pedidos.layout')

@section('title', 'Supervisión de Pedidos')
@section('page-title', 'Supervisión de Pedidos')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/supervisor-pedidos/index.css') }}">
    <link rel="stylesheet" href="{{ asset('css/supervisor-pedidos/edit-pedido.css') }}">
@endpush

@section('content')
<div class="supervisor-pedidos-container">

    <!-- Tabla de Órdenes - Diseño asesores/pedidos -->
    <div style="background: #e5e7eb; border-radius: 8px; overflow: visible; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08); padding: 0.75rem; width: 100%; max-width: 100%;">
        <!-- Contenedor con Scroll -->
        <div class="table-scroll-container" style="overflow-x: auto; overflow-y: auto; width: 100%; max-width: 100%; max-height: 800px; border-radius: 6px; scrollbar-width: thin; scrollbar-color: #cbd5e1 #f1f5f9;">
            <!-- Header Azul -->
            <div style="
                background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%);
                color: white;
                padding: 0.75rem 1rem;
                display: grid;
                grid-template-columns: 200px 140px 200px 160px 150px 150px 140px 160px;
                gap: 1.2rem;
                font-weight: 600;
                font-size: 0.8rem;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                min-width: min-content;
                border-radius: 6px;
            ">
                <div class="th-wrapper" style="display: flex; align-items: center; gap: 0.5rem;">
                    <span>Acciones</span>
                </div>
                <div class="th-wrapper" style="display: flex; align-items: center; gap: 0.5rem;">
                    <span>Número</span>
                    <button type="button" class="btn-filter-column" title="Filtrar Número" style="display: flex; align-items: center; background: none; border: none; color: white; cursor: pointer; padding: 0;">
                        <span class="material-symbols-rounded" style="font-size: 1rem;">filter_alt</span>
                    </button>
                </div>
                <div class="th-wrapper" style="display: flex; align-items: center; gap: 0.5rem;">
                    <span>Cliente</span>
                    <button type="button" class="btn-filter-column" title="Filtrar Cliente" style="display: flex; align-items: center; background: none; border: none; color: white; cursor: pointer; padding: 0;">
                        <span class="material-symbols-rounded" style="font-size: 1rem;">filter_alt</span>
                    </button>
                </div>
                <div class="th-wrapper" style="display: flex; align-items: center; gap: 0.5rem;">
                    <span>Fecha</span>
                    <button type="button" class="btn-filter-column" title="Filtrar Fecha" style="display: flex; align-items: center; background: none; border: none; color: white; cursor: pointer; padding: 0;">
                        <span class="material-symbols-rounded" style="font-size: 1rem;">filter_alt</span>
                    </button>
                </div>
                <div class="th-wrapper" style="display: flex; align-items: center; gap: 0.5rem;">
                    <span>Estado</span>
                    <button type="button" class="btn-filter-column" title="Filtrar Estado" style="display: flex; align-items: center; background: none; border: none; color: white; cursor: pointer; padding: 0;">
                        <span class="material-symbols-rounded" style="font-size: 1rem;">filter_alt</span>
                    </button>
                </div>
                <div class="th-wrapper" style="display: flex; align-items: center; gap: 0.5rem;">
                    <span>Asesora</span>
                    <button type="button" class="btn-filter-column" title="Filtrar Asesora" style="display: flex; align-items: center; background: none; border: none; color: white; cursor: pointer; padding: 0;">
                        <span class="material-symbols-rounded" style="font-size: 1rem;">filter_alt</span>
                    </button>
                </div>
                <div class="th-wrapper" style="display: flex; align-items: center; gap: 0.5rem;">
                    <span>Forma Pago</span>
                    <button type="button" class="btn-filter-column" title="Filtrar Forma Pago" style="display: flex; align-items: center; background: none; border: none; color: white; cursor: pointer; padding: 0;">
                        <span class="material-symbols-rounded" style="font-size: 1rem;">filter_alt</span>
                    </button>
                </div>
                <div class="th-wrapper" style="display: flex; align-items: center; gap: 0.5rem;">
                    <span>Entrega Est.</span>
                    <button type="button" class="btn-filter-column" title="Filtrar Fecha" style="display: flex; align-items: center; background: none; border: none; color: white; cursor: pointer; padding: 0;">
                        <span class="material-symbols-rounded" style="font-size: 1rem;">filter_alt</span>
                    </button>
                </div>
            </div>

            <!-- Filas -->
            @if($ordenes->isEmpty())
                <div style="padding: 3rem 2rem; text-align: center; color: #6b7280;">
                    <i class="fas fa-inbox" style="font-size: 3rem; color: #d1d5db; margin-bottom: 1rem; display: block;"></i>
                    <p style="font-size: 1rem; margin: 0;">No hay órdenes disponibles</p>
                </div>
            @else
                @foreach($ordenes as $orden)
                    <div style="
                        display: grid;
                        grid-template-columns: 200px 140px 200px 160px 150px 150px 140px 160px;
                        gap: 1.2rem;
                        padding: 1rem;
                        border-bottom: 1px solid #e5e7eb;
                        align-items: center;
                        min-width: min-content;
                        background: white;
                        transition: background 0.2s ease;
                    " onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background='white'">
                        
                        <!-- Acciones -->
                        <div style="display: flex; gap: 0.5rem; align-items: center;">
                            <!-- Botón Ver con Dropdown -->
                            <button class="btn-ver-dropdown" data-menu-id="menu-ver-{{ str_replace('#', '', $orden->numero_pedido) }}" data-pedido="{{ str_replace('#', '', $orden->numero_pedido) }}" data-pedido-id="{{ $orden->id }}" title="Ver Opciones" style="
                                background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
                                color: white;
                                border: none;
                                padding: 0.5rem;
                                border-radius: 6px;
                                cursor: pointer;
                                font-size: 1rem;
                                transition: all 0.3s ease;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                width: 36px;
                                height: 36px;
                                box-shadow: 0 2px 4px rgba(37, 99, 235, 0.3);
                            " onmouseover="this.style.transform='scale(1.1)'; this.style.boxShadow='0 4px 8px rgba(37, 99, 235, 0.4)'" onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 2px 4px rgba(37, 99, 235, 0.3)'">
                                <i class="fas fa-eye"></i>
                            </button>
                            
                            @if(request('aprobacion') === 'pendiente')
                                <button class="btn-action btn-success" title="Aprobar orden" onclick="aprobarOrden({{ $orden->id }}, '{{ $orden->numero_pedido }}')" style="
                                    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
                                    color: white;
                                    border: none;
                                    padding: 0.5rem;
                                    border-radius: 6px;
                                    cursor: pointer;
                                    font-size: 1rem;
                                    transition: all 0.3s ease;
                                    display: flex;
                                    align-items: center;
                                    justify-content: center;
                                    width: 36px;
                                    height: 36px;
                                    box-shadow: 0 2px 4px rgba(16, 185, 129, 0.3);
                                " onmouseover="this.style.transform='scale(1.1)'; this.style.boxShadow='0 4px 8px rgba(16, 185, 129, 0.4)'" onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 2px 4px rgba(16, 185, 129, 0.3)'">
                                    <i class="fas fa-check-circle"></i>
                                </button>
                            @endif
                            
                            <!-- Botón Editar -->
                            <button onclick="editarPedido({{ $orden->id }})" title="Editar Pedido" style="
                                background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
                                color: white;
                                border: none;
                                padding: 0.5rem;
                                border-radius: 6px;
                                cursor: pointer;
                                font-size: 1rem;
                                transition: all 0.3s ease;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                width: 36px;
                                height: 36px;
                                box-shadow: 0 2px 4px rgba(59, 130, 246, 0.3);
                            " onmouseover="this.style.transform='scale(1.1)'; this.style.boxShadow='0 4px 8px rgba(59, 130, 246, 0.4)'" onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 2px 4px rgba(59, 130, 246, 0.3)'">
                                <i class="fas fa-edit"></i>
                            </button>
                            
                            @if($orden->estado !== 'Anulada' && !$orden->aprobado_por_supervisor_en && (request('aprobacion') !== 'pendiente' && !request()->filled('estado')))
                                <button class="btn-action btn-danger" title="Anular orden" onclick="abrirModalAnulacion({{ $orden->id }}, '{{ $orden->numero_pedido }}')" style="
                                    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
                                    color: white;
                                    border: none;
                                    padding: 0.5rem;
                                    border-radius: 6px;
                                    cursor: pointer;
                                    font-size: 1rem;
                                    transition: all 0.3s ease;
                                    display: flex;
                                    align-items: center;
                                    justify-content: center;
                                    width: 36px;
                                    height: 36px;
                                    box-shadow: 0 2px 4px rgba(245, 158, 11, 0.3);
                                " onmouseover="this.style.transform='scale(1.1)'; this.style.boxShadow='0 4px 8px rgba(245, 158, 11, 0.4)'" onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 2px 4px rgba(245, 158, 11, 0.3)'">
                                    <i class="fas fa-ban"></i>
                                </button>
                            @endif
                        </div>
                        
                        <!-- Número -->
                        <div>
                            <span style="font-weight: 600; color: #1e5ba8;">#{{ $orden->numero_pedido }}</span>
                        </div>
                        
                        <!-- Cliente -->
                        <div>
                            <span>{{ $orden->cliente }}</span>
                        </div>
                        
                        <!-- Fecha -->
                        <div>
                            <span>{{ $orden->fecha_de_creacion_de_orden ? $orden->fecha_de_creacion_de_orden->format('d/m/Y') : 'N/A' }}</span>
                        </div>
                        
                        <!-- Estado -->
                        <div>
                            @php
                                $estadoColors = [
                                    'No iniciado' => ['bg' => '#ecf0f1', 'color' => '#7f8c8d'],
                                    'En Ejecución' => ['bg' => '#fff3cd', 'color' => '#856404'],
                                    'Entregado' => ['bg' => '#d4edda', 'color' => '#155724'],
                                    'Anulada' => ['bg' => '#f8d7da', 'color' => '#721c24'],
                                    'PENDIENTE_SUPERVISOR' => ['bg' => '#fff3cd', 'color' => '#856404'],
                                ];
                                $colors = $estadoColors[$orden->estado] ?? ['bg' => '#e3f2fd', 'color' => '#1e40af'];
                                $estadoDisplay = $orden->estado === 'PENDIENTE_SUPERVISOR' ? 'PENDIENTE' : $orden->estado;
                            @endphp
                            <span style="background: {{ $colors['bg'] }}; color: {{ $colors['color'] }}; padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: bold; white-space: nowrap;">
                                {{ $estadoDisplay }}
                            </span>
                        </div>
                        
                        <!-- Asesora -->
                        <div>
                            <span>{{ $orden->asesora?->name ?? 'N/A' }}</span>
                        </div>
                        
                        <!-- Forma Pago -->
                        <div>
                            <span>{{ $orden->forma_de_pago ?? 'N/A' }}</span>
                        </div>
                        
                        <!-- Fecha Estimada -->
                        <div>
                            <span>{{ $orden->fecha_estimada_de_entrega ? $orden->fecha_estimada_de_entrega->format('d/m/Y') : 'N/A' }}</span>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    </div>

    <!-- Paginación Personalizada -->
    @if($ordenes->lastPage() > 1 || $ordenes->count() > 0)
        <div style="margin-top: 1.5rem; display: flex; justify-content: center; align-items: center; gap: 8px; flex-wrap: wrap;">
            <!-- Botón Anterior -->
            @if($ordenes->onFirstPage())
                <button disabled style="min-width: 36px; height: 36px; padding: 0 12px; background: #f0f0f0; border: 1px solid #ddd; border-radius: 6px; cursor: not-allowed; color: #999; font-weight: 600;">
                    ← Anterior
                </button>
            @else
                <a href="{{ $ordenes->previousPageUrl() }}" style="min-width: 36px; height: 36px; padding: 0 12px; background: #ffffff; border: 1px solid #ddd; border-radius: 6px; cursor: pointer; color: #333; font-weight: 600; display: flex; align-items: center; justify-content: center; text-decoration: none; transition: all 0.3s ease;" onmouseover="this.style.background='#e9ecef'; this.style.borderColor='#adb5bd';" onmouseout="this.style.background='#ffffff'; this.style.borderColor='#ddd';">
                    ← Anterior
                </a>
            @endif

            <!-- Números de Página -->
            @if($ordenes->lastPage() > 1)
                @foreach($ordenes->getUrlRange(1, $ordenes->lastPage()) as $page => $url)
                    @if($page == $ordenes->currentPage())
                        <button disabled style="min-width: 36px; height: 36px; padding: 0 8px; background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); border: 1px solid #1d4ed8; border-radius: 6px; color: white; font-weight: 600; cursor: default;">
                            {{ $page }}
                        </button>
                    @else
                        <a href="{{ $url }}" style="min-width: 36px; height: 36px; padding: 0 8px; background: #ffffff; border: 1px solid #ddd; border-radius: 6px; cursor: pointer; color: #333; font-weight: 600; display: flex; align-items: center; justify-content: center; text-decoration: none; transition: all 0.3s ease;" onmouseover="this.style.background='#e9ecef'; this.style.borderColor='#adb5bd';" onmouseout="this.style.background='#ffffff'; this.style.borderColor='#ddd';">
                            {{ $page }}
                        </a>
                    @endif
                @endforeach
            @endif

            <!-- Botón Siguiente -->
            @if($ordenes->hasMorePages())
                <a href="{{ $ordenes->nextPageUrl() }}" style="min-width: 36px; height: 36px; padding: 0 12px; background: #ffffff; border: 1px solid #ddd; border-radius: 6px; cursor: pointer; color: #333; font-weight: 600; display: flex; align-items: center; justify-content: center; text-decoration: none; transition: all 0.3s ease;" onmouseover="this.style.background='#e9ecef'; this.style.borderColor='#adb5bd';" onmouseout="this.style.background='#ffffff'; this.style.borderColor='#ddd';">
                    Siguiente →
                </a>
            @else
                <button disabled style="min-width: 36px; height: 36px; padding: 0 12px; background: #f0f0f0; border: 1px solid #ddd; border-radius: 6px; cursor: not-allowed; color: #999; font-weight: 600;">
                    Siguiente →
                </button>
            @endif

            <!-- Info de Página -->
            <span style="margin-left: 1rem; color: #666; font-size: 14px; font-weight: 500;">
                Página {{ $ordenes->currentPage() }} de {{ $ordenes->lastPage() }} | Total: {{ $ordenes->total() }} registros
            </span>
        </div>
    @endif
</div>

<!-- Modal Filtro Dinámico -->
<div id="modalFiltro" class="modal-overlay" style="display: none;">
    <div class="modal-content" style="width: 90%; max-width: 400px;">
        <div class="modal-header">
            <h2 id="modalFiltroTitulo">Filtrar</h2>
            <button class="btn-close" onclick="cerrarModalFiltro()">
                <span class="material-symbols-rounded">close</span>
            </button>
        </div>
        <div class="modal-body">
            <form id="formFiltroColumna" onsubmit="aplicarFiltroColumna(event)">
                <div class="form-group" id="filtroContenido">
                    <!-- Contenido dinámico según la columna -->
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="cerrarModalFiltro()">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary" style="background: var(--primary-color); color: white;">
                        Aplicar Filtro
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Ver Orden -->
<div id="modalVerOrden" class="modal-overlay" style="display: none;">
    <div class="modal-content modal-lg">
        <div class="modal-header">
            <h2>Detalle de Orden</h2>
            <button class="btn-close" onclick="cerrarModalVerOrden()">
                <span class="material-symbols-rounded">close</span>
            </button>
        </div>
        <div class="modal-body" id="modalVerOrdenContent">
            <!-- Contenido cargado dinámicamente -->
        </div>
    </div>
</div>

<!-- Modal Anulación -->
<div id="modalAnulacion" class="modal-overlay" style="display: none;">
    <div class="modal-content modal-anulacion">
        <div class="modal-header">
            <div class="header-icon">
                <span class="material-symbols-rounded">warning</span>
            </div>
            <h2>¿Anular Orden <span id="ordenNumero"></span>?</h2>
        </div>

        <div class="modal-body">
            <p class="advertencia-texto">
                Esta acción cancelará la orden y no se podrá revertir. Por favor ingresa el motivo de la anulación.
            </p>

            <form id="formAnulacion" onsubmit="confirmarAnulacion(event)">
                @csrf
                <div class="form-group">
                    <label for="motivoAnulacion">Motivo de anulación *</label>
                    <textarea 
                        id="motivoAnulacion" 
                        name="motivo_anulacion" 
                        class="form-control" 
                        rows="4" 
                        placeholder="Ej: El cliente solicitó reembolso, error en precios..."
                        required
                        minlength="10"
                        maxlength="500">
                    </textarea>
                    <small class="contador-caracteres">
                        <span id="contadorActual">0</span>/500 caracteres
                    </small>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="cerrarModalAnulacion()">
                        Cancelar
                    </button>
                    <button type="submit" id="btnConfirmarAnulacion" class="btn btn-danger">
                        <span class="material-symbols-rounded">delete</span>
                        Confirmar Anulación
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar Pedido -->
<div id="modalEditarPedido" class="modal-overlay" style="display: none;">
    <div class="modal-content" style="width: 95%; max-width: 1400px; max-height: 95vh; overflow-y: auto;">
        <div class="modal-header">
            <h2>Editar Pedido <span id="editarNumeroOrden"></span></h2>
            <button class="btn-close" onclick="cerrarModalEditar()">
                <span class="material-symbols-rounded">close</span>
            </button>
        </div>

        <div class="modal-body">
            <form id="formEditarPedido" enctype="multipart/form-data">
                @csrf
                <input type="hidden" id="editarOrdenId" name="orden_id">

                <!-- Información General del Pedido -->
                <div style="background: #f8f9fa; padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem;">
                    <h3 style="margin: 0 0 1.5rem 0; color: #2c3e50; font-size: 1.1rem; border-bottom: 2px solid #e0e6ed; padding-bottom: 0.75rem;">
                        <span class="material-symbols-rounded" style="vertical-align: middle; margin-right: 0.5rem;">info</span>
                        Información General
                    </h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem;">
                        <div class="form-group">
                            <label for="editarCliente">Cliente *</label>
                            <input type="text" id="editarCliente" name="cliente" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="editarFormaPago">Forma de Pago</label>
                            <input type="text" id="editarFormaPago" name="forma_de_pago" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="editarDiaEntrega">Días de Entrega</label>
                            <div style="display: flex; gap: 0.75rem; align-items: flex-end;">
                                <input type="number" id="editarDiaEntrega" name="dia_de_entrega" class="form-control" min="1" style="flex: 1;">
                                <button type="button" class="btn" style="background: #3498db; color: white; padding: 0.5rem 1rem; white-space: nowrap; display: flex; align-items: center; gap: 0.5rem;" onclick="calcularFechaEstimada()">
                                    <span class="material-symbols-rounded" style="font-size: 1.2rem;">event</span>
                                    Calcular
                                </button>
                            </div>
                            <!-- Campo oculto para enviar fecha estimada -->
                            <input type="hidden" id="fechaEstimadaOculta" name="fecha_estimada_de_entrega" value="">
                            <div id="fechaEstimadaContainer" style="margin-top: 0.75rem; display: none;">
                                <label style="font-size: 0.9rem; color: #27ae60; font-weight: 600;">Fecha Estimada:</label>
                                <div id="fechaEstimadaMostrada" style="padding: 0.5rem; background: #d5f4e6; border: 1px solid #27ae60; border-radius: 4px; color: #27ae60; font-weight: 500;">-</div>
                            </div>
                        </div>
                    </div>
                    <div class="form-group" style="margin-top: 1rem;">
                        <label for="editarNovedades">Novedades</label>
                        <textarea id="editarNovedades" name="novedades" class="form-control" rows="3"></textarea>
                    </div>
                </div>

                <!-- Prendas del Pedido -->
                <div id="prendasContainer">
                    <h3 style="margin: 0 0 1.5rem 0; color: #2c3e50; font-size: 1.1rem; border-bottom: 2px solid #e0e6ed; padding-bottom: 0.75rem;">
                        <span class="material-symbols-rounded" style="vertical-align: middle; margin-right: 0.5rem;">checkroom</span>
                        Prendas del Pedido
                    </h3>
                    <!-- Las prendas se cargarán dinámicamente aquí -->
                </div>

                <!-- Botones de Acción -->
                <div class="form-actions" style="margin-top: 2rem; padding-top: 1.5rem; border-top: 2px solid #e0e6ed;">
                    <button type="button" class="btn btn-secondary" onclick="cerrarModalEditar()">
                        <span class="material-symbols-rounded">close</span>
                        Cancelar
                    </button>
                    <button type="submit" class="btn" style="background: #27ae60; color: white;">
                        <span class="material-symbols-rounded">save</span>
                        Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<script>
    // ===== VARIABLES GLOBALES =====
    let filtroActual = null;

    // ===== TOGGLE MENU ACCIONES =====
    function toggleAcciones(event, ordenId) {
        event.stopPropagation();
        const menu = document.getElementById(`menu-${ordenId}`);
        
        // Cerrar otros menús abiertos
        document.querySelectorAll('.action-menu:not([style*="display: none"])').forEach(m => {
            if (m.id !== `menu-${ordenId}`) {
                m.style.display = 'none';
            }
        });
        
        // Toggle del menú actual
        if (menu.style.display === 'none' || menu.style.display === '') {
            menu.style.display = 'block';
        } else {
            menu.style.display = 'none';
        }
    }

    // Cerrar menús al hacer clic afuera
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.action-menu') && !e.target.closest('.action-view-btn')) {
            document.querySelectorAll('.action-menu').forEach(menu => {
                menu.style.display = 'none';
            });
        }
    });

    // ===== MENU VER ORDEN =====
    function toggleVerMenu(event, ordenId) {
        event.stopPropagation();
        const menu = document.getElementById(`ver-menu-${ordenId}`);
        
        // Cerrar otros menús abiertos
        document.querySelectorAll('.ver-submenu[style*="display: block"]').forEach(m => {
            if (m.id !== `ver-menu-${ordenId}`) {
                m.style.display = 'none';
            }
        });
        
        // Toggle del menú actual
        menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
    }

    // Cerrar menús al hacer clic afuera
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.ver-menu-container')) {
            document.querySelectorAll('.ver-submenu').forEach(menu => {
                menu.style.display = 'none';
            });
        }
    });

    // ===== FILTROS DE COLUMNAS =====

    function abrirModalFiltro(columna) {
        filtroActual = columna;
        const modalTitulo = document.getElementById('modalFiltroTitulo');
        const filtroContenido = document.getElementById('filtroContenido');
        const modal = document.getElementById('modalFiltro');

        let titulo = '';
        let campoNombre = '';

        // Configurar según la columna
        switch(columna) {
            case 'id-orden':
                titulo = 'Filtrar por ID Orden';
                campoNombre = 'numero';
                break;
            case 'cliente':
                titulo = 'Filtrar por Cliente';
                campoNombre = 'cliente';
                break;
            case 'fecha':
                modalTitulo.textContent = 'Filtrar por Fecha';
                filtroContenido.innerHTML = `
                    <label for="filtroDesde">Desde:</label>
                    <input type="date" id="filtroDesde" name="fecha_desde" class="form-control">
                    <label for="filtroHasta" style="margin-top: 1rem;">Hasta:</label>
                    <input type="date" id="filtroHasta" name="fecha_hasta" class="form-control">
                `;
                modal.style.display = 'flex';
                return;
            case 'estado':
                titulo = 'Filtrar por Estado';
                campoNombre = 'estado';
                // Estados predefinidos
                const estados = ['No iniciado', 'En Ejecución', 'Entregado', 'Anulada'];
                filtroContenido.innerHTML = `
                    <div class="form-group">
                        <input type="text" id="buscadorEstado" class="form-control" placeholder="Buscar estado..." style="margin-bottom: 1rem;">
                        <div id="listaEstados">
                            ${estados.map(estado => `
                                <label style="display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem; cursor: pointer; border-radius: 4px;">
                                    <input type="checkbox" name="estado" value="${estado}" class="filtro-checkbox">
                                    <span>${estado}</span>
                                </label>
                            `).join('')}
                        </div>
                    </div>
                `;
                
                // Agregar funcionalidad de búsqueda
                setTimeout(() => {
                    document.getElementById('buscadorEstado')?.addEventListener('input', function(e) {
                        const valor = e.target.value.toLowerCase();
                        document.querySelectorAll('#listaEstados label').forEach(label => {
                            const texto = label.textContent.toLowerCase();
                            label.style.display = texto.includes(valor) ? 'flex' : 'none';
                        });
                    });
                }, 0);
                
                modal.style.display = 'flex';
                return;
            case 'asesora':
                titulo = 'Filtrar por Asesora';
                campoNombre = 'asesora';
                break;
            case 'forma-pago':
                titulo = 'Filtrar por Forma de Pago';
                campoNombre = 'forma_pago';
                break;
        }

        // Para columnas que necesitan cargar datos de la BD
        if (campoNombre && columna !== 'fecha' && columna !== 'estado') {
            cargarOpcionesFiltro(campoNombre, titulo, modal, filtroContenido);
        }
    }

    function cargarOpcionesFiltro(campo, titulo, modal, filtroContenido) {
        // Mapear campos a columnas de la BD
        const endpoint = `/supervisor-pedidos/filtro-opciones/${campo}`;
        
        fetch(endpoint)
            .then(response => response.json())
            .then(data => {
                modalTitulo = document.getElementById('modalFiltroTitulo');
                modalTitulo.textContent = titulo;
                
                // Crear HTML con buscador y checkboxes
                filtroContenido.innerHTML = `
                    <div class="form-group">
                        <input type="text" id="buscadorFiltro" class="form-control" placeholder="Buscar..." style="margin-bottom: 1rem;">
                        <div id="listaOpciones" style="max-height: 300px; overflow-y: auto;">
                            ${data.opciones.map(opcion => `
                                <label style="display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem; cursor: pointer; border-radius: 4px; transition: background 0.2s;">
                                    <input type="checkbox" name="${campo}" value="${opcion}" class="filtro-checkbox">
                                    <span>${opcion || '(Sin especificar)'}</span>
                                </label>
                            `).join('')}
                        </div>
                    </div>
                `;
                
                // Agregar funcionalidad de búsqueda
                setTimeout(() => {
                    document.getElementById('buscadorFiltro')?.addEventListener('input', function(e) {
                        const valor = e.target.value.toLowerCase();
                        document.querySelectorAll('#listaOpciones label').forEach(label => {
                            const texto = label.textContent.toLowerCase();
                            label.style.display = texto.includes(valor) ? 'flex' : 'none';
                        });
                    });
                }, 0);
                
                modal.style.display = 'flex';
            })
            .catch(error => {
                filtroContenido.innerHTML = `<p style="color: red;">Error cargando opciones de filtro</p>`;
                modal.style.display = 'flex';
            });
    }

    function cerrarModalFiltro() {
        document.getElementById('modalFiltro').style.display = 'none';
        filtroActual = null;
    }

    function aplicarFiltroColumna(event) {
        event.preventDefault();
        
        // Construir URL con parámetros actuales
        const url = new URL(window.location);
        
        // Obtener todos los checkboxes seleccionados
        const checkboxes = document.querySelectorAll('.filtro-checkbox:checked');
        const valoresSeleccionados = Array.from(checkboxes).map(cb => cb.value);
        
        // Limpiar parámetros anteriores según el filtro actual
        if (filtroActual === 'id-orden') {
            url.searchParams.delete('numero');
            if (valoresSeleccionados.length > 0) url.searchParams.set('numero', valoresSeleccionados.join(','));
        } else if (filtroActual === 'cliente') {
            url.searchParams.delete('cliente');
            if (valoresSeleccionados.length > 0) url.searchParams.set('cliente', valoresSeleccionados.join(','));
        } else if (filtroActual === 'fecha') {
            url.searchParams.delete('fecha_desde');
            url.searchParams.delete('fecha_hasta');
            const desde = document.getElementById('filtroDesde')?.value;
            const hasta = document.getElementById('filtroHasta')?.value;
            if (desde) url.searchParams.set('fecha_desde', desde);
            if (hasta) url.searchParams.set('fecha_hasta', hasta);
        } else if (filtroActual === 'estado') {
            url.searchParams.delete('estado');
            if (valoresSeleccionados.length > 0) url.searchParams.set('estado', valoresSeleccionados.join(','));
        } else if (filtroActual === 'asesora') {
            url.searchParams.delete('asesora');
            if (valoresSeleccionados.length > 0) url.searchParams.set('asesora', valoresSeleccionados.join(','));
        } else if (filtroActual === 'forma-pago') {
            url.searchParams.delete('forma_pago');
            if (valoresSeleccionados.length > 0) url.searchParams.set('forma_pago', valoresSeleccionados.join(','));
        }
        
        window.location.href = url.toString();
    }

    // Cerrar modal al hacer clic fuera
    document.getElementById('modalFiltro')?.addEventListener('click', function(e) {
        if (e.target === this) cerrarModalFiltro();
    });

    // ===== MODALES DE ÓRDENES =====
    function verOrdenComparar(ordenId) {
        document.getElementById(`ver-menu-${ordenId}`).style.display = 'none';
        abrirModalComparar(ordenId);
    }

    function cerrarModalVerOrden() {
        document.getElementById('modalVerOrden').style.display = 'none';
    }

    function abrirModalAnulacion(ordenId, numeroOrden) {
        document.getElementById('ordenNumero').textContent = '#' + numeroOrden;
        document.getElementById('formAnulacion').dataset.ordenId = ordenId;
        document.getElementById('motivoAnulacion').value = '';
        document.getElementById('contadorActual').textContent = '0';
        document.getElementById('modalAnulacion').style.display = 'flex';
    }

    function cerrarModalAnulacion() {
        document.getElementById('modalAnulacion').style.display = 'none';
    }

    function confirmarAnulacion(event) {
        event.preventDefault();
        
        const ordenId = document.getElementById('formAnulacion').dataset.ordenId;
        const motivo = document.getElementById('motivoAnulacion').value;

        fetch(`/supervisor-pedidos/${ordenId}/anular`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({
                motivo_anulacion: motivo,
            }),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Orden anulada correctamente');
                // Recargar notificaciones si la función existe
                if (typeof cargarNotificacionesPendientes === 'function') {
                    cargarNotificacionesPendientes();
                }
                // Cerrar modal y recargar después de 1 segundo
                cerrarModalAnulacion();
                setTimeout(() => location.reload(), 1000);
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            alert('Error al anular la orden');
        });
    }

    // Contador de caracteres
    document.getElementById('motivoAnulacion')?.addEventListener('input', function() {
        document.getElementById('contadorActual').textContent = this.value.length;
        const btnConfirmar = document.getElementById('btnConfirmarAnulacion');
        if (btnConfirmar) {
            btnConfirmar.disabled = this.value.length < 10 || this.value.length > 500;
        }
    });

    // Cerrar modales al hacer clic fuera
    document.getElementById('modalVerOrden')?.addEventListener('click', function(e) {
        if (e.target === this) cerrarModalVerOrden();
    });

    document.getElementById('modalAnulacion')?.addEventListener('click', function(e) {
        if (e.target === this) cerrarModalAnulacion();
    });

    // Función para aprobar orden
    function aprobarOrden(ordenId, numeroOrden) {
        if (!confirm(`¿Confirmar aprobación de orden #${numeroOrden}?`)) {
            return;
        }

        fetch(`/supervisor-pedidos/${ordenId}/aprobar`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({}),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Orden aprobada correctamente');
                // Recargar notificaciones si la función existe
                if (typeof cargarNotificacionesPendientes === 'function') {
                    cargarNotificacionesPendientes();
                }
                // Recargar la página después de 1 segundo
                setTimeout(() => location.reload(), 1000);
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            alert('Error al aprobar la orden');
        });
    }

    // Función para ver detalles de orden (orden-detail-modal)
    // Cierra el menú y abre el modal de detalles
    function verOrdenDetalles(ordenId) {
        // Cerrar el menú ver
        const menu = document.getElementById(`ver-menu-${ordenId}`);
        if (menu) {
            menu.style.display = 'none';
        }
        
        // Abrir el modal de detalles usando la función externa
        openOrderDetailModal(ordenId);
    }

    // Función para abrir el seguimiento
    function abrirSeguimiento(ordenId) {
        // Cerrar el menú ver
        const menu = document.getElementById(`ver-menu-${ordenId}`);
        if (menu) {
            menu.style.display = 'none';
        }
        
        // Abrir el modal de seguimiento usando la función externa
        if (typeof openOrderTrackingModal === 'function') {
            openOrderTrackingModal(ordenId);
        }
    }
</script>

<!-- Modal Overlay y Wrapper para Detalles de Orden -->
<div id="dropdowns-container" style="position: fixed; top: 0; left: 0; z-index: 999999; pointer-events: none;"></div>

<div id="modal-overlay" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.5); backdrop-filter: blur(4px); z-index: 9997; display: none;" onclick="closeModalOverlay()"></div>

<div id="order-detail-modal-wrapper" style="width: 90%; max-width: 90vw; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 9998; pointer-events: auto; display: none;">
    <x-orders-components.order-detail-modal />
</div>

<!-- Modal Wrapper para Detalles de Orden - LOGO -->
<div id="order-detail-modal-wrapper-logo" style="width: 90%; max-width: 90vw; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 9998; pointer-events: auto; display: none;">
    <x-orders-components.order-detail-modal-logo />
</div>

<!-- Modal Comparar Pedido y Cotización -->
<x-supervisor-pedidos.modal-comparar-pedido />

<!-- Modal Seguimiento del Pedido -->
<x-orders-components.order-tracking-modal />

<!-- Modal para Selector de Recibos (desde asesores) -->
@include('components.modals.recibos-process-selector')

@push('scripts')
    <!-- Scripts para funcionalidad de asesores -->
    <script src="{{ asset('js/asesores/pedidos-dropdown-simple.js') }}"></script>
    <script src="{{ asset('js/invoice-preview-live.js') }}"></script>
    <script src="{{ asset('js/asesores/invoice-from-list.js') }}"></script>
    <script src="{{ asset('js/asesores/receipt-manager.js') }}"></script>
    <script src="{{ asset('js/asesores/pedidos-detail-modal.js') }}"></script>
    <script src="{{ asset('js/asesores/pedidos-anular.js') }}"></script>
    <script src="{{ asset('js/utilidades/galeria-service.js') }}"></script>
    
    <!-- Scripts específicos de supervisor -->
    <script src="{{ asset('js/supervisor-pedidos/supervisor-pedidos-detail-modal.js') }}"></script>
    <script src="{{ asset('js/supervisor-pedidos/edit-pedido.js') }}"></script>
    
    <!-- Scripts para Recibos/Procesos -->
    <script type="module" src="{{ asset('js/modulos/pedidos-recibos/loader.js') }}"></script>
    
    <!-- Script para activar dropdowns en supervisor -->
    <script>
        let dropdownAbierto = {};
        
        document.addEventListener('DOMContentLoaded', function() {
            console.log('[Supervisor Dropdowns] DOMContentLoaded iniciado');
            console.log('[Supervisor Dropdowns] Buscando botones btn-ver-dropdown...');
            
            const botones = document.querySelectorAll('.btn-ver-dropdown');
            console.log(`[Supervisor Dropdowns] Encontrados ${botones.length} botones`);
            
            // Cuando se haga clic en cualquier botón btn-ver-dropdown, abrir el dropdown
            document.addEventListener('click', function(e) {
                const btnVerDropdown = e.target.closest('.btn-ver-dropdown');
                if (btnVerDropdown) {
                    console.log('[Supervisor Dropdowns] Clic en botón Ver');
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const menuId = btnVerDropdown.getAttribute('data-menu-id');
                    console.log(`[Supervisor Dropdowns] menuId: ${menuId}`);
                    
                    // Crear el dropdown si no existe
                    let dropdown = document.getElementById(menuId);
                    console.log(`[Supervisor Dropdowns] Dropdown existe: ${dropdown !== null}`);
                    
                    if (!dropdown) {
                        console.log(`[Supervisor Dropdowns] Creando dropdown ${menuId}...`);
                        // Usar la función crearDropdownVer del script pedidos-dropdown-simple.js
                        if (typeof crearDropdownVer === 'function') {
                            console.log('[Supervisor Dropdowns] Función crearDropdownVer disponible');
                            // Llamar a la función interna
                            dropdown = crearDropdownVer(btnVerDropdown);
                            console.log(`[Supervisor Dropdowns] Dropdown creado: ${dropdown !== null}`);
                            dropdownAbierto[menuId] = false; // Inicializar estado
                        } else {
                            console.error('[Supervisor Dropdowns] Función crearDropdownVer NO disponible');
                        }
                    }
                    
                    if (dropdown) {
                        console.log(`[Supervisor Dropdowns] Estado actual: ${dropdownAbierto[menuId] ? 'ABIERTO' : 'CERRADO'}`);
                        
                        // Cerrar otros dropdowns abiertos
                        Object.keys(dropdownAbierto).forEach(id => {
                            if (id !== menuId && dropdownAbierto[id]) {
                                const otroDropdown = document.getElementById(id);
                                if (otroDropdown) {
                                    otroDropdown.style.display = 'none';
                                    otroDropdown.style.pointerEvents = 'none';
                                    dropdownAbierto[id] = false;
                                    console.log(`[Supervisor Dropdowns] Cerrado dropdown anterior: ${id}`);
                                }
                            }
                        });
                        
                        // Toggle del dropdown actual
                        if (!dropdownAbierto[menuId]) {
                            // Posicionar el dropdown cerca del botón
                            const rect = btnVerDropdown.getBoundingClientRect();
                            dropdown.style.top = (rect.bottom + 5) + 'px';
                            dropdown.style.left = (rect.left) + 'px';
                            dropdown.style.display = 'block';
                            dropdown.style.pointerEvents = 'auto';
                            dropdownAbierto[menuId] = true;
                            console.log('[Supervisor Dropdowns] Dropdown abierto');
                        } else {
                            dropdown.style.display = 'none';
                            dropdown.style.pointerEvents = 'none';
                            dropdownAbierto[menuId] = false;
                            console.log('[Supervisor Dropdowns] Dropdown cerrado');
                        }
                    } else {
                        console.error('[Supervisor Dropdowns] No se pudo crear el dropdown');
                    }
                }
            });
            
            // Cerrar dropdown al hacer clic afuera
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.btn-ver-dropdown') && !e.target.closest('.dropdown-menu')) {
                    document.querySelectorAll('.dropdown-menu').forEach(menu => {
                        const id = menu.id;
                        if (dropdownAbierto[id]) {
                            menu.style.display = 'none';
                            menu.style.pointerEvents = 'none';
                            dropdownAbierto[id] = false;
                        }
                    });
                }
            });
        });
        
        // Función para cerrar dropdowns
        function closeDropdown() {
            console.log('[closeDropdown] Cerrando dropdowns');
            document.querySelectorAll('.dropdown-menu').forEach(menu => {
                const id = menu.id;
                menu.style.display = 'none';
                menu.style.pointerEvents = 'none';
                dropdownAbierto[id] = false;
            });
        }
        
        // Función para toggle de factura (compatible con order-detail-modal)
        window.toggleFactura = function() {
            // Usar Galeria si está disponible
            if (typeof Galeria !== 'undefined' && Galeria.toggleFactura) {
                Galeria.toggleFactura('order-detail-modal-wrapper', 'btn-factura', 'btn-galeria');
            }
        };
        
        // Función para abrir imagen en grande desde la galería
        window.abrirModalImagenProcesoGrande = function(indice, fotosJSON) {
            // Importar GalleryManager y llamar su método estático
            import('./js/modulos/pedidos-recibos/components/GalleryManager.js').then(module => {
                const { GalleryManager } = module;
                if (GalleryManager) {
                    GalleryManager.abrirModalImagenProcesoGrande(indice, fotosJSON);
                }
            }).catch(err => console.error('Error cargando GalleryManager:', err));
        };
    </script>
@endpush

@endsection

