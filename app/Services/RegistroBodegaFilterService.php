<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

/**
 * RegistroBodegaFilterService
 * 
 * Servicio para filtros dinámicos en tablas de bodega
 */
class RegistroBodegaFilterService
{
    protected $dateColumns = [
        'fecha_de_creacion_de_orden', 'inventario', 'insumos_y_telas', 'corte',
        'bordado', 'estampado', 'costura', 'reflectivo', 'lavanderia',
        'arreglos', 'marras', 'control_de_calidad', 'entrega'
    ];

    protected $allowedColumns = [
        'pedido', 'estado', 'area', 'total_de_dias_', 'cliente',
        'descripcion', 'cantidad', 'novedades', 'forma_de_pago',
        'fecha_de_creacion_de_orden', 'encargado_orden', 'dias_orden', 'inventario',
        'encargados_inventario', 'dias_inventario', 'insumos_y_telas', 'encargados_insumos',
        'dias_insumos', 'corte', 'encargados_de_corte', 'dias_corte', 'bordado',
        'codigo_de_bordado', 'dias_bordado', 'estampado', 'encargados_estampado',
        'dias_estampado', 'costura', 'modulo', 'dias_costura', 'reflectivo',
        'encargado_reflectivo', 'total_de_dias_reflectivo', 'lavanderia',
        'encargado_lavanderia', 'dias_lavanderia', 'arreglos', 'encargado_arreglos',
        'total_de_dias_arreglos', 'marras', 'encargados_marras', 'total_de_dias_marras',
        'control_de_calidad', 'encargados_calidad', 'dias_c_c', 'entrega',
        'encargados_entrega', 'despacho', 'column_52', '_pedido'
    ];

    /**
     * Extraer filtros del request
     */
    public function extractFiltersFromRequest($request): array
    {
        $filters = [];
        $totalDiasFilter = null;
        $pedidoIds = null;
        $separator = '|||FILTER_SEPARATOR|||';

        // Manejar filtro especial de IDs (para descripción)
        if ($request->has('filter_pedido_ids') && !empty($request->filter_pedido_ids)) {
            $pedidoIds = explode(',', $request->filter_pedido_ids);
            $pedidoIds = array_filter(array_map('trim', $pedidoIds));
        }

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
            'totalDiasFilter' => $totalDiasFilter,
            'pedidoIds' => $pedidoIds
        ];
    }

    /**
     * Aplicar filtros a la query
     */
    public function applyFiltersToQuery(Builder $query, array $filters): Builder
    {
        foreach ($filters as $column => $values) {
            if (empty($values)) {
                continue;
            }

            // Columnas de fecha
            if (in_array($column, $this->dateColumns)) {
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
            // Columna cliente: búsqueda parcial
            elseif ($column === 'cliente') {
                $query->where(function($q) use ($values) {
                    foreach ($values as $value) {
                        $q->orWhere('cliente', 'LIKE', '%' . $value . '%');
                    }
                });
            }
            // Otras columnas: whereIn
            else {
                $query->whereIn($column, $values);
            }
        }

        return $query;
    }

    /**
     * Aplicar filtro de IDs de pedidos
     */
    public function applyPedidoIdFilter(Builder $query, ?array $pedidoIds): Builder
    {
        if (!empty($pedidoIds)) {
            $query->whereIn('pedido', $pedidoIds);
        }
        return $query;
    }
}
