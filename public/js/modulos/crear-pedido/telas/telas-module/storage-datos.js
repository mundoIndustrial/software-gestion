/**
 * ================================================
 * TELAS MODULE - STORAGE Y DATOS
 * ================================================
 * 
 * Funciones para almacenamiento y obtención de datos de telas
 * Manejo de datos para creación y edición de prendas
 * 
 * @module TelasModule
 * @version 2.0.0
 */

/**
 * Obtener telas para envío (FLUJO CREACIÓN)
 * @returns {Array} Array de telas para enviar
 */
window.obtenerTelasParaEnvio = function() {
    console.log('[obtenerTelasParaEnvio]  Obteniendo telas para envío');
    return window.telasCreacion;
};

/**
 * Obtener telas para edición
 * @returns {Array} Array de telas para edición
 */
window.obtenerTelasParaEdicion = function() {
    console.log('[obtenerTelasParaEdicion]  Obteniendo telas para edición');
    
    // Aquí se puede agregar lógica para obtener telas de diferentes fuentes
    // Por ahora, usamos el mismo array que para creación
    return window.telasCreacion;
};

/**
 * Obtener imágenes de tela para envío
 * @param {number} telaIndex - Índice de la tela
 * @returns {Array} Array de imágenes de la tela
 */
window.obtenerImagenesTelaParaEnvio = function(telaIndex) {
    console.log('[obtenerImagenesTelaParaEnvio] 📸 Obteniendo imágenes de tela para envío');
    
    const telas = window.telasCreacion;
    if (!telas || telaIndex < 0 || telaIndex >= telas.length) {
        console.warn('[obtenerImagenesTelaParaEnvio]  Índice inválido:', telaIndex);
        return [];
    }
    
    const tela = telas[telaIndex];
    return tela.imagenes || [];
};

/**
 * Obtener imágenes temporales de tela
 * @returns {Array} Array de imágenes temporales
 */
window.obtenerImagenesTemporales = function() {
    return window.imagenesTelaModalNueva || [];
};

/**
 * Establecer telas para edición
 * @param {Array} telas - Array de telas a establecer
 */
window.establecerTelasParaEdicion = function(telas) {
    console.log('[establecerTelasParaEdicion]  Estableciendo telas para edición');
    window.telasCreacion = [...telas];
    console.log('[establecerTelasParaEdicion]  Telas establecidas:', window.telasCreacion.length);
};

/**
 * Establecer imágenes temporales de tela
 * @param {Array} imagenes - Array de imágenes temporales
 */
window.establecerImagenesTemporales = function(imagenes) {
    console.log('[establecerImagenesTemporales]  Estableciendo imágenes temporales');
    window.imagenesTelaModalNueva = [...imagenes];
    console.log('[establecerImagenesTemporales]  Imágenes temporales establecidas:', window.imagenesTelaModalNueva.length);
};

/**
 * Limpiar todas las telas (FLUJO CREACIÓN)
 */
window.limpiarTelas = function() {
    console.log('[limpiarTelas] 🧹 Limpiando todas las telas');
    
    window.telasCreacion = [];
    
    if (window.imagenesTelaStorage) {
        window.imagenesTelaStorage.limpiar();
    }
    
    // Limpiar imágenes temporales
    window.imagenesTelaModalNueva = [];
    
    // Actualizar UI
    window.actualizarTablaTelas();
    window.actualizarContadorTelas();
    
    console.log('[limpiarTelas]  Telas limpiadas');
};

/**
 * Limpiar imágenes temporales
 */
window.limpiarImagenesTemporales = function() {
    console.log('[limpiarImagenesTemporales] 🧹 Limpiando imágenes temporales');
    window.imagenesTelaModalNueva = [];
    
    // Actualizar preview si es necesario
    if (typeof window.actualizarPreviewTelaTemporal === 'function') {
        window.actualizarPreviewTelaTemporal();
    }
    
    console.log('[limpiarImagenesTemporales]  Imágenes temporales limpiadas');
};

/**
 * Obtener resumen de telas
 * @returns {Object} Resumen del estado de telas
 */
