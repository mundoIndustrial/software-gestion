// --- HttpClient.js ---
/**
 * =====================================================
 * SHARED INFRASTRUCTURE - HTTP CLIENT
 * =====================================================
 * Abstracción centralizada para TODAS las peticiones HTTP.
 * Generalizado desde insumos/core/infrastructure/HttpClient.js.
 *
 * Características:
 * - CSRF token automático (Laravel)
 * - Retry logic para errores de red/servidor
 * - Timeout configurable con AbortController
 * - Soporte para JSON y FormData
 * - Manejo de errores tipado (HttpError)
 *
 * Uso:
 *   const http = new SharedHttpClient();
 *   const data = await http.get('/api/pedidos');
 *   await http.post('/api/pedidos', { nombre: 'Test' });
 *   await http.postFormData('/api/upload', formData);
 */

class HttpError extends Error {
    constructor(message, status, response, body = null) {
        super(message);
        this.name = 'HttpError';
        this.status = status;
        this.response = response;
        this.body = body;
    }
}

class SharedHttpClient {
    constructor(config = {}) {
        this.baseUrl = config.baseUrl || '';
        this.timeout = config.timeout || 15000;
        this.retries = config.retries || 2;
        this._csrfToken = null;
    }

    /**
     * Obtiene el token CSRF de Laravel (cacheado).
     */
    getCsrfToken() {
        if (!this._csrfToken) {
            this._csrfToken =
                document.querySelector('meta[name="csrf-token"]')?.content ||
                document.querySelector('input[name="_token"]')?.value ||
                '';
        }
        return this._csrfToken;
    }

    /**
     * Invalida el cache del token CSRF (útil tras un 419).
     */
    refreshCsrfToken() {
        this._csrfToken = null;
        return this.getCsrfToken();
    }

    // --- Métodos públicos ---

    async get(path, options = {}) {
        return this._request('GET', path, null, options);
    }

    async post(path, data, options = {}) {
        return this._request('POST', path, data, options);
    }

    async put(path, data, options = {}) {
        return this._request('PUT', path, data, options);
    }

    async patch(path, data, options = {}) {
        return this._request('PATCH', path, data, options);
    }

    async delete(path, options = {}) {
        return this._request('DELETE', path, null, options);
    }

    /**
     * POST con FormData (sin Content-Type, el navegador lo pone).
     */
    async postFormData(path, formData, options = {}) {
        return this._request('POST', path, formData, {
            ...options,
            isFormData: true,
        });
    }

    // --- Implementación interna ---

    async _request(method, path, data = null, options = {}, attempt = 1) {
        const url = this.baseUrl + path;
        const controller = new AbortController();
        const timeoutMs = options.timeout || this.timeout;
        const timeoutId = setTimeout(() => controller.abort(), timeoutMs);

        try {
            const isFormData = options.isFormData || (data instanceof FormData);

            const headers = {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': this.getCsrfToken(),
                ...(isFormData ? {} : { 'Content-Type': 'application/json' }),
                ...options.headers,
            };

            const fetchOptions = {
                method,
                headers,
                signal: controller.signal,
                credentials: 'same-origin',
            };

            if (data && method !== 'GET') {
                fetchOptions.body = isFormData ? data : JSON.stringify(data);
            }

            const response = await fetch(url, fetchOptions);
            clearTimeout(timeoutId);

            if (!response.ok) {
                let body = null;
                try { body = await response.json(); } catch { /* no json */ }
                throw new HttpError(
                    body?.message || `HTTP ${response.status}: ${response.statusText}`,
                    response.status,
                    response,
                    body
                );
            }

            const contentType = response.headers.get('content-type') || '';
            if (contentType.includes('application/json')) {
                return await response.json();
            }
            return await response.text();

        } catch (error) {
            clearTimeout(timeoutId);

            // Si es un 419 (CSRF expired), refrescar token y reintentar una vez
            if (error instanceof HttpError && error.status === 419 && attempt === 1) {
                this.refreshCsrfToken();
                return this._request(method, path, data, options, attempt + 1);
            }

            // Retry para errores de red/servidor
            if (attempt < this.retries && this._isRetryable(error)) {
                const delay = Math.min(1000 * attempt, 3000);
                await new Promise(r => setTimeout(r, delay));
                return this._request(method, path, data, options, attempt + 1);
            }

            throw error;
        }
    }

