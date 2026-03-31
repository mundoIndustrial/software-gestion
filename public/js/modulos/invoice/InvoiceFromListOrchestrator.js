/**
 * Orquestador para Vista de Factura desde Lista de Pedidos
 * Coordina los módulos desacoplados para invoice-from-list
 */

class InvoiceFromListOrchestrator {
    constructor() {
        this.modulosCargados = false;
        this.init();
    }

    init() {
        // No verificar módulos al inicio - esperar a que se necesiten
        // Hacer función principal disponible globalmente
        window.verFacturaDelPedido = this.verFacturaDelPedido.bind(this);
        window.verRecibosDelPedido = this.verRecibosDelPedido.bind(this);
    }

    verificarModulos(modulosNecesarios = null) {
        // Si no se especifican módulos, verificar todos (comportamiento original)
        const modulosRequeridos = modulosNecesarios || [
            'invoiceDataFetcher',
            'invoiceModalManager',
            'receiptsModalManager',
            'loadingManager',
            'notificationManager',
            'componentLoader'
        ];

        const modulosFaltantes = modulosRequeridos.filter(modulo => !window[modulo]);

        if (modulosFaltantes.length > 0) {
            console.log('[InvoiceFromList] Esperando módulos, faltan:', modulosFaltantes);
            
            // Limitar reintentos para evitar bucle infinito
            if (!this.reintentos) this.reintentos = 0;
            this.reintentos++;
            
            if (this.reintentos < 20) {
                // Reintentar con un intervalo más largo
                setTimeout(() => this.verificarModulos(modulosNecesarios), 300);
            } else {
                console.error('[InvoiceFromList] No se pudieron cargar los módulos después de varios intentos');
                console.error('[InvoiceFromList] Intentando inicialización manual...');
                
                // Intentar inicialización manual como último recurso
                this.inicializarModulosManualmente();
            }
        } else {
            // Solo mostrar el éxito una vez para evitar spam
            if (!this.modulosCargados) {
                console.log('[InvoiceFromList]  Todos los módulos cargados correctamente');
            }
            this.modulosCargados = true;
            this.reintentos = 0; // Resetear contador
        }
    }

    /**
     * Abre la vista previa de factura para un pedido guardado
     */
    async verFacturaDelPedido(numeroPedido, pedidoId) {
        const modulosFactura = ['invoiceDataFetcher', 'invoiceModalManager', 'loadingManager', 'notificationManager'];
        
        // Verificar solo los módulos necesarios para factura
        if (!this.verificarModulosEspecificos(modulosFactura)) {
            alert('Los módulos de vista de factura no están cargados. Por favor recarga la página.');
            return;
        }
        
        try {
            console.log('[InvoiceFromList] Iniciando vista de factura:', { numeroPedido, pedidoId });
            
            // Usar el servicio de datos para obtener la información
            if (window.invoiceDataFetcher) {
                await window.invoiceDataFetcher.verFacturaDelPedido(numeroPedido, pedidoId);
            } else {
                throw new Error('Servicio de datos no disponible');
            }
            
        } catch (error) {
            console.error('[InvoiceFromList] Error en vista de factura:', error);
            
            if (window.notificationManager) {
                window.notificationManager.mostrarError(
                    'Error', 
                    'No se pudo cargar la factura: ' + error.message
                );
            }
        }
    }

    /**
     * Abre la vista de recibos dinámicos para un pedido
     */
    async verRecibosDelPedido(numeroPedido, pedidoId, prendasIndex = null) {
        const modulosRecibos = ['invoiceDataFetcher', 'receiptsModalManager', 'componentLoader', 'loadingManager', 'notificationManager'];
        
        // Verificar solo los módulos necesarios para recibos
        if (!this.verificarModulosEspecificos(modulosRecibos)) {
            alert('Los módulos de recibos no están cargados. Por favor recarga la página.');
            return;
        }
        
        try {
            console.log('[InvoiceFromList] Iniciando vista de recibos:', { numeroPedido, pedidoId });
            
            // Usar el servicio de datos para obtener la información
            if (window.invoiceDataFetcher) {
                await window.invoiceDataFetcher.verRecibosDelPedido(numeroPedido, pedidoId, prendasIndex);
            } else {
                throw new Error('Servicio de datos no disponible');
            }
            
        } catch (error) {
            console.error('[InvoiceFromList] Error en vista de recibos:', error);
            
            if (window.notificationManager) {
                window.notificationManager.mostrarError(
                    'Error', 
                    'No se pudieron cargar los recibos: ' + error.message
                );
            }
        }
    }

    /**
     * Verifica si módulos específicos están cargados
     */
    verificarModulosEspecificos(modulosRequeridos) {
        const modulosFaltantes = modulosRequeridos.filter(modulo => !window[modulo]);
        
        if (modulosFaltantes.length > 0) {
            console.log('[InvoiceFromList] Módulos faltantes para esta operación:', modulosFaltantes);
            return false;
        }
        
        return true;
    }