window.obtenerResumenTelas = function() {
    const telas = window.telasCreacion || [];
    
    const resumen = {
        total: telas.length,
        conImagenes: telas.filter(t => t.imagenes && t.imagenes.length > 0).length,
        sinImagenes: telas.filter(t => !tela.imagenes || t.imagenes.length === 0).length,
        totalImagenes: telas.reduce((total, tela) => total + (tela.imagenes ? tela.imagenes.length : 0), 0),
        colores: [...new Set(telas.map(t => t.color))],
        telas: [...new Set(telas.map(t => t.tela))],
        referencias: telas.filter(t => t.referencia && t.referencia.trim() !== '').map(t => t.referencia)
    };
    
    console.log('[obtenerResumenTelas]  Resumen de telas:', resumen);
    return resumen;
};

/**
 * Validar que haya al menos una tela
 * @returns {boolean} True si hay al menos una tela
 */
window.tieneTelas = function() {
    return window.telasCreacion && window.telaCreacion.length > 0;
};

/**
 * Obtener telas con imágenes
 * @returns {Array} Array de telas que tienen imágenes
 */
window.obtenerTelasConImagenes = function() {
    return window.telasCreacion.filter(tela => t.imagenes && t.imagenes.length > 0);
};

/**
 * Obtener telas sin imágenes
 * @returns {Array} Array de telas sin imágenes
 */
window.obtenerTelasSinImagenes = function() {
    return window.telasCreacion.filter(tela => !tela.imagenes || t.imagenes.length === 0);
};

/**
 * Buscar telas por color
 * @param {string} color - Color a buscar
 * @returns {Array} Array de telas con ese color
 */
window.buscarTelasPorColor = function(color) {
    const telas = window.telasCreacion || [];
    return telas.filter(tela => 
        t.color && t.color.toLowerCase() === color.toLowerCase()
    );
};

/**
 * Buscar telas por nombre
 * @param {string} nombre - Nombre de tela a buscar
 * @returns {Array} Array de telas con ese nombre
 */
window.buscarTelasPorNombre = function(nombre) {
    const telas = window.telasCreacion || [];
    return telas.filter(tela => 
        t.tela && t.tela.toLowerCase().includes(nombre.toLowerCase())
    );
};

/**
 * Exportar datos de telas para diferentes contextos
 * @param {string} contexto - Contexto ('creacion', 'edicion', 'resumen')
 * @returns {Array|Object} Datos exportados
 */
window.exportarDatosTelas = function(contexto = 'creacion') {
    switch (contexto) {
        case 'creacion':
            return window.obtenerTelasParaEnvio();
        case 'edicion':
            return window.obtenerTelasParaEdicion();
        case 'resumen':
            return window.obtenerResumenTelas();
        default:
            return window.obtenerTelasParaEnvio();
    }
};

/**
 * Importar datos de telas
 * @param {Array} telas - Array de telas a importar
 */
window.importarDatosTelas = function(telas) {
    console.log('[importarDatosTelas] 📥 Importando datos de telas');
    window.telasCreacion = [...telas];
    console.log('[importarDatosTelas]  ' + telas.length + ' telas importadas');
};

/**
 * Serializar datos de telas para almacenamiento
 * @returns {string} Datos serializados
 */
window.serializarDatosTelas = function() {
    const datos = {
        telasCreacion: window.telasCreacion || [],
        imagenesTelaModalNueva: window.imagenesTelaModalNueva || [],
        timestamp: new Date().toISOString(),
        version: '2.0.0'
    };
    
    return JSON.stringify(datos);
};

/**
 * Restaurar datos de telas desde almacenamiento
 * @param {string} datosSerializados - Datos serializados
 */
window.restaurarDatosTelas = function(datosSerializados) {
    try {
        const datos = JSON.parse(datosSerializados);
        
        window.telasCreacion = datos.telasCreacion || [];
        window.imagenesTelaModalNueva = datos.imagenesTelaModalNueva || [];
        
        console.log('[restaurarDatosTelas]  Datos de telas restaurados');
        console.log('[restaurarDatosTelas]  ' + window.telasCreacion.length + ' telas restauradas');
        
        // Actualizar UI
        window.actualizarTablaTelas();
        window.actualizarContadorTelas();
        
        return true;
    } catch (error) {
        console.error('[restaurarDatosTelas]  Error al restaurar datos:', error);
        return false;
    }
}
