@extends('supervisor-pedidos.layout')

@section('title', 'Supervisión de Pedidos')
@section('page-title', 'Supervisión de Pedidos')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/supervisor-pedidos/index.css') }}">
    <!-- CSS para modal-agregar-prenda-nueva y formularios de edición -->
    <link rel="stylesheet" href="{{ asset('css/crear-pedido.css') }}">
    <link rel="stylesheet" href="{{ asset('css/crear-pedido-editable.css') }}">
    <link rel="stylesheet" href="{{ asset('css/form-modal-consistency.css') }}">
    <link rel="stylesheet" href="{{ asset('css/swal-z-index-fix.css') }}">
    <link rel="stylesheet" href="{{ asset('css/componentes/prendas.css') }}">
    <link rel="stylesheet" href="{{ asset('css/modales/modal-exito-pedido.css') }}">
    <link rel="stylesheet" href="{{ asset('css/modulos/epp-modal.css') }}">
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
                grid-template-columns: 200px 140px 200px 150px 140px 150px 150px;
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
                    <span>Estado</span>
                    <button type="button" class="btn-filter-column" title="Filtrar Estado" style="display: flex; align-items: center; background: none; border: none; color: white; cursor: pointer; padding: 0;">
                        <span class="material-symbols-rounded" style="font-size: 1rem;">filter_alt</span>
                    </button>
                </div>
                <div class="th-wrapper" style="display: flex; align-items: center; gap: 0.5rem;">
                    <span>Novedades</span>
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
                        grid-template-columns: 200px 140px 200px 150px 140px 150px 150px;
                        gap: 1.2rem;
                        padding: 1rem;
                        border-bottom: 1px solid #e5e7eb;
                        align-items: center;
                        min-width: min-content;
                        background: white;
                        transition: background 0.2s ease;
                    " onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background='white'">
                        
                        <!-- Acciones -->
                        <div style="display: flex; gap: 0.5rem; align-items: center; justify-content: center;">
                            <!-- Botón Ver (con dropdown) -->
                            @php
                                $numeroPedido = $orden->numero_pedido ?? 'sin-numero';
                                $pedidoId = $orden->id;
                                $estado = $orden->estado ?? 'Pendiente';
                            @endphp
                            <button class="btn-ver-dropdown" data-menu-id="menu-ver-{{ str_replace('#', '', $numeroPedido) }}" data-pedido="{{ str_replace('#', '', $numeroPedido) }}" data-pedido-id="{{ $pedidoId }}" title="Ver Opciones" style="
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

                            <!-- Botón Aprobar (solo si está pendiente de aprobación) -->
                            @if($estado === 'PENDIENTE_SUPERVISOR')
                            <button onclick="abrirModalAprobacion({{ $orden->id }}, '{{ str_replace('#', '', $numeroPedido) }}')" title="Aprobar Pedido" style="
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
                                <i class="fas fa-check"></i>
                            </button>
                            @endif

                            <!-- Botón Anular (solo si está pendiente de aprobación) -->
                            @if($estado === 'PENDIENTE_SUPERVISOR')
                            <button onclick="abrirModalAnulacion({{ $orden->id }}, '{{ $numeroPedido }}')" title="Pasar a Revisión" style="
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
                            <span style="font-weight: 600; color: #1e5ba8;">#{{ $orden->numero_pedido ?? '-' }}</span>
                        </div>
                        
                        <!-- Cliente -->
                        <div>
                            <span>{{ $orden->cliente }}</span>
                        </div>
                        
                        <!-- Estado -->
                        <div>
                            @php
                                $estadoColors = [
                                    'PENDIENTE_SUPERVISOR' => ['bg' => '#fff3cd', 'text' => '#856404', 'label' => 'Pendiente Supervisor'],
                                    'PENDIENTE_INSUMOS' => ['bg' => '#d1ecf1', 'text' => '#0c5460', 'label' => 'Pendiente Insumos'],
                                    'En Ejecución' => ['bg' => '#d4edda', 'text' => '#155724', 'label' => 'En Ejecución'],
                                    'No iniciado' => ['bg' => '#e2e3e5', 'text' => '#383d41', 'label' => 'No Iniciado'],
                                    'Entregado' => ['bg' => '#d4edda', 'text' => '#155724', 'label' => 'Entregado'],
                                    'Finalizada' => ['bg' => '#d4edda', 'text' => '#155724', 'label' => 'Finalizada'],
                                    'Anulada' => ['bg' => '#f8d7da', 'text' => '#721c24', 'label' => 'Anulada'],
                                    'DEVUELTO_A_ASESORA' => ['bg' => '#f8d7da', 'text' => '#721c24', 'label' => 'Devuelto'],
                                ];
                                $estadoInfo = $estadoColors[$estado] ?? ['bg' => '#e2e3e5', 'text' => '#383d41', 'label' => $estado];
                            @endphp
                            <span style="background: {{ $estadoInfo['bg'] }}; color: {{ $estadoInfo['text'] }}; padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: bold; white-space: nowrap; display: inline-block;">
                                {{ $estadoInfo['label'] }}
                            </span>
                        </div>
                        
                        <!-- Novedades -->
                        <div>
                            @php
                                $novedades_count = 0;
                                if (!empty($orden->novedades)) {
                                    // Contar por doble salto de línea que es el separador entre novedades
                                    $novedades_count = count(array_filter(explode("\n\n", $orden->novedades)));
                                }
                            @endphp
                            @if($novedades_count > 0)
                                <button class="btn-novedades" type="button" data-orden-id="{{ $orden->id }}" data-novedades='{{ json_encode($orden->novedades, JSON_UNESCAPED_UNICODE) }}' style="background: #e8f3ff; color: #1e40af; padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: bold; white-space: nowrap; border: 1px solid #bfdbfe; cursor: pointer; transition: all 0.2s ease;">
                                    {{ $novedades_count }} novedades
                                </button>
                            @else
                                <span style="background: #f3f4f6; color: #9ca3af; padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: bold; white-space: nowrap;">
                                    Sin novedades
                                </span>
                            @endif
                        </div>
                        
                        <!-- Asesora -->
                        <div>
                            <span>{{ $orden->asesora?->name ?? 'N/A' }}</span>
                        </div>
                        
                        <!-- Forma Pago -->
                        <div>
                            <span>{{ $orden->forma_de_pago ?? 'N/A' }}</span>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    </div>

    <!-- Paginación Personalizada -->
    @if($ordenes->lastPage() > 1 || $ordenes->count() > 0)
        <div style="margin-top: 1.5rem; display: flex; justify-content: center; align-items: center; gap: 8px; flex-wrap: wrap;">
            <!-- Botón Primera Página (<<) -->
            @if($ordenes->onFirstPage())
                <button disabled style="min-width: 36px; height: 36px; padding: 0 12px; background: #f0f0f0; border: 1px solid #ddd; border-radius: 6px; cursor: not-allowed; color: #999; font-weight: 600;">
                    &laquo;&laquo;
                </button>
            @else
                <a href="{{ $ordenes->url(1) }}" style="min-width: 36px; height: 36px; padding: 0 12px; background: #ffffff; border: 1px solid #ddd; border-radius: 6px; cursor: pointer; color: #333; font-weight: 600; display: flex; align-items: center; justify-content: center; text-decoration: none; transition: all 0.3s ease;" onmouseover="this.style.background='#e9ecef'; this.style.borderColor='#adb5bd';" onmouseout="this.style.background='#ffffff'; this.style.borderColor='#ddd';">
                    &laquo;&laquo;
                </a>
            @endif

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

            <!-- Botón Última Página (>>) -->
            @if($ordenes->currentPage() == $ordenes->lastPage())
                <button disabled style="min-width: 36px; height: 36px; padding: 0 12px; background: #f0f0f0; border: 1px solid #ddd; border-radius: 6px; cursor: not-allowed; color: #999; font-weight: 600;">
                    &raquo;&raquo;
                </button>
            @else
                <a href="{{ $ordenes->url($ordenes->lastPage()) }}" style="min-width: 36px; height: 36px; padding: 0 12px; background: #ffffff; border: 1px solid #ddd; border-radius: 6px; cursor: pointer; color: #333; font-weight: 600; display: flex; align-items: center; justify-content: center; text-decoration: none; transition: all 0.3s ease;" onmouseover="this.style.background='#e9ecef'; this.style.borderColor='#adb5bd';" onmouseout="this.style.background='#ffffff'; this.style.borderColor='#ddd';">
                    &raquo;&raquo;
                </a>
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
            <h2>¿Pasar a Revisión Orden <span id="ordenNumero"></span>?</h2>
        </div>

        <div class="modal-body">
            <p class="advertencia-texto">
                Esta acción enviará la orden de vuelta a la asesora para revisión. Por favor ingresa el motivo de la revisión.
            </p>

            <form id="formAnulacion" onsubmit="confirmarAnulacion(event)">
                @csrf
                <div class="form-group">
                    <label for="motivoAnulacion">Motivo de la revisión *</label>
                    <textarea 
                        id="motivoAnulacion" 
                        name="motivo_anulacion" 
                        class="form-control" 
                        rows="4" 
                        placeholder="Ej: Revisar precios, errores en especificaciones..."
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
                        Pasar a Revisión
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de Éxito para Pasar a Revisión -->
<div id="modalExitoRevision" class="modal-overlay" style="display: none;">
    <div class="modal-content modal-anulacion" style="text-align: center; max-width: 400px;">
        <div class="modal-header">
            <div class="header-icon" style="background: #d4edda; color: #28a745;">
                <span class="material-symbols-rounded">check_circle</span>
            </div>
            <h2>¡Éxito!</h2>
        </div>

        <div class="modal-body">
            <p style="color: #28a745; font-weight: 500; margin-bottom: 1.5rem;">
                La orden ha sido enviada a revisión correctamente
            </p>
            <p style="color: #7f8c8d; font-size: 0.9rem; margin-bottom: 1.5rem;">
                La asesora recibirá la notificación del cambio.
            </p>

            <div class="form-actions" style="justify-content: center;">
                <button type="button" class="btn btn-success" onclick="cerrarModalExitoRevision()">
                    Aceptar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Novedades -->
