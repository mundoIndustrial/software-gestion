/**
 * Módulo Total Horas Extras - Asistencia Personal
 * Maneja la visualización del resumen de horas extras por persona
 */

console.log('✓ Módulo AsistenciaTotalHorasExtras cargado');

const AsistenciaTotalHorasExtras = (() => {
    let reportData = null;
    let todasLasFechas = [];

    /**
     * Convertir string HH:MM:SS a minutos totales
     */
    function horaAMinutos(horaStr) {
        if (!horaStr) return 0;
        const [h, m, s] = horaStr.split(':').map(Number);
        return (h * 60) + m + ((s || 0) / 60);
    }

    /**
     * Convertir minutos a formato HH:MM:SS
     */
    function minutosAHora(minutos) {
        const horas = Math.floor(minutos / 60);
        const mins = Math.floor(minutos % 60);
        const segs = Math.round(((minutos % 1) * 60));
        return `${String(horas).padStart(2, '0')}:${String(mins).padStart(2, '0')}:${String(segs).padStart(2, '0')}`;
    }

    /**
     * Calcular horas extras para una fecha y persona
     */
    function calcularHorasExtras(registrosDelDia, fecha) {
        if (!registrosDelDia || registrosDelDia.length === 0) {
            return 0; // sin registros = 0 horas extras
        }

        // Extraer las horas del objeto horas
        let horas = [];
        registrosDelDia.forEach(registro => {
            if (registro.horas && typeof registro.horas === 'object') {
                // Las horas están en un objeto con claves como "Hora 1", "Hora 2", etc.
                Object.values(registro.horas).forEach(hora => {
                    if (hora) {
                        horas.push(hora);
                    }
                });
            }
        });

        if (horas.length === 0) {
            return 0;
        }

        // Detectar si es sábado
        const fechaObj = new Date(fecha + 'T00:00:00');
        const esSabado = fechaObj.getDay() === 6;

        // Calcular total de minutos trabajados
        const minutosArray = horas.map(hora => horaAMinutos(hora)).sort((a, b) => a - b);

        // Limpiar duplicados
        const horasValidas = [];
        for (let i = 0; i < minutosArray.length; i++) {
            if (horasValidas.length === 0 || Math.abs(minutosArray[i] - horasValidas[horasValidas.length - 1]) >= 2) {
                horasValidas.push(minutosArray[i]);
            }
        }

        // Calcular tiempo trabajado
        let totalMinutos = 0;
        if (esSabado) {
            // Para sábado: solo entrada y salida
            if (horasValidas.length >= 2) {
                totalMinutos = horasValidas[horasValidas.length - 1] - horasValidas[0];
            }
        } else {
            // Para días normales: calcular bloques de trabajo
            if (horasValidas.length >= 2) {
                // Bloque mañana (entrada - salida mediodía)
                const bloqueManana = horasValidas[1] - horasValidas[0];
                
                let bloqueTarde = 0;
                if (horasValidas.length >= 4) {
                    // Bloque tarde (entrada tarde - salida final)
                    bloqueTarde = horasValidas[3] - horasValidas[2];
                } else if (horasValidas.length === 3) {
                    // Solo entrada tarde sin salida final
                    bloqueTarde = 0;
                }
                
                totalMinutos = bloqueManana + bloqueTarde;
            }
        }

        // Calcular horas extras
        let umbralMinutos;
        let minutosBase;

        if (esSabado) {
            umbralMinutos = (4 * 60) + 56; // 4 horas y 56 minutos
            minutosBase = 4 * 60; // 4 horas
        } else {
            umbralMinutos = (8 * 60) + 56; // 8 horas y 56 minutos
            minutosBase = 8 * 60; // 8 horas
        }

        if (totalMinutos < umbralMinutos) {
            return 0; // Sin horas extras
        }

        // Calcular minutos extras
        const minutosExtra = totalMinutos - minutosBase;
        return minutosExtra > 0 ? minutosExtra : 0;
    }

    /**
     * Mostrar la tabla de total de horas extras
     */
    function mostrarVista(reporte) {
        reportData = reporte;
        
        console.log('=== DEBUG TOTAL HORAS EXTRAS ===');
        console.log('Reporte:', reporte);
        console.log('Registros por persona:', reporte.registros_por_persona);
        
        // Extraer todas las fechas únicas y ordenarlas
        const fechasSet = new Set();
        reporte.registros_por_persona.forEach(registro => {
            if (registro.fecha) {
                fechasSet.add(registro.fecha);
            }
        });
        
        todasLasFechas = Array.from(fechasSet).sort();
        
        console.log('Todas las fechas:', todasLasFechas);
        
        // Agrupar registros por persona
        const registrosPorPersona = {};
        reporte.registros_por_persona.forEach(registro => {
            console.log('Procesando registro:', registro);
            
            const personaId = registro.codigo_persona;
            const personaNombre = registro.nombre || 'Desconocido';
            
            if (!registrosPorPersona[personaId]) {
                registrosPorPersona[personaId] = {
                    id: personaId,
                    nombre: personaNombre,
                    registros: {},
                    horasExtrasPorFecha: {},
                    totalHorasExtras: 0
                };
            }
            
            if (!registrosPorPersona[personaId].registros[registro.fecha]) {
                registrosPorPersona[personaId].registros[registro.fecha] = [];
            }
            
            registrosPorPersona[personaId].registros[registro.fecha].push(registro);
        });

        console.log('Registros por persona agrupados:', registrosPorPersona);

        // Calcular horas extras por persona y fecha
        Object.keys(registrosPorPersona).forEach(personaId => {
            const persona = registrosPorPersona[personaId];
            let totalExtrasMinutos = 0;
            let totalExtrasHorasCompletas = 0;

            todasLasFechas.forEach(fecha => {
                const registrosDelDia = persona.registros[fecha] || [];
                const minutosExtras = calcularHorasExtras(registrosDelDia, fecha);
                persona.horasExtrasPorFecha[fecha] = minutosExtras;
                totalExtrasMinutos += minutosExtras;
                
                // Sumar solo las horas completas (ignorar minutos)
                const horasCompletas = Math.floor(minutosExtras / 60);
                totalExtrasHorasCompletas += horasCompletas;
            });

            // El total se almacena como solo horas completas
            persona.totalHorasExtras = totalExtrasHorasCompletas * 60;
        });

        console.log('Registros con horas extras calculadas:', registrosPorPersona);

        // Filtrar solo personas con horas extras
        const personasConExtras = Object.keys(registrosPorPersona)
            .filter(personaId => registrosPorPersona[personaId].totalHorasExtras > 0)
            .map(personaId => registrosPorPersona[personaId]);

        console.log('Personas con extras:', personasConExtras);

        // Generar tabla
        generarTabla(personasConExtras);
    }

    /**
     * Generar la tabla de horas extras
     */
    function generarTabla(personasConExtras) {
        // Limpiar el contenido actual
        const tabContent = document.getElementById('tabContent');
        
        // Crear la tabla
        const tabla = document.createElement('table');
        tabla.className = 'records-table';
        tabla.id = 'totalHorasExtrasTable';
        
        // Crear encabezado
        const thead = document.createElement('thead');
        const trHeader = document.createElement('tr');
        
        // Encabezado: ID
        const thId = document.createElement('th');
        thId.textContent = 'ID';
        trHeader.appendChild(thId);
        
        // Encabezado: Nombre
        const thNombre = document.createElement('th');
        thNombre.textContent = 'Nombre';
        trHeader.appendChild(thNombre);
        
        // Encabezado: Acciones
        const thAcciones = document.createElement('th');
        thAcciones.textContent = 'Acciones';
        trHeader.appendChild(thAcciones);
        
        // Encabezados: Fechas dinámicas
        todasLasFechas.forEach(fecha => {
            const thFecha = document.createElement('th');
            // Extraer solo el día de la fecha (YYYY-MM-DD -> DD)
            const dia = fecha.split('-')[2];
            thFecha.textContent = dia;
            trHeader.appendChild(thFecha);
        });

        // Encabezado: Total
        const thTotal = document.createElement('th');
        thTotal.textContent = 'TOTAL';
        thTotal.style.fontWeight = 'bold';
        thTotal.style.backgroundColor = '#1e5ba8';
        thTotal.style.color = 'white';
        trHeader.appendChild(thTotal);
        
        thead.appendChild(trHeader);
        tabla.appendChild(thead);
        
        // Crear body
        const tbody = document.createElement('tbody');
        
        // Agregar filas por persona (solo con horas extras)
        personasConExtras.forEach(persona => {
            const tr = document.createElement('tr');
            tr.setAttribute('data-persona-id', persona.id);
            tr.setAttribute('data-persona-nombre', persona.nombre.toLowerCase());
            
            // Celda ID
            const tdId = document.createElement('td');
            tdId.textContent = persona.id;
            tr.appendChild(tdId);
            
            // Celda Nombre
            const tdNombre = document.createElement('td');
            tdNombre.textContent = persona.nombre;
            tr.appendChild(tdNombre);
            
            // Celda Acciones (vacía - botón Ver Novedades eliminado)
            const tdAcciones = document.createElement('td');
            tr.appendChild(tdAcciones);
            
            // Celdas por fecha
            todasLasFechas.forEach(fecha => {
                const td = document.createElement('td');
                const minutosExtras = persona.horasExtrasPorFecha[fecha] || 0;
                
                if (minutosExtras > 0) {
                    td.textContent = minutosAHora(minutosExtras);
                } else {
                    td.textContent = '-';
                }
                
                tr.appendChild(td);
            });

            // Celda Total
            const tdTotal = document.createElement('td');
            // Solo mostrar las horas sin minutos y segundos
            const horasCompletas = Math.floor(persona.totalHorasExtras / 60);
            tdTotal.textContent = horasCompletas;
            tdTotal.style.fontWeight = 'bold';
            tdTotal.style.backgroundColor = '#e8f1f7';
            tr.appendChild(tdTotal);
            
            tbody.appendChild(tr);
        });
        
        tabla.appendChild(tbody);
        
        // Reemplazar contenido sin tabs
        tabContent.innerHTML = '';
        const wrapper = document.createElement('div');
        wrapper.className = 'records-table-wrapper';
        wrapper.appendChild(tabla);
        tabContent.appendChild(wrapper);
    }

    /**
     * Mostrar detalles de la persona con tabla de registros por día
     */


    return {
        mostrarVista
    };
})();

