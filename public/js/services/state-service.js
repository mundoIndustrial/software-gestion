/**
 * Servicio de Gestión de Estado para Pedidos
 * Centraliza todo el estado de la aplicación de crear pedidos
 * 
 * @class PedidoStateManager
 */

class PedidoStateManager {
    constructor() {
        this.reset();
        this.observers = [];
    }

    /**
     * Resetear todo el estado a valores iniciales
     */
    reset() {
        // Información de cotización
        this.cotizacion = {
            id: null,
            numero: null,
            cliente: null,
            asesora: null,
            formaPago: null
        };
        
        // Prendas
        this.prendas = [];
        this.prendasEliminadas = new Set();
        
        // Tipo de pedido
        this.tipo = 'P'; // P (Prenda), L (Logo), PL (Prenda+Logo), RF (Reflectivo)
        this.esReflectivo = false;
        this.esLogo = false;
        
        // Tallas
        this.tallasDisponibles = [];
        
        // Fotos nuevas (organizadas por tipo)
        this.fotosNuevas = {
            prendas: {},      // { prendaIndex: [fotos] }
            telas: {},        // { prendaIndex: { telaIndex: [fotos] } }
            logos: [],        // [fotos]
            reflectivos: []   // [fotos]
        };
        
        // Fotos eliminadas (URLs)
        this.fotosEliminadas = new Set();
        
        // Logo
        this.logo = null;
        this.logoCotizacionId = null;
        
        // Especificaciones y datos adicionales
        this.especificaciones = null;
        this.datosReflectivo = null;
    }

    // ============================================================
    // COTIZACIÓN
    // ============================================================

    /**
     * Establecer datos de cotización
     * @param {Object} data - Datos de la cotización
     */
    setCotizacion(data) {
        this.cotizacion = { ...this.cotizacion, ...data };
        this.notifyObservers('cotizacion', this.cotizacion);

    }

    /**
     * Obtener datos de cotización
     * @returns {Object}
     */
    getCotizacion() {
        return this.cotizacion;
    }

    /**
     * Obtener ID de cotización
     * @returns {number|null}
     */
    getCotizacionId() {
        return this.cotizacion.id;
    }

    // ============================================================
    // PRENDAS
    // ============================================================

    /**
     * Establecer array completo de prendas
     * @param {Array} prendas - Array de prendas
     */
    setPrendas(prendas) {
        this.prendas = prendas;
        this.notifyObservers('prendas', this.getPrendas());

    }

    /**
     * Obtener prendas activas (sin las eliminadas)
     * @returns {Array}
     */
    getPrendas() {
        return this.prendas.filter((_, idx) => !this.prendasEliminadas.has(idx));
    }

    /**
     * Obtener todas las prendas (incluyendo eliminadas)
     * @returns {Array}
     */
    getAllPrendas() {
        return this.prendas;
    }

    /**
     * Agregar una prenda
     * @param {Object} prenda - Datos de la prenda
     * @returns {number} Índice de la prenda agregada
     */
    addPrenda(prenda) {
        this.prendas.push(prenda);
        const index = this.prendas.length - 1;
        this.notifyObservers('prendaAdded', { prenda, index });

        return index;
    }

    /**
     * Eliminar una prenda (marca como eliminada)
     * @param {number} index - Índice de la prenda
     */
    removePrenda(index) {
        this.prendasEliminadas.add(index);
        this.notifyObservers('prendaRemoved', index);

    }

    /**
     * Obtener una prenda por índice
     * @param {number} index - Índice de la prenda
     * @returns {Object|null}
     */
    getPrenda(index) {
        if (this.prendasEliminadas.has(index)) {
            return null;
        }
        return this.prendas[index] || null;
    }

    /**
     * Actualizar datos de una prenda
     * @param {number} index - Índice de la prenda
     * @param {Object} data - Datos a actualizar
     */
    updatePrenda(index, data) {
        if (this.prendas[index]) {
            this.prendas[index] = { ...this.prendas[index], ...data };
            this.notifyObservers('prendaUpdated', { index, data });

        }
    }

    /**
     * Verificar si una prenda está eliminada
     * @param {number} index - Índice de la prenda
     * @returns {boolean}
     */
    isPrendaEliminada(index) {
        return this.prendasEliminadas.has(index);
    }

    // ============================================================
    // TIPO DE PEDIDO
    // ============================================================

    /**
     * Establecer tipo de pedido
     * @param {string} tipo - Tipo: 'P', 'L', 'PL', 'RF'
     */
    setTipo(tipo) {
        this.tipo = tipo;
        this.esReflectivo = tipo === 'RF';
        this.esLogo = tipo === 'L' || tipo === 'PL';
        this.notifyObservers('tipo', tipo);

    }

