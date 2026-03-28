<?php

namespace App\Infrastructure\Services\Operario;

use App\Domain\Operario\Services\OperarioDashboardReadService;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OperarioDashboardReadServiceImpl implements OperarioDashboardReadService
{
    public function obtenerUsuariosSobremedidaNormalizados(): Collection
    {
        $rolSobremedidaId = Role::where('name', 'confeccion-sobremedida')->value('id');
        if (empty($rolSobremedidaId)) {
            return collect();
        }

        return User::query()
            ->where(function ($q) use ($rolSobremedidaId) {
                $q->whereJsonContains('roles_ids', (int) $rolSobremedidaId)
                    ->orWhere('role_id', (int) $rolSobremedidaId);
            })
            ->pluck('name')
            ->map(fn ($n) => strtolower(trim((string) $n)))
            ->filter()
            ->unique()
            ->values();
    }

    public function obtenerCompletadosPorArea(array $idsRecibo, string $area): Collection
    {
        if (empty($idsRecibo)) {
            return collect();
        }

        return DB::table('prenda_recibo_completado')
            ->where('area', $area)
            ->whereIn('id_recibo', $idsRecibo)
            ->pluck('fecha_completado', 'id_recibo');
    }
}

