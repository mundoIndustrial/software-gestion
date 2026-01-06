<?php

namespace App\Domain\LogoCotizacion\ValueObjects;

/**
 * UbicacionPrenda - Value Object que representa la ubicación de una prenda
 * 
 * Ejemplos: PECHO, ESPALDA, MANGA IZQUIERDA, MANGA DERECHA, etc
 */
final class UbicacionPrenda
{
    private string $nombre;

    public function __construct(string $nombre)
    {
        $nombre = trim($nombre);
        if (empty($nombre)) {
            throw new \InvalidArgumentException('La ubicación no puede estar vacía');
        }

        $this->nombre = strtoupper($nombre);
    }

    public static function pecho(): self
    {
        return new self('PECHO');
    }

    public static function espalda(): self
    {
        return new self('ESPALDA');
    }

    public static function mangaIzquierda(): self
    {
        return new self('MANGA IZQUIERDA');
    }

    public static function mangaDerecha(): self
    {
        return new self('MANGA DERECHA');
    }

    public function nombre(): string
    {
        return $this->nombre;
    }

    public function equals(self $other): bool
    {
        return $this->nombre === $other->nombre();
    }

    public function __toString(): string
    {
        return $this->nombre;
    }
}
