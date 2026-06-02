/**
 * =====================================================
 * SUPERVISOR PEDIDOS INDEX - FUNCIONALIDAD PRINCIPAL
 * =====================================================
 *
 * Requiere: supervisor-pedidos/core/bootstrap.js → window.supervisorPedidos
 */

if (!window.supervisorPedidos?.isReady) {
    throw new Error('[index] window.supervisorPedidos no esta disponible. Carga core/bootstrap.js ANTES.');
}

const _spFilter = window.supervisorPedidos.filterService;
const _spNotify = window.shared.notify;

// Ocultar overlay inicial solo cuando la pagina y modulos criticos estén listos.
(function initInitialLoadingOverlayController() {
    const overlay = document.getElementById('sp-loading-overlay');
    if (!overlay) return;

    const MAX_WAIT_MS = 10000;
    const POLL_MS = 150;
    const startedAt = Date.now();
    let hidden = false;
    let pollTimer = null;

    const clearPoll = () => {
        if (!pollTimer) return;
        window.clearTimeout(pollTimer);
        pollTimer = null;
    };

    const hideOverlay = (reason = 'ready') => {
        if (hidden) return;
        hidden = true;
        clearPoll();
        overlay.style.opacity = '0';
        window.setTimeout(() => {
            overlay.style.display = 'none';
        }, 300);
        console.log('[SP Loader] Overlay inicial oculto', { reason });
    };

    const shouldHide = () => {
        const appReady = Boolean(window.supervisorPedidos?.isReady);
        const entryReady = Boolean(window.supervisorPedidosEntry?.isReady);
        const pageLoaded = document.readyState === 'complete';
        return appReady && entryReady && pageLoaded;
    };

    const tick = () => {
        if (shouldHide()) {
            hideOverlay('all-ready');
            return;
        }

        if (Date.now() - startedAt >= MAX_WAIT_MS) {
            hideOverlay('max-wait');
            return;
        }

        pollTimer = window.setTimeout(tick, POLL_MS);
    };

    document.addEventListener('supervisor-pedidos:entry-ready', () => {
        if (shouldHide()) hideOverlay('entry-ready');
    }, { once: true });

    window.addEventListener('load', () => {
        if (shouldHide()) hideOverlay('window-load');
    }, { once: true });

    tick();
})();

// ===== VARIABLES GLOBALES =====
let filtroActual = null;
let _spCurrentListUrl = window.location.href;
const _spNovedadesByOrderCache = new Map();

function _spSetCurrentListUrl(urlString) {
    try {
        const parsed = new URL(urlString, window.location.origin);
        if (parsed.pathname.startsWith('/supervisor-pedidos')) {
            _spCurrentListUrl = parsed.toString();
        }
    } catch (_) {
        // noop
    }
}

function _spIncludeDespachoEnabled(urlString = window.location.href) {
    try {
        const parsed = new URL(urlString, window.location.origin);
        return parsed.searchParams.get('ver_todos_despacho') === '1';
    } catch (_) {
        return false;
    }
}

function _spUpdateDespachoToggleButton(urlString = window.location.href) {
    const btn = document.getElementById('btnToggleDespachoSupervisor');
    if (!btn) return;

    const includeDespacho = _spIncludeDespachoEnabled(urlString);
    const icon = includeDespacho ? 'fa-eye-slash' : 'fa-eye';
    const title = includeDespacho ? 'Ocultar despacho' : 'Ver todos (incluye despacho)';
    btn.setAttribute('title', title);
    btn.setAttribute('aria-label', title);
    btn.innerHTML = `<i class="fas ${icon}" style="font-size: 0.95rem;"></i>`;
}

function _spEscapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function _spNormalizeHtmlToPlainText(value) {
    const raw = String(value ?? '').trim();
    if (!raw) return '-';

    const decoder = document.createElement('textarea');
    decoder.innerHTML = raw;
    const decoded = decoder.value || raw;

    const container = document.createElement('div');
    container.innerHTML = decoded;

    const plainText = (container.textContent || container.innerText || '')
        .replace(/\s+/g, ' ')
        .trim();

    return plainText || '-';
}

function _spEscapeJsSingle(value) {
    return String(value ?? '').replace(/\\/g, '\\\\').replace(/'/g, "\\'");
}

function _spHasAllPrendasEntregadas(orden) {
    const pendientesCount = Number(orden?.prendas_pendientes_entrega_count);
    if (Number.isFinite(pendientesCount)) {
        return pendientesCount <= 0;
    }

    const estado = String(orden?.estado || '').trim().toUpperCase();
    return estado === 'ENTREGADO' || estado === 'FINALIZADA' || estado === 'FINALIZADO';
}

function _spGetRowBaseBackground(isSelected, isDelivered) {
    if (isSelected) {
        return isDelivered ? '#86efac' : '#d1d5db';
    }
    return isDelivered ? '#dcfce7' : 'white';
}

function _spGetRowHoverBackground(isDelivered) {
    return isDelivered ? '#bbf7d0' : '#f9fafb';
}

async function marcarTodasPrendasEntregadasPedido(pedidoId, numeroPedido = '') {
    if (!pedidoId) {
        _spNotify.error('No se pudo determinar el pedido para entrega masiva.');
        return;
    }

    if (typeof window.marcarTodasPrendasEntregadas !== 'function') {
        _spNotify.error('La entrega masiva no esta disponible en este momento.');
        return;
    }

    try {
        await window.marcarTodasPrendasEntregadas(pedidoId, { numeroPedido });
    } catch (error) {
        console.error('[marcarTodasPrendasEntregadasPedido] Error:', error);
        _spNotify.error(error?.message || 'No se pudo completar la entrega masiva.');
    }
}

window.marcarTodasPrendasEntregadasPedido = marcarTodasPrendasEntregadasPedido;

async function ensureEditorModulesReady() {
    if (typeof window.ensureSupervisorEditorModulesLoaded !== 'function') {
        return;
    }

    await window.ensureSupervisorEditorModulesLoaded();
}

function _spFormatDateTime(value) {
    if (!value) return '--';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return '--';
    const dd = String(date.getDate()).padStart(2, '0');
    const mm = String(date.getMonth() + 1).padStart(2, '0');
    const yyyy = date.getFullYear();
    const hh = String(date.getHours()).padStart(2, '0');
    const min = String(date.getMinutes()).padStart(2, '0');
    return `${dd}/${mm}/${yyyy} ${hh}:${min}`;
}

function _spFormatDate(value) {
    if (!value) return null;
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return null;
    const dd = String(date.getDate()).padStart(2, '0');
    const mm = String(date.getMonth() + 1).padStart(2, '0');
    const yyyy = date.getFullYear();
    return `${dd}/${mm}/${yyyy}`;
}

function _spFormatDateTime12h(value) {
    if (!value) return '-';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return '-';
    const dd = String(date.getDate()).padStart(2, '0');
    const mm = String(date.getMonth() + 1).padStart(2, '0');
    const yyyy = date.getFullYear();
    let hh = date.getHours();
    const min = String(date.getMinutes()).padStart(2, '0');
    const ampm = hh >= 12 ? 'PM' : 'AM';
    hh = hh % 12;
    if (hh === 0) hh = 12;
    return `${dd}/${mm}/${yyyy} ${String(hh).padStart(2, '0')}:${min} ${ampm}`;
}

function _spCountBusinessDays(startDate, endDate) {
    if (!(startDate instanceof Date) || !(endDate instanceof Date)) return 0;
    if (Number.isNaN(startDate.getTime()) || Number.isNaN(endDate.getTime())) return 0;

    const from = new Date(startDate.getFullYear(), startDate.getMonth(), startDate.getDate());
    const to = new Date(endDate.getFullYear(), endDate.getMonth(), endDate.getDate());

    if (from.getTime() === to.getTime()) return 0;

    const forward = from < to;
    const a = forward ? from : to;
    const b = forward ? to : from;

    let count = 0;
    const cursor = new Date(a);
    cursor.setDate(cursor.getDate() + 1);

    while (cursor <= b) {
        const day = cursor.getDay();
        if (day !== 0 && day !== 6) count += 1;
        cursor.setDate(cursor.getDate() + 1);
    }

    return forward ? count : -count;
}

function _spCountNovedades(novedades) {
    const value = String(novedades ?? '').trim();
    if (!value) return 0;
    return value.split(/\n\s*\n/).filter(Boolean).length;
}

function _spStateBadge(estado) {
    const map = {
        PENDIENTE_SUPERVISOR: { bg: '#fff3cd', text: '#856404', label: 'Pendiente Supervisor' },
        PENDIENTE_INSUMOS: { bg: '#d1ecf1', text: '#0c5460', label: 'Pendiente Insumos' },
        'En Ejecución': { bg: '#d4edda', text: '#155724', label: 'En Ejecución' },
        'No iniciado': { bg: '#e2e3e5', text: '#383d41', label: 'No Iniciado' },
        Entregado: { bg: '#d4edda', text: '#155724', label: 'Entregado' },
        Finalizada: { bg: '#d4edda', text: '#155724', label: 'Finalizada' },
        Anulada: { bg: '#f8d7da', text: '#721c24', label: 'Anulada' },
        DEVUELTO_A_ASESORA: { bg: '#f8d7da', text: '#721c24', label: 'Devuelto' },
    };
    return map[estado] || { bg: '#e2e3e5', text: '#383d41', label: estado || '-' };
}

function _spNormalizeNovedadesInput(input) {
    if (Array.isArray(input)) {
        return input.map((item) => String(item ?? '').trim()).filter(Boolean);
    }

    if (input && typeof input === 'object') {
        return Object.values(input).map((item) => String(item ?? '').trim()).filter(Boolean);
    }

    const text = String(input ?? '').trim();
    if (!text) return [];

    return text
        .split(/\n\s*\n+/)
        .map((item) => item.trim())
        .filter(Boolean);
}

function abrirNovedades(ordenId, novedadesData) {
    const modal = document.getElementById('modalNovedades');
    const content = document.getElementById('modalNovedadesContent');

    if (!modal || !content) {
        console.error('[Novedades] Modal no encontrado en el DOM');
        return;
    }

    const novedades = _spNormalizeNovedadesInput(novedadesData);

    if (novedades.length === 0) {
        content.innerHTML = `
            <div style="background:#ffffff;border:1px solid #e5e7eb;border-radius:10px;padding:1rem;color:#6b7280;">
                Sin novedades registradas para la orden #${_spEscapeHtml(ordenId)}.
            </div>
        `;
    } else {
        content.innerHTML = novedades
            .map((novedad, index) => `
                <div style="background:#ffffff;border:1px solid #e5e7eb;border-radius:10px;padding:1rem;margin-bottom:.75rem;">
                    <div style="font-size:12px;color:#6b7280;font-weight:700;margin-bottom:.35rem;">Novedad ${index + 1}</div>
                    <div style="white-space:pre-wrap;color:#111827;line-height:1.45;">${_spEscapeHtml(novedad)}</div>
                </div>
            `)
            .join('');
    }

    modal.style.display = 'flex';
}

function cerrarModalNovedades() {
    const modal = document.getElementById('modalNovedades');
    if (!modal) return;
    modal.style.display = 'none';
}

window.abrirNovedades = abrirNovedades;
window.cerrarModalNovedades = cerrarModalNovedades;

async function _spFetchNovedadesByOrderId(ordenId) {
    const key = String(ordenId || '').trim();
    if (!key) return [];
    if (_spNovedadesByOrderCache.has(key)) {
        return _spNovedadesByOrderCache.get(key);
    }

    const response = await fetch(`/api/supervisor-pedidos/ordenes/${encodeURIComponent(key)}/novedades`, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
        cache: 'no-store',
    });

    const payload = await response.json();
    if (!response.ok || !payload?.success) {
        throw new Error(payload?.message || 'No se pudieron cargar novedades');
    }

    const rawNovedades = payload?.data?.novedades ?? '';
    const normalizadas = _spNormalizeNovedadesInput(rawNovedades);
    _spNovedadesByOrderCache.set(key, normalizadas);
    return normalizadas;
}

