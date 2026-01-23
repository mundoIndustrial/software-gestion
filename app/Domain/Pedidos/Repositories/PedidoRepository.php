<?php

namespace App\Domain\Pedidos\Repositories;

use App\Domain\Pedidos\Agregado\PedidoAggregate;
use App\Domain\Pedidos\ValueObjects\NumeroPedido;

/**
 * Repository Interface para Pedidos
 * 
 * Define el contrato para persistencia de PedidoAggregate
 * La implementaciÃ³n estÃ¡ en Infrastructure
 */
interface PedidoRepository
{
    public function guardar(PedidoAggregate $pedido): void;
    public function porId(int $id): ?PedidoAggregate;
    public function porNumero(NumeroPedido $numero): ?PedidoAggregate;
    public function porClienteId(int $clienteId): array;
    public function eliminar(int $id): void;
    public function porEstado(string $estado): array;
}

