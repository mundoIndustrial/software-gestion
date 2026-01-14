<?php

namespace App\Domain\Procesos\Repositories;

use App\Domain\Procesos\Entities\ProcesoPrendaDetalle;

/**
 * Repository Interface: ProcesoPrendaDetalleRepository
 */
interface ProcesoPrendaDetalleRepository
{
    public function obtenerPorId(int $id): ?ProcesoPrendaDetalle;

    public function obtenerPorPrenda(int $prendaId): array;

    public function obtenerPorPedido(int $numeroPedido): array;

    public function obtenerPorPrendaYTipo(int $prendaId, int $tipoProcesoId): ?ProcesoPrendaDetalle;

    public function guardar(ProcesoPrendaDetalle $proceso): ProcesoPrendaDetalle;

    public function actualizar(ProcesoPrendaDetalle $proceso): ProcesoPrendaDetalle;

    public function eliminar(int $id): bool;

    public function obtenerPendientes(): array;

    public function obtenerAprobados(): array;

    public function obtenerCompletados(): array;
}
