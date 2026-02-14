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
                         Buscar Pedido
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
                     Actualizar
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
                            <th class="px-4 py-3 text-left text-[11px] font-semibold text-slate-700 uppercase tracking-widest w-20">Fecha Pedido</th>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold text-slate-700 uppercase tracking-widest w-20">Fecha Entrega</th>
                            <th class="px-4 py-3 text-center text-[11px] font-semibold text-slate-700 uppercase tracking-widest w-56">Área / Estado</th>
                        </tr>
                    </thead>
                    
                    <tbody id="pedidosTableBody" class="divide-y divide-slate-200">
                        @forelse($pedidosAgrupados as $numeroPedido => $items)
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
                                <td colspan="10" class="px-4 py-3" style="font-family: 'Poppins', sans-serif;">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-3">
                                            <div class="w-1 h-6 bg-gradient-to-b @if($esAnulada) from-purple-600 to-purple-700 @else from-blue-500 to-blue-600 @endif rounded"></div>
                                            <div style="font-family: 'Poppins', sans-serif;">
                                                <span class="font-bold @if($esAnulada) text-purple-700 @else text-slate-900 @endif text-sm" style="font-family: 'Poppins', sans-serif;">PEDIDO #{!! $numeroPedido !!}</span>
                                                <span class="ml-4 text-[11px] @if($esAnulada) text-purple-600 @else text-slate-500 @endif font-medium" style="font-family: 'Poppins', sans-serif;">
                                                    {{ \Carbon\Carbon::parse($items[0]['fecha_pedido'])->format('d-m-Y') }}
                                                    @if($esAnulada)
                                                        <span class="ml-2 inline-flex items-center px-2 py-1 text-[10px] font-bold bg-red-100 text-red-700 uppercase tracking-wider rounded-full">
                                                             Anulado 
                                                            @if($items[0]['nombre_asesor_anulacion'])
                                                                por el asesor {{ $items[0]['nombre_asesor_anulacion'] }}
                                                            @endif
                                                        </span>
                                                    @endif
                                                </span>
                                            </div>
                                        </div>
                                        <div class="flex items-center space-x-3">
                                            @php
                                                $allDelivered = collect($items)->every(fn($item) => ($item['estado_bodega'] ?? null) === 'Entregado');
                                                $anyDelayed = collect($items)->some(fn($item) => ($item['estado_bodega'] ?? null) === 'retrasado');
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
                                                     Actualizado
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
                                    data-estado="{{ $item['estado_bodega'] }}"
                                    data-tipo="{{ $item['tipo'] ?? 'prenda' }}"
                                    data-search="{{ strtolower($numeroPedido . ' ' . $item['asesor'] . ' ' . $item['empresa']) }}"
                                    @if($esAnulada)
                                        style="background-color: rgba(147, 51, 234, 0.1);"
                                    @elseif($item['estado_bodega'] === 'Entregado')
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
                                            {{-- EPP: mostrar talla hash (necesario para tiempo real) --}}
                                            <span class="text-xs text-slate-500 font-mono">{{ substr($item['talla'] ?? '—', 0, 8) }}</span>
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
                                                @if($item['estado_bodega'] === 'Entregado')
                                                    bg-blue-50
                                                @else
                                                    bg-slate-50
                                                @endif"
                                            style="font-family: 'Poppins', sans-serif;"
                                            data-numero-pedido="{{ $item['numero_pedido'] }}"
                                            data-talla="{{ $item['talla'] }}"
                                            placeholder="Pendientes..."
                                            rows="1"
                                        >{{ $item['pendientes'] ?? '' }}</textarea>
                                    </td>
                                    
                                    <!-- OBSERVACIONES -->
                                    <td class="px-2 py-2">
                                        <div class="flex gap-1">
                                            <textarea
                                                class="observaciones-input flex-1 px-1.5 py-1 border-2 border-slate-300 text-[10px] focus:ring-2 focus:ring-slate-500 focus:border-slate-700 outline-none transition resize-none
                                                    @if($item['estado_bodega'] === 'Entregado')
                                                        bg-blue-50
                                                    @else
                                                        bg-slate-50
                                                    @endif"
                                                style="font-family: 'Poppins', sans-serif;"
                                                data-numero-pedido="{{ $item['numero_pedido'] }}"
                                                data-talla="{{ $item['talla'] }}"
                                                data-updated-at="{{ isset($item['updated_at']) ? $item['updated_at']->format('Y-m-d H:i:s') : now()->format('Y-m-d H:i:s') }}"
                                                placeholder="Notas..."
                                                rows="1"
                                                readonly
                                            ></textarea>
                                            @php
                                                $nombreItem = '';
                                                if($item['tipo'] === 'prenda') {
                                                    $nombreItem = $item['descripcion']['nombre_prenda'] ?? $item['descripcion']['nombre'] ?? 'Prenda';
                                                } else {
                                                    $nombreItem = $item['descripcion']['nombre_completo'] ?? $item['descripcion']['nombre'] ?? 'EPP';
                                                }
                                            @endphp
                                            <button
                                                type="button"
                                                onclick="abrirModalNotas('{{ $item['numero_pedido'] }}', '{{ $item['talla'] }}', '{{ addslashes($nombreItem) }}', '{{ $item['tipo'] }}', '{{ $item['talla'] }}')"
                                                class="px-2 py-1 bg-blue-500 hover:bg-blue-600 text-white text-[10px] font-bold rounded transition whitespace-nowrap"
                                                title="Ver/agregar notas"
                                            >
                                                💬
                                            </button>
                                        </div>
                                    </td>
                                    
                                    <!-- FECHA PEDIDO -->
                                    <td class="px-2 py-2">
                                        <input
                                            type="date"
                                            class="fecha-pedido-input w-full px-1.5 py-1 border-2 border-slate-300 text-[10px] focus:ring-2 focus:ring-slate-500 focus:border-slate-700 outline-none transition bg-slate-50
                                                @if($item['estado_bodega'] === 'Entregado')
                                                    bg-blue-50
                                                @endif"
                                            style="font-family: 'Poppins', sans-serif;"
                                            value="{{ $item['fecha_pedido'] ?? '' }}"
                                            data-numero-pedido="{{ $item['numero_pedido'] }}"
                                            data-talla="{{ $item['talla'] }}"
                                            data-updated-at="{{ isset($item['updated_at']) ? $item['updated_at']->format('Y-m-d H:i:s') : now()->format('Y-m-d H:i:s') }}"
                                        >
                                    </td>
                                    
                                    <!-- FECHA ENTREGA -->
                                    <td class="px-2 py-2">
                                        <input
                                            type="date"
                                            class="fecha-input w-full px-1.5 py-1 border-2 border-slate-300 text-[10px] focus:ring-2 focus:ring-slate-500 focus:border-slate-700 outline-none transition bg-slate-50
                                                @if($item['estado_bodega'] === 'Entregado')
                                                    bg-blue-50
                                                @endif"
                                            style="font-family: 'Poppins', sans-serif;"
                                            value="{{ $item['fecha_entrega'] ?? '' }}"
                                            data-numero-pedido="{{ $item['numero_pedido'] }}"
                                            data-talla="{{ $item['talla'] }}"
                                            @if($item['estado_bodega'] === 'Entregado')
                                                disabled
                                            @endif
                                        >
                                    </td>
                                    
                                    <!-- ÁREA / ESTADO (COLUMNA GRANDE APILADA) -->
                                    <td class="px-3 py-2">
                                        <div class="space-y-1.5">
                                            <!-- SELECTOR ÁREA -->
                                            <select
                                                class="area-select w-full px-2 py-1.5 border-2 border-slate-400 bg-white text-slate-900 text-[13px] font-bold uppercase tracking-wide hover:bg-slate-50 transition rounded-lg cursor-pointer"
                                                style="font-family: 'Poppins', sans-serif; min-height: 35px; font-size: 13px; line-height: 1.4; background-color: white; color: #0f172a !important;"
                                                data-numero-pedido="{{ $item['numero_pedido'] }}"
                                                data-talla="{{ $item['talla'] }}"
                                                data-original-area="{{ $item['area'] ?? '' }}"
                                            >
                                                <option value="">ÁREA</option>
                                                <option value="Costura" {{ ($item['area'] ?? null) === 'Costura' ? 'selected' : '' }}>COSTURA</option>
                                                <option value="EPP" {{ ($item['area'] ?? null) === 'EPP' ? 'selected' : '' }}>EPP</option>
                                            </select>

                                            <!-- SELECTOR ESTADO -->
                                            <select
                                                class="estado-select w-full px-2 py-1.5 border-2 border-slate-400 bg-white text-slate-900 text-[13px] font-bold uppercase tracking-wide hover:bg-slate-50 transition rounded-lg cursor-pointer"
                                                style="font-family: 'Poppins', sans-serif; min-height: 35px; font-size: 13px; line-height: 1.4; background-color: white; color: #0f172a !important;"
                                                data-numero-pedido="{{ $item['numero_pedido'] }}"
                                                data-talla="{{ $item['talla'] }}"
                                                data-prenda-nombre="{{ $item['prenda_nombre_actual'] ?? '' }}"
                                                data-cantidad="{{ $item['cantidad_total'] ?? 0 }}"
                                                data-original-estado="{{ $item['estado_bodega'] ?? '' }}"
                                            >
                                                <option value="">ESTADO</option>
                                                <option value="Pendiente" {{ ($item['estado_bodega'] ?? null) === 'Pendiente' ? 'selected' : '' }}>PENDIENTE</option>
                                                <option value="Entregado" {{ ($item['estado_bodega'] ?? null) === 'Entregado' ? 'selected' : '' }}>ENTREGADO</option>
                                                @if(auth()->user()->hasRole(['Bodeguero', 'Admin', 'SuperAdmin']))
                                                <option value="Anulado" {{ ($item['estado_bodega'] ?? null) === 'Anulado' ? 'selected' : '' }}>ANULADO</option>
                                                @endif
                                            </select>

                                            <!-- BOTÓN GUARDAR -->
                                            <button
                                                type="button"
                                                onclick="guardarFilaCompleta(this, '{{ $item['numero_pedido'] }}', '{{ $item['talla'] }}')"
                                                class="w-full px-2 py-2 bg-green-500 hover:bg-green-600 text-white text-[12px] font-bold uppercase tracking-wide rounded-lg transition"
                                                style="font-family: 'Poppins', sans-serif;"
                                            >
                                                💾 Guardar
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
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
<div id="toast" class="fixed bottom-4 right-4 px-5 py-3 bg-slate-900 text-white rounded text-sm font-bold uppercase tracking-wider hidden flex items-center space-x-3 z-[99999] border-2 border-slate-700" style="z-index: 99999;">
    <svg class="h-4 w-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
    </svg>
    <span id="toastMessage">✓ Operación completada</span>