    _isRetryable(error) {
        if (error.name === 'AbortError') return true;
        if (error instanceof TypeError) return true; // network error
        if (error instanceof HttpError && error.status >= 500) return true;
        return false;
    }
}

// Exportar globalmente (compatible con scripts clásicos y módulos)
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { SharedHttpClient, HttpError };
}
window.SharedHttpClient = SharedHttpClient;
window.HttpError = HttpError;


// --- NotificationService.js ---
/**
 * =====================================================
 * SHARED INFRASTRUCTURE - NOTIFICATION SERVICE
 * =====================================================
 * Centraliza TODAS las notificaciones visuales (toasts, alertas).
 * Elimina duplicación de lógica de toasts en múltiples archivos.
 *
 * Soporta:
 * - SweetAlert2 (si disponible)
 * - Toasts nativos (fallback sin dependencias)
 *
 * Uso:
 *   SharedNotification.success('Pedido guardado');
 *   SharedNotification.error('No se pudo guardar');
 *   SharedNotification.warning('Faltan campos');
 *   SharedNotification.info('Procesando...');
 *   SharedNotification.confirm('¿Eliminar?', () => { ... });
 */

const SharedNotification = (() => {
    'use strict';

    const TOAST_CONTAINER_ID = 'shared-toast-container';

    const COLORS = {
        success: { bg: '#16a34a', icon: '✓' },
        error:   { bg: '#dc2626', icon: '✕' },
        warning: { bg: '#f59e0b', icon: '⚠' },
        info:    { bg: '#2563eb', icon: 'ℹ' },
    };

    function _getOrCreateContainer() {
        let container = document.getElementById(TOAST_CONTAINER_ID);
        if (!container) {
            container = document.createElement('div');
            container.id = TOAST_CONTAINER_ID;
            container.style.cssText =
                'position:fixed;top:20px;right:20px;z-index:99999;display:flex;flex-direction:column;gap:10px;pointer-events:none;';
            document.body.appendChild(container);
        }
        return container;
    }

    function _showNativeToast(message, type = 'info', duration = 3500) {
        const container = _getOrCreateContainer();
        const colors = COLORS[type] || COLORS.info;

        const toast = document.createElement('div');
        toast.style.cssText = `
            background:${colors.bg};color:white;padding:12px 16px;
            border-radius:10px;box-shadow:0 10px 25px rgba(0,0,0,0.18);
            font-size:13px;font-weight:600;max-width:380px;
            display:flex;align-items:center;gap:8px;
            transform:translateX(120%);transition:transform 0.25s ease;
            pointer-events:auto;
        `;
        toast.innerHTML = `<span>${colors.icon}</span><span>${_escapeHtml(message)}</span>`;
        container.appendChild(toast);

        requestAnimationFrame(() => { toast.style.transform = 'translateX(0)'; });
        setTimeout(() => {
            toast.style.transform = 'translateX(120%)';
            setTimeout(() => toast.remove(), 300);
        }, duration);
    }

    function _escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function _hasSwal() {
        return typeof window.Swal !== 'undefined';
    }

    // --- API Pública ---

    function toast(message, type = 'info', duration = 3500) {
        if (_hasSwal()) {
            const iconMap = { success: 'success', error: 'error', warning: 'warning', info: 'info' };
            window.Swal.fire({
                toast: true,
                position: 'top-end',
                icon: iconMap[type] || 'info',
                title: message,
                showConfirmButton: false,
                timer: duration,
                timerProgressBar: true,
            });
        } else {
            _showNativeToast(message, type, duration);
        }
    }

    function success(message, duration) { toast(message, 'success', duration); }
    function error(message, duration)   { toast(message, 'error', duration); }
    function warning(message, duration) { toast(message, 'warning', duration); }
    function info(message, duration)    { toast(message, 'info', duration); }

    /**
     * Confirmación con callback.
     * @param {string} message - Texto de la pregunta
     * @param {Function} onConfirm - Callback si acepta
     * @param {Object} [opts] - Opciones extra (title, confirmText, cancelText)
     */
    async function confirm(message, onConfirm, opts = {}) {
        if (_hasSwal()) {
            const result = await window.Swal.fire({
                title: opts.title || '¿Estás seguro?',
                text: message,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#2563eb',
                cancelButtonColor: '#6b7280',
                confirmButtonText: opts.confirmText || 'Sí, confirmar',
                cancelButtonText: opts.cancelText || 'Cancelar',
            });
            if (result.isConfirmed && onConfirm) {
                onConfirm();
            }
            return result.isConfirmed;
        } else {
            const accepted = window.confirm(message);
            if (accepted && onConfirm) onConfirm();
            return accepted;
        }
    }

    return { toast, success, error, warning, info, confirm };
})();

