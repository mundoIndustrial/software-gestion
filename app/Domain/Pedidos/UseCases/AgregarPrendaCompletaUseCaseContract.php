<?php

namespace App\Domain\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\AgregarPrendaCompletaDTO;
use App\Models\PrendaPedido;

interface AgregarPrendaCompletaUseCaseContract
{
    public function execute(AgregarPrendaCompletaDTO $dto): PrendaPedido;
}
