<?php

namespace App\Domain\Ordenes\ValueObjects;

/**
 * Value Object: EstadoOrden
 * 
 * Estados posibles de una orden:
 * Borrador -> Aprobada -> EnProduccion -> Completada
 *                     \-> Cancelada
 */
final class EstadoOrden
{
    private const BORRADOR = 'Borrador';
    private const APROBADA = 'Aprobada';
    private const EN_PRODUCCION = 'EnProduccion';
    private const COMPLETADA = 'Completada';
    private const CANCELADA = 'Cancelada';

    private readonly string $valor;

    private function __construct(string $valor)
    {
        if (!in_array($valor, $this->valoresPermitidos())) {
            throw new \InvalidArgumentException("Estado inválido: {$valor}");
        }

        $this->valor = $valor;
    }

    public static function borrador(): self
    {
        return new self(self::BORRADOR);
    }

    public static function aprobada(): self
    {
        return new self(self::APROBADA);
    }

    public static function enProduccion(): self
    {
        return new self(self::EN_PRODUCCION);
    }

    public static function completada(): self
    {
        return new self(self::COMPLETADA);
    }

    public static function cancelada(): self
    {
        return new self(self::CANCELADA);
    }

    public static function desde(string $valor): self
    {
        return new self($valor);
    }

    public function esBorrador(): bool
    {
        return $this->valor === self::BORRADOR;
    }

    public function esAprobada(): bool
    {
        return $this->valor === self::APROBADA;
    }

    public function esEnProduccion(): bool
    {
        return $this->valor === self::EN_PRODUCCION;
    }

    public function esCompletada(): bool
    {
        return $this->valor === self::COMPLETADA;
    }

    public function esCancelada(): bool
    {
        return $this->valor === self::CANCELADA;
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
            self::BORRADOR,
            self::APROBADA,
            self::EN_PRODUCCION,
            self::COMPLETADA,
            self::CANCELADA,
        ];
    }

    public function __toString(): string
    {
        return $this->toString();
    }
}
