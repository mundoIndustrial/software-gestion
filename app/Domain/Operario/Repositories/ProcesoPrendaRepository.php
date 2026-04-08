<?php

namespace App\Domain\Operario\Repositories;

use App\Models\ProcesoPrenda;

interface ProcesoPrendaRepository
{
    public function findByNumeroPedidoProcesoEncargado(int $numeroPedido, string $proceso, string $encargado): ?ProcesoPrenda;

    public function findLatestByPrendaAndProceso(int $prendaId, string $proceso): ?ProcesoPrenda;

    public function findLatestByProceso(int $numeroPedido, int $prendaId, string $proceso): ?ProcesoPrenda;

    public function findLatestByProcesoAndNumeroRecibo(int $numeroPedido, int $prendaId, string $proceso, int $numeroRecibo): ?ProcesoPrenda;

    public function findLatestNotProcesoByNumeroRecibo(int $numeroPedido, int $prendaId, string $procesoExcluido, int $numeroRecibo): ?ProcesoPrenda;

    /**
     * Obtiene todos los procesos de un tipo específico
     * @return \Illuminate\Database\Eloquent\Collection<int, ProcesoPrenda>
     */
    public function findByProceso(string $proceso): \Illuminate\Database\Eloquent\Collection;

    public function create(array $attributes): ProcesoPrenda;

    public function update(ProcesoPrenda $proceso, array $attributes): void;

    public function forceDelete(ProcesoPrenda $proceso): void;
}
