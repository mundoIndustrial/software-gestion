/**
 * Sistema de Colores Condicionales para Filas
 * Aplica clases CSS a las filas según el estado y otras condiciones
 */

/**
 * Aplicar colores condicionales a una fila
 * Lógica:
 * 1. Si es Entregado o Anulada -> aplicar color de estado (ignora días)
 * 2. Si NO es Entregado o Anulada -> verificar días totales
 *    - Si días totales >= 5 y <= 9 -> amarillo claro
 */
function applyRowConditionalColors(row) {
    if (!row) return;
    
    // Obtener el dropdown de estado
    const estadoSelect = row.querySelector('.estado-dropdown');
    if (!estadoSelect) return;
    
    const estado = estadoSelect.value;
    
    // Obtener el valor de días totales
    const diasCell = row.querySelector('[data-column="total_dias"]');
    let diasTotales = 0;
    
    if (diasCell) {
        const diasText = diasCell.textContent.trim();
        diasTotales = parseInt(diasText);
        console.log('[COLORS] Encontrado por data-column:', diasText);
    } else {
        // Si no encuentra el atributo data-column, buscar por posición (4ta celda - Total de días)
        const cells = row.querySelectorAll('.table-cell');
        if (cells[3]) { // 4ta celda (índice 3) = Total de días
            const diasText = cells[3].textContent.trim();
            diasTotales = parseInt(diasText);
            console.log('[COLORS] Encontrado por posición (4ta celda):', diasText, 'celdas totales:', cells.length);
        } else {
            console.log('[COLORS] No se encontró celda de días, celdas disponibles:', cells.length);
        }
    }
    
    // LOG: Información de diagnóstico
    console.log('[COLORS] Analizando fila:', {
        ordenId: row.getAttribute('data-orden-id'),
        estado: estado,
        diasText: diasCell ? diasCell.textContent.trim() : 'no encontrado',
        diasTotales: diasTotales,
        clasesAntes: row.className
    });
    
    // Remover todas las clases de color
    row.classList.remove('status-entregado', 'status-en-ejecucion', 'status-no-iniciado', 'status-anulada', 'dias-5-9', 'dias-10-15', 'dias-mayor-15');
    
    // Prioridad 1: Si es Entregado o Anulada, aplicar ese color sin importar días
    if (estado === 'Entregado') {
        row.classList.add('status-entregado');
        console.log('[COLORS] Aplicado status-entregado para orden:', row.getAttribute('data-orden-id'));
        return;
    }
    
    if (estado === 'Anulada') {
        row.classList.add('status-anulada');
        console.log('[COLORS] Aplicado status-anulada para orden:', row.getAttribute('data-orden-id'));
        return;
    }
    
    // Prioridad 2: Si NO es Entregado o Anulada, verificar días totales
    let claseAplicada = null;
    
    // Aplicar color según rango de días
    if (diasTotales >= 5 && diasTotales <= 9) {
        row.classList.add('dias-5-9');
        claseAplicada = 'dias-5-9';

    } else if (diasTotales >= 10 && diasTotales <= 13) {
        row.classList.add('dias-10-15');
        claseAplicada = 'dias-10-15';

    } else if (diasTotales >= 14) {
        row.classList.add('dias-mayor-15');
        claseAplicada = 'dias-mayor-15';

    } else {
        // 0-4 días: sin clase
        claseAplicada = 'ninguna';
    }
    
    // LOG: Resultado final
    console.log('[COLORS] Resultado para orden', row.getAttribute('data-orden-id'), ':', {
        diasTotales: diasTotales,
        claseAplicada: claseAplicada,
        clasesDespues: row.className
    });
}

/**
 * Aplicar colores condicionales a todas las filas
 */
function applyAllRowConditionalColors() {
    const rows = document.querySelectorAll('.table-row');
    rows.forEach(row => {
        applyRowConditionalColors(row);
    });

}

/**
 * Inicializar listeners para cambios de estado
 */
function initializeStatusChangeListeners() {
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('estado-dropdown')) {
            // Encontrar la fila padre
            const row = e.target.closest('.table-row');
            if (row) {
                applyRowConditionalColors(row);

            }
        }
    });
}

/**
 * Inicializar cuando el DOM está listo
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log('[COLORS] Inicializando sistema de colores...');
    
    // Forzar limpieza de clases existentes con un pequeño retraso
    setTimeout(() => {
        const rows = document.querySelectorAll('.table-row');
        console.log('[COLORS] Encontradas', rows.length, 'filas para procesar');
        
        rows.forEach((row, index) => {
            // LOG: Estado antes de limpiar
            const clasesAntes = row.className;
            
            // Remover TODAS las clases de color de forma agresiva
            row.classList.remove('status-entregado', 'status-en-ejecucion', 'status-no-iniciado', 'status-anulada', 'dias-5-9', 'dias-10-15', 'dias-mayor-15');
            
            // Forzar limpieza de atributos de clase si persisten
            if (row.className.includes('dias-')) {
                const clasesOriginales = row.className;
                row.className = row.className.replace(/dias-\S+/g, '').trim();
                console.log('[COLORS] Fila', index, '- Limpieza regex:', {
                    ordenId: row.getAttribute('data-orden-id'),
                    clasesOriginales: clasesOriginales,
                    clasesLimpias: row.className
                });
            }
            
            // LOG: Estado después de limpiar
            console.log('[COLORS] Fila', index, '- Después de limpiar:', {
                ordenId: row.getAttribute('data-orden-id'),
                clasesAntes: clasesAntes,
                clasesDespues: row.className
            });
        });
        
        // Aplicar nueva lógica de colores
        console.log('[COLORS] Aplicando nueva lógica de colores...');
        applyAllRowConditionalColors();
        
        console.log('[COLORS] Proceso completado:', rows.length, 'filas procesadas');
    }, 100); // Pequeño retraso para asegurar que el DOM esté listo
    
    initializeStatusChangeListeners();
});

/**
 * Aplicar colores cuando se renderiza la tabla con filtros
 * Esta función se llamará después de renderizar filas dinámicamente
 */
function updateRowConditionalColors() {
    applyAllRowConditionalColors();
}
