@extends('layouts.app-without-sidebar')

@section('title', "Gestión de Despacho - Pedido {$pedido['numero_pedido']}")

@section('content')
<div class="min-h-screen bg-slate-50 w-full flex flex-col">
    <div class="w-full flex-shrink-0">
        <!-- Header -->
        <div class="bg-white border-b border-slate-200 px-4 py-4 sm:px-6 sm:py-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl sm:text-2xl font-semibold text-black">Gestión de Despacho</h1>
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
                            Mostrando solo artículos con estado Pendiente para Despacho
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <a href="{{ route('despacho.pendientes') }}" 
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
                                <th class="px-4 py-3 text-center text-[11px] font-semibold text-black uppercase tracking-widest" style="width: 18%;">Estado</th>
                            </tr>
                        </thead>
                        
                        <tbody id="pedidosTableBody" class="divide-y divide-slate-300">
                            @forelse($items as $item)
                                @php
                                    $tallas = $item['tallas'] ?? [];
                                    $cantidadTotal = $item['cantidad'] ?? 0;
                                    $rowspanPrenda = count($tallas) > 0 ? count($tallas) : 1;
                                @endphp
                                @foreach($tallas as $indexTalla => $talla)
                                    @php
                                        $estadoActual = $talla['estado_bodega'] ?? '';
                                        $filaAmarilla = $estadoActual === 'Pendiente';
                                    @endphp
                                    <tr class="hover:bg-slate-50 transition-colors {{ $filaAmarilla ? 'bg-yellow-100' : '' }}"
                                        data-numero-pedido="{{ $item['numero_pedido'] }}"
                                        data-asesor="{{ $item['asesor'] ?? ($pedido['asesor'] ?? '') }}"
                                        data-empresa="{{ $item['empresa'] ?? ($pedido['cliente'] ?? '') }}"
                                        @if($filaAmarilla)
                                        style="background-color: rgba(254, 243, 199, 0.5) !important;"
                                        @endif
                                    >
                                        <!-- DESCRIPCIÓN (PRENDA) - Solo en primera talla -->
                                        @if($indexTalla === 0)
                                        <td class="px-4 py-3 text-xs text-black border-r border-slate-300" rowspan="{{ $rowspanPrenda }}" style="width: 22%;">
                                            @php
                                                $desc = $item['descripcion'];
                                                $nombre = $desc['nombre_prenda'] ?? $desc['nombre'] ?? 'Prenda sin nombre';
                                                $tela = $desc['tela'] ?? null;
                                                $color = $desc['color'] ?? null;
                                                $variantes = $desc['variantes'] ?? [];
                                                $primeraVariante = count($variantes) > 0 ? $variantes[0] : null;
                                                $genero = $primeraVariante['genero'] ?? null;
                                                $procesos = $desc['procesos'] ?? [];
                                            @endphp
                                            <div class="font-bold text-black mb-1">{{ $nombre }}</div>
                                            @if($tela || $color)
                                                <div class="text-black text-xs mb-1">
                                                    @if($tela && $color)
                                                        Tela: {{ $tela }} - Color: {{ $color }}
                                                    @elseif($tela)
                                                        Tela: {{ $tela }}
                                                    @else
                                                        Color: {{ $color }}
                                                    @endif
                                                </div>
                                            @endif
                                            @if($genero && strtoupper($genero) !== 'GENERICO')
                                                <div class="text-black text-xs mb-1">
                                                    Género: <span class="font-semibold">{{ strtoupper($genero) }}</span>
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
                                        
                                        <!-- GÉNERO - Solo en primera talla -->
                                        @if($indexTalla === 0)
                                        <td class="px-2 py-3 text-center text-[13px] text-black border-r border-slate-300" rowspan="{{ $rowspanPrenda }}" style="width: 6%;">
                                            @php
                                                $genero = '';
                                                if(isset($item['descripcion']['variantes']) && is_array($item['descripcion']['variantes']) && count($item['descripcion']['variantes']) > 0) {
                                                    $primeraVariante = $item['descripcion']['variantes'][0];
                                                    $genero = $primeraVariante['genero'] ?? '';
                                                }
                                                elseif(isset($item['genero'])) {
                                                    $genero = $item['genero'];
                                                }
                                                // Ocultar GENERICO - mostrar "—"
                                                if ($genero && strtoupper(trim($genero)) === 'GENERICO') {
                                                    $genero = '';
                                                }
                                            @endphp
                                            {{ $genero ? ucfirst(strtolower($genero)) : '—' }}
                                        </td>
                                        @endif
                                        
                                        <!-- TALLA -->
                                        <td class="px-2 py-3 text-center text-[10px] text-black border-r border-slate-300" style="width: 6%;">
                                            @php
                                                $es_epp = $item['es_epp'] ?? false;
                                                $talla_valor = $talla['talla'] ?? '';
                                                // Detectar si es un UUID (formato de UUID estándar)
                                                $es_uuid = preg_match('/^[0-9a-f]{8}-?[0-9a-f]{4}-?[0-9a-f]{4}-?[0-9a-f]{4}-?[0-9a-f]{12}$/i', $talla_valor) || 
                                                           preg_match('/^[0-9a-f]{32}$/i', $talla_valor);
                                                $es_sin_especificar = strtoupper($talla_valor) === 'SIN_ESPECIFICAR';
                                            @endphp
                                            {{ $es_epp || $es_uuid || $es_sin_especificar ? '—' : ($talla_valor ?: '—') }}
                                        </td>
                                        
                                        <!-- CANTIDAD -->
                                        <td class="px-4 py-3 text-center text-xs font-bold text-black border-r border-slate-300" style="width: 6%;">
                                            {{ $talla['cantidad'] ?? 0 }}
                                        </td>
                                        
                                        <!-- PENDIENTES -->
                                        <td class="px-2 py-3 border-r border-slate-300" style="width: 8%;">
                                            <textarea
                                                class="pendientes-input w-full px-1.5 py-1 border-2 border-slate-300 text-[10px] focus:ring-2 focus:ring-slate-500 focus:border-slate-700 outline-none transition resize-none bg-slate-50"
                                                style="font-family: 'Poppins', sans-serif; height: 32px;"
                                                data-numero-pedido="{{ $item['numero_pedido'] }}"
                                                data-talla="{{ $talla['talla'] }}"
                                                placeholder="Pendientes..."
                                                rows="1"
                                            >{{ $talla['pendientes'] ?? '' }}</textarea>
                                        </td>
                                        
                                        <!-- OBSERVACIONES - Solo en primera talla -->
                                        @if($indexTalla === 0)
                                        <td class="px-4 py-3 border-r border-slate-300" rowspan="{{ $rowspanPrenda }}" style="width: 16%;">
                                            <div class="flex gap-1">
                                                <textarea
                                                    class="observaciones-input flex-1 px-2 py-1 border border-slate-300 text-xs text-black focus:ring-1 focus:ring-slate-500 focus:border-slate-700 outline-none transition resize-none rounded bg-slate-50"
                                                    data-numero-pedido="{{ $item['numero_pedido'] }}"
                                                    data-talla="{{ $talla['talla'] }}"
                                                    placeholder="Notas..."
                                                    rows="1"
                                                    readonly
                                                    style="height: 40px;"
                                                >{{ $item['observaciones'] ?? '' }}</textarea>
                                                <button
                                                    type="button"
                                                    onclick="abrirModalNotas('{{ $item['numero_pedido'] }}', '{{ $talla['talla'] }}', '{{ addslashes($item['descripcion']['nombre_prenda'] ?? 'Prenda') }}', 'prenda', '{{ $talla['talla'] }}')"
                                                    class="px-2 py-1 bg-blue-500 hover:bg-blue-600 text-white text-xs font-bold rounded transition whitespace-nowrap"
                                                    title="Ver/agregar notas"
                                                >
                                                    💬
                                                </button>
                                            </div>
                                        </td>
                                        @endif
                                        
                                        <!-- FECHA PEDIDO - Solo en primera talla -->
                                        @if($indexTalla === 0)
                                        <td class="px-4 py-3 border-r border-slate-300" rowspan="{{ $rowspanPrenda }}" style="width: 12%;">
                                            <input
                                                type="date"
                                                class="fecha-pedido-input w-full px-2 py-1 border border-slate-300 text-xs text-black focus:ring-1 focus:ring-slate-500 focus:border-slate-700 outline-none transition rounded bg-slate-50"
                                                value="{{ $pedido['fecha_de_creacion_de_orden'] ? \Carbon\Carbon::parse($pedido['fecha_de_creacion_de_orden'])->format('Y-m-d') : '' }}"
                                                data-numero-pedido="{{ $item['numero_pedido'] }}"
                                                data-talla="{{ $talla['talla'] }}"
                                            >
                                        </td>
                                        @endif
                                        
                                        <!-- FECHA ENTREGA -->
                                        <td class="px-4 py-3 border-r border-slate-300" style="width: 12%;">
                                            <input
                                                type="date"
                                                class="fecha-input w-full px-2 py-1 border border-slate-300 text-xs text-black focus:ring-1 focus:ring-slate-500 focus:border-slate-700 outline-none transition rounded bg-slate-50"
                                                value="{{ $talla['fecha_entrega'] ?? '' }}"
                                                data-numero-pedido="{{ $item['numero_pedido'] }}"
                                                data-talla="{{ $talla['talla'] }}"
                                            >
                                        </td>
                                        
                                        <!-- ESTADO (ESTÁTICO) -->
                                        <td class="px-4 py-3" style="width: 18%;">
                                            <div class="w-full px-2 py-1 border border-slate-300 bg-slate-100 text-black text-xs font-semibold uppercase rounded" 
                                                 style="background-color: rgb(254, 243, 199); color: rgb(120, 53, 15);">
                                                @php
                                                    $estadoActual = $talla['estado_bodega'] ?? '';
                                                    $estadoTexto = match($estadoActual) {
                                                        'Pendiente' => 'PENDIENTE',
                                                        'Entregado' => 'ENTREGADO',
                                                        'Homologar' => 'HOMOLOGAR',
                                                        'Anulado' => 'ANULADO',
                                                        default => $estadoActual ?: 'SIN ESTADO'
                                                    };
                                                @endphp
                                                {{ $estadoTexto }}
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            @empty
                                <tr>
                                    <td colspan="9" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center">
                                            <span class="material-symbols-rounded text-slate-300 text-5xl">inventory_2</span>
                                            <p class="text-slate-500 font-medium mt-3">No hay artículos pendientes</p>
                                            <p class="text-slate-400 text-sm mt-1">Este pedido no tiene artículos con estado Pendiente</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Factura -->
