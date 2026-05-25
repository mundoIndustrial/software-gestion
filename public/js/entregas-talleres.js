/**
 * Entregas Talleres Module - Detalle JS
 */

document.addEventListener('DOMContentLoaded', function() {
    initEntregasTalleresSearch();
});

function initEntregasTalleresSearch() {
    const mainContainer = document.querySelector('.entregas-talleres-index');
    const searchInput = document.getElementById('searchInput');
    const clearButton = document.getElementById('clearSearch');
    const talleresGrid = document.getElementById('talleresGrid');
    const paginationContainer = document.querySelector('.pagination-container');
    const apiRoute = mainContainer?.dataset?.routeApiSearch;

    if (mainContainer && searchInput && talleresGrid && paginationContainer && apiRoute) {
        const toggleClear = () => {
            if (clearButton) {
                clearButton.style.display = searchInput.value.trim().length > 0 ? 'flex' : 'none';
            }
        };

        const syncUrl = (searchTerm, page = 1) => {
            const url = new URL(window.location.href);

            if (searchTerm) {
                url.searchParams.set('search', searchTerm);
            } else {
                url.searchParams.delete('search');
            }

            if (page > 1) {
                url.searchParams.set('page', page);
            } else {
                url.searchParams.delete('page');
            }

            window.history.pushState({ view: 'entregas_talleres', search: searchTerm, page }, '', url.toString());
        };

        const renderEmptyState = (searchTerm) => {
            const term = escapeHtml(searchTerm || '');
            talleresGrid.innerHTML = `
                <div style="grid-column: 1/-1; padding: 40px; text-align: center; color: #64748b; background: white; border-radius: 12px; border: 1px dashed #cbd5e1; width: 100%;">
                    <span class="material-symbols-rounded" style="font-size: 40px; color: #cbd5e1; margin-bottom: 10px; display: block;">search_off</span>
                    <p>No se encontraron talleres que coincidan con "<strong>${term}</strong>"</p>
                </div>
            `;
            paginationContainer.innerHTML = '';
        };

        const renderTallerCard = (taller) => {
            const name = escapeHtml(taller.name || '');
            return `
                <a
                    href="${mainContainer.dataset.routeBuscar}?taller_id=${encodeURIComponent(taller.id)}"
                    class="taller-card taller-card-link"
                    data-name="${escapeHtml(String(taller.name || '').toLowerCase())}"
                    data-taller-id="${taller.id}"
                >
                    <div class="card-header-info">
                        <h2 class="taller-name">${name}</h2>
                        <div class="taller-status-badge active">ACTIVO</div>
                    </div>

                    <p class="taller-role">RESPONSABLE DE TALLER</p>

                    <div class="stats-container">
                        <div class="stat-row">
                            <span>Recibos asignados</span>
                            <span class="stat-value">Ver</span>
                        </div>
                        <div class="stat-row">
                            <span>Estado</span>
                            <span class="stat-value stat-active">Activo</span>
                        </div>
                    </div>

                    <div class="card-footer-actions">
                        <span class="btn-view btn-view-recibos">
                            Ver Recibos <span style="font-size: 10px; margin-left: 5px;">&#10095;</span>
                        </span>
                    </div>
                </a>
            `;
        };

        const renderPagination = (pagination, searchTerm) => {
            const { current_page, last_page } = pagination;
            if (!last_page || last_page <= 1) {
                return '';
            }

            let html = '<nav role="navigation" aria-label="Pagination Navigation" class="flex items-center justify-center gap-2">';

            if (current_page > 1) {
                html += `<button class="pagination-item btn-pagination" data-page="${current_page - 1}" data-search="${escapeHtml(searchTerm)}" type="button"><span class="material-symbols-rounded">chevron_left</span></button>`;
            } else {
                html += `<span class="pagination-item disabled"><span class="material-symbols-rounded">chevron_left</span></span>`;
            }

            for (let i = 1; i <= last_page; i++) {
                if (i === current_page) {
                    html += `<span class="pagination-item active" aria-current="page">${i}</span>`;
                } else if (i === 1 || i === last_page || (i >= current_page - 1 && i <= current_page + 1)) {
                    html += `<button class="pagination-item btn-pagination page-number" data-page="${i}" data-search="${escapeHtml(searchTerm)}" type="button">${i}</button>`;
                } else if (i === 2 || i === last_page - 1) {
                    html += `<span class="pagination-item disabled">...</span>`;
                }
            }

            if (current_page < last_page) {
                html += `<button class="pagination-item btn-pagination" data-page="${current_page + 1}" data-search="${escapeHtml(searchTerm)}" type="button"><span class="material-symbols-rounded">chevron_right</span></button>`;
            } else {
                html += `<span class="pagination-item disabled"><span class="material-symbols-rounded">chevron_right</span></span>`;
            }

            html += '</nav>';

            return html;
        };

        const initPaginationEvents = (searchTerm) => {
            const paginationButtons = document.querySelectorAll('.pagination-container .btn-pagination, .pagination-container .page-number');

            paginationButtons.forEach((btn) => {
                btn.addEventListener('click', function() {
                    const page = parseInt(this.dataset.page, 10) || 1;
                    const term = this.dataset.search || searchTerm || '';
                    performTalleresSearch(term, page);
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                });
            });
        };

        const performTalleresSearch = (searchTerm, page = 1) => {
            talleresGrid.innerHTML = '<div class="loading" style="grid-column: 1/-1; padding: 40px; text-align: center;"><div class="loading-spinner"></div><p>Buscando talleres...</p></div>';

            const url = new URL(apiRoute, window.location.origin);
            url.searchParams.set('search', searchTerm || '');
            url.searchParams.set('per_page', 9);
            url.searchParams.set('page', page);

            fetch(url.toString())
                .then((response) => response.json())
                .then((data) => {
                    if (!data.success || !data.data || data.data.length === 0) {
                        renderEmptyState(searchTerm);
                        return;
                    }

                    talleresGrid.innerHTML = data.data.map(renderTallerCard).join('');
                    paginationContainer.innerHTML = renderPagination(data.pagination, searchTerm);
                    initPaginationEvents(searchTerm);
                })
                .catch((error) => {
                    console.error('Error en la búsqueda de talleres:', error);
                    talleresGrid.innerHTML = '<div style="grid-column: 1/-1; padding: 40px; text-align: center; color: #ef4444;"><span class="material-symbols-rounded" style="font-size: 40px; margin-bottom: 10px; display: block;">error</span><p>Error al buscar talleres. Intenta de nuevo.</p></div>';
                    paginationContainer.innerHTML = '';
                });
        };

        toggleClear();

        let searchTimeout;
        searchInput.addEventListener('input', function() {
            toggleClear();
            clearTimeout(searchTimeout);

            const searchTerm = this.value.trim();
            searchTimeout = setTimeout(() => {
                syncUrl(searchTerm);
                performTalleresSearch(searchTerm, 1);
            }, 300);
        });

        const searchForm = searchInput.closest('form');
        if (searchForm) {
            searchForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const searchTerm = searchInput.value.trim();
                syncUrl(searchTerm);
                performTalleresSearch(searchTerm, 1);
            });
        }

        if (clearButton) {
            clearButton.addEventListener('click', () => {
                searchInput.value = '';
                toggleClear();
                syncUrl('');
                performTalleresSearch('', 1);
            });
        }

        window.addEventListener('popstate', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const searchTerm = urlParams.get('search') || '';
            const page = parseInt(urlParams.get('page') || '1', 10) || 1;
            searchInput.value = searchTerm;
            toggleClear();
            performTalleresSearch(searchTerm, page);
        });

        return;
    }

    initRecibosTallerSearch();
}

