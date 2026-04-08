<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Servicio para filtrado dinamico de RegistroOrden
 * Extrae logica compleja de filtrado del controlador
 */
class RegistroOrdenFilterExtendedService
{
    /**
     * Extraer filtros del request
     * @return array ['filters' => array, 'totalDiasFilter' => array|null]
     */
    public function extractFiltersFromRequest(Request $request): array
    {
        $filters = [];
        $totalDiasFilter = null;

        \Log::info(' [FILTER DEBUG] Todos los parametros del request:', [
            'all_params' => $request->all(),
            'query_string' => $request->getQueryString(),
        ]);

        foreach ($request->all() as $key => $value) {
            if (str_starts_with($key, 'filter_') && !empty($value)) {
                $column = str_replace('filter_', '', $key);

                \Log::info(' [FILTER DEBUG] Filtro encontrado:', [
                    'key' => $key,
                    'column' => $column,
                    'value' => $value,
                    'is_array' => is_array($value),
                ]);

                $separator = '|||FILTER_SEPARATOR|||';
                $values = explode($separator, $value);
                $values = array_filter(array_map('trim', $values));

                if (empty($values)) {
                    continue;
                }

                \Log::info(' [FILTER DEBUG] Valores procesados:', [
                    'column' => $column,
                    'values' => $values,
                    'count' => count($values),
                ]);

                if ($column === 'total_dias' || $column === 'total_de_dias_') {
                    $totalDiasFilter = array_map('intval', $values);
                    continue;
                }

                $filters[$column] = $values;
            }
        }

        \Log::info(' [FILTER DEBUG] Resultado final de extractFilters:', [
            'filters' => $filters,
            'totalDiasFilter' => $totalDiasFilter,
        ]);

        return [
            'filters' => $filters,
            'totalDiasFilter' => $totalDiasFilter,
        ];
    }

    /**
     * Aplicar filtros extraidos a la query.
     */
    public function applyFiltersToQuery(Builder $query, array $filters): Builder
    {
        $dateColumns = [
            'created_at', 'fecha_estimada_de_entrega', 'inventario',
            'insumos_y_telas', 'corte', 'bordado', 'estampado', 'costura', 'reflectivo',
            'lavanderia', 'arreglos', 'marras', 'control_de_calidad', 'entrega',
        ];

        $allowedColumns = [
            'numero_pedido', 'estado', 'area', 'cliente', 'forma_de_pago',
            'novedades', 'dia_de_entrega', 'created_at',
            'fecha_estimada_de_entrega', 'descripcion_prendas', 'asesora', 'asesor', 'encargado_orden',
        ];

        \Log::info(' [APPLY FILTER DEBUG] Iniciando applyFiltersToQuery:', [
            'filters_count' => count($filters),
            'filters' => $filters,
        ]);

        foreach ($filters as $column => $values) {
            $this->logFilterProcessing($column, $values, $allowedColumns);

            if (!$this->isAllowedColumn($column, $allowedColumns)) {
                continue;
            }

            $this->applySingleFilter($query, $column, $values, $dateColumns);
            $query->whereNotNull('numero_pedido');
        }

        \Log::info(' [APPLY FILTER DEBUG] Filtros aplicados exitosamente');

        return $query;
    }

    private function logFilterProcessing(string $column, array $values, array $allowedColumns): void
    {
        \Log::info(' [APPLY FILTER DEBUG] Procesando filtro:', [
            'column' => $column,
            'values' => $values,
            'is_allowed' => in_array($column, $allowedColumns),
        ]);
    }

    private function isAllowedColumn(string $column, array $allowedColumns): bool
    {
        if (in_array($column, $allowedColumns)) {
            return true;
        }

        \Log::warning(' [APPLY FILTER DEBUG] Columna no permitida, saltando:', ['column' => $column]);
        return false;
    }

    private function applySingleFilter(Builder $query, string $column, array $values, array $dateColumns): void
    {
        if ($this->isAsesorColumn($column)) {
            $this->applyAsesorFilter($query, $values, $column);
        } elseif ($column === 'descripcion_prendas') {
            $this->applyDescripcionPrendasFilter($query, $values);
        } elseif ($column === 'encargado_orden') {
            $this->applyEncargadoOrdenFilter($query, $values);
        } elseif (in_array($column, $dateColumns)) {
            $this->applyDateFilter($query, $column, $values);
        } else {
            $this->applyExactMatchFilter($query, $column, $values);
        }
    }

    private function isAsesorColumn(string $column): bool
    {
        return in_array($column, ['asesora', 'asesor'], true);
    }

    private function applyAsesorFilter(Builder $query, array $values, string $column): void
    {
        \Log::info(' [APPLY FILTER DEBUG] Aplicando filtro ' . $column);

        $query->whereIn('asesor_id', function ($subquery) use ($values) {
            $subquery->select('id')
                ->from('users')
                ->whereIn('name', $values);
        });
    }

    private function applyDescripcionPrendasFilter(Builder $query, array $values): void
    {
        $query->whereIn('numero_pedido', function ($subquery) use ($values) {
            $subquery->select('numero_pedido')
                ->from('prendas_pedido')
                ->where(function ($q) use ($values) {
                    foreach ($values as $desc) {
                        $q->orWhere('descripcion', 'LIKE', "%{$desc}%");
                    }
                })
                ->distinct();
        });
    }

    private function applyEncargadoOrdenFilter(Builder $query, array $values): void
    {
        $query->whereIn('numero_pedido', function ($subquery) use ($values) {
            $subquery->select('numero_pedido')
                ->from('procesos_prenda')
                ->whereIn('proceso', ['Creación de Orden', 'Creacion de Orden', 'Creación de Orden'])
                ->whereIn('encargado', $values)
                ->distinct();
        });
    }

    private function applyDateFilter(Builder $query, string $column, array $values): void
    {
        $query->where(function ($q) use ($column, $values) {
            foreach ($values as $dateValue) {
                $this->applySingleDateValueFilter($q, $column, (string) $dateValue);
            }
        });
    }

    private function applySingleDateValueFilter($query, string $column, string $dateValue): void
    {
        try {
            $date = Carbon::createFromFormat('d/m/Y', $dateValue);
            $query->orWhereDate($column, $date->format('Y-m-d'));
        } catch (\Exception $e) {
            $query->orWhere($column, $dateValue);
        }
    }

    private function applyExactMatchFilter(Builder $query, string $column, array $values): void
    {
        $query->where(function ($q) use ($column, $values) {
            foreach ($values as $value) {
                $q->orWhereRaw("TRIM(LOWER({$column})) = LOWER(?)", [trim((string) $value)]);
            }
        });
    }
}
