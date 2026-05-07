/**
 * Dropdown Handlers for Insumos/Materiales
 *
 * Extraído de index.blade.php — Refactorización FASE 7
 *
 * Responsabilidades:
 * - Dropdown "Ver Recibo / Seguimiento"
 * - Dropdown "Más Acciones (...)"
 * - Abrir seguimiento de recibo (abrirSeguimientoRecibo)
 * - Modo readonly del tracking modal
 * - Cálculo de demora en frontend (calcularDemora)
 */

// ===== ESTADO DE LOS DROPDOWNS =====

let dropdownAbiertoButton = null;
let dropdownVerAbiertoButton = null;

// ===== CÁLCULO DE DEMORA EN TIEMPO REAL =====

/**
 * Calcula los días de demora entre Fecha Pedido y Fecha Llegada.
 * Delega el cálculo a insumosHandlers.utilities.calcularDemoraAsync.
 */
async function calcularDemora(materialId) {
    const idParts = materialId.split('_');
    const ordenId = idParts[1];
    const index   = idParts[2];
    const sufijo  = idParts.slice(3).join('_');

    const fechaPedidoInput  = document.getElementById(`fecha_pedido_${ordenId}_${index}_${sufijo}`);
    const fechaLlegadaInput = document.getElementById(`fecha_llegada_${ordenId}_${index}_${sufijo}`);
    const diasSpan          = document.getElementById(`dias_${materialId}`);

    if (!fechaPedidoInput || !fechaLlegadaInput || !diasSpan) return;

    if (!fechaPedidoInput.value || !fechaLlegadaInput.value) {
        diasSpan.textContent = '-';
        diasSpan.className = 'inline-block px-3 py-1 rounded-full text-sm font-semibold bg-gray-100 text-gray-600';
        return;
    }

    const calcularDemoraAsync = globalThis.insumosHandlers?.utilities?.calcularDemoraAsync;
    if (typeof calcularDemoraAsync !== 'function') return;

    const demora = await calcularDemoraAsync(fechaPedidoInput.value, fechaLlegadaInput.value);
    diasSpan.textContent = demora.texto;
    diasSpan.className = `inline-block px-3 py-1 rounded-full text-sm font-semibold ${demora.clase_bg} ${demora.clase_text}`;
}

// ===== DROPDOWN: VER RECIBO / SEGUIMIENTO =====

/**
 * Abre el dropdown de "Ver Recibo" o "Seguimiento" posicionado bajo el botón.
 */
function crearDropdownVerRecibo(event, button) {
    event.preventDefault();
    event.stopPropagation();

    if (dropdownVerAbiertoButton === button) {
        cerrarDropdownVerRecibo();
        return;
    }

    cerrarDropdownVerRecibo();
    dropdownVerAbiertoButton = button;

    const container = document.getElementById('dropdowns-container');
    if (!container) return;

    const pedidoId = button.getAttribute('data-pedido-id') || button.getAttribute('data-pedido-produccion-id');
    const prendaId = button.getAttribute('data-prenda-id');
    const tipoRecibo = button.getAttribute('data-tipo-recibo') || 'COSTURA';
    const esParcial = button.getAttribute('data-es-parcial') === '1';
    const pedidoParcialId = button.getAttribute('data-pedido-parcial-id') || '';
    const numeroRecibo = button.getAttribute('data-numero-recibo') || button.getAttribute('data-consecutivo') || '';
    const rect = button.getBoundingClientRect();

    const dropdown = document.createElement('div');
    dropdown.className = 'dropdown-ver-fixed';
    dropdown.style.cssText = `
        position: absolute;
        top: ${rect.bottom}px;
        left: ${rect.left}px;
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        min-width: 180px;
        z-index: 999999;
        overflow: visible;
        pointer-events: auto;
    `;

    dropdown.innerHTML = `
        <button data-insumos-action="dropdown-ver-detalle-recibo"
            data-pedido-id="${pedidoId}"
            data-prenda-id="${prendaId ?? 'null'}"
            data-tipo-recibo="${tipoRecibo}"
            data-es-parcial="${esParcial ? '1' : '0'}"
            data-pedido-parcial-id="${pedidoParcialId}"
            style="
            width:100%;text-align:left;padding:0.875rem 1rem;border:none;
            background:transparent;cursor:pointer;color:#374151;font-size:0.875rem;
            transition:all 0.2s ease;display:flex;align-items:center;gap:0.75rem;
            font-weight:500;border-bottom:1px solid #f3f4f6;
        ">
            <i class="fas fa-file-lines" style="color:#3b82f6;font-size:1rem;"></i>
            <span>Ver recibo</span>
        </button>
        <button data-insumos-action="dropdown-ver-seguimiento"
            data-pedido-id="${pedidoId}"
            data-prenda-id="${prendaId ?? ''}"
            data-numero-recibo="${numeroRecibo}"
            data-tipo-recibo="${tipoRecibo}"
            data-es-parcial="${esParcial ? '1' : '0'}"
            data-pedido-parcial-id="${pedidoParcialId}"
            style="
            width:100%;text-align:left;padding:0.875rem 1rem;border:none;
            background:transparent;cursor:pointer;color:#374151;font-size:0.875rem;
            transition:all 0.2s ease;display:flex;align-items:center;gap:0.75rem;
            font-weight:500;
        ">
            <i class="fas fa-map-location-dot" style="color:#0284c7;font-size:1rem;"></i>
            <span>Seguimiento</span>
        </button>
    `;

    container.appendChild(dropdown);
}

