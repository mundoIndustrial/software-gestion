<?php

namespace App\Application\UseCases\RecibosNovedades;

use App\Infrastructure\Repositories\NovedadesReciboRepository;
use Illuminate\Database\Eloquent\Collection;

class ObtenerNovedadesReciboUseCase
{
    public function __construct(
        private readonly NovedadesReciboRepository $repository
    ) {}

    public function execute(int|string $pedidoId, int $numeroRecibo): Collection
    {
        return $this->repository->obtenerPorRecibo($pedidoId, $numeroRecibo);
    }
}
