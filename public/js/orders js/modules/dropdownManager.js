/**
 * MÓDULO: dropdownManager.js
 * Responsabilidad: Gestionar inicialización y cambios de dropdowns
 * Principios SOLID: SRP (Single Responsibility), DIP (Dependency Inversion)
 */



const OrdersDropdownManager = {
    debounceMap: new Map(),
    debounceDelay: 300,

    /**
     * Inicializar todos los dropdowns de estado
     */
    initializeStatusDropdowns() {
        document.querySelectorAll('.estado-dropdown').forEach(dropdown => {
            dropdown.setAttribute('data-value', dropdown.value);
            dropdown.removeEventListener('change', this.handleStatusChange.bind(this));
            dropdown.addEventListener('change', this.handleStatusChange.bind(this));
        });

    },

    /**
     * Inicializar todos los dropdowns de área
     */
    initializeAreaDropdowns() {
        document.querySelectorAll('.area-dropdown').forEach(dropdown => {
            dropdown.setAttribute('data-value', dropdown.value);
            dropdown.removeEventListener('change', this.handleAreaChange.bind(this));
            dropdown.addEventListener('change', this.handleAreaChange.bind(this));
        });

    },

    /**
     * Manejador de cambio de estado
     */
    handleStatusChange(e) {
        const newStatus = e.target.value;
        e.target.setAttribute('data-value', newStatus);
        const orderId = e.target.dataset.ordenId || e.target.dataset.id;
        
        // 🆕 Actualizar clase de color del dropdown inmediatamente
        this._updateDropdownColorClass(e.target, newStatus);
        
        this.updateWithDebounce('status', orderId, newStatus, e.target.dataset.value, e.target);
    },

    /**
     * Manejador de cambio de área
     */
    handleAreaChange(e) {

        
        const dropdown = e.target;
        const orderId = dropdown.dataset.ordenId || dropdown.dataset.id;
        const oldValue = dropdown.dataset.value;
        const newValue = dropdown.value;
        
        // 🆕 Detectar si el cambio fue programático (iniciado por UpdatesModule)
        if (dropdown.dataset.programmaticChange === 'true') {

            dropdown.dataset.programmaticChange = 'false';
            return;
        }
        


        
        if (!orderId) {

            return;
        }
        

        
        dropdown.setAttribute('data-value', newValue);
        

        this.updateWithDebounce('area', orderId, newValue, oldValue, dropdown);

    },

    /**
     * Aplicar debounce a actualizaciones
     */
    updateWithDebounce(type, orderId, newValue, oldValue, element) {
        const debounceKey = `${type}-${orderId}`;
        

        
        if (this.debounceMap.has(debounceKey)) {
            clearTimeout(this.debounceMap.get(debounceKey));

        }
        
        const timeoutId = setTimeout(() => {
            this.debounceMap.delete(debounceKey);
            

            
            if (type === 'status') {
                if (typeof UpdatesModule !== 'undefined' && UpdatesModule.updateOrderStatus) {
                    UpdatesModule.updateOrderStatus(orderId, newValue, oldValue, element);
                } else {

                }
            } else if (type === 'area') {
                if (typeof UpdatesModule !== 'undefined' && UpdatesModule.updateOrderArea) {

                    UpdatesModule.updateOrderArea(orderId, newValue, oldValue, element);
                } else {

                }
            }
        }, this.debounceDelay);
        
        this.debounceMap.set(debounceKey, timeoutId);

    },

    /**
     * 🆕 Actualizar clases de color del dropdown según el estado
     */
    _updateDropdownColorClass(dropdown, newStatus) {
        if (!dropdown || !dropdown.classList.contains('estado-dropdown')) return;
        
        // Remover todas las clases de estado
        dropdown.classList.remove(
            'estado-entregado',
            'estado-en-ejecución',
            'estado-no-iniciado',
            'estado-anulada'
        );
        
        // Agregar la clase correspondiente al nuevo estado
        const statusClass = `estado-${newStatus.toLowerCase().replace(/ /g, '-')}`;
        dropdown.classList.add(statusClass);
        

    }
};

// Exponer módulo globalmente
window.OrdersDropdownManager = OrdersDropdownManager;
globalThis.OrdersDropdownManager = OrdersDropdownManager;



