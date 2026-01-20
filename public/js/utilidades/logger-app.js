/**
 * LoggerApp - Logger centralizado para toda la aplicación
 * 
 * Centraliza TODOS los logs con niveles y formateo consistente:
 * - debug: Información de debugging
 * - info: Información general
 * - warn: Advertencias
 * - error: Errores
 * - success: Operaciones exitosas
 * 
 * Objetivo: Reemplazar 100+ console.log dispersos
 * Beneficio: Logs consistentes, fácil de modificar estrategia global
 * 
 * @author Phase 3 Refactorización
 * @version 1.0.0
 */

class LoggerApp {
    // Configuración global
    static config = {
        nivel: 'info', // debug, info, warn, error, success
        prefijo: '[APP]',
        timestamps: true,
        colores: true,
        grupos: {
            GestionItemsUI: '📌',
            TelaProcessor: '',
            PrendaDataBuilder: '🏗️',
            ValidadorPrenda: '✔️',
            Modal: '🪟',
            Gestor: '💾'
        }
    };

    /**
     * Configurar logger
     * 
     * @param {Object} opciones - Opciones de configuración
     */
    static configurar(opciones = {}) {
        this.config = { ...this.config, ...opciones };
    }

    /**
     * Obtener prefijo con timestamp
     * 
     * @param {string} grupo - Grupo del log
     * @returns {string} Prefijo formateado
     */
    static obtenerPrefijo(grupo) {
        let prefijo = this.config.prefijo;

        if (grupo) {
            const emoji = this.config.grupos[grupo] || '🔹';
            prefijo += ` ${emoji} [${grupo}]`;
        }

        if (this.config.timestamps) {
            const hora = new Date().toLocaleTimeString('es-ES', {
                hour12: false,
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
            prefijo += ` ${hora}`;
        }

        return prefijo;
    }

    /**
     * Log de nivel DEBUG
     * 
     * @param {string} mensaje - Mensaje a loguear
     * @param {string} grupo - Grupo del log (opcional)
     * @param {*} datos - Datos adicionales (opcional)
     */
    static debug(mensaje, grupo = null, datos = null) {
        if (this.nivelActivo('debug')) {
            const prefijo = this.obtenerPrefijo(grupo);
            console.debug(`%c${prefijo} ${mensaje}`, 'color: #888; font-weight: bold;');
            if (datos !== null) {
                console.debug('  └─ Datos:', datos);
            }
        }
    }

    /**
     * Log de nivel INFO (es el nivel por defecto)
     * 
     * @param {string} mensaje - Mensaje a loguear
     * @param {string} grupo - Grupo del log (opcional)
     * @param {*} datos - Datos adicionales (opcional)
     */
    static info(mensaje, grupo = null, datos = null) {
        if (this.nivelActivo('info')) {
            const prefijo = this.obtenerPrefijo(grupo);
            console.log(`%c${prefijo} ${mensaje}`, 'color: #0066cc; font-weight: bold;');
            if (datos !== null) {
                console.log('  └─ Datos:', datos);
            }
        }
    }

    /**
     * Log de nivel WARN (Advertencias)
     * 
     * @param {string} mensaje - Mensaje a loguear
     * @param {string} grupo - Grupo del log (opcional)
     * @param {*} datos - Datos adicionales (opcional)
     */
    static warn(mensaje, grupo = null, datos = null) {
        if (this.nivelActivo('warn')) {
            const prefijo = this.obtenerPrefijo(grupo);
            console.warn(`%c${prefijo}   ${mensaje}`, 'color: #ff9900; font-weight: bold;');
            if (datos !== null) {
                console.warn('  └─ Datos:', datos);
            }
        }
    }

    /**
     * Log de nivel ERROR
     * 
     * @param {string} mensaje - Mensaje a loguear
     * @param {string} grupo - Grupo del log (opcional)
     * @param {Error} error - Objeto error (opcional)
     */
    static error(mensaje, grupo = null, error = null) {
        // Los errores siempre se muestran
        const prefijo = this.obtenerPrefijo(grupo);
        console.error(`%c${prefijo}  ${mensaje}`, 'color: #cc0000; font-weight: bold; font-size: 14px;');
        if (error) {
            console.error('  Error:', error);
            if (error.stack) {
                console.error('  Stack:', error.stack);
            }
        }
    }

    /**
     * Log de nivel SUCCESS (Operaciones exitosas)
     * 
     * @param {string} mensaje - Mensaje a loguear
     * @param {string} grupo - Grupo del log (opcional)
     * @param {*} datos - Datos adicionales (opcional)
     */
    static success(mensaje, grupo = null, datos = null) {
        if (this.nivelActivo('info')) {
            const prefijo = this.obtenerPrefijo(grupo);
            console.log(`%c${prefijo}  ${mensaje}`, 'color: #00aa00; font-weight: bold; font-size: 14px;');
            if (datos !== null) {
                console.log('  └─ Datos:', datos);
            }
        }
    }

    /**
     * Log de proceso en pasos
     * Útil para loguear múltiples pasos de un proceso
     * 
     * @param {string} paso - Descripción del paso
     * @param {number} numPaso - Número del paso
     * @param {number} totalPasos - Total de pasos
     * @param {string} grupo - Grupo del log
     */
    static paso(paso, numPaso, totalPasos, grupo = null) {
        if (this.nivelActivo('info')) {
            const progreso = `[${numPaso}/${totalPasos}]`;
            const prefijo = this.obtenerPrefijo(grupo);
            console.log(`%c${prefijo} ${progreso} ${paso}`, 'color: #6600cc; font-weight: bold;');
        }
    }

    /**
     * Log de separador para organizar output
     * 
     * @param {string} titulo - Título del separador
     * @param {string} grupo - Grupo del log (opcional)
     */
    static separador(titulo = '', grupo = null) {
        const prefijo = this.obtenerPrefijo(grupo);
        const linea = '═'.repeat(60);
        console.log(`%c${linea}`, 'color: #999;');
        if (titulo) {
            console.log(`%c${prefijo} ${titulo}`, 'color: #666; font-weight: bold;');
        }
    }

    /**
     * Verificar si el nivel actual debe ser logueado
     * 
     * @param {string} nivel - Nivel a verificar
     * @returns {boolean} true si debe ser logueado
     */
    static nivelActivo(nivel) {
        const niveles = { debug: 0, info: 1, warn: 2, error: 3, success: 1 };
        const nivelActual = niveles[this.config.nivel] || 1;
        return niveles[nivel] >= nivelActual;
    }

    /**
     * Log tabla para mostrar datos en formato tabla
     * 
     * @param {Array} datos - Array de objetos para mostrar en tabla
     * @param {string} grupo - Grupo del log
     */
    static tabla(datos, grupo = null) {
        if (this.nivelActivo('info')) {
            const prefijo = this.obtenerPrefijo(grupo);
            console.log(`${prefijo} Tabla de datos:`);
            console.table(datos);
        }
    }

    /**
     * Log de grupo colapsable
     * 
     * @param {string} titulo - Título del grupo
     * @param {Function} callback - Función con logs adicionales
     * @param {string} grupo - Grupo del log
     */
    static grupo(titulo, callback, grupo = null) {
        if (this.nivelActivo('info')) {
            const prefijo = this.obtenerPrefijo(grupo);
            console.group(`%c${prefijo} ${titulo}`, 'color: #0066cc; font-weight: bold;');
            if (typeof callback === 'function') {
                callback();
            }
            console.groupEnd();
        }
    }

    /**
     * Log de tiempo de ejecución
     * 
     * @param {string} etiqueta - Etiqueta para medir
     * @param {Function} callback - Función a medir
     * @param {string} grupo - Grupo del log
     */
    static medirTiempo(etiqueta, callback, grupo = null) {
        if (this.nivelActivo('debug')) {
            const prefijo = this.obtenerPrefijo(grupo);
            console.time(`${prefijo} ${etiqueta}`);
            let resultado;
            try {
                resultado = callback();
            } finally {
                console.timeEnd(`${prefijo} ${etiqueta}`);
            }
            return resultado;
        } else {
            return callback();
        }
    }

    /**
     * Log de validación
     * Muestra validaciones exitosas o fallidas
     * 
     * @param {boolean} válido - Si es válido
     * @param {string} mensaje - Mensaje
     * @param {Array} errores - Array de errores (si los hay)
     * @param {string} grupo - Grupo del log
     */
    static validar(válido, mensaje, errores = [], grupo = null) {
        if (válido) {
            this.success(`✓ ${mensaje}`, grupo);
        } else {
            this.error(`✗ ${mensaje}`, grupo);
            errores.forEach((error, idx) => {
                console.error(`    [${idx + 1}] ${error}`);
            });
        }
    }

    /**
     * Limpiar logs en consola
     */
    static limpiar() {
        console.clear();
    }
}

// Exportar globalmente
window.LoggerApp = LoggerApp;
