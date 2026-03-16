<?php

namespace App\Domain\Pedidos\Contracts;

/**
 * ImagenesEppService
 * 
 * Contrato para obtener y gestionar imágenes de EPPs
 */
interface ImagenesEppService
{
    /**
     * Obtener imágenes de un EPP del pedido
     * 
     * @param int $pedidoEppId
     * @return array
     */
    public function obtenerImagenesEpp(int $pedidoEppId): array;
}
