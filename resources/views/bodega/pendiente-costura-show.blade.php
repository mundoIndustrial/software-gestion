@extends('layouts.app-without-sidebar')

@section('title', "Pendientes de Costura - Pedido {$pedido['numero_pedido']}")

@section('content')
<div class="min-h-screen bg-slate-50 w-full flex flex-col">
    <div class="w-full flex-shrink-0">
        <!-- Header -->
        <div class="bg-white border-b border-slate-200 px-4 py-4 sm:px-6 sm:py-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl sm:text-2xl font-semibold text-black">Pendientes de Costura</h1>
                    <p class="text-xs sm:text-sm text-black mt-1">
                        N° Pedido: <span class="font-semibold text-black">{{ $pedido['numero_pedido'] }}</span> |
                        Cliente: <span class="font-semibold text-black">{{ $pedido['cliente'] ?? 'No especificado' }}</span>
                        @if($pedido['asesor'])
                            | Asesor: <span class="font-semibold text-black">{{ $pedido['asesor'] }}</span>
                        @endif
                    </p>
                    <div class="mt-2 p-2 bg-orange-100 border border-orange-200 rounded">
                        <p class="text-xs font-medium text-orange-800">
                            <span class="material-symbols-rounded text-sm align-middle">filter_alt</span>
                            Mostrando solo artículos de Costura con estado Pendiente
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <a href="{{ route('gestion-bodega.pendientes-costura') }}"
                       class="px-4 py-2 border border-slate-300 text-black hover:text-black font-medium rounded transition-colors">
                        ← Volver a Pendientes
                    </a>
                    @if($pedido['id'])
                        <button type="button"
                                onclick="abrirModalFactura({{ $pedido['id'] }})"
                                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded transition-colors">
                            Ver Pedido Completo
                        </button>
                    @endif
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 overflow-hidden">
            <!-- Tabla Moderna de Detalles -->
            <div class="bg-white h-full overflow-hidden border border-slate-300 shadow-sm rounded">
                <div class="overflow-x-auto h-full" style="height: calc(100vh - 120px);">
                    <table class="w-full border-collapse" style="table-layout: auto;">
                        <!-- THEAD -->
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-300">
                                <th class="px-4 py-3 text-left text-[11px] font-semibold text-black uppercase tracking-widest border-r border-slate-300" style="width: 22%;">Artículo</th>
                                <th class="px-2 py-3 text-center text-[10px] font-semibold text-black uppercase tracking-widest border-r border-slate-300" style="width: 6%;">Género</th>
                                <th class="px-2 py-3 text-center text-[10px] font-semibold text-black uppercase tracking-widest border-r border-slate-300" style="width: 6%;">Talla</th>
                                <th class="px-4 py-3 text-center text-[11px] font-semibold text-black uppercase tracking-widest border-r border-slate-300" style="width: 6%;">Cant.</th>
                                <th class="px-4 py-3 text-center text-[11px] font-semibold text-black uppercase tracking-widest border-r border-slate-300" style="width: 8%;">Pendientes</th>
                                <th class="px-4 py-3 text-left text-[11px] font-semibold text-black uppercase tracking-widest border-r border-slate-300" style="width: 16%;">Observaciones</th>
                                <th class="px-4 py-3 text-center text-[11px] font-semibold text-black uppercase tracking-widest border-r border-slate-300" style="width: 12%;">Fecha Pedido</th>
                                <th class="px-4 py-3 text-center text-[11px] font-semibold text-black uppercase tracking-widest border-r border-slate-300" style="width: 12%;">Fecha Entrega</th>
                                {{-- Comentada columna de estado
                                <th class="px-4 py-3 text-center text-[11px] font-semibold text-black uppercase tracking-widest" style="width: 18%;">Estado</th>
                                --}}
                            </tr>
                        </thead>
                        
                        <tbody id="pedidosTableBody" class="divide-y divide-slate-300">
                            @if($items && count($items) > 0)
                                @php
                                    $itemsAgrupados = collect($items)->groupBy(function ($it) {
                                        return 'prenda_' . ($it['prenda_id'] ?? md5(($it['descripcion']['nombre_prenda'] ?? $it['descripcion']['nombre'] ?? 'sin_nombre')));
                                    });
                                @endphp

                                @foreach($itemsAgrupados as $grupo)
                                    @php
                                        $primeraItem = $grupo->first();

                                        $itemsPorTalla = [];
                                        $itemsPorTallaColor = [];
                                        foreach ($grupo as $it) {
                                            if (isset($it['talla'])) {
                                                $itemsPorTalla[$it['talla']] = $it;

                                                $itTallaColorId = $it['talla_color_id'] ?? ($it['tallaColorId'] ?? null);
                                                $itKey = $it['talla'] . '|' . ($itTallaColorId ?? '');
                                                $itemsPorTallaColor[$itKey] = $it;
                                            }
                                        }

                                        $desc = $primeraItem['descripcion'] ?? [];
                                        $nombre = $desc['nombre_prenda'] ?? $desc['nombre'] ?? 'Prenda sin nombre';
                                        $tela = $desc['tela'] ?? null;
                                        $color = $desc['color'] ?? null;
                                        $variantes = $desc['variantes'] ?? [];
                                        $procesos = $desc['procesos'] ?? [];
                                        $primeraVariante = count($variantes) > 0 ? $variantes[0] : null;

                                        $gruposPorColor = [];
                                        if (is_array($variantes)) {
                                            foreach ($variantes as $variante) {
                                                $generoVar = strtoupper($variante['genero'] ?? '');
                                                $tallaVar = $variante['talla'] ?? '';

                                                if ($generoVar === 'GENERICO') {
                                                    continue;
                                                }

                                                if (isset($variante['colores_detalle']) && is_array($variante['colores_detalle']) && !empty($variante['colores_detalle'])) {
                                                    foreach ($variante['colores_detalle'] as $colorDetalle) {
                                                        $rawColor = $colorDetalle['color'] ?? '';
                                                        $esColorValido = !empty($rawColor) && strtolower(trim($rawColor)) !== 'sin color';
                                                        $colorKey = $esColorValido ? strtoupper($rawColor) : '__SIN_COLOR__';
                                                        $cantidadColor = (int)($colorDetalle['cantidad'] ?? 0);
                                                        $tallaColorId = $colorDetalle['talla_color_id'] ?? ($colorDetalle['tallaColorId'] ?? null);

                                                        if (!empty($tallaVar) && $cantidadColor > 0) {
                                                            if (!isset($gruposPorColor[$colorKey])) {
                                                                $gruposPorColor[$colorKey] = [
                                                                    'color' => $colorKey,
                                                                    'tallas' => [],
                                                                ];
                                                            }
                                                            $gruposPorColor[$colorKey]['tallas'][] = [
                                                                'talla' => $tallaVar,
                                                                'genero' => $variante['genero'] ?? null,
                                                                'cantidad' => $cantidadColor,
                                                                'tallaColorId' => $tallaColorId,
                                                            ];
                                                        }
                                                    }
                                                }
                                            }
                                        }

                                        foreach ($gruposPorColor as &$grupoColor) {
                                            usort($grupoColor['tallas'], function ($a, $b) {
                                                $nA = is_numeric($a['talla']) ? (int)$a['talla'] : null;
                                                $nB = is_numeric($b['talla']) ? (int)$b['talla'] : null;
                                                if ($nA !== null && $nB !== null) return $nA - $nB;
                                                return strcmp($a['talla'], $b['talla']);
                                            });
                                        }
                                        unset($grupoColor);

                                        $tieneColoresPorTalla = !empty($gruposPorColor);
                                    @endphp

                                    @if($tieneColoresPorTalla)
                                        @foreach($gruposPorColor as $indexColor => $grupoColor)
                                            @php
                                                $rowSpanColor = count($grupoColor['tallas']);
                                                $colorLabel = $grupoColor['color'] === '__SIN_COLOR__' ? null : $grupoColor['color'];
                                            @endphp
                                            @foreach($grupoColor['tallas'] as $indexTalla => $t)
                                                @php
                                                    $tKey = ($t['talla'] ?? '') . '|' . (($t['tallaColorId'] ?? null) ?? '');
                                                    $baseItem = $itemsPorTallaColor[$tKey] ?? ($itemsPorTalla[$t['talla']] ?? $primeraItem);
                                                @endphp
                                                <tr class="hover:bg-slate-50 transition-colors"
                                                    data-numero-pedido="{{ $baseItem['numero_pedido'] }}"
                                                    data-asesor="{{ $baseItem['asesor'] ?? ($pedido['asesor'] ?? '') }}"
                                                    data-empresa="{{ $baseItem['empresa'] ?? ($pedido['cliente'] ?? '') }}"
                                                    data-talla-color-id="{{ $t['tallaColorId'] ?? '' }}"
                                                    @if(($baseItem['costura_estado'] ?? null) === 'Homologar')
                                                        style="background-color: rgba(147, 51, 234, 0.08);"
                                                    @elseif(($baseItem['estado_bodega'] ?? '') === 'Entregado')
                                                        style="background-color: rgba(37, 99, 235, 0.05);"
                                                    @endif
                                                >
                                                    @if($indexTalla === 0)
                                                    <td class="px-4 py-3 text-xs text-black border-r border-slate-300" rowspan="{{ $rowSpanColor }}" style="width: 22%;">
                                                        <div class="font-bold text-black mb-1">
                                                            {{ $nombre }}
                                                            @if($colorLabel)
                                                                <span class="text-black"> - <strong>{{ $colorLabel }}</strong></span>
                                                            @endif
                                                            @if(($baseItem['de_bodega'] ?? ($baseItem['descripcion']['de_bodega'] ?? ($baseItem['objetoPrenda']['de_bodega'] ?? false))) )
                                                                <span class="text-orange-600 font-bold"> - SE SACA DE BODEGA</span>
                                                            @endif
                                                        </div>
                                                        @if(isset($desc['descripcion']) && !empty($desc['descripcion']))
                                                            <div class="text-slate-600 text-xs mb-2 italic">
                                                                {{ $desc['descripcion'] }}
                                                            </div>
                                                        @endif
                                                        @if($tela || ($color && strtolower($color) !== 'sin color'))
                                                            <div class="text-black text-xs mb-1">
                                                                @if($tela && $color && strtolower($color) !== 'sin color')
                                                                    Tela: {{ $tela }} - Color: {{ $color }}
                                                                @elseif($tela)
                                                                    Tela: {{ $tela }}
                                                                @elseif($color && strtolower($color) !== 'sin color')
                                                                    Color: {{ $color }}
                                                                @endif
                                                            </div>
                                                        @endif
                                                        @if(count($procesos) > 0)
                                                            <div class="text-black text-xs mt-2 space-y-0.5">
                                                                @foreach($procesos as $proceso)
                                                                    <div class="flex items-start gap-1">
                                                                        <span class="text-blue-600 font-bold">•</span>
                                                                        <span>
                                                                            {{ $proceso['tipo_proceso'] ?? 'Proceso' }}
                                                                            @if(!empty($proceso['ubicaciones']))
                                                                                @php
                                                                                    $ubicaciones = $proceso['ubicaciones'];

                                                                                    if (is_string($ubicaciones) && (strpos($ubicaciones, '[') === 0 || strpos($ubicaciones, '{') === 0)) {
                                                                                        $ubicacionesDecodificadas = json_decode($ubicaciones, true);
                                                                                        if (is_array($ubicacionesDecodificadas)) {
                                                                                            $ubicacionesStr = implode(', ', $ubicacionesDecodificadas);
                                                                                        } else {
                                                                                            $ubicacionesStr = $ubicaciones;
                                                                                        }
                                                                                    } elseif (is_array($ubicaciones)) {
                                                                                        $ubicacionesStr = implode(', ', $ubicaciones);
                                                                                    } else {
                                                                                        $ubicacionesStr = $ubicaciones;
                                                                                    }
                                                                                @endphp
                                                                                @if(!empty($ubicacionesStr))
                                                                                    ({{ $ubicacionesStr }})
                                                                                @endif
                                                                            @endif
                                                                        </span>
                                                                    </div>
                                                                @endforeach
                                                            </div>
                                                        @endif
                                                    </td>
                                                    @endif

                                                    @if($indexTalla === 0)
                                                    <td class="px-2 py-3 text-center text-[13px] text-black border-r border-slate-300" rowspan="{{ $rowSpanColor }}" style="width: 6%;">
                                                        @php
                                                            $gen = $t['genero'] ?? '';
                                                            $gen = (is_string($gen) && strtoupper(trim($gen)) === 'GENERICO') ? '' : $gen;
                                                        @endphp
                                                        {{ $gen ? ucfirst(strtolower($gen)) : '—' }}
                                                    </td>
                                                    @endif

                                                    <td class="px-2 py-3 text-center text-[10px] text-black border-r border-slate-300" style="width: 6%;">
                                                        @if(($t['talla'] ?? null) === 'SIN_ESPECIFICAR')
                                                            —
                                                        @else
                                                            {{ $t['talla'] ?? '—' }}
                                                        @endif
                                                    </td>

                                                    <td class="px-4 py-3 text-center text-xs font-bold text-black border-r border-slate-300" style="width: 6%;">
                                                        {{ $t['cantidad'] ?? 0 }}
                                                    </td>

                                                    <td class="px-2 py-3 border-r border-slate-300" style="width: 8%;">
                                                        <textarea
                                                            class="pendientes-input w-full px-1.5 py-1 border-2 border-slate-300 text-[10px] focus:ring-2 focus:ring-slate-500 focus:border-slate-700 outline-none transition resize-none bg-slate-50"
                                                            style="font-family: 'Poppins', sans-serif;"
                                                            data-numero-pedido="{{ $baseItem['numero_pedido'] }}"
                                                            data-talla="{{ $baseItem['talla'] }}"
                                                            data-talla-color-id="{{ $t['tallaColorId'] ?? '' }}"
                                                            placeholder="Pendientes..."
                                                            rows="1"
                                                            @if($esReadOnly ?? false) disabled @endif
                                                        >{{ $baseItem['pendientes'] ?? '' }}</textarea>
                                                    </td>

                                                    <td class="px-4 py-3 border-r border-slate-300" style="width: 16%;">
                                                        <div class="flex gap-1">
                                                            <textarea
                                                                class="observaciones-input flex-1 px-2 py-1 border border-slate-300 text-xs text-black focus:ring-1 focus:ring-slate-500 focus:border-slate-700 outline-none transition resize-none rounded bg-slate-50"
                                                                data-numero-pedido="{{ $baseItem['numero_pedido'] }}"
                                                                data-talla="{{ $baseItem['talla'] }}"
                                                                data-talla-color-id="{{ $t['tallaColorId'] ?? '' }}"
                                                                placeholder="Notas..."
                                                                rows="1"
                                                                readonly
                                                            >{{ $baseItem['observaciones_bodega'] ?? '' }}</textarea>
                                                            <button
                                                                type="button"
                                                                onclick="abrirModalNotas('{{ $baseItem['numero_pedido'] }}', '{{ $baseItem['talla'] }}', '{{ addslashes($nombre) }}', 'prenda', '{{ $baseItem['talla'] }}', '{{ $t['tallaColorId'] ?? '' }}')"
                                                                class="px-2 py-1 bg-blue-500 hover:bg-blue-600 text-white text-xs font-bold rounded transition whitespace-nowrap"
                                                                title="Ver/agregar notas"
                                                            >
                                                                💬
                                                            </button>
                                                        </div>
                                                    </td>

                                                    <td class="px-4 py-3 border-r border-slate-300" style="width: 12%;">
                                                        <input
                                                            type="date"
                                                            class="fecha-pedido-input w-full px-2 py-1 border border-slate-300 text-xs text-black focus:ring-1 focus:ring-slate-500 focus:border-slate-700 outline-none transition rounded bg-slate-50"
                                                            value="{{ $baseItem['fecha_pedido'] ? \Carbon\Carbon::parse($baseItem['fecha_pedido'])->format('Y-m-d') : '' }}"
                                                            data-numero-pedido="{{ $baseItem['numero_pedido'] }}"
                                                            data-talla="{{ $baseItem['talla'] }}"
                                                            data-talla-color-id="{{ $t['tallaColorId'] ?? '' }}"
                                                            @if($esReadOnly ?? false) disabled @endif
                                                        >
                                                    </td>

                                                    <td class="px-4 py-3 border-r border-slate-300" style="width: 12%;">
                                                        <input
                                                            type="date"
                                                            class="fecha-input w-full px-2 py-1 border border-slate-300 text-xs text-black focus:ring-1 focus:ring-slate-500 focus:border-slate-700 outline-none transition rounded bg-slate-50"
                                                            value="{{ $baseItem['fecha_entrega'] ? \Carbon\Carbon::parse($baseItem['fecha_entrega'])->format('Y-m-d') : '' }}"
                                                            data-numero-pedido="{{ $baseItem['numero_pedido'] }}"
                                                            data-talla="{{ $baseItem['talla'] }}"
                                                            data-talla-color-id="{{ $t['tallaColorId'] ?? '' }}"
                                                            @if($esReadOnly ?? false) disabled @endif
                                                        >
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @endforeach
                                    @else
                                        @php
                                            // Para el fallback, extraer género y datos de la primera variante si existen
                                            $primerItem = $grupo->first();
                                            $generoFallback = '';
                                            
                                            if (is_array($variantes) && !empty($variantes)) {
                                                foreach ($variantes as $var) {
                                                    if (!empty($var['genero'])) {
                                                        $generoVar = strtoupper($var['genero'] ?? '');
                                                        if ($generoVar !== 'GENERICO') {
                                                            $generoFallback = $var['genero'];
                                                            break;
                                                        }
                                                    }
                                                }
                                            }
                                        @endphp
                                        @foreach($grupo as $indexItem => $item)
                                            <tr class="hover:bg-slate-50 transition-colors"
                                                data-numero-pedido="{{ $item['numero_pedido'] }}"
                                                data-asesor="{{ $item['asesor'] ?? ($pedido['asesor'] ?? '') }}"
                                                data-empresa="{{ $item['empresa'] ?? ($pedido['cliente'] ?? '') }}"
                                                data-talla-color-id="{{ $item['talla_color_id'] ?? '' }}"
                                                @if(($item['costura_estado'] ?? null) === 'Homologar')
                                                    style="background-color: rgba(147, 51, 234, 0.08);"
                                                @elseif(($item['estado_bodega'] ?? '') === 'Entregado')
                                                    style="background-color: rgba(37, 99, 235, 0.05);"
                                                @endif
                                            >
                                                @if($indexItem === 0)
                                                <td class="px-4 py-3 text-xs text-black border-r border-slate-300" rowspan="{{ count($grupo) }}" style="width: 22%;">
                                                    <div class="font-bold text-black mb-1">
                                                        {{ $nombre }}
                                                        @if(($item['de_bodega'] ?? ($item['descripcion']['de_bodega'] ?? ($item['objetoPrenda']['de_bodega'] ?? false))) )
                                                            <span class="text-orange-600 font-bold"> - SE SACA DE BODEGA</span>
                                                        @endif
                                                    </div>
                                                    @if($tela || ($color && strtolower($color) !== 'sin color'))
                                                        <div class="text-black text-xs mb-1">
                                                            @if($tela && $color && strtolower($color) !== 'sin color')
                                                                Tela: {{ $tela }} - Color: {{ $color }}
                                                            @elseif($tela)
                                                                Tela: {{ $tela }}
                                                            @elseif($color && strtolower($color) !== 'sin color')
                                                                Color: {{ $color }}
                                                            @endif
                                                        </div>
                                                    @endif
                                                    @if(count($procesos) > 0)
                                                        <div class="text-black text-xs mt-2 space-y-0.5">
                                                            @foreach($procesos as $proceso)
                                                                <div class="flex items-start gap-1">
                                                                    <span class="text-blue-600 font-bold">•</span>
                                                                    <span>
                                                                        {{ $proceso['tipo_proceso'] ?? 'Proceso' }}
                                                                        @if(!empty($proceso['ubicaciones']))
                                                                            @php
                                                                                $ubicaciones = $proceso['ubicaciones'];

                                                                                if (is_string($ubicaciones) && (strpos($ubicaciones, '[') === 0 || strpos($ubicaciones, '{') === 0)) {
                                                                                    $ubicacionesDecodificadas = json_decode($ubicaciones, true);
                                                                                    if (is_array($ubicacionesDecodificadas)) {
                                                                                        $ubicacionesStr = implode(', ', $ubicacionesDecodificadas);
                                                                                    } else {
                                                                                        $ubicacionesStr = $ubicaciones;
                                                                                    }
                                                                                } elseif (is_array($ubicaciones)) {
                                                                                    $ubicacionesStr = implode(', ', $ubicaciones);
                                                                                } else {
                                                                                    $ubicacionesStr = $ubicaciones;
                                                                                }
                                                                            @endphp
                                                                            @if(!empty($ubicacionesStr))
                                                                                ({{ $ubicacionesStr }})
                                                                            @endif
                                                                        @endif
                                                                    </span>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                </td>
                                                @endif

                                                <td class="px-2 py-3 text-center text-[13px] text-black border-r border-slate-300" style="width: 6%;">
                                                    @php
                                                        $generoDisplay = '';
                                                        if (!empty($item['genero'])) {
                                                            $generoDisplay = $item['genero'];
                                                        } elseif ($generoFallback) {
                                                            $generoDisplay = $generoFallback;
                                                        }
                                                        $generoDisplay = (is_string($generoDisplay) && strtoupper(trim($generoDisplay)) === 'GENERICO') ? '' : $generoDisplay;
                                                    @endphp
                                                    {{ $generoDisplay ? ucfirst(strtolower($generoDisplay)) : '—' }}
                                                </td>
                                                <td class="px-2 py-3 text-center text-[10px] text-black border-r border-slate-300" style="width: 6%;">
                                                    {{ $item['talla'] ?? '—' }}
                                                </td>
                                                <td class="px-4 py-3 text-center text-xs font-bold text-black border-r border-slate-300" style="width: 6%;">
                                                    {{ $item['cantidad'] ?? 0 }}
                                                </td>
                                                <td class="px-2 py-3 border-r border-slate-300" style="width: 8%;">
                                                    <textarea
                                                        class="pendientes-input w-full px-1.5 py-1 border-2 border-slate-300 text-[10px] focus:ring-2 focus:ring-slate-500 focus:border-slate-700 outline-none transition resize-none bg-slate-50"
                                                        style="font-family: 'Poppins', sans-serif;"
                                                        data-numero-pedido="{{ $item['numero_pedido'] }}"
                                                        data-talla="{{ $item['talla'] }}"
                                                        data-talla-color-id="{{ $item['talla_color_id'] ?? '' }}"
                                                        placeholder="Pendientes..."
                                                        rows="1"
                                                        @if($esReadOnly ?? false) disabled @endif
                                                    >{{ $item['pendientes'] ?? '' }}</textarea>
                                                </td>
                                                <td class="px-4 py-3 border-r border-slate-300" style="width: 16%;">
                                                    <div class="flex gap-1">
                                                        <textarea
                                                            class="observaciones-input flex-1 px-2 py-1 border border-slate-300 text-xs text-black focus:ring-1 focus:ring-slate-500 focus:border-slate-700 outline-none transition resize-none rounded bg-slate-50"
                                                            data-numero-pedido="{{ $item['numero_pedido'] }}"
                                                            data-talla="{{ $item['talla'] }}"
                                                            data-talla-color-id="{{ $item['talla_color_id'] ?? '' }}"
                                                            placeholder="Notas..."
                                                            rows="1"
                                                            readonly
                                                        >{{ $item['observaciones_bodega'] ?? '' }}</textarea>
                                                        <button
                                                            type="button"
                                                            onclick="abrirModalNotas('{{ $item['numero_pedido'] }}', '{{ $item['talla'] }}', '{{ addslashes($item['prenda_nombre'] ?? 'Prenda') }}', 'prenda', '{{ $item['talla'] }}', '{{ $item['talla_color_id'] ?? '' }}')"
                                                            class="px-2 py-1 bg-blue-500 hover:bg-blue-600 text-white text-xs font-bold rounded transition whitespace-nowrap"
                                                            title="Ver/agregar notas"
                                                        >
                                                            💬
                                                        </button>
                                                    </div>
                                                </td>
                                                <td class="px-4 py-3 border-r border-slate-300" style="width: 12%;">
                                                    <input
                                                        type="date"
                                                        class="fecha-pedido-input w-full px-2 py-1 border border-slate-300 text-xs text-black focus:ring-1 focus:ring-slate-500 focus:border-slate-700 outline-none transition rounded bg-slate-50"
                                                        value="{{ $item['fecha_pedido'] ? \Carbon\Carbon::parse($item['fecha_pedido'])->format('Y-m-d') : '' }}"
                                                        data-numero-pedido="{{ $item['numero_pedido'] }}"
                                                        data-talla="{{ $item['talla'] }}"
                                                        data-talla-color-id="{{ $item['talla_color_id'] ?? '' }}"
                                                        @if($esReadOnly ?? false) disabled @endif
                                                    >
                                                </td>
                                                <td class="px-4 py-3 border-r border-slate-300" style="width: 12%;">
                                                    <input
                                                        type="date"
                                                        class="fecha-input w-full px-2 py-1 border border-slate-300 text-xs text-black focus:ring-1 focus:ring-slate-500 focus:border-slate-700 outline-none transition rounded bg-slate-50"
                                                        value="{{ $item['fecha_entrega'] ? \Carbon\Carbon::parse($item['fecha_entrega'])->format('Y-m-d') : '' }}"
                                                        data-numero-pedido="{{ $item['numero_pedido'] }}"
                                                        data-talla="{{ $item['talla'] }}"
                                                        data-talla-color-id="{{ $item['talla_color_id'] ?? '' }}"
                                                        @if($esReadOnly ?? false) disabled @endif
                                                    >
                                                </td>
                                            </tr>
                                        @endforeach
                                    @endif
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="8" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center">
                                            <span class="material-symbols-rounded text-slate-300 text-5xl">inventory_2</span>
                                            <p class="text-slate-500 font-medium mt-3">No hay artículos pendientes de costura</p>
                                            <p class="text-slate-400 text-sm mt-1">Este pedido no tiene artículos en el área de Costura con estado Pendiente</p>
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Toast Notification -->
<div id="toast" class="fixed bottom-4 right-4 px-5 py-3 bg-slate-900 text-white rounded text-sm font-bold uppercase tracking-wider hidden flex items-center space-x-3 z-[99999]">
    <svg class="h-4 w-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
    </svg>
    <span id="toastMessage">✓ Operación completada</span>
