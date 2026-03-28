@extends('layouts.despacho-standalone')

@section('title', "Despacho - Pedido {$pedido->numero_pedido}")

@push('scripts')
<script>
// Conexión WebSocket para actualizaciones en tiempo real
let socket = null;
let reconnectAttempts = 0;
const maxReconnectAttempts = 5;

function connectWebSocket() {
    try {
        // Usar WebSocket de Reverb con clave desde meta tags
        socket = new window.Echo({
            broadcaster: 'reverb',
            key: document.querySelector('meta[name="reverb-key"]')?.getAttribute('content') || 'mundo-industrial-key',
            wsHost: document.querySelector('meta[name="reverb-host"]')?.getAttribute('content') || window.location.hostname,
            wsPort: parseInt(document.querySelector('meta[name="reverb-port"]')?.getAttribute('content')) || 8080,
            wssPort: parseInt(document.querySelector('meta[name="reverb-port"]')?.getAttribute('content')) || 8080,
            forceTLS: document.querySelector('meta[name="reverb-scheme"]')?.getAttribute('content') === 'https',
            enabledTransports: ['ws', 'wss'],
        });

        // Unirse al canal público de despacho
        socket.channel('pedidos.general')
            .listen('.pedido.actualizado', (event) => {
                console.log('🔄 Pedido actualizado en tiempo real (despacho):', event);
                
                // Si el pedido cambió a "Entregado" y es el pedido actual, mostrar notificación
                if (event.nuevo_estado === 'Entregado' && event.pedido_id == window.pedidoId) {
                    console.log('📦 Pedido actual marcado como entregado');
                    
                    // Mostrar notificación local
                    mostrarNotificacionPedidoEntregadoLocal(event.numero_pedido);
                }
            })
            .error((error) => {
                console.error(' Error en WebSocket (despacho):', error);
            });

        console.log(' WebSocket conectado para pedido:', window.pedidoId);
    } catch (error) {
        console.error(' Error al conectar WebSocket:', error);
        if (reconnectAttempts < maxReconnectAttempts) {
            reconnectAttempts++;
            setTimeout(connectWebSocket, 2000 * reconnectAttempts);
        }
    }
}

// Función para mostrar notificación local
function mostrarNotificacionPedidoEntregadoLocal(numeroPedido) {
    // Crear notificación flotante
    const notificacion = document.createElement('div');
    notificacion.className = 'fixed top-4 right-4 bg-green-500 text-white px-4 py-2 rounded-lg shadow-lg z-50 transform translate-x-full transition-transform duration-300';
    notificacion.innerHTML = `
        <div class="flex items-center gap-2">
            <span class="material-symbols-rounded">check_circle</span>
            <span>Pedido #${numeroPedido} marcado como entregado</span>
        </div>
    `;
    
    document.body.appendChild(notificacion);
    
    // Mostrar notificación inmediatamente
    requestAnimationFrame(() => {
        notificacion.style.transform = 'translateX(0)';
    });
    
    // Ocultar después de 3 segundos
    setTimeout(() => {
        notificacion.style.transform = 'translateX(100%)';
        setTimeout(() => {
            if (document.body.contains(notificacion)) {
                document.body.removeChild(notificacion);
            }
        }, 300);
    }, 3000);
}

// Inicializar cuando se carga la página
document.addEventListener('DOMContentLoaded', function() {
    // Pasar datos del pedido a JavaScript
    window.pedidoId = {{ $pedido->id }};
    window.numeroPedido = '{{ $pedido->numero_pedido }}';
    
    // Usar el sistema waitForEcho para asegurar que Echo esté disponible
    window.waitForEcho(function() {
        console.log('🚀 Echo está listo, conectando WebSocket...');
        connectWebSocket();
    });
});
</script>
@endpush

