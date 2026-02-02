/**
 * Order Detail Modal Management for Pedidos
 * Handles opening, closing, and overlay management for the order detail modal
 */


/**
 * Abre el modal de detalle de la orden y carga los datos
 * @param {number} numeroPedido - Número del pedido
 */
window.verFactura = async function verFactura(numeroPedido) {
    // Limpiar el número del pedido (remover # si existe)
    const pedidoLimpio = numeroPedido.replace('#', '');
    

    
    try {
        //  HACER FETCH a la API para obtener datos del pedido
        // Intentar primero con /registros (para asesores), luego con /orders (para órdenes)

        let response = await fetch(`/registros/${pedidoLimpio}`);
        
        // Si no encuentra en /registros, intentar con /orders
        if (!response.ok) {

            response = await fetch(`/orders/${pedidoLimpio}`);
        }
        
        if (!response.ok) {

            throw new Error('Error fetching order: ' + response.status);
        }
        const order = await response.json();
        




        
        // Disparar evento para que order-detail-modal-manager.js maneje la apertura

        const loadEvent = new CustomEvent('load-order-detail', { 
            detail: order 
        });
        window.dispatchEvent(loadEvent);
        
    } catch (error) {

        alert('Error al cargar los datos del pedido. Intenta nuevamente.');
    }
}

/**
 * Abre el modal de seguimiento del pedido (ASESORAS - VERSIÓN SIMPLIFICADA)
 * @param {number} numeroPedido - Número del pedido
 */
window.verSeguimiento = function verSeguimiento(numeroPedido) {
    
    console.log('[verSeguimiento] Abriendo seguimiento para:', numeroPedido);
    
    // Intentar primero con openOrderTrackingModal (usado en supervisor-pedidos)
    if (typeof openOrderTrackingModal === 'function') {
        console.log('[verSeguimiento] Usando openOrderTrackingModal');
        openOrderTrackingModal(numeroPedido);
        return;
    }
    
    // Si no está disponible, intentar con openAsesorasTrackingModal (usado en asesores)
    if (typeof openAsesorasTrackingModal === 'function') {
        console.log('[verSeguimiento] Usando openAsesorasTrackingModal');
        openAsesorasTrackingModal(numeroPedido);
        return;
    }
    
    // Si ninguna está disponible, mostrar error
    console.error('[verSeguimiento] Ninguna función de tracking está disponible');
    alert('Error: No se puede abrir el seguimiento. Intenta nuevamente.');
}

/**
 * Abre el modal de detalle de LOGO/BORDADOS del pedido
 * @param {number} logoPedidoId - ID del LogoPedido (NO número de pedido)
 */
window.verFacturaLogo = async function verFacturaLogo(logoPedidoId) {


    
    try {
        //  HACER FETCH a la API usando el ID en lugar del número de pedido

        let response = await fetch(`/api/logo-pedidos/${logoPedidoId}`);
        
        if (!response.ok) {

            throw new Error('Error fetching logo pedido: ' + response.status);
        }
        const order = await response.json();
        

        
        // Disparar evento para que order-detail-modal-manager.js maneje la apertura del logo

        const loadEvent = new CustomEvent('load-order-detail-logo', { 
            detail: order 
        });

        window.dispatchEvent(loadEvent);


        
        // DEBUG: Verificar que el evento se dispara
        setTimeout(() => {

            const overlay = document.getElementById('modal-overlay');
            const wrapper = document.getElementById('order-detail-modal-wrapper-logo');


        }, 500);
        
    } catch (error) {

        alert('Error al cargar los datos del pedido. Intenta nuevamente.');
    }
}

/**
 * Cierra el modal de detalle y el overlay
 */
window.closeModalOverlay = function closeModalOverlay() {
    const overlay = document.getElementById('modal-overlay');
    if (overlay) {
        overlay.style.display = 'none';
    }
    
    const modalWrapper = document.getElementById('order-detail-modal-wrapper');
    if (modalWrapper) {
        modalWrapper.style.display = 'none';
    }
    
    //  Recargar filtros desde localStorage al cerrar modal
    if (typeof loadFiltersFromLocalStorage === 'function') {
        loadFiltersFromLocalStorage();

        if (typeof applyTableFilters === 'function') {
            applyTableFilters();

        }
    }
    
    // Notificar que el modal se cerró (sin causar recursión)
    const closeEvent = new CustomEvent('modal-closed', { detail: 'order-detail' });
    window.dispatchEvent(closeEvent);
}

