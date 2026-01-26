<?php

namespace App\Domain\Pedidos\Services;

use Illuminate\Support\Facades\DB;

/**
 * Servicio de Dominio para gestionar secuencias de pedidos
 */
class PedidoSequenceService
{
    /**
     * Generar el siguiente nÃºmero de pedido
     * @return int
     */
    public function generarNumeroPedido(): int
    {
        $secuenciaRow = DB::table('numero_secuencias')
            ->where('tipo', 'pedido_produccion')
            ->lockForUpdate()
            ->first();
        
        $numeroPedido = $secuenciaRow?->siguiente ?? 45709;
        
        // Incrementar secuencia para el próximo pedido
        DB::table('numero_secuencias')
            ->where('tipo', 'pedido_produccion')
            ->increment('siguiente');

        return $numeroPedido;
    }
}

