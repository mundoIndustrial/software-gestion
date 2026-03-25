<?php
namespace App\Infrastructure\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PedidoProduccion;
use App\Services\RegistroOrdenValidationService;
use App\Services\RegistroOrdenCreationService;
use App\Services\RegistroOrdenUpdateService;
use App\Services\RegistroOrdenDeletionService;
use App\Services\RegistroOrdenNumberService;
use App\Services\RegistroOrdenPrendaService;
use App\Services\RegistroOrdenCacheService;
use App\Services\RegistroOrdenEntregasService;
use App\Services\RegistroOrdenProcessesService;
use App\Models\News;
use Illuminate\Support\Facades\DB;
use App\Services\ReciboCosturaQueryService;
use App\Application\UseCases\Orders\CreateOrderUseCase;
use App\Application\UseCases\Orders\UpdateOrderUseCase;
use App\Application\UseCases\Orders\DeleteOrderUseCase;
use App\Application\UseCases\Orders\GetOrderUseCase;
use App\Application\UseCases\Orders\EditFullOrderUseCase;
use App\Application\UseCases\Orders\AddNovedadUseCase;
use App\Application\UseCases\Orders\SaveDiaEntregaUseCase;
use App\Application\UseCases\Receipts\GetSewingReceiptsUseCase;
use App\Application\UseCases\Orders\FilterOrdersUseCase;
use App\Application\UseCases\Orders\SearchOrdersUseCase;
use App\Application\UseCases\Receipts\GetReflectiveReceiptsUseCase;
use App\Application\UseCases\Orders\UpdateNovedadesUseCase;
use App\Application\UseCases\Orders\GetFilterOptionsUseCase;
use App\Application\UseCases\Orders\GetColumnFilterOptionsUseCase;
use App\Application\UseCases\Orders\UpdatePedidoNumberUseCase;
use Carbon\Carbon;

class RegistroOrdenController extends Controller
{
    use RegistroOrdenExceptionHandler;

    protected $validationService;
    protected $creationService;
    protected $updateService;
    protected $deletionService;
    protected $numberService;
    protected $prendaService;
    protected $cacheService;
    protected $entregasService;
    protected $processesService;
    protected $reciboCosturaQueryService;
    protected $createOrderUseCase;
    protected $updateOrderUseCase;
    protected $deleteOrderUseCase;
    protected $getOrderUseCase;
    protected $editFullOrderUseCase;
    protected $addNovedadUseCase;
    protected $saveDiaEntregaUseCase;
    protected $getSewingReceiptsUseCase;
    protected $filterOrdersUseCase;
    protected $searchOrdersUseCase;
    protected $getReflectiveReceiptsUseCase;
    protected $updateNovedadesUseCase;
    protected $getFilterOptionsUseCase;
    protected $getColumnFilterOptionsUseCase;
    protected $updatePedidoNumberUseCase;

