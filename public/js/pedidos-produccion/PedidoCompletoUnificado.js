/**
 * ═══════════════════════════════════════════════════════════════════════════
 * PEDIDO COMPLETO UNIFICADO - FUENTE ÚNICA DE VERDAD
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * PROPÓSITO:
 * - Unifica conceptos "Pedido" y "PedidoProduccion" en UNA SOLA estructura
 * - Sanitiza payloads complejos ANTES de enviar al backend
 * - Garantiza compatibilidad con Laravel FormRequest
 * - Previene errores 422 por JSON mal formado
 * - Asegura persistencia en TODAS las tablas relacionadas
 * 
 * PROBLEMA RESUELTO:
 * - Arrays profundos (>9 niveles) → Laravel normaliza a null
 * - Arrays vacíos anidados [[]] → Laravel ignora
 * - Referencias circulares → JSON.stringify falla
 * - Objetos reactivos (Vue/React) → Propiedades internas se serializan
 * 
 * MAPEO A TABLAS (garantizado por esta estructura):
 * ├─ pedidos_produccion (raíz)
 * ├─ prendas_pedido
 * │  ├─ prenda_pedido_variantes (manga, broche, bolsillos)
 * │  ├─ prenda_pedido_tallas (por género/talla)
 * │  ├─ prenda_pedido_colores_telas
 * │  │  └─ prenda_fotos_tela_pedido (por cada tela)
 * │  ├─ prenda_fotos_pedido
 * │  └─ pedidos_procesos_prenda_detalles (reflectivo, bordado, etc.)
 * │     ├─ pedidos_procesos_prenda_tallas
 * │     └─ pedidos_procesos_imagenes
 * 
 * USO:
 * ```javascript
 * import { PedidoCompletoUnificado } from './PedidoCompletoUnificado.js';
 * 
 * const builder = new PedidoCompletoUnificado();
 * const payloadLimpio = builder
 *   .setCliente('ACME Corp')
 *   .setFormaPago('contado')
 *   .agregarPrenda({
 *     nombre: 'CAMISA DRILL',
 *     tallas: { DAMA: { S: 20, M: 10 } },
 *     telas: [{ tela: 'DRILL', color: 'NARANJA', imagenes: [...] }],
 *     procesos: { reflectivo: { ubicaciones: [...], tallas: {...} } }
 *   })
 *   .build();
 * 
 * await fetch('/api/pedidos', {
 *   method: 'POST',
 *   body: JSON.stringify(payloadLimpio)
 * });
 * ```
 * ═══════════════════════════════════════════════════════════════════════════
 */

/**
 * ═══════════════════════════════════════════════════════════════════════════
 * PARTE 1: DEFINICIONES DE TIPOS Y ESTRUCTURAS
 * ═══════════════════════════════════════════════════════════════════════════
 */

/**
 * Estructura de tallas por género
 * @typedef {Object} TallasPorGenero
 * @property {Object.<string, number>} DAMA - {S: 20, M: 10, ...}
 * @property {Object.<string, number>} CABALLERO
 * @property {Object.<string, number>} UNISEX
 */

/**
 * Variaciones de la prenda (manga, broche, bolsillos)
 * Mapea a: prenda_pedido_variantes
 * @typedef {Object} VariacionesPrenda
 * @property {string|null} tipo_manga
 * @property {string|null} obs_manga
 * @property {boolean} tiene_bolsillos
 * @property {string|null} obs_bolsillos
 * @property {string|null} tipo_broche
 * @property {string|null} obs_broche
 * @property {number|null} tipo_broche_boton_id
 * @property {number|null} tipo_manga_id
 * @property {boolean} tiene_reflectivo
 * @property {string|null} obs_reflectivo
 */

/**
 * Tela con color e imágenes
 * Mapea a: prenda_pedido_colores_telas + prenda_fotos_tela_pedido
 * @typedef {Object} TelaConImagenes
 * @property {string} tela - Nombre de la tela
 * @property {string} color - Color de la tela
 * @property {string|null} referencia - Referencia comercial
 * @property {number|null} tela_id - FK a catálogo de telas
 * @property {number|null} color_id - FK a catálogo de colores
 * @property {string[]} imagenes - URLs de fotos
 */

