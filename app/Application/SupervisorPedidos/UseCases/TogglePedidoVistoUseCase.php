<?php

namespace App\Application\SupervisorPedidos\UseCases;

use App\Application\SupervisorPedidos\DTOs\TogglePedidoVistoRequest;
use App\Application\SupervisorPedidos\DTOs\TogglePedidoVistoResponse;
use App\Application\SupervisorPedidos\Services\PedidoProduccionReadService;

class TogglePedidoVistoUseCase
{
    public function __construct(
        private readonly PedidoProduccionReadService $readService
    ) {}

    public function execute(TogglePedidoVistoRequest $request): TogglePedidoVistoResponse
    {
        try {
            $visto = $this->readService->togglePedidoVisto(
                $request->getPedidoId(),
                $request->getUserId()
            );

            return new TogglePedidoVistoResponse(
                success: true,
                message: $visto ? 'Pedido marcado como visto' : 'Pedido desmarcado',
                visto: $visto
            );

        } catch (\Throwable $e) {
            throw new \DomainException('Error al cambiar estado de visualización del pedido: ' . $e->getMessage());
        }
    }
}
