<?php

namespace App\Services;

use Carbon\Carbon;
use Carbon\CarbonInterface;

class DiasHabilesService
{
    private array $festivos = [];
    private bool $cacheado = false;

    public function __construct()
    {
        $this->cargarFestivos();
    }

    /**
     * Carga los festivos desde la configuración
     */
    private function cargarFestivos(): void
    {
        if ($this->cacheado) {
            return;
        }

        $config = config('dias');
        $ano = now()->year;

        // Cargar festivos fijos
        $festivosFijos = $config['festivos_fijos'] ?? [];
        foreach ($festivosFijos as $fecha => $nombre) {
            $this->festivos[] = "$ano-$fecha";
        }

        // Cargar festivos movibles del año actual
        $festivosMovibles = $config['festivos_movibles'][$ano] ?? [];
        foreach ($festivosMovibles as $fecha => $nombre) {
            $this->festivos[] = "$ano-$fecha";
        }

        $this->cacheado = true;
    }

    /**
     * Verifica si una fecha es festivo
     */
    private function esFestivo(Carbon $fecha): bool
    {
        $fechaFormato = $fecha->format('Y-m-d');
        return in_array($fechaFormato, $this->festivos, true);
    }

    /**
     * Verifica si una fecha es fin de semana
     */
    private function esFinDeSemana(Carbon $fecha): bool
    {
        return $fecha->isWeekend();
    }

    /**
     * Calcula los días hábiles entre dos fechas
     * Excluye: sábados, domingos y festivos
     */
    public function calcularDiasHabiles(Carbon $fechaInicio, Carbon $fechaFin): int
    {
        // Normalizar a solo fechas sin hora para evitar inconsistencias
        $fechaInicio = (clone $fechaInicio)->startOfDay();
        $fechaFin = (clone $fechaFin)->startOfDay();

        if ($fechaInicio->greaterThan($fechaFin)) {
            [$fechaInicio, $fechaFin] = [$fechaFin, $fechaInicio];
        }

        $diasHabiles = 0;
        $fecha = (clone $fechaInicio)->addDay();

        while ($fecha->lessThanOrEqualTo($fechaFin)) {
            // Contar solo si no es fin de semana y no es festivo
            if (!$this->esFinDeSemana($fecha) && !$this->esFestivo($fecha)) {
                $diasHabiles++;
            }
            $fecha->addDay();
        }

        return $diasHabiles;
    }

    /**
     * Obtiene la lista de festivos para el año actual
     */
    public function obtenerFestivos(int $ano = null): array
    {
        $ano = $ano ?? now()->year;
        $config = config('dias');

        $festivosFijos = $config['festivos_fijos'] ?? [];
        $festivosMovibles = $config['festivos_movibles'][$ano] ?? [];

        $festivos = [];

        foreach ($festivosFijos as $fecha => $nombre) {
            $festivos["$ano-$fecha"] = $nombre;
        }

        foreach ($festivosMovibles as $fecha => $nombre) {
            $festivos["$ano-$fecha"] = $nombre;
        }

        return $festivos;
    }
}
