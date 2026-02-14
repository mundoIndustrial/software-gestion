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
        // console.log('[ColoresPorTalla]  Iniciando módulo ColoresPorTalla...');
        
        // Verificar que todos los módulos estén disponibles
        if (!window.StateManager || !window.AsignacionManager || 
            !window.WizardManager || !window.UIRenderer) {
            // console.error('[ColoresPorTalla]  Faltan módulos dependientes');
            // console.error('[ColoresPorTalla] Disponibles:', {
            //     StateManager: !!window.StateManager,
            //     AsignacionManager: !!window.AsignacionManager,
            //     WizardManager: !!window.WizardManager,
            //     UIRenderer: !!window.UIRenderer
            // });
            return false;
        }
        
        // console.log('[ColoresPorTalla]  Todos los módulos están disponibles');
        
        // Configurar eventos globales solo UNA VEZ
        if (!eventosConfigurados) {
            configurarEventosGlobales();
            eventosConfigurados = true;
            // console.log('[ColoresPorTalla]  Eventos globales configurados (primera vez)');
        } else {
            // console.log('[ColoresPorTalla] ⏭️ Eventos ya configurados, saltando...');
        }
        
        // Actualizar vistas iniciales
        actualizarVistasIniciales();
        // console.log('[ColoresPorTalla]  Vistas iniciales actualizadas');
        
        // console.log('[ColoresPorTalla]  Módulo ColoresPorTalla inicializado exitosamente');
        return true;
    }

    /**
     * Configurar eventos globales
     */
    function configurarEventosGlobales() {
        // console.log('[ColoresPorTalla] 🔹 Configurando eventos globales...');
        
        // Botones principales
        const btnAsignarColores = document.getElementById('btn-asignar-colores-tallas');
        const btnCancelarWizard = document.getElementById('btn-cancelar-wizard');
        const btnGuardarAsignacion = document.getElementById('btn-guardar-asignacion');
        const wzdBtnSiguiente = document.getElementById('wzd-btn-siguiente');
        const wzdBtnAtras = document.getElementById('wzd-btn-atras');
        
        // console.log('[ColoresPorTalla] 🔸 Botones encontrados:', {
        //     btnAsignarColores: !!btnAsignarColores,
        //     btnCancelarWizard: !!btnCancelarWizard,
        //     btnGuardarAsignacion: !!btnGuardarAsignacion,
        //     wzdBtnSiguiente: !!wzdBtnSiguiente,
        //     wzdBtnAtras: !!wzdBtnAtras
        // });
        
        // Remover listeners existentes para evitar duplicados
        if (btnAsignarColores && !btnAsignarColores.dataset.listenerConfigured) {
            btnAsignarColores.addEventListener('click', toggleVistaAsignacion);
            btnAsignarColores.dataset.listenerConfigured = 'true';
            // console.log('[ColoresPorTalla]  Event listener agregado a btn-asignar-colores-tallas');
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
            // console.log('[ColoresPorTalla]  Event listener agregado a btn-cancelar-wizard');
        }
        
        if (btnGuardarAsignacion && !btnGuardarAsignacion.dataset.listenerConfigured) {
            btnGuardarAsignacion.addEventListener('click', () => {
                wizardGuardarAsignacion();
                // Establecer bandera para evitar inicialización automática del wizard
                window.evitarInicializacionWizard = true;
                // Después de guardar, cerrar la vista del wizard
                setTimeout(() => {
                    toggleVistaAsignacion();
                }, 500);
            });
            btnGuardarAsignacion.dataset.listenerConfigured = 'true';
            // console.log('[ColoresPorTalla]  Event listener agregado a btn-guardar-asignacion');
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
            // console.log('[ColoresPorTalla]  Event listener agregado a wzd-btn-siguiente');
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
            // console.log('[ColoresPorTalla]  Event listener agregado a wzd-btn-atras');
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
        
        // console.log('[ColoresPorTalla]  Eventos globales configurados');
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
            console.log('[ColoresPorTalla]  ACCIÓN: Volviendo a tabla de telas');
            
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
            
            // Asegurar que la tabla de telas sea visible independientemente de la sección de tallas
            setTimeout(() => {
                const tbodyTelas = document.getElementById('tbody-telas');
                if (tbodyTelas) {
                    const tabla = tbodyTelas.closest('table');
                    if (tabla) {
                        tabla.style.display = 'table';
                        console.log('[ColoresPorTalla]  Tabla de telas forzada a ser visible');
                    }
                }
            }, 100);
            
            console.log('[ColoresPorTalla]  vistaTablaTelas.style.display = block');
            console.log('[ColoresPorTalla]  vistaAsignacion.style.display = none');
            console.log('[ColoresPorTalla]  Volviendo a vista de Tabla de Telas');
            
        } else {
            // Validar que haya una tela seleccionada antes de abrir asignación
            const telas = window.telasCreacion || [];
            if (telas.length === 0) {
                console.warn('[ColoresPorTalla]  ⚠️  No hay telas seleccionadas');
                mostrarModalSinTela();
                return;
            }
            
            // Abriendo vista de asignación
            console.log('[ColoresTalla]  ACCIÓN: Abriendo vista de Asignación de Colores');
            
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
            
            // Resetear wizard y mostrar paso inicial SOLO si no viene de guardar asignaciones
            if (window.WizardManager && !window.evitarInicializacionWizard) {
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
            } else if (window.evitarInicializacionWizard) {
                console.log('[toggleVistaAsignacion] 🚫 Evitando inicialización automática del wizard');
                // Limpiar la bandera después de usarla
                delete window.evitarInicializacionWizard;
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
        
        console.log('[mostrarSelectoreTelasSiNecesario]  Múltiples telas - mostrando selector');
        
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
            
            console.log('[actualizarTallasDisponibles]  Tallas agregadas:', todasLasTallas.length);
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
            
            // Disparar evento para actualizar tarjeta de prenda-card-readonly si existe
            const tarjetaPrenda = document.querySelector('.prenda-card-readonly');
            if (tarjetaPrenda) {
                const prendaIndex = tarjetaPrenda.getAttribute('data-prenda-index');
                console.log('[guardarAsignacionColores]  Disparando evento de actualización para prenda', prendaIndex);
                
                const evento = new CustomEvent('asignacionesActualizadas', {
                    detail: {
                        asignaciones: StateManager.getAsignaciones(),
                        prendaIndex: prendaIndex ? parseInt(prendaIndex) : null
                    }
                });
                document.dispatchEvent(evento);
            }
            
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
        
        console.log('[wizardGuardarAsignacion]  Estado actual:', { genero, tallas, tipo, telaDelWizard });
        
        if (!genero || !tallas || tallas.length === 0) {
            console.error('[wizardGuardarAsignacion]  Datos incompletos del wizard');
            alert('Por favor complete todos los pasos del wizard antes de guardar');
            return;
        }
        
        // Obtener inputs de color con la clase correcta que usa UIRenderer
        const inputsColor = document.querySelectorAll('.color-input-wizard');
        console.log('[wizardGuardarAsignacion]  Inputs de color encontrados:', inputsColor.length);
        
        if (inputsColor.length === 0) {
            console.error('[wizardGuardarAsignacion]  No se encontraron inputs de color');
            alert('Error: No se encontraron campos de color. Por favor intente nuevamente.');
            return;
        }
        
        // Procesar cada input de color agrupando por talla
        const asignacionesAgrupadas = {};
        let totalUnidades = 0;
        
        inputsColor.forEach((colorInput, index) => {
            // UIRenderer guarda la talla en dataset.talla
            const talla = colorInput.dataset.talla;
            
            if (!talla) {
                console.warn(`[wizardGuardarAsignacion]  Input #${index} sin dataset.talla`);
                return;
            }
            
            // Obtener el input de cantidad que está en la misma fila (hermano siguiente)
            const fila = colorInput.parentElement;
            const cantidadInput = fila ? fila.querySelector('.cantidad-input-wizard') : null;
            
            const color = colorInput.value.trim().toUpperCase();
            const cantidad = cantidadInput ? parseInt(cantidadInput.value) || 0 : 0;
            
            if (color && cantidad > 0) {
                // Inicializar array para esta talla si no existe
                if (!asignacionesAgrupadas[talla]) {
                    asignacionesAgrupadas[talla] = [];
                }
                
                // Guardar con propiedad 'nombre' para ser consistente con UIRenderer
                asignacionesAgrupadas[talla].push({ nombre: color, cantidad });
                totalUnidades += cantidad;
                console.log(`[wizardGuardarAsignacion]  Agregado: ${talla} - ${color} x${cantidad}`);
            }
        });
        
        console.log('[wizardGuardarAsignacion]  Asignaciones agrupadas:', asignacionesAgrupadas);
        
        // Guardar en StateManager usando el método correcto para múltiples tallas
        const resultado = AsignacionManager.guardarAsignacionesMultiples(genero, tallas, tipo, telaDelWizard, asignacionesAgrupadas);
        
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
            
            // Actualizar tarjeta de género con los colores asignados SOLO si existen las tarjetas
            const containerTarjetas = document.getElementById('tarjetas-generos-container');
            if (containerTarjetas) {
                console.log('[wizardGuardarAsignacion]  Actualizando tarjeta de género con colores...');
                actualizarTarjetaGeneroConColores(genero);
            } else {
                console.log('[wizardGuardarAsignacion]  No hay tarjetas de género en este modal (modal de creación)');
            }
            
            // Disparar evento para actualizar tarjeta de prenda-card-readonly si existe
            setTimeout(() => {
                const tarjetaPrenda = document.querySelector('.prenda-card-readonly');
                if (tarjetaPrenda) {
                    const prendaIndex = tarjetaPrenda.getAttribute('data-prenda-index');
                    console.log('[wizardGuardarAsignacion]  Disparando evento de actualización para prenda', prendaIndex);
                    
                    const evento = new CustomEvent('asignacionesActualizadas', {
                        detail: {
                            asignaciones: StateManager.getAsignaciones(),
                            prendaIndex: prendaIndex ? parseInt(prendaIndex) : null
                        }
                    });
                    document.dispatchEvent(evento);
                }
                
                // NOTA: No llamar a toggleVistaAsignacion aquí porque 
                // ya se maneja en el event listener del botón guardar
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
            // Actualizar la tarjeta de género cuando se elimina una asignación
            actualizarTarjetaGeneroConColores(genero);
            // Actualizar la tarjeta de prenda-card-readonly
            actualizarTarjetaPrendaReadOnly();
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
     * Actualizar tarjeta de género con colores asignados
     * Muestra los colores debajo de cada talla en la tarjeta del género
     */
    function actualizarTarjetaGeneroConColores(genero) {
        console.log('[actualizarTarjetaGeneroConColores]  Actualizando tarjeta de', genero);
        
        const container = document.getElementById('tarjetas-generos-container');
        if (!container) {
            console.warn('[actualizarTarjetaGeneroConColores]  No se encontró contenedor de tarjetas');
            return;
        }
        
        // Encontrar la tarjeta del género
        const tarjeta = container.querySelector(`[data-genero="${genero}"]`);
        if (!tarjeta) {
            console.warn('[actualizarTarjetaGeneroConColores]  No se encontró tarjeta para género:', genero);
            return;
        }
        
        // Obtener asignaciones del StateManager
        const asignaciones = StateManager.getAsignaciones();
        console.log('[actualizarTarjetaGeneroConColores]  Asignaciones totales:', asignaciones);
        
        // Encontrar el grid de cantidades
        const gridCantidades = tarjeta.querySelector('[style*="grid-template-columns"]');
        if (!gridCantidades) {
            console.warn('[actualizarTarjetaGeneroConColores]  No se encontró grid de cantidades');
            return;
        }
        
        // Para cada itemDiv de talla en el grid
        const itemsDivs = gridCantidades.querySelectorAll('div:has(> input[type="number"])');
        console.log('[actualizarTarjetaGeneroConColores]  Items de talla encontrados:', itemsDivs.length);
        
        itemsDivs.forEach((itemDiv) => {
            // Obtener el label de talla
            const label = itemDiv.querySelector('label');
            if (!label) return;
            
            const talla = label.textContent.trim();
            console.log('[actualizarTarjetaGeneroConColores]  Procesando talla:', talla);
            
            // Limpiar colores anteriores
            const coloresAnteriores = itemDiv.querySelector('[data-colores-asignados]');
            if (coloresAnteriores) {
                coloresAnteriores.remove();
            }
            
            // Buscar asignación para esta talla y género
            const claveBuscada = Object.keys(asignaciones).find(clave => {
                const asignacion = asignaciones[clave];
                return asignacion.genero === genero && asignacion.talla === talla;
            });
            
            if (claveBuscada) {
                const asignacion = asignaciones[claveBuscada];
                console.log('[actualizarTarjetaGeneroConColores]  Asignación encontrada para', talla, ':', asignacion);
                
                // Crear contenedor de colores
                const coloresDiv = document.createElement('div');
                coloresDiv.setAttribute('data-colores-asignados', 'true');
                coloresDiv.style.cssText = `
                    margin-top: 0.75rem;
                    padding: 0.5rem;
                    background: #f9fafb;
                    border-radius: 4px;
                    font-size: 0.75rem;
                    border-left: 3px solid #0066cc;
                `;
                
                // Crear título "Colores"
                const tituloColores = document.createElement('div');
                tituloColores.style.cssText = 'font-weight: 600; color: #374151; margin-bottom: 0.35rem;';
                tituloColores.textContent = ' Colores:';
                coloresDiv.appendChild(tituloColores);
                
                // Agregar cada color
                if (asignacion.colores && asignacion.colores.length > 0) {
                    asignacion.colores.forEach((color) => {
                        const colorItem = document.createElement('div');
                        colorItem.style.cssText = 'color: #6b7280; margin: 0.25rem 0; display: flex; align-items: center; gap: 0.35rem;';
                        
                        const colorName = color.nombre || color.color || 'Sin nombre';
                        const cantidad = color.cantidad || 0;
                        
                        colorItem.innerHTML = `
                            <span style="display: inline-block; width: 8px; height: 8px; background: #0066cc; border-radius: 50%;"></span>
                            ${colorName} <span style="color: #9ca3af; font-weight: 500;">x${cantidad}</span>
                        `;
                        coloresDiv.appendChild(colorItem);
                    });
                } else {
                    const sinColores = document.createElement('div');
                    sinColores.style.cssText = 'color: #9ca3af; font-style: italic;';
                    sinColores.textContent = 'Sin colores asignados';
                    coloresDiv.appendChild(sinColores);
                }
                
                // Agregar al itemDiv después del input
                itemDiv.appendChild(coloresDiv);
                console.log('[actualizarTarjetaGeneroConColores]  ✓ Colores agregados a', talla);
            } else {
                console.log('[actualizarTarjetaGeneroConColores]  No hay asignación para:', talla);
            }
        });
        
        console.log('[actualizarTarjetaGeneroConColores]  Tarjeta actualizada completamente');
    }

    /**
     * Actualizar tarjeta de prenda-card-readonly
     * Reconstruye la sección de tallas mostrando los colores asignados
     */
    function actualizarTarjetaPrendaReadOnly() {
        console.log('[actualizarTarjetaPrendaReadOnly]  Iniciando actualización de tarjeta de prenda');
        
        try {
            // Buscar la tarjeta de prenda-card-readonly visible
            const tarjetaPrenda = document.querySelector('.prenda-card-readonly');
            if (!tarjetaPrenda) {
                console.log('[actualizarTarjetaPrendaReadOnly]  No hay tarjeta visible');
                return;
            }
            
            // Obtener el índice de la prenda
            const prendaIndex = tarjetaPrenda.getAttribute('data-prenda-index');
            console.log('[actualizarTarjetaPrendaReadOnly]  Índice de prenda:', prendaIndex);
            
            // Buscar la sección de tallas
            const seccionTallas = tarjetaPrenda.querySelector('.tallas-y-cantidades-section');
            if (!seccionTallas) {
                console.log('[actualizarTarjetaPrendaReadOnly]  No se encontró sección de tallas');
                return;
            }
            
            // Obtener los datos actuales del StateManager (que ya contiene las asignaciones)
            // Para esto necesitaremos acceso a los datos originales de la prenda
            // Por ahora, simplemente reconstruimos el contenido del TallasBuilder
            
            // Si hay datos de prenda guardados, actualizar la sección de tallas
            // Para esto podríamos usar PrendaCardService.generar() pero necesitaríamos la prenda original
            // Una alternativa es actualizar solo la sección visible
            
            // Actualizar el contenido de la sección expandible
            const seccionContent = seccionTallas.querySelector('.tallas-y-cantidades-content');
            if (seccionContent && window.TallasBuilder) {
                // Necesitamos los datos de prenda. Los obtendríamos del contexto que inicializó esta modal
                // Por ahora, agregaremos un observador para que el TallasBuilder se reconstruya cuando se solicite
                console.log('[actualizarTarjetaPrendaReadOnly]  Sección de contenido encontrada');
                
                // Disparar evento personalizado para que otros módulos sepan que las asignaciones cambiaron
                const evento = new CustomEvent('asignacionesActualizadas', {
                    detail: {
                        asignaciones: StateManager.getAsignaciones(),
                        prendaIndex: prendaIndex
                    }
                });
                document.dispatchEvent(evento);
                console.log('[actualizarTarjetaPrendaReadOnly]  Evento de actualización de asignaciones disparado');
            }
        } catch (error) {
            console.error('[actualizarTarjetaPrendaReadOnly]  Error:', error);
        }
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
     * Mostrar modal cuando no hay tela seleccionada
     */
    function mostrarModalSinTela() {
        console.log('[mostrarModalSinTela]  Mostrando modal de advertencia');
        
        // Crear el modal dinámicamente
        let modal = document.getElementById('modal-sin-tela-seleccionada');
        
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'modal-sin-tela-seleccionada';
            modal.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.7);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 999999 !important;
            `;
            
            const contenido = document.createElement('div');
            contenido.style.cssText = `
                background: white;
                padding: 2rem;
                border-radius: 8px;
                max-width: 500px;
                width: 90%;
                box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
                text-align: center;
            `;
            
            contenido.innerHTML = `
                <div style="margin-bottom: 1.5rem; font-size: 2.5rem;">⚠️</div>
                <h2 style="margin: 0 0 1rem 0; color: #333; font-size: 1.5rem; font-weight: 600;">
                    Selecciona una Tela Primero
                </h2>
                <p style="margin: 0 0 2rem 0; color: #666; font-size: 1rem; line-height: 1.5;">
                    Debes seleccionar una tela primero para aplicar los colores a la talla.
                </p>
                <button id="btn-cerrar-modal-sin-tela" type="button" style="
                    background: #3b82f6;
                    color: white;
                    border: none;
                    padding: 0.75rem 2rem;
                    border-radius: 6px;
                    font-size: 1rem;
                    font-weight: 500;
                    cursor: pointer;
                    transition: background 0.2s ease;
                ">
                    Entendido
                </button>
            `;
            
            modal.appendChild(contenido);
            document.body.appendChild(modal);
            
            // Configurar listener para cerrar el modal
            document.getElementById('btn-cerrar-modal-sin-tela').addEventListener('click', () => {
                modal.style.display = 'none';
            });
            
            // Cerrar al clickear fuera del modal
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.style.display = 'none';
                }
            });
        }
        
        // Mostrar el modal
        modal.style.display = 'flex';
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
        cargarAsignacionesPrevias,
        actualizarTarjetaGeneroConColores
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
