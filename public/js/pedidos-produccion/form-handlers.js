/**
 * 🎮 FORM HANDLERS
 * 
 * Manejadores de eventos para el formulario de pedidos.
 * Coordina: FormManager → Validator → UI → API
 * 
 * Uso:
 * handlers = new PedidoFormHandlers(formManager, validator, uiComponents);
 * handlers.init(containerId);
 * 
 * @author Senior Frontend Developer
 * @version 1.0.0
 */

class PedidoFormHandlers {
    constructor(formManager, validator, uiComponents) {
        this.fm = formManager;
        this.validator = validator;
        this.ui = uiComponents;
        this.container = null;
        this.currentModalContext = null;
        this.isSubmitting = false;

        console.log('🎮 PedidoFormHandlers inicializado');
    }

    /**
     * Inicializar handlers en contenedor
     */
    init(containerId) {
        this.container = document.getElementById(containerId);
        if (!this.container) {
            console.error(` No se encontró elemento: ${containerId}`);
            return;
        }

        this.attachEventListeners();
        this.listenToFormChanges();

        console.log(' Event listeners adjuntados');
    }

    /**
     * Adjuntar listeners a documento
     */
    attachEventListeners() {
        document.addEventListener('click', (e) => this.handleClick(e), true);
        document.addEventListener('change', (e) => this.handleChange(e), true);
        document.addEventListener('submit', (e) => this.handleSubmit(e), true);
    }

    /**
     * Escuchar cambios del FormManager
     */
    listenToFormChanges() {
        this.fm.on('state:cleared', () => this.render());
        this.fm.on('prenda:added', () => this.render());
        this.fm.on('prenda:updated', () => this.render());
        this.fm.on('prenda:deleted', () => this.render());
        this.fm.on('variante:added', () => this.render());
        this.fm.on('variante:deleted', () => this.render());
        this.fm.on('foto:added', () => this.render());
        this.fm.on('foto:deleted', () => this.render());
        this.fm.on('proceso:added', () => this.render());
        this.fm.on('proceso:deleted', () => this.render());
    }

    // ==================== CLICK HANDLERS ====================

    /**
     * Manejador central de clicks
     */
    handleClick(e) {
        const action = e.target.dataset.action;
        const prendaId = e.target.dataset.prendaId;
        const varianteId = e.target.dataset.varianteId;
        const procesoId = e.target.dataset.procesoId;
        const fotoId = e.target.dataset.fotoId;

        if (!action) return;

        try {
            // ====== PRENDAS ======
            if (action === 'add-prenda') {
                e.preventDefault();
                this.showAddPrendaModal();
            }

            if (action === 'edit-prenda' && prendaId) {
                e.preventDefault();
                this.showEditPrendaModal(prendaId);
            }

            if (action === 'delete-prenda' && prendaId) {
                e.preventDefault();
                this.deletePrenda(prendaId);
            }

            // ====== VARIANTES ======
            if (action === 'add-variante' && prendaId) {
                e.preventDefault();
                this.showAddVarianteModal(prendaId);
            }

            if (action === 'edit-variante' && prendaId && varianteId) {
                e.preventDefault();
                this.showEditVarianteModal(prendaId, varianteId);
            }

            if (action === 'delete-variante' && prendaId && varianteId) {
                e.preventDefault();
                this.deleteVariante(prendaId, varianteId);
            }

            // ====== FOTOS ======
            if (action === 'delete-foto' && prendaId && fotoId) {
                e.preventDefault();
                const tipo = e.target.dataset.fotoTipo || 'prenda';
                this.deleteFoto(prendaId, fotoId, tipo);
            }

            // ====== PROCESOS ======
            if (action === 'add-proceso' && prendaId) {
                e.preventDefault();
                this.showAddProcesoModal(prendaId);
            }

            if (action === 'edit-proceso' && prendaId && procesoId) {
                e.preventDefault();
                this.showEditProcesoModal(prendaId, procesoId);
            }

            if (action === 'delete-proceso' && prendaId && procesoId) {
                e.preventDefault();
                this.deleteProceso(prendaId, procesoId);
            }

            // ====== PEDIDO ======
            if (action === 'submit-pedido') {
                e.preventDefault();
                this.submitPedido();
            }

            if (action === 'validate-pedido') {
                e.preventDefault();
                this.validatePedido();
            }

            if (action === 'clear-pedido') {
                e.preventDefault();
                if (confirm('⚠️ ¿Está seguro de que desea limpiar todo el formulario?')) {
                    this.fm.clear();
                }
            }

            if (action === 'modal-save') {
                e.preventDefault();
                this.saveModal();
            }

            if (action === 'modal-close') {
                e.preventDefault();
                this.closeModal();
            }
        } catch (error) {
            console.error(` Error en handler ${action}:`, error);
            this.ui.renderToast('error', `Error: ${error.message}`);
        }
    }

