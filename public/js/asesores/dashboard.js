// ========================================
// DASHBOARD DE ASESORES - GRÁFICAS Y DATOS
// ========================================

// Configuración de colores y gráficas
const CONFIG = {
    colors: {
        primary: ['#667eea', '#764ba2'],
        secondary: ['#f093fb', '#f5576c'],
        tertiary: ['#4facfe', '#00f2fe'],
        success: ['#10b981', '#059669'],
        warning: ['#3b82f6', '#2563eb'],
        danger: ['#ef4444', '#dc2626'],
        info: ['#3b82f6', '#2563eb']
    },
    chartDefaults: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        }
    }
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

    async loadNews(date = new Date().toISOString().split('T')[0], filters = {}) {
        try {
            const params = new URLSearchParams({ date, limit: 50, ...filters });
            console.log('📡 Cargando noticias para:', date);
            
            const response = await fetch(`/dashboard/news?${params}`);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const result = await response.json();
            const data = result.news || result; // Compatibilidad con respuesta antigua
            const counts = result.counts || { total: data.length, unread: 0, read: 0 };
            console.log('📰 Noticias recibidas:', data.length, data);
            
            const newsFeed = document.getElementById('news-feed');
            if (!newsFeed) {
                console.error('❌ Elemento news-feed no encontrado');
                return;
            }
            
            // Actualizar contadores
            document.getElementById('news-count').textContent = `${counts.total} ${counts.total === 1 ? 'nueva' : 'nuevas'}`;
            document.getElementById('count-all').textContent = counts.total;
            document.getElementById('count-unread').textContent = counts.unread;
            document.getElementById('count-read').textContent = counts.read;
            
            if (data.length === 0) {
                newsFeed.innerHTML = '<div style="padding: 2rem; text-align: center; color: #6b7280;">No hay notificaciones para esta fecha</div>';
                return;
            }
        
        // Mapeo de tipos de eventos a badges y estilos
        const eventTypeConfig = {
            'record_created': { badge: 'NUEVO', badgeClass: 'nuevo', icon: '🆕' },
            'record_updated': { badge: 'ACTUALIZADO', badgeClass: 'actualizado', icon: '📝' },
            'record_deleted': { badge: 'ELIMINADO', badgeClass: 'eliminado', icon: '🗑️' },
            'order_created': { badge: 'NUEVO', badgeClass: 'nuevo', icon: '📦' },
            'status_changed': { badge: 'ACTUALIZADO', badgeClass: 'actualizado', icon: '🔄' },
            'area_changed': { badge: 'ACTUALIZADO', badgeClass: 'actualizado', icon: '📍' },
            'delivery_registered': { badge: 'NUEVO', badgeClass: 'nuevo', icon: '✅' },
            'order_deleted': { badge: 'ELIMINADO', badgeClass: 'eliminado', icon: '❌' }
        };
        
            newsFeed.innerHTML = data.map(item => {
                const config = eventTypeConfig[item.event_type] || { badge: 'EVENTO', badgeClass: 'actualizado', icon: '📋' };
                
                // Botón de ver detalles si hay metadata
                const hasDetails = item.metadata && (item.metadata.changes || item.metadata.data);
                const detailsButton = hasDetails ? `
                    <button class="view-details-btn" onclick="showNotificationDetails(${item.id}, '${item.event_type}')" title="Ver detalles">
                        <span class="material-symbols-rounded">info</span>
                    </button>
                ` : '';
                
                return `
                    <div class="news-item" data-type="${item.event_type}" data-id="${item.id}">
                        <div class="news-status-badge ${config.badgeClass}">
                            <span>${config.icon}</span>
                            <span>${config.badge}</span>
                        </div>
                        <div class="news-content-wrapper">
                            <div class="title">${item.description}</div>
                            <div class="meta">
                                <span class="user-badge">👤 ${item.user}</span>
                                <span class="time-badge">🕐 ${item.created_at}</span>
                                ${item.pedido ? `<span class="pedido-badge">📦 #${item.pedido}</span>` : ''}
                                ${detailsButton}
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
            
            // Guardar datos en memoria para el modal
            window.newsData = data;
        } catch (error) {
            console.error('❌ Error cargando noticias:', error);
            const newsFeed = document.getElementById('news-feed');
            if (newsFeed) {
                newsFeed.innerHTML = '<div style="padding: 2rem; text-align: center; color: #ef4444;">Error cargando notificaciones. Ver consola.</div>';
            }
        }
    },

    async loadAuditStats(date = new Date().toISOString().split('T')[0]) {
        const params = new URLSearchParams({ date });
        const stats = await fetch(`/dashboard/audit-stats?${params}`).then(r => r.json());
        
        console.log('📊 Estadísticas de Auditoría:', stats);
        
        // Actualizar UI con estadísticas si existe un contenedor
        const statsContainer = document.getElementById('audit-stats-container');
        if (statsContainer) {
            statsContainer.innerHTML = `
                <div class="stat-card">
                    <h4>Total de Eventos</h4>
                    <p class="stat-number">${stats.total_events}</p>
                </div>
                <div class="stat-card">
                    <h4>Por Tipo</h4>
                    <ul class="stat-list">
                        ${stats.by_type.map(t => `<li>${t.event_type}: ${t.count}</li>`).join('')}
                    </ul>
                </div>
                <div class="stat-card">
                    <h4>Por Tabla</h4>
                    <ul class="stat-list">
                        ${stats.by_table.slice(0, 5).map(t => `<li>${t.table_name || 'N/A'}: ${t.count}</li>`).join('')}
                    </ul>
                </div>
                <div class="stat-card">
                    <h4>Por Usuario</h4>
                    <ul class="stat-list">
                        ${stats.by_user.slice(0, 5).map(u => `<li>${u.name}: ${u.count}</li>`).join('')}
                    </ul>
                </div>
            `;
        }
    }
};

// ===== FUNCIONES DE MODAL =====
function showModal(title, message, type = 'info') {
    const modal = document.createElement('div');
    modal.className = 'notification-modal';
    
    const iconMap = {
        'success': '✅',
        'error': '❌',
        'warning': '⚠️',
        'info': 'ℹ️',
        'question': '❓'
    };
    
    modal.innerHTML = `
        <div class="modal-overlay" onclick="this.parentElement.remove()"></div>
        <div class="modal-content modal-${type}">
            <div class="modal-header">
                <h2><span class="modal-icon">${iconMap[type] || iconMap.info}</span> ${title}</h2>
                <button class="modal-close" onclick="this.closest('.notification-modal').remove()">
                    <span class="material-symbols-rounded">close</span>
                </button>
            </div>
            <div class="modal-body">
                <p class="modal-message">${message}</p>
            </div>
            <div class="modal-footer">
                <button class="modal-btn modal-btn-primary" onclick="this.closest('.notification-modal').remove()">
                    Aceptar
                </button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
}

function showConfirmModal(title, message, onConfirm) {
    const modal = document.createElement('div');
    modal.className = 'notification-modal';
    
    modal.innerHTML = `
        <div class="modal-overlay" onclick="this.closest('.notification-modal').remove()"></div>
        <div class="modal-content modal-question">
            <div class="modal-header">
                <h2><span class="modal-icon">❓</span> ${title}</h2>
                <button class="modal-close" onclick="this.closest('.notification-modal').remove()">
                    <span class="material-symbols-rounded">close</span>
                </button>
            </div>
            <div class="modal-body">
                <p class="modal-message">${message}</p>
            </div>
            <div class="modal-footer">
                <button class="modal-btn modal-btn-secondary" onclick="this.closest('.notification-modal').remove()">
                    Cancelar
                </button>
                <button class="modal-btn modal-btn-primary" id="confirm-action">
                    Confirmar
                </button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    document.getElementById('confirm-action').addEventListener('click', () => {
        modal.remove();
        onConfirm();
    });
}

// ===== FUNCIÓN PARA MOSTRAR DETALLES =====
function showNotificationDetails(newsId, eventType) {
    const newsItem = window.newsData?.find(item => item.id === newsId);
    if (!newsItem || !newsItem.metadata) {
        showModal('Sin Detalles', 'No hay detalles disponibles para esta notificación', 'info');
        return;
    }
    
    const metadata = newsItem.metadata;
    let detailsHTML = '';
    
    if (eventType === 'record_deleted' && metadata.data) {
        // Mostrar datos del registro eliminado
        detailsHTML = '<h3>📋 Datos del registro eliminado:</h3><div class="details-grid">';
        for (const [key, value] of Object.entries(metadata.data)) {
            const fieldName = key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
            detailsHTML += `
                <div class="detail-item">
                    <span class="detail-label">${fieldName}:</span>
                    <span class="detail-value">${value ?? 'N/A'}</span>
                </div>
            `;
        }
        detailsHTML += '</div>';
    } else if (eventType === 'record_updated' && metadata.changes) {
        // Mostrar cambios realizados
        detailsHTML = '<h3>📝 Cambios realizados:</h3><div class="details-grid">';
        for (const [key, newValue] of Object.entries(metadata.changes)) {
            const oldValue = metadata.original?.[key] ?? 'N/A';
            const fieldName = key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
            detailsHTML += `
                <div class="detail-item">
                    <span class="detail-label">${fieldName}:</span>
                    <span class="detail-change">${oldValue} → ${newValue}</span>
                </div>
            `;
        }
        detailsHTML += '</div>';
    } else if (eventType === 'record_created' && metadata.data) {
        // Mostrar datos del registro creado
        detailsHTML = '<h3>✨ Datos del nuevo registro:</h3><div class="details-grid">';
        for (const [key, value] of Object.entries(metadata.data)) {
            const fieldName = key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
            detailsHTML += `
                <div class="detail-item">
                    <span class="detail-label">${fieldName}:</span>
                    <span class="detail-value">${value ?? 'N/A'}</span>
                </div>
            `;
        }
        detailsHTML += '</div>';
    }
    
    // Crear modal
    const modal = document.createElement('div');
    modal.className = 'notification-modal';
    modal.innerHTML = `
        <div class="modal-overlay" onclick="this.parentElement.remove()"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h2>Detalles de la Notificación</h2>
                <button class="modal-close" onclick="this.closest('.notification-modal').remove()">
                    <span class="material-symbols-rounded">close</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="notification-summary">
                    <p><strong>Descripción:</strong> ${newsItem.description}</p>
                    <p><strong>Usuario:</strong> ${newsItem.user}</p>
                    <p><strong>Fecha:</strong> ${newsItem.created_at}</p>
                    ${newsItem.table_name ? `<p><strong>Tabla:</strong> ${newsItem.table_name}</p>` : ''}
                </div>
                ${detailsHTML}
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
}

// ===== INIT =====
document.addEventListener('DOMContentLoaded', () => {
    new CosturaChart();
    new CorteChart();
    DataLoader.loadKPIs();
    
    // News filter
    const newsDateFilter = document.getElementById('news-date-filter');
    newsDateFilter.value = new Date().toISOString().split('T')[0];
    newsDateFilter.addEventListener('change', () => DataLoader.loadNews(newsDateFilter.value));
    
    // Notification tabs
    const notificationTabs = document.querySelectorAll('.notification-tab');
    notificationTabs.forEach(tab => {
        tab.addEventListener('click', () => {
            // Remove active class from all tabs
            notificationTabs.forEach(t => t.classList.remove('active'));
            // Add active class to clicked tab
            tab.classList.add('active');
            
            // Filter notifications based on tab
            const filter = tab.dataset.filter;
            const newsItems = document.querySelectorAll('.news-item');
            
            newsItems.forEach(item => {
                if (filter === 'all') {
                    item.style.display = 'flex';
                } else if (filter === 'unread') {
                    // Por ahora mostrar todas (implementar lógica de leídas/no leídas después)
                    item.style.display = 'flex';
                } else if (filter === 'read') {
                    // Por ahora ocultar todas (implementar lógica de leídas/no leídas después)
                    item.style.display = 'none';
                }
            });
        });
    });
    
    // Mark all as read button
    const markAllReadBtn = document.getElementById('mark-all-read');
    if (markAllReadBtn) {
        markAllReadBtn.addEventListener('click', () => {
            const newsDateFilter = document.getElementById('news-date-filter');
            const date = newsDateFilter.value;
            
            showConfirmModal(
                'Marcar como Leídas',
                '¿Estás seguro de que deseas marcar todas las notificaciones como leídas?',
                async () => {
                    try {
                        const response = await fetch('/dashboard/news/mark-all-read', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({ date })
                        });

                        const data = await response.json();
                        
                        if (data.success) {
                            // Recargar notificaciones
                            DataLoader.loadNews(date);
                            
                            // Mostrar mensaje de éxito
                            showModal('¡Éxito!', 'Todas las notificaciones han sido marcadas como leídas', 'success');
                        } else {
                            showModal('Error', 'No se pudieron marcar las notificaciones como leídas', 'error');
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        showModal('Error', 'Ocurrió un error al procesar la solicitud', 'error');
                    }
                }
            );
        });
    }
    
    DataLoader.loadNews(newsDateFilter.value);
});
