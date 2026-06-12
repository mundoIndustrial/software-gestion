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
        this.currentManualPrendaWizardStep = 1;
        this.currentManualPrendaWizardMode = 'LETRAS';
        this.currentManualPrendaResumen = null;

        // Catálogo alineado con Asesoría
        this.catalogoManualTallas = {
            letras: ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL', 'XXXXL'],
            numerosDama: ['6', '8', '10', '12', '14', '16', '18', '20', '22', '24', '26'],
            numerosCaballero: ['28', '30', '32', '34', '36', '38', '40', '42', '44', '46', '48', '50']
        };
    }

    normalizeGenero(genero) {
        return String(genero || '').trim().toUpperCase();
    }

    normalizeModoTallas(modo) {
        const modoNormalizado = String(modo || '').trim().toUpperCase();
        return modoNormalizado === 'NUMEROS' ? 'NUMEROS' : 'LETRAS';
    }

    getCatalogoTallasPorGenero(genero, modo = 'LETRAS') {
        const generoNormalizado = String(genero || '').trim().toUpperCase();
        const modoNormalizado = this.normalizeModoTallas(modo);

        if (modoNormalizado === 'NUMEROS') {
            if (generoNormalizado === 'DAMA') {
                return [...this.catalogoManualTallas.numerosDama];
            }

            if (generoNormalizado === 'CABALLERO') {
                return [...this.catalogoManualTallas.numerosCaballero];
            }

            return [];
        }

        return [...this.catalogoManualTallas.letras];
    }

    getLabelGenero(genero) {
        const generoNormalizado = String(genero || '').trim().toUpperCase();
        const labels = {
            DAMA: 'Dama',
            CABALLERO: 'Caballero',
            UNISEX: 'Unisex',
            MIXTO: 'Mixto'
        };

        return labels[generoNormalizado] || 'Sin género';
    }

    getLabelModoTallas(modo) {
        return this.normalizeModoTallas(modo) === 'NUMEROS' ? 'Números' : 'Letras';
    }

    getManualPrendaEnEdicion() {
        if (this.currentManualPrendaDraft) {
            return this.currentManualPrendaDraft;
        }

        if (this.currentManualPrendaBeingEdited !== null) {
            return this.manualPrendaHandler.getManualPrenda(this.currentManualPrendaBeingEdited);
        }

        return null;
    }

    openManualPrendaWizard(prenda, step = 1, isEditing = false) {
        this.currentManualPrendaDraft = {
            descripcion: prenda.descripcion || '',
            genero: this.normalizeGenero(prenda.genero || ''),
            modoTallas: this.normalizeModoTallas(prenda.modoTallas || 'LETRAS'),
            selectedSizeNames: Array.isArray(prenda.selectedSizeNames)
                ? [...prenda.selectedSizeNames]
                : Array.isArray(prenda.selectedTallas)
                    ? prenda.selectedTallas.map(t => String(t.talla || '').trim().toUpperCase()).filter(Boolean)
                    : [],
            selectedTallas: Array.isArray(prenda.selectedTallas)
                ? prenda.selectedTallas.map(t => ({ ...t }))
                : []
        };

        this.currentManualPrendaBeingEdited = isEditing ? (prenda.id ?? null) : null;
        this.currentManualPrendaWizardStep = step;

        const modal = document.getElementById('modalSelectorTallasManual');
        if (modal) {
            modal.classList.add('active');
        }

        this.renderTallasForManualPrenda(this.currentManualPrendaDraft);
    }

    closeManualPrendaWizard({ clearDraft = true, restoreForm = true } = {}) {
        const modal = document.getElementById('modalSelectorTallasManual');
        if (modal) {
            modal.classList.remove('active');
        }

        if (restoreForm) {
            const form = document.getElementById('formAgregarPrendaManual');
            if (form) {
                form.style.display = 'block';
            }
        }

        if (clearDraft) {
            this.currentManualPrendaDraft = null;
            this.currentManualPrendaBeingEdited = null;
            this.currentManualPrendaWizardStep = 1;
            this.currentManualPrendaWizardMode = 'LETRAS';
        }
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
        const containerSection = container.closest('div') ? container.parentElement : container;

        if (recibos.length === 0) {
            // Ocultar todo el contenedor si no hay recibos
            if (containerSection && containerSection.style) {
                containerSection.style.display = 'none';
            }
            container.innerHTML = '';
            return;
        }

        // Mostrar el contenedor cuando hay recibos
        if (containerSection && containerSection.style) {
            containerSection.style.display = 'block';
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
        const prendasManuales = this.getPrendasManualesParaRegistro();

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
            const selectedTallas = Array.isArray(prenda.selectedTallas) ? prenda.selectedTallas : [];
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
                temp_id: p.temp_id,
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
                    const tallas = Array.isArray(p.selectedTallas) ? p.selectedTallas : [];
                    return tallas.map(t => ({
                        ...t,
                        prenda_agregada_id: p.temp_id
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
        
        const inputNovedad = document.getElementById('inputNovedad');
        if (inputNovedad) inputNovedad.value = '';

        const inputDescripcion = document.getElementById('inputDescripcionPrenda');
        if (inputDescripcion) inputDescripcion.value = '';
        
        const formAgregarPrenda = document.getElementById('formAgregarPrendaManual');
        if (formAgregarPrenda) formAgregarPrenda.style.display = 'none';
        this.closeManualPrendaWizard({ clearDraft: true, restoreForm: false });
        
        this.multiReceiptHandler.clear();
        this.manualPrendaHandler.clear();
        this.currentReciboBeingEdited = null;
        this.currentManualPrendaBeingEdited = null;
        this.currentManualPrendaDraft = null;
        this.currentManualPrendaResumen = null;
        this.hideTallasSection();
        
        const container = document.getElementById('recibosSeleccionadosContainer');
        if (container) {
            const containerSection = container.parentElement;
            if (containerSection && containerSection.style) {
                containerSection.style.display = 'none';
            }
            container.innerHTML = '';
        }

        const containerManual = document.getElementById('prendasManualContainer');
        if (containerManual) {
            const containerManualSection = containerManual.parentElement;
            if (containerManualSection && containerManualSection.style) {
                containerManualSection.style.display = 'none';
            }
            containerManual.innerHTML = '';
        }

        this.renderManualPrendaResumen();
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
        }
    }

    /**
     * Agrega una prenda manual
     */
    agregarPrendaManual() {
        const descripcionInput = document.getElementById('inputDescripcionPrenda');
        const descripcionFormulario = descripcionInput ? descripcionInput.value.trim() : '';
        const descripcionBorrador = this.currentManualPrendaResumen?.descripcion || '';
        const descripcion = descripcionFormulario || descripcionBorrador;

        if (!descripcion) {
            window.dispatchEvent(new CustomEvent('showToast', { 
                detail: { title: 'Descripción Requerida', message: 'Por favor ingresa una descripción para la prenda', type: 'error' }
            }));
            return;
        }

        if (descripcionInput && !descripcionFormulario) {
            descripcionInput.value = descripcion;
        }

        this.openManualPrendaWizard({
            descripcion,
            genero: '',
            modoTallas: 'LETRAS',
            selectedSizeNames: [],
            selectedTallas: []
        }, 1, false);
    }

    /**
     * Edita las tallas de una prenda manual
     */
    editManualPrendaTallas(tempId) {
        const prenda = this.manualPrendaHandler.getManualPrenda(tempId);
        if (!prenda) return;

        this.openManualPrendaWizard({
            id: prenda.id,
            descripcion: prenda.descripcion,
            genero: prenda.genero || 'UNISEX',
            modoTallas: prenda.modoTallas || 'LETRAS',
            selectedSizeNames: (prenda.selectedTallas || []).map(t => String(t.talla || '').trim().toUpperCase()).filter(Boolean),
            selectedTallas: Array.isArray(prenda.selectedTallas) ? prenda.selectedTallas.map(t => ({ ...t })) : []
        }, 1, true);
    }

    /**
     * Renderiza las tallas para una prenda manual
     */
    renderTallasForManualPrenda(prenda) {
        const container = document.getElementById('manualTallasWizardBody');
        if (!container || !prenda) return;

        const estado = this.getManualPrendaEnEdicion() || prenda;
        const genero = this.normalizeGenero(estado.genero || '');
        const modoTallas = this.normalizeModoTallas(estado.modoTallas || 'LETRAS');
        const tallasDisponibles = this.getCatalogoTallasPorGenero(genero, modoTallas);
        const selectedSizeNames = Array.isArray(estado.selectedSizeNames)
            ? [...estado.selectedSizeNames]
            : [];
        const selectedTallas = Array.isArray(estado.selectedTallas)
            ? estado.selectedTallas.map(t => ({ ...t }))
            : [];

        // Mostrar interfaz unificada: género + tallas + cantidades
        const generoOptions = [
            { value: 'DAMA', label: 'Dama' },
            { value: 'CABALLERO', label: 'Caballero' },
            { value: 'UNISEX', label: 'Unisex' }
        ];

        const etiquetasSeleccionadas = selectedTallas.length > 0
            ? selectedTallas.map(item => {
                const talla = String(item.talla || '').trim().toUpperCase();
                const cantidad = parseInt(item.cantidad_enviada) || 0;
                return `
                    <div style="
                        display: flex;
                        gap: 8px;
                        align-items: center;
                        border: 1px solid #cbd5e1;
                        border-radius: 6px;
                        padding: 8px;
                        background: white;
                    " class="manual-talla-row" data-talla="${talla}">
                        <div style="font-weight: 700; color: #1e293b; font-size: 14px; min-width: 32px; text-align: center;">
                            ${talla}
                        </div>
                        <input
                            type="number"
                            min="0"
                            class="form-input manual-talla-cantidad-input"
                            data-talla="${talla}"
                            value="${cantidad > 0 ? cantidad : ''}"
                            placeholder="0"
                            style="flex: 1; text-align: center; font-size: 13px; padding: 6px 8px; height: 32px; border: 1px solid #e2e8f0; border-radius: 4px; background: white;"
                        >
                        <button type="button" class="btn-remove-manual-size" data-talla="${talla}" style="
                            background: none;
                            border: none;
                            color: #94a3b8;
                            cursor: pointer;
                            padding: 0;
                            font-size: 18px;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            width: 20px;
                            height: 20px;
                            transition: all 0.2s ease;
                        " onmouseover="this.style.color='#ef4444'" onmouseout="this.style.color='#94a3b8'">
                            <span class="material-symbols-rounded">close</span>
                        </button>
                    </div>
                `;
            }).join('')
            : '<p style="color:#94a3b8; margin:0; text-align: center;">Selecciona tallas del listado abajo</p>';

        container.innerHTML = `
            <div style="margin-bottom: 16px; padding-bottom: 16px; border-bottom: 1px solid #e2e8f0;">
                <div style="font-weight: 700; color: #1e293b; margin-bottom: 8px;">
                    ${estado.descripcion}
                </div>
                <div style="font-size: 13px; color: #64748b; margin-bottom: 12px;">
                    Selecciona género, tallas y cantidades
                </div>
            </div>

            <!-- Género -->
            <div class="form-group" style="margin-bottom: 16px;">
                <label class="form-label">Género</label>
                <select id="manualTallasGeneroSelect" class="form-select">
                    <option value="">Selecciona un género</option>
                    ${generoOptions.map(opt => `<option value="${opt.value}" ${genero === opt.value ? 'selected' : ''}>${opt.label}</option>`).join('')}
                </select>
            </div>

            <!-- Tipo de Tallas (Letras/Números) - Solo visible si hay género -->
            <div id="tipoTallasSection" style="display: ${genero ? 'block' : 'none'}; margin-bottom: 16px;">
                <label class="form-label">Tipo de Tallas</label>
                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                    <button type="button" class="manual-talla-modo-btn ${modoTallas === 'LETRAS' ? 'active' : ''}" data-modo="LETRAS" style="
                        flex: 1;
                        min-width: 100px;
                        padding: 10px 14px;
                        border-radius: 8px;
                        border: 1px solid ${modoTallas === 'LETRAS' ? '#2450ef' : '#cbd5e1'};
                        background: ${modoTallas === 'LETRAS' ? '#2450ef' : 'white'};
                        color: ${modoTallas === 'LETRAS' ? 'white' : '#1e293b'};
                        font-weight: 600;
                        cursor: pointer;
                        font-size: 13px;
                    ">
                        Letras
                    </button>
                    <button type="button" class="manual-talla-modo-btn ${modoTallas === 'NUMEROS' ? 'active' : ''}" data-modo="NUMEROS" style="
                        flex: 1;
                        min-width: 100px;
                        padding: 10px 14px;
                        border-radius: 8px;
                        border: 1px solid ${(genero === 'DAMA' || genero === 'CABALLERO') ? (modoTallas === 'NUMEROS' ? '#2450ef' : '#cbd5e1') : '#e5e7eb'};
                        background: ${modoTallas === 'NUMEROS' ? '#2450ef' : '#f8fafc'};
                        color: ${modoTallas === 'NUMEROS' ? 'white' : '#64748b'};
                        font-weight: 600;
                        cursor: ${(genero === 'DAMA' || genero === 'CABALLERO') ? 'pointer' : 'not-allowed'};
                        font-size: 13px;
                        opacity: ${(genero === 'DAMA' || genero === 'CABALLERO') ? '1' : '0.5'};
                    " ${(genero === 'DAMA' || genero === 'CABALLERO') ? '' : 'disabled'}>
                        Números
                    </button>
                </div>
            </div>

            <!-- Tallas disponibles para seleccionar - Solo visible si hay género y tipo de talla -->
            <div id="tallasDisponiblesSection" style="display: ${genero && modoTallas ? 'block' : 'none'}; margin-bottom: 16px;">
                <label class="form-label">Tallas Disponibles</label>
                <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                    ${tallasDisponibles.length > 0
                        ? tallasDisponibles.map(talla => `
                            <button type="button" class="manual-talla-chip ${selectedSizeNames.includes(talla) ? 'active' : ''}" data-talla="${talla}" style="
                                min-width: 50px;
                                padding: 8px 12px;
                                border-radius: 6px;
                                border: 1px solid ${selectedSizeNames.includes(talla) ? '#2450ef' : '#cbd5e1'};
                                background: ${selectedSizeNames.includes(talla) ? '#2450ef' : 'white'};
                                color: ${selectedSizeNames.includes(talla) ? 'white' : '#1e293b'};
                                font-weight: 600;
                                cursor: pointer;
                                font-size: 12px;
                                transition: all 0.2s ease;
                            ">
                                ${talla}
                            </button>
                        `).join('')
                        : '<p style="color:#94a3b8; margin:0;">Selecciona un tipo de talla primero</p>'
                    }
                </div>
            </div>

            <!-- Cantidades de tallas seleccionadas - Solo visible si hay tallas seleccionadas -->
            <div id="cantidadesSection" style="display: ${selectedSizeNames.length > 0 ? 'block' : 'none'}; margin-bottom: 16px;">
                <label class="form-label">Cantidades</label>
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px; margin-bottom: 16px;">
                    ${etiquetasSeleccionadas}
                </div>
            </div>

            <!-- Botones de acción -->
            <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: 12px;">
                <button type="button" class="btn btn-secondary" id="btnCancelarManualTallasWizard" style="flex: 1; min-width: 160px;">
                    Cancelar
                </button>
                <button type="button" class="btn btn-primary" id="btnGuardarEdicionTallasManual" style="flex: 1; min-width: 160px; display: ${selectedSizeNames.length > 0 ? 'flex' : 'none'};">
                    Guardar Tallas
                </button>
            </div>
        `;

        // Event listeners para género
        const generoSelect = document.getElementById('manualTallasGeneroSelect');
        if (generoSelect) {
            generoSelect.addEventListener('change', (e) => {
                this.currentManualPrendaDraft.genero = this.normalizeGenero(e.target.value || '');
                this.currentManualPrendaDraft.modoTallas = 'LETRAS';
                this.currentManualPrendaDraft.selectedSizeNames = [];
                this.currentManualPrendaDraft.selectedTallas = [];
                this.renderTallasForManualPrenda(this.currentManualPrendaDraft);
            });
        }

        // Event listeners para tipo de talla (Letras/Números)
        document.querySelectorAll('.manual-talla-modo-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                if (btn.disabled) return;
                const nuevoModo = this.normalizeModoTallas(btn.dataset.modo);
                this.currentManualPrendaDraft.modoTallas = nuevoModo;
                this.currentManualPrendaDraft.selectedSizeNames = [];
                this.currentManualPrendaDraft.selectedTallas = [];
                this.renderTallasForManualPrenda(this.currentManualPrendaDraft);
            });
        });

        // Event listeners para tallas disponibles
        document.querySelectorAll('.manual-talla-chip').forEach(btn => {
            btn.addEventListener('click', () => {
                const talla = String(btn.dataset.talla || '').trim().toUpperCase();
                if (!talla) return;

                const actual = this.currentManualPrendaDraft.selectedSizeNames || [];
                const index = actual.indexOf(talla);
                if (index >= 0) {
                    actual.splice(index, 1);
                    // Remover de selectedTallas también
                    this.currentManualPrendaDraft.selectedTallas = (this.currentManualPrendaDraft.selectedTallas || [])
                        .filter(item => String(item.talla || '').trim().toUpperCase() !== talla);
                } else {
                    actual.push(talla);
                    // Agregar a selectedTallas con cantidad 0
                    this.currentManualPrendaDraft.selectedTallas.push({
                        talla,
                        cantidad_enviada: 0,
                        genero: this.currentManualPrendaDraft.genero || 'UNISEX'
                    });
                }
                this.currentManualPrendaDraft.selectedSizeNames = actual;
                this.renderTallasForManualPrenda(this.currentManualPrendaDraft);
            });
        });

        // Event listeners para remover tallas
        document.querySelectorAll('.btn-remove-manual-size').forEach(btn => {
            btn.addEventListener('click', () => {
                const talla = String(btn.dataset.talla || '').trim().toUpperCase();
                const prendaActual = this.getManualPrendaEnEdicion();
                if (!prendaActual) return;

                prendaActual.selectedSizeNames = (prendaActual.selectedSizeNames || []).filter(item => item !== talla);
                prendaActual.selectedTallas = (prendaActual.selectedTallas || []).filter(item => String(item.talla || '').trim().toUpperCase() !== talla);

                this.renderTallasForManualPrenda(prendaActual);
            });
        });

        // Event listeners para inputs de cantidad
        document.querySelectorAll('.manual-talla-cantidad-input').forEach(input => {
            input.addEventListener('change', () => {
                this.syncManualQuantitiesFromWizard();
            });
        });

        // Event listeners para botones
        document.getElementById('btnCancelarManualTallasWizard')?.addEventListener('click', () => {
            this.closeManualPrendaWizard({ clearDraft: true, restoreForm: true });
        });

        document.getElementById('btnGuardarEdicionTallasManual')?.addEventListener('click', () => {
            this.syncManualQuantitiesFromWizard();
            this.guardarTallasManualPrenda();
        });
    }

    /**
     * Sincroniza las cantidades del wizard con el borrador actual
     */
    syncManualQuantitiesFromWizard() {
        const prenda = this.getManualPrendaEnEdicion();
        if (!prenda) return;

        const mapaCantidades = new Map();
        document.querySelectorAll('.manual-talla-cantidad-input').forEach(input => {
            const talla = String(input.dataset.talla || '').trim().toUpperCase();
            const cantidad = parseInt(input.value) || 0;
            if (talla) {
                mapaCantidades.set(talla, cantidad);
            }
        });

        prenda.selectedTallas = (prenda.selectedSizeNames || []).map(talla => ({
            talla,
            cantidad_enviada: mapaCantidades.get(talla) || 0,
            genero: prenda.genero || 'UNISEX'
        }));
    }

    /**
     * Guarda las tallas de la prenda manual
     */
    guardarTallasManualPrenda() {
        const prenda = this.getManualPrendaEnEdicion();
        if (!prenda) return;

        const selectedTallas = Array.isArray(prenda.selectedTallas)
            ? prenda.selectedTallas.filter(t => (parseInt(t.cantidad_enviada) || 0) > 0)
            : [];

        if (selectedTallas.length === 0) {
            window.dispatchEvent(new CustomEvent('showToast', { 
                detail: { title: 'Tallas Requeridas', message: 'Por favor agrega al menos una talla con cantidad mayor a 0', type: 'error' }
            }));
            return;
        }

        prenda.selectedTallas = selectedTallas;

        const tallasActuales = Array.isArray(this.currentManualPrendaResumen?.selectedTallas)
            ? this.currentManualPrendaResumen.selectedTallas
            : [];
        const generoResumen = this.obtenerGeneroResumenManualPrenda([...tallasActuales, ...selectedTallas]);
        const modoTallasResumen = this.normalizeModoTallas(prenda.modoTallas || this.currentManualPrendaResumen?.modoTallas || 'LETRAS');
        const descripcionResumen = (prenda.descripcion || this.currentManualPrendaResumen?.descripcion || '').trim();

        this.currentManualPrendaResumen = {
            temp_id: this.currentManualPrendaResumen?.temp_id ?? -999999,
            descripcion: descripcionResumen,
            genero: generoResumen,
            modoTallas: modoTallasResumen,
            selectedTallas: this.fusionarTallasManualPrenda(tallasActuales, selectedTallas)
        };

        this.currentManualPrendaDraft = null;
        this.currentManualPrendaBeingEdited = null;
        this.currentManualPrendaWizardStep = 1;
        this.currentManualPrendaWizardMode = 'LETRAS';

        this.closeManualPrendaWizard({ clearDraft: true, restoreForm: true });
        this.renderManualPrendaResumen();

        const inputDescripcion = document.getElementById('inputDescripcionPrenda');
        if (inputDescripcion) inputDescripcion.value = descripcionResumen;
    }

    hasManualPrendaResumen() {
        return Array.isArray(this.currentManualPrendaResumen?.selectedTallas)
            && this.currentManualPrendaResumen.selectedTallas.length > 0;
    }

    fusionarTallasManualPrenda(tallasBase = [], nuevasTallas = []) {
        const mapa = new Map();

        [...(Array.isArray(tallasBase) ? tallasBase : []), ...(Array.isArray(nuevasTallas) ? nuevasTallas : [])].forEach(item => {
            const talla = String(item?.talla || '').trim().toUpperCase();
            if (!talla) return;

            const genero = this.normalizeGenero(item?.genero || '');
            const key = `${genero}__${talla}`;

            mapa.set(key, {
                talla,
                cantidad_enviada: parseInt(item?.cantidad_enviada) || 0,
                genero: genero || 'UNISEX'
            });
        });

        return [...mapa.values()].filter(item => (parseInt(item.cantidad_enviada) || 0) > 0);
    }

    obtenerGeneroResumenManualPrenda(tallas = []) {
        const generos = [...new Set((Array.isArray(tallas) ? tallas : [])
            .map(t => this.normalizeGenero(t?.genero || ''))
            .filter(Boolean))];

        if (generos.length === 0) {
            return '';
        }

        if (generos.length === 1) {
            return generos[0];
        }

        return 'MIXTO';
    }

    renderManualPrendaResumen() {
        const container = document.getElementById('prendaManualResumenContainer');
        const botonAgregar = document.getElementById('btnAgregarTallasManual');
        const resumen = this.currentManualPrendaResumen;

        if (botonAgregar) {
            botonAgregar.innerHTML = `
                <span class="material-symbols-rounded">check_circle</span>
                ${this.hasManualPrendaResumen() ? 'Agregar Más Tallas' : 'Agregar Tallas'}
            `;
        }

        if (!container) return;

        if (!this.hasManualPrendaResumen()) {
            container.innerHTML = '<p style="color: #94a3b8; font-size: 13px; margin: 0; text-align: center;">Aún no has agregado tallas manuales.</p>';
            return;
        }

        const selectedTallas = Array.isArray(resumen?.selectedTallas) ? resumen.selectedTallas : [];
        const generosUsados = [...new Set(selectedTallas
            .map(t => this.normalizeGenero(t?.genero || ''))
            .filter(Boolean))];
        const generoTexto = generosUsados.length > 1
            ? `Géneros: ${generosUsados.map(g => this.getLabelGenero(g)).join(', ')}`
            : `Género: ${this.getLabelGenero(generosUsados[0] || resumen?.genero)}`;

        const tallasHtml = selectedTallas.length > 0
            ? selectedTallas.map(t => `
                <span class="talla-badge" style="display: inline-block; background: #dbeafe; color: #1d4ed8; padding: 6px 10px; border-radius: 999px; font-size: 12px; margin-right: 4px; margin-bottom: 4px; font-weight: 600;">
                    ${this.getLabelGenero(t.genero || resumen?.genero)} - ${t.talla}: ${t.cantidad_enviada}
                </span>
            `).join('')
            : '<span style="color: #94a3b8; font-size: 13px;">Sin tallas seleccionadas</span>';

        container.innerHTML = `
            <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 12px;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; margin-bottom: 8px;">
                    <div>
                        <div style="font-size: 13px; font-weight: 700; color: #1e293b;">Borrador de prenda manual</div>
                        <div style="font-size: 12px; color: #2563eb; margin-top: 2px; font-weight: 600;">${generoTexto}</div>
                    </div>
                </div>

                <div style="font-size: 13px; color: #334155; margin-bottom: 10px;">
                    ${resumen?.descripcion || 'Sin descripción'}
                </div>

                <div style="display: flex; flex-wrap: wrap; gap: 4px; margin-bottom: 12px;">
                    ${tallasHtml}
                </div>

                <div style="display: flex; gap: 8px;">
                    <button type="button" class="btn btn-success" id="btnGuardarPrenda" style="flex: 1; display: flex; align-items: center; justify-content: center; gap: 6px; padding: 8px 12px; background: #10b981; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 13px;">
                        <span class="material-symbols-rounded" style="font-size: 18px;">save</span>
                        Guardar Prenda
                    </button>
                    <button type="button" class="btn btn-secondary" id="btnLimpiarPrenda" style="flex: 1; display: flex; align-items: center; justify-content: center; gap: 6px; padding: 8px 12px; background: #e2e8f0; color: #64748b; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 13px;">
                        <span class="material-symbols-rounded" style="font-size: 18px;">clear</span>
                        Limpiar
                    </button>
                </div>
            </div>
        `;

        // Agregar event listeners a los botones
        const btnGuardar = document.getElementById('btnGuardarPrenda');
        if (btnGuardar) {
            btnGuardar.addEventListener('click', () => {
                this.guardarPrendaManual();
            });
        }

        const btnLimpiar = document.getElementById('btnLimpiarPrenda');
        if (btnLimpiar) {
            btnLimpiar.addEventListener('click', () => {
                this.limpiarPrendaManual();
            });
        }
    }

    getPrendasManualesParaRegistro() {
        const prendas = [];

        if (this.hasManualPrendaResumen()) {
            prendas.push({
                temp_id: this.currentManualPrendaResumen.temp_id ?? -999999,
                descripcion: this.currentManualPrendaResumen.descripcion || 'Sin descripción',
                genero: this.currentManualPrendaResumen.genero || this.obtenerGeneroResumenManualPrenda(this.currentManualPrendaResumen.selectedTallas),
                modoTallas: this.currentManualPrendaResumen.modoTallas || 'LETRAS',
                selectedTallas: (this.currentManualPrendaResumen.selectedTallas || []).map(t => ({ ...t }))
            });
        }

        this.manualPrendaHandler.getAllManualPrendas().forEach(prenda => {
            const selectedTallas = this.manualPrendaHandler.getSelectedTallasForManualPrenda(prenda.id);
            prendas.push({
                temp_id: prenda.id,
                descripcion: prenda.descripcion,
                genero: prenda.genero || null,
                modoTallas: prenda.modoTallas || 'LETRAS',
                selectedTallas: Array.isArray(selectedTallas) ? selectedTallas.map(t => ({ ...t })) : []
            });
        });

        return prendas;
    }

    /**
     * Renderiza las prendas manuales agregadas
    /**
     * Renderiza las prendas manuales agregadas
     */
    renderManualPrendas() {
        const container = document.getElementById('prendasManualContainer');
        if (!container) return;

        const prendas = this.manualPrendaHandler.getAllManualPrendas();
        const containerSection = container.parentElement;

        if (prendas.length === 0) {
            // Ocultar el contenedor si no hay prendas manuales
            if (containerSection && containerSection.style) {
                containerSection.style.display = 'none';
            }
            container.innerHTML = '';
            return;
        }

        // Mostrar el contenedor cuando hay prendas manuales
        if (containerSection && containerSection.style) {
            containerSection.style.display = 'block';
        }

        container.innerHTML = prendas.map(prenda => {
            const selectedTallas = this.manualPrendaHandler.getSelectedTallasForManualPrenda(prenda.id);
            const generoLabel = this.getLabelGenero(prenda.genero);
            const modoLabel = prenda.modoTallas ? ` · ${this.getLabelModoTallas(prenda.modoTallas)}` : '';
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
                        ${generoResumen}${modoLabel}
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

    /**
     * Guarda la prenda manual actual en la lista de prendas manuales
     */
    guardarPrendaManual() {
        if (!this.hasManualPrendaResumen()) {
            alert('Por favor, agrega tallas antes de guardar la prenda');
            return;
        }

        const resumen = this.currentManualPrendaResumen;
        const tempId = this.manualPrendaHandler.addManualPrenda(
            resumen.descripcion,
            resumen.genero
        );

        // Establecer las tallas seleccionadas
        this.manualPrendaHandler.setSelectedTallasForManualPrenda(tempId, resumen.selectedTallas);

        // Limpiar el resumen actual
        this.currentManualPrendaResumen = null;

        // Actualizar la UI
        this.renderManualPrendas();
        this.renderManualPrendaResumen();

        // Cerrar el formulario
        const form = document.getElementById('formAgregarPrendaManual');
        if (form) {
            form.style.display = 'none';
        }

        // Limpiar el input de descripción
        const inputDescripcion = document.getElementById('inputDescripcionPrenda');
        if (inputDescripcion) {
            inputDescripcion.value = '';
        }
    }

    /**
     * Limpia el borrador de prenda manual
     */
    limpiarPrendaManual() {
        this.currentManualPrendaResumen = null;
        this.renderManualPrendaResumen();

        // Limpiar el input de descripción
        const inputDescripcion = document.getElementById('inputDescripcionPrenda');
        if (inputDescripcion) {
            inputDescripcion.value = '';
        }
    }
}

export { RegistrationHandler };






