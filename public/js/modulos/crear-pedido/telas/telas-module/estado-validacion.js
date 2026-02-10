/**
 * ================================================
 * TELAS MODULE - ESTADO Y VALIDACIÓN
 * ================================================
 * 
 * Manejo de estado global, validaciones de campos y errores
 * Sistema centralizado para la gestión de telas
 * 
 * @module TelasModule
 * @version 2.0.0
 */

// ========== ESTADO GLOBAL DE TELAS ==========
// FLUJO CREACIÓN: Prendas nuevas (NO se afecta por edición)
window.telasCreacion = [];
// FLUJO EDICIÓN: Prendas existentes (en modal-novedad-edicion.js)
window.imagenesTelaModalNueva = [];

/**
 * Función para limpiar errores en campos de tela
 * @param {HTMLElement} campo - Campo de tela a limpiar
 */
window.limpiarErrorTela = function(campo) {
    if (campo && campo.classList.contains('campo-error-tela')) {
        campo.classList.remove('campo-error-tela');
        campo.style.borderColor = '';
        campo.style.backgroundColor = '';
        const mensajeError = campo.nextElementSibling;
        if (mensajeError && mensajeError.classList.contains('error-mensaje-tela')) {
            mensajeError.remove();
        }
    }
};

/**
 * Agregar event listeners a los campos de tela cuando estén listos
 */
window.inicializarEventosTela = function() {
    const campos = ['nueva-prenda-color', 'nueva-prenda-tela', 'nueva-prenda-referencia'];
    campos.forEach(id => {
        const campo = document.getElementById(id);
        if (campo) {
            campo.addEventListener('input', function() {
                window.limpiarErrorTela(this);
            });
            campo.addEventListener('focus', function() {
                window.limpiarErrorTela(this);
            });
        }
    });
};

/**
 * Validar campos de tela
 * @param {string} color - Color de la tela
 * @param {string} tela - Nombre de la tela
 * @param {string} referencia - Referencia de la tela
 * @returns {Object} Resultado de la validación
 */
window.validarCamposTela = function(color, tela, referencia) {
    const errores = [];
    
    // Validar color (opcional)
    // El color puede estar vacío
    
    // Validar tela
    if (!tela || tela.trim() === '') {
        errores.push({
            campo: 'nueva-prenda-tela',
            mensaje: 'La tela es requerida'
        });
    }
    
    // Validar referencia (opcional)
    if (referencia && referencia.trim() === '') {
        errores.push({
            campo: 'nueva-prenda-referencia',
            mensaje: 'La referencia no puede estar vacía si se proporciona'
        });
    }
    
    return {
        valido: errores.length === 0,
        errores: errores
    };
};

/**
 * Mostrar error en campo de tela
 * @param {string} campoId - ID del campo
 * @param {string} mensaje - Mensaje de error
 */
window.mostrarErrorTela = function(campoId, mensaje) {
    const campo = document.getElementById(campoId);
    if (!campo) return;
    
    // Limpiar error anterior
    window.limpiarErrorTela(campo);
    
    // Agregar clases de error
    campo.classList.add('campo-error-tela');
    campo.style.borderColor = '#ef4444';
    campo.style.backgroundColor = '#fef2f2';
    
    // Crear mensaje de error
    const mensajeError = document.createElement('div');
    mensajeError.className = 'error-mensaje-tela';
    mensajeError.style.cssText = 'color: #ef4444; font-size: 0.75rem; margin-top: 4px;';
    mensajeError.textContent = mensaje;
    
    // Insertar mensaje después del campo
    campo.parentNode.insertBefore(mensajeError, campo.nextSibling);
};

/**
 * Limpiar todos los errores de tela
 */
window.limpiarTodosLosErroresTela = function() {
    const campos = ['nueva-prenda-color', 'nueva-prenda-tela', 'nueva-prenda-referencia'];
    campos.forEach(id => {
        const campo = document.getElementById(id);
        if (campo) {
            window.limpiarErrorTela(campo);
        }
    });
};

/**
 * Obtener estado actual de las telas
 * @returns {Object} Estado del sistema de telas
 */
window.obtenerEstadoTelas = function() {
    return {
        telasCreacion: window.telasCreacion || [],
        imagenesTelaModalNueva: window.imagenesTelaModalNueva || [],
        totalTelas: (window.telasCreacion || []).length,
        totalImagenes: (window.imagenesTelaModalNueva || []).length
    };
};

/**
 * Resetear estado de las telas
 */
window.resetearEstadoTelas = function() {
    window.telasCreacion = [];
    window.imagenesTelaModalNueva = [];
    console.log('[resetearEstadoTelas] 🔄 Estado de telas reseteado');
};

// Inicializar cuando el DOM esté listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', window.inicializarEventosTela);
} else {
    // El DOM ya está cargado
    window.inicializarEventosTela();
}
