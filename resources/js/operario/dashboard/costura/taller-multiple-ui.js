import { cargarTalleresParaDistribucion as cargarTalleresParaDistribucionHelper } from './taller-loaders';
import {
    mostrarEstadoCargaDistribucionTaller,
    ocultarEstadoCargaDistribucionTaller,
} from './taller-ui';

function normalizarTallerSeleccionado(taller) {
    return {
        ...taller,
        estado: taller?.estado || 'pendiente',
    };
}

function asegurarTallerActivo() {
    if (!Array.isArray(window.talleresSeleccionadosDistribucion)) {
        window.talleresSeleccionadosDistribucion = [];
    }

    const tieneActivo = window.talleresSeleccionadosDistribucion.some((taller) => taller.estado === 'activo');
    const tienePendientes = window.talleresSeleccionadosDistribucion.some((taller) => taller.estado !== 'completado');
    if (!tieneActivo && tienePendientes) {
        const siguiente = window.talleresSeleccionadosDistribucion.find((taller) => taller.estado !== 'completado');
        if (siguiente) {
            siguiente.estado = 'activo';
        }
    }
}

function obtenerIndiceTallerActivo() {
    if (!Array.isArray(window.talleresSeleccionadosDistribucion)) return -1;
    return window.talleresSeleccionadosDistribucion.findIndex((taller) => taller.estado === 'activo');
}

function obtenerIndiceSiguientePendiente(desdeIndice = -1) {
    if (!Array.isArray(window.talleresSeleccionadosDistribucion)) return -1;

    for (let i = desdeIndice + 1; i < window.talleresSeleccionadosDistribucion.length; i += 1) {
        if (window.talleresSeleccionadosDistribucion[i].estado !== 'completado') {
            return i;
        }
    }

    for (let i = 0; i < window.talleresSeleccionadosDistribucion.length; i += 1) {
        if (window.talleresSeleccionadosDistribucion[i].estado !== 'completado') {
            return i;
        }
    }

    return -1;
}

function activarTallerSeleccionado(index) {
    if (!Array.isArray(window.talleresSeleccionadosDistribucion)) return;
    if (index < 0 || index >= window.talleresSeleccionadosDistribucion.length) return;

    window.talleresSeleccionadosDistribucion.forEach((taller, idx) => {
        if (idx === index) {
            taller.estado = 'activo';
        } else if (taller.estado === 'activo') {
            taller.estado = 'pendiente';
        }
    });

    actualizarListaTalleresSeleccionados();
    if (window.talleresSeleccionadosDistribucion.length > 0) {
        cargarInterfazDistribucionTallerMultiple(window.talleresSeleccionadosDistribucion);
    }
}

function marcarTallerComoCompletado(index) {
    if (!Array.isArray(window.talleresSeleccionadosDistribucion)) return;
    if (index < 0 || index >= window.talleresSeleccionadosDistribucion.length) return;

    window.talleresSeleccionadosDistribucion[index].estado = 'completado';
    const siguienteIndice = obtenerIndiceSiguientePendiente(index);

    window.talleresSeleccionadosDistribucion.forEach((taller, idx) => {
        if (idx === siguienteIndice) {
            taller.estado = 'activo';
        } else if (idx !== index && taller.estado === 'activo') {
            taller.estado = 'pendiente';
        }
    });

    actualizarListaTalleresSeleccionados();

    if (window.talleresSeleccionadosDistribucion.length > 0) {
        cargarInterfazDistribucionTallerMultiple(window.talleresSeleccionadosDistribucion);
    }
}

function normalizarColor(color) {
    const colorLimpio = String(color || '').trim().toLowerCase();
    if (!colorLimpio || colorLimpio === 'sin color') {
        return 'sin_color';
    }

    return colorLimpio.replace(/\s+/g, '_');
}

function normalizarGenero(genero) {
    const generoLimpio = String(genero || '').trim().toLowerCase();
    if (!generoLimpio || generoLimpio === 'sin genero') {
        return 'sin_genero';
    }

    return generoLimpio.replace(/\s+/g, '_');
}

