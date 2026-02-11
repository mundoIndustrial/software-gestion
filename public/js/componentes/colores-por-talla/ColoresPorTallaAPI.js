/**
 * ColoresPorTallaAPI - Cliente API para el sistema de colores por talla
 * Consume los endpoints del backend para gestión de asignaciones
 */

window.ColoresPorTallaAPI = (function() {
    'use strict';
    
    // Configuración base de la API
    const API_BASE = '/api/colores-por-talla';
    
    /**
     * Realizar petición a la API
     */
    async function apiRequest(endpoint, options = {}) {
        try {
            const url = `${API_BASE}${endpoint}`;
            const config = {
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                },
                ...options
            };
            
            console.log(`[API] ${options.method || 'GET'} ${url}`);
            
            const response = await fetch(url, config);
            
            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error(errorData.message || `HTTP ${response.status}: ${response.statusText}`);
            }
            
            const data = await response.json();
            console.log(`[API]  Respuesta de ${endpoint}:`, data);
            
            return data;
            
        } catch (error) {
            console.error(`[API]  Error en ${endpoint}:`, error);
            throw error;
        }
    }
    
    /**
     * Obtener todas las asignaciones
     */
    async function obtenerAsignaciones(filtros = {}) {
        const params = new URLSearchParams(filtros);
        const endpoint = `/asignaciones${params.toString() ? '?' + params.toString() : ''}`;
        
        return await apiRequest(endpoint);
    }
    
    /**
     * Guardar una nueva asignación
     */
    async function guardarAsignacion(datos) {
        return await apiRequest('/asignaciones', {
            method: 'POST',
            body: JSON.stringify(datos)
        });
    }
    
    /**
     * Actualizar una asignación existente
     */
    async function actualizarAsignacion(id, datos) {
        return await apiRequest(`/asignaciones/${id}`, {
            method: 'PATCH',
            body: JSON.stringify(datos)
        });
    }
    
    /**
     * Eliminar una asignación
     */
    async function eliminarAsignacion(id) {
        return await apiRequest(`/asignaciones/${id}`, {
            method: 'DELETE'
        });
    }
    
    /**
     * Obtener colores disponibles para una talla
     */
    async function obtenerColoresDisponibles(genero, talla) {
        return await apiRequest(`/colores-disponibles/${genero}/${talla}`);
    }
    
    /**
     * Obtener tallas disponibles para un género
     */
    async function obtenerTallasDisponibles(genero) {
        return await apiRequest(`/tallas-disponibles/${genero}`);
    }
    
    /**
     * Procesar asignación del wizard (múltiples tallas)
     */
    async function procesarAsignacionWizard(datos) {
        return await apiRequest('/procesar-asignacion-wizard', {
            method: 'POST',
            body: JSON.stringify(datos)
        });
    }
    
    /**
     * Manejar errores de la API
     */
    function manejarError(error, contexto = '') {
        console.error(`[API] Error en ${contexto}:`, error);
        
        let mensaje = 'Error desconocido';
        
        if (error.message) {
            mensaje = error.message;
        } else if (typeof error === 'string') {
            mensaje = error;
        }
        
        // Mostrar notificación al usuario
        if (window.mostrarNotificacion) {
            window.mostrarNotificacion(mensaje, 'error');
        } else {
            alert(mensaje);
        }
        
        return mensaje;
    }
    
    /**
     * Validar datos de asignación
     */
    function validarDatosAsignacion(datos) {
        const errores = [];
        
        if (!datos.genero) {
            errores.push('El género es requerido');
        }
        
        if (!datos.talla) {
            errores.push('La talla es requerida');
        }
        
        if (!datos.tipo_talla) {
            errores.push('El tipo de talla es requerido');
        }
        
        if (!datos.tela) {
            errores.push('La tela es requerida');
        }
        
        if (!datos.colores || datos.colores.length === 0) {
            errores.push('Debe agregar al menos un color');
        }
        
        // Validar cada color
        datos.colores?.forEach((color, index) => {
            if (!color.color) {
                errores.push(`El color #${index + 1} es requerido`);
            }
            
            if (!color.cantidad || color.cantidad <= 0) {
                errores.push(`La cantidad del color #${index + 1} debe ser mayor a 0`);
            }
        });
        
        return {
            valido: errores.length === 0,
            errores: errores
        };
    }
    
    /**
     * Formatear datos para el wizard
     */
    function formatearDatosWizard(datosWizard) {
        return {
            genero: datosWizard.genero,
            tipo_talla: datosWizard.tipo,
            tela: datosWizard.tela,
            tallas: datosWizard.tallas.map(talla => ({
                talla: talla,
                colores: datosWizard.coloresPorTalla[talla] || []
            }))
        };
    }
    
    /**
     * Obtener estadísticas de asignaciones
     */
    async function obtenerEstadisticas() {
        try {
            const asignaciones = await obtenerAsignaciones();
            
            const stats = {
                total_asignaciones: asignaciones.data?.length || 0,
                total_unidades: asignaciones.data?.reduce((sum, a) => sum + a.total_unidades, 0) || 0,
                por_genero: {},
                por_talla: {},
                por_tela: {},
                por_color: {}
            };
            
            asignaciones.data?.forEach(asignacion => {
                // Estadísticas por género
                stats.por_genero[asignacion.genero] = (stats.por_genero[asignacion.genero] || 0) + 1;
                
                // Estadísticas por talla
                stats.por_talla[asignacion.talla] = (stats.por_talla[asignacion.talla] || 0) + 1;
                
                // Estadísticas por tela
                stats.por_tela[asignacion.tela] = (stats.por_tela[asignacion.tela] || 0) + 1;
                
                // Estadísticas por color
                asignacion.colores?.forEach(color => {
                    stats.por_color[color.color] = (stats.por_color[color.color] || 0) + color.cantidad;
                });
            });
            
            return stats;
            
        } catch (error) {
            manejarError(error, 'obteniendo estadísticas');
            return null;
        }
    }
    
    /**
     * Sincronizar asignaciones con el backend
     */
    async function sincronizarAsignaciones() {
        try {
            console.log('[API]  Sincronizando asignaciones con el backend...');
            
            const asignaciones = await obtenerAsignaciones();
            
            // Actualizar StateManager
            if (window.StateManager) {
                window.StateManager.setAsignaciones(asignaciones.data || []);
            }
            
            // Actualizar UI
            if (window.UIRenderer) {
                window.UIRenderer.actualizarTablaAsignaciones();
                window.UIRenderer.actualizarResumenAsignaciones();
            }
            
            console.log('[API]  Sincronización completada');
            
            return asignaciones;
            
        } catch (error) {
            manejarError(error, 'sincronizando asignaciones');
            return null;
        }
    }
    
    /**
     * Probar conexión con la API
     */
    async function probarConexion() {
        try {
            console.log('[API]  Probando conexión con la API...');
            
            const response = await obtenerTallasDisponibles('dama');
            
            console.log('[API]  Conexión exitosa');
            
            return {
                exito: true,
                mensaje: 'Conexión con la API establecida',
                datos: response
            };
            
        } catch (error) {
            console.error('[API]  Error de conexión:', error);
            
            return {
                exito: false,
                mensaje: 'Error de conexión con la API',
                error: error.message
            };
        }
    }
    
    /**
     * API Pública
     */
    return {
        obtenerAsignaciones,
        guardarAsignacion,
        actualizarAsignacion,
        eliminarAsignacion,
        obtenerColoresDisponibles,
        obtenerTallasDisponibles,
        procesarAsignacionWizard,
        manejarError,
        validarDatosAsignacion,
        formatearDatosWizard,
        obtenerEstadisticas,
        sincronizarAsignaciones,
        probarConexion
    };
})();
