@extends('layouts.app-without-sidebar')

@section('title', "Gestión de Bodega - Pedido {$pedido['numero_pedido']}")

@section('content')
<div class="min-h-screen bg-slate-50 w-full flex flex-col">
    <div class="w-full flex-shrink-0">
        <!-- Header -->
        <div class="bg-white border-b border-slate-200 px-4 py-4 sm:px-6 sm:py-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl sm:text-2xl font-semibold text-black">Gestión de Bodega</h1>
                    <p class="text-xs sm:text-sm text-black mt-1">
                        N° Pedido: <span class="font-semibold text-black">{{ $pedido['numero_pedido'] }}</span> | 
                        Cliente: <span class="font-semibold text-black">{{ $pedido['cliente'] ?? 'No especificado' }}</span>
                        @if($pedido['asesor'])
                            | Asesor: <span class="font-semibold text-black">{{ $pedido['asesor'] }}</span>
                        @endif
                    </p>
                    @if(isset($filtro_aplicado))
                        <div class="mt-2 p-2 bg-orange-100 border border-orange-200 rounded">
                            <p class="text-xs font-medium text-orange-800">
                                <span class="material-symbols-rounded text-sm align-middle">filter_alt</span>
                                {{ $filtro_aplicado['descripcion'] }}
                            </p>
                        </div>
                    @endif
                </div>
                <div class="flex items-center gap-4">
                    <a href="{{ route('gestion-bodega.pedidos') }}" 
                       class="px-4 py-2 border border-slate-300 text-black hover:text-black font-medium rounded transition-colors">
                        ← Volver
                    </a>
                    @if($pedido['id'])
                        <button type="button"
                                onclick="abrirModalFactura({{ $pedido['id'] }})"
                                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded transition-colors">
                            Ver Pedido
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
                                <th class="px-4 py-3 text-center text-[11px] font-semibold text-black uppercase tracking-widest" style="width: 18%;">Área / Estado</th>
                            </tr>
                        </thead>
                        
                        <tbody id="pedidosTableBody" class="divide-y divide-slate-300">
                            @if($items && count($items) > 0)
                                @php
                                    $itemsAgrupados = collect($items)->groupBy(function ($it) {
                                        if (($it['tipo'] ?? null) === 'epp') {
                                            return 'epp_' . ($it['pedido_epp_id'] ?? ($it['talla'] ?? ''));
                                        }
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
                                                // Normalizar género a mayúsculas
                                                $generoItem = isset($it['genero']) ? strtoupper($it['genero']) : '';
                                                $itTallaColorId = $it['talla_color_id'] ?? ($it['tallaColorId'] ?? '');
                                                
                                                // Usar row_hash como clave única (incluye: numero_pedido, prenda_id, talla, talla_color_id, genero)
                                                $itKey = md5(
                                                    ($it['numero_pedido'] ?? '') . '_' .
                                                    ($it['prenda_id'] ?? '') . '_' .
                                                    ($it['talla'] ?? '') . '_' .
                                                    $itTallaColorId . '_' .
                                                    $generoItem
                                                );
                                                
                                                $itemsPorTallaColor[$itKey] = $it;
                                                
                                                // Mantener $itemsPorTalla solo como fallback
                                                $itemsPorTalla[$it['talla']] = $it;
                                            }
                                        }

                                        $desc = $primeraItem['descripcion'] ?? [];
                                        $nombre = $desc['nombre_prenda'] ?? $desc['nombre'] ?? 'Prenda sin nombre';
                                        $tela = $desc['tela'] ?? null;
                                        $color = $desc['color'] ?? null;
                                        $variantes = $desc['variantes'] ?? [];
                                        $procesos = $desc['procesos'] ?? [];
                                        $primeraVariante = count($variantes) > 0 ? $variantes[0] : null;

                                        // Group by color and then by gender
                                        $gruposPorColorGenero = [];
                                        if (($primeraItem['tipo'] ?? null) === 'prenda' && is_array($variantes)) {
                                            foreach ($variantes as $variante) {
                                                $generoVar = strtoupper($variante['genero'] ?? '');
                                                $tallaVar = $variante['talla'] ?? '';

                                                if ($generoVar === 'GENERICO') {
                                                    continue;
                                                }

                                                if (isset($variante['colores_detalle']) && is_array($variante['colores_detalle']) && !empty($variante['colores_detalle'])) {
                                                    // Tiene colores detallados
                                                    foreach ($variante['colores_detalle'] as $colorDetalle) {
                                                        $rawColor = $colorDetalle['color'] ?? '';
                                                        $esColorValido = !empty($rawColor) && strtolower(trim($rawColor)) !== 'sin color';
                                                        $colorKey = $esColorValido ? strtoupper($rawColor) : '__SIN_COLOR__';
                                                        $cantidadColor = (int)($colorDetalle['cantidad'] ?? 0);
                                                        $tallaColorId = $colorDetalle['talla_color_id'] ?? ($colorDetalle['tallaColorId'] ?? null);

                                                        if (!empty($tallaVar) && $cantidadColor > 0) {
                                                            // Group by color first, then by gender
                                                            if (!isset($gruposPorColorGenero[$colorKey])) {
                                                                $gruposPorColorGenero[$colorKey] = [];
                                                            }
                                                            if (!isset($gruposPorColorGenero[$colorKey][$generoVar])) {
                                                                $gruposPorColorGenero[$colorKey][$generoVar] = [
                                                                    'color' => $colorKey,
                                                                    'genero' => $generoVar,
                                                                    'tallas' => [],
                                                                ];
                                                            }
                                                            $gruposPorColorGenero[$colorKey][$generoVar]['tallas'][] = [
                                                                'talla' => $tallaVar,
                                                                'genero' => $variante['genero'] ?? null,
                                                                'cantidad' => $cantidadColor,
                                                                'tallaColorId' => $tallaColorId,
                                                            ];
                                                        }
                                                    }
                                                } else {
                                                    // No tiene colores detallados, agregar directamente con cantidad de la variante
                                                    $cantidadVar = (int)($variante['cantidad'] ?? 0);
                                                    if (!empty($tallaVar) && !empty($generoVar) && $cantidadVar > 0) {
                                                        $colorKey = '__SIN_COLOR__';
                                                        if (!isset($gruposPorColorGenero[$colorKey])) {
                                                            $gruposPorColorGenero[$colorKey] = [];
                                                        }
                                                        if (!isset($gruposPorColorGenero[$colorKey][$generoVar])) {
                                                            $gruposPorColorGenero[$colorKey][$generoVar] = [
                                                                'color' => $colorKey,
                                                                'genero' => $generoVar,
                                                                'tallas' => [],
                                                            ];
                                                        }
                                                        $gruposPorColorGenero[$colorKey][$generoVar]['tallas'][] = [
                                                            'talla' => $tallaVar,
                                                            'genero' => $variante['genero'] ?? null,
                                                            'cantidad' => $cantidadVar,
                                                            'tallaColorId' => null,
                                                        ];
                                                    }
                                                }
                                            }
                                        }

                                        // Sort sizes within each color-gender group
                                        foreach ($gruposPorColorGenero as &$gruposPorGenero) {
                                            foreach ($gruposPorGenero as &$grupoGenero) {
                                                usort($grupoGenero['tallas'], function ($a, $b) {
                                                    $nA = is_numeric($a['talla']) ? (int)$a['talla'] : null;
                                                    $nB = is_numeric($b['talla']) ? (int)$b['talla'] : null;
                                                    if ($nA !== null && $nB !== null) return $nA - $nB;
                                                    return strcmp($a['talla'], $b['talla']);
                                                });
                                            }
                                        }
                                        unset($gruposPorGenero, $grupoGenero);

                                        $tieneColoresPorTalla = !empty($gruposPorColorGenero);
                                    @endphp

                                    @if($tieneColoresPorTalla)
                                        @foreach($gruposPorColorGenero as $colorKey => $gruposPorGenero)
                                            @php
                                                $colorLabel = $colorKey === '__SIN_COLOR__' ? null : $colorKey;
                                                // Count total rows for this color (for description rowspan)
                                                $totalRowsColor = 0;
                                                foreach ($gruposPorGenero as $grupoGenero) {
                                                    $totalRowsColor += count($grupoGenero['tallas']);
                                                }
                                                $isFirstRowOfColor = true;
                                            @endphp
                                            @foreach($gruposPorGenero as $generoKey => $grupoGenero)
                                                @php
                                                    $rowSpanGenero = count($grupoGenero['tallas']);
                                                @endphp
                                                @foreach($grupoGenero['tallas'] as $indexTalla => $t)
                                                    @php
                                                        // Normalizar talla_color_id a string vacío si es null
                                                        $tallaColorIdNormalizado = $t['tallaColorId'] ?? '';
                                                        
                                                        // Calcular la clave única usando el mismo formato que en el array
                                                        $tKey = md5(
                                                            ($primeraItem['numero_pedido'] ?? '') . '_' .
                                                            ($primeraItem['prenda_id'] ?? '') . '_' .
                                                            ($t['talla'] ?? '') . '_' .
                                                            $tallaColorIdNormalizado . '_' .
                                                            $generoKey
                                                        );
                                                        
                                                        $baseItem = $itemsPorTallaColor[$tKey] ?? ($itemsPorTalla[$t['talla']] ?? $primeraItem);
                                                        
                                                        // Crear identificador único para cada fila incluyendo género
                                                        $rowHash = md5(
                                                            ($baseItem['numero_pedido'] ?? '') . '_' . 
                                                            ($baseItem['prenda_id'] ?? '') . '_' . 
                                                            ($baseItem['talla'] ?? '') . '_' . 
                                                            $tallaColorIdNormalizado . '_' .
                                                            ($generoKey ?? '')
                                                        );
                                                    @endphp
                                                    <tr class="hover:bg-slate-50 transition-colors"
                                                        data-row-hash="{{ $rowHash }}"
                                                        data-numero-pedido="{{ $baseItem['numero_pedido'] }}"
                                                        data-prenda-id="{{ $baseItem['prenda_id'] ?? '' }}"
                                                        data-talla="{{ $baseItem['talla'] ?? '' }}"
                                                        data-genero="{{ $generoKey ?? '' }}"
                                                        data-talla-color-id="{{ $t['tallaColorId'] ?? '' }}"
                                                        data-asesor="{{ is_string($baseItem['asesor'] ?? null) && !empty($baseItem['asesor']) ? $baseItem['asesor'] : 'N/A' }}"
                                                        data-empresa="{{ is_string($baseItem['empresa'] ?? null) && !empty($baseItem['empresa']) ? $baseItem['empresa'] : 'N/A' }}"
                                                        @if(($baseItem['estado_bodega'] ?? '') === 'Homologar')
                                                            style="background-color: rgba(147, 51, 234, 0.08);"
                                                        @elseif(($baseItem['estado_bodega'] ?? '') === 'Entregado')
                                                            style="background-color: rgba(37, 99, 235, 0.05);"
                                                        @endif
                                                    >
                                                        <!-- DESCRIPCIÓN (PRENDA) - once per color group -->
                                                        @if($isFirstRowOfColor)
                                                        <td class="px-4 py-3 text-xs text-black border-r border-slate-300" rowspan="{{ $totalRowsColor }}" style="width: 22%;">
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

                                                            @if(isset($primeraVariante) && $primeraVariante)
                                                                @php
                                                                    $variantesInfo = [];

                                                                    if(!empty($primeraVariante['manga'])) {
                                                                        $mangaObs = !empty($primeraVariante['manga_obs']) ? $primeraVariante['manga_obs'] : '';
                                                                        $variantesInfo[] = 'Manga:' . $primeraVariante['manga'] . ' (' . $mangaObs . ')';
                                                                    }

                                                                    if(!empty($primeraVariante['broche'])) {
                                                                        $brocheObs = !empty($primeraVariante['broche_obs']) ? $primeraVariante['broche_obs'] : '';
                                                                        $variantesInfo[] = $primeraVariante['broche'] . ' (' . $brocheObs . ')';
                                                                    }

                                                                    if(!empty($primeraVariante['bolsillos'])) {
                                                                        $bolsillosObs = !empty($primeraVariante['bolsillos_obs']) ? $primeraVariante['bolsillos_obs'] : '';
                                                                        $variantesInfo[] = 'Bolsillos (' . $bolsillosObs . ')';
                                                                    }
                                                                @endphp
                                                                @if(!empty($variantesInfo))
                                                                    <div class="text-slate-900 mb-1 text-xs space-y-0.5">
                                                                        @foreach($variantesInfo as $variante)
                                                                            <div>• {{ $variante }}</div>
                                                                        @endforeach
                                                                    </div>
                                                                @endif
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
                                                        @php $isFirstRowOfColor = false; @endphp
                                                        @endif

                                                        <!-- GÉNERO (once per gender within color group) -->
                                                        @if($indexTalla === 0)
                                                        <td class="px-2 py-3 text-center text-[13px] text-black border-r border-slate-300" rowspan="{{ $rowSpanGenero }}" style="width: 6%;">
                                                            @php
                                                                $gen = $generoKey;
                                                                $gen = (is_string($gen) && strtoupper(trim($gen)) === 'GENERICO') ? '' : $gen;
                                                            @endphp
                                                            {{ $gen ? ucfirst(strtolower($gen)) : '—' }}
                                                        </td>
                                                        @endif

                                                    <!-- TALLA -->
                                                    <td class="px-2 py-3 text-center text-[10px] text-black border-r border-slate-300" style="width: 6%;">
                                                        @if(($t['talla'] ?? null) === 'SIN_ESPECIFICAR')
                                                            —
                                                        @else
                                                            {{ $t['talla'] ?? '—' }}
                                                        @endif
                                                    </td>

                                                    <!-- CANTIDAD (por color) -->
                                                    <td class="px-4 py-3 text-center text-xs font-bold text-black border-r border-slate-300" style="width: 6%;">
                                                        {{ $t['cantidad'] ?? 0 }}
                                                    </td>

                                                    <!-- PENDIENTES -->
                                                    <td class="px-2 py-3 border-r border-slate-300" style="width: 8%;">
                                                        <textarea
                                                            class="pendientes-input w-full px-1.5 py-1 border-2 border-slate-300 text-[10px] focus:ring-2 focus:ring-slate-500 focus:border-slate-700 outline-none transition resize-none
                                                                @if(($baseItem['estado_bodega'] ?? '') === 'Entregado')
                                                                    bg-blue-50
                                                                @else
                                                                    bg-slate-50
                                                                @endif"
                                                            style="font-family: 'Poppins', sans-serif;"
                                                            data-row-hash="{{ $rowHash }}"
                                                            data-numero-pedido="{{ $baseItem['numero_pedido'] }}"
                                                            data-talla="{{ $baseItem['talla'] }}"
                                                            data-talla-color-id="{{ $t['tallaColorId'] ?? '' }}"
                                                            data-prenda-id="{{ $baseItem['prenda_id'] ?? '' }}"
                                                            data-pedido-produccion-id="{{ $baseItem['pedido_produccion_id'] }}"
                                                            data-recibo-prenda-id="{{ $baseItem['recibo_prenda_id'] }}"
                                                            placeholder="Pendientes..."
                                                            rows="1"
                                                            @if($esReadOnly ?? false) disabled @endif
                                                        >{{ $baseItem['pendientes'] ?? '' }}</textarea>
                                                    </td>

                                                    <!-- OBSERVACIONES -->
                                                    <td class="px-4 py-3 border-r border-slate-300" style="width: 16%;">
                                                        <div class="flex gap-1">
                                                            <textarea
                                                                class="observaciones-input flex-1 px-2 py-1 border border-slate-300 text-xs text-black focus:ring-1 focus:ring-slate-500 focus:border-slate-700 outline-none transition resize-none rounded
                                                                    @if(($baseItem['estado_bodega'] ?? '') === 'Entregado')
                                                                        bg-blue-50
                                                                    @else
                                                                        bg-slate-50
                                                                    @endif"
                                                                data-row-hash="{{ $rowHash }}"
                                                                data-numero-pedido="{{ $baseItem['numero_pedido'] }}"
                                                                data-talla="{{ $baseItem['talla'] }}"
                                                                data-talla-color-id="{{ $t['tallaColorId'] ?? '' }}"
                                                                data-prenda-id="{{ $baseItem['prenda_id'] ?? '' }}"
                                                                placeholder="Notas..."
                                                                rows="1"
                                                                readonly
                                                            >{{ $baseItem['observaciones'] ?? '' }}</textarea>
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

                                                    <!-- FECHA PEDIDO -->
                                                    <td class="px-4 py-3 border-r border-slate-300" style="width: 12%;">
                                                        <input
                                                            type="date"
                                                            class="fecha-pedido-input w-full px-2 py-1 border border-slate-300 text-xs text-black focus:ring-1 focus:ring-slate-500 focus:border-slate-700 outline-none transition rounded
                                                                @if(($baseItem['estado_bodega'] ?? '') === 'Entregado')
                                                                    bg-blue-50
                                                                @else
                                                                    bg-slate-50
                                                                @endif"
                                                            value="{{ $baseItem['fecha_pedido'] ? $baseItem['fecha_pedido'] : '' }}"
                                                            data-row-hash="{{ $rowHash }}"
                                                            data-numero-pedido="{{ $baseItem['numero_pedido'] }}"
                                                            data-talla="{{ $baseItem['talla'] }}"
                                                            data-talla-color-id="{{ $t['tallaColorId'] ?? '' }}"
                                                            data-prenda-id="{{ $baseItem['prenda_id'] ?? '' }}"
                                                            data-pedido-produccion-id="{{ $baseItem['pedido_produccion_id'] ?? '' }}"
                                                            data-recibo-prenda-id="{{ $baseItem['recibo_prenda_id'] ?? '' }}"
                                                            @if($esReadOnly ?? false) disabled @endif
                                                        >
                                                    </td>

                                                    <!-- FECHA ENTREGA -->
                                                    <td class="px-4 py-3 border-r border-slate-300" style="width: 12%;">
                                                        <input
                                                            type="date"
                                                            class="fecha-input w-full px-2 py-1 border border-slate-300 text-xs text-black focus:ring-1 focus:ring-slate-500 focus:border-slate-700 outline-none transition rounded
                                                                @if(($baseItem['estado_bodega'] ?? '') === 'Entregado')
                                                                    bg-blue-50
                                                                @else
                                                                    bg-slate-50
                                                                @endif"
                                                            value="{{ $baseItem['fecha_entrega'] ? $baseItem['fecha_entrega'] : '' }}"
                                                            data-row-hash="{{ $rowHash }}"
                                                            data-numero-pedido="{{ $baseItem['numero_pedido'] }}"
                                                            data-talla="{{ $baseItem['talla'] }}"
                                                            data-talla-color-id="{{ $t['tallaColorId'] ?? '' }}"
                                                            data-prenda-id="{{ $baseItem['prenda_id'] ?? '' }}"
                                                            data-pedido-produccion-id="{{ $baseItem['pedido_produccion_id'] }}"
                                                            data-recibo-prenda-id="{{ $baseItem['recibo_prenda_id'] }}"
                                                            @if(($baseItem['estado_bodega'] ?? '') === 'Entregado' || ($esReadOnly ?? false))
                                                                disabled
                                                            @endif
                                                        >
                                                    </td>

                                                    <!-- ÁREA / ESTADO -->
                                                    <td class="px-4 py-3" style="width: 18%;">
                                                        <div class="space-y-2">
                                                            <select
                                                                class="area-select w-full px-2 py-1 border border-slate-300 bg-white text-black text-xs font-semibold uppercase rounded"
                                                                data-row-hash="{{ $rowHash }}"
                                                                data-numero-pedido="{{ $baseItem['numero_pedido'] }}"
                                                                data-talla="{{ $baseItem['talla'] }}"
                                                                data-talla-color-id="{{ $t['tallaColorId'] ?? '' }}"
                                                                data-prenda-id="{{ $baseItem['prenda_id'] ?? '' }}"
                                                                data-pedido-produccion-id="{{ $baseItem['pedido_produccion_id'] ?? '' }}"
                                                                data-recibo-prenda-id="{{ $baseItem['recibo_prenda_id'] ?? '' }}"
                                                                data-original-area="{{ $baseItem['area'] ?? '' }}"
                                                                @if($esReadOnly ?? false) disabled @endif
                                                            >
                                                                <option value="">ÁREA</option>
                                                                <option value="Costura" {{ ($baseItem['area'] ?? null) === 'Costura' ? 'selected' : '' }}>COSTURA</option>
                                                                <option value="EPP" {{ ($baseItem['area'] ?? null) === 'EPP' ? 'selected' : '' }}>EPP</option>
                                                            </select>

                                                            <select
                                                                class="estado-select w-full px-2 py-1 border border-slate-300 bg-white text-black text-xs font-semibold uppercase rounded"
                                                                data-row-hash="{{ $rowHash }}"
                                                                data-numero-pedido="{{ $baseItem['numero_pedido'] }}"
                                                                data-talla="{{ $baseItem['talla'] }}"
                                                                data-talla-color-id="{{ $t['tallaColorId'] ?? '' }}"
                                                                data-pedido-produccion-id="{{ $baseItem['pedido_produccion_id'] ?? '' }}"
                                                                data-recibo-prenda-id="{{ $baseItem['recibo_prenda_id'] ?? '' }}"
                                                                data-prenda-nombre="{{ $baseItem['descripcion']['nombre_prenda'] ?? $baseItem['descripcion']['nombre'] ?? '' }}"
                                                                data-prenda-id="{{ $baseItem['prenda_id'] ?? '' }}"
                                                                data-pedido-epp-id="{{ $baseItem['pedido_epp_id'] ?? '' }}"
                                                                data-cantidad="{{ $baseItem['cantidad_total'] }}"
                                                                data-original-estado="{{ $baseItem['estado_bodega'] ?? '' }}"
                                                                @if($esReadOnly ?? false) disabled @endif
                                                            >
                                                                <option value="">ESTADO</option>
                                                                <option value="Pendiente" {{ ($baseItem['estado_bodega'] ?? null) === 'Pendiente' ? 'selected' : '' }}>PENDIENTE</option>
                                                                <option value="Entregado" {{ ($baseItem['estado_bodega'] ?? null) === 'Entregado' ? 'selected' : '' }}>ENTREGADO</option>
                                                                <option value="Homologar" {{ ($baseItem['estado_bodega'] ?? null) === 'Homologar' ? 'selected' : '' }}>HOMOLOGAR</option>
                                                                @if(auth()->user()->hasRole(['Bodeguero', 'Admin', 'SuperAdmin']))
                                                                <option value="Anulado" {{ ($baseItem['estado_bodega'] ?? null) === 'Anulado' ? 'selected' : '' }}>ANULADO</option>
                                                                @endif
                                                            </select>

                                                            @if(($baseItem['homologado_de'] ?? null) && ($baseItem['pedido_epp_id'] ?? null) && ($baseItem['homologado_por'] ?? null))
                                                            <button type="button" 
                                                                    onclick="toggleHomologacionRow(this, {{ json_encode($baseItem['homologado_por']) }})" 
                                                                    title="EPP homologado"
                                                                    class="w-full px-2 py-1 bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold uppercase rounded transition toggle-homologacion-btn">
                                                                🔽 Ver Homologación
                                                            </button>
                                                            @endif

                                                            @if(!($esReadOnly ?? false) && !auth()->user()->hasRole('supervisor_gerencia'))
                                                            <button
                                                                type="button"
                                                                onclick="guardarFilaCompleta(this, '{{ $baseItem['numero_pedido'] }}', '{{ $baseItem['talla'] }}', '{{ $t['tallaColorId'] ?? '' }}', '{{ $baseItem['prenda_id'] ?? '' }}')"
                                                                class="w-full px-2 py-1 bg-green-500 hover:bg-green-600 text-white text-xs font-bold uppercase rounded transition"
                                                            >
                                                                💾 Guardar
                                                            </button>
                                                            @elseif(auth()->user()->hasRole('supervisor_gerencia'))
                                                            <button
                                                                type="button"
                                                                disabled
                                                                class="w-full px-2 py-1 bg-gray-400 text-white text-xs font-bold uppercase rounded cursor-not-allowed opacity-60"
                                                                title="Solo usuarios autorizados pueden guardar cambios"
                                                            >
                                                                💾 Guardar
                                                            </button>
                                                            @else
                                                            <div class="w-full px-2 py-1 bg-slate-100 text-slate-500 text-xs font-medium text-center rounded">
                                                                Guardado deshabilitado
                                                            </div>
                                                            @endif
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                            @endforeach
                                        @endforeach
                                    @else
                                        @foreach($grupo as $item)
                                            @php
                                                // Crear identificador único para cada fila (prendas sin tallas múltiples)
                                                $rowHashSimple = md5(
                                                    ($item['numero_pedido'] ?? '') . '_' . 
                                                    ($item['prenda_id'] ?? '') . '_' . 
                                                    ($item['talla'] ?? '')
                                                );
                                            @endphp
                                            <tr class="hover:bg-slate-50 transition-colors"
                                                data-row-hash="{{ $rowHashSimple }}"
                                                data-numero-pedido="{{ $item['numero_pedido'] }}"
                                                data-asesor="{{ is_string($item['asesor'] ?? null) && !empty($item['asesor']) ? $item['asesor'] : 'N/A' }}"
                                                data-empresa="{{ is_string($item['empresa'] ?? null) && !empty($item['empresa']) ? $item['empresa'] : 'N/A' }}"
                                                @if($item['estado_bodega'] === 'Homologar')
                                                    style="background-color: rgba(147, 51, 234, 0.08);"
                                                @elseif($item['estado_bodega'] === 'Entregado')
                                                    style="background-color: rgba(37, 99, 235, 0.05);"
                                                @endif
                                            >
                                                <!-- DESCRIPCIÓN (PRENDA) -->
                                                @if(($item['descripcion_rowspan'] ?? 0) > 0)
                                                <td class="px-4 py-3 text-xs text-black border-r border-slate-300" rowspan="{{ $item['descripcion_rowspan'] }}" style="width: 22%;">
                                                    @php
                                                        $desc = $item['descripcion'];
                                                        $nombre = $desc['nombre_prenda'] ?? $desc['nombre'] ?? 'Prenda sin nombre';
                                                        $tela = $desc['tela'] ?? null;
                                                        $color = $desc['color'] ?? null;
                                                        $variantes = $desc['variantes'] ?? [];
                                                        $primeraVariante = count($variantes) > 0 ? $variantes[0] : null;
                                                        $genero = $primeraVariante['genero'] ?? null;
                                                        $procesos = $desc['procesos'] ?? [];
                                                        
                                                        // Debug logging
                                                        \Log::debug('[BODEGA-VARIANTES] Datos de variante', [
                                                            'item_id' => $item['id'] ?? 'unknown',
                                                            'primeraVariante' => $primeraVariante,
                                                            'tipo_manga_id' => $primeraVariante['tipo_manga_id'] ?? 'NULL',
                                                            'tipo_broche_boton_id' => $primeraVariante['tipo_broche_boton_id'] ?? 'NULL',
                                                            'bolsillos' => $primeraVariante['bolsillos'] ?? 'NULL',
                                                            'tipoManga_value' => $primeraVariante['tipoManga_value'] ?? 'NULL',
                                                            'tipoBroche_value' => $primeraVariante['tipoBroche_value'] ?? 'NULL'
                                                        ]);
                                                    @endphp
                                                    <div class="font-bold text-black mb-1">
                                                        {{ $nombre }}
                                                        @if(($item['de_bodega'] ?? ($desc['de_bodega'] ?? ($item['objetoPrenda']['de_bodega'] ?? false))) )
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
                                                    
                                                    <!-- Variantes (manga, botón, bolsillos) -->
                                                    @if(isset($primeraVariante) && $primeraVariante)
                                                        @php
                                                            $variantesInfo = [];
                                                            
                                                            // Manga
                                                            if(!empty($primeraVariante['manga'])) {
                                                                $mangaObs = !empty($primeraVariante['manga_obs']) ? $primeraVariante['manga_obs'] : '';
                                                                $variantesInfo[] = 'Manga:' . $primeraVariante['manga'] . ' (' . $mangaObs . ')';
                                                            }
                                                            
                                                            // Botón
                                                            if(!empty($primeraVariante['broche'])) {
                                                                $brocheObs = !empty($primeraVariante['broche_obs']) ? $primeraVariante['broche_obs'] : '';
                                                                $variantesInfo[] = $primeraVariante['broche'] . ' (' . $brocheObs . ')';
                                                            }
                                                            
                                                            // Bolsillos
                                                            if(!empty($primeraVariante['bolsillos'])) {
                                                                $bolsillosObs = !empty($primeraVariante['bolsillos_obs']) ? $primeraVariante['bolsillos_obs'] : '';
                                                                $variantesInfo[] = 'Bolsillos (' . $bolsillosObs . ')';
                                                            }
                                                        @endphp
                                                        @if(!empty($variantesInfo))
                                                            <div class="text-slate-900 mb-1 text-xs space-y-0.5">
                                                                @foreach($variantesInfo as $variante)
                                                                    <div>• {{ $variante }}</div>
                                                                @endforeach
                                                            </div>
                                                        @endif
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
                                                                                
                                                                                // Si es un JSON string, decodificar
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
                                                
                                                <!-- GÉNERO -->
                                                @if(($item['genero_rowspan'] ?? 0) > 0)
                                                <td class="px-2 py-3 text-center text-[13px] text-black border-r border-slate-300" rowspan="{{ $item['genero_rowspan'] }}" style="width: 6%;">
                                                    @if(($item['tipo'] ?? null) === 'epp' || ($item['area'] ?? null) === 'EPP')
                                                        —
                                                    @else
                                                        @php
                                                            $genero = '';
                                                            if(isset($item['descripcion']['variantes']) && is_array($item['descripcion']['variantes']) && count($item['descripcion']['variantes']) > 0) {
                                                                // Buscar género por la talla del item
                                                                foreach ($item['descripcion']['variantes'] as $variante) {
                                                                    if (($variante['talla'] ?? '') === ($item['talla'] ?? '')) {
                                                                        $genero = $variante['genero'] ?? '';
                                                                        break;
                                                                    }
                                                                }
                                                                // Si no encontró, usar el primero
                                                                if (empty($genero)) {
                                                                    $genero = $item['descripcion']['variantes'][0]['genero'] ?? '';
                                                                }
                                                            }
                                                            elseif(isset($item['genero'])) {
                                                                $genero = $item['genero'];
                                                            }
                                                        @endphp
                                                        @if($genero && strtoupper($genero) !== 'GENERICO')
                                                            {{ ucfirst(strtolower($genero)) }}
                                                        @else
                                                            —
                                                        @endif
                                                    @endif
                                                </td>
                                                @endif
                                                
                                                <!-- TALLA -->
                                                <td class="px-2 py-3 text-center text-[10px] text-black border-r border-slate-300" style="width: 6%;">
                                                    @if(($item['tipo'] ?? null) === 'epp' || ($item['area'] ?? null) === 'EPP')
                                                        —
                                                    @elseif(($item['talla'] ?? null) === 'SIN_ESPECIFICAR')
                                                        —
                                                    @else
                                                        {{ $item['talla'] ?? '—' }}
                                                    @endif
                                                </td>
                                                
                                                <!-- CANTIDAD -->
                                                <td class="px-4 py-3 text-center text-xs font-bold text-black border-r border-slate-300" style="width: 6%;">
                                                    {{ $item['cantidad_total'] ?? 0 }}
                                                </td>
                                                
                                                <!-- PENDIENTES -->
                                                <td class="px-2 py-3 border-r border-slate-300" style="width: 8%;">
                                                    <textarea
                                                        class="pendientes-input w-full px-1.5 py-1 border-2 border-slate-300 text-[10px] focus:ring-2 focus:ring-slate-500 focus:border-slate-700 outline-none transition resize-none
                                                            @if($item['estado_bodega'] === 'Entregado')
                                                                bg-blue-50
                                                            @else
                                                                bg-slate-50
                                                            @endif"
                                                        style="font-family: 'Poppins', sans-serif;"
                                                        data-row-hash="{{ $rowHashSimple }}"
                                                        data-numero-pedido="{{ $item['numero_pedido'] }}"
                                                        data-talla="{{ $item['talla'] }}"
                                                        data-prenda-id="{{ $item['prenda_id'] ?? '' }}"
                                                        data-pedido-produccion-id="{{ $item['pedido_produccion_id'] }}"
                                                        data-recibo-prenda-id="{{ $item['recibo_prenda_id'] }}"
                                                        placeholder="Pendientes..."
                                                        rows="1"
                                                        @if($esReadOnly ?? false) disabled @endif
                                                    >{{ $item['pendientes'] ?? '' }}</textarea>
                                                </td>
                                                
                                                <!-- OBSERVACIONES -->
                                                <td class="px-4 py-3 border-r border-slate-300" style="width: 16%;">
                                                    <div class="flex gap-1">
                                                        <textarea
                                                            class="observaciones-input flex-1 px-2 py-1 border border-slate-300 text-xs text-black focus:ring-1 focus:ring-slate-500 focus:border-slate-700 outline-none transition resize-none rounded
                                                                @if($item['estado_bodega'] === 'Entregado')
                                                                    bg-blue-50
                                                                @else
                                                                    bg-slate-50
                                                                @endif"
                                                            data-row-hash="{{ $rowHashSimple }}"
                                                            data-numero-pedido="{{ $item['numero_pedido'] }}"
                                                            data-talla="{{ $item['talla'] }}"
                                                            data-prenda-id="{{ $item['prenda_id'] ?? '' }}"
                                                            placeholder="Notas..."
                                                            rows="1"
                                                            readonly
                                                        >{{ $item['observaciones'] ?? '' }}</textarea>
                                                        <button
                                                            type="button"
                                                            onclick="abrirModalNotas('{{ $item['numero_pedido'] }}', '{{ $item['talla'] }}', '{{ addslashes($nombre) }}', 'prenda', '{{ $item['talla'] }}')"
                                                            class="px-2 py-1 bg-blue-500 hover:bg-blue-600 text-white text-xs font-bold rounded transition whitespace-nowrap"
                                                            title="Ver/agregar notas"
                                                        >
                                                            💬
                                                        </button>
                                                    </div>
                                                </td>
                                                
                                                <!-- FECHA PEDIDO -->
                                                <td class="px-4 py-3 border-r border-slate-300" style="width: 12%;">
                                                    <input
                                                        type="date"
                                                        class="fecha-pedido-input w-full px-2 py-1 border border-slate-300 text-xs text-black focus:ring-1 focus:ring-slate-500 focus:border-slate-700 outline-none transition rounded
                                                            @if($item['estado_bodega'] === 'Entregado')
                                                                bg-blue-50
                                                            @else
                                                                bg-slate-50
                                                            @endif"
                                                        value="{{ $item['fecha_pedido'] ? $item['fecha_pedido'] : '' }}"
                                                        data-row-hash="{{ $rowHashSimple }}"
                                                        data-numero-pedido="{{ $item['numero_pedido'] }}"
                                                        data-talla="{{ $item['talla'] }}"
                                                        data-prenda-id="{{ $item['prenda_id'] ?? '' }}"
                                                        data-pedido-produccion-id="{{ $item['pedido_produccion_id'] ?? '' }}"
                                                        data-recibo-prenda-id="{{ $item['recibo_prenda_id'] ?? '' }}"
                                                        @if($esReadOnly ?? false) disabled @endif
                                                    >
                                                </td>
                                                
                                                <!-- FECHA ENTREGA -->
                                                <td class="px-4 py-3 border-r border-slate-300" style="width: 12%;">
                                                    <input
                                                        type="date"
                                                        class="fecha-input w-full px-2 py-1 border border-slate-300 text-xs text-black focus:ring-1 focus:ring-slate-500 focus:border-slate-700 outline-none transition rounded
                                                            @if($item['estado_bodega'] === 'Entregado')
                                                                bg-blue-50
                                                            @else
                                                                bg-slate-50
                                                            @endif"
                                                        value="{{ $item['fecha_entrega'] ? $item['fecha_entrega'] : '' }}"
                                                        data-row-hash="{{ $rowHashSimple }}"
                                                        data-numero-pedido="{{ $item['numero_pedido'] }}"
                                                        data-talla="{{ $item['talla'] }}"
                                                        data-prenda-id="{{ $item['prenda_id'] ?? '' }}"
                                                        data-pedido-produccion-id="{{ $item['pedido_produccion_id'] }}"
                                                        data-recibo-prenda-id="{{ $item['recibo_prenda_id'] }}"
                                                        @if($item['estado_bodega'] === 'Entregado' || ($esReadOnly ?? false))
                                                            disabled
                                                        @endif
                                                    >
                                                </td>
                                                
                                                <!-- ÁREA / ESTADO -->
                                                <td class="px-4 py-3" style="width: 18%;">
                                                    <div class="space-y-2">
                                                        <select
                                                            class="area-select w-full px-2 py-1 border border-slate-300 bg-white text-black text-xs font-semibold uppercase rounded"
                                                            data-row-hash="{{ $rowHashSimple }}"
                                                            data-numero-pedido="{{ $item['numero_pedido'] }}"
                                                            data-talla="{{ $item['talla'] }}"
                                                            data-prenda-id="{{ $item['prenda_id'] ?? '' }}"
                                                            data-pedido-produccion-id="{{ $item['pedido_produccion_id'] ?? '' }}"
                                                            data-recibo-prenda-id="{{ $item['recibo_prenda_id'] ?? '' }}"
                                                            data-original-area="{{ $item['area'] ?? '' }}"
                                                            @if($esReadOnly ?? false) disabled @endif
                                                        >
                                                            <option value="">ÁREA</option>
                                                            <option value="Costura" {{ ($item['area'] ?? null) === 'Costura' ? 'selected' : '' }}>COSTURA</option>
                                                            <option value="EPP" {{ ($item['area'] ?? null) === 'EPP' ? 'selected' : '' }}>EPP</option>
                                                        </select>

                                                        <select
                                                            class="estado-select w-full px-2 py-1 border border-slate-300 bg-white text-black text-xs font-semibold uppercase rounded"
                                                            data-row-hash="{{ $rowHashSimple }}"
                                                            data-numero-pedido="{{ $item['numero_pedido'] }}"
                                                            data-talla="{{ $item['talla'] }}"
                                                            data-pedido-produccion-id="{{ $item['pedido_produccion_id'] ?? '' }}"
                                                            data-recibo-prenda-id="{{ $item['recibo_prenda_id'] ?? '' }}"
                                                            data-prenda-nombre="{{ $item['descripcion']['nombre_prenda'] ?? $item['descripcion']['nombre'] ?? '' }}"
                                                            data-prenda-id="{{ $item['prenda_id'] ?? '' }}"
                                                            data-pedido-epp-id="{{ $item['pedido_epp_id'] ?? '' }}"
                                                            data-cantidad="{{ $item['cantidad_total'] }}"
                                                            data-original-estado="{{ $item['estado_bodega'] ?? '' }}"
                                                            @if($esReadOnly ?? false) disabled @endif
                                                        >
                                                            <option value="">ESTADO</option>
                                                            <option value="Pendiente" {{ ($item['estado_bodega'] ?? null) === 'Pendiente' ? 'selected' : '' }}>PENDIENTE</option>
                                                            <option value="Entregado" {{ ($item['estado_bodega'] ?? null) === 'Entregado' ? 'selected' : '' }}>ENTREGADO</option>
                                                            <option value="Homologar" {{ ($item['estado_bodega'] ?? null) === 'Homologar' ? 'selected' : '' }}>HOMOLOGAR</option>
                                                            @if(auth()->user()->hasRole(['Bodeguero', 'Admin', 'SuperAdmin']))
                                                            <option value="Anulado" {{ ($item['estado_bodega'] ?? null) === 'Anulado' ? 'selected' : '' }}>ANULADO</option>
                                                            @endif
                                                        </select>

                                                        @if(!($esReadOnly ?? false) && !auth()->user()->hasRole('supervisor_gerencia'))
                                                        <button
                                                            type="button"
                                                            onclick="guardarFilaCompleta(this, '{{ $item['numero_pedido'] }}', '{{ $item['talla'] }}', '', '{{ $item['prenda_id'] ?? '' }}')"
                                                            class="w-full px-2 py-1 bg-green-500 hover:bg-green-600 text-white text-xs font-bold uppercase rounded transition"
                                                        >
                                                            💾 Guardar
                                                        </button>
                                                        @elseif(auth()->user()->hasRole('supervisor_gerencia'))
                                                        <button
                                                            type="button"
                                                            disabled
                                                            class="w-full px-2 py-1 bg-gray-400 text-white text-xs font-bold uppercase rounded cursor-not-allowed opacity-60"
                                                            title="Solo usuarios autorizados pueden guardar cambios"
                                                        >
                                                            💾 Guardar
                                                        </button>
                                                        @else
                                                        <div class="w-full px-2 py-1 bg-slate-100 text-slate-500 text-xs font-medium text-center rounded">
                                                            Guardado deshabilitado
                                                        </div>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    @endif
                                @endforeach
                            @else
                                    <tr>
                                        <td colspan="9" class="px-6 py-12 text-center">
                                            <div class="flex flex-col items-center">
                                                <span class="material-symbols-rounded text-slate-300 text-5xl">inventory_2</span>
                                                <p class="text-slate-500 font-medium mt-3">No hay artículos</p>
                                                <p class="text-slate-400 text-sm mt-1">Este pedido no tiene artículos</p>
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
            <div class="flex items-center gap-3">
                <button onclick="imprimirModalFactura()" class="px-3 py-1.5 bg-white/10 hover:bg-white/20 text-white text-xs font-bold rounded transition">Imprimir</button>
                <button onclick="cerrarModalFactura()" class="text-white hover:text-slate-200 text-2xl leading-none">✕</button>
            </div>
        </div>
        <div id="facturaContenido" class="px-6 py-6 overflow-y-auto" style="max-height: calc(100vh - 200px)">
            <div class="flex justify-center items-center py-12">
                <span class="text-slate-500">⏳ Cargando factura...</span>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Notas -->
