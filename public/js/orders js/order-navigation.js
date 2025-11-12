// order-navigation.js - Navegación entre órdenes con teclas de flecha

let currentOrderId = null;
let allOrderIds = [];
let keysPressed = new Set(); // Rastrear teclas presionadas
let navigationInterval = null; // Intervalo de navegación continua
const NAVIGATION_SPEED = 400; // Milisegundos entre navegaciones mientras se mantiene presionada

/**
 * Obtener lista de todas las órdenes de la tabla actual
 */
function getAllOrderIds() {
    const tbody = document.getElementById('tablaOrdenesBody');
    if (!tbody) return [];
    
    const rows = tbody.querySelectorAll('tr.table-row');
    return Array.from(rows).map(row => {
        const pedido = row.dataset.orderId;
        return pedido ? parseInt(pedido) : null;
    }).filter(id => id !== null);
}

/**
 * Encontrar el índice de la orden actual en la lista
 */
function getCurrentOrderIndex() {
    if (!currentOrderId) return -1;
    return allOrderIds.indexOf(currentOrderId);
}

/**
 * Navegar a la siguiente orden
 */
function navigateToNextOrder() {
    const currentIndex = getCurrentOrderIndex();
    if (currentIndex === -1 || currentIndex >= allOrderIds.length - 1) {
        return; // No hay siguiente orden
    }
    
    const nextOrderId = allOrderIds[currentIndex + 1];
    viewDetail(nextOrderId);
}

/**
 * Navegar a la orden anterior
 */
function navigateToPreviousOrder() {
    const currentIndex = getCurrentOrderIndex();
    if (currentIndex <= 0) {
        return; // No hay orden anterior
    }
    
    const previousOrderId = allOrderIds[currentIndex - 1];
    viewDetail(previousOrderId);
}

/**
 * Actualizar la orden actual cuando se abre un detalle
 */
function setCurrentOrder(pedido) {
    currentOrderId = pedido;
    allOrderIds = getAllOrderIds();
    console.log(`📋 Orden actual: ${currentOrderId}, Total órdenes: ${allOrderIds.length}`);
    
    // Deshabilitar scroll horizontal de la tabla cuando el modal está abierto
    disableTableScroll();
}

/**
 * Deshabilitar scroll horizontal de la tabla
 */
function disableTableScroll() {
    const tbody = document.getElementById('tablaOrdenesBody');
    if (tbody) {
        const table = tbody.closest('table');
        if (table) {
            const wrapper = table.closest('.table-wrapper') || table.parentElement;
            if (wrapper) {
                wrapper.style.overflowX = 'hidden';
                wrapper.style.overflowY = 'auto';
            }
        }
    }
}

/**
 * Habilitar scroll horizontal de la tabla
 */
function enableTableScroll() {
    const tbody = document.getElementById('tablaOrdenesBody');
    if (tbody) {
        const table = tbody.closest('table');
        if (table) {
            const wrapper = table.closest('.table-wrapper') || table.parentElement;
            if (wrapper) {
                wrapper.style.overflowX = 'auto';
                wrapper.style.overflowY = 'auto';
            }
        }
    }
}

/**
 * Verificar si el modal de detalle está abierto
 */
function isOrderDetailModalOpen() {
    // Buscar el contenedor del modal de detalle
    const modalContainer = document.querySelector('.order-detail-modal-container');
    if (!modalContainer) return false;
    
    // Verificar si el modal padre está visible
    const modal = modalContainer.closest('[x-data]');
    if (!modal) return false;
    
    // Verificar el atributo style o la clase de visibilidad
    const style = window.getComputedStyle(modal);
    return style.display !== 'none' && style.visibility !== 'hidden';
}

/**
 * Inicializar listeners de teclado
 */
function initializeKeyboardNavigation() {
    let currentDirection = null; // Rastrear dirección actual (right, left, o null)
    
    // Listener para keydown - iniciar navegación continua
    document.addEventListener('keydown', (e) => {
        // Solo navegar si el modal de detalle está abierto
        if (!isOrderDetailModalOpen()) {
            return;
        }
        
        // Ignorar si el usuario está escribiendo en un input o textarea
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
            return;
        }
        
        // Ignorar si la tecla ya estaba presionada
        if (keysPressed.has(e.key)) {
            return;
        }
        
        if (e.key === 'ArrowRight') {
            e.preventDefault();
            keysPressed.add(e.key);
            
            // Si ya hay un intervalo, detenerlo
            if (navigationInterval) {
                clearInterval(navigationInterval);
            }
            
            currentDirection = 'right';
            // Navegar inmediatamente
            navigateToNextOrder();
            
            // Luego continuar navegando mientras se mantenga presionada
            navigationInterval = setInterval(() => {
                if (currentDirection === 'right' && isOrderDetailModalOpen()) {
                    navigateToNextOrder();
                }
            }, NAVIGATION_SPEED);
            
        } else if (e.key === 'ArrowLeft') {
            e.preventDefault();
            keysPressed.add(e.key);
            
            // Si ya hay un intervalo, detenerlo
            if (navigationInterval) {
                clearInterval(navigationInterval);
            }
            
            currentDirection = 'left';
            // Navegar inmediatamente
            navigateToPreviousOrder();
            
            // Luego continuar navegando mientras se mantenga presionada
            navigationInterval = setInterval(() => {
                if (currentDirection === 'left' && isOrderDetailModalOpen()) {
                    navigateToPreviousOrder();
                }
            }, NAVIGATION_SPEED);
        }
    });
    
    // Listener para keyup - detener navegación inmediatamente
    document.addEventListener('keyup', (e) => {
        if (e.key === 'ArrowRight' || e.key === 'ArrowLeft') {
            keysPressed.delete(e.key);
            
            // Detener el intervalo inmediatamente
            if (navigationInterval) {
                clearInterval(navigationInterval);
                navigationInterval = null;
            }
            
            currentDirection = null;
        }
    });
}

/**
 * Monitorear cierre del modal para habilitar scroll
 */
function monitorModalClose() {
    // Escuchar el evento close-modal
    document.addEventListener('close-modal', (e) => {
        if (e.detail === 'order-detail') {
            enableTableScroll();
            currentOrderId = null;
        }
    });
}

// Inicializar cuando el DOM esté listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        initializeKeyboardNavigation();
        monitorModalClose();
    });
} else {
    initializeKeyboardNavigation();
    monitorModalClose();
}
