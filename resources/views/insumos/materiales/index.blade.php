{{-- resources/views/insumos/materiales/index.blade.php --}}
@extends('insumos.layout')

@section('title', 'Gestión de Insumos - Control de Insumos del Pedido')
@section('page-title', 'Control de Insumos del Pedido')

@section('content')
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
                @if(request('search'))
                    <a href="{{ route('insumos.materiales.index') }}" class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition shadow-sm flex items-center gap-2 whitespace-nowrap border border-gray-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        Limpiar
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

    <div class="px-6 py-8 max-w-7xl mx-auto">

    {{-- Órdenes y Materiales --}}
    <div class="space-y-0" id="ordenesContainer">
        @forelse($ordenes ?? [] as $orden)
            @php
                // Materiales estándar para cada orden
                $materiales = ['Tela', 'Reflectivo', 'Cierre', 'Cuello y puños'];
                
                // Calcular progreso (materiales recibidos)
                $completados = 2; // Mostrar 50% por defecto
                $progreso = 50;
            @endphp

            <div class="bg-white rounded-2xl shadow-md overflow-hidden orden-item" style="transform: scale(0.78); transform-origin: top left; width: 128.2%; margin-bottom: -3.5rem;" data-pedido="{{ strtoupper($orden->pedido ?? '') }}" data-cliente="{{ strtoupper($orden->cliente ?? '') }}" data-orden-pedido="{{ $orden->pedido }}">
                {{-- Header Azul con información de la orden --}}
                <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-6 text-white">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-2">
                                <h2 class="text-3xl font-bold">{{ $orden->pedido ?? 'Sin código' }}</h2>
                                <span class="text-white text-lg font-bold bg-blue-500 bg-opacity-60 px-4 py-2 rounded-full">
                                    {{ $orden->cliente ?? 'Sin cliente' }}
                                </span>
                            </div>
                            <p class="text-blue-100 text-sm">
                                {{ Str::limit($orden->descripcion ?? '', 50) }}
                            </p>
                        </div>
                        <div class="text-right">
                            <div class="flex items-center gap-2 text-white text-sm font-semibold">
                                📅 {{ $orden->fecha_de_creacion_de_orden ? \Carbon\Carbon::parse($orden->fecha_de_creacion_de_orden)->subHours(5)->format('d/m/Y') : 'N/A' }}
                            </div>
                        </div>
                    </div>
                    
                    {{-- Botón Ver Orden --}}
                    <button 
                        class="btn-ver-orden px-4 py-2 bg-white text-blue-600 font-semibold rounded-lg hover:bg-blue-50 transition"
                        data-orden="{{ json_encode($orden) }}"
                    >
                        👁️ Ver Orden
                    </button>
                </div>

                {{-- Tabla de Materiales --}}
                <div class="px-6 py-6">
                    @if(count($materiales) > 0)
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="border-b-2 border-gray-300">
                                        <th class="text-left py-3 px-4 font-bold text-gray-800">Material</th>
                                        <th class="text-center py-3 px-4 font-bold text-gray-800">Estado</th>
                                        <th class="text-center py-3 px-4 font-bold text-gray-800">Fecha Pedido</th>
                                        <th class="text-center py-3 px-4 font-bold text-gray-800">Fecha Llegada</th>
                                        <th class="text-center py-3 px-4 font-bold text-gray-800">Días Demora</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($materiales as $index => $material)
                                        @php
                                            // Generar IDs únicos para cada material usando pedido + índice + nombre sanitizado
                                            $sanitizedMaterial = str_replace(' ', '_', strtolower($material));
                                            $materialId = 'material_' . $orden->pedido . '_' . $index . '_' . $sanitizedMaterial;
                                            $fechaPedidoId = 'fecha_pedido_' . $orden->pedido . '_' . $index . '_' . $sanitizedMaterial;
                                            $fechaLlegadaId = 'fecha_llegada_' . $orden->pedido . '_' . $index . '_' . $sanitizedMaterial;
                                            $checkboxId = 'checkbox_' . $orden->pedido . '_' . $index . '_' . $sanitizedMaterial;
                                            
                                            // Color del punto según estado
                                            $colores = ['bg-green-500', 'bg-yellow-500', 'bg-gray-400'];
                                            $colorPunto = $colores[$index % 3];
                                            
                                            // Buscar si hay datos guardados para este material
                                            $materialGuardado = null;
                                            if (isset($orden->materiales_guardados)) {
                                                $materialGuardado = $orden->materiales_guardados
                                                    ->firstWhere('nombre_material', $material);
                                            }
                                        @endphp
                                        
                                        <tr class="border-b border-gray-200 hover:bg-gray-50 transition">
                                            {{-- Material con punto de color --}}
                                            <td class="py-4 px-4 font-medium text-gray-900">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-3 h-3 rounded-full {{ $colorPunto }}"></div>
                                                    <span>{{ $material }}</span>
                                                </div>
                                            </td>
                                            
                                            {{-- Estado (Checkbox visual) --}}
                                            <td class="py-4 px-4 text-center">
                                                <input 
                                                    type="checkbox" 
                                                    id="{{ $checkboxId }}"
                                                    class="w-6 h-6 cursor-pointer material-checkbox accent-green-500"
                                                    {{ $materialGuardado && $materialGuardado->recibido ? 'checked' : '' }}
                                                    data-original="{{ $materialGuardado && $materialGuardado->recibido ? 'true' : 'false' }}"
                                                    onchange="confirmarEliminacion(this, '{{ $materialId }}')"
                                                >
                                            </td>
                                            
                                            {{-- Fecha Pedido --}}
                                            <td class="py-4 px-4 text-center">
                                                <div class="flex items-center justify-center gap-2">
                                                    <input 
                                                        type="date" 
                                                        id="{{ $fechaPedidoId }}"
                                                        class="px-2 py-1 border border-gray-300 rounded text-sm font-medium text-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                        value="{{ $materialGuardado && $materialGuardado->fecha_pedido ? $materialGuardado->fecha_pedido->format('Y-m-d') : '' }}"
                                                        data-original="{{ $materialGuardado && $materialGuardado->fecha_pedido ? $materialGuardado->fecha_pedido->format('Y-m-d') : '' }}"
                                                        onchange="calcularDemora('{{ $materialId }}')"
                                                        oninput="calcularDemora('{{ $materialId }}')"
                                                    >
                                                </div>
                                            </td>
                                            
                                            {{-- Fecha Llegada --}}
                                            <td class="py-4 px-4 text-center">
                                                <div class="flex items-center justify-center gap-2">
                                                    <span class="text-green-600">✓</span>
                                                    <input 
                                                        type="date" 
                                                        id="{{ $fechaLlegadaId }}"
                                                        class="px-2 py-1 border border-gray-300 rounded text-sm font-medium text-green-600 focus:outline-none focus:ring-2 focus:ring-green-500"
                                                        value="{{ $materialGuardado && $materialGuardado->fecha_llegada ? $materialGuardado->fecha_llegada->format('Y-m-d') : '' }}"
                                                        data-original="{{ $materialGuardado && $materialGuardado->fecha_llegada ? $materialGuardado->fecha_llegada->format('Y-m-d') : '' }}"
                                                        onchange="calcularDemora('{{ $materialId }}')"
                                                        oninput="calcularDemora('{{ $materialId }}')"
                                                    >
                                                </div>
                                            </td>
                                            
                                            {{-- Días Demora --}}
                                            <td class="py-4 px-4 text-center">
                                                @php
                                                    $diasDemora = $materialGuardado ? $materialGuardado->dias_demora : null;
                                                    $bgColor = 'bg-gray-100 text-gray-600';
                                                    $icon = '';
                                                    if ($diasDemora !== null) {
                                                        if ($diasDemora <= 0) {
                                                            $bgColor = 'bg-green-100 text-green-700';
                                                            $icon = '✓ ';
                                                        } elseif ($diasDemora <= 5) {
                                                            $bgColor = 'bg-yellow-100 text-yellow-700';
                                                            $icon = '⚠ ';
                                                        } else {
                                                            $bgColor = 'bg-red-100 text-red-700';
                                                            $icon = '✕ ';
                                                        }
                                                    }
                                                @endphp
                                                <span id="dias_{{ $materialId }}" class="inline-block px-3 py-1 rounded-full text-sm font-semibold {{ $bgColor }}">
                                                    {{ $diasDemora !== null ? $icon . $diasDemora . ' días' : '-' }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{-- Botones de acción --}}
                        <div class="mt-6 flex gap-3">
                            <button 
                                onclick="guardarCambios('{{ $orden->pedido }}')"
                                class="px-6 py-2 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition"
                            >
                                💾 Guardar Cambios
                            </button>
                        </div>
                    @else
                        <p class="text-gray-500 italic">No hay materiales registrados para esta orden.</p>
                    @endif
                </div>
            </div>
        @empty
            <div class="bg-white rounded-2xl shadow-md p-12 text-center">
                <p class="text-xl text-gray-500">No hay órdenes disponibles</p>
            </div>
        @endforelse
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
            
            // Obtener el pedido del materialId
            const idParts = materialId.split('_');
            const ordenPedido = idParts[1];
            
            // Mostrar modal de confirmación
            Swal.fire({
                title: '¿Eliminar Material?',
                html: `<div style="text-align: left; margin: 20px 0;">
                    <p><strong>Material:</strong> ${nombreMaterial}</p>
                    <p><strong>Fecha Pedido:</strong> ${fechaPedido}</p>
                    <p><strong>Fecha Llegada:</strong> ${fechaLlegada}</p>
                    <p style="color: #ef4444; margin-top: 15px;"><strong>⚠️ Se eliminará este registro y todos sus datos.</strong></p>
                </div>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                allowOutsideClick: false,
                allowEscapeKey: false
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
     * Elimina un material inmediatamente del servidor (sin remover la fila)
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
                // Limpiar las fechas pero mantener la fila
                const inputsFecha = fila.querySelectorAll('input[type="date"]');
                if (inputsFecha[0]) inputsFecha[0].value = '';
                if (inputsFecha[1]) inputsFecha[1].value = '';
                
                // Limpiar el span de días de demora
                const diasSpan = fila.querySelector('[id^="dias_"]');
                if (diasSpan) {
                    diasSpan.textContent = '-';
                    diasSpan.className = 'inline-block px-3 py-1 rounded-full text-sm font-semibold bg-gray-100 text-gray-600';
                }
                
                showToast('Material eliminado correctamente', 'success');
                Swal.hideLoading();
                Swal.fire('Eliminado', 'El material ha sido eliminado de la base de datos.', 'success');
            } else {
                showToast('Error al eliminar: ' + data.message, 'error');
                Swal.hideLoading();
                Swal.fire('Error', data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Error al eliminar el material', 'error');
            Swal.hideLoading();
            Swal.fire('Error', 'Ocurrió un error al eliminar el material', 'error');
        });
    }

    /**
     * Guarda los cambios enviando los datos al servidor
     */
    function guardarCambios(ordenPedido) {
        console.log('🔵 Guardando materiales para pedido:', ordenPedido);
        
        const tabla = document.querySelector(`[data-orden-pedido="${ordenPedido}"]`);
        if (!tabla) {
            console.error('❌ No se encontró la orden:', ordenPedido);
            showToast('No se encontró la orden', 'error');
            return;
        }
        
        const filas = tabla.querySelectorAll('tbody tr');
        const materiales = [];
        
        filas.forEach((fila, index) => {
            const celdas = fila.querySelectorAll('td');
            
            // Obtener el nombre del material del primer celda (removiendo el punto de color)
            const nombreMaterialEl = celdas[0];
            let nombreMaterial = nombreMaterialEl.textContent.trim();
            // Remover caracteres especiales del punto de color
            nombreMaterial = nombreMaterial.replace(/^[•●○◐◑\s]+/, '').trim();
            
            // Obtener los inputs de fecha de esta fila
            const inputsFecha = fila.querySelectorAll('input[type="date"]');
            const inputCheckbox = fila.querySelector('input[type="checkbox"]');
            
            const fechaPedidoInput = inputsFecha[0];
            const fechaLlegadaInput = inputsFecha[1];
            
            const fechaPedido = fechaPedidoInput?.value || '';
            const fechaLlegada = fechaLlegadaInput?.value || '';
            const recibido = inputCheckbox?.checked || false;
            
            // Obtener valores originales (comparar strings)
            const originalCheckbox = inputCheckbox?.dataset.original === 'true';
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
            
            // Guardar si hay cambios (ya sea check o uncheck)
            if (hayChangios) {
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
    
    console.timeEnd('RENDER_TOTAL');
    console.log('✅ Vista materiales: Carga completada');
    console.log(`📦 Total de órdenes: {{ $ordenes->total() }}`);
</script>

<script src="{{ asset('js/insumos/pagination.js') }}"></script>
@endsection
