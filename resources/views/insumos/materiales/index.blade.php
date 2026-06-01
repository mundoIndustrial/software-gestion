{{-- resources/views/insumos/materiales/index.blade.php --}}
@extends('layouts.insumos')

@section('module', 'insumos-materiales')
@section('title', 'Gestión de Insumos - Control de Insumos del Pedido')
@php
    $esGestionReflectivo = (bool) ($esGestionReflectivo ?? false);
    $mostrarSoloVerRecibo = (bool) ($mostrarSoloVerRecibo ?? false);
    $tipoReciboActivo = strtoupper((string) ($tipoReciboActivo ?? 'COSTURA'));
    $esTabBodega = $tipoReciboActivo === 'CORTE-PARA-BODEGA';
    $conteoRecibosBodegaPendientes = (int) ($conteoRecibosBodegaPendientes ?? 0);
    $mostrarColumnaPedido = !$esTabBodega;
    $mostrarColumnaEstado = !$esGestionReflectivo;
    $totalColumnas = 7 + ($mostrarColumnaPedido ? 1 : 0) + ($mostrarColumnaEstado ? 1 : 0);
    $assetVersion = static fn (string $path): int => is_file(public_path($path))
        ? filemtime(public_path($path))
        : time();
@endphp

@section('page-title', $esGestionReflectivo ? 'Gestion Reflectivo' : 'Control de Insumos del Pedido')

@section('content')
@php
    $currentRoleName = auth()->user()->role->name ?? 'guest';
@endphp
<link rel="stylesheet" href="{{ asset('css/insumos/materiales.css') }}?v={{ $assetVersion('css/insumos/materiales.css') }}">
<link rel="stylesheet" href="{{ asset('css/tracking-modal.css') }}?v={{ $assetVersion('css/tracking-modal.css') }}">
@if($mostrarSoloVerRecibo)
<style>
    .btn-check-row,
    .btn-acciones {
        display: none !important;
    }
</style>
@endif
{{-- Todos los estilos CSS extraídos a public/css/insumos/materiales.css --}}

@if(app()->isLocal())
<script>
    console.time('RENDER_TOTAL');
</script>
@endif
{{-- Las funciones de dropdown y seguimiento están en dropdown-handlers-insumos.js --}}

{{-- Toast Container --}}
<div id="toastContainer" style="position: fixed; top: 24px; right: 24px; z-index: 99999; display: flex; flex-direction: column; gap: 12px; pointer-events: none;"></div>

