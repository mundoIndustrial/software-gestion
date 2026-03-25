<?php

namespace App\Application\UseCases\RecibosNovedades;

use App\Infrastructure\Repositories\NovedadesReciboRepository;

class ObtenerConsolidadoNovedadesUseCase
{
    public function __construct(
        private readonly NovedadesReciboRepository $repository
    ) {}

    public function execute(int $pedidoId, int $numeroRecibo): array
    {
        return $this->repository->obtenerConsolidadoPorRecibo($pedidoId, $numeroRecibo);
    }
}
