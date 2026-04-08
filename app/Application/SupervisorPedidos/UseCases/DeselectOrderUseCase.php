<?php

namespace App\Application\SupervisorPedidos\UseCases;

use App\Application\SupervisorPedidos\DTOs\SelectOrderRequest;
use App\Application\SupervisorPedidos\DTOs\SelectOrderResponse;
use App\Application\SupervisorPedidos\Services\PedidoProduccionReadService;

class DeselectOrderUseCase
{
    public function __construct(
        private readonly PedidoProduccionReadService $readService
    ) {}

    public function execute(SelectOrderRequest $request): SelectOrderResponse
    {
        try {
            $this->readService->deselectOrderForUser(
                $request->getOrderId(),
                $request->getUserId()
            );

            return new SelectOrderResponse(
                success: true,
                message: 'Pedido deseleccionado correctamente',
                selection: []
            );
        } catch (\Throwable $e) {
            throw new \DomainException('Error al deseleccionar el pedido: ' . $e->getMessage());
        }
    }
}
