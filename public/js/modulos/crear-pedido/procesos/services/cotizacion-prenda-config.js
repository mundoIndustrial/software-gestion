/**
 * CotizacionPrendaConfig - Inicializador de Configuración
 * 
 * Responsabilidad: Sincronizar tipos de cotización desde la BD
 * con la configuración del CotizacionPrendaHandler
 */
class CotizacionPrendaConfig {
    /**
     * Inicializar configuración desde la API
     * Cargar todos los tipos de cotización y registrar los que requieren bodega
     * 
     * @returns {Promise<void>}
     */
    static async inicializarDesdeAPI() {
        try {
            console.info('[CotizacionPrendaConfig] Iniciando sincronización desde API...');

            const response = await fetch('/api/tipos-cotizacion');
            if (!response.ok) {
                throw new Error(`API error: ${response.status}`);
            }

            const datos = await response.json();
            const tipos = datos.data || datos.tipos || [];

            console.debug('[CotizacionPrendaConfig] Tipos obtenidos:', tipos);

            // Procesar cada tipo de cotización
            tipos.forEach(tipo => {
                if (tipo.requiere_bodega || tipo.requiereBodyga) {
                    CotizacionPrendaHandler.registrarTipoBodega(
                        tipo.id,
                        tipo.nombre
                    );
                    console.log(`✓ Tipo registrado: ${tipo.nombre} (ID: ${tipo.id})`);
                }
            });

            console.info(
                '[CotizacionPrendaConfig] Sincronización completada. ' +
                `Tipos bodega: ${CotizacionPrendaHandler.obtenerTiposBodega().length}`
            );

        } catch (error) {
            console.error('[CotizacionPrendaConfig] Error sincronizando desde API:', error);
            console.warn('[CotizacionPrendaConfig] Usando tipos por defecto');
        }
    }

    /**
     * Inicializar desde objeto de configuración local
     * Útil cuando los tipos están en el HTML o localStorage
     * 
     * @param {Array<Object>} tiposConfiguracion - Array de tipos con estructura:
     *     { id, nombre, requiere_bodega }
     * @returns {void}
     */
    static inicializarDesdeObjeto(tiposConfiguracion) {
        if (!Array.isArray(tiposConfiguracion)) {
            console.error('[CotizacionPrendaConfig] Configuración inválida');
            return;
        }

        console.info('[CotizacionPrendaConfig] Inicializando desde objeto...');

        tiposConfiguracion.forEach(tipo => {
            if (tipo.requiere_bodega) {
                CotizacionPrendaHandler.registrarTipoBodega(tipo.id, tipo.nombre);
            }
        });

        console.info(
            `[CotizacionPrendaConfig] Inicialización completada. ` +
            `Tipos: ${tiposConfiguracion.length}`
        );
    }

    /**
     * Inicializar desde localStorage
     * Útil para cache local de tipos
     * 
     * @param {string} storageKey - Clave en localStorage
     * @returns {boolean} - true si se inicializó correctamente
     */
    static inicializarDesdeStorage(storageKey = 'tipos-cotizacion-bodega') {
        try {
            // Verificar si localStorage está disponible
            if (typeof window.localStorage === 'undefined' || !window.localStorage) {
                console.warn(`[CotizacionPrendaConfig] localStorage no disponible`);
                return false;
            }

            const datos = window.localStorage.getItem(storageKey);
            if (!datos) {
                console.warn(`[CotizacionPrendaConfig] No hay datos en localStorage (${storageKey})`);
                return false;
            }

            const tipos = JSON.parse(datos);
            this.inicializarDesdeObjeto(tipos);
            return true;

        } catch (error) {
            console.error('[CotizacionPrendaConfig] Error leyendo localStorage:', error);
            return false;
        }
    }

    /**
     * Guardar tipos actuales en localStorage para persistencia
     * 
     * @param {string} storageKey - Clave en localStorage
     * @returns {boolean} - true si se guardó correctamente
     */
    static guardarEnStorage(storageKey = 'tipos-cotizacion-bodega') {
        try {
            // Verificar si localStorage está disponible
            if (typeof window.localStorage === 'undefined' || !window.localStorage) {
                console.warn(`[CotizacionPrendaConfig] localStorage no disponible para guardar`);
                return false;
            }

            const tiposActuales = CotizacionPrendaHandler.obtenerTiposBodega();
            window.localStorage.setItem(storageKey, JSON.stringify(tiposActuales));
            console.info(
                `[CotizacionPrendaConfig] Tipos guardados en localStorage. ` +
                `Clave: ${storageKey}, Cantidad: ${tiposActuales.length}`
            );
            return true;

        } catch (error) {
            console.error('[CotizacionPrendaConfig] Error guardando en localStorage:', error);
            return false;
        }
    }

    /**
     * Estrategia inteligente de inicialización
     * Intenta: localStorage → API → Valores por defecto
     * 
     * @returns {Promise<void>}
     */
    static async inicializarConRetroalimentacion() {
        console.group('🔧 CotizacionPrendaConfig - Inicialización');

        // Paso 1: Intentar localStorage
        if (this.inicializarDesdeStorage()) {
            console.log('✓ Tipos cargados desde localStorage');
            console.groupEnd();
            return;
        }

        // Paso 2: Intentar API
        try {
            await this.inicializarDesdeAPI();
            // Guardar en localStorage para próxima vez
            this.guardarEnStorage();
            console.log('✓ Tipos cargados desde API');

        } catch (error) {
            console.error('✗ Error cargando desde API, usando valores por defecto');
        }

        // Paso 3: Valores por defecto (ya están en CotizacionPrendaHandler)
        console.log('ℹ Tipos por defecto activos:', 
            CotizacionPrendaHandler.obtenerTiposBodega()
        );

        console.groupEnd();
    }

    /**
     * Verificar sincronización en tiempo real
     * Re-sincronizar si la configuración remota cambió
     * 
     * @param {number} intervalMs - Intervalo en milisegundos (por defecto 5 min)
     * @returns {number} - ID del interval para poder cancelarlo después
     */
    static iniciarSincronizacionAutomatica(intervalMs = 300000) {
        console.info(
            `[CotizacionPrendaConfig] Sincronización automática cada ${intervalMs}ms`
        );

        return setInterval(() => {
            this.inicializarDesdeAPI().catch(error => {
                console.warn('[CotizacionPrendaConfig] Error en sincronización automática:', error);
            });
        }, intervalMs);
    }

    /**
     * Detener sincronización automática
     * 
     * @param {number} intervalId - ID del interval retornado por iniciarSincronizacionAutomatica()
     */
    static detenerSincronizacionAutomatica(intervalId) {
        clearInterval(intervalId);
        console.info('[CotizacionPrendaConfig] Sincronización automática detenida');
    }

    /**
     * Debug: Mostrar estado actual de configuración
     */
    static mostrarEstado() {
        console.group('📊 Estado CotizacionPrendaConfig');
        console.log('Tipos registrados:', CotizacionPrendaHandler.obtenerTiposBodega());
        console.log('Configuración completa:', CotizacionPrendaHandler.TIPOS_COTIZACION_BODEGA);
        console.groupEnd();
    }
}

// Exportar para uso en módulos
if (typeof module !== 'undefined' && module.exports) {
    module.exports = CotizacionPrendaConfig;
}
