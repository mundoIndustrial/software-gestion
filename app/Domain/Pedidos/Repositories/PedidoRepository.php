<?php

namespace App\Domain\Pedidos\Repositories;

use App\Domain\Pedidos\Agregado\PedidoAggregate;
use App\Domain\Pedidos\ValueObjects\NumeroPedido;

/**
 * Contrato de persistencia para el agregado de pedidos.
 */
interface PedidoRepository
{
    public function guardar(PedidoAggregate $pedido): void;

    public function porId(int $id): ?PedidoAggregate;

    public function porNumero(NumeroPedido $numero): ?PedidoAggregate;

    public function porClienteId(int $clienteId): array;

    public function eliminar(int $id): void;

    public function porEstado(string $estado): array;

    public function calcularCantidadTotalPrendas(int $pedidoId): int;

    public function calcularCantidadTotalEpps(int $pedidoId): int;
}
