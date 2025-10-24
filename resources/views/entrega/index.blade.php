@extends('layouts.app')

@section('content')
<style>
    /* Paleta de colores del sistema */
    :root {
        --bg1: #0f172a;
        --accent: #f97316;
        --card: rgba(255,255,255,0.06);
        --muted: rgba(255,255,255,0.7);
        --border: rgba(255,255,255,0.08);
    }

    .ep-container {
        min-height: 70vh;
        padding: 2rem;
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
    }

    .ep-card {
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: 16px;
        padding: 1.5rem;
        backdrop-filter: blur(10px);
        box-shadow: 0 8px 32px rgba(0,0,0,0.3);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .ep-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 40px rgba(0,0,0,0.4);
    }

    .filter-input {
        background: rgba(255,255,255,0.04);
        border: 1px solid var(--border);
        padding: 0.75rem 1rem;
        border-radius: 12px;
        color: var(--muted);
        font-size: 0.9rem;
    }

    .filter-input:focus {
        outline: none;
        border-color: var(--accent);
        box-shadow: 0 0 0 2px rgba(249, 115, 22, 0.2);
    }

    .btn-primary {
        background: linear-gradient(90deg, #ff9d58 0%, #f97316 100%);
        color: white;
        padding: 0.75rem 1.5rem;
        border-radius: 12px;
        border: none;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .btn-primary:hover {
        transform: scale(1.05);
        box-shadow: 0 4px 20px rgba(249, 115, 22, 0.4);
    }

    .btn-secondary {
        background: rgba(255,255,255,0.1);
        color: var(--muted);
        padding: 0.5rem 1rem;
        border-radius: 8px;
        border: 1px solid var(--border);
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
        font-size: 0.85rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .btn-secondary:hover {
        background: rgba(255,255,255,0.15);
        border-color: var(--accent);
        color: white;
    }

    .stat-card {
        background: linear-gradient(135deg, rgba(249, 115, 22, 0.1) 0%, rgba(249, 115, 22, 0.05) 100%);
        border: 1px solid rgba(249, 115, 22, 0.2);
        border-radius: 12px;
        padding: 1rem;
        text-align: center;
        color: white;
    }

    .stat-number {
        font-size: 2rem;
        font-weight: bold;
        color: var(--accent);
    }

    .stat-label {
        font-size: 0.9rem;
        color: var(--muted);
        margin-top: 0.5rem;
    }

    .modern-table {
        width: 100%;
        border-collapse: collapse;
        background: var(--card);
        border-radius: 12px;
        overflow: hidden;
    }

    .modern-table th,
    .modern-table td {
        padding: 0.75rem 1rem;
        text-align: left;
        border-bottom: 1px solid var(--border);
    }

    .modern-table th {
        background: rgba(249, 115, 22, 0.1);
        color: white;
        font-weight: 600;
    }

    .modern-table td {
        color: var(--muted);
    }

    .chart-container {
        position: relative;
        height: 400px;
        background: var(--card);
        border-radius: 12px;
        padding: 1rem;
        margin-top: 1rem;
    }

    .section-title {
        font-size: 1.5rem;
        font-weight: bold;
        color: white;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .section-title::before {
        content: '';
        width: 4px;
        height: 24px;
        background: var(--accent);
        border-radius: 2px;
    }

    @media (max-width: 768px) {
        .ep-container {
            padding: 1rem;
        }

        .ep-card {
            padding: 1rem;
        }

        .chart-container {
            height: 300px;
        }
    }
</style>

<div class="ep-container">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <header class="mb-8 text-center">
            <h1 class="text-4xl font-bold text-white mb-2">{{ $config['titulo'] }}</h1>
            <p class="text-gray-300 text-lg">Monitorea y gestiona las entregas de costura y corte en tiempo real</p>
        </header>

        <!-- Filtro de Fecha -->
        <div class="ep-card mb-8">
            <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                <div>
                    <h3 class="text-xl font-semibold text-white mb-2">Filtrar por Fecha</h3>
                    <p class="text-gray-400 text-sm">Selecciona la fecha para ver las entregas correspondientes</p>
                </div>
                <div class="flex items-center gap-4">
                    <input type="date" id="fechaFilter" class="filter-input" value="{{ $fecha }}">
                    <button id="filtrarBtn" class="btn-primary">Filtrar</button>
                    <button id="registrarEntregaBtn" class="btn-secondary">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" class="w-4 h-4">
                            <path d="M12 4v16m8-8H4" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                        Registrar
                    </button>
                </div>
            </div>
        </div>

        <!-- Sección Costura -->
        <section class="mb-12">
            <h2 class="section-title">
                <i class="fas fa-cut"></i>
                {{ $config['seccionCostura'] }}
            </h2>

            <!-- Estadísticas -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div class="stat-card">
                    <div class="stat-number" id="costura-total">0</div>
                    <div class="stat-label">Total Prendas</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" id="costura-prendas">0</div>
                    <div class="stat-label">Prendas Diferentes</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" id="costura-costureros">0</div>
                    <div class="stat-label">Costureros Activos</div>
                </div>
            </div>

            <!-- Tabla y Gráfico -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="ep-card">
                    <h3 class="text-lg font-semibold text-white mb-4">Registros de Entregas</h3>
                    <div class="overflow-x-auto">
                        <table class="modern-table" id="costura-table">
                            <thead>
                                <tr>
                                    <th>Pedido</th>
                                    <th>Cliente</th>
                                    <th>Prenda</th>
                                    <th>Cantidad</th>
                                    <th>Costurero</th>
                                </tr>
                            </thead>
                            <tbody id="costura-tbody">
                                @foreach($costura as $item)
                                <tr>
                                    <td>{{ $item->pedido }}</td>
                                    <td>{{ $item->cliente }}</td>
                                    <td>{{ $item->prenda }}</td>
                                    <td>{{ $item->cantidad_entregada }}</td>
                                    <td>{{ $item->costurero }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="ep-card">
                    <h3 class="text-lg font-semibold text-white mb-4">Entregas por Costurero</h3>
                    <div class="chart-container">
                        <canvas id="costura-chart"></canvas>
                    </div>
                </div>
            </div>
        </section>

        <!-- Sección Corte -->
        <section>
            <h2 class="section-title">
                <i class="fas fa-scissors"></i>
                {{ $config['seccionCorte'] }}
            </h2>

            <!-- Estadísticas -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div class="stat-card">
                    <div class="stat-number" id="corte-total">0</div>
                    <div class="stat-label">Total Piezas</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" id="corte-etiqueteadas">0</div>
                    <div class="stat-label">Piezas etiqueteadas</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" id="corte-pares">0</div>
                    <div class="stat-label">Pares Cortador-Etiquetador</div>
                </div>
            </div>

            <!-- Tabla y Gráfico -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="ep-card">
                    <h3 class="text-lg font-semibold text-white mb-4">Registros de Entregas</h3>
                    <div class="overflow-x-auto">
                        <table class="modern-table" id="corte-table">
                            <thead>
                                <tr>
                                    <th>Pedido</th>
                                    <th>Cortador</th>
                                    <th>Piezas</th>
                                    <th>etiqueteadas</th>
                                    <th>Etiquetador</th>
                                </tr>
                            </thead>
                            <tbody id="corte-tbody">
                                @foreach($corte as $item)
                                <tr>
                                    <td>{{ $item->pedido }}</td>
                                    <td>{{ $item->cortador }}</td>
                                    <td>{{ $item->piezas }}</td>
                                    <td>{{ $item->etiqueteadas }}</td>
                                    <td>{{ $item->etiquetador }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="ep-card">
                    <h3 class="text-lg font-semibold text-white mb-4">Piezas por Cortador-Etiquetador</h3>
                    <div class="chart-container">
                        <canvas id="corte-chart"></canvas>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>

<!-- Modal Component -->
<x-entrega-form-modal />

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const COLORS = ['#ff9d58', '#4f46e5', '#06b6d4', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899'];
const TIPO = '{{ $tipo }}';

let costuraChart, corteChart;

document.addEventListener('DOMContentLoaded', function() {
    initCharts();
    updateStats();
    document.getElementById('filtrarBtn').addEventListener('click', filtrarDatos);
    document.getElementById('fechaFilter').addEventListener('change', filtrarDatos);
    document.getElementById('registrarEntregaBtn').addEventListener('click', openEntregaModal);
});

function openEntregaModal() {
    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'entrega-form' }));
}

async function filtrarDatos() {
    const fecha = document.getElementById('fechaFilter').value;
    try {
        const [costuraRes, corteRes] = await Promise.all([
            fetch(`/entrega/${TIPO}/costura-data?fecha=${fecha}`),
            fetch(`/entrega/${TIPO}/corte-data?fecha=${fecha}`)
        ]);

        const costuraData = await costuraRes.json();
        const corteData = await corteRes.json();

        updateCosturaTable(costuraData);
        updateCorteTable(corteData);
        updateCharts(costuraData, corteData);
        updateStats(costuraData, corteData);
    } catch (error) {
        console.error('Error al filtrar datos:', error);
    }
}

function initCharts() {
    const costuraCtx = document.getElementById('costura-chart').getContext('2d');
    costuraChart = new Chart(costuraCtx, {
        type: 'bar',
        data: { labels: [], datasets: [{ label: 'Entregas', data: [], backgroundColor: COLORS }] },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.1)' }, ticks: { color: '#fff' } },
                x: { grid: { color: 'rgba(255,255,255,0.1)' }, ticks: { color: '#fff' } }
            }
        }
    });

    const corteCtx = document.getElementById('corte-chart').getContext('2d');
    corteChart = new Chart(corteCtx, {
        type: 'bar',
        data: { labels: [], datasets: [] },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { color: '#fff', padding: 20 } }
            },
            scales: {
                x: {
                    stacked: true,
                    ticks: { color: '#fff' },
                    grid: { color: 'rgba(255,255,255,0.1)' }
                },
                y: {
                    stacked: true,
                    beginAtZero: true,
                    ticks: { color: '#fff' },
                    grid: { color: 'rgba(255,255,255,0.1)' }
                }
            }
        }
    });
}

