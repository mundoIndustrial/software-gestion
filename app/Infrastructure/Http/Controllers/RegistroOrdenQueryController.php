<?php

namespace App\Infrastructure\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Application\UseCases\Orders\GetOrdersQueryUseCase;
use App\Application\UseCases\Orders\GetOrderImagesQueryUseCase;
use App\Application\UseCases\Orders\GetOrderDetailsQueryUseCase;
use App\Services\RegistroOrdenExtendedQueryService;
use App\Services\RegistroOrdenSearchExtendedService;
use App\Services\RegistroOrdenFilterExtendedService;
use App\Services\RegistroOrdenTransformService;
use App\Services\RegistroOrdenProcessService;
use App\Services\RegistroOrdenStatsService;
use App\Services\RegistroOrdenProcessesService;
use App\Services\RegistroOrdenEnumService;
use App\Application\Orders\Services\OrderDescriptionService;
use Carbon\Carbon;

/**
 * RegistroOrdenQueryController - Query/Search/Filter Layer
 * 
 * Responsabilidad única: Búsquedas, filtros y consultas de órdenes
 * Cumple: SRP
 * 
 * NOTA: Métodos CRUD (getNextPedido, validatePedido) fueron movidos a RegistroOrdenController
 */
class RegistroOrdenQueryController extends Controller
{
    use RegistroOrdenExceptionHandler;

    protected $extendedQueryService;
    protected $extendedSearchService;
    protected $extendedFilterService;
    protected $transformService;
    protected $processService;
    protected $statsService;
    protected $processesService;
    protected $enumService;
    protected GetOrdersQueryUseCase $getOrdersQueryUseCase;
    protected OrderDescriptionService $orderDescriptionService;
    protected GetOrderImagesQueryUseCase $getOrderImagesQueryUseCase;
    protected GetOrderDetailsQueryUseCase $getOrderDetailsQueryUseCase;
    protected \App\Application\UseCases\RegistroOrden\GetSeguimientoPorPrendaUseCase $getSeguimientoPorPrendaUseCase;
    protected \App\Application\UseCases\RegistroOrden\GetDescripcionPrendasUseCase $getDescripcionPrendasUseCase;
    protected \App\Application\UseCases\RegistroOrden\GetConsecutivoCosturaUseCase $getConsecutivoCosturaUseCase;
    protected \App\Application\UseCases\RegistroOrden\CalcularDiasUseCase $calcularDiasUseCase;
    protected \App\Application\UseCases\RegistroOrden\CalcularDiasBatchUseCase $calcularDiasBatchUseCase;
    protected \App\Application\UseCases\RegistroOrden\CalcularFechaEstimadaUseCase $calcularFechaEstimadaUseCase;
    protected \App\Application\UseCases\RegistroOrden\GetRecibosDatosUseCase $getRecibosDatosUseCase;
    protected \App\Application\UseCases\RegistroOrden\GetNovedadesUseCase $getNovedadesUseCase;

    public function __construct(
        RegistroOrdenExtendedQueryService $extendedQueryService,
        RegistroOrdenSearchExtendedService $extendedSearchService,
        RegistroOrdenFilterExtendedService $extendedFilterService,
        RegistroOrdenTransformService $transformService,
        RegistroOrdenProcessService $processService,
        RegistroOrdenStatsService $statsService,
        RegistroOrdenProcessesService $processesService,
        RegistroOrdenEnumService $enumService,
        GetOrdersQueryUseCase $getOrdersQueryUseCase,
        OrderDescriptionService $orderDescriptionService,
        GetOrderImagesQueryUseCase $getOrderImagesQueryUseCase,
        GetOrderDetailsQueryUseCase $getOrderDetailsQueryUseCase,
        \App\Application\UseCases\RegistroOrden\GetSeguimientoPorPrendaUseCase $getSeguimientoPorPrendaUseCase,
        \App\Application\UseCases\RegistroOrden\GetDescripcionPrendasUseCase $getDescripcionPrendasUseCase,
        \App\Application\UseCases\RegistroOrden\GetConsecutivoCosturaUseCase $getConsecutivoCosturaUseCase,
        \App\Application\UseCases\RegistroOrden\CalcularDiasUseCase $calcularDiasUseCase,
        \App\Application\UseCases\RegistroOrden\CalcularDiasBatchUseCase $calcularDiasBatchUseCase,
        \App\Application\UseCases\RegistroOrden\CalcularFechaEstimadaUseCase $calcularFechaEstimadaUseCase,
        \App\Application\UseCases\RegistroOrden\GetRecibosDatosUseCase $getRecibosDatosUseCase,
        \App\Application\UseCases\RegistroOrden\GetNovedadesUseCase $getNovedadesUseCase
    )
    {
        $this->extendedQueryService = $extendedQueryService;
        $this->extendedSearchService = $extendedSearchService;
        $this->extendedFilterService = $extendedFilterService;
        $this->transformService = $transformService;
        $this->processService = $processService;
        $this->statsService = $statsService;
        $this->processesService = $processesService;
        $this->enumService = $enumService;
        $this->getOrdersQueryUseCase = $getOrdersQueryUseCase;
        $this->orderDescriptionService = $orderDescriptionService;
        $this->getOrderImagesQueryUseCase = $getOrderImagesQueryUseCase;
        $this->getOrderDetailsQueryUseCase = $getOrderDetailsQueryUseCase;
        $this->getSeguimientoPorPrendaUseCase = $getSeguimientoPorPrendaUseCase;
        $this->getDescripcionPrendasUseCase = $getDescripcionPrendasUseCase;
        $this->getConsecutivoCosturaUseCase = $getConsecutivoCosturaUseCase;
        $this->calcularDiasUseCase = $calcularDiasUseCase;
        $this->calcularDiasBatchUseCase = $calcularDiasBatchUseCase;
        $this->calcularFechaEstimadaUseCase = $calcularFechaEstimadaUseCase;
        $this->getRecibosDatosUseCase = $getRecibosDatosUseCase;
        $this->getNovedadesUseCase = $getNovedadesUseCase;
    }