function initRecibosTallerSearch() {
    const resultsForm = document.querySelector('.results-search-form');
    const resultsContent = document.querySelector('.results-content');

    if (!resultsForm || !resultsContent) {
        return;
    }

    const searchInput = resultsForm.querySelector('input[name="busqueda"]');
    const clearButton = resultsForm.querySelector('.gooey-search-clear');
    const searchEndpoint = resultsForm.getAttribute('action');
    const tallerIdInput = resultsForm.querySelector('input[name="taller_id"]');
    const estadoInput = resultsForm.querySelector('input[name="estado"]');

    if (!searchInput || !searchEndpoint || !tallerIdInput || !estadoInput) {
        return;
    }

    const toggleClear = () => {
        if (clearButton) {
            clearButton.style.display = searchInput.value.trim().length > 0 ? 'flex' : 'none';
        }
    };

    const syncUrl = (searchTerm) => {
        const url = new URL(window.location.href);

        if (searchTerm) {
            url.searchParams.set('busqueda', searchTerm);
        } else {
            url.searchParams.delete('busqueda');
        }

        url.searchParams.set('taller_id', tallerIdInput.value);
        url.searchParams.set('estado', estadoInput.value || 'pendientes');
        window.history.pushState({ view: 'entregas_talleres_recibos', search: searchTerm }, '', url.toString());
    };

    const updateResults = (searchTerm) => {
        const url = new URL(searchEndpoint, window.location.origin);
        url.searchParams.set('taller_id', tallerIdInput.value);
        url.searchParams.set('estado', estadoInput.value || 'pendientes');

        if (searchTerm) {
            url.searchParams.set('busqueda', searchTerm);
        }

        resultsContent.innerHTML = '<div class="loading" style="padding: 40px; text-align: center;"><div class="loading-spinner"></div><p>Buscando recibos...</p></div>';

        fetch(url.toString(), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html'
            }
        })
            .then((response) => response.text())
            .then((html) => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const content = doc.querySelector('.results-content');

                if (content) {
                    resultsContent.innerHTML = content.innerHTML;
                } else {
                    resultsContent.innerHTML = '<div class="empty-state"><span class="material-symbols-rounded empty-state-icon">search_off</span><p>No se encontraron recibos para "' + escapeHtml(searchTerm) + '"</p></div>';
                }
            })
            .catch((error) => {
                console.error('Error en la búsqueda de recibos:', error);
                resultsContent.innerHTML = '<div class="empty-state"><span class="material-symbols-rounded empty-state-icon">error</span><p>Error al buscar recibos. Intenta de nuevo.</p></div>';
            });
    };

    const setActiveTab = (estado) => {
        estadoInput.value = estado || 'pendientes';
        const tabs = resultsContent.querySelectorAll('.tab-pill');
        tabs.forEach((tab) => {
            tab.classList.toggle('active', tab.dataset.estado === estadoInput.value);
        });
    };

    toggleClear();

    let searchTimeout;
    searchInput.addEventListener('input', function() {
        toggleClear();
        clearTimeout(searchTimeout);

        const searchTerm = this.value.trim();
        searchTimeout = setTimeout(() => {
            syncUrl(searchTerm);
            updateResults(searchTerm);
        }, 300);
    });

    const form = resultsForm;
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const searchTerm = searchInput.value.trim();
        syncUrl(searchTerm);
        updateResults(searchTerm);
    });

    if (clearButton) {
        clearButton.addEventListener('click', () => {
            searchInput.value = '';
            toggleClear();
            syncUrl('');
            updateResults('');
        });
    }

    resultsContent.addEventListener('click', (event) => {
        const tab = event.target.closest('.tab-pill');
        if (!tab || !resultsContent.contains(tab)) {
            return;
        }

        const estado = tab.dataset.estado || 'pendientes';
        setActiveTab(estado);
        syncUrl(searchInput.value.trim());
        updateResults(searchInput.value.trim());
    });

    window.addEventListener('popstate', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const searchTerm = urlParams.get('busqueda') || '';
        const estado = urlParams.get('estado') || 'pendientes';
        searchInput.value = searchTerm;
        estadoInput.value = estado;
        toggleClear();
        updateResults(searchTerm);
    });
}

