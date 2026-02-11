/**
 * Servicio de Exportación de Facturas
 * Maneja la exportación y guardado de facturas en diferentes formatos
 */

class InvoiceExportService {
    constructor() {
        this.init();
    }

    init() {
        // Hacer métodos disponibles globalmente para compatibilidad
        window.guardarComoHTML = this.guardarComoHTML.bind(this);
        window.exportarFacturaPDF = this.exportarFacturaPDF.bind(this);
        window.imprimirFactura = this.imprimirFactura.bind(this);
        window.compartirFactura = this.compartirFactura.bind(this);
    }

    /**
     * Guarda el HTML de la factura
     */
    guardarComoHTML(nombreArchivo) {
        const contenido = document.getElementById('preview-content')?.innerHTML;
        
        if (!contenido) {
            console.error('[InvoiceExportService] No se encontró el contenido para exportar');
            return false;
        }

        // Generar nombre de archivo si no se proporciona
        if (!nombreArchivo) {
            const fecha = new Date().toISOString().split('T')[0];
            nombreArchivo = `factura_${fecha}.html`;
        }

        // Asegurar que tenga extensión .html
        if (!nombreArchivo.endsWith('.html')) {
            nombreArchivo += '.html';
        }

        // Crear HTML completo con estilos
        const htmlCompleto = this.generarHTMLCompleto(contenido);

        // Crear elemento de descarga
        const elemento = document.createElement('a');
        elemento.setAttribute('href', 'data:text/html;charset=utf-8,' + encodeURIComponent(htmlCompleto));
        elemento.setAttribute('download', nombreArchivo);
        elemento.style.display = 'none';
        
        document.body.appendChild(elemento);
        elemento.click();
        document.body.removeChild(elemento);

        console.log('[InvoiceExportService] Factura guardada como:', nombreArchivo);
        return true;
    }

    /**
     * Genera el HTML completo con estilos para exportación
     */
    generarHTMLCompleto(contenido) {
        return `
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Factura de Pedido</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 11px;
            margin: 0;
            padding: 20px;
            background: white;
        }
        
        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border: 1px solid #ddd;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }
        
        th, td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        th {
            background: #f5f5f5;
            font-weight: 600;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }
        
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            text-align: center;
            color: #666;
        }
        
        @media print {
            body { margin: 0; padding: 10px; }
            .invoice-container { box-shadow: none; border: none; }
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        ${contenido}
        <div class="footer">
            <p>Factura generada el ${new Date().toLocaleDateString('es-ES')}</p>
        </div>
    </div>
</body>
</html>`;
    }

    /**
     * Exporta la factura como PDF (usando print del navegador)
     */
    exportarFacturaPDF(nombreArchivo) {
        if (!nombreArchivo) {
            const fecha = new Date().toISOString().split('T')[0];
            nombreArchivo = `factura_${fecha}.pdf`;
        }

        // Asegurar que tenga extensión .pdf
        if (!nombreArchivo.endsWith('.pdf')) {
            nombreArchivo += '.pdf';
        }

        // Abrir diálogo de impresión del navegador
        const contentWindow = document.getElementById('preview-content')?.contentWindow;
        
        if (contentWindow) {
            contentWindow.print();
        } else {
            window.print();
        }

        console.log('[InvoiceExportService] Diálogo de impresión PDF abierto para:', nombreArchivo);
        return true;
    }

    /**
     * Imprime la factura directamente
     */
    imprimirFactura() {
        const contentWindow = document.getElementById('preview-content')?.contentWindow;
        
        if (contentWindow) {
            contentWindow.print();
        } else {
            // Fallback: crear una nueva ventana con el contenido
            const contenido = document.getElementById('preview-content')?.innerHTML;
            if (contenido) {
                const nuevaVentana = window.open('', '_blank');
                if (nuevaVentana) {
                    nuevaVentana.document.write(this.generarHTMLCompleto(contenido));
                    nuevaVentana.document.close();
                    nuevaVentana.print();
                    nuevaVentana.close();
                }
            } else {
                console.error('[InvoiceExportService] No se encontró contenido para imprimir');
                return false;
            }
        }

        console.log('[InvoiceExportService] Factura enviada a impresión');
        return true;
    }

