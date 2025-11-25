<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Servicio para calcular días excluyendo fines de semana y festivos
 * Similar a la lógica de tabla_original
 */
class CalculadorDiasService
{
    /**
     * Obtener los festivos del año
     */
    public static function obtenerFestivos($anio = null)
    {
        if (!$anio) {
            $anio = date('Y');
        }

        // Cache por año
        $cacheKey = "festivos_{$anio}";
        
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $festivos = [
            // Festivos fijos
            Carbon::create($anio, 1, 1)->toDateString(),   // Año Nuevo
            Carbon::create($anio, 5, 1)->toDateString(),   // Día del Trabajo
            Carbon::create($anio, 7, 1)->toDateString(),   // Día de la Independencia
            Carbon::create($anio, 7, 20)->toDateString(),  // Grito de Independencia
            Carbon::create($anio, 8, 7)->toDateString(),   // Batalla de Boyacá
            Carbon::create($anio, 12, 8)->toDateString(),  // Inmaculada Concepción
            Carbon::create($anio, 12, 25)->toDateString(), // Navidad
        ];

        // TODO: Agregar festivos movibles (Viernes Santo, Ascensión, Corpus Christi, Sagrado Corazón)
        // Por ahora se asumen los fijos

        Cache::put($cacheKey, $festivos, now()->addYear());

        return $festivos;
    }

    /**
     * Calcular días hábiles entre dos fechas (excluyendo sábados, domingos y festivos)
     * 
     * @param mixed $fechaInicio - Fecha de inicio (string o Carbon)
     * @param mixed $fechaFin - Fecha de fin (string o Carbon)
     * @return int|null - Número de días hábiles o null si falta información
     */
    public static function calcularDiasHabiles($fechaInicio, $fechaFin)
    {
        if (!$fechaInicio || !$fechaFin) {
            return null;
        }

        $inicio = $fechaInicio instanceof Carbon ? $fechaInicio : Carbon::parse($fechaInicio);
        $fin = $fechaFin instanceof Carbon ? $fechaFin : Carbon::parse($fechaFin);

        // Si las fechas son iguales, retornar 0 (no cuenta el mismo día)
        if ($inicio->format('Y-m-d') === $fin->format('Y-m-d')) {
            return 0;
        }

        // Si fin es antes de inicio, retornar 0
        if ($fin < $inicio) {
            return 0;
        }

        $diasHabiles = 0;
        $festivos = self::obtenerFestivos($inicio->year);
        
        // Agregar festivos del siguiente año si es necesario
        if ($fin->year > $inicio->year) {
            $festivos = array_merge($festivos, self::obtenerFestivos($fin->year));
        }

        $actual = $inicio->copy();
        
        while ($actual <= $fin) {
            // Verificar si no es sábado (6) ni domingo (0)
            if ($actual->dayOfWeek !== 0 && $actual->dayOfWeek !== 6) {
                // Verificar si no es festivo
                if (!in_array($actual->toDateString(), $festivos)) {
                    $diasHabiles++;
                }
            }
            
            $actual->addDay();
        }

        // Restar 1 porque no se cuenta el día de inicio (igual que tabla_original)
        return max(0, $diasHabiles - 1);
    }

    /**
     * Calcular días en formato texto (como en tabla_original)
     * Retorna: "5 días" o "1 día"
     */
    public static function formatearDias($dias)
    {
        if (!is_numeric($dias)) {
            return null;
        }

        $dias = (int) $dias;

        if ($dias === 1) {
            return "1 día";
        }

        return "{$dias} días";
    }

    /**
     * Calcular días desde una fecha hasta hoy
     */
    public static function calcularDiasHastahoy($fechaInicio)
    {
        return self::calcularDiasHabiles($fechaInicio, Carbon::now());
    }

    /**
     * Validar si una fecha es fin de semana
     */
    public static function esFinDeSemana($fecha)
    {
        $carbon = $fecha instanceof Carbon ? $fecha : Carbon::parse($fecha);
        return $carbon->dayOfWeek === 0 || $carbon->dayOfWeek === 6;
    }

    /**
     * Validar si una fecha es festivo
     */
    public static function esFestivo($fecha)
    {
        $carbon = $fecha instanceof Carbon ? $fecha : Carbon::parse($fecha);
        $festivos = self::obtenerFestivos($carbon->year);
        
        return in_array($carbon->toDateString(), $festivos);
    }

    /**
     * Obtener el próximo día hábil
     */
    public static function proximoDiaHabil($fecha)
    {
        $carbon = $fecha instanceof Carbon ? $fecha : Carbon::parse($fecha);
        $festivos = self::obtenerFestivos($carbon->year);

        do {
            $carbon->addDay();
        } while (
            $carbon->dayOfWeek === 0 || 
            $carbon->dayOfWeek === 6 || 
            in_array($carbon->toDateString(), $festivos)
        );

        return $carbon;
    }
}
