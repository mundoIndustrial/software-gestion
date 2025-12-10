/**
 * MÓDULO: dropdownManager.js
 * Responsabilidad: Gestionar inicialización y cambios de dropdowns
 * Principios SOLID: SRP (Single Responsibility), DIP (Dependency Inversion)
 */

console.log('📦 Cargando OrdersDropdownManager...');

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
        console.log('✅ Dropdowns de estado inicializados');
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
        console.log('✅ Dropdowns de área inicializados');
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
        console.log('🎯 handleAreaChange INICIO', e.target);
        
        const dropdown = e.target;
        const orderId = dropdown.dataset.ordenId || dropdown.dataset.id;
        const oldValue = dropdown.dataset.value;
        const newValue = dropdown.value;
        
        // 🆕 Detectar si el cambio fue programático (iniciado por UpdatesModule)
        if (dropdown.dataset.programmaticChange === 'true') {
            console.log('ℹ️ Cambio programático detectado, ignorando para evitar loop');
            dropdown.dataset.programmaticChange = 'false';
            return;
        }
        
        console.log(`📍 Área seleccionada (visualización): ${newValue}`);
        console.log(`📊 Datos: orderId=${orderId}, oldValue=${oldValue}, newValue=${newValue}`);
        
        if (!orderId) {
            console.error('❌ No se encontró orderId en el dropdown');
            return;
        }
        
        console.log(`📍 Preparando actualización - Pedido: ${orderId}, Anterior: ${oldValue}, Nueva: ${newValue}`);
        
        dropdown.setAttribute('data-value', newValue);
        
        console.log('🔄 Llamando updateWithDebounce...');
        this.updateWithDebounce('area', orderId, newValue, oldValue, dropdown);
        console.log('✅ updateWithDebounce llamado');
    },

    /**
     * Aplicar debounce a actualizaciones
     */
    updateWithDebounce(type, orderId, newValue, oldValue, element) {
        const debounceKey = `${type}-${orderId}`;
        
        console.log(`⏱️ Debounce ${type} - Key: ${debounceKey}`);
        
        if (this.debounceMap.has(debounceKey)) {
            clearTimeout(this.debounceMap.get(debounceKey));
            console.log(`⏱️ Cancelando timeout anterior para ${debounceKey}`);
        }
        
        const timeoutId = setTimeout(() => {
            this.debounceMap.delete(debounceKey);
            
            console.log(`🚀 Ejecutando actualización ${type} para pedido ${orderId}`);
            
            if (type === 'status') {
                if (typeof UpdatesModule !== 'undefined' && UpdatesModule.updateOrderStatus) {
                    UpdatesModule.updateOrderStatus(orderId, newValue, oldValue, element);
                } else {
                    console.error('❌ UpdatesModule.updateOrderStatus no disponible');
                }
            } else if (type === 'area') {
                if (typeof UpdatesModule !== 'undefined' && UpdatesModule.updateOrderArea) {
                    console.log(`📞 Llamando UpdatesModule.updateOrderArea(${orderId}, ${newValue}, ${oldValue})`);
                    UpdatesModule.updateOrderArea(orderId, newValue, oldValue, element);
                } else {
                    console.error('❌ UpdatesModule.updateOrderArea no disponible');
                }
            }
        }, this.debounceDelay);
        
        this.debounceMap.set(debounceKey, timeoutId);
        console.log(`⏱️ Timeout programado (${this.debounceDelay}ms) - Key: ${debounceKey}`);
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
        
        console.log(`🎨 Clase de dropdown actualizada: ${statusClass}`);
    }
};

// Exponer módulo globalmente
window.OrdersDropdownManager = OrdersDropdownManager;
globalThis.OrdersDropdownManager = OrdersDropdownManager;

console.log('✅ OrdersDropdownManager cargado y disponible globalmente');

