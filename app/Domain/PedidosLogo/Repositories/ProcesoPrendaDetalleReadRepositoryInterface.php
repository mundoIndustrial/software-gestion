<?php

namespace App\Domain\PedidosLogo\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ProcesoPrendaDetalleReadRepositoryInterface
{
    public function paginarRecibosAprobados(array $tipoProcesoIds, ?string $search, bool $soloMinimalRole, ?string $areaFija, int $perPage = 20, ?array $columnFilters = null, bool $incluirEntregados = false): LengthAwarePaginator;

    public function obtenerPedidoProduccionIdPorProceso(int $procesoPrendaDetalleId): ?int;

    public function obtenerPrendaPedidoIdPorProceso(int $procesoPrendaDetalleId): ?int;

    public function obtenerTipoProcesoIdPorProceso(int $procesoPrendaDetalleId): ?int;

    public function obtenerAreasUnicas(array $tipoProcesoIds): array;

    public function obtenerAsesorasUnicas(array $tipoProcesoIds): array;

    public function buscarValoresColumna(string $columna, string $busqueda, array $tipoProcesoIds): array;
}
