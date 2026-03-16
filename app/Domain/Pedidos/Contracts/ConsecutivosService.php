<?php

namespace App\Domain\Pedidos\Contracts;

/**
 * ConsecutivosService
 * 
 * Contrato para obtener y gestionar consecutivos de recibos
 */
interface ConsecutivosService
{
    /**
     * Obtener consecutivos de una prenda específica
     * 
     * @param int $pedidoId
     * @param int $prendaId
     * @return array|null
     */
    public function obtenerConsecutivosPrenda(int $pedidoId, int $prendaId): ?array;

    /**
     * Obtener último recibo COSTURA de una prenda
     */
    public function obtenerUltimoReciboCostura(int $pedidoId, int $prendaId): ?array;
}