<div id="modalNotas" class="hidden fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-9998 overflow-auto" style="z-index: 100001;">
    <div class="bg-white rounded-lg shadow-2xl max-w-2xl w-full mx-4 my-8">
        <div class="bg-slate-900 px-6 py-4 border-b border-slate-200 flex justify-between items-center">
            <h2 class="text-lg font-semibold text-white">💬 Notas - Pedido <span id="modalNotasNumeroPedido">#</span></h2>
            <button onclick="cerrarModalNotas()" class="text-white hover:text-slate-200 text-2xl leading-none">✕</button>
        </div>
        <div class="px-6 py-6">
            <div id="notasHistorial" class="mb-6" style="max-height: 350px; overflow-y: auto;">
                <div class="flex justify-center items-center py-8">
                    <span class="text-slate-500">⏳ Cargando notas...</span>
                </div>
            </div>
            
            @if(!($esReadOnly ?? false))
            <div class="border-t border-slate-200 pt-6">
                <label class="block text-sm font-bold text-slate-900 mb-3">Agregar Nueva Nota:</label>
                <textarea
                    id="notasNuevaContent"
                    class="w-full px-4 py-3 border border-slate-300 rounded-lg text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-700 outline-none transition resize-none"
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
            @else
            <div class="border-t border-slate-200 pt-6 text-center">
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

