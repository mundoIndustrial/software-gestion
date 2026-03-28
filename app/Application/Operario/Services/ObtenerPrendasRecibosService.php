<?php

namespace App\Application\Operario\Services;

use App\Domain\Operario\Services\OperarioPrendasRecibosReadService;
use App\Models\User;
use Illuminate\Support\Collection;

class ObtenerPrendasRecibosService
{
    public function __construct(private readonly OperarioPrendasRecibosReadService $service)
    {
    }

    public function obtenerPrendasConRecibos(User $usuario): Collection
    {
        return $this->service->obtenerPrendasConRecibos($usuario);
    }

    public function obtenerPrendasConRecibosTodosCostura(): Collection
    {
        return $this->service->obtenerPrendasConRecibosTodosCostura();
    }
}
