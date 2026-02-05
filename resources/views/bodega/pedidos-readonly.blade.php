@extends('layouts.bodega-clean')

@section('title', 'Gestión de Pedidos - Bodega')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/bodega.css') }}">
@endpush

@section('content')
<div class="min-h-screen bg-slate-50">
    <!-- Header Corporativo -->
    <div class="bg-white border-b-2 border-slate-300">
        <div class="max-w-7xl mx-auto px-6 py-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-black text-slate-900 tracking-tight">GESTIÓN DE PEDIDOS</h1>
                    <p class="mt-2 text-xs text-slate-600 font-semibold uppercase tracking-wider">Módulo Bodega</p>
                </div>
                <div class="flex items-center space-x-6">
                    <div class="text-right">
                        <p class="text-sm font-bold text-slate-900">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-slate-500 font-medium">{{ auth()->user()->getCurrentRole()?->name ?? 'Sin Rol' }}</p>
                    </div>
                    <div class="relative">
                        <button id="userMenuBtn" onclick="toggleUserMenu()" class="w-10 h-10 bg-slate-200 rounded-lg flex items-center justify-center hover:bg-slate-300 transition cursor-pointer">
                            <svg class="w-6 h-6 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                        </button>
                        
                        <!-- Menú desplegable -->
                        <div id="userMenu" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl border border-slate-200 py-2 z-50">
                            <div class="px-4 py-2 border-b border-slate-100">
                                <p class="text-sm font-semibold text-slate-900">{{ auth()->user()->name }}</p>
                                <p class="text-xs text-slate-500">{{ auth()->user()->getCurrentRole()?->name ?? 'Sin Rol' }}</p>
                            </div>
                            
                            <form method="POST" action="{{ route('logout') }}" class="block">
                                @csrf
                                <button type="submit" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition flex items-center space-x-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                    </svg>
                                    <span>Cerrar sesión</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-6 py-8">
        <!-- Filtros Corporativos -->
        <div class="bg-white border-2 border-slate-300 p-6 mb-8">
            <div class="flex gap-3 items-end">
                <!-- Buscador -->
                <div class="flex-1">
                    <label class="block text-xs font-black text-slate-700 mb-3 uppercase tracking-wider">
                        🔍 Buscar Pedido
                    </label>
                    <input
                        type="text"
                        id="searchInput"
                        placeholder="Nº pedido, asesor, empresa..."
                        class="w-full px-3 py-2 border-2 border-slate-300 text-xs focus:ring-2 focus:ring-slate-500 focus:border-slate-700 outline-none transition bg-slate-50"
                    >
                </div>

                <!-- Botón Limpiar -->
                <button
                    id="btnLimpiar"
                    type="button"
                    onclick="limpiarBuscador()"
                    class="px-4 py-2 bg-slate-600 hover:bg-slate-700 text-white text-xs font-bold uppercase tracking-wider rounded transition border-2 border-slate-600"
                    title="Limpiar búsqueda"
                >
                    🗑️ Limpiar
                </button>

                <!-- Botón Actualizar -->
                <button
                    id="btnActualizar"
                    type="button"
                    onclick="actualizarTabla()"
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold uppercase tracking-wider rounded transition border-2 border-blue-600"
                    title="Actualizar tabla"
                >
                    🔄 Actualizar
                </button>
            </div>
        </div>

        <!-- Tabla Moderna -->
        <div class="bg-white overflow-hidden rounded-lg shadow-sm border border-slate-200">
            <div class="overflow-x-auto">
                <table class="w-full border-collapse">
                    <!-- THEAD -->
                    <thead>
                        <tr>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold text-slate-700 uppercase tracking-widest w-14">Asesor</th>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold text-slate-700 uppercase tracking-widest w-16">Empresa</th>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold text-slate-700 uppercase tracking-widest flex-1">Artículo</th>
                            <th class="px-4 py-3 text-center text-[11px] font-semibold text-slate-700 uppercase tracking-widest w-12">Talla</th>
                            <th class="px-4 py-3 text-center text-[11px] font-semibold text-slate-700 uppercase tracking-widest w-10">Cant.</th>
                            <th class="px-4 py-3 text-center text-[11px] font-semibold text-slate-700 uppercase tracking-widest w-40">Pendientes</th>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold text-slate-700 uppercase tracking-widest w-40">Observaciones</th>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold text-slate-700 uppercase tracking-widest w-20">Fecha</th>
                            <th class="px-4 py-3 text-center text-[11px] font-semibold text-slate-700 uppercase tracking-widest w-56">Área / Estado</th>
                        </tr>
                    </thead>
                    
                    <tbody id="pedidosTableBody" class="divide-y divide-slate-200">
                        @forelse($pedidosAgrupados as $numeroPedido => $items)
                            @if(!empty($items))
                            <!-- FILA SEPARADORA DE PEDIDO -->
                            @php
                                $primeraAsesora = $items[0]['asesor'] ?? '';
                                $firstItemEmpresa = $items[0]['empresa'] ?? '';
                                // Verificar estado del PedidoProduccion
                                $estadoPedidoProduccion = $items[0]['estado_pedido_produccion'] ?? null;
                                $esAnulada = strtoupper(trim($estadoPedidoProduccion)) === 'ANULADA';
                            @endphp
                            <tr class="pedido-row pedido-header"
                                data-numero-pedido="{{ $numeroPedido }}"
                                data-asesor="{{ strtolower($primeraAsesora) }}"
                                data-empresa="{{ strtolower($firstItemEmpresa) }}"
                                data-search="{{ strtolower($numeroPedido . ' ' . $primeraAsesora . ' ' . $firstItemEmpresa) }}"
                                @if($esAnulada)
                                    style="background-color: rgba(147, 51, 234, 0.2);"
                                @endif
                            >
                                <td colspan="9" class="px-4 py-3" style="font-family: 'Poppins', sans-serif;">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-3">
                                            <div class="w-1 h-6 bg-gradient-to-b @if($esAnulada) from-purple-600 to-purple-700 @else from-blue-500 to-blue-600 @endif rounded"></div>
                                            <div style="font-family: 'Poppins', sans-serif;">
                                                <span class="font-bold @if($esAnulada) text-purple-700 @else text-slate-900 @endif text-sm" style="font-family: 'Poppins', sans-serif;">PEDIDO #{!! $numeroPedido !!}</span>
                                                <span class="ml-4 text-[11px] @if($esAnulada) text-purple-600 @else text-slate-500 @endif font-medium" style="font-family: 'Poppins', sans-serif;">
                                                    {{ isset($items[0]['fecha_pedido']) ? \Carbon\Carbon::parse($items[0]['fecha_pedido'])->format('d-m-Y') : 'N/A' }}
                                                    @if($esAnulada)
                                                        <span class="ml-2 inline-flex items-center px-2 py-1 text-[10px] font-bold bg-red-100 text-red-700 uppercase tracking-wider rounded-full">
                                                            ❌ Anulado 
                                                            @if($items[0]['nombre_asesor_anulacion'] ?? false)
                                                                por el asesor {{ $items[0]['nombre_asesor_anulacion'] }}
                                                            @endif
                                                        </span>
                                                    @endif
                                                </span>
                                            </div>
                                        </div>
                                        <div class="flex items-center space-x-3">
                                            @php
                                                $allDelivered = collect($items)->every(fn($item) => $item['estado'] === 'Entregado');
                                                $anyDelayed = collect($items)->some(fn($item) => $item['estado'] === 'retrasado');
                                                $tuvoCambios = collect($items)->some(fn($item) => $item['tuvo_cambios_recientes'] ?? false);
                                                $firstItemId = $items[0]['id'] ?? null;
                                            @endphp
                                            
                                            @if($allDelivered)
                                                <span class="inline-flex items-center px-3 py-1.5 text-[11px] font-semibold bg-green-50 text-green-700 uppercase tracking-wider rounded">
                                                    ✓ Entregado
                                                </span>
                                            @elseif($anyDelayed)
                                                <span class="inline-flex items-center px-3 py-1.5 text-[11px] font-semibold bg-red-50 text-red-700 uppercase tracking-wider rounded">
                                                    ⚠ Retrasado
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-3 py-1.5 text-[11px] font-semibold bg-amber-50 text-amber-700 uppercase tracking-wider rounded">
                                                    ⏳ Pendiente
                                                </span>
                                            @endif
                                            
                                            @if($tuvoCambios)
                                                <span class="inline-flex items-center px-3 py-1.5 text-[11px] font-semibold bg-cyan-50 text-cyan-700 uppercase tracking-wider rounded" title="Este pedido ha sido modificado recientemente">
                                                    🔄 Actualizado
                                                </span>
                                            @endif
                                            
                                            @if($firstItemId)
                                                @php
                                                    $numPedidoReal = $items[0]['numero_pedido'] ?? $numeroPedido;
                                                @endphp
                                                <button 
                                                    type="button"
                                                    onclick="abrirModalFactura({{ $firstItemId }})"
                                                    class="px-3 py-1.5 text-[11px] font-semibold bg-blue-50 text-blue-700 border border-blue-200 hover:bg-blue-100 transition rounded"
                                                    style="font-family: 'Poppins', sans-serif;">
                                                    👁 Ver
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                            </tr>

                            <!-- FILAS DE ARTÍCULOS DEL PEDIDO -->
                            @foreach($items as $item)
                                <tr class="pedido-row"
                                    data-numero-pedido="{{ $numeroPedido }}"
                                    data-asesor="{{ strtolower($item['asesor']) }}"
                                    data-empresa="{{ strtolower($item['empresa']) }}"
                                    data-estado="{{ $item['estado'] }}"
                                    data-tipo="{{ $item['tipo'] ?? 'prenda' }}"
                                    data-search="{{ strtolower($numeroPedido . ' ' . $item['asesor'] . ' ' . $item['empresa']) }}"
                                    @if($esAnulada)
                                        style="background-color: rgba(147, 51, 234, 0.1);"
                                    @elseif($item['estado'] === 'Entregado')
                                        style="background-color: rgba(37, 99, 235, 0.05);"
                                    @endif
                                >
                                    <!-- ASESOR -->
                                    @if(($item['asesor_rowspan'] ?? 0) > 0)
                                    <td class="px-2 py-2 text-[10px] font-semibold text-slate-900" rowspan="{{ $item['asesor_rowspan'] }}" data-asesor="{{ $item['asesor'] }}">
                                        {{ $item['asesor'] }}
                                    </td>
                                    @endif
                                    
                                    <!-- EMPRESA -->
                                    @if(($item['empresa_rowspan'] ?? 0) > 0)
                                    <td class="px-2 py-2 text-[10px] font-semibold text-slate-700 uppercase tracking-wide" rowspan="{{ $item['empresa_rowspan'] }}" data-empresa="{{ $item['empresa'] }}">
                                        {{ $item['empresa'] }}
                                    </td>
                                    @endif
                                    
                                    <!-- DESCRIPCIÓN (agrupa prenda + variantes) -->
                                    @if(($item['descripcion_rowspan'] ?? 0) > 0)
                                    <td class="px-4 py-3 text-xs text-slate-700" rowspan="{{ $item['descripcion_rowspan'] }}" data-prenda-nombre="{{ $item['descripcion']['nombre_prenda'] ?? $item['descripcion']['nombre'] ?? 'Prenda' }}">
                                        @if($item['tipo'] === 'prenda')
                                            @php
                                                $desc = $item['descripcion'];
                                                $nombre = $desc['nombre_prenda'] ?? $desc['nombre'] ?? 'Prenda sin nombre';
                                                $tela = $desc['tela'] ?? null;
                                                $color = $desc['color'] ?? null;
                                                $variantes = $desc['variantes'] ?? [];
                                                $primeraVariante = count($variantes) > 0 ? $variantes[0] : null;
                                                $manga = $primeraVariante['manga'] ?? null;
                                                $broche = $primeraVariante['broche'] ?? null;
                                                $bolsillos = $primeraVariante['bolsillos'] ?? false;
                                                $manga_obs = $primeraVariante['manga_obs'] ?? '';
                                                $broche_obs = $primeraVariante['broche_obs'] ?? '';
                                                $bolsillos_obs = $primeraVariante['bolsillos_obs'] ?? '';
                                            @endphp
                                            <div class="font-bold text-slate-900 mb-2">{{ $nombre }}</div>
                                            
                                            @if($tela || $color)
                                                <div class="text-slate-700 text-xs mb-1">
                                                    @if($tela && $color)
                                                        • Tela: {{ $tela }} - Color: {{ $color }}
                                                    @elseif($tela)
                                                        • Tela: {{ $tela }}
                                                    @else
                                                        • Color: {{ $color }}
                                                    @endif
                                                </div>
                                            @endif
                                            
                                            @if($manga || $broche || $bolsillos)
                                                <div class="text-slate-700 text-xs space-y-0.5">
                                                    @if($manga)
                                                        <div>• Manga: {{ $manga }}{{ $manga_obs && trim($manga_obs) !== '' ? " ($manga_obs)" : '' }}</div>
                                                    @endif
                                                    @if($broche)
                                                        <div>• {{ $broche }}{{ $broche_obs && trim($broche_obs) !== '' ? " ($broche_obs)" : '' }}</div>
                                                    @endif
                                                    @if($bolsillos)
                                                        <div>• Bolsillos{{ $bolsillos_obs && trim($bolsillos_obs) !== '' ? " ($bolsillos_obs)" : '' }}</div>
                                                    @endif
                                                </div>
                                            @endif
                                        @else
                                            {{-- EPP --}}
                                            @php
                                                $desc = $item['descripcion'];
                                                $nombre = $desc['nombre_completo'] ?? $desc['nombre'] ?? 'EPP sin nombre';
                                                $codigo = $desc['codigo'] ?? null;
                                            @endphp
                                            <div class="font-semibold text-slate-900">
                                                {{ $nombre }}{{ $codigo ? " ($codigo)" : '' }}
                                            </div>
                                        @endif
                                    </td>
                                    @endif
                                    
                                    <!-- TALLA -->
                                    <td class="px-2 py-2 text-[10px] text-center text-slate-700" data-talla="{{ $item['talla'] ?? '—' }}">
                                        @if($item['tipo'] === 'epp')
                                            {{-- EPP: no mostrar talla (es un hash interno) --}}
                                        @else
                                            {{ $item['talla'] ?? '—' }}
                                        @endif
                                    </td>
                                    
                                    <!-- CANTIDAD -->
                                    <td class="px-2 py-2 text-center text-[10px] font-bold text-slate-900 font-mono" data-cantidad="{{ $item['cantidad_total'] }}">
                                        {{ $item['cantidad_total'] }}
                                    </td>
                                    
                                    <!-- PENDIENTES -->
                                    <td class="px-2 py-2">
                                        <textarea
                                            class="pendientes-input w-full px-1.5 py-1 border-2 border-slate-300 text-[10px] focus:ring-2 focus:ring-slate-500 focus:border-slate-700 outline-none transition resize-none
                                                @if($item['estado'] === 'Entregado')
                                                    bg-blue-50
                                                @else
                                                    bg-slate-50
                                                @endif"
                                            style="font-family: 'Poppins', sans-serif;"
                                            data-numero-pedido="{{ $item['numero_pedido'] }}"
                                            data-talla="{{ $item['talla'] }}"
                                            placeholder="Pendientes..."
                                            rows="1"
                                            disabled
                                        >{{ $item['pendientes'] ?? '' }}</textarea>
                                    </td>
                                    
                                    <!-- OBSERVACIONES -->
                                    <td class="px-2 py-2">
                                        <textarea
                                            class="observaciones-input w-full px-1.5 py-1 border-2 border-slate-300 text-[10px] focus:ring-2 focus:ring-slate-500 focus:border-slate-700 outline-none transition resize-none
                                                @if($item['estado'] === 'Entregado')
                                                    bg-blue-50
                                                @else
                                                    bg-slate-50
                                                @endif"
                                            style="font-family: 'Poppins', sans-serif;"
                                            data-numero-pedido="{{ $item['numero_pedido'] }}"
                                            data-talla="{{ $item['talla'] }}"
                                            placeholder="Notas..."
                                            rows="1"
                                            disabled
                                        >{{ $item['observaciones'] ?? '' }}</textarea>
                                    </td>
                                    
                                    <!-- FECHA ENTREGA -->
                                    <td class="px-2 py-2">
                                        <input
                                            type="date"
                                            class="fecha-input w-full px-1.5 py-1 border-2 border-slate-300 text-[10px] focus:ring-2 focus:ring-slate-500 focus:border-slate-700 outline-none transition bg-slate-50
                                                @if($item['estado'] === 'Entregado')
                                                    bg-blue-50
                                                @endif"
                                            style="font-family: 'Poppins', sans-serif;"
                                            value="{{ $item['fecha_entrega'] ?? '' }}"
                                            data-numero-pedido="{{ $item['numero_pedido'] }}"
                                            data-talla="{{ $item['talla'] }}"
                                            disabled
                                        >
                                    </td>
                                    
                                    <!-- ESTADO (SOLO LECTURA - sin área) -->
                                    <td class="px-3 py-2">
                                        <div class="space-y-1.5">
                                            <!-- SELECTOR ESTADO - HABILITADO -->
                                            <select
                                                class="estado-select-readonly w-full px-2 py-1.5 border-2 border-slate-400 bg-white text-slate-900 text-[13px] font-bold uppercase tracking-wide hover:bg-slate-50 transition rounded-lg cursor-pointer"
                                                style="font-family: 'Poppins', sans-serif; min-height: 35px; font-size: 13px; line-height: 1.4; background-color: white; color: #0f172a !important;"
                                                data-numero-pedido="{{ $item['numero_pedido'] }}"
                                                data-talla="{{ $item['talla'] }}"
                                                data-bodega-id="{{ $item['bodega_id'] ?? '' }}"
                                                data-original-estado="{{ $item['estado'] ?? '' }}"
                                            >
                                                <option value="">ESTADO</option>
                                                <option value="Pendiente">PENDIENTE</option>
                                                <option value="Entregado">ENTREGADO</option>
                                            </select>
                                            
                                            <!-- BOTÓN GUARDAR -->
                                            <button
                                                type="button"
                                                class="guardar-estado-btn w-full px-2 py-1.5 border-2 border-green-500 bg-green-50 text-green-700 text-[12px] font-bold uppercase tracking-wide hover:bg-green-100 transition rounded-lg cursor-pointer"
                                                style="font-family: 'Poppins', sans-serif; font-size: 12px; line-height: 1.4;"
                                                data-numero-pedido="{{ $item['numero_pedido'] }}"
                                                data-talla="{{ $item['talla'] }}"
                                            >
                                                💾 Guardar
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            @endif
                        @empty
                            <tr>
                                <td colspan="9" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <svg class="h-12 w-12 text-slate-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        <p class="text-slate-500 font-bold text-sm uppercase tracking-wide">Sin pedidos disponibles</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- PAGINACIÓN -->
        <div class="mt-8 bg-white border-2 border-slate-300 p-6">
            <div class="flex items-center justify-between">
                <div class="text-sm text-slate-600 font-semibold">
                    Total de pedidos: <span class="font-black text-slate-900">{{ $totalPedidos }}</span>
                </div>
                
                @if($paginacion->lastPage() > 1)
                    <div class="flex items-center space-x-2">
                        {{-- Botón Primera Página --}}
                        @if($paginacion->onFirstPage())
                            <span class="px-3 py-2 text-xs font-bold bg-slate-200 text-slate-500 rounded cursor-not-allowed">← Primera</span>
                        @else
                            <a href="{{ $paginacion->url(1) }}" class="px-3 py-2 text-xs font-bold bg-white border-2 border-slate-300 text-slate-900 hover:bg-slate-100 transition rounded">← Primera</a>
                        @endif

                        {{-- Botón Página Anterior --}}
                        @if($paginacion->onFirstPage())
                            <span class="px-3 py-2 text-xs font-bold bg-slate-200 text-slate-500 rounded cursor-not-allowed">‹ Anterior</span>
                        @else
                            <a href="{{ $paginacion->previousPageUrl() }}" class="px-3 py-2 text-xs font-bold bg-white border-2 border-slate-300 text-slate-900 hover:bg-slate-100 transition rounded">‹ Anterior</a>
                        @endif

                        {{-- Números de Página --}}
                        <div class="flex items-center space-x-1">
                            @php
                                $inicio = max(1, $paginacion->currentPage() - 2);
                                $fin = min($paginacion->lastPage(), $paginacion->currentPage() + 2);
                            @endphp

                            @if($inicio > 1)
                                <span class="px-1 text-slate-400">...</span>
                            @endif

                            @for($i = $inicio; $i <= $fin; $i++)
                                @if($i == $paginacion->currentPage())
                                    <span class="px-3 py-2 text-xs font-black bg-slate-900 text-white rounded">{{ $i }}</span>
                                @else
                                    <a href="{{ $paginacion->url($i) }}" class="px-3 py-2 text-xs font-bold bg-white border-2 border-slate-300 text-slate-900 hover:bg-slate-100 transition rounded">{{ $i }}</a>
                                @endif
                            @endfor

                            @if($fin < $paginacion->lastPage())
                                <span class="px-1 text-slate-400">...</span>
                            @endif
                        </div>

                        {{-- Botón Página Siguiente --}}
                        @if($paginacion->hasMorePages())
                            <a href="{{ $paginacion->nextPageUrl() }}" class="px-3 py-2 text-xs font-bold bg-white border-2 border-slate-300 text-slate-900 hover:bg-slate-100 transition rounded">Siguiente ›</a>
                        @else
                            <span class="px-3 py-2 text-xs font-bold bg-slate-200 text-slate-500 rounded cursor-not-allowed">Siguiente ›</span>
                        @endif

                        {{-- Botón Última Página --}}
                        @if($paginacion->hasMorePages())
                            <a href="{{ $paginacion->url($paginacion->lastPage()) }}" class="px-3 py-2 text-xs font-bold bg-white border-2 border-slate-300 text-slate-900 hover:bg-slate-100 transition rounded">Última ›</a>
                        @else
                            <span class="px-3 py-2 text-xs font-bold bg-slate-200 text-slate-500 rounded cursor-not-allowed">Última ›</span>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Toast Notification Corporativo -->
