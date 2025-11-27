// Variables globales para filtros
let filtrosActivos = {
    cotizaciones: {
        busqueda: '',
        cliente: [],
        asesora: [],
        estado: [],
        fechaDesde: '',
        fechaHasta: ''
    },
    costos: {
        busqueda: '',
        cliente: [],
        asesora: [],
        fechaDesde: '',
        fechaHasta: ''
    }
};

let datosOriginales = {
    cotizaciones: [],
    costos: []
};

/**
 * Inicializar búsqueda y filtros
 */
function inicializarBusquedaFiltros() {
    // Guardar datos originales de ambas tablas
    guardarDatosOriginales();
    
    // Configurar event listeners
    configurarEventListeners();
    
    // Cargar opciones de filtros
    cargarOpcionesFiltros();
}

/**
 * Guardar datos originales de las tablas
 */
function guardarDatosOriginales() {
    // Tabla de Cotizaciones
    const tablaCotizaciones = document.querySelector('#pedidos-section table');
    if (tablaCotizaciones) {
        const filas = tablaCotizaciones.querySelectorAll('tbody tr:not([data-empty])');
        datosOriginales.cotizaciones = Array.from(filas).map(fila => ({
            numero: fila.cells[0]?.textContent.trim() || '',
            fecha: fila.cells[1]?.textContent.trim() || '',
            cliente: fila.cells[2]?.textContent.trim() || '',
            asesora: fila.cells[3]?.textContent.trim() || '',
            estado: fila.cells[4]?.textContent.trim() || '',
            elemento: fila
        }));
    }
    
    // Tabla de Análisis de Costos
    const tablaCostos = document.querySelector('#costos-section table');
    if (tablaCostos) {
        const filas = tablaCostos.querySelectorAll('tbody tr:not([data-empty])');
        datosOriginales.costos = Array.from(filas).map(fila => ({
            numero: fila.cells[0]?.textContent.trim() || '',
            cliente: fila.cells[1]?.textContent.trim() || '',
            fecha: fila.cells[2]?.textContent.trim() || '',
            asesora: fila.cells[3]?.textContent.trim() || '',
            prendas: fila.cells[4]?.textContent.trim() || '',
            elemento: fila
        }));
    }
}

/**
 * Configurar event listeners
 */
function configurarEventListeners() {
    // Búsqueda en tiempo real - Tabla Cotizaciones
    const inputBusqueda = document.getElementById('inputBusqueda');
    if (inputBusqueda) {
        inputBusqueda.addEventListener('input', (e) => {
            filtrosActivos.cotizaciones.busqueda = e.target.value.toLowerCase();
            aplicarFiltrosCotizaciones();
        });
    }
    
    // Búsqueda en tiempo real - Tabla Costos
    const inputBusquedaCostos = document.getElementById('inputBusquedaCostos');
    if (inputBusquedaCostos) {
        inputBusquedaCostos.addEventListener('input', (e) => {
            filtrosActivos.costos.busqueda = e.target.value.toLowerCase();
            aplicarFiltrosCostos();
        });
    }
}

/**
 * Cargar opciones de filtros
 */
function cargarOpcionesFiltros() {
    // Tabla de Cotizaciones
    const clientesUnicos = [...new Set(datosOriginales.cotizaciones.map(d => d.cliente))].filter(c => c && c !== 'N/A');
    const asesoresUnicos = [...new Set(datosOriginales.cotizaciones.map(d => d.asesora))].filter(a => a && a !== 'N/A');
    const estadosUnicos = [...new Set(datosOriginales.cotizaciones.map(d => d.estado))].filter(e => e && e !== 'N/A');
    
    // Crear filtros en los headers de Cotizaciones
    crearFiltroEnHeader('#pedidos-section table', 'cliente', clientesUnicos, 2, 'cotizaciones');
    crearFiltroEnHeader('#pedidos-section table', 'asesora', asesoresUnicos, 3, 'cotizaciones');
    crearFiltroEnHeader('#pedidos-section table', 'estado', estadosUnicos, 4, 'cotizaciones');
    crearFiltroEnHeader('#pedidos-section table', 'fecha', [], 1, 'cotizaciones');
    
    // Tabla de Costos
    const clientesCostosUnicos = [...new Set(datosOriginales.costos.map(d => d.cliente))].filter(c => c && c !== 'N/A');
    const asesoresCostosUnicos = [...new Set(datosOriginales.costos.map(d => d.asesora))].filter(a => a && a !== 'N/A');
    
    // Crear filtros en los headers de Costos
    crearFiltroEnHeader('#costos-section table', 'cliente', clientesCostosUnicos, 1, 'costos');
    crearFiltroEnHeader('#costos-section table', 'asesora', asesoresCostosUnicos, 3, 'costos');
    crearFiltroEnHeader('#costos-section table', 'fecha', [], 2, 'costos');
}

