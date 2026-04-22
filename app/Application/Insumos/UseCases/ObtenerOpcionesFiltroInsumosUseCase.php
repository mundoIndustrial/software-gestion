<?php

namespace App\Application\Insumos\UseCases;

use App\Domain\Insumos\Repositories\MaterialesReadRepository;

class ObtenerOpcionesFiltroInsumosUseCase
{
    public function __construct(
        private readonly MaterialesReadRepository $repository
    ) {
    }

    public function execute(string $column, ?string $searchTerm = null, string $tipoRecibo = 'COSTURA'): array
    {
        $opciones = $this->repository->obtenerOpcionesFiltro($column, $tipoRecibo);

        // Filtrar por término de búsqueda si se proporciona
        if ($searchTerm && !empty($searchTerm)) {
            $searchTermLower = strtolower($searchTerm);
            $opciones = array_filter($opciones, function($valor) use ($searchTermLower) {
                return stripos($valor, $searchTermLower) !== false;
            });
            // Reindexar array después de filtrar
            $opciones = array_values($opciones);
        }

        return $opciones;
    }
}

