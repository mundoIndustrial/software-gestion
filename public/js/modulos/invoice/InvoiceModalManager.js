/**
 * Gestor de Modal de Factura desde Lista
 * Maneja la creación y gestión del modal para visualizar facturas
 */

class InvoiceModalManager {
    constructor() {
        this.menuImpresionAbierto = false;
        this.init();
    }

    init() {
        // Hacer métodos disponibles globalmente para compatibilidad
        globalThis.crearModalFacturaDesdeListaPedidos = this.crearModalFactura.bind(this);
        globalThis.cerrarModalFactura = this.cerrarModalFactura.bind(this);
        globalThis.imprimirFacturaModal = this.imprimirFacturaModal.bind(this);
        globalThis.imprimirDespachoPedido = this.imprimirDespachoPedido.bind(this);
    }

    /**
     * Crea y muestra el modal con la factura
     */
    crearModalFactura(datos) {
        // IMPORTANTE: Remover TODOS los modales anteriores completamente
        // Usar querySelectorAll para eliminar posibles duplicados
        const modalesAnteriores = document.querySelectorAll('#modal-factura-overlay');
        modalesAnteriores.forEach((modal, index) => {
            modal.remove();
        });

        // Agregar estilos si no existen
        this.agregarEstilos();

        // Generar HTML de la factura
        const htmlFactura = this.generarHTMLFactura(datos);
        this.ultimoDatosPedido = datos?.data || datos || null;

        // Crear estructura del modal
        const modal = this.crearEstructuraModal(htmlFactura);

        // Agregar al DOM
        document.body.appendChild(modal);
        // Configurar eventos
        this.configurarEventos();
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

        if (typeof globalThis.generarHTMLFactura === 'function') {
            try {
                const datosPedido = datos.data || datos;
                const htmlFactura = globalThis.generarHTMLFactura(datosPedido);

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
        contenido.__invoiceDatosPedido = this.ultimoDatosPedido;
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
        botonesAccion.style.cssText = 'display: flex; gap: 8px; align-items: center; position: relative;';

        // Botón Imprimir con menú desplegable
        const bloqueImpresion = this.crearBloqueImpresion();
        botonesAccion.appendChild(bloqueImpresion);

        // Botón Cerrar
        const btnCerrar = this.crearBotonCerrar();
        botonesAccion.appendChild(btnCerrar);

        return botonesAccion;
    }

    /**
     * Crea el bloque de impresión con menú desplegable
     */
    crearBloqueImpresion() {
        const contenedor = document.createElement('div');
        contenedor.style.cssText = 'position: relative; display: flex; align-items: center;';

        const btnImprimir = document.createElement('button');
        btnImprimir.id = 'print-receipt-btn';
        btnImprimir.innerHTML = '<span class="material-symbols-rounded" style="font-size: 20px;">print</span><span class="material-symbols-rounded" style="font-size: 14px; margin-left: 2px;">expand_more</span>';
        btnImprimir.style.cssText = `
            background: #3b82f6;
            color: white;
            border: none;
            padding: 8px 10px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0;
            width: auto;
            min-width: 52px;
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

        btnImprimir.onclick = (event) => {
            event.preventDefault();
            event.stopPropagation();
            this.toggleMenuImpresion();
        };

        const menu = this.crearMenuImpresion();

        contenedor.appendChild(btnImprimir);
        contenedor.appendChild(menu);
        return contenedor;
    }

    /**
     * Crea el menú desplegable de impresión
     */
    crearMenuImpresion() {
        const menu = document.createElement('div');
        menu.id = 'print-receipt-menu';
        menu.style.cssText = `
            position: absolute;
            top: 48px;
            right: 0;
            min-width: 220px;
            background: white;
            border: 1px solid #dbe4f0;
            border-radius: 10px;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.18);
            padding: 8px;
            display: none;
            z-index: 10001;
        `;

        menu.innerHTML = `
            <button type="button" id="btn-print-factura"
                style="
                    width: 100%;
                    text-align: left;
                    padding: 10px 12px;
                    border: none;
                    border-radius: 8px;
                    background: transparent;
                    color: #0f172a;
                    cursor: pointer;
                    display: flex;
                    align-items: center;
                    gap: 10px;
                    font-size: 0.875rem;
                    font-weight: 600;
                ">
                <span class="material-symbols-rounded" style="font-size: 18px; color: #2563eb;">description</span>
                Imprimir factura
            </button>
            <button type="button" id="btn-print-despacho"
                style="
                    width: 100%;
                    text-align: left;
                    padding: 10px 12px;
                    border: none;
                    border-radius: 8px;
                    background: transparent;
                    color: #0f172a;
                    cursor: pointer;
                    display: flex;
                    align-items: center;
                    gap: 10px;
                    font-size: 0.875rem;
                    font-weight: 600;
                    margin-top: 4px;
                ">
                <span class="material-symbols-rounded" style="font-size: 18px; color: #0f766e;">local_shipping</span>
                Imprimir despacho
            </button>
        `;

        menu.querySelector('#btn-print-factura').onclick = (event) => {
            event.preventDefault();
            event.stopPropagation();
            this.ocultarMenuImpresion();
            this.imprimirFacturaModal();
        };

        menu.querySelector('#btn-print-despacho').onclick = (event) => {
            event.preventDefault();
            event.stopPropagation();
            this.ocultarMenuImpresion();
            this.imprimirDespachoDesdeModal();
        };

        return menu;
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

    toggleMenuImpresion() {
        const menu = document.getElementById('print-receipt-menu');
        if (!menu) {
            return;
        }

        const nuevoEstado = menu.style.display === 'block' ? 'none' : 'block';
        menu.style.display = nuevoEstado;
        this.menuImpresionAbierto = nuevoEstado === 'block';
    }

    ocultarMenuImpresion() {
        const menu = document.getElementById('print-receipt-menu');
        if (menu) {
            menu.style.display = 'none';
        }
        this.menuImpresionAbierto = false;
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

        if (this.clickFueraMenuHandler) {
            document.removeEventListener('click', this.clickFueraMenuHandler);
        }

        this.clickFueraMenuHandler = (event) => {
            const menu = document.getElementById('print-receipt-menu');
            const boton = document.getElementById('print-receipt-btn');
            if (!menu || !boton) {
                return;
            }

            if (!menu.contains(event.target) && !boton.contains(event.target)) {
                this.ocultarMenuImpresion();
            }
        };

        document.addEventListener('click', this.clickFueraMenuHandler);
    }

    /**
     * Cierra el modal de factura
     */
    cerrarModalFactura() {
        const overlay = document.getElementById('modal-factura-overlay');
        if (overlay) {
            // Removing directly without animation to prevent stacking issues
            overlay.remove();
        }

        if (this.clickFueraMenuHandler) {
            document.removeEventListener('click', this.clickFueraMenuHandler);
            this.clickFueraMenuHandler = null;
        }

        // Ocultar loading si está activo
        if (globalThis.loadingManager) {
            globalThis.loadingManager.ocultarCargando();
        }
    }

    /**
     * Imprime la factura del modal
     */
    imprimirFacturaModal() {
        // Diagnosticar CSS antes de imprimir
        this.diagnosticarCSSImpresion();

        // Usar globalThis.print() para imprimir el modal
        globalThis.print();
    }

    /**
     * Imprime el diseño de despacho del pedido
     */
    async imprimirDespachoPedido() {
        const datosPedido = this.obtenerDatosPedidoActual();
        const pedidoId = datosPedido?.id || datosPedido?.pedido_id || datosPedido?.pedido_produccion_id;

        if (!pedidoId) {
            console.error('[InvoiceModalManager] No se pudo determinar el ID del pedido para impresión de despacho');
            return;
        }

        const popup = globalThis.open('', '', 'width=1200,height=800');

        if (!popup) {
            console.warn('[InvoiceModalManager] El navegador bloqueó la ventana emergente de despacho.');
            return;
        }

        this.mostrarCargandoDespachoEnPopup(popup);

        try {
            const response = await fetch(`/despacho/${pedidoId}`, {
                method: 'GET',
                headers: {
                    'Accept': 'text/html,application/xhtml+xml',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const html = await response.text();
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const htmlImpresion = this.generarHtmlImpresionDespacho(doc, datosPedido);

            popup.document.open();
            popup.document.write(htmlImpresion);
            popup.document.close();
            popup.focus();
        } catch (error) {
            console.error('[InvoiceModalManager] Error generando impresión de despacho:', error);
            popup.document.open();
            popup.document.write(`<!DOCTYPE html>
                <html lang="es">
                <head><meta charset="UTF-8"><title>Error</title></head>
                <body style="font-family: Arial, sans-serif; padding: 24px;">
                    <h3 style="color: #b91c1c;">No se pudo cargar la impresión de despacho</h3>
                    <p>${String(error.message || error)}</p>
                </body>
                </html>`);
            popup.document.close();
        }
    }

    /**
     * Alias legible para el flujo del menú
     */
    imprimirDespachoDesdeModal() {
        this.imprimirDespachoPedido();
    }

    /**
     * Recupera los datos actuales del pedido desde el modal
     */
    obtenerDatosPedidoActual() {
        return this.ultimoDatosPedido || null;
    }

    /**
     * Muestra un estado de carga en la ventana emergente
     */
    mostrarCargandoDespachoEnPopup(popup) {
        popup.document.open();
        popup.document.write(`<!DOCTYPE html>
            <html lang="es">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Despacho - Imprimir</title>
                <style>
                    body {
                        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                        margin: 0;
                        padding: 24px;
                        background: #fff;
                        color: #0f172a;
                    }
                </style>
            </head>
            <body>
                <div style="padding: 20px; font-size: 14px;">Cargando impresión de despacho...</div>
            </body>
            </html>`);
        popup.document.close();
    }

    /**
     * Extrae un literal JS simple desde un script renderizado
     */
    extraerValorScript(doc, nombreVariable, valorDefecto = '') {
        const scripts = Array.from(doc.querySelectorAll('script'))
            .map(script => script.textContent || '')
            .join('\n');

        const patron = new RegExp(`const\\s+${nombreVariable}\\s*=\\s*([^;]+);`);
        const match = scripts.match(patron);

        if (!match) {
            return valorDefecto;
        }

        const literal = match[1].trim();

        try {
            // El valor viene como literal JS renderizado por Blade.
            // Usamos Function para convertirlo a texto real sin depender del formato exacto de comillas.
            return Function(`"use strict"; return (${literal});`)();
        } catch (error) {
            return valorDefecto;
        }
    }

    /**
     * Formatea una fecha para el encabezado de impresión
     */
    formatearFechaImpresion(valorFecha) {
        if (!valorFecha) {
            return '—';
        }

        const fecha = new Date(valorFecha);
        if (Number.isNaN(fecha.getTime())) {
            return String(valorFecha);
        }

        return fecha.toLocaleString('es-CO', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            hour12: true
        });
    }

    /**
     * Genera el HTML del popup de despacho usando el DOM de la vista de despacho
     */
    generarHtmlImpresionDespacho(doc, datosPedido) {
        const escapeHtml = (value) => String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');

        const pedidoNumero = datosPedido?.numero_pedido ?? datosPedido?.numeroPedido ?? datosPedido?.pedido ?? '—';
        const cliente = datosPedido?.cliente ?? '—';
        const fechaCreacion = this.formatearFechaImpresion(
            datosPedido?.fecha_creacion ?? datosPedido?.fechaCreacion ?? datosPedido?.created_at ?? null
        );
        const ordenCompra = datosPedido?.orden_compra ?? datosPedido?.ordenCompra ?? '—';
        const observaciones = datosPedido?.observaciones ?? this.extraerValorScript(doc, 'observacionesAsesoraText', 'Sin observaciones');
        const pendientesBodegueroText = this.extraerValorScript(doc, 'pendientesBodegueroText', '— Sin observaciones');
        const observacionesAsesoraText = this.extraerValorScript(doc, 'observacionesAsesoraText', '— Sin observaciones');

        const filas = Array.from(doc.querySelectorAll('#tablaDespacho tr[data-tipo]'));
        let tablaHTML = `
            <table style="width: 100%; border-collapse: collapse; border: 2px solid #000;">
                <thead style="background: #f1f5f9; border-bottom: 2px solid #000;">
                    <tr>
                        <th style="padding: 8px 4px; text-align: left; font-weight: 600; font-size: 11px; border: 1px solid #000;">Descripción</th>
                        <th style="padding: 8px 4px; text-align: center; font-weight: 600; font-size: 11px; border: 1px solid #000; width: 60px;">Género</th>
                        <th style="padding: 8px 4px; text-align: center; font-weight: 600; font-size: 11px; border: 1px solid #000; width: 50px;">Talla</th>
                        <th style="padding: 8px 4px; text-align: center; font-weight: 600; font-size: 11px; border: 1px solid #000; width: 60px;">Cantidad</th>
                        <th style="padding: 8px 4px; text-align: center; font-weight: 600; font-size: 11px; border: 1px solid #000; width: 60px;">Pendiente</th>
                        <th style="padding: 8px 4px; text-align: center; font-weight: 600; font-size: 11px; border: 1px solid #000; width: 70px;">Parcial 1</th>
                        <th style="padding: 8px 4px; text-align: center; font-weight: 600; font-size: 11px; border: 1px solid #000; width: 60px;">Pendiente</th>
                        <th style="padding: 8px 4px; text-align: center; font-weight: 600; font-size: 11px; border: 1px solid #000; width: 70px;">Parcial 2</th>
                        <th style="padding: 8px 4px; text-align: center; font-weight: 600; font-size: 11px; border: 1px solid #000; width: 60px;">Pendiente</th>
                        <th style="padding: 8px 4px; text-align: center; font-weight: 600; font-size: 11px; border: 1px solid #000; width: 70px;">Parcial 3</th>
                        <th style="padding: 8px 4px; text-align: center; font-weight: 600; font-size: 11px; border: 1px solid #000; width: 60px;">Pendiente</th>
                    </tr>
                </thead>
                <tbody>
        `;

        let ultimoTipo = '';

        filas.forEach((fila, index) => {
            const tipo = fila.dataset.tipo;
            const id = fila.dataset.id;

            if (tipo !== ultimoTipo) {
                const nombreSeccion = tipo === 'prenda' ? 'Prendas' : 'EPP';
                tablaHTML += `
                    <tr style="background: #f1f5f9;">
                        <td colspan="11" style="padding: 8px 4px; font-weight: 600; font-size: 11px; border: 1px solid #000;">${nombreSeccion}</td>
                    </tr>
                `;
                ultimoTipo = tipo;
            }

            const descCell = fila.querySelector('td.col-descripcion');
            const generoCell = fila.querySelector('td.col-genero');
            const tallaCell = fila.querySelector('td.col-talla');
            const cantidadCell = fila.querySelector('td.col-cantidad');

            const tieneDescripcion = !!descCell;
            const limpiarCell = (cell) => {
                if (!cell) return '';
                const clone = cell.cloneNode(true);
                clone.querySelectorAll('button').forEach(btn => btn.remove());
                return clone.textContent.trim();
            };

            if (tieneDescripcion) {
                const cloneDesc = descCell.cloneNode(true);
                cloneDesc.querySelectorAll('button').forEach(btn => btn.remove());
                const descripcion = cloneDesc.innerHTML;

                let genero = limpiarCell(generoCell);
                if (!genero) {
                    genero = (fila.dataset.genero || '').trim() || '—';
                }

                let talla = limpiarCell(tallaCell) || '—';
                let cantidad = limpiarCell(cantidadCell) || '0';

                let rowspan = 1;
                for (let i = index + 1; i < filas.length; i++) {
                    if (filas[i].dataset.id !== id || filas[i].dataset.tipo !== tipo) {
                        break;
                    }

                    const nextDescCell = filas[i].querySelector('td.col-descripcion');
                    if (nextDescCell) {
                        break;
                    }

                    rowspan++;
                }

                tablaHTML += `
                    <tr style="border-bottom: 1px solid #000;">
                        <td style="padding: 8px 4px; font-size: 10px; border: 1px solid #000;" rowspan="${rowspan}">${descripcion}</td>
                        <td style="padding: 8px 4px; text-align: center; font-size: 10px; border: 1px solid #000;">${escapeHtml(genero)}</td>
                        <td style="padding: 8px 4px; text-align: center; font-size: 10px; border: 1px solid #000;">${escapeHtml(talla)}</td>
                        <td style="padding: 8px 4px; text-align: center; font-weight: 600; font-size: 10px; border: 1px solid #000;">${escapeHtml(cantidad)}</td>
                        <td style="padding: 4px; border: 1px solid #000;"><input type="text" style="width: 100%; border: none; text-align: center; font-size: 10px; padding: 2px;"></td>
                        <td style="padding: 4px; border: 1px solid #000;"><input type="text" style="width: 100%; border: none; text-align: center; font-size: 10px; padding: 2px;"></td>
                        <td style="padding: 4px; border: 1px solid #000;"><input type="text" style="width: 100%; border: none; text-align: center; font-size: 10px; padding: 2px;"></td>
                        <td style="padding: 4px; border: 1px solid #000;"><input type="text" style="width: 100%; border: none; text-align: center; font-size: 10px; padding: 2px;"></td>
                        <td style="padding: 4px; border: 1px solid #000;"><input type="text" style="width: 100%; border: none; text-align: center; font-size: 10px; padding: 2px;"></td>
                        <td style="padding: 4px; border: 1px solid #000;"><input type="text" style="width: 100%; border: none; text-align: center; font-size: 10px; padding: 2px;"></td>
                        <td style="padding: 4px; border: 1px solid #000;"><input type="text" style="width: 100%; border: none; text-align: center; font-size: 10px; padding: 2px;"></td>
                    </tr>
                `;
            } else {
                let genero = limpiarCell(generoCell);
                if (!genero) {
                    genero = (fila.dataset.genero || '').trim() || '—';
                }

                let talla = limpiarCell(tallaCell) || '—';
                let cantidad = limpiarCell(cantidadCell) || '0';

                tablaHTML += `
                    <tr style="border-bottom: 1px solid #000;">
                        <td style="padding: 8px 4px; text-align: center; font-size: 10px; border: 1px solid #000;">${escapeHtml(genero)}</td>
                        <td style="padding: 8px 4px; text-align: center; font-size: 10px; border: 1px solid #000;">${escapeHtml(talla)}</td>
                        <td style="padding: 8px 4px; text-align: center; font-weight: 600; font-size: 10px; border: 1px solid #000;">${escapeHtml(cantidad)}</td>
                        <td style="padding: 4px; border: 1px solid #000;"><input type="text" style="width: 100%; border: none; text-align: center; font-size: 10px; padding: 2px;"></td>
                        <td style="padding: 4px; border: 1px solid #000;"><input type="text" style="width: 100%; border: none; text-align: center; font-size: 10px; padding: 2px;"></td>
                        <td style="padding: 4px; border: 1px solid #000;"><input type="text" style="width: 100%; border: none; text-align: center; font-size: 10px; padding: 2px;"></td>
                        <td style="padding: 4px; border: 1px solid #000;"><input type="text" style="width: 100%; border: none; text-align: center; font-size: 10px; padding: 2px;"></td>
                        <td style="padding: 4px; border: 1px solid #000;"><input type="text" style="width: 100%; border: none; text-align: center; font-size: 10px; padding: 2px;"></td>
                        <td style="padding: 4px; border: 1px solid #000;"><input type="text" style="width: 100%; border: none; text-align: center; font-size: 10px; padding: 2px;"></td>
                        <td style="padding: 4px; border: 1px solid #000;"><input type="text" style="width: 100%; border: none; text-align: center; font-size: 10px; padding: 2px;"></td>
                    </tr>
                `;
            }
        });

        tablaHTML += `
                </tbody>
            </table>
        `;

        const mostrarAsesora = String(observacionesAsesoraText ?? '').trim() !== '' && String(observacionesAsesoraText ?? '') !== '— Sin observaciones';

        const pendientesHTML = `
            <div style="margin-top: 10px; font-size: 12px; color: #000;">
                <strong>Pendientes bodeguero:</strong>
                <div style="margin-top: 6px; white-space: pre-wrap; font-size: 11px; color: #000;">${escapeHtml(pendientesBodegueroText)}</div>
            </div>
            ${mostrarAsesora ? `
                <div style="margin-top: 10px; font-size: 12px; color: #000;">
                    <strong>Observaciones asesora:</strong>
                    <div style="margin-top: 6px; white-space: pre-wrap; font-size: 11px; color: #000;">${escapeHtml(observacionesAsesoraText)}</div>
                </div>
            ` : ''}
        `;

        return `<!DOCTYPE html>
            <html lang="es">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Despacho - Imprimir</title>
                <style>
                    @page { margin: 5mm; size: letter portrait; }
                    * { margin: 0; padding: 0; box-sizing: border-box; }
                    body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; font-size: 9px; background: white; padding: 0; margin: 0; }
                    h2 { text-align: center; margin-bottom: 5px; font-size: 14px; page-break-after: avoid; }
                    p { text-align: center; margin-bottom: 8px; font-size: 10px; page-break-after: avoid; }
                    table { width: 100%; border-collapse: collapse; page-break-before: avoid; }
                    thead { page-break-after: avoid; }
                    tr { page-break-inside: avoid; }
                    @media print {
                        body { margin: 0; padding: 0; }
                        h2, p { page-break-after: avoid; }
                        table { page-break-before: avoid; page-break-inside: auto; }
                    }
                </style>
            </head>
            <body>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; padding: 20px; border: 3px solid #000; border-radius: 10px; background: #f9fafb;">
                    <div style="text-align: left;">
                        <h2 style="margin: 0 0 12px 0; font-size: 20px; font-weight: bold; color: #000;">Despacho - Pedido ${escapeHtml(pedidoNumero)}</h2>
                        <p style="margin: 6px 0; font-size: 14px; color: #000;"><strong>Cliente:</strong> ${escapeHtml(cliente)}</p>
                        <p style="margin: 6px 0; font-size: 13px; color: #333;"><strong>Fecha de creación:</strong> ${escapeHtml(fechaCreacion)}</p>
                        <p style="margin: 6px 0; font-size: 13px; color: #333;"><strong>Orden de Compra:</strong> ${escapeHtml(ordenCompra)}</p>
                    </div>
                    <div style="text-align: center; flex: 1; margin: 0 20px;">
                        <h3 style="margin: 0 0 8px 0; font-size: 16px; font-weight: bold; color: #000;">Observaciones</h3>
                        <div style="text-align: center; font-size: 12px; color: #000; line-height: 1.6; white-space: pre-wrap;">${escapeHtml(observaciones)}</div>
                    </div>
                    <div style="text-align: right;">
                        <p style="margin: 0; font-size: 11px; color: #666;"><strong>Fecha de impresión:</strong></p>
                        <p style="margin: 0; font-size: 10px; color: #666;">${new Date().toLocaleString('es-CO', {
                            year: 'numeric',
                            month: '2-digit',
                            day: '2-digit',
                            hour: '2-digit',
                            minute: '2-digit',
                            hour12: true
                        })}</p>
                    </div>
                </div>
                ${tablaHTML}
                ${pendientesHTML}
                <script>
                    window.print();
                    window.onafterprint = function() { window.close(); };
                <\/script>
            </body>
            </html>`;
    }

    /**
     * Diagnostica qué CSS se está aplicando para impresión
     */
    diagnosticarCSSImpresion() {
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
                const estilos = globalThis.getComputedStyle(elemento);
            } else {
                console.warn(`[CSS-DIAGNOSTIC]  Elemento no encontrado: ${selector}`);
            }
        });

        // Verificar reglas @media print
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
        reglasPrint.forEach((regla, index) => {
        });

        // Verificar imágenes específicas
        const imagenes = document.querySelectorAll('#modal-factura-contenido img');
        imagenes.forEach((img, index) => {
            const estilos = globalThis.getComputedStyle(img);
            console.log({
                overflow: estilos.overflow,
                'object-fit': estilos.objectFit,
                width: estilos.width,
                height: estilos.height
            });
        });
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
    globalThis.invoiceModalManager = new InvoiceModalManager();
});

// También permitir inicialización manual
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        globalThis.invoiceModalManager = new InvoiceModalManager();
    });
} else {
    globalThis.invoiceModalManager = new InvoiceModalManager();
}
