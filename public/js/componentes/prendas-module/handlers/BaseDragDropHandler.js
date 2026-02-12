/**
 * ================================================
 * BASE DRAG & DROP HANDLER
 * ================================================
 * 
 * Clase base abstracta que proporciona funcionalidad común
 * para todos los handlers de drag & drop usando polimorfismo
 * 
 * @class BaseDragDropHandler
 */
class BaseDragDropHandler {
    constructor() {
        this.handler = null;
        this.maxArchivos = 1;
        this.tipo = 'base';
        this.opcionesMenu = {
            textoPegar: 'Pegar imagen',
            iconoPegar: 'content_paste'
        };
    }

    /**
     * Configurar menú contextual genérico
     * @param {HTMLElement} elemento - Elemento donde se configurará el menú
     * @private
     */
    _configurarMenuContextual(elemento) {
        UIHelperService.log(`${this.constructor.name}`, `🔧 Configurando menú contextual para ${this.tipo}...`);
        UIHelperService.log(`${this.constructor.name}`, `📌 Elemento: ${elemento.id || elemento.tagName}`);
        
        // Evento mousedown para menú contextual (botón derecho) - captura para interceptar antes que otros elementos
        elemento.addEventListener('mousedown', (e) => {
            UIHelperService.log(`${this.constructor.name}`, `🖱️ Mousedown - button: ${e.button}, target: ${e.target.id || e.target.tagName}, target class: ${e.target.className || 'no-class'}`);
            if (e.button === 2) { // Botón derecho
                UIHelperService.log(`${this.constructor.name}`, `🖱️ Mousedown botón derecho detectado en ${this.tipo}`);
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                
                // Enfocar el elemento
                elemento.focus();
                UIHelperService.log(`${this.constructor.name}`, `🎯 Elemento enfocado`);
                
                // Mostrar menú contextual
                UIHelperService.log(`${this.constructor.name}`, `📋 Llamando a mostrarMenuContextual...`);
                this.mostrarMenuContextual(e);
                return false;
            }
        }, true); // Usar captura para interceptar antes que otros listeners

        // Evento contextmenu adicional - captura para asegurar que se ejecute
        elemento.addEventListener('contextmenu', (e) => {
            UIHelperService.log(`${this.constructor.name}`, `🖱️ Contextmenu - target: ${e.target.id || e.target.tagName}, target class: ${e.target.className || 'no-class'}`);
            UIHelperService.log(`${this.constructor.name}`, `🖱️ Contextmenu detectado en ${this.tipo}`);
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            
            // Enfocar el elemento
            elemento.focus();
            UIHelperService.log(`${this.constructor.name}`, `🎯 Elemento enfocado (contextmenu)`);
            
            // Mostrar menú contextual
            UIHelperService.log(`${this.constructor.name}`, `📋 Llamando a mostrarMenuContextual (contextmenu)...`);
            this.mostrarMenuContextual(e);
            
            return false;
        }, true); // Usar captura para asegurar que se ejecute
        
        // También configurar menú contextual en elementos internos (como el botón)
        const boton = elemento.querySelector('button');
        if (boton) {
            UIHelperService.log(`${this.constructor.name}`, `🔗 Botón interno encontrado en ${this.tipo}: ${boton.id || boton.tagName}`);
            
            boton.addEventListener('mousedown', (e) => {
                UIHelperService.log(`${this.constructor.name}`, `🖱️ Mousedown en botón - button: ${e.button}`);
                if (e.button === 2) { // Botón derecho
                    UIHelperService.log(`${this.constructor.name}`, `🖱️ Mousedown botón derecho en botón interno de ${this.tipo}`);
                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();
                    
                    // Enfocar el elemento principal
                    elemento.focus();
                    UIHelperService.log(`${this.constructor.name}`, `🎯 Elemento principal enfocado (botón)`);
                    
                    // Mostrar menú contextual
                    UIHelperService.log(`${this.constructor.name}`, `📋 Llamando a mostrarMenuContextual (botón)...`);
                    this.mostrarMenuContextual(e);
                    return false;
                }
            }, true);
            
            boton.addEventListener('contextmenu', (e) => {
                UIHelperService.log(`${this.constructor.name}`, `🖱️ Contextmenu en botón - target: ${e.target.id || e.target.tagName}`);
                UIHelperService.log(`${this.constructor.name}`, `🖱️ Contextmenu en botón interno de ${this.tipo}`);
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                
                // Enfocar el elemento principal
                elemento.focus();
                UIHelperService.log(`${this.constructor.name}`, `🎯 Elemento principal enfocado (contextmenu botón)`);
                
                // Mostrar menú contextual
                UIHelperService.log(`${this.constructor.name}`, `📋 Llamando a mostrarMenuContextual (contextmenu botón)...`);
                this.mostrarMenuContextual(e);
                
                return false;
            }, true);
        } else {
            UIHelperService.log(`${this.constructor.name}`, `⚠️ No se encontró botón interno en ${this.tipo}`);
        }
        
        UIHelperService.log(`${this.constructor.name}`, `✅ Menú contextual configurado para ${this.tipo}`);
    }

