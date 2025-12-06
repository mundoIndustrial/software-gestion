<?php

namespace App\Domain\Bodega\ValueObjects;

final class FormaPagoBodega
{
    private string $valor;

    private function __construct(string $valor)
    {
        if (empty(trim($valor))) {
            throw new \InvalidArgumentException('La forma de pago no puede estar vacía');
        }

        $this->valor = trim($valor);
    }

    public static function crear(string $valor): self
    {
        return new self($valor);
    }

    public static function desde(mixed $valor): self
    {
        return new self((string) $valor);
    }

    public function valor(): string
    {
        return $this->valor;
    }

    public function esIgual(FormaPagoBodega $otra): bool
    {
        return $this->valor === $otra->valor;
    }

    public function __toString(): string
    {
        return $this->valor;
    }
}