@section('content')
<div class="min-h-screen bg-white">
    <div class="max-w-6xl mx-auto">
        <!-- Header minimalista -->
        <div class="border-b border-slate-200 px-6 py-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h1 class="text-2xl font-semibold text-slate-900">Despacho</h1>
                    <p class="text-sm text-slate-500 mt-1">Pedido {{ $pedido->numero_pedido }}</p>
                </div>
                <div class="flex gap-3">
                    <a href="{{ route('despacho.index') }}" 
                       class="px-3 py-2 text-slate-600 hover:text-slate-900 font-medium">
                        ← Volver
                    </a>
                    <button type="button"
                            onclick="abrirModalFactura({{ $pedido->id }})"
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded transition-colors">
                        Ver Pedido
                    </button>
                    <button type="button"
                            onclick="imprimirTablaVacia()"
                            class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white font-medium rounded transition-colors">
                        Imprimir
                    </button>
                </div>
            </div>
            
            <!-- Info compacta del pedido -->
            <div class="flex gap-6 text-sm">
                <div>
                    <span class="text-slate-500">Cliente:</span>
                    <span class="font-medium text-slate-900 ml-2">{{ $pedido->cliente ?? '—' }}</span>
                </div>
                <div>
                    <span class="text-slate-500">Asesor:</span>
                    <span class="font-medium text-slate-900 ml-2">{{ $pedido->asesor->name ?? 'Sin asignar' }}</span>
                </div>
                <div>
                    <span class="text-slate-500">Forma de Pago:</span>
                    <span class="font-medium text-slate-900 ml-2">{{ $pedido->forma_de_pago ?? 'No especificada' }}</span>
                </div>
            </div>
        </div>
        
        <!-- OBSERVACIONES DESTACADAS -->
        @if($pedido->observaciones)
            <div class="mx-6 mb-3">
                <div style="background: #fef3c7; border: 1px solid #fcd34d; padding: 12px; border-radius: 6px; margin-bottom: 12px; font-size: 15px;">
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                        <svg style="width: 18px; height: 18px; color: #000;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <strong style="color: #000;"> Observaciones:</strong>
                    </div>
                    <div style="white-space: pre-wrap; color: #000;">{{ $pedido->observaciones }}</div>
                </div>
            </div>
        @endif

        <!-- Formulario de despacho -->
        <form id="formDespacho" class="bg-white overflow-hidden">
            @csrf

            <!-- Tabla de despacho -->
            <div class="overflow-x-auto lg:overflow-visible">
                <table class="w-full text-sm min-w-[800px] lg:min-w-full border-collapse">
                    <thead class="bg-slate-50 border-b-2 border-slate-400 sticky top-0 z-50">
                        <tr>
                            <th class="px-2 lg:px-4 py-3 text-left font-medium text-slate-700 text-xs lg:text-sm border-r border-slate-400">Descripción</th>
                            <th class="px-2 lg:px-4 py-3 text-center font-medium text-slate-700 w-16 text-xs lg:text-sm border-r border-slate-400">Género</th>
                            <th class="px-2 lg:px-4 py-3 text-center font-medium text-slate-700 w-16 text-xs lg:text-sm border-r border-slate-400">Talla</th>
                            <th class="px-2 lg:px-4 py-3 text-center font-medium text-slate-700 w-16 text-xs lg:text-sm border-r border-slate-400">Cantidad</th>
                            <th class="px-2 lg:px-4 py-3 text-center font-medium text-slate-700 w-32 text-xs lg:text-sm border-r border-slate-400">Entregar</th>
                            <th class="px-2 lg:px-4 py-3 text-center font-medium text-slate-700 w-32 text-xs lg:text-sm">Fecha Entrega</th>
                        </tr>
                    </thead>
                    <tbody id="tablaDespacho">
                        <!-- PRENDAS -->
                        @if($prendas->count() > 0)
                            <tr class="bg-slate-100 border-b-2 border-slate-400">
                                <td colspan="6" class="px-4 py-2 font-semibold text-slate-900">
                                    Prendas
                                </td>
                            </tr>
                            @php
                                // Agrupar prendas por ID para merge de celdas
                                $prendasAgrupadas = $prendas->groupBy('id');
                            @endphp
                            @foreach($prendasAgrupadas as $prendaId => $filasGroup)
                                @php
                                    $primeraFila = $filasGroup->first();

                                    // Si la prenda tiene colores por talla, separar en sub-items por color Y género
                                    $gruposPorColor = [];
                                    if ($primeraFila->objetoPrenda && isset($primeraFila->objetoPrenda['variantes']) && is_array($primeraFila->objetoPrenda['variantes'])) {
                                        foreach ($primeraFila->objetoPrenda['variantes'] as $variante) {
                                            $generoVar = strtoupper($variante['genero'] ?? '');
                                            $tallaVar = $variante['talla'] ?? '';
                                            $tallaIdVar = $variante['talla_id'] ?? null;

                                            if ($generoVar === 'GENERICO') {
                                                continue;
                                            }

                                            if (isset($variante['colores_detalle']) && is_array($variante['colores_detalle']) && !empty($variante['colores_detalle'])) {
                                                foreach ($variante['colores_detalle'] as $colorDetalle) {
                                                    $rawColor = $colorDetalle['color'] ?? '';
                                                    $esColorValido = !empty($rawColor) && strtolower(trim($rawColor)) !== 'sin color';
                                                    $colorKey = $esColorValido ? strtoupper($rawColor) : '__SIN_COLOR__';
                                                    $cantidadColor = (int)($colorDetalle['cantidad'] ?? 0);
                                                    $tallaColorId = $colorDetalle['talla_color_id'] ?? null;

                                                    if (!empty($tallaVar) && $cantidadColor > 0) {
                                                        // Nivel 1: por color
                                                        if (!isset($gruposPorColor[$colorKey])) {
                                                            $gruposPorColor[$colorKey] = [];
                                                        }
                                                        // Nivel 2: por género dentro del color
                                                        if (!isset($gruposPorColor[$colorKey][$generoVar])) {
                                                            $gruposPorColor[$colorKey][$generoVar] = [];
                                                        }
                                                        // Nivel 3: tallas
                                                        $gruposPorColor[$colorKey][$generoVar][] = [
                                                            'talla' => $tallaVar,
                                                            'tallaId' => $tallaIdVar,
                                                            'tallaColorId' => $tallaColorId,
                                                            'genero' => $variante['genero'] ?? null,
                                                            'cantidad' => $cantidadColor,
                                                        ];
                                                    }
                                                }
                                            } else {
                                                // No tiene colores detallados, agregar directamente con cantidad de la variante
                                                $cantidadVar = (int)($variante['cantidad'] ?? 0);
                                                if (!empty($tallaVar) && !empty($generoVar) && $cantidadVar > 0) {
                                                    $colorKey = '__SIN_COLOR__';
                                                    if (!isset($gruposPorColor[$colorKey])) {
                                                        $gruposPorColor[$colorKey] = [];
                                                    }
                                                    if (!isset($gruposPorColor[$colorKey][$generoVar])) {
                                                        $gruposPorColor[$colorKey][$generoVar] = [];
                                                    }
                                                    $gruposPorColor[$colorKey][$generoVar][] = [
                                                        'talla' => $tallaVar,
                                                        'tallaId' => $tallaIdVar,
                                                        'tallaColorId' => null,
                                                        'genero' => $variante['genero'] ?? null,
                                                        'cantidad' => $cantidadVar,
                                                    ];
                                                }
                                            }
                                        }
                                    }

                                    // Ordenar tallas dentro de cada grupo género
                                    foreach ($gruposPorColor as &$generosPorColor) {
                                        foreach ($generosPorColor as &$tallasGenero) {
                                            usort($tallasGenero, function ($a, $b) {
                                                $nA = is_numeric($a['talla']) ? (int)$a['talla'] : null;
                                                $nB = is_numeric($b['talla']) ? (int)$b['talla'] : null;
                                                if ($nA !== null && $nB !== null) return $nA - $nB;
                                                return strcmp($a['talla'], $b['talla']);
                                            });
                                        }
                                        unset($tallasGenero);
                                    }
                                    unset($generosPorColor);

                                    $tieneColoresPorTalla = !empty($gruposPorColor);
                                    $rowSpan = $filasGroup->count();
                                @endphp
                                @if($tieneColoresPorTalla)
                                    @foreach($gruposPorColor as $colorKey => $generosPorColor)
                                        @php
                                            $colorLabel = $colorKey === '__SIN_COLOR__' ? null : $colorKey;
                                            // Total de filas para este color (descripción rowspan)
                                            $totalRowsColor = 0;
                                            foreach ($generosPorColor as $tallasGen) {
                                                $totalRowsColor += count($tallasGen);
                                            }
                                            $isFirstRowOfColor = true;
                                        @endphp
                                        @foreach($generosPorColor as $generoKey => $tallasDelGenero)
                                            @php
                                                $rowSpanGenero = count($tallasDelGenero);
                                            @endphp
                                            @foreach($tallasDelGenero as $indexTalla => $t)
                                            <tr class="border-b border-slate-400 hover:bg-slate-50"
                                                data-tipo="prenda"
                                                data-id="{{ $primeraFila->id }}"
                                                data-talla-id="{{ $t['tallaId'] }}"
                                                data-talla-color-id="{{ $t['tallaColorId'] }}"
                                                data-genero="{{ $t['genero'] }}"
                                                data-cantidad="{{ $t['cantidad'] }}">

                                                {{-- CELDA DE DESCRIPCIÓN: Solo en la primera fila del color --}}
                                                @if($isFirstRowOfColor)
                                                    <td class="px-2 lg:px-4 py-3 text-slate-900 text-xs" rowspan="{{ $totalRowsColor }}">
                                                        <div class="font-semibold text-slate-900 mb-1">
                                                            {{ $primeraFila->objetoPrenda['nombre'] ?? $primeraFila->descripcion }}
                                                            @if($colorLabel)
                                                                <span class="text-slate-900"> - <strong>{{ $colorLabel }}</strong></span>
                                                            @endif
                                                            @if(isset($primeraFila->objetoPrenda['de_bodega']) && $primeraFila->objetoPrenda['de_bodega'])
                                                                <span class="text-orange-600 font-bold"> - SE SACA DE BODEGA</span>
                                                            @endif
                                                        </div>

                                                        @if($primeraFila->objetoPrenda && isset($primeraFila->objetoPrenda['descripcion']) && $primeraFila->objetoPrenda['descripcion'])
                                                            <div class="text-slate-700 mb-1 text-xs">
                                                                {{ $primeraFila->objetoPrenda['descripcion'] }}
                                                            </div>
                                                        @endif
                                                
                                                <!-- Tela y Color -->
                                                @if($primeraFila->objetoPrenda && (isset($primeraFila->objetoPrenda['tela']) || isset($primeraFila->objetoPrenda['color'])))
                                                    @php
                                                        $tela = $primeraFila->objetoPrenda['tela'] ?? null;
                                                        $rawColorPrenda = $primeraFila->objetoPrenda['color'] ?? null;
                                                        $color = ($rawColorPrenda && !in_array(strtolower(trim($rawColorPrenda)), ['sin color', 'no color', ''])) ? $rawColorPrenda : null;
                                                    @endphp
                                                    @if($tela || $color)
                                                    <div class="text-slate-900 mb-1 text-xs">
                                                        @if($tela && $color)
                                                            <div>• Tela: {{ $tela }} - Color: {{ $color }}</div>
                                                        @elseif($tela)
                                                            <div>• Tela: {{ $tela }}</div>
                                                        @elseif($color)
                                                            <div>• Color: {{ $color }}</div>
                                                        @endif
                                                    </div>
                                                    @endif
                                                @endif
                                                
                                                @if($primeraFila->objetoPrenda && isset($primeraFila->objetoPrenda['variantes']) && is_array($primeraFila->objetoPrenda['variantes']) && count($primeraFila->objetoPrenda['variantes']) > 0)
                                                    @php
                                                        $primeraVariante = $primeraFila->objetoPrenda['variantes'][0];
                                                        $manga = $primeraVariante->manga ?? $primeraVariante['manga'] ?? null;
                                                        $manga_obs = $primeraVariante->manga_obs ?? $primeraVariante['manga_obs'] ?? '';
                                                        $broche = $primeraVariante->broche ?? $primeraVariante['broche'] ?? null;
                                                        $broche_obs = $primeraVariante->broche_obs ?? $primeraVariante['broche_obs'] ?? '';
                                                        $bolsillos = $primeraVariante->bolsillos ?? $primeraVariante['bolsillos'] ?? false;
                                                        $bolsillos_obs = $primeraVariante->bolsillos_obs ?? $primeraVariante['bolsillos_obs'] ?? '';
                                                    @endphp
                                                    <div class="text-slate-900 mb-1 text-xs space-y-0.5">
                                                        @if($manga)
                                                            <div>• Manga:{{ $manga }}{{ $manga_obs && trim($manga_obs) !== '' ? " ($manga_obs)" : '' }}</div>
                                                        @endif
                                                        @if($broche)
                                                            <div>• {{ $broche }}{{ $broche_obs && trim($broche_obs) !== '' ? " ($broche_obs)" : '' }}</div>
                                                        @endif
                                                        @if($bolsillos)
                                                            <div>• Bolsillos{{ $bolsillos_obs && trim($bolsillos_obs) !== '' ? " ($bolsillos_obs)" : '' }}</div>
                                                        @endif
                                                    </div>
                                                @endif
                                                @if($primeraFila->objetoPrenda && isset($primeraFila->objetoPrenda['procesos']) && is_array($primeraFila->objetoPrenda['procesos']) && count($primeraFila->objetoPrenda['procesos']) > 0)
                                                    <div class="text-slate-900 mt-1 text-xs">
                                                        <div class="ml-2 mt-0.5">
                                                            @foreach($primeraFila->objetoPrenda['procesos'] as $proc)
                                                                @php
                                                                    $ubicaciones = $proc->ubicaciones ?? $proc['ubicaciones'] ?? [];
                                                                    if (is_string($ubicaciones)) {
                                                                        $decoded = json_decode($ubicaciones, true);
                                                                        $ubicaciones = is_array($decoded) ? $decoded : [$ubicaciones];
                                                                    }
                                                                    $ubicacionesStr = is_array($ubicaciones) ? implode(', ', $ubicaciones) : $ubicaciones;
                                                                @endphp
                                                                <div>• {{ $proc->nombre ?? $proc->tipo_proceso ?? $proc['tipo_proceso'] ?? 'Proceso' }}{{ $ubicacionesStr && trim($ubicacionesStr) !== '' ? " ($ubicacionesStr)" : '' }}</div>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                @else
                                                    <div class="text-slate-400 text-xs mt-1">— Sin procesos</div>
                                                @endif

                                                    </td>
                                                    @php $isFirstRowOfColor = false; @endphp
                                                @endif

                                                {{-- CELDA DE GÉNERO: Solo en la primera fila de cada género --}}
                                                @if($indexTalla === 0)
                                                    <td class="px-2 lg:px-4 py-3 text-center text-slate-600 text-xs" rowspan="{{ $rowSpanGenero }}">
                                                        @if(strtoupper($generoKey) === 'GENERICO')
                                                            —
                                                        @else
                                                            {{ $generoKey }}
                                                        @endif
                                                    </td>
                                                @endif

                                                <td class="px-2 lg:px-4 py-3 text-center text-slate-600">
                                                    @if(($t['talla'] ?? null) === 'SIN_ESPECIFICAR')
                                                        —
                                                    @else
                                                        {{ $t['talla'] }}
                                                    @endif
                                                </td>

                                                <td class="px-2 lg:px-4 py-3 text-center font-medium text-slate-900">
                                                    {{ $t['cantidad'] }}
                                                </td>
                                        
                                        <td class="px-2 lg:px-4 py-3 text-center">
                                            @if(auth()->user()->hasRole('supervisor_gerencia'))
                                                <button type="button" 
                                                        class="px-3 py-1 bg-gray-400 text-white text-xs font-medium rounded cursor-not-allowed opacity-60"
                                                        disabled
                                                        title="Solo usuarios autorizados pueden marcar como entregado">
                                                    Entregar
                                                </button>
                                            @else
                                                <button type="button" 
                                                        class="px-3 py-1 bg-green-500 hover:bg-green-600 text-white text-xs font-medium rounded transition-colors"
                                                        onclick="marcarEntregado(this)">
                                                    Entregar
                                                </button>
                                            @endif
                                        </td>
                                        <td class="px-2 lg:px-4 py-3 text-center">
                                            <input type="date" 
                                                   class="px-2 py-1 text-xs border border-slate-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                   id="fecha-entrega-{{ $primeraFila->id }}{{ $t['tallaId'] ? '-' . $t['tallaId'] : '' }}{{ $t['tallaColorId'] ? '-' . $t['tallaColorId'] : '' }}"
                                                   value="{{ isset($despachos[$primeraFila->id . ($t['tallaId'] ? '-' . $t['tallaId'] : '') . ($t['tallaColorId'] ? '-' . $t['tallaColorId'] : '')]) ? $despachos[$primeraFila->id . ($t['tallaId'] ? '-' . $t['tallaId'] : '') . ($t['tallaColorId'] ? '-' . $t['tallaColorId'] : '')]->fecha_entrega->format('Y-m-d') : '' }}"
                                                   readonly>
                                        </td>
                                    </tr>
                                            @endforeach
                                        @endforeach
                                    @endforeach
                                @else
                                    @foreach($filasGroup as $indexFila => $fila)
                                        <tr class="border-b border-slate-400 hover:bg-slate-50" 
                                            data-tipo="prenda"
                                            data-id="{{ $fila->id }}"
                                            data-talla-id="{{ $fila->tallaId }}"
                                            data-genero="{{ $fila->genero }}"
                                            data-cantidad="{{ $fila->cantidadTotal }}">
                                            
                                            {{-- CELDA DE DESCRIPCIÓN: Solo en la primera fila del grupo --}}
                                            @if($indexFila === 0)
                                                <td class="px-2 lg:px-4 py-3 text-slate-900 text-xs" rowspan="{{ $rowSpan }}">
                                                    <div class="font-semibold text-slate-900 mb-1">
                                                        {{ $primeraFila->objetoPrenda['nombre'] ?? $primeraFila->descripcion }}
                                                        @if(isset($primeraFila->objetoPrenda['de_bodega']) && $primeraFila->objetoPrenda['de_bodega'])
                                                            <span class="text-orange-600 font-bold"> - SE SACA DE BODEGA</span>
                                                        @endif
                                                    </div>

                                                    @if($primeraFila->objetoPrenda && isset($primeraFila->objetoPrenda['descripcion']) && $primeraFila->objetoPrenda['descripcion'])
                                                        <div class="text-slate-700 mb-1 text-xs">
                                                            {{ $primeraFila->objetoPrenda['descripcion'] }}
                                                        </div>
                                                    @endif
                                                    
                                                    <!-- Tela y Color -->
                                                    @if($primeraFila->objetoPrenda && (isset($primeraFila->objetoPrenda['tela']) || isset($primeraFila->objetoPrenda['color'])))
                                                        @php
                                                            $tela = $primeraFila->objetoPrenda['tela'] ?? null;
                                                            $rawColorPrenda = $primeraFila->objetoPrenda['color'] ?? null;
                                                            $color = ($rawColorPrenda && !in_array(strtolower(trim($rawColorPrenda)), ['sin color', 'no color', ''])) ? $rawColorPrenda : null;
                                                        @endphp
                                                        @if($tela || $color)
                                                        <div class="text-slate-900 mb-1 text-xs">
                                                            @if($tela && $color)
                                                                <div>• Tela: {{ $tela }} - Color: {{ $color }}</div>
                                                            @elseif($tela)
                                                                <div>• Tela: {{ $tela }}</div>
                                                            @elseif($color)
                                                                <div>• Color: {{ $color }}</div>
                                                            @endif
                                                        </div>
                                                        @endif
                                                    @endif
                                                    
                                                    @if($primeraFila->objetoPrenda && isset($primeraFila->objetoPrenda['variantes']) && is_array($primeraFila->objetoPrenda['variantes']) && count($primeraFila->objetoPrenda['variantes']) > 0)
                                                        @php
                                                            // Obtener la primera variante para mostrar las características comunes
                                                            $primeraVariante = $primeraFila->objetoPrenda['variantes'][0];
                                                            $manga = $primeraVariante->manga ?? $primeraVariante['manga'] ?? null;
                                                            $manga_obs = $primeraVariante->manga_obs ?? $primeraVariante['manga_obs'] ?? '';
                                                            $broche = $primeraVariante->broche ?? $primeraVariante['broche'] ?? null;
                                                            $broche_obs = $primeraVariante->broche_obs ?? $primeraVariante['broche_obs'] ?? '';
                                                            $bolsillos = $primeraVariante->bolsillos ?? $primeraVariante['bolsillos'] ?? false;
                                                            $bolsillos_obs = $primeraVariante->bolsillos_obs ?? $primeraVariante['bolsillos_obs'] ?? '';
                                                        @endphp
                                                        <div class="text-slate-900 mb-1 text-xs space-y-0.5">
                                                            @if($manga)
                                                                <div>• Manga:{{ $manga }}{{ $manga_obs && trim($manga_obs) !== '' ? " ($manga_obs)" : '' }}</div>
                                                            @endif
                                                            @if($broche)
                                                                <div>• {{ $broche }}{{ $broche_obs && trim($broche_obs) !== '' ? " ($broche_obs)" : '' }}</div>
                                                            @endif
                                                            @if($bolsillos)
                                                                <div>• Bolsillos{{ $bolsillos_obs && trim($bolsillos_obs) !== '' ? " ($bolsillos_obs)" : '' }}</div>
                                                            @endif
                                                        </div>
                                                    @endif
                                                    @if($primeraFila->objetoPrenda && isset($primeraFila->objetoPrenda['procesos']) && is_array($primeraFila->objetoPrenda['procesos']) && count($primeraFila->objetoPrenda['procesos']) > 0)
                                                        <div class="text-slate-900 mt-1 text-xs">
                                                            <div class="ml-2 mt-0.5">
                                                                @foreach($primeraFila->objetoPrenda['procesos'] as $proc)
                                                                    @php
                                                                        $ubicaciones = $proc->ubicaciones ?? $proc['ubicaciones'] ?? [];
                                                                        // Si es string, intenta decodificar como JSON
                                                                        if (is_string($ubicaciones)) {
                                                                            $decoded = json_decode($ubicaciones, true);
                                                                            $ubicaciones = is_array($decoded) ? $decoded : [$ubicaciones];
                                                                        }
                                                                        $ubicacionesStr = is_array($ubicaciones) ? implode(', ', $ubicaciones) : $ubicaciones;
                                                                    @endphp
                                                                    <div>• {{ $proc->nombre ?? $proc->tipo_proceso ?? $proc['tipo_proceso'] ?? 'Proceso' }}{{ $ubicacionesStr && trim($ubicacionesStr) !== '' ? " ($ubicacionesStr)" : '' }}</div>
                                                                @endforeach
                                                            </div>
                                                        </div>
                                                    @else
                                                        <div class="text-slate-400 text-xs mt-1">— Sin procesos</div>
                                                    @endif
                                                </td>
                                                
                                                {{-- CELDA DE GÉNERO: Solo en la primera fila del grupo --}}
                                                <td class="px-2 lg:px-4 py-3 text-center text-slate-600 text-xs" rowspan="{{ $rowSpan }}">
                                                    @if($primeraFila && strtoupper($primeraFila->genero ?? '') === 'GENERICO')
                                                        —
                                                    @else
                                                        {{ $primeraFila->genero ?? '—' }}
                                                    @endif
                                                </td>
                                            @endif
                                            
                                            <td class="px-2 lg:px-4 py-3 text-center text-slate-600">
                                                @if(($fila->talla ?? null) === 'SIN_ESPECIFICAR')
                                                    —
                                                @else
                                                    {{ $fila->talla }}
                                                @endif
                                            </td>
                                            
                                            <td class="px-2 lg:px-4 py-3 text-center font-medium text-slate-900">
                                                {{ $fila->cantidadTotal }}
                                            </td>
                                            
                                            <td class="px-2 lg:px-4 py-3 text-center">
                                                @if(auth()->user()->hasRole('supervisor_gerencia'))
                                                    <button type="button" 
                                                            class="px-3 py-1 bg-gray-400 text-white text-xs font-medium rounded cursor-not-allowed opacity-60"
                                                            disabled
                                                            title="Solo usuarios autorizados pueden marcar como entregado">
                                                        Entregar
                                                    </button>
                                                @else
                                                    <button type="button" 
                                                            class="px-3 py-1 bg-green-500 hover:bg-green-600 text-white text-xs font-medium rounded transition-colors"
                                                            onclick="marcarEntregado(this)">
                                                        Entregar
                                                    </button>
                                                @endif
                                            </td>
                                            <td class="px-2 lg:px-4 py-3 text-center">
                                                <input type="date" 
                                                       class="px-2 py-1 text-xs border border-slate-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                       id="fecha-entrega-{{ $fila->id }}{{ $fila->tallaId ? '-' . $fila->tallaId : '' }}"
                                                       value="{{ isset($despachos[$fila->id . ($fila->tallaId ? '-' . $fila->tallaId : '')]) ? $despachos[$fila->id . ($fila->tallaId ? '-' . $fila->tallaId : '')]->fecha_entrega->format('Y-m-d') : '' }}"
                                                       readonly>
                                            </td>
                                        </tr>
                                    @endforeach
                                @endif
                            @endforeach
                        @endif

                        <!-- EPP -->
                        @if($epps->count() > 0)
                            <tr class="bg-slate-100 border-b-2 border-slate-400">
                                <td colspan="6" class="px-4 py-2 font-semibold text-slate-900">
                                    EPP
                                </td>
                            </tr>
                            @foreach($epps as $index => $fila)
                                <tr class="border-b border-slate-400 hover:bg-slate-50"
                                    data-tipo="epp"
                                    data-id="{{ $fila->id }}"
                                    data-cantidad="{{ $fila->cantidadTotal }}">
                                    
                                    <td class="px-2 lg:px-4 py-3 text-slate-900 text-xs">
                                        <div class="font-semibold text-slate-900"> {{ $fila->objetoEpp['nombre'] ?? $fila->objetoEpp['nombre_completo'] ?? $fila->descripcion }}</div>
                                        @if($fila->objetoEpp && isset($fila->objetoEpp['observaciones']) && $fila->objetoEpp['observaciones'] && $fila->objetoEpp['observaciones'] !== '—' && $fila->objetoEpp['observaciones'] !== '-')
                                            <div class="text-slate-600 mt-1 text-xs">{{ $fila->objetoEpp['observaciones'] }}</div>
                                        @endif
                                    </td>
                                    
                                    <td class="px-2 lg:px-4 py-3 text-center text-slate-600 text-xs">
                                        —
                                    </td>
                                    
                                    <td class="px-2 lg:px-4 py-3 text-center text-slate-600">
                                        —
                                    </td>
                                    
                                    <td class="px-2 lg:px-4 py-3 text-center font-medium text-slate-900">
                                        {{ $fila->cantidadTotal }}
                                    </td>
                                    
                                    <td class="px-2 lg:px-4 py-3 text-center">
                                        @if(auth()->user()->hasRole('supervisor_gerencia'))
                                            <button type="button" 
                                                    class="px-3 py-1 bg-gray-400 text-white text-xs font-medium rounded cursor-not-allowed opacity-60"
                                                    disabled
                                                    title="Solo usuarios autorizados pueden marcar como entregado">
                                                Entregado
                                            </button>
                                        @else
                                            <button type="button" 
                                                    class="px-3 py-1 bg-green-500 hover:bg-green-600 text-white text-xs font-medium rounded transition-colors"
                                                    onclick="marcarEntregado(this)">
                                                Entregado
                                            </button>
                                        @endif
                                    </td>
                                    <td class="px-2 lg:px-4 py-3 text-center">
                                        <input type="date" 
                                               class="px-2 py-1 text-xs border border-slate-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
                                               id="fecha-entrega-epp-{{ $fila->id }}"
                                               value="{{ isset($despachos['epp-' . $fila->id]) ? $despachos['epp-' . $fila->id]->fecha_entrega->format('Y-m-d') : '' }}"
                                               readonly>
                                    </td>
                                </tr>
                            @endforeach
                        @endif

                        @if($prendas->count() === 0 && $epps->count() === 0)
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-slate-500">
                                    No hay ítems en este pedido
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>

            <!-- Info de ítems -->
            <div class="px-6 py-4 bg-slate-50 border-t-2 border-slate-400">
                <div class="text-sm text-slate-600">
                    <span class="font-medium">{{ $prendas->count() + $epps->count() }}</span> ítems en total
                </div>
            </div>
        </form>
    </div>