function escapeHtml(value) {
    return String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function promptDelivery(talla, disponible, genero, color, safeId) {
    if (disponible <= 0) {
        Swal.fire({
            title: 'No hay más unidades',
            text: 'Ya se han entregado todas las unidades de esta talla.',
            icon: 'warning',
            confirmButtonColor: '#2450ef'
        });
        return;
    }

    Swal.fire({
        title: `Entrega Talla ${talla}`,
        text: `¿Cuántas unidades vas a entregar? (Máximo ${disponible})`,
        input: 'number',
        inputValue: disponible,
        showCancelButton: true,
        confirmButtonText: 'Registrar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#2450ef',
        didOpen: () => {
            const input = Swal.getInput();
            if (input) {
                input.focus();
                input.select();
            }
        },
        preConfirm: (value) => {
            if (!value || value < 1 || value > disponible) {
                Swal.showValidationMessage(`Ingresa una cantidad válida entre 1 y ${disponible}`);
            }
            return value;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            registrarEntrega(talla, parseInt(result.value), genero, color, safeId);
        }
    });
}

async function registrarEntrega(talla, cantidad, genero, color, safeId) {
    const container = document.getElementById('recibo-data');
    const reciboId = container.dataset.id;
    const esParcial = container.dataset.parcial;
    const esBodega = container.dataset.esBodega || '0';
    const prendaBodegaId = container.dataset.prendaBodegaId || '0';
    const routeRegistrar = container.dataset.routeRegistrar;

    try {
        const response = await fetch(routeRegistrar, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                recibo_id: reciboId,
                es_parcial: esParcial,
                es_bodega: esBodega,
                prenda_bodega_id: prendaBodegaId,
                talla: talla,
                cantidad: cantidad,
                genero: genero,
                color: color
            })
        });

        const data = await response.json();

        if (data.success) {
            if (data.completado) {
                Swal.fire({
                    title: '¡Recibo Completado!',
                    text: 'Todas las tallas han sido entregadas correctamente.',
                    icon: 'success',
                    confirmButtonColor: '#2450ef'
                });
            } else {
                Swal.fire({
                    title: '¡Registrado!',
                    text: 'La entrega se guardó correctamente.',
                    icon: 'success',
                    timer: 1500,
                    showConfirmButton: false
                });
            }

            // Actualizar UI localmente usando el safeId
            const deliveredEl = document.getElementById(`delivered-${safeId}`);
            const itemEl = document.getElementById(`talla-item-${safeId}`);
            const statusContainer = document.getElementById(`status-container-${safeId}`);

            if (deliveredEl && itemEl && statusContainer) {
                const currentDelivered = parseInt(deliveredEl.innerText) || 0;
                const totalText = deliveredEl.nextElementSibling.innerText;
                const total = parseInt(totalText.replace(/[^\d]/g, '')) || 0;
                
                const newDelivered = currentDelivered + cantidad;
                deliveredEl.innerText = newDelivered;
                
                if (newDelivered >= total) {
                    itemEl.classList.add('completed');
                    statusContainer.innerHTML = 'COMPLETADO';
                    
                    const btnAdd = itemEl.querySelector('.btn-add');
                    if (btnAdd) {
                        btnAdd.outerHTML = `
                            <div class="btn-completed">
                                <span class="material-symbols-rounded">check</span>
                            </div>
                        `;
                    }
                } else {
                    const disponiblesEl = document.getElementById(`disponibles-${safeId}`);
                    if (disponiblesEl) {
                        disponiblesEl.innerText = total - newDelivered;
                    } else {
                        statusContainer.innerHTML = `<span id="disponibles-${safeId}">${total - newDelivered}</span> DISPONIBLES`;
                    }
                }
            }

        } else {
            Swal.fire('Error', data.message || 'No se pudo registrar la entrega', 'error');
        }
    } catch (error) {
        Swal.fire('Error', 'Ocurrió un error en la comunicación con el servidor', 'error');
    }
}

