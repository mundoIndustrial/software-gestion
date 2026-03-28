<?php

namespace App\Domain\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\ActualizarPrendaCompletaDTO;
use App\Models\PrendaPedido;

interface ActualizarPrendaCompletaUseCaseContract
{
    public function ejecutar(ActualizarPrendaCompletaDTO $dto): PrendaPedido;

    public function transformarPrendaParaFactura(PrendaPedido $prenda): array;
}

