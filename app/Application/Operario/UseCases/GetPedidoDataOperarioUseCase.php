<?php

namespace App\Application\Operario\UseCases;

use App\Application\Pedidos\UseCases\ObtenerDetalleCompletoUseCase;
use App\Domain\Operario\Repositories\PedidoProduccionOperarioReadRepository;
use Illuminate\Http\Request;

class GetPedidoDataOperarioUseCase
{
    public function __construct(
        private readonly PedidoProduccionOperarioReadRepository $pedidos,
        private readonly ObtenerDetalleCompletoUseCase $obtenerDetalleCompletoUseCase,
        private readonly ObtenerDatosRecibosOperarioUseCase $obtenerDatosRecibosOperarioUseCase,
    ) {}

    /**
     * @return array{status:int,payload:array<string,mixed>}
     */
    public function execute(int $numeroPedido, Request $request): array
    {
        $tipoRecibo = strtoupper(trim((string) $request->query('tipo_recibo', '')));
        $parcialId = $request->query('parcial_id');
        if ($parcialId) {
            // Si llega parcial_id, el flujo parcial debe resolverse por el ID del parcial
            // y conservar el tipo_recibo real (REFLECTIVO, COSTURA, etc.) cuando exista.
            return $this->obtenerDatosRecibosOperarioUseCase->execute((int) $numeroPedido, $request);
        }

        // OJO: este endpoint recibe un NÚMERO de pedido, no un ID. Evitar ambigüedad con findByIdOrNumero.
        $pedido = $this->pedidos->findByNumeroWithPrendas((int) $numeroPedido);
        if (!$pedido) {
            return [
                'status' => 404,
                'payload' => [
                    'success' => false,
                    'error' => 'not found',
                    'message' => 'Pedido no encontrado',
                ],
            ];
        }

        try {
            // Ejecutar por ID real para evitar colisión id==numero.
            $response = $this->obtenerDetalleCompletoUseCase->ejecutar((int) $pedido->id, false);

            return [
                'status' => 200,
                'payload' => [
                    'success' => true,
                    'data' => $response->toArray(),
                ],
            ];
        } catch (\DomainException $e) {
            return [
                'status' => 403,
                'payload' => [
                    'success' => false,
                    'error_code' => 'DOMAIN_ERROR',
                    'message' => $e->getMessage(),
                ],
            ];
        } catch (\Exception $e) {
            \Log::error('[GetPedidoDataOperarioUseCase] Error inesperado', [
                'numero_pedido' => $numeroPedido,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'status' => 500,
                'payload' => [
                    'success' => false,
                    'error_code' => 'SERVER_ERROR',
                    'message' => 'Error al obtener datos del pedido',
                ],
            ];
        }
    }
}
