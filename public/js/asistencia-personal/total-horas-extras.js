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
     * Detectar si una persona tiene marcas faltantes
     */
    function tieneMarcarsFaltantes(registrosPorFecha) {
        let tieneFaltantes = false;
        
        Object.keys(registrosPorFecha).forEach(fecha => {
            const registros = registrosPorFecha[fecha];
            
            // Extraer todas las horas del día
            let horas = [];
            registros.forEach(registro => {
                if (registro.horas && typeof registro.horas === 'object') {
                    Object.values(registro.horas).forEach(hora => {
                        if (hora) {
                            horas.push(hora);
                        }
                    });
                }
            });
            
            // Limpiar duplicados (diferencias menores a 2 minutos)
            const minutosArray = horas.map(h => horaAMinutos(h)).sort((a, b) => a - b);
            const horasValidas = [];
            for (let i = 0; i < minutosArray.length; i++) {
                if (horasValidas.length === 0 || Math.abs(minutosArray[i] - horasValidas[horasValidas.length - 1]) >= 2) {
                    horasValidas.push(minutosArray[i]);
                }
            }
            
            // Verificar si faltan marcas
            const fechaObj = new Date(fecha + 'T00:00:00');
            const esSabado = fechaObj.getDay() === 6;
            
            if (esSabado) {
                // En sábado: necesita mínimo 2 marcas (entrada y salida)
                if (horasValidas.length < 2) {
                    tieneFaltantes = true;
                }
            } else {
                // En día normal: necesita 4 marcas idealmente (E.Mañana, S.Mañana, E.Tarde, S.Tarde)
                // Si tiene menos de 4, tiene faltantes
                if (horasValidas.length < 4) {
                    tieneFaltantes = true;
                }
            }
        });
        
        return tieneFaltantes;
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
        
        // Encabezado: Novedades
        const thNovedades = document.createElement('th');
        thNovedades.textContent = 'Novedades';
        trHeader.appendChild(thNovedades);
        
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
            
            // Celda Novedades - Mostrar botón si hay marcas faltantes, sino mostrar "Sin novedades"
            const tdNovedades = document.createElement('td');
            const tieneFaltantes = tieneMarcarsFaltantes(persona.registros);
            
            if (tieneFaltantes) {
                const btnVerNovedades = document.createElement('button');
                btnVerNovedades.className = 'btn btn-sm btn-info';
                btnVerNovedades.textContent = 'Ver Novedades';
                btnVerNovedades.onclick = function(e) {
                    e.preventDefault();
                    abrirModalNovedades(persona);
                };
                tdNovedades.appendChild(btnVerNovedades);
            } else {
                const textoSinNovedades = document.createElement('span');
                textoSinNovedades.textContent = 'Sin novedades';
                textoSinNovedades.style.color = '#27ae60';
                textoSinNovedades.style.fontWeight = 'bold';
                tdNovedades.appendChild(textoSinNovedades);
            }
            
            tr.appendChild(tdNovedades);
            
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
     * Abrir modal de novedades para editar marcas
     */
    function abrirModalNovedades(persona) {
        console.log('Abriendo modal de novedades para:', persona);
        
        // Crear modal
        const modal = document.createElement('div');
        modal.className = 'modal-overlay modal-detail-overlay';
        modal.id = 'modalNovedadesEdit';
        modal.style.display = 'flex';
        
        const content = document.createElement('div');
        content.className = 'modal-content modal-detail-content';
        content.style.maxWidth = '900px';
        
        // Header
        const header = document.createElement('div');
        header.className = 'modal-detail-header';
        
        const title = document.createElement('h2');
        title.textContent = `Novedades - ${persona.nombre} (ID: ${persona.id})`;
        header.appendChild(title);
        
        const closeBtn = document.createElement('button');
        closeBtn.className = 'btn-modal-close-detail';
        closeBtn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>';
        closeBtn.onclick = function() {
            modal.remove();
        };
        header.appendChild(closeBtn);
        content.appendChild(header);
        
        // Body
        const body = document.createElement('div');
        body.className = 'modal-detail-body';
        
        // Crear tabla con las marcas
        const tabla = document.createElement('table');
        tabla.className = 'records-table';
        tabla.style.marginTop = '20px';
        
        // Encabezado de tabla
        const thead = document.createElement('thead');
        const trHeader = document.createElement('tr');
        
        const headers = ['Fecha', 'Entrada Mañana', 'Salida Mañana', 'Entrada Tarde', 'Salida Tarde', 'Total Horas', 'Faltante'];
        headers.forEach(headerText => {
            const th = document.createElement('th');
            th.textContent = headerText;
            trHeader.appendChild(th);
        });
        
        thead.appendChild(trHeader);
        tabla.appendChild(thead);
        
        // Body de tabla
        const tbody = document.createElement('tbody');
        
        // Procesar cada fecha
        Object.keys(persona.registros).sort().forEach(fecha => {
            const registros = persona.registros[fecha];
            
            // Extraer horas válidas (eliminando duplicados)
            let horas = [];
            registros.forEach(registro => {
                if (registro.horas && typeof registro.horas === 'object') {
                    Object.values(registro.horas).forEach(hora => {
                        if (hora) {
                            horas.push(hora);
                        }
                    });
                }
            });
            
            // Limpiar duplicados
            const minutosArray = horas.map(h => horaAMinutos(h)).sort((a, b) => a - b);
            const horasValidas = [];
            for (let i = 0; i < minutosArray.length; i++) {
                if (horasValidas.length === 0 || Math.abs(minutosArray[i] - horasValidas[horasValidas.length - 1]) >= 2) {
                    horasValidas.push(minutosArray[i]);
                }
            }
            
            // Convertir de vuelta a formato HH:MM
            const horasFormato = horasValidas.map(m => {
                const horas = Math.floor(m / 60);
                const minutos = Math.floor(m % 60);
                return `${String(horas).padStart(2, '0')}:${String(minutos).padStart(2, '0')}`;
            });
            
            // Crear fila
            const tr = document.createElement('tr');
            
            // Fecha
            const tdFecha = document.createElement('td');
            const dia = fecha.split('-')[2];
            const mes = fecha.split('-')[1];
            tdFecha.textContent = `${dia}/${mes}`;
            tr.appendChild(tdFecha);
            
            // Las 4 marcas (editable)
            const marcas = ['entrada_manana', 'salida_manana', 'entrada_tarde', 'salida_tarde'];
            const inputs = [];
            
            marcas.forEach((tipo, index) => {
                const td = document.createElement('td');
                const input = document.createElement('input');
                input.type = 'text';
                input.placeholder = 'HH:MM';
                input.value = horasFormato[index] || '';
                input.style.width = '70px';
                input.style.padding = '5px';
                input.style.border = '1px solid #ddd';
                input.style.borderRadius = '4px';
                input.dataset.tipo = tipo;
                input.dataset.fecha = fecha;
                input.dataset.personaId = persona.id;
                
                // Permitir actualización en tiempo real
                input.addEventListener('change', function() {
                    actualizarMarcaYCalcularHoras(this, persona, fecha);
                });
                
                input.addEventListener('input', function() {
                    calcularHorasEnTiempoReal(persona, fecha);
                });
                
                inputs.push(input);
                td.appendChild(input);
                tr.appendChild(td);
            });
            
            // Total horas trabajadas
            const tdTotal = document.createElement('td');
            tdTotal.id = `total-${fecha}-${persona.id}`;
            tdTotal.style.fontWeight = 'bold';
            tdTotal.style.textAlign = 'center';
            tr.appendChild(tdTotal);
            
            // Faltante
            const tdFaltante = document.createElement('td');
            tdFaltante.id = `faltante-${fecha}-${persona.id}`;
            tdFaltante.style.color = '#d9534f';
            tdFaltante.style.fontWeight = 'bold';
            tr.appendChild(tdFaltante);
            
            tbody.appendChild(tr);
            
            // Calcular totales iniciales
            setTimeout(() => {
                calcularHorasEnTiempoReal(persona, fecha);
            }, 100);
        });
        
        tabla.appendChild(tbody);
        body.appendChild(tabla);
        
        // Botones de acción
        const buttonContainer = document.createElement('div');
        buttonContainer.style.marginTop = '20px';
        buttonContainer.style.textAlign = 'right';
        
        const btnGuardar = document.createElement('button');
        btnGuardar.className = 'btn btn-primary';
        btnGuardar.textContent = 'Guardar Cambios';
        btnGuardar.onclick = function() {
            guardarMarcasActualizadas(persona, modal);
        };
        buttonContainer.appendChild(btnGuardar);
        
        const btnCerrar = document.createElement('button');
        btnCerrar.className = 'btn btn-secondary';
        btnCerrar.textContent = 'Cerrar';
        btnCerrar.style.marginLeft = '10px';
        btnCerrar.onclick = function() {
            modal.remove();
        };
        buttonContainer.appendChild(btnCerrar);
        
        body.appendChild(buttonContainer);
        content.appendChild(body);
        modal.appendChild(content);
        
        document.body.appendChild(modal);
        
        // Cerrar al hacer click en overlay
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                modal.remove();
            }
        });
    }

    /**
     * Calcular horas en tiempo real
     */
    function calcularHorasEnTiempoReal(persona, fecha) {
        const inputs = document.querySelectorAll(`input[data-fecha="${fecha}"]`);
        const horasValidas = [];
        
        inputs.forEach(input => {
            if (input.value.trim()) {
                const [h, m] = input.value.split(':').map(Number);
                if (!isNaN(h) && !isNaN(m)) {
                    horasValidas.push((h * 60) + m);
                }
            }
        });
        
        horasValidas.sort((a, b) => a - b);
        
        // Calcular total de horas trabajadas
        const fechaObj = new Date(fecha + 'T00:00:00');
        const esSabado = fechaObj.getDay() === 6;
        
        let totalMinutos = 0;
        if (esSabado) {
            // Sábado: entrada a salida
            if (horasValidas.length >= 2) {
                totalMinutos = horasValidas[horasValidas.length - 1] - horasValidas[0];
            }
        } else {
            // Día normal: bloques de mañana y tarde
            if (horasValidas.length >= 2) {
                const bloqueManana = horasValidas[1] - horasValidas[0];
                let bloqueTarde = 0;
                if (horasValidas.length >= 4) {
                    bloqueTarde = horasValidas[3] - horasValidas[2];
                }
                totalMinutos = bloqueManana + bloqueTarde;
            }
        }
        
        // Mostrar total
        const tdTotal = document.getElementById(`total-${fecha}-${persona.id}`);
        if (tdTotal) {
            tdTotal.textContent = minutosAHora(totalMinutos);
        }
        
        // Determinar faltantes
        const tdFaltante = document.getElementById(`faltante-${fecha}-${persona.id}`);
        if (tdFaltante) {
            let faltantes = [];
            if (horasValidas.length === 0) {
                faltantes = ['Entrada Mañana', 'Salida Mañana', 'Entrada Tarde', 'Salida Tarde'];
            } else if (horasValidas.length < 4 && !esSabado) {
                if (horasValidas.length < 2) {
                    faltantes = ['Entrada Mañana', 'Salida Mañana'];
                } else if (horasValidas.length === 2) {
                    faltantes = ['Entrada Tarde', 'Salida Tarde'];
                } else if (horasValidas.length === 3) {
                    faltantes = ['Salida Tarde'];
                }
            }
            tdFaltante.textContent = faltantes.length > 0 ? faltantes.join(', ') : 'Completo ✓';
            tdFaltante.style.color = faltantes.length > 0 ? '#d9534f' : '#27ae60';
        }
    }

    /**
     * Actualizar marca y recalcular horas
     */
    function actualizarMarcaYCalcularHoras(input, persona, fecha) {
        calcularHorasEnTiempoReal(persona, fecha);
    }

    /**
     * Guardar marcas actualizadas
     */
    function guardarMarcasActualizadas(persona, modal) {
        console.log('Guardando cambios para persona:', persona.id);
        
        // Obtener todos los inputs del modal
        const inputs = modal.querySelectorAll('input[type="text"]');
        const cambios = {};
        
        inputs.forEach(input => {
            const fecha = input.dataset.fecha;
            const tipo = input.dataset.tipo;
            const valor = input.value.trim();
            
            if (!cambios[fecha]) {
                cambios[fecha] = {};
            }
            
            cambios[fecha][tipo] = valor;
        });
        
        console.log('Cambios a guardar:', cambios);
        
        // Enviar cambios al servidor
        fetch('/asistencia-personal/guardar-asistencia-detallada', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: JSON.stringify({
                persona_id: persona.id,
                cambios: cambios
            })
        })
        .then(response => {
            console.log('Respuesta HTTP:', response.status, response.statusText);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status} ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Respuesta del servidor:', data);
            if (data.success || data.status === 'success') {
                // Mostrar mensaje de éxito sin cerrar el modal
                mostrarMensajeExito(modal);
                
                // Actualizar los datos de la persona en memoria
                Object.keys(cambios).forEach(fecha => {
                    if (persona.registros[fecha]) {
                        // Actualizar horas en los registros
                        persona.registros[fecha].forEach(registro => {
                            if (!registro.horas) {
                                registro.horas = {};
                            }
                            if (typeof registro.horas === 'string') {
                                registro.horas = JSON.parse(registro.horas);
                            }
                            
                            // Mapear las marcas a Hora 1, 2, 3, 4
                            const mapeo = {
                                'entrada_manana': 'Hora 1',
                                'salida_manana': 'Hora 2',
                                'entrada_tarde': 'Hora 3',
                                'salida_tarde': 'Hora 4'
                            };
                            
                            Object.keys(mapeo).forEach(nombreCambio => {
                                if (cambios[fecha][nombreCambio]) {
                                    registro.horas[mapeo[nombreCambio]] = cambios[fecha][nombreCambio];
                                }
                            });
                        });
                    }
                });
                
                console.log('Datos actualizados en memoria:', persona);
                
                // Recalcular horas extras después de actualizar los datos
                actualizarHorasExtrasEnTabla(persona);
                
            } else {
                alert('⚠ Error al guardar: ' + (data.message || 'Error desconocido'));
            }
        })
        .catch(error => {
            console.error('Error al guardar:', error);
            alert('✗ Error al guardar los cambios: ' + error.message);
        });
    }

    /**
     * Mostrar mensaje de éxito sin cerrar el modal
     */
    function mostrarMensajeExito(modal) {
        const mensajeExistente = modal.querySelector('.mensaje-exito');
        if (mensajeExistente) {
            mensajeExistente.remove();
        }
        
        const mensaje = document.createElement('div');
        mensaje.className = 'mensaje-exito';
        mensaje.textContent = '✓ Cambios guardados correctamente';
        mensaje.style.position = 'fixed';
        mensaje.style.top = '20px';
        mensaje.style.right = '20px';
        mensaje.style.backgroundColor = '#27ae60';
        mensaje.style.color = 'white';
        mensaje.style.padding = '15px 20px';
        mensaje.style.borderRadius = '4px';
        mensaje.style.zIndex = '10000';
        mensaje.style.fontWeight = 'bold';
        mensaje.style.boxShadow = '0 2px 10px rgba(0,0,0,0.2)';
        
        document.body.appendChild(mensaje);
        
        // Remover el mensaje después de 3 segundos
        setTimeout(() => {
            mensaje.remove();
        }, 3000);
    }

    /**
     * Mostrar detalles de la persona con tabla de registros por día
     */

    /**
     * Recalcular y actualizar las horas extras en la tabla para una persona
     */
    function actualizarHorasExtrasEnTabla(persona) {
        console.log('Actualizando horas extras en tabla para:', persona.nombre);
        
        // Recalcular horas extras por fecha
        let totalExtrasMinutos = 0;
        let totalExtrasHorasCompletas = 0;
        
        todasLasFechas.forEach(fecha => {
            const registrosDelDia = persona.registros[fecha] || [];
            const minutosExtras = calcularHorasExtras(registrosDelDia, fecha);
            persona.horasExtrasPorFecha[fecha] = minutosExtras;
            totalExtrasMinutos += minutosExtras;
            
            // Sumar solo las horas completas
            const horasCompletas = Math.floor(minutosExtras / 60);
            totalExtrasHorasCompletas += horasCompletas;
        });
        
        // Actualizar el total
        persona.totalHorasExtras = totalExtrasHorasCompletas * 60;
        
        console.log('Nuevas horas extras calculadas:', persona.totalHorasExtras);
        
        // Actualizar la fila en la tabla
        const tabla = document.getElementById('totalHorasExtrasTable');
        if (tabla) {
            const filaPersona = tabla.querySelector(`tbody tr[data-persona-id="${persona.id}"]`);
            if (filaPersona) {
                // Actualizar celdas de horas extras por fecha
                todasLasFechas.forEach((fecha, index) => {
                    const minutosExtras = persona.horasExtrasPorFecha[fecha] || 0;
                    const celdas = filaPersona.querySelectorAll('td');
                    // Las primeras 3 celdas son ID, Nombre, Novedades
                    // Las siguientes son las fechas (3 + index)
                    if (celdas[3 + index]) {
                        if (minutosExtras > 0) {
                            celdas[3 + index].textContent = minutosAHora(minutosExtras);
                        } else {
                            celdas[3 + index].textContent = '-';
                        }
                    }
                });
                
                // Actualizar celda de total (última celda)
                const celdas = filaPersona.querySelectorAll('td');
                const horasCompletas = Math.floor(persona.totalHorasExtras / 60);
                celdas[celdas.length - 1].textContent = horasCompletas;
                
                console.log('Fila de tabla actualizada para persona:', persona.id);
            }
        }
    }

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
