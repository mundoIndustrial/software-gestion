/**
 * Módulo: WizardManager
 * Gestiona la navegación y lógica del wizard de 3 pasos
 */

window.WizardManager = (function() {
    'use strict';

    // Verificar solo StateManager como dependencia
    if (!window.StateManager) {
        console.error('[WizardManager] ❌ StateManager no está disponible');
        return {};
    }

    return {
        /**
         * PASO 0 (opcional): Seleccionar tela (solo si hay múltiples)
         */
        seleccionarTela(tela) {
            console.log('[WizardManager] 📦 Tela seleccionada:', tela);
            StateManager.setTelaSeleccionada(tela);
            
            // Mostrar botón siguiente
            const btnSiguiente = document.getElementById('wzd-btn-siguiente');
            if (btnSiguiente) {
                btnSiguiente.disabled = false;
                btnSiguiente.style.display = 'flex';
                console.log('[WizardManager] ✅ Botón Siguiente mostrado');
            }
        },

        /**
         * PASO 1: Seleccionar género
         */
        seleccionarGenero(genero) {
            StateManager.setGeneroSeleccionado(genero);
            console.log('[WizardManager] ✅ Género seleccionado:', genero);
            
            // Mostrar y habilitar el botón siguiente
            const btnSiguiente = document.getElementById('wzd-btn-siguiente');
            if (btnSiguiente) {
                btnSiguiente.disabled = false;
                btnSiguiente.style.display = 'flex';
                console.log('[WizardManager] ✅ Botón Siguiente mostrado');
            } else {
                console.error('[WizardManager] ❌ Botón wzd-btn-siguiente no encontrado');
            }
            
            // Resaltar el botón seleccionado
            const generoBtns = document.querySelectorAll('.wizard-genero-btn');
            
            generoBtns.forEach(btn => {
                if (btn.dataset.genero === genero) {
                    btn.style.background = '#eff6ff';
                    btn.style.borderColor = '#3b82f6';
                    btn.style.color = '#1f2937';
                    btn.style.fontWeight = '600';
                    console.log('[WizardManager] ✅ Botón', genero, 'resaltado');
                } else {
                    btn.style.background = 'white';
                    btn.style.borderColor = '#d1d5db';
                    btn.style.color = '#374151';
                    btn.style.fontWeight = '500';
                }
            });
        },

        /**
         * PASO 2: Ir al siguiente paso
         */
        pasoSiguiente() {
            console.log('[WizardManager] 🔄 pasoSiguiente() llamado...');
            
            const pasoActual = StateManager.getPasoActual();
            const telaSeleccionada = StateManager.getTelaSeleccionada();
            const generoSeleccionado = StateManager.getGeneroSeleccionado();
            const tallasSeleccionadas = StateManager.getTallasSeleccionadas();
            
            console.log('[WizardManager] 📋 Estado actual:', {
                pasoActual: pasoActual,
                telaSeleccionada: telaSeleccionada,
                generoSeleccionado: generoSeleccionado,
                tallasSeleccionadas: tallasSeleccionadas ? tallasSeleccionadas.length : 0
            });
            
            // PASO 0: Validar que se seleccionó una tela
            if (pasoActual === 0) {
                console.log('[WizardManager] ↪️ En PASO 0 - Validando tela...');
                if (!telaSeleccionada) {
                    console.warn('[WizardManager] ⚠️ No hay tela seleccionada');
                    alert('Por favor selecciona una tela antes de continuar');
                    return false;
                }
                console.log('[WizardManager] ✅ Tela validada:', telaSeleccionada);
                console.log('[WizardManager] ➜ Avanzando a PASO 1...');
                this.irPaso(1);
                return true;
            }
            // PASO 1: Validar que se seleccionó un género
            else if (pasoActual === 1) {
                console.log('[WizardManager] ↪️ En PASO 1 - Validando género...');
                if (!generoSeleccionado) {
                    console.warn('[WizardManager] ⚠️ No hay género seleccionado');
                    alert('Por favor selecciona un género antes de continuar');
                    return false;
                }
                console.log('[WizardManager] ✅ Género validado:', generoSeleccionado);
                console.log('[WizardManager] ➜ Avanzando a PASO 2...');
                this.irPaso(2);
                return true;
            } 
            // PASO 2: Validar que se seleccionó al menos una talla
            else if (pasoActual === 2) {
                console.log('[WizardManager] ↪️ En PASO 2 - Validando tallas...');
                if (!tallasSeleccionadas || tallasSeleccionadas.length === 0) {
                    console.warn('[WizardManager] ⚠️ No hay tallas seleccionadas');
                    alert('Por favor selecciona al menos una talla antes de continuar');
                    return false;
                }
                console.log('[WizardManager] ✅ Tallas validadas:', tallasSeleccionadas);
                console.log('[WizardManager] ➜ Avanzando a PASO 3...');
                this.irPaso(3);
                return true;
            } 
            // PASO 3: No se puede avanzar más
            else if (pasoActual === 3) {
                console.log('[WizardManager] ↪️ En PASO 3 - Este es el último paso');
                console.log('[WizardManager] ℹ️ Ya estás en paso 3. Usa el botón "Guardar Asignación" para guardar.');
                return false;
            } 
            // Estado inválido
            else {
                console.error('[WizardManager] ❌ Paso no válido:', pasoActual);
                alert('Error: Estado del wizard inválido. Por favor recarga la página.');
                return false;
            }
        },

        /**
         * Ir atrás en el wizard
         */
        pasoAnterior() {
            const pasoActual = StateManager.getPasoActual();
            const pasosDelWizard = this.obtenerTotalPasos();
            if (pasoActual > 0) {
                this.irPaso(pasoActual - 1);
            }
        },

        /**
         * Obtener el número total de pasos (3 o 4 dependiendo de si hay múltiples telas)
         */
        obtenerTotalPasos() {
            const telas = window.telasCreacion || [];
            return telas.length > 1 ? 4 : 3;  // 4 pasos si múltiples, 3 si simple
        },

        /**
         * Inicializar el wizard y determinar paso inicial
         */
        inicializarWizard() {
            console.log('[WizardManager] 🚀 Inicializando wizard...');
            const telas = window.telasCreacion || [];
            const totalPasos = this.obtenerTotalPasos();
            
            console.log('[WizardManager] 📊 Total de telas:', telas.length, '| Total pasos:', totalPasos);
            
            // Mostrar/ocultar indicador paso 0 y su contenedor
            const paso0Wrapper = document.getElementById('paso-0-wrapper');
            const paso0Linea = document.getElementById('paso-0-linea');
            
            if (telas.length > 1) {
                // Múltiples telas: mostrar paso 0 y empezar ahí
                console.log('[WizardManager] 📦 Múltiples telas detectadas - mostrar paso 0');
                if (paso0Wrapper) paso0Wrapper.style.display = 'block';
                if (paso0Linea) paso0Linea.style.display = 'block';
                this.irPaso(0);
            } else {
                // Una sola tela: ocultar paso 0 y empezar en paso 1
                console.log('[WizardManager] 📦 Una sola tela - ocultar paso 0, empezar en paso 1');
                if (paso0Wrapper) paso0Wrapper.style.display = 'none';
                if (paso0Linea) paso0Linea.style.display = 'none';
                // Auto-seleccionar la tela única
                if (telas.length === 1) {
                    const nombreTela = telas[0].tela_nombre || telas[0].nombre_tela || telas[0].tela;
                    StateManager.setTelaSeleccionada(nombreTela);
                    console.log('[WizardManager] ✅ Tela única auto-seleccionada:', nombreTela);
                }
                this.irPaso(1);
            }
        },

        /**
         * Navegar a un paso específico (0, 1, 2 ó 3)
         */
        irPaso(numeroPaso) {
            const totalPasos = this.obtenerTotalPasos();
            // Validar paso
            if (numeroPaso < 0 || numeroPaso >= totalPasos) return;
            
            // Ocultar todos los pasos
            ['wizard-paso-0', 'wizard-paso-1', 'wizard-paso-2', 'wizard-paso-3'].forEach(id => {
                const elemento = document.getElementById(id);
                if (elemento) {
                    elemento.style.display = 'none';
                }
            });
            
            // Mostrar el paso actual
            const pasoActual = document.getElementById(`wizard-paso-${numeroPaso}`);
            if (pasoActual) {
                pasoActual.style.display = 'block';
            }
            
            // Actualizar indicadores de progreso
            this.actualizarIndicadoresProgreso(numeroPaso);
            
            // Actualizar botones de navegación
            this.actualizarBotonesNavegacion(numeroPaso);
            
            // Si es paso 0, cargar las telas disponibles
            if (numeroPaso === 0) {
                this.cargarTelasDisponibles();
            }
            
            // Si es paso 1, cargar los géneros (no hace falta, ya están)
            if (numeroPaso === 1) {
                // Géneros ya están cargados en HTML
            }
            
            // Si es paso 2, cargar las tallas disponibles
            if (numeroPaso === 2) {
                this.cargarTallasParaGenero(StateManager.getGeneroSeleccionado());
            }
            
            // Si es paso 3, cargar los colores disponibles
            if (numeroPaso === 3) {
                this.cargarColoresParaTalla();
            }
            
            StateManager.setPasoActual(numeroPaso);
        },

        /**
         * Cargar telas disponibles para el paso 0
         */
        cargarTelasDisponibles() {
            const contenedor = document.getElementById('wizard-telas-selector');
            if (!contenedor) {
                console.error('[WizardManager] ❌ wizard-telas-selector no encontrado');
                return;
            }
            
            const telas = window.telasCreacion || [];
            if (telas.length <= 1) {
                console.log('[WizardManager] ℹ️ No hay múltiples telas para mostrar selector');
                return;
            }
            
            console.log('[WizardManager] ✅ Cargando telas disponibles:', telas.length);
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
                    console.log('[WizardManager] 🎯 Tela seleccionada:', nombreTela);
                    this.seleccionarTela(nombreTela);
                    // Avanzar al siguiente paso
                    this.pasoSiguiente();
                });
                
                contenedor.appendChild(btn);
            });
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
            
            // Línea 1-2
            const linea1 = paso1 ? paso1.parentElement.parentElement.querySelector('div:nth-child(2)') : null;
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
            const linea2 = paso2 ? paso2.parentElement.parentElement.querySelector('div:nth-child(4)') : null;
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
                const totalPasos = this.obtenerTotalPasos();
                
                if (btnAtras) {
                    if (totalPasos > 3) {
                        btnAtras.style.display = 'flex';
                        btnAtras.style.alignItems = 'center';
                        btnAtras.style.justifyContent = 'center';
                        btnAtras.style.gap = '0.25rem';
                        console.log('[WizardManager] Botón Atrás visible (hay múltiples telas)');
                    } else {
                        btnAtras.style.display = 'none';
                        console.log('[WizardManager] Botón Atrás oculto (simple)');
                    }
                }
                if (btnSiguiente) {
                    btnSiguiente.style.display = 'flex';
                    btnSiguiente.style.alignItems = 'center';
                    btnSiguiente.style.justifyContent = 'center';
                    btnSiguiente.style.gap = '0.25rem';
                    const generoSeleccionado = StateManager.getGeneroSeleccionado();
                    btnSiguiente.disabled = !generoSeleccionado;
                    console.log('[WizardManager] Botón Siguiente visible en paso 1');
                }
                if (btnGuardar) {
                    btnGuardar.style.display = 'none';
                    console.log('[WizardManager] Botón Guardar oculto');
                }
            }
            // PASO 2: Seleccionar Talla
            else if (pasoActual === 2) {
                if (btnAtras) {
                    btnAtras.style.display = 'flex';
                    btnAtras.style.alignItems = 'center';
                    btnAtras.style.justifyContent = 'center';
                    btnAtras.style.gap = '0.25rem';
                    console.log('[WizardManager] Botón Atrás visible');
                }
                if (btnSiguiente) {
                    btnSiguiente.style.display = 'flex';
                    btnSiguiente.style.alignItems = 'center';
                    btnSiguiente.style.justifyContent = 'center';
                    btnSiguiente.style.gap = '0.25rem';
                    const hayTallas = StateManager.tieneTallasSeleccionadas();
                    btnSiguiente.disabled = !hayTallas;
                    console.log('[WizardManager] Botón Siguiente visible en paso 2. Tallas seleccionadas:', StateManager.getTallasSeleccionadas());
                }
                if (btnGuardar) {
                    btnGuardar.style.display = 'none';
                    console.log('[WizardManager] Botón Guardar oculto');
                }
            }
            // PASO 3: Asignar Colores
            else if (pasoActual === 3) {
                if (btnAtras) {
                    btnAtras.style.display = 'flex';
                    btnAtras.style.alignItems = 'center';
                    btnAtras.style.justifyContent = 'center';
                    btnAtras.style.gap = '0.25rem';
                }
                if (btnSiguiente) {
                    btnSiguiente.style.display = 'none';
                }
                if (btnGuardar) {
                    btnGuardar.style.display = 'flex';
                    btnGuardar.style.alignItems = 'center';
                    btnGuardar.style.justifyContent = 'center';
                    btnGuardar.style.gap = '0.25rem';
                    btnGuardar.disabled = false;
                    console.log('[WizardManager] ✅ Botón Guardar VISIBLE en paso 3. Display:', btnGuardar.style.display);
                }
                
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
                generoLabel.textContent = StateManager.getGeneroSeleccionado().toUpperCase();
                console.log('[WizardManager] Género label actualizado:', StateManager.getGeneroSeleccionado().toUpperCase());
            }
            
            if (tallaLabel) {
                const tallas = StateManager.getTallasSeleccionadas();
                const displayTallas = tallas.join(', ');
                tallaLabel.textContent = displayTallas;
                console.log('[WizardManager] Tallas label actualizado:', displayTallas);
            }
            
            if (telaLabel) {
                // Obtener la tela actual del array telasCreacion
                let telaActual = '--';
                if (window.telasCreacion && Array.isArray(window.telasCreacion) && window.telasCreacion.length > 0) {
                    telaActual = window.telasCreacion[0].tela_nombre || window.telasCreacion[0].nombre_tela || window.telasCreacion[0].tela || '--';
                }
                telaLabel.textContent = telaActual;
                console.log('[WizardManager] Tela label actualizado:', telaActual);
            }
        },

        /**
         * Cargar tallas disponibles para el género seleccionado
         */
        cargarTallasParaGenero(genero) {
            const contenedor = document.getElementById('wizard-tallas-contenedor');
            if (!contenedor) return;
            
            // Limpiar lista de tallas seleccionadas al cambiar género
            StateManager.limpiarTallasSeleccionadas();
            console.log('[WizardManager] Lista de tallas limpiada');
            
            // Obtener tipos de talla disponibles para el género
            const tiposTalla = StateManager.getTallasDisponibles(genero);
            console.log('[WizardManager] Tipos de talla para', genero, ':', tiposTalla);
            
            // Limpiar contenedor
            contenedor.innerHTML = '';
            
            // Obtener los tipos de talla disponibles
            const tiposDisponibles = Object.keys(tiposTalla);
            
            // Si hay un solo tipo de talla, mostrar directamente las tallas
            if (tiposDisponibles.length === 1) {
                const tipoUnico = tiposDisponibles[0];
                this.mostrarTallasPorTipo(genero, tipoUnico);
            } else {
                // Si hay múltiples tipos, mostrar los botones de tipos primero
                this.mostrarTiposTalla(genero, tiposDisponibles);
            }
            
            // Actualizar etiqueta del género
            const generoLabel = document.getElementById('wizard-genero-seleccionado');
            if (generoLabel) {
                generoLabel.innerHTML = `Género: <strong>${genero.toUpperCase()}</strong>`;
            }
        },

        /**
         * Mostrar los tipos de talla disponibles
         */
        mostrarTiposTalla(genero, tiposDisponibles) {
            const contenedor = document.getElementById('wizard-tallas-contenedor');
            if (!contenedor) {
                console.error('[WizardManager] ❌ No se encontró wizard-tallas-contenedor');
                return;
            }
            
            console.log('[WizardManager] 🔍 Contenedor estado antes:', {
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
                    
                    console.log('[WizardManager] ✅ Llamando mostrarTallasPorTipo con:', genero, tipo);
                    this.mostrarTallasPorTipo(genero, tipo);
                });
                
                btnContainer.appendChild(btn);
                contadorBotones++;
            });
            
            console.log('[WizardManager] ✅ Se crearon', contadorBotones, 'botones de tipo de talla');
            console.log('[WizardManager] 🔍 Contenedor después:', {
                display: contenedor.style.display,
                offsetHeight: contenedor.offsetHeight,
                childrenCount: contenedor.children.length
            });
        },

        /**
         * Mostrar las tallas para un tipo específico (CON CHECKBOXES PARA MÚLTIPLE SELECCIÓN)
         */
        mostrarTallasPorTipo(genero, tipo) {
            const contenedor = document.getElementById('wizard-tallas-contenedor');
            if (!contenedor) {
                console.error('[WizardManager] ❌ Contenedor wizard-tallas-contenedor no encontrado');
                return;
            }
            
            contenedor.innerHTML = '';
            
            // Obtener las tallas para este tipo
            const tallas = StateManager.getTallasDisponibles(genero)[tipo] || [];
            console.log('[WizardManager] Tallas de tipo', tipo, 'para', genero, ':', tallas);
            
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
            
            // Mostrar el tipo seleccionado
            const tituloDiv = document.createElement('div');
            tituloDiv.style.marginBottom = '1rem';
            tituloDiv.style.fontWeight = '500';
            tituloDiv.style.color = '#374151';
            tituloDiv.style.fontSize = '0.95rem';
            tituloDiv.innerHTML = `Tipo de talla: <strong>${tipo}</strong> (puedes seleccionar varias)`;
            contenedor.appendChild(tituloDiv);
            
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
                
                checkbox.addEventListener('change', () => {
                    if (checkbox.checked) {
                        // Agregar talla a la lista
                        StateManager.agregarTallaSeleccionada(talla);
                        label.style.background = '#eff6ff';
                        label.style.borderColor = '#3b82f6';
                        label.style.color = '#1f2937';
                        label.style.fontWeight = '600';
                    } else {
                        // Remover talla de la lista
                        StateManager.removerTallaSeleccionada(talla);
                        label.style.background = 'white';
                        label.style.borderColor = '#d1d5db';
                        label.style.color = '#374151';
                        label.style.fontWeight = '500';
                    }
                    
                    console.log('[WizardManager] Tallas seleccionadas:', StateManager.getTallasSeleccionadas());
                    
                    // Habilitar botón siguiente si hay al menos una talla
                    const btnSiguiente = document.getElementById('wzd-btn-siguiente');
                    if (btnSiguiente) {
                        const hayTallas = StateManager.tieneTallasSeleccionadas();
                        btnSiguiente.disabled = !hayTallas;
                    }
                });
                
                label.appendChild(checkbox);
                label.appendChild(document.createTextNode(talla));
                checkboxContainer.appendChild(label);
            });
        },

        /**
         * Cargar colores disponibles para cada talla seleccionada en Paso 3
         */
        cargarColoresParaTalla() {
            const genero = StateManager.getGeneroSeleccionado();
            const tallas = StateManager.getTallasSeleccionadas();
            const tipo = StateManager.getTipoTallaSel();
            
            console.log('[WizardManager] Cargando interfaz para', genero, 'tallas:', tallas);
            
            // Actualizar etiquetas del resumen
            this.actualizarEtiquetasResumen();
            
            // Generar interfaz de colores por talla
            UIRenderer.generarInterfazColoresPorTalla(genero, tallas, tipo);
        },

        /**
         * Resetear wizard completamente
         */
        resetWizard() {
            console.log('[WizardManager] 🔄 Iniciando RESET completo del wizard...');
            
            // Paso 1: Resetear estado en StateManager
            StateManager.resetWizardState();
            console.log('[WizardManager] ✅ Estado del StateManager reseteado');
            console.log('[WizardManager] 📋 Verificación de estado:', {
                pasoActual: StateManager.getPasoActual(),
                generoSeleccionado: StateManager.getGeneroSeleccionado(),
                tallasSeleccionadas: StateManager.getTallasSeleccionadas()
            });
            
            // Paso 2: NO ir a paso 1 inmediatamente - esperar a que toggleVistaAsignacion lo haga
            // (porque si hay múltiples telas, mostrarSelectoreTelasSiNecesario() ocultará paso 1)
            console.log('[WizardManager] ⏳ Esperando que toggleVistaAsignacion maneje los pasos...');
            
            console.log('[WizardManager] ✅ Wizard reseteado y listo');
        }
    };
})();
