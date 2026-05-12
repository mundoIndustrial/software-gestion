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

        // Query 1: Procesos base sin parciales
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
                    ->whereRaw("crp.tipo_recibo = ({$tipoReciboCase})");
            })
            ->leftJoin('pedidos_parciales as ppar', function ($join) use ($tipoReciboCase) {
                $join->on('ppar.pedido_produccion_id', '=', 'pp.pedido_produccion_id')
                    ->on('ppar.prenda_pedido_id', '=', 'pp.id')
                    ->where('ppar.estado', 'APROBADO')
                    ->where('ppar.activo', 1)
                    ->whereNull('ppar.deleted_at')
                    ->whereRaw("ppar.tipo_recibo = ({$tipoReciboCase})");
            })
            ->where('pedidos_procesos_prenda_detalles.estado', 'APROBADO')
            ->where(function ($q) {
                $q->whereNotNull('crp.consecutivo_actual')
                  ->orWhereNotNull('pedidos_procesos_prenda_detalles.numero_recibo');
            })
            ->whereNull('ppar.id')
            ->whereIn('pedidos_procesos_prenda_detalles.tipo_proceso_id', $tipoProcesoIds)
            ->groupBy('pedidos_procesos_prenda_detalles.id', 'crp.consecutivo_actual', 'crp.created_at', 'pp.pedido_produccion_id');

        $this->aplicarBusqueda(
            $queryProcesos,
            $searchTerm,
            [
                'ped.cliente',
                'cli.nombre',
                'crp.consecutivo_actual',
                'pedidos_procesos_prenda_detalles.numero_recibo',
            ]
        );

        // Aplicar filtros de columnas
        $this->aplicarFiltrosColumnas($queryProcesos, $columnFilters);

        // Aplicar filtro de área fija si se proporciona
        if ($areaFija) {
            $queryProcesos->having('area', '=', $areaFija);
        }
        if (!$incluirEntregados) {
            $queryProcesos->havingRaw("(MAX(palp.area) IS NULL OR MAX(palp.area) <> 'ENTREGADO')");
        }

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
                    ->whereRaw("crp.tipo_recibo = ({$tipoReciboCase})");
            })
            ->leftJoin('prenda_areas_logo_pedido as palp', function ($join) {
                $join->on('palp.proceso_prenda_detalle_id', '=', 'pedidos_procesos_prenda_detalles.id')
                     ->on('palp.pedido_parcial_id', '=', 'ppar.id');
            })
            ->whereIn('pedidos_procesos_prenda_detalles.tipo_proceso_id', $tipoProcesoIds)
            ->where('ppar.estado', 'APROBADO')
            ->where('ppar.activo', 1)
            ->whereNull('ppar.deleted_at')
            ->groupBy('ppar.id', 'pedidos_procesos_prenda_detalles.id');

        $this->aplicarBusqueda(
            $queryParciales,
            $searchTerm,
            [
                'ped.cliente',
                'cli.nombre',
                'crp.consecutivo_actual',
                'ppar.consecutivo_actual',
                'pedidos_procesos_prenda_detalles.numero_recibo',
            ]
        );

        // Aplicar filtros de columnas
        $this->aplicarFiltrosColumnas($queryParciales, $columnFilters);

        // Aplicar filtro de área fija si se proporciona
        if ($areaFija) {
            $queryParciales->having('area', '=', $areaFija);
        }
        if (!$incluirEntregados) {
            $queryParciales->havingRaw("(MAX(palp.area) IS NULL OR MAX(palp.area) <> 'ENTREGADO')");
        }

        // Combinar queries
        $results = $queryProcesos->unionAll($queryParciales)
            ->orderBy('fecha_creacion_recibo', 'DESC')
            ->orderBy('numero_recibo_consecutivo', 'DESC')
            ->paginate($perPage);

        // Map items to ensure all attributes are available
        $results->getCollection()->transform(function ($item) {
            if (is_object($item)) {
                // Ensure pedido_parcial_id is accessible as an attribute
                if (!isset($item->pedido_parcial_id) && isset($item->attributes['pedido_parcial_id'])) {
                    $item->pedido_parcial_id = $item->attributes['pedido_parcial_id'];
                }
            }
            return $item;
        });

        return $results;
    }

    private function aplicarBusqueda($query, ?string $searchTerm, array $campos): void
    {
        if ($searchTerm === null || $searchTerm === '') {
            return;
        }

        $like = '%' . $searchTerm . '%';
        $soloDigitos = preg_replace('/\D+/', '', $searchTerm);
        $soloDigitosNormalizado = $soloDigitos !== '' ? ltrim($soloDigitos, '0') : '';
        if ($soloDigitosNormalizado === '') {
            $soloDigitosNormalizado = $soloDigitos;
        }

        $query->where(function ($subQuery) use ($campos, $like, $soloDigitos, $soloDigitosNormalizado, $searchTerm) {
            $this->agregarCondicionesBusqueda($subQuery, $campos, $like);

            if ($soloDigitos !== '' && $soloDigitos !== $searchTerm) {
                $this->agregarCondicionesBusqueda($subQuery, $campos, '%' . $soloDigitos . '%', true);
            }

            if ($soloDigitosNormalizado !== '' && $soloDigitosNormalizado !== $soloDigitos) {
                $this->agregarCondicionesBusqueda($subQuery, $campos, '%' . $soloDigitosNormalizado . '%', true);
            }
        });
    }

    private function agregarCondicionesBusqueda($query, array $campos, string $like, bool $usarOr = false): void
    {
        foreach (array_values(array_unique($campos)) as $index => $campo) {
            $method = $usarOr || $index > 0 ? 'orWhereRaw' : 'whereRaw';
            $query->$method("LOWER(COALESCE(CONCAT('', {$campo}), '')) LIKE ?", [strtolower($like)]);
        }
    }

    private function normalizarBusqueda(?string $search): ?string
    {
        $search = trim((string) $search);

        if ($search === '') {
            return null;
        }

        return preg_replace('/\s+/', ' ', $search);
    }

    public function obtenerPedidoProduccionIdPorProceso(int $procesoPrendaDetalleId): ?int
    {
        $proceso = PedidosProcesosPrendaDetalle::with('prenda.pedidoProduccion')
            ->select(['id', 'prenda_pedido_id', 'tipo_proceso_id'])
            ->find($procesoPrendaDetalleId);

        return $proceso?->prenda?->pedidoProduccion?->id;
    }

    public function obtenerPrendaPedidoIdPorProceso(int $procesoPrendaDetalleId): ?int
    {
        return PedidosProcesosPrendaDetalle::query()
            ->where('id', $procesoPrendaDetalleId)
            ->value('prenda_pedido_id');
    }

    public function obtenerTipoProcesoIdPorProceso(int $procesoPrendaDetalleId): ?int
    {
        return PedidosProcesosPrendaDetalle::query()
            ->where('id', $procesoPrendaDetalleId)
            ->value('tipo_proceso_id');
    }

    public function obtenerAreasUnicas(array $tipoProcesoIds): array
    {
        $tipoReciboCase = "CASE ppd.tipo_proceso_id "
            . "WHEN 2 THEN 'BORDADO' "
            . "WHEN 3 THEN 'ESTAMPADO' "
            . "WHEN 4 THEN 'DTF' "
            . "WHEN 5 THEN 'SUBLIMADO' "
            . "ELSE NULL END";

        // Obtener áreas de procesos base (sin parciales)
        $areasProcesos = DB::table('prenda_areas_logo_pedido as palp')
            ->join('pedidos_procesos_prenda_detalles as ppd', 'ppd.id', '=', 'palp.proceso_prenda_detalle_id')
            ->join('prendas_pedido as pp', 'pp.id', '=', 'ppd.prenda_pedido_id')
            ->join('pedidos_produccion as ped', 'ped.id', '=', 'pp.pedido_produccion_id')
            ->join('consecutivos_recibos_pedidos as crp', function ($join) use ($tipoReciboCase) {
                $join->on('crp.pedido_produccion_id', '=', 'pp.pedido_produccion_id')
                    ->on('crp.prenda_id', '=', 'pp.id')
                    ->where('crp.activo', 1)
                    ->whereRaw("crp.tipo_recibo = ({$tipoReciboCase})");
            })
            ->leftJoin('pedidos_parciales as ppar', function ($join) use ($tipoReciboCase) {
                $join->on('ppar.pedido_produccion_id', '=', 'pp.pedido_produccion_id')
                    ->on('ppar.prenda_pedido_id', '=', 'pp.id')
                    ->where('ppar.estado', 'APROBADO')
                    ->where('ppar.activo', 1)
                    ->whereNull('ppar.deleted_at')
                    ->whereRaw("ppar.tipo_recibo = ({$tipoReciboCase})");
            })
            ->where('ppd.estado', 'APROBADO')
            ->whereNotNull('crp.consecutivo_actual')
            ->whereNull('palp.pedido_parcial_id')
            ->whereNull('ppar.id')
            ->whereIn('ppd.tipo_proceso_id', $tipoProcesoIds)
            ->whereNotNull('palp.area')
            ->pluck('palp.area')
            ->toArray();

        // Obtener áreas de parciales
        $areasParciales = DB::table('prenda_areas_logo_pedido as palp')
            ->join('pedidos_parciales as ppar', 'ppar.id', '=', 'palp.pedido_parcial_id')
            ->join('prendas_pedido as pp', 'pp.id', '=', 'ppar.prenda_pedido_id')
            ->join('pedidos_procesos_prenda_detalles as ppd', 'ppd.id', '=', 'palp.proceso_prenda_detalle_id')
            ->where('ppar.estado', 'APROBADO')
            ->where('ppar.activo', 1)
            ->whereNull('ppar.deleted_at')
            ->whereIn('ppd.tipo_proceso_id', $tipoProcesoIds)
            ->whereNotNull('palp.area')
            ->pluck('palp.area')
            ->toArray();

        // Combinar y eliminar duplicados
        $areas = array_unique(array_merge($areasProcesos, $areasParciales));
        sort($areas);

        return $areas;
    }

    public function obtenerAsesorasUnicas(array $tipoProcesoIds): array
    {
        $tipoReciboCase = "CASE ppd.tipo_proceso_id "
            . "WHEN 2 THEN 'BORDADO' "
            . "WHEN 3 THEN 'ESTAMPADO' "
            . "WHEN 4 THEN 'DTF' "
            . "WHEN 5 THEN 'SUBLIMADO' "
            . "ELSE NULL END";

        // Obtener asesoras de procesos base (sin parciales)
        $asesorasProcesos = DB::table('pedidos_produccion as ped')
            ->join('prendas_pedido as pp', 'pp.pedido_produccion_id', '=', 'ped.id')
            ->join('pedidos_procesos_prenda_detalles as ppd', 'ppd.prenda_pedido_id', '=', 'pp.id')
            ->join('consecutivos_recibos_pedidos as crp', function ($join) use ($tipoReciboCase) {
                $join->on('crp.pedido_produccion_id', '=', 'pp.pedido_produccion_id')
                    ->on('crp.prenda_id', '=', 'pp.id')
                    ->where('crp.activo', 1)
                    ->whereRaw("crp.tipo_recibo = ({$tipoReciboCase})");
            })
            ->leftJoin('pedidos_parciales as ppar', function ($join) use ($tipoReciboCase) {
                $join->on('ppar.pedido_produccion_id', '=', 'pp.pedido_produccion_id')
                    ->on('ppar.prenda_pedido_id', '=', 'pp.id')
                    ->where('ppar.estado', 'APROBADO')
                    ->where('ppar.activo', 1)
                    ->whereNull('ppar.deleted_at')
                    ->whereRaw("ppar.tipo_recibo = ({$tipoReciboCase})");
            })
            ->leftJoin('users as u_asesor', 'u_asesor.id', '=', 'ped.asesor_id')
            ->leftJoin('users as u_asesora', 'u_asesora.id', '=', 'ped.anulado_por_asesora_id')
            ->where('ppd.estado', 'APROBADO')
            ->whereNotNull('crp.consecutivo_actual')
            ->whereNull('ppar.id')
            ->whereIn('ppd.tipo_proceso_id', $tipoProcesoIds)
            ->where(function ($q) {
                $q->whereNotNull('ped.asesor_id')
                  ->orWhereNotNull('ped.anulado_por_asesora_id');
            })
            ->pluck('u_asesor.name')
            ->merge(
                DB::table('pedidos_produccion as ped')
                    ->join('prendas_pedido as pp', 'pp.pedido_produccion_id', '=', 'ped.id')
                    ->join('pedidos_procesos_prenda_detalles as ppd', 'ppd.prenda_pedido_id', '=', 'pp.id')
                    ->join('consecutivos_recibos_pedidos as crp', function ($join) use ($tipoReciboCase) {
                        $join->on('crp.pedido_produccion_id', '=', 'pp.pedido_produccion_id')
                            ->on('crp.prenda_id', '=', 'pp.id')
                            ->where('crp.activo', 1)
                            ->whereRaw("crp.tipo_recibo = ({$tipoReciboCase})");
                    })
                    ->leftJoin('pedidos_parciales as ppar', function ($join) use ($tipoReciboCase) {
                        $join->on('ppar.pedido_produccion_id', '=', 'pp.pedido_produccion_id')
                            ->on('ppar.prenda_pedido_id', '=', 'pp.id')
                            ->where('ppar.estado', 'APROBADO')
                            ->where('ppar.activo', 1)
                            ->whereNull('ppar.deleted_at')
                            ->whereRaw("ppar.tipo_recibo = ({$tipoReciboCase})");
                    })
                    ->leftJoin('users as u_asesora', 'u_asesora.id', '=', 'ped.anulado_por_asesora_id')
                    ->where('ppd.estado', 'APROBADO')
                    ->whereNotNull('crp.consecutivo_actual')
                    ->whereNull('ppar.id')
                    ->whereIn('ppd.tipo_proceso_id', $tipoProcesoIds)
                    ->whereNotNull('ped.anulado_por_asesora_id')
                    ->pluck('u_asesora.name')
            )
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->toArray();

        // Obtener asesoras de parciales
        $asesorasParciales = DB::table('pedidos_parciales as ppar')
            ->join('prendas_pedido as pp', 'pp.id', '=', 'ppar.prenda_pedido_id')
            ->join('pedidos_produccion as ped', 'ped.id', '=', 'pp.pedido_produccion_id')
            ->join('pedidos_procesos_prenda_detalles as ppd', 'ppd.prenda_pedido_id', '=', 'pp.id')
            ->leftJoin('users as u_asesor', 'u_asesor.id', '=', 'ped.asesor_id')
            ->leftJoin('users as u_asesora', 'u_asesora.id', '=', 'ped.anulado_por_asesora_id')
            ->where('ppar.estado', 'APROBADO')
            ->where('ppar.activo', 1)
            ->whereNull('ppar.deleted_at')
            ->whereIn('ppd.tipo_proceso_id', $tipoProcesoIds)
            ->where(function ($q) {
                $q->whereNotNull('ped.asesor_id')
                  ->orWhereNotNull('ped.anulado_por_asesora_id');
            })
            ->pluck('u_asesor.name')
            ->merge(
                DB::table('pedidos_parciales as ppar')
                    ->join('prendas_pedido as pp', 'pp.id', '=', 'ppar.prenda_pedido_id')
                    ->join('pedidos_produccion as ped', 'ped.id', '=', 'pp.pedido_produccion_id')
                    ->join('pedidos_procesos_prenda_detalles as ppd', 'ppd.prenda_pedido_id', '=', 'pp.id')
                    ->leftJoin('users as u_asesora', 'u_asesora.id', '=', 'ped.anulado_por_asesora_id')
                    ->where('ppar.estado', 'APROBADO')
                    ->where('ppar.activo', 1)
                    ->whereNull('ppar.deleted_at')
                    ->whereIn('ppd.tipo_proceso_id', $tipoProcesoIds)
                    ->whereNotNull('ped.anulado_por_asesora_id')
                    ->pluck('u_asesora.name')
            )
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->toArray();

        // Combinar y eliminar duplicados
        $asesoras = array_unique(array_merge($asesorasProcesos, $asesorasParciales));
        sort($asesoras);

        return array_values(array_filter($asesoras));
    }

    public function buscarValoresColumna(string $columna, string $busqueda, array $tipoProcesoIds): array
    {
        $tipoReciboCase = "CASE ppd.tipo_proceso_id "
            . "WHEN 2 THEN 'BORDADO' "
            . "WHEN 3 THEN 'ESTAMPADO' "
            . "WHEN 4 THEN 'DTF' "
            . "WHEN 5 THEN 'SUBLIMADO' "
            . "ELSE NULL END";

        $busqueda = trim($busqueda);
        if (empty($busqueda)) {
            return [];
        }

        switch ($columna) {
            case 'cliente':
                $valores = DB::table('pedidos_produccion as ped')
                    ->join('prendas_pedido as pp', 'pp.pedido_produccion_id', '=', 'ped.id')
                    ->join('pedidos_procesos_prenda_detalles as ppd', 'ppd.prenda_pedido_id', '=', 'pp.id')
                    ->join('consecutivos_recibos_pedidos as crp', function ($join) use ($tipoReciboCase) {
                        $join->on('crp.pedido_produccion_id', '=', 'pp.pedido_produccion_id')
                            ->on('crp.prenda_id', '=', 'pp.id')
                            ->where('crp.activo', 1)
                            ->whereRaw("crp.tipo_recibo = ({$tipoReciboCase})");
                    })
                    ->leftJoin('pedidos_parciales as ppar', function ($join) use ($tipoReciboCase) {
                        $join->on('ppar.pedido_produccion_id', '=', 'pp.pedido_produccion_id')
                            ->on('ppar.prenda_pedido_id', '=', 'pp.id')
                            ->where('ppar.estado', 'APROBADO')
                            ->where('ppar.activo', 1)
                            ->whereNull('ppar.deleted_at')
                            ->whereRaw("ppar.tipo_recibo = ({$tipoReciboCase})");
                    })
                    ->where('ppd.estado', 'APROBADO')
                    ->whereNotNull('crp.consecutivo_actual')
                    ->whereNull('ppar.id')
                    ->whereIn('ppd.tipo_proceso_id', $tipoProcesoIds)
                    ->where(function ($q) use ($busqueda) {
                        $q->where('ped.cliente', 'like', '%' . $busqueda . '%');
                    })
                    ->pluck('ped.cliente')
                    ->unique()
                    ->sort()
                    ->values()
                    ->toArray();

                // Agregar clientes de parciales
                $valoresParciales = DB::table('pedidos_parciales as ppar')
                    ->join('prendas_pedido as pp', 'pp.id', '=', 'ppar.prenda_pedido_id')
                    ->join('pedidos_produccion as ped', 'ped.id', '=', 'pp.pedido_produccion_id')
                    ->join('pedidos_procesos_prenda_detalles as ppd', 'ppd.prenda_pedido_id', '=', 'pp.id')
                    ->where('ppar.estado', 'APROBADO')
                    ->where('ppar.activo', 1)
                    ->whereNull('ppar.deleted_at')
                    ->whereIn('ppd.tipo_proceso_id', $tipoProcesoIds)
                    ->where('ped.cliente', 'like', '%' . $busqueda . '%')
                    ->pluck('ped.cliente')
                    ->unique()
                    ->sort()
                    ->values()
                    ->toArray();

                return array_unique(array_merge($valores, $valoresParciales));

            case 'numero_recibo':
                $valores = DB::table('consecutivos_recibos_pedidos as crp')
                    ->join('prendas_pedido as pp', function ($join) {
                        $join->on('crp.pedido_produccion_id', '=', 'pp.pedido_produccion_id')
                            ->on('crp.prenda_id', '=', 'pp.id');
                    })
                    ->join('pedidos_procesos_prenda_detalles as ppd', 'ppd.prenda_pedido_id', '=', 'pp.id')
                    ->where('crp.activo', 1)
                    ->where('ppd.estado', 'APROBADO')
                    ->whereIn('ppd.tipo_proceso_id', $tipoProcesoIds)
                    ->where('crp.consecutivo_actual', 'like', '%' . $busqueda . '%')
                    ->pluck('crp.consecutivo_actual')
                    ->unique()
                    ->sort()
                    ->values()
                    ->toArray();

                return $valores;

            case 'novedades':
                $valores = DB::table('prenda_areas_logo_pedido as palp')
                    ->join('pedidos_procesos_prenda_detalles as ppd', 'ppd.id', '=', 'palp.proceso_prenda_detalle_id')
                    ->join('prendas_pedido as pp', 'pp.id', '=', 'ppd.prenda_pedido_id')
                    ->join('pedidos_produccion as ped', 'ped.id', '=', 'pp.pedido_produccion_id')
                    ->join('consecutivos_recibos_pedidos as crp', function ($join) use ($tipoReciboCase) {
                        $join->on('crp.pedido_produccion_id', '=', 'pp.pedido_produccion_id')
                            ->on('crp.prenda_id', '=', 'pp.id')
                            ->where('crp.activo', 1)
                            ->whereRaw("crp.tipo_recibo = ({$tipoReciboCase})");
                    })
                    ->leftJoin('pedidos_parciales as ppar', function ($join) use ($tipoReciboCase) {
                        $join->on('ppar.pedido_produccion_id', '=', 'pp.pedido_produccion_id')
                            ->on('ppar.prenda_pedido_id', '=', 'pp.id')
                            ->where('ppar.estado', 'APROBADO')
                            ->where('ppar.activo', 1)
                            ->whereNull('ppar.deleted_at')
                            ->whereRaw("ppar.tipo_recibo = ({$tipoReciboCase})");
                    })
                    ->where('ppd.estado', 'APROBADO')
                    ->whereNotNull('crp.consecutivo_actual')
                    ->whereNull('palp.pedido_parcial_id')
                    ->whereNull('ppar.id')
                    ->whereIn('ppd.tipo_proceso_id', $tipoProcesoIds)
                    ->where('palp.novedades', 'like', '%' . $busqueda . '%')
                    ->pluck('palp.novedades')
                    ->unique()
                    ->sort()
                    ->values()
                    ->toArray();

                // Agregar novedades de parciales
                $valoresParciales = DB::table('prenda_areas_logo_pedido as palp')
                    ->join('pedidos_parciales as ppar', 'ppar.id', '=', 'palp.pedido_parcial_id')
                    ->join('prendas_pedido as pp', 'pp.id', '=', 'ppar.prenda_pedido_id')
                    ->join('pedidos_procesos_prenda_detalles as ppd', 'ppd.id', '=', 'palp.proceso_prenda_detalle_id')
                    ->where('ppar.estado', 'APROBADO')
                    ->where('ppar.activo', 1)
                    ->whereNull('ppar.deleted_at')
                    ->whereIn('ppd.tipo_proceso_id', $tipoProcesoIds)
                    ->where('palp.novedades', 'like', '%' . $busqueda . '%')
                    ->pluck('palp.novedades')
                    ->unique()
                    ->sort()
                    ->values()
                    ->toArray();

                return array_unique(array_merge($valores, $valoresParciales));

            default:
                return [];
        }
    }

    private function aplicarFiltrosColumnas($query, ?array $columnFilters): void
    {
        if ($columnFilters === null || empty($columnFilters)) {
            return;
        }

        foreach ($columnFilters as $column => $values) {
            if (empty($values) || !is_array($values)) {
                continue;
            }

            switch ($column) {
                case 'area':
                    $query->where(function ($q) use ($values) {
                        foreach ($values as $value) {
                            $q->orWhere('palp.area', $value);
                        }
                    });
                    break;
                case 'cliente':
                    $query->where(function ($q) use ($values) {
                        foreach ($values as $value) {
                            $q->orWhere('ped.cliente', 'like', '%' . $value . '%')
                              ->orWhere('cli.nombre', 'like', '%' . $value . '%');
                        }
                    });
                    break;
                case 'numero_recibo':
                    $query->where(function ($q) use ($values) {
                        foreach ($values as $value) {
                            $q->orWhere('crp.consecutivo_actual', 'like', '%' . $value . '%');
                        }
                    });
                    break;
                case 'asesora':
                    $query->where(function ($q) use ($values) {
                        foreach ($values as $value) {
                            $q->orWhereExists(function ($subQ) use ($value) {
                                $subQ->select(DB::raw(1))
                                    ->from('users as u')
                                    ->whereColumn('u.id', 'ped.asesor_id')
                                    ->where('u.name', 'like', '%' . $value . '%');
                            })
                            ->orWhereExists(function ($subQ) use ($value) {
                                $subQ->select(DB::raw(1))
                                    ->from('users as u')
                                    ->whereColumn('u.id', 'ped.anulado_por_asesora_id')
                                    ->where('u.name', 'like', '%' . $value . '%');
                            });
                        }
                    });
                    break;
                case 'novedades':
                    $query->where(function ($q) use ($values) {
                        foreach ($values as $value) {
                            $q->orWhere('palp.novedades', 'like', '%' . $value . '%');
                        }
                    });
                    break;
                case 'total_dias':
                    // Este filtro se maneja en el use case después de calcular los días
                    break;
            }
        }
    }
}
