@extends('supervisor-pedidos.layout')

@section('title', 'Pendiente Costura')
@section('page-title', 'Pendiente Costura')
@section('search-action', route('supervisor-pedidos.pendientes-costura'))

@push('styles')
<style>
    .hidden { display: none !important; }

    /* Asegurar ocultamiento por defecto aunque existan clases display conflictivas */
    #novedadesEditModal.hidden,
    #modalConfirmarEliminar.hidden {
        display: none !important;
    }

    /* Forzar comportamiento modal aunque no carguen utilidades CSS */
    #novedadesEditModal {
        position: fixed !important;
        inset: 0 !important;
        background: rgba(0, 0, 0, 0.75) !important;
        z-index: 100001 !important;
        align-items: center;
        justify-content: center;
        overflow: auto;
        padding: 1rem;
        display: none;
    }

    #novedadesEditModal:not(.hidden) {
        display: flex !important;
    }

    #novedadesEditModal > div {
        width: 100%;
        max-width: 42rem;
        max-height: calc(100vh - 2rem);
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }

    #novedadesEditModal #novedadesHistorial {
        flex: 1 1 auto;
        min-height: 180px;
        max-height: none !important;
        overflow-y: auto !important;
        -webkit-overflow-scrolling: touch;
    }

    #novedadesEditModal .px-6.py-6 {
        display: flex;
        flex-direction: column;
        min-height: 0;
        max-height: calc(100vh - 170px);
    }

    .modal-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.45);
        z-index: 9999;
        align-items: center;
        justify-content: center;
        padding: 1rem;
    }

    .modal-content {
        background: white;
        border-radius: 12px;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        padding: 2rem;
        max-width: 500px;
        width: 100%;
        max-height: 80vh;
        display: flex;
        flex-direction: column;
    }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
    }

    .btn-close {
        background: none;
        border: none;
        font-size: 1.5rem;
        cursor: pointer;
        color: #6b7280;
    }

    .modal-body {
        flex: 1;
        overflow-y: auto;
    }

    .form-group {
        margin-bottom: 1rem;
    }

    .form-control {
        width: 100%;
        padding: 0.5rem;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 0.9rem;
    }

    .modal-footer {
        display: flex;
        gap: 0.5rem;
        margin-top: 1.5rem;
    }

    .btn {
        padding: 0.5rem 1rem;
        border-radius: 6px;
        border: 1px solid transparent;
        cursor: pointer;
        font-size: 0.9rem;
        font-weight: 500;
    }

    .btn-primary {
        background: #3b82f6;
        color: white;
    }

    .btn-secondary {
        background: #e5e7eb;
        color: #1f2937;
    }

    .btn-filter-column {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        position: relative;
    }

    .btn-filter-column.has-filter {
        opacity: 1;
        background: rgba(255, 255, 255, 0.18);
        border-radius: 8px;
        box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.18);
    }

    .filter-badge {
        position: absolute;
        top: -8px;
        right: -8px;
        background: #ef4444;
        color: white;
        border-radius: 50%;
        width: 20px;
        height: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.7rem;
        font-weight: 700;
        border: 2px solid white;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        opacity: 0;
        transform: scale(0);
        transition: all 0.3s ease;
    }

    .btn-filter-column.has-filter .filter-badge {
        opacity: 1;
        transform: scale(1);
    }

    [data-row="processo"] {
        background: var(--row-bg-color, #ffffff) !important;
        opacity: 1;
        transition: background 0.2s ease, opacity 0.2s ease;
    }

    [data-row="processo"]:hover,
    [data-row="processo"]:focus-within {
        background: #f9fafb !important;
    }

    [data-row="processo"][data-color-stored]:not([data-color-stored=""]):hover,
    [data-row="processo"][data-color-stored]:not([data-color-stored=""]):focus-within {
        background: var(--row-bg-color, #ffffff) !important;
        opacity: 0.9;
    }

    @media (max-width: 768px) {
        .table-scroll-container {
            max-height: 65vh !important;
        }

        .costura-pagination {
            justify-content: center !important;
        }
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="supervisor-pedidos-container">
                <!-- Botón Generar Reporte -->
                <div style="margin-bottom: 1rem; display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
                    <button type="button" onclick="abrirModalGenerarReporte()" style="
                        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
                        color: white;
                        border: none;
                        padding: 0.75rem 1.5rem;
                        border-radius: 8px;
                        font-weight: 600;
                        cursor: pointer;
                        transition: all 0.3s ease;
                        display: flex;
                        align-items: center;
                        gap: 0.5rem;
                    "
                    onmouseover="this.style.boxShadow='0 4px 12px rgba(16, 185, 129, 0.4)'; this.style.transform='translateY(-2px)';"
                    onmouseout="this.style.boxShadow='none'; this.style.transform='translateY(0)';"
                    >
                        <i class="fas fa-file-pdf" style="font-size: 1rem;"></i>
                        Generar Reporte
                    </button>
                    <button type="button" id="btnToggleAzulesCostura" onclick="toggleFiltroAzulesCostura()" style="
                        background: #1d4ed8;
                        color: white;
                        border: none;
                        padding: 0.75rem 1.25rem;
                        border-radius: 8px;
                        font-weight: 600;
                        cursor: pointer;
                        transition: all 0.2s ease;
                        display: inline-flex;
                        align-items: center;
                        gap: 0.5rem;
                    ">
                        <i class="fas fa-eye" style="font-size: 0.95rem;"></i>
                        Ver todos
                    </button>
                </div>

                <div id="supervisorPendientesCosturaContent">
                <!-- Tabla de Ordenes -->
                <div class="costura-table-frame" style="background: #e5e7eb; border-radius: 8px; overflow: visible; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08); padding: 0.75rem; width: 100%; max-width: 100%;">
                    <!-- Contenedor con Scroll -->
                    <div class="table-scroll-container" style="overflow-x: auto; overflow-y: auto; width: 100%; max-width: 100%; max-height: 800px; border-radius: 6px; scrollbar-width: thin; scrollbar-color: #cbd5e1 #f1f5f9;">
                        <!-- Header Azul -->
                        <div style="
                            background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%);
                            color: white;
                            padding: 0.75rem 1rem;
                            display: grid;
                            grid-template-columns: 110px 170px 110px 200px 120px 200px 160px 130px 100px;
                            gap: 0.15rem;
                            font-weight: 600;
                            font-size: 0.8rem;
                            text-transform: uppercase;
                            letter-spacing: 0.5px;
                            min-width: min-content;
                            border-radius: 6px;
                        ">
                            <div class="th-wrapper" style="display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                                <span>Actions</span>
                            </div>
                            <div class="th-wrapper" style="display: flex; align-items: center; gap: 0.5rem;">
                                <span>Fecha de Creacion</span>
                            </div>
                            <div class="th-wrapper" style="display: flex; align-items: center; gap: 0.5rem;">
                                <span>N° Recibo</span>
                            </div>
                            <div class="th-wrapper" style="display: flex; align-items: center; gap: 0.5rem;">
                                <span>Cliente</span>
                            </div>
                            <div class="th-wrapper" style="display: flex; align-items: center; gap: 0.5rem;">
                                <span>Area</span>
                                <button type="button" class="btn-filter-column" data-col="area" title="Filtrar Area" style="display: flex; align-items: center; background: none; border: none; color: white; cursor: pointer; padding: 0;">
                                    <i class="fas fa-filter" style="font-size: 1rem;"></i>
                                </button>
                            </div>
                            <div class="th-wrapper" style="display: flex; align-items: center; gap: 0.5rem;">
                                <span>Prendas</span>
                            </div>
                            <div class="th-wrapper" style="display: flex; align-items: center; gap: 0.5rem;">
                                <span>Novedades</span>
                            </div>
                            <div class="th-wrapper" style="display: flex; align-items: center; gap: 0.5rem;">
                                <span>Asesora</span>
                            </div>
                            <div class="th-wrapper" style="display: flex; align-items: center; gap: 0.5rem;">
                                <span>Color</span>
                            </div>
                        </div>

                        <div id="costurasRows">
                            <!-- Filas -->
                            @if($procesosConCantidad->count() === 0)
                                <div style="padding: 3rem 2rem; text-align: center; color: #6b7280;">
                                    <i class="fas fa-inbox" style="font-size: 3rem; color: #d1d5db; margin-bottom: 1rem; display: block;"></i>
                                    <p style="font-size: 1rem; margin: 0;">No hay pendientes</p>
                                </div>
                            @else
                                @foreach($procesosConCantidad as $proceso)
                                    <div data-row="processo" data-pedido-id="{{ $proceso['pedido_id'] }}" data-prenda-id="{{ $proceso['prenda_id'] ?? '' }}" data-numero-recibo="{{ $proceso['numero_recibo'] }}" data-es-parcial="{{ !empty($proceso['es_parcial']) ? 'true' : 'false' }}" data-pedido-parcial-id="{{ $proceso['pedido_parcial_id'] ?? '' }}" data-color-stored="{{ $proceso['color_costura'] ?? '' }}" style="
                                        --row-bg-color: {{ $proceso['color_costura'] ?: '#ffffff' }};
                                        display: grid;
                                        grid-template-columns: 110px 170px 110px 200px 120px 200px 160px 130px 100px;
                                        gap: 0.15rem;
                                        padding: 1rem;
                                        border-bottom: 1px solid #e5e7eb;
                                        align-items: start;
                                        min-width: min-content;
                                        transition: background 0.2s ease;
                                    ">
                                        
                                        <!-- Actions -->
                                        <div style="display: flex; align-items: center; justify-content: center;">
                                            <button
                                                type="button"
                                                data-pedido-id="{{ $proceso['pedido_id'] }}"
                                                data-prenda-id="{{ $proceso['prenda_id'] ?? '' }}"
                                                data-numero-recibo="{{ $proceso['numero_recibo'] }}"
                                                data-es-parcial="{{ !empty($proceso['es_parcial']) ? 'true' : 'false' }}"
                                                data-pedido-parcial-id="{{ $proceso['pedido_parcial_id'] ?? '' }}"
                                                onclick="event.stopPropagation(); openReciboCosturaModalFromRow(this)"
                                                style="display:inline-flex;align-items:center;justify-content:center;padding:6px 12px;background:#1d4ed8;color:#fff;border-radius:8px;font-size:0.8rem;font-weight:600;text-decoration:none;"
                                            >
                                                Ver
                                            </button>
                                        </div>

                                        <!-- Fecha de Creacion -->
                                        <div style="display: flex; align-items: center; font-size: 0.9rem; color: #374151;">
                                            {{ \Carbon\Carbon::parse($proceso['fecha_creacion'])->format('d/m/Y') }}
                                        </div>

                                        <!-- Numero de Recibo -->
                                        <div style="display: flex; align-items: center; font-size: 0.9rem; color: #374151; font-weight: 500;">
                                            {{ $proceso['numero_recibo'] }}
                                        </div>

                                        <!-- Cliente -->
                                        <div style="display: flex; align-items: center; font-size: 0.9rem; color: #374151;">
                                            {{ $proceso['cliente'] }} ({{ $proceso['numero_pedido'] ?? '-' }})
                                        </div>

                                        <!-- Area -->
                                        <div style="display: flex; align-items: center; font-size: 0.9rem; color: #374151;">
                                            @if($proceso['area'])
                                                <span style="background: #e8f3ff; color: #1e40af; padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: bold; white-space: nowrap; border: 1px solid #bfdbfe; display: inline-block;">
                                                    {{ $proceso['area'] }}
                                                </span>
                                            @else
                                                <span style="background: #f3f4f6; color: #6b7280; padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: bold; white-space: nowrap; display: inline-block;">
                                                    Sin Area
                                                </span>
                                            @endif
                                        </div>

                                        <!-- Prendas -->
                                        <div style="display: flex; align-items: start; font-size: 0.9rem; color: #374151;">
                                            <div class="prenda-list">
                                                @php
                                                    $prendasAgrupadas = [];
                                                    foreach($proceso['prendas'] as $prenda) {
                                                        if(!empty($prenda->color_nombre) && !empty($prenda->cantidad_color)) {
                                                            // Con color
                                                            $key = $prenda->nombre_prenda . '|' . $prenda->color_nombre;
                                                            $prendasAgrupadas[$key] = ($prendasAgrupadas[$key] ?? 0) + (int) $prenda->cantidad_color;
                                                        } elseif(!empty($prenda->cantidad_talla) && empty($prenda->color_nombre)) {
                                                            // Sin color
                                                            $tela = !empty($prenda->tela) ? ' ' . $prenda->tela : '';
                                                            $key = $prenda->nombre_prenda . $tela . '|sin-color';
                                                            $prendasAgrupadas[$key] = ($prendasAgrupadas[$key] ?? 0) + (int) $prenda->cantidad_talla;
                                                        }
                                                    }
                                                @endphp
                                                @foreach($prendasAgrupadas as $prenda => $cantidad)
                                                    <div style="margin-bottom: 0.25rem;">
                                                        @php
                                                            $partes = explode('|', $prenda);
                                                            $nombrePrenda = $partes[0];
                                                            $tipo = $partes[1] ?? 'sin-color';
                                                            
                                                            if($tipo === 'sin-color') {
                                                                echo $cantidad . ' ' . $nombrePrenda;
                                                            } else {
                                                                echo $cantidad . ' ' . $nombrePrenda . ' color ' . $tipo;
                                                            }
                                                        @endphp
                                                    </div>
                                                @endforeach
                                                @if(count($prendasAgrupadas) === 0)
                                                    <div>-</div>
                                                @endif
                                            </div>
                                        </div>

                                        <!-- Novedades -->
                                        <div style="display: flex; align-items: center; font-size: 0.85rem; color: #374151;">
                                            @php
                                                $novedadesTexto = '';
                                                try {
                                                    $pedido = \App\Models\PedidoProduccion::find($proceso['pedido_id']);
                                                    if ($pedido && $pedido->prendas && $pedido->prendas->count() > 0) {
                                                        $prendaTarget = $pedido->prendas->firstWhere('id', $proceso['prenda_id'] ?? null);
                                                        $prendasIter = $prendaTarget ? collect([$prendaTarget]) : $pedido->prendas;

                                                        $novedadesRecibo = [];
                                                        foreach ($prendasIter as $prenda) {
                                                            $novedadesPrenda = $prenda->novedadesRecibo()
                                                                ->where('numero_recibo', $proceso['numero_recibo'])
                                                                ->orderBy('creado_en', 'desc')
                                                                ->get();

                                                            foreach ($novedadesPrenda as $novedad) {
                                                                $textoLimpio = str_replace(["\r", "\n", "'", '"'], ' ', $novedad->novedad_texto);
                                                                $novedadesRecibo[] = $textoLimpio;
                                                            }
                                                        }

                                                        if (!empty($novedadesRecibo)) {
                                                            $novedadesTexto = implode(' | ', $novedadesRecibo);
                                                        }
                                                    }
                                                } catch (\Exception $e) {
                                                    $novedadesTexto = '';
                                                }
                                            @endphp

                                            <button
                                                type="button"
                                                data-pedido-id="{{ $proceso['pedido_id'] }}"
                                                data-numero-recibo="{{ $proceso['numero_recibo'] }}"
                                                data-novedades="{{ addslashes(str_replace(["\r", "\n"], ' ', $novedadesTexto)) }}"
                                                onclick="event.stopPropagation(); openNovedadesModalRecibo(this)"
                                                title="Ver novedades del recibo"
                                                style="
                                                    width: 100%;
                                                    text-align: left;
                                                    background: #f9fafb;
                                                    border: 1px solid #e5e7eb;
                                                    border-radius: 8px;
                                                    padding: 6px 10px;
                                                    display: flex;
                                                    align-items: center;
                                                    justify-content: space-between;
                                                    gap: 8px;
                                                    cursor: pointer;
                                                    transition: background 0.2s ease;
                                                "
                                                onmouseover="this.style.background='#f3f4f6'"
                                                onmouseout="this.style.background='#f9fafb'"
                                            >
                                                @if($novedadesTexto)
                                                    <span style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 120px;">{{ \Illuminate\Support\Str::limit(str_replace(["\r", "\n"], ' ', $novedadesTexto), 28, '...') }}</span>
                                                @else
                                                    <span style="color:#9ca3af;">Sin novedades</span>
                                                @endif
                                                <i class="fas fa-edit" style="color:#6b7280;"></i>
                                            </button>
                                        </div>

                                        <!-- Asesora -->
                                        <div style="display: flex; align-items: center; font-size: 0.9rem; color: #374151;">
                                            {{ $proceso['asesor'] }}
                                        </div>

                                        <!-- Color Selector -->
                                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                                            <div class="color-selector-wrapper" data-recibo-id="{{ $proceso['numero_recibo'] }}" style="position: relative; display: flex; gap: 0.3rem; align-items: center;">
                                                <button type="button" class="color-btn" data-color="#e0f2fe" title="Azul claro" style="width: 24px; height: 24px; border-radius: 50%; border: 2px solid #cbd5e1; background: #e0f2fe; cursor: pointer; transition: all 0.2s;"></button>
                                                <button type="button" class="color-btn" data-color="#fef08a" title="Amarillo" style="width: 24px; height: 24px; border-radius: 50%; border: 2px solid #cbd5e1; background: #fef08a; cursor: pointer; transition: all 0.2s;"></button>
                                                <button type="button" class="color-btn" data-color="#fecaca" title="Rojo claro" style="width: 24px; height: 24px; border-radius: 50%; border: 2px solid #cbd5e1; background: #fecaca; cursor: pointer; transition: all 0.2s;"></button>
                                            </div>
                                        </div>

                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </div>
                    <div class="costura-pagination" style="padding: 0.85rem 0.25rem 0.25rem; display: flex; justify-content: center; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                        {{ $procesosConCantidad->onEachSide(1)->links('vendor.pagination.bootstrap-custom') }}
                    </div>
                </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Botón Flotante para Limpiar Filtros -->
<div id="btnLimpiarFiltrosCostura" style="
    position: fixed;
    bottom: 30px;
    right: 30px;
    z-index: 999;
    display: none;
    animation: slideIn 0.3s ease;
">
    <button onclick="limpiarTodosFiltros()" style="
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        color: white;
        border: none;
        border-radius: 50%;
        width: 56px;
        height: 56px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-size: 1.5rem;
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
        transition: all 0.3s ease;
        padding: 0;
    "
    onmouseover="this.style.boxShadow='0 6px 20px rgba(239, 68, 68, 0.6)'; this.style.transform='scale(1.1)';"
    onmouseout="this.style.boxShadow='0 4px 12px rgba(239, 68, 68, 0.4)'; this.style.transform='scale(1)';"
    title="Limpiar todos los filtros">
        ✕
    </button>
</div>

<style>
    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>

<!-- Modal detalle recibo (estilo Recibos Costura) -->
<div id="modal-overlay" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.5); backdrop-filter: blur(4px); z-index: 9997; display: none; pointer-events: auto;" onclick="closeModalOverlay()"></div>
<div id="order-detail-modal-wrapper" style="width: 90%; max-width: 672px; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 9998; pointer-events: auto; display: none;">
    <x-orders-components.order-detail-modal />
</div>

<!-- Modal de Novedades (mismo componente de /recibos-costura) -->
<x-modals.novedades-edit-modal />

<!-- Modal de Alerta para Novedades -->
<div id="modalAlerta" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-9999" style="z-index: 100003; display: none;">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4" style="background:#fff;border-radius:12px;box-shadow:0 20px 40px rgba(0,0,0,.25);width:min(100%,520px);margin:1rem;">
        <div id="alertaHeader" class="px-6 py-4 border-b" style="padding:1rem 1.25rem;border-bottom:1px solid #e5e7eb;background:#2563eb;">
            <h3 id="alertaTitulo" class="text-lg font-semibold text-white flex items-center gap-2" style="margin:0;color:#fff;font-size:1rem;font-weight:700;display:flex;align-items:center;gap:.5rem;">
                <span id="alertaIcono" class="material-symbols-rounded">info</span>
                Mensaje
            </h3>
        </div>
        <div class="px-6 py-4" style="padding:1rem 1.25rem;">
            <p id="alertaMensaje" class="text-gray-700" style="margin:0;color:#374151;">Mensaje del sistema</p>
        </div>
        <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex justify-end" style="background:#f9fafb;padding:1rem 1.25rem;border-top:1px solid #e5e7eb;display:flex;justify-content:flex-end;">
            <button type="button" onclick="cerrarModalAlerta()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition" style="border:0;border-radius:10px;background:#2563eb;color:#fff;font-weight:600;padding:.6rem .9rem;cursor:pointer;">
                Entendido
            </button>
        </div>
    </div>
</div>

<!-- Modal Filtro Dinamico -->
<div id="modalFiltro" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalFiltroTitulo">Filtrar</h2>
            <button class="btn-close" type="button" onclick="cerrarModalFiltro()">&times;</button>
        </div>
        <div class="modal-body" id="filtroContenido">
            <!-- Contenido Dinamico -->
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-primary" onclick="aplicarFiltroColumna(event)">Aplicar</button>
            <button type="button" class="btn btn-secondary" onclick="cerrarModalFiltro()">Cancelar</button>
            <button type="button" class="btn btn-secondary" onclick="limpiarFiltroActual()" style="margin-left: auto;">Limpiar Filtro</button>
        </div>
    </div>
</div>

<!-- Modal Generar Reporte -->
<div id="modalGenerarReporte" class="modal-overlay">
    <div class="modal-content" style="max-width: 450px;">
        <div class="modal-header">
            <h2>Generar Reporte</h2>
            <button class="btn-close" type="button" onclick="cerrarModalGenerarReporte()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #374151;">
                    Filtrar por antigüedad
                </label>
                <select id="filtroAntiguedad" style="
                    width: 100%;
                    padding: 0.75rem;
                    border: 1px solid #d1d5db;
                    border-radius: 6px;
                    font-size: 0.95rem;
                    background-color: white;
                    color: #374151;
                ">
                    <option value="">Todos los recibos</option>
                    <option value="7">7 días</option>
                    <option value="15">15 días</option>
                    <option value="30">30 días</option>
                </select>
                <p style="margin-top: 0.5rem; font-size: 0.85rem; color: #6b7280;">
                    Los recibos se mostrarán ordenados por fecha (más antiguos primero)
                </p>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="cerrarModalGenerarReporte()">Cancelar</button>
            <button type="button" class="btn btn-primary" onclick="generarReporteCostura()">Descargar PDF</button>
        </div>
    </div>
</div>

@push('scripts')
<script src="{{ asset('js/supervisor-pedidos/shared/receipts-renderers.js') }}?v={{ filemtime(public_path('js/supervisor-pedidos/shared/receipts-renderers.js')) }}"></script>
<script src="{{ asset('js/supervisor-pedidos/shared/receipts-api-filters.js') }}?v={{ filemtime(public_path('js/supervisor-pedidos/shared/receipts-api-filters.js')) }}"></script>
<script type="module" src="{{ asset('js/modulos/pedidos-recibos/loader.js') }}?v={{ filemtime(public_path('js/modulos/pedidos-recibos/loader.js')) }}"></script>
<script src="{{ asset('js/recibos-novedades.js') }}?v={{ time() }}"></script>
<script>
let filtroActual = null;

// Fallback de zoom para el modal de detalle cuando no existe el handler global.
if (typeof window.toggleReceiptZoom !== 'function') {
    window.receiptZoomState = {
        current: 1,
        levels: [1, 1.2, 1.4, 1.6]
    };

    window.applyReceiptZoom = function applyReceiptZoom(zoomLevel = 1) {
        const wrapper = document.getElementById('order-detail-modal-wrapper');
        let card = wrapper ? wrapper.querySelector('.order-detail-card') : null;
        if (!card) {
            card = document.querySelector('.order-detail-modal-container .order-detail-card');
        }
        if (!card) return;

        card.style.transformOrigin = 'center top';
        card.style.transition = 'transform 0.2s ease';

        if (typeof card.style.zoom !== 'undefined') {
            card.style.zoom = String(zoomLevel);
            card.style.transform = 'none';
        } else {
            card.style.zoom = '';
            card.style.transform = `scale(${zoomLevel})`;
        }

        const btnZoom = document.getElementById('btn-zoom-receipt');
        if (btnZoom) {
            btnZoom.title = `Zoom recibo (${Math.round(zoomLevel * 100)}%)`;
        }
    };

    window.resetReceiptZoom = function resetReceiptZoom() {
        window.receiptZoomState.current = 1;
        window.applyReceiptZoom(1);
    };

    window.toggleReceiptZoom = function toggleReceiptZoom() {
        const { levels, current } = window.receiptZoomState;
        const idx = levels.findIndex((level) => level === current);
        const next = idx >= 0 ? (idx + 1) % levels.length : 1;
        const nextLevel = levels[next];
        window.receiptZoomState.current = nextLevel;
        window.applyReceiptZoom(nextLevel);
    };
}

// Wrapper igual que en /recibos-costura
function openNovedadesModalRecibo(button) {
    const pedidoId = button.getAttribute('data-pedido-id');
    const numeroRecibo = button.getAttribute('data-numero-recibo');
    const novedadesActuales = button.getAttribute('data-novedades') || '';

    if (typeof abrirModalNovedadesRecibo === 'function') {
        abrirModalNovedadesRecibo(pedidoId, numeroRecibo);
        return;
    }

    setTimeout(() => {
        if (typeof abrirModalNovedadesRecibo === 'function') {
            abrirModalNovedadesRecibo(pedidoId, numeroRecibo);
        } else {
            alert(`Novedades del recibo ${numeroRecibo}:\n\n${novedadesActuales || 'Sin novedades'}`);
        }
    }, 100);
}

function escapeHtml(str) {
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

const receiptsFilters = window.SupervisorReceiptsApiFilters.create({
    contentSelector: '#supervisorPendientesCosturaContent',
    openButtonSelector: '#supervisorPendientesCosturaContent .btn-filter-column',
    filterOptionsEndpoint: (columna) => `/api/supervisor-pedidos/recibos/pendientes-costura/filtro-opciones/${columna}`,
    navigate: (url) => window.navegarPendientesCostura(url),
    titleMap: {
        fecha_creacion: 'Filtrar Fecha de Creación',
        numero_recibo: 'Filtrar N° Recibo',
        cliente: 'Filtrar Cliente',
        area: 'Filtrar Área',
        prendas: 'Filtrar Prendas',
        asesor: 'Filtrar Asesora'
    }
});

function abrirModalFiltro(columna) { receiptsFilters.open(columna); }
function cerrarModalFiltro() { receiptsFilters.close(); }
function aplicarFiltroColumna(event) { receiptsFilters.apply(event); }
function limpiarFiltroActual() { receiptsFilters.clearCurrent(); }
function actualizarIndicadoresFiltros() { receiptsFilters.refreshIndicators(); }

receiptsFilters.bindUi();
actualizarIndicadoresFiltros();

function debounce(fn, wait) {
    let timeoutId;
    return function(...args) {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => fn.apply(this, args), wait);
    };
}

function ejecutarBusquedaGeneralCostura() {
    const inputBusqueda = document.getElementById('busqueda');
    const textoBusqueda = (inputBusqueda?.value || '').trim();
    const url = new URL(window.location.href);
    const busquedaActualEnUrl = (url.searchParams.get('busqueda') || '').trim();

    if (textoBusqueda === '') {
        url.searchParams.delete('busqueda');
    } else {
        url.searchParams.set('busqueda', textoBusqueda);
    }

    url.searchParams.delete('page');

    const destino = url.toString();
    if (textoBusqueda === busquedaActualEnUrl && destino === window.location.href) {
        return;
    }

    window.navegarPendientesCostura(destino);
}

function inicializarBusquedaGeneralCostura() {
    const inputBusqueda = document.getElementById('busqueda');
    if (!inputBusqueda) return;

    const formBusqueda = inputBusqueda.closest('form');
    if (formBusqueda) {
        if (formBusqueda.getAttribute('data-costura-search-init') !== '1') {
            formBusqueda.setAttribute('data-costura-search-init', '1');
            formBusqueda.addEventListener('submit', function(event) {
                event.preventDefault();
                ejecutarBusquedaGeneralCostura();
            });
        }
    }

    if (inputBusqueda.getAttribute('data-costura-search-input-init') !== '1') {
        inputBusqueda.setAttribute('data-costura-search-input-init', '1');
        inputBusqueda.addEventListener('input', debounce(function() {
            ejecutarBusquedaGeneralCostura();
        }, 350));
    }
}

let mostrarAzulesCostura = new URLSearchParams(window.location.search).get('ver_todos') === '1';

function actualizarBotonAzulesCostura() {
    const btn = document.getElementById('btnToggleAzulesCostura');
    if (!btn) return;
    const icono = mostrarAzulesCostura ? 'fa-eye-slash' : 'fa-eye';
    const texto = mostrarAzulesCostura ? 'Ocultar azules' : 'Ver todos';
    btn.innerHTML = `<i class="fas ${icono}" style="font-size: 0.95rem;"></i> ${texto}`;
}

function toggleFiltroAzulesCostura() {
    const url = new URL(window.location.href);
    if (mostrarAzulesCostura) {
        url.searchParams.delete('ver_todos');
    } else {
        url.searchParams.set('ver_todos', '1');
    }
    url.searchParams.delete('page');
    window.navegarPendientesCostura(url.toString());
}

inicializarBusquedaGeneralCostura();
actualizarBotonAzulesCostura();

function construirUrlApiPendientesCostura(urlString) {
    const source = new URL(urlString, window.location.origin);
    const apiUrl = `/api/supervisor-pedidos/recibos/pendientes-costura${source.search || ''}`;
    console.log('[construirUrlApiPendientesCostura] urlString:', urlString, 'apiUrl:', apiUrl);
    return apiUrl;
}

async function resolverReciboCosturaContexto(pedidoId, numeroRecibo) {
    try {
        const response = await fetch(`/registros/${pedidoId}/recibos-datos`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            cache: 'no-store'
        });

        if (!response.ok) {
            return null;
        }

        const payload = await response.json();
        const data = payload?.data || payload || {};
        const prendas = Array.isArray(data?.prendas) ? data.prendas : [];
        let primeraPrendaValida = 0;
        const numeroNormalizado = String(numeroRecibo || '').trim();

        for (const prenda of prendas) {
            const prendaId = Number(prenda?.id || 0);
            if (!prendaId) continue;
            if (!primeraPrendaValida) primeraPrendaValida = prendaId;

            const recibos = Array.isArray(prenda?.recibos) ? prenda.recibos : [];
            const reciboCostura = recibos.find((recibo) => {
                const tipo = String(recibo?.tipo_recibo || recibo?.tipo || '').toUpperCase();
                const consecutivo = String(recibo?.consecutivo_actual ?? recibo?.numero_recibo ?? '').trim();
                return (tipo === 'COSTURA' || tipo === 'COSTURA-BODEGA') && consecutivo === numeroNormalizado;
            });

            if (reciboCostura) {
                const esParcial = Boolean(
                    reciboCostura?.es_parcial ||
                    reciboCostura?.origen === 'PARCIAL' ||
                    reciboCostura?.pedido_parcial_id
                );

                return {
                    prendaId,
                    esParcial,
                    pedidoParcialId: Number(reciboCostura?.pedido_parcial_id || 0) || null,
                };
            }
        }

        // Fallback: usar primera prenda disponible para abrir modal y evitar redirecciones.
        if (primeraPrendaValida) {
            return {
                prendaId: primeraPrendaValida,
                esParcial: false,
                pedidoParcialId: null,
            };
        }
    } catch (error) {
        console.error('[resolverReciboCosturaContexto] Error:', error);
    }

    return null;
}