    /**
     * Manejador de cambios
     */
    handleChange(e) {
        const action = e.target.dataset.action;

        if (action === 'upload-foto-prenda' || action === 'upload-foto-tela') {
            const prendaId = e.target.dataset.prendaId;
            const tipo = action === 'upload-foto-tela' ? 'tela' : 'prenda';
            this.handleFotoUpload(prendaId, e.target.files, tipo);
        }
    }

    /**
     * Manejador de submit de formularios
     */
    handleSubmit(e) {
        if (e.target.id === 'pedido-form') {
            e.preventDefault();
            this.submitPedido();
        }
    }

    // ==================== PRENDA OPERATIONS ====================

    /**
     * Mostrar modal para agregar prenda
     */
    showAddPrendaModal() {
        const form = `
            <form id="prenda-form">
                <div class="form-group">
                    <label for="nombre_prenda">Nombre de la prenda *</label>
                    <input type="text" class="form-control" id="nombre_prenda" 
                           name="nombre_prenda" placeholder="Ej: Polo clásico" required>
                    <small class="form-text text-muted">Máximo 100 caracteres</small>
                </div>

                <div class="form-group">
                    <label for="descripcion">Descripción</label>
                    <textarea class="form-control" id="descripcion" 
                              name="descripcion" placeholder="Detalles adicionales" rows="3"></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="genero">Género</label>
                        <select class="form-control" id="genero" name="genero">
                            <option value="">Seleccionar...</option>
                            <option value="dama">👩 Dama</option>
                            <option value="caballero">👨 Caballero</option>
                            <option value="unisex">👥 Unisex</option>
                        </select>
                    </div>

                    <div class="form-group col-md-6">
                        <div class="custom-control custom-checkbox pt-4">
                            <input type="checkbox" class="custom-control-input" 
                                   id="de_bodega" name="de_bodega">
                            <label class="custom-control-label" for="de_bodega">
                                🏭 De bodega
                            </label>
                        </div>
                    </div>
                </div>
            </form>
        `;

        this.showModal('Agregar prenda', form, [
            { label: ' Guardar', action: 'modal-save', variant: 'primary' }
        ]);

        this.currentModalContext = { type: 'add-prenda' };
    }

    /**
     * Mostrar modal para editar prenda
     */
    showEditPrendaModal(prendaId) {
        const prenda = this.fm.getPrenda(prendaId);
        if (!prenda) {
            this.ui.renderToast('error', 'Prenda no encontrada');
            return;
        }

        const form = `
            <form id="prenda-form">
                <div class="form-group">
                    <label for="nombre_prenda">Nombre de la prenda *</label>
                    <input type="text" class="form-control" id="nombre_prenda" 
                           name="nombre_prenda" value="${this.ui.escape(prenda.nombre_prenda)}" required>
                </div>

                <div class="form-group">
                    <label for="descripcion">Descripción</label>
                    <textarea class="form-control" id="descripcion" 
                              name="descripcion" rows="3">${this.ui.escape(prenda.descripcion || '')}</textarea>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="genero">Género</label>
                        <select class="form-control" id="genero" name="genero">
                            <option value="">Seleccionar...</option>
                            <option value="dama" ${prenda.genero === 'dama' ? 'selected' : ''}>👩 Dama</option>
                            <option value="caballero" ${prenda.genero === 'caballero' ? 'selected' : ''}>👨 Caballero</option>
                            <option value="unisex" ${prenda.genero === 'unisex' ? 'selected' : ''}>👥 Unisex</option>
                        </select>
                    </div>

                    <div class="form-group col-md-6">
                        <div class="custom-control custom-checkbox pt-4">
                            <input type="checkbox" class="custom-control-input" 
                                   id="de_bodega" name="de_bodega" 
                                   ${prenda.de_bodega ? 'checked' : ''}>
                            <label class="custom-control-label" for="de_bodega">
                                🏭 De bodega
                            </label>
                        </div>
                    </div>
                </div>
            </form>
        `;

        this.showModal('Editar prenda', form, [
            { label: ' Guardar', action: 'modal-save', variant: 'primary' }
        ]);

        this.currentModalContext = { type: 'edit-prenda', prendaId };
    }

    /**
     * Guardar/actualizar prenda
     */
    savePrenda(formData) {
        const validation = this.validator.validarCampo('nombre_prenda', formData.nombre_prenda);
        if (!validation.valid) {
            validation.errors.forEach(err => this.ui.renderToast('warning', err));
            return;
        }

        if (this.currentModalContext.type === 'add-prenda') {
            this.fm.addPrenda({
                nombre_prenda: formData.nombre_prenda,
                descripcion: formData.descripcion,
                genero: formData.genero || null,
                de_bodega: formData.de_bodega === 'on'
            });
            this.ui.renderToast('success', ` Prenda "${formData.nombre_prenda}" agregada`);
        } else {
            this.fm.editPrenda(this.currentModalContext.prendaId, {
                nombre_prenda: formData.nombre_prenda,
                descripcion: formData.descripcion,
                genero: formData.genero || null,
                de_bodega: formData.de_bodega === 'on'
            });
            this.ui.renderToast('success', ' Prenda actualizada');
        }

        this.closeModal();
    }

