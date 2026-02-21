/**
 * Dropdown Manager para Pedidos - Versión Mejorada
 * Crea dropdowns dinámicamente fuera de la tabla
 */

document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('dropdowns-container');
    const menuHeight = 150;
    
    // Limpiar modales abiertos al cargar la página
    const overlayFacturaViejo = document.getElementById('modal-factura-overlay');
    if (overlayFacturaViejo) {
        overlayFacturaViejo.remove();
    }
    
    // Crear dropdown para botón Ver
window.crearDropdownVer = function(button) {
        const menuId = button.getAttribute('data-menu-id');
        const pedido = button.getAttribute('data-pedido');
        const pedidoId = button.getAttribute('data-pedido-id'); // ID de pedidos_produccion
        const logoPedidoId = button.getAttribute('data-logo-pedido-id'); //  ID específico para logo_pedidos
        let tipoCotizacion = button.getAttribute('data-tipo-cotizacion');
        // Detectar si es LOGO explícito (atributo agregado a la vista)
        const esLogoAttr = button.getAttribute('data-es-logo');
        const esLogo = esLogoAttr === '1' || esLogoAttr === 'true';
        if (esLogo) {
            tipoCotizacion = 'L';
        }
        
        // SIEMPRE generar un menuId único basado en el pedidoId
        const uniqueMenuId = `menu-ver-pedido-${pedidoId}`;
        
        // IMPORTANTE: Limpiar galerías anteriores para evitar conflictos entre pedidos
        if (typeof window._limpiarGalerias === 'function') {
            window._limpiarGalerias();
        }
        
        // Verificar si ya existe
        if (document.getElementById(uniqueMenuId)) {
            return document.getElementById(uniqueMenuId);
        }
        
        // Crear el dropdown
        const dropdown = document.createElement('div');
        dropdown.id = uniqueMenuId;
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
        
        // Verificar si estamos en la ruta insumos/materiales
        const esRutaInsumos = window.location.pathname.includes('insumos/materiales');
        
        if (esRutaInsumos) {
            // Para insumos, mostrar "Ver Recibos" como en supervisor
            dropdownHTML = `
                <button onclick="(window.cerrarModalFactura && window.cerrarModalFactura()); abrirSelectorRecibos(${pedidoId}); closeDropdown()" style="
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
                " onmouseover="this.style.background='#fef3c7'" onmouseout="this.style.background='transparent'">
                    <i class="fas fa-receipt" style="color: #f59e0b;"></i> Ver Recibos
                </button>
            `;
        } else if (tipoCotizacion === 'L') {
            // Solo Logo
            dropdownHTML = `
                <button onclick="(window.cerrarModalFactura && window.cerrarModalFactura()); verFacturaLogo(${logoPedidoId}); closeDropdown()" style="
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
                <button onclick="(window.cerrarModalFactura && window.cerrarModalFactura()); verFacturaDelPedido('${pedido}', ${pedidoId}); closeDropdown()" style="
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
                    <i class="fas fa-receipt" style="color: #f59e0b;"></i> Ver Pedido
                </button>
                <div style="height: 1px; background: #e5e7eb;"></div>
                <button onclick="(window.cerrarModalFactura && window.cerrarModalFactura()); verFacturaLogo(${logoPedidoId}); closeDropdown()" style="
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
            // Prenda o Reflectivo (solo costura) - MODIFICADO: Usar modal de prendas como en registros
            console.log('[crearDropdownVer] Creando dropdown para pedido', {pedido, pedidoId, menuId});
            console.log('[crearDropdownVer] Número de pedido a usar:', pedido);
            dropdownHTML = `
                <button onclick="(window.cerrarModalFactura && window.cerrarModalFactura()); console.log('Dropdown click - pedido:', '${pedido}', 'pedidoId:', ${pedidoId}); verFacturaDelPedido('${pedido}', ${pedidoId}); closeDropdown()" style="
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
                    <i class="fas fa-list" style="color: #3b82f6;"></i> Ver Pedido
                </button>
            `;
        }
        
        // Verificar si estamos en la ruta supervisor-pedidos (mostrar "Ver Recibos" para supervisores)
        const esRutaSupervisor = window.location.pathname.includes('supervisor-pedidos');
        
        // Mostrar "Ver Recibos" si es supervisor-pedidos
        if (esRutaSupervisor) {
            dropdownHTML += `
                <div style="height: 1px; background: #e5e7eb;"></div>
                <button onclick="(window.cerrarModalFactura && window.cerrarModalFactura()); abrirSelectorRecibos(${pedidoId}); closeDropdown()" style="
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
                " onmouseover="this.style.background='#fef3c7'" onmouseout="this.style.background='transparent'">
                    <i class="fas fa-receipt" style="color: #f59e0b;"></i> Ver Recibos
                </button>
            `;
        }
        
        dropdownHTML += `
            <div style="height: 1px; background: #e5e7eb;"></div>
            <button onclick="verSeguimiento(${pedidoId}); closeDropdown()" style="
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
        
        // Ocultar "Observaciones despacho" si es supervisor-pedidos o insumos/materiales
        if (!esRutaSupervisor && !esRutaInsumos) {
            dropdownHTML += `
                <div style="height: 1px; background: #e5e7eb;"></div>
                <button onclick="abrirModalObservacionesDespachoAsesores(${pedidoId}, '${pedido}'); closeDropdown()" style="
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
                    <i class="fas fa-comment-dots" style="color: #3b82f6;"></i> Observaciones despacho
                </button>
            `;
        }
        
        dropdown.innerHTML = dropdownHTML;
        
        container.appendChild(dropdown);
        return dropdown;
    }
    
    // Crear dropdown dinámicamente (función original para compatibilidad)
    function crearDropdown(button) {
        const menuId = button.getAttribute('data-menu-id');
        const pedido = button.getAttribute('data-pedido');
        const pedidoId = button.getAttribute('data-pedido-id'); // ID de pedidos_produccion
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
            <button onclick="verSeguimiento(${pedidoId}); closeDropdown()" title="Ver Seguimiento" style="
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
            <div style="height: 1px; background: #e5e7eb;"></div>
            <button onclick="abrirModalObservacionesDespachoAsesores(${pedidoId}, '${pedido}'); closeDropdown()" title="Observaciones despacho" style="
                width: 100%;
                text-align: center;
                padding: 0.875rem 1rem;
                border: none;
                background: transparent;
                cursor: pointer;
                color: #3b82f6;
                font-size: 1.25rem;
                transition: all 0.2s ease;
                display: flex;
                align-items: center;
                justify-content: center;
            " onmouseover="this.style.background='#f0f9ff'; this.style.transform='scale(1.1)'" onmouseout="this.style.background='transparent'; this.style.transform='scale(1)'">
                <i class="fas fa-comment-dots"></i>
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
            <button onclick="eliminarPedidoDirecto(${pedidoId}); closeDropdown()" title="Eliminar Pedido" style="
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
    }
    
    // Delegación de eventos para botón Ver - Con cierre automático de otros
    document.addEventListener('click', function(e) {
        const buttonVer = e.target.closest('.btn-ver-dropdown');
        
        if (buttonVer) {
            const pedidoId = buttonVer.getAttribute('data-pedido-id');
            const pedido = buttonVer.getAttribute('data-pedido');
            const menuId = buttonVer.getAttribute('data-menu-id');
            
            console.log('[pedidos-dropdown] ========== CLICK EN BOTÓN VER ==========');
            console.log('[pedidos-dropdown] Atributos del botón clickeado:',{pedidoId, pedido, menuId});
            console.log('[pedidos-dropdown] Elemento clickeado:', e.target);
            console.log('[pedidos-dropdown] Button element:', buttonVer);
            
            e.stopPropagation();
            
            // IMPORTANTE: Cerrar TODOS los dropdowns abiertos PRIMERO
            console.log('[pedidos-dropdown] Cerrando todos los dropdowns...');
            document.querySelectorAll('.dropdown-menu').forEach(menu => {
                menu.style.display = 'none';
            });
            
            // IMPORTANTE: Cerrar modales de factura abiertos si existen
            const overlayFactura = document.getElementById('modal-factura-overlay');
            console.log('[pedidos-dropdown] Modal factura existe:', !!overlayFactura);
            
            if (overlayFactura && typeof window.cerrarModalFactura === 'function') {
                console.log('[pedidos-dropdown] Cerrando modal factura...');
                window.cerrarModalFactura();
            }
            
            // Crear dropdown si no existe
    const dropdown = crearDropdownVer(buttonVer);
            
            // Abrir solo el dropdown del botón clickeado
            dropdown.style.display = 'block';
            posicionarDropdown(buttonVer, dropdown);
            
            console.log('[pedidos-dropdown] Dropdown abierto para pedido:', pedidoId);
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

// Función para abrir modal de detalles desde asesores (similar a registros)
window.abrirModalDetallePedidoDesdeAsesores = async function(pedido, pedidoId) {
    console.log(' [abrirModalDetallePedidoDesdeAsesores] Iniciando para pedido:', pedido);
    
    try {
        // Mostrar loading
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                html: `
                    <div style="text-align: center; padding: 2rem;">
                        <div style="width: 60px; height: 60px; border: 4px solid #e5e7eb; border-top-color: #1e40af; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 1.5rem;"></div>
                        <p style="color: #6b7280; font-size: 14px; font-weight: 500; margin: 0;">Cargando detalles del pedido...</p>
                    </div>
                    <style>
                        @keyframes spin {
                            to { transform: rotate(360deg); }
                        }
                    </style>
                `,
                width: '300px',
                padding: '0',
                background: 'white',
                showConfirmButton: false,
                allowOutsideClick: false
            });
        }
        
        // Obtener datos del pedido
        const response = await fetch(`/api/pedidos/${pedidoId}`);
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        
        const result = await response.json();
        const datos = result.data || result;
        
        // Cerrar loading
        if (typeof Swal !== 'undefined') {
            Swal.close();
        }
        
        console.log(' [abrirModalDetallePedidoDesdeAsesores] Datos recibidos:', datos);
        
        // Usar la misma función que en registros
        if (typeof window.abrirModalDetallePedido === 'function') {
            window.abrirModalDetallePedido(datos);
        } else {
            console.error(' [abrirModalDetallePedidoDesdeAsesores] abrirModalDetallePedido no disponible');
            alert('Error: Sistema de modales no disponible');
        }
        
    } catch (error) {
        console.error(' [abrirModalDetallePedidoDesdeAsesores] Error:', error);
        
        if (typeof Swal !== 'undefined') {
            Swal.close();
        }
        
        alert('Error al cargar los detalles del pedido: ' + error.message);
    }
};

/**
 * NUEVA FUNCIÓN: abrirModalDetallePedido
 * Abre el modal de detalle del pedido con los datos recibidos
 */
window.abrirModalDetallePedido = function(datos) {
    console.log(' [abrirModalDetallePedido] Abriendo modal con datos:', datos);
    
    // Crear o reutilizar modal
    let modal = document.getElementById('modalDetallePedidoInfo');
    if (!modal) {
        // Crear modal si no existe
        modal = document.createElement('div');
        modal.id = 'modalDetallePedidoInfo';
        modal.className = 'modal-pedido-detalle';
        modal.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        `;
        document.body.appendChild(modal);
    }
    
    // Construir contenido del modal
    const contenido = `
        <div style="background: white; border-radius: 8px; padding: 30px; max-width: 600px; width: 90%; max-height: 80vh; overflow-y: auto;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 style="margin: 0; color: #333;">Detalle del Pedido ${datos.numero || datos.id}</h2>
                <button onclick="document.getElementById('modalDetallePedidoInfo').style.display='none'" 
                        style="background: none; border: none; font-size: 24px; cursor: pointer; color: #999;">×</button>
            </div>
            
            <div style="border-top: 1px solid #eee; padding-top: 20px;">
                <div style="margin-bottom: 15px;">
                    <strong>ID Pedido:</strong> ${datos.id || '-'}
                </div>
                <div style="margin-bottom: 15px;">
                    <strong>Número Pedido:</strong> ${datos.numero || datos.numero_pedido || '-'}
                </div>
                <div style="margin-bottom: 15px;">
                    <strong>Cliente:</strong> ${datos.cliente || '-'}
                </div>
                <div style="margin-bottom: 15px;">
                    <strong>Cliente ID:</strong> ${datos.cliente_id || '-'}
                </div>
                <div style="margin-bottom: 15px;">
                    <strong>Estado:</strong> <span style="background: #f0f0f0; padding: 4px 8px; border-radius: 4px;">${datos.estado || '-'}</span>
                </div>
                ${datos.fecha_creacion ? `
                <div style="margin-bottom: 15px;">
                    <strong>Fecha Creación:</strong> ${datos.fecha_creacion}
                </div>
                ` : ''}
                ${datos.cantidad_total ? `
                <div style="margin-bottom: 15px;">
                    <strong>Cantidad Total:</strong> ${datos.cantidad_total}
                </div>
                ` : ''}
            </div>
            
            <div style="margin-top: 20px; display: flex; gap: 10px;">
                <button onclick="document.getElementById('modalDetallePedidoInfo').style.display='none'" 
                        style="padding: 10px 20px; background: #f0f0f0; border: none; border-radius: 4px; cursor: pointer;">
                    Cerrar
                </button>
            </div>
        </div>
    `;
    
    modal.innerHTML = contenido;
    modal.style.display = 'flex';
    
    // Cerrar al hacer clic fuera
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.style.display = 'none';
        }
    });
};

// Cerrar dropdown y modales al hacer clic fuera
document.addEventListener('click', function(e) {
    // Cerrar dropdowns
    if (!e.target.closest('.dropdown-menu') && !e.target.closest('.btn-acciones-dropdown') && !e.target.closest('.btn-ver-dropdown')) {
        document.querySelectorAll('.dropdown-menu').forEach(menu => {
            menu.style.display = 'none';
        });
    }
});

