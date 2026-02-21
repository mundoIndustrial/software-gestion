@extends('control-calidad.layout')

@section('title', 'Mis Órdenes')
@section('page-title', '')

@php
    function getEstadoClass($estado) {
        $estado = strtolower(trim($estado));
        if (strpos($estado, 'ejecución') !== false || strpos($estado, 'proceso') !== false) {
            return 'en-proceso';
        }
        if (strpos($estado, 'completada') !== false || strpos($estado, 'completado') !== false) {
            return 'completada';
        }
        return 'pendiente';
    }
@endphp

@section('content')
<div class="operario-dashboard">
    <div class="search-section">
        <span class="material-symbols-rounded">search</span>
        <input type="text" id="searchInput" class="search-box" placeholder="Buscar por # Pedido, Prenda o Cliente...">
    </div>

    <div class="ordenes-section">
        <div class="section-title">
            <span class="material-symbols-rounded">fact_check</span>
            <h3>CONTROL DE CALIDAD</h3>
            <span class="ordenes-count">{{ count($prendasConRecibos ?? []) }}</span>
        </div>

        <div class="recibo-tags" id="reciboTags">
            <button type="button" class="recibo-tag active" data-filter="ALL">TODOS</button>
            <button type="button" class="recibo-tag" data-filter="COSTURA">RECIBO COSTURA</button>
            <button type="button" class="recibo-tag" data-filter="REFLECTIVO">RECIBO REFLECTIVO</button>
        </div>

        <div class="ordenes-list" id="ordenesList">
            @if(count($prendasConRecibos ?? []) > 0)
                @foreach($prendasConRecibos as $prenda)
                    @php
                        $estadoClass = getEstadoClass($prenda['estado_pedido'] ?? 'pendiente');
                        $recibo = $prenda['recibos'][0] ?? null;
                        $tipoRecibo = $recibo['tipo_recibo'] ?? '';
                        $consecutivoActual = $recibo['consecutivo_actual'] ?? null;
                    @endphp
                    <div class="orden-card-simple"
                         data-numero="{{ $prenda['numero_pedido'] }}"
                         data-prenda="{{ strtolower($prenda['nombre_prenda']) }}"
                         data-cliente="{{ strtolower($prenda['cliente']) }}"
                         data-tipo-recibo="{{ strtoupper($tipoRecibo) }}">

                        <div class="orden-border {{ $estadoClass }}"></div>

                        <div class="orden-body">
                            <div class="orden-left">
                                <div class="orden-top">
                                    <div class="orden-numero-section">
                                        <h4 class="orden-numero">#{{ $consecutivoActual ?: $prenda['numero_pedido'] }}</h4>
                                        <span class="estado-badge {{ $estadoClass }}">{{ $tipoRecibo ?: 'RECIBO' }}</span>
                                    </div>
                                </div>

                                <div class="orden-cliente">
                                    <p class="cliente-label">CLIENTE</p>
                                    <p class="cliente-name">{{ $prenda['cliente'] }}</p>
                                </div>

                                <div class="orden-prendas">
                                    <p class="prendas-label">
                                        <strong>{{ $prenda['nombre_prenda'] }}</strong>
                                        @if($prenda['descripcion'])
                                            — {{ $prenda['descripcion'] }}
                                        @endif
                                    </p>
                                </div>

                                <div class="recibos-info">
                                    <div class="recibos-lista">
                                        @if($tipoRecibo)
                                            <span class="recibo-badge recibo-{{ strtolower(str_replace('-', '_', $tipoRecibo)) }}">
                                                {{ $tipoRecibo }}
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                <div class="orden-buttons">
                                    <a
                                        class="btn-ver-recibos"
                                        href="{{ route('control-calidad.ver-pedido', $prenda['numero_pedido']) }}?tipo_recibo={{ urlencode($tipoRecibo) }}">
                                        <span class="material-symbols-rounded">receipt</span>
                                        VER
                                    </a>
                                </div>
                            </div>

                            <div class="orden-right">
                                <div class="orden-fecha">
                                    <span class="orden-fecha-label">PEDIDO</span>
                                    <span>#{{ $prenda['numero_pedido'] }}</span>
                                </div>
                                <div class="orden-fecha">
                                    <span class="orden-fecha-label">REGISTRO</span>
                                    <span>{{ $prenda['fecha_creacion']->format('d/m/Y') }}</span>
                                </div>
                                <a href="{{ route('control-calidad.ver-pedido', $prenda['numero_pedido']) }}?tipo_recibo={{ urlencode($tipoRecibo) }}" class="action-arrow">
                                    <span class="material-symbols-rounded">arrow_forward</span>
                                </a>
                                <div class="orden-pedido-footer">
                                    <small>PEDIDO #{{ $prenda['numero_pedido'] }}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            @else
                <div class="empty-state">
                    <span class="material-symbols-rounded">inbox</span>
                    <p>No hay pedidos en Control de Calidad</p>
                </div>
            @endif
        </div>
    </div>
