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

    /**
     * Alias legacy para compatibilidad.
     */
    public function obtenerValor(): string
    {
        return $this->valor();
    }

    public static function extraSmall(): self
    {
        return new self('XS');
    }

    public static function small(): self
    {
        return new self('S');
    }

    public static function medium(): self
    {
        return new self('M');
    }

    public static function large(): self
    {
        return new self('L');
    }

    public static function xlarge(): self
    {
        return new self('XL');
    }

    public static function xxlarge(): self
    {
        return new self('XXL');
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
