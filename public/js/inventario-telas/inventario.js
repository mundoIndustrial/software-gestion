// ========================================
// INVENTARIO DE TELAS - JavaScript
// ========================================

// Variables globales
let stockActualGlobal = 0;
let chartTelasMasMovidas = null;
let chartStockPorTela = null;
let historialData = [];

// ========================================
// INICIALIZACIÓN
// ========================================
document.addEventListener('DOMContentLoaded', function() {
    inicializarBuscadorYFiltros();
    agregarEstilosAnimaciones();
    agregarEventosCierreModales();
});

function inicializarBuscadorYFiltros() {
    const searchInput = document.getElementById('searchInput');
    const filterCategoria = document.getElementById('filterCategoria');
    const tableBody = document.getElementById('telasTableBody');
    
    if (!tableBody) return;
    
    const rows = tableBody.querySelectorAll('tr');

    function filterTable() {
        const searchTerm = searchInput.value.toLowerCase();
        const selectedCategoria = filterCategoria.value.toLowerCase();

        rows.forEach(row => {
            const nombre = row.dataset.nombre.toLowerCase();
            const categoria = row.dataset.categoria.toLowerCase();

            const matchesSearch = nombre.includes(searchTerm) || categoria.includes(searchTerm);
            const matchesCategoria = !selectedCategoria || categoria === selectedCategoria;

            if (matchesSearch && matchesCategoria) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    searchInput.addEventListener('input', filterTable);
    filterCategoria.addEventListener('change', filterTable);
}

// ========================================
// FUNCIONES PARA MODAL AJUSTAR STOCK
// ========================================
function abrirModalAjustarStock(telaId, telaNombre, stockActual) {
    stockActualGlobal = stockActual;
    document.getElementById('tela_id_ajuste').value = telaId;
    document.getElementById('tela_nombre_ajuste').textContent = telaNombre;
    document.getElementById('stock_actual_ajuste').textContent = stockActual + ' m';
    document.getElementById('preview_stock_actual').textContent = stockActual + ' m';
    document.getElementById('cantidad_ajuste').value = '';
    document.getElementById('observaciones_ajuste').value = '';
    actualizarVistaPrevia();
    document.getElementById('modalAjustarStock').style.display = 'flex';
    mostrarNotificacion(`Ajustando stock de "${telaNombre}" | Stock actual: ${stockActual.toFixed(2)} m`, 'info');
}

function cerrarModalAjustarStock() {
    document.getElementById('modalAjustarStock').style.display = 'none';
}

function actualizarVistaPrevia() {
    const tipoAccion = document.querySelector('input[name="tipo_accion"]:checked').value;
    const cantidad = parseFloat(document.getElementById('cantidad_ajuste').value) || 0;
    
    const operador = tipoAccion === 'entrada' ? '+' : '-';
    document.getElementById('preview_operator').textContent = operador;
    document.getElementById('preview_cantidad').textContent = cantidad.toFixed(2) + ' m';
    
    const nuevoStock = tipoAccion === 'entrada' 
        ? stockActualGlobal + cantidad 
        : stockActualGlobal - cantidad;
    
    document.getElementById('preview_stock_nuevo').textContent = nuevoStock.toFixed(2) + ' m';
    
    const stockNuevoElement = document.getElementById('preview_stock_nuevo');
    if (nuevoStock < 0) {
        stockNuevoElement.style.color = 'var(--danger-color)';
    } else {
        stockNuevoElement.style.color = 'var(--success-color)';
    }
}

function ajustarStock(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    const cantidad = parseFloat(formData.get('cantidad'));
    const tipoAccion = formData.get('tipo_accion');
    const telaId = formData.get('tela_id');
    const nuevoStock = tipoAccion === 'entrada' 
        ? stockActualGlobal + cantidad 
        : stockActualGlobal - cantidad;
    
    if (nuevoStock < 0) {
        alert('Error: El stock no puede ser negativo');
        return;
    }
    
    fetch('/inventario-telas/ajustar-stock', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            tela_id: telaId,
            tipo_accion: tipoAccion,
            cantidad: cantidad,
            observaciones: formData.get('observaciones')
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            actualizarStockEnTabla(telaId, nuevoStock);
            cerrarModalAjustarStock();
            
            const tipoTexto = tipoAccion === 'entrada' ? 'Entrada registrada' : 'Salida registrada';
            const cantidadTexto = cantidad.toFixed(2);
            mostrarNotificacion(`✓ ${tipoTexto}: ${cantidadTexto} m | Nuevo stock: ${nuevoStock.toFixed(2)} m`, 'success');
        } else {
            mostrarNotificacion('⚠ ' + (data.message || 'No se pudo ajustar el stock'), 'error');
        }
    })
    .catch(error => {

        mostrarNotificacion('✗ Error al ajustar el stock', 'error');
    });
}

function actualizarStockEnTabla(telaId, nuevoStock) {
    const rows = document.querySelectorAll('#telasTableBody tr');
    rows.forEach(row => {
        const stockCell = row.querySelector('.stock-badge');
        if (stockCell && row.dataset.telaId == telaId) {
            const stockClase = nuevoStock < 10 ? 'stock-bajo' : (nuevoStock < 50 ? 'stock-medio' : 'stock-alto');
            stockCell.className = 'stock-badge ' + stockClase;
            stockCell.textContent = (nuevoStock == Math.floor(nuevoStock) ? nuevoStock.toFixed(0) : nuevoStock.toFixed(2)) + ' m';
        }
    });
}

// ========================================
// FUNCIONES PARA MODAL CREAR TELA
// ========================================
function abrirModalCrearTela() {
    document.getElementById('formCrearTela').reset();
    document.getElementById('nuevaCategoriaContainer').style.display = 'none';
    document.getElementById('modalCrearTela').style.display = 'flex';
    mostrarNotificacion('Completa el formulario para registrar una nueva tela', 'info');
}

function cerrarModalCrearTela() {
    document.getElementById('modalCrearTela').style.display = 'none';
}

function mostrarInputNuevaCategoria() {
    document.getElementById('nuevaCategoriaContainer').style.display = 'block';
    document.getElementById('nueva_categoria_input').focus();
}

function cancelarNuevaCategoria() {
    document.getElementById('nuevaCategoriaContainer').style.display = 'none';
    document.getElementById('nueva_categoria_input').value = '';
}

function agregarNuevaCategoria() {
    const nuevaCategoria = document.getElementById('nueva_categoria_input').value.trim();
    if (nuevaCategoria) {
        const select = document.getElementById('categoria_nueva');
        const option = new Option(nuevaCategoria, nuevaCategoria, true, true);
        select.add(option);
        cancelarNuevaCategoria();
    }
}

function crearTela(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    const categoria = formData.get('categoria');
    const nombre_tela = formData.get('nombre_tela');
    const stock = parseFloat(formData.get('stock'));
    const metraje_sugerido = formData.get('metraje_sugerido') ? parseFloat(formData.get('metraje_sugerido')) : null;
    
    fetch('/inventario-telas/store', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            categoria: categoria,
            nombre_tela: nombre_tela,
            stock: stock,
            metraje_sugerido: metraje_sugerido
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            agregarTelaATabla(data.tela, categoria, nombre_tela, stock, metraje_sugerido);
            cerrarModalCrearTela();
            mostrarNotificacion(`✓ Tela "${nombre_tela}" creada exitosamente | Stock inicial: ${stock.toFixed(2)} m`, 'success');
        } else {
            mostrarNotificacion('⚠ ' + (data.message || 'No se pudo crear la tela'), 'error');
        }
    })
    .catch(error => {

        mostrarNotificacion('✗ Error al crear la tela', 'error');
    });
}

