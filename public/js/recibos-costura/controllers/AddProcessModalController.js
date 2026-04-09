/**
 * AddProcessModalController
 * Controlador para agregar procesos a prendas desde badges de área
 * 
 * Responsabilidades:
 * - Abrir modal de agregar proceso desde badge
 * - Cargar datos del pedido y prenda
 * - Validar datos antes de guardar
 * - Enviar proceso al backend
 * - Manejar respuestas y mostrar notificaciones
 * 
 * @class AddProcessModalController
 * @example
 * const controller = AddProcessModalController.getInstance();
 * controller.openFromBadge(areaSeleccionada, pedidoId, prendaId);
 */

class AddProcessModalController {
    constructor() {
        this.currentOrderData = null;
        this.currentPrendaData = null;
        this._boundVerifyAndSave = this.verifyAndSave.bind(this);
    }

    /**
     * Obtener instancia singleton del controlador
     * @static
     * @returns {AddProcessModalController} Instancia única
     */
    static getInstance() {
        if (!window.addProcessModalControllerInstance) {
            window.addProcessModalControllerInstance = new AddProcessModalController();
        }
        return window.addProcessModalControllerInstance;
    }

    /**
     * Abrir modal de agregar proceso desde badge
     * @public
     * @param {string} areaSeleccionada - Área seleccionada
     * @param {number} pedidoId - ID del pedido
     * @param {number} prendaId - ID de la prenda
     */
    async openFromBadge(areaSeleccionada, pedidoId, prendaId) {
        try {
            console.log('[AddProcessModalController]  Área seleccionada:', areaSeleccionada, 'Pedido:', pedidoId, 'Prenda:', prendaId);

            // Cerrar cualquier dropdown abierto
            if (typeof closeDropdownRecibos === 'function') {
                closeDropdownRecibos();
            }

            // Verificar que tengamos los IDs necesarios
            if (!pedidoId) {
                console.error('[AddProcessModalController] No se proporcionó ID del pedido');
                alert('No se puede identificar el pedido asociado');
                return;
            }

            // Cargar datos del pedido y prenda antes de abrir el modal
            await this.loadData(pedidoId, prendaId, areaSeleccionada);

            // Verificación adicional antes de abrir el modal
            console.log('[AddProcessModalController] Verificación pre-apertura:', {
                hasOrderData: !!window.currentOrderData,
                hasPrendaData: !!window.currentPrendaData,
                orderNumero: window.currentOrderData?.numero_pedido,
                prendaId: window.currentPrendaData?.id
            });

            if (!window.currentOrderData || !window.currentPrendaData) {
                throw new Error('No se pudieron cargar los datos necesarios');
            }

            this._openModalUI(pedidoId, prendaId, areaSeleccionada);
        } catch (error) {
            console.error('[AddProcessModalController] Error al abrir modal:', error);
            alert('Error al cargar los datos del pedido: ' + error.message);
        }
    }

