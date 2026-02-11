<?php

namespace App\Domain\PedidosLogo\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ProcesoPrendaDetalleReadRepositoryInterface
{
    public function paginarRecibosAprobados(array $tipoProcesoIds, ?string $search, bool $soloMinimalRole, ?string $areaFija, int $perPage = 20): LengthAwarePaginator;

    public function obtenerPedidoProduccionIdPorProceso(int $procesoPrendaDetalleId): ?int;

    public function obtenerPrendaPedidoIdPorProceso(int $procesoPrendaDetalleId): ?int;

    public function obtenerTipoProcesoIdPorProceso(int $procesoPrendaDetalleId): ?int;
}