/**
 * Crear filtro modal en el header
 */
function crearFiltroEnHeader(selectorTabla, columna, opciones, headerIndex, tabla) {
    // Encontrar el header correspondiente
    const tablaElement = document.querySelector(selectorTabla);
    if (!tablaElement) return;
    
    const headers = tablaElement.querySelectorAll('thead th');
    const header = headers[headerIndex];
    if (!header) return;
    
    // Crear botón de filtro
    const btnFiltro = document.createElement('button');
    btnFiltro.className = 'btn-filtro-modal';
    btnFiltro.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="currentColor" style="color: white;"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M20 3h-16a1 1 0 0 0 -1 1v2.227l.008 .223a3 3 0 0 0 .772 1.795l4.22 4.641v8.114a1 1 0 0 0 1.316 .949l6 -2l.108 -.043a1 1 0 0 0 .576 -.906v-6.586l4.121 -4.12a3 3 0 0 0 .879 -2.123v-2.171a1 1 0 0 0 -1 -1z" /></svg>`;
    btnFiltro.style.cssText = `
        background: none;
        border: none;
        cursor: pointer;
        padding: 0.25rem 0.5rem;
        margin-left: 0.5rem;
        transition: all 0.3s;
        display: inline-flex;
        align-items: flex-end;
        justify-content: center;
        width: 32px;
        height: 32px;
        vertical-align: middle;
    `;
    
    btnFiltro.addEventListener('mouseover', () => {
        btnFiltro.style.transform = 'scale(1.15)';
        const svg = btnFiltro.querySelector('svg');
        if (svg) svg.style.color = '#e0e0e0';
    });
    
    btnFiltro.addEventListener('mouseout', () => {
        btnFiltro.style.transform = 'scale(1)';
        const svg = btnFiltro.querySelector('svg');
        if (svg) svg.style.color = 'white';
    });
    
    // Mostrar modal al hacer clic
    btnFiltro.addEventListener('click', (e) => {
        e.stopPropagation();
        abrirModalFiltro(selectorTabla, columna, opciones, tabla);
    });
    
    header.appendChild(btnFiltro);
}

/**
 * Abrir modal de filtro moderno
 */
