<?php

namespace App\Domain\Pedidos\Repositories;

interface ProcesoPedidoReadRepository
{
    /**
     * Obtiene procesos de un pedido (opcionalmente filtrados por prenda),
     * ordenados por fecha de inicio ascendente.
     *
     * @return array<int, object>
     */
    public function obtenerProcesosPorNumeroPedidoYPrenda(int|string $numeroPedido, ?int $prendaId = null): array;
}