    /**
     * Comparte la factura (genera un enlace o copia al portapapeles)
     */
    compartirFactura() {
        const contenido = document.getElementById('preview-content')?.innerHTML;
        
        if (!contenido) {
            console.error('[InvoiceExportService] No se encontró contenido para compartir');
            return false;
        }

        // Opción 1: Copiar HTML al portapapeles
        if (navigator.clipboard && window.isSecureContext) {
            const htmlCompleto = this.generarHTMLCompleto(contenido);
            
            navigator.clipboard.writeText(htmlCompleto).then(() => {
                console.log('[InvoiceExportService] HTML copiado al portapapeles');
                this.mostrarNotificacion('HTML copiado al portapapeles', 'success');
                return true;
            }).catch(err => {
                console.error('[InvoiceExportService] Error copiando al portapapeles:', err);
                return this.compartirComoTexto(contenido);
            });
        } else {
            // Fallback para navegadores que no soportan clipboard API
            return this.compartirComoTexto(contenido);
        }
    }

    /**
     * Comparte como texto plano (fallback)
     */
    compartirComoTexto(contenido) {
        // Extraer texto del HTML
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = contenido;
        const textoPlano = tempDiv.textContent || tempDiv.innerText || '';

        // Crear área de texto temporal
        const textArea = document.createElement('textarea');
        textArea.value = textoPlano;
        textArea.style.position = 'fixed';
        textArea.style.opacity = '0';
        
        document.body.appendChild(textArea);
        textArea.select();
        
        try {
            document.execCommand('copy');
            console.log('[InvoiceExportService] Texto copiado al portapapeles');
            this.mostrarNotificación('Texto copiado al portapapeles', 'success');
            return true;
        } catch (err) {
            console.error('[InvoiceExportService] Error copiando texto:', err);
            this.mostrarNotificación('No se pudo copiar al portapapeles', 'error');
            return false;
        } finally {
            document.body.removeChild(textArea);
        }
    }

    /**
     * Muestra una notificación al usuario
     */
    mostrarNotificacion(mensaje, tipo = 'info') {
        // Crear elemento de notificación
        const notificacion = document.createElement('div');
        notificacion.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 20px;
            background: ${tipo === 'success' ? '#10b981' : tipo === 'error' ? '#ef4444' : '#3b82f6'};
            color: white;
            border-radius: 6px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 10003;
            font-size: 14px;
            font-weight: 500;
            opacity: 0;
            transform: translateY(-20px);
            transition: all 0.3s ease;
        `;
        notificacion.textContent = mensaje;

        document.body.appendChild(notificacion);

        // Animar entrada
        setTimeout(() => {
            notificacion.style.opacity = '1';
            notificacion.style.transform = 'translateY(0)';
        }, 100);

        // Remover después de 3 segundos
        setTimeout(() => {
            notificacion.style.opacity = '0';
            notificacion.style.transform = 'translateY(-20px)';
            setTimeout(() => {
                if (notificacion.parentNode) {
                    notificacion.parentNode.removeChild(notificacion);
                }
            }, 300);
        }, 3000);
    }

    /**
     * Exporta los datos de la factura como JSON
     */
    exportarComoJSON(datos, nombreArchivo) {
        if (!datos) {
            console.error('[InvoiceExportService] No se proporcionaron datos para exportar');
            return false;
        }

        if (!nombreArchivo) {
            const fecha = new Date().toISOString().split('T')[0];
            nombreArchivo = `factura_datos_${fecha}.json`;
        }

        // Asegurar que tenga extensión .json
        if (!nombreArchivo.endsWith('.json')) {
            nombreArchivo += '.json';
        }

        // Crear JSON con formato
        const jsonString = JSON.stringify(datos, null, 2);

        // Crear elemento de descarga
        const elemento = document.createElement('a');
        elemento.setAttribute('href', 'data:application/json;charset=utf-8,' + encodeURIComponent(jsonString));
        elemento.setAttribute('download', nombreArchivo);
        elemento.style.display = 'none';
        
        document.body.appendChild(elemento);
        elemento.click();
        document.body.removeChild(elemento);

        console.log('[InvoiceExportService] Datos exportados como JSON:', nombreArchivo);
        return true;
    }

    /**
     * Genera un identificador único para la factura
     */
    generarIdFactura() {
        const timestamp = Date.now();
        const random = Math.floor(Math.random() * 1000);
        return `INV_${timestamp}_${random}`;
    }

    /**
     * Valida que el contenido sea exportable
     */
    validarContenidoExportable() {
        const contenido = document.getElementById('preview-content')?.innerHTML;
        
        if (!contenido || contenido.trim().length === 0) {
            console.error('[InvoiceExportService] El contenido está vacío');
            return false;
        }

        // Validaciones adicionales si son necesarias
        return true;
    }
}

// Inicializar el servicio cuando se cargue el script
document.addEventListener('DOMContentLoaded', () => {
    window.invoiceExportService = new InvoiceExportService();
});

// También permitir inicialización manual
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.invoiceExportService = new InvoiceExportService();
    });
} else {
    window.invoiceExportService = new InvoiceExportService();
}
