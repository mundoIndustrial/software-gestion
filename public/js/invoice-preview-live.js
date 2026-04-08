/**
 * Preview de Factura en Tiempo Real - Versión Desacoplada
 * Orquestador principal que utiliza módulos especializados
 * 
 * Módulos utilizados:
 * - ImageGalleryManager: Gestión de galerías de imágenes
 * - FormDataCaptureService: Captura de datos del formulario
 * - InvoiceRenderer: Generación de HTML de la factura
 * - ModalManager: Gestión de modales
 * - InvoiceExportService: Exportación y utilidades
 */

// Importar y verificar que los módulos estén cargados
class InvoicePreviewOrchestrator {
    constructor() {
        this.modulosCargados = false;
        this.resuelto = false;
        this.reintentos = 0;
        this.init();
    }

    init() {
        // Esperar a que todos los módulos estén disponibles
        this.verificarModulos();
        
        // Hacer función principal disponible globalmente
        globalThis.abrirPreviewFacturaEnVivo = this.abrirPreviewFacturaEnVivo.bind(this);
    }

    verificarModulos() {
        // Si ya se resolvió, no seguir
        if (this.resuelto) return;

        const modulosRequeridos = [
            'imageGalleryManager',
            'formDataCaptureService', 
            'invoiceRenderer',
            'modalManager',
            'invoiceExportService'
        ];

        const modulosFaltantes = modulosRequeridos.filter(modulo => !globalThis[modulo]);

        if (modulosFaltantes.length === 0) {
            this.modulosCargados = true;
            this.resuelto = true;
            return;
        }

        this.reintentos++;

        if (this.reintentos < 30) {
            // Aún hay tiempo, reintentar en 500ms
            setTimeout(() => this.verificarModulos(), 500);
        } else {
            // 15 segundos sin éxito - parar sin sobreescribir funciones globales
            this.resuelto = true;
            console.warn('[InvoicePreview] Módulos de preview en vivo no disponibles:', modulosFaltantes.join(', '));
            console.warn('[InvoicePreview] La función abrirPreviewFacturaEnVivo no estará disponible en esta página.');
        }
    }

    /**
     * Abre una vista previa en vivo de la factura con datos del formulario actual
     */
    abrirPreviewFacturaEnVivo() {
        if (!this.modulosCargados) {
            alert('Los módulos de la vista previa no están cargados. Por favor recarga la página.');
            return;
        }
        
        try {
            // Capturar datos del formulario usando el servicio especializado
            const datosFormulario = globalThis.formDataCaptureService.capturarDatosFormulario();
            
            if (!datosFormulario) {
                alert('Por favor completa los datos básicos del pedido');
                return;
            }
            
            // Crear modal con la vista previa usando el gestor de modales
            globalThis.modalManager.crearModalPreviewFactura(datosFormulario);
            
        } catch (error) {
            console.error('[InvoicePreview] Error al abrir vista previa:', error);
            alert('Ocurrió un error al generar la vista previa. Por favor intenta nuevamente.');
        }
    }



    /**
     * Obtiene estado de los módulos
     */
    getEstadoModulos() {
        return {
            modulosCargados: this.modulosCargados,
            imageGalleryManager: !!globalThis.imageGalleryManager,
            formDataCaptureService: !!globalThis.formDataCaptureService,
            invoiceRenderer: !!globalThis.invoiceRenderer,
            modalManager: !!globalThis.modalManager,
            invoiceExportService: !!globalThis.invoiceExportService
        };
    }

    /**
     * Recarga los módulos si es necesario
     */
    recargarModulos() {
        console.log('[InvoicePreview] Intentando recargar módulos...');
        this.modulosCargados = false;
        this.reintentos = 0;
        setTimeout(() => this.verificarModulos(), 500);
    }

}

// Inicializar el orquestador (una sola vez)
if (!globalThis.invoicePreviewOrchestrator) {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            if (!globalThis.invoicePreviewOrchestrator) {
                globalThis.invoicePreviewOrchestrator = new InvoicePreviewOrchestrator();
            }
        });
    } else {
        globalThis.invoicePreviewOrchestrator = new InvoicePreviewOrchestrator();
    }
}

/**
 * Función de compatibilidad para mantener la API original
 * Esta función ahora delega al orquestador
 */
globalThis.abrirPreviewFacturaEnVivo = function() {
    if (globalThis.invoicePreviewOrchestrator) {
        return globalThis.invoicePreviewOrchestrator.abrirPreviewFacturaEnVivo();
    } else {
        console.error('[InvoicePreview] Orquestador no disponible');
        alert('Error: Sistema de vista previa no inicializado. Por favor recarga la página.');
    }
};
