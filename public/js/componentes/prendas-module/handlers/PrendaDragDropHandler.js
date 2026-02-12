/**
 * ================================================
 * PRENDA DRAG & DROP HANDLER
 * ================================================
 * 
 * Manejo específico de drag & drop para imágenes de prendas
 * Hereda de BaseDragDropHandler y añade funcionalidad especializada
 * 
 * @class PrendaDragDropHandler
 * @extends BaseDragDropHandler
 */
class PrendaDragDropHandler extends BaseDragDropHandler {
    constructor() {
        super();
        this.maxImagenes = 3;
        this.imagenesActuales = [];
        this.tipo = 'prenda';
        this.opcionesMenu = {
            textoPegar: 'Pegar imagen de prenda',
            iconoPegar: 'content_paste'
        };
    }

    /**
     * Configurar drag & drop para prendas sin imágenes
     * @param {HTMLElement} previewElement - Elemento preview
     * @returns {PrendaDragDropHandler} Instancia para encadenamiento
     */
    configurarSinImagenes(previewElement) {
        if (!previewElement) {
            UIHelperService.log('PrendaDragDropHandler', 'Elemento preview no proporcionado', 'error');
            return this;
        }

        this.imagenesActuales = [];

        // Configurar usando el método base
        this._configurarHandlerBase(previewElement, {
            estilosDragOver: {
                background: '#eff6ff',
                border: '2px dashed #3b82f6',
                opacity: '0.8'
            },
            soloImagenes: true,
            maxArchivos: this.maxImagenes,
            callbacks: {
                onDragOver: (e) => this._onDragOver(e),
                onDragLeave: (e) => this._onDragLeave(e),
                onDrop: (files, e) => this._onDropSinImagenes(files, e),
                onClick: (e) => this._onClickSinImagenes(e),
                onPaste: (files, e) => this._onPasteSinImagenes(files, e),
                onError: (mensaje) => this._onError(mensaje)
            }
        });
        
        // Autoenfocar el preview para que pueda recibir paste
        setTimeout(() => {
            previewElement.focus();
        }, 100);
        
        UIHelperService.log('PrendaDragDropHandler', 'Configurado para prendas sin imágenes');
        return this;
    }

    /**
     * Configurar drag & drop para prendas con imágenes existentes
     * @param {HTMLElement} previewElement - Elemento preview
     * @param {Array} imagenesExistentes - Array de imágenes existentes
     * @returns {PrendaDragDropHandler} Instancia para encadenamiento
     */
    configurarConImagenes(previewElement, imagenesExistentes) {
        if (!previewElement) {
            UIHelperService.log('PrendaDragDropHandler', 'Elemento preview no proporcionado', 'error');
            return this;
        }

        this.imagenesActuales = imagenesExistentes || [];

        // Configurar usando el método base
        this._configurarHandlerBase(previewElement, {
            estilosDragOver: {
                background: 'rgba(59, 130, 246, 0.1)',
                border: '2px dashed #3b82f6',
                opacity: '0.8'
            },
            soloImagenes: true,
            maxArchivos: this.maxImagenes - this.imagenesActuales.length,
            callbacks: {
                onDragOver: (e) => this._onDragOverConImagenes(e),
                onDragLeave: (e) => this._onDragLeave(e),
                onDrop: (files, e) => this._onDropConImagenes(files, e),
                onClick: (e) => this._onClickConImagenes(e),
                onPaste: (files, e) => this._onPasteConImagenes(files, e),
                onError: (mensaje) => this._onError(mensaje)
            }
        });
        
        // Autoenfocar el preview para que pueda recibir paste
        setTimeout(() => {
            previewElement.focus();
        }, 100);
        
        UIHelperService.log('PrendaDragDropHandler', `Configurado para prendas con ${this.imagenesActuales.length} imágenes`);
        return this;
    }