<script>
// Variables globales para notas
window.usuarioActualId = {{ auth()->user()->id }};
window.__usuarioEsAdmin = {{ auth()->user()->hasRole('admin') ? 'true' : 'false' }};

/**
 * Logs de diagnóstico para el diseño de la tabla
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log('🔍 [DIAGNÓSTICO] Iniciando análisis de diseño...');
    
    // Verificar dimensiones del viewport
    const viewportHeight = window.innerHeight;
    const viewportWidth = window.innerWidth;
    console.log(`📏 [DIAGNÓSTICO] Viewport: ${viewportWidth}x${viewportHeight}px`);
    
    // Verificar contenedor principal
    const mainContainer = document.querySelector('.min-h-screen');
    if (mainContainer) {
        const mainRect = mainContainer.getBoundingClientRect();
        console.log(`📦 [DIAGNÓSTICO] Contenedor principal:`, {
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
        console.log(`🗂️ [DIAGNÓSTICO] Contenedor de tabla:`, {
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
        console.log(`📊 [DIAGNÓSTICO] Tabla:`, {
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
    
    // Verificar tbody
    const tbody = document.querySelector('tbody');
    if (tbody) {
        const tbodyRect = tbody.getBoundingClientRect();
        console.log(`📋 [DIAGNÓSTICO] Tbody:`, {
            width: tbodyRect.width,
            height: tbodyRect.height,
            scrollWidth: tbody.scrollWidth,
            scrollHeight: tbody.scrollHeight,
            children: tbody.children.length
        });
    }
    
    // Verificar si hay scroll
    setTimeout(() => {
        const tableContainer = document.querySelector('.overflow-x-auto');
        if (tableContainer) {
            console.log(`🔄 [DIAGNÓSTICO] Estado del scroll:`, {
                hasHorizontalScroll: tableContainer.scrollWidth > tableContainer.clientWidth,
                hasVerticalScroll: tableContainer.scrollHeight > tableContainer.clientHeight,
                scrollWidth: tableContainer.scrollWidth,
                clientWidth: tableContainer.clientWidth,
                scrollHeight: tableContainer.scrollHeight,
                clientHeight: tableContainer.clientHeight
            });
        }
    }, 1000);
    
    // Monitorear cambios de tamaño
    window.addEventListener('resize', () => {
        console.log(`📏 [DIAGNÓSTICO] Resize - Nuevo viewport: ${window.innerWidth}x${window.innerHeight}px`);
    });
});

/**
 * Abre modal mostrando detalles de la homologación
 */
