<?php

namespace App\Infrastructure\Insumos\ReadModels;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RecibosCosturaReadRepository
{
    private function actividadPrendaExpressionJoin(): string
    {
        return "CASE
            WHEN consecutivos_recibos_pedidos.prenda_id IS NULL THEN NULL
            ELSE GREATEST(
                COALESCE(prenda_max.updated_at, '1970-01-01 00:00:00'),
                COALESCE(tallas_max.updated_at, '1970-01-01 00:00:00'),
                COALESCE(talla_colores_max.updated_at, '1970-01-01 00:00:00'),
                COALESCE(colores_telas_max.updated_at, '1970-01-01 00:00:00'),
                COALESCE(variantes_max.updated_at, '1970-01-01 00:00:00')
            )
        END";
    }

    private function applyActividadPrendaJoins($query)
    {
        return $query
            ->leftJoinSub(
                DB::table('prendas_pedido')
                    ->select('id as prenda_id', DB::raw('MAX(updated_at) as updated_at'))
                    ->whereNull('deleted_at')
                    ->groupBy('id'),
                'prenda_max',
                'prenda_max.prenda_id',
                '=',
                'consecutivos_recibos_pedidos.prenda_id'
            )
            ->leftJoinSub(
                DB::table('prenda_pedido_tallas')
                    ->select('prenda_pedido_id', DB::raw('MAX(updated_at) as updated_at'))
                    ->groupBy('prenda_pedido_id'),
                'tallas_max',
                'tallas_max.prenda_pedido_id',
                '=',
                'consecutivos_recibos_pedidos.prenda_id'
            )
            ->leftJoinSub(
                DB::table('prenda_pedido_talla_colores as pptc')
                    ->join('prenda_pedido_tallas as ppt', 'ppt.id', '=', 'pptc.prenda_pedido_talla_id')
                    ->select('ppt.prenda_pedido_id', DB::raw('MAX(pptc.updated_at) as updated_at'))
                    ->groupBy('ppt.prenda_pedido_id'),
                'talla_colores_max',
                'talla_colores_max.prenda_pedido_id',
                '=',
                'consecutivos_recibos_pedidos.prenda_id'
            )
            ->leftJoinSub(
                DB::table('prenda_pedido_colores_telas')
                    ->select('prenda_pedido_id', DB::raw('MAX(updated_at) as updated_at'))
                    ->groupBy('prenda_pedido_id'),
                'colores_telas_max',
                'colores_telas_max.prenda_pedido_id',
                '=',
                'consecutivos_recibos_pedidos.prenda_id'
            )
            ->leftJoinSub(
                DB::table('prenda_pedido_variantes')
                    ->select('prenda_pedido_id', DB::raw('MAX(updated_at) as updated_at'))
                    ->groupBy('prenda_pedido_id'),
                'variantes_max',
                'variantes_max.prenda_pedido_id',
                '=',
                'consecutivos_recibos_pedidos.prenda_id'
            );
    }

    public function buildBaseQuery()
    {
        $actividadPrendaExpr = $this->actividadPrendaExpressionJoin();

        $query = DB::table('consecutivos_recibos_pedidos')
            ->where('tipo_recibo', 'COSTURA')
            ->where('activo', 1)
            ->join('pedidos_produccion', 'consecutivos_recibos_pedidos.pedido_produccion_id', '=', 'pedidos_produccion.id');

        $query = $this->applyActividadPrendaJoins($query);

        return $query
            ->select(
                'consecutivos_recibos_pedidos.*',
                'consecutivos_recibos_pedidos.marcar_plooter',
                'pedidos_produccion.numero_pedido',
                'pedidos_produccion.numero_pedido as numero_pedido_original',
                'pedidos_produccion.cliente',
                'pedidos_produccion.estado as pedido_estado',
                'pedidos_produccion.area as pedido_area',
                'pedidos_produccion.novedades as pedido_novedades',
                'consecutivos_recibos_pedidos.estado as recibo_estado',
                'consecutivos_recibos_pedidos.area as recibo_area',
                'pedidos_produccion.created_at',
                'pedidos_produccion.dia_de_entrega',
                'pedidos_produccion.fecha_estimada_de_entrega',
                DB::raw("{$actividadPrendaExpr} as actividad_prenda_en")
            )
            ->where(function ($q) {
                // Mostrar recibos que estén en PENDIENTE_INSUMOS (estado del RECIBO, no del pedido)
                $q->whereIn('consecutivos_recibos_pedidos.estado', ['PENDIENTE_INSUMOS', 'PENDIENTE_TELA', 'PENDIENTE_PLOTTER', 'INSUMOS_PEDIDOS', 'DEVUELTO_ASESOR', 'Devuelto_Asesor', 'Anulada'])
                    // O también mostrar si el área del RECIBO está en CORTE o COSTURA
                    ->orWhereIn('consecutivos_recibos_pedidos.area', ['CORTE', 'COSTURA']);
            })
            // Exclusión general: No mostrar si el pedido está en PENDIENTE_SUPERVISOR
            ->where('pedidos_produccion.estado', '!=', 'PENDIENTE_SUPERVISOR');
    }

