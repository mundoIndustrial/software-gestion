/**
 * GESTOR DE COTIZACIONES - Crear Pedido Editable (FASE 2)
 * 
 * Centraliza toda la lógica de búsqueda, selección y carga de cotizaciones
 * Responsabilidades:
 * - Mostrar opciones de cotizaciones
 * - Filtrar cotizaciones por criterio
 * - Seleccionar cotización y cargar datos
 * - Obtener información completa de cotización
 */

class GestorCotizacion {
    /**
     * Constructor
     * @param {Array} cotizacionesData - Array de cotizaciones disponibles
     * @param {string} selectorBusqueda - ID del input de búsqueda
     * @param {string} selectorDropdown - ID del dropdown
     * @param {string} selectorSeleccionado - ID del div de selección
     * @param {Function} callbackSeleccionar - Callback cuando selecciona cotización
     */
    constructor(
        cotizacionesData = [],
        selectorBusqueda = '#cotizacion_search_editable',
        selectorDropdown = '#cotizacion_dropdown_editable',
        selectorSeleccionado = '#cotizacion_selected_editable',
        callbackSeleccionar = null
    ) {
        this.cotizaciones = cotizacionesData;
        this.inputBusqueda = getElement(selectorBusqueda);
        this.dropdown = getElement(selectorDropdown);
        this.seleccionadoDiv = getElement(selectorSeleccionado);
        this.callbackSeleccionar = callbackSeleccionar;
        this.cotizacionSeleccionada = null;
        
        this.inicializar();
    }

    /**
     * Inicializar event listeners
     */
    inicializar() {
        if (!this.inputBusqueda) return;

        // Mostrar opciones al enfocar
        this.inputBusqueda.addEventListener('focus', () => {
            this.mostrarOpciones(this.inputBusqueda.value);
        });

        // Filtrar mientras escribe
        this.inputBusqueda.addEventListener('input', (e) => {
            this.mostrarOpciones(e.target.value);
        });

        // Cerrar dropdown al hacer click afuera
        document.addEventListener('click', (e) => {
            if (e.target !== this.inputBusqueda && e.target !== this.dropdown) {
                this.cerrarDropdown();
            }
        });
    }

    /**
     * Mostrar opciones filtradas
     * @param {string} filtro - Texto a filtrar
     */
    mostrarOpciones(filtro = '') {
        const opciones = this.filtrar(filtro);

        if (!this.dropdown) return;

        if (this.cotizaciones.length === 0) {
            this.dropdown.innerHTML = '<div style="padding: 1rem; color: #ef4444; text-align: center;"><strong>No hay cotizaciones aprobadas</strong></div>';
        } else if (opciones.length === 0) {
            this.dropdown.innerHTML = '<div style="padding: 1rem; color: #9ca3af; text-align: center;">No se encontraron cotizaciones</div>';
        } else {
            this.dropdown.innerHTML = opciones.map(cot => this.generarHTMLOpcion(cot)).join('');
        }

        this.dropdown.style.display = 'block';
    }

    /**
     * Generar HTML de una opción
     * @param {Object} cot - Cotización
     * @returns {string} HTML de la opción
     */
    generarHTMLOpcion(cot) {
        return `
            <div onclick="window.gestorCotizacion.seleccionar(${cot.id}, '${cot.numero}', '${cot.cliente}', '${cot.asesora}', '${cot.formaPago || ''}')" 
                 style="padding: 0.75rem 1rem; border-bottom: 1px solid #e5e7eb; cursor: pointer; transition: background 0.2s;"
                 onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background='white'">
                <div style="font-weight: 600; color: #1f2937;">${cot.numero}</div>
                <div style="font-size: 0.875rem; color: #6b7280;">${cot.cliente} - ${cot.asesora}</div>
            </div>
        `;
    }

    /**
     * Filtrar cotizaciones por criterio
     * @param {string} filtro - Texto a filtrar
     * @returns {Array} Cotizaciones filtradas
     */
    filtrar(filtro = '') {
        return filtrarCotizaciones(this.cotizaciones, filtro);
    }

    /**
     * Seleccionar cotización
     * @param {number} id - ID de la cotización
     * @param {string} numero - Número de cotización
     * @param {string} cliente - Nombre del cliente
     * @param {string} asesora - Nombre de la asesora
     * @param {string} formaPago - Forma de pago
     */
    seleccionar(id, numero, cliente, asesora, formaPago) {
        this.cotizacionSeleccionada = {
            id,
            numero,
            cliente,
            asesora,
            formaPago
        };

        // Actualizar campos del formulario
        if (document.getElementById('cotizacion_id_editable')) {
            document.getElementById('cotizacion_id_editable').value = id;
        }
        if (this.inputBusqueda) {
            this.inputBusqueda.value = numero;
        }
        if (document.getElementById('numero_cotizacion_editable')) {
            document.getElementById('numero_cotizacion_editable').value = numero;
        }
        if (document.getElementById('cliente_editable')) {
            document.getElementById('cliente_editable').value = cliente;
        }
        if (document.getElementById('asesora_editable')) {
            document.getElementById('asesora_editable').value = asesora;
        }
        if (document.getElementById('forma_de_pago_editable')) {
            document.getElementById('forma_de_pago_editable').value = formaPago || '';
        }

        // Actualizar div de seleccionado
        if (this.seleccionadoDiv) {
            const textDiv = this.seleccionadoDiv.querySelector('#cotizacion_selected_text_editable');
            if (textDiv) {
                textDiv.textContent = `${numero} - ${cliente}`;
            }
            this.seleccionadoDiv.style.display = 'block';
        }

        this.cerrarDropdown();

        // Ejecutar callback si existe
        if (this.callbackSeleccionar) {
            this.callbackSeleccionar(id);
        }

        logWithEmoji('', `Cotización seleccionada: ${numero}`);
    }

