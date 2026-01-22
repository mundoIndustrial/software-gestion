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
     * Guardar tallas de prenda desde cantidad_talla (estructura relacional)
     * 
     * Soporta:
     * - Array jerárquico: {'DAMA': {'S': 10, 'M': 20}, 'CABALLERO': {'32': 15}}
     * - Array plano: ['S' => 10, 'M' => 20] (DEFAULT: género UNISEX)
     * - String JSON
     */
    public function guardarTallasPrenda(int $prendaId, mixed $cantidades): void
    {
        try {
            $tallasCantidades = [];
            $generoDefault = 'UNISEX';  // Género por defecto para tallas sin género

            // Parsear según formato
            if (is_array($cantidades)) {
                // Verificar si es estructura jerárquica {GENERO: {TALLA: CANTIDAD}}
                // Indicador: primer valor es array
                $firstValue = reset($cantidades);
                
                if (is_array($firstValue) && !is_numeric(key($cantidades))) {
                    // Es estructura jerárquica: {'DAMA': {'S': 10}, ...}
                    foreach ($cantidades as $genero => $tallasObj) {
                        if (is_array($tallasObj)) {
                            foreach ($tallasObj as $talla => $cantidad) {
                                $this->insertarTalla($prendaId, $talla, (int)$cantidad, strtoupper($genero));
                            }
                        }
                    }
                    Log::info(' [PrendaTallaService] Tallas jerárquicas guardadas', [
                        'prenda_id' => $prendaId,
                        'estructura' => 'jerárquica',
                    ]);
                    return;
                } else {
                    // Es estructura plana: {'S': 10, 'M': 20}
                    $tallasCantidades = $cantidades;
                }
            } elseif (is_string($cantidades)) {
                // Intentar parsear como JSON
                $parsed = json_decode($cantidades, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($parsed)) {
                    // Verificar si el JSON es jerárquico
                    $firstValue = reset($parsed);
                    if (is_array($firstValue) && !is_numeric(key($parsed))) {
                        // JSON jerárquico
                        foreach ($parsed as $genero => $tallasObj) {
                            if (is_array($tallasObj)) {
                                foreach ($tallasObj as $talla => $cantidad) {
                                    $this->insertarTalla($prendaId, $talla, (int)$cantidad, strtoupper($genero));
                                }
                            }
                        }
                        Log::info(' [PrendaTallaService] Tallas JSON jerárquicas guardadas', [
                            'prenda_id' => $prendaId,
                            'estructura' => 'JSON jerárquico',
                        ]);
                        return;
                    }
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

            Log::info(' [PrendaTallaService] Guardando tallas de prenda (planas)', [
                'prenda_id' => $prendaId,
                'tallas' => $tallasCantidades,
                'genero_default' => $generoDefault,
            ]);

            // Guardar tallas planas con género default
            foreach ($tallasCantidades as $talla => $cantidad) {
                $this->insertarTalla($prendaId, $talla, (int)$cantidad, $generoDefault);
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

    /**
     * Insertar una talla en la tabla relacional (método helper)
     */
    private function insertarTalla(int $prendaId, string $talla, int $cantidad, string $genero): void
    {
        DB::table('prenda_pedido_tallas')->insertOrIgnore([
            'prenda_pedido_id' => $prendaId,
            'genero' => strtoupper($genero),
            'talla' => $talla,
            'cantidad' => max(0, $cantidad),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