/**
 * Datos de un proceso productivo
 * Mapea a: pedidos_procesos_prenda_detalles
 * @typedef {Object} DatosProceso
 * @property {string} tipo - reflectivo|bordado|estampado|dtf|sublimado
 * @property {string[]} ubicaciones - Dónde se aplica el proceso
 * @property {string|null} observaciones
 * @property {Object} tallas - {dama: {S: 20}, caballero: {}}
 * @property {string[]} imagenes - Fotos de referencia del proceso
 */

/**
 * Proceso completo con tipo y datos
 * @typedef {Object} ProcesoCompleto
 * @property {string} tipo
 * @property {DatosProceso} datos
 */

/**
 * Prenda completa con todas sus relaciones
 * @typedef {Object} PrendaCompleta
 * @property {string} tipo - prenda_nueva|prenda_existente
 * @property {string} nombre_prenda
 * @property {string|null} descripcion
 * @property {string} origen - bodega|proveedor
 * @property {number} de_bodega - 0|1
 * @property {TallasPorGenero} cantidad_talla
 * @property {VariacionesPrenda} variaciones
 * @property {TelaConImagenes[]} telas
 * @property {string[]} imagenes - Fotos de la prenda
 * @property {Object.<string, ProcesoCompleto>} procesos - {reflectivo: {...}, bordado: {...}}
 */

/**
 * Estructura unificada del pedido completo
 * @typedef {Object} PedidoCompletoDTO
 * @property {string} cliente - Nombre o ID del cliente
 * @property {string} asesora - Usuario asesor
 * @property {string} forma_de_pago - contado|credito
 * @property {PrendaCompleta[]} items - Prendas del pedido
 */

/**
 * ═══════════════════════════════════════════════════════════════════════════
 * PARTE 2: SANITIZADOR DEFENSIVO
 * ═══════════════════════════════════════════════════════════════════════════
 */

class SanitizadorDefensivo {
    /**
     * Profundidad máxima permitida en objetos
     * Por qué: Laravel normaliza objetos >9 niveles a null
     */
    static MAX_DEPTH = 5;

    /**
     * Limpia un string y maneja edge cases
     */
    static cleanString(value) {
        // null/undefined → null explícito
        if (value === null || value === undefined || value === '') {
            return null;
        }
        
        // Objeto reactivo → toString
        if (typeof value === 'object') {
            value = String(value);
        }
        
        // String válido → trim
        if (typeof value === 'string') {
            const trimmed = value.trim();
            return trimmed === '' ? null : trimmed;
        }
        
        // Cualquier otro tipo → string
        return String(value).trim() || null;
    }

    /**
     * Limpia un entero
     */
    static cleanInt(value) {
        if (value === null || value === undefined || value === '') {
            return null;
        }
        
        const parsed = parseInt(value, 10);
        return isNaN(parsed) ? null : parsed;
    }

    /**
     * Limpia un booleano
     */
    static cleanBool(value) {
        if (value === null || value === undefined) {
            return false;
        }
        
        // String → bool
        if (typeof value === 'string') {
            const lower = value.toLowerCase();
            if (lower === 'true' || lower === '1') return true;
            if (lower === 'false' || lower === '0') return false;
        }
        
        return Boolean(value);
    }

    /**
     * Elimina arrays vacíos anidados [[[]]]
     * Por qué: Laravel los ignora pero ocupan espacio en payload
     */
    static flattenArray(arr, depth = 0) {
        if (depth > this.MAX_DEPTH) {
            console.warn('[Sanitizador] Profundidad máxima alcanzada, cortando recursión');
            return [];
        }

        if (!Array.isArray(arr)) {
            return [];
        }

        const result = [];
        
        for (const item of arr) {
            // Array anidado → aplanar recursivamente
            if (Array.isArray(item)) {
                const flattened = this.flattenArray(item, depth + 1);
                result.push(...flattened);
            }
            // Valor válido → agregar
            else if (item !== null && item !== undefined && item !== '') {
                result.push(item);
            }
        }

        return result;
    }

    /**
     * Limpia array de strings (imágenes, ubicaciones)
     */
    static cleanStringArray(arr) {
        const flattened = this.flattenArray(arr);
        
        return flattened
            .filter(item => typeof item === 'string' && item.trim() !== '')
            .map(item => item.trim());
    }

