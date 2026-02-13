/**
 * ================================================
 * DRAG & DROP MANAGER
 * ================================================
 * 
 * Orquestador principal para el sistema de drag & drop
 * Coordina todos los handlers especializados y proporciona una API unificada
 * 
 * @class DragDropManager
 */

class DragDropManager {
    constructor() {
        // No crear instancias en el constructor - esperar a inicialización
        this.prendaHandler = null;
        this.telaHandler = null;
        this.procesoHandler = null;
        this.inicializado = false;
        this.globalPasteListenerConfigurado = false;
        
        // Contador para verificar si el listener de fallback se ejecuta
        this.fallbackListenerCounter = 0;
        
        // Rastrear posición actual del mouse
        this.mousePosition = { x: 0, y: 0 };
        this._setupMouseTracking();
    }

    /**
     * Configurar rastreo de posición del mouse
     * @private
     */
    _setupMouseTracking() {
        // Actualizar posición del mouse cuando se mueve
        document.addEventListener('mousemove', (e) => {
            this.mousePosition.x = e.clientX;
            this.mousePosition.y = e.clientY;
        }, { passive: true });
    }

    /**
     * Inicializar todo el sistema de drag & drop
     * @returns {DragDropManager} Instancia para encadenamiento
     */
    inicializar() {
        if (this.inicializado) {
            UIHelperService.log('DragDropManager', 'Sistema ya inicializado', 'warn');
            return this;
        }


        // Verificar dependencias antes de crear instancias
        if (!window.PrendaDragDropHandler || !window.TelaDragDropHandler || !window.ProcesoDragDropHandler) {
            UIHelperService.log('DragDropManager', '❌ Dependencias no disponibles:', 'error');
            UIHelperService.log('DragDropManager', `- PrendaDragDropHandler: ${!!window.PrendaDragDropHandler}`);
            UIHelperService.log('DragDropManager', `- TelaDragDropHandler: ${!!window.TelaDragDropHandler}`);
            UIHelperService.log('DragDropManager', `- ProcesoDragDropHandler: ${!!window.ProcesoDragDropHandler}`);
            throw new Error('Dependencias no disponibles: PrendaDragDropHandler, TelaDragDropHandler, o ProcesoDragDropHandler');
        }


        // Crear instancias ahora que las clases están disponibles
        this.prendaHandler = new PrendaDragDropHandler();
        this.telaHandler = new TelaDragDropHandler();
        this.procesoHandler = new ProcesoDragDropHandler();

  
        // Configurar listener global de paste
        this._configurarListenerGlobalPaste();

        // Inicializar componentes específicos
        this._inicializarPrendas();
        this._inicializarTelas();
        this._inicializarProcesos();

        this.inicializado = true;
        // UIHelperService.log('DragDropManager', 'Sistema de drag & drop inicializado completamente');
        return this;
    }

