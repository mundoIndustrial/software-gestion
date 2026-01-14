/**
 * Asistencia Personal Module
 * Gestión de reportes de asistencia
 */

document.addEventListener('DOMContentLoaded', function() {
    initializeAsistenciaModule();
});

function initializeAsistenciaModule() {
    const insertReportBtn = document.getElementById('insertReportBtn');
    const saveReportBtn = document.getElementById('saveReportBtn');
    const pdfInput = document.getElementById('pdfInput');

    // Almacenar datos del reporte actual
    let currentReportData = [];
    
    // Almacenar datos originales para filtrado
    let registrosOriginalesPorFecha = {};
    
    // Almacenar horas trabajadas por fecha para filtrado en vista de horas
    let horasTrabajadasPorFecha = {};
    
    // Variable para rastrear si estamos en vista de horas trabajadas
    let vistaHorasTrabajadas = false;

    // Botón para insertar reporte
    insertReportBtn.addEventListener('click', function() {
        pdfInput.click();
    });

    // Manejo de selección de archivo PDF
    pdfInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file && file.type === 'application/pdf') {
            uploadPDF(file);
        } else {
            alert('Por favor selecciona un archivo PDF válido');
        }
    });

    // Botón guardar reporte
    saveReportBtn.addEventListener('click', function() {
        if (currentReportData.length === 0) {
            alert('Por favor carga un PDF primero');
            return;
        }
        saveReport();
    });

    // Manejar clicks en botones de ver detalles de reportes
    initializeReportViewButtons();

    /**
     * Inicializar botones de vista de reportes
     */
    function initializeReportViewButtons() {
        const viewButtons = document.querySelectorAll('.btn-view');
        viewButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                const reportId = this.getAttribute('data-id');
                openReportDetailModal(reportId);
            });
        });
    }

    /**
     * Subir y procesar PDF
     */
    function uploadPDF(file) {
        const formData = new FormData();
        formData.append('pdf', file);

        fetch('/asistencia-personal/procesar-pdf', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                currentReportData = data.registros;
                
                // Mostrar modal de confirmación
                showPdfConfirmation(data.cantidad);
                
                // Agregar indicador al botón
                addPdfIndicator(insertReportBtn);
            } else {
                alert('Error al procesar el PDF: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al procesar el PDF: ' + error.message);
        });
    }

    /**
     * Mostrar modal de confirmación
     */
    function showPdfConfirmation(cantidad) {
        const modal = document.createElement('div');
        modal.className = 'modal-overlay';
        modal.innerHTML = `
            <div class="modal-content">
                <div class="modal-success">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                </div>
                <h3>PDF Adjuntado Correctamente</h3>
                <p>${cantidad} registros cargados</p>
                <button class="btn-modal-close">Aceptar</button>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        const closeBtn = modal.querySelector('.btn-modal-close');
        closeBtn.addEventListener('click', function() {
            modal.remove();
        });
        
        // Cerrar con ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                modal.remove();
            }
        }, { once: true });
    }

    /**
     * Agregar indicador al botón
     */
    function addPdfIndicator(btn) {
        // Remover indicador anterior si existe
        const existingIndicator = btn.querySelector('.pdf-indicator');
        if (existingIndicator) {
            existingIndicator.remove();
        }

        const indicator = document.createElement('span');
        indicator.className = 'pdf-indicator';
        indicator.textContent = '1';
        btn.appendChild(indicator);
    }

    /**
     * Remover indicador del botón
     */
    function removePdfIndicator(btn) {
        const indicator = btn.querySelector('.pdf-indicator');
        if (indicator) {
            indicator.remove();
        }
    }

    /**
     * Guardar reporte en la base de datos
     */
    function saveReport() {
        const confirmSave = confirm(`¿Deseas guardar ${currentReportData.length} registros de asistencia?`);
        
        if (!confirmSave) return;

        fetch('/asistencia-personal/guardar-registros', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: JSON.stringify({ registros: currentReportData })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(`✓ ${data.guardados} registros guardados correctamente\nReporte: ${data.numero_reporte}`);
                currentReportData = [];
                
                // Remover indicador del botón
                removePdfIndicator(insertReportBtn);
                
                // Limpiar input
                pdfInput.value = '';
                
                // Recargar la página para ver el nuevo reporte en la tabla
                location.reload();
            } else {
                alert('Error al guardar: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al guardar los registros');
        });
    }

    /**
     * Abrir modal de detalles del reporte
     */
    function openReportDetailModal(reportId) {
        const modal = document.getElementById('reportDetailModal');
        const modalTitle = document.getElementById('reportModalTitle');
        
        if (!modal) {
            console.error('Modal reportDetailModal no encontrado');
            return;
        }
        
        // Mostrar el modal
        modal.style.display = 'block';
        
        // Cargar detalles del reporte
        fetchReportDetails(reportId, function(data) {
            if (data.success && data.reporte) {
                const reporte = data.reporte;
                
                // Actualizar título
                modalTitle.textContent = `${reporte.numero_reporte} - ${reporte.nombre_reporte}`;
                
                // Limpiar tabs anteriores
                const tabsHeader = document.getElementById('tabsHeader');
                tabsHeader.innerHTML = '';
                
                // Agrupar registros por fecha
                const registrosPorFecha = {};
                reporte.registros_por_persona.forEach(registro => {
                    const fecha = registro.fecha;
                    if (!registrosPorFecha[fecha]) {
                        registrosPorFecha[fecha] = [];
                    }
                    registrosPorFecha[fecha].push(registro);
                });
                
                // Crear tabs para cada fecha
                const fechas = Object.keys(registrosPorFecha).sort();
                fechas.forEach((fecha, index) => {
                    const tabBtn = document.createElement('button');
                    tabBtn.className = 'tab-button' + (index === 0 ? ' active' : '');
                    tabBtn.setAttribute('data-fecha', fecha);
                    tabBtn.textContent = fecha;
                    tabBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        // Remover clase active de todos los tabs
                        const allTabs = tabsHeader.querySelectorAll('.tab-button');
                        allTabs.forEach(t => t.classList.remove('active'));
                        // Agregar clase active al tab clickeado
                        this.classList.add('active');
                        // Mostrar contenido del tab
                        showReportTab(fecha, registrosPorFecha[fecha]);
                    });
                    tabsHeader.appendChild(tabBtn);
                });
                
                // Mostrar primera fecha por defecto
                if (fechas.length > 0) {
                    showReportTab(fechas[0], registrosPorFecha[fechas[0]]);
                }
            } else {
                alert('Error al cargar detalles del reporte');
            }
        });
        
        // Botón para cerrar modal - solo agregar listener una vez
        const closeBtn = modal.querySelector('.btn-modal-close-detail');
        if (closeBtn && !closeBtn.dataset.listenerAttached) {
            closeBtn.addEventListener('click', function(e) {
                e.preventDefault();
                modal.style.display = 'none';
            });
            closeBtn.dataset.listenerAttached = true;
        }
        
        // Cerrar modal al hacer click fuera
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                modal.style.display = 'none';
            }
        }, { once: false });
        
        // Botón de ausencias
        const btnAusencias = document.getElementById('btnAusenciasDelDia');
        if (btnAusencias && !btnAusencias.dataset.listenerAttached) {
            btnAusencias.addEventListener('click', function(e) {
                e.preventDefault();
                loadAbsencias(reportId);
            });
            btnAusencias.dataset.listenerAttached = true;
        }
        
        // Botón de horas trabajadas
        const btnHoras = document.getElementById('btnHorasTrabajadas');
        if (btnHoras && !btnHoras.dataset.listenerAttached) {
            btnHoras.addEventListener('click', function(e) {
                e.preventDefault();
                mostrarVistaHorasTrabajadas();
            });
            btnHoras.dataset.listenerAttached = true;
        }
        
        // Botón cerrar reporte
        const btnCerrar = document.getElementById('btnCerrarReporte');
        if (btnCerrar && !btnCerrar.dataset.listenerAttached) {
            btnCerrar.addEventListener('click', function(e) {
                e.preventDefault();
                modal.style.display = 'none';
            });
            btnCerrar.dataset.listenerAttached = true;
        }
    }

    /**
     * Obtener detalles del reporte desde la API
     */
    function fetchReportDetails(reportId, callback) {
        const url = `/asistencia-personal/reportes/${reportId}/detalles`;
        console.log('Fetching report details from:', url);
        
        fetch(url, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            }
        })
        .then(response => {
            console.log('Response status:', response.status);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Detalles del reporte recibidos:', data);
            callback(data);
        })
        .catch(error => {
            console.error('Error al cargar detalles:', error);
            alert('Error al cargar los detalles del reporte: ' + error.message);
        });
    }

    /**
     * Convertir string HH:MM:SS a minutos totales
     */
    function horaAMinutos(horaStr) {
        const [h, m, s] = horaStr.split(':').map(Number);
        return (h * 60) + m + ((s || 0) / 60);
    }

    /**
     * Detectar si una fecha es sábado
     * Formato esperado: YYYY-MM-DD
     */
    function esSabado(fechaStr) {
        const fecha = new Date(fechaStr + 'T00:00:00');
        return fecha.getDay() === 6; // 6 = sábado
    }

    /**
     * Calcular hora extra basado en total de minutos trabajados
     * Para días normales: umbral 8 horas y 56 minutos (536 minutos)
     * Para sábados: umbral 4 horas y 56 minutos (296 minutos)
     * Retorna un objeto con:
     * - tieneHoraExtra: boolean
     * - horaExtra: string "HH:MM:SS"
     */
    function calcularHoraExtra(totalMinutos, esDiaSabado = false) {
        // Determinar umbral según el día
        let umbralMinutos;
        let minutosBase;
        
        if (esDiaSabado) {
            umbralMinutos = (4 * 60) + 56; // 4 horas y 56 minutos = 296 minutos
            minutosBase = 4 * 60; // 4 horas = 240 minutos
        } else {
            umbralMinutos = (8 * 60) + 56; // 8 horas y 56 minutos = 536 minutos
            minutosBase = 8 * 60; // 8 horas = 480 minutos
        }
        
        if (totalMinutos < umbralMinutos) {
            return {
                tieneHoraExtra: false,
                horaExtra: '0:00:00'
            };
        }
        
        // Calcular minutos extra
        const minutosExtra = totalMinutos - minutosBase;
        
        // Convertir a HH:MM:SS
        const horas = Math.floor(minutosExtra / 60);
        const minutos = Math.floor(minutosExtra % 60);
        const segundos = Math.round(((minutosExtra % 1) * 60));
        
        const horaExtraStr = `${String(horas).padStart(2, '0')}:${String(minutos).padStart(2, '0')}:${String(segundos).padStart(2, '0')}`;
        
        return {
            tieneHoraExtra: true,
            horaExtra: horaExtraStr
        };
    }

    /**
     * Calcular horas trabajadas con validación inteligente de jornada
     * Detecta sábado y ajusta lógica de validación
     * Retorna un objeto con:
     * - horasTotales: string "HH:MM:SS"
     * - estado: "completa" | "incompleta" | "sin_datos"
     * - observacion: string descriptivo
     * - registrosFaltantes: array de registros faltantes
     * - excepcion: boolean indicador de excepción aplicada
     */
    function calcularHorasTrabajadasAvanzado(horas, fecha = null) {
        if (!horas || horas.length === 0) {
            return {
                horasTotales: '0:00:00',
                estado: 'sin_datos',
                observacion: 'Sin registros de entrada/salida',
                registrosFaltantes: [],
                excepcion: false,
                esSabado: false
            };
        }
        
        // Detectar si es sábado
        const diaSabado = fecha ? esSabado(fecha) : false;
        
        // Convertir horas a minutos desde medianoche para fácil comparación
        const minutosArray = horas.map(hora => {
            const [h, m, s] = hora.split(':').map(Number);
            return h * 60 + m + (s || 0) / 60;
        }).sort((a, b) => a - b);
        
        console.log('Minutos ordenados:', minutosArray);
        console.log('¿Es sábado?:', diaSabado);
        
        // Eliminar duplicados cercanos (menos de 2 minutos)
        const horasValidas = [];
        for (let i = 0; i < minutosArray.length; i++) {
            if (horasValidas.length === 0 || Math.abs(minutosArray[i] - horasValidas[horasValidas.length - 1]) >= 2) {
                horasValidas.push(minutosArray[i]);
            } else {
                console.log(`Duplicado ignorado: ${minutosArray[i]} minutos (muy cercano a ${horasValidas[horasValidas.length - 1]})`);
            }
        }
        
        console.log('Horas válidas después de limpiar duplicados:', horasValidas);
        
        // Si hay menos de 2 registros, no se puede calcular
        if (horasValidas.length < 2) {
            return {
                horasTotales: '0:00:00',
                estado: 'sin_datos',
                observacion: 'Insuficientes registros (mínimo 2 requeridos)',
                registrosFaltantes: ['entrada_mañana', 'salida_mediodía'],
                excepcion: false,
                esSabado: diaSabado
            };
        }
        
        // Si hay más de 4 registros, tomar solo los primeros 4 válidos
        let registrosAUsar = horasValidas;
        if (horasValidas.length > 4) {
            console.log(`Más de 4 registros detectados (${horasValidas.length}). Usando los primeros 4.`);
            registrosAUsar = horasValidas.slice(0, 4);
        }
        
        // Detectar registros faltantes y validar jornada
        let registrosFaltantes = [];
        let jornada_completa = false;
        
        // Validación diferente para sábado
        if (diaSabado) {
            // Para sábado: solo necesita 2 registros (entrada mañana + salida mediodía)
            jornada_completa = registrosAUsar.length >= 2;
            
            if (registrosAUsar.length === 1) {
                registrosFaltantes = ['salida_mediodía'];
            }
            // Si tiene 2 o más, es completa
        } else {
            // Para días normales: necesita 4 registros
            jornada_completa = registrosAUsar.length === 4;
            
            if (registrosAUsar.length === 1) {
                registrosFaltantes = ['salida_mediodía', 'entrada_tarde', 'salida_final'];
            } else if (registrosAUsar.length === 2) {
                registrosFaltantes = ['entrada_tarde', 'salida_final'];
            } else if (registrosAUsar.length === 3) {
                registrosFaltantes = ['salida_final'];
            }
        }
        
        console.log('Registros faltantes:', registrosFaltantes);
        console.log('¿Jornada completa?:', jornada_completa);
        
        // Caso especial: 3 registros y falta solo salida final (salida tarde) - Solo para días normales
        let excepcion = false;
        if (!diaSabado && registrosAUsar.length === 3 && registrosFaltantes.includes('salida_final')) {
            console.log('EXCEPCIÓN DETECTADA: Falta solo salida de la tarde. Se asume jornada de 8 horas.');
            excepcion = true;
            return {
                horasTotales: '08:00:00',
                estado: 'incompleta_excepcion',
                observacion: 'Falta salida de la tarde. Se calcula como jornada de 8 horas (información faltante)',
                registrosFaltantes: ['salida_final'],
                excepcion: true,
                esSabado: diaSabado
            };
        }
        
        // Calcular bloques válidos
        let totalMinutos = 0;
        let bloqueMañanaCalculado = false;
        let bloqueTardeCalculado = false;
        
        // Para sábado: solo calcula bloque mañana (entrada1 → salida1)
        if (diaSabado) {
            if (registrosAUsar.length >= 2) {
                const entrada_manana = registrosAUsar[0];
                const salida_medidia = registrosAUsar[1];
                
                if (entrada_manana < salida_medidia) {
                    const duracion = salida_medidia - entrada_manana;
                    totalMinutos += duracion;
                    bloqueMañanaCalculado = true;
                    console.log(`[SÁBADO] Bloque Mañana: ${entrada_manana} → ${salida_medidia} = ${duracion.toFixed(2)} minutos`);
                }
            }
        } else {
            // Para días normales: calcula bloque mañana y tarde
            // Bloque mañana: entrada1 → salida1 (índices 0, 1)
            if (registrosAUsar.length >= 2) {
                const entrada_manana = registrosAUsar[0];
                const salida_medidia = registrosAUsar[1];
                
                if (entrada_manana < salida_medidia) {
                    const duracion = salida_medidia - entrada_manana;
                    totalMinutos += duracion;
                    bloqueMañanaCalculado = true;
                    console.log(`Bloque Mañana: ${entrada_manana} → ${salida_medidia} = ${duracion.toFixed(2)} minutos`);
                }
            }
            
            // Bloque tarde: entrada2 → salida2 (índices 2, 3)
            if (registrosAUsar.length >= 4) {
                const entrada_tarde = registrosAUsar[2];
                const salida_final = registrosAUsar[3];
                
                if (entrada_tarde < salida_final) {
                    const duracion = salida_final - entrada_tarde;
                    totalMinutos += duracion;
                    bloqueTardeCalculado = true;
                    console.log(`Bloque Tarde: ${entrada_tarde} → ${salida_final} = ${duracion.toFixed(2)} minutos`);
                }
            } else if (registrosAUsar.length === 3) {
                // 3 registros: No se puede calcular bloque tarde (falta salida final)
                console.log('Bloque Tarde: No se puede calcular (falta salida final)');
            }
        }
        
        // Convertir minutos a HH:MM:SS
        const horas_total = Math.floor(totalMinutos / 60);
        const minutos_restantes = Math.floor(totalMinutos % 60);
        const segundos = Math.round(((totalMinutos % 1) * 60));
        
        const resultado = `${String(horas_total).padStart(2, '0')}:${String(minutos_restantes).padStart(2, '0')}:${String(segundos).padStart(2, '0')}`;
        
        // Determinar estado y observación
        let estado = 'incompleta';
        let observacion = 'Jornada incompleta';
        
        if (jornada_completa) {
            estado = 'completa';
            observacion = diaSabado ? 'Jornada de sábado completa (4 horas mínimo)' : 'Jornada completa';
        } else {
            // Describir qué falta
            if (registrosFaltantes.length > 0) {
                const faltantes = registrosFaltantes.map(r => {
                    const map = {
                        'entrada_mañana': 'entrada mañana',
                        'salida_mediodía': 'salida mediodía',
                        'entrada_tarde': 'entrada tarde',
                        'salida_final': 'salida tarde'
                    };
                    return map[r] || r;
                }).join(', ');
                observacion = `Falta: ${faltantes}`;
            }
        }
        
        console.log(`Total horas trabajadas: ${resultado} (${totalMinutos.toFixed(2)} minutos) - ${estado}`);
        
        return {
            horasTotales: resultado,
            estado: estado,
            observacion: observacion,
            registrosFaltantes: registrosFaltantes,
            excepcion: false,
            esSabado: diaSabado
        };
    }

    /**
     * Mostrar vista de horas trabajadas
     */
    function mostrarVistaHorasTrabajadas() {
        vistaHorasTrabajadas = true;
        
        // Obtener fecha actual
        const tabActivo = document.querySelector('.tab-button.active');
        const fechaActual = tabActivo ? tabActivo.getAttribute('data-fecha') : 'general';
        
        actualizarVistaHorasTrabajadas();
        agregarBotonVolverHoras();
        initializeSearchForHoras(fechaActual);
    }
    
    /**
     * Actualizar la vista de horas trabajadas con registros específicos
     */
    function actualizarVistaHorasTrabajadas(registros = null) {
        const recordsTableBody = document.getElementById('recordsTableBody');
        const recordsTableHeader = document.getElementById('recordsTableHeader');
        
        if (!recordsTableBody || !recordsTableHeader) {
            console.error('Elementos de tabla no encontrados');
            return;
        }
        
        // Si no se pasan registros, obtenerlos del DOM
        if (!registros) {
            registros = [];
            document.querySelectorAll('#recordsTableBody tr').forEach(row => {
                const cells = row.querySelectorAll('td');
                if (cells.length >= 2) {
                    const nombre = cells[0].textContent.trim();
                    const codigo = cells[1].textContent.trim();
                    const horas = [];
                    
                    // Recopilar todas las horas (columnas 2 en adelante)
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
        
        // Obtener fecha actual del tab activo
        const tabActivo = document.querySelector('.tab-button.active');
        const fechaActual = tabActivo ? tabActivo.getAttribute('data-fecha') : 'general';
        
        // Crear encabezado nuevo
        let headerHTML = '<th>Persona</th><th>ID</th><th>Total Horas Trabajadas</th><th>Hora Extra</th><th>Total Horas Extra</th><th>Estado</th>';
        recordsTableHeader.innerHTML = headerHTML;
        
        // Limpiar tabla
        recordsTableBody.innerHTML = '';
        
        // Procesar y guardar registros de horas trabajadas
        const horasTrabajadasData = [];
        
        // Llenar tabla con horas trabajadas
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
                // Usar la función avanzada para calcular horas, pasando la fecha
                calcResult = calcularHorasTrabajadasAvanzado(registro.horas, fechaActual);
            }
            
            // Calcular hora extra (pasando si es sábado)
            const totalMinutos = horaAMinutos(calcResult.horasTotales);
            const horaExtraResult = calcularHoraExtra(totalMinutos, calcResult.esSabado);
            
            // Determinar color del estado
            let colorEstado = '#6c757d'; // gris por defecto
            let iconoEstado = '⚠️';
            
            if (calcResult.estado === 'completa') {
                colorEstado = '#27ae60'; // verde
                iconoEstado = '✓';
            } else if (calcResult.estado === 'incompleta_excepcion') {
                colorEstado = '#f39c12'; // naranja
                iconoEstado = 'ℹ️';
            } else if (calcResult.estado === 'incompleta') {
                colorEstado = '#e74c3c'; // rojo
                iconoEstado = '✗';
            }
            
            // Color para hora extra
            let colorHoraExtra = horaExtraResult.tieneHoraExtra ? '#27ae60' : '#6c757d';
            
            row.setAttribute('data-persona-id', registro.codigo);
            row.setAttribute('data-persona-nombre', registro.nombre.toLowerCase());
            row.setAttribute('title', calcResult.observacion);
            
            row.innerHTML = `
                <td>${registro.nombre}</td>
                <td>${registro.codigo}</td>
                <td style="text-align: center; font-weight: 600; color: #27ae60;">${calcResult.horasTotales}</td>
                <td style="text-align: center; font-weight: 600; color: ${colorHoraExtra};">${horaExtraResult.tieneHoraExtra ? 'Sí' : 'No'}</td>
                <td style="text-align: center; font-weight: 600; color: ${colorHoraExtra};">${horaExtraResult.horaExtra}</td>
                <td style="text-align: center; font-weight: 600; color: ${colorEstado};" title="${calcResult.observacion}">
                    ${iconoEstado} ${calcResult.estado === 'incompleta_excepcion' ? 'Información Faltante' : calcResult.estado === 'completa' ? 'Completa' : calcResult.estado === 'sin_datos' ? 'Sin Datos' : 'Incompleta'}
                </td>
            `;
            
            recordsTableBody.appendChild(row);
            
            // Guardar para búsqueda
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
        
        // Guardar datos de horas por fecha para búsqueda
        horasTrabajadasPorFecha[fechaActual] = horasTrabajadasData;
        
        // Inicializar búsqueda para vista de horas
        initializeSearchForHoras(fechaActual);
    }
    
    /**
     * Agregar botón Volver cuando estamos en vista de horas trabajadas
     */
    function agregarBotonVolverHoras() {
        let btnVolverDiv = document.getElementById('btnVolverHorasDiv');
        if (!btnVolverDiv) {
            btnVolverDiv = document.createElement('div');
            btnVolverDiv.id = 'btnVolverHorasDiv';
            btnVolverDiv.style.marginBottom = '15px';
            btnVolverDiv.style.display = 'flex';
            btnVolverDiv.style.gap = '10px';
            
            const btnVolver = document.createElement('button');
            btnVolver.id = 'btnVolverHoras';
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
                vistaHorasTrabajadas = false;
                // Recargar la vista normal del tab actual
                const tabActivo = document.querySelector('.tab-button.active');
                if (tabActivo) {
                    tabActivo.click();
                }
                // Remover el botón volver
                if (btnVolverDiv && btnVolverDiv.parentNode) {
                    btnVolverDiv.parentNode.removeChild(btnVolverDiv);
                }
            });
            
            btnVolverDiv.appendChild(btnVolver);
            const tableWrapper = document.querySelector('.reportDetailBody table');
            if (tableWrapper && tableWrapper.parentNode) {
                tableWrapper.parentNode.insertBefore(btnVolverDiv, tableWrapper);
            }
        }
    }

    /**
     * Mostrar tab específico con registros de una fecha
     */
    function showReportTab(fecha, registros) {
        console.log('Mostrando tab para fecha:', fecha, 'Registros:', registros);
        
        // Guardar registros originales para búsqueda
        registrosOriginalesPorFecha[fecha] = registros;
        
        // Si estamos en vista de horas trabajadas, actualizar esa vista
        if (vistaHorasTrabajadas) {
            // Procesar registros para extraer horas
            const registrosProcesados = registros.map(registro => ({
                nombre: registro.nombre,
                codigo: registro.codigo_persona,
                horas: registro.horas && typeof registro.horas === 'object' ? Object.values(registro.horas) : []
            }));
            actualizarVistaHorasTrabajadas(registrosProcesados);
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
        initializeRealTimeSearch(fecha, maxHoras);
    }

    /**
     * Renderizar registros en la tabla
     */
    function renderRecordosTable(registros, maxHoras) {
        const recordsTableBody = document.getElementById('recordsTableBody');
        
        if (!recordsTableBody) return;
        
        recordsTableBody.innerHTML = '';
        
        // Llenar tabla con registros
        if (registros && registros.length > 0) {
            registros.forEach(registro => {
                const row = document.createElement('tr');
                row.setAttribute('data-persona-id', registro.codigo_persona);
                row.setAttribute('data-persona-nombre', registro.nombre.toLowerCase());
                
                // Columns: Persona, ID
                let rowHTML = `
                    <td>${registro.nombre}</td>
                    <td>${registro.codigo_persona}</td>
                `;
                
                // Agregar columnas de horas
                if (registro.horas && typeof registro.horas === 'object') {
                    const horasArray = Object.values(registro.horas);
                    for (let i = 0; i < maxHoras; i++) {
                        const hora = horasArray[i] || '—';
                        rowHTML += `<td>${hora}</td>`;
                    }
                } else {
                    // Si no hay horas, llenar con guiones
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

    /**
     * Inicializar búsqueda en tiempo real
     */
    function initializeRealTimeSearch(fechaActual, maxHoras) {
        const searchInput = document.getElementById('searchInput');
        
        if (!searchInput) return;
        
        // Remover event listeners anteriores si existen
        const newSearchInput = searchInput.cloneNode(true);
        searchInput.parentNode.replaceChild(newSearchInput, searchInput);
        
        // Agregar nuevo event listener
        const updatedSearchInput = document.getElementById('searchInput');
        if (updatedSearchInput) {
            updatedSearchInput.addEventListener('input', function(e) {
                const searchTerm = e.target.value.toLowerCase().trim();
                
                // Verificar si estamos en vista de horas trabajadas
                if (vistaHorasTrabajadas) {
                    // Usar búsqueda para horas
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
                    // Usar búsqueda para registros normales
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
            
            // Limpiar búsqueda con ESC
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
    function initializeSearchForHoras(fechaActual) {
        const searchInput = document.getElementById('searchInput');
        
        if (!searchInput) return;
        
        // Remover event listeners anteriores si existen
        const newSearchInput = searchInput.cloneNode(true);
        searchInput.parentNode.replaceChild(newSearchInput, searchInput);
        
        // Agregar nuevo event listener
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
            
            // Limpiar búsqueda con ESC
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
     * Renderizar resultados de búsqueda en vista de horas trabajadas
     */
    function renderHorasTrabajadasSearchResults(filtrados, searchTerm, total) {
        const recordsTableBody = document.getElementById('recordsTableBody');
        
        if (!recordsTableBody) return;
        
        recordsTableBody.innerHTML = '';
        
        // Si no hay resultados
        if (filtrados.length === 0 && searchTerm !== '') {
            const row = document.createElement('tr');
            row.innerHTML = `<td colspan="6" class="empty-search-message">
                <div style="padding: 30px 0;">
                    <svg style="width: 48px; height: 48px; margin-bottom: 15px; opacity: 0.5;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"></circle>
                        <path d="m21 21-4.35-4.35"></path>
                    </svg>
                    <p>No se encontraron resultados para "<strong>${escapeHtml(searchTerm)}</strong>"</p>
                    <p style="font-size: 0.85rem; margin-top: 8px;">Intenta con otro número de persona o nombre</p>
                </div>
            </td>`;
            recordsTableBody.appendChild(row);
            return;
        }
        
        // Si no hay registros en la fecha
        if (filtrados.length === 0) {
            const row = document.createElement('tr');
            row.innerHTML = '<td colspan="6" class="empty-cell">No hay registros para esta fecha</td>';
            recordsTableBody.appendChild(row);
            return;
        }
        
        // Renderizar registros de horas
        filtrados.forEach(registro => {
            const row = document.createElement('tr');
            row.setAttribute('data-persona-id', registro.codigo);
            row.setAttribute('data-persona-nombre', registro.nombre.toLowerCase());
            row.setAttribute('title', registro.observacion);
            
            // Resaltar coincidencias en el nombre si hay búsqueda
            let nombreMostrado = registro.nombre;
            if (searchTerm !== '' && registro.nombre.toLowerCase().includes(searchTerm)) {
                nombreMostrado = registro.nombre.replace(
                    new RegExp(`(${escapeRegExp(searchTerm)})`, 'gi'),
                    '<mark>$1</mark>'
                );
            }
            
            // Determinar color del estado
            let colorEstado = '#6c757d'; // gris por defecto
            let iconoEstado = '⚠️';
            
            if (registro.estado === 'completa') {
                colorEstado = '#27ae60'; // verde
                iconoEstado = '✓';
            } else if (registro.estado === 'incompleta_excepcion') {
                colorEstado = '#f39c12'; // naranja
                iconoEstado = 'ℹ️';
            } else if (registro.estado === 'incompleta') {
                colorEstado = '#e74c3c'; // rojo
                iconoEstado = '✗';
            }
            
            // Color para hora extra
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
     * Renderizar registros con información de búsqueda
     */
    function renderRecordosTableWithSearch(registros, maxHoras, totalRegistros, searchTerm) {
        const recordsTableBody = document.getElementById('recordsTableBody');
        
        if (!recordsTableBody) return;
        
        recordsTableBody.innerHTML = '';
        
        // Si no hay resultados de búsqueda
        if (registros.length === 0 && searchTerm !== '') {
            const row = document.createElement('tr');
            row.innerHTML = `<td colspan="${2 + maxHoras}" class="empty-search-message">
                <div style="padding: 30px 0;">
                    <svg style="width: 48px; height: 48px; margin-bottom: 15px; opacity: 0.5;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"></circle>
                        <path d="m21 21-4.35-4.35"></path>
                    </svg>
                    <p>No se encontraron resultados para "<strong>${escapeHtml(searchTerm)}</strong>"</p>
                    <p style="font-size: 0.85rem; margin-top: 8px;">Intenta con otro número de persona o nombre</p>
                </div>
            </td>`;
            recordsTableBody.appendChild(row);
            return;
        }
        
        // Si no hay registros en la fecha
        if (registros.length === 0) {
            const row = document.createElement('tr');
            row.innerHTML = '<td colspan="' + (2 + maxHoras) + '" class="empty-cell">No hay registros para esta fecha</td>';
            recordsTableBody.appendChild(row);
            return;
        }
        
        // Renderizar registros
        registros.forEach(registro => {
            const row = document.createElement('tr');
            row.setAttribute('data-persona-id', registro.codigo_persona);
            row.setAttribute('data-persona-nombre', registro.nombre.toLowerCase());
            
            // Resaltar coincidencias en el nombre
            let nombreMostrado = registro.nombre;
            if (searchTerm !== '' && registro.nombre.toLowerCase().includes(searchTerm)) {
                nombreMostrado = registro.nombre.replace(
                    new RegExp(`(${escapeRegExp(searchTerm)})`, 'gi'),
                    '<mark>$1</mark>'
                );
            }
            
            // Columns: Persona, ID
            let rowHTML = `
                <td>${nombreMostrado}</td>
                <td>${registro.codigo_persona}</td>
            `;
            
            // Agregar columnas de horas
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

    /**
     * Escapar caracteres especiales en regex
     */
    function escapeRegExp(string) {
        return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    /**
     * Escapar caracteres HTML
     */
    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, m => map[m]);
    }

    /**
     * Cargar y mostrar ausencias del día
     */
    function loadAbsencias(reportId) {
        const url = `/asistencia-personal/reportes/${reportId}/ausencias`;
        console.log('Fetching absencias from:', url);
        
        fetch(url, {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAbsenciasModal(data);
            } else {
                alert('Error al cargar ausencias: ' + (data.message || 'Error desconocido'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al cargar las ausencias: ' + error.message);
        });
    }

    /**
     * Mostrar modal de ausencias
     */
    function showAbsenciasModal(data) {
        const absenciasModal = document.getElementById('absenciasModal');
        const ausenciasTableBody = document.getElementById('ausenciasTableBody');
        
        if (!absenciasModal || !ausenciasTableBody) {
            console.error('Modal de ausencias no encontrado');
            return;
        }
        
        // Limpiar tabla
        ausenciasTableBody.innerHTML = '';
        
        // Llenar tabla con ausencias
        if (data.ausencias && data.ausencias.length > 0) {
            data.ausencias.forEach(ausencia => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${ausencia.nombre}</td>
                    <td style="text-align: center;">${ausencia.total_inasistencias}</td>
                    <td style="text-align: center;">
                        <button class="btn-ver-inasistencias" data-nombre="${ausencia.nombre}" data-fechas='${JSON.stringify(ausencia.fechas_inasistidas)}'>
                            Ver fechas
                        </button>
                    </td>
                `;
                ausenciasTableBody.appendChild(row);
            });
            
            // Agregar event listeners a los botones
            document.querySelectorAll('.btn-ver-inasistencias').forEach(btn => {
                btn.addEventListener('click', function() {
                    const nombre = this.getAttribute('data-nombre');
                    const fechas = JSON.parse(this.getAttribute('data-fechas'));
                    mostrarFechasInasistencias(nombre, fechas);
                });
            });
        } else {
            const row = document.createElement('tr');
            row.innerHTML = '<td colspan="3" class="empty-cell">No hay ausencias registradas</td>';
            ausenciasTableBody.appendChild(row);
        }
        
        // Mostrar modal
        absenciasModal.style.display = 'block';
        
        // Agregar botón Volver encima del header
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
                absenciasModal.style.display = 'none';
            });
            
            btnVolverDiv.appendChild(btnVolver);
            const tableWrapper = document.querySelector('.ausencias-table-wrapper');
            if (tableWrapper && tableWrapper.parentNode) {
                tableWrapper.parentNode.insertBefore(btnVolverDiv, tableWrapper);
            }
        }
        
        // Botón cerrar ausencias
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
            console.error('Modal de ver inasistencias no encontrado');
            return;
        }
        
        // Establecer título
        inasistenciasTitle.textContent = `Fechas de inasistencia - ${nombre}`;
        
        // Limpiar lista
        inasistenciasList.innerHTML = '';
        
        // Crear contenedor con botones
        const container = document.createElement('div');
        container.style.display = 'flex';
        container.style.flexDirection = 'column';
        container.style.gap = '10px';
        
        // Agregar fechas
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
        
        // Agregar botón Cerrar
        const botonesDiv = document.createElement('div');
        botonesDiv.style.display = 'flex';
        botonesDiv.style.gap = '10px';
        botonesDiv.style.marginTop = '20px';
        botonesDiv.style.justifyContent = 'center';
        
        // Botón Cerrar
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
        
        // Mostrar modal
        verInasistenciasModal.style.display = 'block';
        
        // Botón cerrar del header
        const btnClose = document.getElementById('btnCloseVerInasistencias');
        if (btnClose) {
            btnClose.onclick = function() {
                verInasistenciasModal.style.display = 'none';
            };
        }
    }
}

console.log('Asistencia Personal module loaded');