function abrirModalHomologacionBodega(eppId) {
    fetch(`/gestion-bodega/epp/${eppId}/homologacion`)
        .then(response => {
            if (!response.ok) throw new Error('Error al obtener datos de homologación');
            return response.json();
        })
        .then(data => {
            if (!data.success || !data.data) {
                Swal.fire('Error', 'No se encontraron datos de homologación', 'error');
                return;
            }

            const { epp_anterior, epp_nuevo, cambios } = data.data;

            let htmlContenido = `
                <div class="space-y-6">
                    <!-- EPP Anterior -->
                    <div class="border border-red-300 bg-red-50 rounded-lg p-4">
                        <h3 class="font-bold text-red-900 mb-3 text-lg">❌ EPP Anterior (Eliminado)</h3>
                        <div class="grid grid-cols-2 gap-3 text-sm">
                            <div class="bg-white p-2 rounded border border-red-200">
                                <span class="text-slate-600">ID:</span>
                                <p class="font-bold text-red-900">${epp_anterior.id}</p>
                            </div>
                            <div class="bg-white p-2 rounded border border-red-200">
                                <span class="text-slate-600">Nombre:</span>
                                <p class="font-bold text-red-900">${epp_anterior.nombre_epp || epp_anterior.nombre || 'N/A'}</p>
                            </div>
                            <div class="bg-white p-2 rounded border border-red-200">
                                <span class="text-slate-600">Cantidad:</span>
                                <p class="font-bold text-red-900">${epp_anterior.cantidad}</p>
                            </div>
                            <div class="bg-white p-2 rounded border border-red-200">
                                <span class="text-slate-600">Eliminado:</span>
                                <p class="font-bold text-red-900">${new Date(epp_anterior.deleted_at).toLocaleString('es-ES')}</p>
                            </div>
                            ${epp_anterior.observaciones ? `
                            <div class="bg-white p-2 rounded border border-red-200 col-span-2">
                                <span class="text-slate-600">Observaciones:</span>
                                <p class="font-semibold text-red-900 text-xs mt-1">${epp_anterior.observaciones}</p>
                            </div>
                            ` : ''}
                        </div>
                    </div>

                    <!-- EPP Nuevo -->
                    <div class="border border-green-300 bg-green-50 rounded-lg p-4">
                        <h3 class="font-bold text-green-900 mb-3 text-lg">✅ EPP Nuevo (Actual)</h3>
                        <div class="grid grid-cols-2 gap-3 text-sm">
                            <div class="bg-white p-2 rounded border border-green-200">
                                <span class="text-slate-600">ID:</span>
                                <p class="font-bold text-green-900">${epp_nuevo.id}</p>
                            </div>
                            <div class="bg-white p-2 rounded border border-green-200">
                                <span class="text-slate-600">Nombre:</span>
                                <p class="font-bold text-green-900">${epp_nuevo.nombre_epp || epp_nuevo.nombre || 'N/A'}</p>
                            </div>
                            <div class="bg-white p-2 rounded border border-green-200">
                                <span class="text-slate-600">Cantidad:</span>
                                <p class="font-bold text-green-900">${epp_nuevo.cantidad}</p>
                            </div>
                            <div class="bg-white p-2 rounded border border-green-200">
                                <span class="text-slate-600">Creado:</span>
                                <p class="font-bold text-green-900">${new Date(epp_nuevo.created_at).toLocaleString('es-ES')}</p>
                            </div>
                            ${epp_nuevo.observaciones ? `
                            <div class="bg-white p-2 rounded border border-green-200 col-span-2">
                                <span class="text-slate-600">Observaciones:</span>
                                <p class="font-semibold text-green-900 text-xs mt-1">${epp_nuevo.observaciones}</p>
                            </div>
                            ` : ''}
                        </div>
                    </div>

                    <!-- Cambios Realizados -->
                    <div class="border border-blue-300 bg-blue-50 rounded-lg p-4">
                        <h3 class="font-bold text-blue-900 mb-3 text-lg">📊 Cambios Realizados</h3>
                        <div class="space-y-2 text-sm">
                            ${cambios.cantidad_cambio ? `
                            <div class="flex items-center gap-2 bg-white p-2 rounded border border-blue-200">
                                <span class="material-symbols-rounded text-orange-600 text-lg">change_circle</span>
                                <div>
                                    <span class="text-slate-600">Cantidad: </span>
                                    <span class="font-bold text-blue-900">${cambios.cantidad_anterior} → ${cambios.cantidad_nueva}</span>
                                </div>
                            </div>
                            ` : ''}
                            
                            ${cambios.epp_cambio ? `
                            <div class="flex items-center gap-2 bg-white p-2 rounded border border-blue-200">
                                <span class="material-symbols-rounded text-orange-600 text-lg">swap_horiz</span>
                                <div>
                                    <span class="text-slate-600">EPP Modificado</span>
                                    <p class="text-xs text-slate-500">Se cambió el EPP seleccionado</p>
                                </div>
                            </div>
                            ` : ''}
                            
                            ${cambios.observaciones_cambio ? `
                            <div class="flex items-center gap-2 bg-white p-2 rounded border border-blue-200">
                                <span class="material-symbols-rounded text-orange-600 text-lg">edit_note</span>
                                <div>
                                    <span class="text-slate-600">Observaciones Modificadas</span>
                                    <p class="text-xs text-slate-500">Se actualizaron las observaciones</p>
                                </div>
                            </div>
                            ` : ''}
                            
                            ${!cambios.cantidad_cambio && !cambios.epp_cambio && !cambios.observaciones_cambio ? `
                            <div class="text-center py-2 text-slate-500">
                                <span class="material-symbols-rounded text-gray-400 text-lg">check_circle</span>
                                <p class="text-xs mt-1">No se detectaron cambios significativos</p>
                            </div>
                            ` : ''}
                        </div>
                    </div>
                </div>
            `;

            Swal.fire({
                title: '📋 Detalles de Homologación',
                html: htmlContenido,
                icon: 'info',
                width: '700px',
                allowOutsideClick: false,
                allowEscapeKey: true,
                confirmButtonText: '✓ Entendido',
                confirmButtonColor: '#3b82f6',
                didOpen: () => {
                    const popup = Swal.getPopup();
                    if (popup) {
                        popup.style.maxHeight = '90vh';
                    }
                }
            });
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire('Error', 'Error al cargar datos de homologación: ' + error.message, 'error');
        });
}