<div id="modalFactura" class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-9999 overflow-auto" style="z-index: 100000; display: none;">
    <div class="bg-white rounded-lg shadow-2xl max-w-4xl w-full mx-4 my-8">
        <div class="bg-slate-900 px-6 py-4 border-b border-slate-200 flex justify-between items-center sticky top-0">
            <h2 class="text-lg font-semibold text-white"> Pedido</h2>
            <button onclick="cerrarModalFactura()" class="text-white hover:text-slate-200 text-2xl leading-none">✕</button>
        </div>
        <div id="facturaContenido" class="px-6 py-6 overflow-y-auto" style="max-height: calc(100vh - 200px)">
            <div class="flex justify-center items-center py-12">
                <span class="text-slate-500">⏳ Cargando factura...</span>
            </div>
        </div>
    </div>
</div>

<script>
// Variables globales para notas
window.usuarioActualId = {{ auth()->user()->id }};
window.__usuarioEsAdmin = {{ auth()->user()->hasRole('admin') ? 'true' : 'false' }};

// Diagnóstico de CSS y estructura de tabla
document.addEventListener('DOMContentLoaded', function() {
    console.log('🔍 [DIAGNÓSTICO-TABLA] Analizando estructura de tabla en despacho...');
    
    // Analizar contenedor principal
    const mainContainer = document.querySelector('.min-h-screen');
    console.log('📦 [DIAGNÓSTICO-TABLA] Contenedor principal:', {
        tagName: mainContainer?.tagName,
        classes: mainContainer?.className,
        computedWidth: mainContainer ? window.getComputedStyle(mainContainer).width : 'N/A',
        computedDisplay: mainContainer ? window.getComputedStyle(mainContainer).display : 'N/A'
    });
    
    // Analizar tabla
    const table = document.querySelector('table');
    if (table) {
        const tableStyle = window.getComputedStyle(table);
        console.log('📋 [DIAGNÓSTICO-TABLA] Tabla:', {
            tagName: table.tagName,
            classes: table.className,
            computedWidth: tableStyle.width,
            computedTableLayout: tableStyle.tableLayout,
            computedBorderCollapse: tableStyle.borderCollapse,
            offsetWidth: table.offsetWidth,
            scrollWidth: table.scrollWidth
        });
        
        // Analizar thead
        const thead = table.querySelector('thead');
        if (thead) {
            const theadRow = thead.querySelector('tr');
            if (theadRow) {
                const headers = theadRow.querySelectorAll('th');
                console.log('📊 [DIAGNÓSTICO-TABLA] Headers encontrados:', headers.length);
                headers.forEach((th, index) => {
                    const thStyle = window.getComputedStyle(th);
                    console.log(`  Header ${index + 1}:`, {
                        text: th.textContent.trim(),
                        width: thStyle.width,
                        computedWidth: th.offsetWidth,
                        padding: thStyle.padding,
                        fontSize: thStyle.fontSize,
                        fontWeight: thStyle.fontWeight
                    });
                });
            }
        }
        
        // Analizar tbody
        const tbody = table.querySelector('tbody');
        if (tbody) {
            const rows = tbody.querySelectorAll('tr');
            console.log('📄 [DIAGNÓSTICO-TABLA] Filas en tbody:', rows.length);
            
            if (rows.length > 0) {
                const firstRow = rows[0];
                const cells = firstRow.querySelectorAll('td');
                console.log('📊 [DIAGNÓSTICO-TABLA] Celdas en primera fila:', cells.length);
                
                cells.forEach((td, index) => {
                    const tdStyle = window.getComputedStyle(td);
                    console.log(`  Celda ${index + 1}:`, {
                        width: tdStyle.width,
                        computedWidth: td.offsetWidth,
                        padding: tdStyle.padding,
                        fontSize: tdStyle.fontSize,
                        textContent: td.textContent.trim().substring(0, 50) + (td.textContent.length > 50 ? '...' : ''),
                        rowspan: td.getAttribute('rowspan') || 'N/A'
                    });
                });
            }
        }
    }
    
    // Analizar contenedor de la tabla
    const tableContainer = document.querySelector('.bg-white.h-full.overflow-hidden');
    if (tableContainer) {
        const containerStyle = window.getComputedStyle(tableContainer);
        console.log('🏢 [DIAGNÓSTICO-TABLA] Contenedor de tabla:', {
            classes: tableContainer.className,
            computedWidth: containerStyle.width,
            computedHeight: containerStyle.height,
            computedOverflow: containerStyle.overflow,
            offsetWidth: tableContainer.offsetWidth,
            clientWidth: tableContainer.clientWidth
        });
    }
    
    // Analizar overflow
    const overflowDiv = document.querySelector('.overflow-x-auto');
    if (overflowDiv) {
        const overflowStyle = window.getComputedStyle(overflowDiv);
        console.log('🔄 [DIAGNÓSTICO-TABLA] Contenedor overflow:', {
            classes: overflowDiv.className,
            computedWidth: overflowStyle.width,
            computedOverflowX: overflowStyle.overflowX,
            offsetWidth: overflowDiv.offsetWidth,
            scrollWidth: overflowDiv.scrollWidth,
            hasHorizontalScroll: overflowDiv.scrollWidth > overflowDiv.offsetWidth
        });
    }
    
    // Analizar CSS rules que afectan a tablas
    setTimeout(() => {
        const stylesheets = Array.from(document.styleSheets);
        console.log('🎨 [DIAGNÓSTICO-TABLA] Analizando CSS rules para tablas...');
        
        stylesheets.forEach((sheet, sheetIndex) => {
            try {
                const rules = Array.from(sheet.cssRules || sheet.rules || []);
                rules.forEach((rule, ruleIndex) => {
                    if (rule.selectorText) {
                        const selector = rule.selectorText.toLowerCase();
                        if (selector.includes('table') || selector.includes('thead') || selector.includes('tbody') || selector.includes('tr') || selector.includes('td') || selector.includes('th')) {
                            console.log(`  📋 Hoja ${sheetIndex}, Regla ${ruleIndex}: ${rule.selectorText}`);
                            if (rule.style && rule.style.length > 0) {
                                for (let i = 0; i < rule.style.length; i++) {
                                    const property = rule.style[i];
                                    const value = rule.style.getPropertyValue(property);
                                    console.log(`    ${property}: ${value}`);
                                }
                            }
                        }
                    }
                });
            } catch (e) {
                // Ignorar errores de CORS
            }
        });
    }, 1000);
});

