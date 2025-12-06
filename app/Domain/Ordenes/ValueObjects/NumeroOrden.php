<?php

namespace App\Domain\Ordenes\ValueObjects;

/**
 * Value Object: NumeroOrden
 * 
 * Representa el número único de una orden.
 * Inmutable y comparables por valor.
 */
final class NumeroOrden
{
    private readonly int $valor;

    private function __construct(int $valor)
    {
        if ($valor <= 0) {
            throw new \InvalidArgumentException('El número de orden debe ser positivo');
        }

        $this->valor = $valor;
    }

    public static function desde(int $valor): self
    {
        return new self($valor);
    }

    public function toInt(): int
    {
        return $this->valor;
    }

    public function toString(): string
    {
        return (string) $this->valor;
    }

    public function equals(self $other): bool
    {
        return $this->valor === $other->valor;
    }

    public function __toString(): string
    {
        return $this->toString();
    }
}
