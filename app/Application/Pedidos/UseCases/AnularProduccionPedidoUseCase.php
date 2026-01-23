<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\UseCases\Base\AbstractEstadoTransicionUseCase;
use App\Domain\Pedidos\Repositories\PedidoRepository;

/**
 * Use Case: Anular ProducciÃ³n Pedido
 * 
 * REFACTORIZADO: Utiliza AbstractEstadoTransicionUseCase para eliminar duplicaciÃ³n
 * 
 * Antes: 45 lÃ­neas
 * DespuÃ©s: 10 lÃ­neas
 * ReducciÃ³n: 78%
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
        return 'ProducciÃ³n del pedido anulada exitosamente';
    }
}