</div>

 <!-- Modal de Factura -->
 <div id="modalFactura" class="hidden fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-9999 overflow-auto" style="z-index: 100000;">
    <div class="bg-white rounded-lg shadow-2xl max-w-4xl w-full mx-4 my-8">
        <!-- Header -->
        <div class="bg-slate-900 px-6 py-4 border-b border-slate-200 flex justify-between items-center sticky top-0">
            <h2 class="text-lg font-semibold text-white"> Pedido</h2>
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

<!-- Modal de Notas -->
<div id="modalNotas" class="hidden fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-9998 overflow-auto" style="z-index: 9998;">
    <div class="bg-white rounded-lg shadow-2xl max-w-2xl w-full mx-4 my-8">
        <!-- Header -->
        <div class="bg-slate-900 px-6 py-4 border-b border-slate-200 flex justify-between items-center">
            <h2 class="text-lg font-semibold text-white">💬 Notas - Pedido <span id="modalNotasNumeroPedido">#</span> | <span id="modalNotasArticulo">-</span></h2>
            <button onclick="cerrarModalNotas()" 
                    class="text-white hover:text-slate-200 text-2xl leading-none">
                ✕
            </button>
        </div>
        
        <!-- Body -->
        <div class="px-6 py-6">
            <!-- Historial de Notas -->
            <div id="notasHistorial" class="mb-6" style="max-height: 350px; overflow-y: auto;">
                <div class="flex justify-center items-center py-8">
                    <span class="text-slate-500">⏳ Cargando notas...</span>
                </div>
            </div>
            
            <!-- Formulario para agregar nota -->
            <div class="border-t border-slate-200 pt-6">
                <label class="block text-sm font-bold text-slate-900 mb-3">Agregar Nueva Nota:</label>
                <textarea
                    id="notasNuevaContent"
                    class="w-full px-4 py-3 border-2 border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-700 outline-none transition resize-none"
                    placeholder="Escribe tu nota aquí..."
                    rows="4"
                ></textarea>
                
                <div class="flex gap-3 mt-4">
                    <button
                        type="button"
                        onclick="guardarNota()"
                        class="flex-1 px-4 py-2 bg-green-500 hover:bg-green-600 text-white font-bold rounded-lg transition"
                    >
                        ✓ Guardar Nota
                    </button>
                    <button
                        type="button"
                        onclick="cerrarModalNotas()"
                        class="flex-1 px-4 py-2 bg-slate-400 hover:bg-slate-500 text-white font-bold rounded-lg transition"
                    >
                        Cancelar
                    </button>
                </div>
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

