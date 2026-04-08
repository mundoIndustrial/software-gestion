if (window.__invoiceFromListInitialized) {
    console.debug('[InvoiceFromList] Init omitido: ya estaba inicializado');
} else {
    window.__invoiceFromListInitialized = true;

/**
 * Vista de Factura desde Lista de Pedidos - Versión con Lazy Loading
 * Este archivo ahora es mínimo y depende del InvoiceLazyLoader
 * 
 * Los módulos se cargarán bajo demanda cuando se necesiten:
 * - Factura: InvoiceDataFetcher, InvoiceModalManager, LoadingManager, NotificationManager
 * - Recibos: InvoiceDataFetcher, ReceiptsModalManager, ComponentLoader, LoadingManager, NotificationManager
 */

// Esperar a que el lazy loader esté disponible
function esperarLazyLoader(callback) {
    if (window.invoiceLazyLoader) {
        callback();
    } else {
        setTimeout(() => esperarLazyLoader(callback), 100);
    }
}

// Funciones de compatibilidad que delegan al lazy loader
window.verFacturaDelPedido = function(numeroPedido, pedidoId) {
    esperarLazyLoader(() => {
        if (window.invoiceLazyLoader) {
            // El lazy loader se encargará de cargar los módulos necesarios
            window.invoiceLazyLoader.cargarModulosFactura().then(() => {
                // Usar el InvoiceDataFetcher directamente
                if (window.invoiceDataFetcher) {
                    return window.invoiceDataFetcher.verFacturaDelPedido(numeroPedido, pedidoId);
                } else {
                    throw new Error('InvoiceDataFetcher no disponible');
                }
            }).catch(error => {
                console.error('[InvoiceFromList] Error cargando módulos de factura:', error);
                alert('Error al cargar el sistema de factura. Por favor recarga la página.');
            });
        } else {
            console.error('[InvoiceFromList] Lazy loader no disponible');
            alert('Error: Sistema de factura no inicializado. Por favor recarga la página.');
        }
    });
};

window.verRecibosDelPedido = function(numeroPedido, pedidoId, prendasIndex) {
    esperarLazyLoader(() => {
        if (window.invoiceLazyLoader) {
            // El lazy loader se encargará de cargar los módulos necesarios
            window.invoiceLazyLoader.cargarModulosRecibos().then(() => {
                // Usar el InvoiceDataFetcher directamente
                if (window.invoiceDataFetcher) {
                    return window.invoiceDataFetcher.verRecibosDelPedido(numeroPedido, pedidoId, prendasIndex);
                } else {
                    throw new Error('InvoiceDataFetcher no disponible');
                }
            }).catch(error => {
                console.error('[InvoiceFromList] Error cargando módulos de recibos:', error);
                alert('Error al cargar el sistema de recibos. Por favor recarga la página.');
            });
        } else {
            console.error('[InvoiceFromList] Lazy loader no disponible');
            alert('Error: Sistema de recibos no inicializado. Por favor recarga la página.');
        }
    });
};

// Funciones de compatibilidad para modales
window.crearModalFacturaDesdeListaPedidos = function(datos) {
    esperarLazyLoader(() => {
        if (window.invoiceModalManager) {
            return window.invoiceModalManager.crearModalFactura(datos);
        } else {
            // Si no está disponible, cargarlo bajo demanda
            window.invoiceLazyLoader.cargarModulo('InvoiceModalManager').then(() => {
                if (window.invoiceModalManager) {
                    return window.invoiceModalManager.crearModalFactura(datos);
                }
            });
        }
    });
};

window.crearModalRecibosDesdeListaPedidos = function(datos, prendasIndex) {
    esperarLazyLoader(() => {
        if (window.receiptsModalManager) {
            return window.receiptsModalManager.crearModalRecibos(datos, prendasIndex);
        } else {
            // Si no está disponible, cargarlo bajo demanda
            window.invoiceLazyLoader.cargarModulo('ReceiptsModalManager').then(() => {
                if (window.receiptsModalManager) {
                    return window.receiptsModalManager.crearModalRecibos(datos, prendasIndex);
                }
            });
        }
    });
};

window.cerrarModalFactura = function() {
    if (window.invoiceModalManager) {
        return window.invoiceModalManager.cerrarModalFactura();
    }
};

window.cerrarModalRecibos = function() {
    if (window.receiptsModalManager) {
        return window.receiptsModalManager.cerrarModalRecibos();
    }
};

window.imprimirFacturaModal = function() {
    if (window.invoiceModalManager) {
        return window.invoiceModalManager.imprimirFacturaModal();
    }
};

// Funciones de compatibilidad para loading y notificaciones
window.mostrarCargando = function(mensaje) {
    esperarLazyLoader(() => {
        if (window.loadingManager) {
            return window.loadingManager.mostrarCargando(mensaje);
        } else {
            // Fallback simple si el loading manager no está disponible
            console.log('[InvoiceFromList] Cargando:', mensaje);
        }
    });
};

window.ocultarCargando = function() {
    esperarLazyLoader(() => {
        if (window.loadingManager) {
            return window.loadingManager.ocultarCargando();
        }
    });
};

window.mostrarErrorNotificacion = function(titulo, mensaje) {
    esperarLazyLoader(() => {
        if (window.notificationManager) {
            return window.notificationManager.mostrarError(titulo, mensaje);
        } else {
            // Fallback simple si el notification manager no está disponible
            alert(`${titulo}: ${mensaje}`);
        }
    });
};

// Funciones de compatibilidad para component loader
window.cargarReceiptManager = function(callback) {
    esperarLazyLoader(() => {
        if (window.componentLoader) {
            return window.componentLoader.cargarReceiptManager(callback);
        } else {
            // Si no está disponible, cargarlo bajo demanda
            window.invoiceLazyLoader.cargarModulo('ComponentLoader').then(() => {
                if (window.componentLoader) {
                    return window.componentLoader.cargarReceiptManager(callback);
                }
            });
        }
    });
};

window.cargarComponenteOrderDetailModal = function(contenedor, datos, prendasIndex) {
    esperarLazyLoader(() => {
        if (window.componentLoader) {
            return window.componentLoader.cargarComponenteOrderDetailModal(contenedor, datos, prendasIndex);
        } else {
            // Si no está disponible, cargarlo bajo demanda
            window.invoiceLazyLoader.cargarModulo('ComponentLoader').then(() => {
                if (window.componentLoader) {
                    return window.componentLoader.cargarComponenteOrderDetailModal(contenedor, datos, prendasIndex);
                }
            });
        }
    });
};

// Debugging functions
window.estadoModulosInvoiceFromList = function() {
    if (window.invoiceLazyLoader) {
        return window.invoiceLazyLoader.getEstadoModulos();
    } else {
        return { error: 'Lazy loader no disponible' };
    }
};

window.precargarTodosModulosInvoiceFromList = function() {
    if (window.invoiceLazyLoader) {
        return window.invoiceLazyLoader.precargarTodosLosModulos();
    } else {
        console.error('[InvoiceFromList] Lazy loader no disponible');
    }
};

console.log('[InvoiceFromList]  Sistema con lazy loading inicializado');
}
