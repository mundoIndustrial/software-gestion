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
    
    let festivosCache = null;
    
    /**
     * Obtiene los festivos de Colombia desde API o usa fallback
     */
    async function obtenerFestivos() {
        if (festivosCache) {
            return festivosCache;
        }
        
        try {
            const year = new Date().getFullYear();
            const response = await fetch(`https://date.nager.at/api/v3/PublicHolidays/${year}/CO`);
            if (response.ok) {
                const data = await response.json();
                festivosCache = data.map(h => h.date);

                return festivosCache;
            }
        } catch (error) {

        }
        
        festivosCache = FESTIVOS_COLOMBIA_2025;
        return festivosCache;
    }
    
    /**
     * Limpia el cache de festivos
     */
    function clearCache() {
        festivosCache = null;
    }
    
    // Interfaz pública
    return {
        obtenerFestivos,
        clearCache
    };
})();

globalThis.HolidayManager = HolidayManager;

