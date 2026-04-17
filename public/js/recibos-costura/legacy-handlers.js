/**
 * LEGACY HANDLERS - Puente de Compatibilidad
 * 
 * Archivo minimalista que proporciona wrappers globales para delegar
 * funcionalidad a módulos modernos (bundle.js, servicios, controladores)
 * 
 * Este archivo es necesario porque:
 * - El HTML tiene llamadas onclick="openFilterModal()" que necesitan funciones globales
 * - Los módulos están encapsulados en bundle.js
 * - Este archivo actúa como puente entre HTML y módulos
 */

// ========== WRAPPERS DE FILTERMODULE ==========
Object.defineProperty(window, 'activeFilters', {
    get: () => FilterModule.getInstance().getActiveFilters(),
    configurable: true
});

window.openFilterModal = (filterType) => FilterModule.getInstance().openFilterModal(filterType);
window.closeFilterModal = () => FilterModule.getInstance().closeFilterModal();
window.resetFilters = () => FilterModule.getInstance().resetFilters();
window.applyFilters = () => FilterModule.getInstance().applyFilters();
window.selectAllCheckboxFilters = (filterType) => FilterModule.getInstance().selectAllCheckboxes(filterType);
window.filterCheckboxOptions = (filterType) => FilterModule.getInstance().filterCheckboxOptions(filterType);

function loadFilterOptions(filterType) {
    return FilterModule.getInstance().loadFilterOptions(filterType);
}

function getDynamicFilterOptions(filterType) {
    return FilterModule.getInstance().getDynamicFilterOptions(filterType);
}

function getColumnIndex(filterType) {
    return FilterModule.getInstance().getColumnIndex(filterType);
}

// ========== WRAPPERS DE TRACKINGMODALCONTROLLER ==========
function verDetallesRecibo(reciboId) {
    return TrackingModalController.getInstance().viewDetails(reciboId);
}

function abrirModalSeguimiento(
    pedidoId,
    prendaIdTarget,
    numeroRecibo = null,
    reciboId = null,
    esParcial = false,
    pedidoParcialId = null
) {
    return TrackingModalController.getInstance().open(
        pedidoId,
        prendaIdTarget,
        {
            numeroRecibo,
            reciboId,
            esParcial,
            pedidoParcialId
        }
    );
}

function abrirModalSeguimientoDirecto(pedidoId, prendaIdTarget) {
    return TrackingModalController.getInstance()._openTrackingModal(pedidoId, prendaIdTarget);
}

// ========== WRAPPERS DE ADDPROCESSMODALCONTROLLER ==========
window.abrirModalAgregarProcesoDesdeArea = (areaSeleccionada, pedidoId, prendaId, numeroRecibo) =>
    AddProcessModalController.getInstance().openFromBadge(areaSeleccionada, pedidoId, prendaId, numeroRecibo);

function verificarDatosAntesDeGuardar(event) {
    return AddProcessModalController.getInstance().verifyAndSave(event);
}

async function handleAgregarProcesoDesdeBadge() {
    return AddProcessModalController.getInstance().save();
}

function limpiarFormularioProceso() {
    return AddProcessModalController.getInstance().clearForm();
}

// ========== WRAPPERS DE TOASTNOTIFICATIONSERVICE ==========
function showSuccess(message, title = 'Éxito') {
    return ToastNotificationService.getInstance().success(message, title);
}

function showError(message, title = 'Error') {
    return ToastNotificationService.getInstance().error(message, title);
}

function showToast(message, type = 'info', title = '') {
    return ToastNotificationService.getInstance().show(message, type, title);
}

function removeToast(toastId) {
    return ToastNotificationService.getInstance().remove(toastId);
}

function clearAllToasts() {
    return ToastNotificationService.getInstance().clearAll();
}

// ========== WRAPPERS DE DROPDOWNSERVICE ==========
window.closeDropdownRecibos = () => DropdownService.getInstance().closeAll();

function extraerDataDelBoton(button) {
    return DropdownService.getInstance().extractButtonData(button);
}

function construirBotonesDropdown(data) {
    return DropdownService.getInstance().buildDropdownButtons(data);
}

function posicionarDropdown(dropdown, button) {
    return DropdownService.getInstance().positionDropdown(dropdown, button);
}

function crearDropdownRecibos(button) {
    return DropdownService.getInstance().createOrGetDropdown(button);
}

