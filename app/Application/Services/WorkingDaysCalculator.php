<?php

namespace App\Application\Services;

use App\Models\Festivo;
use Carbon\Carbon;

/**
 * WorkingDaysCalculator
 *
 * Calcula días hábiles entre dos fechas excluyendo fines de semana
 * y festivos registrados en la base de datos.
 */
class WorkingDaysCalculator
{
    /**
     * Días hábiles transcurridos desde $inicio hasta hoy (exclusive del día de inicio).
     */
    public function desdeInicio(Carbon $inicio): int
    {
        return $this->calcular($inicio, Carbon::now());
    }

    /**
     * Días hábiles entre dos fechas (exclusive del día de inicio, inclusive del fin).
     */
    public function calcular(Carbon $inicio, Carbon $fin): int
    {
        try {
            $festivosSet = $this->cargarFestivos();
            $current = $inicio->copy()->addDay();
            $totalDays = 0;
            $iterations = 0;

            while ($current <= $fin && $iterations < 365) {
                $isWeekend = $current->dayOfWeek === 0 || $current->dayOfWeek === 6;
                $isFestivo = isset($festivosSet[$current->format('Y-m-d')]);

                if (!$isWeekend && !$isFestivo) {
                    $totalDays++;
                }

                $current->addDay();
                $iterations++;
            }

            return max(0, $totalDays);
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function cargarFestivos(): array
    {
        $festivosSet = [];
        foreach (Festivo::pluck('fecha')->toArray() as $f) {
            try {
                $festivosSet[Carbon::parse($f)->format('Y-m-d')] = true;
            } catch (\Exception $e) {
                // ignorar fecha inválida
            }
        }
        return $festivosSet;
    }
}
