<?php

namespace App\Domain\Talleres\ValueObjects;

class ProgressoEntrega
{
    private int $cantidadEntregada;
    private int $cantidadTotal;
    private int $porcentaje;

    public function __construct(int $cantidadEntregada, int $cantidadTotal)
    {
        if ($cantidadTotal < 0 || $cantidadEntregada < 0) {
            throw new \InvalidArgumentException('Las cantidades no pueden ser negativas');
        }

        // Permitir entregas mayores (puede haber entregas extras)
        // Limitar la cantidad entregada al máximo de la total para el cálculo de porcentaje
        $this->cantidadEntregada = min($cantidadEntregada, $cantidadTotal);
        $this->cantidadTotal = $cantidadTotal;
        $this->porcentaje = $this->calcularPorcentaje();
    }

    private function calcularPorcentaje(): int
    {
        if ($this->cantidadTotal === 0) {
            return 0;
        }

        return (int) round(($this->cantidadEntregada / $this->cantidadTotal) * 100);
    }

    public function getCantidadEntregada(): int
    {
        return $this->cantidadEntregada;
    }

    public function getCantidadTotal(): int
    {
        return $this->cantidadTotal;
    }

    public function getPorcentaje(): int
    {
        return $this->porcentaje;
    }

    public function getCantidadPendiente(): int
    {
        return $this->cantidadTotal - $this->cantidadEntregada;
    }

    public function estaCompleto(): bool
    {
        return $this->porcentaje === 100;
    }

    public function estaEnProgreso(): bool
    {
        return $this->porcentaje > 0 && $this->porcentaje < 100;
    }

    public function noHaComenzado(): bool
    {
        return $this->porcentaje === 0;
    }

    public function getColor(): string
    {
        if ($this->porcentaje <= 33) {
            return '#ef4444'; // Rojo
        } elseif ($this->porcentaje <= 66) {
            return '#f59e0b'; // Amarillo
        } else {
            return '#10b981'; // Verde
        }
    }

    public function toArray(): array
    {
        return [
            'cantidad_entregada' => $this->cantidadEntregada,
            'cantidad_total' => $this->cantidadTotal,
            'cantidad_pendiente' => $this->getCantidadPendiente(),
            'porcentaje' => $this->porcentaje,
            'color' => $this->getColor(),
            'estado' => $this->estaCompleto() ? 'completo' : ($this->estaEnProgreso() ? 'en_progreso' : 'no_iniciado')
        ];
    }
}
