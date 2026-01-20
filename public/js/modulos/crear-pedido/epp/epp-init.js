/**
 * EppInit - Inicializa todos los servicios de EPP
 * Este archivo carga todos los servicios en el orden correcto
 * 
 * Servicios inicializados:
 * - EppNotificationService: Gestión de notificaciones y modales
 * - EppCreationService: Creación de nuevos EPPs
 * - EppFormManager: Gestión de formularios
 * - EppModalInterface: Interfaz unificada del modal
 * - EppService: Orquestador principal
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('[EppInit] Inicializando servicios de EPP...');

    // Inicializar servicios de notificaciones
    if (window.eppNotificationService) {
        console.log('[EppInit] EppNotificationService disponible');
    }

    // Inicializar servicio de creación de EPP
    if (typeof EppCreationService !== 'undefined' && window.eppApiService) {
        window.eppCreationService = new EppCreationService(
            window.eppApiService,
            window.eppNotificationService
        );
        console.log('[EppInit] EppCreationService inicializado');
    }

    // Inicializar gestor de formularios
    if (window.eppFormManager) {
        console.log('[EppInit] EppFormManager disponible');
    }

    // Inicializar interfaz del modal
    if (window.EppModalInterface && window.eppService && window.eppImagenManager) {
        window.EppModalInterface.initialize(
            window.eppService,
            window.eppImagenManager,
            window.eppCreationService,
            window.eppFormManager
        );
        console.log('[EppInit] EppModalInterface inicializada');
    }

    // Inicializar servicio principal
    if (window.eppService) {
        window.eppService.inicializar();
        console.log('[EppInit] EppService inicializado');
    }

    // Agregar event listeners al modal
    _inicializarEventListeners();

    console.log('[EppInit] Servicios de EPP listos');
});

/**
 * Inicializar event listeners del modal
 */
function _inicializarEventListeners() {
    // Input de talla
    const inputTalla = document.getElementById('medidaTallaEPP');
    if (inputTalla) {
        inputTalla.addEventListener('input', () => {
            window.eppService.actualizarBoton();
        });
    }

    // Input de cantidad
    const inputCantidad = document.getElementById('cantidadEPP');
    if (inputCantidad) {
        inputCantidad.addEventListener('input', () => {
            window.eppService.actualizarBoton();
        });
    }

    // Input de imágenes
    const inputImagenes = document.getElementById('inputCargaImagenesEPP');
    if (inputImagenes) {
        inputImagenes.addEventListener('change', (e) => {
            window.eppImagenManager?.manejarSeleccionImagenes(e);
        });
    }

    console.log('[EppInit] Event listeners inicializados');
}

/**
 * Funciones globales para compatibilidad con el modal existente
 */

function abrirModalAgregarEPP() {
    window.eppService?.abrirModalAgregar();
}

function cerrarModalAgregarEPP() {
    window.eppService?.cerrarModal();
}

function agregarEPPAlPedido() {
    window.eppService?.guardarEPP();
}

function editarItemEPP(id, nombre, codigo, categoria, talla, cantidad, observaciones, imagenes) {
    window.eppService?.editarEPPFormulario(id, nombre, codigo, categoria, talla, cantidad, observaciones, imagenes);
}

function editarEPPDesdeDB(eppId) {
    window.eppService?.editarEPPDesdeDB(eppId);
}

function eliminarItemEPP(id) {
    window.eppService?.eliminarEPP(id);
}

function actualizarEstilosBotonEPP() {
    window.eppService?.actualizarBoton();
}

console.log('[EppInit] Funciones globales registradas');
