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

        // ⚡ OPTIMIZACIÓN CRÍTICA: En la página inicial, limitar a últimos 30 días
        // si no hay filtros aplicados. Esto hace que cargue mucho más rápido
        $limit_days = 30; // Mostrar últimos 30 días por defecto
        $hasFilters = request()->has('filters') || request()->has('filter_type') || 
                      request()->has('start_date') || request()->has('end_date');
        
        if (!$hasFilters) {
            // Sin filtros, usar límite de 30 días
            $start_date = now()->subDays($limit_days);
        } else {
            // Con filtros, dejar que filtrarRegistrosPorFecha maneje el rango
            $start_date = now()->subMonths(1); // Permitir búsquedas hasta 1 mes atrás
        }
        
        // TABLAS PRINCIPALES: SIN FILTRO DE FECHA (mostrar todos los registros)
        // Orden descendente: registros más recientes primero
        $queryProduccion = RegistroPisoProduccion::whereDate('fecha', '>=', $start_date);
        $this->aplicarFiltrosDinamicos($queryProduccion, request(), 'produccion');
        // ⚡ OPTIMIZACIÓN: Cargar con SELECT solo las columnas necesarias para la tabla
        // Esto reduce el tamaño de datos transferidos
        $registros = $queryProduccion->orderBy('id', 'desc')->paginate(50);
        $columns = Schema::getColumnListing('registro_piso_produccion');
        $columns = array_diff($columns, ['id', 'created_at', 'updated_at', 'producida']);

        $queryPolos = RegistroPisoPolo::whereDate('fecha', '>=', $start_date);
        $this->aplicarFiltrosDinamicos($queryPolos, request(), 'polos');
        $registrosPolos = $queryPolos->orderBy('id', 'desc')->paginate(50);
        $columnsPolos = Schema::getColumnListing('registro_piso_polo');
        $columnsPolos = array_diff($columnsPolos, ['id', 'created_at', 'updated_at', 'producida']);

        $queryCorte = RegistroPisoCorte::whereDate('fecha', '>=', $start_date);
        $this->aplicarFiltrosDinamicos($queryCorte, request(), 'corte');
        // ⚡ OPTIMIZACIÓN: Eager load relaciones ANTES de paginar para evitar N+1 queries
        $registrosCorte = $queryCorte->with(['hora', 'operario', 'maquina', 'tela'])->orderBy('id', 'desc')->paginate(50);
        // Ya no necesitamos load() aquí porque eager loading ya cargó las relaciones
        
        // 🔍 DEBUG: Verificar que las relaciones se cargaron
        \Log::info('RegistrosCorte loaded with relations', [
            'count' => count($registrosCorte->items()),
            'first_item_has_hora' => !empty($registrosCorte->items()) ? !!$registrosCorte->items()[0]->hora : null,
            'first_item_hora_value' => !empty($registrosCorte->items()) ? $registrosCorte->items()[0]->hora?->hora : null
        ]);
        $columnsCorte = Schema::getColumnListing('registro_piso_corte');
        $columnsCorte = array_diff($columnsCorte, ['id', 'created_at', 'updated_at', 'producida']);        if (request()->wantsJson()) {
            return response()->json([
                'registros' => $registros->items(),
                'columns' => array_values($columns),
                'registrosPolos' => $registrosPolos->items(),
                'columnsPolos' => array_values($columnsPolos),
                'registrosCorte' => $registrosCorte->map(function($registro) {
                    $registroArray = $registro->toArray();
                    // Agregar displays de relaciones para AJAX
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
        // ⚡ OPTIMIZACIÓN: Detectar si hay filtro de fecha para cargar datos apropiados
        $endDate = now();
        
        // Por defecto, cargar últimos 7 días
        $startDate = now()->subDays(7);
        
        // SI hay filtro de fecha aplicado, calcular el rango necesario
        $filterType = request()->get('filter_type');
        if ($filterType) {
            if ($filterType === 'day') {
                $specificDate = request()->get('specific_date');
                if ($specificDate) {
                    $startDate = \Carbon\Carbon::createFromFormat('Y-m-d', $specificDate)->startOfDay();
                    $endDate = \Carbon\Carbon::createFromFormat('Y-m-d', $specificDate)->endOfDay();
                }
            } elseif ($filterType === 'range') {
                $startDateStr = request()->get('start_date');
                $endDateStr = request()->get('end_date');
                if ($startDateStr && $endDateStr) {
                    $startDate = \Carbon\Carbon::createFromFormat('Y-m-d', $startDateStr)->startOfDay();
                    $endDate = \Carbon\Carbon::createFromFormat('Y-m-d', $endDateStr)->endOfDay();
                }
            } elseif ($filterType === 'month') {
                $month = request()->get('month');
                if ($month) {
                    $startDate = \Carbon\Carbon::createFromFormat('Y-m', $month)->startOfMonth()->startOfDay();
                    $endDate = \Carbon\Carbon::createFromFormat('Y-m', $month)->endOfMonth()->endOfDay();
                }
            } elseif ($filterType === 'specific') {
                $specificDates = request()->get('specific_dates');
                if ($specificDates) {
                    $datesArray = array_map(function($date) {
                        return \Carbon\Carbon::createFromFormat('Y-m-d', trim($date));
                    }, explode(',', $specificDates));
                    $startDate = collect($datesArray)->min();
                    $endDate = collect($datesArray)->max()->endOfDay();
                }
            }
        }
        
        \Log::info('index() - Cargando registros de seguimiento:', [
            'startDate' => $startDate->format('Y-m-d H:i:s'),
            'endDate' => $endDate->format('Y-m-d H:i:s'),
            'filter_type' => $filterType
        ]);
        
        $todosRegistrosProduccion = RegistroPisoProduccion::whereDate('fecha', '>=', $startDate)->whereDate('fecha', '<=', $endDate)->get();
        $todosRegistrosPolos = RegistroPisoPolo::whereDate('fecha', '>=', $startDate)->whereDate('fecha', '<=', $endDate)->get();
        $todosRegistrosCorte = RegistroPisoCorte::whereDate('fecha', '>=', $startDate)->whereDate('fecha', '<=', $endDate)->get(); // Sin cargar relaciones aquí

        // Filtrar registros por fecha SOLO para el tablero activo
        $activeSection = request()->get('active_section', 'produccion');
        
        // Por defecto, usar todos los registros (sin filtro adicional de fecha)
        $registrosProduccionFiltrados = $todosRegistrosProduccion;
        $registrosPolosFiltrados = $todosRegistrosPolos;
        $registrosCorteFiltrados = $todosRegistrosCorte;
        
        // Aplicar filtro solo al tablero activo (si hubiera filtros adicionales)
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

    /**
     * Obtener columnas válidas para cada sección
     */
    private function getValidColumnsForSection($section)
    {
        $validColumns = [
            'produccion' => [
                'fecha', 'modulo', 'orden_produccion', 'hora', 'tiempo_ciclo',
                'porcion_tiempo', 'cantidad', 'paradas_programadas', 'paradas_no_programadas',
                'tiempo_parada_no_programada', 'numero_operarios', 'tiempo_para_programada',
                'meta', 'eficiencia'
            ],
            'polos' => [
                'fecha', 'modulo', 'orden_produccion', 'hora', 'tiempo_ciclo',
                'porcion_tiempo', 'cantidad', 'paradas_programadas', 'paradas_no_programadas',
                'tiempo_parada_no_programada', 'numero_operarios', 'tiempo_para_programada',
                'meta', 'eficiencia'
            ],
            'corte' => [
                'fecha', 'modulo', 'orden_produccion', 'hora_id', 'operario_id', 'actividad',
                'maquina_id', 'tela_id', 'tiempo_ciclo', 'porcion_tiempo', 'cantidad',
                'paradas_programadas', 'paradas_no_programadas', 'tiempo_parada_no_programada',
                'numero_operarios', 'tiempo_para_programada', 'meta', 'eficiencia',
                'tipo_extendido', 'numero_capas', 'tiempo_extendido', 'trazado', 'tiempo_trazado'
            ]
        ];

        return $validColumns[$section] ?? [];
    }

    /**
     * Aplicar filtros dinámicos por columna
     */
    private function aplicarFiltrosDinamicos($query, $request, $section)
    {
        try {
            // Obtener filtros del request (formato JSON)
            $filters = $request->get('filters');
            
            if (!$filters) {
                return;
            }

            // Si es string JSON, decodificar
            if (is_string($filters)) {
                $filters = json_decode($filters, true);
                
                // Si la decodificación falla, retornar sin aplicar filtros
                if (json_last_error() !== JSON_ERROR_NONE) {
                    \Log::warning('Error al decodificar filtros JSON', [
                        'error' => json_last_error_msg(),
                        'filters_raw' => $filters
                    ]);
                    return;
                }
            }

            if (!is_array($filters) || empty($filters)) {
                return;
            }

            // VALIDAR QUE LOS FILTROS CORRESPONDAN A LA SECCIÓN ACTUAL
            // Esto previene que filtros de una sección se apliquen a otra
            $validColumns = $this->getValidColumnsForSection($section);
            $filters = array_intersect_key($filters, array_flip($validColumns));
            
            if (empty($filters)) {
                return;
            }

            // Aplicar cada filtro
            foreach ($filters as $column => $values) {
                // Validar que $values sea un array
                if (!is_array($values)) {
                    // Si es un valor único, convertirlo a array
                    $values = [$values];
                }
                
                if (empty($values)) {
                    continue;
                }

                // Manejar columnas especiales según la sección
                if ($section === 'corte') {
                    // Para corte, manejar relaciones usando los nombres de las columnas con _id
                    if ($column === 'hora_id') {
                        // Buscar IDs de horas por sus valores
                        $horaIds = \App\Models\Hora::whereIn('hora', $values)->pluck('id')->toArray();
                        if (!empty($horaIds)) {
                            $query->whereIn('hora_id', $horaIds);
                        }
                    } elseif ($column === 'operario_id') {
                        // Buscar IDs de operarios por sus nombres
                        $operarioIds = \App\Models\User::whereIn('name', $values)->pluck('id')->toArray();
                        if (!empty($operarioIds)) {
                            $query->whereIn('operario_id', $operarioIds);
                        }
                    } elseif ($column === 'maquina_id') {
                        // Buscar IDs de máquinas por sus nombres
                        $maquinaIds = \App\Models\Maquina::whereIn('nombre_maquina', $values)->pluck('id')->toArray();
                        if (!empty($maquinaIds)) {
                            $query->whereIn('maquina_id', $maquinaIds);
                        }
                    } elseif ($column === 'tela_id') {
                        // Buscar IDs de telas por sus nombres
                        $telaIds = \App\Models\Tela::whereIn('nombre_tela', $values)->pluck('id')->toArray();
                        if (!empty($telaIds)) {
                            $query->whereIn('tela_id', $telaIds);
                        }
                    } elseif ($column === 'fecha') {
                        // Convertir fechas del formato dd-mm-yyyy a yyyy-mm-dd
                        $formattedDates = array_map(function($date) {
                            if (preg_match('/^(\d{2})-(\d{2})-(\d{4})$/', $date, $matches)) {
                                return "{$matches[3]}-{$matches[2]}-{$matches[1]}";
                            }
                            return $date;
                        }, $values);
                        $query->whereIn($column, $formattedDates);
                    } else {
                        // Columnas normales
                        $query->whereIn($column, $values);
                    }
                } else {
                    // Para producción y polos, todas son columnas directas
                    // Manejar fecha con formato especial
                    if ($column === 'fecha') {
                        // Convertir fechas del formato dd-mm-yyyy a yyyy-mm-dd
                        $formattedDates = array_map(function($date) {
                            if (preg_match('/^(\d{2})-(\d{2})-(\d{4})$/', $date, $matches)) {
                                return "{$matches[3]}-{$matches[2]}-{$matches[1]}";
                            }
                            return $date;
                        }, $values);
                        $query->whereIn($column, $formattedDates);
                    } else {
                        $query->whereIn($column, $values);
                    }
                }
            }
        } catch (\Exception $e) {
            \Log::error('Error al aplicar filtros dinámicos', [
                'error' => $e->getMessage(),
                'section' => $section,
                'trace' => $e->getTraceAsString()
            ]);
            // No lanzar excepción, simplemente continuar sin filtros
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
        \Log::info('🟠 store (GENÉRICO) INICIADO', [
            'all_data' => $request->all(),
            'method' => $request->method(),
            'route' => $request->route()->getName(),
            'section' => $request->get('section')
        ]);

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

                // Log para debugging
                \Log::info('Calculando meta y eficiencia', [
                    'porcion_tiempo' => $porcion_tiempo,
                    'numero_operarios' => $numero_operarios,
                    'tiempo_parada_no_programada' => $tiempo_parada_no_programada,
                    'tiempo_ciclo' => $tiempo_ciclo,
                    'cantidad' => $cantidad,
                    'tiempo_para_programada' => $tiempo_para_programada
                ]);

                $tiempo_disponible = (3600 * $porcion_tiempo * $numero_operarios)
                                    - $tiempo_parada_no_programada
                                    - $tiempo_para_programada;
                $tiempo_disponible = max(0, $tiempo_disponible);

                $meta = $tiempo_ciclo > 0 ? ($tiempo_disponible / $tiempo_ciclo) * 0.9 : 0;
                $eficiencia = $meta > 0 ? ($cantidad / $meta) : 0;

                \Log::info('Resultado de cálculos', [
                    'tiempo_disponible' => $tiempo_disponible,
                    'meta' => $meta,
                    'eficiencia' => $eficiencia
                ]);

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
        $startTime = microtime(true);
        
        $validateStart = microtime(true);
        $request->validate([
            'section' => 'required|string|in:produccion,polos,corte',
        ]);

        $model = match($request->section) {
            'produccion' => RegistroPisoProduccion::class,
            'polos' => RegistroPisoPolo::class,
            'corte' => RegistroPisoCorte::class,
        };

        $findStart = microtime(true);
        $registro = $model::findOrFail($id);
        
        $validateStart2 = microtime(true);
        $validated = $request->validate([
            'fecha' => 'sometimes|date',
            'modulo' => 'sometimes|string',
            'orden_produccion' => 'sometimes|string',
            'hora' => 'sometimes|string',
            'hora_id' => 'sometimes|integer|exists:horas,id',
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
            // ⚡ OPTIMIZACIÓN: Si solo se actualizan campos de relaciones (hora, operario, máquina, tela)
            // NO recalcular nada, solo guardar y devolver éxito inmediatamente
            $fieldsRelacionesExternas = ['hora_id', 'operario_id', 'maquina_id', 'tela_id'];
            $soloRelacionesExternas = true;
            
            foreach ($validated as $field => $value) {
                if (!in_array($field, $fieldsRelacionesExternas)) {
                    $soloRelacionesExternas = false;
                    break;
                }
            }

            // ⚡ RÁPIDO: Si solo son campos de relaciones, guardar y retornar sin cálculos
            if ($soloRelacionesExternas) {
                $registro->update($validated);
                
                // ⚡ BROADCAST: Cargar relaciones y emitir evento (ASINCRÓNICO gracias a ShouldBroadcast)
                if ($request->section === 'corte') {
                    $registro->load(['hora', 'operario', 'maquina', 'tela']);
                    try {
                        broadcast(new CorteRecordCreated($registro));
                    } catch (\Exception $e) {
                        \Log::warning('Broadcast error: ' . $e->getMessage());
                    }
                }
                
                // Retornar inmediatamente
                $endTime = microtime(true);
                $duration = ($endTime - $startTime) * 1000;
                $findDuration = ($findStart - $validateStart) * 1000;
                $validate2Duration = ($validateStart2 - $findStart) * 1000;
                
                \Log::info('TablerosController::update TIMING', [
                    'total_ms' => round($duration, 2),
                    'findOrFail_ms' => round($findDuration, 2),
                    'validate_ms' => round($validate2Duration, 2),
                    'registro_id' => $id,
                    'section' => $request->section
                ]);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Registro actualizado correctamente.',
                    'data' => $registro->toArray(), // ⚡ Convertir a array para asegurar relaciones se serializan
                    'debug' => [
                        'total_ms' => round($duration, 2),
                        'findOrFail_ms' => round($findDuration, 2),
                        'validate_ms' => round($validate2Duration, 2)
                    ]
                ]);
            }

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
                    'data' => $request->section === 'corte' ? $registro->toArray() : [ // ⚡ Convertir a array
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
                    'data' => $request->section === 'corte' ? $registro->toArray() : null // ⚡ Convertir a array
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

    public function duplicate($id)
    {
        $request = request();
        $section = $request->query('section');

        $model = match($section) {
            'produccion' => RegistroPisoProduccion::class,
            'polos' => RegistroPisoPolo::class,
            'corte' => RegistroPisoCorte::class,
        };

        try {
            // ⚡ OPTIMIZACIÓN: Cargar con relaciones solo si es corte
            $relaciones = $section === 'corte' ? ['hora', 'operario', 'maquina', 'tela'] : [];
            $registroOriginal = $relaciones 
                ? $model::with($relaciones)->findOrFail($id)
                : $model::findOrFail($id);
            
            // Crear un array con los datos del registro original
            $datosNuevos = $registroOriginal->toArray();
            
            // Remover campos que no deben duplicarse
            unset($datosNuevos['id']);
            unset($datosNuevos['created_at']);
            unset($datosNuevos['updated_at']);
            
            // ⚡ OPTIMIZACIÓN: Remover relaciones del array antes de crear
            // Las relaciones ya están guardadas en las foreign keys
            foreach ($relaciones as $rel) {
                unset($datosNuevos[$rel]);
            }
            
            // Crear el nuevo registro duplicado (sin load adicional después)
            $registroDuplicado = $model::create($datosNuevos);
            
            // ⚡ OPTIMIZACIÓN: Cargar relaciones solo una vez, DESPUÉS de crear
            if ($relaciones) {
                $registroDuplicado->load($relaciones);
            }
            
            // Emitir evento de creación via WebSocket para actualización en tiempo real
            try {
                if ($section === 'produccion') {
                    broadcast(new ProduccionRecordCreated($registroDuplicado));
                } elseif ($section === 'polos') {
                    broadcast(new PoloRecordCreated($registroDuplicado));
                } elseif ($section === 'corte') {
                    broadcast(new CorteRecordCreated($registroDuplicado));
                }
            } catch (\Exception $broadcastError) {
                \Log::warning('Error al emitir evento de duplicación', [
                    'error' => $broadcastError->getMessage(),
                    'section' => $section
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Registro duplicado correctamente.',
                'registro' => $registroDuplicado,
                'section' => $section
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al duplicar registro', [
                'error' => $e->getMessage(),
                'id' => $id,
                'section' => $section
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al duplicar el registro: ' . $e->getMessage()
            ], 500);
        }
    }

    public function storeCorte(Request $request)
    {
        \Log::info('🔴 storeCorte INICIADO', [
            'all_data' => $request->all(),
            'method' => $request->method()
        ]);

        // Validación con mensajes personalizados descriptivos
        try {
            $request->validate([
                'fecha' => 'required|date',
                'orden_produccion' => 'required|string',
                'tela_id' => 'required|exists:telas,id',
                'hora_id' => 'required|exists:horas,id',
                'operario_id' => 'required|exists:users,id',
                'actividad' => 'required|string',
                'maquina_id' => 'required|exists:maquinas,id',
                'tiempo_ciclo' => 'required|numeric|min:0.01',
                'porcion_tiempo' => 'required|numeric|min:0|max:1',
                'cantidad_producida' => 'required|integer|min:0',
                'paradas_programadas' => 'required|string',
                'paradas_no_programadas' => 'nullable|string',
                'tiempo_parada_no_programada' => 'nullable|numeric|min:0',
                'tipo_extendido' => 'required|string',
                'numero_capas' => 'required|integer|min:0',
                'trazado' => 'required|string',
                'tiempo_trazado' => 'nullable|numeric|min:0',
            ], [
                'fecha.required' => 'La fecha es obligatoria.',
                'fecha.date' => 'La fecha debe ser una fecha válida (formato: YYYY-MM-DD).',
                'orden_produccion.required' => 'La orden de producción es obligatoria.',
                'tela_id.required' => 'Debe seleccionar una tela válida.',
                'tela_id.exists' => 'La tela seleccionada no existe en el sistema. Intenta crear una nueva.',
                'hora_id.required' => 'Debe seleccionar una hora válida.',
                'hora_id.exists' => 'La hora seleccionada no existe en el sistema.',
                'operario_id.required' => 'Debe seleccionar un operario válido.',
                'operario_id.exists' => 'El operario seleccionado no existe en el sistema.',
                'actividad.required' => 'La actividad es obligatoria.',
                'maquina_id.required' => 'Debe seleccionar una máquina válida.',
                'maquina_id.exists' => 'La máquina seleccionada no existe en el sistema.',
                'tiempo_ciclo.required' => 'El tiempo de ciclo es obligatorio.',
                'tiempo_ciclo.numeric' => 'El tiempo de ciclo debe ser un número válido.',
                'tiempo_ciclo.min' => 'El tiempo de ciclo debe ser mayor a 0.',
                'porcion_tiempo.required' => 'La porción de tiempo es obligatoria.',
                'porcion_tiempo.numeric' => 'La porción de tiempo debe ser un número válido.',
                'porcion_tiempo.min' => 'La porción de tiempo no puede ser negativa.',
                'porcion_tiempo.max' => 'La porción de tiempo no puede ser mayor a 1 (100%).',
                'cantidad_producida.required' => 'La cantidad producida es obligatoria.',
                'cantidad_producida.integer' => 'La cantidad producida debe ser un número entero.',
                'cantidad_producida.min' => 'La cantidad producida no puede ser negativa.',
                'paradas_programadas.required' => 'Debe seleccionar un tipo de parada programada.',
                'tiempo_parada_no_programada.numeric' => 'El tiempo de parada no programada debe ser un número válido.',
                'tiempo_parada_no_programada.min' => 'El tiempo de parada no programada no puede ser negativo.',
                'tipo_extendido.required' => 'Debe seleccionar un tipo de extendido.',
                'numero_capas.required' => 'El número de capas es obligatorio.',
                'numero_capas.integer' => 'El número de capas debe ser un número entero.',
                'numero_capas.min' => 'El número de capas no puede ser negativo.',
                'trazado.required' => 'Debe seleccionar un tipo de trazado.',
                'tiempo_trazado.numeric' => 'El tiempo de trazado debe ser un número válido.',
                'tiempo_trazado.min' => 'El tiempo de trazado no puede ser negativo.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $errors = $e->errors();
            $firstError = reset($errors)[0] ?? 'Error de validación';
            
            return response()->json([
                'success' => false,
                'message' => $firstError,
                'errors' => $errors,
                'error_type' => 'validation'
            ], 422);
        }

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
            // NOTA: Para CORTE no se usa numero_operarios
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

            \Log::info('Corte - Calculando valores', [
                'tiempo_disponible' => $tiempo_disponible,
                'meta' => $meta,
                'eficiencia' => $eficiencia,
                'cantidad_producida' => $request->cantidad_producida,
                'tiempo_ciclo' => $request->tiempo_ciclo,
                'actividad' => $request->actividad
            ]);

            $registro = RegistroPisoCorte::create([
                'fecha' => $request->fecha,
                // 'modulo' NO existe en registro_piso_corte
                'orden_produccion' => $request->orden_produccion,
                'hora_id' => $request->hora_id,
                'operario_id' => $request->operario_id,
                'maquina_id' => $request->maquina_id,
                'porcion_tiempo' => $request->porcion_tiempo,
                // 'numero_operarios' NO existe en registro_piso_corte
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

            \Log::info('Corte - Registro guardado', [
                'registro_id' => $registro->id,
                'tiempo_disponible_guardado' => $registro->tiempo_disponible,
                'meta_guardada' => $registro->meta,
                'eficiencia_guardada' => $registro->eficiencia,
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
        } catch (\Illuminate\Database\QueryException $e) {
            \Log::error('Error de base de datos en storeCorte', [
                'error' => $e->getMessage(),
                'sql' => $e->getSql() ?? 'N/A'
            ]);
            
            $errorMessage = 'Error al guardar en la base de datos. ';
            if (str_contains($e->getMessage(), 'UNIQUE constraint failed')) {
                $errorMessage .= 'Este registro ya existe en el sistema.';
            } elseif (str_contains($e->getMessage(), 'FOREIGN KEY constraint failed')) {
                $errorMessage .= 'Uno de los datos referenciados no existe (tela, máquina, operario u hora).';
            } elseif (str_contains($e->getMessage(), 'Column not found')) {
                $errorMessage .= 'Hay un problema con la estructura de la base de datos.';
            } else {
                $errorMessage .= 'Por favor, intenta nuevamente.';
            }
            
            return response()->json([
                'success' => false,
                'message' => $errorMessage,
                'error_type' => 'database',
                'details' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        } catch (\Exception $e) {
            \Log::error('Error general en storeCorte', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            $errorMessage = 'Error al procesar el registro. ';
            
            if (str_contains($e->getMessage(), 'Call to undefined function')) {
                $errorMessage .= 'Hay un problema con una función del sistema.';
            } elseif (str_contains($e->getMessage(), 'Undefined property')) {
                $errorMessage .= 'Hay un problema con los datos enviados.';
            } elseif (str_contains($e->getMessage(), 'division by zero')) {
                $errorMessage .= 'Error en el cálculo: división por cero. Verifica el tiempo de ciclo.';
            } else {
                $errorMessage .= 'Por favor, contacta al administrador.';
            }
            
            return response()->json([
                'success' => false,
                'message' => $errorMessage,
                'error_type' => 'system',
                'details' => config('app.debug') ? $e->getMessage() : null
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
        try {
            // Verificar si ya existe la tela
            $telaExistente = Tela::where('nombre_tela', $request->nombre_tela)->first();
            
            if ($telaExistente) {
                return response()->json([
                    'success' => false,
                    'message' => 'La tela "' . $request->nombre_tela . '" ya existe en el sistema.',
                    'error_type' => 'duplicate',
                    'existing_item' => $telaExistente
                ], 422);
            }

            $request->validate([
                'nombre_tela' => 'required|string',
            ]);

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
        $query = $request->get('q', '');
        
        // ⚡ OPTIMIZACIÓN: Buscar con índice sin transformar
        // MySQL usa índice cuando buscamos desde el inicio
        $telas = Tela::where('nombre_tela', 'like', $query . '%')
            ->select('id', 'nombre_tela')
            ->limit(10)
            ->get();

        return response()->json(['telas' => $telas]);
    }

    public function storeMaquina(Request $request)
    {
        try {
            // Verificar si ya existe la máquina
            $maquinaExistente = Maquina::where('nombre_maquina', $request->nombre_maquina)->first();
            
            if ($maquinaExistente) {
                return response()->json([
                    'success' => false,
                    'message' => 'La máquina "' . $request->nombre_maquina . '" ya existe en el sistema.',
                    'error_type' => 'duplicate',
                    'existing_item' => $maquinaExistente
                ], 422);
            }

            $request->validate([
                'nombre_maquina' => 'required|string',
            ]);

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
        $query = $request->get('q', '');
        
        // ⚡ OPTIMIZACIÓN: Buscar con índice sin transformar
        // MySQL usa índice cuando buscamos desde el inicio
        $maquinas = Maquina::where('nombre_maquina', 'like', $query . '%')
            ->select('id', 'nombre_maquina')
            ->limit(10)
            ->get();

        return response()->json(['maquinas' => $maquinas]);
    }

    public function searchOperarios(Request $request)
    {
        $query = $request->get('q', '');
        
        // ⚡ OPTIMIZACIÓN: Buscar con índice sin transformar
        // MySQL usa índice cuando buscamos desde el inicio
        $operarios = User::where('name', 'like', $query . '%')
            ->select('id', 'name')
            ->limit(10)
            ->get();

        return response()->json(['operarios' => $operarios]);
    }

    public function storeOperario(Request $request)
    {
        try {
            // Verificar si ya existe el operario
            $operarioExistente = User::where('name', strtoupper($request->name))->first();
            
            if ($operarioExistente) {
                return response()->json([
                    'success' => false,
                    'message' => 'El operario "' . strtoupper($request->name) . '" ya existe en el sistema.',
                    'error_type' => 'duplicate',
                    'existing_item' => $operarioExistente
                ], 422);
            }

            $request->validate([
                'name' => 'required|string',
            ]);

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
        // Log de todos los parámetros recibidos (solo los que tienen valor)
        $paramsRecibidos = array_filter($request->all(), function($value) {
            return $value !== null && $value !== '';
        });
        
        $hayFiltro = !empty($paramsRecibidos) && isset($paramsRecibidos['filter_type']);
        \Log::info('Dashboard Corte API: Parámetros recibidos', [
            'hay_filtro' => $hayFiltro,
            'parametros' => $paramsRecibidos
        ]);
        
        // Obtener todos los registros de corte con relaciones
        $query = RegistroPisoCorte::with(['hora', 'operario', 'maquina', 'tela']);
        $registrosCorte = $query->get();
        
        \Log::info('Dashboard Corte API: Total registros antes de filtrar', [
            'total' => $registrosCorte->count()
        ]);
        
        // Aplicar filtros solo si hay filtro_type
        if ($hayFiltro) {
            $registrosCorteFiltrados = $this->filtrarRegistrosPorFecha($registrosCorte, $request);
            \Log::info('Dashboard Corte API: Registros FILTRADOS', [
                'total' => $registrosCorteFiltrados->count(),
                'filtro_type' => $request->get('filter_type'),
                'specific_date' => $request->get('specific_date'),
                'start_date' => $request->get('start_date'),
                'end_date' => $request->get('end_date'),
                'month' => $request->get('month'),
            ]);
        } else {
            $registrosCorteFiltrados = $registrosCorte;
            \Log::info('Dashboard Corte API: SIN FILTRO - Mostrando TODOS los registros', [
                'total' => $registrosCorteFiltrados->count()
            ]);
        }
        
        // Calcular datos dinámicos para las tablas
        $horasData = $this->calcularProduccionPorHoras($registrosCorteFiltrados);
        $operariosData = $this->calcularProduccionPorOperarios($registrosCorteFiltrados);
        
        \Log::info('Dashboard Corte API: Datos calculados', [
            'horas_count' => count($horasData),
            'operarios_count' => count($operariosData),
        ]);
        
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
                    'eficiencia' => 0,
                    'tiempo_disponible' => 0
                ];
            }
            $horasData[$hora]['cantidad'] += $registro->cantidad ?? 0;
            $horasData[$hora]['meta'] += $registro->meta ?? 0;
            $horasData[$hora]['tiempo_disponible'] += $registro->tiempo_disponible ?? 0;
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
        
        // 🔍 DEBUG: Loguear parámetros recibidos
        $filterType = $request->get('filter_type');
        $specificDate = $request->get('specific_date');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $month = $request->get('month');
        
        \Log::info('getSeguimientoData - Parámetros recibidos:', [
            'section' => $section,
            'filter_type' => $filterType,
            'specific_date' => $specificDate,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'month' => $month,
            'all_params' => $request->all()
        ]);

        $model = match($section) {
            'produccion' => RegistroPisoProduccion::class,
            'polos' => RegistroPisoPolo::class,
            'corte' => RegistroPisoCorte::class,
        };

        $query = $model::query();
        $this->aplicarFiltroFecha($query, $request);
        
        // ⚡ OPTIMIZACIÓN: Si no hay filtro específico, limitar a último día o últimos 500 registros
        // para evitar procesar 7000+ registros que bloquean el servidor
        if (!$filterType) {
            $query = $query->latest()->limit(500);
            \Log::info('getSeguimientoData - Aplicando LIMIT 500 porque no hay filtro');
        }
        
        $registrosFiltrados = $query->get();
        
        // 🔍 DEBUG: Loguear cantidad de registros filtrados
        \Log::info('getSeguimientoData - Registros filtrados:', [
            'section' => $section,
            'cantidad' => count($registrosFiltrados),
            'filter_type' => $filterType,
            'specific_date' => $specificDate,
            'limited' => !$filterType ? true : false
        ]);

        $seguimiento = $this->calcularSeguimientoModulos($registrosFiltrados);

        return response()->json($seguimiento);
    }

    /**
     * Crear o buscar operario por nombre
     */
    public function findOrCreateOperario(Request $request)
    {
        $startTime = microtime(true);
        $name = strtoupper($request->input('name'));
        
        // ⚡ OPTIMIZACIÓN: Primero buscar sin crear para evitar bcrypt en la mayoría de casos
        $searchStart = microtime(true);
        $operario = User::where('name', $name)->first();
        $searchTime = (microtime(true) - $searchStart) * 1000;
        
        if (!$operario) {
            // Solo crear si no existe
            $createStart = microtime(true);
            $operario = User::create([
                'name' => $name,
                'email' => strtolower(str_replace(' ', '', $name)) . '@mundoindustrial.com',
                'password' => bcrypt('password123')
            ]);
            $createTime = (microtime(true) - $createStart) * 1000;
            
            \Log::info('findOrCreateOperario - creado:', [
                'name' => $name,
                'search_time_ms' => round($searchTime, 2),
                'create_time_ms' => round($createTime, 2),
                'total_time_ms' => round($searchTime + $createTime, 2)
            ]);
        } else {
            $totalTime = (microtime(true) - $startTime) * 1000;
            \Log::info('findOrCreateOperario - encontrado:', [
                'name' => $name,
                'total_time_ms' => round($totalTime, 2)
            ]);
        }

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
        $startTime = microtime(true);
        $nombre = strtoupper($request->input('nombre'));
        
        $createStart = microtime(true);
        
        // ⚡ OPTIMIZACIÓN: Primero intentar buscar sin lock
        $maquina = Maquina::where('nombre_maquina', $nombre)->first();
        
        if (!$maquina) {
            // Solo crear si no existe - usar try/catch por si hay race condition
            try {
                $maquina = Maquina::create(['nombre_maquina' => $nombre]);
            } catch (\Exception $e) {
                // Si falla por duplicate, buscar nuevamente
                $maquina = Maquina::where('nombre_maquina', $nombre)->first();
                if (!$maquina) {
                    // Si aún no existe, re-lanzar el error
                    throw $e;
                }
            }
        }
        
        $duration = (microtime(true) - $createStart) * 1000;
        
        \Log::info('findOrCreateMaquina:', [
            'nombre' => $nombre,
            'maquina_id' => $maquina->id,
            'operation_time_ms' => round($duration, 2)
        ]);

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
            $query = RegistroPisoProduccion::query();
            $this->aplicarFiltrosDinamicos($query, request(), 'produccion');
            $registros = $query->orderBy('id', 'desc')->paginate(50);
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
            $query = RegistroPisoPolo::query();
            $this->aplicarFiltrosDinamicos($query, request(), 'polos');
            $registros = $query->orderBy('id', 'desc')->paginate(50);
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
            $query = RegistroPisoCorte::with(['hora', 'operario', 'maquina', 'tela']);
            $this->aplicarFiltrosDinamicos($query, request(), 'corte');
            $registros = $query->orderBy('id', 'desc')->paginate(50);
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
        $startTime = microtime(true);
        $nombre = strtoupper($request->input('nombre'));
        
        $createStart = microtime(true);
        
        // ⚡ OPTIMIZACIÓN: Primero intentar buscar sin lock
        $tela = Tela::where('nombre_tela', $nombre)->first();
        
        if (!$tela) {
            // Solo crear si no existe - usar try/catch por si hay race condition
            try {
                $tela = Tela::create(['nombre_tela' => $nombre]);
            } catch (\Exception $e) {
                // Si falla por duplicate, buscar nuevamente
                $tela = Tela::where('nombre_tela', $nombre)->first();
                if (!$tela) {
                    // Si aún no existe, re-lanzar el error
                    throw $e;
                }
            }
        }
        
        $duration = (microtime(true) - $createStart) * 1000;
        
        \Log::info('findOrCreateTela:', [
            'nombre' => $nombre,
            'tela_id' => $tela->id,
            'operation_time_ms' => round($duration, 2)
        ]);

        return response()->json([
            'id' => $tela->id,
            'nombre_tela' => $tela->nombre_tela
        ]);
    }

    /**
     * Obtener valores únicos de una columna para los filtros
     */
    public function getUniqueValues(Request $request)
    {
        $section = $request->get('section');
        $column = $request->get('column');

        $model = match($section) {
            'produccion' => RegistroPisoProduccion::class,
            'polos' => RegistroPisoPolo::class,
            'corte' => RegistroPisoCorte::class,
            default => null
        };

        if (!$model) {
            return response()->json(['error' => 'Invalid section'], 400);
        }

        $values = [];

        // Manejar columnas especiales para corte (relaciones)
        if ($section === 'corte') {
            if ($column === 'hora_id') {
                // Para hora_id, obtener los valores de la tabla horas
                $values = Hora::distinct()->pluck('hora')->sort()->values()->toArray();
            } elseif ($column === 'operario_id') {
                // Para operario_id, obtener los nombres de los operarios
                $values = User::whereHas('registrosPisoCorte')
                    ->distinct()
                    ->pluck('name')
                    ->sort()
                    ->values()
                    ->toArray();
            } elseif ($column === 'maquina_id') {
                // Para maquina_id, obtener los nombres de las máquinas
                $values = Maquina::whereHas('registrosPisoCorte')
                    ->distinct()
                    ->pluck('nombre_maquina')
                    ->sort()
                    ->values()
                    ->toArray();
            } elseif ($column === 'tela_id') {
                // Para tela_id, obtener los nombres de las telas
                $values = Tela::whereHas('registrosPisoCorte')
                    ->distinct()
                    ->pluck('nombre_tela')
                    ->sort()
                    ->values()
                    ->toArray();
            } elseif ($column === 'fecha') {
                // Para fechas, obtener y formatear
                $values = $model::distinct()
                    ->pluck($column)
                    ->filter()
                    ->map(function($date) {
                        return \Carbon\Carbon::parse($date)->format('d-m-Y');
                    })
                    ->sort()
                    ->values()
                    ->toArray();
            } else {
                // Columnas normales
                $values = $model::distinct()
                    ->pluck($column)
                    ->filter()
                    ->sort()
                    ->values()
                    ->toArray();
            }
        } else {
            // Para producción y polos
            if ($column === 'fecha') {
                // Para fechas, obtener y formatear
                $values = $model::distinct()
                    ->pluck($column)
                    ->filter()
                    ->map(function($date) {
                        return \Carbon\Carbon::parse($date)->format('d-m-Y');
                    })
                    ->sort()
                    ->values()
                    ->toArray();
            } else {
                $values = $model::distinct()
                    ->pluck($column)
                    ->filter()
                    ->sort()
                    ->values()
                    ->toArray();
            }
        }

        return response()->json(['values' => $values]);
    }

    public function findHoraId(Request $request)
    {
        $startTime = microtime(true);
        $request->validate([
            'hora' => 'required|string',
        ]);

        $horaValue = $request->hora;
        
        $searchStart = microtime(true);
        
        // ⚡ OPTIMIZACIÓN: Primero intentar buscar sin lock
        $hora = Hora::where('hora', $horaValue)->first();
        
        if (!$hora) {
            // Solo crear si no existe - usar try/catch por si hay race condition
            try {
                $hora = Hora::create(['hora' => $horaValue]);
            } catch (\Exception $e) {
                // Si falla por duplicate, buscar nuevamente
                $hora = Hora::where('hora', $horaValue)->first();
                if (!$hora) {
                    // Si aún no existe, re-lanzar el error
                    throw $e;
                }
            }
        }
        
        $duration = (microtime(true) - $searchStart) * 1000;
        
        \Log::info('findHoraId performance:', [
            'horaValue' => $horaValue,
            'hora_id' => $hora->id,
            'operation_time_ms' => round($duration, 2)
        ]);

        return response()->json([
            'success' => true,
            'id' => $hora->id,
            'hora' => $hora->hora
        ]);
    }
}
