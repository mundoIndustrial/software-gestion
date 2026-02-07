<?php

namespace App\Domain\Pedidos\ValueObjects;

/**
 * Value Object: OrdenItem
 * 
 * Representa la posición/orden de un item en el pedido
 * Empieza en 1 (no en 0)
 */
final class OrdenItem
{
    private int $posicion;

    private function __construct(int $posicion)
    {
        if ($posicion < 1) {
            throw new \InvalidArgumentException("La posición del item debe ser mayor a 0, recibido: {$posicion}");
        }

        $this->posicion = $posicion;
    }

    public static function primera(): self
    {
        return new self(1);
    }

    public static function desde(int $posicion): self
    {
        return new self($posicion);
    }

    public function valor(): int
    {
        return $this->posicion;
    }

    public function incrementar(): self
    {
        return new self($this->posicion + 1);
    }

    public function decrementar(): self
    {
        if ($this->posicion === 1) {
            throw new \InvalidArgumentException("No se puede decrementar la primera posición");
        }
        return new self($this->posicion - 1);
    }

    public function esPrimera(): bool
    {
        return $this->posicion === 1;
    }

    public function esIgualA(OrdenItem $otra): bool
    {
        return $this->posicion === $otra->posicion;
    }

    public function esMenorQue(OrdenItem $otra): bool
    {
        return $this->posicion < $otra->posicion;
    }

    public function esMayorQue(OrdenItem $otra): bool
    {
        return $this->posicion > $otra->posicion;
    }

    public function __toString(): string
    {
        return (string) $this->posicion;
    }

    public function equals(OrdenItem $otra): bool
    {
        return $this->posicion === $otra->posicion;
    }
}
