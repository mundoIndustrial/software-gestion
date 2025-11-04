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

    async loadNews(date = new Date().toISOString().split('T')[0]) {
        const params = new URLSearchParams({ date });
        const data = await fetch(`/dashboard/news?${params}`).then(r => r.json());
        document.getElementById('news-feed').innerHTML = data.slice(0, 10).map(item => `
            <div class="news-item" data-type="${item.event_type}">
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
    
    // News filter
    const newsDateFilter = document.getElementById('news-date-filter');
    newsDateFilter.value = new Date().toISOString().split('T')[0];
    newsDateFilter.addEventListener('change', () => DataLoader.loadNews(newsDateFilter.value));
    
    DataLoader.loadNews(newsDateFilter.value);
});
