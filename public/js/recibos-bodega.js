const recibosBodegaConfig = window.RECIBOS_BODEGA_CONFIG || {};
const isAdminBodega = Boolean(recibosBodegaConfig.isAdminBodega);

document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('reciboBodegaCreateModal');
    const openBtn = document.getElementById('openReciboBodegaModalBtn');
    const prendasContainer = document.getElementById('prendasContainer');
    const form = document.getElementById('reciboBodegaCreateForm');
    const tipoTallaModal = document.getElementById('tipoTallaGeneroModal');
    const tipoTallaModalText = document.getElementById('tipoTallaGeneroModalText');
    const tipoTallaCloseBtn = document.getElementById('tipoTallaCloseBtn');
    const tipoTallaGeneroBadge = document.getElementById('tipoTallaGeneroBadge');
    const tipoTallaWizardSummary = document.getElementById('tipoTallaWizardSummary');
    const tipoTallaBackBtn = document.getElementById('tipoTallaBackBtn');
    const tipoTallaStepModo = document.getElementById('tipoTallaStepModo');
    const tipoTallaStepTipo = document.getElementById('tipoTallaStepTipo');
    const tipoTallaStepCaptura = document.getElementById('tipoTallaStepCaptura');
    const tipoTallaModoActions = document.getElementById('tipoTallaModoActions');
    const tipoTallaTipoActions = document.getElementById('tipoTallaTipoActions');
    const tipoTallaCancelarBtn = document.getElementById('cancelarTipoTallaGeneroBtn');
    const tipoTallaConfirmarBtn = document.getElementById('confirmarTipoTallaGeneroBtn');
    const tipoTallaGrid = document.getElementById('tipoTallaGeneroGrid');
    const rcbSuccessModal = document.getElementById('rcbSuccessModal');
    const rcbSuccessModalTitle = document.getElementById('rcbSuccessModalTitle');
    const rcbSuccessModalMessage = document.getElementById('rcbSuccessModalMessage');
    const rcbSuccessModalOkBtn = document.getElementById('rcbSuccessModalOkBtn');
    const addProcesoModal = document.getElementById('addProcesoModal');
    const addProcesoOverlay = document.getElementById('addProcesoOverlay');
    const closeAddProcesoBtn = document.getElementById('closeAddProcesoModal');
    const cancelAddProcesoBtn = document.getElementById('btnCancelAddProceso');
    const confirmAddProcesoBtn = document.getElementById('btnConfirmAddProceso');
    const procesoAreaSelect = document.getElementById('procesoArea');
    const procesoEncargadoInput = document.getElementById('procesoEncargado');
    const procesoEncargadoGroup = procesoEncargadoInput?.closest('.add-proceso-form-group') || null;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    let adminBadgeContext = null;

    if (!modal || !prendasContainer || !form || !tipoTallaModal || !tipoTallaModalText) return;

    function showReciboSuccessModal() {
        if (!rcbSuccessModal) return;
        if (rcbSuccessModalTitle) rcbSuccessModalTitle.textContent = 'Recibo registrado';
        if (rcbSuccessModalMessage) rcbSuccessModalMessage.textContent = 'El recibo se guardó correctamente.';
        rcbSuccessModal.style.display = 'flex';
    }

    function showFeedbackModal(title, message) {
        if (!rcbSuccessModal) return;
        if (rcbSuccessModalTitle) rcbSuccessModalTitle.textContent = String(title || 'Mensaje');
        if (rcbSuccessModalMessage) rcbSuccessModalMessage.textContent = String(message || '');
        rcbSuccessModal.style.display = 'flex';
    }

    function hideReciboSuccessModal() {
        if (!rcbSuccessModal) return;
        rcbSuccessModal.style.display = 'none';
    }

    rcbSuccessModalOkBtn?.addEventListener('click', hideReciboSuccessModal);
    rcbSuccessModal?.addEventListener('click', function (event) {
        if (event.target === rcbSuccessModal) {
            hideReciboSuccessModal();
        }
    });

    function closeAdminAddProcesoModal() {
        if (!addProcesoModal) return;
        addProcesoModal.classList.remove('show');
        addProcesoModal.style.display = 'none';
        if (procesoAreaSelect) procesoAreaSelect.value = '';
        if (procesoEncargadoInput) procesoEncargadoInput.value = '';
        adminBadgeContext = null;
    }

    function openAdminAddProcesoModal(context) {
        if (!addProcesoModal) return;
        adminBadgeContext = context;
        if (procesoAreaSelect) {
            procesoAreaSelect.value = 'Corte';
            procesoAreaSelect.setAttribute('disabled', 'disabled');
        }
        ensureEncargadoSelectForCorte().then(() => preselectEncargadoFromProceso(context));
        if (procesoEncargadoInput) procesoEncargadoInput.value = '';
        addProcesoModal.style.display = 'flex';
        addProcesoModal.classList.add('show');
    }

    async function ensureEncargadoSelectForCorte() {
        if (!procesoEncargadoGroup) return;
        procesoEncargadoGroup.style.display = 'block';

        let select = document.getElementById('procesoEncargadoSelect');
        if (!select) {
            select = document.createElement('select');
            select.id = 'procesoEncargadoSelect';
            select.className = 'add-proceso-select';
            procesoEncargadoGroup.appendChild(select);
        }

        if (procesoEncargadoInput) {
            procesoEncargadoInput.style.display = 'none';
        }

        select.innerHTML = '<option value="">Cargando encargados...</option>';
        try {
            const resp = await fetch('/api/areas/corte/encargados', {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
            const data = await resp.json().catch(() => ({}));
            const usuarios = Array.isArray(data?.encargados) ? data.encargados : [];

            select.innerHTML = '<option value="">Seleccionar encargado...</option>';
            usuarios.forEach((u) => {
                const opt = document.createElement('option');
                opt.value = String(u?.id ?? '');
                opt.textContent = String(u?.nombre || u?.name || '').toUpperCase();
                select.appendChild(opt);
            });
        } catch (e) {
            select.innerHTML = '<option value="">No se pudieron cargar encargados</option>';
        }
    }

    async function preselectEncargadoFromProceso(context) {
        const select = document.getElementById('procesoEncargadoSelect');
        if (!select) return;

        const numeroRecibo = Number(context?.numero_recibo || 0);
        const prendaBodegaId = Number(context?.prenda_bodega_id || 0);
        if (!numeroRecibo) return;

        try {
            const qs = prendaBodegaId > 0 ? `?prenda_bodega_id=${encodeURIComponent(String(prendaBodegaId))}` : '';
            const resp = await fetch(`/api/recibos-bodega/${numeroRecibo}/procesos${qs}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
            const procesos = await resp.json().catch(() => []);
            if (!resp.ok || !Array.isArray(procesos) || procesos.length === 0) return;

            const procesoCorte = procesos.find((p) => String(p?.proceso || '').toLowerCase().includes('corte')) || procesos[0];
            const encargadoActual = String(procesoCorte?.encargado || '').trim().toUpperCase();
            if (!encargadoActual) return;

            let matched = false;
            for (const option of select.options) {
                if (String(option.textContent || '').trim().toUpperCase() === encargadoActual) {
                    select.value = option.value;
                    matched = true;
                    break;
                }
            }

            if (!matched && /^\d+$/.test(encargadoActual)) {
                const byId = Array.from(select.options).find((o) => String(o.value) === encargadoActual);
                if (byId) {
                    select.value = byId.value;
                }
            }
        } catch (e) {
            // No bloquear apertura del modal.
        }
    }

    async function saveAdminAddProceso() {
        if (!isAdminBodega || !adminBadgeContext) return;

        const area = 'Corte';
        const selectEncargado = document.getElementById('procesoEncargadoSelect');
        const encargado = selectEncargado
            ? String(selectEncargado.options[selectEncargado.selectedIndex]?.text || '').trim().toUpperCase()
            : (procesoEncargadoInput?.value || '').trim().toUpperCase();

        if (!area) {
            showFeedbackModal('Validación', 'Selecciona un área para continuar.');
            return;
        }

        if (!encargado) {
            showFeedbackModal('Validación', 'Ingresa el encargado para continuar.');
            return;
        }

        if (confirmAddProcesoBtn) confirmAddProcesoBtn.disabled = true;
        try {
            const numeroRecibo = Number(adminBadgeContext.numero_recibo || 0);
            const prendaBodegaId = Number(adminBadgeContext.prenda_bodega_id || 0);
            if (!numeroRecibo) {
                throw new Error('No se encontró número de recibo para actualizar el proceso.');
            }

            const qs = prendaBodegaId > 0 ? `?prenda_bodega_id=${encodeURIComponent(String(prendaBodegaId))}` : '';
            const procesosResp = await fetch(`/api/recibos-bodega/${numeroRecibo}/procesos${qs}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            const procesos = await procesosResp.json().catch(() => []);
            if (!procesosResp.ok || !Array.isArray(procesos)) {
                throw new Error('No se pudieron consultar procesos del recibo en bodega.');
            }

            const procesoCorte = procesos.find((p) => String(p?.proceso || '').toLowerCase().includes('corte'))
                || procesos[0];
            if (!procesoCorte?.id) {
                throw new Error('No existe un proceso para este recibo en bodega.');
            }

            const editarResp = await fetch(`/api/recibos-bodega/procesos/${procesoCorte.id}/encargado`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    encargado,
                }),
            });

            const editarJson = await editarResp.json().catch(() => ({}));
            if (!editarResp.ok || editarJson?.success === false) {
                throw new Error(editarJson?.message || 'No se pudo actualizar el proceso de bodega.');
            }

            closeAdminAddProcesoModal();
            loadRecibosCorteForBodega();
            showFeedbackModal('Proceso actualizado', `Encargado asignado correctamente: ${encargado}`);
        } catch (error) {
            showFeedbackModal('Error', error.message || 'Error guardando proceso');
        } finally {
            if (confirmAddProcesoBtn) confirmAddProcesoBtn.disabled = false;
        }
    }

    if (addProcesoOverlay) addProcesoOverlay.addEventListener('click', closeAdminAddProcesoModal);
    if (closeAddProcesoBtn) closeAddProcesoBtn.addEventListener('click', closeAdminAddProcesoModal);
    if (cancelAddProcesoBtn) cancelAddProcesoBtn.addEventListener('click', closeAdminAddProcesoModal);
    if (confirmAddProcesoBtn) confirmAddProcesoBtn.addEventListener('click', saveAdminAddProceso);

    window.openAddProcesoFromBodegaBadge = function (payload) {
        if (!isAdminBodega) return;
        const data = payload || {};
        openAdminAddProcesoModal(data);
    };

    // Compatibilidad: mismo entrypoint usado en recibos-costura
    window.abrirModalAgregarProcesoDesdeArea = function (areaSeleccionada, pedidoId, prendaId, numeroRecibo) {
        window.openAddProcesoFromBodegaBadge({
            area: areaSeleccionada || 'Corte',
            pedido_produccion_id: pedidoId || null,
            prenda_id: prendaId || null,
            numero_recibo: numeroRecibo || null,
        });
    };

    const tipoTallaState = {
        isOpen: false,
        resolve: null,
        genero: null,
        tipoSeleccionado: null,
        modoCarga: 'normal',
        etapa: 'modo',
        detallesSeleccionados: [],
    };

    const LISTA_TALLAS_POR_TIPO = {
        letra: 'tallas-sugeridas-letra-list',
        numero: 'tallas-sugeridas-numero-list',
    };
    const TALLAS_POR_TIPO = {
        letra: ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL', 'XXXXL'],
        numero: ['4', '6', '8', '10', '12', '14', '16', '18', '20', '22', '24', '26', '28', '30', '32', '34', '36', '38', '40', '42', '44', '46', '48', '50'],
    };
    function toGeneroLabel(genero) {
        const key = String(genero || '').trim().toLowerCase();
        if (key === 'dama') return 'Dama';
        if (key === 'caballero') return 'Caballero';
        if (key === 'unisex') return 'Unisex';
        return 'Género';
    }

    function crearFilaDetalleModal(tipo, modo = 'normal', detalle = {}) {
        const row = document.createElement('div');
        row.className = `tipo-talla-row ${modo === 'normal' ? 'is-normal' : ''}`;
        const datalistId = LISTA_TALLAS_POR_TIPO[tipo] || 'tallas-sugeridas-list';
        if (modo === 'cantidad') {
            row.classList.add('is-normal');
            row.innerHTML = `
                <input type="number" class="modal-cantidad-input" min="1" placeholder="Cantidad" value="${detalle.cantidad || ''}">
                <button type="button" class="tipo-talla-remove">x</button>
            `;
        } else if (modo === 'normal') {
            row.innerHTML = `
                <input type="text" class="modal-talla-input" list="${datalistId}" placeholder="Talla" value="${detalle.talla || ''}">
                <input type="number" class="modal-cantidad-input" min="1" placeholder="Cantidad" value="${detalle.cantidad || ''}">
                <button type="button" class="tipo-talla-remove">x</button>
            `;
        } else {
            row.classList.add('is-color');
            row.innerHTML = `
                <input type="text" class="modal-talla-input" list="${datalistId}" placeholder="Talla" value="${detalle.talla || ''}">
                <input type="number" class="modal-cantidad-input" min="1" placeholder="Cantidad" value="${detalle.cantidad || ''}">
                <button type="button" class="tipo-talla-remove">x</button>
            `;
        }
        return row;
    }

    function textoModoSeleccionado() {
        if (tipoTallaState.modoCarga === 'cantidad') return 'Cantidad nada más';
        if (tipoTallaState.modoCarga === 'color') return 'Talla por color';
        return 'Normal';
    }

    function textoTipoSeleccionado() {
        return tipoTallaState.tipoSeleccionado === 'numero' ? 'Por número' : 'Por letra';
    }

    function setStepVisual(stepEl, isActive, isCompleted) {
        if (!stepEl) return;
        stepEl.classList.toggle('is-active', !!isActive);
        stepEl.classList.toggle('is-completed', !!isCompleted);
    }

    function actualizarUIWizard() {
        const etapa = tipoTallaState.etapa;
        if (tipoTallaModoActions) tipoTallaModoActions.style.display = etapa === 'modo' ? '' : 'none';
        if (tipoTallaTipoActions) tipoTallaTipoActions.style.display = etapa === 'tipo' ? '' : 'none';
        if (tipoTallaBackBtn) tipoTallaBackBtn.style.display = etapa === 'modo' ? 'none' : '';
        if (tipoTallaGrid) tipoTallaGrid.style.display = etapa === 'captura' ? '' : 'none';
        setStepVisual(tipoTallaStepModo, etapa === 'modo', etapa === 'tipo' || etapa === 'captura');
        setStepVisual(tipoTallaStepTipo, etapa === 'tipo', etapa === 'captura');
        setStepVisual(tipoTallaStepCaptura, etapa === 'captura', false);
        if (tipoTallaWizardSummary) {
            tipoTallaWizardSummary.textContent = etapa === 'captura'
                ? `${textoModoSeleccionado()} · ${textoTipoSeleccionado()}`
                : etapa === 'tipo'
                    ? `${textoModoSeleccionado()}`
                    : 'Selecciona un modo';
        }
        if (tipoTallaConfirmarBtn) {
            tipoTallaConfirmarBtn.disabled = etapa !== 'captura';
        }
    }

    function actualizarConfirmarModal() {
        if (!tipoTallaConfirmarBtn) return;
        if (!tipoTallaState.tipoSeleccionado) {
            tipoTallaConfirmarBtn.disabled = true;
            return;
        }
        const rows = tipoTallaGrid?.querySelectorAll('.tipo-talla-row') || [];
        let tieneAlMenosUnaValida = false;
        rows.forEach((row) => {
            const talla = (row.querySelector('.modal-talla-input')?.value || '').trim();
            const cantidad = parseInt(row.querySelector('.modal-cantidad-input')?.value || '0', 10);
            let esValida = tipoTallaState.modoCarga === 'normal'
                ? (talla !== '' && cantidad > 0)
                : tipoTallaState.modoCarga === 'cantidad'
                    ? (cantidad > 0)
                : false;
            if (tipoTallaState.modoCarga === 'color') {
                esValida = false;
            }
            if (esValida) {
                tieneAlMenosUnaValida = true;
            }
        });
        if (!tieneAlMenosUnaValida && tipoTallaState.modoCarga === 'color') {
            const blocks = tipoTallaGrid?.querySelectorAll('.tipo-talla-color-block') || [];
            blocks.forEach((block) => {
                const color = (block.querySelector('.modal-color-grupo-input')?.value || '').trim();
                const colorRows = block.querySelectorAll('.tipo-talla-row');
                colorRows.forEach((row) => {
                    const talla = (row.querySelector('.modal-talla-input')?.value || '').trim();
                    const cantidad = parseInt(row.querySelector('.modal-cantidad-input')?.value || '0', 10);
                    if (color !== '' && talla !== '' && cantidad > 0) {
                        tieneAlMenosUnaValida = true;
                    }
                });
            });
        }
        tipoTallaConfirmarBtn.disabled = !tieneAlMenosUnaValida;
    }

    function crearBloqueColorModal(tipo, color = '', detalles = []) {
        const block = document.createElement('div');
        block.className = 'tipo-talla-color-block';
        const tallasPillsHtml = (TALLAS_POR_TIPO[tipo] || []).map((talla) => {
            return `<button type="button" class="tipo-talla-pill tipo-talla-pill--in-block" data-color-block-pill="${talla}">${talla}</button>`;
        }).join('');
        block.innerHTML = `
            <div class="tipo-talla-color-head">
                <input type="text" class="modal-color-grupo-input" placeholder="Color (ej: AZUL REY)" value="${color}">
                <button type="button" class="tipo-talla-remove-color-block">x</button>
            </div>
            <div class="tipo-talla-block-pills">${tallasPillsHtml}</div>
            <div class="tipo-talla-color-rows"></div>
        `;
        const rowsWrap = block.querySelector('.tipo-talla-color-rows');
        const items = Array.isArray(detalles) && detalles.length > 0 ? detalles : [{}];
        items.forEach((detalle) => {
            if ((detalle?.talla || '').trim() !== '') {
                const row = crearFilaDetalleModal(tipo, 'color', detalle);
                const tallaInput = row.querySelector('.modal-talla-input');
                if (tallaInput) {
                    tallaInput.readOnly = true;
                    tallaInput.classList.add('is-readonly');
                }
                rowsWrap?.appendChild(row);
            }
        });
        const selected = new Set(
            Array.from(rowsWrap?.querySelectorAll('.modal-talla-input') || [])
                .map((input) => String(input.value || '').trim().toUpperCase())
                .filter(Boolean)
        );
        block.querySelectorAll('.tipo-talla-pill--in-block[data-color-block-pill]').forEach((pill) => {
            const talla = String(pill.dataset.colorBlockPill || '').trim().toUpperCase();
            pill.classList.toggle('is-selected', selected.has(talla));
        });
        return block;
    }

    function obtenerBloqueColorActivo() {
        const active = tipoTallaGrid?.querySelector('.tipo-talla-color-block.is-active');
        if (active) return active;
        return tipoTallaGrid?.querySelector('.tipo-talla-color-block') || null;
    }

    function activarBloqueColor(block) {
        tipoTallaGrid?.querySelectorAll('.tipo-talla-color-block').forEach((item) => {
            item.classList.toggle('is-active', item === block);
        });
        sincronizarEstadoPills();
    }

    function sincronizarEstadoPills() {
        const pills = tipoTallaGrid?.querySelectorAll('.tipo-talla-pill') || [];
        pills.forEach((pill) => pill.classList.remove('is-selected'));
        if (tipoTallaState.modoCarga === 'cantidad') return;

        if (tipoTallaState.modoCarga === 'color') {
            const activeBlock = obtenerBloqueColorActivo();
            const selected = new Set(
                Array.from(activeBlock?.querySelectorAll('.modal-talla-input') || [])
                    .map((input) => String(input.value || '').trim().toUpperCase())
                    .filter(Boolean)
            );
            pills.forEach((pill) => {
                const talla = String(pill.dataset.colorBlockPill || pill.textContent || '').trim().toUpperCase();
                if (selected.has(talla)) pill.classList.add('is-selected');
            });
            activeBlock?.querySelectorAll('.tipo-talla-pill--in-block[data-color-block-pill]').forEach((pill) => {
                const talla = String(pill.dataset.colorBlockPill || '').trim().toUpperCase();
                pill.classList.toggle('is-selected', selected.has(talla));
            });
            return;
        }

        const selected = new Set(
            Array.from(tipoTallaGrid?.querySelectorAll('.tipo-talla-row .modal-talla-input') || [])
                .map((input) => String(input.value || '').trim().toUpperCase())
                .filter(Boolean)
        );
        pills.forEach((pill) => {
            const talla = String(pill.textContent || '').trim().toUpperCase();
            if (selected.has(talla)) pill.classList.add('is-selected');
        });
    }

    function renderTallasDisponibles(tipo) {
        if (!tipoTallaGrid) return;
        tipoTallaGrid.innerHTML = '';

        if (tipoTallaState.modoCarga === 'color') {
            const groupsWrap = document.createElement('div');
            groupsWrap.className = 'tipo-talla-color-groups';
            const block = crearBloqueColorModal(tipo);
            groupsWrap.appendChild(block);
            tipoTallaGrid.appendChild(groupsWrap);
            activarBloqueColor(block);
        }

        if (tipoTallaState.modoCarga !== 'cantidad' && tipoTallaState.modoCarga !== 'color') {
            const pillsWrap = document.createElement('div');
            pillsWrap.className = 'tipo-talla-pills';
            (TALLAS_POR_TIPO[tipo] || []).forEach((talla) => {
                const pill = document.createElement('button');
                pill.type = 'button';
                pill.className = 'tipo-talla-pill';
                pill.textContent = talla;
                pill.addEventListener('click', function () {
                    pill.classList.add('is-picked');
                    setTimeout(() => pill.classList.remove('is-picked'), 220);
                    if (tipoTallaState.modoCarga === 'normal') {
                        const existe = Array.from(tipoTallaGrid.querySelectorAll('.tipo-talla-row .modal-talla-input'))
                            .some((input) => String(input.value || '').trim().toUpperCase() === talla.toUpperCase());
                        if (existe) {
                            return;
                        }
                    }
                    const addBtnRef = tipoTallaGrid.querySelector('.tipo-talla-add-btn');
                    const emptyRef = tipoTallaGrid.querySelector('.tipo-talla-empty');
                    if (emptyRef) emptyRef.remove();
                    const row = crearFilaDetalleModal(tipo, tipoTallaState.modoCarga, { talla });
                    if (addBtnRef) {
                        tipoTallaGrid.insertBefore(row, addBtnRef);
                    } else {
                        tipoTallaGrid.appendChild(row);
                    }
                    actualizarConfirmarModal();
                    sincronizarEstadoPills();
                });
                pillsWrap.appendChild(pill);
            });
            tipoTallaGrid.appendChild(pillsWrap);
            const emptyState = document.createElement('div');
            emptyState.className = 'tipo-talla-empty';
            emptyState.textContent = 'No hay tallas agregadas. Usa el botón para crear una fila.';
            tipoTallaGrid.appendChild(emptyState);
        } else {
            const emptyState = document.createElement('div');
            emptyState.className = 'tipo-talla-empty';
            emptyState.textContent = 'Agrega una cantidad para continuar.';
            tipoTallaGrid.appendChild(emptyState);
        }

        const addBtn = document.createElement('button');
        addBtn.type = 'button';
        addBtn.className = 'btn btn-outline-primary tipo-talla-add-btn';
        addBtn.textContent = tipoTallaState.modoCarga === 'cantidad'
            ? '+ Agregar cantidad'
            : tipoTallaState.modoCarga === 'color'
                ? '+ Agregar color'
                : '+ Agregar talla';
        addBtn.addEventListener('click', function () {
            const emptyRef = tipoTallaGrid.querySelector('.tipo-talla-empty');
            if (emptyRef) emptyRef.remove();
            if (tipoTallaState.modoCarga === 'color') {
                const groupsWrap = tipoTallaGrid.querySelector('.tipo-talla-color-groups');
                const block = crearBloqueColorModal(tipo);
                groupsWrap?.appendChild(block);
                activarBloqueColor(block);
                const colorInput = block.querySelector('.modal-color-grupo-input');
                colorInput?.focus();
            } else {
                tipoTallaGrid.insertBefore(crearFilaDetalleModal(tipo, tipoTallaState.modoCarga), addBtn);
            }
            actualizarConfirmarModal();
        });
        tipoTallaGrid.appendChild(addBtn);
        actualizarConfirmarModal();
        sincronizarEstadoPills();
    }

    function abrirModalTipoTalla(genero) {
        return new Promise((resolve) => {
            tipoTallaState.isOpen = true;
            tipoTallaState.resolve = resolve;
            tipoTallaState.genero = genero;
            tipoTallaState.tipoSeleccionado = null;
            tipoTallaState.modoCarga = 'normal';
            tipoTallaState.etapa = 'modo';
            tipoTallaState.detallesSeleccionados = [];
            tipoTallaModalText.textContent = `Configura tallas para ${toGeneroLabel(genero)}.`;
            if (tipoTallaGeneroBadge) {
                tipoTallaGeneroBadge.textContent = toGeneroLabel(genero);
            }

            tipoTallaModal.querySelectorAll('[data-modo-carga-select]').forEach((btn) => {
                const isActive = btn.dataset.modoCargaSelect === 'normal';
                btn.classList.toggle('btn-primary', isActive);
                btn.classList.toggle('btn-outline-primary', !isActive);
                if (btn.dataset.modoCargaSelect === 'cantidad') {
                    btn.style.display = genero === 'unisex' ? '' : 'none';
                }
            });

            tipoTallaModal.querySelectorAll('[data-tipo-talla-select]').forEach((btn) => {
                const isActive = btn.dataset.tipoTallaSelect === 'letra';
                btn.classList.toggle('btn-primary', isActive);
                btn.classList.toggle('btn-outline-primary', !isActive);
            });

            tipoTallaModal.querySelectorAll('[data-tipo-talla-select]').forEach((btn) => {
                btn.classList.toggle('btn-primary', false);
                btn.classList.toggle('btn-outline-primary', true);
            });

            if (tipoTallaGrid) {
                tipoTallaGrid.innerHTML = '';
            }
            actualizarUIWizard();
            tipoTallaModal.classList.remove('is-hidden');
            tipoTallaModal.setAttribute('aria-hidden', 'false');
        });
    }

    function cerrarModalTipoTalla(resultado) {
        if (!tipoTallaState.isOpen) return;
        tipoTallaModal.classList.add('is-hidden');
        tipoTallaModal.setAttribute('aria-hidden', 'true');
        tipoTallaState.isOpen = false;
        if (typeof tipoTallaState.resolve === 'function') {
            tipoTallaState.resolve(resultado || null);
        }
        tipoTallaState.resolve = null;
        tipoTallaState.genero = null;
        tipoTallaState.tipoSeleccionado = null;
        tipoTallaState.modoCarga = 'normal';
        tipoTallaState.etapa = 'modo';
        tipoTallaState.detallesSeleccionados = [];
    }

    function setTipoTallaEnSeccion(section, tipo) {
        if (!section) return;
        const datalistId = LISTA_TALLAS_POR_TIPO[tipo] || 'tallas-sugeridas-list';
        section.dataset.tipoTalla = tipo || '';
        section.querySelectorAll('input[name^="talla_"]').forEach((input) => {
            input.setAttribute('list', datalistId);
            input.placeholder = tipo === 'numero' ? '34' : 'M';
        });
    }

    function setModoCargaEnSeccion(section, modo) {
        if (!section) return;
        const modoNormalizado = modo === 'color' ? 'color' : (modo === 'cantidad' ? 'cantidad' : 'normal');
        section.dataset.modoCarga = modoNormalizado;
        section.classList.toggle('is-sin-color', modoNormalizado === 'normal');
        section.classList.toggle('is-cantidad-solo', modoNormalizado === 'cantidad');
    }

    function crearFilaTalla(prendaIndex, genero, listId, talla = '', incluirColor = true, soloCantidad = false) {
        const fila = document.createElement('div');
        if (soloCantidad) {
            fila.innerHTML = `
                <input type="hidden" name="talla_${genero}[${prendaIndex}][]" value="UNICA">
                <input type="number" name="cantidad_${genero}[${prendaIndex}][]" placeholder="Cantidad" min="1">
                <button type="button" class="eliminar-talla-btn">x</button>
            `;
        } else if (incluirColor) {
            fila.innerHTML = `
                <input type="text" name="talla_${genero}[${prendaIndex}][]" class="talla-input-uppercase" list="${listId}" placeholder="Talla" value="${talla}">
                <input type="text" name="color_${genero}[${prendaIndex}][]" class="color-input-uppercase" placeholder="Color (ej: ROJO)">
                <input type="number" name="cantidad_${genero}[${prendaIndex}][]" placeholder="Cantidad" min="1">
                <button type="button" class="eliminar-talla-btn">x</button>
            `;
        } else {
            fila.innerHTML = `
                <input type="text" name="talla_${genero}[${prendaIndex}][]" class="talla-input-uppercase" list="${listId}" placeholder="Talla" value="${talla}">
                <input type="number" name="cantidad_${genero}[${prendaIndex}][]" placeholder="Cantidad" min="1">
                <button type="button" class="eliminar-talla-btn">x</button>
            `;
        }
        return fila;
    }

    function aplicarTallasSeleccionadas(prendaCard, genero, tipo, detallesSeleccionados, modo = 'normal') {
        const prendaIndex = parseInt(prendaCard.dataset.prendaIndex || '0', 10);
        const list = prendaCard.querySelector(`.tallas-list-${genero}`);
        const section = prendaCard.querySelector(`[data-genero-section="${genero}"]`);
        if (!list) return;
        const listId = LISTA_TALLAS_POR_TIPO[tipo] || 'tallas-sugeridas-list';
        const incluirColor = modo === 'color';
        const soloCantidad = modo === 'cantidad';
        setModoCargaEnSeccion(section, modo);
        list.innerHTML = '';
        (detallesSeleccionados || []).forEach((detalle) => {
            const fila = crearFilaTalla(prendaIndex, genero, listId, detalle.talla || '', incluirColor, soloCantidad);
            const colorInput = fila.querySelector(`input[name="color_${genero}[${prendaIndex}][]"]`);
            const cantidadInput = fila.querySelector(`input[name="cantidad_${genero}[${prendaIndex}][]"]`);
            if (colorInput) colorInput.value = (detalle.color || '').toUpperCase();
            if (cantidadInput) cantidadInput.value = detalle.cantidad || '';
            list.appendChild(fila);
        });
        if ((detallesSeleccionados || []).length === 0) {
            list.appendChild(crearFilaTalla(prendaIndex, genero, listId, '', incluirColor, soloCantidad));
        }
    }

    let previousActiveElement = null;

    const closeModal = () => {
        modal.classList.remove('is-open');
        document.body.style.overflow = '';
        if (previousActiveElement) {
            previousActiveElement.focus();
            previousActiveElement = null;
        }
    };

    const openModal = () => {
        previousActiveElement = document.activeElement;
        modal.classList.add('is-open');
        document.body.style.overflow = 'hidden';
        const firstInput = form.querySelector('input, textarea, button[type="submit"]');
        if (firstInput) setTimeout(() => firstInput.focus(), 100);
    };

    openBtn?.addEventListener('click', openModal);

    modal.addEventListener('click', function (event) {
        if (event.target === modal || event.target.closest('[data-close-recibo-modal="true"]')) {
            closeModal();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && modal.classList.contains('is-open')) {
            closeModal();
        }
    });

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        console.log('[FORM] Submit iniciado');
        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn?.disabled) {
            return;
        }

        const formData = new FormData(form);
        const prendas = [];

        const prendaIndices = new Set();
        for (const [key, value] of formData.entries()) {
            const match = key.match(/^prenda\[(\d+)\]/);
            if (match) {
                prendaIndices.add(parseInt(match[1]));
            }
        }

        console.log('[FORM] indices de prendas encontrados:', Array.from(prendaIndices));

        prendaIndices.forEach(index => {
            const descripcion = formData.get(`prenda[${index}]`);
            const tallasDama = formData.getAll(`talla_dama[${index}][]`);
            const coloresDama = formData.getAll(`color_dama[${index}][]`);
            const cantidadesDama = formData.getAll(`cantidad_dama[${index}][]`);
            const tallasCab = formData.getAll(`talla_caballero[${index}][]`);
            const coloresCab = formData.getAll(`color_caballero[${index}][]`);
            const cantidadesCab = formData.getAll(`cantidad_caballero[${index}][]`);
            const tallasUni = formData.getAll(`talla_unisex[${index}][]`);
            const coloresUni = formData.getAll(`color_unisex[${index}][]`);
            const cantidadesUni = formData.getAll(`cantidad_unisex[${index}][]`);

            const tallasList = [];

            tallasDama.forEach((talla, i) => {
                const cantidad = parseInt(cantidadesDama[i]) || 0;
                const color = (coloresDama[i] || '').trim();
                if ((talla || '').trim() !== '' && cantidad > 0) {
                    tallasList.push({ talla, color, cantidad, genero: 'dama' });
                }
            });

            tallasCab.forEach((talla, i) => {
                const cantidad = parseInt(cantidadesCab[i]) || 0;
                const color = (coloresCab[i] || '').trim();
                if ((talla || '').trim() !== '' && cantidad > 0) {
                    tallasList.push({ talla, color, cantidad, genero: 'caballero' });
                }
            });

            tallasUni.forEach((talla, i) => {
                const cantidad = parseInt(cantidadesUni[i]) || 0;
                const color = (coloresUni[i] || '').trim();
                if ((talla || '').trim() !== '' && cantidad > 0) {
                    tallasList.push({ talla, color, cantidad, genero: 'unisex' });
                }
            });

            console.log(`[FORM] Prenda ${index}:`, { descripcion, tallasList });

            if (descripcion && tallasList.length > 0) {
                
                prendas.push({
                    descripcion: descripcion || null,
                    tallas: tallasList,
                });
            }
        });

        console.log('[FORM] Prendas procesadas:', prendas);

        if (prendas.length === 0) {
            alert('Por favor completa al menos una prenda con talla y cantidad');
            return;
        }

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        console.log('[FORM] CSRF Token:', csrfToken ? 'Presente' : 'NO ENCONTRADO');

        const payload = { prendas: prendas };
        console.log('[FORM] Enviando payload:', JSON.stringify(payload));

        const originalSubmitText = submitBtn ? submitBtn.innerHTML : '';
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = 'Guardando...';
        }

        fetch('/api/recibo-corte-bodega', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify(payload),
        })
        .then(async response => {
            console.log('[FETCH] Response status:', response.status, response.statusText);
            const raw = await response.text();
            let parsed = null;

            try {
                parsed = raw ? JSON.parse(raw) : null;
            } catch (_) {
                parsed = null;
            }

            if (!response.ok) {
                const backendMessage = parsed?.message || raw || `HTTP ${response.status}: ${response.statusText}`;
                throw new Error(backendMessage);
            }

            return parsed || {};
        })
        .then(data => {
            console.log('[FETCH] Response data:', data);
            if (data.success) {
                closeModal();
                form.reset();
                if (!data.duplicate) {
                    showReciboSuccessModal();
                    loadRecibosCorteForBodega();
                    if (data.prendas && data.prendas.length > 0) {
                        setTimeout(() => openReciboCorteBodegaModal(data.prendas[0].id), 500);
                    }
                }
            } else {
                alert('Error: ' + (data.message || 'No se pudo guardar el recibo'));
            }
        })
        .catch(error => {
            console.error('[FETCH] Error:', error);
            alert('Error al guardar el recibo: ' + error.message);
        })
        .finally(() => {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalSubmitText;
            }
        });
    });

    function bindPrendaActions(prendaCard) {
        const prendaIndex = parseInt(prendaCard.dataset.prendaIndex || '0', 10);
        const addDamaBtn = prendaCard.querySelector('.anadir-talla-dama-btn');
        const addCabBtn = prendaCard.querySelector('.anadir-talla-caballero-btn');
        const addUniBtn = prendaCard.querySelector('.anadir-talla-unisex-btn');
        const tallasDamaList = prendaCard.querySelector('.tallas-list-dama');
        const tallasCabList = prendaCard.querySelector('.tallas-list-caballero');
        const tallasUniList = prendaCard.querySelector('.tallas-list-unisex');

        if (addDamaBtn && tallasDamaList) {
            addDamaBtn.addEventListener('click', function () {
                const section = prendaCard.querySelector('[data-genero-section="dama"]');
                const listId = LISTA_TALLAS_POR_TIPO[section?.dataset?.tipoTalla] || 'tallas-sugeridas-list';
                const incluirColor = (section?.dataset?.modoCarga || 'normal') === 'color';
                tallasDamaList.appendChild(crearFilaTalla(prendaIndex, 'dama', listId, '', incluirColor));
            });
        }

        if (addCabBtn && tallasCabList) {
            addCabBtn.addEventListener('click', function () {
                const section = prendaCard.querySelector('[data-genero-section="caballero"]');
                const listId = LISTA_TALLAS_POR_TIPO[section?.dataset?.tipoTalla] || 'tallas-sugeridas-list';
                const incluirColor = (section?.dataset?.modoCarga || 'normal') === 'color';
                tallasCabList.appendChild(crearFilaTalla(prendaIndex, 'caballero', listId, '', incluirColor));
            });
        }

        if (addUniBtn && tallasUniList) {
            addUniBtn.addEventListener('click', function () {
                const section = prendaCard.querySelector('[data-genero-section="unisex"]');
                const listId = LISTA_TALLAS_POR_TIPO[section?.dataset?.tipoTalla] || 'tallas-sugeridas-list';
                const incluirColor = (section?.dataset?.modoCarga || 'normal') === 'color';
                tallasUniList.appendChild(crearFilaTalla(prendaIndex, 'unisex', listId, '', incluirColor));
            });
        }
    }

    prendasContainer.addEventListener('input', function (event) {
        const target = event.target;
        const isTextInput = target.matches('input[type="text"]');
        const isTextarea = target.matches('textarea');
        if (!isTextInput && !isTextarea) return;
        target.value = String(target.value || '').toUpperCase();
    });

    prendasContainer.addEventListener('change', async function (event) {
        const generoToggleInput = event.target.closest('.genero-check-input[data-genero-toggle]');
        if (generoToggleInput) {
            const prendaCard = generoToggleInput.closest('.prenda-card');
            if (!prendaCard) return;
            const genero = generoToggleInput.dataset.generoToggle;
            const section = prendaCard.querySelector(`[data-genero-section="${genero}"]`);
            if (!section) return;
            const label = generoToggleInput.closest('.genero-check');

            if (generoToggleInput.checked) {
                const seleccion = await abrirModalTipoTalla(genero);
                if (!seleccion || !seleccion.tipo || !Array.isArray(seleccion.detalles) || seleccion.detalles.length === 0) {
                    generoToggleInput.checked = false;
                    label?.classList.remove('is-active');
                    section.classList.add('is-hidden');
                    return;
                }
                setTipoTallaEnSeccion(section, seleccion.tipo);
                aplicarTallasSeleccionadas(prendaCard, genero, seleccion.tipo, seleccion.detalles, seleccion.modo);
                label?.classList.add('is-active');
                section.classList.remove('is-hidden');
            } else {
                label?.classList.remove('is-active');
                section.classList.add('is-hidden');
                section.dataset.tipoTalla = '';
                section.dataset.modoCarga = '';
                section.classList.remove('is-sin-color');
            }

            return;
        }
    });

    prendasContainer.addEventListener('click', function (event) {
        const generoToggleInput = event.target.closest('.genero-check-input[data-genero-toggle]');
        if (generoToggleInput) return;

        if (!event.target.classList.contains('eliminar-talla-btn')) return;
        const tallasList = event.target.closest('.tallas-list');
        if (!tallasList) return;
        event.target.closest('div')?.remove();

        const section = tallasList.closest('.tallas-subsection');
        if (!section) return;
        const rowsRestantes = tallasList.querySelectorAll(':scope > div');
        if (rowsRestantes.length > 0) return;

        section.classList.add('is-hidden');
        section.dataset.tipoTalla = '';
        section.dataset.modoCarga = '';
        section.classList.remove('is-sin-color');

        const prendaCard = section.closest('.prenda-card');
        const genero = section.dataset.generoSection;
        if (!prendaCard || !genero) return;

        const checkbox = prendaCard.querySelector(`.genero-check-input[data-genero-toggle="${genero}"]`);
        const label = checkbox?.closest('.genero-check');
        if (checkbox) checkbox.checked = false;
        label?.classList.remove('is-active');
    });

    prendasContainer.querySelectorAll('.prenda-card').forEach(bindPrendaActions);

    tipoTallaModal.querySelectorAll('[data-tipo-talla-select]').forEach((btn) => {
        btn.addEventListener('click', function () {
            tipoTallaState.tipoSeleccionado = btn.dataset.tipoTallaSelect;
            tipoTallaState.detallesSeleccionados = [];
            tipoTallaModal.querySelectorAll('[data-tipo-talla-select]').forEach((b) => {
                b.classList.toggle('btn-primary', b === btn);
                b.classList.toggle('btn-outline-primary', b !== btn);
            });
            renderTallasDisponibles(tipoTallaState.tipoSeleccionado);
            tipoTallaState.etapa = 'captura';
            actualizarUIWizard();
            if (tipoTallaConfirmarBtn) tipoTallaConfirmarBtn.disabled = true;
        });
    });

    tipoTallaModal.querySelectorAll('[data-modo-carga-select]').forEach((btn) => {
        btn.addEventListener('click', function () {
            tipoTallaState.modoCarga = btn.dataset.modoCargaSelect || 'normal';
            if (tipoTallaState.genero !== 'unisex' && tipoTallaState.modoCarga === 'cantidad') {
                tipoTallaState.modoCarga = 'normal';
            }
            tipoTallaModal.querySelectorAll('[data-modo-carga-select]').forEach((b) => {
                b.classList.toggle('btn-primary', b === btn);
                b.classList.toggle('btn-outline-primary', b !== btn);
            });
            if (tipoTallaState.modoCarga === 'cantidad') {
                tipoTallaState.tipoSeleccionado = 'letra';
                tipoTallaState.etapa = 'captura';
                renderTallasDisponibles(tipoTallaState.tipoSeleccionado);
                actualizarUIWizard();
                if (tipoTallaConfirmarBtn) tipoTallaConfirmarBtn.disabled = true;
                return;
            }

            tipoTallaState.tipoSeleccionado = null;
            tipoTallaState.etapa = 'tipo';
            if (tipoTallaGrid) tipoTallaGrid.innerHTML = '';
            actualizarUIWizard();
        });
    });

    tipoTallaBackBtn?.addEventListener('click', function () {
        if (tipoTallaState.etapa === 'captura') {
            tipoTallaState.tipoSeleccionado = null;
            tipoTallaState.etapa = 'tipo';
            if (tipoTallaGrid) tipoTallaGrid.innerHTML = '';
        } else if (tipoTallaState.etapa === 'tipo') {
            tipoTallaState.etapa = 'modo';
        }
        actualizarUIWizard();
    });

    tipoTallaGrid?.addEventListener('input', function () {
        const active = document.activeElement;
        if (active?.classList?.contains('modal-color-grupo-input')) {
            active.value = String(active.value || '').toUpperCase();
        }
        actualizarConfirmarModal();
        sincronizarEstadoPills();
    });

    tipoTallaGrid?.addEventListener('click', function (event) {
        const colorBlock = event.target.closest('.tipo-talla-color-block');
        if (colorBlock) {
            activarBloqueColor(colorBlock);
        }
        const colorPill = event.target.closest('.tipo-talla-pill--in-block[data-color-block-pill]');
        if (colorPill) {
            const block = colorPill.closest('.tipo-talla-color-block');
            const talla = (colorPill.dataset.colorBlockPill || '').trim().toUpperCase();
            const rowsWrap = block?.querySelector('.tipo-talla-color-rows');
            if (!block || !rowsWrap || !talla) return;
            activarBloqueColor(block);
            const existe = Array.from(rowsWrap.querySelectorAll('.modal-talla-input'))
                .some((input) => String(input.value || '').trim().toUpperCase() === talla);
            if (existe) return;
            const row = crearFilaDetalleModal(tipoTallaState.tipoSeleccionado, 'color', { talla, cantidad: 1 });
            const tallaInput = row.querySelector('.modal-talla-input');
            const cantidadInput = row.querySelector('.modal-cantidad-input');
            if (tallaInput) {
                tallaInput.readOnly = true;
                tallaInput.classList.add('is-readonly');
            }
            rowsWrap.appendChild(row);
            colorPill.classList.add('is-selected');
            if (cantidadInput) cantidadInput.focus();
            actualizarConfirmarModal();
            sincronizarEstadoPills();
            return;
        }
        const removeColorBlockBtn = event.target.closest('.tipo-talla-remove-color-block');
        if (removeColorBlockBtn) {
            const block = removeColorBlockBtn.closest('.tipo-talla-color-block');
            block?.remove();
            const firstBlock = tipoTallaGrid.querySelector('.tipo-talla-color-block');
            if (firstBlock) activarBloqueColor(firstBlock);
            actualizarConfirmarModal();
            sincronizarEstadoPills();
            return;
        }
        const removeBtn = event.target.closest('.tipo-talla-remove');
        if (!removeBtn) return;
        removeBtn.closest('.tipo-talla-row')?.remove();
        actualizarConfirmarModal();
        sincronizarEstadoPills();
    });

    tipoTallaConfirmarBtn?.addEventListener('click', function () {
        if (!tipoTallaState.tipoSeleccionado) return;
        const detalles = [];
        const tallasNormal = new Set();
        const rows = tipoTallaGrid?.querySelectorAll('.tipo-talla-row') || [];
        rows.forEach((row) => {
            const talla = (row.querySelector('.modal-talla-input')?.value || '').trim().toUpperCase();
            const cantidad = parseInt(row.querySelector('.modal-cantidad-input')?.value || '0', 10);
            if (tipoTallaState.modoCarga === 'normal') {
                if (talla !== '' && cantidad > 0) {
                    if (tallasNormal.has(talla)) {
                        return;
                    }
                    tallasNormal.add(talla);
                    detalles.push({ talla, color: '', cantidad });
                }
            } else if (tipoTallaState.modoCarga === 'cantidad') {
                if (cantidad > 0) {
                    detalles.push({ talla: 'UNICA', color: '', cantidad });
                }
            }
        });
        if (tipoTallaState.modoCarga === 'color') {
            const blocks = tipoTallaGrid?.querySelectorAll('.tipo-talla-color-block') || [];
            blocks.forEach((block) => {
                const color = (block.querySelector('.modal-color-grupo-input')?.value || '').trim().toUpperCase();
                if (!color) return;
                const tallasColor = new Set();
                block.querySelectorAll('.tipo-talla-row').forEach((row) => {
                    const talla = (row.querySelector('.modal-talla-input')?.value || '').trim().toUpperCase();
                    const cantidad = parseInt(row.querySelector('.modal-cantidad-input')?.value || '0', 10);
                    if (talla !== '' && cantidad > 0) {
                        const key = `${color}::${talla}`;
                        if (tallasColor.has(key)) return;
                        tallasColor.add(key);
                        detalles.push({ talla, color, cantidad });
                    }
                });
            });
        }
        if (tipoTallaState.modoCarga === 'normal') {
            const tallasCapturadas = rows
                ? Array.from(rows).map((row) => (row.querySelector('.modal-talla-input')?.value || '').trim().toUpperCase()).filter(Boolean)
                : [];
            const unicas = new Set(tallasCapturadas);
            if (tallasCapturadas.length !== unicas.size) {
                alert('En modo Normal no se permiten tallas repetidas.');
                return;
            }
        }
        if (detalles.length === 0) return;
        cerrarModalTipoTalla({
            tipo: tipoTallaState.tipoSeleccionado,
            modo: tipoTallaState.modoCarga,
            detalles,
        });
    });

    tipoTallaCancelarBtn?.addEventListener('click', function () {
        cerrarModalTipoTalla(null);
    });

    tipoTallaCloseBtn?.addEventListener('click', function () {
        cerrarModalTipoTalla(null);
    });

    tipoTallaModal.querySelector('.tipo-talla-modal__backdrop')?.addEventListener('click', function () {
        cerrarModalTipoTalla(null);
    });
});

