function initOperarioLayout() {
    setupUserDropdown();
    setupNotificaciones();

    if (typeof window.__initDashboardSearch !== 'function') {
        setupSearch();
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initOperarioLayout);
} else {
    initOperarioLayout();
}

function setupUserDropdown() {
    const userBtn = document.getElementById('userBtn');
    const userMenu = document.getElementById('userMenu');

    if (!userBtn || !userMenu) return;

    userBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        userMenu.classList.toggle('active');
    });

    document.addEventListener('click', function (e) {
        if (!userBtn.contains(e.target) && !userMenu.contains(e.target)) {
            userMenu.classList.remove('active');
        }
    });
}

function setupNotificaciones() {
    const btn = document.getElementById('notificacionesBtn');
    const menu = document.getElementById('notificacionesMenu');
    const badge = document.getElementById('notificacionesBadge');
    const list = document.getElementById('notificacionesList');
    const empty = document.getElementById('notificacionesEmpty');
    const markAllBtn = document.getElementById('notificacionesMarkAll');

    if (!btn || !menu || !badge || !list || !empty || !markAllBtn) return;

    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const rolActual = String(window.USUARIO_ACTUAL?.rol || '').toLowerCase();
    const isVistaCostura = rolActual === 'vista-costura';
    const isCosturaReflectivo = rolActual === 'costura-reflectivo';
    const storageKey = `operario_push_notificaciones_${rolActual || 'anon'}`;
    const sinceKey = `operario_notificaciones_since_${rolActual || 'anon'}`;

    const tipoReciboNotificaciones = isCosturaReflectivo ? 'REFLECTIVO' : 'COSTURA';

    function loadSince() {
        try {
            return String(localStorage.getItem(sinceKey) || '').trim();
        } catch {
            return '';
        }
    }

    function saveSince(value) {
        try {
            localStorage.setItem(sinceKey, String(value || ''));
        } catch {
            // ignore
        }
    }

    function loadPushItems() {
        try {
            const raw = localStorage.getItem(storageKey);
            const data = raw ? JSON.parse(raw) : [];
            return Array.isArray(data) ? data : [];
        } catch {
            return [];
        }
    }

    function savePushItems(items) {
        try {
            localStorage.setItem(storageKey, JSON.stringify(items));
        } catch {
            // ignore
        }
    }

    function renderPushItems(items) {
        list.innerHTML = '';

        if (!items || items.length === 0) {
            empty.style.display = 'block';
            setBadgeCount(0);
            return;
        }

        empty.style.display = 'none';
        setBadgeCount(items.length);

        items.forEach((n) => {
            const row = document.createElement('div');
            row.className = 'notificacion-item';
            row.dataset.id = n.id;
            row.dataset.source = 'push';

            row.innerHTML = `
                <div class="notificacion-main">
                    <div class="notificacion-title">${n.titulo || 'Notificación'}</div>
                    <div class="notificacion-meta">${n.detalle || ''}${n.fecha ? ' · ' + n.fecha : ''}</div>
                </div>
                <button class="notificacion-read" type="button" data-action="leer">Descartar</button>
            `;

            list.appendChild(row);
        });
    }

    function setBadgeCount(count) {
        const c = Number(count) || 0;
        badge.textContent = String(c);
        badge.style.display = c > 0 ? 'inline-flex' : 'none';
    }

    function renderItems(items) {
        list.innerHTML = '';

        if (!items || items.length === 0) {
            empty.style.display = 'block';
            setBadgeCount(0);
            return;
        }

        empty.style.display = 'none';
        setBadgeCount(items.length);

        items.forEach((n) => {
            const row = document.createElement('div');
            row.className = 'notificacion-item';
            row.dataset.id = n.id;

            row.innerHTML = `
                <div class="notificacion-main">
                    <div class="notificacion-title">Nuevo recibo #${n.numero_recibo}</div>
                    <div class="notificacion-meta">${n.cliente || '-'} · ${n.fecha || ''}</div>
                </div>
                <button class="notificacion-read" type="button" data-action="leer">Marcar leída</button>
            `;

            list.appendChild(row);
        });
    }

    async function fetchNotificaciones() {
        try {
            const since = !isVistaCostura ? loadSince() : '';
            const qs = new URLSearchParams({
                tipo_recibo: tipoReciboNotificaciones,
                limit: '50',
            });
            if (since) {
                qs.set('since', since);
            }

            const resp = await fetch(`/operario/api/notificaciones/recibos?${qs.toString()}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            const data = await resp.json();
            if (!resp.ok || !data?.success) {
                throw new Error(data?.message || 'Error listando notificaciones');
            }

            renderItems(data.notificaciones || []);
            return data.notificaciones || [];
        } catch (e) {
            console.warn('[Notificaciones] Error cargando notificaciones', e);
            return [];
        }
    }

    function mergeServerItemsIntoPush(serverItems) {
        const current = loadPushItems();
        const map = new Map(current.map((n) => [String(n?.id), n]));

        (serverItems || []).forEach((n) => {
            const id = n?.id;
            if (!id) return;
            const key = String(id);
            if (map.has(key)) return;

            map.set(key, {
                id,
                titulo: `Nuevo recibo #${n?.numero_recibo}`,
                detalle: `${n?.cliente || '-'}`,
                fecha: n?.fecha || '',
                type: 'info',
                icon: 'checkroom',
            });
        });

        const next = Array.from(map.values()).slice(0, 50);
        savePushItems(next);
        return next;
    }

    async function marcarLeida(id) {
        const resp = await fetch(`/operario/api/notificaciones/recibos/${id}/leer`, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json',
                ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
            },
            credentials: 'same-origin',
            body: JSON.stringify({ tipo_recibo: tipoReciboNotificaciones }),
        });

        const data = await resp.json().catch(() => null);
        if (!resp.ok || !data?.success) {
            throw new Error(data?.message || 'Error marcando leída');
        }
    }

    async function marcarTodas() {
        const resp = await fetch('/operario/api/notificaciones/recibos/leer-todas', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json',
                ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
            },
            credentials: 'same-origin',
            body: JSON.stringify({ tipo_recibo: tipoReciboNotificaciones }),
        });

        const data = await resp.json().catch(() => null);
        if (!resp.ok || !data?.success) {
            throw new Error(data?.message || 'Error marcando todas');
        }
    }

    btn.addEventListener('click', async function (e) {
        e.stopPropagation();
        const isOpen = menu.classList.toggle('active');
        if (isOpen) {
            if (!isVistaCostura) {
                const serverItems = await fetchNotificaciones();
                const merged = mergeServerItemsIntoPush(serverItems);
                renderPushItems(merged);

                saveSince(new Date().toISOString());
            } else {
                renderPushItems(loadPushItems());
            }
        }
    });

    document.addEventListener('click', function (e) {
        if (!btn.contains(e.target) && !menu.contains(e.target)) {
            menu.classList.remove('active');
        }
    });

    list.addEventListener('click', async function (e) {
        const actionBtn = e.target?.closest('button[data-action="leer"]');
        if (!actionBtn) return;

        const item = e.target.closest('.notificacion-item');
        const id = item?.dataset?.id;
        if (!id) return;

        try {
            actionBtn.disabled = true;
            const current = loadPushItems();
            const next = current.filter((n) => String(n.id) !== String(id));
            savePushItems(next);

            const numericId = Number(id);
            if (Number.isFinite(numericId) && numericId > 0) {
                try {
                    await marcarLeida(numericId);
                } catch (err) {
                    console.warn('[Notificaciones] No se pudo marcar leída en BD', err);
                }
            }

            item.remove();

            const remaining = list.querySelectorAll('.notificacion-item').length;
            setBadgeCount(remaining);
            empty.style.display = remaining === 0 ? 'block' : 'none';
        } catch (err) {
            console.warn('[Notificaciones] Error marcar leída', err);
            actionBtn.disabled = false;
        }
    });

    markAllBtn.addEventListener('click', async function (e) {
        e.stopPropagation();
        try {
            markAllBtn.disabled = true;
            const items = loadPushItems();
            savePushItems([]);
            renderPushItems([]);

            const anyNumeric = (items || []).some((n) => Number.isFinite(Number(n?.id)) && Number(n?.id) > 0);
            if (anyNumeric) {
                try {
                    await marcarTodas();
                } catch (err) {
                    console.warn('[Notificaciones] No se pudo marcar todas en BD', err);
                }
            }
        } catch (err) {
            console.warn('[Notificaciones] Error marcar todas', err);
        } finally {
            markAllBtn.disabled = false;
        }
    });

    const items = loadPushItems();
    setBadgeCount(items.length);

    window.NotificacionesPush = {
        add: function (payload) {
            const current = loadPushItems();
            const id = payload?.id || Date.now() + '-' + Math.random().toString(16).slice(2);
            const exists = current.some((n) => String(n?.id) === String(id));
            if (exists) {
                return;
            }
            const next = [
                {
                    id,
                    titulo: payload?.title || payload?.titulo || 'Notificación',
                    detalle: payload?.message || payload?.detalle || '',
                    fecha: payload?.fecha || '',
                    type: payload?.type || 'info',
                    icon: payload?.icon || 'notifications',
                },
                ...current,
            ].slice(0, 50);

            savePushItems(next);
            setBadgeCount(next.length);
            if (menu.classList.contains('active')) {
                renderPushItems(next);
            }
        },
    };

    function hookRealtimeRefresh() {
        try {
            if (!window.EchoInstance || !window.OPERARIO_USUARIO?.id) return;

            window.EchoInstance.private(`App.Models.User.${window.OPERARIO_USUARIO.id}`).listen(
                '.operario.recibos.actualizados',
                () => {
                    renderPushItems(loadPushItems());
                }
            );

            window.EchoInstance.channel('operarios.corte').listen('.corte.asignado', (e) => {
                const encargadoEvento = String(e?.encargado || '').trim().toLowerCase();
                const nombreActual = String(window.OPERARIO_USUARIO?.nombre || '').trim().toLowerCase();
                if (encargadoEvento && nombreActual && encargadoEvento === nombreActual) {
                    renderPushItems(loadPushItems());
                }
            });
        } catch (e) {
            console.warn('[Notificaciones] No se pudo enganchar realtime', e);
        }
    }

    let tries = 0;
    (function waitEcho() {
        tries += 1;
        if (window.EchoInstance && window.OPERARIO_USUARIO?.id) {
            return hookRealtimeRefresh();
        }
        if (tries < 100) {
            return setTimeout(waitEcho, 200);
        }
    })();
}

