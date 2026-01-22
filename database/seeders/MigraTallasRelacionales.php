<?php

namespace Database\Seeders;

use App\Models\PrendaPedido;
use App\Models\PrendaPedidoTalla;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

/**
 * Migra datos de JSON cantidad_talla a tabla prenda_pedido_tallas
 * 
 * Uso:
 * php artisan db:seed --class=MigraTallasRelacionales
 */
class MigraTallasRelacionales extends Seeder
{
    public function run(): void
    {
        Log::info('=== INICIA MIGRACIÓN DE TALLAS ===');

        $prendas = PrendaPedido::all();
        $totalPrendas = $prendas->count();
        $prendasMigradas = 0;
        $tallasMigradas = 0;

        foreach ($prendas as $prenda) {
            try {
                // Parsear JSON cantidad_talla
                $cantidadTalla = $this->parsearCantidadTalla($prenda->cantidad_talla);

                if (empty($cantidadTalla)) {
                    Log::debug("Prenda {$prenda->id} sin tallas");
                    continue;
                }

                // Insertar tallas
                foreach ($cantidadTalla as $genero => $tallas) {
                    foreach ($tallas as $talla => $cantidad) {
                        if ($cantidad > 0) {
                            PrendaPedidoTalla::updateOrCreate(
                                [
                                    'prenda_pedido_id' => $prenda->id,
                                    'genero' => strtoupper($genero),
                                    'talla' => $talla,
                                ],
                                ['cantidad' => (int)$cantidad]
                            );
                            $tallasMigradas++;
                        }
                    }
                }

                $prendasMigradas++;
            } catch (\Exception $e) {
                Log::error("Error migrando prenda {$prenda->id}: {$e->getMessage()}");
            }
        }

        // Log sin output en consola
        Log::info("=== MIGRACIÓN COMPLETADA ===");
        Log::info("Prendas totales: {$totalPrendas}");
        Log::info("Prendas migradas: {$prendasMigradas}");
        Log::info("Tallas migradas: {$tallasMigradas}");
    }

    /**
     * Parsear cantidad_talla (puede ser array o JSON string)
     * 
     * @param mixed $cantidadTalla
     * 
     * @return array
     */
    private function parsearCantidadTalla($cantidadTalla): array
    {
        if (is_array($cantidadTalla)) {
            return $cantidadTalla;
        }

        if (is_string($cantidadTalla) && !empty($cantidadTalla)) {
            $parsed = json_decode($cantidadTalla, true);
            return is_array($parsed) ? $parsed : [];
        }

        return [];
    }
}