    /**
     * Listar órdenes con paginación, búsqueda y filtros
     * GET /registros
     */
    public function index(Request $request)
    {
        $result = $this->getOrdersQueryUseCase->execute($request);

        if (($result['type'] ?? null) === 'json') {
            return response()->json($result['data'] ?? [], $result['status'] ?? 200);
        }

        return view($result['view'] ?? 'orders.index', $result['viewData'] ?? []);
    }

    /**
     * Obtener orden específica
     * GET /registros/{pedido}
     */
    public function show($pedido)
    {
        $result = $this->getOrderDetailsQueryUseCase->execute((string) $pedido);
        return response()->json($result['data'] ?? [], $result['status'] ?? 200);
    }

    /**
     * Obtener imágenes de una orden
     * GET /registros/{pedido}/images
     * Parámetro opcional: tipo=logo para obtener solo imágenes de logo
     */
    public function getOrderImages($pedido)
    {
        $tipo = request()->query('tipo'); // 'logo' o null
        $result = $this->getOrderImagesQueryUseCase->execute((string) $pedido, $tipo);
        return response()->json($result['data'] ?? [], $result['status'] ?? 200);
    }

    /**
     * Obtener descripción de prendas
     * GET /registros/{pedido}/descripcion-prendas
     */
    public function getDescripcionPrendas($pedido)
    {
        $result = $this->getDescripcionPrendasUseCase->execute($pedido);
        $statusCode = $result['success'] ? 200 : ($result['success'] === false ? 404 : 500);
        return response()->json($result, $statusCode);
    }

    /**
     * Calcular días de una orden
     * GET /registros/{pedido}/calcular-dias
     */
    public function calcularDiasAPI(Request $request, $numeroPedido)
    {
        $result = $this->calcularDiasUseCase->execute($numeroPedido);
        $statusCode = $result['success'] ? 200 : 404;
        return response()->json(['success' => $result['success'], ...$result['data'] ?? []], $statusCode);
    }

    /**
     * Calcular días de múltiples órdenes
     * POST /registros/calcular-dias-batch
     */
    public function calcularDiasBatchAPI(Request $request)
    {
        $numeroPedidos = $request->input('numero_pedidos', []);
        $result = $this->calcularDiasBatchUseCase->execute($numeroPedidos);
        $statusCode = $result['success'] ? 200 : 404;
        return response()->json(['success' => $result['success'], ...$result['data'] ?? []], $statusCode);
    }

    /**
     * Calcular fecha estimada de entrega
     * POST /api/registros/{id}/calcular-fecha-estimada
     * 
     * Lanza: CalcularFechaEstimadaException (manejada por ExceptionHandler)
     */
    public function calcularFechaEstimada(Request $request, $id)
    {
        $validated = $request->validate(['dia_de_entrega' => 'required|integer|min:1']);
        
        $result = $this->calcularFechaEstimadaUseCase->execute((int)$id, (int)$validated['dia_de_entrega']);
        
        return response()->json(['success' => true, ...$result], 200);
    }


    /**
     * GET /registros/{pedido}/recibos-datos
     * Obtener datos completos del pedido para el sistema de recibos
     * Compatible con el módulo PedidosRecibosModule
     */
    public function getRecibosDatos($pedido)
    {
        $esInsumos = request()->headers->get('referer') && str_contains(request()->headers->get('referer'), 'insumos/materiales');
        $result = $this->getRecibosDatosUseCase->execute($pedido, $esInsumos);
        $statusCode = $result['success'] ? 200 : 404;
        return response()->json($result['success'] ? $result['data'] : $result, $statusCode);
    }
    
    /**
     * Obtener el consecutivo de costura para un pedido
     */
    public function getConsecutivoCostura($pedido)
    {
        $prendaId = request()->query('prenda_id');
        $result = $this->getConsecutivoCosturaUseCase->execute($pedido, $prendaId);
        $statusCode = $result['success'] ? 200 : 404;
        return response()->json($result, $statusCode);
    }

    /**
     * Obtener seguimiento por prenda para un pedido
     * GET /registros/{pedido}/seguimiento-prenda
     */
    public function getSeguimientoPorPrenda($pedido)
    {
        $result = $this->getSeguimientoPorPrendaUseCase->execute($pedido);
        
        if (!$result['success']) {
            return response()->json($result, 404);
        }

        return response()->json([
            'pedido' => $result['pedido'],
            'prendas' => $result['prendas'],
            'areas_config' => $result['areas_config'],
        ]);
    }

    /**
     * Obtener novedades de un pedido específico
     */
    public function getNovedades($id)
    {
        $result = $this->getNovedadesUseCase->execute($id);
        $statusCode = $result['success'] ? 200 : 404;
        return response()->json([...($result['data'] ?? []), 'message' => $result['message']], $statusCode);
    }
}
