/**
 * Real-time updates script for registros_por_orden
 * Handles Echo/WebSocket listeners for live updates when orders are edited
 */

/**
 * Initialize real-time listeners for registros por orden
 */
function initializeRegistrosPorOrdenRealtimeListeners() {



    if (!window.Echo) {

        setTimeout(initializeRegistrosPorOrdenRealtimeListeners, 500);
        return;
    }



    // Canal de Registros Por Orden
    const registrosChannel = window.Echo.channel('registros-por-orden');

    registrosChannel.subscribed(() => {

    });

    registrosChannel.error((error) => {

    });

    registrosChannel.listen('RegistrosPorOrdenUpdated', (e) => {

        
        // Manejar la actualización de registros
        handleRegistrosUpdate(e.pedido, e.registros, e.action);
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
    // Remover notificaciones existentes
    const existingNotifications = document.querySelectorAll('.registros-notification');
    existingNotifications.forEach(notification => notification.remove());

    // Crear nueva notificación
    const notification = document.createElement('div');
    notification.className = `registros-notification registros-notification-${type}`;
    notification.textContent = message;

    // Estilos inline
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

    // Agregar al DOM
    document.body.appendChild(notification);

    // Auto-remover después de 5 segundos
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
`;
document.head.appendChild(style);

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        setTimeout(initializeRegistrosPorOrdenRealtimeListeners, 100);
    });
} else {
    setTimeout(initializeRegistrosPorOrdenRealtimeListeners, 100);
}

