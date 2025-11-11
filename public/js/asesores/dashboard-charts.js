// ========================================
// DASHBOARD DE ASESORES - GRÁFICAS Y DATOS
// ========================================

let ordenesLineChart, asesoresBarChart, estadosDoughnutChart;

// ========================================
// CARGAR DATOS DEL DASHBOARD
// ========================================
document.addEventListener('DOMContentLoaded', async function() {
    await loadDashboardData();
    initChartPeriodButtons();
});

async function loadDashboardData() {
    try {
        const data = await fetchAPI('/asesores/dashboard-data');
        
        // Actualizar tendencias en las tarjetas
        updateStatTrends(data.tendencia);
        
        // Crear gráficas
        createOrdenesLineChart(data.ordenes_ultimos_30_dias);
        createAsesoresBarChart(data.ordenes_por_asesor);
        createEstadosDoughnutChart(data.ordenes_por_estado);
        updateComparison(data.semana_actual, data.semana_anterior, data.tendencia);
        
    } catch (error) {
        console.error('Error cargando datos del dashboard:', error);
        showToast('Error al cargar los datos del dashboard', 'error');
    }
}

// ========================================
// ACTUALIZAR TENDENCIAS EN TARJETAS
// ========================================
function updateStatTrends(tendencia) {
    const trendElements = document.querySelectorAll('.stat-trend');
    trendElements.forEach(el => {
        const span = el.querySelector('span');
        if (span) {
            span.textContent = `${Math.abs(tendencia).toFixed(1)}%`;
        }
        
        const icon = el.querySelector('i');
        if (icon) {
            if (tendencia >= 0) {
                icon.className = 'fas fa-arrow-up';
                el.classList.remove('down');
            } else {
                icon.className = 'fas fa-arrow-down';
                el.classList.add('down');
            }
        }
    });
}

// ========================================
// GRÁFICA DE LÍNEA - ÓRDENES POR DÍA
// ========================================
function createOrdenesLineChart(data) {
    const ctx = document.getElementById('ordenesLineChart');
    if (!ctx) return;
    
    // Destruir gráfica anterior si existe
    if (ordenesLineChart) {
        ordenesLineChart.destroy();
    }
    
    const labels = data.map(item => {
        const date = new Date(item.fecha);
        return date.toLocaleDateString('es-ES', { day: '2-digit', month: 'short' });
    });
    
    const values = data.map(item => item.total);
    
    ordenesLineChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Órdenes',
                data: values,
                borderColor: '#0066CC',
                backgroundColor: 'rgba(0, 102, 204, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointRadius: 4,
                pointHoverRadius: 6,
                pointBackgroundColor: '#0066CC',
                pointBorderColor: '#fff',
                pointBorderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(15, 23, 42, 0.95)',
                    titleColor: '#fff',
                    bodyColor: '#cbd5e1',
                    borderColor: '#0066CC',
                    borderWidth: 1,
                    padding: 12,
                    cornerRadius: 8,
                    displayColors: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(203, 213, 225, 0.1)',
                        drawBorder: false
                    },
                    ticks: {
                        color: '#94a3b8',
                        font: {
                            size: 11
                        }
                    }
                },
                x: {
                    grid: {
                        display: false,
                        drawBorder: false
                    },
                    ticks: {
                        color: '#94a3b8',
                        font: {
                            size: 11
                        },
                        maxRotation: 45,
                        minRotation: 45
                    }
                }
            }
        }
    });
}

// ========================================
// GRÁFICA DE BARRAS - TOP ASESORES
// ========================================
function createAsesoresBarChart(data) {
    const ctx = document.getElementById('asesoresBarChart');
    if (!ctx) return;
    
    // Destruir gráfica anterior si existe
    if (asesoresBarChart) {
        asesoresBarChart.destroy();
    }
    
    const labels = data.map(item => item.name);
    const values = data.map(item => item.total);
    
    // Generar gradientes
    const gradients = labels.map((_, index) => {
        const gradient = ctx.getContext('2d').createLinearGradient(0, 0, 0, 400);
        gradient.addColorStop(0, '#0066CC');
        gradient.addColorStop(1, '#3b82f6');
        return gradient;
    });
    
    asesoresBarChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Órdenes',
                data: values,
                backgroundColor: gradients,
                borderRadius: 8,
                borderSkipped: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(15, 23, 42, 0.95)',
                    titleColor: '#fff',
                    bodyColor: '#cbd5e1',
                    borderColor: '#0066CC',
                    borderWidth: 1,
                    padding: 12,
                    cornerRadius: 8,
                    displayColors: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(203, 213, 225, 0.1)',
                        drawBorder: false
                    },
                    ticks: {
                        color: '#94a3b8',
                        font: {
                            size: 11
                        }
                    }
                },
                x: {
                    grid: {
                        display: false,
                        drawBorder: false
                    },
                    ticks: {
                        color: '#94a3b8',
                        font: {
                            size: 11
                        }
                    }
                }
            }
        }
    });
}

