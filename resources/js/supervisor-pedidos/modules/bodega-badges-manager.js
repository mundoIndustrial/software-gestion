/**
 * Bodega Badges Manager
 * -------------------------------------------------------
 * Bloque A migrado desde public/js/supervisor-pedidos/index.js
 * - refreshVerButtonsBodegaBadges
 * - fetch /ordenes/bodega-novedades-resumen-batch
 * - badges del botón "Ver"
 * - abrirModalNovedadesBodega
 *
 * Nota: NO auto-ejecuta batch al cargar página.
 * Se activa on-demand (menu Ver o llamada explícita).
 */

const SUMMARY_BATCH_ENDPOINT = '/api/supervisor-pedidos/ordenes/bodega-novedades-resumen-batch';
const detailsEndpoint = (pedidoId) => `/api/supervisor-pedidos/ordenes/${pedidoId}/bodega-novedades`;

const summaryCache = new Map(); // pedidoId -> payload resumen
const summaryInFlight = new Map(); // pedidoId -> Promise
let fullRefreshInFlightPromise = null; // dedupe para refresh global

function normalizeCount(value) {
    const parsed = Number.parseInt(String(value ?? '0'), 10);
    return Number.isFinite(parsed) && parsed > 0 ? parsed : 0;
}

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function normalizeHtmlToPlainText(value) {
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

function formatDateTime(value) {
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

function renderBadgeOnVerButton(button, pendingCount) {
    if (!button) return;
    const badge = button.querySelector('[data-bodega-button-badge]');
    if (!badge) return;

    const count = normalizeCount(pendingCount);
    if (count > 0) {
        badge.style.display = 'inline-block';
        badge.textContent = count > 99 ? '99+' : String(count);
    } else {
        badge.style.display = 'none';
        badge.textContent = '0';
    }
}

function renderBadgeOnMenuOption(opcionBodega, pendingCount) {
    if (!opcionBodega) return;
    const badge = opcionBodega.querySelector('[data-bodega-pendientes-badge]');
    if (!badge) return;

    const count = normalizeCount(pendingCount);
    if (count > 0) {
        badge.style.display = 'inline-flex';
        badge.textContent = count > 99 ? '99+' : String(count);
        opcionBodega.style.backgroundColor = '#fef2f2';
    } else {
        badge.style.display = 'none';
        badge.textContent = '0';
        opcionBodega.style.backgroundColor = '#ffffff';
    }
}

function syncButtonBadgeFromMenuOption(opcionBodega, pedidoId) {
    if (!opcionBodega || !pedidoId) return;

    const menuBadge = opcionBodega.querySelector('[data-bodega-pendientes-badge]');
    if (!menuBadge) return;

    const button = document.querySelector(`.btn-ver-dropdown[data-pedido-id="${String(pedidoId).trim()}"]`);
    if (!button) return;

    const menuCount = normalizeCount(menuBadge.textContent);
    renderBadgeOnVerButton(button, menuCount);
}

async function fetchBatchSummaries(pedidoIds) {
    const uniqueIds = Array.from(new Set((pedidoIds || []).map((id) => String(id).trim()).filter(Boolean)));
    if (uniqueIds.length === 0) return new Map();

    const headers = {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
    };

    const response = await fetch(SUMMARY_BATCH_ENDPOINT, {
        method: 'POST',
        headers,
        body: JSON.stringify({ pedido_ids: uniqueIds }),
    });

    if (!response.ok) {
        throw new Error(`Batch endpoint failed: ${response.status}`);
    }

    const data = await response.json();
    if (!data?.success || !Array.isArray(data.data)) {
        throw new Error('Invalid batch payload');
    }

    const map = new Map();
    data.data.forEach((row) => {
        const key = String(row?.pedido_id ?? '').trim();
        if (!key) return;
        map.set(key, row);
        summaryCache.set(key, row);
    });

    return map;
}

async function getSummaryByPedidoId(pedidoId, options = {}) {
    const key = String(pedidoId ?? '').trim();
    if (!key) return null;
    const force = options?.force === true;

    if (!force && summaryCache.has(key)) return summaryCache.get(key);
    if (summaryInFlight.has(key)) return summaryInFlight.get(key);

    const promise = (async () => {
        try {
            const resultMap = await fetchBatchSummaries([key]);
            return resultMap.get(key) || null;
        } finally {
            summaryInFlight.delete(key);
        }
    })();

    summaryInFlight.set(key, promise);
    return promise;
}

function ensureBodegaNovedadesModal() {
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
        if (event.target === modal) modal.style.display = 'none';
    });
    modal.querySelector('#spBodegaNovedadesClose')?.addEventListener('click', () => {
        modal.style.display = 'none';
    });

    document.body.appendChild(modal);
    return modal;
}

