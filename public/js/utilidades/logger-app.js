/**
 * LoggerApp - Logger centralizado para toda la aplicación
 * 
 * Arquitectura de logging profesional con control por entorno:
 * - En DESARROLLO (APP_DEBUG=true): todos los niveles activos, trazabilidad completa
 * - En PRODUCCIÓN (APP_DEBUG=false): solo error y warn, consola limpia
 * 
 * Niveles: debug < info < warn < error (+ success = alias de info)
 * 
 * Uso:
 *   Logger.info('Mensaje', 'MiServicio');
 *   Logger.debug('Detalle', 'MiServicio', { data });
 *   Logger.error('Falló', 'MiServicio', errorObj);
 *   Logger.safeExec(() => localStorage.getItem('x'), 'fallback');
 * 
 * @version 2.0.0
 */

class LoggerApp {
    // ─── Niveles numéricos para comparación ───
    static LEVELS = Object.freeze({
        debug: 0,
        info: 1,
        success: 1,
        warn: 2,
        error: 3,
        silent: 4
    });

    // ─── Estilos CSS para consola (por nivel) ───
    static STYLES = Object.freeze({
        debug:   'color: #6c757d; font-weight: normal;',
        info:    'color: #0d6efd; font-weight: normal;',
        success: 'color: #198754; font-weight: bold;',
        warn:    'color: #fd7e14; font-weight: bold;',
        error:   'color: #dc3545; font-weight: bold;'
    });

    // ─── Configuración global ───
    static config = {
        // Nivel mínimo: se autodetecta desde window.APP_DEBUG
        // En desarrollo: 'debug' (muestra todo)
        // En producción: 'warn' (solo warn + error)
        nivel: null, // null = autodetectar
        timestamps: true,
        colores: true,
        // Grupos conocidos con emoji
        grupos: {
            PrendaServiceContainer: '📦',
            SharedPrendaEditor:     '✏️',
            SharedPrendaStorage:    '💾',
            SharedPrendaData:       '🗄️',
            SharedPrendaValidation: '✅',
            EventBus:               '📡',
            FormatDetector:         '🔍',
            PrendasEditorHelper:    '🛠️',
            GestionItemsUI:         '📌',
            TelaProcessor:          '🧵',
            PrendaDataBuilder:      '🏗️',
            ValidadorPrenda:        '✔️',
            Modal:                  '🪟',
            ModalCleanup:           '🧹',
            Gestor:                 '⚙️',
            StorageGuard:           '🛡️'
        }
    };

    // ─── Referencia a console original (inmutable) ───
    static _console = {
        log:   console.log.bind(console),
        warn:  console.warn.bind(console),
        error: console.error.bind(console),
        debug: console.debug.bind(console),
        group: console.group.bind(console),
        groupEnd: console.groupEnd.bind(console),
        groupCollapsed: console.groupCollapsed.bind(console),
        table: console.table.bind(console),
        time:  console.time.bind(console),
        timeEnd: console.timeEnd.bind(console)
    };

    /**
     * Detectar nivel efectivo basado en entorno
     * @returns {string} nivel activo
     */
    static _getNivelEfectivo() {
        if (this.config.nivel !== null) return this.config.nivel;
        // Autodetección: window.APP_DEBUG lo setea base.blade.php
        return (typeof window !== 'undefined' && window.APP_DEBUG) ? 'debug' : 'warn';
    }

    /**
     * ¿Estamos en modo desarrollo?
     */
    static get isDev() {
        return typeof window !== 'undefined' && window.APP_DEBUG === true;
    }

    /**
     * Configurar logger (merge parcial)
     * @param {Object} opciones
     */
    static configurar(opciones = {}) {
        if (opciones.grupos) {
            opciones.grupos = { ...this.config.grupos, ...opciones.grupos };
        }
        this.config = { ...this.config, ...opciones };
    }

    // ──────────────────────────────────────────────
    //  Métodos internos
    // ──────────────────────────────────────────────

    /**
     * ¿El nivel dado debe imprimirse?
     */
    static nivelActivo(nivel) {
        const nivelNum = this.LEVELS[nivel] ?? 1;
        const actualNum = this.LEVELS[this._getNivelEfectivo()] ?? 1;
        return nivelNum >= actualNum;
    }

