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
        return (int) DB::table('prendas_pedido')
            ->where('numero_pedido', $pedido)
            ->sum('cantidad');
    }

    /**
     * Obtener cantidad total entregada de una orden
     * Busca en procesos_prenda tabla
     * 
     * @param int $pedido - Número de pedido
     * @return int - Total entregado
     */
    public function getTotalDelivered(int $pedido): int
    {
        try {
            // Contar procesos completados en lugar de sumar una columna inexistente
            return (int) DB::table('procesos_prenda')
                ->where('numero_pedido', $pedido)
                ->where('estado_proceso', 'Completado')
                ->count();
        } catch (\Exception $e) {
            \Log::warning('Error al calcular totalEntregado', ['error' => $e->getMessage()]);
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
