/**
 * MÓDULO: storageModule.js
 * Responsabilidad: Sincronización entre pestañas via localStorage
 * Principios SOLID: SRP (Single Responsibility), DIP (Dependency Inversion)
 */

console.log('📦 Cargando StorageModule...');

const StorageModule = {
    lastTimestamp: 0,

    /**
     * Enviar actualización a otras pestañas
     */
    broadcastUpdate(type, orderId, field, newValue, oldValue, extraData = {}) {
        const timestamp = Date.now();
        
        localStorage.setItem('orders-updates', JSON.stringify({
            type,
            orderId,
            field,
            newValue,
            oldValue,
            ...extraData,
            timestamp
        }));
        
        localStorage.setItem('last-orders-update-timestamp', timestamp.toString());
        console.log(`📡 Actualización transmitida: ${type} para orden ${orderId}`);
    },

    /**
     * Inicializar escucha de mensajes de localStorage
     */
    initializeListener() {
        window.addEventListener('storage', (event) => {
            if (event.key === 'orders-updates') {
                try {
                    const data = JSON.parse(event.newValue);
                    
                    // Evitar procesar mensajes propios
                    const lastTimestamp = parseInt(localStorage.getItem('last-orders-update-timestamp') || '0');
                    if (data.timestamp && data.timestamp <= lastTimestamp) {
                        return;
                    }

                    this.lastTimestamp = data.timestamp;
                    this._processUpdate(data);
                } catch (e) {
                    console.error('Error parsing storage message:', e);
                }
            }
        });
    },

    /**
     * Procesar actualización desde otra pestaña
     */
    _processUpdate(data) {
        const { type, orderId, field, newValue } = data;
        
        const row = document.querySelector(`tr[data-order-id="${orderId}"]`);
        if (!row) return;

        if (type === 'status_update') {
            const dropdown = row.querySelector('.estado-dropdown');
            if (dropdown) {
                dropdown.value = newValue;
                dropdown.dataset.value = newValue;
                RowManager.updateRowColor(orderId, newValue);
            }
        } else if (type === 'area_update') {
            const dropdown = row.querySelector('.area-dropdown');
            if (dropdown) {
                dropdown.value = newValue;
                dropdown.dataset.value = newValue;
            }
        }

        console.log(`📄 Actualización procesada desde otra pestaña: ${field} = ${newValue}`);
    }
};

// Exponer módulo globalmente
window.StorageModule = StorageModule;
globalThis.StorageModule = StorageModule;