function abrirModalFactura(pedidoId) {
    alert('🔍 DEBUG: La función abrirModalFactura se ejecutó con pedidoId: ' + pedidoId);
    
    const modal = document.getElementById('modalFactura');
    const content = document.getElementById('facturaContent');
    
    // Mostrar modal con mensaje de carga
    modal.style.display = 'flex';
    modal.classList.remove('hidden');
    content.innerHTML = '<div class="flex justify-center items-center py-12"><span class="text-slate-500">⏳ Cargando factura...</span></div>';
    
    // Cargar contenido de la factura
    fetch(`/despacho/${pedidoId}/factura-datos`)
        .then(response => {
            alert('🔍 DEBUG: Response status: ' + response.status);
            return response.json();
        })
        .then(data => {
            alert('🔍 DEBUG: Datos recibidos: ' + JSON.stringify(data, null, 2));
            
            // DEBUG: Ver qué datos vienen
            console.log('📋 [FACTURA] Datos recibidos:', data);
            console.log('📋 [FACTURA] Prendas:', data.prendas);
            console.log('📋 [FACTURA] Primera prenda:', data.prendas?.[0]);
            console.log('📋 [FACTURA] Tallas:', data.prendas?.[0]?.tallas);
            console.log('📋 [FACTURA] Variantes:', data.prendas?.[0]?.variantes);
            console.log('📋 [FACTURA] Todas las claves de la primera prenda:', data.prendas?.[0] ? Object.keys(data.prendas[0]) : 'null');
            
            // ALERTA para ver la estructura exacta
            if (data && data.prendas && data.prendas[0]) {
                alert('📋 ESTRUCTURA DE DATOS:\n\n' + JSON.stringify(data.prendas[0], null, 2));
            } else {
                alert('📋 ERROR: No hay datos de prendas\n\nDatos completos: ' + JSON.stringify(data, null, 2));
            }
            
            // Si vienen datos JSON, generar HTML
            if (data && typeof data === 'object' && !data.html) {
                content.innerHTML = generarHTMLFacturaDesdeDatos(data);
            } else if (data.html) {
                content.innerHTML = data.html;
            } else {
                content.innerHTML = '<p class="text-red-500 text-center py-8">Error: No se encontraron datos</p>';
            }
        })
        .catch(error => {
            console.error('Error al cargar factura:', error);
            content.innerHTML = '<p class="text-red-500 text-center py-8">Error al cargar la factura</p>';
        });
}

