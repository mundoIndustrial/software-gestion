/**
 * REGISTRATION HANDLER - Lavandería
 * Maneja el registro de nuevos movimientos con múltiples recibos
 */

class RegistrationHandler {
    constructor(apiSearchUrl, tallasHandler, multiReceiptHandler) {
        this.apiSearchUrl = apiSearchUrl;
        this.tallasHandler = tallasHandler;
        this.multiReceiptHandler = multiReceiptHandler;
        this.currentReciboBeingEdited = null;
    }

    /**
     * Maneja la selección de un recibo desde el buscador
     */
    handleReciboSelected(recibo) {
        // Agregar el recibo a la selección
        const added = this.multiReceiptHandler.addRecibo(recibo);
        
        if (!added) {
            window.dispatchEvent(new CustomEvent('showToast', { 
                detail: { title: 'Recibo Duplicado', message: 'Este recibo ya está seleccionado', type: 'warning' }
            }));
            return;
        }

        // Limpiar búsqueda
        const searchInput = document.getElementById('searchRecibo');
        if (searchInput) searchInput.value = '';
        
        const resultsContainer = document.querySelector('.autocomplete-results');
        if (resultsContainer) resultsContainer.classList.remove('active');

        // Renderizar recibos seleccionados
        this.renderSelectedRecibos();

        // Mostrar sección de tallas
        this.showTallasSection();

        window.dispatchEvent(new CustomEvent('showToast', { 
            detail: { title: 'Recibo Agregado', message: `Recibo #${recibo.numero_recibo} agregado a la selección`, type: 'success' }
        }));
    }

