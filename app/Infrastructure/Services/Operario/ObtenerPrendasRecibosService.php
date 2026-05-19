<?php

namespace App\Infrastructure\Services\Operario;

use App\Domain\Operario\Services\OperarioPrendasRecibosReadService;
use App\Models\User;
use Illuminate\Support\Collection;

class ObtenerPrendasRecibosService implements OperarioPrendasRecibosReadService
{
    public function __construct(
        private readonly ObtenerPrendasRecibosListadoService $listadoService,
        private readonly ObtenerPrendasRecibosGlobalCosturaService $globalCosturaService,
        private readonly ObtenerPrendasRecibosBodegaService $bodegaService
    ) {}

    public function obtenerPrendasConRecibos(User $usuario, ?string $filtroRecibo = null): Collection
    {
        return $this->listadoService->obtenerPrendasConRecibos($usuario, $filtroRecibo);
    }

    public function obtenerPrendasConRecibosTodosCostura(): Collection
    {
        return $this->globalCosturaService->obtenerPrendasConRecibosTodosCostura();
    }

    public function obtenerPrendasConRecibosBodegaCortador(User $usuario): Collection
    {
        return $this->bodegaService->obtenerPrendasConRecibosBodegaCortador($usuario);
    }

    public function obtenerConteoPrendasConRecibosBodegaCortador(User $usuario): int
    {
        return $this->bodegaService->obtenerConteoPrendasConRecibosBodegaCortador($usuario);
    }

    public function obtenerPrendasConRecibosBodegaVistaCostura(): \Illuminate\Support\Collection
    {
        return $this->listadoService->obtenerPrendasConRecibosBodegaVistaCostura();
    }
}
