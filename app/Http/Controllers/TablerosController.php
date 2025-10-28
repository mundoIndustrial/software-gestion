<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use App\Models\RegistroPisoProduccion;
use App\Models\RegistroPisoPolo;
use App\Models\RegistroPisoCorte;

class TablerosController extends Controller
{
    public function index()
    {
        $queryProduccion = RegistroPisoProduccion::query();
        $this->aplicarFiltroFecha($queryProduccion, request());
        $registros = $queryProduccion->paginate(50);
        $columns = Schema::getColumnListing('registro_piso_produccion');
        $columns = array_diff($columns, ['id', 'created_at', 'updated_at', 'producida']);

        // Recalcular tiempo_disponible, meta y eficiencia para cada registro
        foreach ($registros as $registro) {
            $tiempo_disponible = (3600 * $registro->porcion_tiempo * $registro->numero_operarios) -
                               ($registro->tiempo_parada_no_programada ?? 0) -
                               ($registro->tiempo_para_programada ?? 0);

            $meta = $registro->tiempo_ciclo > 0 ? ($tiempo_disponible / $registro->tiempo_ciclo) * 0.9 : 0;
            $eficiencia = $meta == 0 ? 0 : ($registro->cantidad / $meta);

            $registro->tiempo_disponible = $tiempo_disponible;
            $registro->meta = $meta;
            $registro->eficiencia = $eficiencia;
            $registro->save();
        }

        $queryPolos = RegistroPisoPolo::query();
        $this->aplicarFiltroFecha($queryPolos, request());
        $registrosPolos = $queryPolos->paginate(50);
        $columnsPolos = Schema::getColumnListing('registro_piso_polo');
        $columnsPolos = array_diff($columnsPolos, ['id', 'created_at', 'updated_at', 'producida']);

        // Recalcular tiempo_disponible, meta y eficiencia para cada registro de polos
        foreach ($registrosPolos as $registro) {
            $tiempo_disponible = (3600 * $registro->porcion_tiempo * $registro->numero_operarios) -
                               ($registro->tiempo_parada_no_programada ?? 0) -
                               ($registro->tiempo_para_programada ?? 0);

            $meta = $registro->tiempo_ciclo > 0 ? ($tiempo_disponible / $registro->tiempo_ciclo) * 0.9 : 0;
            $eficiencia = $meta == 0 ? 0 : ($registro->cantidad / $meta);

            $registro->tiempo_disponible = $tiempo_disponible;
            $registro->meta = $meta;
            $registro->eficiencia = $eficiencia;
            $registro->save();
        }

        $queryCorte = RegistroPisoCorte::query();
        $this->aplicarFiltroFecha($queryCorte, request());
        $registrosCorte = $queryCorte->paginate(50);
        $columnsCorte = Schema::getColumnListing('registro_piso_corte');
        $columnsCorte = array_diff($columnsCorte, ['id', 'created_at', 'updated_at', 'producida']);

        // Recalcular tiempo_disponible, meta y eficiencia para cada registro de corte
        foreach ($registrosCorte as $registro) {
            $tiempo_disponible = (3600 * $registro->porcion_tiempo * $registro->numero_operarios) -
                               ($registro->tiempo_parada_no_programada ?? 0) -
                               ($registro->tiempo_para_programada ?? 0);

            $meta = $registro->tiempo_ciclo > 0 ? ($tiempo_disponible / $registro->tiempo_ciclo) * 0.9 : 0;
            $eficiencia = $meta == 0 ? 0 : ($registro->cantidad / $meta);

            $registro->tiempo_disponible = $tiempo_disponible;
            $registro->meta = $meta;
            $registro->eficiencia = $eficiencia;
            $registro->save();
        }

        if (request()->wantsJson()) {
            return response()->json([
                'registros' => $registros->items(),
                'columns' => array_values($columns),
                'registrosPolos' => $registrosPolos->items(),
                'columnsPolos' => array_values($columnsPolos),
                'registrosCorte' => $registrosCorte->items(),
                'columnsCorte' => array_values($columnsCorte),
                'pagination' => [
                    'current_page' => $registros->currentPage(),
                    'last_page' => $registros->lastPage(),
                    'per_page' => $registros->perPage(),
                    'total' => $registros->total()
                ],
                'paginationPolos' => [
                    'current_page' => $registrosPolos->currentPage(),
                    'last_page' => $registrosPolos->lastPage(),
                    'per_page' => $registrosPolos->perPage(),
                    'total' => $registrosPolos->total()
                ],
                'paginationCorte' => [
                    'current_page' => $registrosCorte->currentPage(),
                    'last_page' => $registrosCorte->lastPage(),
                    'per_page' => $registrosCorte->perPage(),
                    'total' => $registrosCorte->total()
                ]
            ]);
        }

        // Obtener todos los registros para seguimiento
        $todosRegistrosProduccion = RegistroPisoProduccion::all();
        $todosRegistrosPolos = RegistroPisoPolo::all();
        $todosRegistrosCorte = RegistroPisoCorte::all();

        // Filtrar registros por fecha para seguimiento
        $registrosProduccionFiltrados = $this->filtrarRegistrosPorFecha($todosRegistrosProduccion, request());
        $registrosPolosFiltrados = $this->filtrarRegistrosPorFecha($todosRegistrosPolos, request());
        $registrosCorteFiltrados = $this->filtrarRegistrosPorFecha($todosRegistrosCorte, request());

        // Calcular seguimiento de módulos con registros filtrados
        $seguimientoProduccion = $this->calcularSeguimientoModulos($registrosProduccionFiltrados);
        $seguimientoPolos = $this->calcularSeguimientoModulos($registrosPolosFiltrados);
        $seguimientoCorte = $this->calcularSeguimientoModulos($registrosCorteFiltrados);

        return view('tableros', compact('registros', 'columns', 'registrosPolos', 'columnsPolos', 'registrosCorte', 'columnsCorte', 'seguimientoProduccion', 'seguimientoPolos', 'seguimientoCorte'));
    }

