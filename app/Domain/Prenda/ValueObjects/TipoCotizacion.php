<?php

namespace App\Domain\Prenda\ValueObjects;

class TipoCotizacion
{
    private const REFLECTIVO = 'Reflectivo';
    private const LOGO = 'Logo';
    private const BORDADO = 'Bordado';
    private const PRENDA = 'Prenda';

    private function __construct(private string $nombre)
    {
        if (empty($nombre)) {
            throw new \InvalidArgumentException('TipoCotizacion no puede estar vacío');
        }
    }

    public static function desde(string $nombre): self
    {
        return new self($nombre);
    }

    public static function reflectivo(): self
    {
        return new self(self::REFLECTIVO);
    }

    public static function logo(): self
    {
        return new self(self::LOGO);
    }

    public static function bordado(): self
    {
        return new self(self::BORDADO);
    }

    public static function prenda(): self
    {
        return new self(self::PRENDA);
    }

    public function esReflectivo(): bool
    {
        return strtolower($this->nombre) === strtolower(self::REFLECTIVO);
    }

    public function esLogo(): bool
    {
        return strtolower($this->nombre) === strtolower(self::LOGO);
    }

    public function esBordado(): bool
    {
        return strtolower($this->nombre) === strtolower(self::BORDADO);
    }

    public function nombre(): string
    {
        return $this->nombre;
    }

    public function igual(self $otro): bool
    {
        return strtolower($this->nombre) === strtolower($otro->nombre);
    }

    public function __toString(): string
    {
        return $this->nombre;
    }
}
