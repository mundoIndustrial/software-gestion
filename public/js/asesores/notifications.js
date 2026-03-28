// ========================================
// NOTIFICATIONS SYSTEM
// ========================================

let lastMarkAllReadTime = 0;
let asesoresRealtimeNotificationsBound = false;

document.addEventListener('DOMContentLoaded', function() {
    // Verificar que fetchAPI esté disponible
    if (typeof window.fetchAPI !== 'function') {

        setTimeout(initializeNotifications, 100);
        return;
    }
    initializeNotifications();
});

function initializeNotifications() {
    loadNotifications();
    setupRealtimeNotifications();

    // Marcar todas como leídas
    const markAllReadBtn = document.querySelector('.mark-all-read');
    if (markAllReadBtn) {
        markAllReadBtn.addEventListener('click', markAllAsRead);
    }
}


function setupRealtimeNotifications() {
    if (asesoresRealtimeNotificationsBound) {
        return;
    }

    if (!window.shared?.isReady || typeof window.waitForEcho !== 'function') {
        setTimeout(setupRealtimeNotifications, 300);
        return;
    }

    const currentUserId = Number(document.querySelector('meta[name="user-id"]')?.content || 0);
    const refreshNotifications = () => {
        const timeSinceMarkAllRead = Date.now() - lastMarkAllReadTime;
        if (timeSinceMarkAllRead < 1500) {
            return;
        }
        loadNotifications();
    };

    window.waitForEcho(() => {
        try {
            const ws = window.shared?.websocket;
            if (!ws) {
                setTimeout(setupRealtimeNotifications, 500);
                return;
            }

            ws.subscribe('notifications', '.new-notification', (data) => {
                if (data.exclude_user_id && currentUserId && Number(data.exclude_user_id) === currentUserId) {
                    return;
                }
                refreshNotifications();
            });

            ws.subscribe('notifications', '.notifications-marked-read', (data) => {
                if (!data.user_id || Number(data.user_id) === currentUserId) {
                    refreshNotifications();
                }
            });

            ws.subscribe('cotizaciones', '.cotizacion.creada', refreshNotifications);
            ws.subscribe('cotizaciones', '.cotizacion.estado.cambiado', refreshNotifications);

            if (currentUserId) {
                ws.subscribe(`cotizaciones.asesor.${currentUserId}`, '.cotizacion.creada', refreshNotifications);
                ws.subscribe(`cotizaciones.asesor.${currentUserId}`, '.cotizacion.estado.cambiado', refreshNotifications);
            }

            asesoresRealtimeNotificationsBound = true;
        } catch (_) {
            setTimeout(setupRealtimeNotifications, 500);
        }
    });
}

async function loadNotifications() {
    try {
        const data = await window.fetchAPI('/api/asesores/notificaciones');
        updateNotificationBadge(data.total_notificaciones);
        renderNotifications(data);
    } catch (error) {
        // Ignorar error 401 (no autenticado)
        if (error.message && error.message.includes('Unauthenticated')) {

            return;
        }

    }
}

function updateNotificationBadge(count) {
    const badge = document.getElementById('notificationBadge');
    if (badge) {
        badge.textContent = count;
        badge.style.display = count > 0 ? 'block' : 'none';
    }
}

function renderNotifications(data) {
    const notificationList = document.getElementById('notificationList');
    if (!notificationList) return;
    
    // Limpiar lista
    notificationList.innerHTML = '';
    
    const notifications = [];
    
    // ============================================
    // NUEVA SECCIÓN: Fecha Estimada de Entrega
    // ============================================
    if (data.notificaciones_fecha_estimada && data.notificaciones_fecha_estimada.length > 0) {
        data.notificaciones_fecha_estimada.forEach(notif => {
            const fecha = new Date(notif.created_at);
            const horasTranscurridas = Math.floor((new Date() - fecha) / (1000 * 60 * 60));
            let tiempoTranscurrido = '';
            
            if (horasTranscurridas < 1) {
                const minutosTranscurridos = Math.floor((new Date() - fecha) / (1000 * 60));
                tiempoTranscurrido = `${minutosTranscurridos} min`;
            } else if (horasTranscurridas < 24) {
                tiempoTranscurrido = `${horasTranscurridas} hora${horasTranscurridas !== 1 ? 's' : ''}`;
            } else {
                const diasTranscurridos = Math.floor(horasTranscurridas / 24);
                tiempoTranscurrido = `${diasTranscurridos} día${diasTranscurridos !== 1 ? 's' : ''}`;
            }
            
            notifications.push({
                id: notif.id,
                icon: 'fa-calendar-check',
                color: '#3b82f6',
                title: notif.titulo,
                message: `${notif.numero_pedido} - ${notif.cliente} | Fecha: ${notif.fecha_estimada}`,
                time: `Hace ${tiempoTranscurrido}`,
                link: `#`,
                tipo: 'fecha_estimada',
                isNew: true
            });
        });
    }
    
    // ============================================
    // NUEVO: Pedidos/Cotizaciones de OTROS asesores
    // ============================================
    // Agregar notificaciones de otros asesores
    if (data.pedidos_otros_asesores && data.pedidos_otros_asesores.length > 0) {
        data.pedidos_otros_asesores.forEach(pedido => {
            const fecha = new Date(pedido.created_at);
            const horasTranscurridas = Math.floor((new Date() - fecha) / (1000 * 60 * 60));
            let tiempoTranscurrido = '';
            
            if (horasTranscurridas < 1) {
                const minutosTranscurridos = Math.floor((new Date() - fecha) / (1000 * 60));
                tiempoTranscurrido = `${minutosTranscurridos} min`;
            } else if (horasTranscurridas < 24) {
                tiempoTranscurrido = `${horasTranscurridas} hora${horasTranscurridas !== 1 ? 's' : ''}`;
            } else {
                const diasTranscurridos = Math.floor(horasTranscurridas / 24);
                tiempoTranscurrido = `${diasTranscurridos} día${diasTranscurridos !== 1 ? 's' : ''}`;
            }
            
            notifications.push({
                icon: 'fa-shopping-cart',
                color: '#10b981',
                title: `${pedido.asesor_nombre} - COT-${String(pedido.numero_cotizacion).padStart(5, '0')}`,
                message: `PED-${String(pedido.numero_pedido).padStart(5, '0')} - ${pedido.cliente}`,
                time: `Hace ${tiempoTranscurrido}`,
                link: `#`
            });
        });
    }

    
    // Renderizar notificaciones
    if (notifications.length === 0) {
        notificationList.innerHTML = `
            <div class="notification-empty">
                <i class="fas fa-bell-slash"></i>
                <p>Sin novedad en otros asesores</p>
            </div>
        `;
    } else {
        notifications.forEach(notif => {
            const notifElement = createNotificationElement(notif);
            notificationList.appendChild(notifElement);
        });
    }
}

