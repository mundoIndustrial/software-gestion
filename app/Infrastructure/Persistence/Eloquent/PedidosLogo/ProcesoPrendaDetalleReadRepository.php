<?php

namespace App\Infrastructure\Persistence\Eloquent\PedidosLogo;

use App\Domain\PedidosLogo\Repositories\ProcesoPrendaDetalleReadRepositoryInterface;
use App\Models\PedidosProcesosPrendaDetalle;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class ProcesoPrendaDetalleReadRepository implements ProcesoPrendaDetalleReadRepositoryInterface
{
    public function paginarRecibosAprobados(array $tipoProcesoIds, ?string $search, bool $soloMinimalRole, ?string $areaFija, int $perPage = 20, ?array $columnFilters = null, bool $incluirEntregados = false): LengthAwarePaginator
    {
        $tipoReciboCase = "CASE pedidos_procesos_prenda_detalles.tipo_proceso_id "
            . "WHEN 2 THEN 'BORDADO' "
            . "WHEN 3 THEN 'ESTAMPADO' "
            . "WHEN 4 THEN 'DTF' "
            . "WHEN 5 THEN 'SUBLIMADO' "
            . "ELSE NULL END";
        $searchTerm = $this->normalizarBusqueda($search);

        // Query 1: Procesos base (sin filtrar por si existe parcial, para permitir ver ambos)
        $queryProcesos = PedidosProcesosPrendaDetalle::query()
            ->selectRaw("
                pedidos_procesos_prenda_detalles.id,
                pedidos_procesos_prenda_detalles.prenda_pedido_id,
                pedidos_procesos_prenda_detalles.tipo_proceso_id,
                pedidos_procesos_prenda_detalles.estado,
                pedidos_procesos_prenda_detalles.numero_recibo,
                pedidos_procesos_prenda_detalles.tipo_recibo,
                pedidos_procesos_prenda_detalles.etiqueta_proceso,
                pedidos_procesos_prenda_detalles.notas_rechazo,
                pedidos_procesos_prenda_detalles.fecha_aprobacion,
                pedidos_procesos_prenda_detalles.aprobado_por,
                pedidos_procesos_prenda_detalles.datos_adicionales,
                pedidos_procesos_prenda_detalles.created_at,
                pedidos_procesos_prenda_detalles.updated_at,
                pedidos_procesos_prenda_detalles.deleted_at,
                MAX(palp.area) as area,
                MAX(palp.novedades) as novedades,
                MAX(palp.fechas_areas) as fechas_areas,
                COALESCE(crp.consecutivo_actual, pedidos_procesos_prenda_detalles.numero_recibo) as numero_recibo_consecutivo,
                crp.id as consecutivo_recibo_id,
                COALESCE(crp.created_at, pedidos_procesos_prenda_detalles.created_at) as fecha_creacion_recibo,
                NULL as fecha_activacion,
                0 as es_parcial,
                NULL as pedido_parcial_id,
                pp.pedido_produccion_id
            ")
            ->leftJoin('prenda_areas_logo_pedido as palp', function ($join) {
                $join->on('palp.proceso_prenda_detalle_id', '=', 'pedidos_procesos_prenda_detalles.id')
                     ->whereNull('palp.pedido_parcial_id');
            })
            ->leftJoin('prendas_pedido as pp', 'pp.id', '=', 'pedidos_procesos_prenda_detalles.prenda_pedido_id')
            ->leftJoin('pedidos_produccion as ped', 'ped.id', '=', 'pp.pedido_produccion_id')
            ->leftJoin('clientes as cli', 'cli.id', '=', 'ped.cliente_id')
            ->join('consecutivos_recibos_pedidos as crp', function ($join) use ($tipoReciboCase) {
                $join->on('crp.pedido_produccion_id', '=', 'pp.pedido_produccion_id')
                    ->on('crp.prenda_id', '=', 'pp.id')
                    ->where('crp.activo', 1)
                    ->whereRaw("crp.tipo_recibo = ({$tipoReciboCase})")
                    ->whereRaw("(crp.origen_recibo <> 'ANEXO' OR crp.origen_recibo IS NULL OR crp.origen_recibo = '')");
            })
            ->whereIn('pedidos_procesos_prenda_detalles.estado', ['APROBADO', 'COMPLETADO'])
            ->whereIn('pedidos_procesos_prenda_detalles.tipo_proceso_id', $tipoProcesoIds)
            ->groupBy('pedidos_procesos_prenda_detalles.id', 'crp.id', 'crp.consecutivo_actual', 'crp.created_at', 'pp.pedido_produccion_id');

        $this->aplicarBusqueda($queryProcesos, $searchTerm, ['ped.cliente', 'cli.nombre', 'crp.consecutivo_actual', 'pedidos_procesos_prenda_detalles.numero_recibo']);
        $this->aplicarFiltrosColumnas($queryProcesos, $columnFilters);

        if ($areaFija) $queryProcesos->having('area', '=', $areaFija);
        if (!$incluirEntregados) $queryProcesos->havingRaw("(MAX(palp.area) IS NULL OR MAX(palp.area) <> 'ENTREGADO')");

        // Query 2: Todos los parciales individuales
        $queryParciales = DB::table('pedidos_parciales as ppar')
            ->selectRaw("
                pedidos_procesos_prenda_detalles.id,
                ppar.prenda_pedido_id,
                pedidos_procesos_prenda_detalles.tipo_proceso_id,
                ppar.estado as estado,
                pedidos_procesos_prenda_detalles.numero_recibo,
                pedidos_procesos_prenda_detalles.tipo_recibo,
                pedidos_procesos_prenda_detalles.etiqueta_proceso,
                pedidos_procesos_prenda_detalles.notas_rechazo,
                pedidos_procesos_prenda_detalles.fecha_aprobacion,
                pedidos_procesos_prenda_detalles.aprobado_por,
                pedidos_procesos_prenda_detalles.datos_adicionales,
                pedidos_procesos_prenda_detalles.created_at,
                pedidos_procesos_prenda_detalles.updated_at,
                pedidos_procesos_prenda_detalles.deleted_at,
                MAX(palp.area) as area,
                MAX(palp.novedades) as novedades,
                MAX(palp.fechas_areas) as fechas_areas,
                ppar.consecutivo_actual as numero_recibo_consecutivo,
                crp.id as consecutivo_recibo_id,
                MAX(crp.created_at) as fecha_creacion_recibo,
                ppar.fecha_activacion,
                1 as es_parcial,
                ppar.id as pedido_parcial_id,
                pp.pedido_produccion_id
            ")
            ->join('prendas_pedido as pp', 'pp.id', '=', 'ppar.prenda_pedido_id')
            ->leftJoin('pedidos_produccion as ped', 'ped.id', '=', 'pp.pedido_produccion_id')
            ->leftJoin('clientes as cli', 'cli.id', '=', 'ped.cliente_id')
            ->join('pedidos_procesos_prenda_detalles', function ($join) use ($tipoReciboCase) {
                $join->on('pedidos_procesos_prenda_detalles.prenda_pedido_id', '=', 'pp.id')
                    ->whereRaw("({$tipoReciboCase}) = ppar.tipo_recibo");
            })
            ->join('consecutivos_recibos_pedidos as crp', function ($join) use ($tipoReciboCase) {
                $join->on('crp.pedido_produccion_id', '=', 'pp.pedido_produccion_id')
                    ->on('crp.prenda_id', '=', 'pp.id')
                    ->where('crp.activo', 1)
                    ->whereRaw("crp.tipo_recibo = ({$tipoReciboCase})")
                    ->on('crp.consecutivo_actual', '=', 'ppar.consecutivo_actual');
            })
            ->leftJoin('prenda_areas_logo_pedido as palp', function ($join) {
                $join->on('palp.proceso_prenda_detalle_id', '=', 'pedidos_procesos_prenda_detalles.id')
                     ->on('palp.pedido_parcial_id', '=', 'ppar.id');
            })
            ->whereIn('pedidos_procesos_prenda_detalles.tipo_proceso_id', $tipoProcesoIds)
            ->whereIn('ppar.estado', ['APROBADO', 'COMPLETADO'])
            ->where('ppar.activo', 1)
            ->whereNull('ppar.deleted_at')
            ->groupBy('ppar.id', 'pedidos_procesos_prenda_detalles.id', 'crp.id');

        $this->aplicarBusqueda($queryParciales, $searchTerm, ['ped.cliente', 'cli.nombre', 'crp.consecutivo_actual', 'ppar.consecutivo_actual', 'pedidos_procesos_prenda_detalles.numero_recibo']);
        $this->aplicarFiltrosColumnas($queryParciales, $columnFilters);

        if ($areaFija) $queryParciales->having('area', '=', $areaFija);
        if (!$incluirEntregados) $queryParciales->havingRaw("(MAX(palp.area) IS NULL OR MAX(palp.area) <> 'ENTREGADO')");

        // Combinar queries
        $results = $queryProcesos->unionAll($queryParciales)
            ->orderBy('fecha_creacion_recibo', 'DESC')
            ->orderBy('numero_recibo_consecutivo', 'DESC')
            ->paginate($perPage);

        $results->getCollection()->transform(function ($item) {
            if (is_object($item) && !isset($item->pedido_parcial_id) && isset($item->attributes['pedido_parcial_id'])) {
                $item->pedido_parcial_id = $item->attributes['pedido_parcial_id'];
            }
            return $item;
        });

        return $results;
    }

    private function aplicarBusqueda($query, ?string $searchTerm, array $campos): void
    {
        if (empty($searchTerm)) return;

        $like = '%' . $searchTerm . '%';
        $soloDigitos = preg_replace('/\D+/', '', $searchTerm);
        $soloDigitosNormalizado = $soloDigitos !== '' ? ltrim($soloDigitos, '0') : '';

        $query->where(function ($subQuery) use ($campos, $like, $soloDigitos, $soloDigitosNormalizado, $searchTerm) {
            foreach (array_unique($campos) as $index => $campo) {
                $subQuery->orWhereRaw("LOWER(COALESCE(CONCAT('', {$campo}), '')) LIKE ?", [strtolower($like)]);
                if ($soloDigitos !== '' && $soloDigitos !== $searchTerm) {
                    $subQuery->orWhereRaw("LOWER(COALESCE(CONCAT('', {$campo}), '')) LIKE ?", [strtolower('%' . $soloDigitos . '%')]);
                }
                if ($soloDigitosNormalizado !== '' && $soloDigitosNormalizado !== $soloDigitos) {
                    $subQuery->orWhereRaw("LOWER(COALESCE(CONCAT('', {$campo}), '')) LIKE ?", [strtolower('%' . $soloDigitosNormalizado . '%')]);
                }
            }
        });
    }

    private function normalizarBusqueda(?string $search): ?string
    {
        $search = trim((string) $search);
        return $search === '' ? null : preg_replace('/\s+/', ' ', $search);
    }

    public function obtenerPedidoProduccionIdPorProceso(int $procesoPrendaDetalleId): ?int
    {
        return PedidosProcesosPrendaDetalle::with('prenda.pedidoProduccion')->find($procesoPrendaDetalleId)?->prenda?->pedidoProduccion?->id;
    }

    public function obtenerPrendaPedidoIdPorProceso(int $procesoPrendaDetalleId): ?int
    {
        return PedidosProcesosPrendaDetalle::where('id', $procesoPrendaDetalleId)->value('prenda_pedido_id');
    }

    public function obtenerTipoProcesoIdPorProceso(int $procesoPrendaDetalleId): ?int
    {
        return PedidosProcesosPrendaDetalle::where('id', $procesoPrendaDetalleId)->value('tipo_proceso_id');
    }

    public function obtenerAreasUnicas(array $tipoProcesoIds): array
    {
        $tipoReciboCase = "CASE ppd.tipo_proceso_id WHEN 2 THEN 'BORDADO' WHEN 3 THEN 'ESTAMPADO' WHEN 4 THEN 'DTF' WHEN 5 THEN 'SUBLIMADO' ELSE NULL END";

        $areasProcesos = DB::table('prenda_areas_logo_pedido as palp')
            ->join('pedidos_procesos_prenda_detalles as ppd', 'ppd.id', '=', 'palp.proceso_prenda_detalle_id')
            ->join('prendas_pedido as pp', 'pp.id', '=', 'ppd.prenda_pedido_id')
            ->join('consecutivos_recibos_pedidos as crp', function ($join) use ($tipoReciboCase) {
                $join->on('crp.pedido_produccion_id', '=', 'pp.pedido_produccion_id')
                    ->on('crp.prenda_id', '=', 'pp.id')
                    ->where('crp.activo', 1)
                    ->whereRaw("crp.tipo_recibo = ({$tipoReciboCase})")
                    ->whereRaw("(crp.origen_recibo <> 'ANEXO' OR crp.origen_recibo IS NULL OR crp.origen_recibo = '')");
            })
            ->whereIn('ppd.estado', ['APROBADO', 'COMPLETADO'])
            ->whereNull('palp.pedido_parcial_id')
            ->whereIn('ppd.tipo_proceso_id', $tipoProcesoIds)
            ->whereNotNull('palp.area')
            ->pluck('palp.area')->toArray();

        $areasParciales = DB::table('prenda_areas_logo_pedido as palp')
            ->join('pedidos_parciales as ppar', 'ppar.id', '=', 'palp.pedido_parcial_id')
            ->join('pedidos_procesos_prenda_detalles as ppd', 'ppd.id', '=', 'palp.proceso_prenda_detalle_id')
            ->whereIn('ppar.estado', ['APROBADO', 'COMPLETADO'])
            ->whereIn('ppd.tipo_proceso_id', $tipoProcesoIds)
            ->whereNotNull('palp.area')
            ->pluck('palp.area')->toArray();

        $areas = array_unique(array_merge($areasProcesos, $areasParciales));
        sort($areas);
        return $areas;
    }

    public function obtenerAsesorasUnicas(array $tipoProcesoIds): array
    {
        $tipoReciboCase = "CASE ppd.tipo_proceso_id WHEN 2 THEN 'BORDADO' WHEN 3 THEN 'ESTAMPADO' WHEN 4 THEN 'DTF' WHEN 5 THEN 'SUBLIMADO' ELSE NULL END";

        $asesorasProcesos = DB::table('pedidos_produccion as ped')
            ->join('prendas_pedido as pp', 'pp.pedido_produccion_id', '=', 'ped.id')
            ->join('pedidos_procesos_prenda_detalles as ppd', 'ppd.prenda_pedido_id', '=', 'pp.id')
            ->join('consecutivos_recibos_pedidos as crp', function ($join) use ($tipoReciboCase) {
                $join->on('crp.pedido_produccion_id', '=', 'pp.pedido_produccion_id')
                    ->on('crp.prenda_id', '=', 'pp.id')
                    ->where('crp.activo', 1)
                    ->whereRaw("crp.tipo_recibo = ({$tipoReciboCase})")
                    ->whereRaw("(crp.origen_recibo <> 'ANEXO' OR crp.origen_recibo IS NULL OR crp.origen_recibo = '')");
            })
            ->leftJoin('users as u_asesor', 'u_asesor.id', '=', 'ped.asesor_id')
            ->leftJoin('users as u_asesora', 'u_asesora.id', '=', 'ped.anulado_por_asesora_id')
            ->whereIn('ppd.estado', ['APROBADO', 'COMPLETADO'])
            ->whereIn('ppd.tipo_proceso_id', $tipoProcesoIds)
            ->pluck('u_asesor.name')
            ->merge(
                DB::table('pedidos_produccion as ped')
                    ->join('prendas_pedido as pp', 'pp.pedido_produccion_id', '=', 'ped.id')
                    ->join('pedidos_procesos_prenda_detalles as ppd', 'ppd.prenda_pedido_id', '=', 'pp.id')
                    ->join('consecutivos_recibos_pedidos as crp', function ($join) use ($tipoReciboCase) {
                        $join->on('crp.pedido_produccion_id', '=', 'pp.pedido_produccion_id')
                            ->on('crp.prenda_id', '=', 'pp.id')
                            ->where('crp.activo', 1)
                            ->whereRaw("crp.tipo_recibo = ({$tipoReciboCase})")
                            ->whereRaw("(crp.origen_recibo <> 'ANEXO' OR crp.origen_recibo IS NULL OR crp.origen_recibo = '')");
                    })
                    ->leftJoin('users as u_asesora', 'u_asesora.id', '=', 'ped.anulado_por_asesora_id')
                    ->whereIn('ppd.estado', ['APROBADO', 'COMPLETADO'])
                    ->whereIn('ppd.tipo_proceso_id', $tipoProcesoIds)
                    ->pluck('u_asesora.name')
            )
            ->filter()->unique()->sort()->values()->toArray();

        $asesorasParciales = DB::table('pedidos_parciales as ppar')
            ->join('prendas_pedido as pp', 'pp.id', '=', 'ppar.prenda_pedido_id')
            ->join('pedidos_produccion as ped', 'ped.id', '=', 'pp.pedido_produccion_id')
            ->leftJoin('users as u_asesor', 'u_asesor.id', '=', 'ped.asesor_id')
            ->leftJoin('users as u_asesora', 'u_asesora.id', '=', 'ped.anulado_por_asesora_id')
            ->whereIn('ppar.estado', ['APROBADO', 'COMPLETADO'])
            ->pluck('u_asesor.name')
            ->merge(
                DB::table('pedidos_parciales as ppar')
                    ->join('prendas_pedido as pp', 'pp.id', '=', 'ppar.prenda_pedido_id')
                    ->join('pedidos_produccion as ped', 'ped.id', '=', 'pp.pedido_produccion_id')
                    ->leftJoin('users as u_asesora', 'u_asesora.id', '=', 'ped.anulado_por_asesora_id')
                    ->whereIn('ppar.estado', ['APROBADO', 'COMPLETADO'])
                    ->pluck('u_asesora.name')
            )
            ->filter()->unique()->sort()->values()->toArray();

        $asesoras = array_unique(array_merge($asesorasProcesos, $asesorasParciales));
        sort($asesoras);
        return array_values(array_filter($asesoras));
    }

    public function buscarValoresColumna(string $columna, string $busqueda, array $tipoProcesoIds): array
    {
        $tipoReciboCase = "CASE ppd.tipo_proceso_id WHEN 2 THEN 'BORDADO' WHEN 3 THEN 'ESTAMPADO' WHEN 4 THEN 'DTF' WHEN 5 THEN 'SUBLIMADO' ELSE NULL END";
        $busqueda = trim($busqueda);
        if (empty($busqueda)) return [];

        switch ($columna) {
            case 'cliente':
                $valores = DB::table('pedidos_produccion as ped')
                    ->join('prendas_pedido as pp', 'pp.pedido_produccion_id', '=', 'ped.id')
                    ->join('pedidos_procesos_prenda_detalles as ppd', 'ppd.prenda_pedido_id', '=', 'pp.id')
                    ->join('consecutivos_recibos_pedidos as crp', function ($join) use ($tipoReciboCase) {
                        $join->on('crp.pedido_produccion_id', '=', 'pp.pedido_produccion_id')
                            ->on('crp.prenda_id', '=', 'pp.id')
                            ->where('crp.activo', 1)
                            ->whereRaw("crp.tipo_recibo = ({$tipoReciboCase})")
                            ->whereRaw("(crp.origen_recibo <> 'ANEXO' OR crp.origen_recibo IS NULL OR crp.origen_recibo = '')");
                    })
                    ->whereIn('ppd.estado', ['APROBADO', 'COMPLETADO'])
                    ->whereIn('ppd.tipo_proceso_id', $tipoProcesoIds)
                    ->where('ped.cliente', 'like', '%' . $busqueda . '%')
                    ->pluck('ped.cliente')->unique()->sort()->values()->toArray();

                $valoresParciales = DB::table('pedidos_parciales as ppar')
                    ->join('prendas_pedido as pp', 'pp.id', '=', 'ppar.prenda_pedido_id')
                    ->join('pedidos_produccion as ped', 'ped.id', '=', 'pp.pedido_produccion_id')
                    ->whereIn('ppar.estado', ['APROBADO', 'COMPLETADO'])
                    ->where('ped.cliente', 'like', '%' . $busqueda . '%')
                    ->pluck('ped.cliente')->unique()->sort()->values()->toArray();

                return array_unique(array_merge($valores, $valoresParciales));

            case 'numero_recibo':
                return DB::table('consecutivos_recibos_pedidos as crp')
                    ->join('prendas_pedido as pp', function ($join) { $join->on('crp.pedido_produccion_id', '=', 'pp.pedido_produccion_id')->on('crp.prenda_id', '=', 'pp.id'); })
                    ->join('pedidos_procesos_prenda_detalles as ppd', 'ppd.prenda_pedido_id', '=', 'pp.id')
                    ->where('crp.activo', 1)
                    ->whereIn('ppd.estado', ['APROBADO', 'COMPLETADO'])
                    ->whereIn('ppd.tipo_proceso_id', $tipoProcesoIds)
                    ->where('crp.consecutivo_actual', 'like', '%' . $busqueda . '%')
                    ->pluck('crp.consecutivo_actual')->unique()->sort()->values()->toArray();

            case 'novedades':
                $valores = DB::table('prenda_areas_logo_pedido as palp')
                    ->join('pedidos_procesos_prenda_detalles as ppd', 'ppd.id', '=', 'palp.proceso_prenda_detalle_id')
                    ->join('prendas_pedido as pp', 'pp.id', '=', 'ppd.prenda_pedido_id')
                    ->join('consecutivos_recibos_pedidos as crp', function ($join) use ($tipoReciboCase) {
                        $join->on('crp.pedido_produccion_id', '=', 'pp.pedido_produccion_id')
                            ->on('crp.prenda_id', '=', 'pp.id')
                            ->where('crp.activo', 1)
                            ->whereRaw("crp.tipo_recibo = ({$tipoReciboCase})")
                            ->whereRaw("(crp.origen_recibo <> 'ANEXO' OR crp.origen_recibo IS NULL OR crp.origen_recibo = '')");
                    })
                    ->whereIn('ppd.estado', ['APROBADO', 'COMPLETADO'])
                    ->whereNull('palp.pedido_parcial_id')
                    ->whereIn('ppd.tipo_proceso_id', $tipoProcesoIds)
                    ->where('palp.novedades', 'like', '%' . $busqueda . '%')
                    ->pluck('palp.novedades')->unique()->sort()->values()->toArray();

                $valoresParciales = DB::table('prenda_areas_logo_pedido as palp')
                    ->join('pedidos_parciales as ppar', 'ppar.id', '=', 'palp.pedido_parcial_id')
                    ->join('pedidos_procesos_prenda_detalles as ppd', 'ppd.id', '=', 'palp.proceso_prenda_detalle_id')
                    ->whereIn('ppar.estado', ['APROBADO', 'COMPLETADO'])
                    ->whereIn('ppd.tipo_proceso_id', $tipoProcesoIds)
                    ->where('palp.novedades', 'like', '%' . $busqueda . '%')
                    ->pluck('palp.novedades')->unique()->sort()->values()->toArray();

                return array_unique(array_merge($valores, $valoresParciales));

            default:
                return [];
        }
    }

    private function aplicarFiltrosColumnas($query, ?array $columnFilters): void
    {
        if (empty($columnFilters)) return;

        foreach ($columnFilters as $column => $values) {
            if (empty($values) || !is_array($values)) continue;

            $query->where(function ($q) use ($column, $values) {
                foreach ($values as $value) {
                    switch ($column) {
                        case 'area': $q->orWhere('palp.area', $value); break;
                        case 'cliente': $q->orWhere('ped.cliente', 'like', '%' . $value . '%')->orWhere('cli.nombre', 'like', '%' . $value . '%'); break;
                        case 'numero_recibo': $q->orWhere('crp.consecutivo_actual', 'like', '%' . $value . '%'); break;
                        case 'asesora':
                            $q->orWhereExists(function ($subQ) use ($value) {
                                $subQ->select(DB::raw(1))->from('users as u')->whereColumn('u.id', 'ped.asesor_id')->where('u.name', 'like', '%' . $value . '%');
                            })->orWhereExists(function ($subQ) use ($value) {
                                $subQ->select(DB::raw(1))->from('users as u')->whereColumn('u.id', 'ped.anulado_por_asesora_id')->where('u.name', 'like', '%' . $value . '%');
                            });
                            break;
                        case 'novedades': $q->orWhere('palp.novedades', 'like', '%' . $value . '%'); break;
                    }
                }
            });
        }
    }
}
