<?php

namespace App\Domain\Cotizacion\ValueObjects;

use InvalidArgumentException;

/**
 * CotizacionId - Value Object que representa el ID único de una cotización
 *
 * Reglas:
 * - Debe ser un entero positivo
 * - Inmutable (readonly)
 */
final readonly class CotizacionId
{
    private int $valor;

    /**
     * Constructor privado - usar factory method
     */
    private function __construct(int $valor)
    {
        $this->validar($valor);
        $this->valor = $valor;
    }

    /**
     * Factory method para crear desde un ID
     */
    public static function crear(int $valor): self
    {
        return new self($valor);
    }

    /**
     * Factory method para crear desde un string
     */
    public static function desdeString(string $valor): self
    {
        $id = (int) $valor;
        return new self($id);
    }

    /**
     * Validar el valor del ID
     */
    private function validar(int $valor): void
    {
        if ($valor < 0) {
            throw new InvalidArgumentException('El ID de cotización no puede ser negativo');
        }
    }

    /**
     * Obtener el valor del ID
     */
    public function valor(): int
    {
        return $this->valor;
    }

    /**
     * Comparar con otro CotizacionId
     */
    public function equals(self $otro): bool
    {
        return $this->valor === $otro->valor;
    }

    /**
     * Representación en string
     */
    public function __toString(): string
    {
        return (string) $this->valor;
    }
}
