/**
 * =====================================================
 * SUPERVISOR PEDIDOS - REALTIME LISTENER
 * =====================================================
 * Se suscribe a canales WebSocket (Laravel Echo / Pusher)
 * y actualiza la tabla de pedidos en tiempo real.
 *
 * Dependencias (Fase 3-4 DDD Híbrido):
 *   - window.shared.websocket  (EchoReverbWebSocketClient)
 *   - window.supervisorPedidos.repository (PedidoApiRepository)
 *
 * Nota: WebSocket se inicializa lazy, así que usamos window.waitForEcho()
 * que es proporcionado por resources/js/bootstrap.js
 */

// VALIDACIONES ESTRICTAS (sin fallbacks)

if (!window.supervisorPedidos?.isReady) {
    throw new Error('[realtime-supervisor] window.supervisorPedidos no está disponible. Carga DDD bootstrap ANTES.');
}

if (!window.shared?.notify) {
    throw new Error('[realtime-supervisor] window.shared.notify no disponible. Carga shared/bootstrap.js ANTES.');
}

const _rtRepo = window.supervisorPedidos.repository;
const _rtNotify = window.shared.notify;
const SUPERVISOR_GRID_TEMPLATE = '60px 220px 120px 200px 150px 140px 150px 150px 150px';
const SUPERVISOR_GRID_GAP = '1.2rem';

function _formatFechaPedido(fechaRaw) {
    if (!fechaRaw) return new Date().toLocaleString('es-CO', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        hour12: false
    }).replace(',', '');

    const fecha = new Date(fechaRaw);
    if (Number.isNaN(fecha.getTime())) return String(fechaRaw);
    return fecha.toLocaleString('es-CO', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        hour12: false
    }).replace(',', '');
}

function actualizarFilaEnTabla(fila, orden) {
    const celdas = fila.querySelectorAll('[data-field]');
    celdas.forEach(celda => {
        const field = celda.getAttribute('data-field');
        if (orden[field]) {
            const newValue = orden[field];
            if (celda.textContent !== newValue) {
                celda.textContent = newValue;
                celda.style.backgroundColor = '#fff9e6';
                setTimeout(() => { celda.style.backgroundColor = ''; }, 1500);
            }
        }
    });
    fila.style.backgroundColor = '#f0f9ff';
    setTimeout(() => { fila.style.backgroundColor = 'white'; }, 2000);
}

function agregarNuevaFilaATabla(orden) {
    supervisorPedidosInsertarFilaNuevaAlInicio(orden);
}

function supervisorPedidosMostrarNotificacionNuevoPedido(orden) {
    try {
        const numero  = orden?.numero_pedido || orden?.numero || '';
        const cliente = orden?.cliente ? ` - ${orden.cliente}` : '';
        const mensaje = `Nuevo pedido${numero ? ' #' + numero : ''}${cliente}`;

        try {
            const badge = document.getElementById('notificationBadge');
            if (badge) {
                const count = (parseInt(badge.textContent) || 0) + 1;
                badge.textContent = String(count);
                badge.style.display = count > 0 ? 'block' : 'none';
            }
            if (window.__supervisorPedidosNotifSyncT) clearTimeout(window.__supervisorPedidosNotifSyncT);
            window.__supervisorPedidosNotifSyncT = setTimeout(() => {
                try {
                    if (typeof window.supervisorPedidosRefreshNotificaciones === 'function') {
                        window.supervisorPedidosRefreshNotificaciones();
                    } else if (typeof cargarNotificacionesPendientes === 'function') {
                        cargarNotificacionesPendientes();
                    } else {
                        window.dispatchEvent(new CustomEvent('supervisorPedidos:notificacionesRefresh'));
                    }
                } catch (e) { /* noop */ }
            }, 1200);
        } catch (e) { /* noop */ }

        _rtNotify.success(mensaje);
    } catch (e) { /* silencioso */ }
}

function supervisorPedidosMaybeNotifyFromActualizado(payload) {
    try {
        const pedido   = payload?.pedido || payload?.orden || payload || {};
        const nuevo    = payload?.nuevo_estado?.new || payload?.nuevo_estado || pedido?.estado || '';
        const anterior = payload?.anterior_estado || payload?.nuevo_estado?.old || '';

        if (String(nuevo).toUpperCase() !== 'PENDIENTE_SUPERVISOR') return;
        if (String(anterior).toUpperCase() === 'PENDIENTE_SUPERVISOR') return;

        if (!window.__supervisorPedidosNotifiedIds) window.__supervisorPedidosNotifiedIds = new Set();
        const key = String(pedido?.id || payload?.pedido_id || payload?.id || '');
        if (!key || window.__supervisorPedidosNotifiedIds.has(key)) return;
        window.__supervisorPedidosNotifiedIds.add(key);
        supervisorPedidosMostrarNotificacionNuevoPedido(pedido);
    } catch (e) { /* noop */ }
}

