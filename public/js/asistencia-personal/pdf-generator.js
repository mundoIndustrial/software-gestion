/**
 * Módulo para generar PDFs de reportes
 * Maneja la creación y diseño del PDF de Horas Extras con diseño moderno
 */
const PDFGenerator = (() => {
    /**
     * Cargar librerías jsPDF y AutoTable si no existen
     */
    function cargarLibrerias(callback) {
        if (typeof jsPDF === 'undefined' || typeof window.jspdf === 'undefined') {
            // Crear un loader visual
            mostrarCargando(true);
            
            const script1 = document.createElement('script');
            script1.src = 'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js';
            script1.onload = function() {
                const script2 = document.createElement('script');
                script2.src = 'https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js';
                script2.onload = function() {
                    mostrarCargando(false);
                    callback();
                };
                script2.onerror = function() {
                    mostrarCargando(false);
                    alert('Error cargando la librería AutoTable. Por favor intente de nuevo.');
                    console.error('Error cargando AutoTable');
                };
                document.head.appendChild(script2);
            };
            script1.onerror = function() {
                mostrarCargando(false);
                alert('Error cargando la librería jsPDF. Por favor intente de nuevo.');
                console.error('Error cargando jsPDF');
            };
            document.head.appendChild(script1);
        } else {
            callback();
        }
    }

    /**
     * Mostrar/ocultar indicador de carga
     */
    function mostrarCargando(mostrar) {
        let loader = document.getElementById('pdfLoadingIndicator');
        if (mostrar) {
            if (!loader) {
                loader = document.createElement('div');
                loader.id = 'pdfLoadingIndicator';
                loader.style.cssText = `
                    position: fixed;
                    top: 50%;
                    left: 50%;
                    transform: translate(-50%, -50%);
                    background: white;
                    padding: 30px;
                    border-radius: 10px;
                    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                    z-index: 10000;
                    text-align: center;
                    font-family: Arial, sans-serif;
                `;
                loader.innerHTML = `
                    <div style="font-size: 14px; color: #333; margin-bottom: 15px;">Generando PDF...</div>
                    <div style="width: 40px; height: 40px; border: 4px solid #e0e0e0; border-top: 4px solid #1e5ba8; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto;" id="spinner"></div>
                    <style>
                        @keyframes spin {
                            0% { transform: rotate(0deg); }
                            100% { transform: rotate(360deg); }
                        }
                    </style>
                `;
                document.body.appendChild(loader);
            }
        } else {
            if (loader) {
                loader.remove();
            }
        }
    }

    /**
     * Generar PDF con la tabla de horas extras - Diseño Moderno
     * @param {Array} personasConExtras - Array con datos de personas
     * @param {Array} todasLasFechas - Array con todas las fechas
     */
    function generarPDF(personasConExtras, todasLasFechas) {
        try {
            if (typeof window.jspdf === 'undefined' || !window.jspdf.jsPDF) {
                throw new Error('jsPDF no está disponible');
            }

            const { jsPDF } = window.jspdf;
            const doc = new jsPDF('l', 'mm', 'a4'); // landscape
            const pageWidth = doc.internal.pageSize.getWidth();
            const pageHeight = doc.internal.pageSize.getHeight();
            
            // Color moderno - Azul degradado profesional
            const colorPrincipal = [30, 91, 168]; // #1e5ba8 - Azul profesional
            const colorSecundario = [52, 152, 219]; // #3498db - Azul claro
            const colorExito = [39, 174, 96]; // #27ae60 - Verde
            const colorFondo = [245, 248, 250]; // #f5f8fa - Azul muy claro
            
            // ==================== ENCABEZADO ====================
            // Fondo del encabezado con degradado simulado
            doc.setFillColor(...colorPrincipal);
            doc.rect(0, 0, pageWidth, 30, 'F');
            
            // Título principal
            doc.setFontSize(22);
            doc.setFont(undefined, 'bold');
            doc.setTextColor(255, 255, 255);
            doc.text('REPORTE DE HORAS EXTRAS', pageWidth / 2, 12, { align: 'center' });
            
            // Información de fecha y empresa
            doc.setFontSize(10);
            doc.setFont(undefined, 'normal');
            const now = new Date();
            const fecha = now.toLocaleDateString('es-CO', { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            });
            doc.text(`Generado: ${fecha}`, 14, 24);
            
            // ==================== TABLA ====================
            // Encabezados dinámicos
            const headers = ['NOMBRE Y APELLIDOS'];
            
            // Agregar fechas como encabezados (solo el día y mes)
            todasLasFechas.forEach(fecha => {
                const [año, mes, dia] = fecha.split('-');
                headers.push(`${dia}/${mes}`);
            });
            
            // Agregar Total y Valor
            headers.push('TOTAL HORAS', 'VALOR ($)');
            
            // Preparar datos para la tabla
            const datos = personasConExtras.map(persona => {
                const row = [persona.nombre];
                
                // Agregar horas por fecha
                todasLasFechas.forEach(fecha => {
                    const horasExtras = persona.horasExtrasPorFecha[fecha] || 0;
                    if (horasExtras > 0) {
                        row.push(horasExtras.toFixed(1));
                    } else {
                        row.push('-');
                    }
                });
                
                // Agregar total horas
                row.push(persona.totalHorasExtras.toFixed(1));
                
                // Obtener valor si existe
                const inputValor = document.querySelector(`input[data-codigo-persona="${persona.codigo_persona}"]`);
                let valorFormatado = '-';
                if (inputValor && inputValor.value) {
                    const valor = parseFloat(inputValor.value);
                    if (!isNaN(valor) && valor > 0) {
                        valorFormatado = valor.toLocaleString('es-CO', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
                    }
                }
                row.push(valorFormatado);
                
                return row;
            });
            
            // Agregar fila de TOTAL
            const totalRow = [];
            totalRow.push('TOTAL GENERAL');
            
            // Totales por fecha
            let totalGeneral = 0;
            todasLasFechas.forEach(fecha => {
                let totalFecha = 0;
                personasConExtras.forEach(persona => {
                    totalFecha += persona.horasExtrasPorFecha[fecha] || 0;
                    totalGeneral += persona.horasExtrasPorFecha[fecha] || 0;
                });
                totalRow.push(totalFecha > 0 ? totalFecha.toFixed(1) : '-');
            });
            
            totalRow.push(totalGeneral.toFixed(1));
            totalRow.push('-');
            
            // Tabla con diseño moderno
            doc.autoTable({
                head: [headers],
                body: datos,
                foot: [totalRow],
                startY: 35,
                theme: 'grid',
                styles: {
                    fontSize: 9,
                    cellPadding: 5,
                    halign: 'center',
                    valign: 'middle',
                    lineColor: [200, 200, 200],
                    lineWidth: 0.3,
                    fontStyle: 'normal',
                    textColor: [50, 50, 50]
                },
                headStyles: {
                    fillColor: colorPrincipal,
                    textColor: [255, 255, 255],
                    fontStyle: 'bold',
                    halign: 'center',
                    valign: 'middle',
                    fontSize: 10,
                    lineColor: colorPrincipal,
                    lineWidth: 0.5
                },
                footStyles: {
                    fillColor: colorSecundario,
                    textColor: [255, 255, 255],
                    fontStyle: 'bold',
                    halign: 'center',
                    valign: 'middle',
                    fontSize: 10,
                    lineColor: colorPrincipal,
                    lineWidth: 0.5
                },
                bodyStyles: {
                    lineColor: [220, 220, 220],
                    lineWidth: 0.2,
                    textColor: [40, 40, 40]
                },
                alternateRowStyles: {
                    fillColor: colorFondo
                },
                columnStyles: {
                    0: { 
                        halign: 'left', 
                        fontStyle: 'bold',
                        textColor: colorPrincipal
                    }
                },
                didDrawCell: function(data) {
                    // Resaltar valores
                    if (data.row.section === 'body') {
                        // Última columna de VALOR con color verde suave
                        if (data.column.index === headers.length - 1 && data.cell.text[0] !== '-') {
                            data.cell.styles.fillColor = [230, 250, 237];
                            data.cell.styles.textColor = colorExito;
                            data.cell.styles.fontStyle = 'bold';
                        }
                        // Penúltima columna TOTAL con color azul suave
                        if (data.column.index === headers.length - 2) {
                            data.cell.styles.fillColor = [225, 242, 253];
                            data.cell.styles.textColor = colorPrincipal;
                            data.cell.styles.fontStyle = 'bold';
                        }
                    }
                },
                margin: { top: 5, right: 10, bottom: 15, left: 10 },
                didDrawPage: function(data) {
                    // Pie de página
                    const pageSize = doc.internal.pageSize;
                    const pageHeight = pageSize.getHeight();
                    const pageWidth = pageSize.getWidth();
                    
                    doc.setFontSize(8);
                    doc.setTextColor(150, 150, 150);
                    doc.setFont(undefined, 'normal');
                    doc.text(
                        `Página ${data.pageNumber}`,
                        pageWidth / 2,
                        pageHeight - 5,
                        { align: 'center' }
                    );
                }
            });
            
            // ==================== DESCARGAR ====================
            const nombreArchivo = `reporte-horas-extras-${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}.pdf`;
            doc.save(nombreArchivo);
            
            // Mostrar notificación de éxito
            mostrarNotificacion('PDF descargado exitosamente', 'success');
            
        } catch (error) {
            console.error('Error generando PDF:', error);
            alert('Error al generar el PDF: ' + error.message);
        }
    }

    /**
     * Mostrar notificación al usuario
     */
    function mostrarNotificacion(mensaje, tipo = 'info') {
        const notificacion = document.createElement('div');
        notificacion.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 6px;
            font-family: Arial, sans-serif;
            font-size: 14px;
            z-index: 10000;
            animation: slideIn 0.3s ease-out;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        `;
        
        if (tipo === 'success') {
            notificacion.style.backgroundColor = '#27ae60';
            notificacion.style.color = 'white';
        } else if (tipo === 'error') {
            notificacion.style.backgroundColor = '#e74c3c';
            notificacion.style.color = 'white';
        } else {
            notificacion.style.backgroundColor = '#3498db';
            notificacion.style.color = 'white';
        }
        
        notificacion.textContent = mensaje;
        document.body.appendChild(notificacion);
        
        // Agregar estilos de animación
        if (!document.querySelector('style[data-notification-styles]')) {
            const style = document.createElement('style');
            style.setAttribute('data-notification-styles', 'true');
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
        
        // Remover después de 3 segundos
        setTimeout(() => {
            notificacion.style.animation = 'slideOut 0.3s ease-out forwards';
            setTimeout(() => notificacion.remove(), 300);
        }, 3000);
    }

    /**
     * Descargar tabla como PDF
     * @param {Array} personasConExtras - Datos de personas
     * @param {Array} todasLasFechas - Array con todas las fechas
     */
    function descargar(personasConExtras, todasLasFechas) {
        if (!personasConExtras || personasConExtras.length === 0) {
            mostrarNotificacion('No hay datos para descargar', 'error');
            return;
        }

        cargarLibrerias(() => {
            try {
                generarPDF(personasConExtras, todasLasFechas);
            } catch (error) {
                console.error('Error en descargar:', error);
                mostrarNotificacion('Error al generar el PDF', 'error');
            }
        });
    }

    return {
        descargar
    };
})();
