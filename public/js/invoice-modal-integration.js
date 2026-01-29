/**
 * Invoice Modal Integration
 * Integración del componente de factura con los modales existentes
 * 
 * Uso:
 * - Llama a window.abrirFacturaEnModal(numeroPedido)
 * - Llama a window.abrirFacturaEnVentana(numeroPedido)
 * - Llama a window.descargarFactura(numeroPedido)
 */



/**
 * Abre la factura en una nueva ventana/pestaña
 * @param {number} numeroPedido - Número del pedido
 */
window.abrirFacturaEnVentana = function(numeroPedido) {

    const url = `/facturas/${numeroPedido}/preview`;
    window.open(url, `factura-${numeroPedido}`, 'width=1000,height=800,scrollbars=yes');
};

/**
 * Abre la factura en el modal existente
 * @param {number} numeroPedido - Número del pedido
 */
window.abrirFacturaEnModal = async function(numeroPedido) {

    
    try {
        // Obtener el HTML de la factura
        const response = await fetch(`/facturas/${numeroPedido}`);
        
        if (!response.ok) {
            throw new Error('Error al cargar la factura: ' + response.status);
        }
        
        // Para esta opción, es mejor usar un iframe
        abrirFacturaEnIframe(numeroPedido);
        
    } catch (error) {

        alert('Error al cargar la factura. Intenta nuevamente.');
    }
};

/**
 * Abre la factura en un iframe dentro de un modal
 * @param {number} numeroPedido - Número del pedido
 */
window.abrirFacturaEnIframe = function(numeroPedido) {

    
    // Crear modal si no existe
    let invoiceModal = document.getElementById('invoice-modal-wrapper');
    
    if (!invoiceModal) {
        // Crear estructura del modal
        invoiceModal = document.createElement('div');
        invoiceModal.id = 'invoice-modal-wrapper';
        invoiceModal.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 10000;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        `;
        
        invoiceModal.innerHTML = `
            <div style="background: white; border-radius: 8px; width: 100%; max-width: 1000px; height: 90vh; display: flex; flex-direction: column; box-shadow: 0 5px 40px rgba(0,0,0,0.3);">
                <!-- Header -->
                <div style="padding: 15px 20px; border-bottom: 1px solid #ddd; display: flex; justify-content: space-between; align-items: center;">
                    <h3 style="margin: 0; color: #2c3e50;"> Factura</h3>
                    <button onclick="document.getElementById('invoice-modal-wrapper').remove();" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #999;">×</button>
                </div>
                
                <!-- Content -->
                <div id="invoice-iframe-container" style="flex: 1; overflow: auto;">
                    <iframe id="invoice-frame" src="/facturas/${numeroPedido}/preview" style="width: 100%; height: 100%; border: none;"></iframe>
                </div>
                
                <!-- Footer -->
                <div style="padding: 15px 20px; border-top: 1px solid #ddd; display: flex; gap: 10px; justify-content: flex-end;">
                    <button onclick="document.getElementById('invoice-modal-wrapper').remove();" style="padding: 8px 16px; background: #ddd; border: none; border-radius: 4px; cursor: pointer;">Cerrar</button>
                    <button onclick="document.getElementById('invoice-frame').contentWindow.print();" style="padding: 8px 16px; background: #2c3e50; color: white; border: none; border-radius: 4px; cursor: pointer;"> Imprimir</button>
                </div>
            </div>
        `;
        
        document.body.appendChild(invoiceModal);
        
        // Cerrar al hacer click en el fondo
        invoiceModal.addEventListener('click', function(e) {
            if (e.target === invoiceModal) {
                invoiceModal.remove();
            }
        });
    }
};

/**
 * Descarga la factura como PDF
 * @param {number} numeroPedido - Número del pedido
 */
window.descargarFactura = function(numeroPedido) {

    
    // Redirigir a la ruta de descarga
    window.location.href = `/facturas/${numeroPedido}/download`;
};

/**
 * Integración en el menú de acciones de la tabla
 * Agregar opciones en el menú contextual de la fila
 */
document.addEventListener('DOMContentLoaded', function() {

    
    // Cuando se hace click en el botón de acciones
    document.addEventListener('click', function(e) {
        // Si hace click en una opción de "Ver Factura" (si existe)
        if (e.target.closest('[data-action="factura"]')) {
            e.preventDefault();
            const ordenId = e.target.closest('[data-orden-id]')?.dataset.ordenId 
                         || e.target.closest('[data-action="factura"]')?.dataset.ordenId;
            
            if (ordenId) {
                // Abre en ventana (puedes cambiar a modal según prefieras)
                abrirFacturaEnVentana(ordenId);
            }
        }
    });
});

// ========================================
// Exportar para uso externo
// ========================================
window.InvoiceManager = {
    abrirEnVentana: window.abrirFacturaEnVentana,
    abrirEnModal: window.abrirFacturaEnModal,
    abrirEnIframe: window.abrirFacturaEnIframe,
    descargar: window.descargarFactura
};


