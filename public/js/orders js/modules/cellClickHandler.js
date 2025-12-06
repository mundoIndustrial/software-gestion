/**
 * MÓDULO: cellClickHandler.js
 * Responsabilidad: Manejar clicks en celdas editables y abrir el modal
 * Principios SOLID: SRP (Single Responsibility)
 */

console.log('📦 Cargando CellClickHandler...');

const CellClickHandler = {
    /**
     * Columnas editables
     */
    editableColumns: [
        'novedades',
        'descripcion',
        'cliente',
        'asesor',
        'forma_de_pago',
    ],

    /**
     * Inicializar el módulo
     */
    initialize() {
        console.log('🔧 Inicializando CellClickHandler...');
        this._attachCellClickListeners();
        this._listenForTableUpdates();
        console.log('✅ CellClickHandler inicializado');
    },

    /**
     * Adjuntar listeners de click a celdas
     */
    _attachCellClickListeners() {
        document.addEventListener('click', (e) => {
            const cell = e.target.closest('.table-cell');
            if (!cell) return;

            const row = cell.closest('.table-row');
            if (!row) return;

            // Obtener el orden ID
            const orderId = row.getAttribute('data-orden-id');
            if (!orderId) return;

            // Obtener el contenido de la celda
            const cellContent = cell.querySelector('.cell-content');
            if (!cellContent) return;

            // Obtener la columna basada en la posición
            const column = this._getColumnFromCell(row, cell);
            if (!column || !this.editableColumns.includes(column)) return;

            // Obtener el valor actual
            const currentValue = this._extractCellValue(cellContent, column);

            // Abrir el modal
            if (typeof CellEditModal !== 'undefined') {
                CellEditModal.open(orderId, column, currentValue);
            } else {
                console.error('❌ CellEditModal no disponible');
            }
        });
    },

    /**
     * Escuchar actualizaciones de la tabla
     */
    _listenForTableUpdates() {
        document.addEventListener('cell-edit-save', async (e) => {
            const { orderId, column, newValue, oldValue } = e.detail;

            console.log(`🔄 Guardando cambios: orden=${orderId}, columna=${column}`);

            try {
                // Enviar actualización al servidor
                const response = await fetch(`/api/ordenes/${orderId}`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: JSON.stringify({
                        [column]: newValue,
                    }),
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const data = await response.json();
                console.log('✅ Cambios guardados:', data);

                // Actualizar la celda en la tabla
                this._updateCellInTable(orderId, column, newValue);

                // Notificar éxito
                if (typeof NotificationModule !== 'undefined') {
                    NotificationModule.showSuccess(`${this._getColumnLabel(column)} actualizado correctamente`);
                }
            } catch (error) {
                console.error('❌ Error al guardar cambios:', error);

                // Notificar error
                if (typeof NotificationModule !== 'undefined') {
                    NotificationModule.showError('Error al guardar los cambios');
                }
            }
        });
    },

    /**
     * Obtener la columna basada en la posición de la celda
     */
    _getColumnFromCell(row, cell) {
        const cells = Array.from(row.querySelectorAll('.table-cell'));
        const cellIndex = cells.indexOf(cell);

        // Mapeo de índices a columnas (basado en el orden del HTML)
        const columnMap = {
            0: 'acciones',
            1: 'estado',
            2: 'area',
            3: 'dia_de_entrega',
            4: 'total_de_dias',
            5: 'numero_pedido',
            6: 'cliente',
            7: 'descripcion',
            8: 'cantidad',
            9: 'novedades',
            10: 'asesor',
            11: 'forma_de_pago',
            12: 'fecha_de_creacion_de_orden',
            13: 'fecha_estimada_de_entrega',
            14: 'usuario_anulacion',
        };

        return columnMap[cellIndex] || null;
    },

    /**
     * Extraer el valor de la celda
     */
    _extractCellValue(cellContent, column) {
        // Para dropdowns
        if (column === 'estado' || column === 'area' || column === 'dia_de_entrega') {
            const select = cellContent.querySelector('select');
            if (select) {
                return select.value;
            }
        }

        // Para texto
        const span = cellContent.querySelector('span');
        if (span) {
            return span.textContent.trim();
        }

        return cellContent.textContent.trim();
    },

    /**
     * Actualizar celda en la tabla
     */
    _updateCellInTable(orderId, column, newValue) {
        const row = document.querySelector(`[data-orden-id="${orderId}"]`);
        if (!row) return;

        const cells = Array.from(row.querySelectorAll('.table-cell'));
        const columnMap = {
            'cliente': 6,
            'descripcion': 7,
            'novedades': 9,
            'asesor': 10,
            'forma_de_pago': 11,
        };

        const cellIndex = columnMap[column];
        if (cellIndex === undefined) return;

        const cell = cells[cellIndex];
        if (!cell) return;

        const cellContent = cell.querySelector('.cell-content');
        if (!cellContent) return;

        const span = cellContent.querySelector('span');
        if (span) {
            span.textContent = newValue;
            console.log(`✅ Celda actualizada: ${column} = ${newValue}`);
        }
    },

    /**
     * Obtener etiqueta de columna
     */
    _getColumnLabel(column) {
        const labels = {
            novedades: 'Novedades',
            descripcion: 'Descripción',
            cliente: 'Cliente',
            asesor: 'Asesor',
            forma_de_pago: 'Forma de Pago',
        };

        return labels[column] || column;
    },
};

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    CellClickHandler.initialize();
});

console.log('✅ CellClickHandler cargado');
