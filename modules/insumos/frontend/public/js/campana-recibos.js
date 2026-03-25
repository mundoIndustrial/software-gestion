/**
 * SISTEMA DE NOTIFICACIONES EN TIEMPO REAL PARA RECIBOS COSTURA - INSUMOS
 * Solo muestra recibos con tipo_recibo = 'COSTURA' y estado = 'PENDIENTE_INSUMOS'
 */

console.log('[Campana Recibos] 🔔 Script campana-recibos.js cargado');

// Almacenar notificaciones
window.recibosCosturaPendientes = [];
window.notificacionesRecibos = [];

/**
 * Obtener contador de recibos COSTURA en PENDIENTE_INSUMOS
 */
async function obtenerContadorRecibos() {
    try {
        // Obtener URL del endpoint desde el data-attribute o construir manualmente
        const baseUrl = window.location.pathname.split('/').slice(0, 3).join('/');
        const url = baseUrl + '/api/contar-costura-pendiente';
        
        console.log('[Recibos API] Llamando endpoint:', url);
        
        const response = await fetch(url, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            }
        });
        
        if (!response.ok) {
            console.error('[Recibos API] Error HTTP:', response.status, response.statusText);
            return 0;
        }

        const data = await response.json();
        console.log('[Recibos API] Respuesta completa:', data);
        console.log('[Recibos API] Debug info:', data.debug);
        
        const total = data.total || 0;
        console.log('[Recibos API] Contador final:', total);
        
        return total;
    } catch (error) {
        console.error('[Recibos API] Error obteniendo contador:', error);
        return 0;
    }
}

/**
 * Actualizar el contador en la campana
 */
async function actualizarContadorCampana() {
    const contador = await obtenerContadorRecibos();
    const badge = document.getElementById('notificationBadge');
    
    console.log('[Campana] Actualizando contador:', contador);
    
    if (badge) {
        // Actualizar el contenido del badge
        badge.textContent = contador.toString();
        badge.innerText = contador.toString();
        
        // Actualizar visibilidad
        if (contador > 0) {
            badge.style.display = 'inline-block';
            badge.style.removeProperty('display');
            badge.className = 'position-absolute top-0 start-100 translate-middle-y badge bg-danger';
        } else {
            badge.style.display = 'none';
            badge.style.setProperty('display', 'none', 'important');
        }
        
        console.log('[Campana] Badge actualizado - contador:', contador, 'visible:', contador > 0);
    }
}

/**
 * Agregar notificación a la campana
 */
function agregarNotificacionRecibo(pedido) {
    const notificacion = {
        id: Math.random().toString(36).substr(2, 9),
        numero_pedido: pedido.numero_pedido || pedido.pedido,
        cliente: pedido.cliente_nombre || pedido.cliente || 'Sin cliente',
        timestamp: new Date().toLocaleTimeString(),
        orden_id: pedido.id
    };

    window.notificacionesRecibos.push(notificacion);

    const notificationsList = document.getElementById('notificationsList');
    if (notificationsList) {
        if (notificationsList.children.length === 1 && 
            notificationsList.children[0].textContent.includes('Sin notificaciones')) {
            notificationsList.innerHTML = '';
        }

        const notifEl = document.createElement('div');
        notifEl.className = 'dropdown-item';
        notifEl.style.cssText = 'padding: 12px 16px; cursor: pointer; border-bottom: 1px solid #eee;';
        notifEl.innerHTML = `
            <div class="d-flex justify-content-between align-items-start">
                <div class="flex-grow-1">
                    <h6 class="mb-1" style="color: #007bff; font-weight: bold;">
                        <i class="fas fa-box me-2"></i>Recibo #${notificacion.numero_pedido}
                    </h6>
                    <small class="text-muted d-block">${notificacion.cliente}</small>
                    <small class="text-muted d-block">${notificacion.timestamp}</small>
                </div>
            </div>
        `;
        notificationsList.insertBefore(notifEl, notificationsList.firstChild);
    }

    // Toast visual
    mostrarToastRecibo(notificacion);

    // Actualizar contador desde el servidor (para asegurar sincronización)
    actualizarContadorCampana();
}

/**
 * Mostrar toast visual para nuevos recibos
 */
function mostrarToastRecibo(notificacion) {
    const toast = document.createElement('div');
    toast.style.cssText = `
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: #28a745;
        color: white;
        padding: 12px 20px;
        border-radius: 4px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        z-index: 9999;
        animation: slideInUp 0.5s ease-out;
    `;
    toast.innerHTML = `
        <div>
            <strong><i class="fas fa-check-circle me-2"></i>Nuevo Recibo Aprobado</strong>
            <div style="font-size: 0.9em; margin-top: 4px;">
                Recibo #${notificacion.numero_pedido} - ${notificacion.cliente}
            </div>
        </div>
    `;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'slideOutDown 0.5s ease-out';
        setTimeout(() => toast.remove(), 500);
    }, 5000);
}

/**
 * Inicializar listeners en tiempo real
 */
