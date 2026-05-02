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
let carteraKnownPendingIds = new Set();
let carteraWsNotificationsBootstrapped = false;
let carteraWsDedupe = new Map();
let carteraKnownPendingBootstrapped = false;
let carteraPollingTimer = null;

// ===== PERMISOS DE USUARIO =====
// Detectar si el usuario tiene permisos de acción (no es supervisor_gerencia)
const tienePermisosAccion = window.userRole !== 'supervisor_gerencia';

// ===== FUNCIÓN PARA AGREGAR COLUMNA NÚMERO PEDIDO AL HEADER =====
function agregarColumnaNumeroPedidoHeader() {
    const tableHead = document.getElementById('tableHead');
    if (!tableHead) return;
    
    // Verificar si ya existe la columna
    const existingColumn = tableHead.querySelector('[data-column="numero_pedido"]');
    if (existingColumn) return;
    
    // Buscar la columna de cliente
    const clienteColumn = tableHead.querySelector('[data-sort="cliente"]');
    if (!clienteColumn) return;
    
    // Crear la nueva columna de número de pedido
    const numeroPedidoColumn = document.createElement('div');
    numeroPedidoColumn.className = 'table-header-cell sortable';
    numeroPedidoColumn.setAttribute('data-column', 'numero_pedido');
    numeroPedidoColumn.setAttribute('data-sort', 'numero_pedido');
    numeroPedidoColumn.style.cssText = 'flex: 0 0 120px; justify-content: center;';
    
    numeroPedidoColumn.innerHTML = `
        <div class="th-wrapper" style="display: flex; align-items: center; justify-content: space-between; gap: 0.5rem; width: 100%;">
            <span class="header-text">N° Pedido</span>
            <button type="button" class="btn-filter-column" title="Filtrar N° Pedido" onclick="abrirModalFiltro('numero_pedido', event)">
                <span class="material-symbols-rounded">filter_alt</span>
                <div class="filter-badge"></div>
            </button>
        </div>
    `;
    
    // Insertar antes de la columna de cliente
    clienteColumn.parentNode.insertBefore(numeroPedidoColumn, clienteColumn);
    
    // Agregar event listener para ordenamiento
    numeroPedidoColumn.addEventListener('click', function(e) {
        if (e.target.closest('.btn-filter-column')) return;
        
        if (currentSort === 'numero_pedido') {
            currentSortOrder = currentSortOrder === 'asc' ? 'desc' : 'asc';
        } else {
            currentSort = 'numero_pedido';
            currentSortOrder = 'asc';
        }
        
        updateSortIndicators();
        currentPage = 1;
        cargarPedidos();
    });
}

// ===== HELPER: Obtener elemento =====
function el(selector) {
    return document.querySelector(selector);
}

function elById(id) {
    return document.getElementById(id);
}

function inicializarSincronizacionScrollTabla() {
    const scrollContainer = document.querySelector('.table-scroll-container');
    const headerTrack = document.querySelector('#tableHead .table-head-track');

    if (!scrollContainer || !headerTrack) return;

    const syncHeader = () => {
        headerTrack.style.transform = `translateX(${-scrollContainer.scrollLeft}px)`;
    };

    scrollContainer.addEventListener('scroll', syncHeader, { passive: true });
    syncHeader();
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
    console.log(' abrirModalFiltro en app.js:', tipo);
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
    console.log(' cerrarModalFiltro en app.js:', tipo);
    const modal = document.getElementById(`modalFiltro${tipo.charAt(0).toUpperCase() + tipo.slice(1)}`);
    if (modal) {
        modal.classList.remove('active');
        modal.style.display = 'none';
    }
}

function aplicarFiltroNumero() {
    console.log(' APLICAR FILTRO NÚMERO PEDIDO desde app.js');
    const input = document.getElementById('filtroNumeroInput');
    const valor = input ? input.value.trim() : '';
    
    if (valor) {
        // Agregar a los parámetros de filtro existentes
        const url = new URL(window.location);
        url.searchParams.set('numero_pedido', valor);
        url.searchParams.set('page', '1');
        
        // Actualizar la URL sin recargar la página
        window.history.pushState({}, '', url);
        
        // Actualizar variables de filtro
        currentSearch = valor; // Reutilizamos currentSearch para número de pedido
        currentPage = 1;
        cargarPedidos();
        cerrarModalFiltro('numero_pedido');
        mostrarNotificación(`Filtro de número aplicado: "${valor}"`, 'info');
    } else {
        mostrarNotificación('Por favor ingresa un número de pedido', 'warning');
    }
}

