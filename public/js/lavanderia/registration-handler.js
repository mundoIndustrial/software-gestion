/**
 * REGISTRATION HANDLER - Lavandería
 * Maneja el registro de nuevos movimientos con múltiples recibos
 */

class RegistrationHandler {
    constructor(apiSearchUrl, tallasHandler, multiReceiptHandler, manualPrendaHandler) {
        this.apiSearchUrl = apiSearchUrl;
        this.tallasHandler = tallasHandler;
        this.multiReceiptHandler = multiReceiptHandler;
        this.manualPrendaHandler = manualPrendaHandler;
        this.currentReciboBeingEdited = null;
        this.currentManualPrendaBeingEdited = null;
        this.currentManualPrendaDraft = null;
    }

    getCatalogoTallasPorGenero(genero) {
        const generoNormalizado = String(genero || '').trim().toUpperCase();
        const catalogo = {
            DAMA: ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL', 'XXXXL'],
            CABALLERO: ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL', 'XXXXL'],
            UNISEX: ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL', 'XXXXL']
        };

        return catalogo[generoNormalizado] || catalogo.UNISEX;
    }

    getLabelGenero(genero) {
        const generoNormalizado = String(genero || '').trim().toUpperCase();
        const labels = {
            DAMA: 'Dama',
            CABALLERO: 'Caballero',
            UNISEX: 'Unisex'
        };

        return labels[generoNormalizado] || 'Sin género';
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

        // Refrescar la lista y mostrar inmediatamente la sección de tallas para este recibo
        this.renderSelectedRecibos();
        this.editReciboTallas(recibo.id);
    }

