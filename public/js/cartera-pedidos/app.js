/**
 * =========================================
 * CARTERA PEDIDOS - APP JS
 * Lógica limpia sin dependencias
 * Con Paginación y Filtros
 * =========================================
 */

// ===== VARIABLES GLOBALES =====
let carteraMainPedidosData = [];
let pedidoSeleccionado = null;
const API_BASE = '/api/cartera/pedidos';
let currentPage = 1;
let totalPages = 1;
let perPage = 15;
let currentSearch = '';
let currentSort = 'fecha';
let currentSortOrder = 'desc';
let filtroCliente = '';
let filtroFechaDesde = '';
let filtroFechaHasta = '';

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

// ===== FUNCIONES DE FILTROS MODALES =====
function cargarOpcionesFiltro() {
    return fetch('/api/cartera/opciones-filtro')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Cargar opciones de cliente
                const selectCliente = elById('filtroClienteSelect');
                if (selectCliente && data.clientes) {
                    const clienteActual = selectCliente.value;
                    selectCliente.innerHTML = '<option value="">-- Todos los clientes --</option>';
                    data.clientes.forEach(cliente => {
                        const option = document.createElement('option');
                        option.value = cliente;
                        option.textContent = cliente;
                        selectCliente.appendChild(option);
                    });
                    selectCliente.value = clienteActual;
                }
                
                // Cargar opciones de fecha
                const selectFecha = elById('filtroFechaSelect');
                if (selectFecha && data.fechas) {
                    const fechaActual = selectFecha.value;
                    selectFecha.innerHTML = '<option value="">-- Todas las fechas --</option>';
                    data.fechas.forEach(fecha => {
                        const option = document.createElement('option');
                        option.value = fecha;
                        option.textContent = fecha;
                        selectFecha.appendChild(option);
                    });
                    selectFecha.value = fechaActual;
                }
            }
        })
        .catch(error => {
            console.error('Error cargando opciones de filtro:', error);
        });
}

function abrirModalFiltro(tipo, event) {
    if (event) {
        event.stopPropagation();
    }
    console.log('🔍 abrirModalFiltro en app.js:', tipo);
    const modal = document.getElementById(`modalFiltro${tipo.charAt(0).toUpperCase() + tipo.slice(1)}`);
    if (modal) {
        modal.classList.add('active');
        modal.style.display = 'flex';
        
        // Enfocar el input si existe
        const input = document.getElementById(`filtro${tipo.charAt(0).toUpperCase() + tipo.slice(1)}Input`);
        if (input) {
            setTimeout(() => input.focus(), 100);
        }
    }
}

function cerrarModalFiltro(tipo) {
    console.log('🔍 cerrarModalFiltro en app.js:', tipo);
    const modal = document.getElementById(`modalFiltro${tipo.charAt(0).toUpperCase() + tipo.slice(1)}`);
    if (modal) {
        modal.classList.remove('active');
        modal.style.display = 'none';
    }
}

function aplicarFiltroCliente() {
    console.log('🔍 APLICAR FILTRO CLIENTE desde app.js');
    const input = document.getElementById('filtroClienteInput');
    const valor = input ? input.value.trim() : '';
    
    if (valor) {
        filtroCliente = valor;
        currentPage = 1;
        cargarPedidos();
        cerrarModalFiltro('cliente');
        mostrarNotificacion(`Filtro de cliente aplicado: "${valor}"`, 'info');
    } else {
        mostrarNotificacion('Por favor selecciona un cliente', 'warning');
    }
}

function aplicarFiltroFecha() {
    console.log('🔍 APLICAR FILTRO FECHA desde app.js');
    const input = document.getElementById('filtroFechaInput');
    const valor = input ? input.value.trim() : '';
    
    if (valor) {
        // Si se selecciona una fecha, usa la misma para desde y hasta (para filtrar solo ese día)
        filtroFechaDesde = valor;
        filtroFechaHasta = valor;
        currentPage = 1;
        cargarPedidos();
        cerrarModalFiltro('fecha');
        mostrarNotificacion(`Filtro de fecha aplicado: ${valor}`, 'info');
    } else {
        // Si se limpia la selección, limpia ambos filtros
        filtroFechaDesde = '';
        filtroFechaHasta = '';
        currentPage = 1;
        cargarPedidos();
        cerrarModalFiltro('fecha');
        mostrarNotificacion('Filtro de fecha removido', 'info');
    }
}

