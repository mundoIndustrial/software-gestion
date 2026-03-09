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
            const resp = await fetch('/operario/api/notificaciones/recibos?tipo_recibo=COSTURA&limit=50', {
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
            body: JSON.stringify({ tipo_recibo: 'COSTURA' })
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
            body: JSON.stringify({ tipo_recibo: 'COSTURA' })
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
            await fetchNotificaciones();
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
            await marcarLeida(id);
            item.remove();

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
            await marcarTodas();
            renderItems([]);
        } catch (err) {
            console.warn('[Notificaciones] Error marcar todas', err);
        } finally {
            markAllBtn.disabled = false;
        }
    });

    // Carga inicial del badge (sin abrir)
    fetchNotificaciones();

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
const style = document.createElement('style');
style.textContent = `
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
document.head.appendChild(style);
