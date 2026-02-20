/**
 * ================================================
 * STORAGE UNIVERSAL DE IMÁGENES - SEPARADO POR TIPO
 * ================================================
 * 
 * Sistema de almacenamiento completamente separado para cada tipo de imagen:
 * - prendas: Imágenes de prendas (modal de creación de prendas)
 * - telas: Imágenes de telas (modal de creación de prendas)
 * - procesos: Imágenes de procesos (modal de procesos genéricos)
 * 
 * Cada tipo tiene su propio espacio aislado sin contaminación cruzada
 * 
 * @module UniversalImagenesStorage
 * @version 1.0.0
 */

/**
 * Storage universal completamente separado por tipo
 */
window.universalImagenesStorage = {
    // Almacenamiento interno separado por tipo
    _storage: {
        prendas: {},
        telas: {},
        procesos: {}
    },
    
    /**
     * Agregar una imagen a un tipo específico
     * @param {string} tipo - Tipo de imagen ('prendas', 'telas', 'procesos')
     * @param {number|string} indice - Índice o identificador
     * @param {Object} imagen - Objeto con los datos de la imagen
     */
    agregarImagen: function(tipo, indice, imagen) {
        if (!this._storage[tipo]) {
            console.error(`[UniversalStorage] Tipo '${tipo}' no válido. Tipos válidos: ${Object.keys(this._storage).join(', ')}`);
            return false;
        }
        
        if (!this._storage[tipo][indice]) {
            this._storage[tipo][indice] = [];
        }
        
        this._storage[tipo][indice].push({
            ...imagen,
            fechaCreacion: imagen.fechaCreacion || new Date().toISOString(),
            tipo: tipo,
            indice: indice
        });
        
        console.log(`[UniversalStorage] ✅ Imagen agregada: ${tipo}[${indice}] - Total: ${this._storage[tipo][indice].length}`);
        return true;
    },
    
    /**
     * Eliminar una imagen de un tipo específico
     * @param {string} tipo - Tipo de imagen ('prendas', 'telas', 'procesos')
     * @param {number|string} indice - Índice o identificador
     * @param {number} imagenIndex - Índice de la imagen a eliminar
     */
    eliminarImagen: function(tipo, indice, imagenIndex) {
        if (!this._storage[tipo] || !this._storage[tipo][indice]) {
            return false;
        }
        
        if (imagenIndex < 0 || imagenIndex >= this._storage[tipo][indice].length) {
            return false;
        }
        
        const imagenEliminada = this._storage[tipo][indice].splice(imagenIndex, 1)[0];
        console.log(`[UniversalStorage] 🗑️ Imagen eliminada: ${tipo}[${indice}][${imagenIndex}]`);
        return imagenEliminada;
    },
    
    /**
     * Eliminar todas las imágenes de un tipo específico
     * @param {string} tipo - Tipo de imagen ('prendas', 'telas', 'procesos')
     * @param {number|string} indice - Índice o identificador
     */
    eliminarTodasLasImagenes: function(tipo, indice) {
        if (!this._storage[tipo]) {
            return;
        }
        
        const cantidad = this._storage[tipo][indice]?.length || 0;
        this._storage[tipo][indice] = [];
        console.log(`[UniversalStorage] 🧹 Todas las imágenes eliminadas: ${tipo}[${indice}] - (${cantidad} imágenes)`);
    },
    
    /**
     * Obtener todas las imágenes de un tipo específico
     * @param {string} tipo - Tipo de imagen ('prendas', 'telas', 'procesos')
     * @param {number|string} indice - Índice o identificador
     * @returns {Array} Array con las imágenes
     */
    obtenerImagenes: function(tipo, indice) {
        if (!this._storage[tipo]) {
            console.error(`[UniversalStorage] Tipo '${tipo}' no válido`);
            return [];
        }
        
        const imagenes = this._storage[tipo][indice] || [];
        console.log(`[UniversalStorage] 📸 Obteniendo imágenes: ${tipo}[${indice}] - ${imagenes.length} imágenes`);
        return imagenes;
    },
    
    /**
     * Verificar si un tipo específico tiene imágenes
     * @param {string} tipo - Tipo de imagen ('prendas', 'telas', 'procesos')
     * @param {number|string} indice - Índice o identificador
     * @returns {boolean} True si hay imágenes
     */
    tieneImagenes: function(tipo, indice) {
        const imagenes = this.obtenerImagenes(tipo, indice);
        return imagenes.length > 0;
    },
    
    /**
     * Obtener conteo de imágenes de un tipo específico
     * @param {string} tipo - Tipo de imagen ('prendas', 'telas', 'procesos')
     * @param {number|string} indice - Índice o identificador
     * @returns {number} Número de imágenes
     */
    contarImagenes: function(tipo, indice) {
        return this.obtenerImagenes(tipo, indice).length;
    },
    
    /**
     * Limpiar todas las imágenes de un tipo específico
     * @param {string} tipo - Tipo de imagen ('prendas', 'telas', 'procesos')
     */
    limpiarTipo: function(tipo) {
        if (!this._storage[tipo]) {
            return;
        }
        
        const totalImagenes = Object.values(this._storage[tipo]).reduce((total, arr) => total + arr.length, 0);
        this._storage[tipo] = {};
        console.log(`[UniversalStorage] 🧹 Tipo '${tipo}' limpiado - ${totalImagenes} imágenes eliminadas`);
    },
    
    /**
     * Limpiar TODO el storage (todos los tipos)
     */
    limpiarTodo: function() {
        const totalPorTipo = {};
        Object.keys(this._storage).forEach(tipo => {
            totalPorTipo[tipo] = Object.values(this._storage[tipo]).reduce((total, arr) => total + arr.length, 0);
        });
        
        this._storage = {
            prendas: {},
            telas: {},
            procesos: {}
        };
        
        console.log(`[UniversalStorage] 🧹 Todo el storage limpiado:`, totalPorTipo);
    },
    
    /**
     * Obtener resumen completo del storage
     * @returns {Object} Resumen detallado
     */
    obtenerResumen: function() {
        const resumen = {
            totalTipos: Object.keys(this._storage).length,
            tipos: {}
        };
        
        Object.keys(this._storage).forEach(tipo => {
            resumen.tipos[tipo] = {
                totalIndices: Object.keys(this._storage[tipo]).length,
                totalImagenes: Object.values(this._storage[tipo]).reduce((total, arr) => total + arr.length, 0),
                indices: {}
            };
            
            Object.keys(this._storage[tipo]).forEach(indice => {
                resumen.tipos[tipo].indices[indice] = {
                    cantidad: this._storage[tipo][indice].length,
                    ultimaActualizacion: this._storage[tipo][indice].length > 0 ? 
                        this._storage[tipo][indice][this._storage[tipo][indice].length - 1].fechaCreacion : null
                };
            });
        });
        
        return resumen;
    },
    
    /**
     * Exportar datos de un tipo específico
     * @param {string} tipo - Tipo de imagen ('prendas', 'telas', 'procesos')
     * @returns {Object} Datos del tipo
     */
    exportarTipo: function(tipo) {
        if (!this._storage[tipo]) {
            return null;
        }
        
        return {
            tipo: tipo,
            imagenes: this._storage[tipo],
            resumen: {
                totalIndices: Object.keys(this._storage[tipo]).length,
                totalImagenes: Object.values(this._storage[tipo]).reduce((total, arr) => total + arr.length, 0)
            },
            timestamp: new Date().toISOString(),
            version: '1.0.0'
        };
    },
    
    /**
     * Debug: Mostrar estado actual del storage
     */
    debug: function() {
        console.group('🔍 [UniversalStorage] ESTADO ACTUAL');
        console.log('Resumen completo:', this.obtenerResumen());
        
        Object.keys(this._storage).forEach(tipo => {
            console.group(`📁 ${tipo.toUpperCase()}`);
            Object.keys(this._storage[tipo]).forEach(indice => {
                const imagenes = this._storage[tipo][indice];
                console.log(`  [${indice}] ${imagenes.length} imágenes:`, 
                    imagenes.map(img => ({ nombre: img.nombre || img.name, tipo: img.tipo })));
            });
            console.groupEnd();
        });
        
        console.groupEnd();
    }
};

// Inicializar con logging
console.log('✅ [UniversalStorage] Storage universal inicializado - Tipos: prendas, telas, procesos');