function renderBodegaNovedadesLoading() {
    const modal = ensureBodegaNovedadesModal();
    const body = modal.querySelector('#spBodegaNovedadesBody');
    if (!body) return;

    body.innerHTML = `
        <div style="display:flex; align-items:center; justify-content:center; gap:.75rem; padding:2rem; color:#374151;">
            <span class="material-symbols-rounded" style="animation: spSpin 1s linear infinite;">progress_activity</span>
            <span>Cargando novedades de bodega...</span>
        </div>
    `;
}

function renderBodegaNovedadesContent(payload) {
    const modal = ensureBodegaNovedadesModal();
    const body = modal.querySelector('#spBodegaNovedadesBody');
    const meta = modal.querySelector('#spBodegaNovedadesMeta');
    if (!body) return;

    if (meta) {
        const asesora = escapeHtml(payload?.asesora || '-');
        const cliente = escapeHtml(payload?.cliente || '-');
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

    body.innerHTML = data.map((item) => {
        const contenido = escapeHtml(item?.contenido || '').replace(/\n/g, '<br>');
        const prendaNombre = escapeHtml(item?.prenda_nombre || '-');
        const prendaDescripcion = escapeHtml(normalizeHtmlToPlainText(item?.prenda_descripcion));
        const talla = escapeHtml(item?.talla || '-');
        const genero = escapeHtml(item?.genero || '-');
        const cantidad = normalizeCount(item?.cantidad);
        const fecha = formatDateTime(item?.created_at);

        return `
            <div style="background:#fff; border:1px solid #e5e7eb; border-radius:10px; padding:12px 14px; margin-bottom:10px;">
                <div style="display:flex; justify-content:space-between; gap:1rem; align-items:center; margin-bottom:8px;">
                    <div></div>
                    <span style="font-size:12px; color:#6b7280;">${fecha}</span>
                </div>
                <div style="font-size:13px; color:#334155; margin-bottom:6px;"><strong>Prenda:</strong> ${prendaNombre}</div>
                <div style="font-size:13px; color:#475569; margin-bottom:6px;"><strong>Descripción:</strong> ${prendaDescripcion}</div>
                <div style="font-size:12px; color:#64748b; margin-bottom:4px; font-weight:700;">--Del Pedido</div>
                <div style="font-size:13px; color:#475569; margin-bottom:8px;">
                    <strong>Talla:</strong> ${talla}
                    <span style="margin:0 .4rem; color:#94a3b8;">|</span>
                    <strong>Cantidad:</strong> ${cantidad}
                </div>
                <div style="font-size:13px; color:#475569; margin-bottom:8px;"><strong>Género:</strong> ${genero}</div>
                <div style="font-size:14px; color:#111827; line-height:1.45; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:10px;">
                    ${contenido || '<span style="color:#94a3b8;">Sin contenido</span>'}
                </div>
            </div>
        `;
    }).join('');
}

async function openBodegaNovedadesModal(pedidoId, numeroPedido) {
    const modal = ensureBodegaNovedadesModal();
    const title = modal.querySelector('#spBodegaNovedadesTitle');
    const meta = modal.querySelector('#spBodegaNovedadesMeta');
    if (title) title.textContent = `Novedades Bodega - Pedido #${numeroPedido || pedidoId}`;
    if (meta) meta.textContent = 'Cargando datos del pedido...';

    modal.style.display = 'flex';
    renderBodegaNovedadesLoading();

    try {
        const response = await fetch(detailsEndpoint(pedidoId), {
            method: 'GET',
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            cache: 'no-store',
        });
        const payload = await response.json();

        if (!response.ok || !payload?.success) {
            throw new Error(payload?.message || 'No se pudo cargar la información');
        }

        renderBodegaNovedadesContent(payload);
    } catch (error) {
        const body = modal.querySelector('#spBodegaNovedadesBody');
        if (body) {
            body.innerHTML = `
                <div style="padding:2rem; text-align:center; color:#991b1b;">
                    <i class="fas fa-triangle-exclamation" style="font-size:1.8rem; margin-bottom:.65rem;"></i>
                    <div>No se pudieron cargar las novedades de bodega.</div>
                    <div style="font-size:12px; color:#b91c1c; margin-top:.4rem;">${escapeHtml(error?.message || '')}</div>
                </div>
            `;
        }
    }
}

async function refreshVerButtonsBodegaBadges(options = {}) {
    const onlyButton = options?.onlyButton || null;
    const isFullRefresh = !onlyButton;

    if (isFullRefresh && fullRefreshInFlightPromise) {
        return fullRefreshInFlightPromise;
    }

    const run = async () => {
    const buttons = onlyButton
        ? [onlyButton]
        : Array.from(document.querySelectorAll('.btn-ver-dropdown[data-pedido-id]'));

    if (buttons.length === 0) return;

    const pedidoIds = buttons
        .map((b) => String(b.getAttribute('data-pedido-id') || '').trim())
        .filter(Boolean);

    if (pedidoIds.length === 0) {
        buttons.forEach((b) => renderBadgeOnVerButton(b, 0));
        return;
    }

    const missingIds = pedidoIds.filter((id) => !summaryCache.has(id));

    try {
        if (missingIds.length > 0) {
            await fetchBatchSummaries(missingIds);
        }
    } catch (error) {
        console.warn('[BodegaBadgesManager] Batch failed, fallback per-id', error);
        await Promise.all(missingIds.map((id) => getSummaryByPedidoId(id)));
    }

    buttons.forEach((button) => {
        const pedidoId = String(button.getAttribute('data-pedido-id') || '').trim();
        const resumen = summaryCache.get(pedidoId);
        renderBadgeOnVerButton(button, resumen?.notes_count ?? 0);
    });
    };

    if (isFullRefresh) {
        fullRefreshInFlightPromise = run().finally(() => {
            fullRefreshInFlightPromise = null;
        });
        return fullRefreshInFlightPromise;
    }

    return run();
}

async function updateMenuOptionBadge(opcionBodega, pedidoId) {
    if (!opcionBodega || !pedidoId) return;
    const resumen = await getSummaryByPedidoId(pedidoId, { force: true });
    const count = resumen?.notes_count ?? 0;
    renderBadgeOnMenuOption(opcionBodega, count);
    syncButtonBadgeFromMenuOption(opcionBodega, pedidoId);
}

function initBodegaBadgesManager() {
    // Bandera para compatibilidad temporal con legacy.
    window.__SP_BODEGA_BADGES_MANAGER_ACTIVE = true;

    // Exponer solo lo necesario.
    window.refreshVerButtonsBodegaBadges = refreshVerButtonsBodegaBadges;
    window.abrirModalNovedadesBodega = openBodegaNovedadesModal;

    // API mínima para puente con legacy.
    window.spBodegaBadgesManager = {
        refreshVerButtonsBodegaBadges,
        abrirModalNovedadesBodega: openBodegaNovedadesModal,
        updateMenuOptionBadge,
    };

    // On-demand: solo cuando el usuario interactúa con botón Ver.
    document.addEventListener('click', (event) => {
        const button = event.target.closest('.btn-ver-dropdown[data-pedido-id]');
        if (!button) return;
        refreshVerButtonsBodegaBadges({ onlyButton: button }).catch(() => {});
    });
}

export {
    initBodegaBadgesManager,
    refreshVerButtonsBodegaBadges,
    openBodegaNovedadesModal,
    updateMenuOptionBadge,
};
