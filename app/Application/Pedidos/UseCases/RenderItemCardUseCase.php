<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\RenderItemCardDTO;
use Illuminate\Support\Facades\Log;

final class RenderItemCardUseCase
{
    public function ejecutar(RenderItemCardDTO $dto): string
    {
        Log::info('[RenderItemCardUseCase] Renderizando componente item-card', [
            'index' => $dto->index,
        ]);

        try {
            $html = view('asesores.pedidos.components.item-card', [
                'item' => $dto->item,
                'index' => $dto->index,
            ])->render();

            Log::info('[RenderItemCardUseCase] Componente renderizado exitosamente');

            return $html;
        } catch (\Exception $e) {
            Log::error('[RenderItemCardUseCase] Error renderizando componente', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
