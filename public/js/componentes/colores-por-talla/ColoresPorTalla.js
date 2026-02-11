/**
 * Módulo: ColoresPorTalla
 * Módulo principal que orquesta todos los componentes del sistema de colores por talla
 * Gestiona la asignación de múltiples colores a cada talla-género
 */

window.ColoresPorTalla = (function() {
    'use strict';
    
    // Bandera para prevenir doble inicialización
    let eventosConfigurados = false;

    /**
     * Inicialización del módulo
     */
    function init() {
        console.log('[ColoresPorTalla]  Iniciando módulo ColoresPorTalla...');
        
        // Verificar que todos los módulos estén disponibles
        if (!window.StateManager || !window.AsignacionManager || 
            !window.WizardManager || !window.UIRenderer) {
            console.error('[ColoresPorTalla]  Faltan módulos dependientes');
            console.error('[ColoresPorTalla] Disponibles:', {
                StateManager: !!window.StateManager,
                AsignacionManager: !!window.AsignacionManager,
                WizardManager: !!window.WizardManager,
                UIRenderer: !!window.UIRenderer
            });
            return false;
        }
        
        console.log('[ColoresPorTalla]  Todos los módulos están disponibles');
        
        // Configurar eventos globales solo UNA VEZ
        if (!eventosConfigurados) {
            configurarEventosGlobales();
            eventosConfigurados = true;
            console.log('[ColoresPorTalla]  Eventos globales configurados (primera vez)');
        } else {
            console.log('[ColoresPorTalla] ⏭️ Eventos ya configurados, saltando...');
        }
        
        // Actualizar vistas iniciales
        actualizarVistasIniciales();
        console.log('[ColoresPorTalla]  Vistas iniciales actualizadas');
        
        console.log('[ColoresPorTalla]  Módulo ColoresPorTalla inicializado exitosamente');
        return true;
    }

    /**
     * Configurar eventos globales
     */
    function configurarEventosGlobales() {
        console.log('[ColoresPorTalla] 🔹 Configurando eventos globales...');
        
        // Botones principales
        const btnAsignarColores = document.getElementById('btn-asignar-colores-tallas');
        const btnCancelarWizard = document.getElementById('btn-cancelar-wizard');
        const btnGuardarAsignacion = document.getElementById('btn-guardar-asignacion');
        const wzdBtnSiguiente = document.getElementById('wzd-btn-siguiente');
        const wzdBtnAtras = document.getElementById('wzd-btn-atras');
        
        console.log('[ColoresPorTalla] 🔸 Botones encontrados:', {
            btnAsignarColores: !!btnAsignarColores,
            btnCancelarWizard: !!btnCancelarWizard,
            btnGuardarAsignacion: !!btnGuardarAsignacion,
            wzdBtnSiguiente: !!wzdBtnSiguiente,
            wzdBtnAtras: !!wzdBtnAtras
        });
        
        // Remover listeners existentes para evitar duplicados
        if (btnAsignarColores && !btnAsignarColores.dataset.listenerConfigured) {
            btnAsignarColores.addEventListener('click', toggleVistaAsignacion);
            btnAsignarColores.dataset.listenerConfigured = 'true';
            console.log('[ColoresPorTalla]  Event listener agregado a btn-asignar-colores-tallas');
        }
        
        if (btnCancelarWizard && !btnCancelarWizard.dataset.listenerConfigured) {
            btnCancelarWizard.addEventListener('click', () => {
                toggleVistaAsignacion();
                // Limpiar wizard al cancelar
                if (window.WizardManager && typeof window.WizardManager.resetWizard === 'function') {
                    try {
                        window.WizardManager.resetWizard();
                    } catch (error) {
                        console.warn('[cancelarAsignacion]  Error reseteando wizard:', error);
                    }
                }
            });
            btnCancelarWizard.dataset.listenerConfigured = 'true';
            console.log('[ColoresPorTalla]  Event listener agregado a btn-cancelar-wizard');
        }
        
        if (btnGuardarAsignacion && !btnGuardarAsignacion.dataset.listenerConfigured) {
            btnGuardarAsignacion.addEventListener('click', () => {
                wizardGuardarAsignacion();
                // Después de guardar, cerrar la vista del wizard
                setTimeout(() => {
                    toggleVistaAsignacion();
                }, 500);
            });
            btnGuardarAsignacion.dataset.listenerConfigured = 'true';
            console.log('[ColoresPorTalla]  Event listener agregado a btn-guardar-asignacion');
        }
        
        if (wzdBtnSiguiente && !wzdBtnSiguiente.dataset.listenerConfigured) {
            //  Usar arrow function para mantener contexto de WizardManager
            wzdBtnSiguiente.addEventListener('click', () => {
                if (window.WizardManager && typeof window.WizardManager.pasoSiguiente === 'function') {
                    try {
                        window.WizardManager.pasoSiguiente();
                    } catch (error) {
                        console.warn('[wzdBtnSiguiente]  Error en pasoSiguiente:', error);
                    }
                }
            });
            wzdBtnSiguiente.dataset.listenerConfigured = 'true';
            console.log('[ColoresPorTalla]  Event listener agregado a wzd-btn-siguiente');
        }
        
        if (wzdBtnAtras && !wzdBtnAtras.dataset.listenerConfigured) {
            wzdBtnAtras.addEventListener('click', () => {
                if (window.WizardManager && typeof window.WizardManager.pasoAnterior === 'function') {
                    try {
                        window.WizardManager.pasoAnterior();
                    } catch (error) {
                        console.warn('[wzdBtnAtras]  Error en pasoAnterior:', error);
                    }
                }
            });
            wzdBtnAtras.dataset.listenerConfigured = 'true';
            console.log('[ColoresPorTalla]  Event listener agregado a wzd-btn-atras');
        }
        
        // Eventos de selects
        const asignacionGeneroSelect = document.getElementById('asignacion-genero-select');
        const asignacionTallaSelect = document.getElementById('asignacion-talla-select');
        
        if (asignacionGeneroSelect && !asignacionGeneroSelect.dataset.listenerConfigured) {
            asignacionGeneroSelect.addEventListener('change', actualizarTallasDisponibles);
            asignacionGeneroSelect.dataset.listenerConfigured = 'true';
        }
        
        if (asignacionTallaSelect && !asignacionTallaSelect.dataset.listenerConfigured) {
            asignacionTallaSelect.addEventListener('change', actualizarColoresDisponibles);
            asignacionTallaSelect.dataset.listenerConfigured = 'true';
        }
        
        // Eventos de color personalizado
        const btnAgregarColorPersonalizado = document.getElementById('btn-agregar-color-personalizado');
        if (btnAgregarColorPersonalizado && !btnAgregarColorPersonalizado.dataset.listenerConfigured) {
            btnAgregarColorPersonalizado.addEventListener('click', agregarColorPersonalizado);
            btnAgregarColorPersonalizado.dataset.listenerConfigured = 'true';
        }
        
        console.log('[ColoresPorTalla]  Eventos globales configurados');
    }

    /**
     * Actualizar vistas iniciales
     */
    function actualizarVistasIniciales() {
        UIRenderer.actualizarTablaAsignaciones();
        UIRenderer.actualizarResumenAsignaciones();
        UIRenderer.actualizarVisibilidadSeccionesResumen();
    }

    /**
     * Toggle entre vista de tabla de telas y vista de asignación de colores
     */
    function toggleVistaAsignacion() {
        console.log('[ColoresPorTalla]  toggleVistaAsignacion - Iniciando búsqueda de elementos DOM');
        
        const vistaTablaTelas = document.getElementById('vista-tabla-telas');
        const vistaAsignacion = document.getElementById('vista-asignacion-colores');
        const btnAsignar = document.getElementById('btn-asignar-colores-tallas');
        const generoSelect = document.getElementById('asignacion-genero-select');
        const tallaSelect = document.getElementById('asignacion-talla-select');
        
        console.log('[ColoresPorTalla]  Elementos encontrados:', {
            vistaTablaTelas: !!vistaTablaTelas,
            vistaAsignacion: !!vistaAsignacion,
            btnAsignar: !!btnAsignar,
            generoSelect: !!generoSelect,
            tallaSelect: !!tallaSelect
        });
        
        // Obtener estado actual
        const currentDisplay = vistaAsignacion ? vistaAsignacion.style.display : 'none';
        const esVistaAsignacionActiva = currentDisplay === 'block';
        
        console.log('[ColoresPorTalla]  Estado current:', {
            displayActual: currentDisplay,
            esVistaAsignacionActiva: esVistaAsignacionActiva
        });
        
        if (esVistaAsignacionActiva) {
            // Volviendo a tabla de telas
            console.log('[ColoresPorTalla] 📋 ACCIÓN: Volviendo a tabla de telas');
            
            if (vistaTablaTelas) {
                vistaTablaTelas.style.display = 'block';
            }
            if (vistaAsignacion) {
                vistaAsignacion.style.display = 'none';
            }
            if (btnAsignar) {
                btnAsignar.innerHTML = '<span class="material-symbols-rounded">palette</span>Asignar Colores';
            }
            
            // Ocultar wizard y mostrar resumen si hay asignaciones
            ocultarWizardYMostrarResumen();
            
            console.log('[ColoresPorTalla]  vistaTablaTelas.style.display = block');
            console.log('[ColoresPorTalla]  vistaAsignacion.style.display = none');
            console.log('[ColoresPorTalla]  Volviendo a vista de Tabla de Telas');
            
        } else {
            // Abriendo vista de asignación
            console.log('[ColoresTalla] 📋 ACCIÓN: Abriendo vista de Asignación de Colores');
            
            if (vistaTablaTelas) {
                vistaTablaTelas.style.display = 'none';
            }
            if (vistaAsignacion) {
                vistaAsignacion.style.display = 'block';
            }
            if (btnAsignar) {
                btnAsignar.innerHTML = '<span class="material-symbols-rounded">arrow_back</span>Volver a Telas';
            }
            
            // Mostrar selector de telas si hay múltiples
            mostrarSelectoreTelasSiNecesario();
            
            // Resetear wizard y mostrar paso inicial
            if (window.WizardManager) {
                try {
                    if (typeof window.WizardManager.resetWizard === 'function') {
                        window.WizardManager.resetWizard();
                    }
                    if (typeof window.WizardManager.inicializarWizard === 'function') {
                        window.WizardManager.inicializarWizard();
                    }
                } catch (error) {
                    console.warn('[toggleVistaAsignacion]  Error con WizardManager:', error);
                    // Continuar sin wizard
                }
            } else {
                console.log('[toggleVistaAsignacion]  WizardManager no disponible, continuando sin wizard');
            }
            
            // Resetear selects
            if (generoSelect) {
                generoSelect.value = '';
                generoSelect.dispatchEvent(new Event('change', { bubbles: true }));
            }
            if (tallaSelect) {
                tallaSelect.value = '';
                tallaSelect.dispatchEvent(new Event('change', { bubbles: true }));
            }
            
            console.log('[ColoresPorTalla]  vistaTablaTelas.style.display = none');
            console.log('[ColoresPorTalla]  vistaAsignacion.style.display = block');
            console.log('[ColoresPorTalla] Secciones ajustadas');
            console.log('[ColoresPorTalla]  Cambiando a vista de Asignación de Colores');
            
            // Actualizar tablas de asignaciones
            UIRenderer.actualizarTablaAsignaciones();
            UIRenderer.actualizarResumenAsignaciones();
            
            console.log('[ColoresPorTalla]  Wizard state reseteado');
        }
    }

    /**
     * Mostrar selector de telas si hay múltiples
     * Retorna: nombre de tela si hay 1, null si hay múltiples (espera que usuario seleccione)
     */
    function mostrarSelectoreTelasSiNecesario() {
        const telas = window.telasCreacion || [];
        console.log('[mostrarSelectoreTelasSiNecesario]  Telas disponibles:', telas.length);
        
        if (telas.length <= 1) {
            console.log('[mostrarSelectoreTelasSiNecesario]  Una sola tela - no mostrar selector');
            return telas[0]?.tela || telas[0]?.nombre_tela || null;
        }
        
        console.log('[mostrarSelectoreTelasSiNecesario] 📋 Múltiples telas - mostrando selector');
        
        // Mostrar modal de selección de tela
        const modal = document.getElementById('modal-seleccionar-tela');
        if (!modal) {
            console.error('[mostrarSelectoreTelasSiNecesario]  Modal de selección no encontrado');
            return null;
        }
        
        // Limpiar y llenar selector
        const selector = document.getElementById('selector-tela');
        if (selector) {
            selector.innerHTML = '';
            
            telas.forEach((tela, index) => {
                const nombreTela = tela.tela || tela.nombre_tela || `Tela ${index + 1}`;
                const option = document.createElement('option');
                option.value = nombreTela;
                option.textContent = nombreTela;
                selector.appendChild(option);
            });
        }
        
        // Mostrar modal
        modal.style.display = 'flex';
        
        return new Promise((resolve) => {
            const btnConfirmar = document.getElementById('btn-confirmar-tela');
            const btnCancelar = document.getElementById('btn-cancelar-tela');
            
            const cleanup = () => {
                modal.style.display = 'none';
                btnConfirmar.onclick = null;
                btnCancelar.onclick = null;
            };
            
            btnConfirmar.onclick = () => {
                const seleccionado = selector.value;
                cleanup();
                resolve(seleccionado);
            };
            
            btnCancelar.onclick = () => {
                cleanup();
                resolve(null);
            };
        });
    }

    /**
     * Ocultar wizard y mostrar resumen si hay asignaciones
     */
    function ocultarWizardYMostrarResumen() {
        const wizardContenedor = document.getElementById('wizard-contenedor');
        const seccionTallasCantidades = document.getElementById('seccion-tallas-cantidades');
        const seccionResumenAsignaciones = document.getElementById('seccion-resumen-asignaciones');
        
        const tieneAsignaciones = AsignacionManager.obtenerTotalAsignaciones() > 0;
        
        console.log('[ocultarWizardYMostrarResumen]  Verificando asignaciones:', {
            total: AsignacionManager.obtenerTotalAsignaciones(),
            wizardContenedor: !!wizardContenedor,
            seccionTallasCantidades: !!seccionTallasCantidades,
            seccionResumenAsignaciones: !!seccionResumenAsignaciones
        });
        
        if (wizardContenedor) {
            wizardContenedor.style.display = 'none';
        }
        
        if (seccionTallasCantidades && seccionResumenAsignaciones) {
            if (tieneAsignaciones) {
                seccionTallasCantidades.style.display = 'none';
                seccionResumenAsignaciones.style.display = 'block';
                console.log('[ocultarWizardYMostrarResumen]  Hay asignaciones - mostrando resumen, ocultando tallas');
            } else {
                seccionTallasCantidades.style.display = 'block';
                seccionResumenAsignaciones.style.display = 'none';
                console.log('[ocultarWizardYMostrarResumen]  Sin asignaciones - mostrando tallas, ocultando resumen');
            }
        }
    }

    /**
     * Agregar un color personalizado directamente a las asignaciones
     */
    function agregarColorPersonalizado() {
        const generoSelect = document.getElementById('asignacion-genero-select');
        const tallaSelect = document.getElementById('asignacion-talla-select');
        const colorInput = document.getElementById('color-personalizado-input');
        const cantidadInput = document.getElementById('cantidad-color-personalizado');
        
        if (!generoSelect || !tallaSelect || !colorInput || !cantidadInput) {
            console.warn('[agregarColorPersonalizado]  Faltan elementos del formulario');
            return;
        }
        
        const genero = generoSelect.value;
        const talla = tallaSelect.value;
        const color = colorInput.value.trim();
        const cantidad = parseInt(cantidadInput.value) || 0;
        
        if (!genero || !talla || !color || cantidad <= 0) {
            console.warn('[agregarColorPersonalizado]  Datos inválidos:', { genero, talla, color, cantidad });
            return;
        }
        
        console.log('[agregarColorPersonalizado]  Agregando color personalizado:', {
            genero: genero,
            talla: talla,
            color: color,
            cantidad: cantidad
        });
        
        // Guardar asignación
        const resultado = AsignacionManager.agregarColor(genero, talla, color, cantidad);
        
        if (resultado) {
            // Actualizar UI
            UIRenderer.actualizarTablaAsignaciones();
            UIRenderer.actualizarResumenAsignaciones();
            
            // Limpiar inputs
            limpiarColorPersonalizado();
            
            console.log('[agregarColorPersonalizado]  Color agregado exitosamente');
        } else {
            console.error('[agregarColorPersonalizado]  Error al agregar color');
        }
    }

    /**
     * Limpiar inputs de color personalizado
     */
    function limpiarColorPersonalizado() {
        const colorInput = document.getElementById('color-personalizado-input');
        const cantidadInput = document.getElementById('cantidad-color-personalizado');
        
        if (colorInput) colorInput.value = '';
        if (cantidadInput) cantidadInput.value = '';
        
        console.log('[limpiarColorPersonalizado] 🧹 Inputs limpiados');
    }

    /**
     * Actualizar tallas disponibles según el género seleccionado
     * NOTA: El wizard usa botones en lugar de selects, esta función es legacy
     */
    function actualizarTallasDisponibles() {
        console.log('[ColoresPorTalla] 🔵 Actualizando tallas disponibles...');
        
        // El wizard usa botones dinámicos, no selects tradicionales
        // Esta función se mantiene por compatibilidad pero el wizard maneja esto internamente
        const generoSelect = document.getElementById('asignacion-genero-select');
        const tallaSelect = document.getElementById('asignacion-talla-select');
        
        // Si no existen los selects, el wizard está manejando esto con botones
        if (!generoSelect || !tallaSelect) {
            console.log('[actualizarTallasDisponibles]  Wizard usando botones en lugar de selects');
            return;
        }
        
        const genero = generoSelect.value;
        console.log('[actualizarTallasDisponibles] 👥 Género seleccionado:', genero);
        
        // Limpiar select de tallas
        tallaSelect.innerHTML = '<option value="">Seleccionar talla...</option>';
        
        if (genero) {
            const tiposTalla = StateManager.getTallasDisponibles(genero);
            console.log('[ColoresPorTalla] Tipos de talla para', genero, ':', tiposTalla);
            
            // Obtener todas las tallas de todos los tipos
            let todasLasTallas = [];
            Object.keys(tiposTalla).forEach(tipo => {
                const tallas = tiposTalla[tipo];
                tallas.forEach(talla => {
                    todasLasTallas.push(`${tipo} - ${talla}`);
                });
            });
            
            // Agregar opciones al select
            todasLasTallas.forEach(talla => {
                const option = document.createElement('option');
                option.value = talla;
                option.textContent = talla;
                tallaSelect.appendChild(option);
            });
            
            console.log('[actualizarTallasDisponibles] 📋 Tallas agregadas:', todasLasTallas.length);
        } else {
            console.log('[actualizarTallasDisponibles]  No hay género seleccionado');
        }
        
        // Actualizar colores disponibles
        actualizarColoresDisponibles();
    }

    /**
     * Actualizar colores disponibles cuando se selecciona una talla
     * NOTA: El wizard usa botones en lugar de selects, esta función es legacy
     */
    function actualizarColoresDisponibles() {
        console.log('[ColoresPorTalla] 🔵 Actualizando colores disponibles...');
        
        // El wizard usa botones dinámicos, no selects tradicionales
        const generoSelect = document.getElementById('asignacion-genero-select');
        const tallaSelect = document.getElementById('asignacion-talla-select');
        const colorInput = document.getElementById('color-personalizado-input');
        
        // Si no existen los elementos, el wizard está manejando esto con botones
        if (!generoSelect || !tallaSelect || !colorInput) {
            console.log('[actualizarColoresDisponibles]  Wizard usando botones en lugar de selects/inputs');
            return;
        }
        
        const genero = generoSelect.value;
        const talla = tallaSelect.value;
        console.log('[actualizarColoresDisponibles] 👥 Género:', genero, 'Talla:', talla);
        
        // Los colores se manejan a través de las telas agregadas, no desde StateManager
        // Esta función es legacy y se mantiene por compatibilidad
        console.log('[actualizarColoresDisponibles]  Colores actualizados (wizard maneja esto)');
    }

    /**
     * Verificar si se puede mostrar el botón de guardar asignación
     */
    function verificarBtnGuardarAsignacion() {
        const generoSelect = document.getElementById('asignacion-genero-select');
        const tallaSelect = document.getElementById('asignacion-talla-select');
        const btnGuardar = document.getElementById('btn-guardar-asignacion');
        
        if (!generoSelect || !tallaSelect || !btnGuardar) {
            return false;
        }
        
        const genero = generoSelect.value;
        const talla = tallaSelect.value;
        const tieneColores = AsignacionManager.tieneColores(genero, talla);
        
        // Habilitar botón solo si hay género, talla y colores
        btnGuardar.disabled = !genero || !talla || !tieneColores;
        
        console.log('[verificarBtnGuardarAsignacion]  Estado botón guardar:', {
            genero: genero,
            talla: talla,
            tieneColores: tieneColores,
            btnDisabled: btnGuardar.disabled
        });
        
        return !btnGuardar.disabled;
    }

    /**
     * Guardar asignación de colores para la talla-género seleccionada
     */
    function guardarAsignacionColores() {
        console.log('[guardarAsignacionColores]  Iniciando guardado de asignaciones...');
        
        // Intentar obtener datos del wizard (StateManager) primero
        const genero = StateManager.getGeneroSeleccionado();
        const tallas = StateManager.getTallasSeleccionadas();
        const tipo = StateManager.getTipoTallaSel();
        
        if (!genero || !tallas || tallas.length === 0) {
            console.error('[guardarAsignacionColores]  Datos incompletos del wizard');
            alert('Por favor complete todos los pasos del wizard antes de guardar');
            return false;
        }
        
        console.log('[guardarAsignacionColores]  Datos del wizard:', { genero, tallas, tipo });
        
        // Obtener inputs de color y cantidad
        const inputs = document.querySelectorAll('[data-color-input]');
        if (inputs.length === 0) {
            console.warn('[guardarAsignacionColores]  No se encontraron inputs de color');
            return false;
        }
        
        const colores = [];
        inputs.forEach(input => {
            const colorInput = input.querySelector('[data-color-nombre]');
            const cantidadInput = input.querySelector('[data-color-cantidad]');
            
            if (colorInput && cantidadInput) {
                const color = colorInput.value.trim();
                const cantidad = parseInt(cantidadInput.value) || 0;
                
                if (color && cantidad > 0) {
                    colores.push({ color, cantidad });
                }
            }
        });
        
        if (colores.length === 0) {
            console.warn('[guardarAsignacionColores]  No hay colores válidos');
            alert('Por favor agregue al menos un color con cantidad mayor a 0');
            return false;
        }
        
        console.log('[guardarAsignacionColores]  Colores a guardar:', colores);
        
        // Guardar asignación
        const resultado = AsignacionManager.agregarColores(genero, tallas, tipo, colores);
        
        if (resultado) {
            // Actualizar UI
            UIRenderer.actualizarTablaAsignaciones();
            UIRenderer.actualizarResumenAsignaciones();
            
            console.log('[guardarAsignacionColores]  Asignación guardada exitosamente');
            return true;
        } else {
            console.error('[guardarAsignacionColores]  Error al guardar asignación');
            alert('Error al guardar la asignación. Por favor intente nuevamente.');
            return false;
        }
    }

    /**
     * Guardar asignación del wizard (múltiples tallas)
     */
    function wizardGuardarAsignacion() {
        console.log('[wizardGuardarAsignacion]  Iniciando guardado de asignaciones del wizard...');
        
        const genero = StateManager.getGeneroSeleccionado();
        const tallas = StateManager.getTallasSeleccionadas();
        const tipo = StateManager.getTipoTallaSel();
        const telaDelWizard = StateManager.getTelaSeleccionada();
        
        console.log('[wizardGuardarAsignacion] 📋 Estado actual:', { genero, tallas, tipo, telaDelWizard });
        
        if (!genero || !tallas || tallas.length === 0) {
            console.error('[wizardGuardarAsignacion]  Datos incompletos del wizard');
            alert('Por favor complete todos los pasos del wizard antes de guardar');
            return;
        }
        
        // Obtener inputs de color y cantidad para cada talla
        const inputsColor = document.querySelectorAll('[data-color-input]');
        console.log('[wizardGuardarAsignacion]  Inputs de color encontrados:', inputsColor.length);
        
        if (inputsColor.length === 0) {
            console.error('[wizardGuardarAsignacion]  No se encontraron inputs de color');
            alert('Error: No se encontraron campos de color. Por favor intente nuevamente.');
            return;
        }
        
        // Procesar cada input de color
        const asignacionesAgrupadas = {};
        let totalUnidades = 0;
        
        inputsColor.forEach((input, index) => {
            const tallaInput = input.querySelector('[data-talla-nombre]');
            const colorInputs = input.querySelectorAll('[data-color-input]');
            
            if (!tallaInput) {
                console.warn(`[wizardGuardarAsignacion]  No se encontró input de talla en el input #${index}`);
                return;
            }
            
            const talla = tallaInput.dataset.tallaNombre;
            
            if (!talla) {
                console.warn(`[wizardGuardarAsignacion]  Input de talla sin dataset.tallaNombre en input #${index}`);
                return;
            }
            
            // Procesar colores para esta talla
            const colores = [];
            colorInputs.forEach((colorInput, colorIndex) => {
                const colorNombreInput = colorInput.querySelector('[data-color-nombre]');
                const cantidadInput = colorInput.querySelector('[data-color-cantidad]');
                
                if (colorNombreInput && cantidadInput) {
                    const color = colorNombreInput.value.trim();
                    const cantidad = parseInt(cantidadInput.value) || 0;
                    
                    if (color && cantidad > 0) {
                        colores.push({ color, cantidad });
                        totalUnidades += cantidad;
                    }
                }
            });
            
            if (colores.length > 0) {
                asignacionesAgrupadas[talla] = colores;
                console.log(`[wizardGuardarAsignacion]  Guardando: ${talla} con ${colores.length} colores`);
            }
        });
        
        console.log('[wizardGuardarAsignacion]  Asignaciones agrupadas:', asignacionesAgrupadas);
        
        // Guardar en StateManager
        const resultado = AsignacionManager.guardarAsignaciones(genero, tipo, telaDelWizard, asignacionesAgrupadas);
        
        if (resultado) {
            console.log('[wizardGuardarAsignacion] Resultado:', resultado);
            
            // Actualizar UI
            console.log('[wizardGuardarAsignacion]  Llamando actualizarTablaAsignaciones()...');
            UIRenderer.actualizarTablaAsignaciones();
            
            console.log('[wizardGuardarAsignacion]  Llamando actualizarResumenAsumenAsignaciones()...');
            UIRenderer.actualizarResumenAsignaciones();
            
            console.log('[wizardGuardarAsignacion]  Llamando actualizarVisibilidadSeccionesResumen()...');
            UIRenderer.actualizarVisibilidadSeccionesResumen();
            
            console.log('[wizardGuardarAsignacion]  ÉXITO - Actualizando UI...');
            
            // Resetear wizard
            console.log('[wizardGuardarAsignacion]  Reseteando wizard...');
            if (window.WizardManager && typeof window.WizardManager.resetWizard === 'function') {
                try {
                    window.WizardManager.resetWizard();
                } catch (error) {
                    console.warn('[wizardGuardarAsignacion]  Error reseteando wizard:', error);
                }
            }
            
            console.log('[wizardGuardarAsignacion]  FIN - Asignaciones guardadas y wizard reseteado');
            
            // Cerrar vista de asignación
            setTimeout(() => {
                toggleVistaAsignacion();
            }, 500);
            
            return true;
        } else {
            console.error('[wizardGuardarAsignacion]  Error al guardar asignaciones');
            alert('Error al guardar las asignaciones. Por favor intente nuevamente.');
            return false;
        }
    }

    /**
     * Actualizar cantidad de una asignación
     */
    function actualizarCantidadAsignacion(genero, talla, color, nuevaCantidad) {
        const resultado = AsignacionManager.actualizarCantidadAsignacion(genero, talla, color, nuevaCantidad);
        if (resultado) {
            UIRenderer.actualizarResumenAsignaciones();
        }
        return resultado;
    }

    /**
     * Eliminar una asignación
     */
    function eliminarAsignacion(genero, talla, color) {
        const resultado = AsignacionManager.eliminarAsignacion(genero, talla, color);
        if (resultado) {
            UIRenderer.actualizarTablaAsignaciones();
            UIRenderer.actualizarResumenAsignaciones();
        }
        return resultado;
    }

    /**
     * Obtener datos de asignaciones para guardar
     */
    function obtenerDatosAsignaciones() {
        return AsignacionManager.obtenerDatosAsignaciones();
    }

    /**
     * Limpiar asignaciones
     */
    function limpiarAsignaciones() {
        AsignacionManager.limpiarAsignaciones();
        UIRenderer.actualizarTablaAsignaciones();
        UIRenderer.actualizarResumenAsignaciones();
        UIRenderer.actualizarVisibilidadSeccionesResumen();
    }

    /**
     * Cargar asignaciones previas
     */
    function cargarAsignacionesPrevias(datos) {
        AsignacionManager.cargarAsignacionesPrevias(datos);
        UIRenderer.actualizarTablaAsignaciones();
        UIRenderer.actualizarResumenAsignaciones();
    }

    /**
     * API Pública del módulo
     */
    return {
        init: init,
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
        cargarAsignacionesPrevias
    };
})();

// Inicializar cuando el DOM esté listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        // Esperar un poco más para asegurar que todos los módulos estén completamente cargados
        setTimeout(() => {
            window.ColoresPorTalla.init();
        }, 100);
    });
} else {
    // El DOM ya está cargado
    setTimeout(() => {
        window.ColoresPorTalla.init();
    }, 100);
}