    /**
     * Inicialización manual de módulos (fallback)
     */
    inicializarModulosManualmente() {
        console.log('[InvoiceFromList] Inicializando módulos manualmente...');
        
        try {
            // Intentar inicializar cada módulo manualmente
            if (!window.invoiceDataFetcher && typeof InvoiceDataFetcher !== 'undefined') {
                window.invoiceDataFetcher = new InvoiceDataFetcher();
                console.log('[InvoiceFromList]  InvoiceDataFetcher inicializado manualmente');
            }
            
            if (!window.invoiceModalManager && typeof InvoiceModalManager !== 'undefined') {
                window.invoiceModalManager = new InvoiceModalManager();
                console.log('[InvoiceFromList]  InvoiceModalManager inicializado manualmente');
            }
            
            if (!window.receiptsModalManager && typeof ReceiptsModalManager !== 'undefined') {
                window.receiptsModalManager = new ReceiptsModalManager();
                console.log('[InvoiceFromList]  ReceiptsModalManager inicializado manualmente');
            }
            
            if (!window.loadingManager && typeof LoadingManager !== 'undefined') {
                window.loadingManager = new LoadingManager();
                console.log('[InvoiceFromList]  LoadingManager inicializado manualmente');
            }
            
            if (!window.notificationManager && typeof NotificationManager !== 'undefined') {
                window.notificationManager = new NotificationManager();
                console.log('[InvoiceFromList]  NotificationManager inicializado manualmente');
            }
            
            if (!window.componentLoader && typeof ComponentLoader !== 'undefined') {
                window.componentLoader = new ComponentLoader();
                console.log('[InvoiceFromList]  ComponentLoader inicializado manualmente');
            }
            
            // Verificar después de inicialización manual
            setTimeout(() => this.verificarModulos(), 100);
            
        } catch (error) {
            console.error('[InvoiceFromList] Error en inicialización manual:', error);
        }
    }

    /**
     * Obtiene estado de los módulos
     */
    getEstadoModulos() {
        return {
            modulosCargados: this.modulosCargados,
            invoiceDataFetcher: !!window.invoiceDataFetcher,
            invoiceModalManager: !!window.invoiceModalManager,
            receiptsModalManager: !!window.receiptsModalManager,
            loadingManager: !!window.loadingManager,
            notificationManager: !!window.notificationManager,
            componentLoader: !!window.componentLoader
        };
    }

    /**
     * Recarga los módulos si es necesario
     */
    recargarModulos() {
        console.log('[InvoiceFromList] Intentando recargar módulos...');
        this.modulosCargados = false;
        this.reintentos = 0;
        setTimeout(() => this.verificarModulos(), 500);
    }

    /**
     * Crea fallbacks para funciones antiguas
     */
    crearFallbacks() {
        console.warn('[InvoiceFromList] Creando fallbacks básicos - módulos no disponibles');
        
        // Fallbacks básicos si los módulos no están disponibles
        window.verFacturaDelPedido = function(numeroPedido, pedidoId) {
            console.warn('[InvoiceFromList] Vista de factura no disponible - módulo InvoiceDataFetcher no cargado');
            alert('Error: Sistema de vista de factura no cargado. Por favor recarga la página.');
        };
        
        window.verRecibosDelPedido = function(numeroPedido, pedidoId, prendasIndex) {
            console.warn('[InvoiceFromList] Vista de recibos no disponible - módulo InvoiceDataFetcher no cargado');
            alert('Error: Sistema de recibos no cargado. Por favor recarga la página.');
        };
        
        window.crearModalFacturaDesdeListaPedidos = function(datos) {
            console.warn('[InvoiceFromList] Modal de factura no disponible - módulo InvoiceModalManager no cargado');
            alert('Error: Modal de factura no disponible. Por favor recarga la página.');
        };
        
        window.crearModalRecibosDesdeListaPedidos = function(datos, prendasIndex) {
            console.warn('[InvoiceFromList] Modal de recibos no disponible - módulo ReceiptsModalManager no cargado');
            alert('Error: Modal de recibos no disponible. Por favor recarga la página.');
        };
        
        window.mostrarCargando = function(mensaje) {
            console.warn('[InvoiceFromList] Loading no disponible - módulo LoadingManager no cargado');
        };
        
        window.ocultarCargando = function() {
            console.warn('[InvoiceFromList] Loading no disponible - módulo LoadingManager no cargado');
        };
        
        window.mostrarErrorNotificacion = function(titulo, mensaje) {
            console.warn('[InvoiceFromList] Notificaciones no disponibles - módulo NotificationManager no cargado');
            alert(`${titulo}: ${mensaje}`);
        };
    }

