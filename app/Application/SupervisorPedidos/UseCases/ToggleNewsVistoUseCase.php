<?php

namespace App\Application\SupervisorPedidos\UseCases;

use App\Application\SupervisorPedidos\DTOs\ToggleNewsVistoRequest;
use App\Application\SupervisorPedidos\DTOs\ToggleNewsVistoResponse;
use App\Models\NewsVisto;

class ToggleNewsVistoUseCase
{
    public function execute(ToggleNewsVistoRequest $request): ToggleNewsVistoResponse
    {
        try {
            $existing = NewsVisto::where('news_id', $request->getNewsId())
                ->where('user_id', $request->getUserId())
                ->first();

            $visto = false;

            if ($existing) {
                $existing->delete();
            } else {
                NewsVisto::create([
                    'news_id' => $request->getNewsId(),
                    'user_id' => $request->getUserId()
                ]);
                $visto = true;
            }

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
