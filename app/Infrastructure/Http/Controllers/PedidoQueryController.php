<?php

namespace App\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Application\Pedidos\UseCases\ObtenerPedidoUseCase;
use App\Application\Pedidos\UseCases\ListarPedidosPorClienteUseCase;
use App\Application\Pedidos\QueryHandlers\ObtenerDetalleCompletoQueryHandler;
use App\Application\Pedidos\QueryHandlers\ObtenerDatosEdicionQueryHandler;
use App\Models\PedidoAnchoGeneral;
use App\Models\PedidoMetrajeColor;
use Illuminate\Support\Facades\Log;
use App\Infrastructure\Http\Controllers\PedidoProduccion;
/**
 * PedidoQueryController
 *
 * Lado lectura (CQRS read side) para pedidos.
 * Patrón: Query/Handler
 * Responsabilidad: Orquestar handlers y retornar respuestas HTTP
 * 
 * CAMBIOS DDD+SOLID:
 * - Usa QueryHandlers en lugar de lógica directa
 * - Inyecta dependencias de dominio
 * - Separación de concerns (controlador -> handler -> servicios)
 */
class PedidoQueryController extends Controller
{
    public function __construct(
        private ObtenerPedidoUseCase $obtenerPedidoUseCase,
        private ListarPedidosPorClienteUseCase $listarPedidosPorClienteUseCase,
        private ObtenerDetalleCompletoQueryHandler $obtenerDetalleHandler,
        private ObtenerDatosEdicionQueryHandler $obtenerDatosEdicionHandler,
    ) {}

    /**
     * GET /api/pedidos/{id}
     * 
     * Obtener detalle completo del pedido con todas sus prendas y procesos
     */
    public function show(int $id): JsonResponse
    {
        try {
            $dto = $this->obtenerDetalleHandler->handle($id);

            return response()->json([
                'success' => true,
                'data' => $dto->toArray()
            ], 200);

        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 404);

        } catch (\Exception $e) {
            Log::error('[PedidoQueryController::show] Error', [
                'pedido_id' => $id,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener pedido'
            ], 500);
        }
    }

    /**
     * GET /api/pedidos/cliente/{clienteId}
     */
    public function listarPorCliente(int $clienteId): JsonResponse
    {
        try {
            $response = $this->listarPedidosPorClienteUseCase->ejecutar($clienteId);

            return response()->json([
                'success' => true,
                'data' => array_map(fn($dto) => $dto->toArray(), $response)
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al listar pedidos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /asesores/pedidos/{id}/recibos-datos
     *
     * Datos completos del pedido para recibos con filtrado por rol
     * Flujo: Controlador -> QueryHandler -> Servicios de Dominio/Aplicación
     */
    public function obtenerDetalleCompleto(int $id, bool $filtrarProcesosPendientes = false): JsonResponse
    {
        try {
            $dto = $this->obtenerDetalleHandler->handle($id, $filtrarProcesosPendientes);

            return response()->json([
                'success' => true,
                'data' => $dto->toArray()
            ], 200);

        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 403);

        } catch (\Exception $e) {
            Log::error('[PedidoQueryController::obtenerDetalleCompleto] Error', [
                'pedido_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /asesores/pedidos/{id}/editar-datos
     * 
     * Obtener datos de pedido para edición
     */
    public function obtenerDatosEdicion(int $id): JsonResponse
    {
        try {
            $dto = $this->obtenerDatosEdicionHandler->handle($id);

            return response()->json([
                'success' => true,
                'data' => $dto->toArray()
            ], 200);

        } catch (\DomainException $e) {
            Log::warning('[PedidoQueryController::obtenerDatosEdicion]', [
                'pedido_id' => $id,
                'message' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Pedido no encontrado'
            ], 404);

        } catch (\Exception $e) {
            Log::error('[PedidoQueryController::obtenerDatosEdicion] Error', [
                'pedido_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener datos del pedido',
            ], 500);
        }
    }

    /**
     * GET /pedidos-public/{pedidoId}/ancho-metraje-prenda/{prendaId}
     */
    public function obtenerAnchoMetrajePrendaPublico($pedidoId, $prendaId)
    {
        try {
            $pedido = PedidoProduccion::find($pedidoId);
            if (!$pedido) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pedido no encontrado'
                ], 404);
            }

            $anchoGeneral = PedidoAnchoGeneral::where('pedido_produccion_id', $pedidoId)
                ->where('prenda_pedido_id', $prendaId)
                ->latest('created_at')
                ->first();

            $metrajesPorColor = PedidoMetrajeColor::where('pedido_produccion_id', $pedidoId)
                ->where('prenda_pedido_id', $prendaId)
                ->latest('created_at')
                ->get();

            $tipoModo = null;
            if ($anchoGeneral && $anchoGeneral->tipo_modo) {
                $tipoModo = $anchoGeneral->tipo_modo;
            } elseif ($metrajesPorColor->isNotEmpty() && $metrajesPorColor->first()->tipo_modo) {
                $tipoModo = $metrajesPorColor->first()->tipo_modo;
            }

            $response = [
                'success' => true,
                'ancho' => $anchoGeneral ? $anchoGeneral->ancho : null,
                'metraje' => $anchoGeneral ? $anchoGeneral->metraje : null,
                'contenido_mano' => $anchoGeneral ? $anchoGeneral->contenido_mano : null,
                'tipo_modo' => $tipoModo,
                'data' => []
            ];

            if ($metrajesPorColor->isNotEmpty()) {
                $response['data'] = $metrajesPorColor->map(fn($item) => [
                    'color' => $item->color,
                    'metraje' => $item->metraje,
                    'tipo_modo' => $item->tipo_modo ?? 'color'
                ])->toArray();
            }

            return response()->json($response);

        } catch (\Exception $e) {
            Log::error('[PedidoQueryController::obtenerAnchoMetrajePrendaPublico]', [
                'pedido_id' => $pedidoId,
                'prenda_id' => $prendaId,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener ancho y metraje'
            ], 500);
        }
    }

    /**
     * GET /asesores/prendas-pedido/{prendaPedidoId}/fotos
     *
     * @deprecated Pendiente refactorización a DDD
     */
    public function obtenerFotosPrendaPedido($prendaPedidoId): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Esta funcionalidad está siendo refactorizada a DDD'
        ], 501);
    }
}
