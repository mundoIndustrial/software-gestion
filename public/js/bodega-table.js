/**
 * Script específico para tabla de Bodega
 * Maneja filtros, búsqueda, paginación y acciones básicas
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log(' Bodega Table Script Inicializado');
    
    // Inicializar filtros
    initializeBodegaFilters();
    initializeBodegaSearch();
    initializePagination();
    initializeDropdowns();
});

/**
 * Inicializar filtros de bodega
 */
function initializeBodegaFilters() {
    const filterButtons = document.querySelectorAll('.filter-btn');
    
    filterButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const columnName = this.dataset.columnName;
            console.log(' Abriendo filtro para:', columnName);
            openFilterModal(columnName);
        });
    });
}

/**
 * Abrir modal de filtro
 */
function openFilterModal(columnName) {
    // Obtener valores únicos de la columna
    fetch(`/bodega?get_unique_values=1&column=${columnName}`)
        .then(response => response.json())
        .then(data => {
            showFilterModal(columnName, data.unique_values || []);
        })
        .catch(error => console.error('Error al obtener valores de filtro:', error));
}

/**
 * Mostrar modal de filtro
 */
function showFilterModal(columnName, values) {
    const modal = document.createElement('div');
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10000;
    `;
    
    const content = document.createElement('div');
    content.style.cssText = `
        background: white;
        padding: 24px;
        border-radius: 12px;
        max-width: 400px;
        max-height: 500px;
        overflow-y: auto;
        box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    `;
    
    content.innerHTML = `
        <h3 style="margin-top: 0; margin-bottom: 16px; color: #333;">Filtrar por ${columnName}</h3>
        <div id="filterOptions" style="margin-bottom: 16px; max-height: 300px; overflow-y: auto;">
            ${values.map((val, idx) => `
                <label style="display: flex; align-items: center; margin-bottom: 8px; cursor: pointer;">
                    <input type="checkbox" name="filter_${columnName}" value="${val}" style="margin-right: 8px;">
                    <span>${val || '(vacío)'}</span>
                </label>
            `).join('')}
        </div>
        <div style="display: flex; gap: 8px; justify-content: flex-end;">
            <button onclick="closeFilterModal()" style="padding: 8px 16px; background: #6c757d; color: white; border: none; border-radius: 6px; cursor: pointer;">Cancelar</button>
            <button onclick="applyFilter('${columnName}')" style="padding: 8px 16px; background: #007bff; color: white; border: none; border-radius: 6px; cursor: pointer;">Aplicar</button>
        </div>
    `;
    
    modal.appendChild(content);
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            closeFilterModal();
        }
    });
    
    document.body.appendChild(modal);
    window.currentFilterModal = modal;
}

/**
 * Cerrar modal de filtro
 */
function closeFilterModal() {
    if (window.currentFilterModal) {
        window.currentFilterModal.remove();
        window.currentFilterModal = null;
    }
}

/**
 * Aplicar filtro
 */
function applyFilter(columnName) {
    const checkboxes = document.querySelectorAll(`input[name="filter_${columnName}"]:checked`);
    const values = Array.from(checkboxes).map(cb => cb.value).join('|||FILTER_SEPARATOR|||');
    
    if (values) {
        window.location.href = `?filter_${columnName}=${encodeURIComponent(values)}`;
    }
    
    closeFilterModal();
}

/**
 * Inicializar búsqueda
 */
function initializeBodegaSearch() {
    const searchInput = document.getElementById('buscarOrden');
    if (!searchInput) return;
    
    searchInput.addEventListener('keyup', debounce(function(e) {
        const searchTerm = e.target.value;
        if (searchTerm.length >= 2 || searchTerm.length === 0) {
            window.location.href = `?search=${encodeURIComponent(searchTerm)}`;
        }
    }, 500));
}

/**
 * Debounce helper
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Inicializar paginación
 */
function initializePagination() {
    const paginationBtns = document.querySelectorAll('.pagination-btn');
    
    paginationBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const page = this.dataset.page;
            if (page) {
                const url = new URL(window.location);
                url.searchParams.set('page', page);
                window.location.href = url.toString();
            }
        });
    });
}

/**
 * Inicializar dropdowns de Estado y Área
 */
function initializeDropdowns() {
    // Dropdowns de Estado
    document.querySelectorAll('.estado-dropdown').forEach(select => {
        select.addEventListener('change', function() {
            const pedido = this.dataset.id;
            const newValue = this.value;
            updateOrderStatus(pedido, newValue);
        });
    });
    
    // Dropdowns de Área
    document.querySelectorAll('.area-dropdown').forEach(select => {
        select.addEventListener('change', function() {
            const pedido = this.dataset.id;
            const newValue = this.value;
            updateOrderArea(pedido, newValue);
        });
    });
}

/**
 * Actualizar estado de la orden
 */
function updateOrderStatus(pedido, newStatus) {
    console.log(`🔄 Actualizando estado del pedido ${pedido} a ${newStatus}`);
    
    fetch(`/bodega/${pedido}`, {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
            estado: newStatus
        })
    })
    .then(response => {
        if (response.ok) {
            console.log(' Estado actualizado correctamente');
            showNotification('Estado actualizado correctamente', 'success');
        } else {
            console.error(' Error al actualizar estado');
            showNotification('Error al actualizar estado', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error en la solicitud', 'error');
    });
}

/**
 * Actualizar área de la orden
 */
function updateOrderArea(pedido, newArea) {
    console.log(`🔄 Actualizando área del pedido ${pedido} a ${newArea}`);
    
    fetch(`/bodega/${pedido}`, {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
            area: newArea
        })
    })
    .then(response => {
        if (response.ok) {
            console.log(' Área actualizada correctamente');
            showNotification('Área actualizada correctamente', 'success');
        } else {
            console.error(' Error al actualizar área');
            showNotification('Error al actualizar área', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error en la solicitud', 'error');
    });
}

/**
 * Mostrar notificación
 */
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    const bgColor = type === 'success' ? '#28a745' : type === 'error' ? '#dc3545' : '#007bff';
    
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${bgColor};
        color: white;
        padding: 16px 24px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        z-index: 10001;
        animation: slideIn 0.3s ease;
    `;
    
    notification.textContent = message;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

/**
 * Editar orden
 */
function openEditModal(pedido) {
    console.log(` Abriendo editor para pedido ${pedido}`);
    openBodegaEditModal(pedido);
}

/**
 * Ver detalles de la orden
 */
function createViewButtonDropdown(pedido) {
    console.log(`👁️ Viendo detalles del pedido ${pedido}`);
    
    // Verificar si ya existe un dropdown
    const existingDropdown = document.querySelector(`.view-button-dropdown[data-order-id="${pedido}"]`);
    if (existingDropdown) {
        existingDropdown.remove();
        return;
    }
    
    // Crear dropdown
    const dropdown = document.createElement('div');
    dropdown.className = 'view-button-dropdown';
    dropdown.dataset.orderId = pedido;
    dropdown.innerHTML = `
        <button class="dropdown-option detail-option">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                <circle cx="12" cy="12" r="3"></circle>
            </svg>
            <span>Detalle</span>
        </button>
        <button class="dropdown-option tracking-option">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M9 11l3 3L22 4M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <span>Seguimiento</span>
        </button>
    `;
    
    // Posicionar el dropdown cerca del botón Ver
    // Buscar el botón por data-orden-id y que contenga "createViewButtonDropdown"
    const viewButton = document.querySelector(`.action-view-btn[data-orden-id="${pedido}"][onclick*="createViewButtonDropdown"]`);
    if (viewButton) {
        const rect = viewButton.getBoundingClientRect();
        dropdown.style.position = 'fixed';
        dropdown.style.top = (rect.bottom + 5) + 'px';
        dropdown.style.left = rect.left + 'px';
        dropdown.style.zIndex = '9999';
        document.body.appendChild(dropdown);
        
        console.log(' Dropdown creado');
        
        // Agregar event listeners
        const detailBtn = dropdown.querySelector('.detail-option');
        const trackingBtn = dropdown.querySelector('.tracking-option');
        
        detailBtn.addEventListener('click', function() {
            console.log(' Abriendo detalle de bodega:', pedido);
            openDetailBodega(pedido);
            dropdown.remove();
        });
        
        trackingBtn.addEventListener('click', function() {
            console.log(' Abriendo seguimiento de bodega:', pedido);
            openBodegaTrackingModal(pedido);
            dropdown.remove();
        });
        
        // Cerrar dropdown al hacer click fuera
        setTimeout(() => {
            document.addEventListener('click', function closeDropdown(e) {
                if (!dropdown.contains(e.target) && !viewButton.contains(e.target)) {
                    dropdown.remove();
                    document.removeEventListener('click', closeDropdown);
                }
            });
        }, 0);
    } else {
        console.warn(' No se encontró el botón Ver para el pedido:', pedido);
    }
}

/**
 * Eliminar orden
 */
function deleteOrder(pedido) {
    if (!confirm(`¿Está seguro de que desea eliminar el pedido ${pedido}?`)) {
        return;
    }
    
    console.log(`🗑️ Eliminando pedido ${pedido}`);
    
    fetch(`/bodega/${pedido}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => {
        if (response.ok) {
            showNotification('Pedido eliminado correctamente', 'success');
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            showNotification('Error al eliminar el pedido', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error en la solicitud', 'error');
    });
}

// Agregar estilos de animación
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(400px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(400px);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);
