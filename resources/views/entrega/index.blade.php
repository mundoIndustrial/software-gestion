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
                                    <th>Talla</th>
                                    <th>Cantidad</th>
                                    <th>Costurero</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="costura-tbody">
                                @foreach($costura as $item)
                                <tr data-id="{{ $item->id }}" data-subtipo="costura">
                                    <td class="editable" data-field="pedido">{{ $item->pedido }}</td>
                                    <td class="editable" data-field="cliente">{{ $item->cliente }}</td>
                                    <td class="editable" data-field="prenda">{{ $item->prenda }}</td>
                                    <td class="editable" data-field="talla">{{ $item->talla }}</td>
                                    <td class="editable" data-field="cantidad_entregada"><span class="table-badge">{{ $item->cantidad_entregada }}</span></td>
                                    <td class="editable" data-field="costurero">{{ $item->costurero }}</td>
                                    <td>
                                        <button class="btn-delete" onclick="deleteEntrega({{ $item->id }}, 'costura')" title="Eliminar">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" style="width: 16px; height: 16px;">
                                                <path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                        </button>
                                    </td>
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
                                    <th>Cantidad Prendas</th>
                                    <th>Piezas</th>
                                    <th>Pasadas</th>
                                    <th>Etiqueteadas</th>
                                    <th>Etiquetador</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="corte-tbody">
                                @foreach($corte as $item)
                                <tr data-id="{{ $item->id }}" data-subtipo="corte">
                                    <td class="editable" data-field="pedido">{{ $item->pedido }}</td>
                                    <td class="editable" data-field="cortador">{{ $item->cortador }}</td>
                                    <td class="editable" data-field="cantidad_prendas"><span class="table-badge">{{ $item->cantidad_prendas }}</span></td>
                                    <td class="editable" data-field="piezas"><span class="table-badge">{{ $item->piezas }}</span></td>
                                    <td class="editable" data-field="pasadas"><span class="table-badge">{{ $item->pasadas }}</span></td>
                                    <td><span class="table-badge">{{ $item->etiqueteadas }}</span></td>
                                    <td class="editable" data-field="etiquetador">{{ $item->etiquetador }}</td>
                                    <td>
                                        <button class="btn-delete" onclick="deleteEntrega({{ $item->id }}, 'corte')" title="Eliminar">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" style="width: 16px; height: 16px;">
                                                <path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                        </button>
                                    </td>
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
