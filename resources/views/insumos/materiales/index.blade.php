{{-- resources/views/insumos/materiales/index.blade.php --}}
@extends('layouts.insumos')

@section('title', 'Gestión de Insumos - Control de Insumos del Pedido')
@section('page-title', 'Control de Insumos del Pedido')

@section('content')
<link rel="stylesheet" href="{{ asset('css/insumos/materiales.css') }}?v={{ time() }}">
{{-- Todos los estilos CSS extraídos a public/css/insumos/materiales.css --}}

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
     * Delegado al backend mediante API asincrónica
     */
    async function calcularDemora(materialId) {
        const idParts = materialId.split('_');
        const ordenId = idParts[1];
        const index = idParts[2];
        
        const fechaPedidoInput = document.getElementById('fecha_pedido_' + ordenId + '_' + index + '_' + idParts.slice(3).join('_'));
        const fechaLlegadaInput = document.getElementById('fecha_llegada_' + ordenId + '_' + index + '_' + idParts.slice(3).join('_'));
        const diasSpan = document.getElementById('dias_' + materialId);
        
        if (!fechaPedidoInput || !fechaLlegadaInput || !diasSpan) {
            return;
        }
        
        if (!fechaPedidoInput.value || !fechaLlegadaInput.value) {
            diasSpan.textContent = '-';
            diasSpan.className = 'inline-block px-3 py-1 rounded-full text-sm font-semibold bg-gray-100 text-gray-600';
            return;
        }
        
        const demora = await window.calcularDemoraAsync(fechaPedidoInput.value, fechaLlegadaInput.value);
        diasSpan.textContent = demora.texto;
        diasSpan.className = `inline-block px-3 py-1 rounded-full text-sm font-semibold ${demora.clase_bg} ${demora.clase_text}`;
    }

    // Variable global para rastrear el botón del dropdown abierto
    let dropdownAbiertoButton = null;

    /**
     * Crear dropdown de acciones posicionado de forma fija
     */
    function crearDropdownAcciones(event, button) {
        event.preventDefault();
        event.stopPropagation();
        
        // Si el dropdown está abierto del mismo botón, cerrarlo
        if (dropdownAbiertoButton === button) {
            cerrarDropdownAcciones();
            dropdownAbiertoButton = null;
            return;
        }
        
        // Cerrar dropdown anterior si existe
        cerrarDropdownAcciones();
        
        // Guardar referencia al botón actual
        dropdownAbiertoButton = button;
        
        const container = document.getElementById('dropdowns-container');
        if (!container) return;
        
        // Obtener datos del botón
        const pedidoProduccionId = button.getAttribute('data-pedido-produccion-id');
        const prendaId = button.getAttribute('data-prenda-id');
        const reciboId = button.getAttribute('data-recibo-id');
        const consecutivo = button.getAttribute('data-consecutivo');
        const estado = button.getAttribute('data-estado');
        const tipoRecibo = button.getAttribute('data-tipo-recibo');
        
        // Obtener posición del botón
        const rect = button.getBoundingClientRect();
        
        // Crear elemento del dropdown
        const dropdown = document.createElement('div');
        dropdown.className = 'acciones-dropdown-fixed';
        dropdown.style.cssText = `
            position: fixed;
            top: ${rect.bottom + 8}px;
            left: ${rect.right + 8}px;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            min-width: 220px;
            z-index: 999999;
            overflow: visible;
            pointer-events: auto;
        `;
        
        let html = `
            <button style="
                width: 100%;
                text-align: left;
                padding: 0.875rem 1rem;
                border: none;
                background: transparent;
                cursor: pointer;
                color: #374151;
                font-size: 0.875rem;
                transition: all 0.2s ease;
                display: flex;
                align-items: center;
                gap: 0.75rem;
                font-weight: 500;
                border-bottom: 1px solid #f3f4f6;
            " onclick="abrirModalInsumos('${pedidoProduccionId}', '${prendaId}'); cerrarDropdownAcciones();" 
            onmouseover="this.style.background='#f0fdf4'" 
            onmouseout="this.style.background='transparent'">
                <i class="fas fa-box" style="color: #10b981; font-size: 1rem;"></i>
                <span>Gestionar materiales</span>
            </button>
            
            <button style="
                width: 100%;
                text-align: left;
                padding: 0.875rem 1rem;
                border: none;
                background: transparent;
                cursor: pointer;
                color: #374151;
                font-size: 0.875rem;
                transition: all 0.2s ease;
                display: flex;
                align-items: center;
                gap: 0.75rem;
                font-weight: 500;
                border-bottom: 1px solid #f3f4f6;
            " onclick="abrirModalAnchoMetraje('${pedidoProduccionId}', '${prendaId}'); cerrarDropdownAcciones();" 
            onmouseover="this.style.background='#fef3c7'" 
            onmouseout="this.style.background='transparent'">
                <i class="fas fa-ruler" style="color: #f59e0b; font-size: 1rem;"></i>
                <span>Ancho y metraje</span>
            </button>
        `;
        
        // Agregar botón "Pasar a Revisar" solo si NO está en estado DEVUELTO_ASESOR
        if (estado !== 'DEVUELTO_ASESOR') {
            html += `
            <button style="
                width: 100%;
                text-align: left;
                padding: 0.875rem 1rem;
                border: none;
                background: transparent;
                cursor: pointer;
                color: #374151;
                font-size: 0.875rem;
                transition: all 0.2s ease;
                display: flex;
                align-items: center;
                gap: 0.75rem;
                font-weight: 500;
                border-bottom: 1px solid #f3f4f6;
            " onclick="abrirModalPasarRevisar('${reciboId}', '${pedidoProduccionId}'); cerrarDropdownAcciones();" 
            onmouseover="this.style.background='#fde4e4'" 
            onmouseout="this.style.background='transparent'">
                <i class="fas fa-arrow-rotate-left" style="color: #dc2626; font-size: 1rem;"></i>
                <span>Pasar a Revisar</span>
            </button>
            `;
        }
        
        // Agregar botón de envío solo si está en estado Pendiente
        if (estado === 'Pendiente' || estado === 'PENDIENTE_INSUMOS' || estado === 'Pendiente_Insumos') {
            html += `
            <button style="
                width: 100%;
                text-align: left;
                padding: 0.875rem 1rem;
                border: none;
                background: transparent;
                cursor: pointer;
                color: #374151;
                font-size: 0.875rem;
                transition: all 0.2s ease;
                display: flex;
                align-items: center;
                gap: 0.75rem;
                font-weight: 500;
            " onclick="cambiarEstadoRecibo('${reciboId}', '${consecutivo}'); cerrarDropdownAcciones();" 
            onmouseover="this.style.background='#dbeafe'" 
            onmouseout="this.style.background='transparent'">
                <i class="fas fa-paper-plane" style="color: #3b82f6; font-size: 1rem;"></i>
                <span>Enviar a producción</span>
            </button>
            `;
        }
        
        dropdown.innerHTML = html;
        container.appendChild(dropdown);
    }

    /**
     * Cerrar todos los dropdowns de acciones
     */
    function cerrarDropdownAcciones() {
        document.querySelectorAll('.acciones-dropdown-fixed').forEach(menu => {
            menu.remove();
        });
        dropdownAbiertoButton = null;
    }

    /**
     * Abre el detalle del recibo
     */
    function abrirDetalleRecibo(pedidoId, prendaId, tipoRecibo) {
        // Convertir parámetros correctamente
        pedidoId = parseInt(pedidoId) || null;
        
        // Convertir la string 'null' a null real, o convertir a número si tiene valor
        if (prendaId === 'null' || prendaId === '' || !prendaId) {
            prendaId = null;
        } else {
            prendaId = parseInt(prendaId) || null;
        }
        
        // Verificar si existe la función openOrderDetailModalWithProcess
        if (typeof openOrderDetailModalWithProcess === 'function') {
            openOrderDetailModalWithProcess(pedidoId, prendaId, tipoRecibo);
        } else {
            console.error('Función openOrderDetailModalWithProcess no disponible');
        }
    }

    // Funciones de modal 'Pasar a Revisar' movidas a pasar-a-revisar-insumos.js
    // - abrirModalPasarRevisar()
    // - cerrarModalPasarRevisar()
    // - contador de caracteres
    // - cerrar al hacer clic fuera