</div>

 <!-- Modal de Factura -->
 <div id="modalFactura" class="hidden fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-9999 overflow-auto" style="z-index: 100000;">
    <div class="bg-white rounded-lg shadow-2xl max-w-4xl w-full mx-4 my-8">
        <div class="bg-slate-900 px-6 py-4 border-b border-slate-200 flex justify-between items-center sticky top-0">
            <h2 class="text-lg font-semibold text-white"> Pedido</h2>
            <button onclick="cerrarModalFactura()" class="text-white hover:text-slate-200 text-2xl leading-none">✕</button>
        </div>
        <div id="facturaContenido" class="px-6 py-6 overflow-y-auto" style="max-height: calc(100vh - 200px)">
            <div class="flex justify-center items-center py-12">
                <span class="text-slate-500"> Cargando factura...</span>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Notas -->
<div id="modalNotas" class="fixed inset-0 bg-black bg-opacity-50 z-[9999] hidden flex items-center justify-center p-4" style="z-index: 100001;">
    <div class="bg-white rounded-lg shadow-2xl max-w-2xl w-full mx-4 my-8">
        <div class="bg-slate-900 px-6 py-4 border-b border-slate-200 flex justify-between items-center">
            <h2 class="text-lg font-semibold text-white">💬 Notas - Pedido <span id="modalNotasNumeroPedido">#</span></h2>
            <button onclick="cerrarModalNotas()" class="text-white hover:text-slate-200 text-2xl leading-none">✕</button>
        </div>
        <div class="px-6 py-6">
            <div class="mb-3 text-sm text-slate-700">
                <span class="font-semibold">Artículo:</span> <span id="modalNotasArticulo">—</span>
            </div>
            <div id="notasHistorial" class="mb-6" style="max-height: 350px; overflow-y: auto;"></div>
            
            @if(!($esReadOnly ?? false))
            <div>
                <label for="notasNuevaContent" class="block text-sm font-medium text-slate-700 mb-2">Agregar nueva nota:</label>
                <textarea
                    id="notasNuevaContent"
                    class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:border-slate-500 focus:ring-1 focus:ring-slate-500"
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
            @else
            <div class="text-center py-4">
                <button
                    type="button"
                    onclick="cerrarModalNotas()"
                    class="px-6 py-2 bg-slate-400 hover:bg-slate-500 text-white font-bold rounded-lg transition"
                >
                    Cerrar
                </button>
            </div>
            @endif
        </div>
    </div>
