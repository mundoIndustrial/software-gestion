/**
 * PedidoService.js
 * 
 * Orquesta el flujo completo de creación de pedido:
 * 1. Construir modelo DOM (editable con File objects)
 * 2. Validar datos
 * 3. Convertir a modelo backend (solo referencias)
 * 4. Construir FormData (metadata + archivos)
 * 5. Enviar al backend
 * 
 * EJEMPLO DE USO:
 * 
 * const service = new PedidoService();
 * const resultado = await service.crearPedido({
 *     cliente: "Acme Corp",
 *     asesora: "María",
 *     forma_de_pago: "Contado",
 *     prendas: [{ nombre_prenda: "Camisa", telas: [...], procesos: [...] }],
 *     archivos: {
 *         prendas: { "0": { imagenes: [File, File] } },
 *         telas: { "0-0": { imagenes: [File] } }
 *     }
 * });
 */

import { DOMPedidoModel } from './DOMPedidoModel.js';
import { BackendPedidoModel } from './BackendPedidoModel.js';
import { PedidoFormDataBuilder } from './PedidoFormDataBuilder.js';

export class PedidoService {
    constructor(endpoint = '/asesores/pedidos-editable/crear') {
        this.endpoint = endpoint;
    }

    /**
     * Crear pedido completo
     * 
     * @param {Object} datosUI - Datos del formulario UI
     * @returns {Promise} Respuesta del servidor
     */
    async crearPedido(datosUI) {
        try {
            // PASO 1: Validar datos básicos
            this.validarDatos(datosUI);

            // PASO 2: Construir modelo DOM (editable)
            const domPedido = this.construirDOMPedido(datosUI);

            // PASO 3: Convertir a modelo backend (serializable)
            const backendPedido = BackendPedidoModel.fromDOMPedido(domPedido);

            // PASO 4: Construir FormData (metadata + archivos)
            const formData = this.construirFormData(domPedido, backendPedido);

            // PASO 5: Enviar al backend
            const respuesta = await this.enviarAlBackend(formData);

            return respuesta;

        } catch (error) {
            console.error('Error en PedidoService.crearPedido:', error);
            throw error;
        }
    }

    /**
     * Validar datos básicos del pedido
     */
    validarDatos(datos) {
        if (!datos.cliente || datos.cliente.trim() === '') {
            throw new Error('El cliente es requerido');
        }

        if (!datos.prendas || datos.prendas.length === 0) {
            throw new Error('Debe agregar al menos una prenda');
        }

        datos.prendas.forEach((prenda, idx) => {
            if (!prenda.nombre_prenda || prenda.nombre_prenda.trim() === '') {
                throw new Error(`Prenda ${idx + 1}: el nombre es requerido`);
            }

            // Validar que tenga imágenes, telas, o procesos
            const tieneContenido =
                (prenda.imagenes && prenda.imagenes.length > 0) ||
                (prenda.telas && prenda.telas.length > 0) ||
                (prenda.procesos && prenda.procesos.length > 0);

            if (!tieneContenido) {
                throw new Error(`Prenda "${prenda.nombre_prenda}": debe agregar imágenes, telas o procesos`);
            }
        });
    }

    /**
     * Construir modelo DOM desde datos del UI
     */
    construirDOMPedido(datos) {
        const domPedido = new DOMPedidoModel();
        domPedido.cliente = datos.cliente;
        domPedido.asesora = datos.asesora || '';
        domPedido.forma_de_pago = datos.forma_de_pago || 'Contado';

        // Agregar prendas
        if (datos.prendas) {
            datos.prendas.forEach(prendaUI => {
                const prenda = {
                    uid: prendaUI.uid || this.generarUID(),
                    nombre_prenda: prendaUI.nombre_prenda,
                    cantidad_talla: prendaUI.cantidad_talla || {},
                    variaciones: prendaUI.variaciones || {},
                    telas: prendaUI.telas || [],
                    procesos: prendaUI.procesos || [],
                    imagenes: prendaUI.imagenes || []
                };

                domPedido.agregarPrenda(prenda);
            });
        }

        // Agregar EPPs
        if (datos.epps) {
            domPedido.epps = datos.epps.map(eppUI => ({
                uid: eppUI.uid || this.generarUID(),
                nombre: eppUI.nombre,
                cantidad: eppUI.cantidad,
                imagenes: eppUI.imagenes || []
            }));
        }

        return domPedido;
    }

    /**
     * Construir FormData
     */
    construirFormData(domPedido, backendPedido) {
        const builder = new PedidoFormDataBuilder(domPedido);

        // Agregar JSON metadata
        builder.agregarPedidoJSON(backendPedido);

        // Agregar todos los archivos
        builder.agregarTodasLasImagenes();

        return builder.construir();
    }

    /**
     * Enviar FormData al backend
     */
    async enviarAlBackend(formData) {
        const response = await fetch(this.endpoint, {
            method: 'POST',
            body: formData
            // NO agregar Content-Type: FormData lo maneja automáticamente
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.message || 'Error al crear pedido');
        }

        return data;
    }

    /**
     * Generar UID único
     */
    generarUID() {
        return `${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
    }
}
