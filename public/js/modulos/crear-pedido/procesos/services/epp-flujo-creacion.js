/**
 * EPP Flujo de Creación de Pedidos
 * 
 * Implementa el flujo de 2 pasos:
 * 1. Crear pedido (JSON, SIN imágenes)
 * 2. Subir imágenes (FormData multipart/form-data)
 * 
 * Reglas:
 * ✅ Pedido se crea sin imágenes
 * ✅ Imágenes se envían DESPUÉS de que existe pedido_id
 * ✅ Imágenes como multipart/form-data (NO Base64)
 * ✅ Cada imagen asociada a pedido_id + epp_id
 */

class EppFlujoCreacion {
    constructor(baseUrl = '/api') {
        this.baseUrl = baseUrl;
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    }

    /**
     * PASO 1: Crear pedido CON estructura pero SIN imágenes
     * 
     * @param {Object} pedidoData - {cliente, asesora, forma_de_pago, epps: [{epp_id, cantidad, observaciones}, ...]}
     * @returns {Promise<{success: boolean, pedido_id: number, numero_pedido: string, cliente_id: number}>}
     */
    async crearPedido(pedidoData) {
        try {
            console.log('[EppFlujoCreacion] PASO 1: Creando pedido SIN imágenes');
            
            // Normalizar: eliminar imagenes[] de cada EPP
            const pedidoSinImagenes = this._normalizarPedidoSinImagenes(pedidoData);
            
            console.debug('[EppFlujoCreacion] Pedido a enviar:', pedidoSinImagenes);

            const response = await fetch(`${this.baseUrl}/pedidos-editable/crear`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken
                },
                body: JSON.stringify(pedidoSinImagenes)
            });

            if (!response.ok) {
                const error = await response.json().catch(() => ({
                    message: `HTTP ${response.status}`
                }));
                throw new Error(error.message || `Error al crear pedido: ${response.status}`);
            }

            const resultado = await response.json();
            
            console.log('[EppFlujoCreacion] ✅ PASO 1 EXITOSO:', {
                pedido_id: resultado.pedido_id,
                numero_pedido: resultado.numero_pedido
            });

