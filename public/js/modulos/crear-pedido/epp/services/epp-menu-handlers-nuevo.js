/**
 * EppMenuHandlersNuevo - Manejo de menús exclusivo para vista de crear nuevo pedido
 * Versión independiente para /asesores/pedidos-editable/crear-nuevo
 * VERSIÓN 2.1 - CON SOLUCIÓN DEFINITIVA PARA TOGGLE MENU
 */

// 🔍 LOG GLOBAL: Verificar que el script se cargue
console.log('[EppMenuHandlersNuevo] 📦 Script cargado - VERSIÓN 2.1 - Iniciando creación de clase');

class EppMenuHandlersNuevo {
    constructor() {
        console.log('[EppMenuHandlersNuevo] 🏗️ Constructor iniciado');
        this.inicializado = false;
        this.observer = null;
        this.eventListenersConfigurados = new Set(); // 🔧 SOLUCIÓN: Track listeners configurados
        this.inicializar();
        this.setupMutationObserver();
        console.log('[EppMenuHandlersNuevo] ✅ Constructor completado');
    }

    inicializar() {
        console.log('[EppMenuHandlersNuevo] 🔄 Inicializando...');
        if (!this.inicializado) {
            console.log('[EppMenuHandlersNuevo] 📋 Primer inicialización - configurando listeners');
            this.attachEventListeners();
            this.inicializado = true;
            console.log('[EppMenuHandlersNuevo] ✅ Inicializado correctamente');
        } else {
            console.warn('[EppMenuHandlersNuevo] ⚠️ Ya fue inicializado, evitando duplicación');
        }
    }

    setupMutationObserver() {
        // 🔧 SOLUCIÓN: Observer para detectar nuevos EPPs dinámicos
        this.observer = new MutationObserver((mutations) => {
            let nuevosEPPsDetectados = false;
            
            mutations.forEach((mutation) => {
                if (mutation.type === 'childList') {
                    // Buscar nuevos elementos .item-epp-card-nuevo
                    mutation.addedNodes.forEach((node) => {
                        if (node.nodeType === Node.ELEMENT_NODE) {
                            if (node.classList.contains('item-epp-card-nuevo')) {
                                nuevosEPPsDetectados = true;
                                console.log('[EppMenuHandlersNuevo] Nuevo EPP detectado:', node);
                                this.setupEPPMenu(node);
                            }
                            
                            // También buscar dentro del nodo si es un contenedor
                            const nuevosEPPsEnContenedor = node.querySelectorAll('.item-epp-card-nuevo');
                            nuevosEPPsEnContenedor.forEach((epp) => {
                                nuevosEPPsDetectados = true;
                                console.log('[EppMenuHandlersNuevo] Nuevo EPP en contenedor:', epp);
                                this.setupEPPMenu(epp);
                            });
                        }
                    });
                }
            });
            
            if (nuevosEPPsDetectados) {
                console.log('[EppMenuHandlersNuevo] Re-inicializando menús para nuevos EPPs...');
                setTimeout(() => {
                    this.verificarTodosLosBotones();
                }, 100);
            }
        });

        // Observar el contenedor principal de EPPs
        const contenedorEPPs = document.getElementById('lista-items-pedido');
        if (contenedorEPPs) {
            this.observer.observe(contenedorEPPs, {
                childList: true,
                subtree: true
            });
            console.log('[EppMenuHandlersNuevo] MutationObserver configurado para:', contenedorEPPs);
        } else {
            console.warn('[EppMenuHandlersNuevo] No se encontró contenedor #lista-items-pedido');
        }
    }

    setupEPPMenu(eppElement) {
        // 🔧 SOLUCIÓN: Configurar menú para un EPP específico SIN duplicar listeners
        const botonMenu = eppElement.querySelector('.btn-menu-epp-nuevo');
        if (botonMenu) {
            const itemId = botonMenu.getAttribute('data-item-id');
            
            // 🔧 SOLUCIÓN: Evitar configurar listeners duplicados
            if (this.eventListenersConfigurados.has(itemId)) {
                console.log(`[EppMenuHandlersNuevo] Listener ya configurado para ${itemId}, omitiendo`);
                return;
            }
            
            // Asegurar que el botón tenga pointer-events
            const computedStyle = window.getComputedStyle(botonMenu);
            if (computedStyle.pointerEvents === 'none') {
                botonMenu.style.pointerEvents = 'auto';
                console.log('[EppMenuHandlersNuevo] Corrigiendo pointer-events para nuevo EPP');
            }
            
            // Verificar que el submenú exista
            const submenu = eppElement.querySelector(`.submenu-epp-nuevo[data-item-id="${itemId}"]`);
            if (!submenu) {
                console.warn('[EppMenuHandlersNuevo] No se encontró submenú para nuevo EPP:', itemId);
            } else {
                // 🔧 SOLUCIÓN: Asegurar que el menú esté oculto inicialmente
                submenu.style.display = 'none';
                console.log(`[EppMenuHandlersNuevo] Menú ${itemId} oculto inicialmente`);
            }
            
            // Marcar como configurado
            this.eventListenersConfigurados.add(itemId);
        }
    }