    /**
     * Eliminar prenda
     */
    deletePrenda(prendaId) {
        const prenda = this.fm.getPrenda(prendaId);
        if (!confirm(` ¿Confirma eliminar "${prenda.nombre_prenda}"? Se perderán todas sus variantes y procesos.`)) {
            return;
        }

        this.fm.deletePrenda(prendaId);
        this.ui.renderToast('success', ' Prenda eliminada');
    }

    // ==================== VARIANTE OPERATIONS ====================

    /**
     * Mostrar modal para agregar variante
     */
    showAddVarianteModal(prendaId) {
        const form = `
            <form id="variante-form">
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="talla">Talla *</label>
                        <input type="text" class="form-control" id="talla" name="talla" placeholder="XS, S, M, L, XL..." required>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="cantidad">Cantidad *</label>
                        <input type="number" class="form-control" id="cantidad" name="cantidad" min="1" max="10000" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="color_id">Color (ID de catálogo)</label>
                        <input type="number" class="form-control" id="color_id" name="color_id" placeholder="Opcional">
                    </div>
                    <div class="form-group col-md-6">
                        <label for="tela_id">Tela (ID de catálogo)</label>
                        <input type="number" class="form-control" id="tela_id" name="tela_id" placeholder="Opcional">
                    </div>
                </div>

                <hr>

                <h6>Especificaciones adicionales</h6>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="tipo_manga_id">Tipo de manga (ID)</label>
                        <input type="number" class="form-control" id="tipo_manga_id" name="tipo_manga_id" placeholder="Opcional">
                    </div>
                    <div class="form-group col-md-6">
                        <label for="manga_obs">Observaciones de manga</label>
                        <input type="text" class="form-control" id="manga_obs" name="manga_obs" placeholder="Opcional">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="tipo_broche_boton_id">Broche/botón (ID)</label>
                        <input type="number" class="form-control" id="tipo_broche_boton_id" name="tipo_broche_boton_id" placeholder="Opcional">
                    </div>
                    <div class="form-group col-md-6">
                        <label for="broche_boton_obs">Observaciones de broche/botón</label>
                        <input type="text" class="form-control" id="broche_boton_obs" name="broche_boton_obs" placeholder="Opcional">
                    </div>
                </div>

                <div class="form-group">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="tiene_bolsillos" name="tiene_bolsillos">
                        <label class="custom-control-label" for="tiene_bolsillos">
                            👖 Tiene bolsillos
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="bolsillos_obs">Observaciones de bolsillos *</label>
                    <textarea class="form-control" id="bolsillos_obs" name="bolsillos_obs" placeholder="Describir detalles de bolsillos..." rows="2"></textarea>
                    <small class="form-text text-muted">Requerido si tiene bolsillos</small>
                </div>
            </form>
        `;

        this.showModal('Agregar variante', form, [
            { label: ' Guardar', action: 'modal-save', variant: 'primary' }
        ]);

        this.currentModalContext = { type: 'add-variante', prendaId };
    }

    /**
     * Mostrar modal para editar variante
     */
    showEditVarianteModal(prendaId, varianteId) {
        const prenda = this.fm.getPrenda(prendaId);
        const variante = prenda.variantes.find(v => v._id === varianteId);

        if (!variante) {
            this.ui.renderToast('error', 'Variante no encontrada');
            return;
        }

        const form = `
            <form id="variante-form">
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="talla">Talla *</label>
                        <input type="text" class="form-control" id="talla" name="talla" value="${this.ui.escape(variante.talla)}" required>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="cantidad">Cantidad *</label>
                        <input type="number" class="form-control" id="cantidad" name="cantidad" value="${variante.cantidad}" min="1" max="10000" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="color_id">Color (ID)</label>
                        <input type="number" class="form-control" id="color_id" name="color_id" value="${variante.color_id || ''}">
                    </div>
                    <div class="form-group col-md-6">
                        <label for="tela_id">Tela (ID)</label>
                        <input type="number" class="form-control" id="tela_id" name="tela_id" value="${variante.tela_id || ''}">
                    </div>
                </div>

                <hr>

                <h6>Especificaciones adicionales</h6>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="tipo_manga_id">Tipo de manga (ID)</label>
                        <input type="number" class="form-control" id="tipo_manga_id" name="tipo_manga_id" value="${variante.tipo_manga_id || ''}">
                    </div>
                    <div class="form-group col-md-6">
                        <label for="manga_obs">Observaciones de manga</label>
                        <input type="text" class="form-control" id="manga_obs" name="manga_obs" value="${this.ui.escape(variante.manga_obs)}">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="tipo_broche_boton_id">Broche/botón (ID)</label>
                        <input type="number" class="form-control" id="tipo_broche_boton_id" name="tipo_broche_boton_id" value="${variante.tipo_broche_boton_id || ''}">
                    </div>
                    <div class="form-group col-md-6">
                        <label for="broche_boton_obs">Observaciones de broche/botón</label>
                        <input type="text" class="form-control" id="broche_boton_obs" name="broche_boton_obs" value="${this.ui.escape(variante.broche_boton_obs)}">
                    </div>
                </div>

                <div class="form-group">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="tiene_bolsillos" name="tiene_bolsillos" ${variante.tiene_bolsillos ? 'checked' : ''}>
                        <label class="custom-control-label" for="tiene_bolsillos">
                            👖 Tiene bolsillos
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="bolsillos_obs">Observaciones de bolsillos</label>
                    <textarea class="form-control" id="bolsillos_obs" name="bolsillos_obs" rows="2">${this.ui.escape(variante.bolsillos_obs)}</textarea>
                </div>
            </form>
        `;

        this.showModal('Editar variante', form, [
            { label: ' Guardar', action: 'modal-save', variant: 'primary' }
        ]);

        this.currentModalContext = { type: 'edit-variante', prendaId, varianteId };
    }

