/**
 *  MONITOREO DE PRECARGUÍA - Panel de Control
 * 
 * Abre la consola y ejecuta:
 * window.PrendaEditorPreloader.showMonitor()
 * 
 * O copia y pega este código en la consola:
 */

(function() {
    'use strict';

    /**
     * Panel de monitoreo visual para la precarguía
     */
    window.showPrendaPreloaderMonitor = function() {
        console.clear();
        console.log('%c MONITOR DE PRECARGUÍA DE PRENDAS', 'font-size: 16px; font-weight: bold; color: #3498db;');
        console.log('═'.repeat(60));

        // Estado actual
        const status = window.PrendaEditorPreloader?.getStatus?.();
        
        if (!status) {
            console.error(' PrendaEditorPreloader no encontrado');
            return;
        }

        // Formato de estado
        const estados = {
            preloading: status.isPreloading ? ' PRECARGANDO...' : '⏹️  Inactivo',
            preloaded: status.isPreloaded ? ' PRECARGADO' : ' No precargado',
            error: status.preloadError ? ` ${status.preloadError}` : '✓ Sin errores'
        };

        console.log('%c ESTADO ACTUAL', 'font-weight: bold; color: #2ecc71; font-size: 13px;');
        console.log(`  ${estados.preloading}`);
        console.log(`  ${estados.preloaded}`);
        console.log(`  ${estados.error}`);

        console.log('\n%c CACHÉ', 'font-weight: bold; color: #e74c3c; font-size: 13px;');
        console.log(`  Scripts en cache: ${status.scriptCacheSize}`);
        console.log(`  Módulos en cache: ${status.moduleCacheSize}`);

        console.log('\n%c CONFIGURACIÓN', 'font-weight: bold; color: #f39c12; font-size: 13px;');
        console.log(`  Delay de precarguía: ${status.config.preloadDelay}ms`);
        console.log(`  Threshold idle: ${status.config.idleThreshold}ms`);

        console.log('\n%c🎮 COMANDOS DISPONIBLES', 'font-weight: bold; color: #9b59b6; font-size: 13px;');
        console.log(`
  Forzar precarguía:     window.PrendaEditorPreloader.forceReload()
  Limpiar caché:         window.PrendaEditorPreloader.clearCache()
  Ver estado:            window.PrendaEditorPreloader.getStatus()
  Verificar listo:       window.PrendaEditorPreloader.isReady()
  Precargar ahora:       window.PrendaEditorPreloader.preloadNow()
        `);

        console.log('═'.repeat(60));
        console.log('%cEventos personalizados disponibles', 'color: #1abc9c; font-size: 12px;');
        console.log(`
  'prendaEditorPreloaded' - Se dispara cuando termina la precarga
  'prendaEditorPreloadError' - Se dispara si hay error
        `);

        // Mostrar listeners activos
        console.log('\n%c👂 ESCUCHADORES REGISTRADOS', 'font-weight: bold; color: #3498db; font-size: 13px;');
        const listeners = getEventListenerCount();
        console.log(`  Total de event listeners: ${listeners}`);
    };

    /**
     * Contar listeners (aproximado)
     */
    function getEventListenerCount() {
        try {
            const listeners = getEventListeners ? getEventListeners(window) : {};
            return Object.keys(listeners).length;
        } catch (e) {
            return 'N/A (no disponible en este navegador)';
        }
    }

    /**
     * Auto-actualizar monitor cada N segundos
     */
    window.startPrendaPreloaderAutoMonitor = function(intervalMs = 2000) {
        console.log(`%c Actualizando estado cada ${intervalMs}ms...`, 'color: #16a085; font-weight: bold;');
        
        const interval = setInterval(() => {
            const status = window.PrendaEditorPreloader?.getStatus?.();
            if (status) {
                const icons = {
                    preloading: status.isPreloading ? '' : '⏹️',
                    preloaded: status.isPreloaded ? '' : '',
                    error: status.preloadError ? '' : '✓'
                };
                
                console.clear();
                console.log(`%c${icons.preloading} ${icons.preloaded} ${icons.error} | Cache: ${status.scriptCacheSize} scripts | ${new Date().toLocaleTimeString()}`, 'color: #2980b9; font-weight: bold; font-size: 12px;');
            }
        }, intervalMs);

        console.log(`%c Monitor automático iniciado (intervalo: ${intervalMs}ms)`, 'color: #16a085; font-weight: bold;');
        console.log(`%c Para detener, ejecuta: clearInterval(window.preloaderMonitorInterval)`, 'color: #e74c3c;');
        
        window.preloaderMonitorInterval = interval;
        return interval;
    };

    /**
     * Listener para eventos de precarguía
     */
    window.onPrendaEditorPreloaded = function(callback) {
        window.addEventListener('prendaEditorPreloaded', (e) => {
            console.log(`%c Precarguía completada en ${e.detail.elapsed.toFixed(0)}ms`, 'color: #27ae60; font-weight: bold;');
            if (callback) callback(e.detail);
        });
    };

    window.onPrendaEditorPreloadError = function(callback) {
        window.addEventListener('prendaEditorPreloadError', (e) => {
            console.error(`%c Error en precarguía: ${e.detail.error}`, 'color: #c0392b; font-weight: bold;');
            if (callback) callback(e.detail);
        });
    };

    /**
     * Alias corto
     */
    window.preloaderStatus = () => window.showPrendaPreloaderMonitor();
})();

// Ejecutar automáticamente al cargar la consola
if (window.PrendaEditorPreloader) {
    // console.log('%c💡 TIP: Ejecuta window.showPrendaPreloaderMonitor() para ver el estado', 'color: #f39c12; font-style: italic;');
}
