/**
 * Módulo de Utilidades - Asistencia Personal
 * Funciones auxiliares, cálculos y conversiones
 */

const AsistenciaUtilidades = (() => {
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
     */
    function calcularHoraExtra(totalMinutos, esDiaSabado = false) {
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
        
        const minutosExtra = totalMinutos - minutosBase;
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
        
        const diaSabado = fecha ? esSabado(fecha) : false;
        
        const minutosArray = horas.map(hora => {
            const [h, m, s] = hora.split(':').map(Number);
            return h * 60 + m + (s || 0) / 60;
        }).sort((a, b) => a - b);
        
        console.log('Minutos ordenados:', minutosArray);
        console.log('¿Es sábado?:', diaSabado);
        
        const horasValidas = [];
        for (let i = 0; i < minutosArray.length; i++) {
            if (horasValidas.length === 0 || Math.abs(minutosArray[i] - horasValidas[horasValidas.length - 1]) >= 2) {
                horasValidas.push(minutosArray[i]);
            } else {
                console.log(`Duplicado ignorado: ${minutosArray[i]} minutos`);
            }
        }
        
        console.log('Horas válidas después de limpiar duplicados:', horasValidas);
        
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
        
        let registrosAUsar = horasValidas;
        if (horasValidas.length > 4) {
            console.log(`Más de 4 registros detectados (${horasValidas.length}). Usando los primeros 4.`);
            registrosAUsar = horasValidas.slice(0, 4);
        }
        
        let registrosFaltantes = [];
        let jornada_completa = false;
        
        if (diaSabado) {
            jornada_completa = registrosAUsar.length >= 2;
            if (registrosAUsar.length === 1) {
                registrosFaltantes = ['salida_mediodía'];
            }
        } else {
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
        
        let totalMinutos = 0;
        let bloqueMañanaCalculado = false;
        let bloqueTardeCalculado = false;
        
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
                console.log('Bloque Tarde: No se puede calcular (falta salida final)');
            }
        }
        
        const horas_total = Math.floor(totalMinutos / 60);
        const minutos_restantes = Math.floor(totalMinutos % 60);
        const segundos = Math.round(((totalMinutos % 1) * 60));
        
        const resultado = `${String(horas_total).padStart(2, '0')}:${String(minutos_restantes).padStart(2, '0')}:${String(segundos).padStart(2, '0')}`;
        
        let estado = 'incompleta';
        let observacion = 'Jornada incompleta';
        
        if (jornada_completa) {
            estado = 'completa';
            observacion = diaSabado ? 'Jornada de sábado completa (4 horas mínimo)' : 'Jornada completa';
        } else {
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

    return {
        horaAMinutos,
        esSabado,
        calcularHoraExtra,
        calcularHorasTrabajadasAvanzado,
        escapeRegExp,
        escapeHtml
    };
})();
