/**
 * PedidoSanitizer.js
 * Limpia JSON complejo antes de enviar al backend
 * Elimina: referencias circulares, arrays vacíos, objetos reactivos
 */

export class PedidoSanitizer {
    /**
     * Sanitizar pedido completo
     */
    static sanitize(pedidoData) {
        const seen = new WeakSet();
        
        return {
            cliente: pedidoData.cliente,
            asesora: pedidoData.asesora,
            forma_de_pago: (pedidoData.forma_de_pago || pedidoData.forma_pago || 'contado').toLowerCase(),
            items: (pedidoData.items || []).map(item => this.sanitizeItem(item, seen))
        };
    }

    /**
     * Sanitizar un item/prenda
     */
    static sanitizeItem(item, seen) {
        return {
            tipo: item.tipo || 'prenda_nueva',
            nombre_prenda: item.nombre_prenda || item.nombre_producto || '',
            descripcion: this.cleanString(item.descripcion),
            origen: item.origen || 'bodega',
            de_bodega: item.origen === 'bodega' ? 1 : 0,
            
            // Cantidades por talla
            cantidad_talla: this.sanitizeCantidadTalla(item.cantidad_talla),
            
            // Variaciones
            variaciones: this.sanitizeVariaciones(item.variaciones || item.variantes),
            
            // Telas (CRÍTICO)
            telas: this.sanitizeTelas(item.telas),
            
            // Imágenes de prenda
            imagenes: this.sanitizeImagenes(item.imagenes),
            
            // Procesos productivos
            procesos: this.sanitizeProcesos(item.procesos)
        };
    }

    /**
     * Limpiar cantidad_talla
     */
    static sanitizeCantidadTalla(cantidadTalla) {
        if (!cantidadTalla || typeof cantidadTalla !== 'object') {
            return { DAMA: {}, CABALLERO: {}, UNISEX: {} };
        }

        const cleaned = {};
        ['DAMA', 'CABALLERO', 'UNISEX'].forEach(genero => {
            const tallas = cantidadTalla[genero];
            if (tallas && typeof tallas === 'object' && !Array.isArray(tallas)) {
                cleaned[genero] = {};
                Object.entries(tallas).forEach(([talla, cantidad]) => {
                    const cant = parseInt(cantidad);
                    if (!isNaN(cant) && cant > 0) {
                        cleaned[genero][talla] = cant;
                    }
                });
            } else {
                cleaned[genero] = {};
            }
        });

        return cleaned;
    }

    /**
     * Limpiar variaciones
     */
    static sanitizeVariaciones(variaciones) {
        if (!variaciones) return {};

        return {
            tipo_manga: this.cleanString(variaciones.tipo_manga),
            obs_manga: this.cleanString(variaciones.obs_manga),
            tiene_bolsillos: Boolean(variaciones.tiene_bolsillos),
            obs_bolsillos: this.cleanString(variaciones.obs_bolsillos),
            tipo_broche: this.cleanString(variaciones.tipo_broche),
            obs_broche: this.cleanString(variaciones.obs_broche),
            tipo_broche_boton_id: this.cleanInt(variaciones.tipo_broche_boton_id),
            tipo_manga_id: this.cleanInt(variaciones.tipo_manga_id),
            tiene_reflectivo: Boolean(variaciones.tiene_reflectivo),
            obs_reflectivo: this.cleanString(variaciones.obs_reflectivo)
        };
    }

    /**
     * Limpiar telas (CRÍTICO - aquí se pierde la data)
     */
    static sanitizeTelas(telas) {
        if (!Array.isArray(telas)) return [];

        return telas
            .filter(tela => tela && typeof tela === 'object')
            .map(tela => ({
                tela: this.cleanString(tela.tela),
                color: this.cleanString(tela.color),
                referencia: this.cleanString(tela.referencia),
                tela_id: this.cleanInt(tela.tela_id),
                color_id: this.cleanInt(tela.color_id),
                // LIMPIAR IMÁGENES (eliminar [[]])
                imagenes: this.sanitizeImagenes(tela.imagenes)
            }));
    }

