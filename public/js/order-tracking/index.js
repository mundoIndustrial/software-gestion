/**
 * index.js - Order Tracking v2
 * Archivo de inicialización que carga todos los módulos en orden correcto
 */



//  Cargar módulos en orden de dependencias
const modulesLoaded = {
    dateUtils: typeof DateUtils !== 'undefined',
    holidayManager: typeof HolidayManager !== 'undefined',
    areaMapper: typeof AreaMapper !== 'undefined',
    trackingService: typeof TrackingService !== 'undefined',
    trackingUI: typeof TrackingUI !== 'undefined',
    apiClient: typeof ApiClient !== 'undefined',
    processManager: typeof ProcessManager !== 'undefined',
    tableManager: typeof TableManager !== 'undefined',
    dropdownManager: typeof DropdownManager !== 'undefined'
};



// Verificar que todos los módulos estén disponibles
const allLoaded = Object.values(modulesLoaded).every(loaded => loaded);
if (allLoaded) {

} else {

}