function aplicarFiltroNumero() {
    console.log('🔍 APLICAR FILTRO NÚMERO desde app.js');
    const input = document.getElementById('filtroNumeroInput');
    const valor = input ? input.value.trim() : '';
    
    if (valor) {
        currentSearch = valor;
        currentPage = 1;
        cargarPedidos();
        cerrarModalFiltro('numero');
        mostrarNotificacion(`Filtro de número aplicado: "${valor}"`, 'info');
    } else {
        mostrarNotificacion('Por favor ingresa un número de pedido', 'warning');
    }
}

// ===== INICIALIZACIÓN =====
document.addEventListener('DOMContentLoaded', function() {
    // Cargar pedidos por primera vez
    cargarPedidos();
    
    // Event listeners para búsqueda
    const searchInput = elById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            currentSearch = e.target.value;
            currentPage = 1;
            cargarPedidos();
        });
    }
    
    // Event listeners para paginación
    const btnFirstPage = elById('btnFirstPage');
    if (btnFirstPage) btnFirstPage.addEventListener('click', () => goToPage(1));
    
    const btnPrevPage = elById('btnPrevPage');
    if (btnPrevPage) btnPrevPage.addEventListener('click', () => goToPage(currentPage - 1));
    
    const btnNextPage = elById('btnNextPage');
    if (btnNextPage) btnNextPage.addEventListener('click', () => goToPage(currentPage + 1));
    
    const btnLastPage = elById('btnLastPage');
    if (btnLastPage) btnLastPage.addEventListener('click', () => goToPage(totalPages));
    
    // Event listeners para ordenamiento en headers
    const headerCellsSortable = document.querySelectorAll('.table-header-cell-cartera.sortable');
    headerCellsSortable.forEach(cell => {
        cell.addEventListener('click', function() {
            const sortType = this.getAttribute('data-sort');
            if (currentSort === sortType) {
                currentSortOrder = currentSortOrder === 'asc' ? 'desc' : 'asc';
            } else {
                currentSort = sortType;
                currentSortOrder = 'desc';
            }
            updateSortIndicators();
            currentPage = 1;
            cargarPedidos();
        });
    });
    
    const btnConfirmarAprobacion = elById('btnConfirmarAprobacion');
    if (btnConfirmarAprobacion) {
        btnConfirmarAprobacion.addEventListener('click', confirmarAprobacion);
    }
    
    const formRechazo = elById('formRechazo');
    if (formRechazo) {
        formRechazo.addEventListener('submit', confirmarRechazo);
    }
});

function updateSortIndicators() {
    const sortableCells = document.querySelectorAll('.table-header-cell-cartera.sortable');
    sortableCells.forEach(cell => {
        cell.classList.remove('sort-asc', 'sort-desc');
        if (cell.getAttribute('data-sort') === currentSort) {
            cell.classList.add(currentSortOrder === 'asc' ? 'sort-asc' : 'sort-desc');
        }
    });
}

