/**
 * 📡 HANDLER UNIVERSAL DE MENSAJES v2.0
 * 
 * Handler universal para mensajes y listeners asíncronos que funciona en cualquier navegador.
 * ✅ Elimina errores "message channel closed" y "Uncaught in promise".
 * 
 * ✅ Características:
 * - Compatible con Chrome, Firefox, Safari, Edge, Opera
 * - Siempre llama a sendResponse en listeners async
 * - Manejo seguro de errores sin promesas rechazadas
 * - Detección automática del entorno (Extension vs Web)
 * - Timeout inteligente para respuestas
 * - Soporte para listeners síncronos y asincronos
 * - API simplificada y documentada
 */

(function() {
    'use strict';
    
    console.log('[MessageHandler] Inicializando handler universal de mensajes...');
    
    // ==================== ESTADO GLOBAL ====================
    const handlerState = {
        environment: 'web', // 'chrome-extension', 'firefox-extension', 'web'
        listeners: new Map(),
        initialized: false,
        pendingResponses: new Map(), // Rastrear respuestas pendientes
        DEBUG: false // Cambiar a true para logs detallados
    };
    
    // ==================== DETECCIÓN DE ENTORNO ====================
    /**
     * Detectar el entorno en el que se está ejecutando
     */
    function detectEnvironment() {
        // Chrome Extension (background, content script, service worker)
        if (typeof chrome !== 'undefined' && chrome.runtime && chrome.runtime.id) {
            handlerState.environment = 'chrome-extension';
            return 'chrome';
        }
        
        // Firefox Extension
        if (typeof browser !== 'undefined' && browser.runtime && browser.runtime.id) {
            handlerState.environment = 'firefox-extension';
            return 'firefox';
        }
        
        // Web estándar (páginas HTML regulares)
        handlerState.environment = 'web';
        return 'web';
    }
    
    // Debug log helper
    function debug(msg) {
        if (handlerState.DEBUG) {
            console.log('[MessageHandler DEBUG]', msg);
        }
    }
    
    // ==================== ENVÍO DE MENSAJES ====================
    /**
     * Enviar mensaje de forma segura (versión universal)
     */
    async function sendMessage(message, options = {}) {
        const env = detectEnvironment();
        const timeout = options.timeout || 5000;
        
        debug(`sendMessage en entorno ${env}: ${JSON.stringify(message).substring(0, 100)}`);
        
        try {
            switch (env) {
                case 'chrome':
                    return await sendChromeMessage(message, timeout);
                case 'firefox':
                    return await sendFirefoxMessage(message, timeout);
                case 'web':
                    return await sendWebMessage(message, timeout);
                default:
                    throw new Error(`Entorno no soportado: ${env}`);
            }
        } catch (error) {
            console.warn('[MessageHandler] Error enviando mensaje:', error.message);
            throw error;
        }
    }
    
    /**
     * Enviar mensaje a Chrome Extension
     */
    function sendChromeMessage(message, timeout) {
        return new Promise((resolve, reject) => {
            if (!chrome || !chrome.runtime) {
                reject(new Error('Chrome runtime no disponible'));
                return;
            }
            
            const timeoutId = setTimeout(() => {
                reject(new Error(`Timeout esperando respuesta en Chrome (${timeout}ms)`));
            }, timeout);
            
            try {
                chrome.runtime.sendMessage(message, (response) => {
                    clearTimeout(timeoutId);
                    
                    // Verificar errores de runtime
                    if (chrome.runtime.lastError) {
                        reject(new Error(chrome.runtime.lastError.message));
                        return;
                    }
                    
                    // Verificar respuesta
                    if (!response) {
                        reject(new Error('Respuesta vacía de Chrome'));
                        return;
                    }
                    
                    if (response.success === false) {
                        reject(new Error(response.error || 'Error en respuesta'));
                        return;
                    }
                    
                    resolve(response);
                });
            } catch (error) {
                clearTimeout(timeoutId);
                reject(error);
            }
        });
    }
    
    /**
     * Enviar mensaje a Firefox Extension
     */
    function sendFirefoxMessage(message, timeout) {
        if (!browser || !browser.runtime) {
            return Promise.reject(new Error('Browser runtime no disponible'));
        }
        
        return Promise.race([
            browser.runtime.sendMessage(message).then(response => {
                if (!response || response.success === false) {
                    throw new Error(response?.error || 'Error en respuesta de Firefox');
                }
                return response;
            }),
            new Promise((_, reject) => 
                setTimeout(() => reject(new Error(`Timeout esperando respuesta en Firefox (${timeout}ms)`)), timeout)
            )
        ]);
    }
    
    /**
     * Enviar mensaje a través de postMessage (Web)
     */
    function sendWebMessage(message, timeout) {
        return new Promise((resolve, reject) => {
            const messageId = `msg_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
            const completeMessage = { ...message, id: messageId };
            
            const timeoutId = setTimeout(() => {
                window.removeEventListener('message', responseListener);
                reject(new Error(`Timeout esperando respuesta en Web (${timeout}ms)`));
            }, timeout);
            
            const responseListener = (event) => {
                // Validar origen y tipo de evento
                if (!event.data || event.data.type !== 'universal-response' || event.data.id !== messageId) {
                    return;
                }
                
                clearTimeout(timeoutId);
                window.removeEventListener('message', responseListener);
                
                if (event.data.success === false) {
                    reject(new Error(event.data.error || 'Error en respuesta'));
                } else {
                    resolve(event.data);
                }
            };
            
            window.addEventListener('message', responseListener);
            
            try {
                window.postMessage({
                    type: 'universal-message',
                    message: completeMessage
                }, '*');
                debug('Mensaje Web enviado');
            } catch (error) {
                clearTimeout(timeoutId);
                window.removeEventListener('message', responseListener);
                reject(error);
            }
        });
    }
    
    // ==================== LISTENERS PARA CHROME ====================
    /**
     * Configurar listener para Chrome Extension
     * ⚠️ IMPORTANTE: Siempre llamar a sendResponse, incluso en error
     */
    function setupChromeListener() {
        chrome.runtime.onMessage.addListener((message, sender, sendResponse) => {
            debug(`Mensaje Chrome recibido: ${JSON.stringify(message).substring(0, 100)}`);
            
            // Variable para rastrear si ya se envió respuesta
            let responded = false;
            let hasAsyncHandler = false; // Rastrear si tenemos handlers asincronos
            
            // Función wrapper para garantizar que sendResponse se llame UNA SOLA VEZ
            const safeResponse = (data) => {
                if (!responded) {
                    responded = true;
                    try {
                        sendResponse(data);
                    } catch (e) {
                        console.warn('[MessageHandler] Error enviando respuesta Chrome:', e.message);
                    }
                }
            };
            
            // Timeout de seguridad para respuestas
            const timeoutId = setTimeout(() => {
                if (!responded && hasAsyncHandler) {
                    safeResponse({ success: false, error: 'Listener timeout' });
                }
            }, 29000); // Chrome cierra el canal a los 30s
            
            // Envolver en IIFE async para manejar operaciones asincronos correctamente
            (async () => {
                try {
                    // Validar mensaje
                    if (!message || typeof message !== 'object') {
                        clearTimeout(timeoutId);
                        safeResponse({ success: false, error: 'Mensaje inválido' });
                        return;
                    }
                    
                    // Buscar y ejecutar listeners registrados
                    let response = null;
                    for (const [listenerId, callback] of handlerState.listeners) {
                        try {
                            hasAsyncHandler = true;
                            const result = await Promise.resolve(callback(message, sender));
                            if (result) {
                                response = result;
                                break;
                            }
                        } catch (error) {
                            console.warn(`[MessageHandler] Listener ${listenerId} error:`, error.message);
                        }
                    }
                    
                    // Enviar respuesta (SIEMPRE si hay handlers)
                    clearTimeout(timeoutId);
                    if (hasAsyncHandler) {
                        if (response) {
                            safeResponse({ success: true, ...response });
                        } else {
                            safeResponse({ success: false, error: 'No handler encontrado' });
                        }
                    }
                    
                } catch (error) {
                    console.error('[MessageHandler] Error en listener Chrome:', error);
                    // IMPORTANTE: Enviar respuesta incluso en caso de error SI hay handlers
                    clearTimeout(timeoutId);
                    if (hasAsyncHandler) {
                        safeResponse({ success: false, error: error.message });
                    }
                }
            })();
            
            // ⚠️ SOLO devolver true si hay listeners registrados
            // Esto previene el error "message channel closed" cuando no hay handlers
            const hasListeners = handlerState.listeners.size > 0;
            if (hasListeners) {
                hasAsyncHandler = true;
            }
            return hasListeners;
        });
        
        console.log('[MessageHandler] Chrome listener configurado');
    }
    
    // ==================== LISTENERS PARA FIREFOX ====================
    /**
     * Configurar listener para Firefox Extension
     */
    function setupFirefoxListener() {
        browser.runtime.onMessage.addListener((message, sender) => {
            debug(`Mensaje Firefox recibido: ${JSON.stringify(message).substring(0, 100)}`);
            
            // En Firefox, retornar una Promise
            return (async () => {
                try {
                    // Validar mensaje
                    if (!message || typeof message !== 'object') {
                        return { success: false, error: 'Mensaje inválido' };
                    }
                    
                    // Buscar y ejecutar listeners registrados
                    let response = null;
                    for (const [listenerId, callback] of handlerState.listeners) {
                        try {
                            const result = await Promise.resolve(callback(message, sender));
                            if (result) {
                                response = result;
                                break;
                            }
                        } catch (error) {
                            console.warn(`[MessageHandler] Listener ${listenerId} error:`, error.message);
                        }
                    }
                    
                    // Retornar respuesta
                    if (response) {
                        return { success: true, ...response };
                    } else {
                        return { success: false, error: 'No handler encontrado' };
                    }
                    
                } catch (error) {
                    console.error('[MessageHandler] Error en listener Firefox:', error);
                    return { success: false, error: error.message };
                }
            })();
        });
        
        console.log('[MessageHandler] Firefox listener configurado');
    }
    
    // ==================== LISTENERS PARA WEB ====================
    /**
     * Configurar listener para Web (postMessage)
     */
    function setupWebListener() {
        window.addEventListener('message', (event) => {
            // Validar que sea un mensaje universal
            if (!event.data || event.data.type !== 'universal-message') {
                return;
            }
            
            const message = event.data.message;
            debug(`Mensaje Web recibido: ${JSON.stringify(message).substring(0, 100)}`);
            
            handleWebMessage(message, event.source).catch(error => {
                console.error('[MessageHandler] Error manejando mensaje web:', error);
            });
        });
        
        console.log('[MessageHandler] Web listener configurado');
    }
    
    /**
     * Manejar mensaje web (postMessage)
     */
    async function handleWebMessage(message, source) {
        try {
            if (!message || typeof message !== 'object') {
                source.postMessage({
                    type: 'universal-response',
                    success: false,
                    error: 'Mensaje inválido',
                    id: message?.id
                }, '*');
                return;
            }
            
            const messageId = message.id;
            let response = null;
            
            // Buscar y ejecutar listeners registrados
            for (const [listenerId, callback] of handlerState.listeners) {
                try {
                    const result = await Promise.resolve(callback(message, { source }));
                    if (result) {
                        response = result;
                        break;
                    }
                } catch (error) {
                    console.warn(`[MessageHandler] Listener ${listenerId} error:`, error.message);
                }
            }
            
            // Enviar respuesta
            source.postMessage({
                type: 'universal-response',
                success: response ? true : false,
                ...(response || { error: 'No handler encontrado' }),
                id: messageId
            }, '*');
            
        } catch (error) {
            console.error('[MessageHandler] Error en handleWebMessage:', error);
            try {
                source.postMessage({
                    type: 'universal-response',
                    success: false,
                    error: error.message,
                    id: message?.id
                }, '*');
            } catch (e) {
                console.error('[MessageHandler] No se pudo enviar respuesta de error web:', e.message);
            }
        }
    }
    
    // ==================== API PÚBLICA ====================
    /**
     * API pública del handler universal
     */
    const UniversalMessageHandler = {
        /**
         * Enviar mensaje
         * @param {Object} message - Mensaje a enviar
         * @param {Object} options - Opciones (timeout, etc.)
         * @returns {Promise}
         */
        sendMessage,
        
        /**
         * Agregar listener para mensajes
         * @param {Function} callback - Función callback (async o sync)
         * @returns {String} ID del listener
         */
        addListener(callback) {
            const id = `listener_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
            handlerState.listeners.set(id, callback);
            debug(`Listener agregado: ${id}`);
            return id;
        },
        
        /**
         * Remover listener
         * @param {String} id - ID del listener
         * @returns {Boolean}
         */
        removeListener(id) {
            const result = handlerState.listeners.delete(id);
            if (result) {
                debug(`Listener removido: ${id}`);
            }
            return result;
        },
        
        /**
         * Limpiar todos los listeners
         */
        clearListeners() {
            handlerState.listeners.clear();
            debug('Todos los listeners fueron removidos');
        },
        
        /**
         * Obtener estado actual
         * @returns {Object}
         */
        getState() {
            return {
                environment: handlerState.environment,
                listenersCount: handlerState.listeners.size,
                initialized: handlerState.initialized,
                timestamp: Date.now()
            };
        },
        
        /**
         * Habilitar/deshabilitar debug
         * @param {Boolean} enabled
         */
        setDebug(enabled) {
            handlerState.DEBUG = enabled;
        },
        
        /**
         * Inicializar handler
         */
        init() {
            if (handlerState.initialized) {
                console.log('[MessageHandler] Ya está inicializado');
                return;
            }
            
            const env = detectEnvironment();
            
            switch (env) {
                case 'chrome':
                    setupChromeListener();
                    break;
                case 'firefox':
                    setupFirefoxListener();
                    break;
                case 'web':
                    setupWebListener();
                    break;
            }
            
            handlerState.initialized = true;
            console.log(`[MessageHandler] ✅ Handler inicializado para entorno: ${env}`);
        }
    };
    
    // ==================== INICIALIZACIÓN Y EXPOSICIÓN ====================
    // Inicializar automáticamente
    UniversalMessageHandler.init();
    
    // Exponer API globalmente
    window.UniversalMessageHandler = UniversalMessageHandler;
    
    // Funciones convenientes en window
    window.sendUniversalMessage = (message, options) => UniversalMessageHandler.sendMessage(message, options);
    window.addUniversalListener = (callback) => UniversalMessageHandler.addListener(callback);
    window.removeUniversalListener = (id) => UniversalMessageHandler.removeListener(id);
    
    console.log('[MessageHandler] ✅ Handler universal de mensajes cargado');
    
})();
