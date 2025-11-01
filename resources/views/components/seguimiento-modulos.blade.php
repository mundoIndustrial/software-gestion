@props(['section' => 'produccion', 'seguimiento' => null])

@php
    if ($seguimiento) {
        $modulosDisponibles = $seguimiento['modulosDisponibles'];
        $dataPorHora = $seguimiento['dataPorHora'];
        $totales = $seguimiento['totales'];
    } else {
        // Fallback si no se pasa seguimiento (para compatibilidad)
        $registros = collect();
        // Obtener módulos únicos de los registros y ordenarlos
        $modulosDisponibles = $registros->pluck('modulo')->unique()->values()->toArray();

        // Normalizar los nombres de módulos (trim espacios, uppercase consistente)
        $modulosDisponibles = array_map(function($mod) {
            return strtoupper(trim($mod));
        }, $modulosDisponibles);

        // Remover duplicados después de normalizar
        $modulosDisponibles = array_unique($modulosDisponibles);

        // Ordenar los módulos
        sort($modulosDisponibles);

        // Si no hay módulos dinámicos, usar los módulos por defecto
        if (empty($modulosDisponibles)) {
            $modulosDisponibles = ['MÓDULO 1', 'MÓDULO 2', 'MÓDULO 3'];
        }

        // Inicializar estructuras de datos
        $dataPorHora = [];
        $totales = ['modulos' => []];

        // INICIALIZAR todos los módulos en totales
        foreach ($modulosDisponibles as $modulo) {
            $totales['modulos'][$modulo] = [
                'prendas' => 0,
                'tiempo_ciclo_sum' => 0,
                'numero_operarios_sum' => 0,
                'porcion_tiempo_sum' => 0,
                'tiempo_parada_no_programada_sum' => 0,
                'tiempo_para_programada_sum' => 0,
                'tiempo_disponible_sum' => 0,
                'meta_sum' => 0,
                'count' => 0
            ];
        }

        // Procesar cada registro
        foreach ($registros as $registro) {
            // Normalizar el nombre del módulo del registro
            $modulo = strtoupper(trim($registro->modulo));

            // Normalizar hora a formato "HORA XX"
            $horaNum = (int) preg_replace('/\D/', '', $registro->hora);
            $hora = 'HORA ' . str_pad($horaNum, 2, '0', STR_PAD_LEFT);

            // Inicializar hora si no existe
            if (!isset($dataPorHora[$hora])) {
                $dataPorHora[$hora] = ['modulos' => []];
                // Pre-inicializar todos los módulos para esta hora
                foreach ($modulosDisponibles as $mod) {
                    $dataPorHora[$hora]['modulos'][$mod] = [
                        'prendas' => 0,
                        'tiempo_ciclo_sum' => 0,
                        'numero_operarios_sum' => 0,
                        'porcion_tiempo_sum' => 0,
                        'tiempo_parada_no_programada_sum' => 0,
                        'tiempo_para_programada_sum' => 0,
                        'tiempo_disponible_sum' => 0,
                        'meta_sum' => 0,
                        'count' => 0
                    ];
                }
            }

            // Verificar que el módulo exista en modulosDisponibles
            if (!in_array($modulo, $modulosDisponibles)) {
                // Si el módulo no existe, agregarlo dinámicamente
                $modulosDisponibles[] = $modulo;
                $totales['modulos'][$modulo] = [
                    'prendas' => 0,
                    'tiempo_ciclo_sum' => 0,
                    'numero_operarios_sum' => 0,
                    'porcion_tiempo_sum' => 0,
                    'tiempo_parada_no_programada_sum' => 0,
                    'tiempo_para_programada_sum' => 0,
                    'tiempo_disponible_sum' => 0,
                    'meta_sum' => 0,
                    'count' => 0
                ];

                // Inicializar en todas las horas existentes
                foreach ($dataPorHora as $h => &$hData) {
                    $hData['modulos'][$modulo] = [
                        'prendas' => 0,
                        'tiempo_ciclo_sum' => 0,
                        'numero_operarios_sum' => 0,
                        'porcion_tiempo_sum' => 0,
                        'tiempo_parada_no_programada_sum' => 0,
                        'tiempo_para_programada_sum' => 0,
                        'tiempo_disponible_sum' => 0,
                        'meta_sum' => 0,
                        'count' => 0
                    ];
                }
            }

            // Acumular datos por hora y módulo
            $dataPorHora[$hora]['modulos'][$modulo]['prendas'] += floatval($registro->cantidad ?? 0);
            $dataPorHora[$hora]['modulos'][$modulo]['tiempo_ciclo_sum'] += floatval($registro->tiempo_ciclo ?? 0);
            $dataPorHora[$hora]['modulos'][$modulo]['numero_operarios_sum'] += floatval($registro->numero_operarios ?? 0);
            $dataPorHora[$hora]['modulos'][$modulo]['porcion_tiempo_sum'] += floatval($registro->porcion_tiempo ?? 0);
            $dataPorHora[$hora]['modulos'][$modulo]['tiempo_parada_no_programada_sum'] += floatval($registro->tiempo_parada_no_programada ?? 0);
            $dataPorHora[$hora]['modulos'][$modulo]['tiempo_para_programada_sum'] += floatval($registro->tiempo_para_programada ?? 0);
            $dataPorHora[$hora]['modulos'][$modulo]['count']++;

            // Acumular totales generales
            $totales['modulos'][$modulo]['prendas'] += floatval($registro->cantidad ?? 0);
            $totales['modulos'][$modulo]['tiempo_ciclo_sum'] += floatval($registro->tiempo_ciclo ?? 0);
            $totales['modulos'][$modulo]['numero_operarios_sum'] += floatval($registro->numero_operarios ?? 0);
            $totales['modulos'][$modulo]['porcion_tiempo_sum'] += floatval($registro->porcion_tiempo ?? 0);
            $totales['modulos'][$modulo]['tiempo_parada_no_programada_sum'] += floatval($registro->tiempo_parada_no_programada ?? 0);
            $totales['modulos'][$modulo]['tiempo_para_programada_sum'] += floatval($registro->tiempo_para_programada ?? 0);
            $totales['modulos'][$modulo]['count']++;

            // Calcular meta por registro y sumar
            $tiempo_disponible_registro = (3600 * floatval($registro->porcion_tiempo) * floatval($registro->numero_operarios))
                - floatval($registro->tiempo_parada_no_programada ?? 0)
                - floatval($registro->tiempo_para_programada ?? 0);
            $tiempo_disponible_registro = max(0, $tiempo_disponible_registro);
            $meta_registro = floatval($registro->tiempo_ciclo) > 0 ? ($tiempo_disponible_registro / floatval($registro->tiempo_ciclo)) * 0.9 : 0;
            $dataPorHora[$hora]['modulos'][$modulo]['tiempo_disponible_sum'] += $tiempo_disponible_registro;
            $dataPorHora[$hora]['modulos'][$modulo]['meta_sum'] += $meta_registro;
            $totales['modulos'][$modulo]['tiempo_disponible_sum'] += $tiempo_disponible_registro;
            $totales['modulos'][$modulo]['meta_sum'] += $meta_registro;
        }

        // Calcular meta y eficiencia por hora
        foreach ($dataPorHora as $hora => &$data) {
            foreach ($data['modulos'] as $modulo => &$modData) {
                if ($modData['count'] > 0) {
                    $meta = $modData['meta_sum'];
                    $eficiencia = $meta > 0 ? ($modData['prendas'] / $meta) : 0;

                    $modData['meta'] = $meta;
                    $modData['eficiencia'] = $eficiencia;
                } else {
                    $modData['meta'] = 0;
                    $modData['eficiencia'] = 0;
                }
            }
        }

        // Calcular totales finales
        foreach ($totales['modulos'] as $modulo => &$modData) {
            if ($modData['count'] > 0) {
                $total_prendas = $modData['prendas'];
                $total_meta = $modData['meta_sum'];
                $eficiencia = $total_meta > 0 ? ($total_prendas / $total_meta) : 0;

                $modData['meta'] = $total_meta;
                $modData['eficiencia'] = $eficiencia;
            } else {
                $modData['meta'] = 0;
                $modData['eficiencia'] = 0;
            }
        }

        // Re-ordenar módulos alfabéticamente para consistencia en la visualización
        ksort($modulosDisponibles);
    }
