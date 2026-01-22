<?php

namespace App\Domain\Pedidos\ValueObjects;

/**
 * Value Object: NumeroPedido
 * 
 * Representa el número único de un pedido
 * - Immutable
 * - Validado
 * - Único en el dominio
 */
class NumeroPedido
{
    private string $valor;

    private function __construct(string $valor)
    {
        $this->validar($valor);
        $this->valor = $valor;
    }

    public static function desde(string $valor): self
    {
        return new self($valor);
    }

    public static function generar(): self
    {
        $numero = 'PED-' . date('YmdHis') . '-' . rand(1000, 9999);
        return new self($numero);
    }

    private function validar(string $valor): void
    {
        if (empty($valor)) {
            throw new \InvalidArgumentException('Número de pedido no puede estar vacío');
        }

        if (strlen($valor) > 50) {
            throw new \InvalidArgumentException('Número de pedido muy largo (máx 50 caracteres)');
        }

        if (!preg_match('/^[A-Z0-9\-]+$/', $valor)) {
            throw new \InvalidArgumentException('Número de pedido contiene caracteres inválidos');
        }
    }

    public function valor(): string
    {
        return $this->valor;
    }

    public function esIgualA(NumeroPedido $otro): bool
    {
        return $this->valor === $otro->valor;
    }

    public function __toString(): string
    {
        return $this->valor;
    }
}
