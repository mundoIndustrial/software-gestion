/**
 * Core Bootstrap - Configuración e Inyección de Dependencias
 * 
 * Responsabilidades:
 * - Instanciar todas las capas en orden
 * - Configurar dependencias
 * - Exportar servicios para la aplicación
 * - Punto único de inicialización de la arquitectura DDD
 * 
 * Orden de instanciación (de abajo hacia arriba):
 * 1. HttpClient (sin dependencias)
 * 2. Repository (depende de HttpClient)
 * 3. Service (depende de Repository)
 */

// Importar capas en orden de dependencia
// NOTA: En el navegador, asumimos que los scripts se cargan en orden:
// 1. core/infrastructure/HttpClient.js
// 2. core/infrastructure/SessionStorageInsumoRepository.js
// 3. core/domain/InsumoRepository.js   <- Base class
// 4. core/application/InsumoService.js
// 5. core/bootstrap.js (este archivo)

class CoreBootstrap {
    constructor(options = {}) {
        this.options = {
            httpTimeout: options.httpTimeout || 10000,
            cacheExpiry: options.cacheExpiry || 30 * 60 * 1000, // 30 minutos
            retryAttempts: options.retryAttempts || 3,
            ...options
        };

        this.httpClient = null;
        this.insumoRepository = null;
        this.insumoService = null;
    }

    /**
     * Inicializa todas las capas y retorna el contenedor de servicios
     */
    boot() {
        console.log('[CoreBootstrap] Iniciando arquitectura DDD...');

        try {
            // 1. Capa de infraestructura - HttpClient
            this.httpClient = new HttpClient({
                timeout: this.options.httpTimeout,
                retryAttempts: this.options.retryAttempts
            });
            console.log('[CoreBootstrap] ✓ HttpClient inicializado');

            // 2. Capa de infraestructura - Repository (inyectar HttpClient)
            this.insumoRepository = new SessionStorageInsumoRepository(this.httpClient);
            this.insumoRepository.cacheExpiry = this.options.cacheExpiry;
            console.log('[CoreBootstrap] ✓ SessionStorageInsumoRepository inicializado');

            // 3. Capa de aplicación - Service (inyectar Repository)
            this.insumoService = new InsumoService(this.insumoRepository);
            console.log('[CoreBootstrap] ✓ InsumoService inicializado');

            console.log('[CoreBootstrap] ✓ Arquitectura DDD lista');
            return this.getServices();

        } catch (error) {
            console.error('[CoreBootstrap] Error inicializando:', error);
            throw error;
        }
    }

    /**
     * Retorna el contenedor de servicios
     * Application/Presentación usa estos servicios
     */
    getServices() {
        return {
            insumoService: this.insumoService,
            insumoRepository: this.insumoRepository,
            httpClient: this.httpClient
        };
    }

    /**
     * Retorna solo el servicio de insumos
     * Para uso simple: const { insumoService } = bootstrap.boot();
     */
    getInsumoService() {
        if (!this.insumoService) {
            throw new Error('CoreBootstrap no ha sido inicializado. Ejecute boot() primero');
        }
        return this.insumoService;
    }

    /**
     * Reestablece las dependencias (útil para testing)
     */
    reset() {
        this.httpClient = null;
        this.insumoRepository = null;
        this.insumoService = null;
        console.log('[CoreBootstrap] Dependencias reset');
    }

    /**
     * Obtiene el estado actual de la configuración
     */
    getConfig() {
        return {
            httpTimeout: this.options.httpTimeout,
            cacheExpiry: this.options.cacheExpiry,
            retryAttempts: this.options.retryAttempts
        };
    }
}

/**
 * Instancia global singleton (opcional)
 * Para uso simple en el navegador
 */
const coreBootstrap = new CoreBootstrap({
    httpTimeout: 10000,
    cacheExpiry: 30 * 60 * 1000, // 30 minutos
    retryAttempts: 3
});

/**
 * Inicialización automática al cargar
 * IMPORTANTE: Asegúrese que todos los scripts se cargan antes
 */
document.addEventListener('DOMContentLoaded', () => {
    try {
        const services = coreBootstrap.boot();
        // Hacer servicios disponibles globalmente (solo si es necesario)
        window.insumoService = services.insumoService;
        window.coreServices = services;
    } catch (error) {
        console.error('[CoreBootstrap] No se pudo inicializar servicios:', error);
    }
});

/**
 * Exportar para testing o uso en módulos
 */
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { CoreBootstrap, coreBootstrap };
} else {
    window.CoreBootstrap = CoreBootstrap;
    window.coreBootstrap = coreBootstrap;
}
