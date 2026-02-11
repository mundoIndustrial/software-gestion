/**
 * Gestor de Carga de Componentes Dinámicos
 * Maneja la carga dinámica de scripts y componentes
 */

class ComponentLoader {
    constructor() {
        this.init();
    }

    init() {
        // Hacer métodos disponibles globalmente para compatibilidad
        window.cargarReceiptManager = this.cargarReceiptManager.bind(this);
        window.cargarComponenteOrderDetailModal = this.cargarComponenteOrderDetailModal.bind(this);
    }

    /**
     * Carga el script de ReceiptManager
     */
    cargarReceiptManager(callback) {
        if (typeof ReceiptManager !== 'undefined') {
            // Ya está cargado
            if (callback) callback();
            return Promise.resolve();
        }

        return new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = '/js/asesores/receipt-manager.js';
            script.onload = () => {
                console.log('[ComponentLoader] ReceiptManager cargado exitosamente');
                if (callback) callback();
                resolve();
            };
            script.onerror = (error) => {
                console.error('[ComponentLoader] Error cargando ReceiptManager:', error);
                if (window.notificationManager) {
                    window.notificationManager.mostrarError('Error', 'No se pudo cargar el gestor de recibos');
                }
                reject(error);
            };
            document.head.appendChild(script);
        });
    }

    /**
     * Carga el componente order-detail-modal y lo adapta para recibos
     */
    cargarComponenteOrderDetailModal(contenedor, datos, prendasIndex = null) {
        if (typeof contenedor === 'string') {
            contenedor = document.getElementById(contenedor);
        }

        if (!contenedor) {
            console.error('[ComponentLoader] Contenedor no proporcionado');
            return Promise.reject(new Error('Contenedor no proporcionado'));
        }

        // Usar directamente el HTML que funciona
        contenedor.innerHTML = this.generarHTMLModal();

        // Configurar eventos del modal
        this.configurarEventosModal();

        // Cargar ReceiptManager si es necesario
        return this.cargarReceiptManager(() => {
            this.inicializarReceiptManager(datos, prendasIndex);
        });
    }

    /**
     * Genera el HTML del modal de recibos
     */
    generarHTMLModal() {
        return `
            <link rel="stylesheet" href="/css/order-detail-modal.css">
            
            <!-- Botón cerrar (X) en la esquina superior derecha -->
            <button id="btn-cerrar-modal" type="button" title="Cerrar" onclick="cerrarModalRecibos()" style="position: absolute; right: 0; top: 0; width: 40px; height: 40px; border-radius: 50%; background: rgba(255, 255, 255, 0.95); border: none; color: #333; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 24px; transition: all 0.3s ease; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2); z-index: 20; font-weight: bold;">
                <i class="fas fa-times"></i>
            </button>

            <div class="order-detail-modal-container" style="display: flex; flex-direction: column; width: 100%; height: 100%; position: relative;">

                <div class="order-detail-card">
                    <img src="/images/logo.png" alt="Mundo Industrial Logo" class="order-logo" width="150" height="80">
                    <div id="order-date" class="order-date">
                        <div class="fec-label">FECHA</div>
                        <div class="date-boxes">
                            <div class="date-box day-box" id="receipt-day"></div>
                            <div class="date-box month-box" id="receipt-month"></div>
                            <div class="date-box year-box" id="receipt-year"></div>
                        </div>
                    </div>
                    <div id="order-asesor" class="order-asesor">ASESOR: <span id="asesora-value"></span></div>
                    <div id="order-forma-pago" class="order-forma-pago">FORMA DE PAGO: <span id="forma-pago-value"></span></div>
                    <div id="order-cliente" class="order-cliente">CLIENTE: <span id="cliente-value"></span></div>
                    <div id="order-descripcion" class="order-descripcion">
                        <div id="descripcion-text"></div>
                    </div>
                    <h2 class="receipt-title" id="receipt-title">RECIBO DE COSTURA</h2>
                    <div class="arrow-container">
                        <button id="prev-arrow" class="arrow-btn" style="display: none;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="15 18 9 12 15 6"></polyline>
                            </svg>
                        </button>
                        <button id="next-arrow" class="arrow-btn" style="display: none;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="9 18 15 12 9 6"></polyline>
                            </svg>
                        </button>
                    </div>
                    <div id="order-pedido" class="pedido-number"></div>
                    <div class="separator-line"></div>
                    <div class="signature-section">
                        <div class="signature-field">
                            <span>ENCARGADO DE ORDEN:</span>
                            <span id="encargado-value"></span>
                        </div>
                        <div class="vertical-separator"></div>
                        <div class="signature-field">
                            <span>PRENDAS ENTREGADAS:</span>
                            <span id="prendas-entregadas-value"></span>
                            <a href="#" id="ver-entregas" style="color: red; font-weight: bold;">VER ENTREGAS</a>
                        </div>
                    </div>
                </div>

                <!-- Botones flotantes para cambiar a galería de fotos -->
                <div style="position: absolute; right: -80px; top: 50%; transform: translateY(-50%); display: flex; flex-direction: column; gap: 12px; z-index: 10;">
                    <button id="btn-factura" type="button" title="Ver factura" onclick="toggleFactura()" style="width: 56px; height: 56px; border-radius: 50%; background: linear-gradient(135deg, #1e40af, #0ea5e9); border: none; color: white; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 24px; transition: all 0.3s ease; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);">
                        <i class="fas fa-file-invoice"></i>
                    </button>
                    <button id="btn-galeria" type="button" title="Ver galería" onclick="toggleGaleria()" style="width: 56px; height: 56px; border-radius: 50%; background: linear-gradient(135deg, #10b981, #059669); border: none; color: white; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 24px; transition: all 0.3s ease; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);">
                        <i class="fas fa-images"></i>
                    </button>
                </div>

                <!-- Contenedor dinámico para prendas/procesos -->
                <div id="receipt-content" class="receipt-content" style="flex: 1; overflow-y: auto; padding: 20px;">
                    <!-- El contenido se cargará dinámicamente -->
                </div>

            </div>
        `;
    }

    /**
     * Configura los eventos del modal
     */
    configurarEventosModal() {
        // Evento para cerrar modal
        const btnCerrar = document.getElementById('btn-cerrar-modal');
        if (btnCerrar) {
            btnCerrar.addEventListener('click', () => {
                if (typeof cerrarModalRecibos === 'function') {
                    cerrarModalRecibos();
                }
            });
        }

        // Evento para cerrar con ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                if (typeof cerrarModalRecibos === 'function') {
                    cerrarModalRecibos();
                }
            }
        });

        // Evento para cerrar al hacer clic fuera
        const overlay = document.getElementById('modal-overlay');
        if (overlay) {
            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) {
                    if (typeof cerrarModalRecibos === 'function') {
                        cerrarModalRecibos();
                    }
                }
            });
        }
    }

    /**
     * Inicializa ReceiptManager con los datos
     */
    inicializarReceiptManager(datos, prendasIndex = null) {
        try {
            if (typeof ReceiptManager === 'undefined') {
                console.error('[ComponentLoader] ReceiptManager no está disponible');
                return;
            }

            console.debug('[ComponentLoader] Creando ReceiptManager con datos:', datos);
            window.receiptManager = new ReceiptManager(datos, prendasIndex);

            // Inicializar botón X para insumos si existe
            if (typeof inicializarBotonCerrarInsumos === 'function') {
                setTimeout(() => {
                    inicializarBotonCerrarInsumos();
                }, 200);
            }

        } catch (error) {
            console.error('[ComponentLoader] Error inicializando ReceiptManager:', error);
            if (window.notificationManager) {
                window.notificationManager.mostrarError('Error', 'Error al inicializar el gestor de recibos');
            }
        }
    }

    /**
     * Carga un script dinámicamente
     */
    cargarScript(src, id = null) {
        return new Promise((resolve, reject) => {
            // Verificar si ya está cargado
            if (id && document.getElementById(id)) {
                resolve();
                return;
            }

            const script = document.createElement('script');
            script.src = src;
            if (id) script.id = id;
            
            script.onload = () => {
                console.log(`[ComponentLoader] Script cargado: ${src}`);
                resolve();
            };
            
            script.onerror = (error) => {
                console.error(`[ComponentLoader] Error cargando script: ${src}`, error);
                reject(error);
            };
            
            document.head.appendChild(script);
        });
    }

    /**
     * Carga múltiples scripts en orden
     */
    cargarScriptsEnOrden(scripts) {
        return scripts.reduce((promise, script) => {
            return promise.then(() => this.cargarScript(script.src, script.id));
        }, Promise.resolve());
    }

    /**
     * Carga un CSS dinámicamente
     */
    cargarCSS(href, id = null) {
        return new Promise((resolve, reject) => {
            // Verificar si ya está cargado
            if (id && document.getElementById(id)) {
                resolve();
                return;
            }

            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = href;
            if (id) link.id = id;
            
            link.onload = () => {
                console.log(`[ComponentLoader] CSS cargado: ${href}`);
                resolve();
            };
            
            link.onerror = (error) => {
                console.error(`[ComponentLoader] Error cargando CSS: ${href}`, error);
                reject(error);
            };
            
            document.head.appendChild(link);
        });
    }

    /**
     * Verifica si un componente está cargado
     */
    estaCargado(componentName) {
        switch (componentName) {
            case 'ReceiptManager':
                return typeof ReceiptManager !== 'undefined';
            case 'order-detail-modal':
                return !!document.getElementById('order-detail-modal-wrapper');
            default:
                return false;
        }
    }

    /**
     * Espera a que un componente esté disponible
     */
    esperarComponente(componentName, timeout = 5000) {
        return new Promise((resolve, reject) => {
            const startTime = Date.now();
            
            const verificar = () => {
                if (this.estaCargado(componentName)) {
                    resolve();
                } else if (Date.now() - startTime > timeout) {
                    reject(new Error(`Timeout esperando componente: ${componentName}`));
                } else {
                    setTimeout(verificar, 100);
                }
            };
            
            verificar();
        });
    }

    /**
     * Limpia componentes cargados
     */
    limpiarComponentes() {
        // Limpiar ReceiptManager
        if (window.receiptManager) {
            delete window.receiptManager;
        }

        // Limpiar modales
        const modals = ['order-detail-modal-wrapper', 'modal-factura-overlay'];
        modals.forEach(id => {
            const element = document.getElementById(id);
            if (element) {
                element.remove();
            }
        });

        console.log('[ComponentLoader] Componentes limpiados');
    }

    /**
     * Recarga un componente específico
     */
    recargarComponente(componentName) {
        console.log(`[ComponentLoader] Recargando componente: ${componentName}`);
        
        switch (componentName) {
            case 'ReceiptManager':
                delete window.ReceiptManager;
                return this.cargarReceiptManager();
                
            default:
                return Promise.reject(new Error(`Componente no reconocido: ${componentName}`));
        }
    }

    /**
     * Obtiene el estado de carga de los componentes
     */
    getEstadoComponentes() {
        return {
            ReceiptManager: this.estaCargado('ReceiptManager'),
            orderDetailModal: this.estaCargado('order-detail-modal'),
            timestamp: new Date().toISOString()
        };
    }
}

// Inicializar el gestor cuando se cargue el script
document.addEventListener('DOMContentLoaded', () => {
    window.componentLoader = new ComponentLoader();
});

// También permitir inicialización manual
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.componentLoader = new ComponentLoader();
    });
} else {
    window.componentLoader = new ComponentLoader();
}
