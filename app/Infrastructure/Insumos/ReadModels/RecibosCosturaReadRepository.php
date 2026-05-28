<?php

namespace App\Infrastructure\Insumos\ReadModels;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RecibosCosturaReadRepository
{
    private function applyTipoReciboFilter($query, string $tipoRecibo)
    {
        $tipoReciboNormalizado = strtoupper(trim($tipoRecibo));

        if ($tipoReciboNormalizado === 'REFLECTIVO') {
            return $query->whereRaw('UPPER(TRIM(consecutivos_recibos_pedidos.tipo_recibo)) = ?', ['REFLECTIVO']);
        }

        if ($tipoReciboNormalizado === 'CORTE-PARA-BODEGA') {
            return $query->whereRaw('UPPER(TRIM(consecutivos_recibos_pedidos.tipo_recibo)) = ?', ['CORTE-PARA-BODEGA']);
        }

        return $query->whereRaw('UPPER(TRIM(consecutivos_recibos_pedidos.tipo_recibo)) = ?', [$tipoReciboNormalizado]);
    }

    public function buildBaseQuery(string $tipoRecibo = 'COSTURA')
    {
        $tipoReciboNormalizado = strtoupper(trim($tipoRecibo));

        if ($tipoReciboNormalizado === 'CORTE-PARA-BODEGA') {
            return DB::table('consecutivos_recibos_pedidos')
                ->leftJoin('prenda_bodega', 'consecutivos_recibos_pedidos.prenda_bodega_id', '=', 'prenda_bodega.id')
                ->select(
                    'consecutivos_recibos_pedidos.*',
                    'consecutivos_recibos_pedidos.marcar_plooter',
                    'consecutivos_recibos_pedidos.created_at as recibo_created_at',
                    DB::raw('NULL as numero_pedido'),
                    DB::raw('NULL as numero_pedido_original'),
                    'prenda_bodega.descripcion as cliente',
                    DB::raw('NULL as pedido_estado'),
                    DB::raw('NULL as pedido_area'),
                    DB::raw('NULL as pedido_novedades'),
                    'consecutivos_recibos_pedidos.estado as recibo_estado',
                    'consecutivos_recibos_pedidos.area as recibo_area',
                    DB::raw('NULL as pedido_created_at'),
                    DB::raw('NULL as dia_de_entrega'),
                    DB::raw('NULL as fecha_estimada_de_entrega'),
                    DB::raw('0 as esta_completado')
                )
                ->whereRaw('UPPER(TRIM(consecutivos_recibos_pedidos.tipo_recibo)) = ?', ['CORTE-PARA-BODEGA'])
                ->whereNotNull('consecutivos_recibos_pedidos.consecutivo_actual');
        }

        $query = DB::table('consecutivos_recibos_pedidos')
            ->join('pedidos_produccion', 'consecutivos_recibos_pedidos.pedido_produccion_id', '=', 'pedidos_produccion.id');

        $query = $this->applyTipoReciboFilter($query, $tipoRecibo);

        $query = $query
            ->select(
                'consecutivos_recibos_pedidos.*',
                'consecutivos_recibos_pedidos.marcar_plooter',
                'consecutivos_recibos_pedidos.created_at as recibo_created_at',
                'pedidos_produccion.numero_pedido',
                'pedidos_produccion.numero_pedido as numero_pedido_original',
                'pedidos_produccion.cliente',
                'pedidos_produccion.estado as pedido_estado',
                'pedidos_produccion.area as pedido_area',
                'pedidos_produccion.novedades as pedido_novedades',
                'consecutivos_recibos_pedidos.estado as recibo_estado',
                'consecutivos_recibos_pedidos.area as recibo_area',
                'pedidos_produccion.created_at as pedido_created_at',
                'pedidos_produccion.dia_de_entrega',
                'pedidos_produccion.fecha_estimada_de_entrega',
                DB::raw('(SELECT CASE WHEN COUNT(*) > 0 THEN 1 ELSE 0 END FROM prenda_recibo_completado WHERE id_recibo = consecutivos_recibos_pedidos.id) as esta_completado')
            )
            ->whereNotNull('consecutivos_recibos_pedidos.consecutivo_actual');

        // Estas reglas aplican SOLO al flujo de materiales de COSTURA.
        // Para REFLECTIVO y CORTE-PARA-BODEGA evitamos mezclar reglas de área/estado de costura.
        if (strtoupper(trim($tipoRecibo)) === 'COSTURA') {
            $query->where(function ($q) {
                $q->whereIn('consecutivos_recibos_pedidos.estado', ['PENDIENTE_INSUMOS', 'PENDIENTE_TELA', 'PENDIENTE_METRAJE', 'PENDIENTE_PLOTTER', 'INSUMOS_PEDIDOS', 'DEVUELTO_ASESOR', 'Devuelto_Asesor', 'ANULADO', 'Anulada'])
                    ->orWhereIn('consecutivos_recibos_pedidos.area', ['CORTE', 'COSTURA', 'ANULADO']);
            });
            // Mostrar también recibos anulados cuyo flujo ya movió el área a ANULADO.
            $query->whereIn(
                DB::raw('UPPER(TRIM(consecutivos_recibos_pedidos.area))'),
                ['INSUMOS', 'ANULADO']
            );
        } else {
            // Para REFLECTIVO: solo mostrar recibos del área "Insumos"
            $query->where(function ($q) {
                $q->whereRaw('UPPER(TRIM(consecutivos_recibos_pedidos.area)) = ?', ['INSUMOS']);
            });
        }

        return $query;
    }

