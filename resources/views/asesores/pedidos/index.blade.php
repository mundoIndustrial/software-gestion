@extends('layouts.asesores')

@section('title', 'Mis Pedidos')
@section('page-title', 'Mis Pedidos')

@section('content')

<style>
    .top-nav {
        display: none !important;
    }

    /* ====================== FILTROS RÁPIDOS ====================== */
    .filtros-rapidos-asesores {
        background: white;
        border-radius: 12px;
        padding: 1rem 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        display: flex;
        gap: 0.75rem;
        flex-wrap: wrap;
        align-items: center;
    }

    .filtros-rapidos-asesores-label {
        font-weight: 600;
        font-size: 0.875rem;
        color: #2c3e50;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        white-space: nowrap;
        margin-right: 0.5rem;
    }

    .btn-filtro-rapido-asesores {
        padding: 0.625rem 1.25rem;
        border: 2px solid #e0e6ed;
        background: white;
        border-radius: 20px;
        font-weight: 600;
        font-size: 0.875rem;
        cursor: pointer;
        transition: all 0.3s ease;
        color: #2c3e50;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        white-space: nowrap;
    }

    .btn-filtro-rapido-asesores:hover {
        border-color: #3498db;
        color: #3498db;
        background: rgba(52, 152, 219, 0.05);
    }

    .btn-filtro-rapido-asesores.active {
        background: #3498db;
        color: white;
        border-color: #3498db;
    }

    .btn-filtro-rapido-asesores .material-symbols-rounded {
        font-size: 1.1rem;
    }

    /* ====================== BOTONES FILTRO EMBUDO ====================== */
    .th-wrapper {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem;
    }

    .btn-filter-column {
        background: none;
        border: none;
        color: white;
        cursor: pointer;
        padding: 0.25rem;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
        opacity: 0.7;
    }

    .btn-filter-column:hover {
        opacity: 1;
        transform: scale(1.15);
    }

    .btn-filter-column .material-symbols-rounded {
        font-size: 1.2rem;
    }

    /* ====================== INDICADOR DE FILTRO ACTIVO ====================== */
    .btn-filter-column {
        position: relative;
    }

    .filter-badge {
        position: absolute;
        top: -8px;
        right: -8px;
        background: #ef4444;
        color: white;
        border-radius: 50%;
        width: 20px;
        height: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.7rem;
        font-weight: 700;
        border: 2px solid white;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        opacity: 0;
        transform: scale(0);
        transition: all 0.3s ease;
    }

    .btn-filter-column.has-filter .filter-badge {
        opacity: 1;
        transform: scale(1);
    }

    /* ====================== BOTÓN FLOTANTE LIMPIAR FILTROS ====================== */
    .floating-clear-filters {
        position: fixed;
        bottom: 30px;
        right: 30px;
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        color: white;
        border: none;
        border-radius: 50%;
        width: 56px;
        height: 56px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        transition: all 0.3s ease;
        font-size: 1.5rem;
        z-index: 999;
        opacity: 0;
        visibility: hidden;
        transform: scale(0) translateY(20px);
    }

    .floating-clear-filters.visible {
        opacity: 1;
        visibility: visible;
        transform: scale(1) translateY(0);
    }

    .floating-clear-filters:hover {
        background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
        box-shadow: 0 6px 16px rgba(239, 68, 68, 0.4);
        transform: scale(1.1) translateY(0);
    }

    .floating-clear-filters:active {
        transform: scale(0.95) translateY(0);
    }

    .floating-clear-filters-tooltip {
        position: absolute;
        bottom: 70px;
        right: 0;
        background: #1f2937;
        color: white;
        padding: 0.5rem 0.75rem;
        border-radius: 6px;
        font-size: 0.75rem;
        white-space: nowrap;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.3s ease;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    }

    .floating-clear-filters:hover .floating-clear-filters-tooltip {
        opacity: 1;
    }

    /* ====================== MODAL FILTROS ====================== */
    .filter-modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 9999;
    }

    .filter-modal-overlay.active {
        display: flex;
    }

    .filter-modal {
        background: white;
        border-radius: 12px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        max-height: 90vh;
        overflow-y: auto;
        animation: slideUp 0.3s ease;
        width: 90%;
        max-width: 450px;
    }

    @keyframes slideUp {
        from {
            transform: translateY(50px);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }

    /* Animaciones para notificaciones y modales */
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
    
    @keyframes slideOutRight {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(400px);
            opacity: 0;
        }
    }

    .filter-modal-header {
        padding: 1.5rem;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        justify-content: space-between;
        align-items: center;
        position: sticky;
        top: 0;
        background: white;
        z-index: 10;
    }

    .filter-modal-header h3 {
        margin: 0;
        font-size: 1.125rem;
        color: #1e40af;
        font-weight: 700;
    }

    .filter-modal-close {
        background: none;
        border: none;
        font-size: 1.5rem;
        cursor: pointer;
        color: #6b7280;
        transition: color 0.3s ease;
    }

    .filter-modal-close:hover {
        color: #1e40af;
    }

    .filter-modal-body {
        padding: 1.5rem;
    }

    .filter-search {
        width: 100%;
        padding: 0.75rem;
        border: 2px solid #e5e7eb;
        border-radius: 8px;
        font-size: 0.95rem;
        margin-bottom: 1rem;
        transition: border-color 0.3s ease;
    }

    .filter-search:focus {
        outline: none;
        border-color: #1e40af;
        box-shadow: 0 0 0 3px rgba(30, 64, 175, 0.1);
    }

    .filter-options {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        max-height: 400px;
        overflow-y: auto;
    }

    .filter-option {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.75rem;
        border-radius: 6px;
        transition: background 0.2s ease;
    }

    .filter-option:hover {
        background: #f3f4f6;
    }

    .filter-option input[type="checkbox"] {
        width: 18px;
        height: 18px;
        cursor: pointer;
        accent-color: #1e40af;
    }

    .filter-option label {
        flex: 1;
        cursor: pointer;
        font-size: 0.95rem;
        color: #374151;
    }

    .filter-modal-footer {
        padding: 1rem 1.5rem;
        border-top: 1px solid #e5e7eb;
        display: flex;
        gap: 0.75rem;
        justify-content: flex-end;
        position: sticky;
        bottom: 0;
        background: white;
    }

    .btn-filter-apply,
    .btn-filter-reset {
        padding: 0.75rem 1.5rem;
        border: none;
        border-radius: 6px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        font-size: 0.875rem;
    }

    .btn-filter-apply {
        background: #1e40af;
        color: white;
    }

    .btn-filter-apply:hover {
        background: #1e3a8a;
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(30, 64, 175, 0.2);
    }

    .btn-filter-reset {
        background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
        color: white;
        border: none;
        box-shadow: 0 2px 4px rgba(249, 115, 22, 0.2);
    }

    .btn-filter-reset:hover {
        background: linear-gradient(135deg, #ea580c 0%, #c2410c 100%);
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(249, 115, 22, 0.3);
    }
</style>

<div style="padding: 0 0.5rem 0 0; max-width: 100%; margin: 0 auto;">
    <!-- HEADER PROFESIONAL -->
    <div style="background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%); border-radius: 12px; padding: 20px 30px; margin-bottom: 30px; box-shadow: 0 4px 15px rgba(30, 58, 138, 0.2);">
        <div style="display: flex; justify-content: space-between; align-items: center; gap: 30px;">
            <!-- TÍTULO CON ICONO -->
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="background: rgba(255,255,255,0.15); padding: 10px 12px; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-list" style="color: white; font-size: 24px;"></i>
                </div>
                <div>
                    <h1 style="margin: 0; font-size: 1.5rem; color: white; font-weight: 700;">Lista de Pedidos</h1>
                    <p style="margin: 0; color: rgba(255,255,255,0.7); font-size: 0.85rem;">Gestiona tus pedidos de producción</p>
                </div>
            </div>

            <!-- BOTÓN REGISTRAR -->
            <a href="{{ route('asesores.pedidos-produccion.crear') }}" style="background: white; color: #1e40af; padding: 10px 18px; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 0.9rem; display: inline-flex; align-items: center; gap: 8px; transition: all 0.3s; box-shadow: 0 2px 8px rgba(0,0,0,0.1); white-space: nowrap;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.15)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.1)'">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                Registrar
            </a>
        </div>
    </div>

    <!-- Filtros Rápidos -->
    <div class="filtros-rapidos-asesores">
        <span class="filtros-rapidos-asesores-label">Filtrar por estado:</span>
        <a href="{{ route('asesores.pedidos.index') }}" class="btn-filtro-rapido-asesores {{ !request('estado') ? 'active' : '' }}" onclick="return navegarFiltro(this.href, event)">
            <span class="material-symbols-rounded">home</span>
            Todos
        </a>
        <a href="{{ route('asesores.pedidos.index', ['estado' => 'Pendiente']) }}" class="btn-filtro-rapido-asesores {{ request('estado') === 'Pendiente' ? 'active' : '' }}" onclick="return navegarFiltro(this.href, event)">
            <span class="material-symbols-rounded">schedule</span>
            Pendientes
        </a>
        <a href="javascript:void(0)" onclick="filtrarEnProduccion()" class="btn-filtro-rapido-asesores {{ (request('estado') === 'No iniciado' || request('estado') === 'En Ejecución') ? 'active' : '' }}" id="btnEnProduccion">
            <span class="material-symbols-rounded">build</span>
            En Producción
        </a>
        <a href="{{ route('asesores.pedidos.index', ['estado' => 'Entregado']) }}" class="btn-filtro-rapido-asesores {{ request('estado') === 'Entregado' ? 'active' : '' }}" onclick="return navegarFiltro(this.href, event)">
            <span class="material-symbols-rounded">check_circle</span>
            Entregados
        </a>
        <a href="{{ route('asesores.pedidos.index', ['estado' => 'Anulada']) }}" class="btn-filtro-rapido-asesores {{ request('estado') === 'Anulada' ? 'active' : '' }}" onclick="return navegarFiltro(this.href, event)">
            <span class="material-symbols-rounded">cancel</span>
            Anulados
        </a>
    </div>
        </a>
    </div>

    <!-- Tabla con Scroll Horizontal -->
    <div style="background: #e5e7eb; border-radius: 8px; overflow: visible; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08); padding: 0.75rem;">
        <!-- Contenedor con Scroll -->
        <div class="table-scroll-container" style="overflow-x: auto; overflow-y: visible;">
            <!-- Header Azul -->
            <div style="
                background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%);
                color: white;
                padding: 1rem 1.5rem;
                display: grid;
                grid-template-columns: 130px 110px 110px 130px 90px 160px 150px 110px 110px 110px;
                gap: 1.2rem;
                font-weight: 600;
                font-size: 0.875rem;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                min-width: min-content;
                border-radius: 6px;
            ">
                <div class="th-wrapper">
                    <span>Acciones</span>
                    <button type="button" class="btn-filter-column" title="Filtrar Acciones">
                        <span class="material-symbols-rounded">filter_alt</span>
                    </button>
                </div>
                <div class="th-wrapper">
                    <span>Estado</span>
                    <button type="button" class="btn-filter-column" title="Filtrar Estado">
                        <span class="material-symbols-rounded">filter_alt</span>
                    </button>
                </div>
                <div class="th-wrapper">
                    <span>Área</span>
                    <button type="button" class="btn-filter-column" title="Filtrar Área">
                        <span class="material-symbols-rounded">filter_alt</span>
                    </button>
                </div>
                <div class="th-wrapper">
                    <span>Pedido</span>
                    <button type="button" class="btn-filter-column" title="Filtrar Pedido">
                        <span class="material-symbols-rounded">filter_alt</span>
                    </button>
                </div>
                <div class="th-wrapper">
                    <span>Cliente</span>
                    <button type="button" class="btn-filter-column" title="Filtrar Cliente">
                        <span class="material-symbols-rounded">filter_alt</span>
                    </button>
                </div>
                <div class="th-wrapper">
                    <span>Descripción</span>
                    <button type="button" class="btn-filter-column" title="Filtrar Descripción">
                        <span class="material-symbols-rounded">filter_alt</span>
                    </button>
                </div>
                <div class="th-wrapper">
                    <span>Cantidad</span>
                    <button type="button" class="btn-filter-column" title="Filtrar Cantidad">
                        <span class="material-symbols-rounded">filter_alt</span>
                    </button>
                </div>
                <div class="th-wrapper">
                    <span>Forma Pago</span>
                    <button type="button" class="btn-filter-column" title="Filtrar Forma Pago">
                        <span class="material-symbols-rounded">filter_alt</span>
                    </button>
                </div>
                <div class="th-wrapper">
                    <span>Fecha Creación</span>
                    <button type="button" class="btn-filter-column" title="Filtrar Fecha Creación">
                        <span class="material-symbols-rounded">filter_alt</span>
                    </button>
                </div>
                <div class="th-wrapper">
                    <span>Fecha Estimada</span>
                    <button type="button" class="btn-filter-column" title="Filtrar Fecha">
                        <span class="material-symbols-rounded">filter_alt</span>
                    </button>
                </div>
            </div>

            <!-- Filas -->
            @if($pedidos->isEmpty())
                <div style="padding: 3rem 2rem; text-align: center; color: #6b7280;">
                    <i class="fas fa-inbox" style="font-size: 3rem; color: #d1d5db; margin-bottom: 1rem; display: block;"></i>
                    <p style="font-size: 1rem; margin: 0;">No hay pedidos registrados</p>
                </div>
            @else
                @foreach($pedidos as $pedido)
                    <div style="
                        display: grid;
                        grid-template-columns: 130px 110px 110px 130px 90px 160px 150px 110px 110px 110px;
                        gap: 1.2rem;
                        padding: 1rem 1.5rem;
                        align-items: center;
                        transition: all 0.3s ease;
                        min-width: min-content;
                        background: white;
                        border-radius: 6px;
                        margin-bottom: 0.75rem;
                        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
                        position: relative;
                        z-index: 1;
                    " onmouseover="this.style.boxShadow='0 4px 12px rgba(0, 0, 0, 0.1)'; this.style.transform='translateY(-2px)'" onmouseout="this.style.boxShadow='0 2px 4px rgba(0, 0, 0, 0.05)'; this.style.transform='translateY(0)'">
                    
                    <!-- Acciones -->
                    <div style="display: flex; gap: 0.5rem; align-items: center; justify-content: center;">
                        <!-- Botón Ver (con dropdown) -->
                        <button class="btn-ver-dropdown" data-menu-id="menu-ver-{{ $pedido->numero_pedido }}" data-pedido="{{ $pedido->numero_pedido }}" title="Ver Opciones" style="
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
                        
                        <!-- Botón Anular (solo si no está anulado) -->
                        @if($pedido->estado !== 'Anulada')
                        <button onclick="confirmarAnularPedido({{ $pedido->numero_pedido }})" title="Anular Pedido" style="
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

                    <!-- Estado -->
                    <div>
                        <span style="
                            background: #fef3c7;
                            color: #92400e;
                            padding: 0.25rem 0.75rem;
                            border-radius: 12px;
                            font-size: 0.75rem;
                            font-weight: 600;
                            display: inline-block;
                        ">
                            {{ $pedido->estado ?? 'Pendiente' }}
                        </span>
                    </div>

                    <!-- Área -->
                    <div>
                        <span style="
                            background: #dbeafe;
                            color: #1e40af;
                            padding: 0.25rem 0.75rem;
                            border-radius: 12px;
                            font-size: 0.75rem;
                            font-weight: 600;
                            display: inline-block;
                        ">
                            {{ $pedido->procesoActualOptimizado() ?? '-' }}
                        </span>
                    </div>

                    <!-- Pedido -->
                    <div style="color: #2563eb; font-weight: 700; font-size: 0.875rem;">
                        #{{ $pedido->numero_pedido }}
                    </div>

                    <!-- Cliente -->
                    <div style="color: #374151; font-size: 0.875rem; font-weight: 500; cursor: pointer;" onclick="abrirModalCelda('Cliente', '{{ $pedido->cliente }}')" title="Click para ver completo">
                        {{ $pedido->cliente }}
                    </div>

                    <!-- Descripción -->
                    <div style="color: #6b7280; font-size: 0.875rem; cursor: pointer; max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" onclick="abrirModalCelda('Descripción', `{{ addslashes($pedido->descripcion_prendas ?? '-') }}`)" title="Click para ver completo">
                        @php
                            if ($pedido->prendas && $pedido->prendas->count() > 0) {
                                // Mostrar nombres de prendas con formato
                                $prendasInfo = $pedido->prendas->map(function($prenda) {
                                    return $prenda->nombre_prenda ?? 'Prenda sin nombre';
                                })->unique()->toArray();
                                $descripcion = !empty($prendasInfo) ? implode(', ', $prendasInfo) : '-';
                                echo $descripcion . ' <span style="color: #3b82f6; font-weight: 600;">...</span>';
                            } else {
                                echo '-';
                            }
                        @endphp
                    </div>

                    <!-- Cantidad -->
                    <div style="color: #374151; font-weight: 600; font-size: 0.875rem; text-align: center;">
                        @if($pedido->prendas->first())
                            <span style="white-space: nowrap;">{{ $pedido->prendas->first()->cantidad }} <small style="color: #9ca3af;">und</small></span>
                        @else
                            <span style="color: #d1d5db;">-</span>
                        @endif
                    </div>

                    <!-- Forma Pago -->
                    <div style="color: #374151; font-size: 0.875rem; cursor: pointer;" onclick="abrirModalCelda('Forma de Pago', '{{ $pedido->forma_de_pago ?? '-' }}')" title="Click para ver completo">
                        {{ $pedido->forma_de_pago ?? '-' }}
                    </div>

                    <!-- Fecha Creación -->
                    <div style="color: #6b7280; font-size: 0.75rem;">
                        {{ $pedido->fecha_de_creacion_de_orden ? $pedido->fecha_de_creacion_de_orden->format('d/m/Y') : '-' }}
                    </div>

                    <!-- Fecha Estimada de Entrega -->
                    <div style="color: #6b7280; font-size: 0.75rem;">
                        @php
                            if ($pedido->fecha_estimada_de_entrega) {
                                try {
                                    $fecha = \Carbon\Carbon::parse($pedido->fecha_estimada_de_entrega);
                                    echo $fecha->format('d/m/Y');
                                } catch (\Exception $e) {
                                    echo $pedido->fecha_estimada_de_entrega;
                                }
                            } else {
                                echo '-';
                            }
                        @endphp
                    </div>
                </div>
                @endforeach
            @endif
        </div>
    </div>

    <!-- Paginación -->
    @if($pedidos->hasPages())
        <div style="margin-top: 1.5rem; display: flex; justify-content: center;">
            {{ $pedidos->links() }}
        </div>
    @endif
