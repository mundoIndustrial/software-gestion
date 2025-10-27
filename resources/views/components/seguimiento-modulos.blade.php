@props(['section' => 'produccion'])

@php
    // Lógica para determinar el modelo basado en la sección
    $model = match($section) {
        'produccion' => \App\Models\RegistroPisoProduccion::class,
        'polos' => \App\Models\RegistroPisoPolo::class,
        'corte' => \App\Models\RegistroPisoProduccion::class,
        default => \App\Models\RegistroPisoProduccion::class,
    };

    // Obtener fechas del request o usar valores por defecto
    $startDate = request('start_date', now()->format('Y-m-d'));
    $endDate = request('end_date', now()->format('Y-m-d'));

    // Obtener registros para el rango de fechas
    $registros = $model::whereBetween('fecha', [$startDate, $endDate])->get();

    // Agrupar por hora
    $dataPorHora = [];
    $totales = ['modulos' => []];

    // Obtener módulos únicos de los registros
    $modulosDisponibles = $registros->pluck('modulo')->unique()->sort()->values()->toArray();

    foreach ($registros as $registro) {
        // Normalizar hora a formato "HORA XX"
        $horaNum = (int) preg_replace('/\D/', '', $registro->hora);
        $hora = 'HORA ' . str_pad($horaNum, 2, '0', STR_PAD_LEFT);
        if (!isset($dataPorHora[$hora])) {
            $dataPorHora[$hora] = ['modulos' => []];
        }

        $modulo = $registro->modulo;
        if (!isset($dataPorHora[$hora]['modulos'][$modulo])) {
            $dataPorHora[$hora]['modulos'][$modulo] = [
                'prendas' => 0,
                'tiempo_ciclo_sum' => 0,
                'numero_operarios_sum' => 0,
                'porcion_tiempo_sum' => 0,
                'tiempo_parada_no_programada_sum' => 0,
                'tiempo_para_programada_sum' => 0,
                'count' => 0
            ];
        }

        $dataPorHora[$hora]['modulos'][$modulo]['prendas'] += $registro->cantidad ?? 0;
        $dataPorHora[$hora]['modulos'][$modulo]['tiempo_ciclo_sum'] += $registro->tiempo_ciclo ?? 0;
        $dataPorHora[$hora]['modulos'][$modulo]['numero_operarios_sum'] += $registro->numero_operarios ?? 0;
        $dataPorHora[$hora]['modulos'][$modulo]['porcion_tiempo_sum'] += $registro->porcion_tiempo ?? 0;
        $dataPorHora[$hora]['modulos'][$modulo]['tiempo_parada_no_programada_sum'] += $registro->tiempo_parada_no_programada ?? 0;
        $dataPorHora[$hora]['modulos'][$modulo]['tiempo_para_programada_sum'] += $registro->tiempo_para_programada ?? 0;
        $dataPorHora[$hora]['modulos'][$modulo]['count']++;

        if (!isset($totales['modulos'][$modulo])) {
            $totales['modulos'][$modulo] = [
                'prendas' => 0,
                'tiempo_ciclo_sum' => 0,
                'numero_operarios_sum' => 0,
                'porcion_tiempo_sum' => 0,
                'tiempo_parada_no_programada_sum' => 0,
                'tiempo_para_programada_sum' => 0,
                'count' => 0
            ];
        }
        $totales['modulos'][$modulo]['prendas'] += $registro->cantidad ?? 0;
        $totales['modulos'][$modulo]['tiempo_ciclo_sum'] += $registro->tiempo_ciclo ?? 0;
        $totales['modulos'][$modulo]['numero_operarios_sum'] += $registro->numero_operarios ?? 0;
        $totales['modulos'][$modulo]['porcion_tiempo_sum'] += $registro->porcion_tiempo ?? 0;
        $totales['modulos'][$modulo]['tiempo_parada_no_programada_sum'] += $registro->tiempo_parada_no_programada ?? 0;
        $totales['modulos'][$modulo]['tiempo_para_programada_sum'] += $registro->tiempo_para_programada ?? 0;
        $totales['modulos'][$modulo]['count']++;
    }

    // Calcular meta y eficiencia por hora
    foreach ($dataPorHora as $hora => &$data) {
        foreach ($data['modulos'] as $modulo => &$modData) {
            if ($modData['count'] > 0) {
                $avg_tiempo_ciclo = $modData['tiempo_ciclo_sum'] / $modData['count'];
                $avg_numero_operarios = $modData['numero_operarios_sum'] / $modData['count'];
                $avg_porcion_tiempo = $modData['porcion_tiempo_sum'] / $modData['count'];
                $total_tiempo_parada_no_programada = $modData['tiempo_parada_no_programada_sum'];
                $total_tiempo_para_programada = $modData['tiempo_para_programada_sum'];

                $tiempo_disponible = (3600 * $avg_porcion_tiempo * $avg_numero_operarios) - $total_tiempo_parada_no_programada - $total_tiempo_para_programada;
                $meta = $avg_tiempo_ciclo > 0 ? ($tiempo_disponible / $avg_tiempo_ciclo) * 0.9 : 0;
                $eficiencia = $meta > 0 ? ($modData['prendas'] / $meta) : 0;

                $modData['meta'] = $meta;
                $modData['eficiencia'] = $eficiencia;
            }
        }
    }

    // Calcular totales
    foreach ($totales['modulos'] as $modulo => &$modData) {
        if ($modData['count'] > 0) {
            $avg_tiempo_ciclo = $modData['tiempo_ciclo_sum'] / $modData['count'];
            $avg_numero_operarios = $modData['numero_operarios_sum'] / $modData['count'];
            $avg_porcion_tiempo = $modData['porcion_tiempo_sum'] / $modData['count'];
            $total_tiempo_parada_no_programada = $modData['tiempo_parada_no_programada_sum'];
            $total_tiempo_para_programada = $modData['tiempo_para_programada_sum'];

            $tiempo_disponible = (3600 * $avg_porcion_tiempo * $avg_numero_operarios) - $total_tiempo_parada_no_programada - $total_tiempo_para_programada;
            $meta = $avg_tiempo_ciclo > 0 ? ($tiempo_disponible / $avg_tiempo_ciclo) * 0.9 : 0;
            $eficiencia = $meta > 0 ? ($modData['prendas'] / $meta) : 0;

            $modData['meta'] = $meta;
            $modData['eficiencia'] = $eficiencia;
        }
    }
