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


        const selectors = document.querySelectorAll('.dia-entrega-dropdown');

        
        // Adjuntar listeners directamente a cada selector
        selectors.forEach(select => {

            select.addEventListener('change', (e) => {

                this.handleDiaEntregaChange(e.target);
            });
            
            // Aplicar highlight a los dropdowns con valor seleccionado
            if (select.value && select.value !== '') {
                select.classList.add('orange-highlight');
            }
        });
        
        // También usar delegación de eventos para selectores dinámicos
        this._attachEventListeners();
    },

    /**
     * Adjuntar listeners a todos los dropdowns de día de entrega (delegación)
     */
    _attachEventListeners() {
        document.addEventListener('change', (e) => {
            if (e.target.classList.contains('dia-entrega-dropdown')) {

                this.handleDiaEntregaChange(e.target);
            }
        });
    },

    /**
     * Manejar cambio en el dropdown de día de entrega
     */
    handleDiaEntregaChange(select) {


        
        const numeroOrden = select.dataset.ordenId || select.dataset.id || select.dataset.numeroOrden;
        const value = select.value;
        

        
        // Add or remove orange highlight based on selection
        if (value && value !== '') {
            select.classList.add('orange-highlight');
        } else {
            select.classList.remove('orange-highlight');
        }
        
        if (!numeroOrden) {

            return;
        }
        
        // Actualizar fecha estimada inmediatamente en tiempo real
        this._updateEstimatedDateInRealTime(select, value);
        
        // Si el valor está vacío (deseleccionado), enviar null para borrar
        if (!value || value === '') {

            this._updateWithDebounce(numeroOrden, null);
            return;
        }
        

        
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

    },

    /**
     * Mostrar warning visual
     */
    _showWarning(select, message) {
        select.classList.add('warning-state');

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

            return;
        }

        // Obtener el dropdown y el valor anterior
        let select = document.querySelector(`.dia-entrega-dropdown[data-orden-id="${numeroOrden}"]`);
        if (!select) {
            select = document.querySelector(`.dia-entrega-dropdown[data-id="${numeroOrden}"]`);
        }
        if (!select) {

            return;
        }

        const oldDias = select.dataset.value || select.value;

        UpdatesModule.updateOrderDiaEntrega(numeroOrden, days, oldDias, select);
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
    },

    /**
     * Actualizar fecha estimada en tiempo real
     */
    _updateEstimatedDateInRealTime(select, daysValue) {
        // Encontrar la fila que contiene el dropdown
        let row = select.closest('tr');
        if (!row) {
            row = select.closest('.table-row');
        }
        
        if (!row) {
            console.warn('[DiaEntregaModule] No se encontró la fila para actualizar fecha estimada');
            return;
        }
        
        // Buscar la celda de fecha estimada
        let fechaCell = row.querySelector('.fecha-estimada-cell');
        if (!fechaCell) {
            fechaCell = row.querySelector('td[data-column="fecha_estimada_de_entrega"]');
        }
        
        if (!fechaCell) {
            console.warn('[DiaEntregaModule] No se encontró la celda de fecha estimada');
            return;
        }
        
        // Obtener el span donde se muestra la fecha
        let spanFecha = fechaCell.querySelector('.fecha-estimada-span');
        if (!spanFecha) {
            spanFecha = fechaCell.querySelector('.cell-text');
        }
        
        if (!spanFecha) {
            console.warn('[DiaEntregaModule] No se encontró el span para mostrar fecha estimada');
            return;
        }
        
        // Si no hay días seleccionados, limpiar la fecha
        if (!daysValue || daysValue === '') {
            spanFecha.textContent = '-';
            fechaCell.setAttribute('data-fecha-estimada', '-');
            return;
        }
        
        // Calcular fecha estimada usando días hábiles (como el servidor)
        const days = parseInt(daysValue);
        const today = new Date();
        today.setHours(0, 0, 0, 0); // Normalizar a inicio del día
        
        const estimatedDate = this._calculateBusinessDays(today, days);
        
        // Formatear fecha (usar el mismo formato que el sistema)
        const formattedDate = this._formatDate(estimatedDate);
        
        // Actualizar la UI inmediatamente
        spanFecha.textContent = formattedDate;
        fechaCell.setAttribute('data-fecha-estimada', formattedDate);
        
        // Agregar indicador visual de que se actualizó en tiempo real
        spanFecha.style.transition = 'background-color 0.3s';
        spanFecha.style.backgroundColor = '#fef3c7'; // Amarillo suave
        setTimeout(() => {
            spanFecha.style.backgroundColor = '';
        }, 1000);
        
        console.log(`[DiaEntregaModule] Fecha estimada actualizada en tiempo real: ${days} días hábiles -> ${formattedDate}`);
    },

    /**
     * Calcular fecha sumando días hábiles (excluyendo fines de semana)
     * Simula la lógica del servidor PHP
     */
    _calculateBusinessDays(startDate, businessDays) {
        const date = new Date(startDate);
        let daysAdded = 0;
        
        while (daysAdded < businessDays) {
            date.setDate(date.getDate() + 1);
            
            // Saltar fines de semana (sábado=6, domingo=0)
            if (date.getDay() === 0 || date.getDay() === 6) {
                continue;
            }
            
            // NOTA: El servidor también salta festivos, pero en JavaScript no tenemos acceso a esa lista
            // Esta aproximación es suficientemente buena para el tiempo real
            
            daysAdded++;
        }
        
        return date;
    },

    /**
     * Formatear fecha al formato dd/mm/yyyy
     */
    _formatDate(date) {
        const day = String(date.getDate()).padStart(2, '0');
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const year = date.getFullYear();
        return `${day}/${month}/${year}`;
    }
};

// Exponer módulo globalmente
window.DiaEntregaModule = DiaEntregaModule;
globalThis.DiaEntregaModule = DiaEntregaModule;
