/**
 * TableRenderer
 * Responsabilidad: Renderizar tabla, filas y celdas
 * SOLID: Single Responsibility
 */
const TableRenderer = (() => {
    return {
        // Crear celda con contenido
        createCell: (key, value, orden) => {
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
                select.dataset.id = orden.pedido || orden.id;
                select.dataset.value = value || '';
                content.appendChild(select);
            } else if (key === 'dia_de_entrega' && globalThis.modalContext === 'orden') {
                const select = document.createElement('select');
                select.className = 'dia-entrega-dropdown';
                select.dataset.id = orden.pedido || orden.id;
                select.dataset.value = value || '';
                content.appendChild(select);
            } else {
                const cellText = document.createElement('span');
                cellText.className = 'cell-text';
                cellText.textContent = value || '';
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
        updateTableWithData: (orders, totalDiasCalculados) => {
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
                const pedidoKey = orden.pedido || orden.id;
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
                accionesTd.style.minWidth = '200px';
                const accionesDiv = document.createElement('div');
                accionesDiv.className = 'cell-content';
                accionesDiv.style.cssText = 'display: flex; gap: 4px; flex-wrap: wrap;';
                accionesDiv.innerHTML = `
                    <button class="action-btn edit-btn" onclick="openEditModal(${pedidoKey})" title="Editar orden" style="background: linear-gradient(135deg, #3b82f6 0%, #6366f1 100%); color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer; font-size: 12px; font-weight: 600;">Editar</button>
                    <button class="action-btn detail-btn" onclick="createViewButtonDropdown(${pedidoKey})" title="Ver opciones" style="background-color: green; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer; font-size: 12px; font-weight: 600;">Ver</button>
                    <button class="action-btn delete-btn" onclick="deleteOrder(${pedidoKey})" title="Eliminar orden" style="background-color:#f84c4cff ; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer; font-size: 12px; font-weight: 600;">Borrar</button>
                `;
                accionesTd.appendChild(accionesDiv);
                row.appendChild(accionesTd);

                const theadRow = document.querySelector('#tablaOrdenes thead tr');
                const ths = Array.from(theadRow.querySelectorAll('th'));
                
                for (let i = 1; i < ths.length; i++) {
                    const key = ths[i].dataset.column || ths[i].querySelector('.header-text')?.textContent.trim().toLowerCase().replace(/\s+/g, '_') || '';
                    const value = orden[key] || '';
                    row.appendChild(TableRenderer.createCell(key, value, orden));
                }

                tbody.appendChild(row);
            });
        }
    };
})();

globalThis.TableRenderer = TableRenderer;
