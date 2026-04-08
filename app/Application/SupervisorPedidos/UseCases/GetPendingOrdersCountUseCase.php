<?php

namespace App\Application\SupervisorPedidos\UseCases;

use App\Application\SupervisorPedidos\DTOs\GetPendingOrdersCountRequest;
use App\Application\SupervisorPedidos\DTOs\GetPendingOrdersCountResponse;
use App\Application\SupervisorPedidos\Services\PedidoProduccionReadService;
use Illuminate\Support\Facades\Log;

class GetPendingOrdersCountUseCase
{
    public function __construct(
        private readonly PedidoProduccionReadService $readService
    ) {}

    public function execute(GetPendingOrdersCountRequest $request): GetPendingOrdersCountResponse
    {
        try {
            $counts = $this->readService->getPendingOrdersCount();
            $totalPendientes = $counts['total'];
            $pendientesLogo = $counts['logo'];

            Log::info('[GetPendingOrdersCountUseCase] Total pendientes: ' . $totalPendientes . ', Logo: ' . $pendientesLogo);

            return new GetPendingOrdersCountResponse($totalPendientes, $pendientesLogo);
        } catch (\Exception $e) {
            Log::error('[GetPendingOrdersCountUseCase] Error: ' . $e->getMessage());
            throw $e;
        }
    }
}