function generarHTMLFacturaDesdeDatos(datos) {
    let html = '<div>';
    
    // Header
    html += '<div style="background: #1e3a8a; color: white; padding: 16px; border-radius: 6px; margin-bottom: 12px; text-align: center;">';
    html += '<div style="font-size: 18px; font-weight: 700; margin-bottom: 8px;">FACTURA DE PEDIDO</div>';
    html += '<div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; font-size: 12px; margin-top: 12px;">';
    html += '<div><div style="font-size: 10px; opacity: 0.8;">Número</div><div style="font-weight: 600;">' + (datos.pedido?.numero_pedido || 'N/A') + '</div></div>';
    html += '<div><div style="font-size: 10px; opacity: 0.8;">Cliente</div><div style="font-weight: 600;">' + (datos.pedido?.cliente || 'N/A') + '</div></div>';
    html += '<div><div style="font-size: 10px; opacity: 0.8;">Asesora</div><div style="font-weight: 600;">' + (datos.pedido?.asesor || 'N/A') + '</div></div>';
    html += '</div>';
    html += '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; font-size: 12px; margin-top: 8px;">';
    html += '<div><div style="font-size: 10px; opacity: 0.8;">Forma de Pago</div><div style="font-weight: 600;">' + (datos.pedido?.forma_pago || 'N/A') + '</div></div>';
    html += '<div><div style="font-size: 10px; opacity: 0.8;">Fecha</div><div style="font-weight: 600;">' + (datos.pedido?.fecha || new Date().toLocaleDateString('es-ES')) + '</div></div>';
    html += '</div></div>';
    
    // Prendas
    if (datos.prendas && datos.prendas.length > 0) {
        datos.prendas.forEach((prenda, index) => {
            html += '<div style="background: white; border: 1px solid #e5e7eb; border-radius: 6px; margin-bottom: 16px; padding: 16px;">';
            html += '<div style="border-bottom: 1px solid #e5e7eb; padding-bottom: 8px; margin-bottom: 12px;">';
            html += '<div style="font-size: 14px; font-weight: 600; color: #374151;">';
            html += 'PRENDA ' + (index + 1) + ': ' + (prenda.nombre_prenda || 'Sin nombre');
            if (prenda.estado === 'SE SACA DE BODEGA') {
                html += ' <span style="color: #ea580c; font-weight: bold;">- SE SACA DE BODEGA</span>';
            }
            html += '</div>';
            html += '<div style="font-size: 12px; color: #6b7280; margin-top: 2px;">' + (prenda.descripcion || 'Sin descripción') + '</div>';
            html += '</div>';
            
            // Telas
            if (prenda.colores_telas && prenda.colores_telas.length > 0) {
                html += '<div style="margin-bottom: 12px;">';
                prenda.colores_telas.forEach(colorTela => {
                    html += '<div style="padding: 6px 0; border-bottom: 1px solid #f3f4f6;">';
                    html += '<span style="font-size: 11px; color: #374151;">';
                    html += '<strong>Tela:</strong> ' + (colorTela.tela_nombre || 'N/A');
                    if (colorTela.color_nombre) {
                        html += ' <strong style="margin-left: 12px;">Color:</strong> ' + colorTela.color_nombre;
                    }
                    html += '</span></div>';
                });
                html += '</div>';
            }
            
            // Imagen
            if (prenda.imagenes && prenda.imagenes.length > 0) {
                html += '<div style="float: right; margin-left: 12px; margin-bottom: 8px;">';
                prenda.imagenes.forEach(imagen => {
                    html += '<img src="' + (imagen.ruta_webp || imagen.ruta_original) + '" style="width: 80px; height: 80px; object-fit: cover; border-radius: 4px; border: 1px solid #e5e7eb;">';
                });
                html += '</div>';
            }
            
            // Contenido
            html += '<div style="margin-right: 100px;">';
            
            // Variantes/Tallas
            if (prenda.tallas && prenda.tallas.length > 0) {
                html += '<table style="width: 100%; font-size: 11px; border-collapse: collapse;">';
                html += '<thead><tr style="background: #f9fafb; border-bottom: 1px solid #e5e7eb;">';
                html += '<th style="padding: 6px 8px; text-align: left; font-weight: 600; color: #374151;">Talla</th>';
                html += '<th style="padding: 6px 8px; text-align: center; font-weight: 600; color: #374151;">Cantidad</th>';
                html += '</tr></thead><tbody>';
                prenda.tallas.forEach(talla => {
                    html += '<tr style="background: #ffffff; border-bottom: 1px solid #f3f4f6;">';
                    html += '<td style="padding: 6px 8px; font-weight: 600; color: #374151;">' + (talla.talla || 'N/A') + '</td>';
                    html += '<td style="padding: 6px 8px; text-align: center; color: #6b7280;">' + (talla.cantidad || 0) + '</td>';
                    html += '</tr>';
                });
                html += '</tbody></table>';
            } else if (prenda.variantes && prenda.variantes.length > 0) {
                // Fallback por si vienen como variantes
                html += '<table style="width: 100%; font-size: 11px; border-collapse: collapse;">';
                html += '<thead><tr style="background: #f9fafb; border-bottom: 1px solid #e5e7eb;">';
                html += '<th style="padding: 6px 8px; text-align: left; font-weight: 600; color: #374151;">Talla</th>';
                html += '<th style="padding: 6px 8px; text-align: center; font-weight: 600; color: #374151;">Cantidad</th>';
                html += '</tr></thead><tbody>';
                prenda.variantes.forEach(variante => {
                    html += '<tr style="background: #ffffff; border-bottom: 1px solid #f3f4f6;">';
                    html += '<td style="padding: 6px 8px; font-weight: 600; color: #374151;">' + (variante.talla || 'N/A') + '</td>';
                    html += '<td style="padding: 6px 8px; text-align: center; color: #6b7280;">' + (variante.cantidad || 0) + '</td>';
                    html += '</tr>';
                });
                html += '</tbody></table>';
            }
            
            // Procesos
            if (prenda.procesos && prenda.procesos.length > 0) {
                html += '<div style="margin-bottom: 0;">';
                prenda.procesos.forEach(proceso => {
                    html += '<div style="padding: 8px 0; border-bottom: 1px solid #f3f4f6;">';
                    html += '<div style="font-weight: 600; color: #374151; margin-bottom: 4px; font-size: 11px;">' + (proceso.tipo_proceso || 'N/A') + '</div>';
                    if (proceso.observaciones) {
                        html += '<div style="font-size: 10px; color: #6b7280; margin-bottom: 2px;">' + proceso.observaciones + '</div>';
                    }
                    if (proceso.tallas && proceso.tallas.caballero) {
                        Object.entries(proceso.tallas.caballero).forEach(([talla, cantidad]) => {
                            html += '<div style="font-size: 10px; color: #6b7280; margin-bottom: 2px;">Caballero: ' + talla + '(' + cantidad + ')</div>';
                        });
                    }
                    html += '</div>';
                });
                html += '</div>';
            }
            
            html += '</div><div style="clear: both;"></div></div>';
        });
    }
    
    // EPPs
    if (datos.epps && datos.epps.length > 0) {
        html += '<div style="background: white; border: 1px solid #e5e7eb; border-radius: 6px; margin-bottom: 16px; padding: 16px;">';
        html += '<div style="border-bottom: 1px solid #e5e7eb; padding-bottom: 8px; margin-bottom: 12px;">';
        html += '<div style="font-size: 14px; font-weight: 600; color: #374151;">EPPS</div></div>';
        datos.epps.forEach(epp => {
            html += '<div style="margin-bottom: 12px;">';
            html += '<div style="font-weight: 600; color: #374151; margin-bottom: 4px;">' + (epp.nombre || 'N/A') + '</div>';
            html += '<div style="font-size: 12px; color: #6b7280;">Cantidad: ' + (epp.cantidad || 0) + '</div>';
            if (epp.observaciones) {
                html += '<div style="font-size: 10px; color: #6b7280; margin-top: 4px;">Observaciones: ' + epp.observaciones + '</div>';
            }
            html += '</div>';
        });
        html += '</div>';
    }
    
    // Totales
    html += '<div style="margin: 12px 0; padding: 12px; background: #f3f4f6; border-radius: 6px; border: 2px solid #d1d5db; text-align: right;">';
    html += '<div style="font-size: 12px; margin-bottom: 8px;"><strong>Total Ítems:</strong> ' + (datos.total_items || 0) + '</div>';
    html += '</div>';
    
    html += '</div>';
    return html;
}

function cerrarModalFactura() {
    const modal = document.getElementById('modalFactura');
    if (modal) {
        modal.style.display = 'none';
        modal.classList.add('hidden');
    }
}
</script>

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
                        style="display: none;"
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
