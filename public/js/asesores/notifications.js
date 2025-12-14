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
    
    // ============================================
    // ANTERIOR: Órdenes próximas a vencer (propias)
    // ============================================
    // Agregar órdenes propias próximas a vencer
    if (data.pedidos_proximos_entregar && data.pedidos_proximos_entregar.length > 0) {
        data.pedidos_proximos_entregar.forEach(orden => {
            const diasRestantes = Math.ceil((new Date(orden.fecha_estimada_de_entrega) - new Date()) / (1000 * 60 * 60 * 24));
            notifications.push({
                icon: 'fa-clock',
                color: '#3b82f6',
                title: 'Tu orden próxima a vencer',
                message: `${orden.numero_pedido} - ${orden.cliente}`,
                time: `Vence en ${diasRestantes} día${diasRestantes !== 1 ? 's' : ''}`,
                link: `/asesores/ordenes/${orden.id}`
            });
        });
    }
    
    // Agregar órdenes urgentes (propias)
    if (data.pedidos_en_ejecucion > 0) {
        notifications.push({
            icon: 'fa-exclamation-triangle',
            color: '#ef4444',
            title: 'Órdenes en ejecución',
            message: `Tienes ${data.pedidos_en_ejecucion} orden${data.pedidos_en_ejecucion !== 1 ? 'es' : ''} en ejecución`,
            time: 'Requiere atención',
            link: '/asesores/ordenes?estado=En%20Ejecucion'
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

