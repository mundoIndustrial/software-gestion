<?php

namespace App\Infrastructure\Services\Operario;

use App\Domain\Operario\Services\ControlCalidadWorkflow;
use App\Models\PedidoProduccion;
use App\Models\Prenda;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ControlCalidadWorkflowService implements ControlCalidadWorkflow
{
    public function findPedidoOrFail(int $pedidoId): PedidoProduccion
    {
        return PedidoProduccion::findOrFail($pedidoId);
    }

    public function runInTransaction(callable $callback): mixed
    {
        return DB::transaction($callback);
    }

    public function findPrendaById(?int $prendaId): ?Prenda
    {
        if (!$prendaId) {
            return null;
        }

        return Prenda::find($prendaId);
    }

    public function resolvePrendaNombre(?int $prendaId): string
    {
        if (!$prendaId) {
            return 'Prenda desconocida';
        }

        return Prenda::find($prendaId)?->nombre ?? 'Prenda desconocida';
    }

    public function findUserByNormalizedName(string $name): ?User
    {
        $normalized = strtolower(trim($name));
        if ($normalized === '') {
            return null;
        }

        return User::query()
            ->whereRaw('LOWER(TRIM(name)) = ?', [$normalized])
            ->first();
    }

    public function findUsersWithAnyRole(array $roles): Collection
    {
        return User::all()->filter(function (User $user) use ($roles) {
            foreach ($roles as $role) {
                if ($user->hasRole($role)) {
                    return true;
                }
            }
            return false;
        })->values();
    }
}

