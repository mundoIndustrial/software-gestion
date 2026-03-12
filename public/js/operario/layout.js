/**
 * Script: layout.js
 * Gestiona interactividad del layout de operarios
 */

document.addEventListener('DOMContentLoaded', function() {
    setupUserDropdown();
    setupNotificaciones();
    setupSearch();
});

/**
 * Configurar dropdown de usuario
 */
function setupUserDropdown() {
    const userBtn = document.getElementById('userBtn');
    const userMenu = document.getElementById('userMenu');

    if (!userBtn || !userMenu) return;

    userBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        userMenu.classList.toggle('active');
    });

    document.addEventListener('click', function(e) {
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
    const storageKey = 'vista_costura_push_notificaciones';

    const tipoReciboNotificaciones = isCosturaReflectivo ? 'REFLECTIVO' : 'COSTURA';

    function loadPushItems() {
        if (!isVistaCostura) return [];
        try {
            const raw = localStorage.getItem(storageKey);
            const data = raw ? JSON.parse(raw) : [];
            return Array.isArray(data) ? data : [];
        } catch {
            return [];
        }
    }

    function savePushItems(items) {
        if (!isVistaCostura) return;
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
            const resp = await fetch(`/operario/api/notificaciones/recibos?tipo_recibo=${encodeURIComponent(tipoReciboNotificaciones)}&limit=50`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            });

            const data = await resp.json();
            if (!resp.ok || !data?.success) {
                throw new Error(data?.message || 'Error listando notificaciones');
            }

            renderItems(data.notificaciones || []);
        } catch (e) {
            console.warn('[Notificaciones] Error cargando notificaciones', e);
        }
    }

    async function marcarLeida(id) {
        const resp = await fetch(`/operario/api/notificaciones/recibos/${id}/leer`, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json',
                ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {})
            },
            credentials: 'same-origin',
            body: JSON.stringify({ tipo_recibo: tipoReciboNotificaciones })
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
                ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {})
            },
            credentials: 'same-origin',
            body: JSON.stringify({ tipo_recibo: tipoReciboNotificaciones })
        });

        const data = await resp.json().catch(() => null);
        if (!resp.ok || !data?.success) {
            throw new Error(data?.message || 'Error marcando todas');
        }
    }

    btn.addEventListener('click', async function(e) {
        e.stopPropagation();
        const isOpen = menu.classList.toggle('active');
        if (isOpen) {
            if (isVistaCostura) {
                renderPushItems(loadPushItems());
            } else {
                await fetchNotificaciones();
            }
        }
    });

    document.addEventListener('click', function(e) {
        if (!btn.contains(e.target) && !menu.contains(e.target)) {
            menu.classList.remove('active');
        }
    });

    list.addEventListener('click', async function(e) {
        const actionBtn = e.target?.closest('button[data-action="leer"]');
        if (!actionBtn) return;

        const item = e.target.closest('.notificacion-item');
        const id = item?.dataset?.id;
        if (!id) return;

        try {
            actionBtn.disabled = true;
            if (isVistaCostura || item?.dataset?.source === 'push') {
                const current = loadPushItems();
                const next = current.filter((n) => String(n.id) !== String(id));
                savePushItems(next);
                item.remove();
            } else {
                await marcarLeida(id);
                item.remove();
            }

            const remaining = list.querySelectorAll('.notificacion-item').length;
            setBadgeCount(remaining);
            empty.style.display = remaining === 0 ? 'block' : 'none';
        } catch (err) {
            console.warn('[Notificaciones] Error marcar leída', err);
            actionBtn.disabled = false;
        }
    });

    markAllBtn.addEventListener('click', async function(e) {
        e.stopPropagation();
        try {
            markAllBtn.disabled = true;
            if (isVistaCostura) {
                savePushItems([]);
                renderPushItems([]);
            } else {
                await marcarTodas();
                renderItems([]);
            }
        } catch (err) {
            console.warn('[Notificaciones] Error marcar todas', err);
        } finally {
            markAllBtn.disabled = false;
        }
    });

    // Carga inicial del badge (sin abrir)
    if (isVistaCostura) {
        const items = loadPushItems();
        setBadgeCount(items.length);
    } else {
        fetchNotificaciones();
    }

    // Inicializar NotificacionesPush para todos los roles (no solo vista-costura)
    window.NotificacionesPush = {
        add: function(payload) {
            const current = loadPushItems();
            const id = payload?.id || (Date.now() + '-' + Math.random().toString(16).slice(2));
            const next = [
                {
                    id,
                    titulo: payload?.title || payload?.titulo || 'Notificación',
                    detalle: payload?.message || payload?.detalle || '',
                    fecha: payload?.fecha || '',
                    type: payload?.type || 'info',
                    icon: payload?.icon || 'notifications'
                },
                ...current,
            ].slice(0, 50);

            savePushItems(next);
            setBadgeCount(next.length);
            if (menu.classList.contains('active')) {
                renderPushItems(next);
            }
        }
    };

    // Realtime: refrescar notificaciones cuando llegue un evento de asignación
    // Se engancha a los mismos eventos ya usados en el dashboard.
    function hookRealtimeRefresh() {
        try {
            if (!window.EchoInstance || !window.OPERARIO_USUARIO?.id) return;

            window.EchoInstance.private(`App.Models.User.${window.OPERARIO_USUARIO.id}`)
                .listen('.operario.recibos.actualizados', () => {
                    fetchNotificaciones();
                });

            window.EchoInstance.channel('operarios.corte')
                .listen('.corte.asignado', (e) => {
                    const encargadoEvento = String(e?.encargado || '').trim().toLowerCase();
                    const nombreActual = String(window.OPERARIO_USUARIO?.nombre || '').trim().toLowerCase();
                    if (encargadoEvento && nombreActual && encargadoEvento === nombreActual) {
                        fetchNotificaciones();
                    }
                });
        } catch (e) {
            console.warn('[Notificaciones] No se pudo enganchar realtime', e);
        }
    }

    // Esperar un poco a EchoInstance (Vite/Echo carga async)
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

