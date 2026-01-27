/**
 * =========================================
 * CARTERA PEDIDOS - APP JS
 * Lógica limpia sin dependencias
 * =========================================
 */

    // ===== VARIABLES GLOBALES =====
let pedidosData = [];
let pedidoSeleccionado = null;
const API_BASE = '/api/cartera/pedidos';

// ===== HELPER: Obtener elemento =====
function el(selector) {
    return document.querySelector(selector);
}

function elById(id) {
    return document.getElementById(id);
}

// ===== FUNCIONES DE CARGA =====
function mostrarCargando(mensaje = 'Cargando...') {
    let spinner = document.getElementById('loadingSpinner');
    if (!spinner) {
        spinner = document.createElement('div');
        spinner.id = 'loadingSpinner';
        spinner.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 10000;';
        spinner.innerHTML = '<div style="background: white; padding: 2rem; border-radius: 8px; text-align: center;"><div style="border: 4px solid #f3f4f6; border-top: 4px solid #3b82f6; border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; margin: 0 auto 1rem;"></div><p style="margin: 0; color: #6b7280; font-size: 0.95rem;">' + mensaje + '</p></div><style>@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } } @keyframes slideInRight { from { transform: translateX(400px); opacity: 0; } to { transform: translateX(0); opacity: 1; } }</style>';
        document.body.appendChild(spinner);
    } else {
        spinner.style.display = 'flex';
        spinner.querySelector('p').textContent = mensaje;
    }
}

function ocultarCargando() {
    const spinner = document.getElementById('loadingSpinner');
    if (spinner) {
        spinner.style.display = 'none';
    }
}

// ===== INICIALIZACIÓN =====
document.addEventListener('DOMContentLoaded', function() {
    console.log('Cartera Pedidos APP - Inicializado');
    
    // Cargar pedidos
    cargarPedidos();
    
    // Event listeners
    const btnRefresh = elById('btnRefreshPedidos');
    if (btnRefresh) {
        btnRefresh.addEventListener('click', cargarPedidos);
    }
    
    const btnConfirmarAprobacion = elById('btnConfirmarAprobacion');
    if (btnConfirmarAprobacion) {
        btnConfirmarAprobacion.addEventListener('click', confirmarAprobacion);
    }
    
    const formRechazo = elById('formRechazo');
    if (formRechazo) {
        formRechazo.addEventListener('submit', confirmarRechazo);
    }
    
    const motivoRechazo = elById('motivoRechazo');
    if (motivoRechazo) {
        motivoRechazo.addEventListener('input', function() {
            const count = this.value.length;
            const counter = elById('charCount');
            if (counter) counter.textContent = count;
        });
    }
});

// ===== CARGAR PEDIDOS =====
async function cargarPedidos() {
    const btnRefresh = elById('btnRefreshPedidos');
    const tablaPedidosBody = elById('tablaPedidosBody');
    
    // Obtener los elementos contenedores
    const tableHead = document.querySelector('.table-head');
    const modernTable = document.querySelector('.modern-table');
    const emptyState = elById('emptyState');
    const loadingState = elById('loadingState');
    
    try {
        // Mostrar loading
        if (tableHead) tableHead.style.display = 'none';
        if (modernTable) modernTable.style.display = 'none';
        if (emptyState) emptyState.style.display = 'none';
        if (loadingState) loadingState.style.display = 'block';
        
        if (btnRefresh) btnRefresh.disabled = true;
        
        // Obtener token CSRF
        const csrfMeta = el('meta[name="csrf-token"]');
        const token = csrfMeta ? csrfMeta.content : '';
        
        // Llamar API
        const response = await fetch(`${API_BASE}?estado=pendiente_cartera`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token,
                'Content-Type': 'application/json',
            },
            credentials: 'same-origin'
        });
        
        if (!response.ok) {
            throw new Error(`Error HTTP: ${response.status}`);
        }
        
        const data = await response.json();
        
        // Procesar datos
        if (data.data && Array.isArray(data.data)) {
            pedidosData = data.data;
        } else if (Array.isArray(data)) {
            pedidosData = data;
        } else {
            pedidosData = [];
        }
        
        console.log(' Pedidos cargados:', pedidosData);
        
        // Renderizar tabla
        if (loadingState) loadingState.style.display = 'none';
        
        if (pedidosData.length > 0) {
            renderizarTabla(pedidosData);
            if (tableHead) tableHead.style.display = 'block';
            if (modernTable) modernTable.style.display = 'block';
            if (emptyState) emptyState.style.display = 'none';
        } else {
            // Vaciar tabla pero mantener header visible
            if (tablaPedidosBody) tablaPedidosBody.innerHTML = '';
            if (tableHead) tableHead.style.display = 'block';
            if (modernTable) modernTable.style.display = 'none';
            if (emptyState) emptyState.style.display = 'flex';
        }
        
    } catch (error) {
        console.error(' Error cargando pedidos:', error);
        if (loadingState) loadingState.style.display = 'none';
        mostrarNotificacion('Error al cargar los pedidos', 'danger');
    } finally {
        if (btnRefresh) btnRefresh.disabled = false;
    }
}

