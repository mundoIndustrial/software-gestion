<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

/**
 * RegistroOrdenFilterService
 * 
 * Servicio para aplicar filtros dinámicos a queries
 */
class RegistroOrdenFilterService
{
    protected $dateColumns = [
        'fecha_de_creacion_de_orden', 'fecha_estimada_de_entrega', 'inventario', 
        'insumos_y_telas', 'corte', 'bordado', 'estampado', 'costura', 'reflectivo', 
        'lavanderia', 'arreglos', 'marras', 'control_de_calidad', 'entrega'
    ];

    protected $allowedColumns = [
        'id', 'estado', 'area', 'total_de_dias_', 'dia_de_entrega', 'fecha_estimada_de_entrega', 
        'numero_pedido', 'cliente', 'descripcion_prendas', 'cantidad', 'novedades', 'forma_de_pago', 
        'asesora', 'encargado_orden', 'fecha_de_creacion_de_orden', 'fecha_ultimo_proceso'
    ];

    /**
     * Extraer filtros del request
     * 
     * @param \Illuminate\Http\Request $request
     * @return array ['filters' => [...], 'totalDiasFilter' => [...]]
     */
    public function extractFiltersFromRequest($request): array
    {
        $filters = [];
        $totalDiasFilter = null;
        $separator = '|||FILTER_SEPARATOR|||';

        foreach ($request->all() as $key => $value) {
            if (str_starts_with($key, 'filter_') && !empty($value)) {
                $column = str_replace('filter_', '', $key);
                $values = explode($separator, $value);
                $values = array_filter(array_map('trim', $values));

                if (in_array($column, $this->allowedColumns)) {
                    if ($column === 'total_de_dias_') {
                        $totalDiasFilter = array_map('intval', $values);
                    } else {
                        $filters[$column] = $values;
                    }
                }
            }
        }

        return [
            'filters' => $filters,
            'totalDiasFilter' => $totalDiasFilter
        ];
    }

    /**
     * Aplicar filtros a la query
     * 
     * @param Builder $query
     * @param array $filters
     * @return Builder
     */
    public function applyFiltersToQuery(Builder $query, array $filters): Builder
    {
        foreach ($filters as $column => $values) {
            if (empty($values)) {
                continue;
            }

            // Columna asesora: filtrar por nombre de usuario
            if ($column === 'asesora') {
                $query->whereIn('asesor_id', function($subquery) use ($values) {
                    $subquery->select('id')
                        ->from('users')
                        ->whereIn('name', $values);
                });
            }
            // Columna descripcion_prendas: buscar en prendas_pedido
            elseif ($column === 'descripcion_prendas') {
                $query->whereIn('numero_pedido', function($subquery) use ($values) {
                    $subquery->select('numero_pedido')
                        ->from('prendas_pedido')
                        ->where(function($q) use ($values) {
                            foreach ($values as $value) {
                                $q->orWhere('descripcion', 'LIKE', '%' . $value . '%');
                            }
                        })
                        ->distinct();
                });
            }
            // Columna encargado_orden: filtrar por procesos
            elseif ($column === 'encargado_orden') {
                $query->whereIn('numero_pedido', function($subquery) use ($values) {
                    $subquery->select('numero_pedido')
                        ->from('procesos_prenda')
                        ->where('proceso', 'Creación de Orden')
                        ->whereIn('encargado', $values)
                        ->distinct();
                });
            }
            // Columnas de fecha: convertir formato d/m/Y a Y-m-d
            elseif (in_array($column, $this->dateColumns)) {
                $query->where(function($q) use ($column, $values) {
                    foreach ($values as $dateValue) {
                        try {
                            $date = Carbon::createFromFormat('d/m/Y', $dateValue);
                            $q->orWhereDate($column, $date->format('Y-m-d'));
                        } catch (\Exception $e) {
                            $q->orWhere($column, $dateValue);
                        }
                    }
                });
            }
            // Cliente: búsqueda parcial con LIKE
            elseif ($column === 'cliente') {
                $query->where(function($q) use ($values) {
                    foreach ($values as $value) {
                        $q->orWhere('cliente', 'LIKE', '%' . $value . '%');
                    }
                });
            }
            // Otras columnas: whereIn directo
            else {
                $query->whereIn($column, $values);
            }
        }

        return $query;
    }
}
