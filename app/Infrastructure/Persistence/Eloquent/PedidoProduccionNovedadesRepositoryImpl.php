<?php

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\Operario\Repositories\PedidoProduccionNovedadesRepository;
use App\Models\PedidoProduccion;

class PedidoProduccionNovedadesRepositoryImpl implements PedidoProduccionNovedadesRepository
{
    public function appendNovedadesPorNumeroPedido(int $numeroPedido, string $novedadFormato): void
    {
        $pedido = PedidoProduccion::query()
            ->where('numero_pedido', (int) $numeroPedido)
            ->first();

        if (!$pedido) {
            return;
        }

        $novedadesActuales = (string) ($pedido->novedades ?? '');
        $novedadesActualizadas = $novedadesActuales . ($novedadesActuales ? "\n\n" : '') . $novedadFormato;
        $pedido->update(['novedades' => $novedadesActualizadas]);
    }
}

