<?php

namespace App\Domain\PedidosLogo\Repositories;

use Illuminate\Support\Collection;

interface DisenoLogoPedidoRepositoryInterface
{
    public function listarPorProceso(int $procesoPrendaDetalleId): Collection;

    public function contarPorProceso(int $procesoPrendaDetalleId): int;

    public function crear(int $procesoPrendaDetalleId, string $url): array;

    public function findById(int $id): ?object;

    public function eliminarPorId(int $id): void;
}
