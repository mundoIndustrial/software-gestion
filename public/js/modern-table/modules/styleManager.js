/**
 * StyleManager
 * Responsabilidad: Gestionar estilos y CSS de la tabla
 * SOLID: Single Responsibility
 */
const StyleManager = (() => {
    return {
        // Aplicar estilos guardados
        applySavedSettings: (storage) => {
            const { rowHeight, tableWidth, tableHeight, columnWidths, tablePosition, headerPosition } = storage;

            document.documentElement.style.setProperty('--row-height', `${rowHeight}px`);
            document.documentElement.style.setProperty('--table-width', tableWidth ? `${tableWidth}px` : '100%');
            document.documentElement.style.setProperty('--table-height', tableHeight ? `${tableHeight}px` : 'auto');

            Object.entries(columnWidths).forEach(([colIndex, width]) => {
                const th = document.querySelector(`#tablaOrdenes thead th:nth-child(${parseInt(colIndex) + 1})`);
                if (th) th.style.width = `${width}px`;
            });

            document.querySelectorAll('#tablaOrdenes tbody tr').forEach(row => {
                row.style.height = `${rowHeight}px`;
            });

            StyleManager.applyWrapperStyles(storage);
            StyleManager.applyHeaderStyles(storage);
        },

        // Estilos del wrapper
        applyWrapperStyles: (storage) => {
            const wrapper = document.querySelector('.modern-table-wrapper');
            if (!wrapper) return;

            const { tableWidth, tableHeight, tablePosition, moveTableEnabled } = storage;
            
            wrapper.style.width = 'var(--table-width)';
            wrapper.style.maxWidth = 'var(--table-width)';
            wrapper.style.height = tableHeight ? 'var(--table-height)' : 'auto';

            if (tablePosition) {
                wrapper.style.position = 'absolute';
                wrapper.style.left = `${tablePosition.x}px`;
                wrapper.style.top = `${tablePosition.y}px`;
                wrapper.style.cursor = moveTableEnabled ? 'move' : '';
                wrapper.style.zIndex = moveTableEnabled ? '999' : '';
            }
        },

        // Estilos del header
        applyHeaderStyles: (storage) => {
            const container = document.querySelector('.table-scroll-container');
            const tableHeader = document.getElementById('tableHeader');
            const { rowHeight, tableWidth, tableHeight, headerPosition, moveHeaderEnabled } = storage;

            if (container) {
                container.style.width = 'var(--table-width)';
                container.style.height = tableHeight ? 'var(--table-height)' : `calc(${rowHeight}px * 14 + 60px)`;
            }

            if (tableHeader && headerPosition) {
                tableHeader.style.position = 'absolute';
                tableHeader.style.left = `${headerPosition.x}px`;
                tableHeader.style.top = `${headerPosition.y}px`;
                tableHeader.style.cursor = moveHeaderEnabled ? 'move' : '';
                tableHeader.style.zIndex = moveHeaderEnabled ? '998' : '';
            } else if (tableHeader) {
                tableHeader.style.position = '';
                tableHeader.style.left = '';
                tableHeader.style.top = '';
            }
        },

        // Crear resizers para columnas
        createResizers: () => {
            const thead = document.querySelector('#tablaOrdenes thead');
            if (!thead) return;

            thead.querySelectorAll('th').forEach((th, i) => {
                const resizer = document.createElement('div');
                resizer.className = 'column-resizer';
                resizer.dataset.column = i;
                th.style.position = 'relative';
                th.appendChild(resizer);
            });
        },

        // Crear botón estilizado
        createButton: (id, className, icon, text, style = '') => {
            const btn = document.createElement('button');
            Object.assign(btn, { id, className });
            btn.style.cssText = `margin-left:10px;font-size:12px;${style}`;
            btn.innerHTML = `<i class="fas ${icon}"></i><span>${text}</span>`;
            return btn;
        },

        // Formatear texto con wrapping
        wrapText: (text, maxChars) => {
            return text || '';
        },

        // Aplicar wrapping a celdas
        setupCellTextWrapping: () => {
            document.querySelectorAll('.cell-text').forEach(cell => {
                cell.textContent = StyleManager.wrapText(cell.textContent, 20);
                cell.style.whiteSpace = 'nowrap';
                cell.style.overflow = 'visible';
            });
        }
    };
})();

globalThis.StyleManager = StyleManager;