window.SharedNotification = SharedNotification;


// --- ModalManager.js ---
/**
 * =====================================================
 * SHARED INFRASTRUCTURE - MODAL MANAGER
 * =====================================================
 * Centraliza apertura/cierre de modales estilo display:flex.
 * Elimina duplicación de `style.display = 'flex'` en ~40 archivos.
 *
 * Uso:
 *   SharedModal.open('modalAnulacion');
 *   SharedModal.close('modalAnulacion');
 *   SharedModal.setupOverlayClose('modalNovedades');
 */

const SharedModal = (() => {
    'use strict';

    function open(id) {
        const el = typeof id === 'string' ? document.getElementById(id) : id;
        if (!el) return;
        el.style.display = 'flex';
        el.style.alignItems = 'center';
        el.style.justifyContent = 'center';
    }

    function close(id) {
        const el = typeof id === 'string' ? document.getElementById(id) : id;
        if (!el) return;
        el.style.display = 'none';
    }

    function toggle(id) {
        const el = typeof id === 'string' ? document.getElementById(id) : id;
        if (!el) return;
        if (el.style.display === 'none' || el.style.display === '') {
            open(el);
        } else {
            close(el);
        }
    }

    function isOpen(id) {
        const el = typeof id === 'string' ? document.getElementById(id) : id;
        return el ? el.style.display !== 'none' && el.style.display !== '' : false;
    }

    /**
     * Cierra el modal al hacer click en el overlay (el propio modal-wrapper).
     */
    function setupOverlayClose(id) {
        const el = typeof id === 'string' ? document.getElementById(id) : id;
        if (!el) return;
        el.addEventListener('click', function(e) {
            if (e.target === el) close(el);
        });
    }

    return { open, close, toggle, isOpen, setupOverlayClose };
})();

window.SharedModal = SharedModal;


// --- WebSocketClient.js ---
/**
 * =====================================================
 * SHARED INFRASTRUCTURE - WEBSOCKET CLIENT
 * =====================================================
 * Abstracción centralizada para WebSocket/real-time.
 * Elimina duplicación de inicialización de Echo en 19+ archivos.
 *
 * Métodos públicos:
 *   - subscribe(channel, event, callback)
 *   - unsubscribe(channel)
 *   - private(channel) → Para canales privados
 *   - join(channel) → Para presencia
 *   - isConnected()
 *
 * Nota: Requiere que window.EchoInstance esté disponible.
 * Usar window.waitForEcho() o window.websocket para esperar.
 */

class WebSocketClient {
    /**
     * Se subscribe a un evento en un canal público
     * @param {string} channel - Nombre del canal
     * @param {string} event - Nombre del evento
     * @param {Function} callback - Handler
     * @returns {Object} Subscription object (with stopListening())
     */
    subscribe(channel, event, callback) {
        throw new Error('subscribe() debe ser implementado por subclases');
    }

    /**
     * Cancela todas las suscripciones a un canal
     * @param {string} channel
     * @returns {void}
     */
    unsubscribe(channel) {
        throw new Error('unsubscribe() debe ser implementado por subclases');
    }

    /**
     * Accede a un canal PRIVADO (requiere autenticación)
     * @param {string} channel
     * @returns {Object} Private channel object
     */
    private(channel) {
        throw new Error('private() debe ser implementado por subclases');
    }

    /**
     * Accede a un canal de PRESENCIA (con información de usuarios)
     * @param {string} channel
     * @returns {Object} Presence channel object
     */
    join(channel) {
        throw new Error('join() debe ser implementado por subclases');
    }

    /**
     * Verifica si el WebSocket está conectado
     * @returns {boolean}
     */
    isConnected() {
        throw new Error('isConnected() debe ser implementado por subclases');
    }