</div>

<script src="{{ asset('js/bodega-pedidos.js') }}"></script>

<script>
function cerrarModalFactura() {
    const modal = document.getElementById('modalFactura');
    if (modal) {
        modal.classList.add('hidden');
        modal.style.display = 'none';
    }
}

/**
 * Logs de diagnóstico para el diseño de la tabla - Costura
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log(' [DIAGNÓSTICO-COSTURA] Iniciando análisis de diseño...');
    
    // Verificar dimensiones del viewport
    const viewportHeight = window.innerHeight;
    const viewportWidth = window.innerWidth;
    console.log(` [DIAGNÓSTICO-COSTURA] Viewport: ${viewportWidth}x${viewportHeight}px`);
    
    // Verificar contenedor principal
    const mainContainer = document.querySelector('.min-h-screen');
    if (mainContainer) {
        const mainRect = mainContainer.getBoundingClientRect();
        console.log(` [DIAGNÓSTICO-COSTURA] Contenedor principal:`, {
            width: mainRect.width,
            height: mainRect.height,
            computedHeight: window.getComputedStyle(mainContainer).height,
            classes: mainContainer.className
        });
    }
    
    // Verificar contenedor de la tabla
    const tableContainer = document.querySelector('.overflow-x-auto');
    if (tableContainer) {
        const tableRect = tableContainer.getBoundingClientRect();
        const computedStyle = window.getComputedStyle(tableContainer);
        console.log(`🗂️ [DIAGNÓSTICO-COSTURA] Contenedor de tabla:`, {
            width: tableRect.width,
            height: tableRect.height,
            computedHeight: computedStyle.height,
            customHeight: computedStyle.getPropertyValue('height'),
            overflowX: computedStyle.overflowX,
            overflowY: computedStyle.overflowY,
            classes: tableContainer.className
        });
    }
    
    // Verificar tabla
    const table = document.querySelector('table');
    if (table) {
        const tableRect = table.getBoundingClientRect();
        const tableStyle = window.getComputedStyle(table);
        console.log(` [DIAGNÓSTICO-COSTURA] Tabla:`, {
            width: tableRect.width,
            height: tableRect.height,
            computedWidth: tableStyle.width,
            computedHeight: tableStyle.height,
            tableLayout: tableStyle.tableLayout,
            scrollWidth: table.scrollWidth,
            scrollHeight: table.scrollHeight,
            classes: table.className
        });
    }
    
    // Verificar si hay scroll
    setTimeout(() => {
        const tableContainer = document.querySelector('.overflow-x-auto');
        if (tableContainer) {
            console.log(` [DIAGNÓSTICO-COSTURA] Estado del scroll:`, {
                hasHorizontalScroll: tableContainer.scrollWidth > tableContainer.clientWidth,
                hasVerticalScroll: tableContainer.scrollHeight > tableContainer.clientHeight,
                scrollWidth: tableContainer.scrollWidth,
                clientWidth: tableContainer.clientWidth,
                scrollHeight: tableContainer.scrollHeight,
                clientHeight: tableContainer.clientHeight
            });
        }
    }, 1000);
});

/**
 * Auto-resize textareas para Pendientes y Observaciones
 */
document.addEventListener('DOMContentLoaded', function() {
    const textareas = document.querySelectorAll('.pendientes-input, .observaciones-input');
    
    function autoResizeTextarea(textarea) {
        textarea.style.height = 'auto';
        textarea.style.height = textarea.scrollHeight + 'px';
    }
    
    textareas.forEach(textarea => {
        autoResizeTextarea(textarea);
        
        textarea.addEventListener('input', function() {
            autoResizeTextarea(this);
        });
    });
});
</script>
@endsection
