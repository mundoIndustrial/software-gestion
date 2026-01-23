/**
 * Módulo de Ausencias - Asistencia Personal
 * Gestión de modal de ausencias y personal inasistente
 */

const AsistenciaAbsencias = (() => {
    /**
     * Cargar y mostrar ausencias del día
     */
    function cargar(reportId) {
        const url = `/asistencia-personal/reportes/${reportId}/ausencias`;

        
        fetch(url, {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarModal(data);
            } else {
                alert('Error al cargar ausencias: ' + (data.message || 'Error desconocido'));
            }
        })
        .catch(error => {

            alert('Error al cargar las ausencias: ' + error.message);
        });
    }

    /**
     * Mostrar modal de ausencias
     */
    function mostrarModal(data) {
        const absenciasModal = document.getElementById('absenciasModal');
        const ausenciasTableBody = document.getElementById('ausenciasTableBody');
        
        if (!absenciasModal || !ausenciasTableBody) {

            return;
        }
        
        ausenciasTableBody.innerHTML = '';
        
        if (data.ausencias && data.ausencias.length > 0) {
            data.ausencias.forEach(ausencia => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${ausencia.nombre}</td>
                    <td style="text-align: center;">${ausencia.id}</td>
                    <td style="text-align: center;">${ausencia.total_inasistencias}</td>
                    <td style="text-align: center;">
                        <button class="btn-ver-inasistencias" data-nombre="${ausencia.nombre}" data-fechas='${JSON.stringify(ausencia.fechas_inasistidas)}'>
                            Ver fechas
                        </button>
                    </td>
                `;
                ausenciasTableBody.appendChild(row);
            });
            
            document.querySelectorAll('.btn-ver-inasistencias').forEach(btn => {
                btn.addEventListener('click', function() {
                    const nombre = this.getAttribute('data-nombre');
                    const fechas = JSON.parse(this.getAttribute('data-fechas'));
                    mostrarFechasInasistencias(nombre, fechas);
                });
            });
        } else {
            const row = document.createElement('tr');
            row.innerHTML = '<td colspan="4" class="empty-cell">No hay ausencias registradas</td>';
            ausenciasTableBody.appendChild(row);
        }
        
        absenciasModal.style.display = 'block';
        
        agregarBotonVolver();
        
        const btnClose = document.getElementById('btnCloseAbsencias');
        if (btnClose) {
            btnClose.addEventListener('click', function() {
                absenciasModal.style.display = 'none';
            });
        }
    }

    /**
     * Mostrar modal con las fechas de inasistencias
     */
    function mostrarFechasInasistencias(nombre, fechas) {
        const verInasistenciasModal = document.getElementById('verInasistenciasModal');
        const inasistenciasTitle = document.getElementById('inasistenciasTitle');
        const inasistenciasList = document.getElementById('inasistenciasList');
        
        if (!verInasistenciasModal || !inasistenciasTitle || !inasistenciasList) {

            return;
        }
        
        inasistenciasTitle.textContent = `Fechas de inasistencia - ${nombre}`;
        
        inasistenciasList.innerHTML = '';
        
        const container = document.createElement('div');
        container.style.display = 'flex';
        container.style.flexDirection = 'column';
        container.style.gap = '10px';
        
        if (fechas && fechas.length > 0) {
            const ul = document.createElement('ul');
            ul.style.listStyle = 'none';
            ul.style.padding = '0';
            ul.style.margin = '0 0 20px 0';
            
            fechas.forEach(fecha => {
                const li = document.createElement('li');
                li.style.padding = '12px 15px';
                li.style.borderBottom = '1px solid #eee';
                li.style.color = '#2c3e50';
                li.style.fontSize = '0.95rem';
                li.textContent = fecha;
                ul.appendChild(li);
            });
            
            container.appendChild(ul);
        } else {
            const p = document.createElement('p');
            p.style.textAlign = 'center';
            p.style.padding = '20px';
            p.textContent = 'No hay fechas registradas';
            container.appendChild(p);
        }
        
        const botonesDiv = document.createElement('div');
        botonesDiv.style.display = 'flex';
        botonesDiv.style.gap = '10px';
        botonesDiv.style.marginTop = '20px';
        botonesDiv.style.justifyContent = 'center';
        
        const btnCerrar = document.createElement('button');
        btnCerrar.className = 'btn-cerrar-inasistencias';
        btnCerrar.textContent = 'Cerrar';
        btnCerrar.style.padding = '10px 25px';
        btnCerrar.style.fontSize = '0.9rem';
        btnCerrar.style.border = 'none';
        btnCerrar.style.backgroundColor = '#dc3545';
        btnCerrar.style.color = 'white';
        btnCerrar.style.borderRadius = '6px';
        btnCerrar.style.cursor = 'pointer';
        btnCerrar.style.fontWeight = '500';
        btnCerrar.style.transition = 'all 0.3s ease';
        
        btnCerrar.addEventListener('mouseover', function() {
            this.style.backgroundColor = '#c82333';
            this.style.transform = 'translateY(-2px)';
            this.style.boxShadow = '0 4px 12px rgba(220, 53, 69, 0.3)';
        });
        
        btnCerrar.addEventListener('mouseout', function() {
            this.style.backgroundColor = '#dc3545';
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = 'none';
        });
        
        btnCerrar.addEventListener('click', function() {
            verInasistenciasModal.style.display = 'none';
        });
        
        botonesDiv.appendChild(btnCerrar);
        container.appendChild(botonesDiv);
        inasistenciasList.appendChild(container);
        
        // Asegurar que el modal esté centrado
        verInasistenciasModal.style.display = 'flex';
        verInasistenciasModal.style.position = 'fixed';
        verInasistenciasModal.style.top = '0';
        verInasistenciasModal.style.left = '0';
        verInasistenciasModal.style.width = '100%';
        verInasistenciasModal.style.height = '100%';
        verInasistenciasModal.style.backgroundColor = 'rgba(0, 0, 0, 0.5)';
        verInasistenciasModal.style.zIndex = '10000';
        verInasistenciasModal.style.alignItems = 'center';
        verInasistenciasModal.style.justifyContent = 'center';
        
        const btnClose = document.getElementById('btnCloseVerInasistencias');
        if (btnClose) {
            btnClose.onclick = function() {
                verInasistenciasModal.style.display = 'none';
            };
        }
    }

    /**
     * Agregar botón Volver encima del header
     */
    function agregarBotonVolver() {
        let btnVolverDiv = document.getElementById('btnVolverAbsenciasDiv');
        if (!btnVolverDiv) {
            btnVolverDiv = document.createElement('div');
            btnVolverDiv.id = 'btnVolverAbsenciasDiv';
            btnVolverDiv.style.marginBottom = '15px';
            btnVolverDiv.style.display = 'flex';
            btnVolverDiv.style.justifyContent = 'flex-start';
            
            const btnVolver = document.createElement('button');
            btnVolver.id = 'btnVolverAbsencias';
            btnVolver.textContent = '← Volver';
            btnVolver.style.padding = '10px 20px';
            btnVolver.style.fontSize = '0.9rem';
            btnVolver.style.border = '1px solid #6c757d';
            btnVolver.style.backgroundColor = 'white';
            btnVolver.style.color = '#6c757d';
            btnVolver.style.borderRadius = '6px';
            btnVolver.style.cursor = 'pointer';
            btnVolver.style.fontWeight = '500';
            btnVolver.style.transition = 'all 0.3s ease';
            
            btnVolver.addEventListener('mouseover', function() {
                this.style.backgroundColor = '#6c757d';
                this.style.color = 'white';
            });
            
            btnVolver.addEventListener('mouseout', function() {
                this.style.backgroundColor = 'white';
                this.style.color = '#6c757d';
            });
            
            btnVolver.addEventListener('click', function() {
                const absenciasModal = document.getElementById('absenciasModal');
                absenciasModal.style.display = 'none';
            });
            
            btnVolverDiv.appendChild(btnVolver);
            const tableWrapper = document.querySelector('.ausencias-table-wrapper');
            if (tableWrapper && tableWrapper.parentNode) {
                tableWrapper.parentNode.insertBefore(btnVolverDiv, tableWrapper);
            }
        }
    }

    return {
        cargar,
        mostrarModal,
        mostrarFechasInasistencias
    };
})();
