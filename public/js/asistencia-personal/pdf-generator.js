/**
 * Módulo para generar PDFs de reportes
 * Maneja la creación y diseño del PDF de Horas Extras
 */
const PDFGenerator = (() => {
    /**
     * Cargar librerías jsPDF y AutoTable si no existen
     */
    function cargarLibrerias(callback) {
        if (typeof jsPDF === 'undefined') {
            const script1 = document.createElement('script');
            script1.src = 'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js';
            script1.onload = function() {
                const script2 = document.createElement('script');
                script2.src = 'https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js';
                script2.onload = callback;
                document.head.appendChild(script2);
            };
            document.head.appendChild(script1);
        } else {
            callback();
        }
    }

    /**
     * Generar PDF con la tabla de horas extras
     * @param {Array} personasConExtras - Array con datos de personas
     * @param {Array} todasLasFechas - Array con todas las fechas
     */
    function generarPDF(personasConExtras, todasLasFechas) {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF('l', 'mm', 'a4'); // landscape
        
        // Título principal
        const now = new Date();
        const fecha = now.toLocaleDateString('es-CO');
        doc.setFontSize(18);
        doc.setFont(undefined, 'bold');
        doc.text('HORAS EXTRAS', doc.internal.pageSize.getWidth() / 2, 12, { align: 'center' });
        
        doc.setFontSize(9);
        doc.setFont(undefined, 'normal');
        doc.text(`Generado: ${fecha}`, 14, 18);
        
        // Encabezados
        const headers = ['NOMBRE Y APELLIDOS'];
        
        // Agregar fechas como encabezados (solo el día)
        todasLasFechas.forEach(fecha => {
            const dia = fecha.split('-')[2];
            headers.push(dia);
        });
        
        // Agregar Total y Valor
        headers.push('TOTAL', 'VALOR');
        
        // Preparar datos para la tabla
        const datos = personasConExtras.map(persona => {
            const row = [persona.nombre];
            
            // Agregar horas por fecha (solo la parte entera, sin minutos)
            todasLasFechas.forEach(fecha => {
                const minutosExtras = persona.horasExtrasPorFecha[fecha] || 0;
                if (minutosExtras > 0) {
                    // Solo mostrar las horas enteras (sin minutos)
                    const horasEnteras = Math.floor(minutosExtras / 60);
                    row.push(horasEnteras.toString());
                } else {
                    row.push('');
                }
            });
            
            // Agregar total horas
            const horasCompletas = Math.floor(persona.totalHorasExtras / 60);
            row.push(horasCompletas.toString());
            
            // Obtener valor si existe - formatear como moneda
            const inputValor = document.querySelector(`input[data-codigo-persona="${persona.codigo_persona}"]`);
            let valorFormatado = '';
            if (inputValor && inputValor.value) {
                const valor = parseFloat(inputValor.value);
                if (!isNaN(valor) && valor > 0) {
                    valorFormatado = '$' + valor.toLocaleString('es-CO', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
                }
            }
            row.push(valorFormatado);
            
            return row;
        });
        
        // Agregar tabla al PDF con diseño mejorado
        doc.autoTable({
            head: [headers],
            body: datos,
            startY: 22,
            theme: 'grid',
            styles: {
                fontSize: 10,
                cellPadding: 4,
                halign: 'center',
                valign: 'middle',
                lineColor: [0, 0, 0],
                lineWidth: 0.5
            },
            headStyles: {
                fillColor: [50, 50, 50], // Gris oscuro como la tabla
                textColor: 255,
                fontStyle: 'bold',
                halign: 'center',
                valign: 'middle',
                fontSize: 10
            },
            bodyStyles: {
                lineColor: [100, 100, 100],
                lineWidth: 0.3
            },
            alternateRowStyles: {
                fillColor: [220, 230, 240] // Azul claro alternado
            },
            columnStyles: {
                0: { halign: 'left', fontStyle: 'bold' } // Nombre en negrita alineado a izquierda
            },
            didDrawCell: function(data) {
                // Hacer TOTAL y VALOR en negrita
                if (data.column.index === headers.length - 2 || data.column.index === headers.length - 1) {
                    if (data.row.section === 'body') {
                        data.cell.text.forEach(text => {
                            if (text) {
                                doc.setFont(undefined, 'bold');
                            }
                        });
                    }
                }
            },
            margin: { top: 5, right: 8, bottom: 8, left: 8 }
        });
        
        // Descargar PDF
        const nombreArchivo = `reporte-horas-extras-${now.getTime()}.pdf`;
        doc.save(nombreArchivo);
    }

    /**
     * Descargar tabla como PDF
     * @param {Array} personasConExtras - Datos de personas
     * @param {Array} todasLasFechas - Array con todas las fechas
     */
    function descargar(personasConExtras, todasLasFechas) {
        cargarLibrerias(() => {
            generarPDF(personasConExtras, todasLasFechas);
        });
    }

    return {
        descargar
    };
})();
