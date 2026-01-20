/**
 * Módulo: ViewDropdownManager
 * Responsabilidad: Gestionar los dropdowns del botón Ver
 * Principio SOLID: Single Responsibility
 */

const ViewDropdownManager = (() => {
    /**
     * Crea un dropdown para el botón Ver
     */
    function createViewButtonDropdown(orderId) {
        console.log('%c🔧 [DROPDOWN] Creando dropdown para orden: ' + orderId, 'color: purple; font-weight: bold;');
        
        // Verificar si ya existe un dropdown
        const existingDropdown = document.querySelector(`.view-button-dropdown[data-order-id="${orderId}"]`);
        if (existingDropdown) {
            console.log('⚠️ [DROPDOWN] Dropdown ya existe, removiendo...');
            existingDropdown.remove();
            return;
        }
        
        // Crear dropdown
        const dropdown = document.createElement('div');
        dropdown.className = 'view-button-dropdown';
        dropdown.dataset.orderId = orderId;
        dropdown.innerHTML = `
            <button class="dropdown-option detail-option" onclick="console.log('🔵 [DROPDOWN] Click en Detalle'); viewDetail(${orderId}); ViewDropdownManager.closeViewDropdown(${orderId})">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                    <circle cx="12" cy="12" r="3"></circle>
                </svg>
                <span>Detalle</span>
            </button>
            <button class="dropdown-option tracking-option" onclick="console.log('🔵 [DROPDOWN] Click en Seguimiento'); openOrderTracking(${orderId}); ViewDropdownManager.closeViewDropdown(${orderId})">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 11l3 3L22 4M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span>Seguimiento</span>
            </button>
        `;
        
        // Posicionar el dropdown cerca del botón Ver
        const viewButton = document.querySelector(`.detail-btn[onclick*="createViewButtonDropdown(${orderId})"]`);
        if (viewButton) {
            const rect = viewButton.getBoundingClientRect();
            dropdown.style.position = 'fixed';
            dropdown.style.top = (rect.bottom + 5) + 'px';
            dropdown.style.left = rect.left + 'px';
            dropdown.style.zIndex = '9999';
            document.body.appendChild(dropdown);
            
            console.log(' [DROPDOWN] Dropdown creado y agregado al DOM');
            
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
            console.warn(' [DROPDOWN] No se encontró el botón Ver para la orden:', orderId);
        }
    }
    
    /**
     * Cierra el dropdown del botón Ver
     */
    function closeViewDropdown(orderId) {
        const dropdown = document.querySelector(`.view-button-dropdown[data-order-id="${orderId}"]`);
        if (dropdown) {
            dropdown.remove();
        }
    }
    
    // Interfaz pública
    return {
        createViewButtonDropdown,
        closeViewDropdown
    };
})();

globalThis.ViewDropdownManager = ViewDropdownManager;