document.addEventListener('DOMContentLoaded', function() {
    loadRecibosCorteForBodega();
});

const festivosReciboBodega = Array.isArray(recibosBodegaConfig.festivosReciboBodega)
    ? recibosBodegaConfig.festivosReciboBodega
    : [];
const festivosReciboBodegaSet = new Set(
    (festivosReciboBodega || [])
        .map((f) => String(f || '').slice(0, 10))
        .filter(Boolean)
);

function formatLocalYmd(date) {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
}

function parseFechaFlexible(value) {
    if (!value) return null;
    if (value instanceof Date && !Number.isNaN(value.getTime())) return value;

    const raw = String(value).trim();
    if (!raw) return null;

    const ddmmyyyy = raw.match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})/);
    if (ddmmyyyy) {
        const day = Number(ddmmyyyy[1]);
        const month = Number(ddmmyyyy[2]);
        const year = Number(ddmmyyyy[3]);
        const parsed = new Date(year, month - 1, day);
        return Number.isNaN(parsed.getTime()) ? null : parsed;
    }

    const parsed = new Date(raw);
    return Number.isNaN(parsed.getTime()) ? null : parsed;
}

function esDiaHabil(date) {
    const day = date.getDay();
    if (day === 0 || day === 6) return false;
    return !festivosReciboBodegaSet.has(formatLocalYmd(date));
}

