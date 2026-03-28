<?php

namespace App\Application\SupervisorPedidos\UseCases;

use App\Application\SupervisorPedidos\DTOs\ToggleOrderVisibilityRequest;
use App\Application\SupervisorPedidos\DTOs\ToggleOrderVisibilityResponse;
use App\Application\SupervisorPedidos\Services\PedidoProduccionReadService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ToggleOrderVisibilityUseCase
{
    public function __construct(
        private readonly PedidoProduccionReadService $readService
    ) {}

    public function execute(ToggleOrderVisibilityRequest $request): ToggleOrderVisibilityResponse
    {
        try {
            $usuario = Auth::user();
            $isHiding = $request->isHidden();

            $this->readService->setOrderVisibility(
                $request->getOrderId(),
                $isHiding,
                $usuario->id ?? null
            );

            $message = $isHiding
                ? 'Pedido ocultado correctamente'
                : 'Pedido mostrado correctamente';

            return new ToggleOrderVisibilityResponse(true, $message);

        } catch (\Exception $e) {
            Log::error('[ToggleOrderVisibilityUseCase] Error: ' . $e->getMessage(), [
                'orden_id' => $request->getOrderId(),
                'is_hiding' => $request->isHidden(),
            ]);

            return new ToggleOrderVisibilityResponse(false, 'Error al cambiar visibilidad del pedido: ' . $e->getMessage());
        }
    }
}
