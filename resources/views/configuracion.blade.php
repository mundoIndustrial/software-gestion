@extends('layouts.app')

@section('content')
<style>
    .config-card {
        background: #1e293b;
        border-radius: 12px;
        padding: 24px;
        margin-bottom: 24px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        border: 1px solid #334155;
    }
    
    .config-card-header {
        font-size: 18px;
        font-weight: 600;
        color: #f1f5f9;
        margin-bottom: 20px;
        padding-bottom: 12px;
        border-bottom: 2px solid #f97316;
    }
    
    .backup-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 14px 20px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 15px;
        border: none;
        cursor: pointer;
        transition: all 0.3s ease;
        width: 100%;
    }
    
    .backup-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2);
    }
    
    .backup-btn:active {
        transform: translateY(0);
    }
    
    .backup-btn-orange {
        background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
        color: white;
    }
    
    .backup-btn-blue {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        color: white;
    }
    
    .backup-btn-green {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
    }
    
    .backup-btn-primary {
        background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
        color: white;
    }
    
    .form-select-custom {
        background-color: #334155;
        color: #f1f5f9;
        border: 1px solid #475569;
        border-radius: 8px;
        padding: 10px 14px;
        font-size: 14px;
        width: 100%;
        transition: all 0.3s ease;
    }
    
    .form-select-custom:focus {
        outline: none;
        border-color: #f97316;
        box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.1);
    }
    
    .form-input-custom {
        background-color: #334155;
        color: #f1f5f9;
        border: 1px solid #475569;
        border-radius: 8px;
        padding: 10px 14px;
        font-size: 14px;
        width: 100%;
        transition: all 0.3s ease;
    }
    
    .form-input-custom:focus {
        outline: none;
        border-color: #f97316;
        box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.1);
    }
    
    .form-input-custom::placeholder {
        color: #94a3b8;
    }
    
    .form-label-custom {
        color: #cbd5e1;
        font-size: 14px;
        font-weight: 500;
        margin-bottom: 8px;
        display: block;
    }
    
    .backup-description {
        color: #94a3b8;
        font-size: 14px;
        margin-bottom: 20px;
        text-align: center;
    }
</style>

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-12" style="max-width: 1200px;">
            <div class="row g-4">
                <!-- Backup de Base de Datos -->
                <div class="col-lg-7">
                    <div class="config-card h-100">
                        <div class="config-card-header text-center">💾 Backup de Base de Datos</div>
                        <p class="backup-description">Base de datos: <strong style="color: #f97316;">{{ $currentDatabase }}</strong></p>
                        
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

                            <hr style="margin: 15px 0; border-top: 2px dashed #e5e7eb;">

                            <div class="col-12">
                                <button type="button" class="backup-btn backup-btn-purple" id="backupInsertOnlyBtn" style="background: linear-gradient(135deg, #a855f7 0%, #ec4899 100%);">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M12 19V5M5 12h14"></path>
                                    </svg>
                                    Backup (No Duplicar Datos)
                                </button>
                            </div>

                            <div class="col-12">
                                <button type="button" class="backup-btn backup-btn-teal" id="backupDownloadInsertOnlyBtn" style="background: linear-gradient(135deg, #06b6d4 0%, #10b981 100%);">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                        <polyline points="7 10 12 15 17 10"></polyline>
                                        <line x1="12" y1="15" x2="12" y2="3"></line>
                                    </svg>
                                    Descargar (No Duplicar)
                                </button>
                            </div>

                            <div class="col-12">
                                <button type="button" class="backup-btn" id="backupFlexibleBtn" style="background: linear-gradient(135deg, #f59e0b 0%, #ef4444 100%); color: white;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                        <polyline points="7 10 12 15 17 10"></polyline>
                                        <line x1="12" y1="15" x2="12" y2="3"></line>
                                    </svg>
                                    Backup Flexible (Diferente Estructura)
                                </button>
                            </div>

                            <div class="col-12">
                                <button type="button" class="backup-btn" id="backupFlexibleAdvBtn" style="background: linear-gradient(135deg, #ec4899 0%, #1e40af 100%); color: white;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm3.5-9c.83 0 1.5-.67 1.5-1.5S16.33 8 15.5 8 14 8.67 14 9.5s.67 1.5 1.5 1.5zm-7 0c.83 0 1.5-.67 1.5-1.5S9.33 8 8.5 8 7 8.67 7 9.5 7.67 11 8.5 11zm3.5 6.5c2.33 0 4.31-1.46 5.11-3.5H6.89c.8 2.04 2.78 3.5 5.11 3.5z"></path>
                                    </svg>
                                    Flexible Avanzado (Seleccionar BD Destino)
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal para seleccionar BD Destino -->
                <div id="selectDbModal" style="display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5);">
                    <div style="background-color: #fefefe; margin: 10% auto; padding: 0; border-radius: 10px; width: 90%; max-width: 400px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                        <div style="background-color: #1e40af; color: white; padding: 20px; border-radius: 10px 10px 0 0; display: flex; justify-content: space-between; align-items: center;">
                            <h5 style="margin: 0; font-size: 18px; font-weight: bold;">
                                Seleccionar Base de Datos Destino
                            </h5>
                            <button onclick="closeSelectDbModal()" style="background: none; border: none; color: white; font-size: 28px; cursor: pointer; padding: 0; line-height: 1;">&times;</button>
                        </div>
                        <div style="padding: 30px; text-align: center;">
                            <label style="display: block; margin-bottom: 15px; text-align: left;">
                                <strong>Selecciona la base de datos destino:</strong>
                                <select id="destDbSelect" style="width: 100%; padding: 10px; margin-top: 8px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px;">
                                    <option value="">-- Seleccionar base de datos --</option>
                                    @foreach($databases as $db)
                                        @if($db !== $currentDatabase)
                                            <option value="{{ $db }}">{{ $db }}</option>
                                        @endif
                                    @endforeach
                                </select>
                            </label>
                            <button onclick="downloadFlexibleAdv()" style="background-color: #1e40af; color: white; padding: 12px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 14px; font-weight: bold; margin-right: 10px;">Descargar Backup</button>
                            <button onclick="closeSelectDbModal()" style="background-color: #e5e7eb; color: #374151; padding: 12px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 14px; font-weight: bold;">Cancelar</button>
                        </div>
                    </div>
                </div>

                <!-- Gráfico de Almacenamiento -->
                <div class="col-lg-5">
                    <div class="config-card h-100">
                        <div class="config-card-header text-center"> Uso de Almacenamiento</div>
                        <div style="max-width: 350px; margin: 0 auto; padding: 20px;">
                            <canvas id="storageChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Backup -->
