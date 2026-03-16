<?php

namespace App\Domain\Pedidos\Contracts;

use App\Models\PedidoProduccion;

/**
 * PedidoRepository
 * 
 * Contrato para acceso a datos de pedidos.
 * Implementa patrón Repository y abstrae Eloquent.
 */
interface PedidoRepository
{
    /**
     * Obtener pedido por ID
     */
    public function obtenerPorId(int $id): ?PedidoProduccion;

    /**
     * Obtener pedido por número
     */
    public function obtenerPorNumero(int $numero): ?PedidoProduccion;

    /**
     * Obtener pedido con todas sus relaciones
     */
    public function obtenerConRelaciones(int $id, array $relaciones = []): ?PedidoProduccion;

    /**
     * Listar pedidos por cliente
     */
    public function listarPorCliente(int $clienteId): array;
}