// ========== MODAL DE DISTRIBUCION ==========
window.openDistribucionReciboModal = async function(reciboId) {
    const modal = document.getElementById('recibo-distribution-modal');
    const body = document.getElementById('distributionModalBody');
    const title = document.getElementById('distributionModalTitle');

    if (!modal || !body || !title) {
        console.error('[DistribucionModal] No se encontro la estructura del modal');
        return;
    }

    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');
    syncReciboModalBodyState();

    title.textContent = 'Distribucion del recibo';
    body.innerHTML = `
        <div class="distribution-loading">
            <span class="distribution-spinner"></span>
            <span>Cargando distribucion del recibo...</span>
        </div>
    `;

    try {
        const response = await fetch(`/api/recibos-costura/${encodeURIComponent(reciboId)}/distribucion`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const contentType = response.headers.get('content-type') || '';
        if (!contentType.includes('application/json')) {
            const rawText = await response.text();
            throw new Error(rawText.startsWith('<') ? 'La ruta devolvio HTML en lugar de JSON' : rawText);
        }

        const result = await response.json();

        if (!response.ok || !result.success) {
            throw new Error(result.message || 'No se pudo cargar la distribucion');
        }

        const recibo = result.recibo || {};
        const parciales = Array.isArray(result.parciales) ? result.parciales : [];
        title.textContent = `Distribucion del recibo #${recibo.consecutivo ?? reciboId}`;
        body.innerHTML = buildDistributionModalContent(recibo, parciales);
    } catch (error) {
        console.error('[DistribucionModal] Error cargando distribucion:', error);
        body.innerHTML = `
            <div class="distribution-empty">
                <h3 style="margin: 0 0 8px 0; color: #0f172a; font-size: 20px;">No fue posible cargar la distribucion</h3>
                <p style="margin: 0; font-size: 14px;">${escapeDistributionHtml(error.message || 'Intenta de nuevo en unos segundos.')}</p>
            </div>
        `;
    }
};

window.closeDistribucionReciboModal = function() {
    const modal = document.getElementById('recibo-distribution-modal');
    if (!modal) return;

    window.closeSeguimientoParcialModal();
    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');
    syncReciboModalBodyState();
};

window.openSeguimientoParcialModal = async function(parcialId) {
    const modal = document.getElementById('partial-tracking-modal');
    const body = document.getElementById('partialTrackingModalBody');
    const title = document.getElementById('partialTrackingModalTitle');

    if (!modal || !body || !title) {
        console.error('[SeguimientoParcialModal] No se encontro la estructura del modal');
        return;
    }

    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');
    syncReciboModalBodyState();

    title.textContent = 'Recorrido del parcial';
    body.innerHTML = `
        <div class="distribution-loading">
            <span class="distribution-spinner"></span>
            <span>Cargando seguimiento del parcial...</span>
        </div>
    `;

    try {
        const response = await fetch(`/api/recibos-costura/parciales/${encodeURIComponent(parcialId)}/seguimiento`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const contentType = response.headers.get('content-type') || '';
        if (!contentType.includes('application/json')) {
            const rawText = await response.text();
            throw new Error(rawText.startsWith('<') ? 'La ruta devolvio HTML en lugar de JSON' : rawText);
        }

        const result = await response.json();

        if (!response.ok || !result.success) {
            throw new Error(result.message || 'No se pudo cargar el seguimiento del parcial');
        }

        const parcial = result.parcial || {};
        const timeline = Array.isArray(result.timeline) ? result.timeline : [];
        title.textContent = `Seguimiento del parcial #${escapeDistributionHtml(formatParcialConsecutivo(parcial.consecutivo_parcial ?? parcialId))}`;
        body.innerHTML = buildPartialTrackingModalContent(parcial, timeline);
    } catch (error) {
        console.error('[SeguimientoParcialModal] Error cargando seguimiento:', error);
        body.innerHTML = `
            <div class="partial-tracking-empty">
                <h3 style="margin: 0 0 8px 0; color: #0f172a; font-size: 20px;">No fue posible cargar el seguimiento</h3>
                <p style="margin: 0; font-size: 14px;">${escapeDistributionHtml(error.message || 'Intenta de nuevo en unos segundos.')}</p>
            </div>
        `;
    }
};

window.closeSeguimientoParcialModal = function() {
    const modal = document.getElementById('partial-tracking-modal');
    if (!modal) return;

    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');
    syncReciboModalBodyState();
};

function buildDistributionModalContent(recibo, parciales) {
    const totalTallas = parciales.reduce((carry, parcial) => {
        const tallas = Array.isArray(parcial.tallas) ? parcial.tallas : [];
        return carry + tallas.reduce((sum, talla) => sum + (parseInt(talla.cantidad, 10) || 0), 0);
    }, 0);

    if (parciales.length === 0) {
        return `
            <div class="distribution-summary">
                <div class="distribution-summary__card">
                    <span class="distribution-summary__label">Recibo</span>
                    <span class="distribution-summary__value">#${escapeDistributionHtml(recibo.consecutivo ?? '-')}</span>
                </div>
                <div class="distribution-summary__card">
                    <span class="distribution-summary__label">Area actual</span>
                    <span class="distribution-summary__value">${escapeDistributionHtml(recibo.area_actual ?? 'Sin area')}</span>
                </div>
                <div class="distribution-summary__card">
                    <span class="distribution-summary__label">Parciales</span>
                    <span class="distribution-summary__value">0</span>
                </div>
            </div>
            <div class="distribution-empty">
                <h3 style="margin: 0 0 8px 0; color: #0f172a; font-size: 20px;">Este recibo aun no tiene parciales</h3>
                <p style="margin: 0; font-size: 14px;">Cuando se distribuya por modulos, aqui te mostraremos cada parcial con su encargado, area y tallas.</p>
            </div>
        `;
    }

    const cards = parciales.map((parcial) => {
        const tallas = Array.isArray(parcial.tallas) ? parcial.tallas : [];
        const tallasHtml = tallas.length > 0
            ? tallas.map((talla) => {
                const tallaNombre = escapeDistributionHtml(talla.talla ?? 'N/A');
                const cantidad = parseInt(talla.cantidad, 10) || 0;
                const color = talla.color_nombre ? ` <span style="opacity:.72;">${escapeDistributionHtml(talla.color_nombre)}</span>` : '';
                return `<span class="distribution-size-chip">${tallaNombre} <strong>x${cantidad}</strong>${color}</span>`;
            }).join('')
            : '<span class="distribution-pill distribution-pill--slate">Sin tallas registradas</span>';

        return `
            <article class="distribution-card">
                <div class="distribution-card__inner">
                    <div class="distribution-card__top">
                        <div class="distribution-card__title">
                            <h3>Parcial #${escapeDistributionHtml(formatParcialConsecutivo(parcial.consecutivo_parcial))}</h3>
                            <span class="distribution-pill distribution-pill--slate">${escapeDistributionHtml(parcial.proceso_estado || 'En progreso')}</span>
                        </div>
                        <span class="distribution-pill distribution-pill--green">${escapeDistributionHtml(parcial.area || 'Sin area')}</span>
                    </div>
                    <div class="distribution-card__meta">
                        <div class="distribution-card__row">
                            <span class="distribution-card__row-label">Encargado</span>
                            <span class="distribution-pill distribution-pill--blue">${escapeDistributionHtml(parcial.encargado || 'Sin asignar')}</span>
                        </div>
                        <div class="distribution-card__row">
                            <span class="distribution-card__row-label">Tallas</span>
                            <div class="distribution-sizes">${tallasHtml}</div>
                        </div>
                    </div>
                    <div class="distribution-card__actions">
                        <button
                            type="button"
                            class="distribution-action-btn"
                            onclick="openSeguimientoParcialModal(${Number(parcial.id)})"
                        >
                            <i class="fas fa-route"></i>
                            Ver seguimiento
                        </button>
                    </div>
                </div>
            </article>
        `;
    }).join('');

    return `
        <div class="distribution-summary">
            <div class="distribution-summary__card">
                <span class="distribution-summary__label">Recibo</span>
                <span class="distribution-summary__value">#${escapeDistributionHtml(recibo.consecutivo ?? '-')}</span>
            </div>
            <div class="distribution-summary__card">
                <span class="distribution-summary__label">Area actual</span>
                <span class="distribution-summary__value">${escapeDistributionHtml(recibo.area_actual ?? 'Sin area')}</span>
            </div>
            <div class="distribution-summary__card">
                <span class="distribution-summary__label">Resumen</span>
                <span class="distribution-summary__value">${parciales.length} parciales - ${totalTallas} und</span>
            </div>
        </div>
        <div class="distribution-list">${cards}</div>
    `;
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

    const totalDias = (Array.isArray(timeline) ? timeline : []).reduce((sum, step) => {
        const n = Number(step?.dias_habiles);
        return sum + (Number.isFinite(n) ? n : 0);
    }, 0);

    if (timeline.length === 0) {
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
                <div class="partial-tracking-summary__card">
                    <span class="partial-tracking-summary__label">Total dias</span>
                    <span class="partial-tracking-summary__value">${escapeDistributionHtml(totalDias)}</span>
                </div>
            </div>
            <div class="partial-tracking-empty">
                <h3 style="margin: 0 0 8px 0; color: #0f172a; font-size: 20px;">Este parcial aun no tiene recorrido registrado</h3>
                <p style="margin: 0; font-size: 14px;">Cuando empiece a pasar por las areas, aqui te mostraremos su linea de tiempo.</p>
            </div>
        `;
    }

    const steps = timeline.map((step) => {
        const isCompleted = Boolean(step.completado);
        const estadoLabel = isCompleted ? 'Completado' : (step.estado || 'En progreso');
        const estadoIcon = isCompleted ? 'fa-check-circle' : 'fa-signal';

        const fechaInicio = step.fecha_inicio
            ? `<span><strong>Inicio:</strong> ${escapeDistributionHtml(step.fecha_inicio)}</span>`
            : '';
        const fechaFin = step.fecha_fin
            ? `<span><strong>Fin:</strong> ${escapeDistributionHtml(step.fecha_fin)}</span>`
            : '';
        const diasHabiles = Number.isFinite(Number(step.dias_habiles))
            ? `
                <div class="partial-tracking-step__days" aria-label="Dias habiles transcurridos">
                    <span class="partial-tracking-step__days-label">Dias habiles</span>
                    <span class="partial-tracking-step__days-value">${escapeDistributionHtml(step.dias_habiles)}</span>
                </div>
            `
            : '';

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
                <div class="partial-tracking-step__dates">
                    ${fechaInicio}
                    ${fechaFin}
                </div>
                ${diasHabiles}
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
            <div class="partial-tracking-summary__card">
                <span class="partial-tracking-summary__label">Total dias</span>
                <span class="partial-tracking-summary__value">${escapeDistributionHtml(totalDias)}</span>
            </div>
        </div>
        <div class="partial-tracking-timeline">${steps}</div>
    `;
}

function syncReciboModalBodyState() {
    const distributionModal = document.getElementById('recibo-distribution-modal');
    const partialTrackingModal = document.getElementById('partial-tracking-modal');
    const isAnyOpen = Boolean(
        distributionModal?.classList.contains('is-open') ||
        partialTrackingModal?.classList.contains('is-open')
    );

    document.body.style.overflow = isAnyOpen ? 'hidden' : '';
}

function formatParcialConsecutivo(value) {
    if (value === null || value === undefined || value === '') {
        return '-';
    }

    const raw = String(value);
    if (raw.includes('.')) {
        return raw.replace(/\.0+$/, '').replace(/(\.\d*[1-9])0+$/, '$1');
    }

    return raw;
}

function escapeDistributionHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

// ========== WRAPPERS DE COSTURANOTIFICATIONBELLSERVICE ==========
async function cargarConteoRecibosCorte() {
    return CosturaNotificationBellService.getInstance().loadCosturaCount();
}

async function marcarReciboVisto(reciboId, itemElement) {
    return CosturaNotificationBellService.getInstance().markAsViewed(reciboId, itemElement);
}

function setupCosturaNotifications() {
    return CosturaNotificationBellService.getInstance()._setupEventListeners();
}

// ========== WRAPPERS DE REALTIMERECIBOLISTENER ==========
function initializeReciboAprobadoListener() {
    return RealtimeReciboListener.getInstance().initialize();
}

function showRecibAprobadoNotification(data) {
    return RealtimeReciboListener.getInstance()._showNotification(data);
}

function recargarTablaRecibosEnTiempoReal(data) {
    return RealtimeReciboListener.getInstance()._reloadTable(data);
}

// ========== INICIALIZACIÓN ==========
// Ocultar botón Volver si existe
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        const botonVolver = document.getElementById('backToPrendasBtn');
        if (botonVolver && window.location.pathname.includes('/recibos-costura')) {
            botonVolver.style.display = 'none';
        }
        DropdownService.getInstance().attachEventListeners();
        setupDistribucionModal();
        setupSeguimientoParcialModal();
    });
} else {
    const botonVolver = document.getElementById('backToPrendasBtn');
    if (botonVolver && window.location.pathname.includes('/recibos-costura')) {
        botonVolver.style.display = 'none';
    }
    DropdownService.getInstance().attachEventListeners();
    setupDistribucionModal();
    setupSeguimientoParcialModal();
}