    verificarTodosLosBotones() {
        // 🔧 SOLUCIÓN: Verificar y corregir todos los botones SIN duplicar
        const botonesMenu = document.querySelectorAll('.btn-menu-epp-nuevo');
        console.log(`[EppMenuHandlersNuevo] Verificando ${botonesMenu.length} botones de menú...`);
        
        // 🔧 SOLUCIÓN: Limpiar menús abiertos antes de verificar
        this.cerrarTodosLosMenus();
        
        botonesMenu.forEach((btn, index) => {
            const itemId = btn.getAttribute('data-item-id');
            
            const computedStyle = window.getComputedStyle(btn);
            if (computedStyle.pointerEvents === 'none') {
                console.warn(`[EppMenuHandlersNuevo] Corrigiendo pointer-events del botón ${index + 1}`);
                btn.style.pointerEvents = 'auto';
            }
            
            // Verificar que el submenú correspondiente exista y esté oculto
            const submenu = document.querySelector(`.submenu-epp-nuevo[data-item-id="${itemId}"]`);
            if (!submenu) {
                console.warn(`[EppMenuHandlersNuevo] No se encontró submenú para botón ${index + 1} con ID: ${itemId}`);
            } else {
                // 🔧 SOLUCIÓN: Forzar que todos los menús estén ocultos
                submenu.style.display = 'none';
                
                // Marcar como configurado si no está en el Set
                if (!this.eventListenersConfigurados.has(itemId)) {
                    this.eventListenersConfigurados.add(itemId);
                }
            }
        });
    }