<!-- Modal para Editar Nota -->
<div id="modalEditarNota" class="hidden fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-[10000]" style="z-index: 10000;">
    <div class="bg-white rounded-lg shadow-2xl max-w-md w-full mx-4">
        <!-- Header -->
        <div class="bg-slate-900 px-6 py-4 border-b border-slate-200 flex justify-between items-center">
            <h2 class="text-lg font-semibold text-white">✏️ Editar Nota</h2>
            <button onclick="cerrarModalEditarNota()" class="text-white hover:text-slate-200 text-2xl leading-none">✕</button>
        </div>
        
        <!-- Body -->
        <div class="px-6 py-6">
            <textarea 
                id="editarNotaContent" 
                class="w-full px-3 py-2 border-2 border-slate-300 text-sm focus:ring-2 focus:ring-slate-500 focus:border-slate-700 outline-none transition rounded"
                rows="6"
                placeholder="Edita el contenido de la nota..."
                style="font-family: 'Poppins', sans-serif; resize: vertical;"></textarea>
        </div>
        
        <!-- Footer -->
        <div class="bg-slate-50 px-6 py-4 border-t border-slate-200 flex gap-3 justify-end">
            <button 
                type="button"
                onclick="cerrarModalEditarNota()"
                class="px-4 py-2 bg-slate-300 hover:bg-slate-400 text-slate-900 text-sm font-bold rounded transition"
            >
                Cancelar
            </button>
            <button 
                type="button"
                onclick="guardarEdicionNota()"
                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold rounded transition"
            >
                Guardar
            </button>
        </div>
    </div>
</div>

<!-- Modal para Confirmar Eliminación -->
<div id="modalConfirmarEliminar" class="hidden fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-[10000]" style="z-index: 10000;">
    <div class="bg-white rounded-lg shadow-2xl max-w-md w-full mx-4">
        <!-- Header -->
        <div class="bg-red-600 px-6 py-4 border-b border-red-300 flex justify-between items-center">
            <h2 class="text-lg font-semibold text-white">🗑️ Eliminar Nota</h2>
            <button onclick="cerrarModalConfirmarEliminar()" class="text-white hover:text-red-100 text-2xl leading-none">✕</button>
        </div>
        
        <!-- Body -->
        <div class="px-6 py-8">
            <p class="text-slate-700 text-center font-medium mb-4">
                ¿Estás seguro de que deseas eliminar esta nota?
            </p>
            <p class="text-slate-500 text-center text-sm">
                Esta acción no se puede deshacer.
            </p>
        </div>
        
        <!-- Footer -->
        <div class="bg-slate-50 px-6 py-4 border-t border-slate-200 flex gap-3 justify-end">
            <button 
                type="button"
                onclick="cerrarModalConfirmarEliminar()"
                class="px-4 py-2 bg-slate-300 hover:bg-slate-400 text-slate-900 text-sm font-bold rounded transition"
            >
                Cancelar
            </button>
            <button 
                type="button"
                onclick="confirmarEliminarNota()"
                class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-bold rounded transition"
            >
                Eliminar
            </button>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
// PRIMER LOG - debe aparecer al principio
console.log('*** BLOQUE SCRIPTS CARGADO ***');
console.log('*** ROL ACTUAL: {{ auth()->user()->getRoleNames()->first() ?? "Sin Rol"}} ***');

// Variable global con el ID del usuario actual
window.usuarioActualId = {{ auth()->user()->id }};

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

    // Inicializar selectores de estado con valores guardados
    const estadoSelects = document.querySelectorAll('.estado-select');
    estadoSelects.forEach(select => {
        const estadoGuardado = select.getAttribute('data-original-estado');
        if (estadoGuardado && estadoGuardado.trim() !== '') {
            select.value = estadoGuardado;
        }
    });

    // Cargar notas automáticamente para items visibles en la página
    // Se ejecuta inmediatamente después de que el script se cargue
    (function() {
        const observacionesInputs = document.querySelectorAll('.observaciones-input');
        
        // Agrupar por numero_pedido/talla para evitar duplicados
        const pedidosACargar = new Set();
        observacionesInputs.forEach(textarea => {
            const numeroPedido = textarea.dataset.numeroPedido;
            const talla = textarea.dataset.talla;
            if (numeroPedido && talla) {
                pedidosACargar.add(`${numeroPedido}|${talla}`);
            }
        });

        // Cargar notas para cada combinación única
        pedidosACargar.forEach(key => {
            const [numeroPedido, talla] = key.split('|');
            cargarNotas(numeroPedido, talla);
        });
    })();
});

/**
 * Variables globales para el modal de notas
 */
let notasActualNumeroPedido = '';
let notasActualTalla = '';

/**
 * Guardar todos los cambios de una fila por click en botón Guardar
 */
