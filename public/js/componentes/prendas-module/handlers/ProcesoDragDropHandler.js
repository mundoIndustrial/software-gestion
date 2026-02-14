/**
 * ================================================
 * PROCESO DRAG & DROP HANDLER
 * ================================================
 * 
 * Manejo específico de drag & drop para imágenes de procesos
 * Soporta múltiples procesos (1, 2, 3) con funcionalidad especializada
 * 
 * @class ProcesoDragDropHandler
 */

class ProcesoDragDropHandler {
    constructor() {
        this.handlers = new Map(); // Mapa de handlers por número de proceso
        this.maxProcesos = 3;
    }

    /**
     * Configurar drag & drop para un proceso específico
     * @param {HTMLElement} previewElement - Elemento preview del proceso
     * @param {number} procesoNumero - Número del proceso (1, 2, 3)
     * @returns {ProcesoDragDropHandler} Instancia para encadenamiento
     */
    configurarProceso(previewElement, procesoNumero) {
        if (!previewElement) {
            // UIHelperService.log('ProcesoDragDropHandler', `Preview no proporcionado para proceso ${procesoNumero}`, 'error');
            return this;
        }

        if (procesoNumero < 1 || procesoNumero > this.maxProcesos) {
            UIHelperService.log('ProcesoDragDropHandler', `Número de proceso inválido: ${procesoNumero}`, 'error');
            return this;
        }

        // Destruir handler anterior si existe
        if (this.handlers.has(procesoNumero)) {
            const handlerAnterior = this.handlers.get(procesoNumero);
            handlerAnterior.destruir();
        }

        // Crear handler base con configuración específica para procesos
        const handler = new DragDropEventHandler({
            estilosDragOver: {
                background: 'rgba(59, 130, 246, 0.1)',
                border: '2px dashed #3b82f6',
                opacity: '0.8',
                transform: 'scale(1.05)'
            },
            soloImagenes: true,
            maxArchivos: 1,
            callbacks: {
                onDragOver: (e) => this._onDragOver(e, procesoNumero),
                onDragLeave: (e) => this._onDragLeave(e, procesoNumero),
                onDrop: (files, e) => this._onDrop(files, e, procesoNumero),
                onClick: (e) => this._onClick(e, procesoNumero),
                onPaste: (files, e) => this._onPaste(files, e, procesoNumero),
                onError: (mensaje) => this._onError(mensaje, procesoNumero)
            }
        });

        handler.configurar(previewElement);
        this.handlers.set(procesoNumero, handler);
        
        // UIHelperService.log('ProcesoDragDropHandler', `Proceso ${procesoNumero} configurado`);
        return this;
    }

    /**
     * Manejar evento drag over
     * @param {DragEvent} e - Evento drag over
     * @param {number} procesoNumero - Número del proceso
     * @private
     */
    _onDragOver(e, procesoNumero) {
        // UIHelperService.log('ProcesoDragDropHandler', `Drag over en proceso ${procesoNumero}`);
    }

    /**
     * Manejar evento drag leave
     * @param {DragEvent} e - Evento drag leave
     * @param {number} procesoNumero - Número del proceso
     * @private
     */
    _onDragLeave(e, procesoNumero) {
        // UIHelperService.log('ProcesoDragDropHandler', `Drag leave en proceso ${procesoNumero}`);
    }

    /**
     * Manejar evento drop
     * @param {FileList} files - Archivos arrastrados
     * @param {DragEvent} e - Evento drop
     * @param {number} procesoNumero - Número del proceso
     * @private
     */
    _onDrop(files, e, procesoNumero) {
        // UIHelperService.log('ProcesoDragDropHandler', `Drop en proceso ${procesoNumero}: ${files.length} archivos`);
        
        const handler = this.handlers.get(procesoNumero);
        if (handler) {
            const tempInput = handler.crearInputTemporal(files);
            this._procesarImagenProceso(tempInput, procesoNumero);
        }
    }