    /**
     * Cargar datos del pedido y prenda
     * @public
     * @param {number} pedidoId - ID del pedido
     * @param {number} prendaId - ID de la prenda
     * @param {string} areaSeleccionada - Área seleccionada
     */
    async loadData(pedidoId, prendaId, areaSeleccionada) {
        console.log('[AddProcessModalController] Cargando datos para pedido:', pedidoId, 'prenda:', prendaId);

        try {
            // Validar que se proporcionó una prenda específica
            if (!prendaId || prendaId === 'null' || prendaId === null) {
                throw new Error('CRÍTICO: No se proporcionó una prenda específica. No se puede asignar encargado sin prenda definida.');
            }

            // Cargar datos básicos del pedido
            const response = await fetch(`/registros/${pedidoId}/recibos-datos`);
            if (!response.ok) throw new Error('Error al cargar datos del pedido');

            const result = await response.json();
            const data = result.data || result;

            console.log('[AddProcessModalController] Datos recibidos del endpoint:', data);

            // Asegurar que la estructura de datos sea compatible
            const orderData = {
                ...data,
                numero_pedido: data.numero_pedido || data.id || pedidoId,
                pedido: data.numero_pedido || data.id || pedidoId
            };

            // Establecer variables globales
            window.currentOrderData = orderData;
            window.currentPedidoId = pedidoId;
            window.currentPrendaId = prendaId;
            window.currentArea = areaSeleccionada;

            // Buscar la prenda específica en los datos del pedido
            if (data.prendas && Array.isArray(data.prendas)) {
                const prendaEncontrada = data.prendas.find(p =>
                    String(p.id) === String(prendaId) ||
                    String(p.prenda_pedido_id) === String(prendaId)
                );

                if (prendaEncontrada) {
                    window.currentPrendaData = prendaEncontrada;
                    console.log('[AddProcessModalController]  Prenda encontrada:', prendaEncontrada.nombre_prenda || prendaEncontrada.nombre);
                } else {
                    throw new Error(`Prenda con ID ${prendaId} no encontrada en pedido ${pedidoId}`);
                }
            } else {
                throw new Error('El pedido no tiene prendas asociadas');
            }

            console.log('[AddProcessModalController]  Datos cargados correctamente');
        } catch (error) {
            console.error('[AddProcessModalController] Error cargando datos:', error);
            throw error;
        }
    }

    /**
     * Verificar datos antes de guardar
     * @public
     * @param {Event} event - Evento del click
     */
    async verifyAndSave(event) {
        console.log('[AddProcessModalController] Verificando datos antes de guardar...');

        // Si no hay datos, intentar recargar
        if (!window.currentOrderData || !window.currentPrendaData) {
            console.log('[AddProcessModalController] Datos no disponibles, intentando recuperar...');

            const modal = document.getElementById('addProcesoModal');
            if (modal) {
                const pedidoId = modal.getAttribute('data-pedido-id');
                const prendaId = modal.getAttribute('data-prenda-id');
                const area = modal.getAttribute('data-area');

                if (pedidoId) {
                    try {
                        await this.loadData(pedidoId, prendaId, area);
                        console.log('[AddProcessModalController] Datos recargados exitosamente');
                    } catch (error) {
                        console.error('[AddProcessModalController] Error al recargar datos:', error);
                        alert('Error al cargar los datos: ' + error.message);
                        event.preventDefault();
                        event.stopPropagation();
                        return false;
                    }
                }
            }
        }

        if (!window.currentOrderData || !window.currentPrendaData) {
            console.error('[AddProcessModalController]  Faltan datos necesarios');
            alert('Error: No hay datos de la prenda o pedido. Por favor, recarga la página e intenta nuevamente.');
            event.preventDefault();
            event.stopPropagation();
            return false;
        }

        console.log('[AddProcessModalController]  Datos verificados, procediendo...');
        return this.save();
    }

    /**
     * Guardar proceso
     * @public
     */
    async save() {
        try {
            console.log('[AddProcessModalController] Iniciando guardado de proceso...');

            this._showLoadingState(true);

            const { area, encargado } = this._getFormData();

            // Validar área
            if (!area) {
                this._showError('Por favor selecciona un área/proceso');
                return false;
            }

            // Validar encargado si es requerido
            const areaLower = area.toLowerCase();
            const needsEncargado = ['corte', 'costura', 'control de calidad'];
            const areaRequiresEncargado = needsEncargado.some(reqArea => areaLower.includes(reqArea));

            if (areaRequiresEncargado && !encargado.trim()) {
                this._showError('Por favor selecciona o ingresa el encargado');
                return false;
            }

            if (!window.currentOrderData || !window.currentPrendaData) {
                this._showError('No hay datos de la prenda o pedido');
                return false;
            }

            console.log('[AddProcessModalController] Enviando proceso:', {
                area,
                encargado,
                pedido_produccion_id: window.currentOrderData.numero_pedido,
                prenda_id: window.currentPrendaData.id
            });

            // Enviar datos al backend
            const response = await fetch('/seguimiento-proceso/guardar', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                },
                body: JSON.stringify({
                    pedido_produccion_id: window.currentOrderData.numero_pedido,
                    prenda_id: window.currentPrendaData.id,
                    area: area,
                    encargado: encargado,
                    estado: 'Pendiente'
                })
            });

