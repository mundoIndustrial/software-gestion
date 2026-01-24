/**
 * ItemAPIService - Servicio de API para Ítems
 * 
 * Responsabilidad única: Comunicación con el backend
 * 
 * Principios SOLID aplicados:
 * - SRP: Solo gestiona llamadas a API
 * - DIP: Puede ser inyectado como dependencia
 * - OCP: Fácil de extender para nuevos endpoints
 */
class ItemAPIService {
    constructor(options = {}) {
        this.baseUrl = options.baseUrl || '/asesores/pedidos-editable';
        this.csrfToken = options.csrfToken || this.obtenerCSRFToken();
    }

    /**
     * Obtener token CSRF del DOM
     */
    obtenerCSRFToken() {
        return document.querySelector('meta[name="csrf-token"]')?.content || '';
    }

    /**
     * Realizar petición HTTP genérica
     * @private
     */
    async realizarPeticion(url, opciones = {}) {
        const configuracion = {
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': this.csrfToken,
                ...opciones.headers
            },
            ...opciones
        };

        const respuesta = await fetch(url, configuracion);
        
        if (!respuesta.ok) {
            // Intentar obtener el texto de error (puede ser HTML o JSON)
            const textoError = await respuesta.text();

            throw new Error(`HTTP error! status: ${respuesta.status}\n${textoError}`);
        }

        try {
            return await respuesta.json();
        } catch (error) {

            throw new Error(`Error al parsear respuesta JSON: ${error.message}`);
        }
    }

    /**
     * Obtener ítems desde el servidor
     */
    async obtenerItems() {
        try {
            return await this.realizarPeticion(`${this.baseUrl}/items`);
        } catch (error) {

            throw error;
        }
    }

    /**
     * Agregar un nuevo ítem
     */
    async agregarItem(itemData) {
        try {
            return await this.realizarPeticion(`${this.baseUrl}/items`, {
                method: 'POST',
                body: JSON.stringify(itemData)
            });
        } catch (error) {

            throw error;
        }
    }

    /**
     * Eliminar un ítem
     */
    async eliminarItem(index) {
        try {
            return await this.realizarPeticion(`${this.baseUrl}/items/${index}`, {
                method: 'DELETE'
            });
        } catch (error) {

            throw error;
        }
    }

    /**
     * Renderizar tarjeta de ítem (HTML)
     */
    async renderizarItemCard(item, index) {
        try {
            return await this.realizarPeticion(`${this.baseUrl}/render-item-card`, {
                method: 'POST',
                body: JSON.stringify({ item, index })
            });
        } catch (error) {

            throw error;
        }
    }

    /**
     * Validar un pedido completo
     */
    async validarPedido(pedidoData) {
        try {
            // Transformar estructura para match backend expectations
            const pedidoParaValidar = {
                cliente: pedidoData.cliente || '',
                asesora: pedidoData.asesora || '',
                forma_de_pago: pedidoData.forma_de_pago || '',
                items: pedidoData.items || []
            };
            
            console.log('[item-api-service] 🔍 Enviando a validar:', {
                cliente: pedidoParaValidar.cliente,
                cantidadItems: pedidoParaValidar.items.length,
                items: pedidoParaValidar.items.map((i, idx) => ({
                    index: idx,
                    nombre: i.nombre_prenda,
                    tieneCantidadTalla: !!i.cantidad_talla
                }))
            });

            return await this.realizarPeticion(`${this.baseUrl}/validar`, {
                method: 'POST',
                body: JSON.stringify(pedidoParaValidar)
            });
        } catch (error) {

            throw error;
        }
    }

    /**
     * Crear un nuevo pedido (JSON)
     * 
     * IMPORTANTE: Usa PayloadSanitizer para limpiar datos reactivos antes de enviar
     */
    async crearPedido(pedidoData) {
        try {
            console.log('[item-api-service] 📦 Creando pedido - Datos originales:', pedidoData);
            
            // ✅ PASO 1: Sanitizar payload (elimina propiedades reactivas, convierte tipos)
            let payloadLimpio;
            if (window.PayloadSanitizer) {
                payloadLimpio = window.PayloadSanitizer.sanitizarPedido(pedidoData);
                console.log('[item-api-service] ✅ Payload sanitizado correctamente');
                
                // Validar payload
                const { valido, errores } = window.PayloadSanitizer.validarPayload(payloadLimpio);
                if (!valido) {
                    console.error('[item-api-service] ❌ Payload inválido:', errores);
                    throw new Error(`Validación fallida: ${errores.join(', ')}`);
                }
            } else {
                console.warn('[item-api-service] ⚠️ PayloadSanitizer no disponible, usando datos sin sanitizar');
                payloadLimpio = pedidoData;
            }
            
            // ✅ PASO 2: Enviar como JSON (más simple y compatible con CrearPedidoCompletoRequest)
            const respuesta = await fetch(`${this.baseUrl}/crear`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken
                },
                body: JSON.stringify(payloadLimpio)
            });
            
            // ✅ PASO 3: Manejar respuesta
            if (!respuesta.ok) {
                const errorData = await respuesta.json().catch(() => ({ message: 'Error desconocido' }));
                console.error('[item-api-service] ❌ Error del servidor:', errorData);
                
                if (respuesta.status === 422 && errorData.errors) {
                    // Validación Laravel fallida
                    const mensajesError = Object.entries(errorData.errors)
                        .map(([campo, mensajes]) => `${campo}: ${mensajes.join(', ')}`)
                        .join('\n');
                    throw new Error(`Validación fallida:\n${mensajesError}`);
                }
                
                throw new Error(errorData.message || `HTTP error! status: ${respuesta.status}`);
            }
            
            const resultado = await respuesta.json();
            console.log('[item-api-service] ✅ Pedido creado exitosamente:', resultado);
            
            return resultado;
            
        } catch (error) {
            console.error('[item-api-service] ❌ Error al crear pedido:', error);
            throw error;
        }
    }

    /**
     * Actualizar un pedido existente
     */
    async actualizarPedido(pedidoId, pedidoData) {
        try {
            return await this.realizarPeticion(`${this.baseUrl}/${pedidoId}`, {
                method: 'PUT',
                body: JSON.stringify(pedidoData)
            });
        } catch (error) {

            throw error;
        }
    }
}

window.ItemAPIService = ItemAPIService;