</div>

<!-- Botón Flotante para Limpiar Filtros -->
<button id="clearFiltersBtn" class="floating-clear-filters" onclick="resetFilters(); updateClearButtonVisibility();" title="Limpiar todos los filtros">
    <span class="material-symbols-rounded">filter_alt_off</span>
    <div class="floating-clear-filters-tooltip">Limpiar filtros</div>
</button>

<!-- Modal de Filtros -->
<div id="filterModal" class="filter-modal-overlay" onclick="closeFilterModal(event)">
    <div class="filter-modal" onclick="event.stopPropagation()">
        <div class="filter-modal-header">
            <h3 id="filterModalTitle">Filtrar por Estado</h3>
            <button class="filter-modal-close" onclick="closeFilterModal()">&times;</button>
        </div>
        <div class="filter-modal-body">
            <input type="text" class="filter-search" id="filterSearch" placeholder="Buscar...">
            <div class="filter-options" id="filterOptions"></div>
        </div>
        <div class="filter-modal-footer">
            <button class="btn-filter-reset" onclick="resetFilters()">Limpiar</button>
            <button class="btn-filter-apply" onclick="applyFilters()">Aplicar</button>
        </div>
    </div>
</div>

<!-- Modal de Descripción de Prendas (reutilizado de órdenes) -->
<x-orders-components.order-description-modal />