function _spBuildPageUrl(page) {
    const url = new URL(_spCurrentListUrl || window.location.href, window.location.origin);
    url.searchParams.set('page', String(page));
    return url.toString();
}

window.renderSupervisorOrdersTable = function renderSupervisorOrdersTable(payload) {
    const container = document.getElementById('supervisorPedidosIndexContent');
    if (!container) return;

    const ordenesData = payload?.ordenes || {};
    const rows = Array.isArray(ordenesData.data) ? ordenesData.data : [];
    const pedidosSeleccionados = Array.isArray(payload?.pedidosSeleccionados) ? payload.pedidosSeleccionados : [];

    const header = `
        <style>
            .sp-orders-grid {
                display: grid;
                grid-template-columns: 60px 220px 130px 140px 120px 220px 150px 150px 150px 150px 150px 150px;
                gap: 1.2rem;
                min-width: max-content;
                box-sizing: border-box;
            }
            .sp-orders-grid > div { min-width: 0; }
            .sp-date-cell {
                white-space: nowrap;
                display: inline-block;
                font-size: 0.85rem;
                color: #6b7280;
            }
        </style>
        <div style="background: #e5e7eb; border-radius: 8px; overflow: visible; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08); padding: 0.75rem; width: 100%; max-width: 100%;">
            <div class="table-scroll-container" style="overflow-x: auto; overflow-y: auto; width: 100%; max-width: 100%; max-height: 800px; border-radius: 6px; scrollbar-width: thin; scrollbar-color: #cbd5e1 #f1f5f9;">
                <div class="sp-orders-grid" style="background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%); color: white; padding: 0.75rem 1rem; font-weight: 600; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px; border-radius: 6px;">
                    <div class="th-wrapper" style="display: flex; align-items: center; justify-content: center; gap: 0.5rem;"><span>Listo</span></div>
                    <div class="th-wrapper" style="display: flex; align-items: center; justify-content: center; gap: 0.5rem;"><span>Acciones</span></div>
                    <div class="th-wrapper" style="display: flex; align-items: center; gap: 0.5rem;"><span>Aprob. Cartera</span></div>
                    <div class="th-wrapper" style="display: flex; align-items: center;"><span>Días Restantes</span></div>
                    <div class="th-wrapper" style="display: flex; align-items: center; gap: 0.5rem;"><span>Numero</span></div>
                    <div class="th-wrapper" style="display: flex; align-items: center; gap: 0.5rem;"><span>Cliente</span></div>
                    <div class="th-wrapper" style="display: flex; align-items: center; gap: 0.5rem;"><span>Asesora</span></div>
                    <div class="th-wrapper" style="display: flex; align-items: center; gap: 0.5rem;"><span>Estado</span></div>
                    <div class="th-wrapper" style="display: flex; align-items: center; gap: 0.5rem;"><span>Novedades</span></div>
                    <div class="th-wrapper" style="display: flex; align-items: center; gap: 0.5rem;"><span>Forma Pago</span></div>
                    <div class="th-wrapper" style="display: flex; align-items: center;"><span>Aprob. Supervisor</span></div>
                    <div class="th-wrapper" style="display: flex; align-items: center; gap: 0.5rem;"><span>Fecha</span></div>
                </div>
    `;

    let body = '';
    if (rows.length === 0) {
        body = `<div style="padding: 3rem 2rem; text-align: center; color: #6b7280;"><i class="fas fa-inbox" style="font-size: 3rem; color: #d1d5db; margin-bottom: 1rem; display: block;"></i><p style="font-size: 1rem; margin: 0;">No hay Ördenes disponibles</p></div>`;
    } else {
        body = rows.map((orden) => {
            const isSelected = pedidosSeleccionados.includes(orden.id);
            const numeroPedido = orden.numero_pedido ?? 'sin-numero';
            const numeroPedidoText = _spEscapeHtml(`#${numeroPedido}`);
            const numeroPedidoNoHash = String(numeroPedido).replace(/#/g, '');
            const estado = orden.estado ?? 'Pendiente';
            const estadoInfo = _spStateBadge(estado);
            const novedadesCount = _spCountNovedades(orden.novedades);
            const novedadesJson = _spEscapeHtml(JSON.stringify(orden.novedades ?? ''));
            const asesora = _spEscapeHtml(orden?.asesora?.name ?? 'N/A');
            const formaPago = _spEscapeHtml(orden?.forma_de_pago ?? 'N/A');
            const cliente = _spEscapeHtml(orden?.cliente ?? '');
            const fecha = _spFormatDateTime(orden?.created_at);
            const fechaAprobacionCartera = _spFormatDateTime12h(orden?.aprobado_por_cartera_en);
            const fechaAprobacionSupervisor = _spFormatDateTime12h(orden?.aprobado_por_supervisor_en);
            const fechaEstimadaRaw = orden?.fecha_estimada_de_entrega ?? orden?.fecha_estimada_entrega ?? orden?.fecha_estimada ?? null;
            const fechaEstimada = _spFormatDate(fechaEstimadaRaw) ?? '-';
            const diaEntrega = Number(orden?.dia_de_entrega);
            let diasRestantes = null;
            if (Number.isFinite(diaEntrega) && orden?.created_at) {
                const transcurridos = _spCountBusinessDays(new Date(orden.created_at), new Date());
                diasRestantes = Math.max(0, diaEntrega - transcurridos);
            } else if (fechaEstimadaRaw) {
                const hastaFechaEstimada = _spCountBusinessDays(new Date(), new Date(fechaEstimadaRaw));
                diasRestantes = Math.max(0, hastaFechaEstimada);
            }
            const canApprove = estado === 'PENDIENTE_SUPERVISOR' && !Boolean(orden?.es_solo_epp);
            const canBulkDeliver = !_spHasAllPrendasEntregadas(orden);
            const isDelivered = !canBulkDeliver;
            const jsNumero = _spEscapeJsSingle(numeroPedidoNoHash);
            const jsNumeroHash = _spEscapeJsSingle(String(numeroPedido));
            const rowBaseBackground = _spGetRowBaseBackground(isSelected, isDelivered);
            const rowHoverBackground = _spGetRowHoverBackground(isDelivered);
            const rowSelectedBackground = _spGetRowBaseBackground(true, isDelivered);
            const rowUnselectedBackground = _spGetRowBaseBackground(false, isDelivered);

            return `
                <div class="sp-orders-grid" style="padding: 1rem; border-bottom: 1px solid #e5e7eb; align-items: center; background: ${rowBaseBackground}; transition: background 0.2s ease;"
                    onmouseover="if (!this.dataset.seleccionado || this.dataset.seleccionado === 'false') this.style.background='${rowHoverBackground}'"
                    onmouseout="this.style.background = (this.dataset.seleccionado === 'true') ? '${rowSelectedBackground}' : '${rowUnselectedBackground}'"
                    data-seleccionado="${isSelected ? 'true' : 'false'}"
                    data-entregado="${isDelivered ? 'true' : 'false'}"
                    data-pedido-row="true"
                    data-pedido-id="${orden.id}">
                    <div style="display: flex; align-items: center; justify-content: center;">
                        <input type="checkbox" class="pedido-checkbox" data-pedido-id="${orden.id}" title="Seleccionar pedido" style="width: 18px; height: 18px; cursor: pointer;" ${isSelected ? 'checked' : ''}>
                    </div>
                    <div style="display: flex; gap: 0.5rem; align-items: center; justify-content: center;">
                        <button class="btn-accion btn-accion--ver btn-ver-dropdown" data-menu-id="menu-ver-${numeroPedidoNoHash}" data-pedido="${numeroPedidoNoHash}" data-pedido-id="${orden.id}" title="Ver Opciones" style="position:relative;overflow:visible;"><i class="fas fa-eye"></i><span class="btn-ver-bodega-badge" data-bodega-button-badge style="display:none;position:absolute;top:-7px;right:-7px;min-width:18px;height:18px;padding:0 5px;border-radius:999px;background:#dc2626;color:#fff;font-size:10px;font-weight:700;line-height:18px;text-align:center;box-shadow:0 2px 6px rgba(0,0,0,.25);">0</span></button>
                        ${canApprove ? `<button class="btn-accion btn-accion--aprobar" data-action="aprobar" data-pedido-id="${orden.id}" data-pedido-numero="${jsNumero}" title="Aprobar Pedido"><i class="fas fa-check"></i></button>` : ''}
                        ${canApprove ? `<button class="btn-accion btn-accion--anular" data-action="anular" data-pedido-id="${orden.id}" data-pedido-numero="${jsNumeroHash}" title="Pasar a Revisión"><i class="fas fa-ban"></i></button>` : ''}
                        <button class="btn-accion ${canBulkDeliver ? '' : 'btn-accion--disabled'}"
                            data-action="entregar"
                            data-pedido-id="${orden.id}"
                            data-pedido-numero="${jsNumero}"
                            title="${canBulkDeliver ? 'Marcar todas las prendas entregadas' : 'Todas las prendas ya fueron entregadas'}"
                            ${canBulkDeliver ? '' : 'disabled aria-disabled="true"'}
                            style="background: linear-gradient(135deg, #0f766e 0%, #0d9488 100%); color: #ffffff;">
                            <i class="fas fa-check-double"></i>
                        </button>
                        <button class="btn-accion btn-accion--ocultar" data-action="ocultar" data-pedido-id="${orden.id}" data-pedido-numero="${jsNumero}" title="Ocultar Pedido"><i class="fas fa-eye-slash"></i></button>
                    </div>
                    <div><span class="sp-date-cell">${fechaAprobacionCartera}</span></div>
                    <div>
                        ${diasRestantes !== null
                            ? `<span style="display: inline-flex; flex-direction: column; line-height: 1.1; color: #dc2626; font-weight: 700; font-size: 0.78rem;"><span>${diasRestantes} días</span><span>hábiles restantes</span><span style="margin-top: 0.2rem; color: #6b7280; font-weight: 600; font-size: 0.72rem;">Est.: ${fechaEstimada}</span></span>`
                            : `<span style="display: inline-flex; flex-direction: column; line-height: 1.1; color: #6b7280; font-weight: 600; font-size: 0.78rem;"><span>-</span><span style="margin-top: 0.2rem; font-size: 0.72rem;">Est.: ${fechaEstimada}</span></span>`
                        }
                    </div>
                    <div><span style="font-weight: 600; color: #1e5ba8;">${numeroPedidoText}</span></div>
                    <div><span>${cliente}</span></div>
                    <div><span>${asesora}</span></div>
                    <div><span style="background: ${estadoInfo.bg}; color: ${estadoInfo.text}; padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: bold; white-space: nowrap; display: inline-block;">${_spEscapeHtml(estadoInfo.label)}</span></div>
                    <div>
                        ${novedadesCount > 0
                            ? `<button class="btn-novedades" type="button" data-orden-id="${orden.id}" data-has-novedades="1" data-novedades-count="${novedadesCount}" style="background: #e8f3ff; color: #1e40af; padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: bold; white-space: nowrap; border: 1px solid #bfdbfe; cursor: pointer; transition: all 0.2s ease;">${novedadesCount} novedades</button>`
                            : `<span style="background: #f3f4f6; color: #9ca3af; padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: bold; white-space: nowrap;">Sin novedades</span>`
                        }
                    </div>
                    <div><span>${formaPago}</span></div>
                    <div><span class="sp-date-cell">${fechaAprobacionSupervisor}</span></div>
                    <div><span class="sp-date-cell">${fecha}</span></div>
                </div>
            `;
        }).join('');
    }

    const footerTop = `</div></div>`;
    const currentPage = Number(ordenesData.current_page || 1);
    const lastPage = Number(ordenesData.last_page || 1);
    const total = Number(ordenesData.total || 0);
    let pagination = '';

    if (lastPage > 1 || rows.length > 0) {
        const prevDisabled = currentPage <= 1;
        const nextDisabled = currentPage >= lastPage;

        const makeBtn = (label, page, disabled = false, variant = 'default') => {
            const baseStyle = 'min-width: 40px; height: 38px; padding: 0 12px; border-radius: 10px; font-weight: 600; display: inline-flex; align-items: center; justify-content: center; text-decoration: none; font-size: 13px;';
            const styleByVariant = {
                default: 'background: #ffffff; border: 1px solid #cbd5e1; color: #334155;',
                nav: 'background: #f8fafc; border: 1px solid #cbd5e1; color: #1e293b;',
            };
            const activeStyle = styleByVariant[variant] || styleByVariant.default;

            if (disabled) {
                return `<button disabled style="${baseStyle} background: #f1f5f9; border: 1px solid #e2e8f0; color: #94a3b8; cursor: not-allowed;">${label}</button>`;
            }

            return `<a href="${_spEscapeHtml(_spBuildPageUrl(page))}" style="${baseStyle} ${activeStyle} cursor: pointer;">${label}</a>`;
        };

        const makePageBtn = (page, isActive = false) => {
            if (isActive) {
                return `<button disabled style="min-width: 40px; height: 38px; padding: 0 10px; background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); border: 1px solid #1d4ed8; border-radius: 10px; color: #ffffff; font-weight: 700; cursor: default; font-size: 13px;">${page}</button>`;
            }

            return `<a href="${_spEscapeHtml(_spBuildPageUrl(page))}" style="min-width: 40px; height: 38px; padding: 0 10px; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 10px; color: #334155; font-weight: 600; display: inline-flex; align-items: center; justify-content: center; text-decoration: none; font-size: 13px;">${page}</a>`;
        };

        const makeEllipsis = () => `<span style="min-width: 24px; text-align: center; color: #94a3b8; font-weight: 700;">...</span>`;

        const pages = [];
        const radius = 2;
        for (let page = 1; page <= lastPage; page += 1) {
            if (page === 1 || page === lastPage || Math.abs(page - currentPage) <= radius) {
                pages.push(page);
            }
        }

        let pageButtons = '';
        let previousPage = 0;
        pages.forEach((page) => {
            if (previousPage && page - previousPage > 1) {
                pageButtons += makeEllipsis();
            }
            pageButtons += makePageBtn(page, page === currentPage);
            previousPage = page;
        });

        pagination = `
            <div style="margin-top: 1.25rem; display: flex; flex-direction: column; align-items: center; gap: 0.55rem;">
                <div style="display: flex; justify-content: center; align-items: center; gap: 6px; flex-wrap: wrap; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 8px;">
                ${makeBtn('&laquo; Primera', 1, prevDisabled, 'nav')}
                ${makeBtn('&larr; Anterior', Math.max(1, currentPage - 1), prevDisabled, 'nav')}
                ${pageButtons}
                ${makeBtn('Siguiente &rarr;', Math.min(lastPage, currentPage + 1), nextDisabled, 'nav')}
                ${makeBtn('Última &raquo;', lastPage, nextDisabled, 'nav')}
                </div>
                <span style="color: #64748b; font-size: 13px; font-weight: 600;">Página ${currentPage} de ${lastPage} | Total: ${total} registros</span>
            </div>
        `;
    }

    container.innerHTML = `${header}${body}${footerTop}${pagination}`;
    scheduleDeferredBodegaBadgesPrefetch();
};

// ===== TOGGLE MENU ACCIONES =====
function toggleAcciones(event, ordenId) {
    event.stopPropagation();
    const menu = document.getElementById(`menu-${ordenId}`);

    document.querySelectorAll('.action-menu:not([style*="display: none"])').forEach(m => {
        if (m.id !== `menu-${ordenId}`) {
            m.style.display = 'none';
        }
    });

    if (menu.style.display === 'none' || menu.style.display === '') {
        menu.style.display = 'block';
    } else {
        menu.style.display = 'none';
    }
}

document.addEventListener('click', function(e) {
    if (!e.target.closest('.action-menu') && !e.target.closest('.action-view-btn')) {
        document.querySelectorAll('.action-menu').forEach(menu => {
            menu.style.display = 'none';
        });
    }
});

window.addEventListener('resize', () => closeAllVerMenus());
document.addEventListener('scroll', () => closeAllVerMenus(), true);

// ===== FILTROS HELPERS =====
function getValoresFiltroDesdeURL(columna) {
    return _spFilter.getActiveFilterValues(columna);
}

function asegurarBadgeEnBoton(btn) {
    if (!btn) return null;
    let badge = btn.querySelector('.filter-badge');
    if (!badge) {
        badge = document.createElement('span');
        badge.className = 'filter-badge';
        badge.textContent = '0';
        btn.appendChild(badge);
    }
    return badge;
}

function actualizarIndicadoresFiltros() {
    const botones = document.querySelectorAll('#supervisorPedidosIndexContent .btn-filter-column');
    if (!botones || botones.length === 0) return;

    botones.forEach(btn => {
        const columna = resolveFilterColumn(btn);
        const valores = columna ? getValoresFiltroDesdeURL(columna) : [];
        const cantidad = valores.length;
        const badge = asegurarBadgeEnBoton(btn);
        if (cantidad > 0) {
            btn.classList.add('has-filter');
            if (badge) badge.textContent = String(cantidad);
        } else {
            btn.classList.remove('has-filter');
            if (badge) badge.textContent = '0';
        }
    });
}


function resolveFilterColumn(btn) {
    if (!btn) return '';

    const col = (btn.getAttribute('data-col') || '').trim();
    if (col) return col;

    // Compatibilidad con botones viejos sin data-col
    const title = btn.getAttribute('title') || '';
    switch (title) {
        case 'Filtrar Fecha': return 'fecha';
        case 'Filtrar Número':
        case 'Filtrar Número': return 'numero';
        case 'Filtrar Cliente': return 'cliente';
        case 'Filtrar Estado': return 'estado';
        case 'Filtrar Asesora': return 'asesora';
        case 'Filtrar Forma Pago': return 'forma_pago';
        case 'Filtrar Aprob. Cartera': return 'aprobacion_cartera';
        default: return '';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    actualizarIndicadoresFiltros();
    scheduleDeferredBodegaBadgesPrefetch();

    // Buscar sin recargar toda la pagina: filtra por AJAX en la tabla.
    const searchForm = document.querySelector('form.search-form');
    if (searchForm) {
        searchForm.addEventListener('submit', function (event) {
            event.preventDefault();

            const action = searchForm.getAttribute('action') || window.location.pathname;
            const url = new URL(action, window.location.origin);
            const formData = new FormData(searchForm);

            for (const [key, rawValue] of formData.entries()) {
                const value = String(rawValue ?? '').trim();
                if (value === '') continue;
                url.searchParams.set(key, value);
            }

            // Siempre resetear paginacion al filtrar desde el buscador.
            url.searchParams.delete('page');

            navegarSupervisorPedidos(url.toString());
        });
    }

    // La tabla ya llega renderizada por Blade en la carga inicial.
    // Evitamos un segundo fetch AJAX inmediato que duplicaba trabajo y retrasaba la vista.
    if (!document.querySelector('#supervisorPedidosIndexContent [data-pedido-row="true"]')) {
        navegarSupervisorPedidos(window.location.href, { pushState: false });
    }
});

// ===== MENU VER ORDEN =====
const SP_VER_MENU_CLASS = 'sp-ver-dropdown-menu';
const SP_BODEGA_RESUMEN_ENDPOINT = (pedidoId) => `/api/supervisor-pedidos/ordenes/${pedidoId}/bodega-novedades-resumen`;
const SP_BODEGA_NOVEDADES_ENDPOINT = (pedidoId) => `/api/supervisor-pedidos/ordenes/${pedidoId}/bodega-novedades`;
const spBodegaResumenCache = new Map();
let spBodegaDeferredPrefetchScheduled = false;
let spBodegaDeferredPrefetchCompleted = false;
let spBodegaDeferredPrefetchAttempts = 0;
let spBodegaDeferredPrefetchTimer = null;

function getVisibleVerButtons() {
    return Array.from(document.querySelectorAll('.btn-ver-dropdown[data-pedido-id]'))
        .filter((btn) => !!btn && btn.offsetParent !== null);
}

function scheduleDeferredBodegaBadgesPrefetch() {
    if (spBodegaDeferredPrefetchCompleted || spBodegaDeferredPrefetchScheduled) return;
    spBodegaDeferredPrefetchScheduled = true;

    const runPrefetch = () => {
        spBodegaDeferredPrefetchScheduled = false;
        const visibleButtons = getVisibleVerButtons();
        if (visibleButtons.length === 0) {
            // Si la tabla aun no termina de renderizar, reintentar antes de desistir.
            spBodegaDeferredPrefetchAttempts += 1;
            if (spBodegaDeferredPrefetchAttempts <= 10) {
                spBodegaDeferredPrefetchTimer = window.setTimeout(() => {
                    scheduleDeferredBodegaBadgesPrefetch();
                }, 350);
                return;
            }
            spBodegaDeferredPrefetchCompleted = true;
            return;
        }

        // Reusar manager moderno para batch + cache compartido.
        if (window.spBodegaBadgesManager?.refreshVerButtonsBodegaBadges) {
            spBodegaDeferredPrefetchCompleted = true;
            window.spBodegaBadgesManager.refreshVerButtonsBodegaBadges().catch(() => {});
            return;
        }

        // Si el manager moderno aun no existe, reintentar de forma acotada.
        spBodegaDeferredPrefetchAttempts += 1;
        if (spBodegaDeferredPrefetchAttempts <= 6) {
            spBodegaDeferredPrefetchTimer = window.setTimeout(() => {
                scheduleDeferredBodegaBadgesPrefetch();
            }, 450);
            return;
        }
        // Ultimo fallback: usar implementacion legacy para pintar contador al cargar.
        refreshVerButtonsBodegaBadges().catch(() => {});
    };

    if ('requestIdleCallback' in window) {
        window.requestIdleCallback(runPrefetch, { timeout: 3200 });
        return;
    }

    setTimeout(runPrefetch, 1400);
}

function _spUseModernBodegaManager() {
    return window.__SP_BODEGA_BADGES_MANAGER_ACTIVE
        && typeof window.spBodegaBadgesManager === 'object'
        && window.spBodegaBadgesManager !== null;
}

function _spLogLegacyBodegaBridge(message) {
    const isLocal = ['localhost', '127.0.0.1', '::1'].includes(window.location.hostname);
    if (isLocal) {
        console.info(`[SP Legacy->Modern Bodega] ${message}`);
    }
}

function closeAllVerMenus(exceptId = null) {
    document.querySelectorAll(`.${SP_VER_MENU_CLASS}`).forEach((menu) => {
        if (!exceptId || menu.id !== exceptId) {
            menu.style.display = 'none';
        }
    });
}

function _spNormalizePendingCount(value) {
    const n = Number(value || 0);
    if (!Number.isFinite(n) || n < 0) return 0;
    return Math.floor(n);
}

async function _spFetchBodegaResumen(pedidoId) {
    if (_spUseModernBodegaManager()) {
        _spLogLegacyBodegaBridge('_spFetchBodegaResumen neutralizado (usa manager moderno)');
        return null;
    }

    const cacheKey = String(pedidoId || '');
    if (!cacheKey) return null;
    if (spBodegaResumenCache.has(cacheKey)) return spBodegaResumenCache.get(cacheKey);

    try {
        const response = await fetch(SP_BODEGA_RESUMEN_ENDPOINT(cacheKey), {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            cache: 'no-store',
        });
        const payload = await response.json();
        if (response.ok && payload?.success) {
            spBodegaResumenCache.set(cacheKey, payload);
            return payload;
        }
    } catch (error) {
        console.error('[BodegaResumen] Error:', error);
    }

    return null;
}

async function _spRenderBodegaBadge(opcionBodega, pedidoId) {
    if (!opcionBodega) return;
    if (_spUseModernBodegaManager() && window.spBodegaBadgesManager?.updateMenuOptionBadge) {
        await window.spBodegaBadgesManager.updateMenuOptionBadge(opcionBodega, pedidoId);
        const menuBadge = opcionBodega.querySelector('[data-bodega-pendientes-badge]');
        const menuCount = _spNormalizePendingCount(menuBadge?.textContent);
        _spSyncButtonBadgeFromPedidoId(pedidoId, menuCount);
        return;
    }
    const badge = opcionBodega.querySelector('[data-bodega-pendientes-badge]');
    if (!badge) return;

    const payload = await _spFetchBodegaResumen(pedidoId);
    const notesCount = _spNormalizePendingCount(payload?.notes_count);

    if (notesCount > 0) {
        badge.style.display = 'inline-flex';
        badge.textContent = notesCount > 99 ? '99+' : String(notesCount);
        opcionBodega.style.backgroundColor = '#fef2f2';
    } else {
        badge.style.display = 'none';
        badge.textContent = '0';
        opcionBodega.style.backgroundColor = '#ffffff';
    }

    _spSyncButtonBadgeFromPedidoId(pedidoId, notesCount);
}

function _spRenderBadgeOnVerButton(button, pendingCount) {
    if (_spUseModernBodegaManager()) {
        _spLogLegacyBodegaBridge('_spRenderBadgeOnVerButton neutralizado (render moderno)');
        return;
    }

    if (!button) return;
    const badge = button.querySelector('[data-bodega-button-badge]');
    if (!badge) return;

    const count = _spNormalizePendingCount(pendingCount);
    if (count > 0) {
        badge.style.display = 'inline-block';
        badge.textContent = count > 99 ? '99+' : String(count);
    } else {
        badge.style.display = 'none';
        badge.textContent = '0';
    }
}

function _spForceRenderBadgeOnVerButton(button, pendingCount) {
    if (!button) return;
    const badge = button.querySelector('[data-bodega-button-badge]');
    if (!badge) return;

    const count = _spNormalizePendingCount(pendingCount);
    if (count > 0) {
        badge.style.display = 'inline-block';
        badge.textContent = count > 99 ? '99+' : String(count);
    } else {
        badge.style.display = 'none';
        badge.textContent = '0';
    }
}

function _spSyncButtonBadgeFromPedidoId(pedidoId, pendingCount) {
    const key = String(pedidoId || '').trim();
    if (!key) return;
    const button = document.querySelector(`.btn-ver-dropdown[data-pedido-id="${key}"]`);
    if (!button) return;
    _spForceRenderBadgeOnVerButton(button, pendingCount);
}

async function refreshVerButtonsBodegaBadges() {
    if (_spUseModernBodegaManager() && typeof window.spBodegaBadgesManager?.refreshVerButtonsBodegaBadges === 'function') {
        _spLogLegacyBodegaBridge('refreshVerButtonsBodegaBadges puente a manager moderno');
        return window.spBodegaBadgesManager.refreshVerButtonsBodegaBadges();
    }

    const buttons = Array.from(document.querySelectorAll('.btn-ver-dropdown[data-pedido-id]'));
    if (buttons.length === 0) return;

    try {
        const currentUrl = new URL(window.location.href);
        if ((currentUrl.searchParams.get('aprobacion_cartera') || '').trim() === 'no_aprobado') {
            buttons.forEach((b) => _spRenderBadgeOnVerButton(b, 0));
            return;
        }
    } catch (_) {
        // noop
    }

    // Optimizacion: en vistas filtradas por busqueda no bloquear con batch de badges.
    // Los badges se pueden resolver al abrir cada menu "Ver".
    try {
        const currentUrl = new URL(window.location.href);
        const busqueda = (currentUrl.searchParams.get('busqueda') || '').trim();
        if (busqueda !== '') {
            buttons.forEach((b) => _spRenderBadgeOnVerButton(b, 0));
            return;
        }
    } catch (_) {
        // noop
    }

    // Extraer todos los pedido_ids
    const pedidoIds = buttons
        .map(b => String(b.getAttribute('data-pedido-id') || '').trim())
        .filter(id => id);

    if (pedidoIds.length === 0) {
        buttons.forEach(b => _spRenderBadgeOnVerButton(b, 0));
        return;
    }

    try {
        // UNA SOLA LLAMADA para obtener los resumenes de todos los pedidos
        const response = await fetch('/api/supervisor-pedidos/ordenes/bodega-novedades-resumen-batch', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
            body: JSON.stringify({ pedido_ids: pedidoIds }),
        });

        if (!response.ok) {
            // Fallback: hacer las llamadas individuales si el batch falla
            await Promise.all(buttons.map(async (button) => {
                const pedidoId = String(button.getAttribute('data-pedido-id') || '').trim();
                const payload = await _spFetchBodegaResumen(pedidoId);
                _spRenderBadgeOnVerButton(button, payload?.notes_count ?? 0);
            }));
            return;
        }

        const data = await response.json();
        if (!data?.success || !Array.isArray(data.data)) {
            console.warn('[BodegaBadges] Respuesta invalida del batch endpoint');
            return;
        }

        // Mapear resultados por pedido_id
        const resumenMap = new Map(data.data.map(r => [String(r.pedido_id), r]));

        // Renderizar badges para todos los botones
        buttons.forEach(button => {
            const pedidoId = String(button.getAttribute('data-pedido-id') || '').trim();
            const resumen = resumenMap.get(pedidoId);
            _spRenderBadgeOnVerButton(button, resumen?.notes_count ?? 0);
        });
    } catch (error) {
        console.error('[BodegaBadges] Error:', error);
        // Fallback silencioso
        buttons.forEach(b => _spRenderBadgeOnVerButton(b, 0));
    }
}

