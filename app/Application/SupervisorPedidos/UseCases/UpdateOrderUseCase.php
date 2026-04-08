<?php

namespace App\Application\SupervisorPedidos\UseCases;

use App\Application\SupervisorPedidos\DTOs\UpdateOrderRequest;
use App\Application\SupervisorPedidos\DTOs\UpdateOrderResponse;
use App\Application\SupervisorPedidos\Services\UpdateOrderWriteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UpdateOrderUseCase
{
    public function __construct(
        private readonly UpdateOrderWriteService $writeService
    ) {}

    public function execute(UpdateOrderRequest $dtoRequest, Request $request): UpdateOrderResponse
    {
        try {
            $ordenActualizada = $this->writeService->update($dtoRequest, $request);
            return new UpdateOrderResponse(true, 'Pedido actualizado correctamente', $ordenActualizada);
        } catch (\Exception $e) {
            Log::error('Error al actualizar pedido', [
                'error' => $e->getMessage(),
                'orden_id' => $dtoRequest->getOrderId(),
            ]);

            return new UpdateOrderResponse(false, 'Error al actualizar el pedido: ' . $e->getMessage());
        }
    }
}