function esperarModuloRecibos(timeoutMs = 1200) {
    return new Promise((resolve) => {
        const startedAt = Date.now();
        const timer = setInterval(() => {
            const ready = window.pedidosRecibosModule && typeof window.pedidosRecibosModule.abrirRecibo === 'function';
            if (ready) {
                clearInterval(timer);
                resolve(true);
                return;
            }

            if (Date.now() - startedAt >= timeoutMs) {
                clearInterval(timer);
                resolve(false);
            }
        }, 80);
    });
}

window.openReciboCosturaModalFromRow = async function(button) {
    const pedidoId = Number(button?.getAttribute('data-pedido-id') || 0);
    let prendaId = Number(button?.getAttribute('data-prenda-id') || 0);
    const numeroRecibo = String(button?.getAttribute('data-numero-recibo') || '').trim();
    let esParcial = String(button?.getAttribute('data-es-parcial') || '').toLowerCase() === 'true';
    let pedidoParcialId = Number(button?.getAttribute('data-pedido-parcial-id') || 0);

    if (!pedidoId) {
        console.error('[openReciboCosturaModalFromRow] Falta pedido_id', { pedidoId, prendaId, numeroRecibo });
        return;
    }

    if ((!prendaId || !pedidoParcialId) && numeroRecibo) {
        const contexto = await resolverReciboCosturaContexto(pedidoId, numeroRecibo);
        if (contexto) {
            if (!prendaId) prendaId = Number(contexto.prendaId || 0);
            if (!pedidoParcialId) pedidoParcialId = Number(contexto.pedidoParcialId || 0);
            esParcial = esParcial || Boolean(contexto.esParcial);
        }
    }

    if (!prendaId) {
        console.error('[openReciboCosturaModalFromRow] No se pudo resolver prenda_id para abrir modal.', { pedidoId, numeroRecibo, esParcial, pedidoParcialId });
        if (typeof mostrarAlerta === 'function') {
            mostrarAlerta('Error', 'No se pudo abrir el recibo en modal para este registro.', 'error');
        }
        return;
    }

    const moduleReady = await esperarModuloRecibos();
    if (moduleReady) {
        if (esParcial && pedidoParcialId > 0 && typeof window.openOrderDetailModalWithParcial === 'function') {
            window.openOrderDetailModalWithParcial(pedidoParcialId, prendaId, 'COSTURA', pedidoId, 'COSTURA ANEXO');
            return;
        }

        window.pedidosRecibosModule.abrirRecibo(pedidoId, prendaId, 'costura', null, {
            targetConsecutivo: numeroRecibo || null,
            esParcial: false
        });
        return;
    }

    if (typeof mostrarAlerta === 'function') {
        mostrarAlerta('Error', 'El visor del recibo no está listo. Intenta nuevamente en unos segundos.', 'warning');
    }
};

