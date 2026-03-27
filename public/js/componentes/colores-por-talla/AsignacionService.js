/**
 * AsignacionService - Lógica de Negocio de Asignaciones
 * Maneja toda la lógica de asignación de colores a tallas
 * Centraliza las operaciones CRUD de asignaciones
 */

window.AsignacionService = (function() {
    'use strict';
    
    // Estructura de datos para asignaciones
    let asignaciones = {};
    
    /**
     * Inicializar el servicio
     */
    function init() {
        console.log('[AsignacionService]  Inicializando servicio de asignaciones...');
        asignaciones = {};
        console.log('[AsignacionService]  Servicio inicializado');
        return true;
    }
    
    /**
     * Generar clave única para asignación
     */
    function generarClave(genero, tipo, talla) {
        return `${genero}-${tipo}-${talla}`;
    }
    
    /**
     * Agregar un color a una asignación existente
     */
    function agregarColor(genero, talla, color, cantidad) {
        console.log('[AsignacionService]  Agregando color:', { genero, talla, color, cantidad });
        
        // Obtener tipo de talla (asumimos que viene del StateManager)
        const tipo = window.StateManager ? window.StateManager.getTipoTallaSel() : 'Letra';
        const clave = generarClave(genero, tipo, talla);
        
        // Crear asignación si no existe
        if (!asignaciones[clave]) {
            asignaciones[clave] = {
                genero: genero,
                tipo: tipo,
                talla: talla,
                colores: [],
                fechaCreacion: new Date()
            };
        }
        
        // Verificar si el color ya existe
        const colorExistente = asignaciones[clave].colores.find(c => c.nombre === color);
        if (colorExistente) {
            // Actualizar cantidad
            colorExistente.cantidad += cantidad;
            console.log('[AsignacionService] 📝 Color existente actualizado:', colorExistente);
        } else {
            // Agregar nuevo color
            asignaciones[clave].colores.push({
                nombre: color,
                cantidad: cantidad,
                fecha: new Date()
            });
            console.log('[AsignacionService] ➕ Nuevo color agregado');
        }
        
        // Actualizar StateManager
        if (window.StateManager) {
            window.StateManager.setAsignaciones(asignaciones);
        }
        
        console.log('[AsignacionService]  Color agregado exitosamente');
        return true;
    }
    
    /**
     * Agregar múltiples colores a múltiples tallas (wizard)
     */
    function agregarColores(genero, tallas, tipo, coloresPorTalla) {
        console.log('[AsignacionService]  Agregando colores múltiples:', { genero, tallas, tipo, coloresPorTalla });
        
        let agregadas = 0;
        
        tallas.forEach(talla => {
            const colores = coloresPorTalla[talla];
            if (colores && colores.length > 0) {
                const clave = generarClave(genero, tipo, talla);
                
                // Crear o actualizar asignación
                if (!asignaciones[clave]) {
                    asignaciones[clave] = {
                        genero: genero,
                        tipo: tipo,
                        talla: talla,
                        colores: [],
                        fechaCreacion: new Date()
                    };
                }
                
                // Agregar colores
                colores.forEach(colorInfo => {
                    const colorExistente = asignaciones[clave].colores.find(c => c.nombre === colorInfo.color);
                    if (colorExistente) {
                        colorExistente.cantidad += colorInfo.cantidad;
                    } else {
                        asignaciones[clave].colores.push({
                            nombre: colorInfo.color,
                            cantidad: colorInfo.cantidad,
                            fecha: new Date()
                        });
                    }
                });
                
                agregadas++;
                console.log(`[AsignacionService]  Talla ${talla}: ${colores.length} colores`);
            }
        });
        
        // Actualizar StateManager
        if (window.StateManager) {
            window.StateManager.setAsignaciones(asignaciones);
        }
        
        console.log(`[AsignacionService]  ${agregadas} asignaciones procesadas`);
        return agregadas > 0;
    }
    
    /**
     * Actualizar cantidad de un color específico
     */
    function actualizarCantidad(genero, talla, color, nuevaCantidad) {
        console.log('[AsignacionService] 📝 Actualizando cantidad:', { genero, talla, color, nuevaCantidad });
        
        const tipo = window.StateManager ? window.StateManager.getTipoTallaSel() : 'Letra';
        const clave = generarClave(genero, tipo, talla);
        
        if (!asignaciones[clave]) {
            console.warn('[AsignacionService]  Asignación no encontrada:', clave);
            return false;
        }
        
        const colorInfo = asignaciones[clave].colores.find(c => c.nombre === color);
        if (!colorInfo) {
            console.warn('[AsignacionService]  Color no encontrado:', color);
            return false;
        }
        
        const cantidadAnterior = colorInfo.cantidad;
        colorInfo.cantidad = nuevaCantidad;
        
        // Actualizar StateManager
        if (window.StateManager) {
            window.StateManager.setAsignaciones(asignaciones);
        }
        
        console.log(`[AsignacionService]  Cantidad actualizada: ${cantidadAnterior} → ${nuevaCantidad}`);
        return true;
    }
    
    /**
     * Eliminar un color de una asignación
     */
    function eliminarColor(genero, talla, color) {
        console.log('[AsignacionService]  Eliminando color:', { genero, talla, color });
        
        const tipo = window.StateManager ? window.StateManager.getTipoTallaSel() : 'Letra';
        const clave = generarClave(genero, tipo, talla);
        
        if (!asignaciones[clave]) {
            console.warn('[AsignacionService]  Asignación no encontrada:', clave);
            return false;
        }
        
        const index = asignaciones[clave].colores.findIndex(c => c.nombre === color);
        if (index === -1) {
            console.warn('[AsignacionService]  Color no encontrado:', color);
            return false;
        }
        
        asignaciones[clave].colores.splice(index, 1);
        
        // Si no quedan colores, eliminar la asignación completa
        if (asignaciones[clave].colores.length === 0) {
            delete asignaciones[clave];
            console.log('[AsignacionService]  Asignación eliminada (sin colores)');
        }
        
        // Actualizar StateManager
        if (window.StateManager) {
            window.StateManager.setAsignaciones(asignaciones);
        }
        
        console.log('[AsignacionService]  Color eliminado');
        return true;
    }
    
    /**
     * Eliminar una asignación completa
     */
    function eliminarAsignacion(genero, talla) {
        console.log('[AsignacionService]  Eliminando asignación:', { genero, talla });
        
        const tipo = window.StateManager ? window.StateManager.getTipoTallaSel() : 'Letra';
        const clave = generarClave(genero, tipo, talla);
        
        if (!asignaciones[clave]) {
            console.warn('[AsignacionService]  Asignación no encontrada:', clave);
            return false;
        }
        
        delete asignaciones[clave];
        
        // Actualizar StateManager
        if (window.StateManager) {
            window.StateManager.setAsignaciones(asignaciones);
        }
        
        console.log('[AsignacionService]  Asignación eliminada');
        return true;
    }
    
    /**
     * Obtener asignaciones para un género y talla específicos
     */
    function obtenerAsignacion(genero, talla) {
        const tipo = window.StateManager ? window.StateManager.getTipoTallaSel() : 'Letra';
        const clave = generarClave(genero, tipo, talla);
        return asignaciones[clave] || null;
    }
    
    /**
     * Obtener todas las asignaciones
     */
    function obtenerTodas() {
        return { ...asignaciones };
    }
    
    /**
     * Obtener asignaciones en formato de array para UI
     */
    function obtenerParaUI() {
        const resultado = [];
        
        for (const clave of Object.keys(asignaciones)) {
            const asignacion = asignaciones[clave];
            
            // Obtener información de tela del StateManager
            const tela = window.StateManager ? window.StateManager.getTelaSeleccionada() : 'Sin tela';
            
            resultado.push({
                clave: clave,
                genero: asignacion.genero,
                tipo: asignacion.tipo,
                talla: asignacion.talla,
                tela: tela,
                colores: asignacion.colores,
                totalUnidades: asignacion.colores.reduce((sum, c) => sum + c.cantidad, 0),
                fechaCreacion: asignacion.fechaCreacion
            });
        }
        
        return resultado;
    }
    
    /**
     * Verificar si hay colores para una asignación
     */
    function tieneColores(genero, talla) {
        const asignacion = obtenerAsignacion(genero, talla);
        return asignacion && asignacion.colores.length > 0;
    }
    
    /**
     * Obtener total de asignaciones
     */
    function obtenerTotalAsignaciones() {
        return Object.keys(asignaciones).length;
    }
    
    /**
     * Obtener total de unidades
     */
    function obtenerTotalUnidades() {
        let total = 0;
        
        for (const asignacion of Object.values(asignaciones)) {
            total += asignacion.colores.reduce((sum, color) => sum + color.cantidad, 0);
        }
        
        return total;
    }
    
    /**
     * Limpiar todas las asignaciones
     */
    function limpiar() {
        console.log('[AsignacionService] 🧹 Limpiando todas las asignaciones...');
        asignaciones = {};
        
        // Actualizar StateManager
        if (window.StateManager) {
            window.StateManager.setAsignaciones(asignaciones);
        }
        
        console.log('[AsignacionService]  Asignaciones limpiadas');
    }
    
    /**
     * Cargar asignaciones previas
     */
    function cargar(datos) {
        console.log('[AsignacionService] 📥 Cargando asignaciones previas...');
        
        if (!datos || typeof datos !== 'object') {
            console.warn('[AsignacionService]  Datos inválidos para cargar');
            return false;
        }
        
        asignaciones = { ...datos };
        
        // Actualizar StateManager
        if (window.StateManager) {
            window.StateManager.setAsignaciones(asignaciones);
        }
        
        console.log('[AsignacionService]  Asignaciones cargadas:', Object.keys(asignaciones).length);
        return true;
    }
    
    /**
     * Exportar asignaciones para guardar
     */
    function exportar() {
        return {
            asignaciones: { ...asignaciones },
            totalAsignaciones: obtenerTotalAsignaciones(),
            totalUnidades: obtenerTotalUnidades(),
            fechaExportacion: new Date()
        };
    }
    
    /**
     * Validar datos de asignación
     */
    function validar(genero, talla, color, cantidad) {
        const errores = [];
        
        if (!genero || typeof genero !== 'string') {
            errores.push('Género inválido');
        }
        
        if (!talla || typeof talla !== 'string') {
            errores.push('Talla inválida');
        }
        
        if (!color || typeof color !== 'string') {
            errores.push('Color inválido');
        }
        
        if (!cantidad || typeof cantidad !== 'number' || cantidad <= 0) {
            errores.push('Cantidad inválida');
        }
        
        return {
            valido: errores.length === 0,
            errores: errores
        };
    }
    
    /**
     * Obtener estadísticas
     */
    function obtenerEstadisticas() {
        const stats = {
            totalAsignaciones: obtenerTotalAsignaciones(),
            totalUnidades: obtenerTotalUnidades(),
            porGenero: {},
            porTalla: {},
            porColor: {}
        };
        
        for (const asignacion of Object.values(asignaciones)) {
            // Estadísticas por género
            stats.porGenero[asignacion.genero] = (stats.porGenero[asignacion.genero] || 0) + 1;
            
            // Estadísticas por talla
            stats.porTalla[asignacion.talla] = (stats.porTalla[asignacion.talla] || 0) + 1;
            
            // Estadísticas por color
            asignacion.colores.forEach(color => {
                stats.porColor[color.nombre] = (stats.porColor[color.nombre] || 0) + color.cantidad;
            });
        }
        
        return stats;
    }
    
    /**
     * API Pública
     */
    return {
        init,
        agregarColor,
        agregarColores,
        actualizarCantidad,
        eliminarColor,
        eliminarAsignacion,
        obtenerAsignacion,
        obtenerTodas,
        obtenerParaUI,
        tieneColores,
        obtenerTotalAsignaciones,
        obtenerTotalUnidades,
        limpiar,
        cargar,
        exportar,
        validar,
        obtenerEstadisticas
    };
})();
