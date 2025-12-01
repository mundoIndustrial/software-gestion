const COLORS = ['#ff9d58', '#4f46e5', '#06b6d4', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899'];
let TIPO = '';
let costuraChart, corteChart;
let editingCell = null;

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
        
        // Setup edit cell listeners
        setupEditListeners();
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
        <tr data-id="${item.id}" data-subtipo="costura">
            <td class="editable" data-field="pedido">${item.pedido || ''}</td>
            <td class="editable" data-field="cliente">${item.cliente || ''}</td>
            <td class="editable" data-field="prenda">${item.prenda || ''}</td>
            <td class="editable" data-field="talla">${item.talla || ''}</td>
            <td class="editable" data-field="cantidad_entregada"><span class="table-badge">${item.cantidad_entregada || 0}</span></td>
            <td class="editable" data-field="costurero">${item.costurero || ''}</td>
            <td>
                <button class="btn-delete" onclick="deleteEntrega(${item.id}, 'costura')" title="Eliminar">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" style="width: 16px; height: 16px;">
                        <path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
            </td>
        </tr>
    `).join('');
    setupEditListeners();
}

function updateCorteTable(data) {
    const tbody = document.getElementById('corte-tbody');
    tbody.innerHTML = data.map(item => `
        <tr data-id="${item.id}" data-subtipo="corte">
            <td class="editable" data-field="pedido">${item.pedido || ''}</td>
            <td class="editable" data-field="cortador">${item.cortador || ''}</td>
            <td class="editable" data-field="cantidad_prendas"><span class="table-badge">${item.cantidad_prendas || 0}</span></td>
            <td class="editable" data-field="piezas"><span class="table-badge">${item.piezas || 0}</span></td>
            <td class="editable" data-field="pasadas"><span class="table-badge">${item.pasadas || 0}</span></td>
            <td><span class="table-badge">${item.etiqueteadas || 0}</span></td>
            <td class="editable" data-field="etiquetador">${item.etiquetador || ''}</td>
            <td>
                <button class="btn-delete" onclick="deleteEntrega(${item.id}, 'corte')" title="Eliminar">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" style="width: 16px; height: 16px;">
                        <path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
            </td>
        </tr>
    `).join('');
    setupEditListeners();
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

    channel.listen('EntregaEliminada', (data) => {
        console.log('🗑️ Evento EntregaEliminada recibido!', data);
        
        // Recargar datos de forma automática
        window.filtrarDatos();
        
        // Mostrar notificación visual
        mostrarNotificacionEliminada(data);
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

// Mostrar notificación de entrega eliminada
function mostrarNotificacionEliminada(data) {
    const notificacion = document.createElement('div');
    notificacion.className = 'realtime-notification notification-delete';
    
    let mensaje = '';
    if (data.subtipo === 'costura') {
        mensaje = `Entrega de costura eliminada: Pedido ${data.entrega.pedido}`;
    } else if (data.subtipo === 'corte') {
        mensaje = `Entrega de corte eliminada: Pedido ${data.entrega.pedido}`;
    }
    
    notificacion.innerHTML = `
        <div class="notification-content">
            <svg class="notification-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
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

// Setup edit cell listeners
function setupEditListeners() {
    document.querySelectorAll('.editable').forEach(cell => {
        cell.style.cursor = 'pointer';
        cell.title = 'Click para editar';
        
        cell.addEventListener('click', function(e) {
            if (editingCell) return; // Ya hay una celda en edición
            
            const field = this.dataset.field;
            const row = this.closest('tr');
            const id = row.dataset.id;
            const subtipo = row.dataset.subtipo;
            
            // Get current value (remove badge if exists)
            const badge = this.querySelector('.table-badge');
            let currentValue = badge ? badge.textContent.trim() : this.textContent.trim();
            
            // Create input
            const input = document.createElement('input');
            input.type = (field === 'pedido' || field === 'cantidad_entregada' || field === 'piezas' || field === 'pasadas') ? 'number' : 'text';
            input.value = currentValue;
            input.style.cssText = 'width: 100%; padding: 4px; border: 2px solid #ff9d58; border-radius: 4px; background: rgba(255, 157, 88, 0.1);';
            
            // Store original content
            const originalContent = this.innerHTML;
            
            // Replace content with input
            this.innerHTML = '';
            this.appendChild(input);
            input.focus();
            input.select();
            
            editingCell = { cell: this, originalContent, id, subtipo, field };
            
            // Save on blur or enter
            input.addEventListener('blur', () => saveCell(input.value));
            input.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    saveCell(input.value);
                } else if (e.key === 'Escape') {
                    cancelEdit();
                }
            });
        });
    });
}