function _spEnsureBodegaNovedadesModal() {
    if (_spUseModernBodegaManager()) {
        _spLogLegacyBodegaBridge('_spEnsureBodegaNovedadesModal neutralizado (modal moderno)');
        return null;
    }

    if (!document.getElementById('spBodegaNovedadesModalStyles')) {
        const style = document.createElement('style');
        style.id = 'spBodegaNovedadesModalStyles';
        style.textContent = '@keyframes spSpin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }';
        document.head.appendChild(style);
    }

    let modal = document.getElementById('spBodegaNovedadesModal');
    if (modal) return modal;

    modal = document.createElement('div');
    modal.id = 'spBodegaNovedadesModal';
    modal.style.cssText = `
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.55);
        z-index: 1000000;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 1rem;
    `;

    modal.innerHTML = `
        <div style="width:min(980px, 96vw); max-height:90vh; background:#ffffff; border-radius:14px; box-shadow:0 20px 45px rgba(0,0,0,.35); overflow:hidden; display:flex; flex-direction:column;">
            <div style="background:linear-gradient(135deg,#1e40af 0%,#1e3a8a 100%); color:#fff; padding:14px 16px; display:flex; align-items:center; justify-content:space-between; gap:1rem;">
                <div style="display:flex; flex-direction:column; gap:.25rem; min-width:0;">
                    <div style="display:flex; align-items:center; gap:.6rem;">
                        <i class="fas fa-sticky-note"></i>
                        <strong id="spBodegaNovedadesTitle">Novedades Bodega</strong>
                    </div>
                    <div id="spBodegaNovedadesMeta" style="font-size:12px; opacity:.95; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"></div>
                </div>
                <button type="button" id="spBodegaNovedadesClose" style="border:none; background:rgba(255,255,255,.18); color:#fff; width:34px; height:34px; border-radius:8px; cursor:pointer; font-size:18px; line-height:1;">&times;</button>
            </div>
            <div id="spBodegaNovedadesBody" style="padding:14px 16px; overflow:auto; background:#f8fafc;"></div>
        </div>
    `;

    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });

    modal.querySelector('#spBodegaNovedadesClose')?.addEventListener('click', () => {
        modal.style.display = 'none';
    });

    document.body.appendChild(modal);
    return modal;
}

