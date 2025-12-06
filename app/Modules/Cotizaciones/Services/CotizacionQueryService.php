<?php

namespace App\Modules\Cotizaciones\Services;

use App\Models\Cotizacion;
use App\Modules\Cotizaciones\Contracts\CotizacionRepositoryInterface;
use App\Modules\Cotizaciones\Contracts\CotizacionQueryServiceInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * CotizacionQueryService
 * 
 * Servicio para consultas de lectura (queries)
 * Responsabilidad única: lectura de cotizaciones
 * Principio: Single Responsibility (SRP)
 */
class CotizacionQueryService implements CotizacionQueryServiceInterface
{
    public function __construct(
        private CotizacionRepositoryInterface $repository
    ) {}

    /**
     * Obtener todas las cotizaciones del usuario
     */
    public function getAllUserCotizaciones(int $userId): Collection
    {
        return $this->repository->getAllWithRelations($userId)
            ->filter(fn($c) => !$c->es_borrador);
    }

    /**
     * Obtener borradores del usuario
     */
    public function getUserDrafts(int $userId): Collection
    {
        return $this->repository->getAllWithRelations($userId)
            ->filter(fn($c) => $c->es_borrador);
    }

    /**
     * Obtener cotizaciones enviadas del usuario
     */
    public function getUserSent(int $userId): Collection
    {
        return $this->getAllUserCotizaciones($userId)
            ->filter(fn($c) => $c->estado !== 'BORRADOR');
    }

    /**
     * Obtener cotizaciones filtradas por tipo
     */
    public function getByType(int $userId, string $type, int $page = 1, int $perPage = 15)
    {
        $allCotizaciones = $this->getAllUserCotizaciones($userId);
        $filtered = $this->repository->filterByType($allCotizaciones, $type);

        // Convertir a paginador manual
        return $this->paginate($filtered, $perPage, $page);
    }

    /**
     * Paginar colección manualmente
     */
    private function paginate(Collection $items, int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        $total = $items->count();
        $items = $items->slice(($page - 1) * $perPage, $perPage)->values();

        return new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            [
                'path' => url()->current(),
                'query' => request()->query(),
            ]
        );
    }
}
