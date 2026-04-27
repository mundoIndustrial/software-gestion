@extends('supervisor-pedidos.layout')

@section('title', 'Entregas y Recibidas')
@section('page-title', 'Entregas y Recibidas')

@push('styles')
<style>
    .er-wrapper {
        padding: 1rem;
    }

    .er-muted {
        color: #94a3b8;
    }

    .er-chip {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        padding: 0.2rem 0.55rem;
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.01em;
    }

    .er-chip-ok {
        background: #dcfce7;
        color: #166534;
    }

    .er-chip-pending {
        background: #fef3c7;
        color: #92400e;
    }

    .er-chip-empty {
        background: #e2e8f0;
        color: #475569;
    }

    .er-table-frame {
        background: #e5e7eb;
        border-radius: 8px;
        overflow: visible;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        padding: 0.75rem;
        width: 100%;
        max-width: 100%;
    }

    .table-scroll-container {
        overflow-x: auto;
        overflow-y: auto;
        width: 100%;
        max-width: 100%;
        max-height: 800px;
        border-radius: 6px;
        scrollbar-width: thin;
        scrollbar-color: #cbd5e1 #f1f5f9;
    }

    .er-header {
        background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%);
        color: white;
        padding: 0.75rem 1rem;
        display: grid;
        grid-template-columns: 80px 120px 110px 170px 250px 1fr;
        gap: 0.15rem;
        font-weight: 600;
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        min-width: min-content;
        border-radius: 6px;
    }

    .th-wrapper {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    [data-row="registro"] {
        background: var(--row-bg-color, #ffffff) !important;
        opacity: 1;
        transition: background 0.2s ease, opacity 0.2s ease;
    }

    [data-row="registro"]:hover,
    [data-row="registro"]:focus-within {
        background: #f9fafb !important;
    }

    [data-row="registro"][data-color-stored]:not([data-color-stored=""]):hover,
    [data-row="registro"][data-color-stored]:not([data-color-stored=""]):focus-within {
        background: var(--row-bg-color, #ffffff) !important;
        opacity: 0.9;
    }

    .er-row {
        display: grid;
        grid-template-columns: 80px 120px 110px 170px 250px 1fr;
        gap: 0.15rem;
        padding: 1rem;
        border-bottom: 1px solid #e5e7eb;
        align-items: start;
        min-width: min-content;
        transition: background 0.2s ease;
        font-size: 0.9rem;
        color: #374151;
    }

    .er-pagination {
        padding: 0.85rem 0.25rem 0.25rem;
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    .er-pagination .pagination {
        margin-bottom: 0;
        gap: 0.25rem;
    }

    .er-pagination .page-link {
        border-radius: 8px;
        border: 1px solid #e2e8f0;
        color: #334155;
        font-size: 0.82rem;
        font-weight: 600;
        padding: 0.4rem 0.7rem;
        line-height: 1.1;
    }

    .er-pagination .page-item.active .page-link {
        background-color: #2563eb;
        border-color: #2563eb;
        color: #fff;
    }

    .er-pagination .page-item.disabled .page-link {
        color: #94a3b8;
        background: #f8fafc;
        border-color: #e2e8f0;
    }

    @media (max-width: 768px) {
        .table-scroll-container {
            max-height: 65vh !important;
        }

        .er-pagination {
            justify-content: center !important;
        }
    }
</style>
@endpush

@section('content')
<div class="supervisor-pedidos-container er-wrapper">
    <div class="er-table-frame">
        <div class="table-scroll-container">
            <!-- Header Azul -->
            <div class="er-header">
                <div class="th-wrapper">
                    <span>Acciones</span>
                </div>
                <div class="th-wrapper">
                    <span>Recibo</span>
                </div>
                <div class="th-wrapper">
                    <span>Pedido</span>
                </div>
                <div class="th-wrapper">
                    <span>Cliente</span>
                </div>
                <div class="th-wrapper">
                    <span>Prenda</span>
                </div>
                <div class="th-wrapper">
                    <span>Detalles de Entrega</span>
                </div>
            </div>

            <div id="entregasRecibidas">
                @forelse($registros as $fila)
                    <div data-row="registro" style="
                        --row-bg-color: #ffffff;
                        display: grid;
                        grid-template-columns: 80px 120px 110px 170px 250px 1fr;
                        gap: 0.15rem;
                        padding: 1rem;
                        border-bottom: 1px solid #e5e7eb;
                        align-items: start;
                        min-width: min-content;
                    ">
                        <!-- Acciones -->
                        <div style="display: flex; align-items: center; justify-content: center;">
                            <button
                                type="button"
                                data-pedido-id="{{ $fila->pedido_id }}"
                                data-prenda-id="{{ $fila->prenda_id }}"
                                data-numero-recibo="{{ $fila->numero_recibo }}"
                                data-es-parcial="false"
                                data-pedido-parcial-id=""
                                onclick="event.stopPropagation(); openReciboCosturaModalFromRow(this)"
                                style="display:inline-flex;align-items:center;justify-content:center;padding:6px 12px;background:#1d4ed8;color:#fff;border-radius:8px;font-size:0.8rem;font-weight:600;text-decoration:none;border:none;cursor:pointer;"
                            >
                                Ver
                            </button>
                        </div>

                        <!-- Recibo -->
                        <div style="display: flex; align-items: center; font-size: 0.9rem; color: #374151; font-weight: 500;">
                            {{ $fila->numero_recibo ? ('#' . $fila->numero_recibo) : '-' }}
                        </div>

                        <!-- Pedido -->
                        <div style="display: flex; align-items: center; font-size: 0.9rem; color: #374151;">
                            <strong>#{{ $fila->numero_pedido ?? '-' }}</strong>
                        </div>

                        <!-- Cliente -->
                        <div style="display: flex; align-items: center; font-size: 0.9rem; color: #374151;">
                            {{ $fila->cliente ?? '-' }}
                        </div>

                        <!-- Prenda -->
                        <div style="display: flex; align-items: start; font-size: 0.9rem; color: #374151;">
                            <div style="width: 100%;">
                                <div style="font-weight: 600; margin-bottom: 0.5rem;">{{ $fila->nombre_prenda ?? '-' }}</div>
                                @if(!empty($fila->descripcion))
                                    <div class="er-muted" style="font-size: 0.75rem; margin-bottom: 0.5rem; line-height: 1.3;">
                                        {{ \Illuminate\Support\Str::limit($fila->descripcion, 150, '...') }}
                                    </div>
                                @endif
                                @if(!empty($fila->tallas_por_color) && is_array($fila->tallas_por_color) && count($fila->tallas_por_color) > 0)
                                    <div style="margin-top: 0.5rem; border-top: 1px solid #e5e7eb; padding-top: 0.5rem;">
                                        @foreach($fila->tallas_por_color as $color => $tallas)
                                            <div style="margin-bottom: 0.4rem;">
                                                <div style="font-weight: 600; font-size: 0.8rem; color: #475569;">{{ $color }}:</div>
                                                <div style="font-size: 0.75rem; margin-left: 0.5rem; color: #64748b;">
                                                    @foreach($tallas as $talla)
                                                        {{ $talla['talla'] }}-{{ $talla['cantidad'] }}{{ !$loop->last ? ', ' : '' }}
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @elseif(!empty($fila->tallas_texto))
                                    <div class="er-muted" style="font-size: 0.76rem; margin-top: 0.5rem;">
                                        {{ $fila->tallas_texto }}
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Detalles de Entrega -->
                        <div style="display: flex; flex-direction: column; gap: 0.5rem; font-size: 0.85rem; color: #374151;">
                            <div>
                                <div style="font-weight: 600; color: #64748b; font-size: 0.75rem; text-transform: uppercase; margin-bottom: 0.25rem;">Entrega</div>
                                <div>
                                    @php
                                        $fechaEntrega = $fila->fecha_entrega_movimiento ?? $fila->fecha_entrega;
                                    @endphp
                                    <strong>{{ $fechaEntrega ? \Carbon\Carbon::parse($fechaEntrega)->format('d/m/Y h:i A') : '-' }}</strong>
                                </div>
                                <div class="er-muted">{{ $fila->usuario_entrega ?? '-' }}</div>
                            </div>

                            <div style="border-top: 1px solid #e5e7eb; padding-top: 0.5rem;">
                                <div style="font-weight: 600; color: #64748b; font-size: 0.75rem; text-transform: uppercase; margin-bottom: 0.25rem;">Recibido</div>
                                <div>
                                    <strong>{{ $fila->fecha_recibido ? \Carbon\Carbon::parse($fila->fecha_recibido)->format('d/m/Y h:i A') : '-' }}</strong>
                                </div>
                                <div class="er-muted">{{ $fila->usuario_recibido ?? '-' }}</div>
                            </div>

                            <div style="border-top: 1px solid #e5e7eb; padding-top: 0.5rem;">
                                @if(($fila->estado_recibido ?? null) === 'recibido')
                                    <span class="er-chip er-chip-ok">Recibido</span>
                                @elseif(($fila->estado_recibido ?? null) === 'pendiente')
                                    <span class="er-chip er-chip-pending">Pendiente</span>
                                @else
                                    <span class="er-chip er-chip-empty">Sin recibir</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div style="padding: 3rem 2rem; text-align: center; color: #6b7280;">
                        <i class="fas fa-inbox" style="font-size: 3rem; color: #d1d5db; margin-bottom: 1rem; display: block;"></i>
                        <p style="font-size: 1rem; margin: 0;">No hay registros para mostrar.</p>
                    </div>
                @endforelse
            </div>
        </div>

        <div class="er-pagination">
            {{ $registros->links('pagination::bootstrap-4') }}
        </div>
    </div>
</div>

<!-- Modal overlay para recibos -->
<div id="modal-overlay" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.5); backdrop-filter: blur(4px); z-index: 9997; display: none; pointer-events: auto;" onclick="closeModalOverlay()"></div>
<div id="order-detail-modal-wrapper" style="width: 90%; max-width: 672px; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 9998; pointer-events: auto; display: none;">
    <x-orders-components.order-detail-modal />
</div>

<!-- Modal de Novedades -->
<x-modals.novedades-edit-modal />

@push('scripts')
<script src="{{ asset('js/supervisor-pedidos/shared/receipts-renderers.js') }}?v={{ filemtime(public_path('js/supervisor-pedidos/shared/receipts-renderers.js')) }}"></script>
<script type="module" src="{{ asset('js/modulos/pedidos-recibos/loader.js') }}?v={{ filemtime(public_path('js/modulos/pedidos-recibos/loader.js')) }}"></script>
<script src="{{ asset('js/recibos-novedades.js') }}?v={{ time() }}"></script>
<script>
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
        return;
    }

    const moduleReady = await esperarModuloRecibos();
    if (moduleReady) {
        if (esParcial && pedidoParcialId > 0 && typeof window.openOrderDetailModalWithParcial === 'function') {
            window.openOrderDetailModalWithParcial(pedidoParcialId, prendaId, 'COSTURA', pedidoId, 'COSTURA ANEXO');
            return;
        }

        window.pedidosRecibosModule.abrirRecibo(pedidoId, prendaId, 'costura');
        return;
    }

    console.error('[openReciboCosturaModalFromRow] El visor del recibo no está listo.');
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
</script>
@endpush

@endsection

