/**
 * ================================================
 * DRAG & DROP EVENT HANDLER
 * ================================================
 * 
 * Manejo base de eventos drag & drop reutilizables
 * Proporciona la funcionalidad fundamental para arrastrar y soltar archivos
 * 
 * @class DragDropEventHandler
 */

class DragDropEventHandler {
    constructor(options = {}) {
        this.options = {
            estilosDragOver: {
                background: 'rgba(59, 130, 246, 0.1)',
                border: '2px dashed #3b82f6',
                opacity: '0.8'
            },
            estilosTransform: {
                transform: 'scale(1.02)'
            },
            soloImagenes: true,
            maxArchivos: 1,
            ...options
        };
        
        this.callbacks = {
            onDragOver: null,
            onDragLeave: null,
            onDrop: null,
            onClick: null,
            onPaste: null,
            onError: null,
            ...options.callbacks
        };
        
        this.elemento = null;
        this.estaActivo = false;
    }

    /**
     * Configurar un elemento para drag & drop
     * @param {HTMLElement} elemento - Elemento a configurar
     * @returns {DragDropEventHandler} Instancia para encadenamiento
     */
    configurar(elemento) {
        if (!elemento) {
            UIHelperService.log('DragDropEventHandler', 'Elemento no proporcionado', 'error');
            return this;
        }

        // Limpiar listeners anteriores
        this.elemento = UIHelperService.limpiarEventListeners(elemento);
        this.estaActivo = true;

        // Configurar eventos básicos
        this._configurarEventosBasicos();
        this._configurarEventosDrag();
        this._configurarEventosInteraccion();

        // UIHelperService.log('DragDropEventHandler', 'Elemento configurado exitosamente');
        return this;
    }

    /**
     * Desactivar el drag & drop
     */
    desactivar() {
        this.estaActivo = false;
        UIHelperService.log('DragDropEventHandler', 'Drag & drop desactivado');
    }

    /**
     * Configurar eventos básicos de prevención
     * @private
     */
    _configurarEventosBasicos() {
        // No bloquear menú contextual - permitir que el navegador muestre el menú nativo
        // con opción de pegar (Ctrl+V y clic derecho)
    }

    /**
     * Configurar eventos específicos de drag & drop
     * @private
     */
    _configurarEventosDrag() {
        // Eventos de drag
        this.elemento.addEventListener('dragover', (e) => {
            if (!this.estaActivo) return;
            
            UIHelperService.preventDefaults(e);
            this._aplicarEstilosDragOver();
            
            if (this.callbacks.onDragOver) {
                this.callbacks.onDragOver(e);
            }
        });

        this.elemento.addEventListener('dragenter', (e) => {
            if (!this.estaActivo) return;
            UIHelperService.preventDefaults(e);
        });

        this.elemento.addEventListener('dragleave', (e) => {
            if (!this.estaActivo) return;
            
            UIHelperService.preventDefaults(e);
            this._restaurarEstilos();
            
            if (this.callbacks.onDragLeave) {
                this.callbacks.onDragLeave(e);
            }
        });

        // Evento drop
        this.elemento.addEventListener('drop', (e) => {
            if (!this.estaActivo) return;
            
            UIHelperService.preventDefaults(e);
            this._restaurarEstilos();
            
            const archivos = this._procesarDrop(e);
            if (archivos && this.callbacks.onDrop) {
                this.callbacks.onDrop(archivos, e);
            }
        });
    }

    /**
     * Configurar eventos de interacción (click, focus, paste)
     * @private
     */
    _configurarEventosInteraccion() {
        // Evento click
        this.elemento.addEventListener('click', (e) => {
            if (!this.estaActivo) return;
            
            // Solo procesar click izquierdo (button === 0)
            // Permitir click derecho y botones auxiliares para que funcione el menú nativo
            if (e.button !== 0) return;
            
            UIHelperService.preventDefaults(e);
            
            // Enfocar el elemento
            this.elemento.focus();
            
            if (this.callbacks.onClick) {
                this.callbacks.onClick(e);
            }
        });

        // Eventos de focus
        this.elemento.addEventListener('focus', (e) => {
            if (!this.estaActivo) return;
            UIHelperService.aplicarEstilosFocus(this.elemento);
        });

        this.elemento.addEventListener('blur', (e) => {
            if (!this.estaActivo) return;
            UIHelperService.quitarEstilosFocus(this.elemento);
        });

        // Evento paste delegado a DragDropManager (listener global)
        // No agregar listener local para evitar conflictos con el global
        // que ya maneja correctamente el Ctrl+V y el menú contextual

        // Hacer focusable
        UIHelperService.hacerFocusable(this.elemento);
    }

    /**
     * Aplicar estilos visuales durante drag over
     * @private
     */
    _aplicarEstilosDragOver() {
        const estilos = { ...this.options.estilosDragOver };
        
        // Aplicar estilos de transformación si están configurados
        if (this.options.estilosTransform) {
            Object.assign(estilos, this.options.estilosTransform);
        }
        
        UIHelperService.aplicarEstilosDragOver(this.elemento, estilos);
    }

