/**
 * ================================================
 * ADAPTADOR DE STORAGE UNIVERSAL PARA PRENDAS
 * ================================================
 * 
 * Adaptador para mantener compatibilidad con el código existente de prendas
 * mientras usa el nuevo storage universal separado por tipo
 * 
 * @module PrendaStorageAdapter
 * @version 1.0.0
 */

/**
 * Adaptador para imágenes de prendas usando storage universal
 */
window.imagenesPrendaStorage = {
    /**
     * Establecer imágenes (compatibilidad con código existente)
     * @param {Array} imagenes - Array de imágenes
     */
    establecerImagenes: function(imagenes) {
        if (!window.universalImagenesStorage) {
            console.warn('[PrendaStorageAdapter] ⚠️ Universal storage no disponible');
            return;
        }
        
        // Limpiar tipo 'prendas' completamente
        window.universalImagenesStorage.limpiarTipo('prendas');
        
        // Agregar todas las imágenes al índice 'general'
        imagenes.forEach((imagen, index) => {
            window.universalImagenesStorage.agregarImagen('prendas', 'general', imagen);
        });
        
        console.log(`[PrendaStorageAdapter]  ${imagenes.length} imágenes de prendas establecidas en storage universal`);
    },
    
    /**
     * Obtener imágenes (compatibilidad con código existente)
     * @returns {Array} Array de imágenes
     */
    obtenerImagenes: function() {
        if (!window.universalImagenesStorage) {
            console.warn('[PrendaStorageAdapter] ⚠️ Universal storage no disponible');
            return [];
        }
        
        const imagenes = window.universalImagenesStorage.obtenerImagenes('prendas', 'general');
        console.log(`[PrendaStorageAdapter] 📸 Obteniendo ${imagenes.length} imágenes de prendas desde storage universal`);
        return imagenes;
    },
    
    /**
     * Agregar una imagen (compatibilidad con código existente)
     * @param {Object|File} imagen - Objeto de imagen o File object
     * @returns {Promise} Promise para compatibilidad con código existente
     */
    agregarImagen: function(imagen) {
        return new Promise((resolve, reject) => {
            if (!window.universalImagenesStorage) {
                console.warn('[PrendaStorageAdapter] ⚠️ Universal storage no disponible');
                reject(new Error('Universal storage no disponible'));
                return;
            }
            
            try {
                // 🔴 CRÍTICO: Si es un File object, construir el objeto completo
                let imagenCompleta;
                if (imagen instanceof File) {
                    imagenCompleta = {
                        file: imagen,
                        previewUrl: URL.createObjectURL(imagen),
                        nombre: imagen.name,
                        tamano: imagen.size,
                        fileType: imagen.type,
                        fileSize: imagen.size,
                        fechaCreacion: new Date().toISOString()
                    };
                    console.log('[PrendaStorageAdapter] 📦 File object convertido a objeto completo:', {
                        nombre: imagenCompleta.nombre,
                        tamano: imagenCompleta.tamano,
                        tienePreviewUrl: !!imagenCompleta.previewUrl
                    });
                } else {
                    // Si ya es un objeto completo, usarlo directamente
                    imagenCompleta = imagen;
                }
                
                const resultado = window.universalImagenesStorage.agregarImagen('prendas', 'general', imagenCompleta);
                if (resultado) {
                    console.log('[PrendaStorageAdapter]  Imagen agregada exitosamente');
                    resolve(resultado);
                } else {
                    reject(new Error('No se pudo agregar la imagen'));
                }
            } catch (error) {
                console.error('[PrendaStorageAdapter]  Error al agregar imagen:', error);
                reject(error);
            }
        });
    },
    
    /**
     * Eliminar una imagen por índice (compatibilidad con código existente)
     * @param {number} index - Índice de la imagen a eliminar
     */
    eliminarImagen: function(index) {
        if (!window.universalImagenesStorage) {
            console.warn('[PrendaStorageAdapter] ⚠️ Universal storage no disponible');
            return false;
        }
        
        return window.universalImagenesStorage.eliminarImagen('prendas', 'general', index);
    },
    
    /**
     * Limpiar todas las imágenes (compatibilidad con código existente)
     */
    limpiar: function() {
        if (!window.universalImagenesStorage) {
            console.warn('[PrendaStorageAdapter] ⚠️ Universal storage no disponible');
            return;
        }
        
        window.universalImagenesStorage.limpiarTipo('prendas');
        console.log('[PrendaStorageAdapter] 🧹 Storage de prendas limpiado');
    },
    
    /**
     * Obtener conteo de imágenes
     * @returns {number} Número de imágenes
     */
    contar: function() {
        return this.obtenerImagenes().length;
    },
    
    /**
     * Verificar si hay imágenes
     * @returns {boolean} True si hay imágenes
     */
    tieneImagenes: function() {
        return this.contar() > 0;
    }
};

