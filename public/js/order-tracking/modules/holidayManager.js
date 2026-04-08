/**
 * Módulo: HolidayManager
 * Responsabilidad: Gestionar festivos y obtenerlos de API
 * Principio SOLID: Single Responsibility
 */

const HolidayManager = (() => {
    // Festivos de Colombia 2025 (fallback si API falla)
    const FESTIVOS_COLOMBIA_2025 = [
        '2025-01-01', '2025-01-06', '2025-03-24', '2025-04-17', '2025-04-18',
        '2025-05-01', '2025-06-02', '2025-06-23', '2025-06-30', '2025-07-07',
        '2025-07-20', '2025-08-07', '2025-08-18', '2025-10-13', '2025-11-03',
        '2025-11-17', '2025-12-08', '2025-12-25'
    ];
    
    const DEFAULT_TTL = 12 * 60 * 60 * 1000; // 12 horas
    const CACHE_KEY = 'festivos_año';
    let cacheRepository = null;
    
    /**
     * Obtiene instancia de SessionStorageCacheRepository
     * Fallback: null si no está disponible
     */
    function _getCacheRepository() {
        if (!cacheRepository && window.SessionStorageCacheRepository) {
            cacheRepository = new window.SessionStorageCacheRepository({
                keyPrefix: 'holiday_'
            });
        }
        return cacheRepository;
    }
    
    /**
     * Obtiene festivos desde API externa (Nager.Date)
     * @param {number} year - Año a consultar
     * @returns {Promise<Array>} Array de fechas YYYY-MM-DD
     */
    async function _fetchFromAPI(year) {
        const response = await fetch(`https://date.nager.at/api/v3/PublicHolidays/${year}/CO`);
        if (response.ok) {
            const data = await response.json();
            return data.map(h => h.date);
        }
        throw new Error('API no disponible');
    }
    
    /**
     * Obtiene los festivos de Colombia desde cache o API
     * Con fallback a festivos locales si todo falla
     * @returns {Promise<Array>} Array de fechas YYYY-MM-DD
     */
    async function obtenerFestivos() {
        const cache = _getCacheRepository();
        const year = new Date().getFullYear();
        const cacheKey = `${CACHE_KEY}_${year}`;
        
        if (cache) {
            return await cache.getOrFetch(
                cacheKey,
                () => _fetchFromAPI(year).catch(() => FESTIVOS_COLOMBIA_2025),
                DEFAULT_TTL
            );
        }
        
        // Fallback si SessionStorageCacheRepository no disponible
        try {
            return await _fetchFromAPI(year);
        } catch (error) {
            console.warn('[HolidayManager] API falla, usando festivos locales');
            return FESTIVOS_COLOMBIA_2025;
        }
    }
    
    /**
     * Limpia el cache de festivos del año actual
     */
    function clearCache() {
        const cache = _getCacheRepository();
        const year = new Date().getFullYear();
        const cacheKey = `${CACHE_KEY}_${year}`;
        if (cache) {
            cache.remove(cacheKey);
        }
    }
    
    // Interfaz pública
    return {
        obtenerFestivos,
        clearCache
    };
})();

globalThis.HolidayManager = HolidayManager;

