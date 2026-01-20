/**
 * MÓDULO: updates.js
 * Responsabilidad: Manejar actualizaciones al servidor (PATCH requests)
 * Principios SOLID: SRP (Single Responsibility), OCP (Open/Closed)
 */

console.log(' Cargando UpdatesModule...');

const UpdatesModule = {
    baseUrl: window.updateUrl || '/registros',

    /**
     * Actualizar estado de una orden
     */
    updateOrderStatus(orderId, newStatus, oldStatus, dropdown) {
        console.log(`📍 Actualizando estado: Pedido ${orderId}, Estado: ${newStatus}`);
        
        this._sendUpdate(`${this.baseUrl}/${orderId}`, { estado: newStatus }, (data) => {
            if (data.success) {
                console.log(' Estado actualizado correctamente');
                
                // Actualizar la fila en la tabla con los colores condicionales
                const row = document.querySelector(`.table-row[data-orden-id="${orderId}"]`);
                if (row) {
                    // Actualizar el dropdown visualmente
                    const dropdown = row.querySelector('.estado-dropdown');
                    if (dropdown) {
                        dropdown.value = newStatus;
                        dropdown.setAttribute('data-value', newStatus);
                        
                        // 🆕 Actualizar clases de color del dropdown
                        this._updateDropdownColorClass(dropdown, newStatus);
                    }
                    
                    // Aplicar colores condicionales
                    if (typeof applyRowConditionalColors === 'function') {
                        applyRowConditionalColors(row);
                        console.log(` Colores condicionales aplicados para estado: ${newStatus}`);
                    }
                }
                
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
    async updateOrderArea(orderId, newArea, oldArea, dropdown) {
        console.log(`📍 Actualizando área: Pedido ${orderId}, Área: ${newArea}`);
        console.log(`   - Area anterior: ${oldArea}`);
        console.log(`   - Dropdown encontrado: ${!!dropdown}`);
        
        try {
            // Primero actualizar el área en la BD
            const response = await fetch(`${this.baseUrl}/${orderId}`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ area: newArea })
            });
            
            const data = await response.json();
            
            if (response.ok && (data.success || data)) {
                console.log(' Área actualizada en procesos_prenda');
                
                // Actualizar el dropdown visualmente
                if (dropdown) {
                    dropdown.value = newArea;
                    dropdown.dataset.value = newArea;
                    console.log(` Dropdown actualizado visualmente: ${newArea}`);
                    
                    // 🆕 CRÍTICO: Marcar como cambio programático para evitar loop
                    dropdown.dataset.programmaticChange = 'true';
                    
                    // 🆕 CRÍTICO: Dispatchear evento change para activar listeners
                    const changeEvent = new Event('change', { bubbles: true, cancelable: true });
                    dropdown.dispatchEvent(changeEvent);
                    console.log(` Evento 'change' disparado en dropdown (marcado como programático)`);
                }
                
                // 🆕 Actualizar clase de color del dropdown de área si existe
                if (dropdown && typeof this._updateDropdownColorClass === 'function') {
                    this._updateDropdownColorClass(dropdown, newArea);
                }
                
                // 🔴 COMENTADO: La actualización de estados de procesos está causando problemas
                // NO vamos a actualizar automáticamente procesos cuando se cambia el área
                // El usuario es responsable de marcar los procesos como completados cuando corresponda
                // console.log('📍 Actualizando estados de procesos...');
                // await this._updateProcessStates(orderId, oldArea, newArea);
                
                // 🆕 Actualizar color de fila con colores condicionales
                const row = document.querySelector(`.table-row[data-orden-id="${orderId}"]`);
                if (row && typeof applyRowConditionalColors === 'function') {
                    applyRowConditionalColors(row);
                    console.log(' Colores condicionales aplicados para área');
                }
                
                if (window.RowManager && typeof window.RowManager.updateRowColor === 'function') {
                    window.RowManager.updateRowColor(orderId);
                    console.log(' Color de fila actualizado');
                } else if (RowManager && typeof RowManager.updateRowColor === 'function') {
                    RowManager.updateRowColor(orderId);
                    console.log(' Color de fila actualizado');
                }
                
                console.log('📢 Broadcast de actualización de área');
                // Usar window.StorageModule si está disponible
                if (window.StorageModule && typeof window.StorageModule.broadcastUpdate === 'function') {
                    window.StorageModule.broadcastUpdate('area_update', orderId, 'area', newArea, oldArea);
                } else if (StorageModule && typeof StorageModule.broadcastUpdate === 'function') {
                    StorageModule.broadcastUpdate('area_update', orderId, 'area', newArea, oldArea);
                }
                
                // 🆕 CRÍTICO: Asegurar que el dropdown está visible y actualizado en la tabla
                if (dropdown && dropdown.closest('table') !== null) {
                    // Está en la tabla, hacer un pequeño refresh visual
                    dropdown.blur();
                    dropdown.focus();
                    console.log(' Dropdown refrescado visualmente (blur/focus)');
                }
                
                // 🆕 CRÍTICO: Forzar refrescamiento de la fila en la tabla desde el servidor
                // Esto asegura que la tabla se vea actualizada incluso si el modal estaba abierto
                this._refreshRowInTable(orderId, newArea);
                
                console.log(' Actualización de área completada');
            } else {
                throw new Error(data.message || 'Error desconocido al actualizar área');
            }
        } catch (error) {
            console.error(' Error en updateOrderArea:', error);
            if (dropdown) {
                dropdown.value = oldArea;
                dropdown.dataset.value = oldArea;
                console.log(` Dropdown restaurado a valor anterior: ${oldArea}`);
            }
        }
    },

    /**
     * 🆕 Marca el proceso anterior como Completado y el nuevo como Pendiente
     */
    async _updateProcessStates(orderId, oldArea, newArea) {
        try {
            // Obtener los procesos de la orden
            const response = await fetch(`/api/ordenes/${orderId}/procesos`);
            if (!response.ok) {
                console.warn(' No se pudieron obtener los procesos');
                return;
            }
            
            const data = await response.json();
            const procesos = data.procesos || [];
            
            // Encontrar el índice del área anterior y la nueva
            const oldAreaIndex = procesos.findIndex(p => p.proceso === oldArea);
            const newAreaIndex = procesos.findIndex(p => p.proceso === newArea);
            
            // Actualizar el proceso anterior a "Completado"
            if (oldAreaIndex !== -1 && procesos[oldAreaIndex].estado_proceso !== 'Completado') {
                await this._updateProcessState(procesos[oldAreaIndex], 'Completado');
            }
            
            // Actualizar el nuevo proceso a "Pendiente"
            if (newAreaIndex !== -1 && procesos[newAreaIndex].estado_proceso !== 'Pendiente') {
                await this._updateProcessState(procesos[newAreaIndex], 'Pendiente');
            }
        } catch (error) {
            console.error(' Error al actualizar estados de procesos:', error);
        }
    },

    /**
     * 🆕 Actualiza el estado de un proceso individual
     */
    async _updateProcessState(proceso, nuevoEstado) {
        try {
            const response = await fetch(`/api/procesos/${proceso.id}/editar`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    numero_pedido: proceso.numero_pedido,
                    proceso: proceso.proceso,
                    fecha_inicio: proceso.fecha_inicio,
                    encargado: proceso.encargado || '',
                    estado_proceso: nuevoEstado
                })
            });
            
            if (!response.ok) {
                throw new Error('Error al actualizar proceso');
            }
            
            const data = await response.json();
            console.log(` Proceso actualizado: ${proceso.proceso} → ${nuevoEstado}`);
            return data;
        } catch (error) {
            console.error(` Error al actualizar proceso ${proceso.proceso}:`, error);
            throw error;
        }
    },

    /**
     * Actualizar día de entrega
     */
    updateOrderDiaEntrega(orderId, newDias, oldDias, dropdown) {
        const valorAEnviar = (newDias === '' || newDias === null) ? null : Number.parseInt(newDias);
        
        console.log(` Actualizando día de entrega: Pedido ${orderId}, Días: ${valorAEnviar}`);
        
        this._sendUpdate(`${this.baseUrl}/${orderId}`, { dia_de_entrega: valorAEnviar }, (data) => {
            if (data.success) {
                console.log(` Día de entrega actualizado`);
                console.log(` Datos recibidos del servidor:`, data);
                
                if (dropdown) {
                    // Actualizar el valor del dropdown localmente
                    dropdown.value = valorAEnviar || '';
                    dropdown.classList.remove('updating', 'orange-highlight');
                    console.log(` Dropdown actualizado localmente: ${dropdown.value}`);
                }
                
                // Buscar la fila (puede ser tr o div.table-row)
                let row = document.querySelector(`tr[data-numero-pedido="${orderId}"]`);
                if (!row) {
                    row = document.querySelector(`.table-row[data-orden-id="${orderId}"]`);
                }
                console.log(` Buscando fila para orden ${orderId}, encontrada:`, !!row);
                
                if (row) {
                    console.log(` Ejecutando executeRowUpdate para orden ${orderId}`);
                    RowManager.executeRowUpdate(row, data, orderId, valorAEnviar);
                } else {
                    console.warn(` No se encontró fila para actualizar orden ${orderId}`);
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
            console.error(` Error del servidor (${response.status})`);
            NotificationModule.showAutoReload('Error del servidor. Recargando página...', 2000);
            setTimeout(() => window.location.reload(), 2000);
            return Promise.reject('Server error');
        }
        
        if (response.status === 401 || response.status === 419) {
            console.error(` Sesión expirada (${response.status})`);
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
     * 🆕 Refrescar fila en la tabla actualizando el dropdown de área
     */
    _refreshRowInTable(orderId, newArea) {
        try {
            // Buscar la fila en la tabla (por numero_pedido, que es orderId)
            const tabla = document.querySelector('table#tablaOrdenes tbody');
            if (!tabla) {
                console.warn(' Tabla no encontrada para refrescar');
                return;
            }
            
            const fila = tabla.querySelector(`tr[data-numero-pedido="${orderId}"]`);
            if (!fila) {
                console.warn(` Fila ${orderId} no encontrada en tabla`);
                return;
            }
            
            // Obtener el dropdown de área de esta fila
            const dropdown = fila.querySelector(`.area-dropdown[data-id="${orderId}"]`);
            if (!dropdown) {
                console.warn(` Dropdown de área no encontrado en fila ${orderId}`);
                return;
            }
            
            console.log(`🔄 Refrescando dropdown de tabla: ${dropdown.value} → ${newArea}`);
            
            // 🆕 Actualizar el value y dataset
            dropdown.value = newArea;
            dropdown.dataset.value = newArea;
            
            // 🆕 CRÍTICO: Marcar como actualizado para que se vea en la tabla
            // Esto dispara el cambio visual en el navegador
            const event = new Event('input', { bubbles: true });
            dropdown.dispatchEvent(event);
            
            console.log(` Dropdown de tabla refrescado: ${newArea}`);
            console.log(` Evento 'input' disparado para actualizar vista`);
            
        } catch (error) {
            console.error(' Error refrescando fila en tabla:', error);
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
    },

    /**
     * 🆕 Actualizar clases de color del dropdown según el estado
     */
    _updateDropdownColorClass(dropdown, newStatus) {
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
        
        console.log(` Clase de dropdown actualizada: ${statusClass}`);
    }
};

// Exponer módulo globalmente
window.UpdatesModule = UpdatesModule;
globalThis.UpdatesModule = UpdatesModule;

console.log(' UpdatesModule cargado y disponible globalmente');