function cerrarDropdownVerRecibo() {
    document.querySelectorAll('.dropdown-ver-fixed').forEach(el => el.remove());
    dropdownVerAbiertoButton = null;
}

// ===== BOTÓN VER: ABRIR TRACKING DESDE LA FILA =====

/**
 * Abre el modal de tracking directamente desde el botón "Ver" de la fila,
 * obteniendo los datos del botón de acciones de la misma fila.
 */
function abrirTrackingDesdeBotonVer(event, button) {
    event.preventDefault();
    event.stopPropagation();

    const pedidoProduccionId = button.getAttribute('data-pedido-produccion-id');
    const prendaId = button.getAttribute('data-prenda-id');

    const fila = button.closest('tr');
    if (!fila) return;

    const btnAcciones = fila.querySelector('.btn-acciones');
    let consecutivo = null;
    let estado      = null;
    let tipoRecibo  = null;

    if (btnAcciones) {
        consecutivo = btnAcciones.getAttribute('data-consecutivo');
        estado      = btnAcciones.getAttribute('data-estado');
        tipoRecibo  = btnAcciones.getAttribute('data-tipo-recibo');
    }

    abrirSeguimientoRecibo(pedidoProduccionId, prendaId, consecutivo, estado, tipoRecibo);
}

// ===== DROPDOWN: ACCIONES (...) =====

/**
 * Abre el dropdown de acciones (Gestionar insumos, Ancho/Metraje, Pasar a Revisar).
 */