    /**
     * Mostrar menú contextual (método a implementar por subclases)
     * @param {MouseEvent} e - Evento que activa el menú
     */
    mostrarMenuContextual(e) {
        // Este método debe ser implementado por las subclases
        UIHelperService.log('BaseDragDropHandler', '⚠️ mostrarMenuContextual debe ser implementado por la subclase', 'warn');
        throw new Error('El método mostrarMenuContextual debe ser implementado por la subclase');
    }

    /**
     * Pegar imagen desde menú contextual (método genérico)
     * @private
     */
    async _pegarDesdeMenuContextual() {
        UIHelperService.log(`${this.constructor.name}`, `🖱️ Iniciando pegado desde menú contextual (${this.tipo})...`);
        
        try {
            // Verificar si ClipboardService está disponible
            if (!window.ClipboardService) {
                UIHelperService.log(`${this.constructor.name}`, '❌ ClipboardService no disponible', 'error');
                throw new Error('ClipboardService no disponible');
            }

            UIHelperService.log(`${this.constructor.name}`, '✅ ClipboardService disponible, intentando leer...');

            // Leer imágenes del portapapeles
            const archivos = await ClipboardService.leerImagenes({ maxArchivos: this.maxArchivos });

            UIHelperService.log(`${this.constructor.name}`, `📁 Archivos obtenidos: ${archivos.length}`);

            if (archivos.length > 0) {
                const tempInput = UIHelperService.crearInputTemporal(archivos);
                this._procesarImagen(tempInput);
                UIHelperService.log(`${this.constructor.name}`, `✅ Imagen de ${this.tipo} pegada desde menú`);
            } else {
                UIHelperService.log(`${this.constructor.name}`, '⚠️ No se encontraron imágenes en el portapapeles', 'warn');
            }

        } catch (error) {
            UIHelperService.log(`${this.constructor.name}`, `❌ Error principal pegando desde menú: ${error.message}`, 'error');
            
            // Fallback mejorado: usar el portapapeles del navegador directamente
            try {
                UIHelperService.log(`${this.constructor.name}`, '🔄 Intentando fallback con navigator.clipboard...');
                
                if (navigator.clipboard && navigator.clipboard.read) {
                    const items = await navigator.clipboard.read();
                    UIHelperService.log(`${this.constructor.name}`, `📋 Items encontrados en fallback: ${items.length}`);
                    
                    const archivos = [];
                    
                    for (const item of items) {
                        UIHelperService.log(`${this.constructor.name}`, `🔍 Tipos en item: ${item.types.join(', ')}`);
                        
                        for (const type of item.types) {
                            if (type.startsWith('image/')) {
                                UIHelperService.log(`${this.constructor.name}`, `🖼️ Procesando tipo de imagen: ${type}`);
                                
                                const blob = await item.getType(type);
                                UIHelperService.log(`${this.constructor.name}`, `📦 Blob obtenido: ${blob.size} bytes`);
                                
                                const file = new File([blob], `${this.tipo}-${Date.now()}.${type.split('/')[1]}`, {
                                    type: type
                                });
                                archivos.push(file);
                                
                                // Limitar al máximo necesario
                                if (archivos.length >= this.maxArchivos) {
                                    break;
                                }
                            }
                        }
                        if (archivos.length >= this.maxArchivos) {
                            break;
                        }
                    }
                    
                    if (archivos.length > 0) {
                        const tempInput = UIHelperService.crearInputTemporal(archivos);
                        this._procesarImagen(tempInput);
                        UIHelperService.log(`${this.constructor.name}`, `✅ Imagen de ${this.tipo} pegada con fallback`);
                    } else {
                        UIHelperService.mostrarModalError('No se encontraron imágenes en el portapapeles. Por favor copia una imagen primero.');
                    }
                } else {
                    UIHelperService.log(`${this.constructor.name}`, '❌ navigator.clipboard.read no disponible', 'error');
                    UIHelperService.mostrarModalError('Por favor usa Ctrl+V para pegar la imagen.');
                }
            } catch (fallbackError) {
                UIHelperService.log(`${this.constructor.name}`, `❌ Error en fallback: ${fallbackError.message}`, 'error');
                UIHelperService.mostrarModalError('No se pudo acceder al portapapeles. Por favor usa Ctrl+V para pegar la imagen.');
            }
        }
    }