function _spRenderBodegaNovedadesLoading() {
    if (_spUseModernBodegaManager()) {
        _spLogLegacyBodegaBridge('_spRenderBodegaNovedadesLoading neutralizado (modal moderno)');
        return;
    }

    const modal = _spEnsureBodegaNovedadesModal();
    if (!modal) return;
    const body = modal.querySelector('#spBodegaNovedadesBody');
    if (!body) return;
    body.innerHTML = `
        <div style="display:flex; align-items:center; justify-content:center; gap:.75rem; padding:2rem; color:#374151;">
            <span class="material-symbols-rounded" style="animation: spSpin 1s linear infinite;">progress_activity</span>
            <span>Cargando novedades de bodega...</span>
        </div>
    `;
}

function _spRenderBodegaNovedadesContent(payload) {
    if (_spUseModernBodegaManager()) {
        _spLogLegacyBodegaBridge('_spRenderBodegaNovedadesContent neutralizado (modal moderno)');
        return;
    }

    const modal = _spEnsureBodegaNovedadesModal();
    if (!modal) return;
    const body = modal.querySelector('#spBodegaNovedadesBody');
    const meta = modal.querySelector('#spBodegaNovedadesMeta');
    if (!body) return;
    if (meta) {
        const asesora = _spEscapeHtml(payload?.asesora || '-');
        const cliente = _spEscapeHtml(payload?.cliente || '-');
        meta.innerHTML = `<strong>Asesora:</strong> ${asesora} | <strong>Cliente:</strong> ${cliente}`;
    }

    const data = Array.isArray(payload?.data) ? payload.data : [];
    if (data.length === 0) {
        body.innerHTML = `
            <div style="padding:2rem; text-align:center; color:#6b7280;">
                <i class="fas fa-inbox" style="font-size:2rem; color:#cbd5e1; margin-bottom:.75rem;"></i>
                <div>No hay novedades de bodega para este pedido en Costura pendiente.</div>
            </div>
        `;
        return;
    }

    const rows = data.map((item) => {
        const contenido = _spEscapeHtml(item?.contenido || '').replace(/\n/g, '<br>');
        const prendaNombre = _spEscapeHtml(item?.prenda_nombre || '-');
        const prendaDescripcion = _spEscapeHtml(_spNormalizeHtmlToPlainText(item?.prenda_descripcion));
        const numeroPedido = _spEscapeHtml(item?.numero_pedido || payload?.numero_pedido || '-');
        const talla = _spEscapeHtml(item?.talla || '-');
        const genero = _spEscapeHtml(item?.genero || '-');
        const cantidad = _spNormalizePendingCount(item?.cantidad);
        const fecha = _spFormatDateTime(item?.created_at);

        return `
            <div style="background:#fff; border:1px solid #e5e7eb; border-radius:10px; padding:12px 14px; margin-bottom:10px;">
                <div style="display:flex; justify-content:space-between; gap:1rem; align-items:center; margin-bottom:8px;">
                    <div></div>
                    <span style="font-size:12px; color:#6b7280;">${fecha}</span>
                </div>
                <div style="font-size:13px; color:#334155; margin-bottom:6px;">
                    <strong>Prenda:</strong> ${prendaNombre}
                </div>
                <div style="font-size:13px; color:#475569; margin-bottom:6px;">
                    <strong>Descripcion:</strong> ${prendaDescripcion}
                </div>
                <div style="font-size:12px; color:#64748b; margin-bottom:4px; font-weight:700;">--Del Pedido</div>
                <div style="font-size:13px; color:#475569; margin-bottom:8px;">
                    <strong>Talla:</strong> ${talla}
                    <span style="margin:0 .4rem; color:#94a3b8;">|</span>
                    <strong>Cantidad:</strong> ${cantidad}
                </div>
                <div style="font-size:13px; color:#475569; margin-bottom:8px;">
                    <strong>Genero:</strong> ${genero}
                </div>
                <div style="font-size:14px; color:#111827; line-height:1.45; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:10px;">
                    ${contenido || '<span style="color:#94a3b8;">Sin contenido</span>'}
                </div>
            </div>
        `;
    }).join('');

    body.innerHTML = rows;
}

