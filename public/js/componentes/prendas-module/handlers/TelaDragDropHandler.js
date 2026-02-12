/**
 * ================================================
 * TELA DRAG & DROP HANDLER
 * ================================================
 * 
 * Manejo específico de drag & drop para imágenes de telas
 * Hereda de BaseDragDropHandler y añade funcionalidad especializada
 * 
 * @class TelaDragDropHandler
 * @extends BaseDragDropHandler
 */
class TelaDragDropHandler extends BaseDragDropHandler {
    constructor() {
        super();
        this.tipo = 'tela';
        this.opcionesMenu = {
            textoPegar: 'Pegar imagen de tela',
            iconoPegar: 'content_paste'
        };
    }

    /**
     * Configurar drag & drop para zona de drop de telas
     * @param {HTMLElement} dropZone - Elemento drop zone
     * @returns {TelaDragDropHandler} Instancia para encadenamiento
     */
    configurarDropZone(dropZone) {
        if (!dropZone) {
            UIHelperService.log('TelaDragDropHandler', 'Drop zone no proporcionado', 'error');
            return this;
        }

        // Configurar usando el método base
        this._configurarHandlerBase(dropZone, {
            estilosDragOver: {
                background: 'rgba(59, 130, 246, 0.1)',
                border: '2px dashed #3b82f6',
                opacity: '0.8',
                borderRadius: '6px',
                transform: 'scale(1.02)',
                padding: '8px'
            },
            estilosTransform: {
                transform: 'scale(1.02)'
            },
            soloImagenes: true,
            maxArchivos: 1,
            callbacks: {
                onDragOver: (e) => this._onDragOverDropZone(e),
                onDragLeave: (e) => this._onDragLeaveDropZone(e),
                onDrop: (files, e) => this._onDropDropZone(files, e),
                onClick: (e) => this._onClickDropZone(e),
                onPaste: (files, e) => this._onPasteDropZone(files, e),
                onError: (mensaje) => this._onError(mensaje)
            }
        });
        
        UIHelperService.log('TelaDragDropHandler', 'Drop zone de telas configurada');
        return this;
    }

    /**
     * Configurar drag & drop para preview de telas
     * @param {HTMLElement} previewElement - Elemento preview
     * @returns {TelaDragDropHandler} Instancia para encadenamiento
     */
    configurarPreview(previewElement) {
        if (!previewElement) {
            // UIHelperService.log('TelaDragDropHandler', 'Preview no proporcionado', 'error');
            return this;
        }

        // Crear handler base con configuración específica para preview
        this.handlerPreview = new DragDropEventHandler({
            estilosDragOver: {
                background: 'rgba(59, 130, 246, 0.15)',
                border: '2px dashed #3b82f6',
                opacity: '0.8',
                transform: 'scale(1.02)'
            },
            soloImagenes: true,
            maxArchivos: 1,
            callbacks: {
                onDragOver: (e) => this._onDragOverPreview(e),
                onDragLeave: (e) => this._onDragLeavePreview(e),
                onDrop: (files, e) => this._onDropPreview(files, e),
                onClick: (e) => this._onClickPreview(e),
                onPaste: (files, e) => this._onPastePreview(files, e),
                onError: (mensaje) => this._onError(mensaje)
            }
        });

        this.handlerPreview.configurar(previewElement);
        
        // UIHelperService.log('TelaDragDropHandler', 'Preview configurado');
        return this;
    }

    /**
     * Manejar evento drag over en drop zone
     * @param {DragEvent} e - Evento drag over
     * @private
     */
    _onDragOverDropZone(e) {
        // UIHelperService.log('TelaDragDropHandler', 'Drag over en drop zone de tela');
        
        // Aplicar estilos adicionales a elementos internos
        const button = this.handler.elemento.querySelector('button');
        if (button) {
            button.style.background = '#2563eb';
            button.style.transform = 'scale(1.05)';
            button.style.boxShadow = '0 4px 12px rgba(59, 130, 246, 0.3)';
        }
        
        const helpText = this.handler.elemento.querySelector('div[style*="color: #6b7280"]');
        if (helpText) {
            helpText.style.color = '#3b82f6';
            helpText.style.fontWeight = '500';
            const icon = helpText.querySelector('.material-symbols-rounded');
            if (icon) {
                icon.style.opacity = '1';
            }
        }
    }