    public function __construct(
        RegistroOrdenValidationService $validationService,
        RegistroOrdenCreationService $creationService,
        RegistroOrdenUpdateService $updateService,
        RegistroOrdenDeletionService $deletionService,
        RegistroOrdenNumberService $numberService,
        RegistroOrdenPrendaService $prendaService,
        RegistroOrdenCacheService $cacheService,
        RegistroOrdenEntregasService $entregasService,
        RegistroOrdenProcessesService $processesService,
        ReciboCosturaQueryService $reciboCosturaQueryService,
        CreateOrderUseCase $createOrderUseCase,
        UpdateOrderUseCase $updateOrderUseCase,
        DeleteOrderUseCase $deleteOrderUseCase,
        GetOrderUseCase $getOrderUseCase,
        EditFullOrderUseCase $editFullOrderUseCase,
        AddNovedadUseCase $addNovedadUseCase,
        SaveDiaEntregaUseCase $saveDiaEntregaUseCase,
        GetSewingReceiptsUseCase $getSewingReceiptsUseCase,
        FilterOrdersUseCase $filterOrdersUseCase,
        SearchOrdersUseCase $searchOrdersUseCase,
        GetReflectiveReceiptsUseCase $getReflectiveReceiptsUseCase,
        UpdateNovedadesUseCase $updateNovedadesUseCase,
        GetFilterOptionsUseCase $getFilterOptionsUseCase,
        GetColumnFilterOptionsUseCase $getColumnFilterOptionsUseCase,
        UpdatePedidoNumberUseCase $updatePedidoNumberUseCase
    )
    {
        $this->validationService = $validationService;
        $this->creationService = $creationService;
        $this->updateService = $updateService;
        $this->deletionService = $deletionService;
        $this->numberService = $numberService;
        $this->prendaService = $prendaService;
        $this->cacheService = $cacheService;
        $this->entregasService = $entregasService;
        $this->processesService = $processesService;
        $this->reciboCosturaQueryService = $reciboCosturaQueryService;
        $this->createOrderUseCase = $createOrderUseCase;
        $this->updateOrderUseCase = $updateOrderUseCase;
        $this->deleteOrderUseCase = $deleteOrderUseCase;
        $this->getOrderUseCase = $getOrderUseCase;
        $this->editFullOrderUseCase = $editFullOrderUseCase;
        $this->addNovedadUseCase = $addNovedadUseCase;
        $this->saveDiaEntregaUseCase = $saveDiaEntregaUseCase;
        $this->getSewingReceiptsUseCase = $getSewingReceiptsUseCase;
        $this->filterOrdersUseCase = $filterOrdersUseCase;
        $this->searchOrdersUseCase = $searchOrdersUseCase;
        $this->getReflectiveReceiptsUseCase = $getReflectiveReceiptsUseCase;
        $this->updateNovedadesUseCase = $updateNovedadesUseCase;
        $this->getFilterOptionsUseCase = $getFilterOptionsUseCase;
        $this->getColumnFilterOptionsUseCase = $getColumnFilterOptionsUseCase;
        $this->updatePedidoNumberUseCase = $updatePedidoNumberUseCase;
    }

    public function getNextPedido()
    {
        $pedidoInfo = $this->numberService->getNextPedidoInfo();
        return response()->json($pedidoInfo);
    }

    public function validatePedido(Request $request)
    {
        $request->validate(['pedido' => 'required|integer']);
        
        $pedido = $request->input('pedido');
        $nextInfo = $this->numberService->getNextPedidoInfo();
        $isValid = $this->numberService->isNextExpected($pedido);

        return response()->json([
            'valid' => $isValid,
            'next_pedido' => $nextInfo['next_pedido'],
        ]);
    }

    public function store(Request $request)
    {
        return $this->createOrderUseCase->execute($request);
    }

    public function update(Request $request, $pedido)
    {
        return $this->updateOrderUseCase->execute($request, $pedido);
    }

   

  
    public function destroy($pedido)
    {
        return $this->deleteOrderUseCase->execute($pedido);
    }

    public function getEntregas($pedido)
    {
        return $this->tryExec(function() use ($pedido) {
            $entregas = $this->entregasService->getEntregas($pedido);
            return response()->json($entregas);
        });
    }

    /**
     * Invalidar cache de Dias calculados para una orden especifica
     * Se ejecuta cuando se actualiza o elimina una orden
     * 
     * Delegado a: RegistroOrdenCacheService::invalidateDaysCache()
     */
    private function invalidarCacheDias($pedido): void
    {
        $this->cacheService->invalidateDaysCache($pedido);
    }

    public function updatePedido(Request $request)
    {
        return response()->json($this->updatePedidoNumberUseCase->execute($request));
    }

    /**
     * Obtener registros por orden (API para el modal de edicion)
     * Retorna las prendas desde la nueva arquitectura
     */
    public function getRegistrosPorOrden($pedido)
    {
        return $this->tryExec(function() use ($pedido) {
            $prendas = $this->prendaService->getPrendasArray($pedido);
            return response()->json($prendas);
        });
    }

    /**
     * Editar orden completa
     */
    public function editFullOrder(Request $request, $pedido)
    {
        return $this->editFullOrderUseCase->execute($request, $pedido);
    }