    /**
     * Manejar evento click
     * @param {MouseEvent} e - Evento click
     * @param {number} procesoNumero - Número del proceso
     * @private
     */
    _onClick(e, procesoNumero) {
        // UIHelperService.log('ProcesoDragDropHandler', `Click en proceso ${procesoNumero}`);
        
        // Solo procesar click izquierdo
        if (e.button !== 0) return;
        
        // Obtener el preview
        const preview = document.getElementById(`proceso-foto-preview-${procesoNumero}`);
        
        // Verificar si hay imágenes en el preview
        let imagenesParaGaleria = [];
        const imgs = preview ? preview.querySelectorAll('img') : [];
        imagenesParaGaleria = Array.from(imgs)
            .map(img => img.src)
            .filter(src => src && src.length > 0);
        
        UIHelperService.log('ProcesoDragDropHandler', `Imágenes encontradas en proceso ${procesoNumero}: ${imagenesParaGaleria.length}`);
        
        // Si hay imágenes y la función de galería está disponible, abrir la galería
        // La función debe estar nombrada como: abrirGaleriaProceso{Numero} o abrirGaleriaproceso{Numero}
        const functionNamePascal = `abrirGaleriaProceso${procesoNumero}`;
        const functionNameLower = `abrirGaleriaproceso${procesoNumero}`;
        const galeriaFunction = window[functionNamePascal] || window[functionNameLower];
        
        if (imagenesParaGaleria.length > 0 && typeof galeriaFunction === 'function') {
            UIHelperService.log('ProcesoDragDropHandler', ` Abriendo galería modal para proceso ${procesoNumero}`);
            e.preventDefault();
            e.stopPropagation();
            galeriaFunction(imagenesParaGaleria);
            return;
        }
        
        // Si no hay imágenes o la galería no está disponible, abrir el selector de archivos
        UIHelperService.log('ProcesoDragDropHandler', `Abriendo selector de archivos para proceso ${procesoNumero}`);
        const inputId = `proceso-foto-input-${procesoNumero}`;
        const inputElement = document.getElementById(inputId);
        
        if (inputElement) {
            e.preventDefault();
            e.stopPropagation();
            // UIHelperService.log('ProcesoDragDropHandler', `Abriendo input ${inputId}`);
            inputElement.click();
        } else {
            UIHelperService.log('ProcesoDragDropHandler', `Input ${inputId} no encontrado`, 'warn');
        }
    }

    /**
     * Manejar evento paste
     * @param {FileList} files - Archivos del portapapeles
     * @param {ClipboardEvent} e - Evento paste
     * @param {number} procesoNumero - Número del proceso
     * @private
     */
    _onPaste(files, e, procesoNumero) {
        // UIHelperService.log('ProcesoDragDropHandler', `Paste en proceso ${procesoNumero}: ${files.length} archivos`);
        
        const handler = this.handlers.get(procesoNumero);
        if (handler) {
            const tempInput = handler.crearInputTemporal(files);
            this._procesarImagenProceso(tempInput, procesoNumero);
        }
    }

    /**
     * Manejar errores
     * @param {string} mensaje - Mensaje de error
     * @param {number} procesoNumero - Número del proceso
     * @private
     */
    _onError(mensaje, procesoNumero) {
        UIHelperService.log('ProcesoDragDropHandler', `Error en proceso ${procesoNumero}: ${mensaje}`, 'error');
        UIHelperService.mostrarModalError(mensaje);
    }

    /**
     * Procesar imágenes de proceso usando la función global existente
     * @param {HTMLInputElement} input - Input con archivos
     * @param {number} procesoNumero - Número del proceso
     * @private
     */
    _procesarImagenProceso(input, procesoNumero) {
        // Usar la función global existente si está disponible
        if (typeof window.manejarImagenProceso === 'function') {
            window.manejarImagenProceso(input, procesoNumero);
            UIHelperService.log('ProcesoDragDropHandler', `Imagen de proceso ${procesoNumero} procesada con función global`);
        } else {
            UIHelperService.log('ProcesoDragDropHandler', 'Función manejarImagenProceso no disponible', 'error');
            UIHelperService.mostrarModalError('No se pudo procesar la imagen del proceso. Función de manejo no disponible.');
        }
    }

