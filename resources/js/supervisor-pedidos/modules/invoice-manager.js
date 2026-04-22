/**
 * Invoice Manager - Gestor de modales y vistas de facturas
 *
 * Responsabilidades:
 * - Cargar handlers de factura bajo demanda
 * - Gestionar modal de preview
 * - Cargar imageGallery cuando sea necesario
 *
 * Se carga SOLO si hay botones de factura en la vista
 * Esto evita cargar 100KB+ de código que no se necesita
 */

export class InvoiceManager {
    constructor() {
        this.isInitialized = false;
        this.handlersLoaded = false;
    }

    /**
     * Detectar si hay facturas en la página
     */
    static hasInvoiceButtons() {
        return Boolean(
            document.querySelector('[data-invoice-id]') ||
            document.querySelector('.btn-ver-factura') ||
            document.querySelector('.btn-invoice') ||
            document.getElementById('invoiceContainer')
        );
    }

    /**
     * Inicializar invoice manager
     */
    async init() {
        if (this.isInitialized) {
            return;
        }

        try {
            // Cargar legacy handlers desde public/js/ (por ahora)
            // En futuro: migrar completamente a módulos
            await this.loadLegacyHandlers();

            // Adjuntar event listeners
            this.attachEventListeners();

            this.isInitialized = true;
            console.log('[InvoiceManager] ✅ Initialized');
        } catch (error) {
            console.error('[InvoiceManager] Init error:', error);
            throw error;
        }
    }

    /**
     * Cargar handlers legacy desde public/js/
     * (Estos se migrarán gradualmente a ES6 modules)
     */
    async loadLegacyHandlers() {
        if (this.handlersLoaded) return;

        try {
            // Cargar scripts en paralelo
            await Promise.all([
                this.loadScript('/js/modulos/invoice/ModalManager.js'),
                this.loadScript('/js/modulos/invoice/InvoiceRenderer.js'),
                this.loadScript('/js/modulos/invoice/ImageGalleryManager.js'),
            ]);

            this.handlersLoaded = true;
            console.log('[InvoiceManager] Legacy handlers loaded');
        } catch (error) {
            console.warn('[InvoiceManager] Error loading handlers:', error);
            // No re-throw, invoice es opcional
        }
    }

    /**
     * Cargar script dinámicamente
     */
    loadScript(src) {
        return new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = src;
            script.onload = () => resolve();
            script.onerror = () => reject(new Error(`Failed to load ${src}`));
            document.head.appendChild(script);
        });
    }

    /**
     * Adjuntar event listeners para botones de factura
     */
    attachEventListeners() {
        // Delegar eventos de factura
        document.addEventListener('click', (e) => {
            // Botones para ver factura
            if (e.target.classList.contains('btn-ver-factura') ||
                e.target.closest('[data-invoice-id]')) {

                const invoiceId = e.target.dataset.invoiceId ||
                                 e.target.closest('[data-invoice-id]')?.dataset.invoiceId;

                if (invoiceId) {
                    this.verFactura(invoiceId);
                }
            }
        });
    }

    /**
     * Ver factura (función principal)
     */
    async verFactura(invoiceId) {
        try {
            console.log('[InvoiceManager] Ver factura:', invoiceId);

            // Asegurar que handlers estén cargados
            if (!this.handlersLoaded) {
                await this.loadLegacyHandlers();
            }

            // Esperar a que ModalManager esté disponible
            await this.waitForGlobalFunction('crearModalPreviewFactura', 3000);

            // Obtener datos de factura desde API
            const response = await fetch(`/api/invoices/${invoiceId}`);
            const data = await response.json();

            // Crear modal
            if (window.crearModalPreviewFactura) {
                window.crearModalPreviewFactura(data);
            } else {
                console.error('[InvoiceManager] Modal manager no disponible');
            }
        } catch (error) {
            console.error('[InvoiceManager] Error viendo factura:', error);
        }
    }

    /**
     * Esperar a que función global esté disponible
     */
    waitForGlobalFunction(functionName, timeout = 3000) {
        return new Promise((resolve, reject) => {
            const startTime = Date.now();

            const check = () => {
                if (window[functionName]) {
                    resolve();
                } else if (Date.now() - startTime > timeout) {
                    reject(new Error(`Timeout esperando ${functionName}`));
                } else {
                    setTimeout(check, 100);
                }
            };

            check();
        });
    }

    /**
     * Cleanup
     */
    destroy() {
        this.isInitialized = false;
        this.handlersLoaded = false;
    }
}

/**
 * Inicializar invoice manager solo si hay facturas en la página
 */
export async function initializeInvoiceManager() {
    // Solo inicializar si hay botones de factura
    if (!InvoiceManager.hasInvoiceButtons()) {
        console.log('[InvoiceManager] No invoice buttons found, skipping');
        return null;
    }

    const manager = new InvoiceManager();
    await manager.init();

    // Exponer globalmente
    globalThis.invoiceManager = manager;

    return manager;
}
