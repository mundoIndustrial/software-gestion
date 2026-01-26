/**
 * Gestor de Vista de Factura desde Lista de Pedidos
 * Reutiliza la función generarHTMLFactura de invoice-preview-live.js
 */



/**
 * Abre la vista previa de factura para un pedido guardado
 * Obtiene los datos del servidor y los muestra
 */
window.verFacturaDelPedido = async function(numeroPedido, pedidoId) {

    
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

        console.log('[FACTURA-DEBUG] Datos completos del servidor:', datos);
        console.log('[FACTURA-DEBUG] Prendas recibidas:', datos.prendas);
        if (datos.prendas && datos.prendas[0]) {
            console.log('[FACTURA-DEBUG] Primera prenda:', datos.prendas[0]);
            console.log('[FACTURA-DEBUG] Variantes de primera prenda:', datos.prendas[0].variantes);
        }
        
        // Ocultar spinner
        ocultarCargando();
        
        // Usar el modal de VISUALIZACIÓN bonito con botones de PDF e imprimir (NO el de edición)
        if (typeof crearModalFacturaDesdeListaPedidos === 'function') {

            crearModalFacturaDesdeListaPedidos(datos);
        } else {

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
        `;
        document.head.appendChild(styleSheet);

    }
    
    // Usar la función existente de invoice-preview-live.js
    let htmlFactura;
    if (typeof generarHTMLFactura === 'function') {
        try {
            htmlFactura = generarHTMLFactura(datos);

            if (!htmlFactura || htmlFactura.trim().length === 0) {
                throw new Error('HTML vacío generado');
            }
        } catch (error) {

            htmlFactura = `
                <div style="padding: 30px; background: #fff3cd; border: 1px solid #ffc107; border-radius: 6px; color: #856404;">
                    <h3>Error al generar factura</h3>
                    <p>${error.message}</p>
                    <hr>
                    <div style="font-size: 12px; color: #666;">
                        <strong>Datos disponibles:</strong><br>
                        Pedido: ${datos.numero_pedido}<br>
                        Cliente: ${datos.cliente}<br>
                        Asesor: ${datos.asesora}
                    </div>
                </div>
            `;
        }
    } else {

        htmlFactura = `<div style="padding: 30px; background: #f3f4f6; border-radius: 6px;"><h3>Información del Pedido</h3><p><strong>Pedido #${datos.numero_pedido}</strong></p><p>Cliente: ${datos.cliente}</p><p>Asesor: ${datos.asesora}</p></div>`;
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

/**
 * Abre la vista de recibos dinámicos para un pedido
 */
window.verRecibosDelPedido = async function(numeroPedido, pedidoId, prendasIndex = null) {

    
    try {
        // Mostrar spinner de carga
        mostrarCargando('Cargando recibos...');
        
        // Obtener datos de recibos del servidor
        const response = await fetch(`/asesores/pedidos/${pedidoId}/recibos-datos`, {
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

        // ===== DEBUG: Rastrear estructura completa del backend =====
        console.group('[DEBUG] Datos recibidos del backend - /asesores/pedidos/{id}/recibos-datos');
        console.log('Estructura completa:', datos);
        console.log('Número de prendas:', datos.prendas ? datos.prendas.length : 0);
        if (datos.prendas && datos.prendas.length > 0) {
            datos.prendas.forEach((prenda, idx) => {
                console.group(`Prenda ${idx}: ${prenda.nombre}`);
                console.log('  - Campos disponibles:', Object.keys(prenda));
                console.log('  - procesos existe?', 'procesos' in prenda);
                console.log('  - procesos es array?', Array.isArray(prenda.procesos));
                console.log('  - procesos count:', (prenda.procesos || []).length);
                if (prenda.procesos && prenda.procesos.length > 0) {
                    console.log('  - Procesos:', prenda.procesos);
                    prenda.procesos.forEach((p, pIdx) => {
                        console.log(`    Proceso ${pIdx}:`, {
                            nombre_proceso: p.nombre_proceso,
                            tipo_proceso: p.tipo_proceso,
                            tallas: p.tallas,
                            ubicaciones: p.ubicaciones,
                            imagenes: p.imagenes,
                            observaciones: p.observaciones
                        });
                    });
                }
                console.groupEnd();
            });
        }
        console.groupEnd();
        // ===== FIN DEBUG =====
        
        // Ocultar spinner
        ocultarCargando();
        
        // Crear modal con los recibos
        crearModalRecibosDesdeListaPedidos(datos, prendasIndex);
        
    } catch (error) {

        ocultarCargando();
        
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
    
    // ===== DEBUG: Verificar datos al entrar a crearModal =====
    console.group('[crearModalRecibosDesdeListaPedidos] Datos recibidos en función');
    console.log('datos completo:', datos);
    console.log('prendas count:', datos.prendas ? datos.prendas.length : 0);
    if (datos.prendas && datos.prendas.length > 0) {
        console.log('Primera prenda estructura:', {
            nombre: datos.prendas[0].nombre,
            campos: Object.keys(datos.prendas[0]),
            procesos_existe: 'procesos' in datos.prendas[0],
            procesos_valor: datos.prendas[0].procesos,
            procesos_tipo: typeof datos.prendas[0].procesos
        });
    }
    console.groupEnd();
    // ===== FIN DEBUG =====
    // Crear overlay
    const overlay = document.createElement('div');
    overlay.id = 'modal-recibos-overlay';
    overlay.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 999998;
        padding: 1rem;
    `;
    
    // Contenedor del modal - SIN FONDO
    const modal = document.createElement('div');
    modal.id = 'receipt-modal-content';
    modal.style.cssText = `
        background: transparent;
        border-radius: 0;
        max-width: 95vw;
        width: auto;
        max-height: 90vh;
        overflow: visible;
        box-shadow: none;
        position: relative;
        padding: 0;
    `;
    
    // Placeholder para cargar el componente
    const componentContainer = document.createElement('div');
    componentContainer.id = 'order-detail-modal-container';
    componentContainer.style.cssText = 'padding: 0; position: relative; width: 100%; height: 100%;';
    modal.appendChild(componentContainer);
    
    overlay.appendChild(modal);
    document.body.appendChild(overlay);
    
    // Cerrar cuando se hace clic fuera
    overlay.addEventListener('click', (e) => {
        if (e.target === overlay) {
            overlay.remove();
        }
    });
    
    // Cerrar con ESC
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && overlay.parentElement) {
            overlay.remove();
        }
    });
    
    // Cargar el componente order-detail-modal
    cargarComponenteOrderDetailModal(componentContainer, datos, prendasIndex);
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
                <div id="order-asesora" class="order-asesora">ASESORA: <span id="receipt-asesora-value"></span></div>
                <div id="order-forma-pago" class="order-forma-pago">FORMA DE PAGO: <span id="receipt-forma-pago-value"></span></div>
                <div id="order-cliente" class="order-cliente">CLIENTE: <span id="receipt-cliente-value"></span></div>
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
        counter.style.cssText = 'font-weight: bold; font-size: 14px;';
        counter.innerHTML = 'Recibo <span id="receipt-number">1</span>/<span id="receipt-total">1</span>';
        arrowContainer.appendChild(counter);
    }
    
    // Configurar elementos
    setTimeout(() => {
        // Los campos de firma se mantienen visibles (ENCARGADO y PRENDAS ENTREGADAS)
        
        // ===== DEBUG: Verificar datos justo antes de ReceiptManager =====
        console.group('[cargarComponenteOrderDetailModal] Antes de crear ReceiptManager');
        console.log('datos parámetro:', datos);
        console.log('datos.prendas.length:', datos.prendas ? datos.prendas.length : 'UNDEFINED');
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


