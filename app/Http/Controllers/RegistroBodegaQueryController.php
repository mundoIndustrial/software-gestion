<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TablaOriginalBodega;
use App\Models\Festivo;
use Illuminate\Support\Facades\DB;
use App\Services\RegistroBodegaQueryService;
use App\Services\RegistroBodegaSearchService;
use App\Services\RegistroBodegaFilterService;
use Carbon\Carbon;

/**
 * RegistroBodegaQueryController - Query/Search/Filter Operations
 * 
 * Responsabilidad: Operaciones de lectura, búsqueda, filtrado y análisis
 * - Listar con paginación y filtros
 * - Búsqueda by nombre/cliente
 * - Mostrar detalles específicos
 * - Cálculos de días/procesos
 * 
 * Dependencias: Query Services (búsqueda, filtrado, transformación)
 * Líneas: ~280
 */
class RegistroBodegaQueryController extends Controller
{
    protected $queryService;
    protected $searchService;
    protected $filterService;

    public function __construct(
        RegistroBodegaQueryService $queryService,
        RegistroBodegaSearchService $searchService,
        RegistroBodegaFilterService $filterService
    )
    {
        $this->queryService = $queryService;
        $this->searchService = $searchService;
        $this->filterService = $filterService;
    }

    private function getEnumOptions($table, $column)
    {
        $columnInfo = DB::select("SHOW COLUMNS FROM {$table} WHERE Field = ?", [$column]);
        if (empty($columnInfo)) return [];

        $type = $columnInfo[0]->Type;
        preg_match_all("/'([^']+)'/", $type, $matches);
        return $matches[1] ?? [];
    }

    public function index(Request $request)
    {
        // Handle request for unique values for filters
        if ($request->has('get_unique_values') && $request->column) {
            try {
                $values = $this->queryService->getUniqueValues($request->column);
                
                if ($request->column === 'descripcion') {
                    $result = [];
                    foreach ($values as $desc) {
                        $ids = TablaOriginalBodega::where('descripcion', $desc)->pluck('pedido')->toArray();
                        $result[] = ['value' => $desc, 'ids' => $ids];
                    }
                    return response()->json(['unique_values' => $values, 'value_ids' => $result]);
                }
                
                return response()->json(['unique_values' => $values]);
            } catch (\InvalidArgumentException $e) {
                return response()->json(['error' => 'Invalid column'], 400);
            } catch (\Exception $e) {
                return response()->json(['error' => 'Error fetching values: ' . $e->getMessage()], 500);
            }
        }

        $query = $this->queryService->buildBaseQuery();
        $query = $this->searchService->applySearchFilter($query, $request->input('search'));

        // Extraer y aplicar filtros dinámicos
        $filterData = $this->filterService->extractFiltersFromRequest($request);
        $query = $this->filterService->applyFiltersToQuery($query, $filterData['filters']);
        $query = $this->filterService->applyPedidoIdFilter($query, $filterData['pedidoIds']);
        $filterTotalDias = $filterData['totalDiasFilter'];

        $festivos = Festivo::pluck('fecha')->toArray();
        
        if ($filterTotalDias !== null) {
            $todasOrdenes = $query->get();
            $ordenesArray = $todasOrdenes->map(function($orden) {
                return (object) $orden->getAttributes();
            })->toArray();
            
            $totalDiasCalculados = $this->calcularTotalDiasBatch($ordenesArray, $festivos);
            
            $ordenesFiltradas = $todasOrdenes->filter(function($orden) use ($totalDiasCalculados, $filterTotalDias) {
                $totalDias = $totalDiasCalculados[$orden->pedido] ?? 0;
                return in_array((int)$totalDias, $filterTotalDias, true);
            });
            
            $currentPage = request()->get('page', 1);
            $perPage = 50;
            $ordenes = new \Illuminate\Pagination\LengthAwarePaginator(
                $ordenesFiltradas->forPage($currentPage, $perPage)->values(),
                $ordenesFiltradas->count(),
                $perPage,
                $currentPage,
                ['path' => request()->url(), 'query' => request()->query()]
            );
            
            $totalDiasCalculados = $this->calcularTotalDiasBatch($ordenes->items(), $festivos);
        } else {
            $ordenes = $query->paginate(50);
            $totalDiasCalculados = $this->calcularTotalDiasBatch($ordenes->items(), $festivos);
        }

        $areaOptions = $this->getEnumOptions('tabla_original_bodega', 'area');

        if ($request->wantsJson()) {
            return response()->json([
                'orders' => $ordenes->items(),
                'totalDiasCalculados' => $totalDiasCalculados,
                'areaOptions' => $areaOptions,
                'pagination' => [
                    'current_page' => $ordenes->currentPage(),
                    'last_page' => $ordenes->lastPage(),
                    'per_page' => $ordenes->perPage(),
                    'total' => $ordenes->total(),
                    'from' => $ordenes->firstItem(),
                    'to' => $ordenes->lastItem(),
                ],
                'pagination_html' => $ordenes->appends(request()->query())->links()->toHtml()
            ]);
        }

        $context = 'bodega';
        $title = 'Registro Órdenes Bodega';
        $icon = 'fa-warehouse';
        $fetchUrl = '/bodega';
        $updateUrl = '/bodega';
        $modalContext = 'bodega';
        return view('bodega.index', compact('ordenes', 'totalDiasCalculados', 'areaOptions', 'context', 'title', 'icon', 'fetchUrl', 'updateUrl', 'modalContext'));
    }

