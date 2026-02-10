/**
 * ================================================
 * PRENDAS MODULE - MAIN LOADER
 * ================================================
 * 
 * Archivo principal que carga todos los componentes del módulo
 * Mantiene compatibilidad con el sistema existente
 * 
 * @module PrendasModuleMain
 * @version 2.0.0
 */

// Definir namespace del módulo
if (!window.PrendasModule) {
    window.PrendasModule = {
        name: 'Prendas Module',
        version: '2.0.0',
        description: 'Sistema modular para gestión de prendas, imágenes y drag & drop',
        loaded: false,
        components: {}
    };
}

// Componentes del módulo en orden de dependencia
if (typeof window.components === 'undefined') {
    window.components = [
        {
            name: 'ui-helpers',
            file: '/js/componentes/prendas-module/ui-helpers.js',
            description: 'Utilidades de interfaz y helpers'
        },
        {
            name: 'image-management',
            file: '/js/componentes/prendas-module/image-management.js',
            description: 'Manejo de imágenes de prendas y telas'
        },
        {
            name: 'drag-drop-handlers',
            file: '/js/componentes/prendas-module/drag-drop-handlers.js',
            description: 'Funcionalidades de drag & drop'
        },
        {
            name: 'modal-wrappers',
            file: '/js/componentes/prendas-module/modal-wrappers.js',
            description: 'Gestión de modales de prendas'
        }
    ];
}

// Función para cargar un componente
function loadComponent(component) {
    return new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = component.file;
        script.async = true;
        
        script.onload = () => {
            window.PrendasModule.components[component.name] = true;
            resolve();
        };
        
        script.onerror = () => {
            console.error(`❌ Error cargando componente: ${component.name}`);
            reject(new Error(`Failed to load component: ${component.name}`));
        };
        
        document.head.appendChild(script);
    });
}

// Cargar todos los componentes
async function loadAllComponents() {
    try {
        for (const component of window.components) {
            await loadComponent(component);
        }
        
        // Marcar como completamente cargado
        window.PrendasModule.loaded = true;
        
        // Disparar evento de carga completa
        if (typeof window.dispatchEvent === 'function') {
            window.dispatchEvent(new CustomEvent('prendasModuleLoaded', {
                detail: {
                    module: window.PrendasModule,
                    components: window.components
                }
            }));
        }
        
        // Disparar evento legacy para compatibilidad
        if (typeof window.dispatchEvent === 'function') {
            window.dispatchEvent(new CustomEvent('prendasWrappersLoaded'));
        }
        
        return true;
        
    } catch (error) {
        console.error('❌ Error cargando Prendas Module:', error);
        window.PrendasModule.error = error;
        return false;
    }
}

// Función para exportar las funciones públicas al window
window.PrendasModule.exportFunctions = function() {
    // Las funciones ya están definidas globalmente en modal-wrappers.js 
    // Esta función retorna las funciones reales, no solo boolean checks
    return {
        abrirModalPrendaNueva: window.abrirModalPrendaNueva || false,
        cerrarModalPrendaNueva: window.cerrarModalPrendaNueva || false,
        agregarPrendaNueva: window.agregarPrendaNueva || false,
        cargarItemEnModal: window.cargarItemEnModal || false,
        manejarImagenesPrenda: window.manejarImagenesPrenda || false
    };
};

// Iniciar carga cuando el DOM esté listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', loadAllComponents);
} else {
    // El DOM ya está cargado
    loadAllComponents();
}
