import { httpJson } from '../api/http';
import { mostrarError, mostrarExito } from '../ui/messages';
import {
    agruparTallasPorGeneroYColor,
    cargarInterfazDistribucionConDatos,
    mostrarErrorDistribucion,
    procesarTallasParaDistribucion,
    procesarUsuariosParaDistribucion,
} from './distribucion-core';
import {
    cargarTalleresDisponibles as cargarTalleresDisponiblesHelper,
    cargarTalleresParaDistribucion as cargarTalleresParaDistribucionHelper,
    cargarUsuariosCostura as cargarUsuariosCosturaHelper,
} from './taller-loaders';
import {
    mostrarContenidoTaller as mostrarContenidoTallerHelper,
    mostrarContenidoTallerUnico as mostrarContenidoTallerUnicoHelper,
    mostrarContenidoTallerMultiple as mostrarContenidoTallerMultipleHelper,
} from './taller-ui';
import {
    construirMapaAsignacionesTallerDesdeParciales as construirMapaAsignacionesTallerDesdeParcialesHelper,
    agregarTallerSeleccionado as agregarTallerSeleccionadoHelper,
    actualizarListaTalleresSeleccionados as actualizarListaTalleresSeleccionadosHelper,
    removerTallerSeleccionado as removerTallerSeleccionadoHelper,
    cargarInterfazDistribucionTallerMultiple as cargarInterfazDistribucionTallerMultipleHelper,
} from './taller-multiple-ui';
import {
    construirTallaIdUnico,
    normalizarColor,
    normalizarGenero,
    parseTallaIdUnico,
} from './talla-utils';
import {
    getTotalOriginalTallaId,
    getColorParaTallaId,
    getGeneroParaTallaId,
    getDisponibleRestanteGlobal,
    getMaxDisponibleParaModulo,
    refrescarDistribucionUI,
} from './talla-disponibilidad-utils';
import {
    getTotalOriginalTallaIdTaller,
    getTotalAsignadoTallaTaller,
    getDisponibleRestanteGlobalTaller,
} from './talla-taller-disponibilidad-utils';
import { cargarTallasPrendaDesdeModal } from './tallas-loader';
import { confirmarAsignacion as confirmarAsignacionFlow } from './asignacion-flow';
import { confirmarPasarACostura as confirmarPasarACosturaFlow } from './pasar-costura-flow';
import './taller-distribucion-flow';
import './distribucion-normal-flow';
import { confirmarDistribucionTaller } from './taller-flow';

export { confirmarAsignacionFlow as confirmarAsignacion };

// Funcion para abrir el modal (exportada para ser usada en costura.js)
export function abrirModalCostura(
    pedidoId,
    prendaId,
    nombre,
    tipoRecibo,
    recibo,
    btnId,
    numeroPedido = null,
    parcialId = null,
    prendaBodegaId = null,
    reciboId = null
) {
    const modal = document.getElementById('modalCostura');
    if (!modal) return;

    // Guardar datos globales
    window.datosModalCostura = {
        pedidoId,
        prendaId,
        prendaBodegaId,
        tipoRecibo,
        btnId,
        recibo,
        nombre,
        numeroPedido,
        parcialId,
        reciboId,
    };
    
    // Resetear seleccion
    window.opcionAsignacionSeleccionada = null;
    window.asignacionesPorModulo = {};
    window.modulosSeleccionadosDistribucion = [];
    window.datosDistribucion = null;
    window.talleresSeleccionadosDistribucion = []; // Inicializar talleres seleccionados
    window.asignacionesPorTaller = {}; // Inicializar asignaciones por taller
    document.getElementById('contenidoAsignacion').innerHTML = '<div style="text-align: center; padding: 2rem; color: #6b7280;"><p>Seleccione un tipo de asignación para continuar</p></div>';
    document.getElementById('btnConfirmarAsignacion').disabled = true;
    
    // Resetear estilos de botones
    const btnCompleto = document.getElementById('btnModuloCompleto');
    const btnDistribuir = document.getElementById('btnDistribuirModulos');
    if (btnCompleto) {
        btnCompleto.style.borderColor = '#e2e8f0';
        btnCompleto.style.background = 'white';
    }
    if (btnDistribuir) {
        btnDistribuir.style.borderColor = '#e2e8f0';
        btnDistribuir.style.background = 'white';
    }
    
    // Resetear subtitulo
    const modalSubtitulo = document.getElementById('modalSubtitulo');
    if (modalSubtitulo) {
        modalSubtitulo.textContent = 'Seleccione el tipo de asignacion';
    }
    
    modal.style.display = 'flex';
}

// Variables globales para el modal
window.opcionAsignacionSeleccionada = null;
window.datosModalCostura = null;

