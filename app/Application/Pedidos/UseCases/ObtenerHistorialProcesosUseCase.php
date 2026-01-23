<?php

namespace App\Application\Pedidos\UseCases;

use Illuminate\Support\Facades\DB;

/**
 * ObtenerHistorialProcesosUseCase
 * 
 * Caso de uso para obtener el historial de procesos de una orden
 * Responsabilidad: Retornar procesos actuales e historial de cambios
 * 
 * Patrón: Use Case (Application Layer - DDD)
 */
class ObtenerHistorialProcesosUseCase
{
    /**
     * Ejecutar caso de uso
     * 
     * @param int $numeroPedido - Número de pedido
     * @return array - Procesos actuales e historial
     */
    public function ejecutar(int $numeroPedido): array
    {
        // Obtener procesos actuales
        $procesosActuales = DB::table('procesos_prenda')
            ->where('numero_pedido', $numeroPedido)
            ->get();

        // Obtener historial
        $historial = DB::table('procesos_historial')
            ->where('numero_pedido', $numeroPedido)
            ->orderBy('created_at', 'desc')
            ->get();

        return [
            'success' => true,
            'procesos_actuales' => $procesosActuales->toArray(),
            'historial' => $historial->toArray()
        ];
    }
}
