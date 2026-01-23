<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\UseCases\Base\AbstractEstadoTransicionUseCase;

/**
 * Use Case: Cancelar Pedido
 * 
 * REFACTORIZADO: Utiliza AbstractEstadoTransicionUseCase para eliminar duplicaciÃ³n
 * 
 * Antes: 28 lÃ­neas
 * DespuÃ©s: 8 lÃ­neas
 * ReducciÃ³n: 71%
 */
class CancelarPedidoUseCase extends AbstractEstadoTransicionUseCase
{
    protected function aplicarTransicion($pedido): void
    {
        $pedido->cancelar();
    }

    protected function obtenerMensaje(): string
    {
        return 'Pedido cancelado exitosamente';
    }
}