/**
 * Inicializar búsqueda para tabla de total horas extras
 */
function inicializarBusquedaTotalHorasExtras() {
    const searchInput = document.getElementById('searchInput');
    if (!searchInput) return;

    searchInput.addEventListener('input', function() {
        const busqueda = this.value.toLowerCase().trim();
        const tabla = document.getElementById('totalHorasExtrasTable');
        
        if (!tabla) return;

        const filas = tabla.querySelectorAll('tbody tr');
        let filasVisibles = 0;

        filas.forEach(fila => {
            const personaId = fila.getAttribute('data-persona-id') || '';
            const personaNombre = fila.getAttribute('data-persona-nombre') || '';

            let mostrar = false;

            if (busqueda === '') {
                mostrar = true;
            } else if (personaId.toLowerCase().includes(busqueda)) {
                mostrar = true;
            } else if (personaNombre.includes(busqueda)) {
                mostrar = true;
            }

            if (mostrar) {
                fila.style.display = '';
                filasVisibles++;
            } else {
                fila.style.display = 'none';
            }
        });

        // Mostrar mensaje si no hay resultados
        if (filasVisibles === 0 && busqueda !== '') {
            console.log('No se encontraron resultados para: ' + busqueda);
        }
    });
}

/**
 * Función para extraer datos de la tabla y exportar a JSON
 */