function supervisorPedidosInsertarFilaNuevaAlInicio(orden) {
    const tableContainer = document.querySelector('.table-scroll-container');
    if (!tableContainer) return;

    const header       = tableContainer.firstElementChild;
    const numeroPedido = orden?.numero_pedido || orden?.numero || 'N/A';
    const estado       = orden?.estado || 'PENDIENTE_SUPERVISOR';

    const estadoColors = {
        'PENDIENTE_SUPERVISOR': { bg: '#fff3cd', text: '#856404', label: 'Pendiente Supervisor' },
        'PENDIENTE_INSUMOS':    { bg: '#d1ecf1', text: '#0c5460', label: 'Pendiente Insumos' },
        'En Ejecución':         { bg: '#d4edda', text: '#155724', label: 'En Ejecución' },
        'No iniciado':          { bg: '#e2e3e5', text: '#383d41', label: 'No Iniciado' },
        'Entregado':            { bg: '#d4edda', text: '#155724', label: 'Entregado' },
        'Finalizada':           { bg: '#d4edda', text: '#155724', label: 'Finalizada' },
        'Anulada':              { bg: '#f8d7da', text: '#721c24', label: 'Anulada' },
        'DEVUELTO_A_ASESORA':   { bg: '#f8d7da', text: '#721c24', label: 'Devuelto' },
    };
    const estadoInfo = estadoColors[estado] || { bg: '#e2e3e5', text: '#383d41', label: estado };
    const safeNumero = String(numeroPedido).replace('#', '');

    const fila = document.createElement('div');
    fila.setAttribute('data-pedido-id', String(orden?.id || ''));
    fila.style.cssText = `
        display: grid;
        grid-template-columns: ${SUPERVISOR_GRID_TEMPLATE};
        gap: ${SUPERVISOR_GRID_GAP}; padding: 1rem; border-bottom: 1px solid #e5e7eb;
        align-items: center; min-width: max-content;
        background: #f0f9ff; animation: slideInDown 0.5s ease; transition: background 0.2s ease;
    `;
    fila.setAttribute('data-seleccionado', 'false');
    fila.setAttribute('data-pedido-row', 'true');
    fila.onmouseover = function() {
        if (!this.dataset.seleccionado || this.dataset.seleccionado === 'false') {
            this.style.background = '#f9fafb';
        }
    };
    fila.onmouseout  = function() {
        this.style.background = this.dataset.seleccionado === 'true' ? '#d1d5db' : 'white';
    };

    const fechaCreacion = _formatFechaPedido(orden?.created_at || orden?.fecha_creacion || orden?.fecha);
    fila.innerHTML = `
        <div style="display: flex; align-items: center; justify-content: center;">
            <input type="checkbox" class="pedido-checkbox" data-pedido-id="${orden?.id || ''}" title="Seleccionar pedido" style="width: 18px; height: 18px; cursor: pointer;">
        </div>

        <div style="display: flex; gap: 0.5rem; align-items: center; justify-content: center;">
            <button class="btn-accion btn-accion--ver btn-ver-dropdown"
                data-menu-id="menu-ver-${safeNumero}"
                data-pedido="${safeNumero}"
                data-pedido-id="${orden?.id || ''}"
                title="Ver Opciones">
                <i class="fas fa-eye"></i>
            </button>

            <button class="btn-accion btn-accion--ocultar"
                onclick="if (typeof abrirModalOcultar === 'function') abrirModalOcultar(${orden?.id || 'null'}, '${safeNumero}')"
                title="Ocultar Pedido">
                <i class="fas fa-eye-slash"></i>
            </button>
        </div>

        <div>
            <span style="font-size: 0.85rem; color: #6b7280;">${fechaCreacion}</span>
        </div>

        <div><span style="font-weight: 600; color: #1e5ba8;">#${numeroPedido}</span></div>
        <div><span>${orden?.cliente || ''}</span></div>
        <div>
            <span style="background: ${estadoInfo.bg}; color: ${estadoInfo.text};
                padding: 4px 10px; border-radius: 12px; font-size: 0.75rem;
                font-weight: bold; white-space: nowrap; display: inline-block;">
                ${estadoInfo.label}
            </span>
        </div>
        <div>
            <span style="background: #f3f4f6; color: #9ca3af;
                padding: 4px 10px; border-radius: 12px; font-size: 0.75rem;
                font-weight: bold; white-space: nowrap;">
                Sin novedades
            </span>
        </div>
        <div><span>${orden?.asesora || orden?.asesor || 'N/A'}</span></div>
        <div><span>${orden?.forma_pago || orden?.forma_de_pago || 'N/A'}</span></div>
    `;

    if (header && header.parentNode === tableContainer) {
        header.insertAdjacentElement('afterend', fila);
    } else {
        tableContainer.prepend(fila);
    }
    setTimeout(() => { fila.style.backgroundColor = 'white'; }, 2000);
}