// Cerrar modal al presionar Escape
document.addEventListener('keydown', function(keyEvent) {
    if (keyEvent.key === 'Escape') {
        const overlay = document.getElementById('modal-overlay');
        if (overlay && overlay.style.display === 'block') {
            window.closeModalOverlay();
        }
    }
});

// Cerrar modal al hacer clic fuera (en el overlay)
document.addEventListener('click', function(clickEvent) {
    const overlay = document.getElementById('modal-overlay');
    const modalContainer = document.querySelector('div[style*="max-width: 672px"]');
    
    // Si se hace clic en el overlay y no en el modal
    if (overlay && overlay.style.display === 'block' && clickEvent.target === overlay) {
        window.closeModalOverlay();
    }
});

/**
 * Confirmar y eliminar un pedido completamente
 * @param {number} pedidoId - ID del pedido a eliminar
 * @param {string} numeroPedido - Número del pedido (para mostrar)
 */
window.confirmarEliminarPedido = function(pedidoId, numeroPedido) {
    // Si se pasa solo un parámetro, asumir que es el ID
    if (typeof numeroPedido === 'undefined') {
        numeroPedido = pedidoId;
    }
    
    // Crear modal de confirmación
    const confirmationModal = document.createElement('div');
    confirmationModal.id = 'confirmDeleteModal';
    confirmationModal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 100001;
        backdrop-filter: blur(4px);
        animation: fadeIn 0.2s ease;
    `;
    
    confirmationModal.innerHTML = `
        <div style="
            background: white;
            border-radius: 12px;
            box-shadow: 0 12px 32px rgba(0, 0, 0, 0.15);
            max-width: 500px;
            width: 90%;
            overflow: hidden;
            animation: slideIn 0.3s ease;
        ">
            <!-- Header -->
            <div style="
                background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
                color: white;
                padding: 1.5rem;
                display: flex;
                justify-content: space-between;
                align-items: center;
            ">
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <i class="fas fa-exclamation-triangle" style="font-size: 1.25rem;"></i>
                    <h3 style="margin: 0; font-size: 1.1rem; font-weight: 600;">Eliminar Pedido</h3>
                </div>
                <button onclick="document.getElementById('confirmDeleteModal').remove()" style="
                    background: rgba(255, 255, 255, 0.2);
                    border: none;
                    color: white;
                    cursor: pointer;
                    font-size: 1.25rem;
                    width: 32px;
                    height: 32px;
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    transition: background 0.2s ease;
                " onmouseover="this.style.background='rgba(255, 255, 255, 0.3)'" onmouseout="this.style.background='rgba(255, 255, 255, 0.2)'">
                    ×
                </button>
            </div>

            <!-- Body -->
            <div style="padding: 1.5rem;">
                <p style="
                    margin: 0 0 1rem 0;
                    color: #374151;
                    font-size: 0.95rem;
                    line-height: 1.5;
                ">
                    ¿Estás seguro de que deseas eliminar el pedido <strong>#${numeroPedido}</strong>?
                </p>
                
                <div style="
                    background: #fee2e2;
                    border-left: 4px solid #ef4444;
                    padding: 1rem;
                    border-radius: 6px;
                    margin-bottom: 1rem;
                ">
                    <p style="
                        margin: 0;
                        color: #991b1b;
                        font-size: 0.875rem;
                        font-weight: 500;
                    ">
                        <i class="fas fa-info-circle" style="margin-right: 0.5rem;"></i>
                        Esta acción eliminará el pedido completamente, incluyendo todas las prendas, procesos y datos asociados. Esta acción no se puede deshacer.
                    </p>
                </div>
            </div>

            <!-- Footer -->
            <div style="
                background: #f9fafb;
                padding: 1rem 1.5rem;
                border-top: 1px solid #e5e7eb;
                display: flex;
                justify-content: flex-end;
                gap: 0.75rem;
            ">
                <button onclick="document.getElementById('confirmDeleteModal').remove()" style="
                    background: white;
                    border: 1px solid #d1d5db;
                    color: #374151;
                    padding: 0.625rem 1.25rem;
                    border-radius: 6px;
                    cursor: pointer;
                    font-weight: 500;
                    font-size: 0.875rem;
                    transition: all 0.2s ease;
                " onmouseover="this.style.background='#f3f4f6'; this.style.borderColor='#9ca3af'" onmouseout="this.style.background='white'; this.style.borderColor='#d1d5db'">
                    Cancelar
                </button>
                <button onclick="eliminarPedidoConfirmado(${pedidoId})" style="
                    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
                    border: none;
                    color: white;
                    padding: 0.625rem 1.25rem;
                    border-radius: 6px;
                    cursor: pointer;
                    font-weight: 500;
                    font-size: 0.875rem;
                    transition: all 0.2s ease;
                    box-shadow: 0 4px 6px rgba(239, 68, 68, 0.25);
                " onmouseover="this.style.boxShadow='0 8px 12px rgba(239, 68, 68, 0.4)'" onmouseout="this.style.boxShadow='0 4px 6px rgba(239, 68, 68, 0.25)'">
                    <i class="fas fa-trash-alt" style="margin-right: 0.5rem;"></i> Eliminar Pedido
                </button>
            </div>
        </div>
    `;
    
    document.body.appendChild(confirmationModal);
    
    // Cerrar al hacer clic fuera
    confirmationModal.onclick = (e) => {
        if (e.target === confirmationModal) {
            confirmationModal.remove();
        }
    };
};

/**
 * Ejecutar la eliminación del pedido
 * @param {number} pedidoId - ID del pedido a eliminar
 */
window.eliminarPedidoConfirmado = async function(pedidoId) {
    const modal = document.getElementById('confirmDeleteModal');
    const button = modal.querySelector('button:last-child');
    
    // Mostrar estado de carga
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Eliminando...';
    
    try {
        const response = await fetch(`/asesores/pedidos-produccion/${pedidoId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        });
        
        const data = await response.json();
        

        
        if (response.ok && data.success) {
            // Cerrar modal
            if (modal) {
                modal.remove();
            }
            
            // Mostrar mensaje de éxito
            showSuccessMessage(' ' + (data.message || 'Pedido eliminado exitosamente'));
            
            // Recargar la página después de 1.5 segundos
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            // El servidor devolvió un error (pero fue procesable)
            const errorMsg = data.message || 'Error al eliminar el pedido';

            throw new Error(errorMsg);
        }
    } catch (error) {

        button.disabled = false;
        button.innerHTML = '<i class="fas fa-trash-alt"></i> Eliminar Pedido';
        
        // Mostrar error de forma más amigable
        Swal.fire({
            icon: 'error',
            title: 'Error al eliminar',
            text: error.message || 'No se pudo eliminar el pedido. Por favor intenta de nuevo.',
            confirmButtonText: 'OK',
            confirmButtonColor: '#ef4444'
        });
    }
};

