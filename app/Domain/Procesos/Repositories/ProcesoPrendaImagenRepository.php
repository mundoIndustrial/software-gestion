<?php

namespace App\Domain\Procesos\Repositories;

use App\Domain\Procesos\Entities\ProcesoPrendaImagen;

/**
 * Repository Interface: ProcesoPrendaImagenRepository
 */
interface ProcesoPrendaImagenRepository
{
    public function obtenerPorId(int $id): ?ProcesoPrendaImagen;

    public function obtenerPorProceso(int $procesoPrendaDetalleId): array;

    public function obtenerPrincipal(int $procesoPrendaDetalleId): ?ProcesoPrendaImagen;

    public function obtenerPorHash(string $hashMd5): ?ProcesoPrendaImagen;

    public function guardar(ProcesoPrendaImagen $imagen): ProcesoPrendaImagen;

    public function actualizar(ProcesoPrendaImagen $imagen): ProcesoPrendaImagen;

    public function eliminar(int $id): bool;

    public function obtenerProximoOrden(int $procesoPrendaDetalleId): int;

    public function marcarOtraComoPrincipal(int $procesoPrendaDetalleId, ?int $imagenIdAMarcar = null): void;
}
