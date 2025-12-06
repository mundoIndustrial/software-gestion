<?php

namespace App\Modules\Cotizaciones\Repositories;

use App\Models\Cotizacion;
use App\Modules\Cotizaciones\Contracts\CotizacionRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * CotizacionRepository
 * 
 * Implementación concreta del repositorio de cotizaciones
 * Responsabilidad única: acceso a datos
 * Principio: Single Responsibility (SRP)
 */
class CotizacionRepository implements CotizacionRepositoryInterface
{
    protected string $model = Cotizacion::class;
    protected array $relations = ['tipoCotizacion', 'usuario', 'prendasCotizaciones', 'prendaCotizacion', 'logoCotizacion'];

    /**
     * Obtener todas las cotizaciones del usuario (no borradores)
     */
    public function getByUser(int $userId, int $page = 1, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model::where('user_id', $userId)
            ->where('es_borrador', false)
            ->with($this->relations)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Obtener todos los borradores del usuario
     */
    public function getDraftsByUser(int $userId, int $page = 1, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model::where('user_id', $userId)
            ->where('es_borrador', true)
            ->with($this->relations)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Obtener cotización por ID
     */
    public function findById(int $id): ?Cotizacion
    {
        return $this->model::with($this->relations)->find($id);
    }

    /**
     * Crear nueva cotización
     */
    public function create(array $data): Cotizacion
    {
        return $this->model::create($data);
    }

    /**
     * Actualizar cotización
     */
    public function update(int $id, array $data): bool
    {
        return $this->model::find($id)?->update($data) ?? false;
    }

    /**
     * Eliminar cotización
     */
    public function delete(int $id): bool
    {
        $cotizacion = $this->model::find($id);
        return $cotizacion?->delete() ?? false;
    }

    /**
     * Filtrar cotizaciones por tipo
     */
    public function filterByType(Collection $cotizaciones, string $type): Collection
    {
        if ($type === 'todas') {
            return $cotizaciones;
        }

        return $cotizaciones->filter(fn($c) => $c->obtenerTipoCotizacion() === $type);
    }

    /**
     * Obtener todas las cotizaciones con relaciones
     */
    public function getAllWithRelations(int $userId): Collection
    {
        return $this->model::where('user_id', $userId)
            ->with($this->relations)
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