async function abrirModalNovedadesBodega(pedidoId, numeroPedido) {
    if (_spUseModernBodegaManager() && typeof window.spBodegaBadgesManager?.abrirModalNovedadesBodega === 'function') {
        _spLogLegacyBodegaBridge('abrirModalNovedadesBodega puente a manager moderno');
        return window.spBodegaBadgesManager.abrirModalNovedadesBodega(pedidoId, numeroPedido);
    }
    const modal = _spEnsureBodegaNovedadesModal();
    const title = modal.querySelector('#spBodegaNovedadesTitle');
    const meta = modal.querySelector('#spBodegaNovedadesMeta');
    if (title) {
        title.textContent = `Novedades Bodega - Pedido #${numeroPedido || pedidoId}`;
    }
    if (meta) {
        meta.textContent = 'Cargando datos del pedido...';
    }
    modal.style.display = 'flex';
    _spRenderBodegaNovedadesLoading();

    try {
        const response = await fetch(SP_BODEGA_NOVEDADES_ENDPOINT(pedidoId), {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            cache: 'no-store',
        });
        const payload = await response.json();

        if (!response.ok || !payload?.success) {
            throw new Error(payload?.message || 'No se pudo cargar la informacion');
        }

        _spRenderBodegaNovedadesContent(payload);
    } catch (error) {
        const body = modal.querySelector('#spBodegaNovedadesBody');
        if (body) {
            body.innerHTML = `
                <div style="padding:2rem; text-align:center; color:#991b1b;">
                    <i class="fas fa-triangle-exclamation" style="font-size:1.8rem; margin-bottom:.65rem;"></i>
                    <div>No se pudieron cargar las novedades de bodega.</div>
                    <div style="font-size:12px; color:#b91c1c; margin-top:.4rem;">${_spEscapeHtml(error?.message || '')}</div>
                </div>
            `;
        }
    }
}