    /**
     * Restaurar estilos normales
     * @private
     */
    _restaurarEstilos() {
        UIHelperService.restaurarEstilos(this.elemento);
    }

    /**
     * Procesar archivos del evento drop
     * @param {DragEvent} e - Evento drop
     * @returns {FileList|null} Archivos procesados o null si hay error
     * @private
     */
    _procesarDrop(e) {
        const files = e.dataTransfer.files;
        
        if (!files || files.length === 0) {
            UIHelperService.log('DragDropEventHandler', 'No se arrastraron archivos', 'warn');
            return null;
        }

        // Verificar límite de archivos
        if (files.length > this.options.maxArchivos) {
            const mensaje = `Solo se permiten máximo ${this.options.maxArchivos} archivo(s)`;
            UIHelperService.log('DragDropEventHandler', mensaje, 'warn');
            
            if (this.callbacks.onError) {
                this.callbacks.onError(mensaje);
            } else {
                UIHelperService.mostrarModalError(mensaje);
            }
            return null;
        }

        // Verificar que sean imágenes si está configurado
        if (this.options.soloImagenes) {
            for (let i = 0; i < files.length; i++) {
                if (!UIHelperService.esImagen(files[i])) {
                    const mensaje = 'Por favor arrastra solo archivos de imagen';
                    UIHelperService.log('DragDropEventHandler', mensaje, 'warn');
                    
                    if (this.callbacks.onError) {
                        this.callbacks.onError(mensaje);
                    } else {
                        UIHelperService.mostrarModalError(mensaje);
                    }
                    return null;
                }
            }
        }

        UIHelperService.log('DragDropEventHandler', `Archivos válidos procesados: ${files.length}`);
        return files;
    }

    /**
     * Procesar archivos del evento paste
     * @param {ClipboardEvent} e - Evento paste
     * @returns {FileList|null} Archivos procesados o null si hay error
     * @private
     */
    _procesarPaste(e) {
        const items = e.clipboardData.items;
        
        if (!items || items.length === 0) {
            UIHelperService.log('DragDropEventHandler', 'No hay items en el portapapeles', 'warn');
            return null;
        }

        // Buscar imágenes en el portapapeles
        const archivos = [];
        for (let i = 0; i < items.length; i++) {
            const item = items[i];
            
            // Verificar si es una imagen
            if (this.options.soloImagenes && item.type.startsWith('image/')) {
                const file = item.getAsFile();
                if (file) {
                    archivos.push(file);
                    
                    // Limitar al máximo configurado
                    if (archivos.length >= this.options.maxArchivos) {
                        break;
                    }
                }
            } else if (!this.options.soloImagenes && item.kind === 'file') {
                const file = item.getAsFile();
                if (file) {
                    archivos.push(file);
                    
                    if (archivos.length >= this.options.maxArchivos) {
                        break;
                    }
                }
            }
        }

        if (archivos.length === 0) {
            const mensaje = this.options.soloImagenes 
                ? 'El portapapeles no contiene imágenes válidas'
                : 'El portapapeles no contiene archivos válidos';
            
            UIHelperService.log('DragDropEventHandler', mensaje, 'warn');
            
            if (this.callbacks.onError) {
                this.callbacks.onError(mensaje);
            } else {
                UIHelperService.mostrarModalError(mensaje);
            }
            return null;
        }

        UIHelperService.log('DragDropEventHandler', `Archivos del portapapeles procesados: ${archivos.length}`);
        
        // Convertir a FileList-like
        const dataTransfer = new DataTransfer();
        archivos.forEach(file => dataTransfer.items.add(file));
        return dataTransfer.files;
    }

    /**
     * Actualizar opciones de configuración
     * @param {Object} nuevasOpciones - Nuevas opciones a mezclar
     * @returns {DragDropEventHandler} Instancia para encadenamiento
     */
    actualizarOpciones(nuevasOpciones) {
        this.options = { ...this.options, ...nuevasOpciones };
        
        if (nuevasOpciones.callbacks) {
            this.callbacks = { ...this.callbacks, ...nuevasOpciones.callbacks };
        }
        
        UIHelperService.log('DragDropEventHandler', 'Opciones actualizadas');
        return this;
    }

    /**
     * Obtener estado actual del handler
     * @returns {Object} Estado actual
     */
    getEstado() {
        return {
            activo: this.estaActivo,
            elemento: this.elemento,
            opciones: this.options,
            callbacks: Object.keys(this.callbacks)
        };
    }

    /**
     * Crear un input temporal con los archivos procesados
     * @param {FileList} files - Archivos a incluir
     * @returns {HTMLInputElement} Input temporal
     */
    crearInputTemporal(files) {
        return UIHelperService.crearInputTemporal(files);
    }

    /**
     * Destruir el handler y limpiar recursos
     */
    destruir() {
        this.estaActivo = false;
        this.elemento = null;
        this.callbacks = {};
        
        UIHelperService.log('DragDropEventHandler', 'Handler destruido');
    }
}

// Exportar para uso en módulos
if (typeof module !== 'undefined' && module.exports) {
    module.exports = DragDropEventHandler;
}

// Asignar al window para uso global
window.DragDropEventHandler = DragDropEventHandler;
