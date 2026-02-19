/**
 * Gestor de Modal de Factura desde Lista
 * Maneja la creación y gestión del modal para visualizar facturas
 */

class InvoiceModalManager {
    constructor() {
        this.init();
    }

    init() {
        // Hacer métodos disponibles globalmente para compatibilidad
        window.crearModalFacturaDesdeListaPedidos = this.crearModalFactura.bind(this);
        window.cerrarModalFactura = this.cerrarModalFactura.bind(this);
        window.imprimirFacturaModal = this.imprimirFacturaModal.bind(this);
    }

    /**
     * Crea y muestra el modal con la factura
     */
    crearModalFactura(datos) {
        console.log('[InvoiceModalManager] INICIO crearModalFactura', {pedido_id: datos.id});
        
        // IMPORTANTE: Remover TODOS los modales anteriores completamente
        // Usar querySelectorAll para eliminar posibles duplicados
        const modalesAnteriores = document.querySelectorAll('#modal-factura-overlay');
        console.log('[InvoiceModalManager] Modales encontrados para eliminar:', modalesAnteriores.length);
        
        modalesAnteriores.forEach((modal, index) => {
            console.log('[InvoiceModalManager] Eliminando modal anterior', {index, id: modal.id});
            modal.remove();
        });
        
        console.log('[InvoiceModalManager] Verificando si quedaron modales:', document.querySelectorAll('#modal-factura-overlay').length);
        
        // Agregar estilos si no existen
        this.agregarEstilos();
        
        // Generar HTML de la factura
        const htmlFactura = this.generarHTMLFactura(datos);
        
        // Crear estructura del modal
        const modal = this.crearEstructuraModal(htmlFactura);
        
        // Agregar al DOM
        document.body.appendChild(modal);
        console.log('[InvoiceModalManager] Modal añadido al DOM');
        
        // Configurar eventos
        this.configurarEventos();
        
        console.log('[InvoiceModalManager] Modal de factura creado exitosamente');
    }

    /**
     * Agrega los estilos necesarios para el modal
     */
    agregarEstilos() {
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
                        width: 100% !important;
                        height: 100vh !important;
                        overflow: visible !important;
                        -webkit-print-color-adjust: exact !important;
                        print-color-adjust: exact !important;
                    }
                    
                    html {
                        margin: 0 !important;
                        padding: 0 !important;
                        background: white !important;
                        width: 100% !important;
                        height: 100vh !important;
                        overflow: visible !important;
                    }
                    