    private function aplicarFiltroFecha($query, $request)
    {
        $filterType = $request->get('filter_type');

        if (!$filterType || $filterType === 'range') {
            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');

            if ($startDate && $endDate) {
                $query->whereDate('fecha', '>=', $startDate)
                      ->whereDate('fecha', '<=', $endDate);
            }
        } elseif ($filterType === 'day') {
            $specificDate = $request->get('specific_date');
            if ($specificDate) {
                $query->whereDate('fecha', $specificDate);
            }
        } elseif ($filterType === 'month') {
            $month = $request->get('month');
            if ($month) {
                // Formato esperado: YYYY-MM
                $year = substr($month, 0, 4);
                $monthNum = substr($month, 5, 2);
                $startOfMonth = "{$year}-{$monthNum}-01";
                $endOfMonth = date('Y-m-t', strtotime($startOfMonth));
                $query->whereDate('fecha', '>=', $startOfMonth)
                      ->whereDate('fecha', '<=', $endOfMonth);
            }
        } elseif ($filterType === 'specific') {
            $specificDates = $request->get('specific_dates');
            if ($specificDates) {
                $dates = explode(',', $specificDates);
                $query->whereIn('fecha', $dates);
            }
        }
    }

    private function filtrarRegistrosPorFecha($registros, $request)
    {
        $filterType = $request->get('filter_type');

        if (!$filterType || $filterType === 'range') {
            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');

            if ($startDate && $endDate) {
                return $registros->filter(function($registro) use ($startDate, $endDate) {
                    $fecha = $registro->fecha->format('Y-m-d');
                    return $fecha >= $startDate && $fecha <= $endDate;
                });
            }
        } elseif ($filterType === 'day') {
            $specificDate = $request->get('specific_date');
            if ($specificDate) {
                return $registros->filter(function($registro) use ($specificDate) {
                    return $registro->fecha->format('Y-m-d') == $specificDate;
                });
            }
        } elseif ($filterType === 'month') {
            $month = $request->get('month');
            if ($month) {
                // Formato esperado: YYYY-MM
                $year = substr($month, 0, 4);
                $monthNum = substr($month, 5, 2);
                $startOfMonth = "{$year}-{$monthNum}-01";
                $endOfMonth = date('Y-m-t', strtotime($startOfMonth));
                return $registros->filter(function($registro) use ($startOfMonth, $endOfMonth) {
                    $fecha = $registro->fecha->format('Y-m-d');
                    return $fecha >= $startOfMonth && $fecha <= $endOfMonth;
                });
            }
        } elseif ($filterType === 'specific') {
            $specificDates = $request->get('specific_dates');
            if ($specificDates) {
                $dates = explode(',', $specificDates);
                return $registros->filter(function($registro) use ($dates) {
                    return in_array($registro->fecha->format('Y-m-d'), $dates);
                });
            }
        }

        // Si no hay filtro válido, devolver todos los registros
        return $registros;
    }