async function loadHistorial() {
    const container = document.getElementById('historial-items-container');
    const dataContainer = document.getElementById('recibo-data');
    const reciboId = dataContainer.dataset.id;
    const esParcial = dataContainer.dataset.parcial;
    const esBodega = dataContainer.dataset.esBodega || '0';
    const prendaBodegaId = dataContainer.dataset.prendaBodegaId || '0';
    const routeHistorialBase = dataContainer.dataset.routeHistorial;

    container.innerHTML = '<div style="text-align:center; padding: 20px;">Cargando...</div>';
    
    try {
        const response = await fetch(`${routeHistorialBase}?es_parcial=${esParcial}&es_bodega=${esBodega}&prenda_bodega_id=${encodeURIComponent(prendaBodegaId)}`);
        const items = await response.json();
        
        if (items.length === 0) {
            container.innerHTML = '<div style="text-align:center; padding: 20px; color: #666;">No hay entregas registradas</div>';
            return;
        }

        container.innerHTML = '';
        items.forEach(item => {
            const html = `
                <div class="historial-item">
                    <div class="historial-info">
                        <div class="historial-title">${item.cantidad_total} unidades</div>
                        <div class="historial-date">${item.fecha} • <b>${item.encargado}</b></div>
                        <div style="font-size: 11px; color: #94a3b8; margin-top: 4px;">${item.detalle}</div>
                    </div>
                    <div class="historial-actions">
                        <button class="delete-historial-btn" onclick="deleteEntrega(${item.id})">
                            <span class="material-symbols-rounded">delete</span>
                        </button>
                    </div>
                </div>
            `;
            container.innerHTML += html;
        });
    } catch (error) {
        container.innerHTML = '<div style="text-align:center; color:red;">Error al cargar historial</div>';
    }
}

async function deleteEntrega(id) {
    const dataContainer = document.getElementById('recibo-data');
    const routeEliminarBase = dataContainer.dataset.routeEliminar.replace(':id', id);

    const result = await Swal.fire({
        title: '¿Eliminar entrega?',
        text: "Esta acción no se puede deshacer y el recibo dejará de estar marcado como completado si lo estaba.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#94a3b8',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    });

    if (result.isConfirmed) {
        try {
            const response = await fetch(routeEliminarBase, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            });
            
            const data = await response.json();
            
            if (data.success) {
                Swal.fire({
                    title: 'Eliminado',
                    text: data.message,
                    icon: 'success',
                    timer: 1500
                });
                location.reload();
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        } catch (error) {
            Swal.fire('Error', 'No se pudo eliminar la entrega', 'error');
        }
    }
}

function openHistorial() {
    document.getElementById('modal-overlay').style.display = 'block';
    document.getElementById('historial-modal').classList.add('show');
    loadHistorial();
}

function closeHistorial() {
    document.getElementById('modal-overlay').style.display = 'none';
    document.getElementById('historial-modal').classList.remove('show');
}