// ========================================
// GRÁFICA DE DONA - ESTADOS
// ========================================
function createEstadosDoughnutChart(data) {
    const ctx = document.getElementById('estadosDoughnutChart');
    if (!ctx) return;
    
    // Destruir gráfica anterior si existe
    if (estadosDoughnutChart) {
        estadosDoughnutChart.destroy();
    }
    
    const estadoColors = {
        'pendiente': '#fbbf24',
        'en_proceso': '#3b82f6',
        'completada': '#10b981',
        'cancelada': '#ef4444'
    };
    
    const labels = data.map(item => {
        const estadoLabels = {
            'pendiente': 'Pendiente',
            'en_proceso': 'En Proceso',
            'completada': 'Completada',
            'cancelada': 'Cancelada'
        };
        return estadoLabels[item.estado] || item.estado;
    });
    
    const values = data.map(item => item.total);
    const colors = data.map(item => estadoColors[item.estado] || '#94a3b8');
    
    estadosDoughnutChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: values,
                backgroundColor: colors,
                borderWidth: 4,
                borderColor: getComputedStyle(document.body).getPropertyValue('--bg-card') || '#ffffff',
                hoverBorderWidth: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '70%',
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(15, 23, 42, 0.95)',
                    titleColor: '#fff',
                    bodyColor: '#cbd5e1',
                    borderColor: '#0066CC',
                    borderWidth: 1,
                    padding: 12,
                    cornerRadius: 8,
                    displayColors: true,
                    boxWidth: 12,
                    boxHeight: 12
                }
            }
        }
    });
}

// ========================================
// ACTUALIZAR COMPARATIVA SEMANAL
// ========================================
function updateComparison(actual, anterior, tendencia) {
    // Actualizar valores
    document.getElementById('semanaActual').textContent = actual;
    document.getElementById('semanaAnterior').textContent = anterior;
    
    // Calcular porcentajes para las barras
    const max = Math.max(actual, anterior);
    const actualPercent = max > 0 ? (actual / max) * 100 : 0;
    const anteriorPercent = max > 0 ? (anterior / max) * 100 : 0;
    
    // Actualizar barras
    document.getElementById('barActual').style.width = `${actualPercent}%`;
    document.getElementById('barAnterior').style.width = `${anteriorPercent}%`;
    
    // Actualizar resultado
    const resultIcon = document.getElementById('resultIcon');
    const resultPercentage = document.getElementById('resultPercentage');
    const resultLabel = document.getElementById('resultLabel');
    
    resultPercentage.textContent = `${Math.abs(tendencia).toFixed(1)}%`;
    
    if (tendencia >= 0) {
        resultIcon.innerHTML = '<i class="fas fa-arrow-up"></i>';
        resultIcon.classList.remove('down');
        resultLabel.textContent = 'de incremento';
    } else {
        resultIcon.innerHTML = '<i class="fas fa-arrow-down"></i>';
        resultIcon.classList.add('down');
        resultLabel.textContent = 'de decremento';
    }
}

// ========================================
// BOTONES DE PERÍODO
// ========================================
function initChartPeriodButtons() {
    const buttons = document.querySelectorAll('.chart-btn[data-period]');
    buttons.forEach(btn => {
        btn.addEventListener('click', async function() {
            // Remover clase active de todos los botones
            buttons.forEach(b => b.classList.remove('active'));
            // Agregar clase active al botón clickeado
            this.classList.add('active');
            
            // Recargar datos con el nuevo período
            const period = this.dataset.period;
            await loadDashboardDataByPeriod(period);
        });
    });
}

async function loadDashboardDataByPeriod(period) {
    try {
        const data = await fetchAPI(`/asesores/dashboard-data?tipo=${period}`);
        
        // Actualizar solo la gráfica de línea
        createOrdenesLineChart(data.ordenes_ultimos_30_dias);
        
        showToast(`Datos actualizados (${period} días)`, 'success');
    } catch (error) {
        console.error('Error cargando datos por período:', error);
        showToast('Error al actualizar los datos', 'error');
    }
}

// ========================================
// ACTUALIZAR TEMA EN GRÁFICAS
// ========================================
function updateChartsTheme() {
    const isDark = document.body.classList.contains('dark-theme');
    const borderColor = isDark ? '#334155' : '#e2e8f0';
    
    // Actualizar todas las gráficas con el nuevo tema
    [ordenesLineChart, asesoresBarChart, estadosDoughnutChart].forEach(chart => {
        if (chart && chart.options) {
            // Actualizar color de borde en datasets
            if (chart.data.datasets) {
                chart.data.datasets.forEach(dataset => {
                    if (dataset.borderColor) {
                        dataset.borderColor = borderColor;
                    }
                });
            }
            chart.update();
        }
    });
}

// Escuchar cambios de tema
document.addEventListener('DOMContentLoaded', function() {
    const themeToggle = document.getElementById('themeToggle');
    if (themeToggle) {
        themeToggle.addEventListener('click', function() {
            setTimeout(updateChartsTheme, 100);
        });
    }
});
