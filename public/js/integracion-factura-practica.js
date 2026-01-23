/**
 * INTEGRACIÓN PRÁCTICA: Factura en el Modal de Pedidos
 * 
 * Este archivo muestra cómo integrar la factura en tu sistema existente
 * de modales de órdenes/pedidos
 */

// ========================================
// 1. AGREGAR BOTÓN EN EL MODAL
// ========================================

/**
 * En order-detail-modal-manager.js, función renderOrderDetail()
 * Agrega esta sección después de renderizar los detalles:
 */

function renderOrderDetail_ConFactura(orden) {
    // ... código existente ...
    
    //  NUEVO: Agregar botón de factura
    const facturaBtnContainer = document.createElement('div');
    facturaBtnContainer.style.cssText = `
        margin-top: 20px;
        padding-top: 20px;
        border-top: 1px solid #ddd;
        display: flex;
        gap: 10px;
    `;
    
    facturaBtnContainer.innerHTML = `
        <button onclick="abrirFacturaEnVentana(${orden.numero_pedido})" 
                style="flex: 1; padding: 10px; background: #2c3e50; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600;">
             Ver Factura Completa
        </button>
        <button onclick="abrirFacturaEnIframe(${orden.numero_pedido})" 
                style="flex: 1; padding: 10px; background: #3498db; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600;">
            🔲 Factura en Modal
        </button>
    `;
    
    const modalContent = document.querySelector('.modal-content');
    if (modalContent) {
        modalContent.appendChild(facturaBtnContainer);
    }
    

}


// ========================================
// 2. AGREGAR EN MENÚ DE ACCIONES
// ========================================

/**
 * En el archivo HTML donde se genera la tabla de órdenes,
 * busca el action-menu y agrega esta opción:
 */

// Opción HTML/Blade:
/*
<a href="javascript:abrirFacturaEnVentana({{ $orden->numero_pedido }})" 
   class="action-menu-item">
    <i class="fas fa-file-invoice-dollar"></i>
    <span>Ver Factura</span>
</a>
*/

/**
 * Opción JavaScript (agregar en pedidos-detail-modal.js):
 */
window.agregarOpcionFactura = function() {
    // Buscar todos los menús de acciones
    const menus = document.querySelectorAll('.action-menu');
    
    menus.forEach(menu => {
        // Verificar que no exista ya la opción
        if (!menu.querySelector('[data-action="factura"]')) {
            // Crear el elemento de factura
            const facturaItem = document.createElement('a');
            facturaItem.href = '#';
            facturaItem.className = 'action-menu-item';
            facturaItem.setAttribute('data-action', 'factura');
            facturaItem.innerHTML = `
                <i class="fas fa-file-invoice-dollar"></i>
                <span>Ver Factura</span>
            `;
            
            // Agregar click listener
            facturaItem.addEventListener('click', function(e) {
                e.preventDefault();
                const ordenId = menu.getAttribute('data-orden-id');
                if (ordenId) {
                    abrirFacturaEnVentana(ordenId);
                }
            });
            
            // Insertar en el menú
            menu.appendChild(facturaItem);
        }
    });
    

};


// ========================================
// 3. ESCUCHAR EVENTO DE APERTURA DE MODAL
// ========================================

/**
 * Agregar listener para cuando se abre el modal de detalle
 * (En cualquier archivo JS que se cargue en la página)
 */
window.addEventListener('load', function() {
    // Cuando se abre el modal de orden
    document.addEventListener('load-order-detail', function(event) {
        const orden = event.detail;
        
        // Agregar botones de factura
        setTimeout(() => {
            const container = document.querySelector('#order-detail-modal-wrapper');
            if (container) {
                // Agregar botón flotante de factura
                const floatingBtn = document.createElement('button');
                floatingBtn.innerHTML = ' Factura';
                floatingBtn.style.cssText = `
                    position: absolute;
                    bottom: 20px;
                    right: 20px;
                    padding: 10px 20px;
                    background: #2c3e50;
                    color: white;
                    border: none;
                    border-radius: 24px;
                    cursor: pointer;
                    font-weight: 600;
                    z-index: 10001;
                    box-shadow: 0 2px 10px rgba(0,0,0,0.2);
                    transition: all 0.3s;
                `;
                
                floatingBtn.onmouseover = function() {
                    this.style.background = '#1a252f';
                    this.style.transform = 'translateY(-2px)';
                };
                floatingBtn.onmouseout = function() {
                    this.style.background = '#2c3e50';
                    this.style.transform = 'translateY(0)';
                };
                
                floatingBtn.onclick = function() {
                    abrirFacturaEnVentana(orden.numero_pedido);
                };
                
                container.style.position = 'relative';
                container.appendChild(floatingBtn);
                

            }
        }, 100);
    }, { once: true });
});


