<?php

namespace App\Infrastructure\Http\Controllers\Traits;

use Carbon\Carbon;
use App\Services\FestivosService;

trait CalculateWorkingDays
{
    /**
     * Calcula los días hábiles entre dos fechas excluyendo festivos y fines de semana
     * Usa caché de festivos para optimizar queries
     * 
     * @param Carbon $fechaInicio
     * @param Carbon|null $fechaFin
     * @return int
     */
    protected function calcularDiasHabiles(Carbon $fechaInicio, ?Carbon $fechaFin = null): int
    {
        try {
            $fechaFin = $fechaFin ?? Carbon::now();
            
            // Obtener festivos cacheados (24h TTL)
            $festivosSet = FestivosService::obtenerFestivosSet();
            
            // Calcular días hábiles
            $current = $fechaInicio->copy()->addDay();
            $totalDays = 0;
            $maxIterations = 730;
            $iterations = 0;
            
            while ($current <= $fechaFin && $iterations < $maxIterations) {
                $dateString = $current->format('Y-m-d');
                $isWeekend = $current->dayOfWeek === 0 || $current->dayOfWeek === 6;
                $isFestivo = isset($festivosSet[$dateString]);
                
                if (!$isWeekend && !$isFestivo) {
                    $totalDays++;
                }
                
                $current->addDay();
                $iterations++;
            }
            
            return max(0, $totalDays);
            
        } catch (\Exception $e) {
            \Log::error('Error calculando días hábiles: ' . $e->getMessage());
            return 0;
        }
    }
}