    /**
     * Envía un mensaje a través de un canal privado (whisper)
     * @param {string} channel
     * @param {string} event
     * @param {*} data
     * @returns {void}
     */
    whisper(channel, event, data) {
        throw new Error('whisper() debe ser implementado por subclases');
    }
}

// Custom error
class WebSocketError extends Error {
    constructor(message, originalError = null) {
        super(message);
        this.name = 'WebSocketError';
        this.originalError = originalError;
    }
}

if (typeof module !== 'undefined' && module.exports) {
    module.exports = { WebSocketClient, WebSocketError };
}
window.WebSocketClient = WebSocketClient;
window.WebSocketError = WebSocketError;


// --- CacheRepository.js ---
/**
 * =====================================================
 * SHARED INFRASTRUCTURE - CACHE REPOSITORY
 * =====================================================
 * Abstracción centralizada para cacheo (sessionStorage, localStorage).
 * Elimina duplicación de lógica TTL y serialización en modules.
 *
 * Métodos públicos:
 *   - get(key) → value o null
 *   - set(key, value, ttl) 
 *   - has(key) → boolean
 *   - remove(key)
 *   - clear()
 *   - getOrFetch(key, fetcher, ttl) → async
 *
 * Custom Errors:
 *   - CacheError: Error general de cache
 */

class CacheRepository {
    /**
     * Obtiene un valor del cache
     * @param {string} key
     * @returns {*|null} Valor o null si no existe/expiró
     */
    get(key) {
        throw new Error('get(key) debe ser implementado por subclases');
    }

    /**
     * Guarda un valor en el cache
     * @param {string} key
     * @param {*} value - Puede ser primitivo u objeto
     * @param {number} [ttl] - Tiempo de vida en ms (opcional, sin TTL = indefinido)
     * @returns {void}
     */
    set(key, value, ttl = null) {
        throw new Error('set(key, value, ttl) debe ser implementado por subclases');
    }

    /**
     * Verifica si una clave existe y no ha expirado
     * @param {string} key
     * @returns {boolean}
     */
    has(key) {
        throw new Error('has(key) debe ser implementado por subclases');
    }

    /**
     * Elimina una clave del cache
     * @param {string} key
     * @returns {void}
     */
    remove(key) {
        throw new Error('remove(key) debe ser implementado por subclases');
    }

    /**
     * Limpia TODO el cache
     * @returns {void}
     */
    clear() {
        throw new Error('clear() debe ser implementado por subclases');
    }

    /**
     * Obtiene un valor del cache, o llama un fetcher si no existe/expiró
     * Útil para lazy-loading con fallback a API
     * 
     * @param {string} key
     * @param {Function} fetcher - async () => value
     * @param {number} [ttl] - TTL del cache en ms
     * @returns {Promise<*>} Valor cacheado o resultado de fetcher
     */
    async getOrFetch(key, fetcher, ttl = null) {
        const cached = this.get(key);
        if (cached !== null) {
            return cached;
        }

        try {
            const value = await fetcher();
            this.set(key, value, ttl);
            return value;
        } catch (error) {
            throw error;
        }
    }
}

// Custom error
class CacheError extends Error {
    constructor(message, originalError = null) {
        super(message);
        this.name = 'CacheError';
        this.originalError = originalError;
    }
}

// Exportar
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { CacheRepository, CacheError };
}
window.CacheRepository = CacheRepository;
window.CacheError = CacheError;


// --- EchoReverbWebSocketClient.js ---
/**
 * =====================================================
 * SHARED INFRASTRUCTURE - ECHO REVERB WEBSOCKET CLIENT
 * =====================================================
 * Implementación concreta del WebSocketClient usando Laravel Echo + Reverb.
 *
 * Dependencias:
 *   - window.EchoInstance (de resources/js/bootstrap.js)
 *   - window.WebSocketClient (public/js/shared/WebSocketClient.js)
 */

class EchoReverbWebSocketClient extends WebSocketClient {
    constructor(echoInstance) {
        super();
        
        if (!echoInstance) {
            throw new WebSocketError('EchoInstance no disponible. Asegúrate de que resources/js/bootstrap.js se cargó ANTES.');
        }
        
        this.echo = echoInstance;
    }

