/**
 * IMAGE STORAGE INITIALIZATION
 * ═══════════════════════════════════════════════════════════════
 * Handles initialization of three image storage service instances
 * 
 * Functionality:
 * - Creates ImageStorageService instances for prenda, tela, reflectivo
 * - Manages in-memory image buffers (prenda: 6, others configurable)
 * - Provides global access via window.imagenesPrendaStorage, etc.
 * - Validates ImageStorageService class availability
 * - Prevents duplicate instance creation
 * 
 * Global Objects Exposed:
 * - window.imagenesPrendaStorage - ImageStorageService instance for prendas
 * - window.imagenesTelaStorage - ImageStorageService instance for telas
 * - window.imagenesReflectivoStorage - ImageStorageService instance for reflectivos
 * 
 * Dependencies:
 * - ImageStorageService class (loaded from image-storage-service.js defer script)
 */

(function() {
    'use strict';

    /**
     * Initialize image storage service instances for different media types
     */
    window.InitializeImageStorages = function() {
        console.log('[image-storage-init] Inicializando servicios de almacenamiento de imágenes...');

        // Verificar disponibilidad de servicios
        if (typeof ImageStorageService === 'undefined') {
            console.error('[image-storage-init] ImageStorageService no está disponible. Verifique que image-storage-service.js se haya cargado.');
            return false;
        }

        if (typeof IndexedImageStorageService === 'undefined') {
            console.error('[image-storage-init] IndexedImageStorageService no está disponible. Verifique que indexed-image-storage-service.js se haya cargado.');
            return false;
        }

        // ✅ CAMBIO: Usar IndexedImageStorageService para prendas (previene desincronización)
        if (!window.imagenesPrendaStorage) {
            window.imagenesPrendaStorage = new IndexedImageStorageService(6);
            console.log('[image-storage-init] imagenesPrendaStorage inicializado como IndexedImageStorageService ✓');
        } else {
            console.log('[image-storage-init] imagenesPrendaStorage ya existe, reutilizando instancia');
        }

        // Crear instancia para tela si no existe (mantiene ImageStorageService simple)
        if (!window.imagenesTelaStorage) {
            window.imagenesTelaStorage = new ImageStorageService(3);
            console.log('[image-storage-init] imagenesTelaStorage inicializado ✓');
        } else {
            console.log('[image-storage-init] imagenesTelaStorage ya existe, reutilizando instancia');
        }

        // Crear instancia para reflectivo si no existe (mantiene ImageStorageService simple)
        if (!window.imagenesReflectivoStorage) {
            window.imagenesReflectivoStorage = new ImageStorageService(3);
            console.log('[image-storage-init] imagenesReflectivoStorage inicializado ✓');
        } else {
            console.log('[image-storage-init] imagenesReflectivoStorage ya existe, reutilizando instancia');
        }

        console.log('[image-storage-init] Servicios de almacenamiento de imágenes inicializados ✓');
        return true;
    };

})();