// ===== RENDERIZAR TABLA =====
function renderizarTabla(pedidos) {
    const tablaPedidosBody = elById('tablaPedidosBody');
    if (!tablaPedidosBody) return;
    
    console.log('📊 Renderizando tabla con pedidos:', pedidos.length);
    
    // Obtener tamaños del header para debug
    const headerCells = document.querySelectorAll('.table-header-cell');
    console.log('📐 Encabezados encontrados:', headerCells.length);
    headerCells.forEach((cell, idx) => {
        const flex = cell.style.flex;
        const width = cell.offsetWidth;
        console.log(`  ${idx}. Flex: ${flex}, Ancho real: ${width}px`);
    });
    
    tablaPedidosBody.innerHTML = '';
    
    pedidos.forEach(pedido => {
        const row = document.createElement('div');
        row.className = 'table-row';
        row.setAttribute('data-orden-id', pedido.id);
        row.setAttribute('data-numero', pedido.numero);
        
        row.innerHTML = `
            <!-- Acciones -->
            <div class="table-cell" style="flex: 0 0 200px; justify-content: flex-start; display: flex; gap: 0.5rem; align-items: center;">
                <button class="btn-action btn-success" title="Aprobar pedido" onclick="abrirModalAprobacion(${pedido.id}, '${pedido.numero}')">
                    <span class="material-symbols-rounded">check_circle</span>
                </button>
                <button class="btn-action btn-danger" title="Rechazar pedido" onclick="abrirModalRechazo(${pedido.id}, '${pedido.numero}')">
                    <span class="material-symbols-rounded">cancel</span>
                </button>
                <button class="btn-action btn-info" title="Ver factura" onclick="verFactura(${pedido.id}, '${pedido.numero}')">
                    <span class="material-symbols-rounded">receipt</span>
                </button>
            </div>
            
            <!-- Número -->
            <div class="table-cell" style="flex: 0 0 140px; justify-content: center; display: flex; align-items: center;">
                <span style="font-weight: 600; color: #1e5ba8;">#${pedido.numero}</span>
            </div>
            
            <!-- Cliente -->
            <div class="table-cell" style="flex: 0 0 200px; justify-content: center; display: flex; align-items: center;">
                <span>${pedido.cliente_nombre || 'N/A'}</span>
            </div>
            
            <!-- Fecha -->
            <div class="table-cell" style="flex: 0 0 160px; justify-content: center; display: flex; align-items: center;">
                <span>${new Date(pedido.created_at).toLocaleDateString('es-CO')}</span>
            </div>
        `;
        tablaPedidosBody.appendChild(row);
    });
    
    // Logs después de renderizar
    console.log(' Tabla renderizada');
    setTimeout(() => {
        const headerRow = document.querySelector('.table-head > div');
        const firstBodyRow = document.querySelector('.table-row');
        
        if (headerRow && firstBodyRow) {
            const headerWidth = headerRow.offsetWidth;
            const bodyRowWidth = firstBodyRow.offsetWidth;
            console.log(` COMPARACIÓN DE ANCHO:`);
            console.log(`  Header width: ${headerWidth}px`);
            console.log(`  Body row width: ${bodyRowWidth}px`);
            console.log(`  Diferencia: ${bodyRowWidth - headerWidth}px`);
            
            // Analizar cada celda del body
            const bodyCells = firstBodyRow.querySelectorAll('.table-cell');
            console.log(`📊 Celdas del body:`, bodyCells.length);
            bodyCells.forEach((cell, idx) => {
                const width = cell.offsetWidth;
                const flex = cell.style.flex;
                console.log(`  ${idx}. Ancho: ${width}px, Flex: ${flex}`);
            });
        }
    }, 100);
}

