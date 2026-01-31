/**
 * INICIALIZADOR DE SERVICIOS DE IMÁGENES - Para Edición de Pedidos
 * 
 * Responsabilidades:
 * - Inicializar ImageStorageService para edición de prendas
 * - Sin espera, sin bucles, sin timeouts
 * - Carga directa y síncrona
 */

(function() {
    'use strict';

    // Control para evitar múltiples inicializaciones
    let inicializado = false;

    // Función para inicializar los servicios
    function inicializarServicios() {
        if (inicializado) {
            console.log('🔧 [INIT-STORAGE] Servicios ya inicializados, omitiendo...');
            return;
        }
        
        console.log('🔧 [INIT-STORAGE] Inicializando servicios de imágenes...');
        
        // Verificar si ImageStorageService está disponible
        if (typeof ImageStorageService === 'undefined') {
            console.error('❌ [INIT-STORAGE] ImageStorageService no está disponible. Asegúrate de que image-storage-service.js esté cargado.');
            return;
        }
        
        // Inicializar servicios directamente
        try {
            window.imagenesPrendaStorage = new ImageStorageService(3);
            window.imagenesTelaStorage = new ImageStorageService(3);
            window.imagenesReflectivoStorage = new ImageStorageService(3);
            inicializado = true;
            console.log('✅ [INIT-STORAGE] Servicios de imágenes inicializados correctamente');
        } catch (error) {
            console.error('❌ [INIT-STORAGE] Error al inicializar servicios:', error);
        }
    }

    // Verificar si el DOM ya está cargado
    if (document.readyState === 'loading') {
        // El DOM todavía está cargando, esperar al evento
        document.addEventListener('DOMContentLoaded', inicializarServicios);
    } else {
        // El DOM ya está cargado, ejecutar inmediatamente
        inicializarServicios();
    }

})();
