@extends('layouts.asesores')

@section('title', 'Detalle de Pendientes')
@section('page-title', 'Detalle de Pendientes - Pedido #' . $pedido->numero_pedido)

@section('extra_styles')
    <link rel="stylesheet" href="{{ asset('css/asesores/pedidos/index.css') }}">
    <style>
        .detalle-container {
            padding: 20px;
            max-width: 1400px;
            margin: 0 auto;
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            color: #6b7280;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s ease;
            margin-bottom: 20px;
        }

        .back-button:hover {
            border-color: #3b82f6;
            color: #3b82f6;
            background: #f8fafc;
        }

        .pedido-info-card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-bottom: 24px;
        }

        .pedido-info-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 24px;
        }

        .pedido-numero-grande {
            font-size: 24px;
            font-weight: 700;
            color: #1f2937;
            margin-right: 16px;
        }

        .pedido-cliente-grande {
            font-size: 16px;
            color: #6b7280;
        }

        .info-item {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .info-label {
            font-size: 12px;
            font-weight: 600;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-value {
            font-size: 16px;
            font-weight: 600;
            color: #1f2937;
        }

        .btn-observaciones {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: #3b82f6;
            border: none;
            border-radius: 8px;
            color: white;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .btn-observaciones:hover {
            background: #2563eb;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(59, 130, 246, 0.3);
        }

        .tabla-pendientes {
            background: white;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .tabla-header {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            padding: 16px 24px;
            color: white;
        }

        .tabla-header h2 {
            font-size: 20px;
            font-weight: 700;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .tabla-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: #f9fafb;
            border-bottom: 2px solid #e5e7eb;
        }

        thead th {
            padding: 16px 20px;
            text-align: left;
            font-size: 13px;
            font-weight: 700;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        tbody tr {
            border-bottom: 1px solid #e5e7eb;
            transition: all 0.2s ease;
        }

        tbody tr:hover {
            background: #f9fafb;
        }

        tbody tr:last-child {
            border-bottom: none;
        }

        tbody td {
            padding: 16px 20px;
            font-size: 14px;
            color: #1f2937;
        }

        .area-badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 700;
        }

        .area-costura {
            background: #dbeafe;
            color: #1e40af;
        }

        .area-epp {
            background: #fef3c7;
            color: #92400e;
        }

        .cantidad-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 40px;
            padding: 4px 12px;
            background: #e0e7ff;
            color: #3730a3;
            border-radius: 8px;
            font-weight: 700;
        }

        .pendientes-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 40px;
            padding: 4px 12px;
            background: #fee2e2;
            color: #991b1b;
            border-radius: 8px;
            font-weight: 700;
        }

        .observaciones-cell {
            max-width: 300px;
            color: #6b7280;
            font-size: 13px;
        }

        .sin-observaciones {
            color: #d1d5db;
            font-style: italic;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }

        .empty-state .material-symbols-rounded {
            font-size: 64px;
            color: #d1d5db;
            margin-bottom: 16px;
        }

        .empty-state h3 {
            font-size: 18px;
            font-weight: 600;
            color: #6b7280;
            margin-bottom: 8px;
        }

        .empty-state p {
            font-size: 14px;
            color: #9ca3af;
        }

        .total-resumen {
            display: flex;
            justify-content: flex-end;
            gap: 24px;
            padding: 20px 24px;
            background: #f9fafb;
            border-top: 2px solid #e5e7eb;
            font-size: 16px;
            font-weight: 700;
        }

        .total-item {
            display: flex;
            gap: 8px;
        }

        .total-label {
            color: #6b7280;
        }

        .total-value {
            color: #1f2937;
        }

        .total-pendientes {
            color: #dc2626;
        }
    </style>
@endsection

@section('content')
    <div class="detalle-container">
        {{-- Botón Volver --}}
        <a href="{{ route('asesores.pendientes') }}" class="back-button">
            <span class="material-symbols-rounded">arrow_back</span>
            Volver a Pendientes
        </a>

        {{-- Info del Pedido --}}
        <div class="pedido-info-card">
            <div class="pedido-info-header">
                <div>
                    <span class="pedido-numero-grande">Pedido #{{ $pedido->numero_pedido }}</span>
                    <span class="pedido-cliente-grande">{{ $pedido->cliente ?? 'Sin Cliente' }}</span>
                </div>
                <div style="display: flex; align-items: center; gap: 16px;">
                    <div class="info-item">
                        <span class="info-label">Fecha Pedido</span>
                        <span class="info-value">{{ $pedido->created_at ? $pedido->created_at->format('d/m/Y') : '-' }}</span>
                    </div>
                    <button class="btn-observaciones" onclick="abrirModalNotas()">
                        💬 Ver Observaciones
                    </button>
                </div>
            </div>
        </div>

        {{-- Tabla de Pendientes --}}
        <div class="tabla-pendientes">
            <div class="tabla-header">
                <h2>
                    <span class="material-symbols-rounded">inventory_2</span>
                    Detalle de Pendientes
                </h2>
            </div>

            @if(count($detalles) > 0)
                <div class="tabla-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Artículo</th>
                                <th>Área</th>
                                <th>Talla</th>
                                <th style="text-align: center;">Pendiente</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($detalles as $detalle)
                                @php
                                    // Convertir el item a array si es un stdClass
                                    $item = is_object($detalle) ? json_decode(json_encode($detalle), true) : $detalle;
                                    
                                    $descripcion = $item['descripcion'] ?? [];
                                    $nombre = $descripcion['nombre'] ?? ($item['prenda_nombre'] ?? 'Sin nombre');
                                    $tela = $descripcion['tela'] ?? null;
                                    $color = $descripcion['color'] ?? null;
                                    
                                    // Intentar obtener procesos de diferentes formas
                                    $procesos = $descripcion['procesos'] ?? $item['procesos'] ?? [];
                                    
                                    // Si procesos es un objeto, convertirlo a array
                                    if (is_object($procesos)) {
                                        $procesos = json_decode(json_encode($procesos), true);
                                    }
                                    
                                    $area = $item['area'] ?? '';
                                    $talla = $item['talla'] ?? '';
                                    $cantidadTotal = $item['cantidad'] ?? ($item['cantidad_total'] ?? 0);
                                    $pendientes = (int)($item['pendientes'] ?? 0);
                                    
                                    // Si no hay valor de pendientes pero el estado es Pendiente, 
                                    // por defecto es el total (para compatibilidad con registros antiguos)
                                    if ($pendientes <= 0) {
                                        $pendientes = $cantidadTotal;
                                    }
                                    $genero = $item['genero'] ?? null;
                                    $descripcionTexto = $descripcion['descripcion'] ?? null;
                                    $colorNombre = $item['color_nombre'] ?? null;
                                @endphp
                                <tr>
                                    <td class="px-4 py-3 text-xs text-black">
                                        <div class="font-bold text-black mb-1">
                                            {{ $nombre }}
                                            @if($genero)
                                                <span style="font-weight: 500; color: #6b7280; font-size: 11px;">({{ ucfirst(strtolower($genero)) }})</span>
                                            @endif
                                        </div>
                                        @if($descripcionTexto)
                                            <div class="text-xs" style="color: #4b5563; margin-bottom: 4px;">
                                                {{ $descripcionTexto }}
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

                                        @if(is_array($procesos) && count($procesos) > 0)
                                            <div class="text-black text-xs mt-2 space-y-0.5">
                                                @foreach($procesos as $proceso)
                                                    <div class="flex items-start gap-1">
                                                        <span class="text-blue-600 font-bold">•</span>
                                                        <span>
                                                            {{ $proceso['tipo_proceso'] ?? ($proceso['nombre'] ?? 'Proceso') }}
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
                                                            @elseif(isset($proceso['observacion']) && $proceso['observacion'])
                                                                ({{ $proceso['observacion'] }})
                                                            @endif
                                                        </span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-xs text-black">
                                        <span class="area-badge {{ $area === 'Costura' ? 'area-costura' : 'area-epp' }}">
                                            {{ $area ?: '-' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-black">
                                        {{ $area === 'EPP' ? '-' : ($talla ?: '-') }}
                                        @if($colorNombre)
                                            <div class="text-xs" style="color: #6b7280; margin-top: 2px;">{{ $colorNombre }}</div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-xs text-black" style="text-align: center;">
                                        <div class="flex flex-col items-center">
                                            <span class="pendientes-badge">{{ $pendientes }}</span>
                                            @if($pendientes != $cantidadTotal)
                                                <span class="text-[10px] text-slate-400 mt-1">de {{ $cantidadTotal }}</span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Resumen Total --}}
                <div class="total-resumen">
                    <div class="total-item">
                        <span class="total-label">Total Ítems:</span>
                        <span class="total-value">{{ count($detalles) }}</span>
                    </div>
                    <div class="total-item">
                        <span class="total-label">Total Pendientes:</span>
                        <span class="total-value total-pendientes">
                            @php
                                $sumaPendientes = array_sum(array_map(function($d) { 
                                    $d = is_object($d) ? json_decode(json_encode($d), true) : $d;
                                    $p = (int)($d['pendientes'] ?? 0);
                                    return $p > 0 ? $p : ($d['cantidad'] ?? ($d['cantidad_total'] ?? 0));
                                }, $detalles));
                            @endphp
                            {{ $sumaPendientes }}
                        </span>
                    </div>
                </div>
            @else
                <div class="empty-state">
                    <span class="material-symbols-rounded">check_circle</span>
                    <h3>No hay ítems pendientes</h3>
                    <p>Este pedido no tiene ítems pendientes en este momento</p>
                </div>
            @endif
        </div>

        {{-- Modal de Notas --}}
        <div id="modalNotas" class="fixed inset-0 bg-black bg-opacity-50 z-[9999] hidden flex items-center justify-center p-4" style="z-index: 100001;">
            <div class="bg-white rounded-lg shadow-2xl max-w-2xl w-full mx-4 my-8">
                <div class="bg-slate-900 px-6 py-4 border-b border-slate-200 flex justify-between items-center">
                    <h2 class="text-lg font-semibold text-white">💬 Notas - Pedido <span id="modalNotasNumeroPedido">#</span></h2>
                    <button onclick="cerrarModalNotas()" class="text-white hover:text-slate-200 text-2xl leading-none">✕</button>
                </div>
                <div class="px-6 py-6">
                    <div id="notasHistorial" class="mb-6" style="max-height: 350px; overflow-y: auto;"></div>
                    
                    <div class="text-center py-4">
                        <button
                            type="button"
                            onclick="cerrarModalNotas()"
                            class="px-6 py-2 bg-slate-400 hover:bg-slate-500 text-white font-bold rounded-lg transition"
                        >
                            Cerrar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        console.log('Vista de detalle de pendientes cargada');

        function abrirModalNotas() {
            const modal = document.getElementById('modalNotas');
            const historial = document.getElementById('notasHistorial');
            const numeroPedidoSpan = document.getElementById('modalNotasNumeroPedido');
            const pedidoId = {{ $pedido->id }};
            const numeroPedido = '{{ $pedido->numero_pedido }}';
            
            // Mostrar modal
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.style.overflow = 'hidden';
            
            // Actualizar título
            numeroPedidoSpan.textContent = numeroPedido;
            
            // Mostrar loading
            historial.innerHTML = '<div class="text-center text-slate-500"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-slate-900 mx-auto mb-2"></div>Cargando notas...</div>';
            
            // Cargar notas del pedido
            fetch(`/api/asesores/pendientes/${pedidoId}/notas`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data) {
                        renderizarNotas(data.data);
                    } else {
                        historial.innerHTML = '<div class="text-center text-slate-500">No hay notas para este pedido</div>';
                    }
                })
                .catch(error => {
                    console.error('Error al cargar notas:', error);
                    historial.innerHTML = '<div class="text-center text-red-500">Error al cargar notas</div>';
                });
        }

        function cerrarModalNotas() {
            const modal = document.getElementById('modalNotas');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.style.overflow = 'auto';
        }

        function renderizarNotas(notas) {
            const historial = document.getElementById('notasHistorial');
            
            if (!notas || notas.length === 0) {
                historial.innerHTML = '<div class="text-center text-slate-500">No hay notas para este pedido</div>';
                return;
            }
            
            let html = '<div class="space-y-4">';
            notas.forEach(nota => {
                const fecha = new Date(nota.created_at).toLocaleString('es-ES', {
                    year: 'numeric',
                    month: '2-digit',
                    day: '2-digit',
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit'
                });
                
                html += `
                    <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:16px;">
                        <div style="display:flex;justify-content:space-between;gap:10px;align-items:flex-start;margin-bottom:8px;">
                            <div>
                                <div style="font-weight:700;color:#0f172a;font-size:14px;">${nota.usuario_nombre || 'Usuario'}</div>
                                <div style="font-size:11px;color:#64748b;font-weight:500;">${nota.usuario_rol || 'Sin rol'}</div>
                            </div>
                            <div style="color:#64748b;font-size:12px;white-space:nowrap;">
                                ${fecha}
                            </div>
                        </div>
                        ${nota.talla ? `
                        <div style="margin-bottom:8px;">
                            <span style="display:inline-block;background:#dbeafe;color:#1e40af;padding:2px 8px;border-radius:4px;font-size:11px;font-weight:600;">
                                Talla: ${nota.talla}
                            </span>
                        </div>
                        ` : ''}
                        <div style="margin:0;color:#1e293b;font-size:13px;white-space:pre-wrap;">${nota.contenido || ''}</div>
                    </div>
                `;
            });
            html += '</div>';
            
            historial.innerHTML = html;
        }
    </script>
@endpush
