/**
 * Módulo: ColoresPorTalla
 * Módulo principal que orquesta todos los componentes del sistema de colores por talla
 * Gestiona la asignación de múltiples colores a cada talla-género
 */

window.ColoresPorTalla = (function() {
    'use strict';
    
    // Bandera para prevenir doble inicialización
    let eventosConfigurados = false;

    // Inicialización del módulo
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
                if (window.WizardManager) {
                    window.WizardManager.resetearWizard();
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
            wzdBtnSiguiente.addEventListener('click', () => WizardManager.pasoSiguiente());
            wzdBtnSiguiente.dataset.listenerConfigured = 'true';
            console.log('[ColoresPorTalla]  Event listener agregado a wzd-btn-siguiente');
        }
        
        if (wzdBtnAtras && !wzdBtnAtras.dataset.listenerConfigured) {
            wzdBtnAtras.addEventListener('click', () => WizardManager.pasoAnterior());
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
        
        if (!vistaTablaTelas || !vistaAsignacion) {
            console.error('[ColoresPorTalla]  FALLO: No se encontraron los elementos del DOM', {
                vistaTablaTelas: !!vistaTablaTelas,
                vistaAsignacion: !!vistaAsignacion,
                btnAsignar: !!btnAsignar
            });
            return;
        }
        
        const esVistaAsignacionActiva = vistaAsignacion.style.display !== 'none';
        
        console.log('[ColoresPorTalla]  Estado current:', {
            displayActual: vistaAsignacion.style.display,
            esVistaAsignacionActiva: esVistaAsignacionActiva
        });
        
        if (esVistaAsignacionActiva) {
            // Volver a vista de telas
            console.log('[ColoresPorTalla] 📋 ACCIÓN: Volviendo a tabla de telas');
            vistaTablaTelas.style.display = 'block';
            console.log('[ColoresPorTalla]  vistaTablaTelas.style.display =', vistaTablaTelas.style.display);
            
            vistaAsignacion.style.display = 'none';
            console.log('[ColoresPorTalla]  vistaAsignacion.style.display =', vistaAsignacion.style.display);
            
            if (btnAsignar) {
                btnAsignar.innerHTML = '<span class="material-symbols-rounded" style="font-size: 1.1rem;">color_lens</span>Asignar por Talla';
            }
            
            // Verificar si hay asignaciones guardadas
            UIRenderer.actualizarVisibilidadSeccionesResumen();
            
            console.log('[ColoresPorTalla]  Volviendo a vista de Tabla de Telas');
        } else {
            // Cambiar a vista de asignación
            console.log('[ColoresPorTalla] 📋 ACCIÓN: Abriendo vista de Asignación de Colores');
            vistaTablaTelas.style.display = 'none';
            console.log('[ColoresPorTalla]  vistaTablaTelas.style.display =', vistaTablaTelas.style.display);
            
            vistaAsignacion.style.display = 'block';
            console.log('[ColoresPorTalla]  vistaAsignacion.style.display =', vistaAsignacion.style.display);
            
            if (btnAsignar) {
                btnAsignar.innerHTML = '<span class="material-symbols-rounded" style="font-size: 1.1rem;">table_chart</span>Ver Telas';
            }
            
            // Ocultar sección TALLAS Y CANTIDADES, mostrar resumen
            ocultarTallasCantidadesYMostrarResumen();
            
            console.log('[ColoresPorTalla]  Cambiando a vista de Asignación de Colores');
            
            // Resetear estado del wizard para comenzar nuevo
            WizardManager.resetWizard();
            console.log('[ColoresPorTalla]  Wizard state reseteado');
            
            // Inicializar wizard con lógica de paso 0 si hay múltiples telas
            WizardManager.inicializarWizard();
            console.log('[ColoresPorTalla]  Wizard inicializado (mostrar paso 0 si múltiples, paso 1 si simple)');
            
            // Limpiar selectores para que el usuario elija desde cero
            if (generoSelect) {
                generoSelect.value = '';
                console.log('[ColoresPorTalla]  Reset género select');
            }
            
            if (tallaSelect) {
                tallaSelect.innerHTML = '<option value="">Seleccionar talla...</option>';
                console.log('[ColoresPorTalla]  Reset talla select');
            }
            
            // Limpiar lista de colores
            const listaColores = document.getElementById('lista-colores-checkboxes');
            if (listaColores) {
                listaColores.innerHTML = '';
                console.log('[ColoresPorTalla]  Usando display directo para clearElement');
            }
            
            // Mostrar tabla de asignaciones
            console.log('[ColoresPorTalla] 📞 Actualizando tablas de asignaciones');
            UIRenderer.actualizarTablaAsignaciones();
            UIRenderer.actualizarResumenAsignaciones();
            
            console.log('[ColoresPorTalla]  Vista de Asignación lista - esperando que selecciones género/talla');
        }
    }

    /**
     * Mostrar selector de telas si hay múltiples
     * Retorna: nombre de tela si hay 1, null si hay múltiples (espera que usuario seleccione)
     */
    function mostrarSelectoreTelasSiNecesario() {
        const telas = window.telasCreacion || [];
        console.log('[mostrarSelectoreTelasSiNecesario]  Telas disponibles:', telas.length);
        
        if (telas.length === 0) {
            console.error('[mostrarSelectoreTelasSiNecesario]  No hay telas');
            return null;
        }
        
        if (telas.length === 1) {
            // Si hay solo una tela, usarla automáticamente
            const nombreTela = telas[0].tela_nombre || telas[0].nombre_tela || telas[0].tela;
            console.log('[mostrarSelectoreTelasSiNecesario]  Una tela única:', nombreTela);
            return nombreTela;
        }
        
        // Si hay múltiples telas, mostrar selector
        console.log('[mostrarSelectoreTelasSiNecesario]  Múltiples telas detectadas - mostrando selector');
        
        const vistaAsignacion = document.getElementById('vista-asignacion-colores');
        if (!vistaAsignacion) {
            console.error('[mostrarSelectoreTelasSiNecesario]  No se encontró vista-asignacion-colores');
            return null;
        }
        
        // Verificar si ya existe el selector
        const selectorExistente = document.getElementById('wizard-selector-telas');
        if (selectorExistente) {
            console.log('[mostrarSelectoreTelasSiNecesario]  Selector ya existe');
            selectorExistente.style.display = 'block';
            return null;
        }
        
        // Crear contenedor del selector (PASO 0)
        const selectorDiv = document.createElement('div');
        selectorDiv.id = 'wizard-selector-telas';
        selectorDiv.style.cssText = `
            background: #f0f9ff;
            border: 1px solid #bfdbfe;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            text-align: center;
        `;
        
        const stepCounter = document.createElement('div');
        stepCounter.style.cssText = 'color: #6b7280; font-size: 0.85rem; margin-bottom: 0.5rem; font-weight: 500;';
        stepCounter.textContent = ' PASO 1: Selecciona Tela';
        
        const titulo = document.createElement('h3');
        titulo.style.cssText = 'color: #111827; margin: 0 0 0.5rem 0; font-size: 1.1rem; font-weight: 600;';
        titulo.textContent = 'Selecciona una Tela';
        
        const descrip = document.createElement('p');
        descrip.style.cssText = 'color: #6b7280; margin: 0 0 1.5rem 0; font-size: 0.85rem;';
        descrip.textContent = 'Elige a cuál tela agregar colores y tallas';
        
        const botonesDiv = document.createElement('div');
        botonesDiv.style.cssText = 'display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 0.75rem;';
        
        // Crear botones para cada tela
        telas.forEach(tela => {
            const nombreTela = tela.tela_nombre || tela.nombre_tela || tela.tela;
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'wizard-tela-btn';
            btn.dataset.tela = nombreTela;
            btn.style.cssText = `
                padding: 1rem;
                border: 1px solid #d1d5db;
                background: white;
                border-radius: 4px;
                cursor: pointer;
                font-weight: 500;
                font-size: 0.85rem;
                color: #374151;
                transition: all 0.2s;
            `;
            btn.textContent = nombreTela.toUpperCase();
            
            btn.addEventListener('click', () => {
                console.log('[mostrarSelectoreTelasSiNecesario]  Tela seleccionada:', nombreTela);
                StateManager.setTelaSeleccionada(nombreTela);
                
                // Resaltar botón seleccionado
                document.querySelectorAll('.wizard-tela-btn').forEach(b => {
                    b.style.background = 'white';
                    b.style.borderColor = '#d1d5db';
                    b.style.color = '#374151';
                });
                
                btn.style.background = '#eff6ff';
                btn.style.borderColor = '#3b82f6';
                btn.style.color = '#1f2937';
                btn.style.fontWeight = '600';
                
                // IMPORTANTE: Esperar un poco antes de ocultar el selector y mostrar paso 1
                setTimeout(() => {
                    // Ocultar el selector de tela
                    selectorDiv.style.display = 'none';
                    console.log('[mostrarSelectoreTelasSiNecesario]  Selector de tela ocultado');
                    
                    // Mostrar paso 1 (género)
                    const paso1 = document.getElementById('wizard-paso-1');
                    if (paso1) {
                        paso1.style.display = 'block';
                        console.log('[mostrarSelectoreTelasSiNecesario]  Paso 1 (género) mostrado');
                    }
                    
                    // Actualizar indicadores de progreso
                    const paso1Indicator = document.getElementById('paso-1-indicator');
                    if (paso1Indicator) {
                        paso1Indicator.style.background = '#3b82f6';
                        paso1Indicator.style.color = 'white';
                    }
                    
                    // Mostrar botón siguiente
                    const btnSiguiente = document.getElementById('wzd-btn-siguiente');
                    if (btnSiguiente) {
                        btnSiguiente.disabled = true;
                        btnSiguiente.style.display = 'flex';
                        console.log('[mostrarSelectoreTelasSiNecesario]  Botón Siguiente habilitado para género');
                    }
                }, 300);
            });
            
            botonesDiv.appendChild(btn);
        });
        
        selectorDiv.appendChild(stepCounter);
        selectorDiv.appendChild(titulo);
        selectorDiv.appendChild(descrip);
        selectorDiv.appendChild(botonesDiv);
        
        // Insertar el selector como primer elemento en vistaAsignacion
        // ANTES del indicador de progreso
        vistaAsignacion.insertBefore(selectorDiv, vistaAsignacion.firstChild);
        
        // Ocultar los pasos inicialmente (mostrarán cuando se seleccione tela)
        const paso1 = document.getElementById('wizard-paso-1');
        const paso2 = document.getElementById('wizard-paso-2');
        const paso3 = document.getElementById('wizard-paso-3');
        if (paso1) paso1.style.display = 'none';
        if (paso2) paso2.style.display = 'none';
        if (paso3) paso3.style.display = 'none';
        
        console.log('[mostrarSelectoreTelasSiNecesario]  Selector de telas mostrado como PASO 1');
        return null;
    }

    /**
     * Ocultar TALLAS Y CANTIDADES y mostrar RESUMEN
     */
    function ocultarTallasCantidadesYMostrarResumen() {
        const seccionTallasCantidades = document.getElementById('seccion-tallas-cantidades');
        const seccionResumenAsignaciones = document.getElementById('seccion-resumen-asignaciones');
        
        if (seccionTallasCantidades) seccionTallasCantidades.style.display = 'none';
        if (seccionResumenAsignaciones) seccionResumenAsignaciones.style.display = 'block';
        
        console.log('[ColoresPorTalla] Secciones ajustadas');
    }

    /**
     * Agregar un color personalizado directamente a las asignaciones
     */
    function agregarColorPersonalizado() {
        const generoSelect = document.getElementById('asignacion-genero-select');
        const tallaSelect = document.getElementById('asignacion-talla-select');
        const colorInput = document.getElementById('color-personalizado-input');
        const cantidadInput = document.getElementById('cantidad-color-personalizado');
        
        const genero = generoSelect ? generoSelect.value : '';
        const talla = tallaSelect ? tallaSelect.value : '';
        const color = colorInput ? colorInput.value.trim().toUpperCase() : '';
        const cantidad = cantidadInput ? parseInt(cantidadInput.value) || 1 : 1;
        
        // Intentar agregar la asignación
        const resultado = AsignacionManager.agregarColorPersonalizado(genero, talla, color, cantidad);
        
        if (resultado) {
            // Actualizar tabla y resumen
            UIRenderer.actualizarTablaAsignaciones();
            UIRenderer.actualizarResumenAsignaciones();
            UIRenderer.actualizarVisibilidadSeccionesResumen();
            
            // Limpiar inputs
            if (colorInput) colorInput.value = '';
            if (cantidadInput) cantidadInput.value = '1';
            
            // Efecto visual de confirmación
            const btn = event.target;
            const btnText = btn.textContent;
            btn.innerHTML = '<span class="material-symbols-rounded">check_circle</span> Agregado';
            btn.style.background = '#10b981';
            
            setTimeout(() => {
                btn.innerHTML = '<span class="material-symbols-rounded" style="font-size: 1.1rem;">add</span> <span>Agregar</span>';
                btn.style.background = '';
                btn.style.display = 'flex';
                btn.style.alignItems = 'center';
                btn.style.gap = '0.5rem';
            }, 2000);
            
            verificarBtnGuardarAsignacion();
        }
    }

    /**
     * Limpiar inputs de color personalizado
     */
    function limpiarColorPersonalizado() {
        const colorInput = document.getElementById('color-personalizado-input');
        const cantidadInput = document.getElementById('cantidad-color-personalizado');
        
        if (colorInput) colorInput.value = '';
        if (cantidadInput) cantidadInput.value = '1';
    }

    /**
     * Actualizar tallas disponibles según el género seleccionado
     */
    function actualizarTallasDisponibles() {
        console.log('[ColoresPorTalla] 🔵 Actualizando tallas disponibles...');
        
        const generoSelect = document.getElementById('asignacion-genero-select');
        const tallaSelect = document.getElementById('asignacion-talla-select');
        const listaColores = document.getElementById('lista-colores-checkboxes');
        const seccionPersonalizado = document.getElementById('seccion-agregar-color-personalizado');
        
        if (!generoSelect || !tallaSelect) {
            console.error('[ColoresPorTalla]  Elementos no encontrados');
            return;
        }
        
        const genero = generoSelect.value;
        console.log('[ColoresPorTalla] Género seleccionado:', genero);
        
        // Limpiar opciones de talla
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
            
            console.log('[ColoresPorTalla] Todas las tallas:', todasLasTallas);
            
            todasLasTallas.forEach(talla => {
                const option = document.createElement('option');
                option.value = talla;
                option.textContent = talla;
                tallaSelect.appendChild(option);
            });
            console.log('[ColoresPorTalla]  Tallas agregadas al select:', todasLasTallas.length);
        } else {
            console.warn('[ColoresPorTalla]  No hay tallas para género:', genero);
        }
        
        // Limpiar contenedor de colores - mostrar mensaje de instrucción
        if (listaColores) {
            listaColores.innerHTML = `
                <div style="color: #9ca3af; grid-column: 1/-1; text-align: center; padding: 2rem; background: #f9fafb; border: 1px dashed #d1d5db; border-radius: 8px;">
                    <span class="material-symbols-rounded" style="font-size: 2.5rem; display: block; margin-bottom: 0.5rem; color: #d1d5db;">arrow_forward</span>
                    <p style="margin: 0; font-weight: 500;">Selecciona una talla ▼</p>
                </div>
            `;
            console.log('[ColoresPorTalla]  Contenedor colores limpiado');
        }
        
        // Ocultar sección de color personalizado al cambiar género
        if (seccionPersonalizado) {
            seccionPersonalizado.style.display = 'none';
        }
        
        // Limpiar colores seleccionados
        const checkboxes = document.querySelectorAll('#lista-colores-checkboxes input[type="checkbox"]');
        checkboxes.forEach(cb => {
            cb.checked = false;
            const div = cb.closest('div');
            const cantidadDiv = div.querySelector('.cantidad-input-group');
            if (cantidadDiv) cantidadDiv.style.display = 'none';
        });
        
        limpiarColorPersonalizado();
        verificarBtnGuardarAsignacion();
        
        console.log('[ColoresPorTalla]  Completado para género:', genero);
    }

    /**
     * Actualizar colores disponibles cuando se selecciona una talla
     */
    function actualizarColoresDisponibles() {
        console.log('[ColoresPorTalla] 🔵 Actualizando colores disponibles...');
        
        const generoSelect = document.getElementById('asignacion-genero-select');
        const tallaSelect = document.getElementById('asignacion-talla-select');
        
        const genero = generoSelect ? generoSelect.value : '';
        const talla = tallaSelect ? tallaSelect.value : '';
        
        console.log('[ColoresPorTalla] Valores seleccionados:', { genero, talla });
        
        if (!genero || !talla) {
            console.warn('[ColoresPorTalla]  Género o talla no seleccionados:', { genero, talla });
            return;
        }
        
        try {
            // Recargar colores disponibles
            UIRenderer.cargarColoresDispAsignacion();
            console.log('[ColoresPorTalla]  cargarColoresDispAsignacion() ejecutada sin errores');
        } catch (error) {
            console.error('[ColoresPorTalla]  ERROR en cargarColoresDispAsignacion():', error.message, error);
        }
        
        // Si existe una asignación anterior, cargar los colores que ya tiene
        const tipo = StateManager.getTipoTallaSel();
        const clave = tipo ? `${genero}-${tipo}-${talla}` : `${genero}-${talla}`;
        const asignacion = AsignacionManager.obtenerAsignacion(genero, talla, tipo);
        
        const checkboxes = document.querySelectorAll('#lista-colores-checkboxes input[type="checkbox"]');
        
        if (asignacion && asignacion.colores && asignacion.colores.length > 0) {
            // Marcar los colores que ya están asignados
            checkboxes.forEach(cb => {
                const colorObj = asignacion.colores.find(c => c.nombre === cb.value);
                if (colorObj) {
                    cb.checked = true;
                    const div = cb.closest('div');
                    const cantidadDiv = div.querySelector('.cantidad-input-group');
                    const cantidadInput = cantidadDiv.querySelector('input');
                    cantidadDiv.style.display = 'flex';
                    cantidadInput.value = colorObj.cantidad || 1;
                } else {
                    cb.checked = false;
                    const div = cb.closest('div');
                    const cantidadDiv = div.querySelector('.cantidad-input-group');
                    if (cantidadDiv) cantidadDiv.style.display = 'none';
                }
            });
            
            console.log('[ColoresPorTalla] Colores cargados para:', clave, asignacion);
        } else {
            // Desmarcar todos si no hay asignación previa
            checkboxes.forEach(cb => {
                cb.checked = false;
                const div = cb.closest('div');
                const cantidadDiv = div.querySelector('.cantidad-input-group');
                if (cantidadDiv) cantidadDiv.style.display = 'none';
            });
        }
        
        // Limpiar input de color personalizado para talla nueva
        limpiarColorPersonalizado();
        
        verificarBtnGuardarAsignacion();
    }

    /**
     * Verificar si se puede mostrar el botón de guardar asignación
     */
    function verificarBtnGuardarAsignacion() {
        const generoSelect = document.getElementById('asignacion-genero-select');
        const tallaSelect = document.getElementById('asignacion-talla-select');
        const btnGuardar = document.getElementById('btn-guardar-asignacion');
        
        if (!btnGuardar) return;
        
        const genero = generoSelect ? generoSelect.value : '';
        const talla = tallaSelect ? tallaSelect.value : '';
        const hayColoresSeleccionados = document.querySelectorAll('#lista-colores-checkboxes input[type="checkbox"]:checked').length > 0;
        
        if (genero && talla && hayColoresSeleccionados) {
            btnGuardar.style.display = 'flex';
        } else {
            btnGuardar.style.display = 'none';
        }
    }

    /**
     * Guardar asignación de colores para la talla-género seleccionada
     */
    function guardarAsignacionColores() {
        console.log('[guardarAsignacionColores]  Iniciando guardado de asignaciones...');
        
        // Intentar obtener datos del wizard (StateManager) primero
        const genero = StateManager.getGeneroSeleccionado();
        const tallas = StateManager.getTallasSeleccionadas();
        
        // Si estamos en el contexto del wizard y ya se guardaron, simplemente cerrar
        if (genero && tallas && tallas.length > 0) {
            console.log('[guardarAsignacionColores]  Asignaciones del wizard guardadas. Cerrando vista...');
            // Las asignaciones ya fueron guardadas por wizardGuardarAsignacion
            // Solo necesitamos cerrar la vista
            window.toggleVistaAsignacionColores();
            return;
        }
        
        // Fallback: intentar leer de los inputs (para otros contextos que no sean el wizard)
        const generoSelect = document.getElementById('asignacion-genero-select');
        const tallaSelect = document.getElementById('asignacion-talla-select');
        
        if (generoSelect && tallaSelect && generoSelect.value && tallaSelect.value) {
            console.log('[guardarAsignacionColores]  Usando fallback de inputs (no wizard)');
            // Implementar lógica de fallback si es necesaria
            console.log('[guardarAsignacionColores]  Asignaciones guardadas (fallback)');
            return;
        }
        
        // Si ninguno de los dos funciona, es un error de contexto
        console.warn('[guardarAsignacionColores]  No se puede obtener datos de género/talla. Usa el wizard "Asignar por Talla".');
    }

    /**
     * Guardar asignación del wizard (múltiples tallas)
     */
    function wizardGuardarAsignacion() {
        console.log('[wizardGuardarAsignacion]  Iniciando guardado de asignaciones del wizard...');
        
        const genero = StateManager.getGeneroSeleccionado();
        const tallas = StateManager.getTallasSeleccionadas();
        const tipo = StateManager.getTipoTallaSel();
        let tela = StateManager.getTelaSeleccionada();  //  Obtener tela del wizard primero
        
        console.log('[wizardGuardarAsignacion] 📋 Estado actual:', {
            genero: genero,
            tallas: tallas,
            tipo: tipo,
            telaDelWizard: tela
        });
        
        // Validación básica
        if (!genero || !tallas || tallas.length === 0) {
            console.error('[wizardGuardarAsignacion]  Falta género o tallas');
            alert('Error: Faltan datos (género o tallas)');
            return false;
        }
        
        // Obtener la tela si no está en el wizard
        
        // Opción 1: Input manual (si está presente)
        const inputTela = document.getElementById('nueva-prenda-tela');
        if (inputTela && inputTela.value.trim()) {
            tela = inputTela.value.trim().toUpperCase();
            console.log('[wizardGuardarAsignacion]  Tela obtenida del input:', tela);
        }
        
        // Opción 2: Desde telasCreacion (flujo del wizard)
        if (!tela && window.telasCreacion && Array.isArray(window.telasCreacion) && window.telasCreacion.length > 0) {
            tela = window.telasCreacion[0].tela_nombre || window.telasCreacion[0].nombre_tela || window.telasCreacion[0].tela || '';
            console.log('[wizardGuardarAsignacion]  Tela obtenida del array telasCreacion:', tela);
        }
        
        if (!tela) {
            console.error('[wizardGuardarAsignacion]  No se encontró tela');
            alert('Error: No se encontró la tela');
            return false;
        }
        
        // Obtener todos los inputs de color y cantidad
        const inputsColor = document.querySelectorAll('.color-input-wizard');
        console.log('[wizardGuardarAsignacion]  Inputs de color encontrados:', inputsColor.length);
        
        if (inputsColor.length === 0) {
            console.warn('[wizardGuardarAsignacion]  No hay colores para guardar');
            alert('Por favor ingresa al menos un color para al menos una talla');
            return false;
        }
        
        // Agrupar colores por talla
        const asignacionesPorTalla = {};
        tallas.forEach(talla => {
            asignacionesPorTalla[talla] = [];
        });
        
        // Recorrer inputs de color y recolectar datos
        inputsColor.forEach((inputColor, index) => {
            const color = inputColor.value.trim().toUpperCase();
            const talla = inputColor.dataset.talla;
            
            console.log(`[wizardGuardarAsignacion] Input #${index}: color="${color}", talla="${talla}"`);
            
            if (color === '') return;  // Saltar si está vacío
            
            // Obtener la cantidad (el siguiente input de cantidad en la fila)
            const fila = inputColor.closest('div');
            const cantidadInput = fila.querySelector('.cantidad-input-wizard');
            const cantidad = cantidadInput ? parseInt(cantidadInput.value) || 1 : 1;
            
            console.log(`[wizardGuardarAsignacion]  Guardando: ${color} (cantidad: ${cantidad}) para talla ${talla}`);
            
            if (cantidad > 0) {
                asignacionesPorTalla[talla].push({
                    nombre: color,
                    cantidad: cantidad
                });
            }
        });
        
        console.log('[wizardGuardarAsignacion]  Asignaciones agrupadas:', asignacionesPorTalla);
        
        // Guardar asignaciones
        console.log('[wizardGuardarAsignacion] 💾 Llamando a AsignacionManager...');
        const resultado = AsignacionManager.guardarAsignacionesMultiples(genero, tallas, tipo, tela, asignacionesPorTalla);
        
        console.log('[wizardGuardarAsignacion] Resultado:', resultado);
        console.log('[wizardGuardarAsignacion] Asignaciones en StateManager:', StateManager.getAsignaciones());
        
        if (resultado) {
            console.log('[wizardGuardarAsignacion]  ÉXITO - Actualizando UI...');
            
            // Actualizar tablas de resumen
            console.log('[wizardGuardarAsignacion]  Llamando actualizarTablaAsignaciones()...');
            UIRenderer.actualizarTablaAsignaciones();
            
            console.log('[wizardGuardarAsignacion]  Llamando actualizarResumenAsignaciones()...');
            UIRenderer.actualizarResumenAsignaciones();
            
            console.log('[wizardGuardarAsignacion]  Llamando actualizarVisibilidadSeccionesResumen()...');
            UIRenderer.actualizarVisibilidadSeccionesResumen();
            
            console.log('[wizardGuardarAsignacion]  Asignaciones guardadas correctamente');
            
            // Resetear wizard y volver al paso 1
            console.log('[wizardGuardarAsignacion]  Reseteando wizard...');
            WizardManager.resetWizard();
            
            console.log('[wizardGuardarAsignacion]  FIN - Asignaciones guardadas y wizard reseteado');
            return true;
        } else {
            console.error('[wizardGuardarAsignacion]  Error al guardar asignaciones');
            alert('Error: No se pudieron guardar las asignaciones');
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
    }

    /**
     * Eliminar una asignación
     */
    function eliminarAsignacion(genero, talla, color) {
        const resultado = AsignacionManager.eliminarAsignacion(genero, talla, color);
        if (resultado) {
            UIRenderer.actualizarTablaAsignaciones();
            UIRenderer.actualizarResumenAsignaciones();
            UIRenderer.actualizarVisibilidadSeccionesResumen();
        }
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
        UIRenderer.actualizarVisibilidadSeccionesResumen();
    }

    // API pública
    return {
        init: init,
        toggleVistaAsignacion: toggleVistaAsignacion,
        agregarColorPersonalizado: agregarColorPersonalizado,
        limpiarColorPersonalizado: limpiarColorPersonalizado,
        actualizarTallasDisponibles: actualizarTallasDisponibles,
        actualizarColoresDisponibles: actualizarColoresDisponibles,
        verificarBtnGuardarAsignacion: verificarBtnGuardarAsignacion,
        guardarAsignacionColores: guardarAsignacionColores,
        wizardGuardarAsignacion: wizardGuardarAsignacion,
        actualizarCantidadAsignacion: actualizarCantidadAsignacion,
        eliminarAsignacion: eliminarAsignacion,
        obtenerDatosAsignaciones: obtenerDatosAsignaciones,
        limpiarAsignaciones: limpiarAsignaciones,
        cargarAsignacionesPrevias: cargarAsignacionesPrevias,
        
        // Exponer referencias a los módulos internos para compatibilidad
        StateManager: window.StateManager,
        AsignacionManager: window.AsignacionManager,
        WizardManager: window.WizardManager,
        UIRenderer: window.UIRenderer
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