    public function show($pedido)
    {
        $order = TablaOriginalBodega::where('pedido', $pedido)->firstOrFail();

        $totalCantidad = DB::table('registros_por_orden_bodega')
            ->where('pedido', $pedido)
            ->sum('cantidad');

        $totalEntregado = DB::table('registros_por_orden_bodega')
            ->where('pedido', $pedido)
            ->sum('total_producido_por_talla');

        $order->total_cantidad = $totalCantidad;
        $order->total_entregado = $totalEntregado;

        return response()->json($order->toArray());
    }

    public function getPrendas($pedido)
    {
        try {
            $prendas = DB::table('registros_por_orden_bodega')
                ->where('pedido', $pedido)
                ->get(['prenda', 'descripcion', 'talla', 'cantidad']);

            return response()->json($prendas);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al cargar prendas'], 500);
        }
    }

    public function getRegistrosPorOrden($pedido)
    {
        try {
            $registros = DB::table('registros_por_orden_bodega')
                ->where('pedido', $pedido)
                ->select('prenda', 'descripcion', 'talla', 'cantidad')
                ->get();

            return response()->json($registros);
        } catch (\Exception $e) {
            \Log::error('Error al obtener registros por orden bodega', [
                'pedido' => $pedido,
                'error' => $e->getMessage()
            ]);
            
            return response()->json(['success' => false, 'message' => 'Error al obtener los registros'], 500);
        }
    }

    public function getEntregas($pedido)
    {
        $registros = DB::table('registros_por_orden_bodega')
            ->where('pedido', $pedido)
            ->select('prenda', 'talla', 'cantidad', 'total_producido_por_talla')
            ->get()
            ->map(function ($reg) {
                $reg->total_pendiente_por_talla = $reg->cantidad - ($reg->total_producido_por_talla ?? 0);
                return $reg;
            });

        return response()->json($registros);
    }

