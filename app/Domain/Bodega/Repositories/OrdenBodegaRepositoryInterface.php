<?php

namespace App\Domain\Bodega\Repositories;

use App\Domain\Bodega\Entities\OrdenBodega;
use App\Domain\Bodega\ValueObjects\NumeroPedidoBodega;
use Illuminate\Support\Collection;

interface OrdenBodegaRepositoryInterface
{
    public function obtener(NumeroPedidoBodega $numeroPedido): ?OrdenBodega;

    public function obtenerTodas(): Collection;

    public function obtenerPorCliente(string $cliente): Collection;

    public function obtenerPorEstado(string $estado): Collection;

    public function guardar(OrdenBodega $orden): void;

    public function actualizar(OrdenBodega $orden): void;

    public function eliminar(NumeroPedidoBodega $numeroPedido): void;

    public function obtenerProximoNumero(): int;

    public function existeNumero(int $numero): bool;
}
