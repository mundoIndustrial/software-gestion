// ========================================
// NOTIFICATIONS SYSTEM - CONTADOR
// ========================================
let lastMarkAllReadTime = 0; // Timestamp de última vez que se marcaron todas como leídas

document.addEventListener('DOMContentLoaded', function() {
    initializeNotifications();
});

function initializeNotifications() {
    // Cargar notificaciones iniciales
    loadNotifications();
    
    // Actualizar notificaciones cada 30 segundos
    // PERO: Si hace poco marcamos todas como leídas, esperar más tiempo
    setInterval(() => {
        const timeSinceMarkAllRead = Date.now() - lastMarkAllReadTime;
        // Si pasaron menos de 2 minutos desde que marcamos todas, esperar 60 segundos más
        // Si no, cargar normalmente
        if (timeSinceMarkAllRead < 120000) {

            return;
        }
        loadNotifications();
    }, 30000);
    
    // Toggle del dropdown de notificaciones
    const notificationBtn = document.getElementById('notificationBtn');
    const notificationMenu = document.getElementById('notificationMenu');
    
    if (notificationBtn && notificationMenu) {
        notificationBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Cerrar menú de usuario si está abierto
            const userMenu = document.getElementById('userMenu');
            if (userMenu) {
                userMenu.classList.remove('show');
            }
            
            // Toggle del menú de notificaciones
            const isShowing = notificationMenu.classList.contains('show');
            
            if (isShowing) {
                // Cerrar
                notificationMenu.classList.remove('show');
            } else {
                // Abrir y cargar notificaciones
                notificationMenu.classList.add('show');
                loadNotifications();
            }
        }, false);
    }
    
    // Toggle del dropdown de usuario
    // COMENTADO: El manejo del dropdown de usuario se hace en top-nav.js para evitar conflictos
    /*
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
    */
    
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
        let newsItems = null;
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        
        // Crear promesa con timeout
        const fetchWithTimeout = (url, options, timeout = 5000) => {
            return Promise.race([
                fetch(url, options).then(r => r.ok ? r.json() : null),
                new Promise((_, reject) => 
                    setTimeout(() => reject(new Error('Timeout')), timeout)
                )
            ]);
        };
        
        // Intentar cargar de news primero (más rápido)
        try {
            const result = await fetchWithTimeout('/dashboard/news?limit=100', {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                }
            }, 3000);
            
            if (result) {
                newsItems = result.news || result;
            }
        } catch (e) {

        }
        
        // Si no hay datos de news, intentar contador
        if (!newsItems || newsItems.length === 0) {
            try {
                let data = null;
                
                if (typeof window.fetchAPI === 'function') {
                    data = await window.fetchAPI('/contador/notifications');
                } else {
                    data = await fetchWithTimeout('/contador/notifications', {
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        }
                    }, 3000);
                }
                
                if (data && data.cotizaciones_para_revisar) {
                    newsItems = data.cotizaciones_para_revisar;
                }
            } catch (e) {

            }
        }
        
        if (newsItems && newsItems.length > 0) {
            const data = convertDashboardNotifications(newsItems);
            updateNotificationBadge(data.total_notificaciones || 0);
            renderNotifications(data);
        } else {
            updateNotificationBadge(0);
            renderNotifications({ total_notificaciones: 0, all_notifications: [] });
        }
    } catch (error) {

    }
}