function guardarFilaCompleta(btnGuardar, numeroPedido, talla) {
    // Obtener todos los valores de la fila
    const pendientesInput = document.querySelector(
        `.pendientes-input[data-numero-pedido="${numeroPedido}"][data-talla="${talla}"]`
    );
    const observacionesInput = document.querySelector(
        `.observaciones-input[data-numero-pedido="${numeroPedido}"][data-talla="${talla}"]`
    );
    const fechaPedidoInput = document.querySelector(
        `.fecha-pedido-input[data-numero-pedido="${numeroPedido}"][data-talla="${talla}"]`
    );
    const fechaEntregaInput = document.querySelector(
        `.fecha-input[data-numero-pedido="${numeroPedido}"][data-talla="${talla}"]`
    );
    const areaSelect = document.querySelector(
        `.area-select[data-numero-pedido="${numeroPedido}"][data-talla="${talla}"]`
    );
    const estadoSelect = document.querySelector(
        `.estado-select[data-numero-pedido="${numeroPedido}"][data-talla="${talla}"]`
    );

    // Validar que existan los elementos
    if (!pendientesInput || !estadoSelect) {
        alert('Error: No se encontraron los campos de la fila');
        return;
    }

    // Obtener PRENDA_NOMBRE y CANTIDAD desde el select
    const prendaNombre = estadoSelect.getAttribute('data-prenda-nombre') || '';
    const cantidad = parseInt(estadoSelect.getAttribute('data-cantidad') || '0');

    console.log(`[GUARDAR BODEGUERO] numeroPedido=${numeroPedido}, talla=${talla}, prenda=${prendaNombre}, cantidad=${cantidad}`);

    // Obtener la fila para extraer otros campos
    const fila = pendientesInput.closest('tr');
    const lastUpdatedAt = observacionesInput?.dataset?.updatedAt || new Date().toISOString();

    const datosAGuardar = {
        numero_pedido: numeroPedido,
        talla: talla,
        prenda_nombre: prendaNombre,  // Enviar nombre de la prenda
        cantidad: cantidad,  // Enviar cantidad
        asesor: fila.getAttribute('data-asesor') || '',  // Asesor
        empresa: fila.getAttribute('data-empresa') || '',  // Empresa
        pendientes: pendientesInput.value.trim(),
        observaciones_bodega: observacionesInput?.value?.trim() || '',
        fecha_pedido: fechaPedidoInput?.value || null,
        fecha_entrega: fechaEntregaInput?.value || null,
        area: areaSelect?.value || null,
        estado_bodega: estadoSelect?.value || null,
        last_updated_at: lastUpdatedAt,
    };

    // Mostrar spinner de carga
    const textoOriginal = btnGuardar.textContent;
    btnGuardar.textContent = '⏳ Guardando...';
    btnGuardar.disabled = true;

    fetch('/gestion-bodega/detalles-talla/guardar', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
        },
        body: JSON.stringify(datosAGuardar)
    })
    .then(response => response.json())
    .then(data => {
        btnGuardar.textContent = textoOriginal;
        btnGuardar.disabled = false;

        if (data.success) {
            // Mostrar toast de éxito
            const toast = document.getElementById('toast');
            const toastMessage = document.getElementById('toastMessage');
            if (toast && toastMessage) {
                toastMessage.textContent = '✓ Cambios guardados exitosamente';
                toast.classList.remove('hidden');
                toast.style.display = 'flex';
                setTimeout(() => {
                    toast.classList.add('hidden');
                    toast.style.display = 'none';
                }, 3000);
            }
            
            // Actualizar el updated_at en los inputs
            if (data.data?.updated_at) {
                if (observacionesInput) observacionesInput.dataset.updatedAt = data.data.updated_at;
                if (fechaPedidoInput) fechaPedidoInput.dataset.updatedAt = data.data.updated_at;
                if (fechaEntregaInput) fechaEntregaInput.dataset.updatedAt = data.data.updated_at;
            }

            // Actualizar el selector de estado inmediatamente con el nuevo valor
            if (data.data && data.data.estado_bodega && estadoSelect) {
                estadoSelect.value = data.data.estado_bodega;
                estadoSelect.setAttribute('data-original-estado', data.data.estado_bodega);
            }
        } else if (data.conflict) {
            // Conflicto de edición
            alert(' Conflicto de edición: Otro usuario modificó este registro.\n\nPor favor, recarga la página para los cambios más recientes.');
            location.reload();
        } else {
            alert('Error: ' + (data.message || 'No se pudieron guardar los cambios'));
        }
    })
    .catch(error => {
        btnGuardar.textContent = textoOriginal;
        btnGuardar.disabled = false;
        console.error('Error:', error);
        alert('Error al guardar: ' + error.message);
    });
}

/**
 * Abrir modal de notas
 */
function abrirModalNotas(numeroPedido, talla, nombreItem, tipoItem, tallaReal) {
    notasActualNumeroPedido = numeroPedido;
    notasActualTalla = talla;
    
    const modal = document.getElementById('modalNotas');
    if (modal) {
        document.getElementById('modalNotasNumeroPedido').textContent = numeroPedido;
        
        // Mostrar nombre del artículo con talla (solo para prendas)
        let textoArticulo = nombreItem;
        if (tipoItem === 'prenda') {
            textoArticulo += ` - ${tallaReal}`;
        }
        document.getElementById('modalNotasArticulo').textContent = textoArticulo;
        
        document.getElementById('notasNuevaContent').value = '';
        modal.classList.remove('hidden');
        
        // Cargar historial de notas
        cargarNotas(numeroPedido, talla);
    }
}

/**
 * Cerrar modal de notas
 */
function cerrarModalNotas() {
    const modal = document.getElementById('modalNotas');
    if (modal) {
        modal.classList.add('hidden');
    }
}

/**
 * Cargar historial de notas
 */
