import { httpJson } from '../api/http';
import { mostrarError, mostrarExito } from '../ui/messages';
import {
    cargarTalleresDisponibles as cargarTalleresDisponiblesHelper,
    cargarTalleresParaDistribucion as cargarTalleresParaDistribucionHelper,
} from './taller-loaders';
import {
    mostrarContenidoTallerUnico as mostrarContenidoTallerUnicoHelper,
    mostrarContenidoTallerMultiple as mostrarContenidoTallerMultipleHelper,
} from './taller-ui';
import {
    agregarTallerSeleccionado as agregarTallerSeleccionadoHelper,
    actualizarListaTalleresSeleccionados as actualizarListaTalleresSeleccionadosHelper,
    removerTallerSeleccionado as removerTallerSeleccionadoHelper,
    cargarInterfazDistribucionTallerMultiple as cargarInterfazDistribucionTallerMultipleHelper,
} from './taller-multiple-ui';
import {
    construirTallaIdUnico,
    normalizarColor,
    normalizarGenero,
} from './talla-utils';
import {
    getTotalOriginalTallaIdTaller,
    getTotalAsignadoTallaTaller,
    getDisponibleRestanteGlobalTaller,
} from './talla-taller-disponibilidad-utils';

function seleccionarTipoTaller(tipo) {
    window.tipoDistribucionTaller = tipo;

    if (tipo === 'unico') {
        mostrarContenidoTallerUnicoHelper();
    } else {
        mostrarContenidoTallerMultipleHelper();
    }

    const btnVolver = document.getElementById('btnVolver');
    if (btnVolver) {
        btnVolver.style.display = 'inline-flex';
    }
}

function construirMapaAsignacionesTallerDesdeParciales(parciales, talleres) {
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
            const cantidad = parseInt(tallaParcial?.cantidad) || 0;
            const color = tallaParcial?.color_nombre || null;
            const genero = tallaParcial?.genero || tallaParcial?.sexo || 'Sin género';

            if (!nombreTalla || cantidad <= 0) return;

            const tallaIdUnico = `${nombreTalla}_${normalizarColor(color)}_${normalizarGenero(genero)}`;
            mapa[tallerId][tallaIdUnico] = {
                cantidad,
                color,
                genero,
                tallaOriginal: nombreTalla,
                es_nueva_parte: false,
            };
        });
    });

    return mapa;
}

function obtenerTalleresDisponiblesParaAsignarTaller() {
    const cache = Array.isArray(window.talleresDisponiblesAsignacion) ? window.talleresDisponiblesAsignacion : [];
    if (cache.length > 0) {
        return Promise.resolve(cache);
    }

    return httpJson('/api/usuarios/taller')
        .then((response) => {
            if (response?.ok === false) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            return response.json();
        })
        .then((data) => {
            const talleres = Array.isArray(data?.usuarios) ? data.usuarios : [];
            window.talleresDisponiblesAsignacion = talleres;
            window.talleresDisponibles = talleres;
            return talleres;
        });
}

function resolverOCrearTallerAsignacionPorNombre(tallerNombre) {
    const nombreBuscado = String(tallerNombre || '').trim();
    if (!nombreBuscado) return null;

    if (!Array.isArray(window.talleresDisponiblesAsignacion)) {
        window.talleresDisponiblesAsignacion = [];
    }

    let taller = window.talleresDisponiblesAsignacion.find((item) => {
        const nombreItem = String(item?.name || item?.nombre || '').trim().toLowerCase();
        return nombreItem === nombreBuscado.toLowerCase();
    });

    if (taller) {
        return taller;
    }

    const nuevoId = -(Date.now() + Math.floor(Math.random() * 1000));
    taller = {
        id: nuevoId,
        name: nombreBuscado,
        nombre: nombreBuscado,
        esNuevo: true,
    };

    window.talleresDisponiblesAsignacion.push(taller);
    window.talleresDisponibles = window.talleresDisponiblesAsignacion;

    return taller;
}

