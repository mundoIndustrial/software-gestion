<?php

namespace App\Domain\PedidoProduccion\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PrendaTallaService
 * 
 * Responsabilidad: Guardar tallas de prendas en la BD
 */
class PrendaTallaService
{
    /**
     * Guardar tallas de prenda usando tabla relacional
     * 
     * Estructura esperada:
     * - {DAMA: {S: 5, M: 10}, CABALLERO: {M: 3, L: 7}}  (RELACIONAL)
     * - O fallback: {S: 5, M: 10, L: 3}  (LEGACY plano)
     * 
     * @param int $prendaId
     * @param mixed $cantidades Array o JSON string
     * @return void
     */
    public function guardarTallasPrenda(int $prendaId, mixed $cantidades): void
    {
        try {
            $tallasCantidades = [];
            
            // Parsear entrada (JSON string o array)
            if (is_string($cantidades)) {
                $tallasCantidades = json_decode($cantidades, true) ?? [];
            } elseif (is_array($cantidades)) {
                $tallasCantidades = $cantidades;
            }

            if (empty($tallasCantidades)) {
                Log::warning(' [PrendaTallaService] Cantidades vacías', ['prenda_id' => $prendaId]);
                return;
            }

            $registros = [];
            
            // DETECTAR ESTRUCTURA: ¿Es relacional o legacy?
            $esRelacional = $this->esEstructuraRelacional($tallasCantidades);
            
            Log::info(' [PrendaTallaService] Detectando formato', [
                'prenda_id' => $prendaId,
                'es_relacional' => $esRelacional,
                'estructura_keys' => array_keys($tallasCantidades),
            ]);
            
            if ($esRelacional) {
                // PROCESAMIENTO RELACIONAL: {GENERO: {TALLA: CANTIDAD}}
                foreach ($tallasCantidades as $genero => $tallas) {
                    // Validar que genero es válido
                    if (!in_array($genero, ['DAMA', 'CABALLERO', 'UNISEX'])) {
                        Log::warning(' [PrendaTallaService] Género inválido ignorado', [
                            'genero' => $genero,
                            'prenda_id' => $prendaId,
                        ]);
                        continue;
                    }
                    
                    // Procesar tallas de este género
                    if (is_array($tallas)) {
                        foreach ($tallas as $talla => $cantidad) {
                            if ($cantidad > 0) {
                                $registros[] = [
                                    'prenda_pedido_id' => $prendaId,
                                    'genero' => $genero,
                                    'talla' => (string)$talla,
                                    'cantidad' => (int)$cantidad,
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ];
                            }
                        }
                    }
                }
            } else {
                // PROCESAMIENTO LEGACY: {talla: cantidad} - Asumir GENERO mixto o UNISEX
                Log::warning(' [PrendaTallaService] Usando formato legacy - sin género especificado', [
                    'prenda_id' => $prendaId,
                ]);
                
                foreach ($tallasCantidades as $talla => $cantidad) {
                    if ($talla && $cantidad > 0) {
                        // Fallback: Guardar como UNISEX si no hay género
                        $registros[] = [
                            'prenda_pedido_id' => $prendaId,
                            'genero' => 'UNISEX',  // Fallback conservador
                            'talla' => (string)$talla,
                            'cantidad' => (int)$cantidad,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }
            }

            // Guardar registros en tabla relacional
            if (!empty($registros)) {
                DB::table('prenda_pedido_tallas')->insert($registros);
                
                Log::info(' [PrendaTallaService] Tallas guardadas correctamente', [
                    'prenda_pedido_id' => $prendaId,
                    'total_registros' => count($registros),
                    'total_cantidad' => array_sum(array_column($registros, 'cantidad')),
                ]);
            }
        } catch (\Exception $e) {
            Log::error(' [PrendaTallaService] Error al guardar tallas', [
                'prenda_pedido_id' => $prendaId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Detectar si estructura es relacional {GENERO: {TALLA: CANTIDAD}} 
     * o legacy {TALLA: CANTIDAD}
     * 
     * Heurística:
     * - Si tiene claves que son DAMA/CABALLERO/UNISEX → relacional
     * - Si primer valor es array → relacional
     * - Sino → legacy
     */
    private function esEstructuraRelacional(array $estructura): bool
    {
        if (empty($estructura)) {
            return false;
        }

        $primeraClaveEsGenero = in_array(
            strtoupper(array_key_first($estructura)),
            ['DAMA', 'CABALLERO', 'UNISEX']
        );
        
        if ($primeraClaveEsGenero) {
            return true;
        }

        // Si primer valor es array, es relacional
        $primerValor = reset($estructura);
        return is_array($primerValor);
    }
}