@endphp

<style>
/* === Variables para Seguimiento === */
:root {
    --seg-table-bg: #ffffff;
    --seg-table-border: #e2e8f0;
    --seg-header-bg: linear-gradient(135deg, #1e293b 0%, #334155 100%);
    --seg-header-text: #ffffff;
    --seg-cell-bg: #ffffff;
    --seg-cell-text: #1e293b;
    --seg-cell-border: #f1f5f9;
    --seg-hover-bg: #f8fafc;
    --seg-total-bg: #e2e8f0;
    --seg-total-text: #1e293b;
    --seg-legend-bg: #f8fafc;
    --seg-scrollbar-track: #f1f5f9;
    --seg-scrollbar-thumb: rgba(249, 115, 22, 0.4);
    --seg-hora-bg: #f8fafc;
}

body.dark-theme {
    --seg-table-bg: rgba(255, 255, 255, 0.02);
    --seg-table-border: rgba(255, 255, 255, 0.05);
    --seg-header-bg: rgba(255, 255, 255, 0.05);
    --seg-header-text: #e0e0e0;
    --seg-cell-bg: transparent;
    --seg-cell-text: #e0e0e0;
    --seg-cell-border: rgba(255, 255, 255, 0.05);
    --seg-hover-bg: rgba(255, 255, 255, 0.05);
    --seg-total-bg: rgba(255, 255, 255, 0.08);
    --seg-total-text: #fff;
    --seg-legend-bg: rgba(255, 255, 255, 0.03);
    --seg-scrollbar-track: rgba(255, 255, 255, 0.05);
    --seg-scrollbar-thumb: rgba(255, 107, 53, 0.5);
    --seg-hora-bg: transparent;
}

.records-table-container {
    width: 100%;
    background: var(--seg-table-bg);
    border-radius: 12px;
    padding: 0;
    overflow: hidden;
    margin: 2rem 0 0 0;
    border: 1px solid var(--seg-table-border);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

body.dark-theme .records-table-container {
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3), 0 2px 4px -1px rgba(0, 0, 0, 0.2);
}

.table-scroll-container {
    overflow-x: auto;
    overflow-y: auto;
    max-height: 600px;
}

/* Scrollbar styles to match the records table */
.table-scroll-container::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

.table-scroll-container::-webkit-scrollbar-track {
    background: var(--seg-scrollbar-track);
    border-radius: 4px;
}

.table-scroll-container::-webkit-scrollbar-thumb {
    background: var(--seg-scrollbar-thumb);
    border-radius: 4px;
}

.table-scroll-container::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 107, 53, 0.7);
}

