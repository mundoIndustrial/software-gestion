<?php

namespace App\Infrastructure\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Infrastructure\Containers\RegistroOrdenQueryServicesContainer;
use App\Infrastructure\Containers\RegistroOrdenUseCasesFacade;
use App\Application\Orders\Services\OrderDescriptionService;

/**
 * RegistroOrdenQueryController - Query/Search/Filter Layer
 * Responsabilidad única: Búsquedas, filtros y consultas de órdenes
 * Cumple: SRP
 * NOTA: Métodos CRUD (getNextPedido, validatePedido) fueron movidos a RegistroOrdenController
 */
class RegistroOrdenQueryController extends Controller
{
    use RegistroOrdenExceptionHandler;

    protected RegistroOrdenQueryServicesContainer $queryServicesContainer;
    protected RegistroOrdenUseCasesFacade $useCasesFacade;
    protected OrderDescriptionService $orderDescriptionService;

    public function __construct(
        RegistroOrdenQueryServicesContainer $queryServicesContainer,
        RegistroOrdenUseCasesFacade $useCasesFacade,
        OrderDescriptionService $orderDescriptionService,
    ) {
        $this->queryServicesContainer = $queryServicesContainer;
        $this->useCasesFacade = $useCasesFacade;
        $this->orderDescriptionService = $orderDescriptionService;
    }

    /**
     * Listar órdenes con paginación, búsqueda y filtros
     * GET /registros
     */
    public function index(Request $request)
    {
        $result = $this->useCasesFacade->getOrdersQueryUseCase->execute($request);

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
        $result = $this->useCasesFacade->getOrderDetailsQueryUseCase->execute((string) $pedido);
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
        $result = $this->useCasesFacade->getOrderImagesQueryUseCase->execute((string) $pedido, $tipo);
        return response()->json($result['data'] ?? [], $result['status'] ?? 200);
    }

    /**
     * Obtener descripción de prendas
     * GET /registros/{pedido}/descripcion-prendas
     */
    public function getDescripcionPrendas($pedido)
    {
        $result = $this->useCasesFacade->getDescripcionPrendasUseCase->execute($pedido);
        $statusCode = $this->resolveStatusCode($result['success'] ?? null);
        return response()->json($result, $statusCode);
    }

    /**
     * Resolver código de status basado en el resultado
     */
    private function resolveStatusCode($success): int
    {
        if ($success === true) {
            return 200;
        }

        if ($success === false) {
            return 404;
        }

        return 500;
    }

    /**
     * Calcular días de una orden
     * GET /registros/{pedido}/calcular-dias
     */
    public function calcularDiasAPI(Request $request, $numeroPedido)
    {
        $result = $this->useCasesFacade->calcularDiasUseCase->execute($numeroPedido);
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
        $result = $this->useCasesFacade->calcularDiasBatchUseCase->execute($numeroPedidos);
        $statusCode = $result['success'] ? 200 : 404;
        return response()->json(['success' => $result['success'], ...$result['data'] ?? []], $statusCode);
    }

    /**
     * Calcular fecha estimada de entrega
     * POST /api/registros/{id}/calcular-fecha-estimada
     * Lanza: CalcularFechaEstimadaException (manejada por ExceptionHandler)
     */
    public function calcularFechaEstimada(Request $request, $id)
    {
        $validated = $request->validate(['dia_de_entrega' => 'required|integer|min:1']);
        
        $result = $this->useCasesFacade->calcularFechaEstimadaUseCase->execute((int)$id, (int)$validated['dia_de_entrega']);
        
        return response()->json(['success' => true, ...$result], 200);
    }


    /**
     * GET /registros/{pedido}/recibos-datos
     * Obtener datos completos del pedido para el sistema de recibos
     * Compatible con el módulo PedidosRecibosModule
     */
    public function getRecibosDatos($pedido)
    {
        try {
            $esInsumos = request()->headers->get('referer') && str_contains(request()->headers->get('referer'), 'insumos/materiales');
            $result = $this->useCasesFacade->getRecibosDatosUseCase->execute($pedido, $esInsumos);
            
            return response()->json([
                'success' => true,
                'data' => $result
            ], 200);
        } catch (\App\Exceptions\GetRecibosDatosException $e) {
            return $this->handleRegistroOrdenException($e);
        } catch (\DomainException|\Exception $e) {
            return $this->buildErrorResponse($pedido, $e);
        }
    }

