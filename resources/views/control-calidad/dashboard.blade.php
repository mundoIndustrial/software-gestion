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

                                <div class="orden-buttons">
                                    <a
                                        class="btn-ver-recibos"
                                        href="{{ $urlVerRecibo }}">
                                        <span class="material-symbols-rounded">receipt</span>
                                        VER
                                    </a>
                                    @if($recibo)
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

    body[data-dashboard-theme="reflectivo"] .control-calidad-dashboard .btn-ver-recibos {
        color: #119669;
        border-color: #b7e5d2;
        box-shadow: 0 6px 14px rgba(17, 150, 105, 0.12);
    }

    body[data-dashboard-theme="reflectivo"] .control-calidad-dashboard .btn-ver-recibos:hover {
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

        .control-calidad-dashboard .orden-numero {
            font-size: 1.35rem;
        }
    }
</style>

@include('components.modals.recibo-dinamico-modal')

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
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
            card.className = `orden-card-simple${esReflectivo ? ' borde-reflectivo' : ''}`;
            card.dataset.cardKey = cardKey;
            card.dataset.numero = String(numeroPedido).toLowerCase();
            card.dataset.prenda = String(orden.nombre_prenda || '').toLowerCase();
            card.dataset.cliente = String(orden.cliente || '').toLowerCase();
            card.dataset.tipoRecibo = tipoRecibo;
            card.dataset.esParcial = esParcial ? '1' : '0';
            card.dataset.parcialId = String(parcialId || '');
            card.dataset.numeroRecibo = String(consecutivoActual || recibo.id || orden.id || '');

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
                            <a class="btn-ver-recibos" href="${urlVerRecibo}">
                                <span class="material-symbols-rounded">receipt</span>
                                VER
                            </a>
                            <button class="btn-completar-recibo" data-recibo-id="${recibo.id || orden.id || ''}" data-es-parcial="${esParcial ? '1' : '0'}" data-parcial-id="${parcialId}" data-completado="${completadoArea ? '1' : '0'}" onclick="toggleCompletarRecibo(this); event.stopPropagation();">
                                <span class="material-symbols-rounded">done</span>
                                ${completadoArea ? 'COMPLETADO' : 'COMPLETAR'}
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