/**
 * Mostrar mensaje de éxito
 * @param {string} message - Mensaje a mostrar
 */
function showSuccessMessage(message) {
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        font-size: 0.95rem;
        z-index: 100002;
        animation: slideInRight 0.3s ease;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    `;
    notification.innerHTML = `
        <i class="fas fa-check-circle"></i>
        <span>${message}</span>
    `;
    document.body.appendChild(notification);
    
    // Auto-remove después de 3 segundos
    setTimeout(() => {
        notification.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

/**
 * Eliminar pedido directamente sin modal (para dropdown)
 * @param {number} pedidoId - ID del pedido a eliminar
 */
window.eliminarPedidoDirecto = async function(pedidoId) {
    try {
        const response = await fetch(`/asesores/pedidos-produccion/${pedidoId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        });
        
        const data = await response.json();
        

        
        if (response.ok && data.success) {
            // Mostrar mensaje de éxito
            showSuccessMessage(' Pedido eliminado correctamente');
            
            // Recargar la página después de 1 segundo
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            const errorMsg = data.message || 'Error al eliminar el pedido';

            // Mostrar error
            showErrorMessage(' ' + errorMsg);
        }
    } catch (error) {

        showErrorMessage(' Error al eliminar el pedido');
    }
}

/**
 * Mostrar mensaje de error
 */
function showErrorMessage(message) {
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 1rem 1.5rem;
        background: #ef4444;
        color: white;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.25);
        z-index: 999999;
        animation: slideInRight 0.3s ease;
        max-width: 400px;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    `;
    
    notification.innerHTML = `
        <i class="fas fa-exclamation-circle"></i>
        <span>${message}</span>
    `;
    document.body.appendChild(notification);
    
    // Auto-remove después de 3 segundos
    setTimeout(() => {
        notification.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}