function getOrCreateVerMenu(button) {
    const pedidoId = button.getAttribute('data-pedido-id');
    const numeroPedido = button.getAttribute('data-pedido') || pedidoId;
    const menuId = button.getAttribute('data-menu-id') || `menu-ver-${pedidoId}`;
    let menu = document.getElementById(menuId);

    if (menu) {
        const opcionBodega = menu.querySelector('button[data-action="bodega-novedades"]');
        _spRenderBodegaBadge(opcionBodega, pedidoId);
        return menu;
    }

    menu = document.createElement('div');
    menu.id = menuId;
    menu.className = SP_VER_MENU_CLASS;
    menu.style.cssText = `
        position: fixed;
        min-width: 220px;
        background: #ffffff;
        border: 1px solid #d1d5db;
        border-radius: 12px;
        box-shadow: 0 14px 30px rgba(15, 23, 42, 0.22);
        overflow: hidden;
        z-index: 999999;
        display: none;
    `;

    const buildMenuOption = (btn, iconClass, text, suffixHtml = '') => {
        btn.innerHTML = `
            <span style="display:inline-flex;align-items:center;gap:10px;">
                <i class="${iconClass}" style="width:16px;text-align:center;color:#374151;"></i>
                <span>${text}</span>
                ${suffixHtml}
            </span>
        `;
    };

    const opcionVer = document.createElement('button');
    opcionVer.type = 'button';
    buildMenuOption(opcionVer, 'fas fa-eye', 'Ver Pedido');
    opcionVer.style.cssText = 'width:100%;border:none;background:#ffffff;padding:12px 14px;text-align:left;cursor:pointer;color:#111827;font-size:15px;font-weight:600;line-height:1.2;transition:background-color .15s ease;';
    opcionVer.onmouseover = () => { opcionVer.style.backgroundColor = '#eef2ff'; };
    opcionVer.onmouseout = () => { opcionVer.style.backgroundColor = '#ffffff'; };
    opcionVer.onclick = () => {
        closeAllVerMenus();
        verOrdenDetalles(pedidoId, numeroPedido);
    };

    menu.appendChild(opcionVer);

    if (typeof window.abrirSelectorRecibos === 'function') {
        const separadorRecibos = document.createElement('div');
        separadorRecibos.style.cssText = 'height:1px;background:#e5e7eb;';

        const opcionRecibos = document.createElement('button');
        opcionRecibos.type = 'button';
        buildMenuOption(opcionRecibos, 'fas fa-receipt', 'Ver Recibos');
        opcionRecibos.style.cssText = 'width:100%;border:none;background:#ffffff;padding:12px 14px;text-align:left;cursor:pointer;color:#111827;font-size:15px;font-weight:600;line-height:1.2;transition:background-color .15s ease;';
        opcionRecibos.onmouseover = () => { opcionRecibos.style.backgroundColor = '#eef2ff'; };
        opcionRecibos.onmouseout = () => { opcionRecibos.style.backgroundColor = '#ffffff'; };
        opcionRecibos.onclick = () => {
            closeAllVerMenus();
            window.abrirSelectorRecibos(pedidoId);
        };

        menu.appendChild(separadorRecibos);
        menu.appendChild(opcionRecibos);
    }

    const separadorBodega = document.createElement('div');
    separadorBodega.style.cssText = 'height:1px;background:#e5e7eb;';

    const opcionBodega = document.createElement('button');
    opcionBodega.type = 'button';
    opcionBodega.dataset.action = 'bodega-novedades';
    buildMenuOption(
        opcionBodega,
        'fas fa-sticky-note',
        'Novedades Bodega',
        '<span data-bodega-pendientes-badge style="margin-left:auto;display:none;align-items:center;justify-content:center;min-width:22px;height:22px;padding:0 6px;border-radius:999px;background:#dc2626;color:#fff;font-size:11px;font-weight:700;">0</span>'
    );
    opcionBodega.style.cssText = 'width:100%;border:none;background:#ffffff;padding:12px 14px;text-align:left;cursor:pointer;color:#111827;font-size:15px;font-weight:600;line-height:1.2;transition:background-color .15s ease;';
    opcionBodega.onmouseover = () => { opcionBodega.style.backgroundColor = '#eef2ff'; };
    opcionBodega.onmouseout = () => { opcionBodega.style.backgroundColor = '#ffffff'; };
    opcionBodega.onclick = () => {
        closeAllVerMenus();
        abrirModalNovedadesBodega(pedidoId, numeroPedido);
    };
    _spRenderBodegaBadge(opcionBodega, pedidoId);

    menu.appendChild(separadorBodega);
    menu.appendChild(opcionBodega);

    const separadorSeguimiento = document.createElement('div');
    separadorSeguimiento.style.cssText = 'height:1px;background:#e5e7eb;';

    const opcionSeguimiento = document.createElement('button');
    opcionSeguimiento.type = 'button';
    buildMenuOption(opcionSeguimiento, 'fas fa-tasks', 'Seguimiento');
    opcionSeguimiento.style.cssText = 'width:100%;border:none;background:#ffffff;padding:12px 14px;text-align:left;cursor:pointer;color:#111827;font-size:15px;font-weight:600;line-height:1.2;transition:background-color .15s ease;';
    opcionSeguimiento.onmouseover = () => { opcionSeguimiento.style.backgroundColor = '#eef2ff'; };
    opcionSeguimiento.onmouseout = () => { opcionSeguimiento.style.backgroundColor = '#ffffff'; };
    opcionSeguimiento.onclick = () => {
        closeAllVerMenus();
        abrirSeguimiento(pedidoId);
    };

    menu.appendChild(separadorSeguimiento);
    menu.appendChild(opcionSeguimiento);

    document.body.appendChild(menu);
    return menu;
}

function positionVerMenu(button, menu) {
    const rect = button.getBoundingClientRect();
    const margin = 8;
    const menuWidth = menu.offsetWidth || 180;
    const menuHeight = menu.offsetHeight || 130;

    // Abrir a la derecha del boton por defecto.
    let left = rect.right + margin;
    let top = rect.top;

    // Si no hay espacio a la derecha, usar el lado izquierdo como respaldo.
    if (left + menuWidth > window.innerWidth - margin) {
        left = rect.left - menuWidth - margin;
    }

    if (left < margin) left = margin;
    if (top + menuHeight > window.innerHeight - margin) {
        top = Math.max(margin, rect.top - menuHeight - margin);
    }

    menu.style.left = `${left}px`;
    menu.style.top = `${top}px`;
}

document.addEventListener('click', function(e) {
    if (!e.target.closest('.btn-ver-dropdown') && !e.target.closest(`.${SP_VER_MENU_CLASS}`)) {
        closeAllVerMenus();
    }
});

// ===== FILTROS DE COLUMNAS =====

function abrirModalFiltro(columna) {
    filtroActual = columna;
    const modalTitulo = document.getElementById('modalFiltroTitulo');
    const filtroContenido = document.getElementById('filtroContenido');
    const modal = document.getElementById('modalFiltro');

    let titulo = '';
    let campoNombre = '';

    switch(columna) {
        case 'id-orden':
            titulo = 'Filtrar por ID Orden';
            campoNombre = 'numero';
            break;
        case 'numero':
            titulo = 'Filtrar por Número';
            campoNombre = 'numero';
            break;
        case 'cliente':
            titulo = 'Filtrar por Cliente';
            campoNombre = 'cliente';
            break;
        case 'fecha':
            titulo = 'Filtrar por Fecha';
            campoNombre = 'fecha';
            break;
        case 'estado': {
            titulo = 'Filtrar por Estado';
            campoNombre = 'estado';
            const estadosDisplay = ['Pendiente', 'No iniciado', 'En Ejecución', 'Entregado', 'Anulada', 'Pendiente Supervisor', 'Pendiente Insumos', 'Pendiente Cartera', 'Rechazado Cartera', 'Devuelto a Asesora'];
            const estadosDB    = ['Pendiente', 'No iniciado', 'En Ejecución', 'Entregado', 'Anulada', 'PENDIENTE_SUPERVISOR', 'PENDIENTE_INSUMOS', 'pendiente_cartera', 'RECHAZADO_CARTERA', 'DEVUELTO_A_ASESORA'];
            filtroContenido.innerHTML = `
                <div class="form-group">
                    <input type="text" id="buscadorEstado" class="form-control" placeholder="Buscar estado..." style="margin-bottom: 1rem;">
                    <div id="listaEstados">
                        ${estadosDisplay.map((display, i) => `
                            <label style="display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem; cursor: pointer; border-radius: 4px;">
                                <input type="checkbox" name="estado" value="${estadosDB[i]}" class="filtro-checkbox">
                                <span>${display}</span>
                            </label>
                        `).join('')}
                    </div>
                </div>
            `;
            const seleccionados = new Set(getValoresFiltroDesdeURL('estado'));
            document.querySelectorAll('#listaEstados .filtro-checkbox').forEach(cb => {
                cb.checked = seleccionados.has(cb.value);
            });
            setTimeout(() => {
                document.getElementById('buscadorEstado')?.addEventListener('input', function(e) {
                    const valor = e.target.value.toLowerCase();
                    document.querySelectorAll('#listaEstados label').forEach(label => {
                        label.style.display = label.textContent.toLowerCase().includes(valor) ? 'flex' : 'none';
                    });
                });
            }, 0);
            modal.style.display = 'flex';
            return;
        }
        case 'aprobacion_cartera': {
            titulo = 'Filtrar por Aprobación Cartera';
            campoNombre = 'aprobacion_cartera';
            const opciones = [
                { label: 'No aprobado por cartera', value: 'no_aprobado' },
                { label: 'Aprobado por cartera', value: 'aprobado' },
            ];
            filtroContenido.innerHTML = `
                <div class="form-group">
                    <div id="listaAprobacionCartera">
                        ${opciones.map((opcion) => `
                            <label style="display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem; cursor: pointer; border-radius: 4px;">
                                <input type="checkbox" name="aprobacion_cartera" value="${opcion.value}" class="filtro-checkbox">
                                <span>${opcion.label}</span>
                            </label>
                        `).join('')}
                    </div>
                </div>
            `;
            const seleccionados = new Set(getValoresFiltroDesdeURL('aprobacion_cartera'));
            document.querySelectorAll('#listaAprobacionCartera .filtro-checkbox').forEach(cb => {
                cb.checked = seleccionados.has(cb.value);
            });
            modal.style.display = 'flex';
            return;
        }
        case 'asesora':
            titulo = 'Filtrar por Asesora';
            campoNombre = 'asesora';
            break;
        case 'forma-pago':
        case 'forma_pago':
            titulo = 'Filtrar por Forma de Pago';
            campoNombre = 'forma_pago';
            break;
    }

    if (campoNombre && columna !== 'estado') {
        cargarOpcionesFiltro(campoNombre, titulo, modal, filtroContenido);
    }
}