    /**
     * Procesar imagen (método a implementar por subclases)
     * @param {HTMLInputElement} input - Input con archivos
     * @private
     */
    _procesarImagen(input) {
        // Este método debe ser implementado por las subclases
        throw new Error('El método _procesarImagen debe ser implementado por la subclase');
    }

    /**
     * Crear opciones de menú contextual específicas para el tipo
     * @returns {Array} Array de opciones para el menú
     * @protected
     */
    _crearOpcionesMenu() {
        return [
            ContextMenuService.crearOpcionPegar((e, opcion) => {
                this._pegarDesdeMenuContextual();
            }, {
                texto: this.opcionesMenu.textoPegar,
                icono: this.opcionesMenu.iconoPegar
            })
        ];
    }

    /**
     * Configurar handler base con opciones comunes
     * @param {HTMLElement} elemento - Elemento a configurar
     * @param {Object} opcionesEspecificas - Opciones específicas del handler
     * @returns {BaseDragDropHandler} Instancia para encadenamiento
     * @protected
     */
    _configurarHandlerBase(elemento, opcionesEspecificas = {}) {
        UIHelperService.log(`${this.constructor.name}`, `🔧 _configurarHandlerBase llamado para ${this.tipo}...`);
        UIHelperService.log(`${this.constructor.name}`, `📌 Elemento: ${elemento.id || elemento.tagName}`);
        
        const opcionesComunes = {
            tieneMenuContextual: true,
            callbacks: {
                onDragOver: (e) => this._onDragOver(e),
                onDragLeave: (e) => this._onDragLeave(e),
                onDrop: (files, e) => this._onDrop(files, e),
                onClick: (e) => this._onClick(e),
                onPaste: (files, e) => this._onPaste(files, e),
                onError: (mensaje) => this._onError(mensaje),
                ...opcionesEspecificas.callbacks
            },
            ...opcionesEspecificas
        };

        UIHelperService.log(`${this.constructor.name}`, `📋 Creando DragDropEventHandler...`);
        this.handler = new DragDropEventHandler(opcionesComunes);
        UIHelperService.log(`${this.constructor.name}`, `📋 Configurando handler...`);
        this.handler.configurar(elemento);
        
        // Agregar menú contextual
        UIHelperService.log(`${this.constructor.name}`, `📋 Configurando menú contextual desde _configurarHandlerBase...`);
        this._configurarMenuContextual(elemento);
        
        // Agregar listener directo como fallback para garantizar que funcione
        UIHelperService.log(`${this.constructor.name}`, `🔧 Agregando listener directo de fallback...`);
        
        // Usar setTimeout para asegurar que nuestro listener se agregue después del DragDropEventHandler
        setTimeout(() => {
            const fallbackListener = (e) => {
                UIHelperService.log(`${this.constructor.name}`, `🚨 FALLBACK: Listener ejecutado - button: ${e.button}, target: ${e.target.id || e.target.tagName}`);
                
                // Incrementar contador global para diagnóstico
                if (window.DragDropManager && window.DragDropManager.incrementarFallbackCounter) {
                    window.DragDropManager.incrementarFallbackCounter();
                }
                
                if (e.button === 2) { // Botón derecho
                    UIHelperService.log(`${this.constructor.name}`, `🚨 FALLBACK: mousedown botón derecho detectado en ${this.tipo}`);
                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();
                    
                    // Enfocar el elemento
                    elemento.focus();
                    
                    // Mostrar menú contextual
                    UIHelperService.log(`${this.constructor.name}`, `🚨 FALLBACK: Llamando a mostrarMenuContextual...`);
                    this.mostrarMenuContextual(e);
                    return false;
                }
            };
            
            elemento.addEventListener('mousedown', fallbackListener, true); // Usar captura para máxima prioridad
            UIHelperService.log(`${this.constructor.name}`, `✅ Listener de fallback agregado con capture: true (después de timeout)`);
        }, 100); // Pequeño delay para asegurar que se ejecute después
        
        UIHelperService.log(`${this.constructor.name}`, `✅ _configurarHandlerBase completado para ${this.tipo}`);
        return this;
    }

