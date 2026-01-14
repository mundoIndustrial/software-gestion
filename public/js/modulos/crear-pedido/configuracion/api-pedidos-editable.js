/**
 * Pedidos Editable Web Client
 * Comunicación con rutas web tradicionales (no API REST)
 * Arquitectura: Web tradicional + JSON responses
 */

class PedidosEditableWebClient {
    constructor(baseUrl = '/asesores/pedidos-editable') {
        this.baseUrl = baseUrl;
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    }

    /**
     * Agregar un ítem al pedido
     */
    async agregarItem(itemData) {
        try {
            const response = await fetch(`${this.baseUrl}/items/agregar`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                },
                body: JSON.stringify(itemData),
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'Error al agregar ítem');
            }

            return data;
        } catch (error) {
            console.error('❌ Error en agregarItem:', error);
            throw error;
        }
    }

    /**
     * Eliminar un ítem del pedido
     */
    async eliminarItem(index) {
        try {
            const response = await fetch(`${this.baseUrl}/items/eliminar`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                },
                body: JSON.stringify({ index }),
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'Error al eliminar ítem');
            }

            return data;
        } catch (error) {
            console.error('❌ Error en eliminarItem:', error);
            throw error;
        }
    }

    /**
     * Obtener todos los ítems del pedido
     */
    async obtenerItems() {
        try {
            const response = await fetch(`${this.baseUrl}/items`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                },
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'Error al obtener ítems');
            }

            return data;
        } catch (error) {
            console.error('❌ Error en obtenerItems:', error);
            throw error;
        }
    }

    /**
     * Validar el pedido antes de crear
     */
    async validarPedido(pedidoData) {
        try {
            const response = await fetch(`${this.baseUrl}/validar`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                },
                body: JSON.stringify({
                    items: pedidoData?.items || [],
                }),
            });

            const data = await response.json();

            if (!response.ok) {
                return {
                    valid: false,
                    errores: data.errores || [data.message],
                };
            }

            return data;
        } catch (error) {
            console.error('❌ Error en validarPedido:', error);
            return {
                valid: false,
                errores: ['Error al validar el pedido'],
            };
        }
    }

    /**
     * Crear el pedido
     */
    async crearPedido(pedidoData) {
        try {
            console.log('📤 Enviando pedido:', pedidoData);
            
            const response = await fetch(`${this.baseUrl}/crear`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                },
                body: JSON.stringify(pedidoData),
            });

            const data = await response.json();

            if (!response.ok) {
                // Mostrar errores detallados
                console.error('❌ Errores del servidor:', data.errores || data.message);
                console.error('📋 Respuesta completa:', data);
                
                if (data.errores && typeof data.errores === 'object') {
                    // Errores por campo
                    Object.entries(data.errores).forEach(([field, messages]) => {
                        console.error(`  ⚠️ ${field}:`, messages);
                    });
                }
                
                throw new Error(data.message || data.errores?.join(', ') || 'Error al crear pedido');
            }

            return data;
        } catch (error) {
            console.error('❌ Error en crearPedido:', error);
            throw error;
        }
    }

    /**
     * Subir imágenes de prenda via FormData
     * POST /pedidos-editable/subir-imagenes
     * @param {File[]} archivos - Array de archivos de imagen
     * @param {string} numeroPedido - Número del pedido (temporal o para identificar)
     * @returns {Promise<Object>} - { rutas: [...] }
     */
    async subirImagenesPrenda(archivos, numeroPedido) {
        try {
            if (!archivos || archivos.length === 0) {
                return { rutas: [] };
            }

            const formData = new FormData();
            formData.append('numero_pedido', numeroPedido);
            
            archivos.forEach((archivo, index) => {
                if (archivo instanceof File) {
                    formData.append(`imagenes[${index}]`, archivo);
                }
            });

            const response = await fetch(`${this.baseUrl}/subir-imagenes`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': this.csrfToken,
                },
                body: formData,
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'Error al subir imágenes');
            }

            console.log('✅ Imágenes subidas correctamente:', data.rutas);
            return data;
        } catch (error) {
            console.error('❌ Error en subirImagenesPrenda:', error);
            throw error;
        }
    }
}

// Instancia global
window.pedidosAPI = new PedidosEditableWebClient();