    /**
     * Construir respuesta de error para getRecibosDatos
     */
    private function buildErrorResponse(string $pedido, \Throwable $e)
    {
        if ($e instanceof \DomainException) {
            return response()->json([
                'success' => false,
                'error_code' => 'DOMAIN_ERROR',
                'message' => $e->getMessage()
            ], 403);
        }

        \Log::error('[RegistroOrdenQueryController::getRecibosDatos] Error inesperado', [
            'pedido' => $pedido,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'success' => false,
            'error_code' => 'SERVER_ERROR',
            'message' => 'Error al obtener datos del pedido'
        ], 500);
    }
    
    /**
     * Obtener el consecutivo de costura para un pedido
     * GET /registros/{pedido}/consecutivo-costura?prenda_id={prenda_id}
     */
    public function getConsecutivoCostura($pedido)
    {
        try {
            $prendaId    = request()->query('prenda_id');
            $numeroRecibo = request()->query('numero_recibo');

            \Log::info('[getConsecutivoCostura] Obteniendo consecutivo', [
                'pedido'        => $pedido,
                'prenda_id'     => $prendaId,
                'numero_recibo' => $numeroRecibo,
            ]);

            $result = $this->useCasesFacade->getConsecutivoCosturaUseCase->execute($pedido, $prendaId, $numeroRecibo);
            
            \Log::info('[getConsecutivoCostura] Resultado exitoso', [
                'consecutivo' => $result['consecutivo'] ?? null,
                'area' => $result['area'] ?? null
            ]);
            
            return response()->json(array_merge(
                ['success' => true],
                $result
            ), 200);
            
        } catch (\App\Exceptions\GetConsecutivoCosturaException $e) {
            return $this->handleConsecutivoCosturaException($e, $pedido);
        } catch (\DomainException|\Exception $e) {
            return $this->handleConsecutivoCosturaError($e, $pedido);
        }
    }

    /**
     * Manejar excepción específica de GetConsecutivoCosturaException
     */
    private function handleConsecutivoCosturaException(\App\Exceptions\GetConsecutivoCosturaException $e, string $pedido)
    {
        \Log::error('[getConsecutivoCostura] GetConsecutivoCosturaException: ' . $e->getMessage(), [
            'pedido' => $pedido,
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
            'error_code' => $e->getCode()
        ], 404);
    }

    /**
     * Manejar errores de dominio y genéricos en getConsecutivoCostura
     */
    private function handleConsecutivoCosturaError(\Throwable $e, string $pedido)
    {
        $statusCode = $e instanceof \DomainException ? 400 : 500;
        $logMessage = $e instanceof \DomainException
            ? 'DomainException'
            : 'Exception';
        
        \Log::error("[getConsecutivoCostura] $logMessage: " . $e->getMessage(), [
            'pedido' => $pedido,
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json([
            'success' => false,
            'message' => $e instanceof \DomainException
                ? $e->getMessage()
                : 'Error al obtener datos del consecutivo de costura',
            'error' => $e instanceof \DomainException ? null : $e->getMessage()
        ], $statusCode);
    }

    /**
     * Obtener seguimiento por prenda para un pedido
     * GET /registros/{pedido}/seguimiento-prenda
     */
    public function getSeguimientoPorPrenda(Request $request, $pedido)
    {
        try {
            $prendaId = $request->query('prenda_id');
            $numeroRecibo = $request->query('numero_recibo');
            $pedidoParcialId = $request->query('pedido_parcial_id');
            $tipoRecibo = $request->query('tipo_recibo');

            $result = $this->useCasesFacade->getSeguimientoPorPrendaUseCase->execute(
                $pedido,
                $prendaId !== null ? (string) $prendaId : null,
                $numeroRecibo !== null ? (string) $numeroRecibo : null,
                $pedidoParcialId !== null ? (string) $pedidoParcialId : null,
                $tipoRecibo !== null ? (string) $tipoRecibo : null
            );
            
            if (!$result['success']) {
                return response()->json($result, 404);
            }

            return response()->json([
                'pedido' => $result['pedido'],
                'prendas' => $result['prendas'],
                'areas_config' => $result['areas_config'],
            ]);
        } catch (\Exception $e) {
            \Log::error('[RegistroOrdenQueryController::getSeguimientoPorPrenda] Error inesperado', [
                'pedido' => $pedido,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error_code' => 'SERVER_ERROR',
                'message' => 'Error al obtener seguimiento por prenda'
            ], 500);
        }
    }

    /**
     * Obtener novedades de un pedido específico
     */
    public function getNovedades($id)
    {
        $result = $this->useCasesFacade->getNovedadesUseCase->execute($id);
        $statusCode = $result['success'] ? 200 : 404;
        return response()->json([...($result['data'] ?? []), 'message' => $result['message']], $statusCode);
    }
}