function calcularDiasHabilesDesdeSiguienteHabil(fechaInicio, fechaFin = new Date()) {
    const inicio = parseFechaFlexible(fechaInicio);
    const fin = parseFechaFlexible(fechaFin);
    if (!inicio || !fin) return 0;

    const start = new Date(inicio.getFullYear(), inicio.getMonth(), inicio.getDate());
    const end = new Date(fin.getFullYear(), fin.getMonth(), fin.getDate());
    if (start > end) return 0;

    const cursor = new Date(start);
    cursor.setDate(cursor.getDate() + 1);

    while (cursor <= end && !esDiaHabil(cursor)) {
        cursor.setDate(cursor.getDate() + 1);
    }

    let diasHabiles = 0;
    while (cursor <= end) {
        if (esDiaHabil(cursor)) diasHabiles++;
        cursor.setDate(cursor.getDate() + 1);
    }

    return diasHabiles;
}

function loadRecibosCorteForBodega() {
    fetch('/api/recibo-corte-bodega')
        .then(response => response.json())
        .then(data => {
            const tbody = document.getElementById('recibo-corte-bodega-tbody');
            tbody.innerHTML = '';

            if (data.success && data.data && data.data.length > 0) {
                data.data.forEach(prenda => {
                    const canAssignByBadge = Boolean(
                        isAdminBodega &&
                        prenda &&
                        String(prenda.area || '').toLowerCase().includes('corte')
                    );
                    const fechaBaseCalculo = prenda.created_at || prenda.fecha_creacion || prenda.fecha_corta;
                    const diasHabilesTranscurridos = calcularDiasHabilesDesdeSiguienteHabil(fechaBaseCalculo, new Date());
                    const estaAtrasado = diasHabilesTranscurridos > 3;
                    const row = document.createElement('tr');
                    if (estaAtrasado) {
                        row.classList.add('recibo-atrasado-row');
                        row.title = `Atrasado: ${diasHabilesTranscurridos} dias habiles transcurridos`;
                    }
                    row.innerHTML = `
                        <td class="acciones-column" style="text-align: center; position: relative;">
                            <button type="button"
                                class="btn-ver-dropdown-bodega"
                                title="Ver Opciones"
                                data-menu-id="menu-recibo-bodega-${prenda.id}"
                                data-pedido-id="${prenda.pedido_produccion_id || ''}"
                                data-prenda-id="${prenda.prenda_id || ''}"
                                data-numero-recibo="${prenda.numero_recibo || ''}"
                                data-tipo-recibo="CORTE-PARA-BODEGA"
                                data-es-parcial="false"
                                data-pedido-parcial-id=""
                                data-recibo-id="${prenda.id}"
                                data-prenda-bodega-id="${prenda.id}"
                                data-tiene-parciales="false"
                                data-total-parciales="0">
                                <i class="fas fa-eye"></i>
                            </button>
                        </td>
                        <td style="text-align: center;">
                            <span class="badge rounded-pill bg-info text-dark"
                                  style="${canAssignByBadge ? 'cursor:pointer;' : ''}"
                                  title="${canAssignByBadge ? 'Click para asignar proceso' : ''}"
                                  ${canAssignByBadge ? `onclick="openAddProcesoFromBodegaBadge({ area: '${String(prenda.area || 'Corte').replace(/'/g, "\\'")}', numero_recibo: ${prenda.numero_recibo || 'null'}, pedido_produccion_id: ${prenda.pedido_produccion_id || 'null'}, prenda_id: ${prenda.prenda_id || 'null'}, prenda_bodega_id: ${prenda.id || 'null'} })"` : ''}>${prenda.area || '-'}</span>
                        </td>
                        <td style="text-align: center;"><strong>${prenda.numero_recibo || '-'}</strong></td>
                        <td>${prenda.descripcion || '-'}</td>
                        <td style="text-align: center;">${prenda.cantidad_tallas}</td>
                        <td style="text-align: center;"><span class="badge bg-success">${prenda.total_cantidad}</span></td>
                        <td style="text-align: center;">${prenda.fecha_corta}</td>
                        <td style="text-align: center;">${prenda.encargado || '-'}</td>
                    `;
                    tbody.appendChild(row);
                });
            } else {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="8" class="text-center py-4">
                            <div class="alert alert-info mb-0">
                                <i class="fas fa-info-circle"></i> No hay recibos de corte para bodega registrados aún.
                            </div>
                        </td>
                    </tr>
                `;
            }
        })
        .catch(error => {
            console.error('Error cargando recibos:', error);
            const tbody = document.getElementById('recibo-corte-bodega-tbody');
            tbody.innerHTML = `
                <tr>
                    <td colspan="8" class="text-center py-4">
                        <div class="alert alert-danger mb-0">
                            Error al cargar los recibos de corte para bodega.
                        </div>
                    </td>
                </tr>
            `;
        });
}

