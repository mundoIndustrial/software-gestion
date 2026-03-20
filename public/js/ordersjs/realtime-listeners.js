/**
 * Real-time updates script for orders - FASE 5 v1.0
 * Maneja listeners de WebSocket para actualizaciones en tiempo real
 * Migración de Echo/Reverb (FASE 4) → window.shared.websocket (FASE 5)
 * 
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
        // Buscar la fila por data-orden-id (puede ser ID o numero_pedido según la vista)
        let row = document.querySelector(`[data-orden-id="${ordenData.numero_pedido}"]`);

        // Si no encuentra por numero_pedido, buscar por ID
        if (!row) {
            row = document.querySelector(`[data-orden-id="${ordenData.id}"]`);
        }

        if (!row) {
            const esRegistros = window.location && window.location.pathname && window.location.pathname.includes('/registros');
            const cambioEstado = changedFields && Array.isArray(changedFields) && changedFields.includes('estado');
            if (esRegistros && cambioEstado) {
                setTimeout(() => location.reload(), 300);
            }
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

            applyRowConditionalColors(row);
        }

    },

    /**
     * Actualizar un campo específico de la fila
     */
    _updateField(row, field, ordenData) {

        if (field === 'estado') {
            const dropdown = row.querySelector('.estado-dropdown');
            if (dropdown && ordenData.estado) {
                dropdown.value = ordenData.estado;
                dropdown.setAttribute('data-value', ordenData.estado);

                // 🆕 Actualizar clase de color del dropdown
                this._updateDropdownColorClass(dropdown, ordenData.estado);

            }
        } else if (field === 'area') {
            const dropdown = row.querySelector('.area-dropdown');
            if (dropdown && ordenData.area) {
                dropdown.value = ordenData.area;
                dropdown.setAttribute('data-value', ordenData.area);

                // 🆕 Actualizar clase de color del dropdown
                this._updateDropdownColorClass(dropdown, ordenData.area);

            }
        } else if (field === 'dia_de_entrega') {
            const dropdown = row.querySelector('.dia-entrega-dropdown');
            if (dropdown && ordenData.dia_de_entrega !== undefined) {
                dropdown.value = ordenData.dia_de_entrega || '';

            }
        } else if (field === 'fecha_estimada_de_entrega') {
            // 🆕 Actualizar fecha estimada en tiempo real
            // Buscar en supervisor-pedidos (clase: fecha-estimada)
            let fechaCell = row.querySelector('.fecha-estimada');

            // Si no está en supervisor-pedidos, buscar en orders/index (clase: fecha-estimada-cell)
            if (!fechaCell) {
                fechaCell = row.querySelector('.fecha-estimada-cell');
            }

            if (fechaCell && ordenData.fecha_estimada_de_entrega !== undefined) {
                const fechaFormato = ordenData.fecha_estimada_de_entrega 
                    ? this._formatFecha(ordenData.fecha_estimada_de_entrega)
                    : '-';

                // Para supervisor-pedidos (actualizar directamente la celda)
                if (fechaCell.classList.contains('fecha-estimada')) {
                    fechaCell.textContent = fechaFormato;
                    fechaCell.setAttribute('data-fecha-estimada', fechaFormato);
                }

                // Para orders/index (actualizar el span dentro)
                if (fechaCell.classList.contains('fecha-estimada-cell')) {
                    const span = fechaCell.querySelector('.fecha-estimada-span');
                    if (span) {
                        span.textContent = fechaFormato;
                    }
                    fechaCell.setAttribute('data-fecha-estimada', fechaFormato);
                }

            }
        } else if (field === 'novedades') {
            // 🆕 Actualizar campo de novedades en tiempo real
            const btnEdit = row.querySelector('.btn-edit-novedades');
            if (btnEdit && ordenData.novedades !== undefined) {
                // 🆕 Guardar el valor completo en data-full-novedades
                btnEdit.setAttribute('data-full-novedades', ordenData.novedades || '');

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

                }
            }
        }
    },

    /**
     * Formatear fecha a formato d/m/Y
     */
    _formatFecha(fecha) {
        if (!fecha) return 'N/A';

        try {
            // Si es string ISO, parsear
            const date = typeof fecha === 'string' ? new Date(fecha) : fecha;
            const day = String(date.getDate()).padStart(2, '0');
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const year = date.getFullYear();
            return `${day}/${month}/${year}`;
        } catch (e) {

            return fecha;
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

        }
        // Para area-dropdown (si hay estilos en el futuro)
        else if (dropdown.classList.contains('area-dropdown')) {
            // Aquí se pueden agregar estilos de área si es necesario

        }
    }
};

/**
 * Initialize real-time listeners for orders via WebSocket
 */
function initializeOrdenesRealtimeListeners() {
    try {
        if (typeof window.waitForEcho !== 'function') {
            setTimeout(initializeOrdenesRealtimeListeners, 200);
            return;
        }

        window.waitForEcho(() => {
            const ws = window.shared.websocket;

            if (!ws) {
                console.warn('[Realtime Orders] WebSocket no disponible');
                return;
            }

            // ==========================================
            // CANAL: ordenes
            // ==========================================
            try {
                ws.subscribe('ordenes', 'OrdenUpdated', (e) => {
                    // Usar el nuevo manejador RealtimeOrderHandler
                    if (typeof RealtimeOrderHandler !== 'undefined' && RealtimeOrderHandler.updateOrderRow) {
                        RealtimeOrderHandler.updateOrderRow(e.orden, e.changedFields);
                    } else {
                        console.warn('[Realtime Orders] ⚠️ RealtimeOrderHandler no disponible');
                    }
                });
            } catch (error) {
                console.error('[Realtime Orders] ❌ Error subscribiendo a ordenes/OrdenUpdated:', error);
            }

        });
    } catch (error) {
        console.error('[Realtime Orders] ❌ Error inicializando listeners:', error);
    }
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        initializeOrdenesRealtimeListeners();
    });
} else {

    initializeOrdenesRealtimeListeners();
}

