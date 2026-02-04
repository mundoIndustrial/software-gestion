/**
 * 📡 EJEMPLO: Listeners de Chrome Extension
 * 
 * Uso OPCIONAL: Incluir este archivo solo si tu proyecto tiene listeners de Chrome Extension.
 * 
 * Este archivo muestra cómo usar correctamente el handler universal
 * para mensajes de Chrome Extension sin generar errores.
 * 
 *  Funciona correctamente con el handler universal
 *  Sin "message channel closed"
 *  Sin "Uncaught in promise"
 */

// Verificar que el handler universal esté disponible
if (typeof UniversalMessageHandler === 'undefined') {
    console.warn('[ExtensionListener] UniversalMessageHandler no disponible. ¿Se cargó message-handler-universal.js?');
} else {
    
    // ==================== CONFIGURAR LISTENERS ====================
    
    /**
     * Listener para mensajes de storage
     */
    const storageListenerId = UniversalMessageHandler.addListener(async (message, sender) => {
        console.log('[ExtensionListener] Mensaje recibido:', message.type);
        
        try {
            switch (message.type) {
                // ===== OBTENER VALOR DE STORAGE =====
                case 'storage.get':
                    try {
                        const value = localStorage.getItem(message.key);
                        return { 
                            success: true, 
                            value,
                            key: message.key,
                            timestamp: Date.now()
                        };
                    } catch (error) {
                        return { 
                            success: false, 
                            error: error.message 
                        };
                    }
                
                // ===== GUARDAR VALOR EN STORAGE =====
                case 'storage.set':
                    try {
                        localStorage.setItem(message.key, message.value);
                        return { 
                            success: true, 
                            saved: true,
                            key: message.key,
                            timestamp: Date.now()
                        };
                    } catch (error) {
                        return { 
                            success: false, 
                            error: error.message 
                        };
                    }
                
                // ===== REMOVER VALOR DE STORAGE =====
                case 'storage.remove':
                    try {
                        localStorage.removeItem(message.key);
                        return { 
                            success: true, 
                            removed: true,
                            key: message.key,
                            timestamp: Date.now()
                        };
                    } catch (error) {
                        return { 
                            success: false, 
                            error: error.message 
                        };
                    }
                
                // ===== TRANSMITIR ACTUALIZACIÓN =====
                case 'storage.broadcast':
                    try {
                        // Usar el StorageModule si está disponible
                        if (typeof StorageModule !== 'undefined') {
                            await StorageModule.broadcastUpdate(
                                message.data.type || 'custom_update',
                                message.data.orderId,
                                message.data.field,
                                message.data.newValue,
                                message.data.oldValue,
                                message.data.extraData
                            );
                            
                            return { 
                                success: true, 
                                broadcasted: true,
                                orderId: message.data.orderId,
                                timestamp: Date.now()
                            };
                        } else {
                            return { 
                                success: false, 
                                error: 'StorageModule no disponible' 
                            };
                        }
                    } catch (error) {
                        return { 
                            success: false, 
                            error: error.message 
                        };
                    }
                
                // ===== OBTENER ESTADO DEL STORAGE =====
                case 'storage.status':
                    try {
                        const status = {
                            storageAvailable: true,
                            length: localStorage.length,
                            timestamp: Date.now()
                        };
                        
                        if (typeof StorageModule !== 'undefined') {
                            status.moduleState = StorageModule.getState();
                        }
                        
                        if (typeof window.StorageProxyState !== 'undefined') {
                            status.proxyState = window.StorageProxyState.getStatus();
                        }
                        
                        return { 
                            success: true, 
                            status 
                        };
                    } catch (error) {
                        return { 
                            success: false, 
                            error: error.message 
                        };
                    }
                
                // ===== TIPO NO SOPORTADO =====
                default:
                    return { 
                        success: false, 
                        error: `Tipo no soportado: ${message.type}` 
                    };
            }
        } catch (error) {
            console.error('[ExtensionListener] Error procesando mensaje:', error);
            return { 
                success: false, 
                error: error.message 
            };
        }
    });
    
    console.log('[ExtensionListener]  Listener de storage configurado (ID:', storageListenerId + ')');
    
    
    // ==================== API PUBLICA ====================
    
    /**
     * Enviar mensaje de storage (función auxiliar)
     */
    async function sendStorageMessage(type, data = {}) {
        try {
            const response = await UniversalMessageHandler.sendMessage({
                type: type,
                ...data,
                timestamp: Date.now()
            });
            return response;
        } catch (error) {
            console.error('[ExtensionListener] Error enviando mensaje:', error);
            throw error;
        }
    }
    
    /**
     * API de ejemplo para acceso rápido
     */
    window.ExtensionStorageAPI = {
        /**
         * Obtener valor de storage
         * @param {String} key
         * @returns {Promise}
         */
        get: (key) => sendStorageMessage('storage.get', { key }),
        
        /**
         * Guardar valor en storage
         * @param {String} key
         * @param {String} value
         * @returns {Promise}
         */
        set: (key, value) => sendStorageMessage('storage.set', { key, value }),
        
        /**
         * Remover valor de storage
         * @param {String} key
         * @returns {Promise}
         */
        remove: (key) => sendStorageMessage('storage.remove', { key }),
        
        /**
         * Transmitir actualización a otras pestañas
         * @param {Object} data
         * @returns {Promise}
         */
        broadcast: (data) => sendStorageMessage('storage.broadcast', { data }),
        
        /**
         * Obtener estado del storage
         * @returns {Promise}
         */
        status: () => sendStorageMessage('storage.status', {})
    };
    
    console.log('[ExtensionListener]  API disponible en window.ExtensionStorageAPI');
    
    
    // ==================== EJEMPLO DE USO ====================
    
    /**
     * Ejemplo: Cómo usar la API
     * 
     * // Guardar un valor
     * await ExtensionStorageAPI.set('mi-clave', 'mi-valor');
     * 
     * // Obtener un valor
     * const result = await ExtensionStorageAPI.get('mi-clave');
     * console.log(result.value);
     * 
     * // Transmitir actualización
     * await ExtensionStorageAPI.broadcast({
     *     type: 'status_update',
     *     orderId: 123,
     *     field: 'estado',
     *     newValue: 'completado',
     *     oldValue: 'en_proceso'
     * });
     * 
     * // Verificar estado
     * const status = await ExtensionStorageAPI.status();
     * console.log(status.status);
     */
    
} else {
    console.error('[ExtensionListener] No se puede inicializar sin UniversalMessageHandler');
