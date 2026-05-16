<?php

namespace App\Infrastructure\Talleres\Repositories;

use App\Domain\Talleres\Repositories\OrdenTallerRepositoryInterface;
use App\Infrastructure\Talleres\Queries\ObtenerRecibosNormalesQuery;
use App\Infrastructure\Talleres\Queries\ObtenerRecibosParcalesQuery;
use App\Infrastructure\Talleres\Queries\ObtenerEntregasPorTallaQuery;
use App\Infrastructure\Talleres\Queries\ObtenerCantidadesTotalesQuery;
use App\Models\Role;
use Illuminate\Support\Collection;

class EloquentOrdenTallerRepository implements OrdenTallerRepositoryInterface
{
    private int $rolTallerId;

    public function __construct()
    {
        $rolTaller = Role::where('name', 'taller')->first();
        $this->rolTallerId = $rolTaller?->id ?? 0;
    }

    public function obtenerAsignadas(?string $search = null): Collection
    {
        $normales = $this->obtenerNormales($search);
        $parciales = $this->obtenerParciales($search);
        
        return $normales->concat($parciales);
    }

    public function obtenerNormales(?string $search = null): Collection
    {
        $query = new ObtenerRecibosNormalesQuery($this->rolTallerId, $search);
        return $query->execute();
    }

    public function obtenerParciales(?string $search = null): Collection
    {
        $query = new ObtenerRecibosParcalesQuery($this->rolTallerId, $search);
        return $query->execute();
    }

    public function obtenerEntregasPorTalla(int $reciboId, bool $esParcial = false): array
    {
        $query = new ObtenerEntregasPorTallaQuery([$reciboId], $esParcial);
        return $query->execute();
    }

    public function obtenerCantidadesTotales(array $reciboIds, array $prendaIds = [], bool $esParcial = false): array
    {
        $query = new ObtenerCantidadesTotalesQuery($reciboIds, $prendaIds, $esParcial);
        return $query->execute();
    }
}