function agregarTelaATabla(tela, categoria, nombre_tela, stock, metraje_sugerido) {
    const tableBody = document.getElementById('telasTableBody');
    const stockClase = stock < 10 ? 'stock-bajo' : (stock < 50 ? 'stock-medio' : 'stock-alto');
    const stockFormato = stock == Math.floor(stock) ? stock.toFixed(0) : stock.toFixed(2);
    const metrajFormato = metraje_sugerido ? (metraje_sugerido == Math.floor(metraje_sugerido) ? metraje_sugerido.toFixed(0) : metraje_sugerido.toFixed(2)) + ' m' : '-';
    const fechaHoy = new Date().toLocaleDateString('es-ES') + ' ' + new Date().toLocaleTimeString('es-ES');
    
    const newRow = document.createElement('tr');
    newRow.dataset.categoria = categoria;
    newRow.dataset.nombre = nombre_tela;
    newRow.dataset.telaId = tela.id;
    newRow.innerHTML = `
        <td class="categoria-cell">${categoria}</td>
        <td class="nombre-tela">${nombre_tela}</td>
        <td>
            <span class="stock-badge ${stockClase}">
                ${stockFormato} m
            </span>
        </td>
        <td class="metraje-cell">${metrajFormato}</td>
        <td class="fecha-cell">${fechaHoy}</td>
        <td class="actions-cell">
            <button type="button" 
                    class="btn-action btn-adjust" 
                    onclick="abrirModalAjustarStock(${tela.id}, '${nombre_tela}', ${stock})"
                    title="Ajustar Stock">
                <span class="material-symbols-rounded">tune</span>
            </button>
            <button type="button" 
                    class="btn-action btn-delete" 
                    onclick="eliminarTela(${tela.id}, '${nombre_tela}')"
                    title="Eliminar Tela">
                <span class="material-symbols-rounded">delete</span>
            </button>
        </td>
    `;
    tableBody.insertBefore(newRow, tableBody.firstChild);
}

