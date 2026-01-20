/**
 * ValidationService - Patrón Validator centralizado
 * Elimina duplicación de checks de validación
 * Uso: Validator.check(condición, mensajeError, callback)
 */
window.Validator = {
    /**
     * Check genérico: valida una condición y ejecuta callback si es válida
     */
    check: function(condition, errorMessage, callback) {
        if (!condition) {
            UI.error('Error', errorMessage);
            return false;
        }
        return callback ? callback() : true;
    },

    /**
     * Valida que exista datosEdicionPedido
     */
    requireEdicionPedido: function(callback) {
        return this.check(
            window.datosEdicionPedido,
            'No hay datos del pedido disponibles',
            callback
        );
    },

    /**
     * Valida que exista eppEdicion
     */
    requireEppEdicion: function(callback) {
        return this.check(
            window.eppEdicion,
            'No hay datos de EPP disponibles',
            callback
        );
    },

    /**
     * Valida que exista un EPP específico por índice
     */
    requireEppItem: function(index, callback) {
        if (!this.check(window.eppEdicion, 'No hay datos de EPP disponibles')) {
            return false;
        }
        
        const epp = window.eppEdicion.epp[index];
        return this.check(
            epp,
            'EPP no encontrado',
            () => callback(epp)
        );
    },

    /**
     * Valida que una función exista en window
     */
    requireFunction: function(functionName, callback) {
        return this.check(
            typeof window[functionName] === 'function',
            `Función ${functionName} no disponible`,
            callback
        );
    },

    /**
     * Valida que múltiples condiciones se cumplan
     */
    requireAll: function(conditions, callback) {
        const allValid = conditions.every(cond => cond.condition);
        
        if (!allValid) {
            const failedCondition = conditions.find(cond => !cond.condition);
            UI.error('Error', failedCondition.message);
            return false;
        }
        
        return callback ? callback() : true;
    },

    /**
     * Valida que exista al menos una condición
     */
    requireAny: function(conditions, callback) {
        const anyValid = conditions.some(cond => cond.condition);
        
        if (!anyValid) {
            const firstMessage = conditions[0]?.message || 'Validación fallida';
            UI.error('Error', firstMessage);
            return false;
        }
        
        return callback ? callback() : true;
    },

    /**
     * Validador con transformación - útil para limpiar/transformar datos
     */
    transform: function(value, transform, errorMessage = 'Validación fallida') {
        try {
            return transform(value);
        } catch (err) {
            UI.error('Error', errorMessage);
            return null;
        }
    }
};

/**
 * GuardClause - Patrón para early returns más limpio
 */
window.Guard = {
    /**
     * Ejecuta early return si la condición es falsa
     */
    against: function(condition, errorMessage) {
        if (condition) {
            UI.error('Error', errorMessage);
            throw new Error(errorMessage);
        }
    },

    /**
     * Ejecuta early return si el valor es null/undefined
     */
    againstNull: function(value, propertyName = 'Valor') {
        if (!value) {
            const message = `${propertyName} no puede ser vacío`;
            UI.error('Error', message);
            throw new Error(message);
        }
        return value;
    },

    /**
     * Ejecuta early return si no es un tipo específico
     */
    againstType: function(value, type, propertyName = 'Valor') {
        if (typeof value !== type) {
            const message = `${propertyName} debe ser de tipo ${type}`;
            UI.error('Error', message);
            throw new Error(message);
        }
        return value;
    },

    /**
     * Ejecuta early return si no cumple una condición personalizada
     */
    againstCondition: function(value, condition, errorMessage) {
        if (!condition(value)) {
            UI.error('Error', errorMessage);
            throw new Error(errorMessage);
        }
        return value;
    }
};

console.log('✅ ValidationService cargado - Usar Validator.* o Guard.*');
