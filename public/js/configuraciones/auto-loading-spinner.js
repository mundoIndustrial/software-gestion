/**
 * Auto Loading Spinner
 * Muestra automáticamente el spinner si una operación se demora más de 3 segundos
 */

(function() {
    'use strict';

    // Configuración
    const CONFIG = {
        DELAY: 3000, // 3 segundos
        ENABLED: true
    };

    // Variables globales
    let spinnerTimeout = null;
    let isOperationInProgress = false;
    let operationStartTime = null;

    /**
     * Inicia el temporizador para mostrar el spinner
     */
    function startSpinnerTimer(message = 'Espere, es posible') {
        if (!CONFIG.ENABLED) return;
        
        // No mostrar spinner para navegación de filtros rápidos
        if (sessionStorage.getItem('skipSpinner') === 'true') {
            console.log('Saltando spinner para navegación de filtro rápido');
            sessionStorage.removeItem('skipSpinner');
            return;
        }

        // Cancelar temporizador anterior si existe
        if (spinnerTimeout) {
            clearTimeout(spinnerTimeout);
        }

        isOperationInProgress = true;
        operationStartTime = Date.now();

        // Mostrar spinner después de 3 segundos
        spinnerTimeout = setTimeout(() => {
            // Verificar flag de skip NUEVAMENTE antes de mostrar
            if (sessionStorage.getItem('skipSpinner') === 'true') {
                console.log('Saltando spinner - flag detectado en timeout');
                return;
            }
            if (isOperationInProgress && CONFIG.ENABLED) {
                showLoadingSpinner(message);
            }
        }, CONFIG.DELAY);
    }

    /**
     * Detiene el temporizador y oculta el spinner
     */
    function stopSpinnerTimer() {
        if (spinnerTimeout) {
            clearTimeout(spinnerTimeout);
            spinnerTimeout = null;
        }

        isOperationInProgress = false;
        operationStartTime = null;

        // Ocultar spinner si está visible
        hideLoadingSpinner();
        
        // También ocultar directamente por si acaso
        const spinner = document.getElementById('loadingSpinner');
        if (spinner) {
            spinner.style.display = 'none';
            spinner.style.visibility = 'hidden';
        }
    }

    /**
     * Intercepta Fetch API
     */
    const originalFetch = window.fetch;
    window.fetch = function(...args) {
        startSpinnerTimer('Cargando datos...');

        return originalFetch.apply(this, args)
            .then(response => {
                stopSpinnerTimer();
                return response;
            })
            .catch(error => {
                stopSpinnerTimer();
                throw error;
            });
    };

    /**
     * Intercepta XMLHttpRequest (AJAX)
     */
    const originalOpen = XMLHttpRequest.prototype.open;
    XMLHttpRequest.prototype.open = function(method, url, ...rest) {
        this._startTime = Date.now();
        return originalOpen.apply(this, [method, url, ...rest]);
    };

    const originalSend = XMLHttpRequest.prototype.send;
    XMLHttpRequest.prototype.send = function(...args) {
        startSpinnerTimer('Procesando solicitud...');

        // Interceptar cambios de estado
        const originalOnReadyStateChange = this.onreadystatechange;
        this.onreadystatechange = function() {
            if (this.readyState === 4) {
                stopSpinnerTimer();
            }
            if (originalOnReadyStateChange) {
                originalOnReadyStateChange.apply(this, arguments);
            }
        };

        return originalSend.apply(this, args);
    };

    /**
     * Intercepta jQuery AJAX (si jQuery está disponible)
     */
    if (typeof jQuery !== 'undefined') {
        jQuery(document).ajaxStart(function() {
            startSpinnerTimer('Procesando solicitud...');
        }).ajaxStop(function() {
            stopSpinnerTimer();
        });
    }

    /**
     * Intercepta Axios (si Axios está disponible)
     */
    if (typeof axios !== 'undefined') {
        axios.interceptors.request.use(
            config => {
                startSpinnerTimer('Cargando datos...');
                return config;
            },
            error => {
                stopSpinnerTimer();
                return Promise.reject(error);
            }
        );

        axios.interceptors.response.use(
            response => {
                stopSpinnerTimer();
                return response;
            },
            error => {
                stopSpinnerTimer();
                return Promise.reject(error);
            }
        );
    }

    /**
     * Exponer funciones globales
     */
    window.startSpinnerTimer = startSpinnerTimer;
    window.stopSpinnerTimer = stopSpinnerTimer;

    /**
     * Configuración
     */
    window.setSpinnerConfig = function(options = {}) {
        if (options.delay !== undefined) {
            CONFIG.DELAY = options.delay;
        }
        if (options.enabled !== undefined) {
            CONFIG.ENABLED = options.enabled;
        }
    };

    window.getSpinnerConfig = function() {
        return { ...CONFIG };
    };

    // Log de inicialización
    console.log(' Auto Loading Spinner inicializado (delay: ' + CONFIG.DELAY + 'ms)');
})();
