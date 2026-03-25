/**
 * Real-time updates script for registros_por_orden
 * Phase 5: DDD WebSocket Abstraction - Handles real-time listeners for live updates when orders are edited
 */

console.log('[REGISTROS-REALTIME] Archivo cargado');

/**
 * Initialize real-time listeners for registros por orden
 */
function initializeRegistrosPorOrdenRealtimeListeners() {
    console.log('[REGISTROS-REALTIME] Iniciando escuchadores de registros por orden');

    // Usar window.waitForEcho para esperar WebSocket
    if (typeof window.waitForEcho !== 'function') {
        console.log('[REGISTROS-REALTIME] waitForEcho no disponible, reintentando...');
        setTimeout(initializeRegistrosPorOrdenRealtimeListeners, 500);
        return;
    }

    window.waitForEcho(() => {
        const ws = window.shared?.websocket;
        if (!ws) {
            console.error('[REGISTROS-REALTIME] WebSocket abstraction no disponible');
            return;
        }

        console.log('[REGISTROS-REALTIME] WebSocket disponible, suscribiendo a canal...');
        
        try {
            // Suscribirse a actualizaciones de registros por orden
            ws.subscribe('registros-por-orden', 'RegistrosPorOrdenUpdated', (event) => {
                console.log('[REGISTROS-REALTIME] Evento recibido:', event);
                // Manejar la actualización de registros
                handleRegistrosUpdate(event.pedido, event.registros, event.action);
            });
            
            console.log('[REGISTROS-REALTIME]  Suscripción exitosa');
        } catch (error) {
            console.error('[REGISTROS-REALTIME] Error en suscripción:', error);
        }
    });
}

/**
 * Handle registros updates (updated, deleted)
 */
function handleRegistrosUpdate(pedido, registros, action) {


    if (action === 'deleted') {
        // Eliminar todas las filas del pedido
        removeRegistrosFromTable(pedido);
        return;
    }

    if (action === 'updated') {
        // Actualizar los registros en la tabla
        updateRegistrosInTable(pedido, registros);
        return;
    }
}

/**
 * Remove all registros of a pedido from the table
 */
function removeRegistrosFromTable(pedido) {
    // Buscar todas las tablas de registros (producción, polos, corte)
    const tables = document.querySelectorAll('table[data-section]');
    
    tables.forEach(table => {
        const rows = table.querySelectorAll(`tbody tr[data-pedido="${pedido}"]`);
        rows.forEach(row => {
            row.style.backgroundColor = 'rgba(239, 68, 68, 0.2)';
            setTimeout(() => {
                row.remove();

            }, 500);
        });
    });
}

/**
 * Update registros in the table
 */
function updateRegistrosInTable(pedido, registros) {
    if (!registros || registros.length === 0) {

        return;
    }



    // Primero, eliminar los registros existentes del pedido
    const tables = document.querySelectorAll('table[data-section]');
    tables.forEach(table => {
        const rows = table.querySelectorAll(`tbody tr[data-pedido="${pedido}"]`);
        rows.forEach(row => row.remove());
    });

    // Luego, insertar los nuevos registros
    registros.forEach(registro => {
        insertRegistroInTable(registro);
    });

    // Mostrar notificación
    showRegistrosNotification(`Pedido ${pedido} actualizado en tiempo real`, 'success');
}

/**
 * Insert a single registro into the appropriate table
 */
function insertRegistroInTable(registro) {
    // Determinar en qué tabla debe ir (basado en la sección/área)
    // Por ahora, buscar la tabla visible actualmente
    const visibleTable = document.querySelector('table[data-section]:not([style*="display: none"]) tbody');
    
    if (!visibleTable) {

        return;
    }

    // Crear la fila del registro
    const row = document.createElement('tr');
    row.dataset.pedido = registro.pedido;
    row.className = 'table-row';
    
    // Agregar efecto de entrada
    row.style.backgroundColor = 'rgba(59, 130, 246, 0.3)';
    setTimeout(() => {
        row.style.transition = 'background-color 0.5s ease';
        row.style.backgroundColor = '';
    }, 100);

    // Construir las celdas según las columnas de la tabla
    const headers = visibleTable.closest('table').querySelectorAll('thead th');
    
    headers.forEach(header => {
        const column = header.dataset.column;
        if (!column) return;

        const td = document.createElement('td');
        td.className = 'table-cell';
        td.dataset.column = column;

        const cellContent = document.createElement('div');
        cellContent.className = 'cell-content';

        const cellText = document.createElement('span');
        cellText.className = 'cell-text';
        cellText.textContent = registro[column] || '';

        cellContent.appendChild(cellText);
        td.appendChild(cellContent);
        row.appendChild(td);
    });

    // Insertar la fila al inicio de la tabla
    visibleTable.insertBefore(row, visibleTable.firstChild);
}

/**
 * Show notification for registros updates
 */
function showRegistrosNotification(message, type = 'success') {
    // Intentar usar UIUpdateService si está disponible
    const uiUpdate = window.shared?.uiUpdate;
    if (uiUpdate && typeof uiUpdate.showRealtimeToast === 'function') {
        uiUpdate.showRealtimeToast(message, type);
        return;
    }

    // Fallback a implementación local
    const existingNotifications = document.querySelectorAll('.registros-notification');
    existingNotifications.forEach(notification => notification.remove());

    const notification = document.createElement('div');
    notification.className = `registros-notification registros-notification-${type}`;
    notification.textContent = message;

    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        max-width: 400px;
        padding: 16px 20px;
        border-radius: 12px;
        font-size: 14px;
        font-weight: 600;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        z-index: 10001;
        animation: slideInRight 0.3s ease-out;
        background: ${type === 'success' ? 'linear-gradient(135deg, #10b981 0%, #059669 100%)' : 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)'};
        color: white;
    `;

    document.body.appendChild(notification);

    setTimeout(() => {
        if (notification.parentNode) {
            notification.style.animation = 'slideOutRight 0.3s ease-out';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 300);
        }
    }, 5000);
}

// Agregar estilos de animación
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from {
            opacity: 0;
            transform: translateX(100px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }
    
    @keyframes slideOutRight {
        from {
            opacity: 1;
            transform: translateX(0);
        }
        to {
            opacity: 0;
            transform: translateX(100px);
        }
    }

document.head.appendChild(style);

// Initialize when DOM and WebSocket are ready
console.log('[REGISTROS-REALTIME] Configurando inicialización...');
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        console.log('[REGISTROS-REALTIME] DOM listo, iniciando...');
        setTimeout(initializeRegistrosPorOrdenRealtimeListeners, 100);
    });
} else {
    console.log('[REGISTROS-REALTIME] DOM ya listo, iniciando...');
    setTimeout(initializeRegistrosPorOrdenRealtimeListeners, 100);
}

