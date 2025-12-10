<?php

namespace App\Domain\Shared\ValueObjects;

use InvalidArgumentException;

/**
 * UserId - Value Object compartido para ID de usuario
 *
 * Reglas:
 * - Debe ser un entero positivo
 * - Inmutable (readonly)
 */
final readonly class UserId
{
    private int $valor;

    /**
     * Constructor privado
     */
    private function __construct(int $valor)
    {
        $this->validar($valor);
        $this->valor = $valor;
    }

    /**
     * Factory method
     */
    public static function crear(int $valor): self
    {
        return new self($valor);
    }

    /**
     * Validar
     */
    private function validar(int $valor): void
    {
        if ($valor <= 0) {
            throw new InvalidArgumentException('El ID de usuario debe ser un entero positivo');
        }
    }

    /**
     * Obtener valor
     */
    public function valor(): int
    {
        return $this->valor;
    }

    /**
     * Comparar
     */
    public function equals(self $otro): bool
    {
        return $this->valor === $otro->valor;
    }

    /**
     * String
     */
    public function __toString(): string
    {
        return (string) $this->valor;
    }
}
