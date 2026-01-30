/**
 * Gestor de Vista de Factura desde Lista de Pedidos
 * Reutiliza la función generarHTMLFactura de invoice-preview-live.js
 */



/**
 * Abre la vista previa de factura para un pedido guardado
 * Obtiene los datos del servidor y los muestra
 */
window.verFacturaDelPedido = async function(numeroPedido, pedidoId) {
    console.log('[INICIO-verFacturaDelPedido] ⚠️ FUNCIÓN LLAMADA', { numeroPedido, pedidoId });
    
    try {
        // Mostrar spinner de carga
        mostrarCargando('Cargando factura...');
        
        console.log('[ANTES-FETCH] Llamando a endpoint', { url: `/pedidos-public/${pedidoId}/factura-datos` });
        
        // Obtener datos del pedido desde el servidor
        const response = await fetch(`/pedidos-public/${pedidoId}/factura-datos`, {
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

        console.log('[FACTURA-DEBUG] Datos completos del servidor:', datos);
        console.log('[FACTURA-DEBUG] ¿Tiene prendas?', !!datos.prendas);
        console.log('[FACTURA-DEBUG] Tipo de prendas:', typeof datos.prendas);
        console.log('[FACTURA-DEBUG] ¿Es array?', Array.isArray(datos.prendas));
        console.log('[FACTURA-DEBUG] Prendas count:', datos.prendas ? datos.prendas.length : 'N/A');
        console.log('[FACTURA-DEBUG] Prendas recibidas:', datos.prendas);
        if (datos.prendas && datos.prendas[0]) {
            console.log('[FACTURA-DEBUG] Primera prenda:', datos.prendas[0]);
            console.log('[FACTURA-DEBUG] Variantes de primera prenda:', datos.prendas[0].variantes);
            console.log('[FACTURA-DEBUG] ⚠️ TELAS_ARRAY en primera prenda:', datos.prendas[0].telas_array);
            console.log('[FACTURA-DEBUG] ⚠️ TELAS_ARRAY length:', datos.prendas[0].telas_array ? datos.prendas[0].telas_array.length : 'NO EXISTE');
            console.log('[FACTURA-DEBUG] ⚠️ TELA simple en primera prenda:', datos.prendas[0].tela);
            console.log('[FACTURA-DEBUG] ⚠️ COLOR simple en primera prenda:', datos.prendas[0].color);
        }
        
    // Usar el modal de VISUALIZACIÓN bonito con botones de PDF e imprimir (NO el de edición)
        if (typeof crearModalFacturaDesdeListaPedidos === 'function') {
            console.log('[invoice-from-list.js] 📋 Usando crearModalFacturaDesdeListaPedidos');
            crearModalFacturaDesdeListaPedidos(datos);
        } else {
            console.warn('[invoice-from-list.js] ⚠️ crearModalFacturaDesdeListaPedidos NO EXISTE, usando fallback abrirModalEditarPedido');
            abrirModalEditarPedido(pedidoId, datos, 'ver');  // Fallback al modal simple
        }
        
    } catch (error) {

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

    
    // Agregar estilos y animaciones al documento si no existen
    if (!document.getElementById('factura-styles')) {
        const styleSheet = document.createElement('style');
        styleSheet.id = 'factura-styles';
        styleSheet.textContent = `
            @keyframes fadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
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
            @keyframes fadeOut {
                from { opacity: 1; }
                to { opacity: 0; }
            }
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
            
            /* Override de font-sizes para modal de factura */
            #modal-factura-contenido table,
            #modal-factura-contenido table td,
            #modal-factura-contenido table th,
            #modal-factura-contenido td,
            #modal-factura-contenido th {
                font-size: 11px !important;
            }
        `;
        document.head.appendChild(styleSheet);

    }
    
    // Usar la función existente de invoice-preview-live.js
    let htmlFactura;
    if (typeof generarHTMLFactura === 'function') {
        try {
            console.log('[GENERAR-FACTURA] Intentando generar HTML con datos:', {
                prendas_existe: !!datos.prendas,
                prendas_es_array: Array.isArray(datos.prendas),
                prendas_count: datos.prendas?.length || 0,
                datos_keys: Object.keys(datos)
            });
            
            htmlFactura = generarHTMLFactura(datos);

            console.log('[GENERAR-FACTURA] ✅ HTML generado exitosamente', {
                htmlFactura_length: htmlFactura?.length || 0,
                htmlFactura_vacio: !htmlFactura || htmlFactura.trim().length === 0
            });

            if (!htmlFactura || htmlFactura.trim().length === 0) {
                throw new Error('HTML vacío generado');
            }
        } catch (error) {
            console.error('[GENERAR-FACTURA] ❌ ERROR al generar HTML:', {
                error_mensaje: error.message,
                error_stack: error.stack,
                datos_prendas: datos.prendas,
                datos_keys: Object.keys(datos)
            });

            htmlFactura = `
                <div style="padding: 30px; background: #fee2e2; border: 1px solid #fca5a5; border-radius: 6px; color: #dc2626;">
                    <h3>❌ Error al generar factura</h3>
                    <p><strong>${error.message}</strong></p>
                    <hr>
                    <div style="font-size: 12px; color: #666; background: #f3f4f6; padding: 10px; border-radius: 4px; font-family: monospace; max-height: 200px; overflow-y: auto;">
                        <strong>Stack:</strong><br>${error.stack?.substring(0, 500) || 'No stack disponible'}
                    </div>
                    <hr>
                    <div style="font-size: 12px; color: #666;">
                        <strong>Información del pedido:</strong><br>
                        Pedido: ${datos.numero_pedido}<br>
                        Cliente: ${datos.cliente}<br>
                        Asesor: ${datos.asesora}<br>
                        Prendas recibidas: ${datos.prendas?.length || 0}
                    </div>
                </div>
            `;
        }
    } else {
        console.error('[GENERAR-FACTURA] ❌ Función generarHTMLFactura NO existe');
        htmlFactura = `<div style="padding: 30px; background: #f3f4f6; border-radius: 6px;"><h3>Información del Pedido</h3><p><strong>Pedido #${datos.numero_pedido}</strong></p><p>Cliente: ${datos.cliente}</p><p>Asesor: ${datos.asesora}</p><p><em style="color: #666;">Prendas: ${datos.prendas?.length || 0}</em></p></div>`;
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
        max-width: 1400px;
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
    titulo.textContent = ' Recibo del Pedido';
    titulo.style.cssText = 'margin: 0; font-size: 1.1rem; font-weight: 600;';
    header.appendChild(titulo);
    
    // Botones de acción
    const botonesAccion = document.createElement('div');
    botonesAccion.style.cssText = 'display: flex; gap: 8px; align-items: center;';
    
    // Botón Cerrar
    const btnCerrar = document.createElement('button');
    btnCerrar.id = 'close-receipt-btn';
    btnCerrar.setAttribute('id', 'close-receipt-btn');
    console.log('[invoice-from-list.js] 🔵 Creando botón cerrar con ID:', { id: btnCerrar.id, getAttribute: btnCerrar.getAttribute('id') });
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
    contenido.id = 'modal-factura-contenido';
    contenido.style.cssText = `
        padding: 30px;
        overflow-y: auto;
        max-height: calc(90vh - 100px);
    `;
    
    try {
        contenido.innerHTML = htmlFactura;

        
        // Verificar que el contenido se inyectó correctamente
        if (contenido.innerHTML.trim().length === 0) {

            contenido.innerHTML = '<p style="color: red;">Error: el contenido de la factura no se pudo renderizar</p>';
        }
    } catch (error) {

        contenido.innerHTML = `<p style="color: red;">Error: ${error.message}</p>`;
    }
    
    modal.appendChild(contenido);
    
    overlay.appendChild(modal);
    document.body.appendChild(overlay);
    
    // Log de verificación
    const btnVerify = document.getElementById('close-receipt-btn');
    console.log('[invoice-from-list.js] ✅ Modal agregado al DOM. Verificando botón:', { btnVerify, encontrado: !!btnVerify });
    
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
    // Asegurarse de ocultar el loading
    ocultarCargando();
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
        <h4 style="margin: 0 0 4px 0; font-weight: 600; font-size: 11px;">${titulo}</h4>
        <p style="margin: 0; font-size: 11px;">${mensaje}</p>
    `;
    
    document.body.appendChild(notif);
    
    setTimeout(() => {
        notif.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => notif.remove(), 300);
    }, 5000);
}

/**
 * Abre la vista de recibos dinámicos para un pedido
 */
window.verRecibosDelPedido = async function(numeroPedido, pedidoId, prendasIndex = null) {

    
    try {
        // Obtener datos de recibos del servidor
        const response = await fetch(`/pedidos-public/${pedidoId}/recibos-datos`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        console.log('════════════════════════════════════════════════════════════');
        console.log('[cargarRecibosDesdeLista] FETCH A /pedidos-public/' + pedidoId + '/recibos-datos');
        console.log('════════════════════════════════════════════════════════════');
        console.log('Response status:', response.status);
        console.log('Response ok:', response.ok);
        
        if (!response.ok) {
            throw new Error(`Error ${response.status}: ${response.statusText}`);
        }
        
        const datos = await response.json();

        console.log('📦 DATOS RECIBIDOS DEL BACKEND:');
        console.log('════════════════════════════════════════════════════════════');
        console.log('datos.cliente:', datos.data?.cliente || datos.cliente);
        console.log('datos.asesor:', datos.data?.asesor || datos.asesor);
        console.log('datos.asesora:', datos.data?.asesora || datos.asesora);
        console.log('datos.forma_de_pago:', datos.data?.forma_de_pago || datos.forma_de_pago);
        console.log('datos.numero_pedido:', datos.data?.numero_pedido || datos.numero_pedido);
        console.log('Estructura datos:', Object.keys(datos).slice(0, 10));
        if (datos.data) {
            console.log('Estructura datos.data:', Object.keys(datos.data).slice(0, 10));
        }
        console.log('════════════════════════════════════════════════════════════');
        
        // Crear modal con los recibos
        crearModalRecibosDesdeListaPedidos(datos, prendasIndex);
        
    } catch (error) {
        
        mostrarErrorNotificacion(
            'Error',
            'No se pudo cargar los recibos: ' + error.message
        );
    }
};

/**
 * Crea y muestra el modal con los recibos dinámicos
 * Usa el componente order-detail-modal.blade.php existente
 */
function crearModalRecibosDesdeListaPedidos(datos, prendasIndex = null) {
    
    // ===== DEBUG: Verificar estructura del response =====
    console.group('[crearModalRecibosDesdeListaPedidos] ANALIZANDO ESTRUCTURA DEL RESPONSE');
    console.log('¿Tiene propiedades directas?', Object.keys(datos).slice(0, 15));
    console.log('¿Tiene datos.data?', !!datos.data);
    console.log('¿Tiene datos.success?', !!datos.success);
    
    // Determinar dónde están los datos reales
    const datosReales = datos.data || datos;
    console.log('Usando datosReales:', {
        cliente: datosReales.cliente,
        asesor: datosReales.asesor,
        forma_de_pago: datosReales.forma_de_pago,
        numero_pedido: datosReales.numero_pedido,
        prendas_length: datosReales.prendas ? datosReales.prendas.length : 'undefined'
    });
    
    console.log('prendas count:', datosReales.prendas ? datosReales.prendas.length : 0);
    if (datosReales.prendas && datosReales.prendas.length > 0) {
        console.log('Primera prenda estructura:', {
            nombre: datosReales.prendas[0].nombre,
            campos: Object.keys(datosReales.prendas[0]),
            procesos_existe: 'procesos' in datosReales.prendas[0],
            procesos_valor: datosReales.prendas[0].procesos,
            procesos_tipo: typeof datosReales.prendas[0].procesos
        });
    }
    console.groupEnd();
    // ===== FIN DEBUG =====
    
    // Usar el modal de supervisor existente
    const modalWrapper = document.getElementById('order-detail-modal-wrapper');
    const overlay = document.getElementById('modal-overlay');
    
    if (!modalWrapper || !overlay) {
        console.error('[crearModalRecibosDesdeListaPedidos] No se encontró el modal de supervisor');
        return;
    }
    
    // Mostrar el modal de supervisor
    console.log('[crearModalRecibosDesdeListaPedidos] Mostrando modal wrapper...');
    console.log('[crearModalRecibosDesdeListaPedidos] Estado actual del overlay:', {
        display: overlay.style.display,
        zIndex: overlay.style.zIndex,
        opacity: overlay.style.opacity,
        visibility: overlay.style.visibility
    });
    
    overlay.style.display = 'block';
    overlay.style.zIndex = '9997';
    overlay.style.position = 'fixed';
    overlay.style.opacity = '1';
    overlay.style.visibility = 'visible';
    
    console.log('[crearModalRecibosDesdeListaPedidos] Estado actual del modal wrapper ANTES de mostrar:', {
        display: modalWrapper.style.display,
        zIndex: modalWrapper.style.zIndex,
        opacity: modalWrapper.style.opacity,
        visibility: modalWrapper.style.visibility,
        pointerEvents: modalWrapper.style.pointerEvents,
        innerHTML: modalWrapper.innerHTML.substring(0, 100) + '...'
    });
    
    modalWrapper.style.display = 'block';
    modalWrapper.style.zIndex = '9998';
    modalWrapper.style.position = 'fixed';
    modalWrapper.style.top = '50%';
    modalWrapper.style.left = '50%';
    modalWrapper.style.transform = 'translate(-50%, -50%)';
    modalWrapper.style.pointerEvents = 'auto';
    modalWrapper.style.opacity = '1';
    modalWrapper.style.visibility = 'visible';
    
    console.log('[crearModalRecibosDesdeListaPedidos] Estado del modal wrapper DESPUÉS de mostrar:', {
        display: modalWrapper.style.display,
        zIndex: modalWrapper.style.zIndex,
        opacity: modalWrapper.style.opacity,
        visibility: modalWrapper.style.visibility,
        pointerEvents: modalWrapper.style.pointerEvents
    });
    
    // Obtener el contenedor del modal
    const modalContainer = modalWrapper.querySelector('.order-detail-modal-container');
    console.log('[crearModalRecibosDesdeListaPedidos] Modal container encontrado:', !!modalContainer);
    
    if (modalContainer) {
        console.log('[crearModalRecibosDesdeListaPedidos] Contenido del modal container:', modalContainer.innerHTML.substring(0, 200) + '...');
        console.log('[crearModalRecibosDesdeListaPedidos] Hijos del modal container:', modalContainer.children.length);
        
        // Verificar si hay un card
        const card = modalContainer.querySelector('.order-detail-card');
        console.log('[crearModalRecibosDesdeListaPedidos] Card encontrado:', !!card);
        
        if (card) {
            console.log('[crearModalRecibosDesdeListaPedidos] Estado del card:', {
                display: card.style.display,
                opacity: card.style.opacity,
                visibility: card.style.visibility,
                height: card.style.height
            });
        }
    }
    if (!modalContainer) {
        console.error('[crearModalRecibosDesdeListaPedidos] No se encontró el contenedor del modal');
        return;
    }
    
    // Asegurar que el modal tenga los elementos necesarios para ReceiptManager
    const receiptNumber = modalContainer.querySelector('#receipt-number');
    const receiptTotal = modalContainer.querySelector('#receipt-total');
    
    if (!receiptNumber || !receiptTotal) {
        console.error('[crearModalRecibosDesdeListaPedidos] No se encontraron elementos necesarios para ReceiptManager');
        return;
    }
    
    // Cargar ReceiptManager con los datos correctos
    setTimeout(() => {
        // ===== DEBUG: Verificar datos justo antes de ReceiptManager =====
        console.group('[crearModalRecibosDesdeListaPedidos] ANTES DE CREAR ReceiptManager');
        console.log('🔍 DATOS PARÁMETRO RECIBIDOS:');
        console.log('  cliente:', datosReales.cliente);
        console.log('  asesor:', datosReales.asesor);
        console.log('  asesora:', datosReales.asesora);
        console.log('  forma_de_pago:', datosReales.forma_de_pago);
        console.log('  numero_pedido:', datosReales.numero_pedido);
        console.log('  prendas.length:', datosReales.prendas ? datosReales.prendas.length : 'UNDEFINED');
        console.groupEnd();
        // ===== FIN DEBUG =====
        
        // Cargar ReceiptManager
        if (typeof ReceiptManager === 'undefined') {
            cargarReceiptManager(() => {
                console.debug('[crearModalRecibosDesdeListaPedidos] Creando ReceiptManager con datos:', datosReales);
                window.receiptManager = new ReceiptManager(datosReales, prendasIndex);
                
                // Inicializar botón X para insumos
                if (typeof inicializarBotonCerrarInsumos === 'function') {
                    setTimeout(() => {
                        inicializarBotonCerrarInsumos();
                    }, 200);
                }
            });
        } else {
            console.debug('[crearModalRecibosDesdeListaPedidos] ReceiptManager ya cargado, creando instancia');
            window.receiptManager = new ReceiptManager(datosReales, prendasIndex);
            
            // Inicializar botón X para insumos
            if (typeof inicializarBotonCerrarInsumos === 'function') {
                setTimeout(() => {
                    inicializarBotonCerrarInsumos();
                }, 200);
            }
        }
    }, 100);
}

/**
 * Carga el componente order-detail-modal y lo adapta para recibos
 */
/**
 * Carga el componente order-detail-modal e inyecta los datos
 */
function cargarComponenteOrderDetailModal(contenedor, datos, prendasIndex = null) {

    
    // Usar directamente el HTML que funciona (sin fetch, para evitar problemas con Blade)
    contenedor.innerHTML = `
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
                    <i class="fas fa-receipt"></i>
                </button>
                <button id="btn-galeria" type="button" title="Ver galería" onclick="toggleGaleria()" style="width: 56px; height: 56px; border-radius: 50%; background: white; border: 2px solid #ddd; color: #333; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 24px; transition: all 0.3s ease; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);">
                    <i class="fas fa-images"></i>
                </button>
            </div>
        </div>
    `;
    
    // Crear counter de recibos si no existe
    const arrowContainer = contenedor.querySelector('.arrow-container');
    if (arrowContainer && !contenedor.querySelector('#receipt-counter')) {
        const counter = document.createElement('span');
        counter.id = 'receipt-counter';
        counter.style.cssText = 'font-weight: bold; font-size: 11px;';
        counter.innerHTML = 'Recibo <span id="receipt-number">1</span>/<span id="receipt-total">1</span>';
        arrowContainer.appendChild(counter);
    }
    
    // Configurar elementos
    setTimeout(() => {
        // Los campos de firma se mantienen visibles (ENCARGADO y PRENDAS ENTREGADAS)
        
        // ===== DEBUG: Verificar datos justo antes de ReceiptManager =====
        console.group('[cargarComponenteOrderDetailModal] ANTES DE CREAR ReceiptManager');
        console.log('🔍 DATOS PARÁMETRO RECIBIDOS:');
        console.log('  cliente:', datos.cliente);
        console.log('  asesor:', datos.asesor);
        console.log('  asesora:', datos.asesora);
        console.log('  forma_de_pago:', datos.forma_de_pago);
        console.log('  numero_pedido:', datos.numero_pedido);
        console.log('  prendas.length:', datos.prendas ? datos.prendas.length : 'UNDEFINED');
        if (datos.prendas && datos.prendas.length > 0) {
            console.log('Primera prenda en datos:', {
                nombre: datos.prendas[0].nombre,
                procesos_existe: 'procesos' in datos.prendas[0],
                procesos_valor: datos.prendas[0].procesos,
                procesos_length: datos.prendas[0].procesos ? datos.prendas[0].procesos.length : 'N/A'
            });
        }
        console.groupEnd();
        // ===== FIN DEBUG =====
        
        // Cargar ReceiptManager
        if (typeof ReceiptManager === 'undefined') {

            cargarReceiptManager(() => {
                console.debug('[cargarComponenteOrderDetailModal] Creando ReceiptManager con datos:', datos);
                window.receiptManager = new ReceiptManager(datos, prendasIndex);
            });
        } else {
            console.debug('[cargarComponenteOrderDetailModal] ReceiptManager ya cargado, creando instancia');
            window.receiptManager = new ReceiptManager(datos, prendasIndex);
        }
    }, 100);
}

/**
 * Carga el script de ReceiptManager
 */
function cargarReceiptManager(callback) {
    const script = document.createElement('script');
    script.src = '/js/asesores/receipt-manager.js';
    script.onload = callback;
    script.onerror = () => {

        mostrarErrorNotificacion('Error', 'No se pudo cargar el gestor de recibos');
    };
    document.head.appendChild(script);
}

/**
 * Cierra el modal de recibos
 */
function cerrarModalRecibos() {
    const overlay = document.getElementById('modal-recibos-overlay');
    if (overlay) {
        overlay.remove();
    }
}

/**
 * Registra y loguea todos los font-sizes del modal de lista de pedidos
 */
function registrarFontSizesFacturaListaPedidos(contenedorElement) {
    // Función deshabilitada - logging removido
}

// Exportar función a nivel global para acceso desde otros contextos
window.registrarFontSizesFacturaListaPedidos = registrarFontSizesFacturaListaPedidos;



