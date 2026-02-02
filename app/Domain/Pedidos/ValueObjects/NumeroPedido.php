<?php

namespace App\Domain\Pedidos\ValueObjects;

/**
 * Value Object: NumeroPedido
 * 
 * Representa el nÃºmero Ãºnico de un pedido
 * - Immutable
 * - Validado
 * - Ãšnico en el dominio
 */
class NumeroPedido
{
    private ?string $valor;

    private function __construct(?string $valor)
    {
        if ($valor !== null) {
            $this->validar($valor);
        }
        $this->valor = $valor;
    }

    public static function desde(?string $valor): self
    {
        // Convertir strings vacíos a null para consistencia
        if ($valor === '') {
            $valor = null;
        }
        return new self($valor);
    }

    public static function generar(): self
    {
        $numero = 'PED-' . date('YmdHis') . '-' . rand(1000, 9999);
        return new self($numero);
    }

    private function validar(string $valor): void
    {
        // Solo validar si hay un valor real (no vacío)
        if (empty($valor)) {
            return;
        }

        if (strlen($valor) > 50) {
            throw new \InvalidArgumentException('Número de pedido muy largo (máx 50 caracteres)');
        }

        if (!preg_match('/^[A-Z0-9\-]+$/', $valor)) {
            throw new \InvalidArgumentException('Número de pedido contiene caracteres inválidos');
        }
    }

    public function valor(): ?string
    {
        return $this->valor;
    }

    public function esIgualA(NumeroPedido $otro): bool
    {
        return $this->valor === $otro->valor;
    }

    public function esVacio(): bool
    {
        return $this->valor === null || $this->valor === '';
    }

    public function __toString(): string
    {
        return $this->valor ?? '';
    }
}

