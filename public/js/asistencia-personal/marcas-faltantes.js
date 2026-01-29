/**
 * Módulo de Marcas Faltantes - Asistencia Personal
 * Gestión completa de personas con solo 1 marca en el día
 */

const AsistenciaMarcasFaltantes = (() => {
    let reporteActualData = null;
    let registrosMarcas = {}; // { "codigo_persona_fecha": { horas: [...] } }

    /**
     * Cargar y mostrar marcas faltantes del reporte
     */
    function cargar(reporte) {
        reporteActualData = reporte;
        const modal = document.getElementById('marcasFaltantesModal');

        if (!modal) {
            console.error('Modal de marcas faltantes no encontrado');
            return;
        }

        cargarDatos(reporte);
        modal.style.display = 'block';

        // Botón de cierre en la esquina superior
        const btnCerrar = document.getElementById('btnCerrarMarcasFaltantes');
        if (btnCerrar) {
            btnCerrar.onclick = function() {
                modal.style.display = 'none';
            };
        }

        const closeBtn = modal.querySelector('.btn-modal-close-detail');
        if (closeBtn) {
            closeBtn.onclick = function() {
                modal.style.display = 'none';
            };
        }

        const btnGuardar = document.getElementById('btnGuardarTodasMarcas');
        if (btnGuardar) {
            btnGuardar.onclick = function() {
                guardarTodasMarcas();
            };

            btnGuardar.addEventListener('mouseover', function() {
                if (!this.disabled) {
                    this.style.transform = 'translateY(-2px)';
                    this.style.boxShadow = '0 4px 12px rgba(0, 123, 255, 0.35)';
                }
            });

            btnGuardar.addEventListener('mouseout', function() {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = '0 2px 8px rgba(0, 123, 255, 0.25)';
            });
        }
    }

    /**
     * Cargar datos y construir tabla
     */
    function cargarDatos(reporte) {
        registrosMarcas = {};
        let registrosMarcaFaltante = [];

        if (reporte && reporte.registros_por_persona && reporte.registros_por_persona.length > 0) {
            const registrosPorPersonaYFecha = {};

            // Agrupar por persona y fecha
            reporte.registros_por_persona.forEach(registro => {
                const clave = `${registro.codigo_persona}_${registro.fecha}`;
                if (!registrosPorPersonaYFecha[clave]) {
                    registrosPorPersonaYFecha[clave] = {
                        codigo_persona: registro.codigo_persona,
                        nombre: registro.nombre,
                        fecha: registro.fecha,
                        horas: registro.horas || {}
                    };
                }
            });

            // Encontrar aquellos con solo 1 marca
            Object.entries(registrosPorPersonaYFecha).forEach(([clave, registro]) => {
                const horasArray = Object.values(registro.horas).filter(hora => hora && hora.trim() !== '');
                if (horasArray.length === 1) {
                    registrosMarcaFaltante.push({
                        clave: clave,
                        codigo_persona: registro.codigo_persona,
                        nombre: registro.nombre,
                        fecha: registro.fecha,
                        hora_registrada: horasArray[0]
                    });

                    // Inicializar almacenamiento de marcas para esta persona
                    registrosMarcas[clave] = {
                        codigo_persona: registro.codigo_persona,
                        fecha: registro.fecha,
                        horas: [horasArray[0]] // Almacenar la hora registrada
                    };
                }
            });
        }

        construirTabla(registrosMarcaFaltante);
    }

    /**
     * Construir tabla dinámicamente
     */
    function construirTabla(registros) {
        const container = document.getElementById('marcasFaltantesContainer');
        if (!container) return;

        if (registros.length === 0) {
            container.innerHTML = '<div style="text-align: center; padding: 40px; color: #999;">No hay personas con solo 1 marca</div>';
            return;
        }

        let html = '<table style="width: 100%; border-collapse: collapse;">';
        html += '<thead><tr style="background-color: #f8f9fa; border-bottom: 2px solid #dee2e6;">';
        html += '<th style="padding: 12px 15px; text-align: center; font-weight: 600; color: #2c3e50; min-width: 50px;">ID</th>';
        html += '<th style="padding: 12px 15px; text-align: left; font-weight: 600; color: #2c3e50; min-width: 200px;">Persona</th>';
        html += '<th style="padding: 12px 15px; text-align: center; font-weight: 600; color: #2c3e50; min-width: 100px;">Fecha</th>';
        html += '<th style="padding: 12px 15px; text-align: center; font-weight: 600; color: #2c3e50; min-width: 120px;">Marca Registrada</th>';

        // Calcular máximo de marcas para crear columnas
        const maxMarcas = Math.max(...registros.map(r => registrosMarcas[r.clave].horas.length));

        // Columnas dinámicas para nuevas marcas
        for (let i = 1; i < maxMarcas; i++) {
            html += `<th style="padding: 12px 15px; text-align: center; font-weight: 600; color: #2c3e50; min-width: 120px; background-color: #e8f5e9;">Marca ${i + 1}</th>`;
        }

        // Columna para botón agregar marca
        html += '<th style="padding: 12px 15px; text-align: center; font-weight: 600; color: #666; min-width: 130px;"></th>';

        html += '</tr></thead><tbody>';

        // Filas
        registros.forEach((registro) => {
            const data = registrosMarcas[registro.clave];
            const clave = registro.clave;

            html += `<tr style="border-bottom: 1px solid #e0e0e0; transition: background-color 0.2s;" onmouseover="this.style.backgroundColor='#f8f9fa'" onmouseout="this.style.backgroundColor='transparent'">`;

            // ID
            html += `<td style="padding: 12px 15px; text-align: center; color: #555; font-weight: 500;">${registro.codigo_persona}</td>`;

            // Nombre
            html += `<td style="padding: 12px 15px; text-align: left; font-weight: 500; color: #2c3e50;">${registro.nombre}</td>`;

            // Fecha
            html += `<td style="padding: 12px 15px; text-align: center; color: #555;">${registro.fecha}</td>`;

            // Marca Registrada (primera marca - inmutable)
            html += `<td style="padding: 12px 15px; text-align: center; color: #2c3e50; font-weight: 600;">${data.horas[0]}</td>`;

            // Marcas adicionales (editar/agregar)
            for (let i = 1; i < maxMarcas; i++) {
                if (i < data.horas.length) {
                    html += `<td style="padding: 12px 15px; text-align: center; background-color: #f0f8f0; position: relative;">`;
                    html += `<div class="celda-hora" data-clave="${clave}" data-index="${i}" style="cursor: pointer; padding: 6px; border-radius: 4px; border: 2px solid #ddd; background: white; transition: all 0.2s; font-weight: 600; display: flex; align-items: center; justify-content: space-between; gap: 6px;">`;
                    html += `<div style="flex: 1;">`;
                    html += generarSelectorHora(data.horas[i], clave, i);
                    html += `</div>`;
                    html += `<button class="btn-eliminar-marca" data-clave="${clave}" data-index="${i}" style="padding: 2px 6px; background: #dc3545; color: white; border: none; border-radius: 3px; cursor: pointer; font-size: 0.75rem; font-weight: 700; transition: all 0.2s;" title="Eliminar marca">✕</button>`;
                    html += `</div></td>`;
                } else {
                    html += `<td style="padding: 12px 15px; text-align: center; background-color: #f0f8f0;"></td>`;
                }
            }

            // Columna Botón Agregar Marca
            html += `<td style="padding: 12px 15px; text-align: center;">`;
            if (data.horas.length < 5) {
                html += `<button class="btn-agregar-marca" data-clave="${clave}" style="padding: 8px 14px; background: linear-gradient(135deg, #17a2b8 0%, #20c997 100%); color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 0.8rem; font-weight: 600; transition: all 0.3s ease; box-shadow: 0 2px 8px rgba(23, 162, 184, 0.25); white-space: nowrap;">+ Agregar</button>`;
            }
            html += `</td>`;

            html += `</tr>`;
        });

        html += '</tbody></table>';

        container.innerHTML = html;

        // Agregar listeners
        document.querySelectorAll('.btn-agregar-marca').forEach(btn => {
            btn.addEventListener('click', function() {
                const clave = this.getAttribute('data-clave');
                agregarMarca(clave);
            });

            btn.addEventListener('mouseover', function() {
                this.style.transform = 'translateY(-2px)';
                this.style.boxShadow = '0 4px 12px rgba(23, 162, 184, 0.35)';
            });

            btn.addEventListener('mouseout', function() {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = '0 2px 8px rgba(23, 162, 184, 0.25)';
            });
        });

        // Listeners para selectors de hora
        document.querySelectorAll('.celda-hora').forEach(celda => {
            abrirSelectorHora(celda);
        });

        // Listeners para botones de eliminar
        document.querySelectorAll('.btn-eliminar-marca').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                const clave = this.getAttribute('data-clave');
                const index = parseInt(this.getAttribute('data-index'));
                eliminarMarca(clave, index);
            });

            btn.addEventListener('mouseover', function() {
                this.style.background = '#c82333';
                this.style.transform = 'scale(1.1)';
            });

            btn.addEventListener('mouseout', function() {
                this.style.background = '#dc3545';
                this.style.transform = 'scale(1)';
            });
        });
    }

    /**
     * Generar HTML del selector de hora
     */
    function generarSelectorHora(hora, clave, index) {
        let html = `<div style="display: flex; gap: 2px; align-items: center; justify-content: center;">`;
        html += `<select class="hora-select" data-clave="${clave}" data-index="${index}" style="width: 50px; padding: 4px 2px; border: 1px solid #ddd; border-radius: 4px; font-weight: 700; text-align: center; cursor: pointer; font-size: 0.9rem;">`;
        html += `<option value="">--</option>`;

        for (let i = 0; i < 24; i++) {
            const h = String(i).padStart(2, '0');
            const selected = hora.startsWith(h) ? 'selected' : '';
            html += `<option value="${h}" ${selected}>${h}</option>`;
        }

        html += `</select>`;
        html += `:`;
        const minutos = hora.substring(3, 5);
        html += `<input type="number" class="minutos-input" data-clave="${clave}" data-index="${index}" min="0" max="59" value="${minutos}" style="width: 38px; padding: 4px 2px; border: 1px solid #ddd; border-radius: 4px; font-weight: 700; text-align: center; font-size: 0.9rem;" />`;
        html += `</div>`;

        return html;
    }

    /**
     * Abrir selector de hora en una celda
     */
    function abrirSelectorHora(celdaElement) {
        const clave = celdaElement.getAttribute('data-clave');
        const index = parseInt(celdaElement.getAttribute('data-index'));
        const horaSelect = celdaElement.querySelector('.hora-select');
        const minutosInput = celdaElement.querySelector('.minutos-input');

        if (horaSelect) {
            horaSelect.addEventListener('change', function() {
                const hora = this.value;
                if (hora) {
                    const mins = minutosInput.value || '00';
                    registrosMarcas[clave].horas[index] = `${hora}:${mins}:00`;
                    celdaElement.style.borderColor = '#28a745';
                }
            });
        }

        if (minutosInput) {
            minutosInput.addEventListener('change', function() {
                let val = parseInt(this.value) || 0;
                if (val < 0) val = 0;
                if (val > 59) val = 59;
                this.value = String(val).padStart(2, '0');

                const hora = horaSelect.value;
                if (hora) {
                    registrosMarcas[clave].horas[index] = `${hora}:${this.value}:00`;
                    celdaElement.style.borderColor = '#28a745';
                }
            });

            minutosInput.addEventListener('focus', function() {
                celdaElement.style.borderColor = '#28a745';
            });

            minutosInput.addEventListener('blur', function() {
                if (!this.value) this.value = '00';
            });
        }
    }

    /**
     * Agregar nueva marca
     */
    function agregarMarca(clave) {
        if (registrosMarcas[clave].horas.length < 5) {
            registrosMarcas[clave].horas.push('08:00:00'); // Hora por defecto
            // Reconstruir tabla sin perder datos en memoria
            const registrosMarcaFaltante = Object.entries(registrosMarcas).map(([key, data]) => ({
                clave: key,
                codigo_persona: data.codigo_persona,
                nombre: reporteActualData.registros_por_persona.find(r => r.codigo_persona === data.codigo_persona)?.nombre || '',
                fecha: data.fecha,
                hora_registrada: data.horas[0]
            }));
            construirTabla(registrosMarcaFaltante);
        }
    }

    /**
     * Eliminar una marca agregada
     */
    function eliminarMarca(clave, index) {
        if (index > 0 && registrosMarcas[clave].horas.length > 1) {
            // Eliminar el elemento en la posición index
            registrosMarcas[clave].horas.splice(index, 1);
            // Reconstruir tabla sin perder datos en memoria
            const registrosMarcaFaltante = Object.entries(registrosMarcas).map(([key, data]) => ({
                clave: key,
                codigo_persona: data.codigo_persona,
                nombre: reporteActualData.registros_por_persona.find(r => r.codigo_persona === data.codigo_persona)?.nombre || '',
                fecha: data.fecha,
                hora_registrada: data.horas[0]
            }));
            construirTabla(registrosMarcaFaltante);
        }
    }

    /**
     * Guardar todas las marcas con un botón
     */
    function guardarTodasMarcas() {
        const marcasAGuardar = [];
        let tieneChanges = false;

        Object.entries(registrosMarcas).forEach(([clave, data]) => {
            // Si hay más de una marca, significa que se agregaron
            if (data.horas.length > 1) {
                tieneChanges = true;
                marcasAGuardar.push({
                    codigo_persona: data.codigo_persona,
                    fecha: data.fecha,
                    horas: data.horas
                });
            }
        });

        if (!tieneChanges) {
            alert('No hay cambios para guardar');
            return;
        }

        const btnGuardar = document.getElementById('btnGuardarTodasMarcas');
        btnGuardar.disabled = true;
        btnGuardar.textContent = 'Guardando...';

        // Procesar cada registro individualmente
        const promesas = marcasAGuardar.map(registro => {
            return new Promise((resolve) => {
                const datos = {
                    codigo_persona: registro.codigo_persona,
                    id_reporte: reporteActualData.id,
                    fecha: registro.fecha,
                    horas_nuevas: registro.horas.slice(1) // Todas menos la primera (que es la registrada)
                };

                fetch('/asistencia-personal/guardar-marcas-multiples', {
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
                        return response.json().then(errorData => {
                            throw new Error(JSON.stringify(errorData));
                        });
                    }
                    return response.json();
                })
                .then(data => resolve(data))
                .catch(error => {
                    console.error('Error:', error);
                    resolve({
                        success: false,
                        message: error.message
                    });
                });
            });
        });

        Promise.all(promesas).then(resultados => {
            const todosOk = resultados.every(r => r.success);

            if (todosOk) {
                btnGuardar.textContent = '✓ Guardado';
                btnGuardar.style.background = 'linear-gradient(135deg, #28a745 0%, #20c997 100%)';

                setTimeout(() => {
                    fetchReportDetails(reporteActualData.id, function(respuesta) {
                        if (respuesta.success && respuesta.reporte) {
                            reporteActualData = respuesta.reporte;
                            cargarDatos(reporteActualData);
                            btnGuardar.disabled = false;
                            btnGuardar.textContent = 'Guardar Cambios';
                            btnGuardar.style.background = 'linear-gradient(135deg, #007bff 0%, #0056b3 100%)';
                            
                            // Cerrar modal después de guardar
                            setTimeout(() => {
                                const modal = document.getElementById('marcasFaltantesModal');
                                if (modal) {
                                    modal.style.display = 'none';
                                    // Restaurar scroll
                                    document.body.style.overflow = 'auto';
                                }
                            }, 800);
                        }
                    });
                }, 1500);
            } else {
                const errorMessages = resultados.filter(r => !r.success).map(r => r.message).join(', ');
                alert('Error al guardar algunas marcas:\n' + errorMessages);
                btnGuardar.disabled = false;
                btnGuardar.textContent = 'Guardar Cambios';
            }
        });
    }

    return {
        cargar
    };
})();
