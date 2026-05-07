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
const SUPERVISOR_GRID_TEMPLATE = '60px 220px 130px 140px 120px 220px 150px 150px 150px 150px 150px 150px';
const SUPERVISOR_GRID_GAP = '1.2rem';
const _RT_MODAL_IDS = [
    'modal-overlay',
    'order-detail-modal-wrapper',
    'order-detail-modal-wrapper-logo',
    'modalEditarPedido',
    'modal-agregar-prenda-nueva',
    'orderTrackingModal',
    'spBodegaNovedadesModal',
    'modalNovedades',
    'modalFiltro',
    'novedadesEditModal',
    'modalConfirmarEliminar',
];
let _rtObserverStarted = false;
let _rtQueuedRefresh = false;
let _rtRefreshInFlight = false;

function _rtInstallSwalNuevoPedidoGuard() {
    if (window.__spSwalNuevoPedidoGuardInstalled) return;
    window.__spSwalNuevoPedidoGuardInstalled = true;

    if (!window.Swal || typeof window.Swal.fire !== 'function') return;

    const originalFire = window.Swal.fire.bind(window.Swal);

    window.Swal.fire = function patchedSwalFire(...args) {
        try {
            const config = args[0];
            const title = typeof config === 'string'
                ? config
                : String(config?.title || '');
            const isToast = typeof config === 'object' && config?.toast === true;

            // Bloquear solamente el toast legado "Nuevo pedido ..." en supervisor-pedidos.
            if (isToast && /^nuevo pedido\b/i.test(title.trim())) {
                return Promise.resolve({ isDismissed: true, dismiss: 'blocked_legacy_nuevo_pedido_toast' });
            }
        } catch (e) { /* noop */ }

        return originalFire(...args);
    };
}

function _rtShowCompactCornerToast(message, duration = 3200) {
    try {
        const containerId = 'sp-compact-toast-container';
        let container = document.getElementById(containerId);

        if (!container) {
            container = document.createElement('div');
            container.id = containerId;
            container.style.cssText = [
                'position:fixed',
                'top:20px',
                'right:20px',
                'z-index:2147483646',
                'display:flex',
                'flex-direction:column',
                'gap:10px',
                'pointer-events:none',
                'max-width:420px',
            ].join(';');
            document.body.appendChild(container);
        }

        const toast = document.createElement('div');
        toast.style.cssText = [
            'background:linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%)',
            'color:#ffffff',
            'font-size:14px',
            'font-weight:600',
            'line-height:1.35',
            'padding:12px 14px',
            'border-radius:12px',
            'border:1px solid rgba(255,255,255,0.18)',
            'box-shadow:0 12px 26px rgba(30,58,138,0.35)',
            'display:flex',
            'align-items:center',
            'gap:10px',
            'transform:translateX(115%)',
            'transition:transform 0.25s ease',
            'pointer-events:auto',
            'max-width:420px',
        ].join(';');

        toast.innerHTML = `
            <span style="
                width:26px;
                height:26px;
                border-radius:999px;
                display:inline-flex;
                align-items:center;
                justify-content:center;
                background:rgba(255,255,255,0.2);
                font-size:14px;
                flex:0 0 auto;
            ">✓</span>
            <span style="
                display:block;
                font-size:14px;
                font-weight:700;
                letter-spacing:0.1px;
                word-break:break-word;
            ">${String(message)}</span>
        `;
        container.appendChild(toast);

        requestAnimationFrame(() => {
            toast.style.transform = 'translateX(0)';
        });

        window.setTimeout(() => {
            toast.style.transform = 'translateX(115%)';
            window.setTimeout(() => {
                toast.remove();
                if (container && container.children.length === 0) {
                    container.remove();
                }
            }, 220);
        }, duration);
    } catch (e) { /* noop */ }
}

function _rtIsElementVisible(el) {
    if (!el) return false;
    const style = window.getComputedStyle(el);
    if (style.display === 'none' || style.visibility === 'hidden') return false;
    if (Number(style.opacity) === 0) return false;
    return true;
}

function _rtHasBlockingModalOpen() {
    if (document.querySelector('.modal.show')) return true;

    for (const id of _RT_MODAL_IDS) {
        const el = document.getElementById(id);
        if (_rtIsElementVisible(el)) return true;
    }

    const statsModals = document.querySelectorAll('.stats-modal[aria-hidden="false"]');
    return statsModals.length > 0;
}

function _rtTryFlushQueuedRefresh() {
    if (!_rtQueuedRefresh || _rtRefreshInFlight) return;
    if (_rtHasBlockingModalOpen()) return;

    _rtQueuedRefresh = false;
    _refreshTablaConDelay({}, 'queued:after-modal-close');
}

function _rtStartUiStabilityObserver() {
    if (_rtObserverStarted) return;
    _rtObserverStarted = true;

    document.addEventListener('hidden.bs.modal', _rtTryFlushQueuedRefresh, true);
    document.addEventListener('click', () => {
        if (!_rtQueuedRefresh) return;
        window.setTimeout(_rtTryFlushQueuedRefresh, 80);
    }, true);

    const observer = new MutationObserver(() => {
        if (!_rtQueuedRefresh) return;
        window.setTimeout(_rtTryFlushQueuedRefresh, 40);
    });

    observer.observe(document.body, {
        subtree: true,
        childList: true,
        attributes: true,
        attributeFilter: ['style', 'class', 'aria-hidden'],
    });
}

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