// ========================================
// 4. SHORTCUT DE TECLADO
// ========================================

/**
 * Agregar atajo de teclado para abrir factura rápidamente
 * Presiona Ctrl+F para abrir la factura del pedido actual
 */
window.addEventListener('keydown', function(e) {
    // Ctrl+F (Cmd+F en Mac)
    if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
        // Verificar que hay un modal abierto con un pedido
        const modalContent = document.querySelector('#order-detail-modal-wrapper');
        if (modalContent) {
            const pedidoText = modalContent.textContent;
            const match = pedidoText.match(/#(\d+)/);
            
            if (match) {
                e.preventDefault();
                const numeroPedido = match[1];

                abrirFacturaEnVentana(numeroPedido);
            }
        }
    }
});


// ========================================
// 5. CONTEXTO GLOBAL
// ========================================

/**
 * Crear un contexto global para acceder a la factura desde cualquier lugar
 */
window.Factura = {
    /**
     * Abre la factura del pedido actual
     */
    abrirActual: function() {
        const numeroPedido = this.getNumeroPedidoActual();
        if (numeroPedido) {
            abrirFacturaEnVentana(numeroPedido);
        } else {

        }
    },
    
    /**
     * Abre en modal
     */
    abrirEnModal: function() {
        const numeroPedido = this.getNumeroPedidoActual();
        if (numeroPedido) {
            abrirFacturaEnIframe(numeroPedido);
        }
    },
    
    /**
     * Descarga la factura
     */
    descargar: function() {
        const numeroPedido = this.getNumeroPedidoActual();
        if (numeroPedido) {
            descargarFactura(numeroPedido);
        }
    },
    
    /**
     * Obtiene el número de pedido actual del modal
     */
    getNumeroPedidoActual: function() {
        // Buscar en el titulo del modal
        const titulo = document.querySelector('.pedido-number');
        if (titulo) {
            const match = titulo.textContent.match(/\d+/);
            if (match) return match[0];
        }
        
        // Buscar en el contenido del modal
        const modalContent = document.querySelector('#order-detail-modal-wrapper');
        if (modalContent) {
            const match = modalContent.textContent.match(/#(\d+)/);
            if (match) return match[1];
        }
        
        return null;
    }
};

// Uso: window.Factura.abrirActual()


// ========================================
// 6. CARGAR SCRIPTS NECESARIOS
// ========================================

/**
 * Asegurarse de que los scripts necesarios estén cargados
 */
window.cargarScriptFactura = function() {
    // Verificar si invoice-modal-integration.js está cargado
    if (typeof window.abrirFacturaEnVentana === 'undefined') {
        const script = document.createElement('script');
        script.src = '/js/invoice-modal-integration.js?v=' + Date.now();
        script.onload = function() {

        };
        script.onerror = function() {

        };
        document.head.appendChild(script);
    }
};

// Cargar al iniciar
document.addEventListener('DOMContentLoaded', window.cargarScriptFactura);


// ========================================
// 7. NOTIFICACIÓN DE FACTURA GENERADA
// ========================================

/**
 * Mostrar notificación cuando se genera una factura
 */
window.notificarFactura = function(numeroPedido) {
    // Crear notificación
    const notif = document.createElement('div');
    notif.style.cssText = `
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: #27ae60;
        color: white;
        padding: 15px 20px;
        border-radius: 4px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        z-index: 10002;
        animation: slideIn 0.3s ease;
    `;
    notif.innerHTML = `
         Factura MI-PEDIDO-2026-${String(numeroPedido).padStart(4, '0')} generada
    `;
    
    document.body.appendChild(notif);
    
    // Remover después de 3 segundos
    setTimeout(() => {
        notif.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => notif.remove(), 300);
    }, 3000);
};

// Agregar keyframes si no existen
if (!document.querySelector('#factura-animations')) {
    const style = document.createElement('style');
    style.id = 'factura-animations';
    style.textContent = `
        @keyframes slideIn {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(400px);
                opacity: 0;
            }
        }
    `;
    document.head.appendChild(style);
}


// ========================================
// 8. INFORMACIÓN EN CONSOLA
// ========================================
2. Abrir factura en modal:
   abrirFacturaEnIframe(45703)

3. Descargar factura:
   descargarFactura(45703)

4. Usar el contexto global:
   window.Factura.abrirActual()
   window.Factura.abrirEnModal()
   window.Factura.descargar()

5. Ver número de pedido actual:
   window.Factura.getNumeroPedidoActual()

 Para más información, ver INTEGRACION_FACTURA_MODAL.md
`);

