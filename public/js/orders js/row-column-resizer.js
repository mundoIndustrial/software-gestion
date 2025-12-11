// ========================================
// ROW COLUMN RESIZER
// Redimensionamiento independiente de columnas en filas
// ========================================

const RowColumnResizer = (() => {
    let isResizing = false;
    let currentColumn = null;
    let startX = 0;
    let startWidth = 0;
    let styleSheet = null;
    let pendingChanges = {};

    // Crear o obtener hoja de estilos dinámica
    const getStyleSheet = () => {
        if (styleSheet) return styleSheet;
        
        const style = document.createElement('style');
        style.id = 'row-column-widths-style';
        style.type = 'text/css';
        document.head.appendChild(style);
        styleSheet = style.sheet;
        return styleSheet;
    };

    // Guardar cambios en el servidor
    const saveToServer = (widths) => {
        console.log('📤 Enviando anchos al servidor:', widths);
        
        fetch('/api/save-column-widths', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: JSON.stringify({ widths })
        })
        .then(response => {
            console.log('📨 Respuesta del servidor:', response.status);
            if (!response.ok) {
                return response.text().then(text => {
                    throw new Error(`HTTP ${response.status}: ${text}`);
                });
            }
            return response.json();
        })
        .then(data => {
            console.log('📦 Datos recibidos:', data);
            if (data.success) {
                console.log('✅ Cambios guardados en el servidor correctamente');
                console.log('📝 Bytes escritos:', data.bytes_written);
                console.log('🔍 Verificado en servidor:', data.verified);
            } else {
                console.error('❌ Error en respuesta:', data.message);
            }
        })
        .catch(error => {
            console.error('❌ Error guardando cambios:', error);
        });
    };

    // Aplicar reglas CSS dinámicas
    const applyStyleRules = (widths) => {
        const sheet = getStyleSheet();
        
        // Limpiar reglas anteriores
        while (sheet.cssRules.length > 0) {
            sheet.deleteRule(0);
        }
        
        // Crear nuevas reglas CSS para cada columna
        Object.keys(widths).forEach((key, index) => {
            const width = widths[key];
            const rule = `.table-row .table-cell:nth-child(${index + 1}) {
                min-width: ${width}px !important;
                max-width: ${width}px !important;
                width: ${width}px !important;
                flex: 0 0 ${width}px !important;
            }`;
            try {
                sheet.insertRule(rule, sheet.cssRules.length);
            } catch (e) {
                console.error('Error inserting CSS rule:', e);
            }
        });
    };

    // Cargar anchos guardados desde el servidor
    const applySavedWidths = () => {
        fetch('/api/get-column-widths')
            .then(response => response.json())
            .then(data => {
                if (data.widths && Object.keys(data.widths).length > 0) {
                    console.log('📥 Anchos cargados del servidor:', data.widths);
                    applyStyleRules(data.widths);
                }
            })
            .catch(error => console.error('Error cargando anchos:', error));
    };

    // Crear handle de redimensionamiento para filas
    const createResizeHandle = (cell, columnIndex) => {
        const handle = document.createElement('div');
        handle.className = 'row-column-resize-handle';
        
        handle.addEventListener('mouseenter', () => {
            handle.style.background = 'rgba(37, 99, 235, 0.6)';
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
            currentColumn = columnIndex;
            startX = e.clientX;
            startWidth = cell.offsetWidth;
            handle.style.background = 'rgba(37, 99, 235, 0.8)';
            document.body.style.cursor = 'col-resize';
            document.body.style.userSelect = 'none';
        });

        return handle;
    };

    // Inicializar redimensionamiento para filas
    const init = () => {
        const tableRows = document.querySelectorAll('.table-row');
        if (tableRows.length === 0) return;

        // Aplicar anchos guardados
        applySavedWidths();

        // Agregar handles a cada celda de cada fila
        tableRows.forEach(row => {
            const cells = row.querySelectorAll('.table-cell');
            cells.forEach((cell, index) => {
                cell.style.position = 'relative';
                const handle = createResizeHandle(cell, index);
                cell.appendChild(handle);
            });
        });

        // Event listeners globales
        document.addEventListener('mousemove', handleMouseMove);
        document.addEventListener('mouseup', handleMouseUp);
    };

    // Manejar movimiento del mouse
    const handleMouseMove = (e) => {
        if (!isResizing || currentColumn === null) return;

        const diff = e.clientX - startX;
        const newWidth = Math.max(50, startWidth + diff); // Mínimo 50px

        // Aplicar regla CSS dinámica en tiempo real
        const sheet = getStyleSheet();
        const columnIndex = currentColumn + 1;
        
        // Limpiar reglas anteriores para esta columna
        const rulesToDelete = [];
        for (let i = 0; i < sheet.cssRules.length; i++) {
            const rule = sheet.cssRules[i];
            if (rule.selectorText && rule.selectorText.includes(`.table-cell:nth-child(${columnIndex})`)) {
                rulesToDelete.push(i);
            }
        }
        
        // Eliminar reglas en orden inverso para no afectar los índices
        rulesToDelete.reverse().forEach(index => {
            sheet.deleteRule(index);
        });
        
        // Insertar nueva regla CSS
        const rule = `.table-row .table-cell:nth-child(${columnIndex}) {
            min-width: ${newWidth}px !important;
            max-width: ${newWidth}px !important;
            width: ${newWidth}px !important;
            flex: 0 0 ${newWidth}px !important;
        }`;
        
        try {
            sheet.insertRule(rule, sheet.cssRules.length);
        } catch (e) {
            console.error('Error inserting CSS rule:', e);
        }
    };

    // Manejar fin del redimensionamiento
    const handleMouseUp = () => {
        if (!isResizing) return;

        isResizing = false;
        document.body.style.cursor = 'auto';
        document.body.style.userSelect = 'auto';

        // Guardar anchos desde los estilos CSS reales
        const sheet = getStyleSheet();
        const widths = {};
        
        // Extraer anchos de las reglas CSS
        for (let i = 0; i < sheet.cssRules.length; i++) {
            const rule = sheet.cssRules[i];
            if (rule.selectorText && rule.selectorText.includes('.table-cell:nth-child')) {
                // Extraer el índice de la columna del selector
                const match = rule.selectorText.match(/nth-child\((\d+)\)/);
                if (match) {
                    const columnIndex = parseInt(match[1]) - 1;
                    // Extraer el ancho de la propiedad width
                    const widthMatch = rule.style.width.match(/(\d+)px/);
                    if (widthMatch) {
                        widths[`col_${columnIndex}`] = parseInt(widthMatch[1]);
                    }
                }
            }
        }

        // Guardar en el servidor
        if (Object.keys(widths).length > 0) {
            saveToServer(widths);
        }

        // Resetear color de los handles
        const handles = document.querySelectorAll('.row-column-resize-handle');
        handles.forEach(handle => {
            handle.style.background = 'transparent';
        });

        currentColumn = null;
    };

    return {
        init,
        applySavedWidths
    };
})();

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    RowColumnResizer.init();
});
