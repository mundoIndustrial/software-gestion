// ========================================
// NOTIFICATIONS SYSTEM
// ========================================
let lastMarkAllReadTime = 0; // Timestamp de última vez que se marcaron todas como leídas

document.addEventListener('DOMContentLoaded', function() {
    // Verificar que fetchAPI esté disponible
    if (typeof window.fetchAPI !== 'function') {
        console.warn('fetchAPI no está disponible aún, retrasando carga de notificaciones');
        setTimeout(initializeNotifications, 100);
        return;
    }
    initializeNotifications();
});

function initializeNotifications() {
    loadNotifications();
    
    // Actualizar notificaciones cada 30 segundos
    // PERO: Si hace poco marcamos todas como leídas, esperar más tiempo
    setInterval(() => {
        const timeSinceMarkAllRead = Date.now() - lastMarkAllReadTime;
        // Si pasaron menos de 2 minutos desde que marcamos todas, esperar 60 segundos más
        if (timeSinceMarkAllRead < 120000) {
            console.debug('Esperando antes de recargar notificaciones...');
            return;
        }
        loadNotifications();
    }, 30000);
    
    // Marcar todas como leídas
    const markAllReadBtn = document.querySelector('.mark-all-read');
    if (markAllReadBtn) {
        markAllReadBtn.addEventListener('click', markAllAsRead);
    }
}

async function loadNotifications() {
    try {
        const data = await window.fetchAPI('/asesores/notifications');
        updateNotificationBadge(data.total_notificaciones);
        renderNotifications(data);
    } catch (error) {
        // Ignorar error 401 (no autenticado)
        if (error.message && error.message.includes('Unauthenticated')) {
            console.debug('Usuario no autenticado, notificaciones deshabilitadas');
            return;
        }
        console.error('Error cargando notificaciones:', error);
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
    
    // Agregar órdenes próximas a vencer
    if (data.ordenes_proximas_vencer && data.ordenes_proximas_vencer.length > 0) {
        data.ordenes_proximas_vencer.forEach(orden => {
            const diasRestantes = Math.ceil((new Date(orden.fecha_entrega) - new Date()) / (1000 * 60 * 60 * 24));
            notifications.push({
                icon: 'fa-clock',
                color: '#3b82f6',
                title: 'Orden próxima a vencer',
                message: `${orden.numero_orden} - ${orden.cliente}`,
                time: `Vence en ${diasRestantes} día${diasRestantes !== 1 ? 's' : ''}`,
                link: `/asesores/ordenes/${orden.id}`
            });
        });
    }
    
    // Agregar órdenes urgentes
    if (data.ordenes_urgentes > 0) {
        notifications.push({
            icon: 'fa-exclamation-triangle',
            color: '#ef4444',
            title: 'Órdenes urgentes pendientes',
            message: `Tienes ${data.ordenes_urgentes} orden${data.ordenes_urgentes !== 1 ? 'es' : ''} urgente${data.ordenes_urgentes !== 1 ? 's' : ''} pendiente${data.ordenes_urgentes !== 1 ? 's' : ''}`,
            time: 'Requiere atención',
            link: '/asesores/ordenes?estado=pendiente&prioridad=urgente'
        });
    }
    
    // Renderizar notificaciones
    if (notifications.length === 0) {
        notificationList.innerHTML = `
            <div class="notification-empty">
                <i class="fas fa-bell-slash"></i>
                <p>No tienes notificaciones</p>
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
    const div = document.createElement('a');
    div.href = notif.link;
    div.className = 'notification-item';
    div.style.cssText = `
        display: flex;
        align-items: flex-start;
        gap: 1rem;
        padding: 1rem 1.25rem;
        text-decoration: none;
        color: var(--text-primary);
        border-bottom: 1px solid var(--border-color);
        transition: background 0.2s ease;
    `;
    
    div.innerHTML = `
        <div style="
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: ${notif.color}20;
            color: ${notif.color};
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        ">
            <i class="fas ${notif.icon}"></i>
        </div>
        <div style="flex: 1; min-width: 0;">
            <div style="
                font-weight: 600;
                font-size: 0.875rem;
                margin-bottom: 0.25rem;
                color: var(--text-primary);
            ">${notif.title}</div>
            <div style="
                font-size: 0.875rem;
                color: var(--text-secondary);
                margin-bottom: 0.25rem;
            ">${notif.message}</div>
            <div style="
                font-size: 0.75rem;
                color: var(--text-tertiary);
            ">${notif.time}</div>
        </div>
    `;
    
    div.addEventListener('mouseenter', function() {
        this.style.background = 'var(--bg-hover)';
    });
    
    div.addEventListener('mouseleave', function() {
        this.style.background = 'transparent';
    });
    
    return div;
}

async function markAllAsRead() {
    try {
        await window.fetchAPI('/asesores/notifications/mark-all-read', {
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
        console.error('Error marcando notificaciones:', error);
        showToast('Error al marcar notificaciones', 'error');
    }
}

