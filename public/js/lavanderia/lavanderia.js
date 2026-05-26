/**
 * Módulo de Gestión de Lavandería
 * Maneja registros de salidas y llegadas con firma digital
 */

class LavanderiaManager {
    constructor() {
        this.currentRecibo = null;
        this.selectedTallas = [];
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.loadMovements();
    }

    setupEventListeners() {
        // Botón abrir modal salida
        const btnAbrirModal = document.getElementById('btnAbrirModalSalida');
        if (btnAbrirModal) {
            btnAbrirModal.addEventListener('click', () => this.openModalSalida());
        }

        // Búsqueda de recibo
        const searchInput = document.getElementById('searchRecibo');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => this.handleSearch(e));
            searchInput.addEventListener('blur', () => {
                setTimeout(() => {
                    const results = document.querySelector('.autocomplete-results');
                    if (results) results.classList.remove('active');
                }, 200);
            });
        }

        // Botón registrar salida
        const btnRegistrar = document.getElementById('btnRegistrarSalida');
        if (btnRegistrar) {
            btnRegistrar.addEventListener('click', () => this.registrarSalida());
        }

        // Modales
        document.querySelectorAll('.modal-close').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.target.closest('.modal').classList.remove('active');
            });
        });

        // Cerrar modal al hacer clic fuera
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.classList.remove('active');
                }
            });
        });

        // Botones de llegada en tabla
        document.addEventListener('click', (e) => {
            if (e.target.closest('.btn-registrar-llegada')) {
                const movementId = e.target.closest('.btn-registrar-llegada').dataset.movementId;
                this.openModalLlegada(movementId);
            }
        });
    }

    openModalSalida() {
        const modal = document.getElementById('modalSalida');
        if (modal) {
            modal.classList.add('active');
            this.clearSearch();
            this.currentRecibo = null;
            this.selectedTallas = [];
        }
    }

    clearSearch() {
        const searchInput = document.getElementById('searchRecibo');
        if (searchInput) searchInput.value = '';
        
        document.querySelector('.autocomplete-results').classList.remove('active');
        document.getElementById('reciboInfo').style.display = 'none';
    }

    handleSearch(e) {
        const query = e.target.value.trim();
        
        if (query.length < 2) {
            document.querySelector('.autocomplete-results').classList.remove('active');
            return;
        }

        this.searchRecibos(query);
    }

    searchRecibos(query) {
        // Mostrar loading
        const results = document.querySelector('.autocomplete-results');
        results.innerHTML = '<div style="padding: 12px; text-align: center; color: #94a3b8;">Buscando...</div>';
        results.classList.add('active');

        // Llamar a la API
        fetch(`${apiSearchUrl}?q=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data.length > 0) {
                    this.renderSearchResults(data.data);
                } else {
                    results.innerHTML = '<div style="padding: 12px; text-align: center; color: #94a3b8;">No se encontraron recibos</div>';
                }
            })
            .catch(error => {
                console.error('Error en búsqueda:', error);
                results.innerHTML = '<div style="padding: 12px; text-align: center; color: #ef4444;">Error al buscar</div>';
            });
    }

    renderSearchResults(recibos) {
        const results = document.querySelector('.autocomplete-results');
        const searchInput = document.getElementById('searchRecibo');
        
        results.innerHTML = recibos.map(recibo => `
            <div class="autocomplete-item" data-recibo-id="${recibo.id}" data-recibo-data='${JSON.stringify(recibo)}'>
                <div style="display: flex; justify-content: space-between; align-items: start; gap: 8px;">
                    <div style="flex: 1;">
                        <strong style="color: #1e293b; display: block; margin-bottom: 2px;">
                            Recibo #${recibo.numero_recibo}
                        </strong>
                        <small style="color: #64748b; display: block; margin-bottom: 4px;">
                            ${recibo.cliente}
                        </small>
                        <small style="color: #94a3b8; display: block;">
                            ${recibo.prenda} • ${recibo.tipo_recibo}
                        </small>
                    </div>
                    <span style="background: #f0f4ff; color: #2450ef; padding: 4px 8px; border-radius: 6px; font-size: 12px; font-weight: 600; white-space: nowrap;">
                        ${recibo.cantidad_total} prendas
                    </span>
                </div>
            </div>
        `).join('');
        
        // Posicionar el dropdown correctamente
        const rect = searchInput.getBoundingClientRect();
        results.style.top = (rect.bottom) + 'px';
        results.style.left = rect.left + 'px';
        results.style.width = rect.width + 'px';
        results.classList.add('active');

        // Agregar listeners a los items
        document.querySelectorAll('.autocomplete-item').forEach(item => {
            item.addEventListener('click', () => this.selectRecibo(item));
        });
    }

    selectRecibo(item) {
        const reciboId = item.dataset.reciboId;
        const reciboData = JSON.parse(item.dataset.reciboData);
        
        // Guardar recibo seleccionado
        this.currentRecibo = reciboData;
        this.selectedTallas = [];

        // Actualizar input
        document.getElementById('searchRecibo').value = `Recibo #${reciboData.numero_recibo}`;
        document.querySelector('.autocomplete-results').classList.remove('active');

        // Mostrar información del recibo
        this.showReciboInfo(reciboData);
    }

    showReciboInfo(recibo) {
        // Actualizar información
        document.getElementById('infoCliente').textContent = recibo.cliente;
        document.getElementById('infoPrenda').textContent = recibo.prenda;

        // Mostrar tarjeta de información
        document.getElementById('reciboInfo').style.display = 'block';

        // Generar tallas
        this.renderTallas(recibo);
    }

    renderTallas(recibo) {
        const tallasContainer = document.getElementById('tallasContainer');
        
        // Usar las tallas reales del recibo
        const tallas = recibo.tallas || [];

        if (tallas.length === 0) {
            tallasContainer.innerHTML = '<p style="color: #94a3b8; text-align: center;">No hay tallas disponibles</p>';
            return;
        }

        tallasContainer.innerHTML = tallas.map((talla, index) => `
            <label class="talla-item" data-talla-index="${index}">
                <input type="checkbox" class="talla-checkbox" data-talla="${talla.talla}" data-cantidad-disponible="${talla.cantidad}" data-genero="${talla.genero || ''}">
                <div class="talla-item-content">
                    <div class="talla-nombre">${talla.talla}${talla.genero ? ' (' + talla.genero + ')' : ''}</div>
                    <div class="talla-cantidad">DISP: ${talla.cantidad} pzas</div>
                </div>
                <input type="number" class="talla-input-cantidad" value="${talla.cantidad}" min="0" max="${talla.cantidad}" style="display: none;">
            </label>
        `).join('');

        // Agregar listeners a los checkboxes
        document.querySelectorAll('.talla-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', (e) => this.handleTallaChange(e));
        });

        // Agregar listeners a los inputs de cantidad
        document.querySelectorAll('.talla-input-cantidad').forEach(input => {
            input.addEventListener('change', (e) => this.handleCantidadChange(e));
        });
    }

    handleTallaChange(e) {
        const checkbox = e.target;
        const label = checkbox.closest('.talla-item');
        const input = label.querySelector('.talla-input-cantidad');
        const talla = checkbox.dataset.talla;
        const cantidadDisponible = parseInt(checkbox.dataset.cantidadDisponible);

        if (checkbox.checked) {
            label.classList.add('checked');
            input.style.display = 'block';
            input.value = cantidadDisponible;
            input.max = cantidadDisponible;
        } else {
            label.classList.remove('checked');
            input.style.display = 'none';
            this.selectedTallas = this.selectedTallas.filter(t => t.talla !== talla);
        }
    }

    handleCantidadChange(e) {
        const input = e.target;
        const label = input.closest('.talla-item');
        const checkbox = label.querySelector('.talla-checkbox');
        const talla = checkbox.dataset.talla;
        const cantidad = parseInt(input.value) || 0;
        const cantidadDisponible = parseInt(checkbox.dataset.cantidadDisponible);

        // Validar que no exceda la cantidad disponible
        if (cantidad > cantidadDisponible) {
            input.value = cantidadDisponible;
            return;
        }

        // Actualizar tallas seleccionadas
        const existingIndex = this.selectedTallas.findIndex(t => t.talla === talla);
        if (existingIndex >= 0) {
            this.selectedTallas[existingIndex].cantidad = cantidad;
        } else if (checkbox.checked) {
            this.selectedTallas.push({ 
                talla, 
                cantidad,
                genero: checkbox.dataset.genero
            });
        }
    }

    registrarSalida() {
        if (!this.currentRecibo) {
            alert('Por favor selecciona un recibo');
            return;
        }

        // Obtener tallas seleccionadas con cantidades
        const tallasSeleccionadas = [];
        document.querySelectorAll('.talla-checkbox:checked').forEach(checkbox => {
            const label = checkbox.closest('.talla-item');
            const input = label.querySelector('.talla-input-cantidad');
            const cantidad = parseInt(input.value) || 0;

            if (cantidad > 0) {
                tallasSeleccionadas.push({
                    talla: checkbox.dataset.talla,
                    genero: checkbox.dataset.genero || '',
                    cantidad_enviada: cantidad
                });
            }
        });

        if (tallasSeleccionadas.length === 0) {
            alert('Por favor selecciona al menos una talla con cantidad mayor a 0');
            return;
        }

        // Enviar datos al servidor
        const datos = {
            recibo_id: parseInt(this.currentRecibo.id),
            numero_recibo: String(this.currentRecibo.numero_recibo),
            tipo_recibo: String(this.currentRecibo.tipo_recibo),
            tallas: tallasSeleccionadas
        };

        console.log('Datos a enviar:', datos);

        fetch(`${apiSearchUrl.replace('search-recibos', 'registrar-salida')}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: JSON.stringify(datos)
        })
        .then(response => {
            console.log('Response status:', response.status);
            return response.json().then(data => ({
                status: response.status,
                data: data
            }));
        })
        .then(result => {
            console.log('Response data:', result);
            if (result.data.success) {
                alert('Salida registrada exitosamente');
                document.getElementById('modalSalida').classList.remove('active');
                this.loadMovements();
            } else {
                alert('Error: ' + (result.data.message || 'No se pudo registrar la salida'));
                if (result.data.errors) {
                    console.error('Errores de validación:', result.data.errors);
                }
            }
        })
        .catch(error => {
            console.error('Error al registrar:', error);
            alert('Error al registrar la salida');
        });
    }

    openModalLlegada(movementId) {
        console.log('Abrir modal llegada para movimiento:', movementId);
    }

    loadMovements() {
        // Cargar movimientos desde la API
        const apiUrl = apiSearchUrl.replace('search-recibos', 'movimientos');
        fetch(apiUrl)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.renderMovements(data.data);
                } else {
                    console.error('Error al cargar movimientos:', data.message);
                    this.renderMovements([]);
                }
            })
            .catch(error => {
                console.error('Error en loadMovements:', error);
                this.renderMovements([]);
            });
    }

    renderMovements(movements) {
        const tableBody = document.querySelector('.control-table tbody');
        if (!tableBody) return;

        if (movements.length === 0) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="3" style="text-align: center; padding: 40px;">
                        <div class="empty-state">
                            <div class="empty-icon">📦</div>
                            <h3 class="empty-title">Sin movimientos</h3>
                            <p class="empty-text">No hay registros de salidas aún</p>
                        </div>
                    </td>
                </tr>
            `;
            return;
        }

        tableBody.innerHTML = movements.map(m => {
            // Determinar el tipo de badge según el tipo de recibo
            let tipoBadgeClass = 'tipo-costura';
            if (m.estado.includes('CORTE')) tipoBadgeClass = 'tipo-corte';
            else if (m.estado.includes('ESTAMPADO')) tipoBadgeClass = 'tipo-estampado';
            else if (m.estado.includes('BORDADO')) tipoBadgeClass = 'tipo-bordado';

            // Construir las tallas enviadas
            const tallasHtml = m.tallas.map(t => 
                `<span class="talla-badge">Talla ${t.talla}: ${t.cantidad_enviada}</span>`
            ).join('');

            return `
                <tr>
                    <td>
                        <div class="recibo-tipo-cell">
                            <div class="recibo-numero">C-${m.recibo}</div>
                        </div>
                    </td>
                    <td>
                        <div class="cliente-cell">
                            <div class="cliente-nombre">${m.cliente}</div>
                        </div>
                    </td>
                    <td>
                        <div class="prenda-tallas-cell">
                            <div class="prenda-nombre">${m.prenda}</div>
                            <div class="tallas-enviadas">
                                ${tallasHtml}
                            </div>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    new LavanderiaManager();
});
