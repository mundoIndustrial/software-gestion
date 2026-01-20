/**
 * UIModule - Gestión de UI y eventos
 * 
 *  SOLID Principles:
 * - Single Responsibility: Solo maneja UI
 * - Open/Closed: Extensible sin modificar
 * - Liskov Substitution: Implementa contrato consistente
 * - Interface Segregation: API mínima y clara
 * - Dependency Inversion: No depende de detalles
 * 
 * Responsabilidades:
 * - Gestionar elementos del DOM
 * - Manejar eventos visuales
 * - Sincronizar UI con estado
 * - Mostrar/ocultar modales
 * - Toggle de menús flotantes
 */

const UIModule = (() => {
    // ===== ESTADO PRIVADO =====
    const state = {
        isMenuOpen: false,
        isModalOpen: false,
        selectedTab: null
    };

    // ===== CONSTANTES =====
    const SELECTORS = {
        // Header
        headerCliente: '#header-cliente',
        headerAsesor: '#header-asesor',
        headerTipoCotizacion: '#header-tipo-cotizacion',
        headerFecha: '#header-fecha',
        
        // Botones de acción
        btnFlotante: '#btnFlotante',
        btnGuardarBorrador: '#btnGuardarBorrador',
        btnEnviar: '#btnEnviar',
        
        // Menú flotante
        menuFlotante: '#menuFlotante',
        
        // Modal
        modalEspecificaciones: '#modalEspecificaciones',
        
        // Productos
        productosContainer: '#productosContainer',
        
        // Errores
        errorTipoCotizacion: '#error-tipo-cotizacion',
        
        // Formulario
        cotizacionForm: '#cotizacionPrendaForm'
    };

    // ===== MÉTODOS PRIVADOS =====
    
    /**
     * Obtener elemento del DOM de forma segura
     */
    function getElement(selector) {
        const element = document.querySelector(selector);
        if (!element) {
            console.warn(`⚠️ Elemento no encontrado: ${selector}`);
            return null;
        }
        return element;
    }

    /**
     * Agregar listener a elemento con validación
     */
    function addListener(selector, event, callback) {
        const element = getElement(selector);
        if (element) {
            element.addEventListener(event, callback);
            return true;
        }
        return false;
    }

    /**
     * Mostrar u ocultar elemento
     */
    function toggleDisplay(selector, show = null) {
        const element = getElement(selector);
        if (!element) return;

        if (show === null) {
            element.style.display = element.style.display === 'none' ? 'block' : 'none';
        } else {
            element.style.display = show ? 'block' : 'none';
        }
    }

    /**
     * Actualizar estado visual del botón flotante
     */
    function updateFloatingButtonState() {
        const btn = getElement(SELECTORS.btnFlotante);
        if (!btn) return;

        if (state.isMenuOpen) {
            btn.style.transform = 'scale(1) rotate(45deg)';
        } else {
            btn.style.transform = 'scale(1) rotate(0deg)';
        }
    }

    /**
     * Validar y mostrar error en campo
     */
    function setFieldError(selector, show = true) {
        const element = getElement(selector);
        if (!element) return;

        if (show) {
            element.classList.add('campo-invalido');
            element.style.borderColor = '#ff4444';
        } else {
            element.classList.remove('campo-invalido');
            element.style.borderColor = '';
        }
    }

    // ===== MÉTODOS PÚBLICOS =====

    /**
     * Inicializar módulo de UI
     */
    function init() {
        console.log('🎨 Inicializando UIModule...');
        setupEventListeners();
        setupMenuFlotante();
        setupModal();
        console.log(' UIModule inicializado');
    }

    /**
     * Configurar listeners de eventos
     */
    function setupEventListeners() {
        // Sincronizar header con inputs ocultos
        addListener(SELECTORS.headerCliente, 'input', (e) => {
            const hiddenInput = document.getElementById('cliente');
            if (hiddenInput) hiddenInput.value = e.target.value;
        });

        addListener(SELECTORS.headerTipoCotizacion, 'change', (e) => {
            const hiddenInput = document.getElementById('tipo_cotizacion');
            if (hiddenInput) hiddenInput.value = e.target.value;
            clearFieldError(SELECTORS.headerTipoCotizacion);
            clearFieldError(SELECTORS.errorTipoCotizacion);
        });

        // Botones de acción
        addListener(SELECTORS.btnGuardarBorrador, 'click', () => {
            if (window.app && window.app.guardar) {
                window.app.guardar('borrador');
            }
        });

        addListener(SELECTORS.btnEnviar, 'click', () => {
            if (window.app && window.app.guardar) {
                window.app.guardar('enviar');
            }
        });

        // Cerrar modal al hacer click fuera
        addListener(SELECTORS.modalEspecificaciones, 'click', (e) => {
            if (e.target.id === 'modalEspecificaciones') {
                closeModal();
            }
        });
    }

    /**
     * Configurar menú flotante
     */
    function setupMenuFlotante() {
        const btn = getElement(SELECTORS.btnFlotante);
        const menu = getElement(SELECTORS.menuFlotante);

        if (!btn || !menu) return;

        btn.addEventListener('click', () => {
            state.isMenuOpen = !state.isMenuOpen;
            menu.style.display = state.isMenuOpen ? 'block' : 'none';
            updateFloatingButtonState();
        });

        // Cerrar menú al seleccionar opción
        menu.querySelectorAll('button').forEach(button => {
            button.addEventListener('click', () => {
                state.isMenuOpen = false;
                menu.style.display = 'none';
                updateFloatingButtonState();
            });
        });
    }

    /**
     * Configurar modal
     */
    function setupModal() {
        // Ya manejado en setupEventListeners
    }

    /**
     * Abrir modal de especificaciones
     */
    function openModal() {
        const modal = getElement(SELECTORS.modalEspecificaciones);
        if (modal) {
            modal.style.display = 'flex';
            state.isModalOpen = true;
        }
    }

    /**
     * Cerrar modal
     */
    function closeModal() {
        const modal = getElement(SELECTORS.modalEspecificaciones);
        if (modal) {
            modal.style.display = 'none';
            state.isModalOpen = false;
        }
    }

    /**
     * Mostrar error en campo
     */
    function showFieldError(selector, message = null) {
        setFieldError(selector, true);
        if (message) {
            const element = getElement(selector);
            if (element) {
                const errorEl = element.nextElementSibling;
                if (errorEl && errorEl.classList.contains('error-message')) {
                    errorEl.textContent = message;
                    errorEl.style.display = 'block';
                }
            }
        }
    }

    /**
     * Limpiar error de campo
     */
    function clearFieldError(selector) {
        setFieldError(selector, false);
        const element = getElement(selector);
        if (element) {
            const errorEl = element.nextElementSibling;
            if (errorEl && errorEl.classList.contains('error-message')) {
                errorEl.style.display = 'none';
            }
        }
    }

    /**
     * Deshabilitar botones de acción
     */
    function disableActionButtons(disabled = true) {
        const btnGuardar = getElement(SELECTORS.btnGuardarBorrador);
        const btnEnviar = getElement(SELECTORS.btnEnviar);

        if (btnGuardar) {
            btnGuardar.disabled = disabled;
            btnGuardar.style.opacity = disabled ? '0.5' : '1';
            btnGuardar.style.cursor = disabled ? 'not-allowed' : 'pointer';
        }

        if (btnEnviar) {
            btnEnviar.disabled = disabled;
            btnEnviar.style.opacity = disabled ? '0.5' : '1';
            btnEnviar.style.cursor = disabled ? 'not-allowed' : 'pointer';
        }
    }

    /**
     * Obtener valor del header
     */
    function getHeaderValue(field) {
        const selector = SELECTORS[`header${field.charAt(0).toUpperCase() + field.slice(1)}`];
        const element = getElement(selector);
        return element ? element.value : null;
    }

    /**
     * Obtener todos los valores del header
     */
    function getHeaderValues() {
        return {
            cliente: getHeaderValue('cliente'),
            asesor: getHeaderValue('asesor'),
            tipo_cotizacion: getHeaderValue('tipoCotizacion'),
            fecha: getHeaderValue('fecha')
        };
    }

    /**
     * Sincronizar inputs ocultos del formulario
     */
    function syncHiddenInputs() {
        const valores = getHeaderValues();
        
        const inputCliente = document.getElementById('cliente');
        const inputAsesor = document.getElementById('asesora');
        const inputTipo = document.getElementById('tipo_cotizacion');
        const inputFecha = document.getElementById('fecha');

        if (inputCliente) inputCliente.value = valores.cliente || '';
        if (inputAsesor) inputAsesor.value = valores.asesor || '';
        if (inputTipo) inputTipo.value = valores.tipo_cotizacion || '';
        if (inputFecha) inputFecha.value = valores.fecha || '';
    }

    /**
     * Mostrar loading spinner
     */
    function showLoading(show = true) {
        const btnEnviar = getElement(SELECTORS.btnEnviar);
        const btnGuardar = getElement(SELECTORS.btnGuardarBorrador);

        if (show) {
            if (btnEnviar) {
                btnEnviar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';
                btnEnviar.disabled = true;
            }
            if (btnGuardar) {
                btnGuardar.disabled = true;
                btnGuardar.style.opacity = '0.5';
            }
        } else {
            if (btnEnviar) {
                btnEnviar.innerHTML = '<i class="fas fa-paper-plane"></i> Enviar';
                btnEnviar.disabled = false;
            }
            if (btnGuardar) {
                btnGuardar.disabled = false;
                btnGuardar.style.opacity = '1';
            }
        }
    }

    /**
     * Mostrar notificación
     */
    function showNotification(message, type = 'info') {
        // Usar alertas nativas por ahora
        // TODO: Mejorar con toast notifications
        if (type === 'error') {
            alert(' ' + message);
        } else if (type === 'success') {
            alert(' ' + message);
        } else {
            alert(' ' + message);
        }
    }

    /**
     * Limpiar formulario
     */
    function clearForm() {
        const form = getElement(SELECTORS.cotizacionForm);
        if (form) form.reset();
        
        const container = getElement(SELECTORS.productosContainer);
        if (container) container.innerHTML = '';
    }

    /**
     * Obtener estado actual
     */
    function getState() {
        return { ...state };
    }

    // ===== EXPORTS =====
    return {
        init,
        openModal,
        closeModal,
        showFieldError,
        clearFieldError,
        disableActionButtons,
        getHeaderValue,
        getHeaderValues,
        syncHiddenInputs,
        showLoading,
        showNotification,
        clearForm,
        getState,
        toggleDisplay
    };
})();

// Auto-inicializar cuando el DOM esté listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', UIModule.init);
} else {
    UIModule.init();
}

// Exportar para uso global
window.uiModule = UIModule;
