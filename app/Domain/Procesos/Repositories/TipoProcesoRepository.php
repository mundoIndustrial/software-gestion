<?php

namespace App\Domain\Procesos\Repositories;

use App\Domain\Procesos\Entities\TipoProceso;

/**
 * Repository Interface: TipoProcesoRepository
 */
interface TipoProcesoRepository
{
    public function obtenerPorId(int $id): ?TipoProceso;

    public function obtenerPorSlug(string $slug): ?TipoProceso;

    public function obtenerTodos(): array;

    public function obtenerActivos(): array;

    public function guardar(TipoProceso $tipoProceso): TipoProceso;

    public function actualizar(TipoProceso $tipoProceso): TipoProceso;

    public function eliminar(int $id): bool;
}
