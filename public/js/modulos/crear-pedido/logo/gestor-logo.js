/**
 * GESTOR DE LOGO - Crear Pedido Editable (FASE 2)
 * 
 * Centraliza toda la lógica de logo y prendas tipo logo
 * Responsabilidades:
 * - Almacenar datos de logo
 * - Gestionar técnicas
 * - Gestionar ubicaciones
 * - Gestionar fotos de logo
 */

class GestorLogo {
    /**
     * Constructor
     * @param {Object} logoCotizacion - Datos iniciales del logo de la cotización
     */
    constructor(logoCotizacion = {}) {
        this.logoCotizacion = logoCotizacion;
        this.descripcion = logoCotizacion.descripcion || '';
        this.tecnicas = [];
        this.ubicaciones = [];
        this.fotos = [];
        this.observacionesTecnicas = logoCotizacion.observaciones_tecnicas || '';
        this.observacionesGenerales = [];

        this.inicializar();
    }

    /**
     * Inicializar datos del logo
     */
    inicializar() {
        // Cargar técnicas
        if (this.logoCotizacion.tecnicas && Array.isArray(this.logoCotizacion.tecnicas)) {
            this.logoCotizacion.tecnicas.forEach(tecnica => {
                const tecnicaText = typeof tecnica === 'object' ? tecnica.nombre : tecnica;
                if (!this.tecnicas.includes(tecnicaText)) {
                    this.tecnicas.push(tecnicaText);
                }
            });
        }

        // Cargar ubicaciones
        if (this.logoCotizacion.ubicaciones) {
            const ubicacionesArray = parseArrayData(this.logoCotizacion.ubicaciones);
            ubicacionesArray.forEach(ubicacion => {
                if (typeof ubicacion === 'object' && ubicacion.ubicacion) {
                    this.ubicaciones.push({
                        id: window.generarUUID(),
                        ubicacion: ubicacion.ubicacion,
                        opciones: Array.isArray(ubicacion.opciones) ? ubicacion.opciones : [],
                        tallas: Array.isArray(ubicacion.tallas) ? ubicacion.tallas.map(t => t.talla || t) : [],
                        tallasCantidad: ubicacion.tallasCantidad || {},
                        observaciones: ubicacion.observaciones || ''
                    });
                }
            });
        }

        // Cargar fotos
        if (this.logoCotizacion.fotos && Array.isArray(this.logoCotizacion.fotos)) {
            this.logoCotizacion.fotos.forEach(foto => {
                const fotoUrl = foto.url || foto.ruta_webp || foto.ruta_original;
                if (fotoUrl) {
                    this.fotos.push({
                        id: foto.id,
                        url: fotoUrl,
                        preview: fotoUrl,
                        existing: true
                    });
                }
            });
        }

    }

    /**
     * Obtener descripción
     * @returns {string} Descripción del logo
     */
    obtenerDescripcion() {
        return this.descripcion;
    }

    /**
     * Establecer descripción
     * @param {string} desc - Nueva descripción
     */
    establecerDescripcion(desc) {
        this.descripcion = desc;
    }

    /**
     * Agregar técnica
     * @param {string} tecnica - Técnica a agregar
     * @returns {boolean} true si fue agregada
     */
    agregarTecnica(tecnica) {
        if (!tecnica || tecnica.trim() === '') {
            mostrarAdvertencia('Técnica vacía', 'Por favor selecciona una técnica');
            return false;
        }

        if (this.tecnicas.includes(tecnica)) {
            mostrarInfo('Técnica duplicada', 'Esta técnica ya está agregada');
            return false;
        }

        this.tecnicas.push(tecnica);
        return true;
    }

    /**
     * Eliminar técnica
     * @param {number} index - Índice de la técnica
     */
    eliminarTecnica(index) {
        if (index >= 0 && index < this.tecnicas.length) {
            const eliminada = this.tecnicas.splice(index, 1)[0];
        }
    }

    /**
     * Obtener todas las técnicas
     * @returns {Array} Array de técnicas
     */
    obtenerTecnicas() {
        return this.tecnicas;
    }

    /**
     * Agregar ubicación
     * @param {Object} ubicacion - Objeto de ubicación
     */
    agregarUbicacion(ubicacion) {
        if (!ubicacion.ubicacion || ubicacion.ubicacion.trim() === '') {
            mostrarAdvertencia('Ubicación vacía', 'La ubicación no puede estar vacía');
            return false;
        }

        if (!ubicacion.opciones || ubicacion.opciones.length === 0) {
            mostrarAdvertencia('Sin opciones', 'Debes seleccionar al menos una opción de ubicación');
            return false;
        }

        const nueva = {
            id: window.generarUUID(),
            ubicacion: ubicacion.ubicacion.toUpperCase(),
            opciones: ubicacion.opciones || [],
            tallas: ubicacion.tallas || [],
            tallasCantidad: ubicacion.tallasCantidad || {},
            observaciones: ubicacion.observaciones || ''
        };

        this.ubicaciones.push(nueva);
        return true;
    }

