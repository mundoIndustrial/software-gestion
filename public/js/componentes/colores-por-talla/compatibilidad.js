/**
 * Archivo de Compatibilidad - Colores por Talla
 * Mantiene compatibilidad con la API antigua mientras se migra al nuevo sistema desacoplado
 */

// Funciones globales de compatibilidad (API antigua)
window.obtenerDatosAsignacionesColores = function() {
    if (window.ColoresPorTalla && typeof window.ColoresPorTalla.obtenerDatosAsignaciones === 'function') {
        return window.ColoresPorTalla.obtenerDatosAsignaciones();
    }
    console.warn('[Compatibilidad] ⚠️ ColoresPorTalla no disponible, retornando objeto vacío');
    return {};
};

window.limpiarAsignacionesColores = function() {
    if (window.ColoresPorTalla && typeof window.ColoresPorTalla.limpiarAsignaciones === 'function') {
        return window.ColoresPorTalla.limpiarAsignaciones();
    }
    console.warn('[Compatibilidad] ⚠️ ColoresPorTalla no disponible, no se pueden limpiar asignaciones');
};

window.cargarAsignacionesPrevias = function(datos) {
    if (window.ColoresPorTalla && typeof window.ColoresPorTalla.cargarAsignacionesPrevias === 'function') {
        return window.ColoresPorTalla.cargarAsignacionesPrevias(datos);
    }
    console.warn('[Compatibilidad] ⚠️ ColoresPorTalla no disponible, no se pueden cargar asignaciones previas');
};

// Funciones del wizard para compatibilidad
window.toggleVistaAsignacionColores = function() {
    console.log('[Compatibilidad] 🔍 toggleVistaAsignacionColores - Función llamada');
    console.log('[Compatibilidad] 🔍 ColoresPorTalla disponible:', !!window.ColoresPorTalla);
    
    // Intentar usar el módulo si está disponible
    if (window.ColoresPorTalla && typeof window.ColoresPorTalla.toggleVistaAsignacion === 'function') {
        console.log('[Compatibilidad] ✅ Usando módulo ColoresPorTalla.toggleVistaAsignacion()');
        try {
            return window.ColoresPorTalla.toggleVistaAsignacion();
        } catch (error) {
            console.error('[Compatibilidad] ❌ Error ejecutando ColoresPorTalla.toggleVistaAsignacion():', error);
        }
    } else {
        console.warn('[Compatibilidad] ⚠️ ColoresPorTalla.toggleVistaAsignacion no disponible, usando fallback directo');
    }
    
    // FALLBACK ROBUSTO: Implementación directa
    return toggleVistaAsignacionColoresFallback();
};

/**
 * Fallback directo - Implementación sin depender del módulo
 */
function toggleVistaAsignacionColoresFallback() {
    console.log('[Compatibilidad] 🚨 EJECUTANDO FALLBACK DIRECTO');
    
    try {
        const vistaTablaTelas = document.getElementById('vista-tabla-telas');
        const vistaAsignacion = document.getElementById('vista-asignacion-colores');
        const btnAsignar = document.getElementById('btn-asignar-colores-tallas');
        
        console.log('[Compatibilidad] 🔍 Fallback - Elementos encontrados:', {
            vistaTablaTelas: !!vistaTablaTelas,
            vistaAsignacion: !!vistaAsignacion,
            btnAsignar: !!btnAsignar
        });
        
        if (!vistaTablaTelas || !vistaAsignacion) {
            console.error('[Compatibilidad] ❌ Elementos críticos no encontrados');
            console.error('[Compatibilidad] IDs buscados:', {
                'vista-tabla-telas': document.getElementById('vista-tabla-telas') ? 'EXISTE' : 'NO EXISTE',
                'vista-asignacion-colores': document.getElementById('vista-asignacion-colores') ? 'EXISTE' : 'NO EXISTE'
            });
            
            // Último recurso: listar todos los divs que contienen "vista"
            const divosConVista = document.querySelectorAll('div[id*="vista"]');
            console.log('[Compatibilidad] Divs con "vista" en ID:', Array.from(divosConVista).map(d => d.id));
            
            return false;
        }
        
        const esVistaAsignacionActiva = vistaAsignacion.style.display !== 'none';
        console.log('[Compatibilidad] 🔍 Fallback - Estado actual:', {
            displayAsignacion: vistaAsignacion.style.display,
            esVistaAsignacionActiva: esVistaAsignacionActiva
        });
        
        if (esVistaAsignacionActiva) {
            // Volver a vista de telas
            console.log('[Compatibilidad] 📋 ACCIÓN: Mostrando tabla de telas');
            vistaTablaTelas.style.display = 'block';
            vistaAsignacion.style.display = 'none';
            
            if (btnAsignar) {
                btnAsignar.innerHTML = '<span class="material-symbols-rounded" style="font-size: 1.1rem;">color_lens</span>Asignar por Talla';
            }
            
            console.log('[Compatibilidad] ✅ Toggle exitoso - Tabla VISIBLE');
            return true;
        } else {
            // Cambiar a vista de asignación
            console.log('[Compatibilidad] 📋 ACCIÓN: Mostrando vista de asignación');
            vistaTablaTelas.style.display = 'none';
            vistaAsignacion.style.display = 'block';
            
            if (btnAsignar) {
                btnAsignar.innerHTML = '<span class="material-symbols-rounded" style="font-size: 1.1rem;">table_chart</span>Ver Telas';
            }
            
            console.log('[Compatibilidad] ✅ Toggle exitoso - Asignación VISIBLE');
            return true;
        }
    } catch (error) {
        console.error('[Compatibilidad] ❌ Error en fallback directo:', error);
        console.error('[Compatibilidad] Stack:', error.stack);
        return false;
    }
}