// Funcion para seleccionar opcion de asignacion
export function seleccionarOpcionAsignacion(opcion) {
    window.opcionAsignacionSeleccionada = opcion;
    window.dispatchEvent(new CustomEvent('costura:opcion-asignacion-seleccionada', {
        detail: { opcion }
    }));
    
    // Actualizar estilos de botones
    const btnCompleto = document.getElementById('btnModuloCompleto');
    const btnDistribuir = document.getElementById('btnDistribuirModulos');
    const btnTaller = document.getElementById('btnTaller');
    const btnConfirmar = document.getElementById('btnConfirmarAsignacion');
    const contenidoDiv = document.getElementById('contenidoAsignacion');
    const opcionesDiv = document.getElementById('opcionesAsignacion');
    const modalContent = document.getElementById('modalCosturaContent');
    const modalSubtitulo = document.getElementById('modalSubtitulo');
    const btnVolver = document.getElementById('btnVolver');
    
    // Resetear estilos
    btnCompleto.style.borderColor = '#e2e8f0';
    btnCompleto.style.background = 'white';
    btnDistribuir.style.borderColor = '#e2e8f0';
    btnDistribuir.style.background = 'white';
    if (btnTaller) {
        btnTaller.style.borderColor = '#e2e8f0';
        btnTaller.style.background = 'white';
    }
    
    // Expandir modal para mostrar contenido completo
    modalContent.style.maxWidth = '1200px';
    modalContent.style.maxHeight = '98vh';
    
    // Ocultar opciones y mostrar boton volver
    opcionesDiv.style.display = 'none';
    btnVolver.style.display = 'inline-flex';
    
    // Aplicar estilo seleccionado y cargar contenido
    if (opcion === 'completo') {
        btnCompleto.style.borderColor = '#3b82f6';
        btnCompleto.style.background = '#eff6ff';
        modalSubtitulo.textContent = 'Asignar a Modulo Completo';
        mostrarContenidoModuloCompleto();
        btnConfirmar.style.background = '#3b82f6';
    } else if (opcion === 'distribuir') {
        btnDistribuir.style.borderColor = '#10b981';
        btnDistribuir.style.background = '#ecfdf5';
        modalSubtitulo.textContent = 'Distribuir por Modulos';
        mostrarContenidoDistribuirModulos();
        btnConfirmar.style.background = '#10b981';
    } else if (opcion === 'taller') {
        if (btnTaller) {
            btnTaller.style.borderColor = '#f59e0b';
            btnTaller.style.background = '#fffbeb';
        }
        modalSubtitulo.textContent = 'Distribuir a Taller';
        mostrarContenidoTallerHelper();
        btnConfirmar.style.background = '#f59e0b';
    }
    
    // Habilitar boton confirmar
    btnConfirmar.disabled = false;
}

// Funcion para volver a las opciones de asignacion
export function volverAOpciones() {
    const opcionesDiv = document.getElementById('opcionesAsignacion');
    const contenidoDiv = document.getElementById('contenidoAsignacion');
    const modalContent = document.getElementById('modalCosturaContent');
    const modalSubtitulo = document.getElementById('modalSubtitulo');
    const btnVolver = document.getElementById('btnVolver');
    const btnConfirmar = document.getElementById('btnConfirmarAsignacion');
    const btnCompleto = document.getElementById('btnModuloCompleto');
    const btnDistribuir = document.getElementById('btnDistribuirModulos');
    const btnTaller = document.getElementById('btnTaller');
    
    // Restaurar tamano original
    modalContent.style.maxWidth = '900px';
    
    // Mostrar opciones y ocultar volver
    opcionesDiv.style.display = 'block';
    btnVolver.style.display = 'none';
    
    // Restaurar subti­tulo
    modalSubtitulo.textContent = 'Seleccione el tipo de asignacion';
    
    // Limpiar contenido
    contenidoDiv.innerHTML = '';
    
    // Resetear seleccion
    window.opcionAsignacionSeleccionada = null;
    btnConfirmar.disabled = true;
    btnConfirmar.style.background = '#3b82f6';
    
    // Resetear estilos de botones
    btnCompleto.style.borderColor = '#e2e8f0';
    btnCompleto.style.background = 'white';
    btnDistribuir.style.borderColor = '#e2e8f0';
    btnDistribuir.style.background = 'white';
    if (btnTaller) {
        btnTaller.style.borderColor = '#e2e8f0';
        btnTaller.style.background = 'white';
    }
}