function renderTallasDisponiblesTallerEdicion() {
    const container = document.getElementById('interfazDistribucionTaller');
    if (!container || !window.datosDistribucion) return;

    const tallas = window.datosDistribucion.tallas || [];

    const tallasDisponibles = tallas.filter((talla) => {
        const tallaIdUnico = construirTallaIdUnico(talla.tallaOriginal, talla.color, talla.genero);
        return getDisponibleRestanteGlobalTaller(tallaIdUnico) > 0;
    });

    if (tallasDisponibles.length === 0) {
        container.innerHTML = `
            <div style="text-align: center; padding: 2rem; color: #6b7280; background: #f9fafb; border-radius: 8px; border: 1px solid #e5e7eb;">
                <span class="material-symbols-rounded" style="font-size: 2rem; display: block; margin-bottom: 0.75rem;">check_circle</span>
                <p style="font-size: 0.875rem; margin: 0;">Todas las tallas han sido asignadas</p>
            </div>
        `;
        return;
    }

    const grupos = window.agruparTallasPorGeneroYColor ? window.agruparTallasPorGeneroYColor(tallasDisponibles) : {};
    let html = '';

    Object.entries(grupos).forEach(([genero, colores]) => {
        html += `
            <div style="background: #fffbeb; border: 1px solid #fcd34d; border-radius: 8px; padding: 1rem; overflow: hidden;">
                <h6 style="margin: 0 0 0.75rem 0; font-size: 0.875rem; font-weight: 600; color: #92400e; text-transform: uppercase; letter-spacing: 0.05em;">
                    ${genero}
                </h6>
                <div style="display: grid; gap: 0.75rem;">
        `;

        Object.entries(colores).forEach(([color, tallasColor]) => {
            const colorDisplay = color === 'Sin color' ? null : color;
            const colorStyle = colorDisplay ? `background: ${colorDisplay}; border: 1px solid #d1d5db;` : 'background: #f3f4f6; border: 1px solid #d1d5db;';

            html += `
                <div style="background: white; border: 1px solid #e5e7eb; border-radius: 6px; padding: 0.75rem;">
                    ${colorDisplay ? `
                    <div style="display: flex; align-items: center; margin-bottom: 0.5rem;">
                        <span style="display: inline-block; width: 16px; height: 16px; ${colorStyle} border-radius: 4px; margin-right: 0.5rem;"></span>
                        <span style="font-size: 0.875rem; font-weight: 500; color: #374151;">${color}</span>
                    </div>
                    ` : ''}
                    <div style="display: grid; gap: 0.5rem;">
            `;

            tallasColor.forEach((talla) => {
                const tallaIdUnico = construirTallaIdUnico(talla.tallaOriginal, color, talla.genero);
                const totalOriginal = getTotalOriginalTallaIdTaller(tallaIdUnico);
                const totalAsignado = getTotalAsignadoTallaTaller(tallaIdUnico);
                const disponible = Math.max(0, totalOriginal - totalAsignado);

                if (disponible <= 0) return;

                html += `
                    <div style="padding: 0.5rem; border: 1px solid #f3f4f6; border-radius: 6px; display: grid; grid-template-columns: 1fr auto auto; align-items: center; gap: 0.75rem; background: #fafafa;">
                        <div style="font-size: 0.875rem; font-weight: 500; color: #374151;">
                            ${talla.tallaOriginal}
                        </div>
                        <div style="font-size: 0.75rem; color: #6b7280; font-weight: 500;">
                            Disponible: <span style="color: #059669; font-weight: 600;">${disponible}</span>
                        </div>
                        <button onclick="asignarTallaDisponibleTaller('${tallaIdUnico}', ${disponible})"
                            style="padding: 0.4rem 0.75rem; background: #f59e0b; color: white; border: none; border-radius: 6px; font-size: 0.75rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 0.3rem; transition: background 0.2s; white-space: nowrap;"
                            onmouseover="this.style.background='#d97706'"
                            onmouseout="this.style.background='#f59e0b'"
                        >
                            <span class="material-symbols-rounded" style="font-size: 0.9rem;">add</span>
                            Asignar
                        </button>
                    </div>
                `;
            });

            html += `
                    </div>
                </div>
            `;
        });

        html += `
                </div>
            </div>
        `;
    });

    container.innerHTML = html;
}

