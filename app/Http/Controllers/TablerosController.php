<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use App\Models\RegistroPisoProduccion;
use App\Models\RegistroPisoPolo;
use App\Models\RegistroPisoCorte;
use App\Models\User;
use App\Models\Hora;
use App\Models\Maquina;
use App\Models\Tela;
use App\Models\TiempoCiclo;
use App\Events\ProduccionRecordCreated;
use App\Events\PoloRecordCreated;
use App\Events\CorteRecordCreated;

class TablerosController extends Controller
{
    public function fullscreen(Request $request)
    {
        $section = $request->get('section', 'produccion');
        
        // Obtener todos los registros según la sección
        $registros = match($section) {
            'produccion' => RegistroPisoProduccion::all(),
            'polos' => RegistroPisoPolo::all(),
            'corte' => RegistroPisoCorte::with(['hora', 'operario', 'maquina', 'tela'])->get(),
            default => RegistroPisoProduccion::all(),
        };
        
        // Filtrar registros por fecha si hay filtros
        $registrosFiltrados = $this->filtrarRegistrosPorFecha($registros, $request);
        
        // Calcular seguimiento de módulos
        $seguimiento = $this->calcularSeguimientoModulos($registrosFiltrados);
        
        return view('tableros-fullscreen', compact('seguimiento', 'section'));
    }

    public function corteFullscreen(Request $request)
    {
        // Obtener todos los registros de corte
        $registrosCorte = RegistroPisoCorte::with(['hora', 'operario', 'maquina', 'tela'])->get();
        
        // Filtrar registros por fecha si hay filtros
        $registrosCorteFiltrados = $this->filtrarRegistrosPorFecha($registrosCorte, $request);
        
        // Calcular datos dinámicos para las tablas
        $horasData = $this->calcularProduccionPorHoras($registrosCorteFiltrados);
        $operariosData = $this->calcularProduccionPorOperarios($registrosCorteFiltrados);
        
        return view('tableros-corte-fullscreen', compact('horasData', 'operariosData'));
    }

