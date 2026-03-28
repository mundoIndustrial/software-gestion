<?php

namespace App\Domain\Operario\Services;

use App\Application\Operario\DTOs\ObtenerPedidosOperarioDTO;
use App\Models\User;

interface OperarioPedidosReadService
{
    public function obtenerPedidosDelOperario(User $usuario): ObtenerPedidosOperarioDTO;
}