</div>

<div class="max-w-6xl mx-auto px-6 pb-10">
    <div class="bg-white border border-slate-200 rounded-lg overflow-hidden">
        <div class="px-6 py-4 bg-slate-900 text-white font-semibold">
            Pendientes bodeguero
        </div>

        <div class="p-6">
            <div id="pendientesGrid" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div>
                    <div class="text-sm font-semibold text-slate-900 mb-3">Bodeguero</div>
                    <div id="pendientesBodegueroHistorial" class="space-y-3"></div>
                </div>

                <div id="pendientesAsesoraCol">
                    <div class="text-sm font-semibold text-slate-900 mb-3">Asesora</div>
                    <div id="pendientesAsesoraHistorial" class="space-y-3"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Factura -->
<div id="modalFactura" class="hidden fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-9999 overflow-auto" style="z-index: 9999;">
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
                <span class="text-slate-500"> Cargando factura...</span>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="bg-slate-50 px-6 py-4 border-t border-slate-200 flex justify-end gap-3 sticky bottom-0">
            <button onclick="cerrarModalFactura()" 
                    class="px-4 py-2 text-slate-700 hover:text-slate-900 font-medium border border-slate-300 hover:border-slate-400 rounded transition-colors">
                Cerrar
            </button>
        </div>
    </div>
