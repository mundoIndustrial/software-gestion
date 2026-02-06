/**
 * Módulo de Horas Trabajadas - Asistencia Personal
 * Gestión de vista y renderizado de horas trabajadas
 */

const AsistenciaHorasTrabajadas = (() => {
    /**
     * Mostrar vista de horas trabajadas
     */
    function mostrarVista() {
        AsistenciaBusqueda.setVistaHorasTrabajadas(true);
        
        // Asegurarse que existe la estructura HTML estándar
        const tabContent = document.getElementById('tabContent');
        if (tabContent) {
            // Limpiar y reconstruir si es necesario
            if (!document.getElementById('recordsTable')) {
                tabContent.innerHTML = `
                    <div class="records-table-wrapper">
                        <table class="records-table" id="recordsTable">
                            <thead>
                                <tr id="recordsTableHeader">
                                    <th>Persona</th>
                                </tr>
                            </thead>
                            <tbody id="recordsTableBody">
                            </tbody>
                        </table>
                    </div>
                `;
            }
        }
        
        const tabActivo = document.querySelector('.tab-button.active');
        const fechaActual = tabActivo ? tabActivo.getAttribute('data-fecha') : 'general';
        
        // Cargar filtros guardados
        const filtrosGuardados = AsistenciaFiltros.cargarFiltrosGuardados();
        
        actualizarVista();
        
        // Aplicar filtros guardados si existen
        if (filtrosGuardados.filtros && Object.keys(filtrosGuardados.filtros).length > 0) {
            setTimeout(() => {
                AsistenciaFiltros.aplicarFiltros();
                AsistenciaFiltros.actualizarIndicadoresVisuals();
                AsistenciaFiltros.mostrarBotonLimpiarFiltros();
            }, 100);
        }
        
        agregarBotonVolver();
        AsistenciaBusqueda.inicializarBusquedaHoras(fechaActual);
    }

    /**
     * Actualizar vista de horas trabajadas
     */
    function actualizarVista(registros = null, fechaExplicita = null) {
        const recordsTableBody = document.getElementById('recordsTableBody');
        const recordsTableHeader = document.getElementById('recordsTableHeader');
        
        if (!recordsTableBody || !recordsTableHeader) {

            return;
        }
        
        if (!registros) {
            registros = [];
            const rowsExistentes = document.querySelectorAll('#recordsTableBody tr');
            
            // Si no hay filas en la tabla, no proceder
            if (rowsExistentes.length === 0) {

                return;
            }
            
            rowsExistentes.forEach(row => {
                const cells = row.querySelectorAll('td');
                if (cells.length >= 2) {
                    const nombre = cells[0].textContent.trim();
                    const codigo = cells[1].textContent.trim();
                    const horas = [];
                    
                    for (let i = 2; i < cells.length; i++) {
                        const hora = cells[i].textContent.trim();
                        if (hora !== '—') {
                            horas.push(hora);
                        }
                    }
                    
                    registros.push({
                        nombre: nombre,
                        codigo: codigo,
                        horas: horas
                    });
                }
            });
        }
        
        let tabActivo = document.querySelector('.tab-button.active');
        let fechaActual = fechaExplicita || (tabActivo ? tabActivo.getAttribute('data-fecha') : 'general');
        
        
        // Crear encabezado con botones de filtro
        let headerHTML = `
            <th style="width: 30%; text-align: center;">Persona</th>
            <th style="width: 8%; text-align: center;">ID</th>
            <th style="width: 20%; text-align: center; position: relative;">
                <div style="display: flex; align-items: center; justify-content: center; gap: 4px;">
                    <span style="display: inline-block;">Total Horas Trabajadas</span>
                    <button type="button" onclick="AsistenciaFiltros.abrir('Total Horas Trabajadas')" 
                            style="background: none; border: none; color: inherit; cursor: pointer; padding: 2px; opacity: 0.7; transition: opacity 0.3s; flex-shrink: 0;" 
                            onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.7'" title="Filtrar">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 14px; height: 14px; display: block;">
                            <line x1="4" y1="6" x2="20" y2="6"></line>
                            <line x1="8" y1="12" x2="16" y2="12"></line>
                            <line x1="11" y1="18" x2="13" y2="18"></line>
                        </svg>
                    </button>
                </div>
            </th>
            <th style="width: 14%; text-align: center; position: relative;">
                <div style="display: flex; align-items: center; justify-content: center; gap: 4px;">
                    <span style="display: inline-block;">Hora Extra</span>
                    <button type="button" onclick="AsistenciaFiltros.abrir('Hora Extra')" 
                            style="background: none; border: none; color: inherit; cursor: pointer; padding: 2px; opacity: 0.7; transition: opacity 0.3s; flex-shrink: 0;" 
                            onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.7'" title="Filtrar">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 14px; height: 14px; display: block;">
                            <line x1="4" y1="6" x2="20" y2="6"></line>
                            <line x1="8" y1="12" x2="16" y2="12"></line>
                            <line x1="11" y1="18" x2="13" y2="18"></line>
                        </svg>
                    </button>
                </div>
            </th>
            <th style="width: 20%; text-align: center; position: relative;">
                <div style="display: flex; align-items: center; justify-content: center; gap: 4px;">
                    <span style="display: inline-block;">Total Horas Extra</span>
                    <button type="button" onclick="AsistenciaFiltros.abrir('Total Horas Extra')" 
                            style="background: none; border: none; color: inherit; cursor: pointer; padding: 2px; opacity: 0.7; transition: opacity 0.3s; flex-shrink: 0;" 
                            onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.7'" title="Filtrar">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 14px; height: 14px; display: block;">
                            <line x1="4" y1="6" x2="20" y2="6"></line>
                            <line x1="8" y1="12" x2="16" y2="12"></line>
                            <line x1="11" y1="18" x2="13" y2="18"></line>
                        </svg>
                    </button>
                </div>
            </th>
            <th style="width: 8%; text-align: center; position: relative;">
                <div style="display: flex; align-items: center; justify-content: center; gap: 4px;">
                    <span style="display: inline-block;">Estado</span>
                    <button type="button" onclick="AsistenciaFiltros.abrir('Estado')" 
                            style="background: none; border: none; color: inherit; cursor: pointer; padding: 2px; opacity: 0.7; transition: opacity 0.3s; flex-shrink: 0;" 
                            onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.7'" title="Filtrar">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 14px; height: 14px; display: block;">
                            <line x1="4" y1="6" x2="20" y2="6"></line>
                            <line x1="8" y1="12" x2="16" y2="12"></line>
                            <line x1="11" y1="18" x2="13" y2="18"></line>
                        </svg>
                    </button>
                </div>
            </th>
        `;
        recordsTableHeader.innerHTML = headerHTML;
        
        recordsTableBody.innerHTML = '';
        
        const horasTrabajadasData = [];
        
        registros.forEach(registro => {
            const row = document.createElement('tr');
            
            let calcResult = {
                horasTotales: '0:00:00',
                estado: 'sin_datos',
                observacion: 'Sin datos',
                registrosFaltantes: [],
                excepcion: false,
                esSabado: false
            };
            
            if (registro.horas && registro.horas.length > 0) {
                calcResult = AsistenciaUtilidades.calcularHorasTrabajadasAvanzado(registro.horas, fechaActual, registro.id_rol);
            }
            
            const totalMinutos = AsistenciaUtilidades.horaAMinutos(calcResult.horasTotales);
            const horaExtraResult = AsistenciaUtilidades.calcularHoraExtra(totalMinutos, calcResult.esSabado, registro.id_rol);
            
            // Para roles 19 y 20: verificar si realmente tiene horas extras completas (no solo minutos)
            let tieneHorasExtrasCompletas = horaExtraResult.tieneHoraExtra;
            let mostrarHoraExtra = horaExtraResult.horaExtra;
            let colorHoraExtra = horaExtraResult.tieneHoraExtra ? '#27ae60' : '#6c757d';
            
            // Aplicar lógica estricta solo para roles 19 y 20
            if (registro.id_rol === 19 || registro.id_rol === 20) {
                const minutosExtra = totalMinutos - (calcResult.esSabado ? 240 : 480); // 4h sábado = 240min, 8h entre semana = 480min
                tieneHorasExtrasCompletas = minutosExtra >= 60; // Solo si tiene al menos 1 hora completa
                colorHoraExtra = tieneHorasExtrasCompletas ? '#27ae60' : '#6c757d';
                mostrarHoraExtra = tieneHorasExtrasCompletas ? horaExtraResult.horaExtra : '0:00:00';
            }
            
            let colorEstado = '#6c757d';
            let iconoEstado = '';
            
            if (calcResult.estado === 'completa') {
                colorEstado = '#27ae60';
                iconoEstado = '✓';
            } else if (calcResult.estado === 'incompleta_excepcion') {
                colorEstado = '#f39c12';
                iconoEstado = '';
            } else if (calcResult.estado === 'incompleta') {
                colorEstado = '#e74c3c';
                iconoEstado = '✗';
            }
            
            row.setAttribute('data-persona-id', registro.codigo);
            row.setAttribute('data-persona-nombre', registro.nombre.toLowerCase());
            row.setAttribute('title', calcResult.observacion);
            
            row.innerHTML = `
                <td>${registro.nombre}</td>
                <td>${registro.codigo}</td>
                <td style="text-align: center; font-weight: 600; color: #27ae60;">${calcResult.horasTotales}</td>
                <td style="text-align: center; font-weight: 600; color: ${colorHoraExtra};">${tieneHorasExtrasCompletas ? 'Sí' : 'No'}</td>
                <td style="text-align: center; font-weight: 600; color: ${colorHoraExtra};">${mostrarHoraExtra}</td>
                <td style="text-align: center; font-weight: 600; color: ${colorEstado};" title="${calcResult.observacion}">
                    ${iconoEstado} ${calcResult.estado === 'incompleta_excepcion' ? 'Información Faltante' : calcResult.estado === 'completa' ? 'Completa' : calcResult.estado === 'sin_datos' ? 'Sin Datos' : 'Incompleta'}
                </td>
            `;
            
            recordsTableBody.appendChild(row);
            
            horasTrabajadasData.push({
                nombre: registro.nombre,
                codigo: registro.codigo,
                horasTotales: calcResult.horasTotales,
                tieneHoraExtra: horaExtraResult.tieneHoraExtra,
                horaExtra: horaExtraResult.horaExtra,
                estado: calcResult.estado,
                observacion: calcResult.observacion
            });
        });
        
        AsistenciaBusqueda.setHorasTrabajadasPorFecha(fechaActual, horasTrabajadasData);
        AsistenciaBusqueda.inicializarBusquedaHoras(fechaActual);
    }

    /**
     * Agregar botón Volver
     */
    function agregarBotonVolver() {
        let btnVolver = document.getElementById('btnVolverHoras');
        if (!btnVolver) {
            btnVolver = document.createElement('button');
            btnVolver.id = 'btnVolverHoras';
            btnVolver.type = 'button';
            btnVolver.className = 'btn btn-secondary';
            
            const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
            svg.setAttribute('viewBox', '0 0 24 24');
            svg.setAttribute('fill', 'none');
            svg.setAttribute('stroke', 'currentColor');
            svg.setAttribute('stroke-width', '2');
            svg.setAttribute('style', 'width: 18px; height: 18px;');
            
            const polyline = document.createElementNS('http://www.w3.org/2000/svg', 'polyline');
            polyline.setAttribute('points', '15 18 9 12 15 6');
            svg.appendChild(polyline);
            
            const span = document.createElement('span');
            span.textContent = 'Volver';
            
            btnVolver.appendChild(svg);
            btnVolver.appendChild(span);
            
            btnVolver.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                AsistenciaBusqueda.setVistaHorasTrabajadas(false);
                
                // Limpiar filtros al salir de la vista de horas
                AsistenciaFiltros.limpiarTodos();
                
                const searchInput = document.getElementById('searchInput');
                if (searchInput) {
                    searchInput.value = '';
                    searchInput.classList.remove('searching');
                }
                
                const tabActivo = document.querySelector('.tab-button.active');
                if (tabActivo) {
                    tabActivo.click();
                }
                
                if (btnVolver && btnVolver.parentNode) {
                    btnVolver.parentNode.removeChild(btnVolver);
                }
            });
            
            const modalControls = document.querySelector('.modal-controls');
            if (modalControls) {
                modalControls.appendChild(btnVolver);
            }
        }
    }

    return {
        mostrarVista,
        actualizarVista,
        agregarBotonVolver
    };
})();