window.closeModalOverlay = function() {
    if (window.pedidosRecibosModule && typeof window.pedidosRecibosModule.cerrarRecibo === 'function') {
        window.pedidosRecibosModule.cerrarRecibo();
        return;
    }

    const modalWrapper = document.getElementById('order-detail-modal-wrapper');
    const modalOverlay = document.getElementById('modal-overlay');
    if (modalWrapper) modalWrapper.style.display = 'none';
    if (modalOverlay) modalOverlay.style.display = 'none';
};

const receiptsRenderers = window.SupervisorReceiptsRenderers;

window.navegarPendientesCostura = async function navegarPendientesCostura(urlString, options = {}) {
    console.log('[navegarPendientesCostura] urlString:', urlString);
    const { pushState = true } = options;
    const container = document.getElementById('supervisorPendientesCosturaContent');
    const rows = document.getElementById('costurasRows');
    const pagination = document.querySelector('.costura-pagination');
    if (!container) {
        window.location.href = urlString;
        return;
    }
    if (!rows) return;

    try {
        container.style.opacity = '0.6';
        container.style.pointerEvents = 'none';
        const source = new URL(urlString, window.location.origin);
        mostrarAzulesCostura = source.searchParams.get('ver_todos') === '1';
        actualizarBotonAzulesCostura();

        const apiUrl = construirUrlApiPendientesCostura(source.toString());
        const res = await fetch(apiUrl, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            cache: 'no-store'
        });

        const payload = await res.json();
        const procesos = payload?.data?.procesosConCantidad;
        if (!res.ok || !Array.isArray(procesos)) {
            window.location.href = urlString;
            return;
        }

        if (procesos.length === 0) {
            rows.innerHTML = receiptsRenderers.emptyStateHtml();
        } else {
            rows.innerHTML = procesos.map((proceso) => receiptsRenderers.renderSewingRow(proceso, escapeHtml, {
                gridTemplate: '110px 170px 110px 200px 120px 200px 160px 130px 100px',
                showActions: true,
                actionMode: 'modal',
                actionHandlerName: 'openReciboCosturaModalFromRow',
                showRemainingDays: false
            })).join('');
        }

        // Mantener la paginación sincronizada con los filtros actuales.
        if (pagination) {
            try {
                const htmlRes = await fetch(source.toString(), {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'text/html'
                    },
                    cache: 'no-store'
                });

                if (htmlRes.ok) {
                    const htmlText = await htmlRes.text();
                    const tempDoc = new DOMParser().parseFromString(htmlText, 'text/html');
                    const newPagination = tempDoc.querySelector('.costura-pagination');
                    if (newPagination) {
                        pagination.innerHTML = newPagination.innerHTML;
                    }
                }
            } catch (paginationError) {
                console.warn('[navegarPendientesCostura] No se pudo sincronizar la paginación:', paginationError);
            }
        }

        if (pushState) {
            window.history.pushState({ url: source.toString() }, '', source.toString());
        }

        actualizarIndicadoresFiltros();
        inicializarSelectorColores();
        inicializarBusquedaGeneralCostura();
        window.dispatchEvent(new Event('supervisorPedidos:filtersUpdated'));
    } catch (e) {
        window.location.href = urlString;
        return;
    } finally {
        container.style.opacity = '';
        container.style.pointerEvents = '';
    }
}

