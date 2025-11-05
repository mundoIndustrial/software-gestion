@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="{{ asset('css/entregas styles/entregas.css') }}">

<div class="ep-container">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <header class="ep-header">
            <h1>{{ $config['titulo'] }}</h1>
            <p>Monitorea y gestiona las entregas de costura y corte en tiempo real</p>
            <div class="ep-badge">
                <i class="fas fa-calendar-alt"></i> {{ $fecha }}
            </div>
        </header>

        <!-- Filtro de Fecha -->
        <div class="ep-card mb-8">
            <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                <div>
                    <h3 class="card-title">Filtrar por Fecha</h3>
                    <p class="stat-label">Selecciona la fecha para ver las entregas correspondientes</p>
                </div>
                <div class="flex items-center gap-4">
                    <input type="date" id="fechaFilter" class="filter-input" value="{{ $fecha }}">
                    <button id="filtrarBtn" class="btn-primary">Filtrar</button>
                    <button id="registrarEntregaBtn" class="btn-secondary">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" class="w-4 h-4">
                            <path d="M12 4v16m8-8H4" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                        Registrar
                    </button>
                </div>
            </div>
        </div>

        <!-- Sección Costura -->
        <section class="mb-12">
            <h2 class="section-title">
                <i class="fas fa-cut"></i>
                {{ $config['seccionCostura'] }}
            </h2>

            <!-- Estadísticas -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div class="stat-card">
                    <div class="stat-icon">📦</div>
                    <div class="stat-number" id="costura-total">0</div>
                    <div class="stat-label">Total Prendas</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">👕</div>
                    <div class="stat-number" id="costura-prendas">0</div>
                    <div class="stat-label">Prendas Diferentes</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">👥</div>
                    <div class="stat-number" id="costura-costureros">0</div>
                    <div class="stat-label">Costureros Activos</div>
                </div>
            </div>

            <!-- Tabla -->
            <div class="ep-card mb-6">
                <h3 class="card-title">Registros de Entregas</h3>
                <div class="table-scroll-container">
                    <table class="modern-table" id="costura-table">
                            <thead>
                                <tr>
                                    <th>Pedido</th>
                                    <th>Cliente</th>
                                    <th>Prenda</th>
                                    <th>Cantidad</th>
                                    <th>Costurero</th>
                                </tr>
                            </thead>
                            <tbody id="costura-tbody">
                                @foreach($costura as $item)
                                <tr>
                                    <td>{{ $item->pedido }}</td>
                                    <td>{{ $item->cliente }}</td>
                                    <td>{{ $item->prenda }}</td>
                                    <td><span class="table-badge">{{ $item->cantidad_entregada }}</span></td>
                                    <td>{{ $item->costurero }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                </div>
            </div>

            <!-- Gráfico -->
            <div class="ep-card">
                <h3 class="card-title">Entregas por Costurero</h3>
                <div class="chart-container">
                    <canvas id="costura-chart"></canvas>
                </div>
            </div>
        </section>

        <!-- Sección Corte -->
        <section>
            <h2 class="section-title">
                <i class="fas fa-scissors"></i>
                {{ $config['seccionCorte'] }}
            </h2>

            <!-- Estadísticas -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div class="stat-card">
                    <div class="stat-icon">✂️</div>
                    <div class="stat-number" id="corte-total">0</div>
                    <div class="stat-label">Total Piezas</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">🏷️</div>
                    <div class="stat-number" id="corte-etiqueteadas">0</div>
                    <div class="stat-label">Piezas etiqueteadas</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">🤝</div>
                    <div class="stat-number" id="corte-pares">0</div>
                    <div class="stat-label">Pares Cortador-Etiquetador</div>
                </div>
            </div>

            <!-- Tabla -->
            <div class="ep-card mb-6">
                <h3 class="card-title">Registros de Entregas</h3>
                <div class="table-scroll-container">
                    <table class="modern-table" id="corte-table">
                            <thead>
                                <tr>
                                    <th>Pedido</th>
                                    <th>Cortador</th>
                                    <th>Piezas</th>
                                    <th>etiqueteadas</th>
                                    <th>Etiquetador</th>
                                </tr>
                            </thead>
                            <tbody id="corte-tbody">
                                @foreach($corte as $item)
                                <tr>
                                    <td>{{ $item->pedido }}</td>
                                    <td>{{ $item->cortador }}</td>
                                    <td><span class="table-badge">{{ $item->piezas }}</span></td>
                                    <td><span class="table-badge">{{ $item->etiqueteadas }}</span></td>
                                    <td>{{ $item->etiquetador }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                </div>
            </div>

            <!-- Gráfico -->
            <div class="ep-card">
                <h3 class="card-title">Piezas por Cortador-Etiquetador</h3>
                <div class="chart-container">
                    <canvas id="corte-chart"></canvas>
                </div>
            </div>
        </section>
    </div>
</div>

<!-- Modal Component -->
<x-entrega-form-modal />

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="{{ asset('js/entregas js/entregas.js') }}"></script>
<script>
    // Inicializar el módulo de entregas con el tipo
    initEntregas('{{ $tipo }}');
</script>
@endsection
