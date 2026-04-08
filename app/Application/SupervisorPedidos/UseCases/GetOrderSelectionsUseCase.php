<?php

namespace App\Application\SupervisorPedidos\UseCases;

use App\Application\SupervisorPedidos\DTOs\GetOrderSelectionsResponse;
use App\Application\SupervisorPedidos\Services\PedidoProduccionReadService;

class GetOrderSelectionsUseCase
{
    public function __construct(
        private readonly PedidoProduccionReadService $readService
    ) {}

    public function execute(int $userId): GetOrderSelectionsResponse
    {
        try {
            $selections = $this->readService->getOrderSelectionsForUser($userId);

            return new GetOrderSelectionsResponse(
                success: true,
                message: 'Selecciones obtenidas correctamente',
                selections: $selections,
                totalSelections: count($selections)
            );
        } catch (\Throwable $e) {
            throw new \DomainException('Error al obtener las selecciones: ' . $e->getMessage());
        }
    }
}
