<?php

namespace App\Domain\Operario\Services;

use Illuminate\Support\Collection;

interface OperarioDashboardReadService
{
    public function obtenerUsuariosSobremedidaNormalizados(): Collection;

    public function obtenerCompletadosPorArea(array $idsRecibo, string $area): Collection;
}