function crearDropdownAcciones(event, button) {
    event.preventDefault();
    event.stopPropagation();

    if (dropdownAbiertoButton === button) {
        cerrarDropdownAcciones();
        return;
    }

    cerrarDropdownAcciones();
    dropdownAbiertoButton = button;

    const container = document.getElementById('dropdowns-container');
    if (!container) return;

    const pedidoProduccionId = button.getAttribute('data-pedido-produccion-id');
    const prendaId           = button.getAttribute('data-prenda-id');
    const reciboId           = button.getAttribute('data-recibo-id');
    const consecutivo        = button.getAttribute('data-consecutivo');
    const estado             = button.getAttribute('data-estado');
    const tipoRecibo         = button.getAttribute('data-tipo-recibo');
    const rect               = button.getBoundingClientRect();

    const dropdown = document.createElement('div');
    dropdown.className = 'acciones-dropdown-fixed';
    dropdown.style.cssText = `
        position: absolute;
        top: ${rect.bottom}px;
        left: ${rect.right}px;
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        min-width: 220px;
        z-index: 999999;
        overflow: visible;
        pointer-events: auto;
    `;

    let html = `
        <button data-insumos-action="dropdown-acciones-gestionar-insumos"
            data-pedido-produccion-id="${pedidoProduccionId}"
            data-prenda-id="${prendaId}"
            data-consecutivo="${consecutivo}"
            data-estado="${estado}"
            data-tipo-recibo="${tipoRecibo}"
            style="
            width:100%;text-align:left;padding:0.875rem 1rem;border:none;
            background:transparent;cursor:pointer;color:#374151;font-size:0.875rem;
            transition:all 0.2s ease;display:flex;align-items:center;gap:0.75rem;
            font-weight:500;border-bottom:1px solid #f3f4f6;
        ">
            <i class="fas fa-box" style="color:#10b981;font-size:1rem;"></i>
            <span>Gestionar insumos</span>
        </button>
        <button data-insumos-action="dropdown-acciones-ancho-metraje"
            data-pedido-produccion-id="${pedidoProduccionId}"
            data-prenda-id="${prendaId}"
            style="
            width:100%;text-align:left;padding:0.875rem 1rem;border:none;
            background:transparent;cursor:pointer;color:#374151;font-size:0.875rem;
            transition:all 0.2s ease;display:flex;align-items:center;gap:0.75rem;
            font-weight:500;border-bottom:1px solid #f3f4f6;
        ">
            <i class="fas fa-ruler" style="color:#f59e0b;font-size:1rem;"></i>
            <span>Ancho y metraje</span>
        </button>
    `;

    if (estado !== 'Anulada') {
        html += `
        <button data-insumos-action="dropdown-acciones-anular-recibo"
            data-recibo-id="${reciboId}"
            data-consecutivo="${consecutivo}"
            style="
            width:100%;text-align:left;padding:0.875rem 1rem;border:none;
            background:transparent;cursor:pointer;color:#374151;font-size:0.875rem;
            transition:all 0.2s ease;display:flex;align-items:center;gap:0.75rem;
            font-weight:500;border-bottom:1px solid #f3f4f6;
        ">
            <i class="fas fa-ban" style="color:#dc2626;font-size:1rem;"></i>
            <span>Anular recibo</span>
        </button>
        `;
    }

    const estadosProduccion = ['CORTE','EN_CORTE','ENVIADO_PRODUCCION','EN_PRODUCCION','CORTANDO','EN_EJECUCIÓN','En Ejecución','EN_COSTURA'];
    const yaEnProduccion = estadosProduccion.some(s => estado && estado.toUpperCase().includes(s.toUpperCase()));

    if (estado !== 'DEVUELTO_ASESOR' && !yaEnProduccion) {
        html += `
        <button data-insumos-action="dropdown-acciones-pasar-revisar"
            data-recibo-id="${reciboId}"
            data-pedido-produccion-id="${pedidoProduccionId}"
            style="
            width:100%;text-align:left;padding:0.875rem 1rem;border:none;
            background:transparent;cursor:pointer;color:#374151;font-size:0.875rem;
            transition:all 0.2s ease;display:flex;align-items:center;gap:0.75rem;
            font-weight:500;border-bottom:1px solid #f3f4f6;
        ">
            <i class="fas fa-arrow-rotate-left" style="color:#dc2626;font-size:1rem;"></i>
            <span>Pasar a Revisar</span>
        </button>
        `;
    }

    dropdown.innerHTML = html;
    container.appendChild(dropdown);
}

function cerrarDropdownAcciones() {
    document.querySelectorAll('.acciones-dropdown-fixed').forEach(el => el.remove());
    dropdownAbiertoButton = null;
}

// ===== SEGUIMIENTO DE RECIBO =====

/**
 * Carga los procesos de la prenda y abre el modal de seguimiento (showPrendaTracking).
 */
