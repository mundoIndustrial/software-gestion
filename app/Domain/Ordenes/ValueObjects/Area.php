<?php

namespace App\Domain\Ordenes\ValueObjects;

/**
 * Value Object: Area
 * 
 * Areas de producción donde se procesa la orden.
 */
final class Area
{
    private const CORTE = 'Corte';
    private const PRODUCCION = 'Producción';
    private const POLO = 'Polo';
    private const COSTURA = 'Costura';
    private const ACABADO = 'Acabado';

    private readonly string $valor;

    private function __construct(string $valor)
    {
        if (!in_array($valor, $this->valoresPermitidos())) {
            throw new \InvalidArgumentException("Área inválida: {$valor}");
        }

        $this->valor = $valor;
    }

    public static function corte(): self
    {
        return new self(self::CORTE);
    }

    public static function produccion(): self
    {
        return new self(self::PRODUCCION);
    }

    public static function polo(): self
    {
        return new self(self::POLO);
    }

    public static function costura(): self
    {
        return new self(self::COSTURA);
    }

    public static function acabado(): self
    {
        return new self(self::ACABADO);
    }

    public static function desde(string $valor): self
    {
        return new self($valor);
    }

    public function esCorte(): bool
    {
        return $this->valor === self::CORTE;
    }

    public function esProduccion(): bool
    {
        return $this->valor === self::PRODUCCION;
    }

    public function esPolo(): bool
    {
        return $this->valor === self::POLO;
    }

    public function esCostura(): bool
    {
        return $this->valor === self::COSTURA;
    }

    public function esAcabado(): bool
    {
        return $this->valor === self::ACABADO;
    }

    public function toString(): string
    {
        return $this->valor;
    }

    public function equals(self $other): bool
    {
        return $this->valor === $other->valor;
    }

    private function valoresPermitidos(): array
    {
        return [
            self::CORTE,
            self::PRODUCCION,
            self::POLO,
            self::COSTURA,
            self::ACABADO,
        ];
    }

    public function __toString(): string
    {
        return $this->toString();
    }
}