window.wizardPasoSiguiente = function() {
    if (window.WizardManager && typeof window.WizardManager.pasoSiguiente === 'function') {
        // ✅ Usar .call() para mantener el contexto de WizardManager
        return window.WizardManager.pasoSiguiente.call(window.WizardManager);
    }
    console.warn('[Compatibilidad] ⚠️ WizardManager no disponible, no se puede avanzar paso');
};

window.wizardPasoAnterior = function() {
    if (window.WizardManager && typeof window.WizardManager.pasoAnterior === 'function') {
        // ✅ Usar .call() para mantener el contexto de WizardManager
        return window.WizardManager.pasoAnterior.call(window.WizardManager);
    }
    console.warn('[Compatibilidad] ⚠️ WizardManager no disponible, no se puede retroceder paso');
};

window.wizardGuardarAsignacion = function() {
    if (window.ColoresPorTalla && typeof window.ColoresPorTalla.wizardGuardarAsignacion === 'function') {
        return window.ColoresPorTalla.wizardGuardarAsignacion();
    }
    console.warn('[Compatibilidad] ⚠️ ColoresPorTalla no disponible, no se puede guardar asignación wizard');
};

// Funciones adicionales del wizard
window.wizardSeleccionarTela = function(tela) {
    if (window.WizardManager && typeof window.WizardManager.seleccionarTela === 'function') {
        return window.WizardManager.seleccionarTela(tela);
    }
    console.warn('[Compatibilidad] ⚠️ WizardManager no disponible, no se puede seleccionar tela');
};

window.wizardSeleccionarGenero = function(genero) {
    if (window.WizardManager && typeof window.WizardManager.seleccionarGenero === 'function') {
        return window.WizardManager.seleccionarGenero(genero);
    }
    console.warn('[Compatibilidad] ⚠️ WizardManager no disponible, no se puede seleccionar género');
};

window.wizardReset = function() {
    if (window.WizardManager && typeof window.WizardManager.resetWizard === 'function') {
        return window.WizardManager.resetWizard();
    }
    console.warn('[Compatibilidad] ⚠️ WizardManager no disponible, no se puede resetear wizard');
};

// Variables globales de compatibilidad (para lectura)
Object.defineProperty(window, 'asignacionesColoresPorTalla', {
    get: function() {
        if (window.StateManager && typeof window.StateManager.getAsignaciones === 'function') {
            return window.StateManager.getAsignaciones();
        }
        console.warn('[Compatibilidad] ⚠️ StateManager no disponible, retornando objeto vacío');
        return {};
    },
    set: function(value) {
        if (window.StateManager && typeof window.StateManager.setAsignaciones === 'function') {
            return window.StateManager.setAsignaciones(value);
        }
        console.warn('[Compatibilidad] ⚠️ StateManager no disponible, no se pueden establecer asignaciones');
    }
});

Object.defineProperty(window, 'wizardState', {
    get: function() {
        if (window.StateManager && typeof window.StateManager.getWizardState === 'function') {
            return window.StateManager.getWizardState();
        }
        console.warn('[Compatibilidad] ⚠️ StateManager no disponible, retornando objeto vacío');
        return {};
    },
    set: function(value) {
        if (window.StateManager && typeof window.StateManager.setWizardState === 'function') {
            return window.StateManager.setWizardState(value);
        }
        console.warn('[Compatibilidad] ⚠️ StateManager no disponible, no se puede establecer estado del wizard');
    }
});

Object.defineProperty(window, 'tallasDisponiblesPorGenero', {
    get: function() {
        if (window.StateManager && typeof window.StateManager.getState === 'function') {
            const state = window.StateManager.getState();
            return state.tallasDisponiblesPorGenero || {};
        }
        console.warn('[Compatibilidad] ⚠️ StateManager no disponible, retornando objeto vacío');
        return {};
    }
});
