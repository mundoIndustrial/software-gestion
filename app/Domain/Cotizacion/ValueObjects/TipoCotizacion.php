<?php

namespace App\Domain\Cotizacion\ValueObjects;

/**
 * TipoCotizacion - Value Object que representa los tipos de cotización
 *
 * Los tipos de cotización disponibles:
 * - PRENDA (P): Solo prendas
 * - LOGO (L): Solo logo/bordado
 * - COMBINADO (PL): Prendas con logo/bordado
 * - PRENDA_BORDADO (PB): Alias para Combinado (Prenda + Bordado)
 */
enum TipoCotizacion: string
{
    case PRENDA = 'P';
    case LOGO = 'L';
    case COMBINADO = 'PL';
    case PRENDA_BORDADO = 'PB';

    /**
     * Obtener etiqueta legible del tipo
     */
    public function label(): string
    {
        return match ($this) {
            self::PRENDA => 'Prenda',
            self::LOGO => 'Logo/Bordado',
            self::COMBINADO => 'Combinado (Prenda + Logo/Bordado)',
            self::PRENDA_BORDADO => 'Combinado (Prenda + Bordado)',
        };
    }

    /**
     * Calcular el tipo de cotización basado en lo que contiene
     */
    public static function calcularDesde(bool $tienePrendas, bool $tieneLogo): self
    {
        if ($tienePrendas && $tieneLogo) {
            return self::COMBINADO;
        }

        if ($tieneLogo) {
            return self::LOGO;
        }

        // Por defecto, si no hay nada o solo prendas, es COMBINADO
        return self::COMBINADO;
    }

    /**
     * Verificar si requiere prendas
     */
    public function requierePrendas(): bool
    {
        return in_array($this, [
            self::PRENDA,
            self::COMBINADO,
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
            self::COMBINADO,
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
            self::PRENDA => 'Cotización de prendas',
            self::LOGO => 'Cotización de logo, bordado o diseño',
            self::COMBINADO => 'Cotización de prendas con logo o bordado',
            self::PRENDA_BORDADO => 'Cotización de prendas con bordado',
        };
    }
}
