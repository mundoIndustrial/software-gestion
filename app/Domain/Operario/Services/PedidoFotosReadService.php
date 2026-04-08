<?php

namespace App\Domain\Operario\Services;

interface PedidoFotosReadService
{
    /**
     * @return array<int, string>
     */
    public function obtenerFotosPedido(int $numeroPedido): array;
}

