/**
 * SISTEMA DE ORDENES - VERSION 2 (TABLA SIMPLIFICADA)
 * Script principal solo para tabla y filtros rapidos
 */

document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
});

let currentQuickFilter = 'todos';
let paginationDelegatedBound = false;

function initializeApp() {
    console.log(' Inicializando sistema de Ordenes - Vista Tabla');
    
    initializeFilters();
    initializeSearch();
    initializeActionMenus();
    initializeCheckboxes();
    initializePaginationAjax();
}

/**
 * ============================================
 * GESTION DE VISTAS (DESHABILITADA - Solo Tabla)
 * ============================================
 */
// Las vistas alternativas estan deshabilitadas en esta version

/**
 * ============================================
 * SISTEMA DE FILTROS
 * ============================================
 */
function initializeFilters() {
    const filterBtns = document.querySelectorAll('.filter-btn');

    filterBtns.forEach(btn => {
        if (btn.dataset.filterBound === '1') {
            return;
        }

        btn.dataset.filterBound = '1';
        btn.addEventListener('click', function() {
            const status = this.dataset.status || 'todos';
            applyQuickFilter(status);
        });
    });

    const urlStatus = new URL(window.location.href).searchParams.get('status') || currentQuickFilter;
    applyQuickFilter(urlStatus, { skipServer: true });
}

function applyQuickFilter(status, options = {}) {
    const { skipServer = false } = options;
    currentQuickFilter = status || 'todos';

    const filterBtns = document.querySelectorAll('.filter-btn');
    filterBtns.forEach(btn => {
        const isActive = (btn.dataset.status || 'todos') === currentQuickFilter;
        btn.classList.toggle('active', isActive);
    });

    const isRegistrosPage = window.location.pathname.includes('/registros');
    if (isRegistrosPage && !skipServer) {
        applyServerStatusFilter(currentQuickFilter);
        return;
    }

    filterByStatus(currentQuickFilter === 'todos' ? null : currentQuickFilter);
}

function applyServerStatusFilter(status) {
    const url = new URL(window.location.href);
    const current = (url.searchParams.get('status') || 'todos').trim();
    const next = (status || 'todos').trim();

    if (next === 'todos') {
        url.searchParams.delete('status');
    } else {
        url.searchParams.set('status', next);
    }
    url.searchParams.set('page', '1');

    if (current === next) {
        return;
    }

    refreshOrdersTable(url);
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
    
    console.log(`âœ“ Mostrando ${visibleCount} registros`);
}

function getStatusMatch(filterStatus, rowStatus) {
    if (filterStatus === 'en-progreso') {
        const allowedKeywords = [
            'ejecuci\u00f3n', 
            'insumos', 
            'supervisor', 
            'asesora', 
            'pendiente', 
            'no iniciado'
        ];
        return allowedKeywords.some(keyword => rowStatus.includes(keyword));
    }
    
    return rowStatus.includes(filterStatus);
}

/**
 * ============================================
 * BUSQUEDA GLOBAL
 * ============================================
 */
function initializeSearch() {
    let searchInput = document.getElementById('navSearchInput');
    if (!searchInput) {
        searchInput = document.querySelector('.nav-search-input');
    }
    const clearBtn = document.getElementById('navSearchClear');
    const isRegistrosPage = window.location.pathname.includes('/registros');
    
    if (!searchInput) return;
    
    // Debounce para no buscar en cada tecla
    let searchTimeout;
    
    searchInput.addEventListener('input', function(e) {
        clearTimeout(searchTimeout);
        const rawQuery = e.target.value.trim();
        const query = rawQuery.toLowerCase();

        if (clearBtn) {
            clearBtn.style.display = query ? 'flex' : 'none';
        }
        
        searchTimeout = setTimeout(() => {
            if (isRegistrosPage) {
                applyServerSearch(rawQuery);
                return;
            }
            syncSearchParamInUrl(rawQuery);
            performSearch(query);
        }, 300);
    });

    if (clearBtn) {
        // Captura el click antes que nav-search.js para evitar que rompa la paginacion de /registros
        clearBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopImmediatePropagation();
            searchInput.value = '';
            clearBtn.style.display = 'none';
            if (isRegistrosPage) {
                applyServerSearch('');
                return;
            }
            syncSearchParamInUrl('');
            performSearch('');
            searchInput.focus();
        }, true);
    }

    // Si viene con ?search=... en URL, reflejarlo en el input.
    const queryFromUrl = new URL(window.location.href).searchParams.get('search') || '';
    if (queryFromUrl) {
        searchInput.value = queryFromUrl;
        if (clearBtn) {
            clearBtn.style.display = 'flex';
        }
        if (!isRegistrosPage) {
            performSearch(queryFromUrl.trim().toLowerCase());
        }
    }
}

