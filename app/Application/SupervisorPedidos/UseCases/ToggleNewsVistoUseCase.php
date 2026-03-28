<?php

namespace App\Application\SupervisorPedidos\UseCases;

use App\Application\SupervisorPedidos\DTOs\ToggleNewsVistoRequest;
use App\Application\SupervisorPedidos\DTOs\ToggleNewsVistoResponse;
use App\Application\SupervisorPedidos\Services\PedidoProduccionReadService;

class ToggleNewsVistoUseCase
{
    public function __construct(
        private readonly PedidoProduccionReadService $readService
    ) {}

    public function execute(ToggleNewsVistoRequest $request): ToggleNewsVistoResponse
    {
        try {
            $visto = $this->readService->toggleNewsVisto(
                $request->getNewsId(),
                $request->getUserId()
            );

            return new ToggleNewsVistoResponse(
                success: true,
                message: $visto ? 'Noticia marcada como vista' : 'Noticia desmarcada',
                visto: $visto
            );

        } catch (\Throwable $e) {
            throw new \DomainException('Error al cambiar estado de visualización: ' . $e->getMessage());
        }
    }
}