{{-- Loading Overlay --}}
<div id="loadingOverlay" class="loading-overlay active">
    <div class="loading-content">
        <div class="loading-spinner"></div>
        <p class="loading-title">Espera un momento...</p>
        <p class="loading-subtitle">Estamos cargando los recibos y configurando la vista.</p>
    </div>
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
                        {{ $esGestionReflectivo ? 'Gestion Reflectivo' : 'Control de Insumos del Pedido' }}
                    </h1>
                    <p class="text-gray-600 text-sm mt-2">
                        {{ $esGestionReflectivo ? 'Visualiza recibos del proceso reflectivo.' : 'Gestiona y controla los insumos de tus pedidos en tiempo real' }}
                    </p>
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

            {{-- Buscador - Sin URL, estado local puro --}}
            <div class="flex gap-3 items-end" style="padding: 0 0.5rem; position: relative; z-index: 1;">
                <div class="flex-1 relative">
                    <div class="relative">
                        <input 
                            type="text" 
                            name="search" 
                            placeholder="Buscar por N° Recibo (1234) o Cliente (Empresa ABC)..."
                            class="w-full px-4 py-3 bg-gray-50 text-gray-800 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition shadow-sm"
                        >
                        {{-- El botón X se agregará dinámicamente por search-debounce.js --}}
                    </div>
                </div>
            </div>

            @unless($esGestionReflectivo)
            <div style="padding: 0.75rem 0.5rem 0; position: relative; z-index: 5;">
                <div style="display: inline-flex; gap: 0.4rem; background: #f3f4f6; border-radius: 999px; padding: 0.25rem; border: 1px solid #e5e7eb;">
                    <a id="tabInsumosPedidos" href="{{ route('insumos.materiales.index', ['tipo_recibo' => 'COSTURA']) }}"
                       style="position: relative; z-index: 30; pointer-events: auto; padding: 0.45rem 0.9rem; border-radius: 999px; font-size: 0.85rem; font-weight: 600; text-decoration: none; {{ !$esTabBodega ? 'background:#2563eb;color:#fff;' : 'color:#374151;' }}">
                        Pedidos
                    </a>
                    <a id="tabInsumosBodega" href="{{ route('insumos.materiales.index', ['tipo_recibo' => 'CORTE-PARA-BODEGA']) }}"
                       style="position: relative; z-index: 30; pointer-events: auto; display: inline-flex; align-items: center; gap: 0.45rem; padding: 0.45rem 0.9rem; border-radius: 999px; font-size: 0.85rem; font-weight: 600; text-decoration: none; {{ $esTabBodega ? 'background:#2563eb;color:#fff;' : 'color:#374151;' }}">
                        Bodega
                        @if($conteoRecibosBodegaPendientes > 0)
                            <span style="display:inline-flex;align-items:center;justify-content:center;min-width:1.2rem;height:1.2rem;padding:0 0.35rem;border-radius:999px;background:#dc2626;color:#fff;font-size:0.7rem;font-weight:700;line-height:1;">
                                {{ $conteoRecibosBodegaPendientes }}
                            </span>
                        @endif
                    </a>
                </div>
            </div>
            @endunless
        </div>
    </div>

    @unless($esGestionReflectivo)
    <script>
        (function () {
            function bindTabNavigation(id) {
                const el = document.getElementById(id);
                if (!el || el.dataset.navBound === '1') return;
                el.dataset.navBound = '1';

                const go = function (event) {
                    event.preventDefault();
                    event.stopPropagation();
                    window.location.assign(el.href);
                };

                el.addEventListener('click', go);
                el.addEventListener('touchend', go, { passive: false });
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', function () {
                    bindTabNavigation('tabInsumosPedidos');
                    bindTabNavigation('tabInsumosBodega');
                });
            } else {
                bindTabNavigation('tabInsumosPedidos');
                bindTabNavigation('tabInsumosBodega');
            }
        })();
    </script>
    @endunless

    {{-- Botón Flotante Limpiar Filtros (esquina inferior derecha) --}}
    <button type="button" id="btnClearAllFiltersFloating" onclick="clearAllTableFilters();" class="floating-clear-filter-btn" style="display: none;" title="Limpiar filtros activos">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
        </svg>
    </button>

    <div style="margin: 0; width: 100%; overflow: visible;">
        {{-- Tabla Principal de Órdenes --}}
        <div class="bg-white" style="margin: 0; border-radius: 0; box-shadow: none; width: 100%; overflow-x: auto; overflow-y: visible; padding: 0 0.5rem;">
            <div style="width: 100%; margin: 0; padding: 0;">
                <table class="w-full" style="font-size: 0.75em; width: 100%; margin: 0; padding: 0;">
                    <thead>
                        <tr class="bg-gradient-to-r from-blue-600 to-blue-700 text-white">
                            <th class="text-center py-4 px-6 font-bold whitespace-nowrap" style="min-width: 200px;">Acciones</th>
                            <th class="text-center py-4 px-6 font-bold">
                                <div class="flex items-center justify-center gap-2">
                                    <span>Área</span>
                                    <button
                                        type="button"
                                        class="filter-btn-insumos p-1 rounded hover:bg-blue-500 transition"
                                        data-column="area"
                                        title="Filtrar Área"
                                    >
                                        <i class="fas fa-filter text-xs"></i>
                                    </button>
                                </div>
                            </th>
                            <th class="text-left py-4 px-6 font-bold">
                                <div class="flex items-center justify-between gap-2">
                                    <span>N° Recibo</span>
                                    <button
                                        type="button"
                                        class="filter-btn-insumos p-1 rounded hover:bg-blue-500 transition"
                                        data-column="consecutivo_actual"
                                        title="Filtrar N° Recibo"
                                    >
                                        <i class="fas fa-filter text-xs"></i>
                                    </button>
                                </div>
                            </th>
                            @if($mostrarColumnaPedido)
                                <th class="text-left py-4 px-6 font-bold">
                                    <div class="flex items-center justify-between gap-2">
                                        <span>N° Pedido</span>
                                        <button
                                            type="button"
                                            class="filter-btn-insumos p-1 rounded hover:bg-blue-500 transition"
                                            data-column="numero_pedido"
                                            title="Filtrar N° Pedido"
                                        >
                                            <i class="fas fa-filter text-xs"></i>
                                        </button>
                                    </div>
                                </th>
                            @endif
                            <th class="text-left py-4 px-6 font-bold">
                                <div class="flex items-center justify-between gap-2">
                                    <span>Cliente</span>
                                    <button
                                        type="button"
                                        class="filter-btn-insumos p-1 rounded hover:bg-blue-500 transition"
                                        data-column="cliente"
                                        title="Filtrar Cliente"
                                    >
                                        <i class="fas fa-filter text-xs"></i>
                                    </button>
                                </div>
                            </th>
                            <th class="text-center py-4 px-6 font-bold">
                                Dias restantes
                            </th>
                            @unless($esGestionReflectivo)
                            <th class="text-center py-4 px-6 font-bold" style="min-width: 220px;">
                                <div class="flex items-center justify-center gap-2">
                                    <span>Estado</span>
                                    <button
                                        type="button"
                                        class="filter-btn-insumos p-1 rounded hover:bg-blue-500 transition"
                                        data-column="estado"
                                        title="Filtrar Estado"
                                    >
                                        <i class="fas fa-filter text-xs"></i>
                                    </button>
                                </div>
                            </th>
                            @endunless
                            <th class="text-center py-4 px-6 font-bold">
                                Novedades
                            </th>
                            <th class="text-center py-4 px-6 font-bold">
                                <div class="flex items-center justify-center gap-2">
                                    <span>Fecha de Inicio</span>
                                    <button
                                        type="button"
                                        class="filter-btn-insumos p-1 rounded hover:bg-blue-500 transition"
                                        data-column="created_at"
                                        title="Filtrar Fecha de Inicio"
                                    >
                                        <i class="fas fa-filter text-xs"></i>
                                    </button>
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $diaLaboralCalculator = app(\App\Application\Services\DiaLaboralCalculator::class);
                        @endphp
                        @forelse($ordenes ?? [] as $orden)
                            <tr class="border-b border-gray-200 hover:bg-gray-50 transition @if(isset($orden->dias_calculados) && $orden->dias_calculados > 0)
                                @if($orden->dias_calculados >= 3) dias-mas-3
                                @else dias-0-3 @endif
                            @endif @if(isset($orden->marcar_plooter) && $orden->marcar_plooter) row-checked @endif @if(!$esGestionReflectivo && isset($orden->esta_completado) && $orden->esta_completado) row-completado @endif @if(isset($orden->estado) && in_array($orden->estado, ['ANULADO', 'Anulada'])) bg-red-100 @endif"
                            data-pedido="{{ strtoupper($orden->numero_pedido ?? '') }}" 
                            data-cliente="{{ strtoupper($orden->cliente ?? '') }}" 
                            data-orden-pedido="{{ $orden->numero_pedido }}"
                            data-recibo="{{ $orden->id ?? '' }}"
                            data-material-id="{{ $orden->id ?? '' }}"
                            data-pedido-produccion-id="{{ $orden->pedido_produccion_id ?? '' }}">
                                <td class="py-4 px-6 text-center" style="min-width: 250px; overflow: visible; @if(!isset($orden->estado) || !in_array($orden->estado, ['ANULADO', 'Anulada']))background: white;@endif position: relative; z-index: 5;">
                                    {{-- Indicador de materiales (punto rojo en esquina izquierda) --}}
                                    @if(isset($orden->tiene_materiales) && $orden->tiene_materiales)
                                        <div 
                                            class="btn-tooltip"
                                            data-tooltip="Contiene {{ $orden->cantidad_materiales }} material(es)"
                                            title="Contiene {{ $orden->cantidad_materiales }} material(es)"
                                            style="position: absolute; left: 8px; top: 50%; transform: translateY(-50%); display: inline-flex; align-items: center; justify-content: center;"
                                        >
                                            <div class="w-3 h-3 bg-red-500 rounded-full animate-pulse" style="box-shadow: 0 0 8px rgba(239, 68, 68, 0.6);"></div>
                                        </div>
                                    @endif

                                    <div class="flex items-center justify-center gap-3" style="display: flex !important; flex-wrap: wrap; overflow: visible;">
                                        {{-- Definir variables primero --}}
                                        @php
                                            $userRole = auth()->user()->role;
                                            $roleName = is_object($userRole) ? $userRole->name : $userRole;
                                            $isPatronista = $roleName === 'patronista';
                                            $isInsumos = $roleName === 'insumos';
                                            $reciboId = $orden->id;
                                            $pedidoProduccionId = $orden->pedido_produccion_id;
                                        @endphp
                                        
                                        {{-- Botón Check (marca) en purple --}}
                                        <button 
                                            class="btn-check-row btn-tooltip p-2 text-purple-600 hover:bg-purple-50 rounded transition @if(isset($orden->marcar_plooter) && $orden->marcar_plooter) checked @endif"
                                            data-insumos-action="toggle-row-check"
                                            data-tooltip="Marcar fila"
                                            title="Marcar fila"
                                        >
                                            <i class="fas fa-check text-lg"></i>
                                        </button>

                                        {{-- Dropdown Ver Recibo / Seguimiento --}}
                                        <button
                                            class="btn-ver-insumos-dropdown btn-tooltip p-2 text-blue-600 hover:bg-blue-50 rounded transition relative"
                                            data-insumos-action="ver-recibo-dropdown"
                                            data-pedido-id="{{ $pedidoProduccionId }}"
                                            data-pedido-produccion-id="{{ $pedidoProduccionId }}"
                                            data-prenda-id="{{ $orden->prenda_id ?? '' }}"
                                            data-prenda-bodega-id="{{ $orden->prenda_bodega_id ?? '' }}"
                                            data-recibo-id="{{ $reciboId }}"
                                            data-tipo-recibo="{{ $orden->tipo_recibo ?? 'COSTURA' }}"
                                            data-numero-recibo="{{ $orden->consecutivo_actual ?? '' }}"
                                            data-consecutivo="{{ $orden->consecutivo_actual ?? '' }}"
                                            data-es-parcial="{{ !empty($orden->es_parcial) ? '1' : '0' }}"
                                            data-pedido-parcial-id="{{ $orden->pedido_parcial_id ?? '' }}"
                                            data-tooltip="Ver recibo o seguimiento"
                                            title="Ver recibo o seguimiento"
                                        >
                                            <i class="fas fa-eye text-lg"></i>
                                        </button>

                                        {{-- Dropdown de Acciones (solo para no-patronistas) --}}
                                        @if(!$isPatronista)
                                            {{-- Boton Enviar a Produccion --}}
                                            @if($esGestionReflectivo && !in_array($orden->estado, ['En Ejecución', 'En Ejecucion']) && strtoupper(trim((string) $orden->area)) === 'INSUMOS')
                                                <button 
                                                    class="btn-enviar-produccion btn-tooltip p-2 text-blue-600 hover:bg-blue-50 rounded transition"
                                                    data-insumos-action="enviar-produccion-reflectivo"
                                                    data-recibo-id="{{ $reciboId }}"
                                                    data-consecutivo="{{ $orden->consecutivo_actual }}"
                                                    data-tooltip="Enviar a produccion"
                                                    title="Enviar a produccion"
                                                >
                                                    <i class="fas fa-paper-plane text-lg"></i>
                                                </button>
                                            @elseif(!$esGestionReflectivo && in_array($orden->estado, ['PENDIENTE_INSUMOS', 'Pendiente_Insumos', 'PENDIENTE_TELA', 'Pendiente Tela', 'PENDIENTE_METRAJE', 'Pendiente Metraje', 'PENDIENTE_PLOTTER', 'Pendiente Plotter', 'INSUMOS_PEDIDOS', 'Insumos Pedidos']))
                                                <button 
                                                    class="btn-enviar-produccion btn-tooltip p-2 text-blue-600 hover:bg-blue-50 rounded transition"
                                                    data-insumos-action="enviar-produccion"
                                                    data-recibo-id="{{ $reciboId }}"
                                                    data-consecutivo="{{ $orden->consecutivo_actual }}"
                                                    data-tooltip="Enviar a produccion"
                                                    title="Enviar a produccion"
                                                >
                                                    <i class="fas fa-paper-plane text-lg"></i>
                                                </button>
                                            @endif
                                            
                                            <button 
                                                class="btn-acciones p-2 text-gray-600 hover:bg-gray-100 rounded transition"
                                                data-insumos-action="acciones-dropdown"
                                                data-pedido-produccion-id="{{ $pedidoProduccionId }}"
                                                data-prenda-id="{{ $orden->prenda_id ?? '' }}"
                                                data-prenda-bodega-id="{{ $orden->prenda_bodega_id ?? '' }}"
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
                                <td class="py-4 px-6 text-center">
                                    @php
                                        $areaClass = '';
                                        $areaText = $orden->area ?? 'N/A';
                                        if ($areaText === 'Corte') {
                                            $areaClass = 'bg-purple-100 text-purple-800';
                                        } elseif ($areaText === 'Creación de Orden' || $areaText === 'Creación de orden') {
                                            $areaClass = 'bg-green-100 text-green-800';
                                            $areaText = 'Creación de Orden';
                                        } elseif ($areaText === 'Costura') {
                                            $areaClass = 'bg-blue-100 text-blue-800';
                                        }
                                    @endphp
                                    <span class="inline-block px-3 py-1 rounded-full text-sm font-semibold {{ $areaClass }}">
                                        {{ $areaText }}
                                    </span>
                                </td>
                                <td class="py-4 px-6">
                                    <span class="font-bold text-blue-600 text-lg">{{ $orden->consecutivo_actual ?? 'N/A' }}</span>
                                </td>
                                    @if($mostrarColumnaPedido)
                                        <td class="py-4 px-6">
                                            <span class="font-medium text-gray-800">{{ $orden->numero_pedido_original ?? $orden->numero_pedido ?? 'N/A' }}</span>
                                        </td>
                                    @endif
                                <td class="py-4 px-6">
                                    <span class="font-medium text-gray-800">{{ $orden->cliente ?? 'N/A' }}</span>
                                </td>
                                <td class="py-4 px-6 text-center">
                                    @php
                                        $diasRestantes = null;
                                        $diasTranscurridos = null;
                                        if (!empty($orden->created_at)) {
                                            try {
                                                $diasTranscurridos = $diaLaboralCalculator->calcular(\Carbon\Carbon::parse($orden->created_at));
                                            } catch (\Throwable $e) {
                                                $diasTranscurridos = (isset($orden->dias_calculados) && is_numeric($orden->dias_calculados))
                                                    ? max(0, (int) $orden->dias_calculados)
                                                    : null;
                                            }
                                        } elseif (isset($orden->dias_calculados) && is_numeric($orden->dias_calculados)) {
                                            $diasTranscurridos = max(0, (int) $orden->dias_calculados);
                                        }
                                        if (isset($orden->dia_de_entrega) && is_numeric($orden->dia_de_entrega) && isset($orden->dias_calculados) && is_numeric($orden->dias_calculados)) {
                                            $diasRestantes = max(0, ((int) $orden->dia_de_entrega) - ((int) $orden->dias_calculados));
                                        }
                                        $fechaEstimadaEntrega = $orden->fecha_estimada_de_entrega ?? null;
                                    @endphp
                                    @if($diasRestantes !== null)
                                        <span class="days-chip">
                                            <span class="days-chip__value">{{ $diasRestantes }} dias</span>
                                            <span class="days-chip__label">habiles restantes</span>
                                            <span class="days-chip__est">Est.: {{ $fechaEstimadaEntrega ?? '-' }}</span>
                                        </span>
                                    @elseif($diasTranscurridos !== null)
                                        <span class="days-chip">
                                            <span class="days-chip__value">{{ $diasTranscurridos }} dias</span>
                                            <span class="days-chip__label">habiles transcurridos</span>
                                            <span class="days-chip__est">Est.: {{ $fechaEstimadaEntrega ?? '-' }}</span>
                                        </span>
                                    @else
                                        <span class="days-chip days-chip--muted">
                                            <span class="days-chip__value">-</span>
                                            <span class="days-chip__est">Est.: {{ $fechaEstimadaEntrega ?? '-' }}</span>
                                        </span>
                                    @endif
                                </td>
                                @unless($esGestionReflectivo)
                                <td class="py-6 px-6 text-center min-h-20" style="min-width: 220px;">
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
                                        } elseif ($orden->estado === 'Anulada' || $orden->estado === 'ANULADO') {
                                            $estadoClass = 'bg-red-100 text-red-800';
                                            $estadoDisplay = 'Anulada';
                                        } elseif ($orden->estado === 'PENDIENTE_INSUMOS' || $orden->estado === 'Pendiente_Insumos') {
                                            $estadoClass = 'bg-amber-500 text-white';
                                            $estadoDisplay = 'Pendiente Insumos';
                                        } elseif ($orden->estado === 'PENDIENTE_TELA' || $orden->estado === 'Pendiente Tela') {
                                            $estadoClass = 'bg-yellow-400 text-gray-900';
                                            $estadoDisplay = 'Pendiente Tela';
                                        } elseif ($orden->estado === 'PENDIENTE_METRAJE' || $orden->estado === 'Pendiente Metraje') {
                                            $estadoClass = 'bg-cyan-500 text-white';
                                            $estadoDisplay = 'Pendiente Metraje';
                                        } elseif ($orden->estado === 'PENDIENTE_PLOTTER' || $orden->estado === 'Pendiente Plotter') {
                                            $estadoClass = 'bg-gray-400 text-white';
                                            $estadoDisplay = 'Pendiente Plotter';
                                        } elseif ($orden->estado === 'DEVUELTO_ASESOR') {
                                            $estadoClass = 'bg-red-500 text-white';
                                            $estadoDisplay = 'Devuelto Asesor';
                                        } elseif ($orden->estado === 'Insumos Pedidos' || $orden->estado === 'INSUMOS_PEDIDOS') {
                                            $estadoClass = 'bg-green-500 text-white';
                                            $estadoDisplay = 'Insumos Pedidos';
                                        } else {
                                            $estadoDisplay = str_replace('_', ' ', $orden->estado ?? 'N/A');
                                        }
                                        
                                        // Determinar si el rol insumos puede editar este estado
                                        $estadosEditablesInsumos = ['PENDIENTE_INSUMOS', 'Pendiente_Insumos', 'PENDIENTE_TELA', 'Pendiente Tela', 'PENDIENTE_METRAJE', 'Pendiente Metraje', 'PENDIENTE_PLOTTER', 'Pendiente Plotter', 'Insumos Pedidos', 'INSUMOS_PEDIDOS'];
                                        $puedeEditarInsumos = in_array($orden->estado, $estadosEditablesInsumos);
                                        $estadoBloqueado = in_array($orden->estado, ['En Ejecución', 'En Ejecucion'], true);
                                        
                                        // Mostrar selector solo si no es insumos, o si es insumos y el estado es editable
                                        $mostrarSelector = !$estadoBloqueado && (
                                            ($currentRoleName !== 'insumos') || ($currentRoleName === 'insumos' && $puedeEditarInsumos)
                                        );
                                    @endphp
                                    
                                    @if($mostrarSelector)
                                        {{-- SELECTOR EDITABLE --}}
                                        <div class="relative block w-full flex items-center justify-center">
                                            <select 
                                                class="estado-select px-2 py-2 rounded-full text-xs font-semibold border border-gray-300 cursor-pointer leading-tight whitespace-nowrap {{ $estadoClass }}"
                                                style="min-height: 2rem; line-height: 1.2; white-space: nowrap; width: 100%; max-width: 350px; outline: none;"
                                                data-recibo-id="{{ $orden->id }}"
                                                data-estado-actual="{{ $orden->estado }}"
                                                data-rol="{{ $currentRoleName }}"
                                                onchange="cambiarEstadoDesdeSelector(this); aplicarEstiloEstadoSelect(this);"
                                            >
                                                @if($currentRoleName === 'insumos')
                                                    {{-- Solo 2 opciones editable para rol insumos --}}
                                                    <option value="PENDIENTE_INSUMOS" {{ in_array($orden->estado, ['PENDIENTE_INSUMOS', 'Pendiente_Insumos']) ? 'selected' : '' }}>Pendiente&#10;Insumos</option>
                                                    <option value="PENDIENTE_TELA" {{ in_array($orden->estado, ['Pendiente Tela', 'PENDIENTE_TELA']) ? 'selected' : '' }}>Pendiente&#10;Tela</option>
                                                    <option value="PENDIENTE_METRAJE" {{ in_array($orden->estado, ['Pendiente Metraje', 'PENDIENTE_METRAJE']) ? 'selected' : '' }}>Pendiente&#10;Metraje</option>
                                                    <option value="PENDIENTE_PLOTTER" {{ in_array($orden->estado, ['Pendiente Plotter', 'PENDIENTE_PLOTTER']) ? 'selected' : '' }}>Pendiente&#10;Plotter</option>
                                                    <option value="INSUMOS_PEDIDOS" {{ in_array($orden->estado, ['Insumos Pedidos', 'INSUMOS_PEDIDOS']) ? 'selected' : '' }}>Insumos&#10;Pedidos</option>
                                                @else
                                                    {{-- Todas las opciones para otros roles --}}
                                                    <option value="No iniciado" {{ $orden->estado === 'No iniciado' ? 'selected' : '' }}>No iniciado</option>
                                                    <option value="En Ejecución" {{ $orden->estado === 'En Ejecución' ? 'selected' : '' }}>En Ejecución</option>
                                                    <option value="PENDIENTE_INSUMOS" {{ in_array($orden->estado, ['PENDIENTE_INSUMOS', 'Pendiente_Insumos']) ? 'selected' : '' }}>Pendiente&#10;Insumos</option>
                                                    <option value="PENDIENTE_TELA" {{ in_array($orden->estado, ['Pendiente Tela', 'PENDIENTE_TELA']) ? 'selected' : '' }}>Pendiente&#10;Tela</option>
                                                    <option value="PENDIENTE_METRAJE" {{ in_array($orden->estado, ['Pendiente Metraje', 'PENDIENTE_METRAJE']) ? 'selected' : '' }}>Pendiente&#10;Metraje</option>
                                                    <option value="PENDIENTE_PLOTTER" {{ in_array($orden->estado, ['Pendiente Plotter', 'PENDIENTE_PLOTTER']) ? 'selected' : '' }}>Pendiente&#10;Plotter</option>
                                                    <option value="INSUMOS_PEDIDOS" {{ in_array($orden->estado, ['Insumos Pedidos', 'INSUMOS_PEDIDOS']) ? 'selected' : '' }}>Insumos&#10;Pedidos</option>
                                                    <option value="DEVUELTO_ASESOR" {{ $orden->estado === 'DEVUELTO_ASESOR' ? 'selected' : '' }}>Devuelto Asesor</option>
                                                    <option value="ANULADO" {{ in_array($orden->estado, ['Anulada', 'ANULADO']) ? 'selected' : '' }}>Anulada</option>
                                                @endif
                                            </select>
                                        </div>
                                    @else
                                        {{-- BADGE ESTÁTICO (SIN EDITAR) --}}
                                        <span class="estado-span inline-block px-3 py-2 rounded-lg text-sm font-semibold {{ $estadoClass }} break-words" data-recibo-id="{{ $orden->id }}">
                                            {{ $estadoDisplay }}
                                        </span>
                                    @endif
                                </td>
                                @endunless

                                <td class="py-4 px-6 text-center">
                                    @php
                                        $motivoDevolucion = trim((string) ($orden->motivo_devolucion ?? ''));
                                        $ultimaNovedadAsesora = trim((string) ($orden->ultima_novedad_asesora ?? ''));
                                        $previewNovedad = $motivoDevolucion !== ''
                                            ? $motivoDevolucion
                                            : ($ultimaNovedadAsesora !== '' ? $ultimaNovedadAsesora : '');
                                    @endphp
                                    @if($previewNovedad !== '')
                                        <button
                                            type="button"
                                            class="text-blue-700 text-xs font-semibold underline hover:text-blue-900 transition"
                                            data-insumos-action="open-novedades-modal"
                                            data-numero-recibo="{{ $orden->consecutivo_actual ?? '' }}"
                                            data-numero-pedido="{{ $orden->numero_pedido_original ?? '' }}"
                                            data-estado-recibo="{{ $orden->estado ?? '' }}"
                                            data-motivo-devolucion="{{ e($motivoDevolucion) }}"
                                            data-ultima-novedad-asesora="{{ e($ultimaNovedadAsesora) }}"
                                            title="Ver detalle de novedades"
                                        >
                                            Dale click
                                        </button>
                                    @else
                                        <span class="text-gray-400 text-xs">-</span>
                                    @endif
                                </td>
                                <td class="py-4 px-6 text-center">
                                    <span class="text-gray-600 text-sm">
                                        {{ $orden->created_at ? \Carbon\Carbon::parse($orden->created_at)->subHours(5)->format('d/m/Y') : 'N/A' }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $totalColumnas }}" class="py-12 px-6 text-center">
                                    <p class="text-xl text-gray-500">No hay órdenes disponibles</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>


    {{-- Modal de Confirmación para Cambiar Estado --}}
    <div id="modalConfirmarCambioEstado" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
        <div style="background: white; border-radius: 12px; padding: 2rem; max-width: 400px; box-shadow: 0 10px 40px rgba(0,0,0,0.2);">
            <h2 style="margin: 0 0 1rem 0; color: #1f2937; font-size: 1.25rem; font-weight: 600;">
                Confirmar Cambio de Estado
            </h2>
            <p style="color: #6b7280; margin: 0 0 1.5rem 0; font-size: 0.95rem;">
                ¿Seguro que quieres cambiar el estado a <strong id="nuevoEstadoText"></strong>?
            </p>
            <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                <button 
                    type="button" 
                    onclick="cancelarCambioEstado()"
                    style="padding: 0.75rem 1.5rem; background: #e5e7eb; color: #374151; border: none; border-radius: 6px; font-weight: 500; cursor: pointer; transition: all 0.2s;"
                    onmouseover="this.style.background='#d1d5db'"
                    onmouseout="this.style.background='#e5e7eb'"
                >
                    Cancelar
                </button>
                <button 
                    type="button" 
                    onclick="confirmarCambioEstado()"
                    style="padding: 0.75rem 1.5rem; background: #3b82f6; color: white; border: none; border-radius: 6px; font-weight: 500; cursor: pointer; transition: all 0.2s;"
                    onmouseover="this.style.background='#2563eb'"
                    onmouseout="this.style.background='#3b82f6'"
                >
                    Confirmar
                </button>
            </div>
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

