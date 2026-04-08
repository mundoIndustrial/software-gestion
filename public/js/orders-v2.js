/**
 * SISTEMA DE ÓRDENES - VERSIÓN 2 (TABLA SIMPLIFICADA)
 * Script principal solo para tabla y filtros rápidos
 */

document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
});

function initializeApp() {
    console.log('🚀 Inicializando sistema de órdenes - Vista Tabla');
    
    initializeFilters();
    initializeSearch();
    initializeActionMenus();
    initializeCheckboxes();
}

/**
 * ============================================
 * GESTIÓN DE VISTAS (DESHABILITADA - Solo Tabla)
 * ============================================
 */
// Las vistas alternativas están deshabilitadas en esta versión

/**
 * ============================================
 * SISTEMA DE FILTROS
 * ============================================
 */
function initializeFilters() {
    const filterBtns = document.querySelectorAll('.filter-btn');
    
    filterBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const status = this.dataset.status;
            
            if (status === 'todos') {
                // Mostrar todos
                filterByStatus(null);
                filterBtns.forEach(b => b.classList.remove('active'));
                this.classList.add('active');
            } else {
                // Filtrar por status específico
                filterByStatus(status);
                filterBtns.forEach(b => b.classList.remove('active'));
                this.classList.add('active');
            }
        });
    });
    
    // Trigger the "todos" filter on page load to show all rows
    filterByStatus(null);
}

function filterByStatus(status) {
    console.log(` Filtrando por estado: ${status || 'todos'}`);
    
    const tableRows = document.querySelectorAll('.orders-table tbody tr');
    let visibleCount = 0;
    
    tableRows.forEach(row => {
        if (status === null) {
            row.style.display = '';
            visibleCount++;
        } else {
            let shouldShow = false;
            
            if (status === 'vencidos') {
                // Filtrar por fecha de entrega retrasada
                shouldShow = row.getAttribute('data-vencido') === 'true';
            } else if (status === 'entregados') {
                // Filtrar por estado "Entregado"
                const badge = row.querySelector('.badge');
                shouldShow = badge && badge.textContent.toLowerCase().includes('entregado');
            } else {
                const badge = row.querySelector('.badge');
                if (badge) {
                    const estadoText = badge.textContent.trim().toLowerCase();
                    shouldShow = getStatusMatch(status, estadoText);
                }
            }
            
            row.style.display = shouldShow ? '' : 'none';
            if (shouldShow) visibleCount++;
        }
    });
    
    console.log(`✓ Mostrando ${visibleCount} registros`);
}

function getStatusMatch(filterStatus, rowStatus) {
    const statusMap = {
        'en-progreso': 'ejecuci\u00f3n'
    };
    
    const mappedStatus = statusMap[filterStatus] || filterStatus;
    return rowStatus.includes(mappedStatus);
}

/**
 * ============================================
 * BÚSQUEDA GLOBAL
 * ============================================
 */
function initializeSearch() {
    const searchInput = document.getElementById('globalSearch');
    
    if (!searchInput) return;
    
    // Debounce para no buscar en cada tecla
    let searchTimeout;
    
    searchInput.addEventListener('input', function(e) {
        clearTimeout(searchTimeout);
        const query = e.target.value.toLowerCase();
        
        searchTimeout = setTimeout(() => {
            performSearch(query);
        }, 300);
    });
}

function performSearch(query) {
    console.log(`🔎 Buscando: "${query}"`);
    
    if (query === '') {
        // Mostrar todo
        document.querySelectorAll('.orders-table tbody tr').forEach(row => row.style.display = '');
        return;
    }
    
    // Buscar en tabla
    document.querySelectorAll('.orders-table tbody tr').forEach(row => {
        const pedido = row.querySelector('.col-pedido')?.textContent.toLowerCase() || '';
        const cliente = row.querySelector('.col-cliente')?.textContent.toLowerCase() || '';
        
        const matches = pedido.includes(query) || cliente.includes(query);
        row.style.display = matches ? '' : 'none';
    });
}

/**
 * ============================================
 * MENÚ DE ACCIONES
 * ============================================
 */
