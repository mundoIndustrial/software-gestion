import { httpJson } from '../api/http';
import { mostrarError, mostrarExito } from '../ui/messages';

// Función para abrir el modal (exportada para ser usada en costura.js)
export function abrirModalCostura(pedidoId, prendaId, nombre, tipoRecibo, recibo, btnId, numeroPedido = null, parcialId = null, prendaBodegaId = null) {
    const modal = document.getElementById('modalCostura');
    if (!modal) return;

    // Guardar datos globales
    window.datosModalCostura = { pedidoId, prendaId, prendaBodegaId, tipoRecibo, btnId, recibo, nombre, numeroPedido, parcialId };
    
    // Resetear selección
    window.opcionAsignacionSeleccionada = null;
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
    
    // Ocultar opciones y mostrar botón volver
    opcionesDiv.style.display = 'none';
    btnVolver.style.display = 'inline-flex';
    
    // Aplicar estilo seleccionado y cargar contenido
    if (opcion === 'completo') {
        btnCompleto.style.borderColor = '#3b82f6';
        btnCompleto.style.background = '#eff6ff';
        modalSubtitulo.textContent = 'Asignar a Módulo Completo';
        mostrarContenidoModuloCompleto();
        btnConfirmar.style.background = '#3b82f6';
    } else if (opcion === 'distribuir') {
        btnDistribuir.style.borderColor = '#10b981';
        btnDistribuir.style.background = '#ecfdf5';
        modalSubtitulo.textContent = 'Distribuir por Módulos';
        mostrarContenidoDistribuirModulos();
        btnConfirmar.style.background = '#10b981';
    } else if (opcion === 'taller') {
        if (btnTaller) {
            btnTaller.style.borderColor = '#f59e0b';
            btnTaller.style.background = '#fffbeb';
        }
        modalSubtitulo.textContent = 'Distribuir a Taller';
        mostrarContenidoTaller();
        btnConfirmar.style.background = '#f59e0b';
    }
    
    // Habilitar botón confirmar
    btnConfirmar.disabled = false;
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
    const btnTaller = document.getElementById('btnTaller');
    
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
    if (btnTaller) {
        btnTaller.style.borderColor = '#e2e8f0';
        btnTaller.style.background = 'white';
    }
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
                        <p style="margin: 0; font-size: 0.75rem; font-weight: 600; color: #0c4a6e;">Estado de la asignación:</p>
                        <p id="textoEstadoAsignacion" style="margin: 0.25rem 0 0 0; font-size: 0.75rem; color: #0c4a6e;">Pendiente de seleccionar encargado</p>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Cargar usuarios de costura
    if (window.datosModalCostura) {
        cargarUsuariosCostura(window.datosModalCostura.tipoRecibo);
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

    const { numeroPedido, recibo, parcialId, prendaBodegaId } = window.datosModalCostura;

    if (prendaBodegaId) {
        return httpJson(`/operario/api/prenda-bodega/${prendaBodegaId}`, {
            method: 'GET'
        })
            .then((response) => response.json())
            .then((data) => {
                console.log('[CARGAR TALLAS BODEGA] Datos recibidos:', data);

                if (!data?.success) {
                    throw new Error(data?.message || 'Error cargando prenda de bodega');
                }

                return data?.data?.tallas || [];
            });
    }

    const numeroPedidoCandidatos = [];
    if (numeroPedido !== undefined && numeroPedido !== null) numeroPedidoCandidatos.push(String(numeroPedido));

    const tr = String(tipoRecibo || '').trim();
    const params = new URLSearchParams();
    params.set('prenda_id', String(prendaId));
    if (tr) params.set('tipo_recibo', tr);
    
    // Si tenemos parcialId (es un recibo parcial), lo enviamos prioritariamente
    if (parcialId) params.set('parcial_id', String(parcialId));
    
    // Si tenemos consecutivo de recibo, lo enviamos para filtrar tallas exactas
    if (recibo) params.set('recibo', String(recibo));

    const intentar = (idx) => {
        if (idx >= numeroPedidoCandidatos.length) {
            return Promise.resolve([]);
        }
        const numeroPedido = numeroPedidoCandidatos[idx];
        // Usar la ruta correcta: /operario/api/pedido/{numeroPedido}
        return httpJson(`/operario/api/pedido/${numeroPedido}?${params.toString()}`, {
            method: 'GET'
        })
            .then((response) => response.json())
            .then((data) => {
                console.log('[CARGAR TALLAS] Datos recibidos:', data);
                
                if (!data?.success) throw new Error(data?.message || 'Error cargando pedido');

                const prendas = data?.data?.prendas || [];
                const prenda = prendas.find((p) => String(p.id) === String(prendaId) || String(p.prenda_pedido_id) === String(prendaId));
                const variantes = prenda?.variantes || [];
                
                console.log('[CARGAR TALLAS] Prenda encontrada:', prenda);
                console.log('[CARGAR TALLAS] Variantes:', variantes);
                
                // Verificar si las variantes tienen colores_detalle
                variantes.forEach((variante, index) => {
                    console.log(`[CARGAR TALLAS] Variante ${index}:`, {
                        talla: variante.talla,
                        genero: variante.genero,
                        cantidad: variante.cantidad,
                        colores_detalle: variante.colores_detalle,
                        color_info: variante.color_info
                    });
                });
                
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
        // Variantes del recibo: [{talla, genero, cantidad, colores_detalle, ...}]
        tallas.forEach((v) => {
            const nombreTalla = String(v.talla || '').trim();
            if (!nombreTalla) return;
            const genero = String(v.genero || '').trim();
            
            // Si hay colores_detalle, procesar cada color como una talla separada
            if (v.colores_detalle && Array.isArray(v.colores_detalle) && v.colores_detalle.length > 0) {
                v.colores_detalle.forEach((colorDetalle) => {
                    const cantidad = parseInt(colorDetalle.cantidad) || 0;
                    if (cantidad <= 0) return;
                    
                    const color = colorDetalle.color || null;
                    const nombreDisplay = genero ? `${nombreTalla} (${genero})` : nombreTalla;
                    
                    tallasArray.push({
                        talla: nombreDisplay,
                        cantidad: cantidad,
                        color: color,
                        tallaOriginal: nombreTalla,
                        genero: genero,
                        colorDetalle: colorDetalle
                    });
                });
            } else {
                // Si no hay colores, procesar como talla normal
                const cantidad = parseInt(v.cantidad) || 0;
                if (cantidad <= 0) return;
                const color = v.color_nombre || v.color || null;
                
                tallasArray.push({
                    talla: genero ? `${nombreTalla} (${genero})` : nombreTalla,
                    cantidad: cantidad,
                    color: color,
                    tallaOriginal: nombreTalla,
                    genero: genero
                });
            }
        });
        return tallasArray;
    }

    if (Array.isArray(tallas)) {
        // Si es un array simple
        tallas.forEach((talla, index) => {
            if (typeof talla === 'object' && talla !== null) {
                tallasArray.push({
                    talla: talla.talla || talla.nombre || `Talla ${index + 1}`,
                    cantidad: parseInt(talla.cantidad) || 0,
                    color: talla.color_nombre || talla.color || null
                });
            } else if (typeof talla === 'string') {
                tallasArray.push({
                    talla: talla,
                    cantidad: 0, // Debería obtenerse de otro lado
                    color: null
                });
            }
        });
    } else if (typeof tallas === 'object' && tallas !== null) {
        // Si es un objeto por género
        Object.entries(tallas).forEach(([genero, tallasGenero]) => {
            if (typeof tallasGenero === 'object') {
                Object.entries(tallasGenero).forEach(([nombreTalla, datos]) => {
                    let cantidad = 0;
                    let color = null;
                    
                    if (typeof datos === 'object' && datos !== null) {
                        cantidad = parseInt(datos.cantidad) || 0;
                        color = datos.color_nombre || datos.color || null;
                    } else {
                        cantidad = parseInt(datos) || 0;
                    }
                    
                    if (cantidad > 0) {
                        tallasArray.push({
                            talla: `${nombreTalla} (${genero})`,
                            cantidad: cantidad,
                            color: color
                        });
                    }
                });
            }
        });
    }
    
    return tallasArray;
}

// Función para agrupar tallas por género y color
function agruparTallasPorGeneroYColor(tallas) {
    const grupos = {};
    
    tallas.forEach(talla => {
        const genero = talla.genero || 'Sin género';
        const color = talla.color || 'Sin color';
        
        if (!grupos[genero]) {
            grupos[genero] = {};
        }
        
        if (!grupos[genero][color]) {
            grupos[genero][color] = [];
        }
        
        grupos[genero][color].push(talla);
    });
    
    return grupos;
}

function construirTallaIdUnico(nombreTalla, color, genero = '') {
    const tallaBase = String(nombreTalla || '').trim();
    const colorNormalizado = normalizarColor(color);
    const generoNormalizado = normalizarGenero(genero);
    return `${tallaBase}_${colorNormalizado}_${generoNormalizado}`;
}

// Función para generar HTML de tallas agrupadas
function generarHtmlTallasAgrupadas(tallas, moduloId) {
    const grupos = agruparTallasPorGeneroYColor(tallas);
    let html = '';
    
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
                <div style="background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 0.75rem;">
                    <div style="display: flex; align-items: center; margin-bottom: 0.5rem;">
                        ${colorDisplay ? `
                            <span style="display: inline-block; width: 16px; height: 16px; ${colorStyle} border-radius: 4px; margin-right: 0.5rem;"></span>
                            <span style="font-size: 0.875rem; font-weight: 500; color: #374151;">${color}</span>
                        ` : `
                            <span style="font-size: 0.875rem; font-weight: 500; color: #6b7280;">Sin color</span>
                        `}
                    </div>
                    <div style="display: grid; gap: 0.5rem;">
            `;
            
            tallasColor.forEach(talla => {
                // Crear un ID único que incluya el color y género para evitar colisiones
                const tallaIdUnico = construirTallaIdUnico(talla.tallaOriginal, color, talla.genero);
                
                // Verificar si esta talla específica (con color) está asignada a este módulo
                let asignado = 0;
                if (window.asignacionesPorModulo && window.asignacionesPorModulo[moduloId]) {
                    if (typeof window.asignacionesPorModulo[moduloId][tallaIdUnico] === 'object' && window.asignacionesPorModulo[moduloId][tallaIdUnico] !== null) {
                        asignado = window.asignacionesPorModulo[moduloId][tallaIdUnico].cantidad || 0;
                    } else if (typeof window.asignacionesPorModulo[moduloId][tallaIdUnico] === 'number') {
                        asignado = window.asignacionesPorModulo[moduloId][tallaIdUnico];
                    }
                }
                
                const maxDisponible = getMaxDisponibleParaModulo(tallaIdUnico, moduloId);
                const disponible = getDisponibleRestanteGlobal(tallaIdUnico);
                const asignadoMostrar = Math.min(asignado, maxDisponible);
                const isSelected = asignadoMostrar > 0;
                
                // SI NO HAY DISPONIBILIDAD Y NO ESTÁ SELECCIONADA, NO MOSTRARLA
                if (disponible <= 0 && !isSelected) return;
                
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
                            />
                            <div style="font-size: 0.875rem; font-weight: 500; color: #374151;">
                                ${talla.tallaOriginal}
                            </div>
                            <input
                                type="number"
                                class="dist-talla-input"
                                id="talla_${tallaIdUnico}_modulo_${moduloId}"
                                data-tallaid="${tallaIdUnico}"
                                data-moduloid="${moduloId}"
                                min="0"
                                max="${maxDisponible}"
                                value="${asignadoMostrar}"
                                ${isSelected ? '' : 'disabled'}
                                 oninput="if(this.value==='')return; const v=parseInt(this.value)||0; const mx=parseInt(this.max)||0; if(v>mx)this.value=mx; if(v<0)this.value=0;"
                                onchange="actualizarAsignacion('${tallaIdUnico}', ${moduloId}, this.value)"
                                style="width: 70px; text-align: center; padding: 0.25rem; border: 1px solid #d1d5db; border-radius: 4px; font-size: 0.875rem; font-weight: 500;"
                            />
                            <div class="dist-disp" data-tallaid="${tallaIdUnico}" data-moduloid="${moduloId}" style="font-size: 0.75rem; color: #dc2626; font-weight: 500;">
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
    
    return html;
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
    
    // Para el modo "EDITAR ENCARGADOS", mostraremos las cards de encargados
    if (window.datosModalCostura?.esEdicion) {
        mostrarCardsEncargados(tallas, modulos);
    } else {
        // Modo normal de distribución
        mostrarInterfazDistribucionNormal(tallas, modulos);
    }
    
    // Guardar datos globales para uso posterior
    window.datosDistribucion = { tallas, modulos };
    window.dispatchEvent(new CustomEvent('costura:datos-distribucion-listos', {
        detail: window.datosDistribucion
    }));
}

// Función para mostrar cards de encargados (modo edición)
window.mostrarCardsEncargados = function(tallas, modulos) {
    const interfazDiv = document.getElementById('interfazDistribucion');
    
    // Obtener solo los módulos que tienen asignaciones
    const modulosConAsignaciones = Object.keys(window.asignacionesPorModulo || {}).map(id => 
        modulos.find(m => m.id === parseInt(id))
    ).filter(m => m);
    
    window.modulosSeleccionadosDistribucion = modulosConAsignaciones.map((modulo) => modulo.id);

    let html = `
        <div style="margin-bottom: 1.5rem;">
            <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: #374151;">Seleccionar Módulo:</label>
            <div style="display: flex; gap: 0.5rem;">
                <div style="position: relative; flex: 1;">
                    <input type="text" id="moduloSelector" list="listaModulosDisponibles" 
                        placeholder="Seleccione o escriba un módulo para asignar tallas..." 
                        style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 8px; background: white; font-size: 0.875rem; outline: none; transition: all 0.2s;"
                        onfocus="this.style.borderColor='#10b981'; this.style.boxShadow='0 0 0 3px rgba(16, 185, 129, 0.1)';"
                        onblur="this.style.borderColor='#d1d5db'; this.style.boxShadow='none';"
                        onkeypress="if(event.key === 'Enter') agregarEncargadoSeleccionado()"
                    >
                    <datalist id="listaModulosDisponibles">
                        ${modulos
                            .filter((modulo) => !window.modulosSeleccionadosDistribucion.includes(modulo.id))
                            .map(modulo => `<option value="${modulo.encargado}"></option>`).join('')}
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
        <div id="cardsEncargadosPlaceholder" style="display: ${modulosConAsignaciones.length === 0 ? 'block' : 'none'}; min-height: 120px;">
            <div style="text-align: center; padding: 2rem; color: #6b7280;">
                <span class="material-symbols-rounded" style="font-size: 2rem; display: block; margin-bottom: 0.75rem;">person_off</span>
                <p style="font-size: 0.875rem; margin: 0;">No hay encargados asignados</p>
            </div>
        </div>
        <div id="cardsEncargadosSeleccionados" style="display: grid; gap: 1rem;"></div>
    `;

    interfazDiv.innerHTML = html;

    if (typeof window.renderCardsEncargadosSeleccionados === 'function') {
        window.renderCardsEncargadosSeleccionados();
    }
}

// Función para generar HTML de una card de encargado
function generarCardEncargado(modulo, tallas, asignaciones) {
    const htmlTallas = generarHtmlTallasParaEncargado(tallas, modulo.id, asignaciones);
    
    return `
        <div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; padding: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <!-- Header del encargado -->
            <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid #f3f4f6;">
                <div style="width: 48px; height: 48px; background: linear-gradient(135deg, #10b981, #059669); border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <span class="material-symbols-rounded" style="color: white; font-size: 1.5rem;">person</span>
                </div>
                <div style="flex: 1; min-width: 0;">
                    <h5 style="margin: 0; font-size: 1.125rem; font-weight: 600; color: #1f2937; line-height: 1.3;">${modulo.encargado}</h5>
                    <p style="margin: 0.25rem 0 0 0; font-size: 0.875rem; color: #6b7280;">Encargado de producción</p>
                </div>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <span style="padding: 0.25rem 0.75rem; background: #ecfdf5; color: #059669; font-size: 0.75rem; font-weight: 500; border-radius: 9999px;">
                        ${Object.keys(asignaciones).length} tallas asignadas
                    </span>
                </div>
            </div>
            
            <!-- Tallas agrupadas por género y color -->
            ${htmlTallas}
        </div>
    `;
}

// Función para generar HTML de tallas para un encargado específico
function generarHtmlTallasParaEncargado(tallas, moduloId, asignaciones) {
    const grupos = agruparTallasPorGeneroYColor(tallas);
    let html = '';
    
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
                    <div style="display: flex; align-items: center; margin-bottom: 0.5rem;">
                        ${colorDisplay ? `
                            <span style="display: inline-block; width: 16px; height: 16px; ${colorStyle} border-radius: 4px; margin-right: 0.5rem;"></span>
                            <span style="font-size: 0.875rem; font-weight: 500; color: #374151;">${color}</span>
                        ` : `
                            <span style="font-size: 0.875rem; font-weight: 500; color: #6b7280;">Sin color</span>
                        `}
                    </div>
                    <div style="display: grid; gap: 0.5rem;">
            `;
            
            tallasColor.forEach(talla => {
                // Crear un ID único que incluya el color y género para evitar colisiones
                const tallaIdUnico = construirTallaIdUnico(talla.tallaOriginal, color, talla.genero);
                
                // Verificar si esta talla específica (con color) está asignada a este encargado
                let asignado = 0;
                if (typeof asignaciones[tallaIdUnico] === 'object' && asignaciones[tallaIdUnico] !== null) {
                    asignado = asignaciones[tallaIdUnico].cantidad || 0;
                } else if (typeof asignaciones[tallaIdUnico] === 'number') {
                    asignado = asignaciones[tallaIdUnico];
                }
                
                const maxDisponible = getMaxDisponibleParaModulo(tallaIdUnico, moduloId);
                const disponible = getDisponibleRestanteGlobal(tallaIdUnico);
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
                                min="0"
                                max="${maxDisponible}"
                                value="${asignado}"
                                ${isSelected ? '' : 'disabled'}
                                oninput="if(this.value==='')return; const v=parseInt(this.value)||0; const mx=parseInt(this.max)||0; if(v>mx)this.value=mx; if(v<0)this.value=0;"
                                onchange="actualizarAsignacion('${tallaIdUnico}', ${moduloId}, this.value)"
                                style="width: 70px; text-align: center; padding: 0.25rem; border: 1px solid #d1d5db; border-radius: 4px; font-size: 0.875rem; font-weight: 500;"
                            />
                            <div class="dist-disp" data-tallaid="${tallaIdUnico}" data-moduloid="${moduloId}" style="font-size: 0.75rem; color: #dc2626; font-weight: 500;">
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
    
    return html;
}

// Función para mostrar interfaz normal de distribución (no edición)
function mostrarInterfazDistribucionNormal(tallas, modulos) {
    const interfazDiv = document.getElementById('interfazDistribucion');

    window.modulosSeleccionadosDistribucion = [];
    
    let html = `
        <!-- Selector de módulos -->
        <div style="margin-bottom: 1.5rem;">
            <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: #374151;">Seleccionar Módulo:</label>
            <div style="display: flex; gap: 0.5rem;">
                <div style="position: relative; flex: 1;">
                    <input type="text" id="moduloSelector" list="listaModulosDisponibles" 
                        placeholder="Seleccione o escriba un módulo para asignar tallas..." 
                        style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 8px; background: white; font-size: 0.875rem; outline: none; transition: all 0.2s;"
                        onfocus="this.style.borderColor='#10b981'; this.style.boxShadow='0 0 0 3px rgba(16, 185, 129, 0.1)';"
                        onblur="this.style.borderColor='#d1d5db'; this.style.boxShadow='none';"
                        onkeypress="if(event.key === 'Enter') agregarEncargadoSeleccionado()"
                    >
                    <datalist id="listaModulosDisponibles">
                        ${modulos.map(modulo => `<option value="${modulo.encargado}"></option>`).join('')}
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
        
        <!-- Cards de encargados seleccionados -->
        <div id="cardsEncargadosPlaceholder" style="min-height: 120px;">
            <div style="text-align: center; padding: 2rem; color: #6b7280;">
                <span class="material-symbols-rounded" style="font-size: 2rem; display: block; margin-bottom: 0.75rem;">playlist_add_check</span>
                <p style="font-size: 0.875rem; margin: 0;">Seleccione un módulo para ver las tallas disponibles</p>
            </div>
        </div>
        <div id="cardsEncargadosSeleccionados" style="display: grid; gap: 1rem;"></div>
    `;
    
    interfazDiv.innerHTML = html;
}

window.agregarEncargadoSeleccionado = function() {
    const moduloSelector = document.getElementById('moduloSelector');
    if (!moduloSelector || !window.datosDistribucion) return;

    const valor = moduloSelector.value.trim();
    if (!valor) return;

    const modulos = window.datosDistribucion.modulos;
    let moduloId;
    
    // Buscar si el valor corresponde a un módulo existente
    const moduloExistente = modulos.find(m => m.encargado === valor);
    
    if (moduloExistente) {
        moduloId = moduloExistente.id;
    } else {
        // Es un nuevo taller/módulo, crearlo virtualmente
        moduloId = Date.now(); // ID único temporal
        modulos.push({
            id: moduloId,
            nombre: valor,
            encargado: valor,
            usuarioId: null
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
};

window.renderCardsEncargadosSeleccionados = function() {
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
                <option value="">Seleccione un módulo para asignar tallas...</option>
                ${modulos.map(modulo => `<option value="${modulo.id}">${modulo.encargado}</option>`).join('')}
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
            .map(modulo => `<option value="${modulo.encargado}"></option>`)
            .join('');
    }

    container.innerHTML = selected
        .map((moduloId) => {
            const modulo = modulos.find((m) => m.id === moduloId);
            if (!modulo) return '';

            const htmlTallas = generarHtmlTallasAgrupadas(tallas, moduloId);

            return `
                <div style="background: white; border: 1px solid #d1d5db; border-radius: 12px; padding: 1rem; box-shadow: 0 1px 3px rgba(0,0,0,0.06);">
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin-bottom: 1rem;">
                        <div style="min-width: 0;">
                            <h6 style="margin: 0; font-size: 1rem; font-weight: 700; color: #1f2937; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${modulo.encargado || ''}</h6>
                            <p style="margin: 0.25rem 0 0 0; font-size: 0.75rem; color: #6b7280;">Encargado seleccionado</p>
                        </div>
                        <button onclick="eliminarModuloSeleccionado(${moduloId})" 
                            style="width: 32px; height: 32px; border: none; background: #fee2e2; color: #dc2626; border-radius: 8px; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s;"
                            onmouseover="this.style.background='#fecaca'"
                            onmouseout="this.style.background='#fee2e2'"
                            title="Eliminar encargado"
                        >
                            <span class="material-symbols-rounded" style="font-size: 1.25rem;">delete</span>
                        </button>
                    </div>
                    ${htmlTallas}
                </div>
            `;
        })
        .join('');

    refrescarDistribucionUI();
    actualizarResumenAsignaciones();
};

window.eliminarModuloSeleccionado = function(moduloId) {
    if (!Array.isArray(window.modulosSeleccionadosDistribucion)) return;
    
    window.modulosSeleccionadosDistribucion = window.modulosSeleccionadosDistribucion.filter(id => id !== moduloId);
    
    // También eliminar las asignaciones de este módulo
    if (window.asignacionesPorModulo && window.asignacionesPorModulo[moduloId]) {
        delete window.asignacionesPorModulo[moduloId];
    }
    
    window.renderCardsEncargadosSeleccionados();
};

window.toggleTallaSeleccion = function(talla, moduloId, checked) {
    console.log(`[TOGGLE TALLA] Iniciando - Talla: ${talla}, Módulo: ${moduloId}, Checked: ${checked}`);
    
    const input = document.getElementById(`talla_${talla}_modulo_${moduloId}`);
    console.log(`[TOGGLE TALLA] Input encontrado:`, input);
    
    if (!input) return;

    const maxValue = getMaxDisponibleParaModulo(talla, moduloId);
    input.max = String(maxValue);
    console.log(`[TOGGLE TALLA] Max value: ${maxValue}`);
    
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
    // Al seleccionar, poner automáticamente la cantidad disponible (máximo disponible para este módulo)
    input.value = maxValue;
    console.log(`[TOGGLE TALLA] Asignando valor: ${maxValue}`);
    actualizarAsignacion(talla, moduloId, maxValue);

    console.log(`[TOGGLE TALLA] Actualizando interfaz...`);
    if (typeof window.renderCardsEncargadosSeleccionados === 'function') {
        window.renderCardsEncargadosSeleccionados();
        return;
    }

    refrescarDistribucionUI();
    console.log(`[TOGGLE TALLA] Función completada`);
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

    if (typeof window.renderCardsEncargadosSeleccionados === 'function') {
        window.renderCardsEncargadosSeleccionados();
        return;
    }

    refrescarDistribucionUI();
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
    
    const color = getColorParaTallaId(talla);
    
    if (cantidad > 0) {
        window.asignacionesPorModulo[moduloId][talla] = {
            cantidad: cantidad,
            color: color
        };
    } else {
        delete window.asignacionesPorModulo[moduloId][talla];
    }
    
    // Si el módulo quedó vacío, eliminarlo
    if (Object.keys(window.asignacionesPorModulo[moduloId]).length === 0) {
        delete window.asignacionesPorModulo[moduloId];
    }

    if (typeof window.renderCardsEncargadosSeleccionados === 'function') {
        window.renderCardsEncargadosSeleccionados();
        return;
    }
};

// Función para obtener el total asignado de una talla (excepto el módulo actual)
function getTotalAsignadoTalla(talla, moduloIdExcluir = null) {
    if (!window.asignacionesPorModulo) return 0;
    
    let total = 0;
    for (const [moduloId, asignaciones] of Object.entries(window.asignacionesPorModulo)) {
        if (parseInt(moduloId) !== moduloIdExcluir && asignaciones[talla]) {
            // Manejar tanto el formato antiguo (número) como el nuevo (objeto)
            let cantidad = 0;
            if (typeof asignaciones[talla] === 'object' && asignaciones[talla] !== null) {
                cantidad = asignaciones[talla].cantidad || 0;
            } else if (typeof asignaciones[talla] === 'number') {
                cantidad = asignaciones[talla];
            }
            total += cantidad;
        }
    }
    return total;
}

function normalizarColor(color) {
    const colorLimpio = String(color || '').trim().toLowerCase();
    if (!colorLimpio || colorLimpio === 'sin color') {
        return 'sin_color';
    }
    return colorLimpio.replace(/\s+/g, '_');
}

function normalizarGenero(genero) {
    const genLimpio = String(genero || '').trim().toLowerCase();
    if (!genLimpio || genLimpio === 'sin género' || genLimpio === 'sin genero') {
        return 'sin_genero';
    }
    return genLimpio.replace(/\s+/g, '_');
}
function parseTallaIdUnico(tallaId) {
    const raw = String(tallaId || '');
    const parts = raw.split('_');
    
    // Formato: Talla_Color_Genero
    // Ejemplo: M_azul_marino_dama
    
    if (parts.length < 3) {
        return { base: parts[0] || raw, colorNorm: parts[1] || '', generoNorm: '' };
    }
    
    const base = parts[0];
    const generoNorm = parts[parts.length - 1];
    const colorNorm = parts.slice(1, -1).join('_');
    
    return { base, colorNorm, generoNorm };
}

function getTotalOriginalTallaId(tallaId) {
    const { base, colorNorm, generoNorm } = parseTallaIdUnico(tallaId);
    const tallas = window?.datosDistribucion?.tallas || [];
    const colorObjetivo = colorNorm || 'sin_color';

    const item = tallas.find((t) => {
        const baseT = (t.tallaOriginal || (String(t.talla || '').split(' ')[0])) || '';
        if (String(baseT) !== String(base)) return false;
        
        const c = normalizarColor(t.color);
        if (c !== colorObjetivo) return false;
        
        const g = normalizarGenero(t.genero);
        return g === generoNorm;
    });

    return parseInt(item?.cantidad) || 0;
}

function getColorParaTallaId(tallaId) {
    const { base, colorNorm, generoNorm } = parseTallaIdUnico(tallaId);
    const tallas = window?.datosDistribucion?.tallas || [];
    const colorObjetivo = colorNorm || 'sin_color';

    const item = tallas.find((t) => {
        const baseT = (t.tallaOriginal || (String(t.talla || '').split(' ')[0])) || '';
        if (String(baseT) !== String(base)) return false;
        
        const c = normalizarColor(t.color);
        if (c !== colorObjetivo) return false;
        
        const g = normalizarGenero(t.genero);
        return g === generoNorm;
    });

    return item?.color || null;
}
function getGeneroParaTallaId(tallaId) {
    const { base, colorNorm, generoNorm } = parseTallaIdUnico(tallaId);
    const tallas = window?.datosDistribucion?.tallas || [];
    const colorObjetivo = colorNorm || 'sin_color';

    const item = tallas.find((t) => {
        const baseT = (t.tallaOriginal || (String(t.talla || '').split(' ')[0])) || '';
        if (String(baseT) !== String(base)) return false;
        
        const c = normalizarColor(t.color);
        if (c !== colorObjetivo) return false;
        
        const g = normalizarGenero(t.genero);
        return g === generoNorm;
    });

    return item?.genero || null;
}

function getDisponibleRestanteGlobal(tallaId) {
    const totalOriginal = getTotalOriginalTallaId(tallaId);
    const asignadoTotal = getTotalAsignadoTalla(tallaId, null);
    return Math.max(0, totalOriginal - asignadoTotal);
}

function getMaxDisponibleParaModulo(tallaId, moduloId) {
    const totalOriginal = getTotalOriginalTallaId(tallaId);
    const totalAsignadoOtros = getTotalAsignadoTalla(tallaId, moduloId);
    const max = Math.max(0, totalOriginal - totalAsignadoOtros);
    console.log(`[MAX DISPONIBLE] Talla ID: ${tallaId}, Total original: ${totalOriginal}, Asignado otros: ${totalAsignadoOtros}, Max: ${max}`);
    return max;
}

function refrescarDistribucionUI() {
    const inputs = document.querySelectorAll('input.dist-talla-input[data-tallaid][data-moduloid]');
    inputs.forEach((input) => {
        const tallaId = input.dataset.tallaid;
        const moduloId = parseInt(input.dataset.moduloid);
        if (!tallaId || !Number.isFinite(moduloId)) return;

        const max = getMaxDisponibleParaModulo(tallaId, moduloId);
        input.max = String(max);

        const asignado = (() => {
            const v = window.asignacionesPorModulo?.[moduloId]?.[tallaId];
            if (typeof v === 'object' && v !== null) return parseInt(v.cantidad) || 0;
            if (typeof v === 'number') return parseInt(v) || 0;
            return 0;
        })();

        if (asignado > max) {
            if (window.asignacionesPorModulo?.[moduloId]?.[tallaId]) {
                if (typeof window.asignacionesPorModulo[moduloId][tallaId] === 'object' && window.asignacionesPorModulo[moduloId][tallaId] !== null) {
                    window.asignacionesPorModulo[moduloId][tallaId].cantidad = max;
                } else {
                    window.asignacionesPorModulo[moduloId][tallaId] = max;
                }
            }
            input.value = String(max);
        }

        if (!input.disabled) {
            const cur = parseInt(input.value) || 0;
            if (cur > max) {
                input.value = String(max);
                if (window.asignacionesPorModulo?.[moduloId]?.[tallaId]) {
                    if (typeof window.asignacionesPorModulo[moduloId][tallaId] === 'object' && window.asignacionesPorModulo[moduloId][tallaId] !== null) {
                        window.asignacionesPorModulo[moduloId][tallaId].cantidad = max;
                    } else {
                        window.asignacionesPorModulo[moduloId][tallaId] = max;
                    }
                }
            }
        }

        const check = input.closest('.dist-talla-row')?.querySelector('input.dist-talla-check[data-tallaid][data-moduloid]');
        const row = input.closest('.dist-talla-row');
        const selected = asignado > 0;
        if (check) check.checked = selected;
        if (row) row.classList.toggle('is-selected', selected);
        input.disabled = !selected;
        if (selected) input.value = String(asignado);
        if (!selected) input.value = '0';
    });

    const disps = document.querySelectorAll('.dist-disp[data-tallaid][data-moduloid]');
    disps.forEach((el) => {
        const tallaId = el.dataset.tallaid;
        if (!tallaId) return;
        el.textContent = `Disp: ${getDisponibleRestanteGlobal(tallaId)}`;
    });
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
            .map(([talla, datos]) => {
                // Manejar tanto el formato antiguo (número) como el nuevo (objeto)
                let cantidad = 0;
                if (typeof datos === 'object' && datos !== null) {
                    cantidad = datos.cantidad || 0;
                } else if (typeof datos === 'number') {
                    cantidad = datos;
                }
                return `${talla}×${cantidad}`;
            })
            .join(', ');
        
        const nombreModuloResumen = modulo.encargado || modulo.nombre || `Módulo ${moduloId}`;

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

        const { pedidoId, prendaId, prendaBodegaId, tipoRecibo, recibo } = window.datosModalCostura;
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
            const { base } = parseTallaIdUnico(tallaRaw);
            const s = String(base || '').trim();
            const m = s.match(/^(.+?)\s*\((.+)\)$/);
            return m ? m[1].trim() : s;
        };

        const asignaciones = Object.entries(window.asignacionesPorModulo)
            .map(([moduloIdStr, asignacionesTallas]) => {
                const moduloId = parseInt(moduloIdStr);
                const modulo = modulos.find((m) => m.id === moduloId);
                const encargado = (modulo?.encargado || '').trim();

                const tallas = Object.entries(asignacionesTallas || {})
                    .map(([tallaRaw, datos]) => {
                        let cantidad, color;
                        
                        if (typeof datos === 'object' && datos !== null) {
                            cantidad = parseInt(datos.cantidad) || 0;
                            color = datos.color || null;
                        } else {
                            cantidad = parseInt(datos) || 0;
                            color = null;
                        }

                        const { base, colorNorm, generoNorm } = parseTallaIdUnico(tallaRaw);
                        const itemTalla = window.datosDistribucion.tallas.find(t => {
                            const baseT = (t.tallaOriginal || (String(t.talla || '').split(' ')[0])) || '';
                            return baseT === base && normalizarColor(t.color) === colorNorm && normalizarGenero(t.genero) === generoNorm;
                        });

                        return {
                            talla: base,
                            cantidad: cantidad,
                            color_nombre: color,
                            genero: itemTalla ? itemTalla.genero : null,
                        };
                    })
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
                ...(prendaBodegaId ? { prenda_bodega_id: prendaBodegaId } : {}),
                tipo_recibo: tipoRecibo,
                asignaciones,
            }),
        })
            .then((r) => r.json())
            .then(async (data) => {
                if (data?.success) {
                    cerrarModalCostura();
                    mostrarExito('Éxito', data?.message || 'La distribución del recibo fue exitosa');

                    if (
                        document.getElementById('ordenesList') &&
                        typeof window.__actualizarDashboardSinRecargar === 'function'
                    ) {
                        try {
                            await window.__actualizarDashboardSinRecargar();
                        } catch (refreshError) {
                            console.warn('No se pudo actualizar el dashboard tras distribuir el recibo:', refreshError);
                        }
                    }
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
    } else if (window.opcionAsignacionSeleccionada === 'taller') {
        confirmarDistribucionTaller();
    }
}

// Función para confirmar distribución a taller
function confirmarDistribucionTaller() {
    const tipoDistribucion = window.tipoDistribucionTaller || 'unico';
    const btnConfirmar = document.getElementById('btnConfirmarAsignacion');
    const originalText = btnConfirmar ? btnConfirmar.innerHTML : null;
    
    const { pedidoId, prendaId, prendaBodegaId, tipoRecibo, recibo, esEdicion } = window.datosModalCostura;
    
    if (!pedidoId || !prendaId || !tipoRecibo || !recibo) {
        mostrarError('Error', 'Faltan datos necesarios para procesar la solicitud');
        return;
    }
    
    let tallerData = {};
    
    if (tipoDistribucion === 'unico') {
        // Un solo taller
        const tallerNombre = document.getElementById('tallerUnicoSelector')?.value.trim();
        if (!tallerNombre) {
            mostrarError('Error', 'Debe seleccionar un taller');
            return;
        }
        
        tallerData = {
            tipo_distribucion: 'taller',
            subtipo_taller: 'unico',
            encargado: tallerNombre,
        };
    } else {
        // Múltiples talleres
        if (!window.asignacionesPorTaller || Object.keys(window.asignacionesPorTaller).length === 0) {
            mostrarError('Error', 'No hay asignaciones realizadas');
            return;
        }
        
        const asignaciones = Object.entries(window.asignacionesPorTaller)
            .map(([tallerIdStr, asignacionesTallas]) => {
                const tallerId = parseInt(tallerIdStr);
                const taller = window.talleresSeleccionadosDistribucion?.find(t => {
                    // Comparar por ID (puede ser negativo si es nuevo)
                    return parseInt(t.id) === tallerId;
                });
                const encargado = taller?.nombre || '';
                
                const tallas = Object.entries(asignacionesTallas || {})
                    .map(([tallaRaw, cantidad]) => {
                        const cantidadNum = parseInt(cantidad) || 0;
                        const { base, colorNorm, generoNorm } = parseTallaIdUnico(tallaRaw);
                        const itemTalla = window.datosDistribucion.tallas.find(t => {
                            const baseT = (t.tallaOriginal || (String(t.talla || '').split(' ')[0])) || '';
                            return baseT === base && normalizarColor(t.color) === colorNorm && normalizarGenero(t.genero) === generoNorm;
                        });
                        
                        return {
                            talla: base,
                            cantidad: cantidadNum,
                            color_nombre: itemTalla ? itemTalla.color : null,
                            genero: itemTalla ? itemTalla.genero : null,
                        };
                    })
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
        
        tallerData = {
            tipo_distribucion: 'taller',
            subtipo_taller: 'multiple',
            asignaciones,
            es_edicion: esEdicion || false,
        };
    }
    
    const action = `/recibos-novedades/${pedidoId}/${recibo}/pasar-a-taller`;
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
            ...(prendaBodegaId ? { prenda_bodega_id: prendaBodegaId } : {}),
            tipo_recibo: tipoRecibo,
            ...tallerData,
        }),
    })
        .then((r) => r.json())
        .then(async (data) => {
            if (data?.success) {
                cerrarModalCostura();
                mostrarExito('Éxito', data?.message || 'El recibo fue asignado a taller correctamente');
                
                if (
                    document.getElementById('ordenesList') &&
                    typeof window.__actualizarDashboardSinRecargar === 'function'
                ) {
                    try {
                        await window.__actualizarDashboardSinRecargar();
                    } catch (refreshError) {
                        console.warn('No se pudo actualizar el dashboard tras asignar a taller:', refreshError);
                    }
                }
            } else {
                mostrarError('Error', data?.message || 'No se pudo asignar a taller');
            }
        })
        .catch((err) => {
            console.error('Error asignando a taller:', err);
            mostrarError('Error', 'Error de conexión: ' + (err?.message || err));
        })
        .finally(() => {
            if (btnConfirmar) {
                btnConfirmar.disabled = false;
                if (originalText !== null) btnConfirmar.innerHTML = originalText;
            }
        });
}

// Función para cargar usuarios de costura (copiada de costura.js)
function cargarUsuariosCostura(tipoRecibo = '') {
    const datalist = document.getElementById('listaEncargados');
    if (!datalist) return;

    datalist.innerHTML = '';

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
            if (data.success && data.usuarios) {
                data.usuarios.forEach((usuario) => {
                    const option = document.createElement('option');
                    option.value = usuario.name;
                    datalist.appendChild(option);
                });
            }
        })
        .catch((error) => {
            console.error('Error cargando usuarios de costura:', error);
        });
}

// Función para mostrar contenido de distribución a taller
function mostrarContenidoTaller() {
    const contenidoDiv = document.getElementById('contenidoAsignacion');
    
    contenidoDiv.innerHTML = `
        <div style="display: grid; grid-template-columns: 1fr; gap: 1rem;">
            <!-- Opción: Un solo taller -->
            <button type="button" id="btnTallerUnico" onclick="seleccionarTipoTaller('unico')" style="padding: 1rem; border: 2px solid #e2e8f0; border-radius: 12px; background: white; cursor: pointer; transition: all 0.2s; text-align: left;">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <div style="width: 40px; height: 40px; background: #8b5cf6; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <span class="material-symbols-rounded" style="color: white; font-size: 1.25rem;">person</span>
                    </div>
                    <div style="flex: 1; min-width: 0;">
                        <h5 style="margin: 0; font-size: 0.875rem; font-weight: 600; color: #1e293b; line-height: 1.3;">Un solo taller</h5>
                        <p style="margin: 0.25rem 0 0 0; font-size: 0.75rem; color: #64748b; line-height: 1.3;">Todas las unidades a un único taller externo</p>
                    </div>
                </div>
            </button>

            <!-- Opción: Múltiples talleres -->
            <button type="button" id="btnTallerMultiple" onclick="seleccionarTipoTaller('multiple')" style="padding: 1rem; border: 2px solid #e2e8f0; border-radius: 12px; background: white; cursor: pointer; transition: all 0.2s; text-align: left;">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <div style="width: 40px; height: 40px; background: #ec4899; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <span class="material-symbols-rounded" style="color: white; font-size: 1.25rem;">groups</span>
                    </div>
                    <div style="flex: 1; min-width: 0;">
                        <h5 style="margin: 0; font-size: 0.875rem; font-weight: 600; color: #1e293b; line-height: 1.3;">Múltiples talleres</h5>
                        <p style="margin: 0.25rem 0 0 0; font-size: 0.75rem; color: #64748b; line-height: 1.3;">Repartir entre diversos talleres externos</p>
                    </div>
                </div>
            </button>
        </div>
    `;
}

// Función para seleccionar tipo de taller
window.seleccionarTipoTaller = function(tipo) {
    window.tipoDistribucionTaller = tipo;
    
    if (tipo === 'unico') {
        mostrarContenidoTallerUnico();
    } else {
        mostrarContenidoTallerMultiple();
    }
    
    // Mostrar botón volver
    document.getElementById('btnVolver').style.display = 'inline-flex';
};

// Función para mostrar contenido de un solo taller
function mostrarContenidoTallerUnico() {
    const contenidoDiv = document.getElementById('contenidoAsignacion');
    
    contenidoDiv.innerHTML = `
        <div>
            <h4 style="margin: 0 0 1rem 0; font-size: 1rem; font-weight: 600; color: #1e293b;">Seleccionar Taller</h4>
            
            <div style="position: relative; margin-bottom: 1rem;">
                <div style="position: relative;">
                    <span class="material-symbols-rounded" style="position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); color: #9ca3af; font-size: 1.25rem; pointer-events: none;">search</span>
                    <input type="text" id="tallerUnicoSelector" list="listaTalleresUnicos" 
                        placeholder="Buscar y agregar..." 
                        style="width: 100%; padding: 0.75rem 0.75rem 0.75rem 2.5rem; border: 2px solid #d1d5db; border-radius: 8px; background: white; font-size: 0.875rem; transition: all 0.2s; outline: none; box-sizing: border-box;"
                        onfocus="this.style.borderColor='#3b82f6'; this.style.boxShadow='0 0 0 3px rgba(59, 130, 246, 0.1)';"
                        onblur="this.style.borderColor='#d1d5db'; this.style.boxShadow='none';"
                    >
                    <datalist id="listaTalleresUnicos"></datalist>
                </div>
            </div>
            
            <!-- Resumen visual -->
            <div style="padding: 0.75rem; background: #f0f9ff; border-radius: 8px; border: 1px solid #bfdbfe;">
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <span class="material-symbols-rounded" style="color: #1e40af; font-size: 1rem;">info</span>
                    <div style="flex: 1; min-width: 0;">
                        <p style="margin: 0; font-size: 0.75rem; font-weight: 600; color: #1e40af;">Taller seleccionado:</p>
                        <p id="textoEstadoTallerUnico" style="margin: 0.25rem 0 0 0; font-size: 0.75rem; color: #1e40af;">Ninguno</p>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Cargar talleres disponibles
    cargarTalleresDisponibles();
    
    // Agregar listeners para actualizar el estado visual
    setTimeout(() => {
        const input = document.getElementById('tallerUnicoSelector');
        const textoEstado = document.getElementById('textoEstadoTallerUnico');

        const actualizarEstado = () => {
            const val = input?.value.trim();
            if (val) {
                if (textoEstado) textoEstado.textContent = val;
            } else {
                if (textoEstado) textoEstado.textContent = 'Ninguno';
            }
        };

        if (input) {
            input.oninput = actualizarEstado;
            input.onchange = actualizarEstado;
        }
    }, 100);
}

// Función para mostrar contenido de múltiples talleres
function mostrarContenidoTallerMultiple() {
    const contenidoDiv = document.getElementById('contenidoAsignacion');
    
    contenidoDiv.innerHTML = `
        <div>
            <h4 style="margin: 0 0 1rem 0; font-size: 1rem; font-weight: 600; color: #1e293b;">Múltiples Destinos</h4>
            
            <div style="position: relative; margin-bottom: 1rem;">
                <div style="position: relative;">
                    <span class="material-symbols-rounded" style="position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); color: #9ca3af; font-size: 1.25rem; pointer-events: none;">search</span>
                    <input type="text" id="tallerMultipleSelector" list="listaTalleresMultiplesDatalist" 
                        placeholder="Buscar y agregar..." 
                        style="width: 100%; padding: 0.75rem 0.75rem 0.75rem 2.5rem; border: 2px solid #d1d5db; border-radius: 8px; background: white; font-size: 0.875rem; transition: all 0.2s; outline: none; box-sizing: border-box;"
                        onfocus="this.style.borderColor='#3b82f6'; this.style.boxShadow='0 0 0 3px rgba(59, 130, 246, 0.1)';"
                        onblur="this.style.borderColor='#d1d5db'; this.style.boxShadow='none';"
                        onkeypress="if(event.key === 'Enter') agregarTallerSeleccionado()"
                        onchange="setTimeout(() => agregarTallerSeleccionado(), 100)"
                    >
                    <datalist id="listaTalleresMultiplesDatalist"></datalist>
                </div>
            </div>
            
            <div style="margin-bottom: 1rem;">
                <label style="display: block; margin-bottom: 0.75rem; font-weight: 600; font-size: 0.875rem; color: #1e293b;">
                    Seleccionados (0)
                </label>
                <div id="listaTalleresSeleccionados" style="display: grid; gap: 0.75rem; min-height: 2rem;">
                    <p style="text-align: center; color: #9ca3af; padding: 1rem; font-size: 0.875rem; grid-column: 1/-1;">No hay talleres seleccionados</p>
                </div>
            </div>
            
            <!-- Interfaz de distribución de tallas -->
            <div id="interfazDistribucionTaller" style="margin-top: 1rem;">
                <!-- Se cargará dinámicamente -->
            </div>
        </div>
    `;
    
    // Cargar talleres disponibles
    cargarTalleresParaDistribucion();
}

// Función para cargar talleres disponibles
function cargarTalleresDisponibles() {
    const datalist = document.getElementById('listaTalleresUnicos');
    if (!datalist) return;
    
    datalist.innerHTML = '';
    
    httpJson('/api/usuarios/taller')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.usuarios) {
                data.usuarios.forEach((usuario) => {
                    const option = document.createElement('option');
                    option.value = usuario.name;
                    datalist.appendChild(option);
                });
            }
        })
        .catch((error) => {
            console.error('Error cargando talleres:', error);
        });
}

// Función para cargar talleres para distribución múltiple
function cargarTalleresParaDistribucion() {
    const datalist = document.getElementById('listaTalleresMultiplesDatalist');
    if (!datalist) return;
    
    datalist.innerHTML = '';
    
    httpJson('/api/usuarios/taller')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.usuarios) {
                // Guardar datos de talleres
                window.talleresDisponibles = data.usuarios;
                
                // Obtener IDs de talleres ya seleccionados
                const talleresSeleccionadosIds = (window.talleresSeleccionadosDistribucion || []).map(t => t.id);
                
                // Llenar datalist solo con talleres no seleccionados
                data.usuarios.forEach((usuario) => {
                    // No agregar si ya está seleccionado
                    if (!talleresSeleccionadosIds.includes(usuario.id)) {
                        const option = document.createElement('option');
                        option.value = usuario.name;
                        datalist.appendChild(option);
                    }
                });
            }
        })
        .catch((error) => {
            console.error('Error cargando talleres para distribución:', error);
        });
}

// Función para agregar taller seleccionado
window.agregarTallerSeleccionado = function() {
    const input = document.getElementById('tallerMultipleSelector');
    const tallerNombre = input?.value.trim();
    
    // Si el input está vacío, no hacer nada
    if (!tallerNombre) {
        return;
    }
    
    // Verificar que el taller existe
    let taller = window.talleresDisponibles?.find(t => t.name === tallerNombre);
    
    // Si no existe, crear un objeto temporal para el nuevo taller
    if (!taller) {
        // Generar un ID temporal (negativo para indicar que es nuevo)
        const nuevoId = -(Math.random() * 10000 | 0);
        taller = {
            id: nuevoId,
            name: tallerNombre,
            esNuevo: true // Marcar como nuevo
        };
        
        console.log('[AGREGAR TALLER] Nuevo taller creado localmente:', taller);
    }
    
    // Verificar que no esté duplicado - si ya está seleccionado, simplemente no hacer nada
    if (window.talleresSeleccionadosDistribucion?.find(t => t.id === taller.id)) {
        input.value = ''; // Limpiar input sin mostrar error
        return;
    }
    
    // Agregar taller
    if (!window.talleresSeleccionadosDistribucion) {
        window.talleresSeleccionadosDistribucion = [];
    }
    
    window.talleresSeleccionadosDistribucion.push({
        id: taller.id,
        nombre: taller.name,
        esNuevo: taller.esNuevo || false
    });
    
    // Limpiar input
    input.value = '';
    
    // Actualizar UI
    actualizarListaTalleresSeleccionados();
    
    // Recargar la datalist para ocultar el taller que acaba de ser agregado
    cargarTalleresParaDistribucion();
    
    // Cargar interfaz de distribución
    if (window.talleresSeleccionadosDistribucion.length > 0) {
        cargarInterfazDistribucionTallerMultiple(window.talleresSeleccionadosDistribucion);
    }
};

// Función para actualizar lista de talleres seleccionados
window.actualizarListaTalleresSeleccionados = function() {
    const listaTalleres = document.getElementById('listaTalleresSeleccionados');
    if (!listaTalleres) return;
    
    const count = window.talleresSeleccionadosDistribucion?.length || 0;
    
    // Actualizar el label con el contador
    const labelElement = document.querySelector('label[style*="Seleccionados"]');
    if (labelElement) {
        labelElement.textContent = `Seleccionados (${count})`;
    }
    
    if (!window.talleresSeleccionadosDistribucion || window.talleresSeleccionadosDistribucion.length === 0) {
        listaTalleres.innerHTML = '<p style="text-align: center; color: #9ca3af; padding: 1rem; font-size: 0.875rem; grid-column: 1/-1;">No hay talleres seleccionados</p>';
        return;
    }
    
    let html = '';
    window.talleresSeleccionadosDistribucion.forEach((taller, index) => {
        const esNuevo = taller.esNuevo;
        html += `
            <div style="display: flex; align-items: center; justify-content: space-between; padding: 0.75rem; background: ${esNuevo ? '#fef3c7' : '#f3f4f6'}; border: 1px solid ${esNuevo ? '#fcd34d' : '#d1d5db'}; border-radius: 8px;">
                <div style="display: flex; align-items: center; gap: 0.75rem; flex: 1;">
                    <span class="material-symbols-rounded" style="color: ${esNuevo ? '#f59e0b' : '#6366f1'}; font-size: 1.25rem;">apartment</span>
                    <div>
                        <div style="font-weight: 600; font-size: 0.875rem; color: #1e293b;">${taller.nombre}</div>
                        ${esNuevo ? '<div style="font-size: 0.75rem; color: #b45309;">Nuevo taller (se creará al confirmar)</div>' : ''}
                    </div>
                </div>
                <button type="button" onclick="removerTallerSeleccionado(${index})" style="background: #fee2e2; border: none; border-radius: 6px; padding: 0.5rem; cursor: pointer; color: #dc2626; transition: all 0.2s;">
                    <span class="material-symbols-rounded" style="font-size: 1rem;">close</span>
                </button>
            </div>
        `;
    });
    
    listaTalleres.innerHTML = html;
};

// Función para remover taller seleccionado
window.removerTallerSeleccionado = function(index) {
    if (window.talleresSeleccionadosDistribucion) {
        window.talleresSeleccionadosDistribucion.splice(index, 1);
        actualizarListaTalleresSeleccionados();
        
        // Recargar la datalist para mostrar el taller removido
        cargarTalleresParaDistribucion();
        
        // Actualizar interfaz de distribución
        if (window.talleresSeleccionadosDistribucion.length > 0) {
            cargarInterfazDistribucionTallerMultiple(window.talleresSeleccionadosDistribucion);
        } else {
            const interfazDiv = document.getElementById('interfazDistribucionTaller');
            if (interfazDiv) {
                interfazDiv.innerHTML = '';
            }
        }
    }
};

// Función para cargar la interfaz de distribución para múltiples talleres
window.cargarInterfazDistribucionTallerMultiple = function(talleresSeleccionados) {
    if (!window.datosModalCostura) {
        console.error('No hay datos de la prenda disponibles');
        return Promise.reject(new Error('No hay datos de la prenda disponibles'));
    }
    
    const { prendaId, tipoRecibo } = window.datosModalCostura;
    
    // Cargar tallas de la prenda
    return cargarTallasPrenda(prendaId, tipoRecibo)
        .then((tallas) => {
            console.log('Tallas cargadas para distribución a taller:', tallas);
            
            // Procesar tallas al formato esperado
            const tallasProcesadas = procesarTallasParaDistribucion(tallas);
            
            // Cargar interfaz con datos reales
            cargarInterfazDistribucionTallerConDatos(tallasProcesadas, talleresSeleccionados);
            
            return tallasProcesadas;
        })
        .catch(error => {
            console.error('Error cargando datos de distribución a taller:', error);
            const interfazDiv = document.getElementById('interfazDistribucionTaller');
            if (interfazDiv) {
                interfazDiv.innerHTML = `
                    <div style="text-align: center; padding: 2rem; color: #dc2626;">
                        <span class="material-symbols-rounded" style="font-size: 2rem; display: block; margin-bottom: 0.75rem;">error</span>
                        <p style="font-size: 0.875rem; margin: 0;">No se pudo cargar la información. Por favor, intente nuevamente.</p>
                    </div>
                `;
            }
            throw error;
        });
}

// Función para cargar la interfaz de distribución de tallas para múltiples talleres
function cargarInterfazDistribucionTallerConDatos(tallas, talleres) {
    const interfazDiv = document.getElementById('interfazDistribucionTaller');
    
    if (!tallas || tallas.length === 0) {
        interfazDiv.innerHTML = `
            <div style="text-align: center; padding: 2rem; color: #6b7280;">
                <span class="material-symbols-rounded" style="font-size: 2rem; display: block; margin-bottom: 0.75rem;">info</span>
                <p style="font-size: 0.875rem; margin: 0;">No hay tallas disponibles para esta prenda</p>
            </div>
        `;
        return;
    }
    
    // Guardar datos de distribución para usar en actualizarDisponibilidad()
    window.datosDistribucion = { tallas, talleres };
    
    // Inicializar asignaciones por taller SOLO si no existen
    if (!window.asignacionesPorTaller) {
        window.asignacionesPorTaller = {};
    }
    
    // Agregar nuevos talleres sin borrar los existentes
    talleres.forEach(taller => {
        if (!window.asignacionesPorTaller[taller.id]) {
            window.asignacionesPorTaller[taller.id] = {};
        }
    });
    
    // Generar HTML para cada taller como card
    let html = '<div style="display: grid; gap: 1.5rem;">';
    
    talleres.forEach(taller => {
        html += `
            <div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; padding: 1.5rem; overflow: hidden;">
                <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid #e5e7eb;">
                    <div style="flex: 1; min-width: 0;">
                        <h6 style="margin: 0; font-size: 0.875rem; font-weight: 600; color: #1e293b;">${taller.nombre}</h6>
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
    
    // Recalcular disponibilidad para todas las tallas después de renderizar
    setTimeout(() => {
        tallas.forEach(talla => {
            talleres.forEach(taller => {
                const tallaIdUnico = construirTallaIdUnico(talla.tallaOriginal, talla.color, talla.genero);
                actualizarDisponibilidad(tallaIdUnico, taller.id);
            });
        });
    }, 100);
}

// Función para generar HTML de tallas para un taller específico (igual a distribuir por módulos)
function generarHtmlTallasParaTaller(tallas, tallerId) {
    const grupos = agruparTallasPorGeneroYColor(tallas);
    let html = '';
    
    Object.entries(grupos).forEach(([genero, colores]) => {
        // Primero, verificar si hay al menos una talla visible en este género
        let tieneAlgunaTallaVisible = false;
        
        Object.entries(colores).forEach(([color, tallasColor]) => {
            tallasColor.forEach(talla => {
                const tallaIdUnico = construirTallaIdUnico(talla.tallaOriginal, color, talla.genero);
                const cantidadTotal = talla.cantidad;
                
                let asignado = 0;
                if (window.asignacionesPorTaller && window.asignacionesPorTaller[tallerId]) {
                    asignado = window.asignacionesPorTaller[tallerId][tallaIdUnico] || 0;
                }
                
                // Calcular cuánto se ha asignado en otros talleres
                let asignadoEnOtrosTalleres = 0;
                if (window.asignacionesPorTaller) {
                    Object.entries(window.asignacionesPorTaller).forEach(([otroTallerId, asignaciones]) => {
                        if (parseInt(otroTallerId) !== parseInt(tallerId)) {
                            asignadoEnOtrosTalleres += asignaciones[tallaIdUnico] || 0;
                        }
                    });
                }
                
                // Calcular disponible inicial
                const disponibleInicial = cantidadTotal - asignado - asignadoEnOtrosTalleres;
                const isSelected = asignado > 0;
                
                // Verificar si esta talla es visible
                if (disponibleInicial > 0 || isSelected) {
                    tieneAlgunaTallaVisible = true;
                }
            });
        });
        
        // Si no hay ninguna talla visible en este género, no mostrar la sección
        if (!tieneAlgunaTallaVisible) {
            return;
        }
        
        // Si hay tallas visibles, generar la sección del género
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
            
            // Verificar si hay al menos una talla visible en este color
            let tieneAlgunaTallaVisibleEnColor = false;
            let htmlTallasColor = '';
            
            tallasColor.forEach(talla => {
                const tallaIdUnico = construirTallaIdUnico(talla.tallaOriginal, color, talla.genero);
                const cantidadTotal = talla.cantidad;
                
                let asignado = 0;
                if (window.asignacionesPorTaller && window.asignacionesPorTaller[tallerId]) {
                    asignado = window.asignacionesPorTaller[tallerId][tallaIdUnico] || 0;
                }
                
                // Calcular cuánto se ha asignado en otros talleres
                let asignadoEnOtrosTalleres = 0;
                if (window.asignacionesPorTaller) {
                    Object.entries(window.asignacionesPorTaller).forEach(([otroTallerId, asignaciones]) => {
                        if (parseInt(otroTallerId) !== parseInt(tallerId)) {
                            asignadoEnOtrosTalleres += asignaciones[tallaIdUnico] || 0;
                        }
                    });
                }
                
                // Calcular disponible inicial
                const disponibleInicial = cantidadTotal - asignado - asignadoEnOtrosTalleres;
                const maxDisponible = cantidadTotal - asignadoEnOtrosTalleres;
                
                const isSelected = asignado > 0;
                
                // NO MOSTRAR si no hay disponibilidad Y no está seleccionada en este taller
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
            
            // Solo mostrar el color si hay al menos una talla visible
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

// Función para toggle de selección de talla en taller
window.toggleTallaSeleccionTaller = function(tallaId, tallerId, isChecked) {
    const input = document.getElementById(`talla_${tallaId}_taller_${tallerId}`);
    if (input) {
        input.disabled = !isChecked;
        if (isChecked) {
            // Al hacer check, establecer valor a la cantidad máxima disponible
            const maxDisponible = parseInt(input.max) || 0;
            input.value = maxDisponible;
            actualizarAsignacionTallerConRegeneracion(tallaId, tallerId, maxDisponible, true);
        } else {
            // Al desmarcar, establecer a 0
            input.value = 0;
            actualizarAsignacionTallerConRegeneracion(tallaId, tallerId, 0, true);
        }
    }
};

// Función auxiliar para actualizar asignación con regeneración de todos los talleres
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
    
    // Actualizar disponibilidad
    actualizarDisponibilidad(tallaId, tallerId);
    
    // Siempre regenerar todos los talleres cuando se hace toggle (check/uncheck)
    if (window.datosDistribucion && window.datosDistribucion.talleres) {
        console.log(`[TOGGLE TALLA] Regenerando todos los talleres después de toggle de ${tallaId}`);
        
        // Regenerar todos los talleres
        window.datosDistribucion.talleres.forEach(taller => {
            const container = document.getElementById(`tallas-taller-${taller.id}`);
            if (container) {
                container.innerHTML = generarHtmlTallasParaTaller(window.datosDistribucion.tallas, taller.id);
            }
        });
        
        // Recalcular disponibilidad para todas las tallas después de regenerar
        setTimeout(() => {
            window.datosDistribucion.tallas.forEach(talla => {
                window.datosDistribucion.talleres.forEach(taller => {
                    const tallaIdUnico = construirTallaIdUnico(talla.tallaOriginal, talla.color, talla.genero);
                    actualizarDisponibilidad(tallaIdUnico, taller.id);
                });
            });
        }, 50);
    }
}

// Función para actualizar asignación de talla en taller
window.actualizarAsignacionTaller = function(tallaId, tallerId, cantidad) {
    if (!window.asignacionesPorTaller) {
        window.asignacionesPorTaller = {};
    }
    
    if (!window.asignacionesPorTaller[tallerId]) {
        window.asignacionesPorTaller[tallerId] = {};
    }
    
    // Guardar la cantidad anterior para detectar cambios
    const cantidadAnterior = window.asignacionesPorTaller[tallerId][tallaId] || 0;
    
    const cantidadNum = parseInt(cantidad) || 0;
    if (cantidadNum > 0) {
        window.asignacionesPorTaller[tallerId][tallaId] = cantidadNum;
    } else {
        delete window.asignacionesPorTaller[tallerId][tallaId];
    }
    
    // Actualizar disponibilidad
    actualizarDisponibilidad(tallaId, tallerId);
    
    // Verificar si la talla se agotó completamente o si volvió a tener disponibilidad
    const tallaOriginal = window.datosDistribucion?.tallas?.find(t => {
        const id = construirTallaIdUnico(t.tallaOriginal, t.color, t.genero);
        return id === tallaId;
    });
    
    if (tallaOriginal) {
        const cantidadTotal = tallaOriginal.cantidad;
        
        // Calcular cuánto se ha asignado en TODOS los talleres
        let asignadoTotal = 0;
        if (window.asignacionesPorTaller) {
            Object.entries(window.asignacionesPorTaller).forEach(([otroTallerId, asignaciones]) => {
                asignadoTotal += asignaciones[tallaId] || 0;
            });
        }
        
        // Calcular disponibilidad actual
        const disponibilidadActual = cantidadTotal - asignadoTotal;
        
        // Calcular disponibilidad anterior
        const asignadoTotalAnterior = asignadoTotal + (cantidadAnterior - cantidadNum);
        const disponibilidadAnterior = cantidadTotal - asignadoTotalAnterior;
        
        // Regenerar TODOS los talleres si:
        // 1. La talla se agotó completamente (disponibilidadActual === 0)
        // 2. La talla volvió a tener disponibilidad (disponibilidadAnterior === 0 && disponibilidadActual > 0)
        const debeRegenerarTodos = 
            (disponibilidadActual === 0 && disponibilidadAnterior > 0) || // Se agotó
            (disponibilidadAnterior === 0 && disponibilidadActual > 0);   // Volvió a haber disponibilidad
        
        if (debeRegenerarTodos) {
            console.log(`[ACTUALIZAR ASIGNACIÓN] Cambio en disponibilidad de ${tallaId}. Anterior: ${disponibilidadAnterior}, Actual: ${disponibilidadActual}. Regenerando todos los talleres...`);
            
            if (window.datosDistribucion && window.datosDistribucion.talleres) {
                // Regenerar todos los talleres
                window.datosDistribucion.talleres.forEach(taller => {
                    const container = document.getElementById(`tallas-taller-${taller.id}`);
                    if (container) {
                        container.innerHTML = generarHtmlTallasParaTaller(window.datosDistribucion.tallas, taller.id);
                    }
                });
                
                // Recalcular disponibilidad para todas las tallas después de regenerar
                setTimeout(() => {
                    window.datosDistribucion.tallas.forEach(talla => {
                        window.datosDistribucion.talleres.forEach(taller => {
                            const tallaIdUnico = construirTallaIdUnico(talla.tallaOriginal, talla.color, talla.genero);
                            actualizarDisponibilidad(tallaIdUnico, taller.id);
                        });
                    });
                }, 50);
            }
        } else {
            // Si no hay cambio crítico en disponibilidad, solo regenerar el taller actual
            if (window.datosDistribucion && window.datosDistribucion.talleres) {
                const container = document.getElementById(`tallas-taller-${tallerId}`);
                if (container) {
                    container.innerHTML = generarHtmlTallasParaTaller(window.datosDistribucion.tallas, tallerId);
                }
                
                // Recalcular disponibilidad para todas las tallas después de regenerar
                setTimeout(() => {
                    window.datosDistribucion.tallas.forEach(talla => {
                        window.datosDistribucion.talleres.forEach(taller => {
                            const tallaIdUnico = construirTallaIdUnico(talla.tallaOriginal, talla.color, talla.genero);
                            actualizarDisponibilidad(tallaIdUnico, taller.id);
                        });
                    });
                }, 50);
            }
        }
    }
    
    console.log('Asignaciones por taller actualizadas:', window.asignacionesPorTaller);
};

// Función para actualizar disponibilidad de una talla
function actualizarDisponibilidad(tallaId, tallerId) {
    // Obtener la cantidad total de la talla
    const tallaOriginal = window.datosDistribucion?.tallas?.find(t => {
        const id = construirTallaIdUnico(t.tallaOriginal, t.color, t.genero);
        return id === tallaId;
    });
    
    if (!tallaOriginal) return;
    
    const cantidadTotal = tallaOriginal.cantidad;
    
    // Calcular cuánto se ha asignado en otros talleres
    let asignadoEnOtrosTalleres = 0;
    if (window.asignacionesPorTaller) {
        Object.entries(window.asignacionesPorTaller).forEach(([otroTallerId, asignaciones]) => {
            if (parseInt(otroTallerId) !== parseInt(tallerId)) {
                asignadoEnOtrosTalleres += asignaciones[tallaId] || 0;
            }
        });
    }
    
    // Obtener cantidad asignada en este taller
    const asignadoEnEsteTaller = window.asignacionesPorTaller?.[tallerId]?.[tallaId] || 0;
    
    // Calcular disponible
    const disponible = cantidadTotal - asignadoEnEsteTaller - asignadoEnOtrosTalleres;
    
    // Actualizar el elemento visual
    const dispElement = document.querySelector(`.dist-disp[data-tallaid="${tallaId}"][data-tallerid="${tallerId}"]`);
    if (dispElement) {
        dispElement.textContent = `Disp: ${Math.max(0, disponible)}`;
    }
    
    // Actualizar el max del input
    const input = document.getElementById(`talla_${tallaId}_taller_${tallerId}`);
    if (input) {
        const maxDisponible = Math.max(0, cantidadTotal - asignadoEnOtrosTalleres);
        input.max = maxDisponible;
        
        // Si el valor actual excede el máximo, ajustarlo
        if (parseInt(input.value) > maxDisponible) {
            input.value = maxDisponible;
            window.asignacionesPorTaller[tallerId][tallaId] = maxDisponible;
        }
    }
}

// Función original confirmarPasarACostura (adaptada)
function confirmarPasarACostura() {
    const encargado = document.getElementById('costuraEncargado')?.value.trim();

    if (!encargado) {
        mostrarError('Error', 'Debes seleccionar o escribir un encargado de costura');
        return;
    }

    if (!window.datosModalCostura) {
        mostrarError('Error', 'No hay datos de la prenda pendiente');
        return;
    }

    const { pedidoId, prendaId, prendaBodegaId, tipoRecibo, btnId, recibo } = window.datosModalCostura;

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
    if (prendaBodegaId) {
        formData.append('prenda_bodega_id', prendaBodegaId);
    }
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
    window.modulosSeleccionadosDistribucion = null;
    
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
