<?php

namespace App\Domain\Cotizacion\Services;

use App\Models\Cotizacion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * GeneradorNumeroCotizacionService
 * 
 * Domain Service para generar números de cotización de forma segura y concurrente.
 * Utiliza database locks para evitar condiciones de carrera cuando múltiples
 * usuarios envían cotizaciones simultáneamente.
 * 
 * Responsabilidad: Generar el próximo número de cotización de forma atómica
 */
class GeneradorNumeroCotizacionService
{
    /**
     * Genera el próximo número de cotización de forma segura
     * 
     * Utiliza un lock a nivel de base de datos para garantizar que solo
     * un proceso pueda generar el número a la vez.
     * 
     * @param int $tipoCotizacionId ID del tipo de cotización
     * @return string Número de cotización generado (ej: 000001)
     * @throws \Exception Si hay error al generar el número
     */
    public function generarProximo(int $tipoCotizacionId): string
    {
        return DB::transaction(function () use ($tipoCotizacionId) {
            // Usar lock pessimista para evitar condiciones de carrera
            $ultimaCotizacion = Cotizacion::where('tipo_cotizacion_id', $tipoCotizacionId)
                ->where('numero_cotizacion', '!=', null)
                ->lockForUpdate() // Lock pessimista
                ->orderBy('numero_cotizacion', 'desc')
                ->first();
            
            // Calcular próximo número
            $proximoNumero = $ultimaCotizacion 
                ? intval($ultimaCotizacion->numero_cotizacion) + 1 
                : 1;
            
            // Formatear con padding
            $numeroCotizacion = str_pad($proximoNumero, 6, '0', STR_PAD_LEFT);
            
            Log::info(' Número de cotización generado (Domain Service)', [
                'tipo_cotizacion_id' => $tipoCotizacionId,
                'numero_cotizacion' => $numeroCotizacion,
                'proximo_numero' => $proximoNumero,
                'timestamp' => now()
            ]);
            
            return $numeroCotizacion;
        }, attempts: 3); // Reintentar hasta 3 veces si hay deadlock
    }

    /**
     * Genera el próximo número para cualquier tipo de cotización
     * 
     * @return string Número de cotización generado
     */
    public function generarProximoGlobal(): string
    {
        return DB::transaction(function () {
            // Lock global sin filtrar por tipo
            $ultimaCotizacion = Cotizacion::where('numero_cotizacion', '!=', null)
                ->lockForUpdate()
                ->orderBy('numero_cotizacion', 'desc')
                ->first();
            
            $proximoNumero = $ultimaCotizacion 
                ? intval($ultimaCotizacion->numero_cotizacion) + 1 
                : 1;
            
            $numeroCotizacion = str_pad($proximoNumero, 6, '0', STR_PAD_LEFT);
            
            Log::info(' Número de cotización generado (Global)', [
                'numero_cotizacion' => $numeroCotizacion,
                'proximo_numero' => $proximoNumero,
                'timestamp' => now()
            ]);
            
            return $numeroCotizacion;
        }, attempts: 3);
    }

    /**
     * Obtiene el próximo número sin generarlo (solo lectura)
     * 
     * @param int|null $tipoCotizacionId ID del tipo de cotización (null = global)
     * @return int Próximo número
     */
    public function obtenerProximo(?int $tipoCotizacionId = null): int
    {
        $query = Cotizacion::where('numero_cotizacion', '!=', null);
        
        if ($tipoCotizacionId) {
            $query->where('tipo_cotizacion_id', $tipoCotizacionId);
        }
        
        $ultimaCotizacion = $query->orderBy('numero_cotizacion', 'desc')->first();
        
        return $ultimaCotizacion 
            ? intval($ultimaCotizacion->numero_cotizacion) + 1 
            : 1;
    }
}