// Funcion para mostrar contenido de Modulo completo
function mostrarContenidoModuloCompleto() {
    const contenidoDiv = document.getElementById('contenidoAsignacion');
    
    contenidoDiv.innerHTML = `
        <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 12px; padding: 1.5rem;">
            <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem;">
                <div style="width: 40px; height: 40px; background: #3b82f6; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <span class="material-symbols-rounded" style="color: white; font-size: 1.25rem;">inventory_2</span>
                </div>
                <div style="flex: 1; min-width: 0;">
                    <h5 style="margin: 0; font-size: 1rem; font-weight: 600; color: #1e40af; line-height: 1.3;">asignacion a Modulo Completo</h5>
                    <p style="margin: 0.25rem 0 0 0; font-size: 0.875rem; color: #64748b; line-height: 1.3;">Todas las prendas seran asignadas a un solo encargado</p>
                </div>
            </div>
            
            <!-- Selector de encargado (Editable) -->
            <div style="background: white; border-radius: 8px; padding: 1rem; border: 1px solid #dbeafe;">
                <label style="display: block; margin-bottom: 0.75rem; font-weight: 600; font-size: 0.875rem; color: #1e40af;">
                    <span class="material-symbols-rounded" style="vertical-align: middle; margin-right: 0.5rem; font-size: 1rem;">person</span>
                    Encargado de Costura:
                </label>
                <div style="position: relative;">
                    <input type="text" id="costuraEncargado" list="listaEncargados" 
                        placeholder="Seleccione o escriba un encargado o taller..." 
                        style="width: 100%; padding: 0.75rem; border: 2px solid #d1d5db; border-radius: 8px; background: white; font-size: 0.875rem; transition: all 0.2s; outline: none;"
                        onfocus="this.style.borderColor='#3b82f6'; this.style.boxShadow='0 0 0 3px rgba(59, 130, 246, 0.1)';"
                        onblur="this.style.borderColor='#d1d5db'; this.style.boxShadow='none';"
                    >
                    <datalist id="listaEncargados"></datalist>
                </div>
                <p style="margin: 0.5rem 0 0 0; font-size: 0.75rem; color: #64748b; line-height: 1.3;">
                    <span class="material-symbols-rounded" style="vertical-align: middle; margin-right: 0.25rem; font-size: 0.875rem;">info</span>
                    Puede seleccionar un encargado de la lista o escribir el nombre de un taller directamente.
                </p>
            </div>
            
            <!-- Resumen visual -->
            <div style="margin-top: 1.5rem; padding: 0.75rem; background: #f0f9ff; border-radius: 8px; border: 1px solid #bae6fd;">
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <span class="material-symbols-rounded" style="color: #0c4a6e; font-size: 1rem;">assignment_turned_in</span>
                    <div style="flex: 1; min-width: 0;">
                        <p style="margin: 0; font-size: 0.75rem; font-weight: 600; color: #0c4a6e;">Estado de la asignacion:</p>
                        <p id="textoEstadoAsignacion" style="margin: 0.25rem 0 0 0; font-size: 0.75rem; color: #0c4a6e;">Pendiente de seleccionar encargado</p>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Cargar usuarios de costura
    if (window.datosModalCostura) {
        cargarUsuariosCosturaHelper(window.datosModalCostura.tipoRecibo);
    }

    // Agregar listeners para actualizar el estado visual
    setTimeout(() => {
        const input = document.getElementById('costuraEncargado');
        const textoEstado = document.getElementById('textoEstadoAsignacion');

        const actualizarEstado = () => {
            const val = input?.value.trim();

            if (val) {
                if (textoEstado) textoEstado.textContent = `Asignado a: ${val}`;
            } else {
                if (textoEstado) textoEstado.textContent = 'Pendiente de seleccionar encargado';
            }
        };

        if (input) {
            input.oninput = actualizarEstado;
            input.onchange = actualizarEstado;
        }
    }, 100);
}

// Funcion para mostrar contenido de distribucion por Modulos
function mostrarContenidoDistribuirModulos() {
    const contenidoDiv = document.getElementById('contenidoAsignacion');
    
    contenidoDiv.innerHTML = `
        <div style="background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 12px; padding: 1.5rem;">
            <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem;">
                <div style="width: 40px; height: 40px; background: #10b981; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <span class="material-symbols-rounded" style="color: white; font-size: 1.25rem;">share</span>
                </div>
                <div style="flex: 1; min-width: 0;">
                    <h5 style="margin: 0; font-size: 1rem; font-weight: 600; color: #047857; line-height: 1.3;">Distribucion por Modulos</h5>
                    <p style="margin: 0.25rem 0 0 0; font-size: 0.875rem; color: #64748b; line-height: 1.3;">Reparta las prendas entre diferentes Modulos</p>
                </div>
            </div>
            
            <!-- Aquí se cargará la interfaz de Distribucion -->
            <div id="interfazDistribucion" style="margin-top: 1rem;">
            </div>
        </div>
    `;
    
    // Cargar datos reales de tallas y Modulos
    cargarDatosDistribucion();
}

// Funcion para cargar datos reales de Distribucion
function cargarDatosDistribucion() {
    if (!window.datosModalCostura) {
        console.error('No hay datos de la prenda disponibles');
        return;
    }
    
    const { prendaId, tipoRecibo } = window.datosModalCostura;
    
    // Cargar tallas de la prenda
    Promise.all([
        cargarTallasPrendaDesdeModal(),
        cargarUsuariosPorTipo(tipoRecibo)
    ])
    .then(([tallas, usuarios]) => {
        console.log('Tallas cargadas:', tallas);
        console.log('Usuarios cargados:', usuarios);
        
        // Procesar tallas al formato esperado
        const tallasProcesadas = procesarTallasParaDistribucion(tallas);
        const usuariosProcesados = procesarUsuariosParaDistribucion(usuarios);
        
        // Cargar interfaz con datos reales
        cargarInterfazDistribucionConDatos(tallasProcesadas, usuariosProcesados);
    })
    .catch(error => {
        console.error('Error cargando datos de Distribucion:', error);
        mostrarErrorDistribucion();
    });
}

// Funcion para cargar usuarios segun tipo de recibo
function cargarUsuariosPorTipo(tipoRecibo) {
    const qs = new URLSearchParams();
    const tr = String(tipoRecibo || '').trim().toUpperCase();
    if (tr) {
        qs.set('tipo_recibo', tr);
    }
    const url = qs.toString() ? `/api/usuarios/costura?${qs.toString()}` : '/api/usuarios/costura';
    
    return httpJson(url)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                return data.usuarios || [];
            }
            throw new Error(data.message || 'Error cargando usuarios');
        });
}

window.procesarTallasParaDistribucion = procesarTallasParaDistribucion;
window.agruparTallasPorGeneroYColor = agruparTallasPorGeneroYColor;
window.procesarUsuariosParaDistribucion = procesarUsuariosParaDistribucion;
window.mostrarErrorDistribucion = mostrarErrorDistribucion;
window.cargarInterfazDistribucionConDatos = cargarInterfazDistribucionConDatos;
window.generarHtmlTallasParaEncargado = generarHtmlTallasParaEncargado;

// Funcion para mostrar cards de encargados (modo edicion)
window.mostrarCardsEncargados = function(tallas, modulos) {
    const interfazDiv = document.getElementById('interfazDistribucion');
    if (!interfazDiv) {
        console.warn('[DISTRIBUCION] No existe #interfazDistribucion en modo edicion');
        return;
    }
    
    // Guardar datos globales PRIMERO para que este disponibles en renderCardsEncargadosSeleccionados
    window.datosDistribucion = { tallas, modulos };
    
    let html = `
        <div style="margin-top: 0.5rem;">
            <h5 style="margin: 0 0 1rem 0; font-size: 1rem; font-weight: 600; color: #1f2937; display: flex; align-items: center; gap: 0.5rem;">
                <span class="material-symbols-rounded" style="color: #f59e0b;">inventory_2</span>
                Tallas Disponibles para Asignar
            </h5>
            <div id="tallasDisponiblesContainer" style="display: grid; gap: 1rem;"></div>
        </div>
    `;

    interfazDiv.innerHTML = html;
    
    // Renderizar tallas disponibles
    if (typeof window.renderTallasDisponibles === 'function') {
        window.renderTallasDisponibles();
    }
}

// Funcion para generar HTML de tallas para un encargado especifico
function generarHtmlTallasParaEncargado(tallas, moduloId, asignaciones) {
    // Obtener datos de parciales si estamos en modo edicion
    const parcialesEdicion = window.__datosParcialesEdicion || [];
    const esEdicion = window.datosModalCostura?.esEdicion || false;
    
    // Agrupar tallas por parte (numero de parcial)
    // En modo edicion, solo mostrar las partes nuevas (is_nueva_parte: true)
    const tallasPorParte = agruparTallasPorParte(tallas, asignaciones, parcialesEdicion, moduloId, esEdicion);
    
    let html = '';
    
    // Si hay agrupacion por partes, mostrar asi­
    if (Object.keys(tallasPorParte).length > 0) {
        Object.entries(tallasPorParte).forEach(([numeroParte, datosPartes]) => {
            html += `
                <div style="margin-bottom: 1.5rem; border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden;">
                    <!-- Header de la parte -->
                    <div style="background: #f9fafb; padding: 0.75rem 1rem; border-bottom: 1px solid #e5e7eb; display: flex; align-items: center; gap: 0.75rem;">
                        <span class="material-symbols-rounded" style="color: #6b7280; font-size: 1.25rem;">inventory_2</span>
                        <h6 style="margin: 0; font-size: 0.875rem; font-weight: 600; color: #374151; text-transform: uppercase; letter-spacing: 0.05em;">
                            Parte #${numeroParte}
                        </h6>
                        <span style="margin-left: auto; padding: 0.25rem 0.5rem; background: #f3f4f6; color: #6b7280; font-size: 0.75rem; font-weight: 500; border-radius: 4px;">
                            ${datosPartes.totalTallas} tallas
                        </span>
                    </div>
                    
                    <!-- Contenido de la parte -->
                    <div style="padding: 1rem;">
                        ${generarHtmlTallasParaParte(datosPartes.tallas, moduloId, asignaciones)}
                    </div>
                </div>
            `;
        });
    } else if (!esEdicion) {
        // Fallback: agrupar por genero y color si no hay informacion de partes (solo en modo normal, no en edicion)
        const grupos = agruparTallasPorGeneroYColor(tallas);
        Object.entries(grupos).forEach(([genero, colores]) => {
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
                
                html += `
                    <div style="background: #fafafa; border: 1px solid #e5e7eb; border-radius: 8px; padding: 0.75rem;">
                        ${colorDisplay ? `
                        <div style="display: flex; align-items: center; margin-bottom: 0.5rem;">
                            <span style="display: inline-block; width: 16px; height: 16px; ${colorStyle} border-radius: 4px; margin-right: 0.5rem;"></span>
                            <span style="font-size: 0.875rem; font-weight: 500; color: #374151;">${color}</span>
                        </div>
                        ` : ''}
                        <div style="display: grid; gap: 0.5rem;">
                `;
                
                tallasColor.forEach(talla => {
                    const tallaIdUnico = construirTallaIdUnico(talla.tallaOriginal, color, talla.genero);
                    let asignado = 0;
                    if (typeof asignaciones[tallaIdUnico] === 'object' && asignaciones[tallaIdUnico] !== null) {
                        asignado = asignaciones[tallaIdUnico].cantidad || 0;
                    } else if (typeof asignaciones[tallaIdUnico] === 'number') {
                        asignado = asignaciones[tallaIdUnico];
                    }
                    
                    const totalBase = getTotalOriginalTallaId(tallaIdUnico, talla.cantidad);
                    const maxDisponible = getMaxDisponibleParaModulo(tallaIdUnico, moduloId, talla.cantidad);
                    const disponible = typeof getDisponibleRestanteGlobal === 'function'
                        ? getDisponibleRestanteGlobal(tallaIdUnico, talla.cantidad)
                        : Math.max(0, totalBase - (typeof window.getTotalAsignadoTalla === 'function' ? (parseInt(window.getTotalAsignadoTalla(tallaIdUnico, null)) || 0) : 0));
                    const isSelected = asignado > 0;

                    if (asignado > maxDisponible) {
                        window.actualizarAsignacion(tallaIdUnico, moduloId, maxDisponible);
                    }
                    
                    html += `
                        <div class="dist-talla-row ${isSelected ? 'is-selected' : ''}" style="padding: 0.5rem; border: 1px solid #f3f4f6; border-radius: 6px;">
                            <div style="display: grid; grid-template-columns: auto 1fr auto auto; align-items: center; gap: 0.75rem;">
                                <input
                                    type="checkbox"
                                    class="dist-talla-check"
                                    ${isSelected ? 'checked' : ''}
                                    onchange="toggleTallaSeleccion('${tallaIdUnico}', ${moduloId}, this.checked)"
                                    data-tallaid="${tallaIdUnico}"
                                    data-moduloid="${moduloId}"
                                    data-cantidad="${talla.cantidad}"
                                />
                                <div style="font-size: 0.875rem; font-weight: 500; color: #374151;">
                                    ${talla.tallaOriginal}
                                    ${isSelected ? '<span style="color: #059669; font-size: 0.75rem; margin-left: 0.5rem;"> Asignado</span>' : ''}
                                </div>
                                <input
                                    type="number"
                                    class="dist-talla-input"
                                    id="talla_${tallaIdUnico}_modulo_${moduloId}"
                                    data-tallaid="${tallaIdUnico}"
                                    data-moduloid="${moduloId}"
                                    data-cantidad="${talla.cantidad}"
                                    min="0"
                                    max="${maxDisponible}"
                                    value="${asignado}"
                                    ${isSelected ? '' : 'disabled'}
                                    oninput="if(this.value==='')return; const v=parseInt(this.value)||0; const mx=parseInt(this.max)||0; if(v>mx)this.value=mx; if(v<0)this.value=0;"
                                    onchange="actualizarAsignacion('${tallaIdUnico}', ${moduloId}, this.value)"
                                    style="width: 70px; text-align: center; padding: 0.25rem; border: 1px solid #d1d5db; border-radius: 4px; font-size: 0.875rem; font-weight: 500;"
                                />
                                <div class="dist-disp" data-tallaid="${tallaIdUnico}" data-moduloid="${moduloId}" data-cantidad="${talla.cantidad}" style="font-size: 0.75rem; color: #dc2626; font-weight: 500;">
                                    Disp: ${disponible}
                                </div>
                            </div>
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
    }
    
    return html;
}