    attachEventListeners() {
        console.log('[EppMenuHandlersNuevo] 🎧 Configurando event listeners...');
        
        // Event delegation para menús dinámicos
        document.addEventListener('click', (e) => {
            // 🔍 LOG COMPLETO: Capturar todos los clicks para diagnóstico
            console.log('[EppMenuHandlersNuevo] Click detectado en:', {
                target: e.target,
                tagName: e.target.tagName,
                className: e.target.className,
                id: e.target.id,
                textContent: e.target.textContent?.substring(0, 20),
                parentElement: e.target.parentElement?.tagName,
                parentClassName: e.target.parentElement?.className,
                timestamp: new Date().toISOString()
            });
            
            // 🔍 DIAGNÓSTICO: Verificar si es un botón relacionado con EPP
            const esBotonEPP = e.target.tagName === 'BUTTON' && 
                               (e.target.className.includes('epp') || 
                                e.target.getAttribute('data-item-id')?.includes('epp') ||
                                e.target.textContent === '⋮' ||
                                e.target.closest('.item-epp-card-nuevo'));
            
            // 🔍 DIAGNÓSTICO MEJORADO: También verificar si está dentro de una tarjeta EPP
            const estaEnTarjetaEPP = e.target.closest('.item-epp-card-nuevo');
            const tieneDataItemId = e.target.getAttribute('data-item-id');
            const tieneClaseBtnMenu = e.target.classList.contains('btn-menu-epp-nuevo');
            
            // 🔍 DIAGNÓSTICO ESPECÍFICO: Para botones que deberían ser detectados
            const esBotonConDataItemId = tieneDataItemId && tieneDataItemId.includes('epp');
            const esBotonConTextoPuntos = e.target.textContent === '⋮';
            const esBotonDentroDeTarjeta = !!estaEnTarjetaEPP;
            
            // 🔍 LOG ESPECIAL: Para todos los botones con data-item-id que contenga 'epp'
            if (esBotonConDataItemId || esBotonConTextoPuntos || esBotonDentroDeTarjeta) {
                console.log('[EppMenuHandlersNuevo] 🔍 BOTÓN EPP ESPECÍFICO DETECTADO:', {
                    tagName: e.target.tagName,
                    className: e.target.className,
                    tieneClaseBtnMenu: tieneClaseBtnMenu,
                    dataItemId: tieneDataItemId,
                    textContent: e.target.textContent,
                    clasesCompletas: Array.from(e.target.classList),
                    esBotonEPP: esBotonEPP,
                    estaEnTarjetaEPP: !!estaEnTarjetaEPP,
                    tarjetaPadre: estaEnTarjetaEPP ? {
                        itemId: estaEnTarjetaEPP.getAttribute('data-epp-id'),
                        originalId: estaEnTarjetaEPP.getAttribute('data-epp-original-id')
                    } : null,
                    criterios: {
                        esBotonConDataItemId: esBotonConDataItemId,
                        esBotonConTextoPuntos: esBotonConTextoPuntos,
                        esBotonDentroDeTarjeta: esBotonDentroDeTarjeta,
                        tieneClaseBtnMenu: tieneClaseBtnMenu
                    }
                });
            }
            
            if (esBotonEPP || estaEnTarjetaEPP) {
                console.log('[EppMenuHandlersNuevo] 🔍 Botón EPP detectado (diagnóstico mejorado):', {
                    tagName: e.target.tagName,
                    className: e.target.className,
                    tieneClaseBtnMenu: e.target.classList.contains('btn-menu-epp-nuevo'),
                    dataItemId: tieneDataItemId,
                    textContent: e.target.textContent,
                    clasesCompletas: Array.from(e.target.classList),
                    esBotonEPP: esBotonEPP,
                    estaEnTarjetaEPP: !!estaEnTarjetaEPP,
                    tarjetaPadre: estaEnTarjetaEPP ? {
                        itemId: estaEnTarjetaEPP.getAttribute('data-epp-id'),
                        originalId: estaEnTarjetaEPP.getAttribute('data-epp-original-id')
                    } : null
                });
            }
            
            // Botón del menú
            if (e.target.classList.contains('btn-menu-epp-nuevo')) {
                console.log('[EppMenuHandlersNuevo] 🎯 Click en botón menú EPP detectado!');
                console.log('[EppMenuHandlersNuevo] 🔍 VERSIÓN 2.1 - Ejecutando nueva lógica de búsqueda de botón real');
                e.preventDefault();
                e.stopPropagation();
                
                // 🔧 SOLUCIÓN: Si el target es un DIV, buscar el botón real dentro
                let botonReal = e.target;
                let itemId = botonReal.getAttribute('data-item-id');
                
                console.log('[EppMenuHandlersNuevo] 🔍 Análisis inicial del target:', {
                    tagName: e.target.tagName,
                    className: e.target.className,
                    itemId: itemId,
                    tieneDataItemId: !!itemId
                });
                
                // Si es un DIV sin data-item-id, buscar el botón dentro
                if (e.target.tagName === 'DIV' && !itemId) {
                    console.log('[EppMenuHandlersNuevo] 🔍 Target es DIV sin data-item-id, buscando botón real...');
                    const botonDentro = e.target.querySelector('button[data-item-id*="epp"]');
                    if (botonDentro) {
                        botonReal = botonDentro;
                        itemId = botonReal.getAttribute('data-item-id');
                        console.log('[EppMenuHandlersNuevo] ✅ Botón real encontrado:', {
                            boton: botonReal,
                            itemId: itemId,
                            tagName: botonReal.tagName,
                            className: botonReal.className
                        });
                    } else {
                        console.warn('[EppMenuHandlersNuevo] ⚠️ No se encontró botón con data-item-id dentro del DIV');
                        // Buscar cualquier botón dentro
                        const cualquierBoton = e.target.querySelector('button');
                        if (cualquierBoton) {
                            console.log('[EppMenuHandlersNuevo] 🔍 Botón encontrado (sin data-item-id):', cualquierBoton);
                            botonReal = cualquierBoton;
                            itemId = botonReal.getAttribute('data-item-id');
                        }
                    }
                } else {
                    console.log('[EppMenuHandlersNuevo] 🔍 Target ya tiene data-item-id o no es DIV:', {
                        tagName: e.target.tagName,
                        itemId: itemId
                    });
                }
                
                // 🔍 LOG DETALLADO: Información completa del botón
                console.log('[EppMenuHandlersNuevo] === CLICK EN BOTÓN MENÚ EPP ===');
                console.log('[EppMenuHandlersNuevo] Datos del botón:', {
                    button: botonReal,
                    itemId: itemId,
                    buttonText: botonReal.textContent,
                    tagName: botonReal.tagName,
                    className: botonReal.className,
                    parentElement: botonReal.parentElement,
                    computedStyle: {
                        pointerEvents: window.getComputedStyle(botonReal).pointerEvents,
                        display: window.getComputedStyle(botonReal).display,
                        zIndex: window.getComputedStyle(botonReal).zIndex,
                        position: window.getComputedStyle(botonReal).position
                    },
                    eventListenersConfigurados: Array.from(this.eventListenersConfigurados),
                    timestamp: new Date().toISOString()
                });
                
                // 🔍 LOG: Verificar submenú correspondiente
                const submenu = document.querySelector(`.submenu-epp-nuevo[data-item-id="${itemId}"]`);
                console.log('[EppMenuHandlersNuevo] Submenú correspondiente:', {
                    encontrado: !!submenu,
                    itemId: itemId,
                    displayActual: submenu ? window.getComputedStyle(submenu).display : 'N/A',
                    visibilityActual: submenu ? window.getComputedStyle(submenu).visibility : 'N/A',
                    zIndexActual: submenu ? window.getComputedStyle(submenu).zIndex : 'N/A',
                    positionActual: submenu ? window.getComputedStyle(submenu).position : 'N/A',
                    htmlCompleto: submenu ? submenu.outerHTML.substring(0, 200) + '...' : 'N/A'
                });
                
                // 🔍 LOG: Verificar todos los menús existentes
                const todosLosMenus = document.querySelectorAll('.submenu-epp-nuevo');
                console.log('[EppMenuHandlersNuevo] Todos los menús existentes:', {
                    total: todosLosMenus.length,
                    estados: Array.from(todosLosMenus).map(menu => ({
                        itemId: menu.getAttribute('data-item-id'),
                        display: window.getComputedStyle(menu).display,
                        visibility: window.getComputedStyle(menu).visibility,
                        zIndex: window.getComputedStyle(menu).zIndex
                    }))
                });
                
                // 🔧 SOLUCIÓN: Forzar el evento si hay problemas de pointer-events
                const computedStyle = window.getComputedStyle(botonReal);
                if (computedStyle.pointerEvents === 'none') {
                    console.warn('[EppMenuHandlersNuevo] ⚠️ pointer-events está deshabilitado, forzando evento');
                    botonReal.style.pointerEvents = 'auto';
                    console.log('[EppMenuHandlersNuevo] ✅ pointer-events corregido a auto');
                }
                
                console.log('[EppMenuHandlersNuevo] 🔄 Llamando a toggleMenu...');
                this.toggleMenu(botonReal);
                console.log('[EppMenuHandlersNuevo] === FIN CLICK EN BOTÓN MENÚ EPP ===');
            } else if ((esBotonEPP || estaEnTarjetaEPP) && !tieneClaseBtnMenu) {
                // 🔍 DIAGNÓSTICO: Si es un botón EPP pero no tiene la clase correcta
                console.warn('[EppMenuHandlersNuevo] ⚠️ Botón EPP detectado pero sin clase btn-menu-epp-nuevo');
                console.log('[EppMenuHandlersNuevo] Clases actuales:', Array.from(e.target.classList));
                console.log('[EppMenuHandlersNuevo] Intentando agregar clase faltante...');
                
                // 🔧 SOLUCIÓN: Agregar la clase faltante
                e.target.classList.add('btn-menu-epp-nuevo');
                console.log('[EppMenuHandlersNuevo] ✅ Clase btn-menu-epp-nuevo agregada');
                console.log('[EppMenuHandlersNuevo] Nuevas clases:', Array.from(e.target.classList));
                
                // Volver a verificar
                if (e.target.classList.contains('btn-menu-epp-nuevo')) {
                    console.log('[EppMenuHandlersNuevo] 🎯 Ahora sí tiene la clase correcta, procesando...');
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const itemId = e.target.getAttribute('data-item-id');
                    console.log('[EppMenuHandlersNuevo] === CLICK EN BOTÓN MENÚ EPP (CORREGIDO) ===');
                    console.log('[EppMenuHandlersNuevo] Llamando a toggleMenu con itemId:', itemId);
                    this.toggleMenu(e.target);
                    console.log('[EppMenuHandlersNuevo] === FIN CLICK EN BOTÓN MENÚ EPP (CORREGIDO) ===');
                } else {
                    console.error('[EppMenuHandlersNuevo] ❌ No se pudo agregar la clase btn-menu-epp-nuevo');
                }
            } else if (esBotonConDataItemId || esBotonConTextoPuntos || esBotonDentroDeTarjeta) {
                // 🔍 DIAGNÓSTICO ESPECIAL: Para botones que cumplen criterios específicos pero no fueron capturados
                console.warn('[EppMenuHandlersNuevo] ⚠️ Botón EPP específico detectado pero no procesado');
                console.log('[EppMenuHandlersNuevo] Criterios cumplidos:', {
                    esBotonConDataItemId: esBotonConDataItemId,
                    esBotonConTextoPuntos: esBotonConTextoPuntos,
                    esBotonDentroDeTarjeta: esBotonDentroDeTarjeta,
                    tieneClaseBtnMenu: tieneClaseBtnMenu
                });
                
                if (!tieneClaseBtnMenu) {
                    console.log('[EppMenuHandlersNuevo] Agregando clase btn-menu-epp-nuevo...');
                    e.target.classList.add('btn-menu-epp-nuevo');
                    
                    if (e.target.classList.contains('btn-menu-epp-nuevo')) {
                        console.log('[EppMenuHandlersNuevo] ✅ Clase agregada, procesando click...');
                        e.preventDefault();
                        e.stopPropagation();
                        
                        const itemId = e.target.getAttribute('data-item-id');
                        console.log('[EppMenuHandlersNuevo] === CLICK EN BOTÓN MENÚ EPP (ESPECÍFICO CORREGIDO) ===');
                        console.log('[EppMenuHandlersNuevo] Llamando a toggleMenu con itemId:', itemId);
                        this.toggleMenu(e.target);
                        console.log('[EppMenuHandlersNuevo] === FIN CLICK EN BOTÓN MENÚ EPP (ESPECÍFICO CORREGIDO) ===');
                    }
                } else {
                    console.log('[EppMenuHandlersNuevo] Ya tiene la clase correcta, procesando directamente...');
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const itemId = e.target.getAttribute('data-item-id');
                    console.log('[EppMenuHandlersNuevo] === CLICK EN BOTÓN MENÚ EPP (ESPECÍFICO DIRECTO) ===');
                    console.log('[EppMenuHandlersNuevo] Llamando a toggleMenu con itemId:', itemId);
                    this.toggleMenu(e.target);
                    console.log('[EppMenuHandlersNuevo] === FIN CLICK EN BOTÓN MENÚ EPP (ESPECÍFICO DIRECTO) ===');
                }
            }

            // Opción de editar
            if (e.target.classList.contains('btn-editar-epp-nuevo')) {
                e.preventDefault();
                e.stopPropagation();
                console.log('[EppMenuHandlersNuevo] === CLICK EN BOTÓN EDITAR EPP ===');
                console.log('[EppMenuHandlersNuevo] Datos del botón editar:', {
                    button: e.target,
                    itemId: e.target.getAttribute('data-item-id'),
                    buttonText: e.target.textContent,
                    parentMenu: e.target.closest('.submenu-epp-nuevo'),
                    timestamp: new Date().toISOString()
                });
                this.editarEPP(e.target);
                console.log('[EppMenuHandlersNuevo] === FIN CLICK EN BOTÓN EDITAR EPP ===');
            }

            // Opción de eliminar
            if (e.target.classList.contains('btn-eliminar-epp-nuevo')) {
                e.preventDefault();
                e.stopPropagation();
                console.log('[EppMenuHandlersNuevo] === CLICK EN BOTÓN ELIMINAR EPP ===');
                console.log('[EppMenuHandlersNuevo] Datos del botón eliminar:', {
                    button: e.target,
                    itemId: e.target.getAttribute('data-item-id'),
                    buttonText: e.target.textContent,
                    parentMenu: e.target.closest('.submenu-epp-nuevo'),
                    timestamp: new Date().toISOString()
                });
                this.eliminarEPP(e.target);
                console.log('[EppMenuHandlersNuevo] === FIN CLICK EN BOTÓN ELIMINAR EPP ===');
            }
        });

        // Cerrar menús al hacer clic fuera
        document.addEventListener('click', (e) => {
            // 🔍 LOG: Click fuera de menús
            const clickEnMenu = e.target.closest('.submenu-epp-nuevo') || e.target.closest('.btn-menu-epp-nuevo');
            if (!clickEnMenu) {
                console.log('[EppMenuHandlersNuevo] Click fuera detectado, cerrando menús');
                this.cerrarTodosLosMenus();
            }
        });
        
        console.log('[EppMenuHandlersNuevo] ✅ Event listeners configurados correctamente');
    }