// ========================================
// FUNCIONES PARA ELIMINAR TELA
// ========================================
function eliminarTela(telaId, telaNombre) {
    mostrarConfirmacion(
        `Eliminar "${telaNombre}"`,
        `¿Estás seguro de que deseas eliminar la tela "${telaNombre}"? Se eliminará completamente del sistema junto con su historial de movimientos. Esta acción no se puede deshacer.`,
        () => confirmarEliminacionTela(telaId, telaNombre)
    );
}

function confirmarEliminacionTela(telaId, telaNombre) {
    fetch(`/inventario-telas/${telaId}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const rows = document.querySelectorAll('#telasTableBody tr');
            rows.forEach(row => {
                if (row.dataset.telaId == telaId) {
                    row.style.animation = 'fadeOut 0.3s ease-out';
                    setTimeout(() => row.remove(), 300);
                }
            });
            mostrarNotificacion(`✓ Tela "${telaNombre}" eliminada completamente del sistema`, 'success');
        } else {
            mostrarNotificacion('⚠ ' + (data.message || 'No se pudo eliminar la tela'), 'error');
        }
    })
    .catch(error => {

        mostrarNotificacion('✗ Error al eliminar la tela', 'error');
    });
}

// ========================================
// FUNCIONES PARA MODALES PERSONALIZADOS
// ========================================
function mostrarConfirmacion(titulo, mensaje, callback) {
    const modal = document.createElement('div');
    modal.className = 'modal-confirmacion-overlay';
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(4px);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10000;
        animation: fadeIn 0.2s ease;
    `;
    
    modal.innerHTML = `
        <div style="
            background: var(--bg-card, #ffffff);
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            width: 90%;
            max-width: 450px;
            animation: slideUp 0.3s ease;
            overflow: hidden;
        ">
            <div style="
                padding: 24px;
                border-bottom: 2px solid var(--border-color, #e5e7eb);
                display: flex;
                align-items: center;
                gap: 12px;
            ">
                <span class="material-symbols-rounded" style="
                    font-size: 28px;
                    color: #ef4444;
                ">warning</span>
                <h3 style="
                    margin: 0;
                    font-size: 18px;
                    font-weight: 700;
                    color: var(--text-primary, #000);
                ">${titulo}</h3>
            </div>
            
            <div style="padding: 24px;">
                <p style="
                    margin: 0;
                    color: var(--text-secondary, #666);
                    font-size: 14px;
                    line-height: 1.6;
                ">${mensaje}</p>
            </div>
            
            <div style="
                padding: 16px 24px;
                border-top: 2px solid var(--border-color, #e5e7eb);
                display: flex;
                gap: 12px;
                justify-content: flex-end;
            ">
                <button onclick="this.closest('.modal-confirmacion-overlay').remove()" style="
                    padding: 10px 20px;
                    border: none;
                    border-radius: 8px;
                    background: var(--bg-secondary, #f3f4f6);
                    color: var(--text-primary, #000);
                    font-weight: 600;
                    cursor: pointer;
                    transition: all 0.2s ease;
                " onmouseover="this.style.background='var(--bg-hover, #e5e7eb)'" onmouseout="this.style.background='var(--bg-secondary, #f3f4f6)'">
                    Cancelar
                </button>
                <button onclick="
                    const overlay = this.closest('.modal-confirmacion-overlay');
                    overlay.remove();
                    (${callback.toString()})();
                " style="
                    padding: 10px 20px;
                    border: none;
                    border-radius: 8px;
                    background: #ef4444;
                    color: white;
                    font-weight: 600;
                    cursor: pointer;
                    transition: all 0.2s ease;
                " onmouseover="this.style.background='#dc2626'" onmouseout="this.style.background='#ef4444'">
                    <span class="material-symbols-rounded" style="vertical-align: middle; margin-right: 6px; font-size: 18px;">delete</span>
                    Eliminar
                </button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.remove();
        }
    });
}

function mostrarNotificacion(mensaje, tipo = 'info') {
    const notificacion = document.createElement('div');
    notificacion.className = `notificacion notificacion-${tipo}`;
    
    const config = {
        success: {
            icon: 'check_circle',
            bgColor: '#10b981',
            borderColor: '#059669',
            textColor: '#ffffff'
        },
        error: {
            icon: 'error',
            bgColor: '#ef4444',
            borderColor: '#dc2626',
            textColor: '#ffffff'
        },
        info: {
            icon: 'info',
            bgColor: '#3b82f6',
            borderColor: '#1d4ed8',
            textColor: '#ffffff'
        },
        warning: {
            icon: 'warning',
            bgColor: '#f59e0b',
            borderColor: '#d97706',
            textColor: '#ffffff'
        }
    };
    
    const cfg = config[tipo] || config.info;
    
    notificacion.innerHTML = `
        <div style="display: flex; align-items: center; gap: 12px;">
            <span class="material-symbols-rounded" style="font-size: 24px; flex-shrink: 0;">${cfg.icon}</span>
            <span style="flex: 1; font-weight: 500;">${mensaje}</span>
            <button onclick="this.parentElement.parentElement.remove()" style="background: none; border: none; cursor: pointer; color: inherit; padding: 4px; display: flex; align-items: center;">
                <span class="material-symbols-rounded" style="font-size: 20px;">close</span>
            </button>
        </div>
    `;
    
    notificacion.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 16px 20px;
        border-radius: 12px;
        font-weight: 500;
        z-index: 9999;
        animation: slideIn 0.3s ease-out;
        background-color: ${cfg.bgColor};
        color: ${cfg.textColor};
        border-left: 4px solid ${cfg.borderColor};
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        max-width: 400px;
        min-width: 300px;
        font-size: 14px;
    `;
    
    document.body.appendChild(notificacion);
    
    setTimeout(() => {
        notificacion.style.animation = 'slideOut 0.3s ease-out';
        setTimeout(() => notificacion.remove(), 300);
    }, 4000);
}

// ========================================
// FUNCIONES PARA MODAL HISTORIAL
// ========================================
function abrirModalHistorial() {
    document.getElementById('modalHistorialTelas').style.display = 'flex';
    cargarDatosHistorial();
}

function cerrarModalHistorial() {
    document.getElementById('modalHistorialTelas').style.display = 'none';
}

function cambiarTab(tabName) {
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    document.getElementById('tab-' + tabName).classList.add('active');
    event.target.closest('.tab-btn').classList.add('active');
}

function cargarDatosHistorial() {
    fetch('/inventario-telas/historial', {
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        historialData = data.historial;
        
        document.getElementById('totalEntradas').textContent = data.estadisticas.total_entradas;
        document.getElementById('totalSalidas').textContent = data.estadisticas.total_salidas;
        document.getElementById('stockTotal').textContent = data.estadisticas.stock_total + ' m';
        
        crearGraficaTelasMasMovidas(data.telas_mas_movidas);
        crearGraficaStockPorTela(data.stock_por_tela);
        llenarTablaHistorial(data.historial);
        llenarFiltroTelas(data.telas);
    })
    .catch(error => {

        alert('Error al cargar el historial');
    });
}

function crearGraficaTelasMasMovidas(datos) {
    const ctx = document.getElementById('chartTelasMasMovidas');
    
    if (chartTelasMasMovidas) {
        chartTelasMasMovidas.destroy();
    }
    
    chartTelasMasMovidas = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: datos.map(d => d.nombre_tela),
            datasets: [{
                label: 'Total Movimientos (m)',
                data: datos.map(d => d.total_movimientos),
                backgroundColor: 'rgba(0, 102, 204, 0.7)',
                borderColor: 'rgba(0, 102, 204, 1)',
                borderWidth: 2,
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                    titleFont: { size: 14, weight: 'bold' },
                    bodyFont: { size: 13 },
                    callbacks: {
                        label: function(context) {
                            return 'Total: ' + context.parsed.y.toFixed(2) + ' m';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value + ' m';
                        }
                    },
                    grid: { color: 'rgba(0, 0, 0, 0.05)' }
                },
                x: { grid: { display: false } }
            }
        }
    });
}

