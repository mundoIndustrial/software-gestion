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
    
    // Remover todas las clases de color
    row.classList.remove('status-entregado', 'status-en-ejecucion', 'status-no-iniciado', 'status-anulada', 'dias-5-9', 'dias-10-15', 'dias-mayor-15');
    
    // Prioridad 1: Si es Entregado o Anulada, aplicar ese color sin importar días
    if (estado === 'Entregado') {
        row.classList.add('status-entregado');
        console.log(`🔵 Fila marcada como Entregado (azul claro)`);
        return;
    }
    
    if (estado === 'Anulada') {
        row.classList.add('status-anulada');
        console.log(`🟤 Fila marcada como Anulada (marrón claro)`);
        return;
    }
    
    // Prioridad 2: Si NO es Entregado o Anulada, verificar días totales
    // Obtener el valor de días totales
    const diasCell = row.querySelector('[data-column="total_dias"]');
    let diasTotales = 0;
    
    if (diasCell) {
        const diasText = diasCell.textContent.trim();
        diasTotales = parseInt(diasText);
    } else {
        // Si no encuentra el atributo data-column, buscar por posición (5ta celda)
        const cells = row.querySelectorAll('.table-cell');
        if (cells[4]) {
            const diasText = cells[4].textContent.trim();
            diasTotales = parseInt(diasText);
        }
    }
    
    // Aplicar color según rango de días
    if (diasTotales >= 5 && diasTotales <= 9) {
        row.classList.add('dias-5-9');
        console.log(` Fila con ${diasTotales} días (amarillo claro)`);
    } else if (diasTotales >= 10 && diasTotales <= 15) {
        row.classList.add('dias-10-15');
        console.log(`🔴 Fila con ${diasTotales} días (rojo claro)`);
    } else if (diasTotales > 15) {
        row.classList.add('dias-mayor-15');
        console.log(`⚫ Fila con ${diasTotales} días (gris semi oscuro)`);
    } else {
        console.log(`⚪ Fila con ${diasTotales} días (sin color especial)`);
    }
}

/**
 * Aplicar colores condicionales a todas las filas
 */
function applyAllRowConditionalColors() {
    const rows = document.querySelectorAll('.table-row');
    rows.forEach(row => {
        applyRowConditionalColors(row);
    });
    console.log(' Colores condicionales aplicados a todas las filas');
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
                console.log(` Color de fila actualizado para estado: ${e.target.value}`);
            }
        }
    });
}

/**
 * Inicializar cuando el DOM está listo
 */
document.addEventListener('DOMContentLoaded', function() {
    applyAllRowConditionalColors();
    initializeStatusChangeListeners();
    console.log(' Sistema de colores condicionales inicializado');
});

/**
 * Aplicar colores cuando se renderiza la tabla con filtros
 * Esta función se llamará después de renderizar filas dinámicamente
 */
function updateRowConditionalColors() {
    applyAllRowConditionalColors();
}