/**
 * Alternar visibilidad de la fila de homologación
 * @param {HTMLElement} btn - El botón de alternancia
 * @param {Object} datosHomologacion - Datos del EPP homologado
 */
function toggleHomologacionRow(btn, datosHomologacion) {
    const tr = btn.closest('tr');
    
    // Verificar si la fila expandida ya existe
    let filaExpandida = tr.nextElementSibling;
    
    if (filaExpandida && filaExpandida.classList.contains('homologacion-expandida')) {
        // Ya está expandida, contraer
        filaExpandida.classList.add('hidden');
        btn.innerHTML = '🔽 Ver Homologación';
        btn.classList.remove('bg-purple-700');
        btn.classList.add('bg-purple-600');
        return;
    }
    
    if (!filaExpandida || !filaExpandida.classList.contains('homologacion-expandida')) {
        // No existe, crear la fila expandida
        const columnasCount = 9;
        
        let obsHtml = '';
        if (datosHomologacion.observaciones) {
            obsHtml = '<div class="mt-3 bg-purple-50 rounded p-2"><p class="text-xs text-purple-700 font-semibold mb-1">Observaciones</p><p class="text-sm text-purple-900">' + datosHomologacion.observaciones + '</p></div>';
        }
        
        const html = `<tr class="homologacion-expandida bg-gradient-to-r from-purple-50 to-transparent border-t-2 border-purple-300"><td colspan="${columnasCount}" class="px-6 py-4"><div class="bg-white rounded-lg border border-purple-200 shadow-sm p-4"><div class="flex items-start gap-4"><div class="flex-shrink-0"><span class="inline-flex items-center justify-center h-10 w-10 rounded-full bg-purple-100"><span class="text-purple-700 text-lg">✓</span></span></div><div class="flex-grow"><h4 class="text-sm font-bold text-purple-900 mb-3">EPP Nuevo (Reemplazo)</h4><div class="grid grid-cols-2 md:grid-cols-4 gap-3"><div class="bg-purple-50 rounded p-2"><p class="text-xs text-purple-700 font-semibold">Nombre</p><p class="text-sm text-purple-900 font-bold">${datosHomologacion.epp_nombre || 'N/A'}</p></div><div class="bg-purple-50 rounded p-2"><p class="text-xs text-purple-700 font-semibold">Cantidad</p><p class="text-sm text-purple-900 font-bold">${datosHomologacion.cantidad || 0}</p></div><div class="bg-purple-50 rounded p-2"><p class="text-xs text-purple-700 font-semibold">Fecha Homologación</p><p class="text-sm text-purple-900 font-bold">${datosHomologacion.fecha_homologacion ? new Date(datosHomologacion.fecha_homologacion).toLocaleDateString('es-ES') : 'N/A'}</p></div><div class="bg-purple-50 rounded p-2"><p class="text-xs text-purple-700 font-semibold">ID EPP</p><p class="text-sm text-purple-900 font-bold">#${datosHomologacion.epp_id || 'N/A'}</p></div></div>${obsHtml}</div></div></div></td></tr>`;
        
        const nuevaFila = document.createElement('tr');
        nuevaFila.innerHTML = html;
        nuevaFila.classList.add('homologacion-expandida');
        tr.parentNode.insertBefore(nuevaFila, tr.nextSibling);
    }
    
    btn.innerHTML = '🔼 Ocultar Homologación';
    btn.classList.add('bg-purple-700');
    btn.classList.remove('bg-purple-600');
}

