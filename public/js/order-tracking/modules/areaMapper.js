/**
 * Módulo: AreaMapper
 * Responsabilidad: Mapear áreas con sus propiedades y iconos
 * Principio SOLID: Single Responsibility + Open/Closed
 */

const AreaMapper = (() => {
    const areaFieldMappings = {
        'Creación Orden': {
            dateField: 'fecha_de_creacion_de_orden',
            chargeField: 'encargado_orden',
            daysField: 'dias_orden',
            icon: 'description',
            displayName: 'Pedido Recibido'
        },
        'Insumos': {
            dateField: 'insumos_y_telas',
            chargeField: 'encargados_insumos',
            daysField: 'dias_insumos',
            icon: 'inventory_2',
            displayName: 'Insumos y Telas'
        },
        'Corte': {
            dateField: 'corte',
            chargeField: 'encargados_de_corte',
            daysField: 'dias_corte',
            icon: 'content_cut',
            displayName: 'Corte'
        },
        'Bordado': {
            dateField: 'bordado',
            chargeField: null,
            daysField: 'dias_bordado',
            icon: 'brush',
            displayName: 'Bordado'
        },
        'Estampado': {
            dateField: 'estampado',
            chargeField: 'encargados_estampado',
            daysField: 'dias_estampado',
            icon: 'print',
            displayName: 'Estampado'
        },
        'Costura': {
            dateField: 'costura',
            chargeField: 'modulo',
            daysField: 'dias_costura',
            icon: 'dry_cleaning',
            displayName: 'Costura'
        },
        'Polos': {
            dateField: 'costura',
            chargeField: 'modulo',
            daysField: 'dias_costura',
            icon: 'checkroom',
            displayName: 'Polos'
        },
        'Taller': {
            dateField: 'costura',
            chargeField: 'modulo',
            daysField: 'dias_costura',
            icon: 'construction',
            displayName: 'Taller'
        },
        'Lavandería': {
            dateField: 'lavanderia',
            chargeField: 'encargado_lavanderia',
            daysField: 'dias_lavanderia',
            icon: 'local_laundry_service',
            displayName: 'Lavandería'
        },
        'Arreglos': {
            dateField: 'arreglos',
            chargeField: 'encargado_arreglos',
            daysField: 'total_de_dias_arreglos',
            icon: 'handyman',
            displayName: 'Arreglos'
        },
        'Control-Calidad': {
            dateField: 'control_de_calidad',
            chargeField: 'encargados_calidad',
            daysField: 'dias_c_c',
            icon: 'verified',
            displayName: 'Control de Calidad'
        },
        'Entrega': {
            dateField: 'entrega',
            chargeField: 'encargados_entrega',
            daysField: null,
            icon: 'local_shipping',
            displayName: 'Entrega'
        },
        'Despachos': {
            dateField: 'despacho',
            chargeField: null,
            daysField: null,
            icon: 'directions_car',
            displayName: 'Despachos'
        }
    };
    
    const processoIconMap = {
        'Pedido Recibido': 'description',
        'Creación Orden': 'description',
        'Insumos': 'inventory_2',
        'Insumos y Telas': 'inventory_2',
        'Corte': 'content_cut',
        'Bordado': 'brush',
        'Estampado': 'print',
        'Costura': 'dry_cleaning',
        'Polos': 'checkroom',
        'Taller': 'construction',
        'Lavandería': 'local_laundry_service',
        'Lavanderia': 'local_laundry_service',
        'Arreglos': 'handyman',
        'Control de Calidad': 'verified',
        'Control-Calidad': 'verified',
        'Entrega': 'local_shipping',
        'Despacho': 'directions_car',
        'Despachos': 'directions_car',
        'Reflectivo': 'highlight',
        'Marras': 'search'
    };
    
    /**
     * Obtiene el mapeo de un área específica
     */
    function getAreaMapping(area) {
        return areaFieldMappings[area];
    }
    
    /**
     * Obtiene el icono de un proceso (nombre del icono de Material Symbols)
     */
    function getProcessIcon(proceso) {
        return processoIconMap[proceso] || 'settings';
    }
    
    /**
     * Obtiene el orden de áreas según flujo típico
     */
    function getAreaOrder() {
        return [
            'Creación Orden', 'Insumos', 'Corte', 'Bordado', 'Estampado',
            'Costura', 'Polos', 'Taller', 'Lavandería', 'Arreglos',
            'Control-Calidad', 'Entrega', 'Despachos'
        ];
    }
    
    // Interfaz pública
    return {
        getAreaMapping,
        getProcessIcon,
        getAreaOrder
    };
})();

globalThis.AreaMapper = AreaMapper;

