@extends('control-calidad.layout')

@section('title', 'Mis Órdenes')
@section('page-title')
    <span id="dashboardPageTitle" style="display: inline-flex; align-items: center; gap: 0.6rem;">
        <span class="material-symbols-rounded" id="dashboardPageTitleIcon">fact_check</span>
        <span id="dashboardPageTitleText">CONTROL DE CALIDAD</span>
    </span>
@endsection

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
<div class="operario-dashboard is-modern-dashboard control-calidad-dashboard">
    <div class="search-section">
        <span class="material-symbols-rounded">search</span>
        <input type="text" id="searchInput" class="search-box" placeholder="Buscar por recibo, prenda o cliente...">
        <button id="clearFilterBtn" class="clear-filter-btn" title="Limpiar filtro" style="display: none;">
            <span class="material-symbols-rounded">close</span>
        </button>
    </div>

    <div class="ordenes-section">
        <div class="filtros-badges filtros-badges-principales recibo-tags" id="reciboTags">
            <button type="button" class="badge-filtro recibo-tag badge-filtro-active active" data-filter="COSTURA">Costura</button>
            <button type="button" class="badge-filtro recibo-tag" data-filter="REFLECTIVO">Reflectivo</button>
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
                        $urlVerRecibo = route('control-calidad.ver-pedido', $prenda['numero_pedido'])
                            . '?tipo_recibo=' . urlencode($esParcial ? 'PARCIAL' : $tipoRecibo)
                            . '&prenda_id=' . $prenda['prenda_id'];

                        if ($esParcial) {
                            $urlVerRecibo .= '&parcial_id=' . urlencode((string) ($recibo['parcial_id'] ?? ''))
                                . '&consecutivo_parcial=' . urlencode((string) ($recibo['consecutivo_parcial'] ?? $recibo['consecutivo_actual'] ?? ''));
                        }
                    @endphp
                    <div @class([
                        'orden-card-simple',
                        'borde-reflectivo' => $esReflectivo,
                        'recibo-completado-card' => $reciboCompletadoArea
                    ])
                         data-card-key="{{ $esParcial ? ('parcial-' . ($recibo['parcial_id'] ?? $recibo['id'] ?? '')) : ('recibo-' . ($recibo['id'] ?? '')) }}"
                         data-numero="{{ $prenda['numero_pedido'] }}"
                         data-prenda="{{ strtolower($prenda['nombre_prenda']) }}"
                         data-cliente="{{ strtolower($prenda['cliente']) }}"
                         data-tipo-recibo="{{ strtoupper($tipoRecibo) }}"
                         data-prenda-id="{{ $prenda['prenda_id'] ?? '' }}"
                         data-es-parcial="{{ $esParcial ? '1' : '0' }}"
                         data-parcial-id="{{ $recibo['parcial_id'] ?? '' }}"
                         data-numero-recibo="{{ $consecutivoActual !== '' ? $consecutivoActual : ($recibo['consecutivo_actual'] ?? '') }}">

                        <div class="orden-border {{ $estadoClass }}"></div>

                        <div class="orden-body {{ $reciboCompletadoArea ? 'recibo-completado-area' : '' }}">
                            <span class="recibo-completado-chip" style="{{ $reciboCompletadoArea ? '' : 'display: none;' }}">COMPLETADO</span>
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

                                <div class="orden-buttons">
                                    <a
                                        class="btn-ver-recibos"
                                        href="{{ $urlVerRecibo }}">
                                        <span class="material-symbols-rounded">receipt</span>
                                        VER
                                    </a>
                                    @if($prenda['tiene_parciales'] ?? false)
                                        <button class="btn-ver-distribucion"
                                                onclick="abrirDistribucionReciboCC(this, @js((string) $recibo['tipo_recibo']));"
                                                data-recibo-id="{{ $recibo['id'] ?? '' }}"
                                                data-prenda-id="{{ $prenda['prenda_id'] ?? '' }}"
                                                data-numero-recibo="{{ $prenda['numero_pedido'] ?? '' }}"
                                                data-tipo-recibo="{{ $recibo['tipo_recibo'] ?? '' }}">
                                            <span class="material-symbols-rounded">share</span>
                                            VER DISTRIBUCIÓN
                                        </button>
                                    @endif
                                    <button class="btn-agregar-novedad"
                                            onclick="abrirModalNovedad(@js((string) $prenda['numero_pedido']), {{ (int) $prenda['prenda_id'] }}, @js((string) $prenda['nombre_prenda']), @js((string) ($consecutivoActual !== '' ? $consecutivoActual : $prenda['numero_pedido'])))">
                                        <span class="material-symbols-rounded">comment</span>
                                        AGREGAR NOVEDAD
                                    </button>
                                    @if($recibo)
                                        @if(!($prenda['tiene_parciales'] ?? false))
                                        <button class="btn-completar-recibo"
                                                data-recibo-id="{{ $recibo['id'] ?? '' }}"
                                                data-es-parcial="{{ $esParcial ? '1' : '0' }}"
                                                data-parcial-id="{{ $recibo['parcial_id'] ?? '' }}"
                                                data-completado="{{ $reciboCompletadoArea ? '1' : '0' }}"
                                                style="{{ $reciboCompletadoArea ? 'display: none;' : '' }}"
                                                onclick="toggleCompletarRecibo(this); event.stopPropagation();">
                                            <span class="material-symbols-rounded">done</span>
                                            COMPLETAR
                                        </button>
                                        @endif
                                        <button class="btn-deshacer-recibo"
                                                data-recibo-id="{{ $recibo['id'] ?? '' }}"
                                                data-es-parcial="{{ $esParcial ? '1' : '0' }}"
                                                data-parcial-id="{{ $recibo['parcial_id'] ?? '' }}"
                                                style="{{ $reciboCompletadoArea ? '' : 'display: none;' }}"
                                                onclick="deshacerCompletarRecibo(this); event.stopPropagation();">
                                            <span class="material-symbols-rounded">undo</span>
                                            DESHACER
                                        </button>
                                    @endif
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
                                <a href="{{ $urlVerRecibo }}" class="action-arrow">
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

<div id="modalCompletarTallas" class="cc-modal-overlay" style="display: none;">
    <div class="cc-modal-card">
        <div class="cc-modal-header">
            <h3>Completar recibo por tallas</h3>
            <button type="button" class="cc-modal-close" onclick="cerrarModalCompletarTallas()">
                <span class="material-symbols-rounded">close</span>
            </button>
        </div>
        <div class="cc-modal-meta" id="ccModalMeta"></div>
        <div class="cc-modal-body">
            <div id="ccModalLoading" class="cc-modal-loading" style="display: none;">Cargando tallas...</div>
            <div id="ccModalError" class="cc-modal-error" style="display: none;"></div>
            <div id="ccModalTallasContainer"></div>
        </div>
        <div class="cc-modal-footer">
            <button type="button" class="cc-btn cc-btn-cancel" onclick="cerrarModalCompletarTallas()">Cancelar</button>
            <button type="button" class="cc-btn cc-btn-confirm" id="ccModalConfirmarBtn">Confirmar</button>
        </div>
    </div>
</div>

