<?php

namespace App\Domain\Pedidos\Queries;

use App\Domain\Shared\CQRS\Query;

/**
 * BuscarPedidoPorNumeroQuery
 * 
 * Query para buscar un pedido por su nÃºmero Ãºnico
 * 
 * @param string $numeroPedido NÃºmero del pedido a buscar
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