    /**
     * Suscribe a un evento en un canal público
     * @param {string} channel - Nombre del canal
     * @param {string} event - Nombre del evento
     * @param {Function} callback - Handler(data)
     * @returns {Object} Subscription object
     */
    subscribe(channel, event, callback) {
        if (!channel || !event || typeof callback !== 'function') {
            throw new Error('channel, event (con punto) y callback son requeridos');
        }

        try {
            return this.echo.channel(channel).listen(event, callback);
        } catch (error) {
            throw new WebSocketError(`Error subscribing to ${channel}.${event}`, error);
        }
    }

    /**
     * Cancela todas las suscripciones a un canal
     * @param {string} channel
     */
    unsubscribe(channel) {
        if (!channel) throw new Error('channel es requerido');
        
        try {
            this.echo.leaveChannel(channel);
        } catch (error) {
            throw new WebSocketError(`Error unsubscribing from ${channel}`, error);
        }
    }

    /**
     * Accede a un canal PRIVADO (requiere auth)
     * @param {string} channel - Ej: 'private-pedidos.4'
     * @returns {Object} Private channel object con .listen(), .whisper(), etc.
     */
    private(channel) {
        if (!channel) throw new Error('channel es requerido');
        
        try {
            return this.echo.private(channel);
        } catch (error) {
            throw new WebSocketError(`Error accessing private channel ${channel}`, error);
        }
    }

    /**
     * Accede a un canal de PRESENCIA (con datos de presencia)
     * @param {string} channel - Ej: 'presence-team.5'
     * @returns {Object} Presence channel object con .listen(), .here(), .joining(), .leaving()
     */
    join(channel) {
        if (!channel) throw new Error('channel es requerido');
        
        try {
            return this.echo.join(channel);
        } catch (error) {
            throw new WebSocketError(`Error joining presence channel ${channel}`, error);
        }
    }

    /**
     * Verifica si la conexión WebSocket está activa
     * @returns {boolean}
     */
    isConnected() {
        try {
            // Verificar el estado del socket subyacente
            const connector = this.echo?.connector;
            if (!connector) return false;
            
            // Para Pusher/Reverb (WebSocket)
            if (connector.socket?.readyState === WebSocket.OPEN) return true;
            if (connector.socket?.connected) return true;
            
            return false;
        } catch {
            return false;
        }
    }

    /**
     * Envía un mensaje a través de un canal privado (whisper)
     * @param {string} channel - Canal privado
     * @param {string} event - Evento
     * @param {*} data - Datos a enviar
     */
    whisper(channel, event, data) {
        if (!channel || !event) {
            throw new Error('channel y event son requeridos');
        }

        try {
            const privChannel = this.echo.private(channel);
            return privChannel.whisper(event, data);
        } catch (error) {
            throw new WebSocketError(`Error whispering to ${channel}.${event}`, error);
        }
    }

    /**
     * Obtiene información del estado de la conexión
     * @returns {{connected: boolean, connector: string}}
     */
    getStatus() {
        return {
            connected: this.isConnected(),
            connector: this.echo?.connector?.name || 'unknown',
        };
    }
}

// Exportar
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { EchoReverbWebSocketClient };
} else {
    window.EchoReverbWebSocketClient = EchoReverbWebSocketClient;
}


// --- SessionStorageCacheRepository.js ---
/**
 * =====================================================
 * SHARED INFRASTRUCTURE - SESSION STORAGE CACHE
 * =====================================================
 * Implementación del CacheRepository usando sessionStorage.
 * Auto-limpia valores expirados en garbage collection.
 * Fallback a localStorage si sessionStorage unavailable.
 *
 * Dependencias:
 *   - window.CacheRepository (public/js/shared/CacheRepository.js)
 */

class SessionStorageCacheRepository extends CacheRepository {
    constructor(options = {}) {
        super();
        
        this.storage = options.storage || 'session'; // 'session' o 'local'
        this.keyPrefix = options.keyPrefix || 'app_cache_';
        this.maxSize = options.maxSize || 5242880; // 5MB
        this.garbageCollectionInterval = options.garbageCollectionInterval || 300000; // 5 min
        
        this._backend = this._getBackend();
        this._startGarbageCollection();
    }

    _getBackend() {
        try {
            if (this.storage === 'session') {
                window.sessionStorage.setItem('__test__', '1');
                window.sessionStorage.removeItem('__test__');
                return window.sessionStorage;
            } else if (this.storage === 'local') {
                window.localStorage.setItem('__test__', '1');
                window.localStorage.removeItem('__test__');
                return window.localStorage;
            }
        } catch {
            console.warn(`[CacheRepository] ${this.storage}Storage no disponible, usando fallback`);
        }
        
        // Fallback a in-memory si ambos indisponibles
        return new Map();
    }

