/**
 * StorageManager
 * Responsabilidad: Gestionar localStorage para la tabla
 * SOLID: Single Responsibility
 */
const StorageManager = (() => {
    const PREFIX = 'table_';

    return {
        get: (key) => {
            return localStorage.getItem(PREFIX + key);
        },

        set: (key, value) => {
            localStorage.setItem(PREFIX + key, value);
        },

        remove: (key) => {
            localStorage.removeItem(PREFIX + key);
        },

        getObject: (key) => {
            const value = localStorage.getItem(PREFIX + key);
            return value ? JSON.parse(value) : null;
        },

        setObject: (key, obj) => {
            localStorage.setItem(PREFIX + key, JSON.stringify(obj));
        },

        // Cargar todas las configuraciones guardadas
        loadSettings: () => {
            return {
                rowHeight: parseInt(StorageManager.get('rowHeight')) || 50,
                columnWidths: StorageManager.getObject('columnWidths') || {},
                tableWidth: StorageManager.get('tableWidth') ? parseInt(StorageManager.get('tableWidth')) : null,
                tableHeight: parseInt(StorageManager.get('tableHeight')) || null,
                tablePosition: StorageManager.getObject('tablePosition') || null,
                headerPosition: StorageManager.getObject('headerPosition') || null,
                moveTableEnabled: StorageManager.get('moveTableEnabled') === 'true',
                moveHeaderEnabled: StorageManager.get('moveHeaderEnabled') === 'true'
            };
        },

        saveSettings: (settings) => {
            StorageManager.set('rowHeight', settings.rowHeight);
            StorageManager.setObject('columnWidths', settings.columnWidths);
            if (settings.tableWidth) StorageManager.set('tableWidth', settings.tableWidth);
            if (settings.tableHeight) StorageManager.set('tableHeight', settings.tableHeight);
            if (settings.tablePosition) StorageManager.setObject('tablePosition', settings.tablePosition);
            if (settings.headerPosition) StorageManager.setObject('headerPosition', settings.headerPosition);
            StorageManager.set('moveTableEnabled', settings.moveTableEnabled);
            StorageManager.set('moveHeaderEnabled', settings.moveHeaderEnabled);
        }
    };
})();

globalThis.StorageManager = StorageManager;
