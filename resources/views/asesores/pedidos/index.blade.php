@extends('layouts.asesores')

@section('title', 'Mis Pedidos')
@section('page-title', 'Mis Pedidos')

@section('content')

<style>
    .top-nav {
        display: none !important;
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

    /* ====================== FILTROS RÁPIDOS ASESORES ====================== */
    .filtros-rapidos-asesores {
        display: flex;
        align-items: center;
        justify-content: flex-start;
        gap: 15px;
        margin: 20px 0 25px 0;
        flex-wrap: wrap;
        background: #f9fafb;
        padding: 15px 20px;
        border-radius: 8px;
        border: 1px solid #e5e7eb;
    }

    .filtros-rapidos-asesores-label {
        margin: 0;
        color: #4b5563;
        font-weight: 600;
        font-size: 0.9rem;
        white-space: nowrap;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .btn-filtro-rapido-asesores {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 0.625rem 1.25rem;
        background: #ffffff;
        border: 2px solid #e5e7eb;
        border-radius: 6px;
        color: #6b7280;
        font-weight: 500;
        font-size: 0.875rem;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        white-space: nowrap;
    }

    .btn-filtro-rapido-asesores:hover {
        background: #f3f4f6;
        border-color: #d1d5db;
        color: #374151;
        transform: translateY(-1px);
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
    }

    .btn-filtro-rapido-asesores.active {
        background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
        border-color: #1e40af;
        color: #ffffff;
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
    }

    .btn-filtro-rapido-asesores.active:hover {
        background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%);
        box-shadow: 0 6px 16px rgba(37, 99, 235, 0.4);
        transform: translateY(-2px);
    }

    .btn-filtro-rapido-asesores .material-symbols-rounded {
        font-size: 1.2rem;
        font-weight: 400;
    }

    /* ====================== ESTILOS SCROLLBAR ====================== */
    .table-scroll-container::-webkit-scrollbar {
        width: 10px;
        height: 10px;
    }

    .table-scroll-container::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 6px;
    }

    .table-scroll-container::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 6px;
        border: 2px solid #f1f5f9;
    }

    .table-scroll-container::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
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

            <!-- BUSCADOR -->
            <div style="flex: 1; max-width: 500px; position: relative;">
                <input 
                    type="text" 
                    id="mainSearchInput" 
                    placeholder="Buscar por número de pedido o cliente..." 
                    style="width: 100%; padding: 10px 40px 10px 40px; border: 2px solid rgba(255,255,255,0.3); border-radius: 8px; background: rgba(255,255,255,0.95); font-size: 0.9rem; transition: all 0.3s; color: #1e40af; font-weight: 500;"
                    onfocus="this.style.background='white'; this.style.borderColor='white'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.15)'"
                    onblur="this.style.background='rgba(255,255,255,0.95)'; this.style.borderColor='rgba(255,255,255,0.3)'; this.style.boxShadow='none'"
                >
                <span class="material-symbols-rounded" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #1e40af; pointer-events: none;">search</span>
                <button type="button" id="clearMainSearch" style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #6b7280; cursor: pointer; padding: 4px; display: none; border-radius: 4px; transition: all 0.2s;" onmouseover="this.style.background='#f3f4f6'; this.style.color='#1e40af'" onmouseout="this.style.background='none'; this.style.color='#6b7280'">
                    <span class="material-symbols-rounded" style="font-size: 20px;">close</span>
                </button>
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
        <span class="filtros-rapidos-asesores-label">Filtrar por tipo:</span>
        <a href="{{ route('asesores.pedidos.index') }}" class="btn-filtro-rapido-asesores {{ !request('tipo') ? 'active' : '' }}" onclick="return navegarFiltro(this.href, event)">
            <span class="material-symbols-rounded">shopping_cart</span>
            Todos
        </a>
        <a href="{{ route('asesores.pedidos.index', ['tipo' => 'logo']) }}" class="btn-filtro-rapido-asesores {{ request('tipo') === 'logo' ? 'active' : '' }}" onclick="return navegarFiltro(this.href, event)">
            <span class="material-symbols-rounded">palette</span>
            Logo
        </a>
    </div>

    <!-- Botón Flotante para Limpiar Todos los Filtros -->
    <button id="btnClearAllFilters" class="floating-clear-filters" onclick="clearAllFilters()">
        <span class="material-symbols-rounded" style="font-size: 24px;">filter_alt_off</span>
        <div class="floating-clear-filters-tooltip">Limpiar todos los filtros</div>
    </button>

    <!-- Tabla con Scroll Horizontal -->
    <div style="background: #e5e7eb; border-radius: 8px; overflow: visible; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08); padding: 0.75rem; width: 100%; max-width: 100%;">
        <!-- Contenedor con Scroll -->
        <div class="table-scroll-container" style="overflow-x: auto; overflow-y: auto; width: 100%; max-width: 100%; max-height: {{ request('tipo') === 'logo' ? '400px' : 'none' }}; border-radius: 6px; scrollbar-width: thin; scrollbar-color: #cbd5e1 #f1f5f9;">
            <!-- Header Azul -->
            <div style="
                background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%);
                color: white;
                padding: 0.75rem 1rem;
                display: grid;
                grid-template-columns: {{ request('tipo') === 'logo' ? '140px 140px 160px 180px 190px 260px 160px 170px' : '120px 120px 120px 140px 110px 170px 160px 120px 130px 130px' }};
                gap: 1.2rem;
                font-weight: 600;
                font-size: 0.8rem;
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
                @if(request('tipo') !== 'logo')
                <div class="th-wrapper">
                    <span>Cantidad</span>
                    <button type="button" class="btn-filter-column" title="Filtrar Cantidad">
                        <span class="material-symbols-rounded">filter_alt</span>
                    </button>
                </div>
                @endif
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
                @if(request('tipo') !== 'logo')
                <div class="th-wrapper">
                    <span>Fecha Estimada</span>
                    <button type="button" class="btn-filter-column" title="Filtrar Fecha">
                        <span class="material-symbols-rounded">filter_alt</span>
                    </button>
                </div>
                @endif
            </div>

            <!-- Filas -->
            @if($pedidos->isEmpty())
                <div style="padding: 3rem 2rem; text-align: center; color: #6b7280;">
                    <i class="fas fa-inbox" style="font-size: 3rem; color: #d1d5db; margin-bottom: 1rem; display: block;"></i>
                    <p style="font-size: 1rem; margin: 0;">No hay pedidos registrados</p>
                    
                    <!-- BOTÓN DE TEST TEMPORAL -->
                    @if(request('tipo') === 'logo')
                    <button onclick="testAbrirModal()" style="margin-top: 1rem; padding: 0.5rem 1rem; background: #dc2626; color: white; border: none; border-radius: 6px; cursor: pointer;">
                        TEST: Abrir Modal
                    </button>
                    <script>
                    function testAbrirModal() {
                        console.log('🧪 TEST: Intentando abrir modal');
                        console.log('🧪 overlay elemento:', document.getElementById('modal-overlay'));
                        console.log('🧪 wrapper-logo elemento:', document.getElementById('order-detail-modal-wrapper-logo'));
                        window.openOrderDetailModalLogo();
                    }
                    </script>
                    @endif
                </div>
            @else
                @foreach($pedidos as $pedido)
                    @php
                        // Calcular número de pedido para búsqueda
                        $numeroPedidoBusqueda = '';
                        if (request('tipo') === 'logo') {
                            if (get_class($pedido) === 'App\\Models\\LogoPedido') {
                                $numeroPedidoBusqueda = $pedido->numero_pedido;
                            } else {
                                $numeroPedidoBusqueda = '#' . ($pedido->numero_pedido_mostrable ?? ($pedido->numero_pedido ?? '-'));
                            }
                        } else {
                            if (get_class($pedido) === 'App\\Models\\LogoPedido') {
                                $prod = $pedido->pedidoProduccion ?? null;
                                if ($prod && isset($prod->numero_pedido)) {
                                    $numeroPedidoBusqueda = '#' . $prod->numero_pedido;
                                } elseif (!empty($pedido->numero_pedido_cost)) {
                                    $numeroPedidoBusqueda = '#' . ltrim($pedido->numero_pedido_cost, '#');
                                } else {
                                    $numeroPedidoBusqueda = $pedido->numero_pedido ?? '-';
                                }
                            } else {
                                $numeroPedidoBusqueda = isset($pedido->numero_pedido) ? ('#' . $pedido->numero_pedido) : ('#' . ($pedido->numero_pedido_mostrable ?? '-'));
                            }
                        }
                        $clienteBusqueda = $pedido->cliente ?? '-';
                    @endphp
                    <div data-pedido-row 
                         data-numero-pedido="{{ $numeroPedidoBusqueda }}" 
                         data-cliente="{{ $clienteBusqueda }}" 
                         style="
                        display: grid;
                        grid-template-columns: {{ request('tipo') === 'logo' ? '140px 140px 160px 180px 190px 260px 160px 170px' : '120px 120px 120px 140px 110px 170px 160px 120px 130px 130px' }};
                        gap: 1.2rem;
                        padding: 0.75rem 1rem;
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
                        @php
                            $numeroPedido = get_class($pedido) === 'App\Models\LogoPedido' ? $pedido->numero_pedido : $pedido->numero_pedido;
                            $pedidoId = $pedido->id;
                            $tipoDocumento = get_class($pedido) === 'App\Models\LogoPedido' ? 'L' : ($pedido->cotizacion?->tipoCotizacion?->codigo ?? '');
                            $esLogo = get_class($pedido) === 'App\Models\LogoPedido' ? '1' : '0';
                            // Para pedidos combinados (PL), obtener el ID del logo_pedido asociado
                            $logoPedidoId = get_class($pedido) === 'App\Models\LogoPedido' ? $pedido->id : ($pedido->logoPedidos->first()?->id ?? '');
                        @endphp
                        <button class="btn-ver-dropdown" data-menu-id="menu-ver-{{ str_replace('#', '', $numeroPedido) }}" data-pedido="{{ str_replace('#', '', $numeroPedido) }}" data-pedido-id="{{ $pedidoId }}" data-logo-pedido-id="{{ $logoPedidoId }}" data-tipo-cotizacion="{{ $tipoDocumento }}" data-es-logo="{{ $esLogo }}" title="Ver Opciones" style="
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
                        @php
                            $estado = get_class($pedido) === 'App\Models\LogoPedido' ? ($pedido->estado ?? 'pendiente') : ($pedido->estado ?? 'Pendiente');
                        @endphp
                        @if($estado !== 'Anulada' && $estado !== 'anulada')
                        <button onclick="confirmarAnularPedido({{ $numeroPedido }})" title="Anular Pedido" style="
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

                        <!-- Botón Eliminar -->
                        <button onclick="confirmarEliminarPedido({{ $pedido->id }}, '{{ $numeroPedido }}')" title="Eliminar Pedido" style="

                            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
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
                            box-shadow: 0 2px 4px rgba(239, 68, 68, 0.3);
                        " onmouseover="this.style.transform='scale(1.1)'; this.style.boxShadow='0 4px 8px rgba(239, 68, 68, 0.4)'" onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 2px 4px rgba(239, 68, 68, 0.3)'">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>

                    <!-- Estado -->
                    <div style="display: flex; align-items: center;">
                        <span style="
                            background: #fef3c7;
                            color: #92400e;
                            padding: 0.25rem 0.5rem;
                            border-radius: 12px;
                            font-size: 0.7rem;
                            font-weight: 600;
                            display: inline-block;
                            overflow: hidden;
                            text-overflow: ellipsis;
                            max-width: 90px;
                            white-space: nowrap;
                        ">
                            @php
                                if (get_class($pedido) === 'App\Models\LogoPedido') {
                                    echo ucfirst($pedido->estado ?? 'pendiente');
                                } else {
                                    echo $pedido->estado ?? 'Pendiente';
                                }
                            @endphp
                        </span>
                    </div>

                    <!-- Área -->
                    <div style="display: flex; align-items: center;">
                        @php
                            $area = '-';
                            
                            // Verificar si es LogoPedido (tiene campo 'numero_pedido' pero no 'prendas')
                            if (get_class($pedido) === 'App\Models\LogoPedido') {
                                // Es un LogoPedido
                                $area = $pedido->areaActual ?? 'Creación de Orden';
                            } elseif ($pedido->logoPedidos && $pedido->logoPedidos->count() > 0) {
                                // Es un PedidoProduccion con logo
                                $logoPedido = $pedido->logoPedidos->first();
                                $area = $logoPedido->areaActual ?? 'Creación de Orden';
                            } else {
                                // Es un PedidoProduccion normal
                                $area = $pedido->procesoActualOptimizado() ?? '-';
                            }
                        @endphp
                        <span style="
                            background: #dbeafe;
                            color: #1e40af;
                            padding: 0.25rem 0.5rem;
                            border-radius: 12px;
                            font-size: 0.7rem;
                            font-weight: 600;
                            display: inline-block;
                            overflow: hidden;
                            text-overflow: ellipsis;
                            max-width: 100px;
                            white-space: nowrap;
                        ">
                            {{ $area }}

                        </span>
                    </div>

                    <!-- Pedido -->
                    <div style="display: flex; align-items: center; color: #2563eb; font-weight: 700; font-size: 0.8rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                        @php
                            // Si estamos en la pestaña de logo, mostramos el identificador de logo (#LOGO-...)
                            if (request('tipo') === 'logo') {
                                if (get_class($pedido) === 'App\\Models\\LogoPedido') {
                                    echo $pedido->numero_pedido; // #LOGO-xxxxx
                                } else {
                                    // En lista 'logo' si hay un PedidoProduccion mostramos su numero_pedido_mostrable
                                    echo '#' . ($pedido->numero_pedido_mostrable ?? ($pedido->numero_pedido ?? '-'));
                                }
                            } else {
                                // En la vista principal (Todos) y otras pestañas, mostrar el numero_pedido de pedidos_produccion
                                if (get_class($pedido) === 'App\\Models\\LogoPedido') {
                                    // Intentar obtener el pedido de producción relacionado
                                    $prod = $pedido->pedidoProduccion ?? null;
                                    if ($prod && isset($prod->numero_pedido)) {
                                        echo '#' . $prod->numero_pedido;
                                    } elseif (!empty($pedido->numero_pedido_cost)) {
                                        // Fallback al campo numero_pedido_cost si existe
                                        echo '#' . ltrim($pedido->numero_pedido_cost, '#');
                                    } else {
                                        echo $pedido->numero_pedido ?? '-';
                                    }
                                } else {
                                    // Es un PedidoProduccion
                                    echo isset($pedido->numero_pedido) ? ('#' . $pedido->numero_pedido) : ('#' . ($pedido->numero_pedido_mostrable ?? '-'));
                                }
                            }
                        @endphp
                    </div>

                    <!-- Cliente -->
                    <div style="display: flex; align-items: center; color: #374151; font-size: 0.85rem; font-weight: 500; cursor: pointer; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" onclick="abrirModalCelda('Cliente', '{{ $pedido->cliente }}')" title="Click para ver completo">
                        @php
                            echo $pedido->cliente ?? '-';
                        @endphp
                    </div>

                    <!-- Descripción -->
                    @php
                        $descripcionConTallas = '';
                        
                        // Verificar si es LogoPedido
                        if (get_class($pedido) === 'App\Models\LogoPedido') {
                            // Para LogoPedido, mostrar el campo descripción directamente
                            $descripcionConTallas = $pedido->descripcion ?? 'Logo personalizado';
                        } else {
                            // Para PedidoProduccion, usar la lógica anterior
                            $descripcionBase = $pedido->descripcion_prendas ?? '';
                            
                            // VERIFICAR SI ES COTIZACIÓN TIPO REFLECTIVO
                            $esReflectivo = false;
                            if ($pedido->cotizacion && $pedido->cotizacion->tipoCotizacion) {
                                $esReflectivo = ($pedido->cotizacion->tipoCotizacion->codigo === 'RF');
                            }
                            
                            if (!empty($descripcionBase) || ($esReflectivo && $pedido->prendas && $pedido->prendas->count() > 0)) {
                                if ($esReflectivo) {
                                    // CASO REFLECTIVO: Usar descripción tal cual (ya contiene tallas y cantidad total)
                                    $descripcionConTallas = '';
                                    
                                    if ($pedido->prendas && $pedido->prendas->count() > 0) {
                                        foreach ($pedido->prendas as $index => $prenda) {
                                            if ($index > 0) {
                                                $descripcionConTallas .= "\n\n";
                                            }
                                            
                                            // Agregar descripción de la prenda (ya tiene tallas incluidas)
                                            if (!empty($prenda->descripcion)) {
                                                $descripcionConTallas .= $prenda->descripcion;
                                            }
                                        }
                                    }
                                } else {
                                    // CASO NORMAL: Parsear por "PRENDA X:"
                                    if (strpos($descripcionBase, 'PRENDA ') !== false) {
                                        $prendas = explode('PRENDA ', $descripcionBase);
                                        $prendasCount = 0;
                                        
                                        foreach ($prendas as $index => $prendaBlock) {
                                            if ($index === 0 && empty(trim($prendaBlock))) {
                                                continue;
                                            }
                                            
                                            $prendaBlock = trim($prendaBlock);
                                            if (empty($prendaBlock)) {
                                                continue;
                                            }
                                            
                                            preg_match('/^(\d+):/', $prendaBlock, $matches);
                                            $numPrenda = isset($matches[1]) ? intval($matches[1]) : ($prendasCount + 1);
                                            
                                            $descripcionConTallas .= "PRENDA " . $prendaBlock;
                                            
                                            if ($pedido->prendas && $pedido->prendas->count() > 0) {
                                                $prendaActual = $pedido->prendas->where('numero_prenda', $numPrenda)->first();
                                                
                                                if (!$prendaActual && $prendasCount < $pedido->prendas->count()) {
                                                    $prendaActual = $pedido->prendas[$prendasCount];
                                                }
                                                
                                                if ($prendaActual && $prendaActual->cantidad_talla) {
                                                    try {
                                                        $tallas = is_string($prendaActual->cantidad_talla) 
                                                            ? json_decode($prendaActual->cantidad_talla, true) 
                                                            : $prendaActual->cantidad_talla;
                                                        
                                                        if (is_array($tallas) && !empty($tallas)) {
                                                            $tallasTexto = [];
                                                            foreach ($tallas as $talla => $cantidad) {
                                                                if ($cantidad > 0) {
                                                                    $tallasTexto[] = "$talla: $cantidad";
                                                                }
                                                            }
                                                            if (!empty($tallasTexto)) {
                                                                $descripcionConTallas .= "\nTalla: " . implode(', ', $tallasTexto);
                                                            }
                                                        }
                                                    } catch (\Exception $e) {
                                                        // Continuar sin tallas
                                                    }
                                                }
                                            }
                                            
                                            $prendasCount++;
                                            if ($prendasCount < count($prendas)) {
                                                $descripcionConTallas .= "\n\n";
                                            }
                                        }
                                    } else {
                                        // Descripción sin formato PRENDA
                                        $descripcionConTallas = $descripcionBase;
                                        
                                        if ($pedido->prendas && $pedido->prendas->count() > 0) {
                                            $prendaActual = $pedido->prendas->first();
                                            
                                            if ($prendaActual && $prendaActual->cantidad_talla) {
                                                try {
                                                    $tallas = is_string($prendaActual->cantidad_talla) 
                                                        ? json_decode($prendaActual->cantidad_talla, true) 
                                                        : $prendaActual->cantidad_talla;
                                                    
                                                    if (is_array($tallas) && !empty($tallas)) {
                                                        $tallasTexto = [];
                                                        foreach ($tallas as $talla => $cantidad) {
                                                            if ($cantidad > 0) {
                                                                $tallasTexto[] = "$talla: $cantidad";
                                                            }
                                                        }
                                                        if (!empty($tallasTexto)) {
                                                            $descripcionConTallas .= "\n\nTallas: " . implode(', ', $tallasTexto);
                                                        }
                                                    }
                                                } catch (\Exception $e) {
                                                    // Continuar sin tallas
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        
                        if (empty($descripcionConTallas)) {
                            $descripcionConTallas = get_class($pedido) === 'App\Models\LogoPedido' ? 'Logo personalizado' : '-';
                        }
                    @endphp
                    <div style="display: flex; align-items: center; color: #6b7280; font-size: 0.8rem; cursor: pointer; max-width: {{ request('tipo') === 'logo' ? '220px' : '130px' }}; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" onclick="abrirModalCelda('Descripción', {{ json_encode($descripcionConTallas) }})" title="Click para ver completo">
                        @php
                            if (get_class($pedido) === 'App\Models\LogoPedido') {
                                echo 'Logo personalizado <span style="color: #3b82f6; font-weight: 600;">...</span>';
                            } elseif ($pedido->prendas && $pedido->prendas->count() > 0) {
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

                    <!-- Cantidad (solo si no es logo) -->
                    @if(request('tipo') !== 'logo')
                    <div style="color: #374151; font-weight: 600; font-size: 0.8rem; text-align: center; white-space: nowrap;">
                        @php
                            if (get_class($pedido) === 'App\Models\LogoPedido') {
                                echo '<span style="color: #3b82f6;">LOGO</span>';
                            } elseif ($pedido->prendas->count() > 0) {
                                echo '<span>' . $pedido->prendas->sum('cantidad') . ' und</span>';
                            } else {
                                echo '<span style="color: #d1d5db;">-</span>';
                            }
                        @endphp
                    </div>
                    @endif

                    <!-- Forma Pago -->
                    <div style="display: flex; align-items: center; color: #374151; font-size: 0.8rem; cursor: pointer; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" onclick="abrirModalCelda('Forma de Pago', '{{ $pedido->forma_de_pago ?? '-' }}')" title="Click para ver completo">
                        {{ $pedido->forma_de_pago ?? '-' }}
                    </div>

                    <!-- Fecha Creación -->
                    <div style="display: flex; align-items: center; color: #6b7280; font-size: 0.75rem; white-space: nowrap;">
                        @php
                            if (get_class($pedido) === 'App\Models\LogoPedido') {
                                echo $pedido->created_at ? $pedido->created_at->format('d/m/Y') : '-';
                            } else {
                                echo $pedido->fecha_de_creacion_de_orden ? $pedido->fecha_de_creacion_de_orden->format('d/m/Y') : '-';
                            }
                        @endphp
                    </div>

                    <!-- Fecha Estimada de Entrega (solo si no es logo) -->
                    @if(request('tipo') !== 'logo')
                    <div style="display: flex; align-items: center; color: #6b7280; font-size: 0.75rem; white-space: nowrap;">
                        @php
                            if (get_class($pedido) === 'App\Models\LogoPedido') {
                                echo '-'; // LogoPedido no tiene fecha estimada
                            } else {
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
                            }
                        @endphp
                    </div>
                    @endif
                </div>
                @endforeach
            @endif
        </div>
    </div>
</div>

<!-- Contenedor para Dropdowns (Fuera de la tabla) -->
<div id="dropdowns-container" style="position: fixed; top: 0; left: 0; z-index: 999999; pointer-events: none;"></div>

<!-- Modal de Filtros (necesario para pedidos-table-filters.js) -->
<div id="filterModal" class="filter-modal-overlay" onclick="closeFilterModal(event)">
    <div class="filter-modal" onclick="event.stopPropagation()" style="width: 420px; max-width: 90%;">
        <div class="filter-modal-header" style="display:flex; justify-content:space-between; align-items:center; padding: 1.5rem; border-bottom: 1px solid #e5e7eb;">
            <h3 id="filterModalTitle" style="margin: 0; font-size: 1.125rem; color: #1e40af; font-weight: 700;">Filtrar</h3>
            <button class="filter-modal-close" onclick="closeFilterModal()" style="background:none; border:none; font-size:24px; cursor:pointer; color: #6b7280;">&times;</button>
        </div>
        <div class="filter-modal-body" style="padding: 1.5rem;">
            <input type="text" class="filter-search" id="filterSearch" placeholder="Buscar..." style="width:100%; padding:0.75rem; margin-bottom:1rem; border:2px solid #e5e7eb; border-radius:8px; font-size:0.95rem;">
            <div class="filter-options" id="filterOptions" style="display:flex; flex-direction:column; gap:0.5rem; max-height:300px; overflow-y:auto;"></div>
        </div>
        <div class="filter-modal-footer" style="display:flex; gap:8px; justify-content:flex-end; padding: 1rem 1.5rem; border-top: 1px solid #e5e7eb;">
            <button class="btn-filter-reset" onclick="resetFilters()" style="background:white; border:2px solid #e5e7eb; padding:8px 16px; border-radius:6px; cursor:pointer; font-weight:600; color:#374151;">Limpiar</button>
            <button class="btn-filter-apply" onclick="applyFilters()" style="background:linear-gradient(135deg,#1e40af,#0ea5e9); color:white; border:none; padding:8px 16px; border-radius:6px; cursor:pointer; font-weight:600;">Aplicar</button>
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

<!-- Modal de Detalle de Orden - LOGO -->
<div id="order-detail-modal-wrapper-logo" style="width: 90%; max-width: 672px; height: auto; position: fixed; top: 60%; left: 50%; transform: translate(-50%, -50%); z-index: 99999; pointer-events: auto; display: none;">
    <x-orders-components.order-detail-modal-logo />
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
     * Confirmar eliminación de pedido
     */
    function confirmarEliminarPedido(pedidoId, numeroPedido) {
        // Crear un modal de confirmación
        const confirmHTML = `
            <div id="confirmDeleteModal" style="
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
            " onclick="if(event.target.id === 'confirmDeleteModal') cerrarConfirmModal()">
                <div style="
                    background: white;
                    border-radius: 12px;
                    box-shadow: 0 12px 32px rgba(0, 0, 0, 0.15);
                    max-width: 400px;
                    width: 90%;
                    padding: 2rem;
                    animation: slideUp 0.3s ease;
                ">
                    <div style="text-align: center; margin-bottom: 1.5rem;">
                        <div style="
                            width: 56px;
                            height: 56px;
                            background: #fee2e2;
                            border-radius: 50%;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            margin: 0 auto 1rem;
                            font-size: 1.5rem;
                        ">
                            🗑️
                        </div>
                        <h3 style="
                            margin: 0 0 0.5rem 0;
                            font-size: 1.25rem;
                            font-weight: 700;
                            color: #1f2937;
                        ">Eliminar Pedido</h3>
                        <p style="
                            margin: 0;
                            color: #6b7280;
                            font-size: 0.95rem;
                        ">
                            ¿Estás seguro de que deseas eliminar el pedido <strong>#${numeroPedido}</strong>? 
                            Esta acción no se puede deshacer.
                        </p>
                    </div>

                    <div style="
                        display: flex;
                        gap: 0.75rem;
                        justify-content: flex-end;
                    ">
                        <button onclick="cerrarConfirmModal()" style="
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
                            Cancelar
                        </button>
                        <button onclick="eliminarPedido(${pedidoId})" style="
                            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
                            color: white;
                            border: none;
                            padding: 0.625rem 1.25rem;
                            border-radius: 6px;
                            cursor: pointer;
                            font-weight: 600;
                            font-size: 0.875rem;
                            transition: all 0.2s ease;
                        " onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(239, 68, 68, 0.3)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                            Eliminar
                        </button>
                    </div>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', confirmHTML);
    }

    function cerrarConfirmModal() {
        const modal = document.getElementById('confirmDeleteModal');
        if (modal) {
            modal.style.animation = 'fadeIn 0.3s ease reverse';
            setTimeout(() => modal.remove(), 300);
        }
    }

    /**
     * Eliminar pedido (hacer llamada DELETE al servidor)
     */
    function eliminarPedido(pedidoId) {
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        
        fetch(`/asesores/pedidos/${pedidoId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Mostrar mensaje de éxito
                mostrarNotificacion('✅ Pedido eliminado correctamente', 'success');
                
                // Cerrar el modal de confirmación
                cerrarConfirmModal();
                
                // Recargar la página después de 1 segundo
                setTimeout(() => {
                    location.reload();
                }, 1000);
            } else {
                mostrarNotificacion('❌ ' + (data.message || 'Error al eliminar el pedido'), 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarNotificacion('❌ Error al eliminar el pedido', 'error');
        });
    }

    /**
     * Mostrar notificación (toast)
     */
    function mostrarNotificacion(mensaje, tipo = 'info') {
        const toastHTML = `
            <div style="
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 1rem 1.5rem;
                background: ${tipo === 'success' ? '#10b981' : tipo === 'error' ? '#ef4444' : '#3b82f6'};
                color: white;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                z-index: 999999;
                animation: slideIn 0.3s ease;
                max-width: 400px;
                font-weight: 500;
            " id="toast">
                ${mensaje}
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', toastHTML);

        // Auto-remove después de 3 segundos
        setTimeout(() => {
            const toast = document.getElementById('toast');
            if (toast) {
                toast.style.animation = 'fadeIn 0.3s ease reverse';
                setTimeout(() => toast.remove(), 300);
            }
        }, 3000);
    }

    /**
     * Buscador principal: buscar por número de pedido o cliente
     */
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('mainSearchInput');
        const clearButton = document.getElementById('clearMainSearch');
        
        if (!searchInput) return;

        // Función para buscar en las filas
        function searchOrders() {
            const searchTerm = searchInput.value.toLowerCase().trim();
            const rows = document.querySelectorAll('[data-pedido-row]');
            let visibleCount = 0;

            rows.forEach(row => {
                const numeroPedido = (row.getAttribute('data-numero-pedido') || '').toLowerCase();
                const cliente = (row.getAttribute('data-cliente') || '').toLowerCase();
                
                const matches = !searchTerm || 
                               numeroPedido.includes(searchTerm) || 
                               cliente.includes(searchTerm);

                if (matches) {
                    row.style.display = 'grid';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            // Mostrar/ocultar el botón de limpiar
            if (searchTerm) {
                clearButton.style.display = 'block';
            } else {
                clearButton.style.display = 'none';
            }

            // Mensaje si no hay resultados
            const tableContainer = document.querySelector('.table-scroll-container');
            let noResultsMsg = document.getElementById('noSearchResults');
            
            if (visibleCount === 0 && searchTerm) {
                if (!noResultsMsg) {
                    noResultsMsg = document.createElement('div');
                    noResultsMsg.id = 'noSearchResults';
                    noResultsMsg.style.cssText = 'padding: 2rem; text-align: center; color: #6b7280; font-size: 0.95rem;';
                    noResultsMsg.innerHTML = `
                        <i class="fas fa-search" style="font-size: 2rem; margin-bottom: 0.5rem; opacity: 0.5;"></i>
                        <p style="margin: 0; font-weight: 600;">No se encontraron resultados</p>
                        <p style="margin: 0.25rem 0 0 0; font-size: 0.85rem;">Intenta con otro término de búsqueda</p>
                    `;
                    tableContainer.appendChild(noResultsMsg);
                }
            } else if (noResultsMsg) {
                noResultsMsg.remove();
            }
        }

        // Buscar mientras se escribe (con delay)
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(searchOrders, 300);
        });

        // Limpiar búsqueda
        clearButton.addEventListener('click', function() {
            searchInput.value = '';
            searchOrders();
            searchInput.focus();
        });
    });
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
