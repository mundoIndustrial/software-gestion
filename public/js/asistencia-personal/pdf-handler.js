/**
 * Módulo de Manejo de PDF - Asistencia Personal
 * Procesamiento y gestión de archivos PDF
 */

const AsistenciaPDFHandler = (() => {
    let currentReportData = [];
    let insertReportBtn = null;

    /**
     * Inyectar estilos WinRAR
     */
    function injectWinRARStyles() {
        if (document.getElementById('winrar-modal-styles')) return;

        const style = document.createElement('style');
        style.id = 'winrar-modal-styles';
        style.innerHTML = `
            :root {
                --winrar-bg: #c0c0c0;
                --winrar-border-light: #ffffff;
                --winrar-border-dark: #808080;
                --winrar-border-black: #000000;
                --winrar-blue: #000080;
            }
            .winrar-processing-modal-overlay {
                position: fixed; top: 0; left: 0; right: 0; bottom: 0;
                background-color: rgba(0, 0, 0, 0.5);
                display: flex; align-items: center; justify-content: center;
                z-index: 9999; backdrop-filter: blur(2px);
            }
            .winrar-processing-modal {
                background-color: var(--winrar-bg);
                border: 2px solid; border-color: var(--winrar-border-light) var(--winrar-border-dark) var(--winrar-border-dark) var(--winrar-border-light);
                box-shadow: 1px 1px 0 0 var(--winrar-border-black), inset 1px 1px 0 0 var(--winrar-border-light);
                min-width: 450px; max-width: 600px; overflow: hidden;
                font-family: 'Segoe UI', Arial, sans-serif;
            }
            .winrar-title-bar {
                background: linear-gradient(90deg, var(--winrar-blue), #1084d0);
                padding: 2px 2px; display: flex;
                align-items: center; justify-content: space-between; height: 24px;
            }
            .winrar-title-content { display: flex; align-items: center; gap: 4px; flex: 1; }
            .winrar-title-text {
                color: white; font-weight: bold; font-size: 12px;
                letter-spacing: 0.3px; user-select: none;
                text-shadow: 0 1px 0 rgba(0, 0, 0, 0.3);
            }
            .winrar-close-btn {
                width: 22px; height: 22px;
                background-color: var(--winrar-bg);
                border: 1px solid;
                border-color: var(--winrar-border-light) var(--winrar-border-dark) var(--winrar-border-dark) var(--winrar-border-light);
                color: #000; font-size: 16px; font-weight: bold;
                cursor: pointer; display: flex;
                align-items: center; justify-content: center;
                transition: all 0.1s; outline: none; padding: 0;
            }
            .winrar-close-btn:hover { background-color: #ff0000; color: white; }
            .winrar-close-btn:active {
                border-color: var(--winrar-border-dark) var(--winrar-border-light) var(--winrar-border-light) var(--winrar-border-dark);
                box-shadow: inset 1px 1px 0 0 rgba(0, 0, 0, 0.3);
            }
            .winrar-modal-content { padding: 12px; display: flex; gap: 12px; }
            .winrar-icon-container {
                display: flex; flex-direction: column; gap: 2px;
                align-items: center; justify-content: center;
                width: 40px; flex-shrink: 0;
            }
            .winrar-icon-bar {
                width: 32px; height: 8px;
                border: 1px solid #000; border-radius: 1px;
                box-shadow: 0 1px 0 rgba(255, 255, 255, 0.5) inset;
            }
            .winrar-status-container {
                flex: 1; display: flex;
                flex-direction: column; gap: 8px;
            }
            .winrar-status-label {
                font-size: 12px; color: #000;
                font-weight: 500; letter-spacing: 0.2px;
            }
            .winrar-progress-bar-container {
                height: 20px; background-color: #e0e0e0;
                border: 2px solid;
                border-color: var(--winrar-border-dark) var(--winrar-border-light) var(--winrar-border-light) var(--winrar-border-dark);
                position: relative; overflow: hidden;
                box-shadow: inset 1px 1px 0 0 rgba(0, 0, 0, 0.1);
            }
            .winrar-progress-bar {
                height: 100%;
                background: linear-gradient(180deg, #0066ff 0%, #000080 100%);
                width: 0%; transition: width 0.15s linear;
                position: relative;
                box-shadow: inset 0 1px 0 0 rgba(255, 255, 255, 0.3);
            }
            .winrar-progress-bar::after {
                content: ''; position: absolute;
                top: 0; left: 0; right: 0; bottom: 0;
                background-image: repeating-linear-gradient(45deg, rgba(255,255,255,0.15), rgba(255,255,255,0.15) 10px, transparent 10px, transparent 20px);
                animation: stripeAnimation 1.5s linear infinite;
            }
            @keyframes stripeAnimation {
                0% { background-position: 0 0; }
                100% { background-position: 40px 0; }
            }
            .winrar-percent-text {
                font-size: 11px; color: #000;
                font-weight: bold; letter-spacing: 0.5px;
            }
            .winrar-button-container {
                padding: 8px 12px; display: flex;
                justify-content: flex-end; gap: 6px;
                background-color: #e8e8e8;
                border-top: 1px solid var(--winrar-border-light);
            }
            .winrar-button {
                background-color: var(--winrar-bg);
                border: 2px solid;
                border-color: var(--winrar-border-light) var(--winrar-border-dark) var(--winrar-border-dark) var(--winrar-border-light);
                color: #000; font-size: 11px; font-weight: 600;
                padding: 4px 16px; cursor: pointer; outline: none;
                transition: all 0.1s; user-select: none;
                box-shadow: 0 1px 0 0 var(--winrar-border-black);
            }
            .winrar-button:hover { background-color: #d8d8d8; }
            .winrar-button:active {
                border-color: var(--winrar-border-dark) var(--winrar-border-light) var(--winrar-border-light) var(--winrar-border-dark);
                box-shadow: inset 1px 1px 0 0 rgba(0, 0, 0, 0.3);
                transform: translate(1px, 1px);
            }
            @media (max-width: 600px) {
                .winrar-processing-modal { min-width: 300px; max-width: 90vw; }
                .winrar-modal-content { flex-direction: column; }
                .winrar-icon-container { width: 100%; flex-direction: row; justify-content: center; }
            }
            .winrar-save-modal-overlay {
                position: fixed; top: 0; left: 0; right: 0; bottom: 0;
                background-color: rgba(0, 0, 0, 0.5);
                display: flex; align-items: center; justify-content: center;
                z-index: 9999; backdrop-filter: blur(2px);
            }
            .winrar-save-modal {
                background-color: var(--winrar-bg);
                border: 2px solid; border-color: var(--winrar-border-light) var(--winrar-border-dark) var(--winrar-border-dark) var(--winrar-border-light);
                box-shadow: 1px 1px 0 0 var(--winrar-border-black), inset 1px 1px 0 0 var(--winrar-border-light);
                min-width: 420px; max-width: 550px; overflow: hidden;
                font-family: 'Segoe UI', Arial, sans-serif;
            }
            .winrar-save-title {
                background: linear-gradient(90deg, var(--winrar-blue), #1084d0);
                padding: 2px 2px; display: flex;
                align-items: center; justify-content: space-between; height: 24px;
            }
            .winrar-save-title-text {
                color: white; font-weight: bold; font-size: 12px;
                letter-spacing: 0.3px; user-select: none;
                text-shadow: 0 1px 0 rgba(0, 0, 0, 0.3);
                flex: 1;
            }
            .winrar-save-content {
                padding: 20px; display: flex; gap: 16px; align-items: center;
            }
            .winrar-save-icon {
                display: flex; flex-direction: column; gap: 2px;
                align-items: center; justify-content: center;
                width: 50px; flex-shrink: 0;
            }
            .winrar-save-icon-bar {
                width: 40px; height: 10px;
                border: 1px solid #000; border-radius: 1px;
                box-shadow: 0 1px 0 rgba(255, 255, 255, 0.5) inset;
            }
            .winrar-save-text-container {
                flex: 1; display: flex; flex-direction: column; gap: 8px;
            }
            .winrar-save-status {
                font-size: 13px; color: #000;
                font-weight: 500;
            }
            .winrar-save-progress-bar-container {
                height: 18px; background-color: #e0e0e0;
                border: 2px solid;
                border-color: var(--winrar-border-dark) var(--winrar-border-light) var(--winrar-border-light) var(--winrar-border-dark);
                position: relative; overflow: hidden;
                box-shadow: inset 1px 1px 0 0 rgba(0, 0, 0, 0.1);
            }
            .winrar-save-progress-bar {
                height: 100%;
                background: linear-gradient(180deg, #0066ff 0%, #000080 100%);
                width: 0%; transition: width 0.15s linear;
                position: relative;
                box-shadow: inset 0 1px 0 0 rgba(255, 255, 255, 0.3);
            }
            .winrar-save-progress-bar::after {
                content: ''; position: absolute;
                top: 0; left: 0; right: 0; bottom: 0;
                background-image: repeating-linear-gradient(45deg, rgba(255,255,255,0.15), rgba(255,255,255,0.15) 10px, transparent 10px, transparent 20px);
                animation: stripeAnimation 1.5s linear infinite;
            }
            .winrar-save-percent {
                font-size: 11px; color: #000;
                font-weight: bold; text-align: right;
            }
            .winrar-save-message {
                font-size: 12px; line-height: 1.4;
            }
            .winrar-save-message.success {
                color: #00aa00; font-weight: bold;
            }
            .winrar-save-message.info {
                color: #333;
            }
            .winrar-save-buttons {
                padding: 8px 12px; display: flex;
                justify-content: flex-end; gap: 8px;
                background-color: #e8e8e8;
                border-top: 1px solid var(--winrar-border-light);
            }
            .winrar-save-btn-ok {
                background-color: #00aa00;
                border: 2px solid;
                border-color: var(--winrar-border-light) #008800 #008800 var(--winrar-border-light);
                color: white; font-weight: 600;
                padding: 6px 24px; cursor: pointer; outline: none;
                transition: all 0.1s; user-select: none;
                box-shadow: 0 1px 0 0 #000;
            }
            .winrar-save-btn-ok:hover { background-color: #00cc00; }
            .winrar-save-btn-ok:active {
                border-color: #008800 var(--winrar-border-light) var(--winrar-border-light) #008800;
                box-shadow: inset 1px 1px 0 0 rgba(0, 0, 0, 0.3);
                transform: translate(1px, 1px);
            }
            .winrar-save-btn-ok:disabled {
                opacity: 0.6; cursor: not-allowed;
            }
        `;
        document.head.appendChild(style);
    }

    /**
     * Inicializar el módulo
     */
    function init() {
        // Inyectar estilos
        injectWinRARStyles();

        insertReportBtn = document.getElementById('insertReportBtn');
        const saveReportBtn = document.getElementById('saveReportBtn');
        const pdfInput = document.getElementById('pdfInput');

        if (!insertReportBtn || !saveReportBtn || !pdfInput) {

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
     * Subir y procesar PDF con animación estilo WinRAR
     */
    function uploadPDF(file) {
        const formData = new FormData();
        formData.append('pdf', file);

        // Mostrar modal de carga con animación WinRAR
        const processingModal = createProcessingModal(file.name);
        document.body.appendChild(processingModal);

        // Simulación de progreso (mientras se procesa el PDF)
        let progress = 0;
        const progressInterval = setInterval(() => {
            if (progress < 90) {
                progress += Math.random() * 30;
                updateProcessingProgress(processingModal, Math.min(progress, 90));
            }
        }, 200);

        fetch('/asistencia-personal/procesar-pdf', {
            method: 'POST',
            body: formData,
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
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
            clearInterval(progressInterval);
            updateProcessingProgress(processingModal, 100);

            // Esperar a que la barra llegue al 100%
            setTimeout(() => {
                processingModal.remove();
                
                if (data.success) {
                    currentReportData = data.registros;
                    addPdfIndicator(insertReportBtn);
                    showPdfConfirmation(data.cantidad);
                } else {
                    alert('Error al procesar el PDF: ' + data.message);
                }
            }, 300);
        })
        .catch(error => {
            clearInterval(progressInterval);
            processingModal.remove();

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
     * Crear modal de procesamiento estilo WinRAR
     */
    function createProcessingModal(fileName) {
        const modal = document.createElement('div');
        modal.className = 'winrar-processing-modal-overlay';
        modal.innerHTML = `
            <div class="winrar-processing-modal">
                <!-- Barra de título -->
                <div class="winrar-title-bar">
                    <div class="winrar-title-content">
                        <span class="winrar-title-text">Procesando ${fileName}</span>
                    </div>
                    <button class="winrar-close-btn" type="button">✕</button>
                </div>

                <!-- Contenido -->
                <div class="winrar-modal-content">
                    <!-- Icono WinRAR -->
                    <div class="winrar-icon-container">
                        <div class="winrar-icon-bar" style="background-color: #9933ff;"></div>
                        <div class="winrar-icon-bar" style="background-color: #0066ff;"></div>
                        <div class="winrar-icon-bar" style="background-color: #00cc66;"></div>
                        <div class="winrar-icon-bar" style="background-color: #ffcc00;"></div>
                    </div>

                    <!-- Estado -->
                    <div class="winrar-status-container">
                        <div class="winrar-status-label">Leyendo registros del PDF...</div>
                        <div class="winrar-progress-bar-container">
                            <div class="winrar-progress-bar" style="width: 0%"></div>
                        </div>
                        <div class="winrar-percent-text">0%</div>
                    </div>
                </div>

                <!-- Botones -->
                <div class="winrar-button-container">
                    <button class="winrar-button" type="button">Cancelar</button>
                </div>
            </div>
        `;

        const closeBtn = modal.querySelector('.winrar-close-btn');
        const cancelBtn = modal.querySelector('.winrar-button');
        
        closeBtn.addEventListener('click', () => modal.remove());
        cancelBtn.addEventListener('click', () => modal.remove());

        return modal;
    }

    /**
     * Actualizar progreso del modal de procesamiento
     */
    function updateProcessingProgress(modal, progress) {
        const progressBar = modal.querySelector('.winrar-progress-bar');
        const percentText = modal.querySelector('.winrar-percent-text');
        
        progressBar.style.width = progress + '%';
        percentText.textContent = Math.round(progress) + '%';
    }

    /**
     * Crear modal unificado de guardado con carga y confirmación
     */
    function createSavingModal(cantidad) {
        const overlay = document.createElement('div');
        overlay.className = 'winrar-save-modal-overlay';
        overlay.innerHTML = `
            <div class="winrar-save-modal">
                <div class="winrar-save-title">
                    <span class="winrar-save-title-text">Guardando Registros</span>
                </div>
                <div class="winrar-save-content">
                    <div class="winrar-save-icon">
                        <div class="winrar-save-icon-bar" style="background-color: #9933ff;"></div>
                        <div class="winrar-save-icon-bar" style="background-color: #0066ff;"></div>
                        <div class="winrar-save-icon-bar" style="background-color: #00cc66;"></div>
                        <div class="winrar-save-icon-bar" style="background-color: #ffcc00;"></div>
                    </div>
                    <div class="winrar-save-text-container">
                        <div class="winrar-save-status">Guardando ${cantidad} registros...</div>
                        <div class="winrar-save-progress-bar-container">
                            <div class="winrar-save-progress-bar" style="width: 0%"></div>
                        </div>
                        <div class="winrar-save-percent">0%</div>
                    </div>
                </div>
                <div class="winrar-save-buttons">
                    <button class="winrar-save-btn-ok" type="button" disabled>Aceptar</button>
                </div>
            </div>
        `;

        document.body.appendChild(overlay);

        return {
            element: overlay,
            updateProgress: (progress) => {
                const progressBar = overlay.querySelector('.winrar-save-progress-bar');
                const percentText = overlay.querySelector('.winrar-save-percent');
                progressBar.style.width = progress + '%';
                percentText.textContent = Math.round(progress) + '%';
            },
            showSuccess: (numeroReporte) => {
                const container = overlay.querySelector('.winrar-save-text-container');
                container.innerHTML = `
                    <div class="winrar-save-message success">✓ ¡Datos Guardados Correctamente!</div>
                    <div class="winrar-save-message info">Se han guardado los registros exitosamente.</div>
                    <div style="background-color: #e8f5e9; border: 1px solid #c8e6c9; padding: 6px; margin-top: 4px; font-family: monospace; font-size: 10px; color: #1b5e20; font-weight: bold; border-radius: 2px;">
                        Reporte: ${numeroReporte}
                    </div>
                `;
                
                const okBtn = overlay.querySelector('.winrar-save-btn-ok');
                okBtn.disabled = false;
                okBtn.addEventListener('click', () => {
                    overlay.remove();
                });
            }
        };
    }

    /**
     * Guardar reporte en la base de datos
     */
    function saveReport() {
        const savingModal = createSavingModal(currentReportData.length);
        
        // Simular progreso
        let progress = 0;
        const progressInterval = setInterval(() => {
            if (progress < 90) {
                progress += Math.random() * 25;
                savingModal.updateProgress(Math.min(progress, 90));
            }
        }, 150);

        fetch('/asistencia-personal/guardar-registros', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: JSON.stringify({ registros: currentReportData })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            clearInterval(progressInterval);
            savingModal.updateProgress(100);

            if (data.success) {
                setTimeout(() => {
                    savingModal.showSuccess(data.numero_reporte);
                    currentReportData = [];
                    removePdfIndicator(insertReportBtn);
                    const pdfInput = document.getElementById('pdfInput');
                    pdfInput.value = '';
                    
                    // Recargar reportes en la vista sin hacer reload de la página
                    if (typeof recargarReportes === 'function') {
                        recargarReportes();
                    } else {
                        location.reload();
                    }
                }, 500);
            } else {
                clearInterval(progressInterval);
                savingModal.element.remove();
                alert('Error al guardar: ' + data.message);
            }
        })
        .catch(error => {
            clearInterval(progressInterval);
            savingModal.element.remove();

            alert('Error al guardar los registros');
        });
    }

    return {
        init,
        getCurrentReportData: () => currentReportData
    };
})();
