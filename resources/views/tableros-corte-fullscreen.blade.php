<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Corte - Vista Completa</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            color: #333;
            padding: 20px;
            zoom: 0.76;
        }

        .fullscreen-container {
            max-width: 1500px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #2c3e50, #34495e);
            color: white;
            padding: 20px 28px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 3px solid #3498db;
        }

        .header h1 {
            font-size: 20px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .header-info {
            display: flex;
            gap: 20px;
            align-items: center;
            font-size: 14px;
            color: #ecf0f1;
        }

        .close-btn {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .close-btn:hover {
            background: #c0392b;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(231, 76, 60, 0.3);
        }

        .filter-section {
            background: #f8f9fa;
            padding: 18px 28px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }

        .filter-select, .filter-input {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 8px 12px;
            color: #495057;
            font-size: 13px;
            transition: all 0.2s;
        }

        .filter-select:focus, .filter-input:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        .btn-filter {
            background: #3498db;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-filter:hover {
            background: #2980b9;
            transform: translateY(-1px);
        }

        .btn-clear-filter {
            background: white;
            color: #6c757d;
            border: 1px solid #dee2e6;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-clear-filter:hover {
            border-color: #6c757d;
            background: #f8f9fa;
            transform: translateY(-1px);
        }

        .tables-container {
            padding: 24px;
            display: flex;
            gap: 16px;
            justify-content: space-between;
        }

        .table-section {
            background: white;
            border-radius: 8px;
            padding: 0;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            border: 1px solid #e9ecef;
            flex: 1;
            min-width: 0;
            overflow: hidden;
        }

        .table-title {
            font-size: 13px;
            font-weight: 700;
            color: white;
            background: linear-gradient(135deg, #3498db, #2980b9);
            margin: 0;
            padding: 12px 16px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        .data-table thead {
            background: linear-gradient(135deg, #34495e, #2c3e50);
            color: white;
        }

        .data-table th {
            padding: 10px 12px;
            text-align: center;
            font-weight: 600;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #dee2e6;
        }

        .data-table td {
            padding: 10px 12px;
            text-align: right;
            border-bottom: 1px solid #f1f3f5;
            font-size: 12px;
            color: #495057;
        }

        .data-table tbody tr:nth-child(even) {
            background: #f8f9fa;
        }

        .data-table tbody tr:hover {
            background: #e9ecef;
            transition: background 0.2s;
        }

        .name-cell {
            font-weight: 600;
            text-align: left;
            padding-left: 12px;
            color: #212529 !important;
        }

        .total-row {
            background: linear-gradient(135deg, #34495e, #2c3e50) !important;
            font-weight: 700;
            font-size: 12px;
        }

        .total-row td {
            border: none !important;
            color: white !important;
            padding: 12px;
            font-weight: 700;
        }

        .total-row .name-cell {
            background: transparent !important;
            color: white !important;
        }

        /* Eficiencia colors */
        .eficiencia-cell {
            font-weight: 600;
        }

        .eficiencia-blue {
            background: #3498db !important;
            color: white !important;
        }

        .eficiencia-green {
            background: #27ae60 !important;
            color: white !important;
        }

        .eficiencia-orange {
            background: #f39c12 !important;
            color: white !important;
        }

        .eficiencia-red {
            background: #e74c3c !important;
            color: white !important;
        }

        .eficiencia-gray {
            background: #95a5a6 !important;
            color: white !important;
        }

        @media (max-width: 1200px) {
            .tables-container {
                flex-direction: column;
            }
            
            .table-section {
                width: 100%;
            }
        }

        @media print {
            body {
                zoom: 1;
            }

            .header {
                background: #2c3e50 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            .close-btn,
            .filter-section {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="fullscreen-container">
        <div class="header">
            <div>
                <h1>📊 Dashboard Corte - Vista Completa</h1>
                <div class="header-info">
                    @if(request('filter_type'))
                        <span>
                            @if(request('filter_type') === 'range')
                                📅 {{ \Carbon\Carbon::parse(request('start_date'))->format('d/m/Y') }} - {{ \Carbon\Carbon::parse(request('end_date'))->format('d/m/Y') }}
                            @elseif(request('filter_type') === 'day')
                                📅 {{ \Carbon\Carbon::parse(request('specific_date'))->format('d/m/Y') }}
                            @elseif(request('filter_type') === 'month')
                                📅 {{ \Carbon\Carbon::parse(request('month') . '-01')->format('m/Y') }}
                            @endif
                        </span>
                    @else
                        <span>📅 Todos los registros</span>
                    @endif
                </div>
            </div>
            <button class="close-btn" onclick="window.history.back()">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
                Volver
            </button>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <select id="filterType" class="filter-select" onchange="toggleFilterInputs()">
                <option value="range" {{ request('filter_type', 'range') === 'range' ? 'selected' : '' }}>Rango de fechas</option>
                <option value="day" {{ request('filter_type') === 'day' ? 'selected' : '' }}>Día específico</option>
                <option value="month" {{ request('filter_type') === 'month' ? 'selected' : '' }}>Mes completo</option>
            </select>

            <div id="rangeInputs" style="display: flex; gap: 10px;">
                <input type="date" id="startDate" class="filter-input" value="{{ request('start_date', '') }}" placeholder="Fecha inicio">
                <input type="date" id="endDate" class="filter-input" value="{{ request('end_date', '') }}" placeholder="Fecha fin">
            </div>

            <div id="dayInput" style="display: none;">
                <input type="date" id="specificDate" class="filter-input" value="{{ request('specific_date', '') }}">
            </div>

            <div id="monthInput" style="display: none;">
                <input type="month" id="month" class="filter-input" value="{{ request('month', '') }}">
            </div>

            <button class="btn-filter" onclick="applyFilter()">Aplicar Filtro</button>
            <button class="btn-clear-filter" onclick="clearFilter()">Limpiar</button>
        </div>

        <div class="tables-container">
            <!-- Tabla de Producción por Horas -->
            <div class="table-section">
                <h2 class="table-title">📅 Producción por Horas</h2>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Hora</th>
                            <th>Cantidad</th>
                            <th>Meta</th>
                            <th>Eficiencia</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $totalCantidadHoras = 0;
                            $totalMetaHoras = 0;
                        @endphp
                        @foreach($horasData as $row)
                            @php
                                $totalCantidadHoras += $row['cantidad'];
                                $totalMetaHoras += $row['meta'];
                                $eficiencia = $row['eficiencia'];
                                $eficienciaClass = $eficiencia < 70 ? 'eficiencia-red' 
                                    : ($eficiencia >= 70 && $eficiencia < 80 ? 'eficiencia-orange' 
                                    : ($eficiencia >= 80 && $eficiencia < 100 ? 'eficiencia-green' 
                                    : ($eficiencia >= 100 ? 'eficiencia-blue' : 'eficiencia-gray')));
                            @endphp
                            <tr>
                                <td class="name-cell">{{ $row['hora'] }}</td>
                                <td>{{ number_format($row['cantidad'], 0) }}</td>
                                <td>{{ number_format($row['meta'], 0) }}</td>
                                <td class="eficiencia-cell {{ $eficienciaClass }}">
                                    {{ $row['eficiencia'] > 0 ? number_format($row['eficiencia'], 1) . '%' : '-' }}
                                </td>
                            </tr>
                        @endforeach
                        <tr class="total-row">
                            <td class="name-cell">Suma total</td>
                            <td>{{ number_format($totalCantidadHoras, 0) }}</td>
                            <td>{{ number_format($totalMetaHoras, 0) }}</td>
                            @php
                                $eficienciaTotal = $totalMetaHoras > 0 ? ($totalCantidadHoras / $totalMetaHoras) * 100 : 0;
                                $eficienciaClass = $eficienciaTotal < 70 ? 'eficiencia-red' 
                                    : ($eficienciaTotal >= 70 && $eficienciaTotal < 80 ? 'eficiencia-orange' 
                                    : ($eficienciaTotal >= 80 && $eficienciaTotal < 100 ? 'eficiencia-green' 
                                    : ($eficienciaTotal >= 100 ? 'eficiencia-blue' : 'eficiencia-gray')));
                            @endphp
                            <td class="eficiencia-cell {{ $eficienciaClass }}">
                                {{ number_format($eficienciaTotal, 1) }}%
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Tabla de Producción por Operarios -->
            <div class="table-section">
                <h2 class="table-title">👷 Producción por Operarios</h2>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Operario</th>
                            <th>Cantidad</th>
                            <th>Meta</th>
                            <th>Eficiencia</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $totalCantidadOperarios = 0;
                            $totalMetaOperarios = 0;
                        @endphp
                        @foreach($operariosData as $row)
                            @php
                                $totalCantidadOperarios += $row['cantidad'];
                                $totalMetaOperarios += $row['meta'];
                                $eficiencia = $row['eficiencia'];
                                $eficienciaClass = $eficiencia < 70 ? 'eficiencia-red' 
                                    : ($eficiencia >= 70 && $eficiencia < 80 ? 'eficiencia-orange' 
                                    : ($eficiencia >= 80 && $eficiencia < 100 ? 'eficiencia-green' 
                                    : ($eficiencia >= 100 ? 'eficiencia-blue' : 'eficiencia-gray')));
                            @endphp
                            <tr>
                                <td class="name-cell">{{ $row['operario'] }}</td>
                                <td>{{ number_format($row['cantidad'], 0) }}</td>
                                <td>{{ number_format($row['meta'], 0) }}</td>
                                <td class="eficiencia-cell {{ $eficienciaClass }}">
                                    {{ $row['eficiencia'] > 0 ? number_format($row['eficiencia'], 1) . '%' : '-' }}
                                </td>
                            </tr>
                        @endforeach
                        <tr class="total-row">
                            <td class="name-cell">Suma total</td>
                            <td>{{ number_format($totalCantidadOperarios, 0) }}</td>
                            <td>{{ number_format($totalMetaOperarios, 0) }}</td>
                            @php
                                $eficienciaTotal = $totalMetaOperarios > 0 ? ($totalCantidadOperarios / $totalMetaOperarios) * 100 : 0;
                                $eficienciaClass = $eficienciaTotal < 70 ? 'eficiencia-red' 
                                    : ($eficienciaTotal >= 70 && $eficienciaTotal < 80 ? 'eficiencia-orange' 
                                    : ($eficienciaTotal >= 80 && $eficienciaTotal < 100 ? 'eficiencia-green' 
                                    : ($eficienciaTotal >= 100 ? 'eficiencia-blue' : 'eficiencia-gray')));
                            @endphp
                            <td class="eficiencia-cell {{ $eficienciaClass }}">
                                {{ number_format($eficienciaTotal, 1) }}%
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Permitir volver con ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                window.history.back();
            }
        });

        // Permitir imprimir con Ctrl+P
        document.addEventListener('keydown', (e) => {
            if (e.ctrlKey && e.key === 'p') {
                e.preventDefault();
                window.print();
            }
        });

        // Toggle filter inputs based on filter type
        function toggleFilterInputs() {
            const filterType = document.getElementById('filterType').value;
            document.getElementById('rangeInputs').style.display = filterType === 'range' ? 'flex' : 'none';
            document.getElementById('dayInput').style.display = filterType === 'day' ? 'block' : 'none';
            document.getElementById('monthInput').style.display = filterType === 'month' ? 'block' : 'none';
        }

        // Apply filter
        function applyFilter() {
            const filterType = document.getElementById('filterType').value;
            const url = new URL(window.location);
            
            // Clear previous filter params
            url.searchParams.delete('start_date');
            url.searchParams.delete('end_date');
            url.searchParams.delete('specific_date');
            url.searchParams.delete('month');
            
            url.searchParams.set('filter_type', filterType);

            if (filterType === 'range') {
                const startDate = document.getElementById('startDate').value;
                const endDate = document.getElementById('endDate').value;
                if (!startDate || !endDate) {
                    alert('Por favor selecciona ambas fechas');
                    return;
                }
                url.searchParams.set('start_date', startDate);
                url.searchParams.set('end_date', endDate);
            } else if (filterType === 'day') {
                const specificDate = document.getElementById('specificDate').value;
                if (!specificDate) {
                    alert('Por favor selecciona una fecha');
                    return;
                }
                url.searchParams.set('specific_date', specificDate);
            } else if (filterType === 'month') {
                const month = document.getElementById('month').value;
                if (!month) {
                    alert('Por favor selecciona un mes');
                    return;
                }
                url.searchParams.set('month', month);
            }

            window.location.href = url.toString();
        }

        // Clear filter
        function clearFilter() {
            const url = new URL(window.location);
            url.searchParams.delete('filter_type');
            url.searchParams.delete('start_date');
            url.searchParams.delete('end_date');
            url.searchParams.delete('specific_date');
            url.searchParams.delete('month');
            window.location.href = url.toString();
        }

        // Initialize filter inputs on page load
        document.addEventListener('DOMContentLoaded', () => {
            toggleFilterInputs();
        });
    </script>

    @vite(['resources/js/app.js'])
    
    <script>
    // Esperar a que Echo esté disponible
    function initializeCorteFullscreenRealtime() {
        console.log('=== CORTE FULLSCREEN - Inicializando tiempo real ===');
        
        if (!window.Echo) {
            console.log('Echo no disponible, reintentando...');
            setTimeout(initializeCorteFullscreenRealtime, 500);
            return;
        }

        console.log('✅ Echo disponible, suscribiendo al canal de corte...');

        // Canal de Corte
        window.Echo.channel('corte').listen('CorteRecordCreated', (e) => {
            console.log('🎉 Evento CorteRecordCreated recibido en fullscreen', e);
            
            // Si es eliminación, solo eliminar la fila
            if (e.registro && e.registro.deleted) {
                console.log('🗑️ Eliminando registro ID:', e.registro.id);
                const row = document.querySelector(`tr[data-id="${e.registro.id}"]`);
                if (row) {
                    row.style.transition = 'opacity 0.3s ease';
                    row.style.opacity = '0';
                    setTimeout(() => row.remove(), 300);
                }
            } else {
                // Para crear/actualizar, recargar la página
                location.reload();
            }
        });

        console.log('✅ Listener configurado en fullscreen de corte');
    }

    // Inicializar cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(initializeCorteFullscreenRealtime, 1000);
        });
    } else {
        setTimeout(initializeCorteFullscreenRealtime, 1000);
    }
    </script>
</body>
</html>
