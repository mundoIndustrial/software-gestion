<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

/**
 * PrendaPedidoTallaRepository
 * 
 * Responsabilidades:
 * - Acceso a datos de tallas de prendas
 * - Cálculo de cantidades totales
 * - Soporta dos flujos: talla-color y normal
 */
class PrendaPedidoTallaRepository
{
    /**
     * Calcular cantidad total de una prenda
     * 
     * Soporta dos flujos:
     * 1. Flujo talla-color: Suma desde prenda_pedido_talla_colores
     * 2. Flujo normal: Suma desde prenda_pedido_tallas.cantidad
     * 
     * @param int $prendaPedidoId ID de la prenda del pedido
     * @return int Cantidad total
     */
    public function calcularCantidadTotalPrenda(int $prendaPedidoId): int
    {
        try {
            // Flujo 1: Intentar sumar desde prenda_pedido_talla_colores (talla-color)
            $cantidadTallaColor = DB::table('prenda_pedido_talla_colores as pptc')
                ->join('prenda_pedido_tallas as ppt', 'ppt.id', '=', 'pptc.prenda_pedido_talla_id')
                ->where('ppt.prenda_pedido_id', $prendaPedidoId)
                ->sum('pptc.cantidad');

            // Si hay datos en talla-color, usar esa suma
            if ($cantidadTallaColor > 0) {
                return (int)$cantidadTallaColor;
            }

            // Flujo 2: Sumar desde prenda_pedido_tallas.cantidad (flujo normal)
            $cantidadNormal = DB::table('prenda_pedido_tallas as ppt')
                ->where('ppt.prenda_pedido_id', $prendaPedidoId)
                ->sum('ppt.cantidad');

            return (int)$cantidadNormal;
        } catch (\Exception $e) {
            \Log::error('[PrendaPedidoTallaRepository] Error calculando cantidad de prenda: ' . $e->getMessage(), [
                'prenda_pedido_id' => $prendaPedidoId
            ]);
            return 0;
        }
    }

    /**
     * Obtener tallas con desglose por color para una prenda.
     * Devuelve una colección vacía si la prenda no usa el flujo talla-color.
     *
     * @param  int  $prendaPedidoId
     * @return Collection  de stdClass con campos: talla, color_nombre, cantidad
     */
    public function getTallasPorColor(int $prendaPedidoId): Collection
    {
        return DB::table('prenda_pedido_talla_colores as pptc')
            ->join('prenda_pedido_tallas as ppt', 'ppt.id', '=', 'pptc.prenda_pedido_talla_id')
            ->where('ppt.prenda_pedido_id', $prendaPedidoId)
            ->select([
                'ppt.talla',
                'pptc.color_nombre',
                'pptc.cantidad',
            ])
            ->get();
    }
}
