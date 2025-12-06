<?php

namespace App\Domain\Ordenes\Services;

use App\Domain\Ordenes\Repositories\OrdenRepositoryInterface;
use App\Domain\Ordenes\ValueObjects\NumeroOrden;
use App\Domain\Ordenes\Specifications\PuedeCancelarse;

/**
 * Application Service: CancelarOrdenService
 * 
 * Orquesta la cancelación de una orden.
 */
class CancelarOrdenService
{
    public function __construct(
        private OrdenRepositoryInterface $ordenRepository
    ) {}

    /**
     * Cancelar orden
     * 
     * @param int $numeroPedido
     * @return void
     * @throws \DomainException Si la orden no puede ser cancelada
     */
    public function ejecutar(int $numeroPedido): void
    {
        $numeroOrden = NumeroOrden::desde($numeroPedido);
        $orden = $this->ordenRepository->findByNumero($numeroOrden);

        if (!$orden) {
            throw new \DomainException("Orden {$numeroPedido} no encontrada");
        }

        // Verificar si puede cancelarse
        $specification = new PuedeCancelarse();
        if (!$specification->isSatisfiedBy($orden)) {
            throw new \DomainException(
                "La orden {$numeroPedido} no puede ser cancelada (estado: {$orden->getEstado()->toString()})"
            );
        }

        // Cancelar
        $orden->cancelar();

        // Persistir
        $this->ordenRepository->save($orden);
    }
}
