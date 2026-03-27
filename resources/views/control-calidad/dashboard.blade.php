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
    function formatearConsecutivoVisible($valor) {
        $texto = trim((string) $valor);
        if ($texto === '') {
            return '';
        }

        if (is_numeric($texto)) {
            $texto = rtrim(rtrim(number_format((float) $texto, 2, '.', ''), '0'), '.');
        }

        return $texto;
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
                        $consecutivoActual = formatearConsecutivoVisible($recibo['consecutivo_actual'] ?? null);
                        $reciboCompletadoArea = (bool) ($recibo['completado_area'] ?? false);
                        $esReflectivo = strtoupper(trim((string) $tipoRecibo)) === 'REFLECTIVO';
                        $esParcial = (bool) ($recibo['es_parcial'] ?? false);
                    @endphp
                    <div @class(['orden-card-simple', 'borde-reflectivo' => $esReflectivo])
                         data-card-key="{{ $esParcial ? ('parcial-' . ($recibo['parcial_id'] ?? $recibo['id'] ?? '')) : ('recibo-' . ($recibo['id'] ?? '')) }}"
                         data-numero="{{ $prenda['numero_pedido'] }}"
                         data-prenda="{{ strtolower($prenda['nombre_prenda']) }}"
                         data-cliente="{{ strtolower($prenda['cliente']) }}"
                         data-tipo-recibo="{{ strtoupper($tipoRecibo) }}"
                         data-es-parcial="{{ $esParcial ? '1' : '0' }}"
                         data-parcial-id="{{ $recibo['parcial_id'] ?? '' }}"
                         data-numero-recibo="{{ $recibo['id'] ?? '' }}">

                        <div class="orden-border {{ $estadoClass }}"></div>

                        <div class="orden-body {{ $reciboCompletadoArea ? 'recibo-completado-area' : '' }}">
                            <div class="orden-left">
                                <div class="orden-top">
                                    <div class="orden-numero-section">
                                <h4 class="orden-numero">#{{ $consecutivoActual !== '' ? $consecutivoActual : $prenda['numero_pedido'] }}</h4>
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

                                @if(!empty($prenda['proceso_actual']))
                                    <div class="orden-cliente">
                                        <p class="cliente-label">ÁREA ACTUAL</p>
                                        <p class="cliente-name">{{ $prenda['proceso_actual'] }}</p>
                                    </div>
                                @endif

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
                                        href="{{ route('control-calidad.ver-pedido', $prenda['numero_pedido']) }}?tipo_recibo={{ urlencode($tipoRecibo) }}&prenda_id={{ $prenda['prenda_id'] }}">
                                        <span class="material-symbols-rounded">receipt</span>
                                        VER
                                    </a>
                                </div>
                            </div>

                            @if($recibo)
                                <div class="orden-right-actions">
                                    <button class="btn-completar-recibo"
                                            data-recibo-id="{{ $recibo['id'] ?? '' }}"
                                            data-es-parcial="{{ $esParcial ? '1' : '0' }}"
                                            data-parcial-id="{{ $recibo['parcial_id'] ?? '' }}"
                                            data-completado="{{ $reciboCompletadoArea ? '1' : '0' }}"
                                            onclick="toggleCompletarRecibo(this); event.stopPropagation();">
                                        <span class="material-symbols-rounded">done</span>
                                        {{ $reciboCompletadoArea ? 'COMPLETADO' : 'COMPLETAR' }}
                                    </button>
                                    <button class="btn-deshacer-recibo"
                                            data-recibo-id="{{ $recibo['id'] ?? '' }}"
                                            data-es-parcial="{{ $esParcial ? '1' : '0' }}"
                                            data-parcial-id="{{ $recibo['parcial_id'] ?? '' }}"
                                            style="{{ $reciboCompletadoArea ? '' : 'display: none;' }}"
                                            onclick="deshacerCompletarRecibo(this); event.stopPropagation();">
                                        <span class="material-symbols-rounded">undo</span>
                                        DESHACER
                                    </button>
                                </div>
                            @endif

                            <div class="orden-right">
                                <div class="orden-fecha">
                                    <span class="orden-fecha-label">PEDIDO</span>
                                    <span>#{{ $prenda['numero_pedido'] }}</span>
                                </div>
                                <div class="orden-fecha">
                                    <span class="orden-fecha-label">REGISTRO</span>
                                    <span>{{ $prenda['fecha_creacion']->format('d/m/Y') }}</span>
                                </div>
                                <a href="{{ route('control-calidad.ver-pedido', $prenda['numero_pedido']) }}?tipo_recibo={{ urlencode($tipoRecibo) }}&prenda_id={{ $prenda['prenda_id'] }}" class="action-arrow">
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
        border-left: 4px solid transparent;
        border-radius: 6px;
        overflow: hidden;
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .orden-card-simple:hover {
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    }

    .orden-card-simple.borde-reflectivo {
        border-left-color: #10b981;
    }

    .orden-border {
        display: none;
    }

    .recibo-completado-area {
        background: #E3F2FD;
    }

    .orden-body {
        position: relative;
        padding-bottom: 56px;
    }

    .orden-right {
        padding-bottom: 44px;
    }

    .orden-right-actions {
        position: absolute;
        right: 12px;
        bottom: 10px;
        display: flex;
        gap: 0.45rem;
        justify-content: flex-end;
        flex-wrap: nowrap;
    }

    .btn-completar-recibo {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        border: none;
        border-radius: 10px;
        padding: 0.45rem 0.75rem;
        font-size: 0.66rem;
        font-weight: 700;
        letter-spacing: 0.4px;
        cursor: pointer;
        background: #EEF2F7;
        color: #334155;
        transition: all 0.2s ease;
    }

    .btn-completar-recibo[data-completado="1"] {
        background: #BBDEFB;
        color: #0F172A;
    }

    .btn-deshacer-recibo {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        border: none;
        border-radius: 10px;
        padding: 0.45rem 0.75rem;
        font-size: 0.66rem;
        font-weight: 700;
        letter-spacing: 0.4px;
        cursor: pointer;
        background: #E2E8F0;
        color: #0F172A;
        transition: all 0.2s ease;
    }

    .btn-completar-recibo .material-symbols-rounded,
    .btn-deshacer-recibo .material-symbols-rounded {
        font-size: 16px;
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
        top: 8px;
        right: 12px;
        bottom: auto;
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
        const tagsContainer = document.getElementById('reciboTags');
        const countEl = document.querySelector('.ordenes-count');
        const getOrdenCards = () => ordenesList ? ordenesList.querySelectorAll('.orden-card-simple') : [];

        let activeFilter = 'ALL';

        function formatearConsecutivoVisibleJS(valor) {
            const texto = String(valor ?? '').trim();
            if (!texto) return '';

            if (/^-?\d+(\.\d+)?$/.test(texto)) {
                const numero = Number(texto);
                if (Number.isFinite(numero)) {
                    return String(numero).includes('.')
                        ? String(numero).replace(/\.0+$/, '').replace(/(\.\d*?)0+$/, '$1')
                        : String(numero);
                }
            }

            return texto;
        }

        function aplicarFiltros() {
            const busqueda = (searchInput?.value || '').toLowerCase().trim();
            let visibles = 0;

            getOrdenCards().forEach(card => {
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

        function crearCardControlCalidadDesdeEvento(orden, permitirReemplazo = false) {
            if (!ordenesList || !orden) return null;

            const recibo = Array.isArray(orden.recibos) ? orden.recibos[0] : (orden.recibo || null);
            if (!recibo) return null;

            const tipoRecibo = String(recibo.tipo_recibo || orden.tipo_recibo || '').toUpperCase();
            const esReflectivo = tipoRecibo === 'REFLECTIVO';
            const esParcial = String(recibo.es_parcial || orden.es_parcial || '0') === '1' || Boolean(recibo.es_parcial || orden.es_parcial);
            const completadoArea = Boolean(recibo.completado_area || orden.completado_area);
            const consecutivoActual = formatearConsecutivoVisibleJS(recibo.consecutivo_actual ?? orden.consecutivo_actual ?? '');
            const numeroPedido = orden.numero_pedido || orden.pedido || '';
            const prendaId = orden.prenda_id || recibo.prenda_id || '';
            const parcialId = orden.parcial_id || recibo.parcial_id || '';
            const areaActual = orden.proceso_actual || recibo.area || 'Control Calidad';
            const cardKey = esParcial ? `parcial-${parcialId}` : `recibo-${recibo.id || orden.id || ''}`;

            if (!permitirReemplazo && cardKey && document.querySelector(`[data-card-key="${cardKey}"]`)) {
                return null;
            }

            const card = document.createElement('div');
            card.className = `orden-card-simple${esReflectivo ? ' borde-reflectivo' : ''}`;
            card.dataset.cardKey = cardKey;
            card.dataset.numero = String(numeroPedido).toLowerCase();
            card.dataset.prenda = String(orden.nombre_prenda || '').toLowerCase();
            card.dataset.cliente = String(orden.cliente || '').toLowerCase();
            card.dataset.tipoRecibo = tipoRecibo;
            card.dataset.esParcial = esParcial ? '1' : '0';
            card.dataset.parcialId = String(parcialId || '');
            card.dataset.numeroRecibo = String(recibo.id || orden.id || '');

            card.innerHTML = `
                <div class="orden-border ${esParcial ? 'en-proceso' : ''}"></div>
                <div class="orden-body ${completadoArea ? 'recibo-completado-area' : ''}">
                    <div class="orden-left">
                        <div class="orden-top">
                            <div class="orden-numero-section">
                                <h4 class="orden-numero">#${consecutivoActual || numeroPedido || ''}</h4>
                                <span class="estado-badge ${String(areaActual).toLowerCase().includes('progreso') ? 'en-proceso' : (completadoArea ? 'completada' : 'pendiente')}">${tipoRecibo || 'RECIBO'}</span>
                            </div>
                        </div>

                        <div class="orden-cliente">
                            <p class="cliente-label">CLIENTE</p>
                            <p class="cliente-name">${orden.cliente || ''}</p>
                        </div>

                        <div class="orden-prendas">
                            <p class="prendas-label"><strong>${orden.nombre_prenda || 'Pedido'}</strong>${orden.descripcion ? ` — ${orden.descripcion}` : ''}</p>
                        </div>

                        ${areaActual ? `
                            <div class="orden-cliente">
                                <p class="cliente-label">ÁREA ACTUAL</p>
                                <p class="cliente-name">${areaActual}</p>
                            </div>
                        ` : ''}

                        <div class="recibos-info">
                            <div class="recibos-lista">
                                <span class="recibo-badge recibo-${tipoRecibo.toLowerCase().replace(/-/g, '_')}">${tipoRecibo}</span>
                            </div>
                        </div>

                        <div class="orden-buttons">
                            <a class="btn-ver-recibos" href="/control-calidad/pedido/${numeroPedido}?tipo_recibo=${encodeURIComponent(tipoRecibo)}&prenda_id=${prendaId}">
                                <span class="material-symbols-rounded">receipt</span>
                                VER
                            </a>
                        </div>
                    </div>

                    <div class="orden-right-actions">
                        <button class="btn-completar-recibo" data-recibo-id="${recibo.id || orden.id || ''}" data-es-parcial="${esParcial ? '1' : '0'}" data-parcial-id="${parcialId}" data-completado="${completadoArea ? '1' : '0'}" onclick="toggleCompletarRecibo(this); event.stopPropagation();">
                            <span class="material-symbols-rounded">done</span>
                            ${completadoArea ? 'COMPLETADO' : 'COMPLETAR'}
                        </button>
                        <button class="btn-deshacer-recibo" data-recibo-id="${recibo.id || orden.id || ''}" data-es-parcial="${esParcial ? '1' : '0'}" data-parcial-id="${parcialId}" style="${completadoArea ? '' : 'display: none;'}" onclick="deshacerCompletarRecibo(this); event.stopPropagation();">
                            <span class="material-symbols-rounded">undo</span>
                            DESHACER
                        </button>
                    </div>

                    <div class="orden-right">
                        <div class="orden-fecha">
                            <span class="orden-fecha-label">PEDIDO</span>
                            <span>#${numeroPedido}</span>
                        </div>
                        <div class="orden-fecha">
                            <span class="orden-fecha-label">REGISTRO</span>
                            <span>${orden.fecha_creacion ? new Date(orden.fecha_creacion).toLocaleDateString('es-CO') : ''}</span>
                        </div>
                        <a href="/control-calidad/pedido/${numeroPedido}?tipo_recibo=${encodeURIComponent(tipoRecibo)}&prenda_id=${prendaId}" class="action-arrow">
                            <span class="material-symbols-rounded">arrow_forward</span>
                        </a>
                        <div class="orden-pedido-footer">
                            <small>PEDIDO #${numeroPedido}</small>
                        </div>
                    </div>
                </div>
            `;

            return card;
        }

        function insertarOActualizarCardControlCalidad(orden, accion = 'added') {
            if (!ordenesList || !orden) return;

            const tempCard = crearCardControlCalidadDesdeEvento(orden, true);
            if (!tempCard) return;

            const existente = tempCard.dataset.cardKey ? document.querySelector(`[data-card-key="${tempCard.dataset.cardKey}"]`) : null;

            if (accion === 'added' || accion === 'updated') {
                if (existente) {
                    existente.remove();
                }
                const card = crearCardControlCalidadDesdeEvento(orden, true);
                if (!card) return;
                ordenesList.prepend(card);
            } else if (accion === 'removed') {
                if (existente) existente.remove();
            }

            aplicarFiltros();
        }

        if (window.Echo) {
            window.Echo.channel('control-calidad')
                .listen('ControlCalidadUpdated', (e) => {
                    console.log('[Control Calidad] Evento realtime:', e);
                    if (e?.tipo !== 'parcial') {
                        return;
                    }

                    if (e?.action === 'added' || e?.action === 'removed' || e?.action === 'updated') {
                        insertarOActualizarCardControlCalidad(e.orden || e, e.action);
                    }
                });
        }
    });

    function abrirReciboControlCalidad(pedidoId, prendaId) {
        if (typeof window.abrirModalRecibo !== 'function') {
            return;
        }
        window.abrirModalRecibo(pedidoId, prendaId, 'control calidad');
    }

    window.toggleCompletarRecibo = async function(btn) {
        const reciboId = btn.dataset.reciboId;
        if (!reciboId) {
            return;
        }

        const yaCompletado = btn.dataset.completado === '1';
        const esParcial = btn.dataset.esParcial === '1';
        const parcialId = btn.dataset.parcialId || reciboId;
        if (yaCompletado) {
            return;
        }

        try {
            const response = await fetch(`/control-calidad/api/recibos/${reciboId}/completar`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    es_parcial: esParcial,
                    parcial_id: esParcial ? parcialId : null,
                })
            });

            const data = await response.json();
            if (!data.success) {
                return;
            }

            btn.dataset.completado = '1';
            btn.setAttribute('data-completado', '1');
            btn.innerHTML = '<span class="material-symbols-rounded">done</span>COMPLETADO';

            const body = btn.closest('.orden-body');
            if (body) {
                body.classList.add('recibo-completado-area');
            }

            const btnDeshacer = btn.parentElement?.querySelector('.btn-deshacer-recibo');
            if (btnDeshacer) {
                btnDeshacer.style.display = '';
            }
        } catch (error) {
            return;
        }
    };

    window.deshacerCompletarRecibo = async function(btn) {
        const reciboId = btn.dataset.reciboId;
        if (!reciboId) {
            return;
        }
        const esParcial = btn.dataset.esParcial === '1';
        const parcialId = btn.dataset.parcialId || reciboId;

        try {
            const response = await fetch(`/control-calidad/api/recibos/${reciboId}/deshacer`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    es_parcial: esParcial,
                    parcial_id: esParcial ? parcialId : null,
                })
            });

            const data = await response.json();
            if (!data.success) {
                return;
            }

            const container = btn.parentElement;
            const btnCompletar = container?.querySelector('.btn-completar-recibo');
            if (btnCompletar) {
                btnCompletar.dataset.completado = '0';
                btnCompletar.setAttribute('data-completado', '0');
                btnCompletar.innerHTML = '<span class="material-symbols-rounded">done</span>COMPLETAR';
            }

            const body = btn.closest('.orden-body');
            if (body) {
                body.classList.remove('recibo-completado-area');
            }

            btn.style.display = 'none';
        } catch (error) {
            return;
        }
    };
</script>
@endpush
@endsection
