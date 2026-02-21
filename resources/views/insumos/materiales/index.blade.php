{{-- resources/views/insumos/materiales/index.blade.php --}}
@extends('layouts.insumos')

@section('title', 'Gestión de Insumos - Control de Insumos del Pedido')
@section('page-title', 'Control de Insumos del Pedido')

@section('content')
<link rel="stylesheet" href="{{ asset('css/insumos/materiales.css') }}?v={{ time() }}">
<style>
    /* Ocultar el top-nav del layout para esta vista */
    .top-nav {
        display: none !important;
    }
    
    /* Ajustar page-content para que no tenga padding superior */
    .page-content {
        padding: 0 !important;
        margin: 0 !important;
    }
    
    /* FIX: Remover max-width del container para insumos */
    .container {
        max-width: none !important;
        width: 100% !important;
        margin-left: 0 !important;
        padding: 1.5rem !important;
    }
    
    /* Hacer el thead sticky */
    table thead {
        position: sticky;
        top: 0;
        z-index: 10;
        background-color: inherit;
    }
    
    table thead tr {
        position: sticky;
        top: 0;
        z-index: 10;
    }
    
    table thead th {
        position: sticky;
        top: 0;
        z-index: 10;
    }
    
    /* Tooltips mejorados */
    .btn-tooltip {
        position: relative;
    }
    
    .btn-tooltip:hover::after {
        content: attr(data-tooltip);
        position: absolute;
        bottom: 100%;
        left: 50%;
        transform: translateX(-50%);
        background-color: #1f2937;
        color: white;
        padding: 0.25rem 0.5rem;
        border-radius: 0.25rem;
        font-size: 0.65rem;
        white-space: nowrap;
        z-index: 50000;
        margin-bottom: 0.25rem;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    
    .btn-tooltip:hover::before {
        content: '';
        position: absolute;
        bottom: calc(100% - 0.15rem);
        left: 50%;
        transform: translateX(-50%);
        border: 0.25rem solid transparent;
        border-top-color: #1f2937;
        z-index: 50000;
    }
    
    /* Permitir que los tooltips se muestren sin ser cortados */
    td {
        overflow: visible !important;
    }
    
    /* Asegurar que la columna de acciones sea visible */
    td:last-child {
        overflow: visible !important;
        display: table-cell !important;
        min-width: 200px !important;
    }
    
    /* Asegurar que los botones sean visibles en la celda de acciones */
    td:last-child > div {
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 0.75rem !important;
        flex-wrap: wrap !important;
        overflow: visible !important;
    }
    
    td:last-child button {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        visibility: visible !important;
        opacity: 1 !important;
    }
    
    /* Indicador de carga */
    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.3);
        display: none;
        z-index: 9999;
        align-items: center;
        justify-content: center;
    }
    
    .loading-overlay.active {
        display: flex;
    }
    
    .loading-spinner {
        width: 50px;
        height: 50px;
        border: 5px solid #f3f3f3;
        border-top: 5px solid #3498db;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    /* Mejorar contraste en modo oscuro para filas hover */
    @media (prefers-color-scheme: dark) {
        tbody tr:hover {
            background-color: rgba(59, 130, 246, 0.1) !important;
        }
    }
    
    /* Hover normal en modo claro */
    @media (prefers-color-scheme: light) {
        tbody tr:hover {
            background-color: #f9fafb !important;
        }
    }

    /* Responsive Design para Tabla */
    @media (max-width: 1024px) {
        table {
            font-size: 0.7em !important;
        }
        
        th, td {
            padding: 0.5rem !important;
        }
    }

    @media (max-width: 768px) {
        table {
            font-size: 0.65em !important;
        }
        
        th, td {
            padding: 0.35rem !important;
        }
        
        .overflow-x-auto {
            -webkit-overflow-scrolling: touch;
        }
    }

    @media (max-width: 480px) {
        table {
            font-size: 0.6em !important;
        }
        
        th, td {
            padding: 0.25rem !important;
        }
    }
</style>

@if(app()->isLocal())
<script>
    console.time('RENDER_TOTAL');
</script>
@endif
<script>
    // Lazy load images cuando estén visibles
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    if (img.dataset.src) {
                        img.src = img.dataset.src;
                        img.removeAttribute('data-src');
                    }
                    observer.unobserve(img);
                }
            });
        });
        
        document.querySelectorAll('img[data-src]').forEach(img => imageObserver.observe(img));
    }

    /**
     * Calcula los días de demora entre Fecha Pedido y Fecha Llegada EN TIEMPO REAL
     */
    function calcularDemora(materialId) {
        // El materialId tiene formato: material_PEDIDO_INDEX_NOMBRE
        // Necesitamos extraer PEDIDO e INDEX
        const idParts = materialId.split('_');
        
        // Si tiene más de 3 partes, es porque el nombre tiene guiones
        // Formato: ['material', 'PEDIDO', 'INDEX', 'NOMBRE', ...]
        const ordenId = idParts[1];
        const index = idParts[2];
        
        // Reconstruir los IDs de fecha con el mismo formato
        const fechaPedidoInput = document.getElementById('fecha_pedido_' + ordenId + '_' + index + '_' + idParts.slice(3).join('_'));
        const fechaLlegadaInput = document.getElementById('fecha_llegada_' + ordenId + '_' + index + '_' + idParts.slice(3).join('_'));
        const diasSpan = document.getElementById('dias_' + materialId);
        
        if (!fechaPedidoInput || !fechaLlegadaInput || !diasSpan) {
            return;
        }
        
        // Solo calcular si ambas fechas están completas
        if (fechaPedidoInput.value && fechaLlegadaInput.value) {
            const fechaPedido = new Date(fechaPedidoInput.value + 'T00:00:00');
            const fechaLlegada = new Date(fechaLlegadaInput.value + 'T00:00:00');
            
            // Calcular diferencia en días
            const diferencia = Math.floor((fechaLlegada - fechaPedido) / (1000 * 60 * 60 * 24));
            
            // Color según demora
            let bgColor = 'bg-gray-100';
            let textColor = 'text-gray-600';
            let icon = '';
            
            if (diferencia <= 0) {
                bgColor = 'bg-green-100';
                textColor = 'text-green-700';
                icon = '✓ ';
            } else if (diferencia <= 5) {
                bgColor = 'bg-yellow-100';
                textColor = 'text-yellow-700';
                icon = '⚠ ';
            } else {
                bgColor = 'bg-red-100';
                textColor = 'text-red-700';
                icon = '✕ ';
            }
            
            diasSpan.textContent = icon + diferencia + ' días';
            diasSpan.className = `inline-block px-3 py-1 rounded-full text-sm font-semibold ${bgColor} ${textColor}`;
        } else {
            diasSpan.textContent = '-';
            diasSpan.className = 'inline-block px-3 py-1 rounded-full text-sm font-semibold bg-gray-100 text-gray-600';
        }
    }
</script>

{{-- Toast Container --}}
<div id="toastContainer" style="position: fixed; top: 20px; right: 20px; z-index: 99999; display: flex; flex-direction: column; gap: 10px;"></div>

{{-- Loading Overlay --}}
<div id="loadingOverlay" class="loading-overlay">
    <div class="loading-spinner"></div>
</div>

