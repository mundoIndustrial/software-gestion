/**
 * Admin Talleres - Dashboard SPA Logic
 */

let currentState = {
    view: 'talleres', // talleres, recibos, entregas
    selectedTaller: null,
    selectedRecibo: null,
    activeTab: 'activos',
    ordenesTab: 'pedidos'
};

document.addEventListener('DOMContentLoaded', function() {
    initTalleresSearch();
    initOrdenesTabs();
    initViewHandlers();
    initReciboCompletoEvents();
    initStatusToggles();
    initNewTallerModal();
    initEditTaller();
    initSidebarNavigation();
    initInitialStatus();
    initSidebarToggle();
    initNestedMenuToggle();
    restoreLastViewFromSession();
});

function initInitialStatus() {
    const url = new URL(window.location.href);
    const statusParam = url.searchParams.get('status');
    currentState.activeTab = statusParam === 'inactivos' ? 'inactivos' : 'activos';
    console.log('[TalleresSidebar:initInitialStatus]', {
        pathname: window.location.pathname,
        search: window.location.search,
        statusParam,
        activeTab: currentState.activeTab
    });
}

function initSidebarToggle() {
    const toggleBtn = document.getElementById('sidebarToggle');
    if (!toggleBtn) return;

    const storageKey = 'talleres.sidebar.collapsed';
    const isCollapsed = localStorage.getItem(storageKey) === '1';
    if (isCollapsed) {
        document.body.classList.add('talleres-sidebar-collapsed');
    }

    toggleBtn.addEventListener('click', function () {
        const collapsed = document.body.classList.toggle('talleres-sidebar-collapsed');
        localStorage.setItem(storageKey, collapsed ? '1' : '0');
    });
}

function initNestedMenuToggle() {
    initMenuGroupToggle('navTalleresGroup', 'talleresSubmenu', 'talleres.menu.expanded', false);
    initMenuGroupToggle('navPrestamosGroup', 'prestamosSubmenu', 'prestamos.menu.expanded', true);
}

function initMenuGroupToggle(toggleId, submenuId, storageKey, expandedByDefault = false) {
    const groupToggle = document.getElementById(toggleId);
    const submenu = document.getElementById(submenuId);
    const debugPrefix = '[TalleresSidebar]';
    
    if (!groupToggle || !submenu) return;

    const isExpanded = localStorage.getItem(storageKey) !== null 
        ? localStorage.getItem(storageKey) === '1' 
        : expandedByDefault; // Use default if not in localStorage
    
    // Set initial state
    if (isExpanded) {
        groupToggle.classList.add('expanded');
        submenu.classList.remove('collapsed');
    } else {
        groupToggle.classList.remove('expanded');
        submenu.classList.add('collapsed');
    }
    
    // Toggle on click
    groupToggle.addEventListener('click', function(e) {
        console.log(debugPrefix, 'group toggle click', {
            toggleId,
            submenuId,
            defaultPrevented: e.defaultPrevented,
            currentUrl: window.location.href
        });
        e.preventDefault();
        e.stopPropagation();
        
        const isCurrentlyExpanded = groupToggle.classList.contains('expanded');
        
        if (isCurrentlyExpanded) {
            groupToggle.classList.remove('expanded');
            submenu.classList.add('collapsed');
            localStorage.setItem(storageKey, '0');
        } else {
            groupToggle.classList.add('expanded');
            submenu.classList.remove('collapsed');
            localStorage.setItem(storageKey, '1');
        }
    });
    
    // Handle subitem clicks
    const subitems = submenu.querySelectorAll('.sidebar-subitem');
    subitems.forEach(item => {
        item.addEventListener('click', function(e) {
            console.log(debugPrefix, 'submenu item click', {
                id: this.id || null,
                tag: this.tagName,
                href: this.getAttribute('href'),
                dataView: this.dataset.view || null,
                dataStatus: this.dataset.status || null,
                defaultPrevented: e.defaultPrevented,
                currentUrl: window.location.href
            });
            e.stopPropagation();
        });
    });
}

function initEditTaller() {
    const editButtons = document.querySelectorAll('.btn-edit-taller');
    const modal = document.getElementById('modalEditTaller');
    const closeElements = document.querySelectorAll('.close-modal-talleres-edit, .close-modal-btn-edit');
    const form = document.getElementById('formEditTaller');
    const inputId = document.getElementById('editTallerId');
    const inputName = document.getElementById('editTallerName');
    
    const mainContainer = document.querySelector('.main-container');
    const csrfToken = mainContainer.dataset.csrfToken;
    const updateRouteBase = mainContainer.dataset.routeUpdate;

    editButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            const id = this.dataset.id;
            const name = this.dataset.name;
            
            inputId.value = id;
            inputName.value = name;
            modal.classList.add('show');
        });
    });

    closeElements.forEach(el => {
        el.addEventListener('click', () => {
            modal.classList.remove('show');
        });
    });

    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const id = inputId.value;
            const newName = inputName.value;
            const finalRoute = updateRouteBase.replace(':id', id);
            const submitBtn = this.querySelector('button[type="submit"]');

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<div class="loading-spinner" style="width:16px; height:16px; margin:0; border-width:2px;"></div> Guardando...';

            fetch(finalRoute, {
                method: 'PATCH',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ name: newName })
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    modal.classList.remove('show');
                    Swal.fire({
                        title: '¡Actualizado!',
                        text: result.message,
                        icon: 'success',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<span class="material-symbols-rounded">save</span> GUARDAR CAMBIOS';
                    Swal.fire('Error', result.message || 'Error al actualizar', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<span class="material-symbols-rounded">save</span> GUARDAR CAMBIOS';
                Swal.fire('Error', 'Error de conexión', 'error');
            });
        });
    }
}

function formatFechaSalidaRecibos(fechaSalida) {
    if (!fechaSalida) return '-';

    const fecha = new Date(fechaSalida);
    if (Number.isNaN(fecha.getTime())) {
        return String(fechaSalida);
    }

    return new Intl.DateTimeFormat('es-CO', {
        dateStyle: 'medium',
        timeStyle: 'short'
    }).format(fecha);
}

