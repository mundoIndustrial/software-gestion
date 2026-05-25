@extends('supervisor-pedidos.layout')

@section('title', 'Estadisticas de Asesoras')
@section('page-title', 'Estadisticas de Asesoras')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/supervisor-pedidos/estadisticas-asesoras.css') }}?v={{ filemtime(public_path('css/supervisor-pedidos/estadisticas-asesoras.css')) }}">
@endpush

@section('content')
<div class="stats-asesoras-wrapper">
    @php
        $comparativoTitulo = $periodo === 'mes' ? 'Mes pasado' : ($periodo === 'ano' ? 'Ano pasado' : 'Periodo pasado');
        $periodoNombre = $periodo === 'mes' ? 'mes' : ($periodo === 'ano' ? 'ano' : 'rango');
        $asesorasTopActual = collect($rankingAsesoras)->take(10);
        $asesorasTopComparativo = collect($rankingAsesoras)->sortByDesc('total_anterior')->take(10)->values();
        $clientesRecurrentes = collect($clientesConPedidos)->where('es_recurrente', true)->values();
    @endphp

    <form method="GET" action="{{ route('supervisor-pedidos.estadisticas-asesoras') }}" class="stats-filtros card-shell">
        <div class="filtro-row">
            <label for="periodo">Modo</label>
            <select id="periodo" name="periodo">
                <option value="mes" {{ $periodo === 'mes' ? 'selected' : '' }}>Mes</option>
                <option value="ano" {{ $periodo === 'ano' ? 'selected' : '' }}>Ano</option>
                <option value="rango" {{ $periodo === 'rango' ? 'selected' : '' }}>Rango</option>
            </select>
        </div>
        <div class="filtro-row filtro-mes {{ $periodo !== 'mes' ? 'hidden' : '' }}">
            <label for="month">Mes</label>
            <select id="month" name="month">
                @for($m = 1; $m <= 12; $m++)
                    <option value="{{ $m }}" {{ (int) $month === $m ? 'selected' : '' }}>
                        {{ \Carbon\Carbon::create()->month($m)->locale('es')->translatedFormat('F') }}
                    </option>
                @endfor
            </select>
        </div>
        <div class="filtro-row filtro-ano {{ $periodo === 'rango' ? 'hidden' : '' }}">
            <label for="year">Ano</label>
            <input id="year" name="year" type="number" min="2020" max="2100" value="{{ $year }}">
        </div>
        <div class="filtro-row filtro-rango {{ $periodo !== 'rango' ? 'hidden' : '' }}">
            <label for="desde">Desde</label>
            <input id="desde" name="desde" type="date" value="{{ $desde }}">
        </div>
        <div class="filtro-row filtro-rango {{ $periodo !== 'rango' ? 'hidden' : '' }}">
            <label for="hasta">Hasta</label>
            <input id="hasta" name="hasta" type="date" value="{{ $hasta }}">
        </div>
        <button type="submit" class="btn-aplicar">
            <span class="material-symbols-rounded">tune</span>
            Aplicar
        </button>
    </form>

    <section class="stats-periodo card-shell">
        <h3>Resumen de {{ $periodoActual }}</h3>
        <p>{{ $comparativoTitulo }}: {{ $periodoAnterior }}</p>
    </section>

    <section class="stats-cards">
        <button type="button" class="stats-card stats-card-button card-primary" data-open-modal="pedidosActualModal">
            <span class="material-symbols-rounded stats-icon">shopping_bag</span>
            <p class="stats-label">Pedidos periodo actual</p>
            <p class="stats-value">{{ number_format($totalActual, 0, ',', '.') }}</p>
        </button>
        <button type="button" class="stats-card stats-card-button card-muted" data-open-modal="pedidosComparativoModal">
            <span class="material-symbols-rounded stats-icon">history</span>
            <p class="stats-label">Pedidos {{ strtolower($comparativoTitulo) }}</p>
            <p class="stats-value">{{ number_format($totalAnterior, 0, ',', '.') }}</p>
        </button>
        <button type="button" class="stats-card stats-card-button card-accent" data-open-modal="clientesUnicosModal">
            <span class="material-symbols-rounded stats-icon">groups</span>
            <p class="stats-label">Clientes completamente nuevos del {{ $periodoNombre }}</p>
            <p class="stats-value">{{ number_format($clientesNuevosCount, 0, ',', '.') }}</p>
        </button>
        <button type="button" class="stats-card stats-card-button card-accent-2" data-open-modal="clientesRepitenModal">
            <span class="material-symbols-rounded stats-icon">repeat</span>
            <p class="stats-label">Clientes que repiten del {{ $periodoNombre }}</p>
            <p class="stats-value">{{ number_format($clientesRecurrentesCount, 0, ',', '.') }}</p>
        </button>
        <button type="button" class="stats-card stats-card-button {{ $diferenciaPedidos >= 0 ? 'card-positive' : 'card-negative' }}" data-open-modal="diferenciaModal">
            <span class="material-symbols-rounded stats-icon">{{ $diferenciaPedidos >= 0 ? 'trending_up' : 'trending_down' }}</span>
            <p class="stats-label">Diferencia vs {{ strtolower($comparativoTitulo) }}</p>
            <p class="stats-value">{{ $diferenciaPedidos >= 0 ? '+' : '' }}{{ number_format($diferenciaPedidos, 0, ',', '.') }}</p>
        </button>
    </section>

    <section class="stats-actions-grid">
        <button type="button" class="stats-action-card card-shell" data-open-modal="clientesModal">
            <p class="stats-action-title">Top clientes</p>
            <p class="stats-action-value">{{ count($topClientes) }}</p>
            <p class="stats-action-help">Ver detalle de todos los clientes y sus pedidos.</p>
        </button>
        <button type="button" class="stats-action-card card-shell {{ count($clientesInactivos) > 0 ? 'is-down' : 'is-neutral' }}" data-open-modal="inactivosModal">
            <p class="stats-action-title">Clientes que dejaron de pedir</p>
            <p class="stats-action-value">{{ count($clientesInactivos) }}</p>
            <p class="stats-action-help">Clientes que estuvieron en {{ strtolower($comparativoTitulo) }} y no en el periodo actual.</p>
        </button>
    </section>

    <section class="stats-panel card-shell">
        <div class="stats-panel-header">
            <h3>Ranking asesoras por pedidos</h3>
        </div>
        <div class="stats-table-wrapper">
            <table class="stats-table">
                <thead>
                    <tr>
                        <th>Posicion</th>
                        <th>Asesora</th>
                        <th>Pedidos {{ $periodoNombre }}</th>
                        <th>Clientes</th>
                        <th>Pedidos {{ strtolower($comparativoTitulo) }}</th>
                        <th>Diferencia</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rankingAsesoras as $fila)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $fila['asesora_nombre'] }}</td>
                            <td>{{ number_format($fila['total_actual'], 0, ',', '.') }}</td>
                            <td>
                                <button type="button" class="stats-cliente-btn" data-open-modal="clientesAsesoraModal{{ $loop->index }}" style="background: none; border: none; color: #0066cc; cursor: pointer; text-decoration: underline;">
                                    {{ number_format($fila['clientes_unicos'], 0, ',', '.') }}
                                </button>
                            </td>
                            <td>{{ number_format($fila['total_anterior'], 0, ',', '.') }}</td>
                            <td class="{{ $fila['diferencia'] >= 0 ? 'text-up' : 'text-down' }}">
                                {{ $fila['diferencia'] >= 0 ? '+' : '' }}{{ number_format($fila['diferencia'], 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">No hay pedidos para mostrar.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>

<div id="pedidosActualModal" class="stats-modal" aria-hidden="true">
    <div class="stats-modal-panel">
        <div class="stats-modal-header">
            <h3>Analisis: pedidos del periodo actual</h3>
            <button type="button" class="stats-modal-close" data-close-modal>&times;</button>
        </div>
        <div class="stats-modal-body">
            <p class="modal-metric">Total pedidos: <strong>{{ number_format($totalActual, 0, ',', '.') }}</strong></p>
            <table class="stats-table">
                <thead>
                    <tr>
                        <th>Top asesoras</th>
                        <th>Pedidos</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($asesorasTopActual as $fila)
                        <tr>
                            <td>{{ $fila['asesora_nombre'] }}</td>
                            <td>{{ number_format($fila['total_actual'], 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2">Sin datos para este periodo.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="pedidosComparativoModal" class="stats-modal" aria-hidden="true">
    <div class="stats-modal-panel">
        <div class="stats-modal-header">
            <h3>Analisis: pedidos de {{ strtolower($comparativoTitulo) }}</h3>
            <button type="button" class="stats-modal-close" data-close-modal>&times;</button>
        </div>
        <div class="stats-modal-body">
            <p class="modal-metric">Total pedidos: <strong>{{ number_format($totalAnterior, 0, ',', '.') }}</strong></p>
            <table class="stats-table">
                <thead>
                    <tr>
                        <th>Top asesoras</th>
                        <th>Pedidos</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($asesorasTopComparativo as $fila)
                        <tr>
                            <td>{{ $fila['asesora_nombre'] }}</td>
                            <td>{{ number_format($fila['total_anterior'], 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2">Sin datos para {{ strtolower($comparativoTitulo) }}.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="clientesUnicosModal" class="stats-modal" aria-hidden="true">
    <div class="stats-modal-panel">
        <div class="stats-modal-header">
            <h3>Analisis: clientes nuevos del {{ $periodoNombre }}</h3>
            <button type="button" class="stats-modal-close" data-close-modal>&times;</button>
        </div>
        <div class="stats-modal-body">
            @php
                $nuevosPorAsesora = collect($clientesNuevos)
                    ->groupBy('asesoras')
                    ->map(fn ($items) => $items->count())
                    ->sortDesc();
                $topAsesoraNuevos = $nuevosPorAsesora->keys()->first();
                $topAsesoraNuevosTotal = $nuevosPorAsesora->first();
            @endphp
            <p class="modal-metric">Clientes completamente nuevos del {{ $periodoNombre }}: <strong>{{ number_format($clientesNuevosCount, 0, ',', '.') }}</strong></p>
            <div class="stats-mini-metrics">
                <p class="stats-mini-metric"><span>Asesoras con nuevos</span><strong>{{ number_format($nuevosPorAsesora->count(), 0, ',', '.') }}</strong></p>
                <p class="stats-mini-metric"><span>Promedio por asesora</span><strong>{{ $nuevosPorAsesora->count() > 0 ? number_format($clientesNuevosCount / $nuevosPorAsesora->count(), 1, ',', '.') : '0,0' }}</strong></p>
                <p class="stats-mini-metric"><span>Mayor captacion</span><strong>{{ $topAsesoraNuevos ? $topAsesoraNuevos.' ('.$topAsesoraNuevosTotal.')' : 'Sin datos' }}</strong></p>
            </div>
            <div class="stats-chart-block">
                <h4 class="stats-chart-title">Distribucion de clientes nuevos por asesora</h4>
                <div class="stats-chart-wrap stats-chart-wrap-sm">
                    <canvas id="clientesNuevosChart" aria-label="Distribucion de clientes nuevos por asesora"></canvas>
                </div>
            </div>
            <div class="stats-table-wrapper">
                <table class="stats-table" id="clientesNuevosTable">
                    <thead>
                        <tr>
                            <th>Cliente nuevo</th>
                            <th>Asesor(a)</th>
                            <th>Pedidos en periodo actual</th>
                            <th>Detalle de pedidos</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($clientesNuevos as $cliente)
                            <tr>
                                <td>{{ $cliente['cliente_nombre'] }}</td>
                                <td>{{ $cliente['asesoras'] }}</td>
                                <td>{{ number_format($cliente['total_pedidos'], 0, ',', '.') }}</td>
                                <td>
                                    <details>
                                        <summary>Ver {{ count($cliente['pedidos']) }} pedidos</summary>
                                        <ul class="pedidos-list">
                                            @foreach($cliente['pedidos'] as $pedido)
                                                <li>
                                                    <strong>#{{ $pedido['numero_pedido'] }}</strong>
                                                    <span>{{ $pedido['fecha'] }}</span>
                                                    <span>{{ $pedido['asesora_nombre'] }}</span>
                                                    <span>{{ $pedido['estado'] }}</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </details>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4">No hay clientes nuevos en este periodo.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="stats-pagination" data-table-pagination="clientesNuevosTable" data-page-size="10">
                <button type="button" class="stats-page-btn" data-page-action="prev">Anterior</button>
                <span class="stats-page-info" data-page-info>Página 1 de 1</span>
                <button type="button" class="stats-page-btn" data-page-action="next">Siguiente</button>
            </div>
        </div>
    </div>
</div>

<div id="clientesRepitenModal" class="stats-modal" aria-hidden="true">
    <div class="stats-modal-panel">
        <div class="stats-modal-header">
            <h3>Analisis: clientes que repiten del {{ $periodoNombre }}</h3>
            <button type="button" class="stats-modal-close" data-close-modal>&times;</button>
        </div>
        <div class="stats-modal-body">
            <p class="modal-metric">Clientes que repiten: <strong>{{ number_format($clientesRecurrentesCount, 0, ',', '.') }}</strong></p>
            <div class="stats-table-wrapper">
                <table class="stats-table" id="clientesRepitenTable">
                    <thead>
                        <tr>
                            <th>Cliente</th>
                            <th>Asesor(a)</th>
                            <th>Pedidos en periodo actual</th>
                            <th>Detalle de pedidos</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($clientesRecurrentes as $cliente)
                            <tr>
                                <td>{{ $cliente['cliente_nombre'] }}</td>
                                <td>{{ $cliente['asesoras'] }}</td>
                                <td>{{ number_format($cliente['total_pedidos'], 0, ',', '.') }}</td>
                                <td>
                                    <details>
                                        <summary>Ver {{ count($cliente['pedidos']) }} pedidos</summary>
                                        <ul class="pedidos-list">
                                            @foreach($cliente['pedidos'] as $pedido)
                                                <li>
                                                    <strong>#{{ $pedido['numero_pedido'] }}</strong>
                                                    <span>{{ $pedido['fecha'] }}</span>
                                                    <span>{{ $pedido['asesora_nombre'] }}</span>
                                                    <span>{{ $pedido['estado'] }}</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </details>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4">No hay clientes repetidos en este periodo.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="stats-pagination" data-table-pagination="clientesRepitenTable" data-page-size="10">
                <button type="button" class="stats-page-btn" data-page-action="prev">Anterior</button>
                <span class="stats-page-info" data-page-info>Pagina 1 de 1</span>
                <button type="button" class="stats-page-btn" data-page-action="next">Siguiente</button>
            </div>
        </div>
    </div>
</div>

<div id="diferenciaModal" class="stats-modal" aria-hidden="true">
    <div class="stats-modal-panel">
        <div class="stats-modal-header">
            <h3>Analisis: diferencia vs {{ strtolower($comparativoTitulo) }}</h3>
            <button type="button" class="stats-modal-close" data-close-modal>&times;</button>
        </div>
        <div class="stats-modal-body">
            <p class="modal-metric">
                Resultado: <strong class="{{ $diferenciaPedidos >= 0 ? 'text-up' : 'text-down' }}">
                    {{ $diferenciaPedidos >= 0 ? '+' : '' }}{{ number_format($diferenciaPedidos, 0, ',', '.') }}
                </strong> pedidos
            </p>
            <p class="modal-metric">Periodo actual: <strong>{{ number_format($totalActual, 0, ',', '.') }}</strong></p>
            <p class="modal-metric">{{ $comparativoTitulo }}: <strong>{{ number_format($totalAnterior, 0, ',', '.') }}</strong></p>
            <div class="stats-chart-block">
                <h4 class="stats-chart-title">Diferencia por asesora (barras divergentes)</h4>
                <div class="stats-chart-wrap">
                    <canvas id="diferenciaChart" aria-label="Diferencia de pedidos por asesora frente al periodo comparativo"></canvas>
                </div>
            </div>
            <table class="stats-table">
                <thead>
                    <tr>
                        <th>Asesora</th>
                        <th>Diferencia</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rankingAsesoras as $fila)
                        <tr>
                            <td>{{ $fila['asesora_nombre'] }}</td>
                            <td class="{{ $fila['diferencia'] >= 0 ? 'text-up' : 'text-down' }}">
                                {{ $fila['diferencia'] >= 0 ? '+' : '' }}{{ number_format($fila['diferencia'], 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2">Sin diferencias para mostrar.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="clientesModal" class="stats-modal" aria-hidden="true">
    <div class="stats-modal-panel">
        <div class="stats-modal-header">
            <h3>Clientes y pedidos de {{ $periodoActual }}</h3>
            <button type="button" class="stats-modal-close" data-close-modal>&times;</button>
        </div>
        <div class="stats-modal-body">
            <table class="stats-table" id="clientesPedidosTable">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Asesor(a)</th>
                        <th>Total pedidos</th>
                        <th>Cliente recurrente</th>
                        <th>Detalle de pedidos</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($clientesConPedidos as $cliente)
                        <tr>
                            <td>{{ $cliente['cliente_nombre'] }}</td>
                            <td>{{ $cliente['asesoras'] }}</td>
                            <td>{{ number_format($cliente['total_pedidos'], 0, ',', '.') }}</td>
                            <td>
                                <span class="pill {{ $cliente['es_recurrente'] ? 'pill-up' : 'pill-neutral' }}">
                                    {{ $cliente['es_recurrente'] ? 'Si' : 'No' }}
                                </span>
                            </td>
                            <td>
                                <details>
                                    <summary>Ver {{ count($cliente['pedidos']) }} pedidos</summary>
                                    <ul class="pedidos-list">
                                        @foreach($cliente['pedidos'] as $pedido)
                                            <li>
                                                <strong>#{{ $pedido['numero_pedido'] }}</strong>
                                                <span>{{ $pedido['fecha'] }}</span>
                                                <span>{{ $pedido['asesora_nombre'] }}</span>
                                                <span>{{ $pedido['estado'] }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </details>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">No hay clientes para mostrar en este periodo.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="stats-pagination" data-table-pagination="clientesPedidosTable" data-page-size="10">
                <button type="button" class="stats-page-btn" data-page-action="prev">Anterior</button>
                <span class="stats-page-info" data-page-info>Pagina 1 de 1</span>
                <button type="button" class="stats-page-btn" data-page-action="next">Siguiente</button>
            </div>
        </div>
    </div>
</div>

<div id="inactivosModal" class="stats-modal" aria-hidden="true">
    <div class="stats-modal-panel">
        <div class="stats-modal-header">
            <h3>Clientes inactivos vs {{ strtolower($comparativoTitulo) }}</h3>
            <button type="button" class="stats-modal-close" data-close-modal>&times;</button>
        </div>
        <div class="stats-modal-body">
            <table class="stats-table">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Pedidos en {{ strtolower($comparativoTitulo) }}</th>
                        <th>Ultima compra</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($clientesInactivos as $cliente)
                        <tr>
                            <td>{{ $cliente['cliente_nombre'] }}</td>
                            <td>{{ number_format($cliente['total_anterior'], 0, ',', '.') }}</td>
                            <td>{{ $cliente['ultima_compra'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3">No hay clientes inactivos para este comparativo.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@foreach($clientesConPedidos as $clienteIndex => $cliente)
    @php
        $asesorasDelCliente = collect($cliente['pedidos'])
            ->unique('asesora_nombre')
            ->pluck('asesora_nombre')
            ->values();
    @endphp
    @foreach($rankingAsesoras as $asesoraIndex => $asesora)
        @php
            $clientesAsesoraActual = collect($clientesConPedidos)
                ->filter(function ($c) use ($asesora) {
                    return collect($c['pedidos'])->contains(function ($p) use ($asesora) {
                        return $p['asesora_nombre'] === $asesora['asesora_nombre'];
                    });
                })
                ->values();
        @endphp
        @if($asesoraIndex === 0 && $clienteIndex === 0)
        @endif
    @endforeach
@endforeach

@foreach($rankingAsesoras as $asesoraIndex => $asesora)
    <div id="clientesAsesoraModal{{ $asesoraIndex }}" class="stats-modal" aria-hidden="true">
        <div class="stats-modal-panel">
            <div class="stats-modal-header">
                <h3>Clientes de {{ $asesora['asesora_nombre'] }} - {{ $periodoActual }}</h3>
                <button type="button" class="stats-modal-close" data-close-modal>&times;</button>
            </div>
            <div class="stats-modal-body">
                <p class="modal-metric">Total clientes: <strong>{{ number_format($asesora['clientes_unicos'], 0, ',', '.') }}</strong></p>
                <div class="stats-table-wrapper">
                    <table class="stats-table">
                        <thead>
                            <tr>
                                <th>Cliente</th>
                                <th>Pedidos de esta asesora</th>
                                <th>Detalle</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $clientesAsesora = collect($clientesConPedidos)
                                    ->filter(function ($cliente) use ($asesora) {
                                        return collect($cliente['pedidos'])->contains(function ($pedido) use ($asesora) {
                                            return $pedido['asesora_nombre'] === $asesora['asesora_nombre'];
                                        });
                                    })
                                    ->sortByDesc(function ($cliente) use ($asesora) {
                                        return collect($cliente['pedidos'])->filter(function ($p) use ($asesora) {
                                            return $p['asesora_nombre'] === $asesora['asesora_nombre'];
                                        })->count();
                                    })
                                    ->values();
                            @endphp
                            @forelse($clientesAsesora as $cliente)
                                @php
                                    $pedidosAsesora = collect($cliente['pedidos'])
                                        ->filter(fn ($p) => $p['asesora_nombre'] === $asesora['asesora_nombre'])
                                        ->values();
                                @endphp
                                <tr>
                                    <td>{{ $cliente['cliente_nombre'] }}</td>
                                    <td>{{ $pedidosAsesora->count() }}</td>
                                    <td>
                                        <details>
                                            <summary>Ver {{ $pedidosAsesora->count() }} pedido(s)</summary>
                                            <ul class="pedidos-list">
                                                @foreach($pedidosAsesora as $pedido)
                                                    <li>
                                                        <strong>#{{ $pedido['numero_pedido'] }}</strong>
                                                        <span>{{ $pedido['fecha'] }}</span>
                                                        <span>{{ $pedido['estado'] }}</span>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </details>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3">No hay clientes para esta asesora en este periodo.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endforeach

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const periodoSelect = document.getElementById('periodo');
    const gruposMes = document.querySelectorAll('.filtro-mes');
    const gruposAno = document.querySelectorAll('.filtro-ano');
    const gruposRango = document.querySelectorAll('.filtro-rango');

    function toggleFiltros() {
        const value = periodoSelect.value;
        gruposMes.forEach(el => el.classList.toggle('hidden', value !== 'mes'));
        gruposAno.forEach(el => el.classList.toggle('hidden', value === 'rango'));
        gruposRango.forEach(el => el.classList.toggle('hidden', value !== 'rango'));
    }

    periodoSelect?.addEventListener('change', toggleFiltros);
    toggleFiltros();

    document.querySelectorAll('[data-open-modal]').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.getAttribute('data-open-modal');
            const modal = document.getElementById(id);
            if (!modal) return;
            modal.classList.add('is-open');
            modal.setAttribute('aria-hidden', 'false');
        });
    });

    document.querySelectorAll('[data-close-modal]').forEach(btn => {
        btn.addEventListener('click', () => {
            const modal = btn.closest('.stats-modal');
            if (!modal) return;
            modal.classList.remove('is-open');
            modal.setAttribute('aria-hidden', 'true');
        });
    });

    document.querySelectorAll('.stats-modal').forEach(modal => {
        modal.addEventListener('click', (event) => {
            if (event.target !== modal) return;
            modal.classList.remove('is-open');
            modal.setAttribute('aria-hidden', 'true');
        });
    });

    const diferenciaChartCanvas = document.getElementById('diferenciaChart');
    if (diferenciaChartCanvas && window.Chart) {
        const rankingAsesoras = @json($rankingAsesoras);
        const rows = [...rankingAsesoras]
            .map((row) => ({
                asesora: row.asesora_nombre,
                diferencia: Number(row.diferencia || 0),
            }))
            .sort((a, b) => b.diferencia - a.diferencia);

        const labels = rows.map((item) => item.asesora);
        const data = rows.map((item) => item.diferencia);
        const colors = rows.map((item) => item.diferencia >= 0 ? 'rgba(22, 163, 74, 0.8)' : 'rgba(220, 38, 38, 0.82)');
        const borderColors = rows.map((item) => item.diferencia >= 0 ? '#166534' : '#991b1b');
        const maxAbsValue = Math.max(...data.map((value) => Math.abs(value)), 1);

        new Chart(diferenciaChartCanvas, {
            type: 'bar',
            data: {
                labels,
                datasets: [{
                    label: 'Diferencia de pedidos',
                    data,
                    backgroundColor: colors,
                    borderColor: borderColors,
                    borderWidth: 1.2,
                    borderRadius: 7,
                    maxBarThickness: 28,
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        min: -maxAbsValue,
                        max: maxAbsValue,
                        grid: {
                            color: 'rgba(148, 163, 184, 0.24)',
                        },
                        ticks: {
                            callback: (value) => Number(value).toLocaleString('es-CO'),
                            color: '#334155',
                            font: {
                                size: 11,
                            }
                        }
                    },
                    y: {
                        grid: {
                            display: false,
                        },
                        ticks: {
                            color: '#0f172a',
                            font: {
                                size: 11,
                                weight: '600',
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false,
                    },
                    tooltip: {
                        callbacks: {
                            label: (context) => {
                                const value = Number(context.raw || 0);
                                const sign = value >= 0 ? '+' : '';
                                return `${sign}${value.toLocaleString('es-CO')} pedidos`;
                            }
                        }
                    }
                }
            }
        });
    }

    const clientesNuevosCanvas = document.getElementById('clientesNuevosChart');
    if (clientesNuevosCanvas && window.Chart) {
        const clientesNuevos = @json($clientesNuevos);
        const porAsesora = clientesNuevos.reduce((acc, item) => {
            const key = item.asesoras || 'Sin asesora';
            acc[key] = (acc[key] || 0) + 1;
            return acc;
        }, {});

        const rows = Object.entries(porAsesora)
            .map(([asesora, total]) => ({ asesora, total }))
            .sort((a, b) => b.total - a.total)
            .slice(0, 12);
        const totalNuevos = rows.reduce((acc, item) => acc + item.total, 0) || 1;
        const porcentajes = rows.map((item) => (item.total / totalNuevos) * 100);
        const labels = rows.map((item, index) => `${item.asesora} (${porcentajes[index].toFixed(1)}%)`);

        new Chart(clientesNuevosCanvas, {
            type: 'bar',
            data: {
                labels,
                datasets: [{
                    label: 'Clientes nuevos (participacion %)',
                    data: rows.map((item) => item.total),
                    backgroundColor: 'rgba(14, 165, 233, 0.78)',
                    borderColor: '#0369a1',
                    borderWidth: 1.1,
                    borderRadius: 7,
                    maxBarThickness: 34,
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(148, 163, 184, 0.22)',
                        },
                        ticks: {
                            precision: 0,
                            color: '#334155',
                            font: {
                                size: 11,
                            }
                        }
                    },
                    y: {
                        grid: {
                            display: false,
                        },
                        ticks: {
                            color: '#0f172a',
                            font: {
                                size: 11,
                                weight: '600',
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false,
                    },
                    tooltip: {
                        callbacks: {
                            label: (context) => {
                                const clientes = Number(context.raw || 0);
                                const pct = porcentajes[context.dataIndex] || 0;
                                return `${clientes.toLocaleString('es-CO')} clientes (${pct.toFixed(1)}%)`;
                            }
                        }
                    }
                }
            }
        });
    }

    document.querySelectorAll('[data-table-pagination]').forEach((pager) => {
        const tableId = pager.getAttribute('data-table-pagination');
        const pageSize = Number(pager.getAttribute('data-page-size') || 10);
        const table = document.getElementById(tableId);
        if (!table) return;

        const tbody = table.querySelector('tbody');
        const info = pager.querySelector('[data-page-info]');
        const prevBtn = pager.querySelector('[data-page-action="prev"]');
        const nextBtn = pager.querySelector('[data-page-action="next"]');
        if (!tbody || !info || !prevBtn || !nextBtn) return;

        const rows = Array.from(tbody.querySelectorAll('tr'));
        if (rows.length <= pageSize) {
            pager.style.display = 'none';
            return;
        }

        let currentPage = 1;
        const totalPages = Math.ceil(rows.length / pageSize);

        const renderPage = () => {
            const start = (currentPage - 1) * pageSize;
            const end = start + pageSize;

            rows.forEach((row, index) => {
                row.style.display = index >= start && index < end ? '' : 'none';
            });

            info.textContent = `Página ${currentPage} de ${totalPages}`;
            prevBtn.disabled = currentPage === 1;
            nextBtn.disabled = currentPage === totalPages;
        };

        prevBtn.addEventListener('click', () => {
            if (currentPage <= 1) return;
            currentPage -= 1;
            renderPage();
        });

        nextBtn.addEventListener('click', () => {
            if (currentPage >= totalPages) return;
            currentPage += 1;
            renderPage();
        });

        renderPage();
    });
});
</script>
@endpush
@endsection
