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
     * - Sobremedida: {'SOBREMEDIDA': {'DAMA': 100, 'CABALLERO': 50}}
     */
    public function guardarTallasPrenda(int $prendaId, mixed $cantidades): void
    {
        try {
            $tallasCantidades = [];
            $generoDefault = 'UNISEX';  // Género por defecto para tallas sin género

            // Parsear según formato
            if (is_array($cantidades)) {
                // Verificar si incluye SOBREMEDIDA
                if (isset($cantidades['SOBREMEDIDA']) && is_array($cantidades['SOBREMEDIDA'])) {
                    // Procesar sobremedida
                    foreach ($cantidades['SOBREMEDIDA'] as $genero => $cantidad) {
                        $this->guardarSobremedida($prendaId, (int)$cantidad, strtoupper($genero));
                    }
                    
                    Log::info('[PrendaTallaService] Sobremedida guardada', [
                        'prenda_id' => $prendaId,
                        'sobremedida' => $cantidades['SOBREMEDIDA'],
                    ]);
                    
                    // Procesar el resto de las tallas (si existen)
                    $cantidades = array_diff_key($cantidades, ['SOBREMEDIDA' => null]);
                    
                    if (empty($cantidades)) {
                        // Solo había sobremedida
                        return;
                    }
                }
                
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
                    // Verificar si incluye SOBREMEDIDA
                    if (isset($parsed['SOBREMEDIDA']) && is_array($parsed['SOBREMEDIDA'])) {
                        foreach ($parsed['SOBREMEDIDA'] as $genero => $cantidad) {
                            $this->guardarSobremedida($prendaId, (int)$cantidad, strtoupper($genero));
                        }
                        $parsed = array_diff_key($parsed, ['SOBREMEDIDA' => null]);
                        if (empty($parsed)) return;
                    }
                    
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
     * Guardar una entrada de sobremedida (cantidad sin talla específica)
     * 
     * La sobremedida es para cantidad genérica que no se asigna a tallas específicas
     * @param int $prendaId - ID de la prenda
     * @param int $cantidad - Cantidad total de sobremedida
     * @param string $genero - Género (DAMA, CABALLERO, UNISEX)
     */
    public function guardarSobremedida(int $prendaId, int $cantidad, string $genero = 'UNISEX'): void
    {
        if ($cantidad <= 0) {
            Log::warning('[PrendaTallaService::guardarSobremedida] Cantidad debe ser > 0', [
                'prenda_id' => $prendaId,
                'cantidad' => $cantidad,
            ]);
            return;
        }

        try {
            Log::info('[PrendaTallaService::guardarSobremedida] Guardando sobremedida', [
                'prenda_id' => $prendaId,
                'genero' => $genero,
                'cantidad' => $cantidad,
            ]);

            $this->insertarTalla($prendaId, null, $cantidad, strtoupper($genero), true);

            Log::info('[PrendaTallaService::guardarSobremedida] Sobremedida guardada', [
                'prenda_id' => $prendaId,
                'genero' => $genero,
                'cantidad' => $cantidad,
            ]);
        } catch (\Exception $e) {
            Log::error('[PrendaTallaService::guardarSobremedida] Error guardando sobremedida', [
                'prenda_id' => $prendaId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Insertar una talla en la tabla relacional (método helper)
     * 
     * @param int $prendaId - ID de la prenda
     * @param string|null $talla - Nombre de la talla (null para sobremedida)
     * @param int $cantidad - Cantidad
     * @param string $genero - Género
     * @param bool $esSobremedida - Indica si es sobremedida
     */
    private function insertarTalla(int $prendaId, ?string $talla, int $cantidad, string $genero, bool $esSobremedida = false): void
    {
        DB::table('prenda_pedido_tallas')->insertOrIgnore([
            'prenda_pedido_id' => $prendaId,
            'genero' => strtoupper($genero),
            'talla' => $talla,
            'cantidad' => max(0, $cantidad),
            'es_sobremedida' => $esSobremedida,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
