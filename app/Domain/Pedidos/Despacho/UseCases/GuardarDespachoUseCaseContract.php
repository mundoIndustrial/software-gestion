<?php

namespace App\Domain\Pedidos\Despacho\UseCases;

use App\Application\Pedidos\Despacho\DTOs\ControlEntregasDTO;

interface GuardarDespachoUseCaseContract
{
    public function ejecutar(ControlEntregasDTO $control): array;
}

