/**
 * ================================================
 * PRENDAS WRAPPERS - COMPATIBILITY LAYER
 * ================================================
 * 
 * Este archivo mantiene compatibilidad con el sistema existente
 * mientras carga la nueva arquitectura modular
 * 
 * @deprecated Usar prendas-wrappers-v2.js para nuevas implementaciones
 */

// Cargar el nuevo sistema modular
const script = document.createElement('script');
script.src = '/js/componentes/prendas-module/prendas-wrappers-v2.js';
script.async = true;

script.onload = () => {
    // Las funciones se exportarán cuando todos los componentes estén carguados
    // Ver listener de prendasModuleLoaded abajo
};

script.onerror = () => {
    console.error('❌ Error cargando el sistema modular de Prendas Wrappers');
};

document.head.appendChild(script);

// Escuchar evento de carga completa del módulo (después de que todos los componentes se carguen)
window.addEventListener('prendasModuleLoaded', function() {
    console.log('[prendas-wrappers] 🔄 prendasModuleLoaded event fired - exportando funciones...');
    
    // Exportar funciones al window después de que todos los componentes estén cargados
    if (window.PrendasModule && window.PrendasModule.exportFunctions) {
        const exported = window.PrendasModule.exportFunctions();
        console.log('✅ Prendas Module exportado:', exported);
        
        // Hacer disponibles en el window SOLO si son funciones reales
        if (typeof exported.abrirModalPrendaNueva === 'function') {
            window.abrirModalPrendaNueva = exported.abrirModalPrendaNueva;
            console.log('✅ abrirModalPrendaNueva asignada');
        }
        if (typeof exported.agregarPrendaNueva === 'function') {
            window.agregarPrendaNueva = exported.agregarPrendaNueva;
            console.log('✅ agregarPrendaNueva asignada');
        }
        if (typeof exported.cargarItemEnModal === 'function') {
            window.cargarItemEnModal = exported.cargarItemEnModal;
            console.log('✅ cargarItemEnModal asignada');
        }
        if (typeof exported.manejarImagenesPrenda === 'function') {
            window.manejarImagenesPrenda = exported.manejarImagenesPrenda;
            console.log('✅ manejarImagenesPrenda asignada');
        }
        if (typeof exported.cerrarModalPrendaNueva === 'function') {
            window.cerrarModalPrendaNueva = exported.cerrarModalPrendaNueva;
            console.log('✅ cerrarModalPrendaNueva asignada');
        }
    }
});

// Fallback para prendasWrappersLoaded (para compatibilidad)
window.addEventListener('prendasWrappersLoaded', function() {
    console.log('[prendas-wrappers] 🔄 prendasWrappersLoaded event fired');
    
    // Crear fallbacks solo si las funciones no están disponibles como funciones reales
    if (typeof window.abrirModalPrendaNueva !== 'function') {
        console.log('[prendas-wrappers] ⚠️ Usando fallback para abrirModalPrendaNueva');
        window.abrirModalPrendaNueva = function() {
            const modal = document.getElementById('modal-agregar-prenda-nueva');
            if (modal) {
                modal.style.display = 'flex';
            }
        };
    }
    
    if (typeof window.manejarImagenesPrenda !== 'function') {
        console.log('[prendas-wrappers] ⚠️ Usando fallback para manejarImagenesPrenda');
        window.manejarImagenesPrenda = function(input) {
            if (input.files && input.files.length > 0) {
                console.log('📸 Archivo recibido:', input.files[0].name);
            }
        };
    }
});

// Fallbacks de emergencia (si el módulo no se carga en 5 segundos)
setTimeout(() => {
    if (typeof window.abrirModalPrendaNueva !== 'function') {
        console.warn('⚠️ Módulo no cargado, usando fallback de emergencia para abrirModalPrendaNueva');
        window.abrirModalPrendaNueva = function() {
            const modal = document.getElementById('modal-agregar-prenda-nueva');
            if (modal) {
                modal.style.display = 'flex';
            }
        };
    }

    if (typeof window.manejarImagenesPrenda !== 'function') {
        console.warn('⚠️ Módulo no cargado, usando fallback de emergencia para manejarImagenesPrenda');
        window.manejarImagenesPrenda = function(input) {
            if (input.files && input.files.length > 0) {
                console.log('📸 Archivo recibido:', input.files[0].name);
            }
        };
    }
}, 5000);