/**
 * Adaptador para imágenes de telas usando storage universal
 */
window.imagenesTelaStorage = {
    /**
     * Establecer imágenes de telas
     * @param {Array} imagenes - Array de imágenes
     */
    establecerImagenes: function(imagenes) {
        if (!window.universalImagenesStorage) {
            console.warn('[TelaStorageAdapter] ⚠️ Universal storage no disponible');
            return;
        }
        
        // Limpiar tipo 'telas' completamente
        window.universalImagenesStorage.limpiarTipo('telas');
        
        // Agregar todas las imágenes al índice 'general'
        imagenes.forEach((imagen, index) => {
            window.universalImagenesStorage.agregarImagen('telas', 'general', imagen);
        });
        
        console.log(`[TelaStorageAdapter]  ${imagenes.length} imágenes de telas establecidas en storage universal`);
    },
    
    /**
     * Obtener imágenes de telas
     * @returns {Array} Array de imágenes
     */
    obtenerImagenes: function() {
        if (!window.universalImagenesStorage) {
            console.warn('[TelaStorageAdapter] ⚠️ Universal storage no disponible');
            return [];
        }
        
        const imagenes = window.universalImagenesStorage.obtenerImagenes('telas', 'general');
        console.log(`[TelaStorageAdapter] 📸 Obteniendo ${imagenes.length} imágenes de telas desde storage universal`);
        return imagenes;
    },
    
    /**
     * Agregar una imagen de tela
     * @param {Object|File} imagen - Objeto de imagen o File object
     * @returns {Promise} Promise para compatibilidad con código existente
     */
    agregarImagen: function(imagen) {
        return new Promise((resolve, reject) => {
            if (!window.universalImagenesStorage) {
                console.warn('[TelaStorageAdapter] ⚠️ Universal storage no disponible');
                reject(new Error('Universal storage no disponible'));
                return;
            }
            
            try {
                // 🔴 CRÍTICO: Si es un File object, construir el objeto completo
                let imagenCompleta;
                if (imagen instanceof File) {
                    imagenCompleta = {
                        file: imagen,
                        previewUrl: URL.createObjectURL(imagen),
                        nombre: imagen.name,
                        tamano: imagen.size,
                        fileType: imagen.type,
                        fileSize: imagen.size,
                        fechaCreacion: new Date().toISOString()
                    };
                    console.log('[TelaStorageAdapter] 📦 File object convertido a objeto completo:', {
                        nombre: imagenCompleta.nombre,
                        tamano: imagenCompleta.tamano,
                        tienePreviewUrl: !!imagenCompleta.previewUrl
                    });
                } else {
                    // Si ya es un objeto completo, usarlo directamente
                    imagenCompleta = imagen;
                }
                
                const resultado = window.universalImagenesStorage.agregarImagen('telas', 'general', imagenCompleta);
                if (resultado) {
                    console.log('[TelaStorageAdapter]  Imagen de tela agregada exitosamente');
                    resolve(resultado);
                } else {
                    reject(new Error('No se pudo agregar la imagen de tela'));
                }
            } catch (error) {
                console.error('[TelaStorageAdapter]  Error al agregar imagen de tela:', error);
                reject(error);
            }
        });
    },
    
    /**
     * Eliminar una imagen de tela por índice
     * @param {number} index - Índice de la imagen a eliminar
     */
    eliminarImagen: function(index) {
        if (!window.universalImagenesStorage) {
            console.warn('[TelaStorageAdapter] ⚠️ Universal storage no disponible');
            return false;
        }
        
        return window.universalImagenesStorage.eliminarImagen('telas', 'general', index);
    },
    
    /**
     * Limpiar todas las imágenes de telas
     */
    limpiarImagenes: function() {
        if (!window.universalImagenesStorage) {
            console.warn('[TelaStorageAdapter] ⚠️ Universal storage no disponible');
            return;
        }
        
        window.universalImagenesStorage.limpiarTipo('telas');
        console.log('[TelaStorageAdapter] 🧹 Storage de telas limpiado');
    },
    
    /**
     * Limpiar todas las imágenes de telas (alias para compatibilidad)
     */
    limpiar: function() {
        return this.limpiarImagenes();
    },
    
    /**
     * Obtener conteo de imágenes de telas
     * @returns {number} Número de imágenes
     */
    contar: function() {
        return this.obtenerImagenes().length;
    },
    
    /**
     * Verificar si hay imágenes de telas
     * @returns {boolean} True si hay imágenes
     */
    tieneImagenes: function() {
        return this.contar() > 0;
    }
};

console.log(' [StorageAdapters] Adaptadores de storage inicializados - prendas y telas usando storage universal');
