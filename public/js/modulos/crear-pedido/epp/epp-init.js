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


    // Inicializar servicios de notificaciones
    if (window.eppNotificationService) {

    }

    // Inicializar servicio de creación de EPP
    if (typeof EppCreationService !== 'undefined' && window.eppApiService) {
        window.eppCreationService = new EppCreationService(
            window.eppApiService,
            window.eppNotificationService
        );

    }

    // Inicializar gestor de formularios
    if (window.eppFormManager) {

    }

    // Inicializar interfaz del modal
    if (window.EppModalInterface && window.eppService && window.eppImagenManager) {
        window.EppModalInterface.initialize(
            window.eppService,
            window.eppImagenManager,
            window.eppCreationService,
            window.eppFormManager
        );

    }

    // Inicializar servicio principal
    if (window.eppService) {
        window.eppService.inicializar();
    }

    // Agregar event listeners al modal
    _inicializarEventListeners();

});

/**
 * Inicializar event listeners del modal
 */
function _inicializarEventListeners() {
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

}

/**
 * Funciones globales para compatibilidad
 * Nota: abrirModalAgregarEPP() y cerrarModalAgregarEPP() 
 * están definidas en el template Blade (modal-agregar-epp.blade.php)
 * No las definimos aquí para no sobrescribir las del template
 */

function agregarEPPAlPedido() {
    window.eppService?.guardarEPP();
}

function editarItemEPP(id, nombre, codigo, categoria, cantidad, observaciones, imagenes) {
    window.eppService?.editarEPPFormulario(id, nombre, codigo, categoria, cantidad, observaciones, imagenes);
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