</script>

{{-- Toast Container --}}
<div id="toastContainer" style="position: fixed; top: 24px; right: 24px; z-index: 99999; display: flex; flex-direction: column; gap: 12px; pointer-events: none;"></div>

{{-- Loading Overlay --}}
<div id="loadingOverlay" class="loading-overlay">
    <div class="loading-spinner"></div>
</div>

<div style="min-height: 100vh; background: #f9fafb; margin: 0; padding: 1.5rem; box-sizing: border-box;">
    {{-- Header Principal Blanco --}}
    <div style="background: white; border-bottom: 1px solid #e5e7eb; box-shadow: 0 1px 2px rgba(0,0,0,0.05); width: 100%; margin: 0; box-sizing: border-box;">
        <div style="padding: 1rem 0; width: 100%;">
            {{-- Título, Descripción y Campana --}}
            <div style="margin-bottom: 1rem; padding: 0 0.5rem; display: flex; justify-content: space-between; align-items: flex-start;">
                <div style="flex: 1;">
                    <h1 class="text-3xl font-bold text-gray-900 flex items-center gap-3">
                        <span class="material-symbols-rounded text-4xl text-blue-600">inventory_2</span>
                        Control de Insumos del Pedido
                    </h1>
                    <p class="text-gray-600 text-sm mt-2">Gestiona y controla los insumos de tus pedidos en tiempo real</p>
                </div>
                {{-- Campana de Notificaciones INSUMOS (IDs únicos para evitar colisión con notifications-realtime.js global) --}}
                <div style="position: relative;">
                    <button id="insumosBellBtn" class="relative p-3 text-gray-600 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition" title="Notificaciones de nuevos recibos">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                        </svg>
                        <span id="insumosBadge" class="absolute top-0 right-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform translate-x-1 -translate-y-1 bg-red-600 rounded-full" style="display: none; min-width: 20px;">0</span>
                    </button>

                    {{-- Dropdown de Notificaciones --}}
                    <div id="insumosDropdown" class="absolute right-0 mt-2 w-96 bg-white rounded-lg shadow-lg border border-gray-200 z-50" style="display: none; max-height: 500px; overflow-y: auto;">
                        <div class="p-4 border-b border-gray-200 bg-grad gradient-to-r from-blue-50 to-blue-100">
                            <div class="flex justify-between items-center">
                                <h3 class="font-bold text-gray-900">Nuevos Recibos Aprobados</h3>
                                <button id="insumosClearBtn" class="text-sm text-gray-600 hover:text-gray-900 font-medium">Limpiar Todo</button>
                            </div>
                        </div>
                        <div id="insumosNotifList" class="divide-y divide-gray-200">
                            <div class="p-4 text-center text-gray-500">
                                <p>Sin notificaciones</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Buscador Mejorado --}}
            <form action="{{ route('insumos.materiales.index') }}" method="GET" class="flex gap-3 items-end" style="padding: 0 0.5rem;">
                <div class="flex-1 relative">
                    <div class="relative">
                        <input 
                            type="text" 
                            name="search" 
                            value="{{ request('search') }}"
                            placeholder="Buscar por N° Recibo (1234) o Cliente (Empresa ABC)..."
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
                                <span>N° Recibo</span>
                            </th>
                            <th class="text-left py-4 px-6 font-bold">
                                <div class="flex items-center justify-between gap-2">
                                    <span>N° Pedido</span>
                                </div>
                            </th>
                            <th class="text-left py-4 px-6 font-bold">
                                <span>Cliente</span>
                            </th>
                            <th class="text-center py-4 px-6 font-bold">
                                <span>Estado</span>
                            </th>
                            <th class="text-center py-4 px-6 font-bold">
                                <span>Área</span>
                            </th>
                            <th class="text-center py-4 px-6 font-bold">
                                <span>Fecha de Inicio</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($ordenes ?? [] as $orden)
                            <tr class="border-b border-gray-200 hover:bg-gray-50 transition @if(isset($orden->dias_calculados) && $orden->dias_calculados > 0)
                                @if($orden->dias_calculados >= 14) dias-mayor-15
                                @elseif($orden->dias_calculados >= 10) dias-10-15
                                @elseif($orden->dias_calculados >= 5) dias-5-9
                                @else dias-0-4 @endif
                            @endif @if(isset($orden->marcar_plooter) && $orden->marcar_plooter) row-checked @endif" 
                            data-pedido="{{ strtoupper($orden->numero_pedido ?? '') }}" 
                            data-cliente="{{ strtoupper($orden->cliente ?? '') }}" 
                            data-orden-pedido="{{ $orden->numero_pedido }}"
                            data-recibo="{{ $orden->id ?? '' }}"
                            data-material-id="{{ $orden->id ?? '' }}"
                            data-pedido-produccion-id="{{ $orden->pedido_produccion_id ?? '' }}">
                                <td class="py-4 px-6 text-center" style="min-width: 250px; overflow: visible; background: white; position: relative; z-index: 5;">
                                    <div class="flex items-center justify-center gap-3" style="display: flex !important; flex-wrap: wrap; overflow: visible;">
                                        {{-- Definir variables primero --}}
                                        @php
                                            $userRole = auth()->user()->role;
                                            $roleName = is_object($userRole) ? $userRole->name : $userRole;
                                            $isPatronista = $roleName === 'patronista';
                                            $reciboId = $orden->id;
                                            $pedidoProduccionId = $orden->pedido_produccion_id;
                                        @endphp
                                        
                                        {{-- Botón Check (marca) en purple --}}
                                        <button 
                                            class="btn-check-row btn-tooltip p-2 text-purple-600 hover:bg-purple-50 rounded transition @if(isset($orden->marcar_plooter) && $orden->marcar_plooter) checked @endif"
                                            onclick="toggleRowCheck(this, event)"
                                            data-tooltip="Marcar fila"
                                            title="Marcar fila"
                                        >
                                            <i class="fas fa-check text-lg"></i>
                                        </button>

                                        {{-- Botón Ver Recibo (visible siempre) --}}
                                        <button 
                                            class="btn-tooltip p-2 text-blue-600 hover:bg-blue-50 rounded transition"
                                            onclick="abrirDetalleRecibo('{{ $pedidoProduccionId }}', '{{ $orden->prenda_id ?? 'null' }}', '{{ $orden->tipo_recibo ?? 'COSTURA' }}')"
                                            data-tooltip="Ver recibo"
                                            title="Ver recibo"
                                        >
                                            <i class="fas fa-eye text-lg"></i>
                                        </button>

                                        {{-- Dropdown de Acciones (solo para no-patronistas) --}}
                                        @if(!$isPatronista)
                                            <button 
                                                class="btn-acciones p-2 text-gray-600 hover:bg-gray-100 rounded transition"
                                                onclick="crearDropdownAcciones(event, this)"
                                                data-pedido-produccion-id="{{ $pedidoProduccionId }}"
                                                data-prenda-id="{{ $orden->prenda_id ?? '' }}"
                                                data-recibo-id="{{ $reciboId }}"
                                                data-consecutivo="{{ $orden->consecutivo_actual }}"
                                                data-estado="{{ $orden->estado ?? '' }}"
                                                data-tipo-recibo="{{ $orden->tipo_recibo ?? 'COSTURA' }}"
                                                title="Más opciones"
                                            >
                                                <i class="fas fa-ellipsis-v text-lg"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                                <td class="py-4 px-6">
                                    <span class="font-bold text-blue-600 text-lg">{{ $orden->numero_pedido ?? 'N/A' }}</span>
                                </td>
                                <td class="py-4 px-6">
                                    <span class="font-medium text-gray-800">{{ $orden->numero_pedido_original ?? 'N/A' }}</span>
                                </td>
                                <td class="py-4 px-6">
                                    <span class="font-medium text-gray-800">{{ $orden->cliente ?? 'N/A' }}</span>
                                </td>
                                <td class="py-4 px-6 text-center">
                                    @php
                                        $estadoClass = '';
                                        $estadoColor = '';
                                        $estadoDisplay = '';
                                        
                                        if ($orden->estado === 'No iniciado') {
                                            $estadoClass = 'bg-gray-400 text-white';
                                            $estadoDisplay = 'No iniciado';
                                        } elseif ($orden->estado === 'En Ejecución') {
                                            $estadoClass = 'bg-blue-100 text-blue-800';
                                            $estadoDisplay = 'En Ejecución';
                                        } elseif ($orden->estado === 'Anulada') {
                                            $estadoClass = 'bg-amber-100 text-amber-800';
                                            $estadoDisplay = 'Anulada';
                                        } elseif ($orden->estado === 'PENDIENTE_INSUMOS' || $orden->estado === 'Pendiente_Insumos') {
                                            $estadoClass = 'bg-green-500 text-white';
                                            $estadoDisplay = 'Pendiente Insumos';
                                        } elseif ($orden->estado === 'DEVUELTO_ASESOR') {
                                            $estadoClass = 'bg-red-500 text-white';
                                            $estadoDisplay = 'Devuelto Asesor';
                                        } else {
                                            $estadoDisplay = str_replace('_', ' ', $orden->estado ?? 'N/A');
                                        }
                                    @endphp
                                    <span class="inline-block px-3 py-1 rounded-full text-sm font-semibold {{ $estadoClass }}">
                                        {{ $estadoDisplay }}
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
</script>

