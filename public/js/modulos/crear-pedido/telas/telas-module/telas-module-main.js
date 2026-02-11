/**
 * ================================================
 * TELAS MODULE - MAIN LOADER
 * ================================================
 * 
 * Archivo principal que carga todos los componentes del módulo de telas
 * Mantiene compatibilidad con el sistema existente
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
        file: './estado-validacion.js',
        description: 'Estado global y validaciones de campos'
    },
    {
        name: 'gestion-telas',
        file: './gestion-telas.js',
        description: 'CRUD de telas (agregar, eliminar, actualizar)'
    },
    {
        name: 'manejo-imagenes',
        file: './manejo-imagenes.js',
        description: 'Manejo de imágenes y galería'
    },
    {
        name: 'ui-renderizado',
        file: './ui-renderizado.js',
        description: 'UI y renderizado de tabla'
    },
    {
        name: 'storage-datos',
        file: './storage-datos.js',
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
            console.error(` Error cargando componente: ${component.name}`);
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
        
        return true;
        
    } catch (error) {
        console.error(' Error cargando Telas Module:', error);
        window.TelasModule.error = error;
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

// Funciones globales para compatibilidad
window.limpiarErrorTela = window.limpiarErrorTela;
window.inicializarEventosTela = window.inicializarEventosTela;
window.agregarTelaNueva = window.agregarTelaNueva;
window.eliminarTela = window.eliminarTela;
window.actualizarTablaTelas = window.actualizarTablaTelas;
window.manejarImagenTela = window.manejarImagenTela;
window.mostrarGaleriaImagenesTemporales = window.mostrarGaleriaImagenesTemporales;
window.mostrarGaleriaImagenesTela = window.mostrarGaleriaImagenesTela;
window.obtenerTelasParaEnvio = window.obtenerTelasParaEnvio;
window.obtenerTelasParaEdicion = window.obtenerTelasParaEdicion;
window.obtenerImagenesTelaParaEnvio = window.obtenerImagenesTelaParaEnvio;
window.limpiarTelas = window.limpiarTelas;
window.obtenerResumenTelas = window.obtenerResumenTelas;
window.tieneTelas = window.tieneTelas;
window.obtenerTelasConImagenes = window.obtenerTelasConImagenes;
window.obtenerTelasSinImagenes = window.obtenerTelasSinImagenes;
window.buscarTelasPorColor = window.buscarTelasPorColor;
window.buscarTelasPorNombre = window.buscarTelasPorNombre;
window.exportarDatosTelas = window.exportarDatosTelas;
window.importarDatosTelas = window.importarDatosTelas;
window.serializarDatosTelas = window.serializarDatosTelas;
window.restaurarDatosTelas = window.restaurarDatosTelas;

console.log(' Telas Module v2.0.0 - Sistema modular de gestión de telas listo');
