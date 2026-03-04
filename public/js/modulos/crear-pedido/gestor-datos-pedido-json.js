/**
 * GESTOR CENTRALIZADO DE DATOS DEL PEDIDO
 * 
 * Mantiene todo en un JSON hasta que se hace submit
 * Estructura:
 * {
 *   prendas: [
 *     {
 *       nombre, descripcion, origen, genero,
 *       imagenes: [...File objects],
 *       telas: [ { tela, color, referencia, imagenes: [...] } ],
 *       procesos: {
 *         reflectivo: { tipo, ubicaciones, observaciones, imagenes, tallas },
 *         ...
 *       },
 *       variaciones: { manga, bolsillos, broche, ... },
 *       cantidades: { 'dama-S': 10, 'dama-M': 20, ... }
 *     }
 *   ]
 * }
 */

class GestorDatosPedidoJSON {
    constructor() {
        this.datosCompletos = {
            prendas: []
        };

    }

    /**
     * Agregar prenda al JSON
     */
    agregarPrenda(prendaData) {

        
        const prenda = {
            nombre: prendaData.nombre,
            descripcion: prendaData.descripcion,
            origen: prendaData.origen,
            genero: prendaData.genero,
            imagenes: prendaData.imagenes || [],
            telas: prendaData.telas || [],
            procesos: prendaData.procesos || {},
            variaciones: prendaData.variaciones || {},
            cantidades: prendaData.cantidades || {}
        };

        this.datosCompletos.prendas.push(prenda);

=> 
            v instanceof File ? `[File: ${v.name}]` : v
        )));

        return prenda;
    }

    /**
     * Actualizar prenda existente
     */
    actualizarPrenda(indice, prendaData) {
        if (indice >= 0 && indice < this.datosCompletos.prendas.length) {

            
            Object.assign(this.datosCompletos.prendas[indice], prendaData);

        } else {

        }
    }

    /**
     * Agregar tela a prenda
     */
    agregarTelaAPrenda(indicePrenda, telaData) {
        if (this.datosCompletos.prendas[indicePrenda]) {

            
            this.datosCompletos.prendas[indicePrenda].telas.push({
                tela: telaData.tela,
                color: telaData.color,
                referencia: telaData.referencia,
                imagenes: telaData.imagenes || []
            });
            

        }
    }

    /**
     * Agregar proceso a prenda
     */
    agregarProcesosAPrenda(indicePrenda, procesos) {
        if (this.datosCompletos.prendas[indicePrenda]) {

            
            this.datosCompletos.prendas[indicePrenda].procesos = {
                ...this.datosCompletos.prendas[indicePrenda].procesos,
                ...procesos
            };
            

        }
    }

    /**
     * Agregar variaciones a prenda
     */
    agregarVariacionesAPrenda(indicePrenda, variaciones) {
        if (this.datosCompletos.prendas[indicePrenda]) {

            
            this.datosCompletos.prendas[indicePrenda].variaciones = {
                ...this.datosCompletos.prendas[indicePrenda].variaciones,
                ...variaciones
            };
            

        }
    }

    /**
     * Agregar cantidades a prenda
     */
    agregarCantidadesAPrenda(indicePrenda, cantidades) {
        if (this.datosCompletos.prendas[indicePrenda]) {

            
            this.datosCompletos.prendas[indicePrenda].cantidades = {
                ...this.datosCompletos.prendas[indicePrenda].cantidades,
                ...cantidades
            };
            

        }
    }

    /**
     * Obtener datos completos
     */
    obtenerDatosCompletos() {
        return this.datosCompletos;
    }

    /**
     * Obtener JSON sin archivos (para logging)
     */
    obtenerJSON() {
        return JSON.parse(JSON.stringify(this.datosCompletos, (k, v) => 
            v instanceof File ? `[File: ${v.name} - ${v.size} bytes]` : v
        ));
    }

    /**
     * Crear FormData completo con JSON + archivos
     */
    crearFormData() {




        const formData = new FormData();
        let contadores = {
            archivos: 0,
            prendas: 0,
            telas: 0,
            procesos: 0,
            cantidades: 0
        };

        // Iterar prendas
        this.datosCompletos.prendas.forEach((prenda, prendaIdx) => {


            // Datos básicos de prenda
            formData.append(`prendas[${prendaIdx}][nombre]`, prenda.nombre);
            formData.append(`prendas[${prendaIdx}][descripcion]`, prenda.descripcion);
            formData.append(`prendas[${prendaIdx}][origen]`, prenda.origen);
            formData.append(`prendas[${prendaIdx}][genero]`, prenda.genero);
            contadores.prendas++;

            // Imágenes de prenda
            if (prenda.imagenes && prenda.imagenes.length > 0) {

                prenda.imagenes.forEach((img, imgIdx) => {
                    if (img instanceof File) {
                        formData.append(`prendas[${prendaIdx}][imagenes][]`, img);
                        contadores.archivos++;

                    }
                });
            }

            // Telas
            if (prenda.telas && prenda.telas.length > 0) {

                prenda.telas.forEach((tela, telaIdx) => {
                    formData.append(`prendas[${prendaIdx}][telas][${telaIdx}][tela]`, tela.tela);
                    formData.append(`prendas[${prendaIdx}][telas][${telaIdx}][color]`, tela.color);
                    formData.append(`prendas[${prendaIdx}][telas][${telaIdx}][referencia]`, tela.referencia);
                    contadores.telas++;

                    if (tela.imagenes && tela.imagenes.length > 0) {

                        tela.imagenes.forEach((img, imgIdx) => {
                            // Manejar dos casos: img es File directo, o img es {file: File, nombre: string}
                            const archivo = img instanceof File ? img : (img && img.file instanceof File ? img.file : null);
                            if (archivo) {
                                formData.append(`prendas[${prendaIdx}][telas][${telaIdx}][imagenes][]`, archivo);
                                contadores.archivos++;

                            }
                        });
                    }
                });
            }

            // Procesos
            if (prenda.procesos && Object.keys(prenda.procesos).length > 0) {

                Object.entries(prenda.procesos).forEach(([tipoProceso, proceso]) => {
                    if (proceso && proceso.datos) {
                        const prefix = `prendas[${prendaIdx}][procesos][${tipoProceso}]`;
                        const modoTallas = proceso.modoTallas || (proceso.datos.datosExtendidos ? 'por_tallas' : 'para_todas');

                        formData.append(`${prefix}[tipo]`, proceso.datos.tipo || tipoProceso);
                        formData.append(`${prefix}[ubicaciones]`, JSON.stringify(proceso.datos.ubicaciones || []));
                        formData.append(`${prefix}[observaciones]`, proceso.datos.observaciones || '');
                        formData.append(`${prefix}[tallas]`, JSON.stringify(proceso.datos.tallas || {}));
                        formData.append(`${prefix}[modo_tallas]`, modoTallas);
                        contadores.procesos++;

                        if (modoTallas === 'por_tallas' && proceso.datos.datosExtendidos) {
                            // Enviar datosExtendidos como JSON (ubicaciones + observaciones por talla)
                            formData.append(`${prefix}[datos_extendidos]`, JSON.stringify(proceso.datos.datosExtendidos));

                            // Enviar imágenes POR TALLA con key especial
                            const extendidos = proceso.datos.datosExtendidos;
                            ['dama', 'caballero', 'unisex'].forEach(genero => {
                                if (extendidos[genero]) {
                                    Object.entries(extendidos[genero]).forEach(([talla, datosTalla]) => {
                                        const tallaKey = `${genero}__${talla}`;
                                        if (datosTalla.imagenesFiles && datosTalla.imagenesFiles.length > 0) {
                                            datosTalla.imagenesFiles.forEach(file => {
                                                if (file instanceof File) {
                                                    formData.append(`${prefix}[imagenes_por_talla][${tallaKey}][]`, file);
                                                    contadores.archivos++;
                                                }
                                            });
                                        }
                                    });
                                }
                            });
                        } else {
                            // Modo para_todas: enviar imágenes al nivel del proceso
                            if (proceso.datos.imagenes && proceso.datos.imagenes.length > 0) {
                                proceso.datos.imagenes.forEach((img, imgIdx) => {
                                    if (img instanceof File) {
                                        formData.append(`${prefix}[imagenes][]`, img);
                                        contadores.archivos++;
                                    }
                                });
                            }
                        }
                    }
                });
            }

            // Variaciones
            if (prenda.variaciones && Object.keys(prenda.variaciones).length > 0) {

                Object.entries(prenda.variaciones).forEach(([clave, valor]) => {
                    formData.append(`prendas[${prendaIdx}][variaciones][${clave}]`, valor);
                });
            }

            // Cantidades
            if (prenda.cantidades && Object.keys(prenda.cantidades).length > 0) {

                Object.entries(prenda.cantidades).forEach(([clave, cantidad]) => {
                    formData.append(`prendas[${prendaIdx}][cantidades][${clave}]`, cantidad);
                    contadores.cantidades++;
                });
            }
        });








        return formData;
    }

    /**
     * Limpiar datos
     */
    limpiar() {
        this.datosCompletos = { prendas: [] };

    }
}

// Crear instancia global
window.gestorDatosPedidoJSON = new GestorDatosPedidoJSON();