window.addEventListener('popstate', function() {
    mostrarAzulesCostura = new URLSearchParams(window.location.search).get('ver_todos') === '1';
    actualizarBotonAzulesCostura();
    navegarPendientesCostura(window.location.href, { pushState: false });
});

document.addEventListener('click', function(e) {
    const legacyReciboLink = e.target.closest('a[href*="/recibos-costura"]');
    if (legacyReciboLink) {
        e.preventDefault();
        e.stopPropagation();

        const row = legacyReciboLink.closest('[data-row="processo"]');
        if (!row) return;

        const pseudoButton = {
            getAttribute: (name) => row.getAttribute(name),
        };
        openReciboCosturaModalFromRow(pseudoButton);
        return;
    }

    const a = e.target.closest('#supervisorPendientesCosturaContent a');
    if (!a) return;
    if (e.target.closest('.costura-pagination')) return;
    if (a.hasAttribute('data-pdf-report-link')) return;
    const href = a.getAttribute('href');
    if (!href) return;
    if (href.startsWith('#')) return;
    if (a.target && a.target !== '_self') return;
    if (a.hasAttribute('download')) return;
    if (!href.startsWith(window.location.origin) && !href.startsWith('/')) return;

    const urlAbs = href.startsWith('http') ? href : (window.location.origin + href);
    let path = '';
    try {
        path = new URL(urlAbs).pathname || '';
    } catch (e) {
        return;
    }

    if (!path.startsWith('/supervisor-pedidos/pendientes-costura')) return;
    if (path === '/supervisor-pedidos/pendientes-costura/reporte') return;
    e.preventDefault();
    navegarPendientesCostura(urlAbs);
});

