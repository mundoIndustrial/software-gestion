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
            icon: '📋',
            displayName: 'Pedido Recibido'
        },
        'Insumos': {
            dateField: 'insumos_y_telas',
            chargeField: 'encargados_insumos',
            daysField: 'dias_insumos',
            icon: '🧵',
            displayName: 'Insumos y Telas'
        },
        'Corte': {
            dateField: 'corte',
            chargeField: 'encargados_de_corte',
            daysField: 'dias_corte',
            icon: '✂️',
            displayName: 'Corte'
        },
        'Bordado': {
            dateField: 'bordado',
            chargeField: null,
            daysField: 'dias_bordado',
            icon: '🎨',
            displayName: 'Bordado'
        },
        'Estampado': {
            dateField: 'estampado',
            chargeField: 'encargados_estampado',
            daysField: 'dias_estampado',
            icon: '🖨️',
            displayName: 'Estampado'
        },
        'Costura': {
            dateField: 'costura',
            chargeField: 'modulo',
            daysField: 'dias_costura',
            icon: '👗',
            displayName: 'Costura'
        },
        'Polos': {
            dateField: 'costura',
            chargeField: 'modulo',
            daysField: 'dias_costura',
            icon: '👕',
            displayName: 'Polos'
        },
        'Taller': {
            dateField: 'costura',
            chargeField: 'modulo',
            daysField: 'dias_costura',
            icon: '🔧',
            displayName: 'Taller'
        },
        'Lavandería': {
            dateField: 'lavanderia',
            chargeField: 'encargado_lavanderia',
            daysField: 'dias_lavanderia',
            icon: '🧺',
            displayName: 'Lavandería'
        },
        'Arreglos': {
            dateField: 'arreglos',
            chargeField: 'encargado_arreglos',
            daysField: 'total_de_dias_arreglos',
            icon: '🪡',
            displayName: 'Arreglos'
        },
        'Control-Calidad': {
            dateField: 'control_de_calidad',
            chargeField: 'encargados_calidad',
            daysField: 'dias_c_c',
            icon: '✅',
            displayName: 'Control de Calidad'
        },
        'Entrega': {
            dateField: 'entrega',
            chargeField: 'encargados_entrega',
            daysField: null,
            icon: '📦',
            displayName: 'Entrega'
        },
        'Despachos': {
            dateField: 'despacho',
            chargeField: null,
            daysField: null,
            icon: '🚚',
            displayName: 'Despachos'
        }
    };
    
    const processoIconMap = {
        'Pedido Recibido': '📋',
        'Creación Orden': '📋',
        'Insumos': '🧵',
        'Insumos y Telas': '🧵',
        'Corte': '✂️',
        'Bordado': '🎨',
        'Estampado': '🖨️',
        'Costura': '👗',
        'Polos': '👕',
        'Taller': '🔧',
        'Lavandería': '🧺',
        'Lavanderia': '🧺',
        'Arreglos': '🪡',
        'Control de Calidad': '✅',
        'Control-Calidad': '✅',
        'Entrega': '📦',
        'Despacho': '🚚',
        'Despachos': '🚚',
        'Reflectivo': '✨',
        'Marras': '🔍'
    };
    
    /**
     * Obtiene el mapeo de un área específica
     */
    function getAreaMapping(area) {
        return areaFieldMappings[area];
    }
    
    /**
     * Obtiene el icono de un proceso
     */
    function getProcessIcon(proceso) {
        return processoIconMap[proceso] || '⚙️';
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

