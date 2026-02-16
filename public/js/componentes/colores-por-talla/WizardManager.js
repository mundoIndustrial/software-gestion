/**
 * Módulo: WizardManager
 * Gestiona la navegación y lógica del wizard de 3 pasos
 */

window.WizardManager = (function() {
    'use strict';

    // Verificar solo StateManager como dependencia
    if (!window.StateManager) {
        console.error('[WizardManager]  StateManager no está disponible');
        return {};
    }

    // Variables privadas para controlar el flujo
    const flujoInterno = {
        generoProcesado: null,  // Género del último paso 2 procesado
        generoCambiado: false,  // Bandera si el género cambió en Paso 1
        generoSeleccionandoEnPaso1: false,  // Flag para recordar selección en PASO 1
        generoAnterior: null  // 🆕 Guardar género anterior para restaurar al volver
    };

    return {
        /**
         * INICIALIZACIÓN: Configurar listeners directamente sin depender de WizardBootstrap
         */
        inicializarListeners() {
            console.log('[WizardManager] 🔌 Inicializando listeners directamente...');
            
            const btnSiguiente = document.getElementById('wzd-btn-siguiente');
            const btnAtras = document.getElementById('wzd-btn-atras');
            const btnGuardar = document.getElementById('btn-guardar-asignacion');
            const btnCancelar = document.getElementById('btn-cancelar-wizard');
            
            // 🛡️ Remover listeners anteriores usando referencias guardadas (previene acumulación)
            if (this._storedHandlers) {
                if (btnSiguiente && this._storedHandlers.siguiente) {
                    btnSiguiente.removeEventListener('click', this._storedHandlers.siguiente, false);
                }
                if (btnAtras && this._storedHandlers.atras) {
                    btnAtras.removeEventListener('click', this._storedHandlers.atras, false);
                }
                if (btnGuardar && this._storedHandlers.guardar) {
                    btnGuardar.removeEventListener('click', this._storedHandlers.guardar, false);
                }
                if (btnCancelar && this._storedHandlers.cancelar) {
                    btnCancelar.removeEventListener('click', this._storedHandlers.cancelar, false);
                }
                console.log('[WizardManager] 🧹 Listeners anteriores removidos');
            }
            
            // Crear nuevos handlers y guardar referencias
            this._storedHandlers = {
                siguiente: (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('[WizardManager] 🔘 CLICK DIRECTO en botón Siguiente');
                    this.pasoSiguiente();
                },
                atras: (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('[WizardManager] 🔘 CLICK DIRECTO en botón Atrás');
                    this.pasoAnterior();
                },
                guardar: (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('[WizardManager] 🔘 CLICK DIRECTO en botón Guardar');
                    
                    const btnG = document.getElementById('btn-guardar-asignacion');
                    const htmlOriginal = btnG ? btnG.innerHTML : '';
                    
                    // Mostrar estado de carga
                    if (btnG) {
                        btnG.disabled = true;
                        btnG.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true" style="margin-right: 0.5rem;"></span><span>Guardando...</span>';
                        btnG.style.opacity = '0.8';
                    }
                    
                    try {
                        let resultado = false;
                        if (window.ColoresPorTalla && typeof window.ColoresPorTalla.wizardGuardarAsignacion === 'function') {
                            resultado = window.ColoresPorTalla.wizardGuardarAsignacion();
                        } else if (window.ColoresPorTalla && typeof window.ColoresPorTalla.guardarAsignacion === 'function') {
                            resultado = window.ColoresPorTalla.guardarAsignacion();
                        }
                        
                        if (resultado) {
                            // Mostrar éxito
                            if (btnG) {
                                btnG.innerHTML = '<span class="material-symbols-rounded" style="font-size: 1.2rem; margin-right: 0.5rem;">check_circle</span><span>¡Guardado!</span>';
                                btnG.style.opacity = '1';
                            }
                            setTimeout(() => {
                                if (btnG) {
                                    btnG.innerHTML = htmlOriginal;
                                    btnG.disabled = false;
                                    btnG.style.opacity = '1';
                                }
                            }, 1000);
                        } else {
                            // Restaurar si falla
                            if (btnG) {
                                btnG.innerHTML = htmlOriginal;
                                btnG.disabled = false;
                                btnG.style.opacity = '1';
                            }
                        }
                    } catch (err) {
                        console.error('[WizardManager] Error al guardar:', err);
                        if (btnG) {
                            btnG.innerHTML = htmlOriginal;
                            btnG.disabled = false;
                            btnG.style.opacity = '1';
                        }
                    }
                },
                cancelar: (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('[WizardManager] 🔘 CLICK DIRECTO en botón Cancelar');
                    if (window.ColoresPorTalla && typeof window.ColoresPorTalla.cerrarWizard === 'function') {
                        window.ColoresPorTalla.cerrarWizard();
                    }
                }
            };
            
            if (btnSiguiente) {
                btnSiguiente.onclick = null;
                btnSiguiente.addEventListener('click', this._storedHandlers.siguiente, false);
                console.log('[WizardManager] ✅ Listener directo agregado a botón Siguiente');
            } else {
                console.error('[WizardManager] ❌ Botón Siguiente (wzd-btn-siguiente) NO ENCONTRADO');
            }
            
            if (btnAtras) {
                btnAtras.onclick = null;
                btnAtras.addEventListener('click', this._storedHandlers.atras, false);
                console.log('[WizardManager] ✅ Listener directo agregado a botón Atrás');
            } else {
                console.error('[WizardManager] ❌ Botón Atrás (wzd-btn-atras) NO ENCONTRADO');
            }
            
            if (btnGuardar) {
                btnGuardar.onclick = null;
                btnGuardar.addEventListener('click', this._storedHandlers.guardar, false);
                console.log('[WizardManager] ✅ Listener directo agregado a botón Guardar');
            }
            
            if (btnCancelar) {
                btnCancelar.onclick = null;
                btnCancelar.addEventListener('click', this._storedHandlers.cancelar, false);
                console.log('[WizardManager] ✅ Listener directo agregado a botón Cancelar');
            }
        },

        /**
         * Resetear el flujo interno (para cuando se reabre el wizard en modo edición)
         */
        resetearFlujo() {
            flujoInterno.generoProcesado = null;
            flujoInterno.generoCambiado = false;
            console.log('[WizardManager] 🔄 Flujo interno reseteado para nueva sesión');
        },

        /**
         * PASO 0 (opcional): Seleccionar tela (solo si hay múltiples)
         */
        seleccionarTela(tela) {
            console.log('[WizardManager]  Tela seleccionada:', tela);
            StateManager.setTelaSeleccionada(tela);
            
            // Mostrar botón siguiente
            const btnSiguiente = document.getElementById('wzd-btn-siguiente');
            if (btnSiguiente) {
                btnSiguiente.disabled = false;
                btnSiguiente.style.display = 'flex';
                console.log('[WizardManager]  Botón Siguiente mostrado');
            }
        },

        /**
         * PASO 1: Seleccionar género (detecta cambios)
         */
        seleccionarGenero(genero) {
            const generoActual = StateManager.getGeneroSeleccionado();
            const generesCambian = generoActual !== genero;
            
            // 🆕 Guardar género anterior antes de cambiar
            flujoInterno.generoAnterior = generoActual;
            
            StateManager.setGeneroSeleccionado(genero);
            flujoInterno.generoCambiado = generesCambian;
            flujoInterno.generoSeleccionandoEnPaso1 = true; // 🚩 Marcar que se seleccionó género en PASO 1
            
            console.log('[WizardManager] Género seleccionado:', genero, '| Cambió:', generesCambian);
            
            // Mostrar y habilitar el botón siguiente
            const btnSiguiente = document.getElementById('wzd-btn-siguiente');
            if (btnSiguiente) {
                btnSiguiente.disabled = false;
                btnSiguiente.style.display = 'flex';
                btnSiguiente.style.opacity = '1';
                btnSiguiente.style.cursor = 'pointer';
                btnSiguiente.style.pointerEvents = 'auto';
                btnSiguiente.title = '';
                console.log('[WizardManager] ✅ Botón Siguiente HABILITADO explícitamente después de seleccionar género');
            } else {
                console.error('[WizardManager] Botón wzd-btn-siguiente no encontrado');
            }
            
            // Resaltar el botón seleccionado
            const generoBtns = document.querySelectorAll('.wizard-genero-btn');
            generoBtns.forEach(btn => {
                if (btn.dataset.genero === genero) {
                    btn.style.background = '#eff6ff';
                    btn.style.borderColor = '#3b82f6';
                    btn.style.color = '#1f2937';
                    btn.style.fontWeight = '600';
                    console.log('[WizardManager] Botón', genero, 'resaltado');
                } else {
                    btn.style.background = 'white';
                    btn.style.borderColor = '#d1d5db';
                    btn.style.color = '#374151';
                    btn.style.fontWeight = '500';
                }
            });
        },

        /**
         * Métodos privados de validación (Arquitectura cleaner)
         */
        _validarPaso0() {
            const tela = StateManager.getTelaSeleccionada();
            if (!tela) {
                console.log('[WizardManager] ⏸️ PASO 0: Sin tela seleccionada');
                return false;
            }
            console.log('[WizardManager] ✅ PASO 0: Tela validada -', tela);
            return true;
        },

        _validarPaso1() {
            const genero = StateManager.getGeneroSeleccionado();
            const tallas = StateManager.getTallasSeleccionadas();
            const hayGenero = !!genero;
            const hayTallas = tallas && tallas.length > 0;
            
            console.log('[WizardManager] PASO 1: Validando -', { hayGenero, hayTallas });
            return { hayGenero, hayTallas };
        },

        _validarPaso2() {
            const tallas = StateManager.getTallasSeleccionadas();
            const hayTallas = tallas && tallas.length > 0;
            console.log('[WizardManager] PASO 2: Validando tallas -', { tallasCount: tallas?.length || 0, hayTallas });
            return hayTallas;
        },

        /**
         * PASO 2: Ir al siguiente paso (REFACTORIZADO - Arquitectura limpia)
         */
        pasoSiguiente() {
            // 🛡️ GUARD: Prevenir múltiples llamadas rápidas (por listeners duplicados)
            if (this._navegacionEnProgreso) {
                console.log('[WizardManager] ⏳ Navegación ya en progreso, ignorando llamada duplicada a pasoSiguiente()');
                return false;
            }
            this._navegacionEnProgreso = true;
            setTimeout(() => { this._navegacionEnProgreso = false; }, 200);

            console.log('[WizardManager]  pasoSiguiente() llamado...');
            const pasoActual = StateManager.getPasoActual();
            
            // 🔧 VERIFICACIÓN: Asegurar que los botones estén en estado usable
            this._verificarYRepararBotones();
            
            switch (pasoActual) {
                case 0:
                    return this._procesarPaso0();
                case 1:
                    return this._procesarPaso1();
                case 2:
                    return this._procesarPaso2();
                case 3:
                    return this._procesarPaso3();
                default:
                    console.error('[WizardManager] Paso inválido:', pasoActual);
                    alert('Error: Estado del wizard inválido. Por favor recarga la página.');
                    return false;
            }
        },
        
        /**
         * VERIFICACIÓN: Asegurar que botones no estén bloqueados
         */
        _verificarYRepararBotones() {
            const btnSiguiente = document.getElementById('wzd-btn-siguiente');
            if (btnSiguiente) {
                btnSiguiente.style.pointerEvents = 'auto';
                btnSiguiente.style.opacity = '1';
                btnSiguiente.style.cursor = 'pointer';
                btnSiguiente.disabled = false;
            }
            
            const btnAtras = document.getElementById('wzd-btn-atras');
            if (btnAtras) {
                btnAtras.style.pointerEvents = 'auto';
                btnAtras.style.opacity = '1';
                btnAtras.style.cursor = 'pointer';
                btnAtras.disabled = false;
            }
        },

        /**
         * 🎯 ALERTA: Mostrar cuando se restaura género automáticamente
         */
        _mostrarAlertaGeneroRestaurado(genero) {
            // Buscar si existe un elemento para mostrar alertas
            const alertContainer = document.getElementById('wizard-alert-container');
            if (!alertContainer) return;

            // Crear HTML de alerta
            const alertHTML = `
                <div class="alert alert-info alert-dismissible fade show" style="margin-bottom: 15px;">
                    <strong>✓ Género Restaurado:</strong> Se restauró automáticamente el género <strong>${genero.toUpperCase()}</strong> 
                    para continuar con las tallas acumuladas. Si deseas cambiar de género, haz clic en el botón correspondiente.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `;

            alertContainer.innerHTML = alertHTML;
            console.log('[WizardManager] 📢 Alerta mostrada: Género restaurado -', genero);
        },

        _procesarPaso0() {
            console.log('[WizardManager] ↪️ PASO 0 - Seleccionar tela...');
            if (!this._validarPaso0()) {
                console.warn('[WizardManager] No hay tela seleccionada');
                alert('Por favor selecciona una tela antes de continuar');
                return false;
            }
            console.log('[WizardManager] ➜ Avanzando a PASO 1...');
            this.irPaso(1);
            return true;
        },

        _procesarPaso1() {
            console.log('[WizardManager] ↪️ PASO 1 - Verificar género seleccionado...');
            const generoActual = StateManager.getGeneroSeleccionado();
            
            if (!generoActual) {
                console.log('[WizardManager] ⏸️ Sin género seleccionado. Esperando selección...');
                return false;
            }
            
            // Siempre ir a paso 2 (seleccionar tallas)
            console.log('[WizardManager] ➜ Género seleccionado:', generoActual, '→ PASO 2 (tallas)');
            this.irPaso(2);
            return true;
        },

        _procesarPaso2() {
            console.log('[WizardManager] ↪️ PASO 2 - Validar tallas...');
            const tieneTallas = this._validarPaso2();
            
            if (!tieneTallas) {
                console.log('[WizardManager] ⏸️ Sin tallas seleccionadas. Esperando selección...');
                return false;
            }
            
            console.log('[WizardManager] ✅ Tallas validadas → PASO 3 (asignar colores)');
            this.irPaso(3);
            return true;
        },

        _procesarPaso3() {
            console.log('[WizardManager] ↪️ PASO 3 - Último paso (asignar colores)');
            console.log('[WizardManager] ⏸️ Usa botón "Guardar Asignación" para finalizar');
            return false;
        },

        /**
         * Ir atrás en el wizard
         */
        pasoAnterior() {
            // 🛡️ GUARD: Prevenir múltiples llamadas rápidas
            if (this._navegacionEnProgreso) {
                console.log('[WizardManager] ⏳ Navegación ya en progreso, ignorando llamada duplicada');
                return;
            }
            this._navegacionEnProgreso = true;
            setTimeout(() => { this._navegacionEnProgreso = false; }, 200);

            try {
                const pasoActual = StateManager.getPasoActual();
                const telas = window.telasCreacion || [];
                const hayMultiplesTelas = telas.length > 1;
                
                console.log('[WizardManager] pasoAnterior() - Paso actual:', pasoActual);
                
                if (pasoActual <= 0) {
                    console.warn('[WizardManager] Ya estás en el primer paso');
                    return;
                }
                
                let pasoPrevio = pasoActual - 1;
                
                // Si hay una sola tela, saltar paso 0
                if (!hayMultiplesTelas && pasoPrevio === 0) {
                    console.log('[WizardManager] Una sola tela, no ir a paso 0. Cerrando wizard.');
                    // Cerrar el modal
                    if (typeof cerrarModalAsignarColores === 'function') {
                        cerrarModalAsignarColores();
                    }
                    return;
                }
                
                console.log(`[WizardManager] ⬅️ Navegando de paso ${pasoActual} a paso ${pasoPrevio}`);
                this.irPaso(pasoPrevio);
            } catch (error) {
                console.error('[WizardManager] Error en pasoAnterior():', error);
            }
        },

        /**
         * Obtener el número total de pasos (4 siempre)
         */
        obtenerTotalPasos() {
            // Siempre devolvemos 4 porque los pasos son:
            // 0: Selección de tela (solo si hay múltiples telas)
            // 1: Género
            // 2: Tallas  
            // 3: Colores
            return 4;
        },

        /**
         * Inicializar el wizard y determinar paso inicial
         */
        inicializarWizard() {
            console.log('[WizardManager]  Inicializando wizard...');
            const telas = window.telasCreacion || [];
            const totalPasos = this.obtenerTotalPasos();
            
            console.log('[WizardManager]  Total de telas:', telas.length, '| Total pasos:', totalPasos);
            
            // Mostrar/ocultar indicador paso 0 y su contenedor
            const paso0Wrapper = document.getElementById('paso-0-wrapper');
            const paso0Linea = document.getElementById('paso-0-linea');
            
            if (telas.length > 1) {
                // FLUJO 2: Múltiples telas: mostrar paso 0 y empezar ahí
                console.log('[WizardManager]  🌊 FLUJO 2 - Múltiples telas detectadas - mostrar paso 0');
                if (paso0Wrapper) paso0Wrapper.style.display = 'block';
                if (paso0Linea) paso0Linea.style.display = 'block';
                this.irPaso(0);
            } else {
                // FLUJO 1: Una sola tela: ocultar paso 0 y empezar en paso 1
                console.log('[WizardManager]  🌊 FLUJO 1 - Una sola tela - ocultar paso 0, empezar en paso 1');
                if (paso0Wrapper) paso0Wrapper.style.display = 'none';
                if (paso0Linea) paso0Linea.style.display = 'none';
                
                // Auto-seleccionar la tela única
                if (telas.length === 1) {
                    const nombreTela = telas[0].tela_nombre || telas[0].nombre_tela || telas[0].tela;
                    StateManager.setTelaSeleccionada(nombreTela);
                    console.log('[WizardManager]  Tela única auto-seleccionada:', nombreTela);
                }
                
                // Forzar ir a Paso 1 (aunque el estado anterior sea Paso 0)
                console.log('[WizardManager]  Forzando transición a Paso 1 en FLUJO 1');
                this.irPaso(1);
            }
        },

        /**
         * Navegar a un paso específico (0, 1, 2 ó 3)
         * SIN VALIDACIONES BLOQUEANTES - siempre navega
         */
        irPaso(numeroPaso) {
            try {
                console.log('[WizardManager] 🎯 irPaso() llamado con:', numeroPaso);
                
                const totalPasos = this.obtenerTotalPasos();
                console.log('[WizardManager] Total de pasos disponibles:', totalPasos);
                
                // Validar paso
                if (numeroPaso < 0 || numeroPaso >= totalPasos) {
                    console.error('[WizardManager] Paso inválido:', numeroPaso, 'Total pasos:', totalPasos);
                    return;
                }
                
                console.log('[WizardManager] Paso validado, procediendo a cambiar...');
                
                // Ocultar todos los pasos
                console.log('[WizardManager] 🙈 Ocultando todos los pasos...');
                const todosLosPasos = ['wizard-paso-0', 'wizard-paso-1', 'wizard-paso-2', 'wizard-paso-3'];
                
                todosLosPasos.forEach(id => {
                    try {
                        const elemento = document.getElementById(id);
                        if (elemento) {
                            elemento.style.display = 'none';
                        }
                    } catch (e) {
                        console.error(`[WizardManager] Error ocultando paso ${id}:`, e);
                    }
                });
                
                // Mostrar el paso correcto
                const pasoElement = document.getElementById(`wizard-paso-${numeroPaso}`);
                if (pasoElement) {
                    console.log(`[WizardManager] 👁️ Mostrando paso: wizard-paso-${numeroPaso}`);
                    pasoElement.style.display = 'block';
                } else {
                    console.error(`[WizardManager] Elemento del paso ${numeroPaso} no encontrado!`);
                    return;
                }
                
                console.log('[WizardManager] Actualizando indicadores de progreso...');
                this.actualizarIndicadoresProgreso(numeroPaso);
                
                console.log('[WizardManager] Actualizando botones de navegación...');
                this.actualizarBotonesNavegacion(numeroPaso);
                
                // Si es paso 0, cargar las telas disponibles
                if (numeroPaso === 0) {
                    try {
                        console.log('[WizardManager] Cargando telas disponibles...');
                        this.cargarTelasDisponibles();
                    } catch (e) {
                        console.error('[WizardManager] Error cargando telas:', e);
                    }
                }
                
                // Si es paso 1, cargar los géneros (no hace falta, ya están)
                if (numeroPaso === 1) {
                    console.log('[WizardManager] 👥 Paso 1 - géneros ya cargados');
                }
                
                // Si es paso 2, cargar las tallas disponibles
                if (numeroPaso === 2) {
                    try {
                        console.log('[WizardManager] 📏 Cargando tallas para género...');
                        const generoSeleccionado = StateManager.getGeneroSeleccionado();
                        if (generoSeleccionado) {
                            window.WizardManager.cargarTallasParaGenero(generoSeleccionado);
                        } else {
                            console.error('[WizardManager] No hay género seleccionado para cargar tallas');
                        }
                    } catch (e) {
                        console.error('[WizardManager] Error cargando tallas:', e);
                    }
                }
                
                // Si es paso 3, cargar los colores disponibles
                if (numeroPaso === 3) {
                    try {
                        console.log('[WizardManager] Cargando colores para talla...');
                        this.cargarColoresParaTalla();
                    } catch (e) {
                        console.error('[WizardManager] Error cargando colores:', e);
                    }
                }
                
                console.log('[WizardManager] 💾 Guardando paso actual en StateManager...');
                StateManager.setPasoActual(numeroPaso);
                
                console.log(`[WizardManager] irPaso(${numeroPaso}) completado exitosamente`);
            } catch (error) {
                console.error('[WizardManager] Error crítico en irPaso():', error);
                console.error('[WizardManager] Stack trace:', error.stack);
            }
        },

        /**
         * Cargar telas disponibles para el paso 0
         */
        cargarTelasDisponibles() {
            try {
                const contenedor = document.getElementById('wizard-telas-selector');
                if (!contenedor) {
                    console.error('[WizardManager] wizard-telas-selector no encontrado');
                    return;
                }
                
                const telas = window.telasCreacion || [];
                console.log('[WizardManager] Cargando telas disponibles, total:', telas.length);
                
                if (telas.length <= 1) {
                    console.warn('[WizardManager] No hay múltiples telas para mostrar selector (Expected, paso 0 no debería estar visible)');
                    contenedor.innerHTML = '<div style="text-align: center; padding: 1rem; color: #9ca3af;">No hay múltiples telas disponibles</div>';
                    return;
                }
                
                contenedor.innerHTML = '';
                
                telas.forEach((tela, index) => {
                    const nombreTela = tela.tela_nombre || tela.nombre_tela || tela.tela || `Tela ${index + 1}`;
                    
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'wizard-tela-btn';
                    btn.style.padding = '1rem';
                    btn.style.border = '1px solid #d1d5db';
                    btn.style.background = 'white';
                    btn.style.borderRadius = '4px';
                    btn.style.cursor = 'pointer';
                    btn.style.fontWeight = '500';
                    btn.style.fontSize = '0.85rem';
                    btn.style.color = '#374151';
                    btn.style.transition = 'all 0.2s';
                    btn.style.textAlign = 'center';
                    btn.innerHTML = `
                        <div style="font-size: 0.75rem; color: #6b7280; margin-bottom: 0.5rem;">TELA ${index + 1}</div>
                        <div style="font-weight: 600; color: #111827;">${nombreTela}</div>
                    `;
                    
                    btn.addEventListener('click', () => {
                        try {
                            console.log('[WizardManager] 🎯 Tela seleccionada:', nombreTela);
                            this.seleccionarTela(nombreTela);
                            // Avanzar al siguiente paso
                            this.pasoSiguiente();
                        } catch (e) {
                            console.error('[WizardManager] Error en evento click de tela:', e);
                        }
                    });
                    
                    contenedor.appendChild(btn);
                });
                
                console.log('[WizardManager] Telas cargadas exitosamente');
            } catch (error) {
                console.error('[WizardManager] Error cargando telas disponibles:', error);
            }
        },

        /**
         * Actualizar indicadores de progreso visual
         */
        actualizarIndicadoresProgreso(pasoActual) {
            const totalPasos = this.obtenerTotalPasos();
            
            // Paso 0 (solo si tenemos 4 pasos)
            const paso0 = document.getElementById('paso-0-indicator');
            if (paso0) {
                if (pasoActual >= 0 && totalPasos > 3) {
                    paso0.style.background = '#3b82f6';
                    paso0.style.color = 'white';
                } else {
                    paso0.style.background = '#e5e7eb';
                    paso0.style.color = '#6b7280';
                }
            }
            
            // Paso 1
            const paso1 = document.getElementById('paso-1-indicator');
            if (paso1) {
                if (pasoActual >= 1) {
                    paso1.style.background = '#3b82f6';
                    paso1.style.color = 'white';
                } else {
                    paso1.style.background = '#e5e7eb';
                    paso1.style.color = '#6b7280';
                }
            }
            
            // Línea 0-1
            const linea0 = document.getElementById('paso-0-linea');
            if (linea0) {
                linea0.style.background = pasoActual >= 1 ? '#3b82f6' : '#d1d5db';
            }
            
            // Línea 1-2
            const linea1 = document.getElementById('paso-1-linea');
            if (linea1) {
                linea1.style.background = pasoActual >= 2 ? '#3b82f6' : '#d1d5db';
            }
            
            // Paso 2
            const paso2 = document.getElementById('paso-2-indicator');
            if (paso2) {
                if (pasoActual >= 2) {
                    paso2.style.background = '#3b82f6';
                    paso2.style.color = 'white';
                } else {
                    paso2.style.background = '#e5e7eb';
                    paso2.style.color = '#6b7280';
                }
            }
            
            // Línea 2-3
            const linea2 = document.getElementById('paso-2-linea');
            if (linea2) {
                linea2.style.background = pasoActual >= 3 ? '#3b82f6' : '#d1d5db';
            }
            
            // Paso 3
            const paso3 = document.getElementById('paso-3-indicator');
            if (paso3) {
                if (pasoActual >= 3) {
                    paso3.style.background = '#3b82f6';
                    paso3.style.color = 'white';
                } else {
                    paso3.style.background = '#e5e7eb';
                    paso3.style.color = '#6b7280';
                }
            }
        },

        /**
         * Actualizar visibilidad de botones de navegación
         */
        actualizarBotonesNavegacion(pasoActual) {
            const btnAtras = document.getElementById('wzd-btn-atras');
            const btnSiguiente = document.getElementById('wzd-btn-siguiente');
            const btnGuardar = document.getElementById('btn-guardar-asignacion');
            
            console.log('[WizardManager] Paso:', pasoActual);
            
            // PASO 0: Seleccionar Tela
            if (pasoActual === 0) {
                if (btnAtras) {
                    btnAtras.style.display = 'none';
                    console.log('[WizardManager] Botón Atrás oculto (paso 0)');
                }
                if (btnSiguiente) {
                    btnSiguiente.style.display = 'none';
                    console.log('[WizardManager] Botón Siguiente oculto (se presiona al seleccionar tela)');
                }
                if (btnGuardar) {
                    btnGuardar.style.display = 'none';
                    console.log('[WizardManager] Botón Guardar oculto');
                }
            }
            // PASO 1: Seleccionar Género
            else if (pasoActual === 1) {
                const telas = window.telasCreacion || [];
                const tienePaso0 = telas.length > 1;
                
                if (btnAtras) {
                    if (tienePaso0) {
                        btnAtras.style.display = 'flex';
                        btnAtras.style.alignItems = 'center';
                        btnAtras.style.justifyContent = 'center';
                        btnAtras.style.gap = '0.25rem';
                    } else {
                        btnAtras.style.display = 'flex';
                        btnAtras.style.alignItems = 'center';
                        btnAtras.style.justifyContent = 'center';
                        btnAtras.style.gap = '0.25rem';
                    }
                }
                if (btnSiguiente) {
                    btnSiguiente.style.display = 'flex';
                    btnSiguiente.style.alignItems = 'center';
                    btnSiguiente.style.justifyContent = 'center';
                    btnSiguiente.style.gap = '0.25rem';
                    // NUNCA bloquear - siempre habilitado
                    btnSiguiente.disabled = false;
                    btnSiguiente.style.opacity = '1';
                    btnSiguiente.style.cursor = 'pointer';
                    btnSiguiente.style.pointerEvents = 'auto';
                    btnSiguiente.title = '';
                }
                if (btnGuardar) {
                    btnGuardar.style.display = 'none';
                }
            }
            // PASO 2: Seleccionar Talla
            else if (pasoActual === 2) {
                if (btnAtras) {
                    btnAtras.style.display = 'flex';
                    btnAtras.style.alignItems = 'center';
                    btnAtras.style.justifyContent = 'center';
                    btnAtras.style.gap = '0.25rem';
                }
                if (btnSiguiente) {
                    btnSiguiente.style.display = 'flex';
                    btnSiguiente.style.alignItems = 'center';
                    btnSiguiente.style.justifyContent = 'center';
                    btnSiguiente.style.gap = '0.25rem';
                    // NUNCA bloquear con pointerEvents - solo cambiar opacidad visual
                    btnSiguiente.disabled = false;
                    btnSiguiente.style.opacity = '1';
                    btnSiguiente.style.cursor = 'pointer';
                    btnSiguiente.style.pointerEvents = 'auto';
                    btnSiguiente.title = '';
                }
                if (btnGuardar) {
                    btnGuardar.style.display = 'none';
                }
            }
            // PASO 3: Asignar Colores
            else if (pasoActual === 3) {
                if (btnAtras) {
                    btnAtras.style.display = 'flex';
                    btnAtras.style.alignItems = 'center';
                    btnAtras.style.justifyContent = 'center';
                    btnAtras.style.gap = '0.25rem';
                    console.log('[WizardManager] ✅ Botón Atrás VISIBLE en paso 3');
                }
                if (btnSiguiente) {
                    btnSiguiente.style.display = 'none';
                    console.log('[WizardManager] Botón Siguiente oculto en paso 3');
                }
                if (btnGuardar) {
                    btnGuardar.style.display = 'flex';
                    btnGuardar.style.alignItems = 'center';
                    btnGuardar.style.justifyContent = 'center';
                    btnGuardar.style.gap = '0.25rem';
                    btnGuardar.disabled = false;
                    console.log('[WizardManager] ✅ Botón Guardar VISIBLE en paso 3. Display:', btnGuardar.style.display);
                }
                
                console.log('[WizardManager] Estado en paso 3:', {
                    genero: StateManager.getGeneroSeleccionado(),
                    tallas: StateManager.getTallasSeleccionadas(),
                    atrasVisible: btnAtras ? btnAtras.style.display : 'no encontrado'
                });
                
                // Actualizar etiquetas del resumen
                this.actualizarEtiquetasResumen();
            }
        },

        /**
         * Actualizar etiquetas del resumen
         */
        actualizarEtiquetasResumen() {
            const generoLabel = document.getElementById('wizard-genero-label');
            const tallaLabel = document.getElementById('wizard-talla-label');
            const telaLabel = document.getElementById('wizard-tela-label');
            
            if (generoLabel) {
                const genero = StateManager.getGeneroSeleccionado();
                // Si el género es null (múltiples géneros), obtener de las asignaciones guardadas
                if (!genero) {
                    const asignaciones = window.AsignacionManager?.getAsignacionesPorTela() || [];
                    const generosUnicos = new Set();
                    Object.values(asignaciones).forEach(asign => {
                        if (asign && asign.genero) generosUnicos.add(asign.genero);
                    });
                    const textoGenero = generosUnicos.size > 0 
                        ? Array.from(generosUnicos).map(g => g.toUpperCase()).join(', ')
                        : 'SIN GÉNERO';
                    generoLabel.textContent = textoGenero;
                    console.log('[WizardManager] Género label (múltiple):', textoGenero);
                } else {
                    generoLabel.textContent = genero.toUpperCase();
                    console.log('[WizardManager] Género label actualizado:', genero.toUpperCase());
                }
            }
            
            if (tallaLabel) {
                const tallas = StateManager.getTallasSeleccionadas();
                const displayTallas = tallas.join(', ');
                tallaLabel.textContent = displayTallas;
                console.log('[WizardManager] Tallas label actualizado:', displayTallas);
            }
            
            if (telaLabel) {
                // Obtener la tela de StateManager (donde se guardó en abrirModalAsignarColores)
                const telaActual = StateManager.getTelaSeleccionada() || '--';
                telaLabel.textContent = telaActual;
                console.log('[WizardManager] Tela label actualizado:', telaActual);
            }
        },

        /**
         * Cargar tallas disponibles para el género seleccionado
         */
        cargarTallasParaGenero(genero) {
            const contenedor = document.getElementById('wizard-tallas-contenedor');
            if (!contenedor) {
                console.error('[WizardManager] wizard-tallas-contenedor no encontrado');
                return;
            }
            
            try {
                // Obtener el género previamente seleccionado
                const generoPrevio = StateManager.getGeneroSeleccionado();
                
                // NO limpiar tallas - permitir agregar tallas de múltiples géneros
                // Solo hacer logging del cambio
                if (generoPrevio !== genero) {
                    console.log('[WizardManager] Género cambió de', generoPrevio, 'a', genero, '- Conservando tallas previas para agregar más');
                } else {
                    console.log('[WizardManager] Mismo género - tallas se actualizarán con el nuevo tipo seleccionado');
                }
                
                // Guardar el género seleccionado (género actual para la interfaz)
                StateManager.setGeneroSeleccionado(genero);
                
                // Obtener tipos de talla disponibles para el género
                const tiposTalla = StateManager.getTallasDisponibles(genero);
                console.log('[WizardManager] Tipos de talla para', genero, ':', tiposTalla);
                
                // Obtener los tipos de talla disponibles
                const tiposDisponibles = Object.keys(tiposTalla);
                
                if (tiposDisponibles.length === 0) {
                    console.error('[WizardManager] No hay tipos de talla disponibles para', genero);
                    return;
                }
                
                // Obtener el tipo previamente seleccionado (si existe)
                const tipoPrevio = StateManager.getTipoTallaSel();
                
                // Si hay un solo tipo de talla, mostrar directamente las tallas
                if (tiposDisponibles.length === 1) {
                    const tipoUnico = tiposDisponibles[0];
                    console.log('[WizardManager] Un solo tipo de talla, mostrando:', tipoUnico);
                    window.WizardManager.mostrarTallasPorTipo(genero, tipoUnico);
                } else if (tipoPrevio && tiposDisponibles.includes(tipoPrevio)) {
                    // Si había un tipo previamente seleccionado y sigue siendo válido, mostrar ese tipo
                    console.log('[WizardManager] Tipo previamente seleccionado encontrado:', tipoPrevio);
                    window.WizardManager.mostrarTiposTallaConSeleccion(genero, tiposDisponibles, tipoPrevio);
                } else {
                    // Si hay múltiples tipos y no hay uno previo, mostrar los botones de tipos
                    console.log('[WizardManager] Mostrando selector de tipos de talla');
                    window.WizardManager.mostrarTiposTalla(genero, tiposDisponibles);
                }
                
                // Actualizar etiqueta del género
                const generoLabel = document.getElementById('wizard-genero-seleccionado');
                if (generoLabel) {
                    generoLabel.innerHTML = `Género: <strong>${genero.toUpperCase()}</strong>`;
                }
            } catch (error) {
                console.error('[WizardManager] Error cargando tallas para género:', error);
            }
        },

        /**
         * Mostrar los tipos de talla disponibles
         */
        mostrarTiposTalla(genero, tiposDisponibles) {
            const contenedor = document.getElementById('wizard-tallas-contenedor');
            if (!contenedor) {
                console.error('[WizardManager]  No se encontró wizard-tallas-contenedor');
                return;
            }
            
            console.log('[WizardManager]  Contenedor estado antes:', {
                display: contenedor.style.display,
                offsetHeight: contenedor.offsetHeight,
                parentDisplay: window.getComputedStyle(contenedor.parentElement).display
            });
            
            contenedor.innerHTML = '';
            console.log('[WizardManager] Mostrando tipos para', genero, ':', tiposDisponibles);
            
            // Título
            const tituloDiv = document.createElement('div');
            tituloDiv.style.marginBottom = '0.5rem';
            tituloDiv.style.fontWeight = '500';
            tituloDiv.style.color = '#374151';
            tituloDiv.style.fontSize = '0.95rem';
            tituloDiv.textContent = 'Selecciona el tipo de talla:';
            contenedor.appendChild(tituloDiv);
            
            // Contenedor flex para los botones
            const btnContainer = document.createElement('div');
            btnContainer.style.display = 'flex';
            btnContainer.style.gap = '1rem';
            btnContainer.style.flexWrap = 'wrap';
            btnContainer.style.alignItems = 'center';
            btnContainer.style.marginTop = '1rem';
            contenedor.appendChild(btnContainer);
            
            // Crear botones para cada tipo de talla
            let contadorBotones = 0;
            tiposDisponibles.forEach(tipo => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'wizard-tipo-talla-btn';
                btn.style.padding = '0.6rem 1.5rem';
                btn.style.border = '1px solid #d1d5db';
                btn.style.background = 'white';
                btn.style.borderRadius = '4px';
                btn.style.cursor = 'pointer';
                btn.style.fontWeight = '500';
                btn.style.fontSize = '0.9rem';
                btn.style.color = '#374151';
                btn.style.transition = 'all 0.2s';
                btn.style.minWidth = '100px';
                btn.textContent = tipo;
                
                // Eventos mouse
                btn.addEventListener('mouseover', () => {
                    if (!btn.hasAttribute('data-selected')) {
                        btn.style.background = '#f3f4f6';
                        btn.style.borderColor = '#9ca3af';
                    }
                });
                
                btn.addEventListener('mouseout', () => {
                    if (!btn.hasAttribute('data-selected')) {
                        btn.style.background = 'white';
                        btn.style.borderColor = '#d1d5db';
                        btn.style.color = '#374151';
                        btn.style.fontWeight = '500';
                    }
                });
                
                // Evento click
                btn.addEventListener('click', () => {
                    console.log('[WizardManager] 🎯 Tipo seleccionado:', tipo);
                    
                    // Marcar botón como seleccionado
                    const tipoBtns = document.querySelectorAll('.wizard-tipo-talla-btn');
                    tipoBtns.forEach(b => {
                        b.removeAttribute('data-selected');
                        b.style.background = 'white';
                        b.style.borderColor = '#d1d5db';
                        b.style.color = '#374151';
                        b.style.fontWeight = '500';
                    });
                    
                    btn.setAttribute('data-selected', 'true');
                    btn.style.background = '#eff6ff';
                    btn.style.borderColor = '#3b82f6';
                    btn.style.color = '#1f2937';
                    btn.style.fontWeight = '600';
                    
                    console.log('[WizardManager]  Llamando mostrarTallasPorTipo con:', genero, tipo);
                    window.WizardManager.mostrarTallasPorTipo(genero, tipo);
                });
                
                btnContainer.appendChild(btn);
                contadorBotones++;
            });
            
            console.log('[WizardManager]  Se crearon', contadorBotones, 'botones de tipo de talla');
            console.log('[WizardManager]  Contenedor después:', {
                display: contenedor.style.display,
                offsetHeight: contenedor.offsetHeight,
                childrenCount: contenedor.children.length
            });
        },

        /**
         * Mostrar tipos de talla con uno preseleccionado
         * (Usado cuando vuelves atrás y ya hay un tipo previamente seleccionado)
         */
        mostrarTiposTallaConSeleccion(genero, tiposDisponibles, tipoSeleccionado) {
            const contenedor = document.getElementById('wizard-tallas-contenedor');
            if (!contenedor) {
                console.error('[WizardManager]  No se encontró wizard-tallas-contenedor');
                return;
            }

            console.log('[WizardManager]  Mostrando tipos con preselección:', {
                genero: genero,
                tiposDisponibles: tiposDisponibles,
                tipoSeleccionado: tipoSeleccionado
            });

            contenedor.innerHTML = '';

            // Título
            const tituloDiv = document.createElement('div');
            tituloDiv.style.marginBottom = '0.5rem';
            tituloDiv.style.fontWeight = '500';
            tituloDiv.style.color = '#374151';
            tituloDiv.style.fontSize = '0.95rem';
            tituloDiv.textContent = 'Selecciona el tipo de talla:';
            contenedor.appendChild(tituloDiv);

            // Contenedor flex para los botones
            const btnContainer = document.createElement('div');
            btnContainer.style.display = 'flex';
            btnContainer.style.gap = '1rem';
            btnContainer.style.flexWrap = 'wrap';
            btnContainer.style.alignItems = 'center';
            btnContainer.style.marginTop = '1rem';
            contenedor.appendChild(btnContainer);

            // Crear botones para cada tipo de talla
            tiposDisponibles.forEach(tipo => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'wizard-tipo-talla-btn';
                btn.style.padding = '0.6rem 1.5rem';
                btn.style.borderRadius = '4px';
                btn.style.cursor = 'pointer';
                btn.style.fontWeight = '500';
                btn.style.fontSize = '0.9rem';
                btn.style.transition = 'all 0.2s';
                btn.style.minWidth = '100px';
                btn.textContent = tipo;

                // Pre-seleccionar el tipo que ya estaba seleccionado
                if (tipo === tipoSeleccionado) {
                    btn.setAttribute('data-selected', 'true');
                    btn.style.background = '#eff6ff';
                    btn.style.border = '1px solid #3b82f6';
                    btn.style.color = '#1f2937';
                    btn.style.fontWeight = '600';
                } else {
                    btn.style.border = '1px solid #d1d5db';
                    btn.style.background = 'white';
                    btn.style.color = '#374151';
                }

                // Eventos mouse
                btn.addEventListener('mouseover', () => {
                    if (!btn.hasAttribute('data-selected')) {
                        btn.style.background = '#f3f4f6';
                        btn.style.borderColor = '#9ca3af';
                    }
                });

                btn.addEventListener('mouseout', () => {
                    if (!btn.hasAttribute('data-selected')) {
                        btn.style.background = 'white';
                        btn.style.borderColor = '#d1d5db';
                        btn.style.color = '#374151';
                        btn.style.fontWeight = '500';
                    }
                });

                // Evento click
                btn.addEventListener('click', () => {
                    console.log('[WizardManager] 🎯 Tipo seleccionado:', tipo);

                    // Marcar botón como seleccionado
                    const tipoBtns = document.querySelectorAll('.wizard-tipo-talla-btn');
                    tipoBtns.forEach(b => {
                        b.removeAttribute('data-selected');
                        b.style.background = 'white';
                        b.style.borderColor = '#d1d5db';
                        b.style.color = '#374151';
                        b.style.fontWeight = '500';
                    });

                    btn.setAttribute('data-selected', 'true');
                    btn.style.background = '#eff6ff';
                    btn.style.borderColor = '#3b82f6';
                    btn.style.color = '#1f2937';
                    btn.style.fontWeight = '600';

                    console.log('[WizardManager]  Llamando mostrarTallasPorTipo con:', genero, tipo);
                    window.WizardManager.mostrarTallasPorTipo(genero, tipo);
                });

                btnContainer.appendChild(btn);
            });

            // Automáticamente mostrar las tallas del tipo preseleccionado
            console.log('[WizardManager]  Cargando tallas para tipo preseleccionado:', tipoSeleccionado);
            window.WizardManager.mostrarTallasPorTipo(genero, tipoSeleccionado);
        },

        /**
         * Mostrar las tallas para un tipo específico (CON CHECKBOXES PARA MÚLTIPLE SELECCIÓN)
         */
        mostrarTallasPorTipo(genero, tipo) {
            const contenedor = document.getElementById('wizard-tallas-contenedor');
            if (!contenedor) {
                console.error('[WizardManager] Contenedor wizard-tallas-contenedor no encontrado');
                return;
            }
            
            try {
                contenedor.innerHTML = '';
                
                // Obtener las tallas para este tipo
                const tallas = StateManager.getTallasDisponibles(genero)[tipo] || [];
                console.log('[WizardManager] Tallas de tipo', tipo, 'para', genero, ':', tallas.length, 'tallas');
                
                if (tallas.length === 0) {
                    const div = document.createElement('div');
                    div.style.textAlign = 'center';
                    div.style.padding = '1.5rem';
                    div.style.color = '#9ca3af';
                    div.textContent = 'No hay tallas disponibles para este tipo';
                    contenedor.appendChild(div);
                    return;
                }
                
                // Guardar tipo seleccionado en el estado del wizard
                StateManager.setTipoTallaSel(tipo);
                
                // Obtener las tallas que ya estaban seleccionadas
                const tallasGuardadas = StateManager.getTallasSeleccionadas();
                console.log('[WizardManager] Tallas restauradas:', tallasGuardadas);
                
                // Mostrar el tipo seleccionado
                const tituloDiv = document.createElement('div');
                tituloDiv.style.marginBottom = '1rem';
                tituloDiv.style.fontWeight = '500';
                tituloDiv.style.color = '#374151';
                tituloDiv.style.fontSize = '0.95rem';
                tituloDiv.innerHTML = `Tipo de talla: <strong>${tipo}</strong> (puedes seleccionar varias)`;
                contenedor.appendChild(tituloDiv);
                
                // Agregar instrucción clara
                const instruccionDiv = document.createElement('div');
                instruccionDiv.style.marginBottom = '1rem';
                instruccionDiv.style.padding = '0.75rem';
                instruccionDiv.style.background = '#fef3c7';
                instruccionDiv.style.border = '1px solid #fcd34d';
                instruccionDiv.style.borderRadius = '4px';
                instruccionDiv.style.fontSize = '0.85rem';
                instruccionDiv.style.color = '#92400e';
                instruccionDiv.innerHTML = '⚠️ <strong>Selecciona al menos una talla</strong> haciendo click en los botones de abajo';
                contenedor.appendChild(instruccionDiv);
                
                // Contenedor para los checkboxes - HORIZONTAL
                const checkboxContainer = document.createElement('div');
                checkboxContainer.style.display = 'flex';
                checkboxContainer.style.gap = '0.75rem';
                checkboxContainer.style.flexWrap = 'wrap';
                checkboxContainer.style.alignItems = 'center';
                contenedor.appendChild(checkboxContainer);
                
                // Crear checkboxes para cada talla (MÚLTIPLE SELECCIÓN)
                tallas.forEach(talla => {
                    const label = document.createElement('label');
                    label.style.display = 'flex';
                    label.style.alignItems = 'center';
                    label.style.gap = '0.5rem';
                    label.style.padding = '0.6rem 1rem';
                    label.style.border = '1px solid #d1d5db';
                    label.style.background = 'white';
                    label.style.borderRadius = '4px';
                    label.style.cursor = 'pointer';
                    label.style.fontWeight = '500';
                    label.style.fontSize = '0.9rem';
                    label.style.color = '#374151';
                    label.style.transition = 'all 0.2s';
                    label.style.userSelect = 'none';
                    label.style.whiteSpace = 'nowrap';
                    
                    const checkbox = document.createElement('input');
                    checkbox.type = 'checkbox';
                    checkbox.className = 'wizard-talla-checkbox';
                    checkbox.value = talla;
                    checkbox.dataset.tipo = tipo;
                    
                    // Marcar si la talla ya estaba seleccionada (ANTES de crear el listener)
                    const estaBaSeleccionada = tallasGuardadas && tallasGuardadas.includes(talla);
                    if (estaBaSeleccionada) {
                        checkbox.checked = true;
                        label.style.background = '#eff6ff';
                        label.style.borderColor = '#3b82f6';
                        label.style.color = '#1f2937';
                        label.style.fontWeight = '600';
                    }
                    
                    // Agregar el listener de cambio
                    checkbox.addEventListener('change', () => {
                        try {
                            if (checkbox.checked) {
                                // Agregar talla a la lista
                                StateManager.agregarTallaSeleccionada(talla);
                                label.style.background = '#eff6ff';
                                label.style.borderColor = '#3b82f6';
                                label.style.color = '#1f2937';
                                label.style.fontWeight = '600';
                                console.log('[WizardManager] Talla agregada:', talla);
                            } else {
                                // Remover talla de la lista
                                StateManager.removerTallaSeleccionada(talla);
                                label.style.background = 'white';
                                label.style.borderColor = '#d1d5db';
                                label.style.color = '#374151';
                                label.style.fontWeight = '500';
                                console.log('[WizardManager] Talla removida:', talla);
                            }
                            
                            const nuevasTallas = StateManager.getTallasSeleccionadas();
                            console.log('[WizardManager] Tallas seleccionadas ahora:', nuevasTallas);
                            
                            // Actualizar estado del botón siguiente
                            const btnSiguiente = document.getElementById('wzd-btn-siguiente');
                            if (btnSiguiente) {
                                const hayTallas = StateManager.tieneTallasSeleccionadas();
                                const previo = btnSiguiente.disabled;
                                btnSiguiente.disabled = false;
                                
                                // Cambiar apariencia visual pero NUNCA bloquear
                                if (!hayTallas) {
                                    btnSiguiente.style.opacity = '0.5';
                                    btnSiguiente.style.cursor = 'pointer';
                                    btnSiguiente.style.pointerEvents = 'auto';
                                    btnSiguiente.title = 'Selecciona al menos una talla';
                                } else {
                                    btnSiguiente.style.opacity = '1';
                                    btnSiguiente.style.cursor = 'pointer';
                                    btnSiguiente.style.pointerEvents = 'auto';
                                    btnSiguiente.title = '';
                                }
                                
                                console.log('[WizardManager] Botón siguiente actualizado:', { anterior: previo, nuevo: !hayTallas, hayTallas: hayTallas });
                            }
                        } catch (error) {
                            console.error('[WizardManager] Error en evento change del checkbox:', error);
                        }
                    });
                    
                    label.appendChild(checkbox);
                    label.appendChild(document.createTextNode(talla));
                    checkboxContainer.appendChild(label);
                });
                
                // Actualizar estado del botón siguiente después de cargar todo
                const btnSiguiente = document.getElementById('wzd-btn-siguiente');
                if (btnSiguiente) {
                    const hayTallas = StateManager.tieneTallasSeleccionadas();
                    btnSiguiente.disabled = false;
                    
                    // Solo cambiar apariencia visual, NUNCA bloquear
                    if (!hayTallas) {
                        btnSiguiente.style.opacity = '0.5';
                        btnSiguiente.style.cursor = 'pointer';
                        btnSiguiente.style.pointerEvents = 'auto';
                        btnSiguiente.title = 'Selecciona al menos una talla';
                    } else {
                        btnSiguiente.style.opacity = '1';
                        btnSiguiente.style.cursor = 'pointer';
                        btnSiguiente.style.pointerEvents = 'auto';
                        btnSiguiente.title = '';
                    }
                }
            } catch (error) {
                console.error('[WizardManager] Error mostrando tallas por tipo:', error);
            }
        },

        /**
         * Cargar colores disponibles para cada talla seleccionada en Paso 3
         */
        cargarColoresParaTalla() {
            console.log('[WizardManager]  Iniciando carga de colores para Paso 3...');
            
            const genero = StateManager.getGeneroSeleccionado();
            const tallas = StateManager.getTallasSeleccionadas();
            const tipo = StateManager.getTipoTallaSel();
            
            console.log('[WizardManager]  Datos para colores:', {
                genero: genero,
                tallas: tallas,
                tipo: tipo,
                pasoActual: StateManager.getPasoActual()
            });
            
            try {
                // Actualizar etiquetas del resumen
                console.log('[WizardManager] 🏷️ Actualizando etiquetas del resumen...');
                this.actualizarEtiquetasResumen();
                
                // Generar interfaz de colores por talla
                console.log('[WizardManager]  Generando interfaz de colores...');
                if (window.UIRenderer && typeof window.UIRenderer.generarInterfazColoresPorTalla === 'function') {
                    window.UIRenderer.generarInterfazColoresPorTalla(genero, tallas, tipo);
                    console.log('[WizardManager]  Interfaz de colores generada exitosamente');
                } else {
                    console.error('[WizardManager]  UIRenderer.generarInterfazColoresPorTalla no disponible');
                }
                
                console.log('[WizardManager]  Paso 3 cargado completamente');
                
            } catch (error) {
                console.error('[WizardManager]  Error cargando colores para Paso 3:', error);
                console.error('[WizardManager] Stack trace:', error.stack);
                
                // Mostrar error al usuario
                alert('Error cargando la interfaz de colores. Por favor intente nuevamente.');
                
                // Volver al paso 2 en caso de error
                console.log('[WizardManager]  Volviendo al Paso 2 debido a error...');
                this.irPaso(2);
            }
        },

        /**
         * Resetear wizard completamente
         */
        resetWizard() {
            try {
                console.log('[WizardManager] Iniciando RESET completo del wizard...');
                
                // Paso 1: Resetear estado en StateManager
                StateManager.resetWizardState();
                console.log('[WizardManager] Estado del StateManager reseteado');
                console.log('[WizardManager] Verificación de estado:', {
                    pasoActual: StateManager.getPasoActual(),
                    generoSeleccionado: StateManager.getGeneroSeleccionado(),
                    tallasSeleccionadas: StateManager.getTallasSeleccionadas()
                });
                
                // Paso 2: Limpiar atributos de listeners para que se reconfiguren
                console.log('[WizardManager] Limpiando atributos de listeners...');
                const botonesReset = [
                    'wzd-btn-atras',
                    'wzd-btn-siguiente',
                    'btn-guardar-asignacion',
                    'btn-cancelar-wizard',
                    'asignacion-genero-select',
                    'asignacion-talla-select',
                    'btn-agregar-color-personalizado'
                ];
                
                botonesReset.forEach(id => {
                    const elemento = document.getElementById(id);
                    if (elemento) {
                        delete elemento.dataset.listenerConfigured;
                        console.log(`[WizardManager] Limpiado atributo de: ${id}`);
                    }
                });
                
                // Paso 3: NO ir a paso 1 inmediatamente - esperar a que toggleVistaAsignacion lo haga
                // (porque si hay múltiples telas, mostrarSelectoreTelasSiNecesario() ocultará paso 1)
                console.log('[WizardManager] ⏳ Esperando que toggleVistaAsignacion maneje los pasos...');
                
                console.log('[WizardManager] ✅ Wizard reseteado y listo para reconfigurarse');
            } catch (error) {
                console.error('[WizardManager] Error en resetWizard():', error);
            }
        }
    };
})();