function crearGraficaStockPorTela(datos) {
    const ctx = document.getElementById('chartStockPorTela');
    
    if (chartStockPorTela) {
        chartStockPorTela.destroy();
    }
    
    datos.sort((a, b) => b.stock - a.stock);
    
    chartStockPorTela = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: datos.map(d => d.nombre_tela),
            datasets: [{
                label: 'Stock Actual (m)',
                data: datos.map(d => d.stock),
                backgroundColor: datos.map(d => {
                    if (d.stock < 10) return 'rgba(220, 38, 38, 0.7)';
                    if (d.stock < 50) return 'rgba(247, 127, 0, 0.7)';
                    return 'rgba(0, 168, 107, 0.7)';
                }),
                borderColor: datos.map(d => {
                    if (d.stock < 10) return 'rgba(220, 38, 38, 1)';
                    if (d.stock < 50) return 'rgba(247, 127, 0, 1)';
                    return 'rgba(0, 168, 107, 1)';
                }),
                borderWidth: 2,
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                    titleFont: { size: 14, weight: 'bold' },
                    bodyFont: { size: 13 },
                    callbacks: {
                        label: function(context) {
                            return 'Stock: ' + context.parsed.x.toFixed(2) + ' m';
                        }
                    }
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value + ' m';
                        }
                    },
                    grid: { color: 'rgba(0, 0, 0, 0.05)' }
                },
                y: { grid: { display: false } }
            }
        }
    });
}

