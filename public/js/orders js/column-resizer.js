// ========================================
// COLUMN RESIZER
// Redimensionamiento de columnas tipo Google Sheets
// ========================================

const ColumnResizer = (() => {
    let isResizing = false;
    let currentColumn = null;
    let startX = 0;
    let startWidth = 0;
    const COLUMN_WIDTHS_KEY = 'columnWidths';

    // Obtener anchos guardados
    const getSavedWidths = () => {
        const saved = localStorage.getItem(COLUMN_WIDTHS_KEY);
        return saved ? JSON.parse(saved) : {};
    };

    // Guardar anchos
    const saveWidths = (widths) => {
        localStorage.setItem(COLUMN_WIDTHS_KEY, JSON.stringify(widths));
    };

    // Aplicar anchos guardados
    const applySavedWidths = () => {
        const widths = getSavedWidths();
        const headerCells = document.querySelectorAll('.table-header-cell');
        
        headerCells.forEach((cell, index) => {
            const key = `col_${index}`;
            if (widths[key]) {
                cell.style.flex = `0 0 ${widths[key]}px`;
            }
        });
    };

    // Crear handle de redimensionamiento
    const createResizeHandle = (headerCell, index) => {
        const handle = document.createElement('div');
        handle.className = 'column-resize-handle';
        
        handle.addEventListener('mouseenter', () => {
            handle.style.background = 'rgba(255, 255, 255, 0.6)';
        });

        handle.addEventListener('mouseleave', () => {
            if (!isResizing) {
                handle.style.background = 'transparent';
            }
        });

        handle.addEventListener('mousedown', (e) => {
            e.preventDefault();
            e.stopPropagation();
            isResizing = true;
            currentColumn = index;
            startX = e.clientX;
            startWidth = headerCell.offsetWidth;
            handle.style.background = 'rgba(255, 255, 255, 0.8)';
            document.body.style.cursor = 'col-resize';
            document.body.style.userSelect = 'none';
        });

        return handle;
    };

    // Inicializar redimensionamiento
    const init = () => {
        const tableHead = document.querySelector('.table-head');
        if (!tableHead) return;

        const headerCells = tableHead.querySelectorAll('.table-header-cell');
        
        // Aplicar anchos guardados
        applySavedWidths();

        // Agregar handles a cada columna
        headerCells.forEach((cell, index) => {
            cell.style.position = 'relative';
            const handle = createResizeHandle(cell, index);
            cell.appendChild(handle);
        });

        // Event listeners globales
        document.addEventListener('mousemove', handleMouseMove);
        document.addEventListener('mouseup', handleMouseUp);
    };

    // Manejar movimiento del mouse
    const handleMouseMove = (e) => {
        if (!isResizing || currentColumn === null) return;

        const headerCells = document.querySelectorAll('.table-header-cell');
        const currentCell = headerCells[currentColumn];
        
        if (!currentCell) return;

        const diff = e.clientX - startX;
        const newWidth = Math.max(50, startWidth + diff); // Mínimo 50px

        // Aplicar nuevo ancho SOLO al header
        currentCell.style.minWidth = `${newWidth}px`;
        currentCell.style.maxWidth = `${newWidth}px`;
        currentCell.style.width = `${newWidth}px`;
        currentCell.style.flex = `0 0 ${newWidth}px`;
    };

    // Manejar fin del redimensionamiento
    const handleMouseUp = () => {
        if (!isResizing) return;

        isResizing = false;
        document.body.style.cursor = 'auto';
        document.body.style.userSelect = 'auto';

        // Guardar anchos
        const headerCells = document.querySelectorAll('.table-header-cell');
        const widths = {};
        
        headerCells.forEach((cell, index) => {
            const width = cell.offsetWidth;
            widths[`col_${index}`] = width;
        });

        saveWidths(widths);

        // Resetear color del handle
        const handles = document.querySelectorAll('.column-resize-handle');
        handles.forEach(handle => {
            handle.style.background = 'transparent';
        });

        currentColumn = null;
    };

    return {
        init,
        applySavedWidths,
        getSavedWidths,
        saveWidths
    };
})();

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    ColumnResizer.init();
});
