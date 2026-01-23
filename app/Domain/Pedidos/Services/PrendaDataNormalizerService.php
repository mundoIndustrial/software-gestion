<?php

namespace App\Domain\Pedidos\Services;

use Illuminate\Support\Facades\Log;

/**
 * PrendaDataNormalizerService
 * 
 * Responsabilidad: Normalizar y transformar datos de entrada de prendas
 * - Convertir DTOs a arrays
 * - Procesar gÃ©neros (string/array/JSON)
 * - Procesar cantidades de tallas
 * 
 * MÃ©todos puros: sin DB, sin IO, sin efectos secundarios
 */
class PrendaDataNormalizerService
{
    /**
     * Normalizar datos de prenda (DTO â†’ array)
     * 
     * Convierte objetos DTO a arrays para procesamiento uniforme
     */
    public function normalizarPrendaData(mixed $prendaData): array
    {
        if (is_object($prendaData) && method_exists($prendaData, 'toArray')) {
            $prendaData = $prendaData->toArray();
        } elseif (is_object($prendaData)) {
            $prendaData = (array)$prendaData;
        }
        
        if (!is_array($prendaData)) {
            throw new \InvalidArgumentException(
                'guardarPrenda: prendaData debe ser un array o DTO con toArray(). Recibido: ' . gettype($prendaData)
            );
        }
        
        return $prendaData;
    }

    /**
     * Procesar gÃ©nero (puede ser string, array o JSON)
     * 
     * Retorna siempre un array normalizado
     */
    public function procesarGenero(mixed $generoInput): array
    {
        $generoProcesado = [];
        
        if (is_array($generoInput)) {
            $generoProcesado = array_filter($generoInput, fn($g) => !empty($g));
        } elseif (is_string($generoInput)) {
            if (str_starts_with($generoInput, '[')) {
                $decoded = json_decode($generoInput, true);
                $generoProcesado = is_array($decoded) ? array_filter($decoded) : (!empty($generoInput) ? [$generoInput] : []);
            } else {
                $generoProcesado = !empty($generoInput) ? [$generoInput] : [];
            }
        }
        
        return $generoProcesado;
    }

    /**
     * Procesar cantidad_talla (puede ser string JSON o array)
     * 
     * Retorna siempre un array normalizado
     */
    public function procesarCantidadTalla(mixed $cantidadesInput): array
    {
        $cantidadTallaFinal = [];
        
        if (!$cantidadesInput) {
            return $cantidadTallaFinal;
        }
        
        if (is_string($cantidadesInput)) {
            $cantidadesInput = json_decode($cantidadesInput, true) ?? [];
            Log::info('ðŸ”„ JSON decodificado');
        }
        
        if (is_array($cantidadesInput)) {
            $cantidadTallaFinal = $cantidadesInput;
        }
        
        return $cantidadTallaFinal;
    }
}