    /**
     * Guardar/actualizar variante
     */
    saveVariante(formData) {
        const validation = this.validator.validarCampo('talla', formData.talla);
        if (!validation.valid) {
            validation.errors.forEach(err => this.ui.renderToast('warning', err));
            return;
        }

        const cantidadValidation = this.validator.validarCampo('cantidad', parseInt(formData.cantidad));
        if (!cantidadValidation.valid) {
            cantidadValidation.errors.forEach(err => this.ui.renderToast('warning', err));
            return;
        }

        const data = {
            talla: formData.talla,
            cantidad: parseInt(formData.cantidad),
            color_id: formData.color_id ? parseInt(formData.color_id) : null,
            tela_id: formData.tela_id ? parseInt(formData.tela_id) : null,
            tipo_manga_id: formData.tipo_manga_id ? parseInt(formData.tipo_manga_id) : null,
            manga_obs: formData.manga_obs,
            tipo_broche_boton_id: formData.tipo_broche_boton_id ? parseInt(formData.tipo_broche_boton_id) : null,
            broche_boton_obs: formData.broche_boton_obs,
            tiene_bolsillos: formData.tiene_bolsillos === 'on',
            bolsillos_obs: formData.bolsillos_obs
        };

        if (this.currentModalContext.type === 'add-variante') {
            this.fm.addVariante(this.currentModalContext.prendaId, data);
            this.ui.renderToast('success', ` Variante talla ${data.talla} agregada`);
        } else {
            this.fm.editVariante(
                this.currentModalContext.prendaId,
                this.currentModalContext.varianteId,
                data
            );
            this.ui.renderToast('success', ' Variante actualizada');
        }

        this.closeModal();
    }

    /**
     * Eliminar variante
     */
    deleteVariante(prendaId, varianteId) {
        if (!confirm('⚠️ ¿Eliminar esta variante?')) return;

        this.fm.deleteVariante(prendaId, varianteId);
        this.ui.renderToast('success', ' Variante eliminada');
    }

    // ==================== FOTO OPERATIONS ====================

    /**
     * Manejar carga de fotos
     */
    handleFotoUpload(prendaId, files, tipo) {
        if (!files || files.length === 0) return;

        for (let file of files) {
            // Validaciones básicas
            if (!file.type.startsWith('image/')) {
                this.ui.renderToast('error', `${file.name} no es una imagen válida`);
                continue;
            }

            const sizeMB = file.size / (1024 * 1024);
            if (sizeMB > this.fm.config.maxFileSizeMB) {
                this.ui.renderToast('error', `${file.name} excede el límite de ${this.fm.config.maxFileSizeMB}MB`);
                continue;
            }

            try {
                const fotoData = { file, nombre: file.name };

                if (tipo === 'tela') {
                    this.fm.addFotoTela(prendaId, fotoData);
                    this.ui.renderToast('success', ` Foto de tela cargada`);
                } else {
                    this.fm.addFotoPrenda(prendaId, fotoData);
                    this.ui.renderToast('success', ` Foto de prenda cargada`);
                }
            } catch (error) {
                this.ui.renderToast('error', error.message);
            }
        }

        // Limpiar input
        event.target.value = '';
    }

    /**
     * Eliminar foto
     */
    deleteFoto(prendaId, fotoId, tipo) {
        if (!confirm('⚠️ ¿Eliminar esta foto?')) return;

        this.fm.deleteFoto(prendaId, fotoId, tipo);
        this.ui.renderToast('success', ' Foto eliminada');
    }

    // ==================== PROCESO OPERATIONS ====================

