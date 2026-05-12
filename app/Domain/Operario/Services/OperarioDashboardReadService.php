<?php

namespace App\Domain\Operario\Services;

use Illuminate\Support\Collection;

interface OperarioDashboardReadService
{
    public function obtenerUsuariosSobremedidaNormalizados(): Collection;

    public function obtenerCompletadosPorArea(array $idsRecibo, string $area): Collection;
    public function obtenerCompletadosParcialesPorArea(array $idsParcial, string $area): Collection;

    public function obtenerRecibosCompletadosPorOperario(string $nombreOperario): Collection;
}

