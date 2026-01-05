/**
 * Dropdown Manager para Pedidos - Versión Mejorada
 * Crea dropdowns dinámicamente fuera de la tabla
 */

document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('dropdowns-container');
    const menuHeight = 150;
    
    // Crear dropdown para botón Ver
    function crearDropdownVer(button) {
        const menuId = button.getAttribute('data-menu-id');
        const pedido = button.getAttribute('data-pedido');
        const pedidoId = button.getAttribute('data-pedido-id'); // ID de pedidos_produccion
        const logoPedidoId = button.getAttribute('data-logo-pedido-id'); // ✅ ID específico para logo_pedidos
        let tipoCotizacion = button.getAttribute('data-tipo-cotizacion');
        // Detectar si es LOGO explícito (atributo agregado a la vista)
        const esLogoAttr = button.getAttribute('data-es-logo');
        const esLogo = esLogoAttr === '1' || esLogoAttr === 'true';
        if (esLogo) {
            tipoCotizacion = 'L';
        }
        
        // Verificar si ya existe
        if (document.getElementById(menuId)) {
            return document.getElementById(menuId);
        }
        
        // Crear el dropdown
        const dropdown = document.createElement('div');
        dropdown.id = menuId;
        dropdown.className = 'dropdown-menu';
        dropdown.style.cssText = `
            position: fixed;
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
            min-width: 180px;
            display: none;
            z-index: 999999;
            overflow: visible;
            pointer-events: auto;
        `;
        
        // Construir el HTML del dropdown según el tipo de cotización
        let dropdownHTML = '';
        
        if (tipoCotizacion === 'L') {
            // Solo Logo
            dropdownHTML = `
                <button onclick="verFacturaLogo(${logoPedidoId}); closeDropdown()" style="
                    width: 100%;
                    text-align: left;
                    padding: 0.875rem 1rem;
                    border: none;
                    background: transparent;
                    cursor: pointer;
                    color: #374151;
                    font-size: 0.875rem;
                    transition: all 0.2s ease;
                    display: flex;
                    align-items: center;
                    gap: 0.75rem;
                    font-weight: 500;
                " onmouseover="this.style.background='#fef2f2'" onmouseout="this.style.background='transparent'">
                    <i class="fas fa-image" style="color: #dc2626;"></i> Recibo de Logo
                </button>
            `;
        } else if (tipoCotizacion === 'PL') {
            // Prenda + Logo (Combinada)
            dropdownHTML = `
                <button onclick="verFactura('${pedido}'); closeDropdown()" style="
                    width: 100%;
                    text-align: left;
                    padding: 0.875rem 1rem;
                    border: none;
                    background: transparent;
                    cursor: pointer;
                    color: #374151;
                    font-size: 0.875rem;
                    transition: all 0.2s ease;
                    display: flex;
                    align-items: center;
                    gap: 0.75rem;
                    font-weight: 500;
                " onmouseover="this.style.background='#f0f9ff'" onmouseout="this.style.background='transparent'">
                    <i class="fas fa-file-alt" style="color: #2563eb;"></i> Recibo de Costura
                </button>
                <div style="height: 1px; background: #e5e7eb;"></div>
                <button onclick="verFacturaLogo(${logoPedidoId}); closeDropdown()" style="
                    width: 100%;
                    text-align: left;
                    padding: 0.875rem 1rem;
                    border: none;
                    background: transparent;
                    cursor: pointer;
                    color: #374151;
                    font-size: 0.875rem;
                    transition: all 0.2s ease;
                    display: flex;
                    align-items: center;
                    gap: 0.75rem;
                    font-weight: 500;
                " onmouseover="this.style.background='#fef2f2'" onmouseout="this.style.background='transparent'">
                    <i class="fas fa-image" style="color: #dc2626;"></i> Recibo de Bordados
                </button>
            `;
        } else {
            // Prenda o Reflectivo (solo costura)
            dropdownHTML = `
                <button onclick="verFactura('${pedido}'); closeDropdown()" style="
                    width: 100%;
                    text-align: left;
                    padding: 0.875rem 1rem;
                    border: none;
                    background: transparent;
                    cursor: pointer;
                    color: #374151;
                    font-size: 0.875rem;
                    transition: all 0.2s ease;
                    display: flex;
                    align-items: center;
                    gap: 0.75rem;
                    font-weight: 500;
                " onmouseover="this.style.background='#f0f9ff'" onmouseout="this.style.background='transparent'">
                    <i class="fas fa-file-alt" style="color: #2563eb;"></i> Recibo de Costura
                </button>
            `;
        }
        
        // Agregar divisor y opción de seguimiento
        dropdownHTML += `
            <div style="height: 1px; background: #e5e7eb;"></div>
            <button onclick="verSeguimiento(${pedido}); closeDropdown()" style="
                width: 100%;
                text-align: left;
                padding: 0.875rem 1rem;
                border: none;
                background: transparent;
                cursor: pointer;
                color: #374151;
                font-size: 0.875rem;
                transition: all 0.2s ease;
                display: flex;
                align-items: center;
                gap: 0.75rem;
                font-weight: 500;
            " onmouseover="this.style.background='#f0fdf4'" onmouseout="this.style.background='transparent'">
                <i class="fas fa-tasks" style="color: #10b981;"></i> Seguimiento
            </button>
        `;
        
        dropdown.innerHTML = dropdownHTML;
        
        container.appendChild(dropdown);
        return dropdown;
    }
    
    // Crear dropdown dinámicamente (función original para compatibilidad)
    function crearDropdown(button) {
        const menuId = button.getAttribute('data-menu-id');
        const pedido = button.getAttribute('data-pedido');
        const estado = button.getAttribute('data-estado');
        const motivo = button.getAttribute('data-motivo');
        const usuario = button.getAttribute('data-usuario');
        const fecha = button.getAttribute('data-fecha');
        
        // Verificar si ya existe
        if (document.getElementById(menuId)) {
            return document.getElementById(menuId);
        }
        
        // Crear el dropdown
        const dropdown = document.createElement('div');
        dropdown.id = menuId;
        dropdown.className = 'dropdown-menu';
        dropdown.style.cssText = `
            position: fixed;
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
            min-width: 160px;
            display: none;
            z-index: 999999;
            overflow: visible;
            pointer-events: auto;
        `;
        
        // HTML del dropdown
        dropdown.innerHTML = `
            <button onclick="verFactura(${pedido}); closeDropdown()" title="Ver Detalle" style="
                width: 100%;
                text-align: center;
                padding: 0.875rem 1rem;
                border: none;
                background: transparent;
                cursor: pointer;
                color: #2563eb;
                font-size: 1.25rem;
                transition: all 0.2s ease;
                display: flex;
                align-items: center;
                justify-content: center;
            " onmouseover="this.style.background='#f0f9ff'; this.style.transform='scale(1.1)'" onmouseout="this.style.background='transparent'; this.style.transform='scale(1)'">
                <i class="fas fa-eye"></i>
            </button>
            <div style="height: 1px; background: #e5e7eb;"></div>
            <button onclick="verSeguimiento(${pedido}); closeDropdown()" title="Ver Seguimiento" style="
                width: 100%;
                text-align: center;
                padding: 0.875rem 1rem;
                border: none;
                background: transparent;
                cursor: pointer;
                color: #10b981;
                font-size: 1.25rem;
                transition: all 0.2s ease;
                display: flex;
                align-items: center;
                justify-content: center;
            " onmouseover="this.style.background='#f0fdf4'; this.style.transform='scale(1.1)'" onmouseout="this.style.background='transparent'; this.style.transform='scale(1)'">
                <i class="fas fa-tasks"></i>
            </button>
            ${estado === 'Anulada' ? `
                <div style="height: 1px; background: #e5e7eb;"></div>
                <button onclick="verMotivoanulacion(${pedido}, '${motivo}', '${usuario}', '${fecha}'); closeDropdown()" title="Ver Motivo de Anulación" style="
                    width: 100%;
                    text-align: center;
                    padding: 0.875rem 1rem;
                    border: none;
                    background: transparent;
                    cursor: pointer;
                    color: #ef4444;
                    font-size: 1.25rem;
                    transition: all 0.2s ease;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                " onmouseover="this.style.background='#fef2f2'; this.style.transform='scale(1.1)'" onmouseout="this.style.background='transparent'; this.style.transform='scale(1)'">
                    <i class="fas fa-info-circle"></i>
                </button>
            ` : `
                <div style="height: 1px; background: #e5e7eb;"></div>
                <button onclick="confirmarAnularPedido(${pedido}); closeDropdown()" title="Anular Pedido" style="
                    width: 100%;
                    text-align: center;
                    padding: 0.875rem 1rem;
                    border: none;
                    background: transparent;
                    cursor: pointer;
                    color: #f59e0b;
                    font-size: 1.25rem;
                    transition: all 0.2s ease;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                " onmouseover="this.style.background='#fef3c7'; this.style.transform='scale(1.1)'" onmouseout="this.style.background='transparent'; this.style.transform='scale(1)'">
                    <i class="fas fa-ban"></i>
                </button>
            `}
            <div style="height: 1px; background: #e5e7eb;"></div>
            <button onclick="confirmarEliminarPedido(${pedido}); closeDropdown()" title="Eliminar Pedido" style="
                width: 100%;
                text-align: center;
                padding: 0.875rem 1rem;
                border: none;
                background: transparent;
                cursor: pointer;
                color: #dc2626;
                font-size: 1.25rem;
                transition: all 0.2s ease;
                display: flex;
                align-items: center;
                justify-content: center;
            " onmouseover="this.style.background='#fee2e2'; this.style.transform='scale(1.1)'" onmouseout="this.style.background='transparent'; this.style.transform='scale(1)'">
                <i class="fas fa-trash-alt"></i>
            </button>
        `;
        
        container.appendChild(dropdown);
        return dropdown;
    }
    
    // Posicionar dropdown
    function posicionarDropdown(button, dropdown) {
        const buttonRect = button.getBoundingClientRect();
        const viewportHeight = window.innerHeight;
        const spaceBelow = viewportHeight - buttonRect.bottom;
        
        if (spaceBelow > menuHeight) {
            dropdown.style.top = (buttonRect.bottom + 8) + 'px';
        } else {
            dropdown.style.top = (buttonRect.top - menuHeight - 8) + 'px';
        }
        
        dropdown.style.left = buttonRect.left + 'px';
        
        console.log('Dropdown posicionado:', {
            buttonRect: {top: buttonRect.top, bottom: buttonRect.bottom, left: buttonRect.left},
            spaceBelow: spaceBelow,
            dropdownTop: dropdown.style.top,
            dropdownLeft: dropdown.style.left
        });
    }
    
    // Delegación de eventos para botón Ver
    document.addEventListener('click', function(e) {
        const buttonVer = e.target.closest('.btn-ver-dropdown');
        
        if (buttonVer) {
            e.stopPropagation();
            
            // Crear dropdown si no existe
            const dropdown = crearDropdownVer(buttonVer);
            
            // Cerrar todos los otros menús
            document.querySelectorAll('.dropdown-menu').forEach(m => {
                if (m.id !== dropdown.id) {
                    m.style.display = 'none';
                }
            });
            
            // Toggle el menú actual
            if (dropdown.style.display === 'none') {
                dropdown.style.display = 'block';
                posicionarDropdown(buttonVer, dropdown);
            } else {
                dropdown.style.display = 'none';
            }
        }
    });
    
    // Delegación de eventos para botones dropdown (compatibilidad)
    document.addEventListener('click', function(e) {
        const button = e.target.closest('.btn-acciones-dropdown');
        
        if (button) {
            e.stopPropagation();
            
            // Crear dropdown si no existe
            const dropdown = crearDropdown(button);
            
            // Cerrar todos los otros menús
            document.querySelectorAll('.dropdown-menu').forEach(m => {
                if (m.id !== dropdown.id) {
                    m.style.display = 'none';
                }
            });
            
            // Toggle el menú actual
            if (dropdown.style.display === 'none') {
                dropdown.style.display = 'block';
                posicionarDropdown(button, dropdown);
            } else {
                dropdown.style.display = 'none';
            }
        }
    });
    
    // Cerrar dropdown al hacer clic fuera
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.dropdown-menu') && !e.target.closest('.btn-acciones-dropdown') && !e.target.closest('.btn-ver-dropdown')) {
            document.querySelectorAll('.dropdown-menu').forEach(menu => {
                menu.style.display = 'none';
            });
        }
    });
    
    // Reposicionar dropdown cuando hace scroll
    document.addEventListener('scroll', function() {
        const openMenus = document.querySelectorAll('.dropdown-menu[style*="display: block"]');
        
        openMenus.forEach(menu => {
            const menuId = menu.id;
            const button = document.querySelector(`button[data-menu-id="${menuId}"]`);
            
            if (button) {
                posicionarDropdown(button, menu);
            }
        });
    }, true);
});

// Función para cerrar el dropdown (usada en los botones del menú) - GLOBAL
window.closeDropdown = function() {
    document.querySelectorAll('.dropdown-menu').forEach(menu => {
        menu.style.display = 'none';
    });
}