export function construirMapaAsignacionesTallerDesdeParciales(parciales, talleres) {
    const mapa = {};

    (parciales || []).forEach((parcial) => {
        const estadoParcial = String(parcial?.estado || parcial?.estado_proceso || parcial?.proceso_estado || '').toUpperCase().trim();
        if (estadoParcial === 'ANULADO') return;

        const encargado = String(parcial?.encargado_costura || parcial?.encargado || '').trim();
        if (!encargado) return;

        const taller = (talleres || []).find((item) => String(item.name || item.nombre || '').trim().toLowerCase() === encargado.toLowerCase());
        const tallerId = taller ? taller.id : encargado;

        if (!mapa[tallerId]) {
            mapa[tallerId] = {};
        }

        (parcial.tallas || []).forEach((tallaParcial) => {
            const nombreTalla = String(tallaParcial?.talla || '').trim();
            const cantidad = parseInt(tallaParcial?.cantidad, 10) || 0;
            const color = tallaParcial?.color_nombre || null;
            const genero = tallaParcial?.genero || tallaParcial?.sexo || 'Sin género';

            if (!nombreTalla || cantidad <= 0) return;

            const tallaIdUnico = `${nombreTalla}_${normalizarColor(color)}_${normalizarGenero(genero)}`;
            mapa[tallerId][tallaIdUnico] = {
                cantidad,
                color,
                genero,
                talla: nombreTalla,
            };
        });
    });

    return mapa;
}

export function agregarTallerSeleccionado() {
    const input = document.getElementById('tallerMultipleSelector');
    const tallerNombre = input?.value.trim();

    if (!tallerNombre) {
        return;
    }

    const nombreBuscado = tallerNombre.toLowerCase();
    let taller = window.talleresDisponibles?.find((item) => String(item?.name || item?.nombre || '').trim().toLowerCase() === nombreBuscado);

    if (!taller) {
        const nuevoId = -(Math.random() * 10000 | 0);
        taller = {
            id: nuevoId,
            name: tallerNombre,
            esNuevo: true,
        };
    }

    if (window.talleresSeleccionadosDistribucion?.find((item) => item.id === taller.id)) {
        input.value = '';
        return;
    }

    if (!window.talleresSeleccionadosDistribucion) {
        window.talleresSeleccionadosDistribucion = [];
    }

    const eraPrimerTaller = window.talleresSeleccionadosDistribucion.length === 0;

    window.talleresSeleccionadosDistribucion.push({
        id: taller.id,
        nombre: taller.name,
        estado: eraPrimerTaller ? 'activo' : 'pendiente',
        esNuevo: taller.esNuevo || false,
    });

    asegurarTallerActivo();
    input.value = '';
    actualizarListaTalleresSeleccionados();
    cargarTalleresParaDistribucionHelper();

    if (window.talleresSeleccionadosDistribucion.length > 0) {
        cargarInterfazDistribucionTallerMultiple(window.talleresSeleccionadosDistribucion);
    }
}

export function actualizarListaTalleresSeleccionados() {
    const listaTalleres = document.getElementById('listaTalleresSeleccionados');
    if (!listaTalleres) return;

    asegurarTallerActivo();

    const count = window.talleresSeleccionadosDistribucion?.length || 0;

    if (!window.talleresSeleccionadosDistribucion || window.talleresSeleccionadosDistribucion.length === 0) {
        listaTalleres.innerHTML = '<p style="text-align: center; color: #9ca3af; padding: 1rem; font-size: 0.875rem; grid-column: 1/-1;">No hay talleres seleccionados</p>';
        return;
    }

    listaTalleres.innerHTML = window.talleresSeleccionadosDistribucion
        .map((taller, index) => {
            const esNuevo = taller.esNuevo;
            const esActivo = taller.estado === 'activo';
            const esCompletado = taller.estado === 'completado';
            const colorFondo = esCompletado ? '#f0fdf4' : esActivo ? '#eff6ff' : '#f8fafc';
            const borde = esCompletado ? '#bbf7d0' : esActivo ? '#bfdbfe' : '#e2e8f0';
            return `
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 0.75rem; background: ${colorFondo}; border: 1px solid ${borde}; border-radius: 8px; gap: 0.75rem;">
                    <div style="display: flex; align-items: center; gap: 0.75rem; flex: 1;">
                        <span class="material-symbols-rounded" style="color: ${esCompletado ? '#059669' : esActivo ? '#2563eb' : '#64748b'}; font-size: 1.25rem;">apartment</span>
                        <div>
                            <div style="font-weight: 600; font-size: 0.875rem; color: #1e293b;">${taller.nombre}</div>
                            <div style="font-size: 0.75rem; color: #64748b;">${esCompletado ? 'Listo' : esActivo ? 'Asignando tallas' : 'Pendiente'}</div>
                            ${esNuevo ? '<div style="font-size: 0.75rem; color: #b45309;">Nuevo taller (se creara al confirmar)</div>' : ''}
                        </div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        ${!esActivo ? `<button type="button" onclick="activarTallerSeleccionado(${index})" style="background: #dbeafe; border: none; border-radius: 6px; padding: 0.5rem 0.75rem; cursor: pointer; color: #1d4ed8; font-size: 0.75rem; font-weight: 600; transition: all 0.2s;">Asignar tallas</button>` : ''}
                        ${esActivo ? `<button type="button" onclick="marcarTallerComoCompletado(${index})" style="background: #dcfce7; border: none; border-radius: 6px; padding: 0.5rem 0.75rem; cursor: pointer; color: #166534; font-size: 0.75rem; font-weight: 600; transition: all 0.2s;">Finalizar</button>` : ''}
                        <button type="button" onclick="removerTallerSeleccionado(${index})" style="background: #fee2e2; border: none; border-radius: 6px; padding: 0.5rem; cursor: pointer; color: #dc2626; transition: all 0.2s;">
                            <span class="material-symbols-rounded" style="font-size: 1rem;">close</span>
                        </button>
                    </div>
                </div>
            `;
        })
        .join('');
}

