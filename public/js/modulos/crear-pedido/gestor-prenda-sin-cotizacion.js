/**
 * GESTOR DE PRENDA SIN COTIZACIÓN - Nueva Prenda Tipo PRENDA
 * 
 * Este módulo maneja toda la lógica de renderización y gestión de prendas
 * cuando el usuario selecciona "Nuevo Pedido" > "PRENDA" sin cotización previa.
 * 
 * Renderiza TODOS los campos que se muestran en una cotización combinada 
 * con tipo prenda, incluyendo:
 * - Información básica (nombre, descripción)
 * - Tallas y cantidades
 * - Variaciones (manga, broche, bolsillos, reflectivo)
 * - Telas/colores múltiples
 * - Fotos de prenda
 * - Fotos de tela
 * 
 * RESPONSABILIDADES:
 * - Agregar prendas de tipo PRENDA
 * - Renderizar formularios completos
 * - Gestionar fotos de prenda y tela
 * - Gestionar tallas y variaciones
 * - Gestionar telas con colores y referencias
 */

class GestorPrendaSinCotizacion {
    /**
     * Constructor
     * @param {string} containerId - ID del contenedor donde renderizar
     */
    constructor(containerId = 'prendas-container-editable') {
        this.prendas = [];
        this.containerId = containerId;
        this.prendasEliminadas = new Set();
        this.fotosNuevas = {};
        this.telasFotosNuevas = {};
        this.tipoPedidoActual = null;
        
        logWithEmoji('✅', 'GestorPrendaSinCotizacion inicializado');
    }

    /**
     * Crear una prenda base de tipo PRENDA con estructura completa
     * @returns {Object} Prenda inicializada
     */
    crearPrendaBase() {
        return {
            nombre_producto: '',
            descripcion: '',
            genero: '',
            tipo_manga: 'No aplica',
            obs_manga: '',
            tipo_broche: 'No aplica',
            obs_broche: '',
            tiene_bolsillos: false,
            obs_bolsillos: '',
            tiene_reflectivo: false,
            obs_reflectivo: '',
            tallas: [],
            cantidadesPorTalla: {},
            variantes: {
                tipo_manga: 'No aplica',
                obs_manga: '',
                tipo_broche: 'No aplica',
                obs_broche: '',
                tiene_bolsillos: false,
                obs_bolsillos: '',
                tiene_reflectivo: false,
                obs_reflectivo: '',
                telas_multiples: []
            },
            telas: [],
            fotos: [],
            telaFotos: []
        };
    }

    /**
     * Agregar una nueva prenda de tipo PRENDA
     * @returns {number} Índice de la prenda agregada
     */
    agregarPrenda() {
        const nuevaPrenda = this.crearPrendaBase();
        this.prendas.push(nuevaPrenda);
        const index = this.prendas.length - 1;
        logWithEmoji('➕', `Prenda PRENDA agregada (índice: ${index})`);
        return index;
    }

    /**
     * Obtener todas las prendas activas
     * @returns {Array} Prendas no eliminadas
     */
    obtenerActivas() {
        return this.prendas.filter((_, index) => !this.prendasEliminadas.has(index));
    }

    /**
     * Obtener prenda por índice
     * @param {number} index - Índice de la prenda
     * @returns {Object|null} Prenda encontrada
     */
    obtenerPorIndice(index) {
        return this.prendas[index] || null;
    }