function _rtHasAllPrendasEntregadas(orden) {
    const pendientesCount = Number(orden?.prendas_pendientes_entrega_count);
    if (Number.isFinite(pendientesCount)) {
        return pendientesCount <= 0;
    }

    const estado = String(orden?.estado || '').trim().toUpperCase();
    return estado === 'ENTREGADO' || estado === 'FINALIZADA' || estado === 'FINALIZADO';
}

function _rtGetRowBaseBackground(isSelected, isDelivered) {
    if (isSelected) {
        return isDelivered ? '#86efac' : '#d1d5db';
    }
    return isDelivered ? '#dcfce7' : 'white';
}

function _rtGetRowHoverBackground(isDelivered) {
    return isDelivered ? '#bbf7d0' : '#f9fafb';
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
        const mensaje = `Aprobado por cartera${numero ? ' #' + numero : ''}${cliente}`;

        try {
            if (window.__supervisorPedidosNotifSyncT) clearTimeout(window.__supervisorPedidosNotifSyncT);
            window.__supervisorPedidosNotifSyncT = setTimeout(() => {
                try {
                    window.dispatchEvent(new CustomEvent('supervisorPedidos:notificacionesRefresh', {
                        detail: { pedido: orden || {} }
                    }));
                } catch (e) { /* noop */ }
            }, 250);
        } catch (e) { /* noop */ }

        _rtShowCompactCornerToast(mensaje, 3400);
    } catch (e) { /* silencioso */ }
}

