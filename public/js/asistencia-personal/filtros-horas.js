/**
 * Módulo de Filtros - Asistencia Personal
 * Gestión de filtros en la tabla de horas trabajadas
 */

const AsistenciaFiltros = (() => {
    let filtrosHoras = {};
    const STORAGE_KEY = 'asistencia_filtros_horas';
    const STORAGE_FECHA_KEY = 'asistencia_filtros_fecha';

    /**
     * Cargar filtros del localStorage
     */
    function cargarFiltrosGuardados() {
        const filtrosGuardados = localStorage.getItem(STORAGE_KEY);
        const fechaGuardada = localStorage.getItem(STORAGE_FECHA_KEY);
        
        if (filtrosGuardados) {
            filtrosHoras = JSON.parse(filtrosGuardados);
        }
        
        return { filtros: filtrosHoras, fecha: fechaGuardada };
    }

    /**
     * Guardar filtros en localStorage
     */
    function guardarFiltros(fecha) {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(filtrosHoras));
        localStorage.setItem(STORAGE_FECHA_KEY, fecha || 'general');
    }

    /**
     * Contar cuántos valores está filtrando una columna
     */
    function contarFiltrosColumna(nombreColumna) {
        if (filtrosHoras[nombreColumna]) {
            return Object.values(filtrosHoras[nombreColumna]).filter(v => v === true).length;
        }
        return 0;
    }

    /**
     * Obtener valores únicos de una columna de horas trabajadas
     */
    function obtenerValoresColumnaHoras(nombreColumna) {
        const valores = new Set();
        const recordsTableBody = document.getElementById('recordsTableBody');
        
        if (!recordsTableBody) return [];
        
        const columnMap = {
            'Total Horas Trabajadas': 2,
            'Hora Extra': 3,
            'Total Horas Extra': 4,
            'Estado': 5
        };
        
        const columnIndex = columnMap[nombreColumna];
        if (columnIndex === undefined) return [];
        
        const filas = recordsTableBody.querySelectorAll('tr');
        filas.forEach(fila => {
            const celdas = fila.querySelectorAll('td');
            if (celdas[columnIndex]) {
                let valor = celdas[columnIndex].textContent.trim();
                
                if (nombreColumna === 'Hora Extra') {
                    valor = valor === 'Sí' ? 'Sí' : 'No';
                } else if (nombreColumna === 'Estado') {
                    const match = valor.match(/(Completa|Información Faltante|Sin Datos|Incompleta)/);
                    valor = match ? match[1] : valor;
                }
                
                if (valor && valor !== '-') {
                    valores.add(valor);
                }
            }
        });
        
        return Array.from(valores).sort();
    }

    /**
     * Mostrar botón flotante para limpiar filtros
     */
    function mostrarBotonLimpiarFiltros() {
        let btnFlotante = document.getElementById('btnLimpiarFiltrosFlotante');
        const totalFiltros = obtenerTotalFiltros();
        
        if (totalFiltros > 0) {
            if (!btnFlotante) {
                btnFlotante = document.createElement('button');
                btnFlotante.id = 'btnLimpiarFiltrosFlotante';
                btnFlotante.type = 'button';
                btnFlotante.title = 'Limpiar filtros';
                btnFlotante.style.cssText = `
                    position: fixed;
                    bottom: 30px;
                    right: 30px;
                    width: 60px;
                    height: 60px;
                    border-radius: 50%;
                    background: #ef4444;
                    border: none;
                    cursor: pointer;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    z-index: 9999;
                    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
                    transition: all 0.3s ease;
                `;
                
                btnFlotante.innerHTML = `
                    <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" style="width: 28px; height: 28px;">
                        <line x1="4" y1="6" x2="20" y2="6"></line>
                        <line x1="8" y1="12" x2="16" y2="12"></line>
                        <line x1="11" y1="18" x2="13" y2="18"></line>
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                    </svg>
                `;
                
                btnFlotante.addEventListener('mouseover', function() {
                    this.style.transform = 'scale(1.1)';
                    this.style.boxShadow = '0 6px 16px rgba(239, 68, 68, 0.6)';
                });
                
                btnFlotante.addEventListener('mouseout', function() {
                    this.style.transform = 'scale(1)';
                    this.style.boxShadow = '0 4px 12px rgba(239, 68, 68, 0.4)';
                });
                
                btnFlotante.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    AsistenciaFiltros.limpiarTodos();
                    ocultarBotonLimpiarFiltros();
                });
                
                document.body.appendChild(btnFlotante);
            } else {
                btnFlotante.style.display = 'flex';
            }
        } else {
            ocultarBotonLimpiarFiltros();
        }
    }

    /**
     * Ocultar botón flotante
     */
    function ocultarBotonLimpiarFiltros() {
        const btnFlotante = document.getElementById('btnLimpiarFiltrosFlotante');
        if (btnFlotante) {
            btnFlotante.style.display = 'none';
        }
    }

    /**
     * Abrir modal de filtro para horas trabajadas
     */
    function abrirFiltro(nombreColumna) {
        const modal = document.createElement('div');
        modal.className = 'filter-modal-horas';
        modal.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10000;
        `;
        
        const valores = obtenerValoresColumnaHoras(nombreColumna);
        const valoresSeleccionadosObj = filtrosHoras[nombreColumna] || {};
        const valoresSeleccionados = Object.keys(valoresSeleccionadosObj).filter(v => valoresSeleccionadosObj[v] === true);
        
        let opcionesHTML = '';
        
        const todosSeleccionados = valoresSeleccionados.length === valores.length && valores.length > 0;
        opcionesHTML += `
            <div style="border-bottom: 1px solid #e5e7eb; padding-bottom: 10px; margin-bottom: 10px;">
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-weight: 600;">
                    <input type="checkbox" id="select-all-horas" ${todosSeleccionados ? 'checked' : ''} 
                           onchange="document.querySelectorAll('.checkbox-filtro-horas').forEach(cb => cb.checked = this.checked)">
                    <span>Seleccionar Todo</span>
                </label>
            </div>
        `;
        
        valores.forEach(valor => {
            const isChecked = valoresSeleccionados.includes(valor) ? 'checked' : '';
            opcionesHTML += `
                <div style="margin-bottom: 8px;">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" class="checkbox-filtro-horas" value="${valor}" ${isChecked}>
                        <span>${valor}</span>
                    </label>
                </div>
            `;
        });
        
        const contenido = `
            <div style="background: white; border-radius: 8px; padding: 20px; max-height: 400px; overflow-y: auto; min-width: 300px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <h3 style="margin: 0; font-size: 1rem; color: #1f2937;">Filtrar por ${nombreColumna}</h3>
                    <button onclick="this.closest('.filter-modal-horas').remove()" style="background: none; border: none; cursor: pointer; font-size: 1.5rem;">×</button>
                </div>
                
                <div style="margin-bottom: 15px;">
                    <input type="text" id="buscar-filtro-horas" placeholder="Buscar..." 
                           onkeyup="AsistenciaFiltros.filtrarOpciones()" 
                           style="width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 0.9rem;">
                </div>
                
                <div id="opciones-filtro-horas" style="max-height: 250px; overflow-y: auto;">
                    ${opcionesHTML}
                </div>
                
                <div style="display: flex; gap: 10px; margin-top: 15px; border-top: 1px solid #e5e7eb; padding-top: 15px;">
                    <button onclick="AsistenciaFiltros.limpiar('${nombreColumna}')" 
                            style="flex: 1; padding: 8px 12px; background: #f3f4f6; border: 1px solid #d1d5db; border-radius: 4px; cursor: pointer; font-size: 0.9rem;">
                        Limpiar
                    </button>
                    <button onclick="AsistenciaFiltros.aplicar('${nombreColumna}')" 
                            style="flex: 1; padding: 8px 12px; background: #1a5490; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 0.9rem; font-weight: 500;">
                        Aplicar
                    </button>
                </div>
            </div>
        `;
        
        modal.innerHTML = contenido;
        
        modal.addEventListener('click', (e) => {
            if (e.target === modal) modal.remove();
        });
        
        document.body.appendChild(modal);
        
        setTimeout(() => {
            const buscar = document.getElementById('buscar-filtro-horas');
            if (buscar) buscar.focus();
        }, 100);
    }

    /**
     * Filtrar opciones del modal
     */
    function filtrarOpciones() {
        const busqueda = document.getElementById('buscar-filtro-horas').value.toLowerCase();
        const opciones = document.querySelectorAll('#opciones-filtro-horas label');
        
        opciones.forEach(opcion => {
            const texto = opcion.textContent.toLowerCase();
            opcion.parentElement.style.display = texto.includes(busqueda) ? 'block' : 'none';
        });
    }

    /**
     * Aplicar filtro
     */
    function aplicar(nombreColumna) {
        const checkboxes = document.querySelectorAll('.checkbox-filtro-horas:checked');
        const valoresSeleccionados = Array.from(checkboxes).map(cb => cb.value);
        
        if (valoresSeleccionados.length > 0) {
            filtrosHoras[nombreColumna] = {};
            valoresSeleccionados.forEach(valor => {
                filtrosHoras[nombreColumna][valor] = true;
            });
        } else {
            delete filtrosHoras[nombreColumna];
        }
        
        const tabActivo = document.querySelector('.tab-button.active');
        const fechaActual = tabActivo ? tabActivo.getAttribute('data-fecha') : 'general';
        guardarFiltros(fechaActual);
        
        document.querySelector('.filter-modal-horas').remove();
        aplicarFiltros();
        actualizarIndicadoresVisuals();
        mostrarBotonLimpiarFiltros();
    }

    /**
     * Limpiar filtro específico
     */
    function limpiar(nombreColumna) {
        delete filtrosHoras[nombreColumna];
        const tabActivo = document.querySelector('.tab-button.active');
        const fechaActual = tabActivo ? tabActivo.getAttribute('data-fecha') : 'general';
        guardarFiltros(fechaActual);
        document.querySelector('.filter-modal-horas').remove();
        aplicarFiltros();
        actualizarIndicadoresVisuals();
        mostrarBotonLimpiarFiltros();
    }

    /**
     * Aplicar todos los filtros a la tabla
     */
    function aplicarFiltros() {
        const recordsTableBody = document.getElementById('recordsTableBody');
        if (!recordsTableBody) return;
        
        const filas = recordsTableBody.querySelectorAll('tr');
        const columnMap = {
            'Total Horas Trabajadas': 2,
            'Hora Extra': 3,
            'Total Horas Extra': 4,
            'Estado': 5
        };
        
        let filasVisibles = 0;
        
        filas.forEach(fila => {
            if (fila.classList.contains('empty-filter-message')) return;
            
            let mostrar = true;
            const celdas = fila.querySelectorAll('td');
            
            for (const [columna, valoresFiltro] of Object.entries(filtrosHoras)) {
                const columnIndex = columnMap[columna];
                if (columnIndex === undefined) continue;
                
                let valorCelda = celdas[columnIndex].textContent.trim();
                
                if (columna === 'Hora Extra') {
                    valorCelda = valorCelda === 'Sí' ? 'Sí' : 'No';
                } else if (columna === 'Estado') {
                    const match = valorCelda.match(/(Completa|Información Faltante|Sin Datos|Incompleta)/);
                    valorCelda = match ? match[1] : valorCelda;
                }
                
                // valoresFiltro es un objeto con booleanos, no un array
                const valoresSeleccionados = Object.keys(valoresFiltro).filter(v => valoresFiltro[v] === true);
                if (!valoresSeleccionados.includes(valorCelda)) {
                    mostrar = false;
                    break;
                }
            }
            
            fila.style.display = mostrar ? '' : 'none';
            if (mostrar) filasVisibles++;
        });
        
        if (filasVisibles === 0) {
            const mensajeExistente = recordsTableBody.querySelector('.empty-filter-message');
            if (!mensajeExistente) {
                const row = document.createElement('tr');
                row.className = 'empty-filter-message';
                row.innerHTML = `<td colspan="6" style="text-align: center; padding: 30px; color: #9ca3af;">
                    No hay resultados que coincidan con los filtros seleccionados
                </td>`;
                recordsTableBody.appendChild(row);
            }
        } else {
            const mensajeExistente = recordsTableBody.querySelector('.empty-filter-message');
            if (mensajeExistente) {
                mensajeExistente.remove();
            }
        }
    }

    /**
     * Limpiar todos los filtros
     */
    function limpiarTodos() {
        filtrosHoras = {};
        localStorage.removeItem(STORAGE_KEY);
        localStorage.removeItem(STORAGE_FECHA_KEY);
        aplicarFiltros();
        actualizarIndicadoresVisuals();
    }

    /**
     * Actualizar indicadores visuales en los botones de filtro
     */
    function actualizarIndicadoresVisuals() {
        const columnNames = ['Total Horas Trabajadas', 'Hora Extra', 'Total Horas Extra', 'Estado'];
        columnNames.forEach(col => {
            const btn = document.querySelector(`button[onclick*="'${col}'"]`);
            if (btn) {
                const contador = contarFiltrosColumna(col);
                let badge = btn.querySelector('.filter-badge');
                
                if (contador > 0) {
                    if (!badge) {
                        badge = document.createElement('span');
                        badge.className = 'filter-badge';
                        btn.appendChild(badge);
                    }
                    badge.textContent = contador;
                    badge.style.display = 'inline-flex';
                } else {
                    if (badge) {
                        badge.style.display = 'none';
                    }
                }
            }
        });
    }

    /**
     * Obtener estado total de filtros
     */
    function obtenerTotalFiltros() {
        let total = 0;
        Object.values(filtrosHoras).forEach(columna => {
            total += Object.values(columna).filter(v => v === true).length;
        });
        return total;
    }

    return {
        obtenerValores: obtenerValoresColumnaHoras,
        abrir: abrirFiltro,
        filtrarOpciones,
        aplicar,
        limpiar,
        aplicarFiltros,
        limpiarTodos,
        cargarFiltrosGuardados,
        guardarFiltros,
        contarFiltrosColumna,
        actualizarIndicadoresVisuals,
        obtenerTotalFiltros,
        mostrarBotonLimpiarFiltros,
        ocultarBotonLimpiarFiltros: ocultarBotonLimpiarFiltros,
        obtenerFiltros: () => filtrosHoras
    };
})();
