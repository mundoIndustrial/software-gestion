<?php

namespace App\Infrastructure\Persistence\Eloquent\PedidosLogo;

use App\Domain\PedidosLogo\Repositories\SeguimientoAreaRepositoryInterface;
use Illuminate\Support\Facades\DB;

final class SeguimientoAreaRepository implements SeguimientoAreaRepositoryInterface
{
    public function obtenerPorProceso(int $procesoPrendaDetalleId, ?int $pedidoParcialId = null): ?array
    {
        $query = DB::table('prenda_areas_logo_pedido')
            ->where('proceso_prenda_detalle_id', $procesoPrendaDetalleId);

        // If pedido_parcial_id is provided, use it to filter the specific record
        if ($pedidoParcialId !== null) {
            $query->where('pedido_parcial_id', $pedidoParcialId);
        } else {
            // Otherwise, get the record without a pedido_parcial_id (non-partial)
            $query->whereNull('pedido_parcial_id');
        }

        $row = $query->first();

        if (!$row) {
            return null;
        }

        return [
            'proceso_prenda_detalle_id' => $row->proceso_prenda_detalle_id,
            'prenda_pedido_id' => $row->prenda_pedido_id,
            'area' => $row->area,
            'novedades' => $row->novedades,
            'fechas_areas' => $row->fechas_areas,
            'pedido_parcial_id' => $row->pedido_parcial_id,
            'consecutivo_recibo_id' => $row->consecutivo_recibo_id ?? null,
        ];
    }

    public function upsertSeguimiento(int $procesoPrendaDetalleId, int $prendaPedidoId, string $area, ?string $novedades, array $fechasAreas, string $timestamp, ?int $pedidoParcialId = null, ?int $consecutivoReciboId = null): void
    {
        // Build the where clause - search by both proceso and pedido_parcial_id
        $query = DB::table('prenda_areas_logo_pedido')
            ->where('proceso_prenda_detalle_id', $procesoPrendaDetalleId);
        
        if ($pedidoParcialId !== null) {
            $query->where('pedido_parcial_id', $pedidoParcialId);
        } else {
            $query->whereNull('pedido_parcial_id');
        }

        $existente = $query->first();

        if ($existente) {
            // Update the specific record with both criteria
            $updateQuery = DB::table('prenda_areas_logo_pedido')
                ->where('proceso_prenda_detalle_id', $procesoPrendaDetalleId);
            
            if ($pedidoParcialId !== null) {
                $updateQuery->where('pedido_parcial_id', $pedidoParcialId);
            } else {
                $updateQuery->whereNull('pedido_parcial_id');
            }

            $updateQuery->update([
                'prenda_pedido_id' => $prendaPedidoId,
                'area' => $area,
                'novedades' => $novedades,
                'fechas_areas' => json_encode($fechasAreas),
                'pedido_parcial_id' => $pedidoParcialId,
                'consecutivo_recibo_id' => $consecutivoReciboId,
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
            'pedido_parcial_id' => $pedidoParcialId,
            'consecutivo_recibo_id' => $consecutivoReciboId,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
    }

    /**
     * Guardar seguimiento para recibos sin proceso (proceso_prenda_detalle_id = NULL)
     */
    public function upsertSeguimientoSinProceso(?int $procesoPrendaDetalleId, int $prendaPedidoId, string $area, ?string $novedades, array $fechasAreas, string $timestamp, ?int $pedidoParcialId = null, ?int $consecutivoReciboId = null): void
    {
        // Para recibos sin proceso, buscar por pedido_parcial_id o ambos NULL
        $query = DB::table('prenda_areas_logo_pedido');
        
        if ($pedidoParcialId !== null) {
            // Parcial sin proceso: buscar por pedido_parcial_id y proceso_prenda_detalle_id = NULL
            $query->whereNull('proceso_prenda_detalle_id')
                ->where('pedido_parcial_id', $pedidoParcialId);
        } else {
            // Recibo base sin proceso: ambos NULL
            $query->whereNull('proceso_prenda_detalle_id')
                ->whereNull('pedido_parcial_id');
        }

        $existente = $query->first();

        if ($existente) {
            // Update
            $updateQuery = DB::table('prenda_areas_logo_pedido')
                ->whereNull('proceso_prenda_detalle_id');
            
            if ($pedidoParcialId !== null) {
                $updateQuery->where('pedido_parcial_id', $pedidoParcialId);
            } else {
                $updateQuery->whereNull('pedido_parcial_id');
            }

            $updateQuery->update([
                'prenda_pedido_id' => $prendaPedidoId,
                'area' => $area,
                'novedades' => $novedades,
                'fechas_areas' => json_encode($fechasAreas),
                'pedido_parcial_id' => $pedidoParcialId,
                'consecutivo_recibo_id' => $consecutivoReciboId,
                'updated_at' => $timestamp,
            ]);
            return;
        }

        // Insert
        DB::table('prenda_areas_logo_pedido')->insert([
            'proceso_prenda_detalle_id' => $procesoPrendaDetalleId,
            'prenda_pedido_id' => $prendaPedidoId,
            'area' => $area,
            'novedades' => $novedades,
            'fechas_areas' => json_encode($fechasAreas),
            'pedido_parcial_id' => $pedidoParcialId,
            'consecutivo_recibo_id' => $consecutivoReciboId,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
    }
}