// ===== MODAL APROBACIÓN =====
function verFactura(pedidoId, numeroPedido) {
    // Abre la factura del pedido usando la ruta de cartera
    console.log(`📄 Ver factura del pedido #${numeroPedido}`);
    
    // Usar fetch para obtener datos desde la ruta de cartera
    mostrarCargando('Cargando factura...');
    
    fetch(`/api/cartera/pedidos/${pedidoId}/factura-datos`, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        if (!response.ok) throw new Error(`Error ${response.status}`);
        return response.json();
    })
    .then(datos => {
        ocultarCargando();
        console.log(' Datos de factura obtenidos:', datos);
        
        // Usar la función profesional de factura que tiene la asesora
        if (typeof crearModalPreviewFactura === 'function') {
            crearModalPreviewFactura(datos);
        } else {
            console.error(' crearModalPreviewFactura no está disponible');
            mostrarNotificacion('Error: Sistema de factura no disponible', 'danger');
        }
    })
    .catch(error => {
        ocultarCargando();
        console.error(' Error cargando factura:', error);
        mostrarNotificacion('Error al cargar factura: ' + error.message, 'danger');
    });
   
}

function abrirModalAprobacion(pedidoId, numeroPedido) {
    pedidoSeleccionado = { id: pedidoId, numero: numeroPedido };
    
    const pedidoNumero = elById('pedidoNumeroAprobacion');
    if (pedidoNumero) pedidoNumero.textContent = numeroPedido;
    
    const modal = elById('modalAprobacion');
    if (modal) modal.classList.add('open');
}

function cerrarModalAprobacion() {
    const modal = elById('modalAprobacion');
    if (modal) modal.classList.remove('open');
}

async function confirmarAprobacion() {
    if (!pedidoSeleccionado || !pedidoSeleccionado.id) {
        mostrarNotificacion('Error: Pedido no seleccionado', 'danger');
        return;
    }
    
    const btnConfirmar = elById('btnConfirmarAprobacion');
    const pedidoId = pedidoSeleccionado.id;
    const numeroPedido = pedidoSeleccionado.numero;
    
    try {
        if (btnConfirmar) {
            btnConfirmar.disabled = true;
            btnConfirmar.classList.add('loading');
        }
        
        const csrfMeta = el('meta[name="csrf-token"]');
        const token = csrfMeta ? csrfMeta.content : '';
        
        const response = await fetch(`${API_BASE}/${pedidoId}/aprobar`, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token,
                'Content-Type': 'application/json',
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                pedido_id: pedidoId,
                accion: 'aprobar'
            })
        });
        
        const data = await response.json();
        
        if (!response.ok) {
            throw new Error(data.message || `Error: ${response.status}`);
        }
        
        console.log(' Pedido aprobado:', data);
        
        // Mostrar notificación temporal de éxito
        const notifSuccess = document.createElement('div');
        notifSuccess.className = 'alert alert-success';
        notifSuccess.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999; animation: slideInRight 0.3s ease;';
        notifSuccess.innerHTML = `
            <span class="material-symbols-rounded" style="flex-shrink: 0;">check_circle</span>
            <span>Pedido #${numeroPedido} aprobado exitosamente</span>
        `;
        document.body.appendChild(notifSuccess);
        
        // Remover después de 3 segundos
        setTimeout(() => notifSuccess.remove(), 3000);
        
        cerrarModalAprobacion();
        
        // Recargar tabla inmediatamente
        cargarPedidos();
        
    } catch (error) {
        console.error(' Error aprobando:', error);
        mostrarNotificacion('Error al aprobar: ' + error.message, 'danger');
    } finally {
        if (btnConfirmar) {
            btnConfirmar.disabled = false;
            btnConfirmar.classList.remove('loading');
        }
    }
}

