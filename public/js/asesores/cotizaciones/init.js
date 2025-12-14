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

        // 🔄 AGREGAR EVENT LISTENERS PARA ACTUALIZAR RESUMEN EN TIEMPO REAL
        const camposAObservar = [
            'cliente',
            'fechaActual',
            'descripcion_logo',
            'observaciones_tecnicas'
        ];

        camposAObservar.forEach(campoId => {
            const campo = document.getElementById(campoId);
            if (campo) {
                // Input/change para cambios
                campo.addEventListener('input', () => {
                    console.log(`📝 Campo ${campoId} modificado, actualizando resumen...`);
                    actualizarResumenFriendly();
                });
                
                // Change para inputs de fecha/select
                campo.addEventListener('change', () => {
                    console.log(`📝 Campo ${campoId} cambiado, actualizando resumen...`);
                    actualizarResumenFriendly();
                });
            }
        });

        // Observar cambios en técnicas seleccionadas
        const tecnicasContainer = document.getElementById('tecnicas_seleccionadas');
        if (tecnicasContainer) {
            const observer = new MutationObserver(() => {
                console.log('🔄 Técnicas modificadas, actualizando resumen...');
                actualizarResumenFriendly();
            });
            observer.observe(tecnicasContainer, { childList: true, subtree: true });
        }

        // Observar adición/eliminación de productos
        const formSection = document.querySelector('.form-section');
        if (formSection) {
            const observer = new MutationObserver(() => {
                console.log('🔄 Productos modificados, actualizando resumen...');
                setTimeout(() => actualizarResumenFriendly(), 100);
            });
            observer.observe(formSection, { childList: true, subtree: true });
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
