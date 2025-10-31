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
.records-table-container {
    width: 100%;
    background: rgba(255, 255, 255, 0.02);
    border-radius: 8px;
    padding: 0;
    overflow: hidden;
    margin: 2rem 0 0 0;
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
    background: rgba(255, 255, 255, 0.05);
    border-radius: 4px;
}

.table-scroll-container::-webkit-scrollbar-thumb {
    background: rgba(255, 107, 53, 0.5);
    border-radius: 4px;
}

.table-scroll-container::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 107, 53, 0.7);
}

.table-scroll-container::-webkit-scrollbar-corner {
    background: rgba(255, 255, 255, 0.05);
}

/* Custom scrollbar styles to match the theme */
.seguimiento-table-container::-webkit-scrollbar {
    height: 8px;
}

.seguimiento-table-container::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 4px;
}

.seguimiento-table-container::-webkit-scrollbar-thumb {
    background: rgba(255, 107, 53, 0.5);
    border-radius: 4px;
}

.seguimiento-table-container::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 107, 53, 0.7);
}

.seguimiento-table-container::-webkit-scrollbar-corner {
    background: rgba(255, 255, 255, 0.05);
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
