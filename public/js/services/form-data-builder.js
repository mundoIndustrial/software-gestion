/**
 * FormDataBuilder - Unificar envío de payloads complejos
 * 
 * TODO ENVÍO como FormData (nunca JSON puro)
 * 
 * Estructura:
 * - pedido: JSON stringified (metadata sin archivos)
 * - files_prenda_0_0: File (primera imagen de primera prenda)
 * - files_tela_0_0_0: File (primera imagen de primera tela de primera prenda)
 * - files_epp_0_0: File (primera imagen de primer EPP)
 */
class FormDataBuilder {
    /**
     * Construir FormData desde payload completo
     * @param {Object} payload - { cliente, items, epps }
     * @returns {FormData}
     */
    static build(payload) {
        const formData = new FormData();
        
        // 1. PROCESAR PAYLOAD
        const payloadLimpio = this._extractFilesFromPayload(payload);
        
        // 2. AGREGAR JSON METADATA
        formData.append('pedido', JSON.stringify(payloadLimpio.metadata));
        
        // 3. AGREGAR ARCHIVOS
        for (const [key, file] of Object.entries(payloadLimpio.files)) {
            if (file instanceof File || file instanceof Blob) {
                formData.append(key, file);
            }
        }
        
        console.log('[FormDataBuilder] FormData construido:', {
            metadata_size: JSON.stringify(payloadLimpio.metadata).length,
            archivos_count: Object.keys(payloadLimpio.files).length
        });
        
        return formData;
    }
    
    /**
     * Extraer archivos del payload, dejar UIDs en metadata
     * @private
     */
    static _extractFilesFromPayload(payload) {
        const files = {};
        const metadata = JSON.parse(JSON.stringify(payload)); // Deep clone metadata
        const uuidToFormKey = {}; // Mapeo: uuid → key en FormData
        
        // Procesar prendas
        if (metadata.items && Array.isArray(metadata.items)) {
            metadata.items.forEach((item, prendaIdx) => {
                // Generar UID único para la prenda si no existe
                if (!item.uid) {
                    item.uid = this._generateUUID();
                }
                
                // Imágenes de la prenda
                if (item.imagenes && Array.isArray(item.imagenes)) {
                    item.imagenes.forEach((img, imgIdx) => {
                        if (img instanceof File) {
                            // Generar UID para imagen
                            const imgUID = this._generateUUID();
                            const formKey = `files_prenda_${prendaIdx}_${imgIdx}`;
                            
                            files[formKey] = img;
                            uuidToFormKey[imgUID] = formKey;
                            
                            // Guardar: {uid, nombre_archivo} (NO el File)
                            item.imagenes[imgIdx] = {
                                uid: imgUID,
                                nombre_archivo: img.name,
                                formdata_key: formKey  // Para resolver luego
                            };
                        }
                    });
                }
                
                // Imágenes de telas
                if (item.telas && Array.isArray(item.telas)) {
                    item.telas.forEach((tela, telaIdx) => {
                        // Generar UID para tela si no existe
                        if (!tela.uid) {
                            tela.uid = this._generateUUID();
                        }
                        
                        if (tela.imagenes && Array.isArray(tela.imagenes)) {
                            tela.imagenes.forEach((img, imgIdx) => {
                                if (img instanceof File) {
                                    const imgUID = this._generateUUID();
                                    const formKey = `files_tela_${prendaIdx}_${telaIdx}_${imgIdx}`;
                                    
                                    files[formKey] = img;
                                    uuidToFormKey[imgUID] = formKey;
                                    
                                    tela.imagenes[imgIdx] = {
                                        uid: imgUID,
                                        nombre_archivo: img.name,
                                        formdata_key: formKey
                                    };
                                }
                            });
                        }
                    });
                }
                
                // Imágenes de procesos
                if (item.procesos && typeof item.procesos === 'object') {
                    Object.keys(item.procesos).forEach((procesoIdx) => {
                        const proceso = item.procesos[procesoIdx];
                        
                        if (!proceso.uid) {
                            proceso.uid = this._generateUUID();
                        }
                        
                        if (proceso.imagenes && Array.isArray(proceso.imagenes)) {
                            proceso.imagenes.forEach((img, imgIdx) => {
                                if (img instanceof File) {
                                    const imgUID = this._generateUUID();
                                    const formKey = `files_proceso_${prendaIdx}_${procesoIdx}_${imgIdx}`;
                                    
                                    files[formKey] = img;
                                    uuidToFormKey[imgUID] = formKey;
                                    
                                    proceso.imagenes[imgIdx] = {
                                        uid: imgUID,
                                        nombre_archivo: img.name,
                                        formdata_key: formKey
                                    };
                                }
                            });
                        }
                    });
                }
            });
        }
        
        // Procesar EPPs
        if (metadata.epps && Array.isArray(metadata.epps)) {
            metadata.epps.forEach((epp, eppIdx) => {
                if (!epp.uid) {
                    epp.uid = this._generateUUID();
                }
                
                if (epp.imagenes && Array.isArray(epp.imagenes)) {
                    epp.imagenes.forEach((img, imgIdx) => {
                        if (img instanceof File) {
                            const imgUID = this._generateUUID();
                            const formKey = `files_epp_${eppIdx}_${imgIdx}`;
                            
                            files[formKey] = img;
                            uuidToFormKey[imgUID] = formKey;
                            
                            epp.imagenes[imgIdx] = {
                                uid: imgUID,
                                nombre_archivo: img.name,
                                formdata_key: formKey
                            };
                        }
                    });
                }
            });
        }
        
        // Guardar mapeo en metadata para backend
        metadata._uuid_to_formkey = uuidToFormKey;
        
        return { metadata, files };
    }
    
    /**
     * Generar UUID v4 simple
     * @private
     */
    static _generateUUID() {
        return 'uuid-' + Math.random().toString(36).substr(2, 9) + '-' + Date.now().toString(36);
    }
    
    /**
     * Enviar FormData a endpoint
     * @param {FormData} formData
     * @param {string} url
     * @returns {Promise}
     */
    static async send(formData, url) {
        const response = await fetch(url, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                // NO: 'Content-Type': 'multipart/form-data' - el navegador lo pone automático
            }
        });
        
        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.message || 'Error al enviar');
        }
        
        return response.json();
    }
}

window.FormDataBuilder = FormDataBuilder;
