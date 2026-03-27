@extends('layouts.app')

@section('content')
@php
    $totalStorage = $usedStorage + $availableStorage;
    $usedPercentage = $totalStorage > 0 ? round(($usedStorage / $totalStorage) * 100, 1) : 0;
    $availablePercentage = max(0, round(100 - $usedPercentage, 1));
@endphp
<style>
    .config-shell {
        --config-bg: linear-gradient(180deg, #f4f8fb 0%, #eaf2f7 100%);
        --config-card: rgba(255, 255, 255, 0.94);
        --config-border: #d7e4ea;
        --config-title: #123548;
        --config-text: #516674;
        --config-primary: #2f6f8f;
        --config-secondary: #5b8fa8;
        --config-accent: #78b7b1;
        --config-used: #2f6f8f;
        --config-free: #9ed6c8;
    }

    .config-card {
        background: var(--config-card);
        border-radius: 24px;
        padding: 28px;
        margin-bottom: 24px;
        box-shadow: 0 20px 45px rgba(32, 70, 92, 0.12);
        border: 1px solid var(--config-border);
        backdrop-filter: blur(8px);
    }
    
    .config-card-header {
        font-size: 1.15rem;
        font-weight: 700;
        letter-spacing: 0.01em;
        color: var(--config-title);
        margin-bottom: 22px;
        padding-bottom: 14px;
        border-bottom: 1px solid #d9e8ee;
    }
    
    .backup-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 14px 20px;
        border-radius: 16px;
        font-weight: 700;
        font-size: 15px;
        border: 1px solid rgba(255, 255, 255, 0.22);
        cursor: pointer;
        transition: all 0.3s ease;
        width: 100%;
        min-height: 58px;
    }
    
    .backup-btn:hover {
        transform: translateY(-3px);
        box-shadow: 0 16px 28px rgba(47, 111, 143, 0.18);
    }
    
    .backup-btn:active {
        transform: translateY(0);
    }
    
    .backup-btn-orange {
        background: linear-gradient(135deg, #2f6f8f 0%, #5b8fa8 100%);
        color: white;
    }
    
    .backup-btn-blue {
        background: linear-gradient(135deg, #4a88a8 0%, #78b7b1 100%);
        color: white;
    }
    
    .backup-btn-green {
        background: linear-gradient(135deg, #5ca48f 0%, #8ad0b6 100%);
        color: white;
    }

    .form-select-custom {
        background-color: #f7fbfc;
        color: var(--config-title);
        border: 1px solid var(--config-border);
        border-radius: 8px;
        padding: 10px 14px;
        font-size: 14px;
        width: 100%;
        transition: all 0.3s ease;
    }
    
    .form-select-custom:focus {
        outline: none;
        border-color: var(--config-primary);
        box-shadow: 0 0 0 3px rgba(47, 111, 143, 0.12);
    }
    
    .form-input-custom {
        background-color: #f7fbfc;
        color: var(--config-title);
        border: 1px solid var(--config-border);
        border-radius: 8px;
        padding: 10px 14px;
        font-size: 14px;
        width: 100%;
        transition: all 0.3s ease;
    }
    
    .form-input-custom:focus {
        outline: none;
        border-color: var(--config-primary);
        box-shadow: 0 0 0 3px rgba(47, 111, 143, 0.12);
    }
    
    .form-input-custom::placeholder {
        color: #8fa4af;
    }
    
    .form-label-custom {
        color: var(--config-text);
        font-size: 14px;
        font-weight: 500;
        margin-bottom: 8px;
        display: block;
    }
    
    .backup-description {
        color: var(--config-text);
        font-size: 14px;
        margin-bottom: 20px;
        text-align: center;
    }

    .backup-db-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 6px 14px;
        border-radius: 999px;
        background: #e8f2f7;
        color: var(--config-primary);
        font-weight: 700;
        border: 1px solid #d2e3eb;
    }

    .storage-card {
        background: linear-gradient(180deg, #ffffff 0%, #f4faf9 100%);
    }

    .storage-intro {
        text-align: center;
        color: var(--config-text);
        font-size: 14px;
        margin-bottom: 18px;
    }

    .storage-chart-wrap {
        position: relative;
        max-width: 260px;
        margin: 0 auto 22px;
    }

    .storage-chart-center {
        position: absolute;
        inset: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        pointer-events: none;
    }

    .storage-chart-center strong {
        color: var(--config-title);
        font-size: 2rem;
        line-height: 1;
    }

    .storage-chart-center span {
        color: var(--config-text);
        font-size: 0.9rem;
        margin-top: 6px;
    }

    .storage-summary {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
        margin-bottom: 14px;
    }

    .storage-metric {
        background: #f8fcfc;
        border: 1px solid #d8e8e7;
        border-radius: 18px;
        padding: 16px 14px;
    }

    .storage-metric-label {
        display: flex;
        align-items: center;
        gap: 8px;
        color: var(--config-text);
        font-size: 13px;
        margin-bottom: 10px;
    }

    .storage-dot {
        width: 11px;
        height: 11px;
        border-radius: 999px;
        display: inline-block;
    }

    .storage-dot-used {
        background: var(--config-used);
    }

    .storage-dot-free {
        background: var(--config-free);
    }

    .storage-metric strong {
        display: block;
        color: var(--config-title);
        font-size: 1.5rem;
        line-height: 1.1;
    }

    .storage-metric small {
        color: var(--config-text);
        font-size: 0.85rem;
    }

    .storage-caption {
        background: #eef6f8;
        border-radius: 16px;
        padding: 12px 14px;
        color: var(--config-text);
        font-size: 13px;
        text-align: center;
    }

    @media (max-width: 768px) {
        .config-card {
            padding: 22px;
            border-radius: 20px;
        }

        .storage-summary {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="container-fluid py-4 config-shell" style="background: var(--config-bg); border-radius: 28px;">
    <div class="row justify-content-center">
        <div class="col-12" style="max-width: 1200px;">
            <div class="row g-4">
                <!-- Backup de Base de Datos -->
                <div class="col-lg-7">
                    <div class="config-card h-100">
                        <div class="config-card-header text-center"> Backup de Base de Datos</div>
                        <p class="backup-description">Base de datos activa: <span class="backup-db-badge">{{ $currentDatabase }}</span></p>
                        
                        <div class="row g-2 px-3">
                            <div class="col-12">
                                <button type="button" class="backup-btn backup-btn-orange" id="backupLocalBtn">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                        <polyline points="7 10 12 15 17 10"></polyline>
                                        <line x1="12" y1="15" x2="12" y2="3"></line>
                                    </svg>
                                    Guardar en Servidor
                                </button>
                            </div>
                            
                            <div class="col-12">
                                <button type="button" class="backup-btn backup-btn-blue" id="backupDownloadBtn">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                        <polyline points="7 10 12 15 17 10"></polyline>
                                        <line x1="12" y1="15" x2="12" y2="3"></line>
                                    </svg>
                                    Descargar Backup
                                </button>
                            </div>
                            
                            <div class="col-12">
                                <button type="button" class="backup-btn backup-btn-green" id="backupGoogleDriveBtn">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>
                                    </svg>
                                    Google Drive
                                </button>
                            </div>

                        </div>
                    </div>
                </div>
                <!-- Gráfico de Almacenamiento -->
                <div class="col-lg-5">
                    <div class="config-card storage-card h-100">
                        <div class="config-card-header text-center"> Uso de Almacenamiento</div>
                        <p class="storage-intro">Resumen visual del espacio usado en la base actual. Los porcentajes y valores quedan visibles sin pasar el mouse.</p>
                        <div class="storage-chart-wrap">
                            <canvas id="storageChart"></canvas>
                            <div class="storage-chart-center">
                                <strong>{{ $usedPercentage }}%</strong>
                                <span>ocupado</span>
                            </div>
                        </div>
                        <div class="storage-summary">
                            <div class="storage-metric">
                                <div class="storage-metric-label">
                                    <span class="storage-dot storage-dot-used"></span>
                                    <span>Espacio usado</span>
                                </div>
                                <strong>{{ number_format($usedStorage, 2) }} MB</strong>
                                <small>{{ $usedPercentage }}% del total disponible</small>
                            </div>
                            <div class="storage-metric">
                                <div class="storage-metric-label">
                                    <span class="storage-dot storage-dot-free"></span>
                                    <span>Espacio disponible</span>
                                </div>
                                <strong>{{ number_format($availableStorage, 2) }} MB</strong>
                                <small>{{ $availablePercentage }}% libre actualmente</small>
                            </div>
                        </div>
                        <div class="storage-caption">
                            Capacidad estimada total: {{ number_format($totalStorage, 2) }} MB. Si el porcentaje usado crece, conviene generar y descargar respaldo con más frecuencia.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Backup -->
<div id="backupModal" style="display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(22,46,61,0.28); backdrop-filter: blur(3px);">
    <div style="background: linear-gradient(180deg, #ffffff 0%, #f5faf9 100%); margin: 10% auto; padding: 0; border-radius: 20px; width: 90%; max-width: 500px; box-shadow: 0 24px 40px rgba(32,70,92,0.16); border: 1px solid #d7e4ea;">
        <div style="background: linear-gradient(135deg, #2f6f8f 0%, #5b8fa8 100%); color: white; padding: 20px; border-radius: 20px 20px 0 0; display: flex; justify-content: space-between; align-items: center;">
            <h5 style="margin: 0; font-size: 18px; font-weight: bold;">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: inline-block; vertical-align: middle; margin-right: 8px;">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="7 10 12 15 17 10"></polyline>
                    <line x1="12" y1="15" x2="12" y2="3"></line>
                </svg>
                Backup de Base de Datos
            </h5>
            <button onclick="closeBackupModal()" style="background: none; border: none; color: white; font-size: 28px; cursor: pointer; padding: 0; line-height: 1;">&times;</button>
        </div>
        <div style="padding: 30px; text-align: center;">
            <div id="backupLoading" style="display: none;">
                <div style="border: 4px solid #e7eff3; border-top: 4px solid #2f6f8f; border-radius: 50%; width: 50px; height: 50px; animation: spin 1s linear infinite; margin: 0 auto;"></div>
                <p style="margin-top: 20px; color: #374151; font-size: 16px;" id="backupLoadingMessage">Creando backup, por favor espere...</p>
                <p style="margin-top: 10px; color: #6b7280; font-size: 14px;">Este proceso puede tardar varios minutos dependiendo del tamaño de la base de datos.</p>
            </div>
            <div id="backupSuccess" style="display: none;">
                <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin: 0 auto;">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
                <h4 style="margin-top: 20px; color: #10b981; font-weight: bold;">¡Backup Creado Exitosamente!</h4>
                <p style="margin-top: 15px; color: #374151;">
                    <strong>Archivo:</strong> <span id="backupFilename"></span><br>
                    <strong>Tamaño:</strong> <span id="backupSize"></span>
                </p>
            </div>
            <div id="backupError" style="display: none;">
                <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin: 0 auto;">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="15" y1="9" x2="9" y2="15"></line>
                    <line x1="9" y1="9" x2="15" y2="15"></line>
                </svg>
                <h4 style="margin-top: 20px; color: #ef4444; font-weight: bold;">Error al Crear Backup</h4>
                <p style="margin-top: 15px; color: #374151;" id="backupErrorMessage"></p>
            </div>
        </div>
        <div style="border-top: 1px solid #dee2e6; padding: 15px 20px; text-align: right;">
            <button onclick="closeBackupModal()" style="background-color: #6b7280; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; font-weight: bold;">Cerrar</button>
        </div>
    </div>
</div>

<style>
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>

<!-- Scripts para la gráfica -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const ctx = document.getElementById('storageChart').getContext('2d');
        const storageChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Usado', 'Disponible'],
                datasets: [{
                    data: [{{ $usedStorage }}, {{ $availableStorage }}],
                    backgroundColor: ['#2f6f8f', '#9ed6c8'],
                    borderColor: ['#ffffff', '#ffffff'],
                    borderWidth: 6,
                    hoverOffset: 4,
                }]
            },
            options: {
                responsive: true,
                cutout: '72%',
                plugins: {
                    legend: {
                        display: false,
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((sum, value) => sum + value, 0);
                                const value = Number(context.raw || 0);
                                const percentage = total ? ((value / total) * 100).toFixed(1) : '0.0';
                                return `${context.label}: ${value.toFixed(2)} MB (${percentage}%)`;
                            }
                        }
                    },
                }
            }
        });

        // Manejar el botón de backup local (guardar en servidor)
        const backupLocalBtn = document.getElementById('backupLocalBtn');
        if (backupLocalBtn) {
            backupLocalBtn.addEventListener('click', function() {
                // Mostrar el modal
                document.getElementById('backupModal').style.display = 'block';
                
                // Mostrar loading
                document.getElementById('backupLoading').style.display = 'block';
                document.getElementById('backupSuccess').style.display = 'none';
                document.getElementById('backupError').style.display = 'none';
                
                // Hacer la petición AJAX
                fetch('{{ route('configuracion.backupDatabase') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                })
                .then(response => {
                    return response.json();
                })
                .then(data => {
                    // Si hay información de debug, mostrarla en consola
                    if (data.debug) {
                    }
                    
                    // Ocultar loading
                    document.getElementById('backupLoading').style.display = 'none';
                    
                    if (data.success) {
                        // Mostrar éxito
                        document.getElementById('backupSuccess').style.display = 'block';
                        document.getElementById('backupFilename').textContent = data.filename;
                        document.getElementById('backupSize').textContent = data.size;
                    } else {
                        // Mostrar error
                        document.getElementById('backupError').style.display = 'block';
                        document.getElementById('backupErrorMessage').textContent = data.message || 'Error desconocido';
                    }
                })
                .catch(error => {
                    // Ocultar loading
                    document.getElementById('backupLoading').style.display = 'none';
                    
                    // Mostrar error
                    document.getElementById('backupError').style.display = 'block';
                    document.getElementById('backupErrorMessage').textContent = 'Error de conexión: ' + error.message;
                });
            });
        } else {
        }

        // Manejar el botón de descarga
        const backupDownloadBtn = document.getElementById('backupDownloadBtn');
        if (backupDownloadBtn) {
            backupDownloadBtn.addEventListener('click', function() {
                // Redirigir a la ruta de descarga
                window.location.href = '{{ route('configuracion.downloadBackup') }}';
            });
        } else {
        }

        // Manejar el botón de Google Drive
        const backupGoogleDriveBtn = document.getElementById('backupGoogleDriveBtn');
        if (backupGoogleDriveBtn) {
            backupGoogleDriveBtn.addEventListener('click', function() {
                // Mostrar el modal
                document.getElementById('backupModal').style.display = 'block';
                
                // Mostrar loading
                document.getElementById('backupLoading').style.display = 'block';
                document.getElementById('backupSuccess').style.display = 'none';
                document.getElementById('backupError').style.display = 'none';
                
                // Hacer la petición AJAX
                fetch('{{ route('configuracion.uploadGoogleDrive') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                })
                .then(response => {
                    return response.json();
                })
                .then(data => {
                    // Ocultar loading
                    document.getElementById('backupLoading').style.display = 'none';
                    
                    if (data.success) {
                        // Mostrar éxito
                        document.getElementById('backupSuccess').style.display = 'block';
                        document.getElementById('backupFilename').textContent = data.filename;
                        document.getElementById('backupSize').textContent = data.size;
                    } else {
                        // Mostrar error
                        document.getElementById('backupError').style.display = 'block';
                        document.getElementById('backupErrorMessage').textContent = data.message || 'Error desconocido';
                    }
                })
                .catch(error => {
                    // Ocultar loading
                    document.getElementById('backupLoading').style.display = 'none';
                    
                    // Mostrar error
                    document.getElementById('backupError').style.display = 'block';
                    document.getElementById('backupErrorMessage').textContent = 'Error de conexión: ' + error.message;
                });
            });
        } else {
        }
    });


    // Función para cerrar los modales
    function closeBackupModal() {
        document.getElementById('backupModal').style.display = 'none';
    }

    // Cerrar modales al hacer clic fuera de ellos
    window.onclick = function(event) {
        const backupModal = document.getElementById('backupModal');
        if (event.target == backupModal) {
            backupModal.style.display = 'none';
        }
    }
</script>
@endsection