    private function calcularSeguimientoModulos($registros)
    {
        // Obtener módulos únicos de los registros y ordenarlos
        $modulosDisponibles = $registros->pluck('modulo')->unique()->values()->toArray();

        // Normalizar los nombres de módulos (trim espacios, uppercase consistente)
        $modulosDisponibles = array_map(function($mod) {
            return strtoupper(trim($mod));
        }, $modulosDisponibles);

        // Remover duplicados después de normalizar
        $modulosDisponibles = array_unique($modulosDisponibles);

        // Filtrar módulos vacíos
        $modulosDisponibles = array_filter($modulosDisponibles, function($mod) {
            return !empty(trim($mod));
        });
        $modulosDisponibles = array_values($modulosDisponibles); // reindex

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
                'count' => 0,
                'meta_sum' => 0
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
                    'count' => 0,
                    'meta_sum' => 0
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
            $meta_registro = floatval($registro->tiempo_ciclo) > 0 ? ($tiempo_disponible_registro / floatval($registro->tiempo_ciclo)) * 0.9 : 0;
            $totales['modulos'][$modulo]['meta_sum'] += $meta_registro;
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

                    $tiempo_disponible = (3600 * $avg_porcion_tiempo * $avg_numero_operarios)
                        - $total_tiempo_parada_no_programada
                        - $total_tiempo_para_programada;
                    $meta = $avg_tiempo_ciclo > 0 ? ($tiempo_disponible / $avg_tiempo_ciclo) * 0.9 : 0;
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

        return [
            'modulosDisponibles' => $modulosDisponibles,
            'dataPorHora' => $dataPorHora,
            'totales' => $totales
        ];
    }

