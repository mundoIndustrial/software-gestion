<?php

namespace App\Application\Pedidos\UseCases;

use App\Domain\PedidoProduccion\Validators\PedidoJSONValidator;

/**
 * Use Case: Validar Pedido desde JSON
 * 
 * Responsabilidad:
 * - Validar estructura del JSON del pedido
 * - Retornar errores si hay
 */
class ValidarPedidoDesdeJSONUseCase
{
    /**
     * Ejecutar caso de uso
     */
    public function ejecutar(array $datos): array
    {
        return PedidoJSONValidator::validar($datos);
    }
}