// Funcion para agrupar tallas por numero de parte
function agruparTallasPorParte(tallas, asignaciones, parcialesEdicion, moduloId, esEdicion = false) {
    const tallasPorParte = {};
    
    if (!parcialesEdicion || parcialesEdicion.length === 0) {
        console.log('[AGRUPAR TALLAS POR PARTE] No hay parciales de edicion');
        return tallasPorParte;
    }
    
    // Obtener el Modulo actual
    const modulos = window.datosDistribucion?.modulos || [];
    const moduloActual = modulos.find(m => m.id === moduloId);
    
    if (!moduloActual) {
        console.log('[AGRUPAR TALLAS POR PARTE] No se encontro Modulo:', moduloId);
        return tallasPorParte;
    }
    
    console.log('[AGRUPAR TALLAS POR PARTE] Modulo actual:', moduloActual);
    console.log('[AGRUPAR TALLAS POR PARTE] Parciales de edicion:', parcialesEdicion);
    console.log('[AGRUPAR TALLAS POR PARTE] Asignaciones del Modulo:', asignaciones);
    console.log('[AGRUPAR TALLAS POR PARTE] Es edicion:', esEdicion);
    
    // Buscar los parciales que corresponden a este Modulo
    let parcialesDelModulo = parcialesEdicion.filter(p => {
        const encargado = p.encargado || 'SIN ASIGNAR';
        const match = moduloActual.encargado.toLowerCase().trim() === encargado.toLowerCase().trim();
        console.log(`[AGRUPAR TALLAS POR PARTE] Comparando: "${moduloActual.encargado.toLowerCase().trim()}" === "${encargado.toLowerCase().trim()}" = ${match}`);
        return match;
    });
    
    // En modo edicion, solo mostrar las partes nuevas (is_nueva_parte: true)
    if (esEdicion) {
        parcialesDelModulo = parcialesDelModulo.filter(p => p.is_nueva_parte === true);
        console.log('[AGRUPAR TALLAS POR PARTE] Filtrando solo partes nuevas en modo edicion:', parcialesDelModulo);
    }
    
    console.log('[AGRUPAR TALLAS POR PARTE] Parciales del Modulo:', parcialesDelModulo);
    
    if (parcialesDelModulo.length === 0) {
        console.log('[AGRUPAR TALLAS POR PARTE] No hay parciales para este Modulo');
        return tallasPorParte;
    }
    
    // Agrupar tallas por numero de parte
    parcialesDelModulo.forEach(parcial => {
        const numeroParte = parcial.consecutivo_parcial;
        
        if (!tallasPorParte[numeroParte]) {
            tallasPorParte[numeroParte] = {
                tallas: [],
                totalTallas: 0
            };
        }
        
        // Agregar las tallas de este parcial
        (parcial.tallas || []).forEach(tallaParcial => {
            const nombreTalla = tallaParcial.talla;
            const color = tallaParcial.color_nombre || null;
            const genero = tallaParcial.genero || 'Sin genero';
            
            // Buscar la talla en el array de tallas para obtener mas informacion
            const tallaInfo = tallas.find(t => {
                const baseT = (t.tallaOriginal || (String(t.talla || '').split(' ')[0])) || '';
                return baseT === nombreTalla;
            });
            
            if (tallaInfo) {
                tallasPorParte[numeroParte].tallas.push({
                    ...tallaInfo,
                    tallaOriginal: nombreTalla,
                    color: color || tallaInfo.color,
                    genero: genero,
                    cantidad: tallaParcial.cantidad
                });
                tallasPorParte[numeroParte].totalTallas++;
            }
        });
    });
    
    console.log('[AGRUPAR TALLAS POR PARTE] Resultado final:', tallasPorParte);
    return tallasPorParte;
}

