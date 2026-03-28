<?php

namespace App\Domain\Operario\Services;

use App\Models\PedidoProduccion;
use App\Models\Prenda;
use App\Models\User;
use Illuminate\Support\Collection;

interface ControlCalidadWorkflow
{
    public function findPedidoOrFail(int $pedidoId): PedidoProduccion;

    public function runInTransaction(callable $callback): mixed;

    public function findPrendaById(?int $prendaId): ?Prenda;

    public function resolvePrendaNombre(?int $prendaId): string;

    public function findUserByNormalizedName(string $name): ?User;

    public function findUsersWithAnyRole(array $roles): Collection;
}
