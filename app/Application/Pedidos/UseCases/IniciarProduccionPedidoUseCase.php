<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\UseCases\Base\AbstractEstadoTransicionUseCase;

/**
 * Use Case: Iniciar ProducciÃ³n Pedido
 * 
 * REFACTORIZADO: Utiliza AbstractEstadoTransicionUseCase para eliminar duplicaciÃ³n
 * 
 * Antes: 28 lÃ­neas
 * DespuÃ©s: 8 lÃ­neas
 * ReducciÃ³n: 71%
 */
class IniciarProduccionPedidoUseCase extends AbstractEstadoTransicionUseCase
{
    protected function aplicarTransicion($pedido): void
    {
        $pedido->iniciarProduccion();
    }

    protected function obtenerMensaje(): string
    {
        return 'ProducciÃ³n iniciada exitosamente';
    }
}