    /**
     * Manejar evento drag over (método genérico)
     * @param {DragEvent} e - Evento drag over
     * @private
     */
    _onDragOver(e) {
        UIHelperService.log(`${this.constructor.name}`, 'Drag over');
    }

    /**
     * Manejar evento drag leave (método genérico)
     * @param {DragEvent} e - Evento drag leave
     * @private
     */
    _onDragLeave(e) {
        UIHelperService.log(`${this.constructor.name}`, 'Drag leave');
    }

    /**
     * Manejar evento drop (método a implementar por subclases)
     * @param {FileList} files - Archivos arrastrados
     * @param {DragEvent} e - Evento drop
     * @private
     */
    _onDrop(files, e) {
        // Este método debe ser implementado por las subclases
        throw new Error('El método _onDrop debe ser implementado por la subclase');
    }

    /**
     * Manejar evento click (método genérico)
     * @param {MouseEvent} e - Evento click
     * @private
     */
    _onClick(e) {
        UIHelperService.log(`${this.constructor.name}`, 'Click');
    }

    /**
     * Manejar evento paste (método genérico)
     * @param {FileList} files - Archivos del portapapeles
     * @param {ClipboardEvent} e - Evento paste
     * @private
     */
    _onPaste(files, e) {
        UIHelperService.log(`${this.constructor.name}`, `Paste detectado: ${files.length} archivos`);
        this._procesarImagen(UIHelperService.crearInputTemporal(files));
    }

    /**
     * Manejar errores (método genérico)
     * @param {string} mensaje - Mensaje de error
     * @private
     */
    _onError(mensaje) {
        UIHelperService.log(`${this.constructor.name}`, `Error: ${mensaje}`, 'error');
        UIHelperService.mostrarModalError(mensaje);
    }

    /**
     * Obtener estado actual del handler
     * @returns {Object} Estado actual
     */
    getEstado() {
        return {
            configurado: !!this.handler,
            tipo: this.tipo,
            maxArchivos: this.maxArchivos,
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
        UIHelperService.log(`${this.constructor.name}`, 'Handler desactivado');
    }

    /**
     * Destruir el handler y limpiar recursos
     */
    destruir() {
        if (this.handler) {
            this.handler.destruir();
        }
        this.handler = null;
        UIHelperService.log(`${this.constructor.name}`, 'Handler destruido');
    }
}

// Exportar para uso en módulos
if (typeof module !== 'undefined' && module.exports) {
    module.exports = BaseDragDropHandler;
}

// Asignar al window para uso global
window.BaseDragDropHandler = BaseDragDropHandler;
