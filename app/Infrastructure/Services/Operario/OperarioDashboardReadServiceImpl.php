<?php

namespace App\Infrastructure\Services\Operario;

use App\Domain\Operario\Services\OperarioDashboardReadService;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OperarioDashboardReadServiceImpl implements OperarioDashboardReadService
{
    public function obtenerUsuariosSobremedidaNormalizados(): Collection
    {
        $rolSobremedidaId = Role::where('name', 'confeccion-sobremedida')->value('id');
        if (empty($rolSobremedidaId)) {
            return collect();
        }

        return User::query()
            ->where(function ($q) use ($rolSobremedidaId) {
                $q->whereJsonContains('roles_ids', (int) $rolSobremedidaId)
                    ->orWhere('role_id', (int) $rolSobremedidaId);
            })
            ->pluck('name')
            ->map(fn ($n) => strtolower(trim((string) $n)))
            ->filter()
            ->unique()
            ->values();
    }

    public function obtenerUsuariosTallerNormalizados(): Collection
    {
        $rolTallerId = Role::where('name', 'taller')->value('id');
        if (empty($rolTallerId)) {
            return collect();
        }

        return User::query()
            ->where(function ($q) use ($rolTallerId) {
                $q->whereJsonContains('roles_ids', (int) $rolTallerId)
                    ->orWhere('role_id', (int) $rolTallerId);
            })
            ->pluck('name')
            ->map(fn ($n) => strtolower(trim((string) $n)))
            ->filter()
            ->unique()
            ->values();
    }

    public function obtenerCompletadosPorArea(array $idsRecibo, string $area): Collection
    {
        if (empty($idsRecibo)) {
            return collect();
        }

        return DB::table('prenda_recibo_completado')
            ->where('area', $area)
            ->whereIn('id_recibo', $idsRecibo)
            ->pluck('fecha_completado', 'id_recibo');
    }

    public function obtenerCompletadosParcialesPorArea(array $idsParcial, string $area): Collection
    {
        if (empty($idsParcial)) {
            return collect();
        }

        return DB::table('prenda_recibo_completado')
            ->where('area', $area)
            ->whereIn('id_parcial', $idsParcial)
            ->pluck('fecha_completado', 'id_parcial');
    }

    public function obtenerRecibosCompletadosPorOperario(string $nombreOperario): Collection
    {
        $nombreNormalizado = strtolower(trim($nombreOperario));

        $completados = DB::table('prenda_recibo_completado as prc')
            ->leftJoin('consecutivos_recibos_pedidos as crp', function ($join) {
                $join->on('prc.id_recibo', '=', 'crp.id')
                    ->whereNull('prc.id_parcial');
            })
            ->leftJoin('pedidos_parciales as pp', function ($join) {
                $join->on('prc.id_parcial', '=', 'pp.id')
                    ->whereNotNull('prc.id_parcial');
            })
            ->leftJoin('recibo_por_partes as rpp', function ($join) {
                $join->on('prc.id_parcial', '=', 'rpp.id')
                    ->whereNotNull('prc.id_parcial');
            })
            ->leftJoin('pedidos_produccion as p', function ($join) {
                $join->on('crp.pedido_produccion_id', '=', 'p.id')
                    ->orOn('pp.pedido_produccion_id', '=', 'p.id')
                    ->orOn('rpp.pedido_produccion_id', '=', 'p.id');
            })
            ->leftJoin('prendas_pedido as prep', function ($join) {
                $join->on('crp.prenda_id', '=', 'prep.id')
                    ->orOn('pp.prenda_pedido_id', '=', 'prep.id')
                    ->orOn('rpp.prenda_pedido_id', '=', 'prep.id');
            })
            ->leftJoin('prenda_bodega as pb', 'crp.prenda_bodega_id', '=', 'pb.id')
            ->whereRaw('LOWER(TRIM(prc.nombre_operario)) = ?', [$nombreNormalizado])
            ->select([
                'prc.id',
                'prc.id_recibo',
                'prc.numero_recibo',
                'prc.area',
                'prc.fecha_completado',
                'prc.id_parcial',
                'p.numero_pedido',
                'p.cliente',
                'prep.id as prenda_id',
                'prep.nombre_prenda',
                'prep.descripcion',
                'pb.nombre as bodega_nombre',
                'pb.descripcion as bodega_descripcion',
                'crp.tipo_recibo as crp_tipo',
                'pp.tipo_recibo as pp_tipo',
                'rpp.tipo_recibo as rpp_tipo',
                'crp.consecutivo_actual as crp_consecutivo',
                'pp.consecutivo_actual as pp_consecutivo',
                'rpp.consecutivo_parcial as rpp_consecutivo'
            ])
            ->orderBy('prc.fecha_completado', 'desc')
            ->get();

        return $completados->map(function ($item) {
            $tipoRecibo = $item->crp_tipo ?: ($item->pp_tipo ?: ($item->rpp_tipo ?: 'COSTURA'));
            $esBodega = strtoupper((string) $tipoRecibo) === 'CORTE-PARA-BODEGA';

            return [
                'id' => $item->id,
                'recibo_id' => $item->id_recibo,
                'numero_recibo' => $item->numero_recibo,
                'area' => $item->area,
                'fecha_completado' => $item->fecha_completado,
                'id_parcial' => $item->id_parcial,
                'numero_pedido' => $item->numero_pedido,
                'cliente' => $esBodega ? 'SERVICIO' : ($item->cliente ?? 'N/A'),
                'prenda_id' => $item->prenda_id,
                'nombre_prenda' => $esBodega ? ($item->bodega_nombre ?? 'N/A') : ($item->nombre_prenda ?? 'N/A'),
                'descripcion' => $esBodega ? ($item->bodega_descripcion ?? '') : ($item->descripcion ?? ''),
                'tipo_recibo' => $tipoRecibo,
                'consecutivo_actual' => $item->crp_consecutivo ?: ($item->pp_consecutivo ?: ($item->rpp_consecutivo ?: $item->numero_recibo)),
                'consecutivo_parcial' => $item->pp_consecutivo ?: ($item->rpp_consecutivo ?: null),
            ];
        });
    }

    public function contarRecibosCompletadosPorOperario(string $nombreOperario): array
    {
        $nombreNormalizado = strtolower(trim($nombreOperario));

        $counts = DB::table('prenda_recibo_completado as prc')
            ->leftJoin('consecutivos_recibos_pedidos as crp', function ($join) {
                $join->on('prc.id_recibo', '=', 'crp.id')
                    ->whereNull('prc.id_parcial');
            })
            ->leftJoin('recibo_por_partes as rpp', 'prc.id_parcial', '=', 'rpp.id')
            ->whereRaw('LOWER(TRIM(prc.nombre_operario)) = ?', [$nombreNormalizado])
            ->select(DB::raw("
                COUNT(*) as total,
                SUM(CASE WHEN (crp.tipo_recibo = 'CORTE-PARA-BODEGA' OR rpp.tipo_recibo = 'CORTE-PARA-BODEGA') THEN 1 ELSE 0 END) as bodega,
                SUM(CASE WHEN (COALESCE(crp.tipo_recibo, rpp.tipo_recibo, 'COSTURA') != 'CORTE-PARA-BODEGA') THEN 1 ELSE 0 END) as normales
            "))
            ->first();

        return [
            'total' => (int) ($counts->total ?? 0),
            'bodega' => (int) ($counts->bodega ?? 0),
            'normales' => (int) ($counts->normales ?? 0),
        ];
    }
}

