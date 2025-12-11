// ========================================
// TABLE CONFIG MANAGER
// Maneja la configuración de la tabla de órdenes
// ========================================

const TableConfigManager = (() => {
    const CONFIG_KEY = 'tableConfig';
    const COLUMN_WIDTHS_KEY = 'columnWidths';
    const DEFAULT_CONFIG = {
        separatorsEnabled: false,
        headerWidth: 100
    };

    // Obtener configuración guardada
    const getConfig = () => {
        const saved = localStorage.getItem(CONFIG_KEY);
        return saved ? JSON.parse(saved) : DEFAULT_CONFIG;
    };

    // Guardar configuración
    const saveConfig = (config) => {
        localStorage.setItem(CONFIG_KEY, JSON.stringify(config));
    };

    // Obtener anchos de columnas guardados
    const getColumnWidths = () => {
        const saved = localStorage.getItem(COLUMN_WIDTHS_KEY);
        return saved ? JSON.parse(saved) : {};
    };

    // Guardar anchos de columnas
    const saveColumnWidths = (widths) => {
        localStorage.setItem(COLUMN_WIDTHS_KEY, JSON.stringify(widths));
    };

    // Aplicar configuración a la tabla
    const applyConfig = (config) => {
        const tableHead = document.querySelector('.table-head');
        const tableBody = document.querySelector('.table-body');
        const tableRows = document.querySelectorAll('.table-row');

        if (!tableHead) return;

        // Aplicar separadores al header
        const headerCells = tableHead.querySelectorAll('.table-header-cell');
        if (config.separatorsEnabled) {
            tableHead.classList.add('separators-enabled');
            if (tableBody) tableBody.classList.add('separators-enabled');
            
            // Aplicar separadores al header
            headerCells.forEach((cell, index) => {
                if (index < headerCells.length - 1) {
                    cell.style.borderRight = '1px solid var(--border-light)';
                } else {
                    cell.style.borderRight = 'none';
                }
            });
            
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
            
            // Remover separadores del header
            headerCells.forEach(cell => {
                cell.style.borderRight = 'none';
            });
            
            // Remover separadores de las filas
            tableRows.forEach(row => {
                const cells = row.querySelectorAll('.table-cell');
                cells.forEach(cell => {
                    cell.style.borderRight = 'none';
                });
            });
        }
    };

    // Inicializar el modal
    const initModal = () => {
        const config = getConfig();
        const separatorsToggle = document.getElementById('headerSeparatorsToggle');
        const widthSlider = document.getElementById('headerWidthSlider');
        const widthValue = document.getElementById('headerWidthValue');

        if (separatorsToggle) {
            separatorsToggle.checked = config.separatorsEnabled;
            separatorsToggle.addEventListener('change', (e) => {
                updateWidthDisplay();
            });
        }

        if (widthSlider) {
            widthSlider.value = config.headerWidth;
            updateWidthDisplay();
            widthSlider.addEventListener('input', updateWidthDisplay);
        }
    };

    // Actualizar display del ancho
    const updateWidthDisplay = () => {
        const widthSlider = document.getElementById('headerWidthSlider');
        const widthValue = document.getElementById('headerWidthValue');
        if (widthSlider && widthValue) {
            widthValue.textContent = widthSlider.value + '%';
        }
    };

    // Abrir modal
    const openModal = () => {
        const modal = document.getElementById('tableConfigModal');
        if (modal) {
            modal.style.display = 'flex';
            initModal();
        }
    };

    // Cerrar modal
    const closeModal = () => {
        const modal = document.getElementById('tableConfigModal');
        if (modal) {
            modal.style.display = 'none';
        }
    };

    // Aplicar configuración
    const apply = () => {
        const separatorsToggle = document.getElementById('headerSeparatorsToggle');
        const widthSlider = document.getElementById('headerWidthSlider');

        const config = {
            separatorsEnabled: separatorsToggle ? separatorsToggle.checked : false,
            headerWidth: widthSlider ? parseInt(widthSlider.value) : 100
        };

        saveConfig(config);
        applyConfig(config);
        
        // Sincronizar separadores con el header
        if (window.HeaderSeparatorsSync) {
            window.HeaderSeparatorsSync.syncSeparators(config);
        }
        
        closeModal();

        // Mostrar notificación
        showNotification('Configuración aplicada correctamente');
    };

    // Restablecer configuración
    const reset = () => {
        saveConfig(DEFAULT_CONFIG);
        applyConfig(DEFAULT_CONFIG);
        initModal();
        showNotification('Configuración restablecida');
    };

    // Mostrar notificación
    const showNotification = (message) => {
        const notification = document.createElement('div');
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: linear-gradient(135deg, #25a55f 0%, #1d8a4a 100%);
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            z-index: 10001;
            animation: slideInRight 0.3s ease-out;
            font-weight: 500;
        `;
        notification.textContent = message;
        document.body.appendChild(notification);

        setTimeout(() => {
            notification.style.animation = 'slideOutRight 0.3s ease-out';
            setTimeout(() => notification.remove(), 300);
        }, 2000);
    };

    // Inicializar al cargar
    const init = () => {
        const config = getConfig();
        applyConfig(config);

        // Agregar event listeners
        const configBtn = document.getElementById('tableConfigBtn');
        if (configBtn) {
            configBtn.addEventListener('click', openModal);
        }

        const modal = document.getElementById('tableConfigModal');
        if (modal) {
            const overlay = modal.querySelector('.table-config-overlay');
            if (overlay) {
                overlay.addEventListener('click', closeModal);
            }
        }
    };

    return {
        init,
        openModal,
        closeModal,
        apply,
        reset,
        getConfig,
        saveConfig,
        applyConfig
    };
})();

// Exponer funciones globales para el HTML
window.openTableConfigModal = () => TableConfigManager.openModal();
window.closeTableConfigModal = () => TableConfigManager.closeModal();
window.applyTableConfig = () => TableConfigManager.apply();
window.resetTableConfig = () => TableConfigManager.reset();

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    TableConfigManager.init();
});
