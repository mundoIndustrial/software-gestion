/**
 * payload-normalizer-epp-correcto.js
 * 
 *  VERSIÓN CORRECTA PARA ENVÍO DE IMÁGENES DE EPP
 * 
 * Extrae archivos del payload y construye FormData correctamente
 * Las imágenes NO viajan en JSON, viajan en FormData como archivos reales
 */

class PayloadNormalizerEpp {
    /**
     *  Extraer File objects del payload
     * Retorna un objeto limpio (sin Files) y los archivos por separado
     */
    static extraerArchivos(pedidoData) {
        const archivos = {
            prendas: [],
            telas: [],
            procesos: [],
            epps: []
        };

        // Deep clone para no modificar original
        const pedidoLimpio = JSON.parse(JSON.stringify(pedidoData));

        // ========== EPP ==========
        if (pedidoLimpio.epps && Array.isArray(pedidoLimpio.epps)) {
            pedidoLimpio.epps.forEach((epp, eppIdx) => {
                if (epp.imagenes && Array.isArray(epp.imagenes)) {
                    const imagenesValidas = [];
                    
                    epp.imagenes.forEach((img, imgIdx) => {
                        // Aceptar dos formatos:
                        // 1. img es File directo
                        // 2. img es {file: File, preview: URL}
                        const archivo = this._extraerFile(img);
                        
                        if (archivo instanceof File) {
                            archivos.epps.push({
                                eppIdx,
                                imgIdx,
                                epp_id: epp.epp_id,
                                file: archivo
                            });
                            // No incluir en JSON
                        } else {
                            // Guardar referencias no-File
                            imagenesValidas.push(img);
                        }
                    });
                    
                    // Reemplazar imagenes solo con las referencias
                    epp.imagenes = imagenesValidas;
                }
            });
        }

        // ========== PRENDAS ==========
        if (pedidoLimpio.prendas && Array.isArray(pedidoLimpio.prendas)) {
            pedidoLimpio.prendas.forEach((prenda, prendaIdx) => {
                // Imágenes de prenda
                if (prenda.imagenes && Array.isArray(prenda.imagenes)) {
                    const imagenesValidas = [];
                    
                    prenda.imagenes.forEach((img, imgIdx) => {
                        const archivo = this._extraerFile(img);
                        
                        if (archivo instanceof File) {
                            archivos.prendas.push({
                                prendaIdx,
                                imgIdx,
                                file: archivo
                            });
                        } else {
                            imagenesValidas.push(img);
                        }
                    });
                    
                    prenda.imagenes = imagenesValidas;
                }

                // Imágenes de telas
                if (prenda.telas && Array.isArray(prenda.telas)) {
                    prenda.telas.forEach((tela, telaIdx) => {
                        if (tela.imagenes && Array.isArray(tela.imagenes)) {
                            const imagenesValidas = [];
                            
                            tela.imagenes.forEach((img, imgIdx) => {
                                const archivo = this._extraerFile(img);
                                
                                if (archivo instanceof File) {
                                    archivos.telas.push({
                                        prendaIdx,
                                        telaIdx,
                                        imgIdx,
                                        file: archivo
                                    });
                                } else {
                                    imagenesValidas.push(img);
                                }
                            });
                            
                            tela.imagenes = imagenesValidas;
                        }
                    });
                }

                // Imágenes de procesos
                if (prenda.procesos && Array.isArray(prenda.procesos)) {
                    prenda.procesos.forEach((proceso, procesoIdx) => {
                        if (proceso.imagenes && Array.isArray(proceso.imagenes)) {
                            const imagenesValidas = [];
                            
                            proceso.imagenes.forEach((img, imgIdx) => {
                                const archivo = this._extraerFile(img);
                                
                                if (archivo instanceof File) {
                                    archivos.procesos.push({
                                        prendaIdx,
                                        procesoIdx,
                                        imgIdx,
                                        file: archivo
                                    });
                                } else {
                                    imagenesValidas.push(img);
                                }
                            });
                            
                            proceso.imagenes = imagenesValidas;
                        }
                    });
                }
            });
        }

        return { pedidoLimpio, archivos };
    }

    /**
     *  Extraer File object de diferentes formatos
     */
    static _extraerFile(img) {
        if (img instanceof File) {
            return img;
        }
        
        if (img && typeof img === 'object' && img.file instanceof File) {
            return img.file;
        }
        
        return null;
    }

    /**
     *  Construir FormData correctamente
     */
    static construirFormData(pedidoLimpio, archivos) {
        const formData = new FormData();

        // 1. Agregar JSON del pedido (metadata)
        formData.append('pedido', JSON.stringify(pedidoLimpio));

        // 2. Agregar archivos de prendas
        archivos.prendas.forEach(({prendaIdx, imgIdx, file}) => {
            formData.append(
                `prendas[${prendaIdx}][imagenes][${imgIdx}]`,
                file
            );
        });

        // 3. Agregar archivos de telas
        archivos.telas.forEach(({prendaIdx, telaIdx, imgIdx, file}) => {
            formData.append(
                `prendas[${prendaIdx}][telas][${telaIdx}][imagenes][${imgIdx}]`,
                file
            );
        });

        // 4. Agregar archivos de procesos
        archivos.procesos.forEach(({prendaIdx, procesoIdx, imgIdx, file}) => {
            formData.append(
                `prendas[${prendaIdx}][procesos][${procesoIdx}][imagenes][${imgIdx}]`,
                file
            );
        });

        // 5.  Agregar archivos de EPP
        archivos.epps.forEach(({eppIdx, imgIdx, file, epp_id}) => {
            // Nombre único para facilitar identificación en servidor
            const ext = file.name.split('.').pop() || 'jpg';
            const nombre = `epp_${epp_id}_img_${imgIdx}_${Date.now()}.${ext}`;
            
            formData.append(
                `epps[${eppIdx}][imagenes][${imgIdx}]`,
                file,
                nombre
            );

            console.debug('[PayloadNormalizer] EPP imagen agregada', {
                eppIdx,
                imgIdx,
                epp_id,
                nombre
            });
        });

        console.log('[PayloadNormalizer] FormData construido', {
            prendas_archivos: archivos.prendas.length,
            telas_archivos: archivos.telas.length,
            procesos_archivos: archivos.procesos.length,
            epps_archivos: archivos.epps.length,
            total: archivos.prendas.length + archivos.telas.length + archivos.procesos.length + archivos.epps.length
        });

        return formData;
    }

    /**
     *  Método completo: normalizar payload y crear FormData
     */
    static normalizar(pedidoData) {
        const { pedidoLimpio, archivos } = this.extraerArchivos(pedidoData);
        const formData = this.construirFormData(pedidoLimpio, archivos);
        
        return {
            pedidoLimpio,
            archivos,
            formData
        };
    }

    /**
     *  Debug: verificar contenido de FormData
     */
    static debugFormData(formData) {
        console.log('\n=== FormData Contents ===');
        for (let [key, value] of formData.entries()) {
            if (value instanceof File) {
                console.log(`  ${key}: [File] ${value.name} (${value.size} bytes)`);
            } else if (typeof value === 'string' && value.length > 100) {
                console.log(`  ${key}: [String] ${value.substring(0, 100)}...`);
            } else {
                console.log(`  ${key}:`, value);
            }
        }
        console.log('========================\n');
    }
}

// Exportar para uso global
window.PayloadNormalizerEpp = PayloadNormalizerEpp;

// Para Node.js/módulos
if (typeof module !== 'undefined' && module.exports) {
    module.exports = PayloadNormalizerEpp;
}
