/**
 * ================================================
 * PRENDAS MODULE - INDEX
 * ================================================
 * 
 * Punto de entrada principal para el módulo de prendas
 * Carga todos los componentes del módulo en orden correcto
 * 
 * @module PrendasModule
 * @version 2.0.0
 */

// Definir el namespace del módulo
window.PrendasModule = {
    name: 'Prendas Module',
    version: '2.0.0',
    description: 'Sistema modular para gestión de prendas, imágenes y drag & drop',
    loaded: false,
    components: {
        'ui-helpers': false,
        'image-management': false,
        'modal-wrappers': false
    }
};

// Componentes del módulo en orden de dependencia
const components = [
    {
        name: 'ui-helpers',
        path: '/js/componentes/prendas-module/ui-helpers.js',
        description: 'Utilidades de interfaz y helpers'
    },
    {
        name: 'image-management',
        path: '/js/componentes/prendas-module/image-management.js',
        description: 'Manejo de imágenes de prendas y telas'
    },
    {
        name: 'modal-wrappers',
        path: '/js/componentes/prendas-module/modal-wrappers.js',
        description: 'Gestión de modales de prendas'
    }
];

// Función para cargar un componente
function loadComponent(component) {
    return new Promise((resolve, reject) => {
        console.log(` Cargando componente: ${component.name}`);
        
        const script = document.createElement('script');
        script.src = component.path;
        script.async = true;
        
        script.onload = () => {
            console.log(` Componente cargado: ${component.name}`);
            window.PrendasModule.components[component.name] = true;
            resolve();
        };
        
        script.onerror = () => {
            console.error(` Error cargando componente: ${component.name}`);
            reject(new Error(`Failed to load component: ${component.name}`));
        };
        
        document.head.appendChild(script);
    });
}

// Cargar todos los componentes
async function loadAllComponents() {
    try {
        console.log(' Iniciando carga de componentes del módulo de prendas...');
        
        for (const component of components) {
            await loadComponent(component);
        }
        
        // Marcar como completamente cargado
        window.PrendasModule.loaded = true;
        
        console.log('🎉 Módulo de prendas completamente cargado');
        console.log(' Componentes disponibles:');
        components.forEach(comp => {
            console.log(`   ${comp.name}: ${comp.description}`);
        });
        
        // Disparar evento de carga completa
        if (typeof window.dispatchEvent === 'function') {
            window.dispatchEvent(new CustomEvent('prendasModuleLoaded', {
                detail: {
                    module: window.PrendasModule,
                    components: components
                }
            }));
        }
        
        // Disparar evento legacy para compatibilidad
        if (typeof window.dispatchEvent === 'function') {
            window.dispatchEvent(new CustomEvent('prendasWrappersLoaded'));
        }
        
        return true;
        
    } catch (error) {
        console.error(' Error cargando módulo de prendas:', error);
        window.PrendasModule.error = error;
        return false;
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
console.log('📋 Prendas Module v2.0.0 - Sistema modular de gestión de prendas');
console.log('📁 Estructura del módulo:');
console.log('  📁 prendas-module/');
console.log('    📄 index.js (este archivo)');
console.log('    📄 ui-helpers.js');
console.log('    📄 image-management.js');
console.log('    📄 modal-wrappers.js');
console.log('    📄 prendas-wrappers-v2.js');
console.log('    📄 README.md');
