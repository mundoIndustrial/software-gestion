/**
 * DragManager
 * Responsabilidad: Gestionar drag & drop de tabla y header
 * SOLID: Single Responsibility
 */
const DragManager = (() => {
    return {
        // Habilitar drag de tabla
        enableTableDragging: (storage) => {
            const tableWrapper = document.querySelector('.modern-table-wrapper');
            if (!tableWrapper) return;

            tableWrapper.style.position = 'absolute';
            tableWrapper.style.cursor = 'move';
            tableWrapper.style.zIndex = '999';

            let isDragging = false;
            let startX, startY, initialX, initialY;

            const mouseDownHandler = (e) => {
                isDragging = true;
                startX = e.clientX;
                startY = e.clientY;
                initialX = tableWrapper.offsetLeft;
                initialY = tableWrapper.offsetTop;

                document.addEventListener('mousemove', mouseMoveHandler);
                document.addEventListener('mouseup', mouseUpHandler);
            };

            const mouseMoveHandler = (e) => {
                if (!isDragging) return;

                const dx = e.clientX - startX;
                const dy = e.clientY - startY;

                let newX = initialX + dx;
                let newY = initialY + dy;

                const sidebar = document.querySelector('.sidebar, #sidebar, .main-sidebar');
                if (sidebar) {
                    const sidebarRight = sidebar.offsetLeft + sidebar.offsetWidth;
                    if (newX < sidebarRight) newX = sidebarRight;
                }

                if (newY < 0) newY = 0;

                tableWrapper.style.left = `${newX}px`;
                tableWrapper.style.top = `${newY}px`;
            };

            const mouseUpHandler = () => {
                isDragging = false;
                storage.tablePosition = { x: parseInt(tableWrapper.style.left || 0), y: parseInt(tableWrapper.style.top || 0) };
                StorageManager.setObject('tablePosition', storage.tablePosition);
                document.removeEventListener('mousemove', mouseMoveHandler);
                document.removeEventListener('mouseup', mouseUpHandler);
            };

            tableWrapper.addEventListener('mousedown', mouseDownHandler);
            tableWrapper._dragHandler = mouseDownHandler;
        },

        // Deshabilitar drag de tabla
        disableTableDragging: () => {
            const tableWrapper = document.querySelector('.modern-table-wrapper');
            if (!tableWrapper) return;

            tableWrapper.style.position = '';
            tableWrapper.style.left = '';
            tableWrapper.style.top = '';
            tableWrapper.style.cursor = '';
            tableWrapper.style.zIndex = '';

            if (tableWrapper._dragHandler) {
                tableWrapper.removeEventListener('mousedown', tableWrapper._dragHandler);
                delete tableWrapper._dragHandler;
            }
        },

        // Habilitar drag del header
        enableHeaderDragging: (storage) => {
            const tableHeader = document.getElementById('tableHeader');
            if (!tableHeader) return;

            tableHeader.style.position = 'absolute';
            tableHeader.style.cursor = 'move';
            tableHeader.style.zIndex = '998';

            let isDragging = false;
            let startX, startY, initialX, initialY;

            const mouseDownHandler = (e) => {
                isDragging = true;
                startX = e.clientX;
                startY = e.clientY;
                initialX = tableHeader.offsetLeft;
                initialY = tableHeader.offsetTop;

                document.addEventListener('mousemove', mouseMoveHandler);
                document.addEventListener('mouseup', mouseUpHandler);
            };

            const mouseMoveHandler = (e) => {
                if (!isDragging) return;

                const dx = e.clientX - startX;
                const dy = e.clientY - startY;

                let newX = initialX + dx;
                let newY = initialY + dy;

                const sidebar = document.querySelector('.sidebar, #sidebar, .main-sidebar');
                if (sidebar) {
                    const sidebarRight = sidebar.offsetLeft + sidebar.offsetWidth;
                    if (newX < sidebarRight) newX = sidebarRight;
                }

                if (newY < 0) newY = 0;

                tableHeader.style.left = `${newX}px`;
                tableHeader.style.top = `${newY}px`;
            };

            const mouseUpHandler = () => {
                isDragging = false;
                storage.headerPosition = { x: parseInt(tableHeader.style.left || 0), y: parseInt(tableHeader.style.top || 0) };
                StorageManager.setObject('headerPosition', storage.headerPosition);
                document.removeEventListener('mousemove', mouseMoveHandler);
                document.removeEventListener('mouseup', mouseUpHandler);
            };

            tableHeader.addEventListener('mousedown', mouseDownHandler);
            tableHeader._dragHandler = mouseDownHandler;
        },

        // Deshabilitar drag del header
        disableHeaderDragging: () => {
            const tableHeader = document.getElementById('tableHeader');
            if (!tableHeader) return;

            tableHeader.style.position = '';
            tableHeader.style.left = '';
            tableHeader.style.top = '';
            tableHeader.style.cursor = '';
            tableHeader.style.zIndex = '';

            if (tableHeader._dragHandler) {
                tableHeader.removeEventListener('mousedown', tableHeader._dragHandler);
                delete tableHeader._dragHandler;
            }
        }
    };
})();

globalThis.DragManager = DragManager;
