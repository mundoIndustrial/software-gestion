// ========================================
// NOTIFICATIONS SYSTEM - CONTADOR
// ========================================
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
    // Cargar notificaciones iniciales
    loadNotifications();
    
    // Actualizar notificaciones cada 30 segundos
    setInterval(loadNotifications, 30000);
    
    // Toggle del dropdown de notificaciones
    const notificationBtn = document.getElementById('notificationBtn');
    const notificationMenu = document.getElementById('notificationMenu');
    
    if (notificationBtn && notificationMenu) {
        notificationBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            notificationMenu.classList.toggle('show');
            if (document.getElementById('userMenu')) {
                document.getElementById('userMenu').classList.remove('show');
            }
        });
    }
    
    // Toggle del dropdown de usuario
    const userBtn = document.getElementById('userBtn');
    const userMenu = document.getElementById('userMenu');
    
    if (userBtn && userMenu) {
        userBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            userMenu.classList.toggle('show');
            if (notificationMenu) {
                notificationMenu.classList.remove('show');
            }
        });
    }
    
    // Cerrar dropdowns al hacer clic afuera
    document.addEventListener('click', function() {
        if (notificationMenu) {
            notificationMenu.classList.remove('show');
        }
        if (userMenu) {
            userMenu.classList.remove('show');
        }
    });
    
    // Marcar todas como leídas
    const markAllReadBtn = document.querySelector('.mark-all-read');
    if (markAllReadBtn) {
        markAllReadBtn.addEventListener('click', markAllAsRead);
    }
}

async function loadNotifications() {
    try {
        const data = await window.fetchAPI('/contador/notifications');
        updateNotificationBadge(data.total_notificaciones);
        renderNotifications(data);
    } catch (error) {
        // Ignorar error 401 (no autenticado)
        if (error.message && error.message.includes('Unauthenticated')) {
            console.debug('Usuario no autenticado, notificaciones deshabilitadas');
            return;
        }
        console.debug('Error cargando notificaciones:', error.message);
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
    
    // Agregar cotizaciones enviadas a revisar
    if (data.cotizaciones_para_revisar && data.cotizaciones_para_revisar.length > 0) {
        data.cotizaciones_para_revisar.forEach(cot => {
            notifications.push({
                icon: 'description',
                color: '#3b82f6',
                title: 'Cotización para revisar',
                message: `COT-${String(cot.id).padStart(5, '0')} - ${cot.cliente}`,
                time: formatTime(cot.created_at),
                link: `/contador/dashboard`
            });
        });
    }
    
    // Agregar nuevas cotizaciones creadas
    if (data.nuevas_cotizaciones && data.nuevas_cotizaciones.length > 0) {
        data.nuevas_cotizaciones.forEach(cot => {
            notifications.push({
                icon: 'note_add',
                color: '#10b981',
                title: 'Nueva cotización creada',
                message: `COT-${String(cot.id).padStart(5, '0')} - ${cot.cliente}`,
                time: formatTime(cot.created_at),
                link: `/contador/dashboard`
            });
        });
    }
    
    // Renderizar notificaciones
    if (notifications.length === 0) {
        notificationList.innerHTML = `
            <div class="notification-empty">
                <span class="material-symbols-rounded">notifications_off</span>
                <p>Sin notificaciones</p>
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
            <span class="material-symbols-rounded">${notif.icon}</span>
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

function formatTime(date) {
    const now = new Date();
    const notifDate = new Date(date);
    const diff = now - notifDate;
    
    const seconds = Math.floor(diff / 1000);
    const minutes = Math.floor(seconds / 60);
    const hours = Math.floor(minutes / 60);
    const days = Math.floor(hours / 24);
    
    if (seconds < 60) {
        return 'Hace un momento';
    } else if (minutes < 60) {
        return `Hace ${minutes} minuto${minutes !== 1 ? 's' : ''}`;
    } else if (hours < 24) {
        return `Hace ${hours} hora${hours !== 1 ? 's' : ''}`;
    } else if (days < 7) {
        return `Hace ${days} día${days !== 1 ? 's' : ''}`;
    } else {
        return notifDate.toLocaleDateString('es-ES');
    }
}

async function markAllAsRead() {
    try {
        await window.fetchAPI('/contador/notifications/marcar-leidas', {
            method: 'POST'
        });
        loadNotifications();
    } catch (error) {
        console.error('Error al marcar notificaciones como leídas:', error);
    }
}
