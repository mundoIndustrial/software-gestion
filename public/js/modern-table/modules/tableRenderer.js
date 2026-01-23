/**
 * TableRenderer
 * Responsabilidad: Renderizar tabla, filas y celdas
 * SOLID: Single Responsibility
 */
const TableRenderer = (() => {
    return {
        // Crear celda con contenido
        createCell: (key, value, orden, totalDiasCalculados = {}, areaOptions = []) => {
            const td = document.createElement('td');
            td.className = 'table-cell';
            td.dataset.column = key;
            
            if (key === 'descripcion_prendas' || key === 'novedades') {
                td.style.minWidth = '400px';
                td.style.maxWidth = '600px';
                td.style.whiteSpace = 'normal';
                td.style.wordWrap = 'break-word';
                td.style.overflowWrap = 'break-word';
            }

            const content = document.createElement('div');
            content.className = 'cell-content';
            content.title = value;

            if (key === 'estado' || key === 'area') {
                const select = document.createElement('select');
                select.className = `${key}-dropdown`;
                select.dataset.id = orden.numero_pedido || orden.pedido || orden.id;
                select.dataset.value = value || '';
                
                // Agregar opciones al dropdown
                if (key === 'estado') {
                    const estadoOptions = ['Entregado', 'En Ejecución', 'No iniciado', 'Anulada'];
                    estadoOptions.forEach(option => {
                        const opt = document.createElement('option');
                        opt.value = option;
                        opt.textContent = option;
                        if (option === value) opt.selected = true;
                        select.appendChild(opt);
                    });
                } else if (key === 'area' && areaOptions.length > 0) {
                    const emptyOpt = document.createElement('option');
                    emptyOpt.value = '';
                    emptyOpt.textContent = 'Seleccionar área';
                    select.appendChild(emptyOpt);
                    
                    areaOptions.forEach(option => {
                        const opt = document.createElement('option');
                        opt.value = option;
                        opt.textContent = option;
                        if (option === value) opt.selected = true;
                        select.appendChild(opt);
                    });
                }
                
                content.appendChild(select);
            } else if (key === 'dia_de_entrega' && globalThis.modalContext === 'orden') {
                const select = document.createElement('select');
                select.className = 'dia-entrega-dropdown';
                select.dataset.id = orden.numero_pedido || orden.pedido || orden.id;
                select.dataset.value = value || '';
                
                const diaOptions = ['', '15', '20', '25', '30'];
                const diaLabels = ['Seleccionar', '15 días', '20 días', '25 días', '30 días'];
                diaOptions.forEach((option, index) => {
                    const opt = document.createElement('option');
                    opt.value = option;
                    opt.textContent = diaLabels[index];
                    if (option === (value || '')) opt.selected = true;
                    select.appendChild(opt);
                });
                
                content.appendChild(select);
            } else {
                const cellText = document.createElement('span');
                cellText.className = 'cell-text';
                
                // Manejar total_de_dias_ especialmente
                if (key === 'total_de_dias_') {
                    const pedidoKey = orden.numero_pedido || orden.pedido || orden.id;
                    const totalDias = totalDiasCalculados[pedidoKey] || 0;
                    cellText.textContent = totalDias;
                    cellText.setAttribute('data-dias', totalDias);
                } else if (key === 'asesora') {
                    // Manejar asesora que puede ser un objeto o string
                    if (typeof value === 'object' && value !== null) {
                        cellText.textContent = value.name || value.nombre || '';
                    } else {
                        cellText.textContent = value || '';
                    }
                } else if (key === 'asesor') {
                    // Manejar asesor que viene del servidor
                    cellText.textContent = value || '';
                } else if (key === 'fecha_de_creacion_de_orden' || key === 'fecha_estimada_de_entrega') {
                    // Formatear fechas
                    if (value) {
                        try {
                            const date = new Date(value);
                            const day = String(date.getDate()).padStart(2, '0');
                            const month = String(date.getMonth() + 1).padStart(2, '0');
                            const year = date.getFullYear();
                            cellText.textContent = `${day}/${month}/${year}`;
                        } catch (e) {
                            cellText.textContent = value;
                        }
                    } else {
                        cellText.textContent = '';
                    }
                } else {
                    cellText.textContent = value || '';
                }
                
                content.appendChild(cellText);
            }

            td.appendChild(content);
            return td;
        },

        // Crear fila virtual
        createVirtualRow: (orden, globalIndex) => {
            const row = document.createElement('tr');
            row.className = 'table-row';
            row.dataset.orderId = orden.id;
            row.dataset.globalIndex = globalIndex;

            Object.entries(orden).forEach(([key, value]) => {
                row.appendChild(TableRenderer.createCell(key, value, orden));
            });

            return row;
        },

        // Renderizar filas virtuales
        renderVirtualRows: (allData, startIndex, endIndex, rowHeight, storage) => {
            const tbody = document.querySelector('#tablaOrdenes tbody');
            if (!tbody) return;

            tbody.innerHTML = '';
            const visibleData = allData.slice(startIndex, endIndex + 1);

            visibleData.forEach((orden, i) => {
                tbody.appendChild(TableRenderer.createVirtualRow(orden, startIndex + i));
            });

            tbody.style.transform = `translateY(${startIndex * rowHeight}px)`;
            tbody.style.height = `${allData.length * rowHeight}px`;
        },

        // Actualizar tabla con datos
        updateTableWithData: (orders, totalDiasCalculados, areaOptions = [], context = 'registros', userRole = null) => {
            const tbody = document.querySelector('#tablaOrdenes tbody');
            tbody.innerHTML = '';
            
            if (orders.length === 0) {
                tbody.innerHTML = `
                    <tr class="table-row">
                        <td colspan="51" class="no-results" style="text-align: center; padding: 20px; color: #6c757d;">
                            No hay resultados que coincidan con los filtros aplicados.
                        </td>
                    </tr>
                `;
                return;
            }

            orders.forEach(orden => {
                const row = document.createElement('tr');
                const pedidoKey = orden.numero_pedido || orden.pedido || orden.id;
                const totalDias = parseInt(totalDiasCalculados[pedidoKey] || 0);
                const estado = orden.estado || '';
                let conditionalClass = '';
                
                if (estado === 'Entregado') {
                    conditionalClass = 'row-delivered';
                } else if (totalDias > 30) {
                    conditionalClass = 'row-danger-light';
                } else if (totalDias > 20) {
                    conditionalClass = 'row-warning';
                }
                
                row.className = `table-row ${conditionalClass}`.trim();
                row.dataset.orderId = pedidoKey;

                const accionesTd = document.createElement('td');
                accionesTd.className = 'table-cell acciones-column';
                accionesTd.style.minWidth = '100px';
                const accionesDiv = document.createElement('div');
                accionesDiv.className = 'cell-content';
                accionesDiv.style.cssText = 'display: flex; gap: 6px; flex-wrap: nowrap; align-items: center; justify-content: center; padding: 4px 0;';
                
                // Determinar qué botones mostrar según contexto y rol
                let botonesHTML = '';
                if (context === 'registros') {
                    // Solo botón Ver
                    botonesHTML = `<button class="action-btn detail-btn" onclick="createViewButtonDropdown(${pedidoKey})" title="Ver opciones" style="background-color: #28a745; color: white; border: 1px solid #28a745; padding: 5px 10px; border-radius: 15px; cursor: pointer; font-size: 11px; font-weight: 600; flex: 0 0 auto; height: 36px; text-align: center; display: flex; align-items: center; justify-content: center; white-space: nowrap; transition: all 0.2s ease;"><i class="fas fa-eye" style="margin-right: 4px;"></i> Ver</button>`;
                } else if (userRole === 'supervisor') {
                    // Solo botón Ver
                    botonesHTML = `<button class="action-btn detail-btn" onclick="createViewButtonDropdown(${pedidoKey})" title="Ver opciones" style="background-color: #28a745; color: white; border: 1px solid #28a745; padding: 5px 10px; border-radius: 15px; cursor: pointer; font-size: 11px; font-weight: 600; flex: 0 0 auto; height: 36px; text-align: center; display: flex; align-items: center; justify-content: center; white-space: nowrap; transition: all 0.2s ease;"><i class="fas fa-eye" style="margin-right: 4px;"></i> Ver</button>`;
                } else {
                    // Botones Editar, Ver, Borrar
                    botonesHTML = `
                        <button class="action-btn edit-btn" onclick="openEditModal(${pedidoKey})" title="Editar orden" style="background-color: #007bff; color: white; border: 1px solid #007bff; padding: 5px 10px; border-radius: 15px; cursor: pointer; font-size: 11px; font-weight: 600; flex: 0 0 auto; height: 36px; text-align: center; display: flex; align-items: center; justify-content: center; white-space: nowrap; transition: all 0.2s ease;">Editar</button>
                        <button class="action-btn detail-btn" onclick="createViewButtonDropdown(${pedidoKey})" title="Ver opciones" style="background-color: #28a745; color: white; border: 1px solid #28a745; padding: 5px 10px; border-radius: 15px; cursor: pointer; font-size: 11px; font-weight: 600; flex: 0 0 auto; height: 36px; text-align: center; display: flex; align-items: center; justify-content: center; white-space: nowrap; transition: all 0.2s ease;">Ver</button>
                        <button class="action-btn delete-btn" onclick="deleteOrder(${pedidoKey})" title="Eliminar orden" style="background-color: #dc3545; color: white; border: 1px solid #dc3545; padding: 5px 10px; border-radius: 15px; cursor: pointer; font-size: 11px; font-weight: 600; flex: 0 0 auto; height: 36px; text-align: center; display: flex; align-items: center; justify-content: center; white-space: nowrap; transition: all 0.2s ease;">Borrar</button>
                    `;
                }
                
                accionesDiv.innerHTML = botonesHTML;
                accionesTd.appendChild(accionesDiv);
                row.appendChild(accionesTd);

                const theadRow = document.querySelector('#tablaOrdenes thead tr');
                const ths = Array.from(theadRow.querySelectorAll('th'));
                
                // Iterar sobre todos los encabezados excepto el primero (acciones)
                for (let i = 1; i < ths.length; i++) {
                    // Obtener el data-column directamente del encabezado
                    const key = ths[i].dataset.column;
                    
                    // Si no hay data-column, intentar extraerlo del texto
                    if (!key) {

                        continue;
                    }
                    
                    const value = orden[key] || '';
                    row.appendChild(TableRenderer.createCell(key, value, orden, totalDiasCalculados, areaOptions));
                }

                tbody.appendChild(row);
            });
        }
    };
})();

globalThis.TableRenderer = TableRenderer;
