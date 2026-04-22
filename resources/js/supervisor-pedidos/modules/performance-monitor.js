/**
 * Performance Monitor - Logging detallado de performance en frontend
 *
 * Responsabilidades:
 * - Medir tiempos de módulos
 * - Rastrear API calls
 * - Medir rendering y DOM
 * - Logging en consola y servidor
 */

export class PerformanceMonitor {
    constructor() {
        this.startTime = performance.now();
        this.marks = [];
        this.apiCalls = [];
        this.sessionId = this.generateSessionId();

        this.init();
    }

    init() {
        // Marcar inicio
        this.mark('PAGE_LOAD_START', {
            pathname: window.location.pathname,
            userAgent: navigator.userAgent.substring(0, 50),
        });

        // Escuchar eventos de carga
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                this.mark('DOM_CONTENT_LOADED');
            });
        } else {
            this.mark('DOM_CONTENT_LOADED (pre-loaded)');
        }

        window.addEventListener('load', () => {
            this.mark('WINDOW_LOAD_COMPLETE');
        });

        // Interceptar fetch para medir API calls
        this.interceptFetch();

        console.log('%c[PERF] Monitor iniciado', 'color: #3b82f6; font-weight: bold;');
    }

    /**
     * Generar ID único de sesión
     */
    generateSessionId() {
        return `perf-${Date.now()}-${Math.random().toString(36).substring(7)}`;
    }

    /**
     * Marcar un hito
     */
    mark(label, details = {}) {
        const now = performance.now();
        const delta = this.marks.length > 0
            ? now - this.marks[this.marks.length - 1].timestamp
            : now - this.startTime;

        const mark = {
            label,
            timestamp: now,
            delta,
            totalTime: now - this.startTime,
            memory: performance.memory ? {
                usedJSHeapSize: (performance.memory.usedJSHeapSize / 1024 / 1024).toFixed(2) + ' MB',
                totalJSHeapSize: (performance.memory.totalJSHeapSize / 1024 / 1024).toFixed(2) + ' MB',
            } : null,
            details,
        };

        this.marks.push(mark);

        this.logMark(label, delta, mark.totalTime, details);
    }

    /**
     * Loguear marca en consola
     */
    logMark(label, delta, totalTime, details = {}) {
        const deltaStr = `+${delta.toFixed(2)}ms`;
        const totalStr = `${totalTime.toFixed(2)}ms`;

        const style = delta > 1000 ? 'color: #ef4444; font-weight: bold;' // red para > 1s
            : delta > 500 ? 'color: #f97316; font-weight: bold;' // orange para > 500ms
            : 'color: #10b981;'; // green

        console.log(
            `%c⏱️ ${label}%c ${deltaStr} (total: ${totalStr})`,
            style,
            'color: #6b7280;',
            details
        );
    }

    /**
     * Rastrear llamada API
     */
    async trackApiCall(label, promise) {
        const startTime = performance.now();
        const mark = {
            label,
            startTime,
            status: null,
            duration: null,
            size: null,
        };

        try {
            const response = await promise;
            const duration = performance.now() - startTime;

            mark.status = response.status;
            mark.duration = duration;

            // Intentar obtener tamaño de respuesta
            if (response.headers) {
                const contentLength = response.headers.get('content-length');
                if (contentLength) {
                    mark.size = `${(contentLength / 1024).toFixed(2)} KB`;
                }
            }

            this.apiCalls.push(mark);

            const style = duration > 2000 ? 'color: #ef4444;'
                : duration > 1000 ? 'color: #f97316;'
                : 'color: #10b981;';

            console.log(
                `%c🔌 API: ${label}%c ${response.status} ${duration.toFixed(2)}ms ${mark.size || ''}`,
                style,
                'color: #6b7280;'
            );

            return response;
        } catch (error) {
            const duration = performance.now() - startTime;
            mark.status = 'ERROR';
            mark.duration = duration;
            mark.error = error.message;

            this.apiCalls.push(mark);

            console.error(
                `%c❌ API: ${label}%c ${duration.toFixed(2)}ms`,
                'color: #ef4444;',
                'color: #6b7280;',
                error
            );

            throw error;
        }
    }

    /**
     * Interceptar fetch global
     */
    interceptFetch() {
        const originalFetch = window.fetch;

        window.fetch = async function(...args) {
            const url = typeof args[0] === 'string' ? args[0] : args[0].url;
            const startTime = performance.now();

            try {
                const response = await originalFetch.apply(this, args);
                const duration = performance.now() - startTime;

                // Extraer endpoint
                const endpoint = url.split('?')[0].substring(url.lastIndexOf('/') + 1);
                const style = duration > 2000 ? 'color: #ef4444;'
                    : duration > 1000 ? 'color: #f97316;'
                    : 'color: #10b981;';

                console.log(
                    `%c🔌 ${response.status}%c ${duration.toFixed(0)}ms ${endpoint}`,
                    style,
                    'color: #6b7280;'
                );

                return response;
            } catch (error) {
                const duration = performance.now() - startTime;
                console.error(
                    `%c❌ FETCH ERROR%c ${duration.toFixed(0)}ms`,
                    'color: #ef4444;',
                    'color: #6b7280;',
                    error
                );
                throw error;
            }
        };
    }

    /**
     * Obtener resumen
     */
    getSummary() {
        const totalTime = performance.now() - this.startTime;

        const summary = {
            sessionId: this.sessionId,
            totalTime: `${totalTime.toFixed(2)}ms`,
            marks: this.marks.map(m => ({
                label: m.label,
                delta: `${m.delta.toFixed(2)}ms`,
                totalTime: `${m.totalTime.toFixed(2)}ms`,
            })),
            apiCalls: this.apiCalls,
            navigationTiming: this.getNavigationTiming(),
        };

        return summary;
    }

    /**
     * Obtener timing de navegación
     */
    getNavigationTiming() {
        if (!window.performance || !window.performance.timing) {
            return null;
        }

        const timing = window.performance.timing;
        const paint = window.performance.getEntriesByType('paint');

        return {
            'DNS lookup': timing.domainLookupEnd - timing.domainLookupStart,
            'TCP connection': timing.connectEnd - timing.connectStart,
            'Request time': timing.responseStart - timing.requestStart,
            'Response time': timing.responseEnd - timing.responseStart,
            'DOM Parse': timing.domInteractive - timing.domLoading,
            'DOM Interactive': timing.domInteractive - timing.fetchStart,
            'DOM Complete': timing.domComplete - timing.fetchStart,
            'Resource Load': timing.loadEventStart - timing.domComplete,
            'Total Load': timing.loadEventEnd - timing.fetchStart,
            'First Paint': paint.find(p => p.name === 'first-paint')?.startTime.toFixed(2) + 'ms' || 'N/A',
            'First Contentful Paint': paint.find(p => p.name === 'first-contentful-paint')?.startTime.toFixed(2) + 'ms' || 'N/A',
        };
    }

    /**
     * Mostrar reporte en consola
     */
    report() {
        const summary = this.getSummary();

        console.group('%c📊 PERFORMANCE REPORT', 'color: #3b82f6; font-size: 14px; font-weight: bold;');
        console.log('Session ID:', summary.sessionId);
        console.log('Total Time:', summary.totalTime);
        console.log('Marks:', summary.marks);
        console.log('API Calls:', summary.apiCalls);
        console.log('Navigation Timing:', summary.navigationTiming);
        console.groupEnd();

        return summary;
    }

    /**
     * Enviar reporte al servidor
     */
    async reportToServer() {
        const summary = this.getSummary();

        try {
            const response = await fetch('/api/performance/log', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify(summary),
            });

            if (response.ok) {
                console.log('%c✅ Performance report sent to server', 'color: #10b981;');
            }
        } catch (error) {
            console.warn('[PERF] Could not send report to server:', error);
        }
    }
}

/**
 * Singleton global
 */
let perfMonitor = null;

export function getPerformanceMonitor() {
    if (!perfMonitor) {
        perfMonitor = new PerformanceMonitor();
    }
    return perfMonitor;
}

/**
 * Inicializar monitor
 */
export function initPerformanceMonitor() {
    const monitor = getPerformanceMonitor();

    // Exponer globalmente
    globalThis.perfMonitor = monitor;

    // Reportar cuando todo esté listo
    window.addEventListener('load', () => {
        setTimeout(() => {
            monitor.report();
            // monitor.reportToServer().catch(() => {}); // Deshabilitado - endpoint no existe aún
        }, 1000);
    });

    return monitor;
}