function inicializarSelectorColores() {
    // Aplicar colores guardados al cargar la pagina
    document.querySelectorAll('[data-row="processo"]').forEach((fila) => {
        const color = fila.getAttribute('data-color-stored');
        
        if (color && color.trim()) {
            // Aplicar el color al fondo de la fila
            fila.style.setProperty('--row-bg-color', color);
            
            // Encontrar y marcar el boton correspondiente
            const wrapper = fila.querySelector('.color-selector-wrapper');
            if (wrapper) {
                wrapper.querySelectorAll('.color-btn').forEach(btn => {
                    if (btn.getAttribute('data-color') === color) {
                        btn.style.boxShadow = '0 0 0 2px #1e40af';
                    }
                });
            }
        }
    });

    // Configurar manejadores de clic para los botones de color
    document.querySelectorAll('.color-btn').forEach((btn) => {
        if (btn.dataset.colorBound === '1') return;
        btn.dataset.colorBound = '1';

        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const wrapper = this.closest('.color-selector-wrapper');
            const reciboId = wrapper.getAttribute('data-recibo-id');
            const color = this.getAttribute('data-color');
            const filaBg = wrapper.closest('[data-row="processo"]');

            // Aplicar color a la fila
            filaBg.style.setProperty('--row-bg-color', color);
            filaBg.setAttribute('data-color-stored', color);
            
            // Retroalimentacion visual
            wrapper.querySelectorAll('.color-btn').forEach(b => b.style.boxShadow = '');
            this.style.boxShadow = '0 0 0 2px #1e40af';

            if (!mostrarAzulesCostura && String(color).toLowerCase() === '#e0f2fe') {
                filaBg.style.display = 'none';
            }
            
            // Guardar en BD
            guardarColorCostura(reciboId, color);
        });
    });
}

