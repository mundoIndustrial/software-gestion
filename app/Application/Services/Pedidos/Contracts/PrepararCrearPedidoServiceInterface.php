<?php

namespace App\Application\Services\Pedidos\Contracts;

/**
 * PrepararCrearPedidoServiceInterface
 * 
 * Contrato para servicios que preparan datos para crear pedido
 * Permite implementar diferentes estrategias de preparación
 */
interface PrepararCrearPedidoServiceInterface
{
    /**
     * Preparar datos para creación de pedido
     * 
     * @param int|null $editId - ID del pedido a editar (si existe)
     * @return array [
     *   'modo_edicion' => bool,
     *   'pedido_editar' => ?PedidoProduccion,
     *   'pedido_editar_id' => ?int,
     *   'epps_editar' => array,
     * ]
     */
    public function ejecutar(?int $editId): array;
}
