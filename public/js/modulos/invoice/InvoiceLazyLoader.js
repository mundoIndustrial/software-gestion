/**
 * Gestor de Carga Lazy para Módulos de Invoice
 * Carga los módulos solo cuando se necesitan
 */

class InvoiceLazyLoader {
    constructor() {
        this.modulosCargados = new Set();
        this.modulosCargando = new Set();
        this.callbacksPendientes = new Map();
        this.precargaInteligenteEjecutada = false;
        this.init();
    }

    init() {
        // Hacer disponible globalmente
        window.invoiceLazyLoader = this;
        
        // Sobreescribir las funciones principales para que carguen los módulos bajo demanda
        this.setupLazyFunctions();
    }

    /**
     * Configura las funciones principales para carga lazy
     */
    setupLazyFunctions() {
        // Sobreescribir verFacturaDelPedido
        window.verFacturaDelPedido = async (numeroPedido, pedidoId) => {
            await this.cargarModulosFactura();
            if (window.invoiceFromListOrchestrator) {
                return window.invoiceFromListOrchestrator.verFacturaDelPedido(numeroPedido, pedidoId);
            }
        };

        // Sobreescribir verRecibosDelPedido
        window.verRecibosDelPedido = async (numeroPedido, pedidoId, prendasIndex) => {
            await this.cargarModulosRecibos();
            if (window.invoiceFromListOrchestrator) {
                return window.invoiceFromListOrchestrator.verRecibosDelPedido(numeroPedido, pedidoId, prendasIndex);
            }
        };
    }

    /**
     * Carga los módulos necesarios para factura
     */
    async cargarModulosFactura() {
        const modulosFactura = [
            'InvoiceDataFetcher',
            'InvoiceModalManager',
            'LoadingManager',
            'NotificationManager',
            'InvoiceFromListOrchestrator'
        ];

        await this.cargarModulos(modulosFactura);
    }

    /**
     * Carga los módulos necesarios para recibos
     */
    async cargarModulosRecibos() {
        const modulosRecibos = [
            'InvoiceDataFetcher',
            'ReceiptsModalManager',
            'ComponentLoader',
            'LoadingManager',
            'NotificationManager',
            'InvoiceFromListOrchestrator'
        ];

        await this.cargarModulos(modulosRecibos);
    }

    /**
     * Carga los módulos especificados
     */
    async cargarModulos(modulos) {
        const promesas = modulos.map(modulo => this.cargarModulo(modulo));
        await Promise.all(promesas);
        
        // Inicializar el orquestador si todos los módulos están cargados
        if (this.todosModulosCargados(modulos)) {
            this.inicializarOrquestador();
        }
    }

    /**
     * Carga un módulo específico
     */
    async cargarModulo(modulo) {
        // Si ya está cargado, retornar inmediatamente
        if (this.modulosCargados.has(modulo)) {
            return Promise.resolve();
        }

        // Si está cargando, esperar a que termine
        if (this.modulosCargando.has(modulo)) {
            return new Promise((resolve) => {
                if (!this.callbacksPendientes.has(modulo)) {
                    this.callbacksPendientes.set(modulo, []);
                }
                this.callbacksPendientes.get(modulo).push(resolve);
            });
        }

        // Marcar como cargando
        this.modulosCargando.add(modulo);

        try {
            const scriptUrl = this.getUrlModulo(modulo);
            await this.cargarScript(scriptUrl, modulo);
            
            // Marcar como cargado
            this.modulosCargados.add(modulo);
            this.modulosCargando.delete(modulo);
            
            // Ejecutar callbacks pendientes
            if (this.callbacksPendientes.has(modulo)) {
                const callbacks = this.callbacksPendientes.get(modulo);
                callbacks.forEach(callback => callback());
                this.callbacksPendientes.delete(modulo);
            }
            
            console.log(`[InvoiceLazyLoader]  Módulo ${modulo} cargado`);
            
        } catch (error) {
            this.modulosCargando.delete(modulo);
            console.error(`[InvoiceLazyLoader]  Error cargando ${modulo}:`, error);
            throw error;
        }
    }

    /**
     * Obtiene la URL del script para un módulo
     */
    getUrlModulo(modulo) {
        const urls = {
            'InvoiceDataFetcher': '/js/modulos/invoice/InvoiceDataFetcher.js',
            'InvoiceModalManager': '/js/modulos/invoice/InvoiceModalManager.js',
            'ReceiptsModalManager': '/js/modulos/invoice/ReceiptsModalManager.js',
            'LoadingManager': '/js/modulos/invoice/LoadingManager.js',
            'NotificationManager': '/js/modulos/invoice/NotificationManager.js',
            'ComponentLoader': '/js/modulos/invoice/ComponentLoader.js',
            'InvoiceFromListOrchestrator': '/js/modulos/invoice/InvoiceFromListOrchestrator.js'
        };

        const url = urls[modulo];
        if (!url) {
            throw new Error(`URL no encontrada para módulo: ${modulo}`);
        }

        return `${url}?v=${Date.now()}`;
    }