/**
 * Configurar búsqueda de pedidos
 */
function setupSearch() {
    const searchInput = document.getElementById('searchInput');

    if (!searchInput) return;

    // Actualizar placeholder
    searchInput.placeholder = 'Buscar por # Recibo o Cliente...';

    searchInput.addEventListener('input', function(e) {
        const busqueda = e.target.value.trim().toLowerCase();

        // Obtener todas las tarjetas de orden
        const ordenCards = document.querySelectorAll('.orden-card-simple');

        ordenCards.forEach(card => {
            // Obtener número de RECIBO desde .orden-right (lado derecho)
            // Buscar el texto que está después de "RECIBO"
            const reciboElem = card.querySelector('.orden-right .orden-fecha span:not(.orden-fecha-label)');
            const numeroRecibo = reciboElem ? reciboElem.textContent?.toLowerCase().trim() : '';
            
            // Obtener nombre del cliente
            const clienteName = card.querySelector('.cliente-name')?.textContent?.toLowerCase().trim() || '';

            console.log('🔍 Filtro:', {
                busqueda: busqueda,
                numeroRecibo: numeroRecibo,
                clienteName: clienteName,
                coincide: !busqueda || numeroRecibo.includes(busqueda) || clienteName.includes(busqueda)
            });

            // Mostrar si coincide con recibo o cliente (búsqueda vacía muestra todos)
            const coincide = !busqueda || 
                             numeroRecibo.includes(busqueda) || 
                             clienteName.includes(busqueda);

            card.style.display = coincide ? '' : 'none';
        });
    });
}

/**
 * Buscar pedidos (función deprecated - ahora el filtro es client-side)
 */
function buscarPedidos(busqueda) {
    // Ya no se usa - el filtro es client-side más eficiente
    console.log('[BUSCAR] Búsqueda client-side:', busqueda);
}

/**
 * Agregar estilos dinámicos para dropdown
 */
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

    /* Mejoras para mobile */
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
