// ========================================
// NOTIFICATIONS SYSTEM
// ========================================
document.addEventListener('DOMContentLoaded', function() {
    // Sistema de notificaciones deshabilitado para rol Insumos
    console.debug('Notificaciones deshabilitadas en rol Insumos');
});

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
    
    // Agregar ├│rdenes pr├│ximas a vencer
    if (data.ordenes_proximas_vencer && data.ordenes_proximas_vencer.length > 0) {
        data.ordenes_proximas_vencer.forEach(orden => {
            const diasRestantes = Math.ceil((new Date(orden.fecha_entrega) - new Date()) / (1000 * 60 * 60 * 24));
            notifications.push({
                icon: 'fa-clock',
                color: '#3b82f6',
                title: 'Orden pr├│xima a vencer',
                message: `${orden.numero_orden} - ${orden.cliente}`,
                time: `Vence en ${diasRestantes} d├¡a${diasRestantes !== 1 ? 's' : ''}`,
                link: `/asesores/ordenes/${orden.id}`
            });
        });
    }
    
    // Agregar ├│rdenes urgentes
    if (data.ordenes_urgentes > 0) {
        notifications.push({
            icon: 'fa-exclamation-triangle',
            color: '#ef4444',
            title: '├ôrdenes urgentes pendientes',
            message: `Tienes ${data.ordenes_urgentes} orden${data.ordenes_urgentes !== 1 ? 'es' : ''} urgente${data.ordenes_urgentes !== 1 ? 's' : ''} pendiente${data.ordenes_urgentes !== 1 ? 's' : ''}`,
            time: 'Requiere atenci├│n',
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
        await fetchAPI('/asesores/notifications/mark-all-read', {
            method: 'POST'
        });
        
        updateNotificationBadge(0);
        showToast('Notificaciones marcadas como le├¡das', 'success');
    } catch (error) {
        console.error('Error marcando notificaciones:', error);
        showToast('Error al marcar notificaciones', 'error');
    }
}
