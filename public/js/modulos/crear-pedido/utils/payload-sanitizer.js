/**
 * PayloadSanitizer - Sanitizador de datos para enviar a Laravel
 * 
 * Problema: Frameworks reactivos (Vue/React) agregan propiedades internas que:
 * - Causan "Over 9 levels deep, aborting normalization" en Laravel
 * - Envían booleanos como strings ("true" vs true)
 * - Crean referencias circulares (Proxy, Observer)
 * - Inyectan metadata innecesaria (__v_isRef, _reactive, etc.)
 * 
 * Solución: Clonar profundamente eliminando propiedades reactivas y normalizando tipos
 * 
 * @author GitHub Copilot
 * @version 1.0.0
 * @license MIT
 */

class PayloadSanitizer {
    /**
     * Prefijos de propiedades a eliminar (Vue, React, internos)
     */
    static PREFIJOS_PROHIBIDOS = [
        '__v_',     // Vue 3 Composition API
        '__ob__',   // Vue 2 Observer
        '_',        // Propiedades privadas genéricas
        '$',        // Vue instance properties
        '@@',       // Redux/MobX
    ];

    /**
     * Propiedades específicas a eliminar
     */
    static PROPIEDADES_PROHIBIDAS = new Set([
        '__v_isRef',
        '__v_isReactive',
        '__v_isReadonly',
        '__v_isShallow',
        '__v_skip',
        '__ob__',
        '_isVue',
        '_rawValue',
        '_value',
        '__reactiveHandlers__',
        '__reactInternalInstance',
        '$$typeof',
    ]);

    /**
     * Sanitizar variaciones de una prenda
     * 
     * @param {Object} variaciones - Objeto variaciones desde formulario reactivo
     * @returns {Object} - Objeto plano limpio para Laravel
     */
    static sanitizarVariaciones(variaciones) {
        if (!variaciones || typeof variaciones !== 'object') {
            return {};
        }

        // Clonar sin referencias reactivas
        const limpio = this.clonarProfundo(variaciones);

        return {
            // String fields
            tipo_manga: this.limpiarString(limpio.tipo_manga),
            obs_manga: this.limpiarString(limpio.obs_manga),
            obs_bolsillos: this.limpiarString(limpio.obs_bolsillos),
            tipo_broche: this.limpiarString(limpio.tipo_broche),
            obs_broche: this.limpiarString(limpio.obs_broche),
            obs_reflectivo: this.limpiarString(limpio.obs_reflectivo),
            
            // Boolean fields
            tiene_bolsillos: this.convertirBoolean(limpio.tiene_bolsillos),
            tiene_reflectivo: this.convertirBoolean(limpio.tiene_reflectivo),
            
            // Number fields
            tipo_broche_boton_id: this.convertirNumero(limpio.tipo_broche_boton_id),
        };
    }

    /**
     * Sanitizar un item completo (prenda)
     * 
     * @param {Object} item - Item desde formulario
     * @returns {Object} - Item limpio para Laravel
     */
    static sanitizarItem(item) {
        if (!item || typeof item !== 'object') {
            return null;
        }

        const limpio = this.clonarProfundo(item);

        return {
            tipo: this.limpiarString(limpio.tipo) || 'prenda_nueva',
            nombre_prenda: this.limpiarString(limpio.nombre_prenda),
            descripcion: this.limpiarString(limpio.descripcion),
            origen: this.limpiarString(limpio.origen),
            
            // Cantidad por talla (objeto, no array)
            cantidad_talla: this.sanitizarCantidadTalla(limpio.cantidad_talla),
            
            // Variaciones (Value Object)
            variaciones: this.sanitizarVariaciones(limpio.variaciones),
            
            // Procesos (objeto con keys dinámicas)
            procesos: this.sanitizarProcesos(limpio.procesos),
            
            // Telas (array de objetos)
            telas: this.sanitizarTelas(limpio.telas),
            
            // Imágenes (array de objetos)
            imagenes: this.sanitizarImagenes(limpio.imagenes),
        };
    }

