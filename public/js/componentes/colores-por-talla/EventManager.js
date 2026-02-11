/**
 * EventManager - Sistema Centralizado de Eventos
 * Maneja todos los event listeners del sistema de colores por talla
 * Previene duplicados y facilita la gestión de eventos
 */

window.EventManager = (function() {
    'use strict';
    
    // Mapa para tracking de listeners configurados
    const listenersConfigurados = new Map();
    
    /**
     * Registrar un event listener con prevención de duplicados
     */
    function registrar(elemento, evento, callback, opciones = {}) {
        if (!elemento || !evento || !callback) {
            console.warn('[EventManager]  Parámetros inválidos:', { elemento, evento, callback });
            return false;
        }
        
        // Crear clave única
        const clave = `${elemento.id || elemento.tagName}-${evento}`;
        
        // Verificar si ya está configurado
        if (listenersConfigurados.has(clave)) {
            console.log(`[EventManager] ⏭️ Event listener ya configurado: ${clave}`);
            return false;
        }
        
        try {
            // Agregar event listener
            elemento.addEventListener(evento, callback, opciones);
            
            // Registrar en el mapa
            listenersConfigurados.set(clave, {
                elemento: elemento,
                evento: evento,
                callback: callback,
                opciones: opciones,
                fecha: new Date()
            });
            
            console.log(`[EventManager]  Event listener registrado: ${clave}`);
            return true;
            
        } catch (error) {
            console.error(`[EventManager]  Error registrando listener ${clave}:`, error);
            return false;
        }
    }
    
    /**
     * Remover un event listener específico
     */
    function remover(elemento, evento, callback) {
        if (!elemento || !evento || !callback) {
            return false;
        }
        
        const clave = `${elemento.id || elemento.tagName}-${evento}`;
        
        try {
            elemento.removeEventListener(evento, callback);
            listenersConfigurados.delete(clave);
            console.log(`[EventManager] 🗑️ Event listener removido: ${clave}`);
            return true;
        } catch (error) {
            console.error(`[EventManager]  Error removiendo listener ${clave}:`, error);
            return false;
        }
    }
    
    /**
     * Limpiar todos los listeners de un elemento
     */
    function limpiarElemento(elemento) {
        if (!elemento) return false;
        
        let eliminados = 0;
        
        // Iterar sobre todos los listeners y eliminar los del elemento
        for (const [clave, config] of listenersConfigurados.entries()) {
            if (config.elemento === elemento) {
                try {
                    elemento.removeEventListener(config.evento, config.callback);
                    listenersConfigurados.delete(clave);
                    eliminados++;
                } catch (error) {
                    console.error(`[EventManager]  Error limpiando listener ${clave}:`, error);
                }
            }
        }
        
        console.log(`[EventManager] 🧹 Elemento limpiado: ${eliminados} listeners eliminados`);
        return eliminados > 0;
    }
    
    /**
     * Limpiar todos los listeners del sistema
     */
    function limpiarTodos() {
        let eliminados = 0;
        
        for (const [clave, config] of listenersConfigurados.entries()) {
            try {
                config.elemento.removeEventListener(config.evento, config.callback);
                eliminados++;
            } catch (error) {
                console.error(`[EventManager]  Error limpiando listener ${clave}:`, error);
            }
        }
        
        listenersConfigurados.clear();
        console.log(`[EventManager] 🧹 Todos los listeners limpiados: ${eliminados} eliminados`);
        return eliminados;
    }
    
    /**
     * Obtener estadísticas de listeners
     */
    function obtenerEstadisticas() {
        const stats = {
            total: listenersConfigurados.size,
            porEvento: {},
            porElemento: {}
        };
        
        for (const [clave, config] of listenersConfigurados.entries()) {
            // Estadísticas por evento
            stats.porEvento[config.evento] = (stats.porEvento[config.evento] || 0) + 1;
            
            // Estadísticas por elemento
            const elementoId = config.elemento.id || config.elemento.tagName;
            stats.porElemento[elementoId] = (stats.porElemento[elementoId] || 0) + 1;
        }
        
        return stats;
    }
    
    /**
     * Verificar si un listener está configurado
     */
    function estaConfigurado(elemento, evento) {
        const clave = `${elemento.id || elemento.tagName}-${evento}`;
        return listenersConfigurados.has(clave);
    }
    
    /**
     * Configurar listeners para botones del wizard
     */
    function configurarBotonesWizard() {
        console.log('[EventManager] 🎯 Configurando botones del wizard...');
        
        const botones = {
            'btn-asignar-colores-tallas': () => window.ColoresPorTalla.toggleVistaAsignacion(),
            'btn-cancelar-wizard': () => {
                window.ColoresPorTalla.toggleVistaAsignacion();
                if (window.WizardManager) {
                    window.WizardManager.resetearWizard();
                }
            },
            'btn-guardar-asignacion': () => {
                window.ColoresPorTalla.wizardGuardarAsignacion();
                setTimeout(() => {
                    window.ColoresPorTalla.toggleVistaAsignacion();
                }, 500);
            },
            'wzd-btn-siguiente': () => window.WizardManager.pasoSiguiente(),
            'wzd-btn-atras': () => window.WizardManager.pasoAnterior()
        };
        
        let configurados = 0;
        
        for (const [id, callback] of Object.entries(botones)) {
            const boton = document.getElementById(id);
            if (boton) {
                if (registrar(boton, 'click', callback)) {
                    configurados++;
                }
            } else {
                console.warn(`[EventManager]  Botón no encontrado: ${id}`);
            }
        }
        
        console.log(`[EventManager]  Botones del wizard configurados: ${configurados}/${Object.keys(botones).length}`);
        return configurados;
    }
    
    /**
     * Configurar listeners para selects
     */
    function configurarSelects() {
        console.log('[EventManager] 🎯 Configurando selects...');
        
        const selects = {
            'asignacion-genero-select': () => window.ColoresPorTalla.actualizarTallasDisponibles(),
            'asignacion-talla-select': () => window.ColoresPorTalla.actualizarColoresDisponibles()
        };
        
        let configurados = 0;
        
        for (const [id, callback] of Object.entries(selects)) {
            const select = document.getElementById(id);
            if (select) {
                if (registrar(select, 'change', callback)) {
                    configurados++;
                }
            } else {
                console.warn(`[EventManager]  Select no encontrado: ${id}`);
            }
        }
        
        console.log(`[EventManager]  Selects configurados: ${configurados}/${Object.keys(selects).length}`);
        return configurados;
    }
    
    /**
     * Configurar listeners para colores personalizados
     */
    function configurarColoresPersonalizados() {
        console.log('[EventManager] 🎯 Configurando colores personalizados...');
        
        const botonColor = document.getElementById('btn-agregar-color-personalizado');
        if (botonColor) {
            const callback = () => window.ColoresPorTalla.agregarColorPersonalizado();
            if (registrar(botonColor, 'click', callback)) {
                console.log('[EventManager]  Botón color personalizado configurado');
                return true;
            }
        } else {
            console.warn('[EventManager]  Botón color personalizado no encontrado');
        }
        
        return false;
    }
    
    /**
     * Configurar todos los eventos del sistema
     */
    function configurarTodos() {
        console.log('[EventManager]  Configurando todos los eventos...');
        
        const resultados = {
            botones: configurarBotonesWizard(),
            selects: configurarSelects(),
            colores: configurarColoresPersonalizados()
        };
        
        const totalConfigurados = Object.values(resultados).reduce((sum, count) => sum + count, 0);
        
        console.log('[EventManager]  Resultados de configuración:', resultados);
        console.log(`[EventManager]  Total eventos configurados: ${totalConfigurados}`);
        
        return resultados;
    }
    
    /**
     * API Pública
     */
    return {
        registrar,
        remover,
        limpiarElemento,
        limpiarTodos,
        obtenerEstadisticas,
        estaConfigurado,
        configurarTodos,
        configurarBotonesWizard,
        configurarSelects,
        configurarColoresPersonalizados
    };
})();