// ===== MODAL RECHAZO =====
function abrirModalRechazo(pedidoId, numeroPedido) {
    pedidoSeleccionado = { id: pedidoId, numero: numeroPedido };
    
    const pedidoNumero = elById('pedidoNumeroRechazo');
    if (pedidoNumero) pedidoNumero.textContent = numeroPedido;
    
    const motivoRechazo = elById('motivoRechazo');
    if (motivoRechazo) {
        motivoRechazo.value = '';
    }
    
    const modal = elById('modalRechazo');
    if (modal) modal.classList.add('open');
}

function cerrarModalRechazo() {
    const modal = elById('modalRechazo');
    if (modal) modal.classList.remove('open');
}

async function confirmarRechazo(event) {
    event.preventDefault();
    
    if (!pedidoSeleccionado || !pedidoSeleccionado.id) {
        mostrarNotificacion('Error: Pedido no seleccionado', 'danger');
        return;
    }
    
    const motivoElement = elById('motivoRechazo');
    if (!motivoElement) {
        mostrarNotificacion('Error: Campo de motivo no encontrado', 'danger');
        return;
    }
    
    const motivo = motivoElement.value.trim();
    
    if (!motivo) {
        mostrarNotificacion('El motivo es requerido', 'warning');
        return;
    }
    
    const btnConfirmar = elById('btnConfirmarRechazo');
    const pedidoId = pedidoSeleccionado.id;
    const numeroPedido = pedidoSeleccionado.numero;
    
    try {
        if (btnConfirmar) {
            btnConfirmar.disabled = true;
            btnConfirmar.classList.add('loading');
        }
        
        const csrfMeta = el('meta[name="csrf-token"]');
        const token = csrfMeta ? csrfMeta.content : '';
        
        const response = await fetch(`${API_BASE}/${pedidoId}/rechazar`, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token,
                'Content-Type': 'application/json',
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                pedido_id: pedidoId,
                motivo: motivo,
                accion: 'rechazar'
            })
        });
        
        const data = await response.json();
        
        if (!response.ok) {
            throw new Error(data.message || `Error: ${response.status}`);
        }
        
        console.log(' Pedido rechazado:', data);
        
        // Mostrar notificación temporal de éxito
        const notifSuccess = document.createElement('div');
        notifSuccess.className = 'alert alert-success';
        notifSuccess.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999; animation: slideInRight 0.3s ease;';
        notifSuccess.innerHTML = `
            <span class="material-symbols-rounded" style="flex-shrink: 0;">check_circle</span>
            <span>Pedido #${numeroPedido} rechazado exitosamente</span>
        `;
        document.body.appendChild(notifSuccess);
        
        // Remover después de 3 segundos
        setTimeout(() => notifSuccess.remove(), 3000);
        
        cerrarModalRechazo();
        
        // Recargar tabla inmediatamente
        cargarPedidos();
        
    } catch (error) {
        console.error(' Error rechazando:', error);
        mostrarNotificacion('Error al rechazar: ' + error.message, 'danger');
    } finally {
        if (btnConfirmar) {
            btnConfirmar.disabled = false;
            btnConfirmar.classList.remove('loading');
        }
    }
}

// ===== NOTIFICACIONES =====
function mostrarNotificacion(mensaje, tipo = 'info') {
    const container = elById('notificacionesContainer');
    if (!container) return;
    
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${tipo}`;
    alertDiv.innerHTML = `
        <span class="material-symbols-rounded" style="flex-shrink: 0;">
            ${tipo === 'success' ? 'check_circle' : tipo === 'danger' ? 'error' : tipo === 'warning' ? 'warning' : 'info'}
        </span>
        <span>${mensaje}</span>
    `;
    
    container.appendChild(alertDiv);
    
    // Auto-remove después de 4 segundos
    setTimeout(() => {
        alertDiv.remove();
    }, 4000);
}