    /**
     * Configurar múltiples procesos automáticamente
     * @returns {ProcesoDragDropHandler} Instancia para encadenamiento
     */
    configurarTodos() {
        // UIHelperService.log('ProcesoDragDropHandler', 'Iniciando configuración de todos los procesos');
        
        for (let i = 1; i <= this.maxProcesos; i++) {
            const preview = document.getElementById(`proceso-foto-preview-${i}`);
            
            if (preview) {
                this.configurarProceso(preview, i);
                // UIHelperService.log('ProcesoDragDropHandler', `Proceso ${i} configurado`);
            } else {
                // UIHelperService.log('ProcesoDragDropHandler', `Preview ${i} no encontrado`);
            }
        }
        
        // UIHelperService.log('ProcesoDragDropHandler', 'Configuración de procesos completada');
        return this;
    }

    /**
     * Obtener handler de un proceso específico
     * @param {number} procesoNumero - Número del proceso
     * @returns {DragDropEventHandler|null} Handler del proceso
     */
    getHandler(procesoNumero) {
        return this.handlers.get(procesoNumero) || null;
    }

    /**
     * Obtener estado actual de todos los handlers
     * @returns {Object} Estado actual
     */
    getEstado() {
        const estado = {
            procesosConfigurados: this.handlers.size,
            maxProcesos: this.maxProcesos,
            handlers: {}
        };

        this.handlers.forEach((handler, numero) => {
            estado.handlers[numero] = handler.getEstado();
        });

        return estado;
    }

    /**
     * Desactivar todos los handlers
     */
    desactivar() {
        this.handlers.forEach((handler, numero) => {
            handler.desactivar();
        });
        UIHelperService.log('ProcesoDragDropHandler', 'Todos los handlers desactivados');
    }

    /**
     * Destruir todos los handlers y limpiar recursos
     */
    destruir() {
        this.handlers.forEach((handler, numero) => {
            handler.destruir();
        });
        this.handlers.clear();
        UIHelperService.log('ProcesoDragDropHandler', 'Todos los handlers destruidos');
    }

    /**
     * Comando de debugging para investigar menús contextuales
     * @static
     */
    static debugContextMenu() {
        console.log('=== DEBUG: Buscando menús de contexto de procesos ===');
        
        // Buscar todos los menús contextuales en el DOM
        const menus = document.querySelectorAll('[class*="context-menu"]');
        console.log(`Menús encontrados en el DOM: ${menus.length}`);
        
        menus.forEach((menu, idx) => {
            const rect = menu.getBoundingClientRect();
            console.log(`Menú ${idx}:`, {
                clase: menu.className,
                visible: rect.width > 0 && rect.height > 0,
                posición: `(${Math.round(rect.x)}, ${Math.round(rect.y)})`,
                tamaño: `${Math.round(rect.width)}x${Math.round(rect.height)}`,
                zIndex: window.getComputedStyle(menu).zIndex
            });
        });
    }

    /**
     * Comando para simular un clic derecho
     * @param {number} procesoNumero - Número del proceso a probar
     * @static
     */
    static testRightClick(procesoNumero = 1) {
        console.log(`Simulando clic derecho en proceso ${procesoNumero}...`);
        
        const preview = document.getElementById(`proceso-foto-preview-${procesoNumero}`);
        if (!preview) {
            console.error(`Preview ${procesoNumero} no encontrado`);
            return;
        }
        
        const event = new MouseEvent('mousedown', {
            bubbles: true,
            cancelable: true,
            button: 2, // Botón derecho
            clientX: 200,
            clientY: 300,
        });
        
        preview.dispatchEvent(event);
        console.log('Evento enviado. Revisa la consola para los logs');
    }
}

// Exportar para uso en módulos
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ProcesoDragDropHandler;
}

// Asignar al window para uso global
window.ProcesoDragDropHandler = ProcesoDragDropHandler;
