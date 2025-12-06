<?php

namespace App\Services;

use App\Models\TablaOriginalBodega;
use App\Models\Festivo;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * RegistroBodegaQueryService
 * 
 * Servicio para construir queries del listado de órdenes en bodega
 */
class RegistroBodegaQueryService
{
    protected $dateColumns = [
        'fecha_de_creacion_de_orden', 'inventario', 'insumos_y_telas', 'corte',
        'bordado', 'estampado', 'costura', 'reflectivo', 'lavanderia',
        'arreglos', 'marras', 'control_de_calidad', 'entrega'
    ];

    /**
     * Construir query base
     */
    public function buildBaseQuery()
    {
        return TablaOriginalBodega::query();
    }

    /**
     * Obtener valores únicos de una columna
     */
    public function getUniqueValues(string $column): array
    {
        $allowedColumns = [
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

        if (!in_array($column, $allowedColumns)) {
            throw new \InvalidArgumentException("Columna no permitida: {$column}");
        }

        $values = [];

        try {
            if ($column === 'total_de_dias_') {
                $festivos = Festivo::pluck('fecha')->toArray();
                $ordenes = TablaOriginalBodega::all();
                foreach ($ordenes as $orden) {
                    $orden->setFestivos($festivos);
                }
                $values = $ordenes->map(function($orden) {
                    return $orden->total_de_dias;
                })->unique()->sort()->values()->toArray();
            } else {
                $values = TablaOriginalBodega::distinct()->pluck($column)->filter()->values()->toArray();
            }

            // Formatear fechas si aplica
            if (in_array($column, $this->dateColumns)) {
                $values = $this->formatDateValues($values);
            }

            sort($values);
            return $values;
            
        } catch (\Exception $e) {
            \Log::error("Error obteniendo valores únicos para {$column}", ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Formatear valores de fecha
     */
    protected function formatDateValues(array $values): array
    {
        return array_values(array_unique(array_map(function($value) {
            try {
                if (!empty($value)) {
                    return Carbon::parse($value)->format('d/m/Y');
                }
            } catch (\Exception $e) {
                // Retornar valor original
            }
            return $value;
        }, $values)));
    }
}
