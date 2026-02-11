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

console.log(' Storage de Imágenes de Procesos cargado...');

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
        console.log(`[procesosImagenesStorage]  Agregando imagen al proceso ${procesoIndex}`);
        
        if (!this._imagenes[procesoIndex]) {
            this._imagenes[procesoIndex] = [];
        }
        
        this._imagenes[procesoIndex].push({
            ...imagen,
            fechaCreacion: imagen.fechaCreacion || new Date().toISOString()
        });
        
        console.log(`[procesosImagenesStorage]  Imagen agregada, total en proceso ${procesoIndex}:`, this._imagenes[procesoIndex].length);
    },
    
    /**
     * Eliminar una imagen de un proceso específico
     * @param {number} procesoIndex - Índice del proceso (1, 2, 3)
     * @param {number} imagenIndex - Índice de la imagen a eliminar
     */
    eliminarImagen: function(procesoIndex, imagenIndex) {
        console.log(`[procesosImagenesStorage] 🗑️ Eliminando imagen ${imagenIndex} del proceso ${procesoIndex}`);
        
        if (!this._imagenes[procesoIndex]) {
            console.warn(`[procesosImagenesStorage]  No hay imágenes en el proceso ${procesoIndex}`);
            return false;
        }
        
        if (imagenIndex < 0 || imagenIndex >= this._imagenes[procesoIndex].length) {
            console.warn(`[procesosImagenesStorage]  Índice de imagen inválido: ${imagenIndex}`);
            return false;
        }
        
        const imagenEliminada = this._imagenes[procesoIndex].splice(imagenIndex, 1)[0];
        console.log(`[procesosImagenesStorage]  Imagen eliminada: ${imagenEliminada.name}`);
        return true;
    },
    
    /**
     * Eliminar todas las imágenes de un proceso específico
     * @param {number} procesoIndex - Índice del proceso (1, 2, 3)
     */
    eliminarTodasLasImagenes: function(procesoIndex) {
        console.log(`[procesosImagenesStorage] 🗑️ Eliminando todas las imágenes del proceso ${procesoIndex}`);
        
        if (!this._imagenes[procesoIndex]) {
            console.warn(`[procesosImagenesStorage]  No hay imágenes en el proceso ${procesoIndex}`);
            return;
        }
        
        const cantidad = this._imagenes[procesoIndex].length;
        this._imagenes[procesoIndex] = [];
        console.log(`[procesosImagenesStorage]  Eliminadas ${cantidad} imágenes del proceso ${procesoIndex}`);
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
        console.log('[procesosImagenesStorage] 🧹 Limpiando todas las imágenes de procesos');
        this._imagenes = {};
        console.log('[procesosImagenesStorage]  Storage limpiado');
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
            console.log('[procesosImagenesStorage]  Datos restaurados correctamente');
            return true;
        } catch (error) {
            console.error('[procesosImagenesStorage]  Error al restaurar datos:', error);
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