// Ejecutar al cargar
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', inicializarSelectorColores);
} else {
    inicializarSelectorColores();
}

// Funcion para guardar el color en la BD
async function guardarColorCostura(reciboId, color) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    try {
        const response = await fetch('/api/supervisor-pedidos/recibos/guardar-color-costura', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({
            numero_recibo: reciboId,
            color: color
        })
        });

        if (!response.ok) {
            const errorText = await response.text();
            console.error('Error al guardar color (HTTP):', response.status, errorText);
            return;
        }

        const data = await response.json();
        if (!data?.success) {
            console.error('Error al guardar color (API):', data);
        }
    } catch (error) {
        console.error('Error al guardar color:', error);
    }
}

// Función para limpiar todos los filtros
function limpiarTodosFiltros() {
    const url = new URL(window.location.href);
    url.searchParams.delete('area');
    url.searchParams.delete('cliente');
    url.searchParams.delete('asesor');
    url.searchParams.delete('numero_recibo');
    url.searchParams.delete('prendas');
    url.searchParams.delete('fecha_creacion');
    url.searchParams.delete('busqueda');
    url.searchParams.delete('ver_todos');
    url.searchParams.delete('page');

    const btnLimpiar = document.getElementById('btnLimpiarFiltrosCostura');
    if (btnLimpiar) {
        btnLimpiar.style.display = 'none';
    }

    window.navegarPendientesCostura(url.toString());
}

