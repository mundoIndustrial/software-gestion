<?php

namespace App\Domain\Operario\Repositories;

interface PedidoProduccionNovedadesRepository
{
    public function appendNovedadesPorNumeroPedido(int $numeroPedido, string $novedadFormato): void;
}

