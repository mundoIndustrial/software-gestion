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
            console.error(` [ItemAPIService] Error HTTP ${respuesta.status}:`, textoError);
            throw new Error(`HTTP error! status: ${respuesta.status}\n${textoError}`);
        }

        try {
            return await respuesta.json();
        } catch (error) {
            console.error(' [ItemAPIService] Error al parsear JSON:', error);
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
            console.error('Error al obtener ítems:', error);
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
            console.error('Error al agregar ítem:', error);
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
            console.error('Error al eliminar ítem:', error);
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
            console.error('Error al renderizar tarjeta:', error);
            throw error;
        }
    }

    /**
     * Validar un pedido completo
     */
    async validarPedido(pedidoData) {
        try {
            console.log('📤 [ItemAPIService] Enviando pedido para validar:', pedidoData);
            return await this.realizarPeticion(`${this.baseUrl}/validar`, {
                method: 'POST',
                body: JSON.stringify(pedidoData)
            });
        } catch (error) {
            console.error(' [ItemAPIService] Error al validar pedido:', error);
            throw error;
        }
    }

    /**
     * Crear un nuevo pedido con FormData para soportar archivos
     */
    async crearPedido(pedidoData) {
        try {
            console.log('📤 [ItemAPIService] Construyendo FormData para pedido:', pedidoData);
            
            const formData = new FormData();
            
            // Agregar datos básicos
            formData.append('cliente', pedidoData.cliente || '');
            formData.append('asesora', pedidoData.asesora || '');
            formData.append('forma_de_pago', pedidoData.forma_de_pago || '');
            
            // Procesar items
            if (pedidoData.items && Array.isArray(pedidoData.items)) {
                pedidoData.items.forEach((item, itemIndex) => {
                    // Agregar datos básicos del item
                    formData.append(`items[${itemIndex}][tipo]`, item.tipo || '');
                    formData.append(`items[${itemIndex}][nombre_prenda]`, item.nombre_prenda || item.nombre_producto || '');
                    formData.append(`items[${itemIndex}][descripcion]`, item.descripcion || '');
                    formData.append(`items[${itemIndex}][origen]`, item.origen || 'bodega');
                    
                    // Agregar cantidad_talla como JSON
                    if (item.cantidad_talla) {
                        formData.append(`items[${itemIndex}][cantidad_talla]`, JSON.stringify(item.cantidad_talla));
                    }
                    
                    // Agregar variaciones como JSON
                    if (item.variaciones) {
                        const variacionesStr = typeof item.variaciones === 'string' 
                            ? item.variaciones 
                            : JSON.stringify(item.variaciones);
                        formData.append(`items[${itemIndex}][variaciones]`, variacionesStr);
                    }
                    
                    // Agregar procesos con sus imágenes
                    if (item.procesos && typeof item.procesos === 'object') {
                        Object.keys(item.procesos).forEach(tipoProceso => {
                            const proceso = item.procesos[tipoProceso];
                            
                            // Extraer datos - pueden estar en .datos o directamente
                            const datosProc = proceso.datos || proceso;
                            
                            // Agregar campos individuales del proceso
                            formData.append(`items[${itemIndex}][procesos][${tipoProceso}][tipo]`, datosProc.tipo || tipoProceso);
                            
                            // Agregar ubicaciones como JSON
                            if (datosProc.ubicaciones) {
                                formData.append(`items[${itemIndex}][procesos][${tipoProceso}][ubicaciones]`, 
                                    typeof datosProc.ubicaciones === 'string' ? datosProc.ubicaciones : JSON.stringify(datosProc.ubicaciones));
                            }
                            
                            // Agregar observaciones
                            if (datosProc.observaciones) {
                                formData.append(`items[${itemIndex}][procesos][${tipoProceso}][observaciones]`, datosProc.observaciones);
                            }
                            
                            // Agregar tallas en formato relacional
                            if (datosProc.tallas) {
                                formData.append(`items[${itemIndex}][procesos][${tipoProceso}][tallas]`, 
                                    JSON.stringify(datosProc.tallas));
                            }
                            
                            // Agregar imágenes del proceso
                            if (datosProc.imagenes && Array.isArray(datosProc.imagenes)) {
                                datosProc.imagenes.forEach((img, imgIdx) => {
                                    let archivo = null;
                                    
                                    // Caso 1: img es File directo
                                    if (img instanceof File) {
                                        archivo = img;
                                    }
                                    // Caso 2: img es {file: File, nombre: string}
                                    else if (img && img.file instanceof File) {
                                        archivo = img.file;
                                    }
                                    
                                    if (archivo) {
                                        formData.append(`items_${itemIndex}_procesos_${tipoProceso}_imagenes_files`, archivo);
                                    }
                                });
                            }
                        });
                    }
                    
                    // Agregar imágenes de prenda
                    console.log(`📸 [ItemAPIService] Item ${itemIndex} tipo:`, item.tipo);
                    console.log(`📸 [ItemAPIService] Item ${itemIndex} imagenes:`, item.imagenes);
                    console.log(`📸 [ItemAPIService] Item ${itemIndex} imagenes es array:`, Array.isArray(item.imagenes));
                    console.log(`📸 [ItemAPIService] Item ${itemIndex} imagenes length:`, item.imagenes?.length);
                    
                    if (item.imagenes && Array.isArray(item.imagenes) && item.imagenes.length > 0) {
                        console.log(`📸 [ItemAPIService]  Agregando ${item.imagenes.length} imágenes de prenda para item ${itemIndex}`);
                        item.imagenes.forEach((img, imgIdx) => {
                            let archivo = null;
                            
                            console.log(`📸 [ItemAPIService] Imagen ${imgIdx}:`, img instanceof File ? 'File' : typeof img);
                            
                            // Caso 1: img es File directo
                            if (img instanceof File) {
                                archivo = img;
                                console.log(` [ItemAPIService] Imagen ${imgIdx} es File directo: ${img.name}`);
                            }
                            // Caso 2: img es {file: File, nombre: string, tamaño: number}
                            else if (img && img.file instanceof File) {
                                archivo = img.file;
                                console.log(` [ItemAPIService] Imagen ${imgIdx} tiene propiedad file: ${img.file.name}`);
                            }
                            // Caso 3: img es {archivo: File, nombre: string, ...} (EPP)
                            else if (img && img.archivo instanceof File) {
                                archivo = img.archivo;
                                console.log(` [ItemAPIService] Imagen ${imgIdx} tiene propiedad archivo: ${img.archivo.name}`);
                            }
                            
                            if (archivo) {
                                console.log(` [ItemAPIService] Agregando archivo a FormData: ${archivo.name}`);
                                formData.append(`items_${itemIndex}_imagenes_files`, archivo);
                            } else {
                                console.warn(`⚠️ [ItemAPIService] Imagen ${imgIdx} no es File object`, img);
                            }
                        });
                    } else {
                        console.log(`⚠️ [ItemAPIService] Item ${itemIndex} NO tiene imagenes válidas`);
                    }
                    
                    // Agregar telas con sus imágenes
                    if (item.telas && Array.isArray(item.telas)) {
                        item.telas.forEach((tela, telaIdx) => {
                            formData.append(`items[${itemIndex}][telas][${telaIdx}][tela]`, tela.tela || '');
                            formData.append(`items[${itemIndex}][telas][${telaIdx}][color]`, tela.color || '');
                            formData.append(`items[${itemIndex}][telas][${telaIdx}][referencia]`, tela.referencia || '');
                            
                            // Agregar imágenes de tela
                            // Las imágenes vienen como {file: File, nombre: string, tamaño: number}
                            if (tela.imagenes && Array.isArray(tela.imagenes)) {
                                tela.imagenes.forEach((img, imgIdx) => {
                                    let archivo = null;
                                    
                                    // Caso 1: img es File directo
                                    if (img instanceof File) {
                                        archivo = img;
                                    }
                                    // Caso 2: img es {file: File, nombre: string, tamaño: number}
                                    else if (img && img.file instanceof File) {
                                        archivo = img.file;
                                    }
                                    
                                    if (archivo) {
                                        formData.append(`items_${itemIndex}_telas_${telaIdx}_imagenes_files`, archivo);
                                    }
                                });
                            }
                        });
                    }
                    
                    // Para EPPs: agregar campos específicos
                    if (item.tipo === 'epp') {
                        formData.append(`items[${itemIndex}][epp_id]`, item.epp_id || '');
                        formData.append(`items[${itemIndex}][nombre]`, item.nombre || '');
                        formData.append(`items[${itemIndex}][codigo]`, item.codigo || '');
                        formData.append(`items[${itemIndex}][categoria]`, item.categoria || '');
                        formData.append(`items[${itemIndex}][cantidad]`, item.cantidad || 0);
                        formData.append(`items[${itemIndex}][observaciones]`, item.observaciones || '');
                        
                        // Agregar imágenes de EPP con clave plana consistente
                        if (item.imagenes && Array.isArray(item.imagenes)) {
                            item.imagenes.forEach((img, imgIdx) => {
                                let archivo = null;
                                
                                // Caso 1: img es File directo
                                if (img instanceof File) {
                                    archivo = img;
                                }
                                // Caso 2: img es {archivo: File, nombre: string, url: string}
                                else if (img && img.archivo instanceof File) {
                                    archivo = img.archivo;
                                }
                                
                                if (archivo) {
                                    formData.append(`items_${itemIndex}_imagenes_files`, archivo);
                                }
                            });
                        }
                    }
                });
            }
            
            console.log('📤 [ItemAPIService] FormData construido, enviando...');
            
            // Loguear claves de FormData para debugging
            console.log('📤 [ItemAPIService] Claves en FormData:');
            for (let [key, value] of formData.entries()) {
                if (value instanceof File) {
                    console.log(`  - ${key}: File(${value.name})`);
                } else {
                    console.log(`  - ${key}: ${typeof value}`);
                }
            }
            
            // Realizar petición sin Content-Type (FormData lo establece automáticamente)
            const respuesta = await fetch(`${this.baseUrl}/crear`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': this.csrfToken
                },
                body: formData
            });
            
            if (!respuesta.ok) {
                const textoError = await respuesta.text();
                console.error(` [ItemAPIService] Error HTTP ${respuesta.status}:`, textoError);
                throw new Error(`HTTP error! status: ${respuesta.status}\n${textoError}`);
            }
            
            return await respuesta.json();
        } catch (error) {
            console.error(' Error al crear pedido:', error);
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
            console.error('Error al actualizar pedido:', error);
            throw error;
        }
    }
}

window.ItemAPIService = ItemAPIService;