// Inicializar servicios de campana y realtime
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        CosturaNotificationBellService.getInstance().init();
        initializeReciboAprobadoListener();
    });
} else {
    CosturaNotificationBellService.getInstance().init();
    initializeReciboAprobadoListener();
}

// ========== FUNCIONES DE CARGA DE DATOS (aún no migradas) ==========
/**
 * TODO: Esta función debería migrase a AddProcessModalController
 * Está aquí temporalmente por compatibilidad
 */
async function cargarDatosParaAgregarProceso(pedidoId, prendaId, areaSeleccionada) {
    try {
        if (!prendaId || prendaId === 'null' || prendaId === null) {
            throw new Error('Prenda específica requerida');
        }
        
        const response = await fetch(`/registros/${pedidoId}/recibos-datos`);
        if (!response.ok) throw new Error('Error al cargar datos del pedido');
        
        const result = await response.json();
        const data = result.data || result;
        
        window.currentOrderData = {
            ...data,
            numero_pedido: data.numero_pedido || data.id || pedidoId,
            pedido: data.numero_pedido || data.id || pedidoId
        };
        window.currentPedidoId = pedidoId;
        window.currentPrendaId = prendaId;
        window.currentArea = areaSeleccionada;
        
        if (data.prendas && Array.isArray(data.prendas)) {
            const prendaEncontrada = data.prendas.find(p => 
                String(p.id) === String(prendaId) || 
                String(p.prenda_pedido_id) === String(prendaId)
            );
            
            if (!prendaEncontrada) {
                throw new Error(`Prenda ${prendaId} no encontrada en pedido ${pedidoId}`);
            }
            
            window.currentPrendaData = prendaEncontrada;
        } else {
            throw new Error('El pedido no tiene prendas');
        }
    } catch (error) {
        console.error('[cargarDatosParaAgregarProceso]', error.message);
        throw error;
    }
}