    /**
     * Configurar listener global de paste
     * @private
     */
    _configurarListenerGlobalPaste() {
        if (this.globalPasteListenerConfigurado) {
            return;
        }

        document.addEventListener('paste', (e) => {
            UIHelperService.log('DragDropManager', '📋 EVENTO PASTE DETECTADO');
            
            const preview = document.getElementById('nueva-prenda-foto-preview');
            const modal = document.getElementById('modal-agregar-prenda-nueva');
            
            UIHelperService.log('DragDropManager', `Preview encontrado: ${!!preview}, Modal encontrado: ${!!modal}`);
            
            // Validar que el modal esté visible
            if (!modal || !preview) {
                UIHelperService.log('DragDropManager', 'Modal o preview no existen');
                return;
            }
            
            // Comprobar si el modal está visible
            const isModalVisible = UIHelperService.isModalVisible(modal);
            UIHelperService.log('DragDropManager', `Modal visible: ${isModalVisible}`);
            
            if (!isModalVisible) {
                UIHelperService.log('DragDropManager', 'Modal no está visible, ignorando paste');
                return;
            }
            
            UIHelperService.log('DragDropManager', '✅ Procesando pegado global...');
            
            e.preventDefault();
            e.stopPropagation();
            
            // Obtener items del portapapeles
            const items = e.clipboardData.items;
            if (!items || items.length === 0) {
                UIHelperService.log('DragDropManager', 'No hay items en el portapapeles', 'warn');
                return;
            }
            
            UIHelperService.log('DragDropManager', `Items disponibles: ${items.length}`);
            
            let foundImage = false;
            
            // Buscar imágenes en el portapapeles
            for (let i = 0; i < items.length; i++) {
                const item = items[i];
                UIHelperService.log('DragDropManager', `Item ${i}: ${item.type}, ${item.kind}`);
                
                // Verificar si es una imagen
                if (item.kind === 'file' && item.type.startsWith('image/')) {
                    UIHelperService.log('DragDropManager', `✅ Imagen encontrada: ${item.type}`);
                    foundImage = true;
                    
                    // Obtener el archivo
                    const file = item.getAsFile();
                    if (file) {
                        UIHelperService.log('DragDropManager', `Archivo obtenido: ${file.name}, ${file.type}, ${file.size}`);
                        
                        // Crear un input file temporal
                        const tempInput = UIHelperService.crearInputTemporal([file]);
                        
                        // Determinar qué handler usar según el elemento activo y el cursor
                        const elementoActivo = document.activeElement;
                        let elementoCursor = null;
                        let handlerCorrecto = null;
                        let funcionManejo = null;
                        
                        UIHelperService.log('DragDropManager', `🎯 Elemento activo: ${elementoActivo?.id || elementoActivo?.tagName || 'desconocido'}`);
                        
                        // Intentar obtener el elemento bajo el cursor (usar posición rastreada del mouse)
                        try {
                            // Usar la posición rastreada del mouse
                            const clientX = this.mousePosition.x;
                            const clientY = this.mousePosition.y;
                            
                            if (clientX && clientY && isFinite(clientX) && isFinite(clientY)) {
                                elementoCursor = document.elementFromPoint(clientX, clientY);
                                UIHelperService.log('DragDropManager', `🎯 Elemento bajo cursor: ${elementoCursor?.id || elementoCursor?.tagName || 'desconocido'} (${clientX}, ${clientY})`);
                            } else {
                                UIHelperService.log('DragDropManager', `⚠️ Posición del mouse no disponible, usando solo elemento activo`);
                            }
                        } catch (error) {
                            UIHelperService.log('DragDropManager', `⚠️ Error al obtener elemento bajo cursor: ${error.message}`);
                        }
                        
                        // Priorizar el elemento bajo el cursor para Ctrl+V
                        const elementoAnalizado = elementoCursor || elementoActivo;
                        
                        // Detectar el área activa
                        if (elementoAnalizado) {
                            // Obtener referencias a los elementos
                            const previewPrenda = document.getElementById('nueva-prenda-foto-preview');
                            const dropZoneTela = document.getElementById('nueva-prenda-tela-drop-zone');
                            const previewTela = document.getElementById('nueva-prenda-tela-preview');
                            
                            // Verificar si está en el área de prendas
                            if (previewPrenda && (previewPrenda.contains(elementoAnalizado) || previewPrenda === elementoAnalizado)) {
                                handlerCorrecto = 'prendas';
                                funcionManejo = window.manejarImagenesPrenda;
                                UIHelperService.log('DragDropManager', '🎯 Detectado área de prendas');
                            }
                            // Verificar si está en el área de telas
                            else if ((dropZoneTela && (dropZoneTela.contains(elementoAnalizado) || dropZoneTela === elementoAnalizado)) ||
                                     (previewTela && (previewTela.contains(elementoAnalizado) || previewTela === elementoAnalizado))) {
                                handlerCorrecto = 'telas';
                                funcionManejo = window.manejarImagenTela;
                                UIHelperService.log('DragDropManager', '🎯 Detectado área de telas');
                            }
                            // Verificar si está en el área de procesos
                            else {
                                const previewProceso1 = document.getElementById('proceso-foto-preview-1');
                                const previewProceso2 = document.getElementById('proceso-foto-preview-2');
                                const previewProceso3 = document.getElementById('proceso-foto-preview-3');
                                
                                let numeroProceso = null;
                                
                                // PRIMERO: Verificar si está directamente sobre un preview específico
                                if (previewProceso1 && (previewProceso1.contains(elementoAnalizado) || previewProceso1 === elementoAnalizado)) {
                                    numeroProceso = 1;
                                    UIHelperService.log('DragDropManager', '🎯 Detectado directamente sobre preview de proceso 1');
                                } else if (previewProceso2 && (previewProceso2.contains(elementoAnalizado) || previewProceso2 === elementoAnalizado)) {
                                    numeroProceso = 2;
                                    UIHelperService.log('DragDropManager', '🎯 Detectado directamente sobre preview de proceso 2');
                                } else if (previewProceso3 && (previewProceso3.contains(elementoAnalizado) || previewProceso3 === elementoAnalizado)) {
                                    numeroProceso = 3;
                                    UIHelperService.log('DragDropManager', '🎯 Detectado directamente sobre preview de proceso 3');
                                }
                                
                                // SEGUNDO: Si no está sobre un preview específico, verificar si está en el panel de procesos
                                if (!numeroProceso) {
                                    // Buscar el panel contenedor más cercano
                                    let panelProceso = elementoAnalizado.closest('.foto-panel');
                                    
                                    // Si no se encuentra por clase, buscar por otros selectores
                                    if (!panelProceso) {
                                        // Buscar contenedor que tenga los previews de proceso
                                        const parent = elementoAnalizado.parentElement;
                                        if (parent && (
                                            parent.contains(previewProceso1) || 
                                            parent.contains(previewProceso2) || 
                                            parent.contains(previewProceso3)
                                        )) {
                                            panelProceso = parent;
                                        }
                                    }
                                    
                                    if (panelProceso) {
                                        // Intentar determinar qué preview está más cerca del cursor
                                        const rect1 = previewProceso1?.getBoundingClientRect();
                                        const rect2 = previewProceso2?.getBoundingClientRect();
                                        const rect3 = previewProceso3?.getBoundingClientRect();
                                        
                                        if (rect1 && rect2 && rect3) {
                                            const cursorX = this.mousePosition.x;
                                            const cursorY = this.mousePosition.y;
                                            
                                            // Calcular distancia a cada preview
                                            const dist1 = Math.sqrt(Math.pow(cursorX - (rect1.left + rect1.width/2), 2) + Math.pow(cursorY - (rect1.top + rect1.height/2), 2));
                                            const dist2 = Math.sqrt(Math.pow(cursorX - (rect2.left + rect2.width/2), 2) + Math.pow(cursorY - (rect2.top + rect2.height/2), 2));
                                            const dist3 = Math.sqrt(Math.pow(cursorX - (rect3.left + rect3.width/2), 2) + Math.pow(cursorY - (rect3.top + rect3.height/2), 2));
                                            
                                            // Encontrar el preview más cercano
                                            if (dist1 <= dist2 && dist1 <= dist3) {
                                                numeroProceso = 1;
                                            } else if (dist2 <= dist1 && dist2 <= dist3) {
                                                numeroProceso = 2;
                                            } else {
                                                numeroProceso = 3;
                                            }
                                            
                                            UIHelperService.log('DragDropManager', `🎯 Panel detectado, preview más cercano: proceso ${numeroProceso} (distancias: 1=${dist1.toFixed(0)}, 2=${dist2.toFixed(0)}, 3=${dist3.toFixed(0)})`);
                                        } else {
                                            // Fallback: usar proceso 1 por defecto
                                            numeroProceso = 1;
                                            UIHelperService.log('DragDropManager', '🎯 Panel detectado, usando proceso 1 por defecto (no se pudieron calcular distancias)');
                                        }
                                    }
                                }
                                
                                if (numeroProceso) {
                                    handlerCorrecto = `proceso-${numeroProceso}`;
                                    funcionManejo = (input) => window.manejarImagenProceso(input, numeroProceso);
                                    UIHelperService.log('DragDropManager', `🎯 Detectado área de proceso ${numeroProceso}`);
                                }
                            }
                        }
                        
                        // Si no se detectó área específica, usar prendas por defecto
                        if (!funcionManejo) {
                            handlerCorrecto = 'prendas (defecto)';
                            funcionManejo = window.manejarImagenesPrenda;
                            UIHelperService.log('DragDropManager', '⚠️ Área no detectada, usando prendas por defecto');
                        }
                        
                        // Usar la función de manejo correcta
                        if (typeof funcionManejo === 'function') {
                            UIHelperService.log('DragDropManager', `✅ Llamando a manejarImagen${handlerCorrecto}...`);
                            funcionManejo(tempInput);
                            UIHelperService.log('DragDropManager', `✅ Imagen procesada exitosamente en ${handlerCorrecto}`);
                        } else {
                            UIHelperService.log('DragDropManager', `❌ manejarImagen${handlerCorrecto} no disponible`, 'error');
                        }
                        
                        // Salir después de procesar la primera imagen
                        break;
                    } else {
                        UIHelperService.log('DragDropManager', 'No se pudo obtener el archivo del item', 'warn');
                    }
                }
            }
            
            // Si no se encontraron imágenes
            if (!foundImage) {
                UIHelperService.log('DragDropManager', '⚠️ No se encontraron imágenes en el portapapeles', 'warn');
                UIHelperService.mostrarModalError('El portapapeles no contiene imágenes válidas. Por favor copia una imagen primero.');
            }
        }, true); // Usar captura para interceptar antes que otros listeners
        
        this.globalPasteListenerConfigurado = true;
        // UIHelperService.log('DragDropManager', '✅ Listener global de paste configurado');
    }

