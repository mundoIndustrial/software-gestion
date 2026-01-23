<?php

namespace App\Domain\PedidoProduccion\ValueObjects;

use InvalidArgumentException;

/**
 * Cliente
 * 
 * Value Object que encapsula el nombre/identificación del cliente
 */
class Cliente
{
    private string $valor;

    public function __construct(string $valor)
    {
        $valor = trim($valor);

        if (empty($valor)) {
            throw new InvalidArgumentException("Cliente no puede estar vacío");
        }

        if (strlen($valor) > 255) {
            throw new InvalidArgumentException(
                "Cliente no puede exceder 255 caracteres. Recibido: " . strlen($valor)
            );
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

    public function contieneTexto(string $texto): bool
    {
        return stripos($this->valor, $texto) !== false;
    }

    public function __toString(): string
    {
        return $this->valor;
    }
}
