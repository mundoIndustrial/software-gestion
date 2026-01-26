<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\UseCases\Base\AbstractEstadoTransicionUseCase;

/**
 * Use Case: Iniciar Producción Pedido
 * 
 * REFACTORIZADO: Utiliza AbstractEstadoTransicionUseCase para eliminar duplicación
 * 
 * Antes: 28 lÃ­neas
 * DespuÃ©s: 8 lÃ­neas
 * Reducción: 71%
 */
class IniciarProduccionPedidoUseCase extends AbstractEstadoTransicionUseCase
{
    protected function aplicarTransicion($pedido): void
    {
        $pedido->iniciarProduccion();
    }

    protected function obtenerMensaje(): string
    {
        return 'Producción iniciada exitosamente';
    }
}

