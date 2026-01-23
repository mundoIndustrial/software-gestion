<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\Traits\ManejaPedidosUseCase;
use Illuminate\Support\Facades\DB;

/**
 * ObtenerHistorialProcesosUseCase
 * 
 * Caso de uso para obtener el historial de procesos de una orden
 * Responsabilidad: Retornar procesos actuales e historial de cambios
 * 
 * PatrÃ³n: Use Case (Application Layer - DDD)
 */
class ObtenerHistorialProcesosUseCase
{
    use ManejaPedidosUseCase;

    /**
     * Ejecutar caso de uso
     * 
     * @param int $numeroPedido - NÃºmero de pedido
     * @return array - Procesos actuales e historial
     */
    public function ejecutar(int $numeroPedido): array
    {
        $this->validarPositivo($numeroPedido, 'NÃºmero de pedido');

        $procesosActuales = DB::table('procesos_prenda')
            ->where('numero_pedido', $numeroPedido)
            ->get();

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

