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
            $totalPendientes = (int) ($counts['total'] ?? 0);
            $pendientesLogo = (int) ($counts['logo'] ?? 0);
            $pendientesCarteraNoAprobado = (int) ($counts['cartera_no_aprobado'] ?? 0);

            Log::info('[GetPendingOrdersCountUseCase] Total pendientes: ' . $totalPendientes . ', Logo: ' . $pendientesLogo . ', Cartera no aprobado: ' . $pendientesCarteraNoAprobado);

            return new GetPendingOrdersCountResponse($totalPendientes, $pendientesLogo, $pendientesCarteraNoAprobado);
        } catch (\Exception $e) {
            Log::error('[GetPendingOrdersCountUseCase] Error: ' . $e->getMessage());
            throw $e;
        }
    }
}
