<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Servicio para filtrado dinámico de RegistroOrden
 * 
 * Extrae lógica compleja de filtrado del controlador
 * Maneja:
 * - Extracción de parámetros filter_* de request
 * - Aplicación de filtros con lógica especial por columna
 * - Manejo de fechas, subqueries, y filtros especiales
 */
class RegistroOrdenFilterExtendedService
{
    /**
     * Extraer filtros del request
     * 
     * Parsea todos los parámetros filter_* del request
     * Maneja:
     * - Separador especial: |||FILTER_SEPARATOR|||
     * - Limpieza de valores vacíos
     * - Separación de filtro especial de total_de_dias_
     * 
     * @param Request $request
     * @return array ['filters' => array, 'totalDiasFilter' => array|null]
     */
    public function extractFiltersFromRequest(Request $request): array
    {
        $filters = [];
        $totalDiasFilter = null;

        foreach ($request->all() as $key => $value) {
            if (str_starts_with($key, 'filter_') && !empty($value)) {
                $column = str_replace('filter_', '', $key);

                // Usar separador especial para valores que pueden contener comas
                $separator = '|||FILTER_SEPARATOR|||';
                $values = explode($separator, $value);

                // Limpiar valores vacíos
                $values = array_filter(array_map('trim', $values));

                if (empty($values)) {
                    continue;
                }

                // Separar el filtro de total_de_dias_ (procesado después)
                if ($column === 'total_de_dias_') {
                    $totalDiasFilter = array_map('intval', $values);
                    continue;
                }

                $filters[$column] = $values;
            }
        }

        return [
            'filters' => $filters,
            'totalDiasFilter' => $totalDiasFilter
        ];
    }

    /**
     * Aplicar filtros extraídos a la query
     * 
     * Maneja casos especiales:
     * - asesora: Subquery a users tabla
     * - descripcion_prendas: Subquery a prendas_pedido
     * - encargado_orden: Subquery a procesos_prenda
     * - Columnas de fecha: Parsea d/m/Y a Y-m-d para whereDate
     * - Otras columnas: Búsqueda exacta case-insensitive con TRIM
     * 
     * @param Builder $query Query a filtrar
     * @param array $filters Filtros extraídos
     * @return Builder Query filtrada
     */
    public function applyFiltersToQuery(Builder $query, array $filters): Builder
    {
        $dateColumns = [
            'fecha_de_creacion_de_orden', 'fecha_estimada_de_entrega', 'inventario',
            'insumos_y_telas', 'corte', 'bordado', 'estampado', 'costura', 'reflectivo',
            'lavanderia', 'arreglos', 'marras', 'control_de_calidad', 'entrega'
        ];

        $allowedColumns = [
            'numero_pedido', 'estado', 'area', 'cliente', 'forma_de_pago',
            'novedades', 'dia_de_entrega', 'fecha_de_creacion_de_orden',
            'fecha_estimada_de_entrega', 'descripcion_prendas', 'asesora', 'encargado_orden'
        ];

        foreach ($filters as $column => $values) {
            if (!in_array($column, $allowedColumns)) {
                continue;
            }

            // Caso especial: asesora - buscar en tabla users
            if ($column === 'asesora') {
                $query->whereIn('asesor_id', function ($subquery) use ($values) {
                    $subquery->select('id')
                        ->from('users')
                        ->whereIn('name', $values);
                });
            }
            // Caso especial: descripcion_prendas - buscar en prendas_pedido
            elseif ($column === 'descripcion_prendas') {
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
            // Caso especial: encargado_orden - buscar en procesos_prenda
            elseif ($column === 'encargado_orden') {
                $query->whereIn('numero_pedido', function ($subquery) use ($values) {
                    $subquery->select('numero_pedido')
                        ->from('procesos_prenda')
                        ->where('proceso', 'Creación de Orden')
                        ->whereIn('encargado', $values)
                        ->distinct();
                });
            }
            // Columnas de fecha: convertir d/m/Y a Y-m-d
            elseif (in_array($column, $dateColumns)) {
                $query->where(function ($q) use ($column, $values) {
                    foreach ($values as $dateValue) {
                        try {
                            $date = \Carbon\Carbon::createFromFormat('d/m/Y', $dateValue);
                            $q->orWhereDate($column, $date->format('Y-m-d'));
                        } catch (\Exception $e) {
                            $q->orWhere($column, $dateValue);
                        }
                    }
                });
            }
            // Otras columnas: búsqueda exacta case-insensitive
            else {
                $query->where(function ($q) use ($column, $values) {
                    foreach ($values as $value) {
                        $q->orWhereRaw("TRIM(LOWER({$column})) = LOWER(?)", [trim($value)]);
                    }
                });
            }
        }

        return $query;
    }
}
