<?php

namespace App\Domain\Services;

use Carbon\Carbon;
use App\Services\FestivosColombiaService;

/**
 * Domain Service para cálculos de órdenes
 * Responsable de: 
 * - Cálculo de días hábiles
 * - Cálculo de fechas estimadas
 * - Validaciones de negocio relacionadas con fechas
 */
class OrderCalculationService
{
    private FestivosColombiaService $festivosService;

    public function __construct(FestivosColombiaService $festivosService)
    {
        $this->festivosService = $festivosService;
    }

    /**
     * Calcular días hábiles entre dos fechas
     * Excluye fines de semana y festivos
     */
    public function calcularDiasHabiles(Carbon $fechaInicio, Carbon $fechaFin = null): int
    {
        if ($fechaFin === null) {
            $fechaFin = Carbon::now();
        }

        $festivos = $this->obtenerFestivosFormateados($fechaInicio->year, $fechaFin->year);
        
        $current = $fechaInicio->copy()->addDay(); // Saltar al próximo día
        $totalDays = 0;
        $maxIterations = 365;
        $iterations = 0;

        while ($current <= $fechaFin && $iterations < $maxIterations) {
            $dateString = $current->format('Y-m-d');
            $isWeekend = $current->dayOfWeek === 0 || $current->dayOfWeek === 6;
            $isFestivo = isset($festivos[$dateString]);

            if (!$isWeekend && !$isFestivo) {
                $totalDays++;
            }

            $current->addDay();
            $iterations++;
        }

        return max(0, $totalDays);
    }

    /**
     * Calcular fecha estimada sumando días hábiles
     */
    public function calcularFechaEstimada(Carbon $fechaInicio, int $diasHabiles): Carbon
    {
        if ($diasHabiles <= 0) {
            return $fechaInicio->copy();
        }

        $fecha = $fechaInicio->copy();
        $festivos = $this->obtenerFestivosFormateados($fecha->year, $fecha->addYears(1)->year);
        $fecha->subYears(1); // Volver a la fecha original

        $diasAgregados = 0;

        while ($diasAgregados < $diasHabiles) {
            $fecha->addDay();

            $diaSemana = $fecha->dayOfWeek;
            $esFinde = ($diaSemana === 0 || $diaSemana === 6);
            $esFestivo = isset($festivos[$fecha->format('Y-m-d')]);

            if (!$esFinde && !$esFestivo) {
                $diasAgregados++;
            }
        }

        return $fecha;
    }

    /**
     * Validar que un día de entrega sea válido
     */
    public function validarDiaEntrega(int $dia): bool
    {
        return $dia >= 1 && $dia <= 35;
    }

    /**
     * Obtener festivos formateados en diccionario
     */
    private function obtenerFestivosFormateados(int $yearInicio, int $yearFin): array
    {
        $festivos = array_merge(
            $this->festivosService->obtenerFestivos($yearInicio),
            $this->festivosService->obtenerFestivos($yearFin)
        );

        $festivosSet = [];
        foreach ($festivos as $f) {
            try {
                $festivosSet[Carbon::parse($f)->format('Y-m-d')] = true;
            } catch (\Exception $e) {
                // Ignorar festivos con formato inválido
            }
        }

        return $festivosSet;
    }
}
