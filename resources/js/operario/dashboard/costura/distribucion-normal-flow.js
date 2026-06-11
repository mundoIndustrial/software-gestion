import { mostrarError, mostrarExito } from '../ui/messages';
import {
    agruparTallasPorGeneroYColor,
} from './distribucion-core';
import {
    getColorParaTallaId,
    getDisponibleRestanteGlobal,
    getMaxDisponibleParaModulo,
    refrescarDistribucionUI,
} from './talla-disponibilidad-utils';
import { construirTallaIdUnico } from './talla-utils';

function getTotalAsignadoTalla(talla, moduloIdExcluir = null) {
    let total = 0;
    let hayAsignacionesEnMemoria = false;

    if (window.asignacionesPorModulo) {
        for (const [moduloId, asignaciones] of Object.entries(window.asignacionesPorModulo)) {
            if (parseInt(moduloId) !== moduloIdExcluir && asignaciones[talla]) {
                let cantidad = 0;
                if (typeof asignaciones[talla] === 'object' && asignaciones[talla] !== null) {
                    cantidad = asignaciones[talla].cantidad || 0;
                } else if (typeof asignaciones[talla] === 'number') {
                    cantidad = asignaciones[talla];
                }
                total += cantidad;
                hayAsignacionesEnMemoria = true;
            }
        }
    }

    if (hayAsignacionesEnMemoria) {
        return total;
    }

    if (window.datosModalCostura?.esEdicion) {
        return getTotalAsignadoTallaDesdeParcialesEdicion(talla);
    }

    return total;
}

function getTotalAsignadoTallaDesdeParcialesEdicion(tallaId) {
    const parciales = Array.isArray(window.__datosParcialesEdicion) ? window.__datosParcialesEdicion : [];
    if (parciales.length === 0) return 0;

    return parciales.reduce((total, parcial) => {
        const estadoParcial = String(
            parcial?.proceso_estado
                ?? parcial?.estado_proceso
                ?? parcial?.estado
                ?? ''
        ).toUpperCase().trim();

        if (estadoParcial === 'ANULADO') return total;

        (parcial.tallas || []).forEach((tallaParcial) => {
            const cantidad = parseInt(tallaParcial?.cantidad) || 0;
            if (cantidad <= 0) return;

            const idParcial = construirTallaIdUnico(
                String(tallaParcial?.talla || '').trim(),
                tallaParcial?.color_nombre || getColorParaTallaId(tallaId) || null,
                tallaParcial?.genero || tallaParcial?.sexo || 'Sin género'
            );

            if (idParcial === tallaId) {
                total += cantidad;
            }
        });

        return total;
    }, 0);
}

function actualizarResumenAsignaciones() {
    const resumenContenido = document.getElementById('resumenContenido');
    if (!resumenContenido) return;

    if (!window.asignacionesPorModulo || Object.keys(window.asignacionesPorModulo).length === 0) {
        resumenContenido.innerHTML = '<p style="margin: 0; font-size: 0.875rem; color: #92400e;">No hay asignaciones realizadas</p>';
        return;
    }

    const modulos = window.datosDistribucion.modulos;
    let html = '<div style="display: grid; gap: 0.5rem;">';

    for (const [moduloId, asignaciones] of Object.entries(window.asignacionesPorModulo)) {
        const modulo = modulos.find((m) => m.id === parseInt(moduloId));
        if (!modulo) continue;

        const tallasAsignadas = Object.entries(asignaciones)
            .map(([talla, datos]) => {
                let cantidad = 0;
                if (typeof datos === 'object' && datos !== null) {
                    cantidad = datos.cantidad || 0;
                } else if (typeof datos === 'number') {
                    cantidad = datos;
                }
                return `${talla}×${cantidad}`;
            })
            .join(', ');

        const nombreModuloResumen = modulo.encargado || modulo.nombre || `Modulo ${moduloId}`;

        html += `
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.5rem; background: white; border-radius: 4px;">
                <span style="font-size: 0.875rem; font-weight: 500; color: #92400e;">${nombreModuloResumen}:</span>
                <span style="font-size: 0.875rem; color: #92400e;">${tallasAsignadas}</span>
            </div>
        `;
    }

    html += '</div>';
    resumenContenido.innerHTML = html;
}

