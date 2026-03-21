<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\UseCases\Base\AbstractEstadoTransicionUseCase;

/**
 * Use Case: Iniciar Producción Pedido
 * 
 * REFACTORIZADO: Utiliza AbstractEstadoTransicionUseCase para eliminar duplicación
 * 
 * Antes: 28 lineas
 * despues: 8 lineas
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