{{-- Modal para ver orden --}}
<!-- Modal de Detalle de Orden -->
<div id="modal-overlay" data-insumos-action="close-modal-overlay" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.5); backdrop-filter: blur(4px); z-index: 9997; display: none; pointer-events: auto;"></div>

<div id="order-detail-modal-wrapper" style="width: 90%; max-width: 672px; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 9998; pointer-events: auto; display: none;">
    <x-orders-components.order-detail-modal />
</div>

<!-- Modal de Seguimiento del Pedido -->
<x-orders-components.order-tracking-modal />

<!-- Contenedor para dropdowns dinámicos con position fixed -->
<div id="dropdowns-container" style="position: fixed; top: 0; left: 0; z-index: 999999; pointer-events: none;"></div>

@if(app()->isLocal())
<script>
    console.timeEnd('RENDER_TOTAL');
    console.log('[Insumos] Total de órdenes: {{ $ordenes->total() }}');
</script>
@endif

<!-- Scripts para el modal de órdenes (defer para no-críticos) -->
@if(config('features.insumos_materiales_vite_entry'))
    @vite('resources/js/insumos/materiales.entry.js')
@else
<script defer src="{{ asset('js/ordersjs/order-detail-modal-manager.js') }}"></script>
<script defer src="{{ asset('js/asesores/pedidos-detail-modal.js') }}"></script>
<!-- Image Gallery para mostrar fotos en el modal -->
<script defer src="{{ asset('js/orders-scripts/image-gallery-zoom.js') }}"></script>
<script defer src="{{ asset('js/insumos/materiales-page-loader.js') }}?v={{ $assetVersion('js/insumos/materiales-page-loader.js') }}"></script>