export function removerTallerSeleccionado(index) {
    if (!window.talleresSeleccionadosDistribucion) return;

    window.talleresSeleccionadosDistribucion.splice(index, 1);
    asegurarTallerActivo();
    actualizarListaTalleresSeleccionados();
    cargarTalleresParaDistribucionHelper();

    if (window.talleresSeleccionadosDistribucion.length > 0) {
        cargarInterfazDistribucionTallerMultiple(window.talleresSeleccionadosDistribucion);
        return;
    }

    const interfazDiv = document.getElementById('interfazDistribucionTaller');
    if (interfazDiv) {
        interfazDiv.innerHTML = '';
    }
}

window.activarTallerSeleccionado = activarTallerSeleccionado;
window.marcarTallerComoCompletado = marcarTallerComoCompletado;

export function cargarInterfazDistribucionTallerMultiple(talleresSeleccionados) {
    if (!window.datosModalCostura) {
        console.error('No hay datos de la prenda disponibles');
        return Promise.reject(new Error('No hay datos de la prenda disponibles'));
    }

    const { prendaId, tipoRecibo } = window.datosModalCostura;
    const cargadorTallas = window.cargarTallasPrenda;
    const renderEdicion = window.renderTallasDisponiblesTallerEdicion;
    const renderConDatos = window.cargarInterfazDistribucionTallerConDatos;
    const botonConfirmar = document.getElementById('btnConfirmarAsignacion');

    if (typeof cargadorTallas !== 'function') {
        return Promise.reject(new Error('No esta disponible el cargador de tallas'));
    }

    mostrarEstadoCargaDistribucionTaller(
        window.datosModalCostura?.esEdicion
            ? 'Cargando tallas disponibles...'
            : 'Cargando tallas para distribuir...'
    );

    if (botonConfirmar) {
        botonConfirmar.disabled = true;
    }

    return cargadorTallas(prendaId, tipoRecibo)
        .then((tallas) => {
            const tallasProcesadas = window.procesarTallasParaDistribucion
                ? window.procesarTallasParaDistribucion(tallas)
                : tallas;

            if (window.datosModalCostura?.esEdicion) {
                window.datosDistribucion = {
                    tallas: tallasProcesadas,
                    talleres: Array.isArray(window.talleresDisponibles) ? window.talleresDisponibles : [],
                };

                if (typeof renderEdicion === 'function') {
                    renderEdicion();
                }

                ocultarEstadoCargaDistribucionTaller();
                if (botonConfirmar) {
                    botonConfirmar.disabled = false;
                }

                return tallasProcesadas;
            }

            if (typeof renderConDatos === 'function') {
                renderConDatos(tallasProcesadas, talleresSeleccionados);
            }

            ocultarEstadoCargaDistribucionTaller();
            if (botonConfirmar) {
                botonConfirmar.disabled = false;
            }

            return tallasProcesadas;
        })
        .catch((error) => {
            console.error('Error cargando datos de Distribucion a taller:', error);
            const interfazDiv = document.getElementById('interfazDistribucionTaller');
            if (interfazDiv) {
                interfazDiv.innerHTML = `
                    <div style="text-align: center; padding: 2rem; color: #dc2626;">
                        <span class="material-symbols-rounded" style="font-size: 2rem; display: block; margin-bottom: 0.75rem;">error</span>
                        <p style="font-size: 0.875rem; margin: 0;">No se pudo cargar la informacion. Por favor, intente nuevamente.</p>
                    </div>
                `;
            }

            if (botonConfirmar) {
                botonConfirmar.disabled = false;
            }

            throw error;
        });
}
