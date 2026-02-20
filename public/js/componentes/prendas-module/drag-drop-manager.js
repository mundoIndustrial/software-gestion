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
     * 
     * FASE 1: Guard clause reforzado
     * - Una sola inicialización garantizada
     * - Múltiples llamadas son rechazadas silenciosamente
     * 
     * @returns {DragDropManager} Instancia para encadenamiento
     */
    inicializar() {
        // Guard clause real: SI ya inicializado, SALIR completamente
        if (this.inicializado) {
            UIHelperService.log('DragDropManager', '✅ Ya inicializado, ignorando llamada duplicada', 'info');
            return this;  // ← Retorna AQUÍ, no continúa con el código abajo
        }

        console.log('[DragDropManager] Iniciando inicialización del sistema drag & drop...');

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

        // ✅ MARCAR como inicializado SOLO al final
        this.inicializado = true;
        
        console.log('[DragDropManager] ✅ Sistema de drag & drop inicializado correctamente');
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
            
            // 🔴 CRÍTICO: Verificar si el elemento activo es un campo de texto que debe permitir pegar texto
            const elementoActivo = document.activeElement;
            const esCampoTexto = elementoActivo && (
                elementoActivo.tagName === 'TEXTAREA' ||
                elementoActivo.tagName === 'INPUT' && (
                    elementoActivo.type === 'text' ||
                    elementoActivo.type === 'search' ||
                    elementoActivo.type === 'email' ||
                    elementoActivo.type === 'url' ||
                    elementoActivo.type === 'tel'
                ) ||
                elementoActivo.contentEditable === 'true'
            );
            
            // Si es un campo de texto, permitir el comportamiento normal del navegador
            if (esCampoTexto) {
                UIHelperService.log('DragDropManager', `📝 Campo de texto detectado (${elementoActivo.tagName}${elementoActivo.type ? ':' + elementoActivo.type : ''}), permitiendo pegado normal`);
                return; // No interceptar, dejar que el navegador maneje el pegado
            }
            
            // 🔴 CRÍTICO: Soportar AMBOS modales (creación nueva Y edición) Y EPP
            // Verificar TODOS los modales disponibles y sus visibilidad
            let contenedorEPP = document.getElementById('contenedorFotosEPP');
            let modalEPP = document.getElementById('modalAgregarEPP');
            let previewPrenda = document.getElementById('nueva-prenda-foto-preview');
            let modalPrenda = document.getElementById('modal-agregar-prenda-nueva');
            let modalEditarPrenda = document.getElementById('modal-editar-prenda');
            
            let preview = null;
            let modal = null;
            
            // Array de modales candidatos con su información
            let modalesCandidatos = [];
            
            // Agregar modal EPP si existe
            if (contenedorEPP && modalEPP) {
                const esVisible = UIHelperService.isModalVisible(modalEPP);
                modalesCandidatos.push({
                    tipo: 'EPP',
                    preview: contenedorEPP,
                    modal: modalEPP,
                    visible: esVisible,
                    prioridad: esVisible ? 1 : 999
                });
                UIHelperService.log('DragDropManager', `🔍 Modal EPP evaluado: visible=${esVisible}`);
            }
            
            // Agregar modal de creación de prenda si existe
            if (previewPrenda && modalPrenda) {
                const esVisible = UIHelperService.isModalVisible(modalPrenda);
                modalesCandidatos.push({
                    tipo: 'prenda-creacion',
                    preview: previewPrenda,
                    modal: modalPrenda,
                    visible: esVisible,
                    prioridad: esVisible ? 2 : 999
                });
                UIHelperService.log('DragDropManager', `🔍 Modal prenda-creacion evaluado: visible=${esVisible}`);
            }
            
            // Agregar modal de edición de prenda si existe
            if (previewPrenda && modalEditarPrenda) {
                const esVisible = UIHelperService.isModalVisible(modalEditarPrenda);
                modalesCandidatos.push({
                    tipo: 'prenda-edicion',
                    preview: previewPrenda,
                    modal: modalEditarPrenda,
                    visible: esVisible,
                    prioridad: esVisible ? 3 : 999
                });
                UIHelperService.log('DragDropManager', `🔍 Modal prenda-edicion evaluado: visible=${esVisible}`);
            }
            
            // Agregar modal de proceso genérico si existe
            let modalProceso = document.getElementById('modal-proceso-generico');
            if (modalProceso) {
                const esVisible = UIHelperService.isModalVisible(modalProceso);
                // Para procesos, usamos el primer preview como referencia
                let previewProceso1 = document.getElementById('proceso-foto-preview-1');
                if (previewProceso1) {
                    modalesCandidatos.push({
                        tipo: 'proceso-generico',
                        preview: previewProceso1,
                        modal: modalProceso,
                        visible: esVisible,
                        prioridad: esVisible ? 0 : 999  // Máxima prioridad para procesos si están visibles
                    });
                    UIHelperService.log('DragDropManager', `🔍 Modal proceso-generico evaluado: visible=${esVisible}`);
                }
            }
            
            // Buscar modal de prenda genérico si no hay ninguno de los anteriores
            if (modalesCandidatos.length === 0) {
                let modalGenerico = document.querySelector('[id*="modal"][id*="prenda"]');
                if (modalGenerico && previewPrenda) {
                    const esVisible = UIHelperService.isModalVisible(modalGenerico);
                    modalesCandidatos.push({
                        tipo: 'prenda-generico',
                        preview: previewPrenda,
                        modal: modalGenerico,
                        visible: esVisible,
                        prioridad: esVisible ? 4 : 999
                    });
                    UIHelperService.log('DragDropManager', `🔍 Modal prenda-generico evaluado: visible=${esVisible}`);
                }
            }
            
            // Filtrar solo modales visibles y ordenar por prioridad
            let modalesVisibles = modalesCandidatos.filter(m => m.visible).sort((a, b) => a.prioridad - b.prioridad);
            
            UIHelperService.log('DragDropManager', `📊 Modales candidatos: ${modalesCandidatos.length}, visibles: ${modalesVisibles.length}`);
            
            // Seleccionar el modal visible con mayor prioridad
            if (modalesVisibles.length > 0) {
                let seleccionado = modalesVisibles[0];
                preview = seleccionado.preview;
                modal = seleccionado.modal;
                UIHelperService.log('DragDropManager', `✅ Modal seleccionado: ${seleccionado.tipo} (prioridad: ${seleccionado.prioridad})`);
            } else {
                // Si no hay modales visibles, mostrar advertencia y no procesar
                UIHelperService.log('DragDropManager', `❌ No hay modales visibles. Modales evaluados:`, modalesCandidatos.map(m => `${m.tipo}:${m.visible}`));
                return;
            }
            
            UIHelperService.log('DragDropManager', `Preview encontrado: ${!!preview}, Modal encontrado: ${!!modal}`);
            
            // Validar que el modal y preview existan (ya verificamos visibilidad antes)
            if (!modal || !preview) {
                UIHelperService.log('DragDropManager', 'Modal o preview no existen');
                return;
            }
            
            UIHelperService.log('DragDropManager', '✅ Procesando pegado global...');
            
            // Obtener items del portapapeles
            const items = e.clipboardData.items;
            if (!items || items.length === 0) {
                UIHelperService.log('DragDropManager', 'No hay items en el portapapeles', 'warn');
                return;
            }
            
            UIHelperService.log('DragDropManager', `Items disponibles: ${items.length}`);
            
            // Primero verificar si hay imágenes en el portapapeles
            let foundImage = false;
            for (let i = 0; i < items.length; i++) {
                const item = items[i];
                UIHelperService.log('DragDropManager', `Item ${i}: ${item.type}, ${item.kind}`);
                
                // Verificar si es una imagen
                if (item.kind === 'file' && item.type.startsWith('image/')) {
                    foundImage = true;
                    break;
                }
            }
            
            // Si no hay imágenes, permitir el comportamiento normal
            if (!foundImage) {
                UIHelperService.log('DragDropManager', '📝 No hay imágenes en el portapapeles, permitiendo pegado normal de texto');
                return;
            }
            
            // Si hay imágenes, interceptar el evento para procesarlas
            e.preventDefault();
            e.stopPropagation();
            
            // Buscar y procesar imágenes
            for (let i = 0; i < items.length; i++) {
                const item = items[i];
                
                // Verificar si es una imagen
                if (item.kind === 'file' && item.type.startsWith('image/')) {
                    UIHelperService.log('DragDropManager', `✅ Imagen encontrada: ${item.type}`);
                    
                    // Obtener el archivo
                    const file = item.getAsFile();
                    if (file) {
                        UIHelperService.log('DragDropManager', `Archivo obtenido: ${file.name}, ${file.type}, ${file.size}`);
                        
                        // Crear un input file temporal
                        const tempInput = UIHelperService.crearInputTemporal([file]);
                        
                        // Determinar qué handler usar según el elemento activo y el cursor
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
                        
                        // 🔑 NUEVA LÓGICA: Usar el modal ya seleccionado para determinar el área
                        // Ya verificamos qué modal está visible, ahora usamos esa información
                        let modalSeleccionado = modalesVisibles[0];
                        
                        if (modalSeleccionado) {
                            UIHelperService.log('DragDropManager', `🎯 Procesando para modal: ${modalSeleccionado.tipo}`);
                            
                            // Según el tipo de modal, determinar el handler
                            switch (modalSeleccionado.tipo) {
                                case 'EPP':
                                    handlerCorrecto = 'EPP';
                                    funcionManejo = window.manejarSubidaFotosEPP;
                                    UIHelperService.log('DragDropManager', '🎯 Usando handler para EPP');
                                    break;
                                    
                                case 'proceso-generico':
                                    // Para procesos, necesitamos determinar qué número de proceso
                                    let numeroProceso = this._determinarNumeroProceso(elementoActivo, elementoCursor);
                                    if (numeroProceso) {
                                        handlerCorrecto = `proceso-${numeroProceso}`;
                                        funcionManejo = (input) => window.manejarImagenProceso(input, numeroProceso);
                                        UIHelperService.log('DragDropManager', `🎯 Usando handler para proceso ${numeroProceso}`);
                                    }
                                    break;
                                    
                                case 'prenda-creacion':
                                case 'prenda-edicion':
                                case 'prenda-generico':
                                    // Para prendas, verificar si es área de telas o de fotos de prenda
                                    if (this._estaEnAreaTelas(elementoActivo, elementoCursor)) {
                                        handlerCorrecto = 'telas';
                                        funcionManejo = window.manejarImagenTela;
                                        UIHelperService.log('DragDropManager', '🎯 Usando handler para telas (prendas)');
                                    } else {
                                        handlerCorrecto = 'prendas';
                                        funcionManejo = window.manejarImagenesPrenda;
                                        UIHelperService.log('DragDropManager', '🎯 Usando handler para fotos de prenda');
                                    }
                                    break;
                            }
                        }
                        
                        // Si no se detectó handler específico, usar fallback según el modal
                        if (!funcionManejo && modalSeleccionado) {
                            UIHelperService.log('DragDropManager', '⚠️ No se detectó área específica, usando fallback del modal');
                            
                            // Fallback según el tipo de modal
                            switch (modalSeleccionado.tipo) {
                                case 'EPP':
                                    handlerCorrecto = 'EPP (fallback)';
                                    funcionManejo = window.manejarSubidaFotosEPP;
                                    break;
                                case 'proceso-generico':
                                    handlerCorrecto = 'proceso-1 (fallback)';
                                    funcionManejo = (input) => window.manejarImagenProceso(input, 1);
                                    break;
                                default:
                                    handlerCorrecto = 'prendas (fallback)';
                                    funcionManejo = window.manejarImagenesPrenda;
                                    break;
                            }
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
            
            // Si no se encontraron imágenes (pero ya verificamos que sí había)
            if (!foundImage) {
                UIHelperService.log('DragDropManager', '⚠️ No se encontraron imágenes procesables en el portapapeles', 'warn');
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

    /**
     * Determinar el número de proceso según el elemento
     * @private
     */
    _determinarNumeroProceso(elementoActivo, elementoCursor) {
        const previewProceso1 = document.getElementById('proceso-foto-preview-1');
        const previewProceso2 = document.getElementById('proceso-foto-preview-2');
        const previewProceso3 = document.getElementById('proceso-foto-preview-3');
        
        let elementoAnalizado = elementoActivo;
        if (elementoCursor && (!elementoActivo?.id || !elementoActivo.id.includes('preview'))) {
            elementoAnalizado = elementoCursor;
        }
        
        // Verificar si está directamente sobre un preview específico
        if (previewProceso1 && (previewProceso1.contains(elementoAnalizado) || previewProceso1 === elementoAnalizado)) {
            return 1;
        } else if (previewProceso2 && (previewProceso2.contains(elementoAnalizado) || previewProceso2 === elementoAnalizado)) {
            return 2;
        } else if (previewProceso3 && (previewProceso3.contains(elementoAnalizado) || previewProceso3 === elementoAnalizado)) {
            return 3;
        }
        
        // Si no está sobre un preview específico, usar el más cercano
        if (previewProceso1 && previewProceso2 && previewProceso3 && this.mousePosition.x && this.mousePosition.y) {
            const rect1 = previewProceso1.getBoundingClientRect();
            const rect2 = previewProceso2.getBoundingClientRect();
            const rect3 = previewProceso3.getBoundingClientRect();
            
            const cursorX = this.mousePosition.x;
            const cursorY = this.mousePosition.y;
            
            const dist1 = Math.sqrt(Math.pow(cursorX - (rect1.left + rect1.width/2), 2) + Math.pow(cursorY - (rect1.top + rect1.height/2), 2));
            const dist2 = Math.sqrt(Math.pow(cursorX - (rect2.left + rect2.width/2), 2) + Math.pow(cursorY - (rect2.top + rect2.height/2), 2));
            const dist3 = Math.sqrt(Math.pow(cursorX - (rect3.left + rect3.width/2), 2) + Math.pow(cursorY - (rect3.top + rect3.height/2), 2));
            
            if (dist1 <= dist2 && dist1 <= dist3) return 1;
            if (dist2 <= dist1 && dist2 <= dist3) return 2;
            return 3;
        }
        
        // Fallback: usar proceso 1
        return 1;
    }

    /**
     * Verificar si está en el área de telas
     * @private
     */
    _estaEnAreaTelas(elementoActivo, elementoCursor) {
        const dropZoneTela = document.getElementById('nueva-prenda-tela-drop-zone');
        const previewTela = document.getElementById('nueva-prenda-tela-preview');
        
        let elementoAnalizado = elementoActivo;
        if (elementoCursor && (!elementoActivo?.id || (!elementoActivo.id.includes('tela') && !elementoActivo.id.includes('drop')))) {
            elementoAnalizado = elementoCursor;
        }
        
        if (!elementoAnalizado) return false;
        
        // Verificar si está en el área de telas
        return (dropZoneTela && (dropZoneTela.contains(elementoAnalizado) || dropZoneTela === elementoAnalizado)) ||
               (previewTela && (previewTela.contains(elementoAnalizado) || previewTela === elementoAnalizado)) ||
               (elementoAnalizado.closest && elementoAnalizado.closest('[data-zona="tela"]'));
    }
}

// Crear instancia global
window.DragDropManager = new DragDropManager();

// Funciones de compatibilidad con el sistema antiguo
window.setupGlobalPasteListener = () => {
    window.DragDropManager._configurarListenerGlobalPaste();
};

window.setupDragAndDrop = (previewElement) => {
    // 🔴 CRÍTICO: Asegurar que DragDropManager esté inicializado
    if (!window.DragDropManager || !window.DragDropManager.inicializado) {
        console.warn('[setupDragAndDrop] ⚠️ DragDropManager no inicializado, inicializando...');
        if (!window.DragDropManager) {
            window.DragDropManager = new DragDropManager();
        }
        window.DragDropManager.inicializar();
    }
    
    if (!window.DragDropManager.prendaHandler) {
        console.error('[setupDragAndDrop] ❌ prendaHandler no disponible');
        return;
    }
    
    return window.DragDropManager.prendaHandler.configurarSinImagenes(previewElement);
};

window.setupDragAndDropConImagen = (previewElement, imagenesActuales) => {
    // 🔴 CRÍTICO: Asegurar que DragDropManager esté inicializado
    if (!window.DragDropManager || !window.DragDropManager.inicializado) {
        console.warn('[setupDragAndDropConImagen] ⚠️ DragDropManager no inicializado, inicializando...');
        if (!window.DragDropManager) {
            window.DragDropManager = new DragDropManager();
        }
        window.DragDropManager.inicializar();
    }
    
    if (!window.DragDropManager.prendaHandler) {
        console.error('[setupDragAndDropConImagen] ❌ prendaHandler no disponible');
        return;
    }
    
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

// ============================================================
// AUTO-INICIALIZACIÓN ELIMINADA
// ============================================================
// DragDropManager.inicializar() ahora se ejecuta EXCLUSIVAMENTE
// desde el listener 'shown.bs.modal' { once: true } registrado
// en GestionItemsUI.abrirModalAgregarPrendaNueva().
//
// Esto garantiza:
// - Init SOLO cuando el modal es visible
// - Una sola ejecución por apertura
// - Flujo determinístico: FSM OPENING → shown → DragDrop → FSM OPEN
// ============================================================

// Exportar para uso en módulos
if (typeof module !== 'undefined' && module.exports) {
    module.exports = DragDropManager;
}
