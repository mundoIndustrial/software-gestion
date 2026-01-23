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

            return {
                valid: false,
                errores: ['Error al validar el pedido'],
            };
        }
    }

    /**
     * Crear el pedido
     *  CRÍTICO: Usar FormData para soportar archivos de procesos
     */
    async crearPedido(pedidoData) {
        try {

            
            // Detectar si hay procesos con archivos
            const tieneArchivos = this.tieneArchivosEnPedido(pedidoData);

            
            let fetchConfig;
            
            if (tieneArchivos) {
                //  Usar FormData cuando hay archivos (procesos, imágenes, etc)
                const formData = this.convertirPedidoAFormData(pedidoData);
                fetchConfig = {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': this.csrfToken,
                        //  NO incluir Content-Type: FormData lo establece automáticamente
                    },
                    body: formData,
                };

            } else {
                //  Usar JSON cuando no hay archivos (más simple)
                fetchConfig = {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                    },
                    body: JSON.stringify(pedidoData),
                };

            }
            
            const response = await fetch(`${this.baseUrl}/crear`, fetchConfig);

            const data = await response.json();

            if (!response.ok) {
                // Mostrar errores detallados


                
                if (data.errores && typeof data.errores === 'object') {
                    // Errores por campo
                    Object.entries(data.errores).forEach(([field, messages]) => {

                    });
                }
                
                throw new Error(data.message || data.errores?.join(', ') || 'Error al crear pedido');
            }

            return data;
        } catch (error) {

            throw error;
        }
    }

    /**     *  DETECTAR SI PEDIDO CONTIENE ARCHIVOS
     * Revisa si hay procesos con imagenes que sean File objects
     */
    tieneArchivosEnPedido(pedidoData) {
        if (!pedidoData.items) return false;
        
        for (const item of pedidoData.items) {
            // 1. Revisar procesos
            if (item.procesos && typeof item.procesos === 'object') {
                for (const [tipoProceso, procesoData] of Object.entries(item.procesos)) {
                    if (procesoData?.imagenes && Array.isArray(procesoData.imagenes)) {
                        for (const img of procesoData.imagenes) {
                            if (img instanceof File) {

                                return true;
                            }
                        }
                    }
                }
            }
            
            // 2. Revisar imágenes de prenda
            if (item.imagenes && Array.isArray(item.imagenes)) {
                for (const imgObj of item.imagenes) {
                    // Las imágenes pueden ser File directo o objeto con propiedad 'file'
                    if (imgObj instanceof File) {

                        return true;
                    } else if (imgObj.file instanceof File) {

                        return true;
                    }
                }
            }
            
            // 3. Revisar imágenes de telas
            if (item.telas && Array.isArray(item.telas)) {
                for (const tela of item.telas) {
                    if (tela.imagenes && Array.isArray(tela.imagenes)) {
                        for (const telaImgObj of tela.imagenes) {
                            if (telaImgObj instanceof File) {

                                return true;
                            } else if (telaImgObj.file instanceof File) {

                                return true;
                            }
                        }
                    }
                }
            }
        }
        
        return false;
    }

    /**
     *  CONVERTIR PEDIDO A FormData
     * Maneja archivos de procesos, imágenes de prendas, etc.
     */
    convertirPedidoAFormData(pedidoData) {
        const formData = new FormData();
        
        // Agregar datos básicos del pedido
        formData.append('cliente', pedidoData.cliente);
        formData.append('asesora', pedidoData.asesora);
        formData.append('forma_de_pago', pedidoData.forma_de_pago);
        
        // Procesar items
        if (pedidoData.items && Array.isArray(pedidoData.items)) {
            pedidoData.items.forEach((item, itemIdx) => {
                //  SI ES EPP, PROCESARLO DIFERENTE
                if (item.tipo === 'epp') {

                    
                    // Usar 'items' en lugar de 'prendas' para EPP
                    formData.append(`items[${itemIdx}][tipo]`, 'epp');
                    formData.append(`items[${itemIdx}][epp_id]`, item.epp_id || '');
                    formData.append(`items[${itemIdx}][nombre]`, item.nombre || '');
                    formData.append(`items[${itemIdx}][codigo]`, item.codigo || '');
                    formData.append(`items[${itemIdx}][categoria]`, item.categoria || '');
                    formData.append(`items[${itemIdx}][cantidad]`, item.cantidad || '');
                    formData.append(`items[${itemIdx}][observaciones]`, item.observaciones || '');
                    
                    // Imágenes del EPP
                    if (item.imagenes && Array.isArray(item.imagenes)) {
                        item.imagenes.forEach((imgObj, imgIdx) => {
                            const archivo = imgObj instanceof File ? imgObj : imgObj?.file;
                            if (archivo instanceof File) {
                                formData.append(
                                    `items[${itemIdx}][imagenes][${imgIdx}]`,
                                    archivo
                                );

                            }
                        });
                    }
                    
                    return; // Saltar al siguiente item
                }
                
                // PARA PRENDAS: Datos básicos del item
                formData.append(`prendas[${itemIdx}][tipo]`, item.tipo || 'prenda_nueva');
                formData.append(`prendas[${itemIdx}][prenda]`, item.prenda || '');
                formData.append(`prendas[${itemIdx}][descripcion]`, item.descripcion || '');
                formData.append(`prendas[${itemIdx}][origen]`, item.origen || 'bodega');
                formData.append(`prendas[${itemIdx}][de_bodega]`, item.de_bodega ? 1 : 0);
                formData.append(`prendas[${itemIdx}][genero]`, JSON.stringify(item.genero || []));
                
                // Variaciones
                if (item.variaciones) {
                    formData.append(`prendas[${itemIdx}][variaciones]`, JSON.stringify(item.variaciones));
                }
                
                // Observaciones
                if (item.obs_manga) formData.append(`prendas[${itemIdx}][obs_manga]`, item.obs_manga);
                if (item.obs_bolsillos) formData.append(`prendas[${itemIdx}][obs_bolsillos]`, item.obs_bolsillos);
                if (item.obs_broche) formData.append(`prendas[${itemIdx}][obs_broche]`, item.obs_broche);
                if (item.obs_reflectivo) formData.append(`prendas[${itemIdx}][obs_reflectivo]`, item.obs_reflectivo);
                
                // Cantidades/Tallas
                if (item.cantidad_talla) {
                    formData.append(`prendas[${itemIdx}][cantidad_talla]`, JSON.stringify(item.cantidad_talla));
                }
                if (item.tallas) {
                    formData.append(`prendas[${itemIdx}][tallas]`, JSON.stringify(item.tallas));
                }
                
                // IDs de variaciones
                if (item.color_id) formData.append(`prendas[${itemIdx}][color_id]`, item.color_id);
                if (item.tela_id) formData.append(`prendas[${itemIdx}][tela_id]`, item.tela_id);
                if (item.tipo_manga_id) formData.append(`prendas[${itemIdx}][tipo_manga_id]`, item.tipo_manga_id);
                if (item.tipo_broche_boton_id) formData.append(`prendas[${itemIdx}][tipo_broche_boton_id]`, item.tipo_broche_boton_id);
                
                // Nombres de variaciones (para crear si no existen)
                if (item.color) formData.append(`prendas[${itemIdx}][color]`, item.color);
                if (item.tela) formData.append(`prendas[${itemIdx}][tela]`, item.tela);
                if (item.manga) formData.append(`prendas[${itemIdx}][manga]`, item.manga);
                if (item.broche) formData.append(`prendas[${itemIdx}][broche]`, item.broche);
                
                //  PROCESOS CON IMÁGENES
                if (item.procesos && typeof item.procesos === 'object') {
                    Object.entries(item.procesos).forEach(([tipoProceso, procesoData]) => {
                        if (procesoData) {
                            // Datos del proceso
                            if (procesoData.tipo) {
                                formData.append(
                                    `prendas[${itemIdx}][procesos][${tipoProceso}][tipo]`,
                                    procesoData.tipo
                                );
                            }
                            if (procesoData.ubicaciones) {
                                formData.append(
                                    `prendas[${itemIdx}][procesos][${tipoProceso}][ubicaciones]`,
                                    JSON.stringify(procesoData.ubicaciones)
                                );
                            }
                            if (procesoData.observaciones) {
                                formData.append(
                                    `prendas[${itemIdx}][procesos][${tipoProceso}][observaciones]`,
                                    procesoData.observaciones
                                );
                            }
                            // Estructura relacional: { DAMA: {S: 5}, CABALLERO: {M: 3} }
                            if (procesoData.tallas) {
                                formData.append(
                                    `prendas[${itemIdx}][procesos][${tipoProceso}][tallas]`,
                                    JSON.stringify(procesoData.tallas)
                                );
                            }
                            
                            //  IMÁGENES DEL PROCESO
                            if (procesoData.imagenes && Array.isArray(procesoData.imagenes)) {
                                procesoData.imagenes.forEach((img, imgIdx) => {
                                    // Soportar ambos formatos: File directo o {file: File, ...}
                                    const archivo = img instanceof File ? img : img?.file;
                                    if (archivo instanceof File) {
                                        formData.append(
                                            `prendas[${itemIdx}][procesos][${tipoProceso}][imagenes][${imgIdx}]`,
                                            archivo
                                        );

                                    }
                                });
                            }
                        }
                    });
                }
                
                //  IMÁGENES DE PRENDA
                if (item.imagenes && Array.isArray(item.imagenes)) {
                    item.imagenes.forEach((imgObj, imgIdx) => {
                        // Soportar ambos formatos: File directo o {file: File, ...}
                        const archivo = imgObj instanceof File ? imgObj : imgObj?.file;
                        if (archivo instanceof File) {
                            formData.append(
                                `prendas[${itemIdx}][imagenes][${imgIdx}]`,
                                archivo
                            );

                        }
                    });
                }
                
                //  IMÁGENES DE TELAS
                if (item.telas && Array.isArray(item.telas)) {
                    item.telas.forEach((tela, telaIdx) => {
                        if (tela.imagenes && Array.isArray(tela.imagenes)) {
                            tela.imagenes.forEach((telaImgObj, imgIdx) => {
                                // Soportar ambos formatos: File directo o {file: File, ...}
                                const archivo = telaImgObj instanceof File ? telaImgObj : telaImgObj?.file;
                                if (archivo instanceof File) {
                                    formData.append(
                                        `prendas[${itemIdx}][telas][${telaIdx}][imagenes][${imgIdx}]`,
                                        archivo
                                    );

                                }
                            });
                        }
                    });
                }
            });
        }
        
        return formData;
    }

    /**     * Subir imágenes de prenda via FormData
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


            return data;
        } catch (error) {

            throw error;
        }
    }
}

// Instancia global
window.pedidosAPI = new PedidosEditableWebClient();