function updateCosturaTable(data) {
    const tbody = document.getElementById('costura-tbody');
    tbody.innerHTML = data.map(item => `
        <tr>
            <td>${item.pedido || ''}</td>
            <td>${item.cliente || ''}</td>
            <td>${item.prenda || ''}</td>
            <td>${item.cantidad_entregada || 0}</td>
            <td>${item.costurero || ''}</td>
        </tr>
    `).join('');
}

function updateCorteTable(data) {
    const tbody = document.getElementById('corte-tbody');
    tbody.innerHTML = data.map(item => `
        <tr>
            <td>${item.pedido || ''}</td>
            <td>${item.cortador || ''}</td>
            <td>${item.piezas || 0}</td>
            <td>${item.etiqueteadas || 0}</td>
            <td>${item.etiquetador || ''}</td>
        </tr>
    `).join('');
}

function updateCharts(costuraData, corteData) {
    // Costura chart: entregas por costurero
    const costuraGrouped = costuraData.reduce((acc, item) => {
        acc[item.costurero] = (acc[item.costurero] || 0) + item.cantidad_entregada;
        return acc;
    }, {});

    costuraChart.data.labels = Object.keys(costuraGrouped);
    costuraChart.data.datasets[0].data = Object.values(costuraGrouped);
    costuraChart.update();

    // Corte chart: stacked bar con colores únicos por cortador-etiquetador para piezas, pasadas y etiqueteadas
    const corteGrouped = corteData.reduce((acc, item) => {
        const key = `${item.cortador} - ${item.etiquetador}`;
        if (!acc[key]) {
            acc[key] = { piezas: 0, pasadas: 0, etiqueteadas: 0 };
        }
        acc[key].piezas += item.piezas || 0;
        acc[key].pasadas += item.pasadas || 0;
        acc[key].etiqueteadas += item.etiqueteadas || 0;
        return acc;
    }, {});

    const labels = ['Piezas', 'Pasadas', 'Etiquetadas'];
    const keys = Object.keys(corteGrouped);
    const colorPalette = [
        '#ff9d58', '#f97316', '#ef4444', '#4f46e5', '#06b6d4', '#10b981', '#f59e0b', '#8b5cf6', '#ec4899', '#db2777'
    ];

    // Crear datasets para cada cortador-etiquetador con colores únicos
    const datasets = keys.map((key, index) => {
        const color = colorPalette[index % colorPalette.length];
        return {
            label: key,
            data: [
                corteGrouped[key].piezas,
                corteGrouped[key].pasadas,
                corteGrouped[key].etiqueteadas
            ],
            backgroundColor: color
        };
    });

    corteChart.data.labels = labels;
    corteChart.data.datasets = datasets;
    corteChart.update();
}

function updateStats(costuraData = [], corteData = []) {
    // Costura stats
    const totalCostura = costuraData.reduce((sum, item) => sum + item.cantidad_entregada, 0);
    const prendasCostura = new Set(costuraData.map(item => item.prenda)).size;
    const costureros = new Set(costuraData.map(item => item.costurero)).size;

    document.getElementById('costura-total').textContent = totalCostura;
    document.getElementById('costura-prendas').textContent = prendasCostura;
    document.getElementById('costura-costureros').textContent = costureros;

    // Corte stats
    const totalCorte = corteData.reduce((sum, item) => sum + item.piezas, 0);
    const etiqueteadas = corteData.reduce((sum, item) => sum + item.etiqueteadas, 0);
    const pares = corteData.length;

    document.getElementById('corte-total').textContent = totalCorte;
    document.getElementById('corte-etiqueteadas').textContent = etiqueteadas;
    document.getElementById('corte-pares').textContent = pares;
}
</script>
@endsection
