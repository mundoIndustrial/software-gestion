/**
 * ================================================
 * CONTEXT MENU SERVICE
 * ================================================
 * 
 * Servicio para crear y gestionar menús contextuales
 * Soporta posicionamiento inteligente, cierre automático y callbacks personalizables
 * 
 * @class ContextMenuService
 */

class ContextMenuService {
    constructor() {
        this.menuActual = null;
        this.listenersActivos = false;
        this.opcionesPorDefecto = {
            ancho: 180,
            alto: 50,
            padding: 10,
            zIndex: 999999999,
            usarOverlay: true,
            autoCerrar: true,
            animacion: true
        };
    }

    /**
     * Crear y mostrar un menú contextual
     * @param {number} clientX - Posición X del cursor
     * @param {number} clientY - Posición Y del cursor
     * @param {Array} opciones - Array de opciones del menú
     * @param {Object} config - Configuración adicional
     * @returns {HTMLElement} Elemento del menú creado
     */
    crearMenu(clientX, clientY, opciones, config = {}) {
        // Cerrar menú anterior si existe
        this.cerrarMenuActual();

        const configuracion = { ...this.opcionesPorDefecto, ...config };
        
        // Calcular posición
        const posicion = UIHelperService.calcularPosicionMenu(
            clientX, 
            clientY, 
            { width: configuracion.ancho, height: configuracion.alto }, 
            configuracion.padding
        );

        // Crear elemento del menú
        this.menuActual = this._crearElementoMenu(posicion, configuracion);
        
        // Agregar opciones
        opciones.forEach(opcion => {
            this._agregarOpcion(this.menuActual, opcion, configuracion);
        });

        // Mostrar menú
        this._mostrarMenu(configuracion);

        UIHelperService.log('ContextMenuService', 'Menú contextual creado y mostrado');
        return this.menuActual;
    }

    /**
     * Cerrar el menú actual si existe
     */
    cerrarMenuActual() {
        if (this.menuActual && this.menuActual.parentElement) {
            this.menuActual.parentElement.removeChild(this.menuActual);
            this.menuActual = null;
            
            // Restaurar pointer-events del overlay si está vacío
            if (this.opcionesPorDefecto.usarOverlay) {
                const overlay = document.getElementById('drag-drop-overlay-container');
                if (overlay && overlay.children.length === 0) {
                    overlay.style.pointerEvents = 'none';
                }
            }
            
            this._removerListenersCierre();
            UIHelperService.log('ContextMenuService', 'Menú actual cerrado');
        }
    }

    /**
     * Verificar si hay un menú activo
     * @returns {boolean} True si hay menú activo
     */
    hayMenuActivo() {
        return this.menuActual !== null;
    }