function abrirModalFiltro(selectorTabla, columna, opciones, tabla) {
    // Crear overlay
    const overlay = document.createElement('div');
    overlay.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.5);
        z-index: 9999;
        display: flex;
        justify-content: center;
        align-items: center;
    `;
    
    // Crear modal
    const modal = document.createElement('div');
    modal.style.cssText = `
        background: white;
        border-radius: 12px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.3);
        z-index: 10000;
        width: 90%;
        max-width: 400px;
        max-height: 80vh;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        animation: slideUp 0.3s ease-out;
    `;
    
    // Header del modal
    const header = document.createElement('div');
    header.style.cssText = `
        background: linear-gradient(135deg, #1e5ba8 0%, #2b7ec9 100%);
        color: white;
        padding: 1.5rem;
        border-bottom: 1px solid #ddd;
        display: flex;
        justify-content: space-between;
        align-items: center;
    `;
    
    const titulo = document.createElement('h3');
    titulo.textContent = `Filtrar por ${columna.charAt(0).toUpperCase() + columna.slice(1)}`;
    titulo.style.cssText = 'margin: 0; font-size: 1.1rem; font-weight: 600;';
    
    const btnCerrar = document.createElement('button');
    btnCerrar.innerHTML = '';
    btnCerrar.style.cssText = `
        background: rgba(255,255,255,0.2);
        border: none;
        color: white;
        font-size: 1.5rem;
        cursor: pointer;
        padding: 0;
        width: 32px;
        height: 32px;
        border-radius: 4px;
        transition: all 0.2s;
    `;
    
    btnCerrar.addEventListener('mouseover', () => {
        btnCerrar.style.background = 'rgba(255,255,255,0.3)';
    });
    
    btnCerrar.addEventListener('mouseout', () => {
        btnCerrar.style.background = 'rgba(255,255,255,0.2)';
    });
    
    header.appendChild(titulo);
    header.appendChild(btnCerrar);
    
    // Contenido del modal
    const contenido = document.createElement('div');
    contenido.style.cssText = `
        padding: 1.5rem;
        overflow-y: auto;
        flex: 1;
    `;
    
    // Agregar filtro de fecha si es necesario
    if (columna === 'fecha') {
        const grupoFecha = document.createElement('div');
        grupoFecha.style.cssText = 'margin-bottom: 1rem;';
        
        // Desde
        const labelDesde = document.createElement('label');
        labelDesde.textContent = 'Desde:';
        labelDesde.style.cssText = 'display: block; font-weight: 600; margin-bottom: 0.5rem; color: #333;';
        
        const inputDesde = document.createElement('input');
        inputDesde.type = 'date';
        inputDesde.style.cssText = `
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 0.95rem;
            margin-bottom: 1rem;
        `;
        
        // Hasta
        const labelHasta = document.createElement('label');
        labelHasta.textContent = 'Hasta:';
        labelHasta.style.cssText = 'display: block; font-weight: 600; margin-bottom: 0.5rem; color: #333;';
        
        const inputHasta = document.createElement('input');
        inputHasta.type = 'date';
        inputHasta.style.cssText = `
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 0.95rem;
        `;
        
        grupoFecha.appendChild(labelDesde);
        grupoFecha.appendChild(inputDesde);
        grupoFecha.appendChild(labelHasta);
        grupoFecha.appendChild(inputHasta);
        contenido.appendChild(grupoFecha);
        
        // Guardar valores
        inputDesde.addEventListener('change', () => {
            if (tabla === 'cotizaciones') {
                filtrosActivos.cotizaciones.fechaDesde = inputDesde.value;
            } else {
                filtrosActivos.costos.fechaDesde = inputDesde.value;
            }
            aplicarFiltrosSegunTabla(tabla);
        });
        
        inputHasta.addEventListener('change', () => {
            if (tabla === 'cotizaciones') {
                filtrosActivos.cotizaciones.fechaHasta = inputHasta.value;
            } else {
                filtrosActivos.costos.fechaHasta = inputHasta.value;
            }
            aplicarFiltrosSegunTabla(tabla);
        });
    } else {
        // Opción "Todos"
        const labelTodos = document.createElement('label');
        labelTodos.style.cssText = `
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem;
            cursor: pointer;
            border-radius: 6px;
            margin-bottom: 0.5rem;
            font-weight: 600;
            background: #f0f4f8;
            transition: background 0.2s;
        `;
        
        const checkTodos = document.createElement('input');
        checkTodos.type = 'checkbox';
        checkTodos.checked = true;
        checkTodos.style.cssText = 'width: 18px; height: 18px; cursor: pointer;';
        
        checkTodos.addEventListener('change', () => {
            const checks = contenido.querySelectorAll('input[type="checkbox"]:not(:first-child)');
            checks.forEach(check => {
                check.checked = checkTodos.checked;
            });
            actualizarFiltroModal(selectorTabla, columna, contenido, tabla);
        });
        
        labelTodos.appendChild(checkTodos);
        labelTodos.appendChild(document.createTextNode('Todos'));
        contenido.appendChild(labelTodos);
        
        // Opciones individuales
        opciones.forEach(opcion => {
            const label = document.createElement('label');
            label.style.cssText = `
                display: flex;
                align-items: center;
                gap: 0.75rem;
                padding: 0.75rem;
                cursor: pointer;
                border-radius: 6px;
                transition: background 0.2s;
                margin-bottom: 0.25rem;
            `;
            
            label.addEventListener('mouseover', () => {
                label.style.background = '#f0f4f8';
            });
            
            label.addEventListener('mouseout', () => {
                label.style.background = 'transparent';
            });
            
            const check = document.createElement('input');
            check.type = 'checkbox';
            check.checked = true;
            check.value = opcion;
            check.style.cssText = 'width: 18px; height: 18px; cursor: pointer;';
            
            check.addEventListener('change', () => {
                actualizarFiltroModal(selectorTabla, columna, contenido, tabla);
            });
            
            label.appendChild(check);
            label.appendChild(document.createTextNode(opcion));
            contenido.appendChild(label);
        });
    }
    
    // Footer del modal
    const footer = document.createElement('div');
    footer.style.cssText = `
        padding: 1rem 1.5rem;
        border-top: 1px solid #eee;
        display: flex;
        gap: 0.75rem;
        justify-content: flex-end;
    `;
    
    const btnLimpiar = document.createElement('button');
    btnLimpiar.textContent = 'Limpiar';
    btnLimpiar.style.cssText = `
        padding: 0.75rem 1.5rem;
        background: #e5e7eb;
        color: #333;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.2s;
    `;
    
    btnLimpiar.addEventListener('mouseover', () => {
        btnLimpiar.style.background = '#d1d5db';
    });
    
    btnLimpiar.addEventListener('mouseout', () => {
        btnLimpiar.style.background = '#e5e7eb';
    });
    
    btnLimpiar.addEventListener('click', () => {
        if (columna === 'fecha') {
            inputDesde.value = '';
            inputHasta.value = '';
            if (tabla === 'cotizaciones') {
                filtrosActivos.cotizaciones.fechaDesde = '';
                filtrosActivos.cotizaciones.fechaHasta = '';
            } else {
                filtrosActivos.costos.fechaDesde = '';
                filtrosActivos.costos.fechaHasta = '';
            }
        } else {
            contenido.querySelectorAll('input[type="checkbox"]').forEach(check => {
                check.checked = true;
            });
            actualizarFiltroModal(selectorTabla, columna, contenido, tabla);
        }
        aplicarFiltrosSegunTabla(tabla);
    });
    
    const btnCerrarFooter = document.createElement('button');
    btnCerrarFooter.textContent = 'Cerrar';
    btnCerrarFooter.style.cssText = `
        padding: 0.75rem 1.5rem;
        background: #1e5ba8;
        color: white;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.2s;
    `;
    
    btnCerrarFooter.addEventListener('mouseover', () => {
        btnCerrarFooter.style.background = '#1e40af';
    });
    
    btnCerrarFooter.addEventListener('mouseout', () => {
        btnCerrarFooter.style.background = '#1e5ba8';
    });
    
    btnCerrarFooter.addEventListener('click', () => {
        overlay.remove();
    });
    
    footer.appendChild(btnLimpiar);
    footer.appendChild(btnCerrarFooter);
    
    // Armar modal
    modal.appendChild(header);
    modal.appendChild(contenido);
    modal.appendChild(footer);
    
    overlay.appendChild(modal);
    document.body.appendChild(overlay);
    
    // Cerrar al hacer clic en overlay
    overlay.addEventListener('click', (e) => {
        if (e.target === overlay) {
            overlay.remove();
        }
    });
    
    // Cerrar al presionar ESC
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            overlay.remove();
        }
    });
    
    // Agregar animación
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    `;
    document.head.appendChild(style);
}