function closeReciboBodegaDropdowns() {
    document.querySelectorAll('.dropdown-menu-recibos').forEach((m) => m.remove());
    document.querySelectorAll('.btn-ver-dropdown-bodega.dropdown-opening').forEach((b) => b.classList.remove('dropdown-opening'));
}

function openReciboBodegaDropdown(button) {
    if (!button) return;

    const existing = document.getElementById(button.getAttribute('data-menu-id'));
    if (existing) {
        closeReciboBodegaDropdowns();
        return;
    }

    closeReciboBodegaDropdowns();
    button.classList.add('dropdown-opening');

    const menuId = button.getAttribute('data-menu-id') || `menu-recibo-bodega-${Date.now()}`;
    const reciboId = Number(button.getAttribute('data-recibo-id') || 0);
    const pedidoId = String(button.getAttribute('data-pedido-id') || '').trim();
    const numeroRecibo = Number(button.getAttribute('data-numero-recibo') || 0);
    const prendaBodegaId = Number(button.getAttribute('data-prenda-bodega-id') || 0);

    const dropdown = document.createElement('div');
    dropdown.id = menuId;
    dropdown.className = 'dropdown-menu-recibos';
    dropdown.style.display = 'block';
    dropdown.style.pointerEvents = 'auto';
    dropdown.innerHTML = `
        <button class="dropdown-item-btn" type="button" data-action="ver-detalles">
            <i class="fas fa-eye"></i> Ver Detalles
        </button>
        <div class="dropdown-divider"></div>
        <button class="dropdown-item-btn" type="button" data-action="ver-distribucion">
            <i class="fas fa-share"></i> Ver Distribución
        </button>
        <div class="dropdown-divider"></div>
        <button class="dropdown-item-btn" type="button" data-action="seguimiento">
            <i class="fas fa-tasks"></i> Seguimiento
        </button>
    `;

    dropdown.addEventListener('click', async function (event) {
        const actionBtn = event.target.closest('.dropdown-item-btn');
        if (!actionBtn) return;
        const action = actionBtn.getAttribute('data-action');

        if (action === 'ver-detalles') {
            if (reciboId > 0) {
                openReciboCorteBodegaModal(reciboId);
            }
            closeReciboBodegaDropdowns();
            return;
        }

        if (action === 'ver-distribucion') {
            if (numeroRecibo > 0) {
                await openReciboBodegaDistribucion(numeroRecibo, prendaBodegaId);
            }
            closeReciboBodegaDropdowns();
            return;
        }

        if (action === 'seguimiento') {
            await openReciboBodegaSeguimientoInterno(numeroRecibo, prendaBodegaId);
            closeReciboBodegaDropdowns();
        }
    });

    document.body.appendChild(dropdown);
    const rect = button.getBoundingClientRect();
    dropdown.style.top = `${window.scrollY + rect.bottom + 8}px`;
    dropdown.style.left = `${window.scrollX + rect.left - 8}px`;
}

