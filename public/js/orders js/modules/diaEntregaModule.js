/**
 * MÓDULO: diaEntregaModule.js
 * Responsabilidad: Gestionar el campo "día de entrega" con lógica especializada
 * Principios SOLID: SRP (Single Responsibility), OCP (Open/Closed para nuevas reglas)
 */

const DiaEntregaModule = {
    config: {
        minDays: 1,
        maxDays: 30,
        warningThreshold: 7 // días para mostrar warning
    },

    /**
     * Inicializar dropdowns de día de entrega
     */
    initialize() {
        console.log('📅 Inicializando módulo de día de entrega');
        this._attachEventListeners();
    },

    /**
     * Adjuntar listeners a todos los dropdowns de día de entrega
     */
    _attachEventListeners() {
        document.addEventListener('change', (e) => {
            if (e.target.classList.contains('dia-entrega-select')) {
                this.handleDiaEntregaChange(e.target);
            }
        });
    },

    /**
     * Manejar cambio en el dropdown de día de entrega
     */
    handleDiaEntregaChange(select) {
        const numeroOrden = select.dataset.numeroOrden;
        const value = select.value;
        
        if (!numeroOrden || !value) return;
        
        console.log(`📅 Cambio detectado en orden ${numeroOrden}: ${value} días`);
        
        // Validar valor
        if (!this._isValidDays(value)) {
            this._showValidationError(select, 'Días inválidos');
            return;
        }
        
        // Mostrar warning si es necesario
        const days = parseInt(value);
        if (days >= this.config.warningThreshold) {
            this._showWarning(select, `Entrega en ${days} días`);
        }
        
        // Enviar update (debounced)
        this._updateWithDebounce(numeroOrden, days);
    },

    /**
     * Validar que los días sean válidos
     */
    _isValidDays(value) {
        const days = parseInt(value);
        return !isNaN(days) && 
               days >= this.config.minDays && 
               days <= this.config.maxDays;
    },

    /**
     * Mostrar error de validación
     */
    _showValidationError(select, message) {
        select.classList.add('error-state');
        setTimeout(() => select.classList.remove('error-state'), 2000);
        console.warn(`⚠️ ${message}`);
    },

    /**
     * Mostrar warning visual
     */
    _showWarning(select, message) {
        select.classList.add('warning-state');
        console.log(`⚠️ ${message}`);
        setTimeout(() => select.classList.remove('warning-state'), 3000);
    },

    /**
     * Debounce actualización (300ms)
     */
    _updateWithDebounce(numeroOrden, days) {
        clearTimeout(this._debounceTimer);
        this._debounceTimer = setTimeout(() => {
            this._sendUpdate(numeroOrden, days);
        }, 300);
    },

    /**
     * Enviar actualización al servidor
     */
    _sendUpdate(numeroOrden, days) {
        if (!UpdatesModule) {
            console.error('❌ UpdatesModule no disponible');
            return;
        }

        UpdatesModule.updateOrderDiaEntrega(numeroOrden, days);
    },

    /**
     * Recalcular fecha de entrega basada en días
     */
    calculateDeliveryDate(currentDate, days) {
        if (!currentDate || !this._isValidDays(days)) {
            return null;
        }

        const date = new Date(currentDate);
        date.setDate(date.getDate() + parseInt(days));
        return date.toISOString().split('T')[0];
    },

    /**
     * Obtener rango de días disponibles
     */
    getAvailableDays() {
        const days = [];
        for (let i = this.config.minDays; i <= this.config.maxDays; i++) {
            days.push(i);
        }
        return days;
    },

    /**
     * Mostrar sugerencia de días según estado
     */
    getSuggestedDays(estado) {
        const suggestions = {
            'Cortando': 3,
            'Confeccionando': 5,
            'Armando': 2,
            'Control Calidad': 1,
            'Insumos y Telas': 7,
            'Empacando': 1
        };
        
        return suggestions[estado] || 5;
    },

    /**
     * Obtener color de indicador según días
     */
    getIndicatorColor(days) {
        if (days <= 2) return '#ef4444'; // Rojo - muy urgente
        if (days <= 5) return '#f97316'; // Naranja - urgente
        if (days <= 10) return '#eab308'; // Amarillo - moderado
        return '#22c55e'; // Verde - normal
    }
};

// Exponer módulo globalmente
window.DiaEntregaModule = DiaEntregaModule;
globalThis.DiaEntregaModule = DiaEntregaModule;
