<?php

namespace App\Domain\Prenda\ValueObjects;

class Origen
{
    private const BODEGA = 'bodega';
    private const CONFECCION = 'confeccion';
    private const OPCIONES = [self::BODEGA, self::CONFECCION];

    private function __construct(private string $valor)
    {
        if (!in_array($valor, self::OPCIONES, true)) {
            throw new \InvalidArgumentException(
                "Origen '{$valor}' no válido. Valores: " . implode(', ', self::OPCIONES)
            );
        }
    }

    public static function bodega(): self
    {
        return new self(self::BODEGA);
    }

    public static function confeccion(): self
    {
        return new self(self::CONFECCION);
    }

    public static function desde(string $valor): self
    {
        return new self($valor);
    }

    /**
     * Crear origen según tipo de cotización
     * REGLA DE NEGOCIO: Si es Reflectivo o Logo → FUERZA bodega
     */
    public static function segunTipoCotizacion(TipoCotizacion $tipoCotizacion): self
    {
        if ($tipoCotizacion->esReflectivo() || $tipoCotizacion->esLogo()) {
            return self::bodega();
        }

        return self::confeccion();
    }

    public function esBodega(): bool
    {
        return $this->valor === self::BODEGA;
    }

    public function esConfeccion(): bool
    {
        return $this->valor === self::CONFECCION;
    }

    public function valor(): string
    {
        return $this->valor;
    }

    public function igual(self $otro): bool
    {
        return $this->valor === $otro->valor;
    }

    public function __toString(): string
    {
        return $this->valor;
    }
}