function formatFechaReciboAcordeon(fecha) {
    return formatFechaSalidaRecibos(fecha);
}

function initNewTallerModal() {
    const btnNewTaller = document.getElementById('btnNewTaller');
    const modal = document.getElementById('modalNewTaller');
    const closeModalElements = document.querySelectorAll('.close-modal-talleres, .close-modal-btn');
    const form = document.getElementById('formNewTaller');
    const mainContainer = document.querySelector('.main-container');
    const csrfToken = mainContainer.dataset.csrfToken;
    const storeRoute = mainContainer.dataset.routeStore;

    if (btnNewTaller) {
        btnNewTaller.addEventListener('click', () => {
            modal.classList.add('show');
        });
    }

    closeModalElements.forEach(el => {
        el.addEventListener('click', () => {
            modal.classList.remove('show');
            form.reset();
        });
    });

    // Cerrar al hacer clic fuera
    window.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.classList.remove('show');
            form.reset();
        }
    });

    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const data = Object.fromEntries(formData.entries());
            const submitBtn = this.querySelector('button[type="submit"]');
            
            // Loading state
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<div class="loading-spinner" style="width:16px; height:16px; margin:0; border-width:2px;"></div> Guardando...';

            fetch(storeRoute, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    Swal.fire({
                        title: '¡Éxito!',
                        text: result.message,
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<span class="material-symbols-rounded">save</span> GUARDAR TALLER';
                    Swal.fire('Error', result.message || 'Ocurrió un error al crear el taller', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<span class="material-symbols-rounded">save</span> GUARDAR TALLER';
                Swal.fire('Error', 'Error de conexión con el servidor', 'error');
            });
        });
    }
}

function initStatusToggles() {
    const mainContainer = document.querySelector('.main-container');
    const csrfToken = mainContainer.dataset.csrfToken;

    document.querySelectorAll('.toggle-taller-status').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const id = this.dataset.id;
            const label = this.closest('.taller-status-toggle').querySelector('.status-label');
            const row = this.closest('tr');
            const routeBase = mainContainer.dataset.routeToggleStatus.replace(':id', id);

            fetch(routeBase, {
                method: 'PATCH',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    label.textContent = data.activo ? 'ACTIVO' : 'INACTIVO';
                    label.className = `status-label ${data.activo ? 'active' : 'inactive'}`;
                    
                    if (row) row.classList.toggle('inactive', !data.activo);

                    // Animación de salida al cambiar de estado y desaparecer del tab actual
                    if ((currentState.activeTab === 'activos' && !data.activo) || 
                        (currentState.activeTab === 'inactivos' && data.activo)) {
                        if (row) row.style.opacity = '0';
                        setTimeout(() => {
                            if (row) row.remove();
                            
                            const rows = document.querySelectorAll('#talleresRows tr[data-taller-id]');
                            if (rows.length === 0) {
                                const body = document.getElementById('talleresRows');
                                if (body) body.innerHTML = '<tr><td colspan="5" class="table-empty-state">No hay talleres disponibles en este momento.</td></tr>';
                                const paginationContainer = document.querySelector('.pagination-container');
                                if (paginationContainer) paginationContainer.innerHTML = '';
                            }
                        }, 300);
                    }
                } else {
                    this.checked = !this.checked;
                    Swal.fire('Error', data.message || 'Error al cambiar el estado', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                this.checked = !this.checked;
                Swal.fire('Error', 'Error de conexión al cambiar el estado', 'error');
            });
        });
    });
}

function loadTalleresStats() {
    const mainContainer = document.querySelector('.main-container');
    const statNodes = document.querySelectorAll('.stat-value[data-taller-id]');
    const apiRouteBase = mainContainer.dataset.routeApiRecibos;
    const uniqueIds = [...new Set(Array.from(statNodes).map(el => el.getAttribute('data-taller-id')).filter(Boolean))];
    
    uniqueIds.forEach(tallerId => {
        const completadosSpans = document.querySelectorAll(`.stat-completed[data-taller-id="${tallerId}"]`);
        const pendientesSpans = document.querySelectorAll(`.stat-pending[data-taller-id="${tallerId}"]`);
        const finalRoute = apiRouteBase.replace(':id', tallerId);

        fetch(finalRoute)
            .then(response => response.json())
            .then(data => {
                completadosSpans.forEach(span => span.textContent = data.completados);
                pendientesSpans.forEach(span => span.textContent = data.pendientes);
            })
            .catch(error => {
                console.error('Error loading stats for taller:', tallerId, error);
                completadosSpans.forEach(span => span.textContent = '0');
                pendientesSpans.forEach(span => span.textContent = '0');
            });
    });
}

function initTalleresSearch() {
    const searchInput = document.getElementById('searchInput');
    const clearButton = document.getElementById('clearSearch');
    const mainContainer = document.querySelector('.main-container');
    const apiRoute = mainContainer ? mainContainer.dataset.routeApiSearch : null;
    const talleresForm = searchInput ? searchInput.closest('form') : null;

    if (talleresForm) {
        talleresForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const searchTerm = searchInput ? searchInput.value.trim() : '';
            if (window.TalleresOrdenes?.handleOrdenesSearch(searchTerm, 1)) {
                return;
            }
            
            const url = new URL(window.location.href);
            if (searchTerm) url.searchParams.set('search', searchTerm);
            else url.searchParams.delete('search');
            url.searchParams.delete('page');
            window.history.pushState({ view: 'talleres', status: currentState.activeTab }, '', url.toString());

            performRealtimeSearch(searchTerm, apiRoute);
        });
    }
    
    if (searchInput) {
        const toggleClear = () => {
            if (searchInput.value.length > 0) {
                if (clearButton) clearButton.style.display = 'flex';
            } else {
                if (clearButton) clearButton.style.display = 'none';
            }
        };

        toggleClear();
        
        // Búsqueda en tiempo real con de-bounce
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            toggleClear();
            clearTimeout(searchTimeout);
            
            const searchTerm = this.value.trim();
            
            searchTimeout = setTimeout(() => {
                if (window.TalleresOrdenes?.handleOrdenesSearch(searchTerm, 1)) {
                    return;
                }
                const url = new URL(window.location.href);
                if (searchTerm) url.searchParams.set('search', searchTerm);
                else url.searchParams.delete('search');
                url.searchParams.delete('page');
                window.history.pushState({ view: 'talleres', status: currentState.activeTab }, '', url.toString());

                performRealtimeSearch(searchTerm, apiRoute);
            }, 300);
        });
        
        // Limpiar búsqueda preservando el tab activo
        if (clearButton) {
            clearButton.addEventListener('click', () => {
                searchInput.value = '';
                toggleClear();
                if (window.TalleresOrdenes?.handleOrdenesClearSearch()) {
                    return;
                }
                
                const url = new URL(window.location.href);
                url.searchParams.delete('search');
                url.searchParams.delete('page');
                window.history.pushState({ view: 'talleres', status: currentState.activeTab }, '', url.toString());

                performRealtimeSearch('', apiRoute);
            });
        }
    }
}