function cargarNotas(numeroPedido, talla) {
    fetch('/gestion-bodega/notas/obtener', {
        method: 'POST',
        cache: 'no-store',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
        },
        body: JSON.stringify({
            numero_pedido: numeroPedido,
            talla: talla
        })
    })
    .then(response => response.json())
    .then(data => {
        const historialDiv = document.getElementById('notasHistorial');
        let textAreaContent = '';
        
        if (data.success && data.data && data.data.length > 0) {
            let html = '<div class="space-y-4">';
            data.data.forEach(nota => {
                // Determinar color según rol
                let colorRol = '#e2e8f0';
                let bgRol = '#f1f5f9';
                if (nota.usuario_rol === 'Bodeguero') {
                    colorRol = '#1e40af';
                    bgRol = '#dbeafe';
                } else if (nota.usuario_rol === 'Costura-Bodega') {
                    colorRol = '#7c2d12';
                    bgRol = '#feddba';
                } else if (nota.usuario_rol === 'EPP-Bodega') {
                    colorRol = '#065f46';
                    bgRol = '#d1fae5';
                }
                
                let botones = '';
                if (nota.usuario_id === window.usuarioActualId) {
                    botones = `
                                <button 
                                    type="button"
                                    onclick="editarNota(${nota.id}, '${numeroPedido}', '${talla}', '${nota.contenido.replace(/'/g, "\\'")}')"
                                    class="px-2 py-1 bg-blue-500 hover:bg-blue-600 text-white text-xs rounded"
                                    title="Editar nota"
                                >
                                    ✏️
                                </button>
                                <button 
                                    type="button"
                                    onclick="eliminarNota(${nota.id}, '${numeroPedido}', '${talla}')"
                                    class="px-2 py-1 bg-red-500 hover:bg-red-600 text-white text-xs rounded"
                                    title="Eliminar nota"
                                >
                                    🗑️
                                </button>`;
                }
                
                html += `
                    <div style="background-color: ${bgRol}; border-left: 4px solid ${colorRol}; padding: 12px; border-radius: 6px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px; align-items: flex-start;">
                            <div style="flex: 1;">
                                <strong style="color: ${colorRol}; font-size: 14px;">${nota.usuario_nombre}</strong>
                                <span style="color: #64748b; font-size: 12px; margin-left: 10px;">
                                    <strong>${nota.usuario_rol}</strong>
                                </span>
                            </div>
                            <div style="display: flex; gap: 8px; align-items: center;">
                                <span style="color: #64748b; font-size: 12px; white-space: nowrap;">${nota.fecha} ${nota.hora}</span>
                                ${botones}
                            </div>
                        </div>
                        <p style="margin: 0; color: #1e293b; font-size: 13px; white-space: pre-wrap;">
                            ${nota.contenido}
                        </p>
                    </div>
                `;
                
                // Agregar al contenido del textarea - FORMATO SIMPLIFICADO
                textAreaContent += `${nota.usuario_nombre} - ${nota.contenido}\n`;
            });
            html += '</div>';
            historialDiv.innerHTML = html;
        } else {
            historialDiv.innerHTML = '<p style="text-align: center; color: #94a3b8; font-size: 14px;">No hay notas aún. ¡Sé el primero en comentar!</p>';
        }
        
        // Actualizar TODOS los textareas de observaciones que coincidan con este pedido/talla
        const tallaNorm = String(talla || '').toLowerCase();
        const observacionesInputs = Array.from(
            document.querySelectorAll(`.observaciones-input[data-numero-pedido="${numeroPedido}"]`)
        ).filter(textarea => String(textarea?.dataset?.talla || '').toLowerCase() === tallaNorm);

        observacionesInputs.forEach(textarea => {
            const oldValue = textarea.value;
            textarea.value = textAreaContent.trim();
            
            // Disparar eventos de cambio para que el UI se actualice
            textarea.dispatchEvent(new Event('input', { bubbles: true }));
            textarea.dispatchEvent(new Event('change', { bubbles: true }));
            
            // Trigger auto-resize
            textarea.style.height = 'auto';
            textarea.style.height = (textarea.scrollHeight) + 'px';
        });
    })
    .catch(error => {
        console.error('Error al cargar notas:', error);
        document.getElementById('notasHistorial').innerHTML = '<p style="color: #ef4444;">Error al cargar las notas</p>';
    });
}

/**
 * Guardar nueva nota
 */
