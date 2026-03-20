<?php

namespace App\Application\SupervisorPedidos\UseCases;

use App\Application\SupervisorPedidos\DTOs\ChangeOrderStatusRequest;
use App\Application\SupervisorPedidos\DTOs\ChangeOrderStatusResponse;
use App\Domain\SupervisorPedidos\Repositories\OrderRepository;

class ChangeOrderStatusUseCase
{
    public function __construct(
        private OrderRepository $orderRepository
    ) {}

    /**
     * Cambiar estado de la orden
     */
    public function execute(ChangeOrderStatusRequest $request): ChangeOrderStatusResponse
    {
        try {
            // Validar que la orden exista
            $order = $this->orderRepository->findById($request->getOrderId());
            
            if (!$order) {
                throw new \DomainException('Orden no encontrada');
            }

            // Validar estados permitidos
            $allowedStates = ['No iniciado', 'En Ejecución', 'Entregado', 'Anulada'];
            if (!in_array($request->getStatus(), $allowedStates)) {
                throw new \DomainException('Estado no permitido: ' . $request->getStatus());
            }

            $oldStatus = $order['estado'] ?? null;

            // Actualizar estado
            $resultado = $this->orderRepository->updateStatus(
                $request->getOrderId(),
                $request->getStatus()
            );

            return new ChangeOrderStatusResponse(
                success: true,
                message: 'Estado actualizado correctamente',
                orderId: $request->getOrderId(),
                oldStatus: $oldStatus,
                newStatus: $request->getStatus(),
                order: $resultado
            );
        } catch (\Exception $e) {
            throw new \DomainException('Error al cambiar estado: ' . $e->getMessage());
        }
    }
}