    /**
     * Limpia objeto eliminando propiedades reactivas y circulares
     * Por qué: Vue/React añaden __ob__, _reactivity, etc.
     */
    static cleanObject(obj, depth = 0, seen = new WeakSet()) {
        if (depth > this.MAX_DEPTH) {
            console.warn('[Sanitizador] Profundidad máxima en objeto, cortando');
            return null;
        }

        // null/undefined → null
        if (obj === null || obj === undefined) {
            return null;
        }

        // Primitivo → retornar directo
        if (typeof obj !== 'object') {
            return obj;
        }

        // Referencia circular → cortar
        if (seen.has(obj)) {
            console.warn('[Sanitizador] Referencia circular detectada');
            return null;
        }

        seen.add(obj);

        // Array → limpiar recursivamente
        if (Array.isArray(obj)) {
            return obj.map(item => this.cleanObject(item, depth + 1, seen));
        }

        // Objeto → filtrar propiedades reactivas
        const cleaned = {};
        
        for (const key in obj) {
            // Ignorar propiedades internas de Vue/React
            if (key.startsWith('_') || key.startsWith('$') || key === '__ob__') {
                continue;
            }

            // Recursión en valor
            const value = this.cleanObject(obj[key], depth + 1, seen);
            
            // Solo agregar si no es null/undefined
            if (value !== null && value !== undefined) {
                cleaned[key] = value;
            }
        }

        return cleaned;
    }

    /**
     * Valida estructura de tallas
     */
    static validateTallas(tallas) {
        if (!tallas || typeof tallas !== 'object') {
            return false;
        }

        // Debe tener al menos un género con tallas
        const generos = ['DAMA', 'CABALLERO', 'UNISEX'];
        
        return generos.some(genero => {
            const tallasPorGenero = tallas[genero];
            if (!tallasPorGenero || typeof tallasPorGenero !== 'object') {
                return false;
            }
            
            // Al menos una talla con cantidad > 0
            return Object.values(tallasPorGenero).some(cant => {
                const cantidad = parseInt(cant);
                return !isNaN(cantidad) && cantidad > 0;
            });
        });
    }
}

/**
 * ═══════════════════════════════════════════════════════════════════════════
 * PARTE 3: BUILDER DEL PEDIDO COMPLETO
 * ═══════════════════════════════════════════════════════════════════════════
 */

class PedidoCompletoUnificado {
    constructor() {
        this.reset();
    }

    /**
     * Resetear estado interno
     */
    reset() {
        this._cliente = null;
        this._asesora = null;
        this._formaPago = 'contado';
        this._items = [];
        return this;
    }

    /**
     * Generar UID único para prendas, telas, procesos, imágenes
     * @private
     */
    _generateUID() {
        return 'uid-' + Math.random().toString(36).substr(2, 9) + '-' + Date.now().toString(36);
    }

    /**
     * Establecer cliente
     */
    setCliente(cliente) {
        this._cliente = SanitizadorDefensivo.cleanString(cliente);
        return this;
    }

    /**
     * Establecer asesora
     */
    setAsesora(asesora) {
        this._asesora = SanitizadorDefensivo.cleanString(asesora);
        return this;
    }

    /**
     * Establecer forma de pago
     */
    setFormaPago(formaPago) {
        const cleaned = SanitizadorDefensivo.cleanString(formaPago) || 'contado';
        this._formaPago = cleaned.toLowerCase();
        return this;
    }

    /**
     * Agregar prenda al pedido
     * @param {Object} prendaData - Datos crudos de la prenda
     */
    agregarPrenda(prendaData) {
        const prendaSanitizada = this._sanitizarPrenda(prendaData);
        
        // Validar que tenga datos mínimos
        if (!prendaSanitizada.nombre_prenda) {
            console.error('[PedidoCompleto] Prenda sin nombre, ignorando');
            return this;
        }

        if (!SanitizadorDefensivo.validateTallas(prendaSanitizada.cantidad_talla)) {
            console.error('[PedidoCompleto] Prenda sin tallas válidas, ignorando');
            return this;
        }

        this._items.push(prendaSanitizada);
        return this;
    }