function applyServerSearch(query) {
    const url = new URL(window.location.href);
    const current = (url.searchParams.get('search') || '').trim();
    const next = query.trim();

    if (next) {
        url.searchParams.set('search', next);
        url.searchParams.set('page', '1');
    } else {
        url.searchParams.delete('search');
        url.searchParams.set('page', '1');
    }

    if (current === next && next !== '') {
        return;
    }
    refreshOrdersTable(url);
}

function syncSearchParamInUrl(query) {
    const url = new URL(window.location.href);
    if (query) {
        url.searchParams.set('search', query);
    } else {
        url.searchParams.delete('search');
    }
    window.history.replaceState({}, '', url.toString());
}

async function refreshOrdersTable(url) {
    try {
        const response = await fetch(url.toString(), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html'
            }
        });

        if (!response.ok) {
            window.location.assign(url.toString());
            return;
        }

        const html = await response.text();
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');

        const newView = doc.querySelector('#view-tabla');
        const currentView = document.querySelector('#view-tabla');

        if (!newView || !currentView) {
            window.location.assign(url.toString());
            return;
        }

        currentView.innerHTML = newView.innerHTML;
        window.history.replaceState({}, '', url.toString());
        currentQuickFilter = url.searchParams.get('status') || 'todos';

        // Reenlazar eventos para el contenido reemplazado
        initializeFilters();
        initializeActionMenus();
        initializeCheckboxes();
    } catch (error) {
        window.location.assign(url.toString());
    }
}

function initializePaginationAjax() {
    if (paginationDelegatedBound) {
        return;
    }

    paginationDelegatedBound = true;
    document.addEventListener('click', function(e) {
        const link = e.target.closest('#view-tabla a[href*=\"page=\"]');
        if (!link) {
            return;
        }

        const isRegistrosPage = window.location.pathname.includes('/registros');
        if (!isRegistrosPage) {
            return;
        }

        e.preventDefault();
        const url = new URL(link.href, window.location.origin);

        if (currentQuickFilter && currentQuickFilter !== 'todos') {
            url.searchParams.set('status', currentQuickFilter);
        }

        refreshOrdersTable(url);
    });
}

