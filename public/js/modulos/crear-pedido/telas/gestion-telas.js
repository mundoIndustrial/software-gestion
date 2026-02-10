/**
 * ================================================
 * TELAS MODULE - MAIN LOADER
 * ================================================
 * 
 * Archivo principal que carga todos los componentes del módulo de telas
 * Sistema modular v2.0 - Reemplazo completo del sistema antiguo
 * 
 * @module TelasModuleMain
 * @version 2.0.0
 */

// Definir namespace del módulo
if (!window.TelasModule) {
    window.TelasModule = {
        name: 'Telas Module',
        version: '2.0.0',
        description: 'Sistema modular para gestión de telas, colores, referencias e imágenes',
        loaded: false,
        components: {}
    };
}

// Componentes del módulo en orden de dependencia
const components = [
    {
        name: 'estado-validacion',
        file: '/js/modulos/crear-pedido/telas/telas-module/estado-validacion.js',
        description: 'Estado global y validaciones de campos'
    },
    {
        name: 'gestion-telas',
        file: '/js/modulos/crear-pedido/telas/telas-module/gestion-telas.js',
        description: 'CRUD de telas (agregar, eliminar, actualizar)'
    },
    {
        name: 'manejo-imagenes',
        file: '/js/modulos/crear-pedido/telas/telas-module/manejo-imagenes.js',
        description: 'Manejo de imágenes y galería'
    },
    {
        name: 'ui-renderizado',
        file: '/js/modulos/crear-pedido/telas/telas-module/ui-renderizado.js',
        description: 'UI y renderizado de tabla'
    },
    {
        name: 'storage-datos',
        file: '/js/modulos/crear-pedido/telas/telas-module/storage-datos.js',
        description: 'Storage y obtención de datos'
    }
];

// Función para cargar un componente
function loadComponent(component) {
    return new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = component.file;
        script.async = true;
        
        script.onload = () => {
            window.TelasModule.components[component.name] = true;
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
        for (const component of components) {
            await loadComponent(component);
        }
        
        // Marcar como completamente cargado
        window.TelasModule.loaded = true;
        
        // Disparar evento de carga completa
        if (typeof window.dispatchEvent === 'function') {
            window.dispatchEvent(new CustomEvent('telasModuleLoaded', {
                detail: {
                    module: window.TelasModule,
                    components: components
                }
            }));
        }
        
    } catch (error) {
        console.error('❌ Error cargando Telas Module:', error);
        window.TelasModule.error = error;
    }
}

// Iniciar carga cuando el DOM esté listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', loadAllComponents);
} else {
    // El DOM ya está cargado
    loadAllComponents();
}

// Exportar información del módulo
