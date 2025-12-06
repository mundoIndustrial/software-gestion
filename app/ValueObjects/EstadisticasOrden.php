<?php

namespace App\ValueObjects;

/**
 * EstadisticasOrden
 * 
 * Value Object para encapsular estadísticas de una orden
 * Implementa getters con cálculos derivados
 * 
 * CUMPLE: Encapsulación, Cálculos derivados, Validación
 */
class EstadisticasOrden
{
    private int $total_cantidad = 0;
    private int $total_entregado = 0;
    private int $total_pendiente = 0;
    private float $porcentaje_completado = 0.0;
    private string $estado_entrega = 'No iniciado';

    /**
     * Constructor privado
     */
    private function __construct(
        int $total_cantidad,
        int $total_entregado
    ) {
        $this->total_cantidad = $total_cantidad;
        $this->total_entregado = $total_entregado;
        $this->total_pendiente = $total_cantidad - $total_entregado;
        $this->calcularPorcentaje();
        $this->determinarEstado();
    }

    /**
     * Factory method: crear desde datos
     */
    public static function create(int $total_cantidad, int $total_entregado): self
    {
        return new self($total_cantidad, $total_entregado);
    }

    /**
     * Factory method: crear desde array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            total_cantidad: (int) ($data['total_cantidad'] ?? 0),
            total_entregado: (int) ($data['total_entregado'] ?? 0)
        );
    }

    // GETTERS

    public function getTotalCantidad(): int
    {
        return $this->total_cantidad;
    }

    public function getTotalEntregado(): int
    {
        return $this->total_entregado;
    }

    public function getTotalPendiente(): int
    {
        return $this->total_pendiente;
    }

    public function getPorcentajeCompletado(): float
    {
        return $this->porcentaje_completado;
    }

    public function getEstadoEntrega(): string
    {
        return $this->estado_entrega;
    }

    public function isCompleto(): bool
    {
        return $this->total_pendiente === 0 && $this->total_cantidad > 0;
    }

    public function isVacio(): bool
    {
        return $this->total_cantidad === 0;
    }

    public function estaEnProgreso(): bool
    {
        return $this->total_entregado > 0 && $this->total_pendiente > 0;
    }

    public function noHaIniciado(): bool
    {
        return $this->total_entregado === 0 && $this->total_cantidad > 0;
    }

    /**
     * Convertir a array para respuesta JSON
     */
    public function toArray(): array
    {
        return [
            'total_cantidad' => $this->total_cantidad,
            'total_entregado' => $this->total_entregado,
            'total_pendiente' => $this->total_pendiente,
            'porcentaje_completado' => round($this->porcentaje_completado, 2),
            'estado_entrega' => $this->estado_entrega
        ];
    }

    /**
     * Calcular porcentaje de completado
     */
    private function calcularPorcentaje(): void
    {
        if ($this->total_cantidad === 0) {
            $this->porcentaje_completado = 0.0;
        } else {
            $this->porcentaje_completado = ($this->total_entregado / $this->total_cantidad) * 100;
        }
    }

    /**
     * Determinar estado basado en progreso
     */
    private function determinarEstado(): void
    {
        if ($this->isVacio()) {
            $this->estado_entrega = 'Sin prendas';
        } elseif ($this->isCompleto()) {
            $this->estado_entrega = 'Completado';
        } elseif ($this->estaEnProgreso()) {
            $this->estado_entrega = 'En progreso';
        } else {
            $this->estado_entrega = 'No iniciado';
        }
    }
}