<style>
    .operario-dashboard {
        padding: 1.5rem;
        max-width: 1000px;
        margin: 0 auto;
    }

    .cc-modal-overlay {
        position: fixed;
        inset: 0;
        z-index: 9999;
        background: rgba(15, 23, 42, 0.45);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1rem;
    }

    .cc-modal-card {
        width: 100%;
        max-width: 620px;
        max-height: 85vh;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        background: #fff;
        border-radius: 22px;
        box-shadow: 0 18px 44px rgba(15, 23, 42, 0.26);
    }

    .cc-modal-header, .cc-modal-footer {
        padding: 1.05rem 1.25rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .cc-modal-footer {
        border-top: 1px solid #f1f5f9;
        gap: 0.75rem;
        justify-content: flex-end;
    }

    .cc-modal-header h3 {
        margin: 0;
        font-size: 1.08rem;
        font-weight: 700;
        color: #0f172a;
    }

    .cc-modal-close {
        border: none;
        background: #f3f4f6;
        cursor: pointer;
        color: #6b7280;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 34px;
        height: 34px;
        border-radius: 12px;
    }

    .cc-modal-meta {
        padding: 0 1.25rem 0.75rem;
        color: #475569;
        font-size: 0.82rem;
        font-weight: 600;
    }

    .cc-modal-body {
        overflow: auto;
        padding: 0 1.25rem 1rem;
    }

    .cc-modal-loading, .cc-modal-error {
        font-size: 0.9rem;
        margin-bottom: 0.75rem;
    }

    .cc-modal-error {
        color: #b91c1c;
    }

    .cc-tallas-table {
        width: 100%;
        border-collapse: collapse;
    }

    .cc-tallas-table th, .cc-tallas-table td {
        border-bottom: 1px solid #edf2f7;
        padding: 0.75rem 0.5rem;
        text-align: left;
        font-size: 0.86rem;
    }

    .cc-genero-row td {
        background: #fff;
        color: #94a3b8;
        font-size: 0.75rem;
        letter-spacing: 0.09em;
        text-transform: uppercase;
        font-weight: 700;
    }

    .cc-entrega-input {
        width: 82px;
        border: 1px solid #d6d3d1;
        border-radius: 12px;
        padding: 0.42rem 0.5rem;
        text-align: center;
        font-weight: 600;
        color: #1f2937;
        background: #fff;
    }

    .cc-entrega-wrap {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    .cc-disp-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 62px;
        padding: 0.3rem 0.45rem;
        border-radius: 10px;
        background: #f1f5f9;
        color: #475569;
        font-size: 0.75rem;
        font-weight: 700;
    }

    .cc-talla-check {
        width: 18px;
        height: 18px;
        accent-color: #2b2626;
    }

    .cc-talla-pill {
        display: inline-flex;
        min-width: 38px;
        justify-content: center;
        align-items: center;
        padding: 0.3rem 0.65rem;
        border-radius: 10px;
        background: #f3f4f6;
        font-weight: 700;
        color: #374151;
    }

    .cc-btn {
        border: none;
        border-radius: 14px;
        padding: 0.7rem 1.3rem;
        font-weight: 600;
        cursor: pointer;
        min-width: 116px;
    }

    .cc-btn-cancel {
        background: #f3f4f6;
        color: #334155;
    }

    .cc-btn-confirm {
        background: #262222;
        color: #fff;
    }

    @media (max-width: 640px) {
        .cc-modal-overlay {
            padding: 0.5rem;
        }

        .cc-modal-card {
            max-width: 100%;
            max-height: 92vh;
            border-radius: 16px;
        }

        .cc-modal-header, .cc-modal-footer {
            padding: 0.85rem 0.9rem;
        }

        .cc-modal-meta,
        .cc-modal-body {
            padding-left: 0.9rem;
            padding-right: 0.9rem;
        }

        .cc-tallas-table th, .cc-tallas-table td {
            padding: 0.62rem 0.32rem;
            font-size: 0.81rem;
        }

        .cc-modal-footer {
            display: grid;
            grid-template-columns: 1fr 1fr;
        }

        .cc-btn {
            width: 100%;
            min-width: 0;
        }
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

    .orden-card-simple.recibo-completado-card {
        background: #edf6ff;
        border-color: #bfdbfe;
        border-left-color: #2563eb;
    }

    .recibo-completado-chip {
        position: absolute;
        top: 10px;
        right: 12px;
        padding: 0.2rem 0.5rem;
        border-radius: 999px;
        background: #cfe8ff;
        color: #0f4c81;
        font-size: 0.58rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        z-index: 2;
        line-height: 1.2;
    }

    .orden-right-actions {
        display: none;
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
        top: auto;
        right: 12px;
        bottom: 10px;
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

    .btn-ver-distribucion {
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

    .btn-ver-distribucion:hover {
        background: #BBDEFB;
        box-shadow: 0 4px 8px rgba(25, 118, 210, 0.25);
        transform: translateY(-1px);
    }

    .btn-ver-distribucion .material-symbols-rounded {
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

    .control-calidad-dashboard {
        background: #f8faff;
        padding: 0 0.35rem 1rem;
    }

    .control-calidad-dashboard .search-section {
        max-width: none;
        margin: 0;
        padding: 0.5rem 0.1rem 0;
    }

    .control-calidad-dashboard .search-box {
        width: 100%;
        padding: 0.9rem 3rem 0.9rem 1rem;
        border: 1px solid #dbe2f3;
        border-radius: 999px;
        box-shadow: none;
        font-size: 0.95rem;
        background: #fff;
    }

    .control-calidad-dashboard .search-box:focus {
        transform: none;
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
    }

    .control-calidad-dashboard .search-section .material-symbols-rounded {
        left: auto;
        right: 1rem;
        color: #111827;
        font-size: 1.35rem;
    }

    .control-calidad-dashboard .clear-filter-btn {
        position: absolute;
        right: 2.9rem;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        color: #94a3b8;
        cursor: pointer;
        padding: 0.2rem;
        border-radius: 999px;
    }

    .control-calidad-dashboard .clear-filter-btn:hover {
        background: #eef2ff;
        color: #2563eb;
    }

    .control-calidad-dashboard .ordenes-section {
        background: transparent;
        max-width: none;
    }

    .control-calidad-dashboard .recibo-tags {
        background: #c9c9c9;
        border-radius: 999px;
        padding: 0.25rem;
        margin: 0.55rem 0 0.95rem;
        width: max-content;
        display: inline-flex;
        gap: 0.5rem;
        flex-wrap: nowrap;
        align-items: center;
        max-width: 100%;
    }

    .control-calidad-dashboard .recibo-tag {
        min-width: 0;
        justify-content: center;
        border: none;
        border-radius: 999px;
        padding: 0.42rem 1rem;
        font-size: 0.9rem;
        font-weight: 700;
        text-transform: lowercase;
        letter-spacing: 0;
        background: transparent;
        color: #6b7280;
        box-shadow: none;
        width: auto;
        flex: 0 0 auto;
        transition: background-color 0.28s ease, color 0.28s ease, box-shadow 0.28s ease, transform 0.28s ease;
    }

    .control-calidad-dashboard .recibo-tag.active,
    .control-calidad-dashboard .recibo-tag.badge-filtro-active {
        background: #2563eb;
        color: #fff;
        border-color: transparent;
        box-shadow: 0 8px 18px rgba(37, 99, 235, 0.28);
    }

    body[data-dashboard-theme="reflectivo"] .control-calidad-dashboard .recibo-tag.active,
    body[data-dashboard-theme="reflectivo"] .control-calidad-dashboard .recibo-tag.badge-filtro-active {
        background: #119669;
        box-shadow: 0 8px 18px rgba(17, 150, 105, 0.28);
    }

    .control-calidad-dashboard .ordenes-list {
        gap: 1rem;
    }

    .control-calidad-dashboard .orden-card-simple {
        border: 1px solid #dfe7f6;
        border-left: 4px solid #2563eb;
        border-radius: 24px;
        box-shadow: 0 10px 28px rgba(37, 99, 235, 0.08);
    }

    .control-calidad-dashboard .orden-card-simple.recibo-completado-card {
        background: #eaf4ff;
        border-color: #bfdbfe;
        border-left-color: #2563eb;
        box-shadow: 0 10px 28px rgba(37, 99, 235, 0.14);
    }

    .control-calidad-dashboard .orden-card-simple.borde-reflectivo.recibo-completado-card {
        border-left-color: #10b981;
    }

    /* Modal Novedades: estilos base (esta vista no depende de utilidades Tailwind) */
    #novedadesEditModal,
    #modalConfirmarEliminar {
        display: none !important;
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.55);
        z-index: 100001;
        align-items: center;
        justify-content: center;
        padding: 1rem;
    }

    #modalConfirmarEliminar {
        z-index: 100002;
        background: rgba(15, 23, 42, 0.62);
    }

    #novedadesEditModal.hidden,
    #modalConfirmarEliminar.hidden {
        display: none !important;
    }

    #novedadesEditModal.flex,
    #modalConfirmarEliminar.flex {
        display: flex !important;
    }

    #novedadesEditModal .bg-white,
    #modalConfirmarEliminar .bg-white {
        width: min(580px, 100%) !important;
        max-height: 60vh !important;
        height: auto !important;
        overflow: hidden !important;
        border-radius: 14px !important;
        background: #fff !important;
        box-shadow: 0 24px 50px rgba(15, 23, 42, 0.35) !important;
        display: flex !important;
        flex-direction: column !important;
    }

    #novedadesEditModal .bg-slate-900 {
        background: #111827;
        color: #fff;
        padding: 0.85rem 1rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-shrink: 0;
    }

    #novedadesEditModal .px-6.py-6 {
        padding: 0.85rem 1rem !important;
        overflow-y: auto !important;
        flex: 1 !important;
        display: flex !important;
        flex-direction: column !important;
    }

    #novedadesEditModal #novedadesHistorial {
        flex: 1 !important;
        overflow-y: auto !important;
        padding-right: 0.5rem !important;
        margin-bottom: 0.75rem !important;
        max-height: none !important;
        height: 100% !important;
    }
    
    #novedadesEditModal #novedadesHistorial::-webkit-scrollbar {
        width: 6px;
    }
    
    #novedadesEditModal #novedadesHistorial::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 3px;
    }
    
    #novedadesEditModal #novedadesHistorial::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 3px;
    }
    
    #novedadesEditModal #novedadesHistorial::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }

    #novedadesEditModal #novedadesNuevaContent {
        width: 100% !important;
        min-height: 75px !important;
        max-height: 95px !important;
        border: 1px solid #d1d5db !important;
        border-radius: 10px !important;
        padding: 0.6rem !important;
        font-size: 0.89rem !important;
        resize: vertical !important;
        flex-shrink: 0 !important;
    }

    #novedadesEditModal button[onclick="guardarNovedad()"],
    #novedadesEditModal button[onclick="cerrarModalNovedades()"] {
        border: none !important;
        border-radius: 10px !important;
        padding: 0.6rem 0.9rem !important;
        font-weight: 700 !important;
        cursor: pointer !important;
        font-size: 0.9rem !important;
    }

    #novedadesEditModal button[onclick="guardarNovedad()"] {
        background: #22c55e !important;
        color: #fff !important;
    }

    #novedadesEditModal button[onclick="cerrarModalNovedades()"] {
        background: #94a3b8 !important;
        color: #fff !important;
    }

    /* Contenedor de acciones (textarea + botones) - siempre visible */
    #novedadesEditModal .border-t {
        border-top: 1px solid #e5e7eb !important;
        flex-shrink: 0 !important;
        padding-top: 0.75rem !important;
    }

    /* Compatibilidad de utilidades usadas por recibos-novedades.js */
    #novedadesEditModal .mb-6 { margin-bottom: 0.6rem !important; }
    #novedadesEditModal .pt-6 { padding-top: 0.75rem !important; }
    #novedadesEditModal .mt-4 { margin-top: 0.65rem !important; }
    #novedadesEditModal .block { display: block !important; }
    #novedadesEditModal .text-sm { font-size: 0.85rem !important; }
    #novedadesEditModal .font-bold { font-weight: 700 !important; }
    #novedadesEditModal .mb-3 { margin-bottom: 0.5rem !important; }
    #novedadesEditModal .border-slate-200 { border-color: #e2e8f0 !important; }
    #novedadesEditModal .rounded-lg { border-radius: 10px !important; }
    #novedadesEditModal .transition { transition: all 0.2s ease !important; }
    #novedadesEditModal .flex { display: flex !important; }
    #novedadesEditModal .gap-3 { gap: 0.6rem; }
    #novedadesEditModal .flex-1 { flex: 1; }
    #novedadesEditModal .text-white { color: #fff; }

    #novedadesEditModal .bg-gray-50 {
        background: #f8fafc;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        padding: 0.8rem;
        margin-bottom: 0.7rem;
    }

    #novedadesEditModal .border-gray-200 { border-color: #e5e7eb; }
    #novedadesEditModal .text-gray-700 { color: #374151; }
    #novedadesEditModal .text-gray-500 { color: #6b7280; }
    #novedadesEditModal .text-gray-400 { color: #9ca3af; }
    #novedadesEditModal .text-orange-600 { color: #c2410c; }
    #novedadesEditModal .whitespace-pre-wrap { white-space: pre-wrap; }
    #novedadesEditModal .italic { font-style: italic; }

    #novedadesEditModal .bg-blue-100 { background: #dbeafe; }
    #novedadesEditModal .text-blue-800 { color: #1e40af; }
    #novedadesEditModal .bg-red-100 { background: #fee2e2; }
    #novedadesEditModal .text-red-800 { color: #991b1b; }
    #novedadesEditModal .bg-yellow-100 { background: #fef9c3; }
    #novedadesEditModal .text-yellow-800 { color: #854d0e; }
    #novedadesEditModal .bg-green-100 { background: #dcfce7; }
    #novedadesEditModal .text-green-800 { color: #166534; }
    #novedadesEditModal .bg-orange-100 { background: #ffedd5; }
    #novedadesEditModal .text-orange-800 { color: #9a3412; }

    #novedadesEditModal .bg-blue-500 { background: #3b82f6; }
    #novedadesEditModal .bg-blue-500:hover { background: #2563eb; }
    #novedadesEditModal .bg-red-500 { background: #ef4444; }
    #novedadesEditModal .bg-red-500:hover { background: #dc2626; }

    .control-calidad-dashboard .orden-card-simple.borde-reflectivo {
        border-left-color: #10b981;
    }

    .control-calidad-dashboard .orden-card-simple:hover {
        transform: translateY(-2px);
        box-shadow: 0 14px 32px rgba(15, 23, 42, 0.1);
    }

    .control-calidad-dashboard .orden-body {
        padding: 1.15rem 1.15rem 3.75rem;
        border-radius: 24px;
    }

    .control-calidad-dashboard .recibo-completado-chip {
        top: 12px;
        right: 14px;
        font-size: 0.56rem;
        padding: 0.18rem 0.48rem;
        background: #dbeafe;
        color: #1e3a8a;
    }

    .control-calidad-dashboard .orden-numero {
        font-size: 1.55rem;
        font-weight: 900;
        color: #0f172a;
    }

    .control-calidad-dashboard .estado-badge {
        border-radius: 999px;
        padding: 0.35rem 0.75rem;
        font-size: 0.68rem;
    }

    .control-calidad-dashboard .cliente-label {
        color: #2563eb;
        font-size: 0.62rem;
        letter-spacing: 0.1em;
    }

    body[data-dashboard-theme="reflectivo"] .control-calidad-dashboard .cliente-label {
        color: #119669;
    }

    .control-calidad-dashboard .cliente-name {
        font-size: 0.98rem;
        font-weight: 800;
        color: #0f172a;
    }

    .control-calidad-dashboard .prendas-label {
        color: #64748b;
        font-size: 0.9rem;
        line-height: 1.4;
    }

    .control-calidad-dashboard .orden-cliente + .orden-cliente,
    .control-calidad-dashboard .orden-prendas,
    .control-calidad-dashboard .recibos-info {
        margin-top: 0.8rem;
    }

    .control-calidad-dashboard .orden-left > .orden-prendas + .orden-cliente,
    .control-calidad-dashboard .recibos-info {
        display: none;
    }

    .control-calidad-dashboard .recibo-badge {
        border-radius: 999px;
        font-size: 0.66rem;
        padding: 0.34rem 0.72rem;
        background: #eef2ff;
        color: #2563eb;
    }

    body[data-dashboard-theme="reflectivo"] .control-calidad-dashboard .recibo-badge {
        background: #ecfdf5;
        color: #119669;
    }

    .control-calidad-dashboard .btn-ver-recibos {
        border-radius: 14px;
        padding: 0.62rem 0.95rem;
        font-size: 0.74rem;
        background: #fff;
        color: #2563eb;
        border: 1px solid #c9d8f8;
        box-shadow: 0 6px 14px rgba(37, 99, 235, 0.12);
    }

    .control-calidad-dashboard .btn-ver-recibos:hover {
        background: #eff6ff;
        box-shadow: 0 10px 18px rgba(37, 99, 235, 0.18);
    }

    .control-calidad-dashboard .btn-ver-distribucion {
        border-radius: 14px;
        padding: 0.62rem 0.95rem;
        font-size: 0.74rem;
        background: #fff;
        color: #2563eb;
        border: 1px solid #c9d8f8;
        box-shadow: 0 6px 14px rgba(37, 99, 235, 0.12);
    }

    .control-calidad-dashboard .btn-ver-distribucion:hover {
        background: #eff6ff;
        box-shadow: 0 10px 18px rgba(37, 99, 235, 0.18);
    }

    .control-calidad-dashboard .orden-buttons {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        width: 100%;
        align-items: center;
    }

    .control-calidad-dashboard .orden-buttons .btn-ver-recibos,
    .control-calidad-dashboard .orden-buttons .btn-agregar-novedad,
    .control-calidad-dashboard .orden-buttons .btn-completar-recibo,
    .control-calidad-dashboard .orden-buttons .btn-deshacer-recibo {
        width: auto;
        justify-content: center;
        margin: 0;
    }

    body[data-dashboard-theme="reflectivo"] .control-calidad-dashboard .btn-ver-recibos {
        color: #119669;
        border-color: #b7e5d2;
        box-shadow: 0 6px 14px rgba(17, 150, 105, 0.12);
    }

    body[data-dashboard-theme="reflectivo"] .control-calidad-dashboard .btn-ver-recibos:hover {
        background: #ecfdf5;
        box-shadow: 0 10px 18px rgba(17, 150, 105, 0.18);
    }

    body[data-dashboard-theme="reflectivo"] .control-calidad-dashboard .btn-ver-distribucion {
        color: #119669;
        border-color: #b7e5d2;
        box-shadow: 0 6px 14px rgba(17, 150, 105, 0.12);
    }

    body[data-dashboard-theme="reflectivo"] .control-calidad-dashboard .btn-ver-distribucion:hover {
        background: #ecfdf5;
        box-shadow: 0 10px 18px rgba(17, 150, 105, 0.18);
    }

    .control-calidad-dashboard .orden-right-actions {
        right: 16px;
        bottom: 14px;
        gap: 0.55rem;
    }

    .control-calidad-dashboard .btn-completar-recibo,
    .control-calidad-dashboard .btn-deshacer-recibo {
        border-radius: 14px;
        padding: 0.56rem 0.88rem;
        font-size: 0.68rem;
    }

    .control-calidad-dashboard .orden-right {
        gap: 1.1rem;
    }

    .control-calidad-dashboard .action-arrow {
        width: 34px;
        height: 34px;
        background: #eff6ff;
        color: #2563eb;
    }

    body[data-dashboard-theme="reflectivo"] .control-calidad-dashboard .action-arrow {
        background: #ecfdf5;
        color: #119669;
    }

    .control-calidad-dashboard .action-arrow:hover {
        background: #2563eb;
        color: #fff;
    }

    body[data-dashboard-theme="reflectivo"] .control-calidad-dashboard .action-arrow:hover {
        background: #119669;
    }

    @media (max-width: 768px) {
        .operario-dashboard {
            padding: 1rem;
        }

        .control-calidad-dashboard {
            padding: 0 0.15rem 1rem;
        }

        .control-calidad-dashboard .recibo-tags {
            gap: 0.35rem;
            width: auto;
            overflow-x: auto;
            flex-wrap: nowrap;
        }

        .control-calidad-dashboard .recibo-tag {
            min-width: max-content;
            font-size: 0.82rem;
            padding: 0.38rem 0.85rem;
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

        .control-calidad-dashboard .orden-card-simple {
            border-radius: 22px;
        }

        .control-calidad-dashboard .orden-body {
            padding: 1rem 1rem 4rem;
        }

        .control-calidad-dashboard .orden-buttons {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .control-calidad-dashboard .orden-buttons .btn-ver-recibos {
            order: 1;
            width: auto;
        }

        .control-calidad-dashboard .orden-buttons .btn-ver-distribucion {
            order: 2;
            width: auto;
        }

        .control-calidad-dashboard .orden-buttons .btn-agregar-novedad {
            order: 3;
            width: auto;
        }

        .control-calidad-dashboard .orden-buttons .btn-completar-recibo {
            order: 4;
            width: auto;
        }

        .control-calidad-dashboard .orden-buttons .btn-deshacer-recibo {
            order: 5;
            width: auto;
        }

        .control-calidad-dashboard .recibo-completado-chip {
            top: 10px;
            right: 10px;
        }

        .control-calidad-dashboard .orden-numero {
            font-size: 1.35rem;
        }

        /* Botones responsivos en distribución de parciales */
        .distribucion-parciales-cc-section .btn-ver-recibo-parcial,
        .distribucion-parciales-cc-section .btn-completar-parcial-cc {
            padding: 0.5rem 0.75rem;
            font-size: 0.7rem;
            gap: 0.35rem;
        }

        .distribucion-parciales-cc-section .btn-ver-recibo-parcial .material-symbols-rounded,
        .distribucion-parciales-cc-section .btn-completar-parcial-cc .material-symbols-rounded {
            font-size: 0.85rem;
        }
    }
</style>

@include('components.modals.recibo-dinamico-modal')

<!-- Modal de Confirmación -->
<div id="modalConfirmacion" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 10000; align-items: center; justify-content: center;">
    <div style="background: white; padding: 2rem; border-radius: 12px; max-width: 420px; width: 90%; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); animation: slideIn 0.3s ease;">
        <div style="display: flex; align-items: center; justify-content: center; width: 60px; height: 60px; border-radius: 50%; background: #fef3c7; margin: 0 auto 1rem; font-size: 2rem;">⚠️</div>
        <h3 style="margin: 0 0 0.75rem 0; font-size: 1.25rem; font-weight: 700; color: #111827; text-align: center;">¿Eliminar novedad?</h3>
        <p style="margin: 0 0 1.5rem 0; color: #6b7280; text-align: center; line-height: 1.5; font-size: 0.95rem;">Esta acción no se puede deshacer.</p>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
            <button id="btnConfirmarNo" onclick="cancelarConfirmacion()" style="padding: 0.75rem 1rem; background: #f3f4f6; color: #374151; border: none; border-radius: 10px; cursor: pointer; font-weight: 600; font-size: 0.95rem; transition: all 0.2s;">Cancelar</button>
            <button id="btnConfirmarSi" onclick="confirmarEliminar()" style="padding: 0.75rem 1rem; background: #ef4444; color: white; border: none; border-radius: 10px; cursor: pointer; font-weight: 600; font-size: 0.95rem; transition: all 0.2s;">Eliminar</button>
        </div>
    </div>
</div>

<!-- Modal de Novedades (estilo operario) -->
<div id="modalNovedad" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 12px; max-width: 760px; width: 92%; max-height: 85vh; overflow: hidden; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.25);">
        <div style="background: #111827; color: white; padding: 1rem 1.25rem; display: flex; align-items: center; justify-content: space-between;">
            <div id="modalNovedadHeaderTitulo" style="font-weight: 800; letter-spacing: 0.5px; font-size: 0.95rem; text-transform: uppercase;">NOVEDADES</div>
            <button type="button" onclick="cerrarModalNovedad()" style="background: transparent; border: none; color: white; cursor: pointer; font-size: 1.25rem; line-height: 1; padding: 0.25rem;">×</button>
        </div>

        <div style="padding: 1.25rem; overflow-y: auto; max-height: calc(85vh - 56px);">
            <input type="hidden" id="novedadNumeroPedido">
            <input type="hidden" id="novedadPrendaId">
            <input type="hidden" id="novedadNumeroRecibo">

            <div style="margin-bottom: 1rem;">
                <div style="color: #111827; font-weight: 700; font-size: 0.95rem; margin-bottom: 0.5rem;">Historial:</div>
                <div id="novedadesHistorial" style="max-height: 220px; overflow-y: auto; padding-right: 0.25rem;"></div>
            </div>

            <div style="height: 1px; background: #e5e7eb; margin: 1rem 0;"></div>

            <div style="color: #111827; font-weight: 800; font-size: 1rem; margin-bottom: 0.75rem;">Agregar Nueva Novedad:</div>

            <div style="margin-bottom: 1rem;">
                <textarea id="novedadDescripcionText" rows="5" style="width: 100%; padding: 0.9rem; border: 1px solid #d1d5db; border-radius: 10px; resize: vertical; font-size: 0.95rem;" placeholder="Escribe tu novedad aquí..."></textarea>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                <button type="button" id="btnGuardarNovedad" onclick="guardarNovedad()" style="padding: 0.85rem 1rem; background: #22c55e; color: white; border: none; border-radius: 10px; cursor: pointer; font-weight: 800;">Guardar Novedad</button>
                <button type="button" onclick="cerrarModalNovedad()" style="padding: 0.85rem 1rem; background: #94a3b8; color: white; border: none; border-radius: 10px; cursor: pointer; font-weight: 800;">Cancelar</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    window.usuarioActualId = {{ (int) Auth::id() }};
    window.novedadIdAEliminar = null;

    function escaparHtml(texto) {
        return String(texto ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function tokenCsrf() {
        return document.querySelector('meta[name="csrf-token"]')?.content || '';
    }

    function mostrarNovedadesTarjetas(novedades) {
        const historial = document.getElementById('novedadesHistorial');
        if (!historial) return;

        if (!Array.isArray(novedades) || novedades.length === 0) {
            historial.innerHTML = '<div style="padding: 1rem; border: 1px solid #e5e7eb; border-radius: 0.75rem; background: #f9fafb; color: #6b7280; font-size: 0.9rem;">No hay novedades registradas</div>';
            return;
        }

        historial.innerHTML = novedades.map((novedad) => {
            const fecha = escaparHtml(novedad.creado_en || '');
            const usuarioNombre = escaparHtml(novedad.creado_por_nombre || 'Sistema');
            const usuarioRol = escaparHtml(novedad.creado_por_rol || '');
            const tipoRaw = String(novedad.tipo_novedad || 'observacion');
            const tipo = escaparHtml(tipoRaw.toUpperCase());
            const descripcion = escaparHtml(novedad.novedad_texto || '');
            const esMia = String(novedad.creado_por ?? '') === String(window.usuarioActualId ?? '');
            const editado = Number(novedad.editado || 0) === 1;
            const fechaEdicion = escaparHtml(novedad.editado_en || '');
            const descripcionEsc = JSON.stringify(String(novedad.novedad_texto || ''));
            const tipoEsc = JSON.stringify(tipoRaw);

            return `
                <div style="padding: 1rem; border: 1px solid #e5e7eb; border-radius: 0.75rem; margin-bottom: 0.75rem; background: #f3f4f6;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem;">
                        <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                            <span style="background: #dbeafe; color: #1d4ed8; font-weight: 700; font-size: 0.7rem; padding: 0.25rem 0.6rem; border-radius: 0.5rem;">${tipo}</span>
                            ${editado ? '<span style="background: #fbbf24; color: #92400e; font-weight: 700; font-size: 0.7rem; padding: 0.25rem 0.6rem; border-radius: 0.5rem;">EDITADO</span>' : ''}
                            <span style="color: #6b7280; font-size: 0.85rem;">${usuarioNombre}</span>
                            ${usuarioRol ? `<span style="background: #e5e7eb; color: #374151; font-weight: 700; font-size: 0.7rem; padding: 0.25rem 0.6rem; border-radius: 0.5rem;">${usuarioRol}</span>` : ''}
                        </div>
                        <div style="color: #9ca3af; font-size: 0.8rem; white-space: nowrap;">${fecha}</div>
                    </div>
                    <div style="margin-top: 0.75rem; color: #374151; font-size: 0.95rem; line-height: 1.4; white-space: pre-wrap;">${descripcion}</div>
                    ${editado && fechaEdicion ? `<div style="margin-top: 0.5rem; color: #92400e; font-size: 0.75rem; font-style: italic;">Editado: ${fechaEdicion}</div>` : ''}
                    ${esMia ? `
                        <div style="margin-top: 0.75rem; display: flex; gap: 0.5rem;">
                            <button onclick='editarNovedad(${novedad.id}, ${descripcionEsc}, ${tipoEsc})' style="background: #3b82f6; color: white; border: none; border-radius: 0.375rem; padding: 0.35rem 0.8rem; cursor: pointer; font-weight: 600; font-size: 0.85rem;">Editar</button>
                            <button onclick="eliminarNovedad(${novedad.id})" style="background: #ef4444; color: white; border: none; border-radius: 0.375rem; padding: 0.35rem 0.8rem; cursor: pointer; font-weight: 600; font-size: 0.85rem;">Eliminar</button>
                        </div>
                    ` : ''}
                </div>
            `;
        }).join('');
    }

    async function cargarNovedades(numeroPedido, numeroRecibo) {
        const historial = document.getElementById('novedadesHistorial');
        if (historial) {
            historial.innerHTML = '<div style="padding: 1rem; border: 1px solid #e5e7eb; border-radius: 0.75rem; background: #f9fafb; color: #6b7280; font-size: 0.9rem;">Cargando novedades...</div>';
        }

        try {
            const res = await fetch(`/recibos-novedades/${encodeURIComponent(numeroPedido)}/${encodeURIComponent(numeroRecibo)}`);
            const data = await res.json();
            mostrarNovedadesTarjetas(data?.success ? (data.data || []) : []);
        } catch (_) {
            if (historial) {
                historial.innerHTML = '<div style="padding: 1rem; border: 1px solid #fee2e2; border-radius: 0.75rem; background: #fff1f2; color: #991b1b; font-size: 0.9rem;">Error cargando novedades</div>';
            }
        }
    }

    window.abrirModalNovedad = function(numeroPedido, prendaId, nombrePrenda, numeroRecibo) {
        const modal = document.getElementById('modalNovedad');
        if (!modal) return;

        document.getElementById('modalNovedadHeaderTitulo').textContent = `NOVEDADES - PEDIDO #${numeroPedido} - RECIBO ${numeroRecibo}`;
        document.getElementById('novedadNumeroPedido').value = numeroPedido;
        document.getElementById('novedadPrendaId').value = prendaId;
        document.getElementById('novedadNumeroRecibo').value = numeroRecibo;
        document.getElementById('novedadDescripcionText').value = '';

        const btn = document.getElementById('btnGuardarNovedad');
        if (btn) {
            btn.textContent = 'Guardar Novedad';
            btn.onclick = window.guardarNovedad;
        }

        modal.style.display = 'flex';
        cargarNovedades(numeroPedido, numeroRecibo);
    };

    window.cerrarModalNovedad = function() {
        const modal = document.getElementById('modalNovedad');
        if (modal) modal.style.display = 'none';
    };

    window.guardarNovedad = async function() {
        const numeroPedido = document.getElementById('novedadNumeroPedido')?.value;
        const numeroRecibo = document.getElementById('novedadNumeroRecibo')?.value;
        const texto = (document.getElementById('novedadDescripcionText')?.value || '').trim();
        if (!numeroPedido || !numeroRecibo || !texto) return;

        const btn = document.getElementById('btnGuardarNovedad');
        const txt = btn?.textContent || 'Guardar Novedad';
        if (btn) { btn.disabled = true; btn.textContent = 'Guardando...'; }

        try {
            const res = await fetch(`/recibos-novedades/${encodeURIComponent(numeroPedido)}/${encodeURIComponent(numeroRecibo)}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': tokenCsrf() },
                body: JSON.stringify({ novedades: texto, tipo_novedad: 'observacion', prendas_ids: [] }),
            });
            const data = await res.json();
            if (data?.success) {
                document.getElementById('novedadDescripcionText').value = '';
                await cargarNovedades(numeroPedido, numeroRecibo);
            }
        } finally {
            if (btn) { btn.disabled = false; btn.textContent = txt; }
        }
    };

    window.editarNovedad = function(novedadId, textoActual, tipoActual) {
        const area = document.getElementById('novedadDescripcionText');
        const btn = document.getElementById('btnGuardarNovedad');
        if (!area || !btn) return;

        area.value = String(textoActual || '');
        area.focus();
        btn.textContent = 'Actualizar Novedad';
        btn.onclick = async function() {
            const numeroPedido = document.getElementById('novedadNumeroPedido')?.value;
            const numeroRecibo = document.getElementById('novedadNumeroRecibo')?.value;
            const nuevo = area.value.trim();
            if (!nuevo) return;
            btn.disabled = true;
            try {
                const res = await fetch(`/recibos-novedades/${novedadId}`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': tokenCsrf() },
                    body: JSON.stringify({ novedad_texto: nuevo, tipo_novedad: String(tipoActual || 'observacion') }),
                });
                const data = await res.json();
                if (data?.success) {
                    btn.textContent = 'Guardar Novedad';
                    btn.onclick = window.guardarNovedad;
                    area.value = '';
                    await cargarNovedades(numeroPedido, numeroRecibo);
                }
            } finally {
                btn.disabled = false;
            }
        };
    };

    window.eliminarNovedad = function(novedadId) {
        window.novedadIdAEliminar = novedadId;
        const modal = document.getElementById('modalConfirmacion');
        if (modal) modal.style.display = 'flex';
    };

    window.cancelarConfirmacion = function() {
        window.novedadIdAEliminar = null;
        const modal = document.getElementById('modalConfirmacion');
        if (modal) modal.style.display = 'none';
    };

    window.confirmarEliminar = async function() {
        const id = window.novedadIdAEliminar;
        if (!id) return;
        const numeroPedido = document.getElementById('novedadNumeroPedido')?.value;
        const numeroRecibo = document.getElementById('novedadNumeroRecibo')?.value;
        const btn = document.getElementById('btnConfirmarSi');
        if (btn) btn.disabled = true;
        try {
            const res = await fetch(`/recibos-novedades/${id}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': tokenCsrf() },
            });
            const data = await res.json();
            if (data?.success) {
                window.cancelarConfirmacion();
                await cargarNovedades(numeroPedido, numeroRecibo);
            }
        } finally {
            if (btn) btn.disabled = false;
        }
    };

    document.addEventListener('DOMContentLoaded', function() {
        window.cerrarModalNovedad();
        window.cancelarConfirmacion();

        const searchInput = document.getElementById('searchInput');
        const clearBtn = document.getElementById('clearFilterBtn');
        const ordenesList = document.getElementById('ordenesList');
        const tagsContainer = document.getElementById('reciboTags');
        const getOrdenCards = () => ordenesList ? ordenesList.querySelectorAll('.orden-card-simple') : [];

        let activeFilter = 'COSTURA';

        function aplicarTemaDashboard(filter) {
            const body = document.body;
            const titleText = document.getElementById('dashboardPageTitleText');
            const titleIcon = document.getElementById('dashboardPageTitleIcon');
            const theme = filter === 'REFLECTIVO' ? 'reflectivo' : 'costura';

            if (body) {
                body.setAttribute('data-dashboard-theme', theme);
            }

            if (titleText) {
                titleText.textContent = filter === 'REFLECTIVO' ? 'CONTROL DE CALIDAD REFLECTIVO' : 'CONTROL DE CALIDAD';
            }

            if (titleIcon) {
                titleIcon.textContent = filter === 'REFLECTIVO' ? 'auto_awesome' : 'fact_check';
            }
        }

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

        function construirUrlVerRecibo(orden, recibo, tipoRecibo, numeroPedido, prendaId) {
            const esParcial = String(recibo?.es_parcial || orden?.es_parcial || '0') === '1' || Boolean(recibo?.es_parcial || orden?.es_parcial);
            const params = new URLSearchParams();
            params.set('tipo_recibo', tipoRecibo);
            params.set('prenda_id', prendaId);

            if (esParcial) {
                const parcialId = orden?.parcial_id || recibo?.parcial_id || '';
                const consecutivoParcial = recibo?.consecutivo_parcial || recibo?.consecutivo_actual || orden?.consecutivo_actual || '';
                params.set('tipo_recibo', 'PARCIAL');
                if (parcialId) params.set('parcial_id', parcialId);
                if (consecutivoParcial) params.set('consecutivo_parcial', consecutivoParcial);
            }

            return `/control-calidad/pedido/${numeroPedido}?${params.toString()}`;
        }

        function mostrarNotificacionControlCalidad(orden, action = 'added') {
            if (!window.NotificacionesPush || typeof window.NotificacionesPush.add !== 'function' || !orden) return;

            const tipoRecibo = String(orden?.tipo_recibo || orden?.recibos?.[0]?.tipo_recibo || '').toUpperCase();
            const esParcial = Boolean(orden?.es_parcial || orden?.parcial_id || orden?.recibos?.[0]?.es_parcial);
            const consecutivo = formatearConsecutivoVisibleJS(orden?.consecutivo_actual || orden?.recibos?.[0]?.consecutivo_actual || '');
            const nombrePrenda = String(orden?.nombre_prenda || '').trim();

            let title = esParcial ? 'Parcial en Control de Calidad' : 'Recibo en Control de Calidad';
            if (action === 'updated') {
                title = esParcial ? 'Parcial actualizado en C.C.' : 'Recibo actualizado en C.C.';
            } else if (action === 'removed') {
                title = esParcial ? 'Parcial salió de C.C.' : 'Recibo salió de C.C.';
            }

            const detalle = `${nombrePrenda ? nombrePrenda + ' · ' : ''}#${consecutivo || 'sin consecutivo'}`;

            window.NotificacionesPush.add({
                id: ['control-calidad', action, esParcial ? 'parcial' : 'original', String(orden?.parcial_id || orden?.id || ''), String(consecutivo || '')].join('|'),
                title,
                message: action === 'removed'
                    ? `${detalle} ya no está en Control de Calidad`
                    : `${detalle} llegó a Control de Calidad`,
                icon: tipoRecibo === 'REFLECTIVO' ? 'auto_awesome' : 'fact_check',
                tipo_recibo: tipoRecibo,
            });
        }

        function aplicarFiltros() {
            const busqueda = (searchInput?.value || '').toLowerCase().trim();
            let visibles = 0;

            if (clearBtn) {
                clearBtn.style.display = busqueda ? 'flex' : 'none';
            }

            getOrdenCards().forEach(card => {
                const numero = (card.dataset.numeroRecibo || '').toLowerCase();
                const prenda = (card.dataset.prenda || '').toLowerCase();
                const cliente = (card.dataset.cliente || '').toLowerCase();
                const tipoRecibo = (card.dataset.tipoRecibo || '').toUpperCase();

                const matchBusqueda = (numero.includes(busqueda) || prenda.includes(busqueda) || cliente.includes(busqueda) || busqueda === '');
                const matchTipo = tipoRecibo === activeFilter;

                if (matchBusqueda && matchTipo) {
                    card.style.display = '';
                    visibles++;
                } else {
                    card.style.display = 'none';
                }
            });

            const titleText = document.getElementById('dashboardPageTitleText');
            if (titleText) {
                titleText.textContent = visibles > 0 ? `CONTROL DE CALIDAD` : 'CONTROL DE CALIDAD';
            }
        }

        if (searchInput) {
            searchInput.addEventListener('input', function(e) {
                aplicarFiltros();
            });
        }

        if (clearBtn && searchInput) {
            clearBtn.addEventListener('click', function() {
                searchInput.value = '';
                aplicarFiltros();
            });
        }

        if (tagsContainer) {
            tagsContainer.addEventListener('click', function(e) {
                const btn = e.target.closest('.recibo-tag');
                if (!btn) {
                    return;
                }

                const filter = (btn.dataset.filter || 'COSTURA').toUpperCase();
                activeFilter = filter;

                tagsContainer.querySelectorAll('.recibo-tag').forEach(b => {
                    b.classList.remove('active', 'badge-filtro-active');
                });
                btn.classList.add('active');
                btn.classList.add('badge-filtro-active');

                aplicarTemaDashboard(filter);
                aplicarFiltros();
            });
        }

        aplicarTemaDashboard(activeFilter);
        aplicarFiltros();

        function crearCardControlCalidadDesdeEvento(orden, permitirReemplazo = false) {
            if (!ordenesList || !orden) return null;

            const recibo = Array.isArray(orden.recibos)
                ? (orden.recibos[0] || null)
                : (orden.recibo || {
                    id: orden.id || null,
                    tipo_recibo: orden.tipo_recibo || '',
                    consecutivo_actual: orden.consecutivo_actual || '',
                    consecutivo_original: orden.consecutivo_original || '',
                    consecutivo_parcial: orden.consecutivo_actual || '',
                    creado_en: orden.fecha_creacion || '',
                    area: orden.area || orden.proceso_actual || 'Control Calidad',
                    es_parcial: orden.es_parcial || false,
                    parcial_id: orden.parcial_id || null,
                    completado_area: orden.completado_area || false,
                    prenda_id: orden.prenda_id || null,
                });

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
            const urlVerRecibo = construirUrlVerRecibo(orden, recibo, tipoRecibo, numeroPedido, prendaId);

            if (!permitirReemplazo && cardKey && document.querySelector(`[data-card-key="${cardKey}"]`)) {
                return null;
            }

            const card = document.createElement('div');
            card.className = `orden-card-simple${esReflectivo ? ' borde-reflectivo' : ''}${completadoArea ? ' recibo-completado-card' : ''}`;
            card.dataset.cardKey = cardKey;
            card.dataset.numero = String(numeroPedido).toLowerCase();
            card.dataset.prenda = String(orden.nombre_prenda || '').toLowerCase();
            card.dataset.cliente = String(orden.cliente || '').toLowerCase();
            card.dataset.tipoRecibo = tipoRecibo;
            card.dataset.prendaId = String(prendaId || '');
            card.dataset.esParcial = esParcial ? '1' : '0';
            card.dataset.parcialId = String(parcialId || '');
            card.dataset.numeroRecibo = String(consecutivoActual || recibo.id || orden.id || '');

            card.innerHTML = `
                <div class="orden-border ${esParcial ? 'en-proceso' : ''}"></div>
                <div class="orden-body ${completadoArea ? 'recibo-completado-area' : ''}">
                    <span class="recibo-completado-chip" style="${completadoArea ? '' : 'display: none;'}">COMPLETADO</span>
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
                            <a class="btn-ver-recibos" href="${urlVerRecibo}">
                                <span class="material-symbols-rounded">receipt</span>
                                VER
                            </a>
                            <button class="btn-agregar-novedad" onclick="abrirModalNovedad('${numeroPedido}', ${prendaId || 0}, '${(orden.nombre_prenda || '').replace(/'/g, "\\'")}', '${consecutivoActual || numeroPedido || ''}')">
                                <span class="material-symbols-rounded">comment</span>
                                AGREGAR NOVEDAD
                            </button>
                            <button class="btn-completar-recibo" data-recibo-id="${recibo.id || orden.id || ''}" data-es-parcial="${esParcial ? '1' : '0'}" data-parcial-id="${parcialId}" data-completado="${completadoArea ? '1' : '0'}" style="${completadoArea ? 'display: none;' : ''}" onclick="toggleCompletarRecibo(this); event.stopPropagation();">
                                <span class="material-symbols-rounded">done</span>
                                COMPLETAR
                            </button>
                            <button class="btn-deshacer-recibo" data-recibo-id="${recibo.id || orden.id || ''}" data-es-parcial="${esParcial ? '1' : '0'}" data-parcial-id="${parcialId}" style="${completadoArea ? '' : 'display: none;'}" onclick="deshacerCompletarRecibo(this); event.stopPropagation();">
                                <span class="material-symbols-rounded">undo</span>
                                DESHACER
                            </button>
                        </div>
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
                        <a href="${urlVerRecibo}" class="action-arrow">
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

        function registrarRealtimeControlCalidad() {
            const echo = window.EchoInstance || window.Echo;
            if (!echo) {
                return;
            }

            echo.channel('control-calidad')
                .listen('ControlCalidadUpdated', (e) => {
                    console.log('[Control Calidad] Evento realtime:', e);

                    if (!e?.orden || !['added', 'removed', 'updated'].includes(String(e?.action || ''))) {
                        return;
                    }

                    insertarOActualizarCardControlCalidad(e.orden, e.action);

                    if (e.action === 'added') {
                        mostrarNotificacionControlCalidad(e.orden, e.action);
                    }
                });
        }

        if (typeof window.waitForEcho === 'function') {
            window.waitForEcho(() => registrarRealtimeControlCalidad());
        } else {
            setTimeout(() => registrarRealtimeControlCalidad(), 400);
        }
    });

    function abrirReciboControlCalidad(pedidoId, prendaId) {
        if (typeof window.abrirModalRecibo !== 'function') {
            return;
        }
        window.abrirModalRecibo(pedidoId, prendaId, 'control calidad');
    }

    window._ccCompletarDraft = {
        reciboId: null,
        esParcial: false,
        parcialId: null,
        numeroPedido: null,
        prendaId: null,
        tallas: []
    };

    window.cerrarModalCompletarTallas = function() {
        const modal = document.getElementById('modalCompletarTallas');
        if (modal) {
            modal.style.display = 'none';
        }
    };

    async function cargarTallasReciboParaCompletar(btn) {
        const card = btn.closest('.orden-card-simple');
        const reciboId = btn.dataset.reciboId;
        const esParcial = btn.dataset.esParcial === '1';
        const parcialId = btn.dataset.parcialId || reciboId;
        const numeroPedido = card?.dataset?.numero || '';
        const prendaId = card?.dataset?.prendaId || '';

        const modal = document.getElementById('modalCompletarTallas');
        const meta = document.getElementById('ccModalMeta');
        const loading = document.getElementById('ccModalLoading');
        const errorBox = document.getElementById('ccModalError');
        const container = document.getElementById('ccModalTallasContainer');

        if (!modal || !container || !loading || !errorBox) {
            return;
        }

        window._ccCompletarDraft = { reciboId, esParcial, parcialId, numeroPedido, prendaId, tallas: [] };

        const numeroRecibo = String(
            card?.dataset?.numeroRecibo
            || card?.querySelector('.orden-numero')?.textContent?.replace('#', '').trim()
            || ''
        ).trim();
            meta.textContent = `# Recibo ${numeroRecibo || '-'}`;
        errorBox.style.display = 'none';
        errorBox.textContent = '';
        container.innerHTML = '';
        loading.style.display = '';
        modal.style.display = 'flex';

        try {
            const params = new URLSearchParams();
            if (prendaId) params.set('prenda_id', prendaId);
            if (esParcial) {
                params.set('tipo_recibo', 'PARCIAL');
                if (parcialId) params.set('parcial_id', parcialId);
            }

            const response = await fetch(`/control-calidad/api/pedido/${numeroPedido}?${params.toString()}`, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });

            const data = await response.json();
            const prenda = data?.data?.prendas?.[0] || null;

            const normalizarGenero = (valor) => {
                const g = String(valor || '').trim().toUpperCase();
                if (!g) return 'SIN GENERO';
                if (g === 'HOMBRE') return 'CABALLERO';
                if (g === 'MUJER') return 'DAMA';
                return g;
            };

            const normalizarTallas = (source, generoFallback = '') => {
                if (!source) return [];

                if (Array.isArray(source)) {
                    const salida = [];

                    source.forEach((item) => {
                        if (Array.isArray(item?.tallas)) {
                            const generoGrupo = normalizarGenero(item?.genero ?? item?.nombre_genero ?? generoFallback);
                            item.tallas.forEach((t) => {
                                const tallaNombre = String(t?.talla ?? t?.nombre ?? '').trim();
                                if (!tallaNombre) return;
                                salida.push({
                                    genero: generoGrupo,
                                    talla: tallaNombre.toUpperCase(),
                                    cantidad: Number(t?.cantidad ?? t?.total ?? 0)
                                });
                            });
                            return;
                        }

                        const tallaNombre = String(item?.talla ?? item?.nombre ?? '').trim();
                        const generoFila = normalizarGenero(item?.genero ?? item?.sexo ?? generoFallback);
                        if (!tallaNombre) return;
                        salida.push({
                            genero: generoFila,
                            talla: tallaNombre.toUpperCase(),
                            cantidad: Number(item?.cantidad ?? item?.total ?? 0)
                        });
                    });

                    return salida;
                }

                if (typeof source === 'object') {
                    const salida = [];

                    Object.entries(source).forEach(([key, value]) => {
                        // Caso COSTURA típico:
                        // { dama: { S: 2, M: 1 }, caballero: { L: 3 } }
                        // o { dama: [{ talla:'S', cantidad:2 }] }
                        if (value && typeof value === 'object') {
                            const generoObj = normalizarGenero(key);

                            if (Array.isArray(value)) {
                                value.forEach((item) => {
                                    const tallaNombre = String(item?.talla ?? item?.nombre ?? '').trim();
                                    if (!tallaNombre) return;
                                    salida.push({
                                        genero: generoObj,
                                        talla: tallaNombre.toUpperCase(),
                                        cantidad: Number(item?.cantidad ?? item?.total ?? 0)
                                    });
                                });
                                return;
                            }

                            Object.entries(value).forEach(([talla, cantidad]) => {
                                const tallaNombre = String(talla || '').trim();
                                if (!tallaNombre) return;
                                salida.push({
                                    genero: generoObj,
                                    talla: tallaNombre.toUpperCase(),
                                    cantidad: Number(cantidad || 0)
                                });
                            });
                            return;
                        }

                        // Caso plano:
                        // { S: 2, M: 1 }
                        const tallaNombre = String(key || '').trim();
                        if (!tallaNombre) return;
                        salida.push({
                            genero: normalizarGenero(generoFallback),
                            talla: tallaNombre.toUpperCase(),
                            cantidad: Number(value || 0)
                        });
                    });

                    return salida;
                }

                return [];
            };

            let tallas = normalizarTallas(prenda?.tallas);

            if (tallas.length === 0) {
                tallas = normalizarTallas(prenda?.cantidad_talla);
            }

            if (tallas.length === 0 && Array.isArray(prenda?.procesos)) {
                const tipoReciboCard = String(card?.dataset?.tipoRecibo || '').trim().toUpperCase();
                const procesosPreferidos = prenda.procesos.filter((proc) => {
                    const nombreProc = String(proc?.proceso || proc?.tipo_proceso || proc?.nombre_proceso || '').trim().toUpperCase();
                    return nombreProc === tipoReciboCard || nombreProc === 'CONTROL DE CALIDAD' || nombreProc === 'CONTROL CALIDAD';
                });
                const procesosBase = procesosPreferidos.length > 0 ? procesosPreferidos : prenda.procesos;

                for (const proceso of procesosBase) {
                    if (Array.isArray(proceso?.tallas_detalle) && proceso.tallas_detalle.length > 0) {
                        tallas = normalizarTallas(proceso.tallas_detalle);
                        if (tallas.length > 0) break;
                    }

                    if (Array.isArray(proceso?.tallas) && proceso.tallas.length > 0) {
                        tallas = normalizarTallas(proceso.tallas);
                        if (tallas.length > 0) break;
                    }
                }
            }

            if (tallas.length > 0) {
                const sumadas = {};
                tallas.forEach((t) => {
                    const genero = normalizarGenero(t.genero);
                    const talla = String(t.talla || '').trim().toUpperCase();
                    const key = `${genero}||${talla}`;
                    if (!key) return;
                    if (!sumadas[key]) {
                        sumadas[key] = { genero, talla, cantidad: 0 };
                    }
                    sumadas[key].cantidad += Number(t.cantidad || 0);
                });
                tallas = Object.values(sumadas);
            }

            window._ccCompletarDraft.tallas = tallas;

            if (tallas.length === 0) {
                errorBox.textContent = 'Este recibo no tiene tallas disponibles para mostrar.';
                errorBox.style.display = '';
                return;
            }

            const tallasAgrupadas = tallas.reduce((acc, t) => {
                const genero = normalizarGenero(t.genero);
                if (!acc[genero]) acc[genero] = [];
                acc[genero].push(t);
                return acc;
            }, {});

            const ordenGeneros = ['DAMA', 'CABALLERO', 'UNISEX', 'SIN GENERO'];
            const generosRender = Object.keys(tallasAgrupadas)
                .sort((a, b) => {
                    const ia = ordenGeneros.indexOf(a);
                    const ib = ordenGeneros.indexOf(b);
                    return (ia === -1 ? 999 : ia) - (ib === -1 ? 999 : ib);
                });

            let filaIndex = 0;
            container.innerHTML = `
                <table class="cc-tallas-table">
                    <thead>
                        <tr>
                            <th>Seleccionar</th>
                            <th>Talla</th>
                            <th>Cantidad recibo</th>
                            <th>Cantidad entrega</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${generosRender.map((genero) => {
                            const filas = tallasAgrupadas[genero];
                            return `
                                <tr class="cc-genero-row">
                                    <td colspan="4"><strong>${genero}</strong></td>
                                </tr>
                                ${filas.map((t) => {
                                    const currentIndex = filaIndex++;
                                    return `
                                        <tr>
                                            <td>
                                                <input type="checkbox" data-index="${currentIndex}" class="cc-talla-check">
                                            </td>
                                            <td><span class="cc-talla-pill">${t.talla}</span></td>
                                            <td>${t.cantidad}</td>
                                            <td>
                                                <div class="cc-entrega-wrap">
                                                    <input type="number" min="0" step="1" class="cc-entrega-input" data-index="${currentIndex}" disabled>
                                                    <span class="cc-disp-badge" data-disp-index="${currentIndex}">Disp: ${t.cantidad}</span>
                                                </div>
                                            </td>
                                        </tr>
                                    `;
                                }).join('')}
                            `;
                        }).join('')}
                    </tbody>
                </table>
            `;

            const tallasPlanas = [];
            generosRender.forEach((genero) => {
                tallasAgrupadas[genero].forEach((t) => tallasPlanas.push(t));
            });

            const refrescarDisponible = (idx) => {
                const input = container.querySelector(`.cc-entrega-input[data-index="${idx}"]`);
                const disp = container.querySelector(`.cc-disp-badge[data-disp-index="${idx}"]`);
                if (!disp) return;

                const total = Number(tallasPlanas[idx]?.cantidad ?? 0);
                const digitado = Number(input?.value || 0);
                const restante = Math.max(0, total - digitado);
                disp.textContent = `Disp: ${restante}`;
            };

            const ajustarInputMaximo = (idx) => {
                const input = container.querySelector(`.cc-entrega-input[data-index="${idx}"]`);
                if (!input) return;
                const total = Number(tallasPlanas[idx]?.cantidad ?? 0);
                let valor = Number(input.value || 0);
                if (!Number.isFinite(valor) || valor < 0) valor = 0;
                if (valor > total) valor = total;
                input.value = String(Math.floor(valor));
                refrescarDisponible(idx);
            };

            container.querySelectorAll('.cc-talla-check').forEach((check) => {
                check.addEventListener('change', (e) => {
                    const idx = Number(e.target.dataset.index);
                    const input = container.querySelector(`.cc-entrega-input[data-index="${idx}"]`);
                    if (!input) return;

                    if (e.target.checked) {
                        input.disabled = false;
                        input.value = String(tallasPlanas[idx]?.cantidad ?? 0);
                        refrescarDisponible(idx);
                    } else {
                        input.value = '';
                        input.disabled = true;
                        const disp = container.querySelector(`.cc-disp-badge[data-disp-index="${idx}"]`);
                        if (disp) {
                            disp.textContent = `Disp: ${Number(tallasPlanas[idx]?.cantidad ?? 0)}`;
                        }
                    }
                });
            });

            container.querySelectorAll('.cc-entrega-input').forEach((input) => {
                input.addEventListener('input', (e) => {
                    const idx = Number(e.target.dataset.index);
                    ajustarInputMaximo(idx);
                });
                input.addEventListener('blur', (e) => {
                    const idx = Number(e.target.dataset.index);
                    ajustarInputMaximo(idx);
                });
            });
        } catch (error) {
            errorBox.textContent = 'No se pudieron cargar las tallas del recibo.';
            errorBox.style.display = '';
        } finally {
            loading.style.display = 'none';
        }
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
        await cargarTallasReciboParaCompletar(btn);
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
                btnCompletar.style.display = '';
            }

            const body = btn.closest('.orden-body');
            if (body) {
                body.classList.remove('recibo-completado-area');
                const chip = body.querySelector('.recibo-completado-chip');
                if (chip) {
                    chip.style.display = 'none';
                }
            }
            const card = btn.closest('.orden-card-simple');
            if (card) {
                card.classList.remove('recibo-completado-card');
            }

            btn.style.display = 'none';
        } catch (error) {
            return;
        }
    };

    // Función para abrir distribución de parciales en Control de Calidad
    window.abrirDistribucionReciboCC = function(btn, tipoRecibo) {
        const reciboId = btn.dataset.reciboId;
        const prendaId = btn.dataset.prendaId;
        const numeroRecibo = btn.dataset.numeroRecibo;
        const ordenCard = btn.closest('.orden-card-simple');

        if (!reciboId) {
            console.error('No se pudo determinar el ID del recibo');
            return;
        }

        // Buscar si ya existe la sección de distribución
        let distribucionSection = ordenCard?.nextElementSibling;
        
        if (distribucionSection && !distribucionSection.classList.contains('distribucion-parciales-cc-section')) {
            distribucionSection = null;
        }
        
        if (distribucionSection) {
            // Si ya existe, toggle (mostrar/ocultar)
            const isHidden = distribucionSection.style.display === 'none';
            distribucionSection.style.display = isHidden ? 'flex' : 'none';
            
            // Cambiar el texto del botón
            btn.innerHTML = isHidden ? '<span class="material-symbols-rounded">visibility_off</span> OCULTAR' : '<span class="material-symbols-rounded">share</span> VER DISTRIBUCIÓN';
            
            return;
        }

        // Si no existe, obtener datos del endpoint de control-calidad
        const urlApi = `/control-calidad/api/recibos/${reciboId}/distribucion-parciales`;
        
        fetch(urlApi, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success || !data.parciales || data.parciales.length === 0) {
                console.error('No hay parciales disponibles');
                return;
            }

            // Generar HTML de cards usando las MISMAS clases CSS de operario/dashboard
            let cardsHTML = data.parciales.map((parcial, idx) => {
                // Agrupar tallas
                const tallasSumadas = (parcial.tallas || []).reduce((acc, talla) => {
                    const key = (talla.talla || '').toUpperCase();
                    if (!acc[key]) {
                        acc[key] = 0;
                    }
                    acc[key] += talla.cantidad || 0;
                    return acc;
                }, {});

                const tallasHTML = Object.entries(tallasSumadas)
                    .map(([talla, cantidad]) => `<span class="talla-item">${talla}: <strong>${cantidad}</strong></span>`)
                    .join('');

                return `
                    <div class="parcial-card" data-parcial-id="${parcial.id}">
                        <div class="parcial-header">
                            <div class="parcial-numero">
                                <h4 class="parcial-title">Parcial #${String(parseFloat(parcial.consecutivo_parcial))}</h4>
                                <span class="parcial-tipo-recibo">${tipoRecibo}</span>
                            </div>
                        </div>
                        
                        <div class="parcial-body">
                            <div class="parcial-row">
                                <div class="parcial-info-group full-width">
                                    <span class="parcial-label">Estado</span>
                                    <span class="parcial-value">
                                        ${parcial.completado_area ? 'COMPLETADO' : (parcial.estado_proceso || 'En Progreso')}
                                    </span>
                                </div>
                            </div>

                            ${tallasHTML ? `
                            <div class="parcial-row parcial-tallas-row">
                                <div class="parcial-tallas-container">
                                    ${tallasHTML}
                                </div>
                            </div>
                            ` : ''}

                            <div class="parcial-row parcial-acciones">
                                <button class="btn-ver-recibo-parcial" 
                                        onclick="verReciboParcialCC(${parcial.id}, '${String(parcial.consecutivo_parcial).replace(/'/g, "\\'")}'  , '${numeroRecibo}')">
                                    <span class="material-symbols-rounded">visibility</span>
                                    VER RECIBO
                                </button>
                                ${parcial.completado_area ? `
                                <button class="btn-deshacer-parcial-cc"
                                        onclick="deshacerCompletarParcialCC(${parcial.id});"
                                        data-parcial-id="${parcial.id}">
                                    <span class="material-symbols-rounded">undo</span>
                                    DESHACER
                                </button>
                                ` : `
                                <button class="btn-completar-parcial-cc"
                                        onclick="completarParcialCC(${parcial.id}, ${reciboId});"
                                        data-parcial-id="${parcial.id}">
                                    <span class="material-symbols-rounded">done</span>
                                    COMPLETAR
                                </button>
                                `}
                            </div>
                        </div>
                    </div>
                `;
            }).join('');

            // Crear sección de distribución con las MISMAS clases CSS
            const distribucionDiv = document.createElement('div');
            distribucionDiv.className = 'distribucion-parciales-cc-section';
            distribucionDiv.innerHTML = cardsHTML;

            ordenCard.insertAdjacentElement('afterend', distribucionDiv);

            // Cambiar texto del botón
            btn.innerHTML = '<span class="material-symbols-rounded">visibility_off</span> OCULTAR';
        })
        .catch(error => {
            console.error('Error al obtener parciales:', error);
        });
    };

    // Función para ver recibo de un parcial
    window.verReciboParcialCC = function(parcialId, consecutivoParcial, numeroRecibo) {
        const urlRecibo = `/control-calidad/pedido/${numeroRecibo}?parcial_id=${parcialId}&consecutivo_parcial=${consecutivoParcial}&tipo_recibo=PARCIAL`;
        window.location.href = urlRecibo;
    };

    // Función para completar un parcial
    window.completarParcialCC = async function(parcialId, reciboId) {
        try {
            const response = await fetch(`/control-calidad/api/recibos/${parcialId}/completar`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    es_parcial: true,
                    parcial_id: parcialId
                })
            });

            const data = await response.json();
            if (!data.success) {
                alert('Error: ' + data.message);
                return;
            }

            // Marcar el botón como deshabilitado y cambiar su estilo
            const btn = document.querySelector(`[data-parcial-id="${parcialId}"].btn-completar-parcial-cc`);
            if (btn) {
                btn.disabled = true;
                btn.classList.add('btn-completed');
                btn.innerHTML = '<span class="material-symbols-rounded">check_circle</span> COMPLETADO';
                btn.style.cursor = 'default';
            }

            // Si el recibo padre fue completado, remover la tarjeta del recibo del DOM
            if (data.recibo_padre_completado) {
                const reciboCard = document.querySelector(`[data-recibo-id="${reciboId}"].orden-card-simple`);
                if (reciboCard) {
                    reciboCard.style.transition = 'opacity 0.3s ease-out';
                    reciboCard.style.opacity = '0';
                    setTimeout(() => {
                        reciboCard.remove();
                    }, 300);
                }
            }
        } catch (error) {
            console.error('Error al completar parcial:', error);
        }
    };

    window.deshacerCompletarParcialCC = async function(parcialId) {
        try {
            const response = await fetch(`/control-calidad/api/recibos/${parcialId}/deshacer`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    es_parcial: true,
                    parcial_id: parcialId
                })
            });

            const data = await response.json();
            if (!data.success) {
                alert('Error: ' + data.message);
                return;
            }

            // Mostrar mensaje de éxito
            alert('Parcial deshecho y restaurado a Control de Calidad');

            // Recargar la página después de 1 segundo
            setTimeout(() => {
                location.reload();
            }, 1000);
        } catch (error) {
            console.error('Error al deshacer parcial:', error);
            alert('Error al deshacer el parcial');
        }
    };

    // Agregar estilos CSS para que funcione el diseño
    const style = document.createElement('style');
    style.innerHTML = `
        .distribucion-parciales-cc-section {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            margin: 0.75rem 0 1.5rem 0;
            padding: 1rem;
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.02) 0%, rgba(118, 75, 162, 0.02) 100%);
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            animation: slideDownCards 0.3s ease-out;
        }

        @keyframes slideDownCards {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .distribucion-parciales-cc-section .parcial-card {
            animation: slideDownCards 0.3s ease-out;
        }

        .parcial-row.parcial-acciones {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
            margin-top: 0.75rem;
        }

        .btn-completar-parcial-cc {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            padding: 0.4rem 0.8rem;
            border: none;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
            background: #4CAF50;
            color: white;
        }

        .btn-completar-parcial-cc:hover:not(:disabled) {
            opacity: 0.9;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(76, 175, 80, 0.3);
        }

        .btn-completar-parcial-cc:disabled,
        .btn-completar-parcial-cc.btn-completed {
            cursor: not-allowed;
            opacity: 0.6;
            background: #ccc;
        }

        .btn-deshacer-parcial-cc {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            padding: 0.4rem 0.8rem;
            border: none;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
            background: #ADD8E6;
            color: #0066ff;
        }

        .btn-deshacer-parcial-cc:hover:not(:disabled) {
            opacity: 0.9;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(173, 216, 230, 0.5);
            background: #87CEEB;
        }

        .btn-deshacer-parcial-cc:disabled {
            cursor: not-allowed;
            opacity: 0.6;
            background: #ccc;
        }

        .btn-ver-recibo-parcial {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            padding: 0.4rem 0.8rem;
            border: none;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
            background: #2196F3;
            color: white;
        }

        .btn-ver-recibo-parcial:hover {
            opacity: 0.9;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(33, 150, 243, 0.3);
        }
    `;
    document.head.appendChild(style);
</script>
@endpush
@endsection
