@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="{{ asset('css/dashboard-styles/dashboard.css') }}">
<div class="dashboard-container">

    <div class="d-flex justify-content-between align-items-center mb-3 animate-in">
        <h2 class="mb-0 text-primary font-weight-bold" style="font-size: 1.5rem; margin-bottom: 0;">Resumen del Dashboard</h2>
        <a href="{{ route('dashboard.timeline-pedidos') }}" class="btn btn-primary d-flex align-items-center" style="gap: 5px;">
            <span class="material-symbols-rounded">timeline</span> Generar Reporte
        </a>
    </div>

    <div class="kpis-row animate-in">
        <div class="kpi-card">
            <h3>Total Órdenes</h3>
            <p id="total-orders" class="value">0</p>
        </div>
        <div class="kpi-card">
            <h3>Órdenes Completadas</h3>
            <p id="ordenes-completadas" class="value">0</p>
        </div>
        <div class="kpi-card">
            <h3>Órdenes Pendientes</h3>
            <p id="ordenes-pendientes" class="value">0</p>
        </div>
    </div>

    <div class="main-grid">
        <div class="chart-card animate-in">
            <div class="chart-header">
                <h2 id="costura-title">Entregas Costura</h2>
                <button id="costura-toggle" class="toggle-btn">Bodega</button>
            </div>
            <div class="filters-inline" id="costura-filters"></div>
            <div class="chart-body">
                <canvas id="costura-chart"></canvas>
            </div>
        </div>

        <div class="chart-card animate-in">
            <div class="chart-header">
                <h2 id="corte-title">Piezas Etiquetadas</h2>
                <button id="corte-toggle" class="toggle-btn">Bodega</button>
            </div>
            <div class="filters-inline" id="corte-filters"></div>
            <div class="chart-body">
                <canvas id="corte-chart"></canvas>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="{{ asset('js/dashboard-js/dashboard.js') }}"></script>
<script>
// Dashboard logic
</script>
@endsection