function cargarOpcionesFiltro(campo, titulo, modal, filtroContenido) {
    _spFilter.loadFilterOptions(campo)
        .then(data => {
            const modalTitulo = document.getElementById('modalFiltroTitulo');
            modalTitulo.textContent = titulo;

            filtroContenido.innerHTML = `
                <div class="form-group">
                    <input type="text" id="buscadorFiltro" class="form-control" placeholder="Buscar..." style="margin-bottom: 1rem;">
                    <div id="listaOpciones" style="max-height: 300px; overflow-y: auto;"></div>
                    <div id="paginacionFiltro" style="display:flex;align-items:center;justify-content:space-between;gap:0.5rem;margin-top:0.75rem;">
                        <button type="button" id="btnFiltroPrev" class="btn btn-sm btn-light" style="border:1px solid #d1d5db;">Anterior</button>
                        <span id="filtroPaginaInfo" style="font-size:0.85rem;color:#6b7280;"></span>
                        <button type="button" id="btnFiltroNext" class="btn btn-sm btn-light" style="border:1px solid #d1d5db;">Siguiente</button>
                    </div>
                </div>
            `;

            const buscador = document.getElementById('buscadorFiltro');
            const lista    = document.getElementById('listaOpciones');
            const paginacion = document.getElementById('paginacionFiltro');
            const btnPrev = document.getElementById('btnFiltroPrev');
            const btnNext = document.getElementById('btnFiltroNext');
            const paginaInfo = document.getElementById('filtroPaginaInfo');
            const columnaMap = (campo === 'forma_pago') ? 'forma_pago' : campo;
            const seleccionados = new Set(getValoresFiltroDesdeURL(columnaMap));
            const opciones = Array.isArray(data.opciones) ? data.opciones : [];
            const pageSize = 20;
            let currentPage = 1;
            let lastQuery = '';

            function normalizarTexto(v) {
                return String(v || '').toLowerCase();
            }

            function renderOpciones(query) {
                const q = normalizarTexto(query).trim();
                const queryChanged = q !== lastQuery;
                if (queryChanged) currentPage = 1;
                lastQuery = q;

                const filtered = q === ''
                    ? opciones
                    : opciones.filter(op => normalizarTexto(op).includes(q));

                const totalItems = filtered.length;
                const totalPages = Math.max(1, Math.ceil(totalItems / pageSize));
                if (currentPage > totalPages) currentPage = totalPages;
                const start = (currentPage - 1) * pageSize;
                const end = start + pageSize;
                const list = filtered.slice(start, end);

                if (!lista) return;

                if (list.length === 0) {
                    lista.innerHTML = `<div style="padding:0.5rem;color:#6b7280;">Sin resultados</div>`;
                } else {
                    lista.innerHTML = list.map(opcion => {
                    const safeValue = (opcion === null || opcion === undefined) ? '' : String(opcion);
                    const checked = seleccionados.has(safeValue) ? 'checked' : '';
                    let labelValue = safeValue || '(Sin especificar)';

                    if (campo === 'fecha' && safeValue) {
                        // Mostrar fecha amigable manteniendo valor ISO para el filtro.
                        const parts = safeValue.split('-');
                        if (parts.length === 3) {
                            labelValue = `${parts[2]}/${parts[1]}/${parts[0]}`;
                        }
                    }
                    return `
                        <label style="display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem; cursor: pointer; border-radius: 4px; transition: background 0.2s;">
                            <input type="checkbox" name="${campo}" value="${safeValue}" class="filtro-checkbox" ${checked}>
                            <span>${labelValue}</span>
                        </label>
                    `;
                    }).join('');
                }

                if (paginacion && btnPrev && btnNext && paginaInfo) {
                    const shouldShowPagination = totalItems > pageSize;
                    paginacion.style.display = shouldShowPagination ? 'flex' : 'none';
                    btnPrev.disabled = currentPage <= 1;
                    btnNext.disabled = currentPage >= totalPages;
                    paginaInfo.textContent = `Página ${currentPage} de ${totalPages}`;
                }
            }

            if (lista) {
                lista.addEventListener('change', function(e) {
                    const cb = e.target;
                    if (!cb?.classList?.contains('filtro-checkbox')) return;
                    const val = String(cb.value ?? '');
                    cb.checked ? seleccionados.add(val) : seleccionados.delete(val);
                });
            }

            renderOpciones('');

            if (buscador) {
                buscador.addEventListener('input', e => renderOpciones(e.target.value));
            }

            btnPrev?.addEventListener('click', function() {
                if (currentPage <= 1) return;
                currentPage -= 1;
                renderOpciones(lastQuery);
            });

            btnNext?.addEventListener('click', function() {
                currentPage += 1;
                renderOpciones(lastQuery);
            });

            modal.style.display = 'flex';
        })
        .catch(() => {
            filtroContenido.innerHTML = `<p style="color: red;">Error cargando opciones de filtro</p>`;
            modal.style.display = 'flex';
        });
}

function cerrarModalFiltro() {
    document.getElementById('modalFiltro').style.display = 'none';
    filtroActual = null;
}

function aplicarFiltroColumna(event) {
    event.preventDefault();

    const valoresSeleccionados = Array.from(
        document.querySelectorAll('.filtro-checkbox:checked')
    ).map(cb => cb.value);

    const filteredUrl = _spFilter.buildFilteredUrl(filtroActual, valoresSeleccionados);

    cerrarModalFiltro();
    navegarSupervisorPedidos(filteredUrl);
}

async function navegarSupervisorPedidos(urlString, options = {}) {
    _spSetCurrentListUrl(urlString);
    _spUpdateDespachoToggleButton(urlString);
    const success = await _spFilter.navigateAjax(urlString, options);

    if (success) {
        _spSetCurrentListUrl(urlString);
        _spUpdateDespachoToggleButton(urlString);
        actualizarIndicadoresFiltros();
        window.dispatchEvent(new Event('supervisorPedidos:filtersUpdated'));

        setTimeout(() => {
            if (typeof window.cargarSeleccionesGuardadas === 'function') {
                window.cargarSeleccionesGuardadas();
            }
        }, 300);
    }
}

window.addEventListener('popstate', function() {
    _spSetCurrentListUrl(window.location.href);
    _spUpdateDespachoToggleButton(window.location.href);
    navegarSupervisorPedidos(window.location.href, { pushState: false });
    window.dispatchEvent(new Event('supervisorPedidos:filtersUpdated'));
});

document.addEventListener('DOMContentLoaded', function() {
    _spUpdateDespachoToggleButton(window.location.href);
    const btn = document.getElementById('btnToggleDespachoSupervisor');
    if (!btn) return;

    btn.addEventListener('click', function() {
        const url = new URL(window.location.href);
        const includeDespacho = _spIncludeDespachoEnabled(url.toString());

        if (includeDespacho) {
            url.searchParams.delete('ver_todos_despacho');
        } else {
            url.searchParams.set('ver_todos_despacho', '1');
        }

        url.searchParams.delete('page');
        navegarSupervisorPedidos(url.toString());
    });
});

document.addEventListener('click', function(e) {
    const a = e.target.closest('#supervisorPedidosIndexContent a');
    if (!a) return;
    const href = a.getAttribute('href');
    if (!href || href.startsWith('#')) return;
    if (a.target && a.target !== '_self') return;
    if (a.hasAttribute('download')) return;
    if (!href.startsWith(window.location.origin) && !href.startsWith('/')) return;
    const urlAbs = href.startsWith('http') ? href : (window.location.origin + href);
    let path = '';
    try { path = new URL(urlAbs).pathname || ''; } catch (e) { return; }
    if (!path.startsWith('/supervisor-pedidos')) return;
    e.preventDefault();
    navegarSupervisorPedidos(urlAbs);
});

// Cerrar modal al hacer clic fuera
document.getElementById('modalFiltro')?.addEventListener('click', function(e) {
    if (e.target === this) cerrarModalFiltro();
});

// ===== MODALES DE ORDENES =====
function verOrdenComparar(ordenId) {
    document.getElementById(`ver-menu-${ordenId}`).style.display = 'none';
    abrirModalComparar(ordenId);
}

function cerrarModalVerOrden() {
    document.getElementById('modalVerOrden').style.display = 'none';
}

// Contador de caracteres del textarea de anulación
document.getElementById('motivoAnulacion')?.addEventListener('input', function() {
    document.getElementById('contadorActual').textContent = this.value.length;
    const btnConfirmar = document.getElementById('btnConfirmarAnulacion');
    if (btnConfirmar) {
        btnConfirmar.disabled = this.value.length < 10 || this.value.length > 500;
    }
});

// Cerrar modales al hacer clic fuera
document.getElementById('modalVerOrden')?.addEventListener('click', function(e) {
    if (e.target === this) cerrarModalVerOrden();
});

document.getElementById('modalAnulacion')?.addEventListener('click', function(e) {
    if (e.target === this) cerrarModalAnulacion();
});