function exportarDatosTotalHorasExtras() {
    const tabla = document.getElementById('totalHorasExtrasTable');
    if (!tabla) {
        alert('La tabla no existe. Asegúrate de haber abierto Total Horas Extras primero.');
        return;
    }

    const datos = [];
    const encabezados = [];
    
    // Extraer encabezados
    const theads = tabla.querySelectorAll('thead th');
    theads.forEach(th => {
        encabezados.push(th.textContent.trim());
    });

    // Extraer datos de filas
    const tbody = tabla.querySelector('tbody');
    const filas = tbody.querySelectorAll('tr');
    
    filas.forEach(fila => {
        const celdas = fila.querySelectorAll('td');
        const fila_datos = {};
        
        celdas.forEach((celda, index) => {
            const encabezado = encabezados[index];
            const valor = celda.textContent.trim();
            fila_datos[encabezado] = valor === '-' ? null : valor;
        });
        
        datos.push(fila_datos);
    });

    // Crear objeto JSON
    const json = {
        reporte: "Total Horas Extras",
        fecha_exportacion: new Date().toLocaleString('es-ES'),
        total_personas: datos.length,
        datos: datos
    };

    // Descargar JSON
    const jsonString = JSON.stringify(json, null, 2);
    const blob = new Blob([jsonString], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `total-horas-extras-${new Date().toISOString().split('T')[0]}.json`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);

    console.log('✓ JSON exportado:', json);
    alert('✓ Archivo JSON descargado correctamente');
}

console.log('✓ AsistenciaTotalHorasExtras definido:', typeof AsistenciaTotalHorasExtras);