    /**
     * Crear elemento base del menú
     * @param {Object} posicion - Posición calculada {left, top}
     * @param {Object} config - Configuración del menú
     * @returns {HTMLElement} Elemento del menú
     * @private
     */
    _crearElementoMenu(posicion, config) {
        const menu = document.createElement('div');
        menu.className = 'context-menu-drag-drop';
        
        const estilosBase = {
            position: 'fixed',
            left: `${posicion.left}px`,
            top: `${posicion.top}px`,
            background: 'white',
            border: '1px solid #d1d5db',
            borderRadius: '6px',
            boxShadow: '0 10px 25px rgba(0, 0, 0, 0.25)',
            zIndex: config.zIndex,
            padding: '4px 0',
            minWidth: `${config.ancho}px`,
            fontSize: '14px',
            backdropFilter: 'blur(10px)',
            background: 'rgba(255, 255, 255, 0.95)',
            fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
            lineHeight: '1.5'
        };

        // Agregar estilos de animación si está configurado
        if (config.animacion) {
            estilosBase.transform = 'scale(0.95)';
            estilosBase.opacity = '0';
            estilosBase.transition = 'transform 0.1s ease-out, opacity 0.1s ease-out';
        }

        Object.assign(menu.style, estilosBase);
        
        // Prevenir propagación de eventos
        menu.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
        });
        
        menu.addEventListener('mousedown', (e) => {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
        });
        
        menu.addEventListener('mouseup', (e) => {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
        });

        return menu;
    }

    /**
     * Agregar una opción al menú
     * @param {HTMLElement} menu - Elemento del menú
     * @param {Object} opcion - Configuración de la opción
     * @param {Object} config - Configuración general
     * @private
     */
    _agregarOpcion(menu, opcion, config) {
        const opcionElemento = document.createElement('div');
        opcionElemento.className = 'context-menu-option';
        
        // Estilos de la opción
        const estilosOpcion = {
            padding: '8px 16px',
            cursor: 'pointer',
            display: 'flex',
            alignItems: 'center',
            gap: '8px',
            color: '#374151',
            transition: 'background-color 0.2s',
            userSelect: 'none',
            whiteSpace: 'nowrap'
        };

        Object.assign(opcionElemento.style, estilosOpcion);

        // Contenido de la opción
        let contenido = '';
        
        if (opcion.icono) {
            const estiloIcono = opcion.estiloIcono || 'font-size: 18px; flex-shrink: 0;';
            contenido += `<span class="material-symbols-rounded" style="${estiloIcono}">${opcion.icono}</span>`;
        }
        
        if (opcion.texto) {
            contenido += `<span>${opcion.texto}</span>`;
        }
        
        if (opcion.html) {
            contenido += opcion.html;
        }

        opcionElemento.innerHTML = contenido;

        // Hover effect
        opcionElemento.addEventListener('mouseenter', () => {
            opcionElemento.style.backgroundColor = '#f3f4f6';
        });
        
        opcionElemento.addEventListener('mouseleave', () => {
            opcionElemento.style.backgroundColor = '';
        });

        // Click handler
        opcionElemento.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            
            UIHelperService.log('ContextMenuService', `🖱️ Clic en opción: ${opcion.texto}`);
            
            // Ejecutar callback o accion si existe (compatibilidad con ambos)
            const funcionAEjecutar = opcion.callback || opcion.accion;
            
            if (typeof funcionAEjecutar === 'function') {
                try {
                    UIHelperService.log('ContextMenuService', '⚡ Ejecutando callback/accion...');
                    funcionAEjecutar(e, opcion);
                    UIHelperService.log('ContextMenuService', '✅ Callback/accion ejecutado exitosamente');
                } catch (error) {
                    UIHelperService.log('ContextMenuService', `❌ Error en callback/accion: ${error.message}`, 'error');
                }
            } else {
                UIHelperService.log('ContextMenuService', `❌ Callback/accion no es una función: ${typeof funcionAEjecutar}`, 'error');
            }
            
            // Cerrar menú si está configurado
            if (config.autoCerrar) {
                UIHelperService.log('ContextMenuService', '🔒 Cerrando menú automáticamente...');
                this.cerrarMenuActual();
            }
        });

        // Agregar clase adicional si está especificada
        if (opcion.clase) {
            opcionElemento.classList.add(opcion.clase);
        }

        // Deshabilitar opción si está configurado
        if (opcion.deshabilitado) {
            opcionElemento.style.opacity = '0.5';
            opcionElemento.style.cursor = 'not-allowed';
            opcionElemento.style.pointerEvents = 'none';
        }

        menu.appendChild(opcionElemento);
    }

    /**
     * Mostrar el menú y configurar cierre automático
     * @param {Object} config - Configuración del menú
     * @private
     */
    _mostrarMenu(config) {
        // Agregar al DOM
        if (config.usarOverlay) {
            const overlay = UIHelperService.obtenerContenedorOverlay();
            overlay.style.pointerEvents = 'auto';
            overlay.appendChild(this.menuActual);
        } else {
            document.body.appendChild(this.menuActual);
        }

        // Animación de entrada
        if (config.animacion) {
            // Forzar reflow para que la animación se ejecute
            this.menuActual.offsetHeight;
            
            this.menuActual.style.transform = 'scale(1)';
            this.menuActual.style.opacity = '1';
        }

        // Configurar cierre automático
        this._configurarCierreAutomatico();
    }

    /**
     * Configurar listeners para cierre automático
     * @private
     */
    _configurarCierreAutomatico() {
        if (this.listenersActivos) return;

        // Variable para controlar si el menú debe permanecer abierto
        let mantenerAbierto = false;
        let timeoutCierre = null;

        // Cerrar al hacer clic fuera (con lógica mejorada)
        const closeMenuClick = (e) => {
            if (!this.menuActual) return;
            
            // Si el clic fue dentro del menú, no cerrar
            if (this.menuActual.contains(e.target)) {
                mantenerAbierto = true;
                return;
            }
            
            // Si el clic fue fuera, cerrar después de un breve delay
            if (timeoutCierre) {
                clearTimeout(timeoutCierre);
            }
            
            timeoutCierre = setTimeout(() => {
                if (!mantenerAbierto && this.menuActual) {
                    this.cerrarMenuActual();
                }
                mantenerAbierto = false;
                timeoutCierre = null;
            }, 50);
        };

        // Cerrar al presionar Escape
        const closeMenuEscape = (e) => {
            if (e.key === 'Escape' && this.menuActual) {
                this.cerrarMenuActual();
            }
        };

        // Agregar listeners inmediatamente (sin delay)
        document.addEventListener('click', closeMenuClick, true);
        document.addEventListener('mousedown', closeMenuClick, true);
        document.addEventListener('keydown', closeMenuEscape, true);
        
        // Guardar referencias para poder removerlos
        this._listenersCierre = { closeMenuClick, closeMenuEscape };
        this.listenersActivos = true;
        
        UIHelperService.log('ContextMenuService', 'Listeners de cierre automático activados (sin delay)');
    }

    /**
     * Remover listeners de cierre automático
     * @private
     */
    _removerListenersCierre() {
        if (this._listenersCierre) {
            document.removeEventListener('click', this._listenersCierre.closeMenuClick, true);
            document.removeEventListener('mousedown', this._listenersCierre.closeMenuClick, true);
            document.removeEventListener('keydown', this._listenersCierre.closeMenuEscape, true);
            
            this._listenersCierre = null;
            this.listenersActivos = false;
            
            UIHelperService.log('ContextMenuService', 'Listeners de cierre removidos');
        }
    }

    /**
     * Crear opción estándar para pegar imagen
     * @param {Function} callback - Función a ejecutar al hacer clic
     * @param {Object} opciones - Opciones adicionales
     * @returns {Object} Configuración de opción
     */
    static crearOpcionPegar(callback, opciones = {}) {
        return {
            icono: 'content_paste',
            texto: opciones.texto || 'Pegar imagen',
            callback: callback,
            accion: callback, // Compatibilidad con código existente que usa 'accion'
            clase: 'opcion-pegar',
            ...opciones
        };
    }

    /**
     * Crear opción estándar para pegar imagen de prenda
     * @param {Function} callback - Función a ejecutar
     * @returns {Object} Configuración de opción
     */
    static crearOpcionPegarPrenda(callback) {
        UIHelperService.log('ContextMenuService', `🔧 crearOpcionPegarPrenda llamado con: ${typeof callback}`);
        
        const opcion = this.crearOpcionPegar(callback, {
            texto: 'Pegar imagen de prenda',
            clase: 'opcion-pegar-prenda'
        });
        
        UIHelperService.log('ContextMenuService', `✅ Opción creada - callback: ${typeof opcion.callback}`);
        
        return opcion;
    }

    /**
     * Crear opción estándar para pegar imagen de tela
     * @param {Function} callback - Función a ejecutar
     * @returns {Object} Configuración de opción
     */
    static crearOpcionPegarTela(callback) {
        UIHelperService.log('ContextMenuService', `🔧 crearOpcionPegarTela llamado con: ${typeof callback}`);
        
        const opcion = this.crearOpcionPegar(callback, {
            texto: 'Pegar imagen de tela',
            clase: 'opcion-pegar-tela'
        });
        
        UIHelperService.log('ContextMenuService', `✅ Opción de tela creada - callback: ${typeof opcion.callback}, texto: ${opcion.texto}`);
        
        return opcion;
    }

    /**
     * Crear opción estándar para pegar imagen de proceso
     * @param {Function} callback - Función a ejecutar
     * @param {number} numeroProceso - Número del proceso
     * @returns {Object} Configuración de opción
     */
    static crearOpcionPegarProceso(callback, numeroProceso) {
        return this.crearOpcionPegar(callback, {
            texto: `Pegar imagen ${numeroProceso}`,
            clase: 'opcion-pegar-proceso',
            estiloIcono: 'font-size: 18px; flex-shrink: 0;'
        });
    }

    /**
     * Actualizar opciones por defecto
     * @param {Object} nuevasOpciones - Nuevas opciones por defecto
     */
    actualizarOpcionesPorDefecto(nuevasOpciones) {
        this.opcionesPorDefecto = { ...this.opcionesPorDefecto, ...nuevasOpciones };
        UIHelperService.log('ContextMenuService', 'Opciones por defecto actualizadas');
    }

    /**
     * Obtener estado actual del servicio
     * @returns {Object} Estado actual
     */
    getEstado() {
        return {
            menuActivo: this.hayMenuActivo(),
            listenersActivos: this.listenersActivos,
            opcionesPorDefecto: this.opcionesPorDefecto
        };
    }

    /**
     * Destruir el servicio y limpiar recursos
     */
    destruir() {
        this.cerrarMenuActual();
        this._removerListenersCierre();
        
        UIHelperService.log('ContextMenuService', 'Servicio destruido');
    }
}

// Crear instancia global
window.ContextMenuService = new ContextMenuService();

// Exportar para uso en módulos
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ContextMenuService;
}