    /**
     * Manejar evento drag over en preview
     * @param {DragEvent} e - Evento drag over
     * @private
     */
    _onDragOverPreview(e) {
        // UIHelperService.log('TelaDragDropHandler', 'Drag over en preview de tela');
    }

    /**
     * Manejar evento drag leave en drop zone
     * @param {DragEvent} e - Evento drag leave
     * @private
     */
    _onDragLeaveDropZone(e) {
        // UIHelperService.log('TelaDragDropHandler', 'Drag leave en drop zone de tela');
        
        // Restaurar estilos de elementos internos
        const button = this.handler.elemento.querySelector('button');
        if (button) {
            button.style.background = '';
            button.style.transform = '';
            button.style.boxShadow = '';
        }
        
        const helpText = this.handler.elemento.querySelector('div[style*="color: #6b7280"]');
        if (helpText) {
            helpText.style.color = '#6b7280';
            helpText.style.fontWeight = 'normal';
            const icon = helpText.querySelector('.material-symbols-rounded');
            if (icon) {
                icon.style.opacity = '0.5';
            }
        }
    }

    /**
     * Manejar evento drag leave en preview
     * @param {DragEvent} e - Evento drag leave
     * @private
     */
    _onDragLeavePreview(e) {
        // UIHelperService.log('TelaDragDropHandler', 'Drag leave en preview de tela');
    }

    /**
     * Manejar evento drop en drop zone
     * @param {FileList} files - Archivos arrastrados
     * @param {DragEvent} e - Evento drop
     * @private
     */
    _onDropDropZone(files, e) {
        // UIHelperService.log('TelaDragDropHandler', `Drop en drop zone: ${files.length} archivos`);
        
        const tempInput = UIHelperService.crearInputTemporal(files);
        this._procesarImagen(tempInput);
    }

    /**
     * Manejar evento drop en preview
     * @param {FileList} files - Archivos arrastrados
     * @param {DragEvent} e - Evento drop
     * @private
     */
    _onDropPreview(files, e) {
        // UIHelperService.log('TelaDragDropHandler', `Drop en preview: ${files.length} archivos`);
        
        const tempInput = UIHelperService.crearInputTemporal(files);
        this._procesarImagen(tempInput);
    }

    /**
     * Manejar evento click en drop zone
     * @param {MouseEvent} e - Evento click
     * @private
     */
    _onClickDropZone(e) {
        // UIHelperService.log('TelaDragDropHandler', 'Click en drop zone de tela');
        
        // Enfocar el elemento para permitir pegar
        this.handler.elemento.focus();
    }

    /**
     * Manejar evento click en preview
     * @param {MouseEvent} e - Evento click
     * @private
     */
    _onClickPreview(e) {
        // UIHelperService.log('TelaDragDropHandler', 'Click en preview de tela');
        
        // Enfocar el elemento para permitir pegar
        this.handlerPreview.elemento.focus();
    }

    /**
     * Manejar evento paste en drop zone
     * @param {FileList} files - Archivos del portapapeles
     * @param {ClipboardEvent} e - Evento paste
     * @private
     */
    _onPasteDropZone(files, e) {
        // UIHelperService.log('TelaDragDropHandler', `Paste en drop zone: ${files.length} archivos`);
        
        const tempInput = UIHelperService.crearInputTemporal(files);
        this._procesarImagen(tempInput);
    }

    /**
     * Manejar evento paste en preview
     * @param {FileList} files - Archivos del portapapeles
     * @param {ClipboardEvent} e - Evento paste
     * @private
     */
    _onPastePreview(files, e) {
        UIHelperService.log('TelaDragDropHandler', `Paste en preview: ${files.length} archivos`);
        
        const tempInput = UIHelperService.crearInputTemporal(files);
        this._procesarImagen(tempInput);
    }

