(function() {
    'use strict';

    class ErrorLoggerService {
        MAX_LOGS = 50;
        STORAGE_KEY = 'pedido_error_logs';
        enviarAlServidor = true;
        endpointServidor = '/api/errores/registrar';

        constructor() {
            this.logs = this._cargarDesdeLoacalStorage();
            this._inicializarCapturasGlobales();
        }

        /**
         * Inicializa captura global de errores no manejados
         */
        _inicializarCapturasGlobales() {
            // Capturar errores no manejados
            globalThis.addEventListener('error', (event) => {
                const tipo = event.error?.name === 'ReferenceError' ? 'ERROR_REFERENCIA' : 'ERROR_NO_MANEJADO';
                this.registrarError(tipo, `${event.filename}:${event.lineno} - ${event.message}`, {
                    filename: event.filename,
                    lineno: event.lineno,
                    colno: event.colno,
                    errorName: event.error?.name,
                    stack: event.error?.stack,
                    timestamp: new Date().toISOString()
                });
            });

            // Capturar promises rechazadas no manejadas
            globalThis.addEventListener('unhandledrejection', (event) => {
                this.registrarError('PROMISE_RECHAZADA', event.reason?.message || String(event.reason), {
                    reason: event.reason,
                    stack: event.reason?.stack,
                    timestamp: new Date().toISOString()
                });
            });
        }

        /**
         * Registra un error de imagen
         */
        registrarErrorImagen(archivo, error, contexto = {}) {
            this._agregarLog({
                tipo: 'ERROR_IMAGEN',
                timestamp: new Date().toISOString(),
                archivo: archivo?.name || 'desconocido',
                tamanio: archivo?.size || 0,
                error: error?.message || String(error),
                contexto: {
                    ...contexto,
                    usuario_id: this._obtenerUsuarioId(),
                    pedido_id: this._obtenerPedidoId()
                },
                origen: 'image-upload'
            });
        }

        /**
         * Registra un error de validación
         */
        registrarErrorValidacion(campo, valor, razon) {
            this._agregarLog({
                tipo: 'ERROR_VALIDACION',
                timestamp: new Date().toISOString(),
                campo,
                valor: typeof valor === 'string' ? valor.substring(0, 100) : String(valor),
                razon,
                contexto: {
                    usuario_id: this._obtenerUsuarioId(),
                    pedido_id: this._obtenerPedidoId()
                },
                origen: 'validation'
            });
        }

        /**
         * Registra un error de red/API
         */
        registrarErrorRed(endpoint, statusCode, mensaje, intento = 1) {
            this._agregarLog({
                tipo: 'ERROR_RED',
                timestamp: new Date().toISOString(),
                endpoint,
                statusCode,
                mensaje,
                intento,
                esReintento: intento > 1,
                contexto: {
                    usuario_id: this._obtenerUsuarioId(),
                    pedido_id: this._obtenerPedidoId()
                },
                origen: 'api'
            });
        }

        /**
         * Registra un error genérico
         */
        registrarError(tipo, mensaje, detalles = {}) {
            this._agregarLog({
                tipo: tipo || 'ERROR_GENERICO',
                timestamp: new Date().toISOString(),
                mensaje,
                detalles: {
                    ...detalles,
                    usuario_id: this._obtenerUsuarioId(),
                    pedido_id: this._obtenerPedidoId()
                },
                origen: 'general'
            });
        }

        /**
         * Registra un éxito (para contexto)
         */
        registrarExito(operacion, detalles = {}) {
            this._agregarLog({
                tipo: 'EXITO',
                timestamp: new Date().toISOString(),
                operacion,
                detalles: {
                    ...detalles,
                    usuario_id: this._obtenerUsuarioId(),
                    pedido_id: this._obtenerPedidoId()
                },
                origen: 'general'
            });
        }

        /**
         * Obtiene todos los logs
         */
        obtenerLogs() {
            return [...this.logs];
        }

        /**
         * Obtiene logs de un tipo específico
         */
        obtenerLogsPorTipo(tipo) {
            return this.logs.filter(log => log.tipo === tipo);
        }

        /**
         * Obtiene logs recientes (últimas N horas)
         */
        obtenerLogsRecientes(horas = 24) {
            const ahora = new Date();
            const limiteTime = ahora.getTime() - (horas * 60 * 60 * 1000);

            return this.logs.filter(log => {
                const logTime = new Date(log.timestamp).getTime();
                return logTime >= limiteTime;
            });
        }

        /**
         * Obtiene resumen de errores
         */
        obtenerResumen() {
            const resumen = {
                total: this.logs.length,
                porTipo: {},
                porOrigen: {},
                ultimasHoras24: this.obtenerLogsRecientes(24).length,
                ultimos30Min: this.obtenerLogsRecientes(0.5).length
            };

            this.logs.forEach(log => {
                // Contar por tipo
                resumen.porTipo[log.tipo] = (resumen.porTipo[log.tipo] || 0) + 1;
                // Contar por origen
                resumen.porOrigen[log.origen] = (resumen.porOrigen[log.origen] || 0) + 1;
            });

            return resumen;
        }

        /**
         * Limpia los logs
         */
        limpiar() {
            this.logs = [];
            localStorage.removeItem(this.STORAGE_KEY);
            console.info('[ErrorLoggerService] Logs limpios');
        }

        /**
         * Exporta los logs como JSON (para enviar al servidor)
         */
        exportarJSON() {
            return JSON.stringify(this.logs, null, 2);
        }

        /**
         * Exporta resumen formateado
         */
        exportarResumen() {
            const resumen = this.obtenerResumen();
            let texto = '=== RESUMEN DE ERRORES ===\n\n';
            texto += `Total de logs: ${resumen.total}\n`;
            texto += `Últimas 24h: ${resumen.ultimasHoras24}\n`;
            texto += `Últimos 30min: ${resumen.ultimos30Min}\n\n`;

            texto += 'POR TIPO:\n';
            Object.entries(resumen.porTipo).forEach(([tipo, count]) => {
                texto += `  - ${tipo}: ${count}\n`;
            });

            texto += '\nPOR ORIGEN:\n';
            Object.entries(resumen.porOrigen).forEach(([origen, count]) => {
                texto += `  - ${origen}: ${count}\n`;
            });

            return texto;
        }

        // ============ PRIVADO ============

        _agregarLog(logEntry) {
            // Agregar a memoria
            this.logs.unshift(logEntry);

            // Mantener máximo
            if (this.logs.length > this.MAX_LOGS) {
                this.logs = this.logs.slice(0, this.MAX_LOGS);
            }

            // Guardar en localStorage
            this._guardarEnLocalStorage();

            // Enviar al servidor si está habilitado
            if (this.enviarAlServidor && logEntry.tipo.startsWith('ERROR')) {
                this._enviarAlServidor(logEntry);
            }

            // Log en consola para inmediatez
            console.log(`[ErrorLogger] ${logEntry.tipo}:`, logEntry);
        }

        /**
         * Envía un error al servidor con reintentos
         */
        _enviarAlServidor(logEntry, intento = 1, maxIntentos = 3) {
            // No bloquear la ejecución - enviar en background
            fetch(this.endpointServidor, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': this._obtenerCSRFToken()
                },
                body: JSON.stringify({
                    ...logEntry,
                    tipo: logEntry.tipo.startsWith('ERROR') ? logEntry.tipo : `ERROR_${logEntry.tipo}`,
                    origen: logEntry.origen || 'client-js',
                    url_pagina: globalThis.location.href
                }),
                credentials: 'include',
                timeout: 5000
            })
            .then(response => {
                if (!response.ok) {
                    if (response.status === 401 || response.status === 403 || response.status === 419) {
                        console.warn(`[ErrorLogger] No se enviará el error por autenticación inválida (HTTP ${response.status})`);
                        return;
                    }
                    throw new Error(`HTTP ${response.status}`);
                }
                console.log('[ErrorLogger] Error enviado al servidor:', logEntry.tipo);
            })
            .catch(error => {
                if (intento < maxIntentos) {
                    // Reintentar después de espera exponencial
                    const espera = Math.min(1000 * Math.pow(2, intento - 1), 5000);
                    console.warn(`[ErrorLogger] Reintentando en ${espera}ms (intento ${intento}/${maxIntentos}):`, error.message);
                    setTimeout(() => this._enviarAlServidor(logEntry, intento + 1, maxIntentos), espera);
                } else {
                    console.warn('[ErrorLogger] No se pudo enviar error al servidor después de', maxIntentos, 'intentos:', error.message);
                }
            });
        }

        /**
         * Obtiene el token CSRF del DOM
         */
        _obtenerCSRFToken() {
            return document.querySelector('meta[name="csrf-token"]')?.content ||
                   document.querySelector('input[name="_token"]')?.value ||
                   '';
        }

        /**
         * Obtiene el ID del usuario actual (asesor)
         * Busca en: meta tag, global, localStorage
         */
        _obtenerUsuarioId() {
            // Opción 1: Meta tag
            const metaUsuario = document.querySelector('meta[name="user-id"]')?.content;
            if (metaUsuario) return Number(metaUsuario);

            // Opción 2: Variable global (Laravel Blade)
            if (globalThis.usuarioId) return Number(globalThis.usuarioId);
            if (globalThis.userId) return Number(globalThis.userId);
            if (globalThis.AUTH?.user?.id) return Number(globalThis.AUTH.user.id);

            // Opción 3: Data attribute en body
            const dataUserId = document.body.dataset.userId;
            if (dataUserId) return Number(dataUserId);

            // Opción 4: De localStorage (fallback)
            const stored = localStorage.getItem('current_user_id');
            return stored ? Number(stored) : null;
        }

        /**
         * Obtiene el ID del pedido actual (si está en edición)
         * Busca en: globals, meta tag, data attributes
         */
        _obtenerPedidoId() {
            // Opción 1: Variables globales de edición
            if (globalThis.pedidoEditarId) return Number(globalThis.pedidoEditarId);
            if (globalThis.pedidoId) return Number(globalThis.pedidoId);
            if (globalThis.currentPedidoId) return Number(globalThis.currentPedidoId);

            // Opción 2: De URL (si está en /pedidos/123/editar)
            const urlRegex = /\/pedidos\/(\d+)/;
            const urlMatch = urlRegex.exec(globalThis.location.pathname);
            if (urlMatch) return Number(urlMatch[1]);

            // Opción 3: De query parameter (?edit=123)
            const params = new URLSearchParams(globalThis.location.search);
            if (params.has('edit')) return Number(params.get('edit'));
            if (params.has('pedido_id')) return Number(params.get('pedido_id'));

            // Opción 4: Data attribute
            const dataPedidoId = document.body.dataset.pedidoId;
            if (dataPedidoId) return Number(dataPedidoId);

            return null;
        }

        _guardarEnLocalStorage() {
            try {
                localStorage.setItem(this.STORAGE_KEY, JSON.stringify(this.logs));
            } catch (error) {
                console.warn('[ErrorLoggerService] No se pudo guardar en localStorage:', error.message);
            }
        }

        _cargarDesdeLoacalStorage() {
            try {
                const stored = localStorage.getItem(this.STORAGE_KEY);
                return stored ? JSON.parse(stored) : [];
            } catch (error) {
                console.warn('[ErrorLoggerService] No se pudo cargar desde localStorage:', error.message);
                return [];
            }
        }
    }

    globalThis.ErrorLoggerService = new ErrorLoggerService();
})();
