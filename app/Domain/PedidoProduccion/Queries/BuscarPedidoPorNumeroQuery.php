<?php

namespace App\Domain\PedidoProduccion\Queries;

use App\Domain\Shared\CQRS\Query;

/**
 * BuscarPedidoPorNumeroQuery
 * 
 * Query para buscar un pedido por su número único
 * 
 * @param string $numeroPedido Número del pedido a buscar
 */
class BuscarPedidoPorNumeroQuery implements Query
{
    public function __construct(
        private string $numeroPedido,
    ) {}

    public function getNumeroPedido(): string
    {
        return $this->numeroPedido;
    }
}