function setupSearch() {
    const searchInput = document.getElementById('searchInput');

    if (!searchInput) return;

    searchInput.placeholder = 'Buscar por Consecutivo, Prenda o Cliente...';

    searchInput.addEventListener('input', function (e) {
        const busqueda = e.target.value.trim().toLowerCase();

        const ordenCards = document.querySelectorAll('.orden-card-simple');

        ordenCards.forEach((card) => {
            const numeroRecibo = String(card.dataset.numeroRecibo || '').toLowerCase();
            const clienteName = card.querySelector('.cliente-name')?.textContent?.toLowerCase().trim() || '';
            const nombrePrenda = String(card.dataset.prenda || '').toLowerCase();

            const coincide =
                !busqueda ||
                numeroRecibo.includes(busqueda) ||
                clienteName.includes(busqueda) ||
                nombrePrenda.includes(busqueda);

            card.style.display = coincide ? '' : 'none';
        });
    });
}

const layoutStyle = document.createElement('style');
layoutStyle.textContent = `
    .user-menu {
        display: none;
        position: absolute;
        top: 100%;
        right: 0;
        background: white;
        border-radius: 8px;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
        min-width: 250px;
        z-index: 1000;
        margin-top: 0.5rem;
    }

    .user-menu.active {
        display: block;
    }

    .user-dropdown {
        position: relative;
    }

    .notificaciones-dropdown {
        position: relative;
        margin-right: 10px;
    }

    .notificaciones-btn {
        position: relative;
        background: transparent;
        border: none;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 44px;
        height: 44px;
        border-radius: 12px;
        color: #2c3e50;
    }

    .notificaciones-btn:hover {
        background: rgba(0,0,0,0.06);
    }

    .notificaciones-badge {
        position: absolute;
        top: 4px;
        right: 4px;
        min-width: 18px;
        height: 18px;
        padding: 0 5px;
        background: #e74c3c;
        color: #fff;
        border-radius: 999px;
        font-size: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
    }

    .notificaciones-menu {
        display: none;
        position: absolute;
        top: 100%;
        right: 0;
        background: white;
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.18);
        width: 360px;
        max-width: 92vw;
        z-index: 1000;
        margin-top: 0.5rem;
        overflow: hidden;
    }

    @media (max-width: 768px) {
        .notificaciones-menu {
            width: 320px;
            max-width: 95vw;
            right: -10px;
            margin-top: 0.25rem;
        }

        .notificaciones-header {
            padding: 10px 12px;
        }

        .notificaciones-title {
            font-size: 14px;
        }

        .notificaciones-markall {
            font-size: 12px;
            padding: 4px 8px;
        }

        .notificaciones-list {
            max-height: 300px;
        }

        .notificacion-item {
            padding: 10px 12px;
            gap: 8px;
        }

        .notificacion-title {
            font-size: 13px;
            line-height: 1.3;
            word-wrap: break-word;
            word-break: break-word;
            hyphens: auto;
        }

        .notificacion-meta {
            font-size: 11px;
            margin-top: 3px;
            line-height: 1.3;
            word-wrap: break-word;
            word-break: break-word;
        }

        .notificacion-read {
            padding: 6px 8px;
            font-size: 11px;
            flex-shrink: 0;
            min-width: 70px;
        }
    }

    @media (max-width: 480px) {
        .notificaciones-menu {
            width: 280px;
            max-width: 98vw;
            right: -15px;
            left: auto;
        }

        .notificaciones-header {
            padding: 8px 10px;
        }

        .notificaciones-title {
            font-size: 13px;
        }

        .notificaciones-markall {
            font-size: 11px;
            padding: 3px 6px;
        }

        .notificaciones-list {
            max-height: 250px;
        }

        .notificacion-item {
            padding: 8px 10px;
            gap: 6px;
            flex-direction: column;
            align-items: stretch;
        }

        .notificacion-main {
            flex: 1;
            margin-bottom: 6px;
        }

        .notificacion-title {
            font-size: 12px;
            line-height: 1.4;
        }

        .notificacion-meta {
            font-size: 10px;
            margin-top: 2px;
        }

        .notificacion-read {
            padding: 6px 8px;
            font-size: 10px;
            width: 100%;
            text-align: center;
            margin-top: 4px;
        }
    }

    @media (max-width: 360px) {
        .notificaciones-menu {
            width: 260px;
            max-width: 99vw;
            right: -20px;
        }

        .notificaciones-title {
            font-size: 12px;
        }

        .notificacion-item {
            padding: 6px 8px;
        }

        .notificacion-title {
            font-size: 11px;
        }

        .notificacion-meta {
            font-size: 9px;
        }
    }

    .notificaciones-menu.active {
        display: block;
    }

    .notificaciones-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px 14px;
        border-bottom: 1px solid rgba(0,0,0,0.08);
    }

    .notificaciones-title {
        font-weight: 700;
        color: #2c3e50;
    }

    .notificaciones-markall {
        background: transparent;
        border: none;
        cursor: pointer;
        color: #3498db;
        font-weight: 600;
    }

    .notificaciones-list {
        max-height: 360px;
        overflow: auto;
    }

    .notificacion-item {
        display: flex;
        gap: 10px;
        align-items: center;
        justify-content: space-between;
        padding: 12px 14px;
        border-bottom: 1px solid rgba(0,0,0,0.06);
    }

    .notificacion-title {
        font-weight: 700;
        color: #2c3e50;
        font-size: 14px;
        line-height: 1.2;
    }

    .notificacion-meta {
        margin-top: 2px;
        font-size: 12px;
        color: rgba(44, 62, 80, 0.72);
    }

    .notificacion-read {
        background: rgba(46, 204, 113, 0.14);
        border: 1px solid rgba(46, 204, 113, 0.32);
        color: #27ae60;
        border-radius: 10px;
        padding: 8px 10px;
        cursor: pointer;
        font-weight: 700;
        font-size: 12px;
        white-space: nowrap;
    }

    .notificacion-read:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    .notificaciones-empty {
        padding: 14px;
        color: rgba(44, 62, 80, 0.7);
    }
`;

document.head.appendChild(layoutStyle);
