/**
 * ColumnManager
 * Responsabilidad: Gestionar redimensionamiento y ancho de columnas
 * SOLID: Single Responsibility
 */
const ColumnManager = (() => {
    return {
        // Configurar redimensionamiento de columnas
        setupColumnResizing: (storage) => {
            let state = { isResizing: false, resizer: null, startX: 0, startWidth: 0, column: null };

            const handleMove = e => {
                if (!state.isResizing) return;
                
                const delta = e.clientX - state.startX;
                const newWidth = Math.max(50, state.startWidth + delta);
                const th = state.resizer.parentElement;
                const colIndex = state.column;

                th.style.width = `${newWidth}px`;
                th.style.setProperty('--col-width', `${newWidth}px`);

                document.querySelectorAll(`#tablaOrdenes tbody td:nth-child(${colIndex + 1})`).forEach(td => {
                    td.style.width = `${newWidth}px`;
                });

                storage.columnWidths[colIndex] = newWidth;
                StorageManager.setObject('columnWidths', storage.columnWidths);
            };

            const handleUp = () => {
                if (!state.isResizing) return;
                state.isResizing = false;
                state.resizer?.classList.remove('dragging');
                document.body.style.cursor = '';
                document.body.style.userSelect = '';
            };

            document.addEventListener('mousedown', e => {
                if (e.target.classList.contains('column-resizer')) {
                    state.isResizing = true;
                    state.resizer = e.target;
                    state.startX = e.clientX;
                    state.startWidth = e.target.parentElement.offsetWidth;
                    state.column = parseInt(e.target.dataset.column);

                    state.resizer.classList.add('dragging');
                    document.body.style.cursor = 'col-resize';
                    document.body.style.userSelect = 'none';
                }
            });

            document.addEventListener('mousemove', handleMove);
            document.addEventListener('mouseup', handleUp);
        },

        // Extraer información de columnas
        extractTableHeaders: () => {
            const table = document.getElementById('tablaOrdenes');
            return Array.from(table.querySelectorAll('thead th')).map((th, i) => {
                const headerText = th.querySelector('.header-text')?.textContent.trim() || '';
                const filterBtn = th.querySelector('.filter-btn');
                return {
                    index: i,
                    name: headerText,
                    originalName: filterBtn ? filterBtn.dataset.columnName : headerText.toLowerCase().replace(/\s+/g, '_')
                };
            });
        },

        // Normalizar texto
        normalizeText: (text) => {
            return text.toLowerCase().trim().replace(/\s+/g, ' ');
        }
    };
})();

globalThis.ColumnManager = ColumnManager;