// Funcion para generar HTML de tallas para una parte especifico
function generarHtmlTallasParaParte(tallasParte, moduloId, asignaciones) {
    const grupos = agruparTallasPorGeneroYColor(tallasParte);
    let html = '';
    
    Object.entries(grupos).forEach(([genero, colores]) => {
        html += `
            <div style="margin-bottom: 1rem;">
                <h6 style="margin: 0 0 0.5rem 0; font-size: 0.8rem; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em;">
                    ${genero}
                </h6>
                <div style="display: grid; gap: 0.5rem;">
        `;
        
        Object.entries(colores).forEach(([color, tallasColor]) => {
            const colorDisplay = color === 'Sin color' ? null : color;
            const colorStyle = colorDisplay ? `background: ${colorDisplay}; border: 1px solid #d1d5db;` : 'background: #f3f4f6; border: 1px solid #d1d5db;';
            
            html += `
                <div style="background: #fafafa; border: 1px solid #e5e7eb; border-radius: 6px; padding: 0.5rem;">
                    ${colorDisplay ? `
                    <div style="display: flex; align-items: center; margin-bottom: 0.25rem; font-size: 0.75rem;">
                        <span style="display: inline-block; width: 12px; height: 12px; ${colorStyle} border-radius: 3px; margin-right: 0.4rem;"></span>
                        <span style="font-weight: 500; color: #374151;">${color}</span>
                    </div>
                    ` : ''}
                    <div style="display: grid; gap: 0.4rem;">
            `;
            
            tallasColor.forEach(talla => {
                const tallaIdUnico = construirTallaIdUnico(talla.tallaOriginal, color, talla.genero);
                let asignado = 0;
                if (typeof asignaciones[tallaIdUnico] === 'object' && asignaciones[tallaIdUnico] !== null) {
                    asignado = asignaciones[tallaIdUnico].cantidad || 0;
                } else if (typeof asignaciones[tallaIdUnico] === 'number') {
                    asignado = asignaciones[tallaIdUnico];
                }
                
                const totalBase = getTotalOriginalTallaId(tallaIdUnico, talla.cantidad);
                const totalAsignado = typeof window.getTotalAsignadoTalla === 'function'
                    ? (parseInt(window.getTotalAsignadoTalla(tallaIdUnico, null)) || 0)
                    : 0;
                const maxDisponible = Math.max(0, totalBase - totalAsignado);
                const disponible = Math.max(0, totalBase - totalAsignado);
                const isSelected = asignado > 0;
                
                if (asignado > maxDisponible) {
                    window.actualizarAsignacion(tallaIdUnico, moduloId, maxDisponible);
                }
                
                html += `
                    <div class="dist-talla-row ${isSelected ? 'is-selected' : ''}" style="padding: 0.4rem; border: 1px solid #f3f4f6; border-radius: 4px; display: grid; grid-template-columns: auto 1fr auto auto; align-items: center; gap: 0.5rem;">
                        <input
                            type="checkbox"
                            class="dist-talla-check"
                            ${isSelected ? 'checked' : ''}
                            onchange="toggleTallaSeleccion('${tallaIdUnico}', ${moduloId}, this.checked)"
                            data-tallaid="${tallaIdUnico}"
                            data-moduloid="${moduloId}"
                            data-cantidad="${talla.cantidad}"
                            style="width: 16px; height: 16px; cursor: pointer;"
                        />
                        <div style="font-size: 0.8rem; font-weight: 500; color: #374151;">
                            ${talla.tallaOriginal}
                            ${isSelected ? '<span style="color: #059669; font-size: 0.7rem; margin-left: 0.3rem;"> âœ“</span>' : ''}
                        </div>
                        <input
                            type="number"
                            class="dist-talla-input"
                            id="talla_${tallaIdUnico}_modulo_${moduloId}"
                            data-tallaid="${tallaIdUnico}"
                            data-moduloid="${moduloId}"
                            data-cantidad="${talla.cantidad}"
                            min="0"
                            max="${maxDisponible}"
                            value="${asignado}"
                            ${isSelected ? '' : 'disabled'}
                            oninput="if(this.value==='')return; const v=parseInt(this.value)||0; const mx=parseInt(this.max)||0; if(v>mx)this.value=mx; if(v<0)this.value=0;"
                            onchange="actualizarAsignacion('${tallaIdUnico}', ${moduloId}, this.value)"
                            style="width: 60px; text-align: center; padding: 0.2rem; border: 1px solid #d1d5db; border-radius: 3px; font-size: 0.8rem; font-weight: 500;"
                        />
                        <div class="dist-disp" data-tallaid="${tallaIdUnico}" data-moduloid="${moduloId}" data-cantidad="${talla.cantidad}" style="font-size: 0.7rem; color: #dc2626; font-weight: 500; white-space: nowrap;">
                            ${disponible}
                        </div>
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
    
    return html;
}

window.renderTallasDisponibles = function() {
    const container = document.getElementById('tallasDisponiblesContainer');
    if (!container || !window.datosDistribucion) return;

    const tallas = window.datosDistribucion.tallas || [];
    
    // Calcular tallas disponibles (no asignadas o parcialmente asignadas)
    const tallasDisponibles = tallas.filter(talla => {
        const tallaIdUnico = construirTallaIdUnico(talla.tallaOriginal, talla.color, talla.genero);
        const totalAsignado = window.getTotalAsignadoTalla(tallaIdUnico, null);
        const totalOriginal = getTotalOriginalTallaId(tallaIdUnico, talla.cantidad);
        return totalAsignado < totalOriginal;
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

    // Agrupar tallas disponibles por genero y color
    const grupos = agruparTallasPorGeneroYColor(tallasDisponibles);
    
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
            
            tallasColor.forEach(talla => {
                const tallaIdUnico = construirTallaIdUnico(talla.tallaOriginal, color, talla.genero);
                const totalOriginal = getTotalOriginalTallaId(tallaIdUnico, talla.cantidad);
                const totalAsignado = window.getTotalAsignadoTalla(tallaIdUnico, null);
                const disponible = Math.max(0, totalOriginal - totalAsignado);
                
                html += `
                    <div style="padding: 0.5rem; border: 1px solid #f3f4f6; border-radius: 6px; display: grid; grid-template-columns: 1fr auto auto; align-items: center; gap: 0.75rem; background: #fafafa;">
                        <div style="font-size: 0.875rem; font-weight: 500; color: #374151;">
                            ${talla.tallaOriginal}
                        </div>
                        <div style="font-size: 0.75rem; color: #6b7280; font-weight: 500;">
                            Disponible: <span style="color: #059669; font-weight: 600;">${disponible}</span>
                        </div>
                        <button onclick="asignarTallaDisponible('${tallaIdUnico}', ${disponible})"
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

// Funcion para asignar una talla disponible a un encargado
window.asignarTallaDisponible = function(tallaIdUnico, disponible) {
    // Mostrar modal para seleccionar encargado y cantidad
    const modulos = window.datosDistribucion?.modulos || [];
    
    if (modulos.length === 0) {
        alert('No hay encargados disponibles para asignar');
        return;
    }
    
    // Cargar todos los encargados disponibles
    const modulosHTML = modulos
        .map((modulo) => `<option value="${modulo.id}">${modulo?.encargado || 'Encargado'}</option>`)
        .join('');
    
    const modalHTML = `
        <div style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 10000;">
            <div style="background: white; border-radius: 12px; padding: 2rem; max-width: 400px; width: 90%; box-shadow: 0 20px 25px rgba(0,0,0,0.15);">
                <h5 style="margin: 0 0 1rem 0; font-size: 1.125rem; font-weight: 600; color: #1f2937;">Asignar Talla</h5>
                
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-size: 0.875rem; font-weight: 500; color: #374151;">Encargado:</label>
                    <select id="selectEncargadoAsignar" style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.875rem;">
                        <option value="" selected>Selecciona un encargado</option>
                        ${modulosHTML}
                    </select>
                </div>
                
                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-size: 0.875rem; font-weight: 500; color: #374151;">Cantidad (max: ${disponible}):</label>
                    <input type="number" id="inputCantidadAsignar" min="1" max="${disponible}" value="${disponible}" style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.875rem;">
                </div>
                
                <div style="display: flex; gap: 0.75rem;">
                    <button onclick="confirmarAsignacionTalla('${tallaIdUnico}')"
                        style="flex: 1; padding: 0.75rem; background: #10b981; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; transition: background 0.2s;"
                        onmouseover="this.style.background='#059669'"
                        onmouseout="this.style.background='#10b981'"
                    >
                        Asignar
                    </button>
                    <button onclick="cerrarModalAsignacion()"
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
    
    // Crear contenedor para el modal
    let modalContainer = document.getElementById('modalAsignacionTalla');
    if (!modalContainer) {
        modalContainer = document.createElement('div');
        modalContainer.id = 'modalAsignacionTalla';
        document.body.appendChild(modalContainer);
    }
    
    modalContainer.innerHTML = modalHTML;
    
}

// Funcion para confirmar la asignacion
window.confirmarAsignacionTalla = function(tallaIdUnico) {
    const selectEncargado = document.getElementById('selectEncargadoAsignar');
    const inputCantidad = document.getElementById('inputCantidadAsignar');
    
    if (!selectEncargado || !inputCantidad) return;
    
    const moduloId = parseInt(selectEncargado.value);
    const cantidad = parseInt(inputCantidad.value) || 0;
    
    if (!Number.isFinite(moduloId)) {
        alert('Por favor, selecciona un encargado');
        return;
    }
    
    if (cantidad <= 0) {
        alert('Por favor, ingresa una cantidad valida');
        return;
    }
    
    // Obtener informacion de la talla
    const tallas = window.datosDistribucion?.tallas || [];
    const tallaInfo = tallas.find(t => {
        const id = construirTallaIdUnico(t.tallaOriginal, t.color, t.genero);
        return id === tallaIdUnico;
    });
    
    if (!tallaInfo) {
        alert('No se encontro la informacion de la talla');
        return;
    }

    if (!Array.isArray(window.modulosSeleccionadosDistribucion)) {
        window.modulosSeleccionadosDistribucion = [];
    }
    if (!window.modulosSeleccionadosDistribucion.includes(moduloId)) {
        window.modulosSeleccionadosDistribucion.push(moduloId);
    }

    // Guardar primero la asignacion en memoria para que el calculo de disponibilidad
    // no se coma la propia asignacion al recrear la parte virtual en edicion.
    window.actualizarAsignacion(tallaIdUnico, moduloId, cantidad, true);

    // Crear una nueva parte virtual si es necesario
    crearNuevaParteVirtual(moduloId, tallaIdUnico, tallaInfo, cantidad);
    
    // Cerrar modal
    window.cerrarModalAsignacion();
    
    // Renderizar de nuevo
    if (typeof window.renderCardsEncargadosSeleccionados === 'function') {
        window.renderCardsEncargadosSeleccionados();
    }
    if (typeof window.renderTallasDisponibles === 'function') {
        window.renderTallasDisponibles();
    }
}

// Funcion para crear una nueva parte virtual cuando se asigna una talla disponible
function crearNuevaParteVirtual(moduloId, tallaIdUnico, tallaInfo, cantidad) {
    if (!window.__datosParcialesEdicion) {
        window.__datosParcialesEdicion = [];
    }
    
    const modulos = window.datosDistribucion?.modulos || [];
    const moduloActual = modulos.find(m => m.id === moduloId);
    
    if (!moduloActual) return;
    
    // Obtener el numero de parte mas alto para este encargado
    const parcialesDelEncargado = window.__datosParcialesEdicion.filter(p => 
        p.encargado === moduloActual.encargado
    );
    
    let maxSubParte = 0;
    let numeroPrincipal = null;
    
    parcialesDelEncargado.forEach(p => {
        const numStr = String(p.consecutivo_parcial);
        const partes = numStr.split('.');
        
        if (partes.length === 2) {
            const principal = parseInt(partes[0]);
            const subParte = parseInt(partes[1]);
            
            if (numeroPrincipal === null) {
                numeroPrincipal = principal;
            }
            
            if (subParte > maxSubParte) {
                maxSubParte = subParte;
            }
        }
    });
    
    // Si no hay partes, usar el numero principal del primer parcial
    if (numeroPrincipal === null && parcialesDelEncargado.length > 0) {
        const primerParcial = parcialesDelEncargado[0];
        const numStr = String(primerParcial.consecutivo_parcial);
        const partes = numStr.split('.');
        numeroPrincipal = parseInt(partes[0]);
    }
    
    // Crear nuevo numero de parte (ej: 95.2, 95.3, etc.)
    const nuevoNumeroParte = numeroPrincipal ? `${numeroPrincipal}.${maxSubParte + 1}` : `${maxSubParte + 1}`;
    
    console.log('[CREAR PARTE VIRTUAL] Nuevo numero de parte:', nuevoNumeroParte);
    
    // Verificar si ya existe una parte con este numero
    const parteExistente = window.__datosParcialesEdicion.find(p => 
        String(p.consecutivo_parcial) === nuevoNumeroParte && p.encargado === moduloActual.encargado
    );
    
    if (parteExistente) {
        // Si ya existe, agregar la talla a esa parte
        const tallaExistente = parteExistente.tallas.find(t => 
            t.talla === tallaInfo.tallaOriginal && 
            t.color_nombre === tallaInfo.color &&
            t.genero === tallaInfo.genero
        );
        
        if (tallaExistente) {
            tallaExistente.cantidad = cantidad;
        } else {
            parteExistente.tallas.push({
                talla: tallaInfo.tallaOriginal,
                cantidad: cantidad,
                color_nombre: tallaInfo.color,
                genero: tallaInfo.genero
            });
        }
    } else {
        // Crear una nueva parte virtual con marcador de "nueva"
        const nuevaParte = {
            id: Date.now(), // ID temporal (numero, no string)
            is_nueva_parte: true, // Marcador para indicar que es nueva
            consecutivo_parcial: nuevoNumeroParte,
            consecutivo_original: nuevoNumeroParte,
            encargado: moduloActual.encargado,
            area: 'Costura',
            tipo_recibo: 'COSTURA',
            proceso_estado: 'En Progreso',
            tallas: [{
                talla: tallaInfo.tallaOriginal,
                cantidad: cantidad,
                color_nombre: tallaInfo.color,
                genero: tallaInfo.genero
            }]
        };
        
        window.__datosParcialesEdicion.push(nuevaParte);
    }
    
    console.log('[CREAR PARTE VIRTUAL] Nueva parte creada:', window.__datosParcialesEdicion);
}

// Funcion para cerrar el modal
window.cerrarModalAsignacion = function() {
    const modalContainer = document.getElementById('modalAsignacionTalla');
    if (modalContainer) {
        modalContainer.innerHTML = '';
    }
}
// Funcion para cerrar modal (adaptada)
export function cerrarModalCostura() {
    const modal = document.getElementById('modalCostura');
    if (modal) modal.style.display = 'none';
    
    // Resetear estado completo
    window.datosModalCostura = null;
    window.opcionAsignacionSeleccionada = null;
    window.asignacionesPorModulo = null;
    window.datosDistribucion = null;
    window.modulosSeleccionadosDistribucion = null;
    window.__edicionDistribucionActiva = null;
    
    // Resetear UI al estado inicial
    const modalContent = document.getElementById('modalCosturaContent');
    const opcionesDiv = document.getElementById('opcionesAsignacion');
    const contenidoDiv = document.getElementById('contenidoAsignacion');
    const modalSubtitulo = document.getElementById('modalSubtitulo');
    const btnVolver = document.getElementById('btnVolver');
    const btnConfirmar = document.getElementById('btnConfirmarAsignacion');
    const btnCompleto = document.getElementById('btnModuloCompleto');
    const btnDistribuir = document.getElementById('btnDistribuirModulos');
    
    if (modalContent) {
        modalContent.style.maxWidth = '900px';
    }
    if (opcionesDiv) {
        opcionesDiv.style.display = 'block';
    }
    if (contenidoDiv) {
        contenidoDiv.innerHTML = '';
    }
    if (modalSubtitulo) {
        modalSubtitulo.textContent = 'Seleccione el tipo de asignacion';
    }
    if (btnVolver) {
        btnVolver.style.display = 'none';
    }
    if (btnConfirmar) {
        btnConfirmar.disabled = true;
        btnConfirmar.style.background = '#3b82f6';
    }
    if (btnCompleto) {
        btnCompleto.style.borderColor = '#e2e8f0';
        btnCompleto.style.background = 'white';
    }
    if (btnDistribuir) {
        btnDistribuir.style.borderColor = '#e2e8f0';
        btnDistribuir.style.background = 'white';
    }
}

// Registrar funciones globales
window.seleccionarOpcionAsignacion = seleccionarOpcionAsignacion;
window.confirmarAsignacion = confirmarAsignacionFlow;
window.confirmarPasarACostura = confirmarPasarACosturaFlow;
window.cerrarModalCostura = cerrarModalCostura;
