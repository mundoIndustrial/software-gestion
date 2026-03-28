<?php

namespace App\Application\SupervisorPedidos\UseCases;

use App\Application\SupervisorPedidos\DTOs\SelectOrderRequest;
use App\Application\SupervisorPedidos\DTOs\SelectOrderResponse;
use App\Application\SupervisorPedidos\Services\PedidoProduccionReadService;

class SelectOrderUseCase
{
    public function __construct(
        private readonly PedidoProduccionReadService $readService
    ) {}

    public function execute(SelectOrderRequest $request): SelectOrderResponse
    {
        try {
            $selection = $this->readService->selectOrderForUser(
                $request->getOrderId(),
                $request->getUserId()
            );

            return new SelectOrderResponse(
                success: true,
                message: 'Pedido seleccionado correctamente',
                selection: $selection
            );

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            throw new \DomainException('El pedido especificado no existe');
        } catch (\Throwable $e) {
            throw new \DomainException('Error al seleccionar el pedido: ' . $e->getMessage());
        }
    }
}