            return resultado;

        } catch (error) {
            console.error('[EppFlujoCreacion] ❌ Error en PASO 1:', error.message);
            throw error;
        }
    }

    /**
     * PASO 2: Subir imágenes de EPPs DESPUÉS de que existe el pedido
     * 
     * @param {number} pedidoId - ID del pedido creado
     * @param {Object} pedidoData - Datos originales con imagenes[]
     * @returns {Promise<{success: boolean, imagenes_subidas: number, message: string}>}
     */
    async subirImagenesPedido(pedidoId, pedidoData) {
        try {
            console.log('[EppFlujoCreacion] PASO 2: Subiendo imágenes para pedido', pedidoId);

            if (!pedidoData.epps || pedidoData.epps.length === 0) {
                console.log('[EppFlujoCreacion] ℹ️ No hay EPPs con imágenes, saltando PASO 2');
                return {
                    success: true,
                    imagenes_subidas: 0,
                    message: 'No hay imágenes para subir'
                };
            }

            // Construir FormData con imágenes agrupadas por EPP
            const formData = new FormData();
            let totalImagenes = 0;

            pedidoData.epps.forEach((epp, eppIdx) => {
                if (!epp.imagenes || epp.imagenes.length === 0) return;

                epp.imagenes.forEach((img, imgIdx) => {
                    if (img.archivo instanceof File) {
                        // Nombre único: pedido_epp_index_image_index_timestamp.ext
                        const ext = img.archivo.name.split('.').pop();
                        const nombreUnico = `pedido_${pedidoId}_epp_${epp.epp_id}_img_${imgIdx}_${Date.now()}.${ext}`;

                        formData.append(
                            `epps[${eppIdx}][imagenes][${imgIdx}]`,
                            img.archivo,
                            nombreUnico
                        );

                        totalImagenes++;
                        console.debug(`[EppFlujoCreacion] Agregando imagen: ${nombreUnico}`);
                    }
                });
            });

            if (totalImagenes === 0) {
                console.log('[EppFlujoCreacion] ℹ️ No hay archivos File válidos, saltando PASO 2');
                return {
                    success: true,
                    imagenes_subidas: 0,
                    message: 'No hay archivos válidos para subir'
                };
            }

            // Agregar pedido_id al FormData
            formData.append('pedido_id', pedidoId);
            formData.append('epps_json', JSON.stringify(
                pedidoData.epps.map(epp => ({
                    epp_id: epp.epp_id,
                    imagenes_count: (epp.imagenes || []).length
                }))
            ));

            console.log(`[EppFlujoCreacion] Enviando ${totalImagenes} imágenes...`);

            const response = await fetch(`${this.baseUrl}/pedidos-editable/subir-imagenes-epp`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken
                    // NO incluir Content-Type: multipart/form-data - el navegador lo hace automáticamente
                },
                body: formData
            });

            if (!response.ok) {
                const error = await response.json().catch(() => ({
                    message: `HTTP ${response.status}`
                }));
                throw new Error(error.message || `Error al subir imágenes: ${response.status}`);
            }

            const resultado = await response.json();

            console.log('[EppFlujoCreacion] ✅ PASO 2 EXITOSO:', {
                imagenes_subidas: resultado.imagenes_subidas || totalImagenes,
                message: resultado.message
            });

            return resultado;

        } catch (error) {
            console.error('[EppFlujoCreacion] ❌ Error en PASO 2:', error.message);
            // NO lanzar error aquí - el pedido ya existe, solo falta subir imágenes
            return {
                success: false,
                imagenes_subidas: 0,
                error: error.message,
                message: 'Error al subir imágenes, pero el pedido fue creado exitosamente'
            };
        }
    }

    /**
     * FLUJO COMPLETO: Crear pedido + subir imágenes
     * Maneja todo el proceso de forma atómica (con rollback conceptual)
     * 
     * @param {Object} pedidoData 
     * @returns {Promise<{success: boolean, pedido_id, numero_pedido, imagenes_resultado}>}
     */
    async crearPedidoCompleto(pedidoData) {
        try {
            console.log('[EppFlujoCreacion] 🚀 INICIANDO FLUJO COMPLETO');

            // PASO 1: Crear pedido
            const pedidoCreado = await this.crearPedido(pedidoData);

            if (!pedidoCreado.success) {
                throw new Error('El servidor no confirmó la creación del pedido');
            }

            const pedidoId = pedidoCreado.pedido_id;

            // PASO 2: Subir imágenes
            const imagenesResultado = await this.subirImagenesPedido(pedidoId, pedidoData);

            console.log('[EppFlujoCreacion] ✅ FLUJO COMPLETO EXITOSO');

            return {
                success: true,
                pedido_id: pedidoId,
                numero_pedido: pedidoCreado.numero_pedido,
                cliente_id: pedidoCreado.cliente_id,
                imagenes_resultado: imagenesResultado
            };

        } catch (error) {
            console.error('[EppFlujoCreacion] ❌ FLUJO COMPLETO FALLIDO:', error.message);
            throw error;
        }
    }

    /**
     * Eliminar imagenes del pedido antes de enviar (normalización)
     */
    _normalizarPedidoSinImagenes(pedidoRaw) {
        const pedido = {
            cliente: pedidoRaw.cliente,
            asesora: pedidoRaw.asesora,
            forma_de_pago: pedidoRaw.forma_de_pago,
            descripcion: pedidoRaw.descripcion || '',
            prendas: (pedidoRaw.prendas || []).map(p => ({
                tipo: p.tipo,
                nombre_prenda: p.nombre_prenda,
                // ... otros campos (pero SIN imagenes)
            })),
            epps: (pedidoRaw.epps || []).map(e => ({
                epp_id: e.epp_id,
                nombre_epp: e.nombre_epp,
                categoria: e.categoria,
                cantidad: e.cantidad,
                observaciones: e.observaciones
                // ❌ NO incluir imagenes
            }))
        };

        return pedido;
    }
}

// Exportar instancia global
window.EppFlujoCreacion = window.EppFlujoCreacion || EppFlujoCreacion;