    /**
     * Mostrar modal para agregar proceso
     */
    showAddProcesoModal(prendaId) {
        const form = `
            <form id="proceso-form">
                <div class="form-group">
                    <label for="tipo_proceso_id">Tipo de proceso *</label>
                    <select class="form-control" id="tipo_proceso_id" name="tipo_proceso_id" required>
                        <option value="">Seleccionar...</option>
                        <option value="1">1 - Bordado</option>
                        <option value="2">2 - Estampado</option>
                        <option value="3">3 - DTF</option>
                        <option value="4">4 - Sublimado</option>
                        <option value="5">5 - Confección especial</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="ubicaciones">Ubicaciones (seleccionar múltiples) *</label>
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="ub_pecho" name="ubicaciones" value="pecho">
                        <label class="custom-control-label" for="ub_pecho">Pecho</label>
                    </div>
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="ub_espalda" name="ubicaciones" value="espalda">
                        <label class="custom-control-label" for="ub_espalda">Espalda</label>
                    </div>
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="ub_brazo" name="ubicaciones" value="brazo">
                        <label class="custom-control-label" for="ub_brazo">Brazo</label>
                    </div>
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="ub_costado" name="ubicaciones" value="costado">
                        <label class="custom-control-label" for="ub_costado">Costado</label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="observaciones">Observaciones</label>
                    <textarea class="form-control" id="observaciones" name="observaciones" placeholder="Detalles del proceso..." rows="3"></textarea>
                </div>
            </form>
        `;

        this.showModal('Agregar proceso', form, [
            { label: ' Guardar', action: 'modal-save', variant: 'primary' }
        ]);

        this.currentModalContext = { type: 'add-proceso', prendaId };
    }

    /**
     * Mostrar modal para editar proceso
     */
    showEditProcesoModal(prendaId, procesoId) {
        const prenda = this.fm.getPrenda(prendaId);
        const proceso = prenda.procesos.find(p => p._id === procesoId);

        if (!proceso) {
            this.ui.renderToast('error', 'Proceso no encontrado');
            return;
        }

        const ubicacionesMap = {
            'pecho': 'ub_pecho',
            'espalda': 'ub_espalda',
            'brazo': 'ub_brazo',
            'costado': 'ub_costado'
        };

        const form = `
            <form id="proceso-form">
                <div class="form-group">
                    <label for="tipo_proceso_id">Tipo de proceso *</label>
                    <select class="form-control" id="tipo_proceso_id" name="tipo_proceso_id" required>
                        <option value="">Seleccionar...</option>
                        <option value="1" ${proceso.tipo_proceso_id == 1 ? 'selected' : ''}>1 - Bordado</option>
                        <option value="2" ${proceso.tipo_proceso_id == 2 ? 'selected' : ''}>2 - Estampado</option>
                        <option value="3" ${proceso.tipo_proceso_id == 3 ? 'selected' : ''}>3 - DTF</option>
                        <option value="4" ${proceso.tipo_proceso_id == 4 ? 'selected' : ''}>4 - Sublimado</option>
                        <option value="5" ${proceso.tipo_proceso_id == 5 ? 'selected' : ''}>5 - Confección especial</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="ubicaciones">Ubicaciones</label>
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="ub_pecho" name="ubicaciones" value="pecho" ${proceso.ubicaciones.includes('pecho') ? 'checked' : ''}>
                        <label class="custom-control-label" for="ub_pecho">Pecho</label>
                    </div>
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="ub_espalda" name="ubicaciones" value="espalda" ${proceso.ubicaciones.includes('espalda') ? 'checked' : ''}>
                        <label class="custom-control-label" for="ub_espalda">Espalda</label>
                    </div>
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="ub_brazo" name="ubicaciones" value="brazo" ${proceso.ubicaciones.includes('brazo') ? 'checked' : ''}>
                        <label class="custom-control-label" for="ub_brazo">Brazo</label>
                    </div>
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="ub_costado" name="ubicaciones" value="costado" ${proceso.ubicaciones.includes('costado') ? 'checked' : ''}>
                        <label class="custom-control-label" for="ub_costado">Costado</label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="observaciones">Observaciones</label>
                    <textarea class="form-control" id="observaciones" name="observaciones" rows="3">${this.ui.escape(proceso.observaciones)}</textarea>
                </div>
            </form>
        `;

        this.showModal('Editar proceso', form, [
            { label: ' Guardar', action: 'modal-save', variant: 'primary' }
        ]);

        this.currentModalContext = { type: 'edit-proceso', prendaId, procesoId };
    }