<!-- Scripts no-críticos (defer) -->
<script defer src="{{ asset('js/ordersjs/tracking-modal-utils.js') }}?v={{ $assetVersion('js/ordersjs/tracking-modal-utils.js') }}"></script>
<!-- Sistema de Tracking Modular -->
<!-- DAYS SELECTOR HANDLER - DEBE cargarse PRIMERO -->
<script defer src="{{ asset('js/ordersjs/tracking/days-selector-handler.js') }}?v={{ $assetVersion('js/ordersjs/tracking/days-selector-handler.js') }}"></script>
<script defer src="{{ asset('js/ordersjs/tracking/date-utils.js') }}?v={{ $assetVersion('js/ordersjs/tracking/date-utils.js') }}"></script>
<script defer src="{{ asset('js/ordersjs/tracking/days-selector.js') }}?v={{ $assetVersion('js/ordersjs/tracking/days-selector.js') }}"></script>
<script defer src="{{ asset('js/ordersjs/tracking/data-loader.js') }}?v={{ $assetVersion('js/ordersjs/tracking/data-loader.js') }}"></script>
<!-- TRACKING MODAL HANDLER - DEBE cargarse ANTES de ui-components.js -->
<script defer type="module" src="{{ asset('js/ordersjs/tracking-modal-handler.js') }}?v={{ $assetVersion('js/ordersjs/tracking-modal-handler.js') }}"></script>
<script defer src="{{ asset('js/ordersjs/tracking/ui-components.js') }}?v={{ $assetVersion('js/ordersjs/tracking/ui-components.js') }}"></script>
<script defer src="{{ asset('js/ordersjs/tracking/process-manager.js') }}?v={{ $assetVersion('js/ordersjs/tracking/process-manager.js') }}"></script>
<script defer src="{{ asset('js/ordersjs/tracking/area-cards.js') }}?v={{ $assetVersion('js/ordersjs/tracking/area-cards.js') }}"></script>
<script defer src="{{ asset('js/ordersjs/tracking/prendas-renderer.js') }}?v={{ $assetVersion('js/ordersjs/tracking/prendas-renderer.js') }}"></script>
<script defer src="{{ asset('js/ordersjs/tracking/tracking-main.js') }}?v={{ $assetVersion('js/ordersjs/tracking/tracking-main.js') }}"></script>
<script defer src="{{ asset('js/modulos/invoice/InvoiceLazyLoader.js') }}?v={{ $assetVersion('js/modulos/invoice/InvoiceLazyLoader.js') }}"></script>
<script defer src="{{ asset('js/asesores/invoice-from-list.js') }}"></script>
<script defer src="{{ asset('js/asesores/receipt-manager.js') }}"></script>

