<?php

namespace App\Application\Operario\Services;

use App\Application\Operario\DTOs\ObtenerPedidosOperarioDTO;
use App\Domain\Operario\Services\OperarioPedidosReadService;
use App\Models\User;

class ObtenerPedidosOperarioService
{
    public function __construct(private readonly OperarioPedidosReadService $service)
    {
    }

    public function obtenerPedidosDelOperario(User $usuario): ObtenerPedidosOperarioDTO
    {
        return $this->service->obtenerPedidosDelOperario($usuario);
    }
}