    /**
     * Guardar/actualizar proceso
     */
    saveProceso(formData) {
        const tipoValidation = this.validator.validarCampo('tipo_proceso_id', parseInt(formData.tipo_proceso_id));
        if (!tipoValidation.valid) {
            tipoValidation.errors.forEach(err => this.ui.renderToast('warning', err));
            return;
        }

        const ubicaciones = Array.isArray(formData.ubicaciones)
            ? formData.ubicaciones
            : (formData.ubicaciones ? [formData.ubicaciones] : []);

        const ubicacionesValidation = this.validator.validarCampo('ubicaciones', ubicaciones);
        if (!ubicacionesValidation.valid) {
            ubicacionesValidation.errors.forEach(err => this.ui.renderToast('warning', err));
            return;
        }

        const data = {
            tipo_proceso_id: parseInt(formData.tipo_proceso_id),
            ubicaciones,
            observaciones: formData.observaciones
        };

        if (this.currentModalContext.type === 'add-proceso') {
            this.fm.addProceso(this.currentModalContext.prendaId, data);
            this.ui.renderToast('success', ' Proceso agregado');
        } else {
            this.fm.editProceso(
                this.currentModalContext.prendaId,
                this.currentModalContext.procesoId,
                data
            );
            this.ui.renderToast('success', ' Proceso actualizado');
        }

        this.closeModal();
    }

    /**
     * Eliminar proceso
     */
    deleteProceso(prendaId, procesoId) {
        if (!confirm('⚠️ ¿Eliminar este proceso?')) return;

        this.fm.deleteProceso(prendaId, procesoId);
        this.ui.renderToast('success', ' Proceso eliminado');
    }

    // ==================== MODAL OPERATIONS ====================

    /**
     * Mostrar modal
     */
    showModal(title, content, actions) {
        const html = this.ui.renderModal(title, content, actions);
        
        // Remover modal anterior si existe
        const oldModal = document.getElementById('formModal');
        if (oldModal) oldModal.remove();

        // Agregar nuevo modal al DOM
        document.body.insertAdjacentHTML('beforeend', html);

        // Mostrar modal
        const modal = new bootstrap.Modal(document.getElementById('formModal'));
        modal.show();

        // Guardar referencia
        this.currentModal = modal;
    }

    /**
     * Guardar modal
     */
    saveModal() {
        const form = document.getElementById('prenda-form') || 
                     document.getElementById('variante-form') || 
                     document.getElementById('proceso-form');

        if (!form) {
            this.ui.renderToast('error', 'Formulario no encontrado');
            return;
        }

        const formData = new FormData(form);
        const data = Object.fromEntries(formData);

        if (this.currentModalContext.type === 'add-prenda' || this.currentModalContext.type === 'edit-prenda') {
            this.savePrenda(data);
        } else if (this.currentModalContext.type === 'add-variante' || this.currentModalContext.type === 'edit-variante') {
            this.saveVariante(data);
        } else if (this.currentModalContext.type === 'add-proceso' || this.currentModalContext.type === 'edit-proceso') {
            this.saveProceso(data);
        }
    }

    /**
     * Cerrar modal
     */
    closeModal() {
        if (this.currentModal) {
            this.currentModal.hide();
        }
    }

    // ==================== PEDIDO OPERATIONS ====================

    /**
     * Validar pedido completo
     */
    validatePedido() {
        const state = this.fm.getState();
        const reporte = this.validator.obtenerReporte(state);

        if (reporte.valid) {
            this.ui.renderToast('success', reporte.mensaje);
        } else {
            const errorHtml = this.ui.renderValidationErrors(reporte.errores);
            this.showModal(' Errores de validación', errorHtml, []);
        }
    }

    /**
     *  TRANSFORMACIÓN DE ESTADO PARA ENVÍO
     * 
     * Transforma el estado para eliminar objetos File no serializables.
     * Preserva SOLO los metadatos necesarios para el backend.
     * GARANTÍA: JSON resultante es 100% serializable sin File objects.
     * 
     * @param {Object} state Estado completo del formulario
     * @returns {Object} Estado transformado, listo para JSON.stringify()
     */
    transformStateForSubmit(state) {
        return {
            pedido_produccion_id: state.pedido_produccion_id,
            prendas: state.prendas.map(prenda => ({
                // Metadatos básicos de la prenda
                nombre_prenda: prenda.nombre_prenda,
                descripcion: prenda.descripcion,
                genero: prenda.genero,
                de_bodega: prenda.de_bodega,

                // Variantes: incluir TODOS los metadatos excepto File
                variantes: (prenda.variantes || []).map(v => ({
                    talla: v.talla,
                    cantidad: v.cantidad,
                    color_id: v.color_id,
                    tela_id: v.tela_id,
                    tipo_manga_id: v.tipo_manga_id,
                    manga_obs: v.manga_obs,
                    tipo_broche_boton_id: v.tipo_broche_boton_id,
                    broche_boton_obs: v.broche_boton_obs,
                    tiene_bolsillos: v.tiene_bolsillos,
                    bolsillos_obs: v.bolsillos_obs
                })),

                // Fotos de prenda: SOLO metadatos (sin File)
                fotos_prenda: (prenda.fotos_prenda || []).map(foto => ({
                    nombre: foto.nombre,
                    observaciones: foto.observaciones || ''
                    //  NO incluir: foto.file (va en FormData)
                })),

                // Fotos de tela: SOLO metadatos (sin File)
                fotos_tela: (prenda.fotos_tela || []).map(foto => ({
                    nombre: foto.nombre,
                    color: foto.color || '',
                    observaciones: foto.observaciones || ''
                    //  NO incluir: foto.file (va en FormData)
                })),

                // Procesos: SOLO metadatos de procesos, imagenes van separadas
                procesos: (prenda.procesos || []).map(p => ({
                    tipo_proceso_id: p.tipo_proceso_id,
                    ubicaciones: p.ubicaciones || [],
                    observaciones: p.observaciones || ''
                    //  NO incluir: p.imagenes (van en FormData)
                }))
            }))
        };
    }

