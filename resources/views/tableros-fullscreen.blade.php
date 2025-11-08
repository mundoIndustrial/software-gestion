<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seguimiento de Módulos - Vista Completa</title>
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
            zoom: 0.78;
        }

        .fullscreen-container {
            max-width: 1600px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.5);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #2c3e50, #34495e);
            color: white;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 3px solid #3498db;
        }

        .header h1 {
            font-size: 24px;
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
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .close-btn:hover {
            background: #c0392b;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(231, 76, 60, 0.3);
        }

        .filter-section {
            background: white;
            padding: 20px 30px;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }

        .filter-select, .filter-input {
            background: white;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            padding: 8px 12px;
            color: #2c3e50;
            font-size: 13px;
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
            background: #6b7280;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-clear-filter:hover {
            background: #4b5563;
        }

        .table-container {
            padding: 30px;
            overflow-x: auto;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .data-table thead {
            background: linear-gradient(135deg, #34495e, #2c3e50);
            color: white;
        }

        .data-table th {
            padding: 14px 12px;
            text-align: center;
            font-weight: 600;
            font-size: 30px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: 1px solid #e0e0e0;
            color: #fff;
        }

        .data-table th.module-header {
            background: linear-gradient(135deg, #3498db, #2980b9);
            font-size: 16px;
            padding: 16px 12px;
        }

        .data-table td {
            padding: 12px;
            text-align: center;
            border: 1px solid #e0e0e0;
            font-size: 30px;
            color: #2c3e50;
        }

        .data-table tbody tr:nth-child(even) {
            background: #f8f9fa;
        }

        .data-table tbody tr:hover {
            background: #e8f4f8;
            transition: background 0.2s;
        }

        .hora-cell {
            font-weight: 600;
            background: #ecf0f1;
            color: #2c3e50;
            text-align: left;
            padding-left: 20px;
        }

        .total-row {
            background: linear-gradient(135deg, #34495e, #2c3e50) !important;
            font-weight: 700;
            font-size: 16px;
        }

        .total-row td {
            border-color: #2c3e50 !important;
            color: white !important;
            padding: 16px 12px;
        }
        
        .total-row .hora-cell {
            color: white !important;
            background: #2c3e50 !important;
        }

        /* Eficiencia colors - applied directly to td */
        .eficiencia-blue {
            background: #3498db !important;
            color: white !important;
            font-weight: 600;
        }

        .eficiencia-green {
            background: #27ae60 !important;
            color: white !important;
            font-weight: 600;
        }

        .eficiencia-orange {
            background: #f39c12 !important;
            color: white !important;
            font-weight: 600;
        }

        .eficiencia-red {
            background: #e74c3c !important;
            color: white !important;
            font-weight: 600;
        }

        .eficiencia-gray {
            background: #95a5a6 !important;
            color: white !important;
            font-weight: 600;
        }

        .legend {
            display: flex;
            justify-content: center;
            gap: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-top: 1px solid #e0e0e0;
            flex-wrap: wrap;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
            color: #2c3e50;
        }

        .legend-color {
            width: 24px;
            height: 24px;
            border-radius: 4px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        /* Responsive Design */
        @media (max-width: 1400px) {
            body {
                zoom: 0.7;
            }
            
            .fullscreen-container {
                max-width: 100%;
            }
        }

        @media (max-width: 1200px) {
            body {
                zoom: 0.65;
                padding: 15px;
            }

            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .header h1 {
                font-size: 20px;
            }

            .filter-section {
                flex-direction: column;
                align-items: stretch;
                gap: 10px;
            }

            .filter-select, .filter-input {
                width: 100%;
            }

            #rangeInputs {
                flex-direction: column;
                gap: 10px;
            }
        }

        @media (max-width: 992px) {
            body {
                zoom: 0.6;
                padding: 10px;
            }

            .table-container {
                padding: 15px;
            }

            .data-table th,
            .data-table td {
                padding: 8px 6px;
                font-size: 11px;
            }

            .header h1 {
                font-size: 18px;
            }
        }

        @media (max-width: 768px) {
            body {
                zoom: 0.5;
                padding: 10px;
            }

            .header {
                padding: 15px 20px;
            }

            .header h1 {
                font-size: 16px;
            }

            .header-info {
                flex-direction: column;
                gap: 5px;
                font-size: 12px;
            }

            .close-btn {
                padding: 8px 16px;
                font-size: 12px;
            }

            .filter-section {
                padding: 15px 20px;
            }

            .table-container {
                padding: 10px;
                overflow-x: auto;
            }

            .data-table {
                min-width: 800px;
            }

            .legend {
                flex-direction: column;
                gap: 10px;
            }
        }

        @media (max-width: 576px) {
            body {
                zoom: 0.45;
                padding: 5px;
            }

            .fullscreen-container {
                border-radius: 4px;
            }

            .header {
                padding: 10px 15px;
            }

            .header h1 {
                font-size: 14px;
            }

            .filter-section {
                padding: 10px 15px;
            }

            .btn-filter,
            .btn-clear-filter {
                padding: 6px 12px;
                font-size: 11px;
            }
        }

        /* Modal Styles */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
            z-index: 9999;
            align-items: center;
            justify-content: center;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 12px;
            padding: 30px;
            max-width: 400px;
            width: 90%;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            animation: modalSlideIn 0.3s ease-out;
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-20px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .modal-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
        }

        .modal-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, #f39c12, #e67e22);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .modal-icon svg {
            width: 28px;
            height: 28px;
            color: white;
        }

        .modal-title {
            font-size: 20px;
            font-weight: 600;
            color: #2c3e50;
            margin: 0;
        }

        .modal-message {
            color: #555;
            font-size: 15px;
            line-height: 1.6;
            margin-bottom: 25px;
        }

        .modal-button {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s;
        }

        .modal-button:hover {
            background: linear-gradient(135deg, #2980b9, #21618c);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(52, 152, 219, 0.3);
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
            .filter-section,
            .modal-overlay {
                display: none;
            }
            
            .data-table th,
            .data-table td {
                border: 1px solid #333 !important;
            }
        }
    </style>
</head>
<body>
    <div class="fullscreen-container">
        <div class="header">
            <div>
                <h1>📊 Seguimiento de Módulos - {{ ucfirst($section) }}</h1>
                <div class="header-info">
                    @if(request('filter_type'))
                        <span>
                            @if(request('filter_type') === 'range')
                                📅 {{ \Carbon\Carbon::parse(request('start_date'))->format('d/m/Y') }} - {{ \Carbon\Carbon::parse(request('end_date'))->format('d/m/Y') }}
                            @elseif(request('filter_type') === 'day')
                                📅 {{ \Carbon\Carbon::parse(request('specific_date'))->format('d/m/Y') }}
                            @elseif(request('filter_type') === 'month')
                                📅 {{ \Carbon\Carbon::parse(request('month') . '-01')->format('m/Y') }}
                            @elseif(request('filter_type') === 'specific')
                                📅 Fechas específicas
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

        <div class="table-container">
            @php
                $modulosDisponibles = $seguimiento['modulosDisponibles'];
                $dataPorHora = $seguimiento['dataPorHora'];
                $totales = $seguimiento['totales'];
            @endphp

            <table class="data-table">
                <thead>
                    <tr>
                        <th rowspan="2" style="min-width: 100px;">HORA</th>
                        @foreach($modulosDisponibles as $index => $modulo)
                            <th colspan="3" class="module-header">{{ $modulo }}</th>
                        @endforeach
                    </tr>
                    <tr>
                        @foreach($modulosDisponibles as $modulo)
                            <th>Prendas</th>
                            <th>Meta</th>
                            <th>Eficiencia</th>
                        @endforeach
                    </tr>
                </thead>

                <tbody>
                    @php
                        $horasOrdenadas = array_keys($dataPorHora);
                        sort($horasOrdenadas);
                        $horasOrdenadas = array_slice($horasOrdenadas, 0, 12);
                    @endphp
                    @foreach($horasOrdenadas as $horaKey)
                        @php
                            $horaData = $dataPorHora[$horaKey] ?? ['modulos' => []];
                        @endphp
                        <tr>
                            <td class="hora-cell">{{ $horaKey }}</td>
                            @foreach($modulosDisponibles as $modulo)
                                @php
                                    $modData = $horaData['modulos'][$modulo] ?? ['meta'=>0,'eficiencia'=>0,'prendas'=>0];
                                    $eficiencia = $modData['eficiencia'];
                                    $eficienciaClass = $modData['prendas'] > 0
                                        ? (($eficiencia > 1.00) ? 'eficiencia-blue'
                                        : (($eficiencia >= 0.80) ? 'eficiencia-green'
                                        : (($eficiencia >= 0.70) ? 'eficiencia-orange' : 'eficiencia-red')))
                                        : 'eficiencia-gray';
                                @endphp
                                <td>{{ number_format($modData['prendas'], 0) }}</td>
                                <td>{{ number_format($modData['meta'], 2) }}</td>
                                <td class="{{ $eficienciaClass }}">
                                    {{ $modData['prendas'] > 0 ? round($modData['eficiencia'] * 100) . '%' : '0%' }}
                                </td>
                            @endforeach
                        </tr>
                    @endforeach

                    <tr class="total-row">
                        <td class="hora-cell">SUMA TOTAL</td>
                        @foreach($modulosDisponibles as $modulo)
                            @php
                                $modTotal = $totales['modulos'][$modulo] ?? ['meta'=>0,'eficiencia'=>0,'prendas'=>0];
                                $eficiencia = $modTotal['eficiencia'];
                                $eficienciaClass = $modTotal['prendas'] > 0
                                    ? (($eficiencia > 1.00) ? 'eficiencia-blue'
                                    : (($eficiencia >= 0.80) ? 'eficiencia-green'
                                    : (($eficiencia >= 0.70) ? 'eficiencia-orange' : 'eficiencia-red')))
                                    : 'eficiencia-gray';
                            @endphp
                            <td>{{ number_format($modTotal['prendas'], 0) }}</td>
                            <td>{{ number_format($modTotal['meta'], 2) }}</td>
                            <td class="{{ $eficienciaClass }}">
                                {{ $modTotal['prendas'] > 0 ? round($modTotal['eficiencia'] * 100) . '%' : '0%' }}
                            </td>
                        @endforeach
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="legend">
            <div class="legend-item">
                <div class="legend-color eficiencia-blue"></div>
                <span>≥ 100% Eficiencia</span>
            </div>
            <div class="legend-item">
                <div class="legend-color eficiencia-green"></div>
                <span>80-99% Eficiencia</span>
            </div>
            <div class="legend-item">
                <div class="legend-color eficiencia-orange"></div>
                <span>70-79% Eficiencia</span>
            </div>
            <div class="legend-item">
                <div class="legend-color eficiencia-red"></div>
                <span>&lt; 70% Eficiencia</span>
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

        // Mostrar modal de alerta
        function showModal(message) {
            document.getElementById('modalMessage').textContent = message;
            document.getElementById('alertModal').classList.add('active');
        }

        // Cerrar modal
        function closeModal() {
            document.getElementById('alertModal').classList.remove('active');
        }

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
                    showModal('Por favor selecciona ambas fechas para el rango');
                    return;
                }
                url.searchParams.set('start_date', startDate);
                url.searchParams.set('end_date', endDate);
            } else if (filterType === 'day') {
                const specificDate = document.getElementById('specificDate').value;
                if (!specificDate) {
                    showModal('Por favor selecciona una fecha específica');
                    return;
                }
                url.searchParams.set('specific_date', specificDate);
            } else if (filterType === 'month') {
                const month = document.getElementById('month').value;
                if (!month) {
                    showModal('Por favor selecciona un mes');
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
    function initializeFullscreenRealtime() {
        console.log('=== FULLSCREEN - Inicializando tiempo real ===');
        
        if (!window.Echo) {
            console.log('Echo no disponible, reintentando...');
            setTimeout(initializeFullscreenRealtime, 500);
            return;
        }

        console.log('✅ Echo disponible, suscribiendo a canales...');

        // Determinar la sección actual desde la URL
        const urlParams = new URLSearchParams(window.location.search);
        const currentSection = urlParams.get('section') || '{{ $section }}';
        
        console.log('Sección actual en fullscreen:', currentSection);

        // Canal de Producción
        window.Echo.channel('produccion').listen('ProduccionRecordCreated', (e) => {
            console.log('🎉 Evento ProduccionRecordCreated recibido en fullscreen', e);
            
            // Solo recargar si estamos en la sección de producción
            if (currentSection === 'produccion') {
                // Esta es una tabla agregada, siempre recargar
                if (e.registro && e.registro.deleted) {
                    console.log('🗑️ Registro eliminado ID:', e.registro.id, '- Recargando...');
                } else {
                    console.log('🔄 Registro creado/actualizado - Recargando...');
                }
                location.reload();
            }
        });

        // Canal de Polos
        window.Echo.channel('polo').listen('PoloRecordCreated', (e) => {
            console.log('🎉 Evento PoloRecordCreated recibido en fullscreen', e);
            
            // Solo recargar si estamos en la sección de polos
            if (currentSection === 'polos') {
                // Esta es una tabla agregada, siempre recargar
                if (e.registro && e.registro.deleted) {
                    console.log('🗑️ Registro eliminado ID:', e.registro.id, '- Recargando...');
                } else {
                    console.log('🔄 Registro creado/actualizado - Recargando...');
                }
                location.reload();
            }
        });

        // Canal de Corte
        window.Echo.channel('corte').listen('CorteRecordCreated', (e) => {
            console.log('🎉 Evento CorteRecordCreated recibido en fullscreen', e);
            
            // Solo recargar si estamos en la sección de corte
            if (currentSection === 'corte') {
                // Esta es una tabla agregada, siempre recargar
                if (e.registro && e.registro.deleted) {
                    console.log('🗑️ Registro eliminado ID:', e.registro.id, '- Recargando...');
                } else {
                    console.log('🔄 Registro creado/actualizado - Recargando...');
                }
                location.reload();
            }
        });

        console.log('✅ Listeners configurados en fullscreen para todas las secciones');
    }

    // Inicializar cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(initializeFullscreenRealtime, 1000);
        });
    } else {
        setTimeout(initializeFullscreenRealtime, 1000);
    }
    </script>

    <!-- Modal de Alerta -->
    <div id="alertModal" class="modal-overlay" onclick="if(event.target === this) closeModal()">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <h3 class="modal-title">Atención</h3>
            </div>
            <p id="modalMessage" class="modal-message"></p>
            <button class="modal-button" onclick="closeModal()">Entendido</button>
        </div>
    </div>
</body>
</html>