    /**
     * Actualizar descripcion y regenerar registros_por_orden basado en el contenido
     */
    public function updateDescripcionPrendas(Request $request)
    {
        return $this->tryExec(function() use ($request) {
            // Validar datos
            $validatedData = $this->validationService->validateUpdateDescripcionRequest($request);

            $pedido = $validatedData['pedido'];
            $nuevaDescripcion = $validatedData['descripcion'];

            DB::beginTransaction();

            // Obtener la orden
            $orden = PedidoProduccion::where('numero_pedido', $pedido)->firstOrFail();

            // Parsear descripcion
            $prendas = $this->prendaService->parseDescripcionToPrendas($nuevaDescripcion);
            $procesarRegistros = $this->prendaService->isValidParsedPrendas($prendas);

            // Si hay prendas validas, reemplazarlas
            if ($procesarRegistros) {
                $this->prendaService->replacePrendas($pedido, $prendas);
            }

            // Invalidar cache
            $this->invalidarCacheDias($pedido);

            // Log evento
            News::create([
                'event_type' => 'description_updated',
                'description' => "descripcion y prendas actualizadas para pedido {$pedido}",
                'user_id' => auth()->id(),
                'pedido' => $pedido,
                'metadata' => ['prendas_count' => count($prendas)]
            ]);

            DB::commit();

            // Recargar relaciones
            $orden->load('prendas');

            // Broadcast evento
            broadcast(new \App\Events\OrdenUpdated($orden, 'updated'));

            // Obtener mensaje de resultado
            $mensaje = $this->prendaService->getParsedPrendasMessage($prendas);

            return response()->json([
                'success' => true,
                'message' => $mensaje,
                'prendas_procesadas' => count($prendas),
                'registros_regenerados' => $procesarRegistros
            ]);
        });
    }

    /**
     * Obtener detalles de una orden especifica para el modal
     * GET /orders/{numero_pedido}
     */
    public function show($numeroPedido)
    {
        return $this->getOrderUseCase->execute($numeroPedido);
    }
    

    /**
     * Obtener todas las opciones disponibles para filtros
     * GET /registros/filter-options
     */
    public function getFilterOptions()
    {
        return response()->json($this->getFilterOptionsUseCase->execute());
    }

    /**
     * Obtener opciones de una columna especifica con paginacion y busqueda
     * GET /registros/filter-column-options/{column}
     */
    public function getColumnFilterOptions($column, Request $request)
    {
        return response()->json($this->getColumnFilterOptionsUseCase->execute($column, $request));
    }

    /**
     * Filtrar ordenes por criterios especificos
     * POST /registros/filter-orders
     */
    public function filterOrders(Request $request)
    {
        return response()->json($this->filterOrdersUseCase->execute($request));
    }
    

    /**
     * busqueda simple en tiempo real
     * POST /registros/search
     */
    public function searchOrders(Request $request)
    {
        return response()->json($this->searchOrdersUseCase->execute($request));
    }

    /**
     * Agrega una nueva novedad al final del campo (con usuario, fecha y hora)
     * Endpoint: POST /api/ordenes/{numero_pedido}/novedades/add
     */
    public function addNovedad(Request $request, $numeroPedido)
    {
        return $this->addNovedadUseCase->execute($request, $numeroPedido);
    }

    public function updateNovedades(Request $request, $numeroPedido)
    {
        return $this->updateNovedadesUseCase->execute($request, $numeroPedido);
    }