<div id="modalNovedades" class="modal-overlay" style="display: none;">
    <div class="modal-content" style="width: 90%; max-width: 700px; max-height: 75vh; display: flex; flex-direction: column;">
        <div class="modal-header" style="border-bottom: 2px solid #1e40af; background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%); color: white; padding: 1.5rem;">
            <h2 style="margin: 0; font-size: 1.2rem; color: white;"> Historial de Novedades</h2>
            <button class="btn-close" onclick="cerrarModalNovedades()" style="background: none; border: none; cursor: pointer; font-size: 1.5rem; color: white; position: absolute; right: 1rem; top: 1rem;">
                <span class="material-symbols-rounded">close</span>
            </button>
        </div>
        <div id="modalNovedadesContent" style="overflow-y: auto; flex: 1; padding: 2rem; background: #f9fafb; margin: 0; border: none; color: #1f2937;">
        <!-- Contenido de novedades formateado -->
        </div>

    </div>
</div>

<!-- Modal Editar Pedido -->


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
            case 'numero':
                titulo = 'Filtrar por Número';
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
            case 'forma_pago':
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
        } else if (filtroActual === 'numero') {
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
        } else if (filtroActual === 'forma_pago') {
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

    /**
     * EDICIÓN DE PEDIDOS - Sincronizada con asesores
     * Usa el mismo endpoint /api/pedidos/{id} y abre el mismo modal
     */
    async function editarPedido(pedidoId) {
        // 🔒 Prevenir múltiples clics simultáneos
        if (window.edicionEnProgreso) {
            return;
        }
        
        window.edicionEnProgreso = true;
        const tiempoInicio = performance.now();
        const etapas = {};
        
        try {
            etapas.inicio = performance.now();
            console.log(`[editarPedido]  Iniciando apertura modal - Pedido: ${pedidoId}`);
            
            //  PASO 1: Abrir modal pequeño de carga centrado
            console.log('[editarPedido]  Abriendo modal de carga...');
            await _ensureSwal();
            etapas.swalReady = performance.now();
            console.log(`[editarPedido]  Swal listo: ${(etapas.swalReady - etapas.inicio).toFixed(2)}ms`);
            
            // Mostrar modal pequeño con spinner centrado
            const modalPromise = Swal.fire({
                html: `
                    <div style="text-align: center; padding: 2rem;">
                        <div style="width: 60px; height: 60px; border: 4px solid #e5e7eb; border-top-color: #1e40af; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 1.5rem;"></div>
                        <p style="color: #6b7280; font-size: 14px; font-weight: 500; margin: 0;">Cargando datos del pedido...</p>
                    </div>
                    <style>
                        @keyframes spin {
                            to { transform: rotate(360deg); }
                        }
                    </style>
                `,
                width: '300px',
                padding: '0',
                background: 'white',
                showConfirmButton: false,
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: (modal) => {
                    const swalContainer = document.querySelector('.swal2-container');
                    if (swalContainer) {
                        swalContainer.style.display = 'flex';
                        swalContainer.style.alignItems = 'center';
                        swalContainer.style.justifyContent = 'center';
                    }
                    document.body.style.overflow = 'hidden';
                }
            });

            //  PASO 2: Cargar módulos en segundo plano (con preloader inteligente)
            if (!window.PrendaEditorPreloader?.isReady?.()) {
                console.log('[editarPedido]  Cargando módulos de edición (con preloader)...');
                try {
                    await window.PrendaEditorPreloader.loadWithLoader({
                        title: 'Cargando datos',
                        message: 'Por favor espera...',
                        onComplete: () => {
                            console.log('[editarPedido]  Módulos cargados completamente');
                        }
                    });
                    etapas.modulosCargados = performance.now();
                    console.log(`[editarPedido]  Módulos cargados: ${(etapas.modulosCargados - etapas.swalReady).toFixed(2)}ms`);
                } catch (error) {
                    console.error('[editarPedido]  Error cargando módulos:', error);
                    Swal.close();
                    alert('Error: No se pudieron cargar los módulos de edición');
                    window.edicionEnProgreso = false;
                    return;
                }
            } else {
                etapas.modulosCargados = performance.now();
                console.log('[editarPedido] ⚡ Módulos ya precargados en background (cache)');
            }

            //  PASO 3: Fetch de datos mientras el modal ya está visible
            console.log('[editarPedido] 📥 Cargando datos completos del servidor...');

            const response = await fetch(`/api/pedidos/${pedidoId}`, {
                method: 'GET',
                credentials: 'include',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const respuesta = await response.json();

            if (!respuesta.success) {
                throw new Error(respuesta.message || 'Error desconocido');
            }

            const datos = respuesta.data || respuesta.datos;
            etapas.fetchCompleto = performance.now();
            console.log(`[editarPedido]  Fetch completado: ${(etapas.fetchCompleto - etapas.modulosCargados).toFixed(2)}ms`);
            
            // Transformar datos al formato que espera abrirModalEditarPedido
            const datosTransformados = {
                id: datos.id || datos.numero_pedido,
                numero_pedido: datos.numero_pedido || datos.numero,
                numero: datos.numero || datos.numero_pedido,
                cliente: datos.cliente || 'Cliente sin especificar',
                asesora: datos.asesor || datos.asesora?.name || 'Asesor sin especificar',
                estado: datos.estado || 'Pendiente',
                forma_de_pago: datos.forma_pago || datos.forma_de_pago || 'No especificada',
                prendas: datos.prendas || [],
                epps: datos.epps_transformados || datos.epps || [],
                procesos: datos.procesos || [],
                // Copiar todas las otras propiedades
                ...datos
            };

            console.log('[editarPedido]  Datos cargados:', {
                id: datosTransformados.id,
                numero: datosTransformados.numero_pedido,
                cliente: datosTransformados.cliente,
                prendas: datosTransformados.prendas?.length || 0,
                procesos: datosTransformados.procesos?.length || 0
            });

            //  PASO 4: Reemplazar modal de carga con contenido real
            etapas.antes_modal = performance.now();
            console.log(`[editarPedido] 🎬 Abriendo modal de edición...`);
            
            await abrirModalEditarPedido(pedidoId, datosTransformados, 'editar');
            
            etapas.fin = performance.now();
            console.log(`
[editarPedido]  RESUMEN DE TIEMPOS:
  └─ Swal Ready: ${(etapas.swalReady - etapas.inicio).toFixed(2)}ms
  └─ Módulos: ${(etapas.modulosCargados - etapas.swalReady).toFixed(2)}ms
  └─ Fetch: ${(etapas.fetchCompleto - etapas.modulosCargados).toFixed(2)}ms
  └─ Modal: ${(etapas.fin - etapas.antes_modal).toFixed(2)}ms
  └─ TOTAL: ${(etapas.fin - etapas.inicio).toFixed(2)}ms
            `);

        } catch (err) {
            Swal.close();
            console.error('[editarPedido]  Error:', err);
            alert('Error: No se pudo cargar el pedido: ' + err.message);
            
        } finally {
            window.edicionEnProgreso = false;
        }
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
                // Cerrar modal de revisión
                cerrarModalAnulacion();
                // Mostrar modal de éxito
                document.getElementById('modalExitoRevision').style.display = 'flex';
                // Recargar notificaciones si la función existe
                if (typeof cargarNotificacionesPendientes === 'function') {
                    cargarNotificacionesPendientes();
                }
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            alert('Error al enviar la orden a revisión');
        });
    }

    function cerrarModalExitoRevision() {
        document.getElementById('modalExitoRevision').style.display = 'none';
        // Recargar la página después de cerrar
        setTimeout(() => location.reload(), 300);
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

    document.getElementById('modalExitoRevision')?.addEventListener('click', function(e) {
        if (e.target === this) cerrarModalExitoRevision();
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
        console.log('=== [abrirSeguimiento] Iniciado ===');
        console.log('ordenId:', ordenId);
        
        // Cerrar el menú ver
        const menu = document.getElementById(`ver-menu-${ordenId}`);
        if (menu) {
            console.log('[abrirSeguimiento] Cerrando menú:', `ver-menu-${ordenId}`);
            menu.style.display = 'none';
        } else {
            console.log('[abrirSeguimiento] No se encontró el menú:', `ver-menu-${ordenId}`);
        }
        
        // Log de funciones disponibles
        console.log('[abrirSeguimiento] Verificando si openOrderTrackingModal está disponible');
        console.log('[abrirSeguimiento] typeof openOrderTrackingModal:', typeof openOrderTrackingModal);
        
        // Abrir el modal de seguimiento usando la función externa
        if (typeof openOrderTrackingModal === 'function') {
            console.log('[abrirSeguimiento] ✓ openOrderTrackingModal está disponible, llamando...');
            try {
                openOrderTrackingModal(ordenId);
                console.log('[abrirSeguimiento] ✓ openOrderTrackingModal llamado exitosamente');
            } catch (error) {
                console.error('[abrirSeguimiento] ✗ Error al llamar openOrderTrackingModal:', error);
                console.error('[abrirSeguimiento] Stack:', error.stack);
                alert('Error en openOrderTrackingModal: ' + error.message);
            }
        } else {
            console.error('[abrirSeguimiento] ✗ openOrderTrackingModal NO está disponible');
            console.log('[abrirSeguimiento] Funciones globales con "tracking":', Object.keys(window).filter(k => k.toLowerCase().includes('tracking')));
            console.log('[abrirSeguimiento] Funciones globales con "orden":', Object.keys(window).filter(k => k.toLowerCase().includes('orden')));
            alert('Error: openOrderTrackingModal no está disponible. Intenta nuevamente.');
        }
    }
</script>

<!-- Modal Overlay y Wrapper para Detalles de Orden -->
<div id="dropdowns-container" style="position: fixed; top: 0; left: 0; z-index: 999999; pointer-events: none;"></div>

<div id="modal-overlay" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.5); backdrop-filter: blur(4px); z-index: 9997; display: none;" onclick="closeModalOverlay()"></div>

<div id="order-detail-modal-wrapper" style="width: 90%; max-width: 90vw; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 9998; pointer-events: auto; display: none; border-radius: 8px;">
    <x-orders-components.order-detail-modal />
</div>

<!-- Modal Wrapper para Detalles de Orden - LOGO -->
<div id="order-detail-modal-wrapper-logo" style="width: 90%; max-width: 90vw; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 9998; pointer-events: auto; display: none; border-radius: 8px;">
    <x-orders-components.order-detail-modal-logo />
</div>

<!-- Modal Comparar Pedido y Cotización -->
<x-supervisor-pedidos.modal-comparar-pedido />

<!-- Modal Seguimiento del Pedido -->
<x-orders-components.order-tracking-modal />

<!-- Script para funcionalidad del modal de seguimiento -->
@include('components.orders-components.tracking-modal-script')

<!-- Modal para Selector de Recibos (desde asesores) -->
@include('components.modals.recibos-process-selector')

<!-- Modal Editar Pedido (desde asesores) - Componente completo para edición de pedidos -->
@include('asesores.pedidos.components.modal-editar-pedido')

<!-- Componentes de módulos de edición (desde asesores) -->
@include('asesores.pedidos.components.modal-prendas-lista')
@include('asesores.pedidos.components.modal-agregar-prenda')
@include('asesores.pedidos.modals.modal-agregar-prenda-nueva')
@include('asesores.pedidos.components.modal-editar-prenda')
@include('asesores.pedidos.components.modal-editar-epp')

<!-- Modal para Seleccionar Tallas -->
@include('asesores.pedidos.modals.modal-seleccionar-tallas')

<!-- Modal para Editar Procesos Genéricos -->
@include('asesores.pedidos.modals.modal-proceso-generico')

@push('scripts')
    <!--  SERVICIOS CENTRALIZADOS (Requeridos para modal-editar-pedido) -->
    <script src="{{ asset('js/utilidades/validation-service.js') }}"></script>
    <script src="{{ asset('js/utilidades/ui-modal-service.js') }}"></script>
    <script src="{{ asset('js/utilidades/deletion-service.js') }}"></script>
    <script src="{{ asset('js/utilidades/galeria-service.js') }}"></script>
    
    <!--  SERVICIO DE ALMACENAMIENTO DE IMÁGENES (Requerido para agregar/eliminar imágenes) -->
    <script src="{{ asset('js/modulos/crear-pedido/fotos/image-storage-service.js') }}"></script>
    
    <!--  LAZY LOADERS: Cargan módulos bajo demanda (Requeridos para modal-editar-pedido) -->
    <script src="{{ asset('js/lazy-loaders/prenda-editor-preloader.js') }}"></script>
    <script src="{{ asset('js/lazy-loaders/prenda-editor-loader-modular.js') }}"></script>
    <script src="{{ asset('js/lazy-loaders/epp-manager-loader.js') }}"></script>

    <!-- Scripts para edición de prendas desde lista de pedidos (requeridos por editarPrendaDePedido) -->
    <script defer src="{{ asset('js/modulos/crear-pedido/prendas/prenda-editor.js') }}"></script>
    <script defer src="{{ asset('js/modulos/crear-pedido/tallas/gestion-tallas.js') }}"></script>
    <script defer src="{{ asset('js/modulos/crear-pedido/telas/telas-module/manejo-imagenes.js') }}"></script>
    <script defer src="{{ asset('js/componentes/prenda-form-collector.js') }}"></script>
    <script defer src="{{ asset('js/componentes/prenda-editor-pedidos-adapter.js') }}"></script>
    
    <!-- Inicializador de servicios de imágenes -->
    <script src="{{ asset('js/modulos/crear-pedido/inicializadores/init-storage-servicios.js') }}"></script>
    
    <!-- Manejadores de procesos - Para edición de procesos desde supervisor -->
    <script src="{{ asset('js/modulos/crear-pedido/procesos/manejadores-procesos-prenda.js') }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/procesos/gestor-modal-proceso-generico.js') }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/procesos/renderizador-tarjetas-procesos.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/componentes/procesos-imagenes-storage.js') }}"></script>
    <script src="{{ asset('js/componentes/manejo-imagenes-proceso.js') }}"></script>
    <script src="{{ asset('js/componentes/manejador-imagen-proceso-con-indice.js') }}"></script>
    
    <!-- Scripts para funcionalidad de asesores - Módulos Desacoplados -->
    <script src="{{ asset('js/asesores/pedidos-dropdown-simple.js') }}"></script>
    <script src="{{ asset('js/asesores/pedidos-modal-edit.js') }}"></script>
    <!-- Scripts para Vista Previa en Vivo de Factura - Módulos Desacoplados -->
    <script src="{{ asset('js/modulos/invoice/ImageGalleryManager.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/modulos/invoice/FormDataCaptureService.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/modulos/invoice/InvoiceRenderer.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/modulos/invoice/ModalManager.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/modulos/invoice/InvoiceExportService.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/invoice-preview-live.js') }}"></script>
    <!-- Scripts para Vista de Factura desde Lista - Lazy Loading -->
    <script src="{{ asset('js/modulos/invoice/InvoiceLazyLoader.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/asesores/invoice-from-list.js') }}"></script>
    <script src="{{ asset('js/asesores/receipt-manager.js') }}"></script>
    <script src="{{ asset('js/asesores/pedidos-detail-modal.js') }}"></script>
    <script src="{{ asset('js/asesores/pedidos-anular.js') }}"></script>
    
    <!-- Scripts específicos de supervisor -->
    <script src="{{ asset('js/supervisor-pedidos/supervisor-pedidos-detail-modal.js') }}"></script>
    <script src="{{ asset('js/orders js/tracking-modal-handler.js') }}"></script>
    
    <!-- Script para abrir el modal de seguimiento (inline para asegurar disponibilidad) -->
    <script>
        /**
         * Abre el modal de seguimiento del pedido
         * @param {number} ordenId - ID de la orden/pedido
         */
        window.openOrderTrackingModal = function(ordenId) {
            console.log('[openOrderTrackingModal] Abriendo modal para orden:', ordenId);
            
            // Primero verificar que mostrarTrackingModal está disponible
            if (typeof mostrarTrackingModal !== 'function') {
                console.error('[openOrderTrackingModal] ERROR: mostrarTrackingModal no está disponible');
                alert('Error: El modal de seguimiento no está cargado correctamente. Por favor, recarga la página.');
                return;
            }
            
            console.log('[openOrderTrackingModal] mostrarTrackingModal está disponible');
            
            // Obtener datos del pedido desde la ruta de supervisor
            console.log('[openOrderTrackingModal] Obteniendo datos de /supervisor-pedidos/' + ordenId + '/datos');
            
            fetch(`/supervisor-pedidos/${ordenId}/datos`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                credentials: 'same-origin'
            })
                .then(response => {
                    console.log('[openOrderTrackingModal] Response status:', response.status);
                    
                    if (!response.ok) {
                        console.error('[openOrderTrackingModal] HTTP error! status:', response.status);
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(pedidoData => {
                    console.log('[openOrderTrackingModal] Datos del pedido recibidos:', pedidoData);
                    
                    // Si tenemos los datos, intentar obtener los procesos
                    console.log('[openOrderTrackingModal] Obteniendo procesos de /api/ordenes/' + ordenId + '/procesos');
                    
                    return fetch(`/api/ordenes/${ordenId}/procesos`, {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                        },
                        credentials: 'same-origin'
                    })
                        .then(procResponse => {
                            console.log('[openOrderTrackingModal] Procesos response status:', procResponse.status);
                            
                            // Si la respuesta es exitosa, agregar los procesos
                            if (procResponse.ok) {
                                return procResponse.json().then(procesos => {
                                    console.log('[openOrderTrackingModal] Procesos obtenidos:', procesos);
                                    pedidoData.procesos = procesos;
                                    return pedidoData;
                                });
                            }
                            // Si falla, devolver los datos sin procesos
                            console.warn('[openOrderTrackingModal] No se pudieron cargar los procesos (status ' + procResponse.status + ')');
                            pedidoData.procesos = [];
                            return pedidoData;
                        })
                        .catch(error => {
                            console.warn('[openOrderTrackingModal] Error al obtener procesos:', error);
                            pedidoData.procesos = [];
                            return pedidoData;
                        });
                })
                .then(data => {
                    console.log('[openOrderTrackingModal] Datos finales listos. Llamando a mostrarTrackingModal...');
                    
                    // Verificar nuevamente que la función existe
                    if (typeof mostrarTrackingModal !== 'function') {
                        console.error('[openOrderTrackingModal] ERROR: mostrarTrackingModal no está disponible en el then final');
                        alert('Error: El modal de seguimiento no está cargado correctamente.');
                        return;
                    }
                    
                    // Llamar a la función que rellena y muestra el modal
                    try {
                        mostrarTrackingModal(data);
                        console.log('[openOrderTrackingModal] Modal mostrado exitosamente');
                    } catch (e) {
                        console.error('[openOrderTrackingModal] Error al llamar mostrarTrackingModal:', e);
                        alert('Error: ' + e.message);
                    }
                })
                .catch(error => {
                    console.error('[openOrderTrackingModal] Error general:', error);
                    alert('Error: No se puede abrir el seguimiento. Intenta nuevamente.');
                });
        };

        /**
         * Cierra el modal de seguimiento
         */
        window.closeOrderTracking = function() {
            console.log('[closeOrderTracking] Cerrando modal de seguimiento');
            const modal = document.getElementById('orderTrackingModal');
            if (modal) {
                modal.style.display = 'none';
            }
        };
    </script>
    
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
        window.abrirModalImagenProcesoGrande = (function() {
            let galleryManagerLoaded = false;
            let GalleryManager = null;
            
            return async function(indice, fotosJSON) {
                console.log('[GalleryManager] Intentando abrir imagen:', indice);
                
                // Si ya está cargado, usar directamente
                if (galleryManagerLoaded && GalleryManager) {
                    return GalleryManager.abrirModalImagenProcesoGrande(indice, fotosJSON);
                }
                
                try {
                    // Intentar cargar el módulo GalleryManager
                    console.log('[GalleryManager] Cargando módulo...');
                    
                    // Primero intentar con la ruta relativa
                    try {
                        const module = await import('./js/modulos/pedidos-recibos/components/GalleryManager.js');
                        GalleryManager = module.GalleryManager;
                        galleryManagerLoaded = true;
                        console.log('[GalleryManager] Módulo cargado correctamente');
                    } catch (importError) {
                        console.warn('[GalleryManager] Error con ruta relativa, intentando ruta absoluta:', importError);
                        // Si falla, intentar cargar como script global
                        if (typeof window.GalleryManager !== 'undefined') {
                            GalleryManager = window.GalleryManager;
                            galleryManagerLoaded = true;
                            console.log('[GalleryManager] Usando GalleryManager global');
                        } else {
                            throw new Error('No se pudo cargar GalleryManager');
                        }
                    }
                    
                    if (GalleryManager) {
                        return GalleryManager.abrirModalImagenProcesoGrande(indice, fotosJSON);
                    }
                } catch (err) {
                    console.error('[GalleryManager] Error cargando GalleryManager:', err);
                    galleryManagerLoaded = false;
                    
                    // Implementación fallback básica
                    console.log('[GalleryManager] Usando implementación fallback');
                    try {
                        let fotos = typeof fotosJSON === 'string' ? JSON.parse(fotosJSON) : fotosJSON;
                        if (!fotos || !fotos[indice]) {
                            console.error('Imagen no encontrada:', indice);
                            return;
                        }
                        
                        // Crear modal simple
                        const modal = document.createElement('div');
                        modal.style.cssText = `
                            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
                            background: rgba(0,0,0,0.9); z-index: 9999; display: flex;
                            align-items: center; justify-content: center;
                        `;
                        modal.innerHTML = `
                            <div style="position: relative; max-width: 90%; max-height: 90%;">
                                <img src="${fotos[indice]}" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                                <button onclick="this.parentElement.parentElement.remove()" style="
                                    position: absolute; top: 10px; right: 10px;
                                    background: white; border: none; border-radius: 50%;
                                    width: 40px; height: 40px; cursor: pointer; font-size: 20px;
                                ">×</button>
                            </div>
                        `;
                        document.body.appendChild(modal);
                        modal.addEventListener('click', (e) => {
                            if (e.target === modal) modal.remove();
                        });
                    } catch (fallbackErr) {
                        console.error('[GalleryManager] Error en fallback:', fallbackErr);
                    }
                }
            };
        })();

        // ===== FUNCIONES PARA MODAL DE NOVEDADES =====
        window.abrirNovedades = function(ordenId, novedades) {
            console.log('[Novedades] Abriendo modal con ID:', ordenId);
            const modal = document.getElementById('modalNovedades');
            const contenido = document.getElementById('modalNovedadesContent');
            
            if (modal && contenido) {
                // Procesar saltos de línea: reemplazar \n literal con saltos reales
                const procesado = novedades.replace(/\\n/g, '\n');
                
                // Separar por doble salto de línea (separador de novedades)
                const novedadesArray = procesado.split('\n\n').filter(n => n.trim());
                
                // Formatear cada novedad
                let html = '';
                novedadesArray.forEach((novedad, index) => {
                    // Extraer usuario, rol y fecha usando regex
                    const match = novedad.match(/\[(.*?)\]\s(.*)/);
                    
                    if (match) {
                        const header = match[1];
                        const mensaje = match[2];
                        
                        html += `
                            <div style="
                                background: white;
                                border-left: 4px solid #1e40af;
                                padding: 1.2rem;
                                margin-bottom: 1.5rem;
                                border-radius: 4px;
                                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                            ">
                                <div style="
                                    display: flex;
                                    align-items: center;
                                    gap: 0.5rem;
                                    margin-bottom: 0.8rem;
                                    font-weight: 600;
                                    color: #1e40af;
                                    font-size: 0.85rem;
                                ">
                                    <span style="color: #3b82f6;">✓</span>
                                    <span>${escapeHtml(header)}</span>
                                </div>
                                <div style="
                                    color: #374151;
                                    font-size: 0.95rem;
                                    line-height: 1.6;
                                    white-space: pre-wrap;
                                    word-wrap: break-word;
                                ">
                                    ${escapeHtml(mensaje)}
                                </div>
                            </div>
                        `;
                    } else {
                        // Si no coincide el formato, mostrar como está
                        html += `
                            <div style="
                                background: white;
                                border-left: 4px solid #6b7280;
                                padding: 1.2rem;
                                margin-bottom: 1.5rem;
                                border-radius: 4px;
                                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                            ">
                                <div style="
                                    color: #374151;
                                    font-size: 0.95rem;
                                    line-height: 1.6;
                                    white-space: pre-wrap;
                                    word-wrap: break-word;
                                ">
                                    ${escapeHtml(novedad)}
                                </div>
                            </div>
                        `;
                    }
                });
                
                contenido.innerHTML = html;
                modal.style.display = 'flex';
                modal.style.alignItems = 'center';
                modal.style.justifyContent = 'center';
                console.log('[Novedades] Modal abierto');
            }
        };

        // Función auxiliar para escapar HTML
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        window.cerrarModalNovedades = function() {
            console.log('[Novedades] Cerrando modal');
            const modal = document.getElementById('modalNovedades');
            if (modal) {
                modal.style.display = 'none';
            }
        };

        // Event listener para botones de novedades
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.btn-novedades').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const ordenId = this.dataset.ordenId;
                    // El atributo data-novedades contiene una cadena JSON escapada que debe ser parseada
                    const novedadesJson = this.getAttribute('data-novedades');
                    
                    try {
                        // Parsear JSON para obtener la cadena real
                        const novedades = JSON.parse(novedadesJson);
                        abrirNovedades(ordenId, novedades);
                    } catch (err) {
                        console.error('[Novedades] Error al parsear JSON:', err);
                        console.log('[Novedades] JSON raw:', novedadesJson);
                    }
                });
            });

            // Event listener para botones de filtro
            document.querySelectorAll('.btn-filter-column').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    // Determinar qué columna se está filtrando según el título
                    const title = this.getAttribute('title');
                    let columna = '';
                    
                    switch(title) {
                        case 'Filtrar Número':
                            columna = 'numero';
                            break;
                        case 'Filtrar Cliente':
                            columna = 'cliente';
                            break;
                        case 'Filtrar Asesora':
                            columna = 'asesora';
                            break;
                        case 'Filtrar Forma Pago':
                            columna = 'forma_pago';
                            break;
                        default:
                            columna = 'cliente';
                    }
                    
                    console.log('[Filtro] Abriendo filtro para columna:', columna);
                    abrirModalFiltro(columna);
                });
            });
        });

        // Cerrar modal al hacer clic fuera del contenido
        document.getElementById('modalNovedades')?.addEventListener('click', function(e) {
            if (e.target === this) {
                cerrarModalNovedades();
            }
        });

        // ===== FUNCIÓN PARA ABRIR MODAL DE APROBACIÓN =====
        window.abrirModalAprobacion = function(ordenId, numeroPedido) {
            console.log('[Aprobación] Abriendo modal para orden:', { ordenId, numeroPedido });
            
            Swal.fire({
                title: '¿Aprobar Pedido?',
                html: `<p>¿Deseas aprobar el pedido <strong>#${numeroPedido}</strong>?</p>`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#10b981',
                cancelButtonColor: '#6b7280',
                confirmButtonText: '<i class="fas fa-check"></i> Sí, aprobar',
                cancelButtonText: 'Cancelar',
                allowOutsideClick: false,
                allowEscapeKey: false
            }).then((result) => {
                if (result.isConfirmed) {
                    // Mostrar modal de cargando
                    Swal.fire({
                        title: 'Procesando...',
                        html: '<p>Por favor espera mientras se aprueba el pedido</p><div style="margin-top: 20px;"><div class="spinner-border" role="status"><span class="sr-only">Cargando...</span></div></div>',
                        icon: 'info',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    // Enviar solicitud de aprobación
                    fetch(`/supervisor-pedidos/${ordenId}/aprobar`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({})
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                title: '¡Aprobado!',
                                html: `<p>${data.message || 'Pedido aprobado correctamente'}</p><p style="margin-top: 10px; font-weight: 600; color: #10b981;">Estado: ${data.estado}</p>`,
                                icon: 'success',
                                confirmButtonColor: '#10b981'
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                title: 'Error',
                                text: data.message || 'No se pudo aprobar el pedido',
                                icon: 'error',
                                confirmButtonColor: '#ef4444'
                            });
                        }
                    })
                    .catch(error => {
                        console.error('[Aprobación] Error:', error);
                        Swal.fire({
                            title: 'Error',
                            text: 'Error al procesar la solicitud',
                            icon: 'error',
                            confirmButtonColor: '#ef4444'
                        });
                    });
                }
            });
        };
    </script>

    <!--  REALTIME: Listener para actualizaciones de órdenes en tiempo real -->
    <script>
        /**
         * Sistema de Tiempo Real - Supervisor-Pedidos
         * Se suscribe al canal 'supervisor-pedidos' via WebSockets
         * Actualiza la tabla automáticamente cuando llegan nuevas órdenes o cambios
         * 
         * Usa un polling para esperar a que window.waitForEcho esté disponible
         */
        
        // Definir funciones reutilizables ANTES de intentar conectarse
        /**
         * Actualiza una fila existente en la tabla
         */
        function actualizarFilaEnTabla(fila, orden, action) {
            console.log(`[Realtime] Actualizando fila para pedido #${orden.numero_pedido}`);

            // Actualizar campos que pueden haber cambiado
            const celdas = fila.querySelectorAll('[data-field]');
            celdas.forEach(celda => {
                const field = celda.getAttribute('data-field');
                if (orden[field]) {
                    const newValue = orden[field];
                    if (celda.textContent !== newValue) {
                        celda.textContent = newValue;
                        // Agregar animación de cambio
                        celda.style.backgroundColor = '#fff9e6';
                        setTimeout(() => {
                            celda.style.backgroundColor = '';
                        }, 1500);
                    }
                }
            });

            // Cambiar background de la fila para indicar cambio
            fila.style.backgroundColor = '#f0f9ff';
            setTimeout(() => {
                fila.style.backgroundColor = 'white';
            }, 2000);
        }

        /**
         * Agrega una nueva fila a la tabla cuando llega una nueva orden
         */
        function agregarNuevaFilaATabla(orden, action) {
            console.log(`[Realtime] Agregando nueva fila para pedido #${orden.numero_pedido}`);
            
            const tableContainer = document.querySelector('.table-scroll-container');
            if (!tableContainer) {
                console.warn('[Realtime] No se encontró el contenedor de tabla');
                return;
            }

            // Crear HTML de la nueva fila basado en la estructura existente
            const numeroPedido = orden.numero_pedido || orden.numero;
            const filaHTML = `
                <div data-pedido-id="${orden.id}" style="
                    display: grid;
                    grid-template-columns: 200px 140px 200px 140px 150px 150px;
                    gap: 1.2rem;
                    padding: 1rem;
                    border-bottom: 1px solid #e5e7eb;
                    align-items: center;
                    min-width: min-content;
                    background: #f0f9ff;
                    animation: slideInDown 0.5s ease;
                    transition: background 0.2s ease;
                " onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background='#f0f9ff'">
                    <!-- Acciones -->
                    <div style="display: flex; gap: 0.5rem; align-items: center; justify-content: center;">
                        <button class="btn-ver-dropdown" onclick="editarPedido(${orden.id})" title="Editar" style="
                            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
                            color: white;
                            border: none;
                            padding: 0.5rem;
                            border-radius: 6px;
                            cursor: pointer;
                            font-size: 1rem;
                            transition: all 0.3s ease;
                        ">
                            <span class="material-symbols-rounded">edit</span>
                        </button>
                    </div>
                    <!-- Número -->
                    <div data-field="numero_pedido" style="font-weight: 600; color: #1e3a8a;">#${numeroPedido}</div>
                    <!-- Cliente -->
                    <div data-field="cliente" style="color: #2c3e50;">${orden.cliente || 'Sin especificar'}</div>
                    <!-- Novedades -->
                    <div data-field="novedades" style="color: #666; font-size: 0.9rem;">${orden.novedades || '-'}</div>
                    <!-- Asesora -->
                    <div data-field="asesor" style="color: #666;">${orden.asesor?.name || orden.asesor || 'Sin asignar'}</div>
                    <!-- Forma Pago -->
                    <div data-field="forma_pago" style="color: #666;">${orden.forma_pago || 'No especificada'}</div>
                </div>
            `;

            // Agregar la nueva fila al final de la tabla
            tableContainer.innerHTML += filaHTML;
            
            // Scroll suave a la nueva fila
            tableContainer.scrollLeft = tableContainer.scrollWidth;
        }

        /**
         * Muestra una notificación al usuario sobre la actualización
         */
        function mostrarNotificacionEnTiempoReal(orden, action) {
            const mensajes = {
                'created': `✨ Nueva orden creada: #${orden.numero_pedido}`,
                'updated': ` Orden actualizada: #${orden.numero_pedido}`,
                'deleted': ` Orden eliminada: #${orden.numero_pedido}`
            };

            const mensaje = mensajes[action] || `Orden #${orden.numero_pedido} modificada`;

            // Mostrar notificación flotante
            const notif = document.createElement('div');
            notif.innerHTML = mensaje;
            notif.style.cssText = `
                position: fixed;
                bottom: 20px;
                right: 20px;
                background: linear-gradient(135deg, #10b981 0%, #059669 100%);
                color: white;
                padding: 1rem 1.5rem;
                border-radius: 8px;
                font-weight: 600;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                animation: slideInRight 0.5s ease;
                z-index: 10000;
            `;
            
            document.body.appendChild(notif);

            // Remover después de 4 segundos
            setTimeout(() => {
                notif.style.animation = 'slideOutRight 0.5s ease';
                setTimeout(() => notif.remove(), 500);
            }, 4000);
        }

        // Agregar estilos de animación si no existen
        if (!document.querySelector('style[data-realtime]')) {
            const style = document.createElement('style');
            style.setAttribute('data-realtime', 'true');
            style.textContent = `
                @keyframes slideInDown {
                    from {
                        opacity: 0;
                        transform: translateY(-20px);
                    }
                    to {
                        opacity: 1;
                        transform: translateY(0);
                    }
                }
                @keyframes slideInRight {
                    from {
                        opacity: 0;
                        transform: translateX(100px);
                    }
                    to {
                        opacity: 1;
                        transform: translateX(0);
                    }
                }
                @keyframes slideOutRight {
                    from {
                        opacity: 1;
                        transform: translateX(0);
                    }
                    to {
                        opacity: 0;
                        transform: translateX(100px);
                    }
                }
            `;
            document.head.appendChild(style);
        }

        //  ESPERAR A QUE window.waitForEcho ESTÉ DISPONIBLE ANTES DE CONECTARSE
        console.log('[Realtime Supervisor] ⏳ Esperando a que window.waitForEcho esté disponible...');
        
        // Función para inicializar el listener
        function initializeRealtimeListener() {
            // Verificar que window.waitForEcho está disponible
            if (typeof window.waitForEcho !== 'function') {
                console.log('[Realtime Supervisor] ⏳ window.waitForEcho aún no disponible, reintentando en 100ms...');
                setTimeout(initializeRealtimeListener, 100);
                return;
            }
            
            console.log('[Realtime Supervisor]  window.waitForEcho está disponible, inicializando listener...');
            
            window.waitForEcho(() => {
                console.log('[Realtime Supervisor]  Echo está listo, inicializando suscripción...');
                
                try {
                    window.Echo.channel('supervisor-pedidos')
                        .listen('OrdenUpdated', (data) => {
                            console.log('[Realtime Supervisor] 📨 Evento OrdenUpdated recibido:', data);
                            
                            if (!data.orden) {
                                console.warn('[Realtime Supervisor]  Evento sin datos de orden');
                                return;
                            }

                            const orden = data.orden;
                            const numeroPedido = orden.numero_pedido || orden.numero_pedido || orden.pedido;
                            const action = data.action || 'updated';
                            
                            console.log(`[Realtime Supervisor]  Procesando ${action}: Pedido #${numeroPedido}`);

                            // Buscar la fila en la tabla correspondiente al pedido
                            const filas = document.querySelectorAll('[data-pedido-id]');
                            let filaEncontrada = null;

                            for (let fila of filas) {
                                const pedidoIdAttr = fila.getAttribute('data-pedido-id');
                                if (pedidoIdAttr == orden.id) {
                                    filaEncontrada = fila;
                                    break;
                                }
                            }

                            if (filaEncontrada) {
                                console.log('[Realtime Supervisor]  Fila encontrada, actualizando...');
                                actualizarFilaEnTabla(filaEncontrada, orden, action);
                            } else {
                                console.log('[Realtime Supervisor]  Fila no encontrada, agregando nueva fila...');
                                agregarNuevaFilaATabla(orden, action);
                            }

                            // Notificar al usuario
                            mostrarNotificacionEnTiempoReal(orden, action);
                            
                            // Actualizar contador de órdenes pendientes si es necesario
                            if (typeof actualizarContadorPendientes === 'function') {
                                actualizarContadorPendientes();
                            }
                        })
                        .on('pusher:subscription_succeeded', () => {
                            console.log('[Realtime Supervisor]  Subscripción exitosa al canal supervisor-pedidos');
                        })
                        .on('pusher:subscription_error', (error) => {
                            console.error('[Realtime Supervisor]  Error en subscripción:', error);
                        });
                    
                    console.log('[Realtime Supervisor]  Sistema de tiempo real inicializado correctamente');
                } catch (error) {
                    console.error('[Realtime Supervisor]  Error inicializando listener:', error);
                }
            });
        }

        // Esperar a que el documento esté completamente cargado antes de inicializar
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initializeRealtimeListener);
        } else {
            initializeRealtimeListener();
        }
    </script>
@endpush

@endsection

