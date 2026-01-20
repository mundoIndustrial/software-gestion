// ========================================
// REAL-TIME NOTIFICATIONS SYSTEM
// ========================================

let notificationChannel = null;
let currentUserId = null;
let modalOpenTime = null;
let autoMarkOnCloseEnabled = true;

// Inicializar sistema de notificaciones en tiempo real
document.addEventListener('DOMContentLoaded', function() {
    initializeRealtimeNotifications();
});

function initializeRealtimeNotifications() {
    // Obtener user ID del meta tag
    const userIdMeta = document.querySelector('meta[name="user-id"]');
    if (userIdMeta) {
        currentUserId = parseInt(userIdMeta.content);
    }

    // Cargar notificaciones iniciales
    loadNotifications();
    
    // Configurar Laravel Echo para notificaciones en tiempo real
    setupEchoListener();
    
    // Configurar listeners del UI
    setupUIListeners();
    
    // Actualizar contador cada 60 segundos (backup por si falla websocket)
    setInterval(() => {
        updateUnreadCount();
    }, 60000);
}

// Configurar Laravel Echo para escuchar eventos en tiempo real
function setupEchoListener() {
    if (typeof Echo === 'undefined') {
        console.warn('Laravel Echo no está disponible. Las notificaciones en tiempo real no funcionarán.');
        return;
    }

    try {
        // Escuchar canal público de notificaciones
        notificationChannel = Echo.channel('notifications');
        
        // Evento: Nueva notificación
        notificationChannel.listen('.new-notification', (data) => {
            console.log('📬 Nueva notificación recibida:', data);
            
            // No mostrar notificaciones del usuario actual
            if (data.exclude_user_id && currentUserId && data.exclude_user_id === currentUserId) {
                console.log('🚫 Notificación del usuario actual, ignorando');
                return;
            }
            
            // Agregar notificación al UI
            addNotificationToUI(data);
            
            // Actualizar contador
            updateUnreadCount();
            
            // Mostrar notificación toast (opcional)
            showNotificationToast(data);
        });
        
        // Evento: Notificaciones marcadas como leídas
        notificationChannel.listen('.notifications-marked-read', (data) => {
            console.log(' Notificaciones marcadas como leídas:', data);
            
            // Si es del usuario actual, actualizar UI
            if (data.user_id === currentUserId) {
                updateUnreadCount();
            }
        });
        
        console.log(' Laravel Echo configurado correctamente');
    } catch (error) {
        console.error(' Error configurando Laravel Echo:', error);
    }
}

// Configurar listeners del UI
function setupUIListeners() {
    const notificationBtn = document.getElementById('notificationBtn');
    const notificationMenu = document.getElementById('notificationMenu');
    const markAllReadBtn = document.querySelector('.mark-all-read');
    
    // Toggle del dropdown
    if (notificationBtn && notificationMenu) {
        notificationBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const isShowing = notificationMenu.classList.contains('show');
            
            if (isShowing) {
                closeNotificationModal();
            } else {
                openNotificationModal();
            }
        });
    }
    
    // Marcar todas como leídas
    if (markAllReadBtn) {
        markAllReadBtn.addEventListener('click', markAllAsRead);
    }
    
    // Cerrar al hacer clic fuera
    document.addEventListener('click', function(e) {
        if (notificationMenu && !notificationMenu.contains(e.target) && !notificationBtn.contains(e.target)) {
            if (notificationMenu.classList.contains('show')) {
                closeNotificationModal();
            }
        }
    });
}

// Abrir modal de notificaciones
async function openNotificationModal() {
    const notificationMenu = document.getElementById('notificationMenu');
    if (!notificationMenu) return;
    
    // Cerrar menú de usuario si está abierto
    const userMenu = document.getElementById('userMenu');
    if (userMenu) {
        userMenu.classList.remove('show');
    }
    
    // Abrir modal
    notificationMenu.classList.add('show');
    modalOpenTime = Date.now();
    
    // Cargar notificaciones
    await loadNotifications();
}

// Cerrar modal de notificaciones
async function closeNotificationModal() {
    const notificationMenu = document.getElementById('notificationMenu');
    if (!notificationMenu) return;
    
    notificationMenu.classList.remove('show');
    
    // Si el modal estuvo abierto y hay notificaciones no leídas, marcarlas como leídas
    if (autoMarkOnCloseEnabled && modalOpenTime) {
        const timeOpen = Date.now() - modalOpenTime;
        
        // Solo marcar si el modal estuvo abierto al menos 2 segundos
        if (timeOpen >= 2000) {
            await markAsReadOnClose();
        }
    }
    
    modalOpenTime = null;
}

