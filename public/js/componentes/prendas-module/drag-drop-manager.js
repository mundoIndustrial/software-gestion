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

        /**
         * Registro de sub-modales que deben interceptar paste antes del handler principal.
         * Cada entrada: { modalId: string, handler: (file: File) => void }
         * Los sub-modales registrados tienen prioridad sobre el flujo normal
         * cuando están visibles. Esto permite que modales secundarios (ej: "Agregar Tela")
         * capturen Ctrl+V sin conflictos con el modal padre.
         * @type {Map<string, function(File): void>}
         */
        this._subModalHandlers = new Map();
    }

    /**
     * Registrar un sub-modal para que intercepte paste de imágenes cuando esté visible.
     * @param {string} modalId - ID del elemento DOM del modal
     * @param {function(File): void} handler - Función que recibe el File de la imagen
     * @returns {DragDropManager} Para encadenamiento
     */
    registrarSubModal(modalId, handler) {
        if (typeof handler !== 'function') {
            console.error(`[DragDropManager] registrarSubModal: handler debe ser una función (modal: ${modalId})`);
            return this;
        }
        this._subModalHandlers.set(modalId, handler);
        UIHelperService.log('DragDropManager', ` Sub-modal registrado: #${modalId}`);
        return this;
    }

    /**
     * Desregistrar un sub-modal.
     * @param {string} modalId - ID del modal a desregistrar
     * @returns {DragDropManager}
     */
    desregistrarSubModal(modalId) {
        this._subModalHandlers.delete(modalId);
        return this;
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
            UIHelperService.log('DragDropManager', ' Ya inicializado, ignorando llamada duplicada', 'info');
            return this;  // ← Retorna AQUÍ, no continúa con el código abajo
        }

        console.log('[DragDropManager] Iniciando inicialización del sistema drag & drop...');

        // Verificar dependencias antes de crear instancias
        if (!globalThis.PrendaDragDropHandler || !globalThis.TelaDragDropHandler || !globalThis.ProcesoDragDropHandler) {
            UIHelperService.log('DragDropManager', ' Dependencias no disponibles:', 'error');
            UIHelperService.log('DragDropManager', `- PrendaDragDropHandler: ${!!globalThis.PrendaDragDropHandler}`);
            UIHelperService.log('DragDropManager', `- TelaDragDropHandler: ${!!globalThis.TelaDragDropHandler}`);
            UIHelperService.log('DragDropManager', `- ProcesoDragDropHandler: ${!!globalThis.ProcesoDragDropHandler}`);
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

        //  MARCAR como inicializado SOLO al final
        this.inicializado = true;
        
        console.log('[DragDropManager]  Sistema de drag & drop inicializado correctamente');
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
            if (e.__prendasPasteHandled === true) {
                UIHelperService.log('DragDropManager', 'Paste ya procesado previamente, ignorando listener duplicado');
                return;
            }

            UIHelperService.log('DragDropManager', ' EVENTO PASTE DETECTADO');
            
            // ── PASO 0: Sub-modales registrados tienen prioridad ──
            // Verificar ANTES del check de campo de texto para que Ctrl+V funcione
            // aunque el usuario tenga focus en un input de texto del sub-modal
            for (const [modalId, handler] of this._subModalHandlers) {
                const modalEl = document.getElementById(modalId);
                if (modalEl && UIHelperService.isModalVisible(modalEl)) {
                    const items = e.clipboardData?.items;
                    if (items) {
                        for (let i = 0; i < items.length; i++) {
                            if (items[i].kind === 'file' && items[i].type.startsWith('image/')) {
                                e.__prendasPasteHandled = true;
                                e.preventDefault();
                                e.stopPropagation();
                                const file = items[i].getAsFile();
                                if (file) {
                                    UIHelperService.log('DragDropManager', ` Paste redirigido a sub-modal #${modalId}`);
                                    handler(file);
                                }
                                return;
                            }
                        }
                    }
                    // Sin imagen → permitir pegado normal de texto dentro del sub-modal
                    return;
                }
            }

            // ── PASO 1: Verificar si el elemento activo es un campo de texto ──
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

            //  FIX: ANTES de permitir pegado normal en campos de texto,
            // verificar si estamos dentro del wizard de colores y hay una imagen en el clipboard.
            // En ese caso, redirigir la imagen al dropZone de la fila, no pegarla como texto.
            const wizardColoresContainer = document.getElementById('lista-colores-checkboxes');
            
            // Verificar por activeElement O por posición del cursor (mouse)
            let elementoBajoCursorWizard = null;
            if (this.mousePosition && this.mousePosition.x && this.mousePosition.y) {
                elementoBajoCursorWizard = document.elementFromPoint(this.mousePosition.x, this.mousePosition.y);
            }
            
            const activoEnWizard = elementoActivo && wizardColoresContainer && (
                wizardColoresContainer.contains(elementoActivo) ||
                elementoActivo === wizardColoresContainer
            );
            const cursorEnWizard = elementoBajoCursorWizard && wizardColoresContainer && (
                wizardColoresContainer.contains(elementoBajoCursorWizard) ||
                elementoBajoCursorWizard === wizardColoresContainer
            );
            
            const dentroDelWizardColores = wizardColoresContainer && 
                wizardColoresContainer.offsetParent !== null &&
                (activoEnWizard || cursorEnWizard);
            
            // Determinar cuál elemento usar para buscar la fila (preferir activo, luego cursor)
            const elementoReferencia = activoEnWizard ? elementoActivo : elementoBajoCursorWizard;

            if (dentroDelWizardColores) {
                UIHelperService.log('DragDropManager', `🎨 Wizard colores detectado - activo: ${activoEnWizard}, cursor: ${cursorEnWizard}`);
                
                // Verificar si el clipboard tiene una imagen
                const clipItems = e.clipboardData?.items;
                let imagenEnClipboard = false;
                let archivoImagen = null;
                if (clipItems) {
                    for (let i = 0; i < clipItems.length; i++) {
                        if (clipItems[i].kind === 'file' && clipItems[i].type.startsWith('image/')) {
                            imagenEnClipboard = true;
                            archivoImagen = clipItems[i].getAsFile();
                            break;
                        }
                    }
                }

                if (imagenEnClipboard && archivoImagen) {
                    e.preventDefault();
                    e.stopPropagation();

                    // Encontrar el wrapper más cercano al elemento de referencia
                    // Subir por la fila (grid row) que contiene tanto el input como el wrapper de imagen
                    let filaActual = elementoReferencia ? elementoReferencia.closest('[class^="fila-color"]') : null;
                    if (!filaActual && elementoReferencia && elementoReferencia.parentElement) {
                        // Fallback: buscar en el contenedor padre del grid row
                        filaActual = elementoReferencia.closest('[class^="contenedor-colores"]');
                        if (filaActual) {
                            // Tomar la última fila dentro del contenedor
                            const filas = filaActual.querySelectorAll('[class^="fila-color"]');
                            filaActual = filas.length > 0 ? filas[filas.length - 1] : filaActual;
                        }
                    }
                    
                    // También intentar buscar si el cursor está directamente sobre un .imagen-tela-wrapper
                    if (!filaActual && elementoBajoCursorWizard) {
                        const wrapperDirecto = elementoBajoCursorWizard.closest('.imagen-tela-wrapper');
                        if (wrapperDirecto) {
                            filaActual = wrapperDirecto.closest('[class^="fila-color"]');
                        }
                    }

                    let targetWrapper = null;
                    if (filaActual) {
                        targetWrapper = filaActual.querySelector('.imagen-tela-wrapper');
                    }

                    // Fallback: usar el primer wrapper sin imagen, o el último
                    if (!targetWrapper) {
                        const allWrappers = wizardColoresContainer.querySelectorAll('.imagen-tela-wrapper');
                        for (const w of allWrappers) {
                            const preview = w.querySelector('div[style*="display: none"]') || 
                                           w.querySelector('div:last-child');
                            // Si el preview está oculto, es un wrapper sin imagen
                            if (preview && preview.style.display === 'none') {
                                targetWrapper = w;
                                break;
                            }
                        }
                        // Si todos tienen imagen, usar el último
                        if (!targetWrapper && allWrappers.length > 0) {
                            targetWrapper = allWrappers[allWrappers.length - 1];
                        }
                    }

                    if (targetWrapper) {
                        const fileInput = targetWrapper.querySelector('.imagen-tela-wizard');
                        if (fileInput) {
                            const dt = new DataTransfer();
                            dt.items.add(archivoImagen);
                            fileInput.files = dt.files;
                            fileInput.dispatchEvent(new Event('change', { bubbles: true }));
                            UIHelperService.log('DragDropManager', '🎨 Imagen pegada redirigida a dropZone del wizard colores-por-talla');
                        }
                    }
                    return;
                }
                // Si no hay imagen en clipboard, dejar que el texto se pegue normalmente
                return;
            }

            // Si es un campo de texto (fuera del wizard colores), permitir el comportamiento normal
            if (esCampoTexto) {
                UIHelperService.log('DragDropManager', ` Campo de texto detectado (${elementoActivo.tagName}${elementoActivo.type ? ':' + elementoActivo.type : ''}), permitiendo pegado normal`);
                return;
            }

            //  CRÍTICO: Soportar AMBOS modales (creación nueva Y edición) Y EPP
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
                UIHelperService.log('DragDropManager', ` Modal EPP evaluado: visible=${esVisible}`);
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
                UIHelperService.log('DragDropManager', ` Modal prenda-creacion evaluado: visible=${esVisible}`);
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
                UIHelperService.log('DragDropManager', ` Modal prenda-edicion evaluado: visible=${esVisible}`);
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
                    UIHelperService.log('DragDropManager', ` Modal proceso-generico evaluado: visible=${esVisible}`);
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
                    UIHelperService.log('DragDropManager', ` Modal prenda-generico evaluado: visible=${esVisible}`);
                }
            }
            
            // Filtrar solo modales visibles y ordenar por prioridad
            let modalesVisibles = modalesCandidatos.filter(m => m.visible).sort((a, b) => a.prioridad - b.prioridad);
            
            UIHelperService.log('DragDropManager', ` Modales candidatos: ${modalesCandidatos.length}, visibles: ${modalesVisibles.length}`);
            
            // Seleccionar el modal visible con mayor prioridad
            if (modalesVisibles.length > 0) {
                let seleccionado = modalesVisibles[0];
                preview = seleccionado.preview;
                modal = seleccionado.modal;
                UIHelperService.log('DragDropManager', ` Modal seleccionado: ${seleccionado.tipo} (prioridad: ${seleccionado.prioridad})`);
            } else {
                // Si no hay modales visibles, mostrar advertencia y no procesar
                UIHelperService.log('DragDropManager', ` No hay modales visibles. Modales evaluados:`, modalesCandidatos.map(m => `${m.tipo}:${m.visible}`));
                return;
            }
            
            UIHelperService.log('DragDropManager', `Preview encontrado: ${!!preview}, Modal encontrado: ${!!modal}`);
            
            // Validar que el modal y preview existan (ya verificamos visibilidad antes)
            if (!modal || !preview) {
                UIHelperService.log('DragDropManager', 'Modal o preview no existen');
                return;
            }
            
            UIHelperService.log('DragDropManager', ' Procesando pegado global...');
            
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
                UIHelperService.log('DragDropManager', ' No hay imágenes en el portapapeles, permitiendo pegado normal de texto');
                return;
            }
            
            // Si hay imágenes, interceptar el evento para procesarlas
            e.__prendasPasteHandled = true;
            e.preventDefault();
            e.stopPropagation();
            
            // Buscar y procesar imágenes
            for (let i = 0; i < items.length; i++) {
                const item = items[i];
                
                // Verificar si es una imagen
                if (item.kind === 'file' && item.type.startsWith('image/')) {
                    UIHelperService.log('DragDropManager', ` Imagen encontrada: ${item.type}`);
                    
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
                        
                        UIHelperService.log('DragDropManager', ` Elemento activo: ${elementoActivo?.id || elementoActivo?.tagName || 'desconocido'}`);
                        
                        // Intentar obtener el elemento bajo el cursor (usar posición rastreada del mouse)
                        try {
                            // Usar la posición rastreada del mouse
                            const clientX = this.mousePosition.x;
                            const clientY = this.mousePosition.y;
                            
                            if (clientX && clientY && isFinite(clientX) && isFinite(clientY)) {
                                elementoCursor = document.elementFromPoint(clientX, clientY);
                                UIHelperService.log('DragDropManager', ` Elemento bajo cursor: ${elementoCursor?.id || elementoCursor?.tagName || 'desconocido'} (${clientX}, ${clientY})`);
                            } else {
                                UIHelperService.log('DragDropManager', ` Posición del mouse no disponible, usando solo elemento activo`);
                            }
                        } catch (error) {
                            UIHelperService.log('DragDropManager', ` Error al obtener elemento bajo cursor: ${error.message}`);
                        }
                        
                        // 🔑 NUEVA LÓGICA: Usar el modal ya seleccionado para determinar el área
                        // Ya verificamos qué modal está visible, ahora usamos esa información
                        let modalSeleccionado = modalesVisibles[0];
                        
                        if (modalSeleccionado) {
                            UIHelperService.log('DragDropManager', ` Procesando para modal: ${modalSeleccionado.tipo}`);
                            
                            // Según el tipo de modal, determinar el handler
                            switch (modalSeleccionado.tipo) {
                                case 'EPP':
                                    // Verificar si hay una zona de fotos activa en la tabla de EPPs agregados
                                    if (globalThis.zonaFotosActivaId && globalThis.eppAgregadosList && globalThis.eppAgregadosList.length > 0) {
                                        const eppIdTabla = parseInt(globalThis.zonaFotosActivaId.replace('fotoZona_', ''));
                                        const eppEnTabla = globalThis.eppAgregadosList.find(ep => ep.id == eppIdTabla);
                                        if (eppEnTabla) {
                                            handlerCorrecto = 'EPP-tabla';
                                            funcionManejo = (_input) => {
                                                if (!eppEnTabla.imagenes) eppEnTabla.imagenes = [];
                                                const blobUrl = URL.createObjectURL(file);
                                                eppEnTabla.imagenes.push({
                                                    file: file,
                                                    previewUrl: blobUrl,
                                                    nombre: file.name || 'pegado_' + Date.now() + '.png'
                                                });
                                                if (typeof globalThis.renderizarTablaEPPAgregados === 'function') {
                                                    globalThis.renderizarTablaEPPAgregados();
                                                }
                                            };
                                            UIHelperService.log('DragDropManager', ` Usando handler para EPP tabla (zona activa: ${globalThis.zonaFotosActivaId}, eppId: ${eppIdTabla})`);
                                            break;
                                        }
                                    }
                                    handlerCorrecto = 'EPP';
                                    funcionManejo = globalThis.manejarSubidaFotosEPP;
                                    UIHelperService.log('DragDropManager', ' Usando handler para EPP');
                                    break;
                                    
                                case 'proceso-generico':
                                    // Para procesos, necesitamos determinar qué número de proceso
                                    let numeroProceso = this._determinarNumeroProceso(elementoActivo, elementoCursor);
                                    if (numeroProceso) {
                                        handlerCorrecto = `proceso-${numeroProceso}`;
                                        funcionManejo = (input) => (globalThis.ProcesoModalController?.imagenes?.manejar || globalThis.manejarImagenProceso)?.(input, numeroProceso);
                                        UIHelperService.log('DragDropManager', ` Usando handler para proceso ${numeroProceso}`);
                                    }
                                    break;
                                    
                                case 'prenda-creacion':
                                case 'prenda-edicion':
                                case 'prenda-generico':
                                    // Para prendas, verificar si es área de telas o de fotos de prenda
                                    if (this._estaEnAreaTelas(elementoActivo, elementoCursor)) {
                                        handlerCorrecto = 'telas';
                                        funcionManejo = globalThis.manejarImagenTela;
                                        UIHelperService.log('DragDropManager', ' Usando handler para telas (prendas)');
                                    } else {
                                        handlerCorrecto = 'prendas';
                                        funcionManejo = globalThis.manejarImagenesPrenda;
                                        UIHelperService.log('DragDropManager', ' Usando handler para fotos de prenda');
                                    }
                                    break;
                            }
                        }
                        
                        // Si no se detectó handler específico, usar fallback según el modal
                        if (!funcionManejo && modalSeleccionado) {
                            UIHelperService.log('DragDropManager', ' No se detectó área específica, usando fallback del modal');
                            
                            // Fallback según el tipo de modal
                            switch (modalSeleccionado.tipo) {
                                case 'EPP':
                                    // Fallback: también verificar zona activa de tabla
                                    if (globalThis.zonaFotosActivaId && globalThis.eppAgregadosList && globalThis.eppAgregadosList.length > 0) {
                                        const eppIdFb = Number(globalThis.zonaFotosActivaId.replace('fotoZona_', ''));
                                        const eppFb = globalThis.eppAgregadosList.find(ep => ep.id == eppIdFb);
                                        if (eppFb) {
                                            handlerCorrecto = 'EPP-tabla (fallback)';
                                            funcionManejo = (_input) => {
                                                if (!eppFb.imagenes) eppFb.imagenes = [];
                                                const blobUrl = URL.createObjectURL(file);
                                                eppFb.imagenes.push({
                                                    file: file,
                                                    previewUrl: blobUrl,
                                                    nombre: file.name || 'pegado_' + Date.now() + '.png'
                                                });
                                                if (typeof globalThis.renderizarTablaEPPAgregados === 'function') {
                                                    globalThis.renderizarTablaEPPAgregados();
                                                }
                                            };
                                            break;
                                        }
                                    }
                                    handlerCorrecto = 'EPP (fallback)';
                                    funcionManejo = globalThis.manejarSubidaFotosEPP;
                                    break;
                                case 'proceso-generico':
                                    handlerCorrecto = 'proceso-1 (fallback)';
                                    funcionManejo = (input) => (globalThis.ProcesoModalController?.imagenes?.manejar || globalThis.manejarImagenProceso)?.(input, 1);
                                    break;
                                default:
                                    handlerCorrecto = 'prendas (fallback)';
                                    funcionManejo = globalThis.manejarImagenesPrenda;
                                    break;
                            }
                        }
                        
                        // Usar la función de manejo correcta
                        if (typeof funcionManejo === 'function') {
                            UIHelperService.log('DragDropManager', ` Llamando a manejarImagen${handlerCorrecto}...`);
                            funcionManejo(tempInput);
                            UIHelperService.log('DragDropManager', ` Imagen procesada exitosamente en ${handlerCorrecto}`);
                        } else {
                            UIHelperService.log('DragDropManager', ` manejarImagen${handlerCorrecto} no disponible`, 'error');
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
                UIHelperService.log('DragDropManager', ' No se encontraron imágenes procesables en el portapapeles', 'warn');
                UIHelperService.mostrarModalError('El portapapeles no contiene imágenes válidas. Por favor copia una imagen primero.');
            }
        }, true); // Usar captura para interceptar antes que otros listeners
        
        this.globalPasteListenerConfigurado = true;
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
        if (globalThis.imagenesPrendaStorage && globalThis.imagenesPrendaStorage.obtenerImagenes().length > 0) {
            const imagenes = globalThis.imagenesPrendaStorage.obtenerImagenes();
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
        // La zona de drop de telas ya no existe en el flujo unificado
        // (se maneja dentro del wizard o del formulario simple)
        const dropZone = document.getElementById('nueva-prenda-tela-drop-zone');
        if (dropZone) {
            this.telaHandler.configurarDropZone(dropZone);
            UIHelperService.log('DragDropManager', ' Drop zone de telas configurada');
        }
        
        const preview = document.getElementById('nueva-prenda-tela-preview');
        if (preview && preview.style.display !== 'none') {
            this.telaHandler.configurarPreview(preview);
            UIHelperService.log('DragDropManager', ' Preview de telas configurado');
        }
        
        UIHelperService.log('DragDropManager', ' Sistema de telas inicializado');
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
                manejarImagenesPrenda: typeof globalThis.manejarImagenesPrenda === 'function',
                manejarImagenTela: typeof globalThis.manejarImagenTela === 'function',
                manejarImagenProceso: typeof globalThis.manejarImagenProceso === 'function',
                imagenesPrendaStorage: !!globalThis.imagenesPrendaStorage
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
        
        // Si no está sobre un preview específico, usar el más cercano (requiere posición del mouse)
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

        // No se pudo determinar proceso (ni elemento activo ni posición del mouse disponibles)
        UIHelperService.log('DragDropManager', ' No se pudo determinar número de proceso - falta información de posición o elementos', 'warn');
        return null;
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
globalThis.DragDropManager = new DragDropManager();

// Funciones de compatibilidad con el sistema antiguo
globalThis.setupGlobalPasteListener = () => {
    globalThis.DragDropManager._configurarListenerGlobalPaste();
};

globalThis.setupDragAndDrop = (previewElement) => {
    //  CRÍTICO: Asegurar que DragDropManager esté inicializado
    if (!globalThis.DragDropManager || !globalThis.DragDropManager.inicializado) {
        console.warn('[setupDragAndDrop]  DragDropManager no inicializado, inicializando...');
        if (!globalThis.DragDropManager) {
            globalThis.DragDropManager = new DragDropManager();
        }
        globalThis.DragDropManager.inicializar();
    }
    
    if (!globalThis.DragDropManager.prendaHandler) {
        console.error('[setupDragAndDrop]  prendaHandler no disponible');
        return;
    }
    
    return globalThis.DragDropManager.prendaHandler.configurarSinImagenes(previewElement);
};

globalThis.setupDragAndDropConImagen = (previewElement, imagenesActuales) => {
    //  CRÍTICO: Asegurar que DragDropManager esté inicializado
    if (!globalThis.DragDropManager || !globalThis.DragDropManager.inicializado) {
        console.warn('[setupDragAndDropConImagen]  DragDropManager no inicializado, inicializando...');
        if (!globalThis.DragDropManager) {
            globalThis.DragDropManager = new DragDropManager();
        }
        globalThis.DragDropManager.inicializar();
    }
    
    if (!globalThis.DragDropManager.prendaHandler) {
        console.error('[setupDragAndDropConImagen]  prendaHandler no disponible');
        return;
    }
    
    return globalThis.DragDropManager.prendaHandler.configurarConImagenes(previewElement, imagenesActuales);
};

globalThis.setupDragDropTela = (dropZone) => {
    return globalThis.DragDropManager.telaHandler.configurarDropZone(dropZone);
};

globalThis.setupDragDropTelaPreview = (previewElement) => {
    return globalThis.DragDropManager.telaHandler.configurarPreview(previewElement);
};

globalThis.setupDragDropProceso = (previewElement, procesoNumero) => {
    return globalThis.DragDropManager.procesoHandler.configurarProceso(previewElement, procesoNumero);
};

globalThis.inicializarDragDropPrenda = () => {
    globalThis.DragDropManager._inicializarPrendas();
};

globalThis.inicializarDragDropTela = () => {
    globalThis.DragDropManager._inicializarTelas();
};

globalThis.inicializarDragDropProcesos = () => {
    globalThis.DragDropManager.procesoHandler.configurarTodos();
};

// Funciones de debugging globales
globalThis.debugContextMenu = () => {
    ProcesoDragDropHandler.debugContextMenu();
};

globalThis.testRightClick = () => {
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
