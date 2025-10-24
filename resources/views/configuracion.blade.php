@extends('layouts.app')

@section('content')
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
    });
</script>
@endsection