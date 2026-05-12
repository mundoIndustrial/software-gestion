<?php

namespace App\Domain\Operario\Services;

use App\Models\ConsecutivoReciboPedido;
use App\Models\ProcesoPrenda;
use App\Models\ReciboPorPartes;
use Illuminate\Support\Collection;

interface ReciboOperarioWorkflow
{
    /**
     * @return \App\Models\ReciboPorPartes|\App\Models\PedidoParcial|null
     */
    public function findParcialById(int $id, bool $withRelations = false): ?object;

    public function upsertCompletado(?int $idRecibo, ?int $idParcial, string $area, string $numeroRecibo, string $nombreOperario): void;

    public function deleteCompletadoByReciboAndArea(int $idRecibo, string $area): void;

    public function deleteCompletadoByParcialAndArea(int $idParcial, string $area): void;

    public function runInTransaction(callable $callback): mixed;

    public function findNumeroPedidoByPrendaId(int $prendaId): ?int;

    public function findProcesoCosturaParcial(object $parcial): ?ProcesoPrenda;

    public function findParcialIdsForOriginal(object $parcial): array;

    public function countCompletadosParcialesByArea(array $parcialIds, string $area): int;

    public function findReciboOriginalActivoDesdeParcial(object $parcial): ?ConsecutivoReciboPedido;

    public function findVistaCosturaUsers(): Collection;
}

