/**
 * Infrastructure Layer - HTTP Client
 * 
 * Abstracción para todas las peticiones HTTP
 * Maneja errores, timeouts, retry logic
 * 
 * Ventajas:
 * - Intercambiable (mock para tests)
 * - Lógica centralizada de HTTP
 * - Fácil de debuggear y extender
 */

class HttpClient {
    constructor(config = {}) {
        this.baseUrl = config.baseUrl || '';
        this.timeout = config.timeout || 10000;
        this.headers = config.headers || {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        };
        this.retries = config.retries || 3;
    }

    /**
     * GET request
     */
    async get(path, options = {}) {
        return this._request('GET', path, null, options);
    }

    /**
     * POST request
     */
    async post(path, data, options = {}) {
        return this._request('POST', path, data, options);
    }

    /**
     * PUT request
     */
    async put(path, data, options = {}) {
        return this._request('PUT', path, data, options);
    }

    /**
     * DELETE request
     */
    async delete(path, options = {}) {
        return this._request('DELETE', path, null, options);
    }

    /**
     * Petición con reintentos y error handling
     * @private
     */
    async _request(method, path, data = null, options = {}, attemptNumber = 1) {
        const url = this.baseUrl + path;
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), this.timeout);

        try {
            const fetchOptions = {
                method,
                headers: { ...this.headers, ...options.headers },
                signal: controller.signal,
            };

            if (data) {
                fetchOptions.body = JSON.stringify(data);
            }

            const response = await fetch(url, fetchOptions);
            clearTimeout(timeoutId);

            if (!response.ok) {
                throw new HttpError(
                    `HTTP ${response.status}: ${response.statusText}`,
                    response.status,
                    response
                );
            }

            const contentType = response.headers.get('content-type');
            if (contentType && contentType.includes('application/json')) {
                return await response.json();
            }

            return await response.text();
        } catch (error) {
            clearTimeout(timeoutId);

            // Retry logic para errores de red
            if (attemptNumber < this.retries && this._isRetryableError(error)) {
                console.warn(`[HttpClient] Reintentando ${attemptNumber}/${this.retries}:`, path);
                return this._request(method, path, data, options, attemptNumber + 1);
            }

            throw error;
        }
    }

    /**
     * Determina si un error es reintentable
     * @private
     */
    _isRetryableError(error) {
        // Retry en timeouts o errores de red
        return error.name === 'AbortError' || 
               error instanceof TypeError ||
               (error instanceof HttpError && error.status >= 500);
    }
}

/**
 * Custom Error para HTTP
 */
class HttpError extends Error {
    constructor(message, status, response) {
        super(message);
        this.name = 'HttpError';
        this.status = status;
        this.response = response;
    }
}

// Exportar
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { HttpClient, HttpError };
} else {
    window.HttpClient = HttpClient;
    window.HttpError = HttpError;
}
