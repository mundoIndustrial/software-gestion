<?php

namespace App\Domain\LogoCotizacion\ValueObjects;

/**
 * Talla - Value Object que representa una talla de prenda
 * 
 * Ejemplos: XS, S, M, L, XL, 2XL, etc
 */
final class Talla
{
    private string $valor;

    public function __construct(string $valor)
    {
        $valor = trim($valor);
        if (empty($valor)) {
            throw new \InvalidArgumentException('La talla no puede estar vacía');
        }

        $this->valor = strtoupper($valor);
    }

    public function valor(): string
    {
        return $this->valor;
    }

    public function equals(self $other): bool
    {
        return $this->valor === $other->valor();
    }

    public function __toString(): string
    {
        return $this->valor;
    }
}