    /**
     * Diagnóstico del sistema
     */
    diagnosticarSistema() {
        console.log('\n=== DIAGNÓSTICO DE MÓDULOS INVOICE FROM LIST ===');
        console.log('Timestamp:', new Date().toISOString());
        
        // Verificar scripts cargados
        const scripts = document.querySelectorAll('script[src*="modulos/invoice"]');
        console.log('\n Scripts encontrados:', scripts.length);
        
        scripts.forEach((script, index) => {
            console.log(`  ${index + 1}. ${script.src}`);
        });
        
        // Verificar variables globales
        console.log('\n Variables globales:');
        const modulos = [
            'InvoiceDataFetcher',
            'InvoiceModalManager',
            'ReceiptsModalManager',
            'LoadingManager',
            'NotificationManager',
            'ComponentLoader'
        ];
        
        modulos.forEach(modulo => {
            const claseDisponible = typeof window[modulo] !== 'undefined';
            const instanciaDisponible = !!window[modulo.charAt(0).toLowerCase() + modulo.slice(1)];
            
            console.log(`  ${modulo}:`);
            console.log(`    Clase: ${claseDisponible ? '' : ''}`);
            console.log(`    Instancia: ${instanciaDisponible ? '' : ''}`);
        });
        
        // Verificar orquestador
        console.log('\n🎯 Orquestador:');
        console.log(`  InvoiceFromListOrchestrator: ${typeof window.InvoiceFromListOrchestrator !== 'undefined' ? '' : ''}`);
        console.log(`  invoiceFromListOrchestrator: ${!!window.invoiceFromListOrchestrator ? '' : ''}`);
        
        if (window.invoiceFromListOrchestrator) {
            const estado = window.invoiceFromListOrchestrator.getEstadoModulos();
            console.log('  Estado:', estado);
        }
        
        // Verificar funciones globales
        console.log('\n Funciones globales:');
        const funciones = [
            'verFacturaDelPedido',
            'verRecibosDelPedido',
            'crearModalFacturaDesdeListaPedidos',
            'crearModalRecibosDesdeListaPedidos',
            'mostrarCargando',
            'ocultarCargando',
            'mostrarErrorNotificacion'
        ];
        
        funciones.forEach(funcion => {
            console.log(`  ${funcion}: ${typeof window[funcion] !== 'undefined' ? '' : ''}`);
        });
        
        console.log('\n=== FIN DEL DIAGNÓSTICO ===\n');
    }

    /**
     * Forzar carga de módulos
     */
    forzarCargaModulos() {
        console.log('[InvoiceFromList] Forzando carga de módulos...');
        
        // Intentar inicializar manualmente cada módulo
        if (!window.invoiceDataFetcher && typeof InvoiceDataFetcher !== 'undefined') {
            window.invoiceDataFetcher = new InvoiceDataFetcher();
            console.log(' InvoiceDataFetcher forzado');
        }
        
        if (!window.invoiceModalManager && typeof InvoiceModalManager !== 'undefined') {
            window.invoiceModalManager = new InvoiceModalManager();
            console.log(' InvoiceModalManager forzado');
        }
        
        if (!window.receiptsModalManager && typeof ReceiptsModalManager !== 'undefined') {
            window.receiptsModalManager = new ReceiptsModalManager();
            console.log(' ReceiptsModalManager forzado');
        }
        
        if (!window.loadingManager && typeof LoadingManager !== 'undefined') {
            window.loadingManager = new LoadingManager();
            console.log(' LoadingManager forzado');
        }
        
        if (!window.notificationManager && typeof NotificationManager !== 'undefined') {
            window.notificationManager = new NotificationManager();
            console.log(' NotificationManager forzado');
        }
        
        if (!window.componentLoader && typeof ComponentLoader !== 'undefined') {
            window.componentLoader = new ComponentLoader();
            console.log(' ComponentLoader forzado');
        }
        
        // Re-verificar
        setTimeout(() => this.verificarModulos(), 100);
    }
}

// Inicializar el orquestador
document.addEventListener('DOMContentLoaded', () => {
    window.invoiceFromListOrchestrator = new InvoiceFromListOrchestrator();
});

// También permitir inicialización manual
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.invoiceFromListOrchestrator = new InvoiceFromListOrchestrator();
    });
} else {
    window.invoiceFromListOrchestrator = new InvoiceFromListOrchestrator();
}

// Inicialización simple
if (typeof window !== 'undefined') {
    const orchestrator = new InvoiceFromListOrchestrator();
    
    // Hacer disponible globalmente para debugging
    window.invoiceFromListOrchestrator = orchestrator;
    window.diagnosticarInvoiceFromList = () => orchestrator.diagnosticarSistema();
    window.forzarModulosInvoiceFromList = () => orchestrator.forzarCargaModulos();
    window.recargarModulosInvoiceFromList = () => orchestrator.recargarModulos();
    window.estadoModulosInvoiceFromList = () => orchestrator.getEstadoModulos();
}
