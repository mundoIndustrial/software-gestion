<?php

namespace App\Modules\Cotizaciones\Contracts;

use Illuminate\Database\Eloquent\Collection;

/**
 * Interface CotizacionQueryServiceInterface
 * 
 * Servicio de consultas de cotizaciones (lectura)
 * Principio: Single Responsibility (SRP)
 */
interface CotizacionQueryServiceInterface
{
    /**
     * Obtener cotizaciones filtradas por tipo
     */
    public function getByType(int $userId, string $type, int $page = 1, int $perPage = 15);

    /**
     * Obtener todas las cotizaciones del usuario
     */
    public function getAllUserCotizaciones(int $userId): Collection;

    /**
     * Obtener borradores del usuario
     */
    public function getUserDrafts(int $userId): Collection;

    /**
     * Obtener cotizaciones enviadas del usuario
     */
    public function getUserSent(int $userId): Collection;
}
