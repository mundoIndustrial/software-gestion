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
});

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
    const cards = document.querySelectorAll('.taller-card');
    
    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            const term = e.target.value.toLowerCase().trim();
            
            cards.forEach(card => {
                const name = card.getAttribute('data-name');
                if (name && name.includes(term)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });

        if (clearButton) {
            clearButton.addEventListener('click', function(e) {
                e.preventDefault();
                searchInput.value = '';
                searchInput.focus();
                
                cards.forEach(card => {
                    card.style.display = 'block';
                });
            });
        }
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

            let html = '<div class="table-container"><table class="table-recibos"><thead><tr><th>Nº RECIBO</th><th>CLIENTE</th><th>DESCRIPCIÓN PRENDA</th><th>ESTADO</th><th>ACCIONES</th></tr></thead><tbody>';

            data.recibos.forEach(recibo => {
                html += `
                    <tr>
                        <td class="col-recibo">${recibo.numero_recibo}</td>
                        <td class="col-cliente">${recibo.cliente}</td>
                        <td>
                            <div class="prenda-nombre">${recibo.nombre_prenda}</div>
                            <p class="prenda-desc">${recibo.descripcion_prenda || ''}</p>
                        </td>
                        <td>-</td>
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
                html += '<table class="table-entregas"><thead><tr><th class="col-fecha">FECHA</th><th>DESCRIPCIÓN</th><th class="col-genero">GÉNERO</th><th class="col-talla">TALLA</th><th class="col-cantidad">CANT.</th><th>PROGRESO</th></tr></thead><tbody>';

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
                                    <div class="progress-bar-wrapper">
                                        <div class="progress-bar-fill" style="width: ${porcentaje}%"></div>
                                    </div>
                                    <span class="progress-text">${totalEntregado}/${totalAsignado}</span>
                                </div>
                            </td>
                        </tr>
                    `;
                });

                html += '</tbody></table></div>';
            });

            entregasContent.innerHTML = html;
        })
        .catch(error => {
            console.error('Error:', error);
            entregasContent.innerHTML = '<div class="empty-state"><p>Error al cargar las entregas.</p></div>';
        });
}
