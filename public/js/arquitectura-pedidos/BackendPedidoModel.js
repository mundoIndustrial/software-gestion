/**
 * BackendPedidoModel.js
 * 
 * MODELO SERIALIZABLE DEL PEDIDO (para enviar al backend)
 * 
 * Características:
 *  100% JSON serializable
 *  Sin File objects
 *  Solo referencias (uid, nombre_archivo)
 *  Normalizado según espera el backend
 * 
 * Se genera a partir de DOMPedidoModel extrayendo solo la metadata
 */

export class BackendPedidoModel {
    constructor() {
        this.cliente = '';
        this.asesora = '';
        this.forma_de_pago = 'Contado';
        this.prendas = [];
        this.epps = [];
    }

    /**
     * Crear modelo backend a partir del modelo DOM
     * 
     * Extrae SOLO metadata, elimina File objects
     */
    static fromDOMPedido(domPedido) {
        const backend = new BackendPedidoModel();
        backend.cliente = domPedido.cliente;
        backend.asesora = domPedido.asesora;
        backend.forma_de_pago = domPedido.forma_de_pago;

        // Convertir prendas (eliminar File objects, mantener referencias)
        backend.prendas = (domPedido.prendas || []).map(prenda => ({
            uid: prenda.uid,
            nombre_prenda: prenda.nombre_prenda,
            cantidad_talla: prenda.cantidad_talla || {},
            variaciones: prenda.variaciones || {},
            
            // Telas: convertir a backend
            telas: (prenda.telas || []).map(tela => ({
                uid: tela.uid,
                tela_id: tela.tela_id,
                color_id: tela.color_id,
                nombre: tela.nombre,
                color: tela.color,
                // Imágenes: SOLO referencias (uid + nombre_archivo)
                imagenes: (tela.imagenes || []).map(img => ({
                    uid: img.uid,
                    nombre_archivo: img.nombre_archivo
                }))
            })),

            // Procesos: convertir a backend
            procesos: (prenda.procesos || []).map(proceso => ({
                uid: proceso.uid,
                nombre: proceso.nombre,
                ubicaciones: proceso.ubicaciones || [],
                observaciones: proceso.observaciones || '',
                tallas: proceso.tallas || {},
                // Imágenes: SOLO referencias
                imagenes: (proceso.imagenes || []).map(img => ({
                    uid: img.uid,
                    nombre_archivo: img.nombre_archivo
                }))
            })),

            // Imágenes de prenda: SOLO referencias
            imagenes: (prenda.imagenes || []).map(img => ({
                uid: img.uid,
                nombre_archivo: img.nombre_archivo
            }))
        }));

        // Convertir EPPs si existen
        backend.epps = (domPedido.epps || []).map(epp => ({
            uid: epp.uid,
            nombre: epp.nombre,
            cantidad: epp.cantidad,
            descripcion: epp.descripcion,
            // Imágenes: SOLO referencias
            imagenes: (epp.imagenes || []).map(img => ({
                uid: img.uid,
                nombre_archivo: img.nombre_archivo
            }))
        }));

        return backend;
    }

    /**
     * Serializar a JSON (seguro para stringify)
     */
    toJSON() {
        return {
            cliente: this.cliente,
            asesora: this.asesora,
            forma_de_pago: this.forma_de_pago,
            prendas: this.prendas,
            epps: this.epps
        };
    }
}
