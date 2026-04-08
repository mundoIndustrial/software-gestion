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