    toggleMenu(btn) {
        const itemId = btn.getAttribute('data-item-id');
        console.log('[EppMenuHandlersNuevo] === INICIO TOGGLE MENU ===');
        console.log(`[EppMenuHandlersNuevo] toggleMenu llamado con itemId: ${itemId}`);
        console.log(`[EppMenuHandlersNuevo] Botón que activó el menú:`, btn);
        
        // 🔍 LOG: Estado actual del botón
        console.log('[EppMenuHandlersNuevo] Estado del botón:', {
            itemId: itemId,
            computedStyle: {
                pointerEvents: window.getComputedStyle(btn).pointerEvents,
                display: window.getComputedStyle(btn).display,
                zIndex: window.getComputedStyle(btn).zIndex
            }
        });
        
        const submenu = document.querySelector(`.submenu-epp-nuevo[data-item-id="${itemId}"]`);
        console.log(`[EppMenuHandlersNuevo] Submenú encontrado:`, !!submenu);
        
        if (!submenu) {
            console.error(`[EppMenuHandlersNuevo] ❌ No se encontró el submenú para itemId: ${itemId}`);
            console.log('[EppMenuHandlersNuevo] Buscando todos los submenús existentes:');
            document.querySelectorAll('.submenu-epp-nuevo').forEach((menu, index) => {
                console.log(`  Submenú ${index + 1}:`, {
                    itemId: menu.getAttribute('data-item-id'),
                    display: window.getComputedStyle(menu).display,
                    visibility: window.getComputedStyle(menu).visibility
                });
            });
            return;
        }

        // 🔍 LOG: Estado actual del submenú
        console.log('[EppMenuHandlersNuevo] Estado actual del submenú:', {
            itemId: itemId,
            displayActual: submenu.style.display,
            computedDisplay: window.getComputedStyle(submenu).display,
            visibility: window.getComputedStyle(submenu).visibility,
            zIndex: window.getComputedStyle(submenu).zIndex,
            position: window.getComputedStyle(submenu).position
        });

        // Cerrar otros menús
        console.log(`[EppMenuHandlersNuevo] Cerrando otros menús...`);
        const otrosMenus = document.querySelectorAll('.submenu-epp-nuevo:not([data-item-id="' + itemId + '"])');
        console.log(`[EppMenuHandlersNuevo] Encontrados ${otrosMenus.length} otros menús para cerrar`);
        
        this.cerrarTodosLosMenus();
        
        // 🔍 LOG: Verificar que otros menús se cerraron
        setTimeout(() => {
            const menusDespuesDeCerrar = document.querySelectorAll('.submenu-epp-nuevo');
            console.log('[EppMenuHandlersNuevo] Estado de menús después de cerrar otros:', {
                total: menusDespuesDeCerrar.length,
                estados: Array.from(menusDespuesDeCerrar).map(menu => ({
                    itemId: menu.getAttribute('data-item-id'),
                    display: window.getComputedStyle(menu).display,
                    esElActual: menu.getAttribute('data-item-id') === itemId
                }))
            });
        }, 10);

        // 🔧 SOLUCIÓN V2.1: Siempre abrir el menú cuando se llama desde click (no hacer toggle)
        const isVisible = submenu.style.display === 'flex' || window.getComputedStyle(submenu).display === 'flex';
        
        // Si el menú ya está visible, lo cerramos (comportamiento toggle normal)
        // Si el menú está oculto, lo abrimos (comportamiento normal)
        // Pero si fue cerrado por cerrarTodosLosMenus(), lo abrimos
        
        // Verificar si el menú fue cerrado por cerrarTodosLosMenus()
        setTimeout(() => {
            const estadoDespuesDeCerrar = window.getComputedStyle(submenu).display;
            const fueCerradoPorCerrarTodos = estadoDespuesDeCerrar === 'none' && isVisible;
            
            if (fueCerradoPorCerrarTodos) {
                // Fue cerrado por cerrarTodosLosMenus(), lo reabrimos
                submenu.style.display = 'flex';
                console.log(`[EppMenuHandlersNuevo] 🔄 Menú ${itemId} reabierto (fue cerrado por cerrarTodosLosMenus)`);
            } else if (isVisible) {
                // Estaba visible y no fue cerrado por cerrarTodosLosMenus(), lo cerramos
                submenu.style.display = 'none';
                console.log(`[EppMenuHandlersNuevo] 🔄 Menú ${itemId} cerrado (toggle normal)`);
            } else {
                // Estaba oculto, lo abrimos
                submenu.style.display = 'flex';
                console.log(`[EppMenuHandlersNuevo] 🔄 Menú ${itemId} abierto (toggle normal)`);
            }
            
            console.log(`[EppMenuHandlersNuevo] Estado display del menú: ${submenu.style.display}`);
        }, 5); // Pequeño delay para asegurar que cerrarTodosLosMenus() se ejecute primero
        
        // 🔍 LOG: Verificación final
        setTimeout(() => {
            const estadoFinal = window.getComputedStyle(submenu).display;
            console.log('[EppMenuHandlersNuevo] Estado final verificado:', {
                itemId: itemId,
                displayFinal: estadoFinal,
                esVisible: estadoFinal === 'flex',
                timestamp: new Date().toISOString()
            });
            
            // 🔍 LOG: Obtener información de la tarjeta EPP asociada
            const tarjeta = document.querySelector(`.item-epp-card-nuevo[data-epp-id="${itemId}"]`);
            if (tarjeta) {
                const eppOriginalId = tarjeta.getAttribute('data-epp-original-id');
                const nombreEPP = tarjeta.querySelector('h4')?.textContent;
                console.log('[EppMenuHandlersNuevo] Tarjeta EPP asociada:', {
                    tarjetaId: itemId,
                    eppOriginalId: eppOriginalId,
                    nombreEPP: nombreEPP,
                    cantidad: tarjeta.querySelector('p:nth-child(1)')?.textContent
                });
            } else {
                console.warn(`[EppMenuHandlersNuevo] No se encontró la tarjeta EPP para itemId: ${itemId}`);
            }
            
            console.log('[EppMenuHandlersNuevo] === FIN TOGGLE MENU ===');
        }, 50);
    }