// Limpiar filas expandidas cuando se cambia el estado
document.addEventListener('change', function(e) {
    if (e.target.classList.contains('estado-select')) {
        const tr = e.target.closest('tr');
        const siguienteTr = tr.nextElementSibling;
        
        if (siguienteTr && siguienteTr.classList.contains('homologacion-expandida')) {
            const btn = tr.querySelector('.toggle-homologacion-btn');
            if (btn) {
                btn.click(); // Contraer si estaba expandida
            }
        }
    }
}, true);
</script>

<!-- Modal de Confirmación (Eliminar Nota) -->
<div id="modalConfirmarEliminar" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-9999" style="z-index: 100002;">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
        <div class="bg-red-600 px-6 py-4 border-b border-red-200">
            <h3 class="text-lg font-semibold text-white flex items-center gap-2">
                <span class="material-symbols-rounded">warning</span>
                Confirmar Eliminación
            </h3>
        </div>
        <div class="px-6 py-4">
            <p class="text-gray-700">¿Estás seguro de que deseas eliminar esta nota? Esta acción no se puede deshacer.</p>
        </div>
        <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex gap-3 justify-end">
            <button type="button" onclick="cerrarModalConfirmarEliminar()" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 font-medium rounded-lg transition">
                Cancelar
            </button>
            <button type="button" id="btnConfirmarEliminar" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition">
                Eliminar Nota
            </button>
        </div>
    </div>
</div>

<!-- Modal de Alerta (Mensajes) -->
<div id="modalAlerta" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-9999" style="z-index: 100003;">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
        <div id="alertaHeader" class="px-6 py-4 border-b">
            <h3 id="alertaTitulo" class="text-lg font-semibold text-white flex items-center gap-2">
                <span id="alertaIcono" class="material-symbols-rounded">info</span>
                Mensaje
            </h3>
        </div>
        <div class="px-6 py-4">
            <p id="alertaMensaje" class="text-gray-700">Mensaje del sistema</p>
        </div>
        <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex justify-end">
            <button type="button" onclick="cerrarModalAlerta()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition">
                Entendido
            </button>
        </div>
    </div>
</div>

<script src="{{ asset('js/bodega-pedidos.js') }}?v={{ time() }}"></script>
@endsection