    public function buildBaseQueryForFiltering(string $tipoRecibo = 'COSTURA')
    {
        $tipoReciboNormalizado = strtoupper(trim($tipoRecibo));

        if ($tipoReciboNormalizado === 'CORTE-PARA-BODEGA') {
            return DB::table('consecutivos_recibos_pedidos')
                ->leftJoin('prenda_bodega', 'consecutivos_recibos_pedidos.prenda_bodega_id', '=', 'prenda_bodega.id')
                ->select(
                    'consecutivos_recibos_pedidos.*',
                    'consecutivos_recibos_pedidos.marcar_plooter',
                    'consecutivos_recibos_pedidos.created_at as recibo_created_at',
                    DB::raw('NULL as numero_pedido'),
                    DB::raw('NULL as numero_pedido_original'),
                    'prenda_bodega.descripcion as cliente',
                    DB::raw('NULL as pedido_estado'),
                    DB::raw('NULL as pedido_area'),
                    DB::raw('NULL as pedido_novedades'),
                    'consecutivos_recibos_pedidos.estado as recibo_estado',
                    'consecutivos_recibos_pedidos.area as recibo_area',
                    DB::raw('NULL as pedido_created_at'),
                    DB::raw('NULL as dia_de_entrega'),
                    DB::raw('NULL as fecha_estimada_de_entrega'),
                    DB::raw('0 as esta_completado')
                )
                ->whereRaw('UPPER(TRIM(consecutivos_recibos_pedidos.tipo_recibo)) = ?', ['CORTE-PARA-BODEGA'])
                ->whereNotNull('consecutivos_recibos_pedidos.consecutivo_actual');
        }

        $query = DB::table('consecutivos_recibos_pedidos')
            ->join('pedidos_produccion', 'consecutivos_recibos_pedidos.pedido_produccion_id', '=', 'pedidos_produccion.id');

        $query = $this->applyTipoReciboFilter($query, $tipoRecibo);

        return $query
            ->select(
                'consecutivos_recibos_pedidos.*',
                'consecutivos_recibos_pedidos.marcar_plooter',
                'consecutivos_recibos_pedidos.created_at as recibo_created_at',
                'pedidos_produccion.numero_pedido',
                'pedidos_produccion.numero_pedido as numero_pedido_original',
                'pedidos_produccion.cliente',
                'pedidos_produccion.estado as pedido_estado',
                'pedidos_produccion.area as pedido_area',
                'pedidos_produccion.novedades as pedido_novedades',
                'consecutivos_recibos_pedidos.estado as recibo_estado',
                'consecutivos_recibos_pedidos.area as recibo_area',
                'pedidos_produccion.created_at as pedido_created_at',
                'pedidos_produccion.dia_de_entrega',
                'pedidos_produccion.fecha_estimada_de_entrega',
                DB::raw('(SELECT CASE WHEN COUNT(*) > 0 THEN 1 ELSE 0 END FROM prenda_recibo_completado WHERE id_recibo = consecutivos_recibos_pedidos.id) as esta_completado')
            )
            ->whereNotNull('consecutivos_recibos_pedidos.consecutivo_actual');
    }

