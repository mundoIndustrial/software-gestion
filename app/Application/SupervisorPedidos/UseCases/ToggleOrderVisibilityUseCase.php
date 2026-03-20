<?php

namespace App\Application\SupervisorPedidos\UseCases;

use App\Application\SupervisorPedidos\DTOs\ToggleOrderVisibilityRequest;
use App\Application\SupervisorPedidos\DTOs\ToggleOrderVisibilityResponse;
use App\Models\PedidoProduccion;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ToggleOrderVisibilityUseCase
{
    public function execute(ToggleOrderVisibilityRequest $request): ToggleOrderVisibilityResponse
    {
        try {
            $orden = PedidoProduccion::findOrFail($request->getOrderId());
            $usuario = Auth::user();
            $isHiding = $request->isHidden();

            if ($isHiding) {
                $this->hideOrder($orden, $usuario);
                $message = 'Pedido ocultado correctamente';
            } else {
                $this->showOrder($orden, $usuario);
                $message = 'Pedido mostrado correctamente';
            }

            return new ToggleOrderVisibilityResponse(true, $message);

        } catch (\Exception $e) {
            Log::error('[ToggleOrderVisibilityUseCase] Error: ' . $e->getMessage(), [
                'orden_id' => $request->getOrderId(),
                'is_hiding' => $request->isHidden(),
            ]);

            return new ToggleOrderVisibilityResponse(false, 'Error al cambiar visibilidad del pedido: ' . $e->getMessage());
        }
    }

    private function hideOrder(PedidoProduccion $orden, $usuario): void
    {
        $orden->update([
            'ocultado_en' => now(),
            'usuario_ocultado_por' => $usuario->id ?? null,
        ]);

        Log::info("Pedido #{$orden->numero_pedido} ocultado en supervisor-pedidos por " . ($usuario->name ?? 'Sistema'), [
            'fecha' => now(),
            'usuario_id' => $usuario->id ?? null,
        ]);
    }

    private function showOrder(PedidoProduccion $orden, $usuario): void
    {
        $orden->update([
            'ocultado_en' => null,
            'usuario_ocultado_por' => null,
        ]);

        Log::info("Pedido #{$orden->numero_pedido} mostrado nuevamente en supervisor-pedidos por " . ($usuario->name ?? 'Sistema'), [
            'fecha' => now(),
            'usuario_id' => $usuario->id ?? null,
        ]);
    }
}
