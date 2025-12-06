<?php

namespace App\Modules\Cotizaciones\Contracts;

use App\Models\Cotizacion;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Interface CotizacionRepositoryInterface
 * 
 * Contrato para acceso a datos de Cotizaciones
 * Principio: Dependency Inversion (DIP)
 */
interface CotizacionRepositoryInterface
{
    /**
     * Obtener todas las cotizaciones del usuario (no borradores)
     */
    public function getByUser(int $userId, int $page = 1, int $perPage = 15): LengthAwarePaginator;

    /**
     * Obtener todos los borradores del usuario
     */
    public function getDraftsByUser(int $userId, int $page = 1, int $perPage = 15): LengthAwarePaginator;

    /**
     * Obtener cotización por ID
     */
    public function findById(int $id): ?Cotizacion;

    /**
     * Crear nueva cotización
     */
    public function create(array $data): Cotizacion;

    /**
     * Actualizar cotización
     */
    public function update(int $id, array $data): bool;

    /**
     * Eliminar cotización
     */
    public function delete(int $id): bool;

    /**
     * Filtrar cotizaciones por tipo
     */
    public function filterByType(Collection $cotizaciones, string $type): Collection;

    /**
     * Obtener todas las cotizaciones con relaciones
     */
    public function getAllWithRelations(int $userId): Collection;
}