    /**
     * Actualizar ubicación
     * @param {string} id - ID de la ubicación
     * @param {Object} datos - Datos a actualizar
     */
    actualizarUbicacion(id, datos) {
        const index = this.ubicaciones.findIndex(u => u.id === id);
        if (index !== -1) {
            this.ubicaciones[index] = {
                ...this.ubicaciones[index],
                ...datos
            };
        }
    }

    /**
     * Eliminar ubicación
     * @param {string} id - ID de la ubicación
     */
    eliminarUbicacion(id) {
        const index = this.ubicaciones.findIndex(u => u.id === id);
        if (index !== -1) {
            const eliminada = this.ubicaciones.splice(index, 1)[0];
        }
    }

    /**
     * Obtener todas las ubicaciones
     * @returns {Array} Array de ubicaciones
     */
    obtenerUbicaciones() {
        return this.ubicaciones;
    }

    /**
     * Obtener ubicación por ID
     * @param {string} id - ID de la ubicación
     * @returns {Object} Ubicación encontrada o null
     */
    obtenerUbicacionPorId(id) {
        return this.ubicaciones.find(u => u.id === id) || null;
    }

    /**
     * Agregar foto
     * @param {Object} foto - Objeto de foto
     * @returns {boolean} true si fue agregada
     */
    agregarFoto(foto) {
        if (this.fotos.length >= CONFIG.MAX_FOTOS_LOGO) {
            mostrarError('Límite alcanzado', MENSAJES.FOTO_LIMITE_ALCANZADO(CONFIG.MAX_FOTOS_LOGO));
            return false;
        }

        this.fotos.push({
            url: foto.preview || foto.url,
            preview: foto.preview || foto.url,
            file: foto.file,
            existing: false
        });

        return true;
    }

    /**
     * Eliminar foto
     * @param {number} index - Índice de la foto
     */
    eliminarFoto(index) {
        if (index >= 0 && index < this.fotos.length) {
            this.fotos.splice(index, 1);
        }
    }

    /**
     * Obtener todas las fotos
     * @returns {Array} Array de fotos
     */
    obtenerFotos() {
        return this.fotos;
    }

    /**
     * Cantidad de fotos
     * @returns {number} Cantidad
     */
    cantidadFotos() {
        return this.fotos.length;
    }

    /**
     * Espacios disponibles para fotos
     * @returns {number} Espacios disponibles
     */
    espaciosFotos() {
        return CONFIG.MAX_FOTOS_LOGO - this.fotos.length;
    }

    /**
     * Establecer observaciones de técnicas
     * @param {string} obs - Observaciones
     */
    establecerObservacionesTecnicas(obs) {
        this.observacionesTecnicas = obs;
    }

    /**
     * Obtener observaciones de técnicas
     * @returns {string} Observaciones
     */
    obtenerObservacionesTecnicas() {
        return this.observacionesTecnicas;
    }

    /**
     * Agregar observación general
     * @param {string} obs - Observación
     */
    agregarObservacionGeneral(obs) {
        if (obs && obs.trim() !== '') {
            this.observacionesGenerales.push(obs);
        }
    }

    /**
     * Obtener observaciones generales
     * @returns {Array} Array de observaciones
     */
    obtenerObservacionesGenerales() {
        return this.observacionesGenerales;
    }

    /**
     * Obtener datos formateados para envío
     * @returns {Object} Datos formateados
     */
    obtenerDatosFormato() {
        return {
            descripcion: this.descripcion,
            tecnicas: this.tecnicas,
            ubicaciones: this.ubicaciones,
            fotos: this.fotos,
            observacionesTecnicas: this.observacionesTecnicas,
            observacionesGenerales: this.observacionesGenerales
        };
    }

    /**
     * Validar que el logo tenga datos mínimos
     * @returns {Object} {valido: boolean, errores: Array}
     */
    validar() {
        const errores = [];

        if (!this.descripcion || this.descripcion.trim() === '') {
            errores.push('Logo: Falta descripción');
        }

        if (this.tecnicas.length === 0) {
            errores.push('Logo: Debe tener al menos una técnica');
        }

        if (this.ubicaciones.length === 0) {
            errores.push('Logo: Debe tener al menos una ubicación');
        }

        return {
            valido: errores.length === 0,
            errores
        };
    }

    /**
     * Limpiar todo
     */
    limpiar() {
        this.descripcion = '';
        this.tecnicas = [];
        this.ubicaciones = [];
        this.fotos = [];
        this.observacionesTecnicas = '';
        this.observacionesGenerales = [];
    }
}

/**
 * INSTANCIA GLOBAL
 */
window.gestorLogo = null;

// Exportar para ES6 modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { GestorLogo };
}
