<?php

namespace App\Domain\Ordenes\Services;

use App\Domain\Ordenes\Repositories\OrdenRepositoryInterface;
use App\Domain\Ordenes\ValueObjects\NumeroOrden;
use App\Domain\Ordenes\Entities\Orden;
use Illuminate\Support\Collection;

/**
 * Application Service: ObtenerOrdenService
 * 
 * Orquesta la obtención de órdenes (lectura).
 */
class ObtenerOrdenService
{
    public function __construct(
        private OrdenRepositoryInterface $ordenRepository
    ) {}

    /**
     * Obtener orden por número
     */
    public function porNumero(int $numeroPedido): Orden
    {
        $numeroOrden = NumeroOrden::desde($numeroPedido);
        $orden = $this->ordenRepository->findByNumero($numeroOrden);

        if (!$orden) {
            throw new \DomainException("Orden {$numeroPedido} no encontrada");
        }

        return $orden;
    }

    /**
     * Obtener todas las órdenes
     */
    public function todas(): Collection
    {
        return $this->ordenRepository->findAll();
    }

    /**
     * Obtener órdenes por cliente
     */
    public function porCliente(string $cliente): Collection
    {
        return $this->ordenRepository->findByCliente($cliente);
    }

    /**
     * Obtener órdenes por estado
     */
    public function porEstado(string $estado): Collection
    {
        return $this->ordenRepository->findByEstado($estado);
    }

    /**
     * Obtener órdenes en producción
     */
    public function enProduccion(): Collection
    {
        return $this->ordenRepository->findByEstado('EnProduccion');
    }

    /**
     * Obtener órdenes completadas
     */
    public function completadas(): Collection
    {
        return $this->ordenRepository->findByEstado('Completada');
    }
}
