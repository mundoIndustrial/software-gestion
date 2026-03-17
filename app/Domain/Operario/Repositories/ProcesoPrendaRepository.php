<?php

namespace App\Domain\Operario\Repositories;

use App\Models\ProcesoPrenda;

interface ProcesoPrendaRepository
{
    public function findLatestByPrendaAndProceso(int $prendaId, string $proceso): ?ProcesoPrenda;

    public function findLatestByProceso(int $numeroPedido, int $prendaId, string $proceso): ?ProcesoPrenda;

    public function findLatestByProcesoAndNumeroRecibo(int $numeroPedido, int $prendaId, string $proceso, int $numeroRecibo): ?ProcesoPrenda;

    public function findLatestNotProcesoByNumeroRecibo(int $numeroPedido, int $prendaId, string $procesoExcluido, int $numeroRecibo): ?ProcesoPrenda;

    public function create(array $attributes): ProcesoPrenda;

    public function update(ProcesoPrenda $proceso, array $attributes): void;

    public function forceDelete(ProcesoPrenda $proceso): void;
}
