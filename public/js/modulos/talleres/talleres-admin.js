/**
 * Admin Talleres - Dashboard SPA Logic
 */

let currentState = {
    view: 'talleres', // talleres, recibos, entregas
    selectedTaller: null,
    selectedRecibo: null
};

document.addEventListener('DOMContentLoaded', function() {
    initTalleresSearch();
    initViewHandlers();
    loadTalleresStats();
    initStatusToggles();
    initNewTallerModal();
    initEditTaller();
    initSidebarNavigation();
});

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
            const card = this.closest('.taller-card');
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
                    
                    if (data.activo) {
                        card.classList.remove('inactive');
                    } else {
                        card.classList.add('inactive');
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
    const tallerCards = document.querySelectorAll('.taller-card');
    const apiRouteBase = mainContainer.dataset.routeApiRecibos;
    
    tallerCards.forEach(card => {
        const tallerId = card.getAttribute('data-taller-id');
        const completadosSpan = card.querySelector('.stat-completed');
        const pendientesSpan = card.querySelector('.stat-pending');
        
        const finalRoute = apiRouteBase.replace(':id', tallerId);

        fetch(finalRoute)
            .then(response => response.json())
            .then(data => {
                completadosSpan.textContent = data.completados;
                pendientesSpan.textContent = data.pendientes;
            })
            .catch(error => {
                console.error('Error loading stats for taller:', tallerId, error);
                completadosSpan.textContent = '0';
                pendientesSpan.textContent = '0';
            });
    });
}

function initTalleresSearch() {
    const searchInput = document.getElementById('searchInput');
    const clearButton = document.getElementById('clearSearch');
    const mainContainer = document.querySelector('.main-container');
    const apiRoute = mainContainer.dataset.routeApiSearch;
    
    if (searchInput) {
        const toggleClear = () => {
            if (searchInput.value.length > 0) {
                clearButton.style.display = 'flex';
            } else {
                clearButton.style.display = 'none';
            }
        };

        toggleClear();
        
        // Búsqueda en tiempo real con debounce
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            toggleClear();
            clearTimeout(searchTimeout);
            
            const searchTerm = this.value.trim();
            
            // Si está vacío, recargar la página
            if (searchTerm === '') {
                window.location.href = window.location.pathname;
                return;
            }
            
            // Debounce de 300ms
            searchTimeout = setTimeout(() => {
                performRealtimeSearch(searchTerm, apiRoute);
            }, 300);
        });
        
        // Limpiar búsqueda
        clearButton.addEventListener('click', () => {
            searchInput.value = '';
            toggleClear();
            window.location.href = window.location.pathname;
        });
    }
}

/**
 * Realizar búsqueda en tiempo real
 */