.table-scroll-container::-webkit-scrollbar-corner {
    background: var(--seg-scrollbar-track);
}

/* Custom scrollbar styles to match the theme */
.seguimiento-table-container::-webkit-scrollbar {
    height: 8px;
}

.seguimiento-table-container::-webkit-scrollbar-track {
    background: var(--seg-scrollbar-track);
    border-radius: 4px;
}

.seguimiento-table-container::-webkit-scrollbar-thumb {
    background: var(--seg-scrollbar-thumb);
    border-radius: 4px;
}

.seguimiento-table-container::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 107, 53, 0.7);
}

.seguimiento-table-container::-webkit-scrollbar-corner {
    background: var(--seg-scrollbar-track);
}

.seguimiento-table { 
    width: auto; 
    border-collapse: collapse; 
    min-width: 600px; /* ancho mínimo más pequeño */
    table-layout: auto; /* permite ajustar el ancho según contenido */
    margin: 0 auto; /* centra la tabla si hay pocas columnas */
}

.seguimiento-table-small {
    min-width: 600px;
    table-layout: auto;
    width: auto;
}

.seguimiento-table-small th,
.seguimiento-table-small td {
    padding: 12px 16px;
}

.seguimiento-table th, .seguimiento-table td { box-sizing: border-box; }
.seguimiento-th { 
    background: var(--seg-header-bg); 
    color: var(--seg-header-text); 
    padding: 16px 12px; 
    text-align: center; 
    font-weight: 600; 
    font-size: 12px; 
    text-transform: uppercase; 
    letter-spacing: 0.8px; 
    border-bottom: 2px solid var(--seg-table-border); 
    border-right: 1px solid rgba(255, 255, 255, 0.1);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}