function performSearch(query) {
    console.log(`ðŸ”Ž Buscando: "${query}"`);
    
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
 * MENOS DE ACCIONES
 * ============================================
 */
function initializeActionMenus() {
    console.log(' Inicializando menos de acciones...');
    
    const menuBtns = document.querySelectorAll('.btn-menu-actions, .btn-card-menu');
    console.log(' Botones encontrados:', menuBtns.length);
    
    menuBtns.forEach((btn, index) => {
        console.log(` Agregando listener al boton ${index}:`, btn);
        
        btn.addEventListener('click', function(e) {
            console.log(' Click en boton de menu', this);
            e.preventDefault();
            e.stopPropagation();
            
            // Buscar el menu siguiente
            const menu = this.nextElementSibling;
            console.log(' Elemento siguiente:', menu);
            console.log(' Â¿Tiene clase action-menu?:', menu?.classList.contains('action-menu'));
            
            if (menu && menu.classList.contains('action-menu')) {
                console.log(' Menu encontrado, cerrando otros...');
                
                // Cerrar otros menus primero
                document.querySelectorAll('.action-menu').forEach(m => {
                    if (m !== menu) {
                        console.log(' Cerrando menu:', m);
                        m.style.display = 'none';
                    }
                });
                
                // Toggle el menu actual
                const isVisible = menu.style.display !== 'none';
                console.log(' Menu actualmente visible:', isVisible);
                
                if (isVisible) {
                    menu.style.display = 'none';
                } else {
                    menu.style.display = 'block';
                    // Posicionar el menu fixed basado en el botón
                    posicionarMenuFixed(this, menu);
                }
                console.log(' Display actualizado a:', menu.style.display);
            } else {
                console.log(' No se encontró menú con clase action-menu');
            }
        });
    });
    
    // Manejar clicks en items del menu de acciones (solo dentro de .action-menu)
    document.querySelectorAll('.action-menu .menu-item').forEach(item => {
        item.addEventListener('click', function(e) {
            console.log(' Click en item del menu:', this);
            e.preventDefault();
            
            // Obtener la accion y el ID de la orden
            const action = this.getAttribute('data-action');
            const button = this.closest('.action-menu').previousElementSibling;
            const ordenId = button?.getAttribute('data-orden-id');
            
            console.log(` Accion: ${action}, Orden: ${ordenId}`);
            
            // Ejecutar la accion
            if (action && ordenId) {
                handleMenuAction(e, action, ordenId);
            }
            
            // Cerrar el menu
            const menu = this.closest('.action-menu');
            if (menu) {
                console.log(' Cerrando menu despues de click en item');
                menu.style.display = 'none';
            }
        });
    });
    
    // Cerrar menus cuando se hace click fuera
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.action-menu') && !e.target.closest('.btn-menu-actions') && !e.target.closest('.btn-card-menu')) {
            console.log(' Click fuera, cerrando todos los menus');
            document.querySelectorAll('.action-menu').forEach(menu => {
                menu.style.display = 'none';
            });
        }
    });
    
    console.log(' Menus de acciones inicializados');
}

// Funcion para posicionar el menu en coordenadas fixed
function posicionarMenuFixed(buttonEl, menuEl) {
    const rect = buttonEl.getBoundingClientRect();
    
    // Posicionar debajo del boton y alineado a la DERECHA
    menuEl.style.top = (rect.bottom + 5) + 'px';
    menuEl.style.left = (rect.right + 5) + 'px'; // Menu comienza a la derecha del boton
    menuEl.style.right = 'auto'; // Asegurar que no hay conflicto con right
    
    console.log(' Menu posicionado en:', {
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
// Funcionalidad deshabilitada en esta version

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

// Funcion para abrir modal de detalles
function openDetailModal(ordenId, numeroPedido) {
    console.log(` Abriendo factura - Orden: ${ordenId}, Pedido: ${numeroPedido}`);
    
    // Abrir la factura usando InvoiceLazyLoader
    if (typeof globalThis.verFacturaDelPedido === 'function') {
        console.log('âœ“ Cargando factura con verFacturaDelPedido');
        globalThis.verFacturaDelPedido(numeroPedido, ordenId);
    } else {
        console.warn(' verFacturaDelPedido no disponible, usando fallback');
        // Fallback: abrir en nueva ventana
        globalThis.open(`/asesores/recibos/${ordenId}`, '_blank');
    }
}

// Funcion para renderizar el HTML del recibo
function renderizarRecibo(datos) {
    // Debugging: ver que estructura trae el JSON
    console.log('[renderizarRecibo] Datos recibidos:', datos);
    
    // Manejar estructura de respuesta que puede traer 'data' o datos directos
    const datosRecibo = datos.data || datos;
    
    // Template basico del recibo
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

// Funcion para accion de menu
function handleMenuAction(event, action, ordenId) {
    event.preventDefault();
    
    // Obtener el numeroPedido del boton
    const button = event.target.closest('.action-menu').previousElementSibling;
    const numeroPedido = button?.getAttribute('data-numero-pedido');
    
    console.log(` Accion: ${action} para orden: ${ordenId}, Pedido: ${numeroPedido}`);
    
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