    /**
     * Generar descripcion detallada de una prenda (formato recibo)
     */
    private function generarDescripcionPrenda($prenda, $indexPrenda = 1)
    {
        try {
            $lineas = [];
            $nombrePrenda = $prenda->nombre_prenda ?? $prenda->nombre ?? 'SIN NOMBRE';
            $lineas[] = "PRENDA {$indexPrenda}: {$nombrePrenda}";
            
            // Obtener color y tela de la primera variante (color/tela combinacion)
            if ($prenda->coloresTelas && $prenda->coloresTelas->count() > 0) {
                $primerColorTela = $prenda->coloresTelas->first();
                $tela = $primerColorTela && $primerColorTela->tela ? $primerColorTela->tela->nombre ?? $primerColorTela->tela : '-';
                $color = $primerColorTela && $primerColorTela->color ? $primerColorTela->color->nombre ?? $primerColorTela->color : '-';
                $ref = $primerColorTela && $primerColorTela->tela ? $primerColorTela->tela->referencia ?? '' : '';
                
                $lineas[] = "TELA: {$tela} / COLOR: {$color}" . ($ref ? " (REF: {$ref})" : '');
            }
            
            // Manga
            if ($prenda->variantes && $prenda->variantes->count() > 0) {
                $primerVariante = $prenda->variantes->first();
                if ($primerVariante && $primerVariante->manga) {
                    $manga = strtoupper($primerVariante->manga);
                    $lineas[] = "MANGA: {$manga}";
                }
            }
            
            // Observaciones de manga
            if ($prenda->variantes && $prenda->variantes->count() > 0) {
                $primerVariante = $prenda->variantes->first();
                if ($primerVariante && $primerVariante->manga_obs) {
                    $lineas[] = "OBS. MANGA: {$primerVariante->manga_obs}";
                }
            }
            
            // Bolsillos
            if ($prenda->variantes && $prenda->variantes->count() > 0) {
                $primerVariante = $prenda->variantes->first();
                if ($primerVariante && $primerVariante->bolsillos_obs) {
                    $lineas[] = "BOLSILLOS: {$primerVariante->bolsillos_obs}";
                }
            }
            
            // Broche/Boton
            if ($prenda->variantes && $prenda->variantes->count() > 0) {
                $primerVariante = $prenda->variantes->first();
                if ($primerVariante && $primerVariante->broche) {
                    $broche = strtoupper($primerVariante->broche);
                    $lineas[] = "BROCHE: {$broche}";
                    if ($primerVariante->broche_obs) {
                        $lineas[] = "OBS. BROCHE: {$primerVariante->broche_obs}";
                    }
                }
            }
            
            // Tallas (incluyendo tallas por color)
            $tallasSummary = [];
            
            // Primero, verificar si hay tallas por color
            $tallasPorColor = \DB::table('prenda_pedido_talla_colores')
                ->join('prenda_pedido_tallas', 'prenda_pedido_talla_colores.prenda_pedido_talla_id', '=', 'prenda_pedido_tallas.id')
                ->where('prenda_pedido_tallas.prenda_pedido_id', $prenda->id)
                ->select([
                    'prenda_pedido_tallas.talla',
                    'prenda_pedido_talla_colores.color_nombre',
                    'prenda_pedido_talla_colores.cantidad'
                ])
                ->get();
            
            if ($tallasPorColor->count() > 0) {
                // Hay tallas por color, mostrar en formato TALLA:CANTIDAD-COLOR
                foreach ($tallasPorColor as $tallaColor) {
                    if ($tallaColor->cantidad > 0) {
                        $colorNombre = strtoupper($tallaColor->color_nombre);
                        $tallasSummary[] = "{$tallaColor->talla}:{$tallaColor->cantidad}-{$colorNombre}";
                    }
                }
            } else {
                // No hay tallas por color, usar tallas normales
                if ($prenda->tallas && $prenda->tallas->count() > 0) {
                    foreach ($prenda->tallas as $talla) {
                        $tallaNombre = $talla->talla ?? '-';
                        $cantidad = $talla->cantidad ?? 0;
                        if ($cantidad > 0) {
                            $tallasSummary[] = "{$tallaNombre}: {$cantidad}";
                        }
                    }
                }
            }
            
            if (!empty($tallasSummary)) {
                $lineas[] = "TALLAS: " . implode(", ", $tallasSummary);
            }
            
            $descripcionFinal = implode(" | ", $lineas);
            
            \Log::debug("[GENERAR-DESCRIPCION] descripcion generada", [
                'prenda_id' => $prenda->id,
                'prenda_nombre' => $nombrePrenda,
                'lineas_cantidad' => count($lineas),
                'descripcion_longitud' => strlen($descripcionFinal),
                'descripcion_preview' => substr($descripcionFinal, 0, 150)
            ]);
            
            return $descripcionFinal;
        } catch (\Exception $e) {
            \Log::error("[GENERAR-DESCRIPCION] Error generando descripcion", [
                'error' => $e->getMessage(),
                'prenda_id' => $prenda->id ?? 'unknown'
            ]);
            return null;
        }
    }

    /**
     * Mostrar recibos de costura por numero de recibo
     */
    public function recibosCostura(Request $request)
    {
        $datos = $this->getSewingReceiptsUseCase->execute($request);
        $datos['recibos'] = collect($datos['recibos']);
        return view('registros.recibos-costura', $datos);
    }

    /**
     * Mostrar recibos de reflectivo aprobados
     */
    public function recibosReflectivo(Request $request)
    {
        $datos = $this->getReflectiveReceiptsUseCase->execute($request);
        $datos['recibos'] = collect($datos['recibos']);
        return view('registros.recibos-reflectivo', $datos);
    }

