<?php

namespace App\Domain\Bodega\Services;

use App\Domain\Bodega\ValueObjects\NumeroPedidoBodega;
use App\Domain\Bodega\Repositories\OrdenBodegaRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * Application Service: Obtener Órdenes de Bodega
 */
final class ObtenerOrdenBodegaService
{
    public function __construct(
        private OrdenBodegaRepositoryInterface $repository
    ) {}

    public function obtenerTodas(): Collection
    {
        return $this->repository->obtenerTodas();
    }

    public function obtenerPorNumero(int $numero): ?array
    {
        $orden = $this->repository->obtener(NumeroPedidoBodega::desde($numero));
        return $orden ? $orden->toArray() : null;
    }

    public function obtenerPorCliente(string $cliente): Collection
    {
        return $this->repository->obtenerPorCliente($cliente);
    }

    public function obtenerPorEstado(string $estado): Collection
    {
        return $this->repository->obtenerPorEstado($estado);
    }

    public function obtenerProximoNumero(): int
    {
        return $this->repository->obtenerProximoNumero();
    }
}