    /**
     * Eliminar prenda (marcar para eliminación)
     * @param {number} index - Índice de la prenda
     */
    eliminar(index) {
        this.prendasEliminadas.add(index);
        logWithEmoji('🗑️', `Prenda ${index + 1} marcada para eliminación`);
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
            // Inicializar cantidad en 0 para esta talla
            if (!prenda.cantidadesPorTalla) {
                prenda.cantidadesPorTalla = {};
            }
            prenda.cantidadesPorTalla[talla] = 0;
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
            const idx = prenda.tallas.indexOf(talla);
            if (idx >= 0) {
                prenda.tallas.splice(idx, 1);
                if (prenda.cantidadesPorTalla) {
                    delete prenda.cantidadesPorTalla[talla];
                }
                logWithEmoji('🗑️', `Talla ${talla} eliminada de prenda ${prendaIndex + 1}`);
            }
        }
    }

    /**
     * Actualizar cantidad para una talla
     * @param {number} prendaIndex - Índice de la prenda
     * @param {string} talla - Talla
     * @param {number} cantidad - Nueva cantidad
     */
    actualizarCantidadTalla(prendaIndex, talla, cantidad) {
        const prenda = this.obtenerPorIndice(prendaIndex);
        if (!prenda) return;

        if (!prenda.cantidadesPorTalla) {
            prenda.cantidadesPorTalla = {};
        }
        prenda.cantidadesPorTalla[talla] = parseInt(cantidad) || 0;
    }

    /**
     * Agregar tela a una prenda
     * @param {number} prendaIndex - Índice de la prenda
     * @param {Object} tela - Objeto con propiedades de tela
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

        const nuevaTela = {
            nombre_tela: tela.nombre_tela || '',
            color: tela.color || '',
            referencia: tela.referencia || ''
        };

        prenda.variantes.telas_multiples.push(nuevaTela);
        prenda.telas.push(nuevaTela);

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
     * Actualizar variación de prenda
     * @param {number} prendaIndex - Índice de la prenda
     * @param {string} campoVariacion - Campo de la variación (ej: tipo_manga)
     * @param {any} valor - Nuevo valor
     */
    actualizarVariacion(prendaIndex, campoVariacion, valor) {
        const prenda = this.obtenerPorIndice(prendaIndex);
        if (!prenda) return;

        if (!prenda.variantes) prenda.variantes = {};
        prenda.variantes[campoVariacion] = valor;
        prenda[campoVariacion] = valor;

        logWithEmoji('✏️', `Variación ${campoVariacion} actualizada`);
    }

    /**
     * Agregar fotos a una prenda
     * @param {number} prendaIndex - Índice de la prenda
     * @param {Array} fotos - Array de fotos
     */
    agregarFotos(prendaIndex, fotos) {
        if (!this.fotosNuevas[prendaIndex]) {
            this.fotosNuevas[prendaIndex] = [];
        }
        this.fotosNuevas[prendaIndex] = [...this.fotosNuevas[prendaIndex], ...fotos];
        logWithEmoji('📸', `${fotos.length} foto(s) agregada(s) a prenda ${prendaIndex + 1}`);
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
     * @param {Array} fotos - Array de fotos
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
     * Obtener fotos nuevas de una prenda
     * @param {number} prendaIndex - Índice de la prenda
     * @returns {Array} Fotos nuevas
     */
    obtenerFotosNuevas(prendaIndex) {
        return this.fotosNuevas[prendaIndex] || [];
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
     * Validar que la prenda tenga datos mínimos
     * @param {number} index - Índice de la prenda
     * @returns {Object} {valido: boolean, errores: Array}
     */
    validar(index) {
        const prenda = this.obtenerPorIndice(index);
        const errores = [];

        if (!prenda) {
            errores.push('Prenda no encontrada');
        } else {
            if (!prenda.nombre_producto || prenda.nombre_producto.trim() === '') {
                errores.push(`Prenda ${index + 1}: Falta nombre del producto`);
            }
            if (!prenda.tallas || prenda.tallas.length === 0) {
                errores.push(`Prenda ${index + 1}: Debe tener al menos una talla`);
            }
        }

        return {
            valido: errores.length === 0,
            errores
        };
    }

    /**
     * Obtener datos para envío
     * @returns {Object} Datos formateados
     */
    obtenerDatosFormato() {
        return {
            prendas: this.obtenerActivas(),
            fotosNuevas: this.fotosNuevas,
            telasFotosNuevas: this.telasFotosNuevas,
            prendasEliminadas: Array.from(this.prendasEliminadas)
        };
    }

    /**
     * Limpiar todos los datos
     */
    limpiar() {
        this.prendas = [];
        this.prendasEliminadas.clear();
        this.fotosNuevas = {};
        this.telasFotosNuevas = {};
        logWithEmoji('🗑️', 'GestorPrendaSinCotizacion limpiado');
    }
}

// Instancia global
window.gestorPrendaSinCotizacion = null;

// Exportar para ES6 modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { GestorPrendaSinCotizacion };
}
