<?php

namespace App\Domain\Ordenes\Services;

use App\Domain\Ordenes\Repositories\OrdenRepositoryInterface;
use App\Domain\Ordenes\ValueObjects\NumeroOrden;
use App\Domain\Ordenes\ValueObjects\EstadoOrden;

/**
 * Application Service: ActualizarEstadoOrdenService
 * 
 * Orquesta el cambio de estado de una orden.
 */
class ActualizarEstadoOrdenService
{
    public function __construct(
        private OrdenRepositoryInterface $ordenRepository
    ) {}

    /**
     * Cambiar estado de la orden
     * 
     * @param int $numeroPedido
     * @param string $nuevoEstado Ej: 'Aprobada', 'EnProduccion', 'Completada'
     * @return void
     * @throws \DomainException Si la transición no es permitida
     */
    public function ejecutar(int $numeroPedido, string $nuevoEstado): void
    {
        // Obtener orden
        $numeroOrden = NumeroOrden::desde($numeroPedido);
        $orden = $this->ordenRepository->findByNumero($numeroOrden);

        if (!$orden) {
            throw new \DomainException("Orden {$numeroPedido} no encontrada");
        }

        // Crear Value Object de estado
        $estado = EstadoOrden::desde($nuevoEstado);

        // Cambiar estado (lanza excepción si no es permitido)
        $orden->cambiarEstado($estado);

        // Persistir cambios
        $this->ordenRepository->save($orden);
    }

    /**
     * Aprobar orden
     */
    public function aprobar(int $numeroPedido): void
    {
        $numeroOrden = NumeroOrden::desde($numeroPedido);
        $orden = $this->ordenRepository->findByNumero($numeroOrden);

        if (!$orden) {
            throw new \DomainException("Orden {$numeroPedido} no encontrada");
        }

        $orden->aprobar();
        $this->ordenRepository->save($orden);
    }

    /**
     * Iniciar producción
     */
    public function iniciarProduccion(int $numeroPedido): void
    {
        $numeroOrden = NumeroOrden::desde($numeroPedido);
        $orden = $this->ordenRepository->findByNumero($numeroOrden);

        if (!$orden) {
            throw new \DomainException("Orden {$numeroPedido} no encontrada");
        }

        $orden->iniciarProduccion();
        $this->ordenRepository->save($orden);
    }

    /**
     * Completar orden
     */
    public function completar(int $numeroPedido): void
    {
        $numeroOrden = NumeroOrden::desde($numeroPedido);
        $orden = $this->ordenRepository->findByNumero($numeroOrden);

        if (!$orden) {
            throw new \DomainException("Orden {$numeroPedido} no encontrada");
        }

        $orden->completar();
        $this->ordenRepository->save($orden);
    }
}