function initializeActionMenus() {
    console.log(' Inicializando menús de acciones...');
    
    const menuBtns = document.querySelectorAll('.btn-menu-actions, .btn-card-menu');
    console.log(' Botones encontrados:', menuBtns.length);
    
    menuBtns.forEach((btn, index) => {
        console.log(` Agregando listener al botón ${index}:`, btn);
        
        btn.addEventListener('click', function(e) {
            console.log(' Click en botón de menú', this);
            e.preventDefault();
            e.stopPropagation();
            
            // Buscar el menú siguiente
            const menu = this.nextElementSibling;
            console.log(' Elemento siguiente:', menu);
            console.log(' ¿Tiene clase action-menu?:', menu?.classList.contains('action-menu'));
            
            if (menu && menu.classList.contains('action-menu')) {
                console.log(' Menú encontrado, cerrando otros...');
                
                // Cerrar otros menús primero
                document.querySelectorAll('.action-menu').forEach(m => {
                    if (m !== menu) {
                        console.log(' Cerrando menú:', m);
                        m.style.display = 'none';
                    }
                });
                
                // Toggle el menú actual
                const isVisible = menu.style.display !== 'none';
                console.log(' Menú actualmente visible:', isVisible);
                
                if (isVisible) {
                    menu.style.display = 'none';
                } else {
                    menu.style.display = 'block';
                    // Posicionar el menú fixed basado en el botón
                    posicionarMenuFixed(this, menu);
                }
                console.log(' Display actualizado a:', menu.style.display);
            } else {
                console.log(' No se encontró menú con clase action-menu');
            }
        });
    });
    
    // Manejar clicks en items del menú de acciones (solo dentro de .action-menu)
    document.querySelectorAll('.action-menu .menu-item').forEach(item => {
        item.addEventListener('click', function(e) {
            console.log(' Click en item del menú:', this);
            e.preventDefault();
            
            // Obtener la acción y el ID de la orden
            const action = this.getAttribute('data-action');
            const button = this.closest('.action-menu').previousElementSibling;
            const ordenId = button?.getAttribute('data-orden-id');
            
            console.log(` Acción: ${action}, Orden: ${ordenId}`);
            
            // Ejecutar la acción
            if (action && ordenId) {
                handleMenuAction(e, action, ordenId);
            }
            
            // Cerrar el menú
            const menu = this.closest('.action-menu');
            if (menu) {
                console.log(' Cerrando menú después de click en item');
                menu.style.display = 'none';
            }
        });
    });
    
    // Cerrar menús cuando se hace click fuera
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.action-menu') && !e.target.closest('.btn-menu-actions') && !e.target.closest('.btn-card-menu')) {
            console.log(' Click fuera, cerrando todos los menús');
            document.querySelectorAll('.action-menu').forEach(menu => {
                menu.style.display = 'none';
            });
        }
    });
    
    console.log(' Menús de acciones inicializados');
}

// Función para posicionar el menú en coordenadas fixed
function posicionarMenuFixed(buttonEl, menuEl) {
    const rect = buttonEl.getBoundingClientRect();
    
    // Posicionar debajo del botón y alineado a la DERECHA
    menuEl.style.top = (rect.bottom + 5) + 'px';
    menuEl.style.left = (rect.right + 5) + 'px'; // Menú comienza a la derecha del botón
    menuEl.style.right = 'auto'; // Asegurar que no hay conflicto con right
    
    console.log(' Menú posicionado en:', {
        top: menuEl.style.top,
        left: menuEl.style.left,
        buttonRight: rect.right
    });
}

/**
 * ============================================
 * DRAG & DROP KANBAN (DESHABILITADA)
 * ============================================
 */
// Funcionalidad deshabilitada en esta versión

/**
 * ============================================
 * UTILIDADES
 * ============================================
 */

/**
 * ============================================
 * CHECKBOXES (TABLA)
 * ============================================
 */
function initializeCheckboxes() {
    const checkboxAll = document.querySelector('.checkbox-all');
    const rowCheckboxes = document.querySelectorAll('.row-checkbox');
    
    if (checkboxAll) {
        checkboxAll.addEventListener('change', function() {
            rowCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });
    }
    
    rowCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const allChecked = Array.from(rowCheckboxes).every(cb => cb.checked);
            const someChecked = Array.from(rowCheckboxes).some(cb => cb.checked);
            
            if (checkboxAll) {
                checkboxAll.checked = allChecked;
                checkboxAll.indeterminate = someChecked && !allChecked;
            }
        });
    });
}

// Función para abrir modal de detalles
function openDetailModal(ordenId, numeroPedido) {
    console.log(` Abriendo factura - Orden: ${ordenId}, Pedido: ${numeroPedido}`);
    
    // Abrir la factura usando InvoiceLazyLoader
    if (typeof globalThis.verFacturaDelPedido === 'function') {
        console.log('✓ Cargando factura con verFacturaDelPedido');
        globalThis.verFacturaDelPedido(numeroPedido, ordenId);
    } else {
        console.warn(' verFacturaDelPedido no disponible, usando fallback');
        // Fallback: abrir en nueva ventana
        globalThis.open(`/asesores/recibos/${ordenId}`, '_blank');
    }
}