    /**
     * Limpiar imágenes (eliminar [[]], arrays vacíos, nulls)
     */
    static sanitizeImagenes(imagenes) {
        if (!imagenes) return [];
        if (!Array.isArray(imagenes)) return [];

        const flattened = this.flattenImages(imagenes);
        return flattened.filter(img => img && typeof img === 'string' && img.trim() !== '');
    }

    /**
     * Aplanar arrays anidados de imágenes
     */
    static flattenImages(arr, depth = 0) {
        if (depth > 5) return []; // Prevenir recursión infinita

        const result = [];
        for (const item of arr) {
            if (Array.isArray(item)) {
                result.push(...this.flattenImages(item, depth + 1));
            } else if (item && typeof item === 'string') {
                result.push(item);
            }
        }
        return result;
    }

    /**
     * Limpiar procesos
     */
    static sanitizeProcesos(procesos) {
        if (!procesos || typeof procesos !== 'object') return {};

        const cleaned = {};
        const tiposProceso = ['reflectivo', 'bordado', 'estampado', 'dtf', 'sublimado'];

        tiposProceso.forEach(tipo => {
            if (procesos[tipo]) {
                cleaned[tipo] = {
                    tipo: tipo,
                    datos: this.sanitizeDatosProceso(procesos[tipo].datos || procesos[tipo])
                };
            }
        });

        return cleaned;
    }

    /**
     * Limpiar datos de proceso
     */
    static sanitizeDatosProceso(datos) {
        if (!datos || typeof datos !== 'object') return {};

        return {
            tipo: this.cleanString(datos.tipo),
            ubicaciones: this.sanitizeUbicaciones(datos.ubicaciones),
            observaciones: this.cleanString(datos.observaciones),
            tallas: this.sanitizeTallasProceso(datos.tallas),
            imagenes: this.sanitizeImagenes(datos.imagenes)
        };
    }

    /**
     * Limpiar ubicaciones (puede ser array o string)
     */
    static sanitizeUbicaciones(ubicaciones) {
        if (!ubicaciones) return [];
        
        if (typeof ubicaciones === 'string') {
            return [ubicaciones];
        }
        
        if (Array.isArray(ubicaciones)) {
            return ubicaciones.filter(u => u && typeof u === 'string' && u.trim() !== '');
        }
        
        return [];
    }

    /**
     * Limpiar tallas de proceso
     */
    static sanitizeTallasProceso(tallas) {
        if (!tallas || typeof tallas !== 'object') return { dama: {}, caballero: {} };

        const cleaned = { dama: {}, caballero: {} };

        ['dama', 'caballero'].forEach(genero => {
            const generoTallas = tallas[genero];
            if (generoTallas && typeof generoTallas === 'object' && !Array.isArray(generoTallas)) {
                Object.entries(generoTallas).forEach(([talla, cantidad]) => {
                    const cant = parseInt(cantidad);
                    if (!isNaN(cant) && cant > 0) {
                        cleaned[genero][talla] = cant;
                    }
                });
            }
        });

        return cleaned;
    }

    /**
     * Helpers
     */
    static cleanString(value) {
        if (value === null || value === undefined) return null;
        if (typeof value === 'string') return value.trim() || null;
        return String(value).trim() || null;
    }

    static cleanInt(value) {
        const parsed = parseInt(value);
        return isNaN(parsed) ? null : parsed;
    }
}

/**
 * USO EN FORMULARIO:
 * 
 * import { PedidoSanitizer } from './PedidoSanitizer.js';
 * 
 * async function enviarPedido() {
 *     const dataLimpia = PedidoSanitizer.sanitize(this.pedido);
 *     
 *     const response = await fetch('/api/pedidos', {
 *         method: 'POST',
 *         headers: { 'Content-Type': 'application/json' },
 *         body: JSON.stringify(dataLimpia)
 *     });
 * }
 */