    /**
     * Construir prefijo formateado
     */
    static _prefijo(grupo) {
        const parts = [];

        if (this.config.timestamps) {
            const t = new Date().toLocaleTimeString('es-ES', {
                hour12: false, hour: '2-digit', minute: '2-digit', second: '2-digit'
            });
            parts.push(t);
        }

        if (grupo) {
            const emoji = this.config.grupos[grupo] || '🔹';
            parts.push(`${emoji} [${grupo}]`);
        }

        return parts.join(' ');
    }

    /**
     * Imprimir un mensaje con formato
     */
    static _print(nivel, mensaje, grupo, datos) {
        if (!this.nivelActivo(nivel)) return;

        const prefijo = this._prefijo(grupo);
        const estilo = this.STYLES[nivel] || '';
        const consoleFn = nivel === 'error' ? this._console.error
                        : nivel === 'warn'  ? this._console.warn
                        : nivel === 'debug' ? this._console.debug
                        : this._console.log;

        if (this.config.colores && prefijo) {
            if (datos !== null && datos !== undefined) {
                consoleFn(`%c${prefijo}`, estilo, mensaje, datos);
            } else {
                consoleFn(`%c${prefijo}`, estilo, mensaje);
            }
        } else {
            if (datos !== null && datos !== undefined) {
                consoleFn(prefijo, mensaje, datos);
            } else {
                consoleFn(prefijo, mensaje);
            }
        }
    }

    // ──────────────────────────────────────────────
    //  API pública — Niveles de log
    // ──────────────────────────────────────────────

    /**
     * DEBUG — Solo visible en desarrollo
     */
    static debug(mensaje, grupo = null, datos = null) {
        this._print('debug', mensaje, grupo, datos);
    }

    /**
     * INFO — Información general (visible en desarrollo)
     */
    static info(mensaje, grupo = null, datos = null) {
        this._print('info', mensaje, grupo, datos);
    }

    /**
     * SUCCESS — Operación exitosa (usa nivel info)
     */
    static success(mensaje, grupo = null, datos = null) {
        this._print('success', `✓ ${mensaje}`, grupo, datos);
    }

    /**
     * WARN — Advertencia (siempre visible)
     */
    static warn(mensaje, grupo = null, datos = null) {
        this._print('warn', `⚠ ${mensaje}`, grupo, datos);
    }

    /**
     * ERROR — Error (SIEMPRE visible, incluso en producción)
     */
    static error(mensaje, grupo = null, error = null) {
        const prefijo = this._prefijo(grupo);
        const estilo = this.STYLES.error;

        if (error instanceof Error) {
            this._console.error(`%c${prefijo}`, estilo, `✗ ${mensaje}`, error.message);
            if (this.isDev && error.stack) {
                this._console.debug(`%c${prefijo}`, this.STYLES.debug, 'Stack:', error.stack);
            }
        } else if (error !== null && error !== undefined) {
            this._console.error(`%c${prefijo}`, estilo, `✗ ${mensaje}`, error);
        } else {
            this._console.error(`%c${prefijo}`, estilo, `✗ ${mensaje}`);
        }
    }

    // ──────────────────────────────────────────────
    //  API pública — Utilidades avanzadas
    // ──────────────────────────────────────────────

    /**
     * Log de proceso por pasos
     */
    static paso(descripcion, numPaso, totalPasos, grupo = null) {
        if (!this.nivelActivo('info')) return;
        this._print('info', `[${numPaso}/${totalPasos}] ${descripcion}`, grupo, null);
    }

    /**
     * Separador visual
     */
    static separador(titulo = '', grupo = null) {
        if (!this.nivelActivo('info')) return;
        const linea = '═'.repeat(50);
        if (titulo) {
            this._console.log(`${linea} ${titulo} ${linea}`);
        } else {
            this._console.log(linea);
        }
    }

    /**
     * Tabla (solo en desarrollo)
     */
    static tabla(datos, grupo = null) {
        if (!this.nivelActivo('info')) return;
        if (grupo) this.info(`Tabla de datos:`, grupo);
        this._console.table(datos);
    }

