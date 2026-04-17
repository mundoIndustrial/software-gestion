<?php

namespace App\Application\Services;

use Carbon\Carbon;

/**
 * DiaLaboralCalculator
 *
 * Calcula días laborales excluyendo fines de semana y festivos colombianos.
 * Usa cmixin/business-day (co-national) — sin llamadas a APIs externas.
 */
class DiaLaboralCalculator
{
    /** @var array<string, int> Caché interno por fecha de inicio */
    private array $calculoCache = [];

    /**
     * Días hábiles desde $fechaInicio (exclusive) hasta hoy (inclusive).
     * Si se crea un viernes, el primer día hábil es el lunes siguiente = 1.
     */
    public function calcular(?Carbon $fechaInicio): int
    {
        if (!$fechaInicio) {
            return 0;
        }

        $cacheKey = $fechaInicio->copy()->startOfDay()->format('Y-m-d');

        if (isset($this->calculoCache[$cacheKey])) {
            return $this->calculoCache[$cacheKey];
        }

        $inicioConteo = $fechaInicio->copy()->startOfDay()->addDay();
        $hoy = Carbon::now()->startOfDay();

        if ($inicioConteo->isAfter($hoy)) {
            $this->calculoCache[$cacheKey] = 0;
            return 0;
        }

        $totalDays = 0;
        $current = $inicioConteo->copy();

        while ($current->lte($hoy)) {
            if ($current->isBusinessDay()) {
                $totalDays++;
            }
            $current->addDay();
        }

        $this->calculoCache[$cacheKey] = $totalDays;
        return $totalDays;
    }
}