    /**
     * Inicializar drag & drop para prendas
     * @private
     */
    _inicializarPrendas() {
        const preview = document.getElementById('nueva-prenda-foto-preview');
        if (!preview) {
            UIHelperService.log('DragDropManager', 'Preview de prendas no encontrado', 'warn');
            return;
        }

        // Verificar si ya hay imágenes
        if (window.imagenesPrendaStorage && window.imagenesPrendaStorage.obtenerImagenes().length > 0) {
            const imagenes = window.imagenesPrendaStorage.obtenerImagenes();
            this.prendaHandler.configurarConImagenes(preview, imagenes);
            UIHelperService.log('DragDropManager', `Prendas configuradas con ${imagenes.length} imágenes existentes`);
        } else {
            this.prendaHandler.configurarSinImagenes(preview);
            UIHelperService.log('DragDropManager', 'Prendas configuradas sin imágenes');
        }
    }

    /**
     * Inicializar drag & drop para telas
     * @private
     */
    _inicializarTelas() {
        
        // Configurar drag & drop en el botón
        const dropZone = document.getElementById('nueva-prenda-tela-drop-zone');
        if (dropZone) {
            this.telaHandler.configurarDropZone(dropZone);
            UIHelperService.log('DragDropManager', '✅ Drop zone de telas configurada');
        } else {
            UIHelperService.log('DragDropManager', '❌ Drop zone de telas no encontrada', 'warn');
        }
        
        // Configurar drag & drop en el preview si ya hay imágenes
        const preview = document.getElementById('nueva-prenda-tela-preview');
        if (preview) {
            UIHelperService.log('DragDropManager', '✅ Preview de telas encontrado');
            // En el modal de prendas, el preview de telas está oculto por defecto
            // No mostrar warning ya que es comportamiento normal
            if (preview.style.display !== 'none') {
                this.telaHandler.configurarPreview(preview);
                UIHelperService.log('DragDropManager', '✅ Preview de telas configurado');
            } else {
                UIHelperService.log('DragDropManager', 'ℹ️ Preview de telas oculto (comportamiento normal en modal de prendas)');
            }
        } else {
            UIHelperService.log('DragDropManager', '❌ Preview de telas no encontrado', 'warn');
        }
        
        UIHelperService.log('DragDropManager', '✅ Sistema de telas inicializado');
    }