function supervisorPedidosMaybeNotifyFromActualizado(payload) {
    try {
        const pedido = payload?.pedido || payload?.orden || payload || {};
        const action = String(payload?.action || '').toLowerCase();
        const changedFields = Array.isArray(payload?.changedFields) ? payload.changedFields.map(String) : [];
        const nuevo = payload?.nuevo_estado?.new || payload?.nuevo_estado || pedido?.estado || '';
        const anterior = payload?.anterior_estado || payload?.nuevo_estado?.old || '';

        // Solo aprobación de cartera:
        // - Evento OrdenUpdated desde cartera usa action=created y changedFields incluye estado.
        // - Estado final visible para supervisor debe ser PENDIENTE_SUPERVISOR.
        const isCarteraApprovalBySignature =
            action === 'created' &&
            changedFields.includes('estado') &&
            String(nuevo).toUpperCase() === 'PENDIENTE_SUPERVISOR';

        // Compatibilidad adicional: cambios de estado hacia PENDIENTE_SUPERVISOR
        // cuando vienen sin firma completa.
        const isStatusTransitionToSupervisor =
            String(nuevo).toUpperCase() === 'PENDIENTE_SUPERVISOR' &&
            String(anterior).toUpperCase() !== 'PENDIENTE_SUPERVISOR' &&
            (action === 'updated' || changedFields.includes('estado'));

        if (!isCarteraApprovalBySignature && !isStatusTransitionToSupervisor) return;

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
    const canBulkDeliver = !_rtHasAllPrendasEntregadas(orden);
    const isDelivered = !canBulkDeliver;

    const fila = document.createElement('div');
    fila.setAttribute('data-pedido-id', String(orden?.id || ''));
    fila.style.cssText = `
        display: grid;
        grid-template-columns: ${SUPERVISOR_GRID_TEMPLATE};
        gap: ${SUPERVISOR_GRID_GAP}; padding: 1rem; border-bottom: 1px solid #e5e7eb;
        align-items: center; min-width: max-content;
        background: ${_rtGetRowBaseBackground(false, isDelivered)}; animation: slideInDown 0.5s ease; transition: background 0.2s ease;
    `;
    fila.setAttribute('data-seleccionado', 'false');
    fila.setAttribute('data-entregado', isDelivered ? 'true' : 'false');
    fila.setAttribute('data-pedido-row', 'true');
    fila.onmouseover = function() {
        if (!this.dataset.seleccionado || this.dataset.seleccionado === 'false') {
            this.style.background = _rtGetRowHoverBackground(isDelivered);
        }
    };
    fila.onmouseout  = function() {
        this.style.background = this.dataset.seleccionado === 'true'
            ? _rtGetRowBaseBackground(true, isDelivered)
            : _rtGetRowBaseBackground(false, isDelivered);
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

            <button class="btn-accion ${canBulkDeliver ? '' : 'btn-accion--disabled'}"
                onclick="${canBulkDeliver ? `if (typeof marcarTodasPrendasEntregadasPedido === 'function') marcarTodasPrendasEntregadasPedido(${orden?.id || 'null'}, '${safeNumero}')` : 'return false;'}"
                title="${canBulkDeliver ? 'Marcar todas las prendas entregadas' : 'Todas las prendas ya fueron entregadas'}"
                ${canBulkDeliver ? '' : 'disabled aria-disabled="true"'}
                style="background: linear-gradient(135deg, #0f766e 0%, #0d9488 100%); color: #ffffff;">
                <i class="fas fa-check-double"></i>
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
    setTimeout(() => { fila.style.backgroundColor = _rtGetRowBaseBackground(false, isDelivered); }, 2000);
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
    _rtInstallSwalNuevoPedidoGuard();

    // Esperar a que Echo esté listo (resources/js/bootstrap.js lo proporciona)
    if (typeof window.waitForEcho !== 'function') {
        setTimeout(initializeRealtimeListener, 100);
        return;
    }

    window.waitForEcho(() => {
        try {
            _rtStartUiStabilityObserver();
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
        if (_rtHasBlockingModalOpen()) {
            _rtQueuedRefresh = true;
        } else {
            supervisorPedidosInsertarFilaNuevaAlInicio(pedido);
        }
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
    // Canal: pedidos.general - Actualizaciones globales de pedidos
    try {
        ws.subscribe('pedidos.general', '.pedido.actualizado', (data) => {
            _refreshTablaConDelay(data, 'pedidos.general:.pedido.actualizado');
        });
    } catch (error) {
    }

    // Compatibilidad: eventos estándar de OrdenUpdated (broadcastAs = orden.updated)
    try {
        ws.subscribe('pedidos.general', '.orden.updated', (data) => {
            _refreshTablaConDelay(data, 'pedidos.general:.orden.updated');
        });
    } catch (error) {
    }

    // Canal: supervisor-pedidos - Cambios de estado
    try {
        ws.subscribe('supervisor-pedidos', '.orden.updated', (data) => {
            _refreshTablaConDelay(data, 'supervisor-pedidos:.orden.updated');
        });
    } catch (error) {
    }

    // Compatibilidad legacy (si existe algún emisor sin broadcastAs)
    try {
        ws.subscribe('supervisor-pedidos', 'OrdenUpdated', (data) => {
            _refreshTablaConDelay(data, 'supervisor-pedidos:OrdenUpdated');
        });
    } catch (error) {
    }

    // Canal adicional usado por varios módulos legacy
    try {
        ws.subscribe('ordenes', '.orden.updated', (data) => {
            _refreshTablaConDelay(data, 'ordenes:.orden.updated');
        });
    } catch (error) {
    }

    // Canal: pedidos.creados - Nuevos pedidos
    try {
        ws.subscribe('pedidos.creados', '.pedido.creado', (data) => {
            const pedido = data?.pedido || data?.orden || data || {};
            
            // Filtrar: NO mostrar pedidos en estado pendiente_cartera al supervisor
            if (pedido?.estado === 'pendiente_cartera') {
                console.log('[RT-SUPERVISOR] ⏭️ Pedido omitido (pendiente_cartera):', pedido?.numero_pedido);
                return;
            }
            
            if (_rtHasBlockingModalOpen()) {
                _rtQueuedRefresh = true;
            } else {
                supervisorPedidosInsertarFilaNuevaAlInicio(pedido);
            }
        });
    } catch (error) {
    }
}

/**
 * Refrescador de tabla con debounce para evitar múltiples requests
 */
function _refreshTablaConDelay(payload, eventName) {
    // Detectar si es actualización de estado para notificar
    const eventNameSafe = String(eventName);
    if (
        eventNameSafe.includes('.pedido.actualizado') ||
        eventNameSafe.includes('.orden.updated') ||
        eventNameSafe.includes('OrdenUpdated')
    ) {
        supervisorPedidosMaybeNotifyFromActualizado(payload);
    }

    if (_rtHasBlockingModalOpen()) {
        _rtQueuedRefresh = true;
        return;
    }

    // Debouncer: evitar múltiples requests al servidor
    if (window.__realtimeSupervisorRefreshTimeout) {
        clearTimeout(window.__realtimeSupervisorRefreshTimeout);
    }

    window.__realtimeSupervisorRefreshTimeout = setTimeout(async () => {
        _rtRefreshInFlight = true;
        try {
            console.log('[RT-SUPERVISOR] Iniciando refresco de tabla por evento:', eventName);
            const html = await _rtRepo.fetchPageContent(window.location.href);
            const doc = new DOMParser().parseFromString(html, 'text/html');
            const nuevaTabla = doc.querySelector('.table-scroll-container');
            const tablaActual = document.querySelector('.table-scroll-container');

            if (!nuevaTabla || !tablaActual) {
                console.warn('[RT-SUPERVISOR] No se pudo encontrar el contenedor de tabla en la respuesta.');
                return;
            }

            tablaActual.innerHTML = nuevaTabla.innerHTML;

            // Actualizar badges de bodega si la función existe
            if (typeof window.refreshVerButtonsBodegaBadges === 'function') {
                window.refreshVerButtonsBodegaBadges();
            }
        } catch (error) {
            console.error('[RT-SUPERVISOR] Error en refresco real-time:', error);
        } finally {
            _rtRefreshInFlight = false;
            _rtTryFlushQueuedRefresh();
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
