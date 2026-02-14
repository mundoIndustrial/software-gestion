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
        this.fallbackWarningMostrado = false;
        this.init();
    }

    init() {
        // Esperar a que todos los módulos estén disponibles
        this.verificarModulos();
        
        // Hacer función principal disponible globalmente
        window.abrirPreviewFacturaEnVivo = this.abrirPreviewFacturaEnVivo.bind(this);
    }

    verificarModulos() {
        const modulosRequeridos = [
            'imageGalleryManager',
            'formDataCaptureService', 
            'invoiceRenderer',
            'modalManager',
            'invoiceExportService'
        ];

        const modulosFaltantes = modulosRequeridos.filter(modulo => !window[modulo]);

        if (modulosFaltantes.length > 0) {
            // Limitar reintentos para evitar bucle infinito
            if (!this.reintentos) this.reintentos = 0;
            this.reintentos++;
            
            if (this.reintentos < 20) {
                // Reintentar con un intervalo más largo
                setTimeout(() => this.verificarModulos(), 300);
            } else {
                console.error('[InvoicePreview] No se pudieron cargar los módulos después de varios intentos');
                console.error('[InvoicePreview] Intentando inicialización manual...');
                
                // Intentar inicialización manual como último recurso
                this.inicializarModulosManualmente();
                
                // Si la inicialización manual también falla, activar fallbacks
                setTimeout(() => {
                    if (!this.modulosCargados) {
                        this.crearFallbacks();
                    }
                }, 1000);
            }
        } else {
            // Solo mostrar el éxito una vez para evitar spam
            if (!this.modulosCargados) {
                // Módulos cargados correctamente
            }
            this.modulosCargados = true;
            this.reintentos = 0; // Resetear contador
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
            const datosFormulario = window.formDataCaptureService.capturarDatosFormulario();
            
            if (!datosFormulario) {
                alert('Por favor completa los datos básicos del pedido');
                return;
            }
            
            // Crear modal con la vista previa usando el gestor de modales
            window.modalManager.crearModalPreviewFactura(datosFormulario);
            
        } catch (error) {
            console.error('[InvoicePreview] Error al abrir vista previa:', error);
            alert('Ocurrió un error al generar la vista previa. Por favor intenta nuevamente.');
        }
    }

    /**
     * Método de compatibilidad para versiones antiguas
     */
    inicializarCompatibilidad() {
        // Mantener compatibilidad con variables globales antiguas
        if (!window._galeríasPreview) window._galeríasPreview = {};
        if (!window._idGaleriaPreview) window._idGaleriaPreview = 0;
        
        // No crear fallbacks aquí - esperar a que el proceso de carga complete
        // Los fallbacks se crearán solo si realmente falla la carga de módulos
    }

    crearFallbacks() {
        // Solo mostrar warning si ya pasó suficiente tiempo y los módulos no cargaron
        if (!this.fallbackWarningMostrado) {
            console.warn('[InvoicePreview] Activando modo compatibilidad - módulos no disponibles');
            this.fallbackWarningMostrado = true;
        }
        
        // Fallbacks básicos si los módulos no están disponibles
        window._extraerURLImagen = function(img) {
            if (!img) return '';
            if (typeof img === 'string') return img;
            return img.url || img.ruta || img.src || '';
        };

        window._registrarGalería = function(imagenes, titulo) {
            return null;
        };

        window._abrirGaleriaImagenesDesdeID = function(id) {
            console.warn('Galería no disponible - módulo ImageGalleryManager no cargado');
        };

        window.capturarDatosFormulario = function() {
            console.warn('Captura de datos no disponible - módulo FormDataCaptureService no cargado');
            return null;
        };

        window.generarHTMLFactura = function(datos) {
            return '<div style="color: #dc2626; padding: 1rem;">Error: Módulos no cargados</div>';
        };

        window.crearModalPreviewFactura = function(datos) {
            alert('Error: Módulos de vista previa no cargados. Por favor recarga la página.');
        };
    }

    /**
     * Obtiene estado de los módulos
     */
    getEstadoModulos() {
        return {
            modulosCargados: this.modulosCargados,
            imageGalleryManager: !!window.imageGalleryManager,
            formDataCaptureService: !!window.formDataCaptureService,
            invoiceRenderer: !!window.invoiceRenderer,
            modalManager: !!window.modalManager,
            invoiceExportService: !!window.invoiceExportService
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

    /**
     * Inicialización manual de módulos (fallback)
     */
    inicializarModulosManualmente() {
        console.log('[InvoicePreview] Inicializando módulos manualmente...');
        
        try {
            // Intentar inicializar cada módulo manualmente
            if (!window.imageGalleryManager && typeof ImageGalleryManager !== 'undefined') {
                window.imageGalleryManager = new ImageGalleryManager();
                console.log('[InvoicePreview]  ImageGalleryManager inicializado manualmente');
            }
            
            if (!window.formDataCaptureService && typeof FormDataCaptureService !== 'undefined') {
                window.formDataCaptureService = new FormDataCaptureService();
                console.log('[InvoicePreview]  FormDataCaptureService inicializado manualmente');
            }
            
            if (!window.invoiceRenderer && typeof InvoiceRenderer !== 'undefined') {
                window.invoiceRenderer = new InvoiceRenderer();
                console.log('[InvoicePreview]  InvoiceRenderer inicializado manualmente');
            }
            
            if (!window.modalManager && typeof ModalManager !== 'undefined') {
                window.modalManager = new ModalManager();
                console.log('[InvoicePreview]  ModalManager inicializado manualmente');
            }
            
            if (!window.invoiceExportService && typeof InvoiceExportService !== 'undefined') {
                window.invoiceExportService = new InvoiceExportService();
                console.log('[InvoicePreview]  InvoiceExportService inicializado manualmente');
            }
            
            // Verificar después de inicialización manual
            setTimeout(() => this.verificarModulos(), 100);
            
        } catch (error) {
            console.error('[InvoicePreview] Error en inicialización manual:', error);
        }
    }
}

// Inicializar el orquestador
document.addEventListener('DOMContentLoaded', () => {
    window.invoicePreviewOrchestrator = new InvoicePreviewOrchestrator();
});

// También permitir inicialización manual
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.invoicePreviewOrchestrator = new InvoicePreviewOrchestrator();
    });
} else {
    window.invoicePreviewOrchestrator = new InvoicePreviewOrchestrator();
}

// Inicializar compatibilidad inmediatamente
if (typeof window !== 'undefined') {
    const orchestrator = new InvoicePreviewOrchestrator();
    orchestrator.inicializarCompatibilidad();
    
    // Hacer disponible globalmente para debugging
    window.invoicePreviewOrchestrator = orchestrator;
    window.inicializarModulosInvoicePreview = () => orchestrator.inicializarModulosManualmente();
    window.recargarModulosInvoicePreview = () => orchestrator.recargarModulos();
    window.estadoModulosInvoicePreview = () => orchestrator.getEstadoModulos();
}

/**
 * Función de compatibilidad para mantener la API original
 * Esta función ahora delega al orquestador
 */
window.abrirPreviewFacturaEnVivo = function() {
    if (window.invoicePreviewOrchestrator) {
        return window.invoicePreviewOrchestrator.abrirPreviewFacturaEnVivo();
    } else {
        console.error('[InvoicePreview] Orquestador no disponible');
        alert('Error: Sistema de vista previa no inicializado. Por favor recarga la página.');
    }
};
