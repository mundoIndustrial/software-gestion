/**
 * Módulo de Edición de Registros - Asistencia Personal
 * Gestión del modal para editar marcas y horas extras
 */

// Inyectar estilos de animaciones
if (!document.getElementById('estilos-notificaciones-asistencia')) {
    const style = document.createElement('style');
    style.id = 'estilos-notificaciones-asistencia';
    style.textContent = `
        @keyframes slideIn {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(400px);
                opacity: 0;
            }
        }
    `;
    document.head.appendChild(style);
}

const AsistenciaEditarRegistro = (() => {
    let personasDisponibles = [];
    let personaSeleccionada = null;
    let fechaSeleccionada = null;
    let todasLasFechas = []; // Fechas disponibles en el reporte
    let idReporteGlobal = null;
    let marcasEditables = []; // Array de marcas que se pueden editar (HH:MM:SS)
    let marcasOriginales = []; // Array para guardar marcas originales para comparar

    /**
     * Convertir hora HH:MM:SS a formato 12 horas con AM/PM
     */
    function horaAFormatoAMPM(horaStr) {
        if (!horaStr) return '';
        
        const partes = horaStr.split(':');
        let horas = parseInt(partes[0]);
        const minutos = partes[1];
        const segundos = partes[2];
        
        const ampm = horas >= 12 ? 'PM' : 'AM';
        
        if (horas > 12) {
            horas -= 12;
        } else if (horas === 0) {
            horas = 12;
        }
        
        return `${String(horas).padStart(2, '0')}:${minutos} ${ampm}`;
    }

    /**
     * Mostrar notificación emergente mejorada
     */
    function mostrarNotificacion(mensaje, tipo = 'success', duracion = 4000) {
        // Crear contenedor de notificación
        const notificacion = document.createElement('div');
        notificacion.className = `notificacion-emergente notificacion-${tipo}`;
        notificacion.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${tipo === 'success' ? '#4caf50' : tipo === 'error' ? '#f44336' : '#2196F3'};
            color: white;
            padding: 16px 24px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 10000;
            animation: slideIn 0.3s ease;
            max-width: 400px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 12px;
        `;

        // Agregar icono según tipo
        let icono = '';
        if (tipo === 'success') {
            icono = '✓';
        } else if (tipo === 'error') {
            icono = '✕';
        } else {
            icono = 'ℹ';
        }

        notificacion.innerHTML = `
            <span style="font-size: 18px; font-weight: bold;">${icono}</span>
            <span>${mensaje}</span>
        `;

        document.body.appendChild(notificacion);

        // Añadir animación de salida
        setTimeout(() => {
            notificacion.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => {
                notificacion.remove();
            }, 300);
        }, duracion);
    }

    /**
     * Inicializar el módulo
     */
    function init(personas, idReporte = null, fechas = []) {
        personasDisponibles = personas;
        idReporteGlobal = idReporte;
        todasLasFechas = fechas && fechas.length > 0 ? fechas : [];
        console.log('✓ Módulo editar-registro inicializado con fechas:', todasLasFechas);
        setupEventListeners();
        limpiarFormulario();
    }

    /**
     * Configurar event listeners
     */
    function setupEventListeners() {
        const btnClose = document.getElementById('btnCloseEditarRegistro');
        const btnCancelar = document.getElementById('editarRegistroCancelarBtn');
        const btnGuardar = document.getElementById('editarRegistroGuardarBtn');
        const inputBusqueda = document.getElementById('editarRegistroBusquedaPersona');
        const selectFecha = document.getElementById('editarRegistroFechaSelect');

        if (btnClose) {
            btnClose.addEventListener('click', cerrarModal);
        }
        if (btnCancelar) {
            btnCancelar.addEventListener('click', cerrarModal);
        }
        if (btnGuardar) {
            btnGuardar.addEventListener('click', guardarCambios);
        }
        if (inputBusqueda) {
            inputBusqueda.addEventListener('input', buscarPersona);
        }
        if (selectFecha) {
            selectFecha.addEventListener('change', seleccionarFecha);
        }

        // Cerrar modal al hacer click fuera
        const modal = document.getElementById('editarRegistroModal');
        if (modal) {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    cerrarModal();
                }
            });
        }
    }

    /**
     * Buscar personas en tiempo real
     */
    function buscarPersona(e) {
        const valor = e.target.value.toLowerCase().trim();
        const contenedorResultados = document.getElementById('editarRegistroResultadosBusqueda');

        if (!contenedorResultados) return;

        if (valor.length < 1) {
            contenedorResultados.style.display = 'none';
            contenedorResultados.innerHTML = '';
            return;
        }

        const resultados = personasDisponibles.filter(persona => {
            const nombre = (persona.nombre || '').toLowerCase();
            const codigo = (persona.codigo_persona || '').toString().toLowerCase();
            return nombre.includes(valor) || codigo.includes(valor);
        });

        if (resultados.length === 0) {
            contenedorResultados.innerHTML = '<div style="padding: 12px; color: #999; text-align: center; font-size: 13px;">No se encontraron personas</div>';
        } else {
            contenedorResultados.innerHTML = resultados.map(persona => `
                <div style="padding: 12px; border-bottom: 1px solid #eee; cursor: pointer; transition: background 0.2s; display: flex; justify-content: space-between; align-items: center;" 
                     onmouseover="this.style.background='#f5f5f5'" 
                     onmouseout="this.style.background='white'"
                     onclick="AsistenciaEditarRegistro.seleccionarPersona('${persona.codigo_persona}', '${persona.nombre}')">
                    <div>
                        <strong style="color: #1a237e;">${persona.nombre}</strong><br>
                        <small style="color: #999;">Código: ${persona.codigo_persona}</small>
                    </div>
                    <span style="background: #e3f2fd; color: #1976d2; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold;">Seleccionar</span>
                </div>
            `).join('');
        }

        contenedorResultados.style.display = 'block';
    }

    /**
     * Seleccionar una persona
     */
    function seleccionarPersona(codigo, nombre) {
        personaSeleccionada = personasDisponibles.find(p => p.codigo_persona == codigo);

        if (!personaSeleccionada) {
            mostrarNotificacion('✕ No se pudo cargar la información de la persona', 'error', 3000);
            return;
        }

        // Actualizar UI
        document.getElementById('editarRegistroNombrePersona').textContent = nombre;
        document.getElementById('editarRegistroCodigoPersona').textContent = codigo;
        document.getElementById('editarRegistroPersonaSeleccionada').style.display = 'block';

        // Ocultar resultados de búsqueda
        document.getElementById('editarRegistroResultadosBusqueda').style.display = 'none';
        document.getElementById('editarRegistroBusquedaPersona').value = nombre;

        // Cargar fechas disponibles del reporte actual (no solo de registros de esta persona)
        // Si la persona tiene registros propios, usar esos; si no, usar las fechas del reporte
        const fechasPersona = Object.keys(personaSeleccionada.registros || {}).sort();
        
        if (fechasPersona.length === 0) {
            // La persona no tiene registros en este reporte, pero puede agregar horas extras
            mostrarNotificacion(`✓ Persona seleccionada: ${nombre} (sin registros previos)`, 'info', 2000);
            // Usar todas las fechas disponibles en el reporte para poder elegir
            todasLasFechas = window.todasLasFechasDelReporte || [];
        } else {
            mostrarNotificacion(`✓ Persona seleccionada: ${nombre} (${fechasPersona.length} fechas)`, 'info', 2000);
            todasLasFechas = fechasPersona;
        }
        
        cargarFechas();

        // Mostrar selector de fecha
        document.getElementById('editarRegistroFechaContainer').style.display = 'block';

        // Limpiar datos del día
        document.getElementById('editarRegistroDatosDelDia').style.display = 'none';
    }

    /**
     * Cargar fechas disponibles en el select
     */
    function cargarFechas() {
        const select = document.getElementById('editarRegistroFechaSelect');
        if (!select) return;

        select.innerHTML = '<option value="">-- Seleccionar fecha --</option>';
        todasLasFechas.forEach(fecha => {
            const option = document.createElement('option');
            option.value = fecha;
            option.textContent = fecha;
            select.appendChild(option);
        });
    }

    /**
     * Seleccionar una fecha
     */
    function seleccionarFecha(e) {
        fechaSeleccionada = e.target.value;

        if (!fechaSeleccionada || !personaSeleccionada) {
            document.getElementById('editarRegistroDatosDelDia').style.display = 'none';
            return;
        }

        cargarDatosDelDia();
        mostrarNotificacion(`✓ Fecha seleccionada: ${fechaSeleccionada}`, 'info', 2000);
    }

    /**
     * Cargar datos del día seleccionado
     */
    function cargarDatosDelDia() {
        // Validar que personaSeleccionada y sus registros existan
        if (!personaSeleccionada) {
            console.error('Error: personaSeleccionada es null');
            return;
        }

        if (!personaSeleccionada.registros) {
            console.warn('Advertencia: persona no tiene registros cargados. Inicializando estructura...');
            personaSeleccionada.registros = {};
        }

        const registrosDelDia = personaSeleccionada.registros[fechaSeleccionada] || [];

        // Limpiar marcas editables
        marcasEditables = [];

        // Mostrar sección incluso si no hay registros (para agregar horas)
        if (registrosDelDia.length === 0) {
            // Mostrar la sección de agregar horas extras aunque no haya registros
            document.getElementById('editarRegistroDatosDelDia').style.display = 'block';
            renderizarMarcas();
            
            // Mostrar total de horas trabajadas como 0
            document.getElementById('editarRegistroTotalHoras').textContent = '00:00:00';
            
            return;
        }

        // Extraer horas
        const horas = [];
        registrosDelDia.forEach(registro => {
            if (registro.horas && typeof registro.horas === 'object') {
                Object.values(registro.horas).forEach(hora => {
                    if (hora) horas.push(hora);
                });
            }
        });

        // Cargar en marcasEditables
        marcasEditables = [...horas].sort();
        marcasOriginales = [...marcasEditables]; // Guardar copia de las marcas originales

        // Renderizar marcas con controles
        renderizarMarcas();

        // Calcular total de horas trabajadas
        const totalMinutos = calcularTotalMinutos(marcasEditables, fechaSeleccionada, personaSeleccionada.idRol);
        const totalHoras = minutosAHoras(totalMinutos);
        document.getElementById('editarRegistroTotalHoras').textContent = totalHoras;

        // Limpiar campos de hora extra y novedad
        document.getElementById('editarRegistroAgregarHoraExtra').value = '';
        document.getElementById('editarRegistroNovedad').value = '';
        document.getElementById('editarRegistroNuevaMarca').value = '';

        // Mostrar sección de datos del día
        document.getElementById('editarRegistroDatosDelDia').style.display = 'block';
        
        // Agregar listener al botón de agregar marca
        const btnAgregarMarca = document.getElementById('editarRegistroAgregarMarcaBtn');
        if (btnAgregarMarca) {
            btnAgregarMarca.onclick = agregarNuevaMarca;
        }
    }

    /**
     * Calcular total de minutos trabajados
     */
    function calcularTotalMinutos(horas, fecha, idRol) {
        if (horas.length === 0) return 0;

        const minutosArray = horas.map(hora => {
            const [h, m, s] = hora.split(':').map(Number);
            return h * 60 + m + (s || 0) / 60;
        }).sort((a, b) => a - b);

        // Limpiar duplicados
        const horasValidas = [];
        for (let i = 0; i < minutosArray.length; i++) {
            if (horasValidas.length === 0 || Math.abs(minutosArray[i] - horasValidas[horasValidas.length - 1]) >= 2) {
                horasValidas.push(minutosArray[i]);
            }
        }

        // Detectar si es sábado
        const fechaObj = new Date(fecha + 'T00:00:00');
        const esSabado = fechaObj.getDay() === 6;

        // Lógica especial para rol 21 entre semana
        if (!esSabado && idRol === 21) {
            // Para rol 21 entre semana: lógica especial según cantidad de marcas
            if (horasValidas.length === 4) {
                // Con 4 marcas: calcular como rol normal (bloque mañana + bloque tarde)
                const bloqueMañana = horasValidas[1] - horasValidas[0];
                const bloqueTarde = horasValidas[3] - horasValidas[2];
                return bloqueMañana + bloqueTarde;
            } else if (horasValidas.length >= 2) {
                // Con 2 o 3 marcas: contar desde primera a última marca
                return horasValidas[horasValidas.length - 1] - horasValidas[0];
            }
        } else {
            // Para sábado o otros roles: usar la primera y última marca
            if (horasValidas.length >= 2) {
                return horasValidas[horasValidas.length - 1] - horasValidas[0];
            }
        }

        return 0;
    }

    /**
     * Convertir minutos a formato HH:MM:SS
     */
    function minutosAHoras(totalMinutos) {
        const horas = Math.floor(totalMinutos / 60);
        const minutos = Math.floor(totalMinutos % 60);
        const segundos = Math.round(((totalMinutos % 1) * 60));

        return `${String(horas).padStart(2, '0')}:${String(minutos).padStart(2, '0')}:${String(segundos).padStart(2, '0')}`;
    }

    /**
     * Actualizar una marca (después de que el usuario la edite)
     */
    function actualizarMarca(indice, nuevoValor) {
        console.log(`Actualizando marca ${indice} a ${nuevoValor}`);
        if (indice >= 0 && indice < marcasEditables.length) {
            marcasEditables[indice] = nuevoValor;
            recalcularTotalMarcas();
        }
    }

    /**
     * Recalcular el total de marcas cuando se edita alguna
     */
    function recalcularTotalMarcas() {
        if (!personaSeleccionada || !fechaSeleccionada) return;

        if (marcasEditables.length > 0) {
            const totalMinutos = calcularTotalMinutos(marcasEditables, fechaSeleccionada, personaSeleccionada.idRol);
            const totalHoras = minutosAHoras(totalMinutos);
            document.getElementById('editarRegistroTotalHoras').textContent = totalHoras;
        } else {
            document.getElementById('editarRegistroTotalHoras').textContent = '00:00:00';
        }
    }

    /**
     * Guardar cambios (marcas editadas y/o horas extras)
     */
    function guardarCambios() {
        const horaExtraAgregar = parseFloat(document.getElementById('editarRegistroAgregarHoraExtra').value) || 0;
        const novedad = document.getElementById('editarRegistroNovedad').value.trim();

        // Verificar si hubo cambios en marcas
        const huboChangesMarcas = JSON.stringify(marcasEditables) !== JSON.stringify(marcasOriginales);

        // Validación: debe haber cambios en marcas o horas extras
        if (!huboChangesMarcas && horaExtraAgregar === 0 && !novedad) {
            mostrarNotificacion('Por favor edita marcas, ingresa horas extras o una novedad', 'error', 3000);
            return;
        }

        if (!personaSeleccionada || !fechaSeleccionada) {
            mostrarNotificacion('Selecciona una persona y una fecha', 'error', 3000);
            return;
        }

        if (!idReporteGlobal) {
            mostrarNotificacion('Error: ID de reporte no disponible', 'error', 3000);
            return;
        }

        // Mostrar estado de carga
        const btnGuardar = document.getElementById('editarRegistroGuardarBtn');
        const textoOriginal = btnGuardar.textContent;
        btnGuardar.textContent = 'Guardando...';
        btnGuardar.disabled = true;

        // Si hay cambios en marcas, guardarlas primero
        if (huboChangesMarcas) {
            guardarMarcasEditadas()
                .then(() => {
                    // Después guardar horas extras si las hay
                    if (horaExtraAgregar > 0 || novedad) {
                        guardarHorasExtras();
                    } else {
                        mostrarNotificacion('✓ Marcas actualizadas correctamente', 'success', 3000);
                        setTimeout(() => {
                            cerrarModal();
                        }, 1500);
                    }
                })
                .catch(error => {
                    console.error('Error al guardar marcas:', error);
                    mostrarNotificacion('✕ Error al guardar marcas: ' + error.message, 'error', 4000);
                    btnGuardar.textContent = textoOriginal;
                    btnGuardar.disabled = false;
                });
        } else if (horaExtraAgregar > 0 || novedad) {
            // Solo guardar horas extras
            guardarHorasExtras();
        }
    }

    /**
     * Guardar marcas editadas
     */
    function guardarMarcasEditadas() {
        return new Promise((resolve, reject) => {
            const datos = {
                codigo_persona: personaSeleccionada.codigo_persona,
                id_reporte: idReporteGlobal,
                fecha: fechaSeleccionada,
                marcas: marcasEditables
            };

            console.log('Guardando marcas editadas:', datos);

            fetch('/asistencia-personal/guardar-marcas-editadas', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                body: JSON.stringify(datos)
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    resolve(data);
                } else {
                    reject(new Error(data.message || 'Error al guardar marcas'));
                }
            })
            .catch(error => {
                reject(error);
            });
        });
    }

    /**
     * Guardar horas extras agregadas
     */
    function guardarHorasExtras() {
        const horaExtraAgregar = parseFloat(document.getElementById('editarRegistroAgregarHoraExtra').value) || 0;
        const novedad = document.getElementById('editarRegistroNovedad').value.trim();
        const btnGuardar = document.getElementById('editarRegistroGuardarBtn');
        const textoOriginal = btnGuardar.textContent;

        const datos = {
            codigo_persona: personaSeleccionada.codigo_persona,
            id_reporte: idReporteGlobal,
            fecha: fechaSeleccionada,
            horas_agregadas: horaExtraAgregar,
            novedad: novedad || null
        };

        console.log('Guardando horas extras:', datos);

        fetch('/asistencia-personal/guardar-hora-extra-agregada', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: JSON.stringify(datos)
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                mostrarNotificacion('✓ Cambios guardados correctamente', 'success', 3000);
                
                // Actualizar tabla en tiempo real SOLO si se agregaron horas extras
                // El indicador visual aparecerá solo para horas extras
                actualizarTablaEnTiempoReal(personaSeleccionada, fechaSeleccionada, horaExtraAgregar, novedad, true);
                
                // Limpiar formulario
                document.getElementById('editarRegistroAgregarHoraExtra').value = '';
                document.getElementById('editarRegistroNovedad').value = '';
                
                // Cerrar modal después de 1 segundo
                setTimeout(() => {
                    cerrarModal();
                }, 1500);
            } else {
                mostrarNotificacion('⚠ Error: ' + (data.message || 'Error desconocido'), 'error', 4000);
                btnGuardar.textContent = textoOriginal;
                btnGuardar.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error al guardar horas extras:', error);
            mostrarNotificacion('✕ Error de conexión: ' + error.message, 'error', 4000);
            btnGuardar.textContent = textoOriginal;
            btnGuardar.disabled = false;
        });
    }

    /**
     * Actualizar tabla en tiempo real sin recargar
     * mostrarIndicador: true solo si se agregaron horas extras (no solo se editaron marcas)
     */
    function actualizarTablaEnTiempoReal(persona, fecha, horaExtraAgregar, novedad, mostrarIndicador = false) {
        // Si no se agregaron horas extras (solo se editaron marcas), no actualizar tabla
        if (horaExtraAgregar === 0) {
            return;
        }

        // Buscar la fila en la tabla principal
        const tabla = document.getElementById('totalHorasExtrasTable');
        if (!tabla) return;

        const tbody = tabla.querySelector('tbody');
        const filas = tabla.querySelectorAll('tbody tr:not(:last-child)'); // Excluir fila de TOTAL
        
        let filaEncontrada = false;
        
        filas.forEach(fila => {
            const tdId = fila.querySelector('td:first-child');
            if (tdId && tdId.textContent == persona.codigo_persona) {
                filaEncontrada = true;
                // Encontramos la fila de esta persona
                // Actualizar la celda correspondiente a esta fecha
                const todasLasColumnasEncabezado = tabla.querySelectorAll('thead th');
                let columnaFecha = -1;
                
                // Encontrar la columna de esta fecha
                todasLasColumnasEncabezado.forEach((th, idx) => {
                    const dia = fecha.split('-')[2];
                    if (th.textContent.trim() === dia) {
                        columnaFecha = idx;
                    }
                });
                
                if (columnaFecha > -1) {
                    const celdas = fila.querySelectorAll('td');
                    if (celdas[columnaFecha]) {
                        const celdaFecha = celdas[columnaFecha];
                        // Actualizar el contenido con la hora agregada
                        const texto = celdaFecha.textContent.trim();
                        const numeroActual = parseInt(texto) || 0;
                        const nuevoNumero = numeroActual + Math.round(horaExtraAgregar);
                        celdaFecha.textContent = nuevoNumero.toString();
                        
                        // Mostrar efecto visual SOLO si se agregaron horas extras
                        if (mostrarIndicador) {
                            celdaFecha.style.backgroundColor = '#d4edda';
                            celdaFecha.style.color = '#155724';
                            celdaFecha.style.fontWeight = 'bold';
                            celdaFecha.style.borderLeft = '4px solid #28a745';
                            
                            // Remover efecto después de 2 segundos
                            setTimeout(() => {
                                celdaFecha.style.transition = 'all 0.3s ease';
                                celdaFecha.style.backgroundColor = 'transparent';
                                celdaFecha.style.borderLeft = 'none';
                            }, 2000);
                        }
                    }
                }
                
                // Actualizar el total de la persona (penúltima columna)
                const celdas = fila.querySelectorAll('td');
                const celdaTotal = celdas[celdas.length - 2]; // Antes de la columna de Valor
                if (celdaTotal) {
                    const totalActual = parseInt(celdaTotal.textContent) || 0;
                    const nuevoTotal = totalActual + Math.round(horaExtraAgregar);
                    celdaTotal.textContent = nuevoTotal.toString();
                    
                    // Mostrar efecto visual SOLO si se agregaron horas extras
                    if (mostrarIndicador) {
                        celdaTotal.style.backgroundColor = '#bbdefb';
                        celdaTotal.style.transition = 'all 0.3s ease';
                        
                        setTimeout(() => {
                            celdaTotal.style.backgroundColor = '#e8f0f7';
                        }, 2000);
                    }
                }
            }
        });
        
        // Si la persona NO fue encontrada, agregar una fila nueva
        if (!filaEncontrada) {
            agregarNuevaFilaPersona(tabla, persona, fecha, horaExtraAgregar, mostrarIndicador);
        }

        // Actualizar el total general
        const filaTotal = tabla.querySelector('tbody tr:last-child');
        if (filaTotal) {
            const celdas = filaTotal.querySelectorAll('td');
            const celdaTotalGeneral = celdas[celdas.length - 2]; // Antes de la columna de Valor
            if (celdaTotalGeneral) {
                const totalActual = parseInt(celdaTotalGeneral.textContent) || 0;
                const nuevoTotal = totalActual + Math.round(horaExtraAgregar);
                celdaTotalGeneral.textContent = nuevoTotal.toString();
                
                // Mostrar efecto visual SOLO si se agregaron horas extras
                if (mostrarIndicador) {
                    celdaTotalGeneral.style.backgroundColor = '#90caf9';
                    celdaTotalGeneral.style.transition = 'all 0.3s ease';
                    
                    setTimeout(() => {
                        celdaTotalGeneral.style.backgroundColor = '#1e5ba8';
                    }, 2000);
                }
            }
        }
        
        // Recargar datos del módulo Total Horas Extras si existe
        if (window.AsistenciaTotalHorasExtras && window.AsistenciaTotalHorasExtras.actualizarHorasAgregadas) {
            window.AsistenciaTotalHorasExtras.actualizarHorasAgregadas(persona.codigo_persona);
        }
    }

    /**
     * Agregar nueva fila de persona en la tabla
     * mostrarIndicador: true solo si se agregaron horas extras (no solo se editaron marcas)
     */
    function agregarNuevaFilaPersona(tabla, persona, fecha, horaExtraAgregar, mostrarIndicador = false) {
        const tbody = tabla.querySelector('tbody');
        const thead = tabla.querySelector('thead');
        
        // Crear nueva fila
        const tr = document.createElement('tr');
        tr.setAttribute('data-persona-id', persona.codigo_persona);
        tr.setAttribute('data-persona-nombre', persona.nombre.toLowerCase());
        tr.style.backgroundColor = '#fffacd';
        tr.style.borderLeft = '4px solid #ff9800';
        
        // Celda ID
        const tdId = document.createElement('td');
        tdId.textContent = persona.codigo_persona;
        tdId.style.fontWeight = '600';
        tr.appendChild(tdId);
        
        // Celda Nombre
        const tdNombre = document.createElement('td');
        tdNombre.textContent = persona.nombre;
        tdNombre.style.fontWeight = '600';
        tr.appendChild(tdNombre);
        
        // Celda Novedades
        const tdNovedades = document.createElement('td');
        tdNovedades.textContent = 'Nuevo registro';
        tdNovedades.style.color = '#ff6f00';
        tdNovedades.style.fontSize = '12px';
        tr.appendChild(tdNovedades);
        
        // Obtener todas las fechas del encabezado
        const ths = thead.querySelectorAll('th');
        let columnaFechaTarget = -1;
        const dia = fecha.split('-')[2];
        
        ths.forEach((th, idx) => {
            if (th.textContent.trim() === dia) {
                columnaFechaTarget = idx;
            }
        });
        
        // Agregar celdas por cada fecha
        let indexFecha = 0;
        ths.forEach((th, thIdx) => {
            // Saltar ID, Nombre, Novedades
            if (thIdx < 3) return;
            
            const td = document.createElement('td');
            
            if (thIdx === columnaFechaTarget) {
                // Esta es la fecha donde se agregó la hora extra
                td.textContent = Math.round(horaExtraAgregar).toString();
                
                // Aplicar estilos SOLO si mostrarIndicador es true
                if (mostrarIndicador) {
                    td.style.backgroundColor = '#d4edda';
                    td.style.color = '#155724';
                    td.style.fontWeight = 'bold';
                    td.style.borderLeft = '4px solid #28a745';
                    td.style.borderRadius = '4px';
                }
            } else {
                td.textContent = '-';
            }
            
            tr.appendChild(td);
        });
        
        // Celda Total
        const tdTotal = document.createElement('td');
        tdTotal.textContent = Math.round(horaExtraAgregar).toString();
        tdTotal.style.fontWeight = 'bold';
        
        // Aplicar estilos SOLO si mostrarIndicador es true
        if (mostrarIndicador) {
            tdTotal.style.backgroundColor = '#c8e6c9';
        }
        tdTotal.style.borderRadius = '4px';
        tr.appendChild(tdTotal);
        
        // Celda Valor
        const tdValor = document.createElement('td');
        tdValor.textContent = '';
        tr.appendChild(tdValor);
        
        // Insertar la fila antes de la fila de TOTAL
        const filaTotal = tbody.querySelector('tr:last-child');
        tbody.insertBefore(tr, filaTotal);
        
        // Mensaje visual
        mostrarNotificacion(`✓ Persona "${persona.nombre}" agregada a la tabla`, 'success', 3000);
    }

    /**
     * Renderizar las marcas editables
     */
    function renderizarMarcas() {
        const contenedor = document.getElementById('editarRegistroMarcas');
        if (!contenedor) return;

        if (marcasEditables.length === 0) {
            contenedor.innerHTML = '<p style="color: #999; text-align: center; margin: 20px 0;">Sin marcas registradas</p>';
            return;
        }

        let html = '<div style="display: flex; flex-direction: column; gap: 8px;">';
        
        marcasEditables.forEach((marca, indice) => {
            const esUltima = indice === marcasEditables.length - 1;
            const esPrimera = indice === 0;
            const horaAMPM = horaAFormatoAMPM(marca);
            // Extraer solo HH:MM para el input de tipo time (sin segundos)
            const horaFormato = marca.substring(0, 5); // "HH:MM"
            
            html += `
                <div style="display: flex; align-items: center; gap: 8px; padding: 8px; background: white; border: 1px solid #ddd; border-radius: 4px; justify-content: space-between;">
                    <div style="flex: 1; display: flex; align-items: center; gap: 8px;">
                        <span style="font-weight: bold; color: #666; min-width: 30px; text-align: center;">Marca ${indice + 1}:</span>
                        <input 
                            type="time" 
                            value="${horaFormato}" 
                            class="marca-editable"
                            data-indice="${indice}"
                            style="padding: 4px 8px; border: 1px solid #ccc; border-radius: 3px; width: 120px;"
                        >
                        <span style="color: #1976d2; font-weight: 600; background: #e3f2fd; padding: 4px 8px; border-radius: 3px; font-size: 12px;">${horaAMPM}</span>
                    </div>
                    <div style="display: flex; gap: 4px;">
                        ${!esPrimera ? `<button class="btn-marca btn-arriba" data-indice="${indice}" style="padding: 4px 8px; font-size: 12px;">▲ Arriba</button>` : ''}
                        ${!esUltima ? `<button class="btn-marca btn-abajo" data-indice="${indice}" style="padding: 4px 8px; font-size: 12px;">▼ Abajo</button>` : ''}
                        <button class="btn-marca btn-eliminar" data-indice="${indice}" style="padding: 4px 8px; font-size: 12px; background-color: #f44336; color: white;">✕ Eliminar</button>
                    </div>
                </div>
            `;
        });

        html += '</div>';
        contenedor.innerHTML = html;

        // Agregar event listeners
        document.querySelectorAll('.marca-editable').forEach(input => {
            input.addEventListener('change', (e) => {
                const indice = parseInt(e.target.dataset.indice);
                // Convertir HH:MM a HH:MM:SS (agregar :00 para segundos)
                const nuevoValor = e.target.value + ':00';
                marcasEditables[indice] = nuevoValor;
                recalcularTotalMarcas();
            });
        });

        document.querySelectorAll('.btn-arriba').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const indice = parseInt(e.target.dataset.indice);
                if (indice > 0) {
                    [marcasEditables[indice - 1], marcasEditables[indice]] = [marcasEditables[indice], marcasEditables[indice - 1]];
                    renderizarMarcas();
                    recalcularTotalMarcas();
                }
            });
        });

        document.querySelectorAll('.btn-abajo').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const indice = parseInt(e.target.dataset.indice);
                if (indice < marcasEditables.length - 1) {
                    [marcasEditables[indice], marcasEditables[indice + 1]] = [marcasEditables[indice + 1], marcasEditables[indice]];
                    renderizarMarcas();
                    recalcularTotalMarcas();
                }
            });
        });

        document.querySelectorAll('.btn-eliminar').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const indice = parseInt(e.target.dataset.indice);
                marcasEditables.splice(indice, 1);
                renderizarMarcas();
                recalcularTotalMarcas();
                mostrarNotificacion('✓ Marca eliminada', 'success', 2000);
            });
        });
    }

    /**
     * Agregar nueva marca
     */
    function agregarNuevaMarca() {
        const inputNuevaMarca = document.getElementById('editarRegistroNuevaMarca');
        if (!inputNuevaMarca.value) {
            mostrarNotificacion('✕ Por favor ingresa una hora', 'error', 2000);
            return;
        }

        const nuevaMarca = inputNuevaMarca.value + ':00'; // Convertir HH:MM a HH:MM:SS
        
        if (marcasEditables.includes(nuevaMarca)) {
            mostrarNotificacion('✕ Esta hora ya fue agregada', 'error', 2000);
            return;
        }

        marcasEditables.push(nuevaMarca);
        marcasEditables.sort(); // Ordenar automáticamente
        
        const horaAMPM = horaAFormatoAMPM(nuevaMarca);
        mostrarNotificacion(`✓ Marca agregada: ${horaAMPM}`, 'success', 3000);
        
        inputNuevaMarca.value = '';
        renderizarMarcas();
        recalcularTotalMarcas();
        mostrarNotificacion('✓ Marca agregada correctamente', 'success', 2000);
    }

    /**
     * Limpiar formulario
     */
    function limpiarFormulario() {
        document.getElementById('editarRegistroBusquedaPersona').value = '';
        document.getElementById('editarRegistroResultadosBusqueda').innerHTML = '';
        document.getElementById('editarRegistroResultadosBusqueda').style.display = 'none';
        document.getElementById('editarRegistroPersonaSeleccionada').style.display = 'none';
        document.getElementById('editarRegistroFechaContainer').style.display = 'none';
        document.getElementById('editarRegistroDatosDelDia').style.display = 'none';
        document.getElementById('editarRegistroAgregarHoraExtra').value = '';
        document.getElementById('editarRegistroNovedad').value = '';
        document.getElementById('editarRegistroNuevaMarca').value = '';

        personaSeleccionada = null;
        fechaSeleccionada = null;
        todasLasFechas = [];
        marcasEditables = [];
    }

    /**
     * Cerrar modal
     */
    function cerrarModal() {
        const modal = document.getElementById('editarRegistroModal');
        if (modal) {
            modal.style.display = 'none';
        }
        limpiarFormulario();
    }

    // Exponer métodos públicos
    return {
        init,
        seleccionarPersona,
        actualizarMarca,
        recalcularTotalMarcas,
        cerrarModal
    };
})();
