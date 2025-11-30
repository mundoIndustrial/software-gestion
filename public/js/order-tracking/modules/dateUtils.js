/**
 * Módulo: DateUtils
 * Responsabilidad: Manipulación y formateo de fechas
 * Principio SOLID: Single Responsibility
 */

const DateUtils = (() => {
    /**
     * Parsea una fecha string (YYYY-MM-DD) a Date sin problemas de zona horaria
     */
    function parseLocalDate(dateString) {
        if (!dateString) return null;
        
        let parts;
        
        // Formato YYYY-MM-DD (ISO)
        if (dateString.includes('-') && dateString.split('-')[0].length === 4) {
            parts = dateString.split('T')[0].split('-');
            const date = new Date(Number.parseInt(parts[0]), Number.parseInt(parts[1]) - 1, Number.parseInt(parts[2]));
            date.setHours(0, 0, 0, 0);
            return date;
        }
        
        // Formato DD/MM/YYYY
        if (dateString.includes('/')) {
            parts = dateString.split('/');
            if (parts.length === 3) {
                const date = new Date(Number.parseInt(parts[2]), Number.parseInt(parts[1]) - 1, Number.parseInt(parts[0]));
                date.setHours(0, 0, 0, 0);
                return date;
            }
        }
        
        // Fallback: intentar parseo automático
        const date = new Date(dateString);
        date.setHours(0, 0, 0, 0);
        return date;
    }
    
    /**
     * Formatea una fecha al formato dd/mm/yyyy
     */
    function formatDate(dateString) {
        if (!dateString) return '-';
        
        try {
            const date = typeof dateString === 'string' ? parseLocalDate(dateString) : dateString;
            const day = String(date.getDate()).padStart(2, '0');
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const year = date.getFullYear();
            return `${day}/${month}/${year}`;
        } catch (e) {
            return dateString;
        }
    }
    
    /**
     * Calcula días hábiles entre dos fechas (excluyendo fines de semana y festivos)
     * El contador inicia DESPUÉS de la fecha de inicio
     */
    function calculateBusinessDays(startDate, endDate, festivos = []) {
        if (!startDate || !endDate) return 0;

        const start = typeof startDate === 'string' ? parseLocalDate(startDate) : new Date(startDate);
        const end = typeof endDate === 'string' ? parseLocalDate(endDate) : new Date(endDate);

        start.setHours(0, 0, 0, 0);
        end.setHours(0, 0, 0, 0);

        if (start.getTime() === end.getTime()) {
            return 0;
        }

        const festivosSet = new Set(festivos.map(f => {
            if (typeof f === 'string') {
                return f.split('T')[0];
            }
            return f;
        }));

        let days = 0;
        const current = new Date(start);
        
        // Saltar al próximo día (contador inicia DESPUÉS de la fecha de creación)
        current.setDate(current.getDate() + 1);

        while (current <= end) {
            const dayOfWeek = current.getDay();
            const dateString = current.toISOString().split('T')[0];
            const isFestivo = festivosSet.has(dateString);
            const isWeekend = dayOfWeek === 0 || dayOfWeek === 6;
            
            if (!isWeekend && !isFestivo) {
                days++;
            }
            
            current.setDate(current.getDate() + 1);
        }

        return Math.max(0, days);
    }
    
    // Interfaz pública
    return {
        parseLocalDate,
        formatDate,
        calculateBusinessDays
    };
})();

globalThis.DateUtils = DateUtils;

