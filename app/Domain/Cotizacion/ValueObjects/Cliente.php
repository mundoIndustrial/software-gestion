<?php

namespace App\Domain\Cotizacion\ValueObjects;

use InvalidArgumentException;

/**
 * Cliente - Value Object que representa el nombre del cliente
 *
 * Reglas:
 * - No puede estar vacío
 * - Máximo 255 caracteres
 * - Inmutable (readonly)
 */
final readonly class Cliente
{
    private string $valor;

    /**
     * Constructor privado - usar factory method
     */
    private function __construct(string $valor)
    {
        $this->validar($valor);
        $this->valor = trim($valor);
    }

    /**
     * Factory method para crear una instancia
     */
    public static function crear(string $valor): self
    {
        return new self($valor);
    }

    /**
     * Validar el valor del cliente
     */
    private function validar(string $valor): void
    {
        $valor = trim($valor);

        if (empty($valor)) {
            throw new InvalidArgumentException('El nombre del cliente no puede estar vacío');
        }

        if (strlen($valor) > 255) {
            throw new InvalidArgumentException('El nombre del cliente no puede exceder 255 caracteres');
        }
    }

    /**
     * Obtener el valor del cliente
     */
    public function valor(): string
    {
        return $this->valor;
    }

    /**
     * Comparar con otro Cliente
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
        return $this->valor;
    }
}