{{-- Modal para ver orden --}}
<!-- Modal de Detalle de Orden -->
<div id="modal-overlay" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.5); backdrop-filter: blur(4px); z-index: 9997; display: none; pointer-events: auto;" onclick="closeModalOverlay()"></div>

<div id="order-detail-modal-wrapper" style="width: 90%; max-width: 672px; position: fixed; top: 60%; left: 50%; transform: translate(-50%, -50%); z-index: 9998; pointer-events: auto; display: none;">
    <x-orders-components.order-detail-modal />
</div>

<!-- Modal de Seguimiento del Pedido -->
<x-orders-components.order-tracking-modal />

{{-- Incluir todos los modales de insumos consolidados --}}
@include('insumos.materiales.partials.modales-insumos')

<!-- Contenedor para dropdowns dinámicos -->
<div id="dropdowns-container" style="position: fixed; top: 0; left: 0; z-index: 999999; pointer-events: none;"></div>

<script>
    /**
     * Alias para cerrar el modal - compatible con asesores
     */

    // Remaining inline script starts here - All remaining functions moved to JS modules


    /**
     * Mostrar/ocultar botón eliminar basado en si hay datos guardados
     */
    function mostrarBotonesAnchoMetraje() {
        const modal = document.getElementById('modalAnchoMetraje');
        const btnEliminar = document.getElementById('btnEliminarAnchoMetraje');
        
        if (modal.tieneDatosGuardados) {
            btnEliminar.classList.remove('hidden');
        } else {
            btnEliminar.classList.add('hidden');
        }
    }

    // Función movida a modal-ancho-metraje-insumos.js: generarInputsPorColor()
    // Función movida a modal-ancho-metraje-insumos.js: generarInputsPorTallaColor()
    // Función movida a modal-ancho-metraje-insumos.js: generarInputsPorPieza()
    // Función movida a modal-ancho-metraje-insumos.js: cambiarModoAnchoMetraje()
    // Función movida a modal-ancho-metraje-insumos.js: cerrarModalAnchoMetraje()

    // Funciones stub movidas a index-blade-handlers.js en FASE 6

    /**
     * Abre el modal de insumos para una orden y prenda específica
     */
    function abrirModalInsumos(pedido, prendaId) {
        // Mostrar el modal
        const modal = document.getElementById('insumosModal');
        modal.style.display = 'flex';
        
        // Remover aria-hidden del contenido principal para evitar conflictos
        const mainContent = document.getElementById('mainContent');
        if (mainContent) {
            mainContent.removeAttribute('aria-hidden');
        }

        // Establecer el pedido y prenda
        document.getElementById('modalPedido').textContent = pedido;
        document.getElementById('modalPrendaId').value = prendaId || '';
        document.getElementById('modalPrendaNombre').textContent = prendaId ? `Cargando...` : 'General';

        // Construir URL con prenda_id si existe
        let url = `/insumos/api/materiales/${pedido}`;
        if (prendaId) {
            url += `?prenda_id=${prendaId}`;
        }

        // Cargar los insumos de la orden filtrados por prenda
        fetch(url)
            .then(response => response.json())
            .then(data => {
                // Actualizar nombre de prenda si viene en la respuesta
                if (data.nombre_prenda) {
                    document.getElementById('modalPrendaNombre').textContent = data.nombre_prenda;
                } else if (prendaId) {
                    document.getElementById('modalPrendaNombre').textContent = `Prenda #${prendaId}`;
                }
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
        row.setAttribute('data-guardado', 'true');
        
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
        
        // Marcar como fila nueva (no guardada en BD)
        row.setAttribute('data-nuevo', 'true');
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
     * Delegado a calcularDemora() que usa API asincrónica
     */
    async function actualizarDiasDemora(fila) {
        const todosInputsFecha = fila.querySelectorAll('input[type="date"]');
        const diasSpan = fila.querySelector('span[class*="bg-"]');
        
        if (!diasSpan) {
            return;
        }
        
        // Si no hay fechas completas, mostrar "-"
        if (!todosInputsFecha[0]?.value || !todosInputsFecha[1]?.value) {
            diasSpan.textContent = '-';
            diasSpan.className = 'inline-block px-3 py-1 rounded-full text-sm font-semibold bg-gray-100 text-gray-600';
            return;
        }
        
        // Obtener demora desde API
        const demora = await window.calcularDemoraAsync(todosInputsFecha[0].value, todosInputsFecha[1].value);
        diasSpan.textContent = demora.texto;
        diasSpan.className = `inline-block px-3 py-1 rounded-full text-sm font-semibold ${demora.clase_bg} ${demora.clase_text}`;
    }

    // Filtro functions han sido movidas a filter-modal-insumos.js
    
    // Función movida a status-actions-insumos.js: cambiarEstadoRecibo()

    // Función movida a status-actions-insumos.js: cambiarEstadoPedido()
    
    // Función movida a status-actions-insumos.js: cerrarModalConfirmarProduccion()
    
    // Función movida a status-actions-insumos.js: restaurarBotonAprobar()
    
    // Función movida a status-actions-insumos.js: confirmarEnvioProduccion()
    
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

<!-- Scripts para el modal de órdenes (defer para no-críticos) -->
<script defer src="{{ asset('js/ordersjs/order-detail-modal-manager.js') }}"></script>
<script defer src="{{ asset('js/asesores/pedidos-detail-modal.js') }}"></script>
<!-- Image Gallery para mostrar fotos en el modal -->
<script defer src="{{ asset('js/orders-scripts/image-gallery-zoom.js') }}"></script>
<script defer src="{{ asset('js/insumos/pagination.js') }}"></script>

<!-- Módulos de Insumos - Critical Path (carga inmediata) -->
<script type="module" src="{{ asset('js/insumos/index.js') }}"></script>

<!-- FASE 1-2: Módulos base (critical) -->
<script src="{{ asset('js/insumos/modal-handlers-insumos.js') }}"></script>
<script src="{{ asset('js/insumos/table-handlers-insumos.js') }}"></script>
<script src="{{ asset('js/insumos/filter-manager-insumos.js') }}"></script>

<!-- Inicializar sistema de festivos (API externa + fallback local) -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof inicializarFestivos === 'function') {
        inicializarFestivos().catch(error => {
            console.error('[Init] Error inicializando festivos:', error);
        });
    }
});
</script>
<script src="{{ asset('js/insumos/material-operations-insumos.js') }}"></script>
<script src="{{ asset('js/insumos/form-handlers-insumos.js') }}"></script>

