<?php

namespace App\Application\SupervisorPedidos\UseCases;

use App\Application\SupervisorPedidos\DTOs\ListOrdersRequest;
use App\Application\SupervisorPedidos\DTOs\ListOrdersResponse;
use App\Application\SupervisorPedidos\Services\PedidoProduccionReadService;
use Illuminate\Support\Facades\Log;

class ListOrdersUseCase
{
    private PedidoProduccionReadService $readService;

    public function __construct(PedidoProduccionReadService $readService)
    {
        $this->readService = $readService;
    }

    public function execute(ListOrdersRequest $request): ListOrdersResponse
    {
        try {
            $ordenes = $this->readService->listOrders($request);

            $ordenes->getCollection()->each(function ($orden) {
                $orden->es_solo_epp = $this->readService->esSoloEpp($orden);
            });

            $estados = $this->readService->listDistinctStates();
            $pedidosSeleccionados = $this->readService->getSelectedOrders($request->getUserId());

            Log::info('[ListOrdersUseCase] Retrieved ' . $ordenes->count() . ' orders with ' . count($estados) . ' states');

            return new ListOrdersResponse($ordenes, $estados, $pedidosSeleccionados);
        } catch (\Exception $e) {
            Log::error('[ListOrdersUseCase] Error: ' . $e->getMessage());
            throw $e;
        }
    }
}
