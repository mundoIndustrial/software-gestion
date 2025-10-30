@extends('layouts.app')

@section('content')
<style>
    .filter-select option { color: black !important; background-color: white !important; }
    body { background: #1a1d29; overflow-x: hidden; margin: 0; padding: 0; }
    
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
        background: rgba(255, 255, 255, 0.03);
        border-radius: 8px;
        border: 1px solid rgba(255, 107, 53, 0.1);
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
        background: linear-gradient(135deg, rgba(255, 107, 53, 0.1), rgba(255, 107, 53, 0.05));
        border: 1px solid rgba(255, 107, 53, 0.2);
        border-radius: 8px;
        padding: 0.75rem;
        text-align: center;
        transition: all 0.3s ease;
    }

    .kpi-card:hover {
        transform: translateY(-3px);
        border-color: rgba(255, 107, 53, 0.4);
        box-shadow: 0 8px 20px rgba(255, 107, 53, 0.2);
    }

    .kpi-card h3 {
        font-size: 0.6rem;
        font-weight: 600;
        color: #94a3b8;
        margin: 0 0 0.35rem 0;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .kpi-card .value { font-size: 1.3rem; font-weight: 700; color: #FF6B35; margin: 0; }

    .chart-card {
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 107, 53, 0.15);
        border-radius: 8px;
        padding: 0.9rem;
        display: flex;
        flex-direction: column;
        overflow: hidden;
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
        background: #FF6B35;
        color: white;
        border: none;
        border-radius: 4px;
        font-size: 0.6rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
    }

    .toggle-btn:hover { background: #ff8555; transform: scale(1.05); }
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
        background: rgba(255, 107, 53, 0.05);
        border-left: 2px solid #FF6B35;
        padding: 0.5rem;
        margin-bottom: 0.35rem;
        border-radius: 4px;
        transition: all 0.2s;
    }

    .news-item:hover { background: rgba(255, 107, 53, 0.1); transform: translateX(3px); }
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
    colors: ['#FF6B35', '#ff8555', '#ffa075', '#ffba95', '#ffd4b5', '#4f46e5', '#06b6d4', '#10b981',
        '#e11d48', '#dc2626', '#ea580c', '#d97706', '#65a30d', '#16a34a', '#059669', '#0d9488',
        '#0891b2', '#0284c7', '#2563eb', '#4f46e5', '#7c3aed', '#a855f7', '#c084fc', '#ec4899',
        '#f97316', '#f59e0b', '#eab308', '#84cc16', '#22c55e', '#06b6d4', '#3b82f6', '#6366f1'],
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
            plugins: {
                tooltip: {
                    backgroundColor: 'rgba(0,0,0,0.9)',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    padding: 12,
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

            this.render({
                type: 'bar',
                data: {
                    labels,
                    datasets: [{
                        label: 'Entregas',
                        data: values,
                        backgroundColor: CONFIG.colors.slice(0, labels.length),
                        borderColor: CONFIG.colors.slice(0, labels.length).map(c => c.replace(')', ', 0.8)').replace('rgb', 'rgba')),
                        borderWidth: 2,
                        borderRadius: 6,
                    }]
                },
                options: {
                    ...this.getBaseOptions(),
                    plugins: {
                        ...this.getBaseOptions().plugins,
                        legend: { display: false },
                        tooltip: { ...this.getBaseOptions().plugins.tooltip, displayColors: false }
                    },
                    scales: {
                        y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#94a3b8', font: { size: 8 } } },
                        x: { grid: { display: false }, ticks: { color: '#94a3b8', font: { size: 8 } } }
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
                        borderColor: '#1a1d29',
                        borderWidth: 3,
                    }]
                },
                options: {
                    ...this.getBaseOptions(),
                    cutout: '70%',
                    plugins: {
                        ...this.getBaseOptions().plugins,
                        legend: {
                            display: true,
                            position: 'bottom',
                            labels: { color: '#94a3b8', font: { size: 8 }, padding: 8, boxWidth: 10, boxHeight: 10 }
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