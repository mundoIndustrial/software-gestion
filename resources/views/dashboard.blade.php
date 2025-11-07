@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="{{ asset('css/dashboard styles/dashboard.css') }}">

<div class="dashboard-container">
    <div class="header-compact animate-in">
        <div>
            <h1>Dashboard de Entregas</h1>
            <p class="welcome">Bienvenido, {{ auth()->user()->name }}</p>
        </div>
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

        <div class="chart-card animate-in notifications-section">
            <div class="notifications-header">
                <div class="notifications-title-section">
                    <div class="notifications-icon">
                        <span class="material-symbols-rounded">notifications</span>
                    </div>
                    <div>
                        <h2>Notificaciones</h2>
                        <span class="notifications-count" id="news-count">0 nuevas</span>
                    </div>
                </div>
                <div class="notifications-actions">
                    <input type="date" id="news-date-filter" class="filter-date" />
                    <button class="mark-all-read" id="mark-all-read" title="Marcar todas como leídas">
                        <span class="material-symbols-rounded">done_all</span>
                    </button>
                </div>
            </div>
            
            <div class="notifications-tabs">
                <button class="notification-tab active" data-filter="all" id="tab-all">
                    Todas <span class="tab-count" id="count-all">0</span>
                </button>
                <button class="notification-tab" data-filter="unread" id="tab-unread">
                    No leídas <span class="tab-count" id="count-unread">0</span>
                </button>
                <button class="notification-tab" data-filter="read" id="tab-read">
                    Leídas <span class="tab-count" id="count-read">0</span>
                </button>
            </div>
            
            <div class="news-compact" id="news-feed"></div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="{{ asset('js/dashboard js/dashboard.js') }}"></script>
@endsection