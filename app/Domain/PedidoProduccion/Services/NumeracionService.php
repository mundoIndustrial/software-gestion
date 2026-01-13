<?php

namespace App\Domain\PedidoProduccion\Services;

use Illuminate\Support\Facades\DB;

/**
 * Servicio de dominio para generación de números de pedido
 * Responsabilidad única: Generar números secuenciales únicos
 */
class NumeracionService
{
    /**
     * Generar número único para pedido de producción
     * Retorna solo el número entero (sin prefijo PEP-)
     * Usa DB lock para prevenir race conditions
     */
    public function generarNumeroPedido(): int
    {
        try {
            $secuencia = DB::table('numero_secuencias')
                ->where('tipo', 'pedido_produccion')
                ->lockForUpdate()
                ->first();

            if (!$secuencia) {
                DB::table('numero_secuencias')->insert([
                    'tipo' => 'pedido_produccion',
                    'ultimo_numero' => 1,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                return 1;
            }

            $nuevoNumero = $secuencia->ultimo_numero + 1;

            DB::table('numero_secuencias')
                ->where('tipo', 'pedido_produccion')
                ->update([
                    'ultimo_numero' => $nuevoNumero,
                    'updated_at' => now()
                ]);

            return $nuevoNumero;
        } catch (\Exception $e) {
            \Log::error('Error generando número de pedido', [
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException('No se pudo generar el número de pedido');
        }
    }

    /**
     * Generar número único para LOGO PEDIDO con formato 00001
     */
    public function generarNumeroLogoPedido(): string
    {
        try {
            $secuencia = DB::table('numero_secuencias')
                ->where('tipo', 'logo_pedido')
                ->lockForUpdate()
                ->first();

            if (!$secuencia) {
                DB::table('numero_secuencias')->insert([
                    'tipo' => 'logo_pedido',
                    'ultimo_numero' => 1,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                
                return str_pad(1, 5, '0', STR_PAD_LEFT);
            }

            $nuevoNumero = $secuencia->ultimo_numero + 1;

            DB::table('numero_secuencias')
                ->where('tipo', 'logo_pedido')
                ->update([
                    'ultimo_numero' => $nuevoNumero,
                    'updated_at' => now()
                ]);

            return str_pad($nuevoNumero, 5, '0', STR_PAD_LEFT);
        } catch (\Exception $e) {
            \Log::error('Error generando número de logo pedido', [
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException('No se pudo generar el número de logo pedido');
        }
    }
}