function performRealtimeSearch(searchTerm, apiRoute) {
    const talleresGrid = document.getElementById('talleresGrid');
    const paginationContainer = document.querySelector('.pagination-container');
    
    // Mostrar estado de carga
    talleresGrid.innerHTML = '<div class="loading" style="grid-column: 1/-1; padding: 40px; text-align: center;"><div class="loading-spinner"></div><p>Buscando talleres...</p></div>';
    
    // Construir URL con parámetros
    const url = new URL(apiRoute, window.location.origin);
    url.searchParams.append('search', searchTerm);
    url.searchParams.append('per_page', 15);
    
    fetch(url.toString())
        .then(response => response.json())
        .then(data => {
            if (!data.success || !data.data || data.data.length === 0) {
                talleresGrid.innerHTML = '<div style="grid-column: 1/-1; padding: 40px; text-align: center; color: #64748b; background: white; border-radius: 12px; border: 1px dashed #cbd5e1;"><span class="material-symbols-rounded" style="font-size: 40px; color: #cbd5e1; margin-bottom: 10px; display: block;">search_off</span><p>No se encontraron talleres que coincidan con "<strong>' + escapeHtml(searchTerm) + '</strong>"</p></div>';
                paginationContainer.innerHTML = '';
                return;
            }
            
            // Renderizar resultados
            let html = '';
            data.data.forEach(taller => {
                html += `
                    <div class="taller-card ${!taller.activo ? 'inactive' : ''}" data-name="${escapeHtml(taller.name.toLowerCase())}" data-taller-id="${taller.id}">
                        <div class="card-header-info">
                            <h2 class="taller-name">${escapeHtml(taller.name)}</h2>
                            <div class="taller-status-toggle">
                                <label class="switch">
                                    <input type="checkbox" class="toggle-taller-status" 
                                           data-id="${taller.id}" 
                                           ${taller.activo ? 'checked' : ''}>
                                    <span class="slider round"></span>
                                </label>
                                <span class="status-label ${taller.activo ? 'active' : 'inactive'}">
                                    ${taller.activo ? 'ACTIVO' : 'INACTIVO'}
                                </span>
                            </div>
                        </div>
                        <p class="taller-role">RESPONSABLE DE TALLER</p>
                        
                        <div class="stats-container">
                            <div class="stat-row">
                                <span>Completados:</span>
                                <span class="stat-value stat-completed" data-taller-id="${taller.id}">-</span>
                            </div>
                            <div class="stat-row">
                                <span>Pendientes:</span>
                                <span class="stat-value stat-pending" data-taller-id="${taller.id}">-</span>
                            </div>
                        </div>
                        
                        <div class="card-footer-actions">
                            <button class="btn-edit-icon btn-edit-taller" data-id="${taller.id}" data-name="${escapeHtml(taller.name)}" title="Editar nombre">
                                <span class="material-symbols-rounded">edit</span>
                            </button>
                            <button class="btn-view btn-view-recibos" data-taller-id="${taller.id}">
                                Ver Recibos <span style="font-size: 10px;">&#10095;</span>
                            </button>
                        </div>
                    </div>
                `;
            });
            
            talleresGrid.innerHTML = html;
            
            // Renderizar paginación
            let paginationHtml = '';
            if (data.pagination.last_page > 1) {
                paginationHtml = renderTalleresPagination(data.pagination, searchTerm);
            }
            paginationContainer.innerHTML = paginationHtml;
            
            // Reinicializar eventos
            loadTalleresStats();
            initStatusToggles();
            initViewHandlers();
            initEditTaller();
            initTalleresPaginationEvents(searchTerm);
        })
        .catch(error => {
            console.error('Error en búsqueda:', error);
            talleresGrid.innerHTML = '<div style="grid-column: 1/-1; padding: 40px; text-align: center; color: #ef4444;"><span class="material-symbols-rounded" style="font-size: 40px; margin-bottom: 10px; display: block;">error</span><p>Error al buscar talleres. Intenta de nuevo.</p></div>';
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
    const paginationButtons = document.querySelectorAll('.pagination-container .btn-pagination, .pagination-container .page-number:not(.active)');
    const mainContainer = document.querySelector('.main-container');
    const apiRoute = mainContainer.dataset.routeApiSearch;
    
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
    const talleresGrid = document.getElementById('talleresGrid');
    const paginationContainer = document.querySelector('.pagination-container');
    
    // Mostrar estado de carga
    talleresGrid.innerHTML = '<div class="loading" style="grid-column: 1/-1; padding: 40px; text-align: center;"><div class="loading-spinner"></div><p>Cargando página...</p></div>';
    
    // Construir URL con parámetros
    const url = new URL(apiRoute, window.location.origin);
    url.searchParams.append('search', searchTerm);
    url.searchParams.append('per_page', 15);
    url.searchParams.append('page', page);
    
    fetch(url.toString())
        .then(response => response.json())
        .then(data => {
            if (!data.success || !data.data || data.data.length === 0) {
                talleresGrid.innerHTML = '<div style="grid-column: 1/-1; padding: 40px; text-align: center; color: #64748b;"><p>No hay resultados en esta página.</p></div>';
                paginationContainer.innerHTML = '';
                return;
            }
            
            // Renderizar resultados
            let html = '';
            data.data.forEach(taller => {
                html += `
                    <div class="taller-card ${!taller.activo ? 'inactive' : ''}" data-name="${escapeHtml(taller.name.toLowerCase())}" data-taller-id="${taller.id}">
                        <div class="card-header-info">
                            <h2 class="taller-name">${escapeHtml(taller.name)}</h2>
                            <div class="taller-status-toggle">
                                <label class="switch">
                                    <input type="checkbox" class="toggle-taller-status" 
                                           data-id="${taller.id}" 
                                           ${taller.activo ? 'checked' : ''}>
                                    <span class="slider round"></span>
                                </label>
                                <span class="status-label ${taller.activo ? 'active' : 'inactive'}">
                                    ${taller.activo ? 'ACTIVO' : 'INACTIVO'}
                                </span>
                            </div>
                        </div>
                        <p class="taller-role">RESPONSABLE DE TALLER</p>
                        
                        <div class="stats-container">
                            <div class="stat-row">
                                <span>Completados:</span>
                                <span class="stat-value stat-completed" data-taller-id="${taller.id}">-</span>
                            </div>
                            <div class="stat-row">
                                <span>Pendientes:</span>
                                <span class="stat-value stat-pending" data-taller-id="${taller.id}">-</span>
                            </div>
                        </div>
                        
                        <div class="card-footer-actions">
                            <button class="btn-edit-icon btn-edit-taller" data-id="${taller.id}" data-name="${escapeHtml(taller.name)}" title="Editar nombre">
                                <span class="material-symbols-rounded">edit</span>
                            </button>
                            <button class="btn-view btn-view-recibos" data-taller-id="${taller.id}">
                                Ver Recibos <span style="font-size: 10px;">&#10095;</span>
                            </button>
                        </div>
                    </div>
                `;
            });
            
            talleresGrid.innerHTML = html;
            
            // Renderizar paginación
            let paginationHtml = '';
            if (data.pagination.last_page > 1) {
                paginationHtml = renderTalleresPagination(data.pagination, searchTerm);
            }
            paginationContainer.innerHTML = paginationHtml;
            
            // Reinicializar eventos
            loadTalleresStats();
            initStatusToggles();
            initViewHandlers();
            initEditTaller();
            initTalleresPaginationEvents(searchTerm);
        })
        .catch(error => {
            console.error('Error en búsqueda paginada:', error);
            talleresGrid.innerHTML = '<div style="grid-column: 1/-1; padding: 40px; text-align: center; color: #ef4444;"><p>Error al cargar la página. Intenta de nuevo.</p></div>';
            paginationContainer.innerHTML = '';
        });
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
            const tallerName = this.closest('.taller-card').querySelector('.taller-name').textContent;
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

function showTalleres() {
    switchView('talleres');
    currentState.selectedTaller = null;
    currentState.selectedRecibo = null;
}

function showRecibos(tallerId, tallerName) {
    currentState.selectedTaller = { id: tallerId, name: tallerName };
    const mainContainer = document.querySelector('.main-container');
    const recibosContent = document.getElementById('recibosContent');
    const recibosTitle = document.getElementById('recibosTitle');
    const apiRoute = mainContainer.dataset.routeApiRecibos.replace(':id', tallerId);

    if (recibosTitle) recibosTitle.textContent = tallerName;
    if (recibosContent) recibosContent.innerHTML = '<div class="loading"><div class="loading-spinner"></div><p>Cargando recibos...</p></div>';
    switchView('recibos');

    fetch(apiRoute)
        .then(response => response.json())
        .then(data => {
            if (data.recibos.length === 0) {
                recibosContent.innerHTML = '<div class="empty-state"><div class="empty-state-icon">📦</div><p>No hay recibos asignados a este taller.</p></div>';
                return;
            }

            let html = '<div class="table-container"><table class="table-recibos"><thead><tr><th>Nº RECIBO</th><th>CLIENTE</th><th>DESCRIPCIÓN PRENDA</th><th>PROGRESO</th><th>ACCIONES</th></tr></thead><tbody>';

            data.recibos.forEach(recibo => {
                html += `
                    <tr>
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
                        <td>
                            <button class="btn-action btn-ver-entregas" data-taller-id="${data.taller_id}" data-recibo-id="${recibo.id}" data-es-parcial="${recibo.es_parcial}" data-recibo-numero="${recibo.numero_recibo}" data-cliente="${recibo.cliente}" data-prenda="${recibo.nombre_prenda}">
                                Ver Entregas <span style="font-size: 10px;">&#10095;</span>
                            </button>
                        </td>
                    </tr>
                `;
            });

            html += '</tbody></table></div>';
            recibosContent.innerHTML = html;

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
                html += '<table class="table-entregas"><thead><tr><th class="col-fecha">FECHA</th><th>DESCRIPCIÓN</th><th class="col-genero">GÉNERO</th><th class="col-talla">TALLA</th><th class="col-cantidad">CANT.</th><th>PROGRESO</th><th>PRECIO</th></tr></thead><tbody>';

                semanaGroup.forEach(entrega => {
                    const colorMsg = entrega.color ? `<br><small style="color:#64748b">Color: ${entrega.color}</small>` : '';
                    
                    // Cálculo de progreso
                    const totalEntregado = entrega.total_entregado || 0;
                    const totalAsignado = entrega.total_asignado || 1; // evitar división por cero
                    const porcentaje = Math.min(Math.round((totalEntregado / totalAsignado) * 100), 100);
                    
                    html += `
                        <tr>
                            <td class="col-fecha">${entrega.fecha_formateada}</td>
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

    sidebarItems.forEach(item => {
        item.addEventListener('click', function() {
            const viewName = this.getAttribute('data-view');
            
            // Remover clase active de todos los items
            sidebarItems.forEach(i => i.classList.remove('active'));
            
            // Agregar clase active al item clickeado
            this.classList.add('active');
            
            // Cambiar URL según la vista
            if (viewName === 'viewTalleres') {
                window.history.pushState({ view: 'talleres' }, 'Gestión Talleres', window.location.pathname);
                showTalleres();
            } else if (viewName === 'viewOrdenes') {
                window.history.pushState({ view: 'ordenes' }, 'Órdenes', window.location.pathname + '?view=ordenes');
                showOrdenes();
            }
        });
    });

    // Manejar el botón atrás del navegador
    window.addEventListener('popstate', function(event) {
        if (event.state && event.state.view === 'ordenes') {
            document.querySelector('[data-view="viewOrdenes"]').classList.add('active');
            document.querySelector('[data-view="viewTalleres"]').classList.remove('active');
            showOrdenes();
        } else {
            document.querySelector('[data-view="viewTalleres"]').classList.add('active');
            document.querySelector('[data-view="viewOrdenes"]').classList.remove('active');
            showTalleres();
        }
    });

    // Verificar si hay parámetro view en la URL al cargar
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('view') === 'ordenes') {
        document.querySelector('[data-view="viewOrdenes"]').classList.add('active');
        document.querySelector('[data-view="viewTalleres"]').classList.remove('active');
        showOrdenes();
    }
}

/**
 * Obtener color de progreso basado en porcentaje
 */
function getProgressColor(percentage) {
    if (percentage <= 33) {
        return '#ef4444'; // Rojo
    } else if (percentage <= 66) {
        return '#f59e0b'; // Amarillo
    } else {
        return '#10b981'; // Verde
    }
}

/**
 * Inicializar búsqueda en Órdenes
 */
function initOrdenesSearch() {
    const searchInput = document.getElementById('searchOrdenesInput');
    const clearButton = document.getElementById('clearSearchOrdenes');
    
    if (searchInput) {
        const toggleClear = () => {
            if (searchInput.value.length > 0) {
                clearButton.style.display = 'flex';
            } else {
                clearButton.style.display = 'none';
            }
        };

        toggleClear();
        
        // Buscar al escribir (con debounce)
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            toggleClear();
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                showOrdenes(this.value, 1);
            }, 300);
        });
        
        // Limpiar búsqueda
        clearButton.addEventListener('click', () => {
            searchInput.value = '';
            toggleClear();
            showOrdenes('', 1);
        });
    }
}

/**
 * Mostrar vista de Órdenes
 */
function showOrdenes(search = '', page = 1) {
    switchView('ordenes');
    const mainContainer = document.querySelector('.main-container');
    const ordenesContent = document.getElementById('ordenesContent');
    const apiRoute = mainContainer.dataset.routeApiOrdenes;

    if (ordenesContent) {
        ordenesContent.innerHTML = '<div class="loading"><div class="loading-spinner"></div><p>Cargando órdenes...</p></div>';
    }

    // Construir URL con parámetros
    const url = new URL(apiRoute, window.location.origin);
    if (search) url.searchParams.append('search', search);
    url.searchParams.append('page', page);

    fetch(url.toString())
        .then(response => response.json())
        .then(data => {
            if (!data.ordenes || data.ordenes.length === 0) {
                let html = '<div class="empty-state"><div class="empty-state-icon">📦</div><p>No hay órdenes asignadas a talleres.</p></div>';
                
                // Agregar controles de paginación si hay búsqueda
                if (search || page > 1) {
                    html += renderPaginationControls(data.pagination, search);
                }
                
                ordenesContent.innerHTML = html;
                return;
            }

            let html = '<div class="table-container"><table class="table-ordenes"><thead><tr><th class="col-numero">Nº ORDEN</th><th>DESCRIPCIÓN</th><th class="col-cantidad">CANT. TOTAL</th><th>PROGRESO TOTAL</th><th>ENCARGADO</th><th>DISTRIBUCIÓN</th></tr></thead><tbody>';

            data.ordenes.forEach(orden => {
                const rowClass = orden.es_dividido ? 'orden-dividida' : '';
                
                // Fila principal
                html += `
                    <tr class="${rowClass}" data-orden-id="${orden.id}">
                        <td class="col-numero"><strong>${orden.numero_recibo}</strong></td>
                        <td>
                            <div class="prenda-nombre">${orden.descripcion}</div>
                            <p class="prenda-desc">${orden.cliente}</p>
                        </td>
                        <td class="col-cantidad">${orden.cantidad_total}</td>
                        <td>
                            <div class="progress-container">
                                <div class="progress-info">
                                    <span class="progress-text">${orden.cantidad_entregada} / ${orden.cantidad_total}</span>
                                    <span class="progress-percentage">${orden.porcentaje}%</span>
                                </div>
                                <div class="progress-bar-wrapper">
                                    <div class="progress-bar-fill" style="width: ${orden.porcentaje}%; background: ${getProgressColor(orden.porcentaje)}"></div>
                                </div>
                            </div>
                        </td>
                        <td class="col-encargado">
                            <span class="encargado-badge">${orden.encargado_display}</span>
                        </td>
                        <td class="col-distribucion">
                            ${orden.es_dividido ? 
                                `<button class="btn-ver-distribucion" data-orden-id="${orden.id}">
                                    <span class="material-symbols-rounded">expand_more</span>
                                    Ver Distribución
                                </button>` 
                                : 
                                `<span class="distribucion-badge completa">${orden.distribucion}</span>`
                            }
                        </td>
                    </tr>
                `;

                // Si está dividida, agregar fila expandible con distribución
                if (orden.es_dividido) {
                    html += `<tr class="distribucion-expandible" id="distribucion-${orden.id}" style="display: none;">
                        <td colspan="6">
                            <div class="distribucion-container">
                                <div class="distribucion-titulo">
                                    <span class="material-symbols-rounded">call_split</span>
                                    DISTRIBUCIÓN TÉCNICA DEL RECIBO ${orden.numero_recibo}
                                </div>
                                <div class="distribucion-ramas">`;
                    
                    // Agrupar por número de parte
                    const partesPorNumero = {};
                    orden.distribucion_detalles.forEach(detalle => {
                        if (!partesPorNumero[detalle.numero_recibo_parte]) {
                            partesPorNumero[detalle.numero_recibo_parte] = [];
                        }
                        partesPorNumero[detalle.numero_recibo_parte].push(detalle);
                    });
                    
                    // Renderizar cada parte con sus tallas como ramas
                    Object.keys(partesPorNumero).forEach(numeroParte => {
                        const tallas = partesPorNumero[numeroParte];
                        html += `
                            <div class="rama-parte">
                                <div class="rama-parte-header">
                                    <span class="rama-parte-numero">${numeroParte}</span>
                                </div>
                                <div class="rama-tallas">`;
                        
                        tallas.forEach((detalle, index) => {
                            html += `
                                <div class="rama-talla-item">
                                    <div class="rama-talla-content">
                                        <span class="talla-nombre">${detalle.talla}</span>
                                        <span class="talla-cantidad">${detalle.cantidad}</span>
                                        <div class="talla-progreso">
                                            <span class="progreso-text">${detalle.cantidad_entregada} / ${detalle.cantidad}</span>
                                            <span class="progreso-percentage">${detalle.porcentaje}%</span>
                                            <div class="progress-bar-wrapper">
                                                <div class="progress-bar-fill" style="width: ${detalle.porcentaje}%; background: ${getProgressColor(detalle.porcentaje)}"></div>
                                            </div>
                                        </div>
                                        <span class="talla-encargado">${detalle.taller_nombre}</span>
                                    </div>
                                </div>
                            `;
                        });
                        
                        html += `
                                </div>
                            </div>
                        `;
                    });
                    
                    html += `
                                </div>
                            </div>
                        </td>
                    </tr>`;
                }
            });

            html += '</tbody></table></div>';
            
            // Agregar controles de paginación
            html += renderPaginationControls(data.pagination, search);
            
            ordenesContent.innerHTML = html;

            // Inicializar eventos de distribución
            initDistribucionEvents();
            
            // Inicializar eventos de paginación
            initPaginationEvents(search);
            
            // Inicializar búsqueda
            initOrdenesSearch();
        })
        .catch(error => {
            console.error('Error:', error);
            ordenesContent.innerHTML = '<div class="empty-state"><p>Error al cargar las órdenes.</p></div>';
        });
}

/**
 * Inicializar eventos de distribución
 */
function initDistribucionEvents() {
    const expandButtons = document.querySelectorAll('.btn-ver-distribucion');
    
    expandButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const ordenId = this.dataset.ordenId;
            const expandibleRow = document.getElementById(`distribucion-${ordenId}`);
            
            if (expandibleRow) {
                const isVisible = expandibleRow.style.display !== 'none';
                expandibleRow.style.display = isVisible ? 'none' : 'table-row';
                this.classList.toggle('expanded');
            }
        });
    });
}

/**
 * Renderizar controles de paginación
 */
function renderPaginationControls(pagination, search) {
    const { current_page, last_page, total, per_page } = pagination;
    
    let html = '<div class="pagination-controls">';
    html += `<div class="pagination-info">Mostrando ${(current_page - 1) * per_page + 1} - ${Math.min(current_page * per_page, total)} de ${total} órdenes</div>`;
    html += '<div class="pagination-buttons">';
    
    // Botón anterior
    if (current_page > 1) {
        html += `<button class="btn-pagination btn-prev" data-page="${current_page - 1}" data-search="${search}">
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
            html += `<button class="page-number" data-page="${i}" data-search="${search}">${i}</button>`;
        } else if (i === 2 || i === last_page - 1) {
            html += `<span class="page-number">...</span>`;
        }
    }
    html += '</div>';
    
    // Botón siguiente
    if (current_page < last_page) {
        html += `<button class="btn-pagination btn-next" data-page="${current_page + 1}" data-search="${search}">
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
 * Inicializar eventos de paginación
 */
function initPaginationEvents(search) {
    const paginationButtons = document.querySelectorAll('.btn-pagination, .page-number:not(.active)');
    
    paginationButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const page = this.dataset.page;
            const searchTerm = this.dataset.search || '';
            showOrdenes(searchTerm, page);
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    });
}
