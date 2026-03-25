<?php

namespace App\Domain\BodegaDetalleTalla\Repositories;

interface BodegaDetalleTallaRepositoryInterface
{
    /**
     * Contar pedidos únicos pendientes para un asesor
     */
    public function contarPendientesAsesor(string $asesorNombre): int;

    /**
     * Obtener pendientes del asesor con paginación y filtros
     */
    public function obtenerPendientesAsesor(
        string $asesorNombre,
        string $search = '',
        string $tipo = 'todos',
        int $page = 1,
        int $perPage = 20
    ): array;
}
