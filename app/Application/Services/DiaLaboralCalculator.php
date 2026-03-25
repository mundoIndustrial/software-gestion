<?php

namespace App\Application\Services;

use App\Models\Festivo;
use Carbon\Carbon;

/**
 * DiaLaboralCalculator
 * 
 * Responsabilidad: Calcular días laborales (excluyendo fines de semana y festivos)
 */
class DiaLaboralCalculator
{
    /**
     * Calcular días laborales desde la creación de la orden hasta ahora
     * Excluye: fin de semanas y días festivos
     * 
     * @param Carbon|null $fechaInicio
     * @return int
     */
    public function calcular(?Carbon $fechaInicio): int
    {
        try {
            if (!$fechaInicio) {
                return 0;
            }

            $fechaFin = Carbon::now();

            $festivosArray = Festivo::pluck('fecha')->toArray();
            $festivosSet = [];
            foreach ($festivosArray as $f) {
                try {
                    $festivosSet[Carbon::parse($f)->format('Y-m-d')] = true;
                } catch (\Exception $e) {
                }
            }

            $current = $fechaInicio->copy()->addDay();
            $totalDays = 0;
            $maxIterations = 365;
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
            \Log::error('[DiaLaboralCalculator] Error calculando días laborales: ' . $e->getMessage());
            return 0;
        }
    }
}
