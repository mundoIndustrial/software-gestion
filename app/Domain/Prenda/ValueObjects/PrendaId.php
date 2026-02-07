<?php

namespace App\Domain\Prenda\ValueObjects;

class PrendaId
{
    private int $valor;

    private function __construct(int $valor)
    {
        if ($valor <= 0) {
            throw new \InvalidArgumentException('PrendaId debe ser mayor a 0');
        }
        $this->valor = $valor;
    }

    public static function desde(int $valor): self
    {
        return new self($valor);
    }

    public static function generar(): self
    {
        return new self(mt_rand(1, PHP_INT_MAX));
    }

    public function valor(): int
    {
        return $this->valor;
    }

    public function igual(self $otro): bool
    {
        return $this->valor === $otro->valor;
    }

    public function __toString(): string
    {
        return (string) $this->valor;
    }
}