// Función para mostrar/ocultar botón de limpiar filtros
function actualizarVisibilidadBotonLimpiar() {
    const url = new URL(window.location.href);
    const params = url.searchParams;

    const tieneArea = params.has('area');
    const tieneCliente = params.has('cliente');
    const tieneAsesor = params.has('asesor');
    const tieneNumeroRecibo = params.has('numero_recibo');
    const tienePrendas = params.has('prendas');
    const tieneFechaCreacion = params.has('fecha_creacion');
    const tieneBusqueda = params.has('busqueda');

    const hayFiltros = tieneArea || tieneCliente || tieneAsesor || tieneNumeroRecibo || tienePrendas || tieneFechaCreacion || tieneBusqueda;

    const btnLimpiar = document.getElementById('btnLimpiarFiltrosCostura');
    if (btnLimpiar) {
        btnLimpiar.style.display = hayFiltros ? 'block' : 'none';
    }
}

// Ejecutar al cargar la página
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', actualizarVisibilidadBotonLimpiar);
} else {
    actualizarVisibilidadBotonLimpiar();
}

// Actualizar cuando se navegue
window.addEventListener('popstate', actualizarVisibilidadBotonLimpiar);
const originalNavegar = window.navegarPendientesCostura;
window.navegarPendientesCostura = async function(url, options) {
    if (originalNavegar) {
        await originalNavegar(url, options);
    }
    actualizarVisibilidadBotonLimpiar();
};