    public function index()
    {
        // Optimización: Si es AJAX con parámetro 'section', solo cargar esa sección
        $section = request()->get('section');
        $isAjax = request()->ajax() || request()->wantsJson();
        
        // Si es AJAX y especifica una sección, devolver solo esa tabla
        if ($isAjax && $section) {
            return $this->loadSection($section);
        }
        
        // TABLAS PRINCIPALES: SIN FILTRO DE FECHA (mostrar todos los registros)
        // Orden descendente: registros más recientes primero
        $queryProduccion = RegistroPisoProduccion::query()->orderBy('id', 'desc');
        // Paginación: 50 registros por página
        $registros = $queryProduccion->paginate(50);
        $columns = Schema::getColumnListing('registro_piso_produccion');
        $columns = array_diff($columns, ['id', 'created_at', 'updated_at', 'producida']);

        $queryPolos = RegistroPisoPolo::query()->orderBy('id', 'desc');
        $registrosPolos = $queryPolos->paginate(50);
        $columnsPolos = Schema::getColumnListing('registro_piso_polo');
        $columnsPolos = array_diff($columnsPolos, ['id', 'created_at', 'updated_at', 'producida']);

        $queryCorte = RegistroPisoCorte::with(['hora', 'operario', 'maquina', 'tela'])->orderBy('id', 'desc');
        $registrosCorte = $queryCorte->paginate(50);
        $columnsCorte = Schema::getColumnListing('registro_piso_corte');
        $columnsCorte = array_diff($columnsCorte, ['id', 'created_at', 'updated_at', 'producida']);

        if (request()->wantsJson()) {
            return response()->json([
                'registros' => $registros->items(),
                'columns' => array_values($columns),
                'registrosPolos' => $registrosPolos->items(),
                'columnsPolos' => array_values($columnsPolos),
                'registrosCorte' => $registrosCorte->map(function($registro) {
                    $registroArray = $registro->toArray();
                    if ($registro->hora) {
                        $registroArray['hora_display'] = $registro->hora->hora;
                    }
                    if ($registro->operario) {
                        $registroArray['operario_display'] = $registro->operario->name;
                    }
                    if ($registro->maquina) {
                        $registroArray['maquina_display'] = $registro->maquina->nombre_maquina;
                    }
                    if ($registro->tela) {
                        $registroArray['tela_display'] = $registro->tela->nombre_tela;
                    }
                    return $registroArray;
                })->toArray(),
                'columnsCorte' => array_values($columnsCorte),
                'pagination' => [
                    'current_page' => $registros->currentPage(),
                    'last_page' => $registros->lastPage(),
                    'per_page' => $registros->perPage(),
                    'total' => $registros->total(),
                    'first_item' => $registros->firstItem(),
                    'last_item' => $registros->lastItem(),
                    'links_html' => $registros->appends(request()->query())->links('vendor.pagination.custom')->render()
                ],
                'paginationPolos' => [
                    'current_page' => $registrosPolos->currentPage(),
                    'last_page' => $registrosPolos->lastPage(),
                    'per_page' => $registrosPolos->perPage(),
                    'total' => $registrosPolos->total(),
                    'first_item' => $registrosPolos->firstItem(),
                    'last_item' => $registrosPolos->lastItem(),
                    'links_html' => $registrosPolos->appends(request()->query())->links('vendor.pagination.custom')->render()
                ],
                'paginationCorte' => [
                    'current_page' => $registrosCorte->currentPage(),
                    'last_page' => $registrosCorte->lastPage(),
                    'per_page' => $registrosCorte->perPage(),
                    'total' => $registrosCorte->total(),
                    'first_item' => $registrosCorte->firstItem(),
                    'last_item' => $registrosCorte->lastItem(),
                    'links_html' => $registrosCorte->appends(request()->query())->links('vendor.pagination.custom')->render()
                ]
            ]);
        }

        // Obtener todos los registros para seguimiento
        $todosRegistrosProduccion = RegistroPisoProduccion::all();
        $todosRegistrosPolos = RegistroPisoPolo::all();
        $todosRegistrosCorte = RegistroPisoCorte::with(['hora', 'operario', 'maquina', 'tela'])->get();

        // Filtrar registros por fecha SOLO para el tablero activo
        $activeSection = request()->get('active_section', 'produccion');
        
        // Por defecto, usar todos los registros (sin filtro)
        $registrosProduccionFiltrados = $todosRegistrosProduccion;
        $registrosPolosFiltrados = $todosRegistrosPolos;
        $registrosCorteFiltrados = $todosRegistrosCorte;
        
        // Aplicar filtro solo al tablero activo
        if ($activeSection === 'produccion') {
            $registrosProduccionFiltrados = $this->filtrarRegistrosPorFecha($todosRegistrosProduccion, request());
        } elseif ($activeSection === 'polos') {
            $registrosPolosFiltrados = $this->filtrarRegistrosPorFecha($todosRegistrosPolos, request());
        } elseif ($activeSection === 'corte') {
            $registrosCorteFiltrados = $this->filtrarRegistrosPorFecha($todosRegistrosCorte, request());
        }

        // Calcular seguimiento de módulos con registros filtrados
        $seguimientoProduccion = $this->calcularSeguimientoModulos($registrosProduccionFiltrados);
        $seguimientoPolos = $this->calcularSeguimientoModulos($registrosPolosFiltrados);
        $seguimientoCorte = $this->calcularSeguimientoModulos($registrosCorteFiltrados);
        
        // Calcular datos dinámicos para las tablas de horas y operarios CON FILTROS
        $horasData = $this->calcularProduccionPorHoras($registrosCorteFiltrados);
        $operariosData = $this->calcularProduccionPorOperarios($registrosCorteFiltrados);

        // Obtener datos para selects en el formulario de corte
        $horas = Hora::all();
        $operarios = User::whereHas('role', function($query) {
            $query->where('name', 'cortador');
        })->get();
        $maquinas = Maquina::all();
        $telas = Tela::all();

        return view('tableros', compact('registros', 'columns', 'registrosPolos', 'columnsPolos', 'registrosCorte', 'columnsCorte', 'seguimientoProduccion', 'seguimientoPolos', 'seguimientoCorte', 'horas', 'operarios', 'maquinas', 'telas', 'horasData', 'operariosData'));
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
                'tiempo_disponible_sum' => 0,
                'meta_sum' => 0,
                'count' => 0
            ];
        }

        // Acumular datos por hora y módulo
        foreach ($registros as $registro) {
            // Handle both relationship (object) and direct field (string)
            $hora = is_object($registro->hora) ? $registro->hora->hora : ($registro->hora ?? 'Sin hora');
            $hora = !empty(trim($hora)) ? trim($hora) : 'Sin hora';
            $modulo = !empty(trim($registro->modulo)) ? strtoupper(trim($registro->modulo)) : 'SIN MÓDULO';

            if (!isset($dataPorHora[$hora])) {
                $dataPorHora[$hora] = ['modulos' => []];
            }

            if (!isset($dataPorHora[$hora]['modulos'][$modulo])) {
                $dataPorHora[$hora]['modulos'][$modulo] = [
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

            $dataPorHora[$hora]['modulos'][$modulo]['prendas'] += floatval($registro->cantidad ?? 0);
            $dataPorHora[$hora]['modulos'][$modulo]['tiempo_ciclo_sum'] += floatval($registro->tiempo_ciclo ?? 0);
            $dataPorHora[$hora]['modulos'][$modulo]['numero_operarios_sum'] += floatval($registro->numero_operarios ?? 0);
            $dataPorHora[$hora]['modulos'][$modulo]['porcion_tiempo_sum'] += floatval($registro->porcion_tiempo ?? 0);
            $dataPorHora[$hora]['modulos'][$modulo]['tiempo_parada_no_programada_sum'] += floatval($registro->tiempo_parada_no_programada ?? 0);
            $dataPorHora[$hora]['modulos'][$modulo]['tiempo_para_programada_sum'] += floatval($registro->tiempo_para_programada ?? 0);
            $dataPorHora[$hora]['modulos'][$modulo]['count']++;

            // Inicializar módulo en totales si no existe
            if (!isset($totales['modulos'][$modulo])) {
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

            // Acumular totales generales
            $totales['modulos'][$modulo]['prendas'] += floatval($registro->cantidad ?? 0);
            $totales['modulos'][$modulo]['tiempo_ciclo_sum'] += floatval($registro->tiempo_ciclo ?? 0);
            $totales['modulos'][$modulo]['numero_operarios_sum'] += floatval($registro->numero_operarios ?? 0);
            $totales['modulos'][$modulo]['porcion_tiempo_sum'] += floatval($registro->porcion_tiempo ?? 0);
            $totales['modulos'][$modulo]['tiempo_parada_no_programada_sum'] += floatval($registro->tiempo_parada_no_programada ?? 0);
            $totales['modulos'][$modulo]['tiempo_para_programada_sum'] += floatval($registro->tiempo_para_programada ?? 0);
            $totales['modulos'][$modulo]['count']++;

            // Usar la meta que ya está calculada en el registro
            $meta_registro = floatval($registro->meta ?? 0);
            $dataPorHora[$hora]['modulos'][$modulo]['meta_sum'] += $meta_registro;
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
                $paradaProgramada = strtoupper(trim($registroData['paradas_programadas'] ?? ''));
                $tiempo_para_programada = match ($paradaProgramada) {
                    'DESAYUNO',
                    'MEDIA TARDE' => 900,
                    'NINGUNA' => 0,
                    default => 0
                };

                $porcion_tiempo = floatval($registroData['porcion_tiempo'] ?? 0);
                $numero_operarios = floatval($registroData['numero_operarios'] ?? 0);
                $tiempo_parada_no_programada = floatval($registroData['tiempo_parada_no_programada'] ?? 0);
                $tiempo_ciclo = floatval($registroData['tiempo_ciclo'] ?? 0);
                $cantidad = floatval($registroData['cantidad'] ?? 0);

                $tiempo_disponible = (3600 * $porcion_tiempo * $numero_operarios)
                                    - $tiempo_parada_no_programada
                                    - $tiempo_para_programada;
                $tiempo_disponible = max(0, $tiempo_disponible);

                $meta = $tiempo_ciclo > 0 ? ($tiempo_disponible / $tiempo_ciclo) * 0.9 : 0;
                $eficiencia = $meta > 0 ? ($cantidad / $meta) : 0;

                $record = $model::create([
                    'fecha' => $registroData['fecha'],
                    'modulo' => $registroData['modulo'],
                    'orden_produccion' => $registroData['orden_produccion'],
                    'hora' => $registroData['hora'],
                    'tiempo_ciclo' => $registroData['tiempo_ciclo'],
                    'porcion_tiempo' => $registroData['porcion_tiempo'],
                    'cantidad' => $registroData['cantidad'] ?? 0,
                    'paradas_programadas' => $registroData['paradas_programadas'],
                    'paradas_no_programadas' => $registroData['paradas_no_programadas'] ?? null,
                    'tiempo_parada_no_programada' => $registroData['tiempo_parada_no_programada'] ?? null,
                    'numero_operarios' => $registroData['numero_operarios'],
                    'tiempo_para_programada' => $tiempo_para_programada,
                    'tiempo_disponible' => $tiempo_disponible,
                    'meta' => $meta,
                    'eficiencia' => $eficiencia,
                ]);

                $createdRecords[] = $record;
                
                // Broadcast event for real-time updates (non-blocking)
                try {
                    if ($request->section === 'produccion') {
                        broadcast(new ProduccionRecordCreated($record));
                    } elseif ($request->section === 'polos') {
                        broadcast(new PoloRecordCreated($record));
                    }
                } catch (\Exception $broadcastError) {
                    \Log::warning('Error al emitir evento de creación', [
                        'error' => $broadcastError->getMessage(),
                        'section' => $request->section
                    ]);
                }
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
            'operario_id' => 'sometimes|integer|exists:users,id',
            'maquina_id' => 'sometimes|integer|exists:maquinas,id',
            'tela_id' => 'sometimes|integer|exists:telas,id',
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

            // Recalcular siempre que se actualice cualquier campo que afecte los cálculos
            // Esto incluye: tiempo_ciclo, porcion_tiempo, numero_operarios, paradas, cantidad, etc.
            $fieldsToRecalculate = [
                'porcion_tiempo', 
                'numero_operarios', 
                'tiempo_parada_no_programada', 
                'tiempo_para_programada', 
                'tiempo_ciclo', 
                'cantidad',
                'paradas_programadas',
                'paradas_no_programadas',
                'tipo_extendido',
                'numero_capas',
                'tiempo_trazado'
            ];
            
            $shouldRecalculate = false;
            foreach ($fieldsToRecalculate as $field) {
                if (array_key_exists($field, $validated)) {
                    $shouldRecalculate = true;
                    break;
                }
            }

            if ($shouldRecalculate) {
                // Recalcular según la sección
                if ($request->section === 'corte') {
                    // Fórmula para CORTE (sin numero_operarios)
                    $tiempo_para_programada = match($registro->paradas_programadas) {
                        'DESAYUNO' => 900,
                        'MEDIA TARDE' => 900,
                        'NINGUNA' => 0,
                        default => 0
                    };

                    $tiempo_extendido = match($registro->tipo_extendido) {
                        'Trazo Largo' => 40 * ($registro->numero_capas ?? 0),
                        'Trazo Corto' => 25 * ($registro->numero_capas ?? 0),
                        'Ninguno' => 0,
                        default => 0
                    };

                    $tiempo_disponible = (3600 * $registro->porcion_tiempo) -
                                       ($tiempo_para_programada +
                                       ($registro->tiempo_parada_no_programada ?? 0) +
                                       $tiempo_extendido +
                                       ($registro->tiempo_trazado ?? 0));

                    $tiempo_disponible = max(0, $tiempo_disponible);

                    // Meta: tiempo_disponible / tiempo_ciclo (SIN multiplicar por 0.9)
                    $meta = $registro->tiempo_ciclo > 0 ? $tiempo_disponible / $registro->tiempo_ciclo : 0;
                    
                    // Eficiencia: cantidad / meta (SIN multiplicar por 100)
                    $eficiencia = $meta > 0 ? ($registro->cantidad / $meta) : 0;
                } else {
                    // Fórmula para PRODUCCIÓN y POLOS (con numero_operarios)
                    $tiempo_para_programada = match($registro->paradas_programadas) {
                        'DESAYUNO' => 900,
                        'MEDIA TARDE' => 900,
                        'NINGUNA' => 0,
                        default => 0
                    };

                    $tiempo_disponible = (3600 * $registro->porcion_tiempo * $registro->numero_operarios) -
                                       ($registro->tiempo_parada_no_programada ?? 0) -
                                       $tiempo_para_programada;

                    // Meta: (tiempo_disponible / tiempo_ciclo) * 0.9
                    $meta = $registro->tiempo_ciclo > 0 ? ($tiempo_disponible / $registro->tiempo_ciclo) * 0.9 : 0;
                    
                    // Eficiencia: cantidad / meta (SIN multiplicar por 100)
                    $eficiencia = $meta > 0 ? ($registro->cantidad / $meta) : 0;
                }

                $registro->tiempo_disponible = $tiempo_disponible;
                $registro->meta = $meta;
                $registro->eficiencia = $eficiencia;
                $registro->save();

                // Broadcast event for real-time updates (non-blocking)
                try {
                    if ($request->section === 'produccion') {
                        broadcast(new ProduccionRecordCreated($registro));
                    } elseif ($request->section === 'polos') {
                        broadcast(new PoloRecordCreated($registro));
                    } elseif ($request->section === 'corte') {
                        $registro->load(['hora', 'operario', 'maquina', 'tela']);
                        broadcast(new CorteRecordCreated($registro));
                    }
                } catch (\Exception $broadcastError) {
                    \Log::warning('Error al emitir evento de actualización', [
                        'error' => $broadcastError->getMessage(),
                        'section' => $request->section
                    ]);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Registro actualizado correctamente.',
                    'data' => [
                        'tiempo_disponible' => $tiempo_disponible,
                        'meta' => $meta,
                        'eficiencia' => $eficiencia
                    ]
                ]);
            } else {
                // No recalcular, solo actualizar y emitir evento (non-blocking)
                try {
                    if ($request->section === 'produccion') {
                        broadcast(new ProduccionRecordCreated($registro));
                    } elseif ($request->section === 'polos') {
                        broadcast(new PoloRecordCreated($registro));
                    } elseif ($request->section === 'corte') {
                        $registro->load(['hora', 'operario', 'maquina', 'tela']);
                        broadcast(new CorteRecordCreated($registro));
                    }
                } catch (\Exception $broadcastError) {
                    \Log::warning('Error al emitir evento de actualización', [
                        'error' => $broadcastError->getMessage(),
                        'section' => $request->section
                    ]);
                }
                
                return response()->json([
                    'success' => true,
                    'message' => 'Registro actualizado correctamente.',
                ]);
            }
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
            $registro = $model::find($id);
            
            // Si el registro no existe, ya fue eliminado
            if (!$registro) {
                return response()->json([
                    'success' => true,
                    'message' => 'El registro ya fue eliminado.',
                    'id' => $id,
                    'already_deleted' => true
                ]);
            }
            
            // Guardar el ID antes de eliminar
            $registroId = $registro->id;
            
            $registro->delete();

            // Emitir evento de eliminación via WebSocket
            try {
                if ($section === 'produccion') {
                    broadcast(new ProduccionRecordCreated((object)['id' => $registroId, 'deleted' => true]));
                } elseif ($section === 'polos') {
                    broadcast(new PoloRecordCreated((object)['id' => $registroId, 'deleted' => true]));
                } elseif ($section === 'corte') {
                    broadcast(new CorteRecordCreated((object)['id' => $registroId, 'deleted' => true]));
                }
            } catch (\Exception $broadcastError) {
                \Log::warning('Error al emitir evento de eliminación', [
                    'error' => $broadcastError->getMessage(),
                    'section' => $section
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Registro eliminado correctamente.',
                'id' => $registroId
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al eliminar registro', [
                'error' => $e->getMessage(),
                'id' => $id,
                'section' => $section
            ]);
            
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
            'tela_id' => 'required|exists:telas,id',
            'hora_id' => 'required|exists:horas,id',
            'operario_id' => 'required|exists:users,id',
            'actividad' => 'required|string',
            'maquina_id' => 'required|exists:maquinas,id',
            'tiempo_ciclo' => 'required|numeric',
            'porcion_tiempo' => 'required|numeric|min:0|max:1',
            'cantidad_producida' => 'required|integer',
            'paradas_programadas' => 'required|string',
            'paradas_no_programadas' => 'nullable|string',
            'tiempo_parada_no_programada' => 'nullable|numeric',
            'tipo_extendido' => 'required|string',
            'numero_capas' => 'required|integer',
            'trazado' => 'required|string',
            'tiempo_trazado' => 'nullable|numeric',
        ]);

        try {
            // Check if tiempo_ciclo exists for this tela and maquina, if not, create it
            $tiempoCiclo = TiempoCiclo::where('tela_id', $request->tela_id)
                ->where('maquina_id', $request->maquina_id)
                ->first();

            if (!$tiempoCiclo) {
                TiempoCiclo::create([
                    'tela_id' => $request->tela_id,
                    'maquina_id' => $request->maquina_id,
                    'tiempo_ciclo' => $request->tiempo_ciclo,
                ]);
            }

            // Calculate tiempo_para_programada based on paradas_programadas
            $tiempo_para_programada = 0;
            if ($request->paradas_programadas === 'DESAYUNO' || $request->paradas_programadas === 'MEDIA TARDE') {
                $tiempo_para_programada = 900; // 15 minutes in seconds
            } elseif ($request->paradas_programadas === 'NINGUNA') {
                $tiempo_para_programada = 0;
            }

            // Calculate tiempo_extendido based on tipo_extendido and numero_capas
            $tiempo_extendido = 0;
            $tipo_extendido_lower = strtolower($request->tipo_extendido);
            
            if (str_contains($tipo_extendido_lower, 'largo')) {
                $tiempo_extendido = 40 * $request->numero_capas;
            } elseif (str_contains($tipo_extendido_lower, 'corto')) {
                $tiempo_extendido = 25 * $request->numero_capas;
            } else {
                $tiempo_extendido = 0;
            }

            // Calculate tiempo_disponible: (3600 * porcion_tiempo) - (tiempo_para_programada + tiempo_parada_no_programada + tiempo_extendido + tiempo_trazado)
            $tiempo_disponible = (3600 * $request->porcion_tiempo) -
                               $tiempo_para_programada -
                               ($request->tiempo_parada_no_programada ?? 0) -
                               $tiempo_extendido -
                               ($request->tiempo_trazado ?? 0);

            // Ensure tiempo_disponible is not negative
            $tiempo_disponible = max(0, $tiempo_disponible);

            // Calculate meta and eficiencia based on activity (case insensitive)
            if (str_contains(strtolower($request->actividad), 'extender') || str_contains(strtolower($request->actividad), 'trazar')) {
                // For activities containing "extender" or "trazar", meta is the cantidad_producida, eficiencia is 1 (100%)
                $meta = $request->cantidad_producida;
                $eficiencia = 1;
            } else {
                // Calculate meta: tiempo_disponible / tiempo_ciclo
                $meta = $request->tiempo_ciclo > 0 ? $tiempo_disponible / $request->tiempo_ciclo : 0;
                // Calculate eficiencia: cantidad_producida / meta (SIN multiplicar por 100)
                $eficiencia = $meta == 0 ? 0 : $request->cantidad_producida / $meta;
            }

            $registro = RegistroPisoCorte::create([
                'fecha' => $request->fecha,
                'orden_produccion' => $request->orden_produccion,
                'hora_id' => $request->hora_id,
                'operario_id' => $request->operario_id,
                'maquina_id' => $request->maquina_id,
                'porcion_tiempo' => $request->porcion_tiempo,
                'cantidad' => $request->cantidad_producida,
                'tiempo_ciclo' => $request->tiempo_ciclo,
                'paradas_programadas' => $request->paradas_programadas,
                'tiempo_para_programada' => $tiempo_para_programada,
                'paradas_no_programadas' => $request->paradas_no_programadas,
                'tiempo_parada_no_programada' => $request->tiempo_parada_no_programada ?? null,
                'tipo_extendido' => $request->tipo_extendido,
                'numero_capas' => $request->numero_capas,
                'tiempo_extendido' => $tiempo_extendido,
                'trazado' => $request->trazado,
                'tiempo_trazado' => $request->tiempo_trazado,
                'actividad' => $request->actividad,
                'tela_id' => $request->tela_id,
                'tiempo_disponible' => $tiempo_disponible,
                'meta' => $meta,
                'eficiencia' => $eficiencia,
            ]);

            // Load relations for broadcasting
            $registro->load(['hora', 'operario', 'maquina', 'tela']);

            // Broadcast the new record to ALL clients (non-blocking)
            try {
                broadcast(new CorteRecordCreated($registro));
            } catch (\Exception $broadcastError) {
                \Log::warning('Error al emitir evento de corte', [
                    'error' => $broadcastError->getMessage()
                ]);
            }

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

    public function getTiempoCiclo(Request $request)
    {
        $request->validate([
            'tela_id' => 'required|exists:telas,id',
            'maquina_id' => 'required|exists:maquinas,id',
        ]);

        $tiempoCiclo = TiempoCiclo::where('tela_id', $request->tela_id)
            ->where('maquina_id', $request->maquina_id)
            ->first();

        if ($tiempoCiclo) {
            return response()->json([
                'success' => true,
                'tiempo_ciclo' => $tiempoCiclo->tiempo_ciclo
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró tiempo de ciclo para esta combinación de tela y máquina.'
            ]);
        }
    }

    public function storeTela(Request $request)
    {
        $request->validate([
            'nombre_tela' => 'required|string|unique:telas,nombre_tela',
        ]);

        try {
            $tela = Tela::create([
                'nombre_tela' => $request->nombre_tela,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Tela creada correctamente.',
                'tela' => $tela
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la tela: ' . $e->getMessage()
            ], 500);
        }
    }

    public function searchTelas(Request $request)
    {
        $query = strtoupper($request->get('q', ''));
        $telas = Tela::where('nombre_tela', 'like', '%' . $query . '%')
            ->select('id', 'nombre_tela')
            ->limit(10)
            ->get();

        return response()->json(['telas' => $telas]);
    }

    public function storeMaquina(Request $request)
    {
        $request->validate([
            'nombre_maquina' => 'required|string|unique:maquinas,nombre_maquina',
        ]);

        try {
            $maquina = Maquina::create([
                'nombre_maquina' => $request->nombre_maquina,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Máquina creada correctamente.',
                'maquina' => $maquina
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la máquina: ' . $e->getMessage()
            ], 500);
        }
    }

    public function searchMaquinas(Request $request)
    {
        $query = strtoupper($request->get('q', ''));
        $maquinas = Maquina::where('nombre_maquina', 'like', '%' . $query . '%')
            ->select('id', 'nombre_maquina')
            ->limit(10)
            ->get();

        return response()->json(['maquinas' => $maquinas]);
    }

    public function searchOperarios(Request $request)
    {
        $query = strtoupper($request->get('q', ''));
        $operarios = User::where('name', 'like', '%' . $query . '%')
            ->select('id', 'name')
            ->limit(10)
            ->get();

        return response()->json(['operarios' => $operarios]);
    }

    public function storeOperario(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:users,name',
        ]);

        try {
            $operario = User::create([
                'name' => strtoupper($request->name),
                'email' => strtolower(str_replace(' ', '.', $request->name)) . '@example.com', // Generate email
                'password' => bcrypt('password'), // Default password
                'role_id' => 3, // Cortador role id is 3
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Operario creado correctamente.',
                'operario' => $operario
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el operario: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getDashboardCorteData(Request $request)
    {
        $fecha = $request->input('fecha', now()->toDateString());
        
        $registrosCorte = RegistroPisoCorte::whereDate('fecha', $fecha)
            ->with(['hora', 'operario'])
            ->get();
        
        $horasData = $this->calcularProduccionPorHoras($registrosCorte);
        $operariosData = $this->calcularProduccionPorOperarios($registrosCorte);
        
        return response()->json([
            'horas' => $horasData,
            'operarios' => $operariosData
        ]);
    }

    private function calcularProduccionPorHoras($registrosCorte)
    {
        $horasData = [];

        foreach ($registrosCorte as $registro) {
            $horaOriginal = $registro->hora ? $registro->hora->hora : 'SIN HORA';
            
            // Formatear la hora como "HORA 1", "HORA 2", etc.
            if ($horaOriginal !== 'SIN HORA' && is_numeric($horaOriginal)) {
                $hora = 'HORA ' . $horaOriginal;
            } else {
                $hora = $horaOriginal;
            }
            
            if (!isset($horasData[$hora])) {
                $horasData[$hora] = [
                    'hora' => $hora,
                    'cantidad' => 0,
                    'meta' => 0,
                    'eficiencia' => 0
                ];
            }
            $horasData[$hora]['cantidad'] += $registro->cantidad ?? 0;
            $horasData[$hora]['meta'] += $registro->meta ?? 0;
        }

        // Calcular eficiencia para cada hora
        foreach ($horasData as &$horaData) {
            if ($horaData['meta'] > 0) {
                $horaData['eficiencia'] = round(($horaData['cantidad'] / $horaData['meta']) * 100, 1);
            } else {
                $horaData['eficiencia'] = 0;
            }
        }

        // Ordenar por hora (asumiendo formato HORA XX)
        uasort($horasData, function($a, $b) {
            $numA = (int) preg_replace('/\D/', '', $a['hora']);
            $numB = (int) preg_replace('/\D/', '', $b['hora']);
            return $numA <=> $numB;
        });

        return array_values($horasData);
    }

    private function calcularProduccionPorOperarios($registrosCorte)
    {
        $operariosData = [];

        foreach ($registrosCorte as $registro) {
            $operario = $registro->operario ? $registro->operario->name : 'SIN OPERARIO';
            if (!isset($operariosData[$operario])) {
                $operariosData[$operario] = [
                    'operario' => $operario,
                    'cantidad' => 0,
                    'meta' => 0,
                    'eficiencia' => 0
                ];
            }
            $operariosData[$operario]['cantidad'] += $registro->cantidad ?? 0;
            $operariosData[$operario]['meta'] += $registro->meta ?? 0;
        }

        // Calcular eficiencia para cada operario
        foreach ($operariosData as &$operarioData) {
            if ($operarioData['meta'] > 0) {
                $operarioData['eficiencia'] = round(($operarioData['cantidad'] / $operarioData['meta']) * 100, 1);
            } else {
                $operarioData['eficiencia'] = 0;
            }
        }

        // Ordenar alfabéticamente por operario
        ksort($operariosData);

        return array_values($operariosData);
    }

    public function getDashboardTablesData(Request $request)
    {
        $queryCorte = RegistroPisoCorte::with(['hora', 'operario', 'maquina', 'tela']);
        $this->aplicarFiltroFecha($queryCorte, $request);
        $registrosCorte = $queryCorte->get();

        // Calcular datos dinámicos para las tablas de horas y operarios
        $horasData = $this->calcularProduccionPorHoras($registrosCorte);
        $operariosData = $this->calcularProduccionPorOperarios($registrosCorte);

        return response()->json([
            'horasData' => $horasData,
            'operariosData' => $operariosData
        ]);
    }

    public function getSeguimientoData(Request $request)
    {
        $section = $request->get('section', 'produccion');

        $model = match($section) {
            'produccion' => RegistroPisoProduccion::class,
            'polos' => RegistroPisoPolo::class,
            'corte' => RegistroPisoCorte::class,
        };

        $query = $model::query();
        $this->aplicarFiltroFecha($query, $request);
        $registrosFiltrados = $query->get();

        $seguimiento = $this->calcularSeguimientoModulos($registrosFiltrados);

        return response()->json($seguimiento);
    }

    /**
     * Crear o buscar operario por nombre
     */
    public function findOrCreateOperario(Request $request)
    {
        $name = strtoupper($request->input('name'));
        
        $operario = User::firstOrCreate(
            ['name' => $name],
            ['email' => strtolower(str_replace(' ', '', $name)) . '@mundoindustrial.com', 'password' => bcrypt('password123')]
        );

        return response()->json([
            'id' => $operario->id,
            'name' => $operario->name
        ]);
    }

    /**
     * Crear o buscar máquina por nombre
     */
    public function findOrCreateMaquina(Request $request)
    {
        $nombre = strtoupper($request->input('nombre'));
        
        $maquina = Maquina::firstOrCreate(
            ['nombre_maquina' => $nombre]
        );

        return response()->json([
            'id' => $maquina->id,
            'nombre_maquina' => $maquina->nombre_maquina
        ]);
    }

    /**
     * Cargar solo una sección específica (OPTIMIZACIÓN AJAX)
     */
    private function loadSection($section)
    {
        $startTime = microtime(true);
        
        if ($section === 'produccion') {
            $registros = RegistroPisoProduccion::query()->orderBy('id', 'desc')->paginate(50);
            $columns = Schema::getColumnListing('registro_piso_produccion');
            $columns = array_diff($columns, ['id', 'created_at', 'updated_at', 'producida']);
            
            // Renderizar HTML de la tabla
            $tableHtml = view('partials.table-body-produccion', compact('registros', 'columns'))->render();
            
            $endTime = microtime(true);
            $duration = ($endTime - $startTime) * 1000;
            
            return response()->json([
                'table_html' => $tableHtml,
                'pagination' => [
                    'current_page' => $registros->currentPage(),
                    'last_page' => $registros->lastPage(),
                    'per_page' => $registros->perPage(),
                    'total' => $registros->total(),
                    'first_item' => $registros->firstItem(),
                    'last_item' => $registros->lastItem(),
                    'links_html' => $registros->appends(request()->query())->links('vendor.pagination.custom')->render()
                ],
                'debug' => [
                    'server_time_ms' => round($duration, 2),
                    'section' => $section
                ]
            ]);
        } elseif ($section === 'polos') {
            $registros = RegistroPisoPolo::query()->orderBy('id', 'desc')->paginate(50);
            $columns = Schema::getColumnListing('registro_piso_polo');
            $columns = array_diff($columns, ['id', 'created_at', 'updated_at', 'producida']);
            
            // Renderizar HTML de la tabla
            $tableHtml = view('partials.table-body-polos', compact('registros', 'columns'))->render();
            
            $endTime = microtime(true);
            $duration = ($endTime - $startTime) * 1000;
            
            return response()->json([
                'table_html' => $tableHtml,
                'pagination' => [
                    'current_page' => $registros->currentPage(),
                    'last_page' => $registros->lastPage(),
                    'per_page' => $registros->perPage(),
                    'total' => $registros->total(),
                    'first_item' => $registros->firstItem(),
                    'last_item' => $registros->lastItem(),
                    'links_html' => $registros->appends(request()->query())->links('vendor.pagination.custom')->render()
                ],
                'debug' => [
                    'server_time_ms' => round($duration, 2),
                    'section' => $section
                ]
            ]);
        } elseif ($section === 'corte') {
            $registros = RegistroPisoCorte::with(['hora', 'operario', 'maquina', 'tela'])->orderBy('id', 'desc')->paginate(50);
            $columns = Schema::getColumnListing('registro_piso_corte');
            $columns = array_diff($columns, ['id', 'created_at', 'updated_at', 'producida']);
            
            // Renderizar HTML de la tabla
            $tableHtml = view('partials.table-body-corte', compact('registros', 'columns'))->render();
            
            $endTime = microtime(true);
            $duration = ($endTime - $startTime) * 1000;
            
            return response()->json([
                'table_html' => $tableHtml,
                'pagination' => [
                    'current_page' => $registros->currentPage(),
                    'last_page' => $registros->lastPage(),
                    'per_page' => $registros->perPage(),
                    'total' => $registros->total(),
                    'first_item' => $registros->firstItem(),
                    'last_item' => $registros->lastItem(),
                    'links_html' => $registros->appends(request()->query())->links('vendor.pagination.custom')->render()
                ],
                'debug' => [
                    'server_time_ms' => round($duration, 2),
                    'section' => $section
                ]
            ]);
        }
        
        return response()->json(['error' => 'Invalid section'], 400);
    }

    /**
     * Crear o buscar tela por nombre
     */
    public function findOrCreateTela(Request $request)
    {
        $nombre = strtoupper($request->input('nombre'));
        
        $tela = Tela::firstOrCreate(
            ['nombre_tela' => $nombre]
        );

        return response()->json([
            'id' => $tela->id,
            'nombre_tela' => $tela->nombre_tela
        ]);
    }
}