    /**
     * Sanitizar cantidad_talla (objeto anidado: {DAMA: {S: 10, M: 20}, ...})
     * 
     * @param {Object} cantidadTalla 
     * @returns {Object}
     */
    static sanitizarCantidadTalla(cantidadTalla) {
        if (!cantidadTalla || typeof cantidadTalla !== 'object') {
            return {};
        }

        const limpio = this.clonarProfundo(cantidadTalla);
        const resultado = {};

        // Procesar cada género (DAMA, CABALLERO, UNISEX)
        for (const genero in limpio) {
            if (this.esProhibido(genero)) continue;

            const tallas = limpio[genero];
            if (typeof tallas === 'object' && tallas !== null) {
                resultado[genero] = {};

                for (const talla in tallas) {
                    if (this.esProhibido(talla)) continue;

                    const cantidad = this.convertirNumero(tallas[talla]);
                    if (cantidad !== null && cantidad > 0) {
                        resultado[genero][talla] = cantidad;
                    }
                }
            }
        }

        return resultado;
    }

    /**
     * Sanitizar procesos (objeto con keys dinámicas: reflectivo, bordado, etc.)
     * 
     * @param {Object} procesos 
     * @returns {Object}
     */
    static sanitizarProcesos(procesos) {
        if (!procesos || typeof procesos !== 'object') {
            return {};
        }

        const limpio = this.clonarProfundo(procesos);
        const resultado = {};

        for (const tipoProceso in limpio) {
            if (this.esProhibido(tipoProceso)) continue;

            const proceso = limpio[tipoProceso];
            if (typeof proceso === 'object' && proceso !== null) {
                resultado[tipoProceso] = {
                    tipo: this.limpiarString(proceso.tipo),
                    datos: proceso.datos ? this.clonarProfundo(proceso.datos) : {},
                };
            }
        }

        return resultado;
    }

    /**
     * Sanitizar telas (array de objetos)
     * 
     * @param {Array} telas 
     * @returns {Array}
     */
    static sanitizarTelas(telas) {
        if (!Array.isArray(telas)) {
            return [];
        }

        return telas
            .filter(tela => tela && typeof tela === 'object')
            .map(tela => {
                const limpio = this.clonarProfundo(tela);
                return {
                    tela: this.limpiarString(limpio.tela),
                    color: this.limpiarString(limpio.color),
                    referencia: this.limpiarString(limpio.referencia),
                    imagenes: this.sanitizarImagenes(limpio.imagenes),
                };
            })
            .filter(tela => tela.tela || tela.color || tela.referencia);
    }

    /**
     * Sanitizar imágenes (array de objetos)
     * 
     * @param {Array} imagenes 
     * @returns {Array}
     */
    static sanitizarImagenes(imagenes) {
        if (!Array.isArray(imagenes)) {
            return [];
        }

        // Aplanar arrays anidados [[]] -> []
        const aplanado = imagenes.flat(2);

        return aplanado
            .filter(img => img && typeof img === 'object')
            .map(img => {
                const limpio = this.clonarProfundo(img);
                return {
                    original: this.limpiarString(limpio.original),
                    webp: this.limpiarString(limpio.webp),
                    thumbnail: this.limpiarString(limpio.thumbnail),
                };
            })
            .filter(img => img.original || img.webp);
    }

    /**
     * Sanitizar pedido completo
     * 
     * @param {Object} pedido - Pedido desde formulario
     * @returns {Object} - Payload limpio para Laravel
     */
    static sanitizarPedido(pedido) {
        if (!pedido || typeof pedido !== 'object') {
            throw new Error('El pedido debe ser un objeto válido');
        }

        const limpio = this.clonarProfundo(pedido);

        return {
            cliente: this.limpiarString(limpio.cliente),
            asesora: this.limpiarString(limpio.asesora),
            forma_de_pago: this.limpiarString(limpio.forma_de_pago),
            descripcion: this.limpiarString(limpio.descripcion),
            items: Array.isArray(limpio.items) 
                ? limpio.items.map(item => this.sanitizarItem(item)).filter(Boolean)
                : [],
        };
    }

    // ==================== UTILIDADES PRIVADAS ====================

