<?php

namespace App\Application\Services;

use Carbon\Carbon;

/**
 * DiaLaboralCalculatorMultiAPI
 *
 * Compatibilidad retroactiva: mantiene el mismo contrato público,
 * pero ahora usa exclusivamente cmixin/business-day (co-national),
 * sin consumir APIs externas.
 */
class DiaLaboralCalculatorMultiAPI
{
    /**
     * Días hábiles desde $fechaInicio (exclusive) hasta hoy (inclusive).
     */
    public function calcular(?Carbon $fechaInicio): int
    {
        if (!$fechaInicio) {
            return 0;
        }

        $inicioConteo = $fechaInicio->copy()->startOfDay()->addDay();
        $hoy = Carbon::now()->startOfDay();

        if ($inicioConteo->isAfter($hoy)) {
            return 0;
        }

        $diasHabiles = 0;
        $actual = $inicioConteo->copy();

        while ($actual->lte($hoy)) {
            if ($actual->isBusinessDay()) {
                $diasHabiles++;
            }
            $actual->addDay();
        }

        return $diasHabiles;
    }
}