function aplicarFiltroCliente() {
    console.log(' APLICAR FILTRO CLIENTE desde app.js');
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
    console.log(' APLICAR FILTRO FECHA desde app.js');
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
    console.log(' APLICAR FILTRO NÚMERO desde app.js');
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
    inicializarSincronizacionScrollTabla();
    // Agregar columna de número de pedido al header
    agregarColumnaNumeroPedidoHeader();
    
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
    
    // Event listeners para ordenamiento en headers (estilo Contador)
    const headerCellsSortable = document.querySelectorAll('.table-header-cell.sortable');
    headerCellsSortable.forEach(cell => {
        cell.addEventListener('click', function(e) {
            if (e.target.closest('.btn-filter-column')) return;

            const sortType = this.getAttribute('data-sort');
            if (!sortType) return;

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
    
    // Inicializar notificaciones en tiempo real por WebSocket
    inicializarNotificacionesWebSocketCartera();
    // iniciarFallbackPollingCartera(); // Desactivado: WebSockets ya funcionan en tiempo real
    inicializarPushMovilCartera();
});

function updateSortIndicators() {
    const sortableCells = document.querySelectorAll('.table-header-cell.sortable');
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

        const idsActuales = carteraMainPedidosData
            .map(item => Number(item?.id))
            .filter(id => Number.isFinite(id));
        const idsPrevios = new Set(carteraKnownPendingIds);
        idsActuales.forEach(id => carteraKnownPendingIds.add(id));
        notificarNuevosPendientesPorDelta(idsActuales, idsPrevios);
        
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

function notificarNuevosPendientesPorDelta(idsActuales, idsPrevios) {
    const esVistaPendientes = window.location.pathname.includes('/cartera/pedidos');
    const enMonitoreoBase = !currentSearch && !filtroCliente && !filtroFechaDesde && !filtroFechaHasta && Number(currentPage) === 1;

    if (!carteraKnownPendingBootstrapped) {
        carteraKnownPendingBootstrapped = true;
        return;
    }

    if (!esVistaPendientes || !enMonitoreoBase) {
        return;
    }

    const idsNuevos = idsActuales.filter(id => !idsPrevios.has(id));
    if (idsNuevos.length === 0) {
        return;
    }

    idsNuevos.forEach((pedidoId) => {
        const dedupeKey = `polling|${pedidoId}|pendiente_cartera`;
        if (!debeNotificarRealtime(dedupeKey)) return;

        const pedido = carteraMainPedidosData.find(item => Number(item?.id) === Number(pedidoId));
        const numero = pedido?.numero_pedido || pedido?.numero || `#${pedidoId}`;
        const cliente = pedido?.cliente || pedido?.cliente_nombre || 'Cliente no disponible';
        const mensaje = `Nuevo pedido ${numero} de ${cliente}, pendiente por autorizar.`;

        mostrarNotificacion(mensaje, 'info');
        mostrarNotificacionNavegador('Nuevo pedido pendiente de cartera', mensaje);
    });
}

function iniciarFallbackPollingCartera() {
    // Polling desactivado - Se prefiere el uso de WebSockets (Echo)
    console.log('[CARTERA] Polling desactivado (Real-time activo)');
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

// ===== WEBSOCKET DE NUEVOS PENDIENTES =====
function inicializarNotificacionesWebSocketCartera() {
    if (carteraWsNotificationsBootstrapped) return;
    carteraWsNotificationsBootstrapped = true;

    // Asegurar inicialización de Echo
    if (typeof window.initEcho === 'function') {
        window.initEcho();
    }

    solicitarPermisoNotificacionSiAplica();

    if (typeof window.waitForEcho !== 'function') {
        console.warn('[CARTERA] waitForEcho no disponible para notificaciones realtime');
        return;
    }

    window.waitForEcho(() => {
        console.log('[CARTERA-DEBUG] Echo está listo, configurando suscripciones...');
        const ws = window.shared?.websocket;
        const onCreated = (event) => manejarEventoRealtimeCartera(event, 'pedido.creado');
        const onUpdated = (event) => manejarEventoRealtimeCartera(event, 'pedido.actualizado');
        const onOrdenUpdated = (event) => manejarEventoRealtimeCartera(event, 'orden.updated');

        try {
            if (ws && typeof ws.subscribe === 'function') {
                console.log('[CARTERA-DEBUG] Usando abstracción window.shared.websocket');
                ws.subscribe('pedidos.creados', '.pedido.creado', onCreated);
                ws.subscribe('pedidos.general', '.pedido.actualizado', onUpdated);
                ws.subscribe('ordenes', '.orden.updated', onOrdenUpdated);
                return;
            }

            if (window.EchoInstance) {
                console.log('[CARTERA-DEBUG] Usando window.EchoInstance directamente');
                window.EchoInstance.channel('pedidos.creados').listen('.pedido.creado', onCreated);
                window.EchoInstance.channel('pedidos.general').listen('.pedido.actualizado', onUpdated);
                window.EchoInstance.channel('ordenes').listen('.orden.updated', onOrdenUpdated);
                return;
            }

            console.warn('[CARTERA-DEBUG] No hay cliente websocket disponible (ws o EchoInstance)');
        } catch (error) {
            console.warn('[CARTERA-DEBUG] Error suscribiendo websocket cartera:', error);
        }
    });
}

function manejarEventoRealtimeCartera(event, tipoEvento) {
    console.log(`[CARTERA-DEBUG] Evento recibido: ${tipoEvento}`, event);
    const pedido = extraerPedidoDesdeEventoRealtime(event);
    if (!pedido || !pedido.id) {
        console.warn('[CARTERA-DEBUG] No se pudo extraer el pedido del evento');
        return;
    }

    const estado = String(pedido.estado || '').toLowerCase();
    console.log(`[CARTERA-DEBUG] Estado del pedido: ${estado}`);
    if (estado !== 'pendiente_cartera') {
        console.log('[CARTERA-DEBUG] El pedido no está en estado pendiente_cartera, ignorando');
        return;
    }

    const pedidoId = Number(pedido.id);
    if (!Number.isFinite(pedidoId)) return;

    const numero = pedido.numero_pedido || pedido.numero || `#${pedidoId}`;
    const cliente = pedido.cliente || pedido.cliente_nombre || 'Cliente no disponible';
    const dedupeKey = `${tipoEvento}|${pedidoId}|${estado}`;
    
    if (!debeNotificarRealtime(dedupeKey)) {
        console.log('[CARTERA-DEBUG] Evento duplicado, ignorando notificación');
        return;
    }

    const esNuevoPendiente = !carteraKnownPendingIds.has(pedidoId);
    carteraKnownPendingIds.add(pedidoId);

    console.log(`[CARTERA-DEBUG] Es nuevo pendiente: ${esNuevoPendiente}`);

    if (!esNuevoPendiente && tipoEvento !== 'pedido.creado') {
        return;
    }

    const mensaje = `Nuevo pedido ${numero} de ${cliente}, pendiente por autorizar.`;
    console.log(`[CARTERA-DEBUG] Mostrando notificación: ${mensaje}`);
    mostrarNotificacion(mensaje, 'info');
    mostrarNotificacionNavegador('Nuevo pedido pendiente de cartera', mensaje);

    if (typeof cargarPedidos === 'function') {
        console.log('[CARTERA-DEBUG] Recargando tabla de pedidos...');
        cargarPedidos();
    }
}

function extraerPedidoDesdeEventoRealtime(event) {
    if (!event || typeof event !== 'object') return null;
    return event.pedido || event.orden || null;
}

function debeNotificarRealtime(key) {
    const now = Date.now();
    const ttlMs = 10000;

    for (const [k, ts] of carteraWsDedupe.entries()) {
        if (now - ts > ttlMs) {
            carteraWsDedupe.delete(k);
        }
    }

    if (carteraWsDedupe.has(key)) {
        return false;
    }

    carteraWsDedupe.set(key, now);
    return true;
}

async function solicitarPermisoNotificacionSiAplica() {
    if (!('Notification' in window)) return;
    if (Notification.permission === 'granted' || Notification.permission === 'denied') return;

    try {
        await Notification.requestPermission();
    } catch (error) {
        console.warn('[CARTERA] No se pudo solicitar permiso de notificaciones:', error);
    }
}

function mostrarNotificacionNavegador(titulo, mensaje) {
    if (!('Notification' in window)) return;
    if (Notification.permission !== 'granted') return;

    try {
        const notification = new Notification(titulo, {
            body: mensaje,
            icon: '/mundo_icon.png',
        });

        notification.onclick = function () {
            window.focus();
            this.close();
        };
    } catch (error) {
        console.warn('[CARTERA] Error mostrando notificación de navegador:', error);
    }
}

// ===== WEB PUSH (SERVICE WORKER) =====
async function inicializarPushMovilCartera() {
    const vapidPublicKey = document.querySelector('meta[name="vapid-public-key"]')?.getAttribute('content') || '';
    if (!vapidPublicKey) {
        console.warn('[CARTERA] VAPID_PUBLIC_KEY vacío: push móvil deshabilitado en este entorno.');
        return;
    }
    if (!('serviceWorker' in navigator) || !('PushManager' in window)) return;

    try {
        const permission = await Notification.requestPermission();
        if (permission !== 'granted') return;

        const registration = await navigator.serviceWorker.register('/sw-push.js');
        let subscription = await registration.pushManager.getSubscription();

        if (!subscription) {
            subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(vapidPublicKey),
            });
        }

        await fetch('/push-subscriptions', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body: JSON.stringify(subscription.toJSON()),
        });
    } catch (error) {
        console.warn('[CARTERA] No fue posible registrar push móvil:', error);
    }
}

function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);
    for (let i = 0; i < rawData.length; i += 1) {
        outputArray[i] = rawData.charCodeAt(i);
    }
    return outputArray;
}