    /**
     * Construir base query sin los filtros por defecto de Area
     * Se usa cuando se van a aplicar filtros especi­ficos del usuario
     */
    public function buildBaseQueryForFiltering()
    {
        $actividadPrendaExpr = $this->actividadPrendaExpressionJoin();

        $query = DB::table('consecutivos_recibos_pedidos')
            ->where('tipo_recibo', 'COSTURA')
            ->where('activo', 1)
            ->join('pedidos_produccion', 'consecutivos_recibos_pedidos.pedido_produccion_id', '=', 'pedidos_produccion.id');

        $query = $this->applyActividadPrendaJoins($query);

        return $query
            ->select(
                'consecutivos_recibos_pedidos.*',
                'consecutivos_recibos_pedidos.marcar_plooter',
                'pedidos_produccion.numero_pedido',
                'pedidos_produccion.numero_pedido as numero_pedido_original',
                'pedidos_produccion.cliente',
                'pedidos_produccion.estado as pedido_estado',
                'pedidos_produccion.area as pedido_area',
                'pedidos_produccion.novedades as pedido_novedades',
                'consecutivos_recibos_pedidos.estado as recibo_estado',
                'consecutivos_recibos_pedidos.area as recibo_area',
                'pedidos_produccion.created_at',
                'pedidos_produccion.dia_de_entrega',
                'pedidos_produccion.fecha_estimada_de_entrega',
                DB::raw("{$actividadPrendaExpr} as actividad_prenda_en")
            )
            // Solo la exclusion de PENDIENTE_SUPERVISOR, sin los filtros por defecto
            ->where('pedidos_produccion.estado', '!=', 'PENDIENTE_SUPERVISOR');
    }

