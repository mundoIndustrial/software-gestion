<?php

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\Operario\Repositories\TablaOriginalBodegaNovedadesRepository;
use App\Models\TablaOriginalBodega;

class TablaOriginalBodegaNovedadesRepositoryImpl implements TablaOriginalBodegaNovedadesRepository
{
    public function appendNovedadesPorNumeroPedido(int $numeroPedido, string $novedadFormato): void
    {
        $bodega = TablaOriginalBodega::query()
            ->where('pedido', (int) $numeroPedido)
            ->first();

        if (!$bodega) {
            return;
        }

        $novedadesActuales = (string) ($bodega->novedades ?? '');
        $novedadesActualizadas = $novedadesActuales . ($novedadesActuales ? "\n\n" : '') . $novedadFormato;
        $bodega->update(['novedades' => $novedadesActualizadas]);
    }
}