    /**
     * Carga un script dinámicamente
     * Verifica si el script ya está en el DOM para evitar duplicados
     */
    cargarScript(url, modulo) {
        return new Promise((resolve, reject) => {
            // Verificar si el script ya existe en el DOM
            const scriptExistente = Array.from(document.querySelectorAll('script')).find(
                script => script.src.includes(`/${modulo}.js`)
            );
            
            if (scriptExistente) {
                console.log(`[InvoiceLazyLoader] Script ${modulo} ya está en el DOM, usando existente`);
                resolve();
                return;
            }

            // Marcar el módulo en window para evitar re-declaraciones
            if (window[`_loaded_${modulo}`]) {
                console.log(`[InvoiceLazyLoader] Módulo ${modulo} ya fue cargado previamente`);
                resolve();
                return;
            }

            const script = document.createElement('script');
            script.src = url;
            script.async = true;
            script.setAttribute('data-module', modulo);
            
            script.onload = () => {
                // Marcar como cargado
                window[`_loaded_${modulo}`] = true;
                console.log(`[InvoiceLazyLoader] Script ${modulo} cargado desde ${url}`);
                resolve();
            };
            
            script.onerror = (error) => {
                console.error(`[InvoiceLazyLoader] Error cargando script ${modulo}:`, error);
                reject(error);
            };
            
            document.head.appendChild(script);
        });
    }

    /**
     * Verifica si todos los módulos están cargados
     */
    todosModulosCargados(modulos) {
        return modulos.every(modulo => this.modulosCargados.has(modulo));
    }

    /**
     * Inicializa el orquestador
     */
    inicializarOrquestador() {
        if (typeof InvoiceFromListOrchestrator !== 'undefined' && !window.invoiceFromListOrchestrator) {
            window.invoiceFromListOrchestrator = new InvoiceFromListOrchestrator();
            console.log('[InvoiceLazyLoader]  Orquestador inicializado');
        }
    }

    /**
     * Precarga módulos específicos
     */
    async precargarModulos(modulos) {
        console.log('[InvoiceLazyLoader] Precargando módulos:', modulos);
        await this.cargarModulos(modulos);
    }

    /**
     * Precarga todos los módulos (opcional)
     */
    async precargarTodosLosModulos() {
        const todosLosModulos = [
            'InvoiceDataFetcher',
            'InvoiceModalManager',
            'ReceiptsModalManager',
            'LoadingManager',
            'NotificationManager',
            'ComponentLoader',
            'InvoiceFromListOrchestrator'
        ];
        
        await this.precargarModulos(todosLosModulos);
    }

    /**
     * Obtiene el estado de carga de módulos
     */
    getEstadoModulos() {
        return {
            cargados: Array.from(this.modulosCargados),
            cargando: Array.from(this.modulosCargando),
            total: 7
        };
    }

    /**
     * Limpia los módulos cargados (para testing)
     */
    limpiarModulos() {
        this.modulosCargados.clear();
        this.modulosCargando.clear();
        this.callbacksPendientes.clear();
        
        // Limpiar variables globales
        delete window.invoiceFromListOrchestrator;
        delete window.invoiceDataFetcher;
        delete window.invoiceModalManager;
        delete window.receiptsModalManager;
        delete window.loadingManager;
        delete window.notificationManager;
        delete window.componentLoader;
        
        console.log('[InvoiceLazyLoader]  Módulos limpiados');
    }

    /**
     * Detecta si el usuario está en una página que necesita factura
     */
    necesitaFactura() {
        const rutasFactura = [
            '/supervisor-pedidos',
            '/supervisor-asesores/pedidos',
            '/insumos/materiales',
            '/asesores/pedidos'
        ];
        
        return rutasFactura.some(ruta => window.location.pathname.includes(ruta));
    }

    /**
     * Precarga inteligente basada en la página actual
     */
    precargaInteligente() {
        if (this.precargaInteligenteEjecutada) {
            return;
        }
        this.precargaInteligenteEjecutada = true;

        if (!this.necesitaFactura()) {
            return;
        }

        // Precargar módulos básicos después de un tiempo
        setTimeout(() => {
            this.precargarModulos(['LoadingManager', 'NotificationManager']);
        }, 2000);
        
        // Precargar módulos de factura si hay botones de factura en la página
        if (document.querySelector('[onclick*="verFacturaDelPedido"]')) {
            setTimeout(() => {
                this.cargarModulosFactura();
            }, 3000);
        }
        
        // Precargar módulos de recibos si hay botones de recibos
        if (document.querySelector('[onclick*="verRecibosDelPedido"]')) {
            setTimeout(() => {
                this.cargarModulosRecibos();
            }, 4000);
        }
    }
}

function initInvoiceLazyLoaderOnce() {
    if (window.__invoiceLazyLoaderInitialized) {
        return;
    }
    window.__invoiceLazyLoaderInitialized = true;

    if (!window.invoiceLazyLoader) {
        window.invoiceLazyLoader = new InvoiceLazyLoader();
    }

    setTimeout(() => {
        window.invoiceLazyLoader.precargaInteligente();
    }, 1000);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initInvoiceLazyLoaderOnce, { once: true });
} else {
    initInvoiceLazyLoaderOnce();
}

// Funciones globales para debugging
window.estadoModulosLazy = () => window.invoiceLazyLoader.getEstadoModulos();
window.precargarTodosModulos = () => window.invoiceLazyLoader.precargarTodosLosModulos();
window.limpiarModulosLazy = () => window.invoiceLazyLoader.limpiarModulos();
