<?php

namespace App\Domain\Operario\Services;

use App\Models\ConsecutivoReciboPedido;
use App\Models\ProcesoPrenda;
use App\Models\ReciboPorPartes;
use Illuminate\Support\Collection;

interface ReciboOperarioWorkflow
{
    public function findParcialById(int $id, bool $withRelations = false): ?ReciboPorPartes;

    public function upsertCompletado(?int $idRecibo, ?int $idParcial, string $area, string $numeroRecibo, string $nombreOperario): void;

    public function deleteCompletadoByReciboAndArea(int $idRecibo, string $area): void;

    public function deleteCompletadoByParcialAndArea(int $idParcial, string $area): void;

    public function runInTransaction(callable $callback): mixed;

    public function findNumeroPedidoByPrendaId(int $prendaId): ?int;

    public function findProcesoCosturaParcial(ReciboPorPartes $parcial): ?ProcesoPrenda;

    public function findParcialIdsForOriginal(ReciboPorPartes $parcial): array;

    public function countCompletadosParcialesByArea(array $parcialIds, string $area): int;

    public function findReciboOriginalActivoDesdeParcial(ReciboPorPartes $parcial): ?ConsecutivoReciboPedido;

    public function findVistaCosturaUsers(): Collection;
}

