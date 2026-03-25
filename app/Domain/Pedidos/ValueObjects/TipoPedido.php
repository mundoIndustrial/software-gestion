<?php

namespace App\Domain\Pedidos\ValueObjects;

/**
 * TipoPedido - Value Object
 * 
 * Encapsula la lógica de determinar el tipo de pedido
 * Garantiza que solo existen tipos válidos: LOGO, PRODUCCION
 */
final class TipoPedido
{
    const LOGO = 'logo';
    const PRODUCCION = 'produccion';

    private string $value;

    private function __construct(string $value)
    {
        if (!in_array($value, [self::LOGO, self::PRODUCCION])) {
            throw new \InvalidArgumentException(
                "Tipo de pedido inválido: {$value}. Debe ser 'logo' o 'produccion'"
            );
        }
        $this->value = $value;
    }

    /**
     * Factory: crear desde tipo_cotizacion y cotizacion_id
     * 
     * Lógica de decisión del dominio:
     * - Si tipo_cotizacion = 'logo' Y cotizacion_id existe → Pedido de LOGO
     * - Si no → Pedido de PRODUCCIÓN
     */
    public static function fromCotizacion(?string $tipoCotizacion, ?int $cotizacionId): self
    {
        $esLogo = $tipoCotizacion === 'logo' && $cotizacionId !== null;
        
        return new self(
            $esLogo ? self::LOGO : self::PRODUCCION
        );
    }

    public static function logo(): self
    {
        return new self(self::LOGO);
    }

    public static function produccion(): self
    {
        return new self(self::PRODUCCION);
    }

    public function esLogo(): bool
    {
        return $this->value === self::LOGO;
    }

    public function esProduccion(): bool
    {
        return $this->value === self::PRODUCCION;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
