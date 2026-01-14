/**
 * Módulo de Búsqueda - Asistencia Personal
 * Gestión de búsqueda en tiempo real en tablas
 */

const AsistenciaBusqueda = (() => {
    let registrosOriginalesPorFecha = {};
    let horasTrabajadasPorFecha = {};
    let vistaHorasTrabajadas = false;

    /**
     * Inicializar búsqueda en tiempo real
     */
    function inicializarBusquedaNormal(fechaActual, maxHoras) {
        const searchInput = document.getElementById('searchInput');
        
        if (!searchInput) return;
        
        const newSearchInput = searchInput.cloneNode(true);
        searchInput.parentNode.replaceChild(newSearchInput, searchInput);
        
        const updatedSearchInput = document.getElementById('searchInput');
        if (updatedSearchInput) {
            updatedSearchInput.addEventListener('input', function(e) {
                const searchTerm = e.target.value.toLowerCase().trim();
                
                if (vistaHorasTrabajadas) {
                    const horasData = horasTrabajadasPorFecha[fechaActual] || [];
                    
                    let filtrados;
                    if (searchTerm === '') {
                        filtrados = horasData;
                        this.classList.remove('searching');
                    } else {
                        this.classList.add('searching');
                        filtrados = horasData.filter(registro => {
                            const codigoPersona = String(registro.codigo).toLowerCase();
                            const nombrePersona = String(registro.nombre).toLowerCase();
                            return codigoPersona.includes(searchTerm) || nombrePersona.includes(searchTerm);
                        });
                    }
                    
                    renderHorasTrabajadasSearchResults(filtrados, searchTerm, horasData.length);
                } else {
                    const registrosOriginales = registrosOriginalesPorFecha[fechaActual] || [];
                    
                    let registrosFiltrados;
                    
                    if (searchTerm === '') {
                        registrosFiltrados = registrosOriginales;
                        this.classList.remove('searching');
                    } else {
                        this.classList.add('searching');
                        registrosFiltrados = registrosOriginales.filter(registro => {
                            const codigoPersona = String(registro.codigo_persona).toLowerCase();
                            const nombrePersona = String(registro.nombre).toLowerCase();
                            return codigoPersona.includes(searchTerm) || nombrePersona.includes(searchTerm);
                        });
                    }
                    
                    console.log(`Búsqueda: "${searchTerm}" - ${registrosFiltrados.length} de ${registrosOriginales.length} registros`);
                    renderRecordosTableWithSearch(registrosFiltrados, maxHoras, registrosOriginales.length, searchTerm);
                }
            });
            
            updatedSearchInput.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    this.value = '';
                    this.classList.remove('searching');
                    this.dispatchEvent(new Event('input'));
                }
            });
        }
    }

    /**
     * Inicializar búsqueda para vista de horas trabajadas
     */
    function inicializarBusquedaHoras(fechaActual) {
        const searchInput = document.getElementById('searchInput');
        
        if (!searchInput) return;
        
        const newSearchInput = searchInput.cloneNode(true);
        searchInput.parentNode.replaceChild(newSearchInput, searchInput);
        
        const updatedSearchInput = document.getElementById('searchInput');
        if (updatedSearchInput) {
            updatedSearchInput.addEventListener('input', function(e) {
                const searchTerm = e.target.value.toLowerCase().trim();
                const horasData = horasTrabajadasPorFecha[fechaActual] || [];
                
                let filtrados;
                if (searchTerm === '') {
                    filtrados = horasData;
                    this.classList.remove('searching');
                } else {
                    this.classList.add('searching');
                    filtrados = horasData.filter(registro => {
                        const codigoPersona = String(registro.codigo).toLowerCase();
                        const nombrePersona = String(registro.nombre).toLowerCase();
                        return codigoPersona.includes(searchTerm) || nombrePersona.includes(searchTerm);
                    });
                }
                
                console.log(`Búsqueda en Horas: "${searchTerm}" - ${filtrados.length} de ${horasData.length} registros`);
                renderHorasTrabajadasSearchResults(filtrados, searchTerm, horasData.length);
            });
            
            updatedSearchInput.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    this.value = '';
                    this.classList.remove('searching');
                    this.dispatchEvent(new Event('input'));
                }
            });
        }
    }

    /**
     * Renderizar resultados de búsqueda para horas trabajadas
     */
    function renderHorasTrabajadasSearchResults(filtrados, searchTerm, total) {
        const recordsTableBody = document.getElementById('recordsTableBody');
        
        if (!recordsTableBody) return;
        
        recordsTableBody.innerHTML = '';
        
        if (filtrados.length === 0 && searchTerm !== '') {
            const row = document.createElement('tr');
            row.innerHTML = `<td colspan="6" class="empty-search-message">
                <div style="padding: 30px 0;">
                    <svg style="width: 48px; height: 48px; margin-bottom: 15px; opacity: 0.5;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"></circle>
                        <path d="m21 21-4.35-4.35"></path>
                    </svg>
                    <p>No se encontraron resultados para "<strong>${AsistenciaUtilidades.escapeHtml(searchTerm)}</strong>"</p>
                    <p style="font-size: 0.85rem; margin-top: 8px;">Intenta con otro número de persona o nombre</p>
                </div>
            </td>`;
            recordsTableBody.appendChild(row);
            return;
        }
        
        if (filtrados.length === 0) {
            const row = document.createElement('tr');
            row.innerHTML = '<td colspan="6" class="empty-cell">No hay registros para esta fecha</td>';
            recordsTableBody.appendChild(row);
            return;
        }
        
        filtrados.forEach(registro => {
            const row = document.createElement('tr');
            row.setAttribute('data-persona-id', registro.codigo);
            row.setAttribute('data-persona-nombre', registro.nombre.toLowerCase());
            row.setAttribute('title', registro.observacion);
            
            let nombreMostrado = registro.nombre;
            if (searchTerm !== '' && registro.nombre.toLowerCase().includes(searchTerm)) {
                nombreMostrado = registro.nombre.replace(
                    new RegExp(`(${AsistenciaUtilidades.escapeRegExp(searchTerm)})`, 'gi'),
                    '<mark>$1</mark>'
                );
            }
            
            let colorEstado = '#6c757d';
            let iconoEstado = '⚠️';
            
            if (registro.estado === 'completa') {
                colorEstado = '#27ae60';
                iconoEstado = '✓';
            } else if (registro.estado === 'incompleta_excepcion') {
                colorEstado = '#f39c12';
                iconoEstado = 'ℹ️';
            } else if (registro.estado === 'incompleta') {
                colorEstado = '#e74c3c';
                iconoEstado = '✗';
            }
            
            let colorHoraExtra = registro.tieneHoraExtra ? '#27ae60' : '#6c757d';
            
            row.innerHTML = `
                <td>${nombreMostrado}</td>
                <td>${registro.codigo}</td>
                <td style="text-align: center; font-weight: 600; color: #27ae60;">${registro.horasTotales}</td>
                <td style="text-align: center; font-weight: 600; color: ${colorHoraExtra};">${registro.tieneHoraExtra ? 'Sí' : 'No'}</td>
                <td style="text-align: center; font-weight: 600; color: ${colorHoraExtra};">${registro.horaExtra}</td>
                <td style="text-align: center; font-weight: 600; color: ${colorEstado};" title="${registro.observacion}">
                    ${iconoEstado} ${registro.estado === 'incompleta_excepcion' ? 'Información Faltante' : registro.estado === 'completa' ? 'Completa' : registro.estado === 'sin_datos' ? 'Sin Datos' : 'Incompleta'}
                </td>
            `;
            
            recordsTableBody.appendChild(row);
        });
    }

    /**
     * Renderizar registros con búsqueda
     */
    function renderRecordosTableWithSearch(registros, maxHoras, totalRegistros, searchTerm) {
        const recordsTableBody = document.getElementById('recordsTableBody');
        
        if (!recordsTableBody) return;
        
        recordsTableBody.innerHTML = '';
        
        if (registros.length === 0 && searchTerm !== '') {
            const row = document.createElement('tr');
            row.innerHTML = `<td colspan="${2 + maxHoras}" class="empty-search-message">
                <div style="padding: 30px 0;">
                    <svg style="width: 48px; height: 48px; margin-bottom: 15px; opacity: 0.5;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"></circle>
                        <path d="m21 21-4.35-4.35"></path>
                    </svg>
                    <p>No se encontraron resultados para "<strong>${AsistenciaUtilidades.escapeHtml(searchTerm)}</strong>"</p>
                    <p style="font-size: 0.85rem; margin-top: 8px;">Intenta con otro número de persona o nombre</p>
                </div>
            </td>`;
            recordsTableBody.appendChild(row);
            return;
        }
        
        if (registros.length === 0) {
            const row = document.createElement('tr');
            row.innerHTML = '<td colspan="' + (2 + maxHoras) + '" class="empty-cell">No hay registros para esta fecha</td>';
            recordsTableBody.appendChild(row);
            return;
        }
        
        registros.forEach(registro => {
            const row = document.createElement('tr');
            row.setAttribute('data-persona-id', registro.codigo_persona);
            row.setAttribute('data-persona-nombre', registro.nombre.toLowerCase());
            
            let nombreMostrado = registro.nombre;
            if (searchTerm !== '' && registro.nombre.toLowerCase().includes(searchTerm)) {
                nombreMostrado = registro.nombre.replace(
                    new RegExp(`(${AsistenciaUtilidades.escapeRegExp(searchTerm)})`, 'gi'),
                    '<mark>$1</mark>'
                );
            }
            
            let rowHTML = `
                <td>${nombreMostrado}</td>
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
    }

    function setRegistrosOriginales(fecha, registros) {
        registrosOriginalesPorFecha[fecha] = registros;
    }

    function setHorasTrabajadasPorFecha(fecha, horas) {
        horasTrabajadasPorFecha[fecha] = horas;
    }

    function setVistaHorasTrabajadas(vista) {
        vistaHorasTrabajadas = vista;
    }

    return {
        inicializarBusquedaNormal,
        inicializarBusquedaHoras,
        renderHorasTrabajadasSearchResults,
        renderRecordosTableWithSearch,
        setRegistrosOriginales,
        setHorasTrabajadasPorFecha,
        setVistaHorasTrabajadas,
        getVistaHorasTrabajadas: () => vistaHorasTrabajadas
    };
})();