function asignarTallaDisponibleTaller(tallaIdUnico, disponible) {
    let talleres = Array.isArray(window.talleresDisponiblesAsignacion) ? window.talleresDisponiblesAsignacion : [];

    if (talleres.length === 0) {
        obtenerTalleresDisponiblesParaAsignarTaller()
            .then((loaded) => {
                talleres = loaded;
                if (talleres.length === 0) {
                    alert('No hay talleres disponibles para asignar');
                    return;
                }

                abrirModalAsignacionTallaTaller(tallaIdUnico, disponible, talleres);
            })
            .catch((error) => {
                console.error('Error cargando talleres para asignar:', error);
                alert('No se pudieron cargar los talleres disponibles');
            });
        return;
    }

    abrirModalAsignacionTallaTaller(tallaIdUnico, disponible, talleres);
}

function abrirModalAsignacionTallaTaller(tallaIdUnico, disponible, talleres) {
    const talleresHTML = talleres
        .map((taller) => `<option value="${taller?.name || taller?.nombre || 'Taller'}"></option>`)
        .join('');

    const modalHTML = `
        <div style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 10000;">
            <div style="background: white; border-radius: 12px; padding: 2rem; max-width: 400px; width: 90%; box-shadow: 0 20px 25px rgba(0,0,0,0.15);">
                <h5 style="margin: 0 0 1rem 0; font-size: 1.125rem; font-weight: 600; color: #1f2937;">Asignar Talla</h5>

                <div style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-size: 0.875rem; font-weight: 500; color: #374151;">Taller:</label>
                    <input
                        type="text"
                        id="inputTallerAsignar"
                        list="listaTalleresAsignar"
                        placeholder="Escribe para buscar un taller..."
                        autocomplete="off"
                        style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.875rem;"
                    >
                    <datalist id="listaTalleresAsignar">
                        ${talleresHTML}
                    </datalist>
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-size: 0.875rem; font-weight: 500; color: #374151;">Cantidad (max: ${disponible}):</label>
                    <input type="number" id="inputCantidadAsignarTaller" min="1" max="${disponible}" value="${disponible}" style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.875rem;">
                </div>

                <div style="display: flex; gap: 0.75rem;">
                    <button onclick="confirmarAsignacionTallaTaller('${tallaIdUnico}')"
                        style="flex: 1; padding: 0.75rem; background: #10b981; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; transition: background 0.2s;"
                        onmouseover="this.style.background='#059669'"
                        onmouseout="this.style.background='#10b981'"
                    >
                        Asignar
                    </button>
                    <button onclick="cerrarModalAsignacionTalla()"
                        style="flex: 1; padding: 0.75rem; background: #e5e7eb; color: #374151; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; transition: background 0.2s;"
                        onmouseover="this.style.background='#d1d5db'"
                        onmouseout="this.style.background='#e5e7eb'"
                    >
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    `;

    let modalContainer = document.getElementById('modalAsignacionTallaTaller');
    if (!modalContainer) {
        modalContainer = document.createElement('div');
        modalContainer.id = 'modalAsignacionTallaTaller';
        document.body.appendChild(modalContainer);
    }

    modalContainer.innerHTML = modalHTML;
}

function confirmarAsignacionTallaTaller(tallaIdUnico) {
    const inputTaller = document.getElementById('inputTallerAsignar');
    const inputCantidad = document.getElementById('inputCantidadAsignarTaller');

    if (!inputTaller || !inputCantidad) return;

    const tallerNombre = String(inputTaller.value || '').trim();
    const cantidad = parseInt(inputCantidad.value) || 0;

    if (!tallerNombre) {
        alert('Por favor, selecciona un taller');
        return;
    }

    if (cantidad <= 0) {
        alert('Por favor, ingresa una cantidad valida');
        return;
    }

    const tallas = window.datosDistribucion?.tallas || [];
    const tallaInfo = tallas.find((t) => construirTallaIdUnico(t.tallaOriginal, t.color, t.genero) === tallaIdUnico);

    if (!tallaInfo) {
        alert('No se encontro la informacion de la talla');
        return;
    }

    const taller = resolverOCrearTallerAsignacionPorNombre(tallerNombre);

    if (!taller) {
        alert('Por favor, selecciona un taller de la lista o escribe uno valido');
        return;
    }

    const tallerId = parseInt(taller.id);
    const tallerKey = Number.isFinite(tallerId) ? tallerId : taller.name;
    if (!window.asignacionesPorTaller) {
        window.asignacionesPorTaller = {};
    }
    if (!window.asignacionesPorTaller[tallerKey]) {
        window.asignacionesPorTaller[tallerKey] = {};
    }

    window.asignacionesPorTaller[tallerKey][tallaIdUnico] = {
        cantidad,
        color: tallaInfo.color,
        genero: tallaInfo.genero,
        tallaOriginal: tallaInfo.tallaOriginal,
        es_nueva_parte: true,
    };

    cerrarModalAsignacionTaller();

    if (typeof window.renderTallasDisponiblesTallerEdicion === 'function') {
        window.renderTallasDisponiblesTallerEdicion();
    }
}

