/**
 * Gestor de Vista de Factura desde Lista de Pedidos
 * Reutiliza la función generarHTMLFactura de invoice-preview-live.js
 */

console.log('📄 [INVOICE LIST] Cargando invoice-from-list.js');

/**
 * Abre la vista previa de factura para un pedido guardado
 * Obtiene los datos del servidor y los muestra
 */
window.verFacturaDelPedido = async function(numeroPedido, pedidoId) {
    console.log('📄 [FACTURA] Abriendo factura para pedido:', numeroPedido);
    
    try {
        // Mostrar spinner de carga
        mostrarCargando('Cargando factura...');
        
        // Obtener datos del pedido desde el servidor
        const response = await fetch(`/asesores/pedidos/${pedidoId}/factura-datos`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        if (!response.ok) {
            throw new Error(`Error ${response.status}: ${response.statusText}`);
        }
        
        const datos = await response.json();
        console.log('✅ [FACTURA] Datos del pedido obtenidos:', datos);
        
        // Ocultar spinner
        ocultarCargando();
        
        // Crear modal con la factura
        crearModalFacturaDesdeListaPedidos(datos);
        
    } catch (error) {
        console.error('❌ [FACTURA] Error cargando factura:', error);
        ocultarCargando();
        
        mostrarErrorNotificacion(
            'Error',
            'No se pudo cargar la factura: ' + error.message
        );
    }
};

/**
 * Crea y muestra el modal con la factura
 */
function crearModalFacturaDesdeListaPedidos(datos) {
    console.log('🎨 [FACTURA] Creando modal de factura');
    
    // Agregar estilos de impresión al documento si no existen
    if (!document.getElementById('print-styles-factura')) {
        const styleSheet = document.createElement('style');
        styleSheet.id = 'print-styles-factura';
        styleSheet.textContent = `
            @media print {
                /* Ocultar TODA la página */
                body > * {
                    display: none !important;
                }
                
                /* Mostrar SOLO el modal de factura */
                body > #modal-factura-overlay {
                    display: flex !important;
                    position: static !important;
                }
                
                body {
                    margin: 0 !important;
                    padding: 0 !important;
                    background: white !important;
                }
                
                /* Estilos del overlay */
                #modal-factura-overlay {
                    position: static !important;
                    top: 0 !important;
                    left: 0 !important;
                    width: 100% !important;
                    height: auto !important;
                    background: white !important;
                    z-index: auto !important;
                    padding: 0 !important;
                    margin: 0 !important;
                    border: none !important;
                    box-shadow: none !important;
                    backdrop-filter: none !important;
                    animation: none !important;
                }
                
                /* Estilos del modal */
                #modal-factura {
                    position: static !important;
                    width: 100% !important;
                    max-width: 100% !important;
                    max-height: none !important;
                    box-shadow: none !important;
                    border-radius: 0 !important;
                    border: none !important;
                    padding: 20px !important;
                    margin: 0 !important;
                    overflow: visible !important;
                    animation: none !important;
                }
                
                /* Mostrar header pero ocultar botones */
                #modal-factura > div:first-child {
                    display: none !important;
                }
                
                /* Mostrar contenido de la factura */
                #modal-factura > div {
                    display: block !important;
                }
                
                /* Fondo transparente */
                html {
                    background: white !important;
                }
                
                /* Páginas */
                @page {
                    margin: 0.5cm;
                }
            }
        `;
        document.head.appendChild(styleSheet);
        console.log('✅ [FACTURA] Estilos de impresión agregados');
    }
    
    // Usar la función existente de invoice-preview-live.js
    let htmlFactura;
    if (typeof generarHTMLFactura === 'function') {
        htmlFactura = generarHTMLFactura(datos);
        console.log('✅ [FACTURA] Usando generarHTMLFactura de invoice-preview-live.js');
    } else {
        console.warn('⚠️  [FACTURA] generarHTMLFactura no encontrada, usando fallback simple');
        htmlFactura = `<div style="padding: 20px;"><p>Pedido #${datos.numero_pedido}</p><p>Cliente: ${datos.cliente}</p></div>`;
    }
    
    // Crear overlay
    const overlay = document.createElement('div');
    overlay.id = 'modal-factura-overlay';
    overlay.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.7);
        z-index: 9999;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
        animation: fadeIn 0.3s ease;
    `;
    
    // Crear modal
    const modal = document.createElement('div');
    modal.id = 'modal-factura';
    modal.style.cssText = `
        position: relative;
        background: white;
        border-radius: 12px;
        width: 100%;
        max-width: 900px;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
        animation: slideUp 0.3s ease;
    `;
    
    // Header con botones
    const header = document.createElement('div');
    header.style.cssText = `
        position: sticky;
        top: 0;
        background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%);
        color: white;
        padding: 12px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 10px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        z-index: 10000;
    `;
    
    const titulo = document.createElement('h2');
    titulo.textContent = '📄 Recibo del Pedido';
    titulo.style.cssText = 'margin: 0; font-size: 1.1rem; font-weight: 600;';
    header.appendChild(titulo);
    
    // Botones de acción
    const botonesAccion = document.createElement('div');
    botonesAccion.style.cssText = 'display: flex; gap: 8px; align-items: center;';
    
    // Botón Imprimir (solo icono)
    const btnImprimir = document.createElement('button');
    btnImprimir.innerHTML = '<i class="fas fa-print"></i>';
    btnImprimir.title = 'Imprimir';
    btnImprimir.style.cssText = `
        background: #10b981;
        color: white;
        border: none;
        padding: 8px 10px;
        border-radius: 6px;
        cursor: pointer;
        font-size: 16px;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
    `;
    btnImprimir.onmouseover = () => {
        btnImprimir.style.background = '#059669';
        btnImprimir.style.transform = 'scale(1.1)';
    };
    btnImprimir.onmouseout = () => {
        btnImprimir.style.background = '#10b981';
        btnImprimir.style.transform = 'scale(1)';
    };
    btnImprimir.onclick = () => window.print();
    botonesAccion.appendChild(btnImprimir);
    
    // Botón Descargar PDF (solo icono)
    const btnPDF = document.createElement('button');
    btnPDF.innerHTML = '<i class="fas fa-file-pdf"></i>';
    btnPDF.title = 'Descargar PDF';
    btnPDF.style.cssText = `
        background: #f59e0b;
        color: white;
        border: none;
        padding: 8px 10px;
        border-radius: 6px;
        cursor: pointer;
        font-size: 16px;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
    `;
    btnPDF.onmouseover = () => {
        btnPDF.style.background = '#d97706';
        btnPDF.style.transform = 'scale(1.1)';
    };
    btnPDF.onmouseout = () => {
        btnPDF.style.background = '#f59e0b';
        btnPDF.style.transform = 'scale(1)';
    };
    btnPDF.onclick = () => {
        alert('Función de descarga PDF en desarrollo');
    };
    botonesAccion.appendChild(btnPDF);
    
    // Botón Cerrar
    const btnCerrar = document.createElement('button');
    btnCerrar.innerHTML = '<span class="material-symbols-rounded" style="font-size: 20px;">close</span>';
    btnCerrar.style.cssText = `
        background: #ef4444;
        color: white;
        border: none;
        padding: 8px 12px;
        border-radius: 6px;
        cursor: pointer;
        font-size: 1rem;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
    `;
    btnCerrar.onmouseover = () => {
        btnCerrar.style.background = '#dc2626';
        btnCerrar.style.transform = 'scale(1.1)';
    };
    btnCerrar.onmouseout = () => {
        btnCerrar.style.background = '#ef4444';
        btnCerrar.style.transform = 'scale(1)';
    };
    btnCerrar.onclick = () => cerrarModalFactura();
    botonesAccion.appendChild(btnCerrar);
    
    header.appendChild(botonesAccion);
    modal.appendChild(header);
    
    // Contenido de la factura
    const contenido = document.createElement('div');
    contenido.style.cssText = `
        padding: 30px;
    `;
    contenido.innerHTML = htmlFactura;
    modal.appendChild(contenido);
    
    overlay.appendChild(modal);
    document.body.appendChild(overlay);
    
    // Cerrar con ESC
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            cerrarModalFactura();
        }
    });
    
    // Cerrar al hacer clic fuera
    overlay.onclick = (e) => {
        if (e.target === overlay) {
            cerrarModalFactura();
        }
    };
    
    console.log('✅ [FACTURA] Modal creado exitosamente');
}

/**
 * Cierra el modal de factura
 */
function cerrarModalFactura() {
    const overlay = document.getElementById('modal-factura-overlay');
    if (overlay) {
        overlay.style.animation = 'fadeOut 0.3s ease';
        setTimeout(() => {
            overlay.remove();
        }, 300);
    }
}

/**
 * Muestra un spinner de carga
 */
function mostrarCargando(mensaje = 'Cargando...') {
    const spinner = document.createElement('div');
    spinner.id = 'factura-spinner';
    spinner.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9998;
        flex-direction: column;
        gap: 20px;
    `;
    
    const spinner_inner = document.createElement('div');
    spinner_inner.style.cssText = `
        border: 4px solid rgba(255, 255, 255, 0.2);
        border-top: 4px solid white;
        border-radius: 50%;
        width: 50px;
        height: 50px;
        animation: spin 1s linear infinite;
    `;
    
    const texto = document.createElement('p');
    texto.textContent = mensaje;
    texto.style.cssText = `
        color: white;
        font-size: 16px;
        font-weight: 600;
        margin: 0;
    `;
    
    spinner.appendChild(spinner_inner);
    spinner.appendChild(texto);
    
    const style = document.createElement('style');
    style.textContent = `
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes fadeOut {
            from { opacity: 1; }
            to { opacity: 0; }
        }
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    `;
    
    document.head.appendChild(style);
    document.body.appendChild(spinner);
}

/**
 * Oculta el spinner de carga
 */
function ocultarCargando() {
    const spinner = document.getElementById('factura-spinner');
    if (spinner) {
        spinner.remove();
    }
}

/**
 * Muestra notificación de error
 */
function mostrarErrorNotificacion(titulo, mensaje) {
    const notif = document.createElement('div');
    notif.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: #fee2e2;
        border-left: 4px solid #dc2626;
        color: #991b1b;
        padding: 16px 20px;
        border-radius: 6px;
        box-shadow: 0 4px 12px rgba(220, 38, 38, 0.2);
        z-index: 10001;
        max-width: 400px;
        animation: slideIn 0.3s ease;
    `;
    
    notif.innerHTML = `
        <h4 style="margin: 0 0 4px 0; font-weight: 600; font-size: 14px;">${titulo}</h4>
        <p style="margin: 0; font-size: 13px;">${mensaje}</p>
    `;
    
    document.body.appendChild(notif);
    
    setTimeout(() => {
        notif.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => notif.remove(), 300);
    }, 5000);
}

console.log('✅ [INVOICE LIST] invoice-from-list.js cargado correctamente');
