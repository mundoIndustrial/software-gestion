@extends('layouts.app')

@section('content')
<div class="exportar-corte-container">
    <div class="exportar-corte-card">
        <div class="card-header">
            <h1 class="card-title">
                <i class="material-symbols-outlined">download</i>
                Exportar Datos de Corte
            </h1>
            <p class="card-subtitle">Selecciona el mes y año para generar el reporte</p>
        </div>

        <div class="card-body">
            <form id="exportForm" class="export-form">
                <div class="form-group">
                    <label for="mes" class="form-label">Mes</label>
                    <select id="mes" name="mes" class="form-control" required>
                        <option value="">-- Selecciona un mes --</option>
                        <option value="1">Enero</option>
                        <option value="2">Febrero</option>
                        <option value="3">Marzo</option>
                        <option value="4">Abril</option>
                        <option value="5">Mayo</option>
                        <option value="6">Junio</option>
                        <option value="7">Julio</option>
                        <option value="8">Agosto</option>
                        <option value="9">Septiembre</option>
                        <option value="10">Octubre</option>
                        <option value="11">Noviembre</option>
                        <option value="12">Diciembre</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="año" class="form-label">Año</label>
                    <input type="number" id="año" name="año" class="form-control" min="2020" max="2099" required>
                </div>

                <div class="form-actions">
                    <button type="button" id="generateBtn" class="btn btn-primary">
                        <i class="material-symbols-outlined">refresh</i>
                        Generar Reporte
                    </button>
                </div>
            </form>

            <div id="resultContainer" class="result-container" style="display: none;">
                <div class="result-header">
                    <h2>Reporte Generado</h2>
                    <button type="button" id="copyBtn" class="btn btn-success">
                        <i class="material-symbols-outlined">content_copy</i>
                        Copiar al Portapapeles
                    </button>
                </div>

                <div class="result-info">
                    <p id="resultMessage" class="info-message"></p>
                </div>

                <div class="table-preview-container">
                    <h3>Vista Previa de la Tabla</h3>
                    <div class="table-preview-wrapper">
                        <table id="previewTable" class="preview-table">
                            <thead>
                                <tr>
                                    <th>Fecha de Ingreso a Corte</th>
                                    <th>Número Pedido</th>
                                    <th>Cliente</th>
                                    <th>Prendas</th>
                                    <th>Descripción</th>
                                    <th>Tallas</th>
                                    <th>Total</th>
                                    <th>Cortador</th>
                                    <th>Fecha Terminación</th>
                                    <th>Género</th>
                                </tr>
                            </thead>
                            <tbody id="previewTableBody">
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="result-content">
                    <h3>Datos en Formato TSV (para copiar)</h3>
                    <textarea id="resultText" class="result-textarea" readonly></textarea>
                </div>
            </div>

            <div id="errorContainer" class="error-container" style="display: none;">
                <div class="error-content">
                    <i class="material-symbols-outlined">error</i>
                    <p id="errorMessage"></p>
                </div>
            </div>

            <div id="loadingContainer" class="loading-container" style="display: none;">
                <div class="spinner"></div>
                <p>Generando reporte...</p>
            </div>
        </div>
    </div>
</div>

