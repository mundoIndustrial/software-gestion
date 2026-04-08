<?php

namespace App\Domain\BodegaNota\Repositories;

interface BodegaNotaRepositoryInterface
{
    /**
     * Obtener todas las notas de un pedido por número de pedido
     */
    public function obtenerNotasPorNumeroPedido(string $numeroPedido);
}