    /**
     * Obtener tipo de pedido
     * @returns {string}
     */
    getTipo() {
        return this.tipo;
    }

    /**
     * Verificar si es reflectivo
     * @returns {boolean}
     */
    isReflectivo() {
        return this.esReflectivo;
    }

    /**
     * Verificar si es logo
     * @returns {boolean}
     */
    isLogo() {
        return this.esLogo;
    }

    // ============================================================
    // TALLAS
    // ============================================================

    /**
     * Establecer tallas disponibles
     * @param {Array} tallas - Array de tallas
     */
    setTallasDisponibles(tallas) {
        this.tallasDisponibles = tallas;

    }

    /**
     * Obtener tallas disponibles
     * @returns {Array}
     */
    getTallasDisponibles() {
        return this.tallasDisponibles;
    }

    // ============================================================
    // FOTOS NUEVAS
    // ============================================================

    /**
     * Agregar foto de prenda
     * @param {number} prendaIndex - Índice de la prenda
     * @param {Object} foto - Datos de la foto
     */
    addFotoPrenda(prendaIndex, foto) {
        if (!this.fotosNuevas.prendas[prendaIndex]) {
            this.fotosNuevas.prendas[prendaIndex] = [];
        }
        this.fotosNuevas.prendas[prendaIndex].push(foto);

    }

    /**
     * Obtener fotos de prenda
     * @param {number} prendaIndex - Índice de la prenda
     * @returns {Array}
     */
    getFotosPrenda(prendaIndex) {
        return this.fotosNuevas.prendas[prendaIndex] || [];
    }

    /**
     * Agregar foto de tela
     * @param {number} prendaIndex - Índice de la prenda
     * @param {number} telaIndex - Índice de la tela
     * @param {Object} foto - Datos de la foto
     */
    addFotoTela(prendaIndex, telaIndex, foto) {
        if (!this.fotosNuevas.telas[prendaIndex]) {
            this.fotosNuevas.telas[prendaIndex] = {};
        }
        if (!this.fotosNuevas.telas[prendaIndex][telaIndex]) {
            this.fotosNuevas.telas[prendaIndex][telaIndex] = [];
        }
        this.fotosNuevas.telas[prendaIndex][telaIndex].push(foto);

    }

    /**
     * Obtener fotos de tela
     * @param {number} prendaIndex - Índice de la prenda
     * @param {number} telaIndex - Índice de la tela
     * @returns {Array}
     */
    getFotosTela(prendaIndex, telaIndex) {
        return this.fotosNuevas.telas[prendaIndex]?.[telaIndex] || [];
    }

    /**
     * Agregar foto de logo
     * @param {Object} foto - Datos de la foto
     */
    addFotoLogo(foto) {
        this.fotosNuevas.logos.push(foto);

    }

    /**
     * Obtener fotos de logo
     * @returns {Array}
     */
    getFotosLogo() {
        return this.fotosNuevas.logos;
    }

    /**
     * Agregar foto de reflectivo
     * @param {Object} foto - Datos de la foto
     */
    addFotoReflectivo(foto) {
        this.fotosNuevas.reflectivos.push(foto);

    }

    /**
     * Obtener fotos de reflectivo
     * @returns {Array}
     */
    getFotosReflectivo() {
        return this.fotosNuevas.reflectivos;
    }

    // ============================================================
    // FOTOS ELIMINADAS
    // ============================================================

    /**
     * Marcar foto como eliminada
     * @param {string} fotoUrl - URL de la foto
     */
    markFotoEliminada(fotoUrl) {
        this.fotosEliminadas.add(fotoUrl);

    }

    /**
     * Verificar si una foto está eliminada
     * @param {string} fotoUrl - URL de la foto
     * @returns {boolean}
     */
    isFotoEliminada(fotoUrl) {
        return this.fotosEliminadas.has(fotoUrl);
    }

    /**
     * Obtener todas las fotos eliminadas
     * @returns {Set}
     */
    getFotosEliminadas() {
        return this.fotosEliminadas;
    }

    /**
     * Limpiar fotos eliminadas
     */
    clearFotosEliminadas() {
        this.fotosEliminadas.clear();

    }

    // ============================================================
    // LOGO
    // ============================================================

    /**
     * Establecer datos de logo
     * @param {Object} logo - Datos del logo
     */
    setLogo(logo) {
        this.logo = logo;
        if (logo && logo.id) {
            this.logoCotizacionId = logo.id;
        }

    }

