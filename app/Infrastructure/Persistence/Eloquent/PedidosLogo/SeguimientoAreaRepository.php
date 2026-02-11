<?php

namespace App\Infrastructure\Persistence\Eloquent\PedidosLogo;

use App\Domain\PedidosLogo\Repositories\SeguimientoAreaRepositoryInterface;
use Illuminate\Support\Facades\DB;

final class SeguimientoAreaRepository implements SeguimientoAreaRepositoryInterface
{
    public function obtenerPorProceso(int $procesoPrendaDetalleId): ?array
    {
        $row = DB::table('prenda_areas_logo_pedido')
            ->where('proceso_prenda_detalle_id', $procesoPrendaDetalleId)
            ->first();

        if (!$row) {
            return null;
        }

        return [
            'proceso_prenda_detalle_id' => $row->proceso_prenda_detalle_id,
            'prenda_pedido_id' => $row->prenda_pedido_id,
            'area' => $row->area,
            'novedades' => $row->novedades,
            'fechas_areas' => $row->fechas_areas,
        ];
    }

    public function upsertSeguimiento(int $procesoPrendaDetalleId, int $prendaPedidoId, string $area, ?string $novedades, array $fechasAreas, string $timestamp): void
    {
        $existente = DB::table('prenda_areas_logo_pedido')
            ->where('proceso_prenda_detalle_id', $procesoPrendaDetalleId)
            ->first();

        if ($existente) {
            DB::table('prenda_areas_logo_pedido')
                ->where('proceso_prenda_detalle_id', $procesoPrendaDetalleId)
                ->update([
                    'prenda_pedido_id' => $prendaPedidoId,
                    'area' => $area,
                    'novedades' => $novedades,
                    'fechas_areas' => json_encode($fechasAreas),
                    'updated_at' => $timestamp,
                ]);
            return;
        }

        DB::table('prenda_areas_logo_pedido')->insert([
            'proceso_prenda_detalle_id' => $procesoPrendaDetalleId,
            'prenda_pedido_id' => $prendaPedidoId,
            'area' => $area,
            'novedades' => $novedades,
            'fechas_areas' => json_encode($fechasAreas),
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
    }
}
