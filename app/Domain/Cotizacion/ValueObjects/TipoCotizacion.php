<?php

namespace App\Domain\Cotizacion\ValueObjects;

/**
 * TipoCotizacion - Value Object que representa los tipos de cotización
 *
 * Tipos:
 * - PRENDA (P): Solo prendas
 * - LOGO (L): Solo logo/bordado
 * - PRENDA_LOGO (PL): Prendas con logo/bordado
 * - PRENDA_BORDADO (PB): Alias para Prendas con bordado (mapea a PL)
 * - REFLECTIVO (RF): Solo reflectivo
 */
enum TipoCotizacion: string
{
    case PRENDA = 'P';
    case LOGO = 'L';
    case PRENDA_LOGO = 'PL';
    case PRENDA_BORDADO = 'PB';
    case REFLECTIVO = 'RF';

    /**
     * Obtener etiqueta legible del tipo
     */
    public function label(): string
    {
        return match ($this) {
            self::PRENDA => 'Prenda',
            self::LOGO => 'Logo/Bordado',
            self::PRENDA_LOGO => 'Prenda + Logo/Bordado',
            self::PRENDA_BORDADO => 'Prenda + Bordado',
            self::REFLECTIVO => 'Reflectivo',
        };
    }

    /**
     * Calcular el tipo de cotización basado en lo que contiene
     */
    public static function calcularDesde(bool $tienePrendas, bool $tieneLogo): self
    {
        if ($tienePrendas && $tieneLogo) {
            return self::PRENDA_LOGO;
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
            self::PRENDA_LOGO,
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
            self::PRENDA_LOGO,
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
            self::PRENDA_LOGO => 'Cotización de prendas con logo o bordado',
            self::PRENDA_BORDADO => 'Cotización de prendas con bordado',
            self::REFLECTIVO => 'Cotización de reflectivo',
        };
    }
}