/**
 * Realizar búsqueda en tiempo real
 */
function performRealtimeSearch(searchTerm, apiRoute) {
    const talleresRows = document.getElementById('talleresRows');
    const paginationContainer = document.querySelector('.pagination-container');
    
    // Mostrar estado de carga
    talleresRows.innerHTML = '<tr><td colspan="5" class="table-empty-state"><div class="loading"><div class="loading-spinner"></div><p>Buscando talleres...</p></div></td></tr>';
    
    // Construir URL con parámetros
    const url = new URL(apiRoute, window.location.origin);
    url.searchParams.append('search', searchTerm);
    url.searchParams.append('per_page', 15);
    url.searchParams.append('status', currentState.activeTab || 'activos');
    
    fetch(url.toString())
        .then(response => response.json())
        .then(data => {
            if (!data.success || !data.data || data.data.length === 0) {
                talleresRows.innerHTML = '<tr><td colspan="5" class="table-empty-state">No se encontraron talleres que coincidan con "' + escapeHtml(searchTerm) + '"</td></tr>';
                paginationContainer.innerHTML = '';
                return;
            }
            
            talleresRows.innerHTML = renderTalleresRows(data.data);
            
            // Renderizar paginación
            let paginationHtml = '';
            if (data.pagination.last_page > 1) {
                paginationHtml = renderTalleresPagination(data.pagination, searchTerm);
            }
            paginationContainer.innerHTML = paginationHtml;
            
            // Reinicializar eventos
            initStatusToggles();
            initViewHandlers();
            initEditTaller();
            initTalleresPaginationEvents(searchTerm);
        })
        .catch(error => {
            console.error('Error en búsqueda:', error);
            talleresRows.innerHTML = '<tr><td colspan="5" class="table-empty-state">Error al buscar talleres. Intenta de nuevo.</td></tr>';
            paginationContainer.innerHTML = '';
        });
}

/**
 * Renderizar paginación para búsqueda de talleres
 */
function renderTalleresPagination(pagination, search) {
    const { current_page, last_page, total, per_page } = pagination;
    
    let html = '<div class="pagination-controls">';
    html += `<div class="pagination-info">Mostrando ${(current_page - 1) * per_page + 1} - ${Math.min(current_page * per_page, total)} de ${total} talleres</div>`;
    html += '<div class="pagination-buttons">';
    
    // Botón anterior
    if (current_page > 1) {
        html += `<button class="btn-pagination btn-prev" data-page="${current_page - 1}" data-search="${escapeHtml(search)}">
                    <span class="material-symbols-rounded">chevron_left</span>
                    Anterior
                </button>`;
    } else {
        html += `<button class="btn-pagination btn-prev" disabled>
                    <span class="material-symbols-rounded">chevron_left</span>
                    Anterior
                </button>`;
    }
    
    // Números de página
    html += '<div class="pagination-numbers">';
    for (let i = 1; i <= last_page; i++) {
        if (i === current_page) {
            html += `<span class="page-number active">${i}</span>`;
        } else if (i === 1 || i === last_page || (i >= current_page - 1 && i <= current_page + 1)) {
            html += `<button class="page-number" data-page="${i}" data-search="${escapeHtml(search)}">${i}</button>`;
        } else if (i === 2 || i === last_page - 1) {
            html += `<span class="page-number">...</span>`;
        }
    }
    html += '</div>';
    
    // Botón siguiente
    if (current_page < last_page) {
        html += `<button class="btn-pagination btn-next" data-page="${current_page + 1}" data-search="${escapeHtml(search)}">
                    Siguiente
                    <span class="material-symbols-rounded">chevron_right</span>
                </button>`;
    } else {
        html += `<button class="btn-pagination btn-next" disabled>
                    Siguiente
                    <span class="material-symbols-rounded">chevron_right</span>
                </button>`;
    }
    
    html += '</div></div>';
    return html;
}

/**
 * Inicializar eventos de paginación para búsqueda de talleres
 */
function initTalleresPaginationEvents(search) {
    const paginationButtons = document.querySelectorAll('#viewTalleres .pagination-container .btn-pagination, #viewTalleres .pagination-container .page-number:not(.active)');
    const mainContainer = document.querySelector('.main-container');
    const apiRoute = mainContainer ? mainContainer.dataset.routeApiSearch : null;
    
    paginationButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const page = this.dataset.page;
            const searchTerm = this.dataset.search || '';
            performTalleresPaginationSearch(searchTerm, page, apiRoute);
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    });
}

/**
 * Realizar búsqueda paginada de talleres
 */
