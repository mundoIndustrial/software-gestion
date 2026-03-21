<?php

namespace App\Domain\Pedidos\Repositories;

use App\Domain\Pedidos\Agregado\PedidoAggregate;
use App\Domain\Pedidos\ValueObjects\NumeroPedido;

/**
 * Repository Interface para Pedidos
 * 
 * Define el contrato para persistencia de PedidoAggregate
 * La implementación está en Infrastructure
 */
interface PedidoRepository
{
    public function guardar(PedidoAggregate $pedido): void;
    public function porId(int $id): ?PedidoAggregate;
    public function porNumero(NumeroPedido $numero): ?PedidoAggregate;
    public function porClienteId(int $clienteId): array;
    public function eliminar(int $id): void;
    public function porEstado(string $estado): array;

    /**
     * Calcular cantidad total de prendas en un pedido
     * 
     * @param int $pedidoId
     * @return int
     */
    public function calcularCantidadTotalPrendas(int $pedidoId): int;

    /**
     * Calcular cantidad total de EPPs en un pedido
     * 
     * @param int $pedidoId
     * @return int
     */
    public function calcularCantidadTotalEpps(int $pedidoId): int;

    /**
     * Crear notificación de pedido creado
     * 
     * @param object $pedido
     * @param object $cliente
     * @param int $usuarioId
     * @param int $cantidadPrendas
     * @param int $cantidadEpps
     * @return void
     */
    public function crearNotificacionPedido($pedido, $cliente, int $usuarioId, int $cantidadPrendas, int $cantidadEpps): void;
}