    cerrarTodosLosMenus() {
        console.log('[EppMenuHandlersNuevo] === INICIO CERRAR TODOS LOS MENÚS ===');
        const todosLosMenus = document.querySelectorAll('.submenu-epp-nuevo');
        console.log(`[EppMenuHandlersNuevo] Encontrados ${todosLosMenus.length} menús para cerrar`);
        
        if (todosLosMenus.length === 0) {
            console.log('[EppMenuHandlersNuevo] No hay menús para cerrar');
            console.log('[EppMenuHandlersNuevo] === FIN CERRAR TODOS LOS MENÚS ===');
            return;
        }
        
        // 🔍 LOG: Estado antes de cerrar
        console.log('[EppMenuHandlersNuevo] Estado de menús ANTES de cerrar:');
        todosLosMenus.forEach((menu, index) => {
            const itemId = menu.getAttribute('data-item-id');
            const display = window.getComputedStyle(menu).display;
            const visibility = window.getComputedStyle(menu).visibility;
            console.log(`  Menú ${index + 1} (${itemId}): display=${display}, visibility=${visibility}`);
        });
        
        // Cerrar todos los menús
        todosLosMenus.forEach((menu, index) => {
            const itemId = menu.getAttribute('data-item-id');
            const displayAntes = window.getComputedStyle(menu).display;
            
            menu.style.display = 'none';
            
            const displayDespues = window.getComputedStyle(menu).display;
            console.log(`[EppMenuHandlersNuevo] Menú ${index + 1} (${itemId}): ${displayAntes} → ${displayDespues}`);
        });
        
        // 🔍 LOG: Verificación después de cerrar
        setTimeout(() => {
            console.log('[EppMenuHandlersNuevo] Verificación DESPUÉS de cerrar:');
            todosLosMenus.forEach((menu, index) => {
                const itemId = menu.getAttribute('data-item-id');
                const display = window.getComputedStyle(menu).display;
                const visibility = window.getComputedStyle(menu).visibility;
                console.log(`  Menú ${index + 1} (${itemId}): display=${display}, visibility=${visibility}`);
            });
            
            const menusVisibles = Array.from(todosLosMenus).filter(menu => 
                window.getComputedStyle(menu).display !== 'none'
            );
            
            console.log(`[EppMenuHandlersNuevo] ✅ Todos los menús cerrados. Menús aún visibles: ${menusVisibles.length}`);
            if (menusVisibles.length > 0) {
                console.warn('[EppMenuHandlersNuevo] ⚠️ Algunos menús siguen visibles:', menusVisibles.map(m => m.getAttribute('data-item-id')));
            }
            
            console.log('[EppMenuHandlersNuevo] === FIN CERRAR TODOS LOS MENÚS ===');
        }, 10);
    }

