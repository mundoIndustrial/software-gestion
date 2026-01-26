<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\UseCases\Base\AbstractEstadoTransicionUseCase;
use App\Domain\Pedidos\Repositories\PedidoRepository;

/**
 * Use Case: Anular Producción Pedido
 * 
 * REFACTORIZADO: Utiliza AbstractEstadoTransicionUseCase para eliminar duplicación
 * 
 * Antes: 45 lÃ­neas
 * DespuÃ©s: 10 lÃ­neas
 * Reducción: 78%
 */
class AnularProduccionPedidoUseCase extends AbstractEstadoTransicionUseCase
{
    private string $razon;

    public function __construct(
        PedidoRepository $pedidoRepository,
        string $razon = 'Sin especificar'
    ) {
        parent::__construct($pedidoRepository);
        $this->razon = $razon;
    }

    protected function aplicarTransicion($pedido): void
    {
        $pedido->anular($this->razon);
    }

    protected function obtenerMensaje(): string
    {
        return 'Producción del pedido anulada exitosamente';
    }
}

