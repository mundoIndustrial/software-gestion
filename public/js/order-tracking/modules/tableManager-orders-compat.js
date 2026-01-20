/**
 * Módulo: TableManager - Compatibilidad para vista de órdenes con flexbox
 * Responsabilidad: Gestionar actualización de días en la tabla (compatible con flexbox)
 * Principio SOLID: Single Responsibility
 */

const TableManager = (() => {
    /**
     * Obtiene la tabla de órdenes (compatible con flexbox)
     */
    function getOrdersTable() {
        // Buscar primero la tabla HTML tradicional
        let tabla = document.getElementById('tablaOrdenes');
        if (tabla) return tabla;
        
        // Si no existe, buscar el contenedor de órdenes con flexbox
        tabla = document.getElementById('tablaOrdenesBody');
        if (tabla) return tabla;
        
        // Fallback: buscar cualquier elemento con clase table-body
        tabla = document.querySelector('.table-body');
        return tabla;
    }
    
    /**
     * Obtiene todas las filas de la tabla (compatible con flexbox)
     */
    function getTableRows() {
        const tabla = getOrdersTable();
        if (!tabla) {
            console.warn(' Tabla de órdenes no encontrada');
            return [];
        }
        
        // Buscar filas en tabla HTML tradicional
        let tbody = tabla.querySelector('tbody');
        if (tbody) {
            return Array.from(tbody.querySelectorAll('tr[data-numero-pedido]'));
        }
        
        // Buscar filas en estructura flexbox
        const rows = Array.from(tabla.querySelectorAll('.table-row[data-orden-id]'));
        return rows;
    }
    
    /**
     * Actualiza los días en la tabla desde los atributos data-total-dias
     */
    function updateDaysInTable() {
        const rows = getTableRows();
        
        if (rows.length === 0) {
            console.log('⏱️ No hay órdenes en la tabla actual');
            return;
        }
        
        console.log(` Actualizando días en ${rows.length} órdenes`);
        
        let actualizadas = 0;
        
        rows.forEach(fila => {
            // Obtener número de pedido (compatible con ambas estructuras)
            const numeroPedido = fila.getAttribute('data-numero-pedido') || fila.getAttribute('data-orden-id');
            
            if (!numeroPedido) return;
            
            // Para tabla HTML tradicional
            if (fila.tagName === 'TR') {
                const diasDelHTML = fila.getAttribute('data-total-dias');
                if (diasDelHTML !== null && diasDelHTML !== '') {
                    const celdas = fila.querySelectorAll('td[data-column="total_de_dias_"]');
                    celdas.forEach(celdaTotal => {
                        let spanDias = celdaTotal.querySelector('.dias-value');
                        if (!spanDias) {
                            spanDias = document.createElement('span');
                            spanDias.className = 'dias-value';
                            celdaTotal.appendChild(spanDias);
                        }
                        spanDias.textContent = diasDelHTML;
                        actualizadas++;
                    });
                }
            } else {
                // Para estructura flexbox (divs)
                // Los días se actualizan a través de otros mecanismos
                console.log(` Fila ${numeroPedido} procesada (flexbox)`);
            }
        });
        
        if (actualizadas > 0) {
            console.log(` ${actualizadas} celdas de días actualizadas`);
        }
    }
    
    /**
     * Actualiza los días cuando cambia de página
     */
    function updateDaysOnPageChange() {
        console.log('📄 Actualizando días al cambiar de página');
        updateDaysInTable();
    }
    
    // Interfaz pública
    return {
        getOrdersTable,
        getTableRows,
        updateDaysInTable,
        updateDaysOnPageChange
    };
})();

console.log(' TableManager (compatibilidad flexbox) cargado');
