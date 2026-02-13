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