function mostrarInterfazDistribucionNormal(tallas, modulos) {
    const interfazDiv = document.getElementById('interfazDistribucion');
    if (!interfazDiv) {
        console.warn('[DISTRIBUCION] No existe #interfazDistribucion en modo normal');
        return;
    }

    window.modulosSeleccionadosDistribucion = [];

    let html = `
        <div style="margin-bottom: 1.5rem;">
            <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: #374151;">Seleccionar Modulo:</label>
            <div style="display: flex; gap: 0.5rem;">
                <div style="position: relative; flex: 1;">
                    <input type="text" id="moduloSelector" list="listaModulosDisponibles"
                        placeholder="Seleccione o escriba un Modulo para asignar tallas..."
                        style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 8px; background: white; font-size: 0.875rem; outline: none; transition: all 0.2s;"
                        onfocus="this.style.borderColor='#10b981'; this.style.boxShadow='0 0 0 3px rgba(16, 185, 129, 0.1)';"
                        onblur="this.style.borderColor='#d1d5db'; this.style.boxShadow='none';"
                        onkeypress="if(event.key === 'Enter') agregarEncargadoSeleccionado()"
                    >
                    <datalist id="listaModulosDisponibles">
                        ${modulos.map((modulo) => `<option value="${modulo.encargado}"></option>`).join('')}
                    </datalist>
                </div>
                <button onclick="agregarEncargadoSeleccionado()"
                    style="padding: 0.75rem 1.25rem; background: #10b981; color: white; border: none; border-radius: 8px; font-weight: 600; font-size: 0.875rem; cursor: pointer; display: flex; align-items: center; gap: 0.5rem; transition: background 0.2s;"
                    onmouseover="this.style.background='#059669'"
                    onmouseout="this.style.background='#10b981'"
                >
                    <span class="material-symbols-rounded" style="font-size: 1.25rem;">add</span>
                    Agregar
                </button>
            </div>
        </div>

        <div id="cardsEncargadosPlaceholder" style="min-height: 120px;">
            <div style="text-align: center; padding: 2rem; color: #6b7280;">
                <span class="material-symbols-rounded" style="font-size: 2rem; display: block; margin-bottom: 0.75rem;">playlist_add_check</span>
                <p style="font-size: 0.875rem; margin: 0;">Seleccione un Modulo para ver las tallas disponibles</p>
            </div>
        </div>
        <div id="cardsEncargadosSeleccionados" style="display: grid; gap: 1rem;"></div>
    `;

    interfazDiv.innerHTML = html;
}

function agregarEncargadoSeleccionado() {
    const moduloSelector = document.getElementById('moduloSelector');
    if (!moduloSelector || !window.datosDistribucion) return;

    const valor = moduloSelector.value.trim();
    if (!valor) return;

    const modulos = window.datosDistribucion.modulos;
    let moduloId;
    const moduloExistente = modulos.find((m) => m.encargado === valor);

    if (moduloExistente) {
        moduloId = moduloExistente.id;
    } else {
        moduloId = Date.now();
        modulos.push({
            id: moduloId,
            nombre: valor,
            encargado: valor,
            usuarioId: null,
        });
    }

    if (!Array.isArray(window.modulosSeleccionadosDistribucion)) {
        window.modulosSeleccionadosDistribucion = [];
    }

    if (!window.modulosSeleccionadosDistribucion.includes(moduloId)) {
        window.modulosSeleccionadosDistribucion.push(moduloId);
    }

    if (typeof window.renderCardsEncargadosSeleccionados === 'function') {
        window.renderCardsEncargadosSeleccionados();
    }

    moduloSelector.value = '';
}

