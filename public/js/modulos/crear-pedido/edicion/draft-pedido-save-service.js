(function() {
    'use strict';

    const RETRY_CONFIG = {
        maxRetries: 3,
        baseDelay: 1000, // ms
        maxDelay: 5000, // ms
        backoffMultiplier: 2,
        retryableErrors: [
            'ECONNREFUSED', // Conexión rechazada
            'ECONNRESET',   // Conexión reiniciada
            'ETIMEDOUT',    // Timeout
            'EHOSTUNREACH', // Host inaccesible
            408,            // Request Timeout
            429,            // Too Many Requests
            500,            // Internal Server Error
            502,            // Bad Gateway
            503,            // Service Unavailable
            504             // Gateway Timeout
        ]
    };

    function _calcularDelayReintento(intentoActual) {
        const delay = Math.min(
            RETRY_CONFIG.baseDelay * Math.pow(RETRY_CONFIG.backoffMultiplier, intentoActual),
            RETRY_CONFIG.maxDelay
        );
        // Agregar jitter aleatorio (±20%)
        const jitter = delay * 0.2 * (Math.random() - 0.5);
        return Math.max(100, delay + jitter);
    }

    function _esErrorReintentable(error, statusCode) {
        if (error && typeof error === 'string') {
            return RETRY_CONFIG.retryableErrors.some(err =>
                String(err) === error || error.includes(String(err))
            );
        }
        return statusCode && RETRY_CONFIG.retryableErrors.includes(statusCode);
    }

    function _calcularTimeoutDinamico(formData) {
        // Estimación simple: ~50KB por segundo (conexión típica)
        let estimatedBytes = 0;

        if (formData instanceof FormData) {
            try {
                for (const [, value] of formData.entries()) {
                    if (value instanceof File) {
                        estimatedBytes += value.size;
                    } else if (typeof value === 'string') {
                        estimatedBytes += value.length;
                    }
                }
            } catch (error) {
                console.warn('[DraftPedidoSaveService] No se pudo calcular tamaño de FormData:', error.message);
                return 30000;
            }
        }

        // Timeout = tamaño / velocidad + buffer de procesamiento
        const baseTimeout = Math.ceil((estimatedBytes / (50 * 1024)) * 1000);
        const timeout = Math.max(10000, Math.min(60000, baseTimeout + 5000));

        console.log('[DraftPedidoSaveService] Timeout dinámico calculado:', {
            estimatedBytes,
            timeoutMs: timeout,
            velocidadEstimada: '~50KB/s'
        });

        return timeout;
    }

    async function enviarBorrador(formData, opciones = {}) {
        const modoEdicion = opciones.modoEdicion || false;
        const pedidoId = opciones.pedidoId || null;
        const esActualizacion = !!(modoEdicion && pedidoId && !isNaN(pedidoId) && pedidoId > 0);
        let endpoint = opciones.endpointCrear || '/api/asesores/pedidos/borrador';
        let metodo = 'POST';
        let intentoActual = 0;

        // Compatibilidad Laravel/PHP: multipart con PUT puede perder campos.
        // Para actualizar se envia POST con _method=PUT.
        if (esActualizacion) {
            endpoint = `/api/asesores/pedidos/${pedidoId}/borrador`;
            metodo = 'POST';
            if (formData && typeof formData.set === 'function') {
                formData.set('_method', 'PUT');
            }

            console.warn('[DraftPedidoSaveService] MODO ACTUALIZACION OK', {
                endpoint,
                metodoReal: 'PUT',
                metodoTransporte: metodo,
                pedidoId,
                timestamp: new Date().toISOString()
            });
        } else {
            console.warn('[DraftPedidoSaveService] MODO CREACION OK', {
                endpoint,
                metodo,
                timestamp: new Date().toISOString()
            });
        }

        const headers = {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        };

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content
                        || document.querySelector('input[name="_token"]')?.value;
        if (csrfToken) {
            headers['X-CSRF-TOKEN'] = csrfToken;
        }

        // Idempotencia solo en creacion.
        if (!esActualizacion && window.idempotencyService) {
            const idempotencyKey = window.idempotencyService.obtenerIdempotencyKey();
            if (idempotencyKey) {
                headers['X-Idempotency-Key'] = idempotencyKey;
                console.warn('[DraftPedidoSaveService] Idempotency-Key agregado', {
                    key: idempotencyKey
                });
            }
        }

        const timeoutMs = _calcularTimeoutDinamico(formData);

        async function _intentarEnvio(intento = 0) {
            try {
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), timeoutMs);

                const response = await fetch(endpoint, {
                    method: metodo,
                    body: formData,
                    headers,
                    credentials: 'include',
                    signal: controller.signal
                });

                clearTimeout(timeoutId);
                intentoActual = intento;

                let resultado;
                try {
                    resultado = await response.json();
                } catch (parseError) {
                    console.error('[DraftPedidoSaveService] Error al parsear JSON de respuesta:', {
                        status: response.status,
                        statusText: response.statusText,
                        error: parseError.message,
                        intento: intento + 1
                    });

                    // Si el error es reintentable, reintentar
                    if (intento < RETRY_CONFIG.maxRetries && _esErrorReintentable(parseError.message, response.status)) {
                        const delay = _calcularDelayReintento(intento);
                        console.warn(`[DraftPedidoSaveService] Error parseando respuesta, reintentando en ${delay}ms (intento ${intento + 1}/${RETRY_CONFIG.maxRetries})`);
                        await new Promise(resolve => setTimeout(resolve, delay));
                        return _intentarEnvio(intento + 1);
                    }

                    resultado = {
                        success: false,
                        message: `Error del servidor (HTTP ${response.status}). La respuesta no es JSON válido.`,
                        error: parseError.message
                    };
                }

                // Si la respuesta no es exitosa y es reintentable, reintentar
                if (!resultado.success && intento < RETRY_CONFIG.maxRetries && _esErrorReintentable(null, response.status)) {
                    const delay = _calcularDelayReintento(intento);
                    console.warn(`[DraftPedidoSaveService] Respuesta no exitosa (${response.status}), reintentando en ${delay}ms (intento ${intento + 1}/${RETRY_CONFIG.maxRetries})`);
                    await new Promise(resolve => setTimeout(resolve, delay));
                    return _intentarEnvio(intento + 1);
                }

                return { response, resultado, intentoActual: intento + 1 };

            } catch (error) {
                console.error('[DraftPedidoSaveService] Error en intento de envío:', {
                    error: error.message,
                    name: error.name,
                    intento: intento + 1
                });

                // Reintentar si es error de red/timeout y no hemos agotado intentos
                if (intento < RETRY_CONFIG.maxRetries && _esErrorReintentable(error.message, null)) {
                    const delay = _calcularDelayReintento(intento);
                    console.warn(`[DraftPedidoSaveService] Error de red, reintentando en ${delay}ms (intento ${intento + 1}/${RETRY_CONFIG.maxRetries})`);
                    await new Promise(resolve => setTimeout(resolve, delay));
                    return _intentarEnvio(intento + 1);
                }

                // Si agotamos reintentos o error no reintentable, devolver error
                return {
                    response: null,
                    resultado: {
                        success: false,
                        message: `Error de conexión: ${error.message}. Por favor verifica tu conexión a Internet.`,
                        error: error.message,
                        esErrorFinal: true
                    },
                    intentoActual: intento + 1
                };
            }
        }

        return await _intentarEnvio();
    }

    window.DraftPedidoSaveService = {
        enviarBorrador,
        // Exponer configuración para pruebas/ajustes
        _getRetryConfig: () => RETRY_CONFIG
    };
})();