<div style="min-height: 100vh; background: #f9fafb; margin: 0; padding: 1.5rem; box-sizing: border-box;">
    {{-- Header Principal Blanco --}}
    <div style="background: white; border-bottom: 1px solid #e5e7eb; box-shadow: 0 1px 2px rgba(0,0,0,0.05); width: 100%; margin: 0; box-sizing: border-box;">
        <div style="padding: 1rem 0; width: 100%;">
            {{-- Título y Descripción --}}
            <div style="margin-bottom: 1rem; padding: 0 0.5rem;">
                <h1 class="text-3xl font-bold text-gray-900 flex items-center gap-3">
                    <span class="material-symbols-rounded text-4xl text-blue-600">inventory_2</span>
                    Control de Insumos del Pedido
                </h1>
                <p class="text-gray-600 text-sm mt-2">Gestiona y controla los insumos de tus pedidos en tiempo real</p>
            </div>

            {{-- Buscador Mejorado --}}
            <form action="{{ route('insumos.materiales.index') }}" method="GET" class="flex gap-3 items-end" style="padding: 0 0.5rem;">
                <div class="flex-1 relative">
                    <div class="relative">
                        <input 
                            type="text" 
                            name="search" 
                            value="{{ request('search') }}"
                            placeholder="Buscar por Pedido (1234) o Cliente (Empresa ABC)..."
                            class="w-full px-4 py-3 bg-gray-50 text-gray-800 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition shadow-sm"
                        >
                    </div>
                </div>
                <button type="submit" class="px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition shadow-sm flex items-center gap-2 whitespace-nowrap">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    Buscar
                </button>
                @if((request('filter_column') && request('filter_values')) || (request('filter_columns') && request('filter_values')))
                    <a href="{{ route('insumos.materiales.index') }}" class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition shadow-sm flex items-center gap-2 whitespace-nowrap border border-gray-300">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                        </svg>
                        Limpiar Filtros
                    </a>
                @endif
                @if(request('search'))
                    <a href="{{ route('insumos.materiales.index') }}" class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition shadow-sm flex items-center gap-2 whitespace-nowrap border border-gray-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        Limpiar Búsqueda
                    </a>
                @endif
            </form>

            {{-- Mensaje de búsqueda activa --}}
            @if(request('search'))
                <div class="mt-4 p-4 bg-blue-50 border-l-4 border-blue-500 rounded-lg">
                    <p class="text-blue-800 text-sm flex items-center gap-2">
                        <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                        </svg>
                        <strong>Búsqueda activa:</strong> Mostrando <strong>{{ $ordenes->total() }}</strong> resultado(s) para "<strong>{{ request('search') }}</strong>"
                    </p>
                </div>
            @endif
        </div>
    </div>

    <div style="margin: 0; width: 100%; overflow: visible;">
        {{-- Tabla Principal de Órdenes --}}
        <div class="bg-white" style="margin: 0; border-radius: 0; box-shadow: none; width: 100%; overflow-x: auto; overflow-y: visible; padding: 0 0.5rem;">
            <div style="width: 100%; margin: 0; padding: 0;">
                <table class="w-full" style="font-size: 0.75em; width: 100%; margin: 0; padding: 0;">
                    <thead>
                        <tr class="bg-gradient-to-r from-blue-600 to-blue-700 text-white">
                            <th class="text-center py-4 px-6 font-bold whitespace-nowrap" style="min-width: 200px;">Acciones</th>
                            <th class="text-left py-4 px-6 font-bold">
                                <div class="flex items-center justify-between gap-2">
                                    <span>Pedido</span>
                                    <button class="filter-btn-insumos hover:bg-blue-500 p-1 rounded transition" data-column="numero_pedido" title="Filtrar por Pedido">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                                        </svg>
                                    </button>
                                </div>
                            </th>
                            <th class="text-left py-4 px-6 font-bold">
                                <div class="flex items-center justify-between gap-2">
                                    <span>Cliente</span>
                                    <button class="filter-btn-insumos hover:bg-blue-500 p-1 rounded transition" data-column="cliente" title="Filtrar por Cliente">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                                        </svg>
                                    </button>
                                </div>
                            </th>
                            <th class="text-center py-4 px-6 font-bold">
                                <div class="flex items-center justify-center gap-2">
                                    <span>Estado</span>
                                    <button class="filter-btn-insumos hover:bg-blue-500 p-1 rounded transition" data-column="estado" title="Filtrar por Estado">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                                        </svg>
                                    </button>
                                </div>
                            </th>
                            <th class="text-center py-4 px-6 font-bold">
                                <div class="flex items-center justify-center gap-2">
                                    <span>Área</span>
                                    <button class="filter-btn-insumos hover:bg-blue-500 p-1 rounded transition" data-column="area" title="Filtrar por Área">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                                        </svg>
                                    </button>
                                </div>
                            </th>
                            <th class="text-center py-4 px-6 font-bold">
                                <div class="flex items-center justify-center gap-2">
                                    <span>Fecha de Inicio</span>
                                    <button class="filter-btn-insumos hover:bg-blue-500 p-1 rounded transition" data-column="fecha_de_creacion_de_orden" title="Filtrar por Fecha de Inicio">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                                        </svg>
                                    </button>
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($ordenes ?? [] as $orden)
                            <tr class="border-b border-gray-200 hover:bg-gray-50 transition" data-pedido="{{ strtoupper($orden->numero_pedido ?? '') }}" data-cliente="{{ strtoupper($orden->cliente ?? '') }}" data-orden-pedido="{{ $orden->numero_pedido }}">
                                <td class="py-4 px-6 text-center" style="min-width: 250px; overflow: visible; background: white; position: relative; z-index: 5;">
                                    <div class="flex items-center justify-center gap-3" style="display: flex !important; flex-wrap: wrap; overflow: visible;">
                                        {{-- Botón Ver (con dropdown) --}}
                                        @php
                                            $numeroPedido = $orden->numero_pedido;
                                            $pedidoId = $orden->id;
                                        @endphp
                                        <button class="btn-ver-dropdown btn-tooltip p-2 text-blue-600 hover:bg-blue-50 rounded transition" data-menu-id="menu-ver-{{ str_replace('#', '', $numeroPedido) }}" data-pedido="{{ str_replace('#', '', $numeroPedido) }}" data-pedido-id="{{ $pedidoId }}" title="Ver Opciones">
                                            <i class="fas fa-eye text-lg"></i>
                                        </button>

                                        {{-- Botones adicionales (solo para no-patronistas) --}}
                                        @php
                                            $userRole = auth()->user()->role;
                                            $roleName = is_object($userRole) ? $userRole->name : $userRole;
                                            $isPatronista = $roleName === 'patronista';
                                        @endphp
                                        @if(!$isPatronista)
                                            {{-- Botón Materiales --}}
                                            <button 
                                                class="btn-tooltip p-2 text-green-600 hover:bg-green-50 rounded transition"
                                                onclick="abrirModalInsumos('{{ $orden->numero_pedido }}')"
                                                data-tooltip="Gestionar materiales"
                                            >
                                                <i class="fas fa-box text-lg"></i>
                                            </button>
                                            {{-- Botón Ancho y Metraje --}}
                                            <button 
                                                class="btn-tooltip p-2 text-orange-600 hover:bg-orange-50 rounded transition"
                                                onclick="abrirModalAnchoMetraje('{{ $orden->numero_pedido }}')"
                                                data-tooltip="Ingresar ancho y metraje"
                                            >
                                                <i class="fas fa-ruler text-lg"></i>
                                            </button>
                                            {{-- Botón enviar a producción: solo visible para estado Pendiente o PENDIENTE_INSUMOS --}}
                                            @if($orden->estado === 'Pendiente' || $orden->estado === 'PENDIENTE_INSUMOS')
                                                <button 
                                                    class="btn-tooltip p-2 text-blue-600 hover:bg-blue-50 rounded transition"
                                                    onclick="cambiarEstadoPedido('{{ $orden->numero_pedido }}', '{{ $orden->estado }}')"
                                                    data-tooltip="Aprobar pedido"
                                                >
                                                    <i class="fas fa-paper-plane text-lg"></i>
                                                </button>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                                <td class="py-4 px-6">
                                    <span class="font-bold text-blue-600 text-lg">{{ $orden->numero_pedido ?? 'N/A' }}</span>
                                </td>
                                <td class="py-4 px-6">
                                    <span class="font-medium text-gray-800">{{ $orden->cliente ?? 'N/A' }}</span>
                                </td>
                                <td class="py-4 px-6 text-center">
                                    @php
                                        $estadoClass = '';
                                        $estadoColor = '';
                                        if ($orden->estado === 'No iniciado') {
                                            $estadoClass = 'bg-gray-400 text-white';
                                        } elseif ($orden->estado === 'En Ejecución') {
                                            $estadoClass = 'bg-blue-100 text-blue-800';
                                        } elseif ($orden->estado === 'Anulada') {
                                            $estadoClass = 'bg-amber-100 text-amber-800';
                                        } elseif ($orden->estado === 'PENDIENTE_INSUMOS') {
                                            $estadoClass = 'bg-green-500 text-white';
                                        }
                                    @endphp
                                    <span class="inline-block px-3 py-1 rounded-full text-sm font-semibold {{ $estadoClass }}">
                                        {{ $orden->estado === 'PENDIENTE_INSUMOS' ? 'Pendiente Insumos' : ($orden->estado ?? 'N/A') }}
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-center">
                                    @php
                                        $areaClass = '';
                                        $areaText = $orden->area ?? 'N/A';
                                        if ($orden->area === 'Corte') {
                                            $areaClass = 'bg-purple-100 text-purple-800';
                                        } elseif ($orden->area === 'Creación de Orden' || $orden->area === 'Creación de orden') {
                                            $areaClass = 'bg-green-100 text-green-800';
                                            $areaText = 'Creación de Orden';
                                        }
                                    @endphp
                                    <span class="inline-block px-3 py-1 rounded-full text-sm font-semibold {{ $areaClass }}">
                                        {{ $areaText }}
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-center">
                                    <span class="text-gray-600 text-sm">
                                        {{ $orden->fecha_de_creacion_de_orden ? \Carbon\Carbon::parse($orden->fecha_de_creacion_de_orden)->subHours(5)->format('d/m/Y') : 'N/A' }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-12 px-6 text-center">
                                    <p class="text-xl text-gray-500">No hay órdenes disponibles</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    {{-- Paginación --}}
    @if($ordenes instanceof \Illuminate\Pagination\Paginator || $ordenes instanceof \Illuminate\Pagination\LengthAwarePaginator)
        <div class="table-pagination" id="tablePagination">
            <div class="pagination-info">
                <span id="paginationInfo">Mostrando {{ $ordenes->firstItem() }}-{{ $ordenes->lastItem() }} de {{ $ordenes->total() }} registros</span>
            </div>
            <div class="pagination-controls" id="paginationControls">
                @if($ordenes->hasPages())
                    <button class="pagination-btn" data-page="1" {{ $ordenes->currentPage() == 1 ? 'disabled' : '' }}>
                        <i class="fas fa-angle-double-left"></i>
                    </button>
                    <button class="pagination-btn" data-page="{{ $ordenes->currentPage() - 1 }}" {{ $ordenes->currentPage() == 1 ? 'disabled' : '' }}>
                        <i class="fas fa-angle-left"></i>
                    </button>
                    
                    @php
                        $start = max(1, $ordenes->currentPage() - 2);
                        $end = min($ordenes->lastPage(), $ordenes->currentPage() + 2);
                    @endphp
                    
                    @for($i = $start; $i <= $end; $i++)
                        <button class="pagination-btn page-number {{ $i == $ordenes->currentPage() ? 'active' : '' }}" data-page="{{ $i }}">
                            {{ $i }}
                        </button>
                    @endfor
                    
                    <button class="pagination-btn" data-page="{{ $ordenes->currentPage() + 1 }}" {{ $ordenes->currentPage() == $ordenes->lastPage() ? 'disabled' : '' }}>
                        <i class="fas fa-angle-right"></i>
                    </button>
                    <button class="pagination-btn" data-page="{{ $ordenes->lastPage() }}" {{ $ordenes->currentPage() == $ordenes->lastPage() ? 'disabled' : '' }}>
                        <i class="fas fa-angle-double-right"></i>
                    </button>
                @endif
            </div>
        </div>
    @endif
    </div>
</div>

<script>
    /**
     * Mostrar Toast Notification mejorado para mensajes multilínea
     */
    function showToast(message, type = 'success', duration = 5000) {
        const toastContainer = document.getElementById('toastContainer');
        
        // Determinar colores según tipo
        let bgColor = 'bg-green-500';
        if (type === 'error') {
            bgColor = 'bg-red-500';
        } else if (type === 'warning') {
            bgColor = 'bg-yellow-500';
        } else if (type === 'info') {
            bgColor = 'bg-blue-500';
        }
        
        // Crear elemento de toast
        const toast = document.createElement('div');
        toast.className = `${bgColor} text-white px-6 py-4 rounded-lg shadow-lg flex items-start gap-3 max-w-md`;
        toast.style.animation = 'slideIn 0.3s ease-out';
        
        // Convertir saltos de línea a <br> para HTML
        const formattedMessage = message.replace(/\n/g, '<br>');
        
        // Usar white-space: pre-line para mantener formato
        toast.innerHTML = `<span style="white-space: pre-line; line-height: 1.5;">${formattedMessage}</span>`;
        
        toastContainer.appendChild(toast);
        
        // Remover después del tiempo especificado
        setTimeout(() => {
            toast.style.animation = 'slideOut 0.3s ease-out';
            setTimeout(() => {
                toast.remove();
            }, 300);
        }, duration);
    }
    
    /**
     * Confirma la eliminación de un material y lo elimina inmediatamente
     */
    function confirmarEliminacion(checkbox, materialId) {
        // Si se deselecciona, mostrar modal de confirmación
        if (!checkbox.checked) {
            // Obtener datos del material
            const fila = checkbox.closest('tr');
            const celdas = fila.querySelectorAll('td');
            const nombreMaterial = celdas[0].textContent.trim().replace(/^[•●○◐◑\s]+/, '').trim();
            
            const inputsFecha = fila.querySelectorAll('input[type="date"]');
            const fechaPedido = inputsFecha[0]?.value || 'No especificada';
            const fechaLlegada = inputsFecha[1]?.value || 'No especificada';
            
            // Obtener el pedido del modal (es más confiable)
            const ordenPedido = document.getElementById('modalPedido').textContent;
            
            // Mostrar modal de confirmación
            Swal.fire({
                title: '¿Eliminar Material?',
                html: `<div style="text-align: left; margin: 20px 0;">
                    <p><strong>Material:</strong> ${nombreMaterial}</p>
                    <p><strong>Fecha Pedido:</strong> ${fechaPedido}</p>
                    <p><strong>Fecha Llegada:</strong> ${fechaLlegada}</p>
                    <p style="color: #ef4444; margin-top: 15px;"><strong><i class="fas fa-exclamation-triangle"></i> Se eliminará este registro y todos sus datos.</strong></p>
                </div>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    const swalContainer = document.querySelector('.swal2-container');
                    if (swalContainer) {
                        swalContainer.style.zIndex = '10020';
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    // Eliminar inmediatamente sin guardar
                    eliminarMaterialInmediatamente(nombreMaterial, ordenPedido, fila);
                } else {
                    // Volver a seleccionar si cancela
                    checkbox.checked = true;
                }
            });
        }
    }

    /**
     * Elimina un material inmediatamente del servidor (elimina completamente)
     */
    function eliminarMaterialInmediatamente(nombreMaterial, ordenPedido, fila) {
        Swal.showLoading();
        
        fetch(`/insumos/materiales/${ordenPedido}/eliminar`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ 
                nombre_material: nombreMaterial
            }),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Eliminar la fila con animación
                fila.style.animation = 'slideOut 0.3s ease-out';
                setTimeout(() => {
                    fila.remove();
                    showToast('Material eliminado correctamente', 'success');
                    Swal.hideLoading();
                    Swal.close();
                }, 300);
            } else {
                showToast('Error al eliminar: ' + data.message, 'error');
                Swal.hideLoading();
                Swal.close();
                // Volver a marcar el checkbox si falla
                const checkbox = fila.querySelector('input[type="checkbox"]');
                if (checkbox) checkbox.checked = true;
            }
        })
        .catch(error => {
            showToast('Error al eliminar el material', 'error');
            Swal.hideLoading();
            Swal.close();
            // Volver a marcar el checkbox si falla
            const checkbox = fila.querySelector('input[type="checkbox"]');
            if (checkbox) checkbox.checked = true;
        });
    }

    /**
     * Guarda los cambios enviando los datos al servidor
     */
    function guardarCambios(ordenPedido) {
        const materiales = [];
        
        // Obtener todos los checkboxes de materiales
        const checkboxes = document.querySelectorAll(`input[type="checkbox"][id^="checkbox_"]`);


        // Debug: mostrar todos los checkboxes de la página
        const todosCheckboxes = document.querySelectorAll('input[type="checkbox"]');
        todosCheckboxes.forEach((cb, i) => {
        });
        
        checkboxes.forEach((inputCheckbox, index) => {
            const fila = inputCheckbox.closest('tr');
            if (!fila) return;
            
            const celdas = fila.querySelectorAll('td');
            
            // Obtener el nombre del material del primer celda (removiendo el punto de color)
            const nombreMaterialEl = celdas[0];
            let nombreMaterial = nombreMaterialEl.textContent.trim();
            // Remover caracteres especiales del punto de color
            nombreMaterial = nombreMaterial.replace(/^[•●○◐◑\s]+/, '').trim();
            
            // Obtener los inputs de fecha de esta fila
            const inputsFecha = fila.querySelectorAll('input[type="date"]');
            const checkboxElement = fila.querySelector('input[type="checkbox"]');
            
            const fechaPedidoInput = inputsFecha[0];
            const fechaLlegadaInput = inputsFecha[1];
            
            const fechaPedido = fechaPedidoInput?.value || '';
            const fechaLlegada = fechaLlegadaInput?.value || '';
            const recibido = checkboxElement?.checked || false;
            
            // Obtener valores originales (comparar strings)
            const originalCheckbox = checkboxElement?.dataset.original === 'true';
            const originalFechaPedido = fechaPedidoInput?.dataset.original || '';
            const originalFechaLlegada = fechaLlegadaInput?.dataset.original || '';
            
            // Detectar si hay cambios (comparar valores como strings)
            const checkboxCambio = recibido !== originalCheckbox;
            const fechaPedidoCambio = (fechaPedido || null) !== (originalFechaPedido || null);
            const fechaLlegadaCambio = (fechaLlegada || null) !== (originalFechaLlegada || null);
            const hayChangios = checkboxCambio || fechaPedidoCambio || fechaLlegadaCambio;
            // Guardar si el checkbox está marcado O si hay cambios
            if (recibido || hayChangios) {
                materiales.push({
                    nombre: nombreMaterial,
                    fecha_pedido: fechaPedido || null,
                    fecha_llegada: fechaLlegada || null,
                    recibido: recibido,
                });
            }
        });
        fetch(`/insumos/materiales/${ordenPedido}/guardar`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ materiales }),
        })
        .then(response => {
            // Si no es JSON válido, mostrar error
            if (!response.ok) {
                return response.text().then(text => {
                    throw new Error(`HTTP ${response.status}: ${text.substring(0, 100)}`);
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                showToast('Guardado exitoso', 'success');
            } else {
                showToast('Guardado exitoso', 'success');
            }
        })
        .catch(error => {
            let mensajeError = 'Error al guardar los cambios';
            
            // Si es un error JSON, extraer el mensaje
            if (error.message.includes('HTTP')) {
                mensajeError = error.message;
            } else if (error instanceof SyntaxError) {
                mensajeError = 'Error en el servidor (respuesta inválida)';
            }
            
            showToast(mensajeError, 'error');
        });
    }

    /**
     * Limpia todos los campos del formulario de una orden
     */
    function limpiarFormulario(ordenId) {
        const orden = document.querySelector(`[data-pedido]`).closest('.orden-item');
        const inputs = orden.querySelectorAll('input[type="date"], input[type="checkbox"]');
        
        inputs.forEach(input => {
            if (input.type === 'date') {
                input.value = '';
            } else if (input.type === 'checkbox') {
                input.checked = false;
            }
        });
        
        // Limpiar también los spans de días
        const diasSpans = orden.querySelectorAll('[id^="dias_"]');
        diasSpans.forEach(span => {
            span.textContent = '-';
            span.className = 'inline-block px-3 py-1 rounded-full text-sm font-semibold bg-gray-100 text-gray-600';
        });
    }
</script>

<style>
    input[type="date"] {
        appearance: none;
        -webkit-appearance: none;
        cursor: pointer;
    }

    input[type="date"]:focus {
        outline: none;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    .accent-green-500:checked {
        accent-color: #22c55e;
    }

    /* Estilos del Modal de Orden */
    .modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.7);
        z-index: 9999;
        align-items: center;
        justify-content: center;
    }

    .modal-overlay.active {
        display: flex;
    }

    .order-detail-modal-container {
        background: white;
        width: 90%;
        max-width: 900px;
        max-height: 90vh;
        overflow-y: auto;
        border-radius: 24px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
        padding: 30px;
    }

    .order-detail-card {
        border: 2px solid #000;
        border-radius: 10px;
        padding: 30px;
        background: white;
        position: relative;
    }

    .order-logo {
        display: block;
        margin: 0 auto 20px auto;
        width: 120px;
        height: auto;
    }

    .order-date {
        display: inline-block;
        background: black;
        border-radius: 8px;
        padding: 8px 12px;
        color: white;
        text-align: center;
        margin-bottom: 15px;
    }

    .fec-label {
        font-weight: bold;
        font-size: 12px;
        text-transform: uppercase;
    }

    .date-boxes {
        display: flex;
        gap: 4px;
        margin-top: 4px;
    }

    .date-box {
        background: white;
        color: black;
        border-radius: 4px;
        width: 45px;
        height: 28px;
        line-height: 26px;
        font-weight: bold;
        text-align: center;
        font-size: 12px;
    }

    .order-header-info {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
        margin: 15px 0;
    }

    .order-info-field {
        font-weight: 600;
        font-size: 13px;
    }

    .order-info-field span {
        font-weight: 400;
        display: block;
        margin-top: 2px;
    }

    .receipt-title {
        text-align: center;
        font-weight: 800;
        font-size: 18px;
        text-transform: uppercase;
        margin: 20px 0;
        color: #000;
    }

    .pedido-number {
        text-align: center;
        font-weight: 800;
        font-size: 16px;
        color: #ff0000;
        margin: 10px 0;
    }

    .separator-line {
        height: 2px;
        background-color: #000;
        margin: 20px 0;
    }

    .signature-section {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-top: 30px;
    }

    .signature-field {
        font-weight: 600;
        font-size: 13px;
        flex: 1;
    }

    .vertical-separator {
        width: 2px;
        background-color: #000;
        margin: 0 20px;
        height: 60px;
    }

    .close-modal-btn {
        display: inline-block;
        margin-top: 20px;
        padding: 8px 16px;
        background: #3b82f6;
        color: white;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
    }

    .close-modal-btn:hover {
        background: #2563eb;
    }

    /* Toast Animations */
    @keyframes slideIn {
        from {
            transform: translateX(400px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(400px);
            opacity: 0;
        }
    }
</style>

{{-- Modal para ver orden --}}
<!-- Modal de Detalle de Orden -->
<div id="modal-overlay" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.5); backdrop-filter: blur(4px); z-index: 9997; display: none; pointer-events: auto;" onclick="closeModalOverlay()"></div>

<div id="order-detail-modal-wrapper" style="width: 90%; max-width: 672px; position: fixed; top: 60%; left: 50%; transform: translate(-50%, -50%); z-index: 9998; pointer-events: auto; display: none;">
    <x-orders-components.order-detail-modal />
</div>

{{-- Modal para ver insumos --}}
<div id="insumosModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center" style="display: none; z-index: 10001; top: 0; left: 0; right: 0; bottom: 0;">
    <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto" style="z-index: 10002;">
        <div class="sticky top-0 bg-gradient-to-r from-blue-600 to-blue-700 text-white p-6 flex justify-between items-center" style="z-index: 10003;">
            <div>
                <h2 class="text-2xl font-bold flex items-center gap-2">
                    <i class="fas fa-box"></i>
                    Insumos de la Orden
                </h2>
                <p class="text-blue-100 text-sm">Pedido: <span id="modalPedido" class="font-bold"></span></p>
            </div>
            <button onclick="cerrarModalInsumos()" class="text-white hover:bg-blue-600 rounded-full p-2 transition">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>

        <div class="p-6">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-100 border-b-2 border-gray-300">
                            <th class="text-left py-3 px-4 font-bold text-gray-800 min-w-max">Insumo</th>
                            <th class="text-center py-3 px-3 font-bold text-gray-800">Estado</th>
                            <th class="text-center py-3 px-3 font-bold text-gray-800">Fecha Orden</th>
                            <th class="text-center py-3 px-3 font-bold text-gray-800">Fecha Pedido</th>
                            <th class="text-center py-3 px-3 font-bold text-gray-800">Fecha Pago</th>
                            <th class="text-center py-3 px-3 font-bold text-gray-800">Fecha Despacho</th>
                            <th class="text-center py-3 px-3 font-bold text-gray-800">Fecha Llegada</th>
                            <th class="text-center py-3 px-3 font-bold text-gray-800">Días Demora</th>
                            <th class="text-center py-3 px-3 font-bold text-gray-800">Observaciones</th>
                            <th class="text-center py-3 px-3 font-bold text-gray-800">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="insumosTableBody">
                        <!-- Se llena dinámicamente -->
                    </tbody>
                </table>
            </div>

            <div class="mt-6 flex gap-3 justify-between">
                <div class="flex gap-3">
                    <button 
                        onclick="agregarMaterialModal()"
                        class="px-6 py-2 bg-green-600 text-white font-semibold rounded-lg hover:bg-green-700 transition flex items-center gap-2"
                    >
                        <i class="fas fa-plus"></i> Agregar Insumo
                    </button>
                </div>
                <div class="flex gap-3">
                    <button 
                        onclick="guardarInsumosModal()"
                        class="px-6 py-2 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition flex items-center gap-2"
                    >
                        <i class="fas fa-save"></i> Guardar Cambios
                    </button>
                    <button 
                        onclick="cerrarModalInsumos()"
                        class="px-6 py-2 bg-gray-400 text-white font-semibold rounded-lg hover:bg-gray-500 transition flex items-center gap-2"
                    >
                        <i class="fas fa-times"></i> Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal para ver/editar observaciones --}}
<div id="observacionesModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center" style="display: none; z-index: 10001;">
    <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4" style="z-index: 10002;">
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white p-6 flex justify-between items-center">
            <div>
                <h2 class="text-2xl font-bold flex items-center gap-2">
                    <i class="fas fa-sticky-note"></i>
                    Observaciones del Insumo
                </h2>
                <p class="text-blue-100 text-sm">Material: <span id="observacionesMaterial" class="font-bold"></span></p>
            </div>
            <button onclick="cerrarModalObservaciones()" class="text-white hover:bg-blue-600 rounded-full p-2 transition">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div class="p-6">
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Observaciones:</label>
                <textarea 
                    id="observacionesTexto" 
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none" 
                    rows="6"
                    placeholder="Escribe las observaciones del insumo aquí..."
                    onkeydown="if(event.ctrlKey && event.key === 'Enter') guardarObservaciones()"
                ></textarea>
                <p class="text-gray-500 text-xs mt-2">💡 Presiona <strong>Ctrl + Enter</strong> para guardar rápidamente</p>
            </div>
            <div class="flex gap-3 justify-end">
                <button 
                    onclick="guardarObservaciones()" 
                    class="px-6 py-2 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition flex items-center gap-2"
                >
                    <i class="fas fa-save"></i> Guardar
                </button>
                <button 
                    onclick="cerrarModalObservaciones()" 
                    class="px-6 py-2 bg-gray-400 text-white font-semibold rounded-lg hover:bg-gray-500 transition flex items-center gap-2"
                >
                    <i class="fas fa-times"></i> Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Modal de Ancho y Metraje --}}
<div id="modalAnchoMetraje" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center" style="z-index: 10001;">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4" style="z-index: 10002;">
        <div class="bg-gradient-to-r from-blue-900 to-blue-800 text-white p-6 flex justify-between items-center shadow-lg" style="background: linear-gradient(to right, #111827, #1e3a8a) !important;">
            <div>
                <h2 class="text-2xl font-bold flex items-center gap-2 drop-shadow text-white">
                    <i class="fas fa-ruler"></i>
                    Ancho y Metraje
                </h2>
                <p class="text-white text-sm font-semibold drop-shadow">Pedido: <span id="anchoMetrajePedido" class="font-bold text-white"></span></p>
            </div>
            <button onclick="cerrarModalAnchoMetraje()" class="text-white bg-blue-700 rounded-full p-2 transition hover:bg-blue-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div class="p-6 space-y-6">
            <div>
                <label class="block text-base font-bold text-gray-800 mb-2">Prenda:</label>
                <select 
                    id="prendaSelect" 
                    class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-transparent"
                    onchange="onPrendaSeleccionada()"
                >
                    <option value="">Seleccione una prenda...</option>
                </select>
            </div>
            <div>
                <label class="block text-base font-bold text-gray-800 mb-2">Ancho (m):</label>
                <input 
                    type="number" 
                    id="anchoInput" 
                    class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-transparent"
                    placeholder="Ingresa el ancho en metros..."
                    step="0.01"
                    min="0"
                >
            </div>
            <div>
                <label class="block text-base font-bold text-gray-800 mb-2">Metraje (m):</label>
                <input 
                    type="number" 
                    id="metrajeInput" 
                    class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-transparent"
                    placeholder="Ingresa el metraje en metros..."
                    step="0.01"
                    min="0"
                >
            </div>
            <div class="flex gap-3 justify-end">
                <button 
                    onclick="guardarAnchoMetraje()" 
                    class="px-6 py-2 text-white font-semibold rounded-lg flex items-center gap-2"
                    style="background: linear-gradient(to right, #111827, #1e3a8a) !important; color: white !important;"
                >
                    <i class="fas fa-save"></i> Guardar
                </button>
                <button 
                    onclick="cerrarModalAnchoMetraje()" 
                    class="px-6 py-2 bg-gray-400 text-white font-semibold rounded-lg flex items-center gap-2"
                >
                    <i class="fas fa-times"></i> Cancelar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Contenedor para dropdowns dinámicos -->
<div id="dropdowns-container" style="position: fixed; top: 0; left: 0; z-index: 999999; pointer-events: none;"></div>

{{-- Modal de Confirmación para Enviar a Producción --}}
<div id="modalConfirmarProduccion" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center" style="display: none; z-index: 10001; top: 0; left: 0; right: 0; bottom: 0;">
    <div class="bg-white rounded-lg shadow-2xl" style="width: 380px; z-index: 10002;">
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white p-4 rounded-t-lg flex items-center gap-3">
            <i class="fas fa-industry text-2xl"></i>
            <h2 class="text-base font-bold">Aprobar Pedido</h2>
        </div>

        <div class="p-5">
            <p class="text-gray-700 mb-2 text-sm font-semibold">Pedido:</p>
            <p class="text-2xl font-bold text-blue-600 mb-4" id="numeroPedidoConfirm"></p>
            
            <p class="text-gray-600 text-sm leading-relaxed mb-6">
                ¿Aprobar este pedido para enviar a producción? Esta acción es definitiva.
            </p>
            
            <div class="flex gap-3 justify-end">
                <button 
                    onclick="cerrarModalConfirmarProduccion()"
                    class="flex-1 px-4 py-2 bg-gray-200 text-gray-700 font-semibold rounded hover:bg-gray-300 transition text-sm"
                >
                    Cancelar
                </button>
                <button 
                    onclick="confirmarEnvioProduccion()"
                    class="flex-1 px-4 py-2 bg-blue-600 text-white font-semibold rounded hover:bg-blue-700 transition text-sm"
                >
                    Aprobar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    /**
     * Alias para cerrar el modal - compatible con asesores
     */

    /**
     * Abre el modal de Ancho y Metraje para una orden
     */
    function abrirModalAnchoMetraje(pedido) {
        const modal = document.getElementById('modalAnchoMetraje');
        modal.style.display = 'flex';
        
        // Establecer el pedido
        document.getElementById('anchoMetrajePedido').textContent = pedido;
        
        // Guardar el pedido en el modal para usarlo después
        modal.dataset.pedido = pedido;

        // Cargar las prendas del pedido
        cargarPrendasDelPedido(pedido);

        // Detectar automáticamente la prenda seleccionada actualmente
        let prendaIdSeleccionada = null;
        
        // 1. Verificar si hay un ReceiptManager con prenda seleccionada
        if (window.receiptManager && window.receiptManager.prendasIndex !== null) {
            const prendasIndex = window.receiptManager.prendasIndex;
            const datosFactura = window.receiptManager.datosFactura;
            
            if (datosFactura && datosFactura.prendas && datosFactura.prendas[prendasIndex]) {
                const prendaSeleccionada = datosFactura.prendas[prendasIndex];
                prendaIdSeleccionada = prendaSeleccionada.id;
                
                console.log('[abrirModalAnchoMetraje] Prenda detectada desde ReceiptManager:', {
                    prendasIndex: prendasIndex,
                    prendaNombre: prendaSeleccionada.nombre,
                    prendaId: prendaIdSeleccionada
                });
            }
        }
        
        // 2. Esperar a que se carguen las prendas y luego seleccionar la prenda detectada
        setTimeout(() => {
            if (prendaIdSeleccionada) {
                const select = document.getElementById('prendaSelect');
                if (select.querySelector(`option[value="${prendaIdSeleccionada}"]`)) {
                    select.value = prendaIdSeleccionada;
                    console.log('[abrirModalAnchoMetraje] Prenda seleccionada automáticamente:', prendaIdSeleccionada);
                    
                    // Cargar los datos de esta prenda automáticamente
                    onPrendaSeleccionada();
                } else {
                    console.warn('[abrirModalAnchoMetraje] No se encontró la prenda con ID:', prendaIdSeleccionada);
                    // Limpiar inputs si no se encuentra la prenda
                    document.getElementById('anchoInput').value = '';
                    document.getElementById('metrajeInput').value = '';
                }
            } else {
                // Si no hay prenda seleccionada, limpiar inputs
                document.getElementById('anchoInput').value = '';
                document.getElementById('metrajeInput').value = '';
                console.log('[abrirModalAnchoMetraje] No hay prenda seleccionada, mostrando modal vacío');
            }
        }, 500); // Esperar a que se cargue el selector de prendas
    }

    /**
     * Carga las prendas del pedido en el selector
     */
    function cargarPrendasDelPedido(pedido) {
        fetch(`/insumos/materiales/${pedido}/obtener-prendas`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.prendas) {
                    const select = document.getElementById('prendaSelect');
                    select.innerHTML = '<option value="">Seleccione una prenda...</option>';
                    
                    data.prendas.forEach(prenda => {
                        const option = document.createElement('option');
                        option.value = prenda.id;
                        option.textContent = `${prenda.nombre_prenda} - ${prenda.descripcion || ''}`;
                        select.appendChild(option);
                    });
                }
            })
            .catch(error => {
                console.error('Error al cargar prendas del pedido:', error);
            });
    }

    /**
     * Se ejecuta cuando se selecciona una prenda
     */
    function onPrendaSeleccionada() {
        const prendaId = document.getElementById('prendaSelect').value;
        
        // Primero limpiar siempre los campos para evitar mostrar datos de la prenda anterior
        document.getElementById('anchoInput').value = '';
        document.getElementById('metrajeInput').value = '';
        
        if (prendaId) {
            // Cargar datos de ancho/metraje para esta prenda específica
            const pedido = document.getElementById('modalAnchoMetraje').dataset.pedido;
            console.log(`[onPrendaSeleccionada] Cargando datos para prenda ${prendaId} del pedido ${pedido}`);
            
            fetch(`/insumos/materiales/${pedido}/obtener-ancho-metraje-prenda/${prendaId}`)
                .then(response => response.json())
                .then(data => {
                    console.log(`[onPrendaSeleccionada] Respuesta recibida:`, data);
                    
                    if (data.success && data.data) {
                        // Solo establecer valores si existen datos guardados
                        if (data.data.ancho) {
                            document.getElementById('anchoInput').value = data.data.ancho;
                            console.log(`[onPrendaSeleccionada] Ancho cargado: ${data.data.ancho}`);
                        }
                        if (data.data.metraje) {
                            document.getElementById('metrajeInput').value = data.data.metraje;
                            console.log(`[onPrendaSeleccionada] Metraje cargado: ${data.data.metraje}`);
                        }
                        
                        if (!data.data.ancho && !data.data.metraje) {
                            console.log(`[onPrendaSeleccionada] La prenda ${prendaId} no tiene datos guardados, campos permanecen vacíos`);
                        }
                    } else {
                        console.log(`[onPrendaSeleccionada] La prenda ${prendaId} no tiene datos guardados o ocurrió un error`);
                        // Los campos ya están limpios, no hacer nada más
                    }
                })
                .catch(error => {
                    console.error('Error al cargar datos de ancho/metraje de la prenda:', error);
                    // Los campos ya están limpios, no hacer nada más
                });
        } else {
            console.log('[onPrendaSeleccionada] No hay prenda seleccionada, campos limpiados');
        }
    }

    /**
     * Cierra el modal de Ancho y Metraje
     */
    function cerrarModalAnchoMetraje() {
        const modal = document.getElementById('modalAnchoMetraje');
        modal.style.display = 'none';
        
        // Limpiar los inputs
        document.getElementById('anchoInput').value = '';
        document.getElementById('metrajeInput').value = '';
    }

    /**
     * Guarda los valores de Ancho y Metraje
     */
    function guardarAnchoMetraje() {
        const ancho = parseFloat(document.getElementById('anchoInput').value);
        const metraje = parseFloat(document.getElementById('metrajeInput').value);
        const prendaId = document.getElementById('prendaSelect').value;
        const pedido = document.getElementById('anchoMetrajePedido').textContent;
        
        // Validar que los campos estén completos
        if (!ancho || isNaN(ancho) || ancho <= 0) {
            showToast('Por favor ingresa un ancho válido', 'warning');
            return;
        }
        
        if (!metraje || isNaN(metraje) || metraje <= 0) {
            showToast('Por favor ingresa un metraje válido', 'warning');
            return;
        }
        
        if (!prendaId) {
            showToast('Por favor selecciona una prenda', 'warning');
            return;
        }
        
        // Guardar los datos globalmente usando la función universal
        window.actualizarAnchoMetrajeUniversal(ancho, metraje, pedido);
        
        console.log('[guardarAnchoMetraje] Datos guardados usando función universal');
        
        // Enviar los datos al servidor con la relación de prenda
        fetch(`/insumos/materiales/${pedido}/guardar-ancho-metraje-prenda`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            },
            body: JSON.stringify({
                pedido: pedido,
                prenda_id: prendaId,
                ancho: ancho,
                metraje: metraje
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Ancho y metraje guardados correctamente', 'success');
                
                // Si hay un recibo abierto, actualizarlo dinámicamente
                if (window.receiptManager && window.receiptManager.datosFactura) {
                    console.log('[guardarAnchoMetraje] Actualizando recibo abierto...');
                    actualizarReciboConAnchoMetraje();
                }
                
                // Limpiar los inputs
                document.getElementById('anchoInput').value = '';
                document.getElementById('metrajeInput').value = '';
                document.getElementById('prendaSelect').value = '';
                
                // Cerrar el modal
                setTimeout(() => {
                    cerrarModalAnchoMetraje();
                }, 1000);
            } else {
                showToast('Error al guardar los datos', 'error');
            }
        })
        .catch(error => {
            console.error('Error al guardar ancho y metraje:', error);
            showToast('Error al guardar los datos', 'error');
        });
    }
    
    /**
     * Actualiza el recibo abierto con los datos de ancho y metraje
     */
    function actualizarReciboConAnchoMetraje() {
        if (!window.datosAnchoMetraje || !window.receiptManager) {
            console.log('[actualizarReciboConAnchoMetraje] No hay datos de ancho/metraje o ReceiptManager');
            return;
        }
        
        const { ancho, metraje } = window.datosAnchoMetraje;
        
        // Buscar o crear el elemento para mostrar ancho y metraje
        let anchoMetrajeElement = document.getElementById('ancho-metraje-disponible');
        
        if (!anchoMetrajeElement) {
            // Crear el elemento si no existe
            anchoMetrajeElement = document.createElement('div');
            anchoMetrajeElement.id = 'ancho-metraje-disponible';
            anchoMetrajeElement.style.cssText = `
                position: absolute;
                top: 15px;
                right: 15px;
                background: rgba(0, 0, 0, 0.8);
                color: white;
                padding: 8px 12px;
                border-radius: 8px;
                font-size: 0.75rem;
                font-weight: bold;
                text-align: right;
                z-index: 10;
            `;
            
            // Insertar después del título del recibo
            const receiptTitle = document.getElementById('receipt-title');
            if (receiptTitle) {
                receiptTitle.parentNode.insertBefore(anchoMetrajeElement, receiptTitle.nextSibling);
            }
        }
        
        // Actualizar el contenido
        anchoMetrajeElement.innerHTML = `
            ANCHO DISPONIBLE: ${ancho.toFixed(2)} m<br>
            METRAJE DISPONIBLE: ${metraje.toFixed(2)} m
        `;
        
        console.log('[actualizarReciboConAnchoMetraje] Recibo actualizado con ancho y metraje');
    }

    /**
     * Abre el modal de insumos para una orden
     */
    function abrirModalInsumos(pedido) {
        // Mostrar el modal
        const modal = document.getElementById('insumosModal');
        modal.style.display = 'flex';
        
        // Remover aria-hidden del contenido principal para evitar conflictos
        const mainContent = document.getElementById('mainContent');
        if (mainContent) {
            mainContent.removeAttribute('aria-hidden');
        }

        // Establecer el pedido
        document.getElementById('modalPedido').textContent = pedido;

        // Cargar los insumos de la orden
        fetch(`/insumos/api/materiales/${pedido}`)
            .then(response => response.json())
            .then(data => {
                llenarTablaInsumos(data.materiales || []);
            })
            .catch(error => {
                showToast('Error al cargar los insumos', 'error');
            });
    }

    /**
     * Cierra el modal de insumos
     */
    function cerrarModalInsumos() {
        const modal = document.getElementById('insumosModal');
        modal.style.display = 'none';
        
        // Restaurar aria-hidden al contenido principal
        const mainContent = document.getElementById('mainContent');
        if (mainContent) {
            mainContent.setAttribute('aria-hidden', 'false');
        }
    }

    /**
     * Llena la tabla de insumos del modal
     */
    function llenarTablaInsumos(materiales) {
        const tbody = document.getElementById('insumosTableBody');
        tbody.innerHTML = '';

        const pedido = document.getElementById('modalPedido').textContent;
        
        // Mostrar SOLO los materiales que ya están guardados (sin mostrar estándar por defecto)
        materiales.forEach((materialData, index) => {
            crearFilaMaterial(materialData.nombre_material, materialData, index, pedido, tbody);
        });
    }

    /**
     * Crea una fila de material en la tabla
     */
    function crearFilaMaterial(nombreMaterial, materialData, index, pedido, tbody) {
        const sanitizedMaterial = nombreMaterial.replace(/\s+/g, '_').toLowerCase();
        const materialId = `material_modal_${pedido}_${index}_${sanitizedMaterial}`;

        const row = document.createElement('tr');
        row.className = 'border-b border-gray-200 hover:bg-gray-50 transition';
        row.id = `row_${materialId}`;
        
        const colores = ['bg-green-500', 'bg-yellow-500', 'bg-gray-400'];
        const colorPunto = colores[index % 3];

        row.innerHTML = `
            <td class="py-3 px-4 font-medium text-gray-900 min-w-max">
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 rounded-full ${colorPunto}"></div>
                    <span>${nombreMaterial}</span>
                </div>
            </td>
            <td class="py-3 px-3 text-center">
                <input 
                    type="checkbox" 
                    id="checkbox_${materialId}"
                    class="w-5 h-5 cursor-pointer material-checkbox accent-green-500"
                    ${materialData.recibido ? 'checked' : ''}
                    data-original="${materialData.recibido ? 'true' : 'false'}"
                >
            </td>
            <td class="py-3 px-3 text-center">
                <input 
                    type="date" 
                    id="fecha_orden_${materialId}"
                    class="px-2 py-1 border border-gray-300 rounded text-xs font-medium text-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 w-full"
                    value="${materialData.fecha_orden ? materialData.fecha_orden : ''}"
                    data-original="${materialData.fecha_orden ? materialData.fecha_orden : ''}"
                >
            </td>
            <td class="py-3 px-3 text-center">
                <input 
                    type="date" 
                    id="fecha_pedido_${materialId}"
                    class="px-2 py-1 border border-gray-300 rounded text-xs font-medium text-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 w-full"
                    value="${materialData.fecha_pedido ? materialData.fecha_pedido : ''}"
                    data-original="${materialData.fecha_pedido ? materialData.fecha_pedido : ''}"
                >
            </td>
            <td class="py-3 px-3 text-center">
                <input 
                    type="date" 
                    id="fecha_pago_${materialId}"
                    class="px-2 py-1 border border-gray-300 rounded text-xs font-medium text-purple-600 focus:outline-none focus:ring-2 focus:ring-purple-500 w-full"
                    value="${materialData.fecha_pago ? materialData.fecha_pago : ''}"
                    data-original="${materialData.fecha_pago ? materialData.fecha_pago : ''}"
                >
            </td>
            <td class="py-3 px-3 text-center">
                <input 
                    type="date" 
                    id="fecha_despacho_${materialId}"
                    class="px-2 py-1 border border-gray-300 rounded text-xs font-medium text-orange-600 focus:outline-none focus:ring-2 focus:ring-orange-500 w-full"
                    value="${materialData.fecha_despacho ? materialData.fecha_despacho : ''}"
                    data-original="${materialData.fecha_despacho ? materialData.fecha_despacho : ''}"
                >
            </td>
            <td class="py-3 px-3 text-center">
                <input 
                    type="date" 
                    id="fecha_llegada_${materialId}"
                    class="px-2 py-1 border border-gray-300 rounded text-xs font-medium text-green-600 focus:outline-none focus:ring-2 focus:ring-green-500 w-full"
                    value="${materialData.fecha_llegada ? materialData.fecha_llegada : ''}"
                    data-original="${materialData.fecha_llegada ? materialData.fecha_llegada : ''}"
                >
            </td>
            <td class="py-3 px-3 text-center">
                <span class="inline-block px-2 py-1 rounded text-xs font-semibold bg-gray-100 text-gray-600 flex items-center justify-center gap-1">
                    ${materialData.dias_demora !== null && materialData.dias_demora !== undefined ? 
                        (materialData.dias_demora <= 0 ? '<i class="fas fa-check text-green-600"></i>' : 
                         materialData.dias_demora <= 5 ? '<i class="fas fa-exclamation-triangle text-yellow-600"></i>' : 
                         '<i class="fas fa-times text-red-600"></i>') + 
                        materialData.dias_demora + 'd' 
                        : '-'}
                </span>
            </td>
            <td class="py-3 px-3 text-center">
                <button 
                    onclick="abrirModalObservaciones('${materialId}', '${nombreMaterial}')"
                    class="px-2 py-1 bg-blue-100 text-blue-600 font-medium rounded hover:bg-blue-200 transition text-sm flex items-center gap-1 justify-center"
                    title="Ver/Editar observaciones"
                >
                    <i class="fas fa-eye"></i>
                </button>
                <input type="hidden" id="observaciones_${materialId}" value="${materialData.observaciones ? materialData.observaciones.replace(/"/g, '&quot;') : ''}">
            </td>
            <td class="py-3 px-3 text-center">
                <button 
                    onclick="eliminarFilaMaterial('${materialId}')"
                    class="px-2 py-1 bg-red-100 text-red-600 font-medium rounded hover:bg-red-200 transition text-sm flex items-center gap-1 justify-center"
                    title="Eliminar"
                >
                    <i class="fas fa-trash-alt"></i>
                </button>
            </td>
        `;

        tbody.appendChild(row);
    }

    /**
     * Mostrar modal para agregar nuevo material
     */
    function agregarMaterialModal() {
        const materialesEstandar = [
            'Tela', 
            'Reflectivo', 
            'Cierre', 
            'Cuello y puños',
            'Sesgo Relleno',
            'Sesgo Tela',
            'Sesgo en la misma Tela',
            'Hiladillo',
            'Citafalla',
            'Cordón'
        ];
        const tbody = document.getElementById('insumosTableBody');
        
        // Obtener materiales ya agregados
        const materialesAgregados = new Set();
        tbody.querySelectorAll('tr').forEach(fila => {
            const nombre = fila.querySelector('td:first-child span').textContent.trim();
            materialesAgregados.add(nombre);
        });
        
        // Filtrar materiales estándar que no estén agregados
        const materialesDisponibles = materialesEstandar.filter(m => !materialesAgregados.has(m));
        
        // Crear opciones HTML con datalist
        const opcionesHTML = `
            <div style="text-align: left;">
                <label style="display: block; margin-bottom: 10px; font-weight: bold;">Seleccionar o Escribir Insumo:</label>
                <input 
                    type="text" 
                    id="materialInput" 
                    list="materialesList"
                    placeholder="Selecciona o escribe un insumo..."
                    style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px;"
                    autocomplete="off"
                >
                <datalist id="materialesList">
                    ${materialesDisponibles.map(m => `<option value="${m}">`).join('')}
                </datalist>
            </div>
        `;
        
        Swal.fire({
            title: 'Agregar Insumo',
            html: opcionesHTML,
            icon: 'info',
            showCancelButton: true,
            confirmButtonColor: '#10b981',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Agregar',
            cancelButtonText: 'Cancelar',
            allowOutsideClick: false,
            allowEscapeKey: false,
            customClass: {
                container: 'swal-container-top',
                popup: 'swal-popup-top'
            },
            didOpen: () => {
                const inputElement = document.getElementById('materialInput');
                if (inputElement) {
                    inputElement.focus();
                }
                
                // Asegurar z-index superior
                const swalContainer = document.querySelector('.swal2-container');
                if (swalContainer) {
                    swalContainer.style.zIndex = '10010';
                }
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const inputElement = document.getElementById('materialInput');
                const nombreMaterial = inputElement?.value.trim() || '';
                
                if (!nombreMaterial) {
                    showToast('Debes seleccionar o ingresar un material', 'warning');
                    return;
                }
                
                agregarMaterialATabla(nombreMaterial);
            }
        });
    }

    /**
     * Agregar material a la tabla
     */
    function agregarMaterialATabla(nombreMaterial) {
        const tbody = document.getElementById('insumosTableBody');
        const pedido = document.getElementById('modalPedido').textContent;
        const index = tbody.children.length;
        const sanitizedMaterial = nombreMaterial.replace(/\s+/g, '_').toLowerCase();
        const materialId = `material_modal_${pedido}_${index}_${sanitizedMaterial}`;

        const colores = ['bg-green-500', 'bg-yellow-500', 'bg-gray-400'];
        const colorPunto = colores[index % 3];

        const row = document.createElement('tr');
        row.className = 'border-b border-gray-200 hover:bg-gray-50 transition';
        row.id = `row_${materialId}`;
        
        // Inicializar atributo data-observaciones vacío
        row.setAttribute('data-observaciones', '');

        row.innerHTML = `
            <td class="py-3 px-4 font-medium text-gray-900 min-w-max">
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 rounded-full ${colorPunto}"></div>
                    <span>${nombreMaterial}</span>
                </div>
            </td>
            <td class="py-3 px-3 text-center">
                <input 
                    type="checkbox" 
                    id="checkbox_${materialId}"
                    class="w-5 h-5 cursor-pointer material-checkbox accent-green-500"
                    data-original="false"
                >
            </td>
            <td class="py-3 px-3 text-center">
                <input 
                    type="date" 
                    id="fecha_orden_${materialId}"
                    class="px-2 py-1 border border-gray-300 rounded text-xs font-medium text-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 w-full"
                    data-original=""
                >
            </td>
            <td class="py-3 px-3 text-center">
                <input 
                    type="date" 
                    id="fecha_pedido_${materialId}"
                    class="px-2 py-1 border border-gray-300 rounded text-xs font-medium text-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 w-full"
                    data-original=""
                >
            </td>
            <td class="py-3 px-3 text-center">
                <input 
                    type="date" 
                    id="fecha_pago_${materialId}"
                    class="px-2 py-1 border border-gray-300 rounded text-xs font-medium text-purple-600 focus:outline-none focus:ring-2 focus:ring-purple-500 w-full"
                    data-original=""
                >
            </td>
            <td class="py-3 px-3 text-center">
                <input 
                    type="date" 
                    id="fecha_llegada_${materialId}"
                    class="px-2 py-1 border border-gray-300 rounded text-xs font-medium text-green-600 focus:outline-none focus:ring-2 focus:ring-green-500 w-full"
                    data-original=""
                >
            </td>
            <td class="py-3 px-3 text-center">
                <input 
                    type="date" 
                    id="fecha_despacho_${materialId}"
                    class="px-2 py-1 border border-gray-300 rounded text-xs font-medium text-orange-600 focus:outline-none focus:ring-2 focus:ring-orange-500 w-full"
                    data-original=""
                >
            </td>
            <td class="py-3 px-3 text-center">
                <span class="inline-block px-2 py-1 rounded text-xs font-semibold bg-gray-100 text-gray-600">-</span>
            </td>
            <td class="py-3 px-3 text-center">
                <button 
                    onclick="abrirModalObservaciones('${materialId}', '${nombreMaterial}')"
                    class="px-2 py-1 bg-blue-100 text-blue-600 font-medium rounded hover:bg-blue-200 transition text-sm flex items-center gap-1 justify-center"
                    title="Ver/Editar observaciones"
                >
                    <i class="fas fa-eye"></i>
                </button>
            </td>
            <td class="py-3 px-3 text-center">
                <button 
                    onclick="eliminarFilaMaterial('${materialId}')"
                    class="px-2 py-1 bg-red-100 text-red-600 font-medium rounded hover:bg-red-200 transition text-sm flex items-center gap-1 justify-center"
                    title="Eliminar"
                >
                    <i class="fas fa-trash-alt"></i>
                </button>
            </td>
        `;

        tbody.appendChild(row);
        showToast(`Material "${nombreMaterial}" agregado`, 'success');
    }

    /**
     * Elimina una fila de material del modal (elimina completamente)
     */
    function eliminarFilaMaterial(materialId) {
        const row = document.getElementById(`row_${materialId}`);
        const checkbox = document.getElementById(`checkbox_${materialId}`);
        
        if (row && checkbox) {
            // Obtener nombre del material
            const nombreMaterial = row.querySelector('td:first-child span').textContent.trim();
            const pedido = document.getElementById('modalPedido').textContent;
            
            // Mostrar confirmación
            Swal.fire({
                title: '¿Eliminar Material?',
                html: `<div style="text-align: left; margin: 20px 0;">
                    <p><strong>Material:</strong> ${nombreMaterial}</p>
                    <p style="color: #ef4444; margin-top: 15px;"><strong><i class="fas fa-exclamation-triangle"></i> Se eliminará este registro permanentemente.</strong></p>
                </div>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    const swalContainer = document.querySelector('.swal2-container');
                    if (swalContainer) {
                        swalContainer.style.zIndex = '10020';
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    // Eliminar del servidor
                    fetch(`/insumos/materiales/${pedido}/eliminar`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify({ 
                            nombre_material: nombreMaterial
                        }),
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Eliminar fila con animación
                            row.style.animation = 'slideOut 0.3s ease-out';
                            setTimeout(() => {
                                row.remove();
                                showToast('Material eliminado correctamente', 'success');
                            }, 300);
                        } else {
                            showToast('Error al eliminar: ' + data.message, 'error');
                        }
                    })
                    .catch(error => {
                        showToast('Error al eliminar el material', 'error');
                    });
                }
            });
        }
    }

    /**
     * Elimina un material (marca como eliminado)
     */
    function eliminarMaterial(materialId) {
        const checkbox = document.getElementById(`checkbox_${materialId}`);
        if (checkbox) {
            checkbox.checked = false;
            checkbox.style.opacity = '0.5';
        }
    }

    /**
     * Abre el modal de observaciones para un insumo
     */
    function abrirModalObservaciones(materialId, nombreMaterial) {
        // Mostrar el modal
        const modal = document.getElementById('observacionesModal');
        modal.style.display = 'flex';
        
        // Establecer el nombre del material
        document.getElementById('observacionesMaterial').textContent = nombreMaterial;
        
        // Guardar el materialId en un atributo data para usarlo al guardar
        modal.setAttribute('data-material-id', materialId);
        
        // Extraer el pedido del materialId
        // Formato: material_modal_${pedido}_${index}_${sanitizedMaterial}
        // O: material_${PEDIDO}_INDEX_NOMBRE
        let pedido = '';
        
        if (materialId.includes('material_modal_')) {
            // Nuevo formato: material_modal_45454_0_Tela
            const partes = materialId.split('_');
            if (partes.length >= 3) {
                pedido = partes[2]; // Índice 2 es el número de pedido
            }
        } else if (materialId.includes('material_')) {
            // Antiguo formato
            const partes = materialId.split('_');
            if (partes.length >= 2) {
                pedido = partes[1];
            }
        }
        
        // Guardar el pedido en un atributo data
        modal.setAttribute('data-pedido', pedido);
        
        // Obtener observaciones del input hidden
        const inputObservaciones = document.getElementById(`observaciones_${materialId}`);
        if (inputObservaciones) {
            document.getElementById('observacionesTexto').value = inputObservaciones.value;
        } else {
            document.getElementById('observacionesTexto').value = '';
        }
        
        // Enfocar el textarea
        document.getElementById('observacionesTexto').focus();
    }

    /**
     * Cierra el modal de observaciones
     */
    function cerrarModalObservaciones() {
        const modal = document.getElementById('observacionesModal');
        modal.style.display = 'none';
        document.getElementById('observacionesTexto').value = '';
        modal.removeAttribute('data-material-id');
    }

    /**
     * Guarda las observaciones del insumo directamente en la BD
     */
    function guardarObservaciones() {
        const modal = document.getElementById('observacionesModal');
        const materialId = modal.getAttribute('data-material-id');
        const pedido = modal.getAttribute('data-pedido');
        const observaciones = document.getElementById('observacionesTexto').value;
        
        if (!materialId) {
            showToast('Error: No se pudo identificar el material', 'error');
            return;
        }
        
        if (!pedido) {
            showToast('Error: No se pudo identificar el pedido', 'error');
            return;
        }
        
        // Guardar en el input hidden
        const inputObservaciones = document.getElementById(`observaciones_${materialId}`);
        if (inputObservaciones) {
            inputObservaciones.value = observaciones;
        }
        
        // Obtener el nombre del material
        const fila = document.getElementById(`row_${materialId}`);
        let nombreMaterial = '';
        if (fila) {
            const primeraColumna = fila.querySelector('td:first-child span');
            if (primeraColumna) {
                nombreMaterial = primeraColumna.textContent.trim();
            }
        }
        
        // Obtener el estado actual del checkbox
        const checkbox = fila ? fila.querySelector('input[type="checkbox"]') : null;
        const recibido = checkbox ? checkbox.checked : false;
        
        // Obtener todas las fechas
        const todosInputsFecha = fila ? fila.querySelectorAll('input[type="date"]') : [];
        const fechaOrden = todosInputsFecha[0]?.value || null;
        const fechaPedido = todosInputsFecha[1]?.value || null;
        const fechaPago = todosInputsFecha[2]?.value || null;
        const fechaLlegada = todosInputsFecha[3]?.value || null;
        const fechaDespacho = todosInputsFecha[4]?.value || null;
        
        // Enviar directamente al servidor
        fetch(`/insumos/materiales/${pedido}/guardar`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ 
                materiales: [{
                    nombre: nombreMaterial || `Material ${materialId}`,
                    fecha_orden: fechaOrden,
                    fecha_pedido: fechaPedido,
                    fecha_pago: fechaPago,
                    fecha_llegada: fechaLlegada,
                    fecha_despacho: fechaDespacho,
                    observaciones: observaciones || null,
                    recibido: recibido,
                }]
            }),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Observaciones guardadas correctamente', 'success');
                // Actualizar el input hidden para que se refleje en futuras aperturas
                const inputObservaciones = document.getElementById(`observaciones_${materialId}`);
                if (inputObservaciones) {
                    inputObservaciones.value = observaciones;
                }
                // Recargar los datos del modal para asegurar sincronización
                fetch(`/insumos/api/materiales/${pedido}`)
                    .then(response => response.json())
                    .then(fetchData => {
                        if (fetchData.materiales) {
                            llenarTablaInsumos(fetchData.materiales || []);
                        }
                    })
                    .catch(err => console.error('Error recargando datos:', err));
            } else {
                showToast('Error al guardar observaciones: ' + (data.message || ''), 'error');
            }
            cerrarModalObservaciones();
        })
        .catch(error => {
            showToast('Error al guardar observaciones: ' + error.message, 'error');
        });
    }

    /**
     * Guarda los cambios de insumos desde el modal
     */
    function guardarInsumosModal() {
        const pedido = document.getElementById('modalPedido').textContent;
        const materiales = [];
        
        // Recopilar todos los materiales del modal
        const tbody = document.getElementById('insumosTableBody');
        const filas = tbody.querySelectorAll('tr');
        
        filas.forEach((fila) => {
            const celdas = fila.querySelectorAll('td');
            
            // Obtener nombre del material
            const nombreMaterialEl = celdas[0];
            let nombreMaterial = nombreMaterialEl.textContent.trim();
            nombreMaterial = nombreMaterial.replace(/^[•●○◐◑\s]+/, '').trim();
            
            // Obtener checkbox y fechas
            const checkbox = fila.querySelector('input[type="checkbox"]');
            const todosInputsFecha = fila.querySelectorAll('input[type="date"]');
            const fechaOrdenInput = todosInputsFecha[0];
            const fechaPedidoInput = todosInputsFecha[1];
            const fechaPagoInput = todosInputsFecha[2];
            const fechaLlegadaInput = todosInputsFecha[3];
            const fechaDespachoInput = todosInputsFecha[4];
            
            const recibido = checkbox?.checked || false;
            const fechaOrden = fechaOrdenInput?.value || '';
            const fechaPedido = fechaPedidoInput?.value || '';
            const fechaPago = fechaPagoInput?.value || '';
            const fechaLlegada = fechaLlegadaInput?.value || '';
            const fechaDespacho = fechaDespachoInput?.value || '';
            
            // Obtener observaciones del input hidden
            const inputObservaciones = fila.querySelector(`input[type="hidden"][id^="observaciones_"]`);
            const observaciones = inputObservaciones ? inputObservaciones.value : '';
            // Agregar si está marcado o tiene fechas
            if (recibido || fechaOrden || fechaPedido || fechaPago || fechaLlegada || fechaDespacho || observaciones) {
                materiales.push({
                    nombre: nombreMaterial,
                    fecha_orden: fechaOrden || null,
                    fecha_pedido: fechaPedido || null,
                    fecha_pago: fechaPago || null,
                    fecha_llegada: fechaLlegada || null,
                    fecha_despacho: fechaDespacho || null,
                    recibido: recibido,
                    observaciones: observaciones || null,
                });
            }
        });
        // Enviar al servidor
        fetch(`/insumos/materiales/${pedido}/guardar`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ materiales }),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Materiales guardados correctamente', 'success');
            } else {
                showToast('Error al guardar', 'error');
            }
            cerrarModalInsumos();
        })
        .catch(error => {
            showToast('Error al guardar los materiales', 'error');
        });
    }
    /**
     * Cierra el modal al hacer clic fuera de él
     */
    document.getElementById('insumosModal').addEventListener('click', function(e) {
        if (e.target === this) {
            cerrarModalInsumos();
        }
    });

    /**
     * Event listener para checkboxes de materiales en el modal
     */
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('material-checkbox')) {
            const checkbox = e.target;
            const materialId = checkbox.id.replace('checkbox_', '');
            confirmarEliminacion(checkbox, materialId);
        }
        
        // Recalcular días de demora cuando cambian las fechas
        if (e.target.type === 'date') {
            const fila = e.target.closest('tr');
            if (fila) {
                actualizarDiasDemora(fila);
            }
        }
    });
    
    /**
     * Actualiza los días de demora en tiempo real
     */
    function actualizarDiasDemora(fila) {
        const todosInputsFecha = fila.querySelectorAll('input[type="date"]');
        const fechaPedido = todosInputsFecha[0]?.value;
        const fechaLlegada = todosInputsFecha[1]?.value;
        
        if (!fechaPedido || !fechaLlegada) {
            // Si falta alguna fecha, mostrar "-"
            const diasSpan = fila.querySelector('span[class*="bg-"]');
            if (diasSpan) {
                diasSpan.textContent = '-';
                diasSpan.className = 'inline-block px-3 py-1 rounded-full text-sm font-semibold bg-gray-100 text-gray-600';
            }
            return;
        }
        
        // Calcular días laborales (sin contar sábados, domingos)
        const fecha1 = new Date(fechaPedido);
        const fecha2 = new Date(fechaLlegada);
        
        let diasLaborales = 0;
        const fecha = new Date(fecha1);
        
        while (fecha <= fecha2) {
            const dia = fecha.getDay();
            // Si no es sábado (6) ni domingo (0)
            if (dia !== 0 && dia !== 6) {
                diasLaborales++;
            }
            fecha.setDate(fecha.getDate() + 1);
        }
        
        // Restar 1 porque no contamos el día de inicio
        diasLaborales = Math.max(0, diasLaborales - 1);
        
        // Actualizar el span de días de demora
        const diasSpan = fila.querySelector('span[class*="bg-"]');
        if (diasSpan) {
            let className = 'inline-block px-3 py-1 rounded-full text-sm font-semibold ';
            
            if (diasLaborales <= 0) {
                className += 'bg-green-100 text-green-800';
            } else if (diasLaborales <= 5) {
                className += 'bg-yellow-100 text-yellow-800';
            } else {
                className += 'bg-red-100 text-red-800';
            }
            
            diasSpan.textContent = diasLaborales + ' días';
            diasSpan.className = className;
        }
    }

    /**
     * Manejo de filtros en la tabla de órdenes con modal
     */
    let currentFilterColumn = null;
    let currentFilterValues = [];
    let selectedFilters = {};

    document.querySelectorAll('.filter-btn-insumos').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const column = this.getAttribute('data-column');
            currentFilterColumn = column;
            // Mostrar modal vacío (sin cargar valores aún)
            currentFilterValues = [];
            showFilterModal(column, []);
        });
    });

    function showFilterModal(column, values) {
        // Crear modal si no existe
        let modal = document.getElementById('filterModalInsumos');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'filterModalInsumos';
            modal.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0,0,0,0.5);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 9999;
            `;
            document.body.appendChild(modal);
        }
        
        const columnNames = {
            'pedido': 'Pedido',
            'cliente': 'Cliente',
            'estado': 'Estado',
            'area': 'Área',
            'fecha': 'Fecha',
            'fecha_de_creacion_de_orden': 'Fecha de Inicio'
        };

        // Valores predefinidos para ciertos filtros
        const predefinedValues = {
            'area': ['Corte', 'Creación de Orden'],
            'estado': ['En Ejecución', 'No iniciado', 'Entregado', 'Anulada', 'Pendiente Insumos']
        };

        // Usar valores predefinidos si existen, sino usar los de la tabla
        const displayValues = predefinedValues[column] || values;
        
        modal.innerHTML = `
            <div style="background: white; border-radius: 12px; padding: 24px; width: 90%; max-width: 500px; max-height: 80vh; overflow-y: auto; box-shadow: 0 20px 60px rgba(0,0,0,0.3);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3 style="margin: 0; font-size: 18px; font-weight: bold;">Filtrar Insumos por: ${columnNames[column] || column}</h3>
                    <button onclick="document.getElementById('filterModalInsumos').style.display='none'" style="background: none; border: none; font-size: 24px; cursor: pointer;">×</button>
                </div>
                
                <div style="display: flex; gap: 10px; margin-bottom: 20px; align-items: center;">
                    <input type="text" id="filterSearchInsumos" placeholder="Buscar valores..." style="flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px;">
                    <button onclick="applyFilters()" style="padding: 10px 20px; background: #10b981; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 14px; white-space: nowrap;">✓ Aplicar</button>
                    <button onclick="selectAllFilters()" class="filter-btn-tooltip" data-tooltip="Marcar todos" style="padding: 10px 12px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 14px; box-shadow: 0 2px 4px rgba(102, 126, 234, 0.3);">
                        <i class="fas fa-check-double"></i>
                    </button>
                    <button onclick="deselectAllFilters()" class="filter-btn-tooltip" data-tooltip="Desmarcar todos" style="padding: 10px 12px; background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%); color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 14px; box-shadow: 0 2px 4px rgba(59, 130, 246, 0.3);">
                        <i class="fas fa-times-circle"></i>
                    </button>
                </div>
                
                <div id="filterListInsumos" style="max-height: 400px; overflow-y: auto; border: 1px solid #e5e7eb; border-radius: 6px; padding: 10px;">
                    <p style="text-align: center; color: #999; padding: 20px;">Escribe para buscar valores...</p>
                </div>
            </div>
        `;
        
        modal.style.display = 'flex';
        
        // Agregar tooltips a los botones
        setTimeout(() => {
            document.querySelectorAll('.filter-btn-tooltip').forEach(btn => {
                btn.addEventListener('mouseenter', function() {
                    const tooltip = this.getAttribute('data-tooltip');
                    const rect = this.getBoundingClientRect();
                    
                    // Crear tooltip
                    const tooltipEl = document.createElement('div');
                    tooltipEl.textContent = tooltip;
                    tooltipEl.style.cssText = `
                        position: fixed;
                        top: ${rect.top - 40}px;
                        left: ${rect.left + rect.width / 2}px;
                        transform: translateX(-50%);
                        background: #333;
                        color: white;
                        padding: 8px 12px;
                        border-radius: 6px;
                        font-size: 12px;
                        white-space: nowrap;
                        z-index: 10000;
                        pointer-events: none;
                    `;
                    document.body.appendChild(tooltipEl);
                    
                    // Remover tooltip al salir
                    const removeTooltip = () => {
                        tooltipEl.remove();
                        this.removeEventListener('mouseleave', removeTooltip);
                    };
                    this.addEventListener('mouseleave', removeTooltip);
                });
            });
        }, 100);
        
        // Cargar valores al abrir el modal
        let allValuesLoaded = false;
        let allValues = [];
        // Mostrar mensaje de carga
        const filterList = document.getElementById('filterListInsumos');
        filterList.innerHTML = '<p style="text-align: center; color: #999; padding: 20px;">Cargando...</p>';
        
        // Obtener valores del backend
        fetch(`/insumos/api/filtros/${column}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    allValues = data.valores;
                    allValuesLoaded = true;
                    // Renderizar primeros 15 valores
                    renderFilterValues(allValues, '', column);
                } else {
                    filterList.innerHTML = '<p style="text-align: center; color: #f00; padding: 20px;">Error al cargar valores</p>';
                }
            })
            .catch(error => {
                filterList.innerHTML = '<p style="text-align: center; color: #f00; padding: 20px;">Error al cargar valores</p>';
            });
        
        // Agregar búsqueda
        document.getElementById('filterSearchInsumos').addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            
            // Si ya tenemos los valores, filtrar
            if (allValuesLoaded) {
                renderFilterValues(allValues, searchTerm, column);
            }
        });
    }
    
    function renderFilterValues(values, searchTerm, column) {
        const filterList = document.getElementById('filterListInsumos');
        const urlParams = new URLSearchParams(window.location.search);
        const filterColumns = urlParams.getAll('filter_columns[]') || [];
        const filterValuesArray = urlParams.getAll('filter_values[]') || [];
        
        // Filtrar valores según búsqueda
        let filteredValues = values.filter(val => {
            // Convertir a string si no lo es
            const valStr = String(val || '').trim();
            return valStr.length > 0 && valStr.toLowerCase().includes(searchTerm.toLowerCase());
        });
        
        if (filteredValues.length === 0) {
            filterList.innerHTML = '<p style="text-align: center; color: #999; padding: 20px;">No se encontraron resultados</p>';
            return;
        }
        
        // Si no hay búsqueda, mostrar solo los primeros 15
        const displayValues = searchTerm === '' ? filteredValues.slice(0, 15) : filteredValues;
        
        // Mostrar información de cuántos valores hay
        let totalText = '';
        if (searchTerm === '' && filteredValues.length > 15) {
            totalText = `<p style="text-align: center; color: #666; padding: 10px; font-size: 12px;">Mostrando ${Math.min(15, filteredValues.length)} de ${filteredValues.length} valores. Busca para ver más.</p>`;
        }
        
        // Renderizar checkboxes
        filterList.innerHTML = totalText + displayValues.map(val => {
            // Convertir a string
            const valStr = String(val || '').trim();
            
            // Buscar si este valor está en los filtros del MISMO TIPO DE COLUMNA
            let isChecked = false;
            filterColumns.forEach((col, idx) => {
                if (col === column && filterValuesArray[idx] === valStr) {
                    isChecked = true;
                }
            });
            
            return `
                <label style="display: flex; align-items: center; padding: 10px; cursor: pointer; border-radius: 4px; transition: background 0.2s; hover: background-color: #f3f4f6;">
                    <input type="checkbox" value="${valStr}" class="filter-checkbox" ${isChecked ? 'checked' : ''} style="margin-right: 10px; cursor: pointer;">
                    <span style="flex: 1;">${valStr}</span>
                </label>
            `;
        }).join('');
    }

    function selectAllFilters() {
        document.querySelectorAll('.filter-checkbox').forEach(cb => cb.checked = true);
    }

    function deselectAllFilters() {
        document.querySelectorAll('.filter-checkbox').forEach(cb => cb.checked = false);
    }

    function clearAllFilters() {
        // Mostrar todas las filas
        document.querySelectorAll('table tbody tr').forEach(row => row.style.display = '');
        document.getElementById('filterModalInsumos').style.display = 'none';
    }

    function clearAllTableFilters() {
        // Redirigir a la página sin filtros
        window.location.href = '{{ route("insumos.materiales.index") }}';
    }

    function applyFilters() {
        const selected = Array.from(document.querySelectorAll('.filter-checkbox:checked')).map(cb => cb.value);
        if (selected.length === 0) {
            // Si no hay selección, ir a la página sin filtros
            window.location.href = '{{ route("insumos.materiales.index") }}';
        } else {
            // Obtener filtros existentes de la URL
            const urlParams = new URLSearchParams(window.location.search);
            const existingFilters = {};
            
            // Recopilar filtros existentes
            const filterColumns = urlParams.getAll('filter_columns[]') || [];
            const filterValuesArray = urlParams.getAll('filter_values[]') || [];
            // Reconstruir objeto de filtros existentes
            filterColumns.forEach((col, idx) => {
                if (!existingFilters[col]) {
                    existingFilters[col] = [];
                }
                if (filterValuesArray[idx]) {
                    existingFilters[col].push(filterValuesArray[idx]);
                }
            });
            
            // Agregar o actualizar el filtro actual
            existingFilters[currentFilterColumn] = selected;
            // Construir URL con todos los filtros
            const filterParams = new URLSearchParams();
            Object.keys(existingFilters).forEach(column => {
                filterParams.append('filter_columns[]', column);
                existingFilters[column].forEach(value => {
                    filterParams.append('filter_values[]', value);
                });
            });
            
            const finalUrl = `{{ route("insumos.materiales.index") }}?${filterParams.toString()}`;
            window.location.href = finalUrl;
        }
        
        document.getElementById('filterModalInsumos').style.display = 'none';
    }

    // Cerrar modal al hacer clic fuera
    document.addEventListener('click', function(e) {
        const modal = document.getElementById('filterModalInsumos');
        if (modal && e.target === modal) {
            modal.style.display = 'none';
        }
    });
    
    /**
     * Envía el pedido a producción
     */
    function cambiarEstadoPedido(numeroPedido, estadoActual) {
        // Si el estado es "Pendiente" o "PENDIENTE_INSUMOS", enviar a producción (No iniciado)
        if (estadoActual.toLowerCase() === 'pendiente' || estadoActual === 'PENDIENTE_INSUMOS') {
            // Guardar el número de pedido en una variable global
            window.pedidoParaProduccion = numeroPedido;
            
            // Mostrar el modal
            document.getElementById('numeroPedidoConfirm').textContent = numeroPedido;
            document.getElementById('modalConfirmarProduccion').style.display = 'flex';
        } else {
            showToast('Este pedido ya ha sido enviado a producción', 'info');
        }
    }
    
    /**
     * Cierra el modal de confirmación
     */
    function cerrarModalConfirmarProduccion() {
        document.getElementById('modalConfirmarProduccion').style.display = 'none';
        window.pedidoParaProduccion = null;
    }
    
    /**
     * Confirma el envío a producción
     */
    function confirmarEnvioProduccion() {
        const numeroPedido = window.pedidoParaProduccion;
        if (!numeroPedido) return;
        
        const proximoEstado = 'En Ejecución';
        
        // Mostrar loading overlay
        document.getElementById('loadingOverlay').classList.add('active');
        
        // Enviar petición al servidor
        fetch(`/insumos/materiales/${numeroPedido}/cambiar-estado`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ 
                estado: proximoEstado
            }),
        })
        .then(response => response.json())
        .then(data => {
            // Ocultar loading overlay
            document.getElementById('loadingOverlay').classList.remove('active');
            
            if (data.success) {
                cerrarModalConfirmarProduccion();
                
                // Mostrar mensaje mejorado con información de procesos
                let mensaje = `Pedido aprobado correctamente. Estado: En Ejecución, Área: Corte`;
                if (data.procesos_creados > 0) {
                    mensaje += `\n Se crearon ${data.procesos_creados} procesos automáticamente`;
                    
                    // Mostrar detalles de procesos si están disponibles
                    if (data.detalles_procesos && data.detalles_procesos.length > 0) {
                        mensaje += '\n\n Procesos creados:';
                        data.detalles_procesos.forEach((proceso, index) => {
                            mensaje += `\n   ${index + 1}. ${proceso}`;
                        });
                    }
                }
                
                showToast(mensaje, 'success');
                
                // Recargar la página después de 2 segundos para dar tiempo de leer el mensaje
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            } else {
                showToast('Error al cambiar el estado: ' + (data.message || ''), 'error');
            }
        })
        .catch(error => {
            // Ocultar loading overlay
            document.getElementById('loadingOverlay').classList.remove('active');
            showToast('Error al cambiar el estado', 'error');
        });
    }
    
    console.timeEnd('RENDER_TOTAL');
    console.log(` Total de órdenes: {{ $ordenes->total() }}`);
    
    // Mostrar indicador de carga cuando se hace clic en paginación
    document.querySelectorAll('.pagination-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            if (!this.disabled) {
                document.getElementById('loadingOverlay').classList.add('active');
            }
        });
    });