function goToPage(page) {
    if (page >= 1 && page <= totalPages) {
        currentPage = page;
        cargarPedidos();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
}

// ===== CARGAR PEDIDOS CON PAGINACIÓN =====
async function cargarPedidos() {
    const btnRefresh = elById('btnRefreshPedidos');
    const tablaPedidosBody = elById('tablaPedidosBody');
    
    const modernTable = document.querySelector('.modern-table-cartera');
    const emptyState = elById('emptyState');
    const loadingState = elById('loadingState');
    const paginationContainer = elById('paginationContainer');
    
    try {
        // Mostrar loading
        if (modernTable) modernTable.style.display = 'none';
        if (emptyState) emptyState.style.display = 'none';
        if (loadingState) loadingState.style.display = 'flex';
        if (paginationContainer) paginationContainer.style.display = 'none';
        
        if (btnRefresh) btnRefresh.disabled = true;
        
        // Obtener token CSRF
        const csrfMeta = el('meta[name="csrf-token"]');
        const token = csrfMeta ? csrfMeta.content : '';
        
        // Construir URL con parámetros
        const url = new URL(`${API_BASE}?estado=pendiente_cartera`, window.location.origin);
        url.searchParams.set('page', currentPage);
        url.searchParams.set('per_page', perPage);
        if (currentSearch) url.searchParams.set('search', currentSearch);
        if (filtroCliente) url.searchParams.set('cliente', filtroCliente);
        if (filtroFechaDesde) url.searchParams.set('fecha_desde', filtroFechaDesde);
        if (filtroFechaHasta) url.searchParams.set('fecha_hasta', filtroFechaHasta);
        url.searchParams.set('sort_by', currentSort);
        url.searchParams.set('sort_order', currentSortOrder);
        
        // Llamar API
        const response = await fetch(url.toString(), {
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
            carteraMainPedidosData = data.data;
        } else if (Array.isArray(data)) {
            carteraMainPedidosData = data;
        } else {
            carteraMainPedidosData = [];
        }
        
        // Actualizar información de paginación
        if (data.pagination) {
            totalPages = data.pagination.last_page || 1;
            currentPage = data.pagination.page || 1;
            perPage = data.pagination.per_page || 15;
            
            // Actualizar controles de paginación
            updatePaginationControls(data.pagination);
        }
        
        
        // Renderizar tabla
        if (loadingState) loadingState.style.display = 'none';
        
        if (carteraMainPedidosData.length > 0) {
            renderizarTabla(carteraMainPedidosData);
            if (modernTable) modernTable.style.display = 'flex';
            if (emptyState) emptyState.style.display = 'none';
            if (paginationContainer) paginationContainer.style.display = 'flex';
        } else {
            if (tablaPedidosBody) tablaPedidosBody.innerHTML = '';
            if (modernTable) modernTable.style.display = 'none';
            if (emptyState) emptyState.style.display = 'flex';
            if (paginationContainer) paginationContainer.style.display = 'none';
        }
        
    } catch (error) {
        console.error('✗ Error cargando pedidos:', error);
        if (loadingState) loadingState.style.display = 'none';
        mostrarNotificacion('Error al cargar los pedidos', 'danger');
    } finally {
        if (btnRefresh) btnRefresh.disabled = false;
    }
}

function updatePaginationControls(pagination) {
    // Verificar si los elementos de paginación existen antes de actualizarlos
    const currentPageEl = elById('currentPage');
    const totalPagesEl = elById('totalPages');
    const showingFromEl = elById('showingFrom');
    const showingToEl = elById('showingTo');
    const totalRecordsEl = elById('totalRecords');
    
    if (currentPageEl) currentPageEl.textContent = pagination.page;
    if (totalPagesEl) totalPagesEl.textContent = pagination.last_page;
    if (showingFromEl) showingFromEl.textContent = pagination.from;
    if (showingToEl) showingToEl.textContent = pagination.to;
    if (totalRecordsEl) totalRecordsEl.textContent = pagination.total;
    
    const btnFirst = elById('btnFirstPage');
    const btnPrev = elById('btnPrevPage');
    const btnNext = elById('btnNextPage');
    const btnLast = elById('btnLastPage');
    
    if (btnFirst) btnFirst.disabled = pagination.page <= 1;
    if (btnPrev) btnPrev.disabled = pagination.page <= 1;
    if (btnNext) btnNext.disabled = pagination.page >= pagination.last_page;
    if (btnLast) btnLast.disabled = pagination.page >= pagination.last_page;
}

// ===== RENDERIZAR TABLA =====
function renderizarTabla(pedidos) {
    const tablaPedidosBody = elById('tablaPedidosBody');
    if (!tablaPedidosBody) return;
    
    
    tablaPedidosBody.innerHTML = '';
    
    pedidos.forEach(pedido => {
        const row = document.createElement('div');
        row.className = 'table-row-cartera';
        row.setAttribute('data-orden-id', pedido.id);
        row.setAttribute('data-numero', pedido.numero);
        
        const fechaFormato = new Date(pedido.created_at).toLocaleDateString('es-CO');
        
        // Detectar la página actual para mostrar los botones correctos
        const currentPath = window.location.pathname;
        let botonesHTML = '';
        
        if (currentPath.includes('/cartera/pedidos')) {
            // Vista de pendientes: 3 botones (aprobar, rechazar, factura)
            botonesHTML = `
                <button class="btn-action-cartera btn-success-cartera" title="Aprobar pedido" onclick="abrirModalAprobacion(${pedido.id}, '${pedido.numero_pedido || pedido.numero || 'N/A'}')" style="padding: 8px 10px; display: flex; align-items: center; justify-content: center;">
                    <span class="material-symbols-rounded" style="font-size: 1.3rem;">check_circle</span>
                </button>
                <button class="btn-action-cartera btn-danger-cartera" title="Rechazar pedido" onclick="abrirModalRechazo(${pedido.id}, '${pedido.numero_pedido || pedido.numero || 'N/A'}')" style="padding: 8px 10px; display: flex; align-items: center; justify-content: center;">
                    <span class="material-symbols-rounded" style="font-size: 1.3rem;">cancel</span>
                </button>
                <button class="btn-action-cartera btn-info-cartera" title="Ver factura" onclick="verFactura(${pedido.id}, '${pedido.numero_pedido || pedido.numero || 'N/A'}')" style="padding: 8px 10px; display: flex; align-items: center; justify-content: center;">
                    <span class="material-symbols-rounded" style="font-size: 1.3rem;">receipt</span>
                </button>
            `;
        } else {
            // Vistas de estados (aprobados, rechazados, anulados): solo botón de factura
            botonesHTML = `
                <button class="btn-action-cartera btn-info-cartera" 
                        title="Ver factura" 
                        onclick="verFactura(${pedido.id}, '${pedido.numero_pedido || pedido.numero || 'N/A'}')"
                        style="padding: 8px 10px; display: flex; align-items: center; justify-content: center;">
                    <span class="material-symbols-rounded" style="font-size: 1.3rem;">receipt</span>
                </button>
            `;
        }
        
        row.innerHTML = `
            <!-- Acciones -->
            <div class="table-cell-cartera" style="flex: 0 0 180px; display: flex; gap: 8px; align-items: center; justify-content: center; padding: 0 12px; border-right: 6px solid #e5e7eb; box-sizing: border-box; margin-right: 8px;">
                ${botonesHTML}
            </div>
            
            <!-- Cliente -->
            <div class="table-cell-cartera" style="flex: 0 0 310px; display: flex; align-items: center; padding: 0 14px 0 32px; box-sizing: border-box;">
                <span style="font-size: 0.95rem;">${pedido.cliente_nombre || 'N/A'}</span>
            </div>
            
            <!-- Fecha -->
            <div class="table-cell-cartera" style="flex: 0 0 150px; display: flex; align-items: center; padding: 0 10px; box-sizing: border-box;">
                <span style="font-size: 0.95rem;">${fechaFormato}</span>
            </div>
        `;
        tablaPedidosBody.appendChild(row);
    });
    
}

// ===== MODAL APROBACIÓN =====
function verFactura(pedidoId, numeroPedido) {
    // Abre la factura del pedido usando la ruta de cartera
    
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
        
        // Usar la función profesional de factura que tiene la asesora
        if (typeof crearModalPreviewFactura === 'function') {
            crearModalPreviewFactura(datos);
        } else {
            mostrarNotificacion('Error: Sistema de factura no disponible', 'danger');
        }
    })
    .catch(error => {
        ocultarCargando();
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
        
        
        // Mostrar notificación temporal de éxito
        const notifSuccess = document.createElement('div');
        notifSuccess.className = 'alert alert-success';
        notifSuccess.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999; animation: slideInRight 0.3s ease;';
        notifSuccess.innerHTML = `
            <span class="material-symbols-rounded" style="flex-shrink: 0;">check_circle</span>
            <span>Aprobado exitosamente</span>
        `;
        document.body.appendChild(notifSuccess);
        
        // Remover después de 3 segundos
        setTimeout(() => notifSuccess.remove(), 3000);
        
        cerrarModalAprobacion();
        
        // Recargar tabla inmediatamente
        cargarPedidos();
        
    } catch (error) {
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
        
        
        // Mostrar notificación temporal de éxito
        const notifSuccess = document.createElement('div');
        notifSuccess.className = 'alert alert-success';
        notifSuccess.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999; animation: slideInRight 0.3s ease;';
        notifSuccess.innerHTML = `
            <span class="material-symbols-rounded" style="flex-shrink: 0;">check_circle</span>
            <span>Rechazado exitosamente</span>
        `;
        document.body.appendChild(notifSuccess);
        
        // Remover después de 3 segundos
        setTimeout(() => notifSuccess.remove(), 3000);
        
        cerrarModalRechazo();
        
        // Recargar tabla inmediatamente
        cargarPedidos();
        
    } catch (error) {
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
