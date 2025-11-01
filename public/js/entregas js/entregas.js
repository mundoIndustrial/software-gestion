const COLORS = ['#ff9d58', '#4f46e5', '#06b6d4', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899'];
let TIPO = '';
let costuraChart, corteChart;

// Función de inicialización que se llama desde la vista
function initEntregas(tipo) {
    TIPO = tipo;
    
    document.addEventListener('DOMContentLoaded', function() {
        initCharts();
        document.getElementById('filtrarBtn').addEventListener('click', filtrarDatos);
        document.getElementById('fechaFilter').addEventListener('change', filtrarDatos);
        document.getElementById('registrarEntregaBtn').addEventListener('click', openEntregaModal);
        
        // Cargar datos iniciales con la fecha actual
        filtrarDatos();
        
        // Escuchar eventos en tiempo real
        setupRealtimeListeners();
    });
}

function openEntregaModal() {
    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'entrega-form' }));
}

window.filtrarDatos = async function() {
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
        data: { labels: [], datasets: [{ label: 'Entregas', data: [], backgroundColor: COLORS, borderRadius: 8, borderSkipped: false }] },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { 
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(15, 23, 42, 0.95)',
                    titleColor: '#ff9d58',
                    bodyColor: '#fff',
                    borderColor: 'rgba(249, 115, 22, 0.3)',
                    borderWidth: 1,
                    padding: 12,
                    displayColors: true,
                    cornerRadius: 8
                }
            },
            scales: {
                y: { 
                    beginAtZero: true, 
                    grid: { color: 'rgba(255,255,255,0.08)', drawBorder: false }, 
                    ticks: { color: '#fff', font: { size: 12, weight: '500' } },
                    border: { display: false }
                },
                x: { 
                    grid: { display: false }, 
                    ticks: { color: '#fff', font: { size: 11, weight: '600' } },
                    border: { display: false }
                }
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
                legend: { 
                    position: 'bottom', 
                    labels: { 
                        color: '#fff', 
                        padding: 15,
                        font: { size: 11, weight: '600' },
                        usePointStyle: true,
                        pointStyle: 'circle'
                    } 
                },
                tooltip: {
                    backgroundColor: 'rgba(15, 23, 42, 0.95)',
                    titleColor: '#ff9d58',
                    bodyColor: '#fff',
                    borderColor: 'rgba(249, 115, 22, 0.3)',
                    borderWidth: 1,
                    padding: 12,
                    displayColors: true,
                    cornerRadius: 8
                }
            },
            scales: {
                x: {
                    stacked: true,
                    ticks: { color: '#fff', font: { size: 11, weight: '600' } },
                    grid: { display: false },
                    border: { display: false }
                },
                y: {
                    stacked: true,
                    beginAtZero: true,
                    ticks: { color: '#fff', font: { size: 12, weight: '500' } },
                    grid: { color: 'rgba(255,255,255,0.08)', drawBorder: false },
                    border: { display: false }
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
            <td><span class="table-badge">${item.cantidad_entregada || 0}</span></td>
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
            <td><span class="table-badge">${item.piezas || 0}</span></td>
            <td><span class="table-badge">${item.etiqueteadas || 0}</span></td>
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

// Configurar listeners de tiempo real
function setupRealtimeListeners() {
    if (typeof window.Echo === 'undefined') {
        console.warn('❌ Laravel Echo no está disponible. Las actualizaciones en tiempo real no funcionarán.');
        return;
    }

    console.log('✅ Echo disponible. Suscribiendo al canal "entregas.' + TIPO + '"...');

    const channel = window.Echo.channel(`entregas.${TIPO}`);
    
    channel.subscribed(() => {
        console.log('✅ Suscrito al canal "entregas.' + TIPO + '"');
    });

    channel.error((error) => {
        console.error('❌ Error en canal "entregas.' + TIPO + '":', error);
    });
    
    channel.listen('EntregaRegistrada', (data) => {
        console.log('🎉 Evento EntregaRegistrada recibido!', data);
        
        const fechaActual = document.getElementById('fechaFilter').value;
        
        // Solo actualizar si la fecha coincide con el filtro actual
        if (data.fecha === fechaActual) {
            console.log('✅ Fecha coincide, actualizando vista...');
            
            // Recargar datos de forma automática
            window.filtrarDatos();
            
            // Mostrar notificación visual
            mostrarNotificacion(data);
        } else {
            console.log('ℹ️ Fecha no coincide. Filtro actual:', fechaActual, 'Entrega:', data.fecha);
        }
    });

    console.log('✅ Listener de entregas configurado');
}

// Mostrar notificación de nueva entrega
function mostrarNotificacion(data) {
    const notificacion = document.createElement('div');
    notificacion.className = 'realtime-notification';
    
    let mensaje = '';
    if (data.subtipo === 'costura') {
        mensaje = `Nueva entrega de costura: ${data.entrega.cantidad_entregada} unidades - ${data.entrega.costurero}`;
    } else if (data.subtipo === 'corte') {
        mensaje = `Nueva entrega de corte: ${data.entrega.piezas} piezas - ${data.entrega.cortador}`;
    }
    
    notificacion.innerHTML = `
        <div class="notification-content">
            <svg class="notification-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke-width="2" stroke-linecap="round"/>
            </svg>
            <span>${mensaje}</span>
        </div>
    `;
    
    document.body.appendChild(notificacion);
    
    // Animar entrada
    setTimeout(() => notificacion.classList.add('show'), 10);
    
    // Remover después de 4 segundos
    setTimeout(() => {
        notificacion.classList.remove('show');
        setTimeout(() => notificacion.remove(), 300);
    }, 4000);
}