</div>

<!-- Modal de Confirmación para Deshacer Entregado -->
<div id="modalDeshacerEntregado" class="hidden fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-9999" style="z-index: 9999;">
    <div class="bg-white rounded-lg shadow-2xl max-w-md w-full mx-4">
        <!-- Header -->
        <div class="bg-orange-500 px-6 py-4 border-b border-orange-600">
            <h2 class="text-lg font-semibold text-white flex items-center">
                <span class="mr-2">↶</span>
                Deshacer Entregado
            </h2>
        </div>
        
        <!-- Content -->
        <div class="p-6">
            <div class="flex items-center mb-4">
                <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center mr-4">
                    <span class="text-orange-500 text-xl">⚠️</span>
                </div>
                <div>
                    <h3 class="text-lg font-medium text-slate-900 mb-1">¿Deshacer marcado como entregado?</h3>
                    <p class="text-slate-600 text-sm">El ítem volverá a estado "Pendiente" y el pedido cambiará a "Pendiente" si todos los ítems están pendientes.</p>
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="bg-slate-50 px-6 py-4 border-t border-slate-200 flex justify-end gap-3">
            <button onclick="cerrarModalDeshacerEntregado()" 
                    class="px-4 py-2 text-slate-700 hover:text-slate-900 font-medium border border-slate-300 hover:border-slate-400 rounded transition-colors">
                Cancelar
            </button>
            <button id="btnConfirmarDeshacer" 
                    class="px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white font-medium rounded transition-colors">
                Sí, deshacer entregado
            </button>
        </div>
    </div>
</div>

<!-- JavaScript para despacho -->
<script>
console.log(' Script de despacho cargado correctamente');

async function cargarPendientesBodeguero() {
    const elBodega = document.getElementById('pendientesBodegueroHistorial');
    const elAsesora = document.getElementById('pendientesAsesoraHistorial');
    const elAsesoraCol = document.getElementById('pendientesAsesoraCol');
    const elGrid = document.getElementById('pendientesGrid');
    if (!elBodega || !elAsesora) return;

    elBodega.innerHTML = '<div class="text-center text-slate-500 py-4 text-sm">Cargando...</div>';
    elAsesora.innerHTML = '<div class="text-center text-slate-500 py-4 text-sm">Cargando...</div>';

    try {
        const r = await fetch('{{ route("despacho.observaciones.obtener", $pedido->id) }}', {
            headers: {
                'Accept': 'application/json',
            }
        });

        const data = await r.json().catch(() => null);
        if (!r.ok || !data || data.success === false) {
            const msg = data?.message || 'No se pudieron cargar las observaciones';
            elBodega.innerHTML = `<div class="text-red-600 text-sm">${msg}</div>`;
            elAsesora.innerHTML = `<div class="text-red-600 text-sm">${msg}</div>`;
            return;
        }

        const rows = Array.isArray(data.data) ? data.data : [];
        const bodeguero = rows.filter(x => x && x.source === 'bodega');
        const asesora = rows.filter(x => {
            if (!x || x.source !== 'despacho') return false;
            const rol = String(x.usuario_rol || '').toLowerCase();
            return rol.includes('asesor');
        });

        if (elAsesoraCol && elGrid) {
            if (asesora.length === 0) {
                elAsesoraCol.style.display = 'none';
                elGrid.style.gridTemplateColumns = '1fr';
            } else {
                elAsesoraCol.style.display = '';
                elGrid.style.gridTemplateColumns = '';
            }
        }

        const render = (items, target) => {
            if (!items.length) {
                target.innerHTML = '<div class="text-slate-500 text-sm">— Sin observaciones</div>';
                return;
            }

            target.innerHTML = items.map(item => {
                const source = (item.source || 'despacho').toString();
                const contenido = (item.contenido || '').toString();
                const usuario = (item.usuario_nombre || '—').toString();
                const rol = (item.usuario_rol || '').toString();
                const fechaISO = item.updated_at || item.created_at || '';
                let fechaTexto = '';
                try {
                    if (fechaISO) {
                        fechaTexto = new Date(fechaISO).toLocaleString('es-CO');
                    }
                } catch (e) {
                    fechaTexto = '';
                }

                const esBodega = source === 'bodega';

                return `
                    <div class="border border-slate-200 rounded-lg p-3 bg-slate-50">
                        <div class="text-xs text-slate-600">
                            ${esBodega ? '' : `<span class="font-medium text-slate-900">${usuario}</span>`}
                            ${!esBodega && rol ? `<span class="ml-2 px-2 py-0.5 rounded bg-slate-200 text-slate-700">${rol}</span>` : ''}
                            ${fechaTexto ? `<span class="ml-2 text-slate-500">${fechaTexto}</span>` : ''}
                        </div>
                        <div class="mt-2 text-sm text-slate-800 whitespace-pre-wrap">${contenido.replace(/</g,'&lt;').replace(/>/g,'&gt;')}</div>
                    </div>
                `;
            }).join('');
        };

        render(bodeguero, elBodega);
        if (asesora.length > 0) {
            render(asesora, elAsesora);
        }
    } catch (e) {
        console.error('Error cargando pendientes bodeguero:', e);
        elBodega.innerHTML = '<div class="text-red-600 text-sm">Error cargando observaciones</div>';
        if (elAsesoraCol) elAsesoraCol.style.display = '';
        if (elGrid) elGrid.style.gridTemplateColumns = '';
        elAsesora.innerHTML = '<div class="text-red-600 text-sm">Error cargando observaciones</div>';
    }
}

/**
 * Marcar ítem como entregado
 */
async function marcarEntregado(button) {
    const fila = button.closest('tr');
    const tipo = fila.dataset.tipo;
    const itemId = parseInt(fila.dataset.id);
    const tallaId = fila.dataset.tallaId ? parseInt(fila.dataset.tallaId) : null;
    const tallaColorId = fila.dataset.tallaColorId ? parseInt(fila.dataset.tallaColorId) : null;
    const genero = fila.dataset.genero || null;
    
    // Mostrar la URL para debugging
    const url = '{{ route("despacho.marcar-entregado", $pedido->id) }}';
    console.log(' URL de marcarEntregado:', url);
    
    // Deshabilitar el botón mientras se procesa
    button.disabled = true;
    button.textContent = ' Guardando...';
    
    try {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({
                tipo_item: tipo,
                item_id: itemId,
                talla_id: tallaId,
                talla_color_id: tallaColorId,
                genero: genero,
            }),
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Establecer la fecha actual en el campo correspondiente
            const fechaActual = new Date().toISOString().split('T')[0]; // Fallback
            const fechaEntrega = data.fecha_entrega || fechaActual;
            const clave = tipo === 'epp' ? `epp-${itemId}` : `${itemId}${tallaId ? '-' + tallaId : ''}${tallaColorId ? '-' + tallaColorId : ''}`;
            const fechaCampo = document.getElementById(`fecha-entrega-${clave}`);
            if (fechaCampo) {
                fechaCampo.value = fechaEntrega;
                fechaCampo.readOnly = false; // Permitir edición si se necesita
            }
            
            // Cambiar el botón a estado "Entregado" con opción de deshacer
            button.innerHTML = '✓ Entregado <span class="ml-1 text-xs">(↶)</span>';
            button.classList.remove('bg-green-500', 'hover:bg-green-600');
            button.classList.add('bg-orange-500', 'hover:bg-orange-600');
            button.onclick = function() { deshacerEntregado(this); };
            
            // Importante: habilitar el botón para permitir deshacer
            button.disabled = false;
            
            // Agregar efecto visual a la fila: color azul pastel
            fila.style.backgroundColor = '#DBEAFE'; // bg-blue-100 (azul pastel)
            
            console.log(' Ítem marcado como entregado:', data);
        } else {
            // Error: restaurar botón
            button.textContent = 'Entregar';
            button.disabled = false;
            alert('Error al marcar como entregado: ' + data.message);
        }
    } catch (error) {
        console.error('Error al marcar como entregado:', error);
        button.textContent = 'Entregar';
        button.disabled = false;
        alert('Error al marcar como entregado. Por favor, intenta de nuevo.');
    }
}

/**
 * Deshacer marcado como entregado
 */
async function deshacerEntregado(button) {
    const fila = button.closest('tr');
    const tipo = fila.dataset.tipo;
    const itemId = parseInt(fila.dataset.id);
    const tallaId = fila.dataset.tallaId ? parseInt(fila.dataset.tallaId) : null;
    const tallaColorId = fila.dataset.tallaColorId ? parseInt(fila.dataset.tallaColorId) : null;
    
    // Guardar referencia al botón y datos para usarlos después
    window.deshacerEntregadoData = {
        button: button,
        fila: fila,
        tipo: tipo,
        itemId: itemId,
        tallaId: tallaId,
        tallaColorId: tallaColorId
    };
    
    // Mostrar modal de confirmación
    abrirModalDeshacerEntregado();
}

/**
 * Abrir modal de confirmación para deshacer entregado
 */
function abrirModalDeshacerEntregado() {
    const modal = document.getElementById('modalDeshacerEntregado');
    modal.classList.remove('hidden');
    
    // Configurar el botón de confirmación
    const btnConfirmar = document.getElementById('btnConfirmarDeshacer');
    btnConfirmar.onclick = confirmarDeshacerEntregado;
}

/**
 * Cerrar modal de confirmación para deshacer entregado
 */
function cerrarModalDeshacerEntregado() {
    const modal = document.getElementById('modalDeshacerEntregado');
    modal.classList.add('hidden');
    
    // Limpiar datos guardados
    window.deshacerEntregadoData = null;
}

/**
 * Confirmar y ejecutar el deshacer entregado
 */
