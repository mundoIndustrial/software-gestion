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

        // 🔍 DEBUG: Log todos los parámetros recibidos
        \Log::info('🔍 [FILTER DEBUG] Todos los parámetros del request:', [
            'all_params' => $request->all(),
            'query_string' => $request->getQueryString()
        ]);

        foreach ($request->all() as $key => $value) {
            if (str_starts_with($key, 'filter_') && !empty($value)) {
                $column = str_replace('filter_', '', $key);

                // 🔍 DEBUG: Log cada filtro encontrado
                \Log::info('🔍 [FILTER DEBUG] Filtro encontrado:', [
                    'key' => $key,
                    'column' => $column,
                    'value' => $value,
                    'is_array' => is_array($value)
                ]);

                // Usar separador especial para valores que pueden contener comas
                $separator = '|||FILTER_SEPARATOR|||';
                $values = explode($separator, $value);

                // Limpiar valores vacíos
                $values = array_filter(array_map('trim', $values));

                if (empty($values)) {
                    continue;
                }

                // 🔍 DEBUG: Log valores procesados
                \Log::info('🔍 [FILTER DEBUG] Valores procesados:', [
                    'column' => $column,
                    'values' => $values,
                    'count' => count($values)
                ]);

                // Separar el filtro de total_dias (procesado después)
                if ($column === 'total_dias' || $column === 'total_de_dias_') {
                    $totalDiasFilter = array_map('intval', $values);
                    continue;
                }

                $filters[$column] = $values;
            }
        }

        // 🔍 DEBUG: Log resultado final
        \Log::info('🔍 [FILTER DEBUG] Resultado final de extractFilters:', [
            'filters' => $filters,
            'totalDiasFilter' => $totalDiasFilter
        ]);

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
            'fecha_estimada_de_entrega', 'descripcion_prendas', 'asesora', 'asesor', 'encargado_orden'
        ];

        // 🔍 DEBUG: Log filtros recibidos
        \Log::info('🔍 [APPLY FILTER DEBUG] Iniciando applyFiltersToQuery:', [
            'filters_count' => count($filters),
            'filters' => $filters
        ]);

        foreach ($filters as $column => $values) {
            // 🔍 DEBUG: Log cada filtro que se va a procesar
            \Log::info('🔍 [APPLY FILTER DEBUG] Procesando filtro:', [
                'column' => $column,
                'values' => $values,
                'is_allowed' => in_array($column, $allowedColumns)
            ]);

            if (!in_array($column, $allowedColumns)) {
                \Log::warning('🔍 [APPLY FILTER DEBUG] Columna no permitida, saltando:', ['column' => $column]);
                continue;
            }

            // Caso especial: asesora - buscar en tabla users
            if ($column === 'asesora') {
                \Log::info('🔍 [APPLY FILTER DEBUG] Aplicando filtro asesora');
                $query->whereIn('asesor_id', function ($subquery) use ($values) {
                    $subquery->select('id')
                        ->from('users')
                        ->whereIn('name', $values);
                })->whereNotNull('numero_pedido'); // Asegurar que solo se incluyan pedidos con número
            }
            // Caso especial: asesor - buscar en tabla users (alias de asesora)
            elseif ($column === 'asesor') {
                \Log::info('🔍 [APPLY FILTER DEBUG] Aplicando filtro asesor');
                $query->whereIn('asesor_id', function ($subquery) use ($values) {
                    $subquery->select('id')
                        ->from('users')
                        ->whereIn('name', $values);
                })->whereNotNull('numero_pedido'); // Asegurar que solo se incluyan pedidos con número
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
                })->whereNotNull('numero_pedido'); // Asegurar que solo se incluyan pedidos con número
            }
            // Caso especial: encargado_orden - buscar en procesos_prenda
            elseif ($column === 'encargado_orden') {
                $query->whereIn('numero_pedido', function ($subquery) use ($values) {
                    $subquery->select('numero_pedido')
                        ->from('procesos_prenda')
                        ->where('proceso', 'Creación de Orden')
                        ->whereIn('encargado', $values)
                        ->distinct();
                })->whereNotNull('numero_pedido'); // Asegurar que solo se incluyan pedidos con número
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
                })->whereNotNull('numero_pedido'); // Asegurar que solo se incluyan pedidos con número
            }
            // Otras columnas: búsqueda exacta case-insensitive
            else {
                $query->where(function ($q) use ($column, $values) {
                    foreach ($values as $value) {
                        $q->orWhereRaw("TRIM(LOWER({$column})) = LOWER(?)", [trim($value)]);
                    }
                })->whereNotNull('numero_pedido'); // Asegurar que solo se incluyan pedidos con número
            }
        }

        // 🔍 DEBUG: Log resultado final
        \Log::info('🔍 [APPLY FILTER DEBUG] Filtros aplicados exitosamente');

        return $query;
    }
}
