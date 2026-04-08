<?php

namespace App\Domain\Pedidos\Queries;

use App\Domain\Shared\CQRS\Query;

/**
 * BuscarPedidoPorNumeroQuery
 * 
 * Query para buscar un pedido por su numero unico
 * 
 * @param string $numeroPedido numero del pedido a buscar
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