async function confirmarDeshacerEntregado() {
    if (!window.deshacerEntregadoData) return;
    
    const { button, fila, tipo, itemId, tallaId, tallaColorId } = window.deshacerEntregadoData;
    
    // Cerrar modal
    cerrarModalDeshacerEntregado();
    
    // Deshabilitar el botón mientras se procesa
    button.disabled = true;
    button.innerHTML = ' Deshaciendo...';
    
    try {
        const response = await fetch('{{ route("despacho.deshacer-entregado", $pedido->id) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({
                tipo_item: tipo,
                item_id: itemId,
                talla_id: tallaId,
                talla_color_id: tallaColorId,
            }),
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Limpiar el campo de fecha correspondiente
            const clave = tipo === 'epp' ? `epp-${itemId}` : `${itemId}${tallaId ? '-' + tallaId : ''}${tallaColorId ? '-' + tallaColorId : ''}`;
            const fechaCampo = document.getElementById(`fecha-entrega-${clave}`);
            if (fechaCampo) {
                fechaCampo.value = '';
                fechaCampo.readOnly = true; // Volver a readonly
            }
            
            // Restaurar el botón a estado inicial
            button.innerHTML = 'Entregar';
            button.classList.remove('bg-orange-500', 'hover:bg-orange-600');
            button.classList.add('bg-green-500', 'hover:bg-green-600');
            button.onclick = function() { marcarEntregado(this); };
            
            // Quitar efecto visual de la fila
            fila.style.backgroundColor = '';
            
            console.log(' Marcado como entregado deshecho:', data);
        } else {
            // Error: restaurar botón a estado entregado
            button.innerHTML = '✓ Entregado <span class="ml-1 text-xs">(↶)</span>';
            button.classList.remove('bg-green-500', 'hover:bg-green-600');
            button.classList.add('bg-orange-500', 'hover:bg-orange-600');
            button.onclick = function() { deshacerEntregado(this); };
            
            // Mostrar error con una alerta simple (podemos mejorarlo después)
            alert('Error al deshacer marcado como entregado: ' + data.message);
        }
    } catch (error) {
        console.error('Error al deshacer marcado como entregado:', error);
        
        // Restaurar botón a estado entregado
        button.innerHTML = '✓ Entregado <span class="ml-1 text-xs">(↶)</span>';
        button.classList.remove('bg-green-500', 'hover:bg-green-600');
        button.classList.add('bg-orange-500', 'hover:bg-orange-600');
        button.onclick = function() { deshacerEntregado(this); };
        
        alert('Error al deshacer marcado como entregado. Por favor, intenta de nuevo.');
    } finally {
        button.disabled = false;
    }
}

/**
 * Cargar estado inicial de entregas al cargar la página
 */
async function cargarEstadoEntregas() {
    try {
        const response = await fetch('{{ route("despacho.estado-entregas", $pedido->id) }}');
        const data = await response.json();
        
        if (data.success && data.entregas) {
            data.entregas.forEach(entrega => {
                // Buscar la fila correspondiente
                let selector = `tr[data-tipo="${entrega.tipo_item}"][data-id="${entrega.item_id}"]`;
                if (entrega.talla_id) {
                    selector += `[data-talla-id="${entrega.talla_id}"]`;
                }
                if (entrega.talla_color_id) {
                    selector += `[data-talla-color-id="${entrega.talla_color_id}"]`;
                }
                
                const fila = document.querySelector(selector);
                if (fila) {
                    const button = fila.querySelector('button');
                    if (button) {
                        // Marcar como entregado visualmente con opción de deshacer
                        button.innerHTML = '✓ Entregado <span class="ml-1 text-xs">(↶)</span>';
                        button.classList.remove('bg-green-500', 'hover:bg-green-600');
                        button.classList.add('bg-orange-500', 'hover:bg-orange-600');
                        button.onclick = function() { deshacerEntregado(this); };
                        fila.style.backgroundColor = '#DBEAFE'; // bg-blue-100 (azul pastel)
                    }

                    // Establecer fecha desde BD en el input correspondiente
                    const clave = entrega.tipo_item === 'epp'
                        ? `epp-${entrega.item_id}`
                        : `${entrega.item_id}${entrega.talla_id ? '-' + entrega.talla_id : ''}${entrega.talla_color_id ? '-' + entrega.talla_color_id : ''}`;
                    const fechaCampo = document.getElementById(`fecha-entrega-${clave}`);
                    if (fechaCampo && entrega.fecha_entrega) {
                        fechaCampo.value = entrega.fecha_entrega;
                    }
                }
            });
            
            console.log(' Estado de entregas cargado:', data.entregas.length, 'ítems entregados');
        }
    } catch (error) {
        console.error('Error al cargar estado de entregas:', error);
    }
}

// Cargar estado de entregas al cargar la página
document.addEventListener('DOMContentLoaded', function () {
    cargarEstadoEntregas();
    cargarPendientesBodeguero();
});

// ============ FUNCIONES GLOBALES PARA MODAL ============

/**
 * Abrir modal con la factura del pedido
 */
async function abrirModalFactura(pedidoId) {
    const modal = document.getElementById('modalFactura');
    const contenido = document.getElementById('facturaContenido');
    
    modal.classList.remove('hidden');
    contenido.innerHTML = '<div class="flex justify-center items-center py-12"><span class="text-slate-500"> Cargando factura...</span></div>';
    
    try {
        const response = await fetch(`/despacho/${pedidoId}/factura-datos`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
            },
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        let data;
        try {
            data = await response.json();
            console.log('[DESPACHO][factura-datos] respuesta:', data);
        } catch (parseError) {
            console.error('[DESPACHO][factura-datos] error parseando JSON:', parseError);
            contenido.innerHTML = '<div class="text-center text-red-600 py-6"> Error: respuesta inválida del servidor (no JSON)</div>';
            return;
        }

        if (data) {
            // DEBUG: Ver qué datos estamos recibiendo
            console.log(' [DESPACHO] Datos recibidos del backend:', data);
            console.log(' [DESPACHO] Estructura:', Object.keys(data));
            
            // Extraer datos como lo hace bodega
            const payload = (data && typeof data === 'object' && data.data) ? data.data : data;
            console.log(' [DESPACHO] Payload final:', payload);
            
            // Generar HTML de la factura
            const htmlFactura = generarHTMLFactura(payload);
            contenido.innerHTML = htmlFactura;
        } else {
            contenido.innerHTML = '<div class="text-center text-red-600 py-6"> Error al cargar la factura</div>';
        }
    } catch (error) {
        console.error('Error cargando factura:', error);
        contenido.innerHTML = '<div class="text-center text-red-600 py-6"> Error: ' + error.message + '</div>';
    }
}

/**
 * Cerrar modal de factura
 */
function cerrarModalFactura() {
    const modal = document.getElementById('modalFactura');
    modal.classList.add('hidden');
}

/**
 * Generar HTML de la factura - VERSIÓN BODEGA
 */
