<?php

namespace App\Domain\Pedidos\Services;

use Illuminate\Support\Facades\DB;

/**
 * Servicio de dominio para generación de nÃºmeros de pedido
 * Responsabilidad Ãºnica: Generar nÃºmeros secuenciales Ãºnicos
 */
class NumeracionService
{
    /**
     * Generar nÃºmero Ãºnico para pedido de producción
     * Retorna solo el nÃºmero entero (sin prefijo PEP-)
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
            \Log::error('Error generando nÃºmero de pedido', [
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException('No se pudo generar el nÃºmero de pedido');
        }
    }

    /**
     * Generar nÃºmero Ãºnico para LOGO PEDIDO con formato 00001
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
            \Log::error('Error generando nÃºmero de logo pedido', [
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException('No se pudo generar el nÃºmero de logo pedido');
        }
    }
}

