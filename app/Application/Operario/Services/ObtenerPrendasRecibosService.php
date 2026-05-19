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

    public function obtenerPrendasConRecibosTodosCostura(): \Illuminate\Support\Collection
    {
        return $this->service->obtenerPrendasConRecibosTodosCostura();
    }

    public function obtenerPrendasConRecibos(\App\Models\User $usuario, ?string $filtroRecibo = null): \Illuminate\Support\Collection
    {
        return $this->service->obtenerPrendasConRecibos($usuario, $filtroRecibo);
    }

    public function obtenerPrendasConRecibosBodegaCortador(\App\Models\User $usuario): \Illuminate\Support\Collection
    {
        return $this->service->obtenerPrendasConRecibosBodegaCortador($usuario);
    }

    public function obtenerConteoRecibosBodegaCortador(\App\Models\User $usuario): int
    {
        return $this->service->obtenerConteoPrendasConRecibosBodegaCortador($usuario);
    }

    public function obtenerPrendasConRecibosBodegaVistaCostura(): \Illuminate\Support\Collection
    {
        return $this->service->obtenerPrendasConRecibosBodegaVistaCostura();
    }
}