// Convertir notificaciones del dashboard al formato del contador
function convertDashboardNotifications(newsItems) {
    const eventTypeConfig = {
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
        'cotizacion_rejected': { icon: 'cancel', color: '#ef4444', title: 'Cotización Rechazada' }
    };
    
    // Filtrar notificaciones que contienen "token" (case-insensitive)
    const filteredItems = newsItems.filter(n => {
        const description = (n.description || '').toLowerCase();
        const user = (n.user || '').toLowerCase();
        return !description.includes('token') && !user.includes('token');
    });
    
    const notifications = filteredItems.map(n => {
        const config = eventTypeConfig[n.event_type] || { icon: 'notifications', color: '#94a3b8', title: 'Evento' };
        return {
            id: n.id,
            icon: config.icon,
            color: config.color,
            title: config.title,
            message: n.description,
            time: formatTime(n.created_at),
            link: '#',
            event_type: n.event_type,
            user: n.user,
            metadata: n.metadata
        };
    });
    
    return {
        total_notificaciones: notifications.length,
        cotizaciones_para_revisar: filteredItems
            .filter(n => n.event_type && n.event_type.includes('cotizacion'))
            .map(n => ({
                id: n.id,
                cliente: n.description,
                created_at: n.created_at,
                event_type: n.event_type,
                user: n.user,
                metadata: n.metadata
            })),
        nuevas_cotizaciones: [],
        all_notifications: notifications
    };
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
    
    // Agregar cotizaciones para revisar (del contador)
    if (data.cotizaciones_para_revisar && data.cotizaciones_para_revisar.length > 0) {
        data.cotizaciones_para_revisar.forEach(cot => {
            notifications.push({
                icon: 'description',
                color: '#3b82f6',
                title: 'Cotización para revisar',
                message: `COT-${String(cot.id).padStart(5, '0')} - ${cot.cliente}`,
                time: formatTime(cot.created_at),
                link: `/contador/dashboard`,
                event_type: cot.event_type,
                metadata: cot.metadata
            });
        });
    }
    
    // Agregar nuevas cotizaciones creadas (del contador)
    if (data.nuevas_cotizaciones && data.nuevas_cotizaciones.length > 0) {
        data.nuevas_cotizaciones.forEach(cot => {
            notifications.push({
                icon: 'note_add',
                color: '#10b981',
                title: 'Nueva cotización creada',
                message: `COT-${String(cot.id).padStart(5, '0')} - ${cot.cliente}`,
                time: formatTime(cot.created_at),
                link: `/contador/dashboard`,
                event_type: cot.event_type,
                metadata: cot.metadata
            });
        });
    }
    
    // Agregar todos los eventos del dashboard
    if (data.all_notifications && data.all_notifications.length > 0) {
        data.all_notifications.forEach(notif => {
            notifications.push(notif);
        });
    }
    
    // Ordenar por fecha más reciente primero
    notifications.sort((a, b) => {
        const timeA = a.time || '';
        const timeB = b.time || '';
        return timeB.localeCompare(timeA);
    });
    
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
    const div = document.createElement('div');
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
        cursor: pointer;
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
    
    // Click simple - no hace nada, solo previene propagación
    div.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
    });
    
    // Doble click para abrir modal detallado
    div.addEventListener('dblclick', function(e) {
        e.preventDefault();
        e.stopPropagation();
        showNotificationDetailModal(notif);
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
        let success = false;
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        
        // Intentar con fetchAPI primero
        if (typeof window.fetchAPI === 'function') {
            try {
                const result = await window.fetchAPI('/contador/notifications/marcar-leidas', {
                    method: 'POST'
                });
                success = result?.success || true;
            } catch (e) {

                success = false;
            }
        }
        
        // Si fetchAPI falló, intentar fetch normal
        if (!success) {
            const response = await fetch('/contador/notifications/marcar-leidas', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                }
            });
            
            if (!response.ok) {

                throw new Error(`HTTP Error ${response.status}`);
            }
            
            const data = await response.json();
            success = data?.success || true;
        }
        
        // Solo limpiar la UI si la solicitud fue exitosa
        if (success) {
            // Registrar el tiempo de marca como leídas
            lastMarkAllReadTime = Date.now();
            
            updateNotificationBadge(0);
            const notificationList = document.getElementById('notificationList');
            if (notificationList) {
                notificationList.innerHTML = `
                    <div class="notification-empty">
                        <span class="material-symbols-rounded">notifications_off</span>
                        <p>Sin notificaciones</p>
                    </div>
                `;
            }
            
            // Cerrar el dropdown después de marcar como leídas
            const notificationMenu = document.getElementById('notificationMenu');
            if (notificationMenu) {
                notificationMenu.classList.remove('show');
            }
        }
    } catch (error) {

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
    
    const metadataHTML = notif.metadata ? `
        <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid var(--border-color);">
            <h4 style="margin: 0 0 1rem 0; color: var(--text-primary); font-size: 0.95rem;"> Información Adicional</h4>
            <div style="background: var(--bg-hover); padding: 1rem; border-radius: 8px; font-size: 0.85rem;">
                <pre style="margin: 0; color: var(--text-secondary); overflow-x: auto; white-space: pre-wrap; word-break: break-word;">
${JSON.stringify(notif.metadata, null, 2)}
                </pre>
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
            <!-- Header -->
            <div style="
                display: flex;
                align-items: center;
                gap: 1rem;
                padding: 1.5rem;
                border-bottom: 1px solid var(--border-color);
            ">
                <div style="
                    width: 50px;
                    height: 50px;
                    border-radius: 50%;
                    background: ${notif.color}20;
                    color: ${notif.color};
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    flex-shrink: 0;
                    font-size: 1.5rem;
                ">
                    <span class="material-symbols-rounded">${notif.icon}</span>
                </div>
                <div style="flex: 1;">
                    <h2 style="margin: 0 0 0.25rem 0; color: var(--text-primary); font-size: 1.25rem;">${notif.title}</h2>
                    <p style="margin: 0; color: var(--text-tertiary); font-size: 0.85rem;">${notif.time}</p>
                </div>
                <button onclick="this.closest('.notification-detail-modal').remove()" style="
                    background: none;
                    border: none;
                    color: var(--text-secondary);
                    font-size: 1.5rem;
                    cursor: pointer;
                    padding: 0;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                ">
                    <span class="material-symbols-rounded">close</span>
                </button>
            </div>
            
            <!-- Body -->
            <div style="padding: 1.5rem;">
                <div style="margin-bottom: 1rem;">
                    <h3 style="margin: 0 0 0.5rem 0; color: var(--text-primary); font-size: 0.95rem;"> Descripción</h3>
                    <p style="margin: 0; color: var(--text-secondary); line-height: 1.6;">${notif.message}</p>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                    <div>
                        <h4 style="margin: 0 0 0.5rem 0; color: var(--text-primary); font-size: 0.85rem;">Usuario</h4>
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
    
    // Cerrar al hacer click fuera
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.remove();
        }
    });
    
    // Cerrar con ESC
    const closeOnEsc = (e) => {
        if (e.key === 'Escape') {
            modal.remove();
            document.removeEventListener('keydown', closeOnEsc);
        }
    };
    document.addEventListener('keydown', closeOnEsc);
    
    document.body.appendChild(modal);
}
