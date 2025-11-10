@extends('asesores.layout')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard de Pedidos')

@section('content')
<div class="dashboard-container">
    <!-- Tarjetas de Estadísticas -->
    <div class="stats-grid">
        <div class="stat-card stat-day">
            <div class="stat-icon">
                <i class="fas fa-calendar-day"></i>
            </div>
            <div class="stat-content">
                <h3>Pedidos Hoy</h3>
                <p class="stat-value">{{ $stats['pedidos_dia'] }}</p>
                <span class="stat-trend">
                    <i class="fas fa-arrow-up"></i>
                    <span>0%</span>
                </span>
            </div>
        </div>

        <div class="stat-card stat-month">
            <div class="stat-icon">
                <i class="fas fa-calendar-alt"></i>
            </div>
            <div class="stat-content">
                <h3>Pedidos Este Mes</h3>
                <p class="stat-value">{{ $stats['pedidos_mes'] }}</p>
                <span class="stat-trend">
                    <i class="fas fa-arrow-up"></i>
                    <span>0%</span>
                </span>
            </div>
        </div>

        <div class="stat-card stat-year">
            <div class="stat-icon">
                <i class="fas fa-calendar"></i>
            </div>
            <div class="stat-content">
                <h3>Pedidos Este Año</h3>
                <p class="stat-value">{{ $stats['pedidos_anio'] }}</p>
                <span class="stat-trend">
                    <i class="fas fa-arrow-up"></i>
                    <span>0%</span>
                </span>
            </div>
        </div>

        <div class="stat-card stat-pending">
            <div class="stat-icon">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-content">
                <h3>Pedidos Pendientes</h3>
                <p class="stat-value">{{ $stats['pedidos_pendientes'] }}</p>
                <span class="stat-label">En proceso</span>
            </div>
        </div>
    </div>

    <!-- Gráficas -->
    <div class="charts-grid">
        <!-- Gráfica de Tendencia -->
        <div class="chart-card">
            <div class="chart-header">
                <h2>Tendencia de Pedidos</h2>
                <div class="chart-actions">
                    <button class="chart-btn active" data-period="7">7D</button>
                    <button class="chart-btn" data-period="30">30D</button>
                    <button class="chart-btn" data-period="90">90D</button>
                </div>
            </div>
            <div class="chart-body">
                <canvas id="ordenesLineChart"></canvas>
            </div>
        </div>

        <!-- Gráfica de Estados -->
        <div class="chart-card">
            <div class="chart-header">
                <h2>Pedidos por Estado</h2>
            </div>
            <div class="chart-body">
                <canvas id="estadosDoughnutChart"></canvas>
            </div>
        </div>

        <!-- Gráfica de Asesores -->
        <div class="chart-card">
            <div class="chart-header">
                <h2>Top 10 Asesores</h2>
            </div>
            <div class="chart-body">
                <canvas id="asesoresBarChart"></canvas>
            </div>
        </div>

        <!-- Comparativa Semanal -->
        <div class="chart-card">
            <div class="chart-header">
                <h2>Comparativa Semanal</h2>
            </div>
            <div class="chart-body">
                <div class="comparison-container">
                    <div class="comparison-item">
                        <span class="comparison-label">Semana Actual</span>
                        <span class="comparison-value" id="semanaActual">0</span>
                        <div class="comparison-bar">
                            <div class="bar-fill actual" id="barActual" style="width: 0%"></div>
                        </div>
                    </div>
                    <div class="comparison-item">
                        <span class="comparison-label">Semana Anterior</span>
                        <span class="comparison-value" id="semanaAnterior">0</span>
                        <div class="comparison-bar">
                            <div class="bar-fill anterior" id="barAnterior" style="width: 0%"></div>
                        </div>
                    </div>
                    <div class="comparison-result">
                        <div class="result-icon" id="resultIcon">
                            <i class="fas fa-arrow-up"></i>
                        </div>
                        <div class="result-text">
                            <span class="result-percentage" id="resultPercentage">0%</span>
                            <span class="result-label" id="resultLabel">de incremento</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Acciones Rápidas -->
    <div class="quick-actions">
        <h2>Acciones Rápidas</h2>
        <div class="actions-grid">
            <a href="{{ route('asesores.pedidos.create') }}" class="action-card">
                <div class="action-icon">
                    <i class="fas fa-plus-circle"></i>
                </div>
                <div class="action-content">
                    <h4>Nuevo Pedido</h4>
                    <p>Crear un nuevo pedido de dotación</p>
                </div>
            </a>

            <a href="{{ route('asesores.pedidos.index') }}" class="action-card">
                <div class="action-icon">
                    <i class="fas fa-list"></i>
                </div>
                <div class="action-content">
                    <h4>Mis Pedidos</h4>
                    <p>Ver todos mis pedidos</p>
                </div>
            </a>

            <a href="{{ route('asesores.pedidos.index') }}?estado=En Ejecución" class="action-card">
                <div class="action-icon">
                    <i class="fas fa-tasks"></i>
                </div>
                <div class="action-content">
                    <h4>En Proceso</h4>
                    <p>Pedidos en ejecución</p>
                </div>
            </a>

            <a href="{{ route('asesores.pedidos.index') }}?estado=Entregado" class="action-card">
                <div class="action-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="action-content">
                    <h4>Entregados</h4>
                    <p>Pedidos completados</p>
                </div>
            </a>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="{{ asset('js/asesores/dashboard-charts.js') }}"></script>
@endpush