function cerrarModalAsignacionTaller() {
    const modalContainer = document.getElementById('modalAsignacionTallaTaller');
    if (modalContainer) {
        modalContainer.innerHTML = '';
    }
}

function cargarInterfazDistribucionTallerConDatos(tallas, talleres) {
    const interfazDiv = document.getElementById('interfazDistribucionTaller');

    if (!interfazDiv) {
        console.warn('[TALLER] No existe #interfazDistribucionTaller al cargar datos');
        return;
    }

    if (!tallas || tallas.length === 0) {
        interfazDiv.innerHTML = `
            <div style="text-align: center; padding: 2rem; color: #6b7280;">
                <span class="material-symbols-rounded" style="font-size: 2rem; display: block; margin-bottom: 0.75rem;">info</span>
                <p style="font-size: 0.875rem; margin: 0;">No hay tallas disponibles para esta prenda</p>
            </div>
        `;
        return;
    }

    window.datosDistribucion = { tallas, talleres };

    if (!window.asignacionesPorTaller) {
        window.asignacionesPorTaller = {};
    }

    talleres.forEach((taller) => {
        if (!window.asignacionesPorTaller[taller.id]) {
            window.asignacionesPorTaller[taller.id] = {};
        }
    });

    let html = '<div style="display: grid; gap: 1.5rem;">';

    talleres.forEach((taller) => {
        const nombreTaller = taller.nombre || taller.name || 'Taller sin nombre';
        html += `
            <div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; padding: 1.5rem; overflow: hidden;">
                <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid #e5e7eb;">
                    <div style="flex: 1; min-width: 0;">
                        <h6 style="margin: 0; font-size: 0.875rem; font-weight: 600; color: #1e293b;">${nombreTaller}</h6>
                    </div>
                </div>
                <div id="tallas-taller-${taller.id}" style="display: grid; gap: 0.75rem;">
                    ${generarHtmlTallasParaTaller(tallas, taller.id)}
                </div>
            </div>
        `;
    });

    html += '</div>';
    interfazDiv.innerHTML = html;

    setTimeout(() => {
        tallas.forEach((talla) => {
            talleres.forEach((taller) => {
                const tallaIdUnico = construirTallaIdUnico(talla.tallaOriginal, talla.color, talla.genero);
                actualizarDisponibilidad(tallaIdUnico, taller.id);
            });
        });
    }, 100);
}

