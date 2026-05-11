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

    public function obtenerRecibosCompletadosPorOperario(string $nombreOperario): Collection
    {
        $nombreNormalizado = strtolower(trim($nombreOperario));

        $completados = DB::table('prenda_recibo_completado as prc')
            ->leftJoin('consecutivos_recibos_pedidos as crp', function ($join) {
                $join->on('prc.id_recibo', '=', 'crp.id')
                    ->whereNull('prc.id_parcial');
            })
            ->leftJoin('recibo_por_partes as rpp', 'prc.id_parcial', '=', 'rpp.id')
            ->leftJoin('pedidos_produccion as p', function ($join) {
                $join->on('crp.pedido_produccion_id', '=', 'p.id')
                    ->orOn('rpp.pedido_produccion_id', '=', 'p.id');
            })
            ->leftJoin('prendas_pedido as prep', function ($join) {
                $join->on('crp.prenda_id', '=', 'prep.id')
                    ->orOn('rpp.prenda_pedido_id', '=', 'prep.id');
            })
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
                'crp.tipo_recibo as crp_tipo',
                'rpp.tipo_recibo as pp_tipo',
                'crp.consecutivo_actual as crp_consecutivo',
                'rpp.consecutivo_parcial as pp_consecutivo'
            ])
            ->orderBy('prc.fecha_completado', 'desc')
            ->get();

        return $completados->map(function ($item) {
            return [
                'id' => $item->id,
                'id_recibo' => $item->id_recibo,
                'numero_recibo' => $item->numero_recibo,
                'area' => $item->area,
                'fecha_completado' => $item->fecha_completado,
                'id_parcial' => $item->id_parcial,
                'numero_pedido' => $item->numero_pedido,
                'cliente' => $item->cliente,
                'prenda_id' => $item->prenda_id,
                'nombre_prenda' => $item->nombre_prenda,
                'descripcion' => $item->descripcion,
                'tipo_recibo' => $item->crp_tipo ?: ($item->pp_tipo ?: 'COSTURA'),
                'consecutivo_actual' => $item->crp_consecutivo ?: ($item->pp_consecutivo ?: $item->numero_recibo),
            ];
        });
    }
}