document.getElementById('modalExitoRevision')?.addEventListener('click', function(e) {
    if (e.target === this) cerrarModalExitoRevision();
});

document.getElementById('modalOcultar')?.addEventListener('click', function(e) {
    if (e.target === this) cerrarModalOcultar();
});

document.getElementById('modalExitoOcultar')?.addEventListener('click', function(e) {
    if (e.target === this) cerrarModalExitoOcultar();
});

// Delegado: novedades y filtros de columna
document.addEventListener('click', function(e) {
    const btnVerDropdown = e.target.closest('.btn-ver-dropdown');
    if (btnVerDropdown) {
        e.preventDefault();
        e.stopPropagation();
        if (typeof e.stopImmediatePropagation === 'function') {
            e.stopImmediatePropagation();
        }
        const menu = getOrCreateVerMenu(btnVerDropdown);
        const isOpen = menu.style.display === 'block';
        closeAllVerMenus(menu.id);
        if (!isOpen) {
            menu.style.display = 'block';
            positionVerMenu(btnVerDropdown, menu);

            // Hook on-demand: al abrir menu Ver, sincronizar badges de bodega
            // (boton Ver + opcion "Novedades Bodega") sin cargar nada en arranque.
            const pedidoId = btnVerDropdown.getAttribute('data-pedido-id');
            const opcionBodega = menu.querySelector('button[data-action="bodega-novedades"]');

            if (window.spBodegaBadgesManager?.refreshVerButtonsBodegaBadges) {
                window.spBodegaBadgesManager
                    .refreshVerButtonsBodegaBadges({ onlyButton: btnVerDropdown })
                    .catch(() => {});
            }

            if (window.spBodegaBadgesManager?.updateMenuOptionBadge && opcionBodega && pedidoId) {
                window.spBodegaBadgesManager
                    .updateMenuOptionBadge(opcionBodega, pedidoId)
                    .catch(() => {});
            }
        } else {
            menu.style.display = 'none';
        }
        return;
    }

    const btnNovedades = e.target.closest('.btn-novedades');
    if (btnNovedades) {
        e.preventDefault();
        const ordenId = String(btnNovedades.dataset.ordenId || '').trim();
        if (!ordenId) return;

        abrirNovedades(ordenId, ['Cargando novedades...']);
        _spFetchNovedadesByOrderId(ordenId)
            .then((novedades) => abrirNovedades(ordenId, novedades))
            .catch((err) => {
                console.error('[Novedades] Error al cargar novedades:', err);
                abrirNovedades(ordenId, []);
            });
        return;
    }

    const btnFiltro = e.target.closest('.btn-filter-column');
    if (btnFiltro) {
        e.preventDefault();
        e.stopPropagation();
        const columna = resolveFilterColumn(btnFiltro);
        if (!columna) return;
        abrirModalFiltro(columna);
    }
});

function handleSupervisorRowActionClick(e) {
    const btnRowAction = e.target.closest('.btn-accion[data-action]');
    if (!btnRowAction) return;

    e.preventDefault();
    if (btnRowAction.disabled || btnRowAction.getAttribute('aria-disabled') === 'true') {
        return;
    }

    const action = String(btnRowAction.getAttribute('data-action') || '').trim();
    const pedidoId = Number.parseInt(String(btnRowAction.getAttribute('data-pedido-id') || ''), 10);
    const pedidoNumero = String(btnRowAction.getAttribute('data-pedido-numero') || '').trim();

    if (!Number.isFinite(pedidoId) || pedidoId <= 0) {
        return;
    }

    if (action === 'aprobar' && typeof window.abrirModalAprobacion === 'function') {
        window.abrirModalAprobacion(pedidoId, pedidoNumero);
        return;
    }

    if (action === 'anular' && typeof window.abrirModalAnulacion === 'function') {
        window.abrirModalAnulacion(pedidoId, pedidoNumero);
        return;
    }

    if (action === 'ocultar' && typeof window.abrirModalOcultar === 'function') {
        window.abrirModalOcultar(pedidoId, pedidoNumero);
        return;
    }

    if (action === 'entregar') {
        marcarTodasPrendasEntregadasPedido(pedidoId, pedidoNumero);
    }
}

function bindSupervisorRowActionsDelegationOnce() {
    const container = document.getElementById('supervisorPedidosIndexContent');
    if (!container) return;
    if (container.dataset.rowActionsDelegated === '1') return;
    container.dataset.rowActionsDelegated = '1';
    container.addEventListener('click', handleSupervisorRowActionClick);
}

bindSupervisorRowActionsDelegationOnce();

// Función para aprobar orden
async function aprobarOrden(ordenId, numeroOrden) {
    const result = await _spNotify.confirm(`Â¿Deseas aprobar el pedido <strong>#${numeroOrden}</strong>?`, 'Â¿Aprobar Pedido?');

    if (!result.isConfirmed) return;

    Swal.fire({
        title: 'Procesando...',
        html: '<p>Por favor espera mientras se aprueba el pedido</p>',
        icon: 'info',
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => { Swal.showLoading(); }
    });

    try {
        const data = await window.shared.http.post(`/api/supervisor-pedidos/ordenes/${ordenId}/aprobar`, {});

        if (data.success) {
            Swal.fire({
                title: 'Â¡Aprobado!',
                html: `<p>${data.message || 'Pedido aprobado correctamente'}</p><p style="margin-top: 10px; font-weight: 600; color: #10b981;">Estado: ${data.estado}</p>`,
                icon: 'success',
                confirmButtonColor: '#10b981'
            }).then(() => {
                if (typeof cargarNotificacionesPendientes === 'function') {
                    cargarNotificacionesPendientes();
                }
                setTimeout(() => location.reload(), 1000);
            });
        } else {
            _spNotify.error(data.message || 'No se pudo aprobar el pedido');
        }
    } catch (error) {
        console.error('[aprobarOrden] Error:', error);
        _spNotify.error('Error al procesar la solicitud');
    }
}

async function verOrdenDetalles(ordenId, numeroPedido = null) {
    const menu = document.getElementById(`ver-menu-${ordenId}`);
    if (menu) menu.style.display = 'none';

    if (typeof window.verFacturaDelPedido !== 'function') {
        console.error('[verOrdenDetalles] verFacturaDelPedido no esta disponible');
        _spNotify.error('No se pudo abrir el detalle del pedido.');
        return;
    }

    try {
        await ensureEditorModulesReady();
        const numero = String(numeroPedido || ordenId || '');
        window.verFacturaDelPedido(numero, Number(ordenId));
    } catch (error) {
        console.error('[verOrdenDetalles] Error abriendo factura:', error);
        _spNotify.error('No se pudo abrir la factura del pedido.');
    }
}

function esperarFuncionGlobal(nombre, timeoutMs = 3000, intervalMs = 60) {
    return new Promise((resolve, reject) => {
        const startedAt = Date.now();
        const tick = () => {
            if (typeof window[nombre] === 'function') {
                resolve(window[nombre]);
                return;
            }
            if ((Date.now() - startedAt) >= timeoutMs) {
                reject(new Error(`Funcion global no disponible: ${nombre}`));
                return;
            }
            setTimeout(tick, intervalMs);
        };
        tick();
    });
}

async function abrirSeguimiento(ordenId) {
    const menu = document.getElementById(`ver-menu-${ordenId}`);
    if (menu) menu.style.display = 'none';

    try {
        if (typeof window.ensureTrackingRuntimeLoaded === 'function') {
            await window.ensureTrackingRuntimeLoaded();
        }

        const openTracking = await esperarFuncionGlobal('openOrderTrackingModal');
        await openTracking(ordenId);
    } catch (error) {
        console.error('[abrirSeguimiento] Error:', error);
        _spNotify.error('El modal de seguimiento no esta disponible. Intenta nuevamente.');
    }
}

async function editarPedido(pedidoId) {
    if (window.edicionEnProgreso) return;
    window.edicionEnProgreso = true;

    try {
        await ensureEditorModulesReady();
        await _ensureSwal();

        Swal.fire({
            html: `
                <div style="text-align: center; padding: 2rem;">
                    <div style="width: 60px; height: 60px; border: 4px solid #e5e7eb; border-top-color: #1e40af; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 1.5rem;"></div>
                    <p style="color: #6b7280; font-size: 14px; font-weight: 500; margin: 0;">Cargando datos del pedido...</p>
                </div>
                <style>@keyframes spin { to { transform: rotate(360deg); } }</style>
            `,
            width: '300px',
            padding: '0',
            background: 'white',
            showConfirmButton: false,
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => { document.body.style.overflow = 'hidden'; }
        });

        if (!window.PrendaEditorPreloader?.isReady?.()) {
            await window.PrendaEditorPreloader.loadWithLoader({ title: 'Cargando datos', message: 'Por favor espera...' });
        }

        const respuesta = await window.shared.http.get(`/api/pedidos/${pedidoId}`);
        if (!respuesta.success) throw new Error(respuesta.message || 'Error desconocido');

        const datos = respuesta.data || respuesta.datos;
        const datosTransformados = {
            id: datos.id || datos.numero_pedido,
            numero_pedido: datos.numero_pedido || datos.numero,
            numero: datos.numero || datos.numero_pedido,
            cliente: datos.cliente || 'Cliente sin especificar',
            asesora: datos.asesor || datos.asesora?.name || 'Asesor sin especificar',
            estado: datos.estado || 'Pendiente',
            forma_de_pago: datos.forma_pago || datos.forma_de_pago || 'No especificada',
            prendas: datos.prendas || [],
            epps: datos.epps_transformados || datos.epps || [],
            procesos: datos.procesos || [],
            ...datos
        };

        await abrirModalEditarPedido(pedidoId, datosTransformados, 'editar');

    } catch (err) {
        Swal.close();
        console.error('[editarPedido] Error:', err);
        _spNotify.error('No se pudo cargar el pedido: ' + err.message);
    } finally {
        window.edicionEnProgreso = false;
    }
}