    /**
     * Renderiza los recibos seleccionados
     */
    renderSelectedRecibos() {
        const container = document.getElementById('recibosSeleccionadosContainer');
        if (!container) return;

        const recibos = this.multiReceiptHandler.selectedRecibos;

        if (recibos.length === 0) {
            container.innerHTML = '<p style="color: #94a3b8; text-align: center; margin: 0;">No hay recibos agregados</p>';
            return;
        }

        container.innerHTML = recibos.map(recibo => {
            // Determinar colores según tipo de recibo
            let colorTipo = '#2450ef'; // Azul para COSTURA
            let bgColorTipo = '#f0f4ff';
            let borderColorTipo = '#bfdbfe';
            
            if (recibo.tipo_recibo_mostrar === 'BODEGA') {
                colorTipo = '#059669'; // Verde para BODEGA
                bgColorTipo = '#f0fdf4';
                borderColorTipo = '#86efac';
            }

            const selectedTallas = this.multiReceiptHandler.getSelectedTallasForRecibo(recibo.id);
            const tallasHtml = selectedTallas.length > 0
                ? selectedTallas.map(t => `<span class="talla-badge" style="display: inline-block; background: ${bgColorTipo}; color: ${colorTipo}; padding: 6px 12px; border-radius: 6px; font-size: 12px; margin-right: 4px; margin-bottom: 4px; font-weight: 500;">${t.talla}: ${t.cantidad_enviada}</span>`).join('')
                : '<span style="color: #94a3b8; font-size: 13px;">Sin tallas seleccionadas</span>';

            return `
                <div style="
                    background: ${bgColorTipo};
                    border: 2px solid ${borderColorTipo};
                    border-radius: 8px;
                    padding: 12px;
                    margin-bottom: 12px;
                    transition: all 0.2s;
                " class="recibo-card-agregado" data-recibo-id="${recibo.id}">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                        <div>
                            <div style="font-weight: 600; color: #1e293b; font-size: 14px;">
                                Recibo #${recibo.numero_recibo}
                            </div>
                            <div style="font-size: 12px; color: ${colorTipo}; margin-top: 2px; font-weight: 500;">
                                ${recibo.tipo_recibo_mostrar}
                            </div>
                        </div>
                        <button class="btn-remove-recibo" data-recibo-id="${recibo.id}" style="
                            background: none;
                            border: none;
                            color: #ef4444;
                            cursor: pointer;
                            font-size: 20px;
                            padding: 0;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            width: 24px;
                            height: 24px;
                        ">
                            <span class="material-symbols-rounded" style="font-size: 20px;">close</span>
                        </button>
                    </div>
                    <div style="font-size: 12px; color: #64748b; margin-bottom: 8px; padding-bottom: 8px; border-bottom: 1px solid ${borderColorTipo};">
                        ${recibo.prenda}
                    </div>
                    <div style="display: flex; flex-wrap: wrap; gap: 4px; margin-bottom: 8px;">
                        ${tallasHtml}
                    </div>
                    <button class="btn-edit-recibo" data-recibo-id="${recibo.id}" style="
                        background: white;
                        border: 1px solid ${borderColorTipo};
                        color: ${colorTipo};
                        padding: 6px 12px;
                        border-radius: 4px;
                        font-size: 12px;
                        cursor: pointer;
                        font-weight: 500;
                        display: flex;
                        align-items: center;
                        gap: 4px;
                        transition: all 0.2s;
                    ">
                        <span class="material-symbols-rounded" style="font-size: 16px;">edit</span>
                        Editar Tallas
                    </button>
                </div>
            `;
        }).join('');

        // Agregar event listeners a los botones de eliminar
        document.querySelectorAll('.btn-remove-recibo').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const reciboId = parseInt(e.currentTarget.dataset.reciboId);
                this.removeRecibo(reciboId);
            });
        });

        // Agregar event listeners a los botones de editar
        document.querySelectorAll('.btn-edit-recibo').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const reciboId = parseInt(e.currentTarget.dataset.reciboId);
                this.editReciboTallas(reciboId);
            });
        });
    }

    /**
     * Elimina un recibo de la selección
     */
    removeRecibo(reciboId) {
        this.multiReceiptHandler.removeRecibo(reciboId);
        this.renderSelectedRecibos();
    }

    /**
     * Edita las tallas de un recibo específico
     */
    editReciboTallas(reciboId) {
        const recibo = this.multiReceiptHandler.selectedRecibos.find(r => r.id === reciboId);
        if (!recibo) {
            return;
        }

        this.currentReciboBeingEdited = reciboId;
        this.showTallasSection();
        this.renderTallasForRecibo(recibo);
        
        // Scroll a la sección de tallas con un pequeño delay
        setTimeout(() => {
            const tallasSection = document.getElementById('tallasSection');
            if (tallasSection) {
                tallasSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }, 100);
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
            this.hideTallasSection();
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
        
        // Mostrar el recibo en la lista de recibos agregados
        this.renderSelectedRecibos();
        
        // Ocultar la sección de tallas
        this.hideTallasSection();
    }

    /**
     * Registra una salida con múltiples recibos y prendas manuales
     */
    registrarSalida() {
        const recibos = this.multiReceiptHandler.getSelectedRecibos();
        const prendasManuales = this.manualPrendaHandler.getAllManualPrendas();

        if (recibos.length === 0 && prendasManuales.length === 0) {
            window.dispatchEvent(new CustomEvent('showToast', { 
                detail: { title: 'Contenido Requerido', message: 'Por favor agrega al menos un recibo o una prenda manual', type: 'error' }
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

        // Verificar que todas las prendas manuales tengan tallas seleccionadas
        for (const prenda of prendasManuales) {
            const selectedTallas = this.manualPrendaHandler.getSelectedTallasForManualPrenda(prenda.id);
            if (selectedTallas.length === 0) {
                window.dispatchEvent(new CustomEvent('showToast', { 
                    detail: { title: 'Tallas Incompletas', message: `Por favor selecciona tallas para la prenda manual: ${prenda.descripcion}`, type: 'error' }
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
            prendas_manuales: prendasManuales.map(p => ({
                temp_id: p.id,
                descripcion: p.descripcion,
                genero: p.genero || null
            })),
            tallas: [
                // Tallas de recibos
                ...recibos.flatMap(recibo => {
                    const selectedTallas = this.multiReceiptHandler.getSelectedTallasForRecibo(recibo.id);
                    return selectedTallas.map(t => ({
                        ...t,
                        prenda_id: recibo.tipo_recibo_original === 'CORTE-PARA-BODEGA' ? null : recibo.prenda_id,
                        prenda_bodega_id: recibo.tipo_recibo_original === 'CORTE-PARA-BODEGA' ? recibo.prenda_bodega_id : null
                    }));
                }),
                // Tallas de prendas manuales
                ...prendasManuales.flatMap((p) => {
                    const tallas = this.manualPrendaHandler.getSelectedTallasForManualPrenda(p.id);
                    return tallas.map(t => ({
                        ...t,
                        prenda_agregada_id: p.id
                    }));
                })
            ]
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
                    detail: { title: '¡Movimiento Registrado!', message: `Movimiento registrado exitosamente`, type: 'success' }
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
        document.getElementById('inputDescripcionPrenda').value = '';
        
        const formAgregarPrenda = document.getElementById('formAgregarPrendaManual');
        if (formAgregarPrenda) formAgregarPrenda.style.display = 'none';
        const selectGeneroPrendaManual = document.getElementById('selectGeneroPrendaManual');
        if (selectGeneroPrendaManual) selectGeneroPrendaManual.value = '';
        
        this.multiReceiptHandler.clear();
        this.manualPrendaHandler.clear();
        this.currentReciboBeingEdited = null;
        this.currentManualPrendaBeingEdited = null;
        this.currentManualPrendaDraft = null;
        this.hideTallasSection();
        
        const container = document.getElementById('recibosSeleccionadosContainer');
        if (container) {
            container.innerHTML = '<p style="color: #94a3b8; text-align: center; margin: 0;">No hay recibos agregados</p>';
        }

        const containerManual = document.getElementById('prendasManualContainer');
        if (containerManual) {
            containerManual.innerHTML = '<p style="color: #94a3b8; text-align: center; margin: 0;">No hay prendas manuales agregadas</p>';
        }
    }

    /**
     * Muestra la sección de tallas
     */
    showTallasSection() {
        const section = document.getElementById('tallasSection');
        if (section) {
            section.style.display = 'block';
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
     * Abre el modal de registro
     */
    openModalSalida() {
        const modal = document.getElementById('modalSalida');
        if (modal) {
            modal.classList.add('active');
            this.clearForm();
        }
    }

    /**
     * Agrega una prenda manual
     */
    agregarPrendaManual() {
        const descripcion = document.getElementById('inputDescripcionPrenda').value.trim();
        const genero = document.getElementById('selectGeneroPrendaManual')?.value?.trim().toUpperCase() || '';

        if (!descripcion) {
            window.dispatchEvent(new CustomEvent('showToast', { 
                detail: { title: 'Descripción Requerida', message: 'Por favor ingresa una descripción para la prenda', type: 'error' }
            }));
            return;
        }

        if (!genero) {
            window.dispatchEvent(new CustomEvent('showToast', { 
                detail: { title: 'Género Requerido', message: 'Por favor selecciona el género de la prenda', type: 'error' }
            }));
            return;
        }

        this.currentManualPrendaDraft = {
            descripcion,
            genero,
            selectedTallas: []
        };
        this.currentManualPrendaBeingEdited = null;

        // Ocultar el formulario base y pasar directamente al editor de tallas
        document.getElementById('formAgregarPrendaManual').style.display = 'none';

        this.showTallasSection();
        this.renderTallasForManualPrenda(this.currentManualPrendaDraft);

        // Scroll a la sección de tallas
        setTimeout(() => {
            const tallasSection = document.getElementById('tallasSection');
            if (tallasSection) {
                tallasSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }, 100);
    }

    /**
     * Edita las tallas de una prenda manual
     */
    editManualPrendaTallas(tempId) {
        const prenda = this.manualPrendaHandler.getManualPrenda(tempId);
        if (!prenda) return;

        this.currentManualPrendaDraft = null;
        this.currentManualPrendaBeingEdited = tempId;
        this.showTallasSection();
        this.renderTallasForManualPrenda(prenda);

        // Scroll a la sección de tallas
        setTimeout(() => {
            const tallasSection = document.getElementById('tallasSection');
            if (tallasSection) {
                tallasSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }, 100);
    }

    /**
     * Renderiza las tallas para una prenda manual
     */
    renderTallasForManualPrenda(prenda) {
        const container = document.getElementById('tallasContenedor');
        if (!container) return;

        const genero = prenda.genero || 'UNISEX';
        const tallasDisponibles = this.getCatalogoTallasPorGenero(genero);

        // Para prendas manuales, mostrar un formulario para agregar tallas
        container.innerHTML = `
            <div style="margin-bottom: 16px; padding-bottom: 16px; border-bottom: 1px solid #e2e8f0;">
                <div style="font-weight: 600; color: #1e293b; margin-bottom: 8px;">
                    Prenda Manual
                </div>
                <div style="font-size: 13px; color: #64748b;">
                    ${prenda.descripcion}
                </div>
                <div style="font-size: 12px; color: #2450ef; margin-top: 6px; font-weight: 600;">
                    Género: ${this.getLabelGenero(genero)}
                </div>
            </div>

            <div style="margin-bottom: 16px;">
                <label class="form-label">Agregar Tallas</label>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 12px;">
                    <div>
                        <label style="font-size: 12px; color: #64748b; display: block; margin-bottom: 4px;">Talla</label>
                        <select id="selectTallaManual" class="form-select" style="padding: 8px;">
                            ${tallasDisponibles.map(talla => `<option value="${talla}">${talla}</option>`).join('')}
                        </select>
                    </div>
                    <div>
                        <label style="font-size: 12px; color: #64748b; display: block; margin-bottom: 4px;">Cantidad</label>
                        <input type="number" id="inputTallaCantidad" class="form-input" placeholder="0" min="0" style="padding: 8px;">
                    </div>
                    <div style="display: flex; align-items: flex-end;">
                        <button type="button" class="btn btn-secondary" id="btnAgregarTalla" style="width: 100%;">
                            <span class="material-symbols-rounded" style="font-size: 18px;">add</span>
                        </button>
                    </div>
                </div>
            </div>

            <div id="tallasAgregadasContainer" style="margin-bottom: 16px;">
                <label class="form-label">Tallas Agregadas</label>
                <div id="listaTallasAgregadas" style="display: flex; flex-wrap: wrap; gap: 8px;">
                    ${prenda.selectedTallas.length > 0 
                        ? prenda.selectedTallas.map((t, idx) => `
                            <div style="background: #f0f9ff; border: 1px solid #bfdbfe; padding: 8px 12px; border-radius: 6px; display: flex; align-items: center; gap: 8px;">
                                <span style="color: #1e40af; font-weight: 500;">${this.getLabelGenero(t.genero || genero)} - ${t.talla}: ${t.cantidad_enviada}</span>
                                <button type="button" class="btn-remove-talla" data-talla-idx="${idx}" style="background: none; border: none; color: #ef4444; cursor: pointer; padding: 0; font-size: 16px;">
                                    <span class="material-symbols-rounded" style="font-size: 16px;">close</span>
                                </button>
                            </div>
                        `).join('')
                        : '<p style="color: #94a3b8; font-size: 13px;">No hay tallas agregadas</p>'
                    }
                </div>
            </div>

            <div style="display: flex; gap: 8px;">
                <button class="btn btn-secondary" id="btnCancelarEdicionTallasManual" style="flex: 1;">
                    Cancelar
                </button>
                <button class="btn btn-primary" id="btnGuardarEdicionTallasManual" style="flex: 1;">
                    Guardar Tallas
                </button>
            </div>
        `;

        // Event listeners
        document.getElementById('btnAgregarTalla').addEventListener('click', () => {
            this.agregarTallaAManualPrenda();
        });

        document.querySelectorAll('.btn-remove-talla').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const idx = parseInt(e.currentTarget.dataset.tallaIdx);
                this.removeTallaFromManualPrenda(idx);
            });
        });

        document.getElementById('btnCancelarEdicionTallasManual').addEventListener('click', () => {
            this.currentManualPrendaDraft = null;
            this.currentManualPrendaBeingEdited = null;
            document.getElementById('inputDescripcionPrenda').value = '';
            const selectGeneroPrendaManual = document.getElementById('selectGeneroPrendaManual');
            if (selectGeneroPrendaManual) selectGeneroPrendaManual.value = '';
            this.hideTallasSection();
        });

        document.getElementById('btnGuardarEdicionTallasManual').addEventListener('click', () => {
            this.guardarTallasManualPrenda();
        });
    }

    /**
     * Agrega una talla a la prenda manual
     */
    agregarTallaAManualPrenda() {
        const tallaNombre = document.getElementById('selectTallaManual')?.value?.trim() || '';
        const tallaCantidad = parseInt(document.getElementById('inputTallaCantidad').value) || 0;

        if (!tallaNombre) {
            window.dispatchEvent(new CustomEvent('showToast', { 
                detail: { title: 'Talla Requerida', message: 'Por favor selecciona una talla', type: 'error' }
            }));
            return;
        }

        if (tallaCantidad <= 0) {
            window.dispatchEvent(new CustomEvent('showToast', { 
                detail: { title: 'Cantidad Requerida', message: 'La cantidad debe ser mayor a 0', type: 'error' }
            }));
            return;
        }

        const prenda = this.currentManualPrendaDraft
            || this.manualPrendaHandler.getManualPrenda(this.currentManualPrendaBeingEdited);
        if (!prenda) return;

        const genero = prenda.genero || 'UNISEX';
        const tallaExistente = prenda.selectedTallas.findIndex(t => 
            String(t.talla || '').trim().toUpperCase() === tallaNombre.toUpperCase() &&
            String(t.genero || genero).trim().toUpperCase() === genero
        );

        if (tallaExistente >= 0) {
            prenda.selectedTallas[tallaExistente].cantidad_enviada += tallaCantidad;
        } else {
            prenda.selectedTallas.push({
                talla: tallaNombre,
                cantidad_enviada: tallaCantidad,
                genero: genero
            });
        }

        // Limpiar inputs
        document.getElementById('inputTallaCantidad').value = '';

        // Re-renderizar
        this.renderTallasForManualPrenda(prenda);
    }

    /**
     * Elimina una talla de la prenda manual
     */
    removeTallaFromManualPrenda(idx) {
        const prenda = this.currentManualPrendaDraft
            || this.manualPrendaHandler.getManualPrenda(this.currentManualPrendaBeingEdited);
        if (!prenda) return;

        prenda.selectedTallas.splice(idx, 1);
        this.renderTallasForManualPrenda(prenda);
    }

    /**
     * Guarda las tallas de la prenda manual
     */
    guardarTallasManualPrenda() {
        const prenda = this.currentManualPrendaDraft
            || this.manualPrendaHandler.getManualPrenda(this.currentManualPrendaBeingEdited);
        if (!prenda) return;

        if (prenda.selectedTallas.length === 0) {
            window.dispatchEvent(new CustomEvent('showToast', { 
                detail: { title: 'Tallas Requeridas', message: 'Por favor agrega al menos una talla', type: 'error' }
            }));
            return;
        }

        if (this.currentManualPrendaDraft) {
            const tempId = this.manualPrendaHandler.addManualPrenda(prenda.descripcion, prenda.genero);
            this.manualPrendaHandler.setSelectedTallasForManualPrenda(tempId, prenda.selectedTallas);
            this.currentManualPrendaDraft = null;
        } else {
            this.manualPrendaHandler.setSelectedTallasForManualPrenda(this.currentManualPrendaBeingEdited, prenda.selectedTallas);
        }

        this.currentManualPrendaBeingEdited = null;

        // Renderizar prendas manuales
        this.renderManualPrendas();

        // Ocultar sección de tallas
        this.hideTallasSection();

        // Limpiar formulario base de prenda manual
        document.getElementById('inputDescripcionPrenda').value = '';
        const selectGeneroPrendaManual = document.getElementById('selectGeneroPrendaManual');
        if (selectGeneroPrendaManual) selectGeneroPrendaManual.value = '';
    }

    /**
     * Renderiza las prendas manuales agregadas
     */
    renderManualPrendas() {
        const container = document.getElementById('prendasManualContainer');
        if (!container) return;

        const prendas = this.manualPrendaHandler.getAllManualPrendas();

        if (prendas.length === 0) {
            container.innerHTML = '<p style="color: #94a3b8; text-align: center; margin: 0;">No hay prendas manuales agregadas</p>';
            return;
        }

        container.innerHTML = prendas.map(prenda => {
            const selectedTallas = this.manualPrendaHandler.getSelectedTallasForManualPrenda(prenda.id);
            const generoLabel = this.getLabelGenero(prenda.genero);
            const generosUsados = [...new Set(selectedTallas
                .map(t => String(t.genero || prenda.genero || '').trim().toUpperCase())
                .filter(Boolean))];
            const generoResumen = generosUsados.length > 1
                ? `Géneros: ${generosUsados.map(g => this.getLabelGenero(g)).join(', ')}`
                : `Género: ${generoLabel}`;
            const tallasHtml = selectedTallas.length > 0
                ? selectedTallas.map(t => `<span class="talla-badge" style="display: inline-block; background: #fef3c7; color: #92400e; padding: 6px 12px; border-radius: 6px; font-size: 12px; margin-right: 4px; margin-bottom: 4px; font-weight: 500;">${this.getLabelGenero(t.genero || prenda.genero)} - ${t.talla}: ${t.cantidad_enviada}</span>`).join('')
                : '<span style="color: #94a3b8; font-size: 13px;">Sin tallas seleccionadas</span>';

            return `
                <div style="
                    background: #fffbeb;
                    border: 2px solid #fcd34d;
                    border-radius: 8px;
                    padding: 12px;
                    margin-bottom: 12px;
                    transition: all 0.2s;
                " class="prenda-manual-card" data-prenda-id="${prenda.id}">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                        <div>
                            <div style="font-weight: 600; color: #1e293b; font-size: 14px;">
                                Prenda Manual
                            </div>
                            <div style="font-size: 12px; color: #92400e; margin-top: 2px; font-weight: 500;">
                                MANUAL
                            </div>
                        </div>
                        <button class="btn-remove-prenda-manual" data-prenda-id="${prenda.id}" style="
                            background: none;
                            border: none;
                            color: #ef4444;
                            cursor: pointer;
                            font-size: 20px;
                            padding: 0;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            width: 24px;
                            height: 24px;
                        ">
                            <span class="material-symbols-rounded" style="font-size: 20px;">close</span>
                        </button>
                    </div>
                    <div style="font-size: 12px; color: #64748b; margin-bottom: 8px; padding-bottom: 8px; border-bottom: 1px solid #fcd34d;">
                        ${prenda.descripcion}
                    </div>
                    <div style="font-size: 12px; color: #92400e; margin-bottom: 8px; font-weight: 600;">
                        ${generoResumen}
                    </div>
                    <div style="display: flex; flex-wrap: wrap; gap: 4px; margin-bottom: 8px;">
                        ${tallasHtml}
                    </div>
                    <button class="btn-edit-prenda-manual" data-prenda-id="${prenda.id}" style="
                        background: white;
                        border: 1px solid #fcd34d;
                        color: #92400e;
                        padding: 6px 12px;
                        border-radius: 4px;
                        font-size: 12px;
                        cursor: pointer;
                        font-weight: 500;
                        display: flex;
                        align-items: center;
                        gap: 4px;
                        transition: all 0.2s;
                    ">
                        <span class="material-symbols-rounded" style="font-size: 16px;">edit</span>
                        Editar Tallas
                    </button>
                </div>
            `;
        }).join('');

        // Event listeners
        document.querySelectorAll('.btn-remove-prenda-manual').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const prendaId = parseInt(e.currentTarget.dataset.prendaId);
                this.removeManualPrenda(prendaId);
            });
        });

        document.querySelectorAll('.btn-edit-prenda-manual').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const prendaId = parseInt(e.currentTarget.dataset.prendaId);
                this.editManualPrendaTallas(prendaId);
            });
        });
    }

    /**
     * Elimina una prenda manual
     */
    removeManualPrenda(tempId) {
        this.manualPrendaHandler.removeManualPrenda(tempId);
        this.renderManualPrendas();
    }
}

export { RegistrationHandler };
