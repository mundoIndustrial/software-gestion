import { httpJson } from '../api/http';
import { mostrarError, mostrarExito } from '../ui/messages';

// Función para abrir el modal (exportada para ser usada en costura.js)
export function abrirModalCostura(pedidoId, prendaId, nombre, tipoRecibo, recibo, btnId, numeroPedido = null) {
    const modal = document.getElementById('modalCostura');
    if (!modal) return;

    // Guardar datos globales
    window.datosModalCostura = { pedidoId, prendaId, tipoRecibo, btnId, recibo, nombre, numeroPedido };
    
    // Resetear selección
    window.opcionAsignacionSeleccionada = null;
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
    
    // Resetear subtítulo
    const modalSubtitulo = document.getElementById('modalSubtitulo');
    if (modalSubtitulo) {
        modalSubtitulo.textContent = 'Seleccione el tipo de asignación';
    }
    
    modal.style.display = 'flex';
}

// Variables globales para el modal
window.opcionAsignacionSeleccionada = null;
window.datosModalCostura = null;

// Función para seleccionar opción de asignación
export function seleccionarOpcionAsignacion(opcion) {
    window.opcionAsignacionSeleccionada = opcion;
    
    // Actualizar estilos de botones
    const btnCompleto = document.getElementById('btnModuloCompleto');
    const btnDistribuir = document.getElementById('btnDistribuirModulos');
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
    
    // Expandir modal para mostrar contenido completo
    modalContent.style.maxWidth = '1200px';
    modalContent.style.maxHeight = '98vh';
    
    // Ocultar opciones y mostrar botón volver
    opcionesDiv.style.display = 'none';
    btnVolver.style.display = 'inline-flex';
    
    // Aplicar estilo seleccionado y cargar contenido
    if (opcion === 'completo') {
        btnCompleto.style.borderColor = '#3b82f6';
        btnCompleto.style.background = '#eff6ff';
        modalSubtitulo.textContent = 'Asignar a Módulo Completo';
        mostrarContenidoModuloCompleto();
    } else if (opcion === 'distribuir') {
        btnDistribuir.style.borderColor = '#10b981';
        btnDistribuir.style.background = '#ecfdf5';
        modalSubtitulo.textContent = 'Distribuir por Módulos';
        mostrarContenidoDistribuirModulos();
    }
    
    // Habilitar botón confirmar
    btnConfirmar.disabled = false;
    btnConfirmar.style.background = opcion === 'completo' ? '#3b82f6' : '#10b981';
}

// Función para volver a las opciones de asignación
export function volverAOpciones() {
    const opcionesDiv = document.getElementById('opcionesAsignacion');
    const contenidoDiv = document.getElementById('contenidoAsignacion');
    const modalContent = document.getElementById('modalCosturaContent');
    const modalSubtitulo = document.getElementById('modalSubtitulo');
    const btnVolver = document.getElementById('btnVolver');
    const btnConfirmar = document.getElementById('btnConfirmarAsignacion');
    const btnCompleto = document.getElementById('btnModuloCompleto');
    const btnDistribuir = document.getElementById('btnDistribuirModulos');
    
    // Restaurar tamaño original
    modalContent.style.maxWidth = '900px';
    
    // Mostrar opciones y ocultar volver
    opcionesDiv.style.display = 'block';
    btnVolver.style.display = 'none';
    
    // Restaurar subtítulo
    modalSubtitulo.textContent = 'Seleccione el tipo de asignación';
    
    // Limpiar contenido
    contenidoDiv.innerHTML = '';
    
    // Resetear selección
    window.opcionAsignacionSeleccionada = null;
    btnConfirmar.disabled = true;
    btnConfirmar.style.background = '#3b82f6';
    
    // Resetear estilos de botones
    btnCompleto.style.borderColor = '#e2e8f0';
    btnCompleto.style.background = 'white';
    btnDistribuir.style.borderColor = '#e2e8f0';
    btnDistribuir.style.background = 'white';
}

