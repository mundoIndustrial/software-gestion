<?php

namespace App\Infrastructure\Repositories\Operario;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OperarioDashboardRepository
{
    public function obtenerConteosParcialesControlCalidadPorTipoRecibo(): Collection
    {
        return DB::table('procesos_prenda')
            ->leftJoin('pedidos_produccion as pp', 'pp.numero_pedido', '=', 'procesos_prenda.numero_pedido')
            ->leftJoin('recibo_por_partes', function ($join) {
                $join->on('recibo_por_partes.pedido_produccion_id', '=', 'pp.id')
                    ->on('recibo_por_partes.prenda_pedido_id', '=', 'procesos_prenda.prenda_pedido_id')
                    ->on('recibo_por_partes.consecutivo_parcial', '=', 'procesos_prenda.numero_recibo_parcial');
            })
            ->whereRaw('LOWER(TRIM(procesos_prenda.proceso)) IN (?, ?)', ['control de calidad', 'control calidad'])
            ->whereNull('procesos_prenda.deleted_at')
            ->select('recibo_por_partes.tipo_recibo', DB::raw('count(*) as total'))
            ->groupBy('recibo_por_partes.tipo_recibo')
            ->get();
    }

    public function buscarCoincidenciasVistaCosturaFueraDeArea(
        string $comodin,
        bool $esNumerica,
        array $tiposPermitidos
    ): Collection {
        return DB::table('consecutivos_recibos_pedidos as crp')
            ->leftJoin('pedidos_produccion as pp', 'pp.id', '=', 'crp.pedido_produccion_id')
            ->leftJoin('prendas_pedido as pr', 'pr.id', '=', 'crp.prenda_id')
            ->select([
                'crp.id',
                'crp.pedido_produccion_id',
                'crp.prenda_id',
                'crp.consecutivo_actual',
                'crp.consecutivo_inicial',
                'crp.tipo_recibo',
                'crp.area',
                'crp.estado',
                'crp.created_at',
                'crp.notas',
                'pp.numero_pedido',
                'pp.cliente',
                'pr.nombre_prenda',
                'pr.descripcion',
            ])
            ->where(function ($query) use ($comodin, $esNumerica) {
                if ($esNumerica) {
                    $query
                        ->orWhereRaw('CAST(crp.consecutivo_actual AS CHAR) LIKE ?', [$comodin])
                        ->orWhereRaw('CAST(crp.consecutivo_inicial AS CHAR) LIKE ?', [$comodin])
                        ->orWhereRaw('CAST(pp.numero_pedido AS CHAR) LIKE ?', [$comodin]);
                    return;
                }

                $query
                    ->orWhereRaw('LOWER(CAST(crp.consecutivo_actual AS CHAR)) LIKE ?', [$comodin])
                    ->orWhereRaw('LOWER(CAST(crp.consecutivo_inicial AS CHAR)) LIKE ?', [$comodin])
                    ->orWhereRaw('LOWER(COALESCE(crp.tipo_recibo, "")) LIKE ?', [$comodin])
                    ->orWhereRaw('LOWER(COALESCE(crp.area, "")) LIKE ?', [$comodin])
                    ->orWhereRaw('LOWER(COALESCE(crp.estado, "")) LIKE ?', [$comodin])
                    ->orWhereRaw('LOWER(COALESCE(crp.notas, "")) LIKE ?', [$comodin])
                    ->orWhereRaw('LOWER(COALESCE(pp.numero_pedido, "")) LIKE ?', [$comodin])
                    ->orWhereRaw('LOWER(COALESCE(pp.cliente, "")) LIKE ?', [$comodin])
                    ->orWhereRaw('LOWER(COALESCE(pr.nombre_prenda, "")) LIKE ?', [$comodin])
                    ->orWhereRaw('LOWER(COALESCE(pr.descripcion, "")) LIKE ?', [$comodin]);
            })
            ->where(function ($query) use ($tiposPermitidos) {
                foreach ($tiposPermitidos as $tipoPermitido) {
                    $query->orWhereRaw('UPPER(COALESCE(crp.tipo_recibo, "")) = ?', [$tipoPermitido]);
                }
            })
            ->orderBy('crp.created_at')
            ->get();
    }

    public function buscarCoincidenciasVistaCosturaParaMensaje(
        string $comodin,
        bool $esNumerica,
        array $tiposPermitidos,
        int $limit = 20
    ): Collection {
        return DB::table('consecutivos_recibos_pedidos as crp')
            ->leftJoin('pedidos_produccion as pp', 'pp.id', '=', 'crp.pedido_produccion_id')
            ->leftJoin('prendas_pedido as pr', 'pr.id', '=', 'crp.prenda_id')
            ->select([
                'crp.id',
                'crp.consecutivo_actual',
                'crp.consecutivo_inicial',
                'crp.tipo_recibo',
                'crp.area',
                'crp.estado',
                'pp.numero_pedido',
                'pp.cliente',
                'pr.nombre_prenda',
                'pr.descripcion',
            ])
            ->where(function ($query) use ($comodin, $esNumerica) {
                if ($esNumerica) {
                    $query
                        ->orWhereRaw('CAST(crp.consecutivo_actual AS CHAR) LIKE ?', [$comodin])
                        ->orWhereRaw('CAST(crp.consecutivo_inicial AS CHAR) LIKE ?', [$comodin])
                        ->orWhereRaw('CAST(pp.numero_pedido AS CHAR) LIKE ?', [$comodin]);
                    return;
                }

                $query
                    ->orWhereRaw('LOWER(CAST(crp.consecutivo_actual AS CHAR)) LIKE ?', [$comodin])
                    ->orWhereRaw('LOWER(CAST(crp.consecutivo_inicial AS CHAR)) LIKE ?', [$comodin])
                    ->orWhereRaw('LOWER(COALESCE(crp.tipo_recibo, "")) LIKE ?', [$comodin])
                    ->orWhereRaw('LOWER(COALESCE(crp.area, "")) LIKE ?', [$comodin])
                    ->orWhereRaw('LOWER(COALESCE(crp.estado, "")) LIKE ?', [$comodin])
                    ->orWhereRaw('LOWER(COALESCE(crp.notas, "")) LIKE ?', [$comodin])
                    ->orWhereRaw('LOWER(COALESCE(pp.numero_pedido, "")) LIKE ?', [$comodin])
                    ->orWhereRaw('LOWER(COALESCE(pp.cliente, "")) LIKE ?', [$comodin])
                    ->orWhereRaw('LOWER(COALESCE(pr.nombre_prenda, "")) LIKE ?', [$comodin])
                    ->orWhereRaw('LOWER(COALESCE(pr.descripcion, "")) LIKE ?', [$comodin]);
            })
            ->where(function ($query) use ($tiposPermitidos) {
                foreach ($tiposPermitidos as $tipoPermitido) {
                    $query->orWhereRaw('UPPER(COALESCE(crp.tipo_recibo, "")) = ?', [$tipoPermitido]);
                }
            })
            ->limit($limit)
            ->get();
    }
}

