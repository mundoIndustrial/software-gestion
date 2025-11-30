/**
 * MÓDULO: updates.js
 * Responsabilidad: Manejar actualizaciones al servidor (PATCH requests)
 * Principios SOLID: SRP (Single Responsibility), OCP (Open/Closed)
 */

console.log('📦 Cargando UpdatesModule...');

const UpdatesModule = {
    baseUrl: window.updateUrl || '/registros',

    /**
     * Actualizar estado de una orden
     */
    updateOrderStatus(orderId, newStatus, oldStatus, dropdown) {
        console.log(`📍 Actualizando estado: Pedido ${orderId}, Estado: ${newStatus}`);
        
        this._sendUpdate(`${this.baseUrl}/${orderId}`, { estado: newStatus }, (data) => {
            if (data.success) {
                console.log('✅ Estado actualizado correctamente');
                RowManager.updateRowColor(orderId, newStatus);
                StorageModule.broadcastUpdate('status_update', orderId, 'estado', newStatus, oldStatus, data);
            } else {
                this._handleError(dropdown, oldStatus, 'estado');
            }
        });
    },

    /**
     * Actualizar área de una orden
     */
    updateOrderArea(orderId, newArea, oldArea, dropdown) {
        console.log(`📍 Actualizando área: Pedido ${orderId}, Área: ${newArea}`);
        
        this._sendUpdate(`${this.baseUrl}/${orderId}`, { area: newArea }, (data) => {
            if (data.success) {
                console.log('✅ Proceso creado/actualizado correctamente en procesos_prenda');
                
                if (dropdown) {
                    dropdown.value = newArea;
                    dropdown.dataset.value = newArea;
                }
                
                StorageModule.broadcastUpdate('area_update', orderId, 'area', newArea, oldArea);
            } else {
                this._handleError(dropdown, oldArea, 'area');
            }
        });
    },

    /**
     * Actualizar día de entrega
     */
    updateOrderDiaEntrega(orderId, newDias, oldDias, dropdown) {
        const valorAEnviar = (newDias === '' || newDias === null) ? null : Number.parseInt(newDias);
        
        console.log(`📝 Actualizando día de entrega: Pedido ${orderId}, Días: ${valorAEnviar}`);
        
        this._sendUpdate(`${this.baseUrl}/${orderId}`, { dia_de_entrega: valorAEnviar }, (data) => {
            if (data.success) {
                console.log(`✅ Día de entrega actualizado`);
                
                if (dropdown) {
                    dropdown.classList.remove('updating');
                }
                
                const row = document.querySelector(`tr[data-numero-pedido="${orderId}"]`);
                if (row) {
                    RowManager.executeRowUpdate(row, data, orderId, valorAEnviar);
                }
            } else {
                this._handleError(dropdown, oldDias, 'dia_de_entrega');
            }
        });
    },

    /**
     * Método privado para enviar PATCH request
     */
    _sendUpdate(url, body, successCallback) {
        fetch(url, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(body)
        })
            .then(response => this._handleResponse(response))
            .then(data => successCallback(data))
            .catch(error => this._handleNetworkError(error));
    },

    /**
     * Manejar respuesta del servidor
     */
    _handleResponse(response) {
        if (response.status >= 500) {
            console.error(`❌ Error del servidor (${response.status})`);
            NotificationModule.showAutoReload('Error del servidor. Recargando página...', 2000);
            setTimeout(() => window.location.reload(), 2000);
            return Promise.reject('Server error');
        }
        
        if (response.status === 401 || response.status === 419) {
            console.error(`❌ Sesión expirada (${response.status})`);
            NotificationModule.showAutoReload('Sesión expirada. Recargando página...', 1000);
            setTimeout(() => window.location.reload(), 1000);
            return Promise.reject('Session expired');
        }
        
        return response.json();
    },

    /**
     * Manejar errores de red
     */
    _handleNetworkError(error) {
        if (error !== 'Server error' && error !== 'Session expired') {
            console.error('Error:', error);
            window.consecutiveErrors = (window.consecutiveErrors || 0) + 1;
            
            if (window.consecutiveErrors >= 3) {
                NotificationModule.showAutoReload('Múltiples errores. Recargando página...', 3000);
                setTimeout(() => window.location.reload(), 3000);
            }
        }
    },

    /**
     * Manejar errores de actualización
     */
    _handleError(dropdown, oldValue, field) {
        if (dropdown) {
            dropdown.value = oldValue;
            dropdown.dataset.value = oldValue;
        }
        console.error(`Error al actualizar ${field}`);
    }
};

// Exponer módulo globalmente
window.UpdatesModule = UpdatesModule;
globalThis.UpdatesModule = UpdatesModule;

console.log('✅ UpdatesModule cargado y disponible globalmente');