function generarHtmlTallasParaTaller(tallas, tallerId) {
    const grupos = window.agruparTallasPorGeneroYColor ? window.agruparTallasPorGeneroYColor(tallas) : {};
    let html = '';

    Object.entries(grupos).forEach(([genero, colores]) => {
        let tieneAlgunaTallaVisible = false;

        Object.entries(colores).forEach(([color, tallasColor]) => {
            tallasColor.forEach((talla) => {
                const tallaIdUnico = construirTallaIdUnico(talla.tallaOriginal, color, talla.genero);
                const cantidadTotal = talla.cantidad;

                let asignado = 0;
                if (window.asignacionesPorTaller && window.asignacionesPorTaller[tallerId]) {
                    asignado = window.asignacionesPorTaller[tallerId][tallaIdUnico] || 0;
                }

                let asignadoEnOtrosTalleres = 0;
                if (window.asignacionesPorTaller) {
                    Object.entries(window.asignacionesPorTaller).forEach(([otroTallerId, asignaciones]) => {
                        if (parseInt(otroTallerId) !== parseInt(tallerId)) {
                            asignadoEnOtrosTalleres += asignaciones[tallaIdUnico] || 0;
                        }
                    });
                }

                const disponibleInicial = cantidadTotal - asignado - asignadoEnOtrosTalleres;
                const isSelected = asignado > 0;

                if (disponibleInicial > 0 || isSelected) {
                    tieneAlgunaTallaVisible = true;
                }
            });
        });

        if (!tieneAlgunaTallaVisible) {
            return;
        }

        html += `
            <div style="margin-bottom: 1.5rem;">
                <h6 style="margin: 0 0 0.75rem 0; font-size: 0.875rem; font-weight: 600; color: #374151; text-transform: uppercase; letter-spacing: 0.05em;">
                    ${genero}
                </h6>
                <div style="display: grid; gap: 0.75rem;">
        `;

        Object.entries(colores).forEach(([color, tallasColor]) => {
            const colorDisplay = color === 'Sin color' ? null : color;
            const colorStyle = colorDisplay ? `background: ${colorDisplay}; border: 1px solid #d1d5db;` : 'background: #f3f4f6; border: 1px solid #d1d5db;';

            let tieneAlgunaTallaVisibleEnColor = false;
            let htmlTallasColor = '';

            tallasColor.forEach((talla) => {
                const tallaIdUnico = construirTallaIdUnico(talla.tallaOriginal, color, talla.genero);
                const cantidadTotal = talla.cantidad;

                let asignado = 0;
                if (window.asignacionesPorTaller && window.asignacionesPorTaller[tallerId]) {
                    asignado = window.asignacionesPorTaller[tallerId][tallaIdUnico] || 0;
                }

                let asignadoEnOtrosTalleres = 0;
                if (window.asignacionesPorTaller) {
                    Object.entries(window.asignacionesPorTaller).forEach(([otroTallerId, asignaciones]) => {
                        if (parseInt(otroTallerId) !== parseInt(tallerId)) {
                            asignadoEnOtrosTalleres += asignaciones[tallaIdUnico] || 0;
                        }
                    });
                }

                const disponibleInicial = cantidadTotal - asignado - asignadoEnOtrosTalleres;
                const maxDisponible = cantidadTotal - asignadoEnOtrosTalleres;
                const isSelected = asignado > 0;

                if (disponibleInicial <= 0 && !isSelected) return;

                tieneAlgunaTallaVisibleEnColor = true;

                htmlTallasColor += `
                    <div class="dist-talla-row ${isSelected ? 'is-selected' : ''}" style="padding: 0.5rem; border: 1px solid #f3f4f6; border-radius: 6px;">
                        <div style="display: grid; grid-template-columns: auto 1fr auto auto; align-items: center; gap: 0.75rem;">
                            <input
                                type="checkbox"
                                class="dist-talla-check"
                                ${isSelected ? 'checked' : ''}
                                onchange="toggleTallaSeleccionTaller('${tallaIdUnico}', ${tallerId}, this.checked)"
                                data-tallaid="${tallaIdUnico}"
                                data-tallerid="${tallerId}"
                            />
                            <div style="font-size: 0.875rem; font-weight: 500; color: #374151;">
                                ${talla.tallaOriginal}
                            </div>
                            <input
                                type="number"
                                class="dist-talla-input"
                                id="talla_${tallaIdUnico}_taller_${tallerId}"
                                data-tallaid="${tallaIdUnico}"
                                data-tallerid="${tallerId}"
                                min="0"
                                max="${maxDisponible}"
                                value="${asignado}"
                                ${isSelected ? '' : 'disabled'}
                                oninput="if(this.value==='')return; const v=parseInt(this.value)||0; const mx=parseInt(this.max)||0; if(v>mx)this.value=mx; if(v<0)this.value=0; actualizarAsignacionTaller('${tallaIdUnico}', ${tallerId}, this.value)"
                                onchange="actualizarAsignacionTaller('${tallaIdUnico}', ${tallerId}, this.value)"
                                style="width: 70px; text-align: center; padding: 0.25rem; border: 1px solid #d1d5db; border-radius: 4px; font-size: 0.875rem; font-weight: 500;"
                            />
                            <div class="dist-disp" data-tallaid="${tallaIdUnico}" data-tallerid="${tallerId}" style="font-size: 0.75rem; color: #6366f1; font-weight: 500;">
                                Disp: ${Math.max(0, disponibleInicial)}
                            </div>
                        </div>
                    </div>
                `;
            });

            if (tieneAlgunaTallaVisibleEnColor) {
                html += `
                    <div style="background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 0.75rem;">
                        <div style="display: flex; align-items: center; margin-bottom: 0.5rem;">
                            ${colorDisplay ? `
                                <span style="display: inline-block; width: 16px; height: 16px; ${colorStyle} border-radius: 4px; margin-right: 0.5rem;"></span>
                                <span style="font-size: 0.875rem; font-weight: 500; color: #374151;">${color}</span>
                            ` : ''}
                        </div>
                        <div style="display: grid; gap: 0.5rem;">
                            ${htmlTallasColor}
                        </div>
                    </div>
                `;
            }
        });

        html += `
                </div>
            </div>
        `;
    });

    return html;
}