    public function updateDescripcionPrendas(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'pedido' => 'required|integer',
                'descripcion' => 'required|string'
            ]);

            $pedido = $validatedData['pedido'];
            $nuevaDescripcion = $validatedData['descripcion'];

            DB::beginTransaction();

            $orden = TablaOriginalBodega::where('pedido', $pedido)->firstOrFail();
            $orden->update(['descripcion' => $nuevaDescripcion]);

            $prendas = $this->parseDescripcionToPrendas($nuevaDescripcion);
            $mensaje = '';
            $procesarRegistros = false;

            if (!empty($prendas)) {
                $totalTallasEncontradas = 0;
                foreach ($prendas as $prenda) {
                    $totalTallasEncontradas += count($prenda['tallas']);
                }

                if ($totalTallasEncontradas > 0) {
                    $procesarRegistros = true;
                    
                    DB::table('registros_por_orden_bodega')->where('pedido', $pedido)->delete();

                    foreach ($prendas as $prenda) {
                        foreach ($prenda['tallas'] as $talla) {
                            DB::table('registros_por_orden_bodega')->insert([
                                'pedido' => $pedido,
                                'cliente' => $orden->cliente,
                                'prenda' => $prenda['nombre'],
                                'descripcion' => $prenda['descripcion'] ?? '',
                                'talla' => $talla['talla'],
                                'cantidad' => $talla['cantidad'],
                                'total_pendiente_por_talla' => $talla['cantidad'],
                            ]);
                        }
                    }

                    $totalCantidad = 0;
                    foreach ($prendas as $prenda) {
                        foreach ($prenda['tallas'] as $talla) {
                            $totalCantidad += $talla['cantidad'];
                        }
                    }
                    $orden->update(['cantidad' => $totalCantidad]);
                    
                    $mensaje = "✅ Descripción actualizada y registros regenerados. " . count($prendas) . " prenda(s) con " . $totalTallasEncontradas . " talla(s).";
                } else {
                    $mensaje = "⚠️ Descripción actualizada, pero no se encontraron tallas válidas.";
                }
            } else {
                $mensaje = "📝 Descripción actualizada como texto libre.";
            }

            DB::commit();

            if (class_exists('\App\Events\OrdenBodegaUpdated')) {
                broadcast(new \App\Events\OrdenBodegaUpdated($orden, 'updated'));
            }

            return response()->json([
                'success' => true,
                'message' => $mensaje,
                'prendas_procesadas' => count($prendas),
                'registros_regenerados' => $procesarRegistros
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    private function parseDescripcionToPrendas($descripcion)
    {
        $prendas = [];
        $lineas = explode("\n", $descripcion);
        $prendaActual = null;

        foreach ($lineas as $linea) {
            $linea = trim($linea);
            if (empty($linea)) continue;

            if (preg_match('/^Prenda\s+\d+:\s*(.+)$/i', $linea, $matches)) {
                if ($prendaActual !== null) {
                    $prendas[] = $prendaActual;
                }
                
                $prendaActual = [
                    'nombre' => trim($matches[1]),
                    'descripcion' => '',
                    'tallas' => []
                ];
            }
            elseif (preg_match('/^Descripción:\s*(.+)$/i', $linea, $matches)) {
                if ($prendaActual !== null) {
                    $prendaActual['descripcion'] = trim($matches[1]);
                }
            }
            elseif (preg_match('/^Tallas:\s*(.+)$/i', $linea, $matches)) {
                if ($prendaActual !== null) {
                    $tallasStr = trim($matches[1]);
                    $tallasPares = explode(',', $tallasStr);
                    
                    foreach ($tallasPares as $par) {
                        $par = trim($par);
                        if (preg_match('/^([^:]+):(\d+)$/', $par, $tallaMatches)) {
                            $prendaActual['tallas'][] = [
                                'talla' => trim($tallaMatches[1]),
                                'cantidad' => intval($tallaMatches[2])
                            ];
                        }
                    }
                }
            }
        }

        if ($prendaActual !== null) {
            $prendas[] = $prendaActual;
        }

        return $prendas;
    }

    public function getProcesosTablaOriginal($numeroPedido)
    {
        try {
            $orden = TablaOriginalBodega::where('pedido', $numeroPedido)->firstOrFail();
            $festivos = \App\Models\Festivo::pluck('fecha')->toArray();

            $procesosMap = [
                'Orden' => 'fecha_de_creacion_de_orden',
                'Inventario' => 'inventario',
                'Insumos y Telas' => 'insumos_y_telas',
                'Corte' => 'corte',
                'Bordado' => 'bordado',
                'Estampado' => 'estampado',
                'Costura' => 'costura',
                'Reflectivo' => 'reflectivo',
                'Lavandería' => 'lavanderia',
                'Arreglos' => 'arreglos',
                'Marras' => 'marras',
                'Control Calidad' => 'control_de_calidad',
                'Entrega' => 'entrega',
                'Despacho' => 'despacho'
            ];

            $procesos = collect();
            foreach ($procesosMap as $nombreProceso => $columnaFecha) {
                if (!empty($orden->$columnaFecha)) {
                    $procesos->push((object)[
                        'proceso' => $nombreProceso,
                        'fecha_inicio' => $orden->$columnaFecha,
                    ]);
                }
            }

            $procesos = $procesos->sortBy(function($p) {
                return \Carbon\Carbon::parse($p->fecha_inicio);
            })->values();

            $totalDiasHabiles = 0;
            if ($procesos->count() > 0) {
                $fechaInicio = \Carbon\Carbon::parse($procesos->first()->fecha_inicio);
                $fechaFin = \Carbon\Carbon::parse($procesos->last()->fecha_inicio);
                $totalDiasHabiles = $this->calcularDiasHabilesBatch($fechaInicio, $fechaFin, $festivos);
            }

            return response()->json([
                'numero_pedido' => $orden->pedido,
                'cliente' => $orden->cliente,
                'fecha_inicio' => $orden->fecha_de_creacion_de_orden,
                'procesos' => $procesos,
                'total_dias_habiles' => $totalDiasHabiles,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error en getProcesosTablaOriginal Bodega: ' . $e->getMessage());
            return response()->json(['error' => 'Orden no encontrada'], 404);
        }
    }

    public function calcularDiasAPI(Request $request, $numeroPedido)
    {
        try {
            if (!$numeroPedido) {
                return response()->json(['error' => 'Número de pedido requerido'], 400);
            }

            $festivos = \App\Models\Festivo::pluck('fecha')->toArray();
            $orden = TablaOriginalBodega::where('pedido', $numeroPedido)->first();
            if (!$orden) {
                return response()->json(['error' => 'Orden no encontrada'], 404);
            }

            $resultado = $this->calcularTotalDiasBatch([$orden], $festivos);
            $diasCalculados = $resultado[$numeroPedido] ?? 0;

            return response()->json([
                'success' => true,
                'numero_pedido' => $numeroPedido,
                'total_dias' => intval($diasCalculados),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error en calcularDiasAPI Bodega: ' . $e->getMessage());
            return response()->json(['error' => 'Error al calcular días'], 500);
        }
    }

    private function calcularTotalDiasBatch(array $ordenes, array $festivos): array
    {
        $resultados = [];

        foreach ($ordenes as $orden) {
            $ordenPedido = $orden->pedido;

            if (!$orden->fecha_de_creacion_de_orden) {
                $resultados[$ordenPedido] = 0;
                continue;
            }

            try {
                $fechaCreacion = Carbon::parse($orden->fecha_de_creacion_de_orden);

                if ($orden->estado === 'Entregado') {
                    $fechaDespacho = $orden->despacho ? Carbon::parse($orden->despacho) : null;
                    $dias = $fechaDespacho ? $this->calcularDiasHabilesBatch($fechaCreacion, $fechaDespacho, $festivos) : 0;
                } else {
                    $dias = $this->calcularDiasHabilesBatch($fechaCreacion, Carbon::now(), $festivos);
                }

                $resultados[$ordenPedido] = max(0, $dias);
            } catch (\Exception $e) {
                $resultados[$ordenPedido] = 0;
            }
        }

        return $resultados;
    }

    private function calcularDiasHabilesBatch(Carbon $inicio, Carbon $fin, array $festivos): int
    {
        $totalDays = $inicio->diffInDays($fin);
        $weekends = $this->contarFinesDeSemanaBatch($inicio, $fin);

        $festivosEnRango = array_filter($festivos, function ($festivo) use ($inicio, $fin) {
            $fechaFestivo = Carbon::parse($festivo);
            return $fechaFestivo->between($inicio, $fin);
        });

        $festivosUnicos = [];
        foreach ($festivosEnRango as $festivo) {
            $fecha = Carbon::parse($festivo)->format('Y-m-d');
            $festivosUnicos[$fecha] = $festivo;
        }
        
        $holidaysInRange = count($festivosUnicos);
        $businessDays = $totalDays - $weekends - $holidaysInRange;

        return max(0, $businessDays);
    }

    private function contarFinesDeSemanaBatch(Carbon $start, Carbon $end): int
    {
        $totalDays = $start->diffInDays($end) + 1;
        $startDay = $start->dayOfWeek;

        $fullWeeks = floor($totalDays / 7);
        $extraDays = $totalDays % 7;

        $weekends = $fullWeeks * 2;

        for ($i = 0; $i < $extraDays; $i++) {
            $day = ($startDay + $i) % 7;
            if ($day === 0 || $day === 6) $weekends++;
        }

        return $weekends;
    }
}