    /**
     * Obtener datos de logo
     * @returns {Object|null}
     */
    getLogo() {
        return this.logo;
    }

    /**
     * Obtener ID de logo cotización
     * @returns {number|null}
     */
    getLogoCotizacionId() {
        return this.logoCotizacionId;
    }

    // ============================================================
    // ESPECIFICACIONES Y DATOS ADICIONALES
    // ============================================================

    /**
     * Establecer especificaciones
     * @param {Object} especificaciones - Especificaciones
     */
    setEspecificaciones(especificaciones) {
        this.especificaciones = especificaciones;
    }

    /**
     * Obtener especificaciones
     * @returns {Object|null}
     */
    getEspecificaciones() {
        return this.especificaciones;
    }

    /**
     * Establecer datos de reflectivo
     * @param {Object} datos - Datos del reflectivo
     */
    setDatosReflectivo(datos) {
        this.datosReflectivo = datos;
    }

    /**
     * Obtener datos de reflectivo
     * @returns {Object|null}
     */
    getDatosReflectivo() {
        return this.datosReflectivo;
    }

    // ============================================================
    // OBSERVER PATTERN (para reactividad)
    // ============================================================

    /**
     * Suscribirse a cambios de estado
     * @param {Function} callback - Función a ejecutar cuando cambie el estado
     * @returns {Function} Función para desuscribirse
     */
    subscribe(callback) {
        this.observers.push(callback);
        return () => {
            this.observers = this.observers.filter(obs => obs !== callback);
        };
    }

    /**
     * Notificar a todos los observadores
     * @param {string} event - Nombre del evento
     * @param {*} data - Datos del evento
     */
    notifyObservers(event, data) {
        this.observers.forEach(callback => {
            try {
                callback(event, data);
            } catch (error) {

            }
        });
    }

    // ============================================================
    // UTILIDADES Y DEBUG
    // ============================================================

    /**
     * Obtener todo el estado actual (para debugging)
     * @returns {Object}
     */
    getState() {
        return {
            cotizacion: this.cotizacion,
            prendas: this.getPrendas(),
            prendasEliminadas: Array.from(this.prendasEliminadas),
            tipo: this.tipo,
            esReflectivo: this.esReflectivo,
            esLogo: this.esLogo,
            tallasDisponibles: this.tallasDisponibles,
            fotosNuevas: this.fotosNuevas,
            fotosEliminadas: Array.from(this.fotosEliminadas),
            logo: this.logo,
            especificaciones: this.especificaciones,
            datosReflectivo: this.datosReflectivo
        };
    }

    /**
     * Imprimir estado en consola (debugging)
     */
    debug() {

        console.table({
            'Cotización ID': this.cotizacion.id,
            'Cliente': this.cotizacion.cliente,
            'Tipo': this.tipo,
            'Prendas activas': this.getPrendas().length,
            'Prendas eliminadas': this.prendasEliminadas.size,
            'Tallas disponibles': this.tallasDisponibles.length,
            'Fotos nuevas prendas': Object.keys(this.fotosNuevas.prendas).length,
            'Fotos nuevas telas': Object.keys(this.fotosNuevas.telas).length,
            'Fotos logos': this.fotosNuevas.logos.length,
            'Fotos eliminadas': this.fotosEliminadas.size
        });

    }

    /**
     * Exportar estado a JSON (para guardar/restaurar)
     * @returns {string}
     */
    toJSON() {
        return JSON.stringify(this.getState());
    }

    /**
     * Importar estado desde JSON
     * @param {string} json - Estado en formato JSON
     */
    fromJSON(json) {
        try {
            const state = JSON.parse(json);
            this.cotizacion = state.cotizacion || {};
            this.prendas = state.prendas || [];
            this.prendasEliminadas = new Set(state.prendasEliminadas || []);
            this.tipo = state.tipo || 'P';
            this.esReflectivo = state.esReflectivo || false;
            this.esLogo = state.esLogo || false;
            this.tallasDisponibles = state.tallasDisponibles || [];
            this.fotosNuevas = state.fotosNuevas || { prendas: {}, telas: {}, logos: [], reflectivos: [] };
            this.fotosEliminadas = new Set(state.fotosEliminadas || []);
            this.logo = state.logo || null;
            this.especificaciones = state.especificaciones || null;
            this.datosReflectivo = state.datosReflectivo || null;
        } catch (error) {

        }
    }
}

// Crear instancia global
window.PedidoState = new PedidoStateManager();

// Exponer para debugging en consola
window.debugPedidoState = () => window.PedidoState.debug();