function formatearFechaHora12h(fechaRaw) {
    if (!fechaRaw) return 'N/A';
    const fecha = new Date(fechaRaw);
    if (Number.isNaN(fecha.getTime())) return 'N/A';

    return fecha.toLocaleString('es-CO', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
        hour12: true
    });
}

// ===== RENDERIZAR TABLA =====
function renderizarTabla(pedidos) {
    const tablaPedidosBody = elById('tablaPedidosBody');
    if (!tablaPedidosBody) return;
    
    
    tablaPedidosBody.innerHTML = '';
    
    pedidos.forEach(pedido => {
        const asesorNombre = pedido.asesor_nombre || pedido.asesor || 'Sin asesor';
        const fechaFormato = formatearFechaHora12h(pedido.created_at);

        const row = document.createElement('div');
        row.className = 'table-row';
        row.setAttribute('data-orden-id', pedido.id);
        row.setAttribute('data-numero', pedido.numero_pedido || pedido.numero || '');
        row.setAttribute('data-cliente', pedido.cliente_nombre || pedido.cliente || '');
        row.setAttribute('data-asesor', asesorNombre);
        row.setAttribute('data-fecha', fechaFormato);
        
        // Detectar la página actual para mostrar los botones correctos
        const currentPath = window.location.pathname;
        let botonesHTML = '';
        
        if (currentPath.includes('/cartera/pedidos')) {
            // Vista de pendientes: mostrar botones según permisos
            if (tienePermisosAccion) {
                // Usuario con permisos: 3 botones (aprobar, rechazar, factura)
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
                // Usuario sin permisos (supervisor_gerencia): solo botón de factura (modo lectura)
                botonesHTML = `
                    <button class="btn-action-cartera btn-info-cartera" title="Ver factura" onclick="verFactura(${pedido.id}, '${pedido.numero_pedido || pedido.numero || 'N/A'}')" style="padding: 8px 10px; display: flex; align-items: center; justify-content: center;">
                        <span class="material-symbols-rounded" style="font-size: 1.3rem;">receipt</span>
                    </button>
                    <div style="padding: 8px 10px; display: flex; align-items: center; justify-content: center; color: #6b7280; font-size: 0.8rem;">
                        <span class="material-symbols-rounded" style="font-size: 1.3rem;">visibility</span>
                    </div>
                `;
            }
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
        
        let accionesHTML = '';
        if (tienePermisosAccion) {
            accionesHTML = `
                <!-- Acciones -->
                <div class="table-cell acciones-column" style="flex: 0 0 180px; justify-content: center; position: relative; display: flex; gap: 0.5rem;">
                    ${botonesHTML}
                </div>
            `;
        }
        
        row.innerHTML = `
            ${accionesHTML}

            <!-- Número de Pedido -->
            <div class="table-cell" style="flex: 0 0 120px; justify-content: center;">
                <span class="pedido-numero">${pedido.numero_pedido || pedido.numero || 'N/A'}</span>
            </div>

            <!-- Cliente -->
            <div class="table-cell" style="flex: 0 0 310px;">
                <span>${pedido.cliente_nombre || pedido.cliente || 'N/A'}</span>
            </div>

            <!-- Asesor -->
            <div class="table-cell" style="flex: 0 0 220px;">
                <span>${asesorNombre}</span>
            </div>

            <!-- Fecha -->
            <div class="table-cell" style="flex: 0 0 220px; justify-content: center;">
                <span>${fechaFormato}</span>
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
