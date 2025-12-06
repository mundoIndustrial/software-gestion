<?php

namespace App\Domain\Bodega\ValueObjects;

final class NumeroPedidoBodega
{
    private int $valor;

    private function __construct(int $valor)
    {
        if ($valor < 1) {
            throw new \InvalidArgumentException('El número de pedido debe ser mayor a 0');
        }
        $this->valor = $valor;
    }

    public static function crear(int $valor): self
    {
        return new self($valor);
    }

    public static function desde(mixed $valor): self
    {
        return new self((int) $valor);
    }

    public function valor(): int
    {
        return $this->valor;
    }

    public function esIgual(NumeroPedidoBodega $otro): bool
    {
        return $this->valor === $otro->valor;
    }

    public function __toString(): string
    {
        return (string) $this->valor;
    }
}