</script>

<!-- Scripts para el modal de órdenes -->
<script src="{{ asset('js/ordersjs/order-detail-modal-manager.js') }}"></script>
<script src="{{ asset('js/asesores/pedidos-detail-modal.js') }}"></script>
<!-- Image Gallery para mostrar fotos en el modal -->
<script src="{{ asset('js/orders-scripts/image-gallery-zoom.js') }}"></script>
<script src="{{ asset('js/insumos/pagination.js') }}"></script>

<!-- Scripts para Dropdown de Ver Pedido -->
<script src="{{ asset('js/asesores/pedidos-dropdown-simple.js') }}"></script>
<!-- Scripts para Vista de Factura desde Lista - Lazy Loading -->
<script src="{{ asset('js/modulos/invoice/InvoiceLazyLoader.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/asesores/invoice-from-list.js') }}"></script>
<script src="{{ asset('js/asesores/receipt-manager.js') }}"></script>
<script src="{{ asset('js/insumos/insumos-galeria.js') }}"></script>

<!-- Scripts para Recibos/Procesos -->
<script type="module" src="{{ asset('js/modulos/pedidos-recibos/loader.js') }}"></script>

<!-- Script para activar dropdowns en insumos -->
<script>
    let dropdownAbierto = {};
    
    document.addEventListener('DOMContentLoaded', function() {
        console.log('[Insumos Dropdowns] DOMContentLoaded iniciado');
        console.log('[Insumos Dropdowns] Buscando botones btn-ver-dropdown...');
        
        const botones = document.querySelectorAll('.btn-ver-dropdown');
        console.log(`[Insumos Dropdowns] Encontrados ${botones.length} botones`);
        
        // Esperar un momento para asegurar que todo esté cargado
        setTimeout(() => {
            // Cuando se haga clic en cualquier botón btn-ver-dropdown, abrir el dropdown
            document.addEventListener('click', function(e) {
                const btnVerDropdown = e.target.closest('.btn-ver-dropdown');
                if (btnVerDropdown) {
                    console.log('[Insumos Dropdowns] Clic en botón Ver');
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const menuId = btnVerDropdown.getAttribute('data-menu-id');
                    console.log(`[Insumos Dropdowns] menuId: ${menuId}`);
                    
                    // Crear el dropdown si no existe
                    let dropdown = document.getElementById(menuId);
                    console.log(`[Insumos Dropdowns] Dropdown existe: ${dropdown !== null}`);
                    
                    if (!dropdown) {
                        console.log(`[Insumos Dropdowns] Creando dropdown ${menuId}...`);
                        // Usar la función crearDropdownVer del script pedidos-dropdown-simple.js
                        if (typeof crearDropdownVer === 'function') {
                            console.log('[Insumos Dropdowns] Función crearDropdownVer disponible');
                            // Llamar a la función interna
                            dropdown = crearDropdownVer(btnVerDropdown);
                            console.log(`[Insumos Dropdowns] Dropdown creado: ${dropdown !== null}`);
                            dropdownAbierto[menuId] = false; // Inicializar estado
                        } else {
                            console.error('[Insumos Dropdowns] Función crearDropdownVer NO disponible');
                        }
                    }
                    
                    if (dropdown) {
                        console.log(`[Insumos Dropdowns] Estado actual: ${dropdownAbierto[menuId] ? 'ABIERTO' : 'CERRADO'}`);
                        
                        // Toggle del dropdown actual
                        if (!dropdownAbierto[menuId]) {
                            // Posicionar el dropdown cerca del botón
                            const rect = btnVerDropdown.getBoundingClientRect();
                            dropdown.style.top = (rect.bottom + 5) + 'px';
                            dropdown.style.left = (rect.left) + 'px';
                            dropdown.style.display = 'block';
                            dropdown.style.pointerEvents = 'auto';
                            dropdownAbierto[menuId] = true;
                            console.log('[Insumos Dropdowns] Dropdown abierto');
                        } else {
                            dropdown.style.display = 'none';
                            dropdown.style.pointerEvents = 'none';
                            dropdownAbierto[menuId] = false;
                            console.log('[Insumos Dropdowns] Dropdown cerrado');
                        }
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
        }, 100); // Pequeño retraso para asegurar que el DOM esté completamente cargado
    });
    
    /**
     * Función para abrir selector de recibos
     * Primero muestra la lista de prendas
     */
    function abrirSelectorRecibos(pedidoId) {
        console.log('[abrirSelectorRecibos] Cargando lista de prendas con pedidoId:', pedidoId);
        
        // Cargar datos del pedido
        fetch(`/pedidos-public/${pedidoId}/recibos-datos`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(datos => {
            console.log('[abrirSelectorRecibos] Datos recibidos:', datos);
            
            // Determinar dónde están los datos reales
            const datosReales = datos.data || datos;
            
            if (datosReales.prendas && datosReales.prendas.length > 0) {
                // Mostrar selector de prendas
                mostrarSelectorDePrendas(datosReales, pedidoId);
            } else {
                console.error('[abrirSelectorRecibos] No se encontraron prendas');
            }
        })
        .catch(error => {
            console.error('[abrirRecibos] Error al cargar datos:', error);
        });
    }
    
    /**
     * Muestra el modal con la lista de prendas para seleccionar
     */
    function mostrarSelectorDePrendas(datos, pedidoId) {
        console.log('[mostrarSelectorDePrendas] Mostrando lista de prendas');
        
        // Crear un modal completamente separado
        const modal = document.createElement('div');
        modal.id = 'selector-prendas-modal';
        modal.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 99999;
            padding: 1rem;
        `;
        
        const modalContent = document.createElement('div');
        modalContent.style.cssText = `
            background: white;
            border-radius: 12px;
            padding: 2rem;
            max-width: 600px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        `;
        
        modalContent.innerHTML = `
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h2 style="margin: 0; font-size: 1.5rem; font-weight: bold; color: #1f2937;">
                    Seleccionar Prenda - Pedido ${datos.numero_pedido}
                </h2>
                <button onclick="cerrarSelectorPrendas()" style="
                    background: none;
                    border: none;
                    font-size: 1.5rem;
                    cursor: pointer;
                    color: #6b7280;
                    padding: 0.5rem;
                    border-radius: 0.375rem;
                    transition: all 0.2s ease;
                " onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background='none'">
                    ×
                </button>
            </div>
            
            <div style="margin-bottom: 1.5rem; color: #6b7280;">
                Cliente: ${datos.cliente || 'N/A'} | Asesor: ${datos.asesor || datos.asesora || 'N/A'}
            </div>
            
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                ${datos.prendas.map((prenda, index) => `
                    <button onclick="seleccionarPrendaRecibo('${pedidoId}', ${index})" style="
                        background: white;
                        border: 2px solid #e5e7eb;
                        border-radius: 8px;
                        padding: 1.5rem;
                        text-align: left;
                        cursor: pointer;
                        transition: all 0.2s ease;
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                    " onmouseover="this.style.background='#f9fafb'; this.style.borderColor='#3b82f6'" onmouseout="this.style.background='white'; this.style.borderColor='#e5e7eb'">
                        <div>
                            <div style="font-weight: 700; color: #1f2937; margin-bottom: 0.5rem; font-size: 1.125rem;">
                                ${prenda.nombre || 'Prenda sin nombre'}
                            </div>
                            <div style="font-size: 0.875rem; color: #6b7280; margin-bottom: 0.25rem;">
                                ${prenda.descripcion || 'Sin descripción'}
                            </div>
                            <div style="font-size: 0.75rem; color: #9ca3af;">
                                Cantidad: ${prenda.cantidad || 'N/A'}
                            </div>
                        </div>
                        <div style="background: #3b82f6; color: white; padding: 0.75rem 1.5rem; border-radius: 8px; font-weight: 700;">
                            Ver Recibo
                        </div>
                    </button>
                `).join('')}
            </div>
        `;
        
        modal.appendChild(modalContent);
        document.body.appendChild(modal);
        
        // Cerrar al hacer clic fuera
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                cerrarSelectorPrendas();
            }
        });
        
        // Guardar datos para usarlos después
        window.datosSelectorPrendas = datos;
        window.pedidoIdSelector = pedidoId;
    }
    
    /**
     * Actualiza los datos básicos del modal
     */
    function actualizarDatosBasicosModal(modalContainer, datos) {
        // Asesor
        const asesoraElement = modalContainer.querySelector('#asesora-value');
        if (asesoraElement) {
            asesoraElement.textContent = datos.asesor || datos.asesora || 'N/A';
        }
        
        // Forma de pago
        const formaPagoElement = modalContainer.querySelector('#forma-pago-value');
        if (formaPagoElement) {
            formaPagoElement.textContent = datos.forma_de_pago || 'N/A';
        }
        
        // Cliente
        const clienteElement = modalContainer.querySelector('#cliente-value');
        if (clienteElement) {
            clienteElement.textContent = datos.cliente || 'N/A';
        }
        
        // Pedido
        const pedidoElement = modalContainer.querySelector('.pedido-number');
        if (pedidoElement) {
            pedidoElement.textContent = datos.numero_pedido;
        }
        
        // Fecha actual
        const now = new Date();
        const dayElement = modalContainer.querySelector('.day-box');
        const monthElement = modalContainer.querySelector('.month-box');
        const yearElement = modalContainer.querySelector('.year-box');
        
        if (dayElement) dayElement.textContent = now.getDate().toString().padStart(2, '0');
        if (monthElement) monthElement.textContent = (now.getMonth() + 1).toString().padStart(2, '0');
        if (yearElement) yearElement.textContent = now.getFullYear().toString();
    }
    
    /**
     * Selecciona una prenda y abre su recibo
     * Usa el sistema de recibos que ya funciona
     */
    function seleccionarPrendaRecibo(pedidoId, prendaIndex) {
        console.log('[seleccionarPrendaRecibo] Seleccionada prenda:', prendaIndex);
        
        // Cerrar selector
        cerrarSelectorPrendas();
        
        // Usar el sistema de recibos que ya funciona
        if (typeof verRecibosDelPedido === 'function') {
            verRecibosDelPedido(null, pedidoId, prendaIndex);
        } else {
            console.error('[seleccionarPrendaRecibo] verRecibosDelPedido no está disponible');
        }
    }
    
    /**
     * Carga el módulo PedidosRecibosModule usando el loader existente
     */
    function cargarPedidosRecibosModule(callback) {
        const script = document.createElement('script');
        script.src = '/js/modulos/pedidos-recibos/loader.js';
        script.onload = callback;
        script.onerror = () => {
            console.error('[cargarPedidosRecibosModule] Error al cargar el loader');
        };
        document.head.appendChild(script);
    }
    
    /**
     * Cierra el selector de prendas
     */
    function cerrarSelectorPrendas() {
        const modal = document.getElementById('selector-prendas-modal');
        if (modal) {
            modal.remove();
        }
        
        // Limpiar datos
        window.datosSelectorPrendas = null;
        window.pedidoIdSelector = null;
    }
</script>
@endsection