// Función para mostrar contenido de módulo completo
function mostrarContenidoModuloCompleto() {
    const contenidoDiv = document.getElementById('contenidoAsignacion');
    
    contenidoDiv.innerHTML = `
        <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 12px; padding: 1.5rem;">
            <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem;">
                <div style="width: 40px; height: 40px; background: #3b82f6; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <span class="material-symbols-rounded" style="color: white; font-size: 1.25rem;">inventory_2</span>
                </div>
                <div style="flex: 1; min-width: 0;">
                    <h5 style="margin: 0; font-size: 1rem; font-weight: 600; color: #1e40af; line-height: 1.3;">Asignación a Módulo Completo</h5>
                    <p style="margin: 0.25rem 0 0 0; font-size: 0.875rem; color: #64748b; line-height: 1.3;">Todas las prendas serán asignadas a un solo encargado</p>
                </div>
            </div>
            
            <!-- Selector de encargado -->
            <div style="background: white; border-radius: 8px; padding: 1rem; border: 1px solid #dbeafe;">
                <label style="display: block; margin-bottom: 0.75rem; font-weight: 600; font-size: 0.875rem; color: #1e40af;">
                    <span class="material-symbols-rounded" style="vertical-align: middle; margin-right: 0.5rem; font-size: 1rem;">person</span>
                    Encargado de Costura:
                </label>
                <select id="costuraEncargado" style="width: 100%; padding: 0.75rem; border: 2px solid #d1d5db; border-radius: 8px; background: white; font-size: 0.875rem; transition: border-color 0.2s;">
                    <option value="">Seleccione un encargado...</option>
                </select>
                <p style="margin: 0.5rem 0 0 0; font-size: 0.75rem; color: #64748b; line-height: 1.3;">
                    <span class="material-symbols-rounded" style="vertical-align: middle; margin-right: 0.25rem; font-size: 0.875rem;">info</span>
                    El encargado seleccionado será responsable de todas las unidades de esta prenda.
                </p>
            </div>
            
            <!-- Resumen visual -->
            <div style="margin-top: 1.5rem; padding: 0.75rem; background: #f0f9ff; border-radius: 8px; border: 1px solid #bae6fd;">
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <span class="material-symbols-rounded" style="color: #0c4a6e; font-size: 1rem;">assignment_turned_in</span>
                    <div style="flex: 1; min-width: 0;">
                        <p style="margin: 0; font-size: 0.75rem; font-weight: 600; color: #0c4a6e;">Estado de la asignación:</p>
                        <p style="margin: 0.25rem 0 0 0; font-size: 0.75rem; color: #0c4a6e;">Pendiente de seleccionar encargado</p>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Cargar usuarios de costura
    if (window.datosModalCostura) {
        cargarUsuariosCostura(window.datosModalCostura.tipoRecibo);
    }
}

// Función para mostrar contenido de distribución por módulos
function mostrarContenidoDistribuirModulos() {
    const contenidoDiv = document.getElementById('contenidoAsignacion');
    
    contenidoDiv.innerHTML = `
        <div style="background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 12px; padding: 1.5rem;">
            <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem;">
                <div style="width: 40px; height: 40px; background: #10b981; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <span class="material-symbols-rounded" style="color: white; font-size: 1.25rem;">share</span>
                </div>
                <div style="flex: 1; min-width: 0;">
                    <h5 style="margin: 0; font-size: 1rem; font-weight: 600; color: #047857; line-height: 1.3;">Distribución por Módulos</h5>
                    <p style="margin: 0.25rem 0 0 0; font-size: 0.875rem; color: #64748b; line-height: 1.3;">Reparta las prendas entre diferentes módulos</p>
                </div>
            </div>
            
            <!-- Aquí se cargará la interfaz de distribución -->
            <div id="interfazDistribucion" style="margin-top: 1rem;">
            </div>
        </div>
    `;
    
    // Cargar datos reales de tallas y módulos
    cargarDatosDistribucion();
}

// Función para cargar datos reales de distribución
function cargarDatosDistribucion() {
    if (!window.datosModalCostura) {
        console.error('No hay datos de la prenda disponibles');
        return;
    }
    
    const { prendaId, tipoRecibo } = window.datosModalCostura;
    
    // Cargar tallas de la prenda
    Promise.all([
        cargarTallasPrenda(prendaId, tipoRecibo),
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
        console.error('Error cargando datos de distribución:', error);
        mostrarErrorDistribucion();
    });
}

// Función para cargar tallas de la prenda
function cargarTallasPrenda(prendaId, tipoRecibo) {
    if (!window.datosModalCostura) {
        return Promise.resolve([]);
    }

    const { numeroPedido } = window.datosModalCostura;

    const numeroPedidoCandidatos = [];
    if (numeroPedido !== undefined && numeroPedido !== null) numeroPedidoCandidatos.push(String(numeroPedido));

    const tr = String(tipoRecibo || '').trim();
    const params = new URLSearchParams();
    params.set('prenda_id', String(prendaId));
    if (tr) params.set('tipo_recibo', tr);

    const intentar = (idx) => {
        if (idx >= numeroPedidoCandidatos.length) {
            return Promise.resolve([]);
        }
        const numeroPedido = numeroPedidoCandidatos[idx];
        return httpJson(`/api/operario/pedido/${numeroPedido}?${params.toString()}`)
            .then((response) => response.json())
            .then((data) => {
                if (!data?.success) throw new Error(data?.message || 'Error cargando pedido');

                const prendas = data?.data?.prendas || [];
                const prenda = prendas.find((p) => String(p.id) === String(prendaId) || String(p.prenda_pedido_id) === String(prendaId));
                const variantes = prenda?.variantes || [];
                return variantes;
            })
            .catch(() => intentar(idx + 1));
    };

    return intentar(0);
}

// Función para cargar usuarios según tipo de recibo
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

// Función para procesar tallas al formato esperado
function procesarTallasParaDistribucion(tallas) {
    const tallasArray = [];
    
    if (Array.isArray(tallas) && tallas.length > 0 && typeof tallas[0] === 'object' && tallas[0] !== null && 'talla' in tallas[0] && 'cantidad' in tallas[0]) {
        // Variantes del recibo: [{talla, genero, cantidad, ...}]
        tallas.forEach((v) => {
            const nombreTalla = String(v.talla || '').trim();
            if (!nombreTalla) return;
            const genero = String(v.genero || '').trim();
            const cantidad = parseInt(v.cantidad) || 0;
            if (cantidad <= 0) return;
            tallasArray.push({
                talla: genero ? `${nombreTalla} (${genero})` : nombreTalla,
                cantidad,
            });
        });
        return tallasArray;
    }

    if (Array.isArray(tallas)) {
        // Si es un array simple
        tallas.forEach((talla, index) => {
            if (typeof talla === 'object' && talla !== null) {
                tallasArray.push({
                    talla: talla.talla || talla.nombre || `Talla ${index + 1}`,
                    cantidad: parseInt(talla.cantidad) || 0
                });
            } else if (typeof talla === 'string') {
                tallasArray.push({
                    talla: talla,
                    cantidad: 0 // Debería obtenerse de otro lado
                });
            }
        });
    } else if (typeof tallas === 'object' && tallas !== null) {
        // Si es un objeto por género
        Object.entries(tallas).forEach(([genero, tallasGenero]) => {
            if (typeof tallasGenero === 'object') {
                Object.entries(tallasGenero).forEach(([nombreTalla, cantidad]) => {
                    if (cantidad > 0) {
                        tallasArray.push({
                            talla: `${nombreTalla} (${genero})`,
                            cantidad: parseInt(cantidad) || 0
                        });
                    }
                });
            }
        });
    }
    
    return tallasArray;
}

// Función para procesar usuarios al formato esperado
function procesarUsuariosParaDistribucion(usuarios) {
    return usuarios.map((usuario, index) => ({
        id: index + 1,
        nombre: `Módulo ${index + 1}`,
        encargado: usuario.name || usuario.nombre || 'Sin nombre',
        usuarioId: usuario.id
    }));
}

// Función para mostrar error en distribución
function mostrarErrorDistribucion() {
    const interfazDiv = document.getElementById('interfazDistribucion');
    if (interfazDiv) {
        interfazDiv.innerHTML = `
            <div style="text-align: center; padding: 2rem; color: #dc2626;">
                <span class="material-symbols-rounded" style="font-size: 2rem; display: block; margin-bottom: 0.75rem;">error</span>
                <p style="font-size: 0.875rem; margin: 0;">No se pudo cargar la información. Por favor, intente nuevamente.</p>
            </div>
        `;
    }
}

// Función para cargar la interfaz de distribución con datos reales
function cargarInterfazDistribucionConDatos(tallas, modulos) {
    const interfazDiv = document.getElementById('interfazDistribucion');
    
    if (!tallas || tallas.length === 0) {
        interfazDiv.innerHTML = `
            <div style="text-align: center; padding: 2rem; color: #6b7280;">
                <span class="material-symbols-rounded" style="font-size: 2rem; display: block; margin-bottom: 0.75rem;">info</span>
                <p style="font-size: 0.875rem; margin: 0;">No hay tallas disponibles para esta prenda</p>
            </div>
        `;
        return;
    }
    
    if (!modulos || modulos.length === 0) {
        interfazDiv.innerHTML = `
            <div style="text-align: center; padding: 2rem; color: #6b7280;">
                <span class="material-symbols-rounded" style="font-size: 2rem; display: block; margin-bottom: 0.75rem;">person_off</span>
                <p style="font-size: 0.875rem; margin: 0;">No hay módulos disponibles para asignar</p>
            </div>
        `;
        return;
    }
    
    let html = `
        <!-- Selector de módulos -->
        <div style="margin-bottom: 1.5rem;">
            <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: #374151;">Seleccionar Módulo:</label>
            <select id="moduloSelector" onchange="mostrarAsignacionModulo()" style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 8px; background: white; font-size: 0.875rem;">
                <option value="">Seleccione un módulo para asignar tallas...</option>
                ${modulos.map(modulo => `
                    <option value="${modulo.id}">${modulo.encargado}</option>
                `).join('')}
            </select>
        </div>
        
        <!-- Contenedor de asignación por módulo -->
        <div id="asignacionModuloContainer"></div>
        
        <!-- Resumen de asignaciones -->
        <div id="resumenAsignaciones" style="margin-top: 1.5rem; padding: 1rem; background: #fef3c7; border-radius: 8px; border: 1px solid #fde68a;">
            <h6 style="margin: 0 0 0.75rem 0; font-size: 0.875rem; font-weight: 600; color: #92400e;">Resumen de Asignaciones</h6>
            <div id="resumenContenido">
                <p style="margin: 0; font-size: 0.875rem; color: #92400e;">No hay asignaciones realizadas</p>
            </div>
        </div>
    `;
    
    interfazDiv.innerHTML = html;
    
    // Guardar datos globales para uso posterior
    window.datosDistribucion = { tallas, modulos };
}

// Función para mostrar la interfaz de asignación para un módulo específico
window.mostrarAsignacionModulo = function() {
    const moduloSelector = document.getElementById('moduloSelector');
    const container = document.getElementById('asignacionModuloContainer');
    const moduloId = parseInt(moduloSelector.value);
    
    if (!moduloId) {
        container.innerHTML = '';
        return;
    }
    
    const modulo = window.datosDistribucion.modulos.find(m => m.id === moduloId);
    const tallas = window.datosDistribucion.tallas;
    
    // Calcular cantidades ya asignadas a este módulo
    const asignacionesActuales = window.asignacionesPorModulo || {};
    const asignacionModulo = asignacionesActuales[moduloId] || {};
    
    let html = `
        <div style="background: white; border: 1px solid #d1d5db; border-radius: 8px; padding: 1rem; margin-bottom: 1rem;">
            <div style="display: flex; align-items: center; justify-content: between; margin-bottom: 1rem;">
                <div>
                    <h6 style="margin: 0; font-size: 1rem; font-weight: 600; color: #1f2937;">${modulo.nombre}</h6>
                    <p style="margin: 0.25rem 0 0 0; font-size: 0.875rem; color: #6b7280;">Encargado: ${modulo.encargado}</p>
                </div>
            </div>
            
            <div style="display: grid; gap: 1rem;">
                ${tallas.map(talla => {
                    const asignado = asignacionModulo[talla.talla] || 0;
                    const maxDisponible = getMaxDisponibleParaModulo(talla.talla, moduloId);
                    const disponible = maxDisponible;
                    const isSelected = asignado > 0;

                    // Si por alguna razón quedó un valor mayor al máximo permitido, recortar y persistir.
                    if (asignado > maxDisponible) {
                        window.actualizarAsignacion(talla.talla, moduloId, maxDisponible);
                    }
                    
                    return `
                        <div class="dist-talla-row ${isSelected ? 'is-selected' : ''}">
                            <div class="dist-talla-left">
                                <input
                                    type="checkbox"
                                    class="dist-talla-check"
                                    ${isSelected ? 'checked' : ''}
                                    onchange="toggleTallaSeleccion('${talla.talla}', ${moduloId}, this.checked)"
                                />
                                <div class="dist-talla-text">
                                    <div class="dist-talla-title">${talla.talla}</div>
                                    <div class="dist-talla-sub">Disp: <span class="dist-talla-disp">${disponible}</span></div>
                                </div>
                            </div>

                            <div class="dist-talla-right">
                                <button type="button" class="dist-talla-btn" onclick="ajustarCantidad('${talla.talla}', ${moduloId}, -1)">
                                    <span class="material-symbols-rounded" style="font-size: 1rem;">remove</span>
                                </button>
                                <input
                                    type="number"
                                    class="dist-talla-input"
                                    id="talla_${talla.talla}_modulo_${moduloId}"
                                    min="0"
                                    max="${maxDisponible}"
                                    value="${asignado}"
                                    ${isSelected ? '' : 'disabled'}
                                    onchange="actualizarAsignacion('${talla.talla}', ${moduloId}, this.value)"
                                />
                                <button type="button" class="dist-talla-btn" onclick="ajustarCantidad('${talla.talla}', ${moduloId}, 1)">
                                    <span class="material-symbols-rounded" style="font-size: 1rem;">add</span>
                                </button>
                            </div>
                        </div>
                    `;
                }).join('')}
            </div>
        </div>
    `;
    
    container.innerHTML = html;
    actualizarResumenAsignaciones();
};

window.toggleTallaSeleccion = function(talla, moduloId, checked) {
    const input = document.getElementById(`talla_${talla}_modulo_${moduloId}`);
    if (!input) return;

    const maxValue = parseInt(input.max);
    if (!Number.isFinite(maxValue) || maxValue <= 0) {
        input.value = 0;
        input.disabled = true;
        actualizarAsignacion(talla, moduloId, 0);
        window.mostrarAsignacionModulo();
        return;
    }

    if (!checked) {
        input.value = 0;
        input.disabled = true;
        actualizarAsignacion(talla, moduloId, 0);
        window.mostrarAsignacionModulo();
        return;
    }

    input.disabled = false;
    const currentValue = parseInt(input.value) || 0;
    const nextValue = Math.max(1, Math.min(maxValue, currentValue || maxValue));
    input.value = nextValue;
    actualizarAsignacion(talla, moduloId, nextValue);
    window.mostrarAsignacionModulo();
};

// Función para ajustar cantidad con botones +/-
window.ajustarCantidad = function(talla, moduloId, delta) {
    const input = document.getElementById(`talla_${talla}_modulo_${moduloId}`);
    if (!input) return;

    if (input.disabled) return;
    
    const currentValue = parseInt(input.value) || 0;
    const maxValue = parseInt(input.max);
    const newValue = Math.max(0, Math.min(maxValue, currentValue + delta));
    
    input.value = newValue;
    actualizarAsignacion(talla, moduloId, newValue);

    if (typeof window.mostrarAsignacionModulo === 'function') {
        window.mostrarAsignacionModulo();
    }
};

// Función para actualizar asignación
window.actualizarAsignacion = function(talla, moduloId, cantidad) {
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
    
    if (cantidad > 0) {
        window.asignacionesPorModulo[moduloId][talla] = cantidad;
    } else {
        delete window.asignacionesPorModulo[moduloId][talla];
    }
    
    // Si el módulo quedó vacío, eliminarlo
    if (Object.keys(window.asignacionesPorModulo[moduloId]).length === 0) {
        delete window.asignacionesPorModulo[moduloId];
    }
    
    actualizarResumenAsignaciones();

    if (typeof window.mostrarAsignacionModulo === 'function') {
        window.mostrarAsignacionModulo();
    }
};

// Función para obtener el total asignado de una talla (excepto el módulo actual)
function getTotalAsignadoTalla(talla, moduloIdExcluir = null) {
    if (!window.asignacionesPorModulo) return 0;
    
    let total = 0;
    for (const [moduloId, asignaciones] of Object.entries(window.asignacionesPorModulo)) {
        if (parseInt(moduloId) !== moduloIdExcluir && asignaciones[talla]) {
            total += asignaciones[talla];
        }
    }
    return total;
}

function getMaxDisponibleParaModulo(talla, moduloId) {
    const tallas = window?.datosDistribucion?.tallas || [];
    const tallaObj = tallas.find((t) => t.talla === talla);
    const totalOriginal = parseInt(tallaObj?.cantidad) || 0;
    const totalAsignadoOtros = getTotalAsignadoTalla(talla, moduloId);
    const max = Math.max(0, totalOriginal - totalAsignadoOtros);
    return max;
}

// Función para actualizar el resumen de asignaciones
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
        const modulo = modulos.find(m => m.id === parseInt(moduloId));
        if (!modulo) continue;
        
        const tallasAsignadas = Object.entries(asignaciones)
            .map(([talla, cantidad]) => `${talla}×${cantidad}`)
            .join(', ');
        
        html += `
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.5rem; background: white; border-radius: 4px;">
                <span style="font-size: 0.875rem; font-weight: 500; color: #92400e;">${modulo.nombre}:</span>
                <span style="font-size: 0.875rem; color: #92400e;">${tallasAsignadas}</span>
            </div>
        `;
    }
    
    html += '</div>';
    resumenContenido.innerHTML = html;
}

// Función para confirmar asignación
export function confirmarAsignacion() {
    if (!window.opcionAsignacionSeleccionada) {
        mostrarError('Error', 'Debe seleccionar un tipo de asignación');
        return;
    }
    
    if (!window.datosModalCostura) {
        mostrarError('Error', 'No hay datos de la prenda');
        return;
    }
    
    if (window.opcionAsignacionSeleccionada === 'completo') {
        // Usar el flujo original
        confirmarPasarACostura();
    } else if (window.opcionAsignacionSeleccionada === 'distribuir') {
        const btnConfirmar = document.getElementById('btnConfirmarAsignacion');
        const originalText = btnConfirmar ? btnConfirmar.innerHTML : null;

        const { pedidoId, prendaId, tipoRecibo, recibo } = window.datosModalCostura;
        if (!pedidoId || !prendaId || !tipoRecibo || !recibo) {
            mostrarError('Error', 'Faltan datos necesarios para procesar la solicitud');
            return;
        }

        if (!window.asignacionesPorModulo || Object.keys(window.asignacionesPorModulo).length === 0) {
            mostrarError('Error', 'No hay asignaciones realizadas');
            return;
        }

        const modulos = window?.datosDistribucion?.modulos || [];

        const parseTallaBase = (tallaRaw) => {
            const s = String(tallaRaw || '').trim();
            const m = s.match(/^(.+?)\s*\((.+)\)$/);
            return m ? m[1].trim() : s;
        };

        const asignaciones = Object.entries(window.asignacionesPorModulo)
            .map(([moduloIdStr, asignacionesTallas]) => {
                const moduloId = parseInt(moduloIdStr);
                const modulo = modulos.find((m) => m.id === moduloId);
                const encargado = (modulo?.encargado || '').trim();

                const tallas = Object.entries(asignacionesTallas || {})
                    .map(([tallaRaw, cantidad]) => ({
                        talla: parseTallaBase(tallaRaw),
                        cantidad: parseInt(cantidad) || 0,
                        color_nombre: null,
                    }))
                    .filter((t) => t.talla && t.cantidad > 0);

                return {
                    encargado,
                    tallas,
                };
            })
            .filter((a) => a.encargado && Array.isArray(a.tallas) && a.tallas.length > 0);

        if (asignaciones.length === 0) {
            mostrarError('Error', 'No hay asignaciones válidas para guardar');
            return;
        }

        const action = `/recibos-novedades/${pedidoId}/${recibo}/distribuir-por-modulos`;
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

        if (btnConfirmar) {
            btnConfirmar.disabled = true;
            btnConfirmar.innerHTML = '<span class="material-symbols-rounded" style="animation: spin 1s linear infinite;">refresh</span> Procesando...';
        }

        fetch(action, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
            },
            body: JSON.stringify({
                prenda_id: prendaId,
                tipo_recibo: tipoRecibo,
                asignaciones,
            }),
        })
            .then((r) => r.json())
            .then((data) => {
                if (data?.success) {
                    cerrarModalCostura();
                    mostrarExito('Éxito', data?.message || 'La distribución del recibo fue exitosa');
                } else {
                    mostrarError('Error', data?.message || 'No se pudo guardar la distribución');
                }
            })
            .catch((err) => {
                console.error('Error guardando distribución:', err);
                mostrarError('Error', 'Error de conexión: ' + (err?.message || err));
            })
            .finally(() => {
                if (btnConfirmar) {
                    btnConfirmar.disabled = false;
                    if (originalText !== null) btnConfirmar.innerHTML = originalText;
                }
            });
    }
}

// Función para cargar usuarios de costura (copiada de costura.js)
function cargarUsuariosCostura(tipoRecibo = '') {
    const select = document.getElementById('costuraEncargado');
    if (!select) return;

    select.innerHTML = '<option value="">Cargando...</option>';

    const qs = new URLSearchParams();
    const tr = String(tipoRecibo || '').trim().toUpperCase();
    if (tr) {
        qs.set('tipo_recibo', tr);
    }
    const url = qs.toString() ? `/api/usuarios/costura?${qs.toString()}` : '/api/usuarios/costura';

    fetch(url, {
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
    })
        .then((response) => response.json())
        .then((data) => {
            select.innerHTML = '<option value="">Seleccione un encargado...</option>';
            if (data.success && data.usuarios) {
                data.usuarios.forEach((usuario) => {
                    const option = document.createElement('option');
                    option.value = usuario.name;
                    option.textContent = usuario.name;
                    select.appendChild(option);
                });
            } else {
                select.innerHTML = '<option value="">No hay usuarios disponibles</option>';
            }
        })
        .catch((error) => {
            console.error('Error cargando usuarios de costura:', error);
            select.innerHTML = '<option value="">Error al cargar usuarios</option>';
        });
}

// Función original confirmarPasarACostura (adaptada)
function confirmarPasarACostura() {
    const encargado = document.getElementById('costuraEncargado')?.value.trim();
    if (!encargado) {
        mostrarError('Error', 'Debes seleccionar un encargado de costura');
        return;
    }

    if (!window.datosModalCostura) {
        mostrarError('Error', 'No hay datos de la prenda pendiente');
        return;
    }

    const { pedidoId, prendaId, tipoRecibo, btnId, recibo } = window.datosModalCostura;

    if (!pedidoId || !prendaId || !tipoRecibo || !recibo) {
        mostrarError('Error', 'Faltan datos necesarios para procesar la solicitud');
        console.error('Datos incompletos:', { pedidoId, prendaId, tipoRecibo, recibo });
        return;
    }

    const btn = document.getElementById(btnId);
    if (!btn) {
        mostrarError('Error', 'No se encontró el botón de acción');
        return;
    }

    const originalHTML = btn.innerHTML;

    btn.disabled = true;
    btn.innerHTML = '<span class="material-symbols-rounded" style="animation: spin 1s linear infinite;">refresh</span> Procesando...';

    const action = `/recibos-novedades/${pedidoId}/${recibo}/pasar-a-costura`;

    const formData = new FormData();
    formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
    formData.append('prenda_id', prendaId);
    formData.append('encargado', encargado);
    formData.append('tipo_recibo', tipoRecibo);
    formData.append('_method', 'POST');

    fetch(action, {
        method: 'POST',
        body: formData,
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
    })
        .then((response) => response.json())
        .then((data) => {
            console.log('Respuesta del servidor:', data);

            if (data.success) {
                btn.dataset.encargadoCostura = encargado;
                btn.dataset.procesoId = data.data?.proceso_id || '';
                btn.classList.add('btn-deshacer-costura');
                btn.innerHTML = '<span class="material-symbols-rounded">undo</span> DESHACER COSTURA';
                cerrarModalCostura();
                mostrarExito('Éxito', data.message || 'Prenda asignada a costura correctamente');
            } else {
                btn.innerHTML = originalHTML;
                mostrarError('Error', data.message || 'Error asignando a costura');
            }
        })
        .catch((error) => {
            console.error('Error:', error);
            btn.innerHTML = originalHTML;
            mostrarError('Error', 'Error de conexión: ' + error.message);
        })
        .finally(() => {
            btn.disabled = false;
        });
}

// Función para cerrar modal (adaptada)
export function cerrarModalCostura() {
    const modal = document.getElementById('modalCostura');
    if (modal) modal.style.display = 'none';
    
    // Resetear estado completo
    window.datosModalCostura = null;
    window.opcionAsignacionSeleccionada = null;
    window.asignacionesPorModulo = null;
    window.datosDistribucion = null;
    
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
        modalSubtitulo.textContent = 'Seleccione el tipo de asignación';
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
window.confirmarAsignacion = confirmarAsignacion;
window.cerrarModalCostura = cerrarModalCostura;