    /**
     * Cerrar dropdown
     */
    cerrarDropdown() {
        if (this.dropdown) {
            this.dropdown.style.display = 'none';
        }
    }

    /**
     * Obtener cotización actual
     * @returns {Object} Cotización seleccionada o null
     */
    obtenerSeleccionada() {
        return this.cotizacionSeleccionada;
    }

    /**
     * Obtener cotización por ID
     * @param {number} id - ID de la cotización
     * @returns {Object} Cotización encontrada o null
     */
    obtenerPorId(id) {
        return buscarEnArray(this.cotizaciones, 'id', id);
    }

    /**
     * Obtener todas las cotizaciones
     * @returns {Array} Array de cotizaciones
     */
    obtenerTodas() {
        return this.cotizaciones;
    }

    /**
     * Actualizar lista de cotizaciones
     * @param {Array} nuevasCotizaciones - Nuevas cotizaciones
     */
    actualizar(nuevasCotizaciones) {
        this.cotizaciones = nuevasCotizaciones;
        logWithEmoji('🔄', `Cotizaciones actualizadas: ${nuevasCotizaciones.length}`);
    }

    /**
     * Limpiar selección
     */
    limpiar() {
        this.cotizacionSeleccionada = null;
        if (this.inputBusqueda) this.inputBusqueda.value = '';
        if (document.getElementById('cotizacion_id_editable')) {
            document.getElementById('cotizacion_id_editable').value = '';
        }
        if (this.seleccionadoDiv) {
            this.seleccionadoDiv.style.display = 'none';
        }
        logWithEmoji('🗑️', 'Selección de cotización limpiada');
    }
}

/**
 * CARGADOR DE COTIZACIÓN (via AJAX)
 * Clase separada para cargar datos de cotización del servidor
 */

class CargadorCotizacion {
    /**
     * Constructor
     * @param {string} urlEndpoint - URL del endpoint de la API
     */
    constructor(urlEndpoint = '/asesores/pedidos-produccion/obtener-datos-cotizacion') {
        this.urlEndpoint = urlEndpoint;
        this.csrfToken = this.obtenerCSRFToken();
    }

    /**
     * Obtener CSRF token del DOM
     * @returns {string} Token CSRF
     */
    obtenerCSRFToken() {
        return document.querySelector('meta[name="csrf-token"]')?.content || 
               document.querySelector('input[name="_token"]')?.value || '';
    }

    /**
     * Cargar datos de cotización desde el servidor
     * @param {number} cotizacionId - ID de la cotización
     * @returns {Promise<Object>} Datos de la cotización
     */
    async cargar(cotizacionId) {
        if (!cotizacionId) {
            throw new Error('ID de cotización requerido');
        }

        try {
            logWithEmoji('📥', `Cargando datos de cotización: ${cotizacionId}`);

            const response = await fetch(`${this.urlEndpoint}/${cotizacionId}`);
            
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status}`);
            }

            const data = await response.json();

            if (data.error) {
                throw new Error(data.error);
            }

            logWithEmoji('', `Datos cargados correctamente`);
            return data;

        } catch (error) {
            logWithEmoji('', `Error al cargar cotización: ${error.message}`);
            throw error;
        }
    }

    /**
     * Procesar datos cargados
     * @param {Object} data - Datos de la cotización
     * @returns {Object} Datos procesados
     */
    procesar(data) {
        return {
            prendas: data.prendas || [],
            logo: data.logo || null,
            especificaciones: data.especificaciones || null,
            reflectivo: data.reflectivo || null,
            tipo: data.tipo_cotizacion_codigo || 'P',
            formaPago: data.forma_pago || '',
            tallas: this.extraerTallas(data.prendas || [])
        };
    }

    /**
     * Extraer tallas únicas de las prendas
     * @param {Array} prendas - Array de prendas
     * @returns {Array} Tallas únicas
     */
    extraerTallas(prendas) {
        const tallas = [];
        prendas.forEach(prenda => {
            if (prenda.tallas && Array.isArray(prenda.tallas)) {
                prenda.tallas.forEach(talla => {
                    if (!tallas.includes(talla)) {
                        tallas.push(talla);
                    }
                });
            }
        });
        return tallas;
    }
}

/**
 * INSTANCIA GLOBAL
 */
window.gestorCotizacion = null;
window.cargadorCotizacion = null;

// Exportar para ES6 modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        GestorCotizacion,
        CargadorCotizacion
    };
}
