<?php

namespace App\Domain\PedidoProduccion\ValueObjects;

use InvalidArgumentException;

/**
 * NumeroPedido
 * 
 * Value Object que encapsula el número de pedido
 * Valida formato y características del número
 */
class NumeroPedido
{
    private string $valor;

    public function __construct(string $valor)
    {
        $valor = trim($valor);

        if (empty($valor)) {
            throw new InvalidArgumentException("Número de pedido no puede estar vacío");
        }

        if (strlen($valor) > 50) {
            throw new InvalidArgumentException(
                "Número de pedido no puede exceder 50 caracteres. Recibido: " . strlen($valor)
            );
        }

        // Validar que no contenga caracteres especiales peligrosos
        if (preg_match('/[<>"]/', $valor)) {
            throw new InvalidArgumentException("Número de pedido contiene caracteres inválidos");
        }

        $this->valor = $valor;
    }

    public function valor(): string
    {
        return $this->valor;
    }

    public function esIgualA(self $otro): bool
    {
        return $this->valor === $otro->valor();
    }

    public function __toString(): string
    {
        return $this->valor;
    }
}