    public function store(Request $request)
    {
        $request->validate([
            'registros' => 'required|array',
            'registros.*.fecha' => 'required|date',
            'registros.*.modulo' => 'required|string',
            'registros.*.orden_produccion' => 'required|string',
            'registros.*.hora' => 'required|string',
            'registros.*.tiempo_ciclo' => 'required|numeric',
            'registros.*.porcion_tiempo' => 'required|numeric|min:0|max:1',
            'registros.*.cantidad' => 'nullable|integer',
            'registros.*.producida' => 'nullable|integer',
            'registros.*.paradas_programadas' => 'required|string',
            'registros.*.paradas_no_programadas' => 'nullable|string',
            'registros.*.tiempo_parada_no_programada' => 'nullable|numeric',
            'registros.*.numero_operarios' => 'required|integer',
            'registros.*.tiempo_para_programada' => 'nullable|numeric',
            'registros.*.meta' => 'nullable|numeric',
            'registros.*.eficiencia' => 'nullable|numeric',
            'section' => 'required|string|in:produccion,polos,corte',
        ]);

        $model = match($request->section) {
            'produccion' => RegistroPisoProduccion::class,
            'polos' => RegistroPisoPolo::class,
            'corte' => RegistroPisoCorte::class,
        };

        try {
            $createdRecords = [];
            foreach ($request->registros as $registroData) {
                $tiempo_disponible = (3600 * $registroData['porcion_tiempo'] * $registroData['numero_operarios']) -
                                   ($registroData['tiempo_parada_no_programada'] ?? 0) -
                                   ($registroData['tiempo_para_programada'] ?? 0);

                $meta = ($tiempo_disponible / $registroData['tiempo_ciclo']) * 0.9;
                $eficiencia = $meta == 0 ? 0 : (($registroData['cantidad'] ?? 0) / $meta);

                $record = $model::create([
                    'fecha' => $registroData['fecha'],
                    'modulo' => $registroData['modulo'],
                    'orden_produccion' => $registroData['orden_produccion'],
                    'hora' => $registroData['hora'],
                    'tiempo_ciclo' => $registroData['tiempo_ciclo'],
                    'porcion_tiempo' => $registroData['porcion_tiempo'],
                    'cantidad' => $registroData['cantidad'] ?? 0,
                    'producida' => $registroData['producida'] ?? 0,
                    'paradas_programadas' => $registroData['paradas_programadas'],
                    'paradas_no_programadas' => $registroData['paradas_no_programadas'] ?? null,
                    'tiempo_parada_no_programada' => $registroData['tiempo_parada_no_programada'] ?? null,
                    'numero_operarios' => $registroData['numero_operarios'],
                    'tiempo_para_programada' => $registroData['tiempo_para_programada'] ?? 0.00,
                    'tiempo_disponible' => $tiempo_disponible,
                    'meta' => $meta,
                    'eficiencia' => $eficiencia,
                ]);

                $createdRecords[] = $record;
            }

            return response()->json([
                'success' => true,
                'message' => 'Registros guardados correctamente.',
                'registros' => $createdRecords,
                'section' => $request->section
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar los registros: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'section' => 'required|string|in:produccion,polos,corte',
        ]);

        $model = match($request->section) {
            'produccion' => RegistroPisoProduccion::class,
            'polos' => RegistroPisoPolo::class,
            'corte' => RegistroPisoCorte::class,
        };

        $registro = $model::findOrFail($id);

        $validated = $request->validate([
            'fecha' => 'sometimes|date',
            'modulo' => 'sometimes|string',
            'orden_produccion' => 'sometimes|string',
            'hora' => 'sometimes|string',
            'tiempo_ciclo' => 'sometimes|numeric',
            'porcion_tiempo' => 'sometimes|numeric|min:0|max:1',
            'cantidad' => 'sometimes|integer',
            'paradas_programadas' => 'sometimes|string',
            'paradas_no_programadas' => 'sometimes|string',
            'tiempo_parada_no_programada' => 'sometimes|numeric',
            'numero_operarios' => 'sometimes|integer',
            'tiempo_para_programada' => 'sometimes|numeric',
            'meta' => 'sometimes|numeric',
            'eficiencia' => 'sometimes|numeric',
        ]);

        try {
            $registro->update($validated);

            // Recalcular tiempo_disponible después de la actualización
            $tiempo_disponible = (3600 * $registro->porcion_tiempo * $registro->numero_operarios) -
                               ($registro->tiempo_parada_no_programada ?? 0) -
                               ($registro->tiempo_para_programada ?? 0);

            // Recalcular meta después de la actualización
            $meta = $registro->tiempo_ciclo > 0 ? ($tiempo_disponible / $registro->tiempo_ciclo) * 0.9 : 0;

            // Recalcular eficiencia después de la actualización
            $eficiencia = $meta == 0 ? 0 : ($registro->cantidad / $meta);

            $registro->tiempo_disponible = $tiempo_disponible;
            $registro->meta = $meta;
            $registro->eficiencia = $eficiencia;
            $registro->save();

            return response()->json([
                'success' => true,
                'message' => 'Registro actualizado correctamente.',
                'data' => [
                    'tiempo_disponible' => $tiempo_disponible,
                    'meta' => $meta,
                    'eficiencia' => $eficiencia
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el registro: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        $request = request();
        $section = $request->query('section');

        $model = match($section) {
            'produccion' => RegistroPisoProduccion::class,
            'polos' => RegistroPisoPolo::class,
            'corte' => RegistroPisoCorte::class,
        };

        try {
            $registro = $model::findOrFail($id);
            $registro->delete();

            return response()->json([
                'success' => true,
                'message' => 'Registro eliminado correctamente.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el registro: ' . $e->getMessage()
            ], 500);
        }
    }

    public function storeCorte(Request $request)
    {
        $request->validate([
            'fecha' => 'required|date',
            'orden_produccion' => 'required|string',
            'tela' => 'required|string',
            'hora' => 'required|string',
            'operario' => 'required|string',
            'actividad' => 'required|string',
            'maquina' => 'required|string',
            'tiempo_ciclo' => 'required|numeric',
            'porcion_tiempo' => 'required|numeric|min:0|max:1',
            'cantidad_producida' => 'required|integer',
            'paradas_programadas' => 'required|string',
            'paradas_no_programadas' => 'nullable|string',
            'tipo_extendido' => 'required|string',
            'numero_capas' => 'required|integer',
            'trazado' => 'required|string',
            'tiempo_trazado' => 'nullable|numeric',
        ]);

        try {
            $tiempo_disponible = (3600 * $request->porcion_tiempo * 1) - 0 - 0; // Asumiendo 1 operario por defecto para corte
            $meta = $request->tiempo_ciclo > 0 ? ($tiempo_disponible / $request->tiempo_ciclo) * 0.9 : 0;
            $eficiencia = $meta == 0 ? 0 : ($request->cantidad_producida / $meta);

            $registro = RegistroPisoCorte::create([
                'fecha' => $request->fecha,
                'orden_produccion' => $request->orden_produccion,
                'tela' => $request->tela,
                'hora' => $request->hora,
                'operario' => $request->operario,
                'actividad' => $request->actividad,
                'maquina' => $request->maquina,
                'tiempo_ciclo' => $request->tiempo_ciclo,
                'porcion_tiempo' => $request->porcion_tiempo,
                'cantidad' => $request->cantidad_producida,
                'paradas_programadas' => $request->paradas_programadas,
                'paradas_no_programadas' => $request->paradas_no_programadas,
                'numero_operarios' => 1, // Asumiendo 1 operario por defecto
                'tiempo_disponible' => $tiempo_disponible,
                'meta' => $meta,
                'eficiencia' => $eficiencia,
                'tipo_extendido' => $request->tipo_extendido,
                'numero_capas' => $request->numero_capas,
                'trazado' => $request->trazado,
                'tiempo_trazado' => $request->tiempo_trazado,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Registro de piso de corte guardado correctamente.',
                'registro' => $registro
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar el registro: ' . $e->getMessage()
            ], 500);
        }
    }
}
