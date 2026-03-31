/**
 * Módulo: ColoresPorTalla (NUEVA ARQUITECTURA)
 * 
 * Versión mejorada que usa:
 * - WizardStateMachine: Control de estados
 * - WizardEventBus: Sistema de eventos
 * - WizardLifecycleManager: Ciclo de vida
 * 
 * Mantiene compatibilidad con código existente.
 */

window.ColoresPorTalla = (function() {
    'use strict';

    // Instancia del wizard con nueva arquitectura
    let wizardInstance = null;
    let isInitialized = false;

    /**
     * ALMACENAMIENTO DE IMÁGENES (BLOB URLs)
     * Centraliza la gestión de imágenes para evitar duplicados y fugas de memoria
     */
    const _imageStore = new Map();

    /**
     * Guarda una imagen en el almacén temporal y devuelve un ID único
     * @param {File|Blob} file
     * @returns {string|null} ID de la imagen
     */
    function _storeImage(file) {
        if (!file) return null;
        const id = 'img_' + Math.random().toString(36).substr(2, 9);
        const blobUrl = URL.createObjectURL(file);
        _imageStore.set(id, { id, file, blobUrl, nombre: file.name });
        return id;
    }

    /**
     * Obtiene una imagen del almacén
     * @param {string} id
     * @returns {Object|null} {id, file, blobUrl, nombre}
     */
    function _getImage(id) {
        if (!id) return null;
        return _imageStore.get(id) || null;
    }

    /**
     * Elimina una imagen del almacén y libera el Blob URL
     * @param {string} id
     */
    function _removeImage(id) {
        if (!id) return;
        const data = _imageStore.get(id);
        if (data) {
            if (data.blobUrl) URL.revokeObjectURL(data.blobUrl);
            _imageStore.delete(id);
        }
    }

    /**
     * INICIALIZACIÓN: Crear la instancia del wizard
     */
    async function init() {
        try {
            // Esperar a que los módulos se carguen
            let intentos = 0;
            const maxIntentos = 50; // 5 segundos con delays de 100ms
            
            while ((!window.StateManager || !window.AsignacionManager || 
                    !window.WizardManager || !window.UIRenderer) && intentos < maxIntentos) {
                await new Promise(resolve => setTimeout(resolve, 100));
                intentos++;
            }
            
            if (!window.StateManager || !window.AsignacionManager || 
                !window.WizardManager || !window.UIRenderer) {
                console.error('[ColoresPorTalla]  Módulos dependientes no cargados:', {
                    StateManager: !!window.StateManager,
                    AsignacionManager: !!window.AsignacionManager,
                    WizardManager: !!window.WizardManager,
                    UIRenderer: !!window.UIRenderer
                });
                throw new Error('Faltan módulos dependientes después de esperar');
            }

            // Crear instancia del wizard con la nueva arquitectura
            wizardInstance = await WizardBootstrap.create({
                domSelectors: {
                    container: 'modal-asignar-colores-por-talla',
                    required: [
                        '#wzd-btn-atras',
                        '#wzd-btn-siguiente',
                        '#btn-guardar-asignacion',
                        '#btn-cancelar-wizard'
                    ]
                },
                onReady: _handleWizardReady,
                onClosed: _handleWizardClosed
            });

            // Registrar listeners adicionales
            _setupEventListeners();

            // 🔔 NOTA: El botón "Asignar por Talla" ahora tiene data-bs-toggle="modal" y data-bs-target="#modal-asignar-colores-por-talla"
            // Bootstrap maneja la apertura automáticamente, no necesitamos addEventListener

            // Registrar listener al modal para cuando se cierra (con retry si jQuery no está disponible)
            const maxRetries = 30; // 3 segundos
            let retries = 0;
            while (!window.jQuery && retries < maxRetries) {
                await new Promise(resolve => setTimeout(resolve, 100));
                retries++;
            }
            _setupModalListeners();

            isInitialized = true;
            return true;

        } catch (error) {
            return false;
        }
    }

    /**
     * PUNTO DE ENTRADA: Mostrar/cerrar el wizard
     * Ahora utiliza ModalManager para abstracción
     */
    async function toggleVistaAsignacion() {
        if (!wizardInstance) {
            return;
        }

        try {
            const currentState = wizardInstance.lifecycle.getState();

            if (currentState === 'IDLE') {
                // Mostrar el wizard
                await wizardInstance.lifecycle.show();
                
                // Usar ModalManager para abrir el modal
                if (window.ModalManager) {
                    await window.ModalManager.openWizard();
                } else {
                    console.warn('[ColoresPorTalla] ModalManager no disponible, intentando jQuery directo');
                    const modalElement = document.getElementById('modal-asignar-colores-por-talla');
                    if (modalElement && window.jQuery) {
                        jQuery(modalElement).modal('show');
                    }
                }
                
                // 🔄 Inicializar wizard al paso correcto (previene que se quede en el paso anterior)
                if (window.WizardManager && typeof window.WizardManager.inicializarWizard === 'function') {
                    window.WizardManager.inicializarWizard();
                }

                _updateUI_ShowWizard();
            } else {
                // Cerrar el wizard
                await wizardInstance.lifecycle.close();
                
                // Usar ModalManager para cerrar el modal
                if (window.ModalManager) {
                    await window.ModalManager.closeWizard();
                } else {
                    console.warn('[ColoresPorTalla] ModalManager no disponible, intentando jQuery directo');
                    const modalElement = document.getElementById('modal-asignar-colores-por-talla');
                    if (modalElement && window.jQuery) {
                        jQuery(modalElement).modal('hide');
                    }
                }
                
                _updateUI_HideWizard();
            }
        } catch (error) {
            console.error('[ColoresPorTalla] Error en toggleVistaAsignacion:', error);
            wizardInstance.eventBus.emit('wizard:error', { action: 'toggle', error });
        }
    }

    /**
     * CALLBACKS: Cuando wizard está listo
     */
    function _handleWizardReady() {
        // El wizard está listo para interactuar
        // Todos los listeners están registrados
    }

    /**
     * CALLBACKS: Cuando wizard se cierra
     */
    function _handleWizardClosed() {
        // El wizard fue cerrado
        // Se vuelve a mostrar la tabla de telas
    }

    /**
     * SETUP: Registrar listeners de eventos
     */
    function _setupEventListeners() {
        const { eventBus } = wizardInstance;

        // Evento: Usuario clickea "Siguiente"
        eventBus.subscribe('button:siguiente:clicked', async () => {
            try {
                const pasoActual = window.StateManager.getPasoActual();
                
                if (window.WizardManager && typeof window.WizardManager.pasoSiguiente === 'function') {
                    const avanzó = window.WizardManager.pasoSiguiente();
                    if (avanzó !== false) {
                        eventBus.emit('wizard:paso-avanzado', { paso: pasoActual + 1 });
                    }
                }
            } catch (error) {
                console.error('[ColoresPorTalla] Error en Siguiente:', error);
                eventBus.emit('wizard:error', { action: 'siguiente', error });
            }
        });

        // Evento: Usuario clickea "Atrás"
        eventBus.subscribe('button:atras:clicked', async () => {
            try {
                const pasoActual = window.StateManager.getPasoActual();
                
                if (window.WizardManager && typeof window.WizardManager.pasoAnterior === 'function') {
                    await Promise.resolve(window.WizardManager.pasoAnterior());
                    eventBus.emit('wizard:paso-retrocedido', { paso: pasoActual - 1 });
                }
            } catch (error) {
                console.error('[ColoresPorTalla] Error en Atrás:', error);
                eventBus.emit('wizard:error', { action: 'atras', error });
            }
        });

        // Evento: Usuario clickea "Guardar"
        eventBus.subscribe('button:guardar:clicked', async () => {
            const btnG = document.getElementById('btn-guardar-asignacion');
            const htmlOriginal = btnG ? (btnG._htmlOriginal || btnG.innerHTML) : '';
            
            const restaurarBoton = () => {
                if (btnG) {
                    btnG.innerHTML = htmlOriginal;
                    btnG.disabled = false;
                    btnG.style.opacity = '1';
                    delete btnG._htmlOriginal;
                }
            };
            
            // Mostrar spinner (por si WizardBootstrap no lo puso)
            if (btnG && !btnG.disabled) {
                btnG._htmlOriginal = btnG.innerHTML;
                btnG.disabled = true;
                btnG.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true" style="margin-right: 0.5rem;"></span><span>Guardando...</span>';
                btnG.style.opacity = '0.85';
            }
            
            try {
                eventBus.emit('wizard:saving-started');

                if (window.wizardGuardarAsignacion && typeof window.wizardGuardarAsignacion === 'function') {
                    await Promise.resolve(window.wizardGuardarAsignacion());
                    eventBus.emit('wizard:saved-success');
                    
                    // Mostrar éxito
                    if (btnG) {
                        btnG.innerHTML = '<span class="material-symbols-rounded" style="font-size: 1.2rem; margin-right: 0.5rem;">check_circle</span><span>¡Guardado!</span>';
                        btnG.style.opacity = '1';
                    }
                    
                    // Actualizar tarjetas de resumen con las asignaciones guardadas
                    const genero = window.StateManager.getGeneroSeleccionado();
                    const tallas = window.StateManager.getTallasSeleccionadas();
                    
                    // Actualizar la tabla de resumen de asignaciones
                    actualizarTablaResumen();
                    
                    // Actualizar window.tallasRelacionales para que crearTarjetaGenero funcione
                    if (!window.tallasRelacionales) {
                        window.tallasRelacionales = {
                            DAMA: {},
                            CABALLERO: {},
                            UNISEX: {},
                            SOBREMEDIDA: {}
                        };
                    }
                    
                    // Asegurar que la clave del género existe con la estructura correcta
                    const generoUppercase = String(genero).toUpperCase();
                    if (!window.tallasRelacionales[generoUppercase]) {
                        window.tallasRelacionales[generoUppercase] = {};
                    }
                    
                    // Rellenar tallasRelacionales con las tallas guardadas (con cantidad mínima 1)
                    tallas.forEach(talla => {
                        // Guardar con cantidad 1 para que aparezcan en la tarjeta
                        window.tallasRelacionales[generoUppercase][talla] = 1;
                    });
                    
                    // Crear/actualizar la tarjeta de género
                    if (window.crearTarjetaGenero && typeof window.crearTarjetaGenero === 'function') {
                        window.crearTarjetaGenero(generoUppercase);
                    }
                    
                    // Actualizar total de prendas
                    if (window.actualizarTotalPrendas && typeof window.actualizarTotalPrendas === 'function') {
                        window.actualizarTotalPrendas();
                    }
                    
                    // Cerrar wizard después de 1.5 segundos
                    setTimeout(() => {
                        restaurarBoton();
                        toggleVistaAsignacion();
                    }, 1500);
                } else {
                    restaurarBoton();
                }
            } catch (error) {
                console.error('[ColoresPorTalla] Error en Guardar:', error);
                restaurarBoton();
                eventBus.emit('wizard:saving-error', { error });
            }
        });

        // Evento: Usuario clickea "Cancelar"
        eventBus.subscribe('button:cancelar:clicked', async () => {
            try {
                
                // Resetear wizard
                if (window.WizardManager && typeof window.WizardManager.resetWizard === 'function') {
                    window.WizardManager.resetWizard();
                }
                
                // Cerrar vista
                await wizardInstance.lifecycle.close();
                _updateUI_HideWizard();
                
                eventBus.emit('wizard:cancelled');
            } catch (error) {
                console.error('[ColoresPorTalla] Error en Cancelar:', error);
                eventBus.emit('wizard:error', { action: 'cancelar', error });
            }
        });

        // Reaccionar a errores en eventos
        eventBus.subscribe('wizard:error', ({ action, error }) => {
            console.error(`[ColoresPorTalla] Error en acción ${action}:`, error);
            // Aquí podrías mostrar un toast o mensaje de error al usuario
        });
    }

    /**
     * UI: Mostrar vista de asignación (modal)
     * Ahora que el wizard está en un modal separado, no necesita hacer display/hidden
     */
    function _updateUI_ShowWizard() {
        // Bootstrap Modal maneja la visibilidad, no hay nada que hacer aquí
    }

    /**
     * UI: Ocultar vista de asignación (modal)
     */
    function _updateUI_HideWizard() {
        // Bootstrap Modal maneja la visibilidad, no hay nada que hacer aquí
    }

    /**
     * EVENTOS: Configurar listeners del modal Bootstrap 4
     */
    function _setupModalListeners() {
        const modalElement = document.getElementById('modal-asignar-colores-por-talla');
        if (!modalElement) {
            console.warn('[ColoresPorTalla] No se encontró el modal wizard');
            return;
        }

        // Cuando el modal se cierra
        if (window.jQuery) {
            try {
                jQuery(modalElement).on('hidden.bs.modal', async function() {
                    
                    // Solo cerrar el wizard si está en estado READY (abierto)
                    // No cerrar si está en IDLE (ya cerrado), CLOSING, INITIALIZING o DISPOSED
                    const currentState = wizardInstance?.lifecycle?.getState?.();
                    if (wizardInstance && currentState === 'READY') {
                        try {
                            await wizardInstance.lifecycle.close();
                            _updateUI_HideWizard();
                        } catch (error) {
                        }
                    }
                });

                // Cuando el modal se abre
                jQuery(modalElement).on('show.bs.modal', async function() {
                    const currentState = wizardInstance?.lifecycle?.getState?.();
                    if (wizardInstance && currentState === 'IDLE') {
                        try {
                            await wizardInstance.lifecycle.show();
                            _updateUI_ShowWizard();
                        } catch (error) {
                        }
                    }
                });
            } catch (error) {
            }
        } else {
        }
    }

    /**
     * COMPATIBILIDAD: Funciones públicas que mantienen interfaz antigua
     * (para código que aún llama directamente)
     */

    function wizardGuardarAsignacion() {
        try {
            // Obtener datos del estado actual
            const genero = window.StateManager ? window.StateManager.getGeneroSeleccionado() : null;
            const tallas = window.StateManager ? window.StateManager.getTallasSeleccionadas() : [];
            const tipo = window.StateManager ? window.StateManager.getTipoTallaSel() : null;
            const tela = window.StateManager ? window.StateManager.getTelaSeleccionada() : null;
            
            if (!tela) {
                alert('Error: No hay tela seleccionada');
                return false;
            }
            
            if (!genero || tallas.length === 0) {
                alert('Error: Género o tallas no seleccionadas');
                return false;
            }
            
            // Recopilar asignaciones de colores por talla desde el DOM
            const asignacionesPorTalla = {};
            const coloresInput = document.querySelectorAll('.color-input-wizard');
            const cantidadInput = document.querySelectorAll('.cantidad-input-wizard');
            const referenciaInput = document.querySelectorAll('.referencia-input-wizard');
            const imagenInput = document.querySelectorAll('.imagen-tela-wizard');
            // const observacionesInput = document.querySelectorAll('.observaciones-input-wizard');
            
            // Agrupar colores por talla (con objeto {nombre, cantidad, referencia, imagen, observaciones})
            coloresInput.forEach((inputColor, i) => {
                const talla = inputColor.dataset.talla;
                const cantidad = cantidadInput[i] ? parseInt(cantidadInput[i].value) || 0 : 0;
                const color = inputColor.value.trim().toUpperCase();
                const referencia = referenciaInput[i] ? referenciaInput[i].value.trim().toUpperCase() : '';
                const observaciones = ''; // observacionesInput[i] ? observacionesInput[i].value.trim() : '';
                const imagenFile = imagenInput[i] ? imagenInput[i].files[0] : null;
                
                // Solo procesar si hay color y cantidad > 0, y si la talla está en nuestra lista
                if (color && cantidad > 0 && talla && tallas.includes(talla)) {
                    if (!asignacionesPorTalla[talla]) {
                        asignacionesPorTalla[talla] = [];
                    }
                    // Guardar como objeto con nombre, cantidad, referencia, observaciones e imagen
                    const colorData = {
                        nombre: color,
                        cantidad: cantidad
                    };
                    
                    // Agregar referencia si existe
                    if (referencia) {
                        colorData.referencia = referencia;
                    }
                    
                    // Agregar observaciones si existen
                    if (observaciones) {
                        colorData.observaciones = observaciones;
                    }
                    
                    // Procesar imagen si existe — guardar en store global
                    if (imagenFile) {
                        colorData.imagen_id = _storeImage(imagenFile);
                        colorData.imagen_nombre = imagenFile.name;
                    }
                    
                    asignacionesPorTalla[talla].push(colorData);
                }
            });
            
            // Verificar que hay al menos una asignación
            if (Object.keys(asignacionesPorTalla).length === 0) {
                alert('Por favor selecciona al menos un color con cantidad > 0 para cada talla');
                return false;
            }
            
            // Guardar usando AsignacionManager
            if (!window.AsignacionManager) {
                alert('Error: Sistema de asignación no disponible');
                return false;
            }
            
            const resultado = window.AsignacionManager.guardarAsignacionesMultiples(
                genero,
                tallas,
                tipo,
                tela,
                asignacionesPorTalla
            );
            
            if (resultado) {
                // Asegurar que la tela quede registrada en telasCreacion
                if (!window.telasCreacion) {
                    window.telasCreacion = [];
                }
                const telaYaExiste = window.telasCreacion.some(t => 
                    (t.tela || t.nombreTela || t.nombre || '').toUpperCase() === tela.toUpperCase()
                );
                if (!telaYaExiste) {
                    window.telasCreacion.push({
                        tela: tela.toUpperCase(),
                        color: '',
                        referencia: '',
                        imagenes: [],
                        fechaCreacion: new Date().toISOString()
                    });
                    console.log('[wizardGuardarAsignacion] Tela agregada a telasCreacion:', tela);
                }
                
                // Actualizar chips de telas agregadas en el modal prenda
                if (typeof window.renderizarTelasChips === 'function') {
                    window.renderizarTelasChips();
                }
                
                return true;
            } else {
                alert('Error al guardar asignaciones. Verifica que todas las tallas tengan colores.');
                return false;
            }
        } catch (error) {
            alert(`Error al guardar: ${error.message}`);
            return false;
        }
    }

    function guardarAsignacionColores() {
        return window.AsignacionManager ? window.AsignacionManager.guardarAsignacionColores() : null;
    }

    function actualizarTallasDisponibles() {
        if (window.WizardManager && typeof window.WizardManager.actualizarTallasDisponibles === 'function') {
            return window.WizardManager.actualizarTallasDisponibles();
        }
    }

    function actualizarColoresDisponibles() {
        if (window.WizardManager && typeof window.WizardManager.actualizarColoresDisponibles === 'function') {
            return window.WizardManager.actualizarColoresDisponibles();
        }
    }

    function verificarBtnGuardarAsignacion() {
        if (window.UIRenderer && typeof window.UIRenderer.verificarBtnGuardarAsignacion === 'function') {
            return window.UIRenderer.verificarBtnGuardarAsignacion();
        }
    }

    function agregarColorPersonalizado() {
        if (window.ColoresPorTalla && typeof window.ColoresPorTalla.agregarColorPersonalizado === 'function') {
            return window.ColoresPorTalla.agregarColorPersonalizado();
        }
    }

    function limpiarColorPersonalizado() {
        if (window.ColoresPorTalla && typeof window.ColoresPorTalla.limpiarColorPersonalizado === 'function') {
            return window.ColoresPorTalla.limpiarColorPersonalizado();
        }
    }

    function actualizarCantidadAsignacion() {
        if (window.ColoresPorTalla && typeof window.ColoresPorTalla.actualizarCantidadAsignacion === 'function') {
            return window.ColoresPorTalla.actualizarCantidadAsignacion();
        }
    }



    function eliminarAsignacion() {
        if (window.ColoresPorTalla && typeof window.ColoresPorTalla.eliminarAsignacion === 'function') {
            return window.ColoresPorTalla.eliminarAsignacion();
        }
    }

    function obtenerDatosAsignaciones() {
        return window.AsignacionManager ? window.AsignacionManager.obtenerAsignaciones() : null;
    }

    function limpiarAsignaciones() {
        if (window.AsignacionManager && typeof window.AsignacionManager.limpiarAsignaciones === 'function') {
            window.AsignacionManager.limpiarAsignaciones();
        }
    }

    function cargarAsignacionesPrevias(datos) {
        if (window.AsignacionManager && typeof window.AsignacionManager.cargarAsignacionesPrevias === 'function') {
            window.AsignacionManager.cargarAsignacionesPrevias(datos);
        }
    }

    /**
     * Actualizar tabla de resumen de asignaciones
     * Muestra dinámicamente todas las asignaciones guardadas
     * Oculta/Muestra secciones según haya asignaciones
     */
    function actualizarTablaResumen() {
        const tablaBody = document.getElementById('tabla-resumen-asignaciones-cuerpo');
        const seccionResumen = document.getElementById('seccion-resumen-asignaciones');
        const seccionTallasOriginal = document.getElementById('seccion-tallas-cantidades');
        const msgVacio = document.getElementById('msg-resumen-vacio');
        
        if (!tablaBody || !seccionResumen) {
            console.warn('[ColoresPorTalla] No se encontró tabla-resumen-asignaciones-cuerpo o seccion-resumen-asignaciones');
            return;
        }

        const asignaciones = window.StateManager ? window.StateManager.getAsignaciones() : {};
        const asignacionesArray = Object.entries(asignaciones);
        
        // 🔴 FIX: Si ya no hay asignaciones wizard, limpiar telas que fueron agregadas
        // automáticamente por el wizard (no tienen color/referencia/imágenes propias)
        if (asignacionesArray.length === 0 && window.telasCreacion && window.telasCreacion.length > 0) {
            // Filtrar: solo mantener telas que tienen datos propios (color, referencia o imágenes)
            const telasConDatos = window.telasCreacion.filter(t => {
                const tieneColor = t.color && t.color.trim() !== '';
                const tieneRef = t.referencia && t.referencia.trim() !== '';
                const tieneImgs = t.imagenes && t.imagenes.length > 0;
                const tieneObs = t.observaciones && t.observaciones.trim() !== '' && t.observaciones.trim() !== '-';
                return tieneColor || tieneRef || tieneImgs || tieneObs;
            });
            if (telasConDatos.length < window.telasCreacion.length) {
                console.log('[ColoresPorTalla] 🧹 Limpiando', window.telasCreacion.length - telasConDatos.length, 'telas huérfanas del wizard');
                window.telasCreacion = telasConDatos;
            }
        }
        
        const telasSimples = window.telasCreacion || [];

        // Determinar si hay datos para mostrar
        const hayDatos = asignacionesArray.length > 0 || telasSimples.length > 0;

        if (!hayDatos) {
            if (seccionResumen) seccionResumen.style.display = 'none';
            if (seccionTallasOriginal) seccionTallasOriginal.style.display = 'block';
            tablaBody.innerHTML = '';
            console.log('[ColoresPorTalla] 📭 Sin datos - tabla limpia, sección oculta');
            return;
        }

        // Mostrar sección de resumen
        if (seccionResumen) seccionResumen.style.display = 'block';
        if (asignacionesArray.length > 0 && seccionTallasOriginal) {
            seccionTallasOriginal.style.display = 'none';
        }
        if (msgVacio) msgVacio.style.display = 'none';

        // Construir filas: TELA | COLOR | REFERENCIA | IMAGEN | GÉNERO | TALLA | CANTIDAD | ACCIÓN
        let html = '';
        let totalAsignaciones = 0;
        const cellStyle = 'padding: 0.75rem; color: #374151;';
        const cellMuted = 'padding: 0.75rem; color: #9ca3af; font-size: 0.8rem;';

        // --- 1) Filas de asignaciones wizard ---
        // Recopilar telas que ya están en wizard para excluirlas de las simples
        const telasEnWizard = new Set();

        asignacionesArray.forEach(([clave, asignacion], rowIdx) => {
            const { genero, talla, tela, colores, referencia, imagenes } = asignacion;
            if (tela) telasEnWizard.add(tela.toUpperCase());
            
            if (!colores || !Array.isArray(colores) || colores.length === 0) return;
            
            // Calcular cantidad total para esta asignación
            const totalCant = colores.reduce((sum, c) => sum + (typeof c.cantidad === 'number' ? c.cantidad : 1), 0);
            totalAsignaciones += totalCant;
            
            const bg = (rowIdx % 2 === 0) ? '#ffffff' : '#f9fafb';
            
            // Chips de colores con cantidad
            const coloresChipsHtml = colores.map(c => {
                const nombre = c.nombre || '--';
                const cant = typeof c.cantidad === 'number' ? c.cantidad : 1;
                return `<span style="display:inline-block;background:#dbeafe;color:#1e40af;padding:0.15rem 0.5rem;border-radius:12px;font-size:0.73rem;font-weight:500;margin:0.1rem;white-space:nowrap;">${nombre} (${cant})</span>`;
            }).join('');
            
            // 🔴 FIX: Buscar referencia en colores (para wizard con multi-refs) O en asignación (para cotización simple)
            const refsDesdoColores = [...new Set(colores.map(c => c.referencia).filter(Boolean))];
            const refDesdeAsignacion = referencia ? [referencia] : [];
            const allRefs = [...new Set([...refsDesdoColores, ...refDesdeAsignacion])];
            const refHtml = allRefs.length > 0 ? allRefs.join(', ') : '-';
            
            // 🔴 FIX: Buscar imágenes en colores (para wizard) O en asignación (para cotización)
            let imgsHtml = '<span style="color:#9ca3af;font-size:0.75rem;">—</span>';
            
            // Primero intentar con imagenes de asignación (cotización simple con múltiples colores)
            let imagenEncontrada = false;
            if (imagenes && Array.isArray(imagenes) && imagenes.length > 0) {
                const imgHTML = imagenes.map(img => {
                    if (typeof img === 'string') {
                        // Normalizar URL
                        let src = img;
                        if (!src.startsWith('/') && !src.startsWith('http')) {
                            src = '/storage/' + src;
                        }
                        return '<img src="' + src + '" style="width:28px;height:28px;object-fit:cover;border-radius:3px;border:1px solid #d1d5db;margin:1px;" alt="img">';
                    } else if (typeof img === 'object' && img !== null) {
                        // Objeto con ruta/ruta_webp
                        const rutaFinal = img.ruta || img.ruta_webp || img.url || img.src;
                        if (rutaFinal) {
                            let src = rutaFinal;
                            if (!src.startsWith('/') && !src.startsWith('http')) {
                                src = '/storage/' + src;
                            }
                            return '<img src="' + src + '" style="width:28px;height:28px;object-fit:cover;border-radius:3px;border:1px solid #d1d5db;margin:1px;" alt="img">';
                        }
                    }
                    return '';
                }).filter(Boolean).join('');
                
                if (imgHTML) {
                    imgsHtml = imgHTML;
                    imagenEncontrada = true;
                    console.log('[ColoresPorTalla]  Imagen desde asignación:', clave);
                }
            }
            
            // Si no encontró imagenes en asignación, intentar desde colores (wizard tradicional)
            if (!imagenEncontrada) {
                const conImagenBlob = colores.filter(c => c.imagen_id && _getImage(c.imagen_id));
                const conImagenServidor = colores.filter(c => c.imagen_ruta && !(c.imagen_id && _getImage(c.imagen_id)));
                if (conImagenBlob.length > 0 || conImagenServidor.length > 0) {
                    let partsHtml = '';
                    // Imágenes locales (blob)
                    partsHtml += conImagenBlob.map(c => {
                        const imgData = _getImage(c.imagen_id);
                        if (imgData && imgData.blobUrl) {
                            return '<img src="' + imgData.blobUrl + '" style="width:28px;height:28px;object-fit:cover;border-radius:3px;border:1px solid #d1d5db;margin:1px;" alt="img">';
                        }
                        return '';
                    }).join('');
                    // Imágenes del servidor (guardadas en storage)
                    partsHtml += conImagenServidor.map(c => {
                        let src = c.imagen_ruta;
                        // Normalizar: asegurar que empiece con /storage/ sin duplicar
                        if (src.startsWith('/storage/')) {
                            // ya tiene prefijo correcto
                        } else if (src.startsWith('storage/')) {
                            src = '/' + src;
                        } else if (!src.startsWith('/')) {
                            src = '/storage/' + src;
                        }
                        return '<img src="' + src + '" style="width:28px;height:28px;object-fit:cover;border-radius:3px;border:1px solid #d1d5db;margin:1px;" alt="img">';
                    }).join('');
                    if (partsHtml) {
                        imgsHtml = partsHtml;
                    }
                }
            }
            
            // Combinar observaciones (únicas, no vacías) - COMENTADO
            // const obs = [...new Set(colores.map(c => c.observaciones).filter(Boolean))];
            // const obsText = obs.length > 0 ? obs.join(' | ') : '-';
            
            html += `
                <tr style="background: ${bg}; border-bottom: 1px solid #e5e7eb;" data-clave="${clave}" data-tipo="wizard">
                    <td style="${cellStyle} font-weight: 500;" data-field="tela">${tela || '--'}</td>
                    <td style="${cellStyle}" data-field="color"><div style="display:flex;flex-wrap:wrap;gap:0.15rem;">${coloresChipsHtml}</div></td>
                    <td style="${cellStyle} font-size:0.8rem;" data-field="referencia">${refHtml}</td>
                    <td style="${cellStyle}" data-field="imagen"><div style="display:flex;flex-wrap:wrap;gap:2px;">${imgsHtml}</div></td>
                    <td style="${cellStyle}" data-field="genero">${genero ? genero.toUpperCase() : '--'}</td>
                    <td style="${cellStyle} font-weight: 500;" data-field="talla">${talla || '--'}</td>
                    <td style="${cellStyle} text-align: center; font-weight: 600;" data-field="cantidad">${totalCant}</td>
                    <td style="padding: 0.75rem; text-align: center;">
                        <div style="display: flex; gap: 0.25rem; justify-content: center;">
                            <button type="button" class="btn-editar-asignacion" data-clave="${clave}"
                                style="background: #dbeafe; border: none; color: #2563eb; padding: 0.25rem 0.5rem; border-radius: 4px; cursor: pointer; font-size: 0.75rem; font-weight: 600;"
                                title="Editar asignación">✎</button>
                            <button type="button" class="btn-eliminar-asignacion" data-clave="${clave}"
                                style="background: #fee2e2; border: none; color: #dc2626; padding: 0.25rem 0.5rem; border-radius: 4px; cursor: pointer; font-size: 0.75rem; font-weight: 600;"
                                title="Eliminar asignación">✕</button>
                        </div>
                    </td>
                </tr>
            `;
        });

        // Determinar si hay wizard rows para mostrar/ocultar columnas wizard-only
        const hayWizard = asignacionesArray.length > 0;

        // --- 2) Filas de telas simples (sin wizard) ---
        telasSimples.forEach((t, idx) => {
            // 🔴 FIX: Buscar tanto 'tela' como 'nombre_tela' (compatibilidad cotización + prendas nuevas)
            const telaName = (t.nombre_tela || t.tela || '').toUpperCase();
            // Saltar si esta tela ya está representada por wizard
            if (telasEnWizard.has(telaName)) return;

            const bg = (idx % 2 === 0) ? '#ffffff' : '#f9fafb';
            const color = t.color || t.color_nombre || '-';
            const referencia = t.referencia || '-';
            // const observaciones = t.observaciones || '-';

            // Imagen thumbnail
            let imgHtml = '<span style="color:#9ca3af;font-size:0.75rem;">—</span>';
            if (t.imagenes && t.imagenes.length > 0) {
                const img = t.imagenes[0];
                if (img instanceof File || img instanceof Blob) {
                    imgHtml = '<img src="' + URL.createObjectURL(img) + '" style="width:32px;height:32px;object-fit:cover;border-radius:3px;border:1px solid #d1d5db;" alt="img">';
                } else if (typeof img === 'string') {
                    const src = (img.startsWith('/') || img.startsWith('http') || img.startsWith('blob:') || img.startsWith('data:'))
                        ? img
                        : '/storage/' + img;
                    imgHtml = '<img src="' + src + '" style="width:32px;height:32px;object-fit:cover;border-radius:3px;border:1px solid #d1d5db;" alt="img">';
                } else if (typeof img === 'object' && img !== null) {
                    // 🔴 FIX: Manejar objetos temporales del modal y objetos desde backend
                    let rutaFinal = '';
                    if (img.file instanceof File) {
                        rutaFinal = URL.createObjectURL(img.file);
                    } else {
                        rutaFinal = img.previewUrl || img.blobUrl || img.dataURL || img.ruta || img.ruta_webp || img.url || img.src || img.ruta_original;
                    }
                    if (rutaFinal) {
                        // Normalizar ruta si es necesaria
                        let urlFinal = rutaFinal;
                        if (!urlFinal.startsWith('/') && !urlFinal.startsWith('http') && !urlFinal.startsWith('blob:') && !urlFinal.startsWith('data:')) {
                            urlFinal = '/storage/' + urlFinal;
                        }
                        imgHtml = '<img src="' + urlFinal + '" style="width:32px;height:32px;object-fit:cover;border-radius:3px;border:1px solid #d1d5db;" alt="img">';
                    }
                }
            }

            // Columnas wizard-only: solo si hay wizard rows
            const wizardCols = hayWizard ? `
                    <td style="${cellMuted}" data-field="genero" data-col="wizard-only">—</td>
                    <td style="${cellMuted}" data-field="talla" data-col="wizard-only">—</td>
                    <td style="${cellMuted} text-align:center;" data-field="cantidad" data-col="wizard-only">—</td>` : '';
            
            html += `
                <tr style="background: ${bg}; border-bottom: 1px solid #e5e7eb;" data-tipo="simple" data-tela-idx="${idx}">
                    <td style="${cellStyle} font-weight: 500;" data-field="tela">${telaName || '--'}</td>
                    <td style="${cellStyle}" data-field="color">${color}</td>
                    <td style="${cellStyle}" data-field="referencia">${referencia}</td>
                    <td style="${cellStyle}" data-field="imagen">${imgHtml}</td>${wizardCols}
                    <td style="padding: 0.75rem; text-align: center;">
                        <div style="display: flex; gap: 0.25rem; justify-content: center;">
                            <button type="button" class="btn-editar-tela-simple" data-tela-idx="${idx}"
                                style="background: #dbeafe; border: none; color: #2563eb; padding: 0.25rem 0.5rem; border-radius: 4px; cursor: pointer; font-size: 0.75rem; font-weight: 600;"
                                title="Editar tela">✎</button>
                            <button type="button" class="btn-eliminar-tela-simple" data-tela-idx="${idx}"
                                style="background: #fee2e2; border: none; color: #dc2626; padding: 0.25rem 0.5rem; border-radius: 4px; cursor: pointer; font-size: 0.75rem; font-weight: 600;"
                                title="Eliminar tela">✕</button>
                        </div>
                    </td>
                </tr>
            `;
        });

        tablaBody.innerHTML = html;

        // Mostrar/ocultar columnas wizard-only en el thead
        const tabla = tablaBody.closest('table');
        if (tabla) {
            tabla.querySelectorAll('th[data-col="wizard-only"]').forEach(th => {
                th.style.display = hayWizard ? '' : 'none';
            });
        }
        
        // Configurar edición modal para wizard rows
        tablaBody.querySelectorAll('.btn-editar-asignacion').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault(); e.stopPropagation();
                const clave = btn.getAttribute('data-clave');
                if (clave) _abrirModalEditarWizard(clave, btn);
            });
        });

        // Configurar eliminación de asignaciones wizard
        tablaBody.querySelectorAll('.btn-eliminar-asignacion').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault(); e.stopPropagation();
                const clave = btn.getAttribute('data-clave');
                if (clave && window.StateManager) {
                    const asignaciones = window.StateManager.getAsignaciones();
                    delete asignaciones[clave];
                    window.StateManager.setAsignaciones(asignaciones);
                    
                    // 🔴 FIX: Si no quedan más asignaciones, limpiar estado completo
                    const restantes = Object.keys(asignaciones);
                    if (restantes.length === 0) {
                        console.log('[ColoresPorTalla] 🧹 Última asignación eliminada, limpiando estado...');
                        // Limpiar tallasRelacionales
                        if (window.tallasRelacionales) {
                            Object.keys(window.tallasRelacionales).forEach(g => {
                                window.tallasRelacionales[g] = {};
                            });
                        }
                        // Limpiar tarjetas de género del DOM
                        const containerTarjetas = document.getElementById('tarjetas-generos-container');
                        if (containerTarjetas) containerTarjetas.innerHTML = '';
                        // Desmarcar botones de género
                        document.querySelectorAll('[id^="btn-genero-"]').forEach(b => {
                            b.dataset.selected = 'false';
                            b.style.borderColor = '';
                            b.style.background = '';
                        });
                        document.querySelectorAll('[id^="check-"]').forEach(chk => {
                            chk.style.display = 'none';
                        });
                    }
                    
                    actualizarTablaResumen();
                    if (typeof window.actualizarTotalPrendas === 'function') window.actualizarTotalPrendas();
                }
            });
        });

        // Configurar edición inline para telas simples
        tablaBody.querySelectorAll('.btn-editar-tela-simple').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault(); e.stopPropagation();
                const idx = parseInt(btn.getAttribute('data-tela-idx'));
                if (!window.telasCreacion || isNaN(idx) || !window.telasCreacion[idx]) return;
                _activarEdicionTelaSimple(btn.closest('tr'), idx);
            });
        });

        // Configurar eliminación de telas simples
        tablaBody.querySelectorAll('.btn-eliminar-tela-simple').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const idx = parseInt(btn.getAttribute('data-tela-idx'));
                if (window.telasCreacion && !isNaN(idx)) {
                    window.telasCreacion.splice(idx, 1);
                    actualizarTablaResumen();
                }
            });
        });
        
        // Actualizar total
        const totalElement = document.getElementById('total-asignaciones-resumen');
        if (totalElement) {
            totalElement.textContent = totalAsignaciones;
        }
        
        console.log('[ColoresPorTalla]  Tabla unificada actualizada - wizard:', asignacionesArray.length, 'asignaciones, simples:', telasSimples.length, 'telas, total:', totalAsignaciones);
    }

    /**
     * Activar edición de una tela simple: abre el modal precargado
     */
    function _activarEdicionTelaSimple(fila, idx) {
        if (typeof window.abrirModalTelaSimple === 'function') {
            window.abrirModalTelaSimple(idx);
        } else if (typeof abrirModalTelaSimple === 'function') {
            abrirModalTelaSimple(idx);
        } else {
            console.error('[ColoresPorTalla] abrirModalTelaSimple no disponible');
        }
    }

    /**
     * Abre el modal de edición para una asignación wizard (agrupada)
     */
    function _desenfocarActivoDentroDeModal(modal) {
        if (!modal) return;
        const active = document.activeElement;
        if (active instanceof HTMLElement && modal.contains(active)) {
            active.blur();
        }
    }

    function _cerrarModalWizardConFoco(modal, focusReturnEl = null) {
        if (!modal) return;

        _desenfocarActivoDentroDeModal(modal);

        const fallbackFocus = document.getElementById('btn-asignar-colores-prenda');
        const destinoFoco = (
            focusReturnEl instanceof HTMLElement &&
            focusReturnEl.isConnected &&
            !focusReturnEl.disabled
        ) ? focusReturnEl : fallbackFocus;

        if (destinoFoco) {
            jQuery(modal).one('hidden.bs.modal.focusRestoreWizard', () => {
                requestAnimationFrame(() => {
                    if (destinoFoco.isConnected && !destinoFoco.disabled) {
                        destinoFoco.focus({ preventScroll: true });
                    }
                });
            });
        }

        jQuery(modal).modal('hide');
    }

    function _abrirModalEditarWizard(clave, focusReturnEl = null) {
        const asignaciones = window.StateManager ? window.StateManager.getAsignaciones() : {};
        const asig = asignaciones[clave];
        if (!asig) return;

        // Almacenar IDs de imagen por color para el modal de edición
        // Soporta tanto blobs locales (imagen_id) como imágenes del servidor (imagen_ruta)
        window._wizardEditImagenes = asig.colores.map(c => c.imagen_id || null);
        window._wizardEditImagenesRuta = asig.colores.map(c => c.imagen_ruta || null);

        // Eliminar modal previo si existe
        const existing = document.getElementById('modal-editar-asignacion-wizard');
        if (existing) {
            _desenfocarActivoDentroDeModal(existing);
            jQuery(existing).modal('hide');
            existing.remove();
        }

        // Construir tarjetas de color
        const coloresCardsHtml = asig.colores.map((color, idx) => {
            const nombre = color.nombre || '';
            const cantidad = typeof color.cantidad === 'number' ? color.cantidad : 1;
            const referencia = color.referencia || '';
            const observaciones = color.observaciones || '';
            // Soportar blob local (imagen_id) O imagen guardada en servidor (imagen_ruta)
            const hasBlobImg = !!(color.imagen_id && _getImage(color.imagen_id));
            const hasServerImg = !!(color.imagen_ruta);
            const hasImg = hasBlobImg || hasServerImg;
            let previewSrc = '';
            if (hasBlobImg) {
                const imgData = _getImage(color.imagen_id);
                previewSrc = imgData ? imgData.blobUrl : '';
            } else if (hasServerImg) {
                previewSrc = color.imagen_ruta;
                // Normalizar ruta
                if (!previewSrc.startsWith('/storage/')) {
                    if (previewSrc.startsWith('storage/')) {
                        previewSrc = '/' + previewSrc;
                    } else if (!previewSrc.startsWith('/')) {
                        previewSrc = '/storage/' + previewSrc;
                    }
                }
            }

            return `
            <div class="wizard-edit-color-card" data-color-idx="${idx}" style="border:1px solid #e5e7eb;border-radius:8px;padding:0.75rem;margin-bottom:0.5rem;background:#f9fafb;">
                <div style="display:grid;grid-template-columns:1fr 100px;gap:0.5rem;margin-bottom:0.5rem;">
                    <div>
                        <label style="font-size:0.7rem;font-weight:600;color:#374151;display:block;margin-bottom:0.2rem;">COLOR</label>
                        <input type="text" class="wz-edit-color form-control" value="${nombre}" list="opciones-colores" style="text-transform:uppercase;font-size:0.85rem;" onkeyup="this.value=this.value.toUpperCase()">
                    </div>
                    <div>
                        <label style="font-size:0.7rem;font-weight:600;color:#374151;display:block;margin-bottom:0.2rem;">CANT.</label>
                        <input type="number" class="wz-edit-cantidad form-control" min="0" value="${cantidad}" style="font-size:0.85rem;text-align:center;font-weight:600;">
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.5rem;margin-bottom:0.5rem;">
                    <div>
                        <label style="font-size:0.7rem;font-weight:600;color:#374151;display:block;margin-bottom:0.2rem;">REFERENCIA</label>
                        <input type="text" class="wz-edit-ref form-control" value="${referencia}" style="text-transform:uppercase;font-size:0.85rem;" onkeyup="this.value=this.value.toUpperCase()">
                    </div>
                    <!-- OBSERVACIONES COMENTADO
                    <div>
                        <label style="font-size:0.7rem;font-weight:600;color:#374151;display:block;margin-bottom:0.2rem;">OBSERVACIONES</label>
                        <input type="text" class="wz-edit-obs form-control" value="${observaciones}" style="font-size:0.85rem;">
                    </div>
                    -->
                </div>
                <div>
                    <label style="font-size:0.7rem;font-weight:600;color:#374151;display:block;margin-bottom:0.2rem;">IMAGEN</label>
                    <input type="file" class="wz-edit-file-input" data-cidx="${idx}" accept="image/*" style="display:none;">
                    <div class="wz-edit-dropzone" data-cidx="${idx}" tabindex="0" style="display:${hasImg ? 'none' : 'flex'};flex-direction:column;align-items:center;justify-content:center;gap:0.3rem;padding:0.75rem;border:2px dashed #d1d5db;border-radius:6px;background:#fafafa;color:#6b7280;cursor:pointer;transition:all 0.15s;outline:none;text-align:center;font-size:0.75rem;">
                        <span class="material-symbols-rounded" style="font-size:1.5rem;color:#9ca3af;">add_photo_alternate</span>
                        <span>Click, arrastra o <strong>Ctrl+V</strong></span>
                    </div>
                    <div class="wz-edit-preview" data-cidx="${idx}" style="display:${hasImg ? 'block' : 'none'};position:relative;border-radius:6px;overflow:hidden;border:1px solid #d1d5db;">
                        <img class="wz-edit-preview-img" src="${previewSrc}" alt="Preview" style="width:100%;max-height:120px;object-fit:cover;display:block;">
                        <button type="button" class="wz-edit-preview-del" data-cidx="${idx}" style="position:absolute;top:4px;right:4px;width:24px;height:24px;border-radius:50%;border:none;background:#ef4444;color:white;cursor:pointer;display:flex;align-items:center;justify-content:center;box-shadow:0 2px 6px rgba(0,0,0,0.3);">
                            <span class="material-symbols-rounded" style="font-size:0.85rem;">close</span>
                        </button>
                    </div>
                </div>
            </div>`;
        }).join('');

        const modalHtml = `
        <div id="modal-editar-asignacion-wizard" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static" data-keyboard="false" style="z-index:1060000;">
            <div class="modal-dialog modal-dialog-centered" role="document" style="max-width:560px;">
                <div class="modal-content" style="border-radius:12px;overflow:hidden;border:none;box-shadow:0 25px 50px rgba(0,0,0,0.25);">
                    <div style="background:linear-gradient(135deg,#7c3aed 0%,#5b21b6 100%);padding:1rem 1.25rem;display:flex;justify-content:space-between;align-items:center;">
                        <h5 style="margin:0;color:white;font-size:1rem;font-weight:600;display:flex;align-items:center;gap:0.5rem;">
                            <span class="material-symbols-rounded" style="font-size:1.2rem;">edit</span>Editar Asignación
                        </h5>
                        <button type="button" class="btn-cerrar-wizard-edit" style="background:none;border:none;color:rgba(255,255,255,0.8);cursor:pointer;padding:0.25rem;line-height:1;">
                            <span class="material-symbols-rounded" style="font-size:1.3rem;">close</span>
                        </button>
                    </div>
                    <div style="padding:1.25rem;max-height:70vh;overflow-y:auto;">
                        <div style="display:grid;grid-template-columns:1fr 1fr 80px;gap:0.75rem;margin-bottom:1rem;">
                            <div>
                                <label style="font-size:0.75rem;font-weight:600;color:#374151;display:block;margin-bottom:0.3rem;">TELA</label>
                                <input type="text" id="wz-edit-tela" list="opciones-telas" value="${asig.tela || ''}" class="form-control" style="text-transform:uppercase;font-size:0.9rem;" onkeyup="this.value=this.value.toUpperCase();">
                            </div>
                            <div>
                                <label style="font-size:0.75rem;font-weight:600;color:#374151;display:block;margin-bottom:0.3rem;">GÉNERO</label>
                                <select id="wz-edit-genero" class="form-control" style="font-size:0.9rem;">
                                    <option value="dama" ${(asig.genero||'').toLowerCase()==='dama'?'selected':''}>DAMA</option>
                                    <option value="caballero" ${(asig.genero||'').toLowerCase()==='caballero'?'selected':''}>CABALLERO</option>
                                    <option value="unisex" ${(asig.genero||'').toLowerCase()==='unisex'?'selected':''}>UNISEX</option>
                                </select>
                            </div>
                            <div>
                                <label style="font-size:0.75rem;font-weight:600;color:#374151;display:block;margin-bottom:0.3rem;">TALLA</label>
                                <input type="text" id="wz-edit-talla" value="${asig.talla || ''}" class="form-control" style="text-transform:uppercase;font-size:0.9rem;text-align:center;" onkeyup="this.value=this.value.toUpperCase();">
                            </div>
                        </div>
                        ${asig.referencia ? `
                        <div style="margin-bottom:0.75rem;">
                            <label style="font-size:0.75rem;font-weight:600;color:#374151;display:block;margin-bottom:0.3rem;">REFERENCIA COMÚN</label>
                            <input type="text" id="wz-edit-referencia-comun" value="${asig.referencia || ''}" class="form-control" style="text-transform:uppercase;font-size:0.9rem;background:#f0f9ff;border-color:#0284c7;color:#0c4a6e;font-weight:600;" readonly>
                            <small style="color:#6b7280;font-size:0.7rem;display:block;margin-top:0.2rem;">Aplicada a todos los colores</small>
                        </div>
                        ` : ''}
                        <div style="border-bottom:1px solid #e5e7eb;margin-bottom:0.75rem;padding-bottom:0.5rem;">
                            <span style="font-size:0.8rem;font-weight:600;color:#6b7280;">COLORES (${asig.colores.length})</span>
                        </div>
                        <div id="wz-edit-colores-container">
                            ${coloresCardsHtml}
                        </div>
                    </div>
                    <div style="padding:0.75rem 1.25rem;background:#f9fafb;border-top:1px solid #e5e7eb;display:flex;justify-content:flex-end;gap:0.5rem;">
                        <button type="button" class="btn btn-secondary btn-cerrar-wizard-edit" style="padding:0.5rem 1.25rem;font-size:0.85rem;">Cancelar</button>
                        <button type="button" class="btn btn-primary btn-guardar-wizard-edit" style="padding:0.5rem 1.5rem;font-size:0.85rem;font-weight:600;display:inline-flex;align-items:center;gap:0.35rem;">
                            <span class="material-symbols-rounded" style="font-size:1.1rem;">save</span>Guardar
                        </button>
                    </div>
                </div>
            </div>
        </div>`;

        document.body.insertAdjacentHTML('beforeend', modalHtml);
        const modal = document.getElementById('modal-editar-asignacion-wizard');

        // Configurar drop zones de imagen por cada color
        asig.colores.forEach((color, idx) => {
            _setupWizardEditDropZone(modal, idx);
        });

        // Registrar como sub-modal en DragDropManager para interceptar Ctrl+V
        if (window.DragDropManager && typeof window.DragDropManager.registrarSubModal === 'function') {
            window.DragDropManager.registrarSubModal('modal-editar-asignacion-wizard', (file) => {
                // Buscar el dropzone con foco, o el primero visible
                const focusedDz = modal.querySelector('.wz-edit-dropzone:focus');
                let targetIdx = null;
                if (focusedDz) {
                    targetIdx = parseInt(focusedDz.getAttribute('data-cidx'));
                } else {
                    // Usar el primer dropzone visible
                    const visibleDz = modal.querySelector('.wz-edit-dropzone[style*="display:flex"], .wz-edit-dropzone[style*="display: flex"]');
                    if (visibleDz) targetIdx = parseInt(visibleDz.getAttribute('data-cidx'));
                }
                if (targetIdx !== null && !isNaN(targetIdx)) {
                    const id = _storeImage(file);
                    window._wizardEditImagenes[targetIdx] = id;
                    const imgData = _getImage(id);
                    const preview = modal.querySelector(`.wz-edit-preview[data-cidx="${targetIdx}"]`);
                    const dropzone = modal.querySelector(`.wz-edit-dropzone[data-cidx="${targetIdx}"]`);
                    const previewImg = preview ? preview.querySelector('.wz-edit-preview-img') : null;
                    if (previewImg) previewImg.src = imgData.blobUrl;
                    if (dropzone) dropzone.style.display = 'none';
                    if (preview) preview.style.display = 'block';
                    console.log(`[ColoresPorTalla]  Imagen pegada via DragDropManager en color idx=${targetIdx}`);
                }
            });
        }

        // Cerrar
        modal.querySelectorAll('.btn-cerrar-wizard-edit').forEach(btn => {
            btn.addEventListener('click', () => _cerrarModalWizardConFoco(modal, focusReturnEl));
        });

        // Guardar
        modal.querySelector('.btn-guardar-wizard-edit').addEventListener('click', () => {
            _guardarEdicionWizard(clave, modal, focusReturnEl);
        });

        // Limpieza al cerrar
        jQuery(modal).on('hidden.bs.modal', function() {
            // Desregistrar sub-modal
            if (window.DragDropManager && typeof window.DragDropManager.desregistrarSubModal === 'function') {
                window.DragDropManager.desregistrarSubModal('modal-editar-asignacion-wizard');
            }
            modal.remove();
            window._wizardEditImagenes = [];
            window._wizardEditImagenesRuta = [];
        });

        jQuery(modal).modal('show');
    }

    /**
     * Configura eventos de drag-drop/paste/click para una zona de imagen en el modal de edición wizard
     */
    function _setupWizardEditDropZone(modal, idx) {
        const dropzone = modal.querySelector(`.wz-edit-dropzone[data-cidx="${idx}"]`);
        const preview = modal.querySelector(`.wz-edit-preview[data-cidx="${idx}"]`);
        const previewImg = preview ? preview.querySelector('.wz-edit-preview-img') : null;
        const btnDel = modal.querySelector(`.wz-edit-preview-del[data-cidx="${idx}"]`);
        const fileInput = modal.querySelector(`.wz-edit-file-input[data-cidx="${idx}"]`);
        if (!dropzone || !preview || !previewImg || !btnDel || !fileInput) return;

        const cargar = (file) => {
            if (!file || !file.type.startsWith('image/')) return;
            const id = _storeImage(file);
            window._wizardEditImagenes[idx] = id;
            // Al subir nueva imagen, descartar la del servidor
            if (window._wizardEditImagenesRuta) window._wizardEditImagenesRuta[idx] = null;
            const imgData = _getImage(id);
            previewImg.src = imgData.blobUrl;
            dropzone.style.display = 'none';
            preview.style.display = 'block';
        };

        const eliminar = () => {
            const oldId = window._wizardEditImagenes[idx];
            if (oldId) _removeImage(oldId);
            window._wizardEditImagenes[idx] = null;
            if (window._wizardEditImagenesRuta) window._wizardEditImagenesRuta[idx] = null;
            previewImg.src = '';
            fileInput.value = '';
            preview.style.display = 'none';
            dropzone.style.display = 'flex';
            dropzone.style.borderColor = '#d1d5db';
            dropzone.style.background = '#fafafa';
        };

        dropzone.addEventListener('click', () => fileInput.click());
        previewImg.addEventListener('click', () => fileInput.click());
        btnDel.addEventListener('click', (e) => { e.stopPropagation(); eliminar(); });
        fileInput.addEventListener('change', () => { if (fileInput.files.length) cargar(fileInput.files[0]); });

        // Drag & Drop
        dropzone.addEventListener('dragover', (e) => {
            e.preventDefault(); e.stopPropagation();
            dropzone.style.borderColor = '#3b82f6'; dropzone.style.background = '#eff6ff'; dropzone.style.borderStyle = 'solid';
        });
        dropzone.addEventListener('dragleave', (e) => {
            e.preventDefault(); e.stopPropagation();
            dropzone.style.borderColor = '#d1d5db'; dropzone.style.background = '#fafafa'; dropzone.style.borderStyle = 'dashed';
        });
        dropzone.addEventListener('drop', (e) => {
            e.preventDefault(); e.stopPropagation();
            dropzone.style.borderColor = '#d1d5db'; dropzone.style.background = '#fafafa'; dropzone.style.borderStyle = 'dashed';
            const files = e.dataTransfer.files;
            if (files.length && files[0].type.startsWith('image/')) cargar(files[0]);
        });

        // Ctrl+V (paste)
        dropzone.addEventListener('paste', (e) => {
            e.preventDefault();
            const items = e.clipboardData?.items;
            if (!items) return;
            for (let i = 0; i < items.length; i++) {
                if (items[i].type.startsWith('image/')) { cargar(items[i].getAsFile()); break; }
            }
        });

        // Hover
        dropzone.addEventListener('mouseover', () => {
            if (preview.style.display === 'none') { dropzone.style.borderColor = '#3b82f6'; dropzone.style.color = '#3b82f6'; dropzone.style.background = '#eff6ff'; }
        });
        dropzone.addEventListener('mouseout', () => {
            if (preview.style.display === 'none') { dropzone.style.borderColor = '#d1d5db'; dropzone.style.color = '#6b7280'; dropzone.style.background = '#fafafa'; }
        });

        // Focus para Ctrl+V
        dropzone.addEventListener('focus', () => { dropzone.style.borderColor = '#3b82f6'; dropzone.style.boxShadow = '0 0 0 3px rgba(59,130,246,0.15)'; });
        dropzone.addEventListener('blur', () => { if (preview.style.display === 'none') dropzone.style.borderColor = '#d1d5db'; dropzone.style.boxShadow = 'none'; });
    }

    /**
     * Guarda los cambios del modal de edición wizard
     */
    function _guardarEdicionWizard(claveOriginal, modal, focusReturnEl = null) {
        const nTela = (modal.querySelector('#wz-edit-tela').value || '').trim().toUpperCase();
        const nGenero = modal.querySelector('#wz-edit-genero').value;
        const nTalla = (modal.querySelector('#wz-edit-talla').value || '').trim().toUpperCase();
        const asignacionesActuales = window.StateManager ? window.StateManager.getAsignaciones() : {};
        const asignacionOriginal = asignacionesActuales[claveOriginal] || {};
        const telaOriginal = (asignacionOriginal?.tela || '').trim().toUpperCase();
        const telaCambio = !!(telaOriginal && telaOriginal !== nTela);
        
        // 🔴 FIX: Leer referencia común (si existe)
        const refComun = modal.querySelector('#wz-edit-referencia-comun') 
            ? (modal.querySelector('#wz-edit-referencia-comun').value || '').trim().toUpperCase()
            : '';

        if (!nTela) { modal.querySelector('#wz-edit-tela').style.borderColor = '#ef4444'; return; }
        if (!nTalla) { modal.querySelector('#wz-edit-talla').style.borderColor = '#ef4444'; return; }

        // Leer todas las tarjetas de color
        const cards = modal.querySelectorAll('.wizard-edit-color-card');
        const nuevosColores = [];
        cards.forEach((card, idx) => {
            const colorNombre = (card.querySelector('.wz-edit-color').value || '').trim().toUpperCase();
            const cantidad = parseInt(card.querySelector('.wz-edit-cantidad').value) || 0;
            const referencia = (card.querySelector('.wz-edit-ref').value || '').trim().toUpperCase() || refComun;  // 🔴 FIX: Usar referencia común
            const observaciones = ''; // (card.querySelector('.wz-edit-obs').value || '').trim();
            const colorOriginal = Array.isArray(asignacionOriginal?.colores) ? asignacionOriginal.colores[idx] : null;

            if (!colorNombre) return; // omitir colores vacíos

            const colorData = { nombre: colorNombre, cantidad: cantidad };
            if (referencia) colorData.referencia = referencia;
            // if (observaciones) colorData.observaciones = observaciones;
            if (window._wizardEditImagenes && window._wizardEditImagenes[idx]) {
                const imgId = window._wizardEditImagenes[idx];
                const imgData = _getImage(imgId);
                if (imgData) {
                    colorData.imagen_id = imgId;
                    colorData.imagen_nombre = imgData.nombre;
                }
            } else if (window._wizardEditImagenesRuta && window._wizardEditImagenesRuta[idx]) {
                // Preservar imagen del servidor si no se cambió
                colorData.imagen_ruta = window._wizardEditImagenesRuta[idx];
            }

            const colorOriginalNombre = (colorOriginal?.nombre || '').trim().toUpperCase();
            const colorCambio = !!(colorOriginalNombre && colorOriginalNombre !== colorNombre);
            if (!telaCambio && !colorCambio) {
                const colorTelaId =
                    colorOriginal?.prenda_pedido_colores_telas_id ||
                    colorOriginal?.color_tela_id ||
                    asignacionOriginal?.prenda_pedido_colores_telas_id ||
                    asignacionOriginal?.color_tela_id ||
                    null;
                const colorId = colorOriginal?.color_id || asignacionOriginal?.color_id || null;
                const telaId = colorOriginal?.tela_id || asignacionOriginal?.tela_id || null;

                if (colorTelaId) {
                    colorData.prenda_pedido_colores_telas_id = colorTelaId;
                }
                if (colorId) {
                    colorData.color_id = colorId;
                }
                if (telaId) {
                    colorData.tela_id = telaId;
                }
            }

            nuevosColores.push(colorData);
        });

        if (nuevosColores.length === 0) {
            alert('Debe haber al menos un color.');
            return;
        }

        // Actualizar StateManager
        if (window.StateManager) {
            const asignaciones = asignacionesActuales;
            delete asignaciones[claveOriginal];

            const tipo = 'Letra';
            const nuevaClave = `${nGenero.toLowerCase()}-${tipo}-${nTalla}`;
            const primerColor = nuevosColores[0] || null;
            const relacionUnica = (nuevosColores.length === 1 && primerColor)
                ? (primerColor.prenda_pedido_colores_telas_id || primerColor.color_tela_id || null)
                : null;

            asignaciones[nuevaClave] = {
                genero: nGenero.toLowerCase(),
                tela: nTela,
                tipo: tipo,
                talla: nTalla,
                tela_id: telaCambio ? null : (asignacionOriginal?.tela_id || primerColor?.tela_id || null),
                color_id: (nuevosColores.length === 1 && !telaCambio) ? (primerColor?.color_id || asignacionOriginal?.color_id || null) : null,
                prenda_pedido_colores_telas_id: telaCambio ? null : relacionUnica,
                colores: nuevosColores
            };
            window.StateManager.setAsignaciones(asignaciones);
        }

        _cerrarModalWizardConFoco(modal, focusReturnEl);
        actualizarTablaResumen();
        console.log('[ColoresPorTalla]  Asignación wizard editada:', claveOriginal, '→', nTela, nGenero, nTalla, nuevosColores);
    }

    /**
     * INFORMACIÓN PARA DEBUGGING
     */
    function getWizardStatus() {
        if (!wizardInstance) return { initialized: false };

        return {
            initialized: isInitialized,
            state: wizardInstance.stateMachine.getState(),
            stateHistory: wizardInstance.stateMachine.getHistory(),
            eventHistory: wizardInstance.eventBus.getEventHistory()
        };
    }

    function cleanupWizard() {
        if (wizardInstance) {
            return wizardInstance.lifecycle.dispose();
        }
    }

    /**
     * API PÚBLICA
     */
    return {
        init,
        toggleVistaAsignacion,
        wizardGuardarAsignacion,
        guardarAsignacionColores,
        actualizarTallasDisponibles,
        actualizarColoresDisponibles,
        verificarBtnGuardarAsignacion,
        agregarColorPersonalizado,
        limpiarColorPersonalizado,
        actualizarCantidadAsignacion,
        eliminarAsignacion,
        obtenerDatosAsignaciones,
        limpiarAsignaciones,
        cargarAsignacionesPrevias,
        actualizarTablaResumen,
        getWizardStatus,
        cleanupWizard,
        getWizardInstance: () => wizardInstance,
        // Acceso a imágenes del almacén para enviar al backend
        getImage: _getImage
    };
})();

// INICIALIZACIÓN AUTOMÁTICA
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        setTimeout(() => {
            window.ColoresPorTalla.init();
        }, 200);
    });
} else {
    setTimeout(() => {
        window.ColoresPorTalla.init();
    }, 200);
}