    /**
     * Inicializar drag & drop para procesos
     * @private
     */
    _inicializarProcesos() {
        this.procesoHandler.configurarTodos();
        // UIHelperService.log('DragDropManager', 'Procesos configurados automáticamente');
    }

    /**
     * Actualizar imágenes actuales de prendas
     * @param {Array} nuevasImagenes - Nueva lista de imágenes
     * @returns {DragDropManager} Instancia para encadenamiento
     */
    actualizarImagenesPrenda(nuevasImagenes) {
        if (this.prendaHandler) {
            const teníanImagenesAntes = (this.prendaHandler.imagenesActuales && this.prendaHandler.imagenesActuales.length > 0);
            const tienenImagenesAhora = (nuevasImagenes && nuevasImagenes.length > 0);
            
            // Actualizar la lista de imágenes
            this.prendaHandler.actualizarImagenesActuales(nuevasImagenes);
            UIHelperService.log('DragDropManager', `Imágenes de prenda actualizadas: ${nuevasImagenes.length}`);
            
            // Si pasamos de "sin imágenes" a "con imágenes" o viceversa, reconfigurar el handler
            // para cambiar el comportamiento del click handler
            if (teníanImagenesAntes !== tienenImagenesAhora) {
                UIHelperService.log('DragDropManager', `Estado de imágenes cambió (${teníanImagenesAntes} → ${tienenImagenesAhora}), reconfigurando handler...`);
                this.reconfigurarPrendas();
            }
        } else {
            UIHelperService.log('DragDropManager', 'Handler de prendas no disponible para actualizar imágenes', 'warn');
        }
        return this;
    }