    /**
     * Grupo colapsable
     */
    static grupo(titulo, callback, grupo = null) {
        if (!this.nivelActivo('info')) return;
        const prefijo = this._prefijo(grupo);
        this._console.groupCollapsed(`%c${prefijo} ${titulo}`, 'color: #0066cc; font-weight: bold;');
        try {
            if (typeof callback === 'function') callback();
        } finally {
            this._console.groupEnd();
        }
    }

    /**
     * Medir tiempo de ejecución
     */
    static medirTiempo(etiqueta, callback, grupo = null) {
        if (!this.nivelActivo('debug')) return callback();

        const prefijo = this._prefijo(grupo);
        const label = `${prefijo} ⏱ ${etiqueta}`;
        this._console.time(label);
        let resultado;
        try {
            resultado = callback();
        } finally {
            this._console.timeEnd(label);
        }
        return resultado;
    }

    /**
     * Log de validación
     */
    static validar(esValido, mensaje, errores = [], grupo = null) {
        if (esValido) {
            this.success(mensaje, grupo);
        } else {
            this.error(mensaje, grupo);
            errores.forEach((err, i) => {
                this._print('error', `  ${i + 1}. ${typeof err === 'string' ? err : err.mensaje || err.message}`, grupo, null);
            });
        }
    }

    // ──────────────────────────────────────────────
    //  Ejecución segura (try/catch para storage, etc.)
    // ──────────────────────────────────────────────

    /**
     * Ejecutar una función que puede fallar por restricciones del navegador
     * (ej: acceso a localStorage en contexto restringido, extensiones, iframes)
     * 
     * @param {Function} fn - Función a ejecutar
     * @param {*} fallback - Valor de retorno si falla
     * @param {string} [contexto] - Descripción para el log de error
     * @returns {*} Resultado de fn() o fallback
     * 
     * Ejemplo:
     *   const valor = Logger.safeExec(() => localStorage.getItem('key'), null, 'leer preferencia');
     */
    static safeExec(fn, fallback = null, contexto = '') {
        try {
            return fn();
        } catch (err) {
            const msg = String(err?.message || err);
            // Errores conocidos de restricción de contexto → silenciar en producción
            const esErrorConocido = msg.includes('storage is not allowed') ||
                                    msg.includes('message channel') ||
                                    msg.includes('Access to storage') ||
                                    msg.includes('SecurityError') ||
                                    msg.includes('The operation is insecure');

            if (esErrorConocido) {
                // Solo loguear en desarrollo como debug
                this.debug(`Storage/contexto restringido${contexto ? ': ' + contexto : ''}`, 'StorageGuard', { error: msg });
            } else {
                // Error inesperado → siempre loguear como warn
                this.warn(`Error en ejecución segura${contexto ? ': ' + contexto : ''}`, 'StorageGuard', { error: msg });
            }
            return fallback;
        }
    }

    /**
     * Versión async de safeExec
     * 
     * @param {Function} asyncFn - Función async a ejecutar
     * @param {*} fallback - Valor de retorno si falla
     * @param {string} [contexto] - Descripción para el log
     * @returns {Promise<*>}
     */
    static async safeExecAsync(asyncFn, fallback = null, contexto = '') {
        try {
            return await asyncFn();
        } catch (err) {
            const msg = String(err?.message || err);
            const esErrorConocido = msg.includes('storage is not allowed') ||
                                    msg.includes('message channel') ||
                                    msg.includes('Access to storage') ||
                                    msg.includes('SecurityError') ||
                                    msg.includes('The operation is insecure');

            if (esErrorConocido) {
                this.debug(`Storage/contexto restringido (async)${contexto ? ': ' + contexto : ''}`, 'StorageGuard', { error: msg });
            } else {
                this.warn(`Error async${contexto ? ': ' + contexto : ''}`, 'StorageGuard', { error: msg });
            }
            return fallback;
        }
    }

    /**
     * Limpiar consola
     */
    static limpiar() {
        console.clear();
    }
}

// ─── Alias corto para uso rápido ───
const Logger = LoggerApp;

// Exportar globalmente
window.LoggerApp = LoggerApp;
window.Logger = LoggerApp;