    /**
     * Clonar objeto profundamente SIN referencias circulares ni propiedades reactivas
     * 
     * @param {any} obj 
     * @param {WeakMap} cache - Cache para detectar referencias circulares
     * @returns {any}
     */
    static clonarProfundo(obj, cache = new WeakMap()) {
        // Primitivos y null
        if (obj === null || typeof obj !== 'object') {
            return obj;
        }

        // Detectar referencia circular
        if (cache.has(obj)) {
            console.warn(' Referencia circular detectada y eliminada');
            return undefined;
        }

        // Manejar casos especiales
        if (obj instanceof Date) {
            return new Date(obj);
        }
        if (obj instanceof RegExp) {
            return new RegExp(obj);
        }
        if (ArrayBuffer.isView(obj)) {
            return obj;
        }

        // Arrays
        if (Array.isArray(obj)) {
            cache.set(obj, true);
            return obj.map(item => this.clonarProfundo(item, cache));
        }

        // Objetos (eliminar propiedades reactivas)
        cache.set(obj, true);
        const clone = {};

        for (const key in obj) {
            // Saltar propiedades no enumerables
            if (!obj.hasOwnProperty(key)) continue;

            // Saltar propiedades prohibidas
            if (this.esProhibido(key)) {
                console.debug(`🧹 Propiedad reactiva eliminada: "${key}"`);
                continue;
            }

            // Clonar profundamente el valor
            clone[key] = this.clonarProfundo(obj[key], cache);
        }

        return clone;
    }

    /**
     * Verificar si una propiedad debe ser eliminada
     * 
     * @param {string} key 
     * @returns {boolean}
     */
    static esProhibido(key) {
        if (typeof key !== 'string') return false;

        // Verificar propiedades específicas
        if (this.PROPIEDADES_PROHIBIDAS.has(key)) {
            return true;
        }

        // Verificar prefijos
        return this.PREFIJOS_PROHIBIDOS.some(prefijo => key.startsWith(prefijo));
    }

    /**
     * Limpiar string (trim, null/undefined -> null)
     * 
     * @param {any} valor 
     * @returns {string|null}
     */
    static limpiarString(valor) {
        if (valor === null || valor === undefined) {
            return null;
        }

        const str = String(valor).trim();
        return str === '' ? null : str;
    }

    /**
     * Convertir a boolean REAL (no string)
     * 
     * @param {any} valor 
     * @returns {boolean}
     */
    static convertirBoolean(valor) {
        if (valor === null || valor === undefined) {
            return false;
        }

        // Si ya es boolean
        if (typeof valor === 'boolean') {
            return valor;
        }

        // Si es string
        if (typeof valor === 'string') {
            const lower = valor.toLowerCase().trim();
            return lower === 'true' || lower === '1' || lower === 'yes' || lower === 'si';
        }

        // Si es número
        if (typeof valor === 'number') {
            return valor !== 0;
        }

        // Default
        return Boolean(valor);
    }

    /**
     * Convertir a número (null/undefined/vacío -> null)
     * 
     * @param {any} valor 
     * @returns {number|null}
     */
    static convertirNumero(valor) {
        if (valor === null || valor === undefined || valor === '') {
            return null;
        }

        const num = Number(valor);
        return isNaN(num) ? null : num;
    }

    /**
     * Validar que el payload esté listo para Laravel
     * 
     * @param {Object} payload 
     * @returns {{valido: boolean, errores: string[]}}
     */
    static validarPayload(payload) {
        const errores = [];

        if (!payload.cliente) {
            errores.push('El cliente es requerido');
        }

        if (!Array.isArray(payload.items) || payload.items.length === 0) {
            errores.push('Debe haber al menos un item');
        } else {
            payload.items.forEach((item, idx) => {
                if (!item.nombre_prenda) {
                    errores.push(`Item ${idx + 1}: nombre_prenda es requerido`);
                }

                if (typeof item.variaciones !== 'object' || Array.isArray(item.variaciones)) {
                    errores.push(`Item ${idx + 1}: variaciones debe ser un objeto, no un array`);
                }
            });
        }

        return {
            valido: errores.length === 0,
            errores
        };
    }

    /**
     * Debug: Comparar antes/después de sanitizar
     * 
     * @param {Object} antes 
     * @param {Object} despues 
     */
    static debug(antes, despues) {
        console.group('🧪 PayloadSanitizer - Debug');
        console.log('📦 ANTES (con propiedades reactivas):');
        console.dir(antes, { depth: 3 });
        console.log('\n DESPUÉS (limpio para Laravel):');
        console.dir(despues, { depth: 3 });
        console.log('\n Tamaño:');
        console.log(`  Antes: ${JSON.stringify(antes).length} bytes`);
        console.log(`  Después: ${JSON.stringify(despues).length} bytes`);
        console.groupEnd();
    }
}

// Exportar para uso global
window.PayloadSanitizer = PayloadSanitizer;

// Exportar como módulo ES6 (opcional)
if (typeof module !== 'undefined' && module.exports) {
    module.exports = PayloadSanitizer;
}