    /**
     * Reconfigurar drag & drop para prendas
     * @returns {DragDropManager} Instancia para encadenamiento
     */
    reconfigurarPrendas() {
        // Destruir handler actual si existe
        if (this.prendaHandler) {
            this.prendaHandler.destruir();
        }
        this.prendaHandler = new PrendaDragDropHandler();
        
        // Reconfigurar
        this._inicializarPrendas();
        
        UIHelperService.log('DragDropManager', 'Drag & drop de prendas reconfigurado');
        return this;
    }

    /**
     * Reconfigurar drag & drop para telas
     * @returns {DragDropManager} Instancia para encadenamiento
     */
    reconfigurarTelas() {
        // Destruir handler actual si existe
        if (this.telaHandler) {
            this.telaHandler.destruir();
        }
        this.telaHandler = new TelaDragDropHandler();
        
        // Reconfigurar
        this._inicializarTelas();
        
        UIHelperService.log('DragDropManager', 'Drag & drop de telas reconfigurado');
        return this;
    }

    /**
     * Reconfigurar drag & drop para procesos
     * @returns {DragDropManager} Instancia para encadenamiento
     */
    reconfigurarProcesos() {
        // Destruir handler actual si existe
        if (this.procesoHandler) {
            this.procesoHandler.destruir();
        }
        this.procesoHandler = new ProcesoDragDropHandler();
        
        // Reconfigurar
        this._inicializarProcesos();
        
        // UIHelperService.log('DragDropManager', 'Drag & drop de procesos reconfigurado');
        return this;
    }