function generarHTMLFactura(datos) {
    // DEBUG: Ver estructura de datos
    console.log(' [DESPACHO-FACTURA] Estructura completa:', datos);
    console.log(' [DESPACHO-FACTURA] Prendas:', datos.prendas);
    if (datos.prendas && datos.prendas[0]) {
        console.log(' [DESPACHO-FACTURA] Primera prenda claves:', Object.keys(datos.prendas[0]));
        console.log(' [DESPACHO-FACTURA] Tallas:', datos.prendas[0].tallas);
        console.log(' [DESPACHO-FACTURA] Descripción:', datos.prendas[0].descripcion);
        console.log(' [DESPACHO-FACTURA] Variantes:', datos.prendas[0].variantes);
        console.log(' [DESPACHO-FACTURA] Variantes[0]:', datos.prendas[0].variantes?.[0]);
        console.log(' [DESPACHO-FACTURA] Variantes length:', datos.prendas[0].variantes?.length);
    }
    
    if (!datos || !datos.prendas || !Array.isArray(datos.prendas)) {
        return '<div style="color: #dc2626; padding: 1rem; border: 1px solid #fca5a5; border-radius: 6px; background: #fee2e2;"> Error: No se pudieron cargar las prendas del pedido.</div>';
    }

    // Generar las tarjetas de prendas
    const prendasHTML = datos.prendas.map((prenda, idx) => {
        // Usar TALLAS primero que es donde están los datos correctos
        let variantesHTML = '';
        if (prenda.tallas && typeof prenda.tallas === 'object' && Object.keys(prenda.tallas).length > 0) {
            // 🔴 Detectar si SOLO hay GENERICO (SOLO CANTIDAD)
            const generosEnTallas = Object.keys(prenda.tallas);
            const tieneGenerico = generosEnTallas.some(g => g && String(g).toUpperCase().trim() === 'GENERICO');
            const soloGenerico = tieneGenerico && generosEnTallas.length === 1;
            
            if (soloGenerico) {
                // Extraer cantidad de GENERICO
                let cantidad = 0;
                const genericoObj = prenda.tallas.GENERICO;
                if (genericoObj && typeof genericoObj === 'object') {
                    const valores = Object.values(genericoObj);
                    if (valores.length > 0) {
                        const primerValor = valores[0];
                        if (typeof primerValor === 'number') {
                            cantidad = primerValor;
                        } else if (Array.isArray(primerValor) && primerValor.length > 0 && primerValor[0].cantidad) {
                            cantidad = primerValor[0].cantidad;
                        }
                    }
                }
                
                variantesHTML = `
                    <div style="font-weight: 700; color: #2c3e50; margin-bottom: 6px; font-size: 11px;">Cantidad</div>
                    <div style="font-weight: 600; color: #0369a1; font-size: 12px; background: #f0f9ff; padding: 4px 8px; border-radius: 3px; border-left: 3px solid #0ea5e9; display: inline-block;">
                        ${cantidad}
                    </div>
                `;
            } else {
            // Convertir objeto de géneros a array de tallas con género y colores
            let todasLasTallas = [];
            Object.keys(prenda.tallas).forEach(genero => {
                // 🔴 Excluir GENERICO completamente
                if (genero && String(genero).toUpperCase().trim() === 'GENERICO') {
                    return; // Saltar GENERICO
                }
                if (typeof prenda.tallas[genero] === 'object') {
                    Object.entries(prenda.tallas[genero]).forEach(([talla, cantidad]) => {
                        // Extraer cantidad si viene como array de objetos
                        let cantidadReal = cantidad;
                        if (Array.isArray(cantidad) && cantidad.length > 0 && cantidad[0].cantidad !== undefined) {
                            cantidadReal = cantidad[0].cantidad;
                        }
                        todasLasTallas.push({ 
                            genero: genero.toUpperCase(), 
                            talla, 
                            cantidad: cantidadReal,
                            colores: [] // Se llenará con los colores de prenda_pedido_talla_colores
                        });
                    });
                }
            });
            
            // Buscar colores por talla desde prenda_pedido_talla_colores
            if (prenda.talla_colores && Array.isArray(prenda.talla_colores)) {
                todasLasTallas.forEach(tallaItem => {
                    const coloresEnTalla = prenda.talla_colores.filter(tc => 
                        tc.genero && tc.genero.toLowerCase() === tallaItem.genero.toLowerCase() && 
                        tc.talla === tallaItem.talla
                    );
                    
                    if (coloresEnTalla.length > 0) {
                        tallaItem.colores = coloresEnTalla.map(c => ({
                            color: c.color_nombre || c.color || 'Sin color',
                            cantidad: c.cantidad || 1
                        }));
                    }
                });
            }
            
            if (todasLasTallas.length > 0) {
                // Verificar si hay colores para decidir qué tabla mostrar
                const tieneColores = todasLasTallas.some(t => t.colores && t.colores.length > 0);
                
                if (tieneColores) {
                    // Tabla con colores
                    variantesHTML = `
                        <table style="width: 100%; font-size: 13px; border-collapse: collapse;">
                            <thead>
                                <tr style="background: #f9fafb; border-bottom: 1px solid #e5e7eb;">
                                    <th style="padding: 6px 8px; text-align: left; font-weight: 600; color: #374151;">Género</th>
                                    <th style="padding: 6px 8px; text-align: left; font-weight: 600; color: #374151;">Talla</th>
                                    <th style="padding: 6px 8px; text-align: center; font-weight: 600; color: #374151;">Cantidad</th>
                                    <th style="padding: 6px 8px; text-align: left; font-weight: 600; color: #374151;">Color</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${todasLasTallas.map((talla_item, varIdx) => `
                                    <tr style="background: ${varIdx % 2 === 0 ? '#ffffff' : '#f9fafb'}; border-bottom: 1px solid #f3f4f6;">
                                        <td style="padding: 6px 8px; font-weight: 600; color: #374151;">${talla_item.genero || 'N/A'}</td>
                                        <td style="padding: 6px 8px; font-weight: 600; color: #374151;">${talla_item.talla || 'N/A'}</td>
                                        <td style="padding: 6px 8px; text-align: center; color: #6b7280;">${talla_item.cantidad || 0}</td>
                                        <td style="padding: 6px 8px; color: #374151;">
                                            ${talla_item.colores && talla_item.colores.length > 0 
                                                ? talla_item.colores.map(c => `${c.color}(${c.cantidad})`).join(', ')
                                                : 'Sin color'
                                            }
                                        </td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    `;
                } else {
                    // Tabla normal sin colores
                    variantesHTML = `
                        <table style="width: 100%; font-size: 13px; border-collapse: collapse;">
                            <thead>
                                <tr style="background: #f9fafb; border-bottom: 1px solid #e5e7eb;">
                                    <th style="padding: 6px 8px; text-align: left; font-weight: 600; color: #374151;">Género</th>
                                    <th style="padding: 6px 8px; text-align: left; font-weight: 600; color: #374151;">Talla</th>
                                    <th style="padding: 6px 8px; text-align: center; font-weight: 600; color: #374151;">Cantidad</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${todasLasTallas.map((talla_item, varIdx) => `
                                    <tr style="background: ${varIdx % 2 === 0 ? '#ffffff' : '#f9fafb'}; border-bottom: 1px solid #f3f4f6;">
                                        <td style="padding: 6px 8px; font-weight: 600; color: #374151;">${talla_item.genero || 'N/A'}</td>
                                        <td style="padding: 6px 8px; font-weight: 600; color: #374151;">${talla_item.talla || 'N/A'}</td>
                                        <td style="padding: 6px 8px; text-align: center; color: #6b7280;">${talla_item.cantidad || 0}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    `;
                }
            }
            } // cierre del else de soloGenerico
        } else if (prenda.variantes && Array.isArray(prenda.variantes) && prenda.variantes.length > 0) {
            // 🔴 Filtrar GENERICO de variantes
            const variantesFiltradas = prenda.variantes.filter(v => 
                !(v.genero && String(v.genero).toUpperCase().trim() === 'GENERICO')
            );
            
            if (variantesFiltradas.length > 0) {
            variantesHTML = `
                <table style="width: 100%; font-size: 13px; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f9fafb; border-bottom: 1px solid #e5e7eb;">
                            <th style="padding: 6px 8px; text-align: left; font-weight: 600; color: #374151;">Género</th>
                            <th style="padding: 6px 8px; text-align: left; font-weight: 600; color: #374151;">Talla</th>
                            <th style="padding: 6px 8px; text-align: center; font-weight: 600; color: #374151;">Cantidad</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${variantesFiltradas.map((var_item, varIdx) => `
                            <tr style="background: ${varIdx % 2 === 0 ? '#ffffff' : '#f9fafb'}; border-bottom: 1px solid #f3f4f6;">
                                <td style="padding: 6px 8px; font-weight: 600; color: #374151;">${(var_item.genero || 'N/A').toUpperCase()}</td>
                                <td style="padding: 6px 8px; font-weight: 600; color: #374151;">${var_item.talla || 'N/A'}</td>
                                <td style="padding: 6px 8px; text-align: center; color: #6b7280;">${var_item.cantidad || 0}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            `;
            } else {
                // Todas eran GENERICO - mostrar badge de cantidad
                const genVar = prenda.variantes.find(v => v.genero && String(v.genero).toUpperCase().trim() === 'GENERICO');
                variantesHTML = `
                    <div style="font-weight: 700; color: #2c3e50; margin-bottom: 6px; font-size: 11px;">Cantidad</div>
                    <div style="font-weight: 600; color: #0369a1; font-size: 12px; background: #f0f9ff; padding: 4px 8px; border-radius: 3px; border-left: 3px solid #0ea5e9; display: inline-block;">
                        ${genVar ? genVar.cantidad : 0}
                    </div>
                `;
            }
        }

        // Tela y color
        let telaHTML = '';
        if (prenda.telas_array && Array.isArray(prenda.telas_array) && prenda.telas_array.length > 0) {
            telaHTML = `
                <div style="margin-bottom: 12px;">
                    ${prenda.telas_array.map(tela => `
                        <div style="padding: 6px 0; border-bottom: 1px solid #f3f4f6;">
                            <span style="font-size: 13px; color: #374151;">
                                <strong>Tela:</strong> ${tela.tela_nombre || '—'} 
                                <strong style="margin-left: 12px;">Color:</strong> ${tela.color_nombre || '—'}
                                ${tela.referencia ? `<strong style="margin-left: 12px;">Ref:</strong> ${tela.referencia}` : ''}
                            </span>
                        </div>
                    `).join('')}
                </div>
            `;
        } else if (prenda.tela || prenda.color) {
            telaHTML = `
                <div style="margin-bottom: 12px; font-size: 13px; color: #374151;">
                    <strong>Tela:</strong> ${prenda.tela || '—'} 
                    ${prenda.color ? `<strong style="margin-left: 12px;">Color:</strong> ${prenda.color}` : ''}
                </div>
            `;
        }

        // Procesos
        let procesosHTML = '';
        if (prenda.procesos && Array.isArray(prenda.procesos) && prenda.procesos.length > 0) {
            procesosHTML = '<div style="margin-bottom: 0;">';
            
            prenda.procesos.forEach(proc => {
                // Obtener tallas del proceso para comparar con las de la prenda
                let tallasProceso = [];
                if (proc.tallas && (proc.tallas.dama && Object.keys(proc.tallas.dama).length > 0 || proc.tallas.caballero && Object.keys(proc.tallas.caballero).length > 0 || proc.tallas.unisex && Object.keys(proc.tallas.unisex).length > 0 || proc.tallas.sobremedida && Object.keys(proc.tallas.sobremedida).length > 0)) {
                    tallasProceso = [
                        ...(proc.tallas.dama && Object.keys(proc.tallas.dama).length > 0 ? [`Dama: ${Object.entries(proc.tallas.dama).map(([talla, cantidad]) => `${talla}(${cantidad})`).join(', ')}`] : []),
                        ...(proc.tallas.caballero && Object.keys(proc.tallas.caballero).length > 0 ? [`Caballero: ${Object.entries(proc.tallas.caballero).map(([talla, cantidad]) => `${talla}(${cantidad})`).join(', ')}`] : []),
                        ...(proc.tallas.unisex && Object.keys(proc.tallas.unisex).length > 0 ? [`Unisex: ${Object.entries(proc.tallas.unisex).map(([talla, cantidad]) => `${talla}(${cantidad})`).join(', ')}`] : []),
                        ...(proc.tallas.sobremedida && Object.keys(proc.tallas.sobremedida).length > 0 ? [`Sobremedida: ${Object.entries(proc.tallas.sobremedida).map(([genero, cantidad]) => `${genero}(${cantidad})`).join(', ')}`] : [])
                    ];
                }
                
                // Obtener tallas de la prenda para comparar
                let tallasPrenda = [];
                if (prenda.tallas && typeof prenda.tallas === 'object') {
                    Object.keys(prenda.tallas).forEach(genero => {
                        if (typeof prenda.tallas[genero] === 'object') {
                            const tallasGenero = Object.entries(prenda.tallas[genero]).map(([talla, cantidad]) => `${talla}(${cantidad})`);
                            if (tallasGenero.length > 0) {
                                tallasPrenda.push(`${genero.toUpperCase()}: ${tallasGenero.join(', ')}`);
                            }
                        }
                    });
                }
                
                // Estandarizar formato: usar coma como separador para ambos
                const tallasProcesoStr = tallasProceso.join(', ').toUpperCase();
                const tallasPrendaStr = tallasPrenda.join(', ').toUpperCase();
                const mostrarTallas = tallasProcesoStr !== tallasPrendaStr;
                
                // Log temporal para depurar
                console.log('COMPARACIÓN FINAL:');
                console.log('Proceso:', tallasProcesoStr);
                console.log('Prenda:', tallasPrendaStr);
                console.log('¿Mostrar?:', mostrarTallas);
                console.log('Son iguales?:', tallasProcesoStr === tallasPrendaStr);
                
                procesosHTML += `
                    <div style="padding: 8px 0; border-bottom: 1px solid #f3f4f6;">
                        <div style="font-weight: 600; color: #374151; margin-bottom: 4px; font-size: 13px;">${proc.nombre || proc.tipo}</div>
                        ${proc.ubicaciones && proc.ubicaciones.length > 0 ? `
                            <div style="font-size: 13px; color: #6b7280; margin-bottom: 2px;">
                                 ${Array.isArray(proc.ubicaciones) ? proc.ubicaciones.join(' • ') : proc.ubicaciones}
                            </div>
                        ` : ''}
                        ${mostrarTallas && tallasProceso.length > 0 ? `
                            <div style="font-size: 13px; color: #6b7280; margin-bottom: 2px;">
                                ${tallasProceso.join(', ')}
                            </div>
                        ` : ''}
                        ${proc.observaciones ? `
                            <div style="font-size: 13px; color: #6b7280;">
                                ${proc.observaciones}
                            </div>
                        ` : ''}
                    </div>
                `;
            });
            
            procesosHTML += '</div>';
        }

        return `
            <div style="background: white; border: 1px solid #e5e7eb; border-radius: 6px; margin-bottom: 16px; padding: 16px;">
                <!-- Header simple -->
                <div style="border-bottom: 1px solid #e5e7eb; padding-bottom: 8px; margin-bottom: 12px;">
                    <div style="font-size: 16px; font-weight: 600; color: #374151;">PRENDA ${idx + 1}: ${prenda.nombre_prenda || prenda.nombre}${prenda.de_bodega ? ' <span style="color: #ea580c; font-weight: bold;">- SE SACA DE BODEGA</span>' : ''}</div>
                    ${prenda.descripcion ? `<div style="font-size: 14px; color: #6b7280; margin-top: 2px;">${prenda.descripcion}</div>` : ''}
                </div>
                
                <!-- Telas (movido aquí) -->
                ${telaHTML}
                
                <!-- Imagen pequeña -->
                ${(prenda.imagenes && prenda.imagenes.length > 0) ? `
                    <div style="float: right; margin-left: 12px; margin-bottom: 8px;">
                        <img src="${prenda.imagenes[0].ruta || prenda.imagenes[0].url || prenda.imagenes[0]}" style="width: 80px; height: 80px; object-fit: cover; border-radius: 4px; border: 1px solid #e5e7eb;">
                    </div>
                ` : ''}
                
                <!-- Contenido compacto -->
                <div style="${(prenda.imagenes && prenda.imagenes.length > 0) ? 'margin-right: 100px;' : ''}">
                    <!-- Variantes -->
                    ${variantesHTML ? variantesHTML.replace(/margin: 12px 0/g, 'margin-bottom: 12px;').replace(/border: 1px solid #e0e7ff/g, 'border: 1px solid #e5e7eb;').replace(/background: #f0f9ff/g, 'background: #f9fafb;').replace(/color: #1e40af/g, 'color: #374151;') : ''}
                    
                    <!-- Procesos -->
                    ${procesosHTML ? procesosHTML.replace(/margin: 12px 0/g, 'margin-bottom: 0;').replace(/border: 1px solid #e0e7ff/g, 'border: 1px solid #e5e7eb;') : ''}
                </div>
                
                <div style="clear: both;"></div>
            </div>
        `;
    }).join('');

    // EPPs
    const eppsHTML = (datos.epps && datos.epps.length > 0) ? `
        <div style="margin: 12px 0; padding: 0; background: #ffffff; border-radius: 6px; border: 1px solid #e0e7ff; overflow: hidden;">
            <div style="font-size: 12px !important; font-weight: 700; color: #1e40af; background: #f0f9ff; margin: 0; padding: 12px 12px; border-bottom: 2px solid #bfdbfe;"> EPP (${datos.epps.length})</div>
            <div style="padding: 12px; space-y: 8px;">
                ${datos.epps.map(epp => {
                    // Debug logging para EPPs en despacho
                    console.log('🖼️ [DESPACHO-FACTURA] EPP con imágenes:', {
                        nombre: epp.nombre_completo || epp.nombre,
                        cantidad: epp.cantidad,
                        imagenes_existe: !!epp.imagenes,
                        imagenes_es_array: Array.isArray(epp.imagenes),
                        imagenes_length: epp.imagenes ? epp.imagenes.length : 0,
                        imagenes: epp.imagenes
                    });
                    
                    // Generar HTML para imágenes si existen
                    const imagenesHTML = (epp.imagenes && Array.isArray(epp.imagenes) && epp.imagenes.length > 0) ? `
                        <div style="margin-top: 8px; padding-top: 8px; border-top: 1px solid #e5e7eb;">
                            <div style="color: #6b7280; font-size: 11px; text-transform: uppercase; margin-bottom: 4px; font-weight: 600;">🖼️ Imágenes (${epp.imagenes.length})</div>
                            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(60px, 1fr)); gap: 6px;">
                                ${epp.imagenes.map((imagen, index) => {
                                    let imgUrl = '';
                                    if (typeof imagen === 'string') {
                                        imgUrl = imagen;
                                    } else if (imagen.ruta_web) {
                                        imgUrl = imagen.ruta_web.startsWith('/') ? imagen.ruta_web : `/storage/${imagen.ruta_web}`;
                                    } else if (imagen.url) {
                                        imgUrl = imagen.url.startsWith('/') ? imagen.url : `/storage/${imagen.url}`;
                                    }
                                    
                                    return imgUrl ? `
                                        <div style="position: relative; border-radius: 3px; overflow: hidden; background: #f9fafb; border: 1px solid #e5e7eb; aspect-ratio: 1; cursor: pointer;" 
                                             title="Click para ver imagen completa"
                                             onclick="window.abrirModalImagen('${imgUrl}', '${(epp.nombre_completo || epp.nombre || 'Imagen EPP').replace(/'/g, "\\'")}')">
                                            <img src="${imgUrl}" 
                                                 alt="Imagen EPP" 
                                                 style="width: 100%; height: 100%; object-fit: cover; display: block;"
                                                 onerror="this.style.display='none'; this.parentElement.innerHTML='⚠️';">
                                        </div>
                                    ` : '';
                                }).join('')}
                            </div>
                        </div>
                    ` : '';
                    
                    return `
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; padding: 8px; margin-bottom: 8px; border-left: 3px solid #3b82f6; border-radius: 2px; background: #f8fafc;">
                            <div style="flex: 1;">
                                <div style="font-weight: 700; color: #1e40af; margin-bottom: 4px;">${epp.nombre_completo || epp.nombre}</div>
                                ${epp.observaciones && epp.observaciones !== '—' && epp.observaciones !== '-' ? `<div style="font-size: 11px; color: #475569;">${epp.observaciones}</div>` : ''}
                                ${imagenesHTML}
                            </div>
                            <div style="font-weight: 600; color: #1e40af; font-size: 14px; margin-left: 12px; margin-top: 4px;">
                                ${epp.cantidad}
                            </div>
                        </div>
                    `;
                }).join('')}
            </div>
        </div>
    ` : '';

    // Totales
    const totalHTML = `
        <div style="margin: 12px 0; padding: 12px; background: #f3f4f6; border-radius: 6px; border: 2px solid #d1d5db; text-align: right;">
            <div style="font-size: 12px; margin-bottom: 8px;">
                <strong>Total Ítems:</strong> ${datos.total_items || 0}
            </div>
            ${datos.valor_total ? `
                <div style="font-size: 12px; margin-bottom: 8px;">
                    <strong>Subtotal:</strong> $${parseFloat(datos.valor_total).toLocaleString('es-CO')}
                </div>
            ` : ''}
            ${datos.total_general ? `
                <div style="font-size: 14px; font-weight: 700; color: #1e40af; padding-top: 8px; border-top: 2px solid #d1d5db;">
                    <strong>Total:</strong> $${parseFloat(datos.total_general).toLocaleString('es-CO')}
                </div>
            ` : ''}
        </div>
    `;

    return `
        <div>
            <!-- Header factura -->
            <div style="background: #1e3a8a; color: white; padding: 16px; border-radius: 6px; margin-bottom: 12px; text-align: center;">
                <div style="font-size: 18px; font-weight: 700; margin-bottom: 8px;">FACTURA DE PEDIDO</div>
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; font-size: 12px; margin-top: 12px;">
                    <div>
                        <div style="font-size: 10px; opacity: 0.8;">Número</div>
                        <div style="font-weight: 600;">${datos.numero_pedido}</div>
                    </div>
                    <div>
                        <div style="font-size: 10px; opacity: 0.8;">Cliente</div>
                        <div style="font-weight: 600;">${datos.cliente}</div>
                    </div>
                    <div>
                        <div style="font-size: 10px; opacity: 0.8;">Asesora</div>
                        <div style="font-weight: 600;">${datos.asesora}</div>
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; font-size: 12px; margin-top: 8px;">
                    <div>
                        <div style="font-size: 10px; opacity: 0.8;">Forma de Pago</div>
                        <div style="font-weight: 600;">${datos.forma_de_pago}</div>
                    </div>
                    <div>
                        <div style="font-size: 10px; opacity: 0.8;">Fecha</div>
                        <div style="font-weight: 600;">${datos.fecha}</div>
                    </div>
                </div>
            </div>

            ${datos.observaciones ? `
                <div style="background: #fef3c7; border: 1px solid #fcd34d; padding: 12px; border-radius: 6px; margin-bottom: 12px; font-size: 13px;">
                    <strong style="color: #92400e;"> Observaciones:</strong>
                    <div style="margin-top: 4px; white-space: pre-wrap; color: #666;">${datos.observaciones}</div>
                </div>
            ` : ''}

            <!-- Prendas -->
            ${prendasHTML}

            <!-- EPPs -->
            ${eppsHTML}

            <!-- Totales -->
            ${totalHTML}
        </div>
    `;
}

/**
 * Imprimir tabla de despacho con 11 columnas originales
 */
function imprimirTablaVacia() {
    const pendientesBodegueroText = @json($pendientesBodegueroText ?? '— Sin observaciones');
    const observacionesAsesoraText = @json($observacionesAsesoraText ?? '— Sin observaciones');
    const mostrarAsesora = String(observacionesAsesoraText ?? '').trim() !== '' && String(observacionesAsesoraText ?? '') !== '— Sin observaciones';

    // Construir tabla HTML con 11 columnas para impresión
    let tablaHTML = `
        <table style="width: 100%; border-collapse: collapse; border: 2px solid #000;">
            <thead style="background: #f1f5f9; border-bottom: 2px solid #000;">
                <tr>
                    <th style="padding: 8px 4px; text-align: left; font-weight: 600; font-size: 11px; border: 1px solid #000;">Descripción</th>
                    <th style="padding: 8px 4px; text-align: center; font-weight: 600; font-size: 11px; border: 1px solid #000; width: 60px;">Género</th>
                    <th style="padding: 8px 4px; text-align: center; font-weight: 600; font-size: 11px; border: 1px solid #000; width: 50px;">Talla</th>
                    <th style="padding: 8px 4px; text-align: center; font-weight: 600; font-size: 11px; border: 1px solid #000; width: 60px;">Cantidad</th>
                    <th style="padding: 8px 4px; text-align: center; font-weight: 600; font-size: 11px; border: 1px solid #000; width: 60px;">Pendiente</th>
                    <th style="padding: 8px 4px; text-align: center; font-weight: 600; font-size: 11px; border: 1px solid #000; width: 70px;">Parcial 1</th>
                    <th style="padding: 8px 4px; text-align: center; font-weight: 600; font-size: 11px; border: 1px solid #000; width: 60px;">Pendiente</th>
                    <th style="padding: 8px 4px; text-align: center; font-weight: 600; font-size: 11px; border: 1px solid #000; width: 70px;">Parcial 2</th>
                    <th style="padding: 8px 4px; text-align: center; font-weight: 600; font-size: 11px; border: 1px solid #000; width: 60px;">Pendiente</th>
                    <th style="padding: 8px 4px; text-align: center; font-weight: 600; font-size: 11px; border: 1px solid #000; width: 70px;">Parcial 3</th>
                    <th style="padding: 8px 4px; text-align: center; font-weight: 600; font-size: 11px; border: 1px solid #000; width: 60px;">Pendiente</th>
                </tr>
            </thead>
            <tbody>
    `;

    // Obtener todas las filas de la tabla actual
    const filas = document.querySelectorAll('#tablaDespacho tr[data-tipo]');
    let ultimoTipo = '';
    let ultimoId = null;
    let grupoFilas = [];

    filas.forEach((fila, index) => {
        const tipo = fila.dataset.tipo;
        const id = fila.dataset.id;
        
        // Agregar encabezado de sección si cambia el tipo
        if (tipo !== ultimoTipo) {
            const nombreSeccion = tipo === 'prenda' ? 'Prendas' : 'EPP';
            tablaHTML += `
                <tr style="background: #f1f5f9;">
                    <td colspan="11" style="padding: 8px 4px; font-weight: 600; font-size: 11px; border: 1px solid #000;">${nombreSeccion}</td>
                </tr>
            `;
            ultimoTipo = tipo;
        }

        // Detectar si esta fila tiene la celda de descripción (primera fila de un grupo)
        const tds = fila.querySelectorAll('td');
        const tieneDescripcion = tds.length >= 5; // Si tiene 5 columnas, tiene descripción y género

        if (tieneDescripcion) {
            // Es la primera fila de un grupo, obtener descripción y género
            const cloneDesc = tds[0].cloneNode(true);
            cloneDesc.querySelectorAll('button').forEach(btn => btn.remove());
            const descripcion = cloneDesc.innerHTML;

            const cloneGenero = tds[1].cloneNode(true);
            cloneGenero.querySelectorAll('button').forEach(btn => btn.remove());
            const genero = cloneGenero.textContent.trim() || '—';

            const cloneTalla = tds[2].cloneNode(true);
            cloneTalla.querySelectorAll('button').forEach(btn => btn.remove());
            const talla = cloneTalla.textContent.trim() || '—';

            const cloneCantidad = tds[3].cloneNode(true);
            cloneCantidad.querySelectorAll('button').forEach(btn => btn.remove());
            const cantidad = cloneCantidad.textContent.trim() || '0';

            // Contar cuántas filas más pertenecen a ESTE subgrupo.
            // IMPORTANTE: cuando hay prendas por color, el mismo item_id se repite en subgrupos,
            // y cada subgrupo inicia con una fila que trae la descripción (rowspan por color).
            // Por eso el rowspan debe cortar cuando aparezca otra fila con descripción.
            let rowspan = 1;
            for (let i = index + 1; i < filas.length; i++) {
                if (filas[i].dataset.id !== id || filas[i].dataset.tipo !== tipo) {
                    break;
                }

                const tdsNext = filas[i].querySelectorAll('td');
                const nextTieneDescripcion = tdsNext.length >= 5;
                if (nextTieneDescripcion) {
                    break;
                }

                rowspan++;
            }

            // Primera fila con descripción y género
            tablaHTML += `
                <tr style="border-bottom: 1px solid #000;">
                    <td style="padding: 8px 4px; font-size: 10px; border: 1px solid #000;" rowspan="${rowspan}">${descripcion}</td>
                    <td style="padding: 8px 4px; text-align: center; font-size: 10px; border: 1px solid #000;" rowspan="${rowspan}">${genero}</td>
                    <td style="padding: 8px 4px; text-align: center; font-size: 10px; border: 1px solid #000;">${talla}</td>
                    <td style="padding: 8px 4px; text-align: center; font-weight: 600; font-size: 10px; border: 1px solid #000;">${cantidad}</td>
                    <td style="padding: 4px; border: 1px solid #000;"><input type="text" style="width: 100%; border: none; text-align: center; font-size: 10px; padding: 2px;"></td>
                    <td style="padding: 4px; border: 1px solid #000;"><input type="text" style="width: 100%; border: none; text-align: center; font-size: 10px; padding: 2px;"></td>
                    <td style="padding: 4px; border: 1px solid #000;"><input type="text" style="width: 100%; border: none; text-align: center; font-size: 10px; padding: 2px;"></td>
                    <td style="padding: 4px; border: 1px solid #000;"><input type="text" style="width: 100%; border: none; text-align: center; font-size: 10px; padding: 2px;"></td>
                    <td style="padding: 4px; border: 1px solid #000;"><input type="text" style="width: 100%; border: none; text-align: center; font-size: 10px; padding: 2px;"></td>
                    <td style="padding: 4px; border: 1px solid #000;"><input type="text" style="width: 100%; border: none; text-align: center; font-size: 10px; padding: 2px;"></td>
                    <td style="padding: 4px; border: 1px solid #000;"><input type="text" style="width: 100%; border: none; text-align: center; font-size: 10px; padding: 2px;"></td>
                </tr>
            `;
        } else {
            // Fila adicional sin descripción ni género (solo talla y cantidad)
            const cloneTalla = tds[0].cloneNode(true);
            cloneTalla.querySelectorAll('button').forEach(btn => btn.remove());
            const talla = cloneTalla.textContent.trim() || '—';

            const cloneCantidad = tds[1].cloneNode(true);
            cloneCantidad.querySelectorAll('button').forEach(btn => btn.remove());
            const cantidad = cloneCantidad.textContent.trim() || '0';

            tablaHTML += `
                <tr style="border-bottom: 1px solid #000;">
                    <td style="padding: 8px 4px; text-align: center; font-size: 10px; border: 1px solid #000;">${talla}</td>
                    <td style="padding: 8px 4px; text-align: center; font-weight: 600; font-size: 10px; border: 1px solid #000;">${cantidad}</td>
                    <td style="padding: 4px; border: 1px solid #000;"><input type="text" style="width: 100%; border: none; text-align: center; font-size: 10px; padding: 2px;"></td>
                    <td style="padding: 4px; border: 1px solid #000;"><input type="text" style="width: 100%; border: none; text-align: center; font-size: 10px; padding: 2px;"></td>
                    <td style="padding: 4px; border: 1px solid #000;"><input type="text" style="width: 100%; border: none; text-align: center; font-size: 10px; padding: 2px;"></td>
                    <td style="padding: 4px; border: 1px solid #000;"><input type="text" style="width: 100%; border: none; text-align: center; font-size: 10px; padding: 2px;"></td>
                    <td style="padding: 4px; border: 1px solid #000;"><input type="text" style="width: 100%; border: none; text-align: center; font-size: 10px; padding: 2px;"></td>
                    <td style="padding: 4px; border: 1px solid #000;"><input type="text" style="width: 100%; border: none; text-align: center; font-size: 10px; padding: 2px;"></td>
                    <td style="padding: 4px; border: 1px solid #000;"><input type="text" style="width: 100%; border: none; text-align: center; font-size: 10px; padding: 2px;"></td>
                </tr>
            `;
        }
    });

    tablaHTML += `
            </tbody>
        </table>
    `;

    const pendientesHTML = `
        <div style="margin-top: 10px; font-size: 12px; color: #000;">
            <strong>Pendientes bodeguero:</strong>
            <div style="margin-top: 6px; white-space: pre-wrap; font-size: 11px; color: #000;">${String(pendientesBodegueroText ?? '— Sin observaciones').replace(/</g,'&lt;').replace(/>/g,'&gt;')}</div>
        </div>
        ${mostrarAsesora ? `
            <div style="margin-top: 10px; font-size: 12px; color: #000;">
                <strong>Observaciones asesora:</strong>
                <div style="margin-top: 6px; white-space: pre-wrap; font-size: 11px; color: #000;">${String(observacionesAsesoraText).replace(/</g,'&lt;').replace(/>/g,'&gt;')}</div>
            </div>
        ` : ''}
    `;

    // Crear ventana de impresión
    const ventana = window.open('', '', 'width=1200,height=800');
    
    const htmlContent = `<!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Despacho - Imprimir</title>
            <style>
                @page { 
                    margin: 5mm; 
                    size: letter portrait; 
                }
                * { 
                    margin: 0; 
                    padding: 0; 
                    box-sizing: border-box; 
                }
                body { 
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; 
                    font-size: 9px; 
                    background: white; 
                    padding: 0;
                    margin: 0;
                }
                h2 { 
                    text-align: center; 
                    margin-bottom: 5px; 
                    font-size: 14px;
                    page-break-after: avoid;
                }
                p { 
                    text-align: center; 
                    margin-bottom: 8px; 
                    font-size: 10px;
                    page-break-after: avoid;
                }
                table { 
                    width: 100%; 
                    border-collapse: collapse;
                    page-break-before: avoid;
                }
                thead {
                    page-break-after: avoid;
                }
                tr { 
                    page-break-inside: avoid; 
                }
                @media print { 
                    body { 
                        margin: 0; 
                        padding: 0; 
                    }
                    h2, p {
                        page-break-after: avoid;
                    }
                    table { 
                        page-break-before: avoid;
                        page-break-inside: auto;
                    }
                }
            </style>
        </head>
        <body>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; padding: 20px; border: 3px solid #000; border-radius: 10px; background: #f9fafb;">
                <div style="text-align: left;">
                    <h2 style="margin: 0 0 12px 0; font-size: 20px; font-weight: bold; color: #000;">Despacho - Pedido {{ $pedido->numero_pedido }}</h2>
                    <p style="margin: 6px 0; font-size: 14px; color: #000;"><strong>Cliente:</strong> {{ $pedido->cliente ?? '—' }}</p>
                    <p style="margin: 6px 0; font-size: 13px; color: #333;"><strong>Fecha de creación:</strong> {{ $pedido->created_at ? $pedido->created_at->format('d/m/Y H:i') : '—' }}</p>
                </div>
                <div style="text-align: center; flex: 1; margin: 0 20px;">
                    <h3 style="margin: 0 0 8px 0; font-size: 16px; font-weight: bold; color: #000;">Observaciones</h3>
                    <div style="text-align: center; font-size: 12px; color: #000; line-height: 1.6; white-space: pre-wrap;">{{ $pedido->observaciones ?? 'Sin observaciones' }}</div>
                </div>
                <div style="text-align: right;">
                    <p style="margin: 0; font-size: 11px; color: #666;"><strong>Fecha de impresión:</strong></p>
                    <p style="margin: 0; font-size: 10px; color: #666;">` + new Date().toLocaleString('es-CO', { 
                        year: 'numeric', 
                        month: '2-digit', 
                        day: '2-digit',
                        hour: '2-digit',
                        minute: '2-digit',
                        hour12: true
                    }) + `</p>
                </div>
            </div>` + tablaHTML + pendientesHTML + `
            <script>
                window.print();
                window.onafterprint = function() { window.close(); };
            <\/script>
        </body>
        </html>`;
    
    ventana.document.write(htmlContent);
    ventana.document.close();
}

</script>

@endsection