    /**
     * Manejar errores
     * @param {string} mensaje - Mensaje de error
     * @private
     */
    _onError(mensaje) {
        UIHelperService.log('TelaDragDropHandler', `Error: ${mensaje}`, 'error');
        UIHelperService.mostrarModalError(mensaje);
    }

    /**
     * Procesar imagen específica para telas
     * @param {HTMLInputElement} input - Input con archivos
     * @private
     */
    _procesarImagen(input) {
        if (typeof window.manejarImagenTela === 'function') {
            window.manejarImagenTela(input);
            UIHelperService.log('TelaDragDropHandler', 'Imagen de tela procesada con función global');
        } else {
            UIHelperService.log('TelaDragDropHandler', 'Función manejarImagenTela no disponible', 'error');
            UIHelperService.mostrarModalError('No se pudo procesar la imagen de tela. Función de manejo no disponible.');
        }
    }

    /**
     * Configurar menú contextual para telas
     * @param {MouseEvent} e - Evento que activa el menú
     * @param {string} tipo - Tipo de elemento ('dropzone' o 'preview')
     */
    mostrarMenuContextual(e, tipo = 'dropzone') {
        UIHelperService.log('TelaDragDropHandler', `🎯 mostrarMenuContextual llamado (${tipo})...`);
        UIHelperService.log('TelaDragDropHandler', `📍 Coordenadas: x=${e.clientX}, y=${e.clientY}`);
        
        // Verificar si ContextMenuService está disponible
        if (!window.ContextMenuService) {
            UIHelperService.log('TelaDragDropHandler', '❌ ContextMenuService no disponible', 'error');
            return;
        }
        
        UIHelperService.log('TelaDragDropHandler', '✅ ContextMenuService disponible');
        
        // Crear callback con logging
        const callbackPegar = (e, opcion) => {
            UIHelperService.log('TelaDragDropHandler', '🚀 Callback ejecutado desde menú contextual');
            this._pegarDesdeMenuContextual();
        };
        
        UIHelperService.log('TelaDragDropHandler', `📋 Callback creado: ${typeof callbackPegar}`);
        
        const opciones = [
            ContextMenuService.crearOpcionPegarTela(callbackPegar)
        ];

        UIHelperService.log('TelaDragDropHandler', `📝 Opciones creadas: ${opciones.length}`);

        const config = {
            usarOverlay: true,
            autoCerrar: true,
            animacion: true
        };

        UIHelperService.log('TelaDragDropHandler', `📋 Llamando a ContextMenuService.crearMenu...`);
        
        try {
            const resultado = ContextMenuService.crearMenu(e.clientX, e.clientY, opciones, config);
            UIHelperService.log('TelaDragDropHandler', `✅ ContextMenuService.crearMenu ejecutado, resultado: ${resultado}`);
            UIHelperService.log('TelaDragDropHandler', `✅ Menú contextual mostrado para ${tipo}`);
        } catch (error) {
            UIHelperService.log('TelaDragDropHandler', `❌ Error al crear menú contextual: ${error.message}`, 'error');
            UIHelperService.log('TelaDragDropHandler', `❌ Stack trace: ${error.stack}`, 'error');
        }
    }