    /**
     * Obtener estado completo del sistema
     * @returns {Object} Estado completo
     */
    getEstadoCompleto() {
        return {
            inicializado: this.inicializado,
            globalPasteListenerConfigurado: this.globalPasteListenerConfigurado,
            prendas: this.prendaHandler ? this.prendaHandler.getEstado() : { configurado: false, error: 'Handler no inicializado' },
            telas: this.telaHandler ? this.telaHandler.getEstado() : { dropZoneConfigurada: false, previewConfigurado: false, error: 'Handler no inicializado' },
            procesos: this.procesoHandler ? this.procesoHandler.getEstado() : { procesosConfigurados: 0, error: 'Handler no inicializado' },
            servicios: {
                uiHelper: UIHelperService ? 'disponible' : 'no disponible',
                contextMenu: ContextMenuService && typeof ContextMenuService.getEstado === 'function' ? ContextMenuService.getEstado() : 'no disponible',
                clipboard: ClipboardService && typeof ClipboardService.getEstado === 'function' ? ClipboardService.getEstado() : 'no disponible'
            }
        };
    }

    /**
     * Desactivar todo el sistema temporalmente
     * @returns {DragDropManager} Instancia para encadenamiento
     */
    desactivar() {
        if (this.prendaHandler) this.prendaHandler.desactivar();
        if (this.telaHandler) this.telaHandler.desactivar();
        if (this.procesoHandler) this.procesoHandler.desactivar();
        
        // UIHelperService.log('DragDropManager', 'Sistema de drag & drop desactivado');
        return this;
    }

    /**
     * Reactivar el sistema
     * @returns {DragDropManager} Instancia para encadenamiento
     */
    reactivar() {
        if (!this.inicializado) {
            this.inicializar();
        } else {
            // Reconfigurar todos los componentes
            this.reconfigurarPrendas();
            this.reconfigurarTelas();
            this.reconfigurarProcesos();
        }
        
        // UIHelperService.log('DragDropManager', 'Sistema de drag & drop reactivado');
        return this;
    }

    /**
     * Destruir todo el sistema y limpiar recursos
     */
    destruir() {
        if (this.prendaHandler) this.prendaHandler.destruir();
        if (this.telaHandler) this.telaHandler.destruir();
        if (this.procesoHandler) this.procesoHandler.destruir();
        
        // Limpiar listener global si es necesario
        if (this.globalPasteListenerConfigurado) {
            // No podemos remover listeners específicos sin referencia, 
            // pero podemos marcar como no configurado
            this.globalPasteListenerConfigurado = false;
        }
        
        this.inicializado = false;
        // UIHelperService.log('DragDropManager', 'Sistema de drag & drop destruido completamente');
    }

    /**
     * Obtener información de debugging
     * @returns {Object} Información de debugging
     */
    getDebugInfo() {
        return {
            estado: this.getEstadoCompleto(),
            elementosDOM: {
                previewPrenda: !!document.getElementById('nueva-prenda-foto-preview'),
                modalPrenda: !!document.getElementById('modal-agregar-prenda-nueva'),
                dropZoneTela: !!document.getElementById('nueva-prenda-tela-drop-zone'),
                previewTela: !!document.getElementById('nueva-prenda-tela-preview'),
                previewsProcesos: [
                    !!document.getElementById('proceso-foto-preview-1'),
                    !!document.getElementById('proceso-foto-preview-2'),
                    !!document.getElementById('proceso-foto-preview-3')
                ]
            },
            funcionesGlobales: {
                manejarImagenesPrenda: typeof window.manejarImagenesPrenda === 'function',
                manejarImagenTela: typeof window.manejarImagenTela === 'function',
                manejarImagenProceso: typeof window.manejarImagenProceso === 'function',
                imagenesPrendaStorage: !!window.imagenesPrendaStorage
            }
        };
    }

