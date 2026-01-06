/**
 * GESTOR DE PRENDAS - Crear Pedido Editable (FASE 2)
 * 
 * Centraliza toda la lógica de prendas
 * Responsabilidades:
 * - Almacenar prendas cargadas
 * - Renderizar prendas con edición
 * - Gestionar adición/eliminación de prendas
 * - Gestionar telas y variaciones de prendas
 */

class GestorPrendas {
    /**
     * Constructor
     * @param {Array} prendas - Array de prendas iniciales
     * @param {string} containerId - ID del contenedor de prendas
     */
    constructor(prendas = [], containerId = 'prendas-container-editable') {
        this.prendas = [...prendas];
        this.prendasEliminadas = new Set();
        this.containerId = containerId;
        this.fotosNuevas = {};
        this.telasFotosNuevas = {};
    }

    /**
     * Obtener todas las prendas
     * @returns {Array} Array de prendas
     */
    obtenerTodas() {
        return this.prendas;
    }

    /**
     * Obtener prenda por índice
     * @param {number} index - Índice de la prenda
     * @returns {Object} Prenda encontrada
     */
    obtenerPorIndice(index) {
        return this.prendas[index] || null;
    }

    /**
     * Agregar prenda
     * @param {Object} prenda - Objeto de prenda
     */
    agregar(prenda) {
        this.prendas.push(prenda);
        logWithEmoji('➕', `Prenda agregada: ${prenda.nombre_producto}`);
    }

    /**
     * Eliminar prenda por índice
     * @param {number} index - Índice de la prenda
     */
    eliminar(index) {
        if (index >= 0 && index < this.prendas.length) {
            this.prendasEliminadas.add(index);
            logWithEmoji('🗑️', `Prenda ${index + 1} marcada para eliminación`);
        }
    }

    /**
     * Restaurar prenda
     * @param {number} index - Índice de la prenda
     */
    restaurar(index) {
        this.prendasEliminadas.delete(index);
        logWithEmoji('↩️', `Prenda ${index + 1} restaurada`);
    }

    /**
     * Obtener prendas activas (no eliminadas)
     * @returns {Array} Prendas activas
     */
    obtenerActivas() {
        return this.prendas.filter((_, index) => !this.prendasEliminadas.has(index));
    }

    /**
     * Obtener prendas eliminadas
     * @returns {Array} Prendas marcadas para eliminación
     */
    obtenerEliminadas() {
        return Array.from(this.prendasEliminadas);
    }

    /**
     * Cantidad de prendas activas
     * @returns {number} Cantidad
     */
    cantidad() {
        return this.obtenerActivas().length;
    }

    /**
     * Limpiar todas las prendas
     */
    limpiar() {
        this.prendas = [];
        this.prendasEliminadas.clear();
        this.fotosNuevas = {};
        this.telasFotosNuevas = {};
        logWithEmoji('🗑️', 'Todas las prendas han sido limpiadas');
    }

    /**
     * Agregar fotos a una prenda
     * @param {number} prendaIndex - Índice de la prenda
     * @param {Array} fotos - Fotos a agregar
     */
    agregarFotos(prendaIndex, fotos) {
        if (!this.fotosNuevas[prendaIndex]) {
            this.fotosNuevas[prendaIndex] = [];
        }
        this.fotosNuevas[prendaIndex] = [...this.fotosNuevas[prendaIndex], ...fotos];
        logWithEmoji('📸', `${fotos.length} foto(s) agregada(s) a prenda ${prendaIndex + 1}`);
    }

    /**
     * Obtener fotos nuevas de una prenda
     * @param {number} prendaIndex - Índice de la prenda
     * @returns {Array} Fotos nuevas
     */
    obtenerFotosNuevas(prendaIndex) {
        return this.fotosNuevas[prendaIndex] || [];
    }

    /**
     * Eliminar foto de una prenda
     * @param {number} prendaIndex - Índice de la prenda
     * @param {number} fotoIndex - Índice de la foto
     */
    eliminarFoto(prendaIndex, fotoIndex) {
        if (this.fotosNuevas[prendaIndex]) {
            this.fotosNuevas[prendaIndex].splice(fotoIndex, 1);
            logWithEmoji('🗑️', `Foto eliminada de prenda ${prendaIndex + 1}`);
        }
    }

    /**
     * Agregar fotos a una tela
     * @param {number} prendaIndex - Índice de la prenda
     * @param {number} telaIndex - Índice de la tela
     * @param {Array} fotos - Fotos a agregar
     */
    agregarFotosTela(prendaIndex, telaIndex, fotos) {
        if (!this.telasFotosNuevas[prendaIndex]) {
            this.telasFotosNuevas[prendaIndex] = {};
        }
        if (!this.telasFotosNuevas[prendaIndex][telaIndex]) {
            this.telasFotosNuevas[prendaIndex][telaIndex] = [];
        }
        this.telasFotosNuevas[prendaIndex][telaIndex] = [
            ...this.telasFotosNuevas[prendaIndex][telaIndex],
            ...fotos
        ];
        logWithEmoji('📸', `${fotos.length} foto(s) de tela agregada(s)`);
    }