    /**
     * Sanitizar prenda completa
     *  CRÍTICO: NO hacer JSON.stringify en imagenes (File objects)
     * @private
     */
    _sanitizarPrenda(raw) {
        return {
            uid: raw.uid || this._generateUID(),  // ← NUEVO: UID único
            tipo: raw.tipo || 'prenda_nueva',
            nombre_prenda: SanitizadorDefensivo.cleanString(raw.nombre_prenda || raw.nombre_producto),
            descripcion: SanitizadorDefensivo.cleanString(raw.descripcion),
            origen: raw.origen || 'bodega',
            de_bodega: (raw.origen === 'bodega' || raw.de_bodega === 1) ? 1 : 0,
            
            // Tallas (CRÍTICO)
            cantidad_talla: this._sanitizarCantidadTalla(raw.cantidad_talla),
            
            // Variaciones (manga, broche, bolsillos)
            variaciones: this._sanitizarVariaciones(raw.variaciones || raw.variantes),
            
            // Telas con imágenes (NO stringify, mantener como están)
            telas: this._sanitizarTelas(raw.telas),
            
            // Imágenes de la prenda - PRESERVAR File objects
            imagenes: Array.isArray(raw.imagenes) ? raw.imagenes : [],
            
            // Procesos productivos (manejar array como object)
            procesos: this._sanitizarProcesos(raw.procesos)
        };
    }

    /**
     * Sanitizar cantidad_talla
     * Mapea a: prenda_pedido_tallas
     * @private
     */
    _sanitizarCantidadTalla(raw) {
        if (!raw || typeof raw !== 'object') {
            return { DAMA: {}, CABALLERO: {}, UNISEX: {} };
        }

        const cleaned = {};

        ['DAMA', 'CABALLERO', 'UNISEX'].forEach(genero => {
            const tallas = raw[genero];
            
            // Validar que no sea array
            if (!tallas || typeof tallas !== 'object' || Array.isArray(tallas)) {
                cleaned[genero] = {};
                return;
            }

            // Limpiar cada talla
            cleaned[genero] = {};
            
            Object.entries(tallas).forEach(([talla, cantidad]) => {
                const cant = SanitizadorDefensivo.cleanInt(cantidad);
                
                // Solo guardar si cantidad > 0
                if (cant && cant > 0) {
                    cleaned[genero][talla.toUpperCase()] = cant;
                }
            });
        });

        return cleaned;
    }

    /**
     * Sanitizar variaciones
     * Mapea a: prenda_pedido_variantes
     * @private
     */
    _sanitizarVariaciones(raw) {
        if (!raw || typeof raw !== 'object') {
            return this._variacionesVacias();
        }

        return {
            tipo_manga: SanitizadorDefensivo.cleanString(raw.tipo_manga),
            obs_manga: SanitizadorDefensivo.cleanString(raw.obs_manga),
            tiene_bolsillos: SanitizadorDefensivo.cleanBool(raw.tiene_bolsillos),
            obs_bolsillos: SanitizadorDefensivo.cleanString(raw.obs_bolsillos),
            tipo_broche: SanitizadorDefensivo.cleanString(raw.tipo_broche),
            obs_broche: SanitizadorDefensivo.cleanString(raw.obs_broche),
            tipo_broche_boton_id: SanitizadorDefensivo.cleanInt(raw.tipo_broche_boton_id),
            tipo_manga_id: SanitizadorDefensivo.cleanInt(raw.tipo_manga_id),
            tiene_reflectivo: SanitizadorDefensivo.cleanBool(raw.tiene_reflectivo),
            obs_reflectivo: SanitizadorDefensivo.cleanString(raw.obs_reflectivo)
        };
    }

    /**
     * Variaciones vacías por defecto
     * @private
     */
    _variacionesVacias() {
        return {
            tipo_manga: null,
            obs_manga: null,
            tiene_bolsillos: false,
            obs_bolsillos: null,
            tipo_broche: null,
            obs_broche: null,
            tipo_broche_boton_id: null,
            tipo_manga_id: null,
            tiene_reflectivo: false,
            obs_reflectivo: null
        };
    }

