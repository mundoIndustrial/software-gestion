<?php

namespace App\Domain\Cotizacion\ValueObjects;

use InvalidArgumentException;

/**
 * Asesora - Value Object que representa el nombre de la asesora/asesor
 *
 * Reglas:
 * - No puede estar vacío
 * - Máximo 255 caracteres
 * - Inmutable (readonly)
 */
final readonly class Asesora
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
    public static function crear(?string $valor): self
    {
        // Si el valor es null o vacío, usar "Sin asesora"
        $valor = $valor ?: 'Sin asesora';
        return new self($valor);
    }

    /**
     * Validar el valor de la asesora
     */
    private function validar(string $valor): void
    {
        $valor = trim($valor);

        if (empty($valor)) {
            throw new InvalidArgumentException('El nombre de la asesora no puede estar vacío');
        }

        if (strlen($valor) > 255) {
            throw new InvalidArgumentException('El nombre de la asesora no puede exceder 255 caracteres');
        }
    }

    /**
     * Obtener el valor de la asesora
     */
    public function valor(): string
    {
        return $this->valor;
    }

    /**
     * Comparar con otra Asesora
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
