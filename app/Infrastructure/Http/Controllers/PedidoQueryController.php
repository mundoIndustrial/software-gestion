<?php

namespace App\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Application\Pedidos\UseCases\ObtenerPedidoTransformadoUseCase;
use App\Application\Pedidos\UseCases\ObtenerDetalleCompletoUseCase;
use App\Application\Pedidos\UseCases\ObtenerDatosEdicionUseCase;
use App\Application\Pedidos\UseCases\ObtenerAnchoMetrajePrendaUseCase;
use App\Application\Pedidos\UseCases\ObtenerEncargadosPorAreaUseCase;
use App\Application\Pedidos\UseCases\ListarPedidosPorClienteUseCase;


/**
 * PedidoQueryController
 *
 * Lado lectura (CQRS read side) para pedidos.
 * Maneja show, detalle completo para recibos, datos de edición y catálogos de ancho/metraje.
 */
class PedidoQueryController extends Controller
{
    public function __construct(
        private ObtenerPedidoTransformadoUseCase $obtenerPedidoTransformadoUseCase,
        private ObtenerDetalleCompletoUseCase $obtenerDetalleCompletoUseCase,
        private ObtenerDatosEdicionUseCase $obtenerDatosEdicionUseCase,
        private ObtenerAnchoMetrajePrendaUseCase $obtenerAnchoMetrajePrendaUseCase,
        private ObtenerEncargadosPorAreaUseCase $obtenerEncargadosPorAreaUseCase,
        private ListarPedidosPorClienteUseCase $listarPedidosPorClienteUseCase,
    ) {}

    /**
     * GET /api/pedidos/{id}
     */
    public function show(int $id): JsonResponse
    {
        $response = $this->obtenerPedidoTransformadoUseCase->ejecutar($id);

        return response()->json([
            'success' => true,
            'data' => $response->toArray()
        ], 200);
    }

    /**
     * GET /api/pedidos/cliente/{clienteId}
     */
    public function listarPorCliente(int $clienteId): JsonResponse
    {
        $response = $this->listarPedidosPorClienteUseCase->ejecutar($clienteId);

        return response()->json([
            'success' => true,
            'data' => array_map(fn($dto) => $dto->toArray(), $response)
        ], 200);
    }

    /**
     * GET /asesores/pedidos/{id}/recibos-datos
     *
     */
    public function obtenerDetalleCompleto(int $id, bool $filtrarProcesosPendientes = false): JsonResponse
    {
        try {
            $response = $this->obtenerDetalleCompletoUseCase->ejecutar($id, $filtrarProcesosPendientes);

            return response()->json([
                'success' => true,
                'data' => $response->toArray()
            ], 200);
        } catch (\DomainException $e) {
            \Log::warning('[PedidoQueryController::obtenerDetalleCompleto] Domain Error', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'error_code' => 'DOMAIN_ERROR',
                'message' => $e->getMessage()
            ], 403);
        } catch (\Exception $e) {
            \Log::error('[PedidoQueryController::obtenerDetalleCompleto] Error inesperado', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'error_code' => 'SERVER_ERROR',
                'message' => 'Error al obtener datos del pedido'
            ], 500);
        }
    }

    /**
     * GET /asesores/pedidos/{id}/editar-datos
     */
    /**
     * GET /api/pedidos/{id}/datos-edicion
     */
    public function obtenerDatosEdicion(int $id): JsonResponse
    {
        $response = $this->obtenerDatosEdicionUseCase->ejecutar($id);

        return response()->json([
            'success' => true,
            'data' => $response->toArray()
        ], 200);
    }

    /**
     * GET /pedidos-public/{pedidoId}/ancho-metraje-prenda/{prendaId}
     */
    public function obtenerAnchoMetrajePrendaPublico(int $pedidoId, int $prendaId, \Illuminate\Http\Request $request): JsonResponse
    {
        $numeroRecibo = $request->query('numero_recibo') ? (int) $request->query('numero_recibo') : null;
        $consecutivoReciboId = $request->query('consecutivo_recibo_id') ? (int) $request->query('consecutivo_recibo_id') : null;
        $response = $this->obtenerAnchoMetrajePrendaUseCase->ejecutar($pedidoId, $prendaId, $numeroRecibo, $consecutivoReciboId);
        return response()->json($response->toArray(), 200);
    }

    /**
     * GET /api/areas/{area}/encargados
     */
    public function obtenerEncargadosPorArea(string $area): JsonResponse
    {
        $response = $this->obtenerEncargadosPorAreaUseCase->ejecutar($area);

        return response()->json($response, 200);
    }
}