function renderCardsEncargadosSeleccionados() {
    const container = document.getElementById('cardsEncargadosSeleccionados');
    const placeholder = document.getElementById('cardsEncargadosPlaceholder');
    const selector = document.getElementById('moduloSelector');

    if (!container || !window.datosDistribucion) return;

    const selected = Array.isArray(window.modulosSeleccionadosDistribucion) ? window.modulosSeleccionadosDistribucion : [];
    const tallas = window.datosDistribucion.tallas || [];
    const modulos = window.datosDistribucion.modulos || [];

    if (selected.length === 0) {
        container.innerHTML = '';
        if (placeholder) placeholder.style.display = '';
        if (selector) {
            selector.innerHTML = `
                <option value="">Seleccione un Modulo para asignar tallas...</option>
                ${modulos.map((modulo) => `<option value="${modulo.id}">${modulo.encargado}</option>`).join('')}
            `;
        }
        return;
    }

    if (placeholder) placeholder.style.display = 'none';

    const datalist = document.getElementById('listaModulosDisponibles');
    if (datalist) {
        const selectedSet = new Set(selected.map((id) => parseInt(id)));
        datalist.innerHTML = modulos
            .filter((modulo) => !selectedSet.has(modulo.id))
            .map((modulo) => `<option value="${modulo.encargado}"></option>`)
            .join('');
    }

    container.innerHTML = selected
        .map((moduloId) => {
            const modulo = modulos.find((m) => m.id === moduloId);
            if (!modulo) return '';

            const asignacionesModulo = window.asignacionesPorModulo[moduloId] || {};

            return `
                <div style="background: white; border: 1px solid #d1d5db; border-radius: 12px; padding: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); overflow: hidden;">
                    <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid #f3f4f6; flex-wrap: wrap;">
                        <div style="width: 48px; height: 48px; background: linear-gradient(135deg, #10b981, #059669); border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <span class="material-symbols-rounded" style="color: white; font-size: 1.5rem;">person</span>
                        </div>
                        <div style="flex: 1; min-width: 0;">
                            <h5 style="margin: 0; font-size: 1.125rem; font-weight: 600; color: #1f2937; line-height: 1.3; word-break: break-word;">${modulo.encargado}</h5>
                            <p style="margin: 0.25rem 0 0 0; font-size: 0.875rem; color: #6b7280;">Encargado de produccion</p>
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.5rem; flex-shrink: 0;">
                            <span style="padding: 0.25rem 0.75rem; background: #ecfdf5; color: #059669; font-size: 0.75rem; font-weight: 500; border-radius: 9999px; white-space: nowrap;">
                                ${Object.keys(asignacionesModulo).length} tallas asignadas
                            </span>
                            <button onclick="eliminarModuloSeleccionado(${moduloId})"
                                style="width: 32px; height: 32px; border: none; background: #fee2e2; color: #dc2626; border-radius: 8px; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s; flex-shrink: 0;"
                                onmouseover="this.style.background='#fecaca'"
                                onmouseout="this.style.background='#fee2e2'"
                                title="Eliminar encargado"
                            >
                                <span class="material-symbols-rounded" style="font-size: 1.25rem;">delete</span>
                            </button>
                        </div>
                    </div>

                    ${window.generarHtmlTallasParaEncargado ? window.generarHtmlTallasParaEncargado(tallas, moduloId, asignacionesModulo) : ''}
                </div>
            `;
        })
        .join('');

    refrescarDistribucionUI();
    actualizarResumenAsignaciones();
}

function eliminarModuloSeleccionado(moduloId) {
    if (!Array.isArray(window.modulosSeleccionadosDistribucion)) return;

    window.modulosSeleccionadosDistribucion = window.modulosSeleccionadosDistribucion.filter((id) => id !== moduloId);

    if (window.asignacionesPorModulo && window.asignacionesPorModulo[moduloId]) {
        delete window.asignacionesPorModulo[moduloId];
    }

    renderCardsEncargadosSeleccionados();
}