function performTalleresPaginationSearch(searchTerm, page, apiRoute) {
    const talleresRows = document.getElementById('talleresRows');
    const paginationContainer = document.querySelector('.pagination-container');
    
    // Mostrar estado de carga
    talleresRows.innerHTML = '<tr><td colspan="5" class="table-empty-state"><div class="loading"><div class="loading-spinner"></div><p>Cargando página...</p></div></td></tr>';
    
    // Construir URL con parámetros
    const url = new URL(apiRoute, window.location.origin);
    url.searchParams.append('search', searchTerm);
    url.searchParams.append('per_page', 15);
    url.searchParams.append('page', page);
    url.searchParams.append('status', currentState.activeTab || 'activos');
    
    fetch(url.toString())
        .then(response => response.json())
        .then(data => {
            if (!data.success || !data.data || data.data.length === 0) {
                talleresRows.innerHTML = '<tr><td colspan="5" class="table-empty-state">No hay resultados en esta página.</td></tr>';
                paginationContainer.innerHTML = '';
                return;
            }
            
            talleresRows.innerHTML = renderTalleresRows(data.data);
            
            // Renderizar paginación
            let paginationHtml = '';
            if (data.pagination.last_page > 1) {
                paginationHtml = renderTalleresPagination(data.pagination, searchTerm);
            }
            paginationContainer.innerHTML = paginationHtml;
            
            // Reinicializar eventos
            initStatusToggles();
            initViewHandlers();
            initEditTaller();
            initTalleresPaginationEvents(searchTerm);
        })
        .catch(error => {
            console.error('Error en búsqueda paginada:', error);
            talleresRows.innerHTML = '<tr><td colspan="5" class="table-empty-state">Error al cargar la página. Intenta de nuevo.</td></tr>';
            paginationContainer.innerHTML = '';
        });
}

function renderTalleresRows(talleres) {
    return talleres.map(taller => `
        <tr class="${!taller.activo ? 'inactive' : ''}" data-name="${escapeHtml((taller.name || '').toLowerCase())}" data-taller-id="${taller.id}">
            <td class="col-taller-name">${escapeHtml(taller.name || '')}</td>
            <td>
                <div class="taller-status-toggle">
                    <label class="switch">
                        <input type="checkbox" class="toggle-taller-status" data-id="${taller.id}" ${taller.activo ? 'checked' : ''}>
                        <span class="slider round"></span>
                    </label>
                    <span class="status-label ${taller.activo ? 'active' : 'inactive'}">${taller.activo ? 'ACTIVO' : 'INACTIVO'}</span>
                </div>
            </td>
            <td><span class="stat-value stat-completed" data-taller-id="${taller.id}">-</span></td>
            <td><span class="stat-value stat-pending" data-taller-id="${taller.id}">-</span></td>
            <td>
                <div class="table-actions">
                    <button class="btn-edit-icon btn-edit-taller" data-id="${taller.id}" data-name="${escapeHtml(taller.name || '')}" title="Editar nombre">
                        <span class="material-symbols-rounded">edit</span>
                    </button>
                    <button class="btn-view btn-view-recibos" data-taller-id="${taller.id}" data-taller-name="${escapeHtml(taller.name || '')}">
                        Ver Recibos <span style="font-size: 10px; margin-left: 5px;">&#10095;</span>
                    </button>
                    <a class="btn-view" href="/talleres/${taller.id}/prestamos" style="text-decoration:none;">
                        Ver Préstamos <span style="font-size: 10px; margin-left: 5px;">&#10095;</span>
                    </a>
                </div>
            </td>
        </tr>
    `).join('');
}

/**
 * Escapar HTML para evitar XSS
 */
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}

function initViewHandlers() {
    const viewRecibosButtons = document.querySelectorAll('.btn-view-recibos');
    const backFromRecibos = document.getElementById('backFromRecibos');
    const backFromEntregas = document.getElementById('backFromEntregas');

    viewRecibosButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const tallerId = this.getAttribute('data-taller-id');
            const tallerName = this.getAttribute('data-taller-name') || this.closest('[data-taller-id]')?.querySelector('.col-taller-name')?.textContent || 'Taller';
            showRecibos(tallerId, tallerName);
        });
    });

    if (backFromRecibos) {
        backFromRecibos.addEventListener('click', function() {
            showTalleres();
        });
    }

    if (backFromEntregas) {
        backFromEntregas.addEventListener('click', function() {
            showRecibos(currentState.selectedTaller.id, currentState.selectedTaller.name);
        });
    }
}

