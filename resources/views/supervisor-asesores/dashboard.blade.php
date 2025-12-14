@extends('layouts.supervisor-asesores')

@section('title', 'Dashboard - Supervisor de Asesores')
@section('page-title', 'Dashboard de Supervisor de Asesores')

@section('content')
<div class="dashboard-container">
    <!-- Tarjetas de Estadísticas -->
    <div class="stats-grid">
        <div class="stat-card stat-day">
            <div class="stat-icon">
                <i class="fas fa-chart-line"></i>
            </div>
            <div class="stat-content">
                <h3>Cotizaciones Hoy</h3>
                <p class="stat-value" id="cotizacionesHoy">0</p>
                <span class="stat-trend">
                    <i class="fas fa-arrow-up"></i>
                    <span id="cotizacionesHoyTend">0%</span>
                </span>
            </div>
        </div>

        <div class="stat-card stat-month">
            <div class="stat-icon">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <div class="stat-content">
                <h3>Pedidos Este Mes</h3>
                <p class="stat-value" id="pedidosMes">0</p>
                <span class="stat-trend">
                    <i class="fas fa-arrow-up"></i>
                    <span id="pedidosMesTend">0%</span>
                </span>
            </div>
        </div>

        <div class="stat-card stat-year">
            <div class="stat-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-content">
                <h3>Asesores Activos</h3>
                <p class="stat-value" id="asesoresActivos">0</p>
                <span class="stat-trend">
                    <i class="fas fa-info-circle"></i>
                    <span>Equipo</span>
                </span>
            </div>
        </div>

        <div class="stat-card stat-pending">
            <div class="stat-icon">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-content">
                <h3>Pedidos Pendientes</h3>
                <p class="stat-value" id="pedidosPendientes">0</p>
                <span class="stat-label">En proceso</span>
            </div>
        </div>

        <div class="stat-card stat-month">
            <div class="stat-icon">
                <i class="fas fa-file-invoice"></i>
            </div>
            <div class="stat-content">
                <h3>Cotizaciones Este Mes</h3>
                <p class="stat-value" id="cotizacionesMes">0</p>
                <span class="stat-trend">
                    <i class="fas fa-arrow-up"></i>
                    <span id="cotizacionesMesTend">0%</span>
                </span>
            </div>
        </div>

        <div class="stat-card stat-month">
            <div class="stat-icon">
                <i class="fas fa-boxes"></i>
            </div>
            <div class="stat-content">
                <h3>Total Pedidos Mes</h3>
                <p class="stat-value" id="totalPedidosMes">0</p>
                <span class="stat-trend">
                    <i class="fas fa-arrow-up"></i>
                    <span id="totalPedidosMesTend">0%</span>
                </span>
            </div>
        </div>
    </div>

    <!-- Gráficas -->
    <div class="charts-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; width: 100%; margin-top: 2rem;">
        <!-- Gráfica de Tendencia -->
        <div class="chart-card" style="grid-column: span 2;">
            <div class="chart-header">
                <h2>Tendencia de Cotizaciones y Pedidos</h2>
                <div class="chart-actions">
                    <button class="chart-btn active" data-period="7">7D</button>
                    <button class="chart-btn" data-period="30">30D</button>
                    <button class="chart-btn" data-period="90">90D</button>
                </div>
            </div>
            <div class="chart-body">
                <canvas id="tendenciaLineChart"></canvas>
            </div>
        </div>

        <!-- Gráfica de Estados -->
        <div class="chart-card">
            <div class="chart-header">
                <div style="display: flex; justify-content: space-between; align-items: center; width: 100%; gap: 1rem; flex-wrap: wrap;">
                    <h2 style="margin: 0; font-size: 1.1rem;">Pedidos por Estado</h2>
                    <div class="filtros-estado" style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
                        <input type="date" id="filtroFechaDesde" style="padding: 0.4rem; border: 1px solid #ddd; border-radius: 4px; font-size: 0.85rem;">
                        <span style="color: #666; font-size: 0.85rem;">a</span>
                        <input type="date" id="filtroFechaHasta" style="padding: 0.4rem; border: 1px solid #ddd; border-radius: 4px; font-size: 0.85rem;">
                        <select id="filtroMes" style="padding: 0.4rem; border: 1px solid #ddd; border-radius: 4px; font-size: 0.85rem;">
                            <option value="">Todo</option>
                            <option value="1">Enero</option>
                            <option value="2">Febrero</option>
                            <option value="3">Marzo</option>
                            <option value="4">Abril</option>
                            <option value="5">Mayo</option>
                            <option value="6">Junio</option>
                            <option value="7">Julio</option>
                            <option value="8">Agosto</option>
                            <option value="9">Septiembre</option>
                            <option value="10">Octubre</option>
                            <option value="11">Noviembre</option>
                            <option value="12">Diciembre</option>
                        </select>
                        <button id="btnLimpiarFiltros" style="padding: 0.4rem 0.8rem; background: #999; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 0.85rem;">Limpiar</button>
                    </div>
                </div>
            </div>
            <div class="chart-body" style="height: 300px; display: flex; align-items: center; justify-content: center;">
                <canvas id="estadosPedidosChart"></canvas>
            </div>
        </div>

        <!-- Gráfica de Cotizaciones por Asesor -->
        <div class="chart-card">
            <div class="chart-header">
                <h2>Cotizaciones por Asesor (Top 10)</h2>
            </div>
            <div class="chart-body" style="height: 300px;">
                <canvas id="cotizacionesAsesoresChart"></canvas>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
    let charts = {};

    document.addEventListener('DOMContentLoaded', function() {
        cargarEstadisticas();
        cargarGraficas(7); // Por defecto 7 días
        cargarGraficaEstados(); // Carga inicial sin filtros

        // Event listeners para botones de período
        document.querySelectorAll('.chart-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.chart-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                const periodo = parseInt(this.dataset.period);
                cargarGraficas(periodo);
            });
        });

        // Event listeners para filtros de estados
        document.getElementById('filtroFechaDesde').addEventListener('change', cargarGraficaEstados);
        document.getElementById('filtroFechaHasta').addEventListener('change', cargarGraficaEstados);
        document.getElementById('filtroMes').addEventListener('change', cargarGraficaEstados);
        document.getElementById('btnLimpiarFiltros').addEventListener('click', limpiarFiltros);
    });

    function limpiarFiltros() {
        document.getElementById('filtroFechaDesde').value = '';
        document.getElementById('filtroFechaHasta').value = '';
        document.getElementById('filtroMes').value = '';
        cargarGraficaEstados();
    }

    function cargarEstadisticas() {
        fetch('{{ route("supervisor-asesores.dashboard-stats") }}')
            .then(response => response.json())
            .then(data => {
                document.getElementById('cotizacionesHoy').textContent = data.cotizaciones_hoy || 0;
                document.getElementById('cotizacionesHoyTend').textContent = '0%';
                
                document.getElementById('pedidosMes').textContent = data.pedidos_mes || 0;
                document.getElementById('pedidosMesTend').textContent = '0%';
                
                document.getElementById('asesoresActivos').textContent = data.total_asesores || 0;
                
                document.getElementById('pedidosPendientes').textContent = data.pedidos_pendientes || 0;

                document.getElementById('cotizacionesMes').textContent = data.cotizaciones_mes || 0;
                document.getElementById('cotizacionesMesTend').textContent = '0%';

                document.getElementById('totalPedidosMes').textContent = data.total_pedidos_mes || 0;
                document.getElementById('totalPedidosMesTend').textContent = '0%';
            })
            .catch(error => console.error('Error cargando estadísticas:', error));
    }

    function cargarGraficas(dias) {
        fetch('{{ route("supervisor-asesores.dashboard-stats") }}?dias=' + dias)
            .then(response => response.json())
            .then(data => {
                crearGraficaTendencia(data);
                crearGraficaCotizacionesAsesores(data);
            })
            .catch(error => console.error('Error cargando gráficas:', error));
    }

    function crearGraficaTendencia(data) {
        const ctx = document.getElementById('tendenciaLineChart');
        if (!ctx) return;

        if (charts.tendencia) charts.tendencia.destroy();

        charts.tendencia = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.labels || [],
                datasets: [
                    {
                        label: 'Cotizaciones',
                        data: data.cotizaciones_por_dia || [],
                        borderColor: '#667eea',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: '#667eea',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2
                    },
                    {
                        label: 'Pedidos',
                        data: data.pedidos_por_dia || [],
                        borderColor: '#f5576c',
                        backgroundColor: 'rgba(245, 87, 108, 0.1)',
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: '#f5576c',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: true,
                        labels: { color: 'var(--text-primary)' }
                    }
                },
                scales: {
                    y: { beginAtZero: true, ticks: { color: 'var(--text-secondary)' } },
                    x: { ticks: { color: 'var(--text-secondary)' } }
                }
            }
        });
    }

    function crearGraficaCotizacionesAsesores(data) {
        const ctx = document.getElementById('cotizacionesAsesoresChart');
        if (!ctx) return;

        if (charts.asesores) charts.asesores.destroy();

        charts.asesores = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.asesores_labels || [],
                datasets: [{
                    label: 'Cotizaciones',
                    data: data.asesores_data || [],
                    backgroundColor: '#667eea',
                    borderRadius: 5
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: { beginAtZero: true, ticks: { color: 'var(--text-secondary)' } },
                    y: { ticks: { color: 'var(--text-secondary)' } }
                }
            }
        });
    }

    function cargarGraficaEstados() {
        const fechaDesde = document.getElementById('filtroFechaDesde').value;
        const fechaHasta = document.getElementById('filtroFechaHasta').value;
        const mes = document.getElementById('filtroMes').value;

        let url = '{{ route("supervisor-asesores.dashboard-stats") }}?estados_filter=true';
        if (fechaDesde) url += '&fecha_desde=' + fechaDesde;
        if (fechaHasta) url += '&fecha_hasta=' + fechaHasta;
        if (mes) url += '&mes=' + mes;

        fetch(url)
            .then(response => response.json())
            .then(data => crearGraficaEstados(data))
            .catch(error => console.error('Error cargando gráfica de estados:', error));
    }

    function crearGraficaEstados(data) {
        const ctx = document.getElementById('estadosPedidosChart');
        if (!ctx) return;

        if (charts.estados) charts.estados.destroy();

        const colores = ['#667eea', '#f5576c', '#f59e0b', '#10b981', '#06b6d4', '#8b5cf6'];

        charts.estados = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: data.estados_labels || [],
                datasets: [{
                    data: data.estados_data || [],
                    backgroundColor: colores.slice(0, (data.estados_data || []).length)
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        labels: { color: 'var(--text-primary)' }
                    }
                }
            }
        });
    }
</script>
@endpush
@endsection