            if (!response.ok) {
                throw new Error('Error al agregar proceso');
            }

            const result = await response.json();

            // Mostrar mensaje diferente según si fue creado o actualizado
            const mensaje = result.action === 'actualizado'
                ? 'Proceso actualizado correctamente'
                : 'Proceso agregado correctamente';

            console.log('[AddProcessModalController] Mostrando mensaje:', mensaje);
            this._showSuccess(mensaje);

            // Limpiar y cerrar
            this.clearForm();
            this._closeModal();

            // Refrescar datos/local UI sin recargar navegador
            if (result?.data?.prenda) {
                window.currentPrendaData = result.data.prenda;
                if (typeof renderPrendaTrackingTimeline === 'function') {
                    renderPrendaTrackingTimeline(window.currentPrendaData);
                }
            } else if (typeof loadPrendasWithTracking === 'function' && window.currentOrderData?.id) {
                await loadPrendasWithTracking(window.currentOrderData.id);
                if (window.prendasData && Array.isArray(window.prendasData) && window.currentPrendaData?.id) {
                    const prendaActualizada = window.prendasData.find((p) => String(p.id) === String(window.currentPrendaData.id));
                    if (prendaActualizada) {
                        window.currentPrendaData = prendaActualizada;
                        if (typeof renderPrendaTrackingTimeline === 'function') {
                            renderPrendaTrackingTimeline(window.currentPrendaData);
                        }
                    }
                }
            }

            await this._refreshReciboRowInTable({ encargadoFallback: encargado });

            if (typeof actualizarAreaEnTablaRecibos === 'function') {
                await actualizarAreaEnTablaRecibos();
            }

