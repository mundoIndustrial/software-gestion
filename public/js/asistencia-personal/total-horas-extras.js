/**
 * Módulo Total Horas Extras - Asistencia Personal
 * Maneja la visualización del resumen de horas extras por persona
 */

console.log('✓ Módulo AsistenciaTotalHorasExtras cargado');

const AsistenciaTotalHorasExtras = (() => {
    let reportData = null;
    let todasLasFechas = [];
    let horasExtrasAgregadas = {}; // {codigo_persona: {fecha: horas}}
    let personasConExtras = []; // Variable global del módulo para acceso desde catch
    let registrosPorPersona = {}; // Registros agrupados por persona para acceso desde múltiples funciones

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
     * Contar horas basado en la regla: 56 minutos o más = 1 hora
     * @param {number} minutos - Total de minutos a contar
     * @returns {number} Horas contables (horas completas + 1 si minutos >= 56)
     */
    function contarHorasPor56Minutos(minutos) {
        const horas = Math.floor(minutos / 60);
        const mins = minutos % 60;
        // Si los minutos >= 56, contar como 1 hora adicional
        return mins >= 56 ? horas + 1 : horas;
    }

    /**
     * Calcular horas extras para una fecha y persona
     * Nueva lógica: Contar cada bloque de trabajo por la regla de 56 minutos
     */
    function calcularHorasExtras(registrosDelDia, fecha, idRol = null, entrada_sabado = null, salida_sabado = null) {
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

        // Calcular tiempo trabajado por bloque
        let horasContablesManana = 0;
        let horasContablesTarde = 0;

        if (esSabado) {
            // Para sábado: si tiene 4 marcas, calcular por bloques (mañana y tarde)
            if (horasValidas.length === 4) {
                // Bloque mañana (entrada sabado - salida mediodía)
                const bloqueManana = horasValidas[1] - horasValidas[0];
                const bloqueMananaRedondeado = Math.round(bloqueManana);
                horasContablesManana = contarHorasPor56Minutos(bloqueMananaRedondeado);
                
                // Bloque tarde (entrada tarde - salida sabado)
                const bloqueTarde = horasValidas[3] - horasValidas[2];
                const bloqueTardeRedondeado = Math.round(bloqueTarde);
                horasContablesTarde = contarHorasPor56Minutos(bloqueTardeRedondeado);
            } else if (horasValidas.length >= 2) {
                // Con 2 o 3 marcas: desde primera a última marca
                const bloqueMinutos = horasValidas[horasValidas.length - 1] - horasValidas[0];
                const bloqueMinutosRedondeado = Math.round(bloqueMinutos);
                horasContablesManana = contarHorasPor56Minutos(bloqueMinutosRedondeado);
            }
        } else if (idRol === 21) {
            // Para rol 21 entre semana: lógica especial según cantidad de marcas
            if (horasValidas.length === 4) {
                // Con 4 marcas: calcular como rol normal (bloque mañana + bloque tarde)
                const bloqueManana = horasValidas[1] - horasValidas[0];
                const bloqueMananaRedondeado = Math.round(bloqueManana);
                horasContablesManana = contarHorasPor56Minutos(bloqueMananaRedondeado);
                
                const bloqueTarde = horasValidas[3] - horasValidas[2];
                const bloqueTardeRedondeado = Math.round(bloqueTarde);
                horasContablesTarde = contarHorasPor56Minutos(bloqueTardeRedondeado);
            } else if (horasValidas.length >= 2) {
                // Con 2 o 3 marcas: contar desde la primera marca hasta la última
                const bloqueMinutos = horasValidas[horasValidas.length - 1] - horasValidas[0];
                const bloqueMinutosRedondeado = Math.round(bloqueMinutos);
                horasContablesManana = contarHorasPor56Minutos(bloqueMinutosRedondeado);
            }
        } else {
            // Para otros roles en días normales: calcular bloques de trabajo (mañana y tarde)
            if (horasValidas.length >= 2) {
                // Bloque mañana (entrada - salida mediodía)
                const bloqueManana = horasValidas[1] - horasValidas[0];
                const bloqueMananaRedondeado = Math.round(bloqueManana);
                horasContablesManana = contarHorasPor56Minutos(bloqueMananaRedondeado);
                
                if (horasValidas.length >= 4) {
                    // Bloque tarde (entrada tarde - salida final)
                    const bloqueTarde = horasValidas[3] - horasValidas[2];
                    const bloqueTardeRedondeado = Math.round(bloqueTarde);
                    horasContablesTarde = contarHorasPor56Minutos(bloqueTardeRedondeado);
                }
            }
        }

        // Total de horas contables
        const totalHorasContables = horasContablesManana + horasContablesTarde;

        // Calcular horas extras basado en horario esperado
        let horasEsperadas;
        
        if (esSabado) {
            // Si tenemos el horario del rol para sábado, usarlo
            if (entrada_sabado && salida_sabado) {
                const entradaMinutos = horaAMinutos(entrada_sabado);
                const salidaMinutos = horaAMinutos(salida_sabado);
                horasEsperadas = (salidaMinutos - entradaMinutos) / 60; // Convertir minutos a horas
            } else {
                // Fallback a valores por defecto según el rol
                horasEsperadas = (idRol === 21) ? 8 : 4;
            }
        } else {
            // Para días normales: 8 horas de trabajo (tanto para rol 21 como para otros)
            horasEsperadas = 8;
        }

        const horasExtras = totalHorasContables - horasEsperadas;
        return horasExtras > 0 ? horasExtras : 0;
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
     * Cargar horas extras agregadas de cada persona desde la BD
     * Retorna una promesa
     */
    function cargarHorasExtrasAgregadas(personasIds) {
        horasExtrasAgregadas = {};
        
        // Si no hay personas, retornar promesa resuelta
        if (!personasIds || personasIds.length === 0) {
            return Promise.resolve();
        }
        
        // Hacer una única llamada con todas las personas
        return fetch('/asistencia-personal/obtener-horas-extras-agregadas-batch', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: JSON.stringify({
                codigos_personas: personasIds
            })
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success && data.data) {
                    horasExtrasAgregadas = data.data;
                    return true;
                }
                console.warn('No hay datos de horas extras agregadas');
                horasExtrasAgregadas = {};
                return false;
            })
            .catch(error => {
                console.error('Error cargando horas extras:', error);
                horasExtrasAgregadas = {};
                return false;
            });
    }

    /**
     * Cargar todas las personas del sistema para el modal de editar registro
     */
    function cargarTodasLasPersonasParaModal(idReporte) {
        // Guardar las fechas del reporte en una variable global accesible
        window.todasLasFechasDelReporte = todasLasFechas;
        
        fetch('/api/asistencia-personal/obtener-todas-las-personas', {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(response => {
                // Manejar errores HTTP específicamente
                if (response.status === 401) {
                    // No autenticado - redirigir a login
                    console.warn('Sesión expirada. Redirigiendo a login...');
                    window.location.href = '/login';
                    return null;
                }
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (!data) return; // Si fue redirigido
                
                if (data.success && data.data) {
                    // Inicializar el módulo con las personas (convertir a array y pasar con registros)
                    // Usar registrosPorPersona que tiene la estructura completa con registros
                    const personasConRegistros = Object.values(registrosPorPersona || {});
                    AsistenciaEditarRegistro.init(personasConRegistros || [], idReporte, todasLasFechas);
                } else if (data.error) {
                    console.error('Error al cargar personas:', data.message);
                    // Fallback a personas con extras si falla
                    const personasConRegistros = Object.values(registrosPorPersona || {});
                    AsistenciaEditarRegistro.init(personasConRegistros || [], idReporte, todasLasFechas);
                } else {
                    console.error('Error al cargar personas - respuesta sin datos');
                    // Fallback a personas con extras si falla
                    const personasConRegistros = Object.values(registrosPorPersona || {});
                    AsistenciaEditarRegistro.init(personasConRegistros || [], idReporte, todasLasFechas);
                }
            })
            .catch(error => {
                console.error('Error cargando todas las personas:', error);
                // Fallback a personas con extras si falla
                const personasConRegistros = Object.values(registrosPorPersona || {});
                AsistenciaEditarRegistro.init(personasConRegistros || [], idReporte, todasLasFechas);
            });
    }

    /**
     * Obtener horas extras agregadas para una persona en una fecha específica
     */
    function obtenerHoraExtraAgregada(codigoPersona, fecha) {
        // Normalizar fecha (remover espacios)
        const fechaNormalizada = (fecha || '').trim();
        
        if (!horasExtrasAgregadas[codigoPersona]) {
            return 0;
        }
        
        // Buscar en los datos disponibles
        const datosPersona = horasExtrasAgregadas[codigoPersona];
        
        // Si es un objeto con fechas como claves
        if (typeof datosPersona === 'object' && !Array.isArray(datosPersona)) {
            // Buscar la fecha exacta
            for (let fechaKey in datosPersona) {
                const fechaKeyNormalizada = (fechaKey || '').trim();
                
                if (fechaKeyNormalizada === fechaNormalizada) {
                    const registros = datosPersona[fechaKey];
                    if (Array.isArray(registros) && registros.length > 0) {
                        const total = registros.reduce((sum, reg) => sum + parseFloat(reg.horas_agregadas || 0), 0);
                        return total;
                    }
                }
            }
        }
        
        return 0;
    }

    /**
     * Mostrar la tabla de total de horas extras
     */
    function mostrarVista(reporte) {
        reportData = reporte;
        
        // Extraer todas las fechas únicas y ordenarlas
        const fechasSet = new Set();
        reporte.registros_por_persona.forEach(registro => {
            if (registro.fecha) {
                fechasSet.add(registro.fecha);
            }
        });
        
        todasLasFechas = Array.from(fechasSet).sort();
        
        // Agrupar registros por persona (usar variable de módulo)
        registrosPorPersona = {};
        reporte.registros_por_persona.forEach(registro => {
            
            const personaId = registro.codigo_persona;
            const personaNombre = registro.nombre || 'Desconocido';
            const personaIdRol = registro.id_rol || null;
            
            if (!registrosPorPersona[personaId]) {
                registrosPorPersona[personaId] = {
                    id: personaId,
                    codigo_persona: personaId,
                    nombre: personaNombre,
                    idRol: personaIdRol,
                    entrada_sabado: registro.entrada_sabado || null,
                    salida_sabado: registro.salida_sabado || null,
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

        // Calcular horas extras por persona y fecha
        Object.keys(registrosPorPersona).forEach(personaId => {
            const persona = registrosPorPersona[personaId];
            let totalExtrasHoras = 0;

            todasLasFechas.forEach(fecha => {
                const registrosDelDia = persona.registros[fecha] || [];
                const horasExtras = calcularHorasExtras(registrosDelDia, fecha, persona.idRol, persona.entrada_sabado, persona.salida_sabado);
                persona.horasExtrasPorFecha[fecha] = horasExtras;
                totalExtrasHoras += horasExtras;
            });

            // El total se almacena como horas enteras
            persona.totalHorasExtras = totalExtrasHoras;
        });

        // Filtrar solo personas con horas extras
        personasConExtras = Object.keys(registrosPorPersona)
            .filter(personaId => registrosPorPersona[personaId].totalHorasExtras > 0)
            .map(personaId => registrosPorPersona[personaId]);

        // Cargar horas extras agregadas desde la BD y esperar
        const codigosPersonas = personasConExtras.map(p => p.codigo_persona);
        
        cargarHorasExtrasAgregadas(codigosPersonas).then(() => {
            generarTabla(personasConExtras);
        });
    }

    /**
     * Generar la tabla de horas extras
     */
    function generarTabla(personasConExtras) {
        // Mostrar botón de descargar PDF
        const btnDescargarPDF = document.getElementById('btnDescargarPDF');
        if (btnDescargarPDF) {
            btnDescargarPDF.style.display = 'inline-block';
            btnDescargarPDF.onclick = function(e) {
                e.preventDefault();
                descargarTablaPDF(personasConExtras);
            };
        }
        
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

        // Encabezado: Valor
        const thValor = document.createElement('th');
        thValor.textContent = 'VALOR';
        thValor.style.fontWeight = 'bold';
        thValor.style.backgroundColor = '#1e5ba8';
        thValor.style.color = 'white';
        trHeader.appendChild(thValor);
        
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
                const horasExtras = persona.horasExtrasPorFecha[fecha] || 0;
                const horaExtraAgregada = obtenerHoraExtraAgregada(persona.codigo_persona, fecha);
                
                if (horasExtras > 0 || horaExtraAgregada > 0) {
                    // Mostrar las horas redondeadas al entero más cercano
                    const totalHoras = horasExtras + horaExtraAgregada;
                    td.textContent = Math.round(totalHoras).toString();
                    
                    // Si hay hora extra agregada, pintar de verde
                    if (horaExtraAgregada > 0) {
                        td.style.backgroundColor = '#d4edda';
                        td.style.color = '#155724';
                        td.style.fontWeight = 'bold';
                        td.style.borderLeft = '4px solid #28a745';
                        td.title = `+ ${horaExtraAgregada.toFixed(2)}h agregadas`;
                    }
                } else {
                    td.textContent = '-';
                }
                
                tr.appendChild(td);
            });
            
            // Celda Total - Incluir horas extras agregadas
            const tdTotal = document.createElement('td');
            // Calcular total incluyendo horas agregadas
            let totalConAgregadas = persona.totalHorasExtras;
            todasLasFechas.forEach(fecha => {
                const horaExtraAgregada = obtenerHoraExtraAgregada(persona.codigo_persona, fecha);
                totalConAgregadas += horaExtraAgregada;
            });
            tdTotal.textContent = Math.round(totalConAgregadas).toString();
            tdTotal.style.fontWeight = 'bold';
            tdTotal.style.backgroundColor = '#e8f0f7';
            tr.appendChild(tdTotal);
            
            // Celda Valor
            const tdValor = document.createElement('td');
            const inputValor = document.createElement('input');
            inputValor.type = 'number';
            inputValor.placeholder = '0.00';
            inputValor.step = '0.01';
            inputValor.setAttribute('data-codigo-persona', persona.codigo_persona);
            inputValor.style.width = '100px';
            inputValor.style.padding = '5px';
            
            const btnGuardar = document.createElement('button');
            btnGuardar.textContent = '💾';
            btnGuardar.style.marginLeft = '5px';
            btnGuardar.style.padding = '8px 12px';
            btnGuardar.style.backgroundColor = '#3498db';
            btnGuardar.style.color = 'white';
            btnGuardar.style.border = 'none';
            btnGuardar.style.borderRadius = '4px';
            btnGuardar.style.cursor = 'pointer';
            btnGuardar.style.fontSize = '14px';
            btnGuardar.onclick = function(e) {
                e.preventDefault();
                guardarValorHoraExtra(persona.codigo_persona, inputValor.value, btnGuardar, reportData.id);
            };

            tdValor.appendChild(inputValor);
            tdValor.appendChild(btnGuardar);
            tr.appendChild(tdValor);
            
            // Cargar valor actual si existe
            cargarValorActual(persona.codigo_persona, inputValor);
            
            tbody.appendChild(tr);
        });
        
        // Agregar fila de TOTAL al final
        const trTotal = document.createElement('tr');
        trTotal.style.fontWeight = 'bold';
        trTotal.style.backgroundColor = '#1e5ba8';
        trTotal.style.color = 'white';
        
        // Celda Vacía (ID)
        const tdTotalId = document.createElement('td');
        tdTotalId.textContent = '';
        trTotal.appendChild(tdTotalId);
        
        // Celda "TOTAL"
        const tdTotalLabel = document.createElement('td');
        tdTotalLabel.textContent = 'TOTAL';
        tdTotalLabel.style.fontWeight = 'bold';
        trTotal.appendChild(tdTotalLabel);
        
        // Celda Vacía (Novedades)
        const tdTotalNovedades = document.createElement('td');
        tdTotalNovedades.textContent = '';
        trTotal.appendChild(tdTotalNovedades);
        
        // Celdas de fechas (vacías en fila de total)
        todasLasFechas.forEach(fecha => {
            const tdFechaTotal = document.createElement('td');
            tdFechaTotal.textContent = '';
            trTotal.appendChild(tdFechaTotal);
        });
        
        // Celda Total General - Incluir horas extras agregadas
        const tdTotalGeneral = document.createElement('td');
        let totalGeneral = 0;
        personasConExtras.forEach(persona => {
            totalGeneral += persona.totalHorasExtras;
            // Sumar las horas extras agregadas para cada persona
            todasLasFechas.forEach(fecha => {
                const horaExtraAgregada = obtenerHoraExtraAgregada(persona.codigo_persona, fecha);
                totalGeneral += horaExtraAgregada;
            });
        });
        tdTotalGeneral.textContent = Math.round(totalGeneral).toString();
        tdTotalGeneral.style.fontWeight = 'bold';
        tdTotalGeneral.style.color = 'white';
        trTotal.appendChild(tdTotalGeneral);
        
        // Celda Vacía (Valor)
        const tdTotalValor = document.createElement('td');
        tdTotalValor.textContent = '';
        trTotal.appendChild(tdTotalValor);
        
        tbody.appendChild(trTotal);
        
        tabla.appendChild(tbody);
        
        // Reemplazar contenido sin tabs
        tabContent.innerHTML = '';
        
        const wrapper = document.createElement('div');
        wrapper.className = 'records-table-wrapper';
        wrapper.appendChild(tabla);
        tabContent.appendChild(wrapper);
        
        // Agregar botón "Editar Registro" dinámicamente solo para esta vista
        const btnContainer = document.getElementById('btnEditarRegistroContainer');
        if (btnContainer) {
            btnContainer.innerHTML = `
                <button id="btnEditarRegistroModal" class="btn btn-info" style="padding: 10px 18px; font-size: 14px; font-weight: 600; white-space: nowrap; border-radius: 6px; border: none; background: linear-gradient(135deg, #3498db 0%, #2980b9 100%); color: white; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 2px 4px rgba(52, 152, 219, 0.3);">
                    Editar Registro
                </button>
            `;
            btnContainer.style.display = 'block';
        }
        
        // Agregar listeners al botón de editar registro y búsqueda
        setTimeout(() => {
            const btnEditarRegistro = document.getElementById('btnEditarRegistroModal');
            if (btnEditarRegistro) {
                btnEditarRegistro.addEventListener('click', () => {
                    const modal = document.getElementById('editarRegistroModal');
                    if (modal) {
                        modal.style.display = 'flex';
                        // Pasar el id_reporte al modal y las fechas disponibles
                        const idReporte = reportData?.id || null;
                        // Cargar todas las personas del sistema para búsqueda
                        cargarTodasLasPersonasParaModal(idReporte);
                    }
                });
            }
            
            // Implementar búsqueda en la barra original
            const searchInput = document.getElementById('searchInput');
            if (searchInput) {
                searchInput.addEventListener('input', (e) => {
                    const valor = e.target.value.toLowerCase();
                    const tabla = document.getElementById('totalHorasExtrasTable');
                    if (tabla) {
                        const filas = tabla.querySelectorAll('tbody tr:not(:last-child)');
                        filas.forEach(fila => {
                            const texto = fila.textContent.toLowerCase();
                            fila.style.display = texto.includes(valor) ? '' : 'none';
                        });
                    }
                });
            }
        }, 100);
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
        content.style.maxWidth = '1050px';
        content.style.maxHeight = '85vh';
        content.style.overflowY = 'auto';
        content.style.overflow = 'hidden';
        
        // Body con scroll
        const body = document.createElement('div');
        body.className = 'modal-detail-body';
        body.style.overflowY = 'auto';
        body.style.maxHeight = 'calc(85vh - 120px)';
        body.style.paddingBottom = '10px';
        
        // Crear tabla moderna
        const tabla = document.createElement('table');
        tabla.className = 'records-table';
        tabla.style.marginTop = '0px';
        tabla.style.width = '100%';
        tabla.style.borderCollapse = 'collapse';
        tabla.style.boxShadow = '0 2px 8px rgba(0,0,0,0.1)';
        tabla.style.borderRadius = '8px';
        tabla.style.overflow = 'hidden';
        
        // Encabezado de tabla - FIJO
        const thead = document.createElement('thead');
        thead.style.position = 'sticky';
        thead.style.top = '0';
        thead.style.zIndex = '10';
        thead.style.backgroundColor = '#1e5ba8';
        thead.style.color = 'white';
        thead.style.boxShadow = '0 2px 4px rgba(0,0,0,0.1)';
        
        const trHeader = document.createElement('tr');
        
        const headers = ['Fecha', 'Total Trabajado', 'Horas Extras', 'Agregadas', 'Novedad'];
        const columnWidths = ['18%', '20%', '22%', '20%', '20%'];
        
        headers.forEach((headerText, idx) => {
            const th = document.createElement('th');
            th.textContent = headerText;
            th.style.padding = '14px 12px';
            th.style.textAlign = 'left';
            th.style.fontWeight = '700';
            th.style.fontSize = '13px';
            th.style.letterSpacing = '0.5px';
            th.style.borderBottom = '2px solid #163d7a';
            th.style.width = columnWidths[idx];
            th.style.backgroundColor = '#1e5ba8';
            trHeader.appendChild(th);
        });
        
        thead.appendChild(trHeader);
        tabla.appendChild(thead);
        
        // Body de tabla
        const tbody = document.createElement('tbody');
        
        // Procesar cada fecha
        const fechasOrdenadas = Object.keys(persona.registros).sort();
        
        fechasOrdenadas.forEach((fecha, index) => {
            const registros = persona.registros[fecha];
            
            // Extraer horas válidas
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
            
            // Calcular total trabajado
            let totalTrabajado = 0;
            if (horasValidas.length >= 2) {
                totalTrabajado = horasValidas[horasValidas.length - 1] - horasValidas[0];
            }
            
            // Obtener horas extras trabajadas
            const horasExtras = persona.horasExtrasPorFecha[fecha] || 0;
            
            // Obtener horas extras agregadas y novedad
            const horaExtraAgregada = obtenerHoraExtraAgregada(persona.codigo_persona, fecha);
            let novedad = '-';
            
            // Buscar la novedad en los datos de horas extras agregadas
            if (horasExtrasAgregadas[persona.codigo_persona] && horasExtrasAgregadas[persona.codigo_persona][fecha]) {
                const registrosAgregados = horasExtrasAgregadas[persona.codigo_persona][fecha];
                if (Array.isArray(registrosAgregados) && registrosAgregados.length > 0) {
                    novedad = registrosAgregados[0].novedad || '-';
                }
            }
            
            // Crear fila
            const tr = document.createElement('tr');
            tr.style.borderBottom = '1px solid #e8e8e8';
            tr.style.transition = 'all 0.2s ease';
            
            // Alternar colores de fila
            if (index % 2 === 0) {
                tr.style.backgroundColor = '#fafafa';
            } else {
                tr.style.backgroundColor = 'white';
            }
            
            tr.addEventListener('mouseenter', function() {
                this.style.backgroundColor = '#e3f2fd';
                this.style.boxShadow = 'inset 0 0 0 1px #2196F3';
            });
            
            tr.addEventListener('mouseleave', function() {
                if (index % 2 === 0) {
                    this.style.backgroundColor = '#fafafa';
                } else {
                    this.style.backgroundColor = 'white';
                }
                this.style.boxShadow = 'none';
            });
            
            // Fecha
            const tdFecha = document.createElement('td');
            const dia = fecha.split('-')[2];
            const mes = fecha.split('-')[1];
            const año = fecha.split('-')[0];
            const fechaFormato = new Date(fecha + 'T00:00:00');
            const nombreDia = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'][fechaFormato.getDay()];
            tdFecha.textContent = `${nombreDia} ${dia}/${mes}`;
            tdFecha.style.padding = '12px';
            tdFecha.style.fontWeight = '600';
            tdFecha.style.color = '#1a237e';
            tdFecha.style.fontSize = '13px';
            tr.appendChild(tdFecha);
            
            // Total trabajado
            const tdTotalTrabajado = document.createElement('td');
            tdTotalTrabajado.textContent = minutosAHora(totalTrabajado);
            tdTotalTrabajado.style.padding = '12px';
            tdTotalTrabajado.style.fontWeight = '700';
            tdTotalTrabajado.style.color = '#0d47a1';
            tdTotalTrabajado.style.fontSize = '13px';
            tdTotalTrabajado.style.fontFamily = 'monospace';
            tr.appendChild(tdTotalTrabajado);
            
            // Horas extras trabajadas
            const tdHorasExtras = document.createElement('td');
            if (horasExtras > 0) {
                tdHorasExtras.textContent = horasExtras.toFixed(2) + ' h';
                tdHorasExtras.style.padding = '8px 12px';
                tdHorasExtras.style.fontWeight = '700';
                tdHorasExtras.style.color = '#1b5e20';
                tdHorasExtras.style.backgroundColor = '#c8e6c9';
                tdHorasExtras.style.borderRadius = '6px';
                tdHorasExtras.style.textAlign = 'center';
                tdHorasExtras.style.fontSize = '13px';
                tdHorasExtras.style.fontFamily = 'monospace';
            } else {
                tdHorasExtras.textContent = '0.00 h';
                tdHorasExtras.style.padding = '12px';
                tdHorasExtras.style.color = '#ccc';
                tdHorasExtras.style.fontSize = '13px';
                tdHorasExtras.style.textAlign = 'center';
            }
            tr.appendChild(tdHorasExtras);
            
            // Horas extras agregadas
            const tdHorasAgregadas = document.createElement('td');
            if (horaExtraAgregada > 0) {
                tdHorasAgregadas.textContent = '✓ ' + horaExtraAgregada.toFixed(2) + ' h';
                tdHorasAgregadas.style.padding = '8px 12px';
                tdHorasAgregadas.style.fontWeight = '700';
                tdHorasAgregadas.style.color = '#0d3817';
                tdHorasAgregadas.style.backgroundColor = '#a5d6a7';
                tdHorasAgregadas.style.borderRadius = '6px';
                tdHorasAgregadas.style.textAlign = 'center';
                tdHorasAgregadas.style.fontSize = '13px';
                tdHorasAgregadas.style.fontFamily = 'monospace';
                tdHorasAgregadas.style.borderLeft = '3px solid #2e7d32';
            } else {
                tdHorasAgregadas.textContent = '-';
                tdHorasAgregadas.style.padding = '12px';
                tdHorasAgregadas.style.color = '#ddd';
                tdHorasAgregadas.style.textAlign = 'center';
                tdHorasAgregadas.style.fontSize = '13px';
            }
            tr.appendChild(tdHorasAgregadas);
            
            // Novedad
            const tdNovedad = document.createElement('td');
            if (novedad && novedad !== '-') {
                tdNovedad.textContent = novedad.substring(0, 45);
                tdNovedad.style.padding = '12px';
                tdNovedad.style.fontSize = '12px';
                tdNovedad.style.color = '#424242';
                tdNovedad.title = novedad;
                tdNovedad.style.maxWidth = '180px';
                tdNovedad.style.overflow = 'hidden';
                tdNovedad.style.textOverflow = 'ellipsis';
                tdNovedad.style.whiteSpace = 'nowrap';
            } else {
                tdNovedad.textContent = '-';
                tdNovedad.style.padding = '12px';
                tdNovedad.style.color = '#ddd';
                tdNovedad.style.textAlign = 'center';
                tdNovedad.style.fontSize = '13px';
            }
            tr.appendChild(tdNovedad);
            
            tbody.appendChild(tr);
        });
        
        tabla.appendChild(tbody);
        body.appendChild(tabla);
        
        // Botones de acción
        const buttonContainer = document.createElement('div');
        buttonContainer.style.marginTop = '15px';
        buttonContainer.style.textAlign = 'right';
        buttonContainer.style.paddingTop = '12px';
        buttonContainer.style.borderTop = '1px solid #e0e0e0';
        
        const btnCerrar = document.createElement('button');
        btnCerrar.className = 'btn btn-secondary';
        btnCerrar.textContent = 'Cerrar';
        btnCerrar.style.padding = '10px 20px';
        btnCerrar.style.fontSize = '13px';
        btnCerrar.style.cursor = 'pointer';
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
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
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
        let totalExtrasHoras = 0;
        
        todasLasFechas.forEach(fecha => {
            const registrosDelDia = persona.registros[fecha] || [];
            const horasExtras = calcularHorasExtras(registrosDelDia, fecha, persona.idRol, persona.entrada_sabado, persona.salida_sabado);
            persona.horasExtrasPorFecha[fecha] = horasExtras;
            totalExtrasHoras += horasExtras;
        });
        
        // Actualizar el total
        persona.totalHorasExtras = totalExtrasHoras;
        
        console.log('Nuevas horas extras calculadas:', persona.totalHorasExtras);
        
        // Actualizar la fila en la tabla
        const tabla = document.getElementById('totalHorasExtrasTable');
        if (tabla) {
            const filaPersona = tabla.querySelector(`tbody tr[data-persona-id="${persona.id}"]`);
            if (filaPersona) {
                // Actualizar celdas de horas extras por fecha
                todasLasFechas.forEach((fecha, index) => {
                    const horasExtras = persona.horasExtrasPorFecha[fecha] || 0;
                    const celdas = filaPersona.querySelectorAll('td');
                    // Las primeras 3 celdas son ID, Nombre, Novedades
                    // Las siguientes son las fechas (3 + index)
                    if (celdas[3 + index]) {
                        if (horasExtras > 0) {
                            celdas[3 + index].textContent = Math.round(horasExtras).toString();
                        } else {
                            celdas[3 + index].textContent = '-';
                        }
                    }
                });
                
                // Actualizar celda de total (penúltima celda)
                const celdas = filaPersona.querySelectorAll('td');
                celdas[celdas.length - 2].textContent = Math.round(persona.totalHorasExtras).toString();
                
                console.log('Fila de tabla actualizada para persona:', persona.id);
            }
        }
    }

    /**
     * Descargar tabla como PDF
     */
    function descargarTablaPDF(personasConExtras) {
        PDFGenerator.descargar(personasConExtras, todasLasFechas);
    }

    /**
     * Actualizar horas extras agregadas para una persona
     * Sin recargar toda la página
     */
    function actualizarHorasAgregadas(codigoPersona) {
        // Recargar las horas extras agregadas de esta persona
        fetch(`/asistencia-personal/obtener-horas-extras-agregadas/${codigoPersona}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success && data.data) {
                    horasExtrasAgregadas[codigoPersona] = data.data;
                    console.log(`✓ Horas extras actualizadas para ${codigoPersona}`);
                    
                    // Actualizar celdas en la tabla para esta persona
                    const fila = document.querySelector(`tr[data-persona-id="${codigoPersona}"]`);
                    if (fila) {
                        // Recalcular celdas de cada fecha
                        todasLasFechas.forEach((fecha, index) => {
                            // Las celdas empiezan desde índice 2 (después de ID y Nombre)
                            // Más Novedades (indice 2)
                            // Entonces la primera fecha está en índice 3
                            const celdaIndex = 3 + index;
                            const celda = fila.children[celdaIndex];
                            
                            if (celda) {
                                const horaExtraAgregada = obtenerHoraExtraAgregada(codigoPersona, fecha);
                                
                                // Restaurar estilos por defecto
                                celda.style.backgroundColor = '';
                                celda.style.color = '';
                                celda.style.fontWeight = '';
                                celda.style.borderLeft = '';
                                celda.title = '';
                                
                                // Aplicar nuevo color si hay horas extras agregadas
                                if (horaExtraAgregada > 0) {
                                    celda.style.backgroundColor = '#d4edda';
                                    celda.style.color = '#155724';
                                    celda.style.fontWeight = 'bold';
                                    celda.style.borderLeft = '4px solid #28a745';
                                    celda.title = `+ ${horaExtraAgregada.toFixed(2)}h agregadas`;
                                }
                            }
                        });
                    }
                }
            })
            .catch(error => console.error('Error actualizando horas extras:', error));
    }

    return {
        mostrarVista,
        actualizarHorasAgregadas
    };
})();

/**
 * Cargar el valor actual del valor_hora_extra desde la API
 */
function cargarValorActual(codigoPersona, inputElement) {
    fetch(`/api/valor-hora-extra/${codigoPersona}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.valor) {
                inputElement.value = parseFloat(data.valor).toFixed(2);
            }
        })
        .catch(error => console.log('Error cargando valor:', error));
}

/**
 * Guardar el valor de la hora extra
 */
function guardarValorHoraExtra(codigoPersona, valor, btnElement, idReporte) {
    if (!valor || isNaN(valor)) {
        alert('Por favor ingrese un valor numérico válido');
        return;
    }

    console.log('Guardando valor:', { codigoPersona, valor, idReporte });

    const btnText = btnElement.textContent;
    btnElement.textContent = '⏳';
    btnElement.disabled = true;

    const payload = {
        codigo_persona: parseInt(codigoPersona),
        valor: parseFloat(valor),
        id_reporte: idReporte || null
    };

    console.log('Payload enviado:', payload);

    fetch('/api/valor-hora-extra/guardar', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
        },
        body: JSON.stringify(payload)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            btnElement.textContent = '✓';
            btnElement.style.backgroundColor = '#27ae60';
            
            setTimeout(() => {
                btnElement.textContent = btnText;
                btnElement.style.backgroundColor = '#3498db';
                btnElement.disabled = false;
            }, 2000);
        } else {
            alert('Error al guardar: ' + (data.message || 'Error desconocido'));
            btnElement.textContent = btnText;
            btnElement.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al guardar el valor');
        btnElement.textContent = btnText;
        btnElement.disabled = false;
    });
}

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