</div>

<style>
    .operario-dashboard {
        padding: 1.5rem;
        max-width: 1000px;
        margin: 0 auto;
    }

    /* Búsqueda */
    .search-section {
        position: relative;
        margin-bottom: 1.5rem;
    }

    .search-box {
        width: 100%;
        padding: 0.6rem 1rem 0.6rem 2.5rem;
        border: 1px solid #ddd;
        border-radius: 24px;
        font-size: 0.85rem;
        background: #f9f9f9;
        transition: all 0.3s ease;
    }

    .search-box:focus {
        outline: none;
        background: white;
        border-color: #1976d2;
        box-shadow: 0 0 0 3px rgba(25, 118, 210, 0.1);
    }

    .search-section .material-symbols-rounded {
        position: absolute;
        left: 0.75rem;
        top: 50%;
        transform: translateY(-50%);
        color: #999;
        font-size: 18px;
        pointer-events: none;
    }

    /* Órdenes Section */
    .ordenes-section {
        background: white;
    }

    .section-title {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 1rem;
        padding-bottom: 0.75rem;
        border-bottom: 1px solid #eee;
    }

    .section-title .material-symbols-rounded {
        color: #333;
        font-size: 20px;
    }

    .section-title h3 {
        margin: 0;
        font-size: 0.95rem;
        font-weight: 700;
        color: #333;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .ordenes-count {
        margin-left: auto;
        background: transparent;
        color: #999;
        padding: 0;
        border-radius: 0;
        font-size: 0.85rem;
        font-weight: 600;
    }

    .recibo-tags {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
        margin: 0 0 1rem 0;
    }

    .recibo-tag {
        border: 1px solid #e0e0e0;
        background: #f9f9f9;
        color: #555;
        padding: 0.35rem 0.7rem;
        border-radius: 999px;
        font-size: 0.7rem;
        font-weight: 800;
        letter-spacing: 0.3px;
        text-transform: uppercase;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .recibo-tag:hover {
        background: #f0f0f0;
        border-color: #d0d0d0;
    }

    .recibo-tag.active {
        background: #E3F2FD;
        color: #1976D2;
        border-color: #1976D2;
        box-shadow: 0 2px 4px rgba(25, 118, 210, 0.15);
    }

    /* Órdenes List */
    .ordenes-list {
        display: grid;
        gap: 0.75rem;
    }

    .orden-card-simple {
        display: flex;
        background: white;
        border: 1px solid #eee;
        border-radius: 6px;
        overflow: hidden;
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .orden-card-simple:hover {
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        border-color: #ddd;
    }

    .orden-border {
        width: 4px;
        flex-shrink: 0;
    }

    .orden-border.en-proceso {
        background: #2196F3;
    }

    .orden-border.pendiente {
        background: #FFC107;
    }

    .orden-border.completada {
        background: #4CAF50;
    }

    .orden-body {
        flex: 1;
        padding: 0.9rem 1rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        position: relative;
    }

    .orden-left {
        flex: 1;
    }

    .orden-top {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin-bottom: 0.4rem;
    }

    .orden-numero-section {
        display: flex;
        align-items: center;
        gap: 0.6rem;
    }

    .orden-numero {
        margin: 0;
        font-size: 1rem;
        font-weight: 700;
        color: #333;
    }

    .estado-badge {
        display: inline-block;
        padding: 0.3rem 0.6rem;
        border-radius: 3px;
        font-size: 0.65rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.4px;
    }

    .estado-badge.en-proceso {
        background: #E3F2FD;
        color: #1976D2;
    }

    .estado-badge.pendiente {
        background: #FFF3E0;
        color: #F57C00;
    }

    .estado-badge.completada {
        background: #E8F5E9;
        color: #388E3C;
    }

    .orden-cliente {
        margin-bottom: 0;
    }

    .cliente-label {
        margin: 0;
        font-size: 0.65rem;
        color: #999;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.4px;
    }

    .cliente-name {
        margin: 0.15rem 0 0;
        font-size: 0.85rem;
        font-weight: 600;
        color: #333;
    }

    .orden-prendas {
        margin-bottom: 0;
    }

    .prendas-label {
        margin: 0;
        font-size: 0.75rem;
        color: #666;
        line-height: 1.3;
    }

    .orden-right {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-left: 1rem;
    }

    .orden-fecha {
        text-align: right;
        font-size: 0.75rem;
        color: #999;
        font-weight: 500;
        white-space: nowrap;
    }

    .orden-fecha-label {
        display: block;
        font-size: 0.65rem;
        color: #ccc;
        margin-bottom: 0.2rem;
    }

    .action-arrow {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 28px;
        height: 28px;
        border-radius: 50%;
        background: #f0f0f0;
        color: #999;
        text-decoration: none;
        transition: all 0.3s ease;
        flex-shrink: 0;
    }

    .action-arrow:hover {
        background: #1976d2;
        color: white;
        transform: translateX(2px);
    }

    .action-arrow .material-symbols-rounded {
        font-size: 16px;
    }

    .orden-pedido-footer {
        position: absolute;
        bottom: 8px;
        right: 12px;
        font-size: 0.65rem;
        color: #bbb;
        background: rgba(255, 255, 255, 0.7);
        padding: 2px 6px;
        border-radius: 3px;
        white-space: nowrap;
        font-weight: 500;
    }

    .orden-buttons {
        display: flex;
        gap: 0.5rem;
        margin-top: 0.6rem;
        flex-wrap: wrap;
    }

    .recibos-info {
        margin-top: 0.5rem;
        margin-bottom: 0.6rem;
    }

    .recibos-lista {
        display: flex;
        flex-wrap: wrap;
        gap: 0.4rem;
    }

    .recibo-badge {
        display: inline-block;
        padding: 0.3rem 0.6rem;
        border-radius: 3px;
        font-size: 0.65rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        white-space: nowrap;
    }

    .btn-ver-recibos {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.5rem 0.8rem;
        background: #E3F2FD;
        color: #1976D2;
        border: 1px solid #1976D2;
        border-radius: 4px;
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        cursor: pointer;
        transition: all 0.3s ease;
        width: fit-content;
        box-shadow: 0 2px 4px rgba(25, 118, 210, 0.15);
    }

    .btn-ver-recibos:hover {
        background: #BBDEFB;
        box-shadow: 0 4px 8px rgba(25, 118, 210, 0.25);
        transform: translateY(-1px);
    }

    .btn-ver-recibos .material-symbols-rounded {
        font-size: 14px;
    }

    .empty-state {
        text-align: center;
        padding: 2rem;
        color: #999;
    }

    .empty-state .material-symbols-rounded {
        font-size: 48px;
        margin-bottom: 0.75rem;
        opacity: 0.5;
    }

    .empty-state p {
        font-size: 0.9rem;
        margin: 0;
    }

    @media (max-width: 768px) {
        .operario-dashboard {
            padding: 1rem;
        }

        .orden-body {
            flex-direction: column;
            align-items: flex-start;
        }

        .orden-right {
            width: 100%;
            margin-left: 0;
            margin-top: 0.5rem;
            justify-content: space-between;
        }

        .orden-fecha {
            text-align: left;
        }
    }
</style>

@include('components.modals.recibo-dinamico-modal')

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('searchInput');
        const ordenesList = document.getElementById('ordenesList');
        const ordenCards = ordenesList ? ordenesList.querySelectorAll('.orden-card-simple') : [];
        const tagsContainer = document.getElementById('reciboTags');
        const countEl = document.querySelector('.ordenes-count');

        let activeFilter = 'ALL';

        function aplicarFiltros() {
            const busqueda = (searchInput?.value || '').toLowerCase().trim();
            let visibles = 0;

            ordenCards.forEach(card => {
                const numero = (card.dataset.numero || '').toLowerCase();
                const prenda = (card.dataset.prenda || '').toLowerCase();
                const cliente = (card.dataset.cliente || '').toLowerCase();
                const tipoRecibo = (card.dataset.tipoRecibo || '').toUpperCase();

                const matchBusqueda = (numero.includes(busqueda) || prenda.includes(busqueda) || cliente.includes(busqueda) || busqueda === '');
                const matchTipo = (activeFilter === 'ALL') || (tipoRecibo === activeFilter);

                if (matchBusqueda && matchTipo) {
                    card.style.display = '';
                    visibles++;
                } else {
                    card.style.display = 'none';
                }
            });

            if (countEl) {
                countEl.textContent = String(visibles);
            }
        }

        if (searchInput) {
            searchInput.addEventListener('input', function(e) {
                aplicarFiltros();
            });
        }

        if (tagsContainer) {
            tagsContainer.addEventListener('click', function(e) {
                const btn = e.target.closest('.recibo-tag');
                if (!btn) {
                    return;
                }

                const filter = (btn.dataset.filter || 'ALL').toUpperCase();
                activeFilter = filter;

                tagsContainer.querySelectorAll('.recibo-tag').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');

                aplicarFiltros();
            });
        }

        aplicarFiltros();
    });

    function abrirReciboControlCalidad(pedidoId, prendaId) {
        if (typeof window.abrirModalRecibo !== 'function') {
            return;
        }
        window.abrirModalRecibo(pedidoId, prendaId, 'control calidad');
    }
</script>
@endpush
@endsection
