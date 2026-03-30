<?php

namespace App\Domain\Pedidos\Services;

use App\Models\PrendaPedido;

interface PrendaTransformerServiceContract
{
    public function transformarPrendaParaFactura(PrendaPedido $prenda): array;

    public function call(string $method, array $arguments = []): mixed;
}