    /**
     * Ejecutar comandos de debugging
     * @param {string} comando - Comando a ejecutar
     */
    ejecutarDebug(comando) {
        switch (comando) {
            case 'estado':
                console.log('=== ESTADO DEL DRAG & DROP MANAGER ===');
                console.log(this.getEstadoCompleto());
                break;
                
            case 'debug':
                console.log('=== INFORMACIÓN DE DEBUGGING ===');
                console.log(this.getDebugInfo());
                break;
                
            case 'contextos':
                ProcesoDragDropHandler.debugContextMenu();
                break;
                
            case 'rightclick':
                ProcesoDragDropHandler.testRightClick(1);
                break;
                
            default:
                console.log(`Comando desconocido: ${comando}`);
                console.log('Comandos disponibles: estado, debug, contextos, rightclick');
        }
    }
}

// Crear instancia global
window.DragDropManager = new DragDropManager();

// Funciones de compatibilidad con el sistema antiguo
window.setupGlobalPasteListener = () => {
    window.DragDropManager._configurarListenerGlobalPaste();
};

window.setupDragAndDrop = (previewElement) => {
    return window.DragDropManager.prendaHandler.configurarSinImagenes(previewElement);
};

window.setupDragAndDropConImagen = (previewElement, imagenesActuales) => {
    return window.DragDropManager.prendaHandler.configurarConImagenes(previewElement, imagenesActuales);
};

window.setupDragDropTela = (dropZone) => {
    return window.DragDropManager.telaHandler.configurarDropZone(dropZone);
};

window.setupDragDropTelaPreview = (previewElement) => {
    return window.DragDropManager.telaHandler.configurarPreview(previewElement);
};

window.setupDragDropProceso = (previewElement, procesoNumero) => {
    return window.DragDropManager.procesoHandler.configurarProceso(previewElement, procesoNumero);
};

window.inicializarDragDropPrenda = () => {
    window.DragDropManager._inicializarPrendas();
};

window.inicializarDragDropTela = () => {
    window.DragDropManager._inicializarTelas();
};

window.inicializarDragDropProcesos = () => {
    window.DragDropManager.procesoHandler.configurarTodos();
};

// Funciones de debugging globales
window.debugContextMenu = () => {
    ProcesoDragDropHandler.debugContextMenu();
};

window.testRightClick = () => {
    ProcesoDragDropHandler.testRightClick(1);
};

// Inicialización automática cuando el DOM esté listo
const inicializarConRetraso = () => {
    // Esperar un poco para que todos los scripts carguen
    setTimeout(() => {
        if (window.DragDropManager) {
            // Verificar que las dependencias estén disponibles
            const dependencias = {
                UIHelperService: typeof window.UIHelperService !== 'undefined',
                ContextMenuService: typeof window.ContextMenuService !== 'undefined',
                ClipboardService: typeof window.ClipboardService !== 'undefined',
                PrendaDragDropHandler: typeof window.PrendaDragDropHandler !== 'undefined'
            };
            
            const todasDisponibles = Object.values(dependencias).every(v => v);
            
            if (todasDisponibles) {
                // console.log('[DragDropManager] ✅ Todas las dependencias disponibles, inicializando...');
                window.DragDropManager.inicializar();
            } else {
                // console.log('[DragDropManager] ⚠️ Dependencias faltantes, reintentando...', dependencias);
                // Reintentar después de un tiempo
                setTimeout(inicializarConRetraso, 500);
            }
        } else {
            // console.log('[DragDropManager] ⚠️ DragDropManager no disponible, reintentando...');
            setTimeout(inicializarConRetraso, 500);
        }
    }, 100);
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', inicializarConRetraso);
} else {
    // El DOM ya está cargado
    inicializarConRetraso();
}

// Exportar para uso en módulos
if (typeof module !== 'undefined' && module.exports) {
    module.exports = DragDropManager;
}
