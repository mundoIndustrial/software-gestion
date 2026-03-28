/**
 * =====================================================
 * SUPERVISOR PEDIDOS INDEX - FUNCIONALIDAD PRINCIPAL
 * =====================================================
 *
 * Requiere: supervisor-pedidos/core/bootstrap.js → window.supervisorPedidos
 */

if (!window.supervisorPedidos?.isReady) {
    throw new Error('[index] window.supervisorPedidos no está disponible. Carga core/bootstrap.js ANTES.');
}

const _spFilter = window.supervisorPedidos.filterService;
const _spNotify = window.shared.notify;

// ===== VARIABLES GLOBALES =====
let filtroActual = null;

function _spEscapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function _spEscapeJsSingle(value) {
    return String(value ?? '').replace(/\\/g, '\\\\').replace(/'/g, "\\'");
}

function _spFormatDateTime(value) {
    if (!value) return '--';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return '--';
    const dd = String(date.getDate()).padStart(2, '0');
    const mm = String(date.getMonth() + 1).padStart(2, '0');
    const yyyy = date.getFullYear();
    const hh = String(date.getHours()).padStart(2, '0');
    const min = String(date.getMinutes()).padStart(2, '0');
    return `${dd}/${mm}/${yyyy} ${hh}:${min}`;
}

function _spCountNovedades(novedades) {
    const value = String(novedades ?? '').trim();
    if (!value) return 0;
    return value.split(/\n\s*\n/).filter(Boolean).length;
}

function _spStateBadge(estado) {
    const map = {
        PENDIENTE_SUPERVISOR: { bg: '#fff3cd', text: '#856404', label: 'Pendiente Supervisor' },
        PENDIENTE_INSUMOS: { bg: '#d1ecf1', text: '#0c5460', label: 'Pendiente Insumos' },
        'En Ejecución': { bg: '#d4edda', text: '#155724', label: 'En Ejecución' },
        'No iniciado': { bg: '#e2e3e5', text: '#383d41', label: 'No Iniciado' },
        Entregado: { bg: '#d4edda', text: '#155724', label: 'Entregado' },
        Finalizada: { bg: '#d4edda', text: '#155724', label: 'Finalizada' },
        Anulada: { bg: '#f8d7da', text: '#721c24', label: 'Anulada' },
        DEVUELTO_A_ASESORA: { bg: '#f8d7da', text: '#721c24', label: 'Devuelto' },
    };
    return map[estado] || { bg: '#e2e3e5', text: '#383d41', label: estado || '-' };
}

function _spBuildPageUrl(page) {
    const url = new URL(window.location.href);
    url.searchParams.set('page', String(page));
    return url.toString();
}

window.renderSupervisorOrdersTable = function renderSupervisorOrdersTable(payload) {
    const container = document.getElementById('supervisorPedidosIndexContent');
    if (!container) return;

    const ordenesData = payload?.ordenes || {};
    const rows = Array.isArray(ordenesData.data) ? ordenesData.data : [];
    const pedidosSeleccionados = Array.isArray(payload?.pedidosSeleccionados) ? payload.pedidosSeleccionados : [];

    const header = `
        <style>
            .sp-orders-grid {
                display: grid;
                grid-template-columns: 60px 220px 120px 200px 150px 140px 150px 150px 150px;
                gap: 1.2rem;
                min-width: max-content;
                box-sizing: border-box;
            }
            .sp-orders-grid > div { min-width: 0; }
        </style>
        <div style="background: #e5e7eb; border-radius: 8px; overflow: visible; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08); padding: 0.75rem; width: 100%; max-width: 100%;">
            <div class="table-scroll-container" style="overflow-x: auto; overflow-y: auto; width: 100%; max-width: 100%; max-height: 800px; border-radius: 6px; scrollbar-width: thin; scrollbar-color: #cbd5e1 #f1f5f9;">
                <div class="sp-orders-grid" style="background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%); color: white; padding: 0.75rem 1rem; font-weight: 600; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px; border-radius: 6px;">
                    <div class="th-wrapper" style="display: flex; align-items: center; justify-content: center; gap: 0.5rem;"><span>Listo</span></div>
                    <div class="th-wrapper" style="display: flex; align-items: center; justify-content: center; gap: 0.5rem;"><span>Acciones</span></div>
                    <div class="th-wrapper" style="display: flex; align-items: center; gap: 0.5rem;"><span>Fecha</span><button type="button" class="btn-filter-column" data-col="fecha" title="Filtrar Fecha" style="display: flex; align-items: center; background: none; border: none; color: white; cursor: pointer; padding: 0;"><span class="material-symbols-rounded" style="font-size: 1rem;">filter_alt</span></button></div>
                    <div class="th-wrapper" style="display: flex; align-items: center; gap: 0.5rem;"><span>Número</span><button type="button" class="btn-filter-column" data-col="numero" title="Filtrar Número" style="display: flex; align-items: center; background: none; border: none; color: white; cursor: pointer; padding: 0;"><span class="material-symbols-rounded" style="font-size: 1rem;">filter_alt</span></button></div>
                    <div class="th-wrapper" style="display: flex; align-items: center; gap: 0.5rem;"><span>Cliente</span><button type="button" class="btn-filter-column" data-col="cliente" title="Filtrar Cliente" style="display: flex; align-items: center; background: none; border: none; color: white; cursor: pointer; padding: 0;"><span class="material-symbols-rounded" style="font-size: 1rem;">filter_alt</span></button></div>
                    <div class="th-wrapper" style="display: flex; align-items: center; gap: 0.5rem;"><span>Estado</span><button type="button" class="btn-filter-column" data-col="estado" title="Filtrar Estado" style="display: flex; align-items: center; background: none; border: none; color: white; cursor: pointer; padding: 0;"><span class="material-symbols-rounded" style="font-size: 1rem;">filter_alt</span></button></div>
                    <div class="th-wrapper" style="display: flex; align-items: center; gap: 0.5rem;"><span>Novedades</span></div>
                    <div class="th-wrapper" style="display: flex; align-items: center; gap: 0.5rem;"><span>Asesora</span><button type="button" class="btn-filter-column" data-col="asesora" title="Filtrar Asesora" style="display: flex; align-items: center; background: none; border: none; color: white; cursor: pointer; padding: 0;"><span class="material-symbols-rounded" style="font-size: 1rem;">filter_alt</span></button></div>
                    <div class="th-wrapper" style="display: flex; align-items: center; gap: 0.5rem;"><span>Forma Pago</span><button type="button" class="btn-filter-column" data-col="forma_pago" title="Filtrar Forma Pago" style="display: flex; align-items: center; background: none; border: none; color: white; cursor: pointer; padding: 0;"><span class="material-symbols-rounded" style="font-size: 1rem;">filter_alt</span></button></div>
                </div>
    `;

    let body = '';
    if (rows.length === 0) {
        body = `<div style="padding: 3rem 2rem; text-align: center; color: #6b7280;"><i class="fas fa-inbox" style="font-size: 3rem; color: #d1d5db; margin-bottom: 1rem; display: block;"></i><p style="font-size: 1rem; margin: 0;">No hay órdenes disponibles</p></div>`;
    } else {
        body = rows.map((orden) => {
            const isSelected = pedidosSeleccionados.includes(orden.id);
            const numeroPedido = orden.numero_pedido ?? 'sin-numero';
            const numeroPedidoText = _spEscapeHtml(`#${numeroPedido}`);
            const numeroPedidoNoHash = String(numeroPedido).replace(/#/g, '');
            const estado = orden.estado ?? 'Pendiente';
            const estadoInfo = _spStateBadge(estado);
            const novedadesCount = _spCountNovedades(orden.novedades);
            const novedadesJson = _spEscapeHtml(JSON.stringify(orden.novedades ?? ''));
            const asesora = _spEscapeHtml(orden?.asesora?.name ?? 'N/A');
            const formaPago = _spEscapeHtml(orden?.forma_de_pago ?? 'N/A');
            const cliente = _spEscapeHtml(orden?.cliente ?? '');
            const fecha = _spFormatDateTime(orden?.created_at);
            const canApprove = estado === 'PENDIENTE_SUPERVISOR' && !Boolean(orden?.es_solo_epp);
            const jsNumero = _spEscapeJsSingle(numeroPedidoNoHash);
            const jsNumeroHash = _spEscapeJsSingle(String(numeroPedido));

            return `
                <div class="sp-orders-grid" style="padding: 1rem; border-bottom: 1px solid #e5e7eb; align-items: center; background: ${isSelected ? '#d1d5db' : 'white'}; transition: background 0.2s ease;"
                    onmouseover="if (!this.dataset.seleccionado || this.dataset.seleccionado === 'false') this.style.background='#f9fafb'"
                    onmouseout="this.style.background = (this.dataset.seleccionado === 'true') ? '#d1d5db' : 'white'"
                    data-seleccionado="${isSelected ? 'true' : 'false'}"
                    data-pedido-row="true"
                    data-pedido-id="${orden.id}">
                    <div style="display: flex; align-items: center; justify-content: center;">
                        <input type="checkbox" class="pedido-checkbox" data-pedido-id="${orden.id}" title="Seleccionar pedido" style="width: 18px; height: 18px; cursor: pointer;" ${isSelected ? 'checked' : ''}>
                    </div>
                    <div style="display: flex; gap: 0.5rem; align-items: center; justify-content: center;">
                        <button class="btn-accion btn-accion--ver btn-ver-dropdown" data-menu-id="menu-ver-${numeroPedidoNoHash}" data-pedido="${numeroPedidoNoHash}" data-pedido-id="${orden.id}" title="Ver Opciones"><i class="fas fa-eye"></i></button>
                        ${canApprove ? `<button class="btn-accion btn-accion--aprobar" onclick="abrirModalAprobacion(${orden.id}, '${jsNumero}')" title="Aprobar Pedido"><i class="fas fa-check"></i></button>` : ''}
                        ${canApprove ? `<button class="btn-accion btn-accion--anular" onclick="abrirModalAnulacion(${orden.id}, '${jsNumeroHash}')" title="Pasar a Revisión"><i class="fas fa-ban"></i></button>` : ''}
                        <button class="btn-accion btn-accion--ocultar" onclick="abrirModalOcultar(${orden.id}, '${jsNumero}')" title="Ocultar Pedido"><i class="fas fa-eye-slash"></i></button>
                    </div>
                    <div><span style="font-size: 0.85rem; color: #6b7280;">${fecha}</span></div>
                    <div><span style="font-weight: 600; color: #1e5ba8;">${numeroPedidoText}</span></div>
                    <div><span>${cliente}</span></div>
                    <div><span style="background: ${estadoInfo.bg}; color: ${estadoInfo.text}; padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: bold; white-space: nowrap; display: inline-block;">${_spEscapeHtml(estadoInfo.label)}</span></div>
                    <div>
                        ${novedadesCount > 0
                            ? `<button class="btn-novedades" type="button" data-orden-id="${orden.id}" data-novedades="${novedadesJson}" style="background: #e8f3ff; color: #1e40af; padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: bold; white-space: nowrap; border: 1px solid #bfdbfe; cursor: pointer; transition: all 0.2s ease;">${novedadesCount} novedades</button>`
                            : `<span style="background: #f3f4f6; color: #9ca3af; padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: bold; white-space: nowrap;">Sin novedades</span>`
                        }
                    </div>
                    <div><span>${asesora}</span></div>
                    <div><span>${formaPago}</span></div>
                </div>
            `;
        }).join('');
    }

    const footerTop = `</div></div>`;
    const currentPage = Number(ordenesData.current_page || 1);
    const lastPage = Number(ordenesData.last_page || 1);
    const total = Number(ordenesData.total || 0);
    let pagination = '';

    if (lastPage > 1 || rows.length > 0) {
        const prevDisabled = currentPage <= 1;
        const nextDisabled = currentPage >= lastPage;
        const makeBtn = (label, page, disabled = false) => {
            if (disabled) {
                return `<button disabled style="min-width: 36px; height: 36px; padding: 0 12px; background: #f0f0f0; border: 1px solid #ddd; border-radius: 6px; cursor: not-allowed; color: #999; font-weight: 600;">${label}</button>`;
            }
            return `<a href="${_spEscapeHtml(_spBuildPageUrl(page))}" style="min-width: 36px; height: 36px; padding: 0 12px; background: #ffffff; border: 1px solid #ddd; border-radius: 6px; cursor: pointer; color: #333; font-weight: 600; display: flex; align-items: center; justify-content: center; text-decoration: none;">${label}</a>`;
        };

        let pageButtons = '';
        for (let page = 1; page <= lastPage; page += 1) {
            if (page === currentPage) {
                pageButtons += `<button disabled style="min-width: 36px; height: 36px; padding: 0 8px; background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); border: 1px solid #1d4ed8; border-radius: 6px; color: white; font-weight: 600; cursor: default;">${page}</button>`;
            } else {
                pageButtons += `<a href="${_spEscapeHtml(_spBuildPageUrl(page))}" style="min-width: 36px; height: 36px; padding: 0 8px; background: #ffffff; border: 1px solid #ddd; border-radius: 6px; cursor: pointer; color: #333; font-weight: 600; display: flex; align-items: center; justify-content: center; text-decoration: none;">${page}</a>`;
            }
        }

        pagination = `
            <div style="margin-top: 1.5rem; display: flex; justify-content: center; align-items: center; gap: 8px; flex-wrap: wrap;">
                ${makeBtn('&laquo;&laquo;', 1, prevDisabled)}
                ${makeBtn('&larr; Anterior', Math.max(1, currentPage - 1), prevDisabled)}
                ${pageButtons}
                ${makeBtn('Siguiente &rarr;', Math.min(lastPage, currentPage + 1), nextDisabled)}
                ${makeBtn('&raquo;&raquo;', lastPage, nextDisabled)}
                <span style="margin-left: 1rem; color: #666; font-size: 14px; font-weight: 500;">Página ${currentPage} de ${lastPage} | Total: ${total} registros</span>
            </div>
        `;
    }

    container.innerHTML = `${header}${body}${footerTop}${pagination}`;
};

// ===== TOGGLE MENU ACCIONES =====
function toggleAcciones(event, ordenId) {
    event.stopPropagation();
    const menu = document.getElementById(`menu-${ordenId}`);

    document.querySelectorAll('.action-menu:not([style*="display: none"])').forEach(m => {
        if (m.id !== `menu-${ordenId}`) {
            m.style.display = 'none';
        }
    });

    if (menu.style.display === 'none' || menu.style.display === '') {
        menu.style.display = 'block';
    } else {
        menu.style.display = 'none';
    }
}

document.addEventListener('click', function(e) {
    if (!e.target.closest('.action-menu') && !e.target.closest('.action-view-btn')) {
        document.querySelectorAll('.action-menu').forEach(menu => {
            menu.style.display = 'none';
        });
    }
});

window.addEventListener('resize', () => closeAllVerMenus());
document.addEventListener('scroll', () => closeAllVerMenus(), true);

// ===== FILTROS HELPERS =====
function getValoresFiltroDesdeURL(columna) {
    return _spFilter.getActiveFilterValues(columna);
}

function asegurarBadgeEnBoton(btn) {
    if (!btn) return null;
    let badge = btn.querySelector('.filter-badge');
    if (!badge) {
        badge = document.createElement('span');
        badge.className = 'filter-badge';
        badge.textContent = '0';
        btn.appendChild(badge);
    }
    return badge;
}

function actualizarIndicadoresFiltros() {
    const botones = document.querySelectorAll('#supervisorPedidosIndexContent .btn-filter-column');
    if (!botones || botones.length === 0) return;

    botones.forEach(btn => {
        const columna = resolveFilterColumn(btn);
        const valores = columna ? getValoresFiltroDesdeURL(columna) : [];
        const cantidad = valores.length;
        const badge = asegurarBadgeEnBoton(btn);
        if (cantidad > 0) {
            btn.classList.add('has-filter');
            if (badge) badge.textContent = String(cantidad);
        } else {
            btn.classList.remove('has-filter');
            if (badge) badge.textContent = '0';
        }
    });
}


function resolveFilterColumn(btn) {
    if (!btn) return '';

    const col = (btn.getAttribute('data-col') || '').trim();
    if (col) return col;

    // Compatibilidad con botones viejos sin data-col
    const title = btn.getAttribute('title') || '';
    switch (title) {
        case 'Filtrar Fecha': return 'fecha';
        case 'Filtrar N�mero':
        case 'Filtrar Número': return 'numero';
        case 'Filtrar Cliente': return 'cliente';
        case 'Filtrar Estado': return 'estado';
        case 'Filtrar Asesora': return 'asesora';
        case 'Filtrar Forma Pago': return 'forma_pago';
        default: return '';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    actualizarIndicadoresFiltros();
    navegarSupervisorPedidos(window.location.href, { pushState: false });
});

// ===== MENU VER ORDEN =====
const SP_VER_MENU_CLASS = 'sp-ver-dropdown-menu';

function closeAllVerMenus(exceptId = null) {
    document.querySelectorAll(`.${SP_VER_MENU_CLASS}`).forEach((menu) => {
        if (!exceptId || menu.id !== exceptId) {
            menu.style.display = 'none';
        }
    });
}

function getOrCreateVerMenu(button) {
    const pedidoId = button.getAttribute('data-pedido-id');
    const numeroPedido = button.getAttribute('data-pedido') || pedidoId;
    const menuId = button.getAttribute('data-menu-id') || `menu-ver-${pedidoId}`;
    let menu = document.getElementById(menuId);

    if (menu) return menu;

    menu = document.createElement('div');
    menu.id = menuId;
    menu.className = SP_VER_MENU_CLASS;
    menu.style.cssText = `
        position: fixed;
        min-width: 220px;
        background: #ffffff;
        border: 1px solid #d1d5db;
        border-radius: 12px;
        box-shadow: 0 14px 30px rgba(15, 23, 42, 0.22);
        overflow: hidden;
        z-index: 999999;
        display: none;
    `;

    const buildMenuOption = (btn, iconClass, text) => {
        btn.innerHTML = `
            <span style="display:inline-flex;align-items:center;gap:10px;">
                <i class="${iconClass}" style="width:16px;text-align:center;color:#374151;"></i>
                <span>${text}</span>
            </span>
        `;
    };

    const opcionVer = document.createElement('button');
    opcionVer.type = 'button';
    buildMenuOption(opcionVer, 'fas fa-eye', 'Ver Pedido');
    opcionVer.style.cssText = 'width:100%;border:none;background:#ffffff;padding:12px 14px;text-align:left;cursor:pointer;color:#111827;font-size:15px;font-weight:600;line-height:1.2;transition:background-color .15s ease;';
    opcionVer.onmouseover = () => { opcionVer.style.backgroundColor = '#eef2ff'; };
    opcionVer.onmouseout = () => { opcionVer.style.backgroundColor = '#ffffff'; };
    opcionVer.onclick = () => {
        closeAllVerMenus();
        verOrdenDetalles(pedidoId, numeroPedido);
    };

    menu.appendChild(opcionVer);

    if (typeof window.abrirSelectorRecibos === 'function') {
        const separadorRecibos = document.createElement('div');
        separadorRecibos.style.cssText = 'height:1px;background:#e5e7eb;';

        const opcionRecibos = document.createElement('button');
        opcionRecibos.type = 'button';
        buildMenuOption(opcionRecibos, 'fas fa-receipt', 'Ver Recibos');
        opcionRecibos.style.cssText = 'width:100%;border:none;background:#ffffff;padding:12px 14px;text-align:left;cursor:pointer;color:#111827;font-size:15px;font-weight:600;line-height:1.2;transition:background-color .15s ease;';
        opcionRecibos.onmouseover = () => { opcionRecibos.style.backgroundColor = '#eef2ff'; };
        opcionRecibos.onmouseout = () => { opcionRecibos.style.backgroundColor = '#ffffff'; };
        opcionRecibos.onclick = () => {
            closeAllVerMenus();
            window.abrirSelectorRecibos(pedidoId);
        };

        menu.appendChild(separadorRecibos);
        menu.appendChild(opcionRecibos);
    }

    const separadorSeguimiento = document.createElement('div');
    separadorSeguimiento.style.cssText = 'height:1px;background:#e5e7eb;';

    const opcionSeguimiento = document.createElement('button');
    opcionSeguimiento.type = 'button';
    buildMenuOption(opcionSeguimiento, 'fas fa-tasks', 'Seguimiento');
    opcionSeguimiento.style.cssText = 'width:100%;border:none;background:#ffffff;padding:12px 14px;text-align:left;cursor:pointer;color:#111827;font-size:15px;font-weight:600;line-height:1.2;transition:background-color .15s ease;';
    opcionSeguimiento.onmouseover = () => { opcionSeguimiento.style.backgroundColor = '#eef2ff'; };
    opcionSeguimiento.onmouseout = () => { opcionSeguimiento.style.backgroundColor = '#ffffff'; };
    opcionSeguimiento.onclick = () => {
        closeAllVerMenus();
        abrirSeguimiento(pedidoId);
    };

    menu.appendChild(separadorSeguimiento);
    menu.appendChild(opcionSeguimiento);

    document.body.appendChild(menu);
    return menu;
}

function positionVerMenu(button, menu) {
    const rect = button.getBoundingClientRect();
    const margin = 8;
    const menuWidth = menu.offsetWidth || 180;
    const menuHeight = menu.offsetHeight || 130;

    // Abrir a la derecha del botón por defecto.
    let left = rect.right + margin;
    let top = rect.top;

    // Si no hay espacio a la derecha, usar el lado izquierdo como respaldo.
    if (left + menuWidth > window.innerWidth - margin) {
        left = rect.left - menuWidth - margin;
    }

    if (left < margin) left = margin;
    if (top + menuHeight > window.innerHeight - margin) {
        top = Math.max(margin, rect.top - menuHeight - margin);
    }

    menu.style.left = `${left}px`;
    menu.style.top = `${top}px`;
}

document.addEventListener('click', function(e) {
    if (!e.target.closest('.btn-ver-dropdown') && !e.target.closest(`.${SP_VER_MENU_CLASS}`)) {
        closeAllVerMenus();
    }
});

// ===== FILTROS DE COLUMNAS =====

function abrirModalFiltro(columna) {
    filtroActual = columna;
    const modalTitulo = document.getElementById('modalFiltroTitulo');
    const filtroContenido = document.getElementById('filtroContenido');
    const modal = document.getElementById('modalFiltro');

    let titulo = '';
    let campoNombre = '';

    switch(columna) {
        case 'id-orden':
            titulo = 'Filtrar por ID Orden';
            campoNombre = 'numero';
            break;
        case 'numero':
            titulo = 'Filtrar por Número';
            campoNombre = 'numero';
            break;
        case 'cliente':
            titulo = 'Filtrar por Cliente';
            campoNombre = 'cliente';
            break;
        case 'fecha':
            titulo = 'Filtrar por Fecha';
            campoNombre = 'fecha';
            break;
        case 'estado': {
            titulo = 'Filtrar por Estado';
            campoNombre = 'estado';
            const estadosDisplay = ['Pendiente', 'No iniciado', 'En Ejecución', 'Entregado', 'Anulada', 'Pendiente Supervisor', 'Pendiente Insumos', 'Pendiente Cartera', 'Rechazado Cartera', 'Devuelto a Asesora'];
            const estadosDB    = ['Pendiente', 'No iniciado', 'En Ejecución', 'Entregado', 'Anulada', 'PENDIENTE_SUPERVISOR', 'PENDIENTE_INSUMOS', 'pendiente_cartera', 'RECHAZADO_CARTERA', 'DEVUELTO_A_ASESORA'];
            filtroContenido.innerHTML = `
                <div class="form-group">
                    <input type="text" id="buscadorEstado" class="form-control" placeholder="Buscar estado..." style="margin-bottom: 1rem;">
                    <div id="listaEstados">
                        ${estadosDisplay.map((display, i) => `
                            <label style="display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem; cursor: pointer; border-radius: 4px;">
                                <input type="checkbox" name="estado" value="${estadosDB[i]}" class="filtro-checkbox">
                                <span>${display}</span>
                            </label>
                        `).join('')}
                    </div>
                </div>
            `;
            const seleccionados = new Set(getValoresFiltroDesdeURL('estado'));
            document.querySelectorAll('#listaEstados .filtro-checkbox').forEach(cb => {
                cb.checked = seleccionados.has(cb.value);
            });
            setTimeout(() => {
                document.getElementById('buscadorEstado')?.addEventListener('input', function(e) {
                    const valor = e.target.value.toLowerCase();
                    document.querySelectorAll('#listaEstados label').forEach(label => {
                        label.style.display = label.textContent.toLowerCase().includes(valor) ? 'flex' : 'none';
                    });
                });
            }, 0);
            modal.style.display = 'flex';
            return;
        }
        case 'asesora':
            titulo = 'Filtrar por Asesora';
            campoNombre = 'asesora';
            break;
        case 'forma-pago':
        case 'forma_pago':
            titulo = 'Filtrar por Forma de Pago';
            campoNombre = 'forma_pago';
            break;
    }

    if (campoNombre && columna !== 'estado') {
        cargarOpcionesFiltro(campoNombre, titulo, modal, filtroContenido);
    }
}

function cargarOpcionesFiltro(campo, titulo, modal, filtroContenido) {
    _spFilter.loadFilterOptions(campo)
        .then(data => {
            const modalTitulo = document.getElementById('modalFiltroTitulo');
            modalTitulo.textContent = titulo;

            filtroContenido.innerHTML = `
                <div class="form-group">
                    <input type="text" id="buscadorFiltro" class="form-control" placeholder="Buscar..." style="margin-bottom: 1rem;">
                    <div id="listaOpciones" style="max-height: 300px; overflow-y: auto;"></div>
                    <div id="paginacionFiltro" style="display:flex;align-items:center;justify-content:space-between;gap:0.5rem;margin-top:0.75rem;">
                        <button type="button" id="btnFiltroPrev" class="btn btn-sm btn-light" style="border:1px solid #d1d5db;">Anterior</button>
                        <span id="filtroPaginaInfo" style="font-size:0.85rem;color:#6b7280;"></span>
                        <button type="button" id="btnFiltroNext" class="btn btn-sm btn-light" style="border:1px solid #d1d5db;">Siguiente</button>
                    </div>
                </div>
            `;

            const buscador = document.getElementById('buscadorFiltro');
            const lista    = document.getElementById('listaOpciones');
            const paginacion = document.getElementById('paginacionFiltro');
            const btnPrev = document.getElementById('btnFiltroPrev');
            const btnNext = document.getElementById('btnFiltroNext');
            const paginaInfo = document.getElementById('filtroPaginaInfo');
            const columnaMap = (campo === 'forma_pago') ? 'forma_pago' : campo;
            const seleccionados = new Set(getValoresFiltroDesdeURL(columnaMap));
            const opciones = Array.isArray(data.opciones) ? data.opciones : [];
            const pageSize = 20;
            let currentPage = 1;
            let lastQuery = '';

            function normalizarTexto(v) {
                return String(v || '').toLowerCase();
            }

            function renderOpciones(query) {
                const q = normalizarTexto(query).trim();
                const queryChanged = q !== lastQuery;
                if (queryChanged) currentPage = 1;
                lastQuery = q;

                const filtered = q === ''
                    ? opciones
                    : opciones.filter(op => normalizarTexto(op).includes(q));

                const totalItems = filtered.length;
                const totalPages = Math.max(1, Math.ceil(totalItems / pageSize));
                if (currentPage > totalPages) currentPage = totalPages;
                const start = (currentPage - 1) * pageSize;
                const end = start + pageSize;
                const list = filtered.slice(start, end);

                if (!lista) return;

                if (list.length === 0) {
                    lista.innerHTML = `<div style="padding:0.5rem;color:#6b7280;">Sin resultados</div>`;
                } else {
                    lista.innerHTML = list.map(opcion => {
                    const safeValue = (opcion === null || opcion === undefined) ? '' : String(opcion);
                    const checked = seleccionados.has(safeValue) ? 'checked' : '';
                    let labelValue = safeValue || '(Sin especificar)';

                    if (campo === 'fecha' && safeValue) {
                        // Mostrar fecha amigable manteniendo valor ISO para el filtro.
                        const parts = safeValue.split('-');
                        if (parts.length === 3) {
                            labelValue = `${parts[2]}/${parts[1]}/${parts[0]}`;
                        }
                    }
                    return `
                        <label style="display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem; cursor: pointer; border-radius: 4px; transition: background 0.2s;">
                            <input type="checkbox" name="${campo}" value="${safeValue}" class="filtro-checkbox" ${checked}>
                            <span>${labelValue}</span>
                        </label>
                    `;
                    }).join('');
                }

                if (paginacion && btnPrev && btnNext && paginaInfo) {
                    const shouldShowPagination = totalItems > pageSize;
                    paginacion.style.display = shouldShowPagination ? 'flex' : 'none';
                    btnPrev.disabled = currentPage <= 1;
                    btnNext.disabled = currentPage >= totalPages;
                    paginaInfo.textContent = `Página ${currentPage} de ${totalPages}`;
                }
            }

            if (lista) {
                lista.addEventListener('change', function(e) {
                    const cb = e.target;
                    if (!cb?.classList?.contains('filtro-checkbox')) return;
                    const val = String(cb.value ?? '');
                    cb.checked ? seleccionados.add(val) : seleccionados.delete(val);
                });
            }

            renderOpciones('');

            if (buscador) {
                buscador.addEventListener('input', e => renderOpciones(e.target.value));
            }

            btnPrev?.addEventListener('click', function() {
                if (currentPage <= 1) return;
                currentPage -= 1;
                renderOpciones(lastQuery);
            });

            btnNext?.addEventListener('click', function() {
                currentPage += 1;
                renderOpciones(lastQuery);
            });

            modal.style.display = 'flex';
        })
        .catch(() => {
            filtroContenido.innerHTML = `<p style="color: red;">Error cargando opciones de filtro</p>`;
            modal.style.display = 'flex';
        });
}

function cerrarModalFiltro() {
    document.getElementById('modalFiltro').style.display = 'none';
    filtroActual = null;
}

function aplicarFiltroColumna(event) {
    event.preventDefault();

    const valoresSeleccionados = Array.from(
        document.querySelectorAll('.filtro-checkbox:checked')
    ).map(cb => cb.value);

    const filteredUrl = _spFilter.buildFilteredUrl(filtroActual, valoresSeleccionados);

    cerrarModalFiltro();
    navegarSupervisorPedidos(filteredUrl);
}

async function navegarSupervisorPedidos(urlString, options = {}) {
    const success = await _spFilter.navigateAjax(urlString, options);

    if (success) {
        actualizarIndicadoresFiltros();
        window.dispatchEvent(new Event('supervisorPedidos:filtersUpdated'));

        setTimeout(() => {
            if (typeof window.cargarSeleccionesGuardadas === 'function') {
                window.cargarSeleccionesGuardadas();
            }
        }, 300);
    }
}

window.addEventListener('popstate', function() {
    navegarSupervisorPedidos(window.location.href, { pushState: false });
    window.dispatchEvent(new Event('supervisorPedidos:filtersUpdated'));
});

document.addEventListener('click', function(e) {
    const a = e.target.closest('#supervisorPedidosIndexContent a');
    if (!a) return;
    const href = a.getAttribute('href');
    if (!href || href.startsWith('#')) return;
    if (a.target && a.target !== '_self') return;
    if (a.hasAttribute('download')) return;
    if (!href.startsWith(window.location.origin) && !href.startsWith('/')) return;
    const urlAbs = href.startsWith('http') ? href : (window.location.origin + href);
    let path = '';
    try { path = new URL(urlAbs).pathname || ''; } catch (e) { return; }
    if (!path.startsWith('/supervisor-pedidos')) return;
    e.preventDefault();
    navegarSupervisorPedidos(urlAbs);
});

// Cerrar modal al hacer clic fuera
document.getElementById('modalFiltro')?.addEventListener('click', function(e) {
    if (e.target === this) cerrarModalFiltro();
});

// ===== MODALES DE ÓRDENES =====
function verOrdenComparar(ordenId) {
    document.getElementById(`ver-menu-${ordenId}`).style.display = 'none';
    abrirModalComparar(ordenId);
}

function cerrarModalVerOrden() {
    document.getElementById('modalVerOrden').style.display = 'none';
}

// Contador de caracteres del textarea de anulación
document.getElementById('motivoAnulacion')?.addEventListener('input', function() {
    document.getElementById('contadorActual').textContent = this.value.length;
    const btnConfirmar = document.getElementById('btnConfirmarAnulacion');
    if (btnConfirmar) {
        btnConfirmar.disabled = this.value.length < 10 || this.value.length > 500;
    }
});

// Cerrar modales al hacer clic fuera
document.getElementById('modalVerOrden')?.addEventListener('click', function(e) {
    if (e.target === this) cerrarModalVerOrden();
});

document.getElementById('modalAnulacion')?.addEventListener('click', function(e) {
    if (e.target === this) cerrarModalAnulacion();
});

document.getElementById('modalExitoRevision')?.addEventListener('click', function(e) {
    if (e.target === this) cerrarModalExitoRevision();
});

document.getElementById('modalOcultar')?.addEventListener('click', function(e) {
    if (e.target === this) cerrarModalOcultar();
});

document.getElementById('modalExitoOcultar')?.addEventListener('click', function(e) {
    if (e.target === this) cerrarModalExitoOcultar();
});

// Delegado: novedades y filtros de columna
document.addEventListener('click', function(e) {
    const btnVerDropdown = e.target.closest('.btn-ver-dropdown');
    if (btnVerDropdown) {
        e.preventDefault();
        e.stopPropagation();
        const menu = getOrCreateVerMenu(btnVerDropdown);
        const isOpen = menu.style.display === 'block';
        closeAllVerMenus(menu.id);
        if (!isOpen) {
            menu.style.display = 'block';
            positionVerMenu(btnVerDropdown, menu);
        } else {
            menu.style.display = 'none';
        }
        return;
    }

    const btnNovedades = e.target.closest('.btn-novedades');
    if (btnNovedades) {
        e.preventDefault();
        const ordenId = btnNovedades.dataset.ordenId;
        const novedadesJson = btnNovedades.getAttribute('data-novedades');
        try {
            const novedades = JSON.parse(novedadesJson);
            abrirNovedades(ordenId, novedades);
        } catch (err) {
            console.error('[Novedades] Error al parsear JSON:', err);
        }
        return;
    }

    const btnFiltro = e.target.closest('.btn-filter-column');
    if (btnFiltro) {
        e.preventDefault();
        e.stopPropagation();
        const columna = resolveFilterColumn(btnFiltro);
        if (!columna) return;
        abrirModalFiltro(columna);
    }
});

// Función para aprobar orden
async function aprobarOrden(ordenId, numeroOrden) {
    const result = await _spNotify.confirm(`¿Deseas aprobar el pedido <strong>#${numeroOrden}</strong>?`, '¿Aprobar Pedido?');

    if (!result.isConfirmed) return;

    Swal.fire({
        title: 'Procesando...',
        html: '<p>Por favor espera mientras se aprueba el pedido</p>',
        icon: 'info',
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => { Swal.showLoading(); }
    });

    try {
        const data = await window.shared.http.post(`/api/supervisor-pedidos/ordenes/${ordenId}/aprobar`, {});

        if (data.success) {
            Swal.fire({
                title: '¡Aprobado!',
                html: `<p>${data.message || 'Pedido aprobado correctamente'}</p><p style="margin-top: 10px; font-weight: 600; color: #10b981;">Estado: ${data.estado}</p>`,
                icon: 'success',
                confirmButtonColor: '#10b981'
            }).then(() => {
                if (typeof cargarNotificacionesPendientes === 'function') {
                    cargarNotificacionesPendientes();
                }
                setTimeout(() => location.reload(), 1000);
            });
        } else {
            _spNotify.error(data.message || 'No se pudo aprobar el pedido');
        }
    } catch (error) {
        console.error('[aprobarOrden] Error:', error);
        _spNotify.error('Error al procesar la solicitud');
    }
}

function verOrdenDetalles(ordenId, numeroPedido = null) {
    const menu = document.getElementById(`ver-menu-${ordenId}`);
    if (menu) menu.style.display = 'none';

    if (typeof window.verFacturaDelPedido !== 'function') {
        console.error('[verOrdenDetalles] verFacturaDelPedido no esta disponible');
        _spNotify.error('No se pudo abrir el detalle del pedido.');
        return;
    }

    try {
        const numero = String(numeroPedido || ordenId || '');
        window.verFacturaDelPedido(numero, Number(ordenId));
    } catch (error) {
        console.error('[verOrdenDetalles] Error abriendo factura:', error);
        _spNotify.error('No se pudo abrir la factura del pedido.');
    }
}

function abrirSeguimiento(ordenId) {
    const menu = document.getElementById(`ver-menu-${ordenId}`);
    if (menu) menu.style.display = 'none';

    if (typeof openOrderTrackingModal === 'function') {
        try {
            openOrderTrackingModal(ordenId);
        } catch (error) {
            console.error('[abrirSeguimiento] Error:', error);
            _spNotify.error('Error en modal de seguimiento: ' + error.message);
        }
    } else {
        console.error('[abrirSeguimiento] openOrderTrackingModal no está disponible');
        _spNotify.error('El modal de seguimiento no está disponible. Intenta nuevamente.');
    }
}

async function editarPedido(pedidoId) {
    if (window.edicionEnProgreso) return;
    window.edicionEnProgreso = true;

    try {
        await _ensureSwal();

        Swal.fire({
            html: `
                <div style="text-align: center; padding: 2rem;">
                    <div style="width: 60px; height: 60px; border: 4px solid #e5e7eb; border-top-color: #1e40af; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 1.5rem;"></div>
                    <p style="color: #6b7280; font-size: 14px; font-weight: 500; margin: 0;">Cargando datos del pedido...</p>
                </div>
                <style>@keyframes spin { to { transform: rotate(360deg); } }</style>
            `,
            width: '300px',
            padding: '0',
            background: 'white',
            showConfirmButton: false,
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => { document.body.style.overflow = 'hidden'; }
        });

        if (!window.PrendaEditorPreloader?.isReady?.()) {
            await window.PrendaEditorPreloader.loadWithLoader({ title: 'Cargando datos', message: 'Por favor espera...' });
        }

        const respuesta = await window.shared.http.get(`/api/pedidos/${pedidoId}`);
        if (!respuesta.success) throw new Error(respuesta.message || 'Error desconocido');

        const datos = respuesta.data || respuesta.datos;
        const datosTransformados = {
            id: datos.id || datos.numero_pedido,
            numero_pedido: datos.numero_pedido || datos.numero,
            numero: datos.numero || datos.numero_pedido,
            cliente: datos.cliente || 'Cliente sin especificar',
            asesora: datos.asesor || datos.asesora?.name || 'Asesor sin especificar',
            estado: datos.estado || 'Pendiente',
            forma_de_pago: datos.forma_pago || datos.forma_de_pago || 'No especificada',
            prendas: datos.prendas || [],
            epps: datos.epps_transformados || datos.epps || [],
            procesos: datos.procesos || [],
            ...datos
        };

        await abrirModalEditarPedido(pedidoId, datosTransformados, 'editar');

    } catch (err) {
        Swal.close();
        console.error('[editarPedido] Error:', err);
        _spNotify.error('No se pudo cargar el pedido: ' + err.message);
    } finally {
        window.edicionEnProgreso = false;
    }
}



