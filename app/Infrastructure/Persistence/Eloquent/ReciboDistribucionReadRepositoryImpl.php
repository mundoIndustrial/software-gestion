<?php

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\Operario\Repositories\ReciboDistribucionReadRepository;
use App\Models\ConsecutivoReciboPedido;
use App\Models\PedidoProduccion;
use App\Models\ProcesoPrenda;
use App\Models\ReciboPorPartes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReciboDistribucionReadRepositoryImpl implements ReciboDistribucionReadRepository
{
    public function findReciboById(int $idRecibo): ?ConsecutivoReciboPedido
    {
        return ConsecutivoReciboPedido::query()->find((int) $idRecibo);
    }

    public function findParcialesConTallasParaRecibo(
        int $pedidoProduccionId,
        int $prendaId,
        string $tipoRecibo,
        $consecutivoOriginal
    ): Collection {
        return ReciboPorPartes::query()
            ->where('pedido_produccion_id', (int) $pedidoProduccionId)
            ->where('prenda_pedido_id', (int) $prendaId)
            ->where('tipo_recibo', (string) $tipoRecibo)
            ->where('consecutivo_original', $consecutivoOriginal)
            ->with('tallas')
            ->get();
    }

    public function findNumeroPedidoByPedidoProduccionId(int $pedidoProduccionId): ?int
    {
        $pedido = PedidoProduccion::query()->find((int) $pedidoProduccionId);
        return $pedido ? (int) $pedido->numero_pedido : null;
    }

    public function findProcesoParcial(int $numeroPedido, int $prendaId, $consecutivoParcial): ?ProcesoPrenda
    {
        return ProcesoPrenda::query()
            ->where('numero_pedido', (int) $numeroPedido)
            ->where('prenda_pedido_id', (int) $prendaId)
            ->where('numero_recibo_parcial', $consecutivoParcial)
            ->latest('created_at')
            ->first();
    }

    public function estaCompletadoParcialEnCostura(int $parcialId): bool
    {
        return DB::table('prenda_recibo_completado')
            ->where('id_parcial', (int) $parcialId)
            ->where('area', 'Costura')
            ->exists();
    }
}