function toggleTallaSeleccionTaller(tallaId, tallerId, isChecked) {
    const input = document.getElementById(`talla_${tallaId}_taller_${tallerId}`);
    if (input) {
        input.disabled = !isChecked;
        if (isChecked) {
            const maxDisponible = parseInt(input.max) || 0;
            input.value = maxDisponible;
            actualizarAsignacionTallerConRegeneracion(tallaId, tallerId, maxDisponible, true);
        } else {
            input.value = 0;
            actualizarAsignacionTallerConRegeneracion(tallaId, tallerId, 0, true);
        }
    }
}

function actualizarAsignacionTallerConRegeneracion(tallaId, tallerId, cantidad, regenerarTodos = false) {
    if (!window.asignacionesPorTaller) {
        window.asignacionesPorTaller = {};
    }

    if (!window.asignacionesPorTaller[tallerId]) {
        window.asignacionesPorTaller[tallerId] = {};
    }

    const cantidadNum = parseInt(cantidad) || 0;
    if (cantidadNum > 0) {
        window.asignacionesPorTaller[tallerId][tallaId] = cantidadNum;
    } else {
        delete window.asignacionesPorTaller[tallerId][tallaId];
    }

    actualizarDisponibilidad(tallaId, tallerId);

    if (window.datosDistribucion && window.datosDistribucion.talleres) {
        console.log(`[TOGGLE TALLA] Regenerando todos los talleres despues de toggle de ${tallaId}`);

        window.datosDistribucion.talleres.forEach((taller) => {
            const container = document.getElementById(`tallas-taller-${taller.id}`);
            if (container) {
                container.innerHTML = generarHtmlTallasParaTaller(window.datosDistribucion.tallas, taller.id);
            }
        });

        setTimeout(() => {
            window.datosDistribucion.tallas.forEach((talla) => {
                window.datosDistribucion.talleres.forEach((taller) => {
                    const tallaIdUnico = construirTallaIdUnico(talla.tallaOriginal, talla.color, talla.genero);
                    actualizarDisponibilidad(tallaIdUnico, taller.id);
                });
            });
        }, 50);
    }
}

function actualizarAsignacionTaller(tallaId, tallerId, cantidad) {
    if (!window.asignacionesPorTaller) {
        window.asignacionesPorTaller = {};
    }

    if (!window.asignacionesPorTaller[tallerId]) {
        window.asignacionesPorTaller[tallerId] = {};
    }

    const cantidadAnterior = window.asignacionesPorTaller[tallerId][tallaId] || 0;

    const cantidadNum = parseInt(cantidad) || 0;
    if (cantidadNum > 0) {
        window.asignacionesPorTaller[tallerId][tallaId] = cantidadNum;
    } else {
        delete window.asignacionesPorTaller[tallerId][tallaId];
    }

    actualizarDisponibilidad(tallaId, tallerId);

    const tallaOriginal = window.datosDistribucion?.tallas?.find((t) => {
        const id = construirTallaIdUnico(t.tallaOriginal, t.color, t.genero);
        return id === tallaId;
    });

    if (tallaOriginal) {
        const cantidadTotal = tallaOriginal.cantidad;
        let asignadoTotal = 0;
        if (window.asignacionesPorTaller) {
            Object.entries(window.asignacionesPorTaller).forEach(([, asignaciones]) => {
                asignadoTotal += asignaciones[tallaId] || 0;
            });
        }

        const disponibilidadActual = cantidadTotal - asignadoTotal;
        const asignadoTotalAnterior = asignadoTotal + (cantidadAnterior - cantidadNum);
        const disponibilidadAnterior = cantidadTotal - asignadoTotalAnterior;

        const debeRegenerarTodos =
            (disponibilidadActual === 0 && disponibilidadAnterior > 0) ||
            (disponibilidadAnterior === 0 && disponibilidadActual > 0);

        if (debeRegenerarTodos) {
            console.log(`[ACTUALIZAR ASIGNACION] Cambio en disponibilidad de ${tallaId}. Anterior: ${disponibilidadAnterior}, Actual: ${disponibilidadActual}. Regenerando todos los talleres...`);

            if (window.datosDistribucion && window.datosDistribucion.talleres) {
                window.datosDistribucion.talleres.forEach((taller) => {
                    const container = document.getElementById(`tallas-taller-${taller.id}`);
                    if (container) {
                        container.innerHTML = generarHtmlTallasParaTaller(window.datosDistribucion.tallas, taller.id);
                    }
                });

                setTimeout(() => {
                    window.datosDistribucion.tallas.forEach((talla) => {
                        window.datosDistribucion.talleres.forEach((taller) => {
                            const tallaIdUnico = construirTallaIdUnico(talla.tallaOriginal, talla.color, talla.genero);
                            actualizarDisponibilidad(tallaIdUnico, taller.id);
                        });
                    });
                }, 50);
            }
        } else {
            const container = document.getElementById(`tallas-taller-${tallerId}`);
            if (container) {
                container.innerHTML = generarHtmlTallasParaTaller(window.datosDistribucion.tallas, tallerId);
            }
        }
    }
}

