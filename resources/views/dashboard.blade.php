@extends('layouts.app')

@section('content')
<style>
    .filter-select option { color: black !important; background-color: white !important; }
    body { background: #111827; overflow-x: hidden; margin: 0; padding: 0; }
    
    .dashboard-container {
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        padding: 1rem;
        gap: 0.75rem;
    }

    .header-compact {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.5rem 1rem;
        background: #1f2937;
        border-radius: 12px;
        border: 1px solid #374151;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .header-compact h1 { font-size: 1.125rem; font-weight: 700; color: white; margin: 0; }
    .header-compact .welcome { font-size: 0.7rem; color: #94a3b8; }

    .main-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        grid-template-rows: 1fr auto;
        gap: 0.75rem;
        flex: 1;
        overflow: hidden;
    }

    .notifications-section { grid-column: 1 / -1; }

    .kpis-row {
        grid-column: 1 / -1;
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 0.5rem;
    }

    .kpi-card {
        background: #1f2937;
        border: 1px solid #374151;
        border-radius: 12px;
        padding: 0.75rem;
        text-align: center;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .kpi-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(90deg, #FF6B35, #ff8555, #ffa075);
    }

    .kpi-card:hover {
        transform: translateY(-3px);
        border-color: #FF6B35;
        box-shadow: 0 8px 20px rgba(255, 107, 53, 0.3);
        background: #252f3f;
    }

    .kpi-card h3 {
        font-size: 0.6rem;
        font-weight: 600;
        color: #94a3b8;
        margin: 0 0 0.35rem 0;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .kpi-card .value { font-size: 1.3rem; font-weight: 700; color: #FF6B35; margin: 0; text-shadow: 0 0 20px rgba(255, 107, 53, 0.3); }

    .chart-card {
        background: #1f2937;
        border: 1px solid #374151;
        border-radius: 12px;
        padding: 0.9rem;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .chart-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.75rem;
        flex-shrink: 0;
    }

    .chart-header h2 { font-size: 0.8rem; font-weight: 600; color: white; margin: 0; }

    .toggle-btn {
        padding: 0.3rem 0.6rem;
        background: linear-gradient(135deg, #f97316, #ec4899);
        color: white;
        border: none;
        border-radius: 6px;
        font-size: 0.6rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        box-shadow: 0 4px 12px rgba(249, 115, 22, 0.3);
    }

    .toggle-btn:hover { background: linear-gradient(135deg, #fb923c, #f472b6); transform: scale(1.05); box-shadow: 0 6px 16px rgba(249, 115, 22, 0.4); }
    .chart-body { flex: 1; position: relative; overflow: hidden; }

    .filters-inline {
        display: flex;
        gap: 0.35rem;
        margin-bottom: 0.5rem;
        flex-shrink: 0;
    }

    .filters-inline select,
    .filters-inline input {
        flex: 1;
        padding: 0.35rem;
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 107, 53, 0.2);
        border-radius: 4px;
        color: white;
        font-size: 0.6rem;
    }

    .filters-inline select option { color: black; background: white; }

    .news-compact {
        max-height: 100%;
        overflow-y: auto;
        scrollbar-width: thin;
        scrollbar-color: #FF6B35 rgba(255, 255, 255, 0.05);
    }

    .news-compact::-webkit-scrollbar { width: 4px; }
    .news-compact::-webkit-scrollbar-track { background: rgba(255, 255, 255, 0.05); border-radius: 2px; }
    .news-compact::-webkit-scrollbar-thumb { background: #FF6B35; border-radius: 2px; }

    .news-item {
        background: #111827;
        border-left: 3px solid #FF6B35;
        padding: 0.5rem;
        margin-bottom: 0.35rem;
        border-radius: 6px;
        transition: all 0.2s;
    }

    .news-item:hover { background: #1f2937; transform: translateX(5px); box-shadow: 0 4px 12px rgba(255, 107, 53, 0.2); border-left-color: #ff8555; }
    .news-item .title { color: white; font-size: 0.65rem; font-weight: 600; margin-bottom: 0.2rem; }
    .news-item .meta { display: flex; justify-content: space-between; font-size: 0.55rem; color: #94a3b8; }

    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .animate-in { animation: fadeInUp 0.5s ease-out forwards; }
</style>

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
            <div class="chart-header">
                <h2>Notificaciones</h2>
            </div>
            <div class="news-compact" id="news-feed"></div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// ===== CONFIG =====
const CONFIG = {
    colors: [
        '#38bdf8', // Cyan brillante
        '#8b5cf6', // Púrpura vibrante
        '#ec4899', // Rosa fucsia
        '#f97316', // Naranja intenso
        '#10b981', // Verde esmeralda
        '#f59e0b', // Ámbar dorado
        '#6366f1', // Índigo
        '#14b8a6', // Teal
        '#ef4444', // Rojo brillante
        '#a855f7', // Púrpura claro
        '#06b6d4', // Cyan oscuro
        '#84cc16', // Lima
        '#f472b6', // Rosa claro
        '#fb923c', // Naranja claro
        '#22c55e', // Verde brillante
        '#eab308', // Amarillo
        '#3b82f6', // Azul brillante
        '#d946ef', // Magenta
        '#0ea5e9', // Azul cielo
        '#22d3ee', // Cyan claro
        '#a78bfa', // Púrpura pastel
        '#fbbf24', // Amarillo dorado
        '#34d399', // Verde menta
        '#fb7185', // Rosa coral
        '#fdba74', // Naranja melocotón
    ],
    months: ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'],
    currentYear: new Date().getFullYear(),
    currentMonth: new Date().getMonth() + 1,
};

// ===== UTILITIES =====
const Utils = {
    getWeeksInMonth: (y, m) => Math.ceil((new Date(y, m, 0).getDate() + (new Date(y, m - 1, 1).getDay() || 7) - 1) / 7),
    
    getWeekNumber: d => {
        const dd = new Date(Date.UTC(d.getFullYear(), d.getMonth(), d.getDate()));
        dd.setUTCDate(dd.getUTCDate() + 4 - (dd.getUTCDay() || 7));
        return Math.ceil((((dd - new Date(Date.UTC(dd.getUTCFullYear(), 0, 1))) / 86400000) + 1) / 7);
    },
    
    getWeekStartDate: (y, m, w) => new Date(y, m - 1, (w - 1) * 7 + 1 - ((new Date(y, m - 1, 1).getDay() || 7) - 1)),
    
    fetchData: async (endpoint, filters) => {
        const params = new URLSearchParams();
        if (filters.day) params.append('day', filters.day);
        else {
            if (filters.year) params.append('year', filters.year);
            if (filters.month) params.append('month', filters.month);
            if (filters.week) params.append('week', filters.week);
        }
        const res = await fetch(`${endpoint}${params.toString() ? `&${params}` : ''}`);
        if (!res.ok) throw new Error('Error fetching data');
        return res.json();
    }
};

// ===== FILTER MANAGER =====
class FilterManager {
    constructor(prefix, container) {
        this.prefix = prefix;
        this.els = {};
        this.render(container);
        this.initDefaults();
        this.attachEvents();
    }

    render(container) {
        container.innerHTML = `
            <select id="${this.prefix}-year" class="filter-select">
                <option value="">Año</option>
                ${Array.from({length: CONFIG.currentYear - 2019}, (_, i) => 
                    `<option value="${CONFIG.currentYear - i}">${CONFIG.currentYear - i}</option>`
                ).join('')}
            </select>
            <select id="${this.prefix}-month" class="filter-select">
                <option value="">Mes</option>
                ${CONFIG.months.map((m, i) => `<option value="${i + 1}">${m}</option>`).join('')}
            </select>
            <select id="${this.prefix}-week" class="filter-select">
                <option value="">Semana</option>
            </select>
            <input type="date" id="${this.prefix}-day" />
        `;
        ['year', 'month', 'week', 'day'].forEach(k => this.els[k] = document.getElementById(`${this.prefix}-${k}`));
    }

    initDefaults() {
        this.els.year.value = CONFIG.currentYear;
        this.els.month.value = CONFIG.currentMonth;
        this.updateWeeks();
        this.els.week.value = Utils.getWeekNumber(new Date());
    }

    attachEvents() {
        ['year', 'month'].forEach(k => this.els[k].addEventListener('change', () => this.updateWeeks()));
    }

    updateWeeks() {
        const y = parseInt(this.els.year.value) || CONFIG.currentYear;
        const m = parseInt(this.els.month.value);
        this.els.week.innerHTML = '<option value="">Semana</option>';
        if (m) {
            this.els.week.innerHTML += Array.from({length: Utils.getWeeksInMonth(y, m)}, (_, i) => 
                `<option value="${i + 1}">Sem ${i + 1}</option>`
            ).join('');
        }
    }

    get() {
        const y = this.els.year.value ? parseInt(this.els.year.value) : null;
        const m = this.els.month.value ? parseInt(this.els.month.value) : null;
        const wm = this.els.week.value ? parseInt(this.els.week.value) : null;
        const d = this.els.day.value;
        const w = (wm && m && y) ? Utils.getWeekNumber(Utils.getWeekStartDate(y, m, wm)) : '';
        return { year: y, month: m, week: w, day: d };
    }

    onChange(cb) {
        Object.values(this.els).forEach(el => {
            el.addEventListener('change', cb);
            if (el.type === 'date') el.addEventListener('input', cb);
        });
    }
}

// ===== CHART BASE =====
class ChartBase {
    constructor(canvasId, chartType = 'bar') {
        this.canvas = document.getElementById(canvasId);
        this.ctx = this.canvas.getContext('2d');
        this.chart = null;
        this.type = chartType;
    }

    destroy() { if (this.chart) this.chart.destroy(); }

    render(config) {
        this.destroy();
        this.chart = new Chart(this.ctx, config);
    }

    getBaseOptions() {
        return {
            responsive: true,
            maintainAspectRatio: false,
            animation: {
                duration: 800,
                easing: 'easeInOutQuart'
            },
            plugins: {
                tooltip: {
                    backgroundColor: 'rgba(15, 23, 42, 0.95)',
                    titleColor: '#38bdf8',
                    bodyColor: '#e2e8f0',
                    padding: 14,
                    borderColor: 'rgba(56, 189, 248, 0.3)',
                    borderWidth: 1,
                    cornerRadius: 8,
                    titleFont: {
                        size: 13,
                        weight: 'bold'
                    },
                    bodyFont: {
                        size: 12
                    },
                    displayColors: true,
                    boxWidth: 12,
                    boxHeight: 12,
                    boxPadding: 6
                }
            }
        };
    }
}

// ===== COSTURA CHART =====
class CosturaChart extends ChartBase {
    constructor() {
        super('costura-chart');
        this.tipo = 'pedido';
        this.title = document.getElementById('costura-title');
        this.toggle = document.getElementById('costura-toggle');
        this.filters = new FilterManager('costura', document.getElementById('costura-filters'));
        this.toggle.onclick = () => this.toggleTipo();
        this.filters.onChange(() => this.load());
        this.load();
    }

    toggleTipo() {
        this.tipo = this.tipo === 'pedido' ? 'bodega' : 'pedido';
        const isPed = this.tipo === 'pedido';
        this.title.textContent = isPed ? 'Entregas Costura' : 'Bodega Costura';
        this.toggle.textContent = isPed ? 'Bodega' : 'Pedidos';
        this.load();
    }

    async load() {
        try {
            const data = await Utils.fetchData(`/dashboard/entregas-costura-data?tipo=${this.tipo}`, this.filters.get());
            const labels = data.map(d => d.costurero);
            const values = data.map(d => d.total_entregas);
            
            // Calcular el máximo para establecer altura mínima visible
            const maxValue = Math.max(...values);
            const minVisibleHeight = maxValue * 0.05; // 5% del máximo
            const adjustedValues = values.map(v => v === 0 ? 0 : Math.max(v, minVisibleHeight));

            this.render({
                type: 'bar',
                data: {
                    labels,
                    datasets: [{
                        label: 'Entregas',
                        data: adjustedValues,
                        backgroundColor: CONFIG.colors.slice(0, labels.length),
                        borderColor: CONFIG.colors.slice(0, labels.length),
                        borderWidth: 0,
                        borderRadius: 8,
                        borderSkipped: false,
                        barPercentage: 0.8,
                        categoryPercentage: 0.9,
                    }]
                },
                options: {
                    ...this.getBaseOptions(),
                    plugins: {
                        ...this.getBaseOptions().plugins,
                        legend: { display: false },
                        tooltip: { 
                            ...this.getBaseOptions().plugins.tooltip, 
                            displayColors: false,
                            callbacks: {
                                label: (context) => `Entregas: ${values[context.dataIndex]}`
                            }
                        }
                    },
                    scales: {
                        y: { 
                            beginAtZero: true, 
                            grid: { color: 'rgba(255,255,255,0.05)', drawBorder: false }, 
                            ticks: { color: '#94a3b8', font: { size: 9 }, padding: 8 },
                            border: { display: false }
                        },
                        x: { 
                            grid: { display: false, drawBorder: false }, 
                            ticks: { color: '#94a3b8', font: { size: 9 }, maxRotation: 45, minRotation: 45 },
                            border: { display: false }
                        }
                    }
                }
            });
        } catch (e) { console.error('Error:', e); }
    }
}

// ===== CORTE CHART =====
class CorteChart extends ChartBase {
    constructor() {
        super('corte-chart', 'doughnut');
        this.tipo = 'pedido';
        this.title = document.getElementById('corte-title');
        this.toggle = document.getElementById('corte-toggle');
        this.filters = new FilterManager('corte', document.getElementById('corte-filters'));
        this.toggle.onclick = () => this.toggleTipo();
        this.filters.onChange(() => this.load());
        this.load();
    }

    toggleTipo() {
        this.tipo = this.tipo === 'pedido' ? 'bodega' : 'pedido';
        const isPed = this.tipo === 'pedido';
        this.title.textContent = isPed ? 'Piezas Etiquetadas' : 'Bodega Corte';
        this.toggle.textContent = isPed ? 'Bodega' : 'Pedidos';
        this.load();
    }

    async load() {
        try {
            const data = await Utils.fetchData(`/dashboard/entregas-corte-data?tipo=${this.tipo}`, this.filters.get());
            const labels = data.map(d => `${d.cortador} - ${d.etiquetador}`);

            this.render({
                type: 'doughnut',
                data: {
                    labels,
                    datasets: [{
                        data: data.map(d => d.total_etiquetadas),
                        backgroundColor: CONFIG.colors.slice(0, labels.length),
                        borderColor: '#1f2937',
                        borderWidth: 4,
                        hoverBorderWidth: 4,
                        hoverBorderColor: '#1f2937',
                    }]
                },
                options: {
                    ...this.getBaseOptions(),
                    cutout: '65%',
                    plugins: {
                        ...this.getBaseOptions().plugins,
                        legend: {
                            display: true,
                            position: 'bottom',
                            labels: { 
                                color: '#94a3b8', 
                                font: { size: 9 }, 
                                padding: 10, 
                                boxWidth: 12, 
                                boxHeight: 12,
                                usePointStyle: true,
                                pointStyle: 'circle'
                            }
                        },
                        tooltip: {
                            ...this.getBaseOptions().plugins.tooltip,
                            callbacks: {
                                title: ctx => data[ctx[0].dataIndex].cortador + ' - ' + data[ctx[0].dataIndex].etiquetador,
                                label: ctx => {
                                    const item = data[ctx.dataIndex];
                                    return [`Piezas: ${item.total_piezas}`, `Pasadas: ${item.total_pasadas}`, `Etiquetadas: ${item.total_etiquetadas}`];
                                }
                            }
                        }
                    }
                }
            });
        } catch (e) { console.error('Error:', e); }
    }
}

// ===== DATA LOADERS =====
const DataLoader = {
    async loadKPIs() {
        const data = await fetch('/dashboard/kpis').then(r => r.json());
        document.getElementById('total-orders').textContent = data.total_orders;
        document.getElementById('ordenes-completadas').textContent = 
            data.orders_by_status.find(s => s.estado === 'Entregado')?.count || 0;
        document.getElementById('ordenes-pendientes').textContent = 
            (data.orders_by_status.find(s => s.estado === 'En Ejecución')?.count || 0) + 
            (data.orders_by_status.find(s => s.estado === 'No iniciado')?.count || 0);
    },

    async loadNews() {
        const data = await fetch('/dashboard/news').then(r => r.json());
        document.getElementById('news-feed').innerHTML = data.slice(0, 10).map(item => `
            <div class="news-item">
                <div class="title">${item.description}</div>
                <div class="meta">
                    <span>${item.user}</span>
                    <span>${item.created_at}</span>
                </div>
            </div>
        `).join('');
    }
};

// ===== INIT =====
document.addEventListener('DOMContentLoaded', () => {
    new CosturaChart();
    new CorteChart();
    DataLoader.loadKPIs();
    DataLoader.loadNews();
});
</script>
@endsection