    /**
     * Enviar pedido al backend
     */
    async submitPedido() {
        const state = this.fm.getState();
        const reporte = this.validator.obtenerReporte(state);

        if (!reporte.valid) {
            const errorHtml = this.ui.renderValidationErrors(reporte.errores);
            this.showModal(' No se puede enviar', errorHtml, []);
            return;
        }

        if (this.isSubmitting) return;

        this.isSubmitting = true;
        console.log('📤 Enviando pedido...', state);

        try {
            //  TRANSFORMAR ESTADO: Eliminar File objects, mantener solo metadatos
            const stateToSend = this.transformStateForSubmit(state);

            // Preparar FormData con archivos
            const formData = new FormData();
            formData.append('pedido_produccion_id', state.pedido_produccion_id);
            
            //  ENVIAR JSON LIMPIO (sin File objects)
            formData.append('prendas', JSON.stringify(stateToSend.prendas));

            //  ADJUNTAR ARCHIVOS CON ÍNDICES CORRECTOS
            state.prendas.forEach((prenda, prendaIdx) => {
                // Fotos de prenda
                (prenda.fotos_prenda || []).forEach((foto, fotoIdx) => {
                    if (foto.file) {
                        formData.append(`prenda_${prendaIdx}_foto_${fotoIdx}`, foto.file);
                    }
                });

                // Fotos de tela
                (prenda.fotos_tela || []).forEach((foto, fotoIdx) => {
                    if (foto.file) {
                        formData.append(`prenda_${prendaIdx}_tela_${fotoIdx}`, foto.file);
                    }
                });

                //  CORREGIDO: Usar procesoIdx (no reutilizar prendaIdx)
                (prenda.procesos || []).forEach((proceso, procesoIdx) => {
                    (proceso.imagenes || []).forEach((img, imgIdx) => {
                        if (img.file) {
                            formData.append(
                                `prenda_${prendaIdx}_proceso_${procesoIdx}_img_${imgIdx}`, 
                                img.file
                            );
                        }
                    });
                });
            });

            // Enviar
            const response = await fetch('/api/pedidos/guardar-desde-json', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: formData
            });

            const result = await response.json();

            if (!response.ok) {
                throw new Error(result.message || 'Error al enviar pedido');
            }

            if (result.success) {
                this.ui.renderToast('success', ` Pedido guardado: ${result.numero_pedido}`);
                
                // Mostrar resumen
                const resumen = this.ui.renderResumen(result);
                this.showModal(' ¡Pedido guardado exitosamente!', resumen, []);

                // Limpiar después de 3 segundos
                setTimeout(() => {
                    this.fm.clear();
                    this.closeModal();
                }, 3000);
            } else {
                throw new Error(result.message || 'Error desconocido');
            }
        } catch (error) {
            console.error(' Error enviando pedido:', error);
            this.ui.renderToast('error', `Error: ${error.message}`);
        } finally {
            this.isSubmitting = false;
        }
    }

    // ==================== RENDER ====================

    /**
     * Renderizar formulario completo
     */
    render() {
        if (!this.container) return;

        const state = this.fm.getState();
        const summary = this.fm.getSummary();
        const prendas = this.fm.getPrendas();

        let html = `
            <div class="pedido-form-container">
                <!-- HEADER -->
                <div class="card mb-4 bg-dark text-white">
                    <div class="card-header">
                        <h4>📦 Formulario de Pedido de Producción</h4>
                    </div>
                    <div class="card-body">
                        ${state.pedido_produccion_id 
                            ? `<p class="mb-0"><strong>Pedido ID:</strong> ${state.pedido_produccion_id}</p>`
                            : '<p class="mb-0 text-warning">⚠️ Debe seleccionar un pedido de producción</p>'
                        }
                    </div>
                </div>

                <!-- RESUMEN -->
                ${this.ui.renderResumen(summary)}

                <!-- PRENDAS -->
                <div class="prendas-container">
                    ${prendas.length > 0
                        ? prendas.map(p => this.ui.renderPrendaCard(p)).join('')
                        : '<div class="alert alert-info"> No hay prendas agregadas aún</div>'
                    }
                </div>

                <!-- ACCIONES -->
                <div class="action-buttons mt-4">
                    <button class="btn btn-lg btn-success" data-action="add-prenda">
                        ➕ Agregar prenda
                    </button>
                    <button class="btn btn-lg btn-primary" data-action="validate-pedido">
                        ✓ Validar pedido
                    </button>
                    <button class="btn btn-lg btn-info" data-action="submit-pedido">
                        📤 Enviar pedido
                    </button>
                    <button class="btn btn-lg btn-danger" data-action="clear-pedido">
                        🗑️ Limpiar
                    </button>
                </div>
            </div>
        `;

        this.container.innerHTML = html;
    }

    /**
     * Destruir handlers
     */
    // ==================== DIAGNOSTIC & VALIDATION ====================

    /**
     *  VALIDAR INTEGRIDAD DE TRANSFORMACIÓN
     * 
     * Garantiza que:
     * 1. JSON es serializable (sin File objects)
     * 2. Índices son correctos y únicos
     * 3. Metadatos se preservan correctamente
     * 
     * @returns {Object} Reporte de validación
     */
    validateTransformation() {
        const state = this.fm.getState();
        const stateToSend = this.transformStateForSubmit(state);
        const report = {
            valid: true,
            errors: [],
            warnings: [],
            metadata: {}
        };

        try {
            // TEST 1: JSON es serializable
            const jsonString = JSON.stringify(stateToSend.prendas);
            report.metadata.jsonSerializable = true;
            report.metadata.jsonSize = jsonString.length;
        } catch (error) {
            report.valid = false;
            report.errors.push(` JSON NO serializable: ${error.message}`);
        }

        // TEST 2: No hay File objects en el JSON
        stateToSend.prendas.forEach((prenda, pIdx) => {
            // Verificar fotos_prenda
            (prenda.fotos_prenda || []).forEach((foto, fIdx) => {
                if (foto.file instanceof File) {
                    report.valid = false;
                    report.errors.push(
                        ` File encontrado en prenda[${pIdx}].fotos_prenda[${fIdx}]`
                    );
                }
                if (typeof foto !== 'object' || foto === null) {
                    report.errors.push(
                        `⚠️ Foto malformada en prenda[${pIdx}].fotos_prenda[${fIdx}]`
                    );
                }
            });

            // Verificar fotos_tela
            (prenda.fotos_tela || []).forEach((foto, fIdx) => {
                if (foto.file instanceof File) {
                    report.valid = false;
                    report.errors.push(
                        ` File encontrado en prenda[${pIdx}].fotos_tela[${fIdx}]`
                    );
                }
            });

            // Verificar procesos
            (prenda.procesos || []).forEach((proceso, pIdx) => {
                if (proceso.imagenes) {
                    report.warnings.push(
                        `⚠️ Campo 'imagenes' aún existe en prenda procesos[${pIdx}] (debe estar vacío o ignorado)`
                    );
                }
            });
        });

        // TEST 3: Validar índices de FormData
        const formDataKeys = new Set();
        state.prendas.forEach((prenda, prendaIdx) => {
            (prenda.fotos_prenda || []).forEach((foto, fotoIdx) => {
                if (foto.file) {
                    const key = `prenda_${prendaIdx}_foto_${fotoIdx}`;
                    if (formDataKeys.has(key)) {
                        report.valid = false;
                        report.errors.push(` Índice duplicado: ${key}`);
                    }
                    formDataKeys.add(key);
                }
            });

            (prenda.procesos || []).forEach((proceso, procesoIdx) => {
                (proceso.imagenes || []).forEach((img, imgIdx) => {
                    if (img.file) {
                        const key = `prenda_${prendaIdx}_proceso_${procesoIdx}_img_${imgIdx}`;
                        if (formDataKeys.has(key)) {
                            report.valid = false;
                            report.errors.push(` Índice duplicado: ${key}`);
                        }
                        formDataKeys.add(key);
                    }
                });
            });
        });

        report.metadata.uniqueFormDataKeys = formDataKeys.size;

        return report;
    }

    /**
     *  IMPRIMIR DIAGNÓSTICO EN CONSOLA
     * 
     * Útil para debugging durante desarrollo.
     */
    printDiagnostics() {
        const state = this.fm.getState();
        const stateToSend = this.transformStateForSubmit(state);
        const validation = this.validateTransformation();

        console.group('🔍 DIAGNÓSTICO DE TRANSFORMACIÓN');

        console.log(' Estado transformado (sin File):');
        console.log(JSON.stringify(stateToSend, null, 2));

        console.log('\n Validación:');
        console.table(validation);

        if (validation.errors.length > 0) {
            console.error(' ERRORES ENCONTRADOS:');
            validation.errors.forEach(err => console.error(`  - ${err}`));
        }

        if (validation.warnings.length > 0) {
            console.warn('⚠️ ADVERTENCIAS:');
            validation.warnings.forEach(warn => console.warn(`  - ${warn}`));
        }

        console.groupEnd();

        return validation;
    }

    // ==================== DESTRUCTOR ====================

    destroy() {
        this.fm.destroy();
        console.log('🗑️  PedidoFormHandlers destruido');
    }
}

// Exportar
if (typeof module !== 'undefined' && module.exports) {
    module.exports = PedidoFormHandlers;
}