    /**
     * Obtener datos de un recibo de reflectivo especifico como JSON
     */
    public function getReciboReflectivoJson($reciboId)
    {
        try {
            $recibo = DB::table('consecutivos_recibos_pedidos')
                ->where('id', $reciboId)
                ->where('tipo_recibo', 'REFLECTIVO')
                ->where('activo', 1)
                ->first();
            
            if (!$recibo) {
                return response()->json(['success' => false, 'message' => 'Recibo no encontrado'], 404);
            }
            
            $pedido = PedidoProduccion::find($recibo->pedido_produccion_id);
            
            $diasCalculados = 0;
            if ($pedido && $pedido->fecha_de_creacion_de_orden) {
                try {
                    $fechaInicio = $pedido->fecha_de_creacion_de_orden;
                    $fechaFin = \Carbon\Carbon::now();
                    $festivosArray = \App\Models\Festivo::pluck('fecha')->toArray();
                    $festivosSet = [];
                    foreach ($festivosArray as $f) {
                        try { $festivosSet[\Carbon\Carbon::parse($f)->format('Y-m-d')] = true; } catch (\Exception $e) {}
                    }
                    $current = $fechaInicio->copy()->addDay();
                    $totalDays = 0;
                    $maxIterations = 365;
                    $iterations = 0;
                    while ($current <= $fechaFin && $iterations < $maxIterations) {
                        $dateString = $current->format('Y-m-d');
                        $isWeekend = $current->dayOfWeek === 0 || $current->dayOfWeek === 6;
                        $isFestivo = isset($festivosSet[$dateString]);
                        if (!$isWeekend && !$isFestivo) { $totalDays++; }
                        $current->addDay();
                        $iterations++;
                    }
                    $diasCalculados = max(0, $totalDays);
                } catch (\Exception $e) {
                    $diasCalculados = 0;
                }
            }
            
            $nombrePrenda = 'Sin prendas';
            if ($pedido && $pedido->prendas && $pedido->prendas->count() > 0) {
                $primeraPrenda = $pedido->prendas->first();
                $nombrePrenda = $primeraPrenda->nombre_prenda ?? $primeraPrenda->nombre ?? 'Prenda';
            }
            
            return response()->json([
                'success' => true,
                'recibo' => [
                    'id' => $recibo->id,
                    'consecutivo_actual' => $recibo->consecutivo_actual,
                    'pedido_produccion_id' => $recibo->pedido_produccion_id,
                    'prenda_id' => $recibo->prenda_id,
                    'tipo_recibo' => $recibo->tipo_recibo,
                    'estado' => $recibo->estado ?? 'PENDIENTE_INSUMOS',
                    'area' => $recibo->area ?? 'Insumos',
                    'dias_calculados' => $diasCalculados,
                    'nombre_prenda' => $nombrePrenda,
                    'cliente' => $pedido ? $pedido->cliente : '',
                    'numero_pedido' => $pedido ? $pedido->numero_pedido : '',
                    'fecha_creacion' => $pedido && $pedido->fecha_de_creacion_de_orden ? $pedido->fecha_de_creacion_de_orden->format('d/m/Y') : '-',
                    'created_at' => $recibo->created_at,
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Error en getReciboReflectivoJson: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error interno'], 500);
        }
    }
    
    /**
     * Obtener datos de un recibo especifico como JSON (para tiempo real)
     */
    public function getReciboJson($reciboId)
    {
        try {
            $recibo = DB::table('consecutivos_recibos_pedidos')
                ->where('id', $reciboId)
                ->where('tipo_recibo', 'COSTURA')
                ->where('activo', 1)
                ->first();
            
            if (!$recibo) {
                return response()->json(['success' => false, 'message' => 'Recibo no encontrado'], 404);
            }
            
            $pedido = PedidoProduccion::find($recibo->pedido_produccion_id);
            
            // Calcular Dias
            $diasCalculados = 0;
            if ($pedido && $pedido->fecha_de_creacion_de_orden) {
                try {
                    $fechaInicio = $pedido->fecha_de_creacion_de_orden;
                    $fechaFin = \Carbon\Carbon::now();
                    $festivosArray = \App\Models\Festivo::pluck('fecha')->toArray();
                    $festivosSet = [];
                    foreach ($festivosArray as $f) {
                        try { $festivosSet[\Carbon\Carbon::parse($f)->format('Y-m-d')] = true; } catch (\Exception $e) {}
                    }
                    $current = $fechaInicio->copy()->addDay();
                    $totalDays = 0;
                    $maxIterations = 365;
                    $iterations = 0;
                    while ($current <= $fechaFin && $iterations < $maxIterations) {
                        $dateString = $current->format('Y-m-d');
                        $isWeekend = $current->dayOfWeek === 0 || $current->dayOfWeek === 6;
                        $isFestivo = isset($festivosSet[$dateString]);
                        if (!$isWeekend && !$isFestivo) { $totalDays++; }
                        $current->addDay();
                        $iterations++;
                    }
                    $diasCalculados = max(0, $totalDays);
                } catch (\Exception $e) {
                    $diasCalculados = 0;
                }
            }
            
            // Obtener nombre primera prenda
            $nombrePrenda = 'Sin prendas';
            if ($pedido && $pedido->prendas && $pedido->prendas->count() > 0) {
                $primeraPrenda = $pedido->prendas->first();
                $nombrePrenda = $primeraPrenda->nombre_prenda ?? $primeraPrenda->nombre ?? 'Prenda';
            }
            
            return response()->json([
                'success' => true,
                'recibo' => [
                    'id' => $recibo->id,
                    'consecutivo_actual' => $recibo->consecutivo_actual,
                    'pedido_produccion_id' => $recibo->pedido_produccion_id,
                    'prenda_id' => $recibo->prenda_id,
                    'tipo_recibo' => $recibo->tipo_recibo,
                    'estado' => $recibo->estado ?? 'PENDIENTE_INSUMOS',
                    'area' => $recibo->area ?? 'Insumos',
                    'dias_calculados' => $diasCalculados,
                    'nombre_prenda' => $nombrePrenda,
                    'cliente' => $pedido ? $pedido->cliente : '',
                    'numero_pedido' => $pedido ? $pedido->numero_pedido : '',
                    'fecha_creacion' => $pedido && $pedido->fecha_de_creacion_de_orden ? $pedido->fecha_de_creacion_de_orden->format('d/m/Y') : '-',
                    'created_at' => $recibo->created_at,
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Error en getReciboJson: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error interno'], 500);
        }
    }
    
    /**
     * Obtener el area del proceso mas reciente de una prenda
     */
    private function obtenerAreaProcesoMasReciente($pedidoProduccionId, $prendaId = null)
    {
        try {
            \Log::info('[obtenerAreaProcesoMasReciente] Buscando proceso mas reciente', [
                'pedido_produccion_id' => $pedidoProduccionId,
                'prenda_id' => $prendaId
            ]);
            
            // Primero obtener el numero_pedido desde la tabla pedidos_produccion
            $pedido = \DB::table('pedidos_produccion')
                ->where('id', $pedidoProduccionId)
                ->first();
            
            if (!$pedido) {
                \Log::warning('[obtenerAreaProcesoMasReciente] Pedido no encontrado', ['pedido_produccion_id' => $pedidoProduccionId]);
                return 'Sin procesos';
            }
            
            $numeroPedido = $pedido->numero_pedido;
            \Log::info('[obtenerAreaProcesoMasReciente] Usando numero_pedido', [
                'pedido_produccion_id' => $pedidoProduccionId,
                'numero_pedido' => $numeroPedido
            ]);
            
            $query = \DB::table('procesos_prenda')
                ->where('numero_pedido', $numeroPedido)
                ->whereNull('deleted_at');  // Excluir procesos eliminados (soft delete)
            
            // Si se especifica prenda_id, filtrar por esa prenda
            if ($prendaId) {
                // Convertir a entero para asegurar comparacion correcta
                $prendaId = (int)$prendaId;
                $query->where('prenda_pedido_id', $prendaId);
                \Log::info('[obtenerAreaProcesoMasReciente] Filtrando por prenda_id', ['prenda_id' => $prendaId]);
            } else {
                \Log::info('[obtenerAreaProcesoMasReciente] Buscando todos los procesos del pedido');
            }
            
            // Para debugging: ver todos los procesos disponibles
            $todosLosProcesos = $query->get();
            \Log::info('[obtenerAreaProcesoMasReciente] Todos los procesos encontrados:', [
                'total' => $todosLosProcesos->count(),
                'procesos' => $todosLosProcesos->toArray()
            ]);
            
            // Obtener el proceso mas reciente por created_at
            $procesoReciente = $query->orderBy('created_at', 'desc')
                ->first();
            
            if ($procesoReciente) {
                $area = $procesoReciente->proceso;
                \Log::info('[obtenerAreaProcesoMasReciente] Proceso mas reciente encontrado', [
                    'pedido_produccion_id' => $pedidoProduccionId,
                    'numero_pedido' => $numeroPedido,
                    'prenda_id' => $prendaId,
                    'area' => $area,
                    'proceso_id' => $procesoReciente->id,
                    'created_at' => $procesoReciente->created_at
                ]);
                return $area;
            }
            
            \Log::info('[obtenerAreaProcesoMasReciente] No se encontraron procesos', [
                'pedido_produccion_id' => $pedidoProduccionId,
                'numero_pedido' => $numeroPedido,
                'prenda_id' => $prendaId
            ]);
            
            return 'Sin procesos';
            
        } catch (\Exception $e) {
            \Log::error('[obtenerAreaProcesoMasReciente] Error: ' . $e->getMessage(), [
                'pedido_produccion_id' => $pedidoProduccionId,
                'prenda_id' => $prendaId
            ]);
            return 'Error';
        }
    }
    
    /**
     * Obtener el area mas reciente de un pedido (API)
     */
    public function getAreaReciente($id)
    {
        try {
            \Log::info('[getAreaReciente] Obteniendo area mas reciente para pedido', ['pedido_id' => $id]);
            
            $pedido = PedidoProduccion::find($id);
            
            if (!$pedido) {
                return response()->json([
                    'success' => false,
                    'error' => 'Pedido no encontrado'
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'area' => $pedido->area ?? 'Insumos',
                'pedido_id' => $id
            ]);
            
        } catch (\Exception $e) {
            \Log::error('[getAreaReciente] Error: ' . $e->getMessage(), ['pedido_id' => $id]);
            
            return response()->json([
                'success' => false,
                'error' => 'Error al obtener area reciente: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Contar recibos de COSTURA en Ejecucion (area Corte) para la campana
     * GET /api/recibos-costura/ejecutando-corte
     */
    public function contarRecibosEjecutandoCostura()
    {
        try {
            $userId = auth()->id();
            
            // Obtener recibos COSTURA en estado "En Ejecucion" con area "Corte"
            // EXCLUYENDO los que el usuario actual ya marca como visto
            $recibos = DB::table('consecutivos_recibos_pedidos')
                ->where('tipo_recibo', 'COSTURA')
                ->where('estado', 'En Ejecucion')
                ->where('area', 'Corte')
                ->where('activo', 1)
                ->whereNotIn('id', function($query) use ($userId) {
                    $query->select('consecutivo_recibo_id')
                        ->from('recibos_usuario_vistos')
                        ->where('user_id', $userId)
                        ->where('tipo_recibo', 'COSTURA');
                })
                ->select([
                    'id',
                    'consecutivo_actual as numero_recibo',
                    'pedido_produccion_id',
                    'prenda_id',
                    'created_at'
                ])
                ->get();

            // Enriquecer datos con informacion del pedido
            $recibosConInfo = $recibos->map(function ($recibo) {
                $pedido = PedidoProduccion::find($recibo->pedido_produccion_id);
                
                return [
                    'id' => $recibo->id,
                    'numero_recibo' => $recibo->numero_recibo,
                    'cliente' => $pedido ? $pedido->cliente : '-',
                    'pedido_id' => $pedido ? $pedido->numero_pedido : '-',
                    'fecha' => Carbon::parse($recibo->created_at)->format('d/m/Y H:i')
                ];
            });

            return response()->json([
                'success' => true,
                'total' => $recibosConInfo->count(),
                'recibos' => $recibosConInfo->values()->toArray()
            ]);

        } catch (\Exception $e) {
            \Log::error('Error en contarRecibosEjecutandoCostura: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al contar recibos de costura',
                'total' => 0,
                'recibos' => []
            ], 500);
        }
    }

    /**
     * Marcar un recibo de COSTURA como visto por el usuario actual
     * POST /api/recibos-costura/{id}/marcar-visto-corte
     */
    public function marcarReciboVistoCostura($reciboId)
    {
        try {
            $userId = auth()->id();
            
            // Obtener el recibo
            $recibo = DB::table('consecutivos_recibos_pedidos')
                ->where('id', $reciboId)
                ->where('tipo_recibo', 'COSTURA')
                ->first();
            
            if (!$recibo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Recibo no encontrado'
                ], 404);
            }
            
            // Crear o ignorar si ya existe (gracias a unique constraint)
            DB::table('recibos_usuario_vistos')->insertOrIgnore([
                'consecutivo_recibo_id' => $reciboId,
                'user_id' => $userId,
                'tipo_recibo' => 'COSTURA',
                'created_at' => Carbon::now()
            ]);
            
            \Log::info('Recibo de costura marcado como visto', [
                'recibo_id' => $reciboId,
                'user_id' => $userId,
                'numero_recibo' => $recibo->consecutivo_actual
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Recibo marcado como visto',
                'recibo_id' => $reciboId
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error al marcar recibo como visto: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al marcar el recibo como visto'
            ], 500);
        }
    }

    /**
     * Guardar día de entrega y calcular fecha estimada
     * POST /registros/{id}/dia-entrega
     * 
     * Parámetros esperados en Request:
     * - dia_de_entrega: int (entre 1 y 35)
     */
    public function saveDiaEntrega(Request $request, $id)
    {
        try {
            $request->validate([
                'dia_de_entrega' => 'nullable|integer|min:1|max:35'
            ]);

            $diaDeEntrega = $request->input('dia_de_entrega');

            return response()->json(
                $this->saveDiaEntregaUseCase->execute($id, $diaDeEntrega)
            );
        } catch (\InvalidArgumentException $e) {
            \Log::error('Error validación en SaveDiaEntrega: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error en SaveDiaEntrega: ' . $e->getMessage(), [
                'id' => $id,
                'request_data' => $request->all()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar día de entrega: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener recibos de costura en formato JSON con filtros
     * GET /api/recibos-costura
     * 
     * Query params:
     * - estado: string|array (estado del recibo)
     * - area: string|array (área del proceso)
     * - numero_recibo: string|array (número del recibo)
     * - cliente: string|array (nombre del cliente)
     * - dia_entrega: string|array (día de entrega)
     * - fecha_creacion_desde: date (fecha inicial)
     * - fecha_creacion_hasta: date (fecha final)
     * - page: int (página, default: 1)
     * - per_page: int (items por página, default: 25)
     */
    public function getRecibosCosutraJSON(Request $request)
    {
        try {
            // Construir query base
            $query = $this->reciboCosturaQueryService->getBaseQuery();

            // Extraer y sanitizar filtros
            $filters = [
                'estado' => $request->input('estado'),
                'area' => $request->input('area'),
                'numero_recibo' => $request->input('numero_recibo'),
                'cliente' => $request->input('cliente'),
                'dia_entrega' => $request->input('dia_entrega'),
                'fecha_creacion_desde' => $request->input('fecha_creacion_desde'),
                'fecha_creacion_hasta' => $request->input('fecha_creacion_hasta'),
            ];

            // Remover filtros vacíos
            $filters = array_filter($filters, function ($value) {
                return !is_null($value) && $value !== '';
            });

            // Aplicar filtros
            $query = $this->reciboCosturaQueryService->applyFilters($query, $filters);

            // Paginar
            $perPage = min($request->input('per_page', 25), 100);
            $recibos = $this->reciboCosturaQueryService->getPaginatedRecibos($query, $perPage);

            return response()->json([
                'success' => true,
                'data' => $recibos->items(),
                'pagination' => [
                    'current_page' => $recibos->currentPage(),
                    'last_page' => $recibos->lastPage(),
                    'per_page' => $recibos->perPage(),
                    'total' => $recibos->total(),
                    'from' => $recibos->firstItem(),
                    'to' => $recibos->lastItem(),
                ],
                'filters_applied' => $filters,
                'filters_available' => $this->reciboCosturaQueryService->getFilterOptions(),
            ]);

        } catch (\Exception $e) {
            \Log::error('Error en getRecibosCosutraJSON: ' . $e->getMessage(), [
                'filters' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener recibos de costura',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Obtener opciones disponibles para los filtros de recibos
     * GET /api/recibos-costura/filter-options
     * 
     * Retorna los valores válidos para cada filtro disponible
     */
    public function getRecibosCosutraFilterOptions(Request $request)
    {
        try {
            $filterOptions = $this->reciboCosturaQueryService->getFilterOptions();

            return response()->json([
                'success' => true,
                'filter_options' => $filterOptions
            ]);

        } catch (\Exception $e) {
            \Log::error('Error en getRecibosCosutraFilterOptions: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener opciones de filtro',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

}