@endphp

<style>
/* Estilos sin cambios, solo se mantiene la estructura de la tabla y módulos */
.seguimiento-table-wrapper {
    background: rgba(255, 255, 255, 0.03);
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 20px 60px rgba(0,0,0,0.5);
    margin: 20px 0;
    width: 100%;        /* se ajusta al ancho del div padre */
    max-width: 900px;   /* ancho máximo deseado */
    height: 100%;       /* se ajusta al alto del div padre */
    max-height: 600px;  /* alto máximo deseado */
}


.seguimiento-table-container {
    width: 100%;
    height: 100%;
    overflow-x: auto; /* permite scroll horizontal */
    overflow-y: hidden; /* desactiva scroll vertical */
}

.seguimiento-table { width: 100%; border-collapse: collapse; min-width: 1200px; table-layout: fixed; }
.seguimiento-table th, .seguimiento-table td { box-sizing: border-box; }
.seguimiento-th { background: rgba(255, 255, 255, 0.05); color: #e0e0e0; padding: 16px 8px; text-align: center; font-weight: 600; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid rgba(255,255,255,0.1); border-right:1px solid rgba(255,255,255,0.05); }
.seguimiento-th:last-child { border-right:none; }
.seguimiento-td { padding: 14px 8px; color: #e0e0e0; font-size:14px; border-bottom:1px solid rgba(255,255,255,0.05); border-right:1px solid rgba(255,255,255,0.03); text-align:center; }
.seguimiento-td:last-child { border-right:none; }
.seguimiento-tr:hover { background: rgba(255,255,255,0.05); }
.seguimiento-hora-cell { color:#fff; font-weight:600; text-align:left; padding-left:16px; }
.seguimiento-efficiency-cell { font-weight:600; padding:8px 6px; border-radius:6px; text-align:center; margin:0 auto; max-width:80px; }
.seguimiento-green { background: rgba(72,187,120,0.2); color:#68d391; }
.seguimiento-blue { background: rgba(66,153,225,0.2); color:#63b3ed; }
.seguimiento-red { background: rgba(252,129,129,0.2); color:#fc8181; }
.seguimiento-orange { background: rgba(237,137,54,0.2); color:#f6ad55; }
.seguimiento-gray { background: rgba(160,174,192,0.2); color:#a0aec0; }
.seguimiento-total-row { background: rgba(255,255,255,0.08); font-weight:700; }
.seguimiento-total-row .seguimiento-td { color:#fff; font-size:15px; padding:18px 8px; }
.seguimiento-legend { display:flex; justify-content:center; gap:30px; margin-top:30px; padding:20px; background: rgba(255,255,255,0.03); border-radius:12px; flex-wrap:wrap; }
.seguimiento-legend-item { display:flex; align-items:center; gap:10px; color:#e0e0e0; font-size:13px; }
.seguimiento-legend-color { width:20px; height:20px; border-radius:4px; }
</style>

<div class="seguimiento-table-wrapper">
    <div class="seguimiento-table-container">
        <table class="seguimiento-table">
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
                    $horasOrdenadas = array_slice($horasOrdenadas, 0, 12); // Limitar a 12 horas máximo
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
                                    ? (($eficiencia >= 1.10) ? 'seguimiento-blue'
                                    : (($eficiencia >= 0.98) ? 'seguimiento-green'
                                    : (($eficiencia >= 0.70) ? 'seguimiento-orange' : 'seguimiento-red')))
                                    : 'seguimiento-gray';
                            @endphp
                            <td class="seguimiento-td">{{ number_format($modData['prendas'], 0) }}</td>
                            <td class="seguimiento-td">{{ number_format($modData['meta'], 2) }}</td>
                            <td class="seguimiento-td seguimiento-efficiency-cell {{ $eficienciaClass }}">{{ $modData['prendas'] > 0 ? number_format($modData['eficiencia'] * 100, 2) . '%' : '0.00%' }}</td>
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
                                ? (($eficiencia >= 1.10) ? 'seguimiento-blue' 
                                : (($eficiencia >= 0.98) ? 'seguimiento-green' 
                                : (($eficiencia >= 0.70) ? 'seguimiento-orange' : 'seguimiento-red'))) 
                                : 'seguimiento-gray';
                        @endphp
                        <td class="seguimiento-td">{{ number_format($modTotal['prendas'], 0) }}</td>
                        <td class="seguimiento-td">{{ number_format($modTotal['meta'], 2) }}</td>
                        <td class="seguimiento-td seguimiento-efficiency-cell {{ $eficienciaClass }}">{{ $modTotal['prendas'] > 0 ? number_format($modTotal['eficiencia'] * 100, 2) . '%' : '0.00%' }}</td>
                    @endforeach
                </tr>
            </tbody>
        </table>
    </div>
</div>

<div class="seguimiento-legend">
    <div class="seguimiento-legend-item">
        <div class="seguimiento-legend-color seguimiento-green"></div>
        <span>98-110% Eficiencia</span>
    </div>
    <div class="seguimiento-legend-item">
        <div class="seguimiento-legend-color seguimiento-blue"></div>
        <span>110%+ Eficiencia</span>
    </div>
    <div class="seguimiento-legend-item">
        <div class="seguimiento-legend-color seguimiento-red"></div>
        <span>Bajo 70% Eficiencia</span>
    </div>
    <div class="seguimiento-legend-item">
        <div class="seguimiento-legend-color seguimiento-orange"></div>
        <span>70-98% Eficiencia</span>
    </div>
</div>