// Cargar notificaciones
async function loadNotifications() {
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        
        const response = await fetch('/notifications?limit=50&days=7', {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            }
        });
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }
        
        const data = await response.json();
        
        // Actualizar badge
        updateNotificationBadge(data.unread_count || 0);
        
        // Renderizar notificaciones
        renderNotifications(data.notifications || []);
        
    } catch (error) {
        console.error('Error cargando notificaciones:', error);
    }
}

// Actualizar contador de no leídas
async function updateUnreadCount() {
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        
        const response = await fetch('/notifications/unread-count', {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            }
        });
        
        if (!response.ok) return;
        
        const data = await response.json();
        updateNotificationBadge(data.unread_count || 0);
        
    } catch (error) {
        console.debug('Error actualizando contador:', error);
    }
}

// Actualizar badge de notificaciones
function updateNotificationBadge(count) {
    const badge = document.getElementById('notificationBadge');
    if (badge) {
        badge.textContent = count;
        badge.style.display = count > 0 ? 'block' : 'none';
        badge.setAttribute('aria-label', `${count} notificaciones nuevas`);
    }
}

// Renderizar notificaciones
function renderNotifications(notifications) {
    const notificationList = document.getElementById('notificationList');
    if (!notificationList) return;
    
    notificationList.innerHTML = '';
    
    if (notifications.length === 0) {
        notificationList.innerHTML = `
            <div class="notification-empty">
                <span class="material-symbols-rounded">notifications_off</span>
                <p>Sin notificaciones</p>
            </div>
        `;
        return;
    }
    
    // Filtrar solo no leídas y excluir las que contienen "token"
    const unreadNotifications = notifications.filter(n => {
        const description = (n.description || '').toLowerCase();
        const user = (n.user || '').toLowerCase();
        const isUnread = n.status === 'unread';
        const hasToken = description.includes('token') || user.includes('token');
        
        return isUnread && !hasToken;
    });
    
    if (unreadNotifications.length === 0) {
        notificationList.innerHTML = `
            <div class="notification-empty">
                <span class="material-symbols-rounded">check_circle</span>
                <p>Todas las notificaciones leídas</p>
            </div>
        `;
        return;
    }
    
    unreadNotifications.forEach(notif => {
        const notifElement = createNotificationElement(notif);
        notificationList.appendChild(notifElement);
    });
}

