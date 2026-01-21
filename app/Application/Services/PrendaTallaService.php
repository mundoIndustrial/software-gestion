<?php

namespace App\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PrendaTallaService
 * 
 * Responsabilidad: Guardar tallas de prendas en la BD
 * Acepta múltiples formatos: JSON, string simple, array
 */
class PrendaTallaService
{
    /**
     * Guardar tallas de prenda
     * 
     * Acepta:
     * - Array asociativo: ['S' => 10, 'M' => 20, 'L' => 15]
     * - String JSON: '{"S":10,"M":20,"L":15}'
     * - String simple: 'S, M, L'
     */
    public function guardarTallasPrenda(int $prendaId, mixed $cantidades): void
    {
        try {
            $tallasCantidades = [];

            // Parsear según formato
            if (is_array($cantidades)) {
                $tallasCantidades = $cantidades;
            } elseif (is_string($cantidades)) {
                // Intentar parsear como JSON
                $parsed = json_decode($cantidades, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($parsed)) {
                    $tallasCantidades = $parsed;
                } else {
                    // Parsear como string simple: "S, M, L"
                    $tallas = array_map('trim', explode(',', $cantidades));
                    $tallasCantidades = array_fill_keys($tallas, 1);
                }
            }

            if (empty($tallasCantidades)) {
                Log::warning(' [PrendaTallaService] No hay tallas para guardar', [
                    'prenda_id' => $prendaId,
                ]);
                return;
            }

            Log::info(' [PrendaTallaService] Guardando tallas de prenda', [
                'prenda_id' => $prendaId,
                'tallas' => $tallasCantidades,
            ]);

            foreach ($tallasCantidades as $talla => $cantidad) {
                DB::table('prenda_pedido_tallas')->insert([
                    'prenda_pedido_id' => $prendaId,
                    'talla' => $talla,
                    'cantidad' => (int)$cantidad,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            Log::info(' [PrendaTallaService] Tallas guardadas exitosamente', [
                'prenda_id' => $prendaId,
                'cantidad_tallas' => count($tallasCantidades),
            ]);
        } catch (\Exception $e) {
            Log::error(' [PrendaTallaService] Error guardando tallas', [
                'prenda_id' => $prendaId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