/**
 * Actualizar filtro desde modal
 */
function actualizarFiltroModal(selectorTabla, columna, contenido, tabla) {
    const checks = contenido.querySelectorAll('input[type="checkbox"]:not(:first-child)');
    const seleccionados = Array.from(checks)
        .filter(check => check.checked)
        .map(check => check.value);
    
    if (tabla === 'cotizaciones') {
        filtrosActivos.cotizaciones[columna] = seleccionados;
        aplicarFiltrosCotizaciones();
    } else if (tabla === 'costos') {
        filtrosActivos.costos[columna] = seleccionados;
        aplicarFiltrosCostos();
    }
}

/**
 * Aplicar filtros según tabla
 */
function aplicarFiltrosSegunTabla(tabla) {
    if (tabla === 'cotizaciones') {
        aplicarFiltrosCotizaciones();
    } else if (tabla === 'costos') {
        aplicarFiltrosCostos();
    }
}

/**
 * Aplicar filtros a la tabla de Cotizaciones
 */
function aplicarFiltrosCotizaciones() {
    const tabla = document.querySelector('#pedidos-section table');
    if (!tabla) return;
    
    const filas = tabla.querySelectorAll('tbody tr:not([data-empty])');
    let filasVisibles = 0;
    
    filas.forEach(fila => {
        const numero = fila.cells[0]?.textContent.toLowerCase() || '';
        const cliente = fila.cells[2]?.textContent.trim() || '';
        const asesora = fila.cells[3]?.textContent.trim() || '';
        const estado = fila.cells[4]?.textContent.trim() || '';
        
        // Aplicar búsqueda
        const coincideBusqueda = numero.includes(filtrosActivos.cotizaciones.busqueda) || 
                                 cliente.toLowerCase().includes(filtrosActivos.cotizaciones.busqueda);
        
        // Aplicar filtros de columnas
        const coincideCliente = filtrosActivos.cotizaciones.cliente.length === 0 || 
                               filtrosActivos.cotizaciones.cliente.includes(cliente);
        const coincideAsesora = filtrosActivos.cotizaciones.asesora.length === 0 || 
                               filtrosActivos.cotizaciones.asesora.includes(asesora);
        const coincideEstado = filtrosActivos.cotizaciones.estado.length === 0 || 
                              filtrosActivos.cotizaciones.estado.includes(estado);
        
        const mostrar = coincideBusqueda && coincideCliente && coincideAsesora && coincideEstado;
        
        fila.style.display = mostrar ? '' : 'none';
        if (mostrar) filasVisibles++;
    });
    
    // Mostrar mensaje si no hay resultados
    if (filasVisibles === 0) {
        let filaVacia = tabla.querySelector('tbody tr[data-empty]');
        if (!filaVacia) {
            filaVacia = document.createElement('tr');
            filaVacia.setAttribute('data-empty', 'true');
            filaVacia.innerHTML = `
                <td colspan="6" style="text-align: center; padding: 2rem; color: #999;">
                    <span class="material-symbols-rounded" style="font-size: 2rem; display: block; margin-bottom: 0.5rem;">search_off</span>
                    No hay resultados que coincidan con los filtros
                </td>
            `;
            tabla.querySelector('tbody').appendChild(filaVacia);
        }
    } else {
        const filaVacia = tabla.querySelector('tbody tr[data-empty]');
        if (filaVacia) filaVacia.remove();
    }
}

