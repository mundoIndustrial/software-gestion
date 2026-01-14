/**
 * Módulo de Detalles del Reporte - Asistencia Personal
 * Gestión de tabs y visualización de registros por fecha
 */

const AsistenciaReportDetails = (() => {
    /**
     * Mostrar tab específico con registros de una fecha
     */
    function mostrarTab(fecha, registros) {
        console.log('Mostrando tab para fecha:', fecha, 'Registros:', registros);
        
        // Guardar registros originales para búsqueda
        AsistenciaBusqueda.setRegistrosOriginales(fecha, registros);
        
        // Si estamos en vista de horas trabajadas, actualizar esa vista
        if (AsistenciaBusqueda.getVistaHorasTrabajadas()) {
            const registrosProcesados = registros.map(registro => ({
                nombre: registro.nombre,
                codigo: registro.codigo_persona,
                horas: registro.horas && typeof registro.horas === 'object' ? Object.values(registro.horas) : []
            }));
            AsistenciaHorasTrabajadas.actualizarVista(registrosProcesados);
            
            // Recargar filtros al cambiar de tab en vista de horas
            setTimeout(() => {
                AsistenciaFiltros.aplicarFiltros();
                AsistenciaFiltros.actualizarIndicadoresVisuals();
                const totalFiltros = AsistenciaFiltros.obtenerTotalFiltros();
                if (totalFiltros > 0) {
                    AsistenciaFiltros.mostrarBotonLimpiarFiltros();
                }
            }, 100);
            return;
        }
        
        const recordsTableBody = document.getElementById('recordsTableBody');
        const recordsTableHeader = document.getElementById('recordsTableHeader');
        
        if (!recordsTableBody || !recordsTableHeader) {
            console.error('Elementos de tabla no encontrados');
            return;
        }
        
        // Calcular el número máximo de horas
        let maxHoras = 0;
        if (registros && registros.length > 0) {
            registros.forEach(registro => {
                if (registro.horas && typeof registro.horas === 'object') {
                    const numHoras = Object.keys(registro.horas).length;
                    maxHoras = Math.max(maxHoras, numHoras);
                }
            });
        }
        
        // Crear encabezados dinámicos
        let headerHTML = '<th>Persona</th><th>ID</th>';
        for (let i = 1; i <= maxHoras; i++) {
            headerHTML += `<th>Hora ${i}</th>`;
        }
        recordsTableHeader.innerHTML = headerHTML;
        
        // Limpiar tabla anterior
        recordsTableBody.innerHTML = '';
        
        // Renderizar tabla con registros
        renderRecordosTable(registros, maxHoras);
        
        // Inicializar búsqueda en tiempo real después de renderizar
        AsistenciaBusqueda.inicializarBusquedaNormal(fecha, maxHoras);
    }

    /**
     * Renderizar registros en la tabla
     */
    function renderRecordosTable(registros, maxHoras) {
        const recordsTableBody = document.getElementById('recordsTableBody');
        
        if (!recordsTableBody) return;
        
        recordsTableBody.innerHTML = '';
        
        if (registros && registros.length > 0) {
            registros.forEach(registro => {
                const row = document.createElement('tr');
                row.setAttribute('data-persona-id', registro.codigo_persona);
                row.setAttribute('data-persona-nombre', registro.nombre.toLowerCase());
                
                let rowHTML = `
                    <td>${registro.nombre}</td>
                    <td>${registro.codigo_persona}</td>
                `;
                
                if (registro.horas && typeof registro.horas === 'object') {
                    const horasArray = Object.values(registro.horas);
                    for (let i = 0; i < maxHoras; i++) {
                        const hora = horasArray[i] || '—';
                        rowHTML += `<td>${hora}</td>`;
                    }
                } else {
                    for (let i = 0; i < maxHoras; i++) {
                        rowHTML += '<td>—</td>';
                    }
                }
                
                row.innerHTML = rowHTML;
                recordsTableBody.appendChild(row);
            });
        } else {
            const row = document.createElement('tr');
            row.innerHTML = '<td colspan="' + (2 + maxHoras) + '" class="empty-cell">No hay registros para esta fecha</td>';
            recordsTableBody.appendChild(row);
        }
    }

    return {
        mostrarTab,
        renderRecordosTable
    };
})();
