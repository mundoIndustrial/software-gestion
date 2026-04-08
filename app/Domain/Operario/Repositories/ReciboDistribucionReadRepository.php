<?php

namespace App\Domain\Operario\Repositories;

use App\Models\ConsecutivoReciboPedido;
use App\Models\ProcesoPrenda;
use Illuminate\Support\Collection;

interface ReciboDistribucionReadRepository
{
    public function findReciboById(int $idRecibo): ?ConsecutivoReciboPedido;

    /**
     * @return Collection<int, \App\Models\ReciboPorPartes>
     */
    public function findParcialesConTallasParaRecibo(
        int $pedidoProduccionId,
        int $prendaId,
        string $tipoRecibo,
        $consecutivoOriginal
    ): Collection;

    public function findNumeroPedidoByPedidoProduccionId(int $pedidoProduccionId): ?int;

    public function findProcesoParcial(int $numeroPedido, int $prendaId, $consecutivoParcial): ?ProcesoPrenda;

    public function estaCompletadoParcialEnCostura(int $parcialId): bool;
}

