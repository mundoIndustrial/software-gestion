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

        // Por ahora se asumen los fijos

        Cache::put($cacheKey, $festivos, now()->addYear());

        return $festivos;
    }

    /**
     * Calcular días hábiles entre dos fechas (excluyendo sábados, domingos y festivos)
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

        // Si las fechas son iguales o fin es antes de inicio, retornar 0
        if ($inicio->format('Y-m-d') === $fin->format('Y-m-d') || $fin < $inicio) {
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
            // Verificar si no es fin de semana (sábado 6 o domingo 0) y no es festivo
            if ($actual->dayOfWeek !== 0 && $actual->dayOfWeek !== 6 && !in_array($actual->toDateString(), $festivos)) {
                $diasHabiles++;
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

    /**
     * Calcular días hábiles totales desde creación hasta hoy (sin restar 1)
     * Usa para cálculos absolutos, no para diferencias.
     * @param Carbon|string $fechaInicio
     * @return int Número de días hábiles desde la fecha
     */
    public static function calcularDiasHabilesSinIncluirInicio($fechaInicio): int
    {
        if (!$fechaInicio) {
            return 0;
        }

        $inicio = $fechaInicio instanceof Carbon ? $fechaInicio : Carbon::parse($fechaInicio);
        $ahora = Carbon::now();

        // Si las fechas son iguales, retornar 0
        if ($inicio->format('Y-m-d') === $ahora->format('Y-m-d')) {
            return 0;
        }

        $diasHabiles = 0;
        $festivos = self::obtenerFestivos($inicio->year);

        // Agregar festivos del siguiente año si es necesario
        if ($ahora->year > $inicio->year) {
            $festivos = array_merge($festivos, self::obtenerFestivos($ahora->year));
        }

        $actual = $inicio->copy();

        while ($actual <= $ahora) {
            // Verificar si no es fin de semana (sábado 6 o domingo 0) y no es festivo
            if ($actual->dayOfWeek !== 0 && $actual->dayOfWeek !== 6 && !in_array($actual->toDateString(), $festivos)) {
                $diasHabiles++;
            }
            $actual->addDay();
        }

        // Restar 1 porque no se cuenta el día de inicio
        return max(0, $diasHabiles - 1);
    }

    /**
     * Validar si un pedido está en retraso
     * @param string $areaActual Área/proceso actual del pedido
     * @param Carbon|string|null $fechaEstimada Fecha estimada de entrega
     * @return bool true si está en retraso
     */
    public static function estaEnRetraso($areaActual, $fechaEstimada): bool
    {
        // Si no tiene fecha estimada, no está en retraso
        if (!$fechaEstimada) {
            return false;
        }

        // Si ya está entregado o despachado, no está en retraso
        if (in_array($areaActual, ['Entrega', 'Despacho'])) {
            return false;
        }

        $fechaEst = $fechaEstimada instanceof Carbon
            ? $fechaEstimada->toDateString()
            : Carbon::parse($fechaEstimada)->toDateString();

        return Carbon::now()->toDateString() > $fechaEst;
    }

    /**
     * Calcular días de retraso
     * @param Carbon|string|null $fechaEstimada
     * @return int Número de días de retraso (0 si no está retrasado)
     */
    public static function calcularDiasDeRetraso($fechaEstimada): int
    {
        if (!$fechaEstimada) {
            return 0;
        }

        $fechaEst = $fechaEstimada instanceof Carbon ? $fechaEstimada : Carbon::parse($fechaEstimada);

        if ($fechaEst->toDateString() >= Carbon::now()->toDateString()) {
            return 0;
        }

        $diasRetraso = self::calcularDiasHabiles($fechaEst, Carbon::now());
        return max(0, $diasRetraso ?? 0);
    }
}
