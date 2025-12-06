/**
 * Inicialización del Sistema de Cotizaciones
 * 
 * Este archivo actúa como orquestador central para asegurar que todos los
 * módulos se carguen en el orden correcto y estén disponibles cuando se necesiten.
 */

(function() {
    'use strict';

    // Verificar que todos los módulos necesarios estén disponibles
    const requiredModules = [
        { name: 'window.routes', description: 'Rutas Laravel' },
        { name: 'window.tipoCotizacionGlobal', description: 'Tipo de cotización global' },
        { name: 'agregarProductoFriendly', description: 'Función agregarProductoFriendly' },
        { name: 'actualizarSelectTallas', description: 'Función actualizarSelectTallas' },
    ];

    // Función auxiliar para verificar si existe una propiedad anidada
    function propertyExists(obj, path) {
        return path.split('.').every(prop => !!(obj = obj?.[prop]));
    }

    // Verificar módulos con timeout
    let verificacionesCompletadas = 0;
    let maxIntentos = 0;
    const maxIntentosPermitidos = 50; // 5 segundos con 100ms de espera

    function verificarModulos() {
        maxIntentos++;
        let todosDisponibles = true;

        for (const modulo of requiredModules) {
            if (modulo.name.includes('.')) {
                // Propiedad anidada como "window.routes"
                if (!propertyExists(window, modulo.name)) {
                    todosDisponibles = false;
                    console.warn(`⚠️ Esperando ${modulo.description}...`);
                }
            } else {
                // Función global
                if (typeof window[modulo.name] !== 'function') {
                    todosDisponibles = false;
                    console.warn(`⚠️ Esperando ${modulo.description}...`);
                }
            }
        }

        if (todosDisponibles) {
            console.log('✅ Todos los módulos están disponibles');
            inicializarFormulario();
        } else if (maxIntentos < maxIntentosPermitidos) {
            // Reintentar después de 100ms
            setTimeout(verificarModulos, 100);
        } else {
            console.error('❌ Error: Algunos módulos no se cargaron después de 5 segundos');
            console.error('Módulos requeridos:', requiredModules);
        }
    }

    // Inicializar cuando el DOM esté listo
    function inicializarFormulario() {
        console.log('🎯 Inicializando formulario de cotizaciones...');

        // Configuración global
        if (typeof window.routes === 'object') {
            console.log('✓ Rutas disponibles:', Object.keys(window.routes));
        }

        if (typeof window.tipoCotizacionGlobal === 'string') {
            console.log(`✓ Tipo de cotización: ${window.tipoCotizacionGlobal}`);
        }

        // Aquí puedes agregar más inicializaciones específicas
        console.log('✅ Formulario inicializado correctamente');
    }

    // Iniciar verificación cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', verificarModulos);
    } else {
        verificarModulos();
    }
})();