<!-- Scripts para Recibos/Procesos (SIN defer para carga rápida) -->
<script type="module" src="{{ asset('js/modulos/pedidos-recibos/loader.js') }}?v={{ $assetVersion('js/modulos/pedidos-recibos/loader.js') }}"></script>

@endif

<script>
// Aplicar estilos iniciales a todos los selects de estado.
function aplicarEstilosIniciales() {
    const selectsEstado = document.querySelectorAll('.estado-select');

    selectsEstado.forEach((select) => {
        if (typeof window.aplicarEstiloEstadoSelect === 'function') {
            window.aplicarEstiloEstadoSelect(select);
        }

        // Evitar listeners duplicados.
        if (!select.dataset.estadoStyleBound) {
            select.dataset.estadoStyleBound = '1';
            select.addEventListener('change', function() {
                if (typeof window.aplicarEstiloEstadoSelect === 'function') {
                    window.aplicarEstiloEstadoSelect(this);
                }
            });
        }
    });
}

// Aplicar en primer render.
document.addEventListener('DOMContentLoaded', () => {
    requestAnimationFrame(aplicarEstilosIniciales);
});

// Reaplicar cuando la tabla se refresca por AJAX (búsqueda/filtros/paginación).
document.addEventListener('insumosTableUpdated', () => {
    requestAnimationFrame(aplicarEstilosIniciales);
});
</script>

{{-- Incluir modales de insumos --}}
@include('insumos.materiales.partials.modales-insumos')

<!-- Configuración de rol del usuario y tipo de recibo -->
<script>
    window.userRole = '{{ $currentRoleName }}';
    window.isInsumos = window.userRole === 'insumos';
    window.tipoRecibo = '{{ $esGestionReflectivo ? 'REFLECTIVO' : $tipoReciboActivo }}';
</script>

@endsection