<!-- Modal de Imagen -->
@include('components.modal-imagen')

<!-- Modal de Detalle de Orden -->
<div id="modal-overlay" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.5); backdrop-filter: blur(4px); z-index: 9997; display: none; pointer-events: auto;" onclick="closeModalOverlay()"></div>

<div id="order-detail-modal-wrapper" style="width: 90%; max-width: 672px; position: fixed; top: 60%; left: 50%; transform: translate(-50%, -50%); z-index: 9998; pointer-events: auto; display: none;">
    <x-orders-components.order-detail-modal />
</div>

<!-- Modal de Seguimiento del Pedido (Tracking Simplificado para Asesoras) -->
<x-orders-components.asesoras-tracking-modal />

<!-- Contenedor para Dropdowns (Fuera de la tabla) -->
<div id="dropdowns-container" style="position: fixed; top: 0; left: 0; z-index: 999999; pointer-events: none;"></div>

@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/asesores/pedidos.css') }}">
@endpush

@push('scripts')
<script>
    // Configurar variables globales para los modales
    window.fetchUrl = '/registros';
    window.modalContext = 'pedidos';

    // Función para mostrar modal de motivo de anulación
    function verMotivoanulacion(numeroPedido, motivo, usuario, fecha) {
        // Crear modal dinámicamente
        const modalHTML = `
            <div id="motivoAnulacionModal" style="
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.5);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 100000;
                backdrop-filter: blur(4px);
                animation: fadeIn 0.2s ease;
            " onclick="if(event.target.id === 'motivoAnulacionModal') cerrarModalMotivo()">
                <div style="
                    background: white;
                    border-radius: 12px;
                    box-shadow: 0 12px 32px rgba(0, 0, 0, 0.15);
                    max-width: 500px;
                    width: 90%;
                    overflow: hidden;
                    animation: slideIn 0.3s ease;
                ">
                    <!-- Header -->
                    <div style="
                        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
                        color: white;
                        padding: 1.5rem;
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                    ">
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <i class="fas fa-ban" style="font-size: 1.25rem;"></i>
                            <h3 style="margin: 0; font-size: 1.1rem; font-weight: 600;">Motivo de Anulación</h3>
                        </div>
                        <button onclick="cerrarModalMotivo()" style="
                            background: rgba(255, 255, 255, 0.2);
                            border: none;
                            color: white;
                            cursor: pointer;
                            font-size: 1.25rem;
                            width: 32px;
                            height: 32px;
                            border-radius: 50%;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            transition: background 0.2s ease;
                        " onmouseover="this.style.background='rgba(255, 255, 255, 0.3)'" onmouseout="this.style.background='rgba(255, 255, 255, 0.2)'">
                            ✕
                        </button>
                    </div>

                    <!-- Content -->
                    <div style="padding: 1.5rem;">
                        <!-- Número de Pedido -->
                        <div style="margin-bottom: 1.25rem;">
                            <label style="
                                display: block;
                                font-size: 0.75rem;
                                font-weight: 700;
                                color: #6b7280;
                                text-transform: uppercase;
                                letter-spacing: 0.05em;
                                margin-bottom: 0.375rem;
                            ">Número de Pedido</label>
                            <div style="
                                font-size: 1rem;
                                font-weight: 600;
                                color: #1f2937;
                                background: #f3f4f6;
                                padding: 0.75rem;
                                border-radius: 6px;
                            ">#${numeroPedido}</div>
                        </div>

                        <!-- Motivo -->
                        <div style="margin-bottom: 1.25rem;">
                            <label style="
                                display: block;
                                font-size: 0.75rem;
                                font-weight: 700;
                                color: #6b7280;
                                text-transform: uppercase;
                                letter-spacing: 0.05em;
                                margin-bottom: 0.375rem;
                            ">Motivo</label>
                            <div style="
                                font-size: 0.95rem;
                                color: #374151;
                                background: #fef2f2;
                                padding: 0.875rem;
                                border-radius: 6px;
                                border-left: 3px solid #ef4444;
                                line-height: 1.5;
                            ">${motivo || 'No especificado'}</div>
                        </div>

                        <!-- Usuario -->
                        <div style="margin-bottom: 1.25rem;">
                            <label style="
                                display: block;
                                font-size: 0.75rem;
                                font-weight: 700;
                                color: #6b7280;
                                text-transform: uppercase;
                                letter-spacing: 0.05em;
                                margin-bottom: 0.375rem;
                            ">Anulado por</label>
                            <div style="
                                font-size: 0.95rem;
                                color: #374151;
                                background: #f3f4f6;
                                padding: 0.75rem;
                                border-radius: 6px;
                                display: flex;
                                align-items: center;
                                gap: 0.5rem;
                            ">
                                <i class="fas fa-user" style="color: #6b7280;"></i>
                                ${usuario || 'Sistema'}
                            </div>
                        </div>

                        <!-- Fecha -->
                        <div style="margin-bottom: 1.5rem;">
                            <label style="
                                display: block;
                                font-size: 0.75rem;
                                font-weight: 700;
                                color: #6b7280;
                                text-transform: uppercase;
                                letter-spacing: 0.05em;
                                margin-bottom: 0.375rem;
                            ">Fecha y Hora</label>
                            <div style="
                                font-size: 0.95rem;
                                color: #374151;
                                background: #f3f4f6;
                                padding: 0.75rem;
                                border-radius: 6px;
                                display: flex;
                                align-items: center;
                                gap: 0.5rem;
                            ">
                                <i class="fas fa-calendar" style="color: #6b7280;"></i>
                                ${fecha || 'No disponible'}
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div style="
                        background: #f9fafb;
                        padding: 1rem 1.5rem;
                        border-top: 1px solid #e5e7eb;
                        display: flex;
                        justify-content: flex-end;
                        gap: 0.75rem;
                    ">
                        <button onclick="cerrarModalMotivo()" style="
                            background: white;
                            border: 1px solid #d1d5db;
                            color: #374151;
                            padding: 0.625rem 1.25rem;
                            border-radius: 6px;
                            cursor: pointer;
                            font-weight: 500;
                            font-size: 0.875rem;
                            transition: all 0.2s ease;
                        " onmouseover="this.style.background='#f3f4f6'; this.style.borderColor='#9ca3af'" onmouseout="this.style.background='white'; this.style.borderColor='#d1d5db'">
                            Cerrar
                        </button>
                    </div>
                </div>

                <style>
                    @keyframes fadeIn {
                        from { opacity: 0; }
                        to { opacity: 1; }
                    }
                    @keyframes slideIn {
                        from { 
                            transform: scale(0.95) translateY(-20px);
                            opacity: 0;
                        }
                        to { 
                            transform: scale(1) translateY(0);
                            opacity: 1;
                        }
                    }
                </style>
            </div>
        `;

        // Insertar modal en el DOM
        document.body.insertAdjacentHTML('beforeend', modalHTML);
        
        // Enfocar en el modal para mejorar accesibilidad
        document.getElementById('motivoAnulacionModal').focus();
    }

    // Función para cerrar el modal de motivo de anulación
    function cerrarModalMotivo() {
        const modal = document.getElementById('motivoAnulacionModal');
        if (modal) {
            modal.style.animation = 'fadeIn 0.2s ease reverse';
            setTimeout(() => modal.remove(), 200);
        }
    }

    // Cerrar modal con tecla ESC
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            const modal = document.getElementById('motivoAnulacionModal');
            if (modal) {
                cerrarModalMotivo();
            }
        }
    });

    // ==================== MODAL DE CELDA ====================
    function abrirModalCelda(titulo, contenido) {
        // Limpiar y formatear el contenido
        let contenidoLimpio = contenido || '-';
        
        // Remover asteriscos de formato
        contenidoLimpio = contenidoLimpio.replace(/\*\*\*/g, '');
        
        // Remover etiquetas de formato como "*** DESCRIPCIÓN: ***"
        contenidoLimpio = contenidoLimpio.replace(/\*\*\*\s*[A-Z\s]+:\s*\*\*\*/g, '');
        
        // Dividir por prendas (separadas por \n\n)
        let prendas = contenidoLimpio.split('\n\n').filter(p => p.trim());
        
        let htmlContenido = '';
        
        prendas.forEach((prenda, index) => {
            let lineas = prenda.split('\n').map(l => l.trim()).filter(l => l);
            
            htmlContenido += '<div style="margin-bottom: 1.5rem; padding: 1rem; background: #f9fafb; border-radius: 8px; border-left: 4px solid #3b82f6;">';
            
            lineas.forEach((linea, i) => {
                // Nombre de prenda
                if (linea.match(/^(\d+)\.\s+Prenda:/i) || linea.match(/^Prenda \d+:/i)) {
                    htmlContenido += `<div style="font-weight: 700; font-size: 1rem; margin-bottom: 0.5rem; color: #1f2937;">${linea}</div>`;
                }
                // Atributos (Color, Tela, Manga)
                else if (linea.match(/^Color:|^Tela:|^Manga:/i)) {
                    htmlContenido += `<div style="margin-bottom: 0.5rem; color: #374151;">${linea}</div>`;
                }
                // Descripción
                else if (linea.match(/^DESCRIPCIÓN:/i)) {
                    htmlContenido += `<div style="margin-bottom: 0.5rem; color: #374151;"><strong>${linea}</strong></div>`;
                }
                // Detalles (Reflectivo, Bolsillos, etc)
                else if (linea.match(/^(Reflectivo|Bolsillos|Broche|Ojal):/i)) {
                    htmlContenido += `<div style="margin-bottom: 0.5rem; color: #374151;"><strong>${linea}</strong></div>`;
                }
                // Viñetas
                else if (linea.startsWith('•') || linea.startsWith('-')) {
                    htmlContenido += `<div style="margin-left: 1.5rem; margin-bottom: 0.25rem; color: #374151;">• ${linea.substring(1).trim()}</div>`;
                }
                // Tallas
                else if (linea.match(/^Tallas:/i)) {
                    htmlContenido += `<div style="margin-bottom: 0.5rem; color: #374151;"><strong>${linea}</strong></div>`;
                }
                // Otras líneas
                else if (linea) {
                    htmlContenido += `<div style="margin-bottom: 0.25rem; color: #374151;">${linea}</div>`;
                }
            });
            
            htmlContenido += '</div>';
        });
        
        // Crear modal
        const modalHTML = `
            <div id="celdaModal" style="
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.5);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 9999;
                animation: fadeIn 0.3s ease;
            " onclick="if(event.target.id === 'celdaModal') cerrarModalCelda()">
                <div style="
                    background: white;
                    border-radius: 12px;
                    padding: 2rem;
                    max-width: 600px;
                    width: 90%;
                    max-height: 80vh;
                    overflow-y: auto;
                    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                    animation: slideIn 0.3s ease;
                ">
                    <!-- Header -->
                    <div style="
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        margin-bottom: 1.5rem;
                        border-bottom: 2px solid #e5e7eb;
                        padding-bottom: 1rem;
                    ">
                        <h2 style="
                            margin: 0;
                            font-size: 1.25rem;
                            font-weight: 700;
                            color: #1f2937;
                        ">${titulo}</h2>
                        <button onclick="cerrarModalCelda()" style="
                            background: none;
                            border: none;
                            font-size: 1.5rem;
                            cursor: pointer;
                            color: #6b7280;
                            padding: 0;
                            width: 32px;
                            height: 32px;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            border-radius: 6px;
                            transition: all 0.2s ease;
                        " onmouseover="this.style.background='#f3f4f6'; this.style.color='#1f2937'" onmouseout="this.style.background='none'; this.style.color='#6b7280'">
                            ✕
                        </button>
                    </div>

                    <!-- Contenido -->
                    <div style="
                        color: #374151;
                        font-size: 0.95rem;
                        line-height: 1.8;
                    ">
                        ${htmlContenido}
                    </div>

                    <!-- Footer -->
                    <div style="
                        margin-top: 1.5rem;
                        display: flex;
                        justify-content: flex-end;
                        gap: 0.75rem;
                    ">
                        <button onclick="cerrarModalCelda()" style="
                            background: white;
                            border: 2px solid #d1d5db;
                            color: #374151;
                            padding: 0.625rem 1.25rem;
                            border-radius: 6px;
                            cursor: pointer;
                            font-weight: 600;
                            font-size: 0.875rem;
                            transition: all 0.2s ease;
                        " onmouseover="this.style.background='#f3f4f6'; this.style.borderColor='#9ca3af'" onmouseout="this.style.background='white'; this.style.borderColor='#d1d5db'">
                            Cerrar
                        </button>
                    </div>
                </div>
            </div>
        `;

        // Insertar modal en el DOM
        document.body.insertAdjacentHTML('beforeend', modalHTML);
        
        // Cerrar con ESC
        document.addEventListener('keydown', function cerrarConEsc(event) {
            if (event.key === 'Escape') {
                cerrarModalCelda();
                document.removeEventListener('keydown', cerrarConEsc);
            }
        });
    }

    function cerrarModalCelda() {
        const modal = document.getElementById('celdaModal');
        if (modal) {
            modal.style.animation = 'fadeIn 0.3s ease reverse';
            setTimeout(() => modal.remove(), 300);
        }
    }

    /**
     * Navegación de filtros - Spinner ya está desactivado en esta página
     */
    function navegarFiltro(url, event) {
        event.preventDefault();
        window.location.href = url;
        return false;
    }

    // DESACTIVAR SPINNER en esta página - solo navegación de filtros, sin AJAX
    // El spinner se mantiene desactivado durante toda la sesión en esta página
    window.addEventListener('DOMContentLoaded', function() {
        if (window.setSpinnerConfig) {
            window.setSpinnerConfig({ enabled: false });
        }
        console.log('✅ Spinner desactivado en página de pedidos');
    });

    // Asegurar que el spinner esté oculto al cargar
    window.addEventListener('load', function() {
        if (window.hideLoadingSpinner) {
            window.hideLoadingSpinner();
        }
        const spinner = document.getElementById('loadingSpinner');
        if (spinner) {
            spinner.classList.add('hidden');
            spinner.style.display = 'none';
            spinner.style.visibility = 'hidden';
        }
        console.log('✅ Spinner oculto al cargar la página');
    });



    /**
     * Filtrar pedidos "En Producción" (No iniciado O En Ejecución)
     * Redirige al controlador con estado=En Producción para filtrado backend
     */
    function filtrarEnProduccion() {
        const url = new URL(window.location);
        url.searchParams.set('estado', 'En Producción');
        
        // Usar la función de navegación de filtros
        navegarFiltro(url.toString(), { preventDefault: () => {} });
    }
