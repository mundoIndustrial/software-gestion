<?php

namespace App\Application\UseCases\RecibosNovedades;

use App\Infrastructure\Repositories\NovedadesReciboRepository;

class EliminarNovedadReciboUseCase
{
    public function __construct(
        private readonly NovedadesReciboRepository $repository
    ) {}

    public function execute(int $novedadId): bool
    {
        return $this->repository->eliminar($novedadId);
    }
}