    editarEPP(btn) {
        const tarjetaId = btn.getAttribute('data-item-id');
        console.log(`[EppMenuHandlersNuevo] Editando EPP con tarjetaId: ${tarjetaId}`);
        console.log(`[EppMenuHandlersNuevo] Botón clickeado:`, btn);

        // Obtener el ID original del EPP desde la tarjeta
        const tarjeta = document.querySelector(`.item-epp-card-nuevo[data-epp-id="${tarjetaId}"]`);
        console.log(`[EppMenuHandlersNuevo] Tarjeta encontrada:`, tarjeta);
        
        if (!tarjeta) {
            console.error(`[EppMenuHandlersNuevo] Tarjeta no encontrada: ${tarjetaId}`);
            console.log(`[EppMenuHandlersNuevo] Tarjetas disponibles:`, document.querySelectorAll('.item-epp-card-nuevo'));
            return;
        }

        const eppOriginalId = tarjeta.getAttribute('data-epp-original-id');
        console.log(`[EppMenuHandlersNuevo] ID original del EPP: ${eppOriginalId}`);
        console.log(`[EppMenuHandlersNuevo] Atributos de la tarjeta:`, {
            'data-epp-id': tarjeta.getAttribute('data-epp-id'),
            'data-epp-original-id': tarjeta.getAttribute('data-epp-original-id'),
            'data-pedido-epp-id': tarjeta.getAttribute('data-pedido-epp-id')
        });

        // Mostrar contenido de window.itemsPedido para debugging
        console.log(`[EppMenuHandlersNuevo] window.itemsPedido:`, window.itemsPedido);
        console.log(`[EppMenuHandlersNuevo] Items EPP en window.itemsPedido:`, window.itemsPedido?.filter(item => item.tipo === 'epp'));

        // Buscar el EPP en window.itemsPedido usando el ID original
        const epp = window.itemsPedido?.find(item => 
            item.tipo === 'epp' && (String(item.epp_id) === String(eppOriginalId) || String(item.id) === String(eppOriginalId))
        );

        console.log(`[EppMenuHandlersNuevo] EPP encontrado:`, epp);
        console.log(`[EppMenuHandlersNuevo] Búsqueda realizada con:`, {
            eppOriginalId: eppOriginalId,
            tipo: 'epp',
            itemsEncontrados: window.itemsPedido?.filter(item => 
                item.tipo === 'epp' && (String(item.epp_id) === String(eppOriginalId) || String(item.id) === String(eppOriginalId))
            )
        });

        if (!epp) {
            console.warn(`[EppMenuHandlersNuevo] EPP no encontrado con ID original: ${eppOriginalId}`, {
                itemsPedido: window.itemsPedido,
                eppOriginalId: eppOriginalId,
                tarjetaId: tarjetaId
            });
            return;
        }

        console.log(`[EppMenuHandlersNuevo] EPP encontrado:`, epp);

        // Cerrar menú
        this.cerrarTodosLosMenus();

        // Verificar funciones disponibles
        console.log(`[EppMenuHandlersNuevo] Funciones disponibles:`, {
            'abrirModalEditarEPPNuevo': typeof window.abrirModalEditarEPPNuevo,
            'abrirModalEditarEPP': typeof window.abrirModalEditarEPP
        });

        // Abrir modal de edición con los datos del EPP (usar función exclusiva para nuevo pedido)
        if (typeof window.abrirModalEditarEPPNuevo === 'function') {
            console.log(`[EppMenuHandlersNuevo] Llamando a abrirModalEditarEPPNuevo`);
            window.abrirModalEditarEPPNuevo(epp);
        } else if (typeof window.abrirModalEditarEPP === 'function') {
            // Fallback a la función genérica si la específica no existe
            console.log(`[EppMenuHandlersNuevo] Llamando a abrirModalEditarEPP (fallback)`);
            window.abrirModalEditarEPP(epp);
        } else {
            console.warn('[EppMenuHandlersNuevo] abrirModalEditarEPP no disponible');
        }
    }

