/**
 * MÓDULO: storageModule.js v2.0
 * Responsabilidad: Sincronización entre pestañas via storage universal
 * 
 *  Características:
 * - Usa localStorage/sessionStorage a través del proxy seguro (storage-proxy.js)
 * - Fallback automático a memoria si storage no está disponible
 * - Comunicación entre pestañas con BroadcastChannel y Storage Events
 * - Sin errores "Access to storage is not allowed from this context"
 * - Manejador de errores robusto y sin promesas rechazadas
 * 
 *  REQUISITO: storage-proxy.js debe cargarse ANTES de este archivo
 */

const StorageModule = {
    lastTimestamp: 0,
    broadcastChannel: null,
    initialized: false,
    storageListeners: [], // Rastrear listeners locales

    /**
     * Enviar actualización a otras pestañas
     * @param {String} type - Tipo de actualización (status_update, area_update, etc.)
     * @param {Number|String} orderId - ID de la orden
     * @param {String} field - Campo que cambió
     * @param {*} newValue - Nuevo valor
     * @param {*} oldValue - Valor anterior
     * @param {Object} extraData - Datos adicionales
     */
    async broadcastUpdate(type, orderId, field, newValue, oldValue, extraData = {}) {
        const timestamp = Date.now();
        const updateData = {
            type,
            orderId,
            field,
            newValue,
            oldValue,
            ...extraData,
            timestamp
        };
        
        try {
            // Usar localStorage a través del proxy seguro
            localStorage.setItem('orders-updates', JSON.stringify(updateData));
            localStorage.setItem('last-orders-update-timestamp', timestamp.toString());
            
            // También enviar por BroadcastChannel si está disponible (más rápido)
            if (this.broadcastChannel) {
                try {
                    this.broadcastChannel.postMessage({
                        type: 'orders-update',
                        data: updateData
                    });
                } catch (error) {
                    console.debug('[StorageModule] BroadcastChannel error:', error.message);
                }
            }
            
            console.debug('[StorageModule] Actualización transmitida:', type, orderId);
        } catch (error) {
            console.warn('[StorageModule] Error en broadcastUpdate:', error.message);
            // Continuar sin interrumpir el flujo
        }
    },

    /**
     * Inicializar escucha de mensajes
     */
    initializeListener() {
        if (this.initialized) {
            console.log('[StorageModule] Listener ya inicializado');
            return;
        }
        
        this._setupBroadcastChannel();
        this._setupStorageEvents();
        this.initialized = true;
        
        console.log('[StorageModule]  Listener inicializado');
    },

    /**
     * Configurar BroadcastChannel (moderno, multi-navegador)
     */
    _setupBroadcastChannel() {
        if (typeof BroadcastChannel === 'undefined') {
            console.debug('[StorageModule] BroadcastChannel no disponible en este navegador');
            return;
        }
        
        try {
            this.broadcastChannel = new BroadcastChannel('orders-updates');
            
            this.broadcastChannel.onmessage = (event) => {
                if (event.data && event.data.type === 'orders-update' && event.data.data) {
                    console.debug('[StorageModule] Mensaje BroadcastChannel recibido');
                    this._handleStorageUpdate(event.data.data);
                }
            };
            
            this.broadcastChannel.onerror = (error) => {
                console.warn('[StorageModule] BroadcastChannel error:', error.message);
            };
            
            console.log('[StorageModule]  BroadcastChannel configurado');
        } catch (error) {
            console.warn('[StorageModule] BroadcastChannel no disponible:', error.message);
            this.broadcastChannel = null;
        }
    },

    /**
     * Configurar Storage Events (fallback universal)
     */
    _setupStorageEvents() {
        try {
            const storageListener = (event) => {
                if (event.key === 'orders-updates' && event.newValue) {
                    try {
                        const data = JSON.parse(event.newValue);
                        console.debug('[StorageModule] Storage event recibido');
                        this._handleStorageUpdate(data);
                    } catch (e) {
                        console.warn('[StorageModule] Error parsing storage event:', e.message);
                    }
                }
            };
            
            window.addEventListener('storage', storageListener);
            this.storageListeners.push(storageListener);
            
            console.log('[StorageModule]  Storage events configurados');
        } catch (error) {
            console.warn('[StorageModule] Storage events no disponibles:', error.message);
        }
    },

    /**
     * Manejar actualización desde cualquier fuente
     */
    async _handleStorageUpdate(data) {
        try {
            // Evitar procesar mensajes propios
            const lastTimestamp = localStorage.getItem('last-orders-update-timestamp');
            const lastTimestampNum = parseInt(lastTimestamp || '0');
            
            if (data.timestamp && data.timestamp <= lastTimestampNum) {
                console.debug('[StorageModule] Mensaje ignorado (timestamp antiguo)');
                return;
            }

            this.lastTimestamp = data.timestamp;
            this._processUpdate(data);
        } catch (error) {
            console.warn('[StorageModule] Error en _handleStorageUpdate:', error.message);
        }
    },

    /**
     * Procesar actualización desde otra pestaña
     */
    _processUpdate(data) {
        try {
            const { type, orderId, field, newValue } = data;
            
            if (!orderId) {
                console.warn('[StorageModule] orderId no especificado');
                return;
            }
            
            const row = document.querySelector(`tr[data-order-id="${orderId}"]`);
            if (!row) {
                console.debug('[StorageModule] Fila no encontrada para orderId:', orderId);
                return;
            }

            if (type === 'status_update') {
                const dropdown = row.querySelector('.estado-dropdown');
                if (dropdown) {
                    dropdown.value = newValue;
                    dropdown.dataset.value = newValue;
                    
                    // Actualizar colores si es posible
                    if (typeof RowManager !== 'undefined' && RowManager.updateRowColor) {
                        try {
                            RowManager.updateRowColor(orderId, newValue);
                        } catch (e) {
                            console.debug('[StorageModule] RowManager.updateRowColor error:', e.message);
                        }
                    }
                    
                    console.debug('[StorageModule] Estado actualizado para orden:', orderId, 'Nuevo valor:', newValue);
                }
            } else if (type === 'area_update') {
                const dropdown = row.querySelector('.area-dropdown');
                if (dropdown) {
                    dropdown.value = newValue;
                    dropdown.dataset.value = newValue;
                    console.debug('[StorageModule] Área actualizada para orden:', orderId, 'Nuevo valor:', newValue);
                }
            } else if (type === 'custom_update') {
                // Soporte para actualizaciones personalizadas
                const cell = row.querySelector(`[data-field="${field}"]`);
                if (cell) {
                    cell.textContent = newValue;
                    cell.dataset.value = newValue;
                    console.debug('[StorageModule] Campo personalizado actualizado:', field, 'Nuevo valor:', newValue);
                }
            }
        } catch (error) {
            console.warn('[StorageModule] Error procesando actualización:', error.message);
        }
    },

    /**
     * Verificar disponibilidad de storage
     */
    async checkStorageAvailability() {
        const testKey = 'storage_test_' + Date.now();
        const testValue = 'test_' + Math.random();
        
        try {
            localStorage.setItem(testKey, testValue);
            const result = localStorage.getItem(testKey);
            localStorage.removeItem(testKey);
            
            return result === testValue;
        } catch (error) {
            console.warn('[StorageModule] Storage no disponible:', error.message);
            return false;
        }
    },

    /**
     * Obtener información del estado actual
     */
    getState() {
        const proxyState = window.StorageProxyState ? window.StorageProxyState.getStatus() : null;
        
        return {
            initialized: this.initialized,
            lastTimestamp: this.lastTimestamp,
            hasBroadcastChannel: !!this.broadcastChannel,
            storageListenersCount: this.storageListeners.length,
            proxyState,
            timestamp: Date.now()
        };
    },

    /**
     * Agregar listener personalizado para actualizaciones
     */
    addCustomListener(callback) {
        if (typeof callback !== 'function') {
            console.warn('[StorageModule] Callback inválido');
            return null;
        }
        
        const listenerId = Date.now() + '_' + Math.random();
        const originalProcessUpdate = this._processUpdate.bind(this);
        
        this._processUpdate = function(data) {
            try {
                callback(data);
            } catch (error) {
                console.warn('[StorageModule] Error en custom listener:', error.message);
            }
            originalProcessUpdate(data);
        };
        
        return listenerId;
    },

    /**
     * Limpiar recursos
     */
    destroy() {
        if (this.broadcastChannel) {
            try {
                this.broadcastChannel.close();
            } catch (e) {
                console.debug('[StorageModule] Error cerrando BroadcastChannel:', e.message);
            }
            this.broadcastChannel = null;
        }
        
        // Remover event listeners
        this.storageListeners.forEach(listener => {
            window.removeEventListener('storage', listener);
        });
        this.storageListeners = [];
        
        this.initialized = false;
        console.log('[StorageModule] Recursos liberados');
    }
};

// Exponer módulo globalmente
window.StorageModule = StorageModule;
globalThis.StorageModule = StorageModule;

console.log('[StorageModule]  Módulo cargado correctamente');
