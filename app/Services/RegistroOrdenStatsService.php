<?php

namespace App\Services;

use App\Models\PedidoProduccion;
use Illuminate\Support\Facades\DB;

/**
 * RegistroOrdenStatsService
 * 
 * Responsabilidad: Calcular estadísticas de órdenes (totales, entregados, etc)
 * Extrae la lógica de cálculos del controlador
 * 
 * CUMPLE SRP: Solo calcula estadísticas
 */
class RegistroOrdenStatsService
{
    /**
     * Obtener estadísticas completas de una orden
     * Incluye: cantidad total, cantidad entregada
     * 
     * @param int $pedido - Número de pedido
     * @return array - [total_cantidad, total_entregado]
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getOrderStats(int $pedido): array
    {
        // Obtener la orden
        $orden = PedidoProduccion::where('numero_pedido', $pedido)->firstOrFail();

        // Calcular cantidad total de prendas
        $totalCantidad = $this->getTotalQuantity($pedido);

        // Calcular cantidad entregada
        $totalEntregado = $this->getTotalDelivered($pedido);

        return [
            'total_cantidad' => $totalCantidad,
            'total_entregado' => $totalEntregado,
            'pendiente' => $totalCantidad - $totalEntregado
        ];
    }

    /**
     * Obtener cantidad total de prendas en una orden
     * 
     * @param int $pedido - Número de pedido
     * @return int - Total de prendas
     */
    public function getTotalQuantity(int $pedido): int
    {
        try {
            // Primero obtener los IDs de prendas_pedido para este número de pedido
            $prendaIds = DB::table('prendas_pedido')
                ->join('pedidos_produccion', 'prendas_pedido.pedido_produccion_id', '=', 'pedidos_produccion.id')
                ->where('pedidos_produccion.numero_pedido', $pedido)
                ->pluck('prendas_pedido.id');

            if ($prendaIds->isEmpty()) {
                return 0;
            }

            // Sumar cantidades desde la tabla prenda_pedido_tallas
            return (int) DB::table('prenda_pedido_tallas')
                ->whereIn('prenda_pedido_id', $prendaIds)
                ->sum('cantidad');

        } catch (\Exception $e) {
            \Log::warning('Error al calcular getTotalQuantity', [
                'pedido' => $pedido,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Obtener cantidad total entregada de una orden
     * Busca en pedidos_procesos_prenda_detalles tabla
     * 
     * @param int $pedido - Número de pedido
     * @return int - Total entregado
     */
    public function getTotalDelivered(int $pedido): int
    {
        try {
            // Primero obtener los IDs de prendas_pedido para este número de pedido
            $prendaIds = DB::table('prendas_pedido')
                ->join('pedidos_produccion', 'prendas_pedido.pedido_produccion_id', '=', 'pedidos_produccion.id')
                ->where('pedidos_produccion.numero_pedido', $pedido)
                ->pluck('prendas_pedido.id');

            if ($prendaIds->isEmpty()) {
                return 0;
            }

            // Contar procesos completados y sumar sus cantidades
            $procesosCompletados = DB::table('pedidos_procesos_prenda_detalles')
                ->whereIn('prenda_pedido_id', $prendaIds)
                ->where('estado', 'COMPLETADO')
                ->get();

            // Sumar cantidades desde las tablas de tallas de procesos
            $totalEntregado = 0;
            foreach ($procesosCompletados as $proceso) {
                $cantidadProceso = DB::table('pedidos_procesos_prenda_tallas')
                    ->where('proceso_prenda_detalle_id', $proceso->id)
                    ->sum('cantidad');
                $totalEntregado += $cantidadProceso;
            }

            return (int) $totalEntregado;

        } catch (\Exception $e) {
            \Log::warning('Error al calcular totalEntregado', [
                'pedido' => $pedido,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Obtener cantidad pendiente de una orden
     * 
     * @param int $pedido - Número de pedido
     * @return int - Total pendiente
     */
    public function getTotalPending(int $pedido): int
    {
        return $this->getTotalQuantity($pedido) - $this->getTotalDelivered($pedido);
    }
}
