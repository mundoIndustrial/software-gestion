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
    
    if (searchInput) {
        const toggleClear = () => {
            if (searchInput.value.length > 0) {
                clearButton.style.display = 'flex';
            } else {
                clearButton.style.display = 'none';
            }
        };

        toggleClear();
        searchInput.addEventListener('input', toggleClear);
    }
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

    // Ocultar todas las vistas
    if (viewTalleres) viewTalleres.style.display = 'none';
    if (viewRecibos) viewRecibos.style.display = 'none';
    if (viewEntregas) viewEntregas.style.display = 'none';

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