function llenarTablaHistorial(historial) {
    const tbody = document.getElementById('historialTableBody');
    
    if (historial.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="loading-cell">No hay movimientos registrados</td></tr>';
        return;
    }
    
    tbody.innerHTML = historial.map(h => `
        <tr data-tipo="${h.tipo_accion}" data-tela="${h.tela_nombre}">
            <td>${new Date(h.fecha_accion).toLocaleString('es-ES')}</td>
            <td><strong>${h.tela_nombre}</strong></td>
            <td>
                <span class="accion-badge accion-${h.tipo_accion}">
                    ${h.tipo_accion.charAt(0).toUpperCase() + h.tipo_accion.slice(1)}
                </span>
            </td>
            <td><strong>${parseFloat(h.cantidad).toFixed(2)} m</strong></td>
            <td>${parseFloat(h.stock_anterior).toFixed(2)} m</td>
            <td>${parseFloat(h.stock_nuevo).toFixed(2)} m</td>
            <td>${h.usuario_nombre}</td>
            <td>${h.observaciones || '-'}</td>
        </tr>
    `).join('');
}

function llenarFiltroTelas(telas) {
    const select = document.getElementById('filtroTelaHistorial');
    select.innerHTML = '<option value="">Todas las telas</option>';
    
    telas.forEach(tela => {
        const option = document.createElement('option');
        option.value = tela.nombre_tela;
        option.textContent = tela.nombre_tela;
        select.appendChild(option);
    });
}

function filtrarHistorial() {
    const tipoAccion = document.getElementById('filtroTipoAccion').value;
    const tela = document.getElementById('filtroTelaHistorial').value;
    
    const rows = document.querySelectorAll('#historialTableBody tr');
    
    rows.forEach(row => {
        const rowTipo = row.dataset.tipo;
        const rowTela = row.dataset.tela;
        
        const matchTipo = !tipoAccion || rowTipo === tipoAccion;
        const matchTela = !tela || rowTela === tela;
        
        if (matchTipo && matchTela) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

// ========================================
// FUNCIONES AUXILIARES
// ========================================
function agregarEstilosAnimaciones() {
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideIn {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(400px);
                opacity: 0;
            }
        }
        
        @keyframes fadeOut {
            from {
                opacity: 1;
                transform: translateX(0);
            }
            to {
                opacity: 0;
                transform: translateX(-20px);
            }
        }
    `;
    document.head.appendChild(style);
}

function agregarEventosCierreModales() {
    document.addEventListener('click', function(event) {
        if (event.target.classList.contains('modal-overlay')) {
            event.target.style.display = 'none';
        }
    });
}