    /**
     * Renderiza los recibos seleccionados
     */
    renderSelectedRecibos() {
        const container = document.getElementById('recibosSeleccionadosContainer');
        if (!container) return;

        this.multiReceiptHandler.renderSelectedRecibos(container);

        // Agregar event listeners a los botones de eliminar
        document.querySelectorAll('.btn-remove-recibo').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const reciboId = parseInt(e.currentTarget.dataset.reciboId);
                this.removeRecibo(reciboId);
            });
        });

        // Agregar event listeners a las tarjetas de recibos para editar tallas
        document.querySelectorAll('.recibo-card').forEach(card => {
            card.addEventListener('click', (e) => {
                if (!e.target.closest('.btn-remove-recibo')) {
                    const reciboId = parseInt(card.dataset.reciboId);
                    this.editReciboTallas(reciboId);
                }
            });
        });
    }

    /**
     * Elimina un recibo de la selección
     */
    removeRecibo(reciboId) {
        this.multiReceiptHandler.removeRecibo(reciboId);
        this.renderSelectedRecibos();

        if (this.multiReceiptHandler.getCount() === 0) {
            this.hideTallasSection();
        } else {
            this.showTallasSection();
        }

        window.dispatchEvent(new CustomEvent('showToast', { 
            detail: { title: 'Recibo Eliminado', message: 'El recibo ha sido removido de la selección', type: 'info' }
        }));
    }

    /**
     * Edita las tallas de un recibo específico
     */
    editReciboTallas(reciboId) {
        const recibo = this.multiReceiptHandler.selectedRecibos.find(r => r.id === reciboId);
        if (!recibo) return;

        this.currentReciboBeingEdited = reciboId;
        this.renderTallasForRecibo(recibo);
    }

    /**
     * Renderiza las tallas para un recibo específico
     */
    renderTallasForRecibo(recibo) {
        const container = document.getElementById('tallasContenedor');
        if (!container) return;

        const tallas = recibo.tallas || [];

        if (tallas.length === 0) {
            container.innerHTML = `
                <div style="text-align: center; padding: 20px; color: #94a3b8;">
                    <p>No hay tallas disponibles para este recibo</p>
                </div>
            `;
            return;
        }

        container.innerHTML = `
            <div style="margin-bottom: 16px; padding-bottom: 16px; border-bottom: 1px solid #e2e8f0;">
                <div style="font-weight: 600; color: #1e293b; margin-bottom: 8px;">
                    Recibo #${recibo.numero_recibo} - ${recibo.tipo_recibo_mostrar}
                </div>
                <div style="font-size: 13px; color: #64748b;">
                    ${recibo.prenda}
                </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 12px;">
                ${tallas.map(talla => `
                    <div class="talla-input-group" 
                         data-talla-id="${talla.id}" 
                         data-talla-nombre="${talla.talla}"
                         data-genero="${talla.genero || ''}"
                         style="
                        border: 1px solid #e2e8f0;
                        border-radius: 8px;
                        padding: 12px;
                        background: white;
                        position: relative;
                        display: flex;
                        flex-direction: column;
                    ">
                        <!-- Checkbox en esquina superior derecha -->
                        <input 
                            type="checkbox" 
                            class="talla-checkbox" 
                            data-talla-id="${talla.id}"
                            style="
                                position: absolute;
                                top: 8px;
                                right: 8px;
                                width: 18px;
                                height: 18px;
                                cursor: pointer;
                                accent-color: #2450ef;
                                margin: 0;
                            "
                        >
                        
                        <!-- Nombre de talla -->
                        <div style="font-size: 12px; color: #64748b; font-weight: 600; margin-bottom: 4px; padding-right: 24px;">
                            ${talla.talla}
                        </div>
                        
                        <!-- Género -->
                        ${talla.genero ? `<div style="font-size: 11px; color: #94a3b8; margin-bottom: 8px;">${talla.genero}</div>` : ''}
                        
                        <!-- Input de cantidad -->
                        <input 
                            type="number" 
                            class="talla-cantidad-input" 
                            min="0" 
                            max="${talla.cantidad}" 
                            value="0"
                            placeholder="Cant."
                            disabled
                            style="
                                width: 100%;
                                padding: 6px;
                                border: 1px solid #cbd5e1;
                                border-radius: 4px;
                                font-size: 13px;
                                text-align: center;
                                background: #f8fafc;
                                cursor: not-allowed;
                                margin-bottom: 4px;
                            "
                        >
                        
                        <!-- Disponible -->
                        <div style="font-size: 11px; color: #94a3b8;">
                            Disponible: ${talla.cantidad}
                        </div>
                    </div>
                `).join('')}
            </div>

            <div style="margin-top: 16px; display: flex; gap: 8px;">
                <button class="btn btn-secondary" id="btnCancelarEdicionTallas" style="flex: 1;">
                    Cancelar
                </button>
                <button class="btn btn-primary" id="btnGuardarEdicionTallas" style="flex: 1;">
                    Guardar Tallas
                </button>
            </div>
        `;

        // Event listeners para checkboxes
        document.querySelectorAll('.talla-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', (e) => {
                const tallaGroup = e.target.closest('.talla-input-group');
                const cantidadInput = tallaGroup.querySelector('.talla-cantidad-input');
                const tallaId = e.target.dataset.tallaId;
                
                // Obtener la talla correspondiente
                const talla = tallas.find(t => t.id == tallaId);
                
                if (e.target.checked) {
                    // Habilitar input y llenar con cantidad disponible
                    cantidadInput.disabled = false;
                    cantidadInput.value = talla.cantidad;
                    cantidadInput.style.background = 'white';
                    cantidadInput.style.cursor = 'text';
                    cantidadInput.focus();
                } else {
                    // Deshabilitar input y limpiar
                    cantidadInput.disabled = true;
                    cantidadInput.value = '0';
                    cantidadInput.style.background = '#f8fafc';
                    cantidadInput.style.cursor = 'not-allowed';
                }
            });
        });

        // Event listeners para botones
        document.getElementById('btnCancelarEdicionTallas').addEventListener('click', () => {
            this.showTallasSection();
        });

        document.getElementById('btnGuardarEdicionTallas').addEventListener('click', () => {
            this.saveTallasForRecibo();
        });
    }

    /**
     * Guarda las tallas seleccionadas para un recibo
     */
    saveTallasForRecibo() {
        if (!this.currentReciboBeingEdited) return;

        const recibo = this.multiReceiptHandler.selectedRecibos.find(r => r.id === this.currentReciboBeingEdited);
        if (!recibo) return;

        const tallasInputs = document.querySelectorAll('.talla-checkbox:checked');
        const selectedTallas = [];

        tallasInputs.forEach(checkbox => {
            const tallaGroup = checkbox.closest('.talla-input-group');
            const cantidadInput = tallaGroup.querySelector('.talla-cantidad-input');
            const cantidad = parseInt(cantidadInput.value) || 0;

            if (cantidad > 0) {
                const tallaId = tallaGroup.dataset.tallaId;
                const tallaNombre = tallaGroup.dataset.tallaNombre;
                const genero = tallaGroup.dataset.genero || null;

                // Obtener información de prenda del recibo
                const tipoPrenda = recibo.tipo_recibo_original === 'CORTE-PARA-BODEGA' ? 'BODEGA' : 'COSTURA';
                const prendaId = tipoPrenda === 'BODEGA' ? recibo.prenda_bodega_id : recibo.prenda_id;

                selectedTallas.push({
                    id: tallaId,
                    talla: tallaNombre,
                    genero: genero,
                    cantidad_enviada: cantidad,
                    tipo_prenda: tipoPrenda,
                    prenda_id: tipoPrenda === 'COSTURA' ? prendaId : null,
                    prenda_bodega_id: tipoPrenda === 'BODEGA' ? prendaId : null
                });
            }
        });

        if (selectedTallas.length === 0) {
            window.dispatchEvent(new CustomEvent('showToast', { 
                detail: { title: 'Tallas Requeridas', message: 'Por favor selecciona al menos una talla con cantidad mayor a 0', type: 'error' }
            }));
            return;
        }

        this.multiReceiptHandler.setSelectedTallasForRecibo(this.currentReciboBeingEdited, selectedTallas);
        this.currentReciboBeingEdited = null;
        this.showTallasSection();

        window.dispatchEvent(new CustomEvent('showToast', { 
            detail: { title: 'Tallas Guardadas', message: 'Las tallas han sido guardadas correctamente', type: 'success' }
        }));
    }

    /**
     * Muestra la sección de tallas
     */
    showTallasSection() {
        const section = document.getElementById('tallasSection');
        if (section) {
            section.style.display = 'block';
            this.renderTallasResumen();
        }
    }

    /**
     * Oculta la sección de tallas
     */
    hideTallasSection() {
        const section = document.getElementById('tallasSection');
        if (section) {
            section.style.display = 'none';
        }
    }

    /**
     * Renderiza un resumen de las tallas seleccionadas
     */
    renderTallasResumen() {
        const container = document.getElementById('tallasContenedor');
        if (!container) return;

        const recibos = this.multiReceiptHandler.selectedRecibos;

        if (recibos.length === 0) {
            container.innerHTML = '<p style="color: #94a3b8; text-align: center;">No hay recibos seleccionados</p>';
            return;
        }

        container.innerHTML = recibos.map(recibo => {
            const selectedTallas = this.multiReceiptHandler.getSelectedTallasForRecibo(recibo.id);
            const tallasHtml = selectedTallas.length > 0
                ? selectedTallas.map(t => `<span class="talla-badge">Talla ${t.talla}: ${t.cantidad_enviada}</span>`).join('')
                : '<span style="color: #94a3b8; font-size: 13px;">Sin tallas seleccionadas</span>';

            return `
                <div style="
                    background: #f8fafc;
                    border: 1px solid #e2e8f0;
                    border-radius: 8px;
                    padding: 12px;
                    margin-bottom: 12px;
                    cursor: pointer;
                    transition: all 0.2s;
                " class="recibo-tallas-item" data-recibo-id="${recibo.id}">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <div style="font-weight: 600; color: #1e293b;">
                            Recibo #${recibo.numero_recibo} - ${recibo.tipo_recibo_mostrar}
                        </div>
                        <span class="material-symbols-rounded" style="font-size: 18px; color: #64748b;">edit</span>
                    </div>
                    <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                        ${tallasHtml}
                    </div>
                </div>
            `;
        }).join('');

        // Event listeners para editar tallas
        document.querySelectorAll('.recibo-tallas-item').forEach(item => {
            item.addEventListener('click', () => {
                const reciboId = parseInt(item.dataset.reciboId);
                this.editReciboTallas(reciboId);
            });
        });
    }

    /**
     * Registra una salida con múltiples recibos
     */
    registrarSalida() {
        const recibos = this.multiReceiptHandler.getSelectedRecibos();

        if (recibos.length === 0) {
            window.dispatchEvent(new CustomEvent('showToast', { 
                detail: { title: 'Recibos Requeridos', message: 'Por favor selecciona al menos un recibo', type: 'error' }
            }));
            return;
        }

        // Verificar que todos los recibos tengan tallas seleccionadas
        for (const recibo of recibos) {
            const selectedTallas = this.multiReceiptHandler.getSelectedTallasForRecibo(recibo.id);
            if (selectedTallas.length === 0) {
                window.dispatchEvent(new CustomEvent('showToast', { 
                    detail: { title: 'Tallas Incompletas', message: `Por favor selecciona tallas para el recibo #${recibo.numero_recibo}`, type: 'error' }
                }));
                return;
            }
        }

        const novedad = document.getElementById('inputNovedad').value.trim();
        const tipoMovimiento = document.getElementById('selectTipoMovimiento').value;

        // Preparar datos para enviar
        const datos = {
            tipo_movimiento: tipoMovimiento,
            novedad: novedad,
            recibos: recibos.map(r => ({
                recibo_id: r.id,
                numero_recibo: r.numero_recibo,
                tipo_recibo: r.tipo_recibo_original
            })),
            tallas: this.multiReceiptHandler.getAllSelectedTallas()
        };

        fetch(`${this.apiSearchUrl.replace('search-recibos', 'registrar-salida')}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: JSON.stringify(datos)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.dispatchEvent(new CustomEvent('showToast', { 
                    detail: { title: '¡Movimiento Registrado!', message: `Movimiento registrado con ${recibos.length} recibo(s)`, type: 'success' }
                }));
                document.getElementById('modalSalida').classList.remove('active');
                window.dispatchEvent(new CustomEvent('reloadMovements'));
                this.clearForm();
            } else {
                window.dispatchEvent(new CustomEvent('showToast', { 
                    detail: { title: 'Error', message: data.message || 'No se pudo registrar el movimiento', type: 'error' }
                }));
            }
        })
        .catch(error => {
            console.error('Error al registrar:', error);
            window.dispatchEvent(new CustomEvent('showToast', { 
                detail: { title: 'Error', message: 'Error al registrar el movimiento', type: 'error' }
            }));
        });
    }

    /**
     * Limpia el formulario
     */
    clearForm() {
        const searchInput = document.getElementById('searchRecibo');
        if (searchInput) searchInput.value = '';
        
        const resultsContainer = document.querySelector('.autocomplete-results');
        if (resultsContainer) resultsContainer.classList.remove('active');
        
        document.getElementById('inputNovedad').value = '';
        this.multiReceiptHandler.clear();
        this.currentReciboBeingEdited = null;
        this.hideTallasSection();
        
        const container = document.getElementById('recibosSeleccionadosContainer');
        if (container) {
            container.innerHTML = '<p style="color: #94a3b8; text-align: center; margin: 0;">No hay recibos seleccionados</p>';
        }
    }

    /**
     * Abre el modal de registro
     */
    openModalSalida() {
        const modal = document.getElementById('modalSalida');
        if (modal) {
            modal.classList.add('active');
            this.clearForm();
        }
    }
}

export { RegistrationHandler };