// Cargar nombres de prendas en tabla (inicialización de carga)
document.addEventListener('DOMContentLoaded', function() {
    const filasRecibos = document.querySelectorAll('#tablaRecibosBody tr[data-orden-id]');
    
    filasRecibos.forEach(fila => {
        const reciboId = fila.getAttribute('data-orden-id');
        const descripcionElemento = fila.querySelector('.descripcion-prenda-texto');
        
        if (descripcionElemento) {
            const enlacePedido = fila.querySelector('a[href*="/registros/"]');
            let pedidoProduccionId = null;
            
            if (enlacePedido) {
                const match = enlacePedido.getAttribute('href').match(/\/registros\/(\d+)/);
                if (match) pedidoProduccionId = match[1];
            }
            
            if (pedidoProduccionId) {
                fetch(`/api/pedidos/${pedidoProduccionId}/prendas`)
                    .then(r => r.json())
                    .then(datos => {
                        const data = datos.data && typeof datos.data === 'object' ? datos.data : datos;
                        if (data.prendas && Array.isArray(data.prendas) && data.prendas.length > 0) {
                            const nombrePrenda = data.prendas[0].nombre || data.prendas[0].nombre_prenda || 'Sin nombre';
                            descripcionElemento.textContent = nombrePrenda;
                        }
                    })
                    .catch(e => {
                        console.error(`[CargarNombres] Error para recibo ${reciboId}:`, e);
                        descripcionElemento.textContent = 'Error';
                    });
            } else {
                descripcionElemento.textContent = 'Sin pedido';
            }
        }
    });
});