<div id="toast" class="fixed bottom-4 right-4 px-5 py-3 bg-slate-900 text-white rounded text-sm font-bold uppercase tracking-wider hidden flex items-center space-x-3 z-50 border-2 border-slate-700">
    <svg class="h-4 w-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
    </svg>
    <span id="toastMessage">✓ Operación completada</span>
</div>

<!-- Modal de Factura -->
<div id="modalFactura" class="hidden fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-9999 overflow-auto" style="z-index: 9999;">
    <div class="bg-white rounded-lg shadow-2xl max-w-4xl w-full mx-4 my-8">
        <!-- Header -->
        <div class="bg-slate-900 px-6 py-4 border-b border-slate-200 flex justify-between items-center sticky top-0">
            <h2 class="text-lg font-semibold text-white">📋 Pedido</h2>
            <button onclick="cerrarModalFactura()" 
                    class="text-white hover:text-slate-200 text-2xl leading-none">
                ✕
            </button>
        </div>
        
        <!-- Body -->
        <div id="facturaContenido" class="px-6 py-6 overflow-y-auto" style="max-height: calc(100vh - 200px)">
            <div class="flex justify-center items-center py-12">
                <span class="text-slate-500">⏳ Cargando factura...</span>
            </div>
        </div>
    </div>
</div>

