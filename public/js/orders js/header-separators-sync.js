// ========================================
// HEADER SEPARATORS SYNC
// Sincroniza separadores entre header y filas
// ========================================

const HeaderSeparatorsSync = (() => {
    const CONFIG_KEY = 'tableConfig';

    // Obtener configuración
    const getConfig = () => {
        const saved = localStorage.getItem(CONFIG_KEY);
        return saved ? JSON.parse(saved) : { separatorsEnabled: false };
    };

    // Aplicar separadores al header vacío
    const applySeparatorsToEmptyHeader = (config) => {
        const tableHead = document.getElementById('tableHead');
        if (!tableHead) return;

        const headerDiv = tableHead.querySelector('div');
        if (!headerDiv) return;

        if (config.separatorsEnabled) {
            tableHead.classList.add('separators-enabled');
            // Aplicar borde derecho al contenedor del header
            headerDiv.style.borderRight = '1px solid var(--border-light)';
        } else {
            tableHead.classList.remove('separators-enabled');
            headerDiv.style.borderRight = 'none';
        }
    };

    // Sincronizar separadores cuando se aplican cambios
    const syncSeparators = (config) => {
        const tableHead = document.getElementById('tableHead');
        const tableBody = document.querySelector('.table-body');
        const tableRows = document.querySelectorAll('.table-row');

        if (!tableHead) return;

        if (config.separatorsEnabled) {
            tableHead.classList.add('separators-enabled');
            if (tableBody) tableBody.classList.add('separators-enabled');

            // Aplicar separadores a las filas
            tableRows.forEach(row => {
                const cells = row.querySelectorAll('.table-cell');
                cells.forEach((cell, index) => {
                    if (index < cells.length - 1) {
                        cell.style.borderRight = '1px solid var(--border-light)';
                    } else {
                        cell.style.borderRight = 'none';
                    }
                });
            });
        } else {
            tableHead.classList.remove('separators-enabled');
            if (tableBody) tableBody.classList.remove('separators-enabled');

            // Remover separadores de las filas
            tableRows.forEach(row => {
                const cells = row.querySelectorAll('.table-cell');
                cells.forEach(cell => {
                    cell.style.borderRight = 'none';
                });
            });
        }
    };

    // Observar cambios en el localStorage
    const observeConfigChanges = () => {
        window.addEventListener('storage', (e) => {
            if (e.key === CONFIG_KEY) {
                const config = JSON.parse(e.newValue || '{}');
                syncSeparators(config);
            }
        });
    };

    // Inicializar
    const init = () => {
        const config = getConfig();
        applySeparatorsToEmptyHeader(config);
        syncSeparators(config);
        observeConfigChanges();
    };

    return {
        init,
        syncSeparators,
        applySeparatorsToEmptyHeader
    };
})();

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    HeaderSeparatorsSync.init();
});

// Exponer para que TableConfigManager pueda sincronizar
window.HeaderSeparatorsSync = HeaderSeparatorsSync;
