<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\Traits\ManejaPedidosUseCase;
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
    use ManejaPedidosUseCase;

    /**
     * Ejecutar caso de uso
     */
    public function ejecutar(array $datos): array
    {
        $this->validarNoVacio($datos, 'Datos JSON del pedido');
        return PedidoJSONValidator::validar($datos);
    }
}