    /**
     * Actualizar la lista de imágenes actuales
     * @param {Array} nuevasImagenes - Nueva lista de imágenes
     */
    actualizarImagenesActuales(nuevasImagenes) {
        this.imagenesActuales = nuevasImagenes || [];
        
        // Actualizar límite de archivos en el handler si existe
        if (this.handler) {
            const maxDisponible = this.maxImagenes - this.imagenesActuales.length;
            this.handler.actualizarOpciones({ maxArchivos: maxDisponible });
        }
        
        UIHelperService.log('PrendaDragDropHandler', `Imágenes actualizadas: ${this.imagenesActuales.length}`);
    }

    /**
     * Manejar evento drag over para prendas sin imágenes
     * @param {DragEvent} e - Evento drag over
     * @private
     */
    _onDragOver(e) {
        UIHelperService.log('PrendaDragDropHandler', 'Drag over en prenda sin imágenes');
    }

    /**
     * Manejar evento drag over para prendas con imágenes
     * @param {DragEvent} e - Evento drag over
     * @private
     */
    _onDragOverConImagenes(e) {
        UIHelperService.log('PrendaDragDropHandler', 'Drag over en prenda con imágenes');
    }

    /**
     * Manejar evento drag leave
     * @param {DragEvent} e - Evento drag leave
     * @private
     */
    _onDragLeave(e) {
        UIHelperService.log('PrendaDragDropHandler', 'Drag leave en prenda');
    }

    /**
     * Manejar evento drop para prendas sin imágenes
     * @param {FileList} files - Archivos arrastrados
     * @param {DragEvent} e - Evento drop
     * @private
     */
    _onDropSinImagenes(files, e) {
        UIHelperService.log('PrendaDragDropHandler', `Drop en prenda sin imágenes: ${files.length} archivos`);
        
        const tempInput = UIHelperService.crearInputTemporal(files);
        this._procesarImagen(tempInput);
    }

    /**
     * Manejar evento drop para prendas con imágenes
     * @param {FileList} files - Archivos arrastrados
     * @param {DragEvent} e - Evento drop
     * @private
     */
    _onDropConImagenes(files, e) {
        UIHelperService.log('PrendaDragDropHandler', `Drop en prenda con imágenes: ${files.length} archivos`);
        
        const tempInput = UIHelperService.crearInputTemporal(files);
        this._procesarImagen(tempInput);
    }

    /**
     * Manejar evento click para prendas sin imágenes
     * @param {MouseEvent} e - Evento click
     * @private
     */
    _onClickSinImagenes(e) {
        UIHelperService.log('PrendaDragDropHandler', 'Click en prenda sin imágenes');
        
        // Abrir el selector de archivos
        const inputFotos = document.getElementById('nueva-prenda-foto-input');
        if (inputFotos) {
            inputFotos.click();
        } else {
            UIHelperService.log('PrendaDragDropHandler', 'Input de fotos no encontrado', 'warn');
        }
    }

    /**
     * Manejar evento click para prendas con imágenes
     * @param {MouseEvent} e - Evento click
     * @private
     */
    _onClickConImagenes(e) {
        UIHelperService.log('PrendaDragDropHandler', 'Click en prenda con imágenes');
        
        // Abrir el selector de archivos
        const inputFotos = document.getElementById('nueva-prenda-foto-input');
        if (inputFotos) {
            inputFotos.click();
        } else {
            UIHelperService.log('PrendaDragDropHandler', 'Input de fotos no encontrado', 'warn');
        }
    }

    /**
     * Manejar evento paste para prendas sin imágenes
     * @param {FileList} files - Archivos del portapapeles
     * @param {ClipboardEvent} e - Evento paste
     * @private
     */
    _onPasteSinImagenes(files, e) {
        UIHelperService.log('PrendaDragDropHandler', `Paste en prenda sin imágenes: ${files.length} archivos`);
        
        const tempInput = UIHelperService.crearInputTemporal(files);
        this._procesarImagen(tempInput);
    }