// Función para renderizar el HTML del recibo
function renderizarRecibo(datos) {
    // Debugging: ver qué estructura trae el JSON
    console.log('[renderizarRecibo] Datos recibidos:', datos);
    
    // Manejar estructura de respuesta que puede traer 'data' o datos directos
    const datosRecibo = datos.data || datos;
    
    // Template básico del recibo
    return `
        <div style="background: white; padding: 8px; border-radius: 4px; max-width: 100%; margin: 0 auto; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size: 11px;">
            <!-- Header Profesional -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 8px; padding-bottom: 6px; border-bottom: 2px solid #ddd; align-items: start;">
                <div style="font-size: 13px;">
                    <div style="font-weight: 700; color: #1a3a52; font-size: 13px; margin-bottom: 2px;">${datosRecibo.cliente?.nombre || datosRecibo.cliente || 'Cliente'}</div>
                    <div style="color: #666; font-size: 13px;">Asesor: ${datosRecibo.asesor?.nombre || datosRecibo.asesor || datosRecibo.nombre_asesor || 'N/A'}</div>
                    <div style="color: #666; font-size: 13px; margin-top: 3px;">Forma de Pago: <span style="font-weight: 600; color: #1a3a52;">${datosRecibo.forma_pago || datosRecibo.metodo_pago || 'N/A'}</span></div>
                    ${datosRecibo.orden_compra ? `<div style="color: #666; font-size: 13px; margin-top: 3px;"><strong>Orden de Compra:</strong> ${datosRecibo.orden_compra}</div>` : ''}
                    ${datosRecibo.observaciones ? `<div style="color: #666; font-size: 13px; margin-top: 3px;"><strong>Observaciones:</strong> ${datosRecibo.observaciones}</div>` : ''}
                </div>
                <div style="text-align: right; font-size: 13px;">
                    <div style="font-weight: 700; color: #1a3a52; font-size: 13px; margin-bottom: 2px;">RECIBO DE PEDIDO #${datosRecibo.numero_pedido || datosRecibo.id || 'N/A'}</div>
                    <div style="color: #666; font-size: 13px;">${datosRecibo.fecha || new Date().toLocaleDateString('es-ES')}</div>
                </div>
            </div>

            <!-- Prendas -->
            <div style="margin-top: 6px;">
                ${(datosRecibo.prendas || datosRecibo.items || []).map((prenda, idx) => `
                    <div style="background: white; border: 1px solid #ddd; border-radius: 3px; padding: 8px; margin-bottom: 8px;">
                        <div style="background: #f0f0f0; padding: 6px 8px; margin: -8px -8px 8px -8px; border-bottom: 2px solid #2c3e50;">
                            <span style="font-weight: 700; color: #2c3e50; font-size: 11px;">PRENDA ${idx + 1}</span>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px;">
                            <div>
                                <div style="font-weight: 700; color: #2c3e50; margin-bottom: 3px;">${prenda.nombre || 'Prenda'}</div>
                                <div style="color: #666; font-size: 11px;">${prenda.descripcion || ''}</div>
                            </div>
                            <div style="font-size: 11px;">
                                <strong>Tela:</strong> ${prenda.tela || prenda.tela_nombre || 'N/A'}<br>
                                <strong>Color:</strong> ${prenda.color || prenda.color_nombre || 'N/A'}
                            </div>
                            <div style="font-size: 11px;">
                                <strong>Talla:</strong> ${prenda.talla || prenda.talla_nombre || 'N/A'}<br>
                                <strong>Cantidad:</strong> ${prenda.cantidad || '0'}
                            </div>
                        </div>
                    </div>
                `).join('') || '<div style="color: #999; text-align: center; padding: 20px;">Sin prendas registradas</div>'}
            </div>
        </div>
    `;
}

// Función para acción de menú
function handleMenuAction(event, action, ordenId) {
    event.preventDefault();
    
    // Obtener el numeroPedido del botón
    const button = event.target.closest('.action-menu').previousElementSibling;
    const numeroPedido = button?.getAttribute('data-numero-pedido');
    
    console.log(` Acción: ${action} para orden: ${ordenId}, Pedido: ${numeroPedido}`);
    
    switch(action) {
        case 'detalle':
            console.log(' Abriendo factura del pedido');
            openDetailModal(ordenId, numeroPedido);
            break;
        case 'recibos':
            console.log(' Abriendo selector de recibos');
            if (typeof globalThis.abrirSelectorRecibos === 'function') {
                globalThis.abrirSelectorRecibos(ordenId);
            } else {
                console.error('abrirSelectorRecibos no disponible');
            }
            break;
        case 'seguimiento':
            console.log(' Abriendo seguimiento');
            if (typeof globalThis.openOrderTracking === 'function') {
                globalThis.openOrderTracking(ordenId, true);
            } else {
                console.error('openOrderTracking no disponible');
            }
            break;
    }
}

// Exportar funciones globales
globalThis.filterByStatus = filterByStatus;
globalThis.performSearch = performSearch;
globalThis.openDetailModal = openDetailModal;
globalThis.handleMenuAction = handleMenuAction;