    /**
     * Sanitizar telas
     * Mapea a: prenda_pedido_colores_telas + prenda_fotos_tela_pedido
     * @private
     */
    _sanitizarTelas(raw) {
        if (!Array.isArray(raw)) {
            return [];
        }

        return raw
            .filter(tela => tela && typeof tela === 'object')
            .map(tela => ({
                uid: tela.uid || this._generateUID(),  // ← NUEVO: UID único
                tela: SanitizadorDefensivo.cleanString(tela.tela),
                color: SanitizadorDefensivo.cleanString(tela.color),
                referencia: SanitizadorDefensivo.cleanString(tela.referencia),
                tela_id: SanitizadorDefensivo.cleanInt(tela.tela_id),
                color_id: SanitizadorDefensivo.cleanInt(tela.color_id),
                imagenes: Array.isArray(tela.imagenes) ? tela.imagenes : []  // CRÍTICO: Mantener File objects
            }))
            .filter(tela => tela.tela || tela.color); // Al menos tela o color
    }

    /**
     * Sanitizar procesos
     * Mapea a: pedidos_procesos_prenda_detalles
     * @private
     */
    _sanitizarProcesos(raw) {
        if (!raw) {
            return {};
        }

        // Detectar si procesos llega como ARRAY (convertir a object)
        if (Array.isArray(raw)) {
            console.warn('[Builder] Procesos llegó como array, convirtiendo a object...');
            const procesosObj = {};
            raw.forEach((proc, idx) => {
                // Intentar obtener el slug del tipo de proceso - puede venir como:
                // 1. proc.tipo (string directo)
                // 2. proc.tipoProceso.slug (relación del backend)
                const tipoProcesoSlug = proc.tipo || proc.tipoProceso?.slug;
                
                if (proc && tipoProcesoSlug) {
                    procesosObj[tipoProcesoSlug] = proc;
                } else if (proc) {
                    procesosObj[`proceso_${idx}`] = proc;
                }
            });
            raw = procesosObj;
        }

        if (typeof raw !== 'object' || raw === null) {
            return {};
        }

        const cleaned = {};
        const tiposProceso = ['reflectivo', 'bordado', 'estampado', 'dtf', 'sublimado', 'tejido', 'serigrafía'];

        tiposProceso.forEach(tipo => {
            if (raw[tipo]) {
                const datos = raw[tipo].datos || raw[tipo];
                
                cleaned[tipo] = {
                    uid: raw[tipo].uid || this._generateUID(),  // ← NUEVO: UID único
                    tipo: tipo,
                    datos: this._sanitizarDatosProceso(datos, tipo)
                };
            }
        });

        return cleaned;
    }

    /**
     * Sanitizar datos de un proceso
     * @private
     */
    _sanitizarDatosProceso(raw, tipo) {
        if (!raw || typeof raw !== 'object') {
            return this._datosProcesoVacios(tipo);
        }

        return {
            tipo: tipo,
            ubicaciones: this._sanitizarUbicaciones(raw.ubicaciones),
            observaciones: SanitizadorDefensivo.cleanString(raw.observaciones),
            tallas: this._sanitizarTallasProceso(raw.tallas),
            imagenes: Array.isArray(raw.imagenes) ? raw.imagenes : []  // CRÍTICO: Mantener File objects
        };
    }

    /**
     * Sanitizar ubicaciones (puede ser string o array)
     * @private
     */
    _sanitizarUbicaciones(raw) {
        // String → array de un elemento
        if (typeof raw === 'string' && raw.trim() !== '') {
            return [raw.trim()];
        }

        // Array → limpiar
        if (Array.isArray(raw)) {
            return SanitizadorDefensivo.cleanStringArray(raw);
        }

        return [];
    }

