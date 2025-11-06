@extends('layouts.app')

@section('content')
<style>
    #backupBtn:hover {
        background-color: #ea580c !important;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(249, 115, 22, 0.3);
        transition: all 0.3s ease;
    }
    
    #backupBtn:active {
        transform: translateY(0);
    }
</style>

<div class="container py-4">
   

    <div class="row">
        <!-- Gráfica de almacenamiento -->
        <div class="col-md-6 mb-4">
            <div class="card" style="margin-left: 20px;">
                <div class="card-header text-center">Uso de Almacenamiento</div>
                <div class="card-body">
                    <canvas id="storageChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Selección de base de datos activa -->
        <div class="col-md-6 mb-4">
            <div class="card" style="margin-left: 20px;">
                <div class="card-header text-center">Seleccionar Base de Datos Activa</div>
                <div class="card-body">
                    <form action="{{ route('configuracion.selectDatabase') }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label for="database">Base de Datos</label>
                            <select name="database" id="database" class="form-control" style="color: #374151;">
                                @foreach($databases as $database)
                                    <option value="{{ $database }}" {{ $currentDatabase === $database ? 'selected' : '' }}>{{ $database }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary mt-2 w-100">Seleccionar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Nueva sección para Backup de Base de Datos -->
    <div class="row">
        <div class="col-md-12 mb-4">
            <div class="card" style="margin-left: 20px;">
                <div class="card-header text-center" style="background-color: #f97316; color: white;">Backup de Base de Datos</div>
                <div class="card-body text-center">
                    <p class="mb-3">Crear una copia de seguridad completa de la base de datos actual: <strong>{{ $currentDatabase }}</strong></p>
                    <button type="button" class="btn w-100" id="backupBtn" style="background-color: #f97316; color: white; border: none; padding: 10px; font-weight: bold;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: inline-block; vertical-align: middle; margin-right: 8px;">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="7 10 12 15 17 10"></polyline>
                            <line x1="12" y1="15" x2="12" y2="3"></line>
                        </svg>
                        Crear Backup
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Nueva sección para crear base de datos y migrar usuarios -->
        <div class="col-md-12 mb-4">
            <div class="card" style="margin-left: 20px;">
                <div class="card-header text-center">Crear Nueva Base de Datos</div>
                <div class="card-body">
                    <form action="{{ route('configuracion.createDatabase') }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label for="model">Modelo</label>
                            <select name="model" id="model" class="form-control" style="color: #374151;">
                                <option value="">Seleccione un modelo</option>
                                @foreach($models as $model)
                                    <option value="{{ $model }}">{{ $model }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group mt-3">
                            <label for="databaseName">Nombre de la Nueva Base de Datos</label>
                            <input type="text" name="databaseName" id="databaseName" class="form-control" placeholder="Ingrese el nombre">
                        </div>

                        <button type="submit" class="btn btn-success mt-3 w-100">Crear Base de Datos</button>
                    </form>

                    <hr>

                    <form action="{{ route('configuracion.migrateUsers') }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label for="targetDatabase">Migrar Usuarios a Base de Datos</label>
                            <select name="targetDatabase" id="targetDatabase" class="form-control" style="color: #374151;">
                                <option value="">Seleccione una base de datos</option>
                                @foreach($databases as $database)
                                    <option value="{{ $database }}">{{ $database }}</option>
                                @endforeach
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary mt-3 w-100">Migrar Usuarios</button>
                    </form>
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
                <p style="margin-top: 20px; color: #374151; font-size: 16px;">Creando backup, por favor espere...</p>
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

        // Manejar el botón de backup
        const backupBtn = document.getElementById('backupBtn');
        if (backupBtn) {
            backupBtn.addEventListener('click', function() {
                console.log('Botón de backup clickeado');
                
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
                    console.log('Respuesta recibida:', response);
                    return response.json();
                })
                .then(data => {
                    console.log('Datos:', data);
                    
                    // Si hay información de debug, mostrarla en consola
                    if (data.debug) {
                        console.log('Debug info:', data.debug);
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
                    console.error('Error:', error);
                    // Ocultar loading
                    document.getElementById('backupLoading').style.display = 'none';
                    
                    // Mostrar error
                    document.getElementById('backupError').style.display = 'block';
                    document.getElementById('backupErrorMessage').textContent = 'Error de conexión: ' + error.message;
                });
            });
        } else {
            console.error('No se encontró el botón de backup');
        }
    });

    // Función para cerrar el modal
    function closeBackupModal() {
        document.getElementById('backupModal').style.display = 'none';
    }

    // Cerrar modal al hacer clic fuera de él
    window.onclick = function(event) {
        const modal = document.getElementById('backupModal');
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }
</script>
@endsection