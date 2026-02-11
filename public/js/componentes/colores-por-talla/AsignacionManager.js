/**
 * Módulo: AsignacionManager
 * Gestiona las operaciones CRUD de asignaciones de colores por talla
 */

window.AsignacionManager = (function() {
    'use strict';

    // Verificar dependencias
    if (!window.StateManager) {
        console.error('[AsignacionManager]  StateManager no está disponible. Asegúrate de cargar StateManager.js antes que AsignacionManager.js');
        return {};
    }

    return {
        /**
         * Agregar un color personalizado directamente a las asignaciones
         */
        agregarColorPersonalizado(genero, talla, color, cantidad = 1) {
            // Validaciones
            if (!genero) {
                console.error('Selecciona un género');
                return false;
            }
            
            if (!talla) {
                console.error('Selecciona una talla');
                return false;
            }
            
            if (!color) {
                console.error('Ingresa un nombre de color');
                return false;
            }
            
            if (cantidad < 1) {
                console.error('La cantidad debe ser mayor a 0');
                return false;
            }
            
            // Guardar asignación directa
            const clave = `${genero}-${talla}`;
            const asignaciones = StateManager.getAsignaciones();
            
            // Si ya existe la asignación, agregar el color a la lista
            if (asignaciones[clave]) {
                // Evitar duplicados
                if (asignaciones[clave].colores.some(c => c.nombre === color)) {
                    console.warn('Este color ya está asignado para esta talla');
                    return false;
                }
                
                // Agregar nuevo color
                asignaciones[clave].colores.push({
                    nombre: color,
                    cantidad: cantidad
                });
            } else {
                // Crear nueva asignación con este color
                asignaciones[clave] = {
                    genero: genero,
                    talla: talla,
                    colores: [{
                        nombre: color,
                        cantidad: cantidad
                    }]
                };
            }
            
            StateManager.setAsignaciones(asignaciones);
            console.log('[AsignacionManager]  Color personalizado agregado:', asignaciones[clave]);
            
            return true;
        },

        /**
         * Guardar asignación de colores para la talla-género seleccionada
         */
        guardarAsignacionColores(genero, talla, coloresSeleccionados) {
            if (!genero || !talla) {
                console.error('Selecciona género y talla');
                return false;
            }
            
            if (!coloresSeleccionados || coloresSeleccionados.length === 0) {
                console.warn('Selecciona al menos un color');
                return false;
            }
            
            // Guardar asignación
            const tipo = StateManager.getTipoTallaSel();
            const clave = tipo ? `${genero}-${tipo}-${talla}` : `${genero}-${talla}`;
            
            StateManager.agregarAsignacion(clave, {
                genero: genero,
                tipo: tipo,
                talla: talla,
                colores: coloresSeleccionados
            });
            
            console.log('[AsignacionManager] Asignación guardada:', StateManager.getAsignaciones()[clave]);
            return true;
        },

        /**
         * Guardar asignación para múltiples tallas (wizard)
         */
        guardarAsignacionesMultiples(genero, tallas, tipo, tela, asignacionesPorTalla) {
            if (!tela) {
                console.error('Por favor selecciona una tela primero');
                return false;
            }
            
            console.log('[AsignacionManager] Guardando asignaciones para', genero, 'telas:', tela, 'tallas:', tallas);
            
            // Verificar que al menos una talla tenga colores
            let tieneColores = false;
            tallas.forEach(talla => {
                if (asignacionesPorTalla[talla] && asignacionesPorTalla[talla].length > 0) {
                    tieneColores = true;
                }
            });
            
            if (!tieneColores) {
                console.warn('Ingresa al menos un color para alguna talla');
                return false;
            }
            
            // Guardar cada asignación (una por talla)
            tallas.forEach(talla => {
                if (asignacionesPorTalla[talla] && asignacionesPorTalla[talla].length > 0) {
                    const clave = tipo ? `${genero}-${tipo}-${talla}` : `${genero}-${talla}`;
                    
                    StateManager.agregarAsignacion(clave, {
                        genero: genero,
                        tela: tela,
                        tipo: tipo,
                        talla: talla,
                        colores: asignacionesPorTalla[talla]
                    });
                    
                    console.log('[AsignacionManager] Asignación guardada para talla', talla, ':', StateManager.getAsignaciones()[clave]);
                }
            });
            
            return true;
        },

        /**
         * Actualizar cantidad de una asignación existente
         */
        actualizarCantidadAsignacion(genero, talla, color, nuevaCantidad) {
            const tipo = StateManager.getTipoTallaSel();
            const clave = tipo ? `${genero}-${tipo}-${talla}` : `${genero}-${talla}`;
            const asignaciones = StateManager.getAsignaciones();
            const asignacion = asignaciones[clave];
            
            if (!asignacion) return false;
            
            const colorObj = asignacion.colores.find(c => c.nombre === color);
            if (colorObj) {
                colorObj.cantidad = parseInt(nuevaCantidad) || 0;
                StateManager.setAsignaciones(asignaciones);
                console.log('[AsignacionManager] Cantidad actualizada:', clave, color, colorObj.cantidad);
                return true;
            }
            
            return false;
        },

        /**
         * Eliminar una asignación de color-talla
         */
        eliminarAsignacion(genero, talla, color) {
            const tipo = StateManager.getTipoTallaSel();
            const clave = tipo ? `${genero}-${tipo}-${talla}` : `${genero}-${talla}`;
            const asignaciones = StateManager.getAsignaciones();
            const asignacion = asignaciones[clave];
            
            if (!asignacion) return false;
            
            // Remover color de la asignación
            asignacion.colores = asignacion.colores.filter(c => c.nombre !== color);
            
            // Si no quedan colores, eliminar la asignación completa
            if (asignacion.colores.length === 0) {
                StateManager.eliminarAsignacion(clave);
            } else {
                StateManager.setAsignaciones(asignaciones);
            }
            
            console.log('[AsignacionManager] Asignación eliminada:', clave, color);
            return true;
        },

        /**
         * Eliminar asignación completa
         */
        eliminarAsignacionCompleta(clave) {
            StateManager.eliminarAsignacion(clave);
            console.log('[AsignacionManager] Asignación completa eliminada:', clave);
        },

        /**
         * Limpiar todas las asignaciones
         */
        limpiarAsignaciones() {
            StateManager.limpiarAsignaciones();
            console.log('[AsignacionManager] Asignaciones limpias');
        },

        /**
         * Obtener asignación específica
         */
        obtenerAsignacion(genero, talla, tipo = null) {
            const clave = tipo ? `${genero}-${tipo}-${talla}` : `${genero}-${talla}`;
            const asignaciones = StateManager.getAsignaciones();
            return asignaciones[clave] || null;
        },

        /**
         * Verificar si existe una asignación
         */
        existeAsignacion(genero, talla, tipo = null) {
            return this.obtenerAsignacion(genero, talla, tipo) !== null;
        },

        /**
         * Obtener todas las asignaciones como array
         */
        obtenerTodasLasAsignaciones() {
            return Object.values(StateManager.getAsignaciones());
        },

        /**
         * Calcular total de unidades en todas las asignaciones
         */
        calcularTotalUnidades() {
            const asignaciones = this.obtenerTodasLasAsignaciones();
            let totalUnidades = 0;
            
            asignaciones.forEach(asignacion => {
                if (asignacion.colores) {
                    asignacion.colores.forEach(color => {
                        const cantidad = parseInt(color.cantidad) || 0;
                        totalUnidades += cantidad;
                    });
                }
            });
            
            return totalUnidades;
        },

        /**
         * Obtener colores disponibles de los arrays de telas
         */
        obtenerColoresDisponibles() {
            console.log('[AsignacionManager] 🔵 Obteniendo colores disponibles...');
            
            const colores = [];
            
            // Primero intentar desde window.telasCreacion
            if (window.telasCreacion && Array.isArray(window.telasCreacion) && window.telasCreacion.length > 0) {
                console.log('[AsignacionManager] 📖 Leyendo desde window.telasCreacion:', window.telasCreacion);
                
                window.telasCreacion.forEach((tela, idx) => {
                    const color = (tela.color || tela.color_nombre || '').trim().toUpperCase();
                    const telaName = (tela.tela || tela.nombre_tela || tela.nombre || '').trim().toUpperCase();
                    
                    if (color && !colores.some(c => c.nombre === color)) {
                        colores.push({
                            nombre: color,
                            tela: telaName || 'SIN TELA'
                        });
                    }
                });
            }
            
            // Si no hay en telasCreacion, intentar desde window.telasAgregadas
            if (colores.length === 0 && window.telasAgregadas && Array.isArray(window.telasAgregadas) && window.telasAgregadas.length > 0) {
                console.log('[AsignacionManager] 📖 Leyendo desde window.telasAgregadas:', window.telasAgregadas);
                
                window.telasAgregadas.forEach((tela, idx) => {
                    const color = (tela.color || tela.color_nombre || '').trim().toUpperCase();
                    const telaName = (tela.tela || tela.nombre_tela || tela.nombre || '').trim().toUpperCase();
                    
                    if (color && !colores.some(c => c.nombre === color)) {
                        colores.push({
                            nombre: color,
                            tela: telaName || 'SIN TELA'
                        });
                    }
                });
            }
            
            // Fallback: buscar en la tabla del DOM
            if (colores.length === 0) {
                console.log('[AsignacionManager]  Fallback: Buscando en tabla DOM');
                
                const tbody = document.getElementById('tbody-telas');
                if (tbody) {
                    const filas = tbody.querySelectorAll('tr');
                    
                    filas.forEach((fila) => {
                        const celdas = fila.querySelectorAll('td');
                        if (celdas.length < 2) return;
                        
                        const colorInput = celdas[1].querySelector('input');
                        const telaInput = celdas[0].querySelector('input');
                        
                        if (colorInput && colorInput.value) {
                            const color = colorInput.value.trim().toUpperCase();
                            const tela = telaInput ? telaInput.value.trim().toUpperCase() : 'SIN TELA';
                            
                            if (color && !colores.some(c => c.nombre === color)) {
                                colores.push({
                                    nombre: color,
                                    tela: tela
                                });
                            }
                        }
                    });
                }
            }
            
            console.log('[AsignacionManager]  Colores encontrados:', colores);
            return colores;
        },

        /**
         * Cargar asignaciones previas (para edición)
         */
        cargarAsignacionesPrevias(datos) {
            StateManager.cargarAsignacionesPrevias(datos);
        },

        /**
         * Obtener datos de asignaciones para guardar
         */
        obtenerDatosAsignaciones() {
            return StateManager.obtenerDatosAsignaciones();
        },

        /**
         * Obtener el total de asignaciones (número de tallas con colores asignados)
         */
        obtenerTotalAsignaciones() {
            const asignaciones = StateManager.getAsignaciones();
            return asignaciones ? Object.keys(asignaciones).length : 0;
        }
    };
})();