    _makeKey(key) {
        return `${this.keyPrefix}${key}`;
    }

    _encodeValue(value, ttl) {
        return JSON.stringify({
            value,
            expires: ttl ? Date.now() + ttl : null,
            timestamp: Date.now(),
        });
    }

    _decodeValue(encoded) {
        try {
            const data = JSON.parse(encoded);
            
            // Validar expiración
            if (data.expires && data.expires < Date.now()) {
                return null;
            }
            
            return data.value;
        } catch {
            return null;
        }
    }

    /**
     * Obtiene un valor del cache
     */
    get(key) {
        const storageKey = this._makeKey(key);
        
        try {
            let encoded;
            
            if (this._backend instanceof Map) {
                encoded = this._backend.get(storageKey);
            } else {
                encoded = this._backend.getItem(storageKey);
            }
            
            return encoded ? this._decodeValue(encoded) : null;
        } catch (error) {
            console.error('[CacheRepository] Error en get():', error);
            return null;
        }
    }

    /**
     * Guarda un valor en el cache
     */
    set(key, value, ttl = null) {
        const storageKey = this._makeKey(key);
        const encoded = this._encodeValue(value, ttl);
        
        try {
            // Verificar tamano aproximado
            if (this._getStorageSize() + encoded.length > this.maxSize) {
                console.warn('[CacheRepository] Cuota superada, limpiando...');
                this._runGarbageCollection();
                
                // Si sigue siendo demasiado grande, no guardar
                if (this._getStorageSize() + encoded.length > this.maxSize) {
                    throw new CacheError('Storage quota exceeded');
                }
            }
            
            if (this._backend instanceof Map) {
                this._backend.set(storageKey, encoded);
            } else {
                this._backend.setItem(storageKey, encoded);
            }
        } catch (error) {
            throw new CacheError(`Error en set(): ${error.message}`, error);
        }
    }

    /**
     * Verifica si una clave existe y no ha expirado
     */
    has(key) {
        return this.get(key) !== null;
    }

    /**
     * Elimina una clave
     */
    remove(key) {
        const storageKey = this._makeKey(key);
        
        try {
            if (this._backend instanceof Map) {
                this._backend.delete(storageKey);
            } else {
                this._backend.removeItem(storageKey);
            }
        } catch (error) {
            throw new CacheError(`Error en remove(): ${error.message}`, error);
        }
    }

    /**
     * Limpia TODO el cache
     */
    clear() {
        try {
            if (this._backend instanceof Map) {
                this._backend.clear();
            } else {
                const keysToRemove = [];
                for (let i = 0; i < this._backend.length; i++) {
                    const key = this._backend.key(i);
                    if (key?.startsWith(this.keyPrefix)) {
                        keysToRemove.push(key);
                    }
                }
                keysToRemove.forEach(key => this._backend.removeItem(key));
            }
        } catch (error) {
            throw new CacheError(`Error en clear(): ${error.message}`, error);
        }
    }

    /**
     * Limpia valores expirados (garbage collection)
     */
    _runGarbageCollection() {
        try {
            const keysToRemove = [];
            
            if (this._backend instanceof Map) {
                for (const [key, encoded] of this._backend.entries()) {
                    if (key.startsWith(this.keyPrefix)) {
                        try {
                            const data = JSON.parse(encoded);
                            if (data.expires && data.expires < Date.now()) {
                                keysToRemove.push(key);
                            }
                        } catch { /* malformed, remove */ keysToRemove.push(key); }
                    }
                }
            } else {
                for (let i = 0; i < this._backend.length; i++) {
                    const key = this._backend.key(i);
                    if (key?.startsWith(this.keyPrefix)) {
                        try {
                            const encoded = this._backend.getItem(key);
                            const data = JSON.parse(encoded);
                            if (data.expires && data.expires < Date.now()) {
                                keysToRemove.push(key);
                            }
                        } catch { keysToRemove.push(key); }
                    }
                }
            }
            
            keysToRemove.forEach(key => {
                if (this._backend instanceof Map) {
                    this._backend.delete(key);
                } else {
                    this._backend.removeItem(key);
                }
            });
        } catch (error) {
            console.error('[CacheRepository] Error en GC:', error);
        }
    }

