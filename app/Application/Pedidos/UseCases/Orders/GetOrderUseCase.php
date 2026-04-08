<?php

namespace App\Application\Pedidos\UseCases\Orders;

use App\Application\Pedidos\Services\PedidoDescriptionService;
use App\Domain\Pedidos\Repositories\PedidoProduccionReadRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * UseCase: Obtener detalles de una orden
 * Responsabilidades:
 * - Recuperar datos completos de la orden
 * - Calcular totales
 * - Formatear para presentación
 */
class GetOrderUseCase
{
    public function __construct(
        private readonly PedidoProduccionReadRepository $pedidoRepository,
        private readonly PedidoDescriptionService $descriptionService,
    ) {}

    /**
     * Ejecutar el caso de uso
     */
    public function execute(int $numeroPedido): array
    {
        $orderData = $this->pedidoRepository->obtenerPedidoDetallePorNumero($numeroPedido);
        if ($orderData === null) {
            throw new ModelNotFoundException("Pedido {$numeroPedido} no encontrado");
        }

        $totales = $this->pedidoRepository->obtenerTotalesPorNumeroPedido($numeroPedido);

        return $this->formatOrderData($orderData, $totales);
    }

    /**
     * Formatear datos de la orden para respuesta
     */
    private function formatOrderData(array $orderData, array $totales): array
    {
        $pedidoModel = $orderData['pedido_model'] ?? null;

        return [
            'id' => $orderData['id'],
            'numero_pedido' => $orderData['numero_pedido'],
            'cliente' => $orderData['cliente'],
            'created_at' => $orderData['created_at'],
            'descripcion_prendas' => $pedidoModel
                ? $this->descriptionService->generatePrendasDescription($pedidoModel)
                : '',
            'estado' => $orderData['estado'],
            'forma_de_pago' => $orderData['forma_de_pago'] ?? '-',
            'area' => $orderData['area'],
            'novedades' => $orderData['novedades'],
            'total_cantidad' => (int) ($totales['total_cantidad'] ?? 0),
            'total_entregado' => (int) ($totales['total_entregado'] ?? 0),
            'cantidad' => (int) ($totales['total_cantidad'] ?? 0),
            'encargado_orden' => '',
            'asesora' => $orderData['asesora'] ?? '',
        ];
    }
}