    /**
     * Manejar evento paste para prendas con imágenes
     * @param {FileList} files - Archivos del portapapeles
     * @param {ClipboardEvent} e - Evento paste
     * @private
     */
    _onPasteConImagenes(files, e) {
        UIHelperService.log('PrendaDragDropHandler', `Paste en prenda con imágenes: ${files.length} archivos`);
        
        const tempInput = UIHelperService.crearInputTemporal(files);
        this._procesarImagen(tempInput);
    }

    /**
     * Manejar errores
     * @param {string} mensaje - Mensaje de error
     * @private
     */
    _onError(mensaje) {
        UIHelperService.log('PrendaDragDropHandler', `Error: ${mensaje}`, 'error');
        UIHelperService.mostrarModalError(mensaje);
    }

    /**
     * Procesar imagen específica para prendas
     * @param {HTMLInputElement} input - Input con archivos
     * @private
     */
    _procesarImagen(input) {
        // Usar la función global existente si está disponible
        if (typeof window.manejarImagenesPrenda === 'function') {
            window.manejarImagenesPrenda(input);
            UIHelperService.log('PrendaDragDropHandler', 'Imágenes procesadas con función global');
        } else {
            UIHelperService.log('PrendaDragDropHandler', 'Función manejarImagenesPrenda no disponible', 'error');
            UIHelperService.mostrarModalError('No se pudo procesar las imágenes. Función de manejo no disponible.');
        }
    }

    /**
     * Configurar menú contextual para prendas
     * @param {MouseEvent} e - Evento que activa el menú
     * @param {boolean} conImagenes - Si hay imágenes existentes
     */
    mostrarMenuContextual(e, conImagenes = false) {
        UIHelperService.log('PrendaDragDropHandler', `🎯 Creando menú contextual (con imágenes: ${conImagenes})`);
        
        // Crear callback con logging
        const callbackPegar = (e, opcion) => {
            UIHelperService.log('PrendaDragDropHandler', '🚀 Callback ejecutado desde menú contextual');
            this._pegarDesdeMenuContextual(conImagenes);
        };
        
        UIHelperService.log('PrendaDragDropHandler', `📋 Callback creado: ${typeof callbackPegar}`);
        
        const opciones = [
            ContextMenuService.crearOpcionPegarPrenda(callbackPegar)
        ];

        UIHelperService.log('PrendaDragDropHandler', `📝 Opciones creadas: ${opciones.length}`);

        // Configuración específica según si hay imágenes
        const config = {
            usarOverlay: true,
            autoCerrar: true,
            animacion: true
        };

        ContextMenuService.crearMenu(e.clientX, e.clientY, opciones, config);
        UIHelperService.log('PrendaDragDropHandler', `Menú contextual mostrado (con imágenes: ${conImagenes})`);
    }