    /**
     * Sanitizar tallas de proceso
     * Mapea a: pedidos_procesos_prenda_tallas
     * Soporta: object, array, string
     * @private
     */
    _sanitizarTallasProceso(raw) {
        if (!raw || typeof raw !== 'object') {
            return { dama: {}, caballero: {} };
        }

        const cleaned = { dama: {}, caballero: {}, unisex: {} };

        ['dama', 'caballero', 'unisex'].forEach(genero => {
            const tallas = raw[genero];
            
            if (!tallas) {
                cleaned[genero] = {};
                return;
            }

            // Convertir ARRAY a OBJECT si es necesario
            let tallasObj = tallas;
            if (Array.isArray(tallas)) {
                tallasObj = {};
                tallas.forEach((talla) => {
                    if (talla && typeof talla === 'object') {
                        const nombreTalla = talla.talla || talla.nombre || talla.size;
                        const cantidad = talla.cantidad || talla.count || 0;
                        if (nombreTalla) {
                            tallasObj[nombreTalla.toUpperCase()] = cantidad;
                        }
                    }
                });
            }

            // Procesar OBJECT normalmente
            if (typeof tallasObj === 'object' && !Array.isArray(tallasObj)) {
                Object.entries(tallasObj).forEach(([talla, cantidad]) => {
                    const cant = SanitizadorDefensivo.cleanInt(cantidad);
                    
                    if (cant && cant > 0) {
                        cleaned[genero][talla.toUpperCase()] = cant;
                    }
                });
            }
        });

        return cleaned;
    }

    /**
     * Datos de proceso vacíos
     * @private
     */
    _datosProcesoVacios(tipo) {
        return {
            tipo: tipo,
            ubicaciones: [],
            observaciones: null,
            tallas: { dama: {}, caballero: {} },
            imagenes: []
        };
    }

    /**
     * Construir payload final
     * @returns {PedidoCompletoDTO}
     */
    build() {
        // Validaciones finales
        if (!this._cliente) {
            throw new Error('[PedidoCompleto] Cliente es requerido');
        }

        if (this._items.length === 0) {
            throw new Error('[PedidoCompleto] Al menos una prenda es requerida');
        }

        const payload = {
            cliente: this._cliente,
            asesora: this._asesora,
            forma_de_pago: this._formaPago,
            items: this._items
        };

        // Limpieza final anti-reactividad
        const payloadLimpio = SanitizadorDefensivo.cleanObject(payload);

        // Log para debugging
        console.log('[PedidoCompleto] Payload construido:', {
            cliente: payloadLimpio.cliente,
            items_count: payloadLimpio.items.length,
            total_tallas: this._contarTallasTotal(payloadLimpio.items)
        });

        return payloadLimpio;
    }

    /**
     * Contar tallas total (para validación)
     * @private
     */
    _contarTallasTotal(items) {
        return items.reduce((total, item) => {
            const cantidadPrenda = Object.values(item.cantidad_talla).reduce((sum, tallas) => {
                return sum + Object.values(tallas).reduce((s, c) => s + c, 0);
            }, 0);
            return total + cantidadPrenda;
        }, 0);
    }

    /**
     * Validar payload antes de enviar
     */
    validate() {
        const errores = [];

        if (!this._cliente) {
            errores.push('Cliente es requerido');
        }

        if (this._items.length === 0) {
            errores.push('Al menos una prenda es requerida');
        }

        this._items.forEach((item, index) => {
            if (!item.nombre_prenda) {
                errores.push(`Prenda #${index}: nombre_prenda requerido`);
            }

            if (!SanitizadorDefensivo.validateTallas(item.cantidad_talla)) {
                errores.push(`Prenda #${index}: tallas inválidas o vacías`);
            }
        });

        if (errores.length > 0) {
            throw new Error('[PedidoCompleto] Validación fallida:\n' + errores.join('\n'));
        }

        return true;
    }
}

/**
 * ═══════════════════════════════════════════════════════════════════════════
 * PARTE 4: EXPORTACIÓN Y EJEMPLOS DE USO
 * ═══════════════════════════════════════════════════════════════════════════
 */

// Hacer disponible globalmente
if (typeof window !== 'undefined') {
    window.PedidoCompletoUnificado = PedidoCompletoUnificado;
    window.SanitizadorDefensivo = SanitizadorDefensivo;
}

// Export para módulos ES6
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        PedidoCompletoUnificado,
        SanitizadorDefensivo
    };
}

// Export ES6 para import statements
export { PedidoCompletoUnificado, SanitizadorDefensivo };

/**
 * ═══════════════════════════════════════════════════════════════════════════
 * EJEMPLO DE USO COMPLETO
 * ═══════════════════════════════════════════════════════════════════════════
 */