    public function applyFilters($query, array $filterColumns = [], array $filterValuesArray = [], array $filterValues = [], string $search = '')
    {
        // LOG: Recibiendo parametros
        \Log::info('[applyFilters] INICIANDO', [
            'filterColumns' => $filterColumns,
            'filterValuesArray' => $filterValuesArray,
            'filterValues' => $filterValues,
            'search' => $search,
        ]);

        // VALIDAR QUE HAY FILTROS
        if (empty($filterColumns) && empty($filterValuesArray) && empty($search)) {
            \Log::warning('[applyFilters]  ADVERTENCIA: No hay filtros para aplicar!');
            return $query;
        }

        // Agrupar filtros por columna para manejar multiples valores de la misma columna
        $filtersByColumn = [];
        
        if (!empty($filterColumns) && !empty($filterValuesArray)) {
            foreach ($filterColumns as $idx => $column) {
                if (!isset($filterValuesArray[$idx])) {
                    continue;
                }
                
                $filterValue = $filterValuesArray[$idx];
                
                // Mapear columnas a nombres de BD
                $dbColumn = match ($column) {
                    'numero_pedido' => 'pedidos_produccion.numero_pedido',
                    'cliente' => 'pedidos_produccion.cliente',
                    'estado' => 'consecutivos_recibos_pedidos.estado',  // ← RECIBO_ESTADO, no pedido_estado
                    'area' => 'consecutivos_recibos_pedidos.area',   // ← RECIBO_AREA, no pedido_area
                    'created_at' => 'pedidos_produccion.created_at',
                    'consecutivo_actual' => 'consecutivos_recibos_pedidos.consecutivo_actual',
                    default => $column,
                };
                
                // Agrupar valores por columna
                if (!isset($filtersByColumn[$column])) {
                    $filtersByColumn[$column] = [];
                }
                $filtersByColumn[$column][] = [
                    'value' => $filterValue,
                    'dbColumn' => $dbColumn
                ];
            }
        }
        
        \Log::info('[applyFilters] Filtros agrupados', ['filtersByColumn' => $filtersByColumn]);
        
        // Aplicar filtros agrupados por columna
        foreach ($filtersByColumn as $column => $filters) {
            $dbColumn = $filters[0]['dbColumn'] ?? $column;
            $values = array_map(fn($f) => $f['value'], $filters);
            
            \Log::info('[applyFilters] Procesando columna', [
                'column' => $column,
                'dbColumn' => $dbColumn,
                'values' => $values,
            ]);
            
            // Conversiones especiales de valores
            if ($column === 'estado') {
                $values = array_map(
                    fn($v) => match ($v) {
                        'Pendiente Insumos' => 'PENDIENTE_INSUMOS',
                        'Pendiente Tela' => 'PENDIENTE_TELA',
                        'Pendiente Plotter' => 'PENDIENTE_PLOTTER',
                        'Devuelto Asesor' => 'DEVUELTO_ASESOR',
                        'Devuelto_Asesor' => 'DEVUELTO_ASESOR',
                        default => $v,
                    },
                    $values
                );
            }
            
            // Aplicar filtro segun el tipo de columna
            if (in_array($dbColumn, ['pedidos_produccion.numero_pedido', 'pedidos_produccion.cliente', 'consecutivos_recibos_pedidos.consecutivo_actual'], true)) {
                // Para busqueda de texto: usar LIKE para cada valor
                \Log::info('[applyFilters] Aplicando filtro LIKE', ['dbColumn' => $dbColumn]);
                $query->where(function ($q) use ($dbColumn, $values) {
                    foreach ($values as $idx => $value) {
                        if ($idx === 0) {
                            $q->where($dbColumn, 'LIKE', "%{$value}%");
                        } else {
                            $q->orWhere($dbColumn, 'LIKE', "%{$value}%");
                        }
                    }
                });
            } elseif ($dbColumn === 'pedidos_produccion.created_at') {
                // Para fechas: convertir y usar whereDate
                \Log::info('[applyFilters] Aplicando filtro de fecha');
                $dates = [];
                foreach ($values as $value) {
                    try {
                        $fecha = Carbon::createFromFormat('d/m/Y', $value);
                        $dates[] = $fecha->format('Y-m-d');
                    } catch (\Exception $e) {
                        \Log::warning("Error al convertir fecha: {$value}");
                    }
                }
                if (!empty($dates)) {
                    $query->whereIn(DB::raw('DATE(' . $dbColumn . ')'), $dates);
                }
            } else {
                // Para valores exactos: usar whereIn
                \Log::info('[applyFilters] Aplicando filtro whereIn', ['dbColumn' => $dbColumn, 'values' => $values]);
                $query->whereIn($dbColumn, $values);
            }
        }
        
        // Aplicar busqueda de texto
        if (!empty($search)) {
            \Log::info('[applyFilters] Aplicando busqueda', ['search' => $search]);
            $query->where(function ($q) use ($search) {
                $q->where('consecutivos_recibos_pedidos.consecutivo_actual', 'LIKE', "%{$search}%")
                    ->orWhere('pedidos_produccion.numero_pedido', 'LIKE', "%{$search}%")
                    ->orWhere('pedidos_produccion.cliente', 'LIKE', "%{$search}%");
            });
        }

        \Log::info('[applyFilters] SQL generado', ['sql' => $query->toSql(), 'bindings' => $query->getBindings()]);
        return $query;
    }

    public function obtenerMapaParciales($recibos): array
    {
        $parcialIds = $recibos
            ->map(function ($recibo) {
                $notas = isset($recibo->notas) ? (string) $recibo->notas : '';
                if ($notas !== '' && preg_match('/parcial_id:(\d+)/i', $notas, $matches)) {
                    return (int) $matches[1];
                }

                return null;
            })
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($parcialIds)) {
            return [];
        }

        return DB::table('pedidos_parciales')
            ->whereNull('deleted_at')
            ->whereIn('id', $parcialIds)
            ->pluck('created_at', 'id')
            ->map(fn($dt) => $dt ? (string) $dt : null)
            ->toArray();
    }
}