.seguimiento-th:last-child { border-right:none; }
.seguimiento-td { 
    padding: 14px 12px; 
    color: var(--seg-cell-text); 
    font-size: 14px; 
    font-weight: 500;
    border-bottom: 1px solid var(--seg-cell-border); 
    border-right: 1px solid var(--seg-cell-border); 
    text-align: center; 
    background: var(--seg-cell-bg);
    transition: all 0.2s ease;
}
.seguimiento-td:last-child { border-right:none; }
.seguimiento-tr:hover { 
    background: var(--seg-hover-bg);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    transform: translateY(-1px);
}
.seguimiento-hora-cell { 
    color: var(--seg-cell-text); 
    font-weight: 700; 
    text-align: left; 
    padding-left: 20px;
    background: var(--seg-hora-bg);
    font-size: 13px;
    letter-spacing: 0.3px;
}
.seguimiento-efficiency-cell { 
    font-weight: 700; 
    padding: 8px 10px; 
    border-radius: 8px; 
    text-align: center; 
    margin: 0 auto; 
    max-width: 85px;
    font-size: 13px;
    letter-spacing: 0.3px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
}
.seguimiento-green { background: rgba(34, 197, 94, 0.25) !important; color: #15803d !important; border: 1px solid rgba(34, 197, 94, 0.5) !important; }
.seguimiento-blue { background: rgba(59, 130, 246, 0.25) !important; color: #1e40af !important; border: 1px solid rgba(59, 130, 246, 0.5) !important; }
.seguimiento-red { background: rgba(239, 68, 68, 0.25) !important; color: #b91c1c !important; border: 1px solid rgba(239, 68, 68, 0.5) !important; }
.seguimiento-orange { background: rgba(249, 115, 22, 0.25) !important; color: #c2410c !important; border: 1px solid rgba(249, 115, 22, 0.5) !important; }
.seguimiento-gray { background: rgba(107, 114, 128, 0.2) !important; color: #4b5563 !important; border: 1px solid rgba(107, 114, 128, 0.4) !important; }

body.dark-theme .seguimiento-green { background: rgba(72,187,120,0.2) !important; color:#68d391 !important; border: 1px solid rgba(72,187,120,0.3) !important; }
body.dark-theme .seguimiento-blue { background: rgba(66,153,225,0.2) !important; color:#63b3ed !important; border: 1px solid rgba(66,153,225,0.3) !important; }
body.dark-theme .seguimiento-red { background: rgba(252,129,129,0.2) !important; color:#fc8181 !important; border: 1px solid rgba(252,129,129,0.3) !important; }
body.dark-theme .seguimiento-orange { background: rgba(237,137,54,0.2) !important; color:#f6ad55 !important; border: 1px solid rgba(237,137,54,0.3) !important; }
body.dark-theme .seguimiento-gray { background: rgba(160,174,192,0.2) !important; color:#a0aec0 !important; border: 1px solid rgba(160,174,192,0.3) !important; }

.seguimiento-total-row { 
    background: var(--seg-total-bg) !important; 
    font-weight: 700;
    box-shadow: 0 -2px 8px rgba(0, 0, 0, 0.08);
}
.seguimiento-total-row .seguimiento-td { 
    color: var(--seg-total-text) !important; 
    font-size: 15px; 
    padding: 20px 12px; 
    font-weight: 700 !important;
    letter-spacing: 0.5px;
    background: var(--seg-total-bg) !important;
}
.seguimiento-total-row .seguimiento-hora-cell {
    color: var(--seg-total-text) !important;
    background: var(--seg-total-bg) !important;
}
.seguimiento-total-row .seguimiento-efficiency-cell {
    background: inherit !important;
    color: inherit !important;
    border: inherit !important;
}
.seguimiento-legend { 
    display: flex; 
    justify-content: center; 
    gap: 24px; 
    margin-top: 24px; 
    padding: 16px 24px; 
    background: var(--seg-legend-bg); 
    border-radius: 10px; 
    flex-wrap: wrap; 
    border: 1px solid var(--seg-table-border);
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}
.seguimiento-legend-item { 
    display: flex; 
    align-items: center; 
    gap: 8px; 
    color: var(--seg-cell-text); 
    font-size: 12px;
    font-weight: 500;
    padding: 6px 12px;
    background: var(--seg-cell-bg);
    border-radius: 6px;
    border: 1px solid var(--seg-cell-border);
    transition: all 0.2s ease;
}
.seguimiento-legend-item:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
}
.seguimiento-legend-color { 
    width: 18px; 
    height: 18px; 
    border-radius: 4px;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}
</style>

<div class="records-table-container">
    <div class="table-scroll-container">
        <table class="seguimiento-table {{ count($modulosDisponibles) <= 2 ? 'seguimiento-table-small' : '' }}">
            <thead>
                <tr>
                    <th rowspan="2" class="seguimiento-th">HORA</th>
                    @foreach($modulosDisponibles as $index => $modulo)
                        <th colspan="3" class="seguimiento-th seguimiento-module-header seguimiento-module{{ $index + 1 }}">{{ $modulo }}</th>
                    @endforeach
                </tr>
                <tr>
                    @foreach($modulosDisponibles as $index => $modulo)
                        <th class="seguimiento-th seguimiento-module{{ $index + 1 }}">Prendas</th>
                        <th class="seguimiento-th seguimiento-module{{ $index + 1 }}">Meta</th>
                        <th class="seguimiento-th seguimiento-module{{ $index + 1 }}">Eficiencia</th>
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
                    <tr class="seguimiento-tr">
                        <td class="seguimiento-td seguimiento-hora-cell">{{ $horaKey }}</td>
                        @foreach($modulosDisponibles as $modulo)
                            @php
                                $modData = $horaData['modulos'][$modulo] ?? ['meta'=>0,'eficiencia'=>0,'prendas'=>0];
                                $eficiencia = $modData['eficiencia'];
                                $eficienciaClass = $modData['prendas'] > 0
                                    ? (($eficiencia > 1.00) ? 'seguimiento-blue'
                                    : (($eficiencia >= 0.80) ? 'seguimiento-green'
                                    : (($eficiencia >= 0.70) ? 'seguimiento-orange' : 'seguimiento-red')))
                                    : 'seguimiento-gray';
                            @endphp
                            <td class="seguimiento-td">{{ number_format($modData['prendas'], 0) }}</td>
                            <td class="seguimiento-td">{{ number_format($modData['meta'], 2) }}</td>
                            <td class="seguimiento-td seguimiento-efficiency-cell {{ $eficienciaClass }}">{{ $modData['prendas'] > 0 ? round($modData['eficiencia'] * 100) . '%' : '0%' }}</td>
                        @endforeach
                    </tr>
                @endforeach

                <tr class="seguimiento-total-row">
                    <td class="seguimiento-td seguimiento-hora-cell">Suma total</td>
                    @foreach($modulosDisponibles as $modulo)
                        @php
                            $modTotal = $totales['modulos'][$modulo] ?? ['meta'=>0,'eficiencia'=>0,'prendas'=>0];
                            $eficiencia = $modTotal['eficiencia'];
                            $eficienciaClass = $modTotal['prendas'] > 0
                                ? (($eficiencia > 1.00) ? 'seguimiento-blue'
                                : (($eficiencia >= 0.80) ? 'seguimiento-green'
                                : (($eficiencia >= 0.70) ? 'seguimiento-orange' : 'seguimiento-red')))
                                : 'seguimiento-gray';
                        @endphp
                        <td class="seguimiento-td">{{ number_format($modTotal['prendas'], 0) }}</td>
                        <td class="seguimiento-td">{{ number_format($modTotal['meta'], 2) }}</td>
                        <td class="seguimiento-td seguimiento-efficiency-cell {{ $eficienciaClass }}">{{ $modTotal['prendas'] > 0 ? round($modTotal['eficiencia'] * 100) . '%' : '0%' }}</td>
                    @endforeach
                </tr>
            </tbody>
        </table>
    </div>
</div>

<div class="seguimiento-legend">
    <div class="seguimiento-legend-item">
        <div class="seguimiento-legend-color seguimiento-blue"></div>
        <span>100%+ Eficiencia</span>
    </div>
    <div class="seguimiento-legend-item">
        <div class="seguimiento-legend-color seguimiento-green"></div>
        <span>80-100% Eficiencia</span>
    </div>
    <div class="seguimiento-legend-item">
        <div class="seguimiento-legend-color seguimiento-orange"></div>
        <span>70-79% Eficiencia</span>
    </div>
    <div class="seguimiento-legend-item">
        <div class="seguimiento-legend-color seguimiento-red"></div>
        <span>≤ 70% Eficiencia</span>
    </div>
</div>

<script>
// Función para actualizar la tabla de seguimiento de módulos
function updateSeguimientoTable(params) {
    // Determinar la sección actual desde la URL o un elemento en la página
    const currentSection = getCurrentSection();

    const seguimientoUrl = new URL('/tableros/get-seguimiento-data', window.location.origin);
    seguimientoUrl.search = params.toString();
    seguimientoUrl.searchParams.set('section', currentSection);

    fetch(seguimientoUrl.toString(), {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        updateSeguimientoTableContent(data);
    })
    .catch(error => {
        console.error('Error updating seguimiento table:', error);
        alert('Error al actualizar la tabla de seguimiento. Por favor, recarga la página.');
    });
}

// Función para determinar la sección actual
function getCurrentSection() {
    // Intentar obtener la sección desde elementos de la página
    const activeTab = document.querySelector('.tab.active, .nav-link.active');
    if (activeTab) {
        const tabText = activeTab.textContent.toLowerCase();
        if (tabText.includes('produccion')) return 'produccion';
        if (tabText.includes('polo')) return 'polos';
        if (tabText.includes('corte')) return 'corte';
    }

    // Intentar desde la URL
    const urlParams = new URLSearchParams(window.location.search);
    const section = urlParams.get('section');
    if (section) return section;

    // Default
    return 'produccion';
}

// Función para actualizar el contenido de la tabla de seguimiento
function updateSeguimientoTableContent(seguimientoData) {
    const tableContainer = document.querySelector('.seguimiento-table');
    if (!tableContainer) return;

    // Actualizar módulos disponibles
    const modulosDisponibles = seguimientoData.modulosDisponibles || [];
    const dataPorHora = seguimientoData.dataPorHora || {};
    const totales = seguimientoData.totales || { modulos: {} };

    // Recalcular META y EFICIENCIA en el front siguiendo la regla de negocio
    // meta = ((3600 * avg(porcion_tiempo) * avg(numero_operarios)) - sum(tiempo_parada_no_programada) - sum(tiempo_para_programada)) / avg(tiempo_ciclo) * 0.9
    // eficiencia = prendas / meta
    try {
        // Por hora y módulo
        const normalizarModulo = (modData = {}) => {
            const prendas = parseFloat(modData.prendas ?? 0);
            const meta = parseFloat(modData.meta ?? modData.meta_sum ?? 0);
            const eficiencia = meta > 0 ? prendas / meta : 0;

            return {
                ...modData,
                prendas,
                meta,
                eficiencia
            };
        };

        Object.keys(dataPorHora).forEach(horaKey => {
            const horaData = dataPorHora[horaKey] || { modulos: {} };
            const modulosNorm = {};
            modulosDisponibles.forEach(modulo => {
                modulosNorm[modulo] = normalizarModulo(horaData.modulos?.[modulo]);
            });
            dataPorHora[horaKey] = { ...horaData, modulos: modulosNorm };
        });

        totales.modulos = totales.modulos || {};
        const totalesNorm = {};
        modulosDisponibles.forEach(modulo => {
            totalesNorm[modulo] = normalizarModulo(totales.modulos[modulo]);
        });
        totales.modulos = totalesNorm;
    } catch (err) {
        console.warn('No se pudo recalcular seguimiento en front:', err);
    }

    // Reconstruir la tabla
    let html = '';

    // Header
    html += '<thead><tr><th rowspan="2" class="seguimiento-th">HORA</th>';
    modulosDisponibles.forEach(modulo => {
        html += `<th colspan="3" class="seguimiento-th seguimiento-module-header seguimiento-module${modulosDisponibles.indexOf(modulo) + 1}">${modulo}</th>`;
    });
    html += '</tr><tr>';
    modulosDisponibles.forEach(modulo => {
        html += `<th class="seguimiento-th seguimiento-module${modulosDisponibles.indexOf(modulo) + 1}">Prendas</th>`;
        html += `<th class="seguimiento-th seguimiento-module${modulosDisponibles.indexOf(modulo) + 1}">Meta</th>`;
        html += `<th class="seguimiento-th seguimiento-module${modulosDisponibles.indexOf(modulo) + 1}">Eficiencia</th>`;
    });
    html += '</tr></thead>';

    // Body
    html += '<tbody>';
    const horasOrdenadas = Object.keys(dataPorHora).sort();
    horasOrdenadas.slice(0, 12).forEach(horaKey => {
        const horaData = dataPorHora[horaKey] || { modulos: {} };
        html += `<tr class="seguimiento-tr"><td class="seguimiento-td seguimiento-hora-cell">${horaKey}</td>`;
        modulosDisponibles.forEach(modulo => {
            const modData = horaData.modulos[modulo] || { prendas: 0, meta: 0, eficiencia: 0 };
            
            // Determinar clase de eficiencia
            let eficienciaClass = 'seguimiento-gray';
            if (modData.prendas > 0 && modData.eficiencia) {
                const eficienciaNum = parseFloat(modData.eficiencia);
                
                // Aplicar lógica directamente
                if (eficienciaNum >= 1.00) {
                    eficienciaClass = 'seguimiento-blue';
                } else if (eficienciaNum >= 0.80) {
                    eficienciaClass = 'seguimiento-green';
                } else if (eficienciaNum >= 0.70) {
                    eficienciaClass = 'seguimiento-orange';
                } else {
                    eficienciaClass = 'seguimiento-red';
                }
                
                console.log(`Hora ${horaKey}, Módulo ${modulo}: eficiencia=${modData.eficiencia} (${eficienciaNum}), clase=${eficienciaClass}`);
            }
            
            html += `<td class="seguimiento-td">${number_format(modData.prendas, 0)}</td>`;
            html += `<td class="seguimiento-td">${number_format(modData.meta, 2)}</td>`;
            html += `<td class="seguimiento-td seguimiento-efficiency-cell ${eficienciaClass}">${formatEfficiency(modData.eficiencia)}</td>`;
        });
        html += '</tr>';
    });

    // Total row
    html += '<tr class="seguimiento-total-row"><td class="seguimiento-td seguimiento-hora-cell">Suma total</td>';
    modulosDisponibles.forEach(modulo => {
        const modTotal = totales.modulos[modulo] || { prendas: 0, meta: 0, eficiencia: 0 };
        
        // Determinar clase de eficiencia para totales
        let eficienciaClass = 'seguimiento-gray';
        if (modTotal.prendas > 0 && modTotal.eficiencia) {
            const eficienciaNum = parseFloat(modTotal.eficiencia);
            
            // Aplicar lógica directamente
            if (eficienciaNum >= 1.00) {
                eficienciaClass = 'seguimiento-blue';
            } else if (eficienciaNum >= 0.80) {
                eficienciaClass = 'seguimiento-green';
            } else if (eficienciaNum >= 0.70) {
                eficienciaClass = 'seguimiento-orange';
            } else {
                eficienciaClass = 'seguimiento-red';
            }
            
            console.log(`Total ${modulo}: eficiencia=${modTotal.eficiencia} (${eficienciaNum}), clase=${eficienciaClass}`);
        }
        
        html += `<td class="seguimiento-td">${number_format(modTotal.prendas, 0)}</td>`;
        html += `<td class="seguimiento-td">${number_format(modTotal.meta, 2)}</td>`;
        html += `<td class="seguimiento-td seguimiento-efficiency-cell ${eficienciaClass}">${formatEfficiency(modTotal.eficiencia)}</td>`;
    });
    html += '</tr></tbody>';

    tableContainer.innerHTML = html;
}

// Función auxiliar para formatear números
function number_format(number, decimals) {
    return parseFloat(number).toLocaleString('es-ES', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals
    });
}

function formatEfficiency(value) {
    const eficiencia = parseFloat(value ?? 0);
    if (eficiencia <= 0) {
        return '0%';
    }

    const porcentaje = eficiencia * 100;
    const formatted = porcentaje.toLocaleString('es-ES', {
        minimumFractionDigits: 1,
        maximumFractionDigits: 1
    });

    return `${formatted}%`;
}

// Función para determinar la clase de eficiencia (global)
window.getEficienciaClass = function(eficiencia) {
    const eficienciaNum = parseFloat(eficiencia);
    
    // Si es 0, NaN o undefined, mostrar gris
    if (!eficienciaNum || isNaN(eficienciaNum)) return 'seguimiento-gray';
    
    // La eficiencia viene como decimal (1.0 = 100%, 0.8 = 80%, etc.)
    if (eficienciaNum >= 1.00) return 'seguimiento-blue';
    if (eficienciaNum >= 0.80) return 'seguimiento-green';
    if (eficienciaNum >= 0.70) return 'seguimiento-orange';
    return 'seguimiento-red';
};

// ============================================
// TIEMPO REAL - Actualización automática
// ============================================
function initializeSeguimientoRealtime() {
    console.log('=== SEGUIMIENTO - Inicializando tiempo real ===');
    
    if (!window.Echo) {
        console.log('Echo no disponible, reintentando...');
        setTimeout(initializeSeguimientoRealtime, 500);
        return;
    }

    console.log('✅ Echo disponible, suscribiendo a canales...');

    // Determinar qué canal escuchar según la sección actual
    const currentSection = getCurrentSection();
    
    // Evitar suscripciones duplicadas
    if (window.seguimientoChannelSubscribed) {
        console.log('⚠️ Ya hay una suscripción activa, omitiendo...');
        return;
    }
    
    window.seguimientoChannelSubscribed = true;
    
    if (currentSection === 'produccion') {
        window.Echo.channel('produccion').listen('ProduccionRecordCreated', (e) => {
            console.log('🎉 Evento ProduccionRecordCreated recibido en seguimiento');
            recargarSeguimiento();
        });
    } else if (currentSection === 'polos') {
        window.Echo.channel('polo').listen('PoloRecordCreated', (e) => {
            console.log('🎉 Evento PoloRecordCreated recibido en seguimiento');
            recargarSeguimiento();
        });
    } else if (currentSection === 'corte') {
        window.Echo.channel('corte').listen('CorteRecordCreated', (e) => {
            console.log('🎉 Evento CorteRecordCreated recibido en seguimiento');
            if (typeof recargarDashboardCorte === 'function') {
                recargarDashboardCorte();
            }
        });
    }

    console.log(`✅ Listener configurado para sección: ${currentSection}`);
}

// Función para recargar los datos de seguimiento
function recargarSeguimiento() {
    console.log('Recargando datos de seguimiento...');
    
    // Obtener los parámetros actuales de la URL
    const params = new URLSearchParams(window.location.search);
    const currentSection = getCurrentSection();
    
    const seguimientoUrl = new URL('/tableros/get-seguimiento-data', window.location.origin);
    seguimientoUrl.search = params.toString();
    seguimientoUrl.searchParams.set('section', currentSection);
    
    fetch(seguimientoUrl, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        console.log('Datos de seguimiento recibidos:', data);
        
        // Los datos pueden venir en data.seguimiento o directamente en data
        const seguimientoData = data.seguimiento || data;
        
        if (seguimientoData && seguimientoData.modulosDisponibles) {
            console.log('✅ Actualizando tabla de seguimiento...');
            console.log('Módulos:', seguimientoData.modulosDisponibles);
            console.log('Totales:', seguimientoData.totales);
            
            // Actualizar las variables globales
            window.seguimientoData = seguimientoData;
            
            // Redibujar la tabla usando la función existente
            updateSeguimientoTableContent(seguimientoData);
            
            console.log('✅ Tabla de seguimiento actualizada');
        } else {
            console.error('❌ No se recibieron datos de seguimiento válidos');
            console.error('Estructura recibida:', data);
        }
    })
    .catch(error => {
        console.error('Error al recargar seguimiento:', error);
    });
}

// Variable global para evitar inicialización múltiple
if (!window.seguimientoRealtimeInitialized) {
    window.seguimientoRealtimeInitialized = true;
    
    // Inicializar cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(initializeSeguimientoRealtime, 1000);
        });
    } else {
        setTimeout(initializeSeguimientoRealtime, 1000);
    }
}
</script>