function toggleTallaSeleccion(talla, moduloId, checked) {
    const input = document.getElementById(`talla_${talla}_modulo_${moduloId}`);
    if (!input) return;

    const maxValue = getMaxDisponibleParaModulo(talla, moduloId);
    input.max = String(maxValue);

    if (!Number.isFinite(maxValue) || maxValue <= 0) {
        input.value = 0;
        input.disabled = true;
        actualizarAsignacion(talla, moduloId, 0);
        refrescarDistribucionUI();
        return;
    }

    if (!checked) {
        input.value = 0;
        input.disabled = true;
        actualizarAsignacion(talla, moduloId, 0);
        refrescarDistribucionUI();
        return;
    }

    input.disabled = false;
    input.value = maxValue;
    actualizarAsignacion(talla, moduloId, maxValue);

    if (typeof window.renderCardsEncargadosSeleccionados === 'function') {
        window.renderCardsEncargadosSeleccionados();
        return;
    }

    refrescarDistribucionUI();
}

function ajustarCantidad(talla, moduloId, delta) {
    const input = document.getElementById(`talla_${talla}_modulo_${moduloId}`);
    if (!input || input.disabled) return;

    const currentValue = parseInt(input.value) || 0;
    const maxValue = parseInt(input.max);
    const newValue = Math.max(0, Math.min(maxValue, currentValue + delta));

    input.value = newValue;
    actualizarAsignacion(talla, moduloId, newValue);

    if (typeof window.renderCardsEncargadosSeleccionados === 'function') {
        window.renderCardsEncargadosSeleccionados();
        return;
    }

    refrescarDistribucionUI();
}

function actualizarAsignacion(talla, moduloId, cantidad, esNueva = false) {
    cantidad = parseInt(cantidad) || 0;

    const maxValue = getMaxDisponibleParaModulo(talla, moduloId);
    if (cantidad > maxValue) {
        cantidad = maxValue;
    }
    if (cantidad < 0) {
        cantidad = 0;
    }

    if (!window.asignacionesPorModulo) {
        window.asignacionesPorModulo = {};
    }

    if (!window.asignacionesPorModulo[moduloId]) {
        window.asignacionesPorModulo[moduloId] = {};
    }

    const color = getColorParaTallaId(talla);

    if (cantidad > 0) {
        window.asignacionesPorModulo[moduloId][talla] = {
            cantidad,
            color,
            es_nueva: esNueva,
        };
    } else {
        delete window.asignacionesPorModulo[moduloId][talla];
    }

    if (Object.keys(window.asignacionesPorModulo[moduloId]).length === 0) {
        delete window.asignacionesPorModulo[moduloId];
    }

    if (typeof window.renderCardsEncargadosSeleccionados === 'function') {
        window.renderCardsEncargadosSeleccionados();
    }

    if (typeof window.renderTallasDisponibles === 'function') {
        window.renderTallasDisponibles();
    }
}

window.mostrarInterfazDistribucionNormal = mostrarInterfazDistribucionNormal;
window.agregarEncargadoSeleccionado = agregarEncargadoSeleccionado;
window.renderCardsEncargadosSeleccionados = renderCardsEncargadosSeleccionados;
window.eliminarModuloSeleccionado = eliminarModuloSeleccionado;
window.toggleTallaSeleccion = toggleTallaSeleccion;
window.ajustarCantidad = ajustarCantidad;
window.actualizarAsignacion = actualizarAsignacion;
window.getTotalAsignadoTalla = getTotalAsignadoTalla;
window.getTotalAsignadoTallaDesdeParcialesEdicion = getTotalAsignadoTallaDesdeParcialesEdicion;
window.actualizarResumenAsignaciones = actualizarResumenAsignaciones;