    public function applyFilters($query, array $filterColumns = [], array $filterValuesArray = [], array $filterValues = [], string $search = '', string $tipoRecibo = 'COSTURA')
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

        $tipoReciboNormalizado = strtoupper(trim($tipoRecibo));
        $esCorteBodega = $tipoReciboNormalizado === 'CORTE-PARA-BODEGA';
        $fechaColumn = $tipoReciboNormalizado === 'REFLECTIVO' || $esCorteBodega
            ? 'consecutivos_recibos_pedidos.created_at'
            : 'pedidos_produccion.created_at';

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
                    'numero_pedido' => $esCorteBodega ? 'consecutivos_recibos_pedidos.consecutivo_actual' : 'pedidos_produccion.numero_pedido',
                    'cliente' => $esCorteBodega ? 'prenda_bodega.descripcion' : 'pedidos_produccion.cliente',
                    'estado' => 'consecutivos_recibos_pedidos.estado',  // ← RECIBO_ESTADO, no pedido_estado
                    'area' => 'consecutivos_recibos_pedidos.area',   // ← RECIBO_AREA, no pedido_area
                    'created_at' => $fechaColumn,
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
                        'Pendiente Metraje' => 'PENDIENTE_METRAJE',
                        'Pendiente Plotter' => 'PENDIENTE_PLOTTER',
                        'Devuelto Asesor' => 'DEVUELTO_ASESOR',
                        'Devuelto_Asesor' => 'DEVUELTO_ASESOR',
                        'Anulada' => 'ANULADO',
                        default => $v,
                    },
                    $values
                );
            }
            
            // Aplicar filtro segun el tipo de columna
            if (in_array($dbColumn, ['pedidos_produccion.numero_pedido', 'pedidos_produccion.cliente', 'prenda_bodega.descripcion', 'consecutivos_recibos_pedidos.consecutivo_actual'], true)) {
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
            } elseif (in_array($dbColumn, ['pedidos_produccion.created_at', 'consecutivos_recibos_pedidos.created_at'], true)) {
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
            $search = trim((string) $search);
            $searchIsNumeric = ctype_digit($search);

            \Log::info('[applyFilters] Aplicando busqueda', [
                'search' => $search,
                'search_is_numeric' => $searchIsNumeric,
                'search_scope' => 'consecutivo_actual_and_cliente',
            ]);

            $query->where(function ($q) use ($search, $searchIsNumeric, $esCorteBodega) {
                $q->where('consecutivos_recibos_pedidos.consecutivo_actual', 'LIKE', "%{$search}%");

                if ($esCorteBodega) {
                    $q->orWhere('prenda_bodega.descripcion', 'LIKE', "%{$search}%");
                } else {
                    $q->orWhere('pedidos_produccion.cliente', 'LIKE', "%{$search}%");
                }

                // Refuerzo para recibos numéricos exactos (ej: "48").
                if ($searchIsNumeric) {
                    $q->orWhere('consecutivos_recibos_pedidos.consecutivo_actual', '=', $search);
                }
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
