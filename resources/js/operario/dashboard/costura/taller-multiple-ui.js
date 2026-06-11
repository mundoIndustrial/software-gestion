import { cargarTalleresParaDistribucion as cargarTalleresParaDistribucionHelper } from './taller-loaders';
import {
    mostrarEstadoCargaDistribucionTaller,
    ocultarEstadoCargaDistribucionTaller,
} from './taller-ui';

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

    window.talleresSeleccionadosDistribucion.push({
        id: taller.id,
        nombre: taller.name,
        esNuevo: taller.esNuevo || false,
    });

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

    const count = window.talleresSeleccionadosDistribucion?.length || 0;

    const labelElement = document.querySelector('label[style*="Seleccionados"]');
    if (labelElement) {
        labelElement.textContent = `Seleccionados (${count})`;
    }

    if (!window.talleresSeleccionadosDistribucion || window.talleresSeleccionadosDistribucion.length === 0) {
        listaTalleres.innerHTML = '<p style="text-align: center; color: #9ca3af; padding: 1rem; font-size: 0.875rem; grid-column: 1/-1;">No hay talleres seleccionados</p>';
        return;
    }

    listaTalleres.innerHTML = window.talleresSeleccionadosDistribucion
        .map((taller, index) => {
            const esNuevo = taller.esNuevo;
            return `
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 0.75rem; background: ${esNuevo ? '#fef3c7' : '#f3f4f6'}; border: 1px solid ${esNuevo ? '#fcd34d' : '#d1d5db'}; border-radius: 8px;">
                    <div style="display: flex; align-items: center; gap: 0.75rem; flex: 1;">
                        <span class="material-symbols-rounded" style="color: ${esNuevo ? '#f59e0b' : '#6366f1'}; font-size: 1.25rem;">apartment</span>
                        <div>
                            <div style="font-weight: 600; font-size: 0.875rem; color: #1e293b;">${taller.nombre}</div>
                            ${esNuevo ? '<div style="font-size: 0.75rem; color: #b45309;">Nuevo taller (se creara al confirmar)</div>' : ''}
                        </div>
                    </div>
                    <button type="button" onclick="removerTallerSeleccionado(${index})" style="background: #fee2e2; border: none; border-radius: 6px; padding: 0.5rem; cursor: pointer; color: #dc2626; transition: all 0.2s;">
                        <span class="material-symbols-rounded" style="font-size: 1rem;">close</span>
                    </button>
                </div>
            `;
        })
        .join('');
}

export function removerTallerSeleccionado(index) {
    if (!window.talleresSeleccionadosDistribucion) return;

    window.talleresSeleccionadosDistribucion.splice(index, 1);
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