<style>
.exportar-corte-container {
    padding: 2rem;
    background: var(--dashboard-bg-primary, #f8fafc);
    min-height: 100vh;
}

.exportar-corte-card {
    max-width: 600px;
    margin: 0 auto;
    background: var(--dashboard-bg-card, #ffffff);
    border-radius: 14px;
    border: 1px solid var(--dashboard-border, #e2e8f0);
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.card-header {
    padding: 2rem;
    border-bottom: 1px solid var(--dashboard-border, #e2e8f0);
    background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
    color: white;
}

.card-title {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.card-title i {
    font-size: 1.75rem;
}

.card-subtitle {
    margin: 0.5rem 0 0 0;
    font-size: 0.95rem;
    opacity: 0.9;
}

.card-body {
    padding: 2rem;
}

.export-form {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.form-label {
    font-weight: 600;
    color: var(--dashboard-text-primary, #0f172a);
    font-size: 0.95rem;
}

.form-control {
    padding: 0.75rem 1rem;
    border: 1px solid var(--dashboard-border, #e2e8f0);
    border-radius: 8px;
    font-size: 1rem;
    background: var(--dashboard-bg-primary, #f8fafc);
    color: var(--dashboard-text-primary, #0f172a);
    transition: all 0.3s ease;
}

.form-control:focus {
    outline: none;
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.form-actions {
    display: flex;
    gap: 1rem;
    margin-top: 1rem;
}

.btn {
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
}

.btn i {
    font-size: 1.25rem;
}

.btn-primary {
    background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
}

.btn-primary:active {
    transform: translateY(0);
}

.btn-success {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
}

.btn-success:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

.btn-success:active {
    transform: translateY(0);
}

.result-container {
    margin-top: 2rem;
    padding: 1.5rem;
    background: var(--dashboard-bg-primary, #f8fafc);
    border-radius: 8px;
    border: 1px solid var(--dashboard-border, #e2e8f0);
}

.table-preview-container {
    margin: 2rem 0;
    padding: 1.5rem;
    background: var(--dashboard-bg-card, #ffffff);
    border-radius: 8px;
    border: 1px solid var(--dashboard-border, #e2e8f0);
}

.table-preview-container h3 {
    margin: 0 0 1rem 0;
    font-size: 1.1rem;
    color: var(--dashboard-text-primary, #0f172a);
    font-weight: 600;
}

.table-preview-wrapper {
    overflow-x: auto;
    border-radius: 6px;
    border: 1px solid var(--dashboard-border, #e2e8f0);
}

.preview-table {
    width: 100%;
    border-collapse: collapse;
    background: var(--dashboard-bg-card, #ffffff);
    font-size: 0.85rem;
}

.preview-table thead {
    background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
    color: white;
    position: sticky;
    top: 0;
    z-index: 10;
}

.preview-table th {
    padding: 1rem 0.75rem;
    text-align: left;
    font-weight: 600;
    white-space: nowrap;
    border-right: 1px solid rgba(255, 255, 255, 0.2);
}

.preview-table th:last-child {
    border-right: none;
}

.preview-table tbody tr {
    border-bottom: 1px solid var(--dashboard-border, #e2e8f0);
    transition: background-color 0.2s ease;
}

.preview-table tbody tr:hover {
    background-color: rgba(37, 99, 235, 0.05);
}

.preview-table tbody tr:last-child {
    border-bottom: none;
}

.preview-table td {
    padding: 0.75rem;
    border-right: 1px solid var(--dashboard-border, #e2e8f0);
    color: var(--dashboard-text-primary, #0f172a);
    word-break: break-word;
    max-width: 200px;
}

.preview-table td:last-child {
    border-right: none;
}

.result-content {
    margin-top: 2rem;
}

.result-content h3 {
    margin: 0 0 1rem 0;
    font-size: 1.1rem;
    color: var(--dashboard-text-primary, #0f172a);
    font-weight: 600;
}

.result-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.result-header h2 {
    margin: 0;
    font-size: 1.25rem;
    color: var(--dashboard-text-primary, #0f172a);
}

.result-textarea {
    width: 100%;
    min-height: 400px;
    padding: 1rem;
    border: 1px solid var(--dashboard-border, #e2e8f0);
    border-radius: 8px;
    font-family: 'Courier New', monospace;
    font-size: 0.8rem;
    background: var(--dashboard-bg-card, #ffffff);
    color: var(--dashboard-text-primary, #0f172a);
    resize: vertical;
    white-space: pre-wrap;
    word-wrap: break-word;
    overflow-x: auto;
    line-height: 1.4;
}

.result-info {
    margin-top: 1rem;
}

.info-message {
    margin: 0;
    padding: 0.75rem 1rem;
    background: rgba(16, 185, 129, 0.1);
    border-left: 4px solid #10b981;
    color: #059669;
    border-radius: 4px;
    font-size: 0.9rem;
}

.error-container {
    margin-top: 2rem;
    padding: 1.5rem;
    background: rgba(239, 68, 68, 0.1);
    border-radius: 8px;
    border: 1px solid rgba(239, 68, 68, 0.3);
}

.error-content {
    display: flex;
    align-items: center;
    gap: 1rem;
    color: #dc2626;
}

.error-content i {
    font-size: 1.5rem;
    flex-shrink: 0;
}

.error-content p {
    margin: 0;
    font-size: 0.95rem;
}

.loading-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 1rem;
    padding: 2rem;
    color: var(--dashboard-text-secondary, #475569);
}

.spinner {
    width: 40px;
    height: 40px;
    border: 4px solid var(--dashboard-border, #e2e8f0);
    border-top-color: #2563eb;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

/* Modo oscuro */
body.dark-theme .exportar-corte-card {
    background: var(--dashboard-bg-card, #1e293b);
    border-color: var(--dashboard-border, #334155);
}

body.dark-theme .card-header {
    border-bottom-color: var(--dashboard-border, #334155);
}

body.dark-theme .form-control {
    background: var(--dashboard-bg-primary, #0f172a);
    color: var(--dashboard-text-primary, #f1f5f9);
    border-color: var(--dashboard-border, #334155);
}

body.dark-theme .result-container {
    background: var(--dashboard-bg-primary, #0f172a);
    border-color: var(--dashboard-border, #334155);
}

body.dark-theme .result-textarea {
    background: var(--dashboard-bg-card, #1e293b);
    color: var(--dashboard-text-primary, #f1f5f9);
    border-color: var(--dashboard-border, #334155);
}

body.dark-theme .table-preview-container {
    background: var(--dashboard-bg-card, #1e293b);
    border-color: var(--dashboard-border, #334155);
}

body.dark-theme .preview-table {
    background: var(--dashboard-bg-card, #1e293b);
}

body.dark-theme .preview-table td {
    color: var(--dashboard-text-primary, #f1f5f9);
    border-right-color: var(--dashboard-border, #334155);
}

body.dark-theme .preview-table tbody tr {
    border-bottom-color: var(--dashboard-border, #334155);
}

body.dark-theme .preview-table tbody tr:hover {
    background-color: rgba(59, 130, 246, 0.1);
}

body.dark-theme .table-preview-wrapper {
    border-color: var(--dashboard-border, #334155);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const generateBtn = document.getElementById('generateBtn');
    const copyBtn = document.getElementById('copyBtn');
    const exportForm = document.getElementById('exportForm');
    const resultContainer = document.getElementById('resultContainer');
    const errorContainer = document.getElementById('errorContainer');
    const loadingContainer = document.getElementById('loadingContainer');
    const resultText = document.getElementById('resultText');
    const errorMessage = document.getElementById('errorMessage');
    const resultMessage = document.getElementById('resultMessage');
    const mesSelect = document.getElementById('mes');
    const añoInput = document.getElementById('año');

    // Establecer el año actual por defecto
    const currentYear = new Date().getFullYear();
    const currentMonth = new Date().getMonth() + 1;
    añoInput.value = currentYear;
    mesSelect.value = currentMonth;

    generateBtn.addEventListener('click', function () {
        const mes = mesSelect.value;
        const año = añoInput.value;

        if (!mes || !año) {
            showError('Por favor selecciona mes y año');
            return;
        }

        generateReport(mes, año);
    });

    copyBtn.addEventListener('click', function () {
        const text = resultText.value;
        navigator.clipboard.writeText(text).then(function () {
            const originalText = copyBtn.innerHTML;
            copyBtn.innerHTML = '<i class="material-symbols-outlined">check_circle</i> Copiado!';
            copyBtn.style.background = 'linear-gradient(135deg, #10b981 0%, #059669 100%)';

            setTimeout(function () {
                copyBtn.innerHTML = originalText;
                copyBtn.style.background = '';
            }, 2000);
        }).catch(function () {
            showError('Error al copiar al portapapeles');
        });
    });

    function generateReport(mes, año) {
        showLoading();
        hideError();
        hideResult();

        fetch('{{ route("exportar-corte.generate") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                mes: parseInt(mes),
                año: parseInt(año)
            })
        })
            .then(response => response.json())
            .then(data => {
                hideLoading();

                if (data.success) {
                    resultText.value = data.data;
                    resultMessage.textContent = data.message || 'Reporte generado exitosamente.';
                    
                    // Generar tabla de vista previa
                    generarTablaPrevia(data.data);
                    
                    showResult();
                } else {
                    showError(data.message || 'Error al generar el reporte');
                }
            })
            .catch(error => {
                hideLoading();
                showError('Error al conectar con el servidor: ' + error.message);
            });
    }

    function generarTablaPrevia(datosText) {
        const lineas = datosText.trim().split('\n');
        const tableBody = document.getElementById('previewTableBody');
        tableBody.innerHTML = '';

        // Saltar la primera línea (encabezados) y procesar datos
        for (let i = 1; i < lineas.length; i++) {
            const columnas = lineas[i].split('\t');
            
            if (columnas.length === 10) {
                const row = document.createElement('tr');
                columnas.forEach(columna => {
                    const cell = document.createElement('td');
                    cell.textContent = columna.trim();
                    row.appendChild(cell);
                });
                tableBody.appendChild(row);
            }
        }
    }

    function showLoading() {
        loadingContainer.style.display = 'flex';
    }

    function hideLoading() {
        loadingContainer.style.display = 'none';
    }

    function showResult() {
        resultContainer.style.display = 'block';
    }

    function hideResult() {
        resultContainer.style.display = 'none';
    }

    function showError(message) {
        errorMessage.textContent = message;
        errorContainer.style.display = 'block';
    }

    function hideError() {
        errorContainer.style.display = 'none';
    }
});
</script>
@endsection
