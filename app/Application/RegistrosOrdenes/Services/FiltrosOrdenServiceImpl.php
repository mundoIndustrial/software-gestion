<?php

namespace App\Application\RegistrosOrdenes\Services;

use App\Application\RegistrosOrdenes\Contracts\FiltrosOrdenService;
use App\Services\CacheCalculosService;
use Illuminate\Support\Collection;

/**
 * FiltrosOrdenServiceImpl
 * 
 * Implementación para aplicación de filtros
 */
class FiltrosOrdenServiceImpl implements FiltrosOrdenService
{
    public function extraerDelRequest($request): array
    {
        $filters = [];
        $totalDiasFilter = null;

        // Extraer filtros dinámicos del request
        foreach ($request->all() as $key => $value) {
            // Saltar campos especiales
            if (in_array($key, ['search', 'page', 'per_page', 'sort', 'direction', 'get_unique_values', 'column'])) {
                continue;
            }

            // Detectar filtro de total_dias
            if ($key === 'total_de_dias_' || strpos($key, 'total_de_dias') !== false) {
                $totalDiasFilter = is_array($value) ? $value : [$value];
                continue;
            }

            if ($value !== null && $value !== '') {
                $filters[$key] = $value;
            }
        }

        return [
            'filters' => $filters,
            'totalDiasFilter' => $totalDiasFilter,
        ];
    }

    public function aplicar(&$query, array $filters): void
    {
        foreach ($filters as $column => $value) {
            if (is_array($value)) {
                $query->whereIn($column, $value);
            } else {
                $query->where($column, '=', $value);
            }
        }
    }

    public function aplicarFiltroTotalDias(&$ordenes, array $diasFiltro, array $festivos): Collection
    {
        $totalDiasCalculados = CacheCalculosService::getTotalDiasBatch($ordenes->toArray(), $festivos);

        return $ordenes->filter(function($orden) use ($totalDiasCalculados, $diasFiltro) {
            $totalDias = $totalDiasCalculados[$orden->numero_pedido] ?? 0;
            return in_array((int)$totalDias, $diasFiltro, true);
        });
    }
}
