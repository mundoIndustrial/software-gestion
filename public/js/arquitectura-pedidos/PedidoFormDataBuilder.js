/**
 * PedidoFormDataBuilder.js
 * 
 * Construye FormData correctamente para enviar:
 * 1. JSON metadata del pedido (sin File objects)
 * 2. Archivos agrupados por ruta (respetando estructura de FormData)
 * 
 * EJEMPLO:
 * 
 * const builder = new PedidoFormDataBuilder(domPedidoModel);
 * const formData = builder
 *   .agregarPedidoJSON(backendPedidoModel)
 *   .agregarImagenPrenda(0, archivo)
 *   .agregarImagenTela(0, 0, archivo)
 *   .agregarImagenProceso(0, 0, archivo)
 *   .construir();
 * 
 * // FormData contendrá:
 * // pedido: JSON string
 * // prendas.0.imagenes.0: File
 * // prendas.0.telas.0.imagenes.0: File
 * // prendas.0.procesos.0.imagenes.0: File
 */

export class PedidoFormDataBuilder {
    constructor(domPedidoModel) {
        this.domPedido = domPedidoModel;
        this.formData = new FormData();
        this.archivosAgregados = {
            prendas: {},
            telas: {},
            procesos: {},
            epps: {}
        };
    }

    /**
     * Agregar JSON del pedido (metadata serializable)
     */
    agregarPedidoJSON(backendPedidoModel) {
        this.formData.append('pedido', JSON.stringify(backendPedidoModel.toJSON()));
        return this;
    }

    /**
     * Agregar imagen de prenda
     * prendaIdx: índice de prenda
     * imagenIdx: índice de imagen en la prenda
     * archivo: File object
     */
    agregarImagenPrenda(prendaIdx, imagenIdx, archivo) {
        const key = `prendas.${prendaIdx}.imagenes.${imagenIdx}`;
        this.formData.append(key, archivo);
        this.archivosAgregados.prendas[key] = archivo.name;
        return this;
    }

    /**
     * Agregar imagen de tela
     * prendaIdx: índice de prenda
     * telaIdx: índice de tela en la prenda
     * imagenIdx: índice de imagen en la tela
     * archivo: File object
     */
    agregarImagenTela(prendaIdx, telaIdx, imagenIdx, archivo) {
        const key = `prendas.${prendaIdx}.telas.${telaIdx}.imagenes.${imagenIdx}`;
        this.formData.append(key, archivo);
        this.archivosAgregados.telas[key] = archivo.name;
        return this;
    }

    /**
     * Agregar imagen de proceso
     * prendaIdx: índice de prenda
     * procesoIdx: índice de proceso en la prenda
     * imagenIdx: índice de imagen en el proceso
     * archivo: File object
     */
    agregarImagenProceso(prendaIdx, procesoIdx, imagenIdx, archivo) {
        const key = `prendas.${prendaIdx}.procesos.${procesoIdx}.imagenes.${imagenIdx}`;
        this.formData.append(key, archivo);
        this.archivosAgregados.procesos[key] = archivo.name;
        return this;
    }

    /**
     * Agregar imagen de EPP
     */
    agregarImagenEpp(eppIdx, imagenIdx, archivo) {
        const key = `epps.${eppIdx}.imagenes.${imagenIdx}`;
        this.formData.append(key, archivo);
        this.archivosAgregados.epps[key] = archivo.name;
        return this;
    }

    /**
     * Agregar todas las imágenes automáticamente desde el modelo DOM
     * 
     * Itera el modelo DOM y agrega todos los archivos
     */
    agregarTodasLasImagenes() {
        this.domPedido.prendas.forEach((prenda, prendaIdx) => {
            // Imágenes de prenda
            if (prenda.imagenes) {
                prenda.imagenes.forEach((img, imgIdx) => {
                    if (img.file instanceof File) {
                        this.agregarImagenPrenda(prendaIdx, imgIdx, img.file);
                    }
                });
            }

            // Imágenes de telas
            if (prenda.telas) {
                prenda.telas.forEach((tela, telaIdx) => {
                    if (tela.imagenes) {
                        tela.imagenes.forEach((img, imgIdx) => {
                            if (img.file instanceof File) {
                                this.agregarImagenTela(prendaIdx, telaIdx, imgIdx, img.file);
                            }
                        });
                    }
                });
            }

            // Imágenes de procesos
            if (prenda.procesos) {
                prenda.procesos.forEach((proceso, procesoIdx) => {
                    if (proceso.imagenes) {
                        proceso.imagenes.forEach((img, imgIdx) => {
                            if (img.file instanceof File) {
                                this.agregarImagenProceso(prendaIdx, procesoIdx, imgIdx, img.file);
                            }
                        });
                    }
                });
            }
        });

        // Imágenes de EPPs
        if (this.domPedido.epps) {
            this.domPedido.epps.forEach((epp, eppIdx) => {
                if (epp.imagenes) {
                    epp.imagenes.forEach((img, imgIdx) => {
                        if (img.file instanceof File) {
                            this.agregarImagenEpp(eppIdx, imgIdx, img.file);
                        }
                    });
                }
            });
        }

        return this;
    }

    /**
     * Construir y retornar el FormData
     */
    construir() {
        return this.formData;
    }

    /**
     * Obtener resumen de archivos agregados (para debugging)
     */
    obtenerResumen() {
        return {
            archivos_agregados: this.contarArchivos(),
            estructura: this.archivosAgregados
        };
    }

    /**
     * Contar archivos agregados
     */
    contarArchivos() {
        let total = 0;
        Object.values(this.archivosAgregados).forEach(categoria => {
            total += Object.keys(categoria).length;
        });
        return total;
    }
}
