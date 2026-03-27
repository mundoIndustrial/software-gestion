<?php

namespace App\Application\Asesores\UseCases;

use App\Application\Services\ColorGeneroMangaBrocheService;

final class ObtenerCatalogoTelasAsesorUseCase
{
    public function __construct(
        private readonly ColorGeneroMangaBrocheService $catalogoService
    ) {
    }

    public function ejecutar(): array
    {
        return $this->catalogoService->obtenerTelas();
    }
}