</script>
<script src="{{ asset('js/asesores/pedidos-list.js') }}"></script>
<script src="{{ asset('js/asesores/pedidos.js') }}"></script>
<script src="{{ asset('js/asesores/pedidos-modal.js') }}"></script>
<script src="{{ asset('js/asesores/pedidos-dropdown-simple.js') }}"></script>
<script src="{{ asset('js/asesores/pedidos-anular.js') }}"></script>
<!-- Modal Manager para renderizar detalles del pedido (igual que órdenes) -->
<script src="{{ asset('js/orders js/order-detail-modal-manager.js') }}"></script>
<script src="{{ asset('js/asesores/pedidos-detail-modal.js') }}"></script>
<script src="{{ asset('js/asesores/pedidos-table-filters.js') }}"></script>
<!-- Image Gallery para mostrar fotos en el modal -->
<script src="{{ asset('js/orders-scripts/image-gallery-zoom.js') }}"></script>
<!-- MODULAR ORDER TRACKING (SOLID Architecture) -->
<script src="{{ asset('js/order-tracking/modules/dateUtils.js') }}"></script>
<script src="{{ asset('js/order-tracking/modules/holidayManager.js') }}"></script>
<script src="{{ asset('js/order-tracking/modules/areaMapper.js') }}"></script>
<script src="{{ asset('js/order-tracking/modules/trackingService.js') }}"></script>
<script src="{{ asset('js/order-tracking/modules/trackingUI.js') }}"></script>
<script src="{{ asset('js/order-tracking/modules/apiClient.js') }}"></script>
<script src="{{ asset('js/order-tracking/modules/processManager.js') }}"></script>
<script src="{{ asset('js/order-tracking/modules/tableManager.js') }}"></script>
<script src="{{ asset('js/order-tracking/modules/dropdownManager.js') }}"></script>
<script src="{{ asset('js/order-tracking/orderTracking-v2.js') }}"></script>
@endpush