async function openReciboBodegaSeguimientoInterno(numeroRecibo, prendaBodegaId) {
    const numero = Number(numeroRecibo || 0);
    const prendaBodega = Number(prendaBodegaId || 0);

    if (numero <= 0) {
        alert('Este recibo no tiene número de recibo válido.');
        return;
    }

    try {
        const qs = prendaBodega > 0 ? `?prenda_bodega_id=${encodeURIComponent(String(prendaBodega))}` : '';
        const resp = await fetch(`/api/recibos-bodega/${numero}/procesos${qs}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });
        const procesos = await resp.json().catch(() => []);

        if (!resp.ok || !Array.isArray(procesos)) {
            throw new Error('No se pudieron cargar los procesos del recibo interno.');
        }

        const modal = document.getElementById('orderTrackingModal');
        const overlay = document.getElementById('trackingModalOverlay');
        const closeBtn = document.getElementById('closeTrackingModal');
        const reciboEl = document.getElementById('trackingOrderRecibo');
        const pedidoEl = document.getElementById('trackingOrderNumber');
        const clienteEl = document.getElementById('trackingOrderClient');
        const estadoEl = document.getElementById('trackingOrderStatus');
        const fechaEstimadaEl = document.getElementById('trackingEstimatedDate');
        const subtitleEl = document.getElementById('trackingPrendaReciboHeader');
        const timelineContainer = document.getElementById('trackingTimelineContainer');
        const timelineSection = document.getElementById('trackingTimelineSection');

        if (!modal || !timelineContainer) {
            throw new Error('Modal de seguimiento unificado no disponible.');
        }

        if (reciboEl) reciboEl.textContent = String(numero);
        if (pedidoEl) pedidoEl.textContent = '-';
        if (clienteEl) clienteEl.textContent = 'RECIBO INTERNO BODEGA';
        if (estadoEl) estadoEl.textContent = 'EN EJECUCIÓN';
        if (fechaEstimadaEl) fechaEstimadaEl.textContent = 'No definida';
        if (subtitleEl) subtitleEl.textContent = `CORTE-PARA-BODEGA #${numero}`;

        const cards = procesos.length
            ? procesos.map((p) => {
                const estado = String(p?.estado_proceso || 'Pendiente');
                const pendingClass = estado.toLowerCase() === 'completado' ? 'completed' : 'pending';
                const inicio = p?.fecha_inicio ? String(p.fecha_inicio).slice(0, 10) : '---';
                const fin = p?.fecha_fin ? String(p.fecha_fin).slice(0, 10) : '---';
                return `
                <div class="tracking-area-card tracking-area-card-v2 ${pendingClass}">
                    <div class="tracking-area-v2-left">
                        <div class="tracking-area-v2-name">${p?.proceso || '-'}</div>
                    </div>
                    <div class="tracking-area-v2-body">
                        <div class="tracking-area-v2-row">
                            <div class="tracking-area-v2-field">
                                <div class="tracking-area-v2-label">Encargado:</div>
                                <div class="tracking-area-v2-pill">${p?.encargado || '-'}</div>
                            </div>
                            <div class="tracking-area-v2-field">
                                <div class="tracking-area-v2-label">Fecha inicio:</div>
                                <div class="tracking-area-v2-pill">${inicio}</div>
                            </div>
                            <div class="tracking-area-v2-field tracking-area-v2-field-right">
                                <div class="tracking-area-v2-label">Fecha fin:</div>
                                <div class="tracking-area-v2-badge">${fin}</div>
                            </div>
                        </div>
                        <div class="tracking-area-v2-footer">
                            <div class="tracking-area-v2-status">
                                <span class="tracking-days-badge">${estado}</span>
                            </div>
                        </div>
                    </div>
                </div>`;
            }).join('')
            : `<div style="padding: 1rem; color:#64748b;">Sin procesos registrados para este recibo.</div>`;

        timelineContainer.innerHTML = `
            <div class="tracking-section tracking-section-areas">
                <div class="tracking-section-header">
                    <div class="tracking-section-title">Seguimiento por áreas:</div>
                </div>
                ${cards}
            </div>
        `;
        if (timelineSection) timelineSection.style.display = 'block';

        const closeUnified = () => {
            modal.classList.remove('show');
            modal.style.display = 'none';
            modal.style.visibility = 'hidden';
            modal.style.opacity = '0';
        };

        if (overlay && overlay.dataset.bodegaCloseBound !== '1') {
            overlay.dataset.bodegaCloseBound = '1';
            overlay.addEventListener('click', closeUnified);
        }
        if (closeBtn && closeBtn.dataset.bodegaCloseBound !== '1') {
            closeBtn.dataset.bodegaCloseBound = '1';
            closeBtn.addEventListener('click', closeUnified);
        }

        modal.classList.add('show');
        modal.style.display = 'flex';
        modal.style.visibility = 'visible';
        modal.style.opacity = '1';
    } catch (error) {
        alert(error.message || 'Error cargando seguimiento interno de bodega.');
    }
}

async function openReciboBodegaDistribucion(numeroRecibo, prendaBodegaId) {
    const numero = Number(numeroRecibo || 0);
    const prendaBodega = Number(prendaBodegaId || 0);

    if (numero <= 0) {
        alert('Este recibo no tiene número de recibo válido.');
        return;
    }

    try {
        const qs = prendaBodega > 0 ? `?prenda_bodega_id=${encodeURIComponent(String(prendaBodega))}` : '';
        const resp = await fetch(`/api/recibos-bodega/${numero}/distribucion${qs}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });
        const data = await resp.json().catch(() => ({}));

        if (!resp.ok) {
            throw new Error(data.message || 'No se pudieron cargar los datos de distribución.');
        }

        const parciales = data.parciales || [];
        const totalParciales = data.total_parciales || 0;
        const areaActual = String(data?.recibo?.area_actual || 'Sin asignar');
        const totalUnidades = Number(data?.recibo?.total_unidades || 0);

        if (totalParciales === 0) {
            alert('No hay parciales creados para este recibo #' + numero);
            return;
        }

        // Usar el modal específico de distribución
        const modal = document.getElementById('recibo-distribution-modal');
        const backdrop = modal?.querySelector('.distribution-modal__backdrop');
        const closeBtn = modal?.querySelector('.distribution-modal__close');
        const titleEl = modal?.querySelector('#distributionModalTitle');
        const bodyEl = modal?.querySelector('#distributionModalBody');

        if (!modal || !bodyEl) {
            throw new Error('Modal de distribución no disponible.');
        }

        // Actualizar título
        if (titleEl) titleEl.textContent = `Distribucion del recibo #${numero}`;

        // Calcular cantidad total
        const totalCantidad = parciales.reduce((sum, p) => {
            // Aquí podrías sumar cantidades si están disponibles en los datos
            return sum;
        }, 0);

        // Construir HTML del modal
        const parcialesHTML = parciales.length
            ? parciales.map((p, idx) => {
                const estado = String(p?.estado_proceso || 'Pendiente');
                const estadoNormalizado = estado.trim().toLowerCase();
                const estadoPillClass = estadoNormalizado === 'anulado'
                    ? 'distribution-pill--red'
                    : 'distribution-pill--slate';
                const numeroReciboParcial = p?.numero_recibo_parcial || (idx + 1);
                const parcialLabel = String(numeroReciboParcial).includes('.')
                    ? String(numeroReciboParcial)
                    : `${numero}.${numeroReciboParcial}`;
                const tallas = Array.isArray(p?.tallas) ? p.tallas : [];
                const tallasHTML = tallas.length
                    ? `<div class="distribution-sizes">${tallas.map((t) => {
                        const tallaNombre = String(t?.talla || '-').toUpperCase();
                        const qty = Number(t?.cantidad || 0);
                        return `<span class="distribution-size-chip">${tallaNombre} <strong>x${qty}</strong></span>`;
                    }).join('')}</div>`
                    : '<span class="distribution-pill">Sin tallas</span>';
                
                return `
                <article class="distribution-card">
                    <div class="distribution-card__inner">
                        <div class="distribution-card__top">
                            <div class="distribution-card__title">
                                <h3>Parcial #${parcialLabel}</h3>
                                <span class="distribution-pill ${estadoPillClass}">${estado}</span>
                            </div>
                            <span class="distribution-pill distribution-pill--green">${p?.proceso || 'Sin asignar'}</span>
                        </div>
                        <div class="distribution-card__meta">
                            <div class="distribution-card__row">
                                <span class="distribution-card__row-label">Encargado</span>
                                <span class="distribution-pill distribution-pill--blue">${p?.encargado || 'SIN ASIGNAR'}</span>
                            </div>
                            <div class="distribution-card__row">
                                <span class="distribution-card__row-label">Fechas</span>
                                <span class="distribution-pill">${p?.fecha_inicio ? String(p.fecha_inicio).slice(0, 10) : '---'} a ${p?.fecha_fin ? String(p.fecha_fin).slice(0, 10) : '---'}</span>
                            </div>
                            <div class="distribution-card__row">
                                <span class="distribution-card__row-label">Tallas</span>
                                ${tallasHTML}
                            </div>
                        </div>
                        <div class="distribution-card__actions">
                            <button type="button" class="distribution-action-btn" onclick="openReciboBodegaParcialSeguimiento(${Number(p?.id || 0)}, ${numero}, ${prendaBodega})">
                                <i class="fas fa-route"></i>
                                Ver seguimiento
                            </button>
                        </div>
                    </div>
                </article>`;
            }).join('')
            : `<div style="padding: 2rem; text-align: center; color: #64748b;">Sin parciales registrados para este recibo.</div>`;

        bodyEl.innerHTML = `
            <div class="distribution-summary">
                <div class="distribution-summary__card">
                    <span class="distribution-summary__label">Recibo</span>
                    <span class="distribution-summary__value">#${numero}</span>
                </div>
                <div class="distribution-summary__card">
                    <span class="distribution-summary__label">Area actual</span>
                    <span class="distribution-summary__value">${areaActual}</span>
                </div>
                <div class="distribution-summary__card">
                    <span class="distribution-summary__label">Resumen</span>
                    <span class="distribution-summary__value">${totalParciales} parciales - ${totalUnidades} und</span>
                </div>
            </div>
            <div class="distribution-list">
                ${parcialesHTML}
            </div>
        `;

        // Configurar eventos de cierre
        const closeModal = () => {
            if (modal?.contains(document.activeElement)) {
                document.activeElement.blur();
                const table = document.getElementById('recibo-corte-bodega-table');
                if (table) table.focus?.();
            }
            modal.setAttribute('aria-hidden', 'true');
            modal.style.display = 'none';
        };

        if (backdrop) {
            backdrop.onclick = closeModal;
        }
        if (closeBtn) {
            closeBtn.onclick = closeModal;
        }

        // Mostrar modal
        modal.setAttribute('aria-hidden', 'false');
        modal.style.display = 'flex';
        if (closeBtn) {
            setTimeout(() => closeBtn.focus?.(), 0);
        }
    } catch (error) {
        alert(error.message || 'Error cargando distribución de bodega.');
    }
}

function openReciboBodegaParcialSeguimiento(parcialId, numeroRecibo, prendaBodegaId) {
    const id = Number(parcialId || 0);
    if (id > 0 && typeof window.openSeguimientoParcialModal === 'function') {
        window.openSeguimientoParcialModal(id);
        return;
    }

    alert('No está disponible el modal de seguimiento por parcial en esta vista.');
}

function escapeDistributionHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function formatParcialConsecutivo(value) {
    if (value === null || value === undefined || value === '') return '-';
    const raw = String(value);
    if (raw.includes('.')) {
        return raw.replace(/\.0+$/, '').replace(/(\.\d*[1-9])0+$/, '$1');
    }
    return raw;
}

function buildPartialTrackingModalContent(parcial, timeline) {
    const tallas = Array.isArray(parcial.tallas) ? parcial.tallas : [];
    const tallasHtml = tallas.length > 0
        ? `
            <div class="partial-tracking-sizes" aria-label="Tallas del parcial">
                ${tallas.map((talla) => {
                    const tallaNombre = escapeDistributionHtml(talla.talla ?? 'N/A');
                    const cantidad = parseInt(talla.cantidad, 10) || 0;
                    const color = talla.color_nombre ? ` <span style="opacity:.75;">${escapeDistributionHtml(talla.color_nombre)}</span>` : '';
                    return `<span class="partial-tracking-size-chip">${tallaNombre} <strong>x${cantidad}</strong>${color}</span>`;
                }).join('')}
            </div>
        `
        : '<span class="partial-tracking-muted">Sin tallas registradas</span>';

    const steps = (Array.isArray(timeline) ? timeline : []).map((step) => {
        const isCompleted = Boolean(step.completado);
        const estadoLabel = isCompleted ? 'Completado' : (step.estado || 'En progreso');
        const estadoIcon = isCompleted ? 'fa-check-circle' : 'fa-signal';
        const fechaInicio = step.fecha_inicio ? `<span><strong>Inicio:</strong> ${escapeDistributionHtml(step.fecha_inicio)}</span>` : '';
        const fechaFin = step.fecha_fin ? `<span><strong>Fin:</strong> ${escapeDistributionHtml(step.fecha_fin)}</span>` : '';

        return `
            <article class="partial-tracking-step">
                <div class="partial-tracking-step__top">
                    <div class="partial-tracking-step__title">
                        <h3>${escapeDistributionHtml(step.area || 'Sin area')}</h3>
                        <div class="partial-tracking-step__meta">
                            <span class="partial-tracking-badge partial-tracking-badge--blue">
                                <i class="fas fa-user"></i>
                                ${escapeDistributionHtml(step.encargado || 'Sin asignar')}
                            </span>
                            <span class="partial-tracking-badge partial-tracking-badge--green">
                                <i class="fas ${estadoIcon}"></i>
                                ${escapeDistributionHtml(estadoLabel)}
                            </span>
                        </div>
                    </div>
                    <span class="partial-tracking-badge partial-tracking-badge--slate">Paso ${escapeDistributionHtml(step.orden || '-')}</span>
                </div>
                <div class="partial-tracking-step__dates">${fechaInicio}${fechaFin}</div>
            </article>
        `;
    }).join('');

    return `
        <div class="partial-tracking-summary">
            <div class="partial-tracking-summary__card">
                <span class="partial-tracking-summary__label">Parcial</span>
                <span class="partial-tracking-summary__value">#${escapeDistributionHtml(formatParcialConsecutivo(parcial.consecutivo_parcial ?? '-'))}</span>
            </div>
            <div class="partial-tracking-summary__card">
                <span class="partial-tracking-summary__label">Area actual</span>
                <span class="partial-tracking-summary__value">${escapeDistributionHtml(parcial.area_actual ?? 'Sin area')}</span>
            </div>
            <div class="partial-tracking-summary__card">
                <span class="partial-tracking-summary__label">Tallas</span>
                <div class="partial-tracking-summary__value">${tallasHtml}</div>
            </div>
        </div>
        <div class="partial-tracking-timeline">
            ${steps || '<div class="partial-tracking-empty"><p style="margin:0;">Este parcial aún no tiene recorrido registrado.</p></div>'}
        </div>
    `;
}

window.openSeguimientoParcialModal = async function (parcialId) {
    const modal = document.getElementById('partial-tracking-modal');
    const body = document.getElementById('partialTrackingModalBody');
    const title = document.getElementById('partialTrackingModalTitle');
    const id = Number(parcialId || 0);

    if (!modal || !body || !title || id <= 0) {
        alert('No se pudo abrir el seguimiento del parcial.');
        return;
    }

    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    title.textContent = 'Recorrido del parcial';
    body.innerHTML = `<div class="distribution-loading"><span class="distribution-spinner"></span><span>Cargando seguimiento del parcial...</span></div>`;

    try {
        const response = await fetch(`/api/recibos-costura/parciales/${encodeURIComponent(String(id))}/seguimiento`, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        });
        const result = await response.json().catch(() => ({}));
        if (!response.ok || !result.success) {
            throw new Error(result.message || 'No se pudo cargar el seguimiento del parcial');
        }

        const parcial = result.parcial || {};
        const timeline = Array.isArray(result.timeline) ? result.timeline : [];
        title.textContent = `Seguimiento del parcial #${escapeDistributionHtml(formatParcialConsecutivo(parcial.consecutivo_parcial ?? id))}`;
        body.innerHTML = buildPartialTrackingModalContent(parcial, timeline);
    } catch (error) {
        body.innerHTML = `<div class="partial-tracking-empty"><p style="margin:0;">${escapeDistributionHtml(error.message || 'Error cargando seguimiento')}</p></div>`;
    }
};

window.closeSeguimientoParcialModal = function () {
    const modal = document.getElementById('partial-tracking-modal');
    if (!modal) return;
    if (modal.contains(document.activeElement)) {
        document.activeElement.blur();
        const table = document.getElementById('recibo-corte-bodega-table');
        if (table) table.focus?.();
    }
    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');
    modal.style.display = 'none';
    document.body.style.overflow = '';
};

document.addEventListener('click', function (event) {
    const modal = document.getElementById('partial-tracking-modal');
    if (!modal) return;
    const shouldClose = event.target.closest('[data-partial-tracking-close="true"]');
    if (shouldClose || event.target === modal) {
        window.closeSeguimientoParcialModal();
    }
});


document.addEventListener('click', function (event) {
    const btnVer = event.target.closest('.btn-ver-dropdown-bodega');
    const inMenu = event.target.closest('.dropdown-menu-recibos');

    if (btnVer) {
        event.preventDefault();
        event.stopPropagation();
        openReciboBodegaDropdown(btnVer);
        return;
    }

    if (!inMenu) {
        closeReciboBodegaDropdowns();
    }
});