// Funciones para Modal de Reporte - Expuestas globalmente
window.abrirModalGenerarReporte = function() {
    const modal = document.getElementById('modalGenerarReporte');
    if (modal) {
        modal.style.display = 'flex';
        const select = document.getElementById('filtroAntiguedad');
        if (select) select.value = '';
    }
};

window.cerrarModalGenerarReporte = function() {
    const modal = document.getElementById('modalGenerarReporte');
    if (modal) {
        modal.style.display = 'none';
    }
};

window.generarReporteCostura = function() {
    const filtroAntiguedad = document.getElementById('filtroAntiguedad').value;
    const url = new URL(window.location.href);

    // Construir URL para el reporte con todos los parámetros actuales
    const reporteUrl = new URL('{{ route("supervisor-pedidos.pendientes-costura.reporte") }}', window.location.origin);

    // Copiar filtros actuales
    url.searchParams.forEach((value, key) => {
        if (key !== 'page') {
            reporteUrl.searchParams.set(key, value);
        }
    });

    // Agregar parámetro de antigüedad si está seleccionado
    if (filtroAntiguedad) {
        reporteUrl.searchParams.set('dias_antiguedad', filtroAntiguedad);
    }

    window.cerrarModalGenerarReporte();
    window.mostrarModalGenerando();

    // Usar fetch para obtener el PDF como blob
    fetch(reporteUrl.toString(), {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        }
    })
    .then(response => response.blob())
    .then(blob => {
        // Crear link de descarga
        const urlBlob = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = urlBlob;
        link.download = 'reporte_pendientes_costura_' + new Date().toISOString().slice(0,10) + '.pdf';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        window.URL.revokeObjectURL(urlBlob);

        // Cerrar modal cuando la descarga inicia
        const modal = document.getElementById('modalGenerandoPDF');
        if (modal) modal.remove();
    })
    .catch(error => {
        console.error('Error:', error);
        const modal = document.getElementById('modalGenerandoPDF');
        if (modal) modal.remove();
        alert('Error al generar el reporte');
    });
};

window.mostrarModalGenerando = function() {
    const tiempoInicio = Date.now();
    const html = `
        <div id="modalGenerandoPDF" style="
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10000;
        ">
            <div style="
                background: white;
                border-radius: 12px;
                padding: 2rem;
                text-align: center;
                max-width: 450px;
                box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            ">
                <div style="font-size: 2rem; margin-bottom: 1rem;">⏳</div>
                <h2 style="margin: 0 0 0.5rem 0; color: #1f2937;">Generando PDF</h2>
                <p style="margin: 0 0 1rem 0; color: #6b7280; font-size: 0.95rem;">
                    Tu reporte se está generando. Por favor espera...
                </p>

                <div style="
                    background: #f0f9ff;
                    border: 1px solid #bfdbfe;
                    border-radius: 8px;
                    padding: 1rem;
                    margin-bottom: 1rem;
                ">
                    <div style="display: flex; justify-content: space-around; align-items: center;">
                        <div>
                            <div style="font-size: 2rem; font-weight: bold; color: #0369a1;" id="tiempoTranscurrido">0s</div>
                            <div style="font-size: 0.75rem; color: #6b7280;">Tiempo transcurrido</div>
                        </div>
                        <div style="color: #cbd5e1;">•</div>
                        <div>
                            <div style="font-size: 1.2rem; font-weight: 600; color: #059669;" id="tiempoEstimado">~1-2 min</div>
                            <div style="font-size: 0.75rem; color: #6b7280;">Tiempo estimado</div>
                        </div>
                    </div>
                </div>

                <div style="margin-bottom: 1rem; height: 4px; background: #e5e7eb; border-radius: 2px; overflow: hidden;">
                    <div style="
                        height: 100%;
                        background: #3b82f6;
                        width: 30%;
                        animation: progress 1.5s ease-in-out infinite;
                    "></div>
                </div>

                <p style="margin: 0; color: #9ca3af; font-size: 0.8rem;">
                    El PDF se descargará automáticamente cuando esté listo
                </p>
            </div>
        </div>
        <style>
            @keyframes progress {
                0% { width: 30%; }
                50% { width: 70%; }
                100% { width: 30%; }
            }
        </style>
    `;
    document.body.insertAdjacentHTML('beforeend', html);

    // Actualizar contador cada segundo
    const intervalo = setInterval(() => {
        const ahora = Date.now();
        const segundosTranscurridos = Math.floor((ahora - tiempoInicio) / 1000);

        const elementoTiempo = document.getElementById('tiempoTranscurrido');
        if (elementoTiempo) {
            if (segundosTranscurridos < 60) {
                elementoTiempo.textContent = segundosTranscurridos + 's';
            } else {
                const minutos = Math.floor(segundosTranscurridos / 60);
                const segundos = segundosTranscurridos % 60;
                elementoTiempo.textContent = minutos + 'm ' + segundos + 's';
            }
        } else {
            clearInterval(intervalo);
        }
    }, 1000);
};

window.verificarYDescargarPDF = function(timestamp, intento = 0) {
    const maxIntentos = 120; // 4 minutos máximo

    fetch('/api/supervisor-pedidos/verificar-reporte-costura?timestamp=' + timestamp, {
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.ready && data.file_path) {
            // PDF está listo, descargar
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '/api/supervisor-pedidos/descargar-reporte-costura';
            form.innerHTML = `
                <input type="hidden" name="_token" value="${document.querySelector('meta[name="csrf-token"]').getAttribute('content')}">
                <input type="hidden" name="file_path" value="${data.file_path}">
            `;
            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);

            // Cerrar modal
            const modal = document.getElementById('modalGenerandoPDF');
            if (modal) modal.remove();
        } else if (intento < maxIntentos) {
            // Reintentar en 2 segundos
            setTimeout(() => {
                window.verificarYDescargarPDF(timestamp, intento + 1);
            }, 2000);
        } else {
            // Timeout
            const modal = document.getElementById('modalGenerandoPDF');
            if (modal) modal.remove();
            alert('Timeout: El PDF tardó demasiado en generarse. Por favor intenta de nuevo.');
        }
    })
    .catch(error => {
        console.error('Error al verificar PDF:', error);
    });
};

// Cerrar modal al hacer clic en el overlay
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('modalGenerarReporte');
        if (modal) {
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    window.cerrarModalGenerarReporte();
                }
            });
        }
    });
} else {
    const modal = document.getElementById('modalGenerarReporte');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                window.cerrarModalGenerarReporte();
            }
        });
    }
}
</script>
@endpush

@endsection