// Save cell edit
async function saveCell(newValue) {
    if (!editingCell) return;
    
    const { cell, originalContent, id, subtipo, field } = editingCell;
    
    // Get current value from original content
    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = originalContent;
    const badge = tempDiv.querySelector('.table-badge');
    const currentValue = badge ? badge.textContent.trim() : tempDiv.textContent.trim();
    
    // If value didn't change, just cancel
    if (newValue == currentValue) {
        cancelEdit();
        return;
    }
    
    try {
        const response = await fetch(`/entrega/${TIPO}/${subtipo}/${id}`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                [field]: newValue
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Update cell with new value
            if (field === 'cantidad_entregada' || field === 'piezas' || field === 'pasadas') {
                cell.innerHTML = `<span class="table-badge">${newValue}</span>`;
            } else {
                cell.textContent = newValue;
            }
            
            // Show success message
            showSuccessMessage('✓ Actualizado');
            
            // If piezas or pasadas changed in corte, update etiqueteadas
            if (subtipo === 'corte' && (field === 'piezas' || field === 'pasadas')) {
                const row = cell.closest('tr');
                const piezasCell = row.querySelector('[data-field="piezas"] .table-badge');
                const pasadasCell = row.querySelector('[data-field="pasadas"] .table-badge');
                const etiqueteadasCell = row.querySelectorAll('.table-badge')[2];
                
                if (piezasCell && pasadasCell && etiqueteadasCell) {
                    const piezas = parseInt(piezasCell.textContent);
                    const pasadas = parseInt(pasadasCell.textContent);
                    etiqueteadasCell.textContent = piezas * pasadas;
                }
            }
        } else {
            alert('Error al guardar: ' + (data.message || 'Error desconocido'));
            cancelEdit();
        }
    } catch (error) {
        console.error('Error saving cell:', error);
        alert('Error al guardar el cambio');
        cancelEdit();
    }
    
    editingCell = null;
}

// Cancel cell edit
function cancelEdit() {
    if (!editingCell) return;
    
    const { cell, originalContent } = editingCell;
    cell.innerHTML = originalContent;
    editingCell = null;
}

// Delete entrega
window.deleteEntrega = async function(id, subtipo) {
    if (!confirm('¿Estás seguro de eliminar esta entrega?')) return;
    
    try {
        const response = await fetch(`/entrega/${TIPO}/${subtipo}/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Reload data
            window.filtrarDatos();
        } else {
            alert('Error al eliminar: ' + (data.message || 'Error desconocido'));
        }
    } catch (error) {
        console.error('Error deleting entrega:', error);
        alert('Error al eliminar la entrega');
    }
}

// Show success message
function showSuccessMessage(message) {
    const successMsg = document.createElement('div');
    successMsg.textContent = message;
    successMsg.style.cssText = 'position: fixed; top: 20px; right: 20px; background: #10b981; color: white; padding: 8px 16px; border-radius: 6px; z-index: 10000; box-shadow: 0 2px 4px rgba(0,0,0,0.2); font-size: 13px;';
    document.body.appendChild(successMsg);
    
    setTimeout(() => {
        successMsg.remove();
    }, 1500);
}
