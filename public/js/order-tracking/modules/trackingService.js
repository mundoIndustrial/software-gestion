/**
 * Módulo: TrackingService
 * Responsabilidad: Lógica de cálculo de recorrido del pedido
 * Principio SOLID: Single Responsibility
 */

const TrackingService = (() => {
    /**
     * Obtiene el recorrido del pedido por las áreas
     * Calcula los días en cada área hasta la siguiente
     */
    async function getOrderTrackingPath(order) {
        const path = [];
        
        const festivos = await HolidayManager.obtenerFestivos();
        const areaOrder = AreaMapper.getAreaOrder();
        
        // Obtener todas las áreas con fechas
        const areasWithDates = [];
        for (const area of areaOrder) {
            const mapping = AreaMapper.getAreaMapping(area);
            if (!mapping) continue;
            
            const dateValue = order[mapping.dateField];
            if (dateValue) {
                const dateObj = DateUtils.parseLocalDate(dateValue);
                
                areasWithDates.push({
                    area: area,
                    mapping: mapping,
                    dateValue: dateValue,
                    date: dateObj
                });
            }
        }
        
        // Ordenar cronológicamente por fecha
        areasWithDates.sort((a, b) => a.date.getTime() - b.date.getTime());
        
        // Calcular días en cada área
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        
        let totalDiasModal = 0;
        const despachosIndex = areasWithDates.findIndex(a => a.area === 'Despachos');
        
        for (let i = 0; i < areasWithDates.length; i++) {
            const current = areasWithDates[i];
            const next = areasWithDates[i + 1];
            
            let daysInArea = 0;
            
            if (next) {
                daysInArea = DateUtils.calculateBusinessDays(current.date, next.date, festivos);
            } else {
                if (current.area === 'Despachos') {
                    daysInArea = 0;
                } else if (despachosIndex !== -1 && i < despachosIndex) {
                    const despachosDate = areasWithDates[despachosIndex].date;
                    daysInArea = DateUtils.calculateBusinessDays(current.date, despachosDate, festivos);
                } else {
                    daysInArea = DateUtils.calculateBusinessDays(current.date, today, festivos);
                }
            }
            
            totalDiasModal += daysInArea;
            const chargeValue = current.mapping.chargeField ? order[current.mapping.chargeField] : null;
            
            path.push({
                area: current.area,
                displayName: current.mapping.displayName,
                icon: current.mapping.icon,
                date: current.dateValue,
                charge: chargeValue,
                daysInArea: daysInArea,
                isCompleted: true
            });
        }
        
        path.totalDiasCalculado = totalDiasModal > 0 ? totalDiasModal - 1 : 0;
        
        return path;
    }
    
    // Interfaz pública
    return {
        getOrderTrackingPath
    };
})();

globalThis.TrackingService = TrackingService;

