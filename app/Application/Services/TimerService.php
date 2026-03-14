<?php

namespace App\Application\Services;

/**
 * TimerService
 * 
 * RESPONSABILIDAD ÚNICA:
 * - Encapsular medición de tiempos
 * - Eliminar `microtime()` y conversiones dispersas en el código
 * - Proporcionar interfaz limpia para timing
 * 
 * ANTES (ANTIPATRÓN):
 * ```php
 * $inicio = microtime(true);
 * // ... código
 * $tiempo = round((microtime(true) - $inicio) * 1000, 2);
 * ```
 * 
 * AHORA (CORRECTO):
 * ```php
 * $timer = $this->timerService->iniciar('carga-clientes');
 * // ... código
 * $tiempo = $timer->obtenerMs();
 * ```
 */
class TimerService
{
    private array $timers = [];

    /**
     * Iniciar un timer con nombre
     * 
     * @param string $nombre Identificador único del timer
     * @return Timer
     */
    public function iniciar(string $nombre): Timer
    {
        $timer = new Timer($nombre);
        $this->timers[$nombre] = $timer;
        return $timer;
    }

    /**
     * Obtener tiempo transcurrido en milisegundos
     * 
     * @param string $nombre
     * @return float
     */
    public function obtenerMs(string $nombre): float
    {
        return $this->timers[$nombre]?->obtenerMs() ?? 0;
    }

    /**
     * Obtener tiempo transcurrido en segundos
     * 
     * @param string $nombre
     * @return float
     */
    public function obtenerSeg(string $nombre): float
    {
        return $this->timers[$nombre]?->obtenerSeg() ?? 0;
    }

    /**
     * Obtener todos los timers registrados
     * 
     * @return array [nombre => tiempo_ms]
     */
    public function obtenerTodos(): array
    {
        $resultado = [];
        foreach ($this->timers as $nombre => $timer) {
            $resultado[$nombre] = $timer->obtenerMs();
        }
        return $resultado;
    }
}

/**
 * Timer
 * 
 * Objeto que representa un timer individual
 */
class Timer
{
    private float $inicio;
    private string $nombre;

    public function __construct(string $nombre)
    {
        $this->nombre = $nombre;
        $this->inicio = microtime(true);
    }

    /**
     * Obtener tiempo transcurrido en milisegundos
     * 
     * @param int $precisión Decimales a mostrar
     * @return float
     */
    public function obtenerMs(int $precision = 2): float
    {
        $transcurrido = microtime(true) - $this->inicio;
        return round($transcurrido * 1000, $precision);
    }

    /**
     * Obtener tiempo transcurrido en segundos
     * 
     * @param int $precisión Decimales a mostrar
     * @return float
     */
    public function obtenerSeg(int $precision = 4): float
    {
        $transcurrido = microtime(true) - $this->inicio;
        return round($transcurrido, $precision);
    }

    /**
     * Reset del timer
     */
    public function resetear(): void
    {
        $this->inicio = microtime(true);
    }

    /**
     * Obtener nombre del timer
     */
    public function obtenerNombre(): string
    {
        return $this->nombre;
    }
}
