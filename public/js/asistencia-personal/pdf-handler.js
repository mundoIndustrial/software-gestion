/**
 * Módulo de Manejo de PDF - Asistencia Personal
 * Procesamiento y gestión de archivos PDF
 */

const AsistenciaPDFHandler = (() => {
    let currentReportData = [];
    let insertReportBtn = null;

    /**
     * Inicializar el módulo
     */
    function init() {
        insertReportBtn = document.getElementById('insertReportBtn');
        const saveReportBtn = document.getElementById('saveReportBtn');
        const pdfInput = document.getElementById('pdfInput');

        if (!insertReportBtn || !saveReportBtn || !pdfInput) {
            console.error('Elementos PDF no encontrados');
            return;
        }

        // Botón para insertar reporte
        insertReportBtn.addEventListener('click', function() {
            pdfInput.click();
        });

        // Manejo de selección de archivo PDF
        pdfInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file && file.type === 'application/pdf') {
                uploadPDF(file);
            } else {
                alert('Por favor selecciona un archivo PDF válido');
            }
        });

        // Botón guardar reporte
        saveReportBtn.addEventListener('click', function() {
            if (currentReportData.length === 0) {
                alert('Por favor carga un PDF primero');
                return;
            }
            saveReport();
        });
    }

    /**
     * Subir y procesar PDF
     */
    function uploadPDF(file) {
        const formData = new FormData();
        formData.append('pdf', file);

        fetch('/asistencia-personal/procesar-pdf', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                currentReportData = data.registros;
                showPdfConfirmation(data.cantidad);
                addPdfIndicator(insertReportBtn);
            } else {
                alert('Error al procesar el PDF: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al procesar el PDF: ' + error.message);
        });
    }

    /**
     * Mostrar modal de confirmación
     */
    function showPdfConfirmation(cantidad) {
        const modal = document.createElement('div');
        modal.className = 'modal-overlay';
        modal.innerHTML = `
            <div class="modal-content">
                <div class="modal-success">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                </div>
                <h3>PDF Adjuntado Correctamente</h3>
                <p>${cantidad} registros cargados</p>
                <button class="btn-modal-close">Aceptar</button>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        const closeBtn = modal.querySelector('.btn-modal-close');
        closeBtn.addEventListener('click', function() {
            modal.remove();
        });
        
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                modal.remove();
            }
        }, { once: true });
    }

    /**
     * Agregar indicador al botón
     */
    function addPdfIndicator(btn) {
        const existingIndicator = btn.querySelector('.pdf-indicator');
        if (existingIndicator) {
            existingIndicator.remove();
        }

        const indicator = document.createElement('span');
        indicator.className = 'pdf-indicator';
        indicator.textContent = '1';
        btn.appendChild(indicator);
    }

    /**
     * Remover indicador del botón
     */
    function removePdfIndicator(btn) {
        const indicator = btn.querySelector('.pdf-indicator');
        if (indicator) {
            indicator.remove();
        }
    }

    /**
     * Guardar reporte en la base de datos
     */
    function saveReport() {
        const confirmSave = confirm(`¿Deseas guardar ${currentReportData.length} registros de asistencia?`);
        
        if (!confirmSave) return;

        fetch('/asistencia-personal/guardar-registros', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: JSON.stringify({ registros: currentReportData })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(`✓ ${data.guardados} registros guardados correctamente\nReporte: ${data.numero_reporte}`);
                currentReportData = [];
                
                removePdfIndicator(insertReportBtn);
                const pdfInput = document.getElementById('pdfInput');
                pdfInput.value = '';
                
                location.reload();
            } else {
                alert('Error al guardar: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al guardar los registros');
        });
    }

    return {
        init,
        getCurrentReportData: () => currentReportData
    };
})();