function actualizarDisponibilidad(tallaId, tallerId) {
    const tallaOriginal = window.datosDistribucion?.tallas?.find((t) => {
        const id = construirTallaIdUnico(t.tallaOriginal, t.color, t.genero);
        return id === tallaId;
    });

    if (!tallaOriginal) return;

    const cantidadTotal = tallaOriginal.cantidad;

    let asignadoEnOtrosTalleres = 0;
    if (window.asignacionesPorTaller) {
        Object.entries(window.asignacionesPorTaller).forEach(([otroTallerId, asignaciones]) => {
            if (parseInt(otroTallerId) !== parseInt(tallerId)) {
                asignadoEnOtrosTalleres += asignaciones[tallaId] || 0;
            }
        });
    }

    const asignadoEnEsteTaller = window.asignacionesPorTaller?.[tallerId]?.[tallaId] || 0;
    const disponible = cantidadTotal - asignadoEnEsteTaller - asignadoEnOtrosTalleres;

    const dispElement = document.querySelector(`.dist-disp[data-tallaid="${tallaId}"][data-tallerid="${tallerId}"]`);
    if (dispElement) {
        dispElement.textContent = `Disp: ${Math.max(0, disponible)}`;
    }

    const input = document.getElementById(`talla_${tallaId}_taller_${tallerId}`);
    if (input) {
        const maxDisponible = Math.max(0, cantidadTotal - asignadoEnOtrosTalleres);
        input.max = maxDisponible;

        if (parseInt(input.value) > maxDisponible) {
            input.value = maxDisponible;
            window.asignacionesPorTaller[tallerId][tallaId] = maxDisponible;
        }
    }
}

window.seleccionarTipoTaller = seleccionarTipoTaller;
window.construirMapaAsignacionesTallerDesdeParciales = construirMapaAsignacionesTallerDesdeParciales;
window.agregarTallerSeleccionado = agregarTallerSeleccionadoHelper;
window.actualizarListaTalleresSeleccionados = actualizarListaTalleresSeleccionadosHelper;
window.removerTallerSeleccionado = removerTallerSeleccionadoHelper;
window.cargarInterfazDistribucionTallerMultiple = cargarInterfazDistribucionTallerMultipleHelper;
window.renderTallasDisponiblesTallerEdicion = renderTallasDisponiblesTallerEdicion;
window.asignarTallaDisponibleTaller = asignarTallaDisponibleTaller;
window.confirmarAsignacionTallaTaller = confirmarAsignacionTallaTaller;
window.cerrarModalAsignacionTaller = cerrarModalAsignacionTaller;
window.cargarInterfazDistribucionTallerConDatos = cargarInterfazDistribucionTallerConDatos;
window.toggleTallaSeleccionTaller = toggleTallaSeleccionTaller;
window.actualizarAsignacionTaller = actualizarAsignacionTaller;
window.actualizarDisponibilidad = actualizarDisponibilidad;