function switchView(newView) {
    const viewTalleres = document.getElementById('viewTalleres');
    const viewRecibos = document.getElementById('viewRecibos');
    const viewEntregas = document.getElementById('viewEntregas');
    const viewOrdenes = document.getElementById('viewOrdenes');

    // Ocultar todas las vistas
    if (viewTalleres) viewTalleres.style.display = 'none';
    if (viewRecibos) viewRecibos.style.display = 'none';
    if (viewEntregas) viewEntregas.style.display = 'none';
    if (viewOrdenes) viewOrdenes.style.display = 'none';

    // Mostrar la nueva vista
    const target = document.getElementById('view' + newView.charAt(0).toUpperCase() + newView.slice(1));
    if (target) target.style.display = 'block';

    currentState.view = newView;
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function setTalleresTopNavVisible(isVisible) {
    const topNav = document.querySelector('.top-nav');
    if (!topNav) return;

    topNav.style.display = isVisible ? '' : 'none';
}

function showTalleres() {
    switchView('talleres');
    setTalleresTopNavVisible(true);
    currentState.selectedTaller = null;
    currentState.selectedRecibo = null;
    sessionStorage.removeItem('talleres.lastView');
    sessionStorage.removeItem('talleres.lastTallerId');
    sessionStorage.removeItem('talleres.lastTallerName');
    const url = new URL(window.location.href);
    url.searchParams.delete('taller_id');
    url.searchParams.delete('view');
    url.searchParams.set('status', currentState.activeTab || 'activos');
    window.history.replaceState({ view: 'talleres', status: currentState.activeTab || 'activos' }, '', url.toString());
}

function showRecibos(tallerId, tallerName) {
    currentState.selectedTaller = { id: tallerId, name: tallerName };
    const mainContainer = document.querySelector('.main-container');
    const recibosContent = document.getElementById('recibosContent');
    const recibosTitle = document.getElementById('recibosTitle');
    const apiRoute = mainContainer.dataset.routeApiRecibos.replace(':id', tallerId);

    if (recibosTitle) recibosTitle.textContent = tallerName;
    if (recibosContent) recibosContent.innerHTML = '<div class="loading"><div class="loading-spinner"></div><p>Cargando recibos...</p></div>';
    setTalleresTopNavVisible(false);
    switchView('recibos');
    
    const url = new URL(window.location.href);
    url.searchParams.set('view', 'recibos');
    url.searchParams.set('taller_id', String(tallerId));
    url.searchParams.set('status', currentState.activeTab || 'activos');
    url.searchParams.delete('search');
    window.history.replaceState({ view: 'recibos', taller_id: String(tallerId), status: currentState.activeTab || 'activos' }, '', url.toString());
    sessionStorage.setItem('talleres.lastView', 'recibos');
    sessionStorage.setItem('talleres.lastTallerId', String(tallerId));
    sessionStorage.setItem('talleres.lastTallerName', String(tallerName || 'Taller'));
    sessionStorage.setItem('talleres.lastStatus', String(currentState.activeTab || 'activos'));

    fetch(apiRoute)
        .then(response => response.json())
        .then(data => {
            if (data.recibos.length === 0) {
                recibosContent.innerHTML = '<div class="empty-state"><div class="empty-state-icon">📦</div><p>No hay recibos asignados a este taller.</p></div>';
                return;
            }

            let html = '<div class="table-container"><table class="table-recibos"><thead><tr><th>FECHA SALIDA</th><th>Nº RECIBO</th><th>CLIENTE</th><th>DESCRIPCIÓN PRENDA</th><th>PROGRESO</th><th>NOVEDADES</th><th>ACCIONES</th></tr></thead><tbody>';

            data.recibos.forEach(recibo => {
                html += `
                    <tr>
                        <td class="col-fecha-salida">${formatFechaSalidaRecibos(recibo.fecha_salida)}</td>
                        <td class="col-recibo">${recibo.numero_recibo}</td>
                        <td class="col-cliente">${recibo.cliente}</td>
                        <td>
                            <div class="prenda-nombre">${recibo.nombre_prenda}</div>
                            <p class="prenda-desc">${recibo.descripcion_prenda || ''}</p>
                        </td>
                        <td class="col-progreso">
                            <div class="progress-container">
                                <div class="progress-info">
                                    <span class="progress-text">Entregado: <b>${recibo.cantidad_entregada}</b> | Falta: <b>${recibo.cantidad_pendiente}</b></span>
                                    <span class="progress-percentage">${recibo.porcentaje}%</span>
                                </div>
                                <div class="progress-bar-wrapper">
                                    <div class="progress-bar-fill" style="width: ${recibo.porcentaje}%"></div>
                                </div>
                            </div>
                        </td>
                        <td class="col-novedades">
                            <span class="novedades-badge" data-recibo-id="${recibo.id}" data-es-parcial="${recibo.es_parcial}" style="display: inline-flex; align-items: center; justify-content: center; background: #f0f9ff; color: #1e40af; border: 1px solid #bfdbfe; border-radius: 12px; padding: 4px 10px; font-size: 0.75rem; font-weight: bold; cursor: pointer; transition: all 0.2s ease; white-space: nowrap; gap: 4px;">
                                <span class="novedades-count">0</span> novedades
                            </span>
                        </td>
                        <td>
                            <div style="display: flex; gap: 8px; align-items: center;">
                                <button class="btn-action btn-expandir-detalles" data-recibo-id="${recibo.id}" data-es-parcial="${recibo.es_parcial}" data-recibo-numero="${recibo.numero_recibo}" data-tipo-recibo="${recibo.tipo_recibo || ''}" title="Ver detalles aquí">
                                    <span class="material-symbols-rounded" style="font-size: 16px;">expand_more</span>
                                </button>
                                <button class="btn-action btn-ver-entregas" data-taller-id="${data.taller_id}" data-recibo-id="${recibo.id}" data-es-parcial="${recibo.es_parcial}" data-recibo-numero="${recibo.numero_recibo}" data-cliente="${recibo.cliente}" data-prenda="${recibo.nombre_prenda}">
                                    Ver Entregas <span style="font-size: 10px;">&#10095;</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            });

            html += '</tbody></table></div>';
            recibosContent.innerHTML = html;

            // Cargar el conteo de novedades para cada recibo
            const novedadesBadges = recibosContent.querySelectorAll('.novedades-badge');
            
            novedadesBadges.forEach((badge) => {
                const reciboId = badge.dataset.reciboId;
                const esParcialStr = badge.dataset.esParcial;
                const esParcial = esParcialStr === '1';
                const esParcialParam = esParcial ? '1' : '0';
                
                fetch(`/entregas-talleres/novedades-count/${reciboId}?es_parcial=${esParcialParam}`)
                    .then(response => response.json())
                    .then(data => {
                        const count = data.count || 0;
                        
                        // Actualizar el texto del badge manteniendo los estilos
                        badge.innerHTML = `<span class="novedades-count">${count}</span> ${count === 1 ? 'novedad' : 'novedades'}`;
                        
                        // Agregar evento de click para abrir modal
                        badge.addEventListener('click', function(e) {
                            e.stopPropagation();
                            openNovedadesModal(reciboId, esParcial);
                        });
                    })
                    .catch(error => {
                        console.error('Error al cargar novedades:', error);
                        badge.innerHTML = '<span class="novedades-count">0</span> novedades';
                    });
            });

            // Event delegation para el botón Expandir Detalles - Acordeón
            recibosContent.addEventListener('click', function(e) {
                const btn = e.target.closest('.btn-expandir-detalles');
                if (!btn) return;
                
                e.preventDefault();
                e.stopPropagation();
                
                const reciboId = btn.getAttribute('data-recibo-id');
                const esParcial = btn.getAttribute('data-es-parcial');
                const reciboNumero = btn.getAttribute('data-recibo-numero');
                const tipoRecibo = btn.getAttribute('data-tipo-recibo');
                const reciboRow = btn.closest('tr');
                
                // Crear o encontrar la fila del acordeón
                let accordionRow = reciboRow.nextElementSibling;
                if (!accordionRow || !accordionRow.classList.contains('recibo-accordion-row')) {
                    accordionRow = document.createElement('tr');
                    accordionRow.className = 'recibo-accordion-row';
                    accordionRow.innerHTML = `
                        <td colspan="7">
                            <div class="recibo-accordion-content" style="padding: 20px; text-align: center; color: #64748b;">
                                <div style="font-size: 1.5rem; margin-bottom: 10px;">⏳</div>
                                <p>Cargando entregas...</p>
                            </div>
                        </td>
                    `;
                    reciboRow.parentNode.insertBefore(accordionRow, reciboRow.nextSibling);
                }
                
                // Toggle del acordeón
                if (accordionRow.style.display === 'none' || !accordionRow.style.display) {
                    // Cerrar otros acordeones
                    document.querySelectorAll('.recibo-accordion-row').forEach(row => {
                        if (row !== accordionRow) {
                            row.style.display = 'none';
                        }
                    });
                    
                    // Abrir este acordeón
                    accordionRow.style.display = 'table-row';
                    
                    // Cargar entregas
                    const contentDiv = accordionRow.querySelector('.recibo-accordion-content');
                    if (contentDiv.textContent.includes('Cargando')) {
                        cargarEntregasAcordeon(reciboId, esParcial, reciboNumero, tipoRecibo, contentDiv);
                    }
                } else {
                    accordionRow.style.display = 'none';
                }
            });
            
            // Manejador para el botón Ver Entregas original (ir a otra vista)
            document.querySelectorAll('.btn-ver-entregas').forEach(btn => {
                btn.addEventListener('click', function() {
                    const tallerId = this.getAttribute('data-taller-id');
                    const reciboId = this.getAttribute('data-recibo-id');
                    const esParcial = this.getAttribute('data-es-parcial');
                    const reciboNumero = this.getAttribute('data-recibo-numero');
                    const cliente = this.getAttribute('data-cliente');
                    const prenda = this.getAttribute('data-prenda');
                    showEntregas(tallerId, reciboId, esParcial, reciboNumero, cliente, prenda);
                });
            });
        })
        .catch(error => {
            console.error('Error:', error);
            recibosContent.innerHTML = '<div class="empty-state"><p>Error al cargar los recibos.</p></div>';
        });
}

function restoreLastViewFromSession() {
    const url = new URL(window.location.href);
    const viewParam = url.searchParams.get('view');
    const tallerParam = url.searchParams.get('taller_id');

    // Si la URL ya trae estado explícito, dejamos que ese flujo mande.
    if (viewParam || tallerParam) {
        return;
    }

    const lastView = sessionStorage.getItem('talleres.lastView');
    const lastTallerId = sessionStorage.getItem('talleres.lastTallerId');
    const lastTallerName = sessionStorage.getItem('talleres.lastTallerName');
    const lastStatus = sessionStorage.getItem('talleres.lastStatus');

    if (lastStatus && lastStatus !== currentState.activeTab) {
        currentState.activeTab = lastStatus;
    }

    if (lastView === 'recibos' && lastTallerId) {
        showRecibos(lastTallerId, lastTallerName || 'Taller');
    }
}

function showEntregas(tallerId, reciboId, esParcial, reciboNumero, cliente, prenda) {
    currentState.selectedRecibo = { id: reciboId, numero: reciboNumero, cliente: cliente, prenda: prenda };
    const mainContainer = document.querySelector('.main-container');
    const entregasContent = document.getElementById('entregasContent');
    const entregasTitle = document.getElementById('entregasTitle');
    const entregasCardTitle = document.getElementById('entregasCardTitle');
    const entregasTotalValue = document.getElementById('entregasTotalValue');
    
    const apiRoute = mainContainer.dataset.routeApiEntregas
        .replace(':taller_id', tallerId)
        .replace(':recibo_id', reciboId)
        .replace(':es_parcial', esParcial);

    if (entregasTitle) entregasTitle.textContent = `Recibo: ${reciboNumero} — ${cliente}`;
    if (entregasCardTitle) entregasCardTitle.textContent = `Historial de Entregas Semanales - ${prenda}`;
    if (entregasContent) entregasContent.innerHTML = '<div class="loading"><div class="loading-spinner"></div><p>Cargando entregas...</p></div>';
    switchView('entregas');

    fetch(apiRoute)
        .then(response => response.json())
        .then(data => {
            if (entregasTotalValue) entregasTotalValue.textContent = data.total + ' UND';

            if (!data.entregas || data.entregas.length === 0) {
                entregasContent.innerHTML = '<div class="empty-state"><div class="empty-state-icon">📦</div><p>No hay entregas registradas para este recibo.</p></div>';
                return;
            }

            let html = '<div class="entregas-header"><div class="entregas-title"></div><div class="entregas-total"></div></div>';

            data.entregas.forEach(semanaGroup => {
                if (!semanaGroup || semanaGroup.length === 0) return;
                
                const semana = semanaGroup[0].grupo;
                html += '<div class="semana-group">';
                html += '<div class="semana-header"><span class="material-symbols-rounded">calendar_month</span>' + semana + '</div>';
                html += '<table class="table-entregas"><thead><tr><th class="col-fecha-salida">FECHA SALIDA</th><th>DESCRIPCIÓN</th><th class="col-genero">GÉNERO</th><th class="col-talla">TALLA</th><th class="col-cantidad">CANT.</th><th>PROGRESO</th><th>PRECIO</th><th class="col-fecha-entrada">FECHA ENTRADA</th></tr></thead><tbody>';

                semanaGroup.forEach(entrega => {
                    const colorMsg = entrega.color ? `<br><small style="color:#64748b">Color: ${entrega.color}</small>` : '';
                    
                    // Cálculo de progreso
                    const totalEntregado = entrega.total_entregado || 0;
                    const totalAsignado = entrega.total_asignado || 1; // evitar división por cero
                    const porcentaje = Math.min(Math.round((totalEntregado / totalAsignado) * 100), 100);
                    
                    html += `
                        <tr>
                            <td class="col-fecha-salida">${entrega.fecha_salida || '-'}</td>
                            <td>
                                <div class="prenda-desc-limit" title="${entrega.descripcion}">
                                    ${entrega.descripcion}
                                    ${colorMsg}
                                </div>
                            </td>
                            <td class="col-genero">${entrega.genero || 'UNISEX'}</td>
                            <td class="col-talla">${entrega.talla_nombre}</td>
                            <td class="col-cantidad">${entrega.cantidad}<small>UND</small></td>
                            <td>
                                <div class="progress-container">
                                    <div class="progress-info">
                                        <span class="progress-text">${totalEntregado} / ${totalAsignado}</span>
                                        <span class="progress-percentage">${porcentaje}%</span>
                                    </div>
                                    <div class="progress-bar-wrapper">
                                        <div class="progress-bar-fill" style="width: ${porcentaje}%"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="col-precio">
                                <div class="precio-input-group">
                                    <span class="currency-symbol">$</span>
                                    <input type="number" step="0.01" class="input-precio" 
                                           data-id="${entrega.id}" 
                                           value="${entrega.precio ? parseFloat(entrega.precio) : ''}" 
                                           placeholder="0">
                                </div>
                            </td>
                            <td class="col-fecha-entrada">${entrega.fecha_entrada || entrega.fecha_formateada}</td>
                        </tr>
                    `;
                });

                html += '</tbody></table></div>';
            });

            entregasContent.innerHTML = html;

            // Inicializar eventos para los inputs de precio
            initPrecioInputs();
        })
        .catch(error => {
            console.error('Error:', error);
            entregasContent.innerHTML = '<div class="empty-state"><p>Error al cargar las entregas.</p></div>';
        });
}

function initPrecioInputs() {
    const inputs = document.querySelectorAll('.input-precio');
    const mainContainer = document.querySelector('.main-container');
    const csrfToken = mainContainer.dataset.csrfToken;
    const routeBase = mainContainer.dataset.routeActualizarPrecio;

    const savePrecio = (input) => {
        const id = input.dataset.id;
        const precio = input.value;
        const finalRoute = routeBase.replace(':id', id);
        const group = input.closest('.precio-input-group');

        // Estilo visual de "guardando"
        group.style.opacity = '0.5';
        group.style.borderColor = '#3b82f6';

        fetch(finalRoute, {
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ precio: precio })
        })
        .then(response => response.json())
        .then(data => {
            group.style.opacity = '1';
            if (data.success) {
                // Reformatear para quitar .00 si es entero
                if (input.value) {
                    input.value = parseFloat(input.value);
                }
                
                // Feedback visual de éxito
                group.classList.add('saved-success');
                group.style.borderColor = '#10b981';
                
                setTimeout(() => { 
                    group.classList.remove('saved-success');
                    group.style.borderColor = ''; 
                }, 1500);
            } else {
                group.style.borderColor = '#ef4444';
                Swal.fire('Error', data.message || 'Error al guardar el precio', 'error');
            }
        })
        .catch(error => {
            group.style.opacity = '1';
            group.style.borderColor = '#ef4444';
            console.error('Error:', error);
            Swal.fire('Error', 'Error de conexión', 'error');
        });
    };

    inputs.forEach(input => {
        // Guardar al cambiar (blur o enter)
        input.addEventListener('change', function() {
            savePrecio(this);
        });

        // Guardar explícitamente al presionar Enter y quitar foco
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.blur(); // Esto disparará el evento 'change'
            }
        });
    });
}

/**
 * Inicializar navegación del sidebar
 */
function initSidebarNavigation() {
    const sidebarItems = document.querySelectorAll('.sidebar-item');
    const debugPrefix = '[TalleresSidebar]';
    const mainContainer = document.querySelector('.main-container');
    const hasSpaRoutes = Boolean(mainContainer && mainContainer.dataset.routeApiSearch);
    const isEntradaRoute = window.location.pathname === '/entrada' || window.location.pathname === '/entrada/';
    const isPrestamosGlobalRoute = window.location.pathname.includes('/talleres/prestamos/global');
    console.log('[TalleresSidebar:initSidebarNavigation]', {
        pathname: window.location.pathname,
        search: window.location.search,
        hasSpaRoutes,
        isEntradaRoute,
        isPrestamosGlobalRoute,
        sidebarItems: sidebarItems.length
    });

    const setSidebarActiveById = (id) => {
        sidebarItems.forEach(item => item.classList.remove('active'));
        const target = document.getElementById(id);
        if (target) {
            target.classList.add('active');
        }
    };
    window.setSidebarActiveById = setSidebarActiveById;

    const syncSidebarActiveState = () => {
        const url = new URL(window.location.href);
        const view = url.searchParams.get('view');
        const status = url.searchParams.get('status') === 'inactivos' ? 'inactivos' : 'activos';
        console.log('[TalleresSidebar:syncSidebarActiveState]', {
            pathname: window.location.pathname,
            search: window.location.search,
            view,
            status,
            activeTab: currentState.activeTab,
            isEntradaRoute
        });

        if (isEntradaRoute) {
            setSidebarActiveById('navEntradaCostura');
            return;
        }

        if (isPrestamosGlobalRoute) {
            setSidebarActiveById(url.searchParams.get('tab') === 'contramuestra' ? 'navPrestamosContramuestras' : 'navPrestamosInsumos');
            return;
        }

        if (view === 'ordenes') {
            if (window.TalleresOrdenes?.handleOrdenesSidebarNavigation()) {
                return;
            }
            setSidebarActiveById('navOrdenes');
            return;
        }

        if (view === 'recibos') {
            setSidebarActiveById(status === 'inactivos' ? 'navTalleresInactivos' : 'navTalleres');
            return;
        }

        setSidebarActiveById(status === 'inactivos' ? 'navTalleresInactivos' : 'navTalleres');
    };

    sidebarItems.forEach(item => {
        item.addEventListener('click', function(event) {
            const viewName = this.getAttribute('data-view');
            const href = this.getAttribute('href');

            console.log(debugPrefix, 'sidebar navigation click', {
                id: this.id || null,
                tag: this.tagName,
                viewName,
                href,
                hasSpaRoutes,
                currentUrl: window.location.href
            });

            if (!viewName) {
                return;
            }

            if (!hasSpaRoutes && href) {
                console.log(debugPrefix, 'non-spa page, allowing native navigation', {
                    href
                });
                return;
            }

            event.preventDefault();

            setSidebarActiveById(this.id);
            
            // Cambiar URL según la vista
            if (viewName === 'viewTalleres') {
                currentState.activeTab = this.dataset.status || 'activos';
                console.log('[TalleresSidebar:click:viewTalleres]', {
                    id: this.id || null,
                    status: currentState.activeTab,
                    href
                });
                // Reset search inputs when switching views
                const searchInput = document.getElementById('searchInput');
                if (searchInput) {
                    searchInput.value = '';
                    searchInput.placeholder = 'Buscar taller...';
                    const clearBtn = document.getElementById('clearSearch');
                    if (clearBtn) clearBtn.style.display = 'none';
                }
                const url = new URL(window.location.href);
                url.searchParams.delete('search');
                url.searchParams.set('status', currentState.activeTab || 'activos');
                url.searchParams.delete('view');
                console.log('[TalleresSidebar:pushState:viewTalleres]', {
                    before: window.location.href,
                    after: url.toString()
                });
                window.history.pushState({ view: 'talleres' }, 'Gestión Talleres', url.toString());
                showTalleres();

                // Recargar listado para evitar estado vacío al volver desde Órdenes
                const mainContainer = document.querySelector('.main-container');
                const apiRoute = mainContainer ? mainContainer.dataset.routeApiSearch : null;
                if (apiRoute) {
                    performRealtimeSearch('', apiRoute);
                }
            } else if (viewName === 'viewOrdenes') {
                console.log('[TalleresSidebar:click:viewOrdenes]', {
                    id: this.id || null,
                    href
                });
                // Reset search inputs when switching views
                const searchInput = document.getElementById('searchInput');
                if (searchInput) {
                    searchInput.value = '';
                    searchInput.placeholder = 'Buscar número de orden...';
                    const clearBtn = document.getElementById('clearSearch');
                    if (clearBtn) clearBtn.style.display = 'none';
                }
                const url = new URL(window.location.href);
                url.searchParams.delete('search');
                url.searchParams.set('view', 'ordenes');
                url.searchParams.delete('status');
                url.searchParams.set('tab', currentState.ordenesTab || 'pedidos');
                console.log('[TalleresSidebar:pushState:viewOrdenes]', {
                    before: window.location.href,
                    after: url.toString()
                });
                if (window.TalleresOrdenes?.handleOrdenesSidebarNavigation()) {
                    return;
                }
            }
        });
    });

    // Manejar el botón atrás del navegador
    window.addEventListener('popstate', function(event) {
        console.log('[TalleresSidebar:popstate]', {
            state: event.state,
            href: window.location.href
        });
        if (window.TalleresOrdenes?.handleOrdenesPopstate(event)) {
            return;
        } else if (event.state && event.state.view === 'recibos' && event.state.taller_id) {
            currentState.activeTab = event.state.status === 'inactivos' ? 'inactivos' : (currentState.activeTab || 'activos');
            setSidebarActiveById(currentState.activeTab === 'inactivos' ? 'navTalleresInactivos' : 'navTalleres');
            const row = document.querySelector(`tr[data-taller-id="${event.state.taller_id}"]`);
            const name = row ? row.querySelector('.col-taller-name')?.textContent?.trim() : 'Taller';
            showRecibos(event.state.taller_id, name || 'Taller');
        } else {
            currentState.activeTab = event.state?.status === 'inactivos' ? 'inactivos' : (currentState.activeTab || 'activos');
            if (isEntradaRoute) {
                setSidebarActiveById('navEntradaCostura');
            } else {
                setSidebarActiveById(currentState.activeTab === 'inactivos' ? 'navTalleresInactivos' : 'navTalleres');
            }
            showTalleres();
        }
    });

    // Verificar si hay parámetro view en la URL al cargar
    const urlParams = new URLSearchParams(window.location.search);
    if (window.TalleresOrdenes?.handleOrdenesInitialUrl(urlParams, document.getElementById('searchInput'), document.getElementById('clearSearch'))) {
        return;
    } else if (urlParams.get('view') === 'recibos' && urlParams.get('taller_id')) {
        console.log('[TalleresSidebar:initial-url:view=recibos]', {
            href: window.location.href,
            tallerId: urlParams.get('taller_id'),
            status: urlParams.get('status')
        });
        currentState.activeTab = urlParams.get('status') === 'inactivos' ? 'inactivos' : (currentState.activeTab || 'activos');
        setSidebarActiveById(currentState.activeTab === 'inactivos' ? 'navTalleresInactivos' : 'navTalleres');
        const tallerId = urlParams.get('taller_id');
        const row = document.querySelector(`tr[data-taller-id="${tallerId}"]`);
        const tallerName = row ? row.querySelector('.col-taller-name')?.textContent?.trim() : 'Taller';
        showRecibos(tallerId, tallerName || 'Taller');
    } else if (isPrestamosGlobalRoute) {
        console.log('[TalleresSidebar:initial-url:prestamos-global]', {
            href: window.location.href,
            tab: urlParams.get('tab') || 'insumos'
        });
        setSidebarActiveById(urlParams.get('tab') === 'contramuestra' ? 'navPrestamosContramuestras' : 'navPrestamosInsumos');
    } else {
        console.log('[TalleresSidebar:initial-url:default]', {
            href: window.location.href,
            status: urlParams.get('status'),
            currentStateActiveTab: currentState.activeTab
        });
        currentState.activeTab = urlParams.get('status') === 'inactivos' ? 'inactivos' : 'activos';
        syncSidebarActiveState();
    }
}