async function abrirSeguimientoRecibo(pedidoId, prendaId, consecutivo = null, estado = null, tipoRecibo = null, esParcial = false, pedidoParcialId = null) {
    pedidoId = parseInt(pedidoId) || null;
    prendaId = parseInt(prendaId) || null;

    if (!pedidoId) {
        console.error('[abrirSeguimientoRecibo] pedidoId es requerido');
        return;
    }

    try {
        // Contexto para tracking-modal-handler (normal/parcial + recibo objetivo)
        globalThis.currentTrackingReceiptContext = {
            pedidoId: pedidoId,
            prendaId: prendaId,
            numeroRecibo: consecutivo ? String(consecutivo) : null,
            tipoRecibo: tipoRecibo ? String(tipoRecibo) : 'REFLECTIVO',
            esParcial: Boolean(esParcial),
            pedidoParcialId: pedidoParcialId ? Number(pedidoParcialId) : null
        };

        let procesos = [];

        if (prendaId) {
            const response = await fetch(`/api/ordenes/${pedidoId}/procesos?prenda_id=${prendaId}`);
            if (response.ok) {
                procesos = await response.json();
            } else {
                console.warn('[abrirSeguimientoRecibo] Error al obtener procesos:', response.status);
            }
        }

        const prenda = {
            id:                    prendaId,
            pedido_produccion_id:  pedidoId,
            numero_prenda:         prendaId,
            procesos,
            ultimo_recibo_numero:  consecutivo || null,
            consecutivos:          consecutivo ? [consecutivo] : [],
            estado:                estado     || 'PENDIENTE_INSUMOS',
            tipo_recibo:           tipoRecibo || 'COSTURA',
            // En Insumos, aprobar a "En Ejecución" significa enviar a CORTE (no a Costura).
            area:                  (estado === 'En Ejecución' || estado === 'En Ejecucion') ? 'Corte' : (estado || 'Insumos'),
            readonly:              Boolean(globalThis.isInsumos || globalThis.location?.pathname?.includes('/insumos/materiales')),
            seguimientos_por_area: {}
        };

        if (typeof showPrendaTracking === 'function') {
            showPrendaTracking(prenda);
        } else {
            console.error('[abrirSeguimientoRecibo] showPrendaTracking no está disponible');
        }
    } catch (error) {
        console.error('[abrirSeguimientoRecibo] Error:', error);
        alert('Error al cargar el seguimiento: ' + error.message);
    }
}

// ===== MODO READONLY DEL TRACKING MODAL =====

/**
 * Oculta los botones de edición/eliminación/agregar del modal de tracking.
 * Se usa para usuarios con rol de solo lectura (ej: insumos).
 */
function aplicarModoReadonly() {
    function aplicarReadonly() {
        const modal = document.getElementById('orderTrackingModal');
        if (!modal) return false;

        let modificados = 0;

        modal.querySelectorAll('button.tracking-edit-btn, button.tracking-delete-btn').forEach(btn => {
            btn.style.display     = 'none';
            btn.style.visibility  = 'hidden';
            btn.style.pointerEvents = 'none';
            btn.disabled = true;
            modificados++;
        });

        const btnAgregar = document.getElementById('btnOpenAddProcesoModal');
        if (btnAgregar) {
            btnAgregar.style.display     = 'none';
            btnAgregar.style.visibility  = 'hidden';
            btnAgregar.style.pointerEvents = 'none';
            btnAgregar.disabled = true;
            modificados++;
        }

        return modificados > 0;
    }

    aplicarReadonly();
    setTimeout(aplicarReadonly, 300);
    setTimeout(aplicarReadonly, 800);

    const modal = document.getElementById('orderTrackingModal');
    if (modal) {
        new MutationObserver(() => setTimeout(aplicarReadonly, 100))
            .observe(modal, { childList: true, subtree: true });
    }
}


// ===== CERRAR DROPDOWNS CON SCROLL O CLIC FUERA =====

globalThis.addEventListener('scroll', function () {
    if (dropdownAbiertoButton)    cerrarDropdownAcciones();
    if (dropdownVerAbiertoButton) cerrarDropdownVerRecibo();
}, { passive: true });

document.addEventListener('click', function (e) {
    const inVer     = e.target.closest('.dropdown-ver-fixed');
    const inAcciones = e.target.closest('.acciones-dropdown-fixed');

    if (dropdownVerAbiertoButton && !inVer &&
        e.target !== dropdownVerAbiertoButton &&
        !dropdownVerAbiertoButton.contains(e.target)) {
        cerrarDropdownVerRecibo();
    }

    if (dropdownAbiertoButton && !inAcciones &&
        e.target !== dropdownAbiertoButton &&
        !dropdownAbiertoButton.contains(e.target)) {
        cerrarDropdownAcciones();
    }
}, false);

globalThis.insumosHandlers = globalThis.insumosHandlers || {};
globalThis.insumosHandlers.dropdownHandlers = {
    calcularDemora,
    crearDropdownVerRecibo,
    cerrarDropdownVerRecibo,
    abrirTrackingDesdeBotonVer,
    crearDropdownAcciones,
    cerrarDropdownAcciones,
    abrirSeguimientoRecibo,
    aplicarModoReadonly,
};
