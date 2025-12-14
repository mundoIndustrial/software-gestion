/**
 * Real-time updates script for orders
 * Handles Echo/WebSocket listeners for live order updates
 * Integración con estructura flexbox y colores condicionales
 */

/**
 * Manejador de actualizaciones de órdenes en tiempo real
 */
const RealtimeOrderHandler = {
    /**
     * Actualizar fila en la tabla flexbox
     */
    updateOrderRow(ordenData, changedFields) {
        console.log('🔄 RealtimeOrderHandler.updateOrderRow iniciado', {
            numeroPedido: ordenData.numero_pedido,
            changedFields: changedFields
        });

        // Buscar la fila por data-orden-id (estructura flexbox actual)
        const row = document.querySelector(`.table-row[data-orden-id="${ordenData.numero_pedido}"]`);
        
        if (!row) {
            console.warn(`⚠️ Fila no encontrada para pedido ${ordenData.numero_pedido}`);
            return;
        }

        // Actualizar campos que cambiaron
        if (changedFields && Array.isArray(changedFields)) {
            changedFields.forEach(field => {
                this._updateField(row, field, ordenData);
            });
        }

        // Aplicar colores condicionales si cambió el estado
        if (changedFields && changedFields.includes('estado')) {
            console.log(`🎨 Aplicando colores condicionales para estado: ${ordenData.estado}`);
            applyRowConditionalColors(row);
        }

        console.log(`✅ Fila ${ordenData.numero_pedido} actualizada en tiempo real`);
    },

    /**
     * Actualizar un campo específico de la fila
     */
    _updateField(row, field, ordenData) {
        console.log(`📝 Actualizando campo: ${field}`);

        if (field === 'estado') {
            const dropdown = row.querySelector('.estado-dropdown');
            if (dropdown && ordenData.estado) {
                dropdown.value = ordenData.estado;
                dropdown.setAttribute('data-value', ordenData.estado);
                
                // 🆕 Actualizar clase de color del dropdown
                this._updateDropdownColorClass(dropdown, ordenData.estado);
                
                console.log(`✅ Estado actualizado: ${ordenData.estado}`);
            }
        } else if (field === 'area') {
            const dropdown = row.querySelector('.area-dropdown');
            if (dropdown && ordenData.area) {
                dropdown.value = ordenData.area;
                dropdown.setAttribute('data-value', ordenData.area);
                
                // 🆕 Actualizar clase de color del dropdown
                this._updateDropdownColorClass(dropdown, ordenData.area);
                
                console.log(`✅ Área actualizada: ${ordenData.area}`);
            }
        } else if (field === 'dia_de_entrega') {
            const dropdown = row.querySelector('.dia-entrega-dropdown');
            if (dropdown && ordenData.dia_de_entrega !== undefined) {
                dropdown.value = ordenData.dia_de_entrega || '';
                console.log(`✅ Día de entrega actualizado: ${ordenData.dia_de_entrega}`);
            }
        } else if (field === 'novedades') {
            // 🆕 Actualizar campo de novedades en tiempo real
            const btnEdit = row.querySelector('.btn-edit-novedades');
            if (btnEdit && ordenData.novedades !== undefined) {
                const textSpan = btnEdit.querySelector('.novedades-text');
                if (textSpan) {
                    if (ordenData.novedades) {
                        textSpan.textContent = ordenData.novedades.length > 50 
                            ? ordenData.novedades.substring(0, 50) + '...' 
                            : ordenData.novedades;
                        textSpan.classList.remove('empty');
                    } else {
                        textSpan.textContent = 'Sin novedades';
                        textSpan.classList.add('empty');
                    }
                    console.log(`✅ Novedades actualizadas en tiempo real: ${ordenData.novedades ? 'Con contenido' : 'Vacío'}`);
                }
            }
        }
    },

    /**
     * Actualizar clases de color del dropdown
     */
    _updateDropdownColorClass(dropdown, value) {
        if (!dropdown) return;
        
        // Para estado-dropdown
        if (dropdown.classList.contains('estado-dropdown')) {
            dropdown.classList.remove(
                'estado-entregado',
                'estado-en-ejecución',
                'estado-no-iniciado',
                'estado-anulada'
            );
            const statusClass = `estado-${value.toLowerCase().replace(/ /g, '-')}`;
            dropdown.classList.add(statusClass);
            console.log(`🎨 Clase de estado-dropdown actualizada: ${statusClass}`);
        }
        // Para area-dropdown (si hay estilos en el futuro)
        else if (dropdown.classList.contains('area-dropdown')) {
            // Aquí se pueden agregar estilos de área si es necesario
            console.log(`🎨 Área dropdown actualizado: ${value}`);
        }
    }
};

/**
 * Initialize real-time listeners for orders
 */
function initializeOrdenesRealtimeListeners() {
    console.log('=== ÓRDENES - Inicializando Echo para tiempo real ===');
    console.log('window.Echo disponible:', !!window.Echo);

    if (!window.Echo) {
        console.error('❌ Echo NO está disponible. Reintentando en 500ms...');
        setTimeout(initializeOrdenesRealtimeListeners, 500);
        return;
    }

    console.log('✅ Echo disponible. Suscribiendo al canal "ordenes"...');

    // Canal de Órdenes
    const ordenesChannel = window.Echo.channel('ordenes');

    ordenesChannel.subscribed(() => {
        console.log('✅ Suscrito al canal "ordenes"');
    });

    ordenesChannel.error((error) => {
        console.error('❌ Error en canal "ordenes":', error);
    });

    ordenesChannel.listen('OrdenUpdated', (e) => {
        console.log('🎉 Evento OrdenUpdated recibido!', e);
        
        // Usar el nuevo manejador RealtimeOrderHandler
        if (typeof RealtimeOrderHandler !== 'undefined' && RealtimeOrderHandler.updateOrderRow) {
            console.log('📡 Llamando RealtimeOrderHandler.updateOrderRow');
            RealtimeOrderHandler.updateOrderRow(e.orden, e.changedFields);
        } else {
            console.warn('⚠️ RealtimeOrderHandler no está disponible');
        }
    });

    console.log('✅ Listener de órdenes configurado');
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        setTimeout(initializeOrdenesRealtimeListeners, 100);
    });
} else {
    setTimeout(initializeOrdenesRealtimeListeners, 100);
}

