{{-- resources/views/insumos/materiales/index.blade.php --}}
@extends('insumos.layout')

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
</style>

@if(app()->isLocal())
<script>
    console.log('📄 Vista materiales: Inicio carga');
    console.time('RENDER_TOTAL');
</script>
@endif

<!-- Lazy Loading Script -->
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
            console.error('No se encontraron elementos para:', materialId);
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
<div id="toastContainer" style="position: fixed; top: 20px; right: 20px; z-index: 10000; display: flex; flex-direction: column; gap: 10px;"></div>

{{-- Loading Overlay --}}
<div id="loadingOverlay" class="loading-overlay">
    <div class="loading-spinner"></div>
</div>

<div class="min-h-screen bg-gray-50 m-0 p-0">
    {{-- Header Principal Blanco --}}
    <div class="bg-white border-b border-gray-200 shadow-sm w-full m-0">
        <div class="px-6 py-6">
            {{-- Título y Descripción --}}
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-gray-900 flex items-center gap-3">
                    <span class="material-symbols-rounded text-4xl text-blue-600">inventory_2</span>
                    Control de Insumos del Pedido
                </h1>
                <p class="text-gray-600 text-sm mt-2">Gestiona y controla los insumos de tus pedidos en tiempo real</p>
            </div>

            {{-- Buscador Mejorado --}}
            <form action="{{ route('insumos.materiales.index') }}" method="GET" class="flex gap-3 items-end">
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
                    <button type="button" onclick="clearAllTableFilters()" class="px-6 py-3 bg-orange-500 text-white font-semibold rounded-lg hover:bg-orange-600 transition shadow-sm flex items-center gap-2 whitespace-nowrap" title="Limpiar todos los filtros">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                        </svg>
                        Limpiar Filtros
                    </button>
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

    <div class="px-6 py-8 w-full">
        {{-- Tabla Principal de Órdenes --}}
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div>
                <table class="w-full" style="font-size: 0.75em;">
                    <thead>
                        <tr class="bg-gradient-to-r from-blue-600 to-blue-700 text-white">
                            <th class="text-left py-4 px-6 font-bold">
                                <div class="flex items-center justify-between gap-2">
                                    <span>Pedido</span>
                                    <button class="filter-btn-insumos hover:bg-blue-500 p-1 rounded transition" data-column="pedido" title="Filtrar por Pedido">
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
                            <th class="text-left py-4 px-6 font-bold">
                                <div class="flex items-center justify-between gap-2">
                                    <span>Descripción</span>
                                    <button class="filter-btn-insumos hover:bg-blue-500 p-1 rounded transition" data-column="descripcion" title="Filtrar por Descripción">
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
                            <th class="text-center py-4 px-6 font-bold">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($ordenes ?? [] as $orden)
                            <tr class="border-b border-gray-200 hover:bg-gray-50 transition" data-pedido="{{ strtoupper($orden->pedido ?? '') }}" data-cliente="{{ strtoupper($orden->cliente ?? '') }}" data-orden-pedido="{{ $orden->pedido }}">
                                <td class="py-4 px-6">
                                    <span class="font-bold text-blue-600 text-lg">{{ $orden->pedido ?? 'N/A' }}</span>
                                </td>
                                <td class="py-4 px-6">
                                    <span class="font-medium text-gray-800">{{ $orden->cliente ?? 'N/A' }}</span>
                                </td>
                                <td class="py-4 px-6">
                                    <span class="text-gray-600 text-sm cursor-pointer hover:text-blue-600 transition" onclick="abrirModalDescripcion('{{ $orden->pedido }}', {{ json_encode($orden->descripcion ?? '') }})">
                                        {{ Str::limit($orden->descripcion ?? '', 50) }}
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-center">
                                    @php
                                        $estadoClass = '';
                                        $estadoColor = '';
                                        if ($orden->estado === 'No iniciado') {
                                            $estadoClass = 'bg-gray-100 text-gray-800';
                                        } elseif ($orden->estado === 'En Ejecución') {
                                            $estadoClass = 'bg-blue-100 text-blue-800';
                                        }
                                    @endphp
                                    <span class="inline-block px-3 py-1 rounded-full text-sm font-semibold {{ $estadoClass }}">
                                        {{ $orden->estado ?? 'N/A' }}
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-center">
                                    @php
                                        $areaClass = '';
                                        if ($orden->area === 'Corte') {
                                            $areaClass = 'bg-purple-100 text-purple-800';
                                        } elseif ($orden->area === 'Creación de orden') {
                                            $areaClass = 'bg-green-100 text-green-800';
                                        }
                                    @endphp
                                    <span class="inline-block px-3 py-1 rounded-full text-sm font-semibold {{ $areaClass }}">
                                        {{ $orden->area ?? 'N/A' }}
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-center">
                                    <span class="text-gray-600 text-sm">
                                        {{ $orden->fecha_de_creacion_de_orden ? \Carbon\Carbon::parse($orden->fecha_de_creacion_de_orden)->subHours(5)->format('d/m/Y') : 'N/A' }}
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <button 
                                            class="btn-ver-orden px-3 py-1 bg-blue-100 text-blue-600 font-medium rounded hover:bg-blue-200 transition text-sm flex items-center gap-1"
                                            data-orden="{{ json_encode($orden) }}"
                                            title="Ver orden"
                                        >
                                            <i class="fas fa-eye"></i> Ver
                                        </button>
                                        <button 
                                            class="btn-ver-insumos px-3 py-1 bg-green-100 text-green-600 font-medium rounded hover:bg-green-200 transition text-sm flex items-center gap-1"
                                            onclick="abrirModalInsumos('{{ $orden->pedido }}')"
                                            title="Ver insumos"
                                        >
                                            <i class="fas fa-box"></i> Insumos
                                        </button>
                                    </div>
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
     * Mostrar Toast Notification
     */
    function showToast(message, type = 'success', duration = 3000) {
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
        toast.className = `${bgColor} text-white px-6 py-3 rounded-lg shadow-lg flex items-center gap-3`;
        toast.style.animation = 'slideIn 0.3s ease-out';
        toast.innerHTML = `<span>${message}</span>`;
        
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
            console.error('Error:', error);
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
        
        console.log('🔵 Guardando materiales para pedido:', ordenPedido);
        console.log('📍 Checkboxes encontrados:', checkboxes.length);
        console.log('🔍 Buscando en:', `input[type="checkbox"][id^="checkbox_"]`);
        
        // Debug: mostrar todos los checkboxes de la página
        const todosCheckboxes = document.querySelectorAll('input[type="checkbox"]');
        console.log('📊 Total de checkboxes en la página:', todosCheckboxes.length);
        todosCheckboxes.forEach((cb, i) => {
            console.log(`  ${i}: id=${cb.id}, checked=${cb.checked}`);
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
            
            console.log(`📦 Material ${index}: ${nombreMaterial}`, { 
                recibido, 
                originalCheckbox,
                checkboxCambio,
                fechaPedido, 
                originalFechaPedido,
                fechaPedidoCambio,
                fechaLlegada,
                originalFechaLlegada, 
                fechaLlegadaCambio,
                hayChangios 
            });
            
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
        
        console.log('📋 Materiales a guardar:', materiales);
        
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
            console.log('✅ Respuesta servidor:', data);
            if (data.success) {
                showToast('Guardado exitoso', 'success');
            } else {
                showToast('Guardado exitoso', 'success');
            }
        })
        .catch(error => {
            console.error('Error completo:', error);
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
<div class="order-detail-modal">
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
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-100 border-b-2 border-gray-300">
                            <th class="text-left py-3 px-4 font-bold text-gray-800">Insumo</th>
                            <th class="text-center py-3 px-4 font-bold text-gray-800">Estado</th>
                            <th class="text-center py-3 px-4 font-bold text-gray-800">Fecha Pedido</th>
                            <th class="text-center py-3 px-4 font-bold text-gray-800">Fecha Llegada</th>
                            <th class="text-center py-3 px-4 font-bold text-gray-800">Días Demora</th>
                            <th class="text-center py-3 px-4 font-bold text-gray-800">Acciones</th>
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

{{-- Modal para ver descripción completa --}}
<div id="descripcionModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center" style="display: none; z-index: 10001;">
    <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4" style="z-index: 10002;">
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white p-6 flex justify-between items-center">
            <h2 class="text-2xl font-bold">Descripción Completa</h2>
            <button onclick="cerrarModalDescripcion()" class="text-white hover:text-gray-200 transition">
                <i class="fas fa-times text-2xl"></i>
            </button>
        </div>
        <div class="p-6">
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Pedido:</label>
                <p id="descripcionPedido" class="text-gray-600 font-medium"></p>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Descripción:</label>
                <textarea id="descripcionTexto" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none" rows="6" readonly></textarea>
            </div>
            <div class="flex gap-3 justify-end">
                <button onclick="cerrarModalDescripcion()" class="px-6 py-2 bg-gray-300 text-gray-700 font-semibold rounded-lg hover:bg-gray-400 transition">
                    Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    /**
     * Abre el modal con los detalles de la orden
     */
    function abrirModalOrden(orden) {
        // Actualizar datos del modal
        document.querySelector('.day-box').textContent = '';
        document.querySelector('.month-box').textContent = '';
        document.querySelector('.year-box').textContent = '';
        document.getElementById('order-pedido').textContent = orden.pedido || 'N/A';
        document.getElementById('asesora-value').textContent = orden.asesora || 'N/A';
        document.getElementById('forma-pago-value').textContent = orden.forma_pago || 'N/A';
        document.getElementById('cliente-value').textContent = orden.cliente || 'N/A';
        document.getElementById('descripcion-text').textContent = orden.descripcion || 'N/A';
        document.getElementById('encargado-value').textContent = orden.encargado || 'N/A';
        document.getElementById('prendas-entregadas-value').textContent = orden.prendas_entregadas || 'N/A';

        // Llenar fecha
        if (orden.fecha_de_creacion_de_orden) {
            const fecha = new Date(orden.fecha_de_creacion_de_orden);
            document.querySelector('.day-box').textContent = String(fecha.getDate()).padStart(2, '0');
            document.querySelector('.month-box').textContent = String(fecha.getMonth() + 1).padStart(2, '0');
            document.querySelector('.year-box').textContent = fecha.getFullYear();
        }

        // Abrir modal usando el evento de Alpine
        window.dispatchEvent(new CustomEvent('open-modal', {
            detail: 'order-detail'
        }));
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
                console.error('Error al cargar insumos:', error);
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
            <td class="py-4 px-4 font-medium text-gray-900">
                <div class="flex items-center gap-3">
                    <div class="w-3 h-3 rounded-full ${colorPunto}"></div>
                    <span>${nombreMaterial}</span>
                </div>
            </td>
            <td class="py-4 px-4 text-center">
                <input 
                    type="checkbox" 
                    id="checkbox_${materialId}"
                    class="w-6 h-6 cursor-pointer material-checkbox accent-green-500"
                    ${materialData.recibido ? 'checked' : ''}
                    data-original="${materialData.recibido ? 'true' : 'false'}"
                >
            </td>
            <td class="py-4 px-4 text-center">
                <input 
                    type="date" 
                    id="fecha_pedido_${materialId}"
                    class="px-2 py-1 border border-gray-300 rounded text-sm font-medium text-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    value="${materialData.fecha_pedido ? materialData.fecha_pedido : ''}"
                    data-original="${materialData.fecha_pedido ? materialData.fecha_pedido : ''}"
                >
            </td>
            <td class="py-4 px-4 text-center">
                <input 
                    type="date" 
                    id="fecha_llegada_${materialId}"
                    class="px-2 py-1 border border-gray-300 rounded text-sm font-medium text-green-600 focus:outline-none focus:ring-2 focus:ring-green-500"
                    value="${materialData.fecha_llegada ? materialData.fecha_llegada : ''}"
                    data-original="${materialData.fecha_llegada ? materialData.fecha_llegada : ''}"
                >
            </td>
            <td class="py-4 px-4 text-center">
                <span class="inline-block px-3 py-1 rounded-full text-sm font-semibold bg-gray-100 text-gray-600 flex items-center justify-center gap-1">
                    ${materialData.dias_demora !== null && materialData.dias_demora !== undefined ? 
                        (materialData.dias_demora <= 0 ? '<i class="fas fa-check text-green-600"></i>' : 
                         materialData.dias_demora <= 5 ? '<i class="fas fa-exclamation-triangle text-yellow-600"></i>' : 
                         '<i class="fas fa-times text-red-600"></i>') + 
                        materialData.dias_demora + ' días' 
                        : '-'}
                </span>
            </td>
            <td class="py-4 px-4 text-center">
                <button 
                    onclick="eliminarFilaMaterial('${materialId}')"
                    class="px-3 py-1 bg-red-100 text-red-600 font-medium rounded hover:bg-red-200 transition text-sm flex items-center gap-1 justify-center"
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

        row.innerHTML = `
            <td class="py-4 px-4 font-medium text-gray-900">
                <div class="flex items-center gap-3">
                    <div class="w-3 h-3 rounded-full ${colorPunto}"></div>
                    <span>${nombreMaterial}</span>
                </div>
            </td>
            <td class="py-4 px-4 text-center">
                <input 
                    type="checkbox" 
                    id="checkbox_${materialId}"
                    class="w-6 h-6 cursor-pointer material-checkbox accent-green-500"
                    data-original="false"
                >
            </td>
            <td class="py-4 px-4 text-center">
                <input 
                    type="date" 
                    id="fecha_pedido_${materialId}"
                    class="px-2 py-1 border border-gray-300 rounded text-sm font-medium text-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    data-original=""
                >
            </td>
            <td class="py-4 px-4 text-center">
                <input 
                    type="date" 
                    id="fecha_llegada_${materialId}"
                    class="px-2 py-1 border border-gray-300 rounded text-sm font-medium text-green-600 focus:outline-none focus:ring-2 focus:ring-green-500"
                    data-original=""
                >
            </td>
            <td class="py-4 px-4 text-center">
                <span class="inline-block px-3 py-1 rounded-full text-sm font-semibold bg-gray-100 text-gray-600">-</span>
            </td>
            <td class="py-4 px-4 text-center">
                <button 
                    onclick="eliminarFilaMaterial('${materialId}')"
                    class="px-3 py-1 bg-red-100 text-red-600 font-medium rounded hover:bg-red-200 transition text-sm flex items-center gap-1 justify-center"
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
                        console.error('Error:', error);
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
            const fechaPedidoInput = todosInputsFecha[0];
            const fechaLlegadaInput = todosInputsFecha[1];
            
            const recibido = checkbox?.checked || false;
            const fechaPedido = fechaPedidoInput?.value || '';
            const fechaLlegada = fechaLlegadaInput?.value || '';
            
            console.log(`📦 Material: ${nombreMaterial}, Fecha Pedido: ${fechaPedido}, Fecha Llegada: ${fechaLlegada}, Recibido: ${recibido}`);
            
            // Agregar si está marcado o tiene fechas
            if (recibido || fechaPedido || fechaLlegada) {
                materiales.push({
                    nombre: nombreMaterial,
                    fecha_pedido: fechaPedido || null,
                    fecha_llegada: fechaLlegada || null,
                    recibido: recibido,
                });
            }
        });
        
        console.log('📋 Materiales del modal a guardar:', materiales);
        
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
            console.log('✅ Respuesta servidor:', data);
            if (data.success) {
                showToast('Materiales guardados correctamente', 'success');
            } else {
                showToast('Error al guardar', 'error');
            }
            cerrarModalInsumos();
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Error al guardar los materiales', 'error');
        });
    }

    /**
     * Event delegation para los botones "Ver Orden"
     */
    document.addEventListener('click', function(e) {
        if (e.target.closest('.btn-ver-orden')) {
            e.stopPropagation();
            const btn = e.target.closest('.btn-ver-orden');
            const ordenJSON = btn.getAttribute('data-orden');
            if (ordenJSON) {
                try {
                    const orden = JSON.parse(ordenJSON);
                    abrirModalOrden(orden);
                } catch (error) {
                    console.error('Error al parsear orden:', error);
                }
            }
        }
    });

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
            
            console.log('🔵 Abriendo modal de filtros para:', column);
            
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
            'descripcion': 'Descripción',
            'estado': 'Estado',
            'area': 'Área',
            'fecha': 'Fecha',
            'fecha_de_creacion_de_orden': 'Fecha de Inicio'
        };

        // Valores predefinidos para ciertos filtros
        const predefinedValues = {
            'area': ['Corte', 'Creación de orden'],
            'estado': ['En Ejecución', 'No iniciado', 'Entregado', 'Anulada']
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
        
        console.log('🔵 Cargando valores iniciales para:', column);
        
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
                    console.log(`✅ Valores cargados para ${column}:`, allValues.length);
                    
                    // Renderizar primeros 15 valores
                    renderFilterValues(allValues, '', column);
                } else {
                    filterList.innerHTML = '<p style="text-align: center; color: #f00; padding: 20px;">Error al cargar valores</p>';
                }
            })
            .catch(error => {
                console.error('❌ Error:', error);
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
        
        console.log('🔵 Aplicando filtros:', {
            currentFilterColumn,
            selected,
            selectedLength: selected.length
        });
        
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
            
            console.log('📋 Filtros existentes:', { filterColumns, filterValuesArray });
            
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
            
            console.log('✅ Filtros combinados:', existingFilters);
            
            // Construir URL con todos los filtros
            const filterParams = new URLSearchParams();
            Object.keys(existingFilters).forEach(column => {
                filterParams.append('filter_columns[]', column);
                existingFilters[column].forEach(value => {
                    filterParams.append('filter_values[]', value);
                });
            });
            
            const finalUrl = `{{ route("insumos.materiales.index") }}?${filterParams.toString()}`;
            console.log('🌐 URL final:', finalUrl);
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
     * Abre el modal de descripción completa
     */
    function abrirModalDescripcion(pedido, descripcion) {
        document.getElementById('descripcionPedido').textContent = pedido;
        document.getElementById('descripcionTexto').value = descripcion;
        document.getElementById('descripcionModal').style.display = 'flex';
    }
    
    /**
     * Cierra el modal de descripción
     */
    function cerrarModalDescripcion() {
        document.getElementById('descripcionModal').style.display = 'none';
    }
    
    console.timeEnd('RENDER_TOTAL');
    console.log('✅ Vista materiales: Carga completada');
    console.log(`📦 Total de órdenes: {{ $ordenes->total() }}`);
    
    // Mostrar indicador de carga cuando se hace clic en paginación
    document.querySelectorAll('.pagination-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            if (!this.disabled) {
                document.getElementById('loadingOverlay').classList.add('active');
            }
        });
    });
</script>

<script src="{{ asset('js/insumos/pagination.js') }}"></script>
@endsection