    /**
     * Pegar imagen desde menú contextual
     * @param {string} tipo - Tipo de elemento ('dropzone' o 'preview')
     * @private
     */
    async _pegarDesdeMenuContextual(tipo) {
        UIHelperService.log('TelaDragDropHandler', `🖱️ Iniciando pegado desde menú contextual (${tipo})...`);
        
        try {
            // Verificar si ClipboardService está disponible
            if (!window.ClipboardService) {
                UIHelperService.log('TelaDragDropHandler', '❌ ClipboardService no disponible', 'error');
                throw new Error('ClipboardService no disponible');
            }

            UIHelperService.log('TelaDragDropHandler', '✅ ClipboardService disponible, intentando leer...');

            // Leer imágenes del portapapeles
            const archivos = await ClipboardService.leerImagenes({ maxArchivos: 1 });

            UIHelperService.log('TelaDragDropHandler', `📁 Archivos obtenidos: ${archivos.length}`);

            if (archivos.length > 0) {
                const tempInput = UIHelperService.crearInputTemporal(archivos);
                this._procesarImagen(tempInput);
                UIHelperService.log('TelaDragDropHandler', `✅ Imagen de tela pegada desde menú (${tipo})`);
            } else {
                UIHelperService.log('TelaDragDropHandler', '⚠️ No se encontraron imágenes en el portapapeles', 'warn');
            }

        } catch (error) {
            UIHelperService.log('TelaDragDropHandler', `❌ Error principal pegando desde menú: ${error.message}`, 'error');
            
            // Fallback mejorado: usar el portapapeles del navegador directamente
            try {
                UIHelperService.log('TelaDragDropHandler', '🔄 Intentando fallback con navigator.clipboard...');
                
                if (navigator.clipboard && navigator.clipboard.read) {
                    const items = await navigator.clipboard.read();
                    UIHelperService.log('TelaDragDropHandler', `📋 Items encontrados en fallback: ${items.length}`);
                    
                    const archivos = [];
                    
                    for (const item of items) {
                        UIHelperService.log('TelaDragDropHandler', `🔍 Tipos en item: ${item.types.join(', ')}`);
                        
                        for (const type of item.types) {
                            if (type.startsWith('image/')) {
                                UIHelperService.log('TelaDragDropHandler', `🖼️ Procesando tipo de imagen: ${type}`);
                                
                                const blob = await item.getType(type);
                                UIHelperService.log('TelaDragDropHandler', `📦 Blob obtenido: ${blob.size} bytes`);
                                
                                const file = new File([blob], `tela-${Date.now()}.${type.split('/')[1]}`, {
                                    type: type
                                });
                                archivos.push(file);
                                
                                // Solo necesitamos una imagen para telas
                                break;
                            }
                        }
                        if (archivos.length > 0) {
                            break;
                        }
                    }
                    
                    if (archivos.length > 0) {
                        const tempInput = UIHelperService.crearInputTemporal(archivos);
                        this._procesarImagen(tempInput);
                        UIHelperService.log('TelaDragDropHandler', `✅ Imagen de tela pegada con fallback (${tipo})`);
                    } else {
                        UIHelperService.mostrarModalError('No se encontraron imágenes en el portapapeles. Por favor copia una imagen primero.');
                    }
                } else {
                    UIHelperService.log('TelaDragDropHandler', '❌ navigator.clipboard.read no disponible', 'error');
                    UIHelperService.mostrarModalError('Por favor usa Ctrl+V para pegar la imagen de tela.');
                }
            } catch (fallbackError) {
                UIHelperService.log('TelaDragDropHandler', `❌ Error en fallback: ${fallbackError.message}`, 'error');
                UIHelperService.mostrarModalError('No se pudo acceder al portapapeles. Por favor usa Ctrl+V para pegar la imagen de tela.');
            }
        }
    }

    /**
     * Obtener estado actual del handler
     * @returns {Object} Estado actual
     */
    getEstado() {
        return {
            dropZoneConfigurada: !!this.handler,
            previewConfigurado: !!this.handlerPreview,
            dropZoneEstado: this.handler ? this.handler.getEstado() : null,
            previewEstado: this.handlerPreview ? this.handlerPreview.getEstado() : null
        };
    }

    /**
     * Desactivar todos los handlers
     */
    desactivar() {
        if (this.handler) {
            this.handler.desactivar();
        }
        if (this.handlerPreview) {
            this.handlerPreview.desactivar();
        }
        UIHelperService.log('TelaDragDropHandler', 'Handlers desactivados');
    }

    /**
     * Destruir los handlers y limpiar recursos
     */
    destruir() {
        if (this.handler) {
            this.handler.destruir();
        }
        if (this.handlerPreview) {
            this.handlerPreview.destruir();
        }
        UIHelperService.log('TelaDragDropHandler', 'Handlers destruidos');
    }
}

// Exportar para uso en módulos
if (typeof module !== 'undefined' && module.exports) {
    module.exports = TelaDragDropHandler;
}

// Asignar al window para uso global
window.TelaDragDropHandler = TelaDragDropHandler;
