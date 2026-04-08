<?php

namespace App\Application\SupervisorPedidos\UseCases;

use App\Application\SupervisorPedidos\DTOs\GetFilterOptionsRequest;
use App\Application\SupervisorPedidos\DTOs\GetFilterOptionsResponse;
use App\Application\SupervisorPedidos\Services\PedidoProduccionReadService;
use Illuminate\Support\Facades\Log;

class GetFilterOptionsUseCase
{
    public function __construct(
        private readonly PedidoProduccionReadService $readService
    ) {}

    public function execute(GetFilterOptionsRequest $request): GetFilterOptionsResponse
    {
        try {
            $field = $request->getField();
            $opciones = $this->readService->getOrderFilterOptions($field);

            Log::info('Opciones de filtro obtenidas', [
                'field' => $field,
                'count' => count($opciones),
            ]);

            return new GetFilterOptionsResponse($opciones);
        } catch (\Exception $e) {
            Log::error('Error en GetFilterOptions: ' . $e->getMessage());
            throw $e;
        }
    }
}
