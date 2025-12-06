<?php

namespace App\Services;

use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;
use App\Models\ProcesoPrenda;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * RegistroOrdenQueryService
 * 
 * Servicio para construir y ejecutar queries del listado de órdenes
 * Responsabilidad única: Manejar la lógica de queries
 */
class RegistroOrdenQueryService
{
    /**
     * Columnas que son de fecha (para formatting)
     */
    protected $dateColumns = [
        'fecha_de_creacion_de_orden', 'fecha_estimada_de_entrega', 'inventario', 
        'insumos_y_telas', 'corte', 'bordado', 'estampado', 'costura', 'reflectivo', 
        'lavanderia', 'arreglos', 'marras', 'control_de_calidad', 'entrega'
    ];

    /**
     * Construir query base con select y with eager loading
     * 
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function buildBaseQuery()
    {
        return PedidoProduccion::query()
            ->select([
                'id', 'numero_pedido', 'estado', 'area', 'cliente', 'cliente_id',
                'fecha_de_creacion_de_orden', 'fecha_estimada_de_entrega',
                'fecha_ultimo_proceso',
                'dia_de_entrega', 'asesor_id', 'forma_de_pago',
                'novedades', 'cotizacion_id', 'numero_cotizacion', 'aprobado_por_supervisor_en'
            ])
            ->with([
                'asesora:id,name',
                'prendas' => function($q) {
                    $q->select('id', 'numero_pedido', 'nombre_prenda', 'cantidad', 'descripcion', 'descripcion_variaciones', 'cantidad_talla', 'color_id', 'tela_id', 'tipo_manga_id', 'tiene_bolsillos', 'tiene_reflectivo')
                      ->with('color:id,nombre', 'tela:id,nombre,referencia', 'tipoManga:id,nombre');
                }
            ])
            ->where(function($q) {
                $q->whereNotNull('aprobado_por_supervisor_en')
                  ->orWhereNull('cotizacion_id');
            });
    }

    /**
     * Aplicar filtros por rol de usuario
     * 
     * @param $query
     * @param $user
     * @param \Illuminate\Http\Request $request
     * @return mixed
     */
    public function applyRoleFilters($query, $user, $request)
    {
        if ($user && $user->role && $user->role->name === 'supervisor') {
            if (!$request->has('filter_estado')) {
                $query->where('estado', 'En Ejecución');
            }
        }
        return $query;
    }

    /**
     * Columnas que son de fecha (para formatting)
     */
    protected $dateColumnsOld = [
        'fecha_de_creacion_de_orden', 'fecha_estimada_de_entrega', 'inventario', 
        'insumos_y_telas', 'corte', 'bordado', 'estampado', 'costura', 'reflectivo', 
        'lavanderia', 'arreglos', 'marras', 'control_de_calidad', 'entrega'
    ];

    /**
     * Obtener valores únicos de una columna para los filtros
     * 
     * @param string $column
     * @return array
     */
    public function getUniqueValues(string $column): array
    {
        $allowedColumns = [
            'numero_pedido', 'estado', 'area', 'cliente', 'forma_de_pago',
            'novedades', 'dia_de_entrega', 'fecha_de_creacion_de_orden',
            'fecha_estimada_de_entrega', 'fecha_ultimo_proceso', 'descripcion_prendas',
            'asesora', 'encargado_orden'
        ];

        if (!in_array($column, $allowedColumns)) {
            throw new \InvalidArgumentException("Columna no permitida: {$column}");
        }

        $values = [];

        try {
            if ($column === 'asesora') {
                $values = PedidoProduccion::join('users', 'pedidos_produccion.asesor_id', '=', 'users.id')
                    ->whereNotNull('users.name')
                    ->distinct()
                    ->pluck('users.name')
                    ->filter(function($value) { return $value !== null && $value !== ''; })
                    ->values()
                    ->toArray();
                    
            } elseif ($column === 'descripcion_prendas') {
                $values = DB::table('prendas_pedido')
                    ->whereNotNull('descripcion')
                    ->where('descripcion', '!=', '')
                    ->distinct()
                    ->pluck('descripcion')
                    ->filter(function($value) { return $value !== null && $value !== ''; })
                    ->values()
                    ->toArray();
                    
            } elseif ($column === 'encargado_orden') {
                $values = ProcesoPrenda::where('proceso', 'Creación de Orden')
                    ->whereNotNull('encargado')
                    ->distinct()
                    ->pluck('encargado')
                    ->filter(function($value) { return $value !== null && $value !== ''; })
                    ->values()
                    ->toArray();
                    
            } else {
                $values = PedidoProduccion::whereNotNull($column)
                    ->distinct()
                    ->pluck($column)
                    ->filter(function($value) { return $value !== null && $value !== ''; })
                    ->values()
                    ->toArray();
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
     * Formatear array de valores de fecha a formato d/m/Y
     * 
     * @param array $values
     * @return array
     */
    protected function formatDateValues(array $values): array
    {
        return array_values(array_unique(array_map(function($value) {
            try {
                if (!empty($value)) {
                    return Carbon::parse($value)->format('d/m/Y');
                }
            } catch (\Exception $e) {
                // Retornar valor original si no se puede parsear
            }
            return $value;
        }, $values)));
    }
}