function guardarNota() {
    const contenido = document.getElementById('notasNuevaContent').value.trim();
    
    if (!contenido) {
        alert('Por favor, escribe una nota antes de guardar');
        return;
    }
    
    if (contenido.length > 5000) {
        alert('La nota no puede exceder 5000 caracteres');
        return;
    }

    fetch('/gestion-bodega/notas/guardar', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
        },
        body: JSON.stringify({
            numero_pedido: notasActualNumeroPedido,
            talla: notasActualTalla,
            contenido: contenido
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('notasNuevaContent').value = '';
            // Recargar historial
            cargarNotas(notasActualNumeroPedido, notasActualTalla);
            
            // Mostrar mensaje de éxito
            const toast = document.getElementById('toast');
            const toastMessage = document.getElementById('toastMessage');
            if (toast && toastMessage) {
                toastMessage.textContent = '✓ Nota guardada exitosamente';
                toast.classList.remove('hidden');
                toast.style.display = 'flex';
                setTimeout(() => {
                    toast.classList.add('hidden');
                    toast.style.display = 'none';
                }, 3000);
            }
        } else {
            alert('Error: ' + (data.message || 'No se pudo guardar la nota'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al guardar la nota: ' + error.message);
    });
}

/**
 * Editar una nota existente
 */
function editarNota(notaId, numeroPedido, talla, contenidoActual) {
    // Guardar datos globales para usar en guardarEdicionNota
    window.editarNotaData = {
        notaId: notaId,
        numeroPedido: numeroPedido,
        talla: talla
    };
    
    // Rellenar el textarea del modal
    document.getElementById('editarNotaContent').value = contenidoActual;
    
    // Abrir modal
    const modal = document.getElementById('modalEditarNota');
    modal.classList.remove('hidden');
}

/**
 * Cerrar modal de editar nota
 */
function cerrarModalEditarNota() {
    const modal = document.getElementById('modalEditarNota');
    modal.classList.add('hidden');
}

/**
 * Guardar la edición de una nota
 */
function guardarEdicionNota() {
    const contenido = document.getElementById('editarNotaContent').value.trim();
    
    if (!contenido) {
        alert('La nota no puede estar vacía');
        return;
    }
    
    const data = window.editarNotaData;
    const notaId = data.notaId;
    const numeroPedido = data.numeroPedido;
    const talla = data.talla;
    
    fetch(`/gestion-bodega/notas/${notaId}/actualizar`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
        },
        body: JSON.stringify({
            contenido: contenido
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Cerrar modal
            cerrarModalEditarNota();
            
            // Recargar notas
            cargarNotas(numeroPedido, talla);
            
            // Mostrar toast de éxito
            const toast = document.getElementById('toast');
            const toastMessage = document.getElementById('toastMessage');
            if (toast && toastMessage) {
                toastMessage.textContent = '✓ Nota actualizada correctamente';
                toast.classList.remove('hidden');
                toast.style.display = 'flex';
                setTimeout(() => {
                    toast.classList.add('hidden');
                    toast.style.display = 'none';
                }, 3000);
            }
        } else {
            alert('Error: ' + (data.message || 'No se pudo actualizar la nota'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al actualizar la nota: ' + error.message);
    });
}

/**
 * Eliminar una nota
 */
function eliminarNota(notaId, numeroPedido, talla) {
    // Guardar datos globales para usar en confirmarEliminarNota
    window.eliminarNotaData = {
        notaId: notaId,
        numeroPedido: numeroPedido,
        talla: talla
    };
    
    // Abrir modal de confirmación
    const modal = document.getElementById('modalConfirmarEliminar');
    modal.classList.remove('hidden');
}

/**
 * Cerrar modal de confirmar eliminación
 */
function cerrarModalConfirmarEliminar() {
    const modal = document.getElementById('modalConfirmarEliminar');
    modal.classList.add('hidden');
}

/**
 * Confirmar y ejecutar eliminación de nota
 */
function confirmarEliminarNota() {
    const data = window.eliminarNotaData;
    const notaId = data.notaId;
    const numeroPedido = data.numeroPedido;
    const talla = data.talla;
    
    fetch(`/gestion-bodega/notas/${notaId}/eliminar`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
        },
        body: JSON.stringify({})
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Cerrar modal
            cerrarModalConfirmarEliminar();
            
            // Recargar notas
            cargarNotas(numeroPedido, talla);
            
            // Mostrar toast de éxito
            const toast = document.getElementById('toast');
            const toastMessage = document.getElementById('toastMessage');
            if (toast && toastMessage) {
                toastMessage.textContent = '✓ Nota eliminada correctamente';
                toast.classList.remove('hidden');
                toast.style.display = 'flex';
                setTimeout(() => {
                    toast.classList.add('hidden');
                    toast.style.display = 'none';
                }, 3000);
            }
        } else {
            alert('Error: ' + (data.message || 'No se pudo eliminar la nota'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al eliminar la nota: ' + error.message);
    });
}
/**
 * Escuchar cambios en tiempo real con WebSockets (Reverb/Echo)
 */
console.log('[Reverb] ===== VERIFICANDO WEBSOCKET =====');
console.log('[Reverb] window.Echo:', typeof window.Echo);
console.log('[Reverb] window.Echo disponible:', !!window.Echo);
console.log('[Reverb] window.Pusher:', typeof window.Pusher);
console.log('[Reverb] window.Laravel Echo:', typeof window.LaravelEcho);

if (typeof window.Echo !== 'undefined') {
    // Variables globales para los canales activos
    let canalNotasActivo = null;
    let canalDetallesActivo = null;

    /**
     * Función para subscribirse a cambios de notas
     */
    function subscribirANotas(numeroPedido, talla) {
        // Desuscribirse del canal anterior si existe
        if (canalNotasActivo) {
            window.Echo.leave(`bodega-notas-${canalNotasActivo.numero}-${canalNotasActivo.talla}`);
        }

        // Subscribirse al nuevo canal
        const nombreCanal = `bodega-notas-${numeroPedido}-${talla}`;
        canalNotasActivo = { numero: numeroPedido, talla: talla };
        
        window.Echo.channel(nombreCanal)
            .listen('nota.guardada', (event) => {
                // Recargar notas cuando alguien guarda una nota
                cargarNotas(numeroPedido, talla);
            });
    }

    /**
     * Función para subscribirse a cambios de detalles
     */
    function subscribirADetalles(numeroPedido, talla) {
        if (!window.Echo) {
            console.log('[Reverb] subscribirADetalles: Echo no disponible');
            return;
        }
        
        console.log(`[Reverb] subscribirADetalles: INICIANDO - pedido=${numeroPedido}, talla=${talla}`);
        
        // Desuscribirse del canal anterior si existe
        if (canalDetallesActivo) {
            console.log(`[Reverb] subscribirADetalles: Desuscribiendo canal anterior: bodega-detalles-${canalDetallesActivo.numero}-${canalDetallesActivo.talla}`);
            window.Echo.leave(`bodega-detalles-${canalDetallesActivo.numero}-${canalDetallesActivo.talla}`);
        }

        // Subscribirse al nuevo canal
        const nombreCanal = `bodega-detalles-${numeroPedido}-${talla}`;
        canalDetallesActivo = { numero: numeroPedido, talla: talla };
        
        console.log(`[Reverb] subscribirADetalles: Suscribiendo a canal: ${nombreCanal}`);
        
        window.Echo.private(nombreCanal)
            .listen('detalle.actualizado', (event) => {
                console.log('[Reverb] ===== DETALLE ACTUALIZADO =====');
                console.log('[Reverb] Evento completo:', event);
                console.log('[Reverb] Número pedido:', numeroPedido);
                console.log('[Reverb] Talla:', talla);
                
                // Actualizar los campos del formulario
                const fecha = document.querySelector(
                    `.fecha-input[data-numero-pedido="${numeroPedido}"][data-talla="${talla}"]`
                );
                const fechaPedido = document.querySelector(
                    `.fecha-pedido-input[data-numero-pedido="${numeroPedido}"][data-talla="${talla}"]`
                );
                const pendientes = document.querySelector(
                    `.pendientes-input[data-numero-pedido="${numeroPedido}"][data-talla="${talla}"]`
                );
                const observaciones = document.querySelector(
                    `.observaciones-input[data-numero-pedido="${numeroPedido}"][data-talla="${talla}"]`
                );
                const estadoSelect = document.querySelector(
                    `.estado-select[data-numero-pedido="${numeroPedido}"][data-talla="${talla}"]`
                );

                console.log('[Reverb] Elementos encontrados:');
                console.log('- fecha:', fecha);
                console.log('- fechaPedido:', fechaPedido);
                console.log('- pendientes:', pendientes);
                console.log('- observaciones:', observaciones);
                console.log('- estadoSelect:', estadoSelect);

                if (event.detalles) {
                    console.log('[Reverb] Detalles recibidos:', event.detalles);
                    
                    // Evitar actualizar si el campo está siendo editado
                    if (event.detalles.fecha_entrega && fecha && document.activeElement !== fecha) {
                        console.log('[Reverb] Actualizando fecha_entrega:', event.detalles.fecha_entrega);
                        fecha.value = event.detalles.fecha_entrega;
                    }
                    if (event.detalles.fecha_pedido && fechaPedido && document.activeElement !== fechaPedido) {
                        console.log('[Reverb] Actualizando fecha_pedido:', event.detalles.fecha_pedido);
                        fechaPedido.value = event.detalles.fecha_pedido;
                    }
                    if (event.detalles.pendientes !== undefined && pendientes && document.activeElement !== pendientes) {
                        console.log('[Reverb] Actualizando pendientes:', event.detalles.pendientes);
                        pendientes.value = event.detalles.pendientes || '';
                        autoResizeTextarea(pendientes);
                    }
                    if (event.detalles.observaciones_bodega !== undefined && observaciones && document.activeElement !== observaciones) {
                        console.log('[Reverb] Actualizando observaciones_bodega:', event.detalles.observaciones_bodega);
                        observaciones.value = event.detalles.observaciones_bodega || '';
                        autoResizeTextarea(observaciones);
                    }
                    
                    if (event.detalles.estado_bodega && estadoSelect && document.activeElement !== estadoSelect) {
                        console.log('[WebSocket Estado] ===== PROCESANDO ESTADO =====');
                        console.log('[WebSocket Estado] Estado recibido:', event.detalles.estado_bodega);
                        console.log('[WebSocket Estado] Tipo de dato:', typeof event.detalles.estado_bodega);
                        console.log('[WebSocket Estado] Valor trim:', event.detalles.estado_bodega?.trim());
                        console.log('[WebSocket Estado] Es null?:', event.detalles.estado_bodega === null);
                        console.log('[WebSocket Estado] Es undefined?:', event.detalles.estado_bodega === undefined);
                        console.log('[WebSocket Estado] Es string vacío?:', event.detalles.estado_bodega === '');
                        console.log('[WebSocket Estado] Selector actual:', estadoSelect);
                        console.log('[WebSocket Estado] Valor actual del selector:', estadoSelect.value);
                        
                        // Mostrar todas las opciones del selector
                        console.log('[WebSocket Estado] Opciones disponibles:');
                        const opciones = estadoSelect.querySelectorAll('option');
                        opciones.forEach((opt, index) => {
                            console.log(`  [${index}] value="${opt.value}" text="${opt.textContent}" selected=${opt.selected}`);
                        });
                        
                        // Si el estado es null, vacío o undefined, establecer valor vacío para mostrar "ESTADO"
                        const estadoValido = event.detalles.estado_bodega && event.detalles.estado_bodega.trim() !== '' ? event.detalles.estado_bodega : '';
                        
                        console.log('[WebSocket Estado] Estado validado:', estadoValido);
                        console.log('[WebSocket Estado] Estado validado tipo:', typeof estadoValido);
                        console.log('[WebSocket Estado] Estado validado length:', estadoValido.length);
                        
                        estadoSelect.value = estadoValido;
                        estadoSelect.setAttribute('data-original-estado', estadoValido);
                        
                        console.log('[WebSocket Estado] Valor después de asignar:', estadoSelect.value);
                        console.log('[WebSocket Estado] Atributo data-original-estado:', estadoSelect.getAttribute('data-original-estado'));
                        
                        // Actualizar el texto visible del selector
                        const optionSeleccionada = estadoSelect.querySelector(`option[value="${estadoValido}"]`);
                        console.log('[WebSocket Estado] Opción encontrada:', optionSeleccionada);
                        console.log('[WebSocket Estado] Buscando option[value="' + estadoValido + '"]');
                        
                        if (optionSeleccionada) {
                            console.log('[WebSocket Estado] Opción encontrada - texto:', optionSeleccionada.textContent);
                            // Forzar actualización del texto visible
                            estadoSelect.dispatchEvent(new Event('change'));
                            console.log('[WebSocket Estado] Evento change disparado');
                        } else {
                            console.log('[WebSocket Estado] No se encontró opción para el valor:', estadoValido);
                            console.log('[WebSocket Estado] Intentando con valor vacío para mostrar ESTADO');
                            const optionVacia = estadoSelect.querySelector('option[value=""]');
                            console.log('[WebSocket Estado] Opción vacía:', optionVacia);
                            if (optionVacia) {
                                estadoSelect.value = '';
                                estadoSelect.dispatchEvent(new Event('change'));
                                console.log('[WebSocket Estado] Forzado a valor vacío para mostrar ESTADO');
                            }
                        }
                        console.log('[WebSocket Estado] ===== FIN PROCESAMIENTO ESTADO =====');
                    }
                } else {
                    console.log('[Reverb] No se recibieron detalles en el evento');
                }
            })
            .error((error) => {
                console.error(`[Reverb] Error en canal ${nombreCanal}:`, error);
            })
            .subscribed(() => {
                console.log(`[Reverb] Suscrito exitosamente a canal: ${nombreCanal}`);
            });
    }

    // Suscribirse a todos los items visibles al cargar la página
    document.addEventListener('DOMContentLoaded', function() {
        if (window.Echo) {
            console.log('[Reverb] ===== INICIALIZANDO WEBSOCKET =====');
            console.log('[Reverb] Echo disponible:', !!window.Echo);
            console.log('[Reverb] Conectado?:', window.Echo.connector?.pusher?.connection?.state);
            
            console.log('[Reverb] Inicializando suscripciones a detalles en tiempo real...');
            const observacionesInputs = document.querySelectorAll('.observaciones-input');
            console.log('[Reverb] Inputs de observaciones encontrados:', observacionesInputs.length);
            
            observacionesInputs.forEach((input, index) => {
                const numeroPedido = input.dataset.numeroPedido;
                const talla = input.dataset.talla;
                console.log(`[Reverb] [${index}] Procesando input: pedido=${numeroPedido}, talla=${talla}`);
                
                if (numeroPedido && talla) {
                    console.log(`[Reverb] [${index}] Suscribiendo a canal: bodega-detalles-${numeroPedido}-${talla}`);
                    subscribirADetalles(numeroPedido, talla);
                } else {
                    console.log(`[Reverb] [${index}] Saltando - datos incompletos`);
                }
            });
            console.log(`[Reverb] ===== SUSCRIPCIÓN COMPLETADA: ${observacionesInputs.length} items =====`);
        } else {
            console.log('[Reverb] ===== WEBSOCKET NO DISPONIBLE =====');
            console.log('[Reverb] window.Echo no está definido');
        }
    });

    // Mantener la integración con modal para cambio de item
    const abrirModalNotasOriginal = window.abrirModalNotas;
    window.abrirModalNotas = function(numeroPedido, talla, nombreItem, tipoItem, tallaReal) {
        abrirModalNotasOriginal.call(this, numeroPedido, talla, nombreItem, tipoItem, tallaReal);
        // Subscribirse a cambios de notas y detalles cuando abre el modal
        subscribirANotas(numeroPedido, talla);
        subscribirADetalles(numeroPedido, talla);
    };

    // Desuscribirse al cerrar el modal
    const cerrarModalNotasOriginal = window.cerrarModalNotas;
    window.cerrarModalNotas = function() {
        cerrarModalNotasOriginal.call(this);
        // Desuscribirse de los canales
        if (canalNotasActivo) {
            window.Echo.leave(`bodega-notas-${canalNotasActivo.numero}-${canalNotasActivo.talla}`);
            canalNotasActivo = null;
        }
        if (canalDetallesActivo) {
            window.Echo.leave(`bodega-detalles-${canalDetallesActivo.numero}-${canalDetallesActivo.talla}`);
            canalDetallesActivo = null;
        }
    };
}

// Cerrar modales de editar/eliminar nota al hacer clic fuera
document.addEventListener('DOMContentLoaded', function() {
    const modalEditarNota = document.getElementById('modalEditarNota');
    const modalConfirmarEliminar = document.getElementById('modalConfirmarEliminar');
    
    // Cerrar modal de editar al hacer clic afuera
    if (modalEditarNota) {
        modalEditarNota.addEventListener('click', function(event) {
            if (event.target === this) {
                cerrarModalEditarNota();
            }
        });
    }
    
    // Cerrar modal de confirmar eliminación al hacer clic afuera
    if (modalConfirmarEliminar) {
        modalConfirmarEliminar.addEventListener('click', function(event) {
            if (event.target === this) {
                cerrarModalConfirmarEliminar();
            }
        });
    }
    
    // Cerrar modales al presionar Escape
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            if (modalEditarNota && !modalEditarNota.classList.contains('hidden')) {
                cerrarModalEditarNota();
            }
            if (modalConfirmarEliminar && !modalConfirmarEliminar.classList.contains('hidden')) {
                cerrarModalConfirmarEliminar();
            }
        }
    });
});

// DEBUG SIMPLE - debe aparecer primero
console.log('*** SCRIPT CARGADO ***');
console.log('*** ROL: {{ auth()->user()->getRoleNames()->first() ?? "Sin Rol"}} ***');

// DEBUG: Mostrar datos precargados
console.log('=== DATOS PRECARGADOS DE BODEGA ===');
console.log('Total datosBodega:', Object.keys({!! json_encode($datosBodega->toArray()) !!}).length);
console.log('=============================');

// DEBUG: Analizar datos por rol
console.log('=== ANÁLISIS DE DATOS POR ROL ===');
const usuarioRol = '{{ auth()->user()->getRoleNames()->first() ?? "Sin Rol"}}';
console.log('Rol actual:', usuarioRol);

// Analizar estados disponibles
const estadosDisponibles = [];
const datosBodegaObj = {!! json_encode($datosBodega->toArray()) !!};
Object.values(datosBodegaObj).forEach(item => {
    if (item.estado_bodega) {
        estadosDisponibles.push(item.estado_bodega);
    }
});
console.log('Estados disponibles:', estadosDisponibles);

// Analizar tallas
const tallasDisponibles = [];
Object.keys(datosBodegaObj).forEach(key => {
    const [pedido, talla] = key.split('|');
    tallasDisponibles.push(talla);
});
console.log('Tallas disponibles:', tallasDisponibles);
console.log('=============================');

// Inicializar selectores de estado manualmente (fallback cuando no hay WebSocket)
function inicializarSelectoresEstado() {
    const selectoresEstado = document.querySelectorAll('.estado-select');
    console.log(`[Fallback] Inicializando ${selectoresEstado.length} selectores de estado`);
    
    selectoresEstado.forEach((selector, index) => {
        const valorActual = selector.value;
        const originalEstado = selector.getAttribute('data-original-estado');
        
        console.log(`[Fallback] Selector ${index}: valor=${valorActual}, original=${originalEstado}`);
        
        // Si no hay valor, asegurarse que muestre "ESTADO"
        if (!valorActual || valorActual.trim() === '') {
            selector.value = '';
            console.log(`[Fallback] Selector ${index}: establecido a valor vacío para mostrar ESTADO`);
        }
        
        // Agregar listener para cambios manuales
        selector.addEventListener('change', function() {
            colorearFilaPorEstado(this);
        });
        
        // Colorear fila según estado actual
        colorearFilaPorEstado(selector);
    });
}

// Ejecutar inicialización inmediatamente
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', inicializarSelectoresEstado);
} else {
    inicializarSelectoresEstado();
}
</script>
<script src="{{ asset('js/bodega-pedidos.js') }}?v={{ time() }}"></script>
@endpush
