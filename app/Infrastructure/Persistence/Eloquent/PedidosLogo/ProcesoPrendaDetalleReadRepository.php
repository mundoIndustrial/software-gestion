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
            . "WHEN 1 THEN 'REFLECTIVO' WHEN 2 THEN 'BORDADO' "
            . "WHEN 3 THEN 'ESTAMPADO' "
            . "WHEN 4 THEN 'DTF' "
            . "WHEN 5 THEN 'SUBLIMADO' "
            . "ELSE NULL END";
        $tipoProcesoCaseFromCrp = "CASE UPPER(TRIM(crp.tipo_recibo)) "
            . "WHEN 'REFLECTIVO' THEN 1 WHEN 'BORDADO' THEN 2 "
            . "WHEN 'ESTAMPADO' THEN 3 "
            . "WHEN 'DTF' THEN 4 "
            . "WHEN 'SUBLIMADO' THEN 5 "
            . "ELSE NULL END";
        $tipoProcesoCaseFromPpar = "CASE UPPER(TRIM(ppar.tipo_recibo)) "
            . "WHEN 'REFLECTIVO' THEN 1 WHEN 'BORDADO' THEN 2 "
            . "WHEN 'ESTAMPADO' THEN 3 "
            . "WHEN 'DTF' THEN 4 "
            . "WHEN 'SUBLIMADO' THEN 5 "
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
        $this->aplicarFiltrosColumnas($queryProcesos, $columnFilters, [
            'area' => 'palp.area',
            'novedades' => 'palp.novedades',
            'numero_recibo' => 'crp.consecutivo_actual',
        ]);

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
        $this->aplicarFiltrosColumnas($queryParciales, $columnFilters, [
            'area' => 'palp.area',
            'novedades' => 'palp.novedades',
            'numero_recibo' => 'crp.consecutivo_actual',
        ]);

        if ($areaFija) $queryParciales->having('area', '=', $areaFija);
        if (!$incluirEntregados) $queryParciales->havingRaw("(MAX(palp.area) IS NULL OR MAX(palp.area) <> 'ENTREGADO')");

        // Query 2B (fallback): parciales activos sin proceso técnico asociado.
        // Permite listar recibos creados manualmente solo en pedidos_parciales.
        $queryParcialesSinProceso = DB::table('pedidos_parciales as ppar')
            ->selectRaw("
                COALESCE(MIN(ppd_any.id), 0) as id,
                ppar.prenda_pedido_id,
                {$tipoProcesoCaseFromPpar} as tipo_proceso_id,
                ppar.estado as estado,
                ppar.consecutivo_actual as numero_recibo,
                UPPER(TRIM(ppar.tipo_recibo)) as tipo_recibo,
                NULL as etiqueta_proceso,
                NULL as notas_rechazo,
                NULL as fecha_aprobacion,
                NULL as aprobado_por,
                NULL as datos_adicionales,
                COALESCE(MIN(ppd_any.created_at), ppar.created_at) as created_at,
                COALESCE(MIN(ppd_any.updated_at), ppar.updated_at) as updated_at,
                NULL as deleted_at,
                COALESCE(MAX(palp_any.area), 'PENDIENTE') as area,
                MAX(palp_any.novedades) as novedades,
                MAX(palp_any.fechas_areas) as fechas_areas,
                ppar.consecutivo_actual as numero_recibo_consecutivo,
                crp.id as consecutivo_recibo_id,
                COALESCE(MAX(crp.created_at), ppar.created_at) as fecha_creacion_recibo,
                ppar.fecha_activacion,
                1 as es_parcial,
                ppar.id as pedido_parcial_id,
                pp.pedido_produccion_id
            ")
            ->join('prendas_pedido as pp', 'pp.id', '=', 'ppar.prenda_pedido_id')
            ->leftJoin('pedidos_produccion as ped', 'ped.id', '=', 'pp.pedido_produccion_id')
            ->leftJoin('clientes as cli', 'cli.id', '=', 'ped.cliente_id')
            ->leftJoin('pedidos_procesos_prenda_detalles as ppd_any', 'ppd_any.prenda_pedido_id', '=', 'pp.id')
            ->leftJoin('prenda_areas_logo_pedido as palp_any', function ($join) {
                $join->on('palp_any.proceso_prenda_detalle_id', '=', 'ppd_any.id')
                    ->on('palp_any.pedido_parcial_id', '=', 'ppar.id');
            })
            ->leftJoin('consecutivos_recibos_pedidos as crp', function ($join) {
                $join->on('crp.pedido_produccion_id', '=', 'pp.pedido_produccion_id')
                    ->on('crp.prenda_id', '=', 'pp.id')
                    ->on('crp.consecutivo_actual', '=', 'ppar.consecutivo_actual')
                    ->where('crp.activo', 1);
            })
            ->whereIn('ppar.estado', ['APROBADO', 'COMPLETADO'])
            ->where('ppar.activo', 1)
            ->whereNull('ppar.deleted_at')
            ->whereRaw("{$tipoProcesoCaseFromPpar} IN (" . implode(',', array_map('intval', $tipoProcesoIds)) . ")")
            ->whereNotExists(function ($sub) use ($tipoProcesoCaseFromPpar) {
                $sub->select(DB::raw(1))
                    ->from('pedidos_procesos_prenda_detalles as ppd')
                    ->whereColumn('ppd.prenda_pedido_id', 'pp.id')
                    ->whereColumn('ppd.tipo_proceso_id', DB::raw($tipoProcesoCaseFromPpar));
            })
            ->groupBy('ppar.id', 'ppar.prenda_pedido_id', 'pp.pedido_produccion_id', 'ppar.consecutivo_actual', 'ppar.tipo_recibo', 'ppar.estado', 'ppar.created_at', 'ppar.updated_at', 'ppar.fecha_activacion', 'crp.id');

        $this->aplicarBusqueda($queryParcialesSinProceso, $searchTerm, ['ped.cliente', 'cli.nombre', 'ppar.consecutivo_actual']);
        $this->aplicarFiltrosColumnas($queryParcialesSinProceso, $columnFilters, [
            'area' => 'palp_any.area',
            'novedades' => 'palp_any.novedades',
            'numero_recibo' => 'ppar.consecutivo_actual',
        ]);

        if ($areaFija) $queryParcialesSinProceso->having('area', '=', $areaFija);
        if (!$incluirEntregados) $queryParcialesSinProceso->havingRaw("(COALESCE(MAX(palp_any.area), 'PENDIENTE') <> 'ENTREGADO')");

        // Query 3 (fallback): recibos activos en consecutivos sin proceso técnico asociado.
        // Aplica para TODOS los tipos logo (BORDADO/ESTAMPADO/DTF/SUBLIMADO).
        $queryCrpSinProceso = DB::table('consecutivos_recibos_pedidos as crp')
            ->selectRaw("
                COALESCE(MIN(ppd_any.id), 0) as id,
                pp.id as prenda_pedido_id,
                {$tipoProcesoCaseFromCrp} as tipo_proceso_id,
                'APROBADO' as estado,
                crp.consecutivo_actual as numero_recibo,
                UPPER(TRIM(crp.tipo_recibo)) as tipo_recibo,
                NULL as etiqueta_proceso,
                NULL as notas_rechazo,
                NULL as fecha_aprobacion,
                NULL as aprobado_por,
                NULL as datos_adicionales,
                COALESCE(MIN(ppd_any.created_at), crp.created_at) as created_at,
                COALESCE(MIN(ppd_any.updated_at), crp.updated_at) as updated_at,
                NULL as deleted_at,
                COALESCE(MAX(palp_any.area), 'PENDIENTE') as area,
                MAX(palp_any.novedades) as novedades,
                MAX(palp_any.fechas_areas) as fechas_areas,
                crp.consecutivo_actual as numero_recibo_consecutivo,
                crp.id as consecutivo_recibo_id,
                crp.created_at as fecha_creacion_recibo,
                NULL as fecha_activacion,
                0 as es_parcial,
                NULL as pedido_parcial_id,
                pp.pedido_produccion_id
            ")
            ->join('prendas_pedido as pp', function ($join) {
                $join->on('pp.id', '=', 'crp.prenda_id')
                    ->on('pp.pedido_produccion_id', '=', 'crp.pedido_produccion_id');
            })
            ->leftJoin('pedidos_produccion as ped', 'ped.id', '=', 'pp.pedido_produccion_id')
            ->leftJoin('clientes as cli', 'cli.id', '=', 'ped.cliente_id')
            ->leftJoin('pedidos_procesos_prenda_detalles as ppd_any', 'ppd_any.prenda_pedido_id', '=', 'pp.id')
            ->leftJoin('prenda_areas_logo_pedido as palp_any', function ($join) {
                $join->on('palp_any.proceso_prenda_detalle_id', '=', 'ppd_any.id')
                    ->whereNull('palp_any.pedido_parcial_id');
            })
            ->where('crp.activo', 1)
            ->whereRaw("{$tipoProcesoCaseFromCrp} IN (" . implode(',', array_map('intval', $tipoProcesoIds)) . ")")
            ->whereRaw("(crp.origen_recibo <> 'ANEXO' OR crp.origen_recibo IS NULL OR crp.origen_recibo = '')")
            ->whereNotExists(function ($sub) use ($tipoProcesoCaseFromCrp) {
                $sub->select(DB::raw(1))
                    ->from('pedidos_procesos_prenda_detalles as ppd')
                    ->whereColumn('ppd.prenda_pedido_id', 'pp.id')
                    ->whereColumn('ppd.tipo_proceso_id', DB::raw($tipoProcesoCaseFromCrp));
            })
            ->groupBy('crp.id', 'pp.id', 'pp.pedido_produccion_id', 'crp.consecutivo_actual', 'crp.created_at', 'crp.updated_at', 'crp.tipo_recibo');

        $this->aplicarBusqueda($queryCrpSinProceso, $searchTerm, ['ped.cliente', 'cli.nombre', 'crp.consecutivo_actual']);
        $this->aplicarFiltrosColumnas($queryCrpSinProceso, $columnFilters, [
            'area' => 'palp_any.area',
            'novedades' => 'palp_any.novedades',
            'numero_recibo' => 'crp.consecutivo_actual',
        ]);

        if ($areaFija) $queryCrpSinProceso->having('area', '=', $areaFija);
        if (!$incluirEntregados) $queryCrpSinProceso->havingRaw("(COALESCE(MAX(palp_any.area), 'PENDIENTE') <> 'ENTREGADO')");

        // Combinar queries
        $results = $queryProcesos
            ->unionAll($queryParciales)
            ->unionAll($queryParcialesSinProceso)
            ->unionAll($queryCrpSinProceso)
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
                // Para número de recibo, hacer búsqueda exacta
                if (strpos($campo, 'numero_recibo') !== false || strpos($campo, 'consecutivo_actual') !== false) {
                    $subQuery->orWhereRaw("LOWER(COALESCE(CONCAT('', {$campo}), '')) = ?", [strtolower($searchTerm)]);
                    if ($soloDigitos !== '' && $soloDigitos !== $searchTerm) {
                        $subQuery->orWhereRaw("LOWER(COALESCE(CONCAT('', {$campo}), '')) = ?", [strtolower($soloDigitos)]);
                    }
                    if ($soloDigitosNormalizado !== '' && $soloDigitosNormalizado !== $soloDigitos) {
                        $subQuery->orWhereRaw("LOWER(COALESCE(CONCAT('', {$campo}), '')) = ?", [strtolower($soloDigitosNormalizado)]);
                    }
                } else {
                    // Para otros campos, mantener búsqueda parcial
                    $subQuery->orWhereRaw("LOWER(COALESCE(CONCAT('', {$campo}), '')) LIKE ?", [strtolower($like)]);
                    if ($soloDigitos !== '' && $soloDigitos !== $searchTerm) {
                        $subQuery->orWhereRaw("LOWER(COALESCE(CONCAT('', {$campo}), '')) LIKE ?", [strtolower('%' . $soloDigitos . '%')]);
                    }
                    if ($soloDigitosNormalizado !== '' && $soloDigitosNormalizado !== $soloDigitos) {
                        $subQuery->orWhereRaw("LOWER(COALESCE(CONCAT('', {$campo}), '')) LIKE ?", [strtolower('%' . $soloDigitosNormalizado . '%')]);
                    }
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
        $tipoReciboCase = "CASE ppd.tipo_proceso_id WHEN 1 THEN 'REFLECTIVO' WHEN 2 THEN 'BORDADO' WHEN 3 THEN 'ESTAMPADO' WHEN 4 THEN 'DTF' WHEN 5 THEN 'SUBLIMADO' ELSE NULL END";

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
        $tipoReciboCase = "CASE ppd.tipo_proceso_id WHEN 1 THEN 'REFLECTIVO' WHEN 2 THEN 'BORDADO' WHEN 3 THEN 'ESTAMPADO' WHEN 4 THEN 'DTF' WHEN 5 THEN 'SUBLIMADO' ELSE NULL END";

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
        $tipoReciboCase = "CASE ppd.tipo_proceso_id WHEN 1 THEN 'REFLECTIVO' WHEN 2 THEN 'BORDADO' WHEN 3 THEN 'ESTAMPADO' WHEN 4 THEN 'DTF' WHEN 5 THEN 'SUBLIMADO' ELSE NULL END";
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

    private function aplicarFiltrosColumnas($query, ?array $columnFilters, array $columnMap = []): void
    {
        if (empty($columnFilters)) return;

        $areaColumn = $columnMap['area'] ?? 'palp.area';
        $novedadesColumn = $columnMap['novedades'] ?? 'palp.novedades';
        $numeroReciboColumn = $columnMap['numero_recibo'] ?? 'crp.consecutivo_actual';

        foreach ($columnFilters as $column => $values) {
            if (empty($values) || !is_array($values)) continue;

            $query->where(function ($q) use ($column, $values, $areaColumn, $novedadesColumn, $numeroReciboColumn) {
                foreach ($values as $value) {
                    switch ($column) {
                        case 'area': $q->orWhere($areaColumn, $value); break;
                        case 'cliente': $q->orWhere('ped.cliente', 'like', '%' . $value . '%')->orWhere('cli.nombre', 'like', '%' . $value . '%'); break;
                        case 'numero_recibo': $q->orWhere($numeroReciboColumn, 'like', '%' . $value . '%'); break;
                        case 'asesora':
                            $q->orWhereExists(function ($subQ) use ($value) {
                                $subQ->select(DB::raw(1))->from('users as u')->whereColumn('u.id', 'ped.asesor_id')->where('u.name', 'like', '%' . $value . '%');
                            })->orWhereExists(function ($subQ) use ($value) {
                                $subQ->select(DB::raw(1))->from('users as u')->whereColumn('u.id', 'ped.anulado_por_asesora_id')->where('u.name', 'like', '%' . $value . '%');
                            });
                            break;
                        case 'novedades': $q->orWhere($novedadesColumn, 'like', '%' . $value . '%'); break;
                    }
                }
            });
        }
    }
}