    /**
     * Obtener fotos nuevas de una tela
     * @param {number} prendaIndex - Índice de la prenda
     * @param {number} telaIndex - Índice de la tela
     * @returns {Array} Fotos nuevas
     */
    obtenerFotosNuevasTela(prendaIndex, telaIndex) {
        return this.telasFotosNuevas[prendaIndex]?.[telaIndex] || [];
    }

    /**
     * Agregar tela a una prenda
     * @param {number} prendaIndex - Índice de la prenda
     * @param {Object} tela - Objeto de tela
     */
    agregarTela(prendaIndex, tela = {}) {
        const prenda = this.obtenerPorIndice(prendaIndex);
        if (!prenda) return;

        if (!prenda.variantes) prenda.variantes = {};
        if (!Array.isArray(prenda.variantes.telas_multiples)) {
            prenda.variantes.telas_multiples = [];
        }
        if (!Array.isArray(prenda.telas)) {
            prenda.telas = [];
        }

        prenda.variantes.telas_multiples.push({
            nombre_tela: tela.nombre_tela || '',
            color: tela.color || '',
            referencia: tela.referencia || ''
        });

        prenda.telas.push({
            id: tela.id || null,
            nombre_tela: tela.nombre_tela || '',
            color: tela.color || '',
            referencia: tela.referencia || ''
        });

        logWithEmoji('➕', `Tela agregada a prenda ${prendaIndex + 1}`);
    }

    /**
     * Eliminar tela de una prenda
     * @param {number} prendaIndex - Índice de la prenda
     * @param {number} telaIndex - Índice de la tela
     */
    eliminarTela(prendaIndex, telaIndex) {
        const prenda = this.obtenerPorIndice(prendaIndex);
        if (!prenda) return;

        if (Array.isArray(prenda.variantes?.telas_multiples)) {
            prenda.variantes.telas_multiples.splice(telaIndex, 1);
        }
        if (Array.isArray(prenda.telas)) {
            prenda.telas.splice(telaIndex, 1);
        }
        if (this.telasFotosNuevas[prendaIndex]) {
            delete this.telasFotosNuevas[prendaIndex][telaIndex];
        }

        logWithEmoji('🗑️', `Tela ${telaIndex + 1} eliminada de prenda ${prendaIndex + 1}`);
    }

    /**
     * Agregar talla a una prenda
     * @param {number} prendaIndex - Índice de la prenda
     * @param {string} talla - Talla a agregar
     */
    agregarTalla(prendaIndex, talla) {
        const prenda = this.obtenerPorIndice(prendaIndex);
        if (!prenda) return;

        if (!Array.isArray(prenda.tallas)) {
            prenda.tallas = [];
        }

        if (!prenda.tallas.includes(talla)) {
            prenda.tallas.push(talla);
            logWithEmoji('➕', `Talla ${talla} agregada a prenda ${prendaIndex + 1}`);
        }
    }

    /**
     * Eliminar talla de una prenda
     * @param {number} prendaIndex - Índice de la prenda
     * @param {string} talla - Talla a eliminar
     */
    eliminarTalla(prendaIndex, talla) {
        const prenda = this.obtenerPorIndice(prendaIndex);
        if (!prenda) return;

        if (Array.isArray(prenda.tallas)) {
            prenda.tallas = prenda.tallas.filter(t => t !== talla);
            logWithEmoji('🗑️', `Talla ${talla} eliminada de prenda ${prendaIndex + 1}`);
        }
    }

    /**
     * Obtener todas las tallas disponibles en todas las prendas
     * @returns {Array} Tallas únicas
     */
    obtenerTodasLasTallas() {
        const tallas = [];
        this.obtenerActivas().forEach(prenda => {
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

    /**
     * Actualizar nombre de prenda
     * @param {number} prendaIndex - Índice de la prenda
     * @param {string} nuevoNombre - Nuevo nombre
     */
    actualizarNombre(prendaIndex, nuevoNombre) {
        const prenda = this.obtenerPorIndice(prendaIndex);
        if (prenda) {
            prenda.nombre_producto = nuevoNombre;
        }
    }

    /**
     * Obtener datos formateados para envío
     * @returns {Object} Datos formateados
     */
    obtenerDatosFormato() {
        return {
            prendas: this.obtenerActivas(),
            fotosNuevas: this.fotosNuevas,
            telasFotosNuevas: this.telasFotosNuevas,
            prendasEliminadas: this.obtenerEliminadas()
        };
    }

    /**
     * Validar que todas las prendas tengan datos mínimos
     * @returns {Object} {valido: boolean, errores: Array}
     */
    validar() {
        const errores = [];
        
        this.obtenerActivas().forEach((prenda, index) => {
            if (!prenda.nombre_producto || prenda.nombre_producto.trim() === '') {
                errores.push(`Prenda ${index + 1}: Falta nombre del producto`);
            }
            if (!prenda.tallas || prenda.tallas.length === 0) {
                errores.push(`Prenda ${index + 1}: Debe tener al menos una talla`);
            }
        });

        return {
            valido: errores.length === 0,
            errores
        };
    }
}

/**
 * INSTANCIA GLOBAL
 */
window.gestorPrendas = null;

// Exportar para ES6 modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { GestorPrendas };
}