<div id="backupModal" style="display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5);">
    <div style="background-color: #fefefe; margin: 10% auto; padding: 0; border-radius: 10px; width: 90%; max-width: 500px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
        <div style="background-color: #f97316; color: white; padding: 20px; border-radius: 10px 10px 0 0; display: flex; justify-content: space-between; align-items: center;">
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
                <div style="border: 4px solid #f3f3f3; border-top: 4px solid #f97316; border-radius: 50%; width: 50px; height: 50px; animation: spin 1s linear infinite; margin: 0 auto;"></div>
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
                    backgroundColor: ['#f97316', '#374151'],
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
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

        // Manejar el botón de backup Insert Only (guardar en servidor)
        const backupInsertOnlyBtn = document.getElementById('backupInsertOnlyBtn');
        if (backupInsertOnlyBtn) {
            backupInsertOnlyBtn.addEventListener('click', function() {
                // Redirigir a la ruta de descarga
                window.location.href = '{{ route('configuracion.backupInsertOnly') }}';
            });
        } else {
        }

        // Manejar el botón de descarga Insert Only
        const backupDownloadInsertOnlyBtn = document.getElementById('backupDownloadInsertOnlyBtn');
        if (backupDownloadInsertOnlyBtn) {
            backupDownloadInsertOnlyBtn.addEventListener('click', function() {
                // Redirigir a la ruta de descarga
                window.location.href = '{{ route('configuracion.downloadBackupInsertOnly') }}';
            });
        } else {
        }

        // Manejar el botón de backup flexible
        const backupFlexibleBtn = document.getElementById('backupFlexibleBtn');
        if (backupFlexibleBtn) {
            backupFlexibleBtn.addEventListener('click', function() {
                // Redirigir a la ruta de descarga
                window.location.href = '{{ route('configuracion.backupFlexible') }}';
            });
        } else {
        }

        // Manejar el botón de backup flexible avanzado
        const backupFlexibleAdvBtn = document.getElementById('backupFlexibleAdvBtn');
        if (backupFlexibleAdvBtn) {
            backupFlexibleAdvBtn.addEventListener('click', function() {
                document.getElementById('selectDbModal').style.display = 'block';
            });
        } else {
        }
    });

    function downloadFlexibleAdv() {
        const destDb = document.getElementById('destDbSelect').value;
        if (!destDb) {
            alert('Por favor selecciona una base de datos destino');
            return;
        }
        closeSelectDbModal();
        window.location.href = '{{ route('configuracion.backupFlexible') }}?dest_db=' + encodeURIComponent(destDb);
    }

    function closeSelectDbModal() {
        document.getElementById('selectDbModal').style.display = 'none';
    }

    // Cerrar modal al hacer clic fuera de él
    window.onclick = function(event) {
        const selectDbModal = document.getElementById('selectDbModal');
        if (event.target == selectDbModal) {
            selectDbModal.style.display = 'none';
        }
        const backupModal = document.getElementById('backupModal');
        if (event.target == backupModal) {
            backupModal.style.display = 'none';
        }
    }


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
        const selectDbModal = document.getElementById('selectDbModal');
        if (event.target == selectDbModal) {
            selectDbModal.style.display = 'none';
        }
    }
</script>
@endsection