<!-- MODAL DE ÉXITO -->
<div 
    id="modalExito" 
    style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.5); z-index: 1000; justify-content: center; align-items: center;"
>
    <div style="background-color: white; border-radius: 12px; padding: 40px; max-width: 420px; box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3); text-align: center;">
        <div style="margin-bottom: 24px;">
            <svg style="width: 72px; height: 72px; margin: 0 auto; color: #22c55e;" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
            </svg>
        </div>
        <h2 style="margin: 0 0 8px 0; font-size: 24px; font-weight: 700; color: #0f172a;">¡Éxito!</h2>
        <p id="modalMensajeExito" style="margin: 0 0 32px 0; color: #64748b; font-size: 15px; line-height: 1.5;">Cambios guardados correctamente</p>
        <button 
            id="btnCerrarModalExito"
            style="background-color: #22c55e; color: white; border: none; padding: 11px 28px; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 15px; transition: background-color 0.2s;"
            onmouseover="this.style.backgroundColor='#16a34a'"
            onmouseout="this.style.backgroundColor='#22c55e'"
        >
            Aceptar
        </button>
    </div>
</div>

@endsection

@push('scripts')
<script>
/**
 * Auto-resize textareas para Pendientes y Observaciones
 */
document.addEventListener('DOMContentLoaded', function() {
    const textareas = document.querySelectorAll('.pendientes-input, .observaciones-input');
    
    function autoResizeTextarea(textarea) {
        textarea.style.height = 'auto';
        textarea.style.height = (textarea.scrollHeight) + 'px';
    }
    
    // Aplicar auto-resize inicial
    textareas.forEach(textarea => {
        autoResizeTextarea(textarea);
        
        // Aplicar auto-resize al escribir
        textarea.addEventListener('input', function() {
            autoResizeTextarea(this);
        });
        
        // Aplicar auto-resize cuando se carga la página (si hay contenido previo)
        textarea.addEventListener('change', function() {
            autoResizeTextarea(this);
        });
    });

    // Manejar guardado de estado para rol Costura-Bodega / EPP-Bodega
    document.querySelectorAll('.guardar-estado-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const numeroPedido = this.dataset.numeroPedido;
            const talla = this.dataset.talla;
            const estadoSelect = document.querySelector(
                `.estado-select-readonly[data-numero-pedido="${numeroPedido}"][data-talla="${talla}"]`
            );
            
            if (!estadoSelect) {
                alert('Error: No se encontró el selector de estado');
                return;
            }

            const nuevoEstado = estadoSelect.value;
            if (!nuevoEstado) {
                alert('Error: Debe seleccionar un estado');
                return;
            }

            // Obtener los datos adicionales del formulario
            const pendientesInput = document.querySelector(
                `.pendientes-input[data-numero-pedido="${numeroPedido}"][data-talla="${talla}"]`
            );
            const observacionesInput = document.querySelector(
                `.observaciones-input[data-numero-pedido="${numeroPedido}"][data-talla="${talla}"]`
            );
            const fechaInput = document.querySelector(
                `.fecha-input[data-numero-pedido="${numeroPedido}"][data-talla="${talla}"]`
            );

            const datos = {
                numero_pedido: numeroPedido,
                talla: talla,
                estado_bodega: nuevoEstado,
                pendientes: pendientesInput ? pendientesInput.value : '',
                observaciones_bodega: observacionesInput ? observacionesInput.value : '',
                fecha_entrega: fechaInput ? fechaInput.value : ''
            };

            // Enviar al servidor
            fetch('/gestion-bodega/detalles-talla/guardar', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: JSON.stringify(datos)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Mostrar modal de éxito
                    const modal = document.getElementById('modalExito');
                    const mensaje = document.getElementById('modalMensajeExito');
                    if (modal && mensaje) {
                        mensaje.textContent = '✓ Estado guardado correctamente';
                        modal.style.display = 'flex';
                        document.getElementById('btnCerrarModalExito').onclick = function() {
                            modal.style.display = 'none';
                            // Recargar la página
                            location.reload();
                        };
                    }
                } else {
                    alert('Error: ' + (data.message || 'No se pudo guardar el estado'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al guardar: ' + error.message);
            });
        });
    });
});
</script>
<script src="{{ asset('js/bodega-pedidos.js') }}?v={{ time() }}"></script>
@endpush
