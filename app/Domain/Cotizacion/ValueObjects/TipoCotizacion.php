<?php

namespace App\Domain\Cotizacion\ValueObjects;

/**
 * TipoCotizacion - Value Object que representa los tipos de cotización
 *
 * Tipos:
 * - PRENDA (P): Solo prendas
 * - LOGO (B): Solo logo/bordado
 * - PRENDA_BORDADO (PB): Prendas con logo/bordado
 */
enum TipoCotizacion: string
{
    case PRENDA = 'P';
    case LOGO = 'B';
    case PRENDA_BORDADO = 'PB';

    /**
     * Obtener etiqueta legible del tipo
     */
    public function label(): string
    {
        return match ($this) {
            self::PRENDA => 'Prenda',
            self::LOGO => 'Logo/Bordado',
            self::PRENDA_BORDADO => 'Prenda + Logo/Bordado',
        };
    }

    /**
     * Calcular el tipo de cotización basado en lo que contiene
     */
    public static function calcularDesde(bool $tienePrendas, bool $tieneLogo): self
    {
        if ($tienePrendas && $tieneLogo) {
            return self::PRENDA_BORDADO;
        }

        if ($tienePrendas) {
            return self::PRENDA;
        }

        if ($tieneLogo) {
            return self::LOGO;
        }

        // Por defecto, si no hay nada, es PRENDA
        return self::PRENDA;
    }

    /**
     * Verificar si requiere prendas
     */
    public function requierePrendas(): bool
    {
        return in_array($this, [
            self::PRENDA,
            self::PRENDA_BORDADO,
        ]);
    }

    /**
     * Verificar si requiere logo
     */
    public function requiereLogo(): bool
    {
        return in_array($this, [
            self::LOGO,
            self::PRENDA_BORDADO,
        ]);
    }

    /**
     * Obtener el código de tipo de venta para la tabla cotizaciones
     */
    public function codigoVenta(): string
    {
        return $this->value;
    }

    /**
     * Obtener descripción detallada
     */
    public function descripcion(): string
    {
        return match ($this) {
            self::PRENDA => 'Cotización de prendas (camisas, pantalones, etc.)',
            self::LOGO => 'Cotización de logo, bordado o diseño',
            self::PRENDA_BORDADO => 'Cotización de prendas con logo o bordado',
        };
    }
}