    /**
     * Calcula el tamano aproximado del storage
     */
    _getStorageSize() {
        let size = 0;
        
        if (this._backend instanceof Map) {
            for (const [k, v] of this._backend.entries()) {
                size += k.length + v.length;
            }
        } else {
            for (let i = 0; i < this._backend.length; i++) {
                const key = this._backend.key(i);
                const value = this._backend.getItem(key);
                size += (key?.length || 0) + (value?.length || 0);
            }
        }
        
        return size;
    }

    /**
     * Inicia limpieza automática periódica
     */
    _startGarbageCollection() {
        this._gcInterval = setInterval(() => {
            this._runGarbageCollection();
        }, this.garbageCollectionInterval);
    }

    /**
     * Detiene la limpieza automática
     */
    stopGarbageCollection() {
        if (this._gcInterval) {
            clearInterval(this._gcInterval);
        }
    }
}

// Exportar
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { SessionStorageCacheRepository };
} else {
    window.SessionStorageCacheRepository = SessionStorageCacheRepository;
}


// --- bootstrap.js ---
/**
 * =====================================================
 * SHARED BOOTSTRAP - DEPENDENCY INJECTION CONTAINER
 * =====================================================
 * Inicializa y conecta todos los servicios compartidos.
 * Se carga UNA VEZ en el layout y queda disponible globalmente.
 *
 * Dependencias (cargar ANTES de este archivo):
 *   1. shared/infrastructure/HttpClient.js
 *   2. shared/infrastructure/NotificationService.js
 *   3. shared/infrastructure/ModalManager.js
 *   4. shared/WebSocketClient.js
 *   5. shared/infrastructure/EchoReverbWebSocketClient.js
 *   6. shared/CacheRepository.js
 *   7. shared/infrastructure/SessionStorageCacheRepository.js
 *
 * Después de cargar:
 *   window.shared.http          → SharedHttpClient (instancia)
 *   window.shared.notify        → SharedNotification
 *   window.shared.modal         → SharedModal
 *   window.shared.cache         → SessionStorageCacheRepository (instancia)
 *   window.shared.websocket     → EchoReverbWebSocketClient (lazy, inicializa cuando se accede)
 *   window.shared.isReady       → true
 *
 * Uso en cualquier módulo:
 *   const { http, notify, modal, cache, websocket } = window.shared;
 *   const data = await http.get('/api/pedidos');
 *   notify.success('Pedido cargado');
 *   modal.open('miModal');
 *   const cached = cache.get('myKey');
 *   websocket.subscribe('channel', 'event', handler);
 */

(function() {
    'use strict';

    if (window.shared?.isReady) {
        return;
    }

    // Instanciar HttpClient
    const http = new SharedHttpClient({
        timeout: 15000,
        retries: 2,
    });

    // Instanciar CacheRepository
    const cache = new SessionStorageCacheRepository({
        storage: 'session',
        keyPrefix: 'shared_',
        garbageCollectionInterval: 300000,
    });

    // Closure for lazy WebSocketClient initialization
    let _websocketInstance = null;

    // Crear objeto compartido base
    const sharedObj = {
        http:     http,
        notify:   SharedNotification,
        modal:    SharedModal,
        cache:    cache,
        isReady:  true,
        version:  '1.1.0',
    };

    // Agregar getter lazy para WebSocketClient
    // Se inicializa bajo demanda cuando se accede por primera vez
    Object.defineProperty(sharedObj, 'websocket', {
        get() {
            // Verificar que Echo esté disponible
            if (!window.EchoInstance) {
                throw new Error(
                    '[Shared] WebSocketClient no disponible aún. ' +
                    'Espera a que resources/js/bootstrap.js se cargue (inicializa Echo/Reverb). ' +
                    'Usa: window.waitForEcho(() => { const ws = window.shared.websocket; ... })'
                );
            }

            // Crear una sola vez, reutilizar después
            if (!_websocketInstance) {
                _websocketInstance = new EchoReverbWebSocketClient(window.EchoInstance);
            }

            return _websocketInstance;
        },
        configurable: false,
        enumerable: true,
    });

    // Registrar en namespace global (congelado para evitar mutaciones)
    window.shared = Object.freeze(sharedObj);
})();
