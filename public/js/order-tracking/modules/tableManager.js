/**
 * Módulo: TableManager
 * Responsabilidad: Gestionar actualización de días en la tabla
 * Principio SOLID: Single Responsibility
 */

const TableManager = (() => {
    /**
     * Obtiene la tabla de órdenes
     */
    function getOrdersTable() {
        return document.getElementById('tablaOrdenes');
    }
    
    /**
     * Obtiene todas las filas de la tabla
     */
    function getTableRows() {
        const tabla = getOrdersTable();
        if (!tabla) return [];
        
        const tbody = tabla.querySelector('tbody');
        if (!tbody) return [];
        
        return Array.from(tbody.querySelectorAll('tr[data-numero-pedido]'));
    }
    
    /**
     * Actualiza los días en la tabla desde los atributos data-total-dias
     */
    function updateDaysInTable() {
        const rows = getTableRows();
        
        if (rows.length === 0) {

            return;
        }
        
        let actualizadas = 0;
        
        rows.forEach(fila => {
            const numeroPedido = fila.getAttribute('data-numero-pedido');
            const diasDelHTML = fila.getAttribute('data-total-dias');
            
            if (diasDelHTML !== null && diasDelHTML !== '') {
                const celdas = fila.querySelectorAll('td[data-column="total_de_dias_"]');
                
                celdas.forEach(celdaTotal => {
                    let spanDias = celdaTotal.querySelector('.dias-value');
                    
                    if (!spanDias) {
                        spanDias = celdaTotal.querySelector('.cell-text');
                    }
                    
                    if (spanDias) {
                        const textoAnterior = spanDias.textContent.trim();
                        spanDias.textContent = String(diasDelHTML);
                        
                        if (textoAnterior !== String(diasDelHTML)) {

                            actualizadas++;
                        }
                    }
                });
            }
        });
        

    }
    
    /**
     * Actualiza los días cuando cambia de página
     */
    function updateDaysOnPageChange() {

        
        setTimeout(() => {
            updateDaysInTable();
        }, 200);
    }
    
    // Interfaz pública
    return {
        getOrdersTable,
        getTableRows,
        updateDaysInTable,
        updateDaysOnPageChange
    };
})();

globalThis.TableManager = TableManager;
