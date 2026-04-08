<?php

namespace App\Domain\ValueObjects;

/**
 * Value Object para representar un número de pedido
 * Encapsula las validaciones y reglas de negocio
 */
class PedidoNumber
{
    private int $numero;
    private int $nextExpected;

    private function __construct(int $numero, int $nextExpected)
    {
        $this->numero = $numero;
        $this->nextExpected = $nextExpected;
    }

    public static function create(int $numero, int $nextExpected): self
    {
        if ($numero <= 0) {
            throw new \InvalidArgumentException('El número de pedido debe ser mayor a 0');
        }

        if ($nextExpected <= 0) {
            throw new \InvalidArgumentException('El siguiente número esperado debe ser mayor a 0');
        }

        return new self($numero, $nextExpected);
    }

  
    public function isNextExpected(): bool
    {
        return $this->numero === $this->nextExpected;
    }

    public function toInt(): int
    {
        return $this->numero;
    }


    public function toString(): string
    {
        return (string) $this->numero;
    }

  
    public function equals(PedidoNumber $other): bool
    {
        return $this->numero === $other->toInt();
    }
}
