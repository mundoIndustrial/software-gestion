// Variables globales para filtros
let filtrosActivos = {
    cotizaciones: {
        busqueda: '',
        cliente: [],
        asesora: [],
        estado: [],
        fecha: []
    },
    costos: {
        busqueda: '',
        cliente: [],
        asesora: [],
        fecha: []
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
        datosOriginales.cotizaciones = Array.from(filas).map(fila => {
            // Obtener el estado de la celda 4
            let estado = '';
            const celdaEstado = fila.cells[4];
            if (celdaEstado) {
                // Buscar si hay un select en la celda
                const select = celdaEstado.querySelector('select');
                if (select) {
                    // Obtener el valor del select
                    const valorSelect = select.value;
                    // Mapear valores a nombres legibles
                    const mapeoEstados = {
                        'ENVIADA_CONTADOR': 'Enviada',
                        'APROBADA_COTIZACIONES': 'Entregar',
                        'FINALIZADA': 'Anular'
                    };
                    estado = mapeoEstados[valorSelect] || valorSelect;
                } else {
                    // Fallback: obtener del texto
                    const textoCompleto = celdaEstado.textContent.trim();
                    const palabras = textoCompleto
                        .split(/[\s✓✕🟠×]+/)
                        .map(p => p.trim())
                        .filter(p => p && p.length > 0 && /^[a-záéíóúñA-ZÁÉÍÓÚÑ]/.test(p));
                    estado = palabras[0] || '';
                }
            }
            
            // Extraer solo la fecha sin la hora (formato: dd/mm/yyyy)
            let fechaSinHora = '';
            const fechaCompleta = fila.cells[1]?.textContent.trim() || '';
            if (fechaCompleta) {
                // Tomar solo la parte de la fecha (antes del espacio)
                fechaSinHora = fechaCompleta.split(' ')[0];
            }
            
            return {
                numero: fila.cells[0]?.textContent.trim() || '',
                fecha: fechaSinHora,
                cliente: fila.cells[2]?.textContent.trim() || '',
                asesora: fila.cells[3]?.textContent.trim() || '',
                estado: estado,
                elemento: fila
            };
        });
    }
    
    // Tabla de Análisis de Costos
    const tablaCostos = document.querySelector('#costos-section table');
    if (tablaCostos) {
        const filas = tablaCostos.querySelectorAll('tbody tr:not([data-empty])');
        datosOriginales.costos = Array.from(filas).map(fila => {
            // Extraer solo la fecha sin la hora (formato: dd/mm/yyyy)
            let fechaSinHora = '';
            const fechaCompleta = fila.cells[2]?.textContent.trim() || '';
            if (fechaCompleta) {
                // Tomar solo la parte de la fecha (antes del espacio)
                fechaSinHora = fechaCompleta.split(' ')[0];
            }
            
            return {
                numero: fila.cells[0]?.textContent.trim() || '',
                cliente: fila.cells[1]?.textContent.trim() || '',
                fecha: fechaSinHora,
                asesora: fila.cells[3]?.textContent.trim() || '',
                prendas: fila.cells[4]?.textContent.trim() || '',
                elemento: fila
            };
        });
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
 * Obtener opciones únicas de una columna
 */
function obtenerOpcionesUnicas(datos, columna) {
    const valores = datos.map(d => d[columna]);
    
    const valoresLimpios = valores
        .map(v => v ? v.trim() : '')
        .filter(v => v && v !== 'N/A');
    
    const unicos = [...new Set(valoresLimpios)].sort();
    console.log(`Opciones de ${columna}:`, unicos);
    
    return unicos;
}

/**
 * Cargar opciones de filtros
 */
function cargarOpcionesFiltros() {
    // Tabla de Cotizaciones
    const clientesUnicos = obtenerOpcionesUnicas(datosOriginales.cotizaciones, 'cliente');
    const asesoresUnicos = obtenerOpcionesUnicas(datosOriginales.cotizaciones, 'asesora');
    const estadosUnicos = obtenerOpcionesUnicas(datosOriginales.cotizaciones, 'estado');
    const fechasUnicos = obtenerOpcionesUnicas(datosOriginales.cotizaciones, 'fecha');
    
    console.log('Estados únicos encontrados:', estadosUnicos);
    console.log('Fechas únicas encontradas:', fechasUnicos);
    
    // Crear filtros en los headers de Cotizaciones
    crearFiltroEnHeader('#pedidos-section table', 'cliente', clientesUnicos, 2, 'cotizaciones');
    crearFiltroEnHeader('#pedidos-section table', 'asesora', asesoresUnicos, 3, 'cotizaciones');
    crearFiltroEnHeader('#pedidos-section table', 'estado', estadosUnicos, 4, 'cotizaciones');
    crearFiltroEnHeader('#pedidos-section table', 'fecha', fechasUnicos, 1, 'cotizaciones');
    
    // Tabla de Costos
    const clientesCostosUnicos = obtenerOpcionesUnicas(datosOriginales.costos, 'cliente');
    const asesoresCostosUnicos = obtenerOpcionesUnicas(datosOriginales.costos, 'asesora');
    const fechasCostosUnicos = obtenerOpcionesUnicas(datosOriginales.costos, 'fecha');
    
    // Crear filtros en los headers de Costos
    crearFiltroEnHeader('#costos-section table', 'cliente', clientesCostosUnicos, 1, 'costos');
    crearFiltroEnHeader('#costos-section table', 'asesora', asesoresCostosUnicos, 3, 'costos');
    crearFiltroEnHeader('#costos-section table', 'fecha', fechasCostosUnicos, 2, 'costos');
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
    
    // Crear contenedor para el botón y el indicador
    const contenedorFiltro = document.createElement('div');
    contenedorFiltro.style.cssText = `
        position: relative;
        display: inline-flex;
        align-items: center;
    `;
    
    // Crear botón de filtro
    const btnFiltro = document.createElement('button');
    btnFiltro.className = 'btn-filtro-modal';
    btnFiltro.setAttribute('data-columna', columna);
    btnFiltro.setAttribute('data-tabla', tabla);
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
    
    // Crear badge indicador de filtro activo
    const badge = document.createElement('div');
    badge.style.cssText = `
        position: absolute;
        top: -8px;
        right: -8px;
        background: #ef4444;
        color: white;
        border-radius: 50%;
        width: 20px;
        height: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
        font-weight: bold;
        display: none;
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        z-index: 1;
    `;
    badge.textContent = '1';
    
    // Función para actualizar el indicador visual
    const actualizarIndicador = () => {
        const filtrosCol = tabla === 'cotizaciones' 
            ? filtrosActivos.cotizaciones[columna] 
            : filtrosActivos.costos[columna];
        
        const tieneFiltroCols = filtrosCol && filtrosCol.length > 0;
        const tieneFiltroFecha = tabla === 'cotizaciones'
            ? (filtrosActivos.cotizaciones.fechaDesde || filtrosActivos.cotizaciones.fechaHasta)
            : (filtrosActivos.costos.fechaDesde || filtrosActivos.costos.fechaHasta);
        
        const tieneActivo = (columna === 'fecha' && tieneFiltroFecha) || 
                           (columna !== 'fecha' && tieneFiltroCols);
        
        if (tieneActivo) {
            // Mostrar badge
            badge.style.display = 'flex';
            // Cambiar color del icono a blanco y agregar fondo azul
            const svg = btnFiltro.querySelector('svg');
            if (svg) svg.style.color = 'white';
            // Agregar fondo azul redondeado al botón
            btnFiltro.style.background = '#2563eb';
            btnFiltro.style.borderRadius = '8px';
            btnFiltro.style.boxShadow = '0 2px 8px rgba(37, 99, 235, 0.4)';
            // Agregar tooltip
            btnFiltro.title = `Filtro activo en ${columna}`;
        } else {
            // Ocultar badge
            badge.style.display = 'none';
            // Restaurar color del icono
            const svg = btnFiltro.querySelector('svg');
            if (svg) svg.style.color = 'white';
            // Remover fondo azul
            btnFiltro.style.background = 'none';
            btnFiltro.style.boxShadow = 'none';
            btnFiltro.title = `Filtrar por ${columna}`;
        }
    };
    
    // Event listeners con helper
    agregarEventListenerHover(btnFiltro, () => {
        btnFiltro.style.transform = 'scale(1.15)';
        const tieneActivo = badge.style.display === 'flex';
        if (tieneActivo) {
            // Si hay filtro activo, hacer más brillante el azul
            btnFiltro.style.background = '#1d4ed8';
            btnFiltro.style.boxShadow = '0 4px 12px rgba(37, 99, 235, 0.6)';
        }
    }, () => {
        btnFiltro.style.transform = 'scale(1)';
        const tieneActivo = badge.style.display === 'flex';
        if (tieneActivo) {
            // Restaurar color azul normal
            btnFiltro.style.background = '#2563eb';
            btnFiltro.style.boxShadow = '0 2px 8px rgba(37, 99, 235, 0.4)';
        }
    });
    
    // Mostrar modal al hacer clic
    btnFiltro.addEventListener('click', (e) => {
        e.stopPropagation();
        abrirModalFiltro(selectorTabla, columna, opciones, tabla, actualizarIndicador);
    });
    
    // Agregar elementos al contenedor
    contenedorFiltro.appendChild(btnFiltro);
    contenedorFiltro.appendChild(badge);
    
    // Actualizar indicador inicial
    actualizarIndicador();
    
    // Guardar referencia a la función de actualización en el botón
    btnFiltro.actualizarIndicador = actualizarIndicador;
    
    header.appendChild(contenedorFiltro);
}

/**
 * Abrir modal de filtro moderno
 */
function abrirModalFiltro(selectorTabla, columna, opciones, tabla, actualizarIndicador) {
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
    
    agregarEventListenerHover(btnCerrar, 
        () => { btnCerrar.style.background = 'rgba(255,255,255,0.3)'; },
        () => { btnCerrar.style.background = 'rgba(255,255,255,0.2)'; }
    );
    
    header.appendChild(titulo);
    header.appendChild(btnCerrar);
    
    // Contenido del modal
    const contenido = document.createElement('div');
    contenido.style.cssText = `
        padding: 1.5rem;
        overflow-y: auto;
        flex: 1;
    `;
    
    // Barra de búsqueda moderna (para todas las columnas incluyendo fecha)
    // Barra de búsqueda moderna
    const barraBusqueda = document.createElement('div');
        barraBusqueda.style.cssText = `
            margin-bottom: 1rem;
            position: relative;
        `;
        
        const inputBusqueda = document.createElement('input');
        inputBusqueda.type = 'text';
        inputBusqueda.placeholder = 'Buscar opciones...';
        inputBusqueda.style.cssText = `
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.5rem;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 0.95rem;
            transition: all 0.3s;
            box-sizing: border-box;
        `;
        
        inputBusqueda.addEventListener('focus', () => {
            inputBusqueda.style.borderColor = '#1e5ba8';
            inputBusqueda.style.boxShadow = '0 0 0 3px rgba(30, 91, 168, 0.1)';
        });
        
        inputBusqueda.addEventListener('blur', () => {
            inputBusqueda.style.borderColor = '#e5e7eb';
            inputBusqueda.style.boxShadow = 'none';
        });
        
        // Icono de búsqueda SVG
        const iconoBusqueda = document.createElement('div');
        iconoBusqueda.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: #000;"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 10m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0" /><path d="M21 21l-6 -6" /></svg>`;
        iconoBusqueda.style.cssText = `
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            pointer-events: none;
            display: flex;
            align-items: center;
            justify-content: center;
        `;
        
        barraBusqueda.appendChild(iconoBusqueda);
        barraBusqueda.appendChild(inputBusqueda);
        contenido.appendChild(barraBusqueda);
        
        // Botones de selección rápida mejorados
        const botonesSeleccion = document.createElement('div');
        botonesSeleccion.style.cssText = `
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        `;
        
        // Botón Seleccionar Todas
        const btnSeleccionarTodas = document.createElement('button');
        btnSeleccionarTodas.innerHTML = '✓ Seleccionar Todas';
        btnSeleccionarTodas.style.cssText = `
            flex: 1;
            min-width: 140px;
            padding: 0.75rem 1rem;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s;
            box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
        `;
        
        agregarEventListenerHover(btnSeleccionarTodas,
            () => { 
                btnSeleccionarTodas.style.transform = 'translateY(-2px)';
                btnSeleccionarTodas.style.boxShadow = '0 4px 12px rgba(16, 185, 129, 0.5)';
            },
            () => { 
                btnSeleccionarTodas.style.transform = 'translateY(0)';
                btnSeleccionarTodas.style.boxShadow = '0 2px 8px rgba(16, 185, 129, 0.3)';
            }
        );
        
        btnSeleccionarTodas.addEventListener('click', () => {
            contenido.querySelectorAll('label input[type="checkbox"]:not([style*="display: none"])').forEach(check => {
                check.checked = true;
            });
        });
        
        // Botón Deseleccionar Todas
        const btnDeseleccionarTodas = document.createElement('button');
        btnDeseleccionarTodas.innerHTML = '✕ Deseleccionar Todas';
        btnDeseleccionarTodas.style.cssText = `
            flex: 1;
            min-width: 140px;
            padding: 0.75rem 1rem;
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s;
            box-shadow: 0 2px 8px rgba(239, 68, 68, 0.3);
        `;
        
        agregarEventListenerHover(btnDeseleccionarTodas,
            () => { 
                btnDeseleccionarTodas.style.transform = 'translateY(-2px)';
                btnDeseleccionarTodas.style.boxShadow = '0 4px 12px rgba(239, 68, 68, 0.5)';
            },
            () => { 
                btnDeseleccionarTodas.style.transform = 'translateY(0)';
                btnDeseleccionarTodas.style.boxShadow = '0 2px 8px rgba(239, 68, 68, 0.3)';
            }
        );
        
        btnDeseleccionarTodas.addEventListener('click', () => {
            contenido.querySelectorAll('label input[type="checkbox"]:not([style*="display: none"])').forEach(check => {
                check.checked = false;
            });
        });
        
        botonesSeleccion.appendChild(btnSeleccionarTodas);
        botonesSeleccion.appendChild(btnDeseleccionarTodas);
        contenido.appendChild(botonesSeleccion);
        
        // Evento de búsqueda en tiempo real
        inputBusqueda.addEventListener('input', (e) => {
            const termino = e.target.value.toLowerCase();
            const labels = contenido.querySelectorAll('label');
            
            labels.forEach(label => {
                const texto = label.textContent.toLowerCase();
                if (termino === '' || texto.includes(termino)) {
                    label.style.display = '';
                } else {
                    label.style.display = 'none';
                }
            });
        });
        
        // Línea separadora
        const separador = document.createElement('hr');
        separador.style.cssText = 'margin: 1rem 0; border: none; border-top: 1px solid #e5e7eb;';
        contenido.appendChild(separador);
        
        // Obtener filtros actuales de esta columna
        const filtrosActuales = tabla === 'cotizaciones' 
            ? filtrosActivos.cotizaciones[columna] 
            : filtrosActivos.costos[columna];
        
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
            // Si hay filtros activos, solo marcar los que están siendo filtrados
            // Si no hay filtros, marcar todos por defecto
            if (filtrosActuales && filtrosActuales.length > 0) {
                check.checked = filtrosActuales.includes(opcion);
            } else {
                check.checked = true;
            }
            check.value = opcion;
            check.style.cssText = 'width: 18px; height: 18px; cursor: pointer;';
            
            check.addEventListener('change', () => {
                // No aplicar automáticamente, solo cuando se hace clic en "Aplicar"
            });
            
            label.appendChild(check);
            label.appendChild(document.createTextNode(opcion));
            contenido.appendChild(label);
        });
    
    // Footer del modal
    const footer = document.createElement('div');
    footer.style.cssText = `
        padding: 1rem 1.5rem;
        border-top: 1px solid #eee;
        display: flex;
        gap: 0.75rem;
        justify-content: flex-end;
    `;
    
    const btnAplicar = document.createElement('button');
    btnAplicar.textContent = '✓ Aplicar';
    btnAplicar.style.cssText = `
        padding: 0.75rem 1.5rem;
        background: #1e5ba8;
        color: white;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.2s;
    `;
    
    agregarEventListenerHover(btnAplicar,
        () => { btnAplicar.style.background = '#1e40af'; },
        () => { btnAplicar.style.background = '#1e5ba8'; }
    );
    
    btnAplicar.addEventListener('click', () => {
        // Obtener checkboxes seleccionados (funciona para todas las columnas incluyendo fecha)
        const labels = contenido.querySelectorAll('label input[type="checkbox"]');
        const seleccionados = Array.from(labels)
            .filter(check => check.checked && check.value)
            .map(check => check.value);
        
        // Actualizar filtros activos
        if (tabla === 'cotizaciones') {
            filtrosActivos.cotizaciones[columna] = seleccionados;
            aplicarFiltrosCotizaciones();
        } else if (tabla === 'costos') {
            filtrosActivos.costos[columna] = seleccionados;
            aplicarFiltrosCostos();
        }
        
        // Actualizar indicador visual del filtro
        if (actualizarIndicador) {
            actualizarIndicador();
        }
        overlay.remove();
    });
    
    const btnCerrarFooter = document.createElement('button');
    btnCerrarFooter.textContent = '✕ Cerrar';
    btnCerrarFooter.style.cssText = `
        padding: 0.75rem 1.5rem;
        background: #6b7280;
        color: white;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.2s;
    `;
    
    agregarEventListenerHover(btnCerrarFooter,
        () => { btnCerrarFooter.style.background = '#4b5563'; },
        () => { btnCerrarFooter.style.background = '#6b7280'; }
    );
    
    btnCerrarFooter.addEventListener('click', () => {
        overlay.remove();
    });
    
    footer.appendChild(btnAplicar);
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
 * Helper: Agregar event listeners de hover a botones
 */
function agregarEventListenerHover(elemento, onHover, onOut) {
    elemento.addEventListener('mouseover', onHover);
    elemento.addEventListener('mouseout', onOut);
}

/**
 * Aplicar filtros a la tabla de Cotizaciones
 */
function aplicarFiltrosCotizaciones() {
    const tabla = document.querySelector('#pedidos-section table');
    if (!tabla) return;
    
    const filas = tabla.querySelectorAll('tbody tr:not([data-empty])');
    let filasVisibles = 0;
    
    filas.forEach((fila, index) => {
        const numero = fila.cells[0]?.textContent.toLowerCase() || '';
        // Extraer solo la fecha sin la hora
        const fechaCompleta = fila.cells[1]?.textContent.trim() || '';
        const fecha = fechaCompleta.split(' ')[0];
        const cliente = fila.cells[2]?.textContent.trim() || '';
        const asesora = fila.cells[3]?.textContent.trim() || '';
        
        // Obtener el estado del select (igual que en guardarDatosOriginales)
        let estado = '';
        const celdaEstado = fila.cells[4];
        if (celdaEstado) {
            const select = celdaEstado.querySelector('select');
            if (select) {
                const valorSelect = select.value;
                const mapeoEstados = {
                    'ENVIADA_CONTADOR': 'Enviada',
                    'APROBADA_COTIZACIONES': 'Entregar',
                    'FINALIZADA': 'Anular'
                };
                estado = mapeoEstados[valorSelect] || valorSelect;
            }
        }
        
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
        const coincideFecha = filtrosActivos.cotizaciones.fecha.length === 0 || 
                             filtrosActivos.cotizaciones.fecha.includes(fecha);
        
        const mostrar = coincideBusqueda && coincideCliente && coincideAsesora && coincideEstado && coincideFecha;
        
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
        // Extraer solo la fecha sin la hora
        const fechaCompleta = fila.cells[2]?.textContent.trim() || '';
        const fecha = fechaCompleta.split(' ')[0];
        const asesora = fila.cells[3]?.textContent.trim() || '';
        
        // Aplicar búsqueda
        const coincideBusqueda = numero.includes(filtrosActivos.costos.busqueda) || 
                                 cliente.toLowerCase().includes(filtrosActivos.costos.busqueda);
        
        // Aplicar filtros de columnas
        const coincideCliente = filtrosActivos.costos.cliente.length === 0 || 
                               filtrosActivos.costos.cliente.includes(cliente);
        const coincideAsesora = filtrosActivos.costos.asesora.length === 0 || 
                               filtrosActivos.costos.asesora.includes(asesora);
        const coincideFecha = filtrosActivos.costos.fecha.length === 0 || 
                             filtrosActivos.costos.fecha.includes(fecha);
        
        const mostrar = coincideBusqueda && coincideCliente && coincideAsesora && coincideFecha;
        
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
        estado: [],
        fecha: []
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
    
    // Actualizar indicadores visuales de los botones de filtro
    const botonesFiltroCotizaciones = tablaCotizaciones.querySelectorAll('.btn-filtro-modal');
    botonesFiltroCotizaciones.forEach(btn => {
        if (btn.actualizarIndicador) {
            btn.actualizarIndicador();
        }
    });
    
    aplicarFiltrosCotizaciones();
}

/**
 * Limpiar todos los filtros - Costos
 */
function limpiarFiltrosCostos() {
    filtrosActivos.costos = {
        busqueda: '',
        cliente: [],
        asesora: [],
        fecha: []
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
    
    // Actualizar indicadores visuales de los botones de filtro
    const botonesFiltroCostos = tablaCostos.querySelectorAll('.btn-filtro-modal');
    botonesFiltroCostos.forEach(btn => {
        if (btn.actualizarIndicador) {
            btn.actualizarIndicador();
        }
    });
    
    aplicarFiltrosCostos();
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', inicializarBusquedaFiltros);

