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
                console.error('[ColoresPorTalla] ❌ Módulos dependientes no cargados:', {
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
            
            // Agrupar colores por talla (con objeto {nombre, cantidad})
            coloresInput.forEach((inputColor, i) => {
                const talla = inputColor.dataset.talla;
                const cantidad = cantidadInput[i] ? parseInt(cantidadInput[i].value) || 0 : 0;
                const color = inputColor.value.trim().toUpperCase();
                
                // Solo procesar si hay color y cantidad > 0, y si la talla está en nuestra lista
                if (color && cantidad > 0 && talla && tallas.includes(talla)) {
                    if (!asignacionesPorTalla[talla]) {
                        asignacionesPorTalla[talla] = [];
                    }
                    // Guardar como objeto con nombre y cantidad
                    asignacionesPorTalla[talla].push({
                        nombre: color,
                        cantidad: cantidad
                    });
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

        if (asignacionesArray.length === 0) {
            // Ocultar sección de resumen y mostrar original
            if (seccionResumen) {
                seccionResumen.style.display = 'none';
            }
            if (seccionTallasOriginal) {
                seccionTallasOriginal.style.display = 'block';
            }
            return;
        }

        // Mostrar sección de resumen y ocultar original
        if (seccionResumen) {
            seccionResumen.style.display = 'block';
        }
        if (seccionTallasOriginal) {
            seccionTallasOriginal.style.display = 'none';
        }
        if (msgVacio) {
            msgVacio.style.display = 'none';
        }

        // Construir filas de la tabla con nuevo formato: TELA | GÉNERO | TALLA | COLOR | CANTIDAD | ACCIÓN
        let html = '';
        let totalAsignaciones = 0;

        asignacionesArray.forEach(([clave, asignacion]) => {
            const { genero, talla, tela, colores } = asignacion;
            
            if (colores && Array.isArray(colores)) {
                colores.forEach((color, index) => {
                    const cantidad = typeof color.cantidad === 'number' ? color.cantidad : 1;
                    const colorNombre = color.nombre || color || '--';
                    const backgroundColor = (index % 2 === 0) ? '#ffffff' : '#f9fafb';
                    
                    totalAsignaciones += cantidad;
                    
                    html += `
                        <tr style="background: ${backgroundColor}; border-bottom: 1px solid #e5e7eb;">
                            <td style="padding: 0.75rem; color: #374151; font-weight: 500;">${tela || '--'}</td>
                            <td style="padding: 0.75rem; color: #374151;">${genero ? genero.toUpperCase() : '--'}</td>
                            <td style="padding: 0.75rem; color: #374151; font-weight: 500;">${talla || '--'}</td>
                            <td style="padding: 0.75rem; color: #374151;">${colorNombre}</td>
                            <td style="padding: 0.75rem; text-align: center; color: #374151; font-weight: 600;">${cantidad}</td>
                            <td style="padding: 0.75rem; text-align: center;">
                                <button type="button" class="btn-eliminar-asignacion" data-clave="${clave}" data-color="${colorNombre}" 
                                        style="background: #fee2e2; border: none; color: #dc2626; padding: 0.25rem 0.5rem; border-radius: 4px; cursor: pointer; font-size: 0.75rem; font-weight: 600;">
                                    ✕
                                </button>
                            </td>
                        </tr>
                    `;
                });
            }
        });

        tablaBody.innerHTML = html;
        
        // Actualizar total
        const totalElement = document.getElementById('total-asignaciones-resumen');
        if (totalElement) {
            totalElement.textContent = totalAsignaciones;
        }
        
        console.log('[ColoresPorTalla] ✅ Tabla de resumen actualizada con', asignacionesArray.length, 'asignaciones, total:', totalAsignaciones);
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
        getWizardInstance: () => wizardInstance
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