                    /* Estilos del overlay */
                    #modal-factura-overlay {
                        position: static !important;
                        top: 0 !important;
                        left: 0 !important;
                        width: 100vw !important;
                        min-height: 100vh !important;
                        height: auto !important;
                        background: white !important;
                        z-index: auto !important;
                        padding: 20px 0 !important;
                        margin: 0 !important;
                        border: none !important;
                        box-shadow: none !important;
                        backdrop-filter: none !important;
                        animation: none !important;
                        overflow: visible !important;
                    }
                    
                    /* Estilos del modal */
                    #modal-factura {
                        position: relative !important;
                        width: 100% !important;
                        max-width: 100% !important;
                        min-height: 100vh !important;
                        height: auto !important;
                        box-shadow: none !important;
                        border-radius: 0 !important;
                        border: none !important;
                        padding: 20px !important;
                        margin: 0 auto !important;
                        overflow: visible !important;
                        animation: none !important;
                        page-break-inside: avoid;
                        page-break-after: always;
                        box-sizing: border-box !important;
                    }
                    
                    /* Ocultar header con botones */
                    #modal-factura > div:first-child {
                        display: none !important;
                    }
                    
                    /* Mostrar contenido de la factura */
                    #modal-factura > div {
                        display: block !important;
                        overflow: visible !important;
                    }
                    
                    /* Forzar overflow visible en TODO excepto imágenes */
                    *:not(img):not(video):not(canvas) {
                        overflow: visible !important;
                    }
                    
                    /* Asegurar que las imágenes no se desborden al imprimir */
                    #modal-factura-contenido img {
                        max-width: 80px !important;
                        max-height: 80px !important;
                        width: auto !important;
                        height: auto !important;
                        object-fit: contain !important;
                        overflow: hidden !important;
                        page-break-inside: avoid !important;
                    }
                    
                    /* Imágenes pequeñas (telas) */
                    #modal-factura-contenido img[style*="40px"] {
                        max-width: 40px !important;
                        max-height: 40px !important;
                    }
                    
                    /* Imágenes de procesos */
                    #modal-factura-contenido img[style*="50px"] {
                        max-width: 50px !important;
                        max-height: 50px !important;
                    }
                    
                    /* Evitar cortes en elementos importantes */
                    #modal-factura-contenido,
                    #modal-factura-contenido table,
                    #modal-factura-contenido tr,
                    #modal-factura-contenido td {
                        page-break-inside: avoid !important;
                        overflow: visible !important;
                    }
                    
                    /* Páginas */
                    @page {
                        size: A4 portrait;
                        margin: 0.2cm;
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
    }

    /**
     * Genera el HTML de la factura usando el renderer disponible
     */
    generarHTMLFactura(datos) {
        console.log('[InvoiceModalManager] INICIO generarHTMLFactura', {
            datos_recibidos: !!datos,
            datos_keys: datos ? Object.keys(datos) : [],
            epps_existe: !!(datos && datos.epps),
            epps_count: datos && datos.epps ? datos.epps.length : 0,
            timestamp: new Date().toISOString()
        });
        
        if (typeof window.generarHTMLFactura === 'function') {
            try {
                const datosPedido = datos.data || datos;
                console.log('[InvoiceModalManager] Generando HTML con datos:', {
                    prendas_existe: !!datosPedido.prendas,
                    prendas_count: datosPedido.prendas?.length || 0,
                    epps_existe: !!datosPedido.epps,
                    epps_count: datosPedido.epps?.length || 0,
                    epps_data: datosPedido.epps
                });
                
                const htmlFactura = window.generarHTMLFactura(datosPedido);
                
                console.log('[InvoiceModalManager] HTML generado:', {
                    length: htmlFactura.length,
                    contiene_actualizado: htmlFactura.includes('ACTUALIZADO'),
                    contiene_imagenes: htmlFactura.includes('img src'),
                    preview: htmlFactura.substring(0, 300)
                });
                
                if (!htmlFactura || htmlFactura.trim().length === 0) {
                    throw new Error('HTML vacío generado');
                }
                
                return htmlFactura;
            } catch (error) {
                console.error('[InvoiceModalManager] Error generando HTML:', error);
                return this.generarHTMLError(error, datos);
            }
        } else {
            console.error('[InvoiceModalManager] Función generarHTMLFactura no disponible');
            return this.generarHTMLFallback(datos);
        }
    }

    /**
     * Genera HTML de error
     */
    generarHTMLError(error, datos) {
        return `
            <div style="padding: 30px; background: #fee2e2; border: 1px solid #fca5a5; border-radius: 6px; color: #dc2626;">
                <h3>Error al generar factura</h3>
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
                    Prendas: ${datos.prendas?.length || 0}
                </div>
            </div>
        `;
    }

    /**
     * Genera HTML fallback
     */
    generarHTMLFallback(datos) {
        return `
            <div style="padding: 30px; background: #f3f4f6; border-radius: 6px;">
                <h3>Información del Pedido</h3>
                <p><strong>Pedido #${datos.numero_pedido}</strong></p>
                <p>Cliente: ${datos.cliente}</p>
                <p>Asesor: ${datos.asesora}</p>
                <p><em style="color: #666;">Prendas: ${datos.prendas?.length || 0}</em></p>
            </div>
        `;
    }

    /**
     * Crea la estructura completa del modal
     */
    crearEstructuraModal(htmlFactura) {
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
        const header = this.crearHeader();
        modal.appendChild(header);
        
        // Contenido de la factura
        const contenido = document.createElement('div');
        contenido.id = 'modal-factura-contenido';
        contenido.style.cssText = `
            padding: 30px;
            overflow-y: auto;
            max-height: calc(90vh - 100px);
        `;
        contenido.innerHTML = htmlFactura;
        modal.appendChild(contenido);
        
        overlay.appendChild(modal);
        return overlay;
    }

    /**
     * Crea el header del modal con botones
     */
    crearHeader() {
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
        
        // Título
        const titulo = document.createElement('h2');
        titulo.textContent = ' Recibo del Pedido';
        titulo.style.cssText = 'margin: 0; font-size: 1.1rem; font-weight: 600;';
        header.appendChild(titulo);
        
        // Botones de acción
        const botonesAccion = this.crearBotonesAccion();
        header.appendChild(botonesAccion);
        
        return header;
    }

    /**
     * Crea los botones de acción del header
     */
    crearBotonesAccion() {
        const botonesAccion = document.createElement('div');
        botonesAccion.style.cssText = 'display: flex; gap: 8px; align-items: center;';
        
        // Botón Imprimir
        const btnImprimir = this.crearBotonImprimir();
        botonesAccion.appendChild(btnImprimir);
        
        // Botón Cerrar
        const btnCerrar = this.crearBotonCerrar();
        botonesAccion.appendChild(btnCerrar);
        
        return botonesAccion;
    }

    /**
     * Crea el botón de imprimir
     */
    crearBotonImprimir() {
        const btnImprimir = document.createElement('button');
        btnImprimir.id = 'print-receipt-btn';
        btnImprimir.innerHTML = '<span class="material-symbols-rounded" style="font-size: 20px;">print</span>';
        btnImprimir.style.cssText = `
            background: #3b82f6;
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
        
        btnImprimir.onmouseover = () => {
            btnImprimir.style.background = '#2563eb';
            btnImprimir.style.transform = 'scale(1.1)';
        };
        
        btnImprimir.onmouseout = () => {
            btnImprimir.style.background = '#3b82f6';
            btnImprimir.style.transform = 'scale(1)';
        };
        
        btnImprimir.onclick = () => this.imprimirFacturaModal();
        
        return btnImprimir;
    }

    /**
     * Crea el botón de cerrar
     */
    crearBotonCerrar() {
        const btnCerrar = document.createElement('button');
        btnCerrar.id = 'close-receipt-btn';
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
        
        btnCerrar.onclick = () => this.cerrarModalFactura();
        
        return btnCerrar;
    }

    /**
     * Configura los eventos del modal
     */
    configurarEventos() {
        // Cerrar con ESC
        const manejadorEscape = (e) => {
            if (e.key === 'Escape') {
                this.cerrarModalFactura();
                document.removeEventListener('keydown', manejadorEscape);
            }
        };
        document.addEventListener('keydown', manejadorEscape);
        
        // Cerrar al hacer clic fuera
        const overlay = document.getElementById('modal-factura-overlay');
        if (overlay) {
            overlay.onclick = (e) => {
                if (e.target === overlay) {
                    this.cerrarModalFactura();
                }
            };
        }
    }

    /**
     * Cierra el modal de factura
     */
    cerrarModalFactura() {
        console.log('[InvoiceModalManager] INICIO cerrarModalFactura');
        
        const overlay = document.getElementById('modal-factura-overlay');
        console.log('[InvoiceModalManager] Modal encontrado para cerrar:', !!overlay);
        
        if (overlay) {
            // Removing directly without animation to prevent stacking issues
            console.log('[InvoiceModalManager] Eliminando modal del DOM');
            overlay.remove();
            console.log('[InvoiceModalManager] Modal eliminado - verificando:', document.getElementById('modal-factura-overlay') === null);
        }
        
        // Ocultar loading si está activo
        if (window.loadingManager) {
            console.log('[InvoiceModalManager] Ocultando loading');
            window.loadingManager.ocultarCargando();
        }
        
        console.log('[InvoiceModalManager] Cierre completado');
    }

    /**
     * Imprime la factura del modal
     */
    imprimirFacturaModal() {
        console.log('[InvoiceModalManager] Iniciando impresión de factura');
        
        // Diagnosticar CSS antes de imprimir
        this.diagnosticarCSSImpresion();
        
        // Usar window.print() para imprimir el modal
        window.print();
    }

    /**
     * Diagnostica qué CSS se está aplicando para impresión
     */
    diagnosticarCSSImpresion() {
        console.log('[CSS-DIAGNOSTIC] 🔍 Iniciando diagnóstico de CSS para impresión...');
        
        // Verificar estilos de elementos clave
        const elementos = [
            'body',
            '#modal-factura-overlay',
            '#modal-factura',
            '#modal-factura-contenido',
            '#modal-factura-contenido img',
            '#modal-factura-contenido div',
            '#modal-factura-contenido table'
        ];
        
        elementos.forEach(selector => {
            const elemento = selector === 'body' ? document.body : document.querySelector(selector);
            if (elemento) {
                const estilos = window.getComputedStyle(elemento);
                console.log(`[CSS-DIAGNOSTIC] 📋 ${selector}:`, {
                    overflow: estilos.overflow,
                    display: estilos.display,
                    position: estilos.position,
                    width: estilos.width,
                    height: estilos.height,
                    visibility: estilos.visibility
                });
            } else {
                console.warn(`[CSS-DIAGNOSTIC] ⚠️ Elemento no encontrado: ${selector}`);
            }
        });
        
        // Verificar reglas @media print
        console.log('[CSS-DIAGNOSTIC] 📄 Buscando reglas @media print...');
        const reglas = Array.from(document.styleSheets).flatMap(sheet => {
            try {
                return Array.from(sheet.cssRules || []);
            } catch (e) {
                console.warn('[CSS-DIAGNOSTIC] No se puede acceder a reglas de:', sheet.href);
                return [];
            }
        });
        
        const reglasPrint = reglas.filter(regla => {
            return regla.type === CSSRule.MEDIA_RULE && 
                   regla.media && 
                   regla.media.mediaText.includes('print');
        });
        
        console.log(`[CSS-DIAGNOSTIC] 📐 Reglas @media print encontradas: ${reglasPrint.length}`);
        reglasPrint.forEach((regla, index) => {
            console.log(`[CSS-DIAGNOSTIC] 📐 Regla ${index + 1}:`, regla.media.mediaText);
            console.log(`[CSS-DIAGNOSTIC] 📐 Contenido:`, regla.cssText);
        });
        
        // Verificar imágenes específicas
        const imagenes = document.querySelectorAll('#modal-factura-contenido img');
        console.log(`[CSS-DIAGNOSTIC] 🖼️ Imágenes encontradas: ${imagenes.length}`);
        imagenes.forEach((img, index) => {
            const estilos = window.getComputedStyle(img);
            console.log(`[CSS-DIAGNOSTIC] 🖼️ Imagen ${index + 1}:`, {
                src: img.src.substring(0, 50) + '...',
                overflow: estilos.overflow,
                'object-fit': estilos.objectFit,
                width: estilos.width,
                height: estilos.height
            });
        });
        
        console.log('[CSS-DIAGNOSTIC] ✅ Diagnóstico completado');
    }

    /**
     * Verifica si hay un modal abierto
     */
    estaModalAbierto() {
        return !!document.getElementById('modal-factura-overlay');
    }

    /**
     * Actualiza el contenido del modal con nuevos datos
     */
    actualizarModal(datos) {
        const contenido = document.getElementById('modal-factura-contenido');
        if (contenido) {
            const htmlFactura = this.generarHTMLFactura(datos);
            contenido.innerHTML = htmlFactura;
            
            // Actualizar título
            const titulo = document.querySelector('#modal-factura h2');
            if (titulo) {
                titulo.textContent = ` Recibo del Pedido #${datos.numero_pedido}`;
            }
        }
    }
}

// Inicializar el gestor cuando se cargue el script
document.addEventListener('DOMContentLoaded', () => {
    window.invoiceModalManager = new InvoiceModalManager();
});

// También permitir inicialización manual
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.invoiceModalManager = new InvoiceModalManager();
    });
} else {
    window.invoiceModalManager = new InvoiceModalManager();
}
