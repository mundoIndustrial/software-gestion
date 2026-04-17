<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class FestivosColombiaService
{
    /**
     * Obtener festivos de Colombia para un año.
     * Fuente única: cmixin/business-day (co-national).
     */
    public static function obtenerFestivos(int $year = null): array
    {
        $year = $year ?? now()->year;

        return Cache::remember("festivos_colombia_{$year}", 2592000, function () use ($year) {
            $festivos = [];
            $fecha = Carbon::create($year, 1, 1)->startOfDay();
            $fin = $fecha->copy()->endOfYear()->startOfDay();

            while ($fecha->lte($fin)) {
                if ($fecha->isHoliday()) {
                    $festivos[] = $fecha->toDateString();
                }
                $fecha->addDay();
            }

            return $festivos;
        });
    }

    /**
     * Verificar si una fecha es festivo en Colombia.
     */
    public static function esFestivo(string $fecha): bool
    {
        return Carbon::parse($fecha)->isHoliday();
    }

    /**
     * Obtener festivos entre dos fechas.
     */
    public static function festivosEnRango(Carbon $inicio, Carbon $fin): array
    {
        $festivos = [];

        for ($year = $inicio->year; $year <= $fin->year; $year++) {
            $festivos = array_merge($festivos, self::obtenerFestivos($year));
        }

        return array_values(array_filter($festivos, function (string $festivo) use ($inicio, $fin): bool {
            $fecha = Carbon::parse($festivo);
            return $fecha->between($inicio->copy()->startOfDay(), $fin->copy()->startOfDay(), true);
        }));
    }
}