// Crear elemento de notificación
function createNotificationElement(notif) {
    const config = getNotificationConfig(notif.event_type);
    
    const div = document.createElement('div');
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
        cursor: pointer;
        ${notif.status === 'unread' ? 'background: rgba(59, 130, 246, 0.05);' : ''}
    `;
    
    div.innerHTML = `
        <div style="
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: ${config.color}20;
            color: ${config.color};
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        ">
            <span class="material-symbols-rounded">${config.icon}</span>
        </div>
        <div style="flex: 1; min-width: 0;">
            <div style="
                font-weight: 600;
                font-size: 0.875rem;
                margin-bottom: 0.25rem;
                color: var(--text-primary);
            ">${config.title}</div>
            <div style="
                font-size: 0.875rem;
                color: var(--text-secondary);
                margin-bottom: 0.25rem;
            ">${notif.description}</div>
            <div style="
                font-size: 0.75rem;
                color: var(--text-tertiary);
            ">${formatTime(notif.created_at)}</div>
        </div>
        ${notif.status === 'unread' ? '<div style="width: 8px; height: 8px; border-radius: 50%; background: #3b82f6; flex-shrink: 0;"></div>' : ''}
    `;
    
    div.addEventListener('mouseenter', function() {
        this.style.background = 'var(--bg-hover)';
    });
    
    div.addEventListener('mouseleave', function() {
        this.style.background = notif.status === 'unread' ? 'rgba(59, 130, 246, 0.05)' : 'transparent';
    });
    
    div.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
    });
    
    div.addEventListener('dblclick', function(e) {
        e.preventDefault();
        e.stopPropagation();
        showNotificationDetailModal(notif);
    });
    
    return div;
}

// Agregar notificación al UI en tiempo real
function addNotificationToUI(data) {
    const notificationList = document.getElementById('notificationList');
    if (!notificationList) return;
    
    // Filtrar notificaciones que contienen "token"
    const description = (data.description || '').toLowerCase();
    const user = (data.user || '').toLowerCase();
    
    if (description.includes('token') || user.includes('token')) {
        console.log('🚫 Notificación filtrada (contiene token)');
        return;
    }
    
    // Remover mensaje de "sin notificaciones" si existe
    const emptyMessage = notificationList.querySelector('.notification-empty');
    if (emptyMessage) {
        emptyMessage.remove();
    }
    
    // Crear elemento de notificación
    const notif = {
        id: data.id,
        event_type: data.event_type,
        description: data.description,
        created_at: data.created_at,
        user: data.user,
        status: 'unread'
    };
    
    const notifElement = createNotificationElement(notif);
    
    // Agregar al inicio de la lista con animación
    notifElement.style.opacity = '0';
    notifElement.style.transform = 'translateY(-10px)';
    notificationList.insertBefore(notifElement, notificationList.firstChild);
    
    // Animar entrada
    setTimeout(() => {
        notifElement.style.transition = 'all 0.3s ease';
        notifElement.style.opacity = '1';
        notifElement.style.transform = 'translateY(0)';
    }, 10);
}

// Marcar todas como leídas
async function markAllAsRead() {
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        
        const response = await fetch('/notifications/mark-all-read', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            }
        });
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.success) {
            // Actualizar UI
            updateNotificationBadge(0);
            
            const notificationList = document.getElementById('notificationList');
            if (notificationList) {
                notificationList.innerHTML = `
                    <div class="notification-empty">
                        <span class="material-symbols-rounded">check_circle</span>
                        <p>Todas las notificaciones leídas</p>
                    </div>
                `;
            }
            
            // Cerrar modal
            closeNotificationModal();
        }
        
    } catch (error) {
        console.error('Error marcando notificaciones como leídas:', error);
    }
}

// Marcar como leídas al cerrar modal
async function markAsReadOnClose() {
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        
        await fetch('/notifications/mark-read-on-open', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            }
        });
        
        // Actualizar contador
        updateUnreadCount();
        
    } catch (error) {
        console.debug('Error marcando como leídas al cerrar:', error);
    }
}

// Mostrar notificación toast
function showNotificationToast(data) {
    // Verificar si el usuario quiere ver toasts
    const showToasts = localStorage.getItem('show-notification-toasts') !== 'false';
    if (!showToasts) return;
    
    // Filtrar notificaciones que contienen "token"
    const description = (data.description || '').toLowerCase();
    const user = (data.user || '').toLowerCase();
    
    if (description.includes('token') || user.includes('token')) {
        console.log('🚫 Toast filtrado (contiene token)');
        return;
    }
    
    const config = getNotificationConfig(data.event_type);
    
    const toast = document.createElement('div');
    toast.style.cssText = `
        position: fixed;
        top: 80px;
        right: 20px;
        background: white;
        border: 1px solid var(--border-color);
        border-left: 4px solid ${config.color};
        border-radius: 8px;
        padding: 1rem;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        z-index: 10000;
        max-width: 350px;
        animation: slideInRight 0.3s ease-out;
    `;
    
    toast.innerHTML = `
        <div style="display: flex; align-items: start; gap: 0.75rem;">
            <div style="
                width: 36px;
                height: 36px;
                border-radius: 50%;
                background: ${config.color}20;
                color: ${config.color};
                display: flex;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
            ">
                <span class="material-symbols-rounded" style="font-size: 20px;">${config.icon}</span>
            </div>
            <div style="flex: 1; min-width: 0;">
                <div style="font-weight: 600; font-size: 0.875rem; margin-bottom: 0.25rem;">${config.title}</div>
                <div style="font-size: 0.8rem; color: var(--text-secondary);">${data.description}</div>
            </div>
            <button onclick="this.parentElement.parentElement.remove()" style="
                background: none;
                border: none;
                color: var(--text-secondary);
                cursor: pointer;
                padding: 0;
                font-size: 20px;
            ">×</button>
        </div>
    `;
    
    document.body.appendChild(toast);
    
    // Auto-remover después de 5 segundos
    setTimeout(() => {
        toast.style.animation = 'slideOutRight 0.3s ease-out';
        setTimeout(() => toast.remove(), 300);
    }, 5000);
}

// Obtener configuración de notificación por tipo
function getNotificationConfig(eventType) {
    const configs = {
        'record_created': { icon: 'note_add', color: '#10b981', title: 'Nuevo Registro' },
        'record_updated': { icon: 'edit', color: '#3b82f6', title: 'Registro Actualizado' },
        'record_deleted': { icon: 'delete', color: '#ef4444', title: 'Registro Eliminado' },
        'order_created': { icon: 'shopping_cart', color: '#10b981', title: 'Nueva Orden' },
        'status_changed': { icon: 'update', color: '#f59e0b', title: 'Cambio de Estado' },
        'area_changed': { icon: 'location_on', color: '#8b5cf6', title: 'Cambio de Área' },
        'delivery_registered': { icon: 'local_shipping', color: '#06b6d4', title: 'Entrega Registrada' },
        'order_deleted': { icon: 'delete_outline', color: '#ef4444', title: 'Orden Eliminada' },
        'cotizacion_created': { icon: 'description', color: '#3b82f6', title: 'Nueva Cotización' },
        'cotizacion_updated': { icon: 'edit_note', color: '#f59e0b', title: 'Cotización Actualizada' },
        'cotizacion_approved': { icon: 'check_circle', color: '#10b981', title: 'Cotización Aprobada' },
        'cotizacion_rejected': { icon: 'cancel', color: '#ef4444', title: 'Cotización Rechazada' },
        'pedido_created': { icon: 'add_shopping_cart', color: '#10b981', title: 'Nuevo Pedido' },
        'pedido_approved': { icon: 'verified', color: '#10b981', title: 'Pedido Aprobado' }
    };
    
    return configs[eventType] || { icon: 'notifications', color: '#94a3b8', title: 'Notificación' };
}

// Formatear tiempo relativo
function formatTime(dateString) {
    const now = new Date();
    const date = new Date(dateString);
    const diff = now - date;
    
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
        return date.toLocaleDateString('es-ES');
    }
}

// Modal detallado de notificación
function showNotificationDetailModal(notif) {
    const modal = document.createElement('div');
    modal.className = 'notification-detail-modal';
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.7);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        animation: fadeIn 0.2s ease-out;
    `;
    
    const config = getNotificationConfig(notif.event_type);
    const metadataHTML = notif.metadata ? `
        <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid var(--border-color);">
            <h4 style="margin: 0 0 1rem 0; color: var(--text-primary); font-size: 0.95rem;"> Información Adicional</h4>
            <div style="background: var(--bg-hover); padding: 1rem; border-radius: 8px; font-size: 0.85rem;">
                <pre style="margin: 0; color: var(--text-secondary); overflow-x: auto; white-space: pre-wrap; word-break: break-word;">${JSON.stringify(notif.metadata, null, 2)}</pre>
            </div>
        </div>
    ` : '';
    
    modal.innerHTML = `
        <div style="
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 14px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
            animation: slideUp 0.3s ease-out;
        ">
            <div style="display: flex; align-items: center; gap: 1rem; padding: 1.5rem; border-bottom: 1px solid var(--border-color);">
                <div style="width: 50px; height: 50px; border-radius: 50%; background: ${config.color}20; color: ${config.color}; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 1.5rem;">
                    <span class="material-symbols-rounded">${config.icon}</span>
                </div>
                <div style="flex: 1;">
                    <h2 style="margin: 0 0 0.25rem 0; color: var(--text-primary); font-size: 1.25rem;">${config.title}</h2>
                    <p style="margin: 0; color: var(--text-tertiary); font-size: 0.85rem;">${formatTime(notif.created_at)}</p>
                </div>
                <button onclick="this.closest('.notification-detail-modal').remove()" style="background: none; border: none; color: var(--text-secondary); font-size: 1.5rem; cursor: pointer; padding: 0; display: flex; align-items: center; justify-content: center;">
                    <span class="material-symbols-rounded">close</span>
                </button>
            </div>
            <div style="padding: 1.5rem;">
                <div style="margin-bottom: 1rem;">
                    <h3 style="margin: 0 0 0.5rem 0; color: var(--text-primary); font-size: 0.95rem;"> Descripción</h3>
                    <p style="margin: 0; color: var(--text-secondary); line-height: 1.6;">${notif.description}</p>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                    <div>
                        <h4 style="margin: 0 0 0.5rem 0; color: var(--text-primary); font-size: 0.85rem;">👤 Usuario</h4>
                        <p style="margin: 0; color: var(--text-secondary); font-size: 0.9rem;">${notif.user || 'N/A'}</p>
                    </div>
                    <div>
                        <h4 style="margin: 0 0 0.5rem 0; color: var(--text-primary); font-size: 0.85rem;">🏷️ Tipo de Evento</h4>
                        <p style="margin: 0; color: var(--text-secondary); font-size: 0.9rem;">${notif.event_type || 'N/A'}</p>
                    </div>
                </div>
                ${metadataHTML}
            </div>
        </div>
    `;
    
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.remove();
        }
    });
    
    const closeOnEsc = (e) => {
        if (e.key === 'Escape') {
            modal.remove();
            document.removeEventListener('keydown', closeOnEsc);
        }
    };
    document.addEventListener('keydown', closeOnEsc);
    
    document.body.appendChild(modal);
}

// Estilos de animación
const style = document.createElement('style');
style.textContent = `
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    @keyframes slideUp {
        from { transform: translateY(20px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }
    
    @keyframes slideInRight {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    
    @keyframes slideOutRight {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
`;
document.head.appendChild(style);