/**
 * Aplicar filtros a la tabla de Costos
 */
function aplicarFiltrosCostos() {
    const tabla = document.querySelector('#costos-section table');
    if (!tabla) return;
    
    const filas = tabla.querySelectorAll('tbody tr:not([data-empty])');
    let filasVisibles = 0;
    
    filas.forEach(fila => {
        const numero = fila.cells[0]?.textContent.toLowerCase() || '';
        const cliente = fila.cells[1]?.textContent.trim() || '';
        const asesora = fila.cells[3]?.textContent.trim() || '';
        
        // Aplicar búsqueda
        const coincideBusqueda = numero.includes(filtrosActivos.costos.busqueda) || 
                                 cliente.toLowerCase().includes(filtrosActivos.costos.busqueda);
        
        // Aplicar filtros de columnas
        const coincideCliente = filtrosActivos.costos.cliente.length === 0 || 
                               filtrosActivos.costos.cliente.includes(cliente);
        const coincideAsesora = filtrosActivos.costos.asesora.length === 0 || 
                               filtrosActivos.costos.asesora.includes(asesora);
        
        const mostrar = coincideBusqueda && coincideCliente && coincideAsesora;
        
        fila.style.display = mostrar ? '' : 'none';
        if (mostrar) filasVisibles++;
    });
    
    // Mostrar mensaje si no hay resultados
    if (filasVisibles === 0) {
        let filaVacia = tabla.querySelector('tbody tr[data-empty]');
        if (!filaVacia) {
            filaVacia = document.createElement('tr');
            filaVacia.setAttribute('data-empty', 'true');
            filaVacia.innerHTML = `
                <td colspan="6" style="text-align: center; padding: 2rem; color: #999;">
                    <span class="material-symbols-rounded" style="font-size: 2rem; display: block; margin-bottom: 0.5rem;">search_off</span>
                    No hay resultados que coincidan con los filtros
                </td>
            `;
            tabla.querySelector('tbody').appendChild(filaVacia);
        }
    } else {
        const filaVacia = tabla.querySelector('tbody tr[data-empty]');
        if (filaVacia) filaVacia.remove();
    }
}

/**
 * Limpiar todos los filtros - Cotizaciones
 */
function limpiarFiltros() {
    filtrosActivos.cotizaciones = {
        busqueda: '',
        cliente: [],
        asesora: [],
        estado: []
    };
    
    const inputBusqueda = document.getElementById('inputBusqueda');
    if (inputBusqueda) inputBusqueda.value = '';
    
    // Resetear checkboxes
    const tablaCotizaciones = document.querySelector('#pedidos-section table');
    if (tablaCotizaciones) {
        tablaCotizaciones.querySelectorAll('.filtro-popup input[type="checkbox"]').forEach(check => {
            check.checked = true;
        });
    }
    
    aplicarFiltrosCotizaciones();
}

/**
 * Limpiar todos los filtros - Costos
 */
function limpiarFiltrosCostos() {
    filtrosActivos.costos = {
        busqueda: '',
        cliente: [],
        asesora: []
    };
    
    const inputBusquedaCostos = document.getElementById('inputBusquedaCostos');
    if (inputBusquedaCostos) inputBusquedaCostos.value = '';
    
    // Resetear checkboxes
    const tablaCostos = document.querySelector('#costos-section table');
    if (tablaCostos) {
        tablaCostos.querySelectorAll('.filtro-popup input[type="checkbox"]').forEach(check => {
            check.checked = true;
        });
    }
    
    aplicarFiltrosCostos();
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', inicializarBusquedaFiltros);
