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
        

    }

    /**
     * Establecer la prenda actual para ser agregada
     * @param {Object} prenda - Datos de la prenda
     */
    setPrendaActual(prenda) {
        this.prendaActual = prenda;
        console.log('[GestorPrendaSinCotizacion] 📋 Prenda actual establecida:', prenda.nombre_prenda || prenda.nombre);
    }

    /**
     * Crear una prenda base de tipo PRENDA con estructura completa
     * @returns {Object} Prenda inicializada
     */
    crearPrendaBase() {
        return {
            nombre_producto: '',
            descripcion: '',
            genero: [],
            generosConTallas: {}, 
            tipo_manga: 'No aplica',
            obs_manga: '',
            tipo_broche: 'No aplica',
            obs_broche: '',
            tiene_bolsillos: false,
            obs_bolsillos: '',
            tiene_reflectivo: false,
            obs_reflectivo: '',
            tallas: [],
            cantidadesPorTalla: {}, //  DEPRECATED pero mantenido para compatibilidad
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
            telasAgregadas: [],
            fotos: [],
            telaFotos: [],
            imagenes: [],
            origen: 'bodega', 
            de_bodega: 1,
            procesos: {},
            variaciones: {}
        };
    }

    /**
     * Agregar una nueva prenda de tipo PRENDA
     * @param {Object} datosOpcionales - Datos opcionales para inicializar la prenda
     * @returns {number} Índice de la prenda agregada
     */
    agregarPrenda(datosOpcionales = {}) {
        let nuevaPrenda;
        
        // Si hay una prenda actual establecida, usarla como base
        if (this.prendaActual) {
            console.log('[GestorPrendaSinCotizacion] 📦 Usando prenda actual como base:', this.prendaActual.nombre_prenda || this.prendaActual.nombre);
            nuevaPrenda = { ...this.prendaActual, ...datosOpcionales };
            // Limpiar prenda actual después de usarla
            this.prendaActual = null;
        } else {
            // Si no, crear una nueva desde cero
            console.log('[GestorPrendaSinCotizacion] 🆕 Creando prenda desde cero');
            nuevaPrenda = { ...this.crearPrendaBase(), ...datosOpcionales };
        }
        
        // Merge profundo de variantes si viene en datosOpcionales
        if (datosOpcionales.variantes && typeof datosOpcionales.variantes === 'object') {
            nuevaPrenda.variantes = {
                ...nuevaPrenda.variantes,
                ...datosOpcionales.variantes
            };
        }
        
        this.prendas.push(nuevaPrenda);
        const index = this.prendas.length - 1;
     





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
        const prenda = this.prendas[index] || null;
        return prenda;
    }

    /**
     * Eliminar prenda (marcar para eliminación)
     * @param {number} index - Índice de la prenda
     */
    eliminar(index) {
        this.prendasEliminadas.add(index);

    }

    /**
     *  NUEVO: Actualizar una prenda existente
     * Reemplaza los datos de una prenda en el gestor
     * @param {number} index - Índice de la prenda a actualizar
     * @param {Object} prendaActualizada - Objeto con los nuevos datos de la prenda
     */
    actualizarPrenda(index, prendaActualizada) {
        if (index < 0 || index >= this.prendas.length) {

            return false;
        }

        // Obtener la prenda actual
        const prendaActual = this.prendas[index];
        if (!prendaActual) {

            return false;
        }

        // Fusionar los datos: mantener propiedades existentes, actualizar con los nuevos
        this.prendas[index] = {
            ...prendaActual,
            ...prendaActualizada,
            id: prendaActual.id // Asegurar que se mantiene el ID original
        };

        // Si la prenda estaba marcada para eliminación, desmarcarla
        if (this.prendasEliminadas.has(index)) {
            this.prendasEliminadas.delete(index);
        }




        return true;
    }

    /**
     * Agregar talla a una prenda
     * @param {number} prendaIndex - Índice de la prenda
     * @param {string} talla - Talla a agregar (puede ser "dama-S", "caballero-L", etc.)
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

            //  NUEVO: También inicializar en generosConTallas
            if (!prenda.generosConTallas) {
                prenda.generosConTallas = {};
            }

            // Parsearlo como "genero-talla"
            let genero = 'dama';
            let tallaPura = talla;

            if (talla.includes('-')) {
                [genero, tallaPura] = talla.split('-', 2);
            }

            if (!prenda.generosConTallas[genero]) {
                prenda.generosConTallas[genero] = {};
            }

            prenda.generosConTallas[genero][tallaPura] = 0;


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

            }
        }
    }

    /**
     * Actualizar cantidad para una talla
     * @param {number} prendaIndex - Índice de la prenda
     * @param {string} talla - Talla (puede ser "dama-S", "caballero-L", etc.)
     * @param {number} cantidad - Nueva cantidad
     */
    actualizarCantidadTalla(prendaIndex, talla, cantidad) {
        const prenda = this.obtenerPorIndice(prendaIndex);
        if (!prenda) return;

        if (!prenda.cantidadesPorTalla) {
            prenda.cantidadesPorTalla = {};
        }
        prenda.cantidadesPorTalla[talla] = parseInt(cantidad) || 0;

        //  NUEVO: También actualizar generosConTallas con estructura {genero: {talla: cantidad}}
        if (!prenda.generosConTallas) {
            prenda.generosConTallas = {};
        }

        // Parsearlo como "genero-talla" o si ya vienen separados
        let genero = '';
        let tallaPura = talla;

        if (talla.includes('-')) {
            [genero, tallaPura] = talla.split('-', 2);
        } else {
            // Si no tiene guión, asumir que es para el género actual o todos
            genero = prenda.genero && Array.isArray(prenda.genero) && prenda.genero.length > 0 
                ? prenda.genero[0] 
                : 'dama';
        }

        if (!prenda.generosConTallas[genero]) {
            prenda.generosConTallas[genero] = {};
        }

        prenda.generosConTallas[genero][tallaPura] = parseInt(cantidad) || 0;


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


    }

    /**
     * Actualizar origen de prenda
     * @param {number} prendaIndex - Índice de la prenda
     * @param {string} origen - 'bodega' o 'confeccion'
     */
    actualizarOrigen(prendaIndex, origen) {
        const prenda = this.obtenerPorIndice(prendaIndex);
        if (!prenda) return;

        prenda.origen = origen;
        // Mapear origen a de_bodega: bodega=1, confeccion=0
        prenda.de_bodega = origen === 'bodega' ? 1 : 0;

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
    }

    /**
     * Eliminar foto de una prenda
     * @param {number} prendaIndex - Índice de la prenda
     * @param {number} fotoIndex - Índice de la foto
     */
    eliminarFoto(prendaIndex, fotoIndex) {
        if (this.fotosNuevas[prendaIndex]) {
            this.fotosNuevas[prendaIndex].splice(fotoIndex, 1);
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
     * Obtener imágenes finales para guardar (excluyendo eliminadas)
     * @param {number} prendaIndex - Índice de la prenda
     * @returns {Array} Imágenes finales para el pedido
     */
    obtenerImagenesFinales(prendaIndex) {
        const prenda = this.obtenerPorIndice(prendaIndex);
        if (!prenda || !prenda.imagenes) {
            return [];
        }

        // Si hay imágenes marcadas para eliminación del pedido, filtrarlas
        if (prenda.imagenesEliminadasDelPedido && prenda.imagenesEliminadasDelPedido.length > 0) {
            const indicesEliminados = new Set(prenda.imagenesEliminadasDelPedido.map(img => img.indiceOriginal));
            
            console.log('[GestorPrenda] Filtrando imágenes eliminadas del pedido:', {
                totalOriginal: prenda.imagenes.length,
                eliminadas: indicesEliminados.size,
                prendaIndex
            });

            return prenda.imagenes.filter((img, index) => !indicesEliminados.has(index));
        }

        return prenda.imagenes;
    }

    /**
     * Obtener datos para envío
     * @returns {Object} Datos formateados
     */
    obtenerDatosFormato() {

        const prendas = this.obtenerActivas();
        
        //  NUEVO: Incluir información de imágenes eliminadas de cotizaciones
        const imagenesEliminadasInfo = {};
        prendas.forEach((prenda, idx) => {
            if (prenda.imagenesEliminadasDelPedido && prenda.imagenesEliminadasDelPedido.length > 0) {
                imagenesEliminadasInfo[idx] = {
                    cotizacion_id: prenda.cotizacion_id,
                    imagenesEliminadas: prenda.imagenesEliminadasDelPedido,
                    esCotizacion: !!(prenda.cotizacion_id || prenda.tipo === 'cotizacion')
                };
            }
        });

        console.log('[GestorPrenda] 📊 Datos formateados con imágenes eliminadas:', {
            totalPrendas: prendas.length,
            conImagenesEliminadas: Object.keys(imagenesEliminadasInfo).length,
            imagenesEliminadasInfo
        });

        const datosFormato = {
            prendas: prendas,
            fotosNuevas: this.fotosNuevas,
            telasFotosNuevas: this.telasFotosNuevas,
            prendasEliminadas: Array.from(this.prendasEliminadas),
            //  NUEVO: Información de imágenes eliminadas de cotizaciones
            imagenesEliminadasDeCotizaciones: imagenesEliminadasInfo
        };
        
        return datosFormato;
    }

    /**
     * Obtener todas las prendas
     * @returns {Array} Array de prendas
     */
    obtenerTodas() {
        return this.prendas;
    }

    /**
     * Limpiar todos los datos
     */
    limpiar() {
        this.prendas = [];
        this.prendasEliminadas.clear();
        this.fotosNuevas = {};
        this.telasFotosNuevas = {};

    }
}

// Exportar a window para uso global
window.GestorPrendaSinCotizacion = GestorPrendaSinCotizacion;
console.log('[gestor-prenda-sin-cotizacion.js] ✅ Clase exportada a window.GestorPrendaSinCotizacion');

// Instancia global
window.gestorPrendaSinCotizacion = null;

// Exportar para ES6 modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { GestorPrendaSinCotizacion };
}