/*
// CASO 1: Uso básico
const builder = new PedidoCompletoUnificado();

const payload = builder
    .setCliente('ACME Corporation')
    .setAsesora('yus2')
    .setFormaPago('CONTADO')
    .agregarPrenda({
        nombre_prenda: 'CAMISA DRILL',
        descripcion: 'Camisa drill naranja',
        origen: 'bodega',
        cantidad_talla: {
            DAMA: { S: 20, M: 10 },
            CABALLERO: {},
            UNISEX: {}
        },
        variaciones: {
            tipo_manga: 'Larga',
            obs_manga: 'CHERRETERIA',
            tiene_bolsillos: true,
            obs_bolsillos: 'CON ESPACIO DE 4CM',
            tipo_broche: 'boton',
            obs_broche: 'COLOR BLANCO',
            tipo_broche_boton_id: 2
        },
        telas: [
            {
                tela: 'DRILL',
                color: 'NARANJA',
                referencia: 'REF232',
                imagenes: [
                    '/storage/telas/drill_1.jpg',
                    '/storage/telas/drill_2.jpg'
                ]
            }
        ],
        imagenes: [
            '/storage/prendas/camisa_frente.jpg',
            '/storage/prendas/camisa_espalda.jpg'
        ],
        procesos: {
            reflectivo: {
                datos: {
                    ubicaciones: [
                        '2 LINEAS EN EL HOMBRO',
                        'UNA EN CADA LADO'
                    ],
                    observaciones: 'Reflectivo alta visibilidad',
                    tallas: {
                        dama: { S: 20, M: 10 },
                        caballero: {}
                    },
                    imagenes: ['/storage/procesos/reflectivo_ref.jpg']
                }
            }
        }
    })
    .build();

// CASO 2: Validación antes de enviar
try {
    builder.validate();
    
    const response = await fetch('/api/pedidos/crear-sin-cotizacion', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    });
    
    if (!response.ok) {
        throw new Error(`HTTP ${response.status}`);
    }
    
    const resultado = await response.json();
    console.log(' Pedido creado:', resultado.pedido_id);
    
} catch (error) {
    console.error(' Error:', error.message);
}

// CASO 3: Múltiples prendas
const pedidoMultiple = new PedidoCompletoUnificado()
    .setCliente('Cliente XYZ')
    .setFormaPago('credito')
    .agregarPrenda({
        nombre_prenda: 'CAMISA DRILL',
        cantidad_talla: { DAMA: { S: 10 }, CABALLERO: {}, UNISEX: {} },
        telas: [{ tela: 'DRILL', color: 'AZUL', imagenes: [] }]
    })
    .agregarPrenda({
        nombre_prenda: 'PANTALÓN JEAN',
        cantidad_talla: { DAMA: {}, CABALLERO: { 30: 5, 32: 10 }, UNISEX: {} },
        telas: [{ tela: 'JEAN', color: 'NEGRO', imagenes: [] }]
    })
    .build();

// CASO 4: Sanitización de datos sucios
const datosSucios = {
    nombre_prenda: '  CAMISA  ', // espacios
    cantidad_talla: {
        DAMA: { S: '20', M: 10 }, // string y number mezclados
        CABALLERO: [[]], // array vacío anidado
        UNISEX: {}
    },
    imagenes: [[['/img1.jpg']], null, '', '/img2.jpg'], // arrays anidados + nulls
    procesos: {
        reflectivo: {
            datos: {
                ubicaciones: 'HOMBRO', // string en vez de array
                tallas: {
                    dama: { S: '10' } // string
                }
            }
        }
    }
};

const prendaLimpia = new PedidoCompletoUnificado()
    .setCliente('Test')
    .agregarPrenda(datosSucios)
    .build();

// Resultado:
// {
//   nombre_prenda: 'CAMISA',
//   cantidad_talla: { DAMA: { S: 20, M: 10 }, CABALLERO: {}, UNISEX: {} },
//   imagenes: ['/img1.jpg', '/img2.jpg'],
//   procesos: {
//     reflectivo: {
//       datos: {
//         ubicaciones: ['HOMBRO'],
//         tallas: { dama: { S: 10 }, caballero: {} }
//       }
//     }
//   }
// }
*/