function createNotificationElement(notif) {
    function sanitizeColor(color) {
        return /^#([0-9a-f]{3}|[0-9a-f]{6})$/i.test(String(color || '')) ? color : '#6b7280';
    }

    function sanitizeIcon(iconClass) {
        return /^fa-[a-z0-9-]+$/i.test(String(iconClass || '')) ? iconClass : 'fa-bell';
    }

    const div = document.createElement('a');
    div.href = notif.link;
    div.className = 'notification-item';
    div.dataset.notificationId = notif.id;
    div.style.cssText = `
        display: flex;
        align-items: flex-start;
        gap: 1rem;
        padding: 1rem 1.25rem;
        text-decoration: none;
        color: var(--text-primary);
        border-bottom: 1px solid var(--border-color);
        transition: background 0.2s ease;
        ${notif.isNew ? 'background: rgba(59, 130, 246, 0.05);' : ''}
    `;

    const safeColor = sanitizeColor(notif.color);
    const safeIcon = sanitizeIcon(notif.icon);

    const iconWrapper = document.createElement('div');
    iconWrapper.style.cssText = `
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: ${safeColor}20;
        color: ${safeColor};
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    `;

    const icon = document.createElement('i');
    icon.className = `fas ${safeIcon}`;
    iconWrapper.appendChild(icon);

    const contentWrapper = document.createElement('div');
    contentWrapper.style.cssText = 'flex: 1; min-width: 0;';

    const title = document.createElement('div');
    title.style.cssText = `
        font-weight: 600;
        font-size: 0.875rem;
        margin-bottom: 0.25rem;
        color: var(--text-primary);
    `;
    title.textContent = notif.title || '';

    const message = document.createElement('div');
    message.style.cssText = `
        font-size: 0.875rem;
        color: var(--text-secondary);
        margin-bottom: 0.25rem;
    `;
    message.textContent = notif.message || '';

    const time = document.createElement('div');
    time.style.cssText = `
        font-size: 0.75rem;
        color: var(--text-tertiary);
    `;
    time.textContent = notif.time || '';

    contentWrapper.appendChild(title);
    contentWrapper.appendChild(message);
    contentWrapper.appendChild(time);

    div.appendChild(iconWrapper);
    div.appendChild(contentWrapper);
    
    // Si es notificación de fecha estimada, marcar como leída al hacer click
    if (notif.tipo === 'fecha_estimada' && notif.id) {
        div.addEventListener('click', async function(e) {
            e.preventDefault();
            await markNotificationAsRead(notif.id);
            // Recargar notificaciones
            loadNotifications();
        });
    }
    
    div.addEventListener('mouseenter', function() {
        this.style.background = 'var(--bg-hover)';
    });
    
    div.addEventListener('mouseleave', function() {
        if (!notif.isNew) {
            this.style.background = 'transparent';
        }
    });
    
    return div;
}

async function markAllAsRead() {
    try {
        await window.fetchAPI('/api/asesores/notificaciones/marcar-todas-leidas', {
            method: 'POST'
        });
        
        // Registrar el tiempo de marca como leídas
        lastMarkAllReadTime = Date.now();
        
        updateNotificationBadge(0);
        
        // Limpiar la lista de notificaciones
        const notificationList = document.getElementById('notificationList');
        if (notificationList) {
            notificationList.innerHTML = `
                <div class="notification-empty">
                    <i class="fas fa-bell-slash"></i>
                    <p>No tienes notificaciones</p>
                </div>
            `;
        }
        
        // Cerrar el dropdown después de marcar como leídas
        const notificationMenu = document.getElementById('notificationMenu');
        if (notificationMenu) {
            notificationMenu.classList.remove('show');
        }
        
        showToast('Notificaciones marcadas como leídas', 'success');
    } catch (error) {

        showToast('Error al marcar notificaciones', 'error');
    }
}

/**
 * Marcar una notificación específica como leída
 */
async function markNotificationAsRead(notificationId) {
    try {
        await window.fetchAPI(`/api/asesores/notificaciones/${notificationId}/marcar-leida`, {
            method: 'POST'
        });

    } catch (error) {

    }
}