<!-- FASE 3-6: Módulos adicionales (defer) -->
<script defer src="{{ asset('js/insumos/status-actions-insumos.js') }}"></script>
<script defer src="{{ asset('js/insumos/modal-ancho-metraje-insumos.js') }}"></script>
<script defer src="{{ asset('js/insumos/filter-modal-insumos.js') }}"></script>
<script defer src="{{ asset('js/insumos/insumos-modal-management.js') }}"></script>
<script defer src="{{ asset('js/insumos/notifications-realtime-insumos.js') }}"></script>
<script defer src="{{ asset('js/insumos/recibos-selector-insumos.js') }}"></script>
<script defer src="{{ asset('js/insumos/pasar-a-revisar-insumos.js') }}"></script>
<script defer src="{{ asset('js/insumos/index-blade-handlers.js') }}"></script>

<!-- Performance - Utilidades (defer) -->
<script defer src="{{ asset('js/insumos/search-debounce.js') }}"></script>

<!-- Scripts no-críticos (defer) -->
<script defer src="{{ asset('js/ordersjs/tracking-modal-handler.js') }}?v={{ time() }}"></script>
<script defer src="{{ asset('js/asesores/pedidos-dropdown-simple.js') }}"></script>
<script defer src="{{ asset('js/modulos/invoice/InvoiceLazyLoader.js') }}?v={{ time() }}"></script>
<script defer src="{{ asset('js/asesores/invoice-from-list.js') }}"></script>
<script defer src="{{ asset('js/asesores/receipt-manager.js') }}"></script>
<script defer src="{{ asset('js/insumos/insumos-galeria.js') }}"></script>

<!-- Scripts para Recibos/Procesos (SIN defer para carga rápida) -->
<script type="module" src="{{ asset('js/modulos/pedidos-recibos/loader.js') }}"></script>

@endsection

