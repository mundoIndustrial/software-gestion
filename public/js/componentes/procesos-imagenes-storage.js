/**
 * ================================================
 * STORAGE DE IMÁGENES DE PROCESOS
 * ================================================
 * 
 * Sistema de almacenamiento para imágenes de procesos individuales
 * Compatible con el sistema de manejo de imágenes existente
 * 
 * @module ProcesosImagenesStorage
 * @version 2.0.0
 */

/**
 * Storage para imágenes de procesos individuales
 */
window.procesosImagenesStorage = {
    // Almacenamiento interno
    _imagenes: {},
    
    /**
     * Agregar una imagen a un proceso específico
     * @param {number} procesoIndex - Índice del proceso (1, 2, 3)
     * @param {Object} imagen - Objeto con los datos de la imagen
     */
    agregarImagen: function(procesoIndex, imagen) {
        if (!this._imagenes[procesoIndex]) {
            this._imagenes[procesoIndex] = [];
        }
        
        this._imagenes[procesoIndex].push({
            ...imagen,
            fechaCreacion: imagen.fechaCreacion || new Date().toISOString()
        });
    },
    
    /**
     * Eliminar una imagen de un proceso específico
     * @param {number} procesoIndex - Índice del proceso (1, 2, 3)
     * @param {number} imagenIndex - Índice de la imagen a eliminar
     */
    eliminarImagen: function(procesoIndex, imagenIndex) {
        if (!this._imagenes[procesoIndex]) {
            return false;
        }
        
        if (imagenIndex < 0 || imagenIndex >= this._imagenes[procesoIndex].length) {
            return false;
        }
        
        const imagenEliminada = this._imagenes[procesoIndex].splice(imagenIndex, 1)[0];
        return true;
    },
    
    /**
     * Eliminar todas las imágenes de un proceso específico
     * @param {number} procesoIndex - Índice del proceso (1, 2, 3)
     */
    eliminarTodasLasImagenes: function(procesoIndex) {
        if (!this._imagenes[procesoIndex]) {
            return;
        }
        
        const cantidad = this._imagenes[procesoIndex].length;
        this._imagenes[procesoIndex] = [];
    },
    
    /**
     * Obtener todas las imágenes de un proceso específico
     * @param {number} procesoIndex - Índice del proceso (1, 2, 3)
     * @returns {Array} Array con las imágenes del proceso
     */
    obtenerImagenes: function(procesoIndex) {
        return this._imagenes[procesoIndex] || [];
    },
    
    /**
     * Obtener la última imagen de un proceso específico
     * @param {number} procesoIndex - Índice del proceso (1, 2, 3)
     * @returns {Object|null} Última imagen o null si no hay
     */
    obtenerUltimaImagen: function(procesoIndex) {
        const imagenes = this.obtenerImagenes(procesoIndex);
        return imagenes.length > 0 ? imagenes[imagenes.length - 1] : null;
    },
    
    /**
     * Obtener la primera imagen de un proceso específico
     * @param {number} procesoIndex - Índice del proceso (1, 2, 3)
     * @returns {Object|null} Primera imagen o null si no hay
     */
    obtenerPrimeraImagen: function(procesoIndex) {
        const imagenes = this.obtenerImagenes(procesoIndex);
        return imagenes.length > 0 ? imagenes[0] : null;
    },
    
    /**
     * Verificar si un proceso tiene imágenes
     * @param {number} procesoIndex - Índice del proceso (1, 2, 3)
     * @returns {boolean} True si hay imágenes
     */
    tieneImagenes: function(procesoIndex) {
        const imagenes = this.obtenerImagenes(procesoIndex);
        return imagenes.length > 0;
    },
    
    /**
     * Obtener el conteo de imágenes de un proceso
     * @param {number} procesoIndex - Índice del proceso (1, 2, 3)
     * @returns {number} Número de imágenes
     */
    contarImagenes: function(procesoIndex) {
        return this.obtenerImagenes(procesoIndex).length;
    },
    
    /**
     * Limpiar todas las imágenes de todos los procesos
     */
    limpiar: function() {
        this._imagenes = {};
    },
    
    /**
     * Serializar los datos del storage
     * @returns {string} Datos serializados en JSON
     */
    serializar: function() {
        return JSON.stringify({
            imagenes: this._imagenes,
            timestamp: new Date().toISOString(),
            version: '2.0.0'
        });
    },
    
    /**
     * Restaurar datos desde JSON serializado
     * @param {string} datosSerializados - Datos serializados
     * @returns {boolean} True si se restauró correctamente
     */
    restaurar: function(datosSerializados) {
        try {
            const datos = JSON.parse(datosSerializados);
            this._imagenes = datos.imagenes || {};
            return true;
        } catch (error) {
            return false;
        }
    },
    
    /**
     * Obtener un resumen del storage
     * @returns {Object} Resumen del estado actual
     */
    obtenerResumen: function() {
        const resumen = {
            totalProcesos: Object.keys(this._imagenes).length,
            totalImagenes: 0,
            procesos: {}
        };
        
        for (const [procesoIndex, imagenes] of Object.entries(this._imagenes)) {
            resumen.totalImagenes += imagenes.length;
            resumen.procesos[procesoIndex] = {
                cantidad: imagenes.length,
                ultimaActualizacion: imagenes.length > 0 ? imagenes[imagenes.length - 1].fechaCreacion : null
            };
        }
        
        return resumen;
    },
    
    /**
     * Exportar todos los datos para envío
     * @returns {Object} Datos exportados
     */
    exportarDatos: function() {
        return {
            imagenes: this._imagenes,
            resumen: this.obtenerResumen(),
            timestamp: new Date().toISOString(),
            version: '2.0.0'
        };
    }
}