    eliminarEPP(btn) {
        const itemId = btn.getAttribute('data-item-id');
        console.log(`[EppMenuHandlersNuevo] Eliminando EPP: ${itemId}`);

        // Cerrar menú
        this.cerrarTodosLosMenus();

        // Confirmación con SweetAlert
        if (window.Swal) {
            Swal.fire({
                title: '¿Eliminar este EPP?',
                text: 'Esta acción no se puede deshacer',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#dc3545'
            }).then((result) => {
                if (result.isConfirmed) {
                    this.realizarEliminacion(itemId);
                }
            });
        } else {
            // Fallback sin SweetAlert
            if (confirm('¿Eliminar este EPP? Esta acción no se puede deshacer')) {
                this.realizarEliminacion(itemId);
            }
        }
    }

    realizarEliminacion(tarjetaId) {
        try {
            // Obtener el ID original del EPP desde la tarjeta
            const tarjeta = document.querySelector(`.item-epp-card-nuevo[data-epp-id="${tarjetaId}"]`);
            if (!tarjeta) {
                console.error(`[EppMenuHandlersNuevo] Tarjeta no encontrada para eliminar: ${tarjetaId}`);
                return;
            }

            const eppOriginalId = tarjeta.getAttribute('data-epp-original-id');
            console.log(`[EppMenuHandlersNuevo] Eliminando EPP con tarjetaId: ${tarjetaId}, ID original: ${eppOriginalId}`);

            // Eliminar visualmente usando el tarjetaId
            if (window.eppItemManagerNuevo) {
                window.eppItemManagerNuevo.eliminarItem(tarjetaId);
            }

            // Eliminar del array global usando el ID original
            if (window.itemsPedido) {
                const longitudAnterior = window.itemsPedido.length;
                window.itemsPedido = window.itemsPedido.filter(item => 
                    !(item.tipo === 'epp' && (String(item.epp_id) === String(eppOriginalId) || String(item.id) === String(eppOriginalId)))
                );
                console.log(`[EppMenuHandlersNuevo] EPP eliminado del array. Items: ${longitudAnterior} → ${window.itemsPedido.length}`);
            }

            // Actualizar gestión de items
            if (window.gestionItemsUI) {
                // Sincronizar el estado
                window.gestionItemsUI.epps = window.itemsPedido.filter(item => item.tipo === 'epp');
                window.gestionItemsUI.ordenItems = window.itemsPedido.map((item, index) => ({
                    tipo: item.tipo,
                    index: window.gestionItemsUI.epps.indexOf(item)
                }));
            }

            // Mostrar mensaje de éxito
            if (window.Swal) {
                Swal.fire('Eliminado', 'EPP eliminado correctamente', 'success');
            } else {
                alert('EPP eliminado correctamente');
            }

            console.log(`[EppMenuHandlersNuevo] EPP ${itemId} eliminado completamente`);

        } catch (error) {
            console.error('[EppMenuHandlersNuevo] Error al eliminar EPP:', error);
            
            if (window.Swal) {
                Swal.fire('Error', 'No se pudo eliminar el EPP', 'error');
            } else {
                alert('Error al eliminar el EPP');
            }
        }
    }

