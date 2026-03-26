<?php

namespace App\Infrastructure\Services\Pedidos;

use Illuminate\Support\Facades\DB;

/**
 * Servicio de Dominio para gestionar secuencias de pedidos
 */
class PedidoSequenceService
{
    /**
     * Generar el siguiente numero de pedido
     */
    public function generarNumeroPedido(): int
    {
        $tipo = 'pedido_produccion';

        $secuenciaRow = DB::table('numero_secuencias')
            ->where('tipo', $tipo)
            ->lockForUpdate()
            ->first();

        $numeroPedido = (int) ($secuenciaRow?->siguiente ?? 45709);

        if (!$secuenciaRow) {
            DB::table('numero_secuencias')->insert([
                'tipo' => $tipo,
                'siguiente' => $numeroPedido + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $numeroPedido;
        }

        DB::table('numero_secuencias')
            ->where('tipo', $tipo)
            ->update([
                'siguiente' => $numeroPedido + 1,
                'updated_at' => now(),
            ]);

        return $numeroPedido;
    }
}
