<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\UseCases\Base\AbstractEstadoTransicionUseCase;

/**
 * Use Case: Cancelar Pedido
 * 
 * REFACTORIZADO: Utiliza AbstractEstadoTransicionUseCase para eliminar duplicación
 * 
 * Antes: 28 lineas
 * despues: 8 lineas
 * Reducción: 71%
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