// Agregar estilos de animación si no existen
if (!document.querySelector('style[data-realtime]')) {
    const style = document.createElement('style');
    style.setAttribute('data-realtime', 'true');
    style.textContent = `
        @keyframes slideInDown {
            from { opacity: 0; transform: translateY(-20px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(100px); }
            to   { opacity: 1; transform: translateX(0); }
        }
        @keyframes slideOutRight {
            from { opacity: 1; transform: translateX(0); }
            to   { opacity: 0; transform: translateX(100px); }
        }
    `;
    document.head.appendChild(style);
}

/**
 * Inicializa todas las suscripciones a canales WebSocket
 */
function initializeRealtimeListener() {
    // Esperar a que Echo esté listo (resources/js/bootstrap.js lo proporciona)
    if (typeof window.waitForEcho !== 'function') {
        setTimeout(initializeRealtimeListener, 100);
        return;
    }

    window.waitForEcho(() => {
        try {
            // Obtener instancia de WebSocket (lazy initialized)
            const ws = window.shared.websocket;
            
            if (!ws) {
                throw new Error('WebSocketClient no disponible');
            }

            // Note: No validamos isConnected() aquí porque Echo/Reverb maneja automáticamente
            // la reconexión. Las suscripciones se establecerán cuando la conexión esté lista.
            // Intentar en cada carga es suficiente para el cliente inicializado.

            _setupCustomEventHandlers();
            _subscribeToChannels(ws);
        } catch (error) {
        }
    });
}

/**
 * Configura listeners para eventos custom dispuestos por otras partes de la app
 */
function _setupCustomEventHandlers() {
    if (window.__supervisorPedidosRealtimeCustomBound) {
        return;  // Ya configurado
    }
    window.__supervisorPedidosRealtimeCustomBound = true;

    window.addEventListener('supervisorPedidos:realtimePedidoCreado', (e) => {
        const pedido = e?.detail?.pedido || e?.detail?.raw?.pedido || {};
        supervisorPedidosInsertarFilaNuevaAlInicio(pedido);
        supervisorPedidosMostrarNotificacionNuevoPedido(pedido);
    });

    window.addEventListener('supervisorPedidos:realtimePedidoActualizado', (e) => {
        _refreshTablaConDelay(
            e?.detail?.raw || e?.detail?.pedido || {},
            'custom:.pedido.actualizado'
        );
    });
}

/**
 * Suscribe a todos los canales WebSocket necesarios
 */
function _subscribeToChannels(ws) {
    // Canal: despacho.pedidos - Actualizaciones de pedidos
    try {
        ws.subscribe('despacho.pedidos', '.pedido.actualizado', (data) => {
            _refreshTablaConDelay(data, 'despacho.pedidos:.pedido.actualizado');
        });
    } catch (error) {
    }

    // Canal: supervisor-pedidos - Cambios de estado
    try {
        ws.subscribe('supervisor-pedidos', 'OrdenUpdated', (data) => {
            _refreshTablaConDelay(data, 'supervisor-pedidos:OrdenUpdated');
        });
    } catch (error) {
    }

    // Canal: pedidos.creados - Nuevos pedidos
    try {
        ws.subscribe('pedidos.creados', '.pedido.creado', (data) => {
            const pedido = data?.pedido || data?.orden || data || {};
            supervisorPedidosInsertarFilaNuevaAlInicio(pedido);
            supervisorPedidosMostrarNotificacionNuevoPedido(pedido);
        });
    } catch (error) {
    }
}

/**
 * Refrescador de tabla con debounce para evitar múltiples requests
 */
function _refreshTablaConDelay(payload, eventName) {
    // Detectar si es actualización de estado para notificar
    if (String(eventName).includes('despacho.pedidos:.pedido.actualizado')) {
        supervisorPedidosMaybeNotifyFromActualizado(payload);
    }

    // Debouncer: evitar múltiples requests al servidor
    if (window.__realtimeSupervisorRefreshTimeout) {
        clearTimeout(window.__realtimeSupervisorRefreshTimeout);
    }

    window.__realtimeSupervisorRefreshTimeout = setTimeout(async () => {
        try {
            const html = await _rtRepo.fetchPageContent(window.location.href);
            const doc = new DOMParser().parseFromString(html, 'text/html');
            const nuevaTabla = doc.querySelector('.table-scroll-container');
            const tablaActual = document.querySelector('.table-scroll-container');

            if (!nuevaTabla || !tablaActual) {
                return;
            }

            tablaActual.innerHTML = nuevaTabla.innerHTML;
        } catch (error) {
        }
    }, 450);  // 450ms debounce
}

// ===== INICIAR CUANDO EL DOM ESTÉ LISTO =====

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeRealtimeListener);
} else {
    // Si el script se carga después de DOMContentLoaded, iniciar inmediatamente
    initializeRealtimeListener();
}
