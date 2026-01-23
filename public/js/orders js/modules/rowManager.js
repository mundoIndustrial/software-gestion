/**
 * MÓDULO: rowManager.js
 * Responsabilidad: Gestionar operaciones CRUD de filas en la tabla
 * Principios SOLID: SRP (Single Responsibility), OCP (Open/Closed)
 */



const RowManager = {
    /**
     * Actualizar color de fila basado en estado y días
     */
    updateRowColor(orderId, newStatus) {
        const row = document.querySelector(`tr[data-order-id="${orderId}"]`);
        if (!row) return;

        const totalDiasCell = row.querySelector('td[data-column="total_de_dias_"] .cell-text');
        let totalDias = 0;
        
        if (totalDiasCell && totalDiasCell.textContent.trim() !== 'N/A') {
            totalDias = Number.parseInt(totalDiasCell.textContent.trim()) || 0;
        }

        const diaEntregaDropdown = row.querySelector('.dia-entrega-dropdown');
        let diaDeEntrega = null;
        
        if (diaEntregaDropdown && diaEntregaDropdown.value) {
            diaDeEntrega = Number.parseInt(diaEntregaDropdown.value);
        }

        this._applyRowStyles(row, newStatus, totalDias, diaDeEntrega);

    },

    /**
     * Actualizar fila en la tabla
     */
    actualizarOrdenEnTabla(orden) {
        const row = document.querySelector(`tr[data-order-id="${orden.pedido}"]`);
        if (!row) return;

        let hasChanges = false;

        // Actualizar celdas
        const cells = row.querySelectorAll('td[data-column]');
        cells.forEach(cell => {
            const column = cell.getAttribute('data-column');
            if (!column) return;

            const cellContent = cell.querySelector('.cell-content');
            if (!cellContent) return;

            this._updateCell(cellContent, column, orden[column], hasChanges);
        });

        // Aplicar estilos basado en estado
        const estado = orden.estado || '';
        const totalDias = Number.parseInt(orden.total_de_dias_) || 0;
        const diaDeEntrega = orden.dia_de_entrega || null;

        this._applyRowStyles(row, estado, totalDias, diaDeEntrega);
    },

    /**
     * Crear nueva fila en la tabla
     */
    crearFilaOrden(orden) {
        const table = document.querySelector('.modern-table tbody');
        if (!table) return;

        const row = document.createElement('tr');
        row.className = 'table-row';
        row.setAttribute('data-order-id', orden.pedido);

        // Poblar con datos
        const headers = document.querySelectorAll('.modern-table thead th');
        const columns = Array.from(headers)
            .map(th => th.getAttribute('data-column'))
            .filter(Boolean);

        columns.forEach(column => {
            const td = document.createElement('td');
            td.className = 'table-cell editable-cell';
            td.setAttribute('data-column', column);
            td.textContent = orden[column] || '';
            row.appendChild(td);
        });

        table.insertBefore(row, table.firstChild);

    },

    /**
     * Eliminar fila de la tabla
     */
    eliminarFila(pedido) {
        const row = document.querySelector(`tr[data-order-id="${pedido}"]`);
        if (row) {
            row.style.backgroundColor = 'rgba(239, 68, 68, 0.2)';
            setTimeout(() => row.remove(), 500);

        }
    },

    /**
     * Ejecutar actualización completa de fila
     */
    executeRowUpdate(row, data, orderId, valorAEnviar) {

        
        if (!row) {

            return;
        }

        if (data.totalDiasCalculados) {
            const totalDias = data.totalDiasCalculados[orderId] || 0;
            const estado = data.order?.estado || '';



            // Actualizar fecha estimada (incluso si es null para limpiar)
            // Buscar por clase fecha-estimada-cell (para div.table-row)
            let fechaCell = row.querySelector('.fecha-estimada-cell');
            
            // Si no encuentra, buscar por data-column (para tr tradicional)
            if (!fechaCell) {
                fechaCell = row.querySelector('td[data-column="fecha_estimada_de_entrega"]');
            }
            

            
            if (fechaCell) {
                let spanFecha = fechaCell.querySelector('.fecha-estimada-span');
                
                // Si no encuentra .fecha-estimada-span, buscar .cell-text
                if (!spanFecha) {
                    spanFecha = fechaCell.querySelector('.cell-text');
                }
                
                if (spanFecha) {
                    if (data.order?.fecha_estimada_de_entrega) {
                        const fechaFormateada = FormattingModule.formatearFecha(data.order.fecha_estimada_de_entrega);
                        spanFecha.textContent = fechaFormateada;
                        fechaCell.setAttribute('data-fecha-estimada', fechaFormateada);

                    } else {
                        // Si es null, limpiar la fecha
                        spanFecha.textContent = '-';
                        fechaCell.setAttribute('data-fecha-estimada', '-');

                    }
                } else {

                }
            } else {

            }

            // Actualizar estilos
            this._applyRowStyles(row, estado, totalDias, valorAEnviar);
        } else {

        }
    },

    /**
     * Método privado: actualizar celda individual
     */
    _updateCell(cellContent, column, value, hasChanges) {
        if (column === 'estado') {
            const select = cellContent.querySelector('.estado-dropdown');
            if (select && select.value !== value) {
                select.value = value;
                hasChanges = true;
            }
        } else if (column === 'area') {
            const select = cellContent.querySelector('.area-dropdown');
            if (select && select.value !== value) {
                select.value = value;
                hasChanges = true;
            }
        } else if (column === 'dia_de_entrega') {
            const select = cellContent.querySelector('.dia-entrega-dropdown');
            if (select) {
                const valorFinal = (value === null || value === undefined || value === '') ? '' : String(value);
                if (select.value !== valorFinal) {
                    select.value = valorFinal;
                    hasChanges = true;
                }
            }
        }
    },

    /**
     * Método privado: aplicar estilos a fila
     */
    _applyRowStyles(row, estado, totalDias, diaDeEntrega) {
        row.classList.remove('row-delivered', 'row-anulada', 'row-warning', 'row-danger-light', 'row-secondary', 'row-dia-entrega-warning', 'row-dia-entrega-danger', 'row-dia-entrega-critical');

        if (estado === 'Entregado') {
            row.classList.add('row-delivered');
        } else if (estado === 'Anulada') {
            row.classList.add('row-anulada');
        } else if (diaDeEntrega !== null && diaDeEntrega > 0) {
            if (totalDias > diaDeEntrega + 5) {
                row.classList.add('row-dia-entrega-critical');
            } else if (totalDias > diaDeEntrega) {
                row.classList.add('row-dia-entrega-danger');
            } else if (totalDias > diaDeEntrega - 2) {
                row.classList.add('row-dia-entrega-warning');
            }
        } else if (estado === 'En Ejecución') {
            if (totalDias > 30) {
                row.classList.add('row-danger-light');
            } else if (totalDias > 20) {
                row.classList.add('row-warning');
            }
        }
    }
};

// Exponer módulo globalmente
window.RowManager = RowManager;
globalThis.RowManager = RowManager;