function setupDistribucionModal() {
    const modal = document.getElementById('recibo-distribution-modal');
    if (!modal || modal.dataset.ready === 'true') {
        return;
    }

    modal.dataset.ready = 'true';

    modal.addEventListener('click', function(event) {
        const shouldClose = event.target.closest('[data-distribution-close="true"]');
        if (shouldClose || event.target === modal) {
            window.closeDistribucionReciboModal();
        }
    });

    document.addEventListener('keydown', function(event) {
        if (event.key !== 'Escape') {
            return;
        }

        const partialTrackingModal = document.getElementById('partial-tracking-modal');
        if (partialTrackingModal && partialTrackingModal.classList.contains('is-open')) {
            window.closeSeguimientoParcialModal();
            return;
        }

        if (modal.classList.contains('is-open')) {
            window.closeDistribucionReciboModal();
        }
    });
}

function setupSeguimientoParcialModal() {
    const modal = document.getElementById('partial-tracking-modal');
    if (!modal || modal.dataset.ready === 'true') {
        return;
    }

    modal.dataset.ready = 'true';

    modal.addEventListener('click', function(event) {
        const shouldClose = event.target.closest('[data-partial-tracking-close="true"]');
        if (shouldClose || event.target === modal) {
            window.closeSeguimientoParcialModal();
        }
    });
}