    /**
     * Refrescar manejadores después de agregar nuevos items
     */
    refrescar() {
        console.log('[EppMenuHandlersNuevo] Refrescando manejadores');
        // Los event listeners se manejan con delegation, no necesitan refresco
    }
}

// 🔍 LOG GLOBAL: Verificación final y diagnóstico
console.log('[EppMenuHandlersNuevo] 🚀 Creando instancia global...');

// Exportar instancia global
window.eppMenuHandlersNuevo = new EppMenuHandlersNuevo();

// 🔍 VERIFICACIÓN: Confirmar que el objeto global existe
console.log('[EppMenuHandlersNuevo] ✅ Instancia global creada:', {
    existe: !!window.eppMenuHandlersNuevo,
    tipo: typeof window.eppMenuHandlersNuevo,
    inicializado: window.eppMenuHandlersNuevo?.inicializado,
    listenersConfigurados: window.eppMenuHandlersNuevo?.eventListenersConfigurados?.size || 0
});

// 🔍 VERIFICACIÓN: Verificar botones existentes
setTimeout(() => {
    console.log('[EppMenuHandlersNuevo] 🔍 Verificación post-carga:');
    
    const botonesMenu = document.querySelectorAll('.btn-menu-epp-nuevo');
    console.log(`[EppMenuHandlersNuevo] Botones de menú encontrados: ${botonesMenu.length}`);
    
    if (botonesMenu.length > 0) {
        botonesMenu.forEach((btn, index) => {
            console.log(`[EppMenuHandlersNuevo] Botón ${index + 1}:`, {
                itemId: btn.getAttribute('data-item-id'),
                className: btn.className,
                tagName: btn.tagName,
                computedStyle: {
                    pointerEvents: window.getComputedStyle(btn).pointerEvents,
                    display: window.getComputedStyle(btn).display,
                    zIndex: window.getComputedStyle(btn).zIndex
                }
            });
        });
    } else {
        console.warn('[EppMenuHandlersNuevo] ⚠️ No se encontraron botones de menú .btn-menu-epp-nuevo');
        console.log('[EppMenuHandlersNuevo] Buscando botones similares...');
        const botonesSimilares = document.querySelectorAll('button[data-item-id*="epp"]');
        console.log(`[EppMenuHandlersNuevo] Botones similares encontrados: ${botonesSimilares.length}`);
        botonesSimilares.forEach((btn, index) => {
            console.log(`[EppMenuHandlersNuevo] Botón similar ${index + 1}:`, {
                itemId: btn.getAttribute('data-item-id'),
                className: btn.className,
                tagName: btn.tagName
            });
        });
    }
    
    // 🔍 VERIFICACIÓN: Verificar submenús
    const submenus = document.querySelectorAll('.submenu-epp-nuevo');
    console.log(`[EppMenuHandlersNuevo] Submenús encontrados: ${submenus.length}`);
    
    submenus.forEach((menu, index) => {
        console.log(`[EppMenuHandlersNuevo] Submenú ${index + 1}:`, {
            itemId: menu.getAttribute('data-item-id'),
            display: window.getComputedStyle(menu).display,
            visibility: window.getComputedStyle(menu).visibility
        });
    });
    
    console.log('[EppMenuHandlersNuevo] 🎯 Diagnóstico completado - Sistema listo para usar');
}, 2000);