    /**
     * Pegar imagen desde menú contextual
     * @param {boolean} conImagenes - Si hay imágenes existentes
     * @private
     */
    async _pegarDesdeMenuContextual(conImagenes) {
        UIHelperService.log('PrendaDragDropHandler', '🖱️ Iniciando pegado desde menú contextual...');
        
        try {
            // Verificar límite de imágenes
            if (conImagenes && this.imagenesActuales.length >= this.maxImagenes) {
                UIHelperService.mostrarModalError(`Solo se permiten máximo ${this.maxImagenes} imágenes por prenda`);
                return;
            }

            // Verificar si ClipboardService está disponible
            if (!window.ClipboardService) {
                UIHelperService.log('PrendaDragDropHandler', '❌ ClipboardService no disponible', 'error');
                throw new Error('ClipboardService no disponible');
            }

            UIHelperService.log('PrendaDragDropHandler', '✅ ClipboardService disponible, intentando leer...');

            // Leer imágenes del portapapeles
            const archivos = await ClipboardService.leerImagenes({
                maxArchivos: this.maxImagenes - this.imagenesActuales.length
            });

            UIHelperService.log('PrendaDragDropHandler', `📁 Archivos obtenidos: ${archivos.length}`);

            if (archivos.length > 0) {
                const tempInput = UIHelperService.crearInputTemporal(archivos);
                this._procesarImagen(tempInput);
                UIHelperService.log('PrendaDragDropHandler', `✅ Imagen pegada desde menú: ${archivos.length} archivos`);
            } else {
                UIHelperService.log('PrendaDragDropHandler', '⚠️ No se encontraron imágenes en el portapapeles', 'warn');
            }

        } catch (error) {
            UIHelperService.log('PrendaDragDropHandler', `❌ Error principal pegando desde menú: ${error.message}`, 'error');
            
            // Fallback mejorado: usar el portapapeles del navegador directamente
            try {
                UIHelperService.log('PrendaDragDropHandler', '🔄 Intentando fallback con navigator.clipboard...');
                
                if (navigator.clipboard && navigator.clipboard.read) {
                    const items = await navigator.clipboard.read();
                    UIHelperService.log('PrendaDragDropHandler', `📋 Items encontrados en fallback: ${items.length}`);
                    
                    const archivos = [];
                    
                    for (const item of items) {
                        UIHelperService.log('PrendaDragDropHandler', `🔍 Tipos en item: ${item.types.join(', ')}`);
                        
                        for (const type of item.types) {
                            if (type.startsWith('image/')) {
                                UIHelperService.log('PrendaDragDropHandler', `🖼️ Procesando tipo de imagen: ${type}`);
                                
                                const blob = await item.getType(type);
                                UIHelperService.log('PrendaDragDropHandler', `📦 Blob obtenido: ${blob.size} bytes`);
                                
                                const file = new File([blob], `imagen-${Date.now()}.${type.split('/')[1]}`, {
                                    type: type
                                });
                                archivos.push(file);
                                
                                // Limitar al máximo necesario
                                if (archivos.length >= this.maxImagenes - this.imagenesActuales.length) {
                                    break;
                                }
                            }
                        }
                        if (archivos.length >= this.maxImagenes - this.imagenesActuales.length) {
                            break;
                        }
                    }
                    
                    if (archivos.length > 0) {
                        const tempInput = UIHelperService.crearInputTemporal(archivos);
                        this._procesarImagen(tempInput);
                        UIHelperService.log('PrendaDragDropHandler', `✅ Imagen pegada con fallback: ${archivos.length} archivos`);
                    } else {
                        UIHelperService.mostrarModalError('No se encontraron imágenes en el portapapeles. Por favor copia una imagen primero.');
                    }
                } else {
                    UIHelperService.log('PrendaDragDropHandler', '❌ navigator.clipboard.read no disponible', 'error');
                    // Fallback final: solicitar al usuario que use Ctrl+V
                    UIHelperService.mostrarModalError('Usa Ctrl+V para pegar la imagen directamente en el área de fotos.');
                }
            } catch (fallbackError) {
                UIHelperService.log('PrendaDragDropHandler', `❌ Error en fallback: ${fallbackError.message}`, 'error');
                UIHelperService.mostrarModalError('No se pudo acceder al portapapeles. Por favor usa Ctrl+V para pegar la imagen.');
            }
        }
    }

    /**
     * Obtener estado actual del handler
     * @returns {Object} Estado actual
     */
    getEstado() {
        return {
            configurado: !!this.handler,
            imagenesActuales: this.imagenesActuales.length,
            maxImagenes: this.maxImagenes,
            handlerEstado: this.handler ? this.handler.getEstado() : null
        };
    }

    /**
     * Desactivar el handler
     */
    desactivar() {
        if (this.handler) {
            this.handler.desactivar();
        }
        UIHelperService.log('PrendaDragDropHandler', 'Handler desactivado');
    }
}

// Exportar para uso en módulos
if (typeof module !== 'undefined' && module.exports) {
    module.exports = PrendaDragDropHandler;
}

// Asignar al window para uso global
window.PrendaDragDropHandler = PrendaDragDropHandler;
