<?php

namespace App\Domain\Cotizacion\ValueObjects;

use InvalidArgumentException;

/**
 * NumeroCotizacion - Value Object que representa el número único de cotización
 *
 * Formato: COT-XXXXX (ej: COT-00014)
 * Reglas:
 * - Puede ser null (para borradores)
 * - Debe cumplir el formato COT-XXXXX
 * - Inmutable (readonly)
 */
final readonly class NumeroCotizacion
{
    private ?string $valor;

    /**
     * Constructor privado - usar factory methods
     */
    private function __construct(?string $valor)
    {
        if ($valor !== null) {
            $this->validar($valor);
        }
        $this->valor = $valor;
    }

    /**
     * Factory method para crear desde un string
     */
    public static function crear(?string $valor): self
    {
        return new self($valor);
    }

    /**
     * Factory method para crear un número vacío (para borradores)
     */
    public static function vacio(): self
    {
        return new self(null);
    }

    /**
     * Factory method para generar un nuevo número
     */
    public static function generar(int $secuencial): self
    {
        $numero = sprintf('COT-%05d', $secuencial);
        return new self($numero);
    }

    /**
     * Validar el formato del número
     */
    private function validar(string $valor): void
    {
        if (!preg_match('/^COT-\d{5}$/', $valor)) {
            throw new InvalidArgumentException(
                "El número de cotización debe tener el formato COT-XXXXX, recibido: {$valor}"
            );
        }
    }

    /**
     * Obtener el valor del número
     */
    public function valor(): ?string
    {
        return $this->valor;
    }

    /**
     * Verificar si tiene número asignado
     */
    public function tieneNumero(): bool
    {
        return $this->valor !== null;
    }

    /**
     * Verificar si está vacío (para borradores)
     */
    public function estaVacio(): bool
    {
        return $this->valor === null;
    }

    /**
     * Comparar con otro NumeroCotizacion
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
        return $this->valor ?? '';
    }
}