function initializeRealtimeRecibos() {
    window.waitForEcho(() => {
        const echo = window.EchoInstance;
        
        if (!echo) {
            console.warn('[Recibos COSTURA] Echo no está disponible');
            return;
        }

        console.log('[Recibos COSTURA] Inicializando listeners...');

        // Escuchar en el canal 'supervisor-pedidos'
        const channelSupervisor = echo.channel('supervisor-pedidos');
        
        channelSupervisor.subscribed(() => {
            console.log('[Recibos COSTURA]  Suscripción exitosa');
        });

        channelSupervisor.error((error) => {
            console.error('[Recibos COSTURA]  Error en suscripción:', error);
        });

        // Escuchar evento cuando se aprueba un pedido
        ['orden.updated', '.orden.updated', 'OrdenUpdated'].forEach(eventName => {
            channelSupervisor.listen(eventName, (data) => {
                console.log(`[Recibos COSTURA] 📢 Evento '${eventName}' recibido:`, data);
                
                // SOLO procesar si el estado es PENDIENTE_INSUMOS
                if (data.orden && data.orden.estado === 'PENDIENTE_INSUMOS') {
                    console.log('[Recibos COSTURA]  Recibo aprobado a PENDIENTE_INSUMOS');
                    
                    // Agregar a notificaciones
                    agregarNotificacionRecibo(data.orden);
                    
                    // Recargar lista después de un delay
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                }
            });
        });

        console.log('[Recibos COSTURA]  Sistema inicializado');
    });
}

/**
 * Setup de controles de la campana
 */
function setupBellControls() {
    const bellBtn = document.getElementById('notificationBellBtn');
    const dropdown = document.getElementById('notificationDropdown');
    const clearBtn = document.getElementById('clearNotificationsBtn');

    // Abrir/cerrar dropdown
    if (bellBtn && dropdown) {
        bellBtn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
        });
    }

    // Limpiar notificaciones
    if (clearBtn) {
        clearBtn.addEventListener('click', (e) => {
            e.preventDefault();
            window.notificacionesRecibos = [];
            const notificationsList = document.getElementById('notificationsList');
            if (notificationsList) {
                notificationsList.innerHTML = '<div class="text-center text-muted py-3"><p class="mb-0">Sin notificaciones</p></div>';
            }
            const badge = document.getElementById('notificationBadge');
            if (badge) badge.style.display = 'none';
        });
    }

    // Cerrar dropdown al hacer click afuera
    document.addEventListener('click', (e) => {
        if (dropdown && bellBtn && !dropdown.contains(e.target) && !bellBtn.contains(e.target)) {
            dropdown.style.display = 'none';
        }
    });
}

/**
 * Inicializar todo - función reutilizable
 */
async function inicializarCampana() {
    console.log('[Campana] 🔔 Iniciando campana...');
    
    // Verificar que los elementos existan
    const bellBtn = document.getElementById('notificationBellBtn');
    const badgeElement = document.getElementById('notificationBadge');
    
    console.log('[Campana] Elements check:', {
        bellBtn: !!bellBtn,
        badge: !!badgeElement
    });
    
    if (!bellBtn || !badgeElement) {
        console.error('[Campana]  Elementos de campana no encontrados en el DOM');
        console.log('[Campana] IDs buscados: notificationBellBtn, notificationBadge');
        return;
    }

    // Reset explícito del badge - limpiar cualquier valor cacheado
    console.log('[Campana] Limpiando badge previo...');
    badgeElement.textContent = '0';
    badgeElement.innerText = '0';
    badgeElement.style.display = 'none !important';
    badgeElement.setAttribute('style', 'display: none !important;');

    console.log('[Campana] Iniciando actualización del contador...');
    
    // Actualizar contador inicial desde el API
    await actualizarContadorCampana();
    
    // Setup controles
    console.log('[Campana] Configurando controles de campana...');
    setupBellControls();
    
    // Inicializar listeners en tiempo real
    console.log('[Campana] Inicializando listeners de tiempo real...');
    initializeRealtimeRecibos();
}

// Ejecutar en múltiples puntos para asegurar que se ejecute
console.log('[Campana] Script cargado - esperando DOM...');

// Opción 1: Si DOMContentLoaded ya pasó, esto ejecutará inmediatamente
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', inicializarCampana);
} else {
    console.log('[Campana] DOM ya está listo, inicializando inmediatamente');
    setTimeout(inicializarCampana, 50);
}

// Opción 2: Ejecutar también cuando se define la función
window.inicializarCampanaManual = inicializarCampana;

/**
 * CSS para animaciones
 */
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInUp {
        from {
            opacity: 0;
            transform: translateY(100px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes slideOutDown {
        from {
            opacity: 1;
            transform: translateY(0);
        }
        to {
            opacity: 0;
            transform: translateY(100px);
        }
    }

    #notificationDropdown .dropdown-item:hover {
        background-color: #f8f9fa;
    }

    #notificationBellBtn:hover {
        transform: scale(1.1);
        transition: transform 0.2s ease-in-out;
    }
`;
document.head.appendChild(style);

console.log('[Campana Recibos] Script completamente cargado y ejecutado');