            return true;
        } catch (error) {
            console.error('[AddProcessModalController] Error:', error);
            this._showError('Error al agregar proceso: ' + error.message);
            return false;
        } finally {
            this._showLoadingState(false);
        }
    }

    /**
     * Limpiar formulario
     * @public
     */
    clearForm() {
        const selectArea = document.getElementById('procesoArea');
        const inputEncargado = document.getElementById('procesoEncargado');
        const selectEncargado = document.getElementById('procesoEncargadoSelect');

        if (selectArea) selectArea.value = '';
        if (inputEncargado) inputEncargado.value = '';
        if (selectEncargado) selectEncargado.value = '';
    }

    /**
     * PRIVADO: Refrescar fila de la tabla de recibos-costura sin recargar la página
     * @private
     * @param {Object} options
     * @param {string} options.encargadoFallback
     */
    async _refreshReciboRowInTable({ encargadoFallback = '' } = {}) {
        try {
            if (!String(window.location.pathname || '').includes('/recibos-costura')) {
                return;
            }

            const pedidoId = window.currentOrderData?.id || window.currentPedidoId || window.currentOrderData?.numero_pedido;
            const prendaId = window.currentPrendaData?.id || window.currentPrendaId;
            if (!pedidoId || !prendaId) {
                return;
            }

            let reciboData = null;
            try {
                const url = `/registros/${pedidoId}/consecutivo-costura?prenda_id=${encodeURIComponent(prendaId)}`;
                const response = await fetch(url);
                if (response.ok) {
                    const json = await response.json();
                    if (json && json.success) {
                        reciboData = json;
                        window.currentConsecutivoCosturaData = json;
                    }
                }
            } catch (error) {
                console.warn('[AddProcessModalController] No se pudo refrescar consecutivo-costura:', error);
            }

            const numeroRecibo = reciboData?.consecutivo ?? reciboData?.consecutivo_actual ?? null;
            const row = this._findReciboRow(pedidoId, prendaId, numeroRecibo);
            if (!row) {
                return;
            }

            // Actualizar encargado (última columna)
            const encargadoValue = String(reciboData?.encargado ?? encargadoFallback ?? '').trim();
            const encargadoSpan = row.querySelector('td:last-child span');
            if (encargadoSpan) {
                encargadoSpan.textContent = encargadoValue || '-';
            }

            // Actualizar área si viene en respuesta
            if (reciboData?.area) {
                const areaBadge = row.querySelector('td:nth-child(3) .badge');
                if (areaBadge) {
                    areaBadge.textContent = String(reciboData.area);
                    areaBadge.classList.remove('bg-secondary', 'bg-success', 'bg-info', 'bg-primary', 'bg-warning', 'bg-purple');
                    areaBadge.classList.add(this._resolveAreaBadgeClass(reciboData.area));
                }
            }

            // Sincronizar consecutivo en atributos data-* (si se obtuvo)
            if (numeroRecibo) {
                row.setAttribute('data-numero-recibo', String(numeroRecibo));
                const rowButton = row.querySelector('.btn-ver-dropdown');
                if (rowButton) {
                    rowButton.setAttribute('data-numero-recibo', String(numeroRecibo));
                }
            }
        } catch (error) {
            console.warn('[AddProcessModalController] Error actualizando fila de tabla:', error);
        }
    }

    /**
     * PRIVADO: Buscar fila de tabla por pedido/prenda/(opcional) número recibo
     * @private
     * @param {number|string} pedidoId
     * @param {number|string} prendaId
     * @param {number|string|null} numeroRecibo
     * @returns {HTMLElement|null}
     */
    _findReciboRow(pedidoId, prendaId, numeroRecibo = null) {
        const rows = document.querySelectorAll('#tablaRecibosBody tr[data-pedido-id]');
        let fallbackRow = null;

        for (const row of rows) {
            if (String(row.getAttribute('data-pedido-id') || '') !== String(pedidoId)) {
                continue;
            }

            const rowButton = row.querySelector('.btn-ver-dropdown');
            const rowPrendaId = rowButton ? rowButton.getAttribute('data-prenda-id') : null;
            if (String(rowPrendaId || '') !== String(prendaId)) {
                continue;
            }

            if (numeroRecibo !== null && numeroRecibo !== undefined && String(numeroRecibo) !== '') {
                const rowNumero = row.getAttribute('data-numero-recibo');
                if (String(rowNumero || '') === String(numeroRecibo)) {
                    return row;
                }
            }

            if (!fallbackRow) {
                fallbackRow = row;
            }
        }

        return fallbackRow;
    }

    /**
     * PRIVADO: Resolver clase badge para área en tabla
     * @private
     * @param {string} area
     * @returns {string}
     */
    _resolveAreaBadgeClass(area) {
        const name = String(area || '');
        if (name.includes('Corte')) return 'bg-success';
        if (name.includes('Insumos')) return 'bg-info';
        if (name.includes('Costura')) return 'bg-primary';
        if (name.includes('Estampado')) return 'bg-warning';
        if (name.includes('Bordado')) return 'bg-purple';
        return 'bg-secondary';
    }

    /**
     * PRIVADO: Obtener datos del formulario
     * @private
     * @returns {Object} Objeto con area y encargado
     */
    _getFormData() {
        let encargado = '';
        const selectEncargado = document.getElementById('procesoEncargadoSelect');
        const inputEncargado = document.getElementById('procesoEncargado');

        if (selectEncargado && selectEncargado.offsetParent !== null) {
            // Es un select - obtener el texto del option seleccionado
            const selectedOption = selectEncargado.options[selectEncargado.selectedIndex];
            encargado = selectedOption ? selectedOption.text : '';
        } else if (inputEncargado) {
            // Es un input - obtener el valor y convertir a mayúsculas
            encargado = inputEncargado.value.toUpperCase();
        }

        const area = document.getElementById('procesoArea').value;

        return { area, encargado };
    }

    /**
     * PRIVADO: Mostrar o esconder estado de carga
     * @private
     * @param {boolean} isLoading - Si está cargando o no
     */
    _showLoadingState(isLoading) {
        const btnContent = document.getElementById('addProcesoButtonContent');
        const btnLoading = document.getElementById('addProcesoButtonLoading');
        const btnConfirm = document.getElementById('btnConfirmAddProceso');

        if (btnContent && btnLoading && btnConfirm) {
            if (isLoading) {
                btnContent.style.display = 'none';
                btnLoading.style.display = 'flex';
                btnConfirm.disabled = true;
            } else {
                btnContent.style.display = 'flex';
                btnLoading.style.display = 'none';
                btnConfirm.disabled = false;
            }
        }
    }

    /**
     * PRIVADO: Mostrar error usando ToastNotificationService
     * @private
     * @param {string} message - Mensaje de error
     */
    _showError(message) {
        if (typeof showError === 'function') {
            showError(message);
        } else {
            alert(message);
        }
    }

    /**
     * PRIVADO: Mostrar éxito usando ToastNotificationService
     * @private
     * @param {string} message - Mensaje de éxito
     */
    _showSuccess(message) {
        if (typeof showSuccess === 'function') {
            showSuccess(message);
        } else {
            alert(message);
        }
    }

    /**
     * PRIVADO: Cerrar modal
     * @private
     */
    _closeModal() {
        const modal = document.getElementById('addProcesoModal');
        if (modal) {
            modal.classList.remove('show');
            modal.style.display = 'none';
        }
    }

    /**
     * PRIVADO: Abrir modal (UI)
     * @private
     */
    _openModalUI(pedidoId, prendaId, areaSeleccionada) {
        const modal = document.getElementById('addProcesoModal');
        if (!modal) {
            console.error('[AddProcessModalController] Modal no encontrado');
            alert('Modal de agregar proceso no disponible');
            return;
        }

        // Guardar datos en atributos data- para persistencia
        modal.setAttribute('data-pedido-id', pedidoId);
        modal.setAttribute('data-prenda-id', prendaId || '');
        modal.setAttribute('data-area', areaSeleccionada);

        // Mostrar el modal
        modal.style.display = 'flex';
        modal.classList.add('show');

        // Seleccionar automáticamente el área en el select
        const selectArea = document.getElementById('procesoArea');
        if (selectArea) {
            const normalizedArea = String(areaSeleccionada || '').trim().toLowerCase();
            const exactOption = Array.from(selectArea.options || []).find((opt) => String(opt.value || '').trim().toLowerCase() === normalizedArea);
            selectArea.value = exactOption ? exactOption.value : areaSeleccionada;
            console.log('[AddProcessModalController]  Área seleccionada automáticamente:', areaSeleccionada);

            // Importante: disparar change para que se active el selector dinámico de encargados (cortador/costurero)
            selectArea.dispatchEvent(new Event('change', { bubbles: true }));
        }

        // Limpiar el campo de encargado y enfocarlo
        setTimeout(() => {
            const inputEncargado = document.getElementById('procesoEncargado');
            const selectEncargado = document.getElementById('procesoEncargadoSelect');
            if (selectEncargado) {
                selectEncargado.value = '';
                selectEncargado.focus();
                return;
            }
            if (inputEncargado) {
                inputEncargado.value = '';
                inputEncargado.focus();
            }
        }, 120);

        // Agregar listener para verificar datos al hacer clic en "Agregar Proceso"
        const btnConfirm = document.getElementById('btnConfirmAddProceso');
        if (btnConfirm) {
            btnConfirm.removeEventListener('click', this._boundVerifyAndSave);
            btnConfirm.addEventListener('click', this._boundVerifyAndSave);
        }

        console.log('[AddProcessModalController]  Modal abierto con datos cargados');
    }
